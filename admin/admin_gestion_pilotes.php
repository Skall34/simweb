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
    $stmt = $pdo->prepare('UPDATE PILOTES SET prenom = ?, nom = ?, email = ?, admin = ? WHERE id = ?');
    if ($stmt->execute([$prenom, $nom, $email, $admin, $selected_id])) {
        $message = "Modifications enregistrées.";
        // Recharge les infos
        $stmt = $pdo->prepare('SELECT * FROM PILOTES WHERE id = ?');
        $stmt->execute([$selected_id]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
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
        <select name="pilote_id" id="pilote_id" onchange="document.getElementById('form-pilote').submit();">
            <option value="">-- Sélectionner --</option>
            <?php foreach ($pilotes as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $selected_id==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['callsign']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($info): ?>
    <form method="post" class="form-pilote" style="margin-top:24px;max-width:400px;">
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
            <label>Admin :</label>
            <input type="checkbox" name="admin" value="1" <?= $info['admin']==1?'checked':'' ?>>
        </div>
        <div class="form-row">
            <button type="submit" name="update" class="btn-bleu">Enregistrer</button>
        </div>
    </form>
    <?php endif; ?>
</main>
<style>
.form-pilote .form-row {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
}
.form-pilote label {
    flex: 0 0 120px;
    font-weight: 500;
}
.form-pilote input[type="text"],
.form-pilote input[type="email"] {
    flex: 1;
    padding: 6px 8px;
    border: 1px solid #bbb;
    border-radius: 4px;
}
.form-pilote input[type="checkbox"] {
    margin-left: 8px;
}
.btn-bleu {
    background: #2a4d7a;
    color: #fff;
    border: none;
    border-radius: 4px;
    padding: 8px 22px;
    font-size: 1rem;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.2s;
}
.btn-bleu:hover {
    background: #1a3552;
}
</style>
<?php
include __DIR__ . '/../includes/footer.php';
?>
