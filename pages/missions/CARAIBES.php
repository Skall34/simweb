
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
    <h1 style="text-align:center;color:#1a3552;margin-top:24px;margin-bottom:18px;">Les Caraïbes</h1>
    <div style="display:flex;justify-content:center;margin-bottom:24px;">
        <img src="../../assets/images/caraibes_1.jpg" alt="Les Caraïbes" style="max-width:600px;width:100%;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
    </div>
    <section style="max-width:700px;margin:0 auto 24px auto;font-size:1.13em;line-height:1.6;background:#fafdff;padding:20px 28px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.05);">
        L'histoire des Caraïbes concerne les îles antillaises françaises de la Caraïbe situées dans la mer des Caraïbes : Martinique, les îles de Guadeloupe (Grande-Terre, Basse-Terre, Marie-Galante, la Désirade, les Saintes), Saint-Martin et Saint-Barthélemy, et les autres îles qui ont été ou non françaises dans les Caraïbes, comme Antigua, Bahamas, Barbade, Cuba, Dominique, République dominicaine, Guyana, Grenade, Haïti (Saint-Domingue), Jamaïque, Montserrat, Porto Rico, Saint-Christophe-et-Niévès, Sainte-Lucie, Saint-Vincent-et-les-Grenadines, Trinité-et-Tobago, Turques-et-Caïques ou les Îles Vierges britanniques. Pour ce voyage afin de réaliser des petits vols nous vous conseillons de prendre des petits avions qui vous permettront de découvrir les magnifiques paysages.
    </section>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
