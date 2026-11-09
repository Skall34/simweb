<?php
require_once __DIR__ . '/../includes/require_admin.php';
require_once __DIR__ . '/../lang.php';

$message = '';
$erreur = '';

// Recalcule PISTES.surface_code par simple correspondance du code dans SURFACES
// (PISTES.surface ne contient que des codes canoniques depuis la migration 008).
function recalcSurfaceCodes(PDO $pdo, ?string $ident = null): void {
    $sql = "UPDATE `PISTES` p
            LEFT JOIN `SURFACES` s ON s.`code` = p.`surface`
            SET p.`surface_code` = COALESCE(s.`code`, IF(p.`surface` IS NULL, NULL, 'UNK'))";
    if ($ident !== null) {
        $sql .= " WHERE p.`aeroport_ident` = :ident";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['ident' => $ident]);
    } else {
        $pdo->exec($sql);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'surface_ajouter') {
            $stmt = $pdo->prepare("INSERT INTO SURFACES (code, libelle) VALUES (:code, :libelle)");
            $stmt->execute([
                'code' => strtoupper(trim($_POST['code'] ?? '')),
                'libelle' => trim($_POST['libelle'] ?? ''),
            ]);
            recalcSurfaceCodes($pdo);
            $message = str_replace('{code}', htmlspecialchars(strtoupper(trim($_POST['code'] ?? ''))), t('admin_surfaces_success_create'));
        } elseif ($action === 'surface_maj') {
            $stmt = $pdo->prepare("UPDATE SURFACES SET libelle = :libelle WHERE code = :code");
            $stmt->execute([
                'libelle' => trim($_POST['libelle'] ?? ''),
                'code' => $_POST['code'],
            ]);
            recalcSurfaceCodes($pdo);
            $message = str_replace('{code}', htmlspecialchars($_POST['code']), t('admin_surfaces_success_update'));
        } elseif ($action === 'surface_suppr') {
            $code = $_POST['code'];
            $nbP = (int)$pdo->query("SELECT COUNT(*) FROM PISTES WHERE surface_code = " . $pdo->quote($code))->fetchColumn();
            if ($nbP > 0) {
                $erreur = str_replace(['{n}', '{code}'], [$nbP, htmlspecialchars($code)], t('admin_surfaces_error_used2'));
            } else {
                $stmt = $pdo->prepare("DELETE FROM SURFACES WHERE code = :code");
                $stmt->execute(['code' => $code]);
                $message = str_replace('{code}', htmlspecialchars($code), t('admin_surfaces_success_delete'));
            }
        }
    } catch (PDOException $e) {
        $erreur = t('admin_surfaces_error_db') . ' ' . htmlspecialchars($e->getMessage());
    }
}

$surfaces = $pdo->query("
    SELECT s.code, s.libelle,
           (SELECT COUNT(*) FROM PISTES p WHERE p.surface_code = s.code) AS nb_pistes
    FROM SURFACES s
    ORDER BY s.code
")->fetchAll(PDO::FETCH_ASSOC);

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
                <td><?= (int)$s['nb_pistes'] ?></td>
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
    <p><?= t('admin_surfaces_recalc_note') ?></p>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
