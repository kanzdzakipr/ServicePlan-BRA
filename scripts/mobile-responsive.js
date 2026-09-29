(function () {
    'use strict';

    const MOBILE_BREAKPOINT = 768;
    const tabContainerSelector = [
        '.asset-tabs',
        '.report-view-tabs',
        '.logistics-ledger-tabs',
        '.cm-tabs',
        '.pm-tabs',
        '.pk-nav-tabs',
        '.hse-nav-tabs',
        '.prod-nav-tabs',
        '.p2h-nav-tabs',
        '.notification-panel-tabs',
        '.settings-sidebar-nav'
    ].join(',');
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

        const hint = document.createElement('div');
        hint.className = 'mobile-scroll-hint';
        hint.setAttribute('aria-hidden', 'true');
        hint.innerHTML = '<i class="fas fa-arrows-left-right" aria-hidden="true"></i><span>Geser tabel untuk melihat kolom lain</span>';
        region.parentNode.insertBefore(hint, region);
        region._mobileScrollHint = hint;
        region.addEventListener('scroll', function () {
            if (region.scrollLeft > 8) {
                region.dataset.scrollHintDismissed = 'true';
                refreshScrollHint(region);
            }
        }, { passive: true });
        refreshScrollHint(region);
    }

    function refreshScrollHint(region) {
        if (!region || !region._mobileScrollHint) return;
        const hasOverflow = region.scrollWidth > region.clientWidth + 2;
        const showHint = isMobile() && hasOverflow && region.dataset.scrollHintDismissed !== 'true';
        region.classList.toggle('has-horizontal-scroll', hasOverflow);
        region._mobileScrollHint.classList.toggle('is-visible', showHint);
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

    function normaliseControlLabel(value) {
        return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function inferIconControlLabel(control) {
        const context = [
            control.id,
            control.className,
            control.getAttribute('onclick'),
            control.getAttribute('data-action'),
            control.querySelector('i, svg') && control.querySelector('i, svg').getAttribute('class')
        ].filter(Boolean).join(' ').toLowerCase();

        const labelMap = [
            [/chevron-left|arrow-left|prev|previous/, 'Halaman sebelumnya'],
            [/chevron-right|arrow-right|next/, 'Halaman berikutnya'],
            [/trash|delete|remove|hapus/, 'Hapus'],
            [/reject|decline|tolak/, 'Tolak'],
            [/times|xmark|close|dismiss|tutup/, 'Tutup'],
            [/approve|verify|check|setuju/, 'Setujui'],
            [/pen|edit|ubah/, 'Edit'],
            [/eye|detail|view/, 'Lihat detail'],
            [/download|export/, 'Unduh'],
            [/print|cetak/, 'Cetak'],
            [/refresh|sync|reload/, 'Muat ulang'],
            [/search|cari/, 'Cari'],
            [/filter/, 'Filter'],
            [/plus|add|create|tambah/, 'Tambah'],
            [/ellipsis|more/, 'Opsi lainnya'],
            [/bell|notif/, 'Notifikasi'],
            [/menu|bars|sidebar/, 'Buka menu navigasi']
        ];
        const match = labelMap.find(function (entry) { return entry[0].test(context); });
        return match ? match[1] : '';
    }

    function enhanceControl(control) {
        if (!(control instanceof HTMLElement)) return;

        if (control instanceof HTMLButtonElement && !control.hasAttribute('type')) {
            const isActionButton = !control.closest('form') || control.hasAttribute('onclick') ||
                control.hasAttribute('data-action') || control.hasAttribute('data-close');
            if (isActionButton) control.type = 'button';
        }

        const visibleText = normaliseControlLabel(control.textContent).replace(/^[×✕✖]+$/, '');
        const existingLabel = normaliseControlLabel(
            control.getAttribute('aria-label') || control.getAttribute('title') || visibleText
        );
        const iconOnly = !visibleText && Boolean(control.querySelector('i, svg') || /^[×✕✖]+$/.test(normaliseControlLabel(control.textContent)));

        if (iconOnly) {
            const inferredLabel = existingLabel || inferIconControlLabel(control);
            control.classList.add('icon-only-control');
            if (inferredLabel) {
                if (!control.hasAttribute('aria-label')) control.setAttribute('aria-label', inferredLabel);
                if (!control.hasAttribute('title')) control.setAttribute('title', inferredLabel);
            }
            control.querySelectorAll('i, svg').forEach(function (icon) {
                icon.setAttribute('aria-hidden', 'true');
            });
        }
    }

    function enhanceFormControl(control) {
        if (!(control instanceof HTMLElement) || control.hasAttribute('aria-label') || control.hasAttribute('aria-labelledby')) return;
        if (control.matches('input[type="hidden"], input[type="submit"], input[type="button"]')) return;

        let label = '';
        if (control.id) {
            const explicitLabel = document.querySelector('label[for="' + CSS.escape(control.id) + '"]');
            if (explicitLabel) label = normaliseControlLabel(explicitLabel.textContent);
        }
        const wrappingLabel = control.closest('label');
        const fieldLabel = control.closest('.form-group, .form-field, .filter-group, .field-group');
        label = label || normaliseControlLabel(wrappingLabel && wrappingLabel.textContent);
        label = label || normaliseControlLabel(fieldLabel && fieldLabel.querySelector('label') && fieldLabel.querySelector('label').textContent);
        label = label || normaliseControlLabel(control.getAttribute('placeholder'));
        if (label) control.setAttribute('aria-label', label.replace(/[*:]+$/g, '').trim());
    }

    function syncTabState(container) {
        const tabs = Array.from(container.querySelectorAll(':scope > button, :scope > a, :scope > [role="tab"]'));
        if (!tabs.length) return;
        tabs.forEach(function (tab, index) {
            const active = tab.classList.contains('active') || tab.getAttribute('aria-current') === 'page';
            tab.setAttribute('role', 'tab');
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
            tab.setAttribute('tabindex', active || (!tabs.some(function (item) { return item.classList.contains('active'); }) && index === 0) ? '0' : '-1');
        });
    }

    function enhanceTabs(container) {
        if (!(container instanceof HTMLElement)) return;
        container.setAttribute('role', 'tablist');
        syncTabState(container);
        if (container.dataset.keyboardTabsReady === 'true') return;
        container.dataset.keyboardTabsReady = 'true';
        container.addEventListener('keydown', function (event) {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            const tabs = Array.from(container.querySelectorAll(':scope > button, :scope > a, :scope > [role="tab"]'))
                .filter(function (tab) { return !tab.disabled && tab.getAttribute('aria-disabled') !== 'true'; });
            const currentIndex = tabs.indexOf(document.activeElement);
            if (currentIndex < 0 || !tabs.length) return;
            event.preventDefault();
            let nextIndex = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1 :
                (currentIndex + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
            tabs[nextIndex].focus();
            tabs[nextIndex].click();
        });
    }

    function enhanceSegmentedControl(container) {
        if (!(container instanceof HTMLElement)) return;
        container.querySelectorAll('button').forEach(function (button) {
            button.setAttribute('aria-pressed', button.classList.contains('active') ? 'true' : 'false');
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

        if (root instanceof HTMLElement && root.matches('button, a[href], [role="button"]')) enhanceControl(root);
        scope.querySelectorAll('button, a[href], [role="button"]').forEach(enhanceControl);
        scope.querySelectorAll('input, select, textarea').forEach(enhanceFormControl);

        if (root instanceof HTMLElement && root.matches(tabContainerSelector)) enhanceTabs(root);
        scope.querySelectorAll(tabContainerSelector).forEach(enhanceTabs);
        scope.querySelectorAll('.wo-view-switch').forEach(enhanceSegmentedControl);
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
        document.querySelectorAll('.responsive-scroll-region').forEach(refreshScrollHint);
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
                if (mutation.type === 'attributes') {
                    const tabContainer = mutation.target.closest && mutation.target.closest(tabContainerSelector);
                    if (tabContainer) syncTabState(tabContainer);
                    const segmentedControl = mutation.target.closest && mutation.target.closest('.wo-view-switch');
                    if (segmentedControl) enhanceSegmentedControl(segmentedControl);
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
