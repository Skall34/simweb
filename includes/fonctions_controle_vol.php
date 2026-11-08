<?php
/*
-------------------------------------------------------------
 Fichier : fonctions_controle_vol.php
 Emplacement : includes/

 Description :
 Fonctions partagées pour le vol de contrôle post-maintenance.
 Un appareil qui sort de maintenance (scripts/maintenance.php, crash)
 a FLOTTE.controle_a_faire = 1 et n'est réutilisable que pour une
 mission marquée MISSIONS.est_controle = 1, avec :
  - au moins une limite de décrochage (champ stall_ok)
  - pour les bimoteurs, un arrêt moteur (champ engine_cut_ok)
 Utilisé par api/api_import_vol_direct.php et pages/saisie_manuelle.php.
-------------------------------------------------------------
*/

require_once __DIR__ . '/log_func.php';
require_once __DIR__ . '/fonctions_maintenance.php';

/**
 * Retourne true si le libellé de mission correspond à la mission de contrôle.
 */
function missionEstControle($pdo, string $missionLibelle): bool {
    $stmt = $pdo->prepare("SELECT est_controle FROM MISSIONS WHERE libelle = :libelle");
    $stmt->execute(['libelle' => $missionLibelle]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row && (int)$row['est_controle'] === 1;
}

/**
 * Retourne true si l'appareil est en attente d'un vol de contrôle.
 */
function avionEnAttenteControle($pdo, int $appareilId): bool {
    $stmt = $pdo->prepare("SELECT controle_a_faire FROM FLOTTE WHERE id = :id");
    $stmt->execute(['id' => $appareilId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row && (int)$row['controle_a_faire'] === 1;
}

/**
 * Retourne la catégorie de l'appareil (FLEET_TYPE.type : Monomoteur, Bimoteur, Liner, Helico).
 */
function getTypeAppareil($pdo, int $appareilId): string {
    $stmt = $pdo->prepare("SELECT ft.type FROM FLOTTE f LEFT JOIN FLEET_TYPE ft ON f.fleet_type = ft.id WHERE f.id = :id");
    $stmt->execute(['id' => $appareilId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (string)$row['type'] : '';
}

/**
 * Valide un vol au regard du vol de contrôle post-maintenance.
 *
 * Règles :
 * - Si l'appareil a controle_a_faire = 1, seule une mission de contrôle est acceptée.
 * - Si la mission est une mission de contrôle, le vol doit déclarer au moins
 *   une limite de décrochage (stall_ok), et un arrêt moteur (engine_cut_ok)
 *   si l'appareil est bimoteur.
 *
 * @param PDO    $pdo           Connexion PDO
 * @param int    $appareilId    ID de l'appareil (FLOTTE.id)
 * @param string $missionLibelle Libellé de la mission choisie
 * @param bool   $stallOk       Le pilote déclare avoir effectué une limite de décrochage
 * @param bool   $engineCutOk   Le pilote déclare avoir effectué un arrêt moteur
 * @param string &$erreur       Motif de rejet renseigné si retour false
 * @return bool true si le vol est accepté, false sinon (voir $erreur)
 */
function verifierVolControle($pdo, int $appareilId, string $missionLibelle, bool $stallOk, bool $engineCutOk, &$erreur = null): bool {
    $estControle = missionEstControle($pdo, $missionLibelle);
    $enAttente = avionEnAttenteControle($pdo, $appareilId);

    if ($enAttente && !$estControle) {
        $erreur = "Appareil indisponible : après une maintenance, seul un vol avec la mission de contrôle est autorisé.";
        return false;
    }

    if ($estControle) {
        if (!$stallOk) {
            $erreur = "Vol de contrôle invalide : au moins une limite de décrochage (stall) est requise.";
            return false;
        }
        if (getTypeAppareil($pdo, $appareilId) === 'Bimoteur' && !$engineCutOk) {
            $erreur = "Vol de contrôle invalide : un arrêt moteur est requis pour un bimoteur.";
            return false;
        }
    }

    return true;
}

/**
 * Valide le vol de contrôle et lève le flag : l'appareil redevient disponible.
 * Appelé uniquement après l'enregistrement réussi du vol dans le carnet de vol.
 *
 * @param PDO    $pdo        Connexion PDO
 * @param int    $appareilId ID de l'appareil (FLOTTE.id)
 * @param string $immat       Immatriculation (pour le log)
 * @param int    $volId      ID du vol enregistré (CARNET_DE_VOL_GENERAL.id)
 * @param string $logFile    Chemin du fichier de log
 */
function marquerControleEffectue($pdo, int $appareilId, string $immat, $volId, string $logFile): void {
    $stmt = $pdo->prepare("UPDATE FLOTTE SET controle_a_faire = 0 WHERE id = :id");
    $stmt->execute(['id' => $appareilId]);
    logMaintenance($pdo, $appareilId, 'controle', 100, 100, null, "Vol de contrôle effectué (vol #$volId) — $immat remis en service", $logFile);
    logMsg("[controle] Vol de contrôle validé pour $immat (vol #$volId), appareil remis en service", $logFile);
}
