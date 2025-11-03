<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
// sécurité : accès réservé aux administrateurs
if (!isset($_SESSION['user']['callsign'])) {
    header('Location: ../login.php');
    exit;
}
$stmtAdmin = $pdo->prepare("SELECT admin FROM PILOTES WHERE callsign = :callsign");
$stmtAdmin->execute(['callsign' => $_SESSION['user']['callsign']]);
$isAdmin = $stmtAdmin->fetchColumn();
if (!$isAdmin) {
    echo '<div style="margin:40px auto;max-width:600px;padding:32px;background:#fff;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);color:#b00;text-align:center;">Accès réservé aux administrateurs.</div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}
// Traitement du formulaire
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirect = 'admin_fleet_type.php';

    if ($action === 'add' || $action === 'update') {
        $fleet_type = trim($_POST['fleet_type'] ?? '');
        $type = trim($_POST['type'] ?? '');
        $cout_horaire = floatval($_POST['cout_horaire'] ?? 0);
        $cout_appareil = floatval($_POST['cout_appareil'] ?? 0);

        if ($fleet_type === '' || $type === '') {
            $errorMessage = "Les champs 'fleet_type' et 'type' sont obligatoires.";
        } else {
            try {
                if ($action === 'add') {
                    // Vérifier si le fleet_type existe déjà
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM FLEET_TYPE WHERE fleet_type = :fleet_type");
                    $stmt->execute(['fleet_type' => $fleet_type]);
                    $exists = $stmt->fetchColumn();
                    if ($exists) {
                        $errorMessage = "Ce type de flotte existe déjà.";
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO FLEET_TYPE (fleet_type, type, cout_horaire, cout_appareil) VALUES (:fleet_type, :type, :cout_horaire, :cout_appareil)");
                        $stmt->execute([
                            'fleet_type' => $fleet_type,
                            'type' => $type,
                            'cout_horaire' => $cout_horaire,
                            'cout_appareil' => $cout_appareil
                        ]);
                        $newId = $pdo->lastInsertId();
                        $_SESSION['flash_message'] = "✅ Nouveau fleet type ajouté : $fleet_type";
                        if ($newId) {
                            $redirect = 'admin_fleet_type.php?edit=' . (int)$newId;
                        }
                    }
                } else {
                    // update
                    $id = (int)($_POST['id'] ?? 0);
                    if ($id <= 0) {
                        $errorMessage = 'Identifiant invalide pour la mise à jour.';
                    } else {
                        // éviter doublon (exclure current id)
                        $chk = $pdo->prepare("SELECT COUNT(*) FROM FLEET_TYPE WHERE fleet_type = :fleet_type AND id != :id");
                        $chk->execute(['fleet_type' => $fleet_type, 'id' => $id]);
                        if ($chk->fetchColumn() > 0) {
                            $errorMessage = 'Un autre fleet_type avec ce nom existe déjà.';
                        } else {
                            $stmt = $pdo->prepare("UPDATE FLEET_TYPE SET fleet_type = :fleet_type, type = :type, cout_horaire = :cout_horaire, cout_appareil = :cout_appareil WHERE id = :id");
                            $stmt->execute(['fleet_type' => $fleet_type, 'type' => $type, 'cout_horaire' => $cout_horaire, 'cout_appareil' => $cout_appareil, 'id' => $id]);
                            $_SESSION['flash_message'] = "✅ Fleet type mis à jour.";
                            // Après une modification, revenir sur la page principale pour vider le formulaire
                            $redirect = 'admin_fleet_type.php';
                        }
                    }
                }
            } catch (PDOException $e) {
                $errorMessage = "Erreur SQL : " . htmlspecialchars($e->getMessage());
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $errorMessage = 'Identifiant invalide pour la suppression.';
        } else {
            try {
                // vérifier qu'aucun appareil n'est attaché à ce fleet_type
                $chk = $pdo->prepare("SELECT COUNT(*) FROM FLOTTE WHERE fleet_type = :id");
                $chk->execute(['id' => $id]);
                $cnt = (int)$chk->fetchColumn();
                if ($cnt > 0) {
                    $_SESSION['flash_message'] = "❌ Suppression impossible : $cnt appareil(s) sont rattachés à ce fleet type. Veuillez d'abord réaffecter ou supprimer ces appareils.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM FLEET_TYPE WHERE id = :id");
                    $stmt->execute(['id' => $id]);
                    $_SESSION['flash_message'] = "✅ Fleet type supprimé.";
                }
            } catch (PDOException $e) {
                $errorMessage = 'Erreur lors de la suppression : ' . htmlspecialchars($e->getMessage());
            }
        }
    }

    // redirect back to avoid re-submission
    if (!empty($errorMessage)) {
        $_SESSION['flash_message'] = $errorMessage;
    }
    header('Location: ' . $redirect);
    exit;
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
// Récupérer toute la table FLEET_TYPE pour affichage en deux colonnes
$fleetTypes = [];
try {
    $stmt = $pdo->query("SELECT id, fleet_type, type, cout_horaire, cout_appareil FROM FLEET_TYPE ORDER BY fleet_type ASC");
    $fleetTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ignore erreur
}

// Edit mode: charger l'enregistrement si demandé
$edit_mode = false;
$current = ['id' => 0, 'fleet_type' => '', 'type' => '', 'cout_horaire' => '', 'cout_appareil' => ''];
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    if ($eid > 0) {
        $s = $pdo->prepare("SELECT id, fleet_type, type, cout_horaire, cout_appareil FROM FLEET_TYPE WHERE id = :id");
        $s->execute(['id' => $eid]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $edit_mode = true;
            $current = $row;
        }
    }
}

?>

<main style="display:flex; flex-direction:row; align-items:flex-start; gap:40px;">
    <div style="flex:1; min-width:280px; max-width:370px;">
        <h2><?= $edit_mode ? 'Modifier un fleet type' : 'Ajouter un fleet type' ?></h2>

        <?php if (!empty($_SESSION['flash_message'])): ?>
            <div style="background:#e6f9e6;color:#0b6623;padding:10px 12px;border-radius:8px;font-weight:600;font-size:1.05em;margin-bottom:10px;">
                <?= htmlspecialchars($_SESSION['flash_message']) ?>
            </div>
            <?php unset($_SESSION['flash_message']); endif; ?>
        <?php if ($errorMessage): ?>
            <p style="color: red; font-weight:bold;"><?= $errorMessage ?></p>
        <?php endif; ?>

    <form method="post" action="" class="form-inscription">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="id" value="<?= (int)$current['id'] ?>">
            <?php endif; ?>

            <label>Nom du fleet type *</label>
            <input type="text" id="fleet_type" name="fleet_type" class="form-input input-250" required value="<?= htmlspecialchars($current['fleet_type']) ?>">

            <label>Catégorie *</label>
            <select id="type" name="type" required class="fleet-filter-select input-250">
                <option value="">-- Sélectionner --</option>
                <?php $cats = ['Monomoteur','Bimoteur','Liner','Helico']; foreach($cats as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= ($current['type']==$c)?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Coût horaire (€) *</label>
            <input type="number" id="cout_horaire" name="cout_horaire" step="0.01" class="form-input input-250" required value="<?= htmlspecialchars($current['cout_horaire']) ?>">

            <label>Coût de l'appareil (€) *</label>
            <input type="number" id="cout_appareil" name="cout_appareil" step="0.01" class="form-input input-250" required value="<?= htmlspecialchars($current['cout_appareil']) ?>">

            <div class="form-actions">
                <?php if ($edit_mode): ?>
                    <button type="submit" name="action" value="update" class="btn-bleu btn-small">Mettre à jour</button>
                    <a href="admin_fleet_type.php" class="btn btn-small" style="margin-left:8px;">Annuler</a>
                    <button type="button" class="btn btn-reset btn-small" onclick="window.location.href='admin_fleet_type.php';">Réinitialiser</button>
                <?php else: ?>
                    <button type="submit" name="action" value="add" class="btn-bleu btn-small">Ajouter</button>
                    <button type="button" class="btn btn-reset btn-small" onclick="window.location.href='admin_fleet_type.php';">Réinitialiser</button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <aside style="min-width:900px;max-width:1800px;margin-left:40px;margin-right:auto;background:#f7fbff;border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,0.04);padding:18px 16px 12px 16px;align-self:center;">
        <h3 style="margin-top:0;margin-bottom:12px;font-size:1.1em;color:#0066cc;">Fleet types existants</h3>
        <?php
        $total = count($fleetTypes);
        $mid = (int)ceil($total / 2);
        $col1 = array_slice($fleetTypes, 0, $mid);
        $col2 = array_slice($fleetTypes, $mid);
        ?>
        <div style="display: flex; gap: 32px; align-items: flex-start;">
            <div class="table-section" style="min-width:420px;">
                <table class="table-skywings" style="width:100%; white-space:nowrap; word-break:keep-all;">
                    <thead>
                        <tr>
                            <th class="fleet_type">Nom</th>
                            <th class="type">Catégorie</th>
                            <th class="cout_horaire">Coût horaire (€)</th>
                            <th class="prix">Prix (€)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($col1 as $ft): ?>
                        <tr>
                            <td class="fleet_type"><?= htmlspecialchars($ft['fleet_type']) ?></td>
                            <td class="type" style="color:#444; font-style:italic;"><?= htmlspecialchars($ft['type']) ?></td>
                            <td class="cout_horaire" style="text-align:right;"><?= number_format((float)$ft['cout_horaire'], 2, ',', ' ') ?></td>
                            <td class="prix" style="text-align:right;font-weight:bold;"><?= number_format((float)$ft['cout_appareil'], 0, '', ' ') ?></td>
                            <td>
                                <a href="admin_fleet_type.php?edit=<?= (int)$ft['id'] ?>">Éditer</a>
                                &nbsp;|&nbsp;
                                <a href="#" onclick="if(confirm('Confirmer la suppression ?')){ document.getElementById('delete-form-<?= (int)$ft['id'] ?>').submit(); } return false;">Supprimer</a>
                                <form id="delete-form-<?= (int)$ft['id'] ?>" method="post" style="display:none;">
                                    <input type="hidden" name="id" value="<?= (int)$ft['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-section" style="min-width:420px;">
                <table class="table-skywings" style="width:100%; white-space:nowrap; word-break:keep-all;">
                    <thead>
                        <tr>
                            <th class="fleet_type">Nom</th>
                            <th class="type">Catégorie</th>
                            <th class="cout_horaire">Coût horaire (€)</th>
                            <th class="prix">Prix (€)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($col2 as $ft): ?>
                        <tr>
                            <td class="fleet_type"><?= htmlspecialchars($ft['fleet_type']) ?></td>
                            <td class="type" style="color:#444; font-style:italic;"><?= htmlspecialchars($ft['type']) ?></td>
                            <td class="cout_horaire" style="text-align:right;"><?= number_format((float)$ft['cout_horaire'], 2, ',', ' ') ?></td>
                            <td class="prix" style="text-align:right;font-weight:bold;"><?= number_format((float)$ft['cout_appareil'], 0, '', ' ') ?></td>
                            <td>
                                <a href="admin_fleet_type.php?edit=<?= (int)$ft['id'] ?>">Éditer</a>
                                &nbsp;|&nbsp;
                                <a href="#" onclick="if(confirm('Confirmer la suppression ?')){ document.getElementById('delete-form-<?= (int)$ft['id'] ?>').submit(); } return false;">Supprimer</a>
                                <form id="delete-form-<?= (int)$ft['id'] ?>" method="post" style="display:none;">
                                    <input type="hidden" name="id" value="<?= (int)$ft['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </aside>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
