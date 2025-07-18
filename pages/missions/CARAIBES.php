
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
    <h2>L'histoire des Caraïbes concerne les îles antillaises françaises de la Caraïbe situées dans la mer des Caraïbes : Martinique, les îles de Guadeloupe (Grande-Terre, Basse-Terre, Marie-Galante, la Désirade, les Saintes), Saint-Martin et Saint-Barthélemy, et les autres îles qui ont été ou non françaises dans les Caraïbes, comme Antigua, Bahamas, Barbade, Cuba, Dominique, République dominicaine, Guyana, Grenade, Haïti (Saint-Domingue), Jamaïque, Montserrat, Porto Rico, Saint-Christophe-et-Niévès, Sainte-Lucie, Saint-Vincent-et-les-Grenadines, Trinité-et-Tobago, Turques-et-Caïques ou les Îles Vierges britanniques. Pour ce voyage afin de réaliser des petits vols  nous vous conseillons de prendre des  petits avions qui vous permettront de decouvrir les magnifiques paysages. </h2>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
