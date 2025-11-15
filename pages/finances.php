<?php

session_start();

require_once __DIR__ . '/../includes/db_connect.php';

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

// 1. Balance commerciale
$sqlBalance = "SELECT balance_actuelle FROM BALANCE_COMMERCIALE";
$stmtBalance = $pdo->query($sqlBalance);
$balance = $stmtBalance->fetchColumn();

// 2. Synthèse recettes
$sqlRecettes = "SELECT COUNT(*) AS nb, SUM(montant) AS total, MAX(date) AS derniere FROM finances_recettes";
$recettes = $pdo->query($sqlRecettes)->fetch(PDO::FETCH_ASSOC);

// 3. Synthèse dépenses
$sqlDepenses = "SELECT COUNT(*) AS nb, SUM(montant) AS total, MAX(date) AS derniere FROM finances_depenses";
$depenses = $pdo->query($sqlDepenses)->fetch(PDO::FETCH_ASSOC);

// 4. Solde calculé
$solde_calcule = floatval($recettes['total'] ?? 0) - floatval($depenses['total'] ?? 0);

// 5. Dernières opérations (optionnel)
$dernieres_recettes = $pdo->query("SELECT * FROM finances_recettes ORDER BY date DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
$dernieres_depenses = $pdo->query("SELECT * FROM finances_depenses ORDER BY date DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);



function format_chiffre($valeur) {
    if ($valeur === null) return '0';
    if (!is_numeric($valeur)) return $valeur;
    if (floor($valeur) == $valeur) {
        return number_format($valeur, 0, ',', ' ');
    } else {
        return number_format($valeur, 2, ',', ' ');
    }
}

function color_chiffre($valeur) {
    $val = floatval($valeur);
    $class = ($val < 0) ? 'finances-value-negative' : (($val > 0) ? 'finances-value-positive' : '');
    return '<span class="' . $class . '">' . format_chiffre($valeur) . '</span>';
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>
<main>
    <h2><?= t('finances_title') ?></h2>

    <div class="compte-section finances-cards-row">
        <div class="finances-card">
            <div class="finances-card-value">
                <span class="icon">💰</span> <?= color_chiffre($balance) ?> €
            </div>
            <div class="finances-card-label"><?= t('finances_balance') ?></div>
        </div>
        <div class="finances-card">
            <div class="finances-card-value-income">
                <span class="icon">⬆️</span> <?= format_chiffre($recettes['total'] ?? 0) ?> €
            </div>
            <div class="finances-card-label">
                <?= t('finances_income') ?> (<?= $recettes['nb'] ?? 0 ?> <?= t('finances_ops') ?>)<br>
                <span class="small-text"><?= t('finances_last') ?> <?= !empty($recettes['derniere']) ? date('d/m/Y H:i', strtotime($recettes['derniere'])) : 'N/A' ?></span>
            </div>
        </div>
        <div class="finances-card">
            <div class="finances-card-value-expense">
                <span class="icon">⬇️</span> <?= format_chiffre($depenses['total'] ?? 0) ?> €
            </div>
            <div class="finances-card-label">
                <?= t('finances_expense') ?> (<?= $depenses['nb'] ?? 0 ?> <?= t('finances_ops') ?>)<br>
                <span class="small-text"><?= t('finances_last') ?> <?= !empty($depenses['derniere']) ? date('d/m/Y H:i', strtotime($depenses['derniere'])) : 'N/A' ?></span>
            </div>
        </div>
    </div>

    <details class="finances-details">
        <summary><?= t('finances_last_10_income') ?></summary>
        <table class="table-skywings">
            <thead><tr><th><?= t('finances_date') ?></th><th><?= t('finances_amount') ?></th><th><?= t('finances_type') ?></th><th><?= t('finances_comment') ?></th></tr></thead>
            <tbody>
                <?php foreach ($dernieres_recettes as $r): ?>
                <tr>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($r['date']))) ?></td>
                    <td><?= format_chiffre($r['montant']) ?></td>
                    <td><?= htmlspecialchars($r['reference_type']) ?></td>
                    <td><?= htmlspecialchars($r['commentaire'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </details>

    <details class="finances-details">
        <summary><?= t('finances_last_10_expense') ?></summary>
        <table class="table-skywings">
            <thead><tr><th><?= t('finances_date') ?></th><th><?= t('finances_amount') ?></th><th><?= t('finances_type') ?></th><th><?= t('finances_comment') ?></th></tr></thead>
            <tbody>
                <?php foreach ($dernieres_depenses as $d): ?>
                <tr>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($d['date']))) ?></td>
                    <td><?= format_chiffre($d['montant']) ?></td>
                    <td><?= htmlspecialchars($d['reference_type']) ?></td>
                    <td><?= htmlspecialchars($d['commentaire'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </details>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
