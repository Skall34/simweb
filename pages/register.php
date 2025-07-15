<?php
session_start();
require_once(__DIR__ . "/../includes/db_connect.php");
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/menu_guest.php';

function genererCallsign($pdo) {
    do {
        $callsign = 'SKY' . str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT id FROM PILOTES WHERE callsign = ?");
        $stmt->execute([$callsign]);
    } while ($stmt->fetch());
    return $callsign;
}

function isValidCallsign($callsign) {
    return preg_match('/^SKY\d{4}$/', $callsign);
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $callsign = strtoupper(trim($_POST['callsign'] ?? ''));
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mdp = $_POST['motdepasse'] ?? '';
    $mdp2 = $_POST['motdepasse2'] ?? '';

    if (empty($prenom) || empty($nom) || empty($email) || empty($mdp) || empty($mdp2)) {
        $errors[] = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Adresse email invalide.";
    } elseif ($mdp !== $mdp2) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    } else {
        // Si l'utilisateur a saisi un callsign, on vérifie sa validité et unicité
        if ($callsign !== '') {
            if (!isValidCallsign($callsign)) {
                $errors[] = "Le callsign doit être au format SKY suivi de 4 chiffres (ex : SKY1234).";
            } else {
                $stmt = $pdo->prepare("SELECT id FROM PILOTES WHERE callsign = ?");
                $stmt->execute([$callsign]);
                if ($stmt->fetch()) {
                    $errors[] = "Ce callsign est déjà utilisé, merci d'en choisir un autre.";
                }
            }
        } else {
            // Sinon on génère un callsign automatiquement
            $callsign = genererCallsign($pdo);
        }

        // Si pas d'erreur sur callsign, on vérifie l'email
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM PILOTES WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = "Un compte avec cet email existe déjà.";
            }
        }
    }

    // Si toujours pas d'erreur, on insert
    if (empty($errors)) {
        $hash = password_hash($mdp, PASSWORD_DEFAULT);
        // Affecter le grade 1 (Junior) à tout nouveau pilote
        $stmt = $pdo->prepare("INSERT INTO PILOTES (callsign, password, prenom, nom, email, admin, grade_id) VALUES (?, ?, ?, ?, ?, 0, 1)");
        if(!$stmt->execute([$callsign, $hash, $prenom, $nom, $email])) {
            $errors[] = "Erreur lors de l'inscription, veuillez réessayer.";
        } else {
            $_SESSION['user'] = [
                'callsign' => $callsign,
                'prenom' => $prenom,
                'nom' => $nom,
            ];
        }
    }
}

?>

<main>
    <h2>Inscription</h2>
    <?php if (!empty($errors)): ?>
        <div class="erreurs">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form class="form-inscription" method="post" action="">
    <label>Callsign :
        <input type="text" name="callsign" pattern="SKY\d{4}" maxlength="7" placeholder="Exemple: SKY1234">
    </label>

    <label>Prénom :
        <input type="text" name="prenom" required>
    </label>

    <label>Nom :
        <input type="text" name="nom" required>
    </label>

    <label>Email :
        <input type="email" name="email" required>
    </label>

    <label>Mot de passe :
        <input type="password" name="motdepasse" required>
    </label>

    <label>Confirmer mot de passe :
        <input type="password" name="motdepasse2" required>
    </label>

    <button type="submit">S'inscrire</button>
    </form>
</main>

<?php
include __DIR__ . '/../includes/footer.php';
?>
