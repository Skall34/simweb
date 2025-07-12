<?php
session_start();
require 'includes/db_connect.php'; // à créer: connexion à la base

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $callsign = $_POST['callsign'] ?? '';
    $password = $_POST['password'] ?? '';

    // Prépare et execute requête
    $stmt = $pdo->prepare('SELECT * FROM PILOTES WHERE callsign = ?');
    $stmt->execute([$callsign]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Auth OK
        $_SESSION['user'] = [
            'id' => $user['id'],
            'callsign' => $user['callsign']
        ];
        header('Location: index.php');
        exit;
    } else {
        $error = "Appel ou mot de passe incorrect.";
    }
}


// --- AJOUT AFFICHAGE VOLS EN COURS ---
$vols = [];
$stmt = $pdo->query("SELECT FLOTTE.immat, FLOTTE.pilote_id, PILOTES.callsign
    FROM FLOTTE
    LEFT JOIN PILOTES ON FLOTTE.pilote_id = PILOTES.id
    WHERE FLOTTE.en_vol = 1");
    $vols = $stmt->fetchAll(PDO::FETCH_ASSOC);

    include 'includes/header.php';
    ?>

    <!-- Affichage des vols en cours -->
    <section>
        <h3>Vols en cours</h3>
        <?php if (empty($vols)): ?>
            <p>Aucun vol en cours.</p>
        <?php else: ?>
            <table border="1" cellpadding="5" style="margin-bottom:1em;">
                <tr>
                    <th>Immatriculation</th>
                    <th>Pilote (callsign)</th>
                </tr>
                <?php foreach ($vols as $vol): ?>
                    <tr>
                        <td><?= htmlspecialchars($vol['immat']) ?></td>
                        <td><?= htmlspecialchars($vol['callsign'] ?? 'Inconnu') ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </section>
    ?>

<main>
    <h2>Connexion</h2>
    <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
    <form method="post" action="login.php">
        <label>Callsign : <input type="text" name="callsign" required></label><br><br>
        <label>Mot de passe : <input type="password" name="password" required></label><br><br>
        <button type="submit">Se connecter</button>
    </form>
</main>

<?php include 'includes/footer.php'; ?>
