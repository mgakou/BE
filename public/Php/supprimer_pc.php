<?php
session_start();

// Vérifiez que les variables de session nécessaires sont définies
if (isset($_SESSION['idProjet']) && isset($_SESSION['id_utilisateur'])) {
    $host = 'localhost';
    $dbname = 'BE';
    $username = 'postgres';
    $password = 'poiu';

    // Créer une instance PDO
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $message = "";

    // Fonction pour supprimer un PC par son adresse IP
    function deletePCByIP($pdo, $ip)
    {
        // Vérifier si le PC existe dans la base de données
        $checkStmt = $pdo->prepare("SELECT id_pc FROM Pc WHERE IP_Pc = ?");
        $checkStmt->execute([$ip]);
        $pc = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($pc) {
            $pc_id = $pc['id_pc'];

            // Supprimer le PC de la base de données
            $deleteStmt = $pdo->prepare("DELETE FROM Pc WHERE id_pc = ?");
            $deleteStmt->execute([$pc_id]);

            return "Le PC a été supprimé avec succès.";
        } else {
            return "Le PC avec l'adresse IP spécifiée n'existe pas.";
        }
    }

    // Fonction pour vérifier le format d'une adresse IP
    function validateIP($ip)
    {
        $ip_parts = explode('.', $ip);
        if (count($ip_parts) != 4) {
            return false; // L'adresse IP doit avoir 4 parties
        }
        foreach ($ip_parts as $part) {
            if (!ctype_digit($part) || $part < 0 || $part > 255) {
                return false; // Chaque partie doit être un nombre entre 0 et 255
            }
        }
        return true;
    }

    // Traitement de la requête de suppression de PC
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['delete_pc'])) {
            $ip = $_POST['ip_pc'];
            if (validateIP($ip)) {
                $message = deletePCByIP($pdo, $ip);
            } else {
                $message = "Format d'adresse IP invalide.";
            }
        }
    }
} else {
    // Redirection si les variables de session ne sont pas définies
    echo "Erreur: Variables de session non définies.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Supprimer un PC par son adresse IP</title>
    <link rel="stylesheet" href="../Css/pc.css">
</head>

<body>
    <div class="banniere">
        <img src="../Image/logo.jpeg" alt="logo" class="logo">
        <p>Net-Simulate</p>
    </div>

    <hr>

    <div class="titre">
        <h1>Supprimer un PC par son adresse IP</h1>
    </div>

    <div class="form-container">
        <form method="post" action="">
            <div class="form-group">
                <label for="ip_pc">Adresse IP du PC à supprimer :</label>
                <input type="text" id="ip_pc" name="ip_pc" required><br><br>
            </div>

            <div class="form-group">
                <button type="submit" name="delete_pc" class="submit-button">Supprimer PC</button>
            </div>

            <?php if ($message != "") : ?>
                <div class="form-message">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
