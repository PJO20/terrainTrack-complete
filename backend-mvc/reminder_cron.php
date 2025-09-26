<?php
/**
 * Script de rappels automatiques pour TerrainTrack
 * À exécuter via cron pour les rappels d'entretien et d'interventions
 * 
 * Exemples de configuration cron :
 * - Toutes les heures : 0 heure minute jour mois /usr/bin/php /path/to/reminder_cron.php
 * - Toutes les 30 minutes : toutes les 30 minutes /usr/bin/php /path/to/reminder_cron.php
 * - Tous les jours a 8h : 0 8 heure jour mois /usr/bin/php /path/to/reminder_cron.php
 */

// Configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/reminder_cron.log');

// Charger l'autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Charger les variables d'environnement
use App\Service\EnvService;
EnvService::load();

// Charger la configuration des services
$services = require __DIR__ . '/config/services.php';
$container = new \App\Container\Container($services);

// Logger pour les rappels
class ReminderLogger
{
    private $logFile;
    
    public function __construct($logFile = null)
    {
        $this->logFile = $logFile ?: __DIR__ . '/logs/reminder_cron.log';
        $this->ensureLogDirectory();
    }
    
    private function ensureLogDirectory()
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public function log($message, $level = 'INFO')
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        echo $logMessage;
    }
    
    public function error($message)
    {
        $this->log($message, 'ERROR');
    }
    
    public function success($message)
    {
        $this->log($message, 'SUCCESS');
    }
}

$logger = new ReminderLogger();

try {
    $logger->log("=== DÉMARRAGE DU SCRIPT DE RAPPELS AUTOMATIQUES ===");
    
    // Récupérer les services
    $reminderService = $container->get(\App\Service\ReminderService::class);
    $emailService = $container->get(\App\Service\EmailNotificationService::class);
    $smsService = $container->get(\App\Service\SmsNotificationService::class);
    $maintenanceRepo = $container->get(\App\Repository\MaintenanceSchedulesRepository::class);
    $preferencesRepo = $container->get(\App\Repository\NotificationPreferencesRepository::class);
    $userRepo = $container->get(\App\Repository\UserRepository::class);
    
    $logger->log("Services chargés avec succès");
    
    // 1. Rappels d'entretien des véhicules
    $logger->log("--- TRAITEMENT DES RAPPELS D'ENTRETIEN ---");
    
    $upcomingMaintenance = $maintenanceRepo->findUpcomingMaintenance(7); // 7 jours à l'avance
    $overdueMaintenance = $maintenanceRepo->findOverdueMaintenance();
    
    $logger->log("Entretiens à venir (7 jours) : " . count($upcomingMaintenance));
    $logger->log("Entretiens en retard : " . count($overdueMaintenance));
    
    $maintenanceRemindersSent = 0;
    $maintenanceErrors = 0;
    
    // Traiter les entretiens à venir
    foreach ($upcomingMaintenance as $maintenance) {
        try {
            $vehicleId = $maintenance['vehicle_id'];
            $assignedTechnicianId = $maintenance['assigned_technician_id'];
            
            // Récupérer les détails du véhicule
            $vehicleRepo = $container->get(\App\Repository\VehicleRepository::class);
            $vehicle = $vehicleRepo->findById($vehicleId);
            
            if (!$vehicle) {
                $logger->error("Véhicule ID {$vehicleId} non trouvé pour l'entretien ID {$maintenance['id']}");
                continue;
            }
            
            // Récupérer le technicien assigné
            $technician = null;
            if ($assignedTechnicianId) {
                $technician = $userRepo->findById($assignedTechnicianId);
            }
            
            // Si pas de technicien assigné, notifier tous les techniciens
            if (!$technician) {
                $technicianRepo = $container->get(\App\Repository\TechnicianRepository::class);
                $technicians = $technicianRepo->findAll();
                
                foreach ($technicians as $tech) {
                    $user = $userRepo->findById($tech['user_id']);
                    if ($user) {
                        sendMaintenanceReminder($user, $vehicle, $maintenance, $emailService, $smsService, $preferencesRepo, $logger);
                        $maintenanceRemindersSent++;
                    }
                }
            } else {
                sendMaintenanceReminder($technician, $vehicle, $maintenance, $emailService, $smsService, $preferencesRepo, $logger);
                $maintenanceRemindersSent++;
            }
            
        } catch (\Exception $e) {
            $maintenanceErrors++;
            $logger->error("Erreur lors du traitement de l'entretien ID {$maintenance['id']} : " . $e->getMessage());
        }
    }
    
    // Traiter les entretiens en retard
    foreach ($overdueMaintenance as $maintenance) {
        try {
            $vehicleId = $maintenance['vehicle_id'];
            $assignedTechnicianId = $maintenance['assigned_technician_id'];
            
            $vehicleRepo = $container->get(\App\Repository\VehicleRepository::class);
            $vehicle = $vehicleRepo->findById($vehicleId);
            
            if (!$vehicle) {
                continue;
            }
            
            $technician = null;
            if ($assignedTechnicianId) {
                $technician = $userRepo->findById($assignedTechnicianId);
            }
            
            if (!$technician) {
                $technicianRepo = $container->get(\App\Repository\TechnicianRepository::class);
                $technicians = $technicianRepo->findAll();
                
                foreach ($technicians as $tech) {
                    $user = $userRepo->findById($tech['user_id']);
                    if ($user) {
                        sendOverdueMaintenanceAlert($user, $vehicle, $maintenance, $emailService, $smsService, $preferencesRepo, $logger);
                        $maintenanceRemindersSent++;
                    }
                }
            } else {
                sendOverdueMaintenanceAlert($technician, $vehicle, $maintenance, $emailService, $smsService, $preferencesRepo, $logger);
                $maintenanceRemindersSent++;
            }
            
        } catch (\Exception $e) {
            $maintenanceErrors++;
            $logger->error("Erreur lors du traitement de l'entretien en retard ID {$maintenance['id']} : " . $e->getMessage());
        }
    }
    
    $logger->success("Rappels d'entretien : {$maintenanceRemindersSent} envoyés, {$maintenanceErrors} erreurs");
    
    // 2. Rappels d'interventions (si applicable)
    $logger->log("--- TRAITEMENT DES RAPPELS D'INTERVENTIONS ---");
    
    // Ici on pourrait ajouter la logique pour les rappels d'interventions
    // Par exemple, rappeler les interventions non terminées depuis X jours
    
    // 3. Nettoyage des anciens logs
    cleanupOldLogs($container, $logger);
    
    // 4. Statistiques finales
    logFinalStats($maintenanceRemindersSent, $maintenanceErrors, $logger);
    
    $logger->success("=== SCRIPT DE RAPPELS TERMINÉ AVEC SUCCÈS ===");
    
} catch (\Exception $e) {
    $logger->error("ERREUR CRITIQUE : " . $e->getMessage());
    $logger->error("Trace : " . $e->getTraceAsString());
    exit(1);
}

/**
 * Envoie un rappel d'entretien à un utilisateur
 */
function sendMaintenanceReminder($user, $vehicle, $maintenance, $emailService, $smsService, $preferencesRepo, $logger)
{
    try {
        $userId = $user->getId();
        $preferences = $preferencesRepo->findByUserId($userId);
        
        if (!$preferences) {
            $logger->log("Aucune préférence trouvée pour l'utilisateur ID {$userId}");
            return;
        }
        
        $daysUntilDue = calculateDaysUntilDue($maintenance['due_date']);
        $priority = $daysUntilDue <= 1 ? 'critical' : ($daysUntilDue <= 3 ? 'high' : 'medium');
        
        // Email
        if ($preferences['email_notifications'] && $preferences['maintenance_reminders']) {
            $emailSent = $emailService->sendMaintenanceReminderNotification($userId, $vehicle['name'] ?? 'Véhicule inconnu', $maintenance['maintenance_type'], $maintenance['due_date']);
            if ($emailSent) {
                $logger->log("Email de rappel d'entretien envoyé à {$user->getEmail()}");
            }
        }
        
        // SMS
        if ($preferences['sms_notifications'] && $preferences['maintenance_reminders'] && !empty($user->getPhone())) {
            $smsSent = $smsService->sendMaintenanceReminderSms($userId, $vehicle['name'] ?? 'Véhicule inconnu', $maintenance['maintenance_type'], $maintenance['due_date']);
            if ($smsSent) {
                $logger->log("SMS de rappel d'entretien envoyé à {$user->getPhone()}");
            }
        }
        
    } catch (\Exception $e) {
        $logger->error("Erreur lors de l'envoi du rappel d'entretien : " . $e->getMessage());
    }
}

/**
 * Envoie une alerte d'entretien en retard
 */
function sendOverdueMaintenanceAlert($user, $vehicle, $maintenance, $emailService, $smsService, $preferencesRepo, $logger)
{
    try {
        $userId = $user->getId();
        $preferences = $preferencesRepo->findByUserId($userId);
        
        if (!$preferences) {
            return;
        }
        
        // Email
        if ($preferences['email_notifications'] && $preferences['critical_alerts']) {
            $emailSent = $emailService->sendCriticalAlertNotification($userId, 'maintenance_overdue', "Entretien en retard: {$maintenance['maintenance_type']}", $vehicle['name'] ?? 'Véhicule inconnu');
            if ($emailSent) {
                $logger->log("Email d'alerte d'entretien en retard envoyé à {$user->getEmail()}");
            }
        }
        
        // SMS
        if ($preferences['sms_notifications'] && $preferences['critical_alerts'] && !empty($user->getPhone())) {
            $smsSent = $smsService->sendCriticalAlertSms($userId, 'maintenance_overdue', "Entretien en retard: {$maintenance['maintenance_type']}", $vehicle['name'] ?? 'Véhicule inconnu');
            if ($smsSent) {
                $logger->log("SMS d'alerte d'entretien en retard envoyé à {$user->getPhone()}");
            }
        }
        
    } catch (\Exception $e) {
        $logger->error("Erreur lors de l'envoi de l'alerte d'entretien en retard : " . $e->getMessage());
    }
}

/**
 * Calcule le nombre de jours jusqu'à l'échéance
 */
function calculateDaysUntilDue($dueDate)
{
    $due = new \DateTime($dueDate);
    $now = new \DateTime();
    $diff = $now->diff($due);
    return $diff->days * ($due > $now ? 1 : -1);
}

/**
 * Nettoie les anciens logs
 */
function cleanupOldLogs($container, $logger)
{
    try {
        $logsRepo = $container->get(\App\Repository\NotificationLogsRepository::class);
        
        // Supprimer les logs de plus de 30 jours
        $deletedCount = $logsRepo->deleteOldLogs(30);
        
        if ($deletedCount > 0) {
            $logger->log("Nettoyage : {$deletedCount} anciens logs supprimés");
        }
        
    } catch (\Exception $e) {
        $logger->error("Erreur lors du nettoyage des logs : " . $e->getMessage());
    }
}

/**
 * Affiche les statistiques finales
 */
function logFinalStats($maintenanceRemindersSent, $maintenanceErrors, $logger)
{
    $logger->log("=== STATISTIQUES FINALES ===");
    $logger->log("Rappels d'entretien envoyés : {$maintenanceRemindersSent}");
    $logger->log("Erreurs rencontrées : {$maintenanceErrors}");
    $logger->log("Taux de succès : " . ($maintenanceRemindersSent > 0 ? round((($maintenanceRemindersSent - $maintenanceErrors) / $maintenanceRemindersSent) * 100, 2) : 100) . "%");
}
?>