<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';

if (!isset($_SESSION['user']['id'])) {
    header('Location: ../login.php');
    exit;
}
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
    $message = 'Vous avez déjà une réservation active. Veuillez la terminer ou l\'annuler avant d\'en créer une nouvelle.';
    $message_html = 'Vous avez déjà une réservation active. Veuillez la terminer ou l\'annuler avant d\'en créer une nouvelle. <a href="/pages/mon_compte.php">Gérer</a>';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $immat = $_POST['immat'] ?? '';
    // Re-check at POST time to avoid race conditions
    $stmtActive2 = $pdo->prepare("SELECT id FROM RESERVATIONS WHERE pilote_id = ? AND statut IN ('reserved','in_flight') LIMIT 1 FOR UPDATE");
    $stmtActive2->execute([$pilote_id]);
    $active2 = $stmtActive2->fetch(PDO::FETCH_ASSOC);
    if ($active2) {
        $message = 'Vous avez déjà une réservation active. Impossible d\'en créer une nouvelle.';
        $message_html = 'Vous avez déjà une réservation active. Impossible d\'en créer une nouvelle. <a href="/pages/mon_compte.php">Gérer</a>';
        $canReserve = false;
    }
    if (!$immat) {
        $message = 'Veuillez sélectionner un appareil.';
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
            if (!$row) throw new Exception('Appareil introuvable');
            if ($row['reservee']) {
                throw new Exception('Appareil déjà réservé.');
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
                        throw new Exception('Appareil déjà réservé.');
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
            $message = 'Réservation enregistrée.';
            // set a session flash and redirige vers la liste pour afficher le message de confirmation
            $_SESSION['flash_reserved'] = 1;
            header('Location: lignes_regulieres.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Erreur lors de la réservation : ' . $e->getMessage();
            logMsg('Erreur réservation: ' . $e->getMessage(), __DIR__ . '/../scripts/logs/reservations.log');
        }
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>
<main>
    <h2>Réserver la ligne <?= htmlspecialchars($ligne['icao_dep']) ?> → <?= htmlspecialchars($ligne['icao_arr']) ?></h2>
    <?php if ($message || $message_html): ?>
        <div style="color:#d60000;font-weight:bold;">
            <?= $message_html ? $message_html : htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <form method="post">
        <label for="immat">Choisir un appareil :</label>
        <select name="immat" id="immat">
            <option value="">-- Sélectionner --</option>
            <?php foreach ($flotte as $a): ?>
                <option value="<?= htmlspecialchars($a['immat']) ?>"><?= htmlspecialchars($a['immat']) ?><?= $a['fleet_type_label'] !== '' ? ' (' . htmlspecialchars($a['fleet_type_label']) . ')' : '' ?></option>
            <?php endforeach; ?>
        </select>
        <div style="margin-top:16px;">
            <button type="submit" class="btn">Confirmer la réservation</button>
            <button type="button" class="btn" id="cancelBtn" style="margin-left:8px">Annuler</button>
        </div>
    <script>
        document.getElementById('cancelBtn').addEventListener('click', function () {
            window.location.href = 'lignes_regulieres.php';
        });
    </script>
    </form>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
