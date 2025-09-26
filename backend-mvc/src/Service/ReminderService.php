<?php

namespace App\Service;

use App\Repository\MaintenanceSchedulesRepository;
use App\Repository\NotificationPreferencesRepository;
use App\Repository\UserRepository;

class ReminderService
{
    private MaintenanceSchedulesRepository $maintenanceRepository;
    private NotificationPreferencesRepository $preferencesRepository;
    private UserRepository $userRepository;
    private EmailNotificationService $emailService;
    private SmsNotificationService $smsService;

    public function __construct(
        MaintenanceSchedulesRepository $maintenanceRepository,
        NotificationPreferencesRepository $preferencesRepository,
        UserRepository $userRepository,
        EmailNotificationService $emailService,
        SmsNotificationService $smsService
    ) {
        $this->maintenanceRepository = $maintenanceRepository;
        $this->preferencesRepository = $preferencesRepository;
        $this->userRepository = $userRepository;
        $this->emailService = $emailService;
        $this->smsService = $smsService;
    }

    /**
     * Traite tous les rappels automatiques
     */
    public function processAllReminders(): array
    {
        $results = [
            'maintenance_reminders' => $this->processMaintenanceReminders(),
            'intervention_reminders' => $this->processInterventionReminders(),
            'critical_alerts' => $this->processCriticalAlerts()
        ];

        return $results;
    }

    /**
     * Traite les rappels d'entretien
     */
    public function processMaintenanceReminders(): array
    {
        $results = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];

        try {
            // Récupérer les entretiens à échéance
            $dueMaintenances = $this->maintenanceRepository->getDueMaintenances();
            
            foreach ($dueMaintenances as $maintenance) {
                $results['processed']++;
                
                try {
                    $technicianId = $maintenance['assigned_technician_id'];
                    
                    if (!$technicianId) {
                        $results['errors'][] = "Aucun technicien assigné pour l'entretien ID {$maintenance['id']}";
                        continue;
                    }

                    // Vérifier les préférences de notification
                    $preferences = $this->preferencesRepository->findByUserId($technicianId);
                    
                    if (!$preferences) {
                        $results['errors'][] = "Aucune préférence trouvée pour l'utilisateur ID {$technicianId}";
                        continue;
                    }

                    $sent = false;

                    // Envoyer par email si activé
                    if ($preferences['email_notifications'] && $preferences['maintenance_reminders']) {
                        $emailSent = $this->emailService->sendMaintenanceReminderNotification(
                            $technicianId,
                            $maintenance['vehicle_name'] ?? 'Véhicule inconnu',
                            $maintenance['maintenance_type'],
                            $maintenance['due_date'],
                            $maintenance['priority']
                        );
                        
                        if ($emailSent) {
                            $sent = true;
                        }
                    }

                    // Envoyer par SMS si activé
                    if ($preferences['sms_notifications'] && $preferences['maintenance_reminders']) {
                        $smsSent = $this->smsService->sendMaintenanceReminderSms(
                            $technicianId,
                            $maintenance['vehicle_name'] ?? 'Véhicule inconnu',
                            $maintenance['maintenance_type'],
                            $maintenance['due_date'],
                            $maintenance['priority']
                        );
                        
                        if ($smsSent) {
                            $sent = true;
                        }
                    }

                    if ($sent) {
                        $results['sent']++;
                        
                        // Marquer comme notifié
                        $this->maintenanceRepository->markAsNotified($maintenance['id']);
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Aucune notification envoyée pour l'entretien ID {$maintenance['id']}";
                    }

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Erreur pour l'entretien ID {$maintenance['id']}: " . $e->getMessage();
                }
            }

        } catch (\Exception $e) {
            $results['errors'][] = "Erreur générale lors du traitement des rappels d'entretien: " . $e->getMessage();
        }

        return $results;
    }

    /**
     * Traite les rappels d'intervention
     */
    public function processInterventionReminders(): array
    {
        $results = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];

        try {
            // Récupérer les interventions à rappeler
            $interventions = $this->getInterventionsToRemind();
            
            foreach ($interventions as $intervention) {
                $results['processed']++;
                
                try {
                    $technicianId = $intervention['assigned_technician_id'];
                    
                    if (!$technicianId) {
                        $results['errors'][] = "Aucun technicien assigné pour l'intervention ID {$intervention['id']}";
                        continue;
                    }

                    // Vérifier les préférences de notification
                    $preferences = $this->preferencesRepository->findByUserId($technicianId);
                    
                    if (!$preferences) {
                        $results['errors'][] = "Aucune préférence trouvée pour l'utilisateur ID {$technicianId}";
                        continue;
                    }

                    $sent = false;

                    // Envoyer par email si activé
                    if ($preferences['email_notifications'] && $preferences['intervention_assignments']) {
                        $emailSent = $this->emailService->sendInterventionAssignmentNotification(
                            $technicianId,
                            $intervention['title'],
                            $intervention['description'],
                            $intervention['scheduled_date'],
                            $intervention['location']
                        );
                        
                        if ($emailSent) {
                            $sent = true;
                        }
                    }

                    // Envoyer par SMS si activé
                    if ($preferences['sms_notifications'] && $preferences['intervention_assignments']) {
                        $smsSent = $this->smsService->sendInterventionAssignmentSms(
                            $technicianId,
                            $intervention['title'],
                            $intervention['scheduled_date'],
                            $intervention['location']
                        );
                        
                        if ($smsSent) {
                            $sent = true;
                        }
                    }

                    if ($sent) {
                        $results['sent']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Aucune notification envoyée pour l'intervention ID {$intervention['id']}";
                    }

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Erreur pour l'intervention ID {$intervention['id']}: " . $e->getMessage();
                }
            }

        } catch (\Exception $e) {
            $results['errors'][] = "Erreur générale lors du traitement des rappels d'intervention: " . $e->getMessage();
        }

        return $results;
    }

    /**
     * Traite les alertes critiques
     */
    public function processCriticalAlerts(): array
    {
        $results = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'errors' => []
        ];

        try {
            // Récupérer les alertes critiques
            $criticalAlerts = $this->getCriticalAlerts();
            
            foreach ($criticalAlerts as $alert) {
                $results['processed']++;
                
                try {
                    $technicianId = $alert['technician_id'];
                    
                    if (!$technicianId) {
                        $results['errors'][] = "Aucun technicien assigné pour l'alerte ID {$alert['id']}";
                        continue;
                    }

                    // Vérifier les préférences de notification
                    $preferences = $this->preferencesRepository->findByUserId($technicianId);
                    
                    if (!$preferences) {
                        $results['errors'][] = "Aucune préférence trouvée pour l'utilisateur ID {$technicianId}";
                        continue;
                    }

                    $sent = false;

                    // Envoyer par email si activé
                    if ($preferences['email_notifications'] && $preferences['critical_alerts']) {
                        $emailSent = $this->emailService->sendCriticalAlertNotification(
                            $technicianId,
                            $alert['alert_type'],
                            $alert['message'],
                            $alert['vehicle_name'] ?? null
                        );
                        
                        if ($emailSent) {
                            $sent = true;
                        }
                    }

                    // Envoyer par SMS si activé
                    if ($preferences['sms_notifications'] && $preferences['critical_alerts']) {
                        $smsSent = $this->smsService->sendCriticalAlertSms(
                            $technicianId,
                            $alert['alert_type'],
                            $alert['message'],
                            $alert['vehicle_name'] ?? null
                        );
                        
                        if ($smsSent) {
                            $sent = true;
                        }
                    }

                    if ($sent) {
                        $results['sent']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Aucune notification envoyée pour l'alerte ID {$alert['id']}";
                    }

                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Erreur pour l'alerte ID {$alert['id']}: " . $e->getMessage();
                }
            }

        } catch (\Exception $e) {
            $results['errors'][] = "Erreur générale lors du traitement des alertes critiques: " . $e->getMessage();
        }

        return $results;
    }

    /**
     * Récupère les interventions à rappeler
     */
    private function getInterventionsToRemind(): array
    {
        // Cette méthode devrait récupérer les interventions qui nécessitent un rappel
        // Pour l'instant, on retourne un tableau vide
        // Dans une implémentation complète, on interrogerait la table des interventions
        return [];
    }

    /**
     * Récupère les alertes critiques
     */
    private function getCriticalAlerts(): array
    {
        // Cette méthode devrait récupérer les alertes critiques
        // Pour l'instant, on retourne un tableau vide
        // Dans une implémentation complète, on interrogerait la table des alertes
        return [];
    }

    /**
     * Planifie un rappel pour une date donnée
     */
    public function scheduleReminder(
        int $userId,
        string $type,
        string $subject,
        string $message,
        \DateTime $scheduledDate
    ): bool {
        try {
            // Cette méthode devrait ajouter un rappel à la file d'attente
            // Pour l'instant, on simule le succès
            error_log("Rappel planifié pour l'utilisateur {$userId} le {$scheduledDate->format('Y-m-d H:i:s')}");
            return true;
        } catch (\Exception $e) {
            error_log("Erreur lors de la planification du rappel: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Annule un rappel planifié
     */
    public function cancelReminder(int $reminderId): bool
    {
        try {
            // Cette méthode devrait annuler un rappel dans la file d'attente
            // Pour l'instant, on simule le succès
            error_log("Rappel {$reminderId} annulé");
            return true;
        } catch (\Exception $e) {
            error_log("Erreur lors de l'annulation du rappel: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les statistiques des rappels
     */
    public function getReminderStats(): array
    {
        try {
            $maintenanceStats = $this->maintenanceRepository->getReminderStats();
            $interventionStats = $this->getInterventionReminderStats();
            $alertStats = $this->getAlertStats();

            return [
                'maintenance' => $maintenanceStats,
                'interventions' => $interventionStats,
                'alerts' => $alertStats,
                'total_processed' => $maintenanceStats['total'] + $interventionStats['total'] + $alertStats['total'],
                'total_sent' => $maintenanceStats['sent'] + $interventionStats['sent'] + $alertStats['sent'],
                'total_failed' => $maintenanceStats['failed'] + $interventionStats['failed'] + $alertStats['failed']
            ];
        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération des statistiques: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les statistiques des rappels d'intervention
     */
    private function getInterventionReminderStats(): array
    {
        // Simulation des statistiques
        return [
            'total' => 0,
            'sent' => 0,
            'failed' => 0
        ];
    }

    /**
     * Récupère les statistiques des alertes
     */
    private function getAlertStats(): array
    {
        // Simulation des statistiques
        return [
            'total' => 0,
            'sent' => 0,
            'failed' => 0
        ];
    }
}



