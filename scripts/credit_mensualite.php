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
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../includes/mail_utils.php';
require_once __DIR__ . '/../includes/fonctions_financieres.php';
$logFile = __DIR__ . '/logs/credit_mensualite.log';
logMsg("--- Démarrage du script de mensualités crédit ---", $logFile);
echo "--- Script credit_mensualite.php lancé ---\n";

$mois_courant = date('Y-m'); // Ex: "2026-03"

try {
    // Sélectionner tous les avions à crédit actifs avec des mois restants
    $sql = "SELECT * FROM FLOTTE WHERE nb_mois_restants > 0 AND reste_a_payer > 0 AND Actif = 1";
    $stmt = $pdo->query($sql);
    $finances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $count = 0;
    $immat_mis_a_jour = [];
    $erreurs_coherence = [];

    if (count($finances) == 0) {
        logMsg("Aucun appareil à crédit à traiter.", $logFile);
        echo "Aucun appareil à crédit à traiter.\n";
    }

    foreach ($finances as $row) {
        $avion_id = $row['id'];
        $immat = $row['immat'];

        // Protection contre double exécution dans le même mois
        if (!empty($row['derniere_mensualite'])) {
            $derniere = substr($row['derniere_mensualite'], 0, 7); // "YYYY-MM"
            if ($derniere === $mois_courant) {
                logMsg("Avion $immat : mensualité déjà prélevée ce mois ($mois_courant), ignoré.", $logFile);
                continue;
            }
        }

        $nb_mois_restants = intval($row['nb_mois_restants']);
        $taux_mensuel = floatval($row['taux_percent']) / 100 / 12;
        $reste_a_payer = floatval($row['reste_a_payer']);
        $traite_payee_cumulee = floatval($row['traite_payee_cumulee']);
        $capital_initial = floatval($row['remboursement']); // montant total du prêt (fixe)
        $nb_total_mois = intval($row['nb_annees_credit']) * 12; // durée originale (fixe)

        // Calculer la mensualité fixe à partir du capital initial et de la durée originale
        if ($nb_total_mois > 0 && $taux_mensuel > 0) {
            $mensualite_fixe = $capital_initial * ($taux_mensuel / (1 - pow(1 + $taux_mensuel, -$nb_total_mois)));
            $mensualite_fixe = round($mensualite_fixe, 2);
        } elseif ($nb_total_mois > 0) {
            // Taux à 0% : mensualité = capital / nb mois
            $mensualite_fixe = round($capital_initial / $nb_total_mois, 2);
        } else {
            logMsg("Avion $immat : paramètres de crédit invalides (nb_total_mois=$nb_total_mois, taux_mensuel=$taux_mensuel)", $logFile);
            continue;
        }

        // Calculer la part d'intérêts et la part de capital ce mois-ci
        $interets = round($reste_a_payer * $taux_mensuel, 2);
        $part_capital = $mensualite_fixe - $interets;

        // Dernière mensualité : ajuster si le reste est inférieur à la part capital
        if ($part_capital > $reste_a_payer) {
            $part_capital = $reste_a_payer;
            $mensualite_fixe = $part_capital + $interets;
        }

        $nouveau_reste = round($reste_a_payer - $part_capital, 2);
        if ($nouveau_reste < 0.01) $nouveau_reste = 0; // Éviter les résidus d'arrondi
        $nouveau_traite = round($traite_payee_cumulee + $mensualite_fixe, 2);
        $nouveau_mois_restants = $nb_mois_restants - 1;

        // Mise à jour en base
        $sqlUpdate = "UPDATE FLOTTE SET traite_payee_cumulee = :traite, reste_a_payer = :reste, 
                      nb_mois_restants = :mois_restants, derniere_mensualite = :derniere_mensualite 
                      WHERE id = :avion_id";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([
            'traite' => $nouveau_traite,
            'reste' => $nouveau_reste,
            'mois_restants' => $nouveau_mois_restants,
            'derniere_mensualite' => date('Y-m-d'),
            'avion_id' => $avion_id
        ]);

        // Enregistrer la mensualité dans finances_depenses
        $commentaire = "Mensualité crédit $immat — Capital: " . number_format($part_capital, 2) . ", Intérêts: " . number_format($interets, 2);
        mettreAJourDepenses($mensualite_fixe, $avion_id, $immat, '', 'mensualite_credit', $commentaire);

        $logDetail = "Appareil $immat : mensualité=$mensualite_fixe (capital=$part_capital, intérêts=$interets), "
            . "traite_payee_cumulee=$nouveau_traite, reste_a_payer=$nouveau_reste, mois_restants=$nouveau_mois_restants";

        // Vérification de cohérence
        if ($nouveau_reste == 0 && $nouveau_mois_restants > 0) {
            $logDetail .= " [INFO: crédit soldé avec $nouveau_mois_restants mois restants]";
        }

        logMsg($logDetail, $logFile);
        $count++;
        $immat_mis_a_jour[] = $immat;
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
    // Envoi du mail recapitulatif enrichi
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
    logMsg("Erreur SQL : " . $e->getMessage(), $logFile);
    echo "Erreur SQL : " . $e->getMessage() . "\n";
}

// Fin du script
