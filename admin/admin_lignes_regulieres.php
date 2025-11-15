<?php
require_once __DIR__ . '/../includes/require_admin.php';

$message = '';
$flash = '';
// Read flash message from session (set after POST redirects)
if (!empty($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
$edit_mode = false;
$line = ['id' => '', 'icao_dep' => '', 'icao_arr' => '', 'created_at' => '', 'updated_at' => ''];

// Handle POST actions: add, update, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $icao_dep = strtoupper(trim($_POST['icao_dep'] ?? ''));
        $icao_arr = strtoupper(trim($_POST['icao_arr'] ?? ''));
        $type_ligne = isset($_POST['type_ligne']) ? (int)$_POST['type_ligne'] : null;
        if ($icao_dep === '' || $icao_arr === '') {
            $message = t('admin_lines_error_icao_required');
        } else {
            try {
                // Check duplicate exact pair
                $chk = $pdo->prepare("SELECT COUNT(*) AS c FROM LIGNES_REGULIERES WHERE icao_dep = :dep AND icao_arr = :arr");
                $chk->execute(['dep' => $icao_dep, 'arr' => $icao_arr]);
                $row = $chk->fetch(PDO::FETCH_ASSOC);
                    if ($row && (int)$row['c'] > 0) {
                        $message = t('admin_lines_error_exists', ['dep' => $icao_dep, 'arr' => $icao_arr]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO LIGNES_REGULIERES (icao_dep, icao_arr, type_ligne, created_at, updated_at) VALUES (:dep, :arr, :type_ligne, NOW(), NOW())");
                        $stmt->execute(['dep' => $icao_dep, 'arr' => $icao_arr, 'type_ligne' => $type_ligne]);
                        $message = t('admin_lines_success_add', ['dep' => $icao_dep, 'arr' => $icao_arr]);
                    }
            } catch (Exception $e) {
                $message = t('admin_lines_error_add', ['error' => htmlspecialchars($e->getMessage())]);
            }
        }
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $icao_dep = strtoupper(trim($_POST['icao_dep'] ?? ''));
        $icao_arr = strtoupper(trim($_POST['icao_arr'] ?? ''));
        $type_ligne = isset($_POST['type_ligne']) ? (int)$_POST['type_ligne'] : null;
        if ($id <= 0 || $icao_dep === '' || $icao_arr === '') {
            $message = t('admin_lines_error_invalid_data');
        } else {
            try {
                // Ensure we don't create a duplicate (excluding current row)
                $chk = $pdo->prepare("SELECT COUNT(*) AS c FROM LIGNES_REGULIERES WHERE icao_dep = :dep AND icao_arr = :arr AND id != :id");
                $chk->execute(['dep' => $icao_dep, 'arr' => $icao_arr, 'id' => $id]);
                $row = $chk->fetch(PDO::FETCH_ASSOC);
                if ($row && (int)$row['c'] > 0) {
                    $message = t('admin_lines_error_exists_other', ['dep' => $icao_dep, 'arr' => $icao_arr]);
                } else {
                    $stmt = $pdo->prepare("UPDATE LIGNES_REGULIERES SET icao_dep = :dep, icao_arr = :arr, type_ligne = :type_ligne, updated_at = NOW() WHERE id = :id");
                    $stmt->execute(['dep' => $icao_dep, 'arr' => $icao_arr, 'type_ligne' => $type_ligne, 'id' => $id]);
                    $message = t('admin_lines_success_update', ['dep' => $icao_dep, 'arr' => $icao_arr]);
                }
            } catch (Exception $e) {
                $message = t('admin_lines_error_update', ['error' => htmlspecialchars($e->getMessage())]);
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $message = t('admin_lines_error_invalid_id');
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM LIGNES_REGULIERES WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $message = t('admin_lines_success_delete', ['id' => $id]);
            } catch (Exception $e) {
                $message = t('admin_lines_error_delete', ['error' => htmlspecialchars($e->getMessage())]);
            }
        }
    }

    // After any POST, store the message in session and redirect to show it (flash)
    if (!empty($message)) {
        $_SESSION['flash_message'] = $message;
    }
    header('Location: admin_lignes_regulieres.php');
    exit;
}

// If edit requested, load line
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM LIGNES_REGULIERES WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $line = $row;
            $edit_mode = true;
        }
    }
}

// Fetch all lines (inclure le label du type via LEFT JOIN)
$stmt = $pdo->query("
    SELECT lr.id, lr.icao_dep, lr.icao_arr, lr.type_ligne, tl.label AS type_label, lr.created_at, lr.updated_at
    FROM LIGNES_REGULIERES lr
    LEFT JOIN TYPE_LIGNE tl ON lr.type_ligne = tl.id
    ORDER BY lr.icao_dep, lr.icao_arr
");
$lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Récupérer la liste des types de ligne pour la combobox ---
try {
    $stmtTypes = $pdo->query("SELECT id, label FROM TYPE_LIGNE ORDER BY label ASC");
    $typeLignes = $stmtTypes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $typeLignes = [];
}

// --- NOUVEAU : lire les filtres GET ---
$filter_dep = isset($_GET['icao_dep']) ? strtoupper(trim($_GET['icao_dep'])) : '';
$filter_arr = isset($_GET['icao_arr']) ? strtoupper(trim($_GET['icao_arr'])) : '';
$filter_type = (isset($_GET['type_ligne']) && $_GET['type_ligne'] !== '') ? (int)$_GET['type_ligne'] : null;

// --- NOUVEAU : construire requête avec filtres ---
$sql = "
    SELECT lr.id, lr.icao_dep, lr.icao_arr, lr.type_ligne, tl.label AS type_label, lr.created_at, lr.updated_at
    FROM LIGNES_REGULIERES lr
    LEFT JOIN TYPE_LIGNE tl ON lr.type_ligne = tl.id
";
$conds = [];
$params = [];
if ($filter_dep !== '') {
    $conds[] = "lr.icao_dep LIKE :dep";
    $params['dep'] = $filter_dep . '%';
}
if ($filter_arr !== '') {
    $conds[] = "lr.icao_arr LIKE :arr";
    $params['arr'] = $filter_arr . '%';
}
if ($filter_type !== null) {
    $conds[] = "lr.type_ligne = :type_ligne";
    $params['type_ligne'] = $filter_type;
}
if (!empty($conds)) {
    $sql .= " WHERE " . implode(' AND ', $conds);
}
$sql .= " ORDER BY lr.icao_dep ASC, lr.icao_arr ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>

<main>
    <h2><?= t('admin_lines_title', ['count' => count($lines)]) ?></h2>

    <?php if ($message): ?>
        <div class="success"><?= $message ?></div>
    <?php endif; ?>

    <section class="admin-lines-form-section">
        <div class="narrow-table-wrapper admin-lines-form-wrapper">
        <h3><?= $edit_mode ? t('admin_lines_form_edit_title') : t('admin_lines_form_add_title') ?></h3>
    <form method="post" class="form-inscription admin-lines-inline-form">
             <?php if ($edit_mode): ?>
                 <input type="hidden" name="id" value="<?= htmlspecialchars($line['id']) ?>">
             <?php endif; ?>

            <label>
                <span><?= t('admin_lines_label_icao_dep') ?></span>
                <input name="icao_dep" required value="<?= htmlspecialchars($line['icao_dep']) ?>" class="fleet-filter-input input-160 input-uppercase" oninput="this.value = this.value.toUpperCase();">
            </label>

            <label>
                <span><?= t('admin_lines_label_icao_arr') ?></span>
                <input name="icao_arr" required value="<?= htmlspecialchars($line['icao_arr']) ?>" class="fleet-filter-input input-160 input-uppercase" oninput="this.value = this.value.toUpperCase();">
            </label>

            <label>
                <span><?= t('admin_lines_label_type') ?></span>
                <select name="type_ligne" class="fleet-filter-select input-160">
                    <option value=""><?= t('admin_lines_type_none') ?></option>
                    <?php foreach ($typeLignes as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= (isset($line['type_ligne']) && (int)$line['type_ligne'] === (int)$t['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div class="admin-lines-form-actions">
                 <div>
                     <?php if ($edit_mode): ?>
                         <input type="hidden" name="id" value="<?= htmlspecialchars($line['id']) ?>">
                         <button class="btn" type="submit" name="action" value="update"><?= t('admin_lines_btn_update') ?></button>
                         <a href="admin_lignes_regulieres.php" class="btn admin-lines-btn-cancel"><?= t('admin_lines_btn_cancel') ?></a>
                     <?php else: ?>
                         <button class="btn" type="submit" name="action" value="add"><?= t('admin_lines_btn_add') ?></button>
                     <?php endif; ?>
                 </div>
             </div>
             </form>
         </div>
     </section>

    <section>
        <h3><?= t('admin_lines_list_title') ?></h3>

        <!-- Filters placed under the table title, single-line (inputs inline with buttons) -->
        <form method="get" class="filters-form admin-lines-filters-form">
            <label><?= t('admin_lines_filter_dep') ?>
                <input name="icao_dep" placeholder="<?= t('admin_lines_filter_dep_placeholder') ?>" value="<?= htmlspecialchars($filter_dep) ?>" aria-label="<?= t('admin_lines_filter_dep') ?>" class="fleet-filter-input input-160" oninput="this.value = this.value.toUpperCase();">
            </label>

            <label><?= t('admin_lines_filter_arr') ?>
                <input name="icao_arr" placeholder="<?= t('admin_lines_filter_arr_placeholder') ?>" value="<?= htmlspecialchars($filter_arr) ?>" aria-label="<?= t('admin_lines_filter_arr') ?>" class="fleet-filter-input input-160" oninput="this.value = this.value.toUpperCase();">
            </label>

            <label><?= t('admin_lines_filter_type') ?>
                <select name="type_ligne" class="fleet-filter-select input-160">
                    <option value=""><?= t('admin_lines_filter_type_all') ?></option>
                    <?php foreach ($typeLignes as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= ($filter_type !== null && $filter_type === (int)$t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="admin-lines-form-actions">
                <button class="btn" type="submit"><?= t('admin_lines_btn_filter') ?></button>
                <!-- Keep the current visual style of the reset button exactly as-is (model for the site) -->
                <a href="admin_lignes_regulieres.php" class="btn admin-lines-btn-reset"><?= t('admin_lines_btn_reset') ?></a>
            </div>
        </form>

        <div class="narrow-table-wrapper">
            <table class="table-skywings admin-lines-table">
            <thead>
                <tr>
                    <th><?= t('admin_lines_table_icao_dep') ?></th>
                    <th><?= t('admin_lines_table_icao_arr') ?></th>
                    <th><?= t('admin_lines_table_type') ?></th>
                    <th><?= t('admin_lines_table_created') ?></th>
                    <th><?= t('admin_lines_table_updated') ?></th>
                    <th><?= t('admin_lines_table_actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lines as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['icao_dep']) ?></td>
                        <td><?= htmlspecialchars($r['icao_arr']) ?></td>
                        <td><?= htmlspecialchars($r['type_label'] ?? $r['type_ligne']) ?></td>
                    <td><?= $r['created_at'] ? htmlspecialchars(date('d/m/Y H:i:s', strtotime($r['created_at']))) : '-' ?></td>
                    <td><?= $r['updated_at'] ? htmlspecialchars(date('d/m/Y H:i:s', strtotime($r['updated_at']))) : '-' ?></td>
                        <td>
                            <a href="admin_lignes_regulieres.php?edit=<?= $r['id'] ?>"><?= t('admin_lines_link_edit') ?></a>
                            &nbsp;|&nbsp;
                            <a href="#" onclick="if(confirm('<?= t('admin_lines_confirm_delete') ?>')){ document.getElementById('delete-form-<?= $r['id'] ?>').submit(); } return false;"><?= t('admin_lines_link_delete') ?></a>
                            <form id="delete-form-<?= $r['id'] ?>" method="post" class="hidden">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
