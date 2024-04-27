<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: connexion.html");
    exit;
}

// Récupérer l'ID du réseau depuis l'URL
$idReseau = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($idReseau <= 0) {
    header("Location: accueil.html");
    exit;
}

// Paramètres de connexion à la base de données
require_once('connecter_bd.php');

try {
    // Connexion à la base de données avec gestion des erreurs
    $connexion = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Commencer une transaction pour gérer la suppression en cascade
    $connexion->beginTransaction();

    // 1. Supprimer les connexions PCs-Réseau (si existantes)
    $sql = "DELETE FROM connecter_pc WHERE id_pc IN (
                SELECT id_pc FROM Pc WHERE id_sousréseau IN (
                    SELECT id_sousréseau FROM sous_réseau WHERE id_reseau = :idReseau
                )
            )";
    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':idReseau', $idReseau, PDO::PARAM_INT);
    $stmt->execute();

    // 2. Supprimer les PCs liés à ce réseau
    $sql = "DELETE FROM Pc WHERE id_sousréseau IN (
                SELECT id_sousréseau FROM sous_réseau WHERE id_reseau = :idReseau
            )";
    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':idReseau', $idReseau, PDO::PARAM_INT);
    $stmt->execute();

    // 3. Supprimer les sous-réseaux liés à ce réseau
    $sql = "DELETE FROM sous_réseau WHERE id_reseau = :idReseau";
    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':idReseau', $idReseau, PDO::PARAM_INT);
    $stmt->execute();

    // 4. Finalement, supprimer le réseau
    $sql = "DELETE FROM réseau WHERE id_reseau = :idReseau";
    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':idReseau', $idReseau, PDO::PARAM_INT);
    $stmt->execute();

    // Valider la transaction
    $connexion->commit();
    
    header("Location: accueil.php"); // Redirection vers la page d'accueil après la suppression complète
} catch (PDOException $e) {
    $connexion->rollBack(); // Annuler la transaction en cas d'erreur
    die("Erreur lors de la suppression : " . $e->getMessage());
}
?>
