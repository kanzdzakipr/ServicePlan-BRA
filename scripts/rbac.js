window.FleetRBAC = (function () {
    const defaultRbacMatrix = {
        'Administrator': {
            'dashboard': 'RCUO', 'monitoring': 'RCUO', 'asset': 'RCUO', 'inspection': 'RCUO', 
            'wo': 'RCUO', 'pm': 'RCUO', 'logistics': 'RCUO', 'condition': 'RCUO', 
            'fuel': 'RCUO', 'productivity': 'RCUO', 'biaya': 'RCUO', 'people': 'RCUO', 
            'hse': 'RCUO', 'reports': 'RCUO', 'approval': 'RAO', 'settings': 'RCUO'
        },
        'Equipment Manager': {
            'dashboard': 'RUA', 'monitoring': 'RUA', 'asset': 'RUA', 'inspection': 'RUA', 
            'wo': 'RCUA', 'pm': 'RCUA', 'logistics': 'RA', 'condition': 'RUA', 
            'fuel': 'RUA', 'productivity': 'RUA', 'biaya': 'RUA', 'people': 'RCUA', 
            'hse': 'RCUA', 'reports': 'RCU', 'approval': 'RA', 'settings': '-'
        },
        'Maintenance Planner': {
            'dashboard': 'RU', 'monitoring': 'RU', 'asset': 'RU', 'inspection': 'RU', 
            'wo': 'RCUA', 'pm': 'RCUA', 'logistics': 'RCU', 'condition': 'RCU', 
            'fuel': 'RU', 'productivity': 'RU', 'biaya': '-', 'people': 'RU', 
            'hse': 'R', 'reports': 'RCU', 'approval': 'R', 'settings': '-'
        },
        'Mekanik Senior': {
            'dashboard': 'R', 'monitoring': 'R', 'asset': 'R', 'inspection': 'RC', 
            'wo': 'RCU', 'pm': 'R', 'logistics': 'RC', 'condition': 'R', 
            'fuel': 'R', 'productivity': 'R', 'biaya': '-', 'people': 'R', 
            'hse': 'R', 'reports': 'R', 'approval': '-', 'settings': '-'
        },
        'Mekanik Junior / Helper': {
            'dashboard': 'R', 'monitoring': 'R', 'asset': 'R', 'inspection': 'RC', 
            'wo': 'RU', 'pm': 'R', 'logistics': '-', 'condition': '-', 
            'fuel': '-', 'productivity': 'R', 'biaya': '-', 'people': 'R', 
            'hse': '-', 'reports': 'R', 'approval': '-', 'settings': '-'
        },
        'Welder / Fabrikator': {
            'dashboard': 'R', 'monitoring': 'R', 'asset': 'R', 'inspection': 'RC', 
            'wo': 'RU', 'pm': '-', 'logistics': '-', 'condition': '-', 
            'fuel': '-', 'productivity': 'R', 'biaya': '-', 'people': 'R', 
            'hse': '-', 'reports': 'R', 'approval': '-', 'settings': '-'
        },
        'Inspector K3L / Safety': {
            'dashboard': 'R', 'monitoring': 'RU', 'asset': 'R', 'inspection': 'RCUA', 
            'wo': 'RC', 'pm': '-', 'logistics': '-', 'condition': 'RCU', 
            'fuel': 'RCU', 'productivity': 'R', 'biaya': '-', 'people': '-', 
            'hse': 'RCUA', 'reports': 'RCU', 'approval': '-', 'settings': '-'
        },
        'Logistic Head': {
            'dashboard': 'R', 'monitoring': 'R', 'asset': 'R', 'inspection': '-', 
            'wo': 'R', 'pm': 'R', 'logistics': 'RCUA', 'condition': 'R', 
            'fuel': 'RCU', 'productivity': 'R', 'biaya': '-', 'people': '-', 
            'hse': '-', 'reports': 'RCU', 'approval': 'RA', 'settings': '-'
        },
        'HRD Manager': {
            'dashboard': 'R', 'monitoring': '-', 'asset': '-', 'inspection': '-', 
            'wo': '-', 'pm': '-', 'logistics': '-', 'condition': '-', 
            'fuel': '-', 'productivity': '-', 'biaya': '-', 'people': 'RCUA', 
            'hse': '-', 'reports': 'RCU', 'approval': 'RA', 'settings': '-'
        },
        'Asset Manager': {
            'dashboard': 'R', 'monitoring': 'RUA', 'asset': 'RCUA', 'inspection': '-', 
            'wo': 'R', 'pm': '-', 'logistics': '-', 'condition': 'RU', 
            'fuel': 'R', 'productivity': 'RUA', 'biaya': 'RCUA', 'people': 'R', 
            'hse': 'RA', 'reports': 'RCU', 'approval': 'RA', 'settings': '-'
        }
    };

    const moduleKeysOrder = [
        'dashboard', 'monitoring', 'asset', 'inspection',
        'wo', 'pm', 'logistics', 'condition',
        'fuel', 'productivity', 'biaya', 'people',
        'hse', 'reports', 'approval', 'settings'
    ];

    const menuAliases = {
        'workorder': 'wo',
        'cost': 'biaya',
        'report': 'reports'
    };

    let currentUserRole = '';
    let customMatrixCache = null;
    let simulatedRole = null;

    function setMatrix(matrix) {
        if (Array.isArray(matrix)) {
            customMatrixCache = matrix;
            try {
                localStorage.setItem('fleetmonitor_rbac_custom_matrix', JSON.stringify(matrix));
            } catch (e) {}
        }
    }

    function getEffectiveMatrix() {
        const matrix = JSON.parse(JSON.stringify(defaultRbacMatrix));
        let customRolesData = customMatrixCache;
        if (!customRolesData) {
            try {
                const saved = localStorage.getItem('fleetmonitor_rbac_custom_matrix');
                if (saved) {
                    customRolesData = JSON.parse(saved);
                }
            } catch (e) {
                console.warn('FleetRBAC: Gagal membaca custom matrix dari localStorage', e);
            }
        }

        if (Array.isArray(customRolesData)) {
            customRolesData.forEach(customRole => {
                if (!customRole || !customRole.name) return;
                const cleanName = customRole.name.replace(/^\d+\.\s*/, '').trim().toLowerCase();
                const matchedKey = Object.keys(matrix).find(k => k.toLowerCase() === cleanName);
                if (matchedKey && Array.isArray(customRole.permissions)) {
                    customRole.permissions.forEach((perm, idx) => {
                        const modKey = moduleKeysOrder[idx];
                        if (modKey) {
                            matrix[matchedKey][modKey] = String(perm || '-').replace(/\s+/g, '');
                        }
                    });
                }
            });
        }
        return matrix;
    }

    function getRole() {
        if (simulatedRole) {
            return simulatedRole;
        }
        if (currentUserRole && currentUserRole !== 'Unknown') {
            return currentUserRole;
        }
        if (window.authenticatedUser && window.authenticatedUser.primary_role) {
            currentUserRole = window.authenticatedUser.primary_role;
            return currentUserRole;
        }
        try {
            const cachedRole = sessionStorage.getItem('fleetmonitor_primary_role');
            if (cachedRole && cachedRole !== 'Unknown') {
                currentUserRole = cachedRole;
                return currentUserRole;
            }
        } catch (e) {}

        const profileRoleEl = document.getElementById('profileDropdownRole') || document.getElementById('authenticatedUserRole');
        const domRole = profileRoleEl ? profileRoleEl.textContent.trim() : '';
        if (domRole && domRole !== 'Unknown' && domRole !== 'Memuat pengguna...') {
            currentUserRole = domRole;
            return currentUserRole;
        }

        return currentUserRole || 'Administrator';
    }

    function isSimulating() {
        return Boolean(simulatedRole);
    }

    function simulateRole(roleName) {
        if (!roleName || roleName === 'RESET' || roleName.toLowerCase() === 'administrator') {
            exitSimulation();
            return;
        }
        const cleanName = roleName.replace(/^\d+\.\s*/, '').trim();
        simulatedRole = cleanName;
        renderSimulationBanner(cleanName);
        applyUIPermissions();
        alert(`Mode Pratinjau Aktif: Tampilan sistem kini disimulasikan sebagai [${cleanName}]. Hak akses RCUAO yang Anda atur langsung berlaku.`);
    }

    function exitSimulation() {
        simulatedRole = null;
        removeSimulationBanner();
        applyUIPermissions();
    }

    function renderSimulationBanner(roleName) {
        let banner = document.getElementById('rbacSimulationBanner');
        if (!banner) {
            banner = document.createElement('div');
            banner.id = 'rbacSimulationBanner';
            banner.style.cssText = 'position:fixed; bottom:16px; right:16px; z-index:999999; background:#1e293b; color:#fff; padding:10px 16px; border-radius:8px; box-shadow:0 10px 25px -5px rgba(0,0,0,0.3); border:2px solid #3b82f6; display:flex; align-items:center; gap:12px; font-size:0.85rem;';
            document.body.appendChild(banner);
        }
        banner.innerHTML = `
            <div>
                <i class="fa-solid fa-eye" style="color:#60a5fa; margin-right:6px;"></i>
                <span>Simulasi Role: <strong style="color:#93c5fd;">${roleName}</strong></span>
            </div>
            <button type="button" class="btn btn-sm btn-light" style="font-size:0.75rem; padding:3px 8px; border-radius:4px; font-weight:700;" onclick="window.FleetRBAC.exitSimulation()">
                <i class="fa-solid fa-xmark"></i> Keluar Simulasi
            </button>
        `;
    }

    function removeSimulationBanner() {
        const banner = document.getElementById('rbacSimulationBanner');
        if (banner) {
            banner.remove();
        }
    }

    function init(role) {
        if (role && role !== 'Unknown') {
            currentUserRole = role;
            try {
                sessionStorage.setItem('fleetmonitor_primary_role', role);
            } catch (e) {}
        }
        applyUIPermissions();
    }

    function hasAccess(menu, action = 'R') {
        const role = getRole();
        
        // Administrator always has full master access unless currently simulating another role
        if (!isSimulating() && (!role || role.toLowerCase() === 'administrator' || role.toLowerCase() === 'admin')) {
            return true;
        }

        // Exempt system utility views
        let checkMenu = (menu || '').toLowerCase().trim();
        if (menuAliases[checkMenu]) {
            checkMenu = menuAliases[checkMenu];
        }
        if (checkMenu === 'dashboard' || checkMenu === 'uc' || checkMenu === 'archive') {
            return true;
        }

        const currentMatrix = getEffectiveMatrix();
        const matchedRoleKey = Object.keys(currentMatrix).find(k => k.toLowerCase() === role.toLowerCase());
        if (!matchedRoleKey || !currentMatrix[matchedRoleKey]) {
            return false;
        }

        const rolePerms = currentMatrix[matchedRoleKey];
        const perms = rolePerms[checkMenu];
        
        if (!perms || perms === '-') {
            return false;
        }
        return perms.includes(action);
    }

    function applyUIPermissions() {
        const role = getRole();
        const isAdmin = !isSimulating() && (role.toLowerCase() === 'administrator' || role.toLowerCase() === 'admin');

        // If true Administrator (not in simulation), show all controls
        if (isAdmin) {
            document.querySelectorAll('.sidebar-menu li').forEach(li => li.style.display = '');
            document.querySelectorAll('.rbac-approve-only, .rbac-admin-only, .rbac-create-only, .rbac-update-only, [data-rbac-action]').forEach(el => el.style.display = '');
            return;
        }

        const currentMatrix = getEffectiveMatrix();
        const matchedRoleKey = Object.keys(currentMatrix).find(k => k.toLowerCase() === role.toLowerCase());
        if (!matchedRoleKey) return;
        const rolePerms = currentMatrix[matchedRoleKey];

        // 1. Sidebar menu navigation visibility based on 'R' (Read) permission
        moduleKeysOrder.forEach(menu => {
            const menuItem = document.getElementById(`menu-${menu}`);
            if (menuItem) {
                const parentLi = menuItem.closest('li');
                const canRead = hasAccess(menu, 'R');
                if (parentLi) {
                    parentLi.style.display = canRead ? '' : 'none';
                }
            }
        });

        // 2. Generic data-rbac-action & data-rbac-menu elements
        document.querySelectorAll('[data-rbac-action]').forEach(el => {
            const menu = el.getAttribute('data-rbac-menu') || '';
            const action = (el.getAttribute('data-rbac-action') || 'R').toUpperCase();
            if (menu) {
                el.style.display = hasAccess(menu, action) ? '' : 'none';
            }
        });

        // 3. Class-based action button visibility (C, U, A, O)
        document.querySelectorAll('.rbac-create-only').forEach(el => {
            const menu = el.getAttribute('data-rbac-menu') || '';
            if (menu) {
                el.style.display = hasAccess(menu, 'C') ? '' : 'none';
            }
        });

        document.querySelectorAll('.rbac-update-only').forEach(el => {
            const menu = el.getAttribute('data-rbac-menu') || '';
            if (menu) {
                el.style.display = hasAccess(menu, 'U') ? '' : 'none';
            }
        });

        document.querySelectorAll('.rbac-approve-only').forEach(el => {
            const menu = el.getAttribute('data-rbac-menu') || '';
            if (menu) {
                el.style.display = hasAccess(menu, 'A') ? '' : 'none';
            }
        });
        
        document.querySelectorAll('.rbac-admin-only').forEach(el => {
            const menu = el.getAttribute('data-rbac-menu') || '';
            if (menu) {
                el.style.display = hasAccess(menu, 'O') ? '' : 'none';
            }
        });

        // 4. If current active view is forbidden for this role, bounce back to dashboard
        const currentActiveNav = document.querySelector('.sidebar-menu a.active');
        if (currentActiveNav && currentActiveNav.id) {
            const activeMenuKey = currentActiveNav.id.replace('menu-', '');
            if (activeMenuKey !== 'dashboard' && !hasAccess(activeMenuKey, 'R')) {
                if (typeof window.showView === 'function') {
                    window.showView('dashboard');
                }
            }
        }
    }

    return {
        init: init,
        getRole: getRole,
        isSimulating: isSimulating,
        simulateRole: simulateRole,
        exitSimulation: exitSimulation,
        hasAccess: hasAccess,
        setMatrix: setMatrix,
        getEffectiveMatrix: getEffectiveMatrix,
        applyUIPermissions: applyUIPermissions
    };
})();
