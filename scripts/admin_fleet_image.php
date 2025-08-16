<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['isAdmin'] != 1) {
    die("Accès refusé.");
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image']) && isset($_POST['immat'])) {
    $immat = preg_replace('/[^A-Z0-9\-]/i', '', $_POST['immat']);
    $uploadDir = __DIR__ . '/../assets/images/fleet/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    $file = $_FILES['image'];
    if ($file['size'] > 250 * 1024) {
        die("Le fichier est trop volumineux (max 250 Ko).");
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed)) {
        die("Format de fichier non autorisé.");
    }
    $dest = $uploadDir . $immat . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        echo "Image uploadée avec succès.";
        // redirect to fleet page
        header('Location: ../pages/fleet.php?immat=' . urlencode($immat));
        exit;
    } else {
        echo "Erreur lors de l'upload.";
    }
} else {
    echo "Requête invalide.";
}
?>