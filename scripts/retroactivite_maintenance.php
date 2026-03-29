<?php
/*
-------------------------------------------------------------
 Script : retroactivite_maintenance.php
 Emplacement : scripts/

 Description :
 Script ONE-SHOT à exécuter après la migration add_maintenance_cost.sql.
 
 Étape 1 : Initialise automatiquement cout_maintenance dans FLEET_TYPE
            pour les types qui n'en ont pas encore (= 0).
            Formule : 2% du prix d'achat (cout_appareil).
 
 Étape 2 : Calcule rétroactivement les coûts de maintenance pour chaque
            appareil actif ayant un nb_maintenance > 0, et insère les
            dépenses correspondantes dans finances_depenses.

 Logique rétroactivité :
 - Pour chaque appareil actif avec nb_maintenance > 0 :
   coût rétroactif = nb_maintenance × cout_maintenance du type
 - Enregistré comme une seule dépense de type 'maintenance_retro'
 - Met à jour la balance commerciale

 ⚠ NE PAS EXÉCUTER PLUSIEURS FOIS — vérification intégrée.

 Utilisation :
   php scripts/retroactivite_maintenance.php
-------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../includes/fonctions_financieres.php';

$logFile = __DIR__ . '/logs/retroactivite_maintenance.log';

// Taux d'initialisation : 2% du cout_appareil
$taux_init = 0.02;

date_default_timezone_set('Europe/Paris');

echo "=== Rétroactivité coûts de maintenance ===\n";
logMsg("--- Début rétroactivité maintenance ---", $logFile);

// Sécurité : vérifier qu'on n'a pas déjà exécuté ce script
$deja = $pdo->query("SELECT COUNT(*) FROM finances_depenses WHERE type = 'maintenance_retro'")->fetchColumn();
if ($deja > 0) {
    $msg = "ABANDON : Des écritures 'maintenance_retro' existent déjà ($deja trouvées). Ce script a déjà été exécuté.";
    echo "$msg\n";
    logMsg($msg, $logFile);
    exit(0);
}

try {
    // =============================================
    // ÉTAPE 1 : Initialiser cout_maintenance si = 0
    // =============================================
    echo "\n--- Étape 1 : Initialisation cout_maintenance (2% du prix d'achat) ---\n";
    logMsg("--- Étape 1 : Initialisation cout_maintenance ---", $logFile);

    $types_a_init = $pdo->query("
        SELECT id, fleet_type, cout_appareil
        FROM FLEET_TYPE
        WHERE cout_maintenance = 0 AND cout_appareil > 0
        ORDER BY fleet_type
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($types_a_init)) {
        echo "Tous les types ont déjà un cout_maintenance renseigné.\n";
        logMsg("Tous les types ont déjà un cout_maintenance renseigné.", $logFile);
    } else {
        $stmtUpdate = $pdo->prepare("UPDATE FLEET_TYPE SET cout_maintenance = :cout WHERE id = :id");
        foreach ($types_a_init as $ft) {
            $cout_calc = round(floatval($ft['cout_appareil']) * $taux_init, 2);
            $stmtUpdate->execute(['cout' => $cout_calc, 'id' => $ft['id']]);
            $detail = $ft['fleet_type'] . " : " . number_format($ft['cout_appareil'], 0, '', ' ') . " € × " . ($taux_init * 100) . "% = " . number_format($cout_calc, 2, ',', ' ') . " €";
            echo " - $detail\n";
            logMsg("Init : $detail", $logFile);
        }
        echo count($types_a_init) . " type(s) initialisé(s).\n";
        logMsg(count($types_a_init) . " type(s) initialisé(s).", $logFile);
    }

    // =============================================
    // ÉTAPE 2 : Rétroactivité des coûts
    // =============================================
    echo "\n--- Étape 2 : Calcul rétroactif des coûts de maintenance ---\n";
    logMsg("--- Étape 2 : Rétroactivité ---", $logFile);

    // Récupérer tous les appareils actifs avec nb_maintenance > 0
    $stmt = $pdo->query("
        SELECT f.id, f.immat, f.nb_maintenance,
               ft.cout_maintenance, ft.fleet_type AS type_nom
        FROM FLOTTE f
        JOIN FLEET_TYPE ft ON f.fleet_type = ft.id
        WHERE f.Actif = 1
          AND f.nb_maintenance > 0
          AND ft.cout_maintenance > 0
        ORDER BY f.immat
    ");
    $appareils = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($appareils)) {
        $msg = "Aucun appareil éligible (nb_maintenance > 0 ET cout_maintenance > 0). Rien à faire.";
        echo "$msg\n";
        logMsg($msg, $logFile);
        exit(0);
    }

    $cout_total = 0;
    $count = 0;
    $details = [];

    foreach ($appareils as $avion) {
        $id = $avion['id'];
        $immat = $avion['immat'];
        $nb = (int)$avion['nb_maintenance'];
        $cout_unit = floatval($avion['cout_maintenance']);
        $type_nom = $avion['type_nom'];
        $cout_retro = round($nb * $cout_unit, 2);

        $commentaire = "Rétroactivité maintenance — $immat ($type_nom) — $nb maintenances × " . number_format($cout_unit, 2, ',', ' ') . " €";
        mettreAJourDepenses($cout_retro, $id, $immat, 'SYSTEM', 'maintenance_retro', $commentaire);

        $cout_total += $cout_retro;
        $count++;
        $detail = "$immat ($type_nom) : $nb × " . number_format($cout_unit, 2, ',', ' ') . " € = " . number_format($cout_retro, 2, ',', ' ') . " €";
        $details[] = $detail;
        logMsg($detail, $logFile);
        echo " - $detail\n";
    }

    $cout_total_fmt = number_format($cout_total, 2, ',', ' ');
    $recap = "\nRécapitulatif : $count appareils traités, coût total rétroactif : $cout_total_fmt €";
    echo "$recap\n";
    logMsg($recap, $logFile);
    logMsg("--- Fin rétroactivité maintenance ---", $logFile);

} catch (PDOException $e) {
    $errMsg = "ERREUR : " . $e->getMessage();
    echo "$errMsg\n";
    logMsg($errMsg, $logFile);
    exit(1);
}
