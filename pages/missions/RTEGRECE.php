
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
    <h2>Parcourez la Grèce à partir de petits avions ou bien d'hélicoptères sur des étapes courtes d'une moyenne de 50nm.Voyagez d'ile en ile pour découvrir des paysages extraordinaires.
D'une superficie de 131 957 km2 pour un peu moins de onze millions d'habitants, le pays partage des frontières terrestres avec l’Albanie, la Macédoine du Nord, la Bulgarie et la Turquie et des frontières maritimes avec Chypre, l'Albanie, l'Italie, la Libye, l'Égypte et la Turquie (cette dernière est la source du contentieux gréco-turc en mer Égée). La mer Adriatique à Corfou ( côte septentrionale uniquement), la mer Ionienne à l'ouest, la mer Méditerranée au sud (golfe de Laconie et Sud de l'arc égéen) et la mer Égée à l'est, encadrent le pays. Le cinquième de son territoire est constitué de plus de 9 000 îles et îlots, près de 200 étant habités. De plus, 80 % de son territoire est constitué de montagnes. La plus haute est le mont Olympe qui culmine à 2 917 m.</h2>
    <h2>Carte de la mission : Grèce</h2>
    <div style="max-width:900px;margin:2rem auto;">
        <iframe src="https://www.google.com/maps/d/u/0/embed?mid=1pyWFtMJILSw05_0ZjSMRRbNgD73U32OL&ehbc=2E312F" width="100%" height="600" style="border:1px solid #ccc;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.08);"></iframe>
    </div>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
