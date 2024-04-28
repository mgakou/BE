<?php
session_start();

/* Connexion à la base de données */
require_once('connecter_bd.php');

try {
    /* Utilisation de PDO pour se connecter à la base de données */
    /* Creation d'une chaine de connexion et utilisation de PDO avec cette chaine pour se connecter à la base de données */
    $connexion = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    /* Vérification de l'entrée utilisateur des champs de connexion */
    $pseudo = $_POST['pseudo'] ?? '';
    $motDePasseSaisi = $_POST['password'] ?? '';

    /* Vérification des champs de connexion */
    if (empty($pseudo) || empty($motDePasseSaisi)) {
        $error = urlencode("Veuillez remplir tous les champs.");
        header("Location: ../Html/connexion.html?erreur=$error");
        exit;
    }

    /* Recherfche de l'utilisateur dans la base de données */
    $requete = "SELECT id_utilisateur, pseudo, mot_de_passe FROM utilisateur WHERE pseudo = ?";
    $stmt = $connexion->prepare($requete);
    $stmt->execute([$pseudo]);
    $utilisateur = $stmt->fetch();

    /* Condition de vérification de l'utilisateur */
    if ($utilisateur && password_verify($motDePasseSaisi, $utilisateur['mot_de_passe'])) {
        // Si la vérification est réussie, enregistrez l'utilisateur dans la session
        $_SESSION['utilisateur'] = $utilisateur['pseudo'];
        $_SESSION['id_utilisateur'] = $utilisateur['id_utilisateur'];  // Assurez-vous que ce champ est correctement nommé et récupéré
        header("Location: ./accueil.php");
        exit;
    } else {
        $error = urlencode("Identifiant ou mot de passe incorrect.");
        header("Location: ../Html/connexion.html?erreur=$error");
        exit;
    }
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
