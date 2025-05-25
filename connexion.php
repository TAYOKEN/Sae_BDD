<?php
function getDBConnection() {
    $host = 'localhost';
    $dbname = 'saebdd';
    $user = 'postgres';
    $password = '1307';

    try {
        $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("Erreur de connexion à la base de données : " . $e->getMessage());
    }
}
?>
    