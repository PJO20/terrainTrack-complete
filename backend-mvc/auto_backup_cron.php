<?php
/**
 * Script de sauvegarde automatique pour cron
 * Usage: Ajouter à crontab pour exécution automatique
 * Exemple: 0 2 * * * /usr/bin/php /path/to/auto_backup_cron.php
 */

require_once 'vendor/autoload.php';

class AutoBackupCron
{
    private string $backupDir;
    private string $logFile;
    
    public function __construct()
    {
        $this->backupDir = __DIR__ . '/backups';
        $this->logFile = __DIR__ . '/logs/backup.log';
        
        // Créer les dossiers nécessaires
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }
    
    /**
     * Exécute la sauvegarde automatique
     */
    public function run(): void
    {
        $this->log("🔄 Début de la sauvegarde automatique");
        
        try {
            // 1. Sauvegarde complète
            $this->createFullBackup();
            
            // 2. Nettoyage des anciennes sauvegardes
            $this->cleanupOldBackups();
            
            // 3. Vérification de l'intégrité
            $this->verifyBackupIntegrity();
            
            $this->log("✅ Sauvegarde automatique terminée avec succès");
            
        } catch (Exception $e) {
            $this->log("❌ Erreur lors de la sauvegarde: " . $e->getMessage());
            
            // En cas d'erreur, envoyer une notification
            $this->sendErrorNotification($e->getMessage());
        }
    }
    
    /**
     * Crée une sauvegarde complète
     */
    private function createFullBackup(): string
    {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "auto_backup_{$timestamp}.sql";
        $filepath = $this->backupDir . '/' . $filename;
        
        $this->log("📁 Création de la sauvegarde: $filename");
        
        $command = sprintf(
            'mysqldump -hlocalhost -P8889 -uroot -proot --single-transaction --routines --triggers --events exemple > %s 2>&1',
            $filepath
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Erreur mysqldump: " . implode("\n", $output));
        }
        
        // Compresser la sauvegarde
        $this->compressBackup($filepath);
        $filepath .= '.gz';
        
        $size = round(filesize($filepath) / 1024 / 1024, 2);
        $this->log("✅ Sauvegarde créée: $filename ($size MB)");
        
        return $filepath;
    }
    
    /**
     * Compresse une sauvegarde
     */
    private function compressBackup(string $filepath): void
    {
        $command = "gzip $filepath 2>&1";
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Erreur compression: " . implode("\n", $output));
        }
    }
    
    /**
     * Nettoie les anciennes sauvegardes
     */
    private function cleanupOldBackups(): void
    {
        $this->log("🧹 Nettoyage des anciennes sauvegardes");
        
        // Garder les 7 dernières sauvegardes quotidiennes
        $files = glob($this->backupDir . '/auto_backup_*.sql.gz');
        $cutoffTime = time() - (7 * 24 * 60 * 60);
        
        $deleted = 0;
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $deleted++;
                $this->log("🗑️ Supprimé: " . basename($file));
            }
        }
        
        $this->log("✅ $deleted anciennes sauvegardes supprimées");
    }
    
    /**
     * Vérifie l'intégrité de la dernière sauvegarde
     */
    private function verifyBackupIntegrity(): void
    {
        $this->log("🔍 Vérification de l'intégrité");
        
        $files = glob($this->backupDir . '/auto_backup_*.sql.gz');
        if (empty($files)) {
            throw new Exception("Aucune sauvegarde trouvée pour vérification");
        }
        
        // Prendre la plus récente
        $latestBackup = max($files);
        
        // Vérifier que le fichier n'est pas corrompu
        $command = "gunzip -t $latestBackup 2>&1";
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Sauvegarde corrompue: " . basename($latestBackup));
        }
        
        $this->log("✅ Intégrité vérifiée: " . basename($latestBackup));
    }
    
    /**
     * Envoie une notification d'erreur
     */
    private function sendErrorNotification(string $error): void
    {
        // Log l'erreur
        $this->log("📧 Envoi de notification d'erreur");
        
        // Ici vous pouvez ajouter l'envoi d'email/SMS
        // Exemple avec le service email existant
        try {
            require_once 'src/Service/EmailServiceAdvanced.php';
            $emailService = new \App\Service\EmailServiceAdvanced();
            
            $subject = "🚨 Erreur de sauvegarde automatique - " . date('Y-m-d H:i:s');
            $body = "Une erreur s'est produite lors de la sauvegarde automatique:\n\n";
            $body .= "Erreur: $error\n";
            $body .= "Date: " . date('Y-m-d H:i:s') . "\n";
            $body .= "Serveur: " . gethostname() . "\n";
            
            // Remplacer par votre email d'administration
            $emailService->sendPasswordResetEmail('admin@example.com', 'Administrateur', $subject, $body);
            
            $this->log("✅ Notification d'erreur envoyée");
            
        } catch (Exception $e) {
            $this->log("❌ Impossible d'envoyer la notification: " . $e->getMessage());
        }
    }
    
    /**
     * Log un message avec timestamp
     */
    private function log(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // Afficher aussi dans la console si en mode CLI
        if (php_sapi_name() === 'cli') {
            echo $logMessage;
        }
    }
    
    /**
     * Affiche les statistiques de sauvegarde
     */
    public function showStats(): void
    {
        echo "📊 STATISTIQUES DE SAUVEGARDE\n";
        echo "=============================\n\n";
        
        // Dernières sauvegardes
        $files = glob($this->backupDir . '/auto_backup_*.sql.gz');
        if (empty($files)) {
            echo "❌ Aucune sauvegarde automatique trouvée\n";
            return;
        }
        
        // Trier par date
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        echo "📁 DERNIÈRES SAUVEGARDES :\n";
        echo "-------------------------\n";
        
        $totalSize = 0;
        foreach (array_slice($files, 0, 10) as $file) {
            $size = filesize($file);
            $totalSize += $size;
            $sizeMB = round($size / 1024 / 1024, 2);
            $date = date('Y-m-d H:i:s', filemtime($file));
            echo "📄 " . basename($file) . " ($sizeMB MB) - $date\n";
        }
        
        echo "\n📊 TOTAL : " . count($files) . " sauvegardes (" . round($totalSize / 1024 / 1024, 2) . " MB)\n";
        
        // Logs récents
        if (file_exists($this->logFile)) {
            echo "\n📝 LOGS RÉCENTS :\n";
            echo "-----------------\n";
            $logs = file($this->logFile);
            $recentLogs = array_slice($logs, -10);
            foreach ($recentLogs as $log) {
                echo trim($log) . "\n";
            }
        }
    }
}

// Script principal
if (php_sapi_name() === 'cli') {
    $backup = new AutoBackupCron();
    
    $options = getopt('', ['stats', 'run']);
    
    if (isset($options['stats'])) {
        $backup->showStats();
    } else {
        // Exécution normale
        $backup->run();
    }
}
?>



