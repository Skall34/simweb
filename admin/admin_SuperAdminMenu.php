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
    'importer_vol.php' => 'Importer les vols ACARS',
    'maintenance.php' => 'Maintenance flotte',
    'assurance_mensuelle.php' => "Assurance mensuelle",
    'credit_mensualite.php' => "Mensualités crédit",
    'admin_retablir_balance.php' => 'Rétablir la cohérence balance'
];

$result = '';
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
        $result = ob_get_clean();
    } else {
        $result = "<div class='alert error'>Script introuvable : $script</div>";
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
