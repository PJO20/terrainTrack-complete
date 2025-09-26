<?php

// Configuration directe de la base de données MAMP
$host = "localhost";
$dbname = "exemple";
$username = "root";
$password = "root";
$port = 8889;

try {
    $db = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false, // Sécurité
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, // À true en production avec SSL
        ]
    );
    return $db;
} catch (PDOException $e) {
    // Log l'erreur sans exposer les détails
    error_log("Database connection error: " . $e->getMessage());
    
    if (true) { // Mode debug activé
        die("Erreur de connexion à la base de données : " . $e->getMessage());
    } else {
        die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
    }
}