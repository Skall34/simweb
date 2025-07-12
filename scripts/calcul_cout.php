<?php
// calcul_cout.php

require_once __DIR__ . '/../includes/db_connect.php'; // Assure la connexion PDO

/**
 * Retourne le coefficient basé sur la note du vol
 */
function coef_note($note) {
    if ($note === null || $note === '') return 1;
    return match((int)$note) {
        1 => 100,
        2 => 50,
        3 => 1.8,
        4 => 1.6,
        5 => 1.4,
        6 => 1.2,
        7 => 1,
        8 => 0.8,
        9 => 0.7,
        10 => 0.5,
        default => 1,
    };
}

/**
 * Récupère la majoration de la mission dans la table MISSIONS
 */
function getMajorationMission($mission_libelle) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT majoration_mission FROM MISSIONS WHERE libelle = :libelle");
    $stmt->execute(['libelle' => $mission_libelle]);
    $result = $stmt->fetch();

    return $result ? (float)$result['majoration_mission'] : 1.0; // défaut à 1.0 si mission non trouvée
}

/**
 * Récupère le coût horaire de l'appareil à partir de son immatriculation
 */
function getCoutHoraire($immat) {
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT ft.cout_horaire 
        FROM FLOTTE f 
        JOIN FLEET_TYPE ft ON f.type = ft.id 
        WHERE f.immat = :immat
    ");
    $stmt->execute(['immat' => $immat]);
    $result = $stmt->fetch();

    return $result ? (float)$result['cout_horaire'] : 0.0;
}

/**
 * Calcul du revenu net d'un vol
 */
function calculerRevenuNetVol($payload, $temps_vol, $majoration_mission, $carburant, $note, $cout_horaire) {
    [$h, $m, $s] = sscanf($temps_vol, "%d:%d:%d");
    $heures = $h + ($m / 60) + ($s / 3600);

    $coef_note_val = coef_note($note);

    $revenu_brut = $payload * 5 * $heures * $majoration_mission;
    $cout_carburant = $carburant * 0.88;
    $cout_appareil = $cout_horaire * $heures * $coef_note_val;

    $revenu_net = $revenu_brut - ($cout_carburant + $cout_appareil);

    return round($revenu_net, 2);
}
