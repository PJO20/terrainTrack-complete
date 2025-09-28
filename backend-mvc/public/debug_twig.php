<?php
/**
 * Script de diagnostic pour Twig
 */

session_start();

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: /backend-mvc/public/index.php?page=login');
    exit;
}

echo "<h1>🔍 Diagnostic Twig - " . date('H:i:s') . "</h1>";

// 1. Vérifier les chemins
$templatePath = '/Applications/MAMP/htdocs/exemple/backend-mvc/template/notifications/preferences.html.twig';
echo "<h2>📁 Vérification des fichiers :</h2>";
echo "<p><strong>Template path:</strong> $templatePath</p>";
echo "<p><strong>Existe:</strong> " . (file_exists($templatePath) ? '✅ Oui' : '❌ Non') . "</p>";

if (file_exists($templatePath)) {
    $content = file_get_contents($templatePath);
    echo "<p><strong>Taille:</strong> " . strlen($content) . " caractères</p>";
    echo "<p><strong>Contient 'VERSION':</strong> " . (strpos($content, 'VERSION') !== false ? '✅ Oui' : '❌ Non') . "</p>";
    echo "<p><strong>Contient 'preferences-header':</strong> " . (strpos($content, 'preferences-header') !== false ? '✅ Oui' : '❌ Non') . "</p>";
    echo "<p><strong>Contient 'linear-gradient':</strong> " . (strpos($content, 'linear-gradient') !== false ? '✅ Oui' : '❌ Non') . "</p>";
}

// 2. Vérifier le cache
$cacheDir = '/Applications/MAMP/htdocs/exemple/backend-mvc/var/cache';
echo "<h2>🗄️ Cache Twig :</h2>";
echo "<p><strong>Cache dir:</strong> $cacheDir</p>";
echo "<p><strong>Existe:</strong> " . (is_dir($cacheDir) ? '✅ Oui' : '❌ Non') . "</p>";

if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/**/*.cache');
    echo "<p><strong>Fichiers cache:</strong> " . count($files) . "</p>";
    foreach ($files as $file) {
        echo "<p>- " . basename($file) . " (" . date('H:i:s', filemtime($file)) . ")</p>";
    }
}

// 3. Test direct du template
echo "<h2>🧪 Test direct :</h2>";
try {
    // Simuler les données
    $user = ['email' => 'test@test.com', 'phone' => ''];
    $preferences = [
        'email_notifications' => 1,
        'sms_notifications' => 0,
        'maintenance_reminders' => 1,
        'intervention_assignments' => 1,
        'critical_alerts' => 1
    ];
    $stats = [
        'total_notifications' => 5,
        'email_stats' => ['sent' => 0],
        'sms_stats' => ['sent' => 0],
        'recent_logs' => []
    ];
    
    echo "<p>✅ Variables de test créées</p>";
    echo "<p><strong>User email:</strong> " . $user['email'] . "</p>";
    echo "<p><strong>Preferences:</strong> " . json_encode($preferences) . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Erreur: " . $e->getMessage() . "</p>";
}

// 4. Informations système
echo "<h2>🖥️ Système :</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Current time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>User ID:</strong> " . $_SESSION['user_id'] . "</p>";

// 5. Test de route
echo "<h2>🛣️ Routes :</h2>";
echo "<p><strong>URL actuelle:</strong> " . $_SERVER['REQUEST_URI'] . "</p>";
echo "<p><strong>Script actuel:</strong> " . $_SERVER['SCRIPT_NAME'] . "</p>";

echo "<hr>";
echo "<h2>🔄 Actions suggérées :</h2>";
echo "<ol>";
echo "<li><strong>Videz le cache navigateur</strong> (Ctrl+Shift+R)</li>";
echo "<li><strong>Mode incognito</strong></li>";
echo "<li><strong>Vérifiez le contrôleur</strong> NotificationPreferencesController</li>";
echo "<li><strong>Redémarrez MAMP</strong></li>";
echo "</ol>";

echo "<p><a href='/notifications/preferences' style='background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 8px;'>🔗 Retour aux préférences</a></p>";
?>

