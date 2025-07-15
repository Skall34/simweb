<?php
/*
-------------------------------------------------------------
 Script : importer_vol.php
 Emplacement : scripts/

 Description :
 Ce script traite les vols ACARS non encore importés dans la base.
 Il vérifie et formate les données, rejette les vols invalides, met à jour le fret, la flotte, les finances, le carnet de vol, et applique l'usure.
 Il marque chaque vol comme traité et met à jour la balance commerciale si besoin.

 Log :
 Toutes les opérations et erreurs sont enregistrées dans scripts/logs/importer_vol.log via logMsg().

 Fonctionnement :
 1. Sélectionne tous les vols non traités dans FROM_ACARS.
 2. Pour chaque vol :
    - Vérifie la validité des données et rejette si besoin (avec log et motif).
    - Met à jour le fret, la flotte, les finances, le carnet de vol, et l'usure.
    - Marque le vol comme traité.
 3. Met à jour la balance commerciale si au moins un vol importé.
 4. Logue chaque étape et erreur dans le fichier log.

 Utilisation :
 - À lancer pour importer les nouveaux vols ACARS.
 - Vérifier le log en cas d'anomalie ou d'échec d'opération.

 Auteur :
 - Automatisé avec GitHub Copilot
-------------------------------------------------------------
*/

$mailSummaryEnabled = true; // Active l'envoi du mail récapitulatif
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../includes/mail_utils.php';
require_once __DIR__ . '/fonctions_importer_vol.php';
require_once __DIR__ . '/calcul_cout.php';

date_default_timezone_set('Europe/Paris');
$logFile = __DIR__ . '/logs/importer_vol.log';

try {
    $stmt = $pdo->query("SELECT * FROM FROM_ACARS WHERE processed = 0 ORDER BY id ASC");
    $vols = $stmt->fetchAll();

    if (empty($vols)) {
        logMsg("Aucun nouveau vol à traiter.", $logFile);
        echo "Aucun nouveau vol à traiter.\n";
        return;
    }

    logMsg("Début du traitement des vols ACARS non traités : " . count($vols) . " vols trouvés.", $logFile);
    $vols_importes = 0;
    $vols_details = [];
    foreach ($vols as $vol) {
        $id = $vol['id'];
        $erreurs = [];

        // 1. Formater et vérifier les données
        $horodateur = $vol['horodateur'] ?: date('Y-m-d H:i:s');
        $callsign = trim($vol['callsign']);
        $immat = trim($vol['immatriculation']);
        $depart = strtoupper(trim($vol['departure_icao']));
        $dest = strtoupper(trim($vol['arrival_icao']));
        $fuelDep = (float) $vol['departure_fuel'];
        $fuelArr = (float) $vol['arrival_fuel'];
        $timeDep = $vol['departure_time'];
        $timeArr = $vol['arrival_time'];
        $payload = (float) $vol['payload'];
        $commentaire = str_replace("\n", ". ", $vol['commentaire'] ?? '');
        $note = (int) $vol['note_du_vol'];
        $mission = $vol['mission'] ?: 'VOLLIBRE';

        // 2. Contrôles basiques
        if (!$callsign || !$immat || !$depart || !$dest) {
            $erreurs[] = "Vol #$id invalide : données manquantes (callsign, immat, depart ou destination)";
        }

        if ($note < 1 || $note > 10) {
            $erreurs[] = "Note du vol invalide ($note) pour le vol #$id";
        }

        // Vérification du pilote
        $stmtPilote = $pdo->prepare("SELECT id FROM PILOTES WHERE callsign = :callsign");
        $stmtPilote->execute(['callsign' => $callsign]);
        $pilote = $stmtPilote->fetch();
        if (!$pilote) {
            $erreurs[] = "Pilote '$callsign' introuvable dans PILOTES.";
        }

        // Vérification de l'avion actif
        $stmtAvion = $pdo->prepare("SELECT id FROM FLOTTE WHERE immat = :immat AND actif = 1");
        $stmtAvion->execute(['immat' => $immat]);
        $avion = $stmtAvion->fetch();
        if (!$avion) {
            $erreurs[] = "Avion '$immat' introuvable ou inactif dans FLOTTE.";
        }

        // Si erreurs, rejeter le vol avec tous les motifs
        if (!empty($erreurs)) {
            foreach ($erreurs as $err) {
                logMsg("❌ $err", $logFile);
            }
            rejeterVol($pdo, $vol, implode(' | ', $erreurs));
            continue;
        }

        // 3. Traitement du fret
        if ($payload > 0) {
            $fret_transporte = deduireFretDepart($depart, $payload);
            ajouterFretDestination($dest, $fret_transporte);
        }

       // 4. Calcul du coût du vol
        $majoration_mission = getMajorationMission($mission);
        $cout_horaire = getCoutHoraire($immat);
        $carburant = $fuelDep - $fuelArr;
        $temps_vol = '00:00:00';
        if ($timeDep && $timeArr) {
            $t1 = new DateTime($timeDep);
            $t2 = new DateTime($timeArr);
            $interval = $t1->diff($t2);
            $temps_vol = $interval->format('%H:%I:%S');
        }
        $cout_vol = calculerRevenuNetVol($payload, $temps_vol, $majoration_mission, $carburant, $note, $cout_horaire);

        // 5. Ajout au carnet de vol avec le coût et temps_vol
        logMsg("Ajout au carnet de vol : callsign=$callsign, immat=$immat, depart=$depart, dest=$dest, payload=$payload, cout_vol=$cout_vol, temps_vol=$temps_vol", $logFile);
        remplirCarnetVolGeneral($horodateur, $callsign, $immat, $depart, $dest, $fuelDep, $fuelArr, $payload, $timeDep, $timeArr, $mission, $commentaire, $note, $cout_vol, $temps_vol);

        // 6. Mise à jour de la flotte
        logMsg("Mise à jour flotte : immat=$immat, fuel=$fuelArr, callsign=$callsign, localisation=$dest", $logFile);
        mettreAJourFlotte($immat, $fuelArr, $callsign, $dest);

        // Mettre à jour finances
        logMsg("Mise à jour finances : immat=$immat, cout_vol=$cout_vol", $logFile);
        mettreAJourFinances($immat, $cout_vol);

        // 7. Usure
        logMsg("Usure avion $immat : note=$note", $logFile);
        deduireUsure($immat, $note);

        // 8. Marquer comme traité
        $updateStmt = $pdo->prepare("UPDATE FROM_ACARS SET processed = 1 WHERE id = :id");
        $updateStmt->execute(['id' => $id]);

        logMsg("✅ Vol #$id traité avec succès (callsign: $callsign)", $logFile);
        $vols_importes++;
        $vols_details[] = [
            'callsign' => $callsign,
            'depart' => $depart,
            'dest' => $dest
        ];
    }

    // Mise à jour de la balance commerciale et envoi du mail si au moins un vol importé
    if ($vols_importes > 0) {
        require_once __DIR__ . '/mise_a_jour_balance.php';
        logMsg("Balance commerciale mise à jour après import.", $logFile);
        // Envoi du mail récapitulatif enrichi
        if ($mailSummaryEnabled && function_exists('sendSummaryMail')) {
            $subject = "[SimWeb] Rapport import vols ACARS - " . date('d/m/Y H:i');
            $body = "Bonjour,\n\nImport des vols ACARS terminé.";
            $body .= "\nNombre de vols importés : $vols_importes";
            if ($vols_importes > 0) {
                $body .= "\nDétail des vols :";
                foreach ($vols_details as $v) {
                    $body .= "\n - Pilote : " . $v['callsign'] . ", Trajet : " . $v['depart'] . " -> " . $v['dest'];
                }
            }
            // Ajout des détails de la balance commerciale
            $balanceLogFile = __DIR__ . '/logs/mise_a_jour_balance.log';
            $balanceDetails = '';
            if (file_exists($balanceLogFile)) {
                $lines = file($balanceLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach (array_reverse($lines) as $line) {
                    if (strpos($line, 'Balance commerciale mise à jour') !== false || strpos($line, 'Balance commerciale créée') !== false) {
                        $balanceDetails = $line;
                        break;
                    }
                }
            }
            if ($balanceDetails) {
                $body .= "\n\nDernière mise à jour balance commerciale :\n" . $balanceDetails;
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
    }

    logMsg("Import terminé.", $logFile);
    echo "✅ Import terminé.\n";
} catch (PDOException $e) {
    logMsg("❌ Erreur DB : " . $e->getMessage(), $logFile);
    echo "Erreur : " . $e->getMessage();
}
