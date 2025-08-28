-- Migration: Création de la table technicians
-- Date: 2025-06-27

CREATE TABLE IF NOT EXISTS technicians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    role VARCHAR(100) NOT NULL DEFAULT 'Technicien',
    team_ids JSON NULL COMMENT 'IDs des équipes auxquelles le technicien appartient',
    phone VARCHAR(20) NULL,
    specialization VARCHAR(255) NULL COMMENT 'Spécialisation du technicien',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insertion des techniciens existants
INSERT INTO technicians (name, email, role, team_ids, specialization, is_active) VALUES
('Jean Leclerc', 'jean.leclerc@terraintrack.com', 'Technicien', '[1,2]', 'Maintenance préventive', TRUE),
('Marie Petit', 'marie.petit@terraintrack.com', 'Chef', '[2]', 'Gestion d\'équipe', TRUE),
('Pierre Moreau', 'pierre.moreau@terraintrack.com', 'Technicien', '[2]', 'Réparation mécanique', TRUE),
('Lucas Rousseau', 'lucas.rousseau@terraintrack.com', 'Technicien', '[3]', 'Diagnostic électronique', TRUE),
('Sophie Dubois', 'sophie.dubois@terraintrack.com', 'Chef', '[1]', 'Gestion d\'équipe', TRUE),
('Claire Bernard', 'claire.bernard@terraintrack.com', 'Mécanicien', '[1,3]', 'Réparation moteur', TRUE),
('Emma Leroy', 'emma.leroy@terraintrack.com', 'Chef', '[3]', 'Gestion d\'équipe', TRUE),
('Thomas Martin', 'thomas.martin@terraintrack.com', 'Admin', '[4]', 'Administration système', TRUE),
('Lisa Garcia', 'lisa.garcia@terraintrack.com', 'Technicien', '[4]', 'Maintenance préventive', TRUE),
('David Wilson', 'david.wilson@terraintrack.com', 'Technicien', '[4]', 'Réparation hydraulique', TRUE);

-- Index pour optimiser les requêtes
CREATE INDEX idx_technicians_team_ids ON technicians(team_ids);
CREATE INDEX idx_technicians_active ON technicians(is_active);
CREATE INDEX idx_technicians_role ON technicians(role); 