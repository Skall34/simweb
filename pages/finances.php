<?php

session_start();


require __DIR__ . '/../includes/db_connect.php';

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
    $color = ($val < 0) ? 'red' : (($val > 0) ? 'green' : 'inherit');
    return '<span style="color:' . $color . ';">' . format_chiffre($valeur) . '</span>';
}




include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>


<main>
    <h2>Synthèse financière de la compagnie</h2>

    <div style="margin-bottom: 20px; font-size: 1.2em; color: #2c3e50;">
        <strong>Balance commerciale :</strong> <?= color_chiffre($balance) ?> €
    </div>

    <div style="margin-bottom: 18px;">
        <strong>Total recettes :</strong> <?= format_chiffre($recettes['total'] ?? 0) ?> €<br>
        <span style="color:#555;">Nombre d'opérations : <?= $recettes['nb'] ?? 0 ?> | Dernière recette : <?= !empty($recettes['derniere']) ? date('d/m/Y H:i', strtotime($recettes['derniere'])) : 'N/A' ?></span>
    </div>

    <div style="margin-bottom: 18px;">
        <strong>Total dépenses :</strong> <?= format_chiffre($depenses['total'] ?? 0) ?> €<br>
        <span style="color:#555;">Nombre d'opérations : <?= $depenses['nb'] ?? 0 ?> | Dernière dépense : <?= !empty($depenses['derniere']) ? date('d/m/Y H:i', strtotime($depenses['derniere'])) : 'N/A' ?></span>
    </div>


    <div style="margin-bottom: 24px; font-size:1.1em; color:#0d47a1;">
        <strong>Solde calculé (recettes - dépenses) :</strong> <?= color_chiffre($solde_calcule) ?> €
    </div>

    <details style="margin-bottom:18px;">
        <summary style="font-weight:bold;cursor:pointer;">Voir les 10 dernières recettes</summary>
        <table class="table-skywings">
            <thead><tr><th>Date</th><th>Montant (€)</th><th>Type</th><th>Immat</th><th>Commentaire</th></tr></thead>
            <tbody>
                <?php foreach ($dernieres_recettes as $r): ?>
                <tr>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($r['date_operation']))) ?></td>
                    <td><?= format_chiffre($r['montant']) ?></td>
                    <td><?= htmlspecialchars($r['type_operation']) ?></td>
                    <td><?= htmlspecialchars($r['immat'] ?? '') ?></td>
                    <td><?= htmlspecialchars($r['commentaire'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </details>

    <details>
        <summary style="font-weight:bold;cursor:pointer;">Voir les 10 dernières dépenses</summary>
        <table class="table-skywings">
            <thead><tr><th>Date</th><th>Montant (€)</th><th>Type</th><th>Immat</th><th>Commentaire</th></tr></thead>
            <tbody>
                <?php foreach ($dernieres_depenses as $d): ?>
                <tr>
                    <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($d['date_operation']))) ?></td>
                    <td><?= format_chiffre($d['montant']) ?></td>
                    <td><?= htmlspecialchars($d['type_operation']) ?></td>
                    <td><?= htmlspecialchars($d['immat'] ?? '') ?></td>
                    <td><?= htmlspecialchars($d['commentaire'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </details>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
