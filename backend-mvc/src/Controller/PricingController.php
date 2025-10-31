<?php

namespace App\Controller;

use App\Service\TwigService;
use App\Service\SessionManager;

class PricingController
{
    private TwigService $twig;

    public function __construct(TwigService $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Page de tarification
     */
    public function index(): string
    {
        $plans = [
            'essential' => [
                'name' => 'Essentiel',
                'price' => '49',
                'description' => 'Pour les petites équipes',
                'features' => [
                    'Jusqu\'à 5 utilisateurs',
                    'Gestion d\'interventions',
                    'Gestion de flotte (10 véhicules)',
                    'Support email',
                    'Rapports de base'
                ]
            ],
            'pro' => [
                'name' => 'Pro',
                'price' => '99',
                'description' => 'Pour les équipes professionnelles',
                'features' => [
                    'Jusqu\'à 25 utilisateurs',
                    'Gestion d\'interventions illimitée',
                    'Gestion de flotte (100 véhicules)',
                    'Support prioritaire',
                    'Rapports avancés',
                    'Vue cartographique',
                    'Notifications SMS'
                ]
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'price' => '199',
                'description' => 'Pour les grandes organisations',
                'features' => [
                    'Utilisateurs illimités',
                    'Gestion d\'interventions illimitée',
                    'Flotte illimitée',
                    'Support dédié 24/7',
                    'Rapports personnalisés',
                    'API complète',
                    'Formation sur site',
                    'Gestion avancée des permissions'
                ]
            ]
        ];

        // Vérifier si l'utilisateur est vraiment authentifié
        $isAuthenticated = SessionManager::isAuthenticated();
        $user = $isAuthenticated ? SessionManager::getUser() : null;

        return $this->twig->render('public/pricing.html.twig', [
            'title' => 'Tarifs - TerrainTrack',
            'plans' => $plans,
            'isPublic' => true,
            'user' => $user
        ]);
    }
}
