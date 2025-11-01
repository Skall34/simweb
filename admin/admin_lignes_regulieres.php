<?php
session_start();
require __DIR__ . '/../includes/db_connect.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$message = '';
$flash = '';
// Read flash message from session (set after POST redirects)
if (!empty($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
$edit_mode = false;
$line = ['id' => '', 'icao_dep' => '', 'icao_arr' => '', 'created_at' => '', 'updated_at' => ''];

// Handle POST actions: add, update, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $icao_dep = strtoupper(trim($_POST['icao_dep'] ?? ''));
        $icao_arr = strtoupper(trim($_POST['icao_arr'] ?? ''));
        $type_ligne = isset($_POST['type_ligne']) ? (int)$_POST['type_ligne'] : null;
        if ($icao_dep === '' || $icao_arr === '') {
            $message = 'Les deux codes ICAO sont requis.';
        } else {
            try {
                // Check duplicate exact pair
                $chk = $pdo->prepare("SELECT COUNT(*) AS c FROM LIGNES_REGULIERES WHERE icao_dep = :dep AND icao_arr = :arr");
                $chk->execute(['dep' => $icao_dep, 'arr' => $icao_arr]);
                $row = $chk->fetch(PDO::FETCH_ASSOC);
                    if ($row && (int)$row['c'] > 0) {
                        $message = "⚠️ La ligne $icao_dep → $icao_arr existe déjà.";
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO LIGNES_REGULIERES (icao_dep, icao_arr, type_ligne, created_at, updated_at) VALUES (:dep, :arr, :type_ligne, NOW(), NOW())");
                        $stmt->execute(['dep' => $icao_dep, 'arr' => $icao_arr, 'type_ligne' => $type_ligne]);
                        $message = "✅ Ligne $icao_dep → $icao_arr ajoutée.";
                    }
            } catch (Exception $e) {
                $message = 'Erreur lors de l\'ajout : ' . htmlspecialchars($e->getMessage());
            }
        }
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $icao_dep = strtoupper(trim($_POST['icao_dep'] ?? ''));
        $icao_arr = strtoupper(trim($_POST['icao_arr'] ?? ''));
        $type_ligne = isset($_POST['type_ligne']) ? (int)$_POST['type_ligne'] : null;
        if ($id <= 0 || $icao_dep === '' || $icao_arr === '') {
            $message = 'Données invalides pour la mise à jour.';
        } else {
            try {
                // Ensure we don't create a duplicate (excluding current row)
                $chk = $pdo->prepare("SELECT COUNT(*) AS c FROM LIGNES_REGULIERES WHERE icao_dep = :dep AND icao_arr = :arr AND id != :id");
                $chk->execute(['dep' => $icao_dep, 'arr' => $icao_arr, 'id' => $id]);
                $row = $chk->fetch(PDO::FETCH_ASSOC);
                if ($row && (int)$row['c'] > 0) {
                    $message = "⚠️ Une autre ligne $icao_dep → $icao_arr existe déjà (mise à jour annulée).";
                } else {
                    $stmt = $pdo->prepare("UPDATE LIGNES_REGULIERES SET icao_dep = :dep, icao_arr = :arr, type_ligne = :type_ligne, updated_at = NOW() WHERE id = :id");
                    $stmt->execute(['dep' => $icao_dep, 'arr' => $icao_arr, 'type_ligne' => $type_ligne, 'id' => $id]);
                    $message = "✅ Ligne mise à jour en $icao_dep → $icao_arr.";
                }
            } catch (Exception $e) {
                $message = 'Erreur lors de la mise à jour : ' . htmlspecialchars($e->getMessage());
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $message = 'Identifiant invalide pour la suppression.';
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM LIGNES_REGULIERES WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $message = "Ligne #$id supprimée.";
            } catch (Exception $e) {
                $message = 'Erreur lors de la suppression : ' . htmlspecialchars($e->getMessage());
            }
        }
    }

    // After any POST, store the message in session and redirect to show it (flash)
    if (!empty($message)) {
        $_SESSION['flash_message'] = $message;
    }
    header('Location: admin_lignes_regulieres.php');
    exit;
}

// If edit requested, load line
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM LIGNES_REGULIERES WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $line = $row;
            $edit_mode = true;
        }
    }
}

// Fetch all lines (inclure le label du type via LEFT JOIN)
$stmt = $pdo->query("
    SELECT lr.id, lr.icao_dep, lr.icao_arr, lr.type_ligne, tl.label AS type_label, lr.created_at, lr.updated_at
    FROM LIGNES_REGULIERES lr
    LEFT JOIN TYPE_LIGNE tl ON lr.type_ligne = tl.id
    ORDER BY lr.icao_dep, lr.icao_arr
");
$lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Récupérer la liste des types de ligne pour la combobox ---
try {
    $stmtTypes = $pdo->query("SELECT id, label FROM TYPE_LIGNE ORDER BY label ASC");
    $typeLignes = $stmtTypes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $typeLignes = [];
}

// --- NOUVEAU : lire les filtres GET ---
$filter_dep = isset($_GET['icao_dep']) ? strtoupper(trim($_GET['icao_dep'])) : '';
$filter_arr = isset($_GET['icao_arr']) ? strtoupper(trim($_GET['icao_arr'])) : '';
$filter_type = (isset($_GET['type_ligne']) && $_GET['type_ligne'] !== '') ? (int)$_GET['type_ligne'] : null;

// --- NOUVEAU : construire requête avec filtres ---
$sql = "
    SELECT lr.id, lr.icao_dep, lr.icao_arr, lr.type_ligne, tl.label AS type_label, lr.created_at, lr.updated_at
    FROM LIGNES_REGULIERES lr
    LEFT JOIN TYPE_LIGNE tl ON lr.type_ligne = tl.id
";
$conds = [];
$params = [];
if ($filter_dep !== '') {
    $conds[] = "lr.icao_dep LIKE :dep";
    $params['dep'] = $filter_dep . '%';
}
if ($filter_arr !== '') {
    $conds[] = "lr.icao_arr LIKE :arr";
    $params['arr'] = $filter_arr . '%';
}
if ($filter_type !== null) {
    $conds[] = "lr.type_ligne = :type_ligne";
    $params['type_ligne'] = $filter_type;
}
if (!empty($conds)) {
    $sql .= " WHERE " . implode(' AND ', $conds);
}
$sql .= " ORDER BY lr.icao_dep ASC, lr.icao_arr ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>

<main>
    <h2>Administration des Lignes régulières (<?= count($lines) ?>)</h2>

    <?php if ($message): ?>
        <div class="success"><?= $message ?></div>
    <?php endif; ?>

    <section style="margin-bottom: 20px;">
        <div class="narrow-table-wrapper" style="background:#f7fbff;padding:16px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
        <h3><?= $edit_mode ? 'Modifier la ligne' : 'Ajouter une nouvelle ligne' ?></h3>
    <form method="post" class="form-inscription" style="display:flex;gap:12px;align-items:center;flex-wrap:nowrap;flex-direction:row;white-space:nowrap;">
             <?php if ($edit_mode): ?>
                 <input type="hidden" name="id" value="<?= htmlspecialchars($line['id']) ?>">
             <?php endif; ?>

            <label style="display:inline-flex;flex-direction:row;align-items:center;gap:8px;margin:0;">
                <span style="min-width:86px;display:inline-block;">ICAO départ:</span>
                <input name="icao_dep" required value="<?= htmlspecialchars($line['icao_dep']) ?>" class="fleet-filter-input input-160" style="text-transform:uppercase;" oninput="this.value = this.value.toUpperCase();">
            </label>

            <label style="display:inline-flex;flex-direction:row;align-items:center;gap:8px;margin:0;">
                <span style="min-width:86px;display:inline-block;">ICAO arrivée:</span>
                <input name="icao_arr" required value="<?= htmlspecialchars($line['icao_arr']) ?>" class="fleet-filter-input input-160" style="text-transform:uppercase;" oninput="this.value = this.value.toUpperCase();">
            </label>

            <label style="display:inline-flex;flex-direction:row;align-items:center;gap:8px;margin:0;">
                <span style="min-width:86px;display:inline-block;">Type de ligne:</span>
                <select name="type_ligne" class="fleet-filter-select input-160">
                    <option value="">-- Aucun --</option>
                    <?php foreach ($typeLignes as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= (isset($line['type_ligne']) && (int)$line['type_ligne'] === (int)$t['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div style="margin-left:12px;display:inline-flex;align-items:center;">
                 <div>
                     <?php if ($edit_mode): ?>
                         <input type="hidden" name="id" value="<?= htmlspecialchars($line['id']) ?>">
                         <button class="btn-bleu" type="submit" name="action" value="update">Mettre à jour</button>
                         <a href="admin_lignes_regulieres.php" class="btn" style="background:#ccc;color:#004080;padding:6px 10px;margin-left:8px;text-decoration:none;">Annuler</a>
                     <?php else: ?>
                         <button class="btn-bleu" type="submit" name="action" value="add">Ajouter</button>
                     <?php endif; ?>
                 </div>
             </div>
             </form>
         </div>
     </section>

    <section>
        <h3>Liste des lignes</h3>

        <!-- Filters placed under the table title, single-line (inputs inline with buttons) -->
        <form method="get" class="filters-form" style="margin:8px 0 12px 0;">
            <label>Départ
                <input name="icao_dep" placeholder="Départ" value="<?= htmlspecialchars($filter_dep) ?>" aria-label="Filtrer départ" class="fleet-filter-input input-160" oninput="this.value = this.value.toUpperCase();">
            </label>

            <label>Arrivée
                <input name="icao_arr" placeholder="Arrivée" value="<?= htmlspecialchars($filter_arr) ?>" aria-label="Filtrer arrivée" class="fleet-filter-input input-160" oninput="this.value = this.value.toUpperCase();">
            </label>

            <label>Type de ligne:
                <select name="type_ligne" class="fleet-filter-select input-160">
                    <option value="">-- Tous --</option>
                    <?php foreach ($typeLignes as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= ($filter_type !== null && $filter_type === (int)$t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div style="margin-left:12px;display:inline-flex;align-items:center;">
                <button class="btn-bleu" type="submit">Filtrer</button>
                <!-- Keep the current visual style of the reset button exactly as-is (model for the site) -->
                <a href="admin_lignes_regulieres.php" class="btn" style="background:#ccc;color:#004080;padding:8px 16px;margin-left:8px;text-decoration:none;line-height:18px;border-radius:4px;">Réinitialiser</a>
            </div>
        </form>

        <div class="narrow-table-wrapper">
            <table class="table-skywings" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th>ICAO Dép.</th>
                    <th>ICAO Arr.</th>
                    <th>Type</th>
                    <th>Créé</th>
                    <th>Mise à jour</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lines as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['icao_dep']) ?></td>
                        <td><?= htmlspecialchars($r['icao_arr']) ?></td>
                        <td><?= htmlspecialchars($r['type_label'] ?? $r['type_ligne']) ?></td>
                    <td><?= $r['created_at'] ? htmlspecialchars(date('d/m/Y H:i:s', strtotime($r['created_at']))) : '-' ?></td>
                    <td><?= $r['updated_at'] ? htmlspecialchars(date('d/m/Y H:i:s', strtotime($r['updated_at']))) : '-' ?></td>
                        <td>
                            <a href="admin_lignes_regulieres.php?edit=<?= $r['id'] ?>">Éditer</a>
                            &nbsp;|&nbsp;
                            <a href="#" onclick="if(confirm('Confirmer la suppression ?')){ document.getElementById('delete-form-<?= $r['id'] ?>').submit(); } return false;">Supprimer</a>
                            <form id="delete-form-<?= $r['id'] ?>" method="post" style="display:none;">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
