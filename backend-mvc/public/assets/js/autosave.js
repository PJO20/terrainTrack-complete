/**
 * Service d'auto-save pour les formulaires
 * Sauvegarde automatiquement les données des formulaires toutes les 30 secondes
 */

console.log('📄 Script autosave.js chargé');

class AutoSaveManager {
    constructor() {
        this.autoSaveInterval = 30000; // 30 secondes
        this.timeouts = new Map();
        this.isEnabled = true;
        this.init();
    }

    /**
     * Initialise le gestionnaire d'auto-save
     */
    async init() {
        console.log('🔄 Initialisation de l\'AutoSaveManager...');
        
        // Attendre que le DOM soit prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initializeAutoSave());
        } else {
            this.initializeAutoSave();
        }
    }

    /**
     * Initialise l'auto-save après que le DOM soit prêt
     */
    async initializeAutoSave() {
        // Chercher tous les formulaires avec la classe 'autosave'
        const forms = document.querySelectorAll('form.autosave');
        
        if (forms.length === 0) {
            console.log('💾 Aucun formulaire avec la classe "autosave" trouvé');
            return;
        }

        console.log(`💾 ${forms.length} formulaire(s) avec auto-save trouvé(s)`);

        // Activer l'auto-save pour chaque formulaire
        forms.forEach((form, index) => {
            const formId = form.id || `form_${index}`;
            console.log(`💾 Activation de l'auto-save pour le formulaire: ${formId}`);
            this.enableForForm(formId, form);
        });

        console.log('✅ AutoSaveManager initialisé avec succès');
    }

    /**
     * Active l'auto-save pour un formulaire
     */
    enableForForm(formId, formElement) {
        console.log(`💾 Activation de l'auto-save pour le formulaire: ${formId}`);

        // Charger les données sauvegardées au chargement de la page
        this.loadSavedData(formId, formElement);

        // Démarrer l'auto-save
        this.startAutoSave(formId, formElement);

        // Arrêter l'auto-save lors de la soumission du formulaire
        formElement.addEventListener('submit', () => {
            this.stopAutoSave(formId);
            this.clearSavedData(formId);
        });
    }

    /**
     * Démarre l'auto-save pour un formulaire
     */
    startAutoSave(formId, formElement) {
        // Arrêter l'auto-save existant s'il y en a un
        this.stopAutoSave(formId);

        const timeoutId = setInterval(() => {
            this.saveFormData(formId, formElement);
        }, this.autoSaveInterval);

        this.timeouts.set(formId, timeoutId);
        console.log(`🔄 Auto-save démarré pour ${formId}`);
    }

    /**
     * Arrête l'auto-save pour un formulaire
     */
    stopAutoSave(formId) {
        const timeoutId = this.timeouts.get(formId);
        if (timeoutId) {
            clearInterval(timeoutId);
            this.timeouts.delete(formId);
            console.log(`⏹️ Auto-save arrêté pour ${formId}`);
        }
    }

    /**
     * Sauvegarde les données du formulaire
     */
    async saveFormData(formId, formElement) {
        if (!this.isEnabled) return;

        try {
            const formData = new FormData(formElement);
            const data = {};

            // Convertir FormData en objet
            for (let [key, value] of formData.entries()) {
                // Ignorer les champs sensibles
                if (this.isSensitiveField(key)) {
                    continue;
                }
                data[key] = value;
            }

            // Ne pas sauvegarder si le formulaire est vide
            if (Object.keys(data).length === 0) {
                return;
            }

            console.log(`💾 Sauvegarde automatique pour ${formId}:`, data);

            // Essayer d'abord l'API
            try {
                const response = await fetch('/api/autosave', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        form_id: formId,
                        data: data
                    })
                });

                if (response.ok) {
                    const result = await response.json();
                    if (result.success) {
                        console.log(`✅ Données sauvegardées via API pour ${formId}`);
                        this.showAutoSaveIndicator();
                        return;
                    }
                }
            } catch (apiError) {
                console.log('⚠️ API auto-save non disponible, utilisation de localStorage');
            }

            // Fallback vers localStorage
            const storageKey = `autosave_${formId}`;
            localStorage.setItem(storageKey, JSON.stringify({
                data: data,
                timestamp: Date.now()
            }));
            
            console.log(`💾 Données sauvegardées dans localStorage pour ${formId}`);
            this.showAutoSaveIndicator();
            
        } catch (error) {
            console.error('❌ Erreur lors de la sauvegarde automatique:', error);
        }
    }

    /**
     * Charge les données sauvegardées
     */
    async loadSavedData(formId, formElement) {
        try {
            // Essayer d'abord l'API
            try {
                const response = await fetch(`/api/autosave?form_id=${formId}`);
                
                if (response.ok) {
                    const result = await response.json();
                    
                    if (result.success && result.has_data && result.data) {
                        console.log(`📥 Données restaurées via API pour ${formId}`);
                        
                        // Remplir le formulaire avec les données sauvegardées
                        this.fillForm(formElement, result.data);
                        
                        // Afficher une notification
                        this.showRestoreNotification();
                        return;
                    }
                }
            } catch (apiError) {
                console.log('⚠️ API auto-save non disponible, utilisation de localStorage');
            }

            // Fallback vers localStorage
            const storageKey = `autosave_${formId}`;
            const savedData = localStorage.getItem(storageKey);
            
            if (savedData) {
                try {
                    const parsed = JSON.parse(savedData);
                    const dataAge = Date.now() - parsed.timestamp;
                    
                    // Ne pas restaurer si les données sont trop anciennes (1 heure)
                    if (dataAge < 3600000) {
                        console.log(`📥 Données restaurées depuis localStorage pour ${formId}`);
                        this.fillForm(formElement, parsed.data);
                        this.showRestoreNotification();
                    } else {
                        // Supprimer les données trop anciennes
                        localStorage.removeItem(storageKey);
                    }
                } catch (parseError) {
                    console.error('Erreur lors du parsing des données localStorage:', parseError);
                    localStorage.removeItem(storageKey);
                }
            }
        } catch (error) {
            console.error('❌ Erreur lors du chargement des données:', error);
        }
    }

    /**
     * Remplit le formulaire avec les données sauvegardées
     */
    fillForm(formElement, data) {
        for (const [key, value] of Object.entries(data)) {
            const field = formElement.querySelector(`[name="${key}"]`);
            
            if (field) {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = value === 'on' || value === '1' || value === true;
                } else {
                    field.value = value;
                }
            }
        }
    }

    /**
     * Supprime les données sauvegardées
     */
    async clearSavedData(formId) {
        try {
            await fetch('/api/autosave', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ form_id: formId })
            });
            
            console.log(`🗑️ Données auto-save supprimées pour ${formId}`);
        } catch (error) {
            console.error('Erreur lors de la suppression des données:', error);
        }
    }

    /**
     * Supprime les données sauvegardées
     */
    async clearSavedData(formId) {
        try {
            // Essayer d'abord l'API
            try {
                const response = await fetch('/api/autosave', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ form_id: formId })
                });

                if (response.ok) {
                    console.log(`🗑️ Données supprimées via API pour ${formId}`);
                }
            } catch (apiError) {
                console.log('⚠️ API auto-save non disponible, nettoyage localStorage');
            }

            // Fallback vers localStorage
            const storageKey = `autosave_${formId}`;
            localStorage.removeItem(storageKey);
            console.log(`🗑️ Données supprimées de localStorage pour ${formId}`);
            
        } catch (error) {
            console.error('❌ Erreur lors de la suppression des données:', error);
        }
    }

    /**
     * Vérifie si un champ est sensible (ne pas sauvegarder)
     */
    isSensitiveField(fieldName) {
        const sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'confirm_password',
            'csrf_token',
            '_token'
        ];
        
        return sensitiveFields.some(sensitive => 
            fieldName.toLowerCase().includes(sensitive.toLowerCase())
        );
    }

    /**
     * Affiche un indicateur d'auto-save
     */
    showAutoSaveIndicator() {
        // Créer ou mettre à jour l'indicateur
        let indicator = document.getElementById('autosave-indicator');
        
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'autosave-indicator';
            indicator.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #10b981;
                color: white;
                padding: 8px 16px;
                border-radius: 6px;
                font-size: 14px;
                z-index: 1000;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;
            document.body.appendChild(indicator);
        }

        indicator.textContent = '💾 Sauvegardé automatiquement';
        indicator.style.opacity = '1';

        // Masquer après 2 secondes
        setTimeout(() => {
            indicator.style.opacity = '0';
        }, 2000);
    }

    /**
     * Affiche une notification de restauration
     */
    showRestoreNotification() {
        // Utiliser la fonction showNotification si elle existe
        if (typeof showNotification === 'function') {
            showNotification('📥 Données restaurées automatiquement', 'info');
        } else {
            // Fallback avec alert
            console.log('📥 Données restaurées automatiquement');
        }
    }

    /**
     * Génère un ID unique pour un formulaire
     */
    static generateFormId(page, formName = 'main') {
        return `${page}_${formName}`.replace(/[^a-zA-Z0-9_]/g, '_');
    }
}

// Initialiser le gestionnaire d'auto-save
let autoSaveManager = null;

// Fonction d'initialisation
function initializeAutoSave() {
    if (autoSaveManager) {
        return; // Déjà initialisé
    }
    
    console.log('🚀 Initialisation de l\'AutoSaveManager...');
    autoSaveManager = new AutoSaveManager();
}

// Fonction utilitaire pour activer l'auto-save sur un formulaire
function enableAutoSave(formElement, formId = null) {
    if (!autoSaveManager) {
        initializeAutoSave();
    }
    
    if (!formId) {
        formId = AutoSaveManager.generateFormId(
            window.location.pathname.replace(/\//g, '_'),
            formElement.name || 'form'
        );
    }
    
    autoSaveManager.enableForForm(formId, formElement);
}

// Initialiser automatiquement quand le DOM est prêt
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAutoSave);
} else {
    // DOM déjà prêt
    initializeAutoSave();
}

// Auto-activation sur les formulaires avec la classe 'autosave'
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form.autosave');
    forms.forEach(form => {
        enableAutoSave(form);
    });
});

// Exporter pour utilisation globale
window.AutoSaveManager = AutoSaveManager;
window.enableAutoSave = enableAutoSave;
window.initializeAutoSave = initializeAutoSave;

