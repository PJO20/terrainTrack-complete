/**
 * Gestionnaire de thèmes dynamique
 * Gère les thèmes Clair, Sombre et Auto
 */

class ThemeManager {
    constructor() {
        this.currentTheme = this.getStoredTheme() || 'light';
        this.autoTheme = this.getSystemTheme();
        this.init();
    }

    /**
     * Initialise le gestionnaire de thèmes
     */
    init() {
        console.log('🎨 Initialisation du gestionnaire de thèmes');
        
        // Appliquer le thème au chargement
        this.applyTheme(this.currentTheme);
        
        // Écouter les changements de préférences système pour le mode auto
        this.watchSystemTheme();
        
        // Initialiser les contrôles de thème
        this.initThemeControls();
        
        // Créer l'indicateur de thème
        this.createThemeIndicator();
        
        console.log('✅ Gestionnaire de thèmes initialisé');
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
     * Détecte le thème système
     */
    getSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    /**
     * Applique un thème à la page
     */
    applyTheme(theme) {
        console.log('🎨 Application du thème:', theme);
        
        // Ajouter une classe de transition
        document.body.classList.add('theme-changing');
        
        // Déterminer le thème réel à appliquer
        let actualTheme = theme;
        if (theme === 'auto') {
            actualTheme = this.autoTheme;
        }
        
        // Appliquer le thème au body
        document.body.setAttribute('data-theme', actualTheme);
        
        // Mettre à jour l'indicateur
        this.updateThemeIndicator(theme, actualTheme);
        
        // Supprimer la classe de transition après l'animation
        setTimeout(() => {
            document.body.classList.remove('theme-changing');
        }, 500);
        
        console.log('✅ Thème appliqué:', actualTheme);
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
            
            indicatorText.textContent = `Thème: ${themeNames[selectedTheme]}`;
            indicator.style.opacity = '0.8';
            
            // Masquer après 2 secondes
            setTimeout(() => {
                indicator.style.opacity = '0';
            }, 2000);
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
