<?php
// Paramètres de connexion à la base de données
$host = 'localhost';
$dbname = 'BE';
$username = 'postgres';
$password = 'Niktwo.3111';

// Création de la chaîne de connexion
$connectionString = "host=$host dbname=$dbname user=$username password=$password";

// Tentative de connexion à la base de données PostgreSQL
$conn = pg_connect($connectionString);

// Vérification de la connexion
if (!$conn) {
    // Si la connexion échoue, afficher l'erreur
    echo "Erreur de connexion à la base de données: " . pg_last_error();
    exit;
}

// Si la connexion réussit, afficher un message de succès
echo "Connexion à la base de données réussie !";




function connecter_reseau($id_sous_reseau, $id_routeur, $interface){
    global $conn;

    $result = pg_query($conn, "SELECT * FROM sous_réseau WHERE id_sousréseau = $id_sous_reseau");
    $resultat = pg_fetch_assoc($result);
    if(!$resultat){
        echo "Erreur1";
        exit();
    }

    $sous_reseau = $resultat;
    $ip_sous_reseau = $sous_reseau["ip_sous_reseau"];
    $mask_sous_reseau = $sous_reseau["mask"];
    
    $result = pg_query($conn, "SELECT * FROM réseau
    WHERE id_reseau IN (
        SELECT id_reseau FROM sous_réseau
        WHERE id_sousréseau = $id_sous_reseau

    )");

    $resultat = pg_fetch_assoc($result);
    if(!$resultat){
        echo "Erreur MTU";
        exit();
    }

    $MTU_sous_reseau = $resultat['mtu'];
    $result = pg_query($conn, "SELECT * FROM routeur WHERE id_routeur = $id_routeur");
    $resultat = pg_fetch_assoc($result);
    if(!$resultat){
        echo "Erreur2";
        exit();
    }

    $routeur = $resultat;
    $MTU_routeur = $routeur['mtu'];

    $result = pg_query($conn, "INSERT INTO elements (ip_destination, interface_relayage, masque_destination, mtu) VALUES ('$ip_sous_reseau', '$interface', '$mask_sous_reseau', $MTU_sous_reseau)");
    

    $result = pg_query($conn, "SELECT * FROM elements WHERE ip_destination = '$ip_sous_reseau' AND interface_relayage = '$interface' AND masque_destination = '$mask_sous_reseau' AND MTU = $MTU_sous_reseau");
    $element = pg_fetch_assoc($result);
    $id_element = $element['id_elements'];

    $result = pg_query($conn, "INSERT INTO elem_routeur (id_routeur, id_elements) VALUES ('$id_routeur', '$id_element' )");
    $resultat = pg_fetch_all($result);

    $result = pg_query($conn, "SELECT Pc.* FROM Pc
    JOIN sous_réseau ON Pc.id_sousréseau = sous_réseau.id_sousréseau
    WHERE sous_réseau.id_sousréseau = $id_sous_reseau;");
    $resultat= pg_fetch_all($result);
    if(empty($resultat)){
        echo "Erreur3";
        exit();
    }
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

connecter_reseau(2,1,10);
?>