<?php
/*
-------------------------------------------------------------
 Script : credit_mensualite.php
 Emplacement : scripts/


 Description :
 Ce script calcule et applique chaque mois les mensualités des appareils achetés à crédit par la compagnie aérienne virtuelle.
 Il met à jour les champs financiers de chaque appareil concerné dans la table FLOTTE.

 Log :
 Toutes les opérations et vérifications sont enregistrées dans scripts/logs/credit_mensualite.log.

 Notification :
 Un mail récapitulatif automatique est envoyé à l'administrateur à la fin du script pour indiquer le succès du traitement et le nombre d'appareils mis à jour.

 Fonctionnement :
 1. Sélectionne tous les appareils à crédit (nb_annees_credit > 0 et reste_a_payer > 0).
 2. Pour chaque appareil :
    - Décrémente le nombre d'années de crédit en janvier.
    - Calcule la mensualité selon le taux et la durée restante.
    - Met à jour les champs traite_payee_cumulee, reste_a_payer et remboursement.
    - Logue chaque opération et vérifie la cohérence des montants.
 3. Logue le nombre d'appareils mis à jour et toute anomalie détectée.
 4. Envoie un mail récapitulatif automatique à la fin du script.

 Utilisation :
 - À lancer une fois par mois (cron ou manuel).
 - Vérifier le log en cas d'anomalie ou d'alerte.

 Auteur :
 - Automatisé avec GitHub Copilot
-------------------------------------------------------------
*/

$mailSummaryEnabled = true; // Active l'envoi du mail récapitulatif
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../includes/mail_utils.php';
$logFile = __DIR__ . '/logs/credit_mensualite.log';
logMsg("--- Démarrage du script de mensualités crédit ---", $logFile);
logMsg("--- Script credit_mensualite.php lancé ---", $logFile);
echo "--- Script credit_mensualite.php lancé ---\n";

try {
    // Sélectionner tous les avions à crédit dans FLOTTE
    $sql = "SELECT * FROM FLOTTE WHERE nb_annees_credit > 0 AND reste_a_payer > 0";
    $stmt = $pdo->query($sql);
    $finances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = 0;
    $immat_mis_a_jour = [];
    $erreurs_coherence = [];
    $mois_courant = date('n');
    if (count($finances) == 0) {
        logMsg("Aucun appareil à crédit à traiter.", $logFile);
        echo "Aucun appareil à crédit à traiter.\n";
    }
    foreach ($finances as $row) {
        $avion_id = $row['id'];
        $immat = $row['immat'];
        $nb_annees_credit = $row['nb_annees_credit'];
        // Si le crédit est terminé, on ne fait rien
        if ($nb_annees_credit <= 0) {
            logMsg("Avion $immat : crédit terminé, aucune opération.", $logFile);
            continue;
        }
        // Si on est en janvier, on retire une année
        if ($mois_courant == 1) {
            $nouvelle_annee = $nb_annees_credit - 1;
            if ($nouvelle_annee < 0) $nouvelle_annee = 0;
            $sqlAnnee = "UPDATE FLOTTE SET nb_annees_credit = :annee WHERE id = :avion_id";
            $stmtAnnee = $pdo->prepare($sqlAnnee);
            $stmtAnnee->execute([
                'annee' => $nouvelle_annee,
                'avion_id' => $avion_id
            ]);
            logMsg("Avion $immat : décrément nb_annees_credit à $nouvelle_annee (janvier)", $logFile);
            // Si après décrément on est à 0, on ne fait plus rien
            if ($nouvelle_annee == 0) {
                logMsg("Avion $immat : crédit terminé après décrément, aucune opération.", $logFile);
                continue;
            }
            $nb_annees_credit = $nouvelle_annee;
        }
        $nb_mensualites = $nb_annees_credit * 12;
        $taux_mensuel = $row['taux_percent'] / 100 / 12;
        $reste_initial = $row['traite_payee_cumulee'] + $row['reste_a_payer'];
        if ($nb_mensualites > 0 && $taux_mensuel > 0) {
            $mensualite = $reste_initial * ($taux_mensuel / (1 - pow(1 + $taux_mensuel, -$nb_mensualites)));
            $mensualite = round($mensualite, 2);
            $nouveau_traite = $row['traite_payee_cumulee'] + $mensualite;
            $nouveau_reste = $row['reste_a_payer'] - $mensualite;
            if ($nouveau_reste < 0) $nouveau_reste = 0;
            $nouveau_remboursement = $nouveau_traite + $nouveau_reste;
            // Mise à jour en base
            $sqlUpdate = "UPDATE FLOTTE SET traite_payee_cumulee = :traite, reste_a_payer = :reste, remboursement = :remboursement WHERE id = :avion_id";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([
                'traite' => $nouveau_traite,
                'reste' => $nouveau_reste,
                'remboursement' => $nouveau_remboursement,
                'avion_id' => $avion_id
            ]);
            $logDetail = "Appareil $immat : mensualité=$mensualite, traite_payee_cumulee=$nouveau_traite, reste_a_payer=$nouveau_reste, remboursement=$nouveau_remboursement";
            // Vérification de cohérence
            if (abs($nouveau_remboursement - ($nouveau_traite + $nouveau_reste)) > 0.01) {
                $logDetail .= " [ERREUR: remboursement != traite_payee_cumulee + reste_a_payer]";
                $erreurs_coherence[] = $immat;
            }
            logMsg($logDetail, $logFile);
            $count++;
            $immat_mis_a_jour[] = $immat;
        } else {
            logMsg("Avion $immat : paramètres de crédit invalides (nb_mensualites=$nb_mensualites, taux_mensualite=$taux_mensualite)", $logFile);
        }
    }
    logMsg("Traitement terminé. $count appareils mis à jour.", $logFile);
    // Affichage récapitulatif pour l'admin
    $message = "Traitement des mensualités crédit terminé.\n";
    $message .= "Appareils mis à jour : $count";
    if ($count > 0) {
        $message .= "\n - " . implode(', ', $immat_mis_a_jour);
    }
    if (count($erreurs_coherence) > 0) {
        $message .= "\n[ALERTE] Erreur de cohérence détectée pour : " . implode(', ', $erreurs_coherence);
    }
    echo $message . "\n";
    // Envoi du mail récapitulatif enrichi
    if ($mailSummaryEnabled && function_exists('sendSummaryMail')) {
        $subject = "[SimWeb] Rapport mensualités crédit - " . date('d/m/Y H:i');
        $body = "Bonjour,\n\nLe traitement des mensualités crédit s'est terminé avec succès.";
        $body .= "\nAppareils mis à jour : $count";
        if ($count > 0) {
            $body .= "\n - " . implode(', ', $immat_mis_a_jour);
        }
        if (count($erreurs_coherence) > 0) {
            $body .= "\n\n[ALERTE] Erreur de cohérence détectée pour : " . implode(', ', $erreurs_coherence);
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
    logMsg("Erreur SQL : " . $e->getMessage(), $logFile);
    echo "Erreur SQL : " . $e->getMessage() . "\n";
}

// Fin du script
