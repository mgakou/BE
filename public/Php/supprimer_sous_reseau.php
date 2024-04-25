<?php
session_start();

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: connexion.html");
    exit;
}

// Récupérez l'ID du sous-réseau depuis l'URL
$idSousReseau = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($idSousReseau <= 0) {
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

    // Préparer et exécuter la requête pour supprimer le sous-réseau
    $stmt = $connexion->prepare("DELETE FROM sous_réseau WHERE id_sousréseau = :idSousReseau");
    $stmt->bindParam(':idSousReseau', $idSousReseau, PDO::PARAM_INT);
    $stmt->execute();

    // Vérifier si la suppression a été effectuée
    if ($stmt->rowCount() > 0) {
        $_SESSION['message'] = "Sous-réseau supprimé avec succès.";
    } else {
        $_SESSION['erreur'] = "Erreur lors de la suppression du sous-réseau.";
    }
} catch (PDOException $e) {
    $_SESSION['erreur'] = "Erreur de connexion à la base de données : " . $e->getMessage();
    header("Location: erreur.php"); // Assurez-vous que cette page existe pour gérer les erreurs.
    exit;
}

$idReseau = $_SESSION['idReseau']; // Assurez-vous que cette session contient la bonne valeur
// Redirection vers la page du réseau après la suppression
header("Location: reseau.php?id=$idReseau");
exit;
?>
