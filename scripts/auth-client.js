(function () {
    'use strict';

    const nativeFetch = window.fetch.bind(window);
    const authUrl = new URL('api/auth.php', window.location.href);
    const state = { authenticated: false, csrfToken: '', user: null };

    function redirectToLogin() {
        if (!window.location.pathname.endsWith('/index.html') && !window.location.pathname.endsWith('/')) {
            window.location.replace('index.html?reason=session');
        }
    }

    function isProtectedApi(url) {
        return url.origin === window.location.origin
            && /\/api\/[^/]+\.php$/.test(url.pathname)
            && url.pathname !== authUrl.pathname;
    }

    const authReady = nativeFetch(authUrl.href + '?action=session', {
        method: 'GET',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { Accept: 'application/json' }
    }).then(async function (response) {
        const payload = await response.json().catch(function () { return null; });
        if (!response.ok || !payload || !payload.authenticated) {
            redirectToLogin();
            throw new Error('Authentication required');
        }
        state.authenticated = true;
        state.csrfToken = payload.csrf_token || '';
        state.user = payload.user || null;
        window.FleetAuth = state;
        return state;
    });

    window.FleetAuthReady = authReady;

    window.fetch = async function (input, init) {
        const requestInit = Object.assign({}, init || {});
        const requestUrl = new URL(typeof input === 'string' || input instanceof URL ? input : input.url, window.location.href);
        const protectedRequest = isProtectedApi(requestUrl);

        if (protectedRequest) {
            await authReady;
            const requestMethod = String(requestInit.method || (input instanceof Request ? input.method : 'GET')).toUpperCase();
            const headers = new Headers(input instanceof Request ? input.headers : undefined);
            new Headers(requestInit.headers || {}).forEach(function (value, key) {
                headers.set(key, value);
            });
            headers.set('Accept', 'application/json');
            if (!['GET', 'HEAD', 'OPTIONS'].includes(requestMethod)) {
                headers.set('X-CSRF-Token', state.csrfToken);
            }
            requestInit.headers = headers;
            requestInit.credentials = 'same-origin';
        }

        const response = await nativeFetch(input, requestInit);
        if (protectedRequest && response.status === 401) {
            redirectToLogin();
        }
        return response;
    };

    async function logout() {
        const auth = await authReady;
        const response = await nativeFetch(authUrl.href, {
            method: 'POST',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-Token': auth.csrfToken
            },
            body: JSON.stringify({ action: 'logout' })
        });
        if (response.ok) {
            window.location.replace('index.html?reason=logout');
            return;
        }
        throw new Error('Logout gagal');
    }

    document.addEventListener('DOMContentLoaded', function () {
        authReady.then(function (auth) {
            const profileName = document.getElementById('authenticatedUserName');
            const profileRole = document.getElementById('authenticatedUserRole');
            if (profileName) profileName.textContent = auth.user.full_name || auth.user.username;
            if (profileRole) profileRole.textContent = auth.user.primary_role || '';
            
            // Initialize RBAC UI Permissions
            if (window.FleetRBAC) {
                window.FleetRBAC.init(auth.user.primary_role || 'Unknown');
            }
        }).catch(function () {
            // Redirection is handled by authReady.
        });

        const logoutButton = document.getElementById('logoutButton');
        if (logoutButton) {
            logoutButton.addEventListener('click', function () {
                logoutButton.disabled = true;
                logout().catch(function () {
                    logoutButton.disabled = false;
                    window.alert('Logout gagal. Silakan muat ulang halaman dan coba kembali.');
                });
            });
        }
    });
})();

