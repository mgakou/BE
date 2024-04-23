<?php
session_start();

if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: connexion.html");
    exit;
}

$userId = $_SESSION['id_utilisateur'];
$email = $_POST['pseudo'] ?? null; // Changé pour correspondre à l'input
$newPassword = $_POST['motdepasse'] ?? null; // Changé pour éviter le conflit

$host = "localhost";
$dbname = "BE";
$dbUser = "postgres"; // Changé pour éviter le conflit
$dbPassword = "Niktwo.3111"; // Changé pour éviter le conflit

try {
    $connexion = new PDO("pgsql:host=$host;dbname=$dbname", $dbUser, $dbPassword);
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!empty($email)) {
        $stmt = $connexion->prepare("UPDATE utilisateur SET pseudo = :email WHERE id_utilisateur = :userId");
        $stmt->execute([':email' => $email, ':userId' => $userId]);
    }

    if (!empty($newPassword)) {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $connexion->prepare("UPDATE utilisateur SET mot_de_passe = :password WHERE id_utilisateur = :userId");
        $stmt->execute([':password' => $passwordHash, ':userId' => $userId]);
    }

    header("Location: accueil.php");
    exit;
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>