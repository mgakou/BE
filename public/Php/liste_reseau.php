<?php
session_start();

// Vérifiez que les variables de session nécessaires sont définies
if(isset($_SESSION['idProjet']) && isset($_SESSION['id_utilisateur'])) {
    $host = 'localhost';  // ou autre adresse IP ou nom d'hôte
    $dbname = 'BE';
    $username = 'postgres';
    $password = 'Niktwo.3111';

    // Créer une instance PDO
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Préparer la requête SQL avec une jointure
    $sql = "SELECT réseau.id_reseau, réseau.nom, réseau.mask_reseau, réseau.adresse_réseau 
            FROM réseau 
            JOIN Infrastructure 
            ON réseau.id_infrastructure = Infrastructure.id_infrastructure 
            WHERE réseau.id_infrastructure = ? AND Infrastructure.id_utilisateur = ?";
    $stmt = $pdo->prepare($sql);

    // Lier les paramètres et exécuter la requête
    $stmt->execute([$_SESSION['idProjet'], $_SESSION['id_utilisateur']]);

    // Récupérer tous les résultats
    $reseaux = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Vérifier si des réseaux ont été trouvés et les afficher avec des liens
    if (count($reseaux) > 0) {
        foreach ($reseaux as $reseau) {
            echo '<a href="../Php/reseau.php?id=' . htmlspecialchars($reseau['id_reseau']) . '">' . 
                 htmlspecialchars($reseau['nom']) . '</a><br>';
        }
    } else {
        echo "Aucun réseau trouvé.";
    }
} else {
    echo "Les ID de projet ou utilisateur ne sont pas définis dans la session.";
}
?>
