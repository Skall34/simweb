<?php
require_once __DIR__ . '/../includes/require_admin.php';
require_once __DIR__ . '/../lang.php';

require_once __DIR__ . '/../includes/mail_utils.php';
require_once __DIR__ . '/../includes/log_func.php';

$message = '';
$flash = '';
// Read flash message from session (set after POST redirects)
if (!empty($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
$showFieldPopup = false;
// If a previous POST requested showing the small field tooltip, persist that across redirect
if (!empty($_SESSION['flash_show_field_popup'])) {
    $showFieldPopup = true;
    unset($_SESSION['flash_show_field_popup']);
}
$edit_mode = false;
$line = ['id' => '', 'icao_dep' => '', 'icao_arr' => '', 'distance' => '', 'created_at' => '', 'updated_at' => ''];

// If editing an existing line the $line will be populated later; compute a placeholder distance
// when both ICAOs are available so we can show it as a suggestion in the input.
$placeholder_distance = null;
$showFieldPopup = false;

// Helper: calculate great-circle distance in nautical miles between two lat/lon pairs
function calc_distance_nm($lat1, $lon1, $lat2, $lon2) {
    // Convert degrees to radians
    $deg2rad = pi() / 180.0;
    $lat1r = $lat1 * $deg2rad;
    $lon1r = $lon1 * $deg2rad;
    $lat2r = $lat2 * $deg2rad;
    $lon2r = $lon2 * $deg2rad;

    // Haversine formula
    $dlat = $lat2r - $lat1r;
    $dlon = $lon2r - $lon1r;
    $a = sin($dlat/2) * sin($dlat/2) + cos($lat1r) * cos($lat2r) * sin($dlon/2) * sin($dlon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    $earth_km = 6371.0;
    $dist_km = $earth_km * $c;
    $dist_nm = $dist_km / 1.852; // 1 NM = 1.852 km
    return $dist_nm;
}

// Handle POST actions: add, update, delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $icao_dep = strtoupper(trim($_POST['icao_dep'] ?? ''));
        $icao_arr = strtoupper(trim($_POST['icao_arr'] ?? ''));
        $type_ligne = isset($_POST['type_ligne']) ? (int)$_POST['type_ligne'] : null;
        $distance_raw = trim($_POST['distance'] ?? '');
        $distance = $distance_raw === '' ? null : str_replace(',', '.', $distance_raw);
        $create_return = isset($_POST['create_return']) && $_POST['create_return'] === '1';
        
        if ($icao_dep === '' || $icao_arr === '') {
            $message = t('admin_lines_error_icao_required');
        } elseif (empty($type_ligne) || $type_ligne <= 0) {
            $message = t('admin_lines_error_type_required');
            // Persist a flag through the redirect so we can show the small anchored tooltip
            $_SESSION['flash_show_field_popup'] = true;
        } else {
            try {
                // Validate that both ICAO codes exist in AEROPORTS table
                $stmtCheckDep = $pdo->prepare("SELECT COUNT(*) AS c FROM AEROPORTS WHERE ident = :icao");
                $stmtCheckDep->execute(['icao' => $icao_dep]);
                $depExists = $stmtCheckDep->fetch(PDO::FETCH_ASSOC);
                
                $stmtCheckArr = $pdo->prepare("SELECT COUNT(*) AS c FROM AEROPORTS WHERE ident = :icao");
                $stmtCheckArr->execute(['icao' => $icao_arr]);
                $arrExists = $stmtCheckArr->fetch(PDO::FETCH_ASSOC);
                
                $invalidIcaos = [];
                if (!$depExists || (int)$depExists['c'] === 0) {
                    $invalidIcaos[] = $icao_dep;
                }
                if (!$arrExists || (int)$arrExists['c'] === 0) {
                    $invalidIcaos[] = $icao_arr;
                }
                
                if (!empty($invalidIcaos)) {
                    $message = t('admin_lines_error_icao_not_found', ['icao' => implode(', ', $invalidIcaos)]);
                    $_SESSION['flash_message'] = $message;
                    header('Location: admin_lignes_regulieres.php');
                    exit;
                }
                
                // Check duplicate exact pair for outbound
                $chk = $pdo->prepare("SELECT COUNT(*) AS c FROM LIGNES_REGULIERES WHERE icao_dep = :dep AND icao_arr = :arr");
                $chk->execute(['dep' => $icao_dep, 'arr' => $icao_arr]);
                $row = $chk->fetch(PDO::FETCH_ASSOC);
                
                if ($row && (int)$row['c'] > 0) {
                    $message = t('admin_lines_error_exists', ['dep' => $icao_dep, 'arr' => $icao_arr]);
                } else {
                    // If distance not provided, attempt to compute from AEROPORTS table
                    if ($distance === null) {
                        try {
                            $stmtDep = $pdo->prepare("SELECT latitude_deg, longitude_deg FROM AEROPORTS WHERE ident = :icao LIMIT 1");
                            $stmtDep->execute(['icao' => $icao_dep]);
                            $dep = $stmtDep->fetch(PDO::FETCH_ASSOC);

                            $stmtArr = $pdo->prepare("SELECT latitude_deg, longitude_deg FROM AEROPORTS WHERE ident = :icao LIMIT 1");
                            $stmtArr->execute(['icao' => $icao_arr]);
                            $arr = $stmtArr->fetch(PDO::FETCH_ASSOC);

                                if ($dep && $arr && $dep['latitude_deg'] !== null && $arr['latitude_deg'] !== null) {
                                $computed = calc_distance_nm((float)$dep['latitude_deg'], (float)$dep['longitude_deg'], (float)$arr['latitude_deg'], (float)$arr['longitude_deg']);
                                $distance = (int)ceil($computed);
                            }
                        } catch (Exception $e) {
                            // ignore and leave distance null
                        }
                    }

                    // Insert outbound
                    $stmt = $pdo->prepare("INSERT INTO LIGNES_REGULIERES (icao_dep, icao_arr, type_ligne, distance, created_at, updated_at) VALUES (:dep, :arr, :type_ligne, :distance, NOW(), NOW())");
                    $stmt->execute(['dep' => $icao_dep, 'arr' => $icao_arr, 'type_ligne' => $type_ligne, 'distance' => $distance]);
                    
                    if ($create_return) {
                        // Check if return already exists
                        $chkReturn = $pdo->prepare("SELECT COUNT(*) AS c FROM LIGNES_REGULIERES WHERE icao_dep = :dep AND icao_arr = :arr");
                        $chkReturn->execute(['dep' => $icao_arr, 'arr' => $icao_dep]);
                        $rowReturn = $chkReturn->fetch(PDO::FETCH_ASSOC);
                        
                        if ($rowReturn && (int)$rowReturn['c'] > 0) {
                            $message = t('admin_lines_success_add_return_exists', ['dep' => $icao_dep, 'arr' => $icao_arr]);
                        } else {
                            // Insert return
                            $stmtReturn = $pdo->prepare("INSERT INTO LIGNES_REGULIERES (icao_dep, icao_arr, type_ligne, distance, created_at, updated_at) VALUES (:dep, :arr, :type_ligne, :distance, NOW(), NOW())");
                            $stmtReturn->execute(['dep' => $icao_arr, 'arr' => $icao_dep, 'type_ligne' => $type_ligne, 'distance' => $distance]);
                            $message = t('admin_lines_success_add_both', ['dep' => $icao_dep, 'arr' => $icao_arr]);
                        }
                    } else {
                        $message = t('admin_lines_success_add', ['dep' => $icao_dep, 'arr' => $icao_arr]);
                    }

                    // Send notification mail to admin about the new line(s)
                    try {
                        $callsign = $_SESSION['user']['callsign'] ?? '';
                        $mailSubject = t('admin_lines_mail_subject');
                        // Determine type label if available
                        $typeLabel = '-';
                        if (!empty($type_ligne)) {
                            try {
                                $stmtType = $pdo->prepare("SELECT label FROM TYPE_LIGNE WHERE id = :id LIMIT 1");
                                $stmtType->execute(['id' => $type_ligne]);
                                $tl = $stmtType->fetch(PDO::FETCH_ASSOC);
                                if ($tl && isset($tl['label'])) $typeLabel = $tl['label'];
                            } catch (Exception $e) {
                                // ignore
                            }
                        }
                        $distDisplay = is_null($distance) ? '-' : ((int)$distance);
                        $mailBody = '<h3>' . t('admin_lines_mail_title') . '</h3>' .
                            '<ul>' .
                            '<li><strong>' . t('admin_lines_mail_route') . ' :</strong> ' . htmlspecialchars($icao_dep) . ' → ' . htmlspecialchars($icao_arr) . '</li>' .
                            '<li><strong>' . t('admin_lines_mail_distance') . ' :</strong> ' . htmlspecialchars((string)$distDisplay) . ' NM</li>' .
                            '<li><strong>' . t('admin_lines_mail_type') . ' :</strong> ' . htmlspecialchars($typeLabel) . '</li>' .
                            '<li><strong>' . t('admin_lines_mail_created_by') . ' :</strong> ' . htmlspecialchars($callsign) . '</li>' .
                            '<li><strong>' . t('admin_lines_mail_date') . ' :</strong> ' . date('Y-m-d H:i:s') . '</li>' .
                            '</ul>';
                        if (defined('VA_ADMIN_EMAIL') && VA_ADMIN_EMAIL) {
                            $mailResult = sendSummaryMail($mailSubject, $mailBody, VA_ADMIN_EMAIL);
                            if ($mailResult === true || $mailResult === null || (is_array($mailResult) && !empty($mailResult['success']))) {
                                logMsg('Mail admin_lines envoyé pour ligne ' . $icao_dep . '->' . $icao_arr, __DIR__ . '/../scripts/logs/admin_lignes.log');
                            } else {
                                $errMsg = is_array($mailResult) ? (isset($mailResult['error']) ? $mailResult['error'] : json_encode($mailResult, JSON_UNESCAPED_UNICODE)) : (string)$mailResult;
                                logMsg('Envoi mail admin_lines échoué: ' . $errMsg, __DIR__ . '/../scripts/logs/admin_lignes.log');
                            }
                        }
                    } catch (Exception $e) {
                        logMsg('Exception envoi mail admin_lines: ' . $e->getMessage(), __DIR__ . '/../scripts/logs/admin_lignes.log');
                    }
                }
            } catch (Exception $e) {
                $message = t('admin_lines_error_add', ['error' => htmlspecialchars($e->getMessage())]);
            }
        }
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $icao_dep = strtoupper(trim($_POST['icao_dep'] ?? ''));
        $icao_arr = strtoupper(trim($_POST['icao_arr'] ?? ''));
        $type_ligne = isset($_POST['type_ligne']) ? (int)$_POST['type_ligne'] : null;
        $distance_raw = trim($_POST['distance'] ?? '');
        $distance = $distance_raw === '' ? null : str_replace(',', '.', $distance_raw);
        if ($id <= 0 || $icao_dep === '' || $icao_arr === '') {
            $message = t('admin_lines_error_invalid_data');
        } else {
            try {
                // Validate that both ICAO codes exist in AEROPORTS table
                $stmtCheckDep = $pdo->prepare("SELECT COUNT(*) AS c FROM AEROPORTS WHERE ident = :icao");
                $stmtCheckDep->execute(['icao' => $icao_dep]);
                $depExists = $stmtCheckDep->fetch(PDO::FETCH_ASSOC);
                
                $stmtCheckArr = $pdo->prepare("SELECT COUNT(*) AS c FROM AEROPORTS WHERE ident = :icao");
                $stmtCheckArr->execute(['icao' => $icao_arr]);
                $arrExists = $stmtCheckArr->fetch(PDO::FETCH_ASSOC);
                
                $invalidIcaos = [];
                if (!$depExists || (int)$depExists['c'] === 0) {
                    $invalidIcaos[] = $icao_dep;
                }
                if (!$arrExists || (int)$arrExists['c'] === 0) {
                    $invalidIcaos[] = $icao_arr;
                }
                
                if (!empty($invalidIcaos)) {
                    $message = t('admin_lines_error_icao_not_found', ['icao' => implode(', ', $invalidIcaos)]);
                    $_SESSION['flash_message'] = $message;
                    header('Location: admin_lignes_regulieres.php');
                    exit;
                }
                
                // Ensure we don't create a duplicate (excluding current row)
                $chk = $pdo->prepare("SELECT COUNT(*) AS c FROM LIGNES_REGULIERES WHERE icao_dep = :dep AND icao_arr = :arr AND id != :id");
                $chk->execute(['dep' => $icao_dep, 'arr' => $icao_arr, 'id' => $id]);
                $row = $chk->fetch(PDO::FETCH_ASSOC);
                if ($row && (int)$row['c'] > 0) {
                    $message = t('admin_lines_error_exists_other', ['dep' => $icao_dep, 'arr' => $icao_arr]);
                } else {
                    // If distance not provided, try to compute from AEROPORTS
                    if ($distance === null) {
                        try {
                            $stmtDep = $pdo->prepare("SELECT latitude_deg, longitude_deg FROM AEROPORTS WHERE ident = :icao LIMIT 1");
                            $stmtDep->execute(['icao' => $icao_dep]);
                            $dep = $stmtDep->fetch(PDO::FETCH_ASSOC);

                            $stmtArr = $pdo->prepare("SELECT latitude_deg, longitude_deg FROM AEROPORTS WHERE ident = :icao LIMIT 1");
                            $stmtArr->execute(['icao' => $icao_arr]);
                            $arr = $stmtArr->fetch(PDO::FETCH_ASSOC);

                                if ($dep && $arr && $dep['latitude_deg'] !== null && $arr['latitude_deg'] !== null) {
                                $computed = calc_distance_nm((float)$dep['latitude_deg'], (float)$dep['longitude_deg'], (float)$arr['latitude_deg'], (float)$arr['longitude_deg']);
                                $distance = (int)ceil($computed);
                            }
                        } catch (Exception $e) {
                            // ignore
                        }
                    }

                    $stmt = $pdo->prepare("UPDATE LIGNES_REGULIERES SET icao_dep = :dep, icao_arr = :arr, type_ligne = :type_ligne, distance = :distance, updated_at = NOW() WHERE id = :id");
                    $stmt->execute(['dep' => $icao_dep, 'arr' => $icao_arr, 'type_ligne' => $type_ligne, 'distance' => $distance, 'id' => $id]);
                    $message = t('admin_lines_success_update', ['dep' => $icao_dep, 'arr' => $icao_arr]);
                }
            } catch (Exception $e) {
                $message = t('admin_lines_error_update', ['error' => htmlspecialchars($e->getMessage())]);
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $message = t('admin_lines_error_invalid_id');
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM LIGNES_REGULIERES WHERE id = :id");
                $stmt->execute(['id' => $id]);
                $message = t('admin_lines_success_delete', ['id' => $id]);
            } catch (Exception $e) {
                $message = t('admin_lines_error_delete', ['error' => htmlspecialchars($e->getMessage())]);
            }
        }
    }

    // After any POST, store the message in session and redirect to show it (flash)
    if (!empty($message)) {
        $_SESSION['flash_message'] = $message;
    }
    header('Location: admin_lignes_regulieres.php');
    exit;
}

// If edit requested, load line
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM LIGNES_REGULIERES WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $line = $row;
            $edit_mode = true;
            // compute placeholder distance suggestion when editing and no distance set
            if (empty($line['distance']) && !empty($line['icao_dep']) && !empty($line['icao_arr'])) {
                try {
                    $stmtDepPh = $pdo->prepare("SELECT latitude_deg, longitude_deg FROM AEROPORTS WHERE ident = :icao LIMIT 1");
                    $stmtDepPh->execute(['icao' => $line['icao_dep']]);
                    $depPh = $stmtDepPh->fetch(PDO::FETCH_ASSOC);

                    $stmtArrPh = $pdo->prepare("SELECT latitude_deg, longitude_deg FROM AEROPORTS WHERE ident = :icao LIMIT 1");
                    $stmtArrPh->execute(['icao' => $line['icao_arr']]);
                    $arrPh = $stmtArrPh->fetch(PDO::FETCH_ASSOC);

                    if ($depPh && $arrPh && $depPh['latitude_deg'] !== null && $arrPh['latitude_deg'] !== null) {
                        $computedPh = calc_distance_nm((float)$depPh['latitude_deg'], (float)$depPh['longitude_deg'], (float)$arrPh['latitude_deg'], (float)$arrPh['longitude_deg']);
                        $placeholder_distance = (int)ceil($computedPh);
                    }
                } catch (Exception $e) {
                    // ignore
                }
            }
        }
    }
}

// Fetch all lines (inclure le label du type via LEFT JOIN)
$stmt = $pdo->query("
    SELECT lr.id, lr.icao_dep, lr.icao_arr, lr.type_ligne, lr.distance, tl.label AS type_label, lr.created_at, lr.updated_at
    FROM LIGNES_REGULIERES lr
    LEFT JOIN TYPE_LIGNE tl ON lr.type_ligne = tl.id
    ORDER BY lr.icao_dep, lr.icao_arr
");
$lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Récupérer la liste des types de ligne pour la combobox ---
try {
    $stmtTypes = $pdo->query("SELECT id, label FROM TYPE_LIGNE ORDER BY label ASC");
    $typeLignes = $stmtTypes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $typeLignes = [];
}

// --- NOUVEAU : lire les filtres GET ---
$filter_dep = isset($_GET['icao_dep']) ? strtoupper(trim($_GET['icao_dep'])) : '';
$filter_arr = isset($_GET['icao_arr']) ? strtoupper(trim($_GET['icao_arr'])) : '';
$filter_type = (isset($_GET['type_ligne']) && $_GET['type_ligne'] !== '') ? (int)$_GET['type_ligne'] : null;

// --- NOUVEAU : construire requête avec filtres ---
$sql = "
    SELECT lr.id, lr.icao_dep, lr.icao_arr, lr.type_ligne, lr.distance, tl.label AS type_label, lr.created_at, lr.updated_at
    FROM LIGNES_REGULIERES lr
    LEFT JOIN TYPE_LIGNE tl ON lr.type_ligne = tl.id
";
$conds = [];
$params = [];
if ($filter_dep !== '') {
    $conds[] = "lr.icao_dep LIKE :dep";
    $params['dep'] = $filter_dep . '%';
}
if ($filter_arr !== '') {
    $conds[] = "lr.icao_arr LIKE :arr";
    $params['arr'] = $filter_arr . '%';
}
if ($filter_type !== null) {
    $conds[] = "lr.type_ligne = :type_ligne";
    $params['type_ligne'] = $filter_type;
}
if (!empty($conds)) {
    $sql .= " WHERE " . implode(' AND ', $conds);
}
$sql .= " ORDER BY lr.icao_dep ASC, lr.icao_arr ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>

<main>
    <h2><?= t('admin_lines_title', ['count' => count($lines)]) ?></h2>

    <?php if ($message && !$showFieldPopup): ?>
        <?php
            // Decide display style similar to pages/register.php: errors as list, successes as green box
            $is_error = preg_match('/Erreur|Error|Erreur|⚠|⚠️|Erreur lors|Error during/i', strip_tags($message));
        ?>
        <?php if ($is_error): ?>
            <div class="erreurs">
                <ul>
                    <li><?= htmlspecialchars(strip_tags($message)) ?></li>
                </ul>
            </div>
        <?php else: ?>
            <div class="success" style="background:#e6f9e6;color:#1a7e1a;padding:12px 18px;border-radius:6px;margin-bottom:18px;font-weight:bold;text-align:center;box-shadow:0 2px 8px #0001;">
                <?= htmlspecialchars(strip_tags($message)) ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <section class="admin-lines-form-section">
        <div class="admin-lines-form-wrapper">
        <h3><?= $edit_mode ? t('admin_lines_form_edit_title') : t('admin_lines_form_add_title') ?></h3>
    <form method="post" class="form-inscription admin-lines-inline-form">
             <?php if ($edit_mode): ?>
                 <input type="hidden" name="id" value="<?= htmlspecialchars($line['id']) ?>">
             <?php endif; ?>

            <label>
                <span><?= t('admin_lines_label_icao_dep') ?></span>
                <input name="icao_dep" required value="<?= htmlspecialchars($line['icao_dep']) ?>" class="fleet-filter-input input-160 input-uppercase" oninput="this.value = this.value.toUpperCase();">
            </label>

            <label>
                <span><?= t('admin_lines_label_icao_arr') ?></span>
                <input name="icao_arr" required value="<?= htmlspecialchars($line['icao_arr']) ?>" class="fleet-filter-input input-160 input-uppercase" oninput="this.value = this.value.toUpperCase();">
            </label>

            <label>
                <span><?= t('admin_lines_label_type') ?></span>
                <select name="type_ligne" class="fleet-filter-select input-160">
                    <option value=""><?= t('admin_lines_type_none') ?></option>
                    <?php foreach ($typeLignes as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= (isset($line['type_ligne']) && (int)$line['type_ligne'] === (int)$t['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                    <?php if ($showFieldPopup): ?>
                        <div id="type-field-tooltip" class="field-tooltip" style="display:none">
                            <div class="ft-inner">
                                <span class="ft-icon">&#9888;</span>
                                <span class="ft-text"><?= htmlspecialchars(strip_tags($message)) ?></span>
                            </div>
                            <div class="ft-arrow"></div>
                        </div>
                        <style>
                            .field-tooltip { position: absolute; z-index: 12000; pointer-events: auto; }
                            .field-tooltip .ft-inner { background: #fff8e6; border: 1px solid #f0c36d; color: #333; padding: 8px 10px; border-radius: 6px; box-shadow: 0 6px 18px rgba(0,0,0,0.12); display: flex; gap:8px; align-items:center; font-size:13px; }
                            .field-tooltip .ft-icon { display:inline-block; background:#ffb84d; color:#fff; width:20px; height:20px; line-height:20px; text-align:center; border-radius:4px; font-weight:bold; }
                            .field-tooltip .ft-text { display:inline-block; }
                            .field-tooltip .ft-arrow { width:0; height:0; position: absolute; left: 18px; }
                            .field-tooltip.up .ft-arrow { bottom:-8px; border-left:8px solid transparent; border-right:8px solid transparent; border-top:8px solid #fff8e6; }
                            .field-tooltip.down .ft-arrow { top:-8px; border-left:8px solid transparent; border-right:8px solid transparent; border-bottom:8px solid #fff8e6; }
                        </style>
                        <script>
                            (function(){
                                var tooltip = document.getElementById('type-field-tooltip');
                                var select = document.querySelector('select[name="type_ligne"]');
                                if(!tooltip || !select) return;
                                tooltip.style.display = 'block';
                                // compute position: prefer above the field
                                var rect = select.getBoundingClientRect();
                                // ensure body has position: relative coordinate using page scroll
                                var scrollY = window.scrollY || window.pageYOffset;
                                var scrollX = window.scrollX || window.pageXOffset;
                                // temporary add to DOM to measure size
                                var tipRect = tooltip.getBoundingClientRect();
                                var top = scrollY + rect.top - tipRect.height - 8;
                                var placedUp = true;
                                if (top < scrollY + 6) {
                                    top = scrollY + rect.bottom + 8;
                                    placedUp = false;
                                }
                                var left = scrollX + rect.left;
                                // try center the tooltip roughly over select
                                left = left + (rect.width/2) - (tipRect.width/2);
                                if (left < 6) left = 6;
                                tooltip.style.top = top + 'px';
                                tooltip.style.left = left + 'px';
                                if (placedUp) {
                                    tooltip.classList.add('up');
                                } else {
                                    tooltip.classList.add('down');
                                }
                                // focus the select so the admin can change it quickly
                                select.focus();
                                // auto-hide when user interacts with select
                                select.addEventListener('change', function(){ tooltip.style.display='none'; });
                                document.addEventListener('click', function(e){ if(!tooltip.contains(e.target) && e.target !== select) tooltip.style.display='none'; });
                            })();
                        </script>
                    <?php endif; ?>
            </label>

            <label>
                <span><?= t('admin_lines_label_distance') ?></span>
                <?php
                    // If the line has an explicit distance we display it in the value; otherwise show the computed suggestion as placeholder.
                    $distance_placeholder = $line['distance'] !== '' ? '' : ($placeholder_distance !== null ? $placeholder_distance : 'e.g. 123');
                ?>
                <input name="distance" value="<?= htmlspecialchars($line['distance']) ?>" class="fleet-filter-input input-120" placeholder="<?= htmlspecialchars($distance_placeholder) ?>">
            </label>

            <?php if (!$edit_mode): ?>
            <label class="admin-lines-checkbox-label">
                <input type="checkbox" name="create_return" value="1" class="admin-lines-checkbox">
                <span><?= t('admin_lines_label_create_return') ?></span>
            </label>
            <?php endif; ?>

            <div class="admin-lines-form-actions">
                 <div>
                     <?php if ($edit_mode): ?>
                         <input type="hidden" name="id" value="<?= htmlspecialchars($line['id']) ?>">
                         <button class="btn" type="submit" name="action" value="update"><?= t('admin_lines_btn_update') ?></button>
                         <a href="admin_lignes_regulieres.php" class="btn admin-lines-btn-cancel"><?= t('admin_lines_btn_cancel') ?></a>
                     <?php else: ?>
                         <button class="btn" type="submit" name="action" value="add"><?= t('admin_lines_btn_add') ?></button>
                     <?php endif; ?>
                 </div>
             </div>
             </form>
        <script>
            (function () {
                // Update placeholder suggestion when ICAO inputs change
                const depInput = document.querySelector('input[name="icao_dep"]');
                const arrInput = document.querySelector('input[name="icao_arr"]');
                const distInput = document.querySelector('input[name="distance"]');

                if (!depInput || !arrInput || !distInput) return;

                let timer = null;
                function scheduleCompute() {
                    clearTimeout(timer);
                    timer = setTimeout(computePlaceholder, 400);
                }

                function computePlaceholder() {
                    const dep = depInput.value.trim().toUpperCase();
                    const arr = arrInput.value.trim().toUpperCase();
                    if (dep.length === 0 || arr.length === 0) return;
                    // If user already entered a distance value we don't override it, but we can update the placeholder
                    distInput.placeholder = 'Calcul...';
                    fetch('admin_calc_distance.php?dep=' + encodeURIComponent(dep) + '&arr=' + encodeURIComponent(arr), { credentials: 'same-origin' })
                        .then(r => r.json())
                        .then(data => {
                            if (data && data.ok && data.distance) {
                                // only set placeholder if input value is empty (user can edit value anytime)
                                if (!distInput.value) distInput.placeholder = data.distance;
                            } else {
                                if (!distInput.value) distInput.placeholder = 'e.g. 123';
                            }
                        })
                        .catch(() => {
                            if (!distInput.value) distInput.placeholder = 'e.g. 123';
                        });
                }

                depInput.addEventListener('input', scheduleCompute);
                arrInput.addEventListener('input', scheduleCompute);
            })();
        </script>
         </div>
     </section>

    <section>
        <h3><?= t('admin_lines_list_title') ?></h3>

        <!-- Filters placed under the table title, single-line (inputs inline with buttons) -->
        <form method="get" class="filters-form admin-lines-filters-form">
            <label><?= t('admin_lines_filter_dep') ?>
                <input name="icao_dep" placeholder="<?= t('admin_lines_filter_dep_placeholder') ?>" value="<?= htmlspecialchars($filter_dep) ?>" aria-label="<?= t('admin_lines_filter_dep') ?>" class="fleet-filter-input input-160" oninput="this.value = this.value.toUpperCase();">
            </label>

            <label><?= t('admin_lines_filter_arr') ?>
                <input name="icao_arr" placeholder="<?= t('admin_lines_filter_arr_placeholder') ?>" value="<?= htmlspecialchars($filter_arr) ?>" aria-label="<?= t('admin_lines_filter_arr') ?>" class="fleet-filter-input input-160" oninput="this.value = this.value.toUpperCase();">
            </label>

            <label><?= t('admin_lines_filter_type') ?>
                <select name="type_ligne" class="fleet-filter-select input-160">
                    <option value=""><?= t('admin_lines_filter_type_all') ?></option>
                    <?php foreach ($typeLignes as $t): ?>
                        <option value="<?= (int)$t['id'] ?>" <?= ($filter_type !== null && $filter_type === (int)$t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['label']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="admin-lines-form-actions">
                <button class="btn" type="submit"><?= t('admin_lines_btn_filter') ?></button>
                <!-- Keep the current visual style of the reset button exactly as-is (model for the site) -->
                <a href="admin_lignes_regulieres.php" class="btn admin-lines-btn-reset"><?= t('admin_lines_btn_reset') ?></a>
            </div>
        </form>

        <div>
            <table class="table-skywings admin-lines-table">
            <thead>
                <tr>
                    <th><?= t('admin_lines_table_icao_dep') ?></th>
                    <th><?= t('admin_lines_table_icao_arr') ?></th>
                    <th><?= t('admin_lines_table_distance') ?></th>
                    <th><?= t('admin_lines_table_type') ?></th>
                    <th><?= t('admin_lines_table_created') ?></th>
                    <th><?= t('admin_lines_table_updated') ?></th>
                    <th><?= t('admin_lines_table_actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lines as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['icao_dep']) ?></td>
                        <td><?= htmlspecialchars($r['icao_arr']) ?></td>
                        <td><?= is_null($r['distance']) ? '' : htmlspecialchars((int)$r['distance']) ?></td>
                        <td><?= htmlspecialchars($r['type_label'] ?? $r['type_ligne']) ?></td>
                    <td><?= $r['created_at'] ? htmlspecialchars(date('d/m/Y H:i:s', strtotime($r['created_at']))) : '-' ?></td>
                    <td><?= $r['updated_at'] ? htmlspecialchars(date('d/m/Y H:i:s', strtotime($r['updated_at']))) : '-' ?></td>
                        <td>
                            <a href="admin_lignes_regulieres.php?edit=<?= $r['id'] ?>"><?= t('admin_lines_link_edit') ?></a>
                            &nbsp;|&nbsp;
                            <a href="#" onclick="if(confirm('<?= t('admin_lines_confirm_delete') ?>')){ document.getElementById('delete-form-<?= $r['id'] ?>').submit(); } return false;"><?= t('admin_lines_link_delete') ?></a>
                            <form id="delete-form-<?= $r['id'] ?>" method="post" class="hidden">
                                <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        </div>
    </section>

</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
