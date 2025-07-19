<?php
/*
-------------------------------------------------------------
 Script : calcul_cout.php
 Emplacement : scripts/

 Description :
 Librairie de fonctions pour le calcul des coûts et revenus des vols.
 Permet de récupérer les coefficients, majorations, coûts horaires et de calculer le revenu net d'un vol.
 Toutes les opérations et erreurs sont loguées dans scripts/logs/calcul_cout.log via logMsg().

 Fonctionnement :
 - coef_note($note) : Retourne le coefficient selon la note du vol.
 - getMajorationMission($mission_libelle) : Récupère la majoration de la mission.
 - getCoutHoraire($immat) : Récupère le coût horaire de l'appareil.
 - calculerRevenuNetVol(...) : Calcule le revenu net d'un vol.

 Utilisation :
 - À inclure dans les scripts de traitement de vol ou d'analyse financière.
 - Vérifier le log en cas d'anomalie ou d'échec d'opération.

 Auteur :
 - Automatisé avec GitHub Copilot
-------------------------------------------------------------
*/
require_once __DIR__ . '/../includes/db_connect.php'; // Assure la connexion PDO
require_once __DIR__ . '/../includes/log_func.php';
$logFile = __DIR__ . '/logs/calcul_cout.log';

/**
 * Retourne le coefficient multiplicateur appliqué au coût d'utilisation de l'appareil selon la note du vol.
 *
 * Métier :
 * - Une mauvaise note (1 ou 2) majore fortement le coût d'utilisation (pénalité crash ou incident grave).
 * - Une note moyenne majore modérément le coût.
 * - Une bonne note réduit le coût d'utilisation.
 * - Permet d'inciter les pilotes à voler proprement et à éviter les incidents.
 *
 * @param int|string|null $note Note du vol (1 à 10)
 * @return float Coefficient multiplicateur appliqué au coût horaire
 */
function coef_note($note) {
    if ($note === null || $note === '') return 1;
    $val = match((int)$note) {
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
    logMsg("coef_note($note) = $val", $logFile);
    return $val;
}

/**
 * Récupère le coefficient de majoration associé à une mission spécifique dans la table MISSIONS.
 *
 * Métier :
 * - Certaines missions (ponctuelles, permanentes, spéciales) bénéficient d'un coefficient multiplicateur sur les recettes.
 * - Permet de valoriser les missions complexes ou stratégiques.
 * - Si la mission n'est pas trouvée, le coefficient par défaut est 1 (aucune majoration).
 *
 * @param string $mission_libelle Libellé exact de la mission
 * @return float Coefficient de majoration (>=1)
 */
function getMajorationMission($mission_libelle) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT majoration_mission FROM MISSIONS WHERE libelle = :libelle");
    $stmt->execute(['libelle' => $mission_libelle]);
    $result = $stmt->fetch();
    $val = $result ? (float)$result['majoration_mission'] : 1.0;
    logMsg("getMajorationMission('$mission_libelle') = $val", $logFile);
    return $val;
}

/**
 * Récupère le coût horaire d'utilisation d'un appareil à partir de son immatriculation.
 *
 * Métier :
 * - Permet d'intégrer le coût d'exploitation réel de chaque type d'appareil dans le calcul du revenu net du vol.
 * - Le coût horaire dépend du type d'appareil (hélico, liner, bimoteur, etc.) et de sa catégorie dans FLEET_TYPE.
 * - Si l'appareil n'est pas trouvé, retourne 0 (aucun coût horaire appliqué).
 *
 * @param string $immat Immatriculation de l'appareil
 * @return float Coût horaire en euros
 */
function getCoutHoraire($immat) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT ft.cout_horaire 
        FROM FLOTTE f 
        JOIN FLEET_TYPE ft ON f.fleet_type = ft.id 
        WHERE f.immat = :immat
    ");
    $stmt->execute(['immat' => $immat]);
    $result = $stmt->fetch();
    $val = $result ? (float)$result['cout_horaire'] : 0.0;
    logMsg("getCoutHoraire('$immat') = $val", $logFile);
    return $val;
}

/**
 * Calcule le revenu net généré par un vol en tenant compte de tous les paramètres métier.
 *
 * Métier :
 * - Prend en compte le fret transporté, la durée du vol, la majoration de mission, la consommation de carburant, la note du vol et le coût horaire de l'appareil.
 * - Le revenu brut est calculé selon la formule : fret * 5€ * heures de vol * majoration mission.
 * - Les coûts sont déduits : coût carburant (carburant * 0.88€/L) et coût appareil (coût horaire * heures * coef note).
 * - Permet d'obtenir le bénéfice réel du vol pour la compagnie.
 * - Toutes les étapes sont loguées pour audit et contrôle métier.
 *
 * @param int $payload Fret transporté (en kg)
 * @param string $temps_vol Durée du vol au format HH:MM:SS
 * @param float $majoration_mission Coefficient de majoration de la mission
 * @param float $carburant Consommation de carburant (en litres)
 * @param int|string|null $note Note du vol (1 à 10)
 * @param float $cout_horaire Coût horaire de l'appareil
 * @return float Revenu net du vol (arrondi à 2 décimales)
 */
function calculerRevenuNetVol($payload, $temps_vol, $majoration_mission, $carburant, $note, $cout_horaire) {
    [$h, $m, $s] = sscanf($temps_vol, "%d:%d:%d");
    logMsg("[calculerRevenuNetVol] temps_vol=$temps_vol => h=$h, m=$m, s=$s", $logFile);
    $heures = $h + ($m / 60) + ($s / 3600);
    logMsg("[calculerRevenuNetVol] heures calculées = $heures", $logFile);
    $coef_note_val = coef_note($note);
    logMsg("[calculerRevenuNetVol] coef_note($note) = $coef_note_val", $logFile);
    $revenu_brut = $payload * 5 * $heures * $majoration_mission;
    logMsg("[calculerRevenuNetVol] revenu_brut = payload * 5 * heures * majoration_mission", $logFile);
    logMsg("[calculerRevenuNetVol] revenu_brut = $payload * 5 * $heures * $majoration_mission", $logFile);
    $cout_carburant = $carburant * 0.88;
    logMsg("[calculerRevenuNetVol] cout_carburant = carburant * 0.88", $logFile);
    logMsg("[calculerRevenuNetVol] cout_carburant = $carburant * 0.88", $logFile);
    $cout_appareil = $cout_horaire * $heures * $coef_note_val;
    logMsg("[calculerRevenuNetVol] cout_appareil = cout_horaire * heures * coef_note_val", $logFile);
    logMsg("[calculerRevenuNetVol] cout_appareil = $cout_horaire * $heures * $coef_note_val", $logFile);
    $revenu_net = $revenu_brut - ($cout_carburant + $cout_appareil);
    logMsg("[calculerRevenuNetVol] revenu_net = revenu_brut - (cout_carburant + cout_appareil) = revenu_net", $logFile);
    logMsg("[calculerRevenuNetVol] revenu_net = $revenu_brut - ($cout_carburant + $cout_appareil) = $revenu_net", $logFile);
    logMsg("calculerRevenuNetVol: payload=$payload, temps_vol=$temps_vol, majoration_mission=$majoration_mission, carburant=$carburant, note=$note, cout_horaire=$cout_horaire => revenu_net=$revenu_net", $logFile);
    return round($revenu_net, 2);
}
