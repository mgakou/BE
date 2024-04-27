<?php
session_start();

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: connexion.html");
    exit;
}

// Connexion à la base de données
require_once('connecter_bd.php');

try {
    $connexion = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Préparez et exécutez une requête pour obtenir les informations actuelles de l'utilisateur
    $requete = "SELECT pseudo FROM utilisateur WHERE id_utilisateur = ?";
    $stmt = $connexion->prepare($requete);
    $stmt->execute([$_SESSION['id_utilisateur']]);
    $resultat = $stmt->fetch();
    $pseudoActuel = $resultat['pseudo'];
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier informations- Net-Simulate</title>
    <link rel="stylesheet" href="../Css/modifier_informations.css">
</head>
<body>

    <div class="banniere">
        <img src="../Image/logo.jpeg" alt="logo" class="logo">
        <p>Net-Simulate</p>
    </div>

    <hr>

    <div class="titre">
        <p>Modifier informations</p>
    </div>

    <form action="../Php/traiter_modifications.php" method="post" class="form-container">
    <div class="informationsmail">
        <label for="pseudo">Adresse mail</label>
        <input type="email" id="pseudo" name="pseudo" placeholder="Nouveau Mail">
    </div>
    <div class="informationsmdp">
        <label for="motdepasse">Mot de passe</label>
        <input type="password" id="motdepasse" name="motdepasse" placeholder="Nouveau Mot De Passe">
    </div>
    <button type="submit" class="update-button">Mettre à jour</button>
    <a href="accueil.php" class="cancel-button">Annuler</a>
</form>



</body>
</html>

