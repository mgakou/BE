<?php

// Connexion à la base de données
$host = 'localhost';
$dbname = 'BE';
$username = 'postgres';
$password = '141592';

$conn = pg_connect("host=$host dbname=$dbname user=$username password=$password");

// Vérification de la connexion
if (!$conn) {
    echo "Erreur de connexion à la base de données.\n";
    exit;
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
simulerRoutage('192.6.1.2', '167.2.1.1', 10, 1, 0, 0, 2500, 1);

// Fermeture de la connexion à la base de données PostgreSQL
pg_close($conn);

?>