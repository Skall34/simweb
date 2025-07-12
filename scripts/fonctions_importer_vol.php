<?php
// Fonction utilitaire pour tracer les étapes avec horodatage
function log_trace($message) {
    error_log("[TRACE] " . $message);
}

function deduireFretDepart($icao, $fret_demande) {
    global $pdo;

    log_trace("Déduction fret départ : ICAO=$icao, Demande=$fret_demande");

    $stmt = $pdo->prepare("SELECT fret FROM AEROPORTS WHERE ident = :icao");
    $stmt->execute(['icao' => $icao]);
    $result = $stmt->fetch();

    if (!$result) {
        error_log("❌ Aéroport de départ inconnu : $icao. Impossible de déduire le fret.");
        return 0;
    }

    $fret_dispo = $result['fret'];
    $fret_effectif = min($fret_dispo, $fret_demande);
    log_trace("Fret disponible=$fret_dispo, Fret effectif déduit=$fret_effectif");

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

    log_trace("Nouveau fret restant à $icao : $newFret");

    return $fret_effectif;
}

function ajouterFretDestination($icao, $fret) {
    global $pdo;

    log_trace("Ajout fret destination : ICAO=$icao, Fret à ajouter=$fret");

    // Vérifie si l'aéroport existe
    $stmt = $pdo->prepare("SELECT fret FROM AEROPORTS WHERE ident = :icao");
    $stmt->execute(['icao' => $icao]);
    $result = $stmt->fetch();

    if (!$result) {
        error_log("❌ Aéroport de destination inconnu : $icao. Impossible d'ajouter le fret.");
        return;
    }

    $fret_avant = $result['fret'];
    $fret_apres = $fret_avant + $fret;

    log_trace("Fret actuel=$fret_avant, Fret après ajout=$fret_apres");

    // Mise à jour
    $update = $pdo->prepare("UPDATE AEROPORTS SET fret = fret + :fret WHERE ident = :icao");
    $update->execute(['fret' => $fret, 'icao' => $icao]);

    // Vérification post-mise à jour
    $stmtNew = $pdo->prepare("SELECT fret FROM AEROPORTS WHERE ident = :icao");
    $stmtNew->execute(['icao' => $icao]);
    $newFret = $stmtNew->fetchColumn();

    log_trace("Nouveau fret total à $icao : $newFret");
}

function remplirCarnetVolGeneral(
    $date_vol, $callsign, $immat, $depart, $arrivee,
    $fuel_dep, $fuel_arr, $fret, $heure_dep, $heure_arr,
    $mission, $commentaire, $note, $cout_vol
    ) {
    global $pdo;

    log_trace("Remplissage carnet vol : $callsign, $immat, $depart -> $arrivee, cout_vol=$cout_vol");

    $stmtAppareil = $pdo->prepare("SELECT id FROM FLOTTE WHERE immat = :immat");
    $stmtAppareil->execute(['immat' => $immat]);
    $appareil = $stmtAppareil->fetch();
    if (!$appareil) {
        error_log("❌ Immatriculation inconnue dans FLOTTE : $immat");
        return false;
    }
    $appareil_id = $appareil['id'];

    $stmtPilote = $pdo->prepare("SELECT id FROM PILOTES WHERE callsign = :callsign");
    $stmtPilote->execute(['callsign' => $callsign]);
    $pilote = $stmtPilote->fetch();
    if (!$pilote) {
        error_log("❌ Callsign inconnu dans PILOTES : $callsign");
        return false;
    }
    $pilote_id = $pilote['id'];

    $stmtMission = $pdo->prepare("SELECT id FROM MISSIONS WHERE libelle = :mission");
    $stmtMission->execute(['mission' => $mission]);
    $missionRow = $stmtMission->fetch();
    $mission_id = $missionRow ? $missionRow['id'] : null;

    $stmt = $pdo->prepare("
        INSERT INTO CARNET_DE_VOL_GENERAL
        (date_vol, pilote_id, appareil_id, depart, destination, fuel_depart, fuel_arrivee, payload, heure_depart, heure_arrivee, mission_id, pirep_maintenance, note_du_vol, cout_vol)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $date_vol, $pilote_id, $appareil_id, $depart, $arrivee,
        $fuel_dep, $fuel_arr, $fret, $heure_dep, $heure_arr,
        $mission_id, $commentaire, $note, $cout_vol
    ]);

    log_trace("Vol enregistré avec succès pour $callsign ($immat)");

    return true;
}

function mettreAJourFinances($immat, $cout_vol) {
    global $pdo;

    log_trace("Mise à jour finances : immat=$immat, cout_vol=$cout_vol");

    if (!$immat || $cout_vol === null) {
        error_log("⚠ Paramètres manquants dans mettreAJourFinances: " . print_r([
            'immat' => $immat,
            'cout_vol' => $cout_vol
        ], true));
        return;
    }

    $stmt = $pdo->prepare("SELECT id FROM FLOTTE WHERE immat = :immat");
    $stmt->execute(['immat' => $immat]);
    $avion = $stmt->fetch();

    if (!$avion) {
        error_log("❌ Avion inconnu : $immat. Impossible de mettre à jour les finances.");
        return;
    }

    $avion_id = $avion['id'];

    $stmt = $pdo->prepare("SELECT recettes FROM FINANCES WHERE avion_id = :avion_id");
    $stmt->execute(['avion_id' => $avion_id]);
    $finance = $stmt->fetch();

    if (!$finance) {
        error_log("❌ Pas de ligne FINANCES pour avion_id : $avion_id.");
        return;
    }

    $recette_old = floatval($finance['recettes']);
    $recette_new = $recette_old + floatval($cout_vol);

    log_trace("Recette ancienne: $recette_old €, nouvelle recette: $recette_new €");

    try {
        $update = $pdo->prepare("UPDATE FINANCES SET recettes = :recette_new WHERE avion_id = :id_avion");
        $update->execute(['recette_new' => $recette_new, 'id_avion' => $avion_id]);
        log_trace("Finances mises à jour pour avion_id=$avion_id");
    } catch (PDOException $e) {
        error_log("❌ ERREUR SQL dans mettreAJourFinances: " . $e->getMessage());
        throw $e;
    }
}

function mettreAJourFlotte($immat, $fuel_arr, $callsign, $arrivee) {
    global $pdo;

    log_trace("Mise à jour flotte : immat=$immat, fuel=$fuel_arr, callsign=$callsign, localisation=$arrivee");

    if (!$immat || !$fuel_arr || !$callsign || !$arrivee) {
        error_log("⚠ Paramètres manquants dans mettreAJourFlotte: " . print_r([
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
        error_log("❌ Pilote inconnu : $callsign. Impossible de mettre à jour la flotte.");
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

        log_trace("Flotte mise à jour pour $immat");
    } catch (PDOException $e) {
        error_log("❌ ERREUR SQL dans mettreAJourFlotte: " . $e->getMessage());
        throw $e;
    }
}

function deduireUsure(string $immat, int $note): void {
    global $pdo;

    $pourcentages = [
        10 => 2, 9 => 3, 8 => 4, 7 => 5, 6 => 6,
        5 => 7, 4 => 8, 3 => 9, 2 => 10, 1 => 100
    ];

    if (!isset($pourcentages[$note])) {
        error_log("deduireUsure: note invalide $note pour immat $immat");
        return;
    }

    $stmt = $pdo->prepare("SELECT id, etat FROM FLOTTE WHERE immat = :immat");
    $stmt->execute(['immat' => $immat]);
    $avion = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$avion) {
        error_log("deduireUsure: immatriculation non trouvée $immat");
        return;
    }

    $id = $avion['id'];
    $etatActuel = (float)$avion['etat'];
    $perte = $pourcentages[$note];
    $nouvelEtat = max(0, $etatActuel - $perte);

    if ($note === 1) {
        $update = $pdo->prepare("UPDATE FLOTTE SET etat = 0, status = 2 WHERE id = :id");
        $update->execute(['id' => $id]);
        error_log("deduireUsure: CRASH détecté pour $immat (note 1), état mis à 0, status=2");
    } else {
        $update = $pdo->prepare("UPDATE FLOTTE SET etat = :etat WHERE id = :id");
        $update->execute(['etat' => $nouvelEtat, 'id' => $id]);
        log_trace("Usure avion $immat : $etatActuel% → $nouvelEtat% (note $note)");
    }
}

function rejeterVol($pdo, $vol, $motif) {
    log_trace("🔴 Rejet du vol ACARS ID=" . $vol['id'] . " | Motif : $motif");

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

    log_trace("✅ Vol rejeté inséré dans VOLS_REJETES pour ACARS ID=" . $vol['id']);

    $del = $pdo->prepare("DELETE FROM FROM_ACARS WHERE id = :id");
    $del->execute(['id' => $vol['id']]);

    log_trace("🗑️ Vol supprimé de FROM_ACARS pour ACARS ID=" . $vol['id']);
}
