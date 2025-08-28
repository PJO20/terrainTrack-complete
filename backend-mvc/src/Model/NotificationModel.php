<?php

namespace App\Model;

class NotificationModel
{
    public function findAll(): array
    {
        // Créer des dates récentes pour les tests
        $today = date('d/m/Y');
        $yesterday = date('d/m/Y', strtotime('-1 day'));
        $twoDaysAgo = date('d/m/Y', strtotime('-2 days'));
        
        return [
            // Notifications d'aujourd'hui (non lues)
            [
                'id' => 1,
                'title' => 'Maintenance Urgente Requise',
                'description' => 'Le Quad Explorer X450 nécessite une maintenance immédiate - niveau d\'huile critique',
                'type' => 'Alerte',
                'type_class' => 'danger',
                'icon' => 'bx-error-circle',
                'related_to' => 'Véhicule: Quad Explorer X450',
                'date' => $today,
                'read' => false
            ],
            [
                'id' => 2,
                'title' => 'Nouvelle Intervention Assignée',
                'description' => 'Intervention de nettoyage de terrain assignée à votre équipe pour demain matin',
                'type' => 'Information',
                'type_class' => 'info',
                'icon' => 'bx-info-circle',
                'related_to' => 'Intervention: Nettoyage Terrain Secteur 3',
                'date' => $today,
                'read' => false
            ],
            [
                'id' => 3,
                'title' => 'Carburant Faible',
                'description' => 'Le Truck Defender T-400 a un niveau de carburant inférieur à 15%',
                'type' => 'Avertissement',
                'type_class' => 'warning',
                'icon' => 'bx-error',
                'related_to' => 'Véhicule: Truck Defender T-400',
                'date' => $today,
                'read' => false
            ],
            
            // Notifications d'hier (mélange lu/non lu)
            [
                'id' => 4,
                'title' => 'Intervention Terminée',
                'description' => 'Réparation d\'urgence du pont terminée avec succès par l\'équipe Alpha',
                'type' => 'Succès',
                'type_class' => 'success',
                'icon' => 'bx-check-circle',
                'related_to' => 'Intervention: Réparation Pont d\'Urgence',
                'date' => $yesterday,
                'read' => true
            ],
            [
                'id' => 5,
                'title' => 'Nouveau Membre d\'Équipe',
                'description' => 'Sarah Martin a rejoint l\'équipe Beta en tant que technicienne spécialisée',
                'type' => 'Information',
                'type_class' => 'info',
                'icon' => 'bx-user-plus',
                'related_to' => 'Équipe: Équipe Beta',
                'date' => $yesterday,
                'read' => false
            ],
            [
                'id' => 6,
                'title' => 'Mise à Jour Système',
                'description' => 'TerrainTrack v2.3 est maintenant disponible avec de nouvelles fonctionnalités',
                'type' => 'Information',
                'type_class' => 'info',
                'icon' => 'bx-download',
                'related_to' => 'Système: TerrainTrack v2.3',
                'date' => $yesterday,
                'read' => true
            ],
            
            // Notifications plus anciennes
            [
                'id' => 7,
                'title' => 'Réunion d\'Équipe Programmée',
                'description' => 'Réunion hebdomadaire prévue pour vendredi à 14h00 en salle de conférence',
                'type' => 'Information',
                'type_class' => 'info',
                'icon' => 'bx-calendar',
                'related_to' => 'Équipe: Team Alpha',
                'date' => $twoDaysAgo,
                'read' => true
            ],
            [
                'id' => 8,
                'title' => 'Protocole d\'Urgence Activé',
                'description' => 'Protocole de réponse d\'urgence activé pour le secteur 7 - équipes en route',
                'type' => 'Alerte',
                'type_class' => 'danger',
                'icon' => 'bx-error-circle',
                'related_to' => 'Intervention: Réponse d\'Urgence Secteur 7',
                'date' => $twoDaysAgo,
                'read' => true
            ],
            
            // Anciennes notifications (pour tester le filtre)
            [
                'id' => 9,
                'title' => 'Inspection Véhicule Planifiée',
                'description' => 'Inspection de routine prévue pour tous les véhicules la semaine prochaine',
                'type' => 'Information',
                'type_class' => 'info',
                'icon' => 'bx-search',
                'related_to' => 'Maintenance: Inspection Routine',
                'date' => '15/05/2025',
                'read' => true
            ],
            [
                'id' => 10,
                'title' => 'Formation Sécurité',
                'description' => 'Session de formation sécurité obligatoire pour tous les techniciens',
                'type' => 'Information',
                'type_class' => 'info',
                'icon' => 'bx-shield',
                'related_to' => 'Formation: Sécurité Équipe',
                'date' => '10/05/2025',
                'read' => true
            ]
        ];
    }
} 