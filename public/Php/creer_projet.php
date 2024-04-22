<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    echo "Vous devez être connecté pour voir cette page.";
    exit;
}

// Vérifier si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Vérifier si le champ nomProjet est défini et non vide
    if (isset($_POST["nomProjet"]) && !empty(trim($_POST["nomProjet"]))) {
        // Récupérer le nom du projet et l'ID de l'utilisateur depuis le formulaire
        $nomProjet = trim($_POST["nomProjet"]);
        $idUtilisateur = $_SESSION['id_utilisateur']; // Récupérer l'ID de l'utilisateur connecté

        // Paramètres de connexion à la base de données
        $host = "localhost";
        $dbname = "BE";
        $username = "postgres";
        $password = "Niktwo.3111";

        try {
            // Connexion à la base de données
            $connexion = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
            $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Préparer et exécuter la requête d'insertion
            $stmt = $connexion->prepare("INSERT INTO Infrastructure (nom, id_utilisateur) VALUES (:nomProjet, :idUtilisateur)");
            $stmt->bindParam(':nomProjet', $nomProjet);
            $stmt->bindParam(':idUtilisateur', $idUtilisateur);
            $stmt->execute();

            // Rediriger vers la page d'accueil ou une autre page après la création du projet
            header("Location: ./accueil.php");
            exit;
        } catch (PDOException $e) {
            // Gérer les erreurs de la base de données
            echo "Erreur de connexion : " . $e->getMessage();
        }
    } else {
        // Si le champ nomProjet n'est pas défini ou vide, rediriger vers la page d'accueil avec un message d'erreur
        $error = urlencode("Veuillez saisir un nom pour le projet.");
        header("Location: ../Html/accueil.html?erreur=$error");
        exit;
    }
}
?>
