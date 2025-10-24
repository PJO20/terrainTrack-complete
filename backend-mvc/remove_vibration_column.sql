-- Suppression de la colonne vibration_notifications de la table notification_settings
-- Cette colonne n'est plus utilisée dans l'application

ALTER TABLE notification_settings DROP COLUMN vibration_notifications;
