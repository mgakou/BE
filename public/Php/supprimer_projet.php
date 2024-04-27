<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: connexion.html");
    exit;
}

// Récupérer l'ID de l'infrastructure depuis l'URL
$idInfra = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($idInfra <= 0) {
    header("Location: accueil.html");
    exit;
}

// Paramètres de connexion à la base de données
require_once('connecter_bd.php');

try {
    $connexion = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Commencer une transaction pour gérer la suppression en cascade
    $connexion->beginTransaction();

    // Supprimer les dépendances des PCs (Paquets, connexions, éléments)
    $sql = "DELETE FROM Paquet WHERE id_pc IN (
                SELECT id_pc FROM Pc WHERE id_sousréseau IN (
                    SELECT id_sousréseau FROM sous_réseau WHERE id_reseau IN (
                        SELECT id_reseau FROM réseau WHERE id_infrastructure = :idInfra
                    )
                )
            )";
    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':idInfra', $idInfra, PDO::PARAM_INT);
    $stmt->execute();

    $sql = "DELETE FROM connecter_pc WHERE id_pc IN (
                SELECT id_pc FROM Pc WHERE id_sousréseau IN (
                    SELECT id_sousréseau FROM sous_réseau WHERE id_reseau IN (
                        SELECT id_reseau FROM réseau WHERE id_infrastructure = :idInfra
                    )
                )
            )";
    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':idInfra', $idInfra, PDO::PARAM_INT);
    $stmt->execute();

    $sql = "DELETE FROM elem_pc WHERE id_pc IN (
                SELECT id_pc FROM Pc WHERE id_sousréseau IN (
                    SELECT id_sousréseau FROM sous_réseau WHERE id_reseau IN (
                        SELECT id_reseau FROM réseau WHERE id_infrastructure = :idInfra
                    )
                )
            )";
    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':idInfra', $idInfra, PDO::PARAM_INT);
    $stmt->execute();

    // Supprimer les PCs
    $sql = "DELETE FROM Pc WHERE id_sousréseau IN (
                SELECT id_sousréseau FROM sous_réseau WHERE id_reseau IN (
                    SELECT id_reseau FROM réseau WHERE id_infrastructure = :idInfra
                )
            )";
    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':idInfra', $idInfra, PDO::PARAM_INT);
    $stmt->execute();

    // Supprimer les sous-réseaux
    $sql = "DELETE FROM sous_réseau WHERE id_reseau IN (
                SELECT id_reseau FROM réseau WHERE id_infrastructure = :idInfra
            )";
    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':idInfra', $idInfra, PDO::PARAM_INT);
    $stmt->execute();

    // Supprimer les réseaux
    $sql = "DELETE FROM réseau WHERE id_infrastructure = :idInfra";
    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':idInfra', $idInfra, PDO::PARAM_INT);
    $stmt->execute();

    // Finalement, supprimer l'infrastructure
    $sql = "DELETE FROM Infrastructure WHERE id_infrastructure = :idInfra";
    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':idInfra', $idInfra, PDO::PARAM_INT);
    $stmt->execute();

    // Valider la transaction
    $connexion->commit();

    // Rediriger vers la page d'accueil
    header("Location: accueil.php");
    exit;
} catch (PDOException $e) {
    // En cas d'erreur, annuler la transaction
    $connexion->rollBack();
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
