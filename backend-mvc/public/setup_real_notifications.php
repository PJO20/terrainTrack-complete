<?php

/**
 * Script pour configurer de vraies notifications réalistes
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Configuration des notifications réelles</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .success { color: green; font-weight: bold; }
        .info { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>🔧 Configuration des notifications réelles</h1>";

try {
    // Connexion à la base
    $pdo = new PDO(
        'mysql:host=localhost;dbname=terraintrack;charset=utf8mb4',
        'root',
        'root',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "<p class='success'>✅ Connexion DB OK</p>";
    
    // 1. Supprimer toutes les notifications existantes de test
    echo "<h3>🗑️ Nettoyage des notifications existantes...</h3>";
    $deletedCount = $pdo->exec("DELETE FROM notifications");
    echo "<p>Supprimé : $deletedCount notifications</p>";
    
    // 2. Nettoyer les notifications dynamiques de session
    session_start();
    if (isset($_SESSION['dynamic_notifications'])) {
        unset($_SESSION['dynamic_notifications']);
        echo "<p>✅ Notifications dynamiques supprimées</p>";
    }
    
    if (isset($_SESSION['suppressed_test_notifications'])) {
        unset($_SESSION['suppressed_test_notifications']);
        echo "<p>✅ Cache des notifications supprimées nettoyé</p>";
    }
    
    // 3. Créer quelques vraies notifications d'exemple (optionnel)
    echo "<h3>📝 Création de notifications d'exemple...</h3>";
    
    $realNotifications = [
        [
            'title' => 'Bienvenue dans TerrainTrack',
            'description' => 'L\'application est maintenant configurée et prête à l\'emploi.',
            'type' => 'Succès',
            'type_class' => 'success',
            'icon' => 'bx-check-circle',
            'related_to' => 'Système: Configuration',
            'priority' => 'medium',
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ],
        [
            'title' => 'Système de notifications activé',
            'description' => 'Le système de notifications en temps réel est maintenant opérationnel.',
            'type' => 'Information',
            'type_class' => 'info',
            'icon' => 'bx-info-circle',
            'related_to' => 'Système: Notifications',
            'priority' => 'low',
            'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes'))
        ]
    ];
    
    $sql = "INSERT INTO notifications (title, description, type, type_class, icon, related_to, related_type, priority, is_read, created_at, updated_at) 
            VALUES (:title, :description, :type, :type_class, :icon, :related_to, :related_type, :priority, :is_read, :created_at, :updated_at)";
    
    $stmt = $pdo->prepare($sql);
    
    foreach ($realNotifications as $notif) {
        $stmt->execute([
            'title' => $notif['title'],
            'description' => $notif['description'],
            'type' => $notif['type'],
            'type_class' => $notif['type_class'],
            'icon' => $notif['icon'],
            'related_to' => $notif['related_to'],
            'related_type' => 'system',
            'priority' => $notif['priority'],
            'is_read' => 0,
            'created_at' => $notif['created_at'],
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        echo "<p>✅ Créé : " . htmlspecialchars($notif['title']) . "</p>";
    }
    
    echo "<div class='info'>
        <h3>🎯 Configuration terminée !</h3>
        <p><strong>Les notifications fictives ont été supprimées.</strong></p>
        <p>Maintenant :</p>
        <ul>
            <li>✅ Plus de notifications automatiques fictives</li>
            <li>✅ Seules les vraies notifications apparaîtront</li>
            <li>✅ Notifications créées lors d'actions réelles (créer intervention, etc.)</li>
            <li>✅ Système professionnel et propre</li>
        </ul>
        <p><strong>Actions suivantes :</strong></p>
        <ul>
            <li>Allez sur <a href='/notifications'>la page notifications</a></li>
            <li>Créez une nouvelle intervention pour tester</li>
            <li>Les nouvelles notifications apparaîtront seulement lors d'actions réelles</li>
        </ul>
    </div>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?> 