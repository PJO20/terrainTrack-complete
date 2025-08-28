<?php

namespace App\Service;

use App\Entity\Notification;
use App\Repository\NotificationRepository;

class NotificationService
{
    private NotificationRepository $notificationRepository;

    public function __construct(NotificationRepository $notificationRepository)
    {
        $this->notificationRepository = $notificationRepository;
    }

    /**
     * Envoie une notification d'information
     */
    public function sendInfo(
        string $title, 
        string $description, 
        ?string $relatedTo = null,
        ?int $recipientId = null,
        ?string $relatedType = null,
        ?int $relatedId = null
    ): bool {
        $notification = Notification::createInfoNotification($title, $description, $relatedTo);
        
        if ($recipientId) {
            $notification->setRecipientId($recipientId);
        }
        
        if ($relatedType) {
            $notification->setRelatedType($relatedType);
        }
        
        if ($relatedId) {
            $notification->setRelatedId($relatedId);
        }

        return $this->notificationRepository->save($notification);
    }

    /**
     * Envoie une notification d'avertissement
     */
    public function sendWarning(
        string $title, 
        string $description, 
        ?string $relatedTo = null,
        ?int $recipientId = null,
        ?string $relatedType = null,
        ?int $relatedId = null
    ): bool {
        $notification = Notification::createWarningNotification($title, $description, $relatedTo);
        
        if ($recipientId) {
            $notification->setRecipientId($recipientId);
        }
        
        if ($relatedType) {
            $notification->setRelatedType($relatedType);
        }
        
        if ($relatedId) {
            $notification->setRelatedId($relatedId);
        }

        return $this->notificationRepository->save($notification);
    }

    /**
     * Envoie une notification d'alerte critique
     */
    public function sendAlert(
        string $title, 
        string $description, 
        ?string $relatedTo = null,
        ?int $recipientId = null,
        ?string $relatedType = null,
        ?int $relatedId = null
    ): bool {
        $notification = Notification::createAlertNotification($title, $description, $relatedTo);
        
        if ($recipientId) {
            $notification->setRecipientId($recipientId);
        }
        
        if ($relatedType) {
            $notification->setRelatedType($relatedType);
        }
        
        if ($relatedId) {
            $notification->setRelatedId($relatedId);
        }

        return $this->notificationRepository->save($notification);
    }

    /**
     * Envoie une notification de succès
     */
    public function sendSuccess(
        string $title, 
        string $description, 
        ?string $relatedTo = null,
        ?int $recipientId = null,
        ?string $relatedType = null,
        ?int $relatedId = null
    ): bool {
        $notification = Notification::createSuccessNotification($title, $description, $relatedTo);
        
        if ($recipientId) {
            $notification->setRecipientId($recipientId);
        }
        
        if ($relatedType) {
            $notification->setRelatedType($relatedType);
        }
        
        if ($relatedId) {
            $notification->setRelatedId($relatedId);
        }

        return $this->notificationRepository->save($notification);
    }

    /**
     * Notifications spécifiques aux véhicules
     */
    public function sendVehicleNotification(
        int $vehicleId,
        string $vehicleName,
        string $title,
        string $description,
        string $type = 'info'
    ): bool {
        $relatedTo = "Véhicule: $vehicleName";
        
        switch ($type) {
            case 'warning':
                return $this->sendWarning($title, $description, $relatedTo, null, Notification::RELATED_VEHICLE, $vehicleId);
            case 'alert':
                return $this->sendAlert($title, $description, $relatedTo, null, Notification::RELATED_VEHICLE, $vehicleId);
            case 'success':
                return $this->sendSuccess($title, $description, $relatedTo, null, Notification::RELATED_VEHICLE, $vehicleId);
            default:
                return $this->sendInfo($title, $description, $relatedTo, null, Notification::RELATED_VEHICLE, $vehicleId);
        }
    }

    /**
     * Notifications spécifiques aux interventions
     */
    public function sendInterventionNotification(
        int $interventionId,
        string $interventionTitle,
        string $title,
        string $description,
        string $type = 'info'
    ): bool {
        $relatedTo = "Intervention: $interventionTitle";
        
        switch ($type) {
            case 'warning':
                return $this->sendWarning($title, $description, $relatedTo, null, Notification::RELATED_INTERVENTION, $interventionId);
            case 'alert':
                return $this->sendAlert($title, $description, $relatedTo, null, Notification::RELATED_INTERVENTION, $interventionId);
            case 'success':
                return $this->sendSuccess($title, $description, $relatedTo, null, Notification::RELATED_INTERVENTION, $interventionId);
            default:
                return $this->sendInfo($title, $description, $relatedTo, null, Notification::RELATED_INTERVENTION, $interventionId);
        }
    }

    /**
     * Notifications spécifiques aux équipes
     */
    public function sendTeamNotification(
        int $teamId,
        string $teamName,
        string $title,
        string $description,
        string $type = 'info'
    ): bool {
        $relatedTo = "Équipe: $teamName";
        
        switch ($type) {
            case 'warning':
                return $this->sendWarning($title, $description, $relatedTo, null, Notification::RELATED_TEAM, $teamId);
            case 'alert':
                return $this->sendAlert($title, $description, $relatedTo, null, Notification::RELATED_TEAM, $teamId);
            case 'success':
                return $this->sendSuccess($title, $description, $relatedTo, null, Notification::RELATED_TEAM, $teamId);
            default:
                return $this->sendInfo($title, $description, $relatedTo, null, Notification::RELATED_TEAM, $teamId);
        }
    }

    /**
     * Notifications système
     */
    public function sendSystemNotification(
        string $title,
        string $description,
        string $type = 'info'
    ): bool {
        $relatedTo = "Système: TerrainTrack";
        
        switch ($type) {
            case 'warning':
                return $this->sendWarning($title, $description, $relatedTo, null, Notification::RELATED_SYSTEM);
            case 'alert':
                return $this->sendAlert($title, $description, $relatedTo, null, Notification::RELATED_SYSTEM);
            case 'success':
                return $this->sendSuccess($title, $description, $relatedTo, null, Notification::RELATED_SYSTEM);
            default:
                return $this->sendInfo($title, $description, $relatedTo, null, Notification::RELATED_SYSTEM);
        }
    }

    /**
     * Notifications automatiques basées sur des événements
     */
    
    /**
     * Notification quand une intervention est créée
     */
    public function notifyInterventionCreated(int $interventionId, string $interventionTitle, string $teamName): bool
    {
        return $this->sendInterventionNotification(
            $interventionId,
            $interventionTitle,
            "Nouvelle intervention créée",
            "Une nouvelle intervention \"$interventionTitle\" a été créée et assignée à l'équipe $teamName",
            'info'
        );
    }

    /**
     * Notification quand une intervention est terminée
     */
    public function notifyInterventionCompleted(int $interventionId, string $interventionTitle, string $teamName): bool
    {
        return $this->sendInterventionNotification(
            $interventionId,
            $interventionTitle,
            "Intervention terminée",
            "L'intervention \"$interventionTitle\" a été terminée avec succès par l'équipe $teamName",
            'success'
        );
    }

    /**
     * Notification pour véhicule en maintenance
     */
    public function notifyVehicleMaintenance(int $vehicleId, string $vehicleName, string $reason): bool
    {
        return $this->sendVehicleNotification(
            $vehicleId,
            $vehicleName,
            "Maintenance requise",
            "Le véhicule $vehicleName nécessite une maintenance: $reason",
            'warning'
        );
    }

    /**
     * Notification pour véhicule en panne critique
     */
    public function notifyVehicleCriticalIssue(int $vehicleId, string $vehicleName, string $issue): bool
    {
        return $this->sendVehicleNotification(
            $vehicleId,
            $vehicleName,
            "Problème critique détecté",
            "URGENT: Le véhicule $vehicleName a un problème critique: $issue",
            'alert'
        );
    }

    /**
     * Notification pour nouveau membre d'équipe
     */
    public function notifyNewTeamMember(int $teamId, string $teamName, string $memberName): bool
    {
        return $this->sendTeamNotification(
            $teamId,
            $teamName,
            "Nouveau membre d'équipe",
            "$memberName a rejoint l'équipe $teamName",
            'info'
        );
    }

    /**
     * Récupère les notifications pour un utilisateur
     */
    public function getNotificationsForUser(?int $userId = null, int $limit = 50): array
    {
        // Si aucun userId fourni, récupérer l'utilisateur actuel
        if ($userId === null) {
            $userId = $this->getCurrentUserId();
        }
        
        return $this->notificationRepository->findAll($userId, null, null, null, $limit);
    }

    /**
     * Récupère les notifications récentes
     */
    public function getRecentNotifications(int $limit = 10): array
    {
        // **CORRECTION** : Passer l'ID de l'utilisateur connecté
        $userId = $this->getCurrentUserId();
        return $this->notificationRepository->findRecent($userId, $limit);
    }

    /**
     * Compte les notifications non lues
     */
    public function countUnreadNotifications(): int
    {
        // **CORRECTION** : Passer l'ID de l'utilisateur connecté
        $userId = $this->getCurrentUserId();
        return $this->notificationRepository->countUnread($userId);
    }
    
    /**
     * Récupère l'ID de l'utilisateur connecté
     */
    private function getCurrentUserId(): ?int
    {
        // Démarrer la session si pas encore fait
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Récupérer l'ID utilisateur depuis la session
        return $_SESSION['user']['id'] ?? null;
    }

    /**
     * Marque une notification comme lue
     */
    public function markAsRead(int $notificationId): bool
    {
        return $this->notificationRepository->markAsRead($notificationId);
    }

    /**
     * Marque plusieurs notifications comme lues
     */
    public function markMultipleAsRead(array $notificationIds): bool
    {
        return $this->notificationRepository->markMultipleAsRead($notificationIds);
    }

    /**
     * Marque plusieurs notifications comme non lues
     */
    public function markMultipleAsUnread(array $notificationIds): bool
    {
        return $this->notificationRepository->markMultipleAsUnread($notificationIds);
    }

    /**
     * Supprime plusieurs notifications
     */
    public function deleteMultiple(array $notificationIds): bool
    {
        return $this->notificationRepository->deleteMultiple($notificationIds);
    }

    /**
     * Supprime une notification
     */
    public function deleteNotification(int $notificationId): bool
    {
        return $this->notificationRepository->delete($notificationId);
    }

    /**
     * Nettoie les notifications expirées
     */
    public function cleanupExpired(): int
    {
        return $this->notificationRepository->cleanupExpired();
    }
} 