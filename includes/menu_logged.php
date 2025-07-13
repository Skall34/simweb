<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


?>

<nav class="menu-logged">
    <a href="/index.php">Accueil</a>
    <a href="/pages/tableau_vols.php">Carnet de vol général</a>
    <a href="/pages/fleet.php">Flotte</a>
    <a href="/pages/fleet_type.php">Fleet Type</a>
    <a href="/pages/pilotes.php">Pilotes</a>
    <a href="/pages/flights.php">Mes vols</a>
    <a href="/pages/stats.php">Stats</a>
    <a href="/pages/finances.php">Finances</a>
    <a href="/pages/saisie_manuelle.php">Saisie Manuelle</a>
    <a href="/pages/mon_compte.php">Mon compte</a>
    
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
                    <a href="/admin/admin_fleet.php">Ajouter un Appareil</a>
                    <a href="/admin/admin_aeroport.php">Administration de la base des aéroports</a>
                    <a href="/admin/admin_missions.php">Administration des missions</a>
                    <a href="/admin/admin_gestion_pilotes.php">Administration des pilotes</a>
                </div>
                <?php if (isset($_SESSION['user']['callsign']) && $_SESSION['user']['callsign'] === 'SKY0707'): ?>
                    <a href="/admin/admin_sky0707.php" style="color: #c00; font-weight: bold; margin-left: 10px;">Admin SKY0707</a>
                <?php endif; ?>
            </div>
            <?php
        }
    }
    ?>
</nav>

<style>

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
</style>
