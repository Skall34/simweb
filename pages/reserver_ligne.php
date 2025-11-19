<?php
require_once __DIR__ . '/../includes/require_login.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../includes/mail_utils.php';
require_once __DIR__ . '/../lang.php';

$pilote_id = $_SESSION['user']['id'];

$ligne_id = isset($_GET['ligne_id']) ? intval($_GET['ligne_id']) : 0;
if ($ligne_id <= 0) {
    header('Location: lignes_regulieres.php');
    exit;
}

// Récupérer la ligne
$stmt = $pdo->prepare('SELECT * FROM LIGNES_REGULIERES WHERE id = ?');
$stmt->execute([$ligne_id]);
$ligne = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ligne) {
    header('Location: lignes_regulieres.php');
    exit;
}

// Récupérer la flotte disponible (non réservée) en joignant le libellé du fleet_type
$stmt = $pdo->prepare('SELECT f.id, f.immat, f.fleet_type, COALESCE(ft.fleet_type, "") AS fleet_type_label FROM FLOTTE f LEFT JOIN FLEET_TYPE ft ON f.fleet_type = ft.id WHERE f.actif = 1 AND f.status=0 AND (f.reservee = 0 OR f.reservee IS NULL) ORDER BY f.immat');
$stmt->execute();
$flotte = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process reservation
$message = '';
$message_html = '';
// Check if the pilote already has an active reservation (reserved or in_flight)
$canReserve = true;
$stmtActive = $pdo->prepare("SELECT id, statut FROM RESERVATIONS WHERE pilote_id = ? AND statut IN ('reserved','in_flight') LIMIT 1");
$stmtActive->execute([$pilote_id]);
$active = $stmtActive->fetch(PDO::FETCH_ASSOC);
if ($active) {
    $canReserve = false;
    $message = t('reserver_ligne_error_already_active');
    $message_html = t('reserver_ligne_error_already_active_html');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $immat = $_POST['immat'] ?? '';
    // Re-check at POST time to avoid race conditions
    $stmtActive2 = $pdo->prepare("SELECT id FROM RESERVATIONS WHERE pilote_id = ? AND statut IN ('reserved','in_flight') LIMIT 1 FOR UPDATE");
    $stmtActive2->execute([$pilote_id]);
    $active2 = $stmtActive2->fetch(PDO::FETCH_ASSOC);
    if ($active2) {
        $message = t('reserver_ligne_error_already_active_short');
        $message_html = t('reserver_ligne_error_already_active_html');
        $canReserve = false;
    }
    if (!$immat) {
        $message = t('reserver_ligne_error_no_aircraft');
    } else {
        if (!$canReserve) {
            // do not proceed
        } else {
        try {
            $pdo->beginTransaction();
            // Vérifier si l'appareil est toujours disponible
            $stmtChk = $pdo->prepare('SELECT reservee FROM FLOTTE WHERE immat = ? FOR UPDATE');
            $stmtChk->execute([$immat]);
            $row = $stmtChk->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new Exception(t('reserver_ligne_error_aircraft_not_found'));
            if ($row['reservee']) {
                throw new Exception(t('reserver_ligne_error_aircraft_reserved'));
            }
            // Marquer l'avion comme réservé
            $stmtUpd = $pdo->prepare('UPDATE FLOTTE SET reservee = 1 WHERE immat = ?');
            $stmtUpd->execute([$immat]);
                // Rechercher une réservation existante pour cette paire (ligne_id, immat)
                // (la clé unique uniq_ligne_immat empêche d'insérer si une ligne existe déjà — même si elle est 'cancelled')
                $stmtExist = $pdo->prepare('SELECT id, statut FROM RESERVATIONS WHERE ligne_id = ? AND immat = ? LIMIT 1 FOR UPDATE');
                $stmtExist->execute([$ligne_id, $immat]);
                $existing = $stmtExist->fetch(PDO::FETCH_ASSOC);
                if ($existing) {
                    // Si elle est déjà réservée (statut 'reserved') par quelqu'un d'autre, bloquer
                    if ($existing['statut'] === 'reserved') {
                        throw new Exception(t('reserver_ligne_error_aircraft_reserved'));
                    }
                    // Réutiliser l'enregistrement existant (historique conservé)
                    $stmtUpdRes = $pdo->prepare('UPDATE RESERVATIONS SET pilote_id = ?, statut = ?, date_reservation = NOW(), date_debut = NULL, date_fin = NULL, acars_cle = NULL WHERE id = ?');
                    $stmtUpdRes->execute([$pilote_id, 'reserved', $existing['id']]);
                } else {
                    // Insérer une nouvelle réservation si aucune n'existe
                    $stmtIns = $pdo->prepare('INSERT INTO RESERVATIONS (ligne_id, pilote_id, immat, statut, date_reservation) VALUES (?, ?, ?, ?, NOW())');
                    $stmtIns->execute([$ligne_id, $pilote_id, $immat, 'reserved']);
                }
            $pdo->commit();
            $message = t('reserver_ligne_success');
            // send notification mail to admin
            try {
                $callsign = $_SESSION['user']['callsign'] ?? '';
                $subject = "Nouvelle réservation : " . $immat . " (" . htmlspecialchars($ligne['icao_dep']) . "→" . htmlspecialchars($ligne['icao_arr']) . ")";
                $body = "<h3>Nouvelle réservation</h3>" .
                        "<ul>" .
                        "<li><strong>Pilote :</strong> " . htmlspecialchars($callsign) . " (id: " . intval($pilote_id) . ")</li>" .
                        "<li><strong>Ligne :</strong> " . htmlspecialchars($ligne['icao_dep']) . " → " . htmlspecialchars($ligne['icao_arr']) . " (id: " . intval($ligne_id) . ")</li>" .
                        "<li><strong>Appareil :</strong> " . htmlspecialchars($immat) . "</li>" .
                        "<li><strong>Date :</strong> " . date('Y-m-d H:i:s') . "</li>" .
                        "</ul>";
                $mailResult = sendSummaryMail($subject, $body);
                if ($mailResult !== true) {
                    logMsg('Envoi mail réservation échoué: ' . $mailResult, __DIR__ . '/../scripts/logs/reservations.log');
                } else {
                    logMsg('Mail de réservation envoyé pour immat=' . $immat, __DIR__ . '/../scripts/logs/reservations.log');
                }
            } catch (Exception $e) {
                logMsg('Exception envoi mail réservation: ' . $e->getMessage(), __DIR__ . '/../scripts/logs/reservations.log');
            }
            // set a session flash and redirige vers la liste pour afficher le message de confirmation
            $_SESSION['flash_reserved'] = 1;
            header('Location: lignes_regulieres.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = t('reserver_ligne_error_exception', ['error' => $e->getMessage()]);
            logMsg('Erreur réservation: ' . $e->getMessage(), __DIR__ . '/../scripts/logs/reservations.log');
        }
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>
<main>
    <h2><?= t('reserver_ligne_title', ['dep' => htmlspecialchars($ligne['icao_dep']), 'arr' => htmlspecialchars($ligne['icao_arr'])]) ?></h2>
    <?php if ($message || $message_html): ?>
        <div class="error-msg">
            <?= $message_html ? $message_html : htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <form method="post">
        <label for="immat"><?= t('reserver_ligne_label_immat') ?></label>
        <select name="immat" id="immat">
            <option value=""><?= t('reserver_ligne_select_default') ?></option>
            <?php foreach ($flotte as $a): ?>
                <option value="<?= htmlspecialchars($a['immat']) ?>"><?= htmlspecialchars($a['immat']) ?><?= $a['fleet_type_label'] !== '' ? ' (' . htmlspecialchars($a['fleet_type_label']) . ')' : '' ?></option>
            <?php endforeach; ?>
        </select>
        <div class="form-actions">
            <button type="submit" class="btn"><?= t('reserver_ligne_btn_confirm') ?></button>
            <button type="button" class="btn btn-secondary" id="cancelBtn"><?= t('reserver_ligne_btn_cancel') ?></button>
        </div>
    <script>
        document.getElementById('cancelBtn').addEventListener('click', function () {
            window.location.href = 'lignes_regulieres.php';
        });
    </script>
    </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
