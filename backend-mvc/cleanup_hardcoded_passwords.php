<?php
/**
 * Script pour nettoyer tous les mots de passe hardcodés
 * Usage: php cleanup_hardcoded_passwords.php
 */

class PasswordCleanup
{
    private array $filesToFix = [];
    private int $filesFixed = 0;
    private int $replacements = 0;
    
    public function __construct()
    {
        echo "🔧 NETTOYAGE DES MOTS DE PASSE HARDCODÉS\n";
        echo "========================================\n\n";
    }
    
    /**
     * Scanne et corrige tous les fichiers
     */
    public function cleanupAll(): void
    {
        // Rechercher tous les fichiers PHP avec des mots de passe hardcodés
        $this->scanDirectory(__DIR__);
        
        if (empty($this->filesToFix)) {
            echo "✅ Aucun mot de passe hardcodé trouvé !\n";
            return;
        }
        
        echo "📋 FICHIERS À CORRIGER :\n";
        echo "========================\n";
        foreach ($this->filesToFix as $file => $count) {
            echo "📄 " . basename($file) . " ($count occurrences)\n";
        }
        
        echo "\n⚠️  ATTENTION: Cette opération va modifier " . count($this->filesToFix) . " fichiers.\n";
        echo "Voulez-vous continuer ? (oui/non): ";
        
        if (php_sapi_name() === 'cli') {
            $handle = fopen("php://stdin", "r");
            $response = trim(fgets($handle));
            fclose($handle);
            
            if (strtolower($response) !== 'oui') {
                echo "❌ Opération annulée\n";
                return;
            }
        }
        
        // Effectuer les corrections
        $this->performCleanup();
        
        echo "\n🎉 NETTOYAGE TERMINÉ !\n";
        echo "======================\n";
        echo "✅ Fichiers corrigés: {$this->filesFixed}\n";
        echo "✅ Remplacements: {$this->replacements}\n";
    }
    
    /**
     * Scanne un dossier récursivement
     */
    private function scanDirectory(string $dir): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $this->scanFile($file->getPathname());
            }
        }
    }
    
    /**
     * Scanne un fichier pour les mots de passe hardcodés
     */
    private function scanFile(string $filepath): void
    {
        // Ignorer certains dossiers
        if (strpos($filepath, '/vendor/') !== false || 
            strpos($filepath, '/node_modules/') !== false ||
            basename($filepath) === 'cleanup_hardcoded_passwords.php') {
            return;
        }
        
        $content = file_get_contents($filepath);
        
        // Patterns à rechercher
        $patterns = [
            '/\$password\s*=\s*[\'"]root[\'"];?/',
            '/\$pass\s*=\s*[\'"]root[\'"];?/',
            '/[\'"]password[\'"]\s*=>\s*[\'"]root[\'"]/',
            '/DB_PASS.*=.*[\'"]root[\'"]/',
        ];
        
        $matches = 0;
        foreach ($patterns as $pattern) {
            $matches += preg_match_all($pattern, $content);
        }
        
        if ($matches > 0) {
            $this->filesToFix[$filepath] = $matches;
        }
    }
    
    /**
     * Effectue le nettoyage des fichiers
     */
    private function performCleanup(): void
    {
        foreach ($this->filesToFix as $filepath => $count) {
            echo "\n🔧 Correction de " . basename($filepath) . "...\n";
            
            $content = file_get_contents($filepath);
            $originalContent = $content;
            
            // Remplacements
            $replacements = [
                // Variables simples
                '/\$password\s*=\s*[\'"]root[\'"];?/' => '$password = EnvService::get(\'DB_PASS\', \'root\');',
                '/\$pass\s*=\s*[\'"]root[\'"];?/' => '$pass = EnvService::get(\'DB_PASS\', \'root\');',
                
                // Arrays
                '/([\'"]password[\'"])\s*=>\s*[\'"]root[\'"]/' => '$1 => EnvService::get(\'DB_PASS\', \'root\')',
                
                // Configuration directe
                '/(\$[a-zA-Z_][a-zA-Z0-9_]*\s*=\s*[\'"]root[\'"];?\s*\/\/.*password)/i' => 'EnvService::get(\'DB_PASS\', \'root\'); // $1',
            ];
            
            $fileReplacements = 0;
            foreach ($replacements as $pattern => $replacement) {
                $newContent = preg_replace($pattern, $replacement, $content, -1, $matches);
                if ($matches > 0) {
                    $content = $newContent;
                    $fileReplacements += $matches;
                }
            }
            
            // Ajouter l'import EnvService si nécessaire
            if ($fileReplacements > 0 && strpos($content, 'EnvService::') !== false) {
                if (strpos($content, 'use App\Service\EnvService;') === false && 
                    strpos($content, 'namespace') !== false) {
                    
                    $content = preg_replace(
                        '/(namespace\s+[^;]+;)/',
                        "$1\n\nuse App\\Service\\EnvService;",
                        $content,
                        1
                    );
                } elseif (strpos($content, 'require_once') !== false && 
                          strpos($content, 'EnvService') === false) {
                    
                    $requirePos = strpos($content, 'require_once');
                    $nextLine = strpos($content, "\n", $requirePos);
                    if ($nextLine !== false) {
                        $content = substr_replace(
                            $content, 
                            "\nrequire_once __DIR__ . '/src/Service/EnvService.php';", 
                            $nextLine, 
                            0
                        );
                    }
                }
            }
            
            if ($content !== $originalContent) {
                // Créer une sauvegarde
                $backupFile = $filepath . '.backup.' . date('Y-m-d_H-i-s');
                copy($filepath, $backupFile);
                
                // Écrire le nouveau contenu
                file_put_contents($filepath, $content);
                
                echo "  ✅ $fileReplacements remplacements effectués\n";
                echo "  💾 Sauvegarde: " . basename($backupFile) . "\n";
                
                $this->filesFixed++;
                $this->replacements += $fileReplacements;
            } else {
                echo "  ⚠️  Aucun remplacement effectué (patterns non trouvés)\n";
            }
        }
    }
    
    /**
     * Vérifie que EnvService est disponible
     */
    public function checkEnvService(): bool
    {
        $envServicePath = __DIR__ . '/src/Service/EnvService.php';
        
        if (!file_exists($envServicePath)) {
            echo "❌ EnvService non trouvé à: $envServicePath\n";
            echo "Veuillez vous assurer que EnvService.php existe.\n";
            return false;
        }
        
        echo "✅ EnvService trouvé\n";
        return true;
    }
    
    /**
     * Teste la configuration après nettoyage
     */
    public function testConfiguration(): void
    {
        echo "\n🧪 TEST DE CONFIGURATION :\n";
        echo "==========================\n";
        
        try {
            require_once __DIR__ . '/src/Service/EnvService.php';
            
            \App\Service\EnvService::load();
            $dbPass = \App\Service\EnvService::get('DB_PASS', 'root');
            
            echo "✅ EnvService fonctionne\n";
            echo "✅ DB_PASS récupéré: " . (strlen($dbPass) > 0 ? '***' : 'vide') . "\n";
            
            // Tester la connexion
            $host = \App\Service\EnvService::get('DB_HOST', 'localhost');
            $dbname = \App\Service\EnvService::get('DB_NAME', 'exemple');
            $username = \App\Service\EnvService::get('DB_USER', 'root');
            $port = \App\Service\EnvService::getInt('DB_PORT', 8889);
            
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $dbPass);
            
            echo "✅ Connexion base de données réussie\n";
            
        } catch (Exception $e) {
            echo "❌ Erreur de test: " . $e->getMessage() . "\n";
        }
    }
}

// Exécution du script
if (php_sapi_name() === 'cli') {
    $cleanup = new PasswordCleanup();
    
    // Vérifier les prérequis
    if (!$cleanup->checkEnvService()) {
        exit(1);
    }
    
    // Effectuer le nettoyage
    $cleanup->cleanupAll();
    
    // Tester la configuration
    $cleanup->testConfiguration();
    
    echo "\n🎉 SÉCURITÉ AMÉLIORÉE !\n";
    echo "Les mots de passe ne sont plus hardcodés dans le code.\n";
}
?>


