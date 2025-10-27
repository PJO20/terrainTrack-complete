<?php
/**
 * Script pour nettoyer le cache et corriger l'affichage du profil
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🧹 NETTOYAGE CACHE ET CORRECTION PROFIL\n";
echo "======================================\n\n";

try {
    echo "1️⃣ Nettoyage des sessions:\n";
    
    // Nettoyer toutes les sessions
    $pdo = \App\Service\Database::connect();
    
    // Supprimer toutes les sessions de la base si elles existent
    try {
        $pdo->exec("DELETE FROM sessions");
        echo "   ✅ Sessions de base supprimées\n";
    } catch (Exception $e) {
        echo "   ℹ️ Table sessions n'existe pas ou déjà vide\n";
    }
    
    // Nettoyer les fichiers de session PHP
    $sessionPath = session_save_path();
    if (empty($sessionPath)) {
        $sessionPath = sys_get_temp_dir();
    }
    
    $sessionFiles = glob($sessionPath . '/sess_*');
    $deletedCount = 0;
    foreach ($sessionFiles as $file) {
        if (is_file($file)) {
            unlink($file);
            $deletedCount++;
        }
    }
    echo "   ✅ $deletedCount fichiers de session supprimés\n";
    
    echo "\n2️⃣ Vérification des utilisateurs en base:\n";
    
    $stmt = $pdo->query("SELECT id, email, name, role FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Utilisateurs disponibles:\n";
    foreach ($users as $user) {
        echo "     - ID: {$user['id']}, Email: {$user['email']}, Nom: " . ($user['name'] ?? 'NULL') . ", Rôle: " . ($user['role'] ?? 'NULL') . "\n";
    }
    
    echo "\n3️⃣ Vérification spécifique de momo@gmail.com:\n";
    
    $stmt = $pdo->prepare("SELECT id, email, name, phone, role, department, location, timezone, language, avatar FROM users WHERE email = ?");
    $stmt->execute(['momo@gmail.com']);
    $momoUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($momoUser) {
        echo "   ✅ Utilisateur momo@gmail.com trouvé:\n";
        echo "     - ID: " . $momoUser['id'] . "\n";
        echo "     - Email: " . $momoUser['email'] . "\n";
        echo "     - Nom: " . ($momoUser['name'] ?? 'NULL') . "\n";
        echo "     - Téléphone: " . ($momoUser['phone'] ?? 'NULL') . "\n";
        echo "     - Rôle: " . ($momoUser['role'] ?? 'NULL') . "\n";
        echo "     - Département: " . ($momoUser['department'] ?? 'NULL') . "\n";
        echo "     - Localisation: " . ($momoUser['location'] ?? 'NULL') . "\n";
        echo "     - Fuseau horaire: " . ($momoUser['timezone'] ?? 'NULL') . "\n";
        echo "     - Langue: " . ($momoUser['language'] ?? 'NULL') . "\n";
        echo "     - Avatar: " . ($momoUser['avatar'] ?? 'NULL') . "\n";
    } else {
        echo "   ❌ Utilisateur momo@gmail.com non trouvé\n";
    }
    
    echo "\n4️⃣ Nettoyage du cache navigateur:\n";
    echo "   🔧 Instructions pour l'utilisateur:\n";
    echo "     1. Ouvrez les outils de développement (F12)\n";
    echo "     2. Clic droit sur le bouton actualiser\n";
    echo "     3. Sélectionnez 'Vider le cache et actualiser en dur'\n";
    echo "     OU\n";
    echo "     4. Utilisez Ctrl+Shift+R (Windows/Linux) ou Cmd+Shift+R (Mac)\n";
    
    echo "\n5️⃣ Test de reconnexion:\n";
    echo "   🔧 Étapes recommandées:\n";
    echo "     1. Allez sur http://localhost:8888/login\n";
    echo "     2. Déconnectez-vous si vous êtes connecté\n";
    echo "     3. Reconnectez-vous avec momo@gmail.com\n";
    echo "     4. Allez sur http://localhost:8888/settings\n";
    echo "     5. Vérifiez que les données s'affichent correctement\n";
    
    echo "\n6️⃣ Vérification des headers anti-cache:\n";
    
    // Vérifier que les headers anti-cache sont bien définis
    echo "   Headers anti-cache à vérifier:\n";
    echo "     - Cache-Control: no-cache, no-store, must-revalidate\n";
    echo "     - Pragma: no-cache\n";
    echo "     - Expires: 0\n";
    
    echo "\n7️⃣ Test de l'endpoint settings:\n";
    echo "   🔧 Test manuel:\n";
    echo "     1. Connectez-vous avec momo@gmail.com\n";
    echo "     2. Allez sur http://localhost:8888/settings\n";
    echo "     3. Vérifiez les logs dans /Applications/MAMP/htdocs/exemple/backend-mvc/logs/app.log\n";
    echo "     4. Recherchez les messages 'SettingsController: Utilisateur en session'\n";
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "RÉSUMÉ ET SOLUTIONS\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "✅ CACHE NETTOYÉ\n";
    echo "   - Sessions supprimées\n";
    echo "   - Fichiers de session nettoyés\n";
    echo "   - Base de données vérifiée\n";
    
    echo "\n🔧 SOLUTIONS POUR L'UTILISATEUR:\n";
    echo "   1. Vider le cache du navigateur (Ctrl+Shift+R)\n";
    echo "   2. Se déconnecter complètement\n";
    echo "   3. Se reconnecter avec momo@gmail.com\n";
    echo "   4. Aller sur http://localhost:8888/settings\n";
    echo "   5. Vérifier que les données s'affichent\n";
    
    echo "\n🎯 UTILISATEUR MOMO DISPONIBLE:\n";
    if ($momoUser) {
        echo "   ✅ momo@gmail.com (ID: {$momoUser['id']}, Rôle: {$momoUser['role']})\n";
        echo "   📧 Email: {$momoUser['email']}\n";
        echo "   👤 Nom: " . ($momoUser['name'] ?? 'À définir') . "\n";
    } else {
        echo "   ❌ momo@gmail.com non trouvé en base\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>

