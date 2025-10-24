/**
 * Synchronisation des avatars entre profil et header
 */

class AvatarSync {
    constructor() {
        this.init();
    }

    init() {
        console.log('🖼️ AvatarSync: Initialisation');
        
        // Écouter les événements de mise à jour de profil
        document.addEventListener('profileUpdated', (event) => {
            console.log('🖼️ AvatarSync: Événement profileUpdated reçu', event.detail);
            this.updateAllAvatars(event.detail);
        });

        // Écouter les changements d'avatar spécifiquement
        document.addEventListener('avatarChanged', (event) => {
            console.log('🖼️ AvatarSync: Événement avatarChanged reçu', event.detail);
            this.updateAllAvatars(event.detail);
        });
    }

    /**
     * Met à jour tous les avatars de l'interface
     */
    updateAllAvatars(userData) {
        console.log('🖼️ AvatarSync: Mise à jour de tous les avatars avec:', userData);
        
        // Mettre à jour l'avatar du header
        this.updateHeaderAvatar(userData);
        
        // Mettre à jour l'avatar de la sidebar (si présent)
        this.updateSidebarAvatar(userData);
        
        // Mettre à jour l'avatar du profil (si présent)
        this.updateProfileAvatar(userData);
    }

    /**
     * Met à jour l'avatar du header
     */
    updateHeaderAvatar(userData) {
        const headerAvatar = document.querySelector('.user-avatar');
        if (!headerAvatar) {
            console.log('🖼️ AvatarSync: Aucun avatar de header trouvé');
            return;
        }

        console.log('🖼️ AvatarSync: Mise à jour avatar header');
        console.log('  - Avatar URL:', userData.avatar || userData.avatar_url);
        console.log('  - Initiales:', userData.initials);

        const avatarUrl = userData.avatar || userData.avatar_url;
        
        if (avatarUrl && avatarUrl.trim() !== '') {
            // L'utilisateur a un avatar
            console.log('🖼️ AvatarSync: Affichage de l\'image avatar');
            
            if (headerAvatar.tagName === 'IMG') {
                // C'est déjà une image, changer juste la source
                headerAvatar.src = avatarUrl;
                headerAvatar.alt = userData.name || 'Avatar';
            } else {
                // C'est un div, le remplacer par une image
                const newImg = document.createElement('img');
                newImg.className = headerAvatar.className;
                newImg.src = avatarUrl;
                newImg.alt = userData.name || 'Avatar';
                newImg.style.cssText = 'width: 40px; height: 40px; border-radius: 50%; object-fit: cover;';
                
                headerAvatar.parentNode.replaceChild(newImg, headerAvatar);
            }
        } else if (userData.initials) {
            // Pas d'avatar, afficher les initiales
            console.log('🖼️ AvatarSync: Affichage des initiales');
            
            if (headerAvatar.tagName === 'IMG') {
                // C'est une image, la remplacer par un div
                const newDiv = document.createElement('div');
                newDiv.className = headerAvatar.className;
                newDiv.textContent = userData.initials;
                newDiv.style.cssText = 'width: 40px; height: 40px; border-radius: 50%; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 16px;';
                newDiv.setAttribute('data-initials', userData.initials);
                
                headerAvatar.parentNode.replaceChild(newDiv, headerAvatar);
            } else {
                // C'est déjà un div, changer juste le contenu
                headerAvatar.textContent = userData.initials;
                headerAvatar.setAttribute('data-initials', userData.initials);
            }
        }

        // Mettre à jour le nom si fourni
        if (userData.name) {
            const headerName = document.querySelector('.user-name');
            if (headerName) {
                headerName.textContent = userData.name;
            }
        }
    }

    /**
     * Met à jour l'avatar de la sidebar
     */
    updateSidebarAvatar(userData) {
        const sidebarAvatar = document.querySelector('.sidebar-user-avatar, .profile-avatar');
        if (!sidebarAvatar) return;

        console.log('🖼️ AvatarSync: Mise à jour avatar sidebar');

        const avatarUrl = userData.avatar || userData.avatar_url;
        
        if (avatarUrl && avatarUrl.trim() !== '') {
            if (sidebarAvatar.tagName === 'IMG') {
                sidebarAvatar.src = avatarUrl;
            } else {
                const newImg = document.createElement('img');
                newImg.className = sidebarAvatar.className;
                newImg.src = avatarUrl;
                newImg.alt = userData.name || 'Avatar';
                newImg.style.cssText = 'width: 60px; height: 60px; border-radius: 50%; object-fit: cover;';
                
                sidebarAvatar.parentNode.replaceChild(newImg, sidebarAvatar);
            }
        } else if (userData.initials) {
            if (sidebarAvatar.tagName === 'IMG') {
                const newDiv = document.createElement('div');
                newDiv.className = sidebarAvatar.className;
                newDiv.textContent = userData.initials;
                newDiv.style.cssText = 'width: 60px; height: 60px; border-radius: 50%; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 20px;';
                
                sidebarAvatar.parentNode.replaceChild(newDiv, sidebarAvatar);
            } else {
                sidebarAvatar.textContent = userData.initials;
            }
        }
    }

    /**
     * Met à jour l'avatar du profil (page profil)
     */
    updateProfileAvatar(userData) {
        const profileAvatar = document.querySelector('.profile-avatar-img, .profile-image');
        if (!profileAvatar) return;

        console.log('🖼️ AvatarSync: Mise à jour avatar profil');

        const avatarUrl = userData.avatar || userData.avatar_url;
        
        if (avatarUrl && avatarUrl.trim() !== '') {
            if (profileAvatar.tagName === 'IMG') {
                profileAvatar.src = avatarUrl;
            } else {
                const newImg = document.createElement('img');
                newImg.className = profileAvatar.className;
                newImg.src = avatarUrl;
                newImg.alt = userData.name || 'Avatar';
                newImg.style.cssText = 'width: 120px; height: 120px; border-radius: 50%; object-fit: cover;';
                
                profileAvatar.parentNode.replaceChild(newImg, profileAvatar);
            }
        }
    }

    /**
     * Force la synchronisation depuis le localStorage
     */
    syncFromStorage() {
        const userData = localStorage.getItem('currentUser');
        if (userData) {
            try {
                const parsedData = JSON.parse(userData);
                console.log('🖼️ AvatarSync: Synchronisation depuis localStorage', parsedData);
                this.updateAllAvatars(parsedData);
            } catch (e) {
                console.error('🖼️ AvatarSync: Erreur parsing localStorage', e);
            }
        }
    }

    /**
     * Déclenche manuellement une mise à jour
     */
    static triggerUpdate(userData) {
        console.log('🖼️ AvatarSync: Déclenchement manuel de mise à jour');
        
        // Déclencher l'événement personnalisé
        const event = new CustomEvent('avatarChanged', {
            detail: userData
        });
        document.dispatchEvent(event);
    }
}

// Initialiser automatiquement
document.addEventListener('DOMContentLoaded', function() {
    window.avatarSync = new AvatarSync();
    
    // Synchroniser depuis le localStorage au chargement
    setTimeout(() => {
        window.avatarSync.syncFromStorage();
    }, 500);
});

// Exporter pour utilisation globale
window.AvatarSync = AvatarSync;

