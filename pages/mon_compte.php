<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/log_func.php';

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

// Annulation d'une réservation (POST)
if (isset($_POST['cancel_reservation_id'])) {
    $res_id = intval($_POST['cancel_reservation_id']);
    try {
        $pdo->beginTransaction();
        // verrouille la réservation
        $chk = $pdo->prepare('SELECT * FROM RESERVATIONS WHERE id = ? AND pilote_id = ? FOR UPDATE');
        $chk->execute([$res_id, $id]);
        $r = $chk->fetch(PDO::FETCH_ASSOC);
        if (!$r) {
            $pdo->rollBack();
            $message = 'Réservation introuvable.';
        } elseif ($r['statut'] !== 'reserved') {
            $pdo->rollBack();
            $message = 'La réservation ne peut pas être annulée (statut).';
        } else {
            // annule la réservation
            $upd = $pdo->prepare("UPDATE RESERVATIONS SET statut = 'cancelled' WHERE id = ?");
            $upd->execute([$res_id]);
            // libère l'appareil si immat renseignée
            if (!empty($r['immat'])) {
                $free = $pdo->prepare('UPDATE FLOTTE SET reservee = 0 WHERE immat = ?');
                $free->execute([$r['immat']]);
            }
            $pdo->commit();
            $message = 'Réservation annulée.';
            logMsg("Pilote {$id} a annulé la réservation id={$res_id} immat=" . ($r['immat'] ?? ''));
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = 'Erreur lors de l\'annulation : ' . $e->getMessage();
        logMsg('Erreur annulation reservation: ' . $e->getMessage());
    }
}

// Récupérer les réservations actives du pilote
$stmt = $pdo->prepare("SELECT r.*, lr.icao_dep, lr.icao_arr FROM RESERVATIONS r LEFT JOIN LIGNES_REGULIERES lr ON r.ligne_id = lr.id WHERE r.pilote_id = ? AND r.statut = 'reserved' ORDER BY r.date_reservation DESC");
$stmt->execute([$id]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    <div class="account-grid">
        <div class="compte-section full-width">
            <h3>Ligne régulière réservée</h3>
            <?php if (count($reservations) === 0): ?>
                <p>Aucune réservation active.</p>
            <?php else: ?>
                <table class="table-skywings" style="margin-top:8px;">
                    <thead>
                        <tr>
                            <th>Ligne</th>
                            <th>Immat.</th>
                            <th>Date réservation</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $res): ?>
                            <tr>
                                <td><?= htmlspecialchars(($res['icao_dep'] ?? '---') . ' → ' . ($res['icao_arr'] ?? '---')) ?></td>
                                <td><?= htmlspecialchars($res['immat'] ?? '') ?></td>
                                <td><?= htmlspecialchars($res['date_reservation']) ?></td>
                                <td>
                                    <form method="post" style="display:inline;" class="form-cancel-reservation">
                                        <input type="hidden" name="cancel_reservation_id" value="<?= intval($res['id']) ?>">
                                        <button type="submit" class="btn-bleu">Annuler</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="left-column">
            <div class="compte-section">
                <h3>Informations personnelles</h3>
                <div class="compte-infos">
                    <p><strong>Callsign :</strong> <?= htmlspecialchars($pilote['callsign']) ?></p>
                    <p><strong>Nom :</strong> <?= htmlspecialchars($pilote['nom'] ?? '') ?></p>
                    <p><strong>Prénom :</strong> <?= htmlspecialchars($pilote['prenom'] ?? '') ?></p>
                    <p><strong>Email :</strong> <?= htmlspecialchars($pilote['email'] ?? '') ?></p>
                    <p><strong>Grade :</strong> <?= htmlspecialchars($grade_nom) ?></p>
                    <p><strong>Revenu cumulé :</strong> <?= isset($pilote['revenus']) ? number_format($pilote['revenus'], 2, ',', ' ') : '0,00' ?> €</p>
                    <?php if ($dernier_salaire): ?>
                        <p><strong>Dernier salaire :</strong> <?= number_format($dernier_salaire['montant'], 2, ',', ' ') ?> € (<?= htmlspecialchars($dernier_salaire['date_de_paiement']) ?>)</p>
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
        </div>

        <div class="right-column">
            <div class="compte-section">
                <h3>Détail du dernier salaire versé</h3>
                <?php if ($dernier_salaire):
                    $date_paiement = $dernier_salaire['date_de_paiement'];
                    $stmt = $pdo->prepare('SELECT temps_vol, payload FROM CARNET_DE_VOL_GENERAL WHERE pilote_id = ? AND date_vol <= ?');
                    $stmt->execute([$id, $date_paiement]);
                    $vols_salaire = $stmt->fetchAll();
                    $heures_salaire = 0;
                    $payload_salaire = 0;
                    foreach ($vols_salaire as $vol) {
                        $heures_salaire += strtotime($vol['temps_vol']) ? (strtotime($vol['temps_vol']) - strtotime('TODAY')) : 0;
                        $payload_salaire += (float)$vol['payload'];
                    }
                    $heures_salaire = $heures_salaire / 3600;
                ?>
                <div class="compte-infos">
                    <p><strong>Date de paiement :</strong> <?= htmlspecialchars($dernier_salaire['date_de_paiement']) ?></p>
                    <p><strong>Montant :</strong> <?= number_format($dernier_salaire['montant'], 2, ',', ' ') ?> €</p>
                    <p><strong>Nombre d'heures volées :</strong> <?= number_format($heures_salaire, 2, ',', ' ') ?> h</p>
                    <p><strong>Payload transporté :</strong> <?= number_format($payload_salaire, 2, ',', ' ') ?> kg</p>
                </div>
                <?php else: ?>
                <div class="compte-infos">
                    <p>Aucun salaire versé pour l'instant.</p>
                </div>
                <?php endif; ?>
            </div>

            <div class="compte-section">
                <h3>Statistiques de vol</h3>
                <div class="compte-infos">
                    <p><strong>Nombre de vols :</strong> <?= $nb_vols ?></p>
                    <p><strong>Nombre d'heures de vol :</strong> <?= $heures ? number_format($heures, 2, ',', ' ') : '0,00' ?> h</p>
                    <p><strong>Recettes rapportées :</strong> <?= $recettes ? number_format($recettes, 2, ',', ' ') : '0,00' ?> €</p>
                </div>
            </div>

            <div class="compte-section">
                <h3>Top 3 aéroports les plus fréquentés</h3>
                <ol style="margin-left: 2em;">
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
        </div>
    </div>

    
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>
<script>
// confirmation avant annulation d'une réservation
document.addEventListener('DOMContentLoaded', function(){
    var forms = document.querySelectorAll('.form-cancel-reservation');
    forms.forEach(function(f){
        f.addEventListener('submit', function(e){
            if (!confirm('Confirmer l\'annulation de la réservation ?')) {
                e.preventDefault();
            }
        });
    });
});
</script>
