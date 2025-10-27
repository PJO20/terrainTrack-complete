<?php
/**
 * Configuration de la 2FA par rôle
 * - Chefs d'équipe (managers) : 2FA obligatoire
 * - Techniciens et autres : 2FA optionnelle
 */

require_once __DIR__ . '/src/Service/EnvService.php';
require_once __DIR__ . '/src/Service/Database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "🔐 CONFIGURATION 2FA PAR RÔLE\n";
echo "=============================\n\n";

try {
    echo "1️⃣ Vérification des rôles existants:\n";
    
    $pdo = \App\Service\Database::connect();
    $stmt = $pdo->query("SELECT DISTINCT role FROM users ORDER BY role");
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "   Rôles existants:\n";
    foreach ($roles as $role) {
        echo "     - $role\n";
    }
    
    echo "\n2️⃣ Configuration de la 2FA par rôle:\n";
    
    // Configuration des rôles et leurs exigences 2FA
    $roleConfig = [
        'admin' => [
            'name' => 'Administrateur',
            '2fa_required' => true,
            '2fa_optional' => false,
            'description' => '2FA obligatoire'
        ],
        'super_admin' => [
            'name' => 'Super Administrateur',
            '2fa_required' => true,
            '2fa_optional' => false,
            'description' => '2FA obligatoire'
        ],
        'manager' => [
            'name' => 'Chef d\'équipe',
            '2fa_required' => true,
            '2fa_optional' => false,
            'description' => '2FA obligatoire'
        ],
        'technician' => [
            'name' => 'Technicien',
            '2fa_required' => false,
            '2fa_optional' => true,
            'description' => '2FA optionnelle'
        ],
        'user' => [
            'name' => 'Utilisateur',
            '2fa_required' => false,
            '2fa_optional' => true,
            'description' => '2FA optionnelle'
        ]
    ];
    
    echo "   Configuration des rôles:\n";
    foreach ($roleConfig as $role => $config) {
        echo "     - $role ({$config['name']}): {$config['description']}\n";
    }
    
    echo "\n3️⃣ Mise à jour des utilisateurs existants:\n";
    
    $updatedCount = 0;
    $errors = [];
    
    // Récupérer tous les utilisateurs
    $stmt = $pdo->query("SELECT id, email, name, role, two_factor_enabled, two_factor_required FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($users as $user) {
        $role = $user['role'];
        $userId = $user['id'];
        $userEmail = $user['email'];
        
        echo "   Traitement de $userEmail (Rôle: $role)...\n";
        
        try {
            if (isset($roleConfig[$role])) {
                $config = $roleConfig[$role];
                
                if ($config['2fa_required']) {
                    // 2FA obligatoire pour ce rôle
                    $stmt = $pdo->prepare("UPDATE users SET two_factor_required = 1 WHERE id = ?");
                    $stmt->execute([$userId]);
                    
                    // Si pas encore activé, activer automatiquement
                    if (!$user['two_factor_enabled']) {
                        $secret2FA = 'MFRGG43B' . strtoupper(substr(md5($userId . time()), 0, 24));
                        $stmt = $pdo->prepare("UPDATE users SET two_factor_enabled = 1, two_factor_secret = ? WHERE id = ?");
                        $stmt->execute([$secret2FA, $userId]);
                        echo "     ✅ 2FA obligatoire activée\n";
                    } else {
                        echo "     ✅ 2FA déjà activée\n";
                    }
                } else {
                    // 2FA optionnelle pour ce rôle
                    $stmt = $pdo->prepare("UPDATE users SET two_factor_required = 0 WHERE id = ?");
                    $stmt->execute([$userId]);
                    echo "     ✅ 2FA optionnelle (peut être activée dans les paramètres)\n";
                }
                
                $updatedCount++;
            } else {
                echo "     ⚠️ Rôle non reconnu: $role\n";
            }
        } catch (Exception $e) {
            echo "     ❌ Erreur: " . $e->getMessage() . "\n";
            $errors[] = "Erreur pour $userEmail: " . $e->getMessage();
        }
    }
    
    echo "\n4️⃣ Résumé des mises à jour:\n";
    echo "   ✅ Utilisateurs mis à jour: $updatedCount\n";
    echo "   ❌ Erreurs: " . count($errors) . "\n";
    
    if (!empty($errors)) {
        echo "   Détails des erreurs:\n";
        foreach ($errors as $error) {
            echo "     - $error\n";
        }
    }
    
    echo "\n5️⃣ Vérification des utilisateurs par rôle:\n";
    
    foreach ($roleConfig as $role => $config) {
        $stmt = $pdo->prepare("SELECT id, email, name, two_factor_enabled, two_factor_required FROM users WHERE role = ? ORDER BY id");
        $stmt->execute([$role]);
        $roleUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($roleUsers)) {
            echo "   Rôle: $role ({$config['name']}) - {$config['description']}:\n";
            foreach ($roleUsers as $user) {
                $status2FA = '';
                if ($user['two_factor_required'] && $user['two_factor_enabled']) {
                    $status2FA = ' ✅ 2FA ACTIF';
                } else if ($user['two_factor_required'] && !$user['two_factor_enabled']) {
                    $status2FA = ' ⚠️ 2FA REQUIS MAIS NON ACTIVÉ';
                } else if (!$user['two_factor_required'] && $user['two_factor_enabled']) {
                    $status2FA = ' 🔧 2FA OPTIONNELLE ACTIVÉE';
                } else {
                    $status2FA = ' ℹ️ 2FA OPTIONNELLE NON ACTIVÉE';
                }
                
                echo "     - ID: {$user['id']}, Email: {$user['email']}, Nom: " . ($user['name'] ?? 'NULL') . "$status2FA\n";
            }
        }
    }
    
    echo "\n6️⃣ Mise à jour de UserRepository pour la logique par rôle:\n";
    
    // Créer un script de mise à jour pour UserRepository
    $userRepositoryUpdate = '<?php
/**
 * Mise à jour de UserRepository pour la logique 2FA par rôle
 */

// Dans UserRepository::save(), remplacer la logique actuelle par:
if ($result) {
    $userId = $this->pdo->lastInsertId();
    $this->ensure2FAByRole($userId, $user->getRole());
}

// Ajouter cette méthode dans UserRepository:
private function ensure2FAByRole(int $userId, string $role): void
{
    try {
        // Rôles avec 2FA obligatoire
        $requiredRoles = [\'admin\', \'super_admin\', \'manager\'];
        
        if (in_array($role, $requiredRoles)) {
            // 2FA obligatoire
            $stmt = $this->pdo->prepare("UPDATE users SET two_factor_required = 1 WHERE id = ?");
            $stmt->execute([$userId]);
            
            // Activer automatiquement si pas encore fait
            $stmt = $this->pdo->prepare("SELECT two_factor_enabled FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user[\'two_factor_enabled\']) {
                $secret2FA = \'MFRGG43B\' . strtoupper(substr(md5($userId . time()), 0, 24));
                $stmt = $this->pdo->prepare("UPDATE users SET two_factor_enabled = 1, two_factor_secret = ? WHERE id = ?");
                $stmt->execute([$secret2FA, $userId]);
                error_log("2FA obligatoire activée pour $role ID: $userId");
            }
        } else {
            // 2FA optionnelle
            $stmt = $this->pdo->prepare("UPDATE users SET two_factor_required = 0 WHERE id = ?");
            $stmt->execute([$userId]);
            error_log("2FA optionnelle pour $role ID: $userId");
        }
    } catch (Exception $e) {
        error_log("Erreur lors de la configuration 2FA pour l\'utilisateur ID: $userId - " . $e->getMessage());
    }
}';
    
    file_put_contents(__DIR__ . '/user_repository_2fa_update.php', $userRepositoryUpdate);
    echo "   ✅ Script de mise à jour créé: user_repository_2fa_update.php\n";
    
    echo "\n7️⃣ Recommandations pour l'implémentation:\n";
    
    echo "   🔧 INTÉGRATION DANS LE CODE:\n";
    echo "   1. Mettre à jour UserRepository::save() avec la logique par rôle\n";
    echo "   2. Ajouter la méthode ensure2FAByRole() dans UserRepository\n";
    echo "   3. Ajouter des paramètres 2FA dans les paramètres utilisateur\n";
    echo "   4. Implémenter la logique 2FA dans AuthController\n";
    
    echo "\n   📝 PARAMÈTRES UTILISATEUR À AJOUTER:\n";
    echo "   - Pour les techniciens : Toggle 2FA dans les paramètres\n";
    echo "   - Pour les managers : 2FA obligatoire (pas de toggle)\n";
    echo "   - Pour les admins : 2FA obligatoire (pas de toggle)\n";
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "RÉSUMÉ FINAL\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "✅ 2FA CONFIGURÉE PAR RÔLE\n";
    echo "✅ CHEFS D'ÉQUIPE: 2FA OBLIGATOIRE\n";
    echo "✅ TECHNICIENS: 2FA OPTIONNELLE\n";
    echo "🔧 PROCHAINES ÉTAPES:\n";
    echo "   1. Mettre à jour UserRepository avec la logique par rôle\n";
    echo "   2. Ajouter des paramètres 2FA dans l'interface utilisateur\n";
    echo "   3. Implémenter la logique 2FA dans AuthController\n";
    echo "   4. Tester la déconnexion/reconnexion\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>

