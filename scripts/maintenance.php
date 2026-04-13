<?php
/*
-------------------------------------------------------------
 Script : maintenance.php
 Emplacement : scripts/

 Description :
 Ce script gère la maintenance automatique des appareils de la flotte.
 Il traite l'usure normale, la sortie de maintenance, et la maintenance après crash (3 jours).
 Chaque entrée en maintenance génère un coût financier :
 - Usure normale : cout_maintenance du type d'appareil (FLEET_TYPE)
 - Crash : cout_maintenance × multiplicateur_crash (VARIABLES_CONFIG)
 Le coût est enregistré dans finances_depenses et impacte la balance commerciale.
 Les opérations et erreurs sont loguées dans scripts/logs/maintenance.log via logMsg().

 Notification :
 Un mail récapitulatif automatique est envoyé à l'administrateur à la fin du script pour indiquer le succès du traitement.

 Fonctionnement :
 1. Sélectionne tous les appareils actifs dans FLOTTE (avec jointure FLEET_TYPE).
 2. Récupère le multiplicateur crash depuis VARIABLES_CONFIG.
 3. Pour chaque appareil :
    - Si usure < 10% et statut normal, passage en maintenance + coût maintenance.
    - Si en maintenance, sortie ou réinitialisation selon compteur.
    - Si crash, passage en maintenance crash (3 jours) + coût maintenance × multiplicateur.
 4. Logue chaque étape et erreur dans le fichier log.
 5. Envoie un mail récapitulatif automatique à la fin du script.

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
require_once __DIR__ . '/../includes/fonctions_financieres.php';
require_once __DIR__ . '/../lang.php';
require_once __DIR__ . '/../includes/config.php';
if (!isset($_SESSION['lang'])) $_SESSION['lang'] = VA_DEFAULT_LANGUAGE;

$logFile = __DIR__ . '/logs/maintenance.log';

date_default_timezone_set('Europe/Paris');

// Fonction pour logger dans MAINTENANCES_LOG
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

try {
    logMsg("--- Début maintenance ---", $logFile);

    // Récupérer le multiplicateur crash depuis VARIABLES_CONFIG
    $multiplicateur_crash = 3; // valeur par défaut
    $stmtMult = $pdo->prepare("SELECT valeur FROM VARIABLES_CONFIG WHERE nom = 'multiplicateur_crash'");
    if ($stmtMult->execute()) {
        $valMult = $stmtMult->fetchColumn();
        if ($valMult !== false && is_numeric($valMult)) {
            $multiplicateur_crash = intval($valMult);
        }
    }
    logMsg("Multiplicateur crash : ×$multiplicateur_crash", $logFile);

    // Récupérer tous les avions avec le coût de maintenance du type
    $stmt = $pdo->query("
        SELECT f.id, f.immat, f.status, f.etat, f.compteur_immo, f.nb_maintenance,
               ft.cout_maintenance, ft.fleet_type AS type_nom
        FROM FLOTTE f
        LEFT JOIN FLEET_TYPE ft ON f.fleet_type = ft.id
        WHERE f.actif = 1
    ");
    $flottes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count_entree = 0;
    $count_sortie = 0;
    $entree_immat = [];
    $sortie_immat = [];
    $cout_total = 0;
    $details_couts = [];

    foreach ($flottes as $avion) {
        $id = $avion['id'];
        $immat = $avion['immat'];
        $status = (int)$avion['status'];
        $etat = (float)$avion['etat'];
        $compteur_immo = (int)$avion['compteur_immo'];
        $nb_maintenance = (int)$avion['nb_maintenance'];
        $cout_maintenance = floatval($avion['cout_maintenance'] ?? 0);
        $type_nom = $avion['type_nom'] ?? '';

        if ($immat !== '') {
            logMsg("Avion $immat ($type_nom) : état=$etat / statut=$status / compteur_immo=$compteur_immo / coût_maint=$cout_maintenance", $logFile);

            if ($status === 0 && $etat < 10) {
                // Entrée en maintenance usure normale
                logMsg("L'avion $immat passe en maintenance (usure normale)", $logFile);
                $sql = "UPDATE FLOTTE SET status = 1, etat = 0, compteur_immo = 1, nb_maintenance = (nb_maintenance + 1) WHERE id = :id";
                $stmtUp = $pdo->prepare($sql);
                $stmtUp->execute(['id' => $id]);
                $count_entree++;
                $entree_immat[] = $immat;

                // Coût financier maintenance usure
                if ($cout_maintenance > 0) {
                    $commentaire = "Maintenance usure — $immat ($type_nom)";
                    mettreAJourDepenses($cout_maintenance, $id, $immat, 'SYSTEM', 'maintenance', $commentaire);
                    $cout_total += $cout_maintenance;
                    $details_couts[] = "$immat : " . number_format($cout_maintenance, 2, ',', ' ') . " € (usure)";
                    logMsg("Coût maintenance enregistré : $cout_maintenance € pour $immat (usure)", $logFile);
                    // Log dans MAINTENANCES_LOG
                    logMaintenance($pdo, $id, 'usure', (int)$etat, 0, $cout_maintenance, $commentaire, $logFile);
                }

            } elseif ($status === 1) {
                if ($compteur_immo === 1) {
                    logMsg("L'avion $immat sort de maintenance après 1 jour (usure)", $logFile);
                    $sql = "UPDATE FLOTTE SET status = 0, etat = 100, compteur_immo = 0 WHERE id = :id";
                    $stmtUp = $pdo->prepare($sql);
                    $stmtUp->execute(['id' => $id]);
                    $count_sortie++;
                    $sortie_immat[] = $immat;
                    // Log sortie maintenance
                    logMaintenance($pdo, $id, 'sortie', 0, 100, null, "Sortie maintenance usure — $immat", $logFile);
                } elseif ($compteur_immo > 1) {
                    logMsg("L'avion $immat en maintenance, compteur_immo > 1, réinitialisation", $logFile);
                    $sql = "UPDATE FLOTTE SET status = 0, etat = 1, compteur_immo = 0 WHERE id = :id";
                    $stmtUp = $pdo->prepare($sql);
                    $stmtUp->execute(['id' => $id]);
                    $count_sortie++;
                    $sortie_immat[] = $immat;
                    // Log sortie maintenance
                    logMaintenance($pdo, $id, 'sortie', 0, 1, null, "Sortie maintenance (réinit.) — $immat", $logFile);
                }

            } elseif ($status === 2) {
                if ($compteur_immo === 0) {
                    // Entrée en maintenance crash
                    logMsg("L'avion $immat a subi un crash. Passage en maintenance crash (3 jours)", $logFile);
                    $sql = "UPDATE FLOTTE SET compteur_immo = 1, nb_maintenance = (nb_maintenance + 1) WHERE id = :id";
                    $stmtUp = $pdo->prepare($sql);
                    $stmtUp->execute(['id' => $id]);
                    $count_entree++;
                    $entree_immat[] = $immat;

                    // Coût financier maintenance crash (× multiplicateur)
                    if ($cout_maintenance > 0) {
                        $cout_crash = round($cout_maintenance * $multiplicateur_crash, 2);
                        $commentaire = "Maintenance crash (×$multiplicateur_crash) — $immat ($type_nom)";
                        mettreAJourDepenses($cout_crash, $id, $immat, 'SYSTEM', 'maintenance_crash', $commentaire);
                        $cout_total += $cout_crash;
                        $details_couts[] = "$immat : " . number_format($cout_crash, 2, ',', ' ') . " € (crash ×$multiplicateur_crash)";
                        logMsg("Coût maintenance crash enregistré : $cout_crash € pour $immat (×$multiplicateur_crash)", $logFile);
                        // Log dans MAINTENANCES_LOG
                        logMaintenance($pdo, $id, 'crash', (int)$etat, 0, $cout_crash, $commentaire, $logFile);
                    }

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
                    // Log sortie maintenance crash
                    logMaintenance($pdo, $id, 'sortie_crash', 0, 100, null, "Sortie maintenance crash — $immat", $logFile);
                }
            }
        }
    }
    // Récapitulatif
    $cout_total_fmt = number_format($cout_total, 2, ',', ' ');
    $logRecap = "--- Maintenance flotte ---\n";
    $logRecap .= "Appareils entrés en maintenance : $count_entree";
    if ($count_entree > 0) {
        $logRecap .= "\n - " . implode(', ', $entree_immat);
    }
    $logRecap .= "\nAppareils sortis de maintenance : $count_sortie";
    if ($count_sortie > 0) {
        $logRecap .= "\n - " . implode(', ', $sortie_immat);
    }
    $logRecap .= "\nCoût total maintenance : $cout_total_fmt €";
    if (!empty($details_couts)) {
        $logRecap .= "\n" . implode("\n", array_map(fn($d) => " - $d", $details_couts));
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
    $message .= "\nCoût total maintenance : $cout_total_fmt €";
    echo $message . "\n";
    // Envoi du mail recapitulatif enrichi
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
        $body .= "\n\nCoût total maintenance : $cout_total_fmt €";
        if (!empty($details_couts)) {
            $body .= "\nDétail :\n - " . implode("\n - ", $details_couts);
        }
        $body .= "\n\nCeci est un message automatique.";
        $to = VA_ADMIN_EMAIL;
        $mailResult = sendSummaryMail($subject, $body, $to);
        if ($mailResult === true || $mailResult === null || (is_array($mailResult) && !empty($mailResult['success']))) {
            logMsg("Mail recapitulatif envoye a $to", $logFile);
        } else {
            $errMsg = is_array($mailResult) ? (isset($mailResult['error']) ? $mailResult['error'] : json_encode($mailResult, JSON_UNESCAPED_UNICODE)) : (string)$mailResult;
            logMsg("Erreur lors de l'envoi du mail recapitulatif : $errMsg", $logFile);
        }
    }
} catch (PDOException $e) {
    logMsg("Erreur lors de la maintenance : " . $e->getMessage(), $logFile);
    echo t('cli_error') . " " . $e->getMessage() . "\n";
}
