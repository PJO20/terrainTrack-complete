-- Table pour les paramètres système
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_setting (user_id, setting_key),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insérer les paramètres par défaut pour tous les utilisateurs existants
INSERT INTO system_settings (user_id, setting_key, setting_value)
SELECT 
    id as user_id,
    'offline_mode' as setting_key,
    'false' as setting_value
FROM users
WHERE NOT EXISTS (
    SELECT 1 FROM system_settings 
    WHERE system_settings.user_id = users.id 
    AND system_settings.setting_key = 'offline_mode'
);

-- Insérer d'autres paramètres système par défaut
INSERT INTO system_settings (user_id, setting_key, setting_value)
SELECT 
    id as user_id,
    'auto_save' as setting_key,
    'true' as setting_value
FROM users
WHERE NOT EXISTS (
    SELECT 1 FROM system_settings 
    WHERE system_settings.user_id = users.id 
    AND system_settings.setting_key = 'auto_save'
);

INSERT INTO system_settings (user_id, setting_key, setting_value)
SELECT 
    id as user_id,
    'cache_enabled' as setting_key,
    'true' as setting_value
FROM users
WHERE NOT EXISTS (
    SELECT 1 FROM system_settings 
    WHERE system_settings.user_id = users.id 
    AND system_settings.setting_key = 'cache_enabled'
);

INSERT INTO system_settings (user_id, setting_key, setting_value)
SELECT 
    id as user_id,
    'performance_mode' as setting_key,
    'false' as setting_value
FROM users
WHERE NOT EXISTS (
    SELECT 1 FROM system_settings 
    WHERE system_settings.user_id = users.id 
    AND system_settings.setting_key = 'performance_mode'
);

INSERT INTO system_settings (user_id, setting_key, setting_value)
SELECT 
    id as user_id,
    'data_compression' as setting_key,
    'true' as setting_value
FROM users
WHERE NOT EXISTS (
    SELECT 1 FROM system_settings 
    WHERE system_settings.user_id = users.id 
    AND system_settings.setting_key = 'data_compression'
);

INSERT INTO system_settings (user_id, setting_key, setting_value)
SELECT 
    id as user_id,
    'debug_mode' as setting_key,
    'false' as setting_value
FROM users
WHERE NOT EXISTS (
    SELECT 1 FROM system_settings 
    WHERE system_settings.user_id = users.id 
    AND system_settings.setting_key = 'debug_mode'
);

INSERT INTO system_settings (user_id, setting_key, setting_value)
SELECT 
    id as user_id,
    'log_level' as setting_key,
    'info' as setting_value
FROM users
WHERE NOT EXISTS (
    SELECT 1 FROM system_settings 
    WHERE system_settings.user_id = users.id 
    AND system_settings.setting_key = 'log_level'
);
