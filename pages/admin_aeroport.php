<?php
session_start();
// Require an active session (callsign set). If not logged, redirect to home.
if (!isset($_SESSION['callsign'])) {
    header('Location: /index.php');
    exit;
}
require_once __DIR__ . '/../includes/db_connect.php';

$message = '';
$aeroport = [
    'ident' => '', 'type_aeroport' => '', 'name' => '', 'municipality' => '',
    'latitude_deg' => '', 'longitude_deg' => '', 'elevation_ft' => '',
    'Observations' => '', 'wikipedia_link' => '', 'fret' => ''
];
$pistes = [];

// Enregistre les pistes postées (tableau 'pistes') pour un aéroport
function savePistes(PDO $pdo, string $ident): void {
    if (empty($_POST['pistes']) || !is_array($_POST['pistes'])) return;
    $upd = $pdo->prepare("UPDATE PISTES SET designation = :d, longueur_m = :l, surface = :s WHERE id = :id AND aeroport_ident = :ident");
    $del = $pdo->prepare("DELETE FROM PISTES WHERE id = :id AND aeroport_ident = :ident");
    $ins = $pdo->prepare("INSERT INTO PISTES (aeroport_ident, designation, longueur_m, surface) VALUES (:ident, :d, :l, :s)");
    foreach ($_POST['pistes'] as $key => $p) {
        if ($key === 'new') {
            foreach ((array)$p as $np) {
                if (!is_array($np)) continue;
                $d = trim((string)($np['designation'] ?? '')) ?: null;
                $len = trim((string)($np['longueur_m'] ?? ''));
                $l = ($len !== '' && is_numeric($len)) ? (int)$len : null;
                $s = trim((string)($np['surface'] ?? '')) ?: null;
                if ($d === null && $l === null && $s === null) continue; // ligne vide
                $ins->execute(['ident' => $ident, 'd' => $d, 'l' => $l, 's' => $s]);
            }
            continue;
        }
        $d = trim((string)($p['designation'] ?? '')) ?: null;
        $len = trim((string)($p['longueur_m'] ?? ''));
        $l = ($len !== '' && is_numeric($len)) ? (int)$len : null;
        $s = trim((string)($p['surface'] ?? '')) ?: null;
        $id = (int)$key;
        if (!empty($p['supprimer']) || ($d === null && $l === null && $s === null)) {
            $del->execute(['id' => $id, 'ident' => $ident]);
        } else {
            $upd->execute(['d' => $d, 'l' => $l, 's' => $s, 'id' => $id, 'ident' => $ident]);
        }
    }
}

$etat = 'recherche'; // valeur par défaut

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ident = strtoupper(trim($_POST['ident']));
    $aeroport['ident'] = $ident;

    if (isset($_POST['action']) && $_POST['action'] === 'rechercher') {
        $stmt = $pdo->prepare("SELECT * FROM AEROPORTS WHERE ident = :ident");
        $stmt->execute(['ident' => $ident]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $aeroport = $result;
            $pstmt = $pdo->prepare("SELECT * FROM PISTES WHERE aeroport_ident = :ident ORDER BY id");
            $pstmt->execute(['ident' => $ident]);
            $pistes = $pstmt->fetchAll(PDO::FETCH_ASSOC);
            $etat = 'edition';
        } else {
            $etat = 'creation';
        }

    } elseif (isset($_POST['action']) && $_POST['action'] === 'mettre_a_jour') {
        foreach ($aeroport as $key => $_) {
            if ($key !== 'ident') {
                $aeroport[$key] = $_POST[$key] ?? null;
            }
        }

        $stmt = $pdo->prepare("
            UPDATE AEROPORTS SET
                type_aeroport = :type_aeroport,
                name = :name,
                municipality = :municipality,
                latitude_deg = :latitude_deg,
                longitude_deg = :longitude_deg,
                elevation_ft = :elevation_ft,
                Observations = :Observations,
                wikipedia_link = :wikipedia_link,
                fret = :fret
            WHERE ident = :ident
        ");
        $stmt->execute($aeroport);

        savePistes($pdo, $ident);

        // Met à jour la date de dernière modification globale
        $pdo->exec("UPDATE AEROPORTS_LAST_ADMIN_UPDATE SET last_update = NOW()");

        $message = "✅ Aéroport <strong>$ident</strong> mis à jour.<BR>";
        $etat = 'recherche'; // on revient à l'écran de recherche

    } elseif (isset($_POST['action']) && $_POST['action'] === 'creer') {
        foreach ($aeroport as $key => $_) {
            if ($key !== 'ident') {
                $aeroport[$key] = $_POST[$key] ?? null;
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO AEROPORTS (
                ident, type_aeroport, name, municipality, latitude_deg, longitude_deg,
                elevation_ft, Observations, wikipedia_link, fret
            ) VALUES (
                :ident, :type_aeroport, :name, :municipality, :latitude_deg, :longitude_deg,
                :elevation_ft, :Observations, :wikipedia_link, :fret
            )
        ");
        $stmt->execute($aeroport);

        savePistes($pdo, $ident);

        // Met à jour la date de dernière modification globale
        $pdo->exec("UPDATE AEROPORTS_LAST_ADMIN_UPDATE SET last_update = NOW()");

        $message = "✅ Aéroport <strong>$ident</strong> créé.<BR>";
        $etat = 'recherche'; // on revient à l'écran de recherche
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>

<main>
    <h2>Administration des Aéroports</h2>

    <?php if ($message): ?>
        <div class="success"><?= $message ?></div>
    <?php endif; ?>

    <!-- Étape 1 : formulaire de recherche -->
    <?php if ($etat === 'recherche'): ?>
        <form method="post" class="form-inscription">
            <label>Ident (code ICAO) * :
                <input name="ident" style="width: 400px;" required>
            </label>
            <button type="submit" name="action" value="rechercher">Rechercher</button>
        </form>
    <?php endif; ?>

    <!-- Étape 2 : formulaire d'édition ou de création -->
    <?php if (in_array($etat, ['edition', 'creation'])): ?>
        <form method="post" class="form-inscription">
            <input type="hidden" name="ident" value="<?= htmlspecialchars($aeroport['ident']) ?>">

            <label>Type :
                <input name="type_aeroport" value="<?= htmlspecialchars($aeroport['type_aeroport']) ?>">
            </label>

            <label>Nom :
                <input name="name" value="<?= htmlspecialchars($aeroport['name']) ?>">
            </label>

            <label>Ville :
                <input name="municipality" value="<?= htmlspecialchars($aeroport['municipality']) ?>">
            </label>

            <label>Latitude :
                <input name="latitude_deg" value="<?= htmlspecialchars($aeroport['latitude_deg']) ?>">
            </label>

            <label>Longitude :
                <input name="longitude_deg" value="<?= htmlspecialchars($aeroport['longitude_deg']) ?>">
            </label>

            <label>Altitude (ft) :
                <input name="elevation_ft" value="<?= htmlspecialchars($aeroport['elevation_ft']) ?>">
            </label>

            <fieldset class="admin-pistes">
                <legend>Pistes :</legend>
                <table class="pistes-table">
                    <tr>
                        <th>Désignation</th>
                        <th>Longueur (m)</th>
                        <th>Surface</th>
                        <th></th>
                    </tr>
                    <?php foreach ($pistes as $p): ?>
                    <tr>
                        <td><input name="pistes[<?= (int)$p['id'] ?>][designation]" value="<?= htmlspecialchars($p['designation'] ?? '') ?>"></td>
                        <td><input name="pistes[<?= (int)$p['id'] ?>][longueur_m]" type="number" min="0" max="10000" value="<?= htmlspecialchars($p['longueur_m'] ?? '') ?>"></td>
                        <td><input name="pistes[<?= (int)$p['id'] ?>][surface]" value="<?= htmlspecialchars($p['surface'] ?? '') ?>"></td>
                        <td><label><input type="checkbox" name="pistes[<?= (int)$p['id'] ?>][supprimer]"> Supprimer</label></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php for ($i = 0; $i < 3; $i++): ?>
                    <tr>
                        <td><input name="pistes[new][<?= $i ?>][designation]" placeholder="ex: 6-24"></td>
                        <td><input name="pistes[new][<?= $i ?>][longueur_m]" type="number" min="0" max="10000"></td>
                        <td><input name="pistes[new][<?= $i ?>][surface]" placeholder="ex: ASPH"></td>
                        <td></td>
                    </tr>
                    <?php endfor; ?>
                </table>
            </fieldset>

            <label>Observations :
                <textarea name="Observations"><?= htmlspecialchars($aeroport['Observations']) ?></textarea>
            </label>

            <label>Wikipedia :
                <input name="wikipedia_link" value="<?= htmlspecialchars($aeroport['wikipedia_link']) ?>">
            </label>

            <label>Fret (0 ou 1) :
                <input name="fret" type="number" value="<?= htmlspecialchars($aeroport['fret']) ?>">
            </label>

            <br>
            <button type="submit" name="action" value="<?= $etat === 'edition' ? 'mettre_a_jour' : 'creer' ?>">
                <?= $etat === 'edition' ? 'Mettre à jour' : 'Créer' ?>
            </button>
        </form>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>