-- Migration pour ajouter la colonne team à la table interventions
-- Cette colonne permettra d'associer les interventions à des équipes spécifiques (alpha, beta, gamma)

ALTER TABLE interventions
ADD COLUMN team VARCHAR(50) NULL COMMENT 'Équipe assignée à l\'intervention (alpha, beta, gamma, etc.)';

-- Optionnel : ajouter un index pour optimiser les requêtes filtrant par équipe
CREATE INDEX idx_interventions_team ON interventions(team);

-- Optionnel : mettre à jour les interventions existantes avec une valeur par défaut
-- UPDATE interventions SET team = 'general' WHERE team IS NULL; 