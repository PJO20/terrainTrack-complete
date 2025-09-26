<?php

namespace App\Controller;

use App\Service\TwigService;
use App\Service\SessionManager;
use App\Repository\NotificationPreferencesRepository;
use App\Repository\UserRepository;
use App\Service\EmailNotificationService;
use App\Service\SmsNotificationService;

class NotificationPreferencesController
{
    private TwigService $twig;
    private SessionManager $sessionManager;
    private NotificationPreferencesRepository $preferencesRepository;
    private UserRepository $userRepository;
    private EmailNotificationService $emailService;
    private SmsNotificationService $smsService;

    public function __construct(
        TwigService $twig,
        SessionManager $sessionManager,
        NotificationPreferencesRepository $preferencesRepository,
        UserRepository $userRepository,
        EmailNotificationService $emailService,
        SmsNotificationService $smsService
    ) {
        $this->twig = $twig;
        $this->sessionManager = $sessionManager;
        $this->preferencesRepository = $preferencesRepository;
        $this->userRepository = $userRepository;
        $this->emailService = $emailService;
        $this->smsService = $smsService;
    }

    /**
     * Affiche la page des préférences de notification
     */
    public function index(): string
    {
        $this->sessionManager->requireLogin();
        
        $userId = $this->sessionManager->getCurrentUser()['id'];
        $user = $this->userRepository->findById($userId);
        $preferences = $this->preferencesRepository->findByUserId($userId);
        
        // Créer des préférences par défaut si elles n'existent pas
        if (!$preferences) {
            $this->preferencesRepository->createDefaultPreferences($userId);
            $preferences = $this->preferencesRepository->findByUserId($userId);
        }
        
        // Récupérer les statistiques de notification
        $stats = $this->getNotificationStats($userId);
        
        return $this->twig->render('notifications/preferences.html.twig', [
            'user' => $user,
            'preferences' => $preferences,
            'stats' => $stats,
            'emailConfig' => $this->emailService->testSmtpConfiguration(),
            'smsConfig' => $this->smsService->testSmsConfiguration()
        ]);
    }

    /**
     * Met à jour les préférences de notification
     */
    public function update(): void
    {
        $this->sessionManager->requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /notifications/preferences');
            exit;
        }
        
        $userId = $this->sessionManager->getCurrentUser()['id'];
        
        try {
            $preferences = [
                'user_id' => $userId,
                'email_notifications' => isset($_POST['email_notifications']),
                'sms_notifications' => isset($_POST['sms_notifications']),
                'intervention_assignments' => isset($_POST['intervention_assignments']),
                'maintenance_reminders' => isset($_POST['maintenance_reminders']),
                'critical_alerts' => isset($_POST['critical_alerts']),
                'reminder_frequency_days' => (int)($_POST['reminder_frequency_days'] ?? 7)
            ];
            
            $success = $this->preferencesRepository->save($preferences);
            
            if ($success) {
                // Mettre à jour les informations de contact de l'utilisateur
                $this->updateUserContactInfo($userId);
                
                header('Location: /notifications/preferences?success=1');
            } else {
                header('Location: /notifications/preferences?error=1');
            }
            exit;
            
        } catch (\Exception $e) {
            error_log("Erreur lors de la mise à jour des préférences: " . $e->getMessage());
            header('Location: /notifications/preferences?error=1');
            exit;
        }
    }

    /**
     * Teste l'envoi d'un email de test
     */
    public function testEmail(): void
    {
        $this->sessionManager->requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /notifications/preferences');
            exit;
        }
        
        $userId = $this->sessionManager->getCurrentUser()['id'];
        $user = $this->userRepository->findById($userId);
        
        if (!$user) {
            header('Location: /notifications/preferences?error=user_not_found');
            exit;
        }
        
        $email = $user->getEmail();
        $success = $this->emailService->sendTestEmail($email, "Test de notification TerrainTrack");
        
        if ($success) {
            header('Location: /notifications/preferences?test_email=success');
        } else {
            header('Location: /notifications/preferences?test_email=error');
        }
        exit;
    }

    /**
     * Teste l'envoi d'un SMS de test
     */
    public function testSms(): void
    {
        $this->sessionManager->requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /notifications/preferences');
            exit;
        }
        
        $userId = $this->sessionManager->getCurrentUser()['id'];
        $user = $this->userRepository->findById($userId);
        
        if (!$user || empty($user->getPhone())) {
            header('Location: /notifications/preferences?error=no_phone');
            exit;
        }
        
        $phone = $user->getPhone();
        $success = $this->smsService->sendTestSms($phone, "Test de notification TerrainTrack");
        
        if ($success) {
            header('Location: /notifications/preferences?test_sms=success');
        } else {
            header('Location: /notifications/preferences?test_sms=error');
        }
        exit;
    }

    /**
     * Met à jour les informations de contact de l'utilisateur
     */
    private function updateUserContactInfo(int $userId): void
    {
        try {
            $updateData = [];
            
            if (isset($_POST['notification_email']) && !empty($_POST['notification_email'])) {
                $updateData['notification_email'] = $_POST['notification_email'];
            }
            
            if (isset($_POST['phone']) && !empty($_POST['phone'])) {
                $updateData['phone'] = $_POST['phone'];
            }
            
            if (isset($_POST['notification_sms'])) {
                $updateData['notification_sms'] = isset($_POST['notification_sms']);
            }
            
            if (!empty($updateData)) {
                $this->userRepository->update($userId, $updateData);
            }
            
        } catch (\Exception $e) {
            error_log("Erreur lors de la mise à jour des informations de contact: " . $e->getMessage());
        }
    }

    /**
     * Récupère les statistiques de notification pour l'utilisateur
     */
    private function getNotificationStats(int $userId): array
    {
        try {
            $logsRepo = new \App\Repository\NotificationLogsRepository(
                \App\Container\Container::getInstance()->get(\PDO::class)
            );
            
            $recentLogs = $logsRepo->findByUserId($userId, 10);
            $emailStats = $logsRepo->getStatsByType('email', $userId);
            $smsStats = $logsRepo->getStatsByType('sms', $userId);
            
            return [
                'recent_logs' => $recentLogs,
                'email_stats' => $emailStats,
                'sms_stats' => $smsStats,
                'total_notifications' => count($recentLogs)
            ];
            
        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération des statistiques: " . $e->getMessage());
            return [
                'recent_logs' => [],
                'email_stats' => [],
                'sms_stats' => [],
                'total_notifications' => 0
            ];
        }
    }

    /**
     * Affiche l'historique des notifications
     */
    public function history(): string
    {
        $this->sessionManager->requireLogin();
        
        $userId = $this->sessionManager->getCurrentUser()['id'];
        $stats = $this->getNotificationStats($userId);
        
        return $this->twig->render('notifications/history.html.twig', [
            'stats' => $stats,
            'user' => $this->sessionManager->getCurrentUser()
        ]);
    }

    /**
     * Supprime un log de notification
     */
    public function deleteLog(): void
    {
        $this->sessionManager->requireLogin();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /notifications/history');
            exit;
        }
        
        $logId = (int)($_POST['log_id'] ?? 0);
        $userId = $this->sessionManager->getCurrentUser()['id'];
        
        if ($logId <= 0) {
            header('Location: /notifications/history?error=invalid_id');
            exit;
        }
        
        try {
            $logsRepo = new \App\Repository\NotificationLogsRepository(
                \App\Container\Container::getInstance()->get(\PDO::class)
            );
            
            // Vérifier que le log appartient à l'utilisateur
            $log = $logsRepo->findById($logId);
            if (!$log || $log['user_id'] != $userId) {
                header('Location: /notifications/history?error=unauthorized');
                exit;
            }
            
            $success = $logsRepo->delete($logId);
            
            if ($success) {
                header('Location: /notifications/history?success=deleted');
            } else {
                header('Location: /notifications/history?error=delete_failed');
            }
            exit;
            
        } catch (\Exception $e) {
            error_log("Erreur lors de la suppression du log: " . $e->getMessage());
            header('Location: /notifications/history?error=1');
            exit;
        }
    }
}



