<?php
// Activer le rapport d’erreurs complet
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/importer_vol.log');

require_once __DIR__ . '/../includes/db_connect.php';

$table = 'CARNET_DE_VOL_GENERAL';
$csvFile = './CDV_GENERAL.csv';

// Vérification du fichier
if (!file_exists($csvFile)) {
    die("❌ Fichier CSV introuvable : $csvFile");
}

$handle = fopen($csvFile, 'r');
if (!$handle) {
    die("❌ Impossible d’ouvrir le fichier CSV.");
}

// Lecture de la première ligne (entêtes)
$rawHeader = fgetcsv($handle, 0, ";", '"', "\\");

if (!$rawHeader || count($rawHeader) === 0) {
    die("❌ Le fichier CSV est vide ou l'en-tête est invalide.");
}

// Nettoyage et validation du header
$header = [];
$invalidHeaders = [];

foreach ($rawHeader as $i => $col) {
    $col = trim($col);
    if ($col === '' || preg_match('/[^a-zA-Z0-9_]/', $col)) {
        $invalidHeaders[] = [
            'index' => $i + 1,
            'valeur' => $col === '' ? '[vide]' : $col
        ];
    } else {
        $header[] = $col;
    }
}

// Affichage des entêtes invalides
if (!empty($invalidHeaders)) {
    echo "<strong>❌ Colonnes invalides détectées dans l'en-tête :</strong><br>";
    foreach ($invalidHeaders as $err) {
        echo "Colonne {$err['index']} : <span style='color:red'>{$err['valeur']}</span><br>";
    }
    fclose($handle);
    exit("🛑 Import interrompu. Corrigez l'en-tête du fichier CSV.");
}

// Préparation de la requête SQL
$columns = array_map(fn($col) => "`$col`", $header);
$placeholders = array_map(fn($col) => ":$col", $header);
$sql = "INSERT INTO `$table` (" . implode(',', $columns) . ") VALUES (" . implode(',', $placeholders) . ")";
$stmt = $pdo->prepare($sql);

// Préparation des requêtes de recherche
$lookupStmtMission = $pdo->prepare("SELECT id FROM MISSIONS WHERE Libelle = :libelle");
$lookupStmtPilote = $pdo->prepare("SELECT id FROM PILOTES WHERE callsign = :callsign");
$lookupStmtAppareil = $pdo->prepare("SELECT id FROM FLOTTE WHERE immat = :immat");

// Boucle de lecture
$countInserted = 0;
$countIgnored = 0;
$ignoredLines = [];
$lineNumber = 1;

while (($data = fgetcsv($handle, 0, ";", '"', "\\")) !== false) {
    $lineNumber++;

    if (count($data) < count($header)) {
        $ignoredLines[] = "Ligne $lineNumber : trop peu de colonnes.";
        $countIgnored++;
        continue;
    }

    $params = [];

    foreach ($header as $i => $col) {
        $val = trim($data[$i] ?? '');

        // Conversion mission_id
        if ($col === 'mission_id') {
            $lookupStmtMission->execute([':libelle' => $val]);
            $result = $lookupStmtMission->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                $ignoredLines[] = "Ligne $lineNumber : mission inconnue '$val'.";
                $countIgnored++;
                continue 2;
            }
            $params[":$col"] = $result['id'];
            continue;
        }

        // Conversion pilote_id
        if ($col === 'pilote_id') {
            $lookupStmtPilote->execute([':callsign' => $val]);
            $result = $lookupStmtPilote->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                $ignoredLines[] = "Ligne $lineNumber : pilote inconnu '$val'.";
                $countIgnored++;
                continue 2;
            }
            $params[":$col"] = $result['id'];
            continue;
        }

        // Conversion appareil_id
        if ($col === 'appareil_id') {
            $lookupStmtAppareil->execute([':immat' => $val]);
            $result = $lookupStmtAppareil->fetch(PDO::FETCH_ASSOC);
            if (!$result) {
                $ignoredLines[] = "Ligne $lineNumber : appareil inconnu '$val'.";
                $countIgnored++;
                continue 2;
            }
            $params[":$col"] = $result['id'];
            continue;
        }

        // Formatage de la date
        if ($col === 'date_vol') {
            $dt = DateTime::createFromFormat('d/m/Y', $val);
            if (!$dt) {
                $ignoredLines[] = "Ligne $lineNumber : date invalide '$val'.";
                $countIgnored++;
                continue 2;
            }
            $params[":$col"] = $dt->format('Y-m-d');
            continue;
        }

        // Heures
        if (in_array($col, ['heure_depart', 'heure_arrivee'])) {
            if ($val === '') {
                $params[":$col"] = null;
                continue;
            }
            $time = DateTime::createFromFormat('H:i:s', $val) ?: DateTime::createFromFormat('H:i', $val);
            if (!$time) {
                $ignoredLines[] = "Ligne $lineNumber : heure invalide '$val' pour '$col'.";
                $countIgnored++;
                continue 2;
            }
            $params[":$col"] = $time->format('H:i:s');
            continue;
        }

        // payload vide => 0
        if ($col === 'payload') {
            $params[":$col"] = $val === '' ? 0 : (int)$val;
            continue;
        }

        // note_du_vol vide => 10
        if ($col === 'note_du_vol') {
            $params[":$col"] = $val === '' ? 10 : (int)$val;
            continue;
        }

        // pirep_maintenance vide => "RAS AUTO"
        if ($col === 'pirep_maintenance') {
            $params[":$col"] = $val === '' ? 'RAS AUTO' : $val;
            continue;
        }

        // Valeur par défaut
        $params[":$col"] = $val;
    }

    // Génération de la requête avec valeurs pour debug
    $sqlDebug = $sql;
    foreach ($params as $key => $value) {
        $escaped = is_null($value) ? 'NULL' : $pdo->quote($value);
        $sqlDebug = str_replace($key, $escaped, $sqlDebug);
    }
    error_log("🔍 Ligne $lineNumber : $sqlDebug");

    try {
        $stmt->execute($params);
        $countInserted++;
    } catch (PDOException $e) {
        $ignoredLines[] = "Ligne $lineNumber : erreur SQL - " . $e->getMessage() . " | Requête : $sqlDebug";
        $countIgnored++;
    }
}

fclose($handle);

// Résumé
echo "<br>✅ Import terminé : $countInserted lignes insérées.<br>";
if ($countIgnored > 0) {
    echo "⚠️ $countIgnored lignes ignorées :<br>";
    foreach ($ignoredLines as $msg) {
        echo htmlspecialchars($msg) . "<br>";
    }
}
?>
