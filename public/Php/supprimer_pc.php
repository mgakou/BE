<?php
session_start();
$idSousReseau = isset($_GET['id']) ? intval($_GET['id']) : 0;
// Vérifiez que les variables de session nécessaires sont définies
if (isset($_SESSION['idProjet']) && isset($_SESSION['id_utilisateur'])) {
    
    require_once('connecter_bd.php');

    // Créer une instance PDO
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $message = "";

    // Fonction pour supprimer un PC par son adresse IP
    function deletePCByIP($connexion, $ip) {
        try {
            // Commencer une transaction pour gérer la suppression en cascade
            $connexion->beginTransaction();
    
            // Récupérer l'ID du PC à partir de son adresse IP
            $stmt = $connexion->prepare("SELECT id_pc FROM Pc WHERE IP_Pc = ?");
            $stmt->execute([$ip]);
            $pc = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($pc) {
                $id_pc = $pc['id_pc'];
    

                $stmt = $connexion->prepare("DELETE FROM connecter_pc WHERE id_pc = ?");
                $stmt->execute([$id_pc]);
    
                $stmt = $connexion->prepare("DELETE FROM Paquet WHERE id_pc = ?");
                $stmt->execute([$id_pc]);
    
            
                $stmt = $connexion->prepare("DELETE FROM elem_pc WHERE id_pc = ?");
                $stmt->execute([$id_pc]);
    
                
                $stmt = $connexion->prepare("DELETE FROM Pc WHERE id_pc = ?");
                $stmt->execute([$id_pc]);
    
                $connexion->commit();
                return "Le PC avec l'adresse IP " . $ip . " a été supprimé avec succès.";
            } else {
                return "Aucun PC avec l'adresse IP spécifiée n'a été trouvé.";
            }
        } catch (Exception $e) {
            // Annuler la transaction en cas d'erreur
            $connexion->rollBack();
            return "Erreur: " . $e->getMessage();
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
                <button type="DELETE" name="delete_pc" class="submit-button">Supprimer PC</button>
                <button type="button" class="button" onclick="retourAuSousReseau()">Annuler</button>

            </div>
            <script>
                function retourAuSousReseau() {
                    window.location.href = 'sous_reseau.php?id=<?php echo $idSousReseau; ?>';
                }
            </script>
            <div class="form-group">
            </div>
            
            
            <?php if ($message != "") : ?>
                <div class="form-message">
                    <?php echo htmlspecialchars($message); ?>
                </div>

            <?php endif; ?>
        </form>
        
    </div>
    <h3><table>
                <tr>   
                    <th>--|ID PC |--</th>
                    <th>--|IP PC|--</th>
                    <th>--|ID Sous-réseau|--</th>
                </tr>
                <?php
                $stmt = $pdo->query("SELECT * FROM Pc");
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" ."--|". $row['id_pc'] ."|--". "</td>";
                    echo "<td>" ."--|". $row['ip_pc'] ."|--". "</td>";
                    echo "<td>" ."--|". $row['id_sousréseau'] ."|--". "</td>";
                    echo "</tr>";
                }
                ?>
   <h3> </table>
    
</body>
</html>
