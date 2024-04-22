<?php
session_start();

// Connexion à la base de données
$host = "localhost";
$dbname = "BE";
$username = "postgres";
$password = "Niktwo.3111";


if (!isset($_SESSION['utilisateur'])) {
    header("Location: ../Html/connexion.html");
    exit;
}

header("Location: ../Html/accueil.html");

session_start();



?>
