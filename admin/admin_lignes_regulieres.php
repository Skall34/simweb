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
                        $stmt = $pdo->prepare("INSERT INTO LIGNES_REGULIERES (icao_dep, icao_arr, created_at, updated_at) VALUES (:dep, :arr, NOW(), NOW())");
                        $stmt->execute(['dep' => $icao_dep, 'arr' => $icao_arr]);
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
                    $stmt = $pdo->prepare("UPDATE LIGNES_REGULIERES SET icao_dep = :dep, icao_arr = :arr, updated_at = NOW() WHERE id = :id");
                    $stmt->execute(['dep' => $icao_dep, 'arr' => $icao_arr, 'id' => $id]);
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

// Fetch all lines
$stmt = $pdo->query("SELECT id, icao_dep, icao_arr, created_at, updated_at FROM LIGNES_REGULIERES ORDER BY icao_dep, icao_arr");
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
        <h3><?= $edit_mode ? 'Modifier la ligne' : 'Ajouter une nouvelle ligne' ?></h3>
        <form method="post" class="form-inscription" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="id" value="<?= htmlspecialchars($line['id']) ?>">
            <?php endif; ?>

            <label>ICAO départ:
                <input name="icao_dep" required value="<?= htmlspecialchars($line['icao_dep']) ?>" style="width:120px;text-transform:uppercase;">
            </label>

            <label>ICAO arrivée:
                <input name="icao_arr" required value="<?= htmlspecialchars($line['icao_arr']) ?>" style="width:120px;text-transform:uppercase;">
            </label>

            <div>
                <?php if ($edit_mode): ?>
                    <button class="btn-bleu" type="submit" name="action" value="update">Mettre à jour</button>
                    <a href="admin_lignes_regulieres.php" class="btn" style="background:#ccc;color:#004080;padding:6px 10px;margin-left:8px;text-decoration:none;">Annuler</a>
                <?php else: ?>
                    <button class="btn-bleu" type="submit" name="action" value="add">Ajouter</button>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section>
        <h3>Liste des lignes</h3>
        <table class="table-skywings" style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th>ICAO Dép.</th>
                    <th>ICAO Arr.</th>
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
                        <td><?= htmlspecialchars($r['created_at']) ?></td>
                        <td><?= htmlspecialchars($r['updated_at']) ?></td>
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
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
