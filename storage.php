<?php
//filer owner: horvathgyozo

interface IFileIO
{
  function save($data);
  function load();
}
abstract class FileIO implements IFileIO
{
  protected $filepath;

  public function __construct($filename)
  {
    if (!is_readable($filename) || !is_writable($filename)) {
      throw new Exception("Data source ${filename} is invalid.");
    }
    $this->filepath = realpath($filename);
  }
}
class JsonIO extends FileIO
{
  public function load($assoc = true)
  {
    $file_content = file_get_contents($this->filepath);
    return json_decode($file_content, $assoc) ?: [];
  }

  public function save($data)
  {
    $json_content = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents($this->filepath, $json_content);
  }
}
class SerializeIO extends FileIO
{
  public function load()
  {
    $file_content = file_get_contents($this->filepath);
    return unserialize($file_content) ?: [];
  }

  public function save($data)
  {
    $serialized_content = serialize($data);
    file_put_contents($this->filepath, $serialized_content);
  }
}

interface IStorage
{
  function add($record): string;
  function findById(string $id);
  function findAll(array $params = []);
  function findOne(array $params = []);
  function update(string $id, $record);
  function delete(string $id);

  function findMany(callable $condition);
  function updateMany(callable $condition, callable $updater);
  function deleteMany(callable $condition);
}

class Storage implements IStorage
{
  protected $contents;
  protected $io;

  public function __construct(IFileIO $io, $assoc = true)
  {
    $this->io = $io;
    $this->contents = (array)$this->io->load($assoc);
  }

  public function __destruct()
  {
    $this->io->save($this->contents);
  }

  public function add($record): string
  {
    $id = uniqid();
    if (is_array($record)) {
      $record['id'] = $id;
    } else if (is_object($record)) {
      $record->id = $id;
    }
    $this->contents[$id] = $record;
    return $id;
  }

  public function addVotedPoll($pollId,$userId,$data) {
    $user = $this->findById($userId);
    $user['votes'][$pollId] = array_values($data);
    $this->update($userId,$user);
  }

  public function findById(string $id)
  {
    return $this->contents[$id] ?? NULL;
  }

  public function deletePollIdFromVoted($pollId) {
    $users = $this->findAll();
    foreach($users as $user) {
      unset($user['votes'][$pollId]);
      $this->update($user['id'],$user);
    }
  }

  public function findAll(array $params = [])
  {
    return array_filter($this->contents, function ($item) use ($params) {
      foreach ($params as $key => $value) {
        if (((array)$item)[$key] !== $value) {
          return FALSE;
        }
      }
      return TRUE;
    });
  }

  public function findOne(array $params = [])
  {
    $found_items = $this->findAll($params);
    $first_index = array_keys($found_items)[0] ?? NULL;
    return $found_items[$first_index] ?? NULL;
  }

  public function update(string $id, $record)
  {
    $this->contents[$id] = $record;
  }

  public function delete(string $id)
  {
    unset($this->contents[$id]);
  }

  public function findMany(callable $condition)
  {
    return array_filter($this->contents, $condition);
  }

  public function updateMany(callable $condition, callable $updater)
  {
    array_walk($all, function (&$item) use ($condition, $updater) {
      if ($condition($item)) {
        $updater($item);
      }
    });
  }

  public function deleteMany(callable $condition)
  {
    $this->contents = array_filter($this->contents, function ($item) use ($condition) {
      return !$condition($item);
    });
  }
}


class PollsStorage extends Storage
{
  public function __construct()
  {
    parent::__construct(new JsonIO('polls.json'));
  }

  public function updatePoll($id,$data)
  {
    $opts = explode("\r\n", $data['lehetosegek']);
    $oldPoll = $this->findById($id);
    $hasOptsChanged = false;

    foreach($opts as $opt) {
      if(trim($opt) === '') {
        $ind = array_search($opt,$opts);
        unset($opts[$ind]);
      } else {
        if(!in_array($opt,$oldPoll['options'])) {
          $hasOptsChanged = true;
        }
      }
    }

    if(count($opts) != count($oldPoll['options'])) $hasOptsChanged = true;
    
    $options = [];
    $newVoted = [];
    if($hasOptsChanged) {
      $answersArr = [];
      $options = $opts;
      foreach ($opts as $opt) {
        $answersArr[$opt] = 0;
      }
    } else {
      $answersArr = $oldPoll['answers'];
      $options = $oldPoll['options'];
      $newVoted = $oldPoll['voted'];
    }

    $this->update($id, [
      'id' => $id,
      'question' => $data['szovegezes'],
      'options' => $options,
      'isMultiple' => isset($data['tobbLehetoseg']),
      'createdAt' => $oldPoll['createdAt'],
      'deadline' => $data['hatarido'],
      'answers' => $answersArr,
      'voted' => $newVoted
    ]);

    return $hasOptsChanged;
  }

  public function addNewPoll($data)
  {
    $opts = explode("\r\n", $data['lehetosegek']);
    //ures lehetosegeket toroljuk
    foreach($opts as $opt) {
      if(trim($opt) === '') {
        $ind = array_search($opt,$opts);
        unset($opts[$ind]);
      }
    }

    $answersArr = [];
    foreach ($opts as $opt) {
      $answersArr[$opt] = 0;
    }
    date_default_timezone_set('Europe/Budapest');
    $this->add([
      'question' => $data['szovegezes'],
      'options' => $opts,
      'isMultiple' => isset($data['tobbLehetoseg']),
      'createdAt' => date('Y-m-d H:i:s'),
      'deadline' => $data['hatarido'],
      'answers' => $answersArr,
      'voted' => []
    ]);
  }

  public function getActivePolls(&$sortedPolls)
  {
    date_default_timezone_set('Europe/Budapest');
    $sortedPolls = $this->findMany(function ($poll) {
      return $poll['deadline'] >= date('Y-m-d');
    });

    usort($sortedPolls, function ($poll1, $poll2) {
      $createdAt1 = strtotime($poll1['createdAt']);
      $createdAt2 = strtotime($poll2['createdAt']);
      if ($createdAt1 == $createdAt2) {
        return 0;
      }
      return ($createdAt1 < $createdAt2) ? 1 : -1;
    });
  }

  public function getExpiredPolls(&$sortedPolls)
  {
    date_default_timezone_set('Europe/Budapest');
    $sortedPolls = $this->findMany(function ($poll) {
      return $poll['deadline'] < date('Y-m-d');
    });

    usort($sortedPolls, function ($poll1, $poll2) {
      $createdAt1 = strtotime($poll1['createdAt']);
      $createdAt2 = strtotime($poll2['createdAt']);
      if ($createdAt1 == $createdAt2) {
        return 0;
      }
      return ($createdAt1 < $createdAt2) ? 1 : -1;
    });
  }

  public function hasUserVoted($voteId, $userId)
  {
    return in_array($userId, $this->findOne(['id' => $voteId])['voted']);
  }

  public function isValidPoll($pollId)
  {
    return $this->findOne(['id' => $pollId]) !== NULL;
  }

  public function isActivePoll($pollId)
  {
    return $this->findOne(['id' => $pollId])['deadline'] >= date('Y-m-d');
  }
}

class UsersStorage extends Storage
{
  public function __construct()
  {
    parent::__construct(new JsonIO('users.json'));
  }

  public function validLogin($userName, $pwd)
  {
    $user = $this->findOne(['username' => $userName]);
    if ($user === NULL) return false;
    if (password_verify($pwd, $user["password"])) {
      return true;
    } else return false;
  }

  public function getVotesByPollId($pollId, $userId) {
    $user = $this->findById($userId);
    return $user['votes'][$pollId];
  }

  public function getUserName($id)
  {
    return $this->findById($id)['username'];
  }

  public function getEmail($id)
  {
    return $this->findById($id)['email'];
  }

  public function isNewEmail($email)
  {
    return count(array_filter($this->findAll([]), function ($user) use ($email) {
      return $user['email'] === $email;
    })) === 0;
  }

  public function isNewUserName($username)
  {
    return count(array_filter($this->findAll([]), function ($user) use ($username) {
      return $user['username'] === $username;
    })) === 0;
  }

  public function isNewPassword($pwd)
  {
    return count(array_filter($this->findAll([]), function ($user) use ($pwd) {
      return password_verify($pwd,$user['password']);
    })) === 0;
  }

  public function logout() {
    unset($_SESSION['userId']);
    session_destroy();
  }
}

$users_storage = new UsersStorage();
$polls_storage = new PollsStorage();
