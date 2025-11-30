<?php
require_once __DIR__ . '/../includes/require_admin.php';
require_once __DIR__ . '/../includes/db_connect.php';
header('Content-Type: application/json; charset=utf-8');

$dep = isset($_GET['dep']) ? strtoupper(trim($_GET['dep'])) : '';
$arr = isset($_GET['arr']) ? strtoupper(trim($_GET['arr'])) : '';

if ($dep === '' || $arr === '') {
    echo json_encode(['ok' => false, 'error' => 'missing']);
    exit;
}

function calc_distance_nm_local($lat1, $lon1, $lat2, $lon2) {
    $deg2rad = pi() / 180.0;
    $lat1r = $lat1 * $deg2rad;
    $lon1r = $lon1 * $deg2rad;
    $lat2r = $lat2 * $deg2rad;
    $lon2r = $lon2 * $deg2rad;
    $dlat = $lat2r - $lat1r;
    $dlon = $lon2r - $lon1r;
    $a = sin($dlat/2) * sin($dlat/2) + cos($lat1r) * cos($lat2r) * sin($dlon/2) * sin($dlon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $earth_km = 6371.0;
    $dist_km = $earth_km * $c;
    $dist_nm = $dist_km / 1.852;
    return $dist_nm;
}

try {
    $stmtDep = $pdo->prepare("SELECT latitude_deg, longitude_deg FROM AEROPORTS WHERE ident = :icao LIMIT 1");
    $stmtDep->execute(['icao' => $dep]);
    $depRow = $stmtDep->fetch(PDO::FETCH_ASSOC);

    $stmtArr = $pdo->prepare("SELECT latitude_deg, longitude_deg FROM AEROPORTS WHERE ident = :icao LIMIT 1");
    $stmtArr->execute(['icao' => $arr]);
    $arrRow = $stmtArr->fetch(PDO::FETCH_ASSOC);

    if ($depRow && $arrRow && $depRow['latitude_deg'] !== null && $arrRow['latitude_deg'] !== null) {
        $computed = calc_distance_nm_local((float)$depRow['latitude_deg'], (float)$depRow['longitude_deg'], (float)$arrRow['latitude_deg'], (float)$arrRow['longitude_deg']);
        $distance = (int)ceil($computed);
        echo json_encode(['ok' => true, 'distance' => $distance]);
        exit;
    } else {
        echo json_encode(['ok' => false, 'error' => 'no_coords']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => 'exception', 'msg' => $e->getMessage()]);
    exit;
}
