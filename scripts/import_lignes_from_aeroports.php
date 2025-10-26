<?php
// Import simple des lignes régulières à partir de la table AEROPORTS
// Version simplifiée : aucune option. Récupère tous les aéroports WHERE type_aeroport='large_airport'
// et insère chaque paire (dep->arr) dans LIGNES_REGULIERES si elle n'existe pas encore.

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';


$isCli = php_sapi_name() === 'cli';

// Support minimal d'option dry_run via CLI (--dry-run) ou web (?dry_run=1)
$dryRun = false;
if ($isCli) {
    foreach ($argv as $a) if ($a === '--dry-run' || $a === '--dryrun') $dryRun = true;
} else {
    if (!empty($_GET['dry_run']) || !empty($_GET['dry-run'])) $dryRun = true;
}

// Récupère tous les ICAO de type large_airport
$stmt = $pdo->prepare("SELECT ident FROM AEROPORTS WHERE (type_aeroport = 'large_airport' AND ident like 'LF%') OR (ident IN ('LFMT','CYUL','CYVR','TFFR','SPZO','SCEL','NTAA','AYPY','VNKT','FBMN','EINN','BIKF','BGBN','CYQX','CYQT','CYQR','CYVR','KSLC','KDFN','KTPA','MTPP','SVMC','SEQM','SCFA','SCEL','SCIP','NCRG','NSTU','NFFN','NWWW','AGGH','WABB','WAMM','WBSB','VDPP','VYMD','OPLA','OPGD','OOSA','HDAM','HKJK','FWKI','FNLU','FKKD','DRZA','DAUH')) ORDER BY ident");
$stmt->execute();
$airports = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!$airports) {
    $out = "Aucun aéroport de type 'large_airport' trouvé.";
    if ($isCli) echo $out . "\n"; else { header('Content-Type: text/plain'); echo $out; }
    exit;
}



$checkStmt = $pdo->prepare('SELECT COUNT(*) FROM LIGNES_REGULIERES WHERE icao_dep = ? AND icao_arr = ?');
// On ajoute created_at et updated_at à l'insertion et on les renseigne à la date courante
// Utilise INSERT IGNORE pour accélérer lorsque dry_run == false (nécessite contrainte UNIQUE)
$insertStmt = $pdo->prepare('INSERT IGNORE INTO LIGNES_REGULIERES (icao_dep, icao_arr, created_at, updated_at) VALUES (?, ?, ?, ?)');

$now = date('Y-m-d H:i:s');
$checked = 0; $inserted = 0; $skipped = 0;

foreach ($airports as $dep) {
    $dep = trim($dep);
    if (!$dep) continue;
    foreach ($airports as $arr) {
        $arr = trim($arr);
        if (!$arr) continue;
        if ($dep === $arr) { $skipped++; continue; }
        $checked++;
        if ($dryRun) {
            // check existence and count would-be inserts
            $checkStmt->execute([$dep, $arr]);
            if ((int)$checkStmt->fetchColumn() > 0) { $skipped++; continue; }
            $inserted++; // would-be inserted
        } else {
            try {
                $insertStmt->execute([$dep, $arr, $now, $now]);
                // rowCount for INSERT IGNORE: 1 if inserted, 0 if ignored
                $inserted += $insertStmt->rowCount();
                if ($insertStmt->rowCount() === 0) $skipped++;
            } catch (Exception $e) {
                logMsg('import_lignes_from_aeroports error inserting ' . $dep . '->' . $arr . ' : ' . $e->getMessage());
            }
        }
    }
}

$summary = "Checked: $checked, Inserted: $inserted, Skipped: $skipped";
if ($isCli) {
    echo $summary . "\n";
} else {
    header('Content-Type: text/plain');
    echo $summary;
}

if (function_exists('logMsg')) logMsg('import_lignes_from_aeroports: ' . $summary);

return;
