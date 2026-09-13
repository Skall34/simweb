<?php
require_once __DIR__ . '/../../includes/require_login.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/menu_logged.php';
?>
<main>
    <?php afficherCoefficientMission(); ?>
    <h1 style="text-align:center;color:#1a3552;margin-top:24px;margin-bottom:18px;">Mission UNESCO</h1>
    <div style="display:flex;justify-content:center;margin-bottom:24px;">
        <img src="/assets/images/UNESCO.png" alt="Logo de l'UNESCO" style="max-width:420px;width:100%;height:auto;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
    </div>
    <section style="max-width:700px;margin:0 auto 32px auto;font-size:1.15em;line-height:1.6;background:#f7fbff;padding:24px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.06);">
        <h2 style="color:#1a3552;">Les trésors du patrimoine mondial</h2>
        <p>
            Skywings a été missionnée par l'UNESCO pour réaliser des photographies aériennes des plus beaux sites culturels et naturels de la planète.
        </p>
        <p>
            La mission est inaugurée avec le <strong>Machu Picchu</strong>, cité inca perchée dans les Andes péruviennes. À vous de choisir le meilleur angle, la bonne lumière et l'altitude idéale pour révéler chaque site sans le survoler inutilement.
        </p>
        <p><strong>Objectif :</strong> constituer un album mondial du patrimoine, étape après étape, en respectant les zones habitées et les consignes locales de survol.</p>
    </section>
    <section style="max-width:700px;margin:0 auto 32px auto;background:#fff;padding:24px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.04);">
        <h2 style="color:#1a3552;">Idées de sites à photographier</h2>
        <p>Voici quelques étapes emblématiques pour poursuivre la collection :</p>
        <ul style="list-style:disc inside; padding-left:20px; font-size:1.08em;">
            <li><strong>Machu Picchu</strong> - Pérou : la cité inca dans son écrin montagneux.</li>
            <li><strong>Chichén Itzá</strong> - Mexique : la pyramide de Kukulcán et l'ancienne cité maya.</li>
            <li><strong>La cité de Petra</strong> - Jordanie : le Khazneh sculpté dans la roche rose.</li>
            <li><strong>Le Taj Mahal</strong> - Inde : le mausolée de marbre au bord de la Yamuna.</li>
            <li><strong>La Grande Muraille</strong> - Chine : ses remparts serpentant sur les crêtes.</li>
            <li><strong>Angkor</strong> - Cambodge : les tours et les bassins du temple d'Angkor Wat.</li>
            <li><strong>Le delta de l'Okavango</strong> - Botswana : un paysage naturel de chenaux et d'îlots.</li>
            <li><strong>Le parc national de Yellowstone</strong> - États-Unis : geysers, lacs et canyons multicolores.</li>
            <li><strong>La Grande Barrière de corail</strong> - Australie : récifs et lagons vus depuis le ciel.</li>
            <li><strong>Le parc national de Rapa Nui</strong> - Chili : les moaï face à l'océan Pacifique.</li>
            <li><strong>Le centre historique de Rome</strong> - Italie : le Colisée, les forums et les toits de la ville éternelle.</li>
            <li><strong>Mont-Saint-Michel</strong> - France : l'abbaye entourée par les marées.</li>
        </ul>
    </section>
    <section style="max-width:700px;margin:0 auto 32px auto;font-size:1.1em;line-height:1.6;background:#f7fbff;padding:24px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.06);">
        <h2 style="color:#1a3552;">Conseils de reportage</h2>
        <ul style="list-style:disc inside; padding-left:20px;">
            <li>Privilégiez les petits appareils maniables pour cadrer les sites avec précision.</li>
            <li>Variez les altitudes et les heures de la journée pour faire ressortir reliefs, ombres et couleurs.</li>
            <li>Pour les sites isolés, préparez votre navigation et votre autonomie avant le départ.</li>
            <li>Une photographie réussie montre le site, mais aussi le paysage qui raconte son histoire.</li>
        </ul>
    </section>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
