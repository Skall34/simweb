<?php
/*
-------------------------------------------------------------
 Fichier : fonctions_maintenance.php
 Emplacement : includes/

 Description :
 Fonctions partagées pour la gestion de la maintenance des appareils.
 Utilisé par scripts/maintenance.php et api/api_import_vol_direct.php.
-------------------------------------------------------------
*/

/**
 * Enregistre une entrée dans MAINTENANCES_LOG.
 *
 * @param PDO    $pdo           Connexion PDO
 * @param int    $appareil_id   ID de l'appareil (FLOTTE.id)
 * @param string $type          Type de maintenance : 'usure', 'crash', 'sortie', 'sortie_crash'
 * @param int    $etat_avant    État de l'appareil avant maintenance
 * @param int    $etat_apres    État de l'appareil après maintenance
 * @param float|null $cout      Coût de la maintenance (null si pas de coût)
 * @param string $commentaire   Commentaire descriptif
 * @param string $logFile       Chemin du fichier de log
 */
function logMaintenance($pdo, $appareil_id, $type, $etat_avant, $etat_apres, $cout, $commentaire, $logFile) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO MAINTENANCES_LOG (appareil_id, type_maintenance, etat_avant, etat_apres, cout, commentaire)
            VALUES (:appareil_id, :type_maintenance, :etat_avant, :etat_apres, :cout, :commentaire)
        ");
        $stmt->execute([
            'appareil_id' => $appareil_id,
            'type_maintenance' => $type,
            'etat_avant' => $etat_avant,
            'etat_apres' => $etat_apres,
            'cout' => $cout,
            'commentaire' => $commentaire
        ]);
    } catch (PDOException $e) {
        logMsg("Erreur log MAINTENANCES_LOG : " . $e->getMessage(), $logFile);
    }
}
