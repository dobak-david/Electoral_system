<?php
session_start();
include('storage.php');
if (isset($_SESSION['userId'])) {
    $users_storage->logout();
} else {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kijelentkezés</title>
    <link rel="stylesheet" href="index.css">
</head>

<body>

    <div style="text-align: center;">
        <h1 style="color: green">Sikeres kijelentkezés</h1>
        <a href="index.php">Vissza a főoldalra</a>
    </div>
</body>

</html>