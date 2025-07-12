<?php
// Activer les erreurs et configurer le log PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/maintenance.log'); // Log PHP

// Inclusion de la connexion PDO
require_once __DIR__ . '/../includes/db_connect.php';

try {
    $log = [];
    $log[] = "[" . date('Y-m-d H:i:s') . "] Début maintenance";

    // Récupérer tous les avions
    $stmt = $pdo->query("SELECT id, immat, status, etat, compteur_immo, nb_maintenance FROM FLOTTE WHERE actif=1");
    $flottes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($flottes as $avion) {
        $id = $avion['id'];
        $immat = $avion['immat'];
        $status = (int)$avion['status'];
        $etat = (float)$avion['etat'];
        $compteur_immo = (int)$avion['compteur_immo'];
        $nb_maintenance = (int)$avion['nb_maintenance'];

        if ($immat !== '') {
            $log[] = "Avion $immat : état=$etat / statut=$status / compteur_immo=$compteur_immo";

            if ($status === 0 && $etat < 30) {
                // Passage en maintenance pour usure
                $log[] = "L'avion $immat passe en maintenance (usure normale)";
                $sql = "UPDATE FLOTTE SET status = 1, etat = 0, compteur_immo = 1, nb_maintenance = (nb_maintenance + 1) WHERE id = :id";
                $stmtUp = $pdo->prepare($sql);
                $stmtUp->execute(['id' => $id]);

            } elseif ($status === 1) {
                // Avion déjà en maintenance, vérifier compteur_immo pour sortie
                if ($compteur_immo === 1) {
                    $log[] = "L'avion $immat sort de maintenance après 1 jour (usure)";
                    $sql = "UPDATE FLOTTE SET status = 0, etat = 100, compteur_immo = 0 WHERE id = :id";
                    $stmtUp = $pdo->prepare($sql);
                    $stmtUp->execute(['id' => $id]);
                } elseif ($compteur_immo > 1) {
                    $log[] = "L'avion $immat en maintenance, compteur_immo > 1, réinitialisation";
                    $sql = "UPDATE FLOTTE SET status = 0, etat = 1, compteur_immo = 0 WHERE id = :id";
                    $stmtUp = $pdo->prepare($sql);
                    $stmtUp->execute(['id' => $id]);
                }

            } elseif ($status === 2) {
                // Maintenance crash : compteur_immo max 3 jours
                if ($compteur_immo === 0) {
                    $log[] = "L'avion $immat a subi un crash. Passage en maintenance crash (3 jours)";
                    $sql = "UPDATE FLOTTE SET compteur_immo = 1, nb_maintenance = (nb_maintenance + 1) WHERE id = :id";
                    $stmtUp = $pdo->prepare($sql);
                    $stmtUp->execute(['id' => $id]);
                } elseif ($compteur_immo >= 1 && $compteur_immo < 3) {
                    $log[] = "L'avion $immat est en maintenance crash. Incrémentation compteur_immo à " . ($compteur_immo + 1);
                    $sql = "UPDATE FLOTTE SET compteur_immo = (compteur_immo + 1) WHERE id = :id";
                    $stmtUp = $pdo->prepare($sql);
                    $stmtUp->execute(['id' => $id]);
                } elseif ($compteur_immo >= 3) {
                    $log[] = "L'avion $immat sort de maintenance après crash (3 jours). Réinitialisation compteurs.";
                    $sql = "UPDATE FLOTTE SET status = 0, etat = 100, compteur_immo = 0 WHERE id = :id";
                    $stmtUp = $pdo->prepare($sql);
                    $stmtUp->execute(['id' => $id]);
                }
            }
        }
    }

    $log[] = "[" . date('Y-m-d H:i:s') . "] Fin maintenance";

    // Sauvegarde du log dans le fichier maintenance.log
    file_put_contents(__DIR__ . '/logs/maintenance.log', implode("\n", $log) . "\n", FILE_APPEND);

    echo "Maintenance terminée avec succès.\n";

} catch (PDOException $e) {
    $msg = "Erreur lors de la maintenance : " . $e->getMessage();
    error_log($msg);
    echo $msg . "\n";
}
