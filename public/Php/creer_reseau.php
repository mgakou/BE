<?php
session_start();
require_once 'fonction_adresse.php';

require_once('connecter_bd.php');


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
        $MTU= $_POST['MTU'];
 

        // Début de la transaction
        $conn->beginTransaction();

        if (masqueValide($mask_reseau) && adresseIPValideReseau($adresse_reseau)) {
            $insert_reseau_query = "INSERT INTO réseau (nom, mask_reseau, adresse_réseau, id_infrastructure, MTU) VALUES (?, ?, ?, ?, ?)";
            $stmt_insert_reseau = $conn->prepare($insert_reseau_query);
        if ($stmt_insert_reseau->execute([$nom_reseau, $mask_reseau, $adresse_reseau, $_SESSION['idProjet'], $MTU])) {
                $message = "Réseau ajouté avec succès.";
                $id_reseau = $conn->lastInsertId();
            } else {
                $message = "Échec de l'ajout du réseau.";
            }
        } else {
            if (!masqueValide($mask_reseau)) {
                $message = "Échec: Le masque de réseau '$mask_reseau' n'est pas valide.";
            }
            if (!adresseIPValideReseau($adresse_reseau)) {
                $message = "Échec: L'adresse réseau '$adresse_reseau' n'est pas valide.";
            }
        }

        // Récupérer l'ID du réseau inséré
        $id_reseau = $conn->lastInsertId();



        // Valider la transaction
        if (!empty($id_reseau)) {
            $conn->commit();
        } else {
            $conn->rollBack();
            $message = isset($message) ? $message : "Échec de l'opération et la transaction a été annulée.";
        }

        //$message = "Les données ont été insérées avec succès.";
    } catch (PDOException $e) {
        // En cas d'erreur, annuler la transaction et rapporter l'erreur
        $conn->rollBack();
        //$message = "Erreur : " . $e->getMessage();
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

    <!--<script>
    var id_infrastructure = <?php echo json_encode($id_infrastructure); ?>;
    console.log("iciu:", id_infrastructure);
</script>-->

    <!-- Div pour centrer le formulaire -->
    <div class="form-container">
    
        <h1>Creation</h1>
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
            
            <!-- Champ pour l'adresse du réseau -->
            <label for="MTU">MTU :</label>
            <input type="text" id="MTU" name="MTU"><br><br>
            
            <!-- Bouton pour soumettre le formulaire -->
            <input type="submit" value="Enregistrer le réseau">

            <!-- Champ caché pour l'idProjet -->
            <input type="hidden" name="id_infrastructure" value="<?php echo $idProjet; ?>">

        </form>

        <!-- Affichage du message -->
        <?php if (!empty($message)): ?>
            <p><?php echo $message; ?></p>
        <?php endif; ?>
    </div>

</body>
</html>