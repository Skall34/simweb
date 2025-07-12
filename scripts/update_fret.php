<?php
// Activer les erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/update_fret.log'); // Log dans le même dossier que le script

// Connexion à la base de données
require_once __DIR__ . '/../includes/db_connect.php';

// Plage des valeurs à ajouter
$min = 1;
$max = 100;

try {
    // Récupérer tous les aéroports avec leur fret actuel
    $stmt = $pdo->query("SELECT ident, fret FROM AEROPORTS");
    $aeroports = $stmt->fetchAll();

    foreach ($aeroports as $aeroport) {
        $valeurAleatoire = random_int($min, $max);
        $nouveauFret = $aeroport['fret'] + $valeurAleatoire;

        // Mise à jour du fret pour cet aéroport
        $updateStmt = $pdo->prepare("UPDATE AEROPORTS SET fret = :fret WHERE ident = :ident");
        $updateStmt->execute([
            'fret' => $nouveauFret,
            'ident' => $aeroport['ident']
        ]);

        error_log("Fret mis à jour pour {$aeroport['ident']}: +$valeurAleatoire (total: $nouveauFret)");
    }

    error_log("✅ Traitement hebdomadaire terminé avec succès.");
    echo "Traitement hebdomadaire terminé avec succès.\n";
} catch (PDOException $e) {
    $msg = "❌ Erreur lors de la mise à jour : " . $e->getMessage();
    error_log($msg);
    echo $msg . "\n";
}
