<?php
require_once __DIR__ . '/../includes/require_admin.php';
require_once __DIR__ . '/../lang.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/menu_logged.php';

$config_path = __DIR__ . '/../config.ini';

// Lire le fichier ini avec sections
$config = [];
if (file_exists($config_path)) {
    $config = parse_ini_file($config_path, true, INI_SCANNER_TYPED);
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // On reconstruit la config en partant de celle lue
    $new = $config;

    // Parcourir POST et mapper sur sections/keys via name="section__key"
    foreach ($_POST as $name => $value) {
        if ($name === 'save' || $name === 'csrf') continue;
        // nom attendu: section__key
        if (strpos($name, '__') !== false) {
            list($section, $key) = explode('__', $name, 2);
            if (!isset($new[$section])) $new[$section] = [];
            // Normaliser certaines valeurs booleans
            $v = $value;
            if ($v === 'true' || $v === 'false') {
                $v = ($v === 'true');
            }
            $new[$section][$key] = $v;
        }
    }

    // Générer le contenu INI
    $ini_lines = [];
    $ini_lines[] = "; Configuration mise à jour via admin_config.php - " . date('Y-m-d H:i:s');
    foreach ($new as $section => $pairs) {
        $ini_lines[] = "\n[{$section}]";
        foreach ($pairs as $k => $v) {
            if (is_bool($v)) {
                $val = $v ? 'true' : 'false';
            } elseif (is_numeric($v) && (string)(int)$v === (string)$v) {
                $val = $v;
            } else {
                // Escape double quotes
                $val = str_replace('"', '\\"', (string)$v);
                $val = '"' . $val . '"';
            }
            $ini_lines[] = "{$k} = {$val}";
        }
    }

    $content = implode("\n", $ini_lines) . "\n";

    $ok = @file_put_contents($config_path, $content);
    if ($ok === false) {
        $message = t('admin_config_write_error');
    } else {
        $message = t('admin_config_saved_success');
        // recharger
        $config = parse_ini_file($config_path, true, INI_SCANNER_TYPED);
    }
}

// Helper for rendering input values
function cfg_val($cfg, $section, $key, $default = '') {
    if (isset($cfg[$section]) && array_key_exists($key, $cfg[$section])) return $cfg[$section][$key];
    return $default;
}

?>
<main>
    <div class="container" style="max-width:900px;margin:24px auto;background:#fff;padding:28px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.06);">
        <h2 style="color:#1a3552;margin-bottom:18px;"><?= t('admin_config_title') ?></h2>
        <?php if ($message): ?>
            <div style="margin-bottom:18px;color:#155724;background:#d4edda;padding:10px 16px;border-radius:6px;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="post" style="display:flex;flex-direction:column;gap:18px;">
            <?php foreach ($config as $section => $pairs): ?>
                <fieldset style="border:1px solid #eef2f6;padding:12px;border-radius:8px;">
                    <legend style="font-weight:700;color:#1a3552;"><?= htmlspecialchars(strtoupper($section)) ?></legend>
                    <div style="display:flex;flex-wrap:wrap;gap:16px;">
                        <?php foreach ($pairs as $k => $v): ?>
                            <label style="display:flex;flex-direction:column;font-weight:600;color:#1a3552;min-width:260px;">
                                <?= htmlspecialchars($k) ?>
                                <?php
                                    $name = $section . '__' . $k;
                                    if (is_bool($v)):
                                ?>
                                    <select name="<?= htmlspecialchars($name) ?>" style="margin-top:6px;padding:8px;border-radius:4px;border:1px solid #bbb;max-width:220px;">
                                        <option value="true" <?= $v ? 'selected' : '' ?>>true</option>
                                        <option value="false" <?= !$v ? 'selected' : '' ?>>false</option>
                                    </select>
                                <?php elseif (is_numeric($v)): ?>
                                    <input type="number" name="<?= htmlspecialchars($name) ?>" step="any" value="<?= htmlspecialchars($v) ?>" style="margin-top:6px;padding:8px;border-radius:4px;border:1px solid #bbb;max-width:220px;">
                                <?php else: ?>
                                    <input type="text" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars($v) ?>" style="margin-top:6px;padding:8px;border-radius:4px;border:1px solid #bbb;max-width:420px;">
                                <?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
            <?php endforeach; ?>

            <div>
                <button type="submit" name="save" class="btn btn-primary" style="padding:10px 16px;"><?= t('admin_config_save_button') ?></button>
            </div>
        </form>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
