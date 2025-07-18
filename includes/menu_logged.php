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

    <div class="menu-missions">
        <span class="missions-label">🗺️ Missions</span>
        <div class="submenu-missions">
            <?php
            require_once __DIR__ . '/db_connect.php';
            $missions = [];
            try {
                $stmtMissions = $pdo->query("SELECT libelle FROM MISSIONS ORDER BY libelle ASC");
                $missions = $stmtMissions->fetchAll(PDO::FETCH_COLUMN);
            } catch (PDOException $e) {
                echo '<span style="color:red;">Erreur chargement missions</span>';
            }
            foreach ($missions as $mission) {
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
                    // Ajouter d'autres exceptions ici si besoin
                ];
                $url = '/pages/missions/' . urlencode($mission) . '.php';
                $label = isset($missionLabels[$mission]) ? $missionLabels[$mission] : htmlspecialchars($mission);
                echo '<a href="' . $url . '">' . $label . '</a>';
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
            <a href="/pages/stats.php">📊 Stats</a>
            <a href="/pages/finances.php">💶 Finances</a>
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
                </div>
                <?php if (isset($_SESSION['user']['callsign']) && in_array($_SESSION['user']['callsign'], ['SKY0707', 'SKY3434'])): ?>
                    <a href="/admin/admin_SuperAdminMenu.php" style="color: #c00; font-weight: bold; margin-left: 10px;">Super Admin</a>
                <?php endif; ?>
            </div>
            <?php
        }
    }
    ?>

</nav>

<style>

/* Sous-menu Plus */
.menu-plus {
    position: relative;
    margin-left: 10px;
    color: white;
    display: flex;
    align-items: center;
}
.menu-plus span {
    cursor: pointer;
    font-weight: bold;
    font-size: 1rem;
    color: white;
    display: inline-block;
}
.submenu-plus {
    display: none;
    position: absolute;
    background-color: #fff;
    border: 1px solid #ccc;
    z-index: 100;
    padding: 5px;
    min-width: 180px;
    color: blue !important;
    font-size: 0.85rem;
    top: 100%;
    left: 0;
}
.menu-plus:hover .submenu-plus {
    display: block;
}
.submenu-plus a {
    display: block;
    padding: 5px;
    text-decoration: none;
    color: blue !important;
}
.submenu-plus a:hover {
    background-color: blue;
    color: white !important;
}

.menu-logged {
    /* Pour que les liens soient bien alignés avec flexbox */
    display: flex;
    align-items: center;
    gap: 10px; /* espace entre les items */
}

.menu-admin {
    position: relative;
    margin-left: 10px;
    color: white;
    display: flex;          /* flex container */
    align-items: center;    /* centrage vertical */
}

.menu-admin span {
    cursor: pointer;
    font-weight: bold;
    font-size: 1rem;
    color: white;
    display: inline-block;
}

/* Sous-menu */
.submenu-admin {
    display: none;
    position: absolute;
    background-color: #fff;
    border: 1px solid #ccc;
    z-index: 100;
    padding: 5px;
    min-width: 180px;
    color: blue !important;
    font-size: 0.85rem; /* police plus petite dans le sous-menu */
    top: 100%; /* positionne le sous-menu juste en dessous de son parent */
    left: 0;
}

/* Affiche le sous-menu au survol */
.menu-admin:hover .submenu-admin {
    display: block;
}

/* liens du sous-menu */
.submenu-admin a {
    display: block;
    padding: 5px;
    text-decoration: none;
    color: blue !important; /* forcer la couleur */
}

/* hover lien sous-menu */
.submenu-admin a:hover {
    background-color: blue;
    color: white !important; /* texte blanc au hover */
}
/* Sous-menu Missions */
.menu-missions {
    position: relative;
    margin-left: 10px;
    color: white;
    display: flex;
    align-items: center;
}
.missions-label {
    cursor: pointer;
    font-weight: bold;
    font-size: 1rem;
    color: white;
    display: inline-block;
    background: transparent;
    border-radius: 0;
    box-shadow: none;
    padding: 0;
    transition: color 0.2s;
}
.menu-missions:hover .missions-label,
.menu-missions:focus-within .missions-label,
.missions-label:hover {
    color: #cce0ff;
}
.submenu-missions {
    display: none;
    position: absolute;
    background-color: #fff;
    border: 1px solid #ccc;
    z-index: 100;
    padding: 5px;
    min-width: 180px;
    color: blue !important;
    font-size: 0.85rem;
    top: 100%;
    left: 0;
}
.menu-missions:hover .submenu-missions {
    display: block;
}
.submenu-missions a {
    display: block;
    padding: 5px;
    text-decoration: none;
    color: blue !important;
}
.submenu-missions a:hover {
    background-color: blue;
    color: white !important;
}
</style>
