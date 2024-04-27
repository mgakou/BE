<?php
   
session_start();

require_once('connecter_bd.php');

$conn = pg_connect("host=$host dbname=$dbname user=$username password=$password");

// Vérification de la connexion
if (!$conn) {
    echo "Erreur de connexion à la base de données.\n";
    exit;
}

// Vérification de la soumission du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $id_routeur1 = $_POST['ID_Rout1'];
    $id_routeur2 = $_POST['ID_Rout2'];
    $interface = $_POST['Interface'];

    
}
  

function connecter_routeur($id_routeur1, $id_routeur2, $interface){
    global $conn;

    $result = pg_query($conn, "SELECT * FROM routeur WHERE id_routeur = $id_routeur1");
    $resultat = pg_fetch_assoc($result);
    if(!$resultat){
        echo "Erreur1";
        exit();
    }

    $routeur1 = $resultat;
    $MTU_routeur1 = $routeur1['mtu'];

    $result = pg_query($conn, "SELECT * FROM routeur WHERE id_routeur = $id_routeur2");
    $resultat = pg_fetch_assoc($result);
    if(!$resultat){
        echo "Erreur2";
        exit();
    }

    $routeur2 = $resultat;
    $MTU_routeur2 = $routeur2['mtu'];

    $result = pg_query($conn, "SELECT * FROM Elements WHERE id_elements IN 
    (SELECT id_elements FROM elem_routeur WHERE id_routeur = $id_routeur1);");
    $resultat = pg_fetch_all($result);
    if(empty($resultat)){
        echo "Erreur3";
        exit();
    }
    $TableRoutageRouteur1 = $resultat;

    $result = pg_query($conn, "SELECT * FROM Elements WHERE id_elements IN 
    (SELECT id_elements FROM elem_routeur WHERE id_routeur = $id_routeur2);");
    $resultat = pg_fetch_all($result);
    if(empty($resultat)){
        echo "Erreur4";
        exit();
    }
    $TableRoutageRouteur2 = $resultat;

    $result = pg_query($conn, "INSERT INTO connecter_routeur (id_routeur, id_routeur_1, interface_routeur) VALUES ('$id_routeur1', '$id_routeur2', '$interface')");

    foreach ($TableRoutageRouteur1 as $ElemRoutageRouteur1){
        foreach($TableRoutageRouteur2 as $ElemRoutageRouteur2){
            $ip_elem_routeur2 = $ElemRoutageRouteur2['ip_destination'];
            $ip_elem_routeur1 = $ElemRoutageRouteur1['ip_destination'];
            $mask_elem_routeur1 = $ElemRoutageRouteur1['masque_destination'];
            if(!memeReseau($ip_elem_routeur1, $ip_elem_routeur2, $mask_elem_routeur1)){
                $mask_elem_routeur2 = $ElemRoutageRouteur2['masque_destination'];
                $result = pg_query($conn, "INSERT INTO elements (ip_destination, interface_relayage, masque_destination, MTU) VALUES ('$ip_elem_routeur2', '$interface', '$mask_elem_routeur2', $MTU_routeur1 )");
                $result = pg_query($conn, "SELECT * FROM elements WHERE ip_destination = '$ip_elem_routeur2' AND interface_relayage = '$interface' AND masque_destination = '$mask_elem_routeur2' AND MTU = $MTU_routeur1");
                $element = pg_fetch_assoc($result);
                $id_element = $element['id_elements'];

                $result = pg_query($conn, "INSERT INTO elem_routeur (id_routeur, id_elements) VALUES ('$id_routeur2', '$id_element' )");
            }
        }
    }
    foreach ($TableRoutageRouteur2 as $ElemRoutageRouteur2){
        foreach($TableRoutageRouteur1 as $ElemRoutageRouteur1){
            $ip_elem_routeur2 = $ElemRoutageRouteur2['ip_destination'];
            $ip_elem_routeur1 = $ElemRoutageRouteur1['ip_destination'];
            $mask_elem_routeur1 = $ElemRoutageRouteur1['masque_destination'];
            if(!memeReseau($ip_elem_routeur1, $ip_elem_routeur2, $mask_elem_routeur1)){
                $mask_elem_routeur2 = $ElemRoutageRouteur2['masque_destination'];
                $result = pg_query($conn, "INSERT INTO elements (ip_destination, interface_relayage, masque_destination, MTU) VALUES ('$ip_elem_routeur1', '$interface', '$mask_elem_routeur1', $MTU_routeur2 )");
                $result = pg_query($conn, "SELECT * FROM elements WHERE ip_destination = '$ip_elem_routeur1' AND interface_relayage = '$interface' AND masque_destination = '$mask_elem_routeur1' AND MTU = $MTU_routeur2");
                $element = pg_fetch_assoc($result);
                $id_element = $element['id_elements'];

                $result = pg_query($conn, "INSERT INTO elem_routeur (id_routeur, id_elements) VALUES ('$id_routeur1', '$id_element' )");
            }
        }
    }

}


function memeReseau($adresse1, $adresse2, $masque) {
    // Convertir les adresses IP en entiers sans point
    $adresse1Int = ip2long($adresse1);
    $adresse2Int = ip2long($adresse2);
    $masqueInt = ip2long($masque);

    // Calculer les adresses réseau pour chaque adresse IP
    $reseau1 = $adresse1Int & $masqueInt;
    $reseau2 = $adresse2Int & $masqueInt;

    // Comparer les adresses réseau
    if ($reseau1 == $reseau2) {
        return 1; // Les adresses sont dans le même réseau
    } else {
        return 0; // Les adresses ne sont pas dans le même réseau
    }
}


?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creer lien routeur routeur</title>
    <link rel="stylesheet" href="../Css/routeur.css">
</head>
<body>
    <div class="banniere">
        <img src="../Image/logo.jpeg" alt="logo" class="logo">
        <p>Net-Simulate</p>
    </div>

    <hr>

    <div class="titre">
        
        <h2>Connecter deux routeurs</h2>
        
    </div>


    <div class="form-container">
        <form method="post" action="">
            <div class="form-group">
                <label for="ID_Rout1">ID du routeur 1 :</label>
                <input type="text" id="ID_Rout1" name="ID_Rout1" required><br><br>
            </div>
            <div class="form-group">
                <label for="ID_Rout2">ID du routeur 2 :</label>
                <input type="text" id="ID_Rout2" name="ID_Rout2" required><br><br>
            </div>

            <div class="form-group">
                <label for="Interface">Interface:</label>
                <input type="text" id="Interface" name="Interface" required><br><br>
            </div>
            <button type="submit">Connecter les routeurs</button>
            <button class="button" onclick="retourAuSousReseau()">Annuler</button>
        </form>
    </div>
    
    <script>
    function retourAuSousReseau() {
        window.location.href = 'reseau.php?id=<?php echo $idReseau; ?>';
    }
    </script>
        
    </div>


    <table>
        <tr>
            <th>Id Routeur</th>
            <th>IP Routeur</th>
        </tr>
        <?php
        $result = pg_query($conn, "SELECT * FROM routeur");
        $resultat = pg_fetch_all($result);
        foreach ($resultat as $routeur) {
            echo "<tr>";
            echo "<td>" . $routeur['id_routeur'] . "</td>";
            echo "<td>" . $routeur['ip_routeur'] . "</td>";
            echo "</tr>";
        }
        ?>
    </table>
</body>
</html>