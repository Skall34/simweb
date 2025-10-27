<?php
include '../../includes/header.php';
include '../../includes/menu_logged.php';
?>
<main>
    <h1 style="text-align:center;color:#1a3552;margin-top:24px;margin-bottom:18px;">Vols réguliers</h1>
   
    <section style="max-width:1100px;margin:0 auto 32px auto;font-size:1.05em;line-height:1.6;background:#f7fbff;padding:24px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.06);">
        <h2 style="color:#1a3552;">Vols Réguliers</h2>
        <p> Les vols réguliers ont pour avantage de générer des revenus stables et prévisibles pour la compagnie aérienne. Ils profitent d'un coeficient multiplicateur de 3.</p><br>
        <p>Voici la carte interactive des lignes régulières. Vous pouvez utiliser les contrôles Google Maps pour zoomer et afficher les détails.</p>

        <!-- Responsive container for embedded Google My Maps -->
        <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:8px;border:1px solid rgba(0,0,0,0.06);margin-top:12px;">
            <iframe src="https://www.google.com/maps/d/u/0/embed?mid=1fYs3mM8W3nRfVHl78xp2w8st6hcK22w" width="100%" height="100%" style="position:absolute;top:0;left:0;border:0;" allowfullscreen="allowfullscreen"></iframe>
        </div>

        <p style="margin-top:12px;font-size:0.95em;color:#333;">Si l'intégration ne fonctionne pas, ouvrez la carte dans Google Maps :
            <a href="https://www.google.com/maps/d/u/0/viewer?mid=1fYs3mM8W3nRfVHl78xp2w8st6hcK22w" target="_blank" rel="noopener noreferrer">Ouvrir la carte des lignes régulières</a>
        </p>
    </section>
</main>
<?php include '../../includes/footer.php'; ?>
