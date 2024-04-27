<?php
session_start();

if (!isset($_SESSION['id_utilisateur'])) {
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
$_SESSION['ipsousreseau'] = $sousReseau['ip_sous_reseau'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion du Sous-Réseau: <?php echo htmlspecialchars($sousReseau['mask']); ?></title>
    <link rel="stylesheet" href="../Css/accueil.css">
    <script>
        function supprimerSousReseau() {
            if (confirm("Êtes-vous sûr de vouloir supprimer ce sous-réseau ?")) {
                window.location.href = 'supprimer_sous_reseau.php?id=<?php echo $idSousReseau; ?>';
            }
        }

        function visualiserSousReseau() {
            window.location.href = 'visualiser_sous_reseau.php?id=<?php echo $idSousReseau; ?>';
        }

        function ajouterPc() {
            window.location.href = 'creer_pc.php?id=<?php echo $idSousReseau; ?>';
        }

        function retourAuReseau() {
            window.location.href = 'reseau.php?id=<?php echo $sousReseau['id_reseau']; ?>';
        }
        
        function supprimerPc() {
            window.location.href = 'supprimer_pc.php?id=<?php echo $idSousReseau; ?>';
        }
        
    </script>
</head>
<body>
    <div class="banniere">
        <img src="../Image/logo.jpeg" alt="logo" class="logo">
        <p>Net-Simulate</p>
    </div>

    <hr>

    <div class="titre">
        <h1>Sous-Réseau :
    </div>

    <div class="button-container">
        <button class="button" onclick="supprimerSousReseau()">Supprimer Sous-Réseau</button>
        <button class="button" onclick="visualiserSousReseau()">Visualiser Sous-Réseau</button>
        <button class="button" onclick="ajouterPc()">Ajouter PC</button>
        <button class="button" onclick="supprimerPc()">Supprimer PC</button>
        <button class="button" onclick="retourAuReseau()">Retour au Réseau</button>
    </div>
    
</body>
</html>
