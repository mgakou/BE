<?php
session_start();
require_once 'fonction_adresse.php';  


require_once('connecter_bd.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        
        $conn = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

       
        $mask_sous_reseau = $_POST['mask_sous_reseau'];
        $ip_sous_reseau = $_POST['ip_sous_reseau'];
        $id_reseau = $_POST['id_reseau'];

      
        $conn->beginTransaction();

        
        $stmt = $conn->prepare("SELECT adresse_réseau FROM réseau WHERE id_reseau = ?");
        $stmt->execute([$id_reseau]);
        $adresse_reseau = $stmt->fetchColumn();

        if (!$adresse_reseau) {
            $message ="L'ID réseau spécifié ne correspond à aucun réseau existant.";
        }

        if (masqueValide($mask_sous_reseau) && AdresseIPValideSousReseau($adresse_reseau, $mask_sous_reseau, $ip_sous_reseau)) {
            $insert_sous_reseau_query = "INSERT INTO sous_réseau (mask, IP_Sous_Reseau, id_reseau) VALUES (?, ?, ?)";
            $stmt_insert_sous_reseau = $conn->prepare($insert_sous_reseau_query);
            $stmt_insert_sous_reseau->execute([$mask_sous_reseau, $ip_sous_reseau, $id_reseau]);
            $message = "Sous réseau ajouté avec succès ! ";
            $conn->commit();
        } else {
            $message = "Échec de l'ajout du sous-réseau. Le masque et/ou l'adresse IP invalide(s).";
            $conn->rollBack();
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        //$message = "Erreur : " . $e->getMessage();
        $message = "Erreur champs";
    } catch (Exception $e) {
        $conn->rollBack();
        $message = $e->getMessage();
    } finally {
       
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
