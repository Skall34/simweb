
<?php
// filepath: includes/flight_detail_table.php

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
<table style="width:100%;border=1;border-collapse:collapse;">
    <tr>
        <td>
            <table>
                <tr><td style="font-weight:bold;padding:4px 8px;color:#0d47a1;">Date vol</td><td style="padding:4px 8px;"><?= htmlspecialchars($details["Date vol"] ?? '') ?></td></tr>
                <tr><td style="font-weight:bold;padding:4px 8px;color:#0d47a1;">Immat</td><td style="padding:4px 8px;"><?= htmlspecialchars($details["Immat"] ?? '') ?></td></tr>
                <tr>
                    <td style="font-weight:bold;padding:4px 8px;color:#0d47a1;">Aéroport</td>
                    <td style="padding:4px 8px;">
                        <table>
                            <tr><td style="font-weight:bold;padding:4px 8px;color:#0d47a1;">Départ</td><td><?= htmlspecialchars($details["Départ"] ?? '') ?></td></tr>
                            <tr><td style="font-weight:bold;padding:4px 8px;color:#0d47a1;">Destination</td><td><?= htmlspecialchars($details["Destination"] ?? '') ?></td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;padding:4px 8px;color:#0d47a1;">Fuel</td>
                    <td style="padding:4px 8px;">
                        <table>
                            <tr><td style="font-weight:bold;padding:4px 8px;color:#0d47a1;">Départ</td><td><?= htmlspecialchars($details["Fuel départ"] ?? '') ?></td></tr>
                            <tr><td style="font-weight:bold;padding:4px 8px;color:#0d47a1;">Arrivée</td><td><?= htmlspecialchars($details["Fuel arrivée"] ?? '') ?></td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight:bold;padding:4px 8px;color:#0d47a1;">Heures</td>
                    <td style="padding:4px 8px;">
                        <table>
                            <tr><td style="font-weight:bold;padding:4px 8px;color:#0d47a1;">Départ</td><td><?= htmlspecialchars($details["Heure départ"] ?? '') ?></td></tr>
                            <tr><td style="font-weight:bold;padding:4px 8px;color:#0d47a1;">Arrivée</td><td><?= htmlspecialchars($details["Heure arrivée"] ?? '') ?></td></tr>
                        </table>
                    </td>
                </tr>
                <tr><td style="font-weight:bold;padding:4px 8px;color:#0d47a1;">Conso</td><td style="padding:4px 8px;"><?= htmlspecialchars($details["Conso"] ?? '') ?></td></tr>
                <tr><td style="font-weight:bold;padding:4px 8px;color:#0d47a1;">Payload</td><td style="padding:4px 8px;"><?= htmlspecialchars($details["Payload"] ?? '') ?></td></tr>
                <tr><td style="font-weight:bold;padding:4px 8px;color:#0d47a1;">Recette du vol</td><td style="padding:4px 8px;"><?= htmlspecialchars($details["Recette du vol"] ?? '') ?></td></tr>
                <tr><td style="font-weight:bold;padding:4px 8px;color:#0d47a1;">Mission</td><td style="padding:4px 8px;"><?= htmlspecialchars($details["Mission"] ?? '') ?></td></tr>
            </table>
        </td>
        <td><div style="width: 100%; height: 400px;">map placeholder</div></td>
    </tr>
    <tr>
        <td style="font-weight:bold;padding:4px 8px;color:#0d47a1;">Pirep</td>
        <td style="padding:4px 8px;"><?= htmlspecialchars($details["Pirep"] ?? '') ?></td>
    </tr>
</table>
<?php
echo ob_get_clean();
