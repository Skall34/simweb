<?php
/**
 * Script : promotion_grades_pilotes.php
 * --------------------------------------
 * Fonction métier :
 *   Automatise la promotion des pilotes selon leurs heures de vol cumulées.
 *   Met à jour le grade, envoie un mail de notification au pilote promu, logue chaque promotion et envoie un récapitulatif à l'administrateur.
 *
 * Automatisation :
 *   - Calcul des heures de vol pour chaque pilote
 *   - Détermination du grade éligible et mise à jour en base
 *   - Envoi d'un mail de notification au pilote promu
 *   - Log des promotions dans le fichier dédié
 *   - Envoi d'un mail récapitulatif à l'administrateur
 *
 * Accès :
 *   Via CRON (hébergeur) : https://votresite.com/scripts/promotion_grades_pilotes.php?token=VOTRE_TOKEN_SECRET
 *   Mode dry-run : https://votresite.com/scripts/promotion_grades_pilotes.php?token=VOTRE_TOKEN_SECRET&dry-run=1
 */

// Protection par token
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/mail_utils.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../lang.php';

// Token attendu — à définir dans config.php via : define('CRON_SECRET_TOKEN', 'votre_token');
if (!defined('CRON_SECRET_TOKEN')) {
    define('CRON_SECRET_TOKEN', 'monTokenSecret2026XyZ987');
}

// Accepte le token via HTTP (?token=...) ou en CLI (php script.php votre_token)
$provided_token = php_sapi_name() === 'cli'
    ? ($argv[1] ?? '')
    : ($_GET['token'] ?? '');

if ($provided_token !== CRON_SECRET_TOKEN) {
    if (php_sapi_name() !== 'cli') {
        http_response_code(403);
    }
    die('Accès refusé');
}

// Définir la langue par défaut pour les scripts (pas de session)
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = VA_DEFAULT_LANGUAGE;
}

// Mode dry run (simuler sans modifier la base ni envoyer de mails)
// Supporte les deux modes : ligne de commande (php script.php token --dry-run) et HTTP (?dry-run=1)
$dryRun = (isset($argv[2]) && $argv[2] === '--dry-run') || (isset($_GET['dry-run']) && $_GET['dry-run'] == '1');

// Définir le type de sortie
header('Content-Type: text/plain; charset=utf-8');

if ($dryRun) {
    echo "========================================\n";
    echo "MODE DRY RUN - Simulation sans modification\n";
    echo "========================================\n\n";
}

// Récupérer les grades et seuils depuis la base de données
$stmtGrades = $pdo->query("SELECT id, nom, niveau, seuil_heures FROM GRADES ORDER BY niveau ASC");
$gradesData = $stmtGrades->fetchAll(PDO::FETCH_ASSOC);

// Construction du tableau de correspondance niveau => seuil heures
$grades = [];
foreach ($gradesData as $grade) {
    $grades[$grade['id']] = [
        'seuil' => (int)$grade['seuil_heures'],
        'nom' => $grade['nom'],
        'niveau' => $grade['niveau']
    ];
}

logMsg('[PROMOTION] Début du script de promotion automatique' . ($dryRun ? ' (DRY RUN)' : ''), __DIR__ . '/logs/promotion_grades.log');
if ($dryRun) {
    echo "Grades chargés depuis la base :\n";
    foreach ($grades as $id => $data) {
        echo "  - Grade #{$id} '{$data['nom']}' (niveau {$data['niveau']}) : seuil {$data['seuil']}h\n";
    }
    echo "\n";
}
$stmtPilotes = $pdo->query("SELECT id, email, grade_id, prenom, nom, callsign FROM PILOTES");
$pilotes = $stmtPilotes->fetchAll(PDO::FETCH_ASSOC);

// Initialiser le tableau des promotions
$promotions = [];

foreach ($pilotes as $pilote) {
    // Calculer le total d'heures de vol
    $stmtHeures = $pdo->prepare("SELECT SUM(TIME_TO_SEC(temps_vol)) FROM CARNET_DE_VOL_GENERAL WHERE pilote_id = ?");
    $stmtHeures->execute([$pilote['id']]);
    $total_sec = (int)$stmtHeures->fetchColumn();
    $total_heures = $total_sec / 3600;

    // Déterminer le grade éligible (le plus haut niveau accessible)
    $nouveau_grade_id = $pilote['grade_id'];
    $nouveau_grade_data = null;
    $niveau_actuel = $grades[$pilote['grade_id']]['niveau'] ?? 0;
    
    foreach ($grades as $grade_id => $grade_data) {
        if ($total_heures >= $grade_data['seuil'] && $grade_data['niveau'] > ($grades[$nouveau_grade_id]['niveau'] ?? 0)) {
            $nouveau_grade_id = $grade_id;
            $nouveau_grade_data = $grade_data;
        }
    }

    // Si le grade doit être augmenté (comparaison des NIVEAUX, pas des IDs)
    if ($nouveau_grade_data !== null && $nouveau_grade_data['niveau'] > $niveau_actuel) {
        $grade_nom = $nouveau_grade_data['nom'];
        
        if ($dryRun) {
            echo "[DRY RUN] Promotion détectée : {$pilote['callsign']} ({$pilote['prenom']} {$pilote['nom']})\n";
            echo "  - Heures de vol : " . number_format($total_heures, 2) . "h\n";
            echo "  - Grade actuel : #{$pilote['grade_id']}\n";
            echo "  - Nouveau grade : #{$nouveau_grade_id} '{$grade_nom}'\n\n";
        } else {
            // Mettre à jour le grade
            $stmtUpdate = $pdo->prepare("UPDATE PILOTES SET grade_id = ? WHERE id = ?");
            $stmtUpdate->execute([$nouveau_grade_id, $pilote['id']]);

            // Envoyer un mail de notification au pilote
            $to = $pilote['email'];
            $subject = t('script_promotion_subject', ['grade' => $grade_nom]);
            $message = t('script_promotion_greeting', ['firstname' => htmlspecialchars($pilote['prenom']), 'lastname' => htmlspecialchars($pilote['nom'])]) . "<br><br>";
            $message .= t('script_promotion_congrats', ['grade' => $grade_nom]) . "<br>";
            $message .= t('script_promotion_continue') . "<br><br>";
            $message .= t('script_promotion_team');
            
            $mailResult = sendSummaryMail($subject, $message, $to);
            if ($mailResult === true || $mailResult === null || (is_array($mailResult) && !empty($mailResult['success']))) {
                logMsg("Mail de promotion envoye a $to", __DIR__ . '/logs/promotion_grades.log');
            } else {
                // Extraire le message d'erreur de façon robuste
                if (is_array($mailResult)) {
                    $errMsg = isset($mailResult['error']) ? $mailResult['error'] : json_encode($mailResult, JSON_UNESCAPED_UNICODE);
                } else {
                    $errMsg = (string)$mailResult;
                }
                logMsg("Erreur lors de l'envoi du mail de promotion a $to : $errMsg", __DIR__ . '/logs/promotion_grades.log');
            }
        }

        // Logger et ajouter au tableau de promotions
        $promotions[] = "- {$pilote['callsign']} ({$pilote['prenom']} {$pilote['nom']}) : {$grade_nom} (" . number_format($total_heures, 2) . "h)\n";
        logMsg("[PROMOTION] {$pilote['callsign']} promu au grade {$grade_nom}", __DIR__ . '/logs/promotion_grades.log');
    }
}

// Envoyer le mail récapitulatif à l'administrateur
if (!$dryRun) {
    if (!empty($promotions)) {
        $subject = t('script_promotion_recap_subject');
        $body = t('script_promotion_recap_greeting') . "<br><br>";
        $body .= t('script_promotion_recap_intro') . "<br><pre>" . implode("", $promotions) . "</pre><br>";
        $body .= t('script_promotion_recap_signature');
        $mailResult = sendSummaryMail($subject, $body, VA_ADMIN_EMAIL);
        if ($mailResult === true || $mailResult === null || (is_array($mailResult) && !empty($mailResult['success']))) {
            logMsg("Mail recapitulatif envoye a " . VA_ADMIN_EMAIL, __DIR__ . '/logs/promotion_grades.log');
        } else {
            if (is_array($mailResult)) {
                $errMsg = isset($mailResult['error']) ? $mailResult['error'] : json_encode($mailResult, JSON_UNESCAPED_UNICODE);
            } else {
                $errMsg = (string)$mailResult;
            }
            logMsg("Erreur lors de l'envoi du mail recapitulatif : $errMsg", __DIR__ . '/logs/promotion_grades.log');
        }
    } else {
        $subject = t('script_promotion_recap_subject');
        $body = t('script_promotion_recap_greeting') . "<br><br>";
        $body .= t('script_promotion_recap_none') . "<br><br>";
        $body .= t('script_promotion_recap_signature');
        $mailResult = sendSummaryMail($subject, $body, VA_ADMIN_EMAIL);
        if ($mailResult === true || $mailResult === null || (is_array($mailResult) && !empty($mailResult['success']))) {
            logMsg("Mail récapitulatif (aucune promotion) envoyé à " . VA_ADMIN_EMAIL, __DIR__ . '/logs/promotion_grades.log');
        } else {
            if (is_array($mailResult)) {
                $errMsg = isset($mailResult['error']) ? $mailResult['error'] : json_encode($mailResult, JSON_UNESCAPED_UNICODE);
            } else {
                $errMsg = (string)$mailResult;
            }
            logMsg("Erreur lors de l'envoi du mail récapitulatif (aucune promotion) : $errMsg", __DIR__ . '/logs/promotion_grades.log');
        }
    }
}

logMsg('[PROMOTION] Fin du script de promotion automatique' . ($dryRun ? ' (DRY RUN)' : ''), __DIR__ . '/logs/promotion_grades.log');

if ($dryRun) {
    echo "\n========================================\n";
    echo "RÉSUMÉ DRY RUN\n";
    echo "========================================\n";
    echo "Total des promotions détectées : " . count($promotions) . "\n";
    if (!empty($promotions)) {
        echo "\nListe des promotions :\n";
        foreach ($promotions as $promo) {
            echo $promo;
        }
    }
    echo "\nAucune modification n'a été effectuée en base.\n";
    echo "Aucun mail n'a été envoyé.\n";
} else {
    echo "Promotions terminées. " . count($promotions) . " promotion(s) effectuée(s).\n";
}
