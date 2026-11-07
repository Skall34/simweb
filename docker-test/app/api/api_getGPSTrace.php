<?php
/*
-------------------------------------------------------------
 Script : api_getGPSTrace.php
 Emplacement : api/

 Description :
 API REST permettant de récupérer la trace GPS d'un vol spécifique.
 Retourne le chemin GPS au format JSON pour affichage sur carte.

 Paramètres GET :
 - vol_id : ID du vol dans CARNET_DE_VOL_GENERAL (obligatoire, numérique)

 Réponse JSON :
 - {path: '...'} : Trace GPS au format JSON
 - {error: '...'} : Erreur (vol non trouvé, paramètre invalide, erreur serveur)

 Codes HTTP :
 - 200 : Succès
 - 400 : Paramètre manquant ou invalide
 - 500 : Erreur serveur

 Utilisation :
 - Utilisé pour afficher le trajet d'un vol sur une carte interactive.
 - Appelé depuis les pages de détails de vol.

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../lang.php';

if (!isset($_GET['vol_id']) || !is_numeric($_GET['vol_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètre vol_id manquant ou invalide.']);
    exit;
}

$vol_id = (int)$_GET['vol_id'];

try {
    $stmt = $pdo->prepare("SELECT path FROM TRACE_GPS WHERE id = :vol_id LIMIT 1");
    $stmt->execute(['vol_id' => $vol_id]);
    $path = $stmt->fetchColumn();

    if ($path === false) {
        echo json_encode(['error' => t('api_error_no_gps')]);
    } else {
        echo json_encode(['path' => $path]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => t('cli_error_sql') . ' ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => t('api_error_gps_fetch')]);
}
?>
