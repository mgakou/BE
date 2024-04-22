<?php
session_start();

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: connexion.html");
    exit;
}

// Récupérez l'ID du projet depuis l'URL
$idProjet = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($idProjet <= 0) {
    header("Location: accueil.html");
    exit;
}

// Paramètres de connexion à la base de données
$host = "localhost";
$dbname = "BE";
$username = "postgres";
$password = "Niktwo.3111";

try {
  
    $connexion = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);    
    $stmt = $connexion->prepare("DELETE  FROM Infrastructure WHERE id_infrastructure = :idProjet");
    $stmt->bindParam(':idProjet', $idProjet, PDO::PARAM_INT);
    $stmt->execute();
    $projet = $stmt->fetch(PDO::FETCH_ASSOC);

    
    } catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
header("Location: ./accueil.php");
?>