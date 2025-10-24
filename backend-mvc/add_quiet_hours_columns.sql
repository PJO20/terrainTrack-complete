-- Ajouter les colonnes pour les heures silencieuses
ALTER TABLE users ADD COLUMN quiet_hours_enabled BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN quiet_hours_start TIME DEFAULT '22:00:00';
ALTER TABLE users ADD COLUMN quiet_hours_end TIME DEFAULT '07:00:00';
