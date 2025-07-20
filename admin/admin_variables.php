<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/menu_logged.php';

// Vérification admin comme dans menu_logged.php
if (!isset($_SESSION['user']['callsign'])) {
    echo '<div style="margin:40px auto;max-width:600px;padding:32px;background:#fff;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);color:#b00;text-align:center;">Accès réservé aux administrateurs.</div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}
$stmt = $pdo->prepare("SELECT admin FROM PILOTES WHERE callsign = :callsign");
$stmt->execute(['callsign' => $_SESSION['user']['callsign']]);
$isAdmin = $stmt->fetchColumn();
if ($isAdmin != 1) {
    echo '<div style="margin:40px auto;max-width:600px;padding:32px;background:#fff;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);color:#b00;text-align:center;">Accès réservé aux administrateurs.</div>';
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// Création de la table si elle n'existe pas
$pdo->exec("CREATE TABLE IF NOT EXISTS VARIABLES_CONFIG (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(64) UNIQUE NOT NULL,
    valeur VARCHAR(64) NOT NULL
)");

// Variables à gérer
$variables = [
    'prix_kg_fret' => [
        'label' => 'Prix du Kg transporté',
        'type' => 'number',
        'step' => '0.1',
        'default' => '5.00'
    ],
    'prix_litre_essence' => [
        'label' => 'Prix du litre d\'essence',
        'type' => 'number',
        'step' => '0.01',
        'default' => '0.88'
    ],
    'taux_assurance' => [
        'label' => "Taux de l'assurance (%)",
        'type' => 'number',
        'step' => '1',
        'min' => '0',
        'max' => '100',
        'default' => '2'
    ]
];

// Initialiser les variables si absentes
foreach ($variables as $key => $info) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM VARIABLES_CONFIG WHERE nom = ?');
    $stmt->execute([$key]);
    if ($stmt->fetchColumn() == 0) {
        $val = $info['default'];
        if ($key === 'taux_assurance') {
            $val = number_format(floatval($val) / 100, 4, '.', '');
        }
        $stmt = $pdo->prepare('INSERT INTO VARIABLES_CONFIG (nom, valeur) VALUES (?, ?)');
        $stmt->execute([$key, $val]);
    }
}

// Traitement du formulaire
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($variables as $key => $info) {
        if (isset($_POST[$key])) {
            $val = trim($_POST[$key]);
            if ($key === 'taux_assurance') {
                $val = number_format(floatval($val) / 100, 4, '.', '');
            }
            $stmt = $pdo->prepare('UPDATE VARIABLES_CONFIG SET valeur = ? WHERE nom = ?');
            $stmt->execute([$val, $key]);
        }
    }
    $message = 'Variables mises à jour avec succès.';
}

// Récupération des valeurs actuelles
$values = [];
$stmt = $pdo->query('SELECT nom, valeur FROM VARIABLES_CONFIG');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $values[$row['nom']] = $row['valeur'];
}
?>
<main>
    <div class="container" style="max-width:700px;margin:40px 0 40px 0;background:#fff;padding:32px;border-radius:12px;box-shadow:0 2px 16px rgba(0,0,0,0.08);">
        <h2 style="text-align:left;color:#1a3552;margin-bottom:28px;">Administration des variables globales</h2>
        <?php if ($message): ?>
            <div style="margin-bottom:18px;color:#1a3552;background:#eaf2fb;padding:10px 16px;border-radius:6px;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <form method="post" style="display:flex;flex-direction:column;gap:18px;max-width:420px;">
            <?php foreach ($variables as $key => $info): ?>
                <label style="display:flex;flex-direction:column;font-weight:600;color:#1a3552;">
                    <?= $info['label'] ?>
                    <?php if ($key === 'taux_assurance'): ?>
                        <input type="number" name="taux_assurance" step="1" min="0" max="100" value="<?= isset($values['taux_assurance']) ? round(floatval($values['taux_assurance'])*100) : $info['default'] ?>" style="margin-top:6px;padding:8px 10px;border-radius:4px;border:1px solid #bbb;max-width:220px;">
                    <?php else: ?>
                        <input type="<?= $info['type'] ?>" name="<?= $key ?>" step="<?= $info['step'] ?>" value="<?= htmlspecialchars($values[$key] ?? $info['default']) ?>" style="margin-top:6px;padding:8px 10px;border-radius:4px;border:1px solid #bbb;max-width:220px;">
                    <?php endif; ?>
                </label>
            <?php endforeach; ?>
            <button type="submit" style="margin-top:12px;padding:10px 18px;background:#1a3552;color:#fff;border:none;border-radius:4px;align-self:flex-start;">Enregistrer</button>
        </form>
    </div>
</main>
<?php
include __DIR__ . '/../includes/footer.php';
?>
