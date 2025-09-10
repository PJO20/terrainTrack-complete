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
            'overviewCards' => [
                [
                    'id' => 'guides',
                    'title' => 'Guides d\'utilisation',
                    'description' => 'Apprenez à utiliser toutes les fonctionnalités',
                    'icon' => 'fa-book',
                    'iconColor' => 'blue'
                ],
                [
                    'id' => 'faq',
                    'title' => 'Questions fréquentes',
                    'description' => 'Trouvez des réponses rapides',
                    'icon' => 'fa-circle-question',
                    'iconColor' => 'green'
                ],
                [
                    'id' => 'contact',
                    'title' => 'Contacter le support',
                    'description' => 'Obtenez de l\'aide personnalisée',
                    'icon' => 'fa-comments',
                    'iconColor' => 'purple'
                ]
            ],
            'popularArticles' => [
                [
                    'id' => 1,
                    'title' => 'Premiers pas avec TerrainTrack',
                    'description' => 'Découvrez les fonctionnalités principales et configurez votre espace de travail',
                    'icon' => 'fa-bolt',
                    'iconColor' => 'blue',
                    'duration' => '10 min'
                ],
                [
                    'id' => 2,
                    'title' => 'Gestion des véhicules',
                    'description' => 'Apprenez à ajouter, modifier et suivre vos véhicules',
                    'icon' => 'fa-truck',
                    'iconColor' => 'green',
                    'duration' => '15 min'
                ],
                [
                    'id' => 3,
                    'title' => 'Planification des interventions',
                    'description' => 'Créez et gérez efficacement vos interventions',
                    'icon' => 'fa-calendar',
                    'iconColor' => 'purple',
                    'duration' => '20 min'
                ]
            ],
            'newsUpdates' => [
                [
                    'id' => 1,
                    'title' => 'Nouvelle fonctionnalité : Rapports avancés',
                    'description' => 'Générez des rapports plus détaillés avec de nouveaux filtres et graphiques.',
                    'timeAgo' => 'Il y a 2 jours'
                ]
            ],
            'faqItems' => [
                [
                    'id' => 1,
                    'question' => 'Comment ajouter un nouveau véhicule ?',
                    'answer' => 'Pour ajouter un nouveau véhicule, allez dans la section "Véhicules", cliquez sur "Nouveau véhicule" et remplissez les informations requises comme la marque, le modèle, l\'année et le type de véhicule.',
                    'category' => 'vehicles',
                    'helpful' => 24,
                    'notHelpful' => 2
                ],
                [
                    'id' => 2,
                    'question' => 'Comment planifier une intervention ?',
                    'answer' => 'Pour planifier une intervention, accédez au module "Interventions", sélectionnez "Nouvelle intervention", choisissez le véhicule concerné, définissez la date et l\'heure, puis assignez l\'équipe responsable.',
                    'category' => 'interventions',
                    'helpful' => 18,
                    'notHelpful' => 1
                ],
                [
                    'id' => 3,
                    'question' => 'Comment créer une équipe ?',
                    'answer' => 'Pour créer une équipe, rendez-vous dans la section "Équipes", cliquez sur "Nouvelle équipe", saisissez le nom de l\'équipe, ajoutez les membres et définissez les rôles de chacun.',
                    'category' => 'teams',
                    'helpful' => 15,
                    'notHelpful' => 3
                ],
                [
                    'id' => 4,
                    'question' => 'Comment générer un rapport ?',
                    'answer' => 'Pour générer un rapport, allez dans la section "Rapports", sélectionnez le type de rapport souhaité, définissez la période et les critères, puis cliquez sur "Générer" pour obtenir votre rapport personnalisé.',
                    'category' => 'reports',
                    'helpful' => 22,
                    'notHelpful' => 1
                ],
                [
                    'id' => 5,
                    'question' => 'Comment modifier mes notifications ?',
                    'answer' => 'Pour modifier vos notifications, accédez à vos paramètres personnels, section "Notifications", et configurez les types d\'alertes que vous souhaitez recevoir par email ou dans l\'application.',
                    'category' => 'notifications',
                    'helpful' => 12,
                    'notHelpful' => 4
                ],
                [
                    'id' => 6,
                    'question' => 'Que faire si un véhicule est en panne ?',
                    'answer' => 'En cas de panne, marquez le véhicule comme "Hors service" dans la section véhicules, créez une intervention d\'urgence, et contactez immédiatement l\'équipe de maintenance pour une réparation prioritaire.',
                    'category' => 'vehicles',
                    'helpful' => 31,
                    'notHelpful' => 2
                ]
            ],
            'guides' => [
                [
                    'id' => 1,
                    'title' => 'Premiers pas avec TerrainTrack',
                    'description' => 'Découvrez les fonctionnalités principales et configurez votre espace de travail',
                    'icon' => 'fa-bolt',
                    'iconColor' => 'blue',
                    'duration' => '10 min',
                    'difficulty' => 'Débutant',
                    'difficultyColor' => 'success',
                    'category' => 'getting-started'
                ],
                [
                    'id' => 2,
                    'title' => 'Gestion des véhicules',
                    'description' => 'Apprenez à ajouter, modifier et suivre vos véhicules',
                    'icon' => 'fa-truck',
                    'iconColor' => 'green',
                    'duration' => '15 min',
                    'difficulty' => 'Débutant',
                    'difficultyColor' => 'success',
                    'category' => 'vehicles'
                ],
                [
                    'id' => 3,
                    'title' => 'Planification des interventions',
                    'description' => 'Créez et gérez efficacement vos interventions',
                    'icon' => 'fa-calendar',
                    'iconColor' => 'purple',
                    'duration' => '20 min',
                    'difficulty' => 'Intermédiaire',
                    'difficultyColor' => 'warning',
                    'category' => 'interventions'
                ],
                [
                    'id' => 4,
                    'title' => 'Collaboration en équipe',
                    'description' => 'Organisez vos équipes et assignez les tâches',
                    'icon' => 'fa-users',
                    'iconColor' => 'orange',
                    'duration' => '12 min',
                    'difficulty' => 'Intermédiaire',
                    'difficultyColor' => 'warning',
                    'category' => 'teams'
                ],
                [
                    'id' => 5,
                    'title' => 'Rapports et analyses',
                    'description' => 'Générez des rapports détaillés et analysez vos performances',
                    'icon' => 'fa-chart-bar',
                    'iconColor' => 'red',
                    'duration' => '18 min',
                    'difficulty' => 'Avancé',
                    'difficultyColor' => 'danger',
                    'category' => 'reports'
                ],
                [
                    'id' => 6,
                    'title' => 'Application mobile',
                    'description' => 'Utilisez TerrainTrack sur le terrain avec l\'app mobile',
                    'icon' => 'fa-mobile',
                    'iconColor' => 'blue',
                    'duration' => '8 min',
                    'difficulty' => 'Débutant',
                    'difficultyColor' => 'success',
                    'category' => 'mobile'
                ]
            ],
            'guideCategories' => [
                [
                    'id' => 'all',
                    'name' => 'Toutes les catégories',
                    'icon' => 'fa-folder',
                    'active' => true
                ],
                [
                    'id' => 'vehicles',
                    'name' => 'Gestion des véhicules',
                    'icon' => 'fa-truck'
                ],
                [
                    'id' => 'interventions',
                    'name' => 'Interventions',
                    'icon' => 'fa-wrench'
                ],
                [
                    'id' => 'teams',
                    'name' => 'Équipes',
                    'icon' => 'fa-users'
                ],
                [
                    'id' => 'reports',
                    'name' => 'Rapports',
                    'icon' => 'fa-chart-bar'
                ],
                [
                    'id' => 'settings',
                    'name' => 'Paramètres',
                    'icon' => 'fa-gear'
                ],
                [
                    'id' => 'notifications',
                    'name' => 'Notifications',
                    'icon' => 'fa-bell'
                ]
            ],
            'mainCategories' => [
                [
                    'id' => 'overview',
                    'name' => 'Vue d\'ensemble',
                    'icon' => 'fa-eye',
                    'description' => 'Trouvez des réponses et obtenez de l\'aide',
                    'active' => false
                ],
                [
                    'id' => 'guides',
                    'name' => 'Guides',
                    'icon' => 'fa-book',
                    'description' => 'Apprenez à utiliser toutes les fonctionnalités',
                    'active' => true
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
            ],
            'contactInfo' => [
                [
                    'id' => 1,
                    'type' => 'phone',
                    'title' => 'Support téléphonique',
                    'value' => '+33 1 23 45 67 89',
                    'description' => 'Lun-Ven 9h-18h',
                    'icon' => 'fa-phone',
                    'color' => '#3b82f6'
                ],
                [
                    'id' => 2,
                    'type' => 'email',
                    'title' => 'Email',
                    'value' => 'support@terraintrack.com',
                    'description' => 'Réponse sous 24h',
                    'icon' => 'fa-envelope',
                    'color' => '#10b981'
                ],
                [
                    'id' => 3,
                    'type' => 'chat',
                    'title' => 'Chat en direct',
                    'value' => 'Assistance immédiate',
                    'description' => 'Lun-Ven 9h-17h',
                    'icon' => 'fa-comments',
                    'color' => '#8b5cf6'
                ]
            ],
            'supportCategories' => [
                [
                    'id' => 'general',
                    'name' => 'Question générale',
                    'value' => 'general'
                ],
                [
                    'id' => 'technical',
                    'name' => 'Problème technique',
                    'value' => 'technical'
                ],
                [
                    'id' => 'billing',
                    'name' => 'Facturation',
                    'value' => 'billing'
                ],
                [
                    'id' => 'feature',
                    'name' => 'Demande de fonctionnalité',
                    'value' => 'feature'
                ],
                [
                    'id' => 'bug',
                    'name' => 'Signalement de bug',
                    'value' => 'bug'
                ]
            ],
            'supportPriorities' => [
                [
                    'id' => 'low',
                    'name' => 'Faible',
                    'value' => 'low'
                ],
                [
                    'id' => 'medium',
                    'name' => 'Moyenne',
                    'value' => 'medium'
                ],
                [
                    'id' => 'high',
                    'name' => 'Élevée',
                    'value' => 'high'
                ],
                [
                    'id' => 'urgent',
                    'name' => 'Urgente',
                    'value' => 'urgent'
                ]
            ]
        ];

        echo $this->twig->render('help-center.html.twig', $helpData);
    }
} 