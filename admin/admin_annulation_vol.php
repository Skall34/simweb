<?php
session_start();

require_once __DIR__ . '/../includes/require_login.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../lang.php';
require_once __DIR__ . '/../includes/log_func.php';

$callsign = $_SESSION['user']['callsign'] ?? ($_SESSION['callsign'] ?? null);
$superAdmins = array_filter(array_map('trim', explode(',', VA_SUPER_ADMIN_CALLSIGNS)));
if (!$callsign || !in_array($callsign, $superAdmins, true)) {
    header('Location: /index.php');
    exit;
}

$pilotFilter = trim($_GET['callsign'] ?? '');
$aircraftFilter = trim($_GET['immat'] ?? '');
$dateFilter = trim($_GET['date'] ?? '');
$airportFilter = strtoupper(trim($_GET['airport'] ?? ''));
$showCancelled = isset($_GET['show_cancelled']) && $_GET['show_cancelled'] === '1';
$volId = isset($_POST['vol_id']) ? filter_var($_POST['vol_id'], FILTER_VALIDATE_INT) : null;
$motif = trim($_POST['motif'] ?? '');
$action = $_POST['action'] ?? 'simulate';
$errors = [];
$success = '';
$vol = null;
$recettes = [];
$depenses = [];
$usureCredit = null;
$vols = [];

if (empty($_SESSION['flight_cancellation_csrf'])) {
    $_SESSION['flight_cancellation_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['flight_cancellation_csrf'];

$sqlVols = "
        SELECT c.id, c.date_vol, c.heure_depart, c.depart, c.destination, c.note_du_vol,
            c.date_annulation, c.annule_par, c.motif_annulation,
           p.callsign AS pilote_callsign, f.immat
    FROM CARNET_DE_VOL_GENERAL c
    INNER JOIN PILOTES p ON p.id = c.pilote_id
    INNER JOIN FLOTTE f ON f.id = c.appareil_id
";
$conditions = [];
$params = [];
if ($pilotFilter !== '') {
    $conditions[] = 'p.callsign LIKE :callsign';
    $params['callsign'] = '%' . $pilotFilter . '%';
}
if ($aircraftFilter !== '') {
    $conditions[] = 'f.immat LIKE :immat';
    $params['immat'] = '%' . $aircraftFilter . '%';
}
if ($dateFilter !== '') {
    $conditions[] = 'c.date_vol = :date_vol';
    $params['date_vol'] = $dateFilter;
}
if ($airportFilter !== '') {
    $conditions[] = '(c.depart LIKE :airport_depart OR c.destination LIKE :airport_destination)';
    $params['airport_depart'] = '%' . $airportFilter . '%';
    $params['airport_destination'] = '%' . $airportFilter . '%';
}
$conditions[] = $showCancelled ? 'c.annule = 1' : 'c.annule = 0';
if (!empty($conditions)) {
    $sqlVols .= ' WHERE ' . implode(' AND ', $conditions);
}
$sqlVols .= ' ORDER BY c.date_vol DESC, c.heure_depart DESC, c.id DESC LIMIT 100';
$stmtVols = $pdo->prepare($sqlVols);
$stmtVols->execute($params);
$vols = $stmtVols->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$volId || $volId <= 0) {
        $errors[] = t('admin_cancel_flight_error_id');
    }
    if ($motif === '') {
        $errors[] = t('admin_cancel_flight_error_reason');
    }
    if ($action === 'cancel' && !hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $errors[] = t('admin_cancel_flight_error_csrf');
    }
    if ($action === 'cancel' && ($_POST['confirm_cancel'] ?? '') !== '1') {
        $errors[] = t('admin_cancel_flight_error_confirmation');
    }

    if (empty($errors)) {
        $stmtVol = $pdo->prepare("\n            SELECT c.id, c.date_vol, c.depart, c.destination, c.fuel_depart, c.fuel_arrivee,\n                   c.payload, c.temps_vol, c.note_du_vol, c.cout_vol,\n                   p.callsign AS pilote_callsign, f.id AS appareil_id, f.immat\n            FROM CARNET_DE_VOL_GENERAL c\n            INNER JOIN PILOTES p ON p.id = c.pilote_id\n            INNER JOIN FLOTTE f ON f.id = c.appareil_id\n            WHERE c.id = :vol_id\n        ");
        $stmtVol->execute(['vol_id' => $volId]);
        $vol = $stmtVol->fetch(PDO::FETCH_ASSOC);

        if (!$vol) {
            $errors[] = t('admin_cancel_flight_error_not_found');
        } else {
            $stmtRecettes = $pdo->prepare("\n                SELECT id, montant, date, commentaire\n                FROM finances_recettes\n                WHERE reference_id = :vol_id AND reference_type = 'vol'\n                ORDER BY id\n            ");
            $stmtRecettes->execute(['vol_id' => $volId]);
            $recettes = $stmtRecettes->fetchAll(PDO::FETCH_ASSOC);

            $stmtDepenses = $pdo->prepare("\n                SELECT id, montant, date, type, commentaire\n                FROM finances_depenses\n                WHERE reference_id = :vol_id\n                ORDER BY id\n            ");
            $stmtDepenses->execute(['vol_id' => $volId]);
            $depenses = $stmtDepenses->fetchAll(PDO::FETCH_ASSOC);

            $usureParNote = [10 => 2, 9 => 3, 8 => 4, 7 => 5, 6 => 6, 5 => 7, 4 => 8, 3 => 9, 2 => 10, 1 => 100];
            $usureCredit = $usureParNote[(int)$vol['note_du_vol']] ?? 0;

            if ($action === 'cancel') {
                try {
                    $pdo->beginTransaction();
                    $stmtLock = $pdo->prepare('SELECT id FROM CARNET_DE_VOL_GENERAL WHERE id = :vol_id AND annule = 0 FOR UPDATE');
                    $stmtLock->execute(['vol_id' => $volId]);
                    if (!$stmtLock->fetchColumn()) {
                        throw new RuntimeException('Flight is no longer active.');
                    }

                    $pdo->prepare('DELETE FROM TRACE_GPS WHERE id = :vol_id')->execute(['vol_id' => $volId]);
                    $pdo->prepare('UPDATE AEROPORTS SET fret = fret + :payload WHERE ident = :airport')->execute(['payload' => $vol['payload'], 'airport' => $vol['depart']]);
                    $pdo->prepare('UPDATE AEROPORTS SET fret = GREATEST(fret - :payload, 0) WHERE ident = :airport')->execute(['payload' => $vol['payload'], 'airport' => $vol['destination']]);

                    $commentaire = 'Annulation du vol #' . $volId . ' par ' . $callsign . ' : ' . $motif;
                    $pdo->prepare("INSERT INTO finances_recettes (date, type, montant, reference_id, reference_type, commentaire) SELECT NOW(), 'annulation_vol', -montant, :vol_id, 'annulation_vol', :commentaire FROM finances_recettes WHERE reference_id = :source_vol_id AND reference_type = 'vol'")->execute(['vol_id' => $volId, 'source_vol_id' => $volId, 'commentaire' => $commentaire]);
                    $pdo->prepare("INSERT INTO finances_depenses (date, type, montant, reference_id, reference_type, commentaire) SELECT NOW(), 'annulation_vol', -montant, :vol_id, 'annulation_vol', :commentaire FROM finances_depenses WHERE reference_id = :source_vol_id AND type = 'maintenance_crash'")->execute(['vol_id' => $volId, 'source_vol_id' => $volId, 'commentaire' => $commentaire]);

                    $pdo->prepare('UPDATE CARNET_DE_VOL_GENERAL SET annule = 1, date_annulation = NOW(), annule_par = :callsign, motif_annulation = :motif WHERE id = :vol_id')->execute(['callsign' => $callsign, 'motif' => $motif, 'vol_id' => $volId]);
                    $pdo->prepare('UPDATE FLOTTE SET etat = LEAST(100, etat + :usure), recettes = (SELECT COALESCE(SUM(cout_vol), 0) FROM CARNET_DE_VOL_GENERAL WHERE appareil_id = :appareil_id AND annule = 0) WHERE id = :appareil_id')->execute(['usure' => $usureCredit, 'appareil_id' => $vol['appareil_id']]);
                    $stmtPilotId = $pdo->prepare('SELECT id FROM PILOTES WHERE callsign = :callsign');
                    $stmtPilotId->execute(['callsign' => $vol['pilote_callsign']]);
                    $pilotId = $stmtPilotId->fetchColumn();
                    if ($pilotId !== false) {
                        $stmtGrade = $pdo->prepare('SELECT id FROM GRADES WHERE seuil_heures <= (SELECT COALESCE(SUM(TIME_TO_SEC(temps_vol)), 0) / 3600 FROM CARNET_DE_VOL_GENERAL WHERE pilote_id = :pilote_id AND annule = 0) ORDER BY niveau DESC LIMIT 1');
                        $stmtGrade->execute(['pilote_id' => $pilotId]);
                        $gradeId = $stmtGrade->fetchColumn();
                        if ($gradeId !== false) {
                            $pdo->prepare('UPDATE PILOTES SET grade_id = :grade_id WHERE id = :pilote_id')->execute(['grade_id' => $gradeId, 'pilote_id' => $pilotId]);
                        }
                    }
                    $pdo->prepare('UPDATE BALANCE_COMMERCIALE SET balance_actuelle = (SELECT COALESCE(SUM(montant), 0) FROM finances_recettes) - (SELECT COALESCE(SUM(montant), 0) FROM finances_depenses), derniere_maj = NOW(), commentaire = :commentaire WHERE id = 1')->execute(['commentaire' => $commentaire]);
                    $pdo->commit();

                    logMsg('[annulation_vol] Vol #' . $volId . ' annulé par ' . $callsign . ' | Motif: ' . $motif, __DIR__ . '/../scripts/logs/annulation_vol.log');
                    $_SESSION['flight_cancellation_success'] = t('admin_cancel_flight_success', ['id' => $volId]);
                    header('Location: admin_annulation_vol.php?show_cancelled=1');
                    exit;
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    logMsg('[annulation_vol] Erreur annulation vol #' . $volId . ': ' . $e->getMessage(), __DIR__ . '/../scripts/logs/annulation_vol.log');
                    $errors[] = t('admin_cancel_flight_error_cancel');
                }
            }
        }
    }
}

if (!empty($_SESSION['flight_cancellation_success'])) {
    $success = $_SESSION['flight_cancellation_success'];
    unset($_SESSION['flight_cancellation_success']);
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>

<main class="admin-cancel-flight">
    <h2><?= t('admin_cancel_flight_title') ?></h2>
    <p class="admin-cancel-flight-intro"><?= t('admin_cancel_flight_intro') ?></p>

    <form method="get" class="admin-cancel-flight-filters">
        <label for="callsign"><?= t('admin_cancel_flight_filter_pilot') ?></label>
        <input type="text" id="callsign" name="callsign" value="<?= htmlspecialchars($pilotFilter) ?>">
        <label for="immat"><?= t('admin_cancel_flight_filter_aircraft') ?></label>
        <input type="text" id="immat" name="immat" value="<?= htmlspecialchars($aircraftFilter) ?>">
        <label for="date"><?= t('admin_cancel_flight_filter_date') ?></label>
        <input type="date" id="date" name="date" value="<?= htmlspecialchars($dateFilter) ?>">
        <label for="airport"><?= t('admin_cancel_flight_filter_airport') ?></label>
        <input type="text" id="airport" name="airport" maxlength="4" value="<?= htmlspecialchars($airportFilter) ?>">
        <label class="admin-cancel-flight-filter-checkbox" for="show_cancelled"><input type="checkbox" id="show_cancelled" name="show_cancelled" value="1" <?= $showCancelled ? 'checked' : '' ?>> <?= t('admin_cancel_flight_filter_cancelled') ?></label>
        <button type="submit" class="btn"><?= t('admin_cancel_flight_filter_button') ?></button>
        <a href="admin_annulation_vol.php" class="btn btn-reset"><?= t('admin_cancel_flight_reset_button') ?></a>
    </form>

    <?php if (!empty($errors)): ?>
        <div class="admin-cancel-flight-error" role="alert">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="admin-cancel-flight-success" role="status"><p><?= htmlspecialchars($success) ?></p></div>
    <?php endif; ?>

    <form method="post" class="form-inscription admin-cancel-flight-form">
        <fieldset class="admin-cancel-flight-selector">
            <legend><?= $showCancelled ? t('admin_cancel_flight_cancelled_flights') : t('admin_cancel_flight_select_flight') ?></legend>
            <?php if (empty($vols)): ?>
                <p><?= t('admin_cancel_flight_no_results') ?></p>
            <?php else: ?>
                <table class="admin-cancel-flight-table">
                    <thead>
                        <tr>
                            <th><?= t('admin_cancel_flight_table_select') ?></th>
                            <th><?= t('admin_cancel_flight_table_date') ?></th>
                            <th><?= t('admin_cancel_flight_table_pilot') ?></th>
                            <th><?= t('admin_cancel_flight_table_aircraft') ?></th>
                            <th><?= t('admin_cancel_flight_table_route') ?></th>
                            <th><?= t('admin_cancel_flight_table_note') ?></th>
                            <?php if ($showCancelled): ?>
                                <th><?= t('admin_cancel_flight_table_cancelled_at') ?></th>
                                <th><?= t('admin_cancel_flight_table_cancelled_by') ?></th>
                                <th><?= t('admin_cancel_flight_table_reason') ?></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vols as $flight): ?>
                            <tr>
                                <td><?php if (!$showCancelled): ?><input type="radio" name="vol_id" value="<?= (int)$flight['id'] ?>" required <?= ((int)$volId === (int)$flight['id']) ? 'checked' : '' ?>><?php endif; ?></td>
                                <td><?= htmlspecialchars($flight['date_vol'] . ' ' . $flight['heure_depart']) ?></td>
                                <td><?= htmlspecialchars($flight['pilote_callsign']) ?></td>
                                <td><?= htmlspecialchars($flight['immat']) ?></td>
                                <td><?= htmlspecialchars($flight['depart']) ?> - <?= htmlspecialchars($flight['destination']) ?></td>
                                <td><?= (int)$flight['note_du_vol'] ?></td>
                                <?php if ($showCancelled): ?>
                                    <td><?= htmlspecialchars($flight['date_annulation'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($flight['annule_par'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($flight['motif_annulation'] ?? '') ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </fieldset>

        <?php if (!$showCancelled): ?>
            <label for="motif"><?= t('admin_cancel_flight_label_reason') ?></label>
            <textarea id="motif" name="motif" rows="4" required><?= htmlspecialchars($motif) ?></textarea>

            <?php if (!empty($vols)): ?>
                <input type="hidden" name="action" value="simulate">
                <button type="submit" class="btn"><?= t('admin_cancel_flight_simulate_button') ?></button>
            <?php endif; ?>
        <?php endif; ?>
    </form>

    <?php if ($vol && empty($errors)): ?>
        <section class="admin-cancel-flight-result">
            <h3><?= t('admin_cancel_flight_result_title') ?></h3>
            <dl class="admin-cancel-flight-details">
                <dt><?= t('admin_cancel_flight_detail_flight') ?></dt><dd>#<?= (int)$vol['id'] ?></dd>
                <dt><?= t('admin_cancel_flight_detail_pilot') ?></dt><dd><?= htmlspecialchars($vol['pilote_callsign']) ?></dd>
                <dt><?= t('admin_cancel_flight_detail_aircraft') ?></dt><dd><?= htmlspecialchars($vol['immat']) ?></dd>
                <dt><?= t('admin_cancel_flight_detail_route') ?></dt><dd><?= htmlspecialchars($vol['depart']) ?> - <?= htmlspecialchars($vol['destination']) ?></dd>
                <dt><?= t('admin_cancel_flight_detail_reason') ?></dt><dd><?= nl2br(htmlspecialchars($motif)) ?></dd>
            </dl>

            <h3><?= t('admin_cancel_flight_operations_title') ?></h3>
            <ul class="admin-cancel-flight-operations">
                <li><?= t('admin_cancel_flight_operation_hide') ?></li>
                <li><?= t('admin_cancel_flight_operation_gps') ?></li>
                <li><?= t('admin_cancel_flight_operation_freight', ['payload' => number_format((float)$vol['payload'], 0, ',', ' ')]) ?></li>
                <li><?= t('admin_cancel_flight_operation_aircraft_revenue', ['amount' => number_format((float)$vol['cout_vol'], 2, ',', ' ')]) ?></li>
                <li><?= t('admin_cancel_flight_operation_wear', ['points' => $usureCredit]) ?></li>
                <li><?= t('admin_cancel_flight_operation_grade') ?></li>
                <li><?= t('admin_cancel_flight_operation_balance') ?></li>
            </ul>

            <h3><?= t('admin_cancel_flight_finance_title') ?></h3>
            <p><?= t('admin_cancel_flight_finance_income', ['count' => count($recettes)]) ?></p>
            <p><?= t('admin_cancel_flight_finance_expense', ['count' => count($depenses)]) ?></p>
            <?php if (!empty($depenses)): ?>
                <p class="admin-cancel-flight-warning"><?= t('admin_cancel_flight_crash_warning') ?></p>
            <?php endif; ?>

            <p class="admin-cancel-flight-simulation"><?= t('admin_cancel_flight_simulation_notice') ?></p>
            <form method="post" class="admin-cancel-flight-confirm-form">
                <input type="hidden" name="vol_id" value="<?= (int)$vol['id'] ?>">
                <input type="hidden" name="motif" value="<?= htmlspecialchars($motif) ?>">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <label class="admin-cancel-flight-confirm"><input type="checkbox" name="confirm_cancel" value="1" required> <?= t('admin_cancel_flight_confirm_label') ?></label>
                <button type="submit" class="btn admin-cancel-flight-confirm-button"><?= t('admin_cancel_flight_confirm_button') ?></button>
            </form>
        </section>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>