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

    let currentUserRole = 'Unknown';

    function init(role) {
        currentUserRole = role;
        applyUIPermissions();
    }

    function hasAccess(menu, action) {
        if (!rbacMatrix[currentUserRole]) return false;
        const perms = rbacMatrix[currentUserRole][menu];
        if (!perms || perms === '-') return false;
        return perms.includes(action);
    }

    function applyUIPermissions() {
        const rolePerms = rbacMatrix[currentUserRole];
        if (!rolePerms) return;

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
            // Kita perlu tau tombol ini masuk di menu apa. Kita bisa taruh atribut data-rbac-menu="wo"
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
        hasAccess: hasAccess,
        applyUIPermissions: applyUIPermissions
    };
})();
