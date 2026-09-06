<?php
require_once __DIR__ . '/../../includes/require_login.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/menu_logged.php';
?>
<main>
    <?php afficherCoefficientMission(); ?>
    <h1 style="text-align:center;color:#1a3552;margin-top:24px;margin-bottom:18px;">Aéropostale</h1>
    <div style="display:flex;justify-content:center;margin-bottom:24px;gap:24px;flex-wrap:wrap;">
        <img src="/assets/images/aeropostale.jpg" alt="Vol libre monde" style="max-width:420px;width:100%;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
    </div>
    <section style="max-width:700px;margin:0 auto 32px auto;font-size:1.15em;line-height:1.6;background:#f7fbff;padding:24px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.06);">
        <h2 style="color:#1a3552;">Mission : Aéropostale</h2>
        <p>
            Revivez l'aventure que nos anciens ont vécus.<br><br>
        </p>
        <p>Partez pour un grand voyage sur les traces des pionniers de l’aviation, en suivant la légendaire route de l’Aéropostale.</p>
        <p>Depuis Toulouse, vous traverserez l’Espagne, le Maroc, le Sahara et l’Afrique de l’Ouest jusqu’à Dakar, comme l’ont fait les premiers aviateurs du courrier. </p>
        <p>Après la traversée de l’Atlantique, la seconde partie vous mènera de Natal, au Brésil, à travers la cordillère des Andes, jusqu’à Santiago du Chili.</p>
        <p>Une aventure exceptionnelle, entre défis techniques et paysages grandioses, pour revivre l’épopée de l’Aéropostale et rendre hommage à ses héros.</p>
    </section>
    <section>
        <h2 style="color:#1a3552;text-align:center;margin-bottom:16px;">La route de l'Aéropostale</h2>
        <p style="text-align:center;font-size:0.9em;color:#555;margin-bottom:24px;">(cliquez sur la carte pour l'ouvrir en grand)</p>
        <div style="display:flex;justify-content:center;">
            <iframe src="https://www.google.com/maps/d/embed?mid=1fJea97aqul-gysC0NyCZs2KWh3xPsS4&ehbc=2E312F&noprof=1" width="640" height="480" style="border:0;"></iframe>
        </div>
    </section>
    <section style="max-width:700px;margin:0 auto 32px auto;background:#fff;padding:24px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.04);">
        <h2 style="color:#1a3552;">Pour aller plus loin</h2>
        <ul style="list-style:disc inside; padding-left:20px; font-size:1.08em;">
            <li><a href="https://fr.wikipedia.org/wiki/Compagnie_g%C3%A9n%C3%A9rale_a%C3%A9ropostale" target="_blank" style="color:#1565c0;font-weight:600;text-decoration:underline;">Page wikipedia</a></li>
        </ul>
    </section>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
