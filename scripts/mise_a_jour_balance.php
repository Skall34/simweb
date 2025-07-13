<?php
/*
-------------------------------------------------------------
 Script : mise_a_jour_balance.php
 Emplacement : scripts/

 Description :
 Ce script met à jour la table BALANCE_COMMERCIALE avec les données financières actuelles.
 Il calcule la balance commerciale à partir de l'apport initial, des recettes et du coût des avions, puis met à jour ou crée la ligne correspondante.
 Toutes les opérations et erreurs sont loguées dans scripts/logs/mise_a_jour_balance.log via logMsg().

 Fonctionnement :
 1. Récupère la somme des recettes et des coûts des avions depuis FINANCES.
 2. Récupère l'apport initial depuis BALANCE_COMMERCIALE.
 3. Calcule la balance actuelle : apport_initial + recettes - cout_avions.
 4. Met à jour ou insère la ligne dans BALANCE_COMMERCIALE.
 5. Logue chaque étape et erreur dans le fichier log.

 Utilisation :
 - À lancer après chaque import ou opération financière pour garder la balance à jour.
 - Vérifier le log en cas d'anomalie ou d'échec d'opération.

 Auteur :
 - Automatisé avec GitHub Copilot
-------------------------------------------------------------*/

// Met à jour la table BALANCE_COMMERCIALE avec les données actuelles
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';

$logFile = __DIR__ . '/logs/mise_a_jour_balance.log';

try {
    // Récupérer la somme des recettes et des coûts des avions
    $stmtRecettes = $pdo->query("SELECT SUM(recettes) FROM FINANCES");
    $recettes = (float) $stmtRecettes->fetchColumn();
    $stmtCout = $pdo->query("SELECT SUM(traite_payee_cumulee) FROM FINANCES");
    $cout_avions = (float) $stmtCout->fetchColumn();

    // Récupérer l'apport initial (première ligne ou valeur par défaut)
    $apport_initial = 0.0;
    $stmtApport = $pdo->query("SELECT apport_initial FROM BALANCE_COMMERCIALE ORDER BY id ASC LIMIT 1");
    $apport_initial = (float) $stmtApport->fetchColumn();
    if ($apport_initial === false) $apport_initial = 1000;

    // Calculer la balance actuelle
    $balance_actuelle = $apport_initial + $recettes - $cout_avions;

    // Mettre à jour ou insérer la ligne
    $stmtCheck = $pdo->query("SELECT id FROM BALANCE_COMMERCIALE LIMIT 1");
    $row = $stmtCheck->fetch();
    if ($row) {
        $sqlUpdate = "UPDATE BALANCE_COMMERCIALE SET balance_actuelle = :balance, recettes = :recettes, cout_avions = :cout, date_maj = NOW() WHERE id = :id";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            'balance' => $balance_actuelle,
            'recettes' => $recettes,
            'cout' => $cout_avions,
            'id' => $row['id']
        ]);
        logMsg("Balance commerciale mise à jour : balance=$balance_actuelle, recettes=$recettes, cout_avions=$cout_avions", $logFile);
    } else {
        $sqlInsert = "INSERT INTO BALANCE_COMMERCIALE (balance_actuelle, recettes, cout_avions, apport_initial, date_maj) VALUES (:balance, :recettes, :cout, :apport, NOW())";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            'balance' => $balance_actuelle,
            'recettes' => $recettes,
            'cout' => $cout_avions,
            'apport' => $apport_initial
        ]);
        logMsg("Balance commerciale créée : balance=$balance_actuelle, recettes=$recettes, cout_avions=$cout_avions, apport_initial=$apport_initial", $logFile);
    }
} catch (PDOException $e) {
    logMsg("Erreur SQL : " . $e->getMessage(), $logFile);
}
