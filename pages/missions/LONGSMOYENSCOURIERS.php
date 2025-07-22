
<?php
session_start();
require_once __DIR__ . '/../../includes/db_connect.php';
include __DIR__ . '/../../includes/header.php';
if (!isset($_SESSION['user'])) {
    include __DIR__ . '/../../includes/menu_guest.php';
} else {
    include __DIR__ . '/../../includes/menu_logged.php';
}
?>
<main>
    <h1 style="text-align:center;color:#1a3552;margin-top:24px;margin-bottom:18px;">Opération Liner</h1>
    <div style="display:flex;justify-content:center;margin-bottom:24px;gap:24px;flex-wrap:wrap;">
        <img src="/assets/images/OPLINER_1.jpg" alt="Liner international" style="max-width:420px;width:100%;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
        <img src="/assets/images/OPLINER_2.jpg" alt="Carte du monde" style="max-width:820px;width:100%;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
    </div>
    <section style="max-width:700px;margin:0 auto 32px auto;font-size:1.15em;line-height:1.6;background:#f7fbff;padding:24px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.06);">
        <h2 style="color:#1a3552;">Mission : Transport international</h2>
        <p>
            Transportez du fret ou des passagers entre n'importe quels aéroports du monde, avec l'appareil de votre choix.<br>
            Les recettes de chaque vol dépendent du poids transporté, de la durée du vol et de la qualité de votre pilotage.<br><br>
            <b>Objectif :</b> explorer de nouveaux horizons, relier des continents et maximiser vos gains !<br>
            Tous les types d'appareils sont acceptés, du petit jet au gros liner intercontinental.</p>
        <ul style="list-style:disc inside; padding-left:20px; font-size:1.08em;">
            <li>Départ et arrivée : tout aéroport du monde</li>
            <li>Type de vol : fret ou passagers</li>
            <li>Appareil libre</li>
            <li>Recette calculée selon poids, durée et qualité du vol</li>
        </ul>
    </section>
    <section style="max-width:700px;margin:0 auto 32px auto;background:#fff;padding:24px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.04);">
        <h2 style="color:#1a3552;">Pour aller plus loin</h2>
        <p>Quelques ressources utiles :</p>
        <ul style="list-style:disc inside; padding-left:20px; font-size:1.08em;">
            <li><a href="https://en.wikipedia.org/wiki/List_of_airports_by_country" target="_blank" style="color:#1565c0;font-weight:600;text-decoration:underline;">Liste des aéroports mondiaux</a></li>
        </ul>
    </section>
</main>
<?php include __DIR__ . '/../../includes/footer.php'; ?>

    
