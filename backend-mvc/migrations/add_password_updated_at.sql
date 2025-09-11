-- Migration pour ajouter la colonne password_updated_at à la table users
-- Cette colonne permettra de tracker la dernière modification du mot de passe

ALTER TABLE users ADD COLUMN password_updated_at TIMESTAMP NULL DEFAULT NULL;

-- Mettre à jour les utilisateurs existants avec la date de création comme date de dernière modification du mot de passe
UPDATE users SET password_updated_at = created_at WHERE password_updated_at IS NULL;
