<?php
session_start();


require_once('connecter_bd.php');


if (!isset($_SESSION['utilisateur'])) {
    header("Location: ../Html/connexion.html");
    exit;
}

header("Location: ../Html/accueil.html");





?>
