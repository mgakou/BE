<?php
session_start();
require_once 'fonction_adresse.php';


if (!isset($_SESSION['id_utilisateur']) || !isset($_SESSION['ipsousreseau'])) {
    header("Location: connexion.html");
    exit;
}

$idSousReseau = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($idSousReseau <= 0) {
    header("Location: accueil.html");
    exit;
}


require_once('connecter_bd.php');
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

$adressesousreseau = "SELECT ip_sous_reseau FROM sous_réseau WHERE id_sousréseau := id_sousreseau";
$mask = "SELECT mask FROM sous_réseau WHERE id_sousréseau := id_sousreseau";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ip_pc = $_POST['ip_pc'];

    if (AdresseIPValidePC($ip_pc, $sousReseau['ip_sous_reseau'], $sousReseau['mask'])) {
        $insertStmt = $pdo->prepare("INSERT INTO Pc (IP_Pc, id_sousréseau) VALUES (?, ?)");
        if ($insertStmt->execute([$ip_pc, $idSousReseau])) {
            echo "<script>alert('PC ajouté .'); window.location.href = './sous_reseau.php?id=$idSousReseau';</script>";
        } else {
            echo "<script>alert('Erreur ajout PC .'); window.location.href = './sous_reseau.php?id=$idSousReseau';</script>";
        }
    } else {
        echo "<script>alert('Adresse PC non valide .'); window.location.href = './sous_reseau.php?id=$idSousReseau';</script>";
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
                <button class="button" onclick="retourAuSousReseau()">Annuler</button>
            </div>   
        </form>
    </div>
    
    <script>
    function retourAuSousReseau() {
        window.location.href = 'sous_reseau.php?id=<?php echo $idSousReseau; ?>';
    }
    </script>
        
    </div>
</body>
</html>
