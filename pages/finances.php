<?php

session_start();


require __DIR__ . '/../includes/db_connect.php';

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

// Récupération du filtre (immat)
$immatFilter = $_GET['immat'] ?? '';

// Requête avec jointure FLOTTE pour récupérer l'immat
$sql = "
    SELECT 
        f.immat, 
        fi.date_achat, 
        fi.recettes, 
        ft.cout_horaire, 
        ft.cout_appareil AS prix_achat, 
        fi.nb_annees_credit, 
        fi.taux_percent, 
        fi.remboursement, 
        fi.traite_payee_cumulee, 
        fi.reste_a_payer,
        fi.recette_vente, 
        fi.date_vente 
    FROM FINANCES fi 
    LEFT JOIN FLOTTE f ON fi.avion_id = f.id 
    LEFT JOIN FLEET_TYPE ft ON f.fleet_type = ft.id 
    WHERE 1
";

$params = [];

if ($immatFilter !== '') {
    $sql .= " AND f.immat LIKE :immat";
    $params['immat'] = '%' . $immatFilter . '%';
}

$sql .= " ORDER BY fi.date_achat DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $finances = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Erreur SQL : " . htmlspecialchars($e->getMessage());
    exit;
}

function format_chiffre($valeur) {
    if ($valeur === null) return '0';
    if (floor($valeur) == $valeur) {
        return number_format($valeur, 0, ',', ' ');
    } else {
        return number_format($valeur, 2, ',', ' ');
    }
}

// Calcul de la balance financière
$sqlBalance = "SELECT 
    COALESCE(SUM(recettes),0) AS total_recettes, 
    COALESCE(SUM(reste_a_payer),0) AS total_reste_a_payer, 
    COALESCE(SUM(recette_vente),0) AS total_recette_vente 
FROM FINANCES";
$stmtBalance = $pdo->query($sqlBalance);
$balanceRow = $stmtBalance->fetch();
$balance = ($balanceRow['total_recettes'] + $balanceRow['total_recette_vente']) - $balanceRow['total_reste_a_payer'];


include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>

<main>
    <h2>Historique financier des appareils</h2>
    <div style="margin-bottom: 20px; font-size: 1.2em; color: #2c3e50;">
        <strong>Balance financière de la compagnie :</strong> <?= format_chiffre($balance) ?> €
    </div>

    <form method="get" action="finances.php">
        <label for="immat">Filtrer par immatriculation:</label>
        <input type="text" id="immat" name="immat" value="<?= htmlspecialchars($immatFilter) ?>" placeholder="Ex: F-XXXX">
        <button class="btn" type="submit">Filtrer</button>
        <button type="button" class="btn" style="margin-left:10px;" onclick="window.location.href='finances.php';">Réinitialiser</button>
    </form>

    <?php if (empty($finances)): ?>
        <p>Aucune donnée financière trouvée.</p>
    <?php else: ?>
        <?php // ...existing code... ?>

        <table class="table-skywings" border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>Immatriculation</th>
                    <th>Date d'achat</th>
                    <th>Recettes (€)</th>
                    <th>Coût horaire (€)</th>
                    <th>Prix achat (€)</th>
                    <th>Années crédit</th>
                    <th>Taux (%)</th>
                    <th>Remboursement (€)</th>
                    <th>Traités payés cumulés</th>
                    <th>Reste à payer (€)</th>
                    <th>Recette vente (€)</th>
                    <th>Date vente</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($finances as $ligne): ?>
                    <tr>
                        <td><?= htmlspecialchars($ligne['immat'] ?? 'N/A') ?></td>
                        <td><?= !empty($ligne['date_achat']) ? date('d/m/Y', strtotime($ligne['date_achat'])) : 'N/A' ?></td>
                        <td><?= format_chiffre($ligne['recettes'] ?? 0) ?></td>
                        <td><?= format_chiffre($ligne['cout_horaire'] ?? 0) ?></td>
                        <td><?= format_chiffre($ligne['prix_achat'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($ligne['nb_annees_credit'] ?? 'N/A') ?></td>
                        <td><?= format_chiffre($ligne['taux_percent'] ?? 0) ?></td>
                        <td><?= format_chiffre($ligne['remboursement'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($ligne['traite_payee_cumulee'] ?? 'N/A') ?></td>
                        <td><?= format_chiffre($ligne['reste_a_payer'] ?? 0) ?></td>
                        <td><?= format_chiffre($ligne['recette_vente'] ?? 0) ?></td>
                        <td><?= !empty($ligne['date_vente']) ? date('d/m/Y', strtotime($ligne['date_vente'])) : 'N/A' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
