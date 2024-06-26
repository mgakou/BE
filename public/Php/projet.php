<?php
session_start();

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: connexion.html");
    exit;
}

// Récupérez l'ID du projet depuis l'URL
$idProjet = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($idProjet <= 0) {
    header("Location: accueil.html");
    exit;
}

// Paramètres de connexion à la base de données
require_once('connecter_bd.php');

try {
    $connexion = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $connexion->prepare("SELECT * FROM Infrastructure WHERE id_infrastructure = :idProjet");
    $stmt->bindParam(':idProjet', $idProjet, PDO::PARAM_INT);
    $stmt->execute();
    $projet = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$projet) {
        echo "Projet non trouvé.";
        header("Location: accueil.php");
        exit;
    }

    $_SESSION['idProjet'] = $idProjet;

} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion du Projet: <?php echo htmlspecialchars($projet['nom']); ?></title>
    <link rel="stylesheet" href="../Css/accueil.css">
    <script>
        function supprimerProjet(idProjet) {
            if (confirm("Êtes-vous sûr de vouloir supprimer ce projet ?")) {
                window.location.href = 'supprimer_projet.php?id=' + idProjet;
            }
        }
        function ajouterRouteur() {
            window.location.href = 'creer_routeur.php?id=<?php echo $idProjet; ?>';
        }
        function connecterRouteur() {
            window.location.href = 'creer_lien_routeur_routeur.php?id=<?php echo $idProjet; ?>';
        }
        function ConnecterPcRouteur() {
            window.location.href = 'connecter_sous_reseau.php';
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
        <h1>Projet : <?php echo htmlspecialchars($projet['nom']); ?></h1>
    </div>

    <div class="button-container">
        <button class="button" id="supprimer-projet" onclick="supprimerProjet(<?php echo $idProjet; ?>)">Supprimer Projet</button>
        <button class="button" id="visualiser-projet" onclick="window.location.href='visualiser_projet.php?id=<?php echo $idProjet; ?>'">Visualiser Projet</button>
        <button class="button" id="simuler-routage" onclick="window.location.href='routage.php?id=<?php echo $idProjet; ?>'">Simuler Routage</button>
        <button class="button" id="ouvrir-reseau" onclick="document.getElementById('modal-ouvrir-reseau').style.display='block'">Ouvrir Réseau</button>
        <button class="button" id="creer-reseau" onclick="document.getElementById('modal-creer-reseau').style.display='block'">Créer Nouveau Réseau</button>
        <button class="button" id="ajouter-routeur" onclick="ajouterRouteur()">Ajouter Routeur</button>
        <button class="button" id="connecter-routeur" onclick="connecterRouteur()">Connecter un Routeur à un Routeur</button>
        <button class="button" id="connecter-pc"onclick="ConnecterPcRouteur()">Connecter un Sous-Réseaux à un Routeur</button>
        <button class="button" id="retour-accueil" onclick="window.location.href='accueil.php'">Retour à l'accueil</button>
    </div>


    <div id="modal-creer-reseau" class="modal">
        <div class="modal-content">
                <span class="close" onclick="document.getElementById('modal-creer-reseau').style.display='none'">&times;</span>
            <iframe src="creer_reseau.php?id=<?php echo $idProjet; ?>" style="width:100%; height:400px; border:none;"></iframe>
        </div>
    </div>

    
    <!-- Modal pour ouvrir les reseax existants -->
    <div id="modal-reseau" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modal-reseau').style.display='none'">&times;</span>
            <h2>Choisir un reseau</h2>
            <div id="liste-reseau-modal">
            </div>
        </div>
    </div>

</div>

<script>
        document.getElementById('ouvrir-reseau').addEventListener('click', function() {
            var modal = document.getElementById('modal-reseau');
            modal.style.display = 'block';
            fetch('./liste_reseau.php')
            .then(response => response.text())  // Utilisez response.text() si le PHP renvoie du HTML
            .then(html => {
                document.getElementById('liste-reseau-modal').innerHTML = html;  // Mettez directement le HTML dans le modal
            })
            .catch(error => {
                console.error('Erreur lors du chargement des reseau:', error);
                document.getElementById('liste-reseau-modal').innerHTML = '<p>Erreur lors du chargement des reseau.</p>';
            });
        });
</script>

</body>
</html>
