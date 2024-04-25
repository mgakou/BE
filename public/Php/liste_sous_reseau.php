<?php
session_start();

// Vérifier que les variables de session nécessaires sont définies
if(isset($_SESSION['idReseau']) && isset($_SESSION['id_utilisateur'])) {
    $host = 'localhost';  // ou autre adresse IP ou nom d'hôte
    $dbname = 'BE';
    $username = 'postgres';
    $password = 'Niktwo.3111';

    // Créer une instance PDO
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Préparer la requête SQL pour récupérer les sous-réseaux associés à un réseau spécifique
    $sql = "SELECT id_sousréseau, mask, IP_Sous_Reseau 
            FROM sous_réseau 
            WHERE id_reseau = ?";
    $stmt = $pdo->prepare($sql);

    // Lier le paramètre et exécuter la requête
    $stmt->execute([$_SESSION['idReseau']]);

    // Récupérer tous les résultats
    $sousReseaux = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Vérifier si des sous-réseaux ont été trouvés et les afficher avec des liens
    if (count($sousReseaux) > 0) {
        foreach ($sousReseaux as $sousReseau) {
            $idSousReseau = htmlspecialchars($sousReseau['id_sousréseau']);
            $mask = htmlspecialchars($sousReseau['mask']);
            $ipSousReseau = htmlspecialchars($sousReseau['ip_sous_reseau']);
        
            echo "<li><a href='../Php/sous_reseau.php?id=$idSousReseau'>Ip Sous-Réseau : $ipSousReseau et Mask : $mask </a></li>";
        }
    } else {
        echo "Aucun sous-réseau trouvé.";
    }
} else {
    echo "Les ID de réseau ou utilisateur ne sont pas définis dans la session.";
}
?>
