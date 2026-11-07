<?php
/**
 * Système de rate limiting simple
 * Limite le nombre de tentatives par IP sur une période donnée
 */

/**
 * Vérifie si l'IP a dépassé la limite de tentatives
 * 
 * @param PDO $pdo Connexion PDO
 * @param string $action Type d'action ('login', 'forgot_password', etc.)
 * @param int $maxAttempts Nombre maximum de tentatives autorisées
 * @param int $windowSeconds Fenêtre de temps en secondes
 * @return array ['allowed' => bool, 'remaining' => int, 'reset_at' => string]
 */
function checkRateLimit($pdo, $action, $maxAttempts = 5, $windowSeconds = 300) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $now = time();
    
    // Nettoyer les anciennes entrées (plus vieilles que 24h)
    try {
        $pdo->exec("DELETE FROM rate_limits WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    } catch (PDOException $e) {
        // Ignorer les erreurs de nettoyage
    }
    
    // Récupérer l'entrée actuelle
    $stmt = $pdo->prepare("SELECT * FROM rate_limits WHERE ip = ? AND action = ?");
    $stmt->execute([$ip, $action]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        // Première tentative
        $stmt = $pdo->prepare("INSERT INTO rate_limits (ip, action, attempts, first_attempt, last_attempt) VALUES (?, ?, 1, NOW(), NOW())");
        $stmt->execute([$ip, $action]);
        
        return [
            'allowed' => true,
            'remaining' => $maxAttempts - 1,
            'reset_at' => date('Y-m-d H:i:s', $now + $windowSeconds)
        ];
    }
    
    $firstAttempt = strtotime($record['first_attempt']);
    $timeSinceFirst = $now - $firstAttempt;
    
    // Si la fenêtre est expirée, réinitialiser
    if ($timeSinceFirst > $windowSeconds) {
        $stmt = $pdo->prepare("UPDATE rate_limits SET attempts = 1, first_attempt = NOW(), last_attempt = NOW() WHERE ip = ? AND action = ?");
        $stmt->execute([$ip, $action]);
        
        return [
            'allowed' => true,
            'remaining' => $maxAttempts - 1,
            'reset_at' => date('Y-m-d H:i:s', $now + $windowSeconds)
        ];
    }
    
    // Vérifier si la limite est dépassée
    if ($record['attempts'] >= $maxAttempts) {
        $resetAt = $firstAttempt + $windowSeconds;
        $waitSeconds = $resetAt - $now;
        
        return [
            'allowed' => false,
            'remaining' => 0,
            'reset_at' => date('Y-m-d H:i:s', $resetAt),
            'wait_seconds' => max(0, $waitSeconds)
        ];
    }
    
    // Incrémenter les tentatives
    $stmt = $pdo->prepare("UPDATE rate_limits SET attempts = attempts + 1, last_attempt = NOW() WHERE ip = ? AND action = ?");
    $stmt->execute([$ip, $action]);
    
    return [
        'allowed' => true,
        'remaining' => $maxAttempts - ($record['attempts'] + 1),
        'reset_at' => date('Y-m-d H:i:s', $firstAttempt + $windowSeconds)
    ];
}

/**
 * Réinitialise le compteur pour une IP et une action
 * À appeler après une connexion réussie
 */
function resetRateLimit($pdo, $action) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE ip = ? AND action = ?");
    $stmt->execute([$ip, $action]);
}
