<?php
/**
 * Script pour déboguer les données passées au template
 */

echo "🔍 Debug des données du template de préférences\n";
echo "==============================================\n\n";

// Inclure les dépendances
require_once __DIR__ . '/vendor/autoload.php';

try {
    // Configuration directe de la base de données
    $host = 'localhost';
    $port = '8889';
    $dbname = 'exemple';
    $username = 'root';
    $password = 'root';
    
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Simuler l'utilisateur connecté (ID 7)
    $userId = 7;
    
    // Récupérer l'utilisateur via UserRepository
    echo "1. 👤 Test UserRepository->findById($userId):\n";
    $userRepository = new \App\Repository\UserRepository($pdo);
    $user = $userRepository->findById($userId);
    
    if ($user) {
        echo "  ✅ Utilisateur trouvé:\n";
        echo "    - ID: " . $user->getId() . "\n";
        echo "    - Nom: " . ($user->getName() ?? 'NULL') . "\n";
        echo "    - Email: " . $user->getEmail() . "\n";
        echo "    - Email de notification: " . ($user->getNotificationEmail() ?? 'NULL') . "\n";
        echo "    - Téléphone: " . ($user->getPhone() ?? 'NULL') . "\n";
        echo "    - SMS notifications: " . ($user->getNotificationSms() ? 'true' : 'false') . "\n";
    } else {
        echo "  ❌ Utilisateur non trouvé\n";
        exit;
    }
    
    // Test de la logique du template
    echo "\n2. 🧪 Test de la logique du template:\n";
    $notificationEmail = $user->getNotificationEmail() ?? $user->getEmail();
    echo "  - user.getNotificationEmail(): " . ($user->getNotificationEmail() ?? 'NULL') . "\n";
    echo "  - user.getEmail(): " . $user->getEmail() . "\n";
    echo "  - Résultat de user.notification_email ?? user.email: " . $notificationEmail . "\n";
    
    // Récupérer les préférences
    echo "\n3. 🔧 Test NotificationPreferencesRepository:\n";
    $preferencesRepository = new \App\Repository\NotificationPreferencesRepository($pdo);
    $preferences = $preferencesRepository->findByUserId($userId);
    
    if ($preferences) {
        echo "  ✅ Préférences trouvées:\n";
        echo "    - Email notifications: " . ($preferences['email_notifications'] ? 'true' : 'false') . "\n";
        echo "    - SMS notifications: " . ($preferences['sms_notifications'] ? 'true' : 'false') . "\n";
        echo "    - Maintenance reminders: " . ($preferences['maintenance_reminders'] ? 'true' : 'false') . "\n";
        echo "    - Critical alerts: " . ($preferences['critical_alerts'] ? 'true' : 'false') . "\n";
    } else {
        echo "  ⚠️ Aucune préférence trouvée\n";
    }
    
    // Test direct du template
    echo "\n4. 🎨 Test de rendu du template:\n";
    
    // Créer les services nécessaires
    $twigService = new \App\Service\TwigService();
    
    // Simuler les données exactes du contrôleur
    $templateData = [
        'user' => $user,
        'preferences' => $preferences,
        'stats' => [],
        'emailConfig' => true,
        'smsConfig' => false
    ];
    
    echo "  📋 Données passées au template:\n";
    echo "    - user->getEmail(): " . $user->getEmail() . "\n";
    echo "    - user->getNotificationEmail(): " . ($user->getNotificationEmail() ?? 'NULL') . "\n";
    echo "    - Logique template: " . ($user->getNotificationEmail() ?? $user->getEmail()) . "\n";
    
    // Vérifier le cache Twig
    echo "\n5. 🗂️ Vérification du cache Twig:\n";
    $cacheDir = __DIR__ . '/var/cache';
    if (is_dir($cacheDir)) {
        echo "  📁 Répertoire cache existe: $cacheDir\n";
        
        // Lister les fichiers de cache
        $cacheFiles = glob($cacheDir . '/*');
        echo "  📄 Fichiers de cache: " . count($cacheFiles) . "\n";
        
        // Vider le cache
        foreach ($cacheFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        echo "  🗑️ Cache vidé!\n";
    } else {
        echo "  ⚠️ Répertoire cache n'existe pas\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "📍 Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n🔧 Debug terminé !\n";
echo "\n💡 Maintenant, allez sur http://localhost:8888/notifications/preferences\n";
echo "    et vérifiez si l'email de notification s'affiche correctement.\n";
?>


