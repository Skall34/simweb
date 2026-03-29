<?php
require_once __DIR__ . '/../includes/require_admin.php';
require_once __DIR__ . '/../lang.php';
// Traitement du formulaire
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirect = 'admin_fleet_type.php';

    if ($action === 'add' || $action === 'update') {
        $fleet_type = trim($_POST['fleet_type'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $cout_horaire = floatval($_POST['cout_horaire'] ?? 0);
        $cout_appareil = floatval($_POST['cout_appareil'] ?? 0);
        $cout_maintenance = floatval($_POST['cout_maintenance'] ?? 0);

        if ($fleet_type === '' || $type === '') {
            $errorMessage = t('admin_fleet_type_error_required');
        } else {
            try {
                if ($action === 'add') {
                    // Vérifier si le fleet_type existe déjà
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM FLEET_TYPE WHERE fleet_type = :fleet_type");
                    $stmt->execute(['fleet_type' => $fleet_type]);
                    $exists = $stmt->fetchColumn();
                    if ($exists) {
                        $errorMessage = t('admin_fleet_type_error_exists');
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO FLEET_TYPE (fleet_type, type, cout_horaire, cout_appareil, cout_maintenance) VALUES (:fleet_type, :type, :cout_horaire, :cout_appareil, :cout_maintenance)");
                        $stmt->execute([
                            'fleet_type' => $fleet_type,
                            'type' => $type,
                            'cout_horaire' => $cout_horaire,
                            'cout_appareil' => $cout_appareil,
                            'cout_maintenance' => $cout_maintenance
                        ]);
                        $newId = $pdo->lastInsertId();
                        $_SESSION['flash_message'] = str_replace('{fleet_type}', htmlspecialchars($fleet_type), t('admin_fleet_type_success_add'));
                        if ($newId) {
                            $redirect = 'admin_fleet_type.php?edit=' . (int)$newId;
                        }
                    }
                } else {
                    // update
                    $id = (int)($_POST['id'] ?? 0);
                    if ($id <= 0) {
                        $errorMessage = t('admin_fleet_type_error_invalid_id');
                    } else {
                        // éviter doublon (exclure current id)
                        $chk = $pdo->prepare("SELECT COUNT(*) FROM FLEET_TYPE WHERE fleet_type = :fleet_type AND id != :id");
                        $chk->execute(['fleet_type' => $fleet_type, 'id' => $id]);
                        if ($chk->fetchColumn() > 0) {
                            $errorMessage = t('admin_fleet_type_error_exists_other');
                        } else {
                            $stmt = $pdo->prepare("UPDATE FLEET_TYPE SET fleet_type = :fleet_type, type = :type, cout_horaire = :cout_horaire, cout_appareil = :cout_appareil, cout_maintenance = :cout_maintenance WHERE id = :id");
                            $stmt->execute(['fleet_type' => $fleet_type, 'type' => $type, 'cout_horaire' => $cout_horaire, 'cout_appareil' => $cout_appareil, 'cout_maintenance' => $cout_maintenance, 'id' => $id]);
                            $_SESSION['flash_message'] = t('admin_fleet_type_success_update');
                            // Après une modification, revenir sur la page principale pour vider le formulaire
                            $redirect = 'admin_fleet_type.php';
                        }
                    }
                }
            } catch (PDOException $e) {
                $errorMessage = "Erreur SQL : " . htmlspecialchars($e->getMessage());
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $errorMessage = t('admin_fleet_type_error_invalid_id');
        } else {
            try {
                // vérifier qu'aucun appareil n'est attaché à ce fleet_type
                $chk = $pdo->prepare("SELECT COUNT(*) FROM FLOTTE WHERE fleet_type = :id");
                $chk->execute(['id' => $id]);
                $cnt = (int)$chk->fetchColumn();
                if ($cnt > 0) {
                    $_SESSION['flash_message'] = str_replace('{count}', $cnt, t('admin_fleet_type_error_delete_attached'));
                } else {
                    $stmt = $pdo->prepare("DELETE FROM FLEET_TYPE WHERE id = :id");
                    $stmt->execute(['id' => $id]);
                    $_SESSION['flash_message'] = t('admin_fleet_type_success_delete');
                }
            } catch (PDOException $e) {
                $errorMessage = t('admin_fleet_type_error_delete') . htmlspecialchars($e->getMessage());
            }
        }
    }

    // redirect back to avoid re-submission
    if (!empty($errorMessage)) {
        $_SESSION['flash_message'] = $errorMessage;
    }
    header('Location: ' . $redirect);
    exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
// Récupérer toute la table FLEET_TYPE pour affichage en deux colonnes
$fleetTypes = [];
try {
    $stmt = $pdo->query("SELECT id, fleet_type, type, cout_horaire, cout_appareil, cout_maintenance FROM FLEET_TYPE ORDER BY fleet_type ASC");
    $fleetTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ignore erreur
}

// Edit mode: charger l'enregistrement si demandé
$edit_mode = false;
$current = ['id' => 0, 'fleet_type' => '', 'type' => '', 'cout_horaire' => '', 'cout_appareil' => '', 'cout_maintenance' => ''];
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    if ($eid > 0) {
        $s = $pdo->prepare("SELECT id, fleet_type, type, cout_horaire, cout_appareil, cout_maintenance FROM FLEET_TYPE WHERE id = :id");
        $s->execute(['id' => $eid]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $edit_mode = true;
            $current = $row;
        }
    }
}

?>

<main style="display:flex; flex-direction:row; align-items:flex-start; gap:40px;">
    <div style="flex:1; min-width:280px; max-width:370px;">
        <h2><?= $edit_mode ? t('admin_fleet_type_edit_title') : t('admin_fleet_type_add_title') ?></h2>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div style="background:#e6f9e6;color:#0b6623;padding:10px 12px;border-radius:8px;font-weight:600;font-size:1.05em;margin-bottom:10px;">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message']); endif; ?>
        <?php if ($errorMessage): ?>
            <p style="color: red; font-weight:bold;"><?= t($errorMessage) ?></p>
        <?php endif; ?>

    <form method="post" action="" class="form-inscription">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="id" value="<?= (int)$current['id'] ?>">
            <?php endif; ?>

            <label><?= t('admin_fleet_type_label_name') ?></label>
            <input type="text" id="fleet_type" name="fleet_type" class="form-input input-250" required value="<?= htmlspecialchars($current['fleet_type']) ?>">

            <label><?= t('admin_fleet_type_label_category') ?></label>
            <select id="type" name="type" required class="fleet-filter-select input-250">
                <option value="">-- <?= t('admin_fleet_type_select_option') ?> --</option>
                <?php $cats = ['Monomoteur','Bimoteur','Liner','Helico']; foreach($cats as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= ($current['type']==$c)?'selected':'' ?>><?= t('admin_fleet_type_cat_' . strtolower($c)) ?></option>
                <?php endforeach; ?>
            </select>

            <label><?= t('admin_fleet_type_label_hourly_cost') ?></label>
            <input type="number" id="cout_horaire" name="cout_horaire" step="100" class="form-input input-250" required value="<?= htmlspecialchars($current['cout_horaire']) ?>">

            <label><?= t('admin_fleet_type_label_plane_cost') ?></label>
            <input type="number" id="cout_appareil" name="cout_appareil" step="100" class="form-input input-250" required value="<?= htmlspecialchars($current['cout_appareil']) ?>">

            <label><?= t('admin_fleet_type_label_maintenance_cost') ?></label>
            <input type="number" id="cout_maintenance" name="cout_maintenance" step="100" class="form-input input-250" value="<?= htmlspecialchars($current['cout_maintenance']) ?>">

            <div class="form-actions">
                <?php if ($edit_mode): ?>
                    <button type="submit" name="action" value="update" class="btn btn-small"><?= t('admin_fleet_type_update_button') ?></button>
                    <button type="button" class="btn btn-reset btn-small" onclick="window.location.href='admin_fleet_type.php';"><?= t('admin_fleet_type_reset_button') ?></button>
                <?php else: ?>
                    <button type="submit" name="action" value="add" class="btn btn-small"><?= t('admin_fleet_type_add_button') ?></button>
                    <button type="button" class="btn btn-reset btn-small" onclick="window.location.href='admin_fleet_type.php';"><?= t('admin_fleet_type_reset_button') ?></button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <aside style="min-width:900px;max-width:1800px;margin-left:40px;margin-right:auto;background:#f7fbff;border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,0.04);padding:18px 16px 12px 16px;align-self:center;">
        <h3 style="margin-top:0;margin-bottom:12px;font-size:1.1em;color:#0066cc;"><?= t('admin_fleet_type_existing_title') ?></h3>
        <?php
        $total = count($fleetTypes);
        $mid = (int)ceil($total / 2);
        $col1 = array_slice($fleetTypes, 0, $mid);
        $col2 = array_slice($fleetTypes, $mid);
        ?>
        <div style="display: flex; gap: 32px; align-items: flex-start;">
            <div class="table-section" style="min-width:420px;">
                <table class="table-skywings" style="width:100%; white-space:nowrap; word-break:keep-all;">
                    <thead>
                        <tr>
                            <th class="fleet_type"><?= t('admin_fleet_type_col_name') ?></th>
                            <th class="type"><?= t('admin_fleet_type_col_category') ?></th>
                            <th class="cout_horaire"><?= t('admin_fleet_type_col_hourly_cost') ?></th>
                            <th class="prix"><?= t('admin_fleet_type_col_plane_cost') ?></th>
                            <th class="cout_maintenance"><?= t('admin_fleet_type_col_maintenance_cost') ?></th>
                            <th><?= t('admin_fleet_type_col_actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($col1 as $ft): ?>
                        <tr>
                            <td class="fleet_type"><?= htmlspecialchars($ft['fleet_type']) ?></td>
                            <td class="type" style="color:#444; font-style:italic;"><?= htmlspecialchars($ft['type']) ?></td>
                            <td class="cout_horaire" style="text-align:right;"><?= number_format((float)$ft['cout_horaire'], 2, ',', ' ') ?></td>
                            <td class="prix" style="text-align:right;font-weight:bold;"><?= number_format((float)$ft['cout_appareil'], 0, '', ' ') ?></td>
                            <td class="cout_maintenance" style="text-align:center;"><?= number_format((float)$ft['cout_maintenance'], 0, '', ' ') ?></td>
                            <td>
                                <a href="admin_fleet_type.php?edit=<?= (int)$ft['id'] ?>"><?= t('admin_fleet_type_edit_link') ?></a>
                                &nbsp;|&nbsp;
                                <a href="#" onclick="if(confirm('<?= t('admin_fleet_type_confirm_delete') ?>')){ document.getElementById('delete-form-<?= (int)$ft['id'] ?>').submit(); } return false;"><?= t('admin_fleet_type_delete_link') ?></a>
                                <form id="delete-form-<?= (int)$ft['id'] ?>" method="post" style="display:none;">
                                    <input type="hidden" name="id" value="<?= (int)$ft['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-section" style="min-width:420px;">
                <table class="table-skywings" style="width:100%; white-space:nowrap; word-break:keep-all;">
                    <thead>
                        <tr>
                            <th class="fleet_type"><?= t('admin_fleet_type_col_name') ?></th>
                            <th class="type"><?= t('admin_fleet_type_col_category') ?></th>
                            <th class="cout_horaire"><?= t('admin_fleet_type_col_hourly_cost') ?></th>
                            <th class="prix"><?= t('admin_fleet_type_col_plane_cost') ?></th>
                            <th class="cout_maintenance"><?= t('admin_fleet_type_col_maintenance_cost') ?></th>
                            <th><?= t('admin_fleet_type_col_actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($col2 as $ft): ?>
                        <tr>
                            <td class="fleet_type"><?= htmlspecialchars($ft['fleet_type']) ?></td>
                            <td class="type" style="color:#444; font-style:italic;"><?= htmlspecialchars($ft['type']) ?></td>
                            <td class="cout_horaire" style="text-align:right;"><?= number_format((float)$ft['cout_horaire'], 2, ',', ' ') ?></td>
                            <td class="prix" style="text-align:right;font-weight:bold;"><?= number_format((float)$ft['cout_appareil'], 0, '', ' ') ?></td>
                            <td class="cout_maintenance" style="text-align:center;"><?= number_format((float)$ft['cout_maintenance'], 0, '', ' ') ?></td>
                            <td>
                                <a href="admin_fleet_type.php?edit=<?= (int)$ft['id'] ?>"><?= t('admin_fleet_type_edit_link') ?></a>
                                &nbsp;|&nbsp;
                                <a href="#" onclick="if(confirm('<?= t('admin_fleet_type_confirm_delete') ?>')){ document.getElementById('delete-form-<?= (int)$ft['id'] ?>').submit(); } return false;"><?= t('admin_fleet_type_delete_link') ?></a>
                                <form id="delete-form-<?= (int)$ft['id'] ?>" method="post" style="display:none;">
                                    <input type="hidden" name="id" value="<?= (int)$ft['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </aside>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
