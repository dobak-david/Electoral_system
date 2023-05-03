<?php
session_start();
include('storage.php');

if (!isset($_GET['id']) || $polls_storage->findById($_GET['id']) === NULL || !isset($_SESSION['userId']) || !$users_storage->findById($_SESSION['userId'])['isAdmin']) {
    header('Location: index.php');
    exit();
}


function validate($post, &$data, &$errors)
{
    $data = $post;
    if (trim($data['szovegezes']) === '') {
        $errors['szovegezes'] = 'A szavazás szövegének megadása kötelező.';
    }

    if (trim($data['lehetosegek']) === '') {
        $errors['lehetosegek'] = 'Legalább két lehetőség megadása kötelező.';
    } else if (count(explode("\r\n", trim($data['lehetosegek']))) < 2) {
        $errors['lehetosegek'] = 'Legalább két lehetőség megadása kötelező.';
    }

    if ($data['hatarido'] === '') {
        $errors['hatarido'] = 'A leadási határidő megadása kötelező.';
    } else if (strtotime($data['hatarido']) === false) {
        $errors['hatarido'] = 'A érvénytelen dátum';
    } else if (date('Y-m-d') > $data['hatarido']) {
        $errors['hatarido'] = 'A leadási határidő nem lehet korábbi dátum.';
    }
    return count($errors) === 0;
}

$id = $_GET['id'];
$poll = $polls_storage->findById($id);
$data = [];
$errors = [];
$passed = false;
if (count($_POST) > 0) {
    if (validate($_POST, $data, $errors)) {
        $passed = true;
        if ($polls_storage->updatePoll($id, $data)) $users_storage->deletePollIdFromVoted($id);
    }
}
?>

<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szavazazás módosítása</title>
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
            <h1 style="color: green">Sikeres módosítás</h1>
            <a href="index.php">Vissza a főoldalra</a>
        </div>

    <?php else : ?>

        <h1>Szavazás módosítása</h1>
        <form action="editPoll.php?id=<?= $id ?>" method="post" class="addPollForm" novalidate>
            <label for="szovegezes">Szavazás szövegezése</label><br>
            <span><?= $errors['szovegezes'] ?? '' ?></span>
            <input type="text" name="szovegezes" value="<?= $_POST['szovegezes'] ?? $poll['question'] ?? '' ?>"><br><br>

            <label for="lehetosegek">Választási lehetőségek</label><br>
            <?php
            if (isset($_POST['lehetosegek'])) :
            ?>
                <span><?= $errors['lehetosegek'] ?? '' ?></span>
                <textarea rows="4" name="lehetosegek"><?= $_POST['lehetosegek'] ?></textarea><br><br>

            <?php
            elseif ($poll['options'] !== NULL) :
            ?>
                <span><?= $errors['lehetosegek'] ?? '' ?></span>
                <textarea rows="4" name="lehetosegek"><?= implode('&#13;&#10;', $poll['options']); ?></textarea><br><br>

            <?php
            else :
            ?>
                <span><?= $errors['lehetosegek'] ?? '' ?></span>
                <textarea rows="4" name="lehetosegek"></textarea><br><br>
            <?php
            endif;
            ?>

            <input type="checkbox" name="tobbLehetoseg" <?= isset($_POST['tobbLehetoseg']) ? 'checked' : ($poll['isMultiple'] ? 'checked' : '') ?>>
            <label for="tobbLehetoseg">Megengedett több lehetőség</label><br><br>

            <label for="hatarido">Leadási határidő</label><br>
            <span><?= $errors['hatarido'] ?? '' ?></span>
            <input type="date" name="hatarido" value="<?= $_POST['hatarido'] ?? $poll['deadline'] ?? '' ?>"><br><br>

            <input type="submit" value="Módosítás" class="button-vote" style="float: left;">
            <a href="index.php" class="button-delete" style="float: right;">Mégsem</a>
        </form>

    <?php endif; ?>
</body>

</html>