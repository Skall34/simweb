<?php
// Active les erreurs (en dev uniquement)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connexion BDD
require_once __DIR__ . '/../includes/db_connect.php';

// Réponse en JSON
header('Content-Type: application/json');

file_put_contents('/tmp/acars_post.txt', print_r($_POST, true), FILE_APPEND);

// Refuser toute méthode autre que POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Méthode non autorisée
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupération des données POST
$data = $_POST;

// Champs obligatoires
$required = [
    'callsign', 'plane', 'departure_icao', 'flying'
];

// Vérification des champs requis
foreach ($required as $field) {
    if (!isset($data[$field]) || trim($data[$field]) === '') {
        http_response_code(400); // Mauvaise requête
        echo json_encode(['status' => 'error', 'message' => "Champ requis manquant ou vide : $field"]);
        exit;
    }
}

// Formatage et nettoyage
$callsign = trim($data['callsign']);
$immat = trim($data['plane']);
$departure_icao = strtoupper(trim($data['departure_icao']));
$arrival_icao = strtoupper(trim($data['arrival_icao']));
$flying = intval($data['flying']);

// Insertion en base
try {
    if ($flying == 1) {
        $stmt = $pdo->prepare("INSERT INTO Live_FLIGHTS (
            Callsign, ICAO_Dep, ICAO_Arr, Avion
        ) VALUES (
            :Callsign, :ICAO_Dep, :ICAO_Arr, :Avion
        )
        ON DUPLICATE KEY UPDATE
            ICAO_Dep = VALUES(ICAO_Dep),
            ICAO_Arr = VALUES(ICAO_Arr),
            Avion = VALUES(Avion)");

        $stmt->execute([
            'Callsign'  => $callsign,
            'ICAO_Dep'  => $departure_icao,
            'ICAO_Arr'  => $arrival_icao,
            'Avion'     => $immat,
        ]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM Live_FLIGHTS WHERE Callsign = :cs");
        $stmt->execute([
            'cs' => $callsign
        ]);
    }

    echo json_encode(['status' => 'success', 'message' => '✅ status mis à jour avec succès']);
} catch (PDOException $e) {
    http_response_code(500); // Erreur serveur
    echo json_encode(['status' => 'error', 'message' => '❌ Erreur SQL : ' . $e->getMessage()]);
}
