<?php
require_once __DIR__ . '/config.php';

$host = DB_HOST;
$db   = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;
$charset = DB_CHARSET;

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    exit("Erreur de connexion à la base de données : " . $e->getMessage());
}
