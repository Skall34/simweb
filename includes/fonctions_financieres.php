<?php
/*
-------------------------------------------------------------
 Script : fonctions_financieres.php
 Emplacement : includes/

 Description :
 Fonctions utilitaires pour la gestion financière (recettes, dépenses, etc.)
-------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/log_func.php';
$logFile = dirname(__DIR__) . '/scripts/logs/fonctions_financieres.log';

/**
 * Insère une recette (vol, vente, etc.) dans la table finances_recettes.
 * @param float $montant Le montant de la recette
 * @param int|null $reference_id L'ID de référence (vol, vente, etc.)
 * @param string $immat Immatriculation de l'appareil
 * @param string $callsign Callsign du pilote ou vendeur
 * @param string $type Type de recette ('vol', 'vente', ...)
 * @param string $commentaire Commentaire éventuel
 */
function mettreAJourRecettes($montant, $reference_id = null, $immat = '', $callsign = '', $type = 'vol', $commentaire = '') {
    global $pdo, $logFile;
    $date = date('Y-m-d H:i:s');
    $reference_type = $type;
    $comment = "Immat: $immat, Pilote: $callsign, Type: $type";
    if ($commentaire) {
        $comment .= ' | ' . $commentaire;
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO finances_recettes (date, type, montant, reference_id, reference_type, commentaire) VALUES (:date, :type, :montant, :reference_id, :reference_type, :commentaire)");
        $params = [
            'date' => $date,
            'type' => $type,
            'montant' => $montant,
            'reference_id' => $reference_id,
            'reference_type' => $reference_type,
            'commentaire' => $comment
        ];
        $stmt->execute($params);
        if (function_exists('logMsg')) {
            logMsg('Recette insérée : ' . json_encode($params), $logFile);
        }
        mettreAJourBalanceCommerciale();
    } catch (PDOException $e) {
        error_log("❌ ERREUR SQL dans mettreAJourRecettes: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Insère une dépense (achat, maintenance, etc.) dans la table finances_depenses.
 * @param float $montant Le montant de la dépense
 * @param int|null $reference_id L'ID de référence (vol, achat, etc.)
 * @param string $immat Immatriculation de l'appareil
 * @param string $callsign Callsign du pilote ou payeur
 * @param string $type Type de dépense ('achat', 'maintenance', ...)
 * @param string $commentaire Commentaire éventuel
 */
function mettreAJourDepenses($montant, $reference_id = null, $immat = '', $callsign = '', $type = 'depense', $commentaire = '') {
    global $pdo, $logFile;
    $date = date('Y-m-d H:i:s');
    $reference_type = $type;
    $comment = "Immat: $immat, Pilote: $callsign, Type: $type";
    if ($commentaire) {
        $comment .= ' | ' . $commentaire;
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO finances_depenses (date, type, montant, reference_id, reference_type, commentaire) VALUES (:date, :type, :montant, :reference_id, :reference_type, :commentaire)");
        $params = [
            'date' => $date,
            'type' => $type,
            'montant' => $montant,
            'reference_id' => $reference_id,
            'reference_type' => $reference_type,
            'commentaire' => $comment
        ];
        $stmt->execute($params);
        if (function_exists('logMsg')) {
            logMsg('Dépense insérée : ' . json_encode($params), $logFile);
        }
        mettreAJourBalanceCommerciale();
    } catch (PDOException $e) {
        error_log("❌ ERREUR SQL dans mettreAJourDepenses: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Recalcule la balance commerciale à partir des recettes et dépenses.
 * À appeler après chaque ajout de recette ou dépense.
 */
function mettreAJourBalanceCommerciale($commentaire = '') {
    global $pdo;
    $recettes = $pdo->query('SELECT SUM(montant) FROM finances_recettes')->fetchColumn();
    $depenses = $pdo->query('SELECT SUM(montant) FROM finances_depenses')->fetchColumn();
    $balance = round(floatval($recettes) - floatval($depenses), 2);
    $stmt = $pdo->prepare('UPDATE BALANCE_COMMERCIALE SET balance_actuelle = :balance, derniere_maj = NOW(), commentaire = :commentaire WHERE id = 1');
    $stmt->execute(['balance' => $balance, 'commentaire' => $commentaire]);
}
