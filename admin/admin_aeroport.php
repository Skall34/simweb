<?php

require_once __DIR__ . '/../includes/require_admin.php';
require_once __DIR__ . '/../lang.php';

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
    // Recalcule le code canonique de surface depuis SURFACES_MAP
    $pdo->prepare("UPDATE PISTES p LEFT JOIN SURFACES_MAP m ON m.brut = p.surface SET p.surface_code = COALESCE(m.code, IF(p.surface IS NULL, NULL, 'UNK')) WHERE p.aeroport_ident = :ident")
        ->execute(['ident' => $ident]);
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

        $message = str_replace('{ident}', htmlspecialchars($ident), t('admin_airports_success_update'));
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

        $message = str_replace('{ident}', htmlspecialchars($ident), t('admin_airports_success_create'));
        $etat = 'recherche'; // on revient à l'écran de recherche
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
// Récupérer les types d'aéroports distincts pour la combo
$types_aero = [];
try {
    $tstmt = $pdo->query("SELECT DISTINCT type_aeroport FROM AEROPORTS WHERE type_aeroport IS NOT NULL AND type_aeroport <> '' ORDER BY type_aeroport ASC");
    $types_aero = $tstmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // ignore: on affichera un select vide si erreur
}
?>


<main>
    <h2><?= t('admin_airports_title') ?></h2>

    <?php if ($message): ?>
        <div class="success"><?= $message ?></div>
    <?php endif; ?>

    <!-- Étape 1 : formulaire de recherche -->
    <?php if ($etat === 'recherche'): ?>
        <form method="post" class="form-inscription">
            <label><?= t('admin_airports_ident_label') ?>
                <input name="ident" class="form-input input-250" required>
            </label>
            <button type="submit" name="action" value="rechercher" class="btn"><?= t('admin_airports_search_button') ?></button>
        </form>
    <?php endif; ?>

    <!-- Étape 2 : formulaire d'édition ou de création -->
    <?php if (in_array($etat, ['edition', 'creation'])): ?>
        <form method="post" class="form-inscription">
            <input type="hidden" name="ident" value="<?= htmlspecialchars($aeroport['ident']) ?>">

            <label><?= t('admin_airports_type_label') ?>
                <select name="type_aeroport" class="form-input">
                    <option value="">-- <?= t('admin_airports_select_option') ?> --</option>
                    <?php foreach ($types_aero as $ta): ?>
                        <option value="<?= htmlspecialchars($ta) ?>" <?= ($aeroport['type_aeroport']==$ta)?'selected':'' ?>><?= htmlspecialchars($ta) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label><?= t('admin_airports_name_label') ?>
                <input name="name" class="form-input" value="<?= htmlspecialchars($aeroport['name']) ?>">
            </label>

            <label><?= t('admin_airports_city_label') ?>
                <input name="municipality" class="form-input" value="<?= htmlspecialchars($aeroport['municipality']) ?>">
            </label>

            <label><?= t('admin_airports_lat_label') ?>
                <input name="latitude_deg" class="form-input" value="<?= htmlspecialchars($aeroport['latitude_deg']) ?>">
            </label>

            <label><?= t('admin_airports_lon_label') ?>
                <input name="longitude_deg" class="form-input" value="<?= htmlspecialchars($aeroport['longitude_deg']) ?>">
            </label>

            <label><?= t('admin_airports_alt_label') ?>
                <input name="elevation_ft" class="form-input" value="<?= htmlspecialchars($aeroport['elevation_ft']) ?>">
            </label>

            <fieldset class="admin-pistes">
                <legend><?= t('admin_airports_runways_label') ?></legend>
                <table class="pistes-table">
                    <tr>
                        <th><?= t('admin_airports_runway_designation') ?></th>
                        <th><?= t('admin_airports_runway_length_m') ?></th>
                        <th><?= t('admin_airports_runway_surface') ?></th>
                        <th><?= t('admin_airports_runway_code') ?></th>
                        <th></th>
                    </tr>
                    <?php foreach ($pistes as $p): ?>
                    <tr>
                        <td><input name="pistes[<?= (int)$p['id'] ?>][designation]" class="form-input" value="<?= htmlspecialchars($p['designation'] ?? '') ?>"></td>
                        <td><input name="pistes[<?= (int)$p['id'] ?>][longueur_m]" type="number" min="0" max="10000" class="form-input" value="<?= htmlspecialchars($p['longueur_m'] ?? '') ?>"></td>
                        <td><input name="pistes[<?= (int)$p['id'] ?>][surface]" class="form-input" value="<?= htmlspecialchars($p['surface'] ?? '') ?>"></td>
                        <td><?= htmlspecialchars($p['surface_code'] ?? '') ?></td>
                        <td><label><input type="checkbox" name="pistes[<?= (int)$p['id'] ?>][supprimer]"> <?= t('admin_airports_runway_delete') ?></label></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php for ($i = 0; $i < 3; $i++): ?>
                    <tr>
                        <td><input name="pistes[new][<?= $i ?>][designation]" class="form-input" placeholder="ex: 6-24"></td>
                        <td><input name="pistes[new][<?= $i ?>][longueur_m]" type="number" min="0" max="10000" class="form-input"></td>
                        <td><input name="pistes[new][<?= $i ?>][surface]" class="form-input" placeholder="ex: ASPH"></td>
                        <td></td>
                    </tr>
                    <?php endfor; ?>
                </table>
            </fieldset>

            <label><?= t('admin_airports_obs_label') ?>
                <textarea name="Observations" class="form-input"><?= htmlspecialchars($aeroport['Observations']) ?></textarea>
            </label>

            <label><?= t('admin_airports_wiki_label') ?>
                <input name="wikipedia_link" class="form-input" value="<?= htmlspecialchars($aeroport['wikipedia_link']) ?>">
            </label>

            <label><?= t('admin_airports_freight_label') ?>
                <input name="fret" type="number" class="form-input" value="<?= htmlspecialchars($aeroport['fret']) ?>">
            </label>

            <div class="form-actions admin-aeroport-actions">
                <button type="submit" name="action" value="<?= $etat === 'edition' ? 'mettre_a_jour' : 'creer' ?>" class="btn">
                    <?= $etat === 'edition' ? t('admin_airports_update_button') : t('admin_airports_create_button') ?>
                </button>
                <button type="button" class="btn btn-reset" onclick="window.location.href='admin_aeroport.php'"><?= t('admin_airports_back_button') ?></button>
            </div>
        </form>
    <?php endif; ?>
</main>


<?php include __DIR__ . '/../includes/footer.php'; ?>