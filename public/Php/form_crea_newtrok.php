<?php
// Informations de connexion à la base de données
$host = 'localhost';
$dbname = 'BE';
$username = 'postgres';
$password = 'poiu';

// Vérification de la soumission du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Connexion à la base de données avec PDO
        $conn = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);

        // Configuration de PDO pour rapporter les erreurs
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Récupérer les données du formulaire
        $nom_reseau = $_POST['nom_reseau'];
        $mask_reseau = $_POST['mask_reseau'];
        $adresse_reseau = $_POST['adresse_reseau'];
        $ip_pc = $_POST['ip_pc'];
        $mac_pc = $_POST['mac_pc'];
        $mac_routeur = $_POST['mac_routeur'];

        // Début de la transaction
        $conn->beginTransaction();

        // Insérer le réseau dans la base de données
        $insert_reseau_query = "INSERT INTO réseau (nom, mask_reseau, adresse_réseau) VALUES (?, ?, ?)";
        $stmt_insert_reseau = $conn->prepare($insert_reseau_query);
        $stmt_insert_reseau->execute([$nom_reseau, $mask_reseau, $adresse_reseau]);

        // Récupérer l'ID du réseau inséré
        $id_reseau = $conn->lastInsertId();

        // Insérer les PC dans la base de données
        $insert_pc_query = "INSERT INTO Pc (IP, Mac, id_reseau) VALUES (?, ?, ?)";
        $stmt_insert_pc = $conn->prepare($insert_pc_query);
        foreach ($ip_pc as $key => $ip) {
            $stmt_insert_pc->execute([$ip, $mac_pc[$key], $id_reseau]);
        }

        // Insérer les routeurs dans la base de données
        $insert_routeur_query = "INSERT INTO Routeur (Mac) VALUES (?)";
        $stmt_insert_routeur = $conn->prepare($insert_routeur_query);
        foreach ($mac_routeur as $mac) {
            $stmt_insert_routeur->execute([$mac]);
        }

        // Valider la transaction
        $conn->commit();

        $message = "Les données ont été insérées avec succès.";
    } catch (PDOException $e) {
        // En cas d'erreur, annuler la transaction et rapporter l'erreur
        $conn->rollBack();
        $message = "Erreur : " . $e->getMessage();
    } finally {
        // Fermer la connexion
        $conn = null;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulaire de gestion du réseau</title>
    <link rel="stylesheet" href="../Css/accueil.css">
    <style>
    /* CSS pour centrer le formulaire */
    .form-container{
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        flex-direction: column;
    }

    </style>
</head>
<body>
    <div class="banniere">
        <img src="../Image/logo.jpeg" alt="logo" class="logo">
        <p>Net-Simulate</p>
    </div>

    <hr>
    <div class="titre">
        <p>Creation</p>
    </div>
  
    
    <div class="button-container">
        <button class="button" id="supprimer-projet" onclick="supprimerProjet(<?php echo $idProjet; ?>)">Supprimer Projet</button>
        <button class="button" id="visualiser-projet" onclick="window.location.href='visualiser_projet.php?id=<?php echo $idProjet; ?>'">Visualiser Projet</button>
        <button class="button" id="ouvrir-reseau" onclick="window.location.href='ouvrir_reseau.php?id=<?php echo $idProjet; ?>'">Ouvrir Réseau</button>
        <button class="button" id="creer-reseau" onclick="document.getElementById('modal-creer-reseau').style.display='block'">Créer Nouveau Réseau</button>
        <button class="button" id="retour-accueil" onclick="window.location.href='accueil.php'">Retour à l'accueil</button>

    </div>
    
    <!-- Div pour centrer le formulaire -->
    <div class="form-container">
        <h1>Gestion du réseau</h1>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <!-- Champ pour le nom du réseau -->
            <label for="nom_reseau">Nom du réseau :</label>
            <input type="text" id="nom_reseau" name="nom_reseau"><br><br>

            <!-- Champ pour le masque du réseau -->
            <label for="mask_reseau">Masque du réseau :</label>
            <input type="text" id="mask_reseau" name="mask_reseau"><br><br>

            <!-- Champ pour l'adresse du réseau -->
            <label for="adresse_reseau">Adresse du réseau :</label>
            <input type="text" id="adresse_reseau" name="adresse_reseau"><br><br>

            <!-- Section pour ajouter des PC -->
            <h2>Ajouter des PC</h2>
            <div id="pc_fields">
                <!-- Le JavaScript ajoutera ici les champs pour les PC -->
            </div>
            <button type="button" onclick="ajouterPC()">Ajouter un PC</button><br><br>

            <!-- Section pour ajouter des routeurs -->
            <h2>Ajouter des routeurs</h2>
            <div id="routeur_fields">
                <!-- Le JavaScript ajoutera ici les champs pour les routeurs -->
            </div>
            <button type="button" onclick="ajouterRouteur()">Ajouter un routeur</button><br><br>

            <!-- Bouton pour soumettre le formulaire -->
            <input type="submit" value="Enregistrer le réseau">
        </form>

        <!-- Affichage du message -->
        <?php if (!empty($message)): ?>
            <p><?php echo $message; ?></p>
        <?php endif; ?>
    </div>

    <!-- Script JavaScript pour ajouter dynamiquement des champs pour les PC et les routeurs -->
    <script>
        function ajouterPC() {
            const pcFields = document.getElementById('pc_fields');
            const div = document.createElement('div');
            div.innerHTML = `
                <label for="ip_pc">IP :</label>
                <input type="text" name="ip_pc[]"><br><br>
                <label for="mac_pc">MAC :</label>
                <input type="text" name="mac_pc[]"><br><br>
            `;
            pcFields.appendChild(div);
        }

        function ajouterRouteur() {
            const routeurFields = document.getElementById('routeur_fields');
            const div = document.createElement('div');
            div.innerHTML = `
                <label for="mac_routeur">MAC :</label>
                <input type="text" name="mac_routeur[]"><br><br>
            `;
            routeurFields.appendChild(div);
        }
    </script>
</body>
</html>