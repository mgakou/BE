<?php
session_start();

// Vérifiez que les variables de session nécessaires sont définies
if (isset($_SESSION['idProjet']) && isset($_SESSION['id_utilisateur'])) {
    $host = 'localhost';
    $dbname = 'BE';
    $username = 'postgres';
    $password = 'Niktwo.3111';

    // Créer une instance PDO
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $message = "";

    // Traitement du formulaire lorsqu'il est soumis
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $ip_routeur = $_POST['ip_routeur'];

        // Vérifier si l'adresse IP existe déjà
        $checkStmt = $pdo->prepare("SELECT * FROM Routeur WHERE IP_Routeur = ?");
        $checkStmt->execute([$ip_routeur]);
        if ($checkStmt->rowCount() > 0) {
            // IP déjà utilisée
            $message = "L'adresse IP du routeur existe déjà.";
        } else {
            // L'IP n'est pas utilisée, procéder à l'insertion
            $insertStmt = $pdo->prepare("INSERT INTO Routeur (IP_Routeur) VALUES (?)");
            if ($insertStmt->execute([$ip_routeur])) {
                $message = "Nouveau routeur ajouté avec succès!";
            } else {
                $message = "Erreur: " . $insertStmt->errorInfo()[2];
            }
        }
        
  
        $checkStmt = null;
        $insertStmt = null;
    }

    // Fermeture de la connexion
    $pdo = null;


?>

    <!DOCTYPE html>
    <html lang="fr">
    <head>
    <meta charset="UTF-8">
    <title>Ajouter un Routeur</title>
        <link rel="stylesheet" href="../Css/routeur.css">
    </head>
    <body>
        <div class="banniere">
            <img src="../Image/logo.jpeg" alt="logo" class="logo">
            <p>Net-Simulate</p>
        </div>

        <hr>

        <div class="titre">
            <h1>Ajouter un Routeur</h1>
        </div>

        <div class="form-container">
        <form method="post" action="creer_routeur.php" class="form">
            <div class="form-group">
                <label for="ip_routeur">IP du Routeur:</label>
                <input type="text" id="ip_routeur" name="ip_routeur" required>
            </div>
        <div class="form-group">
            <button type="submit" class="submit-button">Ajouter Routeur</button>
        
            <a href="../PHP/accueil.php" type="button" class="cancel-button">Annuler</a>

            <?php if ($message != "") : ?>
                <div class="form-message">
            <?php echo htmlspecialchars($message); ?>

            </div>
            <?php endif; ?>

        </div>
</form>

    </div>
    </body>
    </html>
    <?php
} else {
    // Redirection si les variables de session ne sont pas définies
    echo "error";
    exit;
}
?>
