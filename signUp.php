<?php
session_start();
include('storage.php');

function validate($post,&$data,&$errors) {
    $data = $post;
    global $users_storage;
    if(trim($data['felhasznalonev']) === '') {
        $errors['felhasznalonev'] = 'A felhasználónév megadása kötelező.';
    } else if(strlen(trim($data['felhasznalonev'])) <= 3) {
        $errors['felhasznalonev'] = 'A felhasználónévnek legalább négy karakter hosszúnak kell lennie.';
    } else if(!$users_storage->isNewUserName($data['felhasznalonev'])) {
        $errors['felhasznalonev'] = "Ez a felhasználónév már foglalt.";
    }

    if(trim($data['email']) === '') {
        $errors['email'] = 'Az email megadása kötelező.';
    }  else if(!filter_var($data['email'],FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Érvénytelen email.";
    } else if(!$users_storage->isNewEmail($data['email'])) {
        $errors['email'] = "Ez az email már foglalt.";
    }

    if(trim($data['password1']) === '') {
        $errors['password1'] = "A jelszó megadása kötelező.";
    } else if(strlen(trim($data['password1'])) <= 4) {
        $errors['password1'] = 'A jelszónak legalább öt karakter hosszúnak kell lennie.';
    } else if(!$users_storage->isNewPassword($data['password1'])) {
        $errors['password1'] = "Ez a jelszó már foglalt.";
    }

    if(trim($data['password2']) === '') {
        $errors['password2'] = "Add meg újra a jelszót.";
    } else if($data['password1'] !== $data['password2']) {
        $errors['password2'] = 'A jelszavak nem egyeznek.';
    }

    return count($errors) === 0;
}

$data = [];
$errors = [];
$passed = false;
if(count($_POST) > 0) {
    if(validate($_POST,$data,$errors,$users_storage)) {
        $passed = true;
        $users_storage->add([
            'username' => $data['felhasznalonev'],
            'email' => $data['email'],
            'password' => password_hash($data['password1'],PASSWORD_DEFAULT),
            'isAdmin' => false,
            'votes' => []
        ]);
        header('Location: login.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regisztráció</title>
    <link rel="stylesheet" href="index.css">
    <style>
        span {
            color: red;
        }
    </style>
</head>

<body>
    <h1>Regisztráció</h1>

    <?php if($passed) : ?> 
        <div style="text-align: center;">
            <h1 style="color: green">Sikeres regisztráció</h1>
            <a href="index.php">Vissza a főoldalra</a>
        </div>
    <?php else : ?>

    <form class="loginForm" action="signUp.php" method="post" novalidate>
        <label for="felhasznalonev">Felhasználónév</label><br>
        <span><?= $errors['felhasznalonev'] ?? ''?></span>
        <input type="text" name="felhasznalonev" value="<?= $_POST['felhasznalonev'] ?? '' ?>"><br><br>

        <label for="email">E-mail</label><br>
        <span><?= $errors['email'] ?? ''?></span>
        <input type="email" name="email" value="<?= $_POST['email'] ?? '' ?>"><br><br>

        <label for="password1">Jelszó</label><br>
        <span><?= $errors['password1'] ?? ''?></span>
        <input type="password" name="password1" value="<?= $_POST['password1'] ?? '' ?>"><br><br>

        <label for="password2">Jelszó újra</label><br>
        <span><?= $errors['password2'] ?? ''?></span>
        <input type="password" name="password2" value="<?= $_POST['password2'] ?? '' ?>"><br><br>

        <input type="submit" value="Regisztrálás" class="button-vote">
        <a href="index.php" style="float: right;">Vissza a főoldalra</a>
    </form>

    <?php endif; ?>
</body>

</html>