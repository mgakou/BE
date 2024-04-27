<?php

session_start();
// Connexion à la base de données
require_once('connecter_bd.php');

$conn = pg_connect("host=$host dbname=$dbname user=$username password=$password");

// Vérification de la connexion
if (!$conn) {
    echo "Erreur de connexion à la base de données.\n";
    exit;
}

if (isset($_POST['submit'])) {
    $adresseIPSource = $_POST['IP_source'];
    $adresseIPDestination = $_POST['IP_destination'];
    $TTL = $_POST['TTL'];
    $DF = $_POST['DF'];
    $MF = $_POST['MF'];
    $deplacement = $_POST['deplacement'];
    $taille = $_POST['taille'];
    $id_pc = $_POST['id_pc'];

    simulerRoutage($adresseIPSource, $adresseIPDestination, $TTL, $DF, $MF, $deplacement, $taille, $id_pc);
}



// Fonction pour simuler le routage d'un paquet
function simulerRoutage($adresseIPSource, $adresseIPDestination, $TTL, $DF, $MF, $deplacement, $taille, $id_pc) {
    global $conn;

    // Liste des appareils par lesquels le paquet est passé
    $appareilsParcourus = array();
    // Paquet à envoyer
    $paquet = array(
        'adresse_IP_source' => $adresseIPSource,
        'adresse_IP_destination' => $adresseIPDestination,
        'TTL' => $TTL,  // Valeur arbitraire pour le TTL
        'DF' => $DF, // Fragmentation désactivée
        'MF' => $MF, // Fragmentation non utilisée
        'offset' => $deplacement, // Valeur arbitraire pour le déplacement
        'taille' => $taille, // Taille arbitraire du paquet
        'id_pc'=> $id_pc // ID PC source
    );

    $result = pg_query($conn, "SELECT * FROM Pc WHERE id_pc = $id_pc");

    // Vérification du résultat
    if (!$result) {
        echo "Erreur lors de l'exécution de la requête.\n";
        exit;
    }

    $equipement = pg_fetch_assoc($result);
    $adresseEquipement = $equipement['ip_pc'];
    $id_equipement = $equipement['id_pc'];
    $appareilsParcourus[] = 'PC'. $id_equipement;
    $result = pg_query($conn, "SELECT * FROM Elements WHERE id_elements IN (SELECT id_elements FROM elem_pc WHERE id_pc = $id_equipement);");
    // Vérification du résultat
    if (!$result) {
        echo "Erreur lors de l'exécution de la requête.\n";
        exit;
    }
    $tableRoutage = pg_fetch_all($result);
    $isPc = 0;
    // Boucle de routage du paquet
    while ($paquet['TTL'] > 0 and $paquet['adresse_IP_destination'] != $adresseEquipement) {
        // Parcourir les éléments liés à l'appareil actuel
        $prochainAppareil = 1;
        $routeDefault = null;
        foreach ($tableRoutage as $element) {
            if (memeReseau("127.0.0.0", $element['ip_destination'], $element['masque_destination'])){
                $routeDefault = $element;
            }
            if (memeReseau($paquet['adresse_IP_destination'], $element['ip_destination'], $element['masque_destination'])){
                $prochainAppareil = $element;
            }
        }
        if($prochainAppareil == 1){
            $prochainAppareil = $routeDefault;
        }

        // Vérifier si la fragmentation est autorisée
        if (!$paquet['DF'] and $paquet['taille']> $prochainAppareil['mtu']) {
            echo "Fragmentation du paquet...\n" . nombrePaquetsFragmentation($paquet['taille'], $prochainAppareil['mtu']);
            $paquet['taille'] = $prochainAppareil['mtu'];
        } else if($paquet['DF'] and $paquet['taille']> $prochainAppareil['mtu']){
            // Afficher les appareils parcourus et un message d'erreur
            echo "Erreur : Paquet trop grand pour la MTU de l'élément et ne peut pas être fragmenté.\n";
            echo "Appareils parcourus : " . implode(', ', $appareilsParcourus) . "\n";

            // Arrêter la simulation
            return;
        }

        // Réduire le TTL du paquet
        $paquet['TTL'] = $paquet['TTL']-1;

        // Passer au prochain appareil
        $interfacePrAppareil = intval($prochainAppareil["interface_relayage"]);
        
        $result = pg_query($conn, "SELECT * FROM Routeur WHERE (id_routeur IN (SELECT id_routeur FROM connecter_routeur WHERE interface_routeur = $interfacePrAppareil) OR id_routeur IN (SELECT id_routeur_1 FROM connecter_routeur WHERE interface_routeur = $interfacePrAppareil)) AND id_routeur != $id_equipement;");
        // Vérification du résultat
        $equipement = pg_fetch_assoc($result);
        if (!$equipement) {
            if($isPc){
                $result = pg_query($conn, "SELECT * FROM Routeur WHERE id_routeur IN ( SELECT id_routeur FROM connecter_pc WHERE interface_routeur_pc = $interfacePrAppareil);");
                $equipement = pg_fetch_assoc($result);
                if(!$equipement){
                    echo "Erreur lors de l'exécution de la requête.\n";
                    exit;
                } else {
                    $isPc = 0;
                }
            }
            else{
                $result = pg_query($conn, "SELECT * FROM Pc WHERE id_pc IN ( SELECT id_pc FROM connecter_pc WHERE interface_routeur_pc = $interfacePrAppareil);");
                $equipement = pg_fetch_assoc($result);
                if(!$equipement){
                    echo "Erreur lors de l'exécution de la requête.\n";
                    exit;
                } else {
                    $isPc = 1;
                }

            } 
        } else {
            $isPc = 0;
        }
         
        if($isPc) {
            $adresseEquipement = $equipement['ip_pc'];
            $id_equipement = $equipement['id_pc'];
            $appareilsParcourus[] = 'PC'. $id_equipement;
            $result = pg_query($conn, "SELECT * FROM Elements WHERE id_elements IN (SELECT id_elements FROM elem_pc WHERE id_pc = $id_equipement);");
        } else {    
            $adresseEquipement = $equipement['mac'];
            $id_equipement = $equipement['id_routeur'];
            $appareilsParcourus[] = 'Routeur'. $id_equipement;
            $result = pg_query($conn, "SELECT * FROM Elements WHERE id_elements IN (SELECT id_elements FROM elem_routeur WHERE id_routeur = $id_equipement);");
        }

        // Vérification du résultat
        if (!$result) {
            if(!$result){
                echo "Erreur lors de l'exécution de la requête.\n";
                exit;
            } 
        } 
        $tableRoutage = pg_fetch_all($result);
      }
    // Afficher les appareils parcourus
    echo "Appareils parcourus : " . implode(', ', $appareilsParcourus) . "\n";
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

function nombrePaquetsFragmentation($taillePaquet, $MTU) {
    // Taille de l'entête
    $tailleEntete = 20; // en octets

    // Taille maximale des données d'un paquet
    $tailleMaxDonnees = $MTU - $tailleEntete;

    // Nombre de paquets nécessaires
    $nombrePaquets = ceil($taillePaquet / $tailleMaxDonnees);

    return $nombrePaquets;
}

// Exemple d'utilisation de la fonction simulerRoutage
//simulerRoutage('192.6.1.2', '167.2.1.1', 10, 1, 0, 0, 2500, 1);

// Fermeture de la connexion à la base de données PostgreSQL


?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Simulation routage</title>
    <link rel="stylesheet" href="../Css/creer_lien_c_c.css">
</head>
<body>
    <div class="banniere">
        <img src="../Image/logo.jpeg" alt="logo" class="logo">
        <p>Net-Simulate</p>
    </div>
    <hr>

    <h2>Connection routeur - routeur</h2>
    
    <table>
        <tr>
            <th>ID Routeur 1</th>
            <th>ID Routeur 2</th>
            <th>Mask</th>
        </tr>
        <?php
        $result = pg_query($conn, "SELECT * FROM connecter_routeur");
        $resultat = pg_fetch_all($result);
        foreach ($resultat as $routeur) {
            echo "<tr>";
            echo "<td>" ."  |  ". $routeur['id_routeur_1'] ."  |  ". "</td>";
            echo "<td>" ."  |  ". $routeur['id_routeur_2'] ."  |  ". "</td>";
            echo "<td>" ."  |  ". $routeur['mask'] ."  |  ". "</td>";
            echo "</tr>";
        }
        ?>
    </table>
    <h2>Connection routeur - pc</h2>
    <table>
        <tr>
            <th>ID Routeur</th>
            <th>ID PC</th>
            <th>interface_routeur_pc</th>
        </tr>
        <?php
        $result = pg_query($conn, "SELECT * FROM connecter_pc");
        $resultat = pg_fetch_all($result);
        foreach ($resultat as $routeur) {
            echo "<tr>";
            echo "<td>" ."  |  ". $routeur['id_routeur'] ."  |  ". "</td>";
            echo "<td>" ."  |  ". $routeur['id_pc'] ."  |  ". "</td>";
            echo "<td>" ."  |  ". $routeur['interface_routeur_pc'] ."  |  ". "</td>";
            echo "</tr>";
        }
        ?>
    </table>

    <form method="post">
        <h2>Simuler routage</h2>
        
        <label for="IP_source">IP Source :</label>
        <input type="text" id="IP_source" name="IP_source" required>

        <label for="IP_destination">IP Destination :</label>
        <input type="text" id="IP_destination" name="IP_destination" required>

        <label for="TTL">TTL:</label>
        <input type="text" id="TTL" name="TTL" required>

        <label for="DF">DF:</label>
        <input type="text" id="DF" name="DF" required>

        <label for="MF">MF:</label>
        <input type="text" id="MF" name="MF" required>

        <label for="deplacement">Déplacement:</label>
        <input type="text" id="deplacement" name="deplacement" required>

        <label for="taille">Taille:</label>
        <input type="text" id="taille" name="taille" required>

        <label for="id_pc">ID PC:</label>
        <input type="text" id="id_pc" name="id_pc" required>


        <button type="submit" name="submit" class="submit-button">Simuler routage</button>
        
        <button class="button" onclick="retourAuProjet()">Annuler</button>
<script>
            function retourAuProjet() {
                window.location.href = 'projet.php?id=<?php echo $_SESSION['idProjet']; ?>';
                
            }
        </script>
        
    </form>
</body>
</html>
