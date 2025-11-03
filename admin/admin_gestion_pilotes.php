<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';

// Vérification admin
if (!isset($_SESSION['user']['callsign'])) {
    header('Location: /login.php');
    exit;
}
$stmt = $pdo->prepare('SELECT admin FROM PILOTES WHERE callsign = ?');
$stmt->execute([$_SESSION['user']['callsign']]);
$isAdmin = $stmt->fetchColumn();
if ($isAdmin != 1) {
    echo "<p style='color:red;font-weight:bold;'>Accès réservé aux administrateurs.</p>";
    exit;
}

// Récupère tous les callsigns
$stmt = $pdo->query('SELECT id, callsign FROM PILOTES ORDER BY callsign');
$pilotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupère les infos du pilote sélectionné
// Aucun pilote sélectionné par défaut
$selected_id = isset($_POST['pilote_id']) ? intval($_POST['pilote_id']) : null;
$info = null;
if ($selected_id) {
    $stmt = $pdo->prepare('SELECT * FROM PILOTES WHERE id = ?');
    $stmt->execute([$selected_id]);
    $info = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Mise à jour des infos
$message = '';
if (isset($_POST['update']) && $info) {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $admin = isset($_POST['admin']) ? 1 : 0;
    $actif = isset($_POST['actif']) ? 1 : 0;
    $stmt = $pdo->prepare('UPDATE PILOTES SET prenom = ?, nom = ?, email = ?, admin = ?, actif = ? WHERE id = ?');
    if ($stmt->execute([$prenom, $nom, $email, $admin, $actif, $selected_id])) {
        $message = "Modifications enregistrées.";
        // Réinitialise la sélection du pilote
        $selected_id = null;
        $info = null;
    } else {
        $message = "Erreur lors de la modification.";
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>
<main>
    <h2>Gestion des pilotes</h2>
    <?php if ($message): ?>
        <div style="font-weight:bold;color:<?= strpos($message,'enregistr')!==false?'#1ca64c':'#d60000' ?>;margin-bottom:16px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <form method="post" id="form-pilote">
        <label for="pilote_id"><strong>Choisir un callsign :</strong></label>
        <select name="pilote_id" id="pilote_id" class="fleet-filter-select input-320" onchange="document.getElementById('form-pilote').submit();">
            <option value="">-- Sélectionner --</option>
            <?php foreach ($pilotes as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $selected_id==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['callsign']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($info): ?>
    <form method="post" class="form-pilote">
        <input type="hidden" name="pilote_id" value="<?= $info['id'] ?>">
        <div class="form-row">
            <label>Callsign :</label>
            <input type="text" value="<?= htmlspecialchars($info['callsign']) ?>" disabled>
        </div>
        <div class="form-row">
            <label>Prénom :</label>
            <input type="text" name="prenom" value="<?= htmlspecialchars($info['prenom']) ?>">
        </div>
        <div class="form-row">
            <label>Nom :</label>
            <input type="text" name="nom" value="<?= htmlspecialchars($info['nom']) ?>">
        </div>
        <div class="form-row">
            <label>Email :</label>
            <input type="email" name="email" value="<?= htmlspecialchars($info['email']) ?>">
        </div>
        <div class="form-row">
            <label></label>
            <div class="checkbox-group">
                <label class="checkbox-inline">
                    <input type="checkbox" name="admin" value="1" <?= $info['admin']==1?'checked':'' ?>>
                    Admin
                </label>
                <label class="checkbox-inline">
                    <input type="checkbox" name="actif" value="1" <?= (isset($info['actif']) && $info['actif']==1)?'checked':'' ?>>
                    Actif
                </label>
            </div>
        </div>
        <div class="form-row form-actions">
            <button type="submit" name="update" class="btn-bleu">Enregistrer</button>
            <button type="button" class="btn btn-reset" onclick="window.location.href='admin_gestion_pilotes.php';">Réinitialiser</button>
        </div>
    </form>
    <?php endif; ?>
</main>
<?php
include __DIR__ . '/../includes/footer.php';
?>
