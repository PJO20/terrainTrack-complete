/**
 * Notifications.js - Gestion dynamique de la page notifications
 * Fonctionnalités : recherche, filtres, actions AJAX, rafraîchissement automatique
 */

class NotificationsManager {
    constructor() {
        this.notifications = [];
        this.filteredNotifications = [];
        this.currentFilters = {
            type: 'all',
            status: 'all',
            period: 'all',
            sort: 'date'
        };
        this.searchTerm = '';
        this.selectedNotifications = new Set();
        
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadNotifications();
        this.startAutoRefresh();
    }

    bindEvents() {
        // Recherche
        const searchInput = document.querySelector('input[placeholder="Rechercher..."]');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.searchTerm = e.target.value.toLowerCase();
                this.applyFilters();
            });
        }

        // Bouton "Tout marquer lu"
        const markAllReadBtn = document.querySelector('.btn-primary');
        if (markAllReadBtn && markAllReadBtn.textContent.includes('Tout marquer lu')) {
            markAllReadBtn.addEventListener('click', () => this.markAllAsRead());
        }

        // Filtres de type
        const typeFilters = document.querySelectorAll('.btn-group-custom .btn');
        typeFilters.forEach(btn => {
            btn.addEventListener('click', (e) => {
                // Retirer la classe active de tous les boutons du groupe
                const group = e.target.closest('.btn-group');
                group.querySelectorAll('.btn').forEach(b => b.classList.remove('active'));
                // Ajouter la classe active au bouton cliqué
                e.target.classList.add('active');
                
                this.currentFilters.type = e.target.textContent.toLowerCase();
                this.applyFilters();
            });
        });

        // Filtres de statut
        const statusFilters = document.querySelectorAll('.filter-group:nth-child(2) .btn-group-custom .btn');
        statusFilters.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const group = e.target.closest('.btn-group');
                group.querySelectorAll('.btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                
                this.currentFilters.status = e.target.textContent.toLowerCase();
                this.applyFilters();
            });
        });

        // Filtres de période
        const periodFilters = document.querySelectorAll('.filter-group:nth-child(3) .btn-group-custom .btn');
        periodFilters.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const group = e.target.closest('.btn-group');
                group.querySelectorAll('.btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                
                this.currentFilters.period = e.target.textContent.toLowerCase();
                this.applyFilters();
            });
        });

        // Filtres de tri
        const sortFilters = document.querySelectorAll('.filter-group:nth-child(4) .btn-group-custom .btn');
        sortFilters.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const group = e.target.closest('.btn-group');
                group.querySelectorAll('.btn').forEach(b => b.classList.remove('active'));
                e.target.classList.add('active');
                
                this.currentFilters.sort = e.target.textContent.toLowerCase();
                this.applyFilters();
            });
        });

        // Bouton "Effacer" les filtres
        const clearFiltersBtn = document.querySelector('.filters-header .btn-light');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', () => this.clearFilters());
        }

        // Checkboxes de sélection
        document.addEventListener('change', (e) => {
            if (e.target.type === 'checkbox' && e.target.closest('.notification-item')) {
                const notificationId = e.target.closest('.notification-item').dataset.id;
                if (e.target.checked) {
                    this.selectedNotifications.add(notificationId);
                } else {
                    this.selectedNotifications.delete(notificationId);
                }
                this.updateSelectionUI();
            }
        });

        // Actions sur les notifications (menu 3 points)
        document.addEventListener('click', (e) => {
            if (e.target.closest('.btn-light') && e.target.closest('.notification-item')) {
                const notificationItem = e.target.closest('.notification-item');
                const notificationId = notificationItem.dataset.id;
                
                // Créer un menu contextuel simple
                this.showNotificationMenu(e, notificationId);
            }
        });
    }

    async loadNotifications() {
        try {
            const response = await fetch('/api/notifications');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            
            if (data.success) {
                this.notifications = data.notifications || [];
                this.renderNotifications();
                this.updateStats();
            }
        } catch (error) {
            console.error('Erreur lors du chargement des notifications:', error);
            // Fallback : utiliser les données du template
            this.loadNotificationsFromTemplate();
        }
    }

    loadNotificationsFromTemplate() {
        // Récupérer les notifications depuis le DOM (fallback)
        const notificationItems = document.querySelectorAll('.notification-item');
        this.notifications = Array.from(notificationItems).map(item => {
            const checkbox = item.querySelector('input[type="checkbox"]');
            const title = item.querySelector('.title').textContent.trim();
            const description = item.querySelector('.description').textContent.trim();
            const type = item.querySelector('.badge').textContent.trim();
            const date = item.querySelector('.notification-date').textContent.trim();
            const isRead = !item.classList.contains('read');
            
            return {
                id: item.dataset.id || Math.random().toString(36).substr(2, 9),
                title: title,
                description: description,
                type: type,
                date: date,
                read: !isRead
            };
        });
        
        this.renderNotifications();
        this.updateStats();
    }

    renderNotifications() {
        const container = document.querySelector('.notifications-list');
        if (!container) return;

        container.innerHTML = '';
        
        this.filteredNotifications.forEach(notification => {
            const item = this.createNotificationItem(notification);
            container.appendChild(item);
        });
    }

    createNotificationItem(notification) {
        const item = document.createElement('div');
        item.className = `notification-item ${notification.read ? 'read' : ''}`;
        item.dataset.id = notification.id;
        
        const typeClass = this.getTypeClass(notification.type);
        const icon = this.getIconForType(notification.type);
        
        item.innerHTML = `
            <input type="checkbox" class="form-check-input" ${this.selectedNotifications.has(notification.id) ? 'checked' : ''}>
            <i class='bx ${icon} notification-icon ${typeClass}'></i>
            <div class="notification-content">
                <div class="title">
                    ${notification.title}
                    <span class="badge bg-${typeClass} badge-notification">${notification.type}</span>
                </div>
                <div class="description">${notification.description}</div>
                <div class="meta"><i class='bx bx-link-alt'></i> ${notification.related_to || 'Système'}</div>
            </div>
            <div class="notification-date">${notification.date}</div>
            ${!notification.read ? '<div class="status-dot"></div>' : ''}
            <button class="btn btn-light btn-sm"><i class='bx bx-dots-vertical-rounded'></i></button>
        `;
        
        return item;
    }

    getTypeClass(type) {
        const typeMap = {
            'Alerte': 'danger',
            'Avertissement': 'warning',
            'Information': 'info',
            'Succès': 'success'
        };
        return typeMap[type] || 'info';
    }

    getIconForType(type) {
        const iconMap = {
            'Alerte': 'bx-error-circle',
            'Avertissement': 'bx-error',
            'Information': 'bx-info-circle',
            'Succès': 'bx-check-circle'
        };
        return iconMap[type] || 'bx-info-circle';
    }

    applyFilters() {
        this.filteredNotifications = this.notifications.filter(notification => {
            // Filtre par recherche
            if (this.searchTerm && !this.matchesSearch(notification)) {
                return false;
            }
            
            // Filtre par type
            if (this.currentFilters.type !== 'all' && 
                notification.type.toLowerCase() !== this.currentFilters.type) {
                return false;
            }
            
            // Filtre par statut
            if (this.currentFilters.status === 'non lues' && notification.read) {
                return false;
            }
            if (this.currentFilters.status === 'lues' && !notification.read) {
                return false;
            }
            
            // Filtre par période
            if (this.currentFilters.period !== 'toutes') {
                if (!this.matchesPeriod(notification, this.currentFilters.period)) {
                    return false;
                }
            }
            
            return true;
        });
        
        // Tri
        this.sortNotifications();
        
        this.renderNotifications();
        this.updateStats();
    }

    matchesSearch(notification) {
        const searchText = `${notification.title} ${notification.description} ${notification.type}`.toLowerCase();
        return searchText.includes(this.searchTerm);
    }

    matchesPeriod(notification, period) {
        const today = new Date();
        const notificationDate = this.parseDate(notification.date);
        
        switch (period) {
            case 'aujourd\'hui':
                return this.isSameDay(today, notificationDate);
            case 'cette semaine':
                return this.isThisWeek(today, notificationDate);
            case 'ce mois':
                return this.isThisMonth(today, notificationDate);
            default:
                return true;
        }
    }

    parseDate(dateStr) {
        // Gérer différents formats de date
        if (dateStr.includes('/')) {
            const [day, month, year] = dateStr.split('/');
            return new Date(year, month - 1, day);
        }
        return new Date(dateStr);
    }

    isSameDay(date1, date2) {
        return date1.toDateString() === date2.toDateString();
    }

    isThisWeek(date1, date2) {
        const oneWeekAgo = new Date(date1);
        oneWeekAgo.setDate(date1.getDate() - 7);
        return date2 >= oneWeekAgo && date2 <= date1;
    }

    isThisMonth(date1, date2) {
        return date1.getMonth() === date2.getMonth() && 
               date1.getFullYear() === date2.getFullYear();
    }

    sortNotifications() {
        this.filteredNotifications.sort((a, b) => {
            switch (this.currentFilters.sort) {
                case 'date':
                    return new Date(b.date) - new Date(a.date);
                case 'type':
                    return a.type.localeCompare(b.type);
                case 'titre':
                    return a.title.localeCompare(b.title);
                default:
                    return 0;
            }
        });
    }

    clearFilters() {
        this.searchTerm = '';
        this.currentFilters = {
            type: 'all',
            status: 'all',
            period: 'all',
            sort: 'date'
        };
        
        // Réinitialiser les boutons
        document.querySelectorAll('.btn-group-custom .btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Réactiver les premiers boutons de chaque groupe
        document.querySelectorAll('.btn-group-custom').forEach(group => {
            group.querySelector('.btn').classList.add('active');
        });
        
        // Vider la recherche
        const searchInput = document.querySelector('input[placeholder="Rechercher..."]');
        if (searchInput) {
            searchInput.value = '';
        }
        
        this.applyFilters();
    }

    async markAllAsRead() {
        try {
            const response = await fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Toutes les notifications ont été marquées comme lues', 'success');
                this.loadNotifications(); // Recharger pour mettre à jour l'UI
            } else {
                this.showNotification('Erreur lors du marquage', 'error');
            }
        } catch (error) {
            console.error('Erreur lors du marquage:', error);
            this.showNotification('Erreur de connexion', 'error');
        }
    }

    async markSelectedAsRead() {
        if (this.selectedNotifications.size === 0) {
            this.showNotification('Aucune notification sélectionnée', 'warning');
            return;
        }

        try {
            const response = await fetch('/notifications/mark-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    notification_ids: Array.from(this.selectedNotifications)
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification(`${data.marked_count} notification(s) marquée(s) comme lue(s)`, 'success');
                this.selectedNotifications.clear();
                this.loadNotifications();
            } else {
                this.showNotification('Erreur lors du marquage', 'error');
            }
        } catch (error) {
            console.error('Erreur lors du marquage:', error);
            this.showNotification('Erreur de connexion', 'error');
        }
    }

    async deleteSelected() {
        if (this.selectedNotifications.size === 0) {
            this.showNotification('Aucune notification sélectionnée', 'warning');
            return;
        }

        if (!confirm(`Êtes-vous sûr de vouloir supprimer ${this.selectedNotifications.size} notification(s) ?`)) {
            return;
        }

        try {
            const response = await fetch('/notifications/delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    notification_ids: Array.from(this.selectedNotifications)
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification(`${data.deleted_count} notification(s) supprimée(s)`, 'success');
                this.selectedNotifications.clear();
                this.loadNotifications();
            } else {
                this.showNotification('Erreur lors de la suppression', 'error');
            }
        } catch (error) {
            console.error('Erreur lors de la suppression:', error);
            this.showNotification('Erreur de connexion', 'error');
        }
    }

    updateSelectionUI() {
        const selectedCount = this.selectedNotifications.size;
        const actionButtons = document.querySelector('.header-actions');
        
        // Supprimer les boutons d'action existants
        const existingActionBtns = actionButtons.querySelectorAll('.action-btn');
        existingActionBtns.forEach(btn => btn.remove());
        
        if (selectedCount > 0) {
            // Ajouter les boutons d'action pour les éléments sélectionnés
            const markReadBtn = document.createElement('button');
            markReadBtn.className = 'btn btn-outline-primary btn-sm action-btn';
            markReadBtn.textContent = `Marquer ${selectedCount} comme lu(e)(s)`;
            markReadBtn.addEventListener('click', () => this.markSelectedAsRead());
            
            const deleteBtn = document.createElement('button');
            deleteBtn.className = 'btn btn-outline-danger btn-sm action-btn';
            deleteBtn.textContent = `Supprimer ${selectedCount}`;
            deleteBtn.addEventListener('click', () => this.deleteSelected());
            
            actionButtons.appendChild(markReadBtn);
            actionButtons.appendChild(deleteBtn);
        }
    }

    updateStats() {
        const total = this.filteredNotifications.length;
        const unread = this.filteredNotifications.filter(n => !n.read).length;
        const today = this.filteredNotifications.filter(n => this.matchesPeriod(n, 'aujourd\'hui')).length;
        const alerts = this.filteredNotifications.filter(n => n.type === 'Alerte').length;
        
        // Mettre à jour les statistiques
        const statNumbers = document.querySelectorAll('.stat-number');
        if (statNumbers.length >= 4) {
            statNumbers[0].textContent = total;
            statNumbers[1].textContent = unread;
            statNumbers[2].textContent = today;
            statNumbers[3].textContent = alerts;
        }
        
        // Mettre à jour le sous-titre
        const subtitle = document.querySelector('.page-subtitle');
        if (subtitle) {
            subtitle.textContent = `${unread} non lues sur ${total}`;
        }
    }

    showNotificationMenu(event, notificationId) {
        event.preventDefault();
        
        // Créer un menu contextuel simple
        const menu = document.createElement('div');
        menu.className = 'notification-menu';
        menu.style.cssText = `
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            padding: 8px 0;
            min-width: 150px;
        `;
        
        menu.innerHTML = `
            <div class="menu-item" data-action="mark-read" data-id="${notificationId}">
                <i class='bx bx-check'></i> Marquer comme lu
            </div>
            <div class="menu-item" data-action="mark-unread" data-id="${notificationId}">
                <i class='bx bx-x'></i> Marquer comme non lu
            </div>
            <div class="menu-item" data-action="delete" data-id="${notificationId}">
                <i class='bx bx-trash'></i> Supprimer
            </div>
        `;
        
        // Positionner le menu
        const rect = event.target.getBoundingClientRect();
        menu.style.left = rect.left + 'px';
        menu.style.top = (rect.bottom + 5) + 'px';
        
        // Ajouter les événements
        menu.addEventListener('click', (e) => {
            const menuItem = e.target.closest('.menu-item');
            if (menuItem) {
                const action = menuItem.dataset.action;
                const id = menuItem.dataset.id;
                this.handleMenuAction(action, id);
                document.body.removeChild(menu);
            }
        });
        
        // Fermer le menu en cliquant ailleurs
        document.addEventListener('click', function closeMenu() {
            if (document.body.contains(menu)) {
                document.body.removeChild(menu);
            }
            document.removeEventListener('click', closeMenu);
        });
        
        document.body.appendChild(menu);
    }

    async handleMenuAction(action, notificationId) {
        switch (action) {
            case 'mark-read':
                await this.markNotificationAsRead(notificationId);
                break;
            case 'mark-unread':
                await this.markNotificationAsUnread(notificationId);
                break;
            case 'delete':
                await this.deleteNotification(notificationId);
                break;
        }
    }

    async markNotificationAsRead(notificationId) {
        try {
            const response = await fetch('/notifications/mark-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    notification_ids: [notificationId]
                })
            });
            
            const data = await response.json();
            if (data.success) {
                this.showNotification('Notification marquée comme lue', 'success');
                this.loadNotifications();
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    async markNotificationAsUnread(notificationId) {
        try {
            const response = await fetch('/notifications/mark-unread', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    notification_ids: [notificationId]
                })
            });
            
            const data = await response.json();
            if (data.success) {
                this.showNotification('Notification marquée comme non lue', 'success');
                this.loadNotifications();
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    async deleteNotification(notificationId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette notification ?')) {
            return;
        }

        try {
            const response = await fetch('/notifications/delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    notification_ids: [notificationId]
                })
            });
            
            const data = await response.json();
            if (data.success) {
                this.showNotification('Notification supprimée', 'success');
                this.loadNotifications();
            }
        } catch (error) {
            console.error('Erreur:', error);
        }
    }

    showNotification(message, type = 'info') {
        // Créer une notification toast
        const toast = document.createElement('div');
        toast.className = `notification-toast notification-toast-${type}`;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#d4edda' : type === 'error' ? '#f8d7da' : '#d1ecf1'};
            color: ${type === 'success' ? '#155724' : type === 'error' ? '#721c24' : '#0c5460'};
            border: 1px solid ${type === 'success' ? '#c3e6cb' : type === 'error' ? '#f5c6cb' : '#bee5eb'};
            border-radius: 8px;
            padding: 12px 20px;
            z-index: 10000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease-out;
        `;
        
        toast.textContent = message;
        
        // Ajouter l'animation CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
        
        document.body.appendChild(toast);
        
        // Supprimer après 3 secondes
        setTimeout(() => {
            if (document.body.contains(toast)) {
                document.body.removeChild(toast);
            }
        }, 3000);
    }

    startAutoRefresh() {
        // Rafraîchissement automatique désactivé pour préserver les filtres
        // setInterval(() => {
        //     this.loadNotifications();
        // }, 30000);
        console.log('Actualisation automatique des notifications désactivée pour préserver les filtres');
    }
}

// Initialiser quand le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    new NotificationsManager();
}); 