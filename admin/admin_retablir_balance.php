<?php
/*
-------------------------------------------------------------
Page : admin_retablir_balance.php
Emplacement : admin/

Description :
Page d'administration pour rétablir la cohérence de la balance commerciale.
Permet de recalculer balance_actuelle à partir des autres champs (recettes, cout_avions, apport_initial, assurance)
et de corriger la base si besoin.
-------------------------------------------------------------
*/
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/menu_logged.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';
session_start();

// Vérification de l'utilisateur
if (!isset($_SESSION['user']['callsign']) || $_SESSION['user']['callsign'] !== 'SKY0707') {
    header('Location: /index.php');
    exit;
}

$logFile = __DIR__ . '/../scripts/logs/admin_retablir_balance.log';
$result = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "SELECT recettes, cout_avions, apport_initial, assurance, balance_actuelle FROM BALANCE_COMMERCIALE";
    $row = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
    $balance_theorique = $row['recettes'] - $row['cout_avions'] + $row['apport_initial'] - $row['assurance'];
    $balance_actuelle = $row['balance_actuelle'];
    $result .= "Valeurs actuelles :\n";
    $result .= "recettes = {$row['recettes']}\n";
    $result .= "cout_avions = {$row['cout_avions']}\n";
    $result .= "apport_initial = {$row['apport_initial']}\n";
    $result .= "assurance = {$row['assurance']}\n";
    $result .= "balance_actuelle = $balance_actuelle\n";
    $result .= "balance_theorique = $balance_theorique\n";
    if (abs($balance_actuelle - $balance_theorique) > 0.01) {
        $result .= "\n[ALERTE] Incohérence détectée. Correction en base...\n";
        $sqlUpdate = "UPDATE BALANCE_COMMERCIALE SET balance_actuelle = :balance";
        $stmt = $pdo->prepare($sqlUpdate);
        $stmt->execute(['balance' => $balance_theorique]);
        logMsg("Correction balance_actuelle : $balance_actuelle => $balance_theorique", $logFile);
        $result .= "Correction effectuée.\n";
    } else {
        $result .= "\nBalance déjà cohérente.\n";
    }
}
?>
<main>
    <h2>Rétablir la cohérence de la balance commerciale</h2>
    <form method="post" style="margin-bottom:2em;">
        <button type="submit" class="btn">Vérifier et corriger</button>
    </form>
    <?php if ($result): ?>
        <pre style="max-width:900px;overflow-x:auto;white-space:pre-wrap;word-break:break-all;"><?= $result ?></pre>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
