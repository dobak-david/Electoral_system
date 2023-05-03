<?php
session_start();
include('storage.php');

//be van-e jelentkezve, admin-e?
$loggedIn = isset($_SESSION['userId']);
$isAdmin = false;

if ($loggedIn) {
    $id = $_SESSION['userId'];
    $isAdmin = $users_storage->findById($id)['isAdmin'];
    $userName = $users_storage->getUserName($id);
    $email = $users_storage->getEmail($id);
}

//szavazatok betoltese
$sortedActivePolls;
$sortedExpiredPolls;
$polls_storage->getActivePolls($sortedActivePolls);
$polls_storage->getExpiredPolls($sortedExpiredPolls);
?>

<!DOCTYPE html>
<html lang="hu">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Főoldal</title>
    <link rel="stylesheet" href="index.css">
</head>

<body>
    <div class="actions">
        <ul>
            <?php if (!$loggedIn) : ?>
                <li><a class="button" href="login.php"><img src="img\right-to-bracket-solid.svg"> Bejelentkezés </a></li>
                <li><a class="button" href="signUp.php"><img src="img\right-to-bracket-solid.svg"> Regisztráció </a></li>
                <p>Jelentkezz be, hogy tudj szavazani.</p>
            <?php else : ?>
                <?php if ($isAdmin) : ?>
                    <li><a class="button" href="addPoll.php"><img src="img\plus-solid.svg"> Új szavazás hozzáadása</a></li>
                <?php endif; ?>
                <li><a class="button" href="logout.php"><img src="img\right-from-bracket-solid.svg"> Kijelentkezés</a></li>
                </ul>
                <p>Sikeresen bejelentkeztél. Most már tudsz szavazni.</p>
                <h3>Felhasználó adatai: </h3>
                <div>
                    <div>Felhasználó név: <b><?= $userName ?></b></div>
                    <div>E-mail: <b><?= $email ?></b></div>
                </div>
                <p>Jelenleg <b><?= count($sortedActivePolls) ?></b> aktív szavazás van. </p>
            <?php endif; ?>
    </div>

    <div class="main">
        <div class="intro">
            <h1>Szavazórendszer</h1>
            <p>
                Ezen az oldalon tudsz szavazni az egyetemet érintő kérdésekre. Az oldal fenti része tartalmazza a még aktív szavazólapokat. Az oldal alsó részén a már lejárt szavazásokat látod, ezekre már nem tudsz szavazni.
                Ahhoz, hogy szavazni tudj, regisztrálnod kell az oldalon. A szavazataidat a határidő lejártáig bárhányszor tudod módosítani.
                Minden szavazásra a szavazatodat a határidő napján még éjfélig le tudod adni.
            </p>

            <a href="#expiredPolls">Ugrás a lejárt szavazásokhoz</a>

            <?php if (count($sortedActivePolls) === 0) : ?>
                <h2>Nincsenek aktív szavazások</h2>
            <?php else : ?>
                <h2>Aktív szavazások</h2>
            <?php endif; ?>
        </div>

        <div class="card-container">
            <?php $i = 0;
            foreach ($sortedActivePolls as $poll) : $i++; ?>
                <a class="card-href" href="<?= $loggedIn ? 'vote.php?id=' . $poll['id'] : 'login.php' ?>">
                    <div class="card">
                        <div class="container">
                            <div><?= $i ?>. szavazás</div>
                            <h2><?= $poll['question'] ?></h2>
                            <div>Létrehozás dátuma: <b><?= $poll['createdAt'] ?></b></div>
                            <h3>Leadási határidő: <?= $poll['deadline'] ?></h3>
                            <a class="button-vote" href="<?= $loggedIn ? 'vote.php?id=' . $poll['id'] : 'login.php' ?>"><?= $loggedIn && $polls_storage->hasUserVoted($poll['id'], $id) ? 'Szavazat frissítése' : 'Szavazás' ?></a>
                            <?php if ($isAdmin) : ?>
                                <br><br>
                                <a class="button-delete" href="deletePoll.php?id=<?= $poll['id'] ?>">Szavazás törlése</a>
                                <br><br>
                                <a class="button-edit" href="editPoll.php?id=<?= $poll['id'] ?>">Szavazás módosítása</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <hr>

        <?php if (count($sortedExpiredPolls) === 0) : ?>
            <h2 style="margin-left: 15%;">Nincsenek lejárt szavazások</h2>
        <?php else : ?>
            <h2 style="margin-left: 15%;">Lejárt szavazások</h2>
        <?php endif; ?>

        <div id="expiredPolls">
            <?php
            foreach ($sortedExpiredPolls as $poll) : $i++; ?>
                <div class="card expired">
                    <div class="container">
                        <div><?= $i ?>. szavazás</div>
                        <div><b>Létrehozás dátuma: <?= $poll['createdAt'] ?></b></div>
                        <h3>Leadási határidő: <?= $poll['deadline'] ?></h3>
                        <div><b><?= $poll['question'] ?></b></div>
                        <?php foreach ($poll['options'] as $opt) : ?>
                            <div><?= $opt ?> : <?= $poll['answers'][$opt] ?></div>
                        <?php endforeach; ?>
                        <h3>Válaszadók száma: <?= count($poll['voted']) ?></h3>
                        <?php if ($isAdmin) : ?>
                            <a class="button-delete" href="deletePoll.php?id=<?= $poll['id'] ?>">Szavazás törlése</a>
                            <br><br>
                            <a class="button-edit" href="editPoll.php?id=<?= $poll['id'] ?>">Szavazás módosítása</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>