<?php
session_start();

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: connexion.html");
    exit;
}

// Paramètres de la base de données
$host = 'localhost';
$dbname = 'BE';
$username = 'postgres';
$password = 'Niktwo.3111';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Récupérer tous les routeurs pour les menus déroulants
    $stmtRouteurs = $pdo->query("SELECT id_routeur, IP_Routeur FROM Routeur");
    $routeurs = $stmtRouteurs->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $idRouteur1 = $_POST['id_routeur1'];
        $idRouteur2 = $_POST['id_routeur2'];
        $interfaceRouteur = $_POST['interface_routeur'];

        // Insertion du nouveau lien de routeur
        $insertStmt = $pdo->prepare("INSERT INTO connecter_routeur (id_routeur, id_routeur_1, interface_routeur) VALUES (?, ?, ?)");
        if ($insertStmt->execute([$idRouteur1, $idRouteur2, $interfaceRouteur])) {
            echo "<script>alert('Lien entre routeurs créé avec succès!')</script>";
        } else {
            echo "Échec de la création du lien: ";
        }
    }
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer un lien entre Routeurs</title>
    <link rel="stylesheet" href="../Css/creer_lien_c_c.css">
</head>
<body>
    <div class="banniere">
        <img src="../Image/logo.jpeg" alt="logo" class="logo">
        <p>Net-Simulate</p>
    </div>

    <hr>

    <form method="post">
        <div class="form-group">
        <div class="titre">
        
            <h2>Connecter deux routeurs</h2>
      
        
        </div>
        
            <label for="id_routeur1">Routeur 1 :</label>
            <select id="id_routeur1" name="id_routeur1" required>
                <?php foreach ($routeurs as $routeur) {
                    echo "<option value='{$routeur['id_routeur']}'>{$routeur['IP_Routeur']}</option>";
                } ?>
            </select>
        </div>

        <div class="form-container">
            <label for="id_routeur2">Routeur 2 :</label>
            <select id="id_routeur2" name="id_routeur2" required>
                <?php foreach ($routeurs as $routeur) {
                    echo "<option value='{$routeur['id_routeur']}'>{$routeur['IP_Routeur']}</option>";
                } ?>
            </select>
        </div>

        <div class="form-group">
            <label for="interface_routeur">Interface :</label>
            <input type="text" id="interface_routeur" name="interface_routeur" required>
        </div>

        <button type="submit" class="submit-button">Créer Lien</button>
    </form>
</body>
</html>
