<?php

session_start();


if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: connexion.html");
    exit;
}


$idSousReseau = isset($_GET['id']) ? intval($_GET['id']) : 0;



require_once('connecter_bd.php');

try {
   
    $connexion = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    

    $connexion->beginTransaction();

  
    $sql = "DELETE FROM connecter_pc WHERE id_pc IN (
                SELECT id_pc FROM Pc WHERE id_sousréseau = :idSousReseau
            )";
    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':idSousReseau', $idSousReseau, PDO::PARAM_INT);
    $stmt->execute();


    $sql = "DELETE FROM Pc WHERE id_sousréseau = :idSousReseau";
    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':idSousReseau', $idSousReseau, PDO::PARAM_INT);
    $stmt->execute();


    $sql = "DELETE FROM sous_réseau WHERE id_sousréseau = :idSousReseau";
    $stmt = $connexion->prepare($sql);
    $stmt->bindParam(':idSousReseau', $idSousReseau, PDO::PARAM_INT);
    $stmt->execute();

    $connexion->commit();
    
    header("Location: accueil.php");

} catch (PDOException $e) {
    $connexion->rollBack(); 
    die("Erreur lors de la suppression : " . $e->getMessage());
}
?>
