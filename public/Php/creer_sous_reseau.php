<?php
session_start();

// Informations de connexion à la base de données
$host = 'localhost';
$dbname = 'BE';
$username = 'postgres';
$password = 'Niktwo.3111';

// Vérification de la soumission du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Connexion à la base de données avec PDO
        $conn = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);

        // Configuration de PDO pour rapporter les erreurs
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Récupérer les données du formulaire
        $mask_sous_reseau = $_POST['mask_sous_reseau'];
        $ip_sous_reseau = $_POST['ip_sous_reseau'];
        $id_reseau = $_POST['id_reseau'];

        // Début de la transaction
        $conn->beginTransaction();

        // Insérer le sous-réseau dans la base de données
        $insert_sous_reseau_query = "INSERT INTO sous_réseau (mask, IP_Sous_Reseau, id_reseau) VALUES (?, ?, ?)";
        $stmt_insert_sous_reseau = $conn->prepare($insert_sous_reseau_query);
        $stmt_insert_sous_reseau->execute([$mask_sous_reseau, $ip_sous_reseau, $id_reseau]);

        // Valider la transaction
        $conn->commit();

        $message = "Le sous-réseau a été créé avec succès.";
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
    <title>Création de sous-réseau</title>
    <link rel="stylesheet" href="../Css/accueil.css">
    <style>
        .form-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            flex-direction: column;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Création d'un sous-réseau</h1>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <label for="mask_sous_reseau">Masque du sous-réseau :</label>
            <input type="text" id="mask_sous_reseau" name="mask_sous_reseau"><br><br>

            <label for="ip_sous_reseau">Adresse IP du sous-réseau :</label>
            <input type="text" id="ip_sous_reseau" name="ip_sous_reseau"><br><br>

            <label for="id_reseau">ID du réseau :</label>
            <input type="number" id="id_reseau" name="id_reseau" value="<?php echo isset($_GET['id']) ? intval($_GET['id']) : ''; ?>"><br><br>

            <input type="submit" value="Enregistrer le sous-réseau">
        </form>

        <?php if (!empty($message)): ?>
            <p><?php echo $message; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
