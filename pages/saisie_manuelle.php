<?php
// filepath: /home/cyber/VA/skywings/admin/admin_saisie_vol.php
require_once __DIR__ . '/../includes/db_connect.php';

$erreurs = [];
$success = false;

// Récupérer les callsigns et missions pour les listes déroulantes
$stmt = $pdo->query("SELECT callsign FROM PILOTES ORDER BY callsign");
$callsigns = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->query("SELECT libelle FROM MISSIONS ORDER BY libelle");
$missions = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Récupérer les immatriculations actives
$stmt = $pdo->query("SELECT immat FROM FLOTTE WHERE actif = 1 ORDER BY immat");
$immats = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Initialiser les valeurs du formulaire
$form = [
    'callsign' => '',
    'immatriculation' => '',
    'departure_icao' => '',
    'departure_fuel' => '',
    'departure_datetime' => '',
    'arrival_icao' => '',
    'arrival_fuel' => '',
    'arrival_datetime' => '',
    'payload' => '',
    'commentaire' => '',
    'note_du_vol' => '',
    'mission' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Si reset, on garde les champs vides
    if (isset($_POST['reset'])) {
        // Rien à faire, $form reste vide
    } else {
        // Récupération et nettoyage des champs
        foreach ($form as $key => $val) {
            $form[$key] = isset($_POST[$key]) ? trim($_POST[$key]) : '';
        }
        $form['departure_icao'] = strtoupper($form['departure_icao']);
        $form['arrival_icao'] = strtoupper($form['arrival_icao']);

        // Contrôles
        if (!$form['callsign']) $erreurs[] = "Sélectionnez un callsign.";
        if (!$form['immatriculation']) $erreurs[] = "Sélectionnez une immatriculation.";
        if (!$form['departure_icao'] || strlen($form['departure_icao']) !== 4) $erreurs[] = "Code ICAO départ invalide.";
        if (!$form['arrival_icao'] || strlen($form['arrival_icao']) !== 4) $erreurs[] = "Code ICAO arrivée invalide.";
        if ($form['departure_fuel'] === '' || floatval($form['departure_fuel']) < 0) $erreurs[] = "Fuel départ invalide.";
        if ($form['arrival_fuel'] === '' || floatval($form['arrival_fuel']) < 0) $erreurs[] = "Fuel arrivée invalide.";
        if (floatval($form['departure_fuel']) < floatval($form['arrival_fuel'])) $erreurs[] = "Le fuel départ ne peut pas être inférieur au fuel arrivée.";
        if (!$form['departure_datetime']) $erreurs[] = "Date/heure départ obligatoire.";
        if (!$form['arrival_datetime']) $erreurs[] = "Date/heure arrivée obligatoire.";
        if ($form['note_du_vol'] === '' || intval($form['note_du_vol']) < 1 || intval($form['note_du_vol']) > 10) $erreurs[] = "Note du vol entre 1 et 10.";
        if (!$form['mission']) $erreurs[] = "Mission obligatoire.";

        // Vérification des aéroports
        if ($form['departure_icao']) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM AEROPORTS WHERE ident = :icao");
            $stmt->execute(['icao' => $form['departure_icao']]);
            if ($stmt->fetchColumn() == 0) $erreurs[] = "Aéroport de départ inconnu.";
        }
        if ($form['arrival_icao']) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM AEROPORTS WHERE ident = :icao");
            $stmt->execute(['icao' => $form['arrival_icao']]);
            if ($stmt->fetchColumn() == 0) $erreurs[] = "Aéroport d'arrivée inconnu.";
        }

        // Contrôle cohérence date/heure
        if ($form['departure_datetime'] && $form['arrival_datetime']) {
            $dt_dep = DateTime::createFromFormat('Y-m-d\TH:i', $form['departure_datetime']);
            $dt_arr = DateTime::createFromFormat('Y-m-d\TH:i', $form['arrival_datetime']);
            if (!$dt_dep || !$dt_arr) {
                $erreurs[] = "Format date/heure invalide.";
            } elseif ($dt_dep >= $dt_arr) {
                $erreurs[] = "La date/heure de départ doit être antérieure à la date/heure d'arrivée.";
            }
        }

        if (empty($erreurs)) {
            $stmt = $pdo->prepare("INSERT INTO FROM_ACARS
                (horodateur, callsign, immatriculation, departure_icao, departure_fuel, departure_time, arrival_icao, arrival_fuel, arrival_time, payload, commentaire, note_du_vol, mission, processed, created_at)
                VALUES (NOW(), :callsign, :immat, :depart, :fuelDep, :timeDep, :dest, :fuelArr, :timeArr, :payload, :commentaire, :note, :mission, 0, NOW())");
            $stmt->execute([
                'callsign' => $form['callsign'],
                'immat' => $form['immatriculation'],
                'depart' => $form['departure_icao'],
                'fuelDep' => floatval($form['departure_fuel']),
                'timeDep' => $form['departure_datetime'] ? str_replace('T', ' ', $form['departure_datetime']) . ':00' : null,
                'dest' => $form['arrival_icao'],
                'fuelArr' => floatval($form['arrival_fuel']),
                'timeArr' => $form['arrival_datetime'] ? str_replace('T', ' ', $form['arrival_datetime']) . ':00' : null,
                'payload' => floatval($form['payload']),
                'commentaire' => $form['commentaire'],
                'note' => intval($form['note_du_vol']),
                'mission' => $form['mission']
            ]);
            $success = true;
            // Réinitialiser le formulaire après succès
            foreach ($form as $key => $val) {
                $form[$key] = '';
            }
        }
    }
}
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>

<main>
        <h2>Saisie manuelle d'un vol</h1>
        <?php if ($success): ?>
            <div class="alert success">✅ Vol ajouté avec succès !</div>
        <?php elseif ($erreurs): ?>
            <div class="alert error">
                <?php foreach ($erreurs as $err): ?>
                    <div><?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post"  class="form-inscription" autocomplete="off">
            <div class="form-group">
                <label for="callsign">Pilote (callsign)</label>
                <select name="callsign" id="callsign" required>
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($callsigns as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= ($form['callsign'] === $c) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="immatriculation">Immatriculation</label>
                <select name="immatriculation" id="immatriculation" required>
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($immats as $i): ?>
                        <option value="<?= htmlspecialchars($i) ?>" <?= ($form['immatriculation'] === $i) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($i) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="departure_icao">Départ (ICAO)</label>
                <input type="text" name="departure_icao" id="departure_icao" maxlength="4" value="<?= htmlspecialchars($form['departure_icao']) ?>" required>
            </div>
            <div class="form-group">
                <label for="departure_fuel">Fuel départ</label>
                <input type="number" name="departure_fuel" id="departure_fuel" min="0" step="1" value="<?= htmlspecialchars($form['departure_fuel']) ?>" required>
            </div>
            <div class="form-group">
                <label for="departure_datetime">Date/heure départ</label>
                <input type="datetime-local" name="departure_datetime" id="departure_datetime" value="<?= htmlspecialchars($form['departure_datetime']) ?>" required>
            </div>
            <div class="form-group">
                <label for="arrival_icao">Arrivée (ICAO)</label>
                <input type="text" name="arrival_icao" id="arrival_icao" maxlength="4" value="<?= htmlspecialchars($form['arrival_icao']) ?>" required>
            </div>
            <div class="form-group">
                <label for="arrival_fuel">Fuel arrivée</label>
                <input type="number" name="arrival_fuel" id="arrival_fuel" min="0" step="1" value="<?= htmlspecialchars($form['arrival_fuel']) ?>" required>
            </div>
            <div class="form-group">
                <label for="arrival_datetime">Date/heure arrivée</label>
                <input type="datetime-local" name="arrival_datetime" id="arrival_datetime" value="<?= htmlspecialchars($form['arrival_datetime']) ?>" required>
            </div>
            <div class="form-group">
                <label for="payload">Payload (kg)</label>
                <input type="number" name="payload" id="payload" min="0" step="1" value="<?= htmlspecialchars($form['payload']) ?>">
            </div>
            <div class="form-group">
                <label for="commentaire">Commentaire</label>
                <input type="text" name="commentaire" id="commentaire" maxlength="255" value="<?= htmlspecialchars($form['commentaire']) ?>">
            </div>
            <div class="form-group">
                <label for="note_du_vol">Note du vol (1-10)</label>
                <input type="number" name="note_du_vol" id="note_du_vol" min="1" max="10" value="<?= htmlspecialchars($form['note_du_vol']) ?>" required>
            </div>
            <div class="form-group">
                <label for="mission">Mission</label>
                <select name="mission" id="mission" required>
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($missions as $m): ?>
                        <option value="<?= htmlspecialchars($m) ?>" <?= ($form['mission'] === $m) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="display:flex;gap:1em;">
                <button type="submit" class="btn btn-primary">Ajouter le vol</button>
                <button type="submit" name="reset" value="1" class="btn btn-secondary" style="margin-left:0;">Réinitialiser</button>
            </div>
        </form>
    </div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>