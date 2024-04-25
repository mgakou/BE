<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: connexion.html");
    exit;
}

// Récupérer l'ID de l'infrastructure (projet) depuis l'URL
$idInfra = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($idInfra <= 0) {
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
    
    // Commencer une transaction pour gérer la suppression en cascade
    $connexion->beginTransaction();
    
    // 1. Supprimer tous les PCs dans chaque sous-réseau de chaque réseau de l'infrastructure
    $sql = "DELETE FROM Pc WHERE id_sousréseau IN (
                SELECT id_sousréseau FROM sous_réseau WHERE id_reseau IN (
                    SELECT id_reseau FROM réseau WHERE id_infrastructure = :idInfra
                )
            )";
    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':idInfra', $idInfra, PDO::PARAM_INT);
    $stmt->execute();

    // 2. Supprimer tous les sous-réseaux dans chaque réseau de l'infrastructure
    $sql = "DELETE FROM sous_réseau WHERE id_reseau IN (
                SELECT id_reseau FROM réseau WHERE id_infrastructure = :idInfra
            )";
    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':idInfra', $idInfra, PDO::PARAM_INT);
    $stmt->execute();

    // 3. Supprimer tous les réseaux dans l'infrastructure
    $sql = "DELETE FROM réseau WHERE id_infrastructure = :idInfra";
    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':idInfra', $idInfra, PDO::PARAM_INT);
    $stmt->execute();

    // 4. Finalement, supprimer l'infrastructure (projet)
    $sql = "DELETE FROM Infrastructure WHERE id_infrastructure = :idInfra";
    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':idInfra', $idInfra, PDO::PARAM_INT);
    $stmt->execute();

    // Valider la transaction
    $connexion->commit();
    
    header("Location: accueil.php"); // Redirection vers la page d'accueil après la suppression complète
} catch (PDOException $e) {
    $connexion->rollBack(); // Annuler la transaction en cas d'erreur
    die("Erreur lors de la suppression : " . $e->getMessage());
}
?>
