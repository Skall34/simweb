<?php
require_once __DIR__ . '/../includes/require_login.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../includes/mail_utils.php';
require_once __DIR__ . '/../includes/fonctions_financieres.php';
require_once __DIR__ . '/../includes/fonctions_importer_vol.php';
require_once __DIR__ . '/../includes/calcul_cout.php';

$logFile = __DIR__ . '/../scripts/logs/importer_vol_manual.log';
$mailSummaryEnabled = true; // activer/désactiver l'envoi du mail récapitulatif

$erreurs = [];
$success = false;

// Récupérer les callsigns et missions pour les listes déroulantes
$stmt = $pdo->query("SELECT callsign FROM PILOTES WHERE actif=1 ORDER BY callsign");
$callsigns = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->query("SELECT libelle FROM MISSIONS WHERE active = 1 ORDER BY libelle");
$missionslist = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Récupérer les immatriculations actives
$stmt = $pdo->query("SELECT immat FROM FLOTTE WHERE actif = 1 ORDER BY immat");
$immats = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Initialiser les valeurs du formulaire
$form = [
    'callsign' => '',
    'immatriculation' => '',
    'departure_icao' => '',
    'departure_fuel' => '',
    'departure_datetime' => '',
    'arrival_icao' => '',
    'arrival_fuel' => '',
    'arrival_datetime' => '',
    'payload' => '',
    'commentaire' => '',
    'note_du_vol' => '',
    'mission' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si reset, on garde les champs vides
    if (isset($_POST['reset'])) {
        // Rien à faire, $form reste vide
    } else {
        // Récupération et nettoyage des champs
        foreach ($form as $key => $val) {
            $form[$key] = isset($_POST[$key]) ? trim($_POST[$key]) : '';
        }
        $form['departure_icao'] = strtoupper($form['departure_icao']);
        $form['arrival_icao'] = strtoupper($form['arrival_icao']);

        // Contrôles
        if (!$form['callsign']) $erreurs[] = "Sélectionnez un callsign.";
        if (!$form['immatriculation']) $erreurs[] = "Sélectionnez une immatriculation.";
        if (!$form['departure_icao'] || strlen($form['departure_icao']) !== 4) $erreurs[] = "Code ICAO départ invalide.";
        if (!$form['arrival_icao'] || strlen($form['arrival_icao']) !== 4) $erreurs[] = "Code ICAO arrivée invalide.";
        if ($form['departure_fuel'] === '' || floatval($form['departure_fuel']) < 0) $erreurs[] = "Fuel départ invalide.";
        if ($form['arrival_fuel'] === '' || floatval($form['arrival_fuel']) < 0) $erreurs[] = "Fuel arrivée invalide.";
        if (floatval($form['departure_fuel']) < floatval($form['arrival_fuel'])) $erreurs[] = "Le fuel départ ne peut pas être inférieur au fuel arrivée.";
        if (!$form['departure_datetime']) $erreurs[] = "Date/heure départ obligatoire.";
        if (!$form['arrival_datetime']) $erreurs[] = "Date/heure arrivée obligatoire.";
        if ($form['note_du_vol'] === '' || intval($form['note_du_vol']) < 1 || intval($form['note_du_vol']) > 10) $erreurs[] = "Note du vol entre 1 et 10.";
        if (!$form['mission']) $erreurs[] = "Mission obligatoire.";

        // Vérification des aéroports
        if ($form['departure_icao']) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM AEROPORTS WHERE ident = :icao");
            $stmt->execute(['icao' => $form['departure_icao']]);
            if ($stmt->fetchColumn() == 0) $erreurs[] = "Aéroport de départ inconnu.";
        }
        if ($form['arrival_icao']) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM AEROPORTS WHERE ident = :icao");
            $stmt->execute(['icao' => $form['arrival_icao']]);
            if ($stmt->fetchColumn() == 0) $erreurs[] = "Aéroport d'arrivée inconnu.";
        }

        // Contrôle cohérence date/heure (format, ordre et pas de dates futures)
        if ($form['departure_datetime'] && $form['arrival_datetime']) {
            $dt_dep = DateTime::createFromFormat('Y-m-d\TH:i', $form['departure_datetime']);
            $dt_arr = DateTime::createFromFormat('Y-m-d\TH:i', $form['arrival_datetime']);
            $now = new DateTime();
            if (!$dt_dep || !$dt_arr) {
                $erreurs[] = "Format date/heure invalide.";
            } else {
                if ($dt_dep >= $dt_arr) {
                    $erreurs[] = "La date/heure de départ doit être antérieure à la date/heure d'arrivée.";
                }
                if ($dt_dep > $now) {
                    $erreurs[] = "La date/heure de départ ne peut pas être postérieure au moment présent.";
                }
                if ($dt_arr > $now) {
                    $erreurs[] = "La date/heure d'arrivée ne peut pas être postérieure au moment présent.";
                }
            }
        }

        if (empty($erreurs)) {
            // Traitement immédiat du vol (mode identique à api_import_vol_direct.php)
            try {
                // Préparer les variables
                $callsign = $form['callsign'];
                $immat = $form['immatriculation'];
                $departure_icao = strtoupper($form['departure_icao']);
                $arrival_icao = strtoupper($form['arrival_icao']);
                $departure_time = $form['departure_datetime'] ? str_replace('T', ' ', $form['departure_datetime']) . ':00' : null;
                $arrival_time = $form['arrival_datetime'] ? str_replace('T', ' ', $form['arrival_datetime']) . ':00' : null;
                $departure_fuel = floatval($form['departure_fuel']);
                $arrival_fuel = floatval($form['arrival_fuel']);
                $payload = floatval($form['payload']);
                $note = intval($form['note_du_vol']);
                $commentaire = $form['commentaire'];
                $mission = $form['mission'];
                $horodateur = $departure_time ?: date('Y-m-d H:i:s');
                $tracegps = '';

                // 1) Contrôles supplémentaires (doublon, pilote/avion, carburant non nul)
                $erreurs_proc = [];
                // carburant et conso
                $conso = $departure_fuel - $arrival_fuel;
                if ($departure_fuel == 0 || $arrival_fuel == 0 || $conso == 0) {
                    $erreurs_proc[] = "Vol rejeté : carburant départ/arrivée/consommation à 0";
                }

                // pilote
                $stmtPilote = $pdo->prepare("SELECT id FROM PILOTES WHERE callsign = :callsign");
                $stmtPilote->execute(['callsign' => $callsign]);
                $pilote = $stmtPilote->fetch();
                if (!$pilote) $erreurs_proc[] = "Pilote '$callsign' introuvable.";

                // avion
                $stmtAvion = $pdo->prepare("SELECT id FROM FLOTTE WHERE immat = :immat AND actif = 1");
                $stmtAvion->execute(['immat' => $immat]);
                $avion = $stmtAvion->fetch();
                if (!$avion) $erreurs_proc[] = "Avion '$immat' introuvable ou inactif.";

                // doublon
                if (function_exists('detecterDoublonVol') && detecterDoublonVol($pdo, $callsign, $departure_icao, $arrival_icao, $departure_fuel, $arrival_fuel, $payload, $note, $mission)) {
                    $erreurs_proc[] = "Vol doublon détecté pour le pilote '$callsign'.";
                }

                if (!empty($erreurs_proc)) {
                    foreach ($erreurs_proc as $errp) logMsg("[saisie_manuelle] $errp", $logFile);
                    // rejeter le vol si la fonction existe
                    if (function_exists('rejeterVol')) {
                        rejeterVol($pdo, $form, implode(' | ', $erreurs_proc), $logFile);
                    }
                    $erreurs = array_merge($erreurs, $erreurs_proc);
                } else {
                   
                    // Traitement métier (fret, coûts, carnet, flotte, finances, usure)
                    if ($payload > 0 && function_exists('deduireFretDepart')) {
                        $fret_transporte = deduireFretDepart($departure_icao, $payload, $logFile);
                        if (function_exists('ajouterFretDestination')) ajouterFretDestination($arrival_icao, $fret_transporte, $logFile);
                    }

                    $distance = function_exists('ComputeFlightDistance') ? ComputeFlightDistance($departure_icao, $arrival_icao) : 0;
                    $majoration_mission = function_exists('getMajorationMission') ? getMajorationMission($mission) : 0;
                    $cout_horaire = function_exists('getCoutHoraire') ? getCoutHoraire($immat) : 0;
                    $carburant = $departure_fuel - $arrival_fuel;
                    $temps_vol = '00:00:00';
                    if ($departure_time && $arrival_time) {
                        $t1 = new DateTime($departure_time);
                        $t2 = new DateTime($arrival_time);
                        if ($t2 <= $t1) $t2->modify('+1 day');
                        $interval = $t1->diff($t2);
                        $temps_vol = $interval->format('%H:%I:%S');
                    }

                    $cout_vol = function_exists('calculerRevenuNetVol') ? calculerRevenuNetVol($payload, $temps_vol, $distance, $majoration_mission, $carburant, $note, $cout_horaire, $immat) : 0;

                    $vol_id = function_exists('remplirCarnetVolGeneral') ? remplirCarnetVolGeneral($horodateur, $callsign, $immat, $departure_icao, $arrival_icao, $departure_fuel, $arrival_fuel, $payload, $departure_time, $arrival_time, $mission, $commentaire, $note, $cout_vol, $temps_vol, $logFile) : null;
                    logMsg("[saisie_manuelle] Vol inséré et traité: callsign=$callsign immat=$immat depart=$departure_icao dest=$arrival_icao payload=$payload cout=$cout_vol", $logFile);

                    if (!empty($tracegps) && function_exists('ajouterTraceGPS') && $vol_id) {
                        ajouterTraceGPS($vol_id, $tracegps, $logFile);
                        logMsg("[saisie_manuelle] Trace GPS ajoutée pour vol id=$vol_id", $logFile);
                    }

                    if (function_exists('mettreAJourFlotte')) mettreAJourFlotte($immat, $arrival_fuel, $callsign, $arrival_icao, $logFile);
                    if (function_exists('mettreAJourFinances')) mettreAJourFinances($immat, $cout_vol, $logFile);
                    if (function_exists('mettreAJourRecettes')) {
                        $commentaire_recette = "Vol importé manuellement : $departure_icao -> $arrival_icao, pilote: $callsign, immat: $immat";
                        mettreAJourRecettes($cout_vol, $vol_id, $immat, $callsign, 'vol', 'Recette vol (saisie manuelle)');
                    }
                    if (function_exists('deduireUsure')) deduireUsure($immat, $note, $logFile);

                    // Mail recapitulatif (si active)
                    if ($mailSummaryEnabled && function_exists('sendSummaryMail')) {
                        $subject = "[SimWeb] Rapport import vol manuel - " . date('d/m/Y H:i');
                        $body = "Import manuel d'un vol termine.\n\n";
                        // Nettoyer les caracteres speciaux pour eviter problemes SMTP
                        $callsign_clean = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $callsign);
                        $immat_clean = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $immat);
                        $body .= "Pilote : $callsign_clean\n";
                        $body .= "Trajet : $departure_icao -> $arrival_icao\n";
                        $body .= "Immatriculation : $immat_clean\n";
                        $payload_fmt = number_format(floatval($payload), 2, ',', ' ');
                        $body .= "Payload : {$payload_fmt} Kg\n";
                        $cout_vol_fmt = number_format(floatval($cout_vol), 2, ',', ' ');
                        $body .= "Recettes du vol : {$cout_vol_fmt} €\n";
                        $to = VA_ADMIN_EMAIL;
                        $mailResult = sendSummaryMail($subject, $body, $to);
                        if ($mailResult === true || $mailResult === null) {
                            logMsg("[saisie_manuelle] Mail recapitulatif envoye a $to", $logFile);
                        } else {
                            logMsg("[saisie_manuelle] Erreur lors de l'envoi du mail recapitulatif : $mailResult", $logFile);
                        }
                    }

                    $success = true;
                    // réinitialiser formulaire
                    foreach ($form as $key => $val) $form[$key] = '';
                }
            } catch (Exception $e) {
                logMsg('[saisie_manuelle] Exception: ' . $e->getMessage(), $logFile);
                $erreurs[] = 'Erreur lors du traitement du vol : ' . $e->getMessage();
            }
        }
    }
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>

<main>
        <h2><?= t('saisie_manuelle_title') ?></h2>
        <?php if ($success): ?>
            <div class="alert success">✅ <?= t('saisie_manuelle_success') ?></div>
        <?php elseif ($erreurs): ?>
            <div class="alert error">
                <?php foreach ($erreurs as $err): ?>
                    <div><?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post"  class="form-inscription" autocomplete="off">
            <div class="form-group">
                <label for="callsign"><?= t('saisie_manuelle_callsign') ?></label>
                <select name="callsign" id="callsign" required class="form-input">
                    <option value=""><?= t('select_placeholder') ?></option>
                    <?php foreach ($callsigns as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= ($form['callsign'] === $c) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="immatriculation"><?= t('saisie_manuelle_immat') ?></label>
                <select name="immatriculation" id="immatriculation" required class="form-input">
                    <option value=""><?= t('select_placeholder') ?></option>
                    <?php foreach ($immats as $i): ?>
                        <option value="<?= htmlspecialchars($i) ?>" <?= ($form['immatriculation'] === $i) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($i) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="departure_icao"><?= t('saisie_manuelle_depart_icao') ?></label>
                <input type="text" name="departure_icao" id="departure_icao" maxlength="4" value="<?= htmlspecialchars($form['departure_icao']) ?>" required class="form-input" style="text-transform:uppercase;" oninput="this.value = this.value.toUpperCase();">
            </div>
            <div class="form-group">
                <label for="departure_fuel"><?= t('saisie_manuelle_depart_fuel') ?></label>
                <input type="number" name="departure_fuel" id="departure_fuel" min="0" step="1" value="<?= htmlspecialchars($form['departure_fuel']) ?>" required class="form-input">
            </div>
            <div class="form-group">
                <label for="departure_datetime"><?= t('saisie_manuelle_depart_datetime') ?></label>
                <input type="datetime-local" name="departure_datetime" id="departure_datetime" value="<?= htmlspecialchars($form['departure_datetime']) ?>" required class="form-input">
            </div>
            <div class="form-group">
                <label for="arrival_icao"><?= t('saisie_manuelle_arrivee_icao') ?></label>
                <input type="text" name="arrival_icao" id="arrival_icao" maxlength="4" value="<?= htmlspecialchars($form['arrival_icao']) ?>" required class="form-input" style="text-transform:uppercase;" oninput="this.value = this.value.toUpperCase();">
            </div>
            <div class="form-group">
                <label for="arrival_fuel"><?= t('saisie_manuelle_arrivee_fuel') ?></label>
                <input type="number" name="arrival_fuel" id="arrival_fuel" min="0" step="1" value="<?= htmlspecialchars($form['arrival_fuel']) ?>" required class="form-input">
            </div>
            <div class="form-group">
                <label for="arrival_datetime"><?= t('saisie_manuelle_arrivee_datetime') ?></label>
                <input type="datetime-local" name="arrival_datetime" id="arrival_datetime" value="<?= htmlspecialchars($form['arrival_datetime']) ?>" required class="form-input">
            </div>
            <div class="form-group">
                <label for="payload"><?= t('saisie_manuelle_payload') ?></label>
                <input type="number" name="payload" id="payload" min="0" step="1" value="<?= htmlspecialchars($form['payload']) ?>" class="form-input">
            </div>
            <div class="form-group">
                <label for="commentaire"><?= t('saisie_manuelle_commentaire') ?></label>
                <input type="text" name="commentaire" id="commentaire" maxlength="255" value="<?= htmlspecialchars($form['commentaire']) ?>" class="form-input">
            </div>
            <div class="form-group">
                <label for="note_du_vol"><?= t('saisie_manuelle_note') ?></label>
                <input type="number" name="note_du_vol" id="note_du_vol" min="1" max="10" value="<?= htmlspecialchars($form['note_du_vol']) ?>" required class="form-input">
            </div>
            <div class="form-group">
                <label for="mission"><?= t('saisie_manuelle_mission') ?></label>
                <select name="mission" id="mission" required class="form-input">
                    <option value=""><?= t('select_placeholder') ?></option>
                    <?php foreach ($missionslist as $m): ?>
                        <option value="<?= htmlspecialchars($m) ?>" <?= ($form['mission'] === $m) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="display:flex;gap:1em;">
                <button type="submit" class="btn"><?= t('saisie_manuelle_add') ?></button>
                <button type="submit" name="reset" value="1" class="btn btn-reset"><?= t('saisie_manuelle_reset') ?></button>
            </div>
        </form>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>