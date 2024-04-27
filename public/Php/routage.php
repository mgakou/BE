<?php
// Connexion à la base de données
$host = 'localhost';
$dbname = 'BE';
$username = 'postgres';
$password = 'Niktwo.3111';

$conn = pg_connect("host=$host dbname=$dbname user=$username password=$password");

// Vérification de la connexion
if (!$conn) {
    echo "Erreur de connexion à la base de données.\n";
    exit;
}

// Donner au bouton les svaleurs correspondantes

if (isset($_POST['submit'])) {
    $adresseIPSource = $_POST['IP_source'];
    $adresseIPDestination = $_POST['IP_destination'];
    $TTL = $_POST['TTL'];
    $DF = $_POST['DF'];
    $MF = $_POST['MF'];
    $taille = $_POST['taille'];
    $id_pc = $_POST['id_pc'];
    simulerRoutage($adresseIPSource, $adresseIPDestination, $TTL, $DF, $MF, $taille, $id_pc);
}

// Fonction pour simuler le routage d'un paquet
function simulerRoutage($adresseIPSource, $adresseIPDestination, $TTL, $DF, $MF, $taille, $id_pc) {
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
        'taille' => $taille, // Taille arbitraire du paquet
        'id_pc'=> $id_pc // ID PC source
    );

    //Routage dans un même réseau

    //Requete pour avoir le reseau du pc envoyant le paquet
    $result = pg_query($conn, "SELECT R.*
    FROM réseau R
    INNER JOIN sous_réseau SR ON R.id_reseau = SR.id_reseau
    INNER JOIN Pc P ON SR.id_sousréseau = P.id_sousréseau
    WHERE P.id_pc = $id_pc;");
    $resultat = pg_fetch_assoc($result);
    // Vérification de l'éxecution de la requête
    if (!$resultat) {
        echo "Erreur lors de l'exécution de la requête (1.1).\n";
        exit;
    }
    $reseau = $resultat;

    #Verification que les deux adresses sont dans le même réseau
    if(memeReseau($adresseIPDestination, $adresseIPSource, $reseau['mask_reseau'])){
        #Requête pour avoir le pc de destination
        $result = pg_query($conn, "SELECT * FROM Pc WHERE ip_pc = '$adresseIPDestination';");
        $resultat = pg_fetch_assoc($result);
        // Vérification de l'éxecution de la requête
        if (!$resultat) {
            echo "Erreur lors de l'exécution de la requête (1.2).\n";
            exit;
        }
        $pc_dest = $resultat;
        //Ajout du Pc d'origine dans la liste des appareils parcourus
        $appareilsParcourus[] = 'PC'. $id_pc;
        //Ajout du Pc de destination dans la liste des appareils parcourus
        $appareilsParcourus[] = 'PC'. $pc_dest['id_pc'];
        echo "Appareils parcourus : " . implode(', ', $appareilsParcourus) . "\n";
        return;
    }


    //Pc a partir duquel le paquet à été créé
    $result = pg_query($conn, "SELECT * FROM Pc WHERE id_pc = $id_pc");
    $resultat = pg_fetch_assoc($result);

    // Vérification de l'éxecution de la requête
    if (!$resultat) {
        echo "Erreur lors de l'exécution de la requête (1).\n";
        exit;
    }

    //Initialisation des variables utiles pour le routage
    $equipement = $resultat;
    $adresseEquipement = $equipement['ip_pc'];
    $id_equipement = $equipement['id_pc'];
    $isPc = 0;
    $result = pg_query($conn, "SELECT * FROM Elements WHERE id_elements IN 
    (SELECT id_elements FROM elem_pc WHERE id_pc = $id_equipement);");
    $resultat = pg_fetch_all($result);
    // Vérification de l'éxecution de la requête
    if (!$resultat) {
        echo "Erreur lors de l'exécution de la requête (2).\n";
        exit;
    }
    $tableRoutage = $resultat;
    

    //Ajout du Pc d'origine dans la liste des appareils parcourus
    $appareilsParcourus[] = 'PC'. $id_equipement;

    // Boucle de routage du paquet
    while ($paquet['TTL'] > 0 and $paquet['adresse_IP_destination'] != $adresseEquipement) {
        //Initialisation variables 
        $prochaineRoute = 1;
        $routeDefault = null;
        // Parcourir les éléments liés à l'appareil actuel
        foreach ($tableRoutage as $element) {
            //Attribution de la route par defaut
            if (memeReseau("127.0.0.0", $element['ip_destination'], $element['masque_destination'])){
                $routeDefault = $element;
            }
            //Attribution de la prochaine route
            if (memeReseau($paquet['adresse_IP_destination'], $element['ip_destination'], $element['masque_destination'])){
                $prochaineRoute = $element;
            }
        }

        //Attribution des données de la route par défaut
        if($prochaineRoute == 1){
            $prochaineRoute = $routeDefault;
            echo "Appareils parcourus : " . implode(', ', $appareilsParcourus) . "\n";
            // Arrêter la simulation
            return;
        }

        //Fragmentation

        // Verifier que la taille du paquet dépasse celle de la MTU et qu'il peut être fragmenter
        if (!$paquet['DF'] and $paquet['taille']> $prochaineRoute['mtu']) {
            // Afficher en combien de paquets il est fragmenté et changé sa taille
            echo "Fragmentation du paquet en " . nombrePaquetsFragmentation($paquet['taille'], $prochaineRoute['mtu'] . " paquets");
            $paquet['taille'] = $prochaineRoute['mtu'];
        // Verifier la taille du paquet dépasse celle de la MTU et qu'il ne peut pas être fragmenter
        } else if($paquet['DF'] and $paquet['taille']> $prochaineRoute['mtu']){
            // Afficher les appareils parcourus et un message d'erreur
            echo "Erreur : Paquet trop grand pour la MTU de l'élément et ne peut pas être fragmenté.\n";
            echo "Appareils parcourus : " . implode(', ', $appareilsParcourus) . "\n";
            // Arrêter la simulation
            return;
        }

        // Réduire le TTL du paquet
        $paquet['TTL'] = $paquet['TTL']-1;

        // Passer au prochain appareil
    
        $interfacePrAppareil = intval($prochaineRoute["interface_relayage"]);
        
        // Requête pour avoir tous les routeurs connectés à un autre routeur sur une interface donné
        $result = pg_query($conn, "SELECT * FROM Routeur WHERE 
        (id_routeur IN (SELECT id_routeur FROM connecter_routeur WHERE interface_routeur = $interfacePrAppareil) 
        OR id_routeur IN (SELECT id_routeur_1 FROM connecter_routeur WHERE interface_routeur = $interfacePrAppareil)) 
        AND id_routeur != $id_equipement;");
        $resultat = pg_fetch_assoc($result);
        // Vérification de l'éxecution de la requête
        if (!$resultat) {
            // Verification du type de l'appareil (Pc ou Routeur)
            if($isPc){
                // Requête pour avoir tous les routeurs connectés à un pc sur une interface donné
                $result = pg_query($conn, "SELECT * FROM Routeur WHERE id_routeur IN 
                ( SELECT id_routeur FROM connecter_pc WHERE interface_routeur_pc = $interfacePrAppareil);");
                $resultat = pg_fetch_assoc($result);
                // Vérification de l'éxecution de la requête
                if(!$resultat){
                    echo "Erreur lors de l'exécution de la requête (3).\n";
                    exit;
                } else {
                    //Changement de la variable car l'équipement est maintenant un routeur
                    $isPc = 0;
                }
            }
            else{
                // Requête pour avoir tous les Pcs connectés à un routeur sur une interface donné
                $result = pg_query($conn, "SELECT * FROM Pc WHERE id_pc IN 
                ( SELECT id_pc FROM connecter_pc WHERE interface_routeur_pc = $interfacePrAppareil);");
                $resultat = pg_fetch_assoc($result);
                // Vérification de l'éxecution de la requête
                if(!$resultat){
                    echo "Erreur lors de l'exécution de la requête (4).\n";
                    exit;
                } else {
                    //Changement de la variable car l'équipement est maintenant un Pc
                    $isPc = 1;
                }

            } 
        } else {
            //Changement de la variable car l'équipement est maintenant un routeur
            $isPc = 0;
        }
         
        // Verification du type de l'appareil (Pc ou Routeur)
        if($isPc) {
            //Mise à jour des variables liés à l'equipement
            $adresseEquipement = $equipement['ip_pc'];
            $id_equipement = $equipement['id_pc'];
            $result = pg_query($conn, "SELECT * FROM Elements WHERE id_elements IN 
            (SELECT id_elements FROM elem_pc WHERE id_pc = $id_equipement);");
            $resultat = pg_fetch_all($result);
            //Ajout de l'équipement dans la liste des appareils parcourus
            $appareilsParcourus[] = 'PC'. $id_equipement;
        } else {    
            //Mise à jour des variables liés à l'equipement
            $adresseEquipement = $equipement['mac'];
            $id_equipement = $equipement['id_routeur'];
            $result = pg_query($conn, "SELECT * FROM Elements WHERE id_elements IN 
            (SELECT id_elements FROM elem_routeur WHERE id_routeur = $id_equipement);");
            $resultat = pg_fetch_all($result);
            //Ajout de l'équipement dans la liste des appareils parcourus
            $appareilsParcourus[] = 'Routeur'. $id_equipement;
        }

        // Vérification du résultat
        if(!$resultat){
            echo "Erreur lors de l'exécution de la requête (5).\n";
            exit;
        }
        $tableRoutage = $resultat;
      }
    

    echo "Appareils parcourus : " . implode(', ', $appareilsParcourus) . "\n";

}

function AfficherTableau($tableau) {
    echo "<table border='1'>";
    echo "<tr>";
    foreach ($tableau[0] as $cle => $valeur) {
        echo "<th>$cle</th>";
    }
    echo "</tr>";
    foreach ($tableau as $ligne) {
        echo "<tr>";
        foreach ($ligne as $valeur) {
            echo "<td>$valeur</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
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



// Fermeture de la connexion à la base de données PostgreSQL
pg_close($conn);
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
    
    
    <h2>Connection routeur - pc</h2>
    

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
