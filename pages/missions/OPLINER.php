
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
    <h2>Parcourez l'Europe et une petite partie de l'Afrique du nord à partir de vos Liners de façon aléatoire et livrez dans chaque aéroport le fret que vous transportez.</h2>
</main>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

 	
