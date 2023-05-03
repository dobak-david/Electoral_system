<?php
session_start();
include('storage.php');
function validate($post, &$data, &$errors)
{
    $data = $post;

    if (trim($data['felhasznalonev']) === '') {
        $errors['felhasznalonev'] = 'Add meg a felhasználóneved!';
    }

    if (trim($data['password']) === '') {
        $errors['password'] = 'Add meg a jelszavad!';
    }

    return count($errors) === 0;
}

$data;
$errors = [];
$passed = false;
$inValidLogin = false;
if (count($_POST) > 0) {
    if (validate($_POST, $data, $errors)) {
        if ($users_storage->validLogin($data['felhasznalonev'], $data['password'])) {
            $passed = true;
            $_SESSION['userId'] = $users_storage->findOne(["username" => $data['felhasznalonev']])['id'];
            header('Location: index.php');
            exit();
        } else {
            $inValidLogin = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bejelentkezés</title>
    <link rel="stylesheet" href="index.css">
    <style>
        span {
            color: red;
        }
    </style>
</head>

<body>
    <h1>Bejelentkezés</h1>

    <?php if ($inValidLogin) : ?>
        <div style="text-align: center;">
            <h1 style="color: red">Hibás felhasználónév vagy jelszó</h1>
        </div>
    <?php endif; ?>

    <form class="loginForm" action="login.php" method="post" novalidate>
        <label for="felhasznalonev">Felhasználónév</label><br>
        <input type="text" name="felhasznalonev"><br><br>

        <label for="password">Jelszó</label><br>
        <input type="password" name="password"><br><br>

        <input type="submit" value="Bejelentkezés" class="button" style="border: none">
        <a href="signUp.php" class="button-vote" style="float: right">Regisztráció</a>
    </form>

    <div style="text-align: center; margin-top: 2%">
        <a href="index.php">Vissza a főoldalra</a>
    </div>
    <div style="margin-left: 40%;color: red;">
        <br>
        <?php foreach (array_values($errors) as $error) : ?>
            <li style="margin-bottom: 0px;"><?= $error ?></li>
        <?php endforeach ?>
    </div>

</body>

</html>