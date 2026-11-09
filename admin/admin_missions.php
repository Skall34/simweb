<?php

require_once __DIR__ . '/../includes/require_admin.php';
require_once __DIR__ . '/../lang.php';

$message = '';
$errors = [];
$selectedMission = null;

// Catégories d'appareils possibles (identiques à admin_fleet_type.php)
$missionTypeCats = ['Monomoteur', 'Bimoteur', 'Liner', 'Helico'];

/**
 * Normalise la liste des types autorisés envoyée par le formulaire en CSV propre ('*' si aucun).
 */
function normaliserTypesAutorises($typesPost, $cats) {
    if (!is_array($typesPost) || empty($typesPost)) {
        return '*';
    }
    $clean = array_filter(array_map('trim', $typesPost), function ($v) use ($cats) {
        return in_array($v, $cats, true);
    });
    $csv = implode(',', $clean);
    return $csv === '' ? '*' : $csv;
}

/**
 * Indique si une catégorie doit être cochée pour une valeur types_autorises stockée.
 */
function missionTypeCoche($typesAutorises, $cat) {
    $t = trim((string)$typesAutorises);
    if ($t === '' || $t === '*') return true;
    return in_array($cat, array_map('trim', explode(',', $t)), true);
}

/**
 * Crée une page de mission basée sur un template
 */
function creerPageMission($libelle, &$errorMsg = '') {
    // Normaliser le nom : remplacer espaces et caractères spéciaux par +
    $nomFichier = strtoupper(str_replace([' ', '/', '\\', ':', '*', '?', '"', '<', '>', '|'], '+', $libelle));
    $cheminFichier = __DIR__ . '/../pages/missions/' . $nomFichier . '.php';
    
    // Debug
    if (defined('VA_DEBUG_MODE') && VA_DEBUG_MODE) {
        $errorMsg = "Tentative création : $cheminFichier | ";
    }
    
    // Vérifier si le fichier existe déjà
    if (file_exists($cheminFichier)) {
        $errorMsg .= "Le fichier existe déjà";
        return false; // Fichier existe déjà
    }
    
    // Template de la page mission
    $libelleEscaped = htmlspecialchars($libelle, ENT_QUOTES, 'UTF-8');
    $template = <<<'TEMPLATE'
<?php
require_once __DIR__ . '/../../includes/require_login.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/menu_logged.php';
?>
<main>
    <?php afficherCoefficientMission(); ?>
    <h1 style="text-align:center;color:#1a3552;margin-top:24px;margin-bottom:18px;">MISSION_LIBELLE</h1>
    <div style="display:flex;justify-content:center;margin-bottom:24px;">
        <!-- Ajoutez une image ici si nécessaire -->
        <!-- <img src="/assets/images/mission_image.jpg" alt="MISSION_LIBELLE" style="max-width:600px;width:100%;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.08);"> -->
    </div>
    <section style="max-width:700px;margin:0 auto 32px auto;font-size:1.15em;line-height:1.6;background:#f7fbff;padding:24px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.06);">
        <h2 style="color:#1a3552;">Description de la mission</h2>
        <p>Bienvenue sur la mission <strong>MISSION_LIBELLE</strong> !</p>
        <p>Cette page a été générée automatiquement. Vous pouvez la personnaliser en éditant le fichier :<br>
        <code>pages/missions/MISSION_FILENAME.php</code></p>
        
        <!-- Ajoutez une carte Google Maps si nécessaire -->
        <!-- <div style="text-align:center;margin:18px 0;">
            <iframe src="https://www.google.com/maps/d/embed?mid=YOUR_MAP_ID" width="640" height="480"></iframe>
        </div> -->
    </section>
    <section style="max-width:700px;margin:0 auto 32px auto;background:#fff;padding:24px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.04);">
        <h2 style="color:#1a3552;">Informations complémentaires</h2>
        <p>Ajoutez ici des informations sur les sceneries, les liens utiles, les instructions spécifiques, etc.</p>
        <!-- Exemple de liste de liens
        <ul style="list-style:disc inside; padding-left:20px; font-size:1.08em;">
            <li style="margin-bottom:10px;"><a href="#" target="_blank" style="color:#1a3552;font-weight:bold;text-decoration:underline;display:inline-block;">Lien 1</a></li>
            <li><a href="#" target="_blank" style="color:#1a3552;font-weight:bold;text-decoration:underline;display:inline-block;">Lien 2</a></li>
        </ul>
        -->
    </section>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

TEMPLATE;
    
    // Remplacer les placeholders
    $template = str_replace('MISSION_LIBELLE', $libelleEscaped, $template);
    $template = str_replace('MISSION_FILENAME', $nomFichier, $template);
    
    // Créer le dossier missions si nécessaire
    $dossierMissions = __DIR__ . '/../pages/missions';
    if (!is_dir($dossierMissions)) {
        $mkdirResult = @mkdir($dossierMissions, 0755, true);
        if (!$mkdirResult) {
            $errorMsg .= "Impossible de créer le dossier $dossierMissions";
            return false;
        }
    }
    
    // Écrire le fichier
    $result = @file_put_contents($cheminFichier, $template);
    if ($result !== false) {
        @chmod($cheminFichier, 0644);
        $errorMsg .= "Fichier créé avec succès : $result octets";
        return true;
    } else {
        $errorMsg .= "Échec file_put_contents() - Vérifiez permissions sur pages/missions/";
        return false;
    }
}

// Récupérer toutes les missions pour la liste déroulante
$missionsList = [];
try {
    $stmtAll = $pdo->query("SELECT id, libelle, majoration_mission, Active, est_controle, types_autorises FROM MISSIONS ORDER BY libelle ASC");
    $missionsList = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Traitement sélection/modification/ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'select') {
        $id = (int)($_POST['mission_id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT id, libelle, majoration_mission, Active, est_controle, types_autorises FROM MISSIONS WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $selectedMission = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$selectedMission) {
                $errors[] = t('admin_missions_error_not_found');
            }
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['mission_id'] ?? 0);
        $libelle = trim($_POST['libelle'] ?? '');
        $majoration = trim($_POST['majoration_mission'] ?? '');
        $active = isset($_POST['Active']) ? 1 : 0;
        $est_controle = isset($_POST['est_controle']) ? 1 : 0;
        $types_autorises = normaliserTypesAutorises($_POST['types_autorises'] ?? [], $missionTypeCats);

        // Normaliser en Title Case (première lettre de chaque mot en majuscule)
        $libelle = mb_convert_case($libelle, MB_CASE_TITLE, 'UTF-8');

        if ($libelle === '') $errors[] = t('admin_missions_error_libelle');
        if (!is_numeric($majoration) || $majoration < 0) $errors[] = t('admin_missions_error_majoration');
        if (empty($errors) && $id > 0) {
            $stmt = $pdo->prepare("UPDATE MISSIONS SET libelle = :libelle, majoration_mission = :maj, Active = :active, est_controle = :est_controle, types_autorises = :types WHERE id = :id");
            $stmt->execute([
                'libelle' => $libelle,
                'maj' => $majoration,
                'active' => $active,
                'est_controle' => $est_controle,
                'types' => $types_autorises,
                'id' => $id
            ]);
            $message = t('admin_missions_success_update');
            // Rafraîchir la sélection
            $stmt = $pdo->prepare("SELECT id, libelle, majoration_mission, Active, est_controle, types_autorises FROM MISSIONS WHERE id = :id");
            $stmt->execute(['id' => $id]);
            $selectedMission = $stmt->fetch(PDO::FETCH_ASSOC);
            // Rafraîchir la liste
            $stmtAll = $pdo->query("SELECT id, libelle, majoration_mission, Active, est_controle, types_autorises FROM MISSIONS ORDER BY libelle ASC");
            $missionsList = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
        }
    } elseif ($action === 'create') {
        $libelle = trim($_POST['libelle_new'] ?? '');
        $majoration = trim($_POST['majoration_mission_new'] ?? '');
        $active = isset($_POST['Active_new']) ? 1 : 0;
        $est_controle = isset($_POST['est_controle_new']) ? 1 : 0;
        $types_autorises = normaliserTypesAutorises($_POST['types_autorises_new'] ?? [], $missionTypeCats);
        
        // Normaliser en Title Case (première lettre de chaque mot en majuscule)
        $libelle = mb_convert_case($libelle, MB_CASE_TITLE, 'UTF-8');
        
        if ($libelle === '') $errors[] = t('admin_missions_error_libelle');
        if (!is_numeric($majoration) || $majoration < 0) $errors[] = t('admin_missions_error_majoration');
        // Vérifier unicité
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM MISSIONS WHERE libelle = :libelle");
        $stmt->execute(['libelle' => $libelle]);
        if ($stmt->fetchColumn() > 0) $errors[] = t('admin_missions_error_exists');
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO MISSIONS (libelle, majoration_mission, Active, est_controle, types_autorises) VALUES (:libelle, :maj, :active, :est_controle, :types)");
                $stmt->execute([
                    'libelle' => $libelle,
                    'maj' => $majoration,
                    'active' => $active,
                    'est_controle' => $est_controle,
                    'types' => $types_autorises
                ]);
                
                // Créer la page de mission
                $debugInfo = '';
                $pageCreee = creerPageMission($libelle, $debugInfo);
                
                $baseMessage = function_exists('t') ? t('admin_missions_success_create') : 'Mission créée avec succès';
                if ($pageCreee) {
                    $message = $baseMessage . ' La page de mission a été créée avec succès.';
                } else {
                    $message = $baseMessage . ' Attention : la page de mission n\'a pas pu être créée automatiquement.';
                    if (defined('VA_DEBUG_MODE') && VA_DEBUG_MODE && $debugInfo) {
                        $message .= '<br><small>Debug : ' . htmlspecialchars($debugInfo) . '</small>';
                    }
                }
                
                // Rafraîchir la liste
                $stmtAll = $pdo->query("SELECT id, libelle, majoration_mission, Active, est_controle, types_autorises FROM MISSIONS ORDER BY libelle ASC");
                $missionsList = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $errors[] = 'Erreur lors de la création : ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>


<main>
    <h2><?= t('admin_missions_title') ?></h2>

    <?php if ($message): ?>
        <p class="message-success"> <?= htmlspecialchars($message) ?> </p>
    <?php endif; ?>
    <?php if ($errors): ?>
        <ul class="message-error">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="admin-missions-layout">
        <div class="admin-missions-left">
            <form method="post" class="admin-missions-form-select">
                <label for="mission_id"><strong><?= t('admin_missions_select_label') ?></strong></label>
                <select name="mission_id" id="mission_id" onchange="this.form.submit()">
                    <option value=""><?= t('admin_missions_select_default') ?></option>
                    <?php foreach ($missionsList as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= (isset($selectedMission['id']) && $selectedMission['id'] == $m['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['libelle']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="action" value="select">
            </form>

            <?php if ($selectedMission): ?>
                <form method="post" class="admin-missions-form-edit">
                    <input type="hidden" name="mission_id" value="<?= $selectedMission['id'] ?>">
                    <label for="libelle"><?= t('admin_missions_label_libelle') ?></label>
                    <input type="text" name="libelle" id="libelle" value="<?= htmlspecialchars($selectedMission['libelle']) ?>" required>

                    <label for="majoration_mission"><?= t('admin_missions_label_majoration') ?></label>
                    <input type="number" step="0.10" min="0" name="majoration_mission" id="majoration_mission" value="<?= htmlspecialchars($selectedMission['majoration_mission']) ?>" required>

                    <label for="Active" class="admin-missions-checkbox-label">
                        <input type="checkbox" name="Active" id="Active" value="1" <?php if ((int)$selectedMission['Active'] === 1) echo 'checked'; ?>>
                        <?= t('admin_missions_label_active') ?>
                    </label>

                    <label for="est_controle" class="admin-missions-checkbox-label">
                        <input type="checkbox" name="est_controle" id="est_controle" value="1" <?php if ((int)$selectedMission['est_controle'] === 1) echo 'checked'; ?>>
                        <?= t('admin_missions_label_est_controle') ?>
                    </label>

                    <label><strong><?= t('admin_missions_label_types_autorises') ?></strong></label>
                    <?php foreach ($missionTypeCats as $c): ?>
                    <label class="admin-missions-checkbox-label">
                        <input type="checkbox" name="types_autorises[]" value="<?= htmlspecialchars($c) ?>" <?= missionTypeCoche($selectedMission['types_autorises'] ?? '*', $c) ? 'checked' : '' ?>>
                        <?= t('admin_fleet_type_cat_' . strtolower($c)) ?>
                    </label>
                    <?php endforeach; ?>
                    <p class="form-hint"><?= t('admin_missions_types_hint') ?></p>

                    <button type="submit" name="action" value="update"><?= t('admin_missions_btn_update') ?></button>
                </form>
            <?php endif; ?>

            <form method="post" class="admin-missions-form-create">
                <h3><?= t('admin_missions_create_title') ?></h3>
                <label for="libelle_new"><?= t('admin_missions_label_libelle') ?></label>
                <input type="text" name="libelle_new" id="libelle_new" required>

                <label for="majoration_mission_new"><?= t('admin_missions_label_majoration') ?></label>
                <input type="number" step="0.10" min="0" name="majoration_mission_new" id="majoration_mission_new" value="1.00" required>

                <label for="Active_new" class="admin-missions-checkbox-label">
                    <input type="checkbox" name="Active_new" id="Active_new" value="1" checked>
                    <?= t('admin_missions_label_active') ?>
                </label>

                <label for="est_controle_new" class="admin-missions-checkbox-label">
                    <input type="checkbox" name="est_controle_new" id="est_controle_new" value="1">
                    <?= t('admin_missions_label_est_controle') ?>
                </label>

                <label><strong><?= t('admin_missions_label_types_autorises') ?></strong></label>
                <?php foreach ($missionTypeCats as $c): ?>
                <label class="admin-missions-checkbox-label">
                    <input type="checkbox" name="types_autorises_new[]" value="<?= htmlspecialchars($c) ?>">
                    <?= t('admin_fleet_type_cat_' . strtolower($c)) ?>
                </label>
                <?php endforeach; ?>
                <p class="form-hint"><?= t('admin_missions_types_hint') ?></p>

                <button type="submit" name="action" value="create"><?= t('admin_missions_btn_create') ?></button>
            </form>
        </div>
        <div class="admin-missions-right">
            <h3 class="admin-missions-table-title"><?= t('admin_missions_table_title') ?></h3>
            <table class="admin-missions-table">
                <thead>
                    <tr>
                        <th><?= t('admin_missions_col_libelle') ?></th>
                        <th><?= t('admin_missions_col_majoration') ?></th>
                        <th><?= t('admin_missions_col_active') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($missionsList as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['libelle']) ?></td>
                            <td><?= htmlspecialchars($m['majoration_mission']) ?></td>
                            <td class="<?= ((int)$m['Active'] !== 0) ? 'admin-missions-active-yes' : 'admin-missions-active-no' ?>">
                                <?= ((int)$m['Active'] !== 0) ? t('admin_missions_active_yes') : t('admin_missions_active_no') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
