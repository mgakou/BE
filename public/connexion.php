<?php
session_start();

// Connexion à la base de données
$host = "localhost";
$dbname = "BE"; // Assurez-vous que le nom de la base de données est correct
$username = "postgres"; // Votre nom d'utilisateur pour la base de données
$password = "VOTRE MDP"; // Votre mot de passe de base de données

try {
    $connexion = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération et vérification des données POST
    $pseudo = $_POST['pseudo'] ?? '';
    $motDePasseSaisi = $_POST['password'] ?? '';

    if (empty($pseudo) || empty($motDePasseSaisi)) {
        // Si les champs sont vides, redirigez vers la page de connexion
        header("Location: connexion.html");
        exit;
    }

    // Recherche de l'utilisateur dans la base de données
    $requete = "SELECT pseudo, mot_de_passe FROM utilisateur WHERE pseudo = ?";
    $stmt = $connexion->prepare($requete);
    $stmt->execute([$pseudo]);
    $utilisateur = $stmt->fetch();

    if ($utilisateur && password_verify($motDePasseSaisi, $utilisateur['mot_de_passe'])) {
        // Si la vérification est réussie, enregistrez l'utilisateur dans la session
        $_SESSION['utilisateur'] = $utilisateur['pseudo'];
        
        // Redirection vers la page d'accueil
        header("Location: accueil.html");
        exit;
    } else {
        // Si les identifiants sont incorrects, redirigez à nouveau vers la page de connexion
        header("Location: connexion.html");
        exit;
    }
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
