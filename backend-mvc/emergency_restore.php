<?php
/**
 * Script d'urgence pour restaurer l'application
 * Usage: php emergency_restore.php [--backup=filename] [--force]
 */

require_once 'vendor/autoload.php';

class EmergencyRestore
{
    private string $backupDir;
    private string $appDir;
    
    public function __construct()
    {
        $this->backupDir = __DIR__ . '/backups';
        $this->appDir = __DIR__;
    }
    
    /**
     * Mode d'urgence : restaure la dernière sauvegarde
     */
    public function emergencyRestore(): bool
    {
        echo "🚨 MODE D'URGENCE - RESTAURATION AUTOMATIQUE\n";
        echo "============================================\n\n";
        
        // 1. Vérifier les sauvegardes disponibles
        $backups = $this->getAvailableBackups();
        if (empty($backups)) {
            echo "❌ Aucune sauvegarde disponible pour la restauration\n";
            return false;
        }
        
        // 2. Prendre la plus récente
        $latestBackup = $backups[0];
        echo "📁 Sauvegarde sélectionnée: {$latestBackup['file']}\n";
        echo "📅 Date: {$latestBackup['date']}\n";
        echo "📊 Taille: " . round($latestBackup['size'] / 1024 / 1024, 2) . " MB\n\n";
        
        // 3. Confirmation
        echo "⚠️  ATTENTION: Cette opération va remplacer toutes les données actuelles !\n";
        echo "Voulez-vous continuer ? (oui/non): ";
        
        if (php_sapi_name() === 'cli') {
            $handle = fopen("php://stdin", "r");
            $response = trim(fgets($handle));
            fclose($handle);
            
            if (strtolower($response) !== 'oui') {
                echo "❌ Restauration annulée\n";
                return false;
            }
        }
        
        // 4. Créer une sauvegarde de sécurité avant restauration
        echo "\n🔄 Création d'une sauvegarde de sécurité...\n";
        $this->createSafetyBackup();
        
        // 5. Restaurer
        echo "🔄 Restauration en cours...\n";
        return $this->restoreFromBackup($latestBackup['path']);
    }
    
    /**
     * Restaure depuis un fichier spécifique
     */
    public function restoreFromFile(string $backupFile, bool $force = false): bool
    {
        if (!file_exists($backupFile)) {
            echo "❌ Fichier de sauvegarde non trouvé: $backupFile\n";
            return false;
        }
        
        echo "🔄 Restauration depuis: " . basename($backupFile) . "\n";
        
        if (!$force) {
            echo "⚠️  Cette opération va remplacer toutes les données actuelles !\n";
            echo "Voulez-vous continuer ? (oui/non): ";
            
            if (php_sapi_name() === 'cli') {
                $handle = fopen("php://stdin", "r");
                $response = trim(fgets($handle));
                fclose($handle);
                
                if (strtolower($response) !== 'oui') {
                    echo "❌ Restauration annulée\n";
                    return false;
                }
            }
        }
        
        // Sauvegarde de sécurité
        $this->createSafetyBackup();
        
        return $this->restoreFromBackup($backupFile);
    }
    
    /**
     * Crée une sauvegarde de sécurité avant restauration
     */
    private function createSafetyBackup(): void
    {
        $timestamp = date('Y-m-d_H-i-s');
        $safetyFile = $this->backupDir . "/safety_backup_{$timestamp}.sql";
        
        $command = sprintf(
            'mysqldump -hlocalhost -P8889 -uroot -proot --single-transaction exemple > %s',
            $safetyFile
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            echo "✅ Sauvegarde de sécurité créée: " . basename($safetyFile) . "\n";
        } else {
            echo "⚠️  Impossible de créer la sauvegarde de sécurité\n";
        }
    }
    
    /**
     * Restaure depuis un fichier de sauvegarde
     */
    private function restoreFromBackup(string $backupFile): bool
    {
        try {
            // Décompresser si nécessaire
            if (pathinfo($backupFile, PATHINFO_EXTENSION) === 'gz') {
                $tempFile = str_replace('.gz', '', $backupFile);
                exec("gunzip -c $backupFile > $tempFile");
                $backupFile = $tempFile;
            }
            
            // Restaurer
            $command = sprintf(
                'mysql -hlocalhost -P8889 -uroot -proot exemple < %s',
                $backupFile
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                echo "✅ Restauration réussie !\n";
                echo "🔄 Redémarrage des services...\n";
                
                // Nettoyer les caches
                $this->clearCaches();
                
                echo "🎉 Application restaurée et prête à l'emploi !\n";
                return true;
            } else {
                echo "❌ Erreur lors de la restauration\n";
                echo "Détails: " . implode("\n", $output) . "\n";
                return false;
            }
            
        } catch (Exception $e) {
            echo "❌ Erreur: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Nettoie les caches après restauration
     */
    private function clearCaches(): void
    {
        $cacheDirs = [
            $this->appDir . '/var/cache',
            $this->appDir . '/cache',
            $this->appDir . '/tmp'
        ];
        
        foreach ($cacheDirs as $dir) {
            if (is_dir($dir)) {
                $this->deleteDirectory($dir);
                echo "🧹 Cache vidé: " . basename($dir) . "\n";
            }
        }
    }
    
    /**
     * Supprime récursivement un dossier
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
    
    /**
     * Récupère les sauvegardes disponibles
     */
    private function getAvailableBackups(): array
    {
        $files = glob($this->backupDir . '/backup_*.sql*');
        $backups = [];
        
        foreach ($files as $file) {
            $backups[] = [
                'file' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'date' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
        
        // Trier par date (plus récent en premier)
        usort($backups, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return $backups;
    }
    
    /**
     * Affiche le statut de l'application
     */
    public function showStatus(): void
    {
        echo "📊 STATUT DE L'APPLICATION\n";
        echo "==========================\n\n";
        
        // Base de données
        try {
            $pdo = new PDO("mysql:host=localhost;port=8889;dbname=exemple", 'root', 'root');
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $userCount = $stmt->fetch()['count'];
            echo "✅ Base de données: Connectée ($userCount utilisateurs)\n";
        } catch (Exception $e) {
            echo "❌ Base de données: Erreur de connexion\n";
        }
        
        // Sauvegardes
        $backups = $this->getAvailableBackups();
        echo "📁 Sauvegardes disponibles: " . count($backups) . "\n";
        
        if (!empty($backups)) {
            $latest = $backups[0];
            echo "📅 Dernière sauvegarde: {$latest['date']}\n";
        }
        
        // Caches
        $cacheDirs = ['var/cache', 'cache', 'tmp'];
        $cacheStatus = [];
        foreach ($cacheDirs as $dir) {
            $cacheStatus[] = is_dir($this->appDir . '/' . $dir) ? "✅ $dir" : "❌ $dir";
        }
        echo "🧹 Caches: " . implode(', ', $cacheStatus) . "\n";
    }
}

// Script principal
if (php_sapi_name() === 'cli') {
    $restore = new EmergencyRestore();
    
    $options = getopt('', ['backup:', 'force', 'status']);
    
    try {
        if (isset($options['status'])) {
            $restore->showStatus();
        }
        elseif (isset($options['backup'])) {
            $force = isset($options['force']);
            $restore->restoreFromFile($options['backup'], $force);
        }
        else {
            // Mode d'urgence par défaut
            $restore->emergencyRestore();
        }
        
    } catch (Exception $e) {
        echo "❌ Erreur: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>


