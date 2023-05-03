<?php
session_start();
include('storage.php');

//Átirányítás, ha nem léphet erre az oldalra
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}
$id = $_GET['id'];
if (!isset($_SESSION['userId']) || !$polls_storage->isValidPoll($id) || !$polls_storage->isActivePoll($id)) {
    header('Location: index.php');
    exit();
}

//ha már szavazott, akkor lekérjük a válaszait
$lastVotes = [];
$toUpdate = $polls_storage->hasUserVoted($id,$_SESSION['userId']);
if ($toUpdate) {
    $lastVotes = $users_storage->getVotesByPollId($id, $_SESSION['userId']);
}

function validate($post, &$data, &$errors)
{
    $data = $post;
    if (count($data) === 0) {
        $errors['answer'] = 'Nincsen válasz kijelölve.';
    }
    return count($errors) === 0;
}

$poll = $polls_storage->findById($id);
$errors = [];
$data = [];
$passed = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (validate($_POST, $data, $errors)) {
        $passed = true;
        if (!$toUpdate) {
            //hozzaadjuk a szavazók listájához
            $poll['voted'][] = $_SESSION['userId'];
        } else {
            //toroljuk a regi szavazait
            foreach ($lastVotes as $lastVote) {
                $poll['answers'][$lastVote]--;
            }
        }
        //eltároljuk a válaszokat
        $users_storage->addVotedPoll($id, $_SESSION['userId'], $data);
        foreach (array_values($data) as $value) {
            $poll['answers'][$value]++;
        }
        $polls_storage->update($id, $poll);
    }
}

?>

<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szavazás</title>
    <link rel="stylesheet" href="index.css">
    <style>
        span {
            color: red;
        }
    </style>
</head>

<body>

    <?php if ($passed) : ?>
        <div style="text-align: center;">
            <h1 style="color: green">Sikeres szavazás</h1>
            <a href="index.php">Vissza a főoldalra</a>
        </div>

    <?php else : ?>

        <h1>Szavazás</h1>
        <div class="voteDiv">
            <p>Létrehozás dátuma: <b><?= $poll['createdAt'] ?></b></p>
            <p>Leadási határidő: <b><?= $poll['deadline'] ?></b></p>
            <div><b><?= $poll['question'] ?></b></div>

            <form action="vote.php?id=<?= $id ?>" method="post" novalidate>
                <span <?= isset($errors['answer']) ? '' : 'hidden' ?>><?= $errors['answer'] ?? '' ?></br></span>
                <?php if ($poll['isMultiple']) : ?>
                    <?php $i = 0;
                    foreach ($poll['options'] as $opt) :
                        $i++;
                    ?>
                        <input type="checkbox" name="answer<?= $i ?>" value="<?= $opt ?>" <?= in_array($opt,$lastVotes) ? 'checked' : '' ?>>
                        <label for="answer<?= $i ?>"><?= $opt ?></label><br>
                    <?php endforeach; ?>

                <?php else : ?>
                    <?php
                    foreach ($poll['options'] as $opt) :
                    ?>
                        <input type="radio" name="answer" value="<?= $opt ?>" <?= in_array($opt,$lastVotes) ? 'checked' : '' ?>>
                        <label for="answer"><?= $opt ?></label><br>
                <?php endforeach;
                endif; ?>
                <br>

                <a class="button-delete" style="float: right;" href="index.php">Mégsem</a>
                <input type="submit" value="Szavazás leadása" class="button-vote" style="float: left;">
            </form>
        </div>

    <?php endif; ?>


</body>

</html>