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
    'callsign', 'immatriculation', 'departure_icao', 'departure_fuel', 'departure_time',
    'arrival_icao', 'arrival_fuel', 'arrival_time', 'payload', 'note_du_vol', 'mission'
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
$departure_time = str_replace('T', ' ', $data['departure_time']) . ':00';
$arrival_time = str_replace('T', ' ', $data['arrival_time']) . ':00';
$callsign = trim($data['callsign']);
$immat = trim($data['immatriculation']);
$departure_icao = strtoupper(trim($data['departure_icao']));
$arrival_icao = strtoupper(trim($data['arrival_icao']));
$departure_fuel = floatval($data['departure_fuel']);
$arrival_fuel = floatval($data['arrival_fuel']);
$payload = floatval($data['payload']);
$note = intval($data['note_du_vol']);
$commentaire = isset($data['commentaire']) ? trim($data['commentaire']) : '';
$mission = trim($data['mission']);

// Insertion en base
try {
    $stmt = $pdo->prepare("INSERT INTO FROM_ACARS (
        horodateur, callsign, immatriculation, departure_icao, departure_fuel, departure_time,
        arrival_icao, arrival_fuel, arrival_time, payload, commentaire, note_du_vol, mission, processed, created_at
    ) VALUES (
        NOW(), :callsign, :immat, :dep_icao, :dep_fuel, :dep_time,
        :arr_icao, :arr_fuel, :arr_time, :payload, :commentaire, :note, :mission, 0, NOW()
    )");

    $stmt->execute([
        'callsign'     => $callsign,
        'immat'        => $immat,
        'dep_icao'     => $departure_icao,
        'dep_fuel'     => $departure_fuel,
        'dep_time'     => $departure_time,
        'arr_icao'     => $arrival_icao,
        'arr_fuel'     => $arrival_fuel,
        'arr_time'     => $arrival_time,
        'payload'      => $payload,
        'commentaire'  => $commentaire,
        'note'         => $note,
        'mission'      => $mission
    ]);

    echo json_encode(['status' => 'success', 'message' => '✅ Vol inséré avec succès']);
} catch (PDOException $e) {
    http_response_code(500); // Erreur serveur
    echo json_encode(['status' => 'error', 'message' => '❌ Erreur SQL : ' . $e->getMessage()]);
}
