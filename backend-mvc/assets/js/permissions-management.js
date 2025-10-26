/**
 * Gestion des Permissions - Interface JavaScript
 */

// Variables globales
let currentRoles = [];
let currentUsers = [];
let currentPermissions = [];
let editingRoleId = null;
let auditLogs = []; // Nouveau: stockage des logs d'audit

// Initialisation
document.addEventListener('DOMContentLoaded', async function() {
    console.log('🔐 Initialisation de l\'interface de gestion des permissions');
    
    initializeTabs();
    await loadInitialData(); // Attendre que les données soient chargées
    initializeAuditSystem();
    
    // Charger la section Rôles par défaut APRÈS le chargement des données
    showSection('roles');
});

/**
 * Sauvegarder les données dans localStorage
 */
function saveDataToStorage() {
    try {
        localStorage.setItem('permissions_roles', JSON.stringify(currentRoles));
        localStorage.setItem('permissions_users', JSON.stringify(currentUsers));
        localStorage.setItem('permissions_permissions', JSON.stringify(currentPermissions));
        console.log('💾 Données sauvegardées dans localStorage');
    } catch (error) {
        console.warn('⚠️ Impossible de sauvegarder les données:', error);
    }
}

/**
 * Charger les données depuis localStorage
 */
function loadDataFromStorage() {
    try {
        const savedRoles = localStorage.getItem('permissions_roles');
        const savedUsers = localStorage.getItem('permissions_users');
        const savedPermissions = localStorage.getItem('permissions_permissions');
        
        if (savedRoles) {
            currentRoles = JSON.parse(savedRoles);
            console.log('✅ Rôles restaurés depuis localStorage:', currentRoles.length);
        }
        
        if (savedUsers) {
            currentUsers = JSON.parse(savedUsers);
            console.log('✅ Utilisateurs restaurés depuis localStorage:', currentUsers.length);
        }
        
        if (savedPermissions) {
            currentPermissions = JSON.parse(savedPermissions);
            console.log('✅ Permissions restaurées depuis localStorage:', currentPermissions.length);
        }
        
        return {
            hasRoles: !!savedRoles,
            hasUsers: !!savedUsers,
            hasPermissions: !!savedPermissions
        };
    } catch (error) {
        console.warn('⚠️ Impossible de charger les données sauvegardées:', error);
        return { hasRoles: false, hasUsers: false, hasPermissions: false };
    }
}

/**
 * Initialisation du système d'audit
 */
function initializeAuditSystem() {
    console.log('📜 Initialisation du système d\'audit dynamique');
    
    // Charger les logs existants depuis le localStorage si disponibles
    const savedLogs = localStorage.getItem('permissions_audit_logs');
    if (savedLogs) {
        try {
            auditLogs = JSON.parse(savedLogs);
        } catch (error) {
            console.warn('Impossible de charger les logs d\'audit sauvegardés:', error);
            auditLogs = [];
        }
    }
    
    // Ajouter un log initial si c'est la première fois
    if (auditLogs.length === 0) {
        addAuditLog('Connexion', 'Session utilisateur', 'Interface de gestion des permissions', 'Première connexion à l\'interface');
    }
}

/**
 * Ajouter un nouveau log d'audit
 */
function addAuditLog(action, user, target, details) {
    const timestamp = new Date().toLocaleString('fr-FR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    
    const logEntry = {
        id: Date.now() + Math.random(), // ID unique
        action: action,
        user: user || 'Utilisateur actuel',
        target: target,
        timestamp: timestamp,
        details: details,
        level: getLogLevel(action) // Niveau d'importance
    };
    
    // Ajouter au début du tableau (plus récent en premier)
    auditLogs.unshift(logEntry);
    
    // Limiter à 100 logs maximum pour éviter la surcharge
    if (auditLogs.length > 100) {
        auditLogs = auditLogs.slice(0, 100);
    }
    
    // Sauvegarder dans localStorage
    try {
        localStorage.setItem('permissions_audit_logs', JSON.stringify(auditLogs));
    } catch (error) {
        console.warn('Impossible de sauvegarder les logs d\'audit:', error);
    }
    
    console.log(`📝 Nouveau log d'audit: ${action} - ${target}`);
    
    // Mettre à jour l'affichage si l'onglet Audit est actif
    const auditSection = document.getElementById('audit-section');
    if (auditSection && auditSection.classList.contains('active')) {
        loadAuditLogs();
    }
    
    // Afficher une notification discrète
    showAuditNotification(`Action enregistrée: ${action}`);
}

/**
 * Déterminer le niveau d'importance d'un log
 */
function getLogLevel(action) {
    const criticalActions = ['Suppression de rôle', 'Suppression d\'utilisateur', 'Réinitialisation'];
    const warningActions = ['Modification de rôle', 'Retrait de rôle', 'Modification de permission'];
    
    if (criticalActions.some(a => action.includes(a))) return 'critical';
    if (warningActions.some(a => action.includes(a))) return 'warning';
    return 'info';
}

/**
 * Afficher une notification d'audit discrète
 */
function showAuditNotification(message) {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        left: 20px;
        background: #1e293b;
        color: white;
        padding: 0.75rem 1rem;
        border-radius: 8px;
        font-size: 0.875rem;
        z-index: 9999;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.3s ease;
        max-width: 300px;
    `;
    
    notification.innerHTML = `<i class='bx bx-history'></i> ${message}`;
    document.body.appendChild(notification);
    
    // Animation d'apparition
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateY(0)';
    }, 100);
    
    // Suppression automatique
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(20px)';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * Initialisation des onglets
 */
function initializeTabs() {
    const tabs = document.querySelectorAll('.permissions-tab');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Désactiver tous les onglets
            tabs.forEach(t => t.classList.remove('active'));
            
            // Activer l'onglet cliqué
            this.classList.add('active');
            
            // Afficher la section correspondante
            showSection(targetTab);
        });
    });
}

/**
 * Afficher une section spécifique
 */
function showSection(sectionName) {
    console.log(`📋 Affichage de la section: ${sectionName}`);
    
    // Masquer toutes les sections
    const sections = document.querySelectorAll('.permissions-section');
    sections.forEach(section => section.classList.remove('active'));
    
    // Afficher la section demandée
    const targetSection = document.getElementById(`${sectionName}-section`);
    if (targetSection) {
        targetSection.classList.add('active');
        
        // Vérifier que les données sont disponibles avant de charger la section
        const ensureDataLoaded = async () => {
            try {
                // Charger les données spécifiques à la section
                switch(sectionName) {
                    case 'roles':
                        if (currentRoles.length === 0) {
                            console.log('🔄 Rechargement des rôles...');
                            currentRoles = await loadRolesData();
                        }
                        loadRoles();
                        break;
                    case 'users':
                        if (currentUsers.length === 0) {
                            console.log('🔄 Rechargement des utilisateurs...');
                            currentUsers = await loadUsersData();
                        }
                        loadUsers();
                        break;
                    case 'permissions':
                        if (currentPermissions.length === 0) {
                            console.log('🔄 Rechargement des permissions...');
                            currentPermissions = await loadPermissionsData();
                        }
                        loadPermissionsMatrix();
                        break;
                    case 'audit':
                        loadAuditLogs();
                        break;
                }
            } catch (error) {
                console.error(`❌ Erreur lors du chargement de la section ${sectionName}:`, error);
                showNotification(`Erreur lors du chargement de la section ${sectionName}`, 'error');
            }
        };
        
        // Exécuter immédiatement
        ensureDataLoaded();
    }
}

/**
 * Charger les données depuis la base de données
 */
async function loadDataFromDatabase() {
    try {
        const response = await fetch('/test_permissions_api.php?action=matrix');
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (data.roles && data.permissions && data.matrix) {
            // Convertir les données de la BDD au format attendu
            currentRoles = data.roles.map(role => ({
                id: role.id,
                name: role.name,
                displayName: role.display_name,
                description: role.description,
                permissions: data.matrix[role.id] || [],
                userCount: 0 // Sera calculé plus tard
            }));
            
            currentPermissions = data.permissions.map(permission => ({
                id: permission.id,
                name: permission.name,
                displayName: permission.display_name,
                description: permission.description,
                module: permission.module,
                action: permission.action
            }));
            
            // Sauvegarder dans localStorage pour le cache
            saveDataToStorage();
            
            console.log(`✅ Chargé depuis la BDD: ${currentRoles.length} rôles, ${currentPermissions.length} permissions`);
        } else {
            throw new Error('Format de données invalide reçu de l\'API');
        }
        
    } catch (error) {
        console.error('Erreur lors du chargement depuis la BDD:', error);
        throw error;
    }
}

/**
 * Chargement initial des données
 */
async function loadInitialData() {
    console.log('📊 Chargement des données initiales...');
    
    // FORCER LE RECHARGEMENT DEPUIS L'API DE TEST
    console.log('🔄 FORÇAGE du chargement depuis l\'API de test...');
    
    try {
        // TOUJOURS charger depuis l'API de test
        await loadDataFromDatabase();
        console.log('✅ Données chargées depuis l\'API:', { 
            roles: currentRoles.length, 
            users: currentUsers.length, 
            permissions: currentPermissions.length 
        });
        
        // Vider le localStorage pour éviter les conflits
        localStorage.removeItem('permissions_roles');
        localStorage.removeItem('permissions_users');
        localStorage.removeItem('permissions_permissions');
        console.log('🧹 Cache localStorage vidé');
        
    } catch (error) {
        console.error('❌ Erreur lors du chargement depuis l\'API:', error);
        showNotification('Erreur lors du chargement des données depuis l\'API', 'error');
        
        // En dernier recours, charger les données par défaut
        try {
            currentRoles = await loadRolesData();
            currentUsers = await loadUsersData();
            currentPermissions = await loadPermissionsData();
            console.log('🔄 Données de secours chargées (défaut)');
        } catch (fallbackError) {
            console.error('❌ Erreur critique lors du chargement des données de secours:', fallbackError);
        }
    }
}

/**
 * Chargement des rôles (données simulées pour l'instant)
 */
async function loadRolesData() {
    // Simuler un appel API - à remplacer par fetch('/api/roles')
    return [
        {
            id: 1,
            name: 'super_admin',
            displayName: 'Super Administrateur',
            description: 'Accès complet à toutes les fonctionnalités du système',
            permissions: ['system.admin', 'users.manage', 'roles.manage', 'permissions.manage'],
            userCount: 1
        },
        {
            id: 2,
            name: 'admin',
            displayName: 'Administrateur',
            description: 'Gestion complète du système et des utilisateurs',
            permissions: ['system.access', 'users.manage', 'interventions.manage', 'vehicles.manage'],
            userCount: 3
        },
        {
            id: 3,
            name: 'manager',
            displayName: 'Chef d\'équipe',
            description: 'Gestion des équipes et supervision des interventions',
            permissions: ['interventions.manage', 'teams.manage', 'vehicles.read', 'reports.read'],
            userCount: 5
        },
        {
            id: 4,
            name: 'technician',
            displayName: 'Technicien',
            description: 'Exécution des interventions sur le terrain',
            permissions: ['interventions.read', 'interventions.update', 'vehicles.read'],
            userCount: 12
        }
    ];
}

/**
 * Chargement des utilisateurs (données simulées)
 */
async function loadUsersData() {
    return [
        {
            id: 1,
            name: 'Administrateur Système',
            email: 'admin@terraintrack.com',
            roles: ['Super Administrateur'],
            lastLogin: '2025-01-21 10:30:00',
            status: 'active'
        },
        {
            id: 2,
            name: 'Jean Dupont',
            email: 'jean.dupont@terraintrack.com',
            roles: ['Chef d\'équipe'],
            lastLogin: '2025-01-21 09:15:00',
            status: 'active'
        },
        {
            id: 3,
            name: 'Marie Martin',
            email: 'marie.martin@terraintrack.com',
            roles: ['Technicien'],
            lastLogin: '2025-01-20 16:45:00',
            status: 'active'
        }
    ];
}

/**
 * Chargement des permissions (données simulées)
 */
async function loadPermissionsData() {
    return [
        { module: 'system', action: 'access', name: 'system.access', displayName: 'Accès système' },
        { module: 'system', action: 'admin', name: 'system.admin', displayName: 'Administration système' },
        { module: 'users', action: 'read', name: 'users.read', displayName: 'Lire les utilisateurs' },
        { module: 'users', action: 'create', name: 'users.create', displayName: 'Créer des utilisateurs' },
        { module: 'users', action: 'update', name: 'users.update', displayName: 'Modifier les utilisateurs' },
        { module: 'users', action: 'delete', name: 'users.delete', displayName: 'Supprimer les utilisateurs' },
        { module: 'users', action: 'manage', name: 'users.manage', displayName: 'Gérer les utilisateurs' },
        { module: 'roles', action: 'read', name: 'roles.read', displayName: 'Lire les rôles' },
        { module: 'roles', action: 'create', name: 'roles.create', displayName: 'Créer des rôles' },
        { module: 'roles', action: 'update', name: 'roles.update', displayName: 'Modifier les rôles' },
        { module: 'roles', action: 'delete', name: 'roles.delete', displayName: 'Supprimer les rôles' },
        { module: 'roles', action: 'manage', name: 'roles.manage', displayName: 'Gérer les rôles' },
        { module: 'interventions', action: 'read', name: 'interventions.read', displayName: 'Lire les interventions' },
        { module: 'interventions', action: 'create', name: 'interventions.create', displayName: 'Créer des interventions' },
        { module: 'interventions', action: 'update', name: 'interventions.update', displayName: 'Modifier les interventions' },
        { module: 'interventions', action: 'delete', name: 'interventions.delete', displayName: 'Supprimer les interventions' },
        { module: 'interventions', action: 'manage', name: 'interventions.manage', displayName: 'Gérer les interventions' },
        { module: 'vehicles', action: 'read', name: 'vehicles.read', displayName: 'Lire les véhicules' },
        { module: 'vehicles', action: 'create', name: 'vehicles.create', displayName: 'Créer des véhicules' },
        { module: 'vehicles', action: 'update', name: 'vehicles.update', displayName: 'Modifier les véhicules' },
        { module: 'vehicles', action: 'delete', name: 'vehicles.delete', displayName: 'Supprimer les véhicules' },
        { module: 'vehicles', action: 'manage', name: 'vehicles.manage', displayName: 'Gérer les véhicules' },
        { module: 'teams', action: 'read', name: 'teams.read', displayName: 'Lire les équipes' },
        { module: 'teams', action: 'create', name: 'teams.create', displayName: 'Créer des équipes' },
        { module: 'teams', action: 'update', name: 'teams.update', displayName: 'Modifier les équipes' },
        { module: 'teams', action: 'delete', name: 'teams.delete', displayName: 'Supprimer les équipes' },
        { module: 'teams', action: 'manage', name: 'teams.manage', displayName: 'Gérer les équipes' },
        { module: 'reports', action: 'read', name: 'reports.read', displayName: 'Consulter les rapports' },
        { module: 'reports', action: 'create', name: 'reports.create', displayName: 'Créer des rapports' }
    ];
}

/**
 * Chargement et affichage des rôles
 */
async function loadRoles() {
    console.log('👥 Chargement des rôles...');
    
    const rolesGrid = document.getElementById('roles-grid');
    
    if (currentRoles.length === 0) {
        rolesGrid.innerHTML = `
            <div style="text-align: center; padding: 3rem; color: #64748b;">
                <i class='bx bx-group' style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <p>Aucun rôle défini pour le moment.</p>
                <button class="btn-primary" onclick="openCreateRoleModal()">
                    <i class='bx bx-plus'></i> Créer le premier rôle
                </button>
            </div>
        `;
        return;
    }
    
    const rolesHTML = currentRoles.map(role => {
        const permissionBadges = role.permissions.slice(0, 3).map(permission => 
            `<span class="permission-badge">${permission}</span>`
        ).join('');
        
        const morePermissions = role.permissions.length > 3 ? 
            `<span class="permission-badge">+${role.permissions.length - 3} autres</span>` : '';
        
        return `
            <div class="role-card" data-role-id="${role.id}">
                <div class="role-header">
                    <h3 class="role-name">${role.displayName}</h3>
                    <div class="role-actions">
                        <button class="role-btn edit" onclick="editRole(${role.id})" title="Modifier">
                            <i class='bx bx-edit'></i>
                        </button>
                        <button class="role-btn delete" onclick="deleteRole(${role.id})" title="Supprimer">
                            <i class='bx bx-trash'></i>
                        </button>
                    </div>
                </div>
                <p class="role-description">${role.description}</p>
                <div style="margin-bottom: 1rem;">
                    <small style="color: #64748b;">
                        <i class='bx bx-user'></i> ${role.userCount} utilisateur(s)
                    </small>
                </div>
                <div class="role-permissions">
                    ${permissionBadges}
                    ${morePermissions}
                </div>
            </div>
        `;
    }).join('');
    
    rolesGrid.innerHTML = rolesHTML;
}

/**
 * Chargement et affichage des utilisateurs
 */
async function loadUsers() {
    console.log('👤 Chargement des utilisateurs...');
    
    const usersTableBody = document.querySelector('#users-table tbody');
    
    if (currentUsers.length === 0) {
        usersTableBody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align: center; padding: 3rem; color: #64748b;">
                    <i class='bx bx-user' style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <br>Aucun utilisateur trouvé.
                </td>
            </tr>
        `;
        return;
    }
    
    const usersHTML = currentUsers.map(user => {
        const userInitials = user.name.split(' ').map(n => n[0]).join('').toUpperCase();
        const rolesBadges = user.roles.map(role => {
            const roleClass = role.toLowerCase().replace(/\s+/g, '');
            return `<span class="role-badge ${roleClass}">${role}</span>`;
        }).join('');
        
        const lastLoginFormatted = new Date(user.lastLogin).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        return `
            <tr data-user-id="${user.id}">
                <td>
                    <div class="user-info">
                        <div class="user-avatar">${userInitials}</div>
                        <div class="user-details">
                            <h4>${user.name}</h4>
                            <p>ID: ${user.id}</p>
                        </div>
                    </div>
                </td>
                <td>${user.email}</td>
                <td>
                    <div class="user-roles">
                        ${rolesBadges}
                    </div>
                </td>
                <td>${lastLoginFormatted}</td>
                <td>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="role-btn edit" onclick="editUserRoles(${user.id})" title="Modifier les rôles">
                            <i class='bx bx-edit'></i>
                        </button>
                        <button class="role-btn delete" onclick="removeUserRole(${user.id})" title="Retirer un rôle">
                            <i class='bx bx-user-minus'></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
    
    usersTableBody.innerHTML = usersHTML;
}

/**
 * Chargement et affichage de la matrice des permissions
 */
async function loadPermissionsMatrix() {
    console.log('🔑 Chargement de la matrice des permissions...');
    
    const matrixTable = document.getElementById('permissions-matrix');
    
    // Grouper les permissions par module
    const modules = [...new Set(currentPermissions.map(p => p.module))];
    const actions = [...new Set(currentPermissions.map(p => p.action))];
    
    // Construire l'en-tête du tableau
    let headerHTML = '<thead><tr><th>Module / Rôle</th>';
    currentRoles.forEach(role => {
        headerHTML += `<th class="module-header">${role.displayName}</th>`;
    });
    headerHTML += '</tr></thead>';
    
    // Construire le corps du tableau
    let bodyHTML = '<tbody>';
    modules.forEach(module => {
        const modulePermissions = currentPermissions.filter(p => p.module === module);
        
        modulePermissions.forEach((permission, index) => {
            bodyHTML += '<tr>';
            
            // Nom de la permission
            bodyHTML += `<td style="text-align: left;"><strong>${permission.displayName}</strong><br><small style="color: #64748b;">${permission.name}</small></td>`;
            
            // Cases à cocher pour chaque rôle
            currentRoles.forEach(role => {
                const hasPermission = role.permissions.includes(permission.name);
                bodyHTML += `
                    <td>
                        <input type="checkbox" 
                               class="permission-checkbox" 
                               data-role-id="${role.id}" 
                               data-permission="${permission.name}"
                               ${hasPermission ? 'checked' : ''}
                               onchange="togglePermission(${role.id}, '${permission.name}', this.checked)">
                    </td>
                `;
            });
            
            bodyHTML += '</tr>';
        });
    });
    bodyHTML += '</tbody>';
    
    matrixTable.innerHTML = headerHTML + bodyHTML;
}

/**
 * Chargement et affichage des logs d'audit (version dynamique)
 */
async function loadAuditLogs() {
    console.log('📜 Chargement des logs d\'audit dynamiques...');
    
    const auditContainer = document.getElementById('audit-logs');
    
    if (auditLogs.length === 0) {
        auditContainer.innerHTML = `
            <div style="background: white; border-radius: 12px; padding: 3rem; text-align: center;">
                <i class='bx bx-history' style="font-size: 3rem; color: #64748b; margin-bottom: 1rem;"></i>
                <h3 style="color: #1e293b; margin-bottom: 0.5rem;">Aucun historique disponible</h3>
                <p style="color: #64748b; margin: 0;">Les actions que vous effectuez seront enregistrées ici automatiquement.</p>
            </div>
        `;
        return;
    }
    
    // Grouper les logs par date
    const logsByDate = {};
    auditLogs.forEach(log => {
        const date = log.timestamp.split(' ')[0]; // Extraire la date
        if (!logsByDate[date]) {
            logsByDate[date] = [];
        }
        logsByDate[date].push(log);
    });
    
    let auditHTML = `
        <div style="background: white; border-radius: 12px; padding: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="margin: 0; color: #1e293b;">
                    <i class='bx bx-history'></i> Historique des modifications
                </h3>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <span style="font-size: 0.875rem; color: #64748b;">
                        ${auditLogs.length} action${auditLogs.length > 1 ? 's' : ''} enregistrée${auditLogs.length > 1 ? 's' : ''}
                    </span>
                    <button onclick="clearAuditLogs()" style="background: #ef4444; color: white; border: none; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; cursor: pointer;">
                        <i class='bx bx-trash'></i> Vider l'historique
                    </button>
                </div>
            </div>
    `;
    
    // Afficher les logs groupés par date
    Object.keys(logsByDate).sort().reverse().forEach(date => {
        auditHTML += `
            <div style="margin-bottom: 2rem;">
                <h4 style="color: #374151; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #e5e7eb;">
                    <i class='bx bx-calendar'></i> ${formatDate(date)}
                </h4>
        `;
        
        logsByDate[date].forEach(log => {
            const levelColors = {
                critical: '#ef4444',
                warning: '#f59e0b',
                info: '#3b82f6'
            };
            
            const levelIcons = {
                critical: 'bx-error-circle',
                warning: 'bx-warning',
                info: 'bx-info-circle'
            };
            
            auditHTML += `
                <div style="border-left: 3px solid ${levelColors[log.level]}; padding: 1rem; margin-bottom: 1rem; background: #f8fafc; border-radius: 8px; transition: all 0.2s ease;" 
                     onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='#f8fafc'">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <i class='bx ${levelIcons[log.level]}' style="color: ${levelColors[log.level]};"></i>
                            <strong style="color: #1e293b;">${log.action}</strong>
                        </div>
                        <small style="color: #64748b; white-space: nowrap;">${log.timestamp.split(' ')[1]}</small>
                    </div>
                    <p style="margin: 0.5rem 0; color: #374151; font-size: 0.9rem;">
                        <strong>Utilisateur:</strong> ${log.user}<br>
                        <strong>Cible:</strong> ${log.target}
                    </p>
                    <p style="margin: 0; color: #64748b; font-size: 0.875rem;">
                        ${log.details}
                    </p>
                </div>
            `;
        });
        
        auditHTML += '</div>';
    });
    
    auditHTML += '</div>';
    auditContainer.innerHTML = auditHTML;
}

/**
 * Formater une date pour l'affichage
 */
function formatDate(dateStr) {
    const [day, month, year] = dateStr.split('/');
    const date = new Date(year, month - 1, day);
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    
    if (date.toDateString() === today.toDateString()) {
        return "Aujourd'hui";
    } else if (date.toDateString() === yesterday.toDateString()) {
        return "Hier";
    } else {
        return date.toLocaleDateString('fr-FR', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
    }
}

/**
 * Vider l'historique d'audit (version professionnelle avec modal)
 */
function clearAuditLogs() {
    // Ouvrir la modal de confirmation professionnelle
    document.getElementById('clear-audit-modal').classList.add('active');
}

/**
 * Fermer la modal de confirmation
 */
function closeClearAuditModal() {
    document.getElementById('clear-audit-modal').classList.remove('active');
}

/**
 * Confirmer la suppression de l'historique d'audit
 */
function confirmClearAuditLogs() {
    console.log('🗑️ Confirmation de suppression de l\'historique d\'audit');
    
    // Fermer la modal
    closeClearAuditModal();
    
    // Effectuer la suppression
    auditLogs = [];
    localStorage.removeItem('permissions_audit_logs');
    
    // Ajouter un log de réinitialisation
    addAuditLog('Réinitialisation', 'Utilisateur actuel', 'Historique d\'audit', 'Historique d\'audit vidé manuellement par l\'utilisateur');
    
    // Recharger l'affichage
    loadAuditLogs();
    
    // Notification de succès
    showNotification('Historique d\'audit vidé avec succès', 'success');
}

/**
 * Gestion des modals - Créer/Modifier rôle
 */
function openCreateRoleModal() {
    editingRoleId = null;
    document.getElementById('role-modal-title').textContent = 'Créer un rôle';
    document.getElementById('role-form').reset();
    
    generatePermissionsList();
    document.getElementById('role-modal').classList.add('active');
}

function editRole(roleId) {
    const role = currentRoles.find(r => r.id === roleId);
    if (!role) return;
    
    editingRoleId = roleId;
    document.getElementById('role-modal-title').textContent = 'Modifier le rôle';
    
    document.getElementById('role-name').value = role.name;
    document.getElementById('role-display-name').value = role.displayName;
    document.getElementById('role-description').value = role.description;
    
    generatePermissionsList(role.permissions);
    document.getElementById('role-modal').classList.add('active');
}

function generatePermissionsList(selectedPermissions = []) {
    const container = document.getElementById('role-permissions-list');
    
    // Grouper les permissions par module
    const modules = [...new Set(currentPermissions.map(p => p.module))];
    
    let permissionsHTML = '';
    modules.forEach(module => {
        const modulePermissions = currentPermissions.filter(p => p.module === module);
        
        permissionsHTML += `
            <div style="margin-bottom: 1.5rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                <h4 style="margin: 0 0 1rem 0; color: #374151; text-transform: capitalize;">
                    <i class='bx bx-folder'></i> ${module}
                </h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 0.5rem;">
                    ${modulePermissions.map(permission => `
                        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                            <input type="checkbox" 
                                   name="permissions[]" 
                                   value="${permission.name}"
                                   ${selectedPermissions.includes(permission.name) ? 'checked' : ''}>
                            <span style="font-size: 0.9rem;">${permission.displayName}</span>
                        </label>
                    `).join('')}
                </div>
            </div>
        `;
    });
    
    container.innerHTML = permissionsHTML;
}

function closeRoleModal() {
    document.getElementById('role-modal').classList.remove('active');
    editingRoleId = null;
}

/**
 * Gestion des utilisateurs (avec modifications dynamiques)
 */

// Variable globale pour stocker l'utilisateur en cours d'édition
let editingUserId = null;

function editUserRoles(userId) {
    const user = currentUsers.find(u => u.id == userId);
    if (!user) {
        showNotification('Utilisateur non trouvé', 'error');
        return;
    }
    
    console.log(`✏️ Édition des rôles pour l'utilisateur ${userId}:`, user);
    
    // Stocker l'ID de l'utilisateur en cours d'édition
    editingUserId = userId;
    
    // Remplir les informations de l'utilisateur
    const userInitials = user.name.split(' ').map(n => n[0]).join('').toUpperCase();
    document.getElementById('edit-user-name').textContent = user.name;
    document.getElementById('edit-user-display-name').textContent = user.name;
    document.getElementById('edit-user-email').textContent = user.email;
    document.getElementById('edit-user-avatar').textContent = userInitials;
    
    // Générer la liste des rôles disponibles avec cases à cocher
    generateAvailableRolesList(user.roles);
    
    // Ouvrir la modal
    document.getElementById('edit-user-roles-modal').classList.add('active');
    
    // Enregistrer l'action dans l'audit
    addAuditLog(
        'Ouverture édition rôles',
        'Utilisateur actuel',
        `Utilisateur "${user.name}"`,
        'Ouverture de l\'interface d\'édition des rôles utilisateur'
    );
}

function generateAvailableRolesList(userCurrentRoles = []) {
    const container = document.getElementById('available-roles-list');
    
    if (currentRoles.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 2rem; color: #64748b;">
                <i class='bx bx-info-circle' style="font-size: 2rem; margin-bottom: 1rem;"></i>
                <p>Aucun rôle disponible pour le moment.</p>
            </div>
        `;
        return;
    }
    
    let rolesHTML = '';
    currentRoles.forEach(role => {
        const isAssigned = userCurrentRoles.includes(role.displayName);
        const roleClass = role.name.toLowerCase().replace(/[^a-z0-9]/g, '');
        
        rolesHTML += `
            <div class="role-assignment-item" style="
                display: flex; 
                align-items: center; 
                justify-content: space-between; 
                padding: 1rem; 
                background: ${isAssigned ? '#f0f9ff' : '#ffffff'}; 
                border: 2px solid ${isAssigned ? '#3b82f6' : '#e5e7eb'}; 
                border-radius: 8px;
                transition: all 0.3s ease;
                cursor: pointer;
            " onmouseover="this.style.background='${isAssigned ? '#e0f2fe' : '#f8fafc'}'" onmouseout="this.style.background='${isAssigned ? '#f0f9ff' : '#ffffff'}'">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <input type="checkbox" 
                           id="role-${role.id}" 
                           class="role-checkbox" 
                           data-role-id="${role.id}" 
                           data-role-name="${role.displayName}"
                           ${isAssigned ? 'checked' : ''}
                           style="width: 18px; height: 18px; cursor: pointer;"
                           onchange="toggleUserRole(${role.id}, '${role.displayName}', this.checked)">
                    <div>
                        <h5 style="margin: 0; color: #1e293b; font-weight: 600;">${role.displayName}</h5>
                        <p style="margin: 0; color: #64748b; font-size: 0.875rem;">${role.description}</p>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <span class="role-badge ${roleClass}" style="font-size: 0.75rem;">${role.name}</span>
                    <i class='bx ${isAssigned ? 'bx-check-circle' : 'bx-circle'}' style="
                        color: ${isAssigned ? '#10b981' : '#d1d5db'}; 
                        font-size: 1.25rem;
                    "></i>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = rolesHTML;
}

function toggleUserRole(roleId, roleName, isAssigned) {
    console.log(`🔄 Toggle rôle ${roleName} pour utilisateur ${editingUserId}:`, isAssigned);
    
    // Mettre à jour visuellement l'interface
    const roleItem = document.querySelector(`#role-${roleId}`).closest('.role-assignment-item');
    const icon = roleItem.querySelector('i');
    
    if (isAssigned) {
        roleItem.style.background = '#f0f9ff';
        roleItem.style.borderColor = '#3b82f6';
        icon.className = 'bx bx-check-circle';
        icon.style.color = '#10b981';
    } else {
        roleItem.style.background = '#ffffff';
        roleItem.style.borderColor = '#e5e7eb';
        icon.className = 'bx bx-circle';
        icon.style.color = '#d1d5db';
    }
    
    // Feedback visuel immédiat
    showNotification(
        `Rôle "${roleName}" ${isAssigned ? 'sélectionné' : 'désélectionné'}`, 
        'info'
    );
}

function saveUserRoles() {
    if (!editingUserId) {
        showNotification('Erreur: Aucun utilisateur sélectionné', 'error');
        return;
    }
    
    const user = currentUsers.find(u => u.id == editingUserId);
    if (!user) {
        showNotification('Erreur: Utilisateur non trouvé', 'error');
        return;
    }
    
    // Récupérer les rôles sélectionnés
    const selectedRoles = [];
    const roleCheckboxes = document.querySelectorAll('.role-checkbox:checked');
    
    roleCheckboxes.forEach(checkbox => {
        selectedRoles.push(checkbox.getAttribute('data-role-name'));
    });
    
    console.log(`💾 Sauvegarde des rôles pour ${user.name}:`, selectedRoles);
    
    // Mettre à jour les rôles de l'utilisateur
    const oldRoles = [...user.roles];
    user.roles = selectedRoles;
    
    // Mettre à jour le compteur d'utilisateurs pour chaque rôle
    currentRoles.forEach(role => {
        const usersWithRole = currentUsers.filter(u => u.roles.includes(role.displayName));
        role.userCount = usersWithRole.length;
    });
    
    // Sauvegarder les modifications
    saveDataToStorage();
    
    // Enregistrer l'action dans l'audit
    const addedRoles = selectedRoles.filter(role => !oldRoles.includes(role));
    const removedRoles = oldRoles.filter(role => !selectedRoles.includes(role));
    
    let auditDetails = `Rôles mis à jour pour l'utilisateur. `;
    if (addedRoles.length > 0) {
        auditDetails += `Ajoutés: ${addedRoles.join(', ')}. `;
    }
    if (removedRoles.length > 0) {
        auditDetails += `Retirés: ${removedRoles.join(', ')}.`;
    }
    
    addAuditLog(
        'Modification des rôles utilisateur',
        'Utilisateur actuel',
        `Utilisateur "${user.name}"`,
        auditDetails
    );
    
    // Fermer la modal
    closeEditUserRolesModal();
    
    // Recharger l'affichage
    loadUsers();
    loadRoles(); // Pour mettre à jour les compteurs
    
    showNotification(`Rôles mis à jour pour ${user.name}`, 'success');
}

function closeEditUserRolesModal() {
    document.getElementById('edit-user-roles-modal').classList.remove('active');
    editingUserId = null;
}

function removeUserRole(userId) {
    const user = currentUsers.find(u => u.id == userId);
    if (!user) {
        showNotification('Utilisateur non trouvé', 'error');
        return;
    }
    
    if (user.roles.length === 0) {
        showNotification('Cet utilisateur n\'a aucun rôle à retirer', 'warning');
        return;
    }
    
    console.log(`➖ Ouverture modal retrait de rôle pour l'utilisateur ${userId}`);
    
    // Stocker l'ID de l'utilisateur
    editingUserId = userId;
    
    // Remplir les informations de l'utilisateur
    const userInitials = user.name.split(' ').map(n => n[0]).join('').toUpperCase();
    document.getElementById('remove-user-display-name').textContent = user.name;
    document.getElementById('remove-user-email').textContent = user.email;
    document.getElementById('remove-user-avatar').textContent = userInitials;
    
    // Remplir la liste des rôles à retirer
    const removeRoleSelect = document.getElementById('remove-role-select');
    removeRoleSelect.innerHTML = '<option value="">Choisir un rôle à retirer</option>';
    
    user.roles.forEach(roleName => {
        const role = currentRoles.find(r => r.displayName === roleName);
        if (role) {
            removeRoleSelect.innerHTML += `<option value="${role.id}">${roleName}</option>`;
        }
    });
    
    // Ouvrir la modal
    document.getElementById('remove-role-modal').classList.add('active');
}

function confirmRemoveRole() {
    if (!editingUserId) {
        showNotification('Erreur: Aucun utilisateur sélectionné', 'error');
        return;
    }
    
    const roleId = document.getElementById('remove-role-select').value;
    if (!roleId) {
        showNotification('Veuillez sélectionner un rôle à retirer', 'warning');
        return;
    }
    
    const user = currentUsers.find(u => u.id == editingUserId);
    const role = currentRoles.find(r => r.id == roleId);
    
    if (!user || !role) {
        showNotification('Erreur: Utilisateur ou rôle non trouvé', 'error');
        return;
    }
    
    console.log(`🗑️ Retrait du rôle ${role.displayName} pour ${user.name}`);
    
    // Retirer le rôle
    user.roles = user.roles.filter(r => r !== role.displayName);
    
    // Mettre à jour le compteur d'utilisateurs pour le rôle
    role.userCount = Math.max(0, role.userCount - 1);
    
    // Sauvegarder les modifications
    saveDataToStorage();
    
    // Enregistrer l'action dans l'audit
    addAuditLog(
        'Retrait de rôle utilisateur',
        'Utilisateur actuel',
        `Utilisateur "${user.name}"`,
        `Rôle "${role.displayName}" retiré`
    );
    
    // Fermer la modal
    closeRemoveRoleModal();
    
    // Recharger l'affichage
    loadUsers();
    loadRoles();
    
    showNotification(`Rôle "${role.displayName}" retiré de ${user.name}`, 'success');
}

function closeRemoveRoleModal() {
    document.getElementById('remove-role-modal').classList.remove('active');
    editingUserId = null;
}

/**
 * Amélioration du formulaire d'assignation de rôle
 */
function openAssignRoleModal() {
    // Remplir les sélecteurs
    const userSelect = document.getElementById('assign-user');
    const roleSelect = document.getElementById('assign-role');
    
    userSelect.innerHTML = '<option value="">Sélectionner un utilisateur</option>' + 
        currentUsers.map(user => `<option value="${user.id}">${user.name} (${user.email})</option>`).join('');
    
    roleSelect.innerHTML = '<option value="">Sélectionner un rôle</option>' + 
        currentRoles.map(role => `<option value="${role.id}">${role.displayName}</option>`).join('');
    
    document.getElementById('assign-role-modal').classList.add('active');
}

function closeAssignRoleModal() {
    document.getElementById('assign-role-modal').classList.remove('active');
    document.getElementById('assign-role-form').reset();
}

/**
 * Actions sur les rôles (avec audit et sauvegarde)
 */
function deleteRole(roleId) {
    const role = currentRoles.find(r => r.id === roleId);
    if (!role) return;
    
    if (confirm(`Êtes-vous sûr de vouloir supprimer le rôle "${role.displayName}" ?\n\nCette action est irréversible.`)) {
        console.log(`🗑️ Suppression du rôle ID: ${roleId}`);
        
        // Enregistrer l'action dans l'audit
        addAuditLog(
            'Suppression de rôle',
            'Utilisateur actuel',
            `Rôle "${role.displayName}"`,
            `Rôle supprimé avec ${role.permissions.length} permission(s) et ${role.userCount} utilisateur(s) assigné(s)`
        );
        
        // Simuler la suppression
        currentRoles = currentRoles.filter(r => r.id !== roleId);
        
        // Sauvegarder les modifications
        saveDataToStorage();
        
        loadRoles();
        
        showNotification(`Rôle "${role.displayName}" supprimé avec succès`, 'success');
    }
}

async function togglePermission(roleId, permission, isChecked) {
    console.log(`🔐 Permission ${permission} ${isChecked ? 'accordée' : 'retirée'} pour le rôle ${roleId}`);
    
    const role = currentRoles.find(r => r.id === roleId);
    if (!role) return;
    
    // Mettre à jour localement d'abord pour l'interface
    if (isChecked) {
        if (!role.permissions.includes(permission)) {
            role.permissions.push(permission);
        }
    } else {
        role.permissions = role.permissions.filter(p => p !== permission);
    }
    
    // Sauvegarder dans localStorage
    saveDataToStorage();
    
    // Synchroniser avec la base de données via API
    try {
        const response = await fetch('/api/permissions.php?action=toggle-permission', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                role_id: roleId,
                permission: permission,
                enabled: isChecked
            })
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            // Enregistrer l'action dans l'audit
            addAuditLog(
                isChecked ? 'Attribution de permission' : 'Retrait de permission',
                'Utilisateur actuel',
                `Rôle "${role.displayName}"`,
                `Permission "${permission}" ${isChecked ? 'accordée' : 'retirée'} (synchronisé avec la BDD)`
            );
            
            showNotification(`Permission mise à jour pour le rôle "${role.displayName}"`, 'success');
        } else {
            // Revenir en arrière en cas d'erreur
            if (isChecked) {
                role.permissions = role.permissions.filter(p => p !== permission);
            } else {
                role.permissions.push(permission);
            }
            saveDataToStorage();
            
            showNotification(`Erreur lors de la sauvegarde: ${result.error || 'Erreur inconnue'}`, 'error');
        }
        
    } catch (error) {
        console.error('Erreur lors de la synchronisation:', error);
        
        // Revenir en arrière en cas d'erreur
        if (isChecked) {
            role.permissions = role.permissions.filter(p => p !== permission);
        } else {
            role.permissions.push(permission);
        }
        saveDataToStorage();
        
        showNotification('Erreur de connexion lors de la sauvegarde', 'error');
    }
}

/**
 * Actions générales (avec audit)
 */
async function savePermissions() {
    console.log('💾 Sauvegarde des permissions...');
    
    // Construire la matrice de permissions
    const matrix = {};
    currentRoles.forEach(role => {
        matrix[role.id] = role.permissions;
    });
    
    try {
        const response = await fetch('/api/permissions.php?action=save-matrix', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                matrix: matrix
            })
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            addAuditLog(
                'Sauvegarde des permissions',
                'Utilisateur actuel',
                'Matrice des permissions',
                `Sauvegarde manuelle de la matrice des permissions (${result.updated_roles} rôles mis à jour)`
            );
            
            showNotification('Permissions sauvegardées avec succès', 'success');
        } else {
            showNotification(`Erreur lors de la sauvegarde: ${result.error || 'Erreur inconnue'}`, 'error');
        }
        
    } catch (error) {
        console.error('Erreur lors de la sauvegarde:', error);
        showNotification('Erreur de connexion lors de la sauvegarde', 'error');
    }
}

function resetPermissions() {
    if (confirm('Êtes-vous sûr de vouloir réinitialiser toutes les permissions ?\n\nCette action restaurera les permissions par défaut.')) {
        console.log('🔄 Réinitialisation des permissions...');
        
        addAuditLog(
            'Réinitialisation des permissions',
            'Utilisateur actuel',
            'Matrice des permissions',
            'Réinitialisation complète de la matrice des permissions aux valeurs par défaut'
        );
        
        loadPermissionsMatrix();
        showNotification('Permissions réinitialisées', 'info');
    }
}

function exportRoles() {
    console.log('📤 Export des rôles...');
    
    addAuditLog(
        'Export des données',
        'Utilisateur actuel',
        'Rôles système',
        `Export de ${currentRoles.length} rôle(s)`
    );
    
    showNotification('Export des rôles en cours...', 'info');
}

function exportUsers() {
    console.log('📤 Export des utilisateurs...');
    
    addAuditLog(
        'Export des données',
        'Utilisateur actuel',
        'Utilisateurs système',
        `Export de ${currentUsers.length} utilisateur(s)`
    );
    
    showNotification('Export des utilisateurs en cours...', 'info');
}

function exportAudit() {
    console.log('📤 Export de l\'audit...');
    
    addAuditLog(
        'Export des données',
        'Utilisateur actuel',
        'Historique d\'audit',
        `Export de ${auditLogs.length} entrée(s) d'audit`
    );
    
    showNotification('Export des logs d\'audit en cours...', 'info');
}

function refreshAudit() {
    console.log('🔄 Actualisation de l\'audit...');
    loadAuditLogs();
    showNotification('Logs d\'audit actualisés', 'success');
}

/**
 * Système de notifications
 */
function showNotification(message, type = 'info') {
    // Créer la notification
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'error' ? '#ef4444' : type === 'success' ? '#10b981' : type === 'warning' ? '#f59e0b' : '#3b82f6'};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        font-weight: 500;
        animation: slideIn 0.3s ease-out;
        max-width: 400px;
    `;
    
    const icon = type === 'error' ? 'bx-error' : type === 'success' ? 'bx-check' : type === 'warning' ? 'bx-warning' : 'bx-info-circle';
    notification.innerHTML = `<i class='bx ${icon}'></i> ${message}`;
    
    document.body.appendChild(notification);
    
    // Retirer la notification après 4 secondes
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, 4000);
}

/**
 * Gestion des formulaires (avec audit et sauvegarde automatique)
 */
document.getElementById('role-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const permissions = formData.getAll('permissions[]');
    
    // Récupération des valeurs avec debug
    const roleName = formData.get('role-name');
    const roleDisplayName = formData.get('role-display-name');
    const roleDescription = formData.get('role-description');
    
    console.log('🔍 Debug formulaire:', {
        roleName: roleName,
        roleDisplayName: roleDisplayName,
        roleDescription: roleDescription,
        permissions: permissions
    });
    
    const roleData = {
        name: roleName,
        displayName: roleDisplayName, 
        description: roleDescription,
        permissions: permissions
    };
    
    if (editingRoleId) {
        console.log('✏️ Modification du rôle:', roleData);
        
        // Enregistrer l'action dans l'audit
        addAuditLog(
            'Modification de rôle',
            'Utilisateur actuel',
            `Rôle "${roleDisplayName}"`,
            `Rôle modifié avec ${permissions.length} permission(s)`
        );
        
        // Mettre à jour le rôle existant
        const roleIndex = currentRoles.findIndex(r => r.id === editingRoleId);
        if (roleIndex !== -1) {
            currentRoles[roleIndex] = { ...currentRoles[roleIndex], ...roleData };
        }
        showNotification('Rôle modifié avec succès', 'success');
    } else {
        console.log('➕ Création du rôle:', roleData);
        
        // Enregistrer l'action dans l'audit
        addAuditLog(
            'Création de rôle',
            'Utilisateur actuel',
            `Rôle "${roleDisplayName}"`,
            `Nouveau rôle créé avec ${permissions.length} permission(s)`
        );
        
        // Ajouter un nouveau rôle
        const newRole = {
            id: Math.max(...currentRoles.map(r => r.id)) + 1,
            ...roleData,
            userCount: 0
        };
        currentRoles.push(newRole);
        showNotification('Rôle créé avec succès', 'success');
    }
    
    // Sauvegarder automatiquement les modifications
    saveDataToStorage();
    
    closeRoleModal();
    loadRoles();
});

document.getElementById('assign-role-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const userId = document.getElementById('assign-user').value;
    const roleId = document.getElementById('assign-role').value;
    
    if (!userId || !roleId) {
        showNotification('Veuillez sélectionner un utilisateur et un rôle', 'warning');
        return;
    }
    
    const user = currentUsers.find(u => u.id == userId);
    const role = currentRoles.find(r => r.id == roleId);
    
    if (!user || !role) {
        showNotification('Utilisateur ou rôle non trouvé', 'error');
        return;
    }
    
    // Vérifier si l'utilisateur a déjà ce rôle
    if (user.roles.includes(role.displayName)) {
        showNotification(`L'utilisateur ${user.name} a déjà le rôle ${role.displayName}`, 'warning');
        return;
    }
    
    console.log(`🔗 Attribution du rôle ${role.displayName} à l'utilisateur ${user.name}`);
    
    // Ajouter le rôle à l'utilisateur
    user.roles.push(role.displayName);
    
    // Mettre à jour le compteur d'utilisateurs pour le rôle
    role.userCount += 1;
    
    // Sauvegarder les modifications
    saveDataToStorage();
    
    // Enregistrer l'action dans l'audit
    addAuditLog(
        'Attribution de rôle',
        'Utilisateur actuel',
        `Utilisateur "${user.name}"`,
        `Rôle "${role.displayName}" attribué`
    );
    
    showNotification(`Rôle "${role.displayName}" attribué à ${user.name}`, 'success');
    closeAssignRoleModal();
    loadUsers();
    loadRoles(); // Pour mettre à jour les compteurs
});

/**
 * Fonctions de navigation
 */

// Fonction pour revenir à la page précédente
function goBack() {
    console.log('🔙 Retour à la page précédente');
    
    // Enregistrer l'action dans l'audit
    addAuditLog(
        'Navigation',
        'Utilisateur actuel',
        'Interface de gestion',
        'Retour à la page précédente via le bouton Retour'
    );
    
    // Utiliser l'historique du navigateur ou redirection par défaut
    if (window.history.length > 1) {
        window.history.back();
    } else {
        // Si pas d'historique, rediriger vers les paramètres
        window.location.href = '/settings';
    }
}

// Fonction pour naviguer vers une URL spécifique (gardée pour les liens du breadcrumb)
function navigateTo(url) {
    console.log(`🔗 Navigation vers: ${url}`);
    
    // Enregistrer l'action dans l'audit
    addAuditLog(
        'Navigation',
        'Utilisateur actuel',
        `Page "${url}"`,
        `Navigation depuis l'interface de gestion des permissions`
    );
    
    // Redirection
    window.location.href = url;
}

// Gestion des raccourcis clavier simplifiée
document.addEventListener('keydown', function(e) {
    // Échap pour fermer les modals
    if (e.key === 'Escape') {
        const activeModals = document.querySelectorAll('.modal.active');
        if (activeModals.length > 0) {
            const lastModal = activeModals[activeModals.length - 1];
            lastModal.classList.remove('active');
        }
    }
    
    // Alt + Flèche gauche pour retour
    if (e.altKey && e.key === 'ArrowLeft') {
        e.preventDefault();
        goBack();
    }
});

// Ajouter les styles pour la navigation simplifiée
const navigationStyles = document.createElement('style');
navigationStyles.textContent = `
    /* Responsive navigation */
    @media (max-width: 768px) {
        .permissions-navigation {
            flex-direction: column;
            gap: 1rem;
            padding: 1rem;
        }
        
        .nav-left, .nav-right {
            width: 100%;
            justify-content: space-between;
        }
        
        .nav-breadcrumb {
            font-size: 0.8rem;
        }
        
        .nav-status {
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }
    }
`;
document.head.appendChild(navigationStyles);

// Ajouter les animations CSS
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    
    .role-assignment-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .role-checkbox {
        transform: scale(1.1);
        transition: transform 0.2s ease;
    }
    
    .role-checkbox:hover {
        transform: scale(1.2);
    }
`;
document.head.appendChild(style); 