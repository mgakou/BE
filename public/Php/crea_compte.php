<?php
//Download Xamp et lancer Apache
// Aller dans le fichier php.ini et décommenter la ligne extension=pgsql
// Connexion à la base de données http://localhost/BE/public/connexion.html
// Remplir ici vos infos postgres de connexion
$host = "localhost";
$dbname = "BE";
$username = "postgres";
$password = "Niktwo.3111"; // Modifier avec votre mot de passe réel

try {
    $dbco = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $dbco->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Data postgres
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Vérif BD
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Format de l'adresse e-mail invalide.'); window.location.href = '../Html/inscription.html';</script>";
    } elseif (!preg_match("/(?=.*[A-Z])(?=.*\W)/", $password)) {
        echo "<script>alert('Le mot de passe ne respecte pas la nomenclature.'); window.location.href = '../Html/inscription.html';</script>";
    } else {
        // Vérif email existant
        $checkEmail = $dbco->prepare("SELECT pseudo FROM utilisateur WHERE pseudo = ?");
        $checkEmail->execute([$email]);
        if ($checkEmail->rowCount() > 0) {
            echo "<script>alert('Cette adresse e-mail est déjà utilisée.'); window.location.href = '../Html/inscription.html';</script>";
        } else {
            // Hachage avec fonction PHP intégré
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Insertion BD
            $sql = "INSERT INTO utilisateur (pseudo, mot_de_passe) VALUES (?, ?)";
            $stmt = $dbco->prepare($sql);
            $stmt->execute([$email, $passwordHash]);

            // Message succées
            echo "<script>alert('Compte créé avec succès !'); window.location.href = '../Html/connexion.html';</script>";
        }
    }
} catch (PDOException $e) {
    echo "Erreur de connexion : " . $e->getMessage();
}
?>
