<?php
/*
-------------------------------------------------------------
 Script : api_getAirports.php
 Emplacement : api/

 Description :
 API REST retournant la liste complète de tous les aéroports disponibles.
 Triés par code ICAO pour faciliter la recherche.

 Paramètres : Aucun

 Réponse JSON :
 - {success: true, aeroports: [{..., pistes: [{id, designation, longueur_m, surface}, ...],
     Piste, Longueur_de_piste, Type_de_piste}, ...]} : Liste des aéroports,
   chacun avec son tableau de pistes (longueurs en mètres).
   Les 3 clés historiques Piste / Longueur_de_piste / Type_de_piste sont conservées
   (reconstruites depuis PISTES, longueur remise en ft) pour compatibilité SimAddon :
   le JSON est identique avant/après la migration 002 (drop des anciennes colonnes).
 - {success: false, error: '...'} : Erreur lors de la récupération (HTTP 500)

 Utilisation :
 - Utilisé pour peupler les listes déroulantes de sélection d'aéroport.
 - Appelé au chargement des pages de réservation et création de vols.
 - Consommé par le client SimAddon (liste des aéroports, cache JSON).

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db_connect.php';

try {
    // 2 requêtes au lieu de 4 sous-requêtes corrélées par aéroport (~29k aéroports) :
    // la version corrélée coûtait 29k x 4 balayages de PISTES à chaque appel.
    $stmt = $pdo->query("
        SELECT a.ident, a.type_aeroport, a.name, a.municipality,
               a.latitude_deg, a.longitude_deg, a.elevation_ft,
               a.Observations, a.wikipedia_link, a.fret
        FROM AEROPORTS a
        ORDER BY a.ident
    ");
    $airports = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pistes regroupées par aéroport (une seule lecture de PISTES)
    $pistesByAirport = [];
    $pstmt = $pdo->query("SELECT id, aeroport_ident, designation, longueur_m, surface FROM PISTES ORDER BY id");
    foreach ($pstmt->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $pistesByAirport[$p['aeroport_ident']][] = [
            'id' => $p['id'],
            'designation' => $p['designation'],
            'longueur_m' => $p['longueur_m'],
            'surface' => $p['surface'],
            // surface_code n'existe plus en base (migration 012) : alias pour compatibilité JSON SimAddon
            'surface_code' => $p['surface'],
        ];
    }

    // Champs historiques Piste / Longueur_de_piste / Type_de_piste (longueur remise en ft),
    // reconstruits depuis PISTES : une requête groupée, sémantique identique aux anciennes
    // sous-requêtes (GROUP_CONCAT ignore les NULL, ORDER BY p.id, séparateurs identiques).
    $legacyByAirport = [];
    $lstmt = $pdo->query("
        SELECT p.aeroport_ident AS ident,
               GROUP_CONCAT(p.designation ORDER BY p.id SEPARATOR ' ') AS `Piste`,
               GROUP_CONCAT(IF(p.longueur_m IS NULL, NULL, CAST(ROUND(p.longueur_m / 0.3048, 0) AS UNSIGNED)) ORDER BY p.id SEPARATOR '/') AS `Longueur_de_piste`,
               GROUP_CONCAT(IF(p.surface IS NULL, NULL, p.surface) ORDER BY p.id SEPARATOR '/') AS `Type_de_piste`
        FROM PISTES p
        GROUP BY p.aeroport_ident
    ");
    foreach ($lstmt->fetchAll(PDO::FETCH_ASSOC) as $l) {
        $legacyByAirport[$l['ident']] = $l;
    }

    foreach ($airports as &$a) {
        $legacy = $legacyByAirport[$a['ident']] ?? null;
        // /!\ SimAddon deserialize dans List<Dictionary<string,string>> : toute valeur doit
        // etre un scalaire. `pistes` est donc emis comme CHAINE JSON (tableau serialise),
        // sinon le parse plante : "Unexpected character [ ... Path 'aeroports[0].pistes'".
        $a['pistes'] = json_encode($pistesByAirport[$a['ident']] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $a['Piste'] = $legacy['Piste'] ?? null;
        $a['Longueur_de_piste'] = $legacy['Longueur_de_piste'] ?? null;
        $a['Type_de_piste'] = $legacy['Type_de_piste'] ?? null;
    }
    unset($a);

    echo json_encode([
        'success' => true,
        'aeroports' => $airports
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des aéroports.'
    ]);
}