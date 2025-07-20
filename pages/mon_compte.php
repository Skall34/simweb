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

// Récupérer le dernier salaire versé
$stmt = $pdo->prepare('SELECT montant, date_de_paiement FROM SALAIRES WHERE id_pilote = ? ORDER BY date_de_paiement DESC LIMIT 1');
$stmt->execute([$id]);
$dernier_salaire = $stmt->fetch();
// Récupérer le libellé du grade
$grade_nom = '';
if (!empty($pilote['grade_id'])) {
    $stmt = $pdo->prepare('SELECT nom FROM GRADES WHERE id = ?');
    $stmt->execute([$pilote['grade_id']]);
    $grade_nom = $stmt->fetchColumn();
}

// Nombre de vols
$stmt = $pdo->prepare('SELECT COUNT(*) FROM CARNET_DE_VOL_GENERAL WHERE pilote_id = ?');
$stmt->execute([$id]);
$nb_vols = $stmt->fetchColumn();
// Nombre d'heures de vol
$stmt = $pdo->prepare('SELECT SUM(TIME_TO_SEC(temps_vol)) FROM CARNET_DE_VOL_GENERAL WHERE pilote_id = ?');
$stmt->execute([$id]);
$total_sec = (int)$stmt->fetchColumn();
$heures = $total_sec / 3600;
// Recettes rapportées
$stmt = $pdo->prepare('SELECT SUM(cout_vol) FROM CARNET_DE_VOL_GENERAL WHERE pilote_id = ?');
$stmt->execute([$id]);
$recettes = (float)$stmt->fetchColumn();

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
            <p><strong>Grade :</strong> <?= htmlspecialchars($grade_nom) ?></p>
            <p><strong>Revenu cumulé :</strong> <?= isset($pilote['revenus']) ? number_format($pilote['revenus'], 2) : '0.00' ?> €</p>
            <?php if ($dernier_salaire): ?>
                <p><strong>Dernier salaire :</strong> <?= number_format($dernier_salaire['montant'], 2) ?> € (<?= htmlspecialchars($dernier_salaire['date_de_paiement']) ?>)</p>
            <?php endif; ?>
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

<?php
include __DIR__ . '/../includes/footer.php';
?>
