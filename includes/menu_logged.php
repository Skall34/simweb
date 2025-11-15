
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../lang.php';


?>

<nav class="menu-logged">
    <a href="/index.php">🏠 <?= t('menu_home') ?></a>
    <a href="/pages/tableau_vols.php">📒 <?= t('menu_logbook') ?></a>
    <a href="/pages/flights.php">🛫 <?= t('menu_myflights') ?></a>
    <a href="/pages/fleet.php">✈️ <?= t('menu_fleet') ?></a>
    <a href="/pages/reserver_ligne.php">🧭 <?= t('menu_bookline') ?></a>
    <a href="/pages/stats.php">📊 <?= t('menu_stats') ?></a>
    <a href="/pages/finances.php">💶 <?= t('menu_finances') ?></a>
    <a href="/pages/mon_compte.php">👤 <?= t('menu_account') ?></a>
    <div class="menu-missions">
        <span class="missions-label">🗺️ <?= t('menu_missions') ?></span>
        <div class="submenu-missions">
            <?php
            require_once __DIR__ . '/db_connect.php';
            $missions = [];
            try {
                $stmtMissions = $pdo->query("SELECT libelle, Active FROM MISSIONS ORDER BY libelle ASC");
                $missions = $stmtMissions->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                echo '<span style="color:red;">' . t('menu_missions_error') . '</span>';
            }
            $missionLabels = [
                'NORMANDIE 80' => t('mission_normandie80'),
                'HYDRAVIONS' => t('mission_hydravions'),
                'CANNES 2024' => t('mission_cannes2024'),
                'RTESOIE' => t('mission_rtesoie'),
                'RTEGRECE' => t('mission_rtegrece'),
                'CARAIBES' => t('mission_caraibes'),
                'ESQUIMOS' => t('mission_esquimos'),
                'OPFRANCE' => t('mission_opfrance'),
                'OPLINER' => t('mission_opliner'),
                'OPPNG' => t('mission_oppng'),
                'VOLLIBRE' => t('mission_vollibre'),
                'Long/moyen courrier' => t('mission_longmoyen'),
            ];
            foreach ($missions as $missionRow) {
                $mission = $missionRow['libelle'];
                $isInactive = (isset($missionRow['Active']) && !$missionRow['Active']);
                if ($mission === 'Long/moyen courrier') {
                    $url = '/pages/missions/LONGSMOYENSCOURIERS.php';
                } else {
                    $url = '/pages/missions/' . urlencode($mission) . '.php';
                }
                $label = isset($missionLabels[$mission]) ? $missionLabels[$mission] : htmlspecialchars($mission);
                if ($isInactive) {
                    echo '<a href="#" style="color:#b0b0b0;pointer-events:none;cursor:default;background:#f6f6f6;">' . $label . '</a>';
                } else {
                    echo '<a href="' . $url . '">' . $label . '</a>';
                }
            }
            ?>
        </div>
    </div>

    <div class="menu-plus">
        <span>➕ <?= t('menu_plus') ?></span>
        <div class="submenu-plus">
            <a href="/pages/documentation.php">📖 <?= t('menu_documentation') ?></a>
            <a href="/pages/fleet_type.php">🛩️ <?= t('menu_fleettype') ?></a>
            <a href="/pages/pilotes.php">👨‍✈️ <?= t('menu_pilots') ?></a>
            <a href="/pages/simulation.php">🧮 <?= t('menu_simulation') ?></a>
            <a href="/pages/grades.php">🧑‍✈️ <?= t('menu_grades') ?></a>
            <a href="/pages/saisie_manuelle.php">📝 <?= t('menu_manualentry') ?></a>
        </div>
    </div>
    
    <?php
    if (isset($_SESSION['user']['callsign'])) {
        require_once __DIR__ . '/db_connect.php';
        $stmt = $pdo->prepare("SELECT admin FROM PILOTES WHERE callsign = :callsign");
        $stmt->execute(['callsign' => $_SESSION['user']['callsign']]);
        $isAdmin = $stmt->fetchColumn();
        $_SESSION['user']['isAdmin'] = $isAdmin;

        if ($isAdmin == 1) {
            ?>
            <div class="menu-admin">
                <span><?= t('menu_admin') ?></span>
                <div class="submenu-admin">
                    <a href="/admin/admin_fleet_type.php"><?= t('admin_fleettype') ?></a>
                    <a href="/admin/admin_flotte.php"><?= t('admin_fleet') ?></a>
                    <a href="/admin/admin_aeroport.php"><?= t('admin_airports') ?></a>
                    <a href="/admin/admin_missions.php"><?= t('admin_missions') ?></a>
                    <a href="/admin/admin_gestion_pilotes.php"><?= t('admin_pilots') ?></a>
                    <a href="/admin/admin_grades.php"><?= t('admin_grades') ?></a>
                    <a href="/admin/admin_variables.php"><?= t('admin_variables') ?></a>
                    <a href="/admin/admin_lignes_regulieres.php"><?= t('admin_lines') ?></a>
                    <a href="/admin/admin_message_accueil.php"><?= t('admin_welcomemsg') ?></a>
                </div>
                <?php if (isset($_SESSION['user']['callsign']) && in_array($_SESSION['user']['callsign'], ['SKY0707', 'SKY0034'])): ?>
                    <a href="/admin/admin_SuperAdminMenu.php" style="color: #c00; font-weight: bold; margin-left: 10px;"><?= t('admin_superadmin') ?></a>
                <?php endif; ?>
            </div>
            <?php
        }
    }
    ?>

</nav>
