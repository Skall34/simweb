<?php
/*
-------------------------------------------------------------
 Script : cleanup_orphan_reservations.php
 Emplacement : scripts/

 Description :
 Script CRON de nettoyage des réservations orphelines.
 Détecte les réservations en statut 'in_flight' ou 'reserved' dont le vol
 correspondant a déjà été enregistré dans CARNET_DE_VOL_GENERAL.
 
 Cas traités :
 - Le client ACARS a soumis le vol mais n'a pas appelé api_complete_reservation
   (timeout réseau, crash du client, etc.)
 - La réservation reste bloquée en 'in_flight' alors que le vol est terminé

 Logique de matching :
 - Même pilote (pilote_id)
 - Même avion (immat = FLOTTE.immat via appareil_id)
 - Aéroports correspondant à la ligne (dans les deux sens)
 - Vol enregistré après la date de réservation

 Fréquence recommandée : toutes les 10-15 minutes

 Utilisation :
 - À lancer régulièrement via CRON (CLI) : php cleanup_orphan_reservations.php
 - Vérifier le log en cas d'anomalie.

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';

$logFile = __DIR__ . '/logs/cleanup_orphan_reservations.log';
$timestamp = date('Y-m-d H:i:s');

logMsg("[$timestamp] === Début du script cleanup_orphan_reservations ===", $logFile);

try {
    // Récupère toutes les réservations actives (reserved ou in_flight)
    $sql = "
        SELECT 
            r.id AS reservation_id,
            r.pilote_id,
            r.immat,
            r.statut,
            r.date_reservation,
            r.date_debut,
            lr.icao_dep,
            lr.icao_arr,
            p.callsign
        FROM RESERVATIONS r
        INNER JOIN LIGNES_REGULIERES lr ON r.ligne_id = lr.id
        INNER JOIN PILOTES p ON r.pilote_id = p.id
        WHERE r.statut IN ('reserved', 'in_flight')
    ";
    
    $stmt = $pdo->query($sql);
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $cleanedCount = 0;
    $checkedCount = count($reservations);
    
    foreach ($reservations as $res) {
        // Cherche un vol correspondant dans CARNET_DE_VOL_GENERAL
        // On match sur : pilote, avion (via FLOTTE), aéroports de la ligne (dans les deux sens)
        // Vol doit être postérieur à la date de réservation
        
        $dateRef = $res['date_debut'] ?? $res['date_reservation'];
        
        $sqlVol = "
            SELECT c.id, c.date_vol, c.heure_arrivee, c.depart, c.destination
            FROM CARNET_DE_VOL_GENERAL c
            INNER JOIN FLOTTE f ON c.appareil_id = f.id
            WHERE c.pilote_id = :pilote_id
              AND f.immat = :immat
              AND (
                  (c.depart = :icao_dep AND c.destination = :icao_arr)
                  OR (c.depart = :icao_arr2 AND c.destination = :icao_dep2)
              )
              AND c.date_vol >= DATE(:date_ref)
            ORDER BY c.date_vol DESC, c.id DESC
            LIMIT 1
        ";
        
        $stmtVol = $pdo->prepare($sqlVol);
        $stmtVol->execute([
            'pilote_id' => $res['pilote_id'],
            'immat' => $res['immat'],
            'icao_dep' => $res['icao_dep'],
            'icao_arr' => $res['icao_arr'],
            'icao_arr2' => $res['icao_arr'],
            'icao_dep2' => $res['icao_dep'],
            'date_ref' => $dateRef
        ]);
        
        $vol = $stmtVol->fetch(PDO::FETCH_ASSOC);
        
        if ($vol) {
            // Un vol correspondant existe ! On complète la réservation
            // Construire la date_fin à partir du vol (date_vol + heure_arrivee)
            $dateFin = $vol['date_vol'];
            if (!empty($vol['heure_arrivee'])) {
                $dateFin .= ' ' . $vol['heure_arrivee'];
            } else {
                $dateFin .= ' 23:59:59'; // Fallback si pas d'heure
            }
            
            $pdo->beginTransaction();
            try {
                // Marquer la réservation comme complétée avec la vraie date de fin du vol
                $updRes = $pdo->prepare("UPDATE RESERVATIONS SET statut = 'completed', date_fin = ? WHERE id = ?");
                $updRes->execute([$dateFin, $res['reservation_id']]);
                
                // Libérer l'avion
                $updFlotte = $pdo->prepare("UPDATE FLOTTE SET reservee = 0 WHERE immat = ?");
                $updFlotte->execute([$res['immat']]);
                
                $pdo->commit();
                $cleanedCount++;
                
                logMsg("[$timestamp] Réservation #{$res['reservation_id']} complétée (pilote: {$res['callsign']}, avion: {$res['immat']}, vol #{$vol['id']} du {$vol['date_vol']})", $logFile);
                
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                logMsg("[$timestamp] ERREUR réservation #{$res['reservation_id']}: " . $e->getMessage(), $logFile);
            }
        }
    }
    
    $msg = "[$timestamp] Terminé - Vérifiées: $checkedCount, Nettoyées: $cleanedCount";
    logMsg($msg, $logFile);
    echo $msg . "\n";
    
} catch (Exception $e) {
    $errMsg = "[$timestamp] ERREUR FATALE: " . $e->getMessage();
    logMsg($errMsg, $logFile);
    echo $errMsg . "\n";
    exit(1);
}
