/**
 * Gestionnaire de thèmes dynamique amélioré
 * Gère les thèmes Clair, Sombre et Auto avec détection intelligente de luminosité
 */

class ThemeManager {
    constructor() {
        this.currentTheme = this.getStoredTheme() || 'light';
        this.autoTheme = this.getSystemTheme();
        this.luminosityThreshold = 0.5; // Seuil de luminosité pour basculer
        this.init();
    }

    /**
     * Initialise le gestionnaire de thèmes
     */
    init() {
        console.log('🎨 Initialisation du gestionnaire de thèmes amélioré');
        
        // Appliquer le thème au chargement
        this.applyTheme(this.currentTheme);
        
        // Écouter les changements de préférences système pour le mode auto
        this.watchSystemTheme();
        
        // Surveiller la luminosité de l'écran pour le mode auto
        this.watchScreenLuminosity();
        
        // Initialiser les contrôles de thème
        this.initThemeControls();
        
        // Créer l'indicateur de thème
        this.createThemeIndicator();
        
        console.log('✅ Gestionnaire de thèmes amélioré initialisé');
    }

    /**
     * Récupère le thème stocké dans localStorage
     */
    getStoredTheme() {
        return localStorage.getItem('theme') || 'light';
    }

    /**
     * Sauvegarde le thème dans localStorage
     */
    saveTheme(theme) {
        localStorage.setItem('theme', theme);
        console.log('💾 Thème sauvegardé:', theme);
    }

    /**
     * Détecte le thème système avec détection de luminosité
     */
    getSystemTheme() {
        // D'abord vérifier les préférences système
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        // Ensuite vérifier la luminosité de l'écran si disponible
        if (window.screen && window.screen.orientation) {
            const luminosity = this.getScreenLuminosity();
            if (luminosity !== null) {
                return luminosity < this.luminosityThreshold ? 'dark' : 'light';
            }
        }
        
        // Fallback sur les préférences système
        return systemPrefersDark ? 'dark' : 'light';
    }

    /**
     * Estime la luminosité de l'écran
     */
    getScreenLuminosity() {
        try {
            // Utiliser l'API Screen Wake Lock si disponible pour détecter la luminosité
            if ('wakeLock' in navigator) {
                // Estimation basée sur l'heure et les préférences système
                const hour = new Date().getHours();
                const isNightTime = hour < 6 || hour > 20;
                return isNightTime ? 0.3 : 0.7;
            }
            
            // Fallback: estimation basée sur l'heure
            const hour = new Date().getHours();
            if (hour >= 6 && hour <= 18) {
                return 0.7; // Jour
            } else {
                return 0.3; // Nuit
            }
        } catch (error) {
            console.warn('Impossible de détecter la luminosité:', error);
            return null;
        }
    }

    /**
     * Applique un thème à la page avec transitions fluides
     */
    applyTheme(theme) {
        console.log('🎨 Application du thème:', theme);
        
        // Ajouter une classe de transition
        document.body.classList.add('theme-changing');
        
        // Déterminer le thème réel à appliquer
        let actualTheme = theme;
        if (theme === 'auto') {
            actualTheme = this.autoTheme;
            console.log('🔄 Mode auto détecté:', actualTheme);
        }
        
        // Appliquer le thème au body et à l'html
        document.body.setAttribute('data-theme', actualTheme);
        document.documentElement.setAttribute('data-theme', actualTheme);
        
        // Mettre à jour les variables CSS personnalisées
        this.updateCSSVariables(actualTheme);
        
        // Mettre à jour l'indicateur
        this.updateThemeIndicator(theme, actualTheme);
        
        // Supprimer la classe de transition après l'animation
        setTimeout(() => {
            document.body.classList.remove('theme-changing');
        }, 500);
        
        console.log('✅ Thème appliqué:', actualTheme);
    }

    /**
     * Met à jour les variables CSS personnalisées
     */
    updateCSSVariables(theme) {
        const root = document.documentElement;
        
        if (theme === 'dark') {
            root.style.setProperty('--bg-primary', '#1a1a1a');
            root.style.setProperty('--bg-secondary', '#2d2d2d');
            root.style.setProperty('--bg-tertiary', '#404040');
            root.style.setProperty('--text-primary', '#ffffff');
            root.style.setProperty('--text-secondary', '#b3b3b3');
            root.style.setProperty('--text-muted', '#808080');
            root.style.setProperty('--border-color', '#404040');
            root.style.setProperty('--shadow', '0 2px 8px rgba(0,0,0,0.3)');
            root.style.setProperty('--shadow-hover', '0 4px 12px rgba(0,0,0,0.4)');
        } else {
            root.style.setProperty('--bg-primary', '#ffffff');
            root.style.setProperty('--bg-secondary', '#f8f9fa');
            root.style.setProperty('--bg-tertiary', '#e9ecef');
            root.style.setProperty('--text-primary', '#212529');
            root.style.setProperty('--text-secondary', '#6c757d');
            root.style.setProperty('--text-muted', '#adb5bd');
            root.style.setProperty('--border-color', '#dee2e6');
            root.style.setProperty('--shadow', '0 2px 8px rgba(0,0,0,0.1)');
            root.style.setProperty('--shadow-hover', '0 4px 12px rgba(0,0,0,0.15)');
        }
    }

    /**
     * Change le thème
     */
    changeTheme(theme) {
        console.log('🔄 Changement de thème vers:', theme);
        
        this.currentTheme = theme;
        this.saveTheme(theme);
        this.applyTheme(theme);
        
        // Mettre à jour les contrôles de thème
        this.updateThemeControls(theme);
        
        // Envoyer une notification
        this.showThemeNotification(theme);
    }

    /**
     * Initialise les contrôles de thème
     */
    initThemeControls() {
        const themeOptions = document.querySelectorAll('.theme-option');
        
        themeOptions.forEach(option => {
            option.addEventListener('click', (e) => {
                e.preventDefault();
                const theme = option.getAttribute('data-theme');
                console.log('🖱️ Clic sur thème:', theme);
                this.changeTheme(theme);
            });
        });
        
        // Mettre à jour l'état initial des contrôles
        this.updateThemeControls(this.currentTheme);
    }

    /**
     * Met à jour l'état des contrôles de thème
     */
    updateThemeControls(theme) {
        const themeOptions = document.querySelectorAll('.theme-option');
        const themeInput = document.getElementById('theme-input');
        
        // Retirer la classe active de toutes les options
        themeOptions.forEach(option => {
            option.classList.remove('active');
        });
        
        // Ajouter la classe active à l'option sélectionnée
        const activeOption = document.querySelector(`[data-theme="${theme}"]`);
        if (activeOption) {
            activeOption.classList.add('active');
        }
        
        // Mettre à jour l'input hidden
        if (themeInput) {
            themeInput.value = theme;
        }
        
        console.log('🎛️ Contrôles de thème mis à jour:', theme);
    }

    /**
     * Surveille les changements de préférences système
     */
    watchSystemTheme() {
        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        
        mediaQuery.addEventListener('change', (e) => {
            console.log('🔄 Changement de préférence système détecté');
            this.autoTheme = e.matches ? 'dark' : 'light';
            
            // Si le thème actuel est "auto", appliquer le nouveau thème système
            if (this.currentTheme === 'auto') {
                this.applyTheme('auto');
                this.updateThemeIndicator('auto', this.autoTheme);
            }
        });
    }

    /**
     * Surveille la luminosité de l'écran pour le mode auto
     */
    watchScreenLuminosity() {
        // Vérifier périodiquement la luminosité si en mode auto
        setInterval(() => {
            if (this.currentTheme === 'auto') {
                const newLuminosity = this.getScreenLuminosity();
                const currentLuminosity = this.getScreenLuminosity();
                
                // Basculer si la luminosité change significativement
                if (newLuminosity !== null && Math.abs(newLuminosity - currentLuminosity) > 0.2) {
                    const newTheme = newLuminosity < this.luminosityThreshold ? 'dark' : 'light';
                    if (newTheme !== this.autoTheme) {
                        console.log('🌅 Changement de luminosité détecté:', newTheme);
                        this.autoTheme = newTheme;
                        this.applyTheme('auto');
                        this.updateThemeIndicator('auto', this.autoTheme);
                    }
                }
            }
        }, 30000); // Vérifier toutes les 30 secondes

        // Écouter les changements d'heure pour ajuster automatiquement
        const checkHourlyTheme = () => {
            if (this.currentTheme === 'auto') {
                const hour = new Date().getHours();
                const isNightTime = hour < 6 || hour > 20;
                const expectedTheme = isNightTime ? 'dark' : 'light';
                
                if (expectedTheme !== this.autoTheme) {
                    console.log('🕐 Changement d\'heure détecté:', expectedTheme);
                    this.autoTheme = expectedTheme;
                    this.applyTheme('auto');
                    this.updateThemeIndicator('auto', this.autoTheme);
                }
            }
        };

        // Vérifier à chaque heure
        setInterval(checkHourlyTheme, 3600000); // 1 heure
    }

    /**
     * Crée l'indicateur de thème
     */
    createThemeIndicator() {
        // Supprimer l'ancien indicateur s'il existe
        const existingIndicator = document.getElementById('theme-indicator');
        if (existingIndicator) {
            existingIndicator.remove();
        }
        
        // Créer le nouvel indicateur
        const indicator = document.createElement('div');
        indicator.id = 'theme-indicator';
        indicator.className = 'theme-indicator';
        indicator.innerHTML = `
            <i class='bx bx-palette'></i>
            <span id="theme-indicator-text">Thème: ${this.currentTheme}</span>
        `;
        
        document.body.appendChild(indicator);
        
        // Masquer l'indicateur après 3 secondes
        setTimeout(() => {
            indicator.style.opacity = '0';
            setTimeout(() => {
                if (indicator.parentNode) {
                    indicator.parentNode.removeChild(indicator);
                }
            }, 300);
        }, 3000);
    }

    /**
     * Met à jour l'indicateur de thème
     */
    updateThemeIndicator(selectedTheme, actualTheme) {
        const indicator = document.getElementById('theme-indicator');
        const indicatorText = document.getElementById('theme-indicator-text');
        
        if (indicator && indicatorText) {
            const themeNames = {
                'light': 'Clair',
                'dark': 'Sombre',
                'auto': `Auto (${actualTheme === 'dark' ? 'Sombre' : 'Clair'})`
            };
            
            const luminosity = this.getScreenLuminosity();
            const luminosityText = luminosity !== null ? ` - Luminosité: ${Math.round(luminosity * 100)}%` : '';
            
            indicatorText.textContent = `Thème: ${themeNames[selectedTheme]}${luminosityText}`;
            indicator.style.opacity = '0.8';
            
            // Masquer après 3 secondes pour le mode auto (plus d'infos)
            const hideDelay = selectedTheme === 'auto' ? 3000 : 2000;
            setTimeout(() => {
                indicator.style.opacity = '0';
            }, hideDelay);
        }
    }

    /**
     * Affiche une notification de changement de thème
     */
    showThemeNotification(theme) {
        const themeNames = {
            'light': 'Clair',
            'dark': 'Sombre',
            'auto': 'Auto'
        };
        
        // Créer une notification personnalisée
        const notification = document.createElement('div');
        notification.className = 'theme-notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--accent-color);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            z-index: 1001;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        `;
        notification.innerHTML = `
            <i class='bx bx-palette'></i>
            Thème changé vers: ${themeNames[theme]}
        `;
        
        document.body.appendChild(notification);
        
        // Animation d'entrée
        setTimeout(() => {
            notification.style.transform = 'translateX(-50%) translateY(0)';
            notification.style.opacity = '1';
        }, 100);
        
        // Supprimer après 3 secondes
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(-50%) translateY(-20px)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    /**
     * Récupère le thème actuel
     */
    getCurrentTheme() {
        return this.currentTheme;
    }

    /**
     * Récupère le thème réel appliqué
     */
    getActualTheme() {
        if (this.currentTheme === 'auto') {
            return this.autoTheme;
        }
        return this.currentTheme;
    }
}

// Initialiser le gestionnaire de thèmes au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    window.themeManager = new ThemeManager();
    console.log('🎨 Gestionnaire de thèmes chargé');
});

// Exporter pour utilisation globale
window.ThemeManager = ThemeManager;
