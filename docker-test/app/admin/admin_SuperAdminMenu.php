<?php

/*

-------------------------------------------------------------

Page : admin_sky0707.php

Emplacement : admin/



Description :

Page d'administration réservée à l'utilisateur SKY0707. Permet de lancer les scripts de maintenance, import, assurance et crédit pour mise au point.

-------------------------------------------------------------

*/

session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/require_login.php';
require_once __DIR__ . '/../includes/log_func.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/menu_logged.php';

// Vérification de l'utilisateur (super-admins list)
$callsign = $_SESSION['user']['callsign'] ?? ($_SESSION['callsign'] ?? null);
if (! $callsign || !in_array($callsign, explode(',', VA_SUPER_ADMIN_CALLSIGNS))) {
    header('Location: /index.php');
    exit;
}

/*
$scripts = [

    'maintenance.php' => 'Maintenance flotte',

    'assurance_mensuelle.php' => "Assurance mensuelle",

    'credit_mensualite.php' => "Mensualités crédit",

    'update_fret.php' => "Mise à jour du fret",

    'paiement_salaires_pilotes.php' => "Paiement salaires pilotes",

    'expire_reservations.php' => "Purge des réservations expirées"

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
*/
?>

<main>

    <h2>Super Administration</h2>

    <p>Bienvenue dans le menu de super administration. Sélectionnez une action à effectuer :</p>

    <a href="admin_config.php" class="button">Administrer la compagnie</a>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

