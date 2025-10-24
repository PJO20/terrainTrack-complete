<?php

namespace App\Controller;

use App\Service\EnvService;

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
        $preferences = $this->preferencesRepository->findByUserId($userId);
        
        if (!$user) {
            header('Location: /notifications/preferences?error=user_not_found');
            exit;
        }
        
        $email = $user->getEmail();
        $success = $this->emailService->sendTestEmailWithPreferences($email, $preferences, $user);
        
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
    private function getNotificationStats(int $userId, array $filters = []): array
    {
        try {
            // Configuration directe de la base de données
            $host = 'localhost';
            $port = '8889';
            $dbname = 'exemple';
            $username = 'root';
            $password = EnvService::get('DB_PASS', 'root');
            
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ]);
            
            $logsRepo = new \App\Repository\NotificationLogsRepository($pdo);
            
            // Récupérer les logs récents avec filtres
            $recentLogs = $this->getFilteredLogs($logsRepo, $userId, $filters, 10);
            
            // Statistiques par type
            $emailStats = $logsRepo->getStatsByType('email', $userId);
            $smsStats = $logsRepo->getStatsByType('sms', $userId);
            
            // Statistiques globales pour l'utilisateur
            $globalStats = $logsRepo->getGlobalStats($userId);
            
            // Calculer le total des notifications
            $totalNotifications = 0;
            $totalEmailsSent = 0;
            $totalSmsSent = 0;
            $totalFailures = 0;
            
            foreach ($globalStats as $stat) {
                $totalNotifications += (int)$stat['total'];
                if ($stat['notification_type'] === 'email' || strpos($stat['notification_type'], 'reminder') !== false || strpos($stat['notification_type'], 'overdue') !== false) {
                    $totalEmailsSent += (int)$stat['sent'];
                }
                if ($stat['notification_type'] === 'sms') {
                    $totalSmsSent += (int)$stat['sent'];
                }
                $totalFailures += (int)$stat['failed'] + (int)$stat['bounced'];
            }
            
            // Calculer le taux de succès email
            $emailSuccessRate = 0;
            if ($totalEmailsSent > 0) {
                $emailSuccessRate = round(($totalEmailsSent / ($totalEmailsSent + $totalFailures)) * 100, 1);
            }
            
            return [
                'recent_logs' => $recentLogs,
                'email_stats' => [
                    'sent' => $totalEmailsSent,
                    'failed' => $totalFailures,
                    'success_rate' => $emailSuccessRate
                ],
                'sms_stats' => [
                    'sent' => $totalSmsSent,
                    'failed' => $totalFailures,
                    'success_rate' => $emailSuccessRate
                ],
                'total_notifications' => $totalNotifications
            ];
            
        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération des statistiques: " . $e->getMessage());
            return [
                'recent_logs' => [],
                'email_stats' => [
                    'sent' => 0,
                    'failed' => 0,
                    'success_rate' => 0
                ],
                'sms_stats' => [
                    'sent' => 0,
                    'failed' => 0,
                    'success_rate' => 0
                ],
                'total_notifications' => 0
            ];
        }
    }

    /**
     * Récupère les logs filtrés
     */
    private function getFilteredLogs($logsRepo, int $userId, array $filters, int $limit = 10): array
    {
        try {
            // Configuration directe de la base de données
            $host = 'localhost';
            $port = '8889';
            $dbname = 'exemple';
            $username = 'root';
            $password = EnvService::get('DB_PASS', 'root');
            
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ]);
            
            // Construire la requête SQL avec filtres
            $sql = "SELECT * FROM notification_logs WHERE user_id = :user_id";
            $params = ['user_id' => $userId];
            
            // Filtre par type
            if (!empty($filters['type'])) {
                if ($filters['type'] === 'email') {
                    $sql .= " AND (notification_type = 'email' OR notification_type LIKE '%reminder%' OR notification_type LIKE '%overdue%')";
                } elseif ($filters['type'] === 'sms') {
                    $sql .= " AND notification_type = 'sms'";
                }
            }
            
            // Filtre par statut
            if (!empty($filters['status'])) {
                $sql .= " AND status = :status";
                $params['status'] = $filters['status'];
            }
            
            // Filtre par date de début
            if (!empty($filters['date_from'])) {
                $sql .= " AND DATE(sent_at) >= :date_from";
                $params['date_from'] = $filters['date_from'];
            }
            
            // Filtre par date de fin
            if (!empty($filters['date_to'])) {
                $sql .= " AND DATE(sent_at) <= :date_to";
                $params['date_to'] = $filters['date_to'];
            }
            
            $sql .= " ORDER BY sent_at DESC LIMIT :limit";
            $params['limit'] = $limit;
            
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            
            if (isset($params['status'])) {
                $stmt->bindValue(':status', $params['status'], \PDO::PARAM_STR);
            }
            if (isset($params['date_from'])) {
                $stmt->bindValue(':date_from', $params['date_from'], \PDO::PARAM_STR);
            }
            if (isset($params['date_to'])) {
                $stmt->bindValue(':date_to', $params['date_to'], \PDO::PARAM_STR);
            }
            
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            error_log("Erreur lors de la récupération des logs filtrés: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtient le repository des logs de notifications
     */
    private function getLogsRepository(): \App\Repository\NotificationLogsRepository
    {
        // Configuration directe de la base de données
        $host = 'localhost';
        $port = '8889';
        $dbname = 'exemple';
        $username = 'root';
        $password = EnvService::get('DB_PASS', 'root');
        
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
        $pdo = new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ]);
        
        return new \App\Repository\NotificationLogsRepository($pdo);
    }

    /**
     * Affiche l'historique des notifications
     */
    public function history(): string
    {
        $this->sessionManager->requireLogin();
        
        $userId = $this->sessionManager->getCurrentUser()['id'];
        
        // Récupérer les paramètres de filtres
        $filters = [
            'type' => $_GET['type'] ?? '',
            'status' => $_GET['status'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? ''
        ];
        
        // Obtenir les statistiques avec filtres
        $stats = $this->getNotificationStats($userId, $filters);
        
        // Récupérer les logs filtrés
        $filteredLogs = $this->getFilteredLogs($this->getLogsRepository(), $userId, $filters, 50);
        
        // Ajouter les logs filtrés aux statistiques
        $stats['recent_logs'] = $filteredLogs;
        
        return $this->twig->render('notifications/history.html.twig', [
            'stats' => $stats,
            'user' => $this->sessionManager->getCurrentUser(),
            'filters' => $filters
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
            // Configuration directe de la base de données
            $host = 'localhost';
            $port = '8889';
            $dbname = 'exemple';
            $username = 'root';
            $password = EnvService::get('DB_PASS', 'root');
            
            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
            $pdo = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ]);
            
            $logsRepo = new \App\Repository\NotificationLogsRepository($pdo);
            
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



