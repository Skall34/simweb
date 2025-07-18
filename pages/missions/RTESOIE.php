
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
    <h1 style="text-align:center;color:#1a3552;margin-top:24px;margin-bottom:18px;">La Route de la Soie</h1>
    <section style="max-width:700px;margin:0 auto 24px auto;font-size:1.13em;line-height:1.6;background:#fafdff;padding:20px 28px;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.05);">
        Parcourez les grands paysages des steppes en longeant la chaîne des montagnes himalayennes. La Route de la soie est un réseau ancien de routes commerciales entre l'Asie et l'Europe, reliant la ville de Chang'an (actuelle Xi'an) en Chine à la ville de Constantinople (aujourd'hui Istanbul), en Turquie. Elle tire son nom de la plus précieuse marchandise qui y transitait : la soie.
    </section>
    <div style="display:flex;justify-content:center;gap:18px;flex-wrap:wrap;margin-bottom:28px;">
        <img src="../../assets/images/route_soie_1.jpg" alt="Route de la soie 1" style="max-width:220px;max-height:220px;width:auto;height:auto;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
        <img src="../../assets/images/route_soie_2.jpg" alt="Route de la soie 2" style="max-width:220px;max-height:220px;width:auto;height:auto;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
        <img src="../../assets/images/route_soie_tableau_etapes.jpg" alt="Route de la soie 3" style="max-width:220px;max-height:220px;width:auto;height:auto;border-radius:10px;box-shadow:0 2px 12px rgba(0,0,0,0.08);">
    </div>
    <h2 style="text-align:center;margin-bottom:18px;">Carte de la mission : Route de la Soie</h2>
    <div style="max-width:900px;margin:2rem auto;">
        <iframe src="https://www.google.com/maps/d/u/0/embed?mid=1_4Rs-dTEiRvdWeqS6W7R-rw_hN62n4k&ehbc=2E312F" width="100%" height="600" style="border:1px solid #ccc;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);"></iframe>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
