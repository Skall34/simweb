
<?php
/*
-------------------------------------------------------------
 Script : flight_details_table.php
 Emplacement : includes/

 Description :
 Génère un tableau HTML formaté affichant les détails complets d'un vol.
 Reçoit les données en POST au format JSON et retourne du HTML structuré.

 Utilisation :
 - Appelé via POST avec un paramètre 'details' contenant un objet JSON.
 - Utilisé pour l'affichage des détails de vol dans les modals ou popups.
 - Valide les données reçues avant génération du tableau.

 Format attendu :
 - POST['details'] : JSON contenant les champs du vol (Date vol, Immat, Départ, etc.)

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['details'])) {
    http_response_code(400);
    echo "Données manquantes.";
    exit;
}

$details = json_decode($_POST['details'], true);

if (!is_array($details)) {
    http_response_code(400);
    echo "Format invalide.";
    exit;
}

ob_start();
?>


<table class="fd-table">
    <tr><td class="fd-label">Date vol</td><td class="fd-value"><?= htmlspecialchars($details["Date vol"] ?? '') ?></td></tr>
    <tr><td class="fd-label">Immat</td><td class="fd-value"><?= htmlspecialchars($details["Immat"] ?? '') ?></td></tr>
    <tr>
        <td class="fd-label">Aéroport</td>
        <td class="fd-value">
            <table>
                <tr><td class="fd-label">Départ</td><td><?= htmlspecialchars($details["Départ"] ?? '') ?></td></tr>
                <tr><td class="fd-label">Destination</td><td><?= htmlspecialchars($details["Destination"] ?? '') ?></td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="fd-label">Fuel</td>
        <td class="fd-value">
            <table>
                <tr><td class="fd-label">Départ</td><td><?= htmlspecialchars($details["Fuel départ"] ?? '') ?></td></tr>
                <tr><td class="fd-label">Arrivée</td><td><?= htmlspecialchars($details["Fuel arrivée"] ?? '') ?></td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td class="fd-label">Heures</td>
        <td class="fd-value">
            <table>
                <tr><td class="fd-label">Départ</td><td><?= htmlspecialchars($details["Heure départ"] ?? '') ?></td></tr>
                <tr><td class="fd-label">Arrivée</td><td><?= htmlspecialchars($details["Heure arrivée"] ?? '') ?></td></tr>
            </table>
        </td>
    </tr>
    <tr><td class="fd-label">Conso</td><td class="fd-value"><?= htmlspecialchars($details["Conso"] ?? '') ?></td></tr>
    <tr><td class="fd-label">Payload</td><td class="fd-value"><?= htmlspecialchars($details["Payload"] ?? '') ?></td></tr>
    <tr><td class="fd-label">Recette du vol</td><td class="fd-value"><?= htmlspecialchars($details["Recette du vol"] ?? '') ?></td></tr>
    <tr><td class="fd-label">Mission</td><td class="fd-value"><?= htmlspecialchars($details["Mission"] ?? '') ?></td></tr>
    <tr>
        <td class="fd-label">Pirep</td>
        <td class="fd-value"><?= htmlspecialchars($details["Pirep"] ?? '') ?></td>
    </tr>
</table>

<?php
echo ob_get_clean();
