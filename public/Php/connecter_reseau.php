<?php
/* Début de la session, cela permet de récupérer les variables de session */
session_start();

/* Connexion à la BD */
require_once('connecter_bd.php');

/* Création d'une chaine de connexion, qui permet de se connecter à la BD */
$connexion_chain= "host=$host dbname=$bdnom user=$utilisateur password=$mdp";

/*apelle de la focntion pg_connect, cela permet de d'utiliser la chaine de connexion pour s'identifier à la bd */
$conn = pg_connect($connexion_chain);

/* Verification si la connexion est établie ou à échouée */
if (!$conn) {
    // Si la connexion échoue, afficher l'erreur
    echo "Erreur de connexion à la base de données: " . pg_last_error();
    exit;
}


/* Vérification de l'entrée utilisateur des champs de connexion */
if (isset($_POST['submit'])) {
    $id_reseau = $_POST['id_reseau'];
    $id_routeur = $_POST['id_routeur'];
    $interface = $_POST['interface'];
    connecter_reseau($id_reseau, $id_routeur, $interface);
}


/* Fonction de connexion d'un réseau à un routeur */
function connecter_reseau($id_reseau, $id_routeur, $interface){
    global $conn;

    /* Recuperation des infos du réseau et du routeur */
    $result = pg_query($conn, "SELECT * FROM réseau WHERE id_reseau = $id_reseau");
    $resultat = pg_fetch_assoc($result);
    if(!$resultat){
        echo "Erreur1";
        exit();
    }


    $reseau = $resultat;
    $ip_reseau = $reseau["adresse_réseau"];
    $mask_reseau = $reseau["mask_reseau"];
    $MTU_reseau = $reseau["mtu"];

    $result = pg_query($conn, "SELECT * FROM routeur WHERE id_routeur = $id_routeur");
    $resultat = pg_fetch_assoc($result);
    if(!$resultat){
        echo "Erreur2";
        exit();
    }
    
    $routeur = $resultat;
    $MTU_routeur = $routeur['mtu'];
    /* Insertion des infos du réseau dans la table elements */
    $result = pg_query($conn, "INSERT INTO elements (ip_destination, interface_relayage, masque_destination, mtu) VALUES ('$ip_reseau', '$interface', '$mask_reseau', $MTU_reseau )");
    
    /* Recuperation de l'id de l'element */
    $result = pg_query($conn, "SELECT * FROM elements WHERE ip_destination = '$ip_reseau' AND interface_relayage = '$interface' AND masque_destination = '$mask_reseau' AND MTU = $MTU_reseau");
    $element = pg_fetch_assoc($result);
    $id_element = $element['id_elements'];

    $result = pg_query($conn, "INSERT INTO elem_routeur (id_routeur, id_elements) VALUES ('$id_routeur', '$id_element' )");
    $resultat = pg_fetch_assoc($result);
  
    /* Recuperation des pc du réseau */
    $result = pg_query($conn, "SELECT * FROM Pc
    JOIN sous_réseau ON Pc.id_sousréseau = sous_réseau.id_sousréseau
    JOIN réseau ON sous_réseau.id_reseau = réseau.id_reseau
    WHERE réseau.id_reseau = '$id_reseau';
    ");
    $resultat = pg_fetch_all($result);
    
    if(!$resultat[0]){
        echo "Erreur3";
        exit();
    }
   
    /* Insertion des informations contenu dans les pc dans la table elements */
    $liste_pc = $resultat;
    $result = pg_query($conn, "SELECT * FROM Elements WHERE id_elements IN 
    (SELECT id_elements FROM elem_routeur WHERE id_routeur = $id_routeur);");
    $resultat = pg_fetch_all($result);
    if(!$resultat[0]){
        echo "Erreur4";
        exit();
    }
   
    $TableRoutageRouteur = $resultat;
    foreach($liste_pc as $pc){
        $id_pc = $pc['id_pc'];
        $result = pg_query($conn, "INSERT INTO connecter_pc (id_pc, id_routeur, interface_routeur_pc) VALUES ('$id_pc', '$id_routeur', '$interface')");
      

        $result = pg_query($conn, "SELECT * FROM Elements WHERE id_elements IN 
        (SELECT id_elements FROM elem_pc WHERE id_pc = $id_pc);");
        $resultat = pg_fetch_all($result);

        if(empty($resultat)){
            echo "Erreur5";
            exit();
        }
        $TableRoutagePc = $resultat;
        foreach ($TableRoutageRouteur as $ElemRoutageRouteur){
            foreach($TableRoutagePc as $ElemRoutagePc){
                $ip_elem_routeur = $ElemRoutageRouteur['ip_destination'];
                $ip_elem_pc = $ElemRoutagePc['ip_destination'];
                $mask_elem_pc = $ElemRoutagePc['masque_destination'];
                if(!memeReseau($ip_elem_pc, $ip_elem_routeur, $mask_elem_pc)){
                    $mask_elem_routeur = $ElemRoutageRouteur['masque_destination'];
                    $result = pg_query($conn, "INSERT INTO elements (ip_destination, interface_relayage, masque_destination, MTU) VALUES ('$ip_elem_routeur', '$interface', '$mask_elem_routeur', $MTU_routeur )");
                    
                    $result = pg_query($conn, "SELECT * FROM elements WHERE ip_destination = '$ip_elem_routeur' AND interface_relayage = '$interface' AND masque_destination = '$mask_elem_routeur' AND MTU = $MTU_routeur");
                    $element = pg_fetch_assoc($result);
                    $id_element = $element['id_elements'];

                    $result = pg_query($conn, "INSERT INTO elem_pc (id_pc, id_elements) VALUES ('$id_pc', '$id_element' )");
                    
                    
                }


            }
        }

    }
}

/* fonction de vérifications des adresses IP avec la bonne numenclature */
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


// Front end avec champs : ID Réseau, ID Routeur, Interface
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connecter PC et Routeur</title>
    <link rel="stylesheet" href="../Css/creer_lien_c_c.css">
</head>
<body>
    <div class="banniere">
        <img src="../Image/logo.jpeg" alt="logo" class="logo">
        <p>Net-Simulate</p>
    </div>
    <hr>
  
    
    <h2> Sous réseaux </h2>
    <table>
        <tr>
            <th>ID Sous-réseau</th>
            <th>IP Sous-réseau</th>
            <th>Mask</th>
        </tr>
        <?php
        $result = pg_query($conn, "SELECT * FROM sous_réseau");
        $resultat = pg_fetch_all($result);
        foreach ($resultat as $sous_reseau) {
            echo "<tr>";
            echo "<td>" ."  |  ". $sous_reseau['id_sousréseau'] ."  |  ". "</td>";
            echo "<td>" ."  |  ". $sous_reseau['ip_sous_reseau'] ."  |  ". "</td>";
            echo "<td>" ."  |  ". $sous_reseau['mask'] ."  |  ". "</td>";
            echo "</tr>";
        }
        ?>
    </table>
    <h2>Réseaux </h2>
    <table>
        <tr>
            <th>ID Sous-réseau</th>
            <th>IP Sous-réseau</th>
            <th>Mask</th>
        </tr>
        <?php
        $result = pg_query($conn, "SELECT * FROM sous_réseau");
        $resultat = pg_fetch_all($result);
        foreach ($resultat as $sous_reseau) {
            echo "<tr>";
            echo "<td>" ."  |  ". $sous_reseau['id_sousréseau'] ."  |  ". "</td>";
            echo "<td>" ."  |  ". $sous_reseau['ip_sous_reseau'] ."  |  ". "</td>";
            echo "<td>" ."  |  ". $sous_reseau['mask'] ."  |  ". "</td>";
            echo "</tr>";
        }
        ?>
    </table>
    <h2>PC </h2>
    <table>
        <tr>
            <th>ID PC</th>
            <th>IP PC</th>
            <th>ID Sous-réseau</th>
       
        </tr>
        <?php
        $result = pg_query($conn, "SELECT * FROM Pc");
        $resultat = pg_fetch_all($result);
        foreach ($resultat as $Pc) {
            echo "<tr>";
            
            echo "<td>" ."  |  ". $Pc['id_pc'] ."  |  ". "</td>";
          
            echo ".<td>" ."  |  ". $Pc['ip_pc'] ."  |  ". "</td>";

            echo ".<td>" ."  |  ". $Pc['id_sousréseau'] ."  |  ". "</td>";
         

            echo "</tr>";
        }
        ?>
    </table>
    <h2>Routeur </h2>
    <table>
        <tr>
            <th>ID Routeur</th>
            <th>IP Routeur</th>
            <th>MTU</th>
        </tr>
        <?php
        $result = pg_query($conn, "SELECT * FROM Routeur");
        $resultat = pg_fetch_all($result);
        foreach ($resultat as $routeur) {
            echo "<tr>";
            echo "<td>" ."  |  ". $routeur['id_routeur'] ."  |  ". "</td>";
            echo "<td>" ."  |  ". $routeur['ip_routeur'] ."  |  ". "</td>";
            echo "<td>" ."  |  ". $routeur['mtu'] ."  |  ". "</td>";
            echo "</tr>";
        }
        ?>
    </table>

    

    <!-- Formulaire pour créer un lien -->
    <form method="post">
        <h2>Connecter un réseau à un routeur</h2>
        
        <label for="id_sous_reseau">ID Réseau :</label>
        <input type="text" id="id_reseau" name="id_reseau" required>

        <label for="id_routeur">ID Routeur :</label>
        <input type="text" id="id_routeur" name="id_routeur" required>

        <label for="interface">Interface :</label>
        <input type="text" id="interface" name="interface" required>
        
        <button type="submit" name="submit" class="submit-button">Créer Lien</button>
        
        <button class="button" onclick="retourAuReseau()">Annuler</button>
        <script>
            function retourAuReseau() {
                window.location.href = 'reseau.php?id=<?php echo $_SESSION['idReseau']; ?>';
                
            }
        </script>
        
    </form>
</body>
</html>
