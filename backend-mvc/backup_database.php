<?php
/**
 * Script de sauvegarde automatique de la base de données
 * Usage: php backup_database.php [--full] [--compress]
 */

require_once 'vendor/autoload.php';

use App\Service\Database;

class DatabaseBackup
{
    private string $backupDir;
    private string $dbHost;
    private string $dbName;
    private string $dbUser;
    private string $dbPass;
    private int $dbPort;
    
    public function __construct()
    {
        $this->backupDir = __DIR__ . '/backups';
        $this->dbHost = 'localhost';
        $this->dbName = 'exemple';
        $this->dbUser = 'root';
        $this->dbPass = 'root';
        $this->dbPort = 8889;
        
        // Créer le dossier de sauvegarde s'il n'existe pas
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    /**
     * Effectue une sauvegarde complète de la base de données
     */
    public function createFullBackup(bool $compress = false): string
    {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_full_{$timestamp}.sql";
        $filepath = $this->backupDir . '/' . $filename;
        
        echo "🔄 Création de la sauvegarde complète...\n";
        
        // Commande mysqldump
        $command = sprintf(
            'mysqldump -h%s -P%d -u%s -p%s --single-transaction --routines --triggers %s > %s',
            $this->dbHost,
            $this->dbPort,
            $this->dbUser,
            $this->dbPass,
            $this->dbName,
            $filepath
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Erreur lors de la sauvegarde: " . implode("\n", $output));
        }
        
        // Compression si demandée
        if ($compress) {
            $this->compressFile($filepath);
            $filepath .= '.gz';
        }
        
        echo "✅ Sauvegarde créée: $filepath\n";
        return $filepath;
    }
    
    /**
     * Effectue une sauvegarde des données critiques uniquement
     */
    public function createCriticalBackup(): string
    {
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_critical_{$timestamp}.sql";
        $filepath = $this->backupDir . '/' . $filename;
        
        echo "🔄 Création de la sauvegarde critique...\n";
        
        $criticalTables = [
            'users',
            'roles',
            'permissions',
            'user_roles',
            'role_permissions',
            'interventions',
            'vehicles',
            'teams',
            'notification_preferences',
            'appearance_settings'
        ];
        
        $command = sprintf(
            'mysqldump -h%s -P%d -u%s -p%s --single-transaction --no-create-info %s %s > %s',
            $this->dbHost,
            $this->dbPort,
            $this->dbUser,
            $this->dbPass,
            $this->dbName,
            implode(' ', $criticalTables),
            $filepath
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Erreur lors de la sauvegarde critique: " . implode("\n", $output));
        }
        
        echo "✅ Sauvegarde critique créée: $filepath\n";
        return $filepath;
    }
    
    /**
     * Compresse un fichier
     */
    private function compressFile(string $filepath): void
    {
        $command = "gzip $filepath";
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Erreur lors de la compression: " . implode("\n", $output));
        }
    }
    
    /**
     * Restaure une sauvegarde
     */
    public function restoreBackup(string $backupFile): bool
    {
        if (!file_exists($backupFile)) {
            throw new Exception("Fichier de sauvegarde non trouvé: $backupFile");
        }
        
        echo "🔄 Restauration de la sauvegarde...\n";
        
        // Décompresser si nécessaire
        if (pathinfo($backupFile, PATHINFO_EXTENSION) === 'gz') {
            $tempFile = str_replace('.gz', '', $backupFile);
            exec("gunzip -c $backupFile > $tempFile");
            $backupFile = $tempFile;
        }
        
        $command = sprintf(
            'mysql -h%s -P%d -u%s -p%s %s < %s',
            $this->dbHost,
            $this->dbPort,
            $this->dbUser,
            $this->dbPass,
            $this->dbName,
            $backupFile
        );
        
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Erreur lors de la restauration: " . implode("\n", $output));
        }
        
        echo "✅ Sauvegarde restaurée avec succès\n";
        return true;
    }
    
    /**
     * Nettoie les anciennes sauvegardes (garde les 7 dernières)
     */
    public function cleanupOldBackups(int $keepDays = 7): void
    {
        echo "🧹 Nettoyage des anciennes sauvegardes...\n";
        
        $files = glob($this->backupDir . '/backup_*.sql*');
        $cutoffTime = time() - ($keepDays * 24 * 60 * 60);
        
        $deleted = 0;
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $deleted++;
                echo "🗑️ Supprimé: " . basename($file) . "\n";
            }
        }
        
        echo "✅ $deleted anciennes sauvegardes supprimées\n";
    }
    
    /**
     * Liste les sauvegardes disponibles
     */
    public function listBackups(): array
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
}

// Script principal
if (php_sapi_name() === 'cli') {
    $backup = new DatabaseBackup();
    
    $options = getopt('', ['full', 'compress', 'critical', 'restore:', 'list', 'cleanup']);
    
    try {
        if (isset($options['list'])) {
            echo "📋 SAUVEGARDES DISPONIBLES :\n";
            echo "============================\n\n";
            
            $backups = $backup->listBackups();
            if (empty($backups)) {
                echo "❌ Aucune sauvegarde trouvée\n";
            } else {
                foreach ($backups as $b) {
                    $size = round($b['size'] / 1024 / 1024, 2);
                    echo "📁 {$b['file']} ({$size} MB) - {$b['date']}\n";
                }
            }
        }
        elseif (isset($options['restore'])) {
            $backup->restoreBackup($options['restore']);
        }
        elseif (isset($options['cleanup'])) {
            $backup->cleanupOldBackups();
        }
        elseif (isset($options['critical'])) {
            $backup->createCriticalBackup();
        }
        else {
            // Sauvegarde complète par défaut
            $compress = isset($options['compress']);
            $backup->createFullBackup($compress);
        }
        
    } catch (Exception $e) {
        echo "❌ Erreur: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>



