<?php
// Script d'import ACARS vers FROM_ACARS
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';

$logFile = __DIR__ . '/logs/import_from_acars.log';
logMsg("--- Démarrage import_from_acars.php ---", $logFile);

// Récupération du chemin du CSV
$csvFile = $_POST['csv_path'] ?? $argv[1] ?? null;
if (!$csvFile || !file_exists($csvFile)) {
    logMsg("❌ Fichier CSV introuvable : $csvFile", $logFile);
    exit("❌ Fichier CSV introuvable : $csvFile\n");
}

$handle = fopen($csvFile, 'r');
if (!$handle) {
    logMsg("❌ Impossible d’ouvrir le fichier CSV.", $logFile);
    exit("❌ Impossible d’ouvrir le fichier CSV.\n");
}

$table = 'FROM_ACARS';
$countInserted = 0;
$countIgnored = 0;
$ignoredLines = [];
$lineNumber = 0;

$dryRun = false;
if ((isset($_GET['dryrun']) && $_GET['dryrun']) || (isset($_POST['dryrun']) && $_POST['dryrun'])) {
    $dryRun = true;
}

while (($line = fgets($handle)) !== false) {
    $lineNumber++;
    $line = trim($line);
    if ($line === '') continue;

    // Mapping direct des champs du CSV
    $fields = explode("\t", $line);
    if (count($fields) < 13) {
        $ignoredLines[] = "Ligne $lineNumber : format non reconnu (" . count($fields) . " champs).";
        $countIgnored++;
        continue;
    }
    // Nettoyage et extraction après mapping direct
    // Horodateur : formatage en Y-m-d H:i:s si possible
    $horodateur = $fields[0];
    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2}):(\d{2})$/', $horodateur, $dtMatch)) {
        $horodateur = $dtMatch[3] . '-' . $dtMatch[2] . '-' . $dtMatch[1] . ' ' . $dtMatch[4] . ':' . $dtMatch[5] . ':' . $dtMatch[6];
    }

    // Callsign : nettoyage éventuel (majuscules, trim)
    $callsign = strtoupper(trim($fields[1]));

    // Immatriculation : nettoyage éventuel (majuscules, trim)
    $immatriculation = strtoupper(trim($fields[2]));

    // ICAO départ/arrivée : nettoyage
    $departure_icao = strtoupper(trim($fields[3]));
    $arrival_icao   = strtoupper(trim($fields[6]));

    // Fuel départ/arrivée : conversion en entier
    $departure_fuel = is_numeric($fields[4]) ? intval($fields[4]) : null;
    $arrival_fuel   = is_numeric($fields[7]) ? intval($fields[7]) : null;

    // Heures départ/arrivée : trim
    $departure_time = trim($fields[5]);
    $arrival_time   = trim($fields[8]);

    // Payload : arrondi, conversion décimale
    $payload = round(floatval(str_replace(',', '.', $fields[9])));

    // Commentaire : tout le texte qui commence par "Landing speed" et se termine par "V3.4.1)"
    $commentaire = '';
    if (preg_match('/(Landing speed.*?V3\.4\.1\))/i', $fields[10], $matches)) {
        $commentaire = $matches[1];
    } else {
        $commentaire = trim($fields[10]);
    }

    // Note du vol : conversion en entier si possible
    $note_du_vol = is_numeric($fields[11]) ? intval($fields[11]) : null;

    // Mission : nettoyage et correction
    $mission = strtoupper(trim($fields[12]));
    if ($mission === 'LONG, MOYEN COURRIER') {
        $mission = 'Long/moyen courrier';
    } elseif ($mission === 'LONG COURRIER') {
        $mission = 'Long courrier';
    } elseif ($mission === 'MOYEN COURRIER') {
        $mission = 'Moyen courrier';
    }

    $processed = 0;

    // Construction des paramètres
    $params = [
        ':horodateur' => $horodateur ?: date('Y-m-d H:i:s'),
        ':callsign' => $callsign,
        ':immatriculation' => $immatriculation,
        ':departure_icao' => $departure_icao,
        ':departure_fuel' => $departure_fuel,
        ':departure_time' => $departure_time,
        ':arrival_icao' => $arrival_icao,
        ':arrival_fuel' => $arrival_fuel,
        ':arrival_time' => $arrival_time,
        ':payload' => $payload,
        ':commentaire' => $commentaire,
        ':note_du_vol' => $note_du_vol,
        ':mission' => $mission,
        ':processed' => 0
    ];

    // Affichage debug du mapping colonne => donnée
    echo "<div style='font-size:0.95em; margin-bottom:8px; border-bottom:1px dashed #ccc;'>";
    echo "<strong>Ligne $lineNumber - Pré-insertion :</strong><br>";
    foreach ($params as $col => $val) {
        $colName = substr($col, 1);
        echo "$colName : <span style='color:blue'>" . htmlspecialchars((string)$val) . "</span><br>";
    }
    echo "</div>";
    if ($dryRun) {
        // En mode simulation, on n'insère rien
        continue;
    }
    // Préparation requête
    $columns = array_keys($params);
    $sql = "INSERT INTO $table (" . implode(',', array_map(fn($c) => substr($c,1), $columns)) . ") VALUES (" . implode(',', $columns) . ")";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $countInserted++;
    } catch (PDOException $e) {
        $ignoredLines[] = "Ligne $lineNumber : erreur SQL - " . $e->getMessage();
        $countIgnored++;
        logMsg("Erreur SQL ligne $lineNumber : " . $e->getMessage(), $logFile);
    }
}
fclose($handle);

// Résumé
if ($dryRun) {
    echo "<br><strong>Mode simulation : aucune donnée insérée en base.</strong><br>";
} else {
    logMsg("Import terminé : $countInserted lignes insérées, $countIgnored ignorées.", $logFile);
    echo "<br>✅ Import terminé : $countInserted lignes insérées.<br>";
}
if ($countIgnored > 0) {
    echo "⚠️ $countIgnored lignes ignorées :<br>";
    foreach ($ignoredLines as $msg) {
        echo htmlspecialchars($msg) . "<br>";
    }
}
