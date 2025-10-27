<?php

/**
 * Système de permissions par défaut pour TerrainTrack
 * Logique métier : Permissions intelligentes qui minimisent les interventions admin
 */

class TerrainTrackDefaultPermissions
{
    /**
     * Permissions par défaut organisées par logique métier
     * Principe : Donner le maximum de liberté tout en maintenant la sécurité
     */
    public static function getDefaultPermissions(): array
    {
        return [
            // === SYSTÈME DE BASE ===
            'system' => [
                'system.access' => 'Accès au système',
                'system.settings' => 'Gestion des paramètres système',
                'system.logs' => 'Consultation des logs système',
            ],

            // === DASHBOARD (Accès universel avec niveaux) ===
            'dashboard' => [
                'dashboard.access' => 'Accès au tableau de bord',
                'dashboard.view' => 'Voir le tableau de bord',
                'dashboard.customize' => 'Personnaliser le tableau de bord',
                'dashboard.export' => 'Exporter les données du dashboard',
            ],

            // === UTILISATEURS (Gestion progressive) ===
            'users' => [
                'users.access' => 'Accès à la section utilisateurs',
                'users.view' => 'Voir les utilisateurs',
                'users.create' => 'Créer des utilisateurs',
                'users.update' => 'Modifier les utilisateurs',
                'users.delete' => 'Supprimer des utilisateurs',
                'users.manage' => 'Gestion complète des utilisateurs',
                'users.roles' => 'Gérer les rôles des utilisateurs',
            ],

            // === RÔLES ET PERMISSIONS (Admin uniquement) ===
            'roles' => [
                'roles.access' => 'Accès à la gestion des rôles',
                'roles.view' => 'Voir les rôles',
                'roles.create' => 'Créer des rôles',
                'roles.update' => 'Modifier les rôles',
                'roles.delete' => 'Supprimer des rôles',
                'roles.manage' => 'Gestion complète des rôles',
            ],

            // === VÉHICULES (Gestion opérationnelle) ===
            'vehicles' => [
                'vehicles.access' => 'Accès à la section véhicules',
                'vehicles.view' => 'Voir les véhicules',
                'vehicles.create' => 'Ajouter des véhicules',
                'vehicles.update' => 'Modifier les véhicules',
                'vehicles.delete' => 'Supprimer des véhicules',
                'vehicles.maintenance' => 'Gérer la maintenance',
                'vehicles.location' => 'Voir la localisation',
                'vehicles.manage' => 'Gestion complète des véhicules',
            ],

            // === INTERVENTIONS (Cœur métier) ===
            'interventions' => [
                'interventions.access' => 'Accès aux interventions',
                'interventions.view' => 'Voir les interventions',
                'interventions.create' => 'Créer des interventions',
                'interventions.update' => 'Modifier les interventions',
                'interventions.delete' => 'Supprimer les interventions',
                'interventions.assign' => 'Assigner des interventions',
                'interventions.complete' => 'Marquer comme terminées',
                'interventions.manage' => 'Gestion complète des interventions',
            ],

            // === ÉQUIPES (Gestion d'équipe) ===
            'teams' => [
                'teams.access' => 'Accès aux équipes',
                'teams.view' => 'Voir les équipes',
                'teams.create' => 'Créer des équipes',
                'teams.update' => 'Modifier les équipes',
                'teams.delete' => 'Supprimer les équipes',
                'teams.manage' => 'Gestion complète des équipes',
            ],

            // === CARTE ET GÉOLOCALISATION ===
            'map' => [
                'map.access' => 'Accès à la carte',
                'map.view' => 'Voir la carte',
                'map.edit' => 'Modifier la carte',
                'map.tracking' => 'Suivi en temps réel',
                'map.zones' => 'Gérer les zones',
            ],

            // === NOTIFICATIONS (Communication) ===
            'notifications' => [
                'notifications.access' => 'Accès aux notifications',
                'notifications.view' => 'Voir les notifications',
                'notifications.create' => 'Créer des notifications',
                'notifications.update' => 'Modifier les notifications',
                'notifications.delete' => 'Supprimer les notifications',
                'notifications.manage' => 'Gestion complète des notifications',
            ],

            // === RAPPORTS ET ANALYTICS ===
            'reports' => [
                'reports.access' => 'Accès aux rapports',
                'reports.view' => 'Voir les rapports',
                'reports.create' => 'Créer des rapports',
                'reports.export' => 'Exporter les rapports',
                'reports.analytics' => 'Accès aux analytics',
            ],

            // === MAINTENANCE ET SUPPORT ===
            'maintenance' => [
                'maintenance.access' => 'Accès à la maintenance',
                'maintenance.view' => 'Voir les tâches de maintenance',
                'maintenance.create' => 'Créer des tâches de maintenance',
                'maintenance.update' => 'Modifier les tâches de maintenance',
                'maintenance.complete' => 'Marquer comme terminées',
            ],

            // === AUDIT ET SÉCURITÉ ===
            'audit' => [
                'audit.access' => 'Accès aux logs d\'audit',
                'audit.view' => 'Voir les logs d\'audit',
                'audit.export' => 'Exporter les logs d\'audit',
                'audit.manage' => 'Gestion des logs d\'audit',
            ],
        ];
    }

    /**
     * Rôles par défaut avec permissions intelligentes
     * Principe : Donner le maximum de liberté selon le niveau hiérarchique
     */
    public static function getDefaultRoles(): array
    {
        return [
            'admin' => [
                'display_name' => 'Administrateur',
                'description' => 'Accès complet à toutes les fonctionnalités',
                'permissions' => [
                    // Système complet
                    'system.access', 'system.settings', 'system.logs',
                    
                    // Dashboard complet
                    'dashboard.access', 'dashboard.view', 'dashboard.customize', 'dashboard.export',
                    
                    // Gestion complète des utilisateurs
                    'users.access', 'users.view', 'users.create', 'users.update', 'users.delete', 'users.manage', 'users.roles',
                    
                    // Gestion complète des rôles
                    'roles.access', 'roles.view', 'roles.create', 'roles.update', 'roles.delete', 'roles.manage',
                    
                    // Gestion complète des véhicules
                    'vehicles.access', 'vehicles.view', 'vehicles.create', 'vehicles.update', 'vehicles.delete', 'vehicles.maintenance', 'vehicles.location', 'vehicles.manage',
                    
                    // Gestion complète des interventions
                    'interventions.access', 'interventions.view', 'interventions.create', 'interventions.update', 'interventions.delete', 'interventions.assign', 'interventions.complete', 'interventions.manage',
                    
                    // Gestion complète des équipes
                    'teams.access', 'teams.view', 'teams.create', 'teams.update', 'teams.delete', 'teams.manage',
                    
                    // Carte complète
                    'map.access', 'map.view', 'map.edit', 'map.tracking', 'map.zones',
                    
                    // Notifications complètes
                    'notifications.access', 'notifications.view', 'notifications.create', 'notifications.update', 'notifications.delete', 'notifications.manage',
                    
                    // Rapports complets
                    'reports.access', 'reports.view', 'reports.create', 'reports.export', 'reports.analytics',
                    
                    // Maintenance complète
                    'maintenance.access', 'maintenance.view', 'maintenance.create', 'maintenance.update', 'maintenance.complete',
                    
                    // Audit complet
                    'audit.access', 'audit.view', 'audit.export', 'audit.manage',
                ]
            ],

            'manager' => [
                'display_name' => 'Manager',
                'description' => 'Gestion opérationnelle des équipes et interventions',
                'permissions' => [
                    // Accès système de base
                    'system.access',
                    
                    // Dashboard complet
                    'dashboard.access', 'dashboard.view', 'dashboard.customize', 'dashboard.export',
                    
                    // Gestion des utilisateurs (sans suppression)
                    'users.access', 'users.view', 'users.create', 'users.update', 'users.roles',
                    
                    // Vue des rôles uniquement
                    'roles.access', 'roles.view',
                    
                    // Gestion complète des véhicules
                    'vehicles.access', 'vehicles.view', 'vehicles.create', 'vehicles.update', 'vehicles.maintenance', 'vehicles.location',
                    
                    // Gestion complète des interventions
                    'interventions.access', 'interventions.view', 'interventions.create', 'interventions.update', 'interventions.assign', 'interventions.complete', 'interventions.manage',
                    
                    // Gestion complète des équipes
                    'teams.access', 'teams.view', 'teams.create', 'teams.update', 'teams.manage',
                    
                    // Carte complète
                    'map.access', 'map.view', 'map.edit', 'map.tracking', 'map.zones',
                    
                    // Notifications complètes
                    'notifications.access', 'notifications.view', 'notifications.create', 'notifications.update', 'notifications.manage',
                    
                    // Rapports complets
                    'reports.access', 'reports.view', 'reports.create', 'reports.export', 'reports.analytics',
                    
                    // Maintenance complète
                    'maintenance.access', 'maintenance.view', 'maintenance.create', 'maintenance.update', 'maintenance.complete',
                    
                    // Audit en lecture seule
                    'audit.access', 'audit.view', 'audit.export',
                ]
            ],

            'technician' => [
                'display_name' => 'Technicien',
                'description' => 'Exécution des interventions et maintenance',
                'permissions' => [
                    // Accès système de base
                    'system.access',
                    
                    // Dashboard de base
                    'dashboard.access', 'dashboard.view',
                    
                    // Vue des utilisateurs de l'équipe uniquement
                    'users.access', 'users.view',
                    
                    // Vue des véhicules assignés
                    'vehicles.access', 'vehicles.view', 'vehicles.location',
                    
                    // Gestion des interventions assignées
                    'interventions.access', 'interventions.view', 'interventions.update', 'interventions.complete',
                    
                    // Vue de l'équipe
                    'teams.access', 'teams.view',
                    
                    // Carte de base
                    'map.access', 'map.view', 'map.tracking',
                    
                    // Notifications de base
                    'notifications.access', 'notifications.view',
                    
                    // Rapports de base
                    'reports.access', 'reports.view',
                    
                    // Maintenance des véhicules assignés
                    'maintenance.access', 'maintenance.view', 'maintenance.update', 'maintenance.complete',
                ]
            ],

            'supervisor' => [
                'display_name' => 'Superviseur',
                'description' => 'Supervision des équipes et contrôle qualité',
                'permissions' => [
                    // Accès système de base
                    'system.access',
                    
                    // Dashboard complet
                    'dashboard.access', 'dashboard.view', 'dashboard.customize', 'dashboard.export',
                    
                    // Gestion des utilisateurs de l'équipe
                    'users.access', 'users.view', 'users.create', 'users.update',
                    
                    // Vue des rôles
                    'roles.access', 'roles.view',
                    
                    // Gestion des véhicules
                    'vehicles.access', 'vehicles.view', 'vehicles.create', 'vehicles.update', 'vehicles.maintenance', 'vehicles.location',
                    
                    // Supervision des interventions
                    'interventions.access', 'interventions.view', 'interventions.create', 'interventions.update', 'interventions.assign', 'interventions.complete',
                    
                    // Gestion des équipes
                    'teams.access', 'teams.view', 'teams.create', 'teams.update',
                    
                    // Carte complète
                    'map.access', 'map.view', 'map.edit', 'map.tracking', 'map.zones',
                    
                    // Notifications complètes
                    'notifications.access', 'notifications.view', 'notifications.create', 'notifications.update',
                    
                    // Rapports complets
                    'reports.access', 'reports.view', 'reports.create', 'reports.export', 'reports.analytics',
                    
                    // Maintenance complète
                    'maintenance.access', 'maintenance.view', 'maintenance.create', 'maintenance.update', 'maintenance.complete',
                    
                    // Audit en lecture
                    'audit.access', 'audit.view',
                ]
            ],

            'viewer' => [
                'display_name' => 'Observateur',
                'description' => 'Consultation uniquement - Pas de modifications',
                'permissions' => [
                    // Accès système de base
                    'system.access',
                    
                    // Dashboard en lecture seule
                    'dashboard.access', 'dashboard.view',
                    
                    // Vue des utilisateurs
                    'users.access', 'users.view',
                    
                    // Vue des véhicules
                    'vehicles.access', 'vehicles.view', 'vehicles.location',
                    
                    // Vue des interventions
                    'interventions.access', 'interventions.view',
                    
                    // Vue des équipes
                    'teams.access', 'teams.view',
                    
                    // Carte en lecture seule
                    'map.access', 'map.view',
                    
                    // Notifications en lecture seule
                    'notifications.access', 'notifications.view',
                    
                    // Rapports en lecture seule
                    'reports.access', 'reports.view',
                    
                    // Maintenance en lecture seule
                    'maintenance.access', 'maintenance.view',
                ]
            ],
        ];
    }

    /**
     * Logique métier des permissions
     * Principe : Permissions intelligentes qui couvrent les besoins réels
     */
    public static function getPermissionLogic(): array
    {
        return [
            'dashboard' => [
                'description' => 'Tableau de bord - Accès universel avec personnalisation',
                'logic' => 'Tous les utilisateurs ont accès au dashboard, avec des niveaux de personnalisation selon le rôle'
            ],
            
            'users' => [
                'description' => 'Gestion des utilisateurs - Hiérarchique',
                'logic' => 'Les managers peuvent créer/modifier, les admins peuvent supprimer, les techniciens voient seulement leur équipe'
            ],
            
            'vehicles' => [
                'description' => 'Gestion des véhicules - Opérationnelle',
                'logic' => 'Les techniciens voient leurs véhicules assignés, les managers gèrent la flotte, les admins ont le contrôle total'
            ],
            
            'interventions' => [
                'description' => 'Interventions - Cœur métier',
                'logic' => 'Les techniciens exécutent, les managers planifient, les admins supervisent'
            ],
            
            'map' => [
                'description' => 'Carte et géolocalisation - Niveaux d\'accès',
                'logic' => 'Tous voient la carte, les techniciens voient le tracking, les managers peuvent éditer'
            ],
            
            'reports' => [
                'description' => 'Rapports et analytics - Niveaux de détail',
                'logic' => 'Les techniciens voient leurs rapports, les managers voient les analytics, les admins ont accès complet'
            ]
        ];
    }

    /**
     * Avantages de ce système de permissions
     */
    public static function getBenefits(): array
    {
        return [
            'Minimise les interventions admin' => 'Les permissions sont logiques et couvrent les besoins réels',
            'Sécurité par défaut' => 'Principe du moindre privilège avec escalade intelligente',
            'Flexibilité opérationnelle' => 'Les managers peuvent gérer leurs équipes sans intervention admin',
            'Traçabilité complète' => 'Toutes les actions sont auditées',
            'Évolutivité' => 'Facile d\'ajouter de nouveaux rôles ou permissions',
            'Performance' => 'Cache optimisé pour les vérifications fréquentes',
            'Maintenance réduite' => 'Moins de demandes de modification de permissions'
        ];
    }
}
