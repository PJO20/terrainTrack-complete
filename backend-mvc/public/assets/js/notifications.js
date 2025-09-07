// === MODAL PARAMÈTRES NOTIFICATIONS ===

// Ouvrir la modal des paramètres
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 === PAGE NOTIFICATIONS CHARGÉE ===');
    
    // **NOUVEAU** : Recharger les statistiques au chargement de la page
    if (window.location.pathname.includes('/notifications')) {
        console.log('📊 Chargement initial des statistiques depuis l\'API');
        setTimeout(() => {
            reloadHeaderNotifications(); // Cela va mettre à jour toutes les stats
        }, 500); // Petit délai pour laisser la page se charger complètement
    }
    
    // **AMÉLIORÉ** : Initialisation multiple des menus avec retry
    function tryInitializeMenus(attempt = 1, maxAttempts = 3) {
        console.log(`🔧 Tentative ${attempt}/${maxAttempts} d'initialisation des menus et checkboxes`);
        
        const notifications = document.querySelectorAll('.notification-item');
        const menuButtons = document.querySelectorAll('.notification-item .btn.btn-light.btn-sm');
        const checkboxes = document.querySelectorAll('.notification-item .form-check-input');
        
        console.log(`📊 ${notifications.length} notifications, ${menuButtons.length} boutons, ${checkboxes.length} cases à cocher`);
        
        if (notifications.length > 0) {
            // **CRITIQUE** : Initialiser SYSTÉMATIQUEMENT les checkboxes
            console.log('📋 Initialisation des checkboxes...');
            initializeBulkSelection();
            
            // Initialiser les menus contextuels
            console.log('📱 Initialisation des menus contextuels...');
            initializeNotificationMenus();
            
            console.log('✅ Initialisation terminée avec succès');
            return true;
        } else if (attempt < maxAttempts) {
            console.log(`⏳ Retry dans 500ms (tentative ${attempt + 1}/${maxAttempts})`);
            setTimeout(() => tryInitializeMenus(attempt + 1, maxAttempts), 500);
        } else {
            console.warn('⚠️ Échec de l\'initialisation après', maxAttempts, 'tentatives');
        }
    }
    
    // Lancer l'initialisation
    tryInitializeMenus();
    
    const settingsBtn = document.querySelector('.btn-outline-secondary');
    const settingsModal = new bootstrap.Modal(document.getElementById('settingsModal'));
    
    if (settingsBtn) {
        settingsBtn.addEventListener('click', function() {
            settingsModal.show();
        });
    }
    
    // Gestion du bouton Sauvegarder
    const saveSettingsBtn = document.getElementById('saveSettings');
    if (saveSettingsBtn) {
        saveSettingsBtn.addEventListener('click', function() {
            // Récupérer les valeurs des paramètres
            const maintenance = document.getElementById('maintenance').checked;
            const interventions = document.getElementById('interventions').checked;
            const teams = document.getElementById('teams').checked;
            const system = document.getElementById('system').checked;
            const frequency = document.getElementById('frequency').value;
            
            // Sauvegarder les paramètres (ici on peut ajouter une requête AJAX)
            console.log('Paramètres sauvegardés:', {
                maintenance,
                interventions,
                teams,
                system,
                frequency
            });
            
            // Fermer la modal
            settingsModal.hide();
            
            // Afficher un toast de confirmation
            showToast('Paramètres sauvegardés avec succès', 'success');
        });
    }
    
    // === FONCTIONNALITÉ "TOUT MARQUER LU" ===
    const markAllReadBtn = document.querySelector('.btn-primary');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
            // Appel AJAX pour marquer toutes les notifications comme lues
            fetch('/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour le compteur dans le header
                    const subtitle = document.querySelector('.page-subtitle');
                    if (subtitle) {
                        const total = document.querySelector('.stat-card:nth-child(1) .stat-number').textContent;
                        subtitle.textContent = `0 non lues sur ${total}`;
                    }
                    
                    // Mettre à jour la carte "Non lues"
                    const unreadCard = document.querySelector('.stat-card:nth-child(2) .stat-number');
                    if (unreadCard) {
                        unreadCard.textContent = '0';
                        unreadCard.classList.remove('text-danger');
                        unreadCard.classList.add('text-success');
                    }
                    
                    // Supprimer le dot rouge de la carte "Non lues"
                    const unreadDot = document.querySelector('.unread-dot');
                    if (unreadDot) {
                        unreadDot.style.display = 'none';
                    }
                    
                    // Marquer toutes les notifications comme lues visuellement
                    const notifications = document.querySelectorAll('.notification-item');
                    notifications.forEach(notification => {
                        // Supprimer la classe "unread"
                        notification.classList.remove('unread');
                        
                        // Supprimer les dots de statut
                        const statusDot = notification.querySelector('.status-dot');
                        if (statusDot) {
                            statusDot.remove();
                        }
                        
                        // Changer le background de bleu clair à blanc
                        notification.style.backgroundColor = '#fff';
                        notification.style.fontWeight = 'normal';
                    });
                    
                    // Mettre à jour le compteur dans le header (icône cloche)
                    updateHeaderNotificationCount(0);
                    
                    // Recharger les notifications du header pour mettre à jour le dropdown
                    reloadHeaderNotifications();
                    
                    // Afficher un toast de confirmation
                    showToast('Toutes les notifications ont été marquées comme lues', 'success');
                } else {
                    showToast('Erreur lors du marquage des notifications', 'danger');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showToast('Erreur lors du marquage des notifications', 'danger');
            });
        });
    }
});

// Fonction pour afficher un toast
function showToast(message, type = 'info') {
    const toastContainer = document.createElement('div');
    toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
    toastContainer.style.zIndex = '9999';
    
    const toast = document.createElement('div');
    toast.className = `toast notification-toast bg-${type} text-white`;
    toast.innerHTML = message;
    
    toastContainer.appendChild(toast);
    document.body.appendChild(toastContainer);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Supprimer le toast après fermeture
    toast.addEventListener('hidden.bs.toast', function() {
        document.body.removeChild(toastContainer);
    });
}

// Fonction pour mettre à jour le compteur de notifications dans le header
function updateHeaderNotificationCount(count) {
    // Mettre à jour le badge dans le header
    const headerBadge = document.querySelector('.notif-badge');
    if (headerBadge) {
        headerBadge.textContent = count;
        headerBadge.style.display = count > 0 ? 'flex' : 'none';
    }
    
    // Mettre à jour le compteur dans le dropdown du header
    const notifCount = document.getElementById('notifCount');
    if (notifCount) {
        notifCount.textContent = count;
    }
    
    // Mettre à jour le titre de la page si on est sur la page notifications
    const pageSubtitle = document.querySelector('.page-subtitle');
    if (pageSubtitle) {
        const total = document.querySelector('.stat-card:nth-child(1) .stat-number')?.textContent || '0';
        pageSubtitle.textContent = `${count} non lues sur ${total}`;
    }
}

// Fonction pour recharger les notifications du header
function reloadHeaderNotifications() {
    // Faire un appel AJAX pour récupérer les données mises à jour
    fetch('/api/notifications')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('📡 Données API reçues:', data);
                
                // Mettre à jour le badge du header
                const headerBadge = document.querySelector('.notif-badge');
                if (headerBadge) {
                    headerBadge.textContent = data.unreadCount;
                    headerBadge.style.display = data.unreadCount > 0 ? 'flex' : 'none';
                }
                
                // Mettre à jour le compteur dans le dropdown
                const notifCount = document.getElementById('notifCount');
                if (notifCount) {
                    notifCount.textContent = data.unreadCount;
                }
                
                // Mettre à jour le contenu du dropdown avec les nouvelles notifications
                if (data.notifications) {
                    updateNotificationDropdown(data.notifications);
                }
                
                // **NOUVEAU** : Mettre à jour les statistiques de la page notifications si on est dessus
                updatePageStatisticsFromAPI(data);
                
                console.log('✅ Header notifications rechargé - Non lues:', data.unreadCount);
            }
        })
        .catch(error => {
            console.error('❌ Erreur lors du rechargement des notifications header:', error);
        });
}

// **NOUVELLE FONCTION** : Met à jour les statistiques de la page avec les données de l'API
function updatePageStatisticsFromAPI(apiData) {
    // Vérifier si on est sur la page des notifications
    if (!window.location.pathname.includes('/notifications')) {
        return; // Ne rien faire si on n'est pas sur la page des notifications
    }
    
    console.log('📊 Mise à jour des statistiques de la page avec les données API');
    
    const statCards = document.querySelectorAll('.stat-card .stat-number');
    const pageSubtitle = document.querySelector('.page-subtitle');
    
    // Mettre à jour les cartes de statistiques
    if (statCards[0] && apiData.totalCount !== undefined) {
        statCards[0].textContent = apiData.totalCount; // Total
        console.log('📊 Carte Total mise à jour:', apiData.totalCount);
    }
    
    if (statCards[1] && apiData.unreadCount !== undefined) {
        statCards[1].textContent = apiData.unreadCount; // Non lues
        // Mettre à jour la couleur selon le nombre de non lues
        statCards[1].classList.remove('text-success', 'text-danger');
        if (apiData.unreadCount > 0) {
            statCards[1].classList.add('text-danger');
        } else {
            statCards[1].classList.add('text-success');
        }
        console.log('📊 Carte Non lues mise à jour:', apiData.unreadCount);
    }
    
    if (statCards[2] && apiData.todayCount !== undefined) {
        statCards[2].textContent = apiData.todayCount; // Aujourd'hui
        console.log('📊 Carte Aujourd\'hui mise à jour:', apiData.todayCount);
    }
    
    if (statCards[3] && apiData.alertsCount !== undefined) {
        statCards[3].textContent = apiData.alertsCount; // Alertes
        console.log('📊 Carte Alertes mise à jour:', apiData.alertsCount);
    }
    
    // Mettre à jour le sous-titre de la page
    if (pageSubtitle && apiData.unreadCount !== undefined && apiData.totalCount !== undefined) {
        pageSubtitle.textContent = `${apiData.unreadCount} non lues sur ${apiData.totalCount}`;
        console.log('📊 Sous-titre mis à jour:', `${apiData.unreadCount} non lues sur ${apiData.totalCount}`);
    }
    
    // Mettre à jour le dot de la carte "Non lues"
    const unreadDot = document.querySelector('.unread-dot');
    if (unreadDot && apiData.unreadCount !== undefined) {
        unreadDot.style.display = apiData.unreadCount > 0 ? 'block' : 'none';
    }
    
    console.log('✅ Statistiques de la page mises à jour depuis l\'API');
}

// Nouvelle fonction pour mettre à jour le dropdown des notifications
function updateNotificationDropdown(notifications) {
    const notificationList = document.querySelector('.notification-dropdown .list-group');
    if (!notificationList) return;
    
    // Vider le contenu actuel
    notificationList.innerHTML = '';
    
    if (notifications.length === 0) {
        notificationList.innerHTML = '<div class="list-group-item text-center text-muted py-3">Aucune notification récente</div>';
        return;
    }
    
    // Afficher les 5 dernières notifications
    const recentNotifications = notifications.slice(0, 5);
    recentNotifications.forEach(notification => {
        const notifElement = document.createElement('div');
        notifElement.className = `list-group-item ${!notification.read ? 'unread' : ''}`;
        notifElement.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <h6 class="mb-1">${notification.title}</h6>
                    <p class="mb-1 small text-muted">${notification.description}</p>
                    <small class="text-muted">${formatTimeAgo(notification.created_at)}</small>
                </div>
                ${!notification.read ? '<div class="status-dot"></div>' : ''}
            </div>
        `;
        notificationList.appendChild(notifElement);
    });
    
    console.log('✅ Dropdown notifications mis à jour avec', recentNotifications.length, 'notifications');
}

// === GESTION DE LA SÉLECTION MULTIPLE ===
let selectedNotifications = new Set();

// Initialiser la sélection multiple
document.addEventListener('DOMContentLoaded', function() {
    initializeBulkSelection();
});

function initializeBulkSelection() {
    console.log('📋 === INITIALISATION SÉLECTION MULTIPLE (RENFORCÉE) ===');
    
    const checkboxes = document.querySelectorAll('.notification-item .form-check-input');
    const bulkContainer = document.getElementById('bulkActionsContainer');
    const selectedCount = document.getElementById('selectedCount');
    
    console.log('📋 Cases à cocher trouvées:', checkboxes.length);
    console.log('📋 Container actions bulk:', !!bulkContainer);
    console.log('📋 Compteur sélection:', !!selectedCount);
    
    // Réinitialiser la sélection
    selectedNotifications.clear();
    updateBulkActionsVisibility();
    
    // **CRITIQUE** : Attacher les event listeners directement à chaque checkbox
    checkboxes.forEach((checkbox, index) => {
        console.log(`📋 Traitement checkbox ${index + 1}/${checkboxes.length}`);
        
        const notificationItem = checkbox.closest('.notification-item');
        const notificationId = notificationItem?.dataset?.id;
        
        if (!notificationId) {
            console.warn(`⚠️ Checkbox ${index + 1} sans ID de notification`);
            return;
        }
        
        // **IMPORTANT** : Supprimer les anciens event listeners
        checkbox.removeEventListener('change', handleCheckboxChange);
        
        // **NOUVEAU** : Event listener direct et simple
        const changeHandler = function(e) {
            console.log('📋 === CHANGEMENT CHECKBOX ===');
            console.log('📋 Checkbox changée pour notification ID:', notificationId);
            console.log('📋 État:', e.target.checked ? 'cochée' : 'décochée');
            
            if (e.target.checked) {
                selectedNotifications.add(notificationId);
                console.log('➕ Notification ajoutée à la sélection');
            } else {
                selectedNotifications.delete(notificationId);
                console.log('➖ Notification retirée de la sélection');
            }
            
            console.log('📊 Total sélectionnées:', selectedNotifications.size);
            updateBulkActionsVisibility();
        };
        
        // Attacher le nouvel event listener
        checkbox.addEventListener('change', changeHandler);
        
        // Marquer comme initialisé
        checkbox.setAttribute('data-checkbox-initialized', 'true');
        console.log(`✅ Checkbox ${index + 1} initialisée pour ID: ${notificationId}`);
    });
    
    // **NOUVEAU** : Initialiser aussi le bouton de suppression
    const deleteBtn = document.getElementById('deleteBtn');
    if (deleteBtn) {
        console.log('🗑️ Initialisation du bouton supprimer...');
        
        // Supprimer l'ancien listener
        deleteBtn.removeEventListener('click', handleDeleteClick);
        
        // Ajouter le nouveau listener
        const deleteHandler = function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🗑️ === CLIC BOUTON SUPPRIMER ===');
            console.log('🗑️ Notifications sélectionnées:', Array.from(selectedNotifications));
            
            if (selectedNotifications.size === 0) {
                console.warn('⚠️ Aucune notification sélectionnée');
                showToast('Aucune notification sélectionnée', 'warning', 3000);
                return;
            }
            
            // Lancer la suppression
            deleteSelected();
        };
        
        deleteBtn.addEventListener('click', deleteHandler);
        console.log('✅ Bouton supprimer initialisé');
    } else {
        console.warn('⚠️ Bouton supprimer non trouvé');
    }
    
    // **NOUVEAU** : Initialiser les boutons "Marquer lues" et "Marquer non lues"
    const markReadBtn = document.getElementById('markReadBtn');
    const markUnreadBtn = document.getElementById('markUnreadBtn');
    const cancelBtn = document.getElementById('cancelSelectionBtn');
    
    if (markReadBtn) {
        console.log('👁️ Initialisation du bouton marquer lues...');
        
        // Supprimer l'ancien listener
        markReadBtn.removeEventListener('click', markSelectedAsRead);
        
        // Ajouter le nouveau listener
        const markReadHandler = function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('👁️ === CLIC BOUTON MARQUER LUES ===');
            console.log('👁️ Notifications sélectionnées:', Array.from(selectedNotifications));
            
            if (selectedNotifications.size === 0) {
                console.warn('⚠️ Aucune notification sélectionnée');
                showToast('Aucune notification sélectionnée', 'warning', 3000);
                return;
            }
            
            // Lancer le marquage comme lues
            markSelectedAsRead();
        };
        
        markReadBtn.addEventListener('click', markReadHandler);
        console.log('✅ Bouton marquer lues initialisé');
    } else {
        console.warn('⚠️ Bouton marquer lues non trouvé');
    }
    
    if (markUnreadBtn) {
        console.log('🔄 Initialisation du bouton marquer non lues...');
        
        // Supprimer l'ancien listener
        markUnreadBtn.removeEventListener('click', markSelectedAsUnread);
        
        // Ajouter le nouveau listener
        const markUnreadHandler = function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🔄 === CLIC BOUTON MARQUER NON LUES ===');
            console.log('🔄 Notifications sélectionnées:', Array.from(selectedNotifications));
            
            if (selectedNotifications.size === 0) {
                console.warn('⚠️ Aucune notification sélectionnée');
                showToast('Aucune notification sélectionnée', 'warning', 3000);
                return;
            }
            
            // Lancer le marquage comme non lues
            markSelectedAsUnread();
        };
        
        markUnreadBtn.addEventListener('click', markUnreadHandler);
        console.log('✅ Bouton marquer non lues initialisé');
    } else {
        console.warn('⚠️ Bouton marquer non lues non trouvé');
    }
    
    if (cancelBtn) {
        console.log('❌ Initialisation du bouton annuler...');
        
        // Supprimer l'ancien listener
        cancelBtn.removeEventListener('click', cancelSelection);
        
        // Ajouter le nouveau listener
        const cancelHandler = function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('❌ === CLIC BOUTON ANNULER ===');
            
            // Lancer l'annulation
            cancelSelection();
        };
        
        cancelBtn.addEventListener('click', cancelHandler);
        console.log('✅ Bouton annuler initialisé');
    } else {
        console.warn('⚠️ Bouton annuler non trouvé');
    }
    
    const initializedCheckboxes = document.querySelectorAll('[data-checkbox-initialized="true"]');
    console.log(`✅ ${initializedCheckboxes.length}/${checkboxes.length} checkboxes initialisées`);
    console.log('📋 === FIN INITIALISATION SÉLECTION MULTIPLE ===');
}

function updateBulkActionsVisibility() {
    const bulkContainer = document.getElementById('bulkActionsContainer');
    const selectedCount = document.getElementById('selectedCount');
    
    if (selectedNotifications.size > 0) {
        bulkContainer.style.display = 'block';
        selectedCount.textContent = `${selectedNotifications.size} notification${selectedNotifications.size > 1 ? 's' : ''} sélectionnée${selectedNotifications.size > 1 ? 's' : ''}`;
    } else {
        bulkContainer.style.display = 'none';
    }
}

function markSelectedAsRead() {
    if (selectedNotifications.size === 0) {
        showToast('Aucune notification sélectionnée', 'warning', 3000);
        return;
    }
    
    const notificationIds = Array.from(selectedNotifications);
    console.log('📖 === MARQUAGE COMME LUES (CORRIGÉ) ===');
    console.log('📖 Marquage de', notificationIds.length, 'notification(s) comme lues');
    console.log('📖 IDs:', notificationIds);

    // **ROBUSTE** : Trouver les éléments avec sélecteurs multiples
    const elementsToUpdate = [];
    notificationIds.forEach(id => {
        // **MÊME LOGIQUE** que deleteSelected : sélecteurs multiples
        let element = document.querySelector(`div.notification-item[data-id="${id}"]`);
        if (!element) {
            element = document.querySelector(`[data-id="${id}"].notification-item`);
        }
        if (!element) {
            element = document.querySelector(`*[data-id="${id}"]`);
        }
        
        if (element) {
            elementsToUpdate.push({id, element});
            console.log('📖 Element trouvé pour ID', id);
        } else {
            console.error('❌ Element introuvable pour ID:', id);
        }
    });

    console.log(`📊 ${elementsToUpdate.length}/${notificationIds.length} éléments trouvés à marquer`);

    if (elementsToUpdate.length === 0) {
        console.error('❌ AUCUN élément trouvé à marquer !');
        showToast('Erreur : éléments introuvables', 'error', 5000);
        return;
    }
    
    // **OPTIMISTE** : Mise à jour immédiate de l'interface
    elementsToUpdate.forEach(({id, element}) => {
        // Afficher un indicateur de traitement
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'processing-indicator';
        loadingIndicator.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i>';
        loadingIndicator.style.cssText = 'position: absolute; top: 10px; right: 10px; z-index: 10; background: rgba(255,255,255,0.9); border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; color: #666;';
        element.style.position = 'relative';
        element.style.opacity = '0.7';
        element.appendChild(loadingIndicator);
        
        // **IMMÉDIAT** : Mise à jour visuelle
        element.classList.remove('unread', 'selected');
        const checkbox = element.querySelector('.form-check-input');
        if (checkbox) checkbox.checked = false;
        
        // **IMMÉDIAT** : Supprimer le point bleu (status-dot)
        const statusDot = element.querySelector('.status-dot');
        if (statusDot) {
            statusDot.remove();
            console.log('📖 Point bleu supprimé pour ID', id);
        }
    });
    
    // Réinitialiser la sélection immédiatement
    selectedNotifications.clear();
    updateBulkActionsVisibility();
    
    // Envoyer la requête au serveur
    fetch('/notifications/mark-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_ids: notificationIds })
    })
    .then(response => response.json())
    .then(data => {
        // Supprimer les indicateurs de chargement
        elementsToUpdate.forEach(({id, element}) => {
            const loadingIndicator = element.querySelector('.processing-indicator');
            if (loadingIndicator) {
                loadingIndicator.remove();
            }
            element.style.opacity = '1';
        });
        
        if (data.success) {
            console.log('✅', data.marked_count, 'notification(s) marquée(s) comme lues');
            
            // **APRÈS** : Mettre à jour les statistiques
            console.log('🔄 Mise à jour des stats après marquage en lot (API)');
            reloadHeaderNotifications(); // Cela met à jour header + page si on y est
            
            showToast(`${data.marked_count} notification${data.marked_count > 1 ? 's' : ''} marquée${data.marked_count > 1 ? 's' : ''} comme lue${data.marked_count > 1 ? 's' : ''}`, 'success', 3000);
            
        } else {
            console.error('❌ Erreur lors du marquage comme lu:', data.error || 'Erreur inconnue');
            
            // **ROLLBACK** : Restaurer l'état en cas d'erreur
            elementsToUpdate.forEach(({id, element}) => {
                element.classList.add('unread');
                
                // Remettre le point bleu
                if (!element.querySelector('.status-dot')) {
                    const statusDot = document.createElement('div');
                    statusDot.className = 'status-dot';
                    const titleElement = element.querySelector('.title');
                    if (titleElement) {
                        titleElement.appendChild(statusDot);
                    }
                }
            });
            
            // **ROLLBACK** : Restaurer les stats
            console.log('🔄 Restauration des stats après erreur (API)');
            reloadHeaderNotifications();
            
            showToast('Erreur lors du marquage comme lu: ' + (data.error || 'Erreur inconnue'), 'error', 5000);
        }
    })
    .catch(error => {
        console.error('❌ Erreur réseau lors du marquage comme lu:', error);
        
        // **ROLLBACK** : Supprimer les indicateurs de chargement et restaurer
        elementsToUpdate.forEach(({id, element}) => {
            const loadingIndicator = element.querySelector('.processing-indicator');
            if (loadingIndicator) {
                loadingIndicator.remove();
            }
            element.style.opacity = '1';
            element.classList.add('unread');
            
            // Remettre le point bleu
            if (!element.querySelector('.status-dot')) {
                const statusDot = document.createElement('div');
                statusDot.className = 'status-dot';
                const titleElement = element.querySelector('.title');
                if (titleElement) {
                    titleElement.appendChild(statusDot);
                }
            }
        });
        
        // **ROLLBACK** : Restaurer les stats
        console.log('🔄 Restauration des stats après erreur réseau (API)');
        reloadHeaderNotifications();
        
        showToast('Erreur de connexion lors du marquage comme lu', 'error', 5000);
    });
}

function markSelectedAsUnread() {
    if (selectedNotifications.size === 0) {
        showToast('Aucune notification sélectionnée', 'warning', 3000);
        return;
    }
    
    const notificationIds = Array.from(selectedNotifications);
    console.log('📬 === MARQUAGE COMME NON LUES (CORRIGÉ) ===');
    console.log('📬 Marquage de', notificationIds.length, 'notification(s) comme non lues');
    console.log('📬 IDs:', notificationIds);

    // **ROBUSTE** : Trouver les éléments avec sélecteurs multiples
    const elementsToUpdate = [];
    notificationIds.forEach(id => {
        // **MÊME LOGIQUE** que deleteSelected : sélecteurs multiples
        let element = document.querySelector(`div.notification-item[data-id="${id}"]`);
        if (!element) {
            element = document.querySelector(`[data-id="${id}"].notification-item`);
        }
        if (!element) {
            element = document.querySelector(`*[data-id="${id}"]`);
        }
        
        if (element) {
            elementsToUpdate.push({id, element});
            console.log('📬 Element trouvé pour ID', id);
        } else {
            console.error('❌ Element introuvable pour ID:', id);
        }
    });

    console.log(`📊 ${elementsToUpdate.length}/${notificationIds.length} éléments trouvés à marquer`);

    if (elementsToUpdate.length === 0) {
        console.error('❌ AUCUN élément trouvé à marquer !');
        showToast('Erreur : éléments introuvables', 'error', 5000);
        return;
    }
    
    // **OPTIMISTE** : Mise à jour immédiate de l'interface
    elementsToUpdate.forEach(({id, element}) => {
        // Afficher un indicateur de traitement
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'processing-indicator';
        loadingIndicator.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i>';
        loadingIndicator.style.cssText = 'position: absolute; top: 10px; right: 10px; z-index: 10; background: rgba(255,255,255,0.9); border-radius: 50%; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; color: #666;';
        element.style.position = 'relative';
        element.style.opacity = '0.7';
        element.appendChild(loadingIndicator);
        
        // **IMMÉDIAT** : Mise à jour visuelle
        element.classList.add('unread');
        element.classList.remove('selected');
        const checkbox = element.querySelector('.form-check-input');
        if (checkbox) checkbox.checked = false;
        
        // **IMMÉDIAT** : Ajouter le point bleu (status-dot)
        if (!element.querySelector('.status-dot')) {
            const statusDot = document.createElement('div');
            statusDot.className = 'status-dot';
            const titleElement = element.querySelector('.title');
            if (titleElement) {
                titleElement.appendChild(statusDot);
                console.log('📬 Point bleu ajouté pour ID', id);
            }
        }
    });
    
    // Réinitialiser la sélection immédiatement
    selectedNotifications.clear();
    updateBulkActionsVisibility();
    
    // Envoyer la requête au serveur
    fetch('/notifications/mark-unread', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_ids: notificationIds })
    })
    .then(response => response.json())
    .then(data => {
        // Supprimer les indicateurs de chargement
        elementsToUpdate.forEach(({id, element}) => {
            const loadingIndicator = element.querySelector('.processing-indicator');
            if (loadingIndicator) {
                loadingIndicator.remove();
            }
            element.style.opacity = '1';
        });
        
        if (data.success) {
            console.log('✅', data.marked_count, 'notification(s) marquée(s) comme non lues');
            
            // **APRÈS** : Mettre à jour les statistiques
            console.log('🔄 Mise à jour des stats après marquage en lot (API)');
            reloadHeaderNotifications(); // Cela met à jour header + page si on y est
            
            showToast(`${data.marked_count} notification${data.marked_count > 1 ? 's' : ''} marquée${data.marked_count > 1 ? 's' : ''} comme non lue${data.marked_count > 1 ? 's' : ''}`, 'success', 3000);
            
        } else {
            console.error('❌ Erreur lors du marquage comme non lu:', data.error || 'Erreur inconnue');
            
            // **ROLLBACK** : Restaurer l'état en cas d'erreur
            elementsToUpdate.forEach(({id, element}) => {
                element.classList.remove('unread');
                
                // Supprimer le point bleu
                const statusDot = element.querySelector('.status-dot');
                if (statusDot) {
                    statusDot.remove();
                }
            });
            
            // **ROLLBACK** : Restaurer les stats
            console.log('🔄 Restauration des stats après erreur (API)');
            reloadHeaderNotifications();
            
            showToast('Erreur lors du marquage comme non lu: ' + (data.error || 'Erreur inconnue'), 'error', 5000);
        }
    })
    .catch(error => {
        console.error('❌ Erreur réseau lors du marquage comme non lu:', error);
        
        // **ROLLBACK** : Supprimer les indicateurs de chargement et restaurer
        elementsToUpdate.forEach(({id, element}) => {
            const loadingIndicator = element.querySelector('.processing-indicator');
            if (loadingIndicator) {
                loadingIndicator.remove();
            }
            element.style.opacity = '1';
            element.classList.remove('unread');
            
            // Supprimer le point bleu
            const statusDot = element.querySelector('.status-dot');
            if (statusDot) {
                statusDot.remove();
            }
        });
        
        // **ROLLBACK** : Restaurer les stats
        console.log('🔄 Restauration des stats après erreur réseau (API)');
        reloadHeaderNotifications();
        
        showToast('Erreur de connexion lors du marquage comme non lu', 'error', 5000);
    });
}

// Fonction d'aide pour mettre à jour les statistiques avec des valeurs spécifiques
function updateStatsWithCounts(total = null, unread = null) {
    // Pour les actions en lot, on utilise aussi l'API pour avoir des données cohérentes
    console.log('🔄 Mise à jour des stats avec appel API (action en lot)');
    reloadHeaderNotifications(); // Cela va automatiquement mettre à jour toutes les stats
}

function deleteSelected() {
    if (selectedNotifications.size === 0) {
        showToast('Aucune notification sélectionnée', 'warning', 3000);
        return;
    }

    const count = selectedNotifications.size;
    const notificationIds = Array.from(selectedNotifications);
    console.log('🗑️ === DÉBUT SUPPRESSION EN LOT (CORRIGÉE) ===');
    console.log('🗑️ Notifications à supprimer:', notificationIds);

    // **CRITIQUE** : Utiliser un sélecteur plus précis
    const elementsToRemove = [];
    notificationIds.forEach(id => {
        // **NOUVEAU** : Multiple sélecteurs pour assurer qu'on trouve l'élément
        let element = document.querySelector(`div.notification-item[data-id="${id}"]`);
        if (!element) {
            element = document.querySelector(`[data-id="${id}"].notification-item`);
        }
        if (!element) {
            element = document.querySelector(`*[data-id="${id}"]`);
        }
        
        if (element) {
            elementsToRemove.push({id, element});
            console.log('🗑️ Element trouvé pour ID', id, ':', element.outerHTML.substring(0, 100) + '...');
        } else {
            console.error('❌ AUCUN Element trouvé pour ID:', id);
            // **DEBUG** : Lister tous les éléments avec data-id
            const allDataIdElements = document.querySelectorAll('[data-id]');
            console.log('🔍 Tous les éléments avec data-id:', Array.from(allDataIdElements).map(el => ({
                id: el.getAttribute('data-id'),
                tag: el.tagName,
                classes: el.className
            })));
        }
    });

    console.log(`📊 ${elementsToRemove.length}/${notificationIds.length} éléments trouvés à supprimer`);

    if (elementsToRemove.length === 0) {
        console.error('❌ AUCUN élément trouvé à supprimer !');
        showToast('Erreur : éléments introuvables', 'error', 5000);
        return;
    }

    // Afficher la modal de confirmation
    showDeleteConfirmationModal(count, async () => {
        try {
            console.log('🗑️ Confirmation reçue, envoi de la requête...');
            
            // **OPTIMISTIQUE** : Animation immédiate des éléments trouvés
            elementsToRemove.forEach(({element}) => {
                element.style.transition = 'all 0.3s ease';
                element.style.opacity = '0.5';
                element.style.transform = 'translateX(-20px)';
                element.style.filter = 'grayscale(100%)';
                element.style.pointerEvents = 'none'; // Empêcher les interactions
            });

            const response = await fetch('/notifications/delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    notification_ids: notificationIds
                })
            });

            const data = await response.json();
            console.log('🗑️ Réponse serveur:', data);

            if (data.success) {
                console.log('✅ Suppression confirmée par le serveur');
                
                // **IMMEDIAT** : Supprimer les éléments du DOM sans délai
                let suppressedCount = 0;
                elementsToRemove.forEach(({id, element}) => {
                    console.log(`🗑️ Suppression immédiate DOM pour ID: ${id}`);
                    try {
                        if (element && element.parentNode) {
                            // **DOUBLE VÉRIFICATION** : L'élément existe encore ?
                            if (document.contains(element)) {
                                element.remove();
                                suppressedCount++;
                                console.log(`✅ Element ID ${id} supprimé du DOM avec succès`);
                            } else {
                                console.warn(`⚠️ Element ID ${id} déjà supprimé du DOM`);
                            }
                        } else {
                            console.warn(`⚠️ Element ID ${id} n'a plus de parent`);
                        }
                    } catch (removeError) {
                        console.error(`❌ Erreur lors de la suppression DOM pour ID ${id}:`, removeError);
                    }
                });

                console.log(`📊 ${suppressedCount}/${elementsToRemove.length} éléments supprimés du DOM`);

                // Nettoyer la sélection
                selectedNotifications.clear();
                updateBulkActionsVisibility();

                // **APRÈS** : Mettre à jour les statistiques
                console.log('🔄 Mise à jour des statistiques après suppression');
                reloadHeaderNotifications();

                // Afficher le message de succès
                const deletedCount = data.deleted_count || suppressedCount;
                showToast(`${deletedCount} notification${deletedCount > 1 ? 's' : ''} supprimée${deletedCount > 1 ? 's' : ''}`, 'success', 3000);

                // Vérifier s'il n'y a plus de notifications
                const remainingNotifications = document.querySelectorAll('.notification-item');
                console.log(`📊 ${remainingNotifications.length} notifications restantes`);
                if (remainingNotifications.length === 0) {
                    showNoNotificationsMessage();
                }

            } else {
                console.error('❌ Erreur serveur:', data.error || 'Erreur inconnue');
                
                // **ROLLBACK** : Restaurer l'apparence des éléments
                elementsToRemove.forEach(({element}) => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateX(0)';
                    element.style.filter = 'none';
                    element.style.pointerEvents = 'auto';
                });
                
                showToast('Erreur lors de la suppression: ' + (data.error || 'Erreur inconnue'), 'error', 5000);
            }

        } catch (error) {
            console.error('❌ Erreur réseau:', error);
            
            // **ROLLBACK** : Restaurer l'apparence des éléments
            elementsToRemove.forEach(({element}) => {
                element.style.opacity = '1';
                element.style.transform = 'translateX(0)';
                element.style.filter = 'none';
                element.style.pointerEvents = 'auto';
            });
            
            showToast('Erreur de connexion lors de la suppression', 'error', 5000);
        }
    });
}

// Fonction pour afficher la modal de confirmation de suppression
function showDeleteConfirmationModal(count, onConfirm) {
    // Supprimer la modal existante si elle existe
    const existingModal = document.getElementById('deleteConfirmationModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Créer la modal
    const modal = document.createElement('div');
    modal.id = 'deleteConfirmationModal';
    modal.className = 'delete-confirmation-modal';
    modal.innerHTML = `
        <div class="delete-confirmation-overlay">
            <div class="delete-confirmation-dialog">
                <div class="delete-confirmation-header">
                    <div class="delete-confirmation-icon">
                        <i class='bx bx-trash'></i>
                    </div>
                    <h3 class="delete-confirmation-title">Confirmer la suppression</h3>
                </div>
                <div class="delete-confirmation-body">
                    <p class="delete-confirmation-message">
                        Êtes-vous sûr de vouloir supprimer <strong>${count} notification${count > 1 ? 's' : ''}</strong> ?
                    </p>
                    <p class="delete-confirmation-warning">
                        <i class='bx bx-error-circle'></i>
                        Cette action est irréversible.
                    </p>
                </div>
                <div class="delete-confirmation-footer">
                    <button class="btn btn-outline-secondary delete-confirmation-cancel">
                        <i class='bx bx-x'></i> Annuler
                    </button>
                    <button class="btn btn-danger delete-confirmation-confirm">
                        <i class='bx bx-trash'></i> Supprimer
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Ajouter au body
    document.body.appendChild(modal);
    
    // Event listeners
    const cancelBtn = modal.querySelector('.delete-confirmation-cancel');
    const confirmBtn = modal.querySelector('.delete-confirmation-confirm');
    const overlay = modal.querySelector('.delete-confirmation-overlay');
    
    // Fermer sur clic du bouton Annuler ou de l'overlay
    cancelBtn.addEventListener('click', () => {
        modal.remove();
    });
    
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            modal.remove();
        }
    });
    
    // Confirmer la suppression
    confirmBtn.addEventListener('click', () => {
        modal.remove();
        onConfirm();
    });
    
    // Fermer avec la touche Escape
    document.addEventListener('keydown', function handleEscape(e) {
        if (e.key === 'Escape') {
            modal.remove();
            document.removeEventListener('keydown', handleEscape);
        }
    });
    
    // Animation d'entrée
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
}

function cancelSelection() {
    // Décocher toutes les checkboxes
    const checkboxes = document.querySelectorAll('.notification-item .form-check-input:checked');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
        const notificationItem = checkbox.closest('.notification-item');
        notificationItem.classList.remove('selected');
    });
    
    // Réinitialiser la sélection
    selectedNotifications.clear();
    updateBulkActionsVisibility();
}

// Fonction pour afficher le message "Aucune notification"
function showNoNotificationsMessage() {
    const notificationsList = document.querySelector('.notifications-list');
    if (notificationsList) {
        notificationsList.innerHTML = `
            <div class="no-notifications">
                <i class='bx bx-bell'></i>
                <p>Aucune notification</p>
            </div>
        `;
    }
}

// === FILTRAGE DYNAMIQUE DES NOTIFICATIONS ===
let currentFilters = {
    type: null,
    readStatus: 'all',
    period: 'all',
    sort: 'date_desc'
};

// Initialiser le filtrage
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initialisation des filtres...'); // Debug
    initializeFilters();
    initializeSearch();
    initializeNotificationMenus(); // Ajouter l'initialisation des menus
    
    // S'assurer que les filtres par défaut sont bien appliqués
    setTimeout(() => {
        console.log('Application des filtres par défaut...'); // Debug
        applyFilters();
    }, 100);
});

function initializeFilters() {
    // Filtres par type (recherche par label spécifique)
    const typeFilterGroup = Array.from(document.querySelectorAll('.filter-group')).find(group => 
        group.querySelector('.filter-label')?.textContent?.includes('Type de notification')
    );
    if (typeFilterGroup) {
        const typeButtons = typeFilterGroup.querySelectorAll('.btn');
        typeButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const type = this.textContent.trim().toLowerCase();
                toggleFilterButton(this, typeButtons);
                currentFilters.type = type;
                applyFilters();
            });
        });
    }

    // Filtres par statut de lecture (recherche par label spécifique)
    const statusFilterGroup = Array.from(document.querySelectorAll('.filter-group')).find(group => 
        group.querySelector('.filter-label')?.textContent?.includes('Statut de lecture')
    );
    if (statusFilterGroup) {
        const statusButtons = statusFilterGroup.querySelectorAll('.btn');
        statusButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const status = this.textContent.trim().toLowerCase();
                toggleFilterButton(this, statusButtons);
                currentFilters.readStatus = getReadStatusFilter(status);
                applyFilters();
            });
        });
    }

    // Filtres par période (recherche par label spécifique)
    const periodFilterGroup = Array.from(document.querySelectorAll('.filter-group')).find(group => 
        group.querySelector('.filter-label')?.textContent?.includes('Période')
    );
    if (periodFilterGroup) {
        const periodButtons = periodFilterGroup.querySelectorAll('.btn');
        periodButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const period = this.textContent.trim().toLowerCase();
                toggleFilterButton(this, periodButtons);
                currentFilters.period = getPeriodFilter(period);
                applyFilters();
            });
        });
    }

    // Filtres de tri (recherche par label spécifique)
    const sortFilterGroup = Array.from(document.querySelectorAll('.filter-group')).find(group => 
        group.querySelector('.filter-label')?.textContent?.includes('Trier par')
    );
    if (sortFilterGroup) {
        const sortButtons = sortFilterGroup.querySelectorAll('.btn');
        sortButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                console.log('🔄 === CLIC BOUTON TRI ===');
                
                const sort = this.textContent.trim().toLowerCase();
                console.log('🔄 Texte bouton:', sort);
                
                // **SPÉCIAL** : Gestion du bouton Date avec alternance des flèches
                if (sort.includes('date')) {
                    console.log('🔄 Bouton Date cliqué');
                    
                    // Désactiver tous les boutons de tri
                    sortButtons.forEach(b => b.classList.remove('active'));
                    
                    // Activer ce bouton
                    this.classList.add('active');
                    
                    // **ALTERNANCE** : Vérifier la flèche actuelle et inverser
                    if (sort.includes('↓')) {
                        // Actuellement ↓ (plus ancien d'abord), passer à ↑ (plus récent d'abord)
                        this.innerHTML = '<i class=\'bx bx-sort-up\'></i> Date ↑';
                        currentFilters.sort = 'date_desc'; // Plus récent d'abord
                        console.log('🔄 Changement vers ↑ (plus récent d\'abord) - date_desc');
                    } else {
                        // Actuellement ↑ ou par défaut, passer à ↓ (plus ancien d'abord)
                        this.innerHTML = '<i class=\'bx bx-sort-down\'></i> Date ↓';
                        currentFilters.sort = 'date_asc'; // Plus ancien d'abord
                        console.log('🔄 Changement vers ↓ (plus ancien d\'abord) - date_asc');
                    }
                    
                    applyFilters();
                    return;
                }
                
                // **SPÉCIAL** : Gestion du bouton Type avec alternance des flèches
                if (sort.includes('type')) {
                    console.log('🔄 Bouton Type cliqué');
                    
                    // Désactiver tous les boutons de tri
                    sortButtons.forEach(b => b.classList.remove('active'));
                    
                    // Activer ce bouton
                    this.classList.add('active');
                    
                    // **ALTERNANCE** : Vérifier la flèche actuelle et inverser
                    if (sort.includes('↓')) {
                        // Actuellement ↓ (moins important d'abord), passer à ↑ (plus important d'abord)
                        this.innerHTML = '<i class=\'bx bx-sort-up\'></i> Type ↑';
                        currentFilters.sort = 'type_asc'; // Plus important d'abord (Alerte → Info → Succès → Avertissement)
                        console.log('🔄 Changement vers ↑ (plus important d\'abord) - type_asc');
                    } else {
                        // Actuellement ↑ ou par défaut, passer à ↓ (moins important d'abord)
                        this.innerHTML = '<i class=\'bx bx-sort-down\'></i> Type ↓';
                        currentFilters.sort = 'type_desc'; // Moins important d'abord (Avertissement → Succès → Info → Alerte)
                        console.log('🔄 Changement vers ↓ (moins important d\'abord) - type_desc');
                    }
                    
                    applyFilters();
                    return;
                }
                
                // **AUTRES BOUTONS** : Gestion classique
                toggleFilterButton(this, sortButtons);
                currentFilters.sort = getSortFilter(sort);
                applyFilters();
            });
        });
        
        // **INITIALISATION** : S'assurer que l'état initial est correct
        const dateButton = Array.from(sortButtons).find(btn => 
            btn.textContent.toLowerCase().includes('date')
        );
        if (dateButton && dateButton.classList.contains('active')) {
            // Si le bouton Date est actif, vérifier qu'il a le bon état
            const currentText = dateButton.textContent.trim().toLowerCase();
            if (currentText.includes('↓')) {
                // État initial correct : ↓ = plus ancien d'abord
                currentFilters.sort = 'date_asc';
                console.log('🎯 État initial: Date ↓ (plus ancien d\'abord) - date_asc');
            } else {
                // Corriger l'état
                dateButton.innerHTML = '<i class=\'bx bx-sort-down\'></i> Date ↓';
                currentFilters.sort = 'date_asc';
                console.log('🎯 État initial corrigé: Date ↓ (plus ancien d\'abord) - date_asc');
            }
        }
    }

    // Bouton "Effacer les filtres"
    const clearButton = document.querySelector('.filters-header .btn-light');
    if (clearButton) {
        clearButton.addEventListener('click', clearAllFilters);
    }
}

function toggleFilterButton(clickedBtn, allButtons) {
    // Désactiver tous les autres boutons du groupe
    allButtons.forEach(btn => btn.classList.remove('active'));
    
    // Activer le bouton cliqué
    clickedBtn.classList.add('active');
}

function getReadStatusFilter(status) {
    console.log('Filtre statut de lecture:', status, '→', status.toLowerCase() === 'toutes' ? 'all' : status.toLowerCase() === 'non lues' ? 'unread' : status.toLowerCase() === 'lues' ? 'read' : 'all'); // Debug simplifié
    
    switch(status.toLowerCase()) {
        case 'toutes': 
            return 'all';
        case 'non lues': 
            return 'unread';
        case 'lues': 
            return 'read';
        default: 
            return 'all';
    }
}

function getPeriodFilter(period) {
    console.log('Filtre période demandé:', period); // Debug
    
    const periodLower = period.toLowerCase().trim();
    
    switch(periodLower) {
        case 'toutes': 
            console.log('→ Retour: all');
            return 'all';
        case "aujourd'hui":
        case 'aujourdhui':
            console.log('→ Retour: today');
            return 'today';
        case 'cette semaine':
        case 'semaine':
            console.log('→ Retour: week');
            return 'week';
        case 'ce mois':
        case 'mois':
            console.log('→ Retour: month');
            return 'month';
        default: 
            console.log('→ Retour par défaut: all');
            return 'all';
    }
}

function getSortFilter(sort) {
    console.log('Filtre tri demandé:', sort); // Debug
    
    const sortLower = sort.toLowerCase().trim();
    
    // **NOUVEAU** : Gestion spécifique pour le tri par date avec flèches
    if (sortLower.includes('date')) {
        if (sortLower.includes('↑')) {
            console.log('→ Retour: date_desc (plus récent d\'abord)');
            return 'date_desc';
        } else if (sortLower.includes('↓')) {
            console.log('→ Retour: date_asc (plus ancien d\'abord)');
            return 'date_asc';
        } else {
            console.log('→ Retour par défaut: date_desc');
            return 'date_desc';
        }
    }
    
    // **NOUVEAU** : Gestion spécifique pour le tri par type avec flèches
    if (sortLower.includes('type')) {
        if (sortLower.includes('↑')) {
            console.log('→ Retour: type_asc (plus important d\'abord)');
            return 'type_asc';
        } else if (sortLower.includes('↓')) {
            console.log('→ Retour: type_desc (moins important d\'abord)');
            return 'type_desc';
        } else {
            console.log('→ Retour par défaut: type_asc');
            return 'type_asc';
        }
    }
    
    if (sortLower.includes('titre')) {
        console.log('→ Retour: title_asc');
        return 'title_asc';
    }
    
    console.log('→ Retour par défaut: date_desc');
    return 'date_desc';
}

function applyFilters() {
    console.log('Application des filtres:', currentFilters); // Debug
    
    const notifications = document.querySelectorAll('.notification-item');
    let visibleCount = 0;
    let visibleUnreadCount = 0;

    notifications.forEach(notification => {
        let shouldShow = true;

        // Filtre par type
        if (currentFilters.type && currentFilters.type !== 'toutes' && shouldShow) {
            const typeElement = notification.querySelector('.badge-notification');
            const notificationType = typeElement ? typeElement.textContent.trim().toLowerCase() : '';
            shouldShow = notificationType.includes(currentFilters.type);
        }

        // Filtre par statut de lecture
        if (currentFilters.readStatus !== 'all' && shouldShow) {
            const isUnread = notification.classList.contains('unread');
            
            if (currentFilters.readStatus === 'unread' && !isUnread) {
                shouldShow = false;
            }
            if (currentFilters.readStatus === 'read' && isUnread) {
                shouldShow = false;
            }
        }

        // Filtre par période (approximatif basé sur la position dans la liste)
        if (currentFilters.period !== 'all' && shouldShow) {
            console.log(`Test période pour notification ${notification.dataset.id || 'sans-id'}`); // Debug
            shouldShow = matchesPeriod(notification, currentFilters.period);
            console.log(`→ Résultat période: ${shouldShow}`); // Debug
        }

        // Filtre par recherche
        if (searchTerm && shouldShow) {
            shouldShow = matchesSearch(notification, searchTerm);
        }

        // Afficher ou masquer la notification
        if (shouldShow) {
            notification.style.display = 'flex';
            visibleCount++;
            if (notification.classList.contains('unread')) {
                visibleUnreadCount++;
            }
        } else {
            notification.style.display = 'none';
        }
    });

    // Trier les notifications visibles
    sortVisibleNotifications(currentFilters.sort);

    // Mettre à jour les statistiques
    updateFilteredStats(visibleCount, visibleUnreadCount);

    // Afficher un message si aucune notification ne correspond aux filtres
    showFilterMessage(visibleCount);
    
    console.log(`Filtrage terminé: ${visibleCount} notifications visibles, ${visibleUnreadCount} non lues`); // Debug
}

// === RECHERCHE DYNAMIQUE ===
let searchTerm = '';

function initializeSearch() {
    const searchInput = document.querySelector('.page-header .search-box input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            searchTerm = this.value.toLowerCase().trim();
            applyFilters(); // Réappliquer tous les filtres avec la recherche
        });

        // Vider la recherche avec Escape
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                searchTerm = '';
                applyFilters();
            }
        });
    }
}

function matchesPeriod(notification, period) {
    // Récupérer la date de la notification
    const dateElement = notification.querySelector('.notification-date');
    if (!dateElement) return true; // Si pas de date, afficher par défaut
    
    const dateText = dateElement.textContent.trim();
    if (!dateText) return true;
    
    console.log('Analyse de la période pour:', dateText, 'filtre:', period); // Debug
    
    // Obtenir la date actuelle
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    
    try {
        let notificationDate = null;
        
        // Format français DD/MM/YYYY (le plus courant selon les exemples)
        const frenchDateMatch = dateText.match(/(\d{1,2})\/(\d{1,2})\/(\d{4})/);
        if (frenchDateMatch) {
            const [, day, month, year] = frenchDateMatch;
            notificationDate = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
        }
        // Format relatif: "Il y a X minutes/heures/jours"
        else if (dateText.includes('Il y a')) {
            if (dateText.includes('minute') || dateText.includes('min')) {
                notificationDate = today; // Aujourd'hui
            } else if (dateText.includes('heure') || dateText.includes('h')) {
                notificationDate = today; // Aujourd'hui
            } else if (dateText.includes('jour') || dateText.includes('j')) {
                const days = parseInt(dateText.match(/\d+/)?.[0] || '0');
                notificationDate = new Date(today);
                notificationDate.setDate(today.getDate() - days);
            }
        }
        // Format: "À l'instant"
        else if (dateText.toLowerCase().includes('instant')) {
            notificationDate = today;
        }
        // Format: "Aujourd'hui"
        else if (dateText.toLowerCase().includes('aujourd')) {
            notificationDate = today;
        }
        // Format: "Hier"
        else if (dateText.toLowerCase().includes('hier')) {
            notificationDate = new Date(today);
            notificationDate.setDate(today.getDate() - 1);
        }
        // Format de dropdown du header: "2h", "5min", "3j"
        else if (/^\d+min$/.test(dateText)) {
            notificationDate = today; // Minutes = aujourd'hui
        }
        else if (/^\d+h$/.test(dateText)) {
            notificationDate = today; // Heures = aujourd'hui
        }
        else if (/^\d+j$/.test(dateText)) {
            const days = parseInt(dateText.match(/\d+/)?.[0] || '0');
            notificationDate = new Date(today);
            notificationDate.setDate(today.getDate() - days);
        }
        // Fallback: essayer de parser comme date standard
        else {
            notificationDate = new Date(dateText);
            if (isNaN(notificationDate.getTime())) {
                console.log('Date non parsable:', dateText);
                return true; // En cas d'échec, afficher par défaut
            }
        }
        
        // Si on n'arrive pas à parser la date, afficher par défaut
        if (!notificationDate || isNaN(notificationDate.getTime())) {
            console.log('Date non parsable:', dateText);
            return true;
        }
        
        console.log('Date parsée:', notificationDate.toDateString(), 'vs aujourd\'hui:', today.toDateString()); // Debug
        
        // Calculer les périodes
        const weekStart = new Date(today);
        weekStart.setDate(today.getDate() - today.getDay()); // Début de semaine (dimanche)
        
        const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
        
        // Filtrer selon la période demandée
        switch(period) {
            case 'today':
                const isToday = notificationDate.toDateString() === today.toDateString();
                console.log('Test aujourd\'hui:', isToday);
                return isToday;
                
            case 'week':
                const isThisWeek = notificationDate >= weekStart && notificationDate <= now;
                console.log('Test cette semaine:', isThisWeek, 'weekStart:', weekStart.toDateString());
                return isThisWeek;
                
            case 'month':
                const isThisMonth = notificationDate >= monthStart && notificationDate <= now;
                console.log('Test ce mois:', isThisMonth, 'monthStart:', monthStart.toDateString());
                return isThisMonth;
                
            default:
                return true;
        }
        
    } catch (error) {
        console.error('Erreur lors du parsing de la date:', dateText, error);
        return true; // En cas d'erreur, afficher par défaut
    }
}

function matchesSearch(notification, term) {
    if (!term) return true;
    
    const title = notification.querySelector('.title')?.textContent?.toLowerCase() || '';
    const description = notification.querySelector('.description')?.textContent?.toLowerCase() || '';
    const meta = notification.querySelector('.meta')?.textContent?.toLowerCase() || '';
    
    return title.includes(term) || description.includes(term) || meta.includes(term);
}

function sortVisibleNotifications(sortType) {
    console.log('Tri des notifications par:', sortType); // Debug
    
    const container = document.querySelector('.notifications-list');
    if (!container) return;
    
    // Tri des notifications (sans indicateur de chargement)
    
    // Récupérer toutes les notifications visibles (pas seulement celles avec style="flex")
    const notifications = Array.from(container.querySelectorAll('.notification-item')).filter(notification => {
        return notification.style.display !== 'none';
    });
    
    console.log('Notifications à trier:', notifications.length); // Debug
    
    notifications.sort((a, b) => {
        switch(sortType) {
            case 'type_asc':
                // **NOUVEAU** : Tri par importance (Plus important d'abord)
                // Ordre : Alerte → Info → Succès → Avertissement
                const typeA = a.querySelector('.badge-notification')?.textContent?.trim() || '';
                const typeB = b.querySelector('.badge-notification')?.textContent?.trim() || '';
                console.log(`Tri type ASC (importance): "${typeA}" vs "${typeB}"`); // Debug
                
                const importanceOrder = {
                    'Alerte': 1,    // Plus important
                    'Information': 2,
                    'Succès': 3,
                    'Avertissement': 4  // Moins important
                };
                
                const orderA = importanceOrder[typeA] || 999; // Valeur par défaut si type inconnu
                const orderB = importanceOrder[typeB] || 999;
                
                return orderA - orderB; // Ordre croissant d'importance
                
            case 'type_desc':
                // **NOUVEAU** : Tri par importance inverse (Moins important d'abord)
                // Ordre : Avertissement → Succès → Info → Alerte
                const typeA_desc = a.querySelector('.badge-notification')?.textContent?.trim() || '';
                const typeB_desc = b.querySelector('.badge-notification')?.textContent?.trim() || '';
                console.log(`Tri type DESC (importance inverse): "${typeA_desc}" vs "${typeB_desc}"`); // Debug
                
                const importanceOrderDesc = {
                    'Avertissement': 1, // Moins important d'abord
                    'Succès': 2,
                    'Information': 3,
                    'Alerte': 4         // Plus important à la fin
                };
                
                const orderA_desc = importanceOrderDesc[typeA_desc] || 999;
                const orderB_desc = importanceOrderDesc[typeB_desc] || 999;
                
                return orderA_desc - orderB_desc; // Ordre croissant d'importance inverse
                
            case 'title_asc':
                // Récupérer le titre en excluant le badge et le status dot
                const titleElementA = a.querySelector('.title');
                const titleElementB = b.querySelector('.title');
                
                let titleA = '';
                let titleB = '';
                
                if (titleElementA) {
                    // Cloner l'élément pour manipuler sans affecter l'original
                    const cloneA = titleElementA.cloneNode(true);
                    // Supprimer les badges et status dots du clone
                    cloneA.querySelectorAll('.badge, .status-dot').forEach(el => el.remove());
                    titleA = cloneA.textContent?.trim() || '';
                }
                
                if (titleElementB) {
                    const cloneB = titleElementB.cloneNode(true);
                    cloneB.querySelectorAll('.badge, .status-dot').forEach(el => el.remove());
                    titleB = cloneB.textContent?.trim() || '';
                }
                
                console.log(`Tri titre: "${titleA}" vs "${titleB}"`); // Debug
                return titleA.localeCompare(titleB);
                
            case 'date_desc':
            default:
                // Tri par date (plus récent en premier)
                const dateA = a.querySelector('.notification-date')?.textContent?.trim() || '';
                const dateB = b.querySelector('.notification-date')?.textContent?.trim() || '';
                
                console.log(`Tri date DESC: "${dateA}" vs "${dateB}"`); // Debug
                
                // Parser les dates pour un tri correct
                const parsedDateA = parseNotificationDate(dateA);
                const parsedDateB = parseNotificationDate(dateB);
                
                // Plus récent en premier (ordre décroissant)
                return parsedDateB.getTime() - parsedDateA.getTime();
                
            case 'date_asc':
                // Tri par date (plus ancien en premier)
                const dateA_asc = a.querySelector('.notification-date')?.textContent?.trim() || '';
                const dateB_asc = b.querySelector('.notification-date')?.textContent?.trim() || '';
                
                console.log(`Tri date ASC: "${dateA_asc}" vs "${dateB_asc}"`); // Debug
                
                // Parser les dates pour un tri correct
                const parsedDateA_asc = parseNotificationDate(dateA_asc);
                const parsedDateB_asc = parseNotificationDate(dateB_asc);
                
                // Plus ancien en premier (ordre croissant)
                return parsedDateA_asc.getTime() - parsedDateB_asc.getTime();
        }
    });

    // Animer la réorganisation
    notifications.forEach((notification, index) => {
        notification.style.transition = 'all 0.3s ease';
        notification.style.transform = 'translateX(-10px)';
        notification.style.opacity = '0.7';
        
        setTimeout(() => {
            container.appendChild(notification);
            notification.style.transform = 'translateX(0)';
            notification.style.opacity = '1';
        }, index * 50); // Délai progressif pour un effet visuel
    });
    
    // Tri terminé
    console.log('Tri terminé'); // Debug
}

// Fonction showSortingIndicator supprimée - inutile car le tri est instantané

// Fonction helper pour parser les dates des notifications
function parseNotificationDate(dateText) {
    if (!dateText) return new Date(0); // Date très ancienne pour les éléments sans date
    
    const today = new Date();
    const todayMidnight = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    
    try {
        // Format français DD/MM/YYYY
        const frenchDateMatch = dateText.match(/(\d{1,2})\/(\d{1,2})\/(\d{4})/);
        if (frenchDateMatch) {
            const [, day, month, year] = frenchDateMatch;
            return new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
        }
        
        // Format relatif: "Il y a X minutes/heures/jours"
        if (dateText.includes('Il y a')) {
            if (dateText.includes('minute') || dateText.includes('min')) {
                const minutes = parseInt(dateText.match(/\d+/)?.[0] || '0');
                return new Date(today.getTime() - minutes * 60 * 1000);
            } else if (dateText.includes('heure') || dateText.includes('h')) {
                const hours = parseInt(dateText.match(/\d+/)?.[0] || '0');
                return new Date(today.getTime() - hours * 60 * 60 * 1000);
            } else if (dateText.includes('jour') || dateText.includes('j')) {
                const days = parseInt(dateText.match(/\d+/)?.[0] || '0');
                return new Date(todayMidnight.getTime() - days * 24 * 60 * 60 * 1000);
            }
        }
        
        // Format: "À l'instant"
        if (dateText.toLowerCase().includes('instant')) {
            return today;
        }
        
        // Format: "Aujourd'hui"
        if (dateText.toLowerCase().includes('aujourd')) {
            return todayMidnight;
        }
        
        // Format: "Hier"
        if (dateText.toLowerCase().includes('hier')) {
            return new Date(todayMidnight.getTime() - 24 * 60 * 60 * 1000);
        }
        
        // Format court: "2h", "5min", "3j"
        if (/^\d+min$/.test(dateText)) {
            const minutes = parseInt(dateText.match(/\d+/)?.[0] || '0');
            return new Date(today.getTime() - minutes * 60 * 1000);
        }
        if (/^\d+h$/.test(dateText)) {
            const hours = parseInt(dateText.match(/\d+/)?.[0] || '0');
            return new Date(today.getTime() - hours * 60 * 60 * 1000);
        }
        if (/^\d+j$/.test(dateText)) {
            const days = parseInt(dateText.match(/\d+/)?.[0] || '0');
            return new Date(todayMidnight.getTime() - days * 24 * 60 * 60 * 1000);
        }
        
        // Fallback: essayer de parser comme date standard
        const fallbackDate = new Date(dateText);
        return isNaN(fallbackDate.getTime()) ? new Date(0) : fallbackDate;
        
    } catch (error) {
        console.error('Erreur lors du parsing de la date pour le tri:', dateText, error);
        return new Date(0); // Date très ancienne en cas d'erreur
    }
}

function updateFilteredStats(visibleCount, visibleUnreadCount) {
    // Mettre à jour les cartes de statistiques
    const statCards = document.querySelectorAll('.stat-card .stat-number');
    const pageSubtitle = document.querySelector('.page-subtitle');
    
    if (statCards[0]) {
        statCards[0].textContent = visibleCount;
    }
    
    if (statCards[1]) {
        statCards[1].textContent = visibleUnreadCount;
        if (visibleUnreadCount > 0) {
            statCards[1].classList.remove('text-success');
            statCards[1].classList.add('text-danger');
        } else {
            statCards[1].classList.remove('text-danger');
            statCards[1].classList.add('text-success');
        }
    }
    
    if (pageSubtitle) {
        pageSubtitle.textContent = `${visibleUnreadCount} non lues sur ${visibleCount}`;
    }

    // Mettre à jour le dot
    const unreadDot = document.querySelector('.unread-dot');
    if (unreadDot) {
        unreadDot.style.display = visibleUnreadCount > 0 ? 'block' : 'none';
    }
}

function showFilterMessage(visibleCount) {
    const container = document.querySelector('.notifications-list');
    let filterMessage = container.querySelector('.filter-no-results');
    
    if (visibleCount === 0) {
        if (!filterMessage) {
            filterMessage = document.createElement('div');
            filterMessage.className = 'filter-no-results';
            filterMessage.style.cssText = `
                text-align: center;
                padding: 3rem 2rem;
                color: #6b7280;
                font-size: 1.1rem;
            `;
            filterMessage.innerHTML = `
                <i class='bx bx-filter' style='font-size: 3rem; margin-bottom: 1rem; display: block; opacity: 0.5;'></i>
                <p><strong>Aucune notification ne correspond aux filtres</strong></p>
                <p style='font-size: 0.95rem; margin-top: 0.5rem;'>Essayez de modifier ou d'effacer vos filtres</p>
            `;
            container.appendChild(filterMessage);
        }
        filterMessage.style.display = 'block';
    } else {
        if (filterMessage) {
            filterMessage.style.display = 'none';
        }
    }
}

function clearAllFilters() {
    // Réinitialiser les filtres
    currentFilters = {
        type: null,
        readStatus: 'all',
        period: 'all',
        sort: 'date_desc'
    };

    // Réinitialiser tous les boutons et activer les bons boutons par défaut
    const filterGroups = document.querySelectorAll('.filter-group');
    filterGroups.forEach(group => {
        const buttons = group.querySelectorAll('.btn');
        const label = group.querySelector('.filter-label')?.textContent?.trim();
        
        // Désactiver tous les boutons
        buttons.forEach(btn => btn.classList.remove('active'));
        
        // Activer le bon bouton par défaut selon le type de filtre
        if (label?.includes('Statut de lecture')) {
            // Pour "Statut de lecture", activer "Toutes"
            const toutesBtn = Array.from(buttons).find(btn => 
                btn.textContent.trim().toLowerCase() === 'toutes'
            );
            if (toutesBtn) toutesBtn.classList.add('active');
        } else if (label?.includes('Période')) {
            // Pour "Période", activer "Toutes"
            const toutesBtn = Array.from(buttons).find(btn => 
                btn.textContent.trim().toLowerCase() === 'toutes'
            );
            if (toutesBtn) toutesBtn.classList.add('active');
        } else if (label?.includes('Trier par')) {
            // Pour "Trier par", activer le premier bouton (Date)
            if (buttons[0]) buttons[0].classList.add('active');
        }
        // Pour "Type de notification", ne pas activer de bouton par défaut
    });

    // Vider la barre de recherche si elle existe
    const searchInput = document.querySelector('.page-header .search-box input');
    if (searchInput) {
        searchInput.value = '';
        searchTerm = '';
    }

    // Réappliquer les filtres (tout afficher)
    applyFilters();
}

// Fonction pour recharger les statistiques de la page
function reloadPageStats() {
    // Recalculer les statistiques directement depuis le DOM
    const notifications = document.querySelectorAll('.notification-item:not([style*="display: none"])'); // Exclure les notifications cachées par filtres
    const allNotifications = document.querySelectorAll('.notification-item'); // Toutes les notifications pour le total
    const unreadNotifications = document.querySelectorAll('.notification-item.unread:not([style*="display: none"])'); // Non lues visibles
    
    const total = allNotifications.length;
    const unread = unreadNotifications.length;
    
    console.log('📊 Statistiques recalculées - Total:', total, 'Non lues:', unread);
    
    // Mettre à jour les cartes de statistiques
    const statCards = document.querySelectorAll('.stat-card .stat-number');
    if (statCards[0]) {
        statCards[0].textContent = total; // Total
        console.log('📊 Carte Total mise à jour:', total);
    }
    
    if (statCards[1]) {
        statCards[1].textContent = unread; // Non lues
        // Mettre à jour la couleur selon le nombre de non lues
        statCards[1].classList.remove('text-success', 'text-danger');
        if (unread > 0) {
            statCards[1].classList.add('text-danger');
        } else {
            statCards[1].classList.add('text-success');
        }
        console.log('📊 Carte Non lues mise à jour:', unread);
    }
    
    // Mettre à jour le sous-titre de la page
    const pageSubtitle = document.querySelector('.page-subtitle');
    if (pageSubtitle) {
        pageSubtitle.textContent = `${unread} non lues sur ${total}`;
        console.log('📊 Sous-titre mis à jour:', `${unread} non lues sur ${total}`);
    }
    
    // Mettre à jour le dot de la carte "Non lues"
    const unreadDot = document.querySelector('.unread-dot');
    if (unreadDot) {
        unreadDot.style.display = unread > 0 ? 'block' : 'none';
    }
    
    // Mettre à jour les statistiques "Aujourd'hui" et "Alertes" si elles existent
    updateTodayAndAlertsStats();
    
    // Vérifier s'il faut afficher le message "Aucune notification"
    if (total === 0) {
        showNoNotificationsMessage();
    } else {
        hideNoNotificationsMessage();
    }
    
    console.log('✅ Statistiques de la page mises à jour');
}

// Fonction pour mettre à jour les statistiques "Aujourd'hui" et "Alertes"
function updateTodayAndAlertsStats() {
    const today = new Date().toISOString().split('T')[0]; // Date d'aujourd'hui au format YYYY-MM-DD
    
    // Compter les notifications d'aujourd'hui
    let todayCount = 0;
    let alertsCount = 0;
    
    const notifications = document.querySelectorAll('.notification-item:not([style*="display: none"])');
    notifications.forEach(notification => {
        // Récupérer la date depuis l'attribut data ou le contenu
        const dateElement = notification.querySelector('.time') || notification.querySelector('[data-date]');
        if (dateElement) {
            const notificationDate = dateElement.dataset.date || dateElement.textContent;
            if (notificationDate && notificationDate.includes(today)) {
                todayCount++;
            }
        }
        
        // Vérifier si c'est une alerte (critique ou de type alerte)
        if (notification.classList.contains('critical') || 
            notification.querySelector('.badge-danger') ||
            notification.textContent.toLowerCase().includes('alerte') ||
            notification.textContent.toLowerCase().includes('urgent')) {
            alertsCount++;
        }
    });
    
    // Mettre à jour les cartes statistiques si elles existent
    const statCards = document.querySelectorAll('.stat-card .stat-number');
    if (statCards[2]) {
        statCards[2].textContent = todayCount; // Aujourd'hui
    }
    if (statCards[3]) {
        statCards[3].textContent = alertsCount; // Alertes
    }
}

// Fonction pour masquer le message "Aucune notification"
function hideNoNotificationsMessage() {
    const notificationsList = document.querySelector('.notifications-list');
    if (notificationsList) {
        notificationsList.innerHTML = ''; // Vider le contenu pour masquer le message
    }
}

// Fonction de test pour le tri (à supprimer en production)
function testSorting() {
    console.log('=== TEST DU TRI ===');
    console.log('Tri par date...');
    currentFilters.sort = 'date_desc';
    sortVisibleNotifications('date_desc');
    
    setTimeout(() => {
        console.log('Tri par type...');
        currentFilters.sort = 'type_asc';
        sortVisibleNotifications('type_asc');
    }, 2000);
    
    setTimeout(() => {
        console.log('Tri par titre...');
        currentFilters.sort = 'title_asc';
        sortVisibleNotifications('title_asc');
    }, 4000);
    
    setTimeout(() => {
        console.log('Retour au tri par date...');
        currentFilters.sort = 'date_desc';
        sortVisibleNotifications('date_desc');
    }, 6000);
}

// Exposer la fonction de test globalement pour les tests manuels
window.testSorting = testSorting;

// Fonction de test pour la suppression (à supprimer en production)
function testNotificationDeletion() {
    console.log('=== TEST DE SUPPRESSION ===');
    
    const firstNotification = document.querySelector('.notification-item');
    if (firstNotification) {
        const notificationId = firstNotification.dataset.id;
        console.log('Test de suppression sur notification ID:', notificationId);
        
        // Simuler le clic sur le bouton menu
        const menuButton = firstNotification.querySelector('.btn.btn-light.btn-sm');
        if (menuButton) {
            console.log('Bouton menu trouvé, simulation du clic...');
            const fakeEvent = {
                target: menuButton,
                preventDefault: () => {},
                stopPropagation: () => {}
            };
            showNotificationContextMenu(fakeEvent, notificationId, firstNotification);
        } else {
            console.warn('Bouton menu non trouvé dans la première notification');
        }
    } else {
        console.warn('Aucune notification trouvée pour le test');
    }
}

// Exposer la fonction de test pour la suppression
window.testNotificationDeletion = testNotificationDeletion;

// Debug: afficher des informations sur les notifications au chargement
window.debugNotifications = function() {
    console.log('=== DEBUG NOTIFICATIONS ===');
    const notifications = document.querySelectorAll('.notification-item');
    console.log('Nombre de notifications:', notifications.length);
    
    notifications.forEach((notif, index) => {
        const id = notif.dataset.id;
        const title = notif.querySelector('.title')?.textContent?.trim();
        const hasMenuButton = !!notif.querySelector('.btn.btn-light.btn-sm');
        const hasDotsIcon = !!notif.querySelector('.bx-dots-vertical-rounded');
        
        console.log(`Notification ${index + 1}:`, {
            id: id,
            title: title,
            hasMenuButton: hasMenuButton,
            hasDotsIcon: hasDotsIcon,
            element: notif
        });
    });
    
    // Tester le premier bouton menu
    const firstMenuButton = document.querySelector('.notification-item .btn.btn-light.btn-sm');
    if (firstMenuButton) {
        console.log('Premier bouton menu trouvé:', firstMenuButton);
        const notification = firstMenuButton.closest('.notification-item');
        console.log('Notification parent:', notification);
        console.log('ID de la notification:', notification?.dataset?.id);
    } else {
        console.warn('Aucun bouton menu trouvé !');
    }
};

// **NOUVEAU** : Fonction pour tester la suppression manuellement
window.testDeleteNotification = function(notificationId) {
    console.log('🧪 Test suppression manuelle pour ID:', notificationId);
    
    const notificationElement = document.querySelector(`[data-id="${notificationId}"]`);
    if (notificationElement) {
        console.log('✅ Element trouvé:', notificationElement);
        deleteSingleNotification(notificationId, notificationElement);
    } else {
        console.error('❌ Element non trouvé pour ID:', notificationId);
        console.log('📋 IDs disponibles:', 
            Array.from(document.querySelectorAll('.notification-item')).map(el => el.dataset.id)
        );
    }
};

// **SUPER SIMPLE** : Fonction pour tester en cliquant sur le premier bouton menu
window.testFirstNotificationMenu = function() {
    console.log('🧪 === TEST PREMIER BOUTON MENU ===');
    
    const firstButton = document.querySelector('.notification-item .btn.btn-light.btn-sm');
    if (firstButton) {
        console.log('✅ Premier bouton trouvé:', firstButton);
        
        // Vérifier qu'il a l'icône
        const hasIcon = firstButton.querySelector('.bx-dots-vertical-rounded');
        if (hasIcon) {
            console.log('✅ Icône trouvée, simulation du clic...');
            
            // Créer un événement de clic
            const clickEvent = new MouseEvent('click', {
                bubbles: true,
                cancelable: true,
                view: window
            });
            
            firstButton.dispatchEvent(clickEvent);
            console.log('✅ Clic simulé sur le premier bouton');
        } else {
            console.error('❌ Pas d\'icône dots sur ce bouton');
        }
    } else {
        console.error('❌ Aucun bouton menu trouvé');
    }
};

// **DIAGNOSTIC COMPLET** : Fonction pour diagnostiquer l'état actuel
window.diagNotifications = function() {
    console.log('🔍 === DIAGNOSTIC COMPLET ===');
    
    const notifications = document.querySelectorAll('.notification-item');
    const buttons = document.querySelectorAll('.notification-item .btn.btn-light.btn-sm');
    const checkboxes = document.querySelectorAll('.notification-item .form-check-input');
    const initializedButtons = document.querySelectorAll('[data-menu-initialized="true"]');
    const initializedCheckboxes = document.querySelectorAll('[data-checkbox-initialized="true"]');
    const menus = document.querySelectorAll('.notification-context-menu');
    const bulkContainer = document.getElementById('bulkActionsContainer');
    
    console.log('📊 Notifications trouvées:', notifications.length);
    console.log('📊 Boutons menu trouvés:', buttons.length);
    console.log('📊 Checkboxes trouvées:', checkboxes.length);
    console.log('📊 Boutons initialisés:', initializedButtons.length);
    console.log('📊 Checkboxes initialisées:', initializedCheckboxes.length);
    console.log('📊 Menus contextuels ouverts:', menus.length);
    console.log('📊 Container actions bulk:', !!bulkContainer);
    console.log('📊 Notifications sélectionnées:', selectedNotifications.size);
    
    notifications.forEach((notif, index) => {
        const id = notif.dataset.id;
        const title = notif.querySelector('.title')?.textContent?.trim();
        const button = notif.querySelector('.btn.btn-light.btn-sm');
        const checkbox = notif.querySelector('.form-check-input');
        const hasIcon = button?.querySelector('.bx-dots-vertical-rounded');
        const isButtonInitialized = button?.getAttribute('data-menu-initialized') === 'true';
        const isCheckboxInitialized = checkbox?.getAttribute('data-checkbox-initialized') === 'true';
        const isSelected = selectedNotifications.has(id);
        
        console.log(`📋 Notification ${index + 1}:`, {
            id: id,
            title: title,
            hasButton: !!button,
            hasCheckbox: !!checkbox,
            hasIcon: !!hasIcon,
            isButtonInitialized: isButtonInitialized,
            isCheckboxInitialized: isCheckboxInitialized,
            isSelected: isSelected,
            checkboxChecked: checkbox?.checked
        });
    });
    
    console.log('🔍 === FIN DIAGNOSTIC ===');
};

// **NOUVEAU** : Fonction pour tester la sélection multiple
window.testBulkSelection = function() {
    console.log('🧪 === TEST SÉLECTION MULTIPLE ===');
    
    // Sélectionner les 2 premières notifications
    const checkboxes = document.querySelectorAll('.notification-item .form-check-input');
    if (checkboxes.length >= 2) {
        console.log('✅ Au moins 2 checkboxes trouvées, test de sélection...');
        
        // Cocher les 2 premières
        checkboxes[0].checked = true;
        checkboxes[0].dispatchEvent(new Event('change'));
        
        checkboxes[1].checked = true;
        checkboxes[1].dispatchEvent(new Event('change'));
        
        console.log('✅ 2 notifications sélectionnées');
        console.log('📊 Container d\'actions bulk visible:', 
            document.getElementById('bulkActionsContainer')?.style?.display !== 'none');
        
        // Attendre un peu puis tester la suppression
        setTimeout(() => {
            const deleteBtn = document.getElementById('deleteBtn');
            if (deleteBtn) {
                console.log('🗑️ Test du bouton supprimer...');
                deleteBtn.click();
            } else {
                console.error('❌ Bouton supprimer non trouvé');
            }
        }, 1000);
        
    } else {
        console.error('❌ Pas assez de checkboxes pour le test (minimum 2 requis)');
    }
};

// Fonction pour initialiser les menus des notifications individuelles
function initializeNotificationMenus() {
    console.log('🔧 === INITIALISATION MENUS CONTEXTUELS (CORRIGÉ) ===');
    
    // **ROBUSTE** : Supprimer tous les anciens event listeners
    document.removeEventListener('click', handleNotificationMenuClick);
    
    // **DÉLAI** : Attendre un peu avant d'ajouter le nouvel event listener
    // Cela évite les conflits avec les événements en cours
    setTimeout(() => {
        document.addEventListener('click', handleNotificationMenuClick);
        console.log('✅ Event listener global ajouté avec délai');
    }, 100);
    
    console.log('✅ Initialisation menus contextuels terminée');
}

function handleNotificationMenuClick(event) {
    // **CRITIQUE** : Vérifier d'abord si c'est un clic sur un bouton menu
    const menuButton = event.target.closest('.notification-item .btn.btn-light.btn-sm');
    
    if (menuButton) {
        // **IMPORTANT** : Empêcher la propagation immédiatement
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        
        const notificationItem = menuButton.closest('.notification-item');
        const notificationId = notificationItem?.dataset?.id;
        
        console.log('🔧 === CLIC BOUTON MENU (CORRIGÉ) ===');
        console.log('🔧 ID notification:', notificationId);
        
        if (!notificationId) {
            console.error('❌ ID de notification introuvable');
            return;
        }
        
        // **FERMER** tous les autres menus d'abord
        hideAllContextMenus();
        
        // **DÉLAI** : Attendre un peu avant d'ouvrir le nouveau menu
        // Cela évite le conflit avec la fermeture
        setTimeout(() => {
            showNotificationContextMenu(event, notificationId, notificationItem);
        }, 50);
        
        return;
    }
    
    // **FERMETURE** : Si clic ailleurs, fermer tous les menus
    const isMenuClick = event.target.closest('.notification-context-menu');
    if (!isMenuClick) {
        hideAllContextMenus();
    }
}

// Fonction pour afficher le menu contextuel d'une notification
function showNotificationContextMenu(event, notificationId, notificationItem) {
    console.log('📱 === AFFICHAGE MENU CONTEXTUEL (CORRIGÉ) ===');
    console.log('📱 Notification ID:', notificationId);
    
    // **SÉCURITÉ** : Fermer tous les menus existants d'abord
    hideAllContextMenus();
    
    const isUnread = notificationItem.classList.contains('unread');
    console.log('📱 Notification non lue:', isUnread);
    
    // **ROBUSTE** : Créer le menu contextuel avec gestion d'erreurs
    try {
        const menu = document.createElement('div');
        menu.className = 'notification-context-menu';
        menu.dataset.notificationId = notificationId; // Pour identification
        menu.style.cssText = `
            position: fixed;
            background: white;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            z-index: 9999;
            padding: 8px 0;
            min-width: 160px;
            font-size: 14px;
        `;
        
        // **AMÉLIORÉ** : Options du menu avec meilleure gestion
        const markOption = isUnread ? 
            '<div class="menu-item" data-action="mark-read" data-id="' + notificationId + '">📖 Marquer comme lue</div>' :
            '<div class="menu-item" data-action="mark-unread" data-id="' + notificationId + '">📭 Marquer comme non lue</div>';
        
        menu.innerHTML = `
            <style>
                .menu-item {
                    padding: 8px 16px;
                    cursor: pointer;
                    transition: background-color 0.15s;
                    color: #333;
                }
                .menu-item:hover {
                    background-color: #f8f9fa;
                }
                .menu-item.delete {
                    color: #dc3545;
                }
                .menu-item.delete:hover {
                    background-color: #f8d7da;
                }
            </style>
            ${markOption}
            <div class="menu-item delete" data-action="delete" data-id="${notificationId}">🗑️ Supprimer</div>
        `;
        
        // **POSITION** : Calculer la position du menu
        const buttonRect = event.target.closest('.btn').getBoundingClientRect();
        const menuWidth = 160;
        const menuHeight = 80; // Estimation
        
        let left = buttonRect.left;
        let top = buttonRect.bottom + 5;
        
        // **AJUSTEMENT** : Éviter que le menu sorte de l'écran
        if (left + menuWidth > window.innerWidth) {
            left = buttonRect.right - menuWidth;
        }
        if (top + menuHeight > window.innerHeight) {
            top = buttonRect.top - menuHeight - 5;
        }
        
        menu.style.left = left + 'px';
        menu.style.top = top + 'px';
        
        // **ÉVÉNEMENTS** : Ajouter les event listeners pour les actions
        menu.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const menuItem = e.target.closest('.menu-item');
            if (!menuItem) return;
            
            const action = menuItem.dataset.action;
            const itemId = menuItem.dataset.id;
            
            console.log('📱 Action menu:', action, 'pour ID:', itemId);
            
            // **FERMER** le menu immédiatement
            hideAllContextMenus();
            
            // **EXÉCUTER** l'action avec un délai pour éviter les conflits
            setTimeout(() => {
                switch(action) {
                    case 'mark-read':
                        markSingleNotificationAsRead(itemId, notificationItem);
                        break;
                    case 'mark-unread':
                        markSingleNotificationAsUnread(itemId, notificationItem);
                        break;
                    case 'delete':
                        deleteSingleNotification(itemId, notificationItem);
                        break;
                    default:
                        console.warn('Action inconnue:', action);
                }
            }, 100);
        });
        
        // **AJOUTER** le menu au DOM
        document.body.appendChild(menu);
        console.log('📱 Menu contextuel créé et ajouté');
        
        // **AUTO-FERMETURE** : Fermer le menu si clic ailleurs
        // Utiliser setTimeout pour éviter la fermeture immédiate
        setTimeout(() => {
            const closeHandler = function(e) {
                if (!menu.contains(e.target)) {
                    hideAllContextMenus();
                    document.removeEventListener('click', closeHandler);
                }
            };
            document.addEventListener('click', closeHandler);
        }, 200);
        
    } catch (error) {
        console.error('❌ Erreur lors de la création du menu contextuel:', error);
        hideAllContextMenus();
    }
}

// Fonction pour masquer tous les menus contextuels
function hideAllContextMenus() {
    const menus = document.querySelectorAll('.notification-context-menu');
    menus.forEach(menu => {
        menu.classList.add('closing');
        setTimeout(() => {
            if (menu.parentNode) {
                menu.remove();
            }
        }, 150); // Durée de l'animation
    });
}

// Fonction pour gérer les actions du menu contextuel
async function handleNotificationAction(action, notificationId, notificationItem) {
    console.log('⚡ === EXÉCUTION ACTION MENU ===');
    console.log('⚡ Action:', action);
    console.log('⚡ Notification ID:', notificationId);
    console.log('⚡ Notification Item:', notificationItem);
    
    try {
        switch (action) {
            case 'mark-read':
                console.log('⚡ Exécution: Marquer comme lue');
                await markSingleNotificationAsRead(notificationId, notificationItem);
                break;
            case 'mark-unread':
                console.log('⚡ Exécution: Marquer comme non lue');
                await markSingleNotificationAsUnread(notificationId, notificationItem);
                break;
            case 'delete':
                console.log('⚡ Exécution: Supprimer');
                await deleteSingleNotification(notificationId, notificationItem);
                break;
            default:
                console.warn('⚡ Action inconnue:', action);
        }
    } catch (error) {
        console.error('❌ Erreur lors de l\'exécution de l\'action:', error);
        showToast('Erreur lors de l\'exécution de l\'action', 'error', 5000);
    }
}

// Fonction pour supprimer une notification individuelle
async function deleteSingleNotification(notificationId, notificationItem) {
    console.log('🗑️ === DÉBUT SUPPRESSION INDIVIDUELLE ===');
    console.log('🗑️ ID:', notificationId, 'Type:', typeof notificationId);
    console.log('🗑️ Element reçu:', notificationItem);
    console.log('🗑️ Element existe dans DOM:', document.contains(notificationItem));
    
    // Vérifier que l'élément existe bien
    if (!notificationItem || !document.contains(notificationItem)) {
        console.error('❌ Element notification non trouvé dans le DOM !');
        return;
    }
    
    // Demander confirmation avec une modal moderne
    const confirmed = await showConfirmationModal(
        'Supprimer la notification', 
        'Êtes-vous sûr de vouloir supprimer cette notification ?',
        'Cette action est irréversible.'
    );
    
    if (!confirmed) {
        console.log('🚫 Suppression annulée par l\'utilisateur');
        return;
    }
    
    try {
        // Vérifier une dernière fois que l'élément existe
        if (!document.contains(notificationItem)) {
            console.error('❌ Element notification a disparu avant la suppression !');
            return;
        }
        
        console.log('🗑️ Début de l\'animation optimiste...');
        
        // Sauvegarder l'état pour restauration possible
        const wasUnread = notificationItem.classList.contains('unread');
        const originalStyle = notificationItem.style.cssText;
        
        // **OPTIMISTIQUE** : Animation immédiate pour la réactivité
        notificationItem.style.transition = 'all 0.3s ease';
        notificationItem.style.opacity = '0.5';
        notificationItem.style.transform = 'translateX(-20px)';
        notificationItem.style.filter = 'grayscale(100%)';
        
        console.log('🗑️ Animation appliquée, envoi de la requête...');
        
        // Envoyer la requête de suppression
        const response = await fetch('/notifications/delete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notification_ids: [parseInt(notificationId)] })
        });
        
        const data = await response.json();
        console.log('🗑️ Réponse serveur pour suppression individuelle:', data);
        
        if (data.success) {
            console.log('✅ Suppression confirmée par le serveur');
            
            // **IMMEDIAT** : Supprimer l'élément du DOM
            if (notificationItem && notificationItem.parentNode) {
                console.log('🗑️ Suppression immédiate de l\'élément du DOM');
                notificationItem.remove();
                console.log('✅ Element supprimé du DOM');
            }
            
            // **APRÈS** : Mettre à jour les statistiques
            console.log('🔄 Mise à jour des statistiques');
            reloadHeaderNotifications();
            
            // Message de succès
            showToast('Notification supprimée avec succès', 'success', 3000);
            
            // Vérifier s'il n'y a plus de notifications
            const remainingNotifications = document.querySelectorAll('.notification-item');
            if (remainingNotifications.length === 0) {
                showNoNotificationsMessage();
            }
            
        } else {
            console.error('❌ Erreur serveur lors de la suppression:', data.error || 'Erreur inconnue');
            
            // **ROLLBACK** : Restaurer l'apparence originale
            notificationItem.style.cssText = originalStyle;
            
            showToast('Erreur lors de la suppression: ' + (data.error || 'Erreur inconnue'), 'error', 5000);
        }
        
    } catch (error) {
        console.error('❌ Erreur réseau lors de la suppression:', error);
        
        // **ROLLBACK** : Restaurer l'apparence originale
        if (notificationItem && document.contains(notificationItem)) {
            notificationItem.style.opacity = '1';
            notificationItem.style.transform = 'translateX(0)';
            notificationItem.style.filter = 'none';
        }
        
        showToast('Erreur de connexion lors de la suppression', 'error', 5000);
    }
    
    console.log('🗑️ === FIN SUPPRESSION INDIVIDUELLE ===');
}

// Fonction d'aide pour mettre à jour les statistiques après suppression
function updateStatsAfterDelete(wasUnread) {
    const statCards = document.querySelectorAll('.stat-card .stat-number');
    
    // Mettre à jour le total
    const currentTotal = parseInt(statCards[0]?.textContent || '0');
    const newTotal = Math.max(0, currentTotal - 1);
    if (statCards[0]) {
        statCards[0].textContent = newTotal;
    }
    
    // Mettre à jour les non lues si la notification supprimée était non lue
    if (wasUnread) {
        const currentUnread = parseInt(statCards[1]?.textContent || '0');
        const newUnread = Math.max(0, currentUnread - 1);
        if (statCards[1]) {
            statCards[1].textContent = newUnread;
            statCards[1].classList.remove('text-success', 'text-danger');
            if (newUnread > 0) {
                statCards[1].classList.add('text-danger');
            } else {
                statCards[1].classList.add('text-success');
            }
        }
        
        // Mettre à jour le dot
        const unreadDot = document.querySelector('.unread-dot');
        if (unreadDot) {
            unreadDot.style.display = newUnread > 0 ? 'block' : 'none';
        }
        
        // Mettre à jour le sous-titre
        const pageSubtitle = document.querySelector('.page-subtitle');
        if (pageSubtitle) {
            pageSubtitle.textContent = `${newUnread} non lues sur ${newTotal}`;
        }
    } else {
        // Juste mettre à jour le sous-titre avec le nouveau total
        const unread = parseInt(statCards[1]?.textContent || '0');
        const pageSubtitle = document.querySelector('.page-subtitle');
        if (pageSubtitle) {
            pageSubtitle.textContent = `${unread} non lues sur ${newTotal}`;
        }
    }
}

// Fonction pour afficher une modal de confirmation moderne
async function showConfirmationModal(title, message, warning = '') {
    return new Promise((resolve) => {
        // Créer la modal
        const modal = document.createElement('div');
        modal.className = 'confirmation-modal-overlay';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            animation: fadeIn 0.3s ease;
        `;
        
        const modalContent = document.createElement('div');
        modalContent.className = 'confirmation-modal';
        modalContent.style.cssText = `
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
            transform-origin: center;
        `;
        
        modalContent.innerHTML = `
            <div style="text-align: center;">
                <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; color: #dc2626; font-size: 1.5rem;">
                    <i class="bx bx-trash"></i>
                </div>
                <h3 style="margin: 0 0 1rem; color: #1f2937; font-size: 1.25rem; font-weight: 600;">${title}</h3>
                <p style="margin: 0 0 1rem; color: #6b7280; line-height: 1.5;">${message}</p>
                ${warning ? `<p style="margin: 0 0 1.5rem; color: #d97706; font-size: 0.9rem; font-weight: 500;"><i class="bx bx-warning"></i> ${warning}</p>` : ''}
                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <button class="btn-cancel" style="padding: 0.75rem 1.5rem; border: 1px solid #d1d5db; background: #f9fafb; color: #374151; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.2s;">
                        Annuler
                    </button>
                    <button class="btn-confirm" style="padding: 0.75rem 1.5rem; border: none; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: white; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.2s; box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);">
                        Supprimer
                    </button>
                </div>
            </div>
        `;
        
        modal.appendChild(modalContent);
        document.body.appendChild(modal);
        
        // Ajouter les animations CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes modalSlideIn {
                from { opacity: 0; transform: scale(0.9) translateY(-20px); }
                to { opacity: 1; transform: scale(1) translateY(0); }
            }
            .confirmation-modal .btn-cancel:hover {
                background: #f3f4f6 !important;
                border-color: #9ca3af !important;
            }
            .confirmation-modal .btn-confirm:hover {
                background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%) !important;
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4) !important;
            }
        `;
        document.head.appendChild(style);
        
        // Gérer les clics
        modalContent.querySelector('.btn-cancel').addEventListener('click', () => {
            modal.style.animation = 'fadeIn 0.2s ease reverse';
            setTimeout(() => {
                document.body.removeChild(modal);
                document.head.removeChild(style);
                resolve(false);
            }, 200);
        });
        
        modalContent.querySelector('.btn-confirm').addEventListener('click', () => {
            modal.style.animation = 'fadeIn 0.2s ease reverse';
            setTimeout(() => {
                document.body.removeChild(modal);
                document.head.removeChild(style);
                resolve(true);
            }, 200);
        });
        
        // Fermer avec échap
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                modal.style.animation = 'fadeIn 0.2s ease reverse';
                setTimeout(() => {
                    if (document.body.contains(modal)) {
                        document.body.removeChild(modal);
                        document.head.removeChild(style);
                    }
                    resolve(false);
                }, 200);
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);
        
        // Empêcher le scroll du body
        document.body.style.overflow = 'hidden';
        
        // Restaurer le scroll quand la modal se ferme
        setTimeout(() => {
            document.body.style.overflow = 'auto';
        }, 300);
    });
}

// Fonction pour marquer une notification individuelle comme lue
async function markSingleNotificationAsRead(notificationId, notificationItem) {
    try {
        // Mettre à jour visuellement IMMÉDIATEMENT pour une réactivité instantanée
        notificationItem.classList.remove('unread');
        const statusDot = notificationItem.querySelector('.status-dot');
        if (statusDot) {
            statusDot.remove();
        }
        
        // Afficher un indicateur de traitement
        const originalHTML = notificationItem.innerHTML;
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'processing-indicator';
        loadingIndicator.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Marquage en cours...';
        loadingIndicator.style.cssText = 'position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10; background: rgba(255,255,255,0.9); padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; color: #666;';
        notificationItem.style.position = 'relative';
        notificationItem.style.opacity = '0.7';
        notificationItem.appendChild(loadingIndicator);
        
        // Envoyer la requête au serveur
        const response = await fetch('/notifications/mark-read', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notification_ids: [notificationId] })
        });
        
        const data = await response.json();
        
        // Supprimer l'indicateur de chargement
        if (loadingIndicator && loadingIndicator.parentNode) {
            loadingIndicator.remove();
        }
        notificationItem.style.opacity = '1';
        
        if (data.success) {
            // Mise à jour réussie
            console.log('✅ Notification', notificationId, 'marquée comme lue');
            
            // **MODIFIÉ** : Un seul appel pour mettre à jour toutes les stats
            console.log('🔄 Mise à jour des stats après marquage individuel lu (API)');
            reloadHeaderNotifications(); // Cela met à jour header + page automatiquement
            
            // Afficher un toast de succès discret
            showToast('Notification marquée comme lue', 'success', 2000);
            
        } else {
            // Erreur - restaurer l'état précédent
            console.error('❌ Erreur lors du marquage:', data.error || 'Erreur inconnue');
            notificationItem.classList.add('unread');
            if (!notificationItem.querySelector('.status-dot')) {
                const newStatusDot = document.createElement('div');
                newStatusDot.className = 'status-dot';
                const titleElement = notificationItem.querySelector('.title');
                if (titleElement) {
                    titleElement.appendChild(newStatusDot);
                }
            }
            showToast('Erreur lors du marquage: ' + (data.error || 'Erreur inconnue'), 'error');
        }
        
    } catch (error) {
        console.error('❌ Erreur réseau lors du marquage:', error);
        
        // Restaurer l'état précédent en cas d'erreur réseau
        notificationItem.classList.add('unread');
        if (!notificationItem.querySelector('.status-dot')) {
            const newStatusDot = document.createElement('div');
            newStatusDot.className = 'status-dot';
            const titleElement = notificationItem.querySelector('.title');
            if (titleElement) {
                titleElement.appendChild(newStatusDot);
            }
        }
        
        // Supprimer l'indicateur de chargement
        const loadingIndicator = notificationItem.querySelector('.processing-indicator');
        if (loadingIndicator) {
            loadingIndicator.remove();
        }
        notificationItem.style.opacity = '1';
        
        showToast('Erreur de connexion lors du marquage', 'error');
    }
}

// Fonction pour marquer une notification individuelle comme non lue
async function markSingleNotificationAsUnread(notificationId, notificationItem) {
    try {
        // Mettre à jour visuellement IMMÉDIATEMENT
        notificationItem.classList.add('unread');
        
        // Ajouter le status dot s'il n'existe pas
        if (!notificationItem.querySelector('.status-dot')) {
            const statusDot = document.createElement('div');
            statusDot.className = 'status-dot';
            const titleElement = notificationItem.querySelector('.title');
            if (titleElement) {
                titleElement.appendChild(statusDot);
            }
        }
        
        // Afficher un indicateur de traitement
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'processing-indicator';
        loadingIndicator.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Marquage en cours...';
        loadingIndicator.style.cssText = 'position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10; background: rgba(255,255,255,0.9); padding: 5px 10px; border-radius: 4px; font-size: 0.8rem; color: #666;';
        notificationItem.style.position = 'relative';
        notificationItem.style.opacity = '0.7';
        notificationItem.appendChild(loadingIndicator);
        
        // Envoyer la requête au serveur
        const response = await fetch('/notifications/mark-unread', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ notification_ids: [notificationId] })
        });
        
        const data = await response.json();
        
        // Supprimer l'indicateur de chargement
        if (loadingIndicator && loadingIndicator.parentNode) {
            loadingIndicator.remove();
        }
        notificationItem.style.opacity = '1';
        
        if (data.success) {
            // Mise à jour réussie
            console.log('✅ Notification', notificationId, 'marquée comme non lue');
            
            // **MODIFIÉ** : Un seul appel pour mettre à jour toutes les stats
            console.log('🔄 Mise à jour des stats après marquage individuel non lu (API)');
            reloadHeaderNotifications(); // Cela met à jour header + page automatiquement
            
            // Afficher un toast de succès
            showToast('Notification marquée comme non lue', 'success', 2000);
            
        } else {
            // Erreur - restaurer l'état précédent
            console.error('❌ Erreur lors du marquage:', data.error || 'Erreur inconnue');
            notificationItem.classList.remove('unread');
            const statusDot = notificationItem.querySelector('.status-dot');
            if (statusDot) {
                statusDot.remove();
            }
            showToast('Erreur lors du marquage: ' + (data.error || 'Erreur inconnue'), 'error');
        }
        
    } catch (error) {
        console.error('❌ Erreur réseau lors du marquage:', error);
        
        // Restaurer l'état précédent
        notificationItem.classList.remove('unread');
        const statusDot = notificationItem.querySelector('.status-dot');
        if (statusDot) {
            statusDot.remove();
        }
        
        // Supprimer l'indicateur de chargement
        const loadingIndicator = notificationItem.querySelector('.processing-indicator');
        if (loadingIndicator) {
            loadingIndicator.remove();
        }
        notificationItem.style.opacity = '1';
        
        showToast('Erreur de connexion lors du marquage', 'error');
    }
}

// Fonctions d'aide pour les mises à jour statistiques locales
function updateStatsAfterMarkAsRead() {
    // Au lieu de calculer localement, on fait un appel API pour avoir les vraies données
    console.log('🔄 Mise à jour des stats après marquage comme lu - Appel API');
    reloadHeaderNotifications(); // Cela va automatiquement mettre à jour les stats via updatePageStatisticsFromAPI
}

function updateStatsAfterMarkAsUnread() {
    // Au lieu de calculer localement, on fait un appel API pour avoir les vraies données
    console.log('🔄 Mise à jour des stats après marquage comme non lu - Appel API');
    reloadHeaderNotifications(); // Cela va automatiquement mettre à jour les stats via updatePageStatisticsFromAPI
}

// Fonction pour formater le temps écoulé (utilisée dans le dropdown)
function formatTimeAgo(dateString) {
    try {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);
        
        if (diffInSeconds < 60) {
            return 'À l\'instant';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `${minutes} min${minutes > 1 ? '' : ''}`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `${hours}h`;
        } else if (diffInSeconds < 604800) {
            const days = Math.floor(diffInSeconds / 86400);
            return `${days}j`;
        } else {
            return date.toLocaleDateString('fr-FR', { 
                day: '2-digit', 
                month: '2-digit', 
                year: 'numeric' 
            });
        }
    } catch (error) {
        console.error('Erreur lors du formatage de la date:', error);
        return 'Date invalide';
    }
}

// **NOUVELLE FONCTION** : Gestionnaire pour les changements de checkbox
function handleCheckboxChange(e) {
    const checkbox = e.target;
    const notificationItem = checkbox.closest('.notification-item');
    const notificationId = notificationItem.dataset.id;
    
    console.log('📋 Changement checkbox pour notification:', notificationId, 'Cochée:', checkbox.checked);
    
    if (checkbox.checked) {
        selectedNotifications.add(notificationId);
        console.log('➕ Notification ajoutée à la sélection');
    } else {
        selectedNotifications.delete(notificationId);
        console.log('➖ Notification retirée de la sélection');
    }
    
    console.log('📊 Total sélectionnées:', selectedNotifications.size);
    updateBulkActionsVisibility();
}

// **NOUVELLE FONCTION** : Gestionnaire pour le clic de suppression
function handleDeleteClick(e) {
    e.preventDefault();
    
    if (selectedNotifications.size === 0) {
        showToast('Aucune notification sélectionnée', 'warning', 3000);
        return;
    }
    
    console.log('🗑️ Clic sur supprimer, notifications sélectionnées:', Array.from(selectedNotifications));
    deleteSelected();
}