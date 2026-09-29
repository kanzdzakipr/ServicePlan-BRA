(function () {
    'use strict';

    const MOBILE_BREAKPOINT = 768;
    const tableContainerSelector = [
        '.table-responsive',
        '.table-container',
        '.logistics-data-table-wrap',
        '.cm-kpi-table-wrap',
        '.cm-table-wrap',
        '.pm-table-wrap',
        '.pk-table-wrap',
        '.hse-table-wrap',
        '.prod-table-wrap',
        '.p2h-table-wrap',
        '.sl-table-wrap',
        '.mobile-table-scroll'
    ].join(',');

    function getTableColumnCount(table) {
        const rows = Array.from(table.rows).slice(0, 4);
        return rows.reduce(function (largest, row) {
            const total = Array.from(row.cells).reduce(function (count, cell) {
                return count + Math.max(1, Number(cell.colSpan) || 1);
            }, 0);
            return Math.max(largest, total);
        }, 0);
    }

    function prepareScrollRegion(region, label) {
        if (!region || region.dataset.mobileScrollReady === 'true') return;
        region.dataset.mobileScrollReady = 'true';
        region.classList.add('responsive-scroll-region');
        region.setAttribute('role', 'region');
        region.setAttribute('tabindex', '0');
        region.setAttribute('aria-label', label || 'Tabel data, geser horizontal untuk melihat kolom lain');
    }

    function enhanceTable(table) {
        if (!(table instanceof HTMLTableElement) || table.dataset.mobileTableReady === 'true') return;
        table.dataset.mobileTableReady = 'true';

        const columnCount = getTableColumnCount(table);
        table.classList.add(columnCount <= 3 ? 'mobile-compact-table' : 'mobile-wide-table');

        let region = table.closest(tableContainerSelector);
        if (!region) {
            region = document.createElement('div');
            region.className = 'mobile-table-scroll';
            table.parentNode.insertBefore(region, table);
            region.appendChild(table);
        }
        prepareScrollRegion(region, table.getAttribute('aria-label'));
    }

    function classifyFieldGrid(grid) {
        if (!(grid instanceof HTMLElement) || grid.dataset.mobileGridReady === 'true') return;
        if (!grid.querySelector('input, select, textarea')) return;

        grid.dataset.mobileGridReady = 'true';
        grid.classList.add('adaptive-field-grid');
        Array.from(grid.children).forEach(function (field) {
            if (!(field instanceof HTMLElement)) return;
            if (field.matches('.full-width, [data-mobile-span="full"]') || field.querySelector('textarea, input[type="file"]')) {
                field.classList.add('mobile-field-wide');
            }
        });
    }

    function enhanceResponsiveContent(root) {
        const scope = root && root.querySelectorAll ? root : document;
        if (root instanceof HTMLTableElement) enhanceTable(root);
        scope.querySelectorAll('table').forEach(enhanceTable);

        const inlineGrids = scope.querySelectorAll(
            '.modal-content [style*="grid-template-columns"], ' +
            '.view-section [style*="grid-template-columns"], ' +
            '.hse-form-grid, .cm-form-grid, .p2h-form-header, .pm-detail-form, .import-manual-field-grid'
        );
        inlineGrids.forEach(classifyFieldGrid);

        scope.querySelectorAll('canvas, .apexcharts-canvas').forEach(function (visual) {
            visual.classList.add('responsive-visual');
        });
    }

    function isMobile() {
        return window.innerWidth <= MOBILE_BREAKPOINT;
    }

    function setDrawer(open) {
        if (!isMobile()) return;
        document.body.classList.toggle('sidebar-collapsed', Boolean(open));
        const toggle = document.getElementById('sidebarToggle');
        if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function createDrawerBackdrop() {
        if (document.querySelector('.mobile-nav-backdrop')) return;
        const backdrop = document.createElement('button');
        backdrop.type = 'button';
        backdrop.className = 'mobile-nav-backdrop';
        backdrop.setAttribute('aria-label', 'Tutup menu navigasi');
        backdrop.addEventListener('click', function () { setDrawer(false); });
        document.body.appendChild(backdrop);
    }

    let previousMobileState = null;
    let desktopCollapsedPreference = false;
    try { desktopCollapsedPreference = localStorage.getItem('fleetmonitor_sidebar_collapsed') === '1'; } catch (error) { }

    function syncViewportMode() {
        const mobile = isMobile();
        if (mobile !== previousMobileState) {
            if (mobile) {
                document.body.classList.remove('sidebar-collapsed');
            } else {
                document.body.classList.toggle('sidebar-collapsed', desktopCollapsedPreference);
            }
            previousMobileState = mobile;
        }

        const toggle = document.getElementById('sidebarToggle');
        if (toggle) {
            toggle.setAttribute('aria-controls', 'primarySidebar');
            toggle.setAttribute('aria-expanded', mobile && document.body.classList.contains('sidebar-collapsed') ? 'true' : 'false');
        }
    }

    function notifyVisualResize() {
        window.requestAnimationFrame(function () {
            window.dispatchEvent(new Event('resize'));
            if (window.dashboardMapInstance && typeof window.dashboardMapInstance.invalidateSize === 'function') {
                window.dashboardMapInstance.invalidateSize();
            }
        });
    }

    function initialise() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) sidebar.id = sidebar.id || 'primarySidebar';
        createDrawerBackdrop();
        enhanceResponsiveContent(document);
        syncViewportMode();

        const toggle = document.getElementById('sidebarToggle');
        if (toggle) {
            toggle.addEventListener('click', function () {
                window.requestAnimationFrame(function () {
                    if (isMobile()) {
                        try { localStorage.setItem('fleetmonitor_sidebar_collapsed', desktopCollapsedPreference ? '1' : '0'); } catch (error) { }
                    } else {
                        desktopCollapsedPreference = document.body.classList.contains('sidebar-collapsed');
                    }
                    syncViewportMode();
                });
            });
        }

        document.addEventListener('click', function (event) {
            if (isMobile() && event.target.closest('.sidebar-menu a')) setDrawer(false);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && isMobile() && document.body.classList.contains('sidebar-collapsed')) {
                setDrawer(false);
                if (toggle) toggle.focus();
            }
        });

        let resizeTimer = 0;
        window.addEventListener('resize', function () {
            window.clearTimeout(resizeTimer);
            resizeTimer = window.setTimeout(function () {
                syncViewportMode();
                notifyVisualResize();
            }, 100);
        }, { passive: true });

        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === Node.ELEMENT_NODE) enhanceResponsiveContent(node);
                });
                if (mutation.type === 'attributes' && mutation.target.classList.contains('view-section') && mutation.target.classList.contains('active')) {
                    enhanceResponsiveContent(mutation.target);
                    notifyVisualResize();
                }
            });
        });
        observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialise, { once: true });
    } else {
        initialise();
    }
})();
