<?php
session_start();
include('storage.php');
if(!isset($_SESSION['userId']) || !$users_storage->findById($_SESSION['userId'])['isAdmin']) {
    header('Location: index.php');
    exit();
}

function validate($post, &$data, &$errors)
{
    $data = $post;
    if(trim($data['szovegezes']) === '') {
        $errors['szovegezes'] = 'A szavazás szövegének megadása kötelező.';
    }

    if(trim($data['lehetosegek']) === '') {
        $errors['lehetosegek'] = 'Legalább két lehetőség megadása kötelező.';
    } else if(count(explode("\r\n", trim($data['lehetosegek'])))<2) {
        $errors['lehetosegek'] = 'Legalább két lehetőség megadása kötelező.';
    }

    if($data['hatarido'] === '') {
        $errors['hatarido'] = 'A leadási határidő megadása kötelező.';
    } else if(strtotime($data['hatarido']) === false) {
        $errors['hatarido'] = 'A érvénytelen dátum';
    } else if(date('Y-m-d')>$data['hatarido']) {
        $errors['hatarido'] = 'A leadási határidő nem lehet korábbi dátum.';
    }
    return count($errors) === 0;
}

$data = [];
$errors = [];
$passed = false;

if(count($_POST)>0) {
    if(validate($_POST,$data,$errors)) {
        $passed = true;
        $polls_storage->addNewPoll($data);
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Új szavazás hozzáadása</title>
    <link rel="stylesheet" href="index.css">
    <style>
        span {
            color: red;
        }
    </style>
</head>
<body>
    <?php if($passed) : ?>
        <div style="text-align: center;">
            <h1 style="color: green">Sikeres létrehozás</h1>
            <a href="index.php">Vissza a főoldalra</a>
        </div>

    <?php else : ?>

    <h1>Új szavazás</h1>
    <form action="addPoll.php" method="post" class="addPollForm" novalidate>
        <label for="szovegezes">Szavazás szövegezése</label><br>
        <span><?= $errors['szovegezes'] ?? '' ?></span>
        <input type="text" name="szovegezes" value="<?= $_POST['szovegezes'] ?? '' ?>"><br><br>

        <label for="lehetosegek">Választási lehetőségek</label><br>
        <span><?= $errors['lehetosegek'] ?? '' ?></span>
        <textarea rows="5" cols="38" name="lehetosegek"><?= $_POST['lehetosegek'] ?? '' ?></textarea><br>

        <input type="checkbox" name="tobbLehetoseg" <?= isset($data['tobbLehetoseg']) ? 'checked' : '' ?>>
        <label for="tobbLehetoseg">Megengedett több lehetőség</label><br><br>

        <label for="hatarido">Leadási határidő</label><br>
        <span><?= $errors['hatarido'] ?? '' ?></span>
        <input type="date" name="hatarido" value="<?= $_POST['hatarido'] ?? '' ?>"><br><br>

        <input type="submit" value="Hozzáadás" class="button-vote" style="float: left;">
        <a href="index.php" class="button-delete" style="float: right;">Mégse</a>
    </form>

    <?php endif; ?>

</body>
</html>