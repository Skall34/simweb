<?php
/*
-------------------------------------------------------------
 Script : api_import_vol_direct.php
 Emplacement : api/

 Description :
 API REST permettant d'importer un vol ACARS en base via une requête POST.
 Vérifie et formate les données reçues, rejette les vols invalides, met à jour le fret, la flotte, les finances, le carnet de vol, et applique l'usure.
 Toutes les opérations et erreurs sont enregistrées dans api/logs/importer_vol_direct.log via logMsg().

 Fonctionnement :
 1. Vérifie la méthode HTTP (POST uniquement).
 2. Vérifie la présence et la validité des champs requis dans $_POST.
 3. Formate et nettoie les données reçues.
 4. Insère le vol dans FROM_ACARS (marqué comme traité).
 5. Contrôles métier : validité des données, existence du pilote et de l'avion.
 6. Met à jour le fret, la flotte, les finances, le carnet de vol, et l'usure.
 7. Logue chaque étape et erreur dans le fichier log.
 8. Retourne une réponse JSON indiquant le succès ou l'erreur.

 Utilisation :
 - À appeler via une requête HTTP POST depuis un client ACARS ou une interface web.
 - Vérifier le log en cas d'anomalie ou d'échec d'opération.

 Auteur :
 - Automatisé avec GitHub Copilot
-------------------------------------------------------------
*/

// Connexion BDD
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../includes/mail_utils.php';
require_once __DIR__ . '/../scripts/fonctions_importer_vol.php';
require_once __DIR__ . '/../scripts/calcul_cout.php';

date_default_timezone_set('Europe/Paris');
$logFile = __DIR__ . '/logs/importer_vol_direct.log';
$mailSummaryEnabled = true; // Active l'envoi du mail récapitulatif (mettre à false pour désactiver)

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


    // Vérification des doublons
    if (empty($erreurs)) {
        if (detecterDoublonVol($pdo, $callsign, $departure_icao, $arrival_icao, $departure_fuel, $arrival_fuel, $payload, $note, $mission)) {
            $msgDoublon = "Vol doublon détecté pour le pilote '$callsign' (depart=$departure_icao, dest=$arrival_icao, payload=$payload, fuelDep=$departure_fuel, fuelArr=$arrival_fuel, note=$note, mission=$mission)";
            logMsg("❌ $msgDoublon", $logFile);
            rejeterVol($pdo, $_POST, $msgDoublon);
            echo json_encode(['status' => 'error', 'message' => $msgDoublon]);
            return;
        }
    }

    // Si erreurs, rejeter le vol avec tous les motifs
    if (!empty($erreurs)) {
        foreach ($erreurs as $err) {
            logMsg("❌ $err", $logFile);
        }
        rejeterVol($pdo, $_POST, implode(' | ', $erreurs));
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
    mettreAJourRecettesBalanceCommerciale($pdo);

    // 7. Usure
    logMsg("Usure avion $immat : note=$note", $logFile);
    deduireUsure($immat, $note);

    logMsg("✅ Vol traité avec succès (callsign: $callsign)", $logFile);

    // Envoi du mail récapitulatif enrichi
    if ($mailSummaryEnabled && function_exists('sendSummaryMail')) {
        $subject = "[SimWeb] Rapport import vol direct ACARS - " . date('d/m/Y H:i');
        $body = "Bonjour,\n\nImport d'un vol ACARS direct terminé.";
        $body .= "\nPilote : $callsign";
        $body .= "\nTrajet : $departure_icao -> $arrival_icao";
        $body .= "\nImmatriculation : $immat";
        $body .= "\nMission : $mission";
        $body .= "\nPayload : $payload";
        $body .= "\nNote : $note";
        $body .= "\nCoût du vol : $cout_vol €";
        $body .= "\n\nCeci est un message automatique.";
        $to = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'zjfk7400@gmail.com';
        $mailResult = sendSummaryMail($subject, $body, $to);
        if ($mailResult === true || $mailResult === null) {
            logMsg("Mail récapitulatif envoyé à $to", $logFile);
        } else {
            logMsg("Erreur lors de l'envoi du mail récapitulatif : $mailResult", $logFile);
        }
    }

    echo json_encode(['status' => 'success', 'message' => '✅ Vol inséré avec succès']);
} catch (PDOException $e) {
    http_response_code(500); // Erreur serveur
    echo json_encode(['status' => 'error', 'message' => '❌ Erreur SQL : ' . $e->getMessage()]);
}
