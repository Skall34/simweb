<?php
require_once __DIR__ . '/../includes/require_admin.php';
require_once __DIR__ . '/../lang.php';

// Traitement du formulaire
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirect = 'admin_type_ligne.php';

    if ($action === 'add' || $action === 'update') {
        $label = trim($_POST['Label'] ?? '');

        if ($label === '') {
            $errorMessage = t('admin_type_ligne_error_required');
        } else {
            try {
                if ($action === 'add') {
                    // Vérifier si le label existe déjà
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TYPE_LIGNE WHERE Label = :label");
                    $stmt->execute(['label' => $label]);
                    $exists = $stmt->fetchColumn();
                    if ($exists) {
                        $errorMessage = t('admin_type_ligne_error_exists');
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO TYPE_LIGNE (Label) VALUES (:label)");
                        $stmt->execute(['label' => $label]);
                        $newId = $pdo->lastInsertId();
                        $_SESSION['flash_message'] = str_replace('{label}', htmlspecialchars($label), t('admin_type_ligne_success_add'));
                        if ($newId) {
                            $redirect = 'admin_type_ligne.php?edit=' . (int)$newId;
                        }
                    }
                } else {
                    // update
                    $id = (int)($_POST['id'] ?? 0);
                    if ($id <= 0) {
                        $errorMessage = t('admin_type_ligne_error_invalid_id');
                    } else {
                        // Éviter doublon (exclure current id)
                        $chk = $pdo->prepare("SELECT COUNT(*) FROM TYPE_LIGNE WHERE Label = :label AND id != :id");
                        $chk->execute(['label' => $label, 'id' => $id]);
                        if ($chk->fetchColumn() > 0) {
                            $errorMessage = t('admin_type_ligne_error_exists_other');
                        } else {
                            $stmt = $pdo->prepare("UPDATE TYPE_LIGNE SET Label = :label WHERE id = :id");
                            $stmt->execute(['label' => $label, 'id' => $id]);
                            $_SESSION['flash_message'] = t('admin_type_ligne_success_update');
                            // Après une modification, revenir sur la page principale
                            $redirect = 'admin_type_ligne.php';
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
            $errorMessage = t('admin_type_ligne_error_invalid_id');
        } else {
            try {
                // Vérifier qu'aucune ligne régulière n'est attachée à ce type
                $chk = $pdo->prepare("SELECT COUNT(*) FROM LIGNES_REGULIERES WHERE type_ligne = :id");
                $chk->execute(['id' => $id]);
                $cnt = (int)$chk->fetchColumn();
                if ($cnt > 0) {
                    $_SESSION['flash_message'] = str_replace('{count}', $cnt, t('admin_type_ligne_error_delete_attached'));
                } else {
                    $stmt = $pdo->prepare("DELETE FROM TYPE_LIGNE WHERE id = :id");
                    $stmt->execute(['id' => $id]);
                    $_SESSION['flash_message'] = t('admin_type_ligne_success_delete');
                }
            } catch (PDOException $e) {
                $errorMessage = t('admin_type_ligne_error_delete') . htmlspecialchars($e->getMessage());
            }
        }
    }

    // Redirect back to avoid re-submission
    if (!empty($errorMessage)) {
        $_SESSION['flash_message'] = $errorMessage;
    }
    header('Location: ' . $redirect);
    exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';

// Récupérer tous les types de ligne pour affichage en deux colonnes
$typesLigne = [];
try {
    $stmt = $pdo->query("SELECT id, Label FROM TYPE_LIGNE ORDER BY Label ASC");
    $typesLigne = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ignore erreur
}

// Edit mode: charger l'enregistrement si demandé
$edit_mode = false;
$current = ['id' => 0, 'Label' => ''];
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    if ($eid > 0) {
        $s = $pdo->prepare("SELECT id, Label FROM TYPE_LIGNE WHERE id = :id");
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
        <h2><?= $edit_mode ? t('admin_type_ligne_edit_title') : t('admin_type_ligne_add_title') ?></h2>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div style="background:#e6f9e6;color:#0b6623;padding:10px 12px;border-radius:8px;font-weight:600;font-size:1.05em;margin-bottom:10px;">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message']); endif; ?>
        <?php if ($errorMessage): ?>
            <p style="color: red; font-weight:bold;"><?= htmlspecialchars($errorMessage) ?></p>
        <?php endif; ?>

        <form method="post" action="" class="form-inscription">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="id" value="<?= (int)$current['id'] ?>">
            <?php endif; ?>

            <label><?= t('admin_type_ligne_label_name') ?></label>
            <input type="text" id="Label" name="Label" class="form-input input-250" required value="<?= htmlspecialchars($current['Label']) ?>">

            <div class="form-actions">
                <?php if ($edit_mode): ?>
                    <button type="submit" name="action" value="update" class="btn btn-small"><?= t('admin_type_ligne_update_button') ?></button>
                    <button type="button" class="btn btn-reset btn-small" onclick="window.location.href='admin_type_ligne.php';"><?= t('admin_type_ligne_reset_button') ?></button>
                <?php else: ?>
                    <button type="submit" name="action" value="add" class="btn btn-small"><?= t('admin_type_ligne_add_button') ?></button>
                    <button type="button" class="btn btn-reset btn-small" onclick="window.location.href='admin_type_ligne.php';"><?= t('admin_type_ligne_reset_button') ?></button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <aside style="min-width:400px;max-width:600px;margin-left:40px;margin-right:auto;background:#f7fbff;border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,0.04);padding:18px 16px 12px 16px;align-self:center;">
        <h3 style="margin-top:0;margin-bottom:12px;font-size:1.1em;color:#0066cc;"><?= t('admin_type_ligne_existing_title') ?></h3>
        
        <table class="table-skywings" style="width:100%; white-space:nowrap; word-break:keep-all;">
            <thead>
                <tr>
                    <th><?= t('admin_type_ligne_col_name') ?></th>
                    <th><?= t('admin_type_ligne_col_actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($typesLigne as $tl): ?>
                <tr>
                    <td><?= htmlspecialchars($tl['Label']) ?></td>
                    <td>
                        <a href="admin_type_ligne.php?edit=<?= (int)$tl['id'] ?>"><?= t('admin_type_ligne_edit_link') ?></a>
                        &nbsp;|&nbsp;
                        <a href="#" onclick="if(confirm('<?= t('admin_type_ligne_confirm_delete') ?>')){ document.getElementById('delete-form-<?= (int)$tl['id'] ?>').submit(); } return false;"><?= t('admin_type_ligne_delete_link') ?></a>
                        <form id="delete-form-<?= (int)$tl['id'] ?>" method="post" style="display:none;">
                            <input type="hidden" name="id" value="<?= (int)$tl['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </aside>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
