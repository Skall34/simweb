<?php
session_start();

require __DIR__ . '/../includes/db_connect.php';

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

// Récupère tous les pilotes
$sql = "SELECT callsign, prenom, nom FROM PILOTES ORDER BY callsign";
$stmt = $pdo->query($sql);
$pilotes = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_logged.php';
?>

<main>
    <h2>Liste des pilotes</h2>

    <?php if (empty($pilotes)): ?>
        <p>Aucun pilote trouvé.</p>
    <?php else: ?>
        <div class="table-section">
            <table class="table-skywings">
                <thead>
                    <tr>
                        <th>Callsign</th>
                        <th>Prénom</th>
                        <th>Nom</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pilotes as $pilote): ?>
                    <tr>
                        <td><?= htmlspecialchars($pilote['callsign']) ?></td>
                        <td><?= htmlspecialchars($pilote['prenom']) ?></td>
                        <td><?= htmlspecialchars($pilote['nom']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
