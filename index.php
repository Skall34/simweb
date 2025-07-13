<?php
session_start();
include("includes/header.php");
require_once("includes/db_connect.php"); // Connexion PDO

// Inclusion du menu ici, juste après le header
if (!isset($_SESSION['user'])) {
    include("includes/menu_guest.php");
} else {
    include("includes/menu_logged.php");
}
?>

<main>
    <?php
    if (!isset($_SESSION['user'])) {
        ?>
        <div style="display: flex; justify-content: center; align-items: flex-start; gap: 2rem; margin-top: 2rem; flex-wrap: wrap;">
            <!-- Texte à gauche -->
            <div style="max-width: 600px; border: 1px solid #ccc; border-radius: 10px; padding: 1rem; background-color: #f9f9f9; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h2>Bienvenue sur <strong>SkyWings VA</strong></h2>
                <p>Veuillez vous connecter pour accéder à vos vols.</p>
                <p><strong>SkyWings VA</strong> est une compagnie aérienne virtuelle qui vous permet de suivre vos vols, de gérer votre flotte, et de participer à des missions variées dans un univers immersif.</p>
            </div>

            <!-- Image à droite -->
            <div style="max-width: 600px;">
                <img src="assets/images/accueil.jpg" alt="SkyWings" style="width: 100%; height: auto; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
            </div>
        </div>
        <?php
    } else {
        try {
            $sql = "
                SELECT 
                    cdvg.date_vol,
                    p.callsign,
                    f.immat,
                    cdvg.depart,
                    cdvg.destination,
                    cdvg.heure_depart,
                    cdvg.heure_arrivee
                FROM CARNET_DE_VOL_GENERAL cdvg
                LEFT JOIN PILOTES p ON cdvg.pilote_id = p.id
                LEFT JOIN FLOTTE f ON cdvg.appareil_id = f.id
                ORDER BY cdvg.date_vol DESC, cdvg.heure_depart DESC
                LIMIT 10
            ";
            $stmt = $pdo->query($sql);
            $vols = $stmt->fetchAll();
        } catch (PDOException $e) {
            echo "<p>Erreur lors de la récupération des vols : " . htmlspecialchars($e->getMessage()) . "</p>";
            $vols = [];
        }
    ?>

    <!-- Titre du tableau -->
    <h2>Les 10 derniers vols</h2>

    <!-- Tableau des vols -->
    <table class="table-skywings">
        <thead>
            <tr>
                <th>Date</th>
                <th>Callsign</th>
                <th>Appareil</th>
                <th>Départ</th>
                <th>Destination</th>
                <th>Durée</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vols as $vol): 
                $start = strtotime($vol['heure_depart']);
                $end = strtotime($vol['heure_arrivee']);
                $duration = $end && $start ? gmdate("H:i", $end - $start) : "N/A";
                $date_formatee = date("d-m-Y", strtotime($vol['date_vol']));
            ?>
                <tr>
                    <td><?= $date_formatee ?></td>
                    <td><?= htmlspecialchars($vol['callsign']) ?></td>
                    <td><?= htmlspecialchars($vol['immat']) ?></td>
                    <td><?= htmlspecialchars($vol['depart']) ?></td>
                    <td><?= htmlspecialchars($vol['destination']) ?></td>
                    <td><?= $duration ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php } ?>

    <!-- Section des vols en cours (live_flights) -->
    <section style="margin: 2rem auto; max-width: 1000px;">
        <h2>Vols en cours</h2>
        <div id="live-flights-container">
            <p>Chargement des vols en cours...</p>
        </div>
    </section>

    <script>
    function chargerVolsEnCours() {
        fetch('live_flights.php')
            .then(response => response.text())
            .then(html => {
                document.getElementById('live-flights-container').innerHTML = html;
            })
            .catch(error => {
                document.getElementById('live-flights-container').innerHTML = "<p>Erreur de chargement des vols en cours.</p>";
                console.error("Erreur AJAX :", error);
            });
    }

    // Chargement initial
    chargerVolsEnCours();

    // Rafraîchissement toutes les 30 secondes
    setInterval(chargerVolsEnCours, 30000);
    </script>

</main>

<?php include("includes/footer.php"); ?>
</body>
</html>
