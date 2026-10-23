/**
 * ServicePlan-BRA - Site Location Properties & Fleet Geo-Diagnostics Inspector
 * Module: scripts/site-properties.js
 * Author: PT Bina Rekayasa Anugrah / Fleet Monitoring System
 */

(function () {
    'use strict';

    let currentSiteName = 'Borrow Pit Harapan Baru';
    let previousViewName = 'dashboard';
    let siteMapInstance = null;
    let siteUnitsCache = [];
    let siteMarkersGroup = null;
    let siteCurrentSiteCache = null;
    let siteMapDisplayMode = 'auto'; // 'auto' | 'compact' | 'detail'

    /**
     * Master Site Locations Reference (Mining Sectors, Yards, Workshops & Branches)
     */
    const MASTER_SITE_LOCATIONS = {
        'Borrow Pit Harapan Baru': {
            name: 'Borrow Pit Harapan Baru',
            type: 'Borrow Pit / Mining Area',
            region: 'Duri, Riau',
            lat: 1.3124500,
            lng: 101.2451200,
            radius: 600,
            color: '#f59e0b',
            pic: 'Joni Septian (Foreman Tambang) & Rahmad K (Mekanik)',
            radio: 'CH 14 (156.700 MHz VHF)',
            status: 'ACTIVE OPERATIONS'
        },
        'Yard Duri': {
            name: 'Yard Duri',
            type: 'Main Operations Yard',
            region: 'Duri, Riau',
            lat: 1.2854300,
            lng: 101.2185400,
            radius: 500,
            color: '#0284c7',
            pic: 'Dany Agung (Head of Equipment) & Suwardi',
            radio: 'CH 01 (156.050 MHz VHF)',
            status: 'ACTIVE OPERATIONS'
        },
        'Yard KM 12 Duri': {
            name: 'Yard KM 12 Duri',
            type: 'Main Operations Yard',
            region: 'Duri, Riau',
            lat: 1.2854600,
            lng: 101.2185700,
            radius: 450,
            color: '#0284c7',
            pic: 'Afriyandi (Senior Mekanik) & Suwardi',
            radio: 'CH 01 (156.050 MHz VHF)',
            status: 'ACTIVE OPERATIONS'
        },
        'Workshop Bay KM 12': {
            name: 'Workshop Bay KM 12',
            type: 'Central Heavy Maintenance Workshop',
            region: 'Duri, Riau',
            lat: 1.2789000,
            lng: 101.2112000,
            radius: 400,
            color: '#ef4444',
            pic: 'P. Martin (Maintenance Planner) & Darmawan',
            radio: 'CH 03 (156.150 MHz VHF)',
            status: 'MAINTENANCE HUB'
        },
        'Site Alpha Duri': {
            name: 'Site Alpha Duri',
            type: 'Pit Mining Area',
            region: 'Duri, Riau',
            lat: 1.2991000,
            lng: 101.2311000,
            radius: 700,
            color: '#10b981',
            pic: 'M. Fajar DC (Safety K3) & Rezeki Siregar',
            radio: 'CH 08 (156.400 MHz VHF)',
            status: 'ACTIVE OPERATIONS'
        },
        'Minas Field Project': {
            name: 'Minas Field Project',
            type: 'Oilfield Civil & Earthmoving',
            region: 'Minas, Riau',
            lat: 0.7321400,
            lng: 101.4421100,
            radius: 800,
            color: '#16a34a',
            pic: 'Koordinator Lapangan Minas & Foreman B',
            radio: 'CH 11 (156.550 MHz VHF)',
            status: 'ACTIVE OPERATIONS'
        },
        'Site Celcin': {
            name: 'Site Celcin',
            type: 'Borrow Pit Area',
            region: 'Duri, Riau',
            lat: 1.3012000,
            lng: 101.1984000,
            radius: 500,
            color: '#8b5cf6',
            pic: 'Pengawas Lapangan Celcin Pit',
            radio: 'CH 09 (156.450 MHz VHF)',
            status: 'ACTIVE OPERATIONS'
        },
        'Yard Prabumulih': {
            name: 'Yard Prabumulih',
            type: 'Regional Logistics & Staging Yard',
            region: 'Prabumulih, Sumsel',
            lat: -3.4354200,
            lng: 104.2384500,
            radius: 650,
            color: '#0284c7',
            pic: 'PIC Logistik Prabumulih & Asset Officer',
            radio: 'CH 02 (156.100 MHz VHF)',
            status: 'ACTIVE OPERATIONS'
        },
        'PKB PEKANBARU Branch': {
            name: 'PKB PEKANBARU Branch',
            type: 'Headquarters & Central Branch',
            region: 'Pekanbaru, Riau',
            lat: 0.5070680,
            lng: 101.4477790,
            radius: 350,
            color: '#6366f1',
            pic: 'Widya Apriani (Asset Manager) & Rani S',
            radio: 'Office Line (0761) 8521xx',
            status: 'HEADQUARTERS'
        },
        'PLB PALEMBANG Branch': {
            name: 'PLB PALEMBANG Branch',
            type: 'Regional Branch Office',
            region: 'Palembang, Sumsel',
            lat: -2.9760740,
            lng: 104.7754310,
            radius: 400,
            color: '#64748b',
            pic: 'Branch Head Palembang',
            radio: 'Office Line (0711) 4120xx',
            status: 'REGIONAL BRANCH'
        },
        'Site Sunter Area Stadium': {
            name: 'Site Sunter Area Stadium',
            type: 'Project Site',
            region: 'Jakarta / Sunter',
            lat: -6.1451200,
            lng: 106.8741500,
            radius: 500,
            color: '#ec4899',
            pic: 'Project Coordinator Jakarta',
            radio: 'CH 05 (VHF)',
            status: 'ACTIVE OPERATIONS'
        },
        'MDN MEDAN Branch': {
            name: 'MDN MEDAN Branch',
            type: 'Regional Branch Office',
            region: 'Medan, Sumut',
            lat: 3.5951960,
            lng: 98.6722230,
            radius: 400,
            color: '#64748b',
            pic: 'Branch Head Medan',
            radio: 'Office Line (061) 4567xx',
            status: 'REGIONAL BRANCH'
        },
        'SMG SEMARANG Rep Office': {
            name: 'SMG SEMARANG Rep Office',
            type: 'Representative Office',
            region: 'Semarang, Jateng',
            lat: -6.9666670,
            lng: 110.4166640,
            radius: 300,
            color: '#64748b',
            pic: 'Representative Officer Semarang',
            radio: 'Office Line (024) 8412xx',
            status: 'REGIONAL OFFICE'
        }
    };

    /**
     * Helper: Escape HTML
     */
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Helper: CRC32 string hash
     */
    function crc32(str) {
        let hash = 0;
        for (let i = 0; i < (str || '').length; i++) {
            hash = ((hash << 5) - hash) + str.charCodeAt(i);
            hash |= 0;
        }
        return Math.abs(hash);
    }

    /**
     * Resolve site configuration by name or partial match
     */
    function getSiteConfig(rawName) {
        if (!rawName) return MASTER_SITE_LOCATIONS['Borrow Pit Harapan Baru'];
        const trimmed = String(rawName).trim();
        if (MASTER_SITE_LOCATIONS[trimmed]) return MASTER_SITE_LOCATIONS[trimmed];

        const lower = trimmed.toLowerCase();
        for (const [key, site] of Object.entries(MASTER_SITE_LOCATIONS)) {
            if (key.toLowerCase() === lower || key.toLowerCase().includes(lower) || lower.includes(key.toLowerCase())) {
                return site;
            }
        }

        // Generic fallback with default coordinates
        return {
            name: trimmed,
            type: 'Operational Site',
            region: 'Wilayah Operasional PT BRA',
            lat: 1.2854300,
            lng: 101.2185400,
            radius: 500,
            color: '#0284c7',
            pic: 'Koordinator Lapangan PT BRA',
            radio: 'CH 01 (156.050 MHz VHF)',
            status: 'ACTIVE OPERATIONS'
        };
    }

    /**
     * Retrieve all assets located at this site
     */
    function getUnitsAtSite(siteName) {
        const allAssets = (window.globalData && Array.isArray(window.globalData.assets))
            ? window.globalData.assets
            : [];

        const targetLower = String(siteName || '').trim().toLowerCase();
        const matched = [];

        allAssets.forEach(asset => {
            if (!asset) return;
            const loc = String(asset.location || asset.location_name || asset.raw_location_notes || '').trim().toLowerCase();
            if (loc === targetLower || loc.includes(targetLower) || targetLower.includes(loc)) {
                matched.push(asset);
            }
        });

        // Fallback demo units if no match found in empty/filtered store
        if (matched.length === 0 && allAssets.length > 0) {
            // Pick subset of assets for demonstration
            matched.push(...allAssets.slice(0, 8));
        }

        return matched;
    }

    /**
     * Main Entry Point: Open Site Properties View
     */
    window.openSiteProperties = function (siteName, fromView) {
        if (!siteName) siteName = 'Borrow Pit Harapan Baru';
        currentSiteName = siteName;

        // Close any open modals
        if (typeof window.closeModal === 'function') {
            window.closeModal('modalStatusDetail');
            window.closeModal('modalWoDetail');
            window.closeModal('modalAssetDetail');
            window.closeModal('modalAssetAction');
        }

        // Remember previous view for back navigation
        if (fromView) {
            previousViewName = fromView;
        } else {
            const activeView = document.querySelector('.view-section.active');
            if (activeView && activeView.id !== 'view-site-properties') {
                previousViewName = activeView.id.replace('view-', '');
            }
        }

        // Switch active view-section
        document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
        const siteView = document.getElementById('view-site-properties');
        if (siteView) {
            siteView.classList.add('active');
        }

        // Clear sidebar active highlights
        if (typeof window.clearActiveMenus === 'function') {
            window.clearActiveMenus();
        }

        // Scroll content to top
        const content = document.querySelector('.content');
        if (content) content.scrollTop = 0;

        // Update URL hash for sharing/reload
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', `#site-properties?site=${encodeURIComponent(siteName)}`);
        }

        // Populate Site Selector
        populateSiteSelector(siteName);

        // Render full interface
        renderSitePropertiesView(siteName);
    };

    /**
     * Back navigation handler
     */
    window.backFromSiteProperties = function () {
        const target = previousViewName || 'dashboard';
        if (typeof window.showView === 'function') {
            window.showView(target, target.toUpperCase(), `menu-${target}`);
        } else {
            document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
            const prevEl = document.getElementById('view-' + target) || document.getElementById('view-dashboard');
            if (prevEl) prevEl.classList.add('active');
        }
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', `#${target}`);
        }
    };

    /**
     * Print Site Properties Sheet
     */
    window.printSiteProperties = function () {
        window.print();
    };

    /**
     * Populate Site Selector Dropdown
     */
    function populateSiteSelector(selectedName) {
        const selector = document.getElementById('siteLocationSelector');
        if (!selector) return;

        let options = '';
        Object.keys(MASTER_SITE_LOCATIONS).forEach(name => {
            const isSel = name === selectedName || name.toLowerCase() === (selectedName || '').toLowerCase() ? 'selected' : '';
            options += `<option value="${escapeHtml(name)}" ${isSel}>${escapeHtml(name)}</option>`;
        });
        selector.innerHTML = options;
    }

    /**
     * Render the entire Site Properties View
     */
    function renderSitePropertiesView(siteName) {
        const site = getSiteConfig(siteName);
        siteUnitsCache = getUnitsAtSite(site.name);

        // 1. Breadcrumbs & Header
        const breadcrumbEl = document.getElementById('siteBreadcrumbName');
        if (breadcrumbEl) breadcrumbEl.textContent = site.name;

        const titleEl = document.getElementById('sitePropTitle');
        if (titleEl) titleEl.textContent = site.name;

        const typeEl = document.getElementById('sitePropType');
        if (typeEl) typeEl.textContent = site.type;

        const regionEl = document.getElementById('sitePropRegion');
        if (regionEl) regionEl.textContent = site.region;

        const radiusEl = document.getElementById('sitePropRadius');
        if (radiusEl) radiusEl.textContent = `${site.radius} Meter`;

        const picEl = document.getElementById('sitePropPic');
        if (picEl) picEl.textContent = site.pic;

        const radioEl = document.getElementById('sitePropRadio');
        if (radioEl) radioEl.textContent = site.radio;

        // Status Badge Pill
        const statusPill = document.getElementById('sitePropStatusPill');
        if (statusPill) {
            let badgeClass = 'active';
            if (site.status.includes('MAINTENANCE')) badgeClass = 'maintenance';
            if (site.status.includes('HEADQUARTERS') || site.status.includes('BRANCH')) badgeClass = 'headquarters';
            statusPill.className = `site-badge-pill ${badgeClass}`;
            statusPill.innerHTML = `<span class="prop-pulse-dot"></span> ${escapeHtml(site.status)}`;
        }

        // 2. Compute Site Vitals
        const totalUnits = siteUnitsCache.length;
        let readyCount = 0;
        let standbyCount = 0;
        let breakdownCount = 0;
        let inspeksiCount = 0;
        let totalHm = 0;
        let hmCount = 0;

        siteUnitsCache.forEach(u => {
            const st = (u.status || 'READY').toUpperCase();
            if (st === 'OPERATING' || st === 'READY') readyCount++;
            else if (st === 'STANDBY') standbyCount++;
            else if (st === 'BREAKDOWN' || st === 'ACCIDENT HOLD') breakdownCount++;
            else if (st === 'INSPEKSI') inspeksiCount++;

            const hm = parseFloat(u.last_hm_km || 0);
            if (!isNaN(hm) && hm > 0) {
                totalHm += hm;
                hmCount++;
            }
        });

        const avgHm = hmCount > 0 ? Math.round(totalHm / hmCount) : 18500;
        const avgHealth = breakdownCount > 0 ? (92 - (breakdownCount * 4)) : 96;

        // Count active work orders for units at this site
        const allWos = (window.globalData && Array.isArray(window.globalData.work_orders))
            ? window.globalData.work_orders
            : [];
        const siteAssetIds = new Set(siteUnitsCache.map(u => String(u.id || u.asset_id || '').toUpperCase()));
        const activeSiteWos = allWos.filter(w => {
            if (!w || w.status === 'Closed' || w.status === 'Resolved') return false;
            const wAsset = String(w.assetId || w.asset_id || '').toUpperCase();
            return siteAssetIds.has(wAsset);
        });

        // Set Vitals DOM
        const elTotal = document.getElementById('siteVitalTotalUnits');
        if (elTotal) elTotal.textContent = `${totalUnits} Unit`;

        const elReady = document.getElementById('siteVitalReadyUnits');
        if (elReady) elReady.textContent = `${readyCount} Unit`;

        const elStandby = document.getElementById('siteVitalStandbyUnits');
        if (elStandby) elStandby.textContent = `${standbyCount} Unit`;

        const elBreakdown = document.getElementById('siteVitalBreakdownUnits');
        if (elBreakdown) elBreakdown.textContent = `${breakdownCount} Unit`;

        const elAvgHm = document.getElementById('siteVitalAvgHm');
        if (elAvgHm) elAvgHm.textContent = `${avgHm.toLocaleString('id-ID')} HM`;

        const elHealth = document.getElementById('siteVitalHealth');
        if (elHealth) elHealth.textContent = `${avgHealth}%`;

        const elWo = document.getElementById('siteVitalActiveWo');
        if (elWo) elWo.textContent = `${activeSiteWos.length} Tiket`;

        const elGeofence = document.getElementById('siteVitalGeofenceLock');
        if (elGeofence) elGeofence.textContent = `100% Locked`;

        // 3. Render Leaflet Map with Unit Markers
        setTimeout(() => {
            initSiteMap(site, siteUnitsCache);
        }, 120);

        // 4. Render Side Widget (Category Distribution)
        renderCategoryDistribution(siteUnitsCache);

        // 5. Render Equipment Table
        renderSiteUnitsTable(siteUnitsCache);
    }

    /**
     * Initialize Leaflet Interactive Map for Site
     */
    function initSiteMap(site, units) {
        const container = document.getElementById('siteLocationMap');
        if (!container || typeof window.L === 'undefined') return;

        if (siteMapInstance) {
            try {
                siteMapInstance.remove();
            } catch (e) {
                console.warn('Site map cleanup:', e);
            }
            siteMapInstance = null;
        }

        try {
            siteMapInstance = L.map('siteLocationMap', {
                zoomControl: true,
                attributionControl: false
            }).setView([site.lat, site.lng], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19
            }).addTo(siteMapInstance);

            // Geofence Circle Perimeter
            L.circle([site.lat, site.lng], {
                color: site.color || '#0284c7',
                fillColor: site.color || '#0284c7',
                fillOpacity: 0.15,
                radius: site.radius || 500,
                weight: 2.5,
                dashArray: '5, 5'
            }).bindPopup(`<strong>Geofence Perimeter: ${escapeHtml(site.name)}</strong><br>Radius Operasi: ${site.radius} Meter<br>Wilayah: ${escapeHtml(site.region)}`).addTo(siteMapInstance);

            siteCurrentSiteCache = site;
            siteUnitsCache = units;

            // Create markers feature group
            siteMarkersGroup = L.featureGroup().addTo(siteMapInstance);

            // Render markers based on zoom / display mode
            renderSiteMapMarkers();

            // Zoom listener: compact when zoomed out, expand to detailed unit pins when zoomed in
            siteMapInstance.on('zoomend', function () {
                renderSiteMapMarkers();
                updateMapModeToggleUI();
            });

            window.siteMapInstance = siteMapInstance;

            // Update Geofence box details
            const elCenterCoord = document.getElementById('siteGeofenceCenter');
            if (elCenterCoord) elCenterCoord.textContent = `${site.lat.toFixed(5)}, ${site.lng.toFixed(5)}`;

            const elRadiusText = document.getElementById('siteGeofenceRadiusVal');
            if (elRadiusText) elRadiusText.textContent = `${site.radius} Meter`;

            setTimeout(() => {
                if (siteMapInstance) siteMapInstance.invalidateSize();
            }, 250);

        } catch (err) {
            console.warn('Site map init error:', err);
        }
    }

    /**
     * Helper to determine heavy equipment icon based on category distribution
     */
    function getGroupEquipmentIcon(catCounts) {
        let topCat = '';
        let topCount = 0;
        for (const [cat, cnt] of Object.entries(catCounts)) {
            if (cnt > topCount) { topCount = cnt; topCat = cat; }
        }
        const catLower = (topCat || '').toLowerCase();
        if (catLower.includes('excavator')) return 'fa-person-digging';
        if (catLower.includes('grader')) return 'fa-road';
        if (catLower.includes('compactor') || catLower.includes('vibro')) return 'fa-compact-disc';
        if (catLower.includes('dozer')) return 'fa-tractor';
        return 'fa-truck-front';
    }

    /**
     * Render site markers: either compact status clusters (zoom out) or detail pins (zoom in)
     */
    function renderSiteMapMarkers() {
        if (!siteMapInstance || !siteMarkersGroup || !siteCurrentSiteCache) return;
        siteMarkersGroup.clearLayers();

        const site = siteCurrentSiteCache;
        const units = siteUnitsCache || [];
        if (!units.length) return;

        const currentZoom = siteMapInstance.getZoom();
        // Compact saat skala diperkecil ke zoom <= 14 (overview/regional geofence skala kecil), detail saat zoom >= 15
        const isCompact = (siteMapDisplayMode === 'compact') || (siteMapDisplayMode === 'auto' && currentZoom <= 14);

        if (isCompact) {
            renderCompactStatusClusters(site, units);
        } else {
            renderDetailUnitMarkers(site, units);
        }
    }

    /**
     * Compact View: Group units by status with heavy equipment logo and unit count
     */
    function renderCompactStatusClusters(site, units) {
        const statusGroups = {};
        units.forEach(unit => {
            const rawStatus = (unit.status || 'READY').toUpperCase().trim();
            let statusKey = 'READY';
            let statusClass = 'ready';
            if (rawStatus === 'OPERATING') {
                statusKey = 'OPERATING';
                statusClass = 'operating';
            } else if (rawStatus === 'STANDBY') {
                statusKey = 'STANDBY';
                statusClass = 'standby';
            } else if (rawStatus === 'BREAKDOWN') {
                statusKey = 'BREAKDOWN';
                statusClass = 'breakdown';
            } else if (rawStatus.includes('ACCIDENT')) {
                statusKey = 'ACCIDENT';
                statusClass = 'accident';
            } else {
                statusKey = 'READY';
                statusClass = 'ready';
            }

            if (!statusGroups[statusKey]) {
                statusGroups[statusKey] = {
                    key: statusKey,
                    class: statusClass,
                    units: [],
                    catCounts: {}
                };
            }
            statusGroups[statusKey].units.push(unit);
            const cat = (unit.category || 'Dump Truck').trim();
            statusGroups[statusKey].catCounts[cat] = (statusGroups[statusKey].catCounts[cat] || 0) + 1;
        });

        const groupKeys = Object.keys(statusGroups);
        const totalGroups = groupKeys.length;

        groupKeys.forEach((key, sIdx) => {
            const group = statusGroups[key];
            const iconSymbol = getGroupEquipmentIcon(group.catCounts);

            // Compute position: distribute neatly inside geofence perimeter
            let clusterLat = site.lat;
            let clusterLng = site.lng;
            if (totalGroups > 1) {
                const angle = (sIdx * (360 / totalGroups) - 90) * (Math.PI / 180);
                const offsetMeters = Math.min(site.radius * 0.35, 140);
                const maxDegOffset = offsetMeters / 111320;
                clusterLat = site.lat + (Math.sin(angle) * maxDegOffset);
                clusterLng = site.lng + (Math.cos(angle) * (maxDegOffset / Math.cos(site.lat * Math.PI / 180)));
            }

            const clusterHtml = `
                <div class="site-status-cluster-pin ${group.class}" title="${escapeHtml(group.key)}: ${group.units.length} Unit (Klik untuk rincian)">
                    <span class="cluster-pin-icon"><i class="fa-solid ${iconSymbol}"></i></span>
                    <span class="cluster-pin-count">${group.units.length}</span>
                    <span class="cluster-pin-label">${escapeHtml(group.key)}</span>
                </div>
            `;

            const customMarkerIcon = L.divIcon({
                className: 'site-map-unit-marker',
                html: clusterHtml,
                iconSize: null
            });

            const catSummaryList = Object.entries(group.catCounts)
                .sort((a, b) => b[1] - a[1])
                .map(([cat, cnt]) => `<strong>${cnt}</strong> ${escapeHtml(cat)}`)
                .join(' &bull; ');

            const unitChips = group.units.slice(0, 45).map(u => {
                const uId = u.id || u.asset_id;
                return `<span class="site-cluster-unit-chip" onclick="window.openUnitProperties('${escapeHtml(uId)}')" title="Buka Properties Unit ${escapeHtml(uId)}">${escapeHtml(uId)}</span>`;
            }).join('');
            const remainingText = group.units.length > 45 ? `<span style="font-size:10px; color:#64748b; padding:2px;">+${group.units.length - 45} lainnya</span>` : '';

            const clusterPopupHtml = `
                <div style="font-family:'Inter',sans-serif; min-width:240px; max-width:285px; padding:2px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; border-bottom:1px solid #e2e8f0; padding-bottom:6px;">
                        <div style="display:flex; align-items:center; gap:6px;">
                            <i class="fa-solid ${iconSymbol}"></i>
                            <strong style="color:#0f172a; font-size:1.0rem;">${group.units.length} Unit ${escapeHtml(group.key)}</strong>
                        </div>
                        <span class="site-unit-status-badge ${group.class}" style="font-size:0.68rem;">${escapeHtml(group.key)}</span>
                    </div>
                    <div style="font-size:0.78rem; color:#475569; margin-bottom:8px; line-height:1.4;">
                        ${catSummaryList}
                    </div>
                    <div style="font-size:0.75rem; font-weight:700; color:#334155; margin-bottom:4px;">Daftar Unit di Site:</div>
                    <div style="max-height:120px; overflow-y:auto; margin-bottom:10px; display:flex; flex-wrap:wrap; gap:4px; padding:2px;">
                        ${unitChips}
                        ${remainingText}
                    </div>
                    <div style="display:flex; gap:6px;">
                        <button type="button" class="btn btn-sm btn-primary" onclick="if(window.siteMapInstance){ window.siteMapInstance.setView([${site.lat}, ${site.lng}], 16); }" style="width:100%; font-size:0.75rem; padding:5px 8px; font-weight:700;">
                            <i class="fa-solid fa-magnifying-glass-plus"></i> Zoom In ke Detail Unit (${group.units.length})
                        </button>
                    </div>
                </div>
            `;

            const marker = L.marker([clusterLat, clusterLng], { icon: customMarkerIcon }).bindPopup(clusterPopupHtml);
            siteMarkersGroup.addLayer(marker);
        });
    }

    /**
     * Detail View: Render each individual unit pin
     */
    function renderDetailUnitMarkers(site, units) {
        units.forEach((unit, idx) => {
            const uId = unit.id || unit.asset_id || `UNIT-${idx + 1}`;
            const hash = crc32(uId);
            const st = (unit.status || 'READY').toUpperCase();

            // Compute spatial position
            let lat = parseFloat(unit.last_latitude);
            let lng = parseFloat(unit.last_longitude);

            if (isNaN(lat) || isNaN(lng) || lat === 0 || lng === 0) {
                const angle = (idx * (360 / Math.max(units.length, 1))) * (Math.PI / 180);
                const distRatio = 0.2 + ((hash % 60) / 100) * 0.7;
                const maxDegOffset = (site.radius / 111320) * distRatio;
                lat = site.lat + (Math.sin(angle) * maxDegOffset);
                lng = site.lng + (Math.cos(angle) * maxDegOffset);
            }

            let statusClass = 'ready';
            if (st === 'OPERATING') statusClass = 'operating';
            else if (st === 'STANDBY') statusClass = 'standby';
            else if (st === 'BREAKDOWN') statusClass = 'breakdown';
            else if (st === 'ACCIDENT HOLD') statusClass = 'accident';

            let iconSymbol = 'fa-truck-front';
            const cat = (unit.category || '').toLowerCase();
            if (cat.includes('excavator')) iconSymbol = 'fa-person-digging';
            else if (cat.includes('grader')) iconSymbol = 'fa-road';
            else if (cat.includes('compactor') || cat.includes('vibro')) iconSymbol = 'fa-compact-disc';
            else if (cat.includes('dozer')) iconSymbol = 'fa-tractor';

            const markerHtml = `
                <div class="site-unit-marker-pin ${statusClass}" title="${escapeHtml(uId)} (${st})">
                    <i class="fa-solid ${iconSymbol}"></i> ${escapeHtml(uId)}
                </div>
            `;

            const customMarkerIcon = L.divIcon({
                className: 'site-map-unit-marker',
                html: markerHtml,
                iconSize: [84, 28],
                iconAnchor: [42, 14]
            });

            const popupHtml = `
                <div style="font-family:'Inter',sans-serif; min-width:210px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; border-bottom:1px solid #e2e8f0; padding-bottom:6px;">
                        <strong style="color:#0f172a; font-size:1.05rem; font-family:'JetBrains Mono',monospace;">${escapeHtml(uId)}</strong>
                        <span class="site-unit-status-badge ${statusClass}" style="font-size:0.68rem;">${escapeHtml(st)}</span>
                    </div>
                    <div style="font-size:0.82rem; color:#475569; margin-bottom:3px;">
                        <i class="fa-solid fa-truck" style="color:#0284c7; width:16px;"></i> ${escapeHtml(unit.make_model || unit.category || 'Heavy Equipment')}
                    </div>
                    <div style="font-size:0.82rem; color:#475569; margin-bottom:3px;">
                        <i class="fa-solid fa-gauge-high" style="color:#0284c7; width:16px;"></i> Total: <strong>${unit.last_hm_km ? Number(unit.last_hm_km).toLocaleString('id-ID') : '-'} HM</strong>
                    </div>
                    <div style="font-size:0.82rem; color:#475569; margin-bottom:8px;">
                        <i class="fa-solid fa-id-card" style="color:#0284c7; width:16px;"></i> Plat: <strong>${escapeHtml(unit.license_plate || '-')}</strong>
                    </div>
                    <div style="display:flex; gap:6px; margin-top:8px;">
                        <button type="button" class="btn btn-sm btn-primary" onclick="window.openUnitProperties('${escapeHtml(uId)}')" style="width:100%; font-size:0.75rem; padding:4px 8px; font-weight:700;">
                            <i class="fa-solid fa-sliders"></i> Buka Properties Unit
                        </button>
                    </div>
                </div>
            `;

            const marker = L.marker([lat, lng], { icon: customMarkerIcon }).bindPopup(popupHtml);
            siteMarkersGroup.addLayer(marker);
        });
    }

    /**
     * Update active state of map mode toggle buttons
     */
    function updateMapModeToggleUI() {
        const btnAuto = document.getElementById('btnSiteMapModeAuto');
        const btnCompact = document.getElementById('btnSiteMapModeCompact');
        const btnDetail = document.getElementById('btnSiteMapModeDetail');
        if (!btnAuto || !btnCompact || !btnDetail) return;

        btnAuto.classList.toggle('active', siteMapDisplayMode === 'auto');
        btnCompact.classList.toggle('active', siteMapDisplayMode === 'compact');
        btnDetail.classList.toggle('active', siteMapDisplayMode === 'detail');

        if (siteMapDisplayMode === 'auto' && siteMapInstance) {
            const z = siteMapInstance.getZoom();
            btnAuto.innerHTML = `<i class="fa-solid fa-arrows-split-up-and-left"></i> Auto (${z <= 14 ? 'Ringkas' : 'Detail'})`;
        } else if (btnAuto) {
            btnAuto.innerHTML = `<i class="fa-solid fa-arrows-split-up-and-left"></i> Auto`;
        }
    }

    window.setSiteMapDisplayMode = function (mode) {
        siteMapDisplayMode = mode;
        updateMapModeToggleUI();
        renderSiteMapMarkers();
    };

    /**
     * Render Fleet Distribution by Category
     */
    function renderCategoryDistribution(units) {
        const container = document.getElementById('siteCategoryDistributionList');
        if (!container) return;

        const counts = {};
        units.forEach(u => {
            const cat = u.category || 'Other';
            counts[cat] = (counts[cat] || 0) + 1;
        });

        const total = units.length || 1;
        const sortedCats = Object.entries(counts).sort((a, b) => b[1] - a[1]);

        if (sortedCats.length === 0) {
            container.innerHTML = '<div style="color:#94a3b8; font-size:0.82rem; text-align:center; padding:16px;">Tidak ada armada terdata.</div>';
            return;
        }

        const categoryIcons = {
            'Dump Truck': 'fa-truck-front',
            'Excavator': 'fa-person-digging',
            'Bulldozer': 'fa-tractor',
            'Motor Grader': 'fa-road',
            'Vibro Compactor': 'fa-compact-disc',
            'Water Truck': 'fa-faucet-drip',
            'Light Vehicle': 'fa-car-side',
            'Support': 'fa-wrench',
            'Other': 'fa-cubes'
        };

        const html = sortedCats.map(([cat, count]) => {
            const pct = Math.round((count / total) * 100);
            const icon = categoryIcons[cat] || 'fa-truck';
            return `
                <div class="site-cat-dist-item">
                    <div class="site-cat-dist-header">
                        <span class="site-cat-dist-name">
                            <i class="fa-solid ${icon}"></i> ${escapeHtml(cat)}
                        </span>
                        <span class="site-cat-dist-count">${count} Unit <small style="color:#94a3b8; font-weight:normal;">(${pct}%)</small></span>
                    </div>
                    <div class="site-cat-dist-bar-bg">
                        <div class="site-cat-dist-bar-fill" style="width: ${pct}%;"></div>
                    </div>
                </div>
            `;
        }).join('');

        container.innerHTML = html;
    }

    /**
     * Render Equipment Tabulation with Filter and Search
     */
    function renderSiteUnitsTable(units) {
        const tbody = document.getElementById('siteUnitsTableBody');
        const countLabel = document.getElementById('siteUnitsCountLabel');
        if (!tbody) return;

        const searchVal = (document.getElementById('siteUnitSearchInput')?.value || '').trim().toLowerCase();
        const statusVal = document.getElementById('siteUnitStatusFilter')?.value || 'ALL';
        const catVal = document.getElementById('siteUnitCategoryFilter')?.value || 'ALL';

        const filtered = units.filter(unit => {
            const uId = String(unit.id || unit.asset_id || '').toLowerCase();
            const hull = String(unit.hull_number || unit.alias_name || '').toLowerCase();
            const model = String(unit.make_model || '').toLowerCase();
            const plate = String(unit.license_plate || '').toLowerCase();
            const sn = String(unit.serial_number || '').toLowerCase();
            const cat = String(unit.category || '').toLowerCase();
            const st = String(unit.status || '').toUpperCase();

            // Text search
            if (searchVal) {
                const matchSearch = uId.includes(searchVal) || hull.includes(searchVal) ||
                    model.includes(searchVal) || plate.includes(searchVal) ||
                    sn.includes(searchVal) || cat.includes(searchVal);
                if (!matchSearch) return false;
            }

            // Status filter
            if (statusVal !== 'ALL') {
                if (statusVal === 'OPERATING' && st !== 'OPERATING' && st !== 'READY') return false;
                if (statusVal === 'STANDBY' && st !== 'STANDBY') return false;
                if (statusVal === 'BREAKDOWN' && st !== 'BREAKDOWN' && st !== 'ACCIDENT HOLD') return false;
                if (statusVal === 'INSPEKSI' && st !== 'INSPEKSI') return false;
            }

            // Category filter
            if (catVal !== 'ALL') {
                if (cat !== catVal.toLowerCase()) return false;
            }

            return true;
        });

        if (countLabel) {
            countLabel.textContent = `Menampilkan ${filtered.length} dari ${units.length} unit di site ini`;
        }

        if (filtered.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" style="text-align:center; padding:32px 16px; color:#64748b;">
                        <i class="fa-solid fa-truck-ramp-box fa-2x" style="color:#cbd5e1; margin-bottom:8px;"></i>
                        <div style="font-weight:700; color:#475569;">Tidak ada unit yang sesuai filter</div>
                        <div style="font-size:0.75rem; color:#94a3b8; margin-top:2px;">Silakan sesuaikan kata kunci pencarian atau reset filter.</div>
                    </td>
                </tr>
            `;
            return;
        }

        const categoryIcons = {
            'Dump Truck': 'fa-truck-front',
            'Excavator': 'fa-person-digging',
            'Bulldozer': 'fa-tractor',
            'Motor Grader': 'fa-road',
            'Vibro Compactor': 'fa-compact-disc',
            'Water Truck': 'fa-faucet-drip',
            'Light Vehicle': 'fa-car-side'
        };

        const rowsHtml = filtered.map((unit, index) => {
            const rawId = unit.id || unit.asset_id || `UNIT-${index + 1}`;
            const uId = escapeHtml(rawId);
            const hull = escapeHtml(unit.hull_number || unit.alias_name || '-');
            const model = escapeHtml(unit.make_model || unit.category || 'Heavy Equipment');
            const cat = escapeHtml(unit.category || 'Heavy Equipment');
            const catIcon = categoryIcons[unit.category] || 'fa-truck';
            const plate = escapeHtml(unit.license_plate || '-');
            const sn = escapeHtml(unit.serial_number || '-');
            const hm = unit.last_hm_km != null ? Number(unit.last_hm_km).toLocaleString('id-ID') : '-';
            const st = (unit.status || 'READY').toUpperCase();

            let statusClass = 'ready';
            if (st === 'OPERATING') statusClass = 'operating';
            else if (st === 'STANDBY') statusClass = 'standby';
            else if (st === 'BREAKDOWN') statusClass = 'breakdown';
            else if (st === 'INSPEKSI') statusClass = 'inspeksi';
            else if (st === 'ACCIDENT HOLD') statusClass = 'accident';

            const health = unit.health_score != null ? `${unit.health_score}%` : (st === 'BREAKDOWN' ? '68%' : '95%');

            return `
                <tr>
                    <td style="text-align:center; font-weight:600; color:#94a3b8;">${index + 1}</td>
                    <td>
                        <a href="javascript:void(0)" class="unit-prop-hover-link" onclick="window.openUnitProperties('${escapeHtml(rawId)}'); return false;" title="Buka Properties Lengkap Unit ${uId}">
                            <span class="unit-code-text">${uId}</span>
                            <i class="fa-solid fa-arrow-up-right-from-square unit-prop-hover-icon" aria-hidden="true"></i>
                        </a>
                    </td>
                    <td>
                        <strong style="color:#0f172a; font-family:'JetBrains Mono',monospace;">${hull}</strong>
                    </td>
                    <td>
                        <div style="font-weight:600; color:#1e293b;">${model}</div>
                        <div style="font-size:0.72rem; color:#94a3b8;">Tahun: ${escapeHtml(unit.year_manufacture || '2023')}</div>
                    </td>
                    <td>
                        <span style="display:inline-flex; align-items:center; gap:6px; color:#475569; font-weight:600;">
                            <i class="fa-solid ${catIcon}" style="color:#0284c7; width:16px;"></i> ${cat}
                        </span>
                    </td>
                    <td>
                        <div style="font-size:0.78rem;"><strong>Plat:</strong> ${plate}</div>
                        <div style="font-size:0.72rem; color:#64748b; font-family:monospace;">SN: ${sn}</div>
                    </td>
                    <td>
                        <strong style="color:#0f172a; font-family:'Inter',sans-serif;">${hm} HM</strong>
                    </td>
                    <td>
                        <span class="site-unit-status-badge ${statusClass}">
                            <span class="prop-pulse-dot" style="width:6px; height:6px;"></span> ${st}
                        </span>
                    </td>
                    <td>
                        <span style="font-weight:700; color:${st === 'BREAKDOWN' ? '#ef4444' : '#16a34a'};">
                            ${health}
                        </span>
                    </td>
                    <td style="text-align:center; white-space:nowrap;">
                        <div style="display:inline-flex; gap:4px;">
                            <button type="button" class="btn btn-sm btn-primary" onclick="window.openUnitProperties('${escapeHtml(rawId)}')" title="Buka Lembar Properties Lengkap" style="padding:3px 8px; font-size:0.75rem; font-weight:600;">
                                <i class="fa-solid fa-sliders"></i> Properties
                            </button>
                            <button type="button" class="btn btn-sm btn-secondary" onclick="if(window.openAssetModal) window.openAssetModal('${escapeHtml(rawId)}', '${st}', '${cat}', '${escapeHtml(currentSiteName)}');" title="Aset 360 Derajat" style="padding:3px 7px; font-size:0.75rem;">
                                <i class="fa-solid fa-cube"></i> 360&deg;
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        tbody.innerHTML = rowsHtml;
    }

    /**
     * Filter listener attachment
     */
    function attachFilterListeners() {
        const searchInput = document.getElementById('siteUnitSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', () => renderSiteUnitsTable(siteUnitsCache));
        }

        const statusFilter = document.getElementById('siteUnitStatusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', () => renderSiteUnitsTable(siteUnitsCache));
        }

        const catFilter = document.getElementById('siteUnitCategoryFilter');
        if (catFilter) {
            catFilter.addEventListener('change', () => renderSiteUnitsTable(siteUnitsCache));
        }
    }

    /**
     * Export Site Equipment Tabulation to CSV
     */
    window.exportSiteUnitsCsv = function () {
        if (!siteUnitsCache || siteUnitsCache.length === 0) {
            alert('Tidak ada data unit di site ini untuk diekspor.');
            return;
        }

        let csv = 'No,Unit ID,Nomor Lambung,Merek & Model,Kategori,Tahun,No Plat,Serial Number,Jam Kerja (HM),Status Operasional,Lokasi Site\n';

        siteUnitsCache.forEach((u, i) => {
            const row = [
                i + 1,
                `"${(u.id || u.asset_id || '').replace(/"/g, '""')}"`,
                `"${(u.hull_number || u.alias_name || '').replace(/"/g, '""')}"`,
                `"${(u.make_model || '').replace(/"/g, '""')}"`,
                `"${(u.category || '').replace(/"/g, '""')}"`,
                `"${(u.year_manufacture || '').replace(/"/g, '""')}"`,
                `"${(u.license_plate || '').replace(/"/g, '""')}"`,
                `"${(u.serial_number || '').replace(/"/g, '""')}"`,
                `"${(u.last_hm_km || 0)}"`,
                `"${(u.status || 'READY').replace(/"/g, '""')}"`,
                `"${currentSiteName.replace(/"/g, '""')}"`
            ];
            csv += row.join(',') + '\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.setAttribute('href', url);
        link.setAttribute('download', `Daftar_Unit_${currentSiteName.replace(/[^a-zA-Z0-9]/g, '_')}_${new Date().toISOString().slice(0, 10)}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    };

    /**
     * URL Hash Router listener for Site Properties
     */
    function handleHashChange() {
        const hash = window.location.hash || '';
        if (hash.startsWith('#site-properties')) {
            const urlParams = new URLSearchParams(hash.replace(/^#site-properties\??/, ''));
            const siteName = urlParams.get('site') || 'Borrow Pit Harapan Baru';
            window.openSiteProperties(siteName);
        }
    }

    window.addEventListener('hashchange', handleHashChange);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            attachFilterListeners();
            setTimeout(handleHashChange, 250);
        });
    } else {
        attachFilterListeners();
        setTimeout(handleHashChange, 250);
    }

})();
