<?php
session_start();

/* Connexion à la base de donées avec renvoie sur le fichier connecter_bd.php */
require_once('connecter_bd.php');

/* Vérification de la validité de la session, et si pas valide alors (identification d'aucune connexion viable), renvoie sur la page de connexion */
if (!isset($_SESSION['utilisateur'])) {
    header("Location: ../Html/connexion.html");
    exit;
}

/* Renvoie sur la page d'accueil si l'utilisateur est connecté */
header("Location: ../Html/accueil.html");


?>
