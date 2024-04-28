<?php

/* Connexion à la base de données */
session_start();

/* Vérification de la session */
if (!isset($_SESSION['id_utilisateur'])) {
    header("Location: connexion.html");
    exit;
}

/* Connexion à la base de données */
$host = 'localhost';
$dbname = 'BE';
$username = 'postgres';
$password = 'Niktwo.3111';

try {
    /* Connexion à la base de données avec creation de chaine de connexion et utilisation de PDO pour se connecter à la base de données */
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    
    $stmtPCs = $pdo->query("SELECT id_pc, IP_Pc FROM Pc");
    $pcs = $stmtPCs->fetchAll(PDO::FETCH_ASSOC);

    // Récupération de tous les Routeurs
    $stmtRouteurs = $pdo->query("SELECT id_routeur, IP_Routeur FROM Routeur");
    $routeurs = $stmtRouteurs->fetchAll(PDO::FETCH_ASSOC);


    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        //requete trouver id sous reseau du pc entré en post
        $stmt = $pdo->prepare("SELECT id_sousréseau FROM Pc WHERE id_pc = :id_pc");
        $stmt->bindParam(':id_pc', $_POST['id_pc'], PDO::PARAM_INT);
        $stmt->execute();
        $sousReseau = $stmt->fetch(PDO::FETCH_ASSOC);
        $idSousReseau = $sousReseau['id_sousréseau'];
        //stmt trouver id reseau
        $stmt = $pdo->prepare("SELECT id_reseau FROM sous_réseau WHERE id_sousréseau = :id_sousreseau");
        $stmt->bindParam(':id_sousreseau', $idSousReseau, PDO::PARAM_INT);
        $stmt->execute();
        $reseau = $stmt->fetch(PDO::FETCH_ASSOC);
        $idReseau = $reseau['id_reseau'];
        $interface = $_POST['interface'];

        // Insertion d'un nouvel élément de routage
        $pdo->beginTransaction();
        $insertElement = $pdo->prepare("INSERT INTO Elements (IP_destination, interface_relayage, masque_destination) VALUES (?, ?, ?)");
        $insertElement->execute([$idReseau, $interface, $mask]);
        $idElement = $pdo->lastInsertId();

        // Liaison de cet élément aux routeurs et PCs spécifiques
        $insertElemRouteur = $pdo->prepare("INSERT INTO elem_routeur (id_routeur, id_elements) VALUES (?, ?)");
        $insertElemRouteur->execute([$idReseau, $idElement]);

        $insertElemPc = $pdo->prepare("INSERT INTO elem_pc (id_pc, id_elements) VALUES (?, ?)");
        $insertElemPc->execute([$idSousReseau, $idElement]);

        // Récupération des tables de routage pour le routeur et le PC
        $routeurRoutage = $pdo->query("SELECT * FROM Elements WHERE id_elements IN (SELECT id_elements FROM elem_routeur WHERE id_routeur = $idReseau)");
        $pcRoutage = $pdo->query("SELECT * FROM Elements WHERE id_elements IN (SELECT id_elements FROM elem_pc WHERE id_pc = $idSousReseau)");

        foreach ($routeurRoutage as $elementRouteur) {
            foreach ($pcRoutage as $elementPc) {
                // Comparaison des réseaux, mise à jour si nécessaire
                if ($elementRouteur['masque_destination'] != $elementPc['masque_destination']) {
                    $updateMTU = $pdo->prepare("UPDATE Elements SET MTU = ? WHERE id_elements = ?");
                    $updateMTU->execute([$elementRouteur['MTU'], $elementPc['id_elements']]);
                }
            }
        }

        $pdo->commit();
        echo "<script>alert('Lien entre PC et routeur créé et tables de routage mises à jour avec succès!')</script>";
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>

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

    <form method="post">
        <h2>Connecter un PC à un routeur</h2>
        <label for="id_pc">PC :</label>
        <select id="id_pc" name="id_pc" required>
            <?php foreach ($pcs as $pc) {
                echo "<option value='{$pc['id_pc']}'>{$pc['IP_Pc']}</option>";
            } ?>
        </select>
        
        <label for="id_routeur">Routeur :</label>
        <select id="id_routeur" name="id_routeur" required>
            <?php foreach ($routeurs as $routeur) {
                echo "<option value='{$routeur['id_routeur']}'>{$routeur['IP_Routeur']}</option>";
            } ?>
        </select>
        
        <label for="interface">Interface :</label>
        <input type="text" id="interface" name="interface" required>

        <button type="submit" class="submit-button">Créer Lien</button>
    </form>
</body>
</html>
