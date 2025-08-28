<?php

namespace App\Controller;

use App\Service\TwigService;
use App\Model\NotificationModel;

class NotificationController
{
    private TwigService $twig;
    private NotificationModel $notificationModel;

    public function __construct(TwigService $twig)
    {
        $this->twig = $twig;
        $this->notificationModel = new NotificationModel();
    }

    public function index()
    {
        $notifications = $this->notificationModel->findAll();
        $total = count($notifications);
        $unread = count(array_filter($notifications, fn($n) => !$n['read']));
        
        // Simuler les alertes pour les stats
        $alerts = count(array_filter($notifications, fn($n) => $n['type'] === 'Alerte'));

        return $this->twig->render('notifications.html.twig', [
            'notifications' => $notifications,
            'total' => $total,
            'unread' => $unread,
            'alerts' => $alerts
        ]);
    }
} 