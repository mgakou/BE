<?php

session_start();


if (!isset($_SESSION['utilisateur'])) {
    // Rediriger vers la page de connexion
    header("Location: ../Html/connexion.html");
    exit;
}


$_SESSION = array();

session_destroy();

header('Location: ../Html/connexion.html');
exit;

?>