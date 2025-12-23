<?php
/*
-------------------------------------------------------------
 Script : api_update_status.php
 Emplacement : api/

 Description :
 API REST permettant de mettre à jour le statut de vol d'un pilote en temps réel.
 Gère l'insertion/suppression dans Live_FLIGHTS et la mise à jour du statut en_vol de la flotte.

 Paramètres POST (obligatoires) :
 - callsign : Callsign du pilote
 - plane : Immatriculation de l'appareil
 - departure_icao : Code ICAO de départ
 - flying : Statut de vol (1 = en vol, 0 = au sol)
 - latitude : Latitude actuelle
 - longitude : Longitude actuelle

 Paramètres POST (optionnels) :
 - arrival_icao : Code ICAO d'arrivée

 Réponse JSON :
 - {status: 'success', message: '✅ ...'} : Mise à jour réussie
 - {status: 'error', message: 'Méthode non autorisée'} : Méthode HTTP invalide (HTTP 405)
 - {status: 'error', message: 'Champ requis manquant...'} : Paramètre manquant (HTTP 400)
 - {status: 'error', message: '❌ Erreur SQL : ...'} : Erreur serveur (HTTP 500)

 Fonctionnement :
 - Si flying=1 : INSERT ou UPDATE dans Live_FLIGHTS + en_vol=1 dans FLOTTE
 - Si flying=0 : DELETE de Live_FLIGHTS + en_vol=0 dans FLOTTE

 Utilisation :
 - Appelé en continu par SimAddon pour tracker les positions des avions.
 - Permet l'affichage en temps réel sur la carte des vols.

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/
require_once __DIR__ . '/../includes/db_connect.php';
header('Content-Type: application/json');

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
    'callsign', 'plane', 'departure_icao', 'flying', 'latitude','longitude'
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
$latitude = doubleval($data['latitude']);
$longitude = doubleval($data['longitude']);

// Insertion en base
try {
    if ($flying == 1) {
        $stmt = $pdo->prepare("INSERT INTO Live_FLIGHTS (
            Callsign, ICAO_Dep, ICAO_Arr, Avion, Latitude, Longitude
        ) VALUES (
            :Callsign, :ICAO_Dep, :ICAO_Arr, :Avion, :Latitude, :Longitude
        )
        ON DUPLICATE KEY UPDATE
            ICAO_Dep = VALUES(ICAO_Dep),
            ICAO_Arr = VALUES(ICAO_Arr),
            Avion = VALUES(Avion),
            Latitude = VALUES(Latitude),
            Longitude = VALUES(Longitude)");

        $stmt->execute([
            'Callsign'  => $callsign,
            'ICAO_Dep'  => $departure_icao,
            'ICAO_Arr'  => $arrival_icao,
            'Avion'     => $immat,
            'Latitude'  => $latitude,
            'Longitude' => $longitude
        ]);

        //met à jour le statut de l'avion dans la table FLOTTE
        $stmt2 = $pdo->prepare("UPDATE FLOTTE SET en_vol = '1' WHERE immat = :immat");
        $stmt2->execute(['immat' => $immat]);

    } else {
        $stmt = $pdo->prepare("DELETE FROM Live_FLIGHTS WHERE Callsign = :cs");
        $stmt->execute([
            'cs' => $callsign
        ]);

        //met à jour le statut de l'avion dans la table FLOTTE
        $stmt2 = $pdo->prepare("UPDATE FLOTTE SET en_vol = '0' WHERE immat = :immat");
        $stmt2->execute(['immat' => $immat]);

    }

    echo json_encode(['status' => 'success', 'message' => '✅ status mis à jour avec succès Latitude: ' . $latitude . ', Longitude: ' . $longitude]);
} catch (PDOException $e) {
    http_response_code(500); // Erreur serveur
    echo json_encode(['status' => 'error', 'message' => '❌ Erreur SQL : ' . $e->getMessage()]);
}
