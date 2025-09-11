-- Création de la table appearance_settings
CREATE TABLE IF NOT EXISTS appearance_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    theme ENUM('light', 'dark', 'auto') DEFAULT 'light',
    primary_color VARCHAR(50) DEFAULT 'blue',
    font_size ENUM('small', 'medium', 'large') DEFAULT 'medium',
    compact_mode BOOLEAN DEFAULT FALSE,
    animations_enabled BOOLEAN DEFAULT TRUE,
    high_contrast BOOLEAN DEFAULT FALSE,
    reduced_motion BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_appearance (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insérer des paramètres par défaut pour les utilisateurs existants
INSERT IGNORE INTO appearance_settings (user_id, theme, primary_color, font_size, compact_mode, animations_enabled, high_contrast, reduced_motion)
SELECT 
    id as user_id,
    'light' as theme,
    'blue' as primary_color,
    'medium' as font_size,
    FALSE as compact_mode,
    TRUE as animations_enabled,
    FALSE as high_contrast,
    FALSE as reduced_motion
FROM users 
WHERE id NOT IN (SELECT user_id FROM appearance_settings);
