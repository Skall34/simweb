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
            <div style="max-width: 900px; border: 1px solid #ccc; border-radius: 10px; padding: 1rem; background-color: #f9f9f9; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h2>Bienvenue sur <strong>SkyWings VA</strong></h2>                
                <p><strong>SkyWings VA</strong> est une compagnie aérienne virtuelle qui vous permet de suivre vos vols, de gérer votre flotte, et de participer à des missions variées dans un univers immersif.</p>
                <p>Une fois inscrit sur notre site, vous aurez accès à <b>SimAddon</b>, un logiciel qui va vous permettre de monitorer vos vols sur simulateur (XPlane ou FS2020, ou FS2024).</p>
                <p><b>SimAddon</b>, vous permettra, entre autre, d'envoyer votre rapport de vol à la fin de votre périple, dans la base de données du site.</p>
                <p>Il sera alors importé. Voici les étapes qu'il va suivre:</p>
                <ul style="margin-left: 2.2em;">
                    <li>Vérification de la validité du rapport de vol</li>
                    <li>Extraction des informations du vol (pilote, heure, aéroports, appareil, etc.)</li>
                    <li>Enregistrement des données dans la base de données</li>
                    <li>Met à jour le fret, la flotte, les finances, le carnet de vol, et l'usure des appareils.</li>
                    <li>Met à jour la balance commerciale.</li>
                    <li>Met à jour votre carnet de vol personnel</li>
                </ul>
                <p>Le site permet une gestion complète d'une flotte d'appareils, avec une gestion automatique de la maintenance.</p>
                <p>Il gère aussi un système de grades pour les pilotes, qui a une influence sur les salaires.</p>
            </div>

            <!-- Image + Vols en cours à droite -->
            <div style="max-width: 600px; display: flex; flex-direction: column; gap: 1.5rem;">
                <img src="assets/images/accueil.jpg" alt="SkyWings" style="width: 100%; height: auto; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
                <section style="margin: 0; max-width: 100%;">
                    <h2 style="margin-top: 0;">Vols en cours</h2>
                    <div id="live-flights-container">
                        <p>Chargement des vols en cours...</p>
                    </div>
                </section>
            </div>
        </div>
        <?php
    } else {
        // Message de bienvenue personnalisé
        $callsign = isset($_SESSION['user']['callsign']) ? htmlspecialchars($_SESSION['user']['callsign']) : '';
        if ($callsign) {
            echo '<div style="font-size:1.25em;font-weight:bold;color:#2a4d7a;margin-bottom:22px;">Bonjour ' . $callsign . ' 👋</div>';
        }
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


        <div style="display: flex; flex-direction: row; gap: 2rem; align-items: flex-start; margin-bottom: 2rem;">
            <div style="flex: 1; min-width: 320px;">
                <section style="margin: 0; max-width: 100%;">
                    <h2 style="margin-top: 0;">Vols en cours</h2>
                    <div id="live-flights-container">
                        <p>Chargement des vols en cours...</p>
                    </div>
                </section>
            </div>
            <div style="flex: 0 0 320px; max-width: 380px;">
                <img src="assets/images/PDF.jpg" alt="SkyWings" style="width: 100%; height: auto; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
            </div>
        </div>

    <!-- Espace vertical avant le tableau -->
    <div style="height: 2.5rem;"></div>

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


    <?php
    // Affichage de la balance commerciale sous le tableau
    // Fonction de formatage (copiée de finances.php)
    function format_chiffre($valeur) {
        if ($valeur === null) return '0';
        if (floor($valeur) == $valeur) {
            return number_format($valeur, 0, ',', ' ');
        } else {
            return number_format($valeur, 2, ',', ' ');
        }
    }
    // Récupère la balance financière depuis la table BALANCE_COMMERCIALE
    try {
        $sqlBalance = "SELECT balance_actuelle FROM BALANCE_COMMERCIALE";
        $stmtBalance = $pdo->query($sqlBalance);
        $balance = $stmtBalance->fetchColumn();
    } catch (PDOException $e) {
        $balance = null;
    }
    ?>

    <div style="margin: 32px 0 0 0; font-size: 1.2em; color: #2c3e50;">
        <strong>Balance commerciale de la compagnie :</strong> <?= format_chiffre($balance) ?> €
    </div>

    <?php } ?>

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

    // Rafraîchissement toutes les 20 secondes
    setInterval(chargerVolsEnCours, 20000);
    </script>

</main>

<?php include("includes/footer.php"); ?>
</body>
</html>
