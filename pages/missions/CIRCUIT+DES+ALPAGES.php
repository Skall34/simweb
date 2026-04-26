<?php
require_once __DIR__ . '/../../includes/require_login.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/menu_logged.php';
?>
<main>
    <h1 style="text-align:center;color:#1a3552;margin-top:24px;margin-bottom:18px;">Circuit Des Alpages</h1>
    <div style="display:flex;justify-content:center;margin-bottom:24px;">
        <!-- Ajoutez une image ici si nécessaire -->
        <img src="/assets/images/circuit_alpages.png" alt="Circuit Des Alpages" style="max-width:600px;width:100%;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
    </div>
    <section style="max-width:700px;margin:0 auto 32px auto;font-size:1.15em;line-height:1.6;background:#f7fbff;padding:24px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.06);">
        <h2 style="color:#1a3552;">Description de la mission</h2>
        <p>Bienvenue sur la mission <strong>Circuit Des Alpages</strong> !</p>
        <p>Partez pour une aventure aérienne inoubliable à travers les Alpes du Sud ! Cette balade en hélicoptère vous emmènera depuis Aix-les-Bains, 
            station thermale emblématique de Savoie, pour une immersion totale dans les paysages de montagne. 
            Vous survolerez d'abord les massifs préalpins avant de rejoindre le majestueux Parc Régional du Verdon, connu pour ses gorges spectaculaires et ses eaux turquoise.
            La dernière étape vous conduira jusqu'à Barcelonette, charmante ville des Alpes-de-Haute-Provence, nichée au cœur d'un vallon préservé.
            Ce circuit vous offre une diversité de paysages exceptionnelle : lacs alpins, sommets enneigés, prairies verdoyantes et villages perchés.
            Un vol idéal pour découvrir la région en toute tranquilité !</p>
        <code>pages/missions/CIRCUIT+DES+ALPAGES.php</code></p>
        
        <!-- Ajoutez une carte Google Maps si nécessaire -->
        <div style="text-align:center;margin:18px 0;">
            <iframe src="https://www.google.com/maps/d/embed?mid=1hzf6AWy7kbru-0O9EeaMoiCoRGi7kIY&ehbc=2E312F&noprof=1" width="640" height="480"></iframe>
        </div> 
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
