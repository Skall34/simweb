<?php
/**
 * Token validation utilities for simaddon integration
 */
require_once __DIR__ . '/log_func.php';
require_once __DIR__ . '/db_connect.php';

/**
 * Check if a token exists and is valid in the simaddon_tokens table
 * 
 * @param string $token The token to validate
 * @return string user if token exists and is valid (not expired), null otherwise
 */
function check_token($token) {
    global $pdo;
    logMsg('Checking token: ' . ($token ?? 'NULL'), 'tokens');
    if (empty($token)) {
        return null;
    }
    
    try {
        // Check if token exists and is not expired
        $stmt = $pdo->prepare(
            "SELECT id, user_id, expires_at 
             FROM simaddon_tokens 
             WHERE token = ? 
             LIMIT 1"
        );
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            logMsg('Token not found: ' . $token, 'tokens');
            return null;
        }
        logMsg('Token found for user_id: ' . $row['user_id'], 'tokens');
        // Check if token has expired (if expires_at is set)

        if ($row['expires_at'] !== null) {
            $expiresAt = new DateTime($row['expires_at']);
            $now = new DateTime();
            
            if ($now > $expiresAt) {
                // Token has expired
                logMsg('Token expired for user_id: ' . $row['user_id'], 'tokens');  
                return null;
            }
        }
        
        // Token exists and is valid
        logMsg('Token is valid for user_id: ' . $row['user_id'], 'tokens');
        return $row['user_id'];
        
    } catch (PDOException $e) {
        // Log error if logging function is available
        if (function_exists('logMsg')) {
            logMsg('check_token error: ' . $e->getMessage(), 'tokens');
        }
        return null;
    }
}
