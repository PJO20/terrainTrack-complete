// Vérifier que le script est chargé
console.log('dashboard-map.js chargé');

// Fonction d'initialisation de la carte
window.initMap = function() {
    console.log('Initialisation de la carte...');

    // Vérifier si Leaflet est chargé
    if (typeof L === 'undefined') {
        console.error('Leaflet non chargé');
        return;
    }
    console.log('Leaflet est chargé');

    // Vérifier si le conteneur existe
    var mapContainer = document.getElementById('map');
    if (!mapContainer) {
        console.error('Conteneur de carte non trouvé');
        return;
    }
    console.log('Conteneur de carte trouvé');

    // Initialiser la carte
    var map = L.map('map').setView([48.8566, 2.3522], 12);
    console.log('Carte initialisée');

    // Ajouter la couche de tuiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    console.log('Couche de tuiles ajoutée');

    let vehicleMarkers = [];
    let interventionMarkers = [];

    function randomOffset() {
        return (Math.random() - 0.5) * 0.01;
    }

    function updateMap() {
        console.log('Mise à jour de la carte...');
        
        // Supprimer les anciens marqueurs
        vehicleMarkers.forEach(m => map.removeLayer(m));
        interventionMarkers.forEach(m => map.removeLayer(m));
        vehicleMarkers = [];
        interventionMarkers = [];

        // Données simulées
        const vehicles = [
            { name: 'Quad Explorer X450', type: 'quad', status: 'available', lat: 48.8566 + randomOffset(), lng: 2.3522 + randomOffset() },
            { name: 'Heavy Duty Tractor T-800', type: 'tractor', status: 'inprogress', lat: 48.8656 + randomOffset(), lng: 2.3789 + randomOffset() },
            { name: 'All-Terrain Truck AT-350', type: 'truck', status: 'maintenance', lat: 48.8320 + randomOffset(), lng: 2.3586 + randomOffset() },
            { name: 'Mountain Crawler XL', type: 'truck', status: 'available', lat: 48.8738 + randomOffset(), lng: 2.3652 + randomOffset() },
            { name: 'Utility Quad Pro', type: 'quad', status: 'available', lat: 48.8456 + randomOffset(), lng: 2.3412 + randomOffset() },
            { name: 'Forest Ranger Quad', type: 'quad', status: 'issue', lat: 48.8434 + randomOffset(), lng: 2.3235 + randomOffset() }
        ];

        const statusColors = {
            available: '#22c55e',
            inprogress: '#2563eb',
            maintenance: '#eab308',
            issue: '#ef4444'
        };

        vehicles.forEach(vehicle => {
            const marker = L.circleMarker([vehicle.lat, vehicle.lng], {
                radius: 8,
                fillColor: statusColors[vehicle.status],
                color: '#fff',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.9
            })
            .addTo(map)
            .bindPopup(`<b>${vehicle.name}</b><br>Status: ${vehicle.status.charAt(0).toUpperCase() + vehicle.status.slice(1)}`);
            vehicleMarkers.push(marker);
        });
        
        console.log('Marqueurs mis à jour');
    }

    // Première mise à jour
    updateMap();
    console.log('Première mise à jour effectuée');

    // Mise à jour automatique désactivée pour préserver les filtres
    // setInterval(updateMap, 30000);
    console.log('Actualisation automatique désactivée - utiliser les boutons de rafraîchissement manuel');
}; 