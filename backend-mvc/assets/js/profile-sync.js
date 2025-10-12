/**
 * Script global pour la synchronisation du profil
 * À inclure dans toutes les pages pour maintenir la cohérence
 */

class ProfileSync {
    constructor() {
        this.init();
    }

    init() {
        // Écouter les événements de mise à jour de profil
        document.addEventListener('profileUpdated', (event) => {
            this.handleProfileUpdate(event.detail);
        });

        // Charger les données de session au démarrage
        this.loadSessionData();
    }

    handleProfileUpdate(userData) {
        console.log('🔄 ProfileSync: Mise à jour du profil reçue', userData);
        
        // Mettre à jour tous les éléments de l'interface
        this.updateHeader(userData);
        this.updateSidebar(userData);
        this.updatePageElements(userData);
        this.updateLocalStorage(userData);
        
        console.log('✅ ProfileSync: Mise à jour terminée');
    }

    updateHeader(userData) {
        // Avatar du header
        const headerAvatar = document.querySelector('.user-avatar, .header-avatar');
        if (headerAvatar) {
            if (userData.avatar && userData.avatar.trim() !== '') {
                if (headerAvatar.tagName === 'IMG') {
                    headerAvatar.src = userData.avatar;
                } else {
                    this.replaceWithImage(headerAvatar, userData.avatar, userData.name);
                }
            } else if (userData.initials) {
                if (headerAvatar.tagName === 'IMG') {
                    this.replaceWithInitials(headerAvatar, userData.initials);
                } else {
                    headerAvatar.textContent = userData.initials;
                }
            }
        }

        // Nom du header
        const headerName = document.querySelector('.user-name, .header-name');
        if (headerName && userData.name) {
            headerName.textContent = userData.name;
        }

        // Email du header
        const headerEmail = document.querySelector('.user-email, .header-email');
        if (headerEmail && userData.email) {
            headerEmail.textContent = userData.email;
        }
    }

    updateSidebar(userData) {
        // Avatar de la sidebar
        const sidebarAvatar = document.querySelector('.sidebar-avatar, .profile-avatar, .nav-avatar');
        if (sidebarAvatar) {
            if (userData.avatar && userData.avatar.trim() !== '') {
                if (sidebarAvatar.tagName === 'IMG') {
                    sidebarAvatar.src = userData.avatar;
                } else {
                    this.replaceWithImage(sidebarAvatar, userData.avatar, userData.name, '60px');
                }
            } else if (userData.initials) {
                if (sidebarAvatar.tagName === 'IMG') {
                    this.replaceWithInitials(sidebarAvatar, userData.initials, '60px');
                } else {
                    sidebarAvatar.textContent = userData.initials;
                }
            }
        }

        // Nom de la sidebar
        const sidebarName = document.querySelector('.sidebar-name, .profile-name, .nav-name');
        if (sidebarName && userData.name) {
            sidebarName.textContent = userData.name;
        }

        // Email de la sidebar
        const sidebarEmail = document.querySelector('.sidebar-email, .profile-email, .nav-email');
        if (sidebarEmail && userData.email) {
            sidebarEmail.textContent = userData.email;
        }
    }

    updatePageElements(userData) {
        // Mettre à jour tous les éléments avec des classes génériques
        const selectors = [
            '.display-name', '.current-name', '.user-display-name',
            '.display-email', '.current-email', '.user-display-email',
            '.display-phone', '.current-phone', '.user-display-phone',
            '.display-location', '.current-location', '.user-display-location',
            '.display-department', '.current-department', '.user-display-department',
            '.display-role', '.current-role', '.user-display-role'
        ];

        selectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                const field = selector.split('-').pop(); // 'name', 'email', etc.
                if (userData[field]) {
                    element.textContent = userData[field];
                }
            });
        });

        // Mettre à jour le titre de la page
        if (userData.name) {
            const title = document.querySelector('title');
            if (title && !title.textContent.includes(userData.name)) {
                const baseTitle = title.textContent.replace(/ - .*$/, '');
                title.textContent = `${baseTitle} - ${userData.name}`;
            }
        }
    }

    updateLocalStorage(userData) {
        if (typeof(Storage) !== "undefined") {
            const sessionData = {
                ...userData,
                lastUpdate: new Date().toISOString()
            };
            localStorage.setItem('userSession', JSON.stringify(sessionData));
        }
    }

    loadSessionData() {
        if (typeof(Storage) !== "undefined") {
            const sessionData = localStorage.getItem('userSession');
            if (sessionData) {
                try {
                    const userData = JSON.parse(sessionData);
                    // Vérifier si les données ne sont pas trop anciennes (1 heure)
                    const lastUpdate = new Date(userData.lastUpdate);
                    const now = new Date();
                    const diffHours = (now - lastUpdate) / (1000 * 60 * 60);
                    
                    if (diffHours < 1) {
                        this.handleProfileUpdate(userData);
                    }
                } catch (e) {
                    console.warn('ProfileSync: Erreur lors du chargement des données de session', e);
                }
            }
        }
    }

    replaceWithImage(element, src, alt, size = '40px') {
        const parent = element.parentNode;
        const newImg = document.createElement('img');
        newImg.className = element.className;
        newImg.style.cssText = `width: ${size}; height: ${size}; border-radius: 50%; object-fit: cover;`;
        newImg.src = src;
        newImg.alt = alt || 'Avatar';
        parent.replaceChild(newImg, element);
    }

    replaceWithInitials(element, initials, size = '40px') {
        const parent = element.parentNode;
        const newDiv = document.createElement('div');
        newDiv.className = element.className;
        newDiv.style.cssText = `width: ${size}; height: ${size}; border-radius: 50%; background: #2563eb; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: ${parseInt(size) * 0.4}px;`;
        newDiv.textContent = initials;
        parent.replaceChild(newDiv, element);
    }
}

// Initialiser ProfileSync quand le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    new ProfileSync();
});

// Exporter pour utilisation globale
window.ProfileSync = ProfileSync;
