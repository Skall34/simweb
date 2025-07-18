
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
    <h2>Parcourez les grands paysages des steppes en longeant la chaine des montagnes himalayennes.La Route de la soie est un réseau ancien de routes commerciales entre l'Asie et l'Europe, reliant la ville de Chang'an (actuelle Xi'an) en Chine à la ville de Constantinople (aujourd'hui Istanbul), en Turquie. Elle tire son nom de la plus précieuse marchandise qui y transitait : la soie.</h2>
    <h2>Carte de la mission : Route de la Soie</h2>
    <div style="max-width:900px;margin:2rem auto;">
        <iframe src="https://www.google.com/maps/d/u/0/embed?mid=1_4Rs-dTEiRvdWeqS6W7R-rw_hN62n4k&ehbc=2E312F" width="100%" height="600" style="border:1px solid #ccc;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);"></iframe>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
