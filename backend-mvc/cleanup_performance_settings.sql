-- Nettoyage des paramètres de performance
-- Suppression des paramètres techniques inutiles pour l'utilisateur

-- Supprimer les paramètres de performance
DELETE FROM system_settings WHERE setting_key = 'performance_mode';
DELETE FROM system_settings WHERE setting_key = 'data_compression';

-- Vérifier les paramètres restants
SELECT 
    setting_key, 
    COUNT(*) as count
FROM system_settings 
GROUP BY setting_key
ORDER BY setting_key;

