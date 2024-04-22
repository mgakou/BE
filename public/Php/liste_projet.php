<?php
session_start();

if (!isset($_SESSION['id_utilisateur'])) {
    echo "Vous devez être connecté pour voir cette page.";
    exit;
}

$host = "localhost";
$dbname = "BE";
$username = "postgres";
$password = "Niktwo.3111";

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->prepare("SELECT id_infrastructure, nom FROM Infrastructure WHERE id_utilisateur = ?");
    $stmt->execute([$_SESSION['id_utilisateur']]);
    $infrastructures = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($infrastructures) > 0) {
        foreach ($infrastructures as $infrastructure) {
            echo '<a href="../Php/projet.php?id=' . $infrastructure['id_infrastructure'] . '">' . htmlspecialchars($infrastructure['nom']) . '</a><br>';
        }
    } else {
        echo "Aucun projet trouvé.";
    }

} catch (PDOException $e) {
    echo "Erreur de connexion à la base de données : " . $e->getMessage();
}
?>
