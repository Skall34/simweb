<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db_connect.php';

try {
    $stmt = $pdo->query("SELECT last_update FROM AEROPORTS_LAST_ADMIN_UPDATE LIMIT 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode([
            'success' => true,
            'last_update' => $result['last_update']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Date non trouvée.'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération de la date de dernière mise à jour.'
    ]);
}
?>
