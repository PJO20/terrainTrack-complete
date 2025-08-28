-- Migration: Création de la table notifications pour TerrainTrack
-- Date: 2025-07-04
-- Description: Table pour stocker les notifications du système

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    type ENUM('Information', 'Avertissement', 'Alerte', 'Succès') NOT NULL DEFAULT 'Information',
    type_class ENUM('info', 'warning', 'danger', 'success') NOT NULL DEFAULT 'info',
    icon VARCHAR(50) NOT NULL DEFAULT 'bx-info-circle',
    related_to VARCHAR(255) NULL,
    related_id INT NULL,
    related_type ENUM('vehicle', 'intervention', 'team', 'user', 'system') NULL,
    recipient_id INT NULL, -- ID utilisateur destinataire (NULL = notification globale)
    is_read BOOLEAN DEFAULT FALSE,
    priority ENUM('low', 'medium', 'high', 'critical') NOT NULL DEFAULT 'medium',
    expires_at TIMESTAMP NULL, -- Date d'expiration optionnelle
    metadata JSON NULL, -- Données supplémentaires en JSON
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_recipient_read (recipient_id, is_read),
    INDEX idx_type_priority (type, priority),
    INDEX idx_created_at (created_at DESC),
    INDEX idx_related (related_type, related_id)
);

-- Insérer quelques notifications de test
INSERT INTO notifications (title, description, type, type_class, icon, related_to, related_type, related_id, priority, created_at) VALUES
('Maintenance Urgente Requise', 'Le Quad Explorer X450 nécessite une maintenance immédiate - niveau d\'huile critique', 'Alerte', 'danger', 'bx-error-circle', 'Véhicule: Quad Explorer X450', 'vehicle', 1, 'critical', NOW()),
('Nouvelle Intervention Assignée', 'Intervention de nettoyage de terrain assignée à votre équipe pour demain matin', 'Information', 'info', 'bx-info-circle', 'Intervention: Nettoyage Terrain Secteur 3', 'intervention', NULL, 'medium', NOW()),
('Carburant Faible', 'Le Truck Defender T-400 a un niveau de carburant inférieur à 15%', 'Avertissement', 'warning', 'bx-error', 'Véhicule: Truck Defender T-400', 'vehicle', 2, 'high', NOW()),
('Intervention Terminée', 'Réparation d\'urgence du pont terminée avec succès par l\'équipe Alpha', 'Succès', 'success', 'bx-check-circle', 'Intervention: Réparation Pont d\'Urgence', 'intervention', NULL, 'medium', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Nouveau Membre d\'Équipe', 'Sarah Martin a rejoint l\'équipe Beta en tant que technicienne spécialisée', 'Information', 'info', 'bx-user-plus', 'Équipe: Équipe Beta', 'team', 2, 'low', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Mise à Jour Système', 'TerrainTrack v2.3 est maintenant disponible avec de nouvelles fonctionnalités', 'Information', 'info', 'bx-download', 'Système: TerrainTrack v2.3', 'system', NULL, 'low', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('Réunion d\'Équipe Programmée', 'Réunion hebdomadaire prévue pour vendredi à 14h00 en salle de conférence', 'Information', 'info', 'bx-calendar', 'Équipe: Team Alpha', 'team', 1, 'medium', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('Protocole d\'Urgence Activé', 'Protocole de réponse d\'urgence activé pour le secteur 7 - équipes en route', 'Alerte', 'danger', 'bx-error-circle', 'Intervention: Réponse d\'Urgence Secteur 7', 'intervention', NULL, 'critical', DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Marquer quelques notifications comme lues
UPDATE notifications SET is_read = TRUE WHERE id IN (4, 6, 7, 8); 