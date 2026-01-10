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
 4. Contrôles métier : validité des données, existence du pilote et de l'avion, détection doublons.
 5. Met à jour le fret, la flotte, les finances, le carnet de vol, et l'usure.
 6. Logue chaque étape et erreur dans le fichier log.
 7. Retourne une réponse JSON indiquant le succès ou l'erreur.

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
require_once __DIR__ . '/../includes/fonctions_financieres.php';
require_once __DIR__ . '/../includes/fonctions_importer_vol.php';
require_once __DIR__ . '/../includes/calcul_cout.php';
require_once __DIR__ . '/../lang.php';

date_default_timezone_set('Europe/Paris');
$logFile = dirname(__DIR__) . '/scripts/logs/importer_vol_direct.log';
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
$tracegps = isset($data['tracegps']) ? trim($data['tracegps']) : '';

logMsg("[api_import_vol_direct] ✅ Début traitement vol (callsign: $callsign)", $logFile);
// Harmonisation avec importer_vol.php :
try {
    $erreurs = [];

    // 1. Contrôles basiques
    if (!$callsign || !$immat || !$departure_icao || !$arrival_icao) {
        $erreurs[] = "Vol invalide : données manquantes (callsign, immat, depart ou destination)";
    }

    if ($note < 1 || $note > 10) {
        $erreurs[] = "Note du vol invalide ($note) pour le vol";
    }

    // 2. Contrôle carburant nul (fuel_dep, fuel_arr, conso à 0)
    $conso = $departure_fuel - $arrival_fuel;
    if ($departure_fuel == 0 || $arrival_fuel == 0 || $conso == 0) {
        $erreurs[] = "Vol rejeté automatiquement : carburant départ, arrivée et consommation à 0";
    }

    // 3. Vérification du pilote
    $stmtPilote = $pdo->prepare("SELECT id FROM PILOTES WHERE callsign = :callsign");
    $stmtPilote->execute(['callsign' => $callsign]);
    $pilote = $stmtPilote->fetch();
    if (!$pilote) {
        $erreurs[] = "Pilote '$callsign' introuvable dans PILOTES.";
    }

    // 4. Vérification de l'avion actif
    $stmtAvion = $pdo->prepare("SELECT id FROM FLOTTE WHERE immat = :immat AND actif = 1");
    $stmtAvion->execute(['immat' => $immat]);
    $avion = $stmtAvion->fetch();
    if (!$avion) {
        $erreurs[] = "Avion '$immat' introuvable ou inactif dans FLOTTE.";
    }

    // 5. Vérification des doublons
    if (detecterDoublonVol($pdo, $callsign, $departure_icao, $arrival_icao, $departure_fuel, $arrival_fuel, $payload, $note, $mission, $logFile)) {
        $erreurs[] = "Vol doublon détecté pour le pilote '$callsign' (depart=$departure_icao, dest=$arrival_icao, payload=$payload, fuelDep=$departure_fuel, fuelArr=$arrival_fuel, note=$note, mission=$mission)";        
    }

    // Si erreurs, rejeter le vol avec tous les motifs
    if (!empty($erreurs)) {
        foreach ($erreurs as $err) {
            logMsg("[api_import_vol_direct] ❌ $err", $logFile);
        }
        rejeterVolDirect($pdo, $callsign, $immat, $departure_icao, $arrival_icao, $departure_fuel, $arrival_fuel, $departure_time, $arrival_time, $payload, $commentaire, $note, $mission, implode(' | ', $erreurs), $horodateur, $logFile);
        echo json_encode(['status' => 'error', 'message' => implode(' | ', $erreurs)]);
        return;
    }

    // 6. Traitement du fret
    if ($payload > 0) {
        $fret_transporte = deduireFretDepart($departure_icao, $payload, $logFile);
        ajouterFretDestination($arrival_icao, $fret_transporte, $logFile);
    }

    // 7. Calcul du coût du vol
    $distance = ComputeFlightDistance($departure_icao, $arrival_icao);
    logMsg("[api_import_vol_direct] Distance calculée : $distance NM", $logFile);
    $majoration_mission = getMajorationMission($mission);
    $cout_horaire = getCoutHoraire($immat);
    $carburant = $departure_fuel - $arrival_fuel;
    $temps_vol = '00:00:00';
    if ($departure_time && $arrival_time) {
        $t1 = new DateTime($departure_time);
        $t2 = new DateTime($arrival_time);
        // Si l'heure d'arrivée est inférieure ou égale à l'heure de départ, on ajoute 1 jour à l'arrivée
        if ($t2 <= $t1) {
            $t2->modify('+1 day');
        }
        $interval = $t1->diff($t2);
        $temps_vol = $interval->format('%H:%I:%S');
    }
    $cout_vol = calculerRevenuNetVol($payload, $temps_vol,$distance, $majoration_mission, $carburant, $note, $cout_horaire,$immat);

    // 8. Ajout au carnet de vol avec le coût
    $vol_id = remplirCarnetVolGeneral($horodateur, $callsign, $immat, $departure_icao, $arrival_icao, $departure_fuel, $arrival_fuel, $payload, $departure_time, $arrival_time, $mission, $commentaire, $note, $cout_vol, $temps_vol, $logFile);
    logMsg("[api_import_vol_direct] Ajout au carnet de vol : callsign=$callsign, immat=$immat, depart=$departure_icao, dest=$arrival_icao, payload=$payload, cout_vol=$cout_vol", $logFile);

    // 9. Ajout de la trace GPS si fournie
    if (empty($tracegps)) {
        $tracegps = "Aucune trace GPS fournie pour le vol $vol_id";
    }else{
        ajouterTraceGPS($vol_id, $tracegps, $logFile);
        logMsg("[api_import_vol_direct] Ajout de la trace GPS ajoutée pour le vol ID $vol_id", $logFile);
    }
    
    // 10. Mise à jour de la flotte
    mettreAJourFlotte($immat, $arrival_fuel, $callsign, $arrival_icao, $logFile);
    logMsg("[api_import_vol_direct] Mise à jour flotte : immat=$immat, fuel=$arrival_fuel, callsign=$callsign, localisation=$arrival_icao", $logFile);

    // 11. Mettre à jour finances
    mettreAJourFinances($immat, $cout_vol, $logFile);
    logMsg("[api_import_vol_direct] Mise à jour finances : immat=$immat, cout_vol=$cout_vol", $logFile);

    // 12. Mise à jour de la balance commerciale via fonction dédiée
    $commentaire = "Vol importé depuis ACARS : $departure_icao -> $arrival_icao, pilote: $callsign, immat: $immat";
    mettreAJourRecettes($cout_vol, $vol_id, $immat, $callsign, 'vol', 'Recette vol ACARS');
    logMsg("[api_import_vol_direct] Ajout recette dans finances_recettes : cout_vol=$cout_vol, vol_id=$vol_id", $logFile);

    // 13. Usure
    deduireUsure($immat, $note, $logFile);
    logMsg("[api_import_vol_direct] Usure avion $immat, note=$note", $logFile);
    
    // 14. Envoi du mail recapitulatif enrichi
    if ($mailSummaryEnabled && function_exists('sendSummaryMail')) {
        $subject = "[SimWeb] Rapport import vol direct ACARS - " . date('d/m/Y H:i');
        $body = "Bonjour,\r\n\r\nImport d'un vol ACARS direct termine.\r\n\r\n";
        // Nettoyer les caracteres speciaux pour eviter problemes SMTP
        $callsign_clean = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $callsign);
        $immat_clean = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $immat);
        $mission_clean = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $mission);
        $body .= "Pilote : " . $callsign_clean . "\r\n";
        $body .= "Trajet : " . $departure_icao . " -> " . $arrival_icao . "\r\n";
        $body .= "Immatriculation : " . $immat_clean . "\r\n";
        $body .= "Mission : " . $mission_clean . "\r\n";
        // Formater payload avec une virgule comme séparateur décimal et ajouter l'unité Kg
        $payload_fmt = number_format(floatval($payload), 2, ',', '');
        $body .= "Payload : {$payload_fmt} Kg\r\n";
        $body .= "Note : " . intval($note) . "\r\n";
        // Formater le montant de la recette avec une virgule comme séparateur décimal
        $cout_vol_fmt = number_format(floatval($cout_vol), 2, ',', '');
        $body .= "Recettes du vol : {$cout_vol_fmt} EUR\r\n";
        
        $body .= "\r\n\r\nCeci est un message automatique.\r\n";
        $to = VA_ADMIN_EMAIL;
        $mailResult = sendSummaryMail($subject, $body, $to);
        if (is_array($mailResult)) {
            // Nouveau format avec retry log
            if ($mailResult['success']) {
                logMsg("[api_import_vol_direct] Mail recapitulatif envoye a $to apres {$mailResult['attempts']} tentative(s)", $logFile);
            } else {
                logMsg("[api_import_vol_direct] ERREUR envoi mail apres {$mailResult['attempts']} tentatives: {$mailResult['error']}", $logFile);
            }
            // Enregistrer tous les logs de retry
            foreach ($mailResult['log'] as $logLine) {
                logMsg("[api_import_vol_direct] RETRY: $logLine", $logFile);
            }
        } elseif ($mailResult === true) {
            // Ancien format (true = succes premier coup)
            logMsg("[api_import_vol_direct] Mail recapitulatif envoye a $to", $logFile);
        } else {
            // Ancien format (string = erreur)
            logMsg("[api_import_vol_direct] Avertissement envoi mail : $mailResult", $logFile);
        }
    }

    logMsg("[api_import_vol_direct] ✅ Vol traité avec succès (callsign: $callsign)", $logFile);
    $compagnyName = t('company_name');
    echo json_encode(['status' => 'success', 'message' => "Thank you for flying with $compagnyName!"]);
} catch (PDOException $e) {
    logMsg("[api_import_vol_direct] ❌ Erreur DB : " . $e->getMessage(), $logFile);
    http_response_code(500); // Erreur serveur
    echo json_encode(['status' => 'error', 'message' => '❌ Erreur SQL : ' . $e->getMessage()]);
}