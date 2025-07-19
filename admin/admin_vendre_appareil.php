<?php
session_start();
require __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
require_once __DIR__ . '/../includes/fonctions_financieres.php';

$successMessage = '';
$errorMessage = '';

// Récupérer la liste des appareils actifs avec infos financières
try {
    $stmt = $pdo->query("SELECT f.id, f.immat, f.type, f.localisation, f.hub, f.fleet_type, fi.reste_a_payer, fi.date_achat, fi.date_vente, fi.recette_vente, fi.recettes FROM FLOTTE f LEFT JOIN FINANCES fi ON f.id = fi.avion_id WHERE f.actif = 1 ORDER BY f.immat");
    $flotte_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $flotte = [];
    foreach ($flotte_raw as $avion) {
        // Calcul du prix de vente prévu
        if (!is_null($avion['reste_a_payer']) && $avion['reste_a_payer'] > 0) {
            // Minoration de 10% pour pénalités de remboursement anticipé
            $prix_vente_prevu = round($avion['reste_a_payer'] * 0.9, 2);
            $mode_achat = 'crédit';
        } else {
            // Récupérer le prix neuf
            $prix_neuf = null;
            if (!empty($avion['fleet_type'])) {
                $stmtPrix = $pdo->prepare("SELECT cout_appareil FROM FLEET_TYPE WHERE id = :ftid");
                $stmtPrix->execute(['ftid' => $avion['fleet_type']]);
                $prix_neuf = $stmtPrix->fetchColumn();
            }
            // Usure plus forte si comptant : -30%
            $prix_vente_prevu = $prix_neuf ? round($prix_neuf * 0.7, 2) : '';
            $mode_achat = 'comptant';
        }
        $avion['prix_vente_prevu'] = $prix_vente_prevu;
        $avion['mode_achat'] = $mode_achat;
        $flotte[] = $avion;
    }
} catch (PDOException $e) {
    $errorMessage = "Erreur lors de la récupération de la flotte : " . htmlspecialchars($e->getMessage());
    $flotte = [];
}

// Traitement de la vente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['avion_id'])) {
    $avion_id = intval($_POST['avion_id']);
    try {
        // Récupérer le reste à payer
        $stmtFinance = $pdo->prepare("SELECT reste_a_payer FROM FINANCES WHERE avion_id = :avion_id");
        $stmtFinance->execute(['avion_id' => $avion_id]);
        $reste_a_payer = $stmtFinance->fetchColumn();

        // Calculer la recette de vente
        if ($reste_a_payer > 0) {
            // Si reste à payer > 0, recette vente = reste à payer
            $recette_vente = $reste_a_payer;
        } else {
            // Sinon, recette vente = prix neuf - 20% (usure)
            $stmtPrix = $pdo->prepare("SELECT ft.cout_appareil FROM FLOTTE f JOIN FLEET_TYPE ft ON f.fleet_type = ft.id WHERE f.id = :avion_id");
            $stmtPrix->execute(['avion_id' => $avion_id]);
            $prix_neuf = $stmtPrix->fetchColumn();
            $recette_vente = round($prix_neuf * 0.8, 2); // 20% d'usure
        }

        // Mettre à jour FLOTTE (actif = 0, status = 1, etat = 0)
        $stmtUpdateF = $pdo->prepare("UPDATE FLOTTE SET actif = 0, status = 1, etat = 0 WHERE id = :id");
        $stmtUpdateF->execute(['id' => $avion_id]);

        // Mettre à jour FINANCES
        $stmtUpdateFin = $pdo->prepare("UPDATE FINANCES SET date_vente = :date_vente, recette_vente = :recette_vente, reste_a_payer = 0 WHERE avion_id = :avion_id");
        $stmtUpdateFin->execute([
            'date_vente' => date('Y-m-d'),
            'recette_vente' => $recette_vente,
            'avion_id' => $avion_id
        ]);

        // Enregistrer la vente dans finances_recettes (nouveau système)
        
        // Récupérer l'immatriculation de l'appareil vendu
        $stmtImmat = $pdo->prepare("SELECT immat FROM FLOTTE WHERE id = :id");
        $stmtImmat->execute(['id' => $avion_id]);
        $immat_vendue = $stmtImmat->fetchColumn();
        // On peut utiliser le callsign de l'utilisateur connecté si besoin, sinon laisser vide
        $callsign_vendeur = isset($_SESSION['callsign']) ? $_SESSION['callsign'] : '';
        mettreAJourRecettes($recette_vente, null, $immat_vendue, $callsign_vendeur, 'vente', 'Vente appareil');

        // Récupérer l'immatriculation pour le message
        $stmtImmat = $pdo->prepare("SELECT immat FROM FLOTTE WHERE id = :id");
        $stmtImmat->execute(['id' => $avion_id]);
        $immat_vendue = $stmtImmat->fetchColumn();
        $successMessage = "L'appareil $immat_vendue a été vendu avec succès. Le banquier va être content !";
    } catch (PDOException $e) {
        $errorMessage = "Erreur lors de la vente : " . htmlspecialchars($e->getMessage());
    }
}
?>

<main>
    <h2>Vendre un appareil</h2>
    <?php if ($successMessage): ?>
        <p style="color: green; font-weight:bold;"><?= $successMessage ?></p>
        <script>
        setTimeout(function() {
            window.location.reload();
        }, 1200);
        </script>
    <?php elseif ($errorMessage): ?>
        <p style="color: red; font-weight:bold;"><?= $errorMessage ?></p>
    <?php endif; ?>

    <?php if (empty($flotte)): ?>
        <p>Aucun appareil actif à vendre.</p>
    <?php else: ?>
        <form id="venteForm" method="post" action="" onsubmit="return confirm('Confirmez-vous la vente de cet appareil ?');">
            <label for="avionSelect" style="font-weight:bold;display:block;margin-bottom:7px;">
                <span style="color:#0066cc;font-size:1.15em;vertical-align:middle;">✈️</span> Choisir un appareil à vendre :
            </label>
            <div class="select-wrapper">
                <select id="avionSelect" name="avion_id">
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($flotte as $avion): ?>
                        <option value="<?= $avion['id'] ?>"
                            data-type="<?= htmlspecialchars($avion['type']) ?>"
                            data-localisation="<?= htmlspecialchars($avion['localisation']) ?>"
                            data-hub="<?= htmlspecialchars($avion['hub']) ?>"
                            data-reste="<?= is_null($avion['reste_a_payer']) ? '' : htmlspecialchars($avion['reste_a_payer']) ?>"
                            data-dateachat="<?= is_null($avion['date_achat']) ? '' : htmlspecialchars($avion['date_achat']) ?>"
                            data-datevente="<?= is_null($avion['date_vente']) ? '' : htmlspecialchars($avion['date_vente']) ?>"
                            data-recettevente="<?= is_null($avion['recette_vente']) ? '' : htmlspecialchars($avion['recette_vente']) ?>"
                            data-recettes="<?= is_null($avion['recettes']) ? '' : htmlspecialchars($avion['recettes']) ?>"
                            data-prixvente="<?= $avion['prix_vente_prevu'] !== '' ? htmlspecialchars($avion['prix_vente_prevu']) : '' ?>"
                            data-modeachat="<?= htmlspecialchars($avion['mode_achat']) ?>"
                        >
                            <?= htmlspecialchars($avion['immat']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="select-arrow">▼</span>
            </div>
            <div id="detailsAvion" style="display:none; margin-bottom:15px;">
                <p><strong>Type :</strong> <span id="detailType"></span></p>
                <p><strong>Localisation :</strong> <span id="detailLocalisation"></span></p>
                <p><strong>Hub :</strong> <span id="detailHub"></span></p>
                <p><strong>Reste à payer :</strong> <span id="detailReste"></span></p>
                <p><strong>Date d'achat :</strong> <span id="detailDateAchat"></span></p>
                <p><strong>Recette de vente <span title="Si acheté comptant : prix d'achat - 30% (usure plus forte). Si acheté à crédit : reste à payer - 10% (pénalités de remboursement anticipé).">🛈</span> :</strong> <span id="detailPrixVentePrevu"></span></p>
                <p id="achatModeText" style="font-style:italic;color:#555;"></p>
                <p><strong>Revenus :</strong> <span id="detailRecettes"></span></p>
            </div>
            <button type="submit" id="btnVendre" class="btn" style="background-color:#0066cc;color:white; display:none;">Vendre</button>
        </form>
        <script>
        const select = document.getElementById('avionSelect');
        const detailsDiv = document.getElementById('detailsAvion');
        const btnVendre = document.getElementById('btnVendre');
        select.addEventListener('change', function() {
            const selected = select.options[select.selectedIndex];
            if (select.value) {
                document.getElementById('detailType').textContent = selected.getAttribute('data-type');
                document.getElementById('detailLocalisation').textContent = selected.getAttribute('data-localisation');
                document.getElementById('detailHub').textContent = selected.getAttribute('data-hub');
                document.getElementById('detailReste').textContent = selected.getAttribute('data-reste') ? selected.getAttribute('data-reste') + ' €' : '-';
                document.getElementById('detailDateAchat').textContent = selected.getAttribute('data-dateachat') || '-';
                document.getElementById('detailPrixVentePrevu').textContent = selected.getAttribute('data-prixvente') ? selected.getAttribute('data-prixvente') + ' €' : '-';
                // Affichage du mode d'achat
                const modeAchat = selected.getAttribute('data-modeachat');
                const achatModeText = document.getElementById('achatModeText');
                if (modeAchat === 'crédit') {
                    achatModeText.textContent = "Cet avion a été acheté à crédit.";
                } else if (modeAchat === 'comptant') {
                    achatModeText.textContent = "Cet avion a été acheté comptant.";
                } else {
                    achatModeText.textContent = "";
                }
                document.getElementById('detailRecettes').textContent = selected.getAttribute('data-recettes') ? selected.getAttribute('data-recettes') + ' €' : '-';
                detailsDiv.style.display = 'block';
                btnVendre.style.display = 'inline-block';
            } else {
                detailsDiv.style.display = 'none';
                btnVendre.style.display = 'none';
            }
        });
        </script>
    <?php endif; ?>
</main>

<style>
.table-skywings {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}
.table-skywings th, .table-skywings td {
    padding: 8px 12px;
    border: 1px solid #ccc;
    text-align: center;
}
.table-skywings th {
    background-color: #e6f0fa;
    color: #0066cc;
    font-weight: bold;
}
.btn {
    padding: 6px 14px;
    border-radius: 4px;
    border: none;
    font-weight: bold;
    cursor: pointer;
    font-size: 1em;
    background-color: #0066cc;
    color: white;
    transition: background-color 0.3s;
}
.btn:hover {
    background-color: #005bb5;
}
/* Style moderne pour la liste de sélection */
.select-wrapper {
    position: relative;
    display: inline-block;
    width: 270px;
    margin-bottom: 15px;
}
#avionSelect {
    width: 100%;
    padding: 9px 38px 9px 12px;
    border-radius: 12px;
    border: 1px solid #b3c6e0;
    background: #f7fbff;
    font-size: 1.08em;
    color: #0066cc;
    font-weight: bold;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: border-color 0.2s;
}
#avionSelect:focus {
    border-color: #0066cc;
    outline: none;
}
.select-arrow {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    font-size: 1.1em;
    color: #0066cc;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
