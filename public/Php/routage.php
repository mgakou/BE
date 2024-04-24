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

// Fonction pour simuler le routage d'un paquet
function simulerRoutage($adresseIPSource, $adresseIPDestination, $TTL, $DF, $MF, $deplacement, $taille, $id_pc) {
    global $conn;

    // Liste des appareils par lesquels le paquet est passé
    $appareilsParcourus = array();

    // Paquet à envoyer
    $paquet = array(
        'adresse_IP_source' => $adresseIPSource,
        'adresse_IP_destination' => $adresseIPDestination,
        'TTL' => $TTL,
        'DF' => $DF,
        'MF' => $MF,
        'offset' => $deplacement,
        'taille' => $taille,
        'id_pc' => $id_pc
    );

    // Récupérer les informations initiales de l'appareil
    $result = pg_query($conn, "SELECT * FROM Pc WHERE id_pc = $id_pc");
    if (!$result) {
        echo "Erreur lors de l'exécution de la requête.\n";
        exit;
    }
    $equipement = pg_fetch_assoc($result);
    $adresseEquipement = $equipement['ip'];
    $id_equipement = $equipement['id_pc'];
    $appareilsParcourus[] = 'PC' . $id_equipement;

    // Récupérer la table de routage associée à l'équipement
    $result = pg_query($conn, "SELECT * FROM Elements WHERE id_elements IN (SELECT id_elements FROM elem_pc WHERE id_pc = $id_equipement)");
    if (!$result) {
        echo "Erreur lors de l'exécution de la requête.\n";
        exit;
    }
    $tableRoutage = pg_fetch_all($result);

    // Boucle de routage du paquet
    while ($paquet['TTL'] > 0 && $paquet['adresse_IP_destination'] !== $adresseEquipement) {
        $prochainAppareil = null;
        $routeDefault = null;

        // Parcourir les éléments de routage pour trouver la prochaine route
        foreach ($tableRoutage as $element) {
            if (memeReseau($adresseEquipement, $element['ip_destination'], $element['masque_destination'])) {
                $prochainAppareil = $element;
                break;
            } elseif (memeReseau("0.0.0.0", $element['ip_destination'], $element['masque_destination'])) {
                $routeDefault = $element; // Utilisation d'une route par défaut
            }
        }

        if (!$prochainAppareil && $routeDefault) {
            $prochainAppareil = $routeDefault;
        }

        // Vérifier si un appareil suivant a été trouvé
        if (!$prochainAppareil) {
            echo "Erreur : Aucun prochain appareil trouvé, arrêt de la simulation.\n";
            break;
        }

        // Gérer la fragmentation du paquet si nécessaire
        if ($paquet['taille'] > $prochainAppareil['mtu'] && !$paquet['DF']) {
            echo "Fragmentation du paquet...\n";
            $nombrePaquets = nombrePaquetsFragmentation($taille, $prochainAppareil['mtu']);
            echo "Nombre de fragments : $nombrePaquets\n";
            $paquet['taille'] = $prochainAppareil['mtu'] - 20; // Mise à jour de la taille du paquet pour la fragmentation
        } elseif ($paquet['DF'] && $paquet['taille'] > $prochainAppareil['mtu']) {
            echo "Erreur : Paquet trop grand pour la MTU de l'élément et ne peut pas être fragmenté.\n";
            break;
        }

        // Mise à jour du TTL et passage au prochain appareil
        $paquet['TTL']--;

        // Mise à jour de l'équipement actuel en tant que prochain appareil
        $adresseEquipement = $prochainAppareil['ip_destination']; // Mise à jour de l'adresse IP de l'équipement
        $appareilsParcourus[] = 'Élément' . $prochainAppareil['id_elements'];
    }

    // Afficher les appareils parcourus
    echo "Appareils parcourus : " . implode(', ', $appareilsParcourus) . "\n";
}

// Fonctions auxiliaires
function memeReseau($adresse1, $adresse2, $masque) {
    $adresse1Int = ip2long($adresse1);
    $adresse2Int = ip2long($adresse2);
    $masqueInt = ip2long($masque);

    $reseau1 = $adresse1Int & $masqueInt;
    $reseau2 = $adresse2Int & $masqueInt;

    return $reseau1 === $reseau2;
}

function nombrePaquetsFragmentation($taillePaquet, $MTU) {
    $tailleEntete = 20; // Taille de l'entête IP en octets
    $tailleMaxDonnees = $MTU - $tailleEntete;
    return ceil($taillePaquet / $tailleMaxDonnees);
}

// Exemple d'utilisation de la fonction simulerRoutage
simulerRoutage('192.6.1.2', '167.2.1.1', 10, 0, 0, 0, 1000, 1);

// Fermeture de la connexion à la base de données
pg_close($conn);



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
simulerRoutage('192.6.1.2', '167.2.1.1', 10, 0, 0, 0, 1000, 1);

// Fermeture de la connexion à la base de données PostgreSQL
pg_close($dbconn);

?>