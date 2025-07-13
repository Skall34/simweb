<?php
/*
-------------------------------------------------------------
 Script : maintenance.php
 Emplacement : scripts/

 Description :
 Ce script gère la maintenance automatique des appareils de la flotte.
 Il traite l'usure normale, la sortie de maintenance, et la maintenance après crash (3 jours).
 Les opérations et erreurs sont loguées dans scripts/logs/maintenance.log via logMsg().

 Notification :
 Un mail récapitulatif automatique est envoyé à l'administrateur à la fin du script pour indiquer le succès du traitement.

 Fonctionnement :
 1. Sélectionne tous les appareils actifs dans FLOTTE.
 2. Pour chaque appareil :
    - Si usure < 30% et statut normal, passage en maintenance.
    - Si en maintenance, sortie ou réinitialisation selon compteur.
    - Si crash, passage en maintenance crash et gestion du compteur sur 3 jours.
 3. Logue chaque étape et erreur dans le fichier log.
 4. Envoie un mail récapitulatif automatique à la fin du script.

 Utilisation :
 - À lancer régulièrement pour automatiser la gestion de la maintenance.
 - Vérifier le log en cas d'anomalie ou d'échec d'opération.

 Auteur :
 - Automatisé avec GitHub Copilot
-------------------------------------------------------------
*/
$mailSummaryEnabled = true; // Active l'envoi du mail récapitulatif
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../includes/mail_utils.php';
$logFile = __DIR__ . '/logs/maintenance.log';

date_default_timezone_set('Europe/Paris');

try {
    logMsg("--- Début maintenance ---", $logFile);
    // Récupérer tous les avions
    $stmt = $pdo->query("SELECT id, immat, status, etat, compteur_immo, nb_maintenance FROM FLOTTE WHERE actif=1");
    $flottes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count_entree = 0;
    $count_sortie = 0;
    $entree_immat = [];
    $sortie_immat = [];
    foreach ($flottes as $avion) {
        $id = $avion['id'];
        $immat = $avion['immat'];
        $status = (int)$avion['status'];
        $etat = (float)$avion['etat'];
        $compteur_immo = (int)$avion['compteur_immo'];
        $nb_maintenance = (int)$avion['nb_maintenance'];
        if ($immat !== '') {
            logMsg("Avion $immat : état=$etat / statut=$status / compteur_immo=$compteur_immo", $logFile);
            if ($status === 0 && $etat < 30) {
                logMsg("L'avion $immat passe en maintenance (usure normale)", $logFile);
                $sql = "UPDATE FLOTTE SET status = 1, etat = 0, compteur_immo = 1, nb_maintenance = (nb_maintenance + 1) WHERE id = :id";
                $stmtUp = $pdo->prepare($sql);
                $stmtUp->execute(['id' => $id]);
                $count_entree++;
                $entree_immat[] = $immat;
            } elseif ($status === 1) {
                if ($compteur_immo === 1) {
                    logMsg("L'avion $immat sort de maintenance après 1 jour (usure)", $logFile);
                    $sql = "UPDATE FLOTTE SET status = 0, etat = 100, compteur_immo = 0 WHERE id = :id";
                    $stmtUp = $pdo->prepare($sql);
                    $stmtUp->execute(['id' => $id]);
                    $count_sortie++;
                    $sortie_immat[] = $immat;
                } elseif ($compteur_immo > 1) {
                    logMsg("L'avion $immat en maintenance, compteur_immo > 1, réinitialisation", $logFile);
                    $sql = "UPDATE FLOTTE SET status = 0, etat = 1, compteur_immo = 0 WHERE id = :id";
                    $stmtUp = $pdo->prepare($sql);
                    $stmtUp->execute(['id' => $id]);
                    $count_sortie++;
                    $sortie_immat[] = $immat;
                }
            } elseif ($status === 2) {
                if ($compteur_immo === 0) {
                    logMsg("L'avion $immat a subi un crash. Passage en maintenance crash (3 jours)", $logFile);
                    $sql = "UPDATE FLOTTE SET compteur_immo = 1, nb_maintenance = (nb_maintenance + 1) WHERE id = :id";
                    $stmtUp = $pdo->prepare($sql);
                    $stmtUp->execute(['id' => $id]);
                    $count_entree++;
                    $entree_immat[] = $immat;
                } elseif ($compteur_immo >= 1 && $compteur_immo < 3) {
                    logMsg("L'avion $immat est en maintenance crash. Incrémentation compteur_immo à " . ($compteur_immo + 1), $logFile);
                    $sql = "UPDATE FLOTTE SET compteur_immo = (compteur_immo + 1) WHERE id = :id";
                    $stmtUp = $pdo->prepare($sql);
                    $stmtUp->execute(['id' => $id]);
                } elseif ($compteur_immo >= 3) {
                    logMsg("L'avion $immat sort de maintenance après crash (3 jours). Réinitialisation compteurs.", $logFile);
                    $sql = "UPDATE FLOTTE SET status = 0, etat = 100, compteur_immo = 0 WHERE id = :id";
                    $stmtUp = $pdo->prepare($sql);
                    $stmtUp->execute(['id' => $id]);
                    $count_sortie++;
                    $sortie_immat[] = $immat;
                }
            }
        }
    }
    // On ne logue plus chaque étape, seulement le récapitulatif
    $logRecap = "--- Maintenance flotte ---\n";
    $logRecap .= "Appareils entrés en maintenance : $count_entree";
    if ($count_entree > 0) {
        $logRecap .= "\n - " . implode(', ', $entree_immat);
    }
    $logRecap .= "\nAppareils sortis de maintenance : $count_sortie";
    if ($count_sortie > 0) {
        $logRecap .= "\n - " . implode(', ', $sortie_immat);
    }
    $logRecap .= "\n------------------------\n";
    logMsg($logRecap, $logFile);
    $message = "Maintenance terminée avec succès.\n";
    $message .= "Appareils entrés en maintenance : $count_entree";
    if ($count_entree > 0) {
        $message .= "\n - " . implode(', ', $entree_immat);
    }
    $message .= "\nAppareils sortis de maintenance : $count_sortie";
    if ($count_sortie > 0) {
        $message .= "\n - " . implode(', ', $sortie_immat);
    }
    echo $message . "\n";
    // Envoi du mail récapitulatif enrichi
    if ($mailSummaryEnabled && function_exists('sendSummaryMail')) {
        $subject = "[SimWeb] Rapport maintenance flotte - " . date('d/m/Y H:i');
        $body = "Bonjour,\n\nLa maintenance automatique de la flotte s'est terminée avec succès.\n";
        $body .= "\nAppareils entrés en maintenance : $count_entree";
        if ($count_entree > 0) {
            $body .= "\n - " . implode(', ', $entree_immat);
        }
        $body .= "\nAppareils sortis de maintenance : $count_sortie";
        if ($count_sortie > 0) {
            $body .= "\n - " . implode(', ', $sortie_immat);
        }
        $body .= "\n\nCeci est un message automatique.";
        $to = ADMIN_EMAIL;
        $mailResult = sendSummaryMail($subject, $body, $to);
        if ($mailResult === true || $mailResult === null) {
            logMsg("Mail récapitulatif envoyé à $to", $logFile);
        } else {
            logMsg("Erreur lors de l'envoi du mail récapitulatif : $mailResult", $logFile);
        }
    }
} catch (PDOException $e) {
    logMsg("Erreur lors de la maintenance : " . $e->getMessage(), $logFile);
    echo "Erreur : " . $e->getMessage() . "\n";
}
