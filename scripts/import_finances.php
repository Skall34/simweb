<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require __DIR__ . '/../includes/db_connect.php';

// Utilitaire pour nettoyer les montants financiers
function clean_money($val) {
    if ($val === null || trim($val) === '') return 0.0;
    return floatval(str_replace(['€', ' ', ',', ' '], ['', '', '.', ''], $val));
}

// Convertir JJ/MM/AAAA en YYYY-MM-DD
function convert_date($val) {
    if (!$val) return null;
    $dt = DateTime::createFromFormat('d/m/Y', trim($val));
    return $dt ? $dt->format('Y-m-d') : null;
}

// Validation : doit contenir exactement 11 colonnes
function valider_colonnes($row, $ligneNum) {
    if (count($row) !== 11) {
        echo "⚠️ Ligne $ligneNum : $count colonnes trouvées, 11 attendues.<br>";
        return false;
    }
    return true;
}

$csvFile = 'finances2.csv'; // Fichier CSV
$handle = fopen($csvFile, 'r');
if (!$handle) {
    die("❌ Impossible d’ouvrir le fichier CSV");
}

$ligneNum = 0;
$insertCount = 0;

while (($row = fgetcsv($handle, 0, ";", '"', "\\")) !== false) {
    $ligneNum++;

    // Ligne vide ?
    if (count(array_filter($row)) === 0) continue;

    // Validation nombre de colonnes
    if (!valider_colonnes($row, $ligneNum)) continue;

    // Colonnes attendues (sans type, cout_horaire, prix_achat)
    [
        $immat,
        $date_achat,
        $recettes,
        $nb_annees_credit,
        $taux_percent,
        $remboursement,
        $traite_payee_cumulee,
        $reste_a_payer,
        $vente,
        $recette_vente,
        $date_vente
    ] = array_pad($row, 11, null);

    // ID avion à partir de l'immatriculation
    $lookupAvionStmt = $pdo->prepare("SELECT id FROM FLOTTE WHERE immat = :immat");
    $lookupAvionStmt->execute([':immat' => trim($immat)]);
    $avion = $lookupAvionStmt->fetch(PDO::FETCH_ASSOC);
    if (!$avion) {
        echo "❌ Ligne $ligneNum : avion inconnu avec immatriculation '$immat'<br>";
        continue;
    }
    $avion_id = $avion['id'];

    // Debug visuel de la requête
    echo "<pre>Ligne $ligneNum → INSERT INTO FINANCES (
        avion_id, date_achat, recettes,
        nb_annees_credit, taux_percent, remboursement,
        traite_payee_cumulee, reste_a_payer,
        vente, recette_vente, date_vente
    ) VALUES (
        {$avion_id},
        '" . convert_date($date_achat) . "',
        " . clean_money($recettes) . ",
        " . ($nb_annees_credit !== '' ? (int)$nb_annees_credit : 0) . ",
        " . ($taux_percent !== '' ? clean_money($taux_percent) : 0.0) . ",
        " . clean_money($remboursement) . ",
        " . clean_money($traite_payee_cumulee) . ",
        " . clean_money($reste_a_payer) . ",
        " . ($vente !== '' ? clean_money($vente) : 'NULL') . ",
        " . ($recette_vente !== '' ? clean_money($recette_vente) : 'NULL') . ",
        '" . convert_date($date_vente) . "'
    )</pre>";

    try {
        $stmt = $pdo->prepare("
            INSERT INTO FINANCES (
                avion_id, date_achat, recettes,
                nb_annees_credit, taux_percent, remboursement,
                traite_payee_cumulee, reste_a_payer,
                vente, recette_vente, date_vente
            ) VALUES (
                :avion_id, :date_achat, :recettes,
                :nb_annees_credit, :taux_percent, :remboursement,
                :traite_payee_cumulee, :reste_a_payer,
                :vente, :recette_vente, :date_vente
            )
        ");

        $stmt->execute([
            ':avion_id' => $avion_id,
            ':date_achat' => convert_date($date_achat),
            ':recettes' => clean_money($recettes),
            ':nb_annees_credit' => $nb_annees_credit !== '' ? (int)$nb_annees_credit : 0,
            ':taux_percent' => $taux_percent !== '' ? clean_money($taux_percent) : 0.0,
            ':remboursement' => clean_money($remboursement),
            ':traite_payee_cumulee' => clean_money($traite_payee_cumulee),
            ':reste_a_payer' => clean_money($reste_a_payer),
            ':vente' => $vente !== '' ? clean_money($vente) : null,
            ':recette_vente' => $recette_vente !== '' ? clean_money($recette_vente) : null,
            ':date_vente' => convert_date($date_vente)
        ]);

        $insertCount++;
    } catch (PDOException $e) {
        echo "❌ Ligne $ligneNum : Erreur SQL - " . $e->getMessage() . "<br>";
    }
}

fclose($handle);
echo "<br>✅ Import terminé : $insertCount lignes insérées dans FINANCES.";
?>
