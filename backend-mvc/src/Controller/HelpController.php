<?php

namespace App\Controller;

use App\Service\TwigService;

class HelpController
{
    private TwigService $twig;

    public function __construct(TwigService $twig)
    {
        $this->twig = $twig;
    }

    public function index()
    {
        // Données pour la page d'aide
        $helpData = [
            'user' => [
                'name' => 'Thomas Martin',
                'email' => 'thomas.martin@terraintrack.com'
            ],
            'articles' => [
                [
                    'id' => 1,
                    'title' => 'Premiers pas avec TerrainTrack',
                    'description' => 'Découvrez les fonctionnalités principales et configurez votre espace de travail',
                    'icon' => 'fa-bolt',
                    'duration' => '10 min',
                    'category' => 'getting-started'
                ],
                [
                    'id' => 2,
                    'title' => 'Gestion des véhicules',
                    'description' => 'Apprenez à ajouter, modifier et suivre vos véhicules',
                    'icon' => 'fa-truck',
                    'duration' => '15 min',
                    'category' => 'vehicles'
                ],
                [
                    'id' => 3,
                    'title' => 'Planification des interventions',
                    'description' => 'Créez et gérez efficacement vos interventions',
                    'icon' => 'fa-calendar',
                    'duration' => '20 min',
                    'category' => 'interventions'
                ]
            ],
            'categories' => [
                [
                    'id' => 'overview',
                    'name' => 'Vue d\'ensemble',
                    'icon' => 'fa-eye',
                    'description' => 'Trouvez des réponses et obtenez de l\'aide',
                    'active' => true
                ],
                [
                    'id' => 'guides',
                    'name' => 'Guides',
                    'icon' => 'fa-book',
                    'description' => 'Apprenez à utiliser toutes les fonctionnalités'
                ],
                [
                    'id' => 'faq',
                    'name' => 'FAQ',
                    'icon' => 'fa-circle-question',
                    'description' => 'Questions fréquemment posées'
                ],
                [
                    'id' => 'contact',
                    'name' => 'Contact',
                    'icon' => 'fa-comments',
                    'description' => 'Obtenez de l\'aide personnalisée'
                ],
                [
                    'id' => 'support',
                    'name' => 'Support',
                    'icon' => 'fa-headset',
                    'description' => 'Assistance technique'
                ]
            ]
        ];

        echo $this->twig->render('help-center.html.twig', $helpData);
    }
} 