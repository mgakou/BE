<?php
session_start();

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: connexion.html");
    exit;
}

// Récupérez l'ID du réseau depuis l'URL
$idReseau = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($idReseau <= 0) {
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

    
    $stmt = $connexion->prepare("SELECT id_sousréseau FROM sous_réseau WHERE id_reseau = :idReseau");
    // et supprimer tous les pc de ces sous réseaux
    $stmt->bindParam(':idReseau', $idReseau, PDO::PARAM_INT);
    $stmt->execute();
    $sousReseaux = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($sousReseaux as $sousReseau) {
        $idSousReseau = $sousReseau['id_sousréseau'];
        $stmt = $connexion->prepare("DELETE FROM Pc WHERE id_sousréseau = :idSousReseau");
        $stmt->bindParam(':idSousReseau', $idSousReseau, PDO::PARAM_INT);
        $stmt->execute();
    }
   
    $stmt = $connexion->prepare("DELETE FROM sous_réseau WHERE id_reseau = :idReseau");
    $stmt->bindParam(':idReseau', $idReseau, PDO::PARAM_INT);
    $stmt->execute();


    $stmt = $connexion->prepare("DELETE FROM réseau WHERE id_reseau = :idReseau");
    $stmt->bindParam(':idReseau', $idReseau, PDO::PARAM_INT);
    $stmt->execute();

    // Vérifier si la suppression a été effectuée
    if ($stmt->rowCount() > 0) {
        $_SESSION['message'] = "Réseau supprimé avec succès.";
    } else {
        $_SESSION['erreur'] = "Erreur lors de la suppression du réseau.";
    }
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
$idProjet = $_SESSION['idProjet'];
// Redirection vers la page d'accueil après la suppression
header("Location: projet.php?id=$idProjet");
exit;
?>
