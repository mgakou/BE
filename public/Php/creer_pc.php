<?php
session_start();

// Vérification des prérequis de session
if (!isset($_SESSION['id_utilisateur']) || !isset($_SESSION['ipsousreseau'])) {
    header("Location: connexion.html");
    exit;
}

$idSousReseau = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($idSousReseau <= 0) {
    header("Location: accueil.html");
    exit;
}

// Paramètres de la base de données
$host = 'localhost';
$dbname = 'BE';
$username = 'postgres';
$password = 'Niktwo.3111';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $stmt = $pdo->prepare("SELECT * FROM sous_réseau WHERE id_sousréseau = :idSousReseau");
    $stmt->bindParam(':idSousReseau', $idSousReseau, PDO::PARAM_INT);
    $stmt->execute();
    $sousReseau = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sousReseau) {
        echo "Sous-réseau non trouvé.";
        exit;
    }
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ip_pc = $_POST['ip_pc'];
    // Enregistrement d'un nouveau PC dans le sous-réseau
    $insertStmt = $pdo->prepare("INSERT INTO Pc (IP_Pc, id_sousréseau) VALUES (?, ?)");
    if ($insertStmt->execute([$ip_pc, $idSousReseau])) {
        // je voudrais un message de réussite affiché pendant 3 secondes

        $message = "PC ajouté avec succès.";
        echo "<script>alert('Pc ajouté'); window.location.href = './sous_reseau.php?id=$idSousReseau';</script>";
       



       

    } else {
        $message = "Erreur lors de l'ajout du PC: " . $insertStmt->errorInfo()[2];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un PC</title>
    <link rel="stylesheet" href="../Css/pc.css">
</head>
<body>
    <div class="banniere">
        <img src="../Image/logo.jpeg" alt="logo" class="logo">
        <p>Net-Simulate</p>
    </div>

    <hr>

    <div class="titre">
        
        <h2>Ajouter un PC au Sous-Réseau : <?php echo htmlspecialchars($_SESSION['ipsousreseau']); ?> </h2>
        <h2> De l'infrastructure :<?php echo htmlspecialchars($_SESSION['idProjet']); ?></h2>
        <h2> Du réseau :<?php echo htmlspecialchars($_SESSION['idReseau']); ?></h2>
        
    </div>

    <div class="form-container">
        <form method="post" action="">
            <div class="form-group">
                <label for="ip_pc">IP du PC :</label>
                <input type="text" id="ip_pc" name="ip_pc" required><br><br>
            </div>


            <div class="form-group">
                <button type="submit" class="submit-button">Ajouter PC</button>
                <a href="../PHP/accueil.php" type="button" class="cancel-button">Annuler</a>
            </div>   
        </form>
        
        <!-- Affichage des messages d'erreur ou de succès -->
        <?php if (!empty($message)): ?>
            <div class="form-message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
