<?php
require_once __DIR__ . '/../includes/require_admin.php';
require_once __DIR__ . '/../lang.php';

// Récupère tous les callsigns
$stmt = $pdo->query('SELECT id, callsign FROM PILOTES ORDER BY callsign');
$pilotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupère les infos du pilote sélectionné
// Aucun pilote sélectionné par défaut
$selected_id = isset($_POST['pilote_id']) ? intval($_POST['pilote_id']) : null;
$info = null;
if ($selected_id) {
    $stmt = $pdo->prepare('SELECT * FROM PILOTES WHERE id = ?');
    $stmt->execute([$selected_id]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Mise à jour des infos
$message = '';
if (isset($_POST['update']) && $info) {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $admin = isset($_POST['admin']) ? 1 : 0;
    $actif = isset($_POST['actif']) ? 1 : 0;
    $stmt = $pdo->prepare('UPDATE PILOTES SET prenom = ?, nom = ?, email = ?, admin = ?, actif = ? WHERE id = ?');
    if ($stmt->execute([$prenom, $nom, $email, $admin, $actif, $selected_id])) {
        $message = t('admin_pilots_success_update');
        // Réinitialise la sélection du pilote
        $selected_id = null;
        $info = null;
    } else {
        $message = t('admin_pilots_error_update');
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>
<main>
    <h2><?= t('admin_pilots_title') ?></h2>
    <?php if ($message): ?>
        <div class="<?= strpos($message,t('admin_pilots_success_keyword'))!==false?'admin-pilots-message-success':'admin-pilots-message-error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <form method="post" id="form-pilote">
        <label for="pilote_id"><strong><?= t('admin_pilots_select_callsign') ?></strong></label>
        <select name="pilote_id" id="pilote_id" class="fleet-filter-select input-320" onchange="document.getElementById('form-pilote').submit();">
            <option value=""><?= t('admin_pilots_select_default') ?></option>
            <?php foreach ($pilotes as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $selected_id==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['callsign']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($info): ?>
    <form method="post" class="form-pilote">
        <input type="hidden" name="pilote_id" value="<?= $info['id'] ?>">
        <div class="form-row">
            <label><?= t('admin_pilots_label_callsign') ?></label>
            <input type="text" value="<?= htmlspecialchars($info['callsign']) ?>" disabled>
        </div>
        <div class="form-row">
            <label><?= t('admin_pilots_label_prenom') ?></label>
            <input type="text" name="prenom" value="<?= htmlspecialchars($info['prenom']) ?>">
        </div>
        <div class="form-row">
            <label><?= t('admin_pilots_label_nom') ?></label>
            <input type="text" name="nom" value="<?= htmlspecialchars($info['nom']) ?>">
        </div>
        <div class="form-row">
            <label><?= t('admin_pilots_label_email') ?></label>
            <input type="email" name="email" value="<?= htmlspecialchars($info['email']) ?>">
        </div>
        <div class="form-row">
            <label></label>
            <div class="checkbox-group">
                <label class="checkbox-inline">
                    <input type="checkbox" name="admin" value="1" <?= $info['admin']==1?'checked':'' ?>>
                    <?= t('admin_pilots_label_admin') ?>
                </label>
                <label class="checkbox-inline">
                    <input type="checkbox" name="actif" value="1" <?= (isset($info['actif']) && $info['actif']==1)?'checked':'' ?>>
                    <?= t('admin_pilots_label_actif') ?>
                </label>
            </div>
        </div>
        <div class="form-row form-actions">
            <button type="submit" name="update" class="btn"><?= t('admin_pilots_btn_save') ?></button>
            <button type="button" class="btn btn-reset" onclick="window.location.href='admin_gestion_pilotes.php';"><?= t('admin_pilots_btn_reset') ?></button>
        </div>
    </form>
    <?php endif; ?>
</main>
<?php
include __DIR__ . '/../includes/footer.php';
?>
