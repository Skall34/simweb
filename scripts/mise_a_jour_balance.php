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
    $recettes_finances = (float) $stmtRecettes->fetchColumn();
    logMsg("[BALANCE] Recettes totales FINANCES: $recettes_finances", $logFile);
    $stmtCout = $pdo->query("SELECT SUM(traite_payee_cumulee) FROM FINANCES");
    $cout_avions = (float) $stmtCout->fetchColumn();
    logMsg("[BALANCE] Coût total avions FINANCES: $cout_avions", $logFile);

    // Récupérer tous les champs nécessaires dans BALANCE_COMMERCIALE
    $stmtBalance = $pdo->query("SELECT id, apport_initial, recettes, recettes_ventes_appareils, cout_avions, assurance, paiement_salaires FROM BALANCE_COMMERCIALE ORDER BY id ASC LIMIT 1");
    $rowBalance = $stmtBalance->fetch(PDO::FETCH_ASSOC);
    if ($rowBalance) {
        $id_balance = $rowBalance['id'];
        $apport_initial = (float) $rowBalance['apport_initial'];
        $recettes_balance = (float) $rowBalance['recettes'];
        $recettes_ventes = (float) $rowBalance['recettes_ventes_appareils'];
        $cout_avions_balance = (float) $rowBalance['cout_avions'];
        $assurance = isset($rowBalance['assurance']) ? (float) $rowBalance['assurance'] : 0.0;
        $paiement_salaires = isset($rowBalance['paiement_salaires']) ? (float) $rowBalance['paiement_salaires'] : 0.0;
    } else {
        // Valeurs par défaut si la ligne n'existe pas
        $id_balance = null;
        $apport_initial = 1000;
        $recettes_balance = 0.0;
        $recettes_ventes = 0.0;
        $cout_avions_balance = 0.0;
        $assurance = 0.0;
        $paiement_salaires = 0.0;
    }
    logMsg("[BALANCE] Apport initial: $apport_initial", $logFile);
    logMsg("[BALANCE] Assurance: $assurance", $logFile);
    logMsg("[BALANCE] Paiement salaires: $paiement_salaires", $logFile);
    logMsg("[BALANCE] Recettes ventes appareils: $recettes_ventes", $logFile);

    // Vérification de la cohérence des recettes
    if (abs($recettes_balance - $recettes_finances) > 0.01) {
        logMsg("[ALERTE] Recettes BALANCE_COMMERCIALE ($recettes_balance) différentes de FINANCES ($recettes_finances)", $logFile);
        // Optionnel : mettre à jour recettes dans BALANCE_COMMERCIALE
        if ($id_balance) {
            $stmtUpdateRecettes = $pdo->prepare("UPDATE BALANCE_COMMERCIALE SET recettes = :recettes WHERE id = :id");
            $stmtUpdateRecettes->execute(['recettes' => $recettes_finances, 'id' => $id_balance]);
            logMsg("[CORRECTION] Recettes BALANCE_COMMERCIALE mises à jour à $recettes_finances", $logFile);
            $recettes_balance = $recettes_finances;
        }
    }

    // Calculer la balance actuelle selon la nouvelle formule
    $balance_actuelle = ($apport_initial + $recettes_balance + $recettes_ventes) - ($cout_avions + $assurance + $paiement_salaires);
    logMsg("[BALANCE] Calcul balance_actuelle = (apport_initial + recettes + recettes_ventes_appareils) - (cout_avion + assurance + paiement_salaires) = ($apport_initial + $recettes_balance + $recettes_ventes) - ($cout_avions + $assurance + $paiement_salaires) = $balance_actuelle", $logFile);

    // Mettre à jour ou insérer la ligne
    if ($id_balance) {
        $sqlUpdate = "UPDATE BALANCE_COMMERCIALE SET balance_actuelle = :balance, date_maj = NOW() WHERE id = :id";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            'balance' => $balance_actuelle,
            'id' => $id_balance
        ]);
        logMsg("Balance commerciale mise à jour : balance=$balance_actuelle", $logFile);
    } else {
        $sqlInsert = "INSERT INTO BALANCE_COMMERCIALE (balance_actuelle, recettes, recettes_ventes_appareils, cout_avions, apport_initial, assurance, paiement_salaires, date_maj) VALUES (:balance, :recettes, :ventes, :cout, :apport, :assurance, :salaires, NOW())";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            'balance' => $balance_actuelle,
            'recettes' => $recettes_finances,
            'ventes' => $recettes_ventes,
            'cout' => $cout_avions,
            'apport' => $apport_initial,
            'assurance' => $assurance,
            'salaires' => $paiement_salaires
        ]);
        logMsg("Balance commerciale créée : balance=$balance_actuelle", $logFile);
    }
} catch (PDOException $e) {
    logMsg("Erreur SQL : " . $e->getMessage(), $logFile);
}
