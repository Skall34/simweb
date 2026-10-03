<?php
require_once __DIR__ . '/../../includes/require_login.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/menu_logged.php';
?>
<main>
    <?php afficherCoefficientMission(); ?>
    <h1 style="text-align:center;color:#1a3552;margin-top:24px;margin-bottom:18px;">Dc-3 World Tour</h1>
    <figure class="dc3-world-tour__photo">
        <img src="/assets/images/DC3.png" alt="Douglas DC-3 en vol au-dessus d'un aéroport">
    </figure>
    <section style="max-width:700px;margin:0 auto 32px auto;font-size:1.15em;line-height:1.6;background:#f7fbff;padding:24px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.06);">
        <h2 style="color:#1a3552;">Description de la mission</h2>
        <p>Bienvenue sur la mission <strong>Dc-3 World Tour</strong> !</p>
        <p>Partez à la découverte du monde aux commandes d'un avion mythique : le Douglas DC-3, également connu sous le nom de C-47. Préparez votre tour du monde et profitez de l'expérience des vols à bord de ces appareils vintage.</p>
        
        <!-- Ajoutez une carte Google Maps si nécessaire -->
        <!-- <div style="text-align:center;margin:18px 0;">
            <iframe src="https://www.google.com/maps/d/embed?mid=YOUR_MAP_ID" width="640" height="480"></iframe>
        </div> -->
    </section>
    <section style="max-width:700px;margin:0 auto 32px auto;background:#fff;padding:24px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.04);">
        <h2 style="color:#1a3552;">Informations complémentaires</h2>
        <p>Retrouvez les informations et les ressources de DC-3 Airways, une compagnie virtuelle consacrée aux vols en Douglas DC-3 et C-47 :</p>
        <p><a href="https://www.dc3airways.aeroworx.co.za/" target="_blank" rel="noopener noreferrer" style="color:#1a3552;font-weight:bold;text-decoration:underline;">DC-3 Airways – Virtual Airline flying Douglas DC-3/C-47 vintage airliners.</a></p>
    </section>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
