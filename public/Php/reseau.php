<?php
session_start();

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: connexion.html");
    exit;
}

// Récupérer l'ID du réseau depuis l'URL
$idReseau = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($idReseau <= 0) {
    header("Location: accueil.html");
    exit;
}


require_once('connecter_bd.php');

try {
    $connexion = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Sélectionner le réseau spécifique
    $stmt = $connexion->prepare("SELECT * FROM réseau WHERE id_reseau = :idReseau");
    $stmt->bindParam(':idReseau', $idReseau, PDO::PARAM_INT);
    $stmt->execute();
    $reseau = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reseau) {
        echo "Réseau non trouvé.";
        header("Location: accueil.php");
        exit;
    }

    $_SESSION['idReseau'] = $idReseau;

} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion du Réseau: <?php echo htmlspecialchars($reseau['nom']); ?></title>
    <link rel="stylesheet" href="../Css/accueil.css">
    <script>
        function supprimerReseau(idReseau) {
            if (confirm("Êtes-vous sûr de vouloir supprimer ce réseau ?")) {
                window.location.href = 'supprimer_reseau.php?id=' + idReseau;
            }
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
        <h1>Réseau : <?php echo htmlspecialchars($_SESSION['idReseau']); ?>
    </div>

    <div class="button-container">
        <button class="button" id="supprimer-reseau" onclick="supprimerReseau(<?php echo $idReseau; ?>)">Supprimer Réseau</button>
        <button class="button" id="Connecter réseau - routeur" onclick="window.location.href='connecter_reseau.php?id=<?php echo $idReseau; ?>'">Connecter un réseau à un routeur</button>
        <button class="button" id="ouvrir-sous-reseau" onclick="document.getElementById('modal-ouvrir-sous-reseau').style.display='block'">Ouvrir Sous Réseau</button>
        <button class="button" id="ajouter-sous-reseau" onclick="document.getElementById('modal-ajouter-sous-reseau').style.display='block'">Ajouter Sous-Réseau</button>
        <button class="button" id="retour-projet" onclick="window.location.href='projet.php?id=<?php echo $_SESSION['idProjet']; ?>'">Retour au Projet</button>
    </div>

    <div id="modal-ajouter-sous-reseau" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('modal-ajouter-sous-reseau').style.display='none'">&times;</span>
            <iframe src="creer_sous_reseau.php?id=<?php echo $idReseau; ?>" style="width:100%; height:400px; border:none;"></iframe>
        </div>
    </div>

   <!-- Div pour le modal de sous-réseau, en supposant que tu veux montrer la liste des sous-réseaux ici -->
<div id="modal-ouvrir-sous-reseau" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('modal-ouvrir-sous-reseau').style.display='none'">&times;</span>
        <h2>Gérer les sous-réseaux</h2>
        <div id="liste-sous-reseau-modal">
            <!-- Les sous-réseaux seront ajoutés ici dynamiquement -->
        </div>
    </div>
</div>
    
    <script>
        document.getElementById('ouvrir-sous-reseau').addEventListener('click', function() {
    var modal = document.getElementById('modal-ouvrir-sous-reseau');
    modal.style.display = 'block';
    fetch('./liste_sous_reseau.php')
    .then(response => response.text())
    .then(html => {
        document.getElementById('liste-sous-reseau-modal').innerHTML = html;
    })
    .catch(error => {
        console.error('Erreur lors du chargement des sous-réseaux:', error);
        document.getElementById('liste-sous-reseau-modal').innerHTML = '<p>Erreur lors du chargement des sous-réseaux.</p>';
    });
});

</script>
</body>
</html>
