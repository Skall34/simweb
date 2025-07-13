<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';

if (!isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}

$id = $_SESSION['user']['id'];

// Récupération des infos du pilote
$stmt = $pdo->prepare('SELECT * FROM PILOTES WHERE id = ?');
$stmt->execute([$id]);
$pilote = $stmt->fetch();

// Nombre de vols
$stmt = $pdo->prepare('SELECT COUNT(*) FROM CARNET_DE_VOL_GENERAL WHERE pilote_id = ?');
$stmt->execute([$id]);
$nb_vols = $stmt->fetchColumn();

// 3 aéroports les plus fréquentés avec ident
$stmt = $pdo->prepare('SELECT c.destination, COUNT(*) as freq, a.ident FROM CARNET_DE_VOL_GENERAL c LEFT JOIN AEROPORTS a ON c.destination = a.ident WHERE c.pilote_id = ? GROUP BY c.destination ORDER BY freq DESC LIMIT 3');
$stmt->execute([$id]);
$aeroports = $stmt->fetchAll();

// Changement de mot de passe
$message = '';
if (isset($_POST['old_password'], $_POST['new_password'], $_POST['new_password_confirm'])) {
    $old = $_POST['old_password'];
    $new = $_POST['new_password'];
    $new_confirm = $_POST['new_password_confirm'];
    if ($new !== $new_confirm) {
        $message = "Les nouveaux mots de passe ne correspondent pas.";
    } elseif (password_verify($old, $pilote['password'])) {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE PILOTES SET password = ? WHERE id = ?');
        $stmt->execute([$hash, $id]);
        $message = "Mot de passe modifié avec succès.";
    } else {
        $message = "Mot de passe actuel incorrect.";
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>
<main>
    <h2>Mon compte</h2>
    <?php
    if ($message) {
        $isSuccess = strpos($message, 'succès') !== false;
        $color = $isSuccess ? '#1ca64c' : '#d60000';
        echo "<div style='font-weight:bold;color:$color;margin-bottom:16px;'>$message</div>";
    }
    ?>

    <div class="compte-section">
        <h3>Informations personnelles</h3>
        <div class="compte-infos">
            <p><strong>Callsign :</strong> <?= htmlspecialchars($pilote['callsign']) ?></p>
            <p><strong>Nom :</strong> <?= htmlspecialchars($pilote['nom'] ?? '') ?></p>
            <p><strong>Prénom :</strong> <?= htmlspecialchars($pilote['prenom'] ?? '') ?></p>
            <p><strong>Email :</strong> <?= htmlspecialchars($pilote['email'] ?? '') ?></p>
            <!-- Ajoute d'autres champs si besoin -->
        </div>
    </div>

    <div class="compte-section">
        <h3>Changer le mot de passe</h3>
        <form method="post" class="form-mdp">
            <div class="form-row">
                <label for="old_password">Mot de passe actuel :</label>
                <input type="password" name="old_password" id="old_password" required>
            </div>
            <div class="form-row">
                <label for="new_password">Nouveau mot de passe :</label>
                <input type="password" name="new_password" id="new_password" required>
            </div>
            <div class="form-row">
                <label for="new_password_confirm">Confirmer le nouveau mot de passe :</label>
                <input type="password" name="new_password_confirm" id="new_password_confirm" required>
            </div>
            <div class="form-row">
                <button type="submit" class="btn-bleu">Modifier</button>
            </div>
        </form>
    </div>

    <div class="compte-section">
        <h3>Statistiques de vol</h3>
        <div class="compte-infos">
            <p><strong>Nombre de vols :</strong> <?= $nb_vols ?></p>
            <p><strong>Nombre d'heures de vol :</strong> <?= $heures ? number_format($heures, 2) : 0 ?> h</p>
            <p><strong>Recettes rapportées :</strong> <?= $recettes ? number_format($recettes, 2) : 0 ?> €</p>
        </div>
    </div>

    <div class="compte-section">
        <h3>Top 3 aéroports les plus fréquentés</h3>
        <ol>
            <?php foreach ($aeroports as $aero): ?>
                <li>
                    <?= htmlspecialchars($aero['destination']) ?>
                    <?php if (!empty($aero['ident'])): ?>
                        (<?= htmlspecialchars($aero['ident']) ?>)
                    <?php endif; ?>
                    - <?= $aero['freq'] ?> vols
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</main>
<style>
.compte-section {
    border: 1px solid #ccc;
    border-radius: 8px;
    padding: 18px;
    margin-bottom: 24px;
    background: #f9f9f9;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.compte-section h3 {
    margin-top: 0;
    color: #2a4d7a;
}
.compte-infos {
    margin: 0;
    padding: 0;
}
.compte-infos p {
    margin: 6px 0;
    padding: 0;
}
.form-mdp {
    max-width: 400px;
    margin: 0;
}
.form-row {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
}
.form-row label {
    flex: 0 0 180px;
    font-weight: 500;
}
.form-row input[type="password"] {
    flex: 1;
    padding: 6px 8px;
    border: 1px solid #bbb;
    border-radius: 4px;
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
