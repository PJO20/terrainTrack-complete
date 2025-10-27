/**
 * Gestion des sons de notification - Version Chrome Optimisée
 */

class NotificationSoundsChrome {
    constructor() {
        this.enabled = this.getSoundPreference();
        this.volume = 0.7;
        this.userInteracted = false;
        
        this.init();
    }

    init() {
        console.log('🔊 NotificationSoundsChrome initialisé');
        
        // Écouter les interactions utilisateur pour débloquer l'audio
        this.setupUserInteractionListener();
        
        // Écouter les changements de préférence
        this.listenForPreferenceChanges();
    }

    /**
     * Configure l'écoute des interactions utilisateur
     */
    setupUserInteractionListener() {
        const enableAudio = () => {
            this.userInteracted = true;
            console.log('🔊 Interaction utilisateur détectée - Audio débloqué');
            
            // Supprimer les écouteurs après la première interaction
            document.removeEventListener('click', enableAudio);
            document.removeEventListener('touchstart', enableAudio);
            document.removeEventListener('keydown', enableAudio);
        };

        document.addEventListener('click', enableAudio);
        document.addEventListener('touchstart', enableAudio);
        document.addEventListener('keydown', enableAudio);
    }

    /**
     * Récupère la préférence de son
     */
    getSoundPreference() {
        const localPreference = localStorage.getItem('sound_notifications');
        if (localPreference !== null) {
            return localPreference === 'true';
        }

        const soundToggle = document.querySelector('input[name="sound_notifications"]');
        if (soundToggle) {
            return soundToggle.checked;
        }

        return true;
    }

    /**
     * Met à jour la préférence de son
     */
    setSoundPreference(enabled) {
        this.enabled = enabled;
        localStorage.setItem('sound_notifications', enabled.toString());
        console.log('🔊 Préférence de son mise à jour:', enabled);
    }

    /**
     * Écoute les changements de préférence
     */
    listenForPreferenceChanges() {
        const soundToggle = document.querySelector('input[name="sound_notifications"]');
        if (soundToggle) {
            soundToggle.addEventListener('change', (e) => {
                this.setSoundPreference(e.target.checked);
            });
        }
    }

    /**
     * Joue un son de notification (version Chrome optimisée)
     */
    playSound(type = 'default') {
        if (!this.enabled) {
            console.log('🔇 Sons désactivés');
            return;
        }

        console.log('🔊 Tentative de lecture du son:', type);

        // Créer un nouveau Audio à chaque fois (nécessaire pour Chrome)
        const audioUrl = `/assets/sounds/notification-${type}.wav`;
        const audio = new Audio(audioUrl);
        audio.volume = this.volume;

        // Gestion des événements
        audio.onloadstart = () => console.log('📥 Chargement du son...');
        audio.oncanplay = () => console.log('✅ Son prêt à être joué');
        audio.onplay = () => console.log('▶️ Son en cours de lecture');
        audio.onended = () => console.log('⏹️ Son terminé');
        audio.onerror = (e) => console.warn('❌ Erreur audio:', e);

        // Tenter de jouer le son
        const playPromise = audio.play();

        if (playPromise !== undefined) {
            playPromise.then(() => {
                console.log('✅ Son joué avec succès:', type);
            }).catch(error => {
                console.warn('⚠️ Erreur de lecture:', error.name, error.message);
                
                if (error.name === 'NotAllowedError') {
                    this.handleNotAllowedError(type);
                } else {
                    console.error('❌ Erreur inattendue:', error);
                }
            });
        }
    }

    /**
     * Gère l'erreur NotAllowedError
     */
    handleNotAllowedError(type) {
        console.warn('🔇 Lecture automatique bloquée par le navigateur');
        
        if (!this.userInteracted) {
            this.showInteractionRequiredMessage();
        } else {
            this.showPermissionRequiredMessage();
        }
    }

    /**
     * Affiche un message pour interaction utilisateur
     */
    showInteractionRequiredMessage() {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #ffc107;
            color: #000;
            padding: 15px 20px;
            border-radius: 8px;
            z-index: 10000;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            max-width: 300px;
        `;
        notification.innerHTML = `
            <div style="font-weight: bold; margin-bottom: 5px;">🔊 Interaction Requise</div>
            <div style="font-size: 12px;">Cliquez n'importe où sur la page pour activer les sons</div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }

    /**
     * Affiche un message pour permissions
     */
    showPermissionRequiredMessage() {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #dc3545;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            z-index: 10000;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            max-width: 300px;
        `;
        notification.innerHTML = `
            <div style="font-weight: bold; margin-bottom: 5px;">🔊 Permissions Audio</div>
            <div style="font-size: 12px;">Autorisez les sons dans les paramètres du navigateur</div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 8000);
    }

    /**
     * Joue un son basé sur le type de notification
     */
    playNotificationSound(notificationType) {
        let soundType = 'default';
        
        switch (notificationType) {
            case 'info':
            case 'Information':
                soundType = 'info';
                break;
            case 'warning':
            case 'Avertissement':
                soundType = 'warning';
                break;
            case 'success':
            case 'Succès':
                soundType = 'success';
                break;
            case 'error':
            case 'danger':
            case 'Alerte':
                soundType = 'error';
                break;
            default:
                soundType = 'default';
        }
        
        this.playSound(soundType);
    }

    /**
     * Teste un son (pour les paramètres)
     */
    testSound(type = 'default') {
        console.log('🧪 Test du son:', type);
        this.playSound(type);
    }

    /**
     * Teste tous les sons
     */
    testAllSounds() {
        console.log('🧪 Test de tous les sons');
        const types = ['info', 'warning', 'success', 'error', 'default'];
        let index = 0;
        
        const playNext = () => {
            if (index < types.length) {
                this.playSound(types[index]);
                index++;
                setTimeout(playNext, 1000);
            }
        };
        
        playNext();
    }

    /**
     * Définit le volume
     */
    setVolume(volume) {
        this.volume = Math.max(0, Math.min(1, volume));
        console.log('🔊 Volume défini à:', this.volume);
    }

    /**
     * Active ou désactive les sons
     */
    toggleSounds() {
        this.setSoundPreference(!this.enabled);
        return this.enabled;
    }
}

// Initialiser automatiquement
document.addEventListener('DOMContentLoaded', function() {
    window.notificationSoundsChrome = new NotificationSoundsChrome();
    
    // Exposer les méthodes pour les tests
    window.testNotificationSound = (type) => window.notificationSoundsChrome.testSound(type);
    window.testAllNotificationSounds = () => window.notificationSoundsChrome.testAllSounds();
});

// Écouter les notifications du système
document.addEventListener('notificationReceived', function(event) {
    if (window.notificationSoundsChrome && event.detail) {
        const notification = event.detail;
        window.notificationSoundsChrome.playNotificationSound(notification.type || notification.typeClass);
    }
});

// Exporter pour utilisation globale
window.NotificationSoundsChrome = NotificationSoundsChrome;

