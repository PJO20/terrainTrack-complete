/**
 * Test simple de l'auto-save
 */

console.log('🧪 Script autosave-test.js chargé');

// Test simple
document.addEventListener('DOMContentLoaded', function() {
    console.log('🧪 DOM prêt - Test auto-save');
    
    // Chercher les formulaires avec la classe autosave
    const forms = document.querySelectorAll('form.autosave');
    console.log('🧪 Formulaires trouvés:', forms.length);
    
    forms.forEach((form, index) => {
        console.log(`🧪 Formulaire ${index + 1}:`, form.id, form.className);
        
        // Test de sauvegarde simple
        const inputs = form.querySelectorAll('input, select, textarea');
        console.log(`🧪 Champs trouvés dans le formulaire ${form.id}:`, inputs.length);
        
        // Activer un timer simple
        setInterval(() => {
            const data = {};
            inputs.forEach(input => {
                if (input.type !== 'password' && input.type !== 'submit' && input.type !== 'button') {
                    data[input.name || input.id] = input.value;
                }
            });
            
            if (Object.keys(data).length > 0) {
                console.log(`💾 Test sauvegarde pour ${form.id}:`, data);
                
                // Sauvegarder dans localStorage
                localStorage.setItem(`test_autosave_${form.id}`, JSON.stringify({
                    data: data,
                    timestamp: Date.now()
                }));
                
                // Afficher une notification
                showTestNotification();
            }
        }, 30000); // 30 secondes
    });
});

function showTestNotification() {
    console.log('💾 Test: Données sauvegardées dans localStorage');
    
    // Créer une notification visuelle
    let notification = document.getElementById('test-autosave-notification');
    if (!notification) {
        notification = document.createElement('div');
        notification.id = 'test-autosave-notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #22c55e;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
            z-index: 9999;
            font-size: 14px;
        `;
        document.body.appendChild(notification);
    }
    
    notification.textContent = '💾 Test: Sauvegardé automatiquement';
    notification.style.display = 'block';
    
    // Masquer après 3 secondes
    setTimeout(() => {
        notification.style.display = 'none';
    }, 3000);
}
