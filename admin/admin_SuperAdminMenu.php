<?php
/*
-------------------------------------------------------------
Page : admin_sky0707.php
Emplacement : admin/

Description :
Page d'administration réservée à l'utilisateur SKY0707. Permet de lancer les scripts de maintenance, import, assurance et crédit pour mise au point.
-------------------------------------------------------------
*/
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/menu_logged.php';
require_once __DIR__ . '/../includes/log_func.php';
session_start();

// Vérification de l'utilisateur
if (!isset($_SESSION['user']['callsign']) || $_SESSION['user']['callsign'] !== 'SKY0707') {
    header('Location: /index.php');
    exit;
}

$scripts = [
    'importer_vol.php' => 'Importer les vols depuis FROM_ACARS',
    'maintenance.php' => 'Maintenance flotte',
    'assurance_mensuelle.php' => "Assurance mensuelle",
    'credit_mensualite.php' => "Mensualités crédit",
    'update_fret.php' => "Mise à jour du fret",
    'paiement_salaires_pilotes.php' => "Paiement salaires pilotes"
];

// Scripts nécessitant un CSV
$csvScripts = [
    'import_from_acars.php' => 'Import dans la base FROM_ACARS depuis un CSV'
];

$result = '';

// Exécution script classique
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['script'])) {
    $script = basename($_POST['script']);
    if (strpos($script, 'admin_') === 0) {
        $scriptPath = __DIR__ . '/' . $script;
    } else {
        $scriptPath = __DIR__ . '/../scripts/' . $script;
    }
    if (file_exists($scriptPath)) {
        ob_start();
        include $scriptPath;
        $result = trim(ob_get_clean());
        if ($script === 'update_fret.php') {
            $result = preg_replace('#<\/?(html|body)[^>]*>#i', '', $result);
        }
    } else {
        $result = "<div class='alert error'>Script introuvable : $script</div>";
    }
}

// Traitement import CSV
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csv_script']) && isset($_FILES['csv_file'])) {
    $csvScript = basename($_POST['csv_script']);
    $csvTmp = $_FILES['csv_file']['tmp_name'];
    $csvName = basename($_FILES['csv_file']['name']);
    $uploadDir = __DIR__ . '/../scripts/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $destPath = $uploadDir . uniqid('csv_') . '_' . $csvName;
    if (move_uploaded_file($csvTmp, $destPath)) {
        $scriptPath = __DIR__ . '/../scripts/' . $csvScript;
        if (file_exists($scriptPath)) {
            $_POST['csv_path'] = $destPath;
            ob_start();
            include $scriptPath;
            $result = trim(ob_get_clean());
        } else {
            $result = "<div class='alert error'>Script introuvable : $csvScript</div>";
        }
    } else {
        $result = "<div class='alert error'>Erreur lors de l'upload du fichier CSV.";
    }
}
?>
<main>
    <h2>Super Administration</h2>
    <form method="post" style="margin-bottom:2em;" id="form-admin-sky0707">
        <label for="script">Choisir un script à exécuter :</label>
        <select name="script" id="script">
            <?php foreach ($scripts as $file => $label): ?>
                <option value="<?= htmlspecialchars($file) ?>"><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn" id="btn-executer">Exécuter</button>
    </form>

    <h3>Import CSV</h3>
    <?php foreach ($csvScripts as $csvFile => $csvLabel): ?>
        <form method="post" enctype="multipart/form-data" style="margin-bottom:2em;">
            <input type="hidden" name="csv_script" value="<?= htmlspecialchars($csvFile) ?>">
            <label><?= htmlspecialchars($csvLabel) ?> :
                <input type="file" name="csv_file" accept=".csv" required>
            </label>
            <?php if ($csvFile === 'import_from_acars.php'): ?>
                <label style="margin-left:15px;">
                    <input type="checkbox" name="dryrun" value="1"> Mode simulation (dry-run)
                </label>
            <?php endif; ?>
            <button type="submit" class="btn">Lancer l'import</button>
        </form>
    <?php endforeach; ?>

    <script>
    const form = document.getElementById('form-admin-sky0707');
    const btn = document.getElementById('btn-executer');
    form.addEventListener('submit', function() {
        document.body.style.cursor = 'wait';
        btn.disabled = true;
        btn.textContent = 'Exécution...';
    });
    window.addEventListener('pageshow', function() {
        document.body.style.cursor = 'default';
        btn.disabled = false;
        btn.textContent = 'Exécuter';
    });
    </script>
    <?php if ($result): ?>
        <?= $result ?>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
