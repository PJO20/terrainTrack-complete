<?php
// Afficher toutes les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définir le répertoire de travail
chdir(__DIR__ . '/public');

// Inclure le fichier index.php
require 'index.php'; 