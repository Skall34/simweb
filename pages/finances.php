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
        ft.fleet_type AS fleet_type_libelle,
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

// Récupère la balance financière depuis la table BALANCE_FINANCIERE
$sqlBalance = "SELECT balance_actuelle FROM BALANCE_COMMERCIALE";
$stmtBalance = $pdo->query($sqlBalance);
$balance = $stmtBalance->fetchColumn();


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

        <!-- Tableau d'en-tête fixe -->
        <table class="table-skywings table-header-fixed-finances">
            <thead>
                <tr>
                    <th>Immatriculation</th>
                    <th>Fleet type</th>
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
        </table>
        <!-- Tableau scrollable des données -->
        <div class="table-scroll-wrapper-finances">
            <table class="table-skywings">
                <tbody>
                    <?php foreach ($finances as $ligne): ?>
                        <tr>
                            <td><?= htmlspecialchars($ligne['immat'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($ligne['fleet_type_libelle'] ?? 'N/A') ?></td>
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
        </div>
        <style>
            .table-skywings th, .table-skywings td {
                padding: 4px 6px;
                font-size: 14px;
                box-sizing: border-box;
            }
            .table-skywings th {
                white-space: normal;
                word-break: break-word;
            }
            .table-header-fixed-finances th {
                background: #0d47a1;
                color: #fff;
                border-bottom: 2px solid #08306b;
                z-index: 10;
                box-shadow: 0 2px 4px rgba(0,0,0,0.03);
                text-align: center;
                font-weight: bold;
                letter-spacing: 0.5px;
            }
            .table-header-fixed-finances {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                table-layout: auto;
                margin-bottom: 0;
            }
            .table-scroll-wrapper-finances {
                width: 100%;
                max-height: 60vh;
                overflow-y: auto;
                overflow-x: auto;
                border-top: none;
            }
        </style>
        <script>
        // Synchronise dynamiquement la largeur des colonnes du header avec celles du tableau de données
        function syncHeaderWidthsFinances() {
            const headerTable = document.querySelector('.table-header-fixed-finances');
            const dataTable = document.querySelector('.table-scroll-wrapper-finances .table-skywings');
            if (!headerTable || !dataTable) return;
            const headerCells = headerTable.querySelectorAll('th');
            const dataRow = dataTable.querySelector('tr');
            if (!dataRow) return;
            const dataCells = dataRow.querySelectorAll('td');
            if (headerCells.length !== dataCells.length) return;
            // Reset widths
            headerCells.forEach(th => th.style.width = '');
            dataCells.forEach(td => td.style.width = '');
            // Get computed widths from data cells
            for (let i = 0; i < headerCells.length; i++) {
                const width = dataCells[i].getBoundingClientRect().width + 'px';
                headerCells[i].style.width = width;
                dataCells[i].style.width = width;
            }
            // Ajuste la largeur du headerTable pour ne pas dépasser le dataTable (évite le débordement dû au scrollbar)
            headerTable.style.width = dataTable.getBoundingClientRect().width + 'px';
        }
        window.addEventListener('load', syncHeaderWidthsFinances);
        window.addEventListener('resize', syncHeaderWidthsFinances);
        document.querySelector('.table-scroll-wrapper-finances').addEventListener('scroll', function() {
            document.querySelector('.table-header-fixed-finances').scrollLeft = this.scrollLeft;
        });
        </script>

    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
