/**
 * Gestion du mode hors-ligne
 */
class OfflineModeManager {
    constructor() {
        this.isOffline = false;
        this.offlineData = null;
        this.syncQueue = [];
        this.init();
    }

    init() {
        console.log('🔌 OfflineModeManager initialisé');
        
        // Écouter les changements de connectivité
        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());
        
        // Vérifier l'état initial
        this.checkConnectionStatus();
        
        // Charger les paramètres système
        this.loadSystemSettings();
    }

    /**
     * Vérifie l'état de la connexion
     */
    checkConnectionStatus() {
        this.isOffline = !navigator.onLine;
        console.log('🔌 État de connexion:', this.isOffline ? 'HORS-LIGNE' : 'EN LIGNE');
        
        if (this.isOffline) {
            this.showOfflineIndicator();
        } else {
            this.hideOfflineIndicator();
        }
    }

    /**
     * Gère le passage en mode hors-ligne
     */
    handleOffline() {
        console.log('🔌 Passage en mode hors-ligne');
        this.isOffline = true;
        this.showOfflineIndicator();
        this.enableOfflineFeatures();
    }

    /**
     * Gère le retour en ligne
     */
    handleOnline() {
        console.log('🔌 Retour en ligne');
        this.isOffline = false;
        this.hideOfflineIndicator();
        this.syncOfflineData();
    }

    /**
     * Active les fonctionnalités hors-ligne
     */
    enableOfflineFeatures() {
        // Sauvegarder les données essentielles
        this.saveOfflineData();
        
        // Activer le cache local
        this.enableLocalCache();
        
        // Désactiver les requêtes réseau
        this.disableNetworkRequests();
    }

    /**
     * Sauvegarde les données pour le mode hors-ligne
     */
    saveOfflineData() {
        const offlineData = {
            user: this.getUserData(),
            interventions: this.getInterventionsData(),
            vehicles: this.getVehiclesData(),
            technicians: this.getTechniciansData(),
            timestamp: new Date().toISOString()
        };

        localStorage.setItem('offline_data', JSON.stringify(offlineData));
        console.log('💾 Données hors-ligne sauvegardées');
    }

    /**
     * Charge les paramètres système
     */
    async loadSystemSettings() {
        try {
            const response = await fetch('/settings/system-settings');
            if (response.ok) {
                const settings = await response.json();
                this.updateOfflineToggle(settings.offline_mode === 'true');
            }
        } catch (error) {
            console.warn('⚠️ Impossible de charger les paramètres système:', error);
        }
    }

    /**
     * Met à jour le toggle du mode hors-ligne
     */
    updateOfflineToggle(enabled) {
        const toggle = document.querySelector('input[name="offline_mode"]');
        if (toggle) {
            toggle.checked = enabled;
        }
    }

    /**
     * Active le mode hors-ligne
     */
    async enableOfflineMode() {
        try {
            const response = await fetch('/settings/update-system', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'offline_mode=true'
            });

            if (response.ok) {
                console.log('✅ Mode hors-ligne activé');
                this.showSuccessMessage('Mode hors-ligne activé');
                this.saveOfflineData();
            } else {
                throw new Error('Erreur lors de l\'activation du mode hors-ligne');
            }
        } catch (error) {
            console.error('❌ Erreur activation mode hors-ligne:', error);
            this.showErrorMessage('Erreur lors de l\'activation du mode hors-ligne');
        }
    }

    /**
     * Désactive le mode hors-ligne
     */
    async disableOfflineMode() {
        try {
            const response = await fetch('/settings/update-system', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'offline_mode=false'
            });

            if (response.ok) {
                console.log('✅ Mode hors-ligne désactivé');
                this.showSuccessMessage('Mode hors-ligne désactivé');
                this.syncOfflineData();
            } else {
                throw new Error('Erreur lors de la désactivation du mode hors-ligne');
            }
        } catch (error) {
            console.error('❌ Erreur désactivation mode hors-ligne:', error);
            this.showErrorMessage('Erreur lors de la désactivation du mode hors-ligne');
        }
    }

    /**
     * Synchronise les données hors-ligne
     */
    async syncOfflineData() {
        if (this.syncQueue.length === 0) {
            return;
        }

        console.log('🔄 Synchronisation des données hors-ligne...');
        
        try {
            for (const data of this.syncQueue) {
                await this.syncSingleData(data);
            }
            
            this.syncQueue = [];
            console.log('✅ Synchronisation terminée');
            this.showSuccessMessage('Données synchronisées avec succès');
        } catch (error) {
            console.error('❌ Erreur synchronisation:', error);
            this.showErrorMessage('Erreur lors de la synchronisation');
        }
    }

    /**
     * Synchronise une donnée individuelle
     */
    async syncSingleData(data) {
        const response = await fetch(data.endpoint, {
            method: data.method,
            headers: data.headers,
            body: data.body
        });

        if (!response.ok) {
            throw new Error(`Erreur synchronisation: ${response.statusText}`);
        }
    }

    /**
     * Affiche l'indicateur hors-ligne
     */
    showOfflineIndicator() {
        let indicator = document.getElementById('offline-indicator');
        if (!indicator) {
            indicator = this.createOfflineIndicator();
        }
        indicator.style.display = 'block';
    }

    /**
     * Cache l'indicateur hors-ligne
     */
    hideOfflineIndicator() {
        const indicator = document.getElementById('offline-indicator');
        if (indicator) {
            indicator.style.display = 'none';
        }
    }

    /**
     * Crée l'indicateur hors-ligne
     */
    createOfflineIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'offline-indicator';
        indicator.innerHTML = `
            <div style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background: #ff6b6b;
                color: white;
                padding: 10px;
                text-align: center;
                z-index: 10000;
                font-weight: bold;
            ">
                🔌 Mode hors-ligne activé - Données mises en cache localement
            </div>
        `;
        document.body.appendChild(indicator);
        return indicator;
    }

    /**
     * Affiche un message de succès
     */
    showSuccessMessage(message) {
        this.showNotification(message, 'success');
    }

    /**
     * Affiche un message d'erreur
     */
    showErrorMessage(message) {
        this.showNotification(message, 'error');
    }

    /**
     * Affiche une notification
     */
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            z-index: 10001;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            max-width: 300px;
        `;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 3000);
    }

    /**
     * Récupère les données utilisateur
     */
    getUserData() {
        return JSON.parse(localStorage.getItem('currentUser') || '{}');
    }

    /**
     * Récupère les données des interventions
     */
    getInterventionsData() {
        // Simuler la récupération des interventions
        return JSON.parse(localStorage.getItem('interventions') || '[]');
    }

    /**
     * Récupère les données des véhicules
     */
    getVehiclesData() {
        // Simuler la récupération des véhicules
        return JSON.parse(localStorage.getItem('vehicles') || '[]');
    }

    /**
     * Récupère les données des techniciens
     */
    getTechniciansData() {
        // Simuler la récupération des techniciens
        return JSON.parse(localStorage.getItem('technicians') || '[]');
    }

    /**
     * Active le cache local
     */
    enableLocalCache() {
        // Implémenter la logique de cache local
        console.log('💾 Cache local activé');
    }

    /**
     * Désactive les requêtes réseau
     */
    disableNetworkRequests() {
        // Intercepter les requêtes fetch pour les mettre en queue
        const originalFetch = window.fetch;
        window.fetch = async (...args) => {
            if (this.isOffline) {
                // Mettre en queue pour synchronisation ultérieure
                this.syncQueue.push({
                    endpoint: args[0],
                    method: args[1]?.method || 'GET',
                    headers: args[1]?.headers || {},
                    body: args[1]?.body || null
                });
                
                console.log('📝 Requête mise en queue pour synchronisation');
                return Promise.resolve(new Response('{"status": "queued"}'));
            }
            
            return originalFetch(...args);
        };
    }
}

// Initialiser le gestionnaire de mode hors-ligne
document.addEventListener('DOMContentLoaded', () => {
    window.offlineModeManager = new OfflineModeManager();
});
