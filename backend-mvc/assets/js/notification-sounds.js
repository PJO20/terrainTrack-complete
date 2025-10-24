/**
 * Gestion des sons de notification
 */

class NotificationSounds {
    constructor() {
        this.sounds = {
            info: new Audio('/assets/sounds/notification-info.wav'),
            warning: new Audio('/assets/sounds/notification-warning.wav'),
            success: new Audio('/assets/sounds/notification-success.wav'),
            error: new Audio('/assets/sounds/notification-error.wav'),
            default: new Audio('/assets/sounds/notification-default.wav')
        };
        
        this.enabled = this.getSoundPreference();
        this.volume = 0.7; // Volume par défaut (0.0 à 1.0)
        
        this.init();
    }

    init() {
        // Configurer le volume pour tous les sons
        Object.values(this.sounds).forEach(sound => {
            sound.volume = this.volume;
            sound.preload = 'auto';
        });

        // Écouter les changements de préférence
        this.listenForPreferenceChanges();
        
        console.log('🔊 NotificationSounds initialisé, sons activés:', this.enabled);
    }

    /**
     * Récupère la préférence de son depuis localStorage ou les paramètres
     */
    getSoundPreference() {
        // Vérifier localStorage d'abord
        const localPreference = localStorage.getItem('sound_notifications');
        if (localPreference !== null) {
            return localPreference === 'true';
        }

        // Vérifier les paramètres utilisateur depuis le DOM
        const soundToggle = document.querySelector('input[name="sound_notifications"]');
        if (soundToggle) {
            return soundToggle.checked;
        }

        // Valeur par défaut
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
     * Écoute les changements de préférence dans l'interface
     */
    listenForPreferenceChanges() {
        // Écouter les changements sur le toggle de son
        const soundToggle = document.querySelector('input[name="sound_notifications"]');
        if (soundToggle) {
            soundToggle.addEventListener('change', (e) => {
                this.setSoundPreference(e.target.checked);
                console.log('🔊 Toggle de son modifié:', e.target.checked);
            });
        }

        // Écouter les événements de mise à jour des paramètres
        document.addEventListener('settingsUpdated', (event) => {
            if (event.detail && event.detail.sound_notifications !== undefined) {
                this.setSoundPreference(event.detail.sound_notifications);
            }
        });
    }

    /**
     * Joue un son de notification
     */
    playSound(type = 'default') {
        if (!this.enabled) {
            console.log('🔇 Sons désactivés, notification silencieuse');
            return;
        }

        const sound = this.sounds[type] || this.sounds.default;
        
        if (sound) {
            try {
                // Réinitialiser le son au début
                sound.currentTime = 0;
                
                // Vérifier si le son est prêt
                if (sound.readyState >= 2) { // HAVE_CURRENT_DATA
                    this.attemptPlay(sound, type);
                } else {
                    // Attendre que le son soit prêt
                    sound.addEventListener('canplay', () => {
                        this.attemptPlay(sound, type);
                    }, { once: true });
                    
                    // Charger le son si nécessaire
                    sound.load();
                }
            } catch (error) {
                console.warn('⚠️ Erreur lors de la lecture du son:', error);
            }
        }
    }

    /**
     * Tente de jouer un son avec gestion d'erreurs
     */
    attemptPlay(sound, type) {
        try {
            const playPromise = sound.play();
            
            if (playPromise !== undefined) {
                playPromise.then(() => {
                    console.log('🔊 Son de notification joué:', type);
                }).catch(error => {
                    console.warn('⚠️ Impossible de jouer le son:', error);
                    
                    // Si c'est une erreur de politique de lecture automatique
                    if (error.name === 'NotAllowedError') {
                        console.warn('🔇 Lecture automatique bloquée par le navigateur');
                        this.showUserInteractionRequired();
                    }
                    
                    // Essayer avec le son par défaut si le son spécifique échoue
                    if (type !== 'default') {
                        this.playSound('default');
                    }
                });
            }
        } catch (error) {
            console.warn('⚠️ Erreur lors de la tentative de lecture:', error);
        }
    }

    /**
     * Affiche un message si une interaction utilisateur est requise
     */
    showUserInteractionRequired() {
        // Créer une notification temporaire
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #ffc107;
            color: #000;
            padding: 10px 15px;
            border-radius: 5px;
            z-index: 10000;
            font-size: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        `;
        notification.textContent = '🔊 Cliquez n\'importe où pour activer les sons';
        
        document.body.appendChild(notification);
        
        // Supprimer après 5 secondes
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
        
        // Activer les sons au premier clic
        const enableOnClick = () => {
            this.enableSoundsAfterUserInteraction();
            document.removeEventListener('click', enableOnClick);
            document.removeEventListener('touchstart', enableOnClick);
        };
        
        document.addEventListener('click', enableOnClick);
        document.addEventListener('touchstart', enableOnClick);
    }

    /**
     * Active les sons après une interaction utilisateur
     */
    enableSoundsAfterUserInteraction() {
        console.log('🔊 Interaction utilisateur détectée, activation des sons...');
        
        // Tenter de jouer un son silencieux pour débloquer l'audio
        const silentSound = new Audio('data:audio/wav;base64,UklGRigAAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQAAAAA=');
        silentSound.volume = 0.01;
        silentSound.play().then(() => {
            console.log('✅ Sons débloqués par interaction utilisateur');
        }).catch(error => {
            console.warn('⚠️ Impossible de débloquer les sons:', error);
        });
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
     * Définit le volume des sons
     */
    setVolume(volume) {
        this.volume = Math.max(0, Math.min(1, volume)); // Clamp entre 0 et 1
        
        Object.values(this.sounds).forEach(sound => {
            sound.volume = this.volume;
        });
        
        console.log('🔊 Volume des sons défini à:', this.volume);
    }

    /**
     * Active ou désactive les sons
     */
    toggleSounds() {
        this.setSoundPreference(!this.enabled);
        return this.enabled;
    }

    /**
     * Teste les sons (pour les paramètres)
     */
    testSound(type = 'default') {
        console.log('🧪 Test du son:', type);
        
        // Pour Chrome, créer un nouveau contexte audio à chaque test
        if (this.isChrome()) {
            this.playSoundWithUserInteraction(type);
        } else {
            this.playSound(type);
        }
    }

    /**
     * Détecte si c'est Chrome
     */
    isChrome() {
        return /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor);
    }

    /**
     * Joue un son avec interaction utilisateur (pour Chrome)
     */
    playSoundWithUserInteraction(type = 'default') {
        if (!this.enabled) {
            console.log('🔇 Sons désactivés, notification silencieuse');
            return;
        }

        // Créer un nouveau Audio à chaque fois pour Chrome
        const audioUrl = `/assets/sounds/notification-${type}.wav`;
        const sound = new Audio(audioUrl);
        sound.volume = this.volume;
        
        console.log('🔊 Lecture du son avec interaction utilisateur:', type);
        
        sound.play().then(() => {
            console.log('✅ Son joué avec succès:', type);
        }).catch(error => {
            console.warn('⚠️ Erreur de lecture:', error);
            
            if (error.name === 'NotAllowedError') {
                console.warn('🔇 Lecture bloquée par Chrome - interaction requise');
                this.showChromeInteractionMessage();
            }
        });
    }

    /**
     * Affiche un message spécifique pour Chrome
     */
    showChromeInteractionMessage() {
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
            <div style="font-weight: bold; margin-bottom: 5px;">🔊 Chrome - Sons Bloqués</div>
            <div style="font-size: 12px;">Cliquez sur l'icône audio dans la barre d'adresse pour autoriser les sons</div>
        `;
        
        document.body.appendChild(notification);
        
        // Supprimer après 8 secondes
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 8000);
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
                setTimeout(playNext, 1000); // 1 seconde entre chaque son
            }
        };
        
        playNext();
    }
}

// Initialiser automatiquement
document.addEventListener('DOMContentLoaded', function() {
    window.notificationSounds = new NotificationSounds();
    
    // Exposer les méthodes pour les tests
    window.testNotificationSound = (type) => window.notificationSounds.testSound(type);
    window.testAllNotificationSounds = () => window.notificationSounds.testAllSounds();
});

// Écouter les notifications du système
document.addEventListener('notificationReceived', function(event) {
    if (window.notificationSounds && event.detail) {
        const notification = event.detail;
        window.notificationSounds.playNotificationSound(notification.type || notification.typeClass);
    }
});

// Écouter les notifications de l'API
document.addEventListener('newNotification', function(event) {
    if (window.notificationSounds && event.detail) {
        const notification = event.detail;
        window.notificationSounds.playNotificationSound(notification.type || notification.typeClass);
    }
});

// Exporter pour utilisation globale
window.NotificationSounds = NotificationSounds;
