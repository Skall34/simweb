<?php
require_once __DIR__ . '/../includes/require_admin.php';
require_once __DIR__ . '/../lang.php';

$message = '';
$erreur = '';

// Recalcule PISTES.surface_code depuis SURFACES_MAP (brut -> code, sinon UNK)
function recalcSurfaceCodes(PDO $pdo): void {
    $pdo->exec("UPDATE `PISTES` p LEFT JOIN `SURFACES_MAP` m ON m.brut = p.surface
                SET p.surface_code = COALESCE(m.code, IF(p.surface IS NULL, NULL, 'UNK'))");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'surface_ajouter') {
            $stmt = $pdo->prepare("INSERT INTO SURFACES (code, libelle) VALUES (:code, :libelle)");
            $stmt->execute(['code' => strtoupper(trim($_POST['code'] ?? '')), 'libelle' => trim($_POST['libelle'] ?? '')]);
            $message = str_replace('{code}', htmlspecialchars(strtoupper(trim($_POST['code'] ?? ''))), t('admin_surfaces_success_create'));
        } elseif ($action === 'surface_maj') {
            $stmt = $pdo->prepare("UPDATE SURFACES SET libelle = :libelle WHERE code = :code");
            $stmt->execute(['libelle' => trim($_POST['libelle'] ?? ''), 'code' => $_POST['code']]);
            $message = str_replace('{code}', htmlspecialchars($_POST['code']), t('admin_surfaces_success_update'));
        } elseif ($action === 'surface_suppr') {
            $code = $_POST['code'];
            $nbP = (int)$pdo->query("SELECT COUNT(*) FROM PISTES WHERE surface_code = " . $pdo->quote($code))->fetchColumn();
            $nbM = (int)$pdo->query("SELECT COUNT(*) FROM SURFACES_MAP WHERE code = " . $pdo->quote($code))->fetchColumn();
            if ($nbP > 0 || $nbM > 0) {
                $erreur = str_replace(['{n}', '{m}', '{code}'], [$nbP, $nbM, htmlspecialchars($code)], t('admin_surfaces_error_used'));
            } else {
                $stmt = $pdo->prepare("DELETE FROM SURFACES WHERE code = :code");
                $stmt->execute(['code' => $code]);
                $message = str_replace('{code}', htmlspecialchars($code), t('admin_surfaces_success_delete'));
            }
        } elseif ($action === 'map_ajouter') {
            $stmt = $pdo->prepare("INSERT INTO SURFACES_MAP (brut, code) VALUES (:brut, :code)");
            $stmt->execute(['brut' => trim($_POST['brut'] ?? ''), 'code' => $_POST['code']]);
            recalcSurfaceCodes($pdo);
            $message = t('admin_surfaces_success_map');
        } elseif ($action === 'map_maj') {
            $stmt = $pdo->prepare("UPDATE SURFACES_MAP SET brut = :brut, code = :code WHERE brut = :brut_old");
            $stmt->execute(['brut' => trim($_POST['brut'] ?? ''), 'code' => $_POST['code'], 'brut_old' => $_POST['brut_old']]);
            recalcSurfaceCodes($pdo);
            $message = t('admin_surfaces_success_map');
        } elseif ($action === 'map_suppr') {
            $stmt = $pdo->prepare("DELETE FROM SURFACES_MAP WHERE brut = :brut");
            $stmt->execute(['brut' => $_POST['brut']]);
            recalcSurfaceCodes($pdo);
            $message = t('admin_surfaces_success_map');
        }
    } catch (PDOException $e) {
        $erreur = t('admin_surfaces_error_db') . ' ' . htmlspecialchars($e->getMessage());
    }
}

$filtre = trim($_GET['filtre'] ?? '');

$surfaces = $pdo->query("
    SELECT s.code, s.libelle,
           (SELECT COUNT(*) FROM PISTES p WHERE p.surface_code = s.code) AS nb_pistes,
           (SELECT COUNT(*) FROM SURFACES_MAP m WHERE m.code = s.code) AS nb_map
    FROM SURFACES s
    ORDER BY s.code
")->fetchAll(PDO::FETCH_ASSOC);

$sqlMap = "SELECT brut, code FROM SURFACES_MAP";
$params = [];
if ($filtre !== '') {
    $sqlMap .= " WHERE brut LIKE :f OR code = :c";
    $params = ['f' => '%' . $filtre . '%', 'c' => $filtre];
}
$sqlMap .= " ORDER BY brut";
$stmtMap = $pdo->prepare($sqlMap);
$stmtMap->execute($params);
$map = $stmtMap->fetchAll(PDO::FETCH_ASSOC);
$totalMap = (int)$pdo->query("SELECT COUNT(*) FROM SURFACES_MAP")->fetchColumn();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>

<main>
    <h2><?= t('admin_surfaces_title') ?></h2>

    <?php if ($message): ?>
        <div class="success"><?= $message ?></div>
    <?php endif; ?>
    <?php if ($erreur): ?>
        <div class="error"><?= $erreur ?></div>
    <?php endif; ?>

    <h3><?= t('admin_surfaces_codes_section') ?></h3>
    <table class="pistes-table">
        <tr>
            <th><?= t('admin_surfaces_code') ?></th>
            <th><?= t('admin_surfaces_libelle') ?></th>
            <th><?= t('admin_surfaces_usages') ?></th>
            <th></th>
        </tr>
        <?php foreach ($surfaces as $s): ?>
        <tr>
            <form method="post">
                <td><strong><?= htmlspecialchars($s['code']) ?></strong><input type="hidden" name="code" value="<?= htmlspecialchars($s['code']) ?>"></td>
                <td><input name="libelle" class="form-input" value="<?= htmlspecialchars($s['libelle']) ?>"></td>
                <td><?= (int)$s['nb_pistes'] ?> / <?= (int)$s['nb_map'] ?></td>
                <td>
                    <button type="submit" name="action" value="surface_maj" class="btn"><?= t('admin_surfaces_save') ?></button>
                    <button type="submit" name="action" value="surface_suppr" class="btn btn-reset"><?= t('admin_surfaces_delete') ?></button>
                </td>
            </form>
        </tr>
        <?php endforeach; ?>
        <tr>
            <form method="post">
                <td><input name="code" class="form-input" maxlength="10" required placeholder="ex: ASPH"></td>
                <td><input name="libelle" class="form-input" required placeholder="<?= t('admin_surfaces_libelle') ?>"></td>
                <td></td>
                <td><button type="submit" name="action" value="surface_ajouter" class="btn"><?= t('admin_surfaces_add') ?></button></td>
            </form>
        </tr>
    </table>

    <h3><?= t('admin_surfaces_map_section') ?> (<?= $totalMap ?>)</h3>
    <form method="get" class="form-inscription">
        <input name="filtre" class="form-input input-250" value="<?= htmlspecialchars($filtre) ?>" placeholder="<?= t('admin_surfaces_filter') ?>">
        <button type="submit" class="btn"><?= t('admin_surfaces_filter') ?></button>
    </form>
    <table class="pistes-table">
        <tr>
            <th><?= t('admin_surfaces_brut') ?></th>
            <th><?= t('admin_surfaces_code') ?></th>
            <th></th>
        </tr>
        <?php foreach ($map as $m): ?>
        <tr>
            <form method="post">
                <td>
                    <input type="hidden" name="brut_old" value="<?= htmlspecialchars($m['brut']) ?>">
                    <input name="brut" class="form-input" value="<?= htmlspecialchars($m['brut']) ?>">
                </td>
                <td>
                    <select name="code" class="form-input">
                        <?php foreach ($surfaces as $s): ?>
                            <option value="<?= htmlspecialchars($s['code']) ?>" <?= $s['code'] === $m['code'] ? 'selected' : '' ?>><?= htmlspecialchars($s['code']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <button type="submit" name="action" value="map_maj" class="btn"><?= t('admin_surfaces_save') ?></button>
                    <button type="submit" name="action" value="map_suppr" class="btn btn-reset"><?= t('admin_surfaces_delete') ?></button>
                </td>
            </form>
        </tr>
        <?php endforeach; ?>
        <tr>
            <form method="post">
                <td><input name="brut" class="form-input" required placeholder="<?= t('admin_surfaces_brut') ?>"></td>
                <td>
                    <select name="code" class="form-input">
                        <?php foreach ($surfaces as $s): ?>
                            <option value="<?= htmlspecialchars($s['code']) ?>"><?= htmlspecialchars($s['code']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><button type="submit" name="action" value="map_ajouter" class="btn"><?= t('admin_surfaces_add') ?></button></td>
            </form>
        </tr>
    </table>
    <p><?= t('admin_surfaces_recalc_note') ?></p>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
