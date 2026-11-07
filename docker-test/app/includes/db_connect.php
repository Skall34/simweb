<?php
/*
-------------------------------------------------------------
 Script : db_connect.php
 Emplacement : includes/

 Description :
 Fichier de connexion centralisé à la base de données MySQL via PDO.
 Établit une connexion sécurisée avec gestion d'erreurs et options optimisées.

 Utilisation :
 - À inclure dans tous les scripts nécessitant un accès à la base de données.
 - La variable globale $pdo est disponible après inclusion.
 - En cas d'erreur de connexion, le script s'arrête avec un message.

 Configuration :
 - Les paramètres de connexion sont définis dans config.php (DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_CHARSET).

 Auteur :
 - Équipe de développement SimWeb
-------------------------------------------------------------
*/
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
