<?php
session_start();
include('storage.php');
if (!isset($_SESSION['userId']) || !$users_storage->findById($_SESSION['userId'])['isAdmin'] || !isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id = $_GET['id'];
if ($polls_storage->findById($id) !== NULL) :
    $polls_storage->delete($id);
    $users_storage->deletePollIdFromVoted($id);
    $content = "<div style='text-align: center;'>
        <h1 style='color: green'>Sikeres törlés</h1>
    </div>";
else :
    $content = "<div style='text-align: center;'>
        <h1 style='color: red'>Nincsen ilyen azonosítójú kérdőív</h1>
    </div>";
endif;
?>

<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szavazás törlése</title>
    <link rel="stylesheet" href="index.css">
</head>

<body>
    <?= $content ?>
    <div style="text-align: center;">
        <a href="index.php" style="text-align: center;">Vissza a főoldalra</a>
    </div>
</body>

</html>