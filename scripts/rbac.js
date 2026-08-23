window.FleetRBAC = (function () {
    const rbacMatrix = {
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

    const menuAliases = {
        'workorder': 'wo',
        'cost': 'biaya',
        'report': 'reports'
    };

    let currentUserRole = '';

    function getRole() {
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

        const profileRoleEl = document.getElementById('authenticatedUserRole');
        const domRole = profileRoleEl ? profileRoleEl.textContent.trim() : '';
        if (domRole && domRole !== 'Unknown') {
            currentUserRole = domRole;
            return currentUserRole;
        }

        return currentUserRole || 'Administrator';
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
        
        // Administrator always has full master access (all menus & actions)
        if (!role || role.toLowerCase() === 'administrator' || role.toLowerCase() === 'admin') {
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

        // Normalize role key in matrix (case-insensitive)
        const matchedRoleKey = Object.keys(rbacMatrix).find(k => k.toLowerCase() === role.toLowerCase());
        if (!matchedRoleKey || !rbacMatrix[matchedRoleKey]) {
            // Default allow if role is unrecognized to prevent complete lockout
            console.warn(`FleetRBAC: Role '${role}' not defined in matrix, allowing read.`);
            return true;
        }

        const rolePerms = rbacMatrix[matchedRoleKey];
        const perms = rolePerms[checkMenu];
        
        if (!perms || perms === '-') {
            return false;
        }
        return perms.includes(action);
    }

    function applyUIPermissions() {
        const role = getRole();
        
        // If Administrator, show all menus and all admin/approve buttons
        if (role.toLowerCase() === 'administrator' || role.toLowerCase() === 'admin') {
            document.querySelectorAll('.sidebar-menu li').forEach(li => li.style.display = '');
            document.querySelectorAll('.rbac-approve-only, .rbac-admin-only').forEach(el => el.style.display = '');
            return;
        }

        const matchedRoleKey = Object.keys(rbacMatrix).find(k => k.toLowerCase() === role.toLowerCase());
        if (!matchedRoleKey) return;
        const rolePerms = rbacMatrix[matchedRoleKey];

        // Hide menus in sidebar
        Object.keys(rolePerms).forEach(menu => {
            const menuItem = document.getElementById(`menu-${menu}`);
            if (menuItem) {
                const parentLi = menuItem.closest('li');
                if (rolePerms[menu] === '-') {
                    if (parentLi) parentLi.style.display = 'none';
                } else {
                    if (parentLi) parentLi.style.display = '';
                }
            }
        });

        // Hide specific buttons globally using CSS classes
        document.querySelectorAll('.rbac-approve-only').forEach(el => {
            const menu = el.getAttribute('data-rbac-menu');
            if (menu) {
                el.style.display = hasAccess(menu, 'A') ? '' : 'none';
            }
        });
        
        document.querySelectorAll('.rbac-admin-only').forEach(el => {
            const menu = el.getAttribute('data-rbac-menu');
            if (menu) {
                el.style.display = hasAccess(menu, 'O') ? '' : 'none';
            }
        });
    }

    return {
        init: init,
        getRole: getRole,
        hasAccess: hasAccess,
        applyUIPermissions: applyUIPermissions
    };
})();
