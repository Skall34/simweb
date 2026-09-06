<?php
require_once __DIR__ . '/../../includes/require_login.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/menu_logged.php';
?>
<main>
    <?php afficherCoefficientMission(); ?>
    <h1 style="text-align:center;color:#1a3552;margin-top:24px;margin-bottom:18px;">✈️ Vols Loisir</h1>

    <section style="max-width:700px;margin:0 auto 32px auto;font-size:1.15em;line-height:1.6;background:#f7fbff;padding:24px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.06);">
        <h2 style="color:#1a3552;">À propos de cette mission</h2>
        <p>La mission <strong>Loisir</strong> est dédiée aux vols de plaisance : balades, découvertes, vols touristiques ou tout simplement le plaisir de voler sans contrainte commerciale.</p>
        <p>Contrairement aux missions de transport de passagers ou de fret, les vols loisir ne génèrent pas de revenus pour la compagnie. C'est normal : vous volez pour le plaisir, pas pour le business !</p>
    </section>

    <section style="max-width:700px;margin:0 auto 32px auto;background:#fff3cd;padding:24px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.04);border-left:4px solid #ffc107;">
        <h2 style="color:#856404;margin-top:0;">⚠️ Impact financier</h2>
        <p>Cela signifie que les vols loisir <strong>coûtent de l'argent</strong> à la compagnie. Le revenu net sera généralement négatif car :</p>
        <ul style="margin-left:20px;">
            <li>Aucun passager ni fret payant à bord</li>
            <li>Consommation de carburant à votre charge</li>
            <li>Usure de l'appareil sans contrepartie commerciale</li>
        </ul>
        <p style="margin-bottom:0;font-style:italic;">💡 Utilisez cette mission avec parcimonie si la balance de la compagnie est fragile !</p>
    </section>

    <section style="max-width:700px;margin:0 auto 32px auto;background:#fff;padding:24px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.04);">
        <h2 style="color:#1a3552;">Quand utiliser cette mission ?</h2>
        <ul style="list-style:disc inside; padding-left:20px; font-size:1.08em; line-height:1.8;">
            <li>Vol de découverte d'une région ou d'un appareil</li>
            <li>Entraînement aux procédures (IFR, approches, etc.)</li>
            <li>Convoyage d'un avion vers une autre base</li>
            <li>Vol touristique ou balade photo</li>
            <li>Tout vol sans objectif commercial</li>
        </ul>
    </section>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

