<?php
require_once __DIR__ . '/../includes/require_admin.php';

$message = '';
$aeroport = [
    'ident' => '', 'type_aeroport' => '', 'name' => '', 'municipality' => '',
    'latitude_deg' => '', 'longitude_deg' => '', 'elevation_ft' => '',
    'Piste' => '', 'Longueur_de_piste' => '', 'Type_de_piste' => '',
    'Observations' => '', 'wikipedia_link' => '', 'fret' => ''
];

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
                Piste = :Piste,
                Longueur_de_piste = :Longueur_de_piste,
                Type_de_piste = :Type_de_piste,
                Observations = :Observations,
                wikipedia_link = :wikipedia_link,
                fret = :fret
            WHERE ident = :ident
        ");
        $stmt->execute($aeroport);

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
                elevation_ft, Piste, Longueur_de_piste, Type_de_piste,
                Observations, wikipedia_link, fret
            ) VALUES (
                :ident, :type_aeroport, :name, :municipality, :latitude_deg, :longitude_deg,
                :elevation_ft, :Piste, :Longueur_de_piste, :Type_de_piste,
                :Observations, :wikipedia_link, :fret
            )
        ");
        $stmt->execute($aeroport);

        // Met à jour la date de dernière modification globale
        $pdo->exec("UPDATE AEROPORTS_LAST_ADMIN_UPDATE SET last_update = NOW()");

        $message = "✅ Aéroport <strong>$ident</strong> créé.<BR>";
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
    <h2>Administration des Aéroports</h2>

    <?php if ($message): ?>
        <div class="success"><?= $message ?></div>
    <?php endif; ?>

    <!-- Étape 1 : formulaire de recherche -->
    <?php if ($etat === 'recherche'): ?>
        <form method="post" class="form-inscription">
            <label>Ident (code ICAO) * :
                <input name="ident" class="form-input input-250" required>
            </label>
            <button type="submit" name="action" value="rechercher" class="btn-bleu">Rechercher</button>
        </form>
    <?php endif; ?>

    <!-- Étape 2 : formulaire d'édition ou de création -->
    <?php if (in_array($etat, ['edition', 'creation'])): ?>
        <form method="post" class="form-inscription">
            <input type="hidden" name="ident" value="<?= htmlspecialchars($aeroport['ident']) ?>">

            <label>Type :
                <select name="type_aeroport" class="form-input">
                    <option value="">-- Sélectionner --</option>
                    <?php foreach ($types_aero as $ta): ?>
                        <option value="<?= htmlspecialchars($ta) ?>" <?= ($aeroport['type_aeroport']==$ta)?'selected':'' ?>><?= htmlspecialchars($ta) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label>Nom :
                <input name="name" class="form-input" value="<?= htmlspecialchars($aeroport['name']) ?>">
            </label>

            <label>Ville :
                <input name="municipality" class="form-input" value="<?= htmlspecialchars($aeroport['municipality']) ?>">
            </label>

            <label>Latitude :
                <input name="latitude_deg" class="form-input" value="<?= htmlspecialchars($aeroport['latitude_deg']) ?>">
            </label>

            <label>Longitude :
                <input name="longitude_deg" class="form-input" value="<?= htmlspecialchars($aeroport['longitude_deg']) ?>">
            </label>

            <label>Altitude (ft) :
                <input name="elevation_ft" class="form-input" value="<?= htmlspecialchars($aeroport['elevation_ft']) ?>">
            </label>

            <label>Piste :
                <textarea name="Piste" class="form-input"><?= htmlspecialchars($aeroport['Piste']) ?></textarea>
            </label>

            <label>Longueur de piste :
                <input name="Longueur_de_piste" class="form-input" value="<?= htmlspecialchars($aeroport['Longueur_de_piste']) ?>">
            </label>

            <label>Type de piste :
                <input name="Type_de_piste" class="form-input" value="<?= htmlspecialchars($aeroport['Type_de_piste']) ?>">
            </label>

            <label>Observations :
                <textarea name="Observations" class="form-input"><?= htmlspecialchars($aeroport['Observations']) ?></textarea>
            </label>

            <label>Wikipedia :
                <input name="wikipedia_link" class="form-input" value="<?= htmlspecialchars($aeroport['wikipedia_link']) ?>">
            </label>

            <label>Fret présent sur place :
                <input name="fret" type="number" class="form-input" value="<?= htmlspecialchars($aeroport['fret']) ?>">
            </label>

            <div class="form-actions" style="margin-top: 10px;">
                <button type="submit" name="action" value="<?= $etat === 'edition' ? 'mettre_a_jour' : 'creer' ?>" class="btn-bleu">
                    <?= $etat === 'edition' ? 'Mettre à jour' : 'Créer' ?>
                </button>
                <button type="button" class="btn btn-reset" onclick="window.location.href='admin_aeroport.php'">Retour</button>
            </div>
        </form>
    <?php endif; ?>
</main>


<?php include __DIR__ . '/../includes/footer.php'; ?>