<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/importer_vol.log');

require_once __DIR__ . '/../includes/db_connect.php';

// Charger les fonctions nécessaires (à créer ensuite)
require_once __DIR__ . '/fonctions_importer_vol.php';
require_once __DIR__ . '/calcul_cout.php';

date_default_timezone_set('Europe/Paris');


try {
    $stmt = $pdo->query("SELECT * FROM FROM_ACARS WHERE processed = 0 ORDER BY id ASC");
    $vols = $stmt->fetchAll();

    if (empty($vols)) {
        log_trace("Aucun nouveau vol à traiter.");
        exit;
    }
    
    log_trace("Début du traitement des vols ACARS non traités : " . count($vols) . " vols trouvés.");
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

        if (!empty($erreurs)) {
            foreach ($erreurs as $err) {
                log_trace("❌ $err");
            }
            rejeterVol($pdo, $vol, implode(' | ', $erreurs));
            continue;
        }

        // Vérification du pilote
        $stmtPilote = $pdo->prepare("SELECT id FROM PILOTES WHERE callsign = :callsign");
        $stmtPilote->execute(['callsign' => $callsign]);
        $pilote = $stmtPilote->fetch();
        if (!$pilote) {
            $motif = "Pilote '$callsign' introuvable dans PILOTES.";
            log_trace("❌ Vol #$id non importé : $motif");
            rejeterVol($pdo, $vol, $motif);
            continue;
        }

        // Vérification de l'avion actif
        $stmtAvion = $pdo->prepare("SELECT id FROM FLOTTE WHERE immat = :immat AND actif = 1");
        $stmtAvion->execute(['immat' => $immat]);
        $avion = $stmtAvion->fetch();
        if (!$avion) {
            $motif = "Avion '$immat' introuvable ou inactif dans FLOTTE.";
            log_trace("❌ Vol #$id non importé : $motif");
            rejeterVol($pdo, $vol, $motif);
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

        // 5. Ajout au carnet de vol avec le coût
        remplirCarnetVolGeneral($horodateur, $callsign, $immat, $depart, $dest, $fuelDep, $fuelArr, $payload, $timeDep, $timeArr, $mission, $commentaire, $note, $cout_vol);

        // 6. Mise à jour de la flotte
        mettreAJourFlotte($immat, $fuelArr, $callsign, $dest);
        
        // Mettre à jour finances
        mettreAJourFinances($immat, $cout_vol);

        // 7. Usure
        deduireUsure($immat, $note);

        // 8. Marquer comme traité
        $updateStmt = $pdo->prepare("UPDATE FROM_ACARS SET processed = 1 WHERE id = :id");
        $updateStmt->execute(['id' => $id]);

        log_trace("✅ Vol #$id traité avec succès (callsign: $callsign)");
    }

    log_trace("Import terminé.");
    echo "✅ Import terminé.\n";
} catch (PDOException $e) {
    log_trace("❌ Erreur DB : " . $e->getMessage());
    echo "Erreur : " . $e->getMessage();
}
