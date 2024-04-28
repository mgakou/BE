<?php
session_start();
require_once('connecter_bd.php');

// Vérifiez que les variables de session nécessaires sont définies
if (isset($_SESSION['idProjet']) && isset($_SESSION['id_utilisateur'])) {
    try {
        // Créer une instance PDO
        $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // Récupérer les informations sur les réseaux
        $stmt_reseaux = $pdo->query("SELECT * FROM réseau");
        $reseaux = $stmt_reseaux->fetchAll();

        // Récupérer les informations sur les sous-réseaux
        $stmt_sous_reseaux = $pdo->query("SELECT * FROM sous_réseau");
        $sousReseaux = $stmt_sous_reseaux->fetchAll();

        // Récupérer les informations sur les PC
        $stmt_pcs = $pdo->query("SELECT * FROM Pc");
        $pcs = $stmt_pcs->fetchAll();

        // Récupérer les informations sur les routeurs
        $stmt_routeurs = $pdo->query("SELECT * FROM Routeur");
        $routeurs = $stmt_routeurs->fetchAll();

        // Récupérer les informations sur les routeurs reliés aux réseaux
        $stmt_routeurs_reseaux = $pdo->query("SELECT * FROM connecter_routeur");
        $routeursReseaux = $stmt_routeurs_reseaux->fetchAll();
        $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // Récupérer les informations sur les éléments connectés
        $stmt_elements = $pdo->query("SELECT * FROM Elements");
        $elements = $stmt_elements->fetchAll();

        // Récupérer les informations sur les connexions entre PC et routeurs
        $stmt_connecter_pc = $pdo->query("SELECT * FROM connecter_pc");
        $connecter_pc = $stmt_connecter_pc->fetchAll();

        // Récupérer les informations sur les connexions entre routeurs
        $stmt_connecter_routeur = $pdo->query("SELECT * FROM connecter_routeur");
        $connecter_routeur = $stmt_connecter_routeur->fetchAll();
    } catch (PDOException $e) {
        // Gérer les erreurs de base de données
        echo "Erreur de base de données: " . $e->getMessage();
    }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Visualisation du Projet</title>
    <style>
        .banniere {
    background-color: white;
    color : #2E7590;
    text-align: center;
    font-family: 'Arial', sans-serif;
    align-items: center;
    height: 120px;
    display: flex;
    position: relative;
}

.banniere p {
    margin-top: 50px;
    font-size: 40px;
    position: absolute;
    width: 100%;
    text-align: center;
}

body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
}

.button-container {
    display: flex;
}
.logo {
    position: absolute;
    left: 20px;
    width: 100px;
    height: 100px;
}

.hr {
    border : none;
    height: 5px;
    background-color: #2E7590;
    margin : 0;
}

.titre {
    text-align: center;
    font-family: Roboto;
    font-size: 25px;
    font-weight: 700;

}
.button {
    margin-top: 20px;
    color: black;
    padding: 10px 20px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 16px;
    cursor: pointer;
    flex: 1;
    border-radius: 0;
    
}
    </style>
</head>
<body>

<div class="banniere">
        <img src="../Image/logo.jpeg" alt="logo" class="logo">
        <p>Net-Simulate</p>

    </div>

    <hr>



    <div class="button-container">
        <button class="button" id="retour-accueil" onclick="window.location.href='accueil.php'">Retour à l'accueil</button>
    </div>
    

    <h1>Visualisation du Projet</h1>
    
    <h2>Réseaux</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>MTU</th>
            <th>Mask Réseau</th>
            <th>Adresse Réseau</th>
        </tr>
        <?php foreach ($reseaux as $reseau): ?>
            <tr>
                <td><?php echo $reseau['id_reseau']; ?></td>
                <td><?php echo $reseau['nom']; ?></td>
                <td><?php echo $reseau['mtu']; ?></td>
                <td><?php echo $reseau['mask_reseau']; ?></td>
                <td><?php echo $reseau['adresse_réseau']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <h2>Sous-Réseaux</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Mask</th>
            <th>Adresse IP</th>
            <th>ID Réseau</th>
        </tr>
        <?php foreach ($sousReseaux as $sousReseau): ?>
            <tr>
                <td><?php echo $sousReseau['id_sousréseau']; ?></td>
                <td><?php echo $sousReseau['mask']; ?></td>
                <td><?php echo $sousReseau['ip_sous_reseau']; ?></td>
                <td><?php echo $sousReseau['id_reseau']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>PC</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Adresse IP</th>
            <th>ID Sous-Réseau</th>
        </tr>
        <?php foreach ($pcs as $pc): ?>
            <tr>
                <td><?php echo $pc['id_pc']; ?></td>
                <td><?php echo $pc['ip_pc']; ?></td>
                <td><?php echo $pc['id_sousréseau']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Routeurs</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>Adresse IP</th>
            <th>MTU</th>
        </tr>
        <?php foreach ($routeurs as $routeur): ?>
            <tr>
                <td><?php echo $routeur['id_routeur']; ?></td>
                <td><?php echo $routeur['ip_routeur']; ?></td>
                <td><?php echo $routeur['mtu']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Routeurs Reliés aux Réseaux</h2>
    <table border="1">
        <tr>
            <th>ID Routeur</th>
            <th>ID Routeur Relié</th>
            <th>Interface Routeur</th>
        </tr>
        <?php foreach ($routeursReseaux as $routeurReseau): ?>
            <tr>
                <td><?php echo $routeurReseau['id_routeur']; ?></td>
                <td><?php echo $routeurReseau['id_routeur_1']; ?></td>
                <td><?php echo $routeurReseau['interface_routeur']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <h2>Informations sur les éléments connectés</h2>
    <table border="1">
        <tr>
            <th>ID</th>
            <th>IP de destination</th>
            <th>Interface de relais</th>
            <th>Masque de destination</th>
            <th>MTU</th>
        </tr>
        <?php foreach ($elements as $element): ?>
            <tr>
                <td><?php echo $element['id_elements']; ?></td>
                <td><?php echo $element['IP_destination']; ?></td>
                <td><?php echo $element['interface_relayage']; ?></td>
                <td><?php echo $element['masque_destination']; ?></td>
                <td><?php echo $element['MTU']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Informations sur les connexions entre PC et routeurs</h2>
    <table border="1">
        <tr>
            <th>ID PC</th>
            <th>ID Routeur</th>
            <th>Interface Routeur-PC</th>
        </tr>
        <?php foreach ($connecter_pc as $connexion): ?>
            <tr>
                <td><?php echo $connexion['id_pc']; ?></td>
                <td><?php echo $connexion['id_routeur']; ?></td>
                <td><?php echo $connexion['interface_routeur_pc']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Informations sur les connexions entre routeurs</h2>
    <table border="1">
        <tr>
            <th>ID Routeur 1</th>
            <th>ID Routeur 2</th>
            <th>Interface Routeur 1-Routeur 2</th>
        </tr>
        <?php foreach ($connecter_routeur as $connexion): ?>
            <tr>
                <td><?php echo $connexion['id_routeur']; ?></td>
                <td><?php echo $connexion['id_routeur_1']; ?></td>
                <td><?php echo $connexion['interface_routeur']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>


</body>
</html>

<?php
} else {
    // Redirection si les variables de session ne sont pas définies
    echo "error";
    exit;
}