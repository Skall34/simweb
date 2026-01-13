<?php
/*
-------------------------------------------------------------
 Script : fonctions_importer_vol.php
 Emplacement : includes/

 Description :
 Ce fichier regroupe les fonctions utilitaires pour l'import et le traitement des vols dans la compagnie aérienne virtuelle.
 Il gère la mise à jour du fret, du carnet de vol, de la flotte, de l'usure des appareils, et le rejet des vols.

 Log :
 Les étapes et anomalies sont tracées via logMsg() et error_log().

 Principales fonctions :
 - deduireFretDepart : Déduit le fret au départ d'un aéroport.
 - ajouterFretDestination : Ajoute du fret à l'arrivée.
 - remplirCarnetVolGeneral : Insère un vol dans le carnet général.
 - mettreAJourFinances : Met à jour les recettes d'un appareil.
 - mettreAJourFlotte : Met à jour l'état et la localisation d'un appareil.
 - deduireUsure : Applique l'usure selon la note du vol.
 - rejeterVol : Insère un vol rejeté, envoie un mail et supprime le vol ACARS.

 Utilisation :
 - Ces fonctions sont appelées lors de l'import de vols ou du traitement ACARS.
 - Vérifier les logs en cas d'anomalie ou d'échec d'opération.

 Auteur :
 - Automatisé avec GitHub Copilot
-------------------------------------------------------------
*/
require_once __DIR__ . '/../includes/log_func.php';

// Permet de passer dynamiquement le chemin du fichier log depuis le script appelant
if (!isset($logFile)) {
    $logFile = dirname(__DIR__) . '/scripts/logs/import_vol.log';
}

/**
 * Déduit le fret disponible au départ d'un aéroport et le met à jour.
 * @param string $icao Code ICAO de l'aéroport de départ
 * @param float $fret_demande Quantité de fret demandée
 * @return float Quantité de fret effectivement déduite
 */
function deduireFretDepart($icao, $fret_demande, $logFile = null) {
    global $pdo;
    if ($logFile === null) {
        $logFile = dirname(__DIR__) . '/scripts/logs/import_vol.log';
    }
    logMsg("[deduireFretDepart] Déduction fret départ : ICAO=$icao, Demande=$fret_demande", $logFile);

    $stmt = $pdo->prepare("SELECT fret FROM AEROPORTS WHERE ident = :icao");
    $stmt->execute(['icao' => $icao]);
    $result = $stmt->fetch();

    if (!$result) {
        error_log("[deduireFretDepart] ❌ Aéroport de départ inconnu : $icao. Impossible de déduire le fret.");
        return 0;
    }

    $fret_dispo = $result['fret'];
    $fret_effectif = min($fret_dispo, $fret_demande);
    logMsg("[deduireFretDepart] Fret disponible=$fret_dispo, Fret effectif déduit=$fret_effectif", $logFile);

    $update = $pdo->prepare("
        UPDATE AEROPORTS 
        SET fret = GREATEST(fret - :fret, 0) 
        WHERE ident = :icao
    ");
    $update->execute(['fret' => $fret_effectif, 'icao' => $icao]);

    // Lecture du fret mis à jour
    $stmtNew = $pdo->prepare("SELECT fret FROM AEROPORTS WHERE ident = :icao");
    $stmtNew->execute(['icao' => $icao]);
    $newFret = $stmtNew->fetchColumn();

    logMsg("[deduireFretDepart] Nouveau fret restant à $icao : $newFret", $logFile);

    return $fret_effectif;
}

/**
 * Vérifie s'il existe un vol identique dans CARNET_DE_VOL_GENERAL pour le même pilote.
 * @param PDO $pdo
 * @param string $callsign
 * @param string $depart
 * @param string $dest
 * @param float $fuelDep
 * @param float $fuelArr
 * @param float $payload
 * @param int $note
 * @param string $mission
 * @return bool True si doublon trouvé, False sinon
 */
function detecterDoublonVol($pdo, $callsign, $depart, $dest, $fuelDep, $fuelArr, $payload, $note, $mission, $logFile = null) {
    // Récupérer l'id du pilote à partir du callsign
    $stmtPilote = $pdo->prepare("SELECT id FROM PILOTES WHERE callsign = :callsign");
    $stmtPilote->execute(['callsign' => $callsign]);
    $pilote = $stmtPilote->fetch();
    if (!$pilote) {
        // Si le pilote n'existe pas, on ne peut pas détecter de doublon
        logMsg("[detecterDoublonVol] ❌ Pilote inconnu pour callsign=$callsign", $logFile);
        return false;
    }
    $pilote_id = $pilote['id'];
    
    logMsg("[detecterDoublonVol] 🔍 Vérification doublons pour callsign=$callsign, dep=$depart, dest=$dest, fuelDep=$fuelDep, fuelArr=$fuelArr, payload=$payload, mission=$mission", $logFile);

    // Vérification dans CARNET_DE_VOL_GENERAL uniquement
    // Note retirée des critères : elle peut varier selon l'évaluation ACARS
    // On utilise une tolérance de 1 sur les valeurs flottantes pour éviter les problèmes d'arrondis
    // Et on limite la recherche aux dernières 24 heures pour optimiser les performances
    $sqlCarnet = "SELECT COUNT(*) FROM CARNET_DE_VOL_GENERAL 
                  WHERE pilote_id = :pilote_id 
                  AND depart = :depart 
                  AND destination = :dest 
                  AND ABS(fuel_depart - :fuelDep) < 1 
                  AND ABS(fuel_arrivee - :fuelArr) < 1 
                  AND ABS(payload - :payload) < 1 
                  AND mission_id = (SELECT id FROM MISSIONS WHERE libelle = :mission LIMIT 1)
                  AND date_vol >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    $stmtCarnet = $pdo->prepare($sqlCarnet);
    $stmtCarnet->execute([
        'pilote_id' => $pilote_id,
        'depart' => $depart,
        'dest' => $dest,
        'fuelDep' => $fuelDep,
        'fuelArr' => $fuelArr,
        'payload' => $payload,
        'mission' => $mission
    ]);
    $countCarnet = $stmtCarnet->fetchColumn();
    logMsg("[detecterDoublonVol] 📗 Vols dans CARNET_DE_VOL_GENERAL : $countCarnet", $logFile);
    
    if ($countCarnet > 0) {
        logMsg("[detecterDoublonVol] ⚠️ DOUBLON détecté dans CARNET_DE_VOL_GENERAL (count=$countCarnet)", $logFile);
        return true;
    }
    
    logMsg("[detecterDoublonVol] ✅ Aucun doublon détecté", $logFile);
    return false;
}

/**
 * Insère un vol rejeté dans VOLS_REJETES et envoie un mail (version pour import direct sans FROM_ACARS).
 * @param PDO $pdo Instance PDO
 * @param string $callsign Callsign du pilote
 * @param string $immat Immatriculation de l'avion
 * @param string $departure_icao Code ICAO départ
 * @param string $arrival_icao Code ICAO arrivée
 * @param float $departure_fuel Carburant départ
 * @param float $arrival_fuel Carburant arrivée
 * @param string $departure_time Heure de départ
 * @param string $arrival_time Heure d'arrivée
 * @param float $payload Fret transporté
 * @param string $commentaire Commentaire
 * @param int $note Note du vol
 * @param string $mission Mission
 * @param string $motif Motif du rejet
 * @param string $horodateur Horodateur
 * @return void
 */
function rejeterVolDirect($pdo, $callsign, $immat, $departure_icao, $arrival_icao, $departure_fuel, $arrival_fuel, $departure_time, $arrival_time, $payload, $commentaire, $note, $mission, $motif, $horodateur, $logFile = null) {
    if ($logFile === null) {
        $logFile = dirname(__DIR__) . '/scripts/logs/import_vol.log';
    }
    logMsg("[rejeterVolDirect] 🔴 Rejet du vol callsign=$callsign, immat=$immat | Motif : $motif", $logFile);

    $stmt = $pdo->prepare("
        INSERT INTO VOLS_REJETES
        (acars_id, horodateur, callsign, immatriculation, departure_icao, arrival_icao,
         departure_fuel, arrival_fuel, departure_time, arrival_time, payload,
         commentaire, note_du_vol, mission, motif_rejet)
        VALUES (-1, :horodateur, :callsign, :immatriculation, :departure_icao, :arrival_icao,
                :departure_fuel, :arrival_fuel, :departure_time, :arrival_time, :payload,
                :commentaire, :note_du_vol, :mission, :motif_rejet)
    ");

    $stmt->execute([
        'horodateur' => $horodateur,
        'callsign' => $callsign,
        'immatriculation' => $immat,
        'departure_icao' => $departure_icao,
        'arrival_icao' => $arrival_icao,
        'departure_fuel' => $departure_fuel,
        'arrival_fuel' => $arrival_fuel,
        'departure_time' => $departure_time,
        'arrival_time' => $arrival_time,
        'payload' => $payload,
        'commentaire' => $commentaire,
        'note_du_vol' => $note,
        'mission' => $mission,
        'motif_rejet' => $motif
    ]);

    logMsg("[rejeterVolDirect] Vol rejeté inséré dans VOLS_REJETES pour callsign=$callsign", $logFile);

    // Envoi d'un mail après rejet
    try {
        require_once __DIR__ . '/../includes/PHPMailer/PHPMailer.php';
        require_once __DIR__ . '/../includes/PHPMailer/SMTP.php';
        require_once __DIR__ . '/../includes/PHPMailer/Exception.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress(VA_ADMIN_EMAIL);
        $mail->Subject = 'Vol rejeté';
        $mail->CharSet = 'UTF-8';
        $mail->Body = "Un vol a été rejeté :\n" .
            "Callsign : " . $callsign . "\n" .
            "Immatriculation : " . $immat . "\n" .
            "Départ : " . $departure_icao . "\n" .
            "Arrivée : " . $arrival_icao . "\n" .
            "Motif du rejet : " . $motif . "\n";
        $mail->send();
        logMsg("[rejeterVolDirect] 📧 Mail envoyé pour vol rejeté callsign=$callsign", $logFile);
    } catch (Exception $e) {
        error_log("[rejeterVolDirect] Erreur lors de l'envoi du mail de vol rejeté : " . $e->getMessage());
    }
}

/**
 * Ajoute du fret à l'arrivée sur un aéroport donné.
 * @param string $icao Code ICAO de l'aéroport d'arrivée
 * @param float $fret Quantité de fret à ajouter
 * @return void
 */
function ajouterFretDestination($icao, $fret, $logFile = null) {
    global $pdo;
    if ($logFile === null) {
        $logFile = dirname(__DIR__) . '/scripts/logs/import_vol.log';
    }
    logMsg("[ajouterFretDestination] Ajout fret destination : ICAO=$icao, Fret à ajouter=$fret", $logFile);

    // Vérifie si l'aéroport existe
    $stmt = $pdo->prepare("SELECT fret FROM AEROPORTS WHERE ident = :icao");
    $stmt->execute(['icao' => $icao]);
    $result = $stmt->fetch();

    if (!$result) {
        error_log("[ajouterFretDestination] ❌ Aéroport de destination inconnu : $icao. Impossible d'ajouter le fret.");
        return;
    }

    $fret_avant = $result['fret'];
    $fret_apres = $fret_avant + $fret;

    logMsg("[ajouterFretDestination] Fret actuel=$fret_avant, Fret après ajout=$fret_apres", $logFile);

    // Mise à jour
    $update = $pdo->prepare("UPDATE AEROPORTS SET fret = fret + :fret WHERE ident = :icao");
    $update->execute(['fret' => $fret, 'icao' => $icao]);

    // Vérification post-mise à jour
    $stmtNew = $pdo->prepare("SELECT fret FROM AEROPORTS WHERE ident = :icao");
    $stmtNew->execute(['icao' => $icao]);
    $newFret = $stmtNew->fetchColumn();

    logMsg("[ajouterFretDestination] Nouveau fret total à $icao : $newFret", $logFile);
}

/**
 * Insère un vol dans le carnet de vol général (CARNET_DE_VOL_GENERAL).
 * @param string $date_vol Date et heure du vol
 * @param string $callsign Callsign du pilote
 * @param string $immat Immatriculation de l'appareil
 * @param string $depart Code ICAO de départ
 * @param string $arrivee Code ICAO d'arrivée
 * @param float $fuel_dep Carburant au départ
 * @param float $fuel_arr Carburant à l'arrivée
 * @param float $fret Quantité de fret transportée
 * @param string $heure_dep Heure de départ
 * @param string $heure_arr Heure d'arrivée
 * @param string $mission Libellé de la mission
 * @param string $commentaire Commentaire PIREP/maintenance
 * @param int $note Note du vol
 * @param float $cout_vol Coût ou revenu net du vol
 * @return bool True si insertion réussie, False sinon
 */
function remplirCarnetVolGeneral(
    $date_vol, $callsign, $immat, $depart, $arrivee,
    $fuel_dep, $fuel_arr, $fret, $heure_dep, $heure_arr,
    $mission, $commentaire, $note, $cout_vol, $temps_vol,
    $logFile = null
    ) {
    global $pdo;
    if ($logFile === null) {
        $logFile = dirname(__DIR__) . '/scripts/logs/import_vol.log';
    }
    logMsg("[remplirCarnetVolGeneral] Remplissage carnet vol : callsign=$callsign, immat=$immat, depart=$depart, arrivee=$arrivee, fuel_dep=$fuel_dep, fuel_arr=$fuel_arr, fret=$fret, heure_dep=$heure_dep, heure_arr=$heure_arr, mission=$mission, note=$note, cout_vol=$cout_vol, temps_vol=$temps_vol", $logFile);

    $stmtAppareil = $pdo->prepare("SELECT id FROM FLOTTE WHERE immat = :immat");
    $stmtAppareil->execute(['immat' => $immat]);
    $appareil = $stmtAppareil->fetch();
    if (!$appareil) {
        error_log("[remplirCarnetVolGeneral] ❌ Immatriculation inconnue dans FLOTTE : $immat");
        return false;
    }
    $appareil_id = $appareil['id'];

    $stmtPilote = $pdo->prepare("SELECT id FROM PILOTES WHERE callsign = :callsign");
    $stmtPilote->execute(['callsign' => $callsign]);
    $pilote = $stmtPilote->fetch();
    if (!$pilote) {
        error_log("[remplirCarnetVolGeneral] ❌ Callsign inconnu dans PILOTES : $callsign");
        return false;
    }
    $pilote_id = $pilote['id'];

    $stmtMission = $pdo->prepare("SELECT id FROM MISSIONS WHERE libelle = :mission");
    $stmtMission->execute(['mission' => $mission]);
    $missionRow = $stmtMission->fetch();
    $mission_id = $missionRow ? $missionRow['id'] : null;

    $stmt = $pdo->prepare("
        INSERT INTO CARNET_DE_VOL_GENERAL
        (date_vol, pilote_id, appareil_id, depart, destination, fuel_depart, fuel_arrivee, payload, heure_depart, heure_arrivee, mission_id, pirep_maintenance, note_du_vol, cout_vol, temps_vol)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $date_vol, $pilote_id, $appareil_id, $depart, $arrivee,
        $fuel_dep, $fuel_arr, $fret, $heure_dep, $heure_arr,
        $mission_id, $commentaire, $note, $cout_vol, $temps_vol
    ]);

    $vol_id = $pdo->lastInsertId();
    logMsg("[remplirCarnetVolGeneral] Vol enregistré avec succès pour $callsign ($immat), id=$vol_id", $logFile);

    return $vol_id;
}

/**
 * Ajoute une trace GPS dans la table TRACE_GPS.
 * @param int $vol_id ID du vol dans CARNET_DE_VOL_GENERAL
 * @param string $tracegps Trace GPS au format JSON
 * @return void
 */
function ajouterTraceGPS($vol_id, $tracegps, $logFile = null) {
    global $pdo;
    if ($logFile === null) {
        $logFile = dirname(__DIR__) . '/scripts/logs/import_vol.log';
    }
    logMsg("[ajouterTraceGPS] Ajout trace GPS pour vol_id=$vol_id", $logFile);

    if (!$vol_id || !$tracegps) {
        error_log("[ajouterTraceGPS] ⚠ Paramètres manquants: vol_id=$vol_id, tracegps=$tracegps");
        return; 
    }
    $stmt = $pdo->prepare("INSERT INTO TRACE_GPS (id, path) VALUES (:vol_id, :tracegps)");
    try {
        $stmt->execute(['vol_id' => $vol_id, 'tracegps' => $tracegps]);
        logMsg("[ajouterTraceGPS] Trace GPS ajoutée pour vol_id=$vol_id", $logFile);
    } catch (PDOException $e) {
        error_log("[ajouterTraceGPS] ❌ ERREUR SQL: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Met à jour les recettes de l'appareil dans la table FLOTTE.
 * @param string $immat Immatriculation de l'appareil
 * @param float $cout_vol Revenu net du vol à ajouter
 * @return void
 */
function mettreAJourFinances($immat, $cout_vol, $logFile = null) {
    global $pdo;
    if ($logFile === null) {
        $logFile = dirname(__DIR__) . '/scripts/logs/import_vol.log';
    }
    // Log avant et après modification
    if (!$immat || $cout_vol === null) {
        error_log("[mettreAJourFinances] ⚠ Paramètres manquants: " . print_r([
            'immat' => $immat,
            'cout_vol' => $cout_vol
        ], true));
        return;
    }

    $stmt = $pdo->prepare("SELECT id, recettes FROM FLOTTE WHERE immat = :immat");
    $stmt->execute(['immat' => $immat]);
    $avion = $stmt->fetch();

    if (!$avion) {
        error_log("[mettreAJourFinances] ❌ Avion inconnu : $immat. Impossible de mettre à jour les finances.");
        return;
    }

    $avion_id = $avion['id'];
    $recette_old = isset($avion['recettes']) ? floatval($avion['recettes']) : 0.0;
    $recette_new = $recette_old + floatval($cout_vol);
    logMsg("[mettreAJourFinances] Recette ancienne: $recette_old €, nouvelle recette: $recette_new €", $logFile);

    try {
        $update = $pdo->prepare("UPDATE FLOTTE SET recettes = :recette_new WHERE id = :id_avion");
        $update->execute(['recette_new' => $recette_new, 'id_avion' => $avion_id]);
        logMsg("[mettreAJourFinances] Recettes mises à jour pour avion_id=$avion_id", $logFile);
    } catch (PDOException $e) {
        error_log("[mettreAJourFinances] ❌ ERREUR SQL: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Met à jour l'état, le carburant et la localisation d'un appareil dans la flotte.
 * @param string $immat Immatriculation de l'appareil
 * @param float $fuel_arr Carburant restant à l'arrivée
 * @param string $callsign Callsign du pilote
 * @param string $arrivee Code ICAO d'arrivée
 * @return void
 */
function mettreAJourFlotte($immat, $fuel_arr, $callsign, $arrivee, $logFile = null) {
    global $pdo;
    if ($logFile === null) {
        $logFile = dirname(__DIR__) . '/scripts/logs/import_vol.log';
    }

    if (!$immat || !$fuel_arr || !$callsign || !$arrivee) {
        error_log("[mettreAJourFlotte] ⚠ Paramètres manquants: " . print_r([
            'immat' => $immat,
            'fuel_arr' => $fuel_arr,
            'callsign' => $callsign,
            'arrivee' => $arrivee
        ], true));
        return;
    }

    $stmt = $pdo->prepare("SELECT id FROM PILOTES WHERE callsign = :callsign");
    $stmt->execute(['callsign' => $callsign]);
    $pilote = $stmt->fetch();

    if (!$pilote) {
        error_log("[mettreAJourFlotte] ❌ Pilote inconnu : $callsign. Impossible de mettre à jour la flotte.");
        return;
    }

    $id_pilote = $pilote['id'];

    try {
        $update = $pdo->prepare("
            UPDATE FLOTTE
            SET fuel_restant = :fuel, dernier_utilisateur = :id_pilote, localisation = :location
            WHERE immat = :immat
        ");
        $update->execute([
            'fuel' => $fuel_arr,
            'id_pilote' => $id_pilote,
            'location' => $arrivee,
            'immat' => $immat
        ]);

        logMsg("[mettreAJourFlotte] Flotte mise à jour pour $immat", $logFile);
    } catch (PDOException $e) {
        error_log("[mettreAJourFlotte] ❌ ERREUR SQL: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Applique l'usure à un appareil selon la note du vol. Met à jour l'état et le statut en cas de crash.
 * @param string $immat Immatriculation de l'appareil
 * @param int $note Note du vol (1 à 10)
 * @return void
 */
function deduireUsure(string $immat, int $note, $logFile = null): void {
    if ($logFile === null) {
        $logFile = dirname(__DIR__) . '/scripts/logs/import_vol.log';
    }
    global $pdo;

    $pourcentages = [
        10 => 2, 9 => 3, 8 => 4, 7 => 5, 6 => 6,
        5 => 7, 4 => 8, 3 => 9, 2 => 10, 1 => 100
    ];

    if (!isset($pourcentages[$note])) {
        error_log("[deduireUsure] ⚠ note invalide $note pour immat $immat");
        return;
    }

    $stmt = $pdo->prepare("SELECT id, etat FROM FLOTTE WHERE immat = :immat");
    $stmt->execute(['immat' => $immat]);
    $avion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$avion) {
        error_log("[deduireUsure] ⚠ immatriculation non trouvée $immat");
        return;
    }

    $id = $avion['id'];
    $etatActuel = (float)$avion['etat'];
    $perte = $pourcentages[$note];
    $nouvelEtat = max(0, $etatActuel - $perte);

    if ($note === 1) {
        $update = $pdo->prepare("UPDATE FLOTTE SET etat = 0, status = 2 WHERE id = :id");
        $update->execute(['id' => $id]);
        error_log("[deduireUsure] ❌ CRASH détecté pour $immat (note 1), état mis à 0, status=2");
    } else {
        $update = $pdo->prepare("UPDATE FLOTTE SET etat = :etat WHERE id = :id");
        $update->execute(['etat' => $nouvelEtat, 'id' => $id]);
        logMsg("[deduireUsure] Usure avion $immat : $etatActuel% → $nouvelEtat% (note $note)", $logFile);
    }
}

/**
 * Insère un vol rejeté dans VOLS_REJETES, envoie un mail et supprime le vol ACARS.
 * @param PDO $pdo Instance PDO
 * @param array $vol Données du vol ACARS
 * @param string $motif Motif du rejet
 * @return void
 */
function rejeterVol($pdo, $vol, $motif, $logFile = null) {
    if ($logFile === null) {
        $logFile = dirname(__DIR__) . '/scripts/logs/import_vol.log';
    }
    logMsg("[rejeterVol] 🔴 Rejet du vol ACARS ID=" . $vol['id'] . " | Motif : $motif", $logFile);

    $stmt = $pdo->prepare("
        INSERT INTO VOLS_REJETES
        (acars_id, horodateur, callsign, immatriculation, departure_icao, arrival_icao,
         departure_fuel, arrival_fuel, departure_time, arrival_time, payload,
         commentaire, note_du_vol, mission, motif_rejet)
        VALUES (:acars_id, :horodateur, :callsign, :immatriculation, :departure_icao, :arrival_icao,
                :departure_fuel, :arrival_fuel, :departure_time, :arrival_time, :payload,
                :commentaire, :note_du_vol, :mission, :motif_rejet)
    ");

    $stmt->execute([
        'acars_id' => $vol['id'],
        'horodateur' => $vol['horodateur'],
        'callsign' => $vol['callsign'],
        'immatriculation' => $vol['immatriculation'],
        'departure_icao' => $vol['departure_icao'],
        'arrival_icao' => $vol['arrival_icao'],
        'departure_fuel' => $vol['departure_fuel'],
        'arrival_fuel' => $vol['arrival_fuel'],
        'departure_time' => $vol['departure_time'],
        'arrival_time' => $vol['arrival_time'],
        'payload' => $vol['payload'],
        'commentaire' => $vol['commentaire'],
        'note_du_vol' => $vol['note_du_vol'],
        'mission' => $vol['mission'],
        'motif_rejet' => $motif
    ]);

    logMsg("[rejeterVol] Vol rejeté inséré dans VOLS_REJETES pour ACARS ID=" . $vol['id'], $logFile);

    // Envoi d'un mail après rejet
    try {
        require_once __DIR__ . '/../includes/PHPMailer/PHPMailer.php';
        require_once __DIR__ . '/../includes/PHPMailer/SMTP.php';
        require_once __DIR__ . '/../includes/PHPMailer/Exception.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress(VA_ADMIN_EMAIL);
        $mail->Subject = 'Vol rejeté';
        $mail->CharSet = 'UTF-8';
        $mail->Body = "Un vol a été rejeté :\n" .
            "ACARS ID : " . $vol['id'] . "\n" .
            "Callsign : " . $vol['callsign'] . "\n" .
            "Immatriculation : " . $vol['immatriculation'] . "\n" .
            "Départ : " . $vol['departure_icao'] . "\n" .
            "Arrivée : " . $vol['arrival_icao'] . "\n" .
            "Motif du rejet : " . $motif . "\n";
        $mail->send();
        logMsg("[rejeterVol] 📧 Mail envoyé pour vol rejeté ACARS ID=" . $vol['id'], $logFile);
    } catch (Exception $e) {
        error_log("[rejeterVol] Erreur lors de l'envoi du mail de vol rejeté : " . $e->getMessage());
    }

    $del = $pdo->prepare("DELETE FROM FROM_ACARS WHERE id = :id");
    $del->execute(['id' => $vol['id']]);

    logMsg("[rejeterVol] 🗑️ Vol supprimé de FROM_ACARS pour ACARS ID=" . $vol['id'], $logFile);
}

/**
 * Calcule la distance (en miles nautiques) entre deux aéroports à partir de leurs codes ICAO.
 * @param string $icao1 Code ICAO du premier aéroport
 * @param string $icao2 Code ICAO du second aéroport
 * @return float|null Distance en NM, ou null si un des aéroports est inconnu
 */
function ComputeFlightDistance($icao1, $icao2) {
    global $pdo;
    // Récupère les coordonnées des deux aéroports
    $stmt = $pdo->prepare("SELECT ident, latitude_deg, longitude_deg FROM AEROPORTS WHERE ident IN (:icao1, :icao2)");
    $stmt->execute(['icao1' => $icao1, 'icao2' => $icao2]);
    $coords = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $coords[$row['ident']] = [$row['latitude_deg'], $row['longitude_deg']];
    }
    if (!isset($coords[$icao1]) || !isset($coords[$icao2])) {
        return null; // Un des aéroports n'existe pas
    }
    list($lat1, $lon1) = $coords[$icao1];
    list($lat2, $lon2) = $coords[$icao2];

    // Formule de Haversine en miles nautiques
    $R = 3440.065; // Rayon de la Terre en miles nautiques
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $distance = $R * $c;
    return $distance;
}

