<?php
// Active les erreurs (en dev uniquement)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connexion BDD
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../includes/mail_utils.php';
require_once __DIR__ . '/../scripts/fonctions_importer_vol.php';
require_once __DIR__ . '/../scripts/calcul_cout.php';

date_default_timezone_set('Europe/Paris');
$logFile = __DIR__ . '/logs/importer_vol_direct.log';

// Réponse en JSON
header('Content-Type: application/json');

// file_put_contents('/tmp/acars_post.txt', print_r($_POST, true), FILE_APPEND);

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
$horodateur = date("Y-m-d H:i:s");

// Insertion en base
try {
    $stmt = $pdo->prepare("INSERT INTO FROM_ACARS (
        horodateur, callsign, immatriculation, departure_icao, departure_fuel, departure_time,
        arrival_icao, arrival_fuel, arrival_time, payload, commentaire, note_du_vol, mission, processed, created_at
    ) VALUES (
        :horodateur, :callsign, :immat, :dep_icao, :dep_fuel, :dep_time,
        :arr_icao, :arr_fuel, :arr_time, :payload, :commentaire, :note, :mission, 1, NOW()
    )");

    $stmt->execute([
        'horodateur'   => $horodateur,
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
    
    $erreurs = [];

        // 2. Contrôles basiques
    if (!$callsign || !$immat || !$departure_icao || !$arrival_icao) {
        $erreurs[] = "Vol invalide : données manquantes (callsign, immat, depart ou destination)";
    }

    if ($note < 1 || $note > 10) {
        $erreurs[] = "Note du vol invalide ($note) pour le vol";
    }

    // Vérification du pilote
    $stmtPilote = $pdo->prepare("SELECT id FROM PILOTES WHERE callsign = :callsign");
    $stmtPilote->execute(['callsign' => $callsign]);
    $pilote = $stmtPilote->fetch();
    if (!$pilote) {
        $erreurs[] = "Pilote '$callsign' introuvable dans PILOTES.";
    }

    // Vérification de l'avion actif
    $stmtAvion = $pdo->prepare("SELECT id FROM FLOTTE WHERE immat = :immat AND actif = 1");
    $stmtAvion->execute(['immat' => $immat]);
    $avion = $stmtAvion->fetch();
    if (!$avion) {
        $erreurs[] = "Avion '$immat' introuvable ou inactif dans FLOTTE.";
    }

    // Si erreurs, rejeter le vol avec tous les motifs
    if (!empty($erreurs)) {
        foreach ($erreurs as $err) {
            logMsg("❌ $err", $logFile);
        }
        //rejeterVol($pdo, $vol, implode(' | ', $erreurs));
        echo json_encode(['status' => 'error', 'message' => implode(' | ', $erreurs)]);
        return;
    }

    // 3. Traitement du fret
    if ($payload > 0) {
        $fret_transporte = deduireFretDepart($departure_icao, $payload);
        ajouterFretDestination($arrival_icao, $fret_transporte);
    }

    // 4. Calcul du coût du vol
    $majoration_mission = getMajorationMission($mission);
    $cout_horaire = getCoutHoraire($immat);
    $carburant = $departure_fuel - $arrival_fuel;
    $temps_vol = '00:00:00';
    if ($departure_time && $arrival_time) {
        $t1 = new DateTime($departure_time);
        $t2 = new DateTime($arrival_time);
        $interval = $t1->diff($t2);
        $temps_vol = $interval->format('%H:%I:%S');
    }
    $cout_vol = calculerRevenuNetVol($payload, $temps_vol, $majoration_mission, $carburant, $note, $cout_horaire);

    // 5. Ajout au carnet de vol avec le coût
    logMsg("Ajout au carnet de vol : callsign=$callsign, immat=$immat, depart=$departure_icao, dest=$arrival_icao, payload=$payload, cout_vol=$cout_vol", $logFile);
    remplirCarnetVolGeneral($horodateur, $callsign, $immat, $departure_icao, $arrival_icao, $departure_fuel, $arrival_fuel, $payload, $departure_time, $arrival_time, $mission, $commentaire, $note, $cout_vol);

    // 6. Mise à jour de la flotte
    logMsg("Mise à jour flotte : immat=$immat, fuel=$arrival_fuel, callsign=$callsign, localisation=$arrival_icao", $logFile);
    mettreAJourFlotte($immat, $arrival_fuel, $callsign, $arrival_icao);

    // Mettre à jour finances
    logMsg("Mise à jour finances : immat=$immat, cout_vol=$cout_vol", $logFile);
    mettreAJourFinances($immat, $cout_vol);

    // 7. Usure
    logMsg("Usure avion $immat : note=$note", $logFile);
    deduireUsure($immat, $note);

    logMsg("✅ Vol traité avec succès (callsign: $callsign)", $logFile);


    echo json_encode(['status' => 'success', 'message' => '✅ Vol inséré avec succès']);
} catch (PDOException $e) {
    http_response_code(500); // Erreur serveur
    echo json_encode(['status' => 'error', 'message' => '❌ Erreur SQL : ' . $e->getMessage()]);
}
