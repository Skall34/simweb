<?php
include '../../includes/header.php';
include '../../includes/menu_logged.php';
?>
<main>
    <h1 style="text-align:center;color:#1a3552;margin-top:24px;margin-bottom:18px;">Vols réguliers</h1>
   
    <section style="max-width:1100px;margin:0 auto 32px auto;font-size:1.05em;line-height:1.6;background:#f7fbff;padding:26px 28px;border-radius:12px;box-shadow:0 6px 18px rgba(10,30,60,0.06);">
        <h2 style="color:#1a3552;margin-bottom:8px;">Vols réguliers — mode d'emploi</h2>

        <p style="margin-top:6px;color:#234;">Les vols réguliers génèrent des revenus stables et bénéficient d'un coefficient multiplicateur ×3.</p>

        <div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start;">
            <div>
                <h3 style="margin:8px 0 6px 0;color:#0b3460;font-size:1.05rem;">Comment ça marche</h3>
                <ol style="margin-left:18px;color:#123;">
                    <li>Allez dans <strong>Réserver un VR</strong> et choisissez la ligne qui vous intéresse. Utilisez les filtres si besoin.</li>
                    <li>Cliquez sur <em>Réserver</em> à droite ; choisissez ensuite un appareil disponible (hors maintenance / réservé).</li>
                    <li>La réservation est tenue pendant <strong>24 heures</strong> : l'appareil est bloqué pour les autres pilotes.</li>
                    <li>Sur la page d'accueil et dans <strong>Mon compte</strong> vous verrez l'état de votre réservation.</li>
                    <li>Pour effectuer le vol, positionnez votre simulateur sur l'aéroport de départ puis lancez l'ACARS : un popup vous proposera d'appliquer la réservation.</li>
                </ol>

                <h3 style="margin:12px 0 6px 0;color:#0b3460;font-size:1.05rem;">À quoi s'attendre</h3>
                <ul style="margin-left:18px;color:#123;">
                    <li>Si vous acceptez, le vol est chargé automatiquement : immatriculation et ICAO d'arrivée sont verrouillés.</li>
                    <li>La mission est définie automatiquement.</li>
                    <li>Une fois le vol terminé et le rapport soumis, la recette du vol est multipliée par <strong>3</strong>.</li>
                    <li>L'appareil est ensuite libéré pour d'autres pilotes.</li>
                </ul>
            </div>

            <aside style="background:#fff;padding:14px;border-radius:8px;border:1px solid rgba(0,0,0,0.06);box-shadow:0 2px 8px rgba(0,0,0,0.03);">
                <h4 style="margin-top:0;color:#0b3460;">Rappels</h4>
                <p style="margin:6px 0;color:#333;font-size:0.98em;">Si vous ne consommez pas la réservation, l'appareil est libéré automatiquement après 24 heures.</p>
                <p style="margin:6px 0;color:#333;font-size:0.98em;">Si vous refusez, l'ACARS fonctionne comme avant — aucun changement.</p>
            </aside>
        </div>

        <div style="margin-top:18px;color:#234;">Voici la carte interactive des lignes régulières — utilisez les contrôles Google Maps pour zoomer et afficher les détails.</div>

        <!-- Responsive container for embedded Google My Maps -->
        <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:10px;border:1px solid rgba(0,0,0,0.06);margin-top:12px;">
            <iframe src="https://www.google.com/maps/d/u/0/embed?mid=1fYs3mM8W3nRfVHl78xp2w8st6hcK22w" width="100%" height="100%" style="position:absolute;top:0;left:0;border:0;" allowfullscreen="allowfullscreen"></iframe>
        </div>

        <p style="margin-top:12px;font-size:0.95em;color:#333;">Si l'intégration ne fonctionne pas, ouvrez la carte dans Google Maps :
            <a href="https://www.google.com/maps/d/u/0/viewer?mid=1fYs3mM8W3nRfVHl78xp2w8st6hcK22w" target="_blank" rel="noopener noreferrer">Ouvrir la carte des lignes régulières</a>
        </p>
    </section>
</main>
<?php include '../../includes/footer.php'; ?>
