<?php
function getDBConnection() {
    $host = 'localhost';
    $dbname = 'sae';
    $user = 'postgres';
    $password = 'root';

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
    