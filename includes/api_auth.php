<?php
/**
 * Helper minimal pour authentifier les appels API via une clé stockée
 * dans la table VARIABLES_CONFIG (nom = 'reservation_api_key').
 *
 * Comportement : si la valeur en base est vide ou absente, la vérification
 * est bypassée (comportement rétrocompatible). Si une clé est définie,
 * on exige soit l'en-tête HTTP X-API-KEY, soit le paramètre GET/POST api_key.
 */

function require_api_key(PDO $pdo)
{
    try {
        $stmt = $pdo->query("SELECT valeur FROM VARIABLES_CONFIG WHERE nom = 'reservation_api_key' LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $expected = $row && isset($row['valeur']) ? trim($row['valeur']) : '';
    } catch (Exception $e) {
        // Si la lecture échoue, on préfère ne pas bloquer l'API (rétrocompatibilité).
        return true;
    }

    if ($expected === '') {
        // Pas de clé configurée -> comportement inchangé
        return true;
    }

    // Récupère la clé fournie (header prioritaire)
    $provided = '';
    if (!empty($_SERVER['HTTP_X_API_KEY'])) {
        $provided = $_SERVER['HTTP_X_API_KEY'];
    } elseif (isset($_GET['api_key'])) {
        $provided = $_GET['api_key'];
    } elseif (isset($_POST['api_key'])) {
        $provided = $_POST['api_key'];
    }

    // Compare en constant time
    if (!is_string($provided) || !hash_equals($expected, $provided)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Invalid API key']);
        exit;
    }

    return true;
}
