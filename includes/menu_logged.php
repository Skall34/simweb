<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


?>

<nav class="menu-logged">
    <a href="/index.php">🏠 Accueil</a>
    <a href="/pages/tableau_vols.php">📒 Carnet de vol</a>
    <a href="/pages/fleet.php">✈️ Flotte</a>
    <a href="/pages/flights.php">🛫 Mes vols</a>
    <a href="/pages/stats.php">📊 Stats</a>
    <a href="/pages/finances.php">💶 Finances</a>
    <div class="menu-missions">
        <span class="missions-label">🗺️ Missions</span>
        <div class="submenu-missions">
            <?php
            require_once __DIR__ . '/db_connect.php';
            $missions = [];
            try {
                $stmtMissions = $pdo->query("SELECT libelle, Active FROM MISSIONS ORDER BY libelle ASC");
                $missions = $stmtMissions->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                echo '<span style="color:red;">Erreur chargement missions</span>';
            }
            foreach ($missions as $missionRow) {
                $mission = $missionRow['libelle'];
                $isInactive = (isset($missionRow['Active']) && !$missionRow['Active']);
                // Tableau de correspondance pour les noms jolis
                $missionLabels = [
                    'NORMANDIE 80' => 'Normandie 80',
                    'HYDRAVIONS' => 'Hydravions',
                    'CANNES 2024' => 'Cannes 2024',
                    'RTESOIE' => 'Route de la soie',
                    'RTEGRECE' => 'Grèce',
                    'CARAIBES' => 'Caraïbes',
                    'ESQUIMOS' => 'Esquimos',
                    'OPFRANCE' => 'OP France',
                    'OPLINER' => 'OP Liner',
                    'OPPNG' => 'OP Papouasie',
                    'VOLLIBRE' => 'Vol libre',
                    'Long/moyen courrier' => 'Long/moyen courrier',
                    // Ajouter d'autres exceptions ici si besoin
                ];
                // Correction du nom de fichier pour Long/moyen courrier
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
        <span>➕ Plus</span>
        <div class="submenu-plus">
            <a href="/pages/documentation.php">📖 Documentation</a>
            <a href="/pages/fleet_type.php">🛩️ Fleet Type</a>
            <a href="/pages/pilotes.php">👨‍✈️ Pilotes</a>
            <a href="/pages/simulation.php">🧮 Simulation</a>
            <a href="/pages/grades.php">🧑‍✈️ Grades</a>
            <a href="/pages/mon_compte.php">👤 Mon Compte</a>
            <a href="/pages/saisie_manuelle.php">📝 Saisie Manuelle</a>
        </div>
    </div>
    
    <?php
    if (isset($_SESSION['user']['callsign'])) {
        require_once __DIR__ . '/db_connect.php';
        $stmt = $pdo->prepare("SELECT admin FROM PILOTES WHERE callsign = :callsign");
        $stmt->execute(['callsign' => $_SESSION['user']['callsign']]);
        $isAdmin = $stmt->fetchColumn();
        // Assure que $isAdmin est un entier, et mémorise-le dans la session
        $_SESSION['user']['isAdmin'] = $isAdmin;

        if ($isAdmin == 1) {
            ?>
            <div class="menu-admin">
                <span>Admin</span>
                <div class="submenu-admin">
                    <a href="/admin/admin_fleet_type.php">Ajouter un Fleet Type</a>
                    <a href="/admin/admin_fleet.php">Acheter un Appareil</a>
                    <a href="/admin/admin_vendre_appareil.php">Vendre un Appareil</a>
                    <a href="/admin/admin_aeroport.php">Administration de la base des aéroports</a>
                    <a href="/admin/admin_missions.php">Administration des missions</a>
                    <a href="/admin/admin_gestion_pilotes.php">Administration des pilotes</a>
                    <a href="/admin/admin_grades.php">Administration des grades</a>
                    <a href="/admin/admin_variables.php">Administration des variables</a>
                    <a href="/admin/admin_message_accueil.php">Message d'accueil</a>
                </div>
                <?php if (isset($_SESSION['user']['callsign']) && in_array($_SESSION['user']['callsign'], ['SKY0707', 'SKY0034'])): ?>
                    <a href="/admin/admin_SuperAdminMenu.php" style="color: #c00; font-weight: bold; margin-left: 10px;">Super Admin</a>
                <?php endif; ?>
            </div>
            <?php
        }
    }
    ?>

</nav>
