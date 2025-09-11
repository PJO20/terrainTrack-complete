// Script JavaScript d'urgence pour supprimer TOUTES les images problématiques
console.log('🚨 Script d\'urgence chargé - Suppression de TOUTES les images problématiques');

// Fonction pour supprimer toutes les images problématiques
function removeAllProblematicImages() {
    console.log('🔍 Suppression de TOUTES les images problématiques...');
    
    // Supprimer TOUTES les images (img tags)
    const allImages = document.querySelectorAll('img');
    allImages.forEach(img => {
        console.log('🗑️ Suppression complète de l\'image:', img);
        img.style.display = 'none';
        img.style.visibility = 'hidden';
        img.style.opacity = '0';
        img.style.width = '0';
        img.style.height = '0';
        img.remove(); // Supprimer complètement l'élément
    });
    
    // Supprimer toutes les images d'arrière-plan
    const elements = document.querySelectorAll('*');
    elements.forEach(element => {
        const computedStyle = window.getComputedStyle(element);
        if (computedStyle.backgroundImage && computedStyle.backgroundImage !== 'none') {
            console.log('🗑️ Suppression de l\'image d\'arrière-plan de:', element);
            element.style.backgroundImage = 'none';
            element.style.background = 'transparent';
        }
    });
    
    // Supprimer toutes les images dans les éléments de profil
    const profileElements = document.querySelectorAll('.profile-avatar, .profile-header, .card, .profile-main, .profile-sidebar');
    profileElements.forEach(element => {
        console.log('🗑️ Nettoyage de l\'élément:', element);
        element.style.backgroundImage = 'none';
        element.style.background = '#f8f9fa';
        
        // Supprimer les pseudo-éléments
        const before = window.getComputedStyle(element, '::before');
        const after = window.getComputedStyle(element, '::after');
        if (before.content !== 'none' || after.content !== 'none') {
            element.style.setProperty('--before-display', 'none');
            element.style.setProperty('--after-display', 'none');
        }
    });
    
    // Forcer l'affichage des initiales pour l'avatar
    const profileAvatar = document.querySelector('.profile-avatar');
    if (profileAvatar) {
        console.log('🎯 Correction de l\'avatar principal');
        profileAvatar.style.backgroundImage = 'none';
        profileAvatar.style.background = '#2563eb';
        profileAvatar.style.color = 'white';
        profileAvatar.style.display = 'flex';
        profileAvatar.style.alignItems = 'center';
        profileAvatar.style.justifyContent = 'center';
        profileAvatar.style.fontSize = '2.5rem';
        profileAvatar.style.fontWeight = '700';
        profileAvatar.style.borderRadius = '50%';
        profileAvatar.style.width = '120px';
        profileAvatar.style.height = '120px';
        profileAvatar.style.maxWidth = '120px';
        profileAvatar.style.maxHeight = '120px';
        profileAvatar.style.minWidth = '120px';
        profileAvatar.style.minHeight = '120px';
        
        // Supprimer COMPLÈTEMENT les images dans l'avatar
        const avatarImages = profileAvatar.querySelectorAll('img');
        avatarImages.forEach(img => {
            console.log('🗑️ Suppression complète de l\'image dans l\'avatar');
            img.remove(); // Supprimer complètement l'élément
        });
        
        // Vider complètement le contenu et afficher les initiales
        profileAvatar.innerHTML = '';
        profileAvatar.textContent = 'M';
    }
    
    // Supprimer les images dans le header
    const headerAvatar = document.querySelector('.user-avatar');
    if (headerAvatar) {
        console.log('🎯 Correction de l\'avatar du header');
        headerAvatar.style.backgroundImage = 'none';
        headerAvatar.style.background = '#2563eb';
        headerAvatar.style.color = 'white';
        headerAvatar.style.display = 'flex';
        headerAvatar.style.alignItems = 'center';
        headerAvatar.style.justifyContent = 'center';
        headerAvatar.style.fontSize = '1rem';
        headerAvatar.style.fontWeight = '600';
        headerAvatar.style.borderRadius = '50%';
        headerAvatar.style.width = '40px';
        headerAvatar.style.height = '40px';
        headerAvatar.style.maxWidth = '40px';
        headerAvatar.style.maxHeight = '40px';
        headerAvatar.style.minWidth = '40px';
        headerAvatar.style.minHeight = '40px';
        
        // Supprimer COMPLÈTEMENT les images dans le header
        const headerImages = headerAvatar.querySelectorAll('img');
        headerImages.forEach(img => {
            console.log('🗑️ Suppression complète de l\'image dans le header');
            img.remove(); // Supprimer complètement l'élément
        });
        
        // Vider complètement le contenu et afficher les initiales
        headerAvatar.innerHTML = '';
        headerAvatar.textContent = 'M';
    }
    
    console.log('✅ Nettoyage terminé');
}

// Exécuter immédiatement
removeAllProblematicImages();

// Exécuter après le chargement de la page
document.addEventListener('DOMContentLoaded', removeAllProblematicImages);

// Exécuter après un délai pour s'assurer que tout est chargé
setTimeout(removeAllProblematicImages, 500);
setTimeout(removeAllProblematicImages, 1000);
setTimeout(removeAllProblematicImages, 2000);
setTimeout(removeAllProblematicImages, 3000);

// Exécuter quand la page est complètement chargée
window.addEventListener('load', removeAllProblematicImages);

// Observer les changements DOM pour contrôler les nouvelles images
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.type === 'childList') {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) { // Element node
                    if (node.tagName === 'IMG') {
                        console.log('🎯 Nouvelle image détectée - application des tailles correctes');
                        // Au lieu de supprimer, forcer les bonnes tailles
                        if (node.classList.contains('profile-avatar')) {
                            node.style.width = '120px';
                            node.style.height = '120px';
                            node.style.maxWidth = '120px';
                            node.style.maxHeight = '120px';
                            node.style.minWidth = '120px';
                            node.style.minHeight = '120px';
                            node.style.borderRadius = '50%';
                            node.style.objectFit = 'cover';
                            node.style.border = '4px solid #fff';
                            node.style.boxShadow = '0 4px 16px rgba(0,0,0,0.15)';
                        } else if (node.classList.contains('user-avatar')) {
                            node.style.width = '40px';
                            node.style.height = '40px';
                            node.style.maxWidth = '40px';
                            node.style.maxHeight = '40px';
                            node.style.minWidth = '40px';
                            node.style.minHeight = '40px';
                            node.style.borderRadius = '50%';
                            node.style.objectFit = 'cover';
                        } else {
                            // Pour toute autre image, la supprimer
                            console.log('🗑️ Image non-avatar supprimée');
                            node.remove();
                        }
                    }
                    // Contrôler les images dans les nouveaux éléments
                    const newImages = node.querySelectorAll && node.querySelectorAll('img');
                    if (newImages) {
                        newImages.forEach(img => {
                            console.log('🎯 Image dans nouvel élément - application des tailles');
                            if (img.classList.contains('profile-avatar')) {
                                img.style.width = '120px';
                                img.style.height = '120px';
                                img.style.maxWidth = '120px';
                                img.style.maxHeight = '120px';
                                img.style.minWidth = '120px';
                                img.style.minHeight = '120px';
                                img.style.borderRadius = '50%';
                                img.style.objectFit = 'cover';
                                img.style.border = '4px solid #fff';
                                img.style.boxShadow = '0 4px 16px rgba(0,0,0,0.15)';
                            } else if (img.classList.contains('user-avatar')) {
                                img.style.width = '40px';
                                img.style.height = '40px';
                                img.style.maxWidth = '40px';
                                img.style.maxHeight = '40px';
                                img.style.minWidth = '40px';
                                img.style.minHeight = '40px';
                                img.style.borderRadius = '50%';
                                img.style.objectFit = 'cover';
                            } else {
                                console.log('🗑️ Image non-avatar dans nouvel élément supprimée');
                                img.remove();
                            }
                        });
                    }
                }
            });
        }
    });
});

// Commencer à observer
observer.observe(document.body, {
    childList: true,
    subtree: true
});

console.log('✅ Script d\'urgence configuré');
