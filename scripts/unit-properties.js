/**
 * ServicePlan-BRA - Heavy Equipment Properties & Diagnostics Inspector
 * Module: scripts/unit-properties.js (Unified Single-View Spec Sheet Layout)
 */

(function () {
    'use strict';

    let currentPropUnitId = null;
    let previousViewName = 'dashboard';
    let propMapInstance = null;

    // Mining Sites & Coordinates Reference (PT BRA Operational Sectors)
    const siteLocations = {
        'Borrow Pit Harapan Baru': { lat: 1.2789000, lng: 101.2112000, radius: 600, color: '#f59e0b' },
        'Yard KM 12 Duri': { lat: 1.2854600, lng: 101.2185700, radius: 450, color: '#0284c7' },
        'Minas Field Project': { lat: 0.7234000, lng: 101.4421000, radius: 800, color: '#16a34a' },
        'Site Celcin': { lat: 1.3012000, lng: 101.1984000, radius: 500, color: '#8b5cf6' },
        'WUR EW Project': { lat: 1.2645000, lng: 101.2341000, radius: 700, color: '#ec4899' },
        'Head Office Pekanbaru': { lat: 0.5071000, lng: 101.4478000, radius: 300, color: '#64748b' }
    };

    /**
     * Escape HTML string helper
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
     * Simple CRC32 hash for consistent mock variation
     */
    function crc32(str) {
        let hash = 0;
        for (let i = 0; i < (str || '').length; i++) {
            hash = ((hash << 5) - hash) + str.charCodeAt(i);
            hash |= 0;
        }
        return hash;
    }

    /**
     * Find asset in globalData or build fallback model
     */
    function getAssetData(assetId) {
        let asset = null;
        if (window.globalData && window.globalData.assets) {
            asset = window.globalData.assets.find(a => a.id === assetId || a.asset_id === assetId || a.asset_code === assetId);
        }

        const idStr = String(assetId || 'DT-00049').toUpperCase();
        const hash = Math.abs(crc32(idStr));

        if (!asset) {
            const isExcavator = idStr.startsWith('EX');
            const isGrader = idStr.startsWith('MG');
            const isCompactor = idStr.startsWith('CP') || idStr.startsWith('VB');
            const isDozer = idStr.startsWith('DZ');

            let cat = 'Dump Truck';
            let model = 'Hino Ranger FM 280 JD';
            if (isExcavator) { cat = 'Excavator'; model = 'Komatsu PC200-10M0 / CAT 320GX'; }
            else if (isGrader) { cat = 'Motor Grader'; model = 'Komatsu GD535-5'; }
            else if (isCompactor) { cat = 'Compactor / Vibro'; model = 'Sakai SV512 Single Drum'; }
            else if (isDozer) { cat = 'Bulldozer'; model = 'Komatsu D85ESS-2'; }

            asset = {
                id: assetId,
                asset_id: assetId,
                asset_code: assetId,
                serial_number: 'HINO-' + hash.toString(16).toUpperCase().padStart(8, '0'),
                license_plate: 'B ' + (9000 + (hash % 900)) + ' ZYT',
                category: cat,
                make_model: model,
                model: model,
                status: 'OPERATING',
                location: 'Borrow Pit Harapan Baru',
                last_hm_km: (32000 + (hash % 6000)),
                ownership: 'Milik Sendiri (PT BRA)',
                year_manufacture: 2023,
                branch: 'PKB PEKANBARU Branch',
                health_score: (90 + (hash % 8))
            };
        }

        return asset;
    }

    /**
     * Open Unit Properties View in Main Content (Unified Single-View)
     */
    window.openUnitProperties = function (assetId) {
        if (!assetId) return;
        currentPropUnitId = assetId;

        // Remember active view before switching
        const currentActiveSection = document.querySelector('.view-section.active');
        if (currentActiveSection && currentActiveSection.id !== 'view-unit-properties') {
            previousViewName = currentActiveSection.id.replace('view-', '');
        }

        // Hide other view-sections and show unit properties
        document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
        const propView = document.getElementById('view-unit-properties');
        if (propView) {
            propView.classList.add('active');
        }

        // Clear sidebar active states
        if (typeof window.clearActiveMenus === 'function') {
            window.clearActiveMenus();
        }

        // Scroll to top
        const content = document.querySelector('.content');
        if (content) content.scrollTop = 0;

        // Update URL hash for persistence
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', `#unit-properties?id=${encodeURIComponent(assetId)}`);
        }

        // Populate unit selector dropdown
        populateUnitSelector(assetId);

        // Render complete unified properties sheet
        renderUnitPropertiesData(assetId);
    };

    /**
     * Return back to previous view
     */
    window.backFromUnitProperties = function () {
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
     * Print Equipment Spec Sheet
     */
    window.printUnitProperties = function () {
        window.print();
    };

    /**
     * Populate Unit Selector Dropdown
     */
    function populateUnitSelector(selectedId) {
        const selectEl = document.getElementById('propUnitSelector');
        if (!selectEl) return;

        let options = '';
        if (window.globalData && window.globalData.assets && window.globalData.assets.length > 0) {
            window.globalData.assets.forEach(a => {
                const isSel = a.id === selectedId ? 'selected' : '';
                options += `<option value="${escapeHtml(a.id)}" ${isSel}>${escapeHtml(a.id)} - ${escapeHtml(a.category || 'Heavy Eq')} (${escapeHtml(a.location || 'Site')})</option>`;
            });
        } else {
            const defaults = ['DT-00049', 'DT-00050', 'DT-00051', 'DT-00052', 'EX-00010', 'MG-00003', 'VB-00004'];
            options = defaults.map(id => `<option value="${id}" ${id === selectedId ? 'selected' : ''}>${id} - Heavy Equipment</option>`).join('');
        }
        selectEl.innerHTML = options;
    }

    /**
     * Render Complete Unit Properties View (Unified Single Page)
     */
    function renderUnitPropertiesData(assetId) {
        const asset = getAssetData(assetId);
        const hash = Math.abs(crc32(assetId));

        // 1. Identity & Spec Header
        const unitIdEls = document.querySelectorAll('.prop-render-unit-id');
        unitIdEls.forEach(el => el.textContent = asset.id || assetId);

        const modelEl = document.getElementById('propUnitModel');
        if (modelEl) modelEl.textContent = asset.make_model || asset.model || 'Heavy Equipment';

        const plateEl = document.getElementById('propUnitPlate');
        if (plateEl) plateEl.textContent = asset.license_plate || ('B ' + (9000 + (hash % 900)) + ' ZYT');

        const serialEl = document.getElementById('propUnitSerial');
        if (serialEl) serialEl.textContent = asset.serial_number || ('SN-BRA-' + hash.toString(16).toUpperCase());

        const iconBox = document.getElementById('propCategoryIcon');
        if (iconBox) {
            const cat = (asset.category || '').toLowerCase();
            if (cat.includes('excavator')) iconBox.innerHTML = '<i class="fa-solid fa-person-digging"></i>';
            else if (cat.includes('grader')) iconBox.innerHTML = '<i class="fa-solid fa-road"></i>';
            else if (cat.includes('compactor') || cat.includes('vibro')) iconBox.innerHTML = '<i class="fa-solid fa-compact-disc"></i>';
            else if (cat.includes('dozer')) iconBox.innerHTML = '<i class="fa-solid fa-tractor"></i>';
            else iconBox.innerHTML = '<i class="fa-solid fa-truck-front"></i>';
        }

        // Status Pill with Pulse
        const statusPill = document.getElementById('propUnitStatusPill');
        if (statusPill) {
            const st = (asset.status || 'OPERATING').toUpperCase();
            let statusCls = 'operating';
            if (st === 'READY') statusCls = 'ready';
            else if (st === 'STANDBY') statusCls = 'standby';
            else if (st === 'BREAKDOWN') statusCls = 'breakdown';
            else if (st === 'ACCIDENT HOLD' || st.includes('ACCIDENT')) statusCls = 'accident';

            statusPill.className = `prop-status-pill ${statusCls}`;
            statusPill.innerHTML = `<span class="prop-pulse-dot"></span> ${escapeHtml(st)}`;
        }

        // Vitals
        const hmVal = asset.last_hm_km ? Number(asset.last_hm_km).toLocaleString('id-ID') : '34.500';
        const elVitalHm = document.getElementById('propVitalHm');
        if (elVitalHm) elVitalHm.textContent = `${hmVal} HM`;

        const elVitalLoc = document.getElementById('propVitalLoc');
        if (elVitalLoc) elVitalLoc.textContent = asset.location || 'Borrow Pit Harapan Baru';

        const elVitalHealth = document.getElementById('propVitalHealth');
        if (elVitalHealth) elVitalHealth.textContent = `${asset.health_score || (90 + (hash % 8))}%`;

        const elVitalNextService = document.getElementById('propVitalNextService');
        if (elVitalNextService) elVitalNextService.textContent = `PS ${500} (${(120 + (hash % 50))} HM lagi)`;

        // SECTION 1: Top Live Map & Telemetry CANbus
        renderTopLocationAndTelemetry(asset, hash);

        // SECTION 2: Technical Specifications Listing (Non-Card)
        renderTechnicalSpecsListing(asset, hash);

        // SECTION 3: Maintenance Plans, WOs & Spare Parts
        renderMaintenanceAndWorkOrders(asset, hash);

        // SECTION 4: Diagnostics, 10-Tire Diagram, Oil PAP & P2H
        renderDiagnosticsAndTires(asset, hash);
    }

    /**
     * Render SECTION 1: Top Live Map & Telemetry CANbus
     */
    function renderTopLocationAndTelemetry(asset, hash) {
        const locName = asset.location || 'Borrow Pit Harapan Baru';
        const siteRef = siteLocations[locName] || siteLocations['Borrow Pit Harapan Baru'];
        const jitter = (hash % 100) * 0.0002;
        const currentLat = siteRef.lat + jitter;
        const currentLng = siteRef.lng + (jitter * 0.7);

        // Initialize / Update Leaflet Map
        setTimeout(() => {
            initPropertiesMap(currentLat, currentLng, locName, asset);
        }, 100);

        // Telemetry Live Gauges Values
        const isOperating = (asset.status || '').toUpperCase() === 'OPERATING';
        const rpm = isOperating ? (1500 + (hash % 350)) : (asset.status === 'STANDBY' ? 750 : 0);
        const coolant = isOperating ? (82 + (hash % 6)) : 34;
        const oilPress = isOperating ? (3.8 + ((hash % 10) * 0.05)).toFixed(1) : '0.0';
        const volt = (27.2 + ((hash % 8) * 0.1)).toFixed(1);

        const elRpm = document.getElementById('propGaugeRpm');
        if (elRpm) elRpm.textContent = rpm;

        const elCoolant = document.getElementById('propGaugeCoolant');
        if (elCoolant) elCoolant.textContent = coolant;

        const elOilPress = document.getElementById('propGaugeOilPress');
        if (elOilPress) elOilPress.textContent = oilPress;

        const elVolt = document.getElementById('propGaugeVolt');
        if (elVolt) elVolt.textContent = volt;

        // GPS Breadcrumb Logs Table
        const tbGps = document.getElementById('propGpsLogsTableBody');
        if (tbGps) {
            let rows = '';
            for (let i = 0; i < 4; i++) {
                const d = new Date(Date.now() - (i * 900000));
                const timeStr = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                const lat = (currentLat - (i * 0.0005)).toFixed(6);
                const lng = (currentLng - (i * 0.0004)).toFixed(6);
                const speed = i === 0 ? (asset.status === 'OPERATING' ? '26.4 km/h' : '0.0 km/h') : `${20 + (i * 3)} km/h`;
                const ign = i === 0 ? (asset.status === 'OPERATING' ? '<span class="p2h-badge pass">ON</span>' : '<span class="p2h-badge warning">IDLING</span>') : '<span class="p2h-badge pass">ON</span>';

                rows += `
                    <tr>
                        <td><strong>${timeStr}</strong></td>
                        <td>${lat}, ${lng}</td>
                        <td>${speed}</td>
                        <td>${ign}</td>
                    </tr>
                `;
            }
            tbGps.innerHTML = rows;
        }

        // BAST Movements History Table
        const tbMov = document.getElementById('propMovementTableBody');
        if (tbMov) {
            tbMov.innerHTML = `
                <tr>
                    <td><strong>BAST/MUT/2026/08/042</strong></td>
                    <td>14 Agu 2026</td>
                    <td>Yard KM 12 &rarr; <strong>${escapeHtml(locName)}</strong></td>
                    <td>Mobilisasi unit untuk overburden hauling.</td>
                    <td><span class="p2h-badge pass">Approved</span></td>
                </tr>
            `;
        }
    }

    /**
     * Render SECTION 2: Technical Specifications Listing (Non-Card Format)
     */
    function renderTechnicalSpecsListing(asset, hash) {
        const cat = (asset.category || '').toLowerCase();
        let engine = 'Hino J08E-WD Turbo Intercooler (280 PS / 2.100 RPM)';
        let trans = 'Eaton 9-Speed Manual w/ Hi-Lo Splitter';
        let payload = '26.000 KG (Gross Vehicle Weight)';
        let oilCap = '28 Liter (Engine Oil 15W-40)';
        let fuelCap = '200 Liter High Grade Solar';
        let drive = '6x4 Rigid Heavy Duty Axle';

        if (cat.includes('excavator')) {
            engine = 'Komatsu SAA6D107E-1 Turbocharged Diesel (148 HP / 2.000 RPM)';
            trans = 'Dual Variable Displacement Axial Piston Hydraulic';
            payload = '0.93 m³ Bucket Capacity / 20.5 Ton Operating Class';
            oilCap = '23.1 Liter Engine / 135 Liter Hydraulic Tank';
            fuelCap = '400 Liter Solar';
            drive = 'Full Hydrostatic Crawler Track 600mm Triple Grouser';
        } else if (cat.includes('grader')) {
            engine = 'Komatsu SAA6D107E-1 (145 HP / 2.000 RPM)';
            trans = 'Direct Drive Power Shift 8 Forward - 4 Reverse';
            payload = 'Blade Width 3.71 Meter / Operating Weight 14.5 Ton';
            oilCap = '20 Liter Engine / 70 Liter Hydraulic';
            fuelCap = '340 Liter Solar';
            drive = '6x4 Tandem Drive with Differential Lock';
        }

        const listEl = document.getElementById('propSpecListGrid');
        if (listEl) {
            listEl.innerHTML = `
                <!-- ROW 1 (White) -->
                <div class="prop-spec-row">
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-truck"></i> Tipe &amp; Kategori Alat</div>
                        <div class="prop-spec-val">${escapeHtml(asset.category || 'Heavy Equipment')}</div>
                    </div>
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-gears"></i> Model Mesin (Powertrain)</div>
                        <div class="prop-spec-val">${escapeHtml(engine)}</div>
                    </div>
                </div>

                <!-- ROW 2 (Grey Striped) -->
                <div class="prop-spec-row">
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-code-branch"></i> Transmisi &amp; Drive Train</div>
                        <div class="prop-spec-val">${escapeHtml(trans)}</div>
                    </div>
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-weight-hanging"></i> Kapasitas Muatan / GVW</div>
                        <div class="prop-spec-val">${escapeHtml(payload)}</div>
                    </div>
                </div>

                <!-- ROW 3 (White) -->
                <div class="prop-spec-row">
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-road"></i> Konfigurasi Penggerak / Drive</div>
                        <div class="prop-spec-val">${escapeHtml(drive)}</div>
                    </div>
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-oil-can"></i> Kapasitas Pelumas &amp; Hidrolik</div>
                        <div class="prop-spec-val">${escapeHtml(oilCap)}</div>
                    </div>
                </div>

                <!-- ROW 4 (Grey Striped) -->
                <div class="prop-spec-row">
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-gas-pump"></i> Kapasitas Tangki Bahan Bakar</div>
                        <div class="prop-spec-val">${escapeHtml(fuelCap)}</div>
                    </div>
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-shield-halved"></i> Status Kepemilikan</div>
                        <div class="prop-spec-val">${escapeHtml(asset.ownership || 'Milik Sendiri (PT BRA)')}</div>
                    </div>
                </div>

                <!-- ROW 5 (White) -->
                <div class="prop-spec-row">
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-building-flag"></i> Cabang &amp; Wilayah Operasi</div>
                        <div class="prop-spec-val">${escapeHtml(asset.branch || 'PKB PEKANBARU Branch')}</div>
                    </div>
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-calendar-check"></i> Tahun Pembuatan Unit</div>
                        <div class="prop-spec-val">${escapeHtml(asset.year_manufacture || '2023')}</div>
                    </div>
                </div>

                <!-- ROW 6 (Grey Striped) -->
                <div class="prop-spec-row">
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-heart-pulse"></i> Mean Time Between Failures (MTBF)</div>
                        <div class="prop-spec-val">168 Jam Operasi Normal</div>
                    </div>
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-stopwatch"></i> Mean Time to Repair (MTTR)</div>
                        <div class="prop-spec-val">4.5 Jam Perbaikan Rata-rata</div>
                    </div>
                </div>
            `;
        }
    }

    /**
     * Leaflet Map Initializer for Properties
     */
    function initPropertiesMap(lat, lng, locName, asset) {
        const mapContainer = document.getElementById('propUnitMap');
        if (!mapContainer || typeof window.L === 'undefined') return;

        if (propMapInstance) {
            try {
                propMapInstance.remove();
            } catch (e) {
                console.warn('Map cleanup warning:', e);
            }
            propMapInstance = null;
        }

        try {
            propMapInstance = L.map('propUnitMap', {
                zoomControl: true,
                attributionControl: false
            }).setView([lat, lng], 14);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19
            }).addTo(propMapInstance);

            // Add Mining Site Geofences
            Object.entries(siteLocations).forEach(([name, data]) => {
                const isCurrent = name === locName;
                L.circle([data.lat, data.lng], {
                    color: data.color,
                    fillColor: data.color,
                    fillOpacity: isCurrent ? 0.25 : 0.1,
                    radius: data.radius,
                    weight: isCurrent ? 3 : 1.5,
                    dashArray: isCurrent ? null : '4, 4'
                }).bindPopup(`<strong>Geofence Site: ${name}</strong><br>Radius: ${data.radius} Meter`).addTo(propMapInstance);
            });

            // Add GPS Trail Breadcrumbs Polyline
            const trailPoints = [
                [lat - 0.0035, lng - 0.0028],
                [lat - 0.0022, lng - 0.0019],
                [lat - 0.0012, lng - 0.0009],
                [lat, lng]
            ];
            L.polyline(trailPoints, {
                color: '#0284c7',
                weight: 4,
                opacity: 0.8,
                dashArray: '6, 6'
            }).addTo(propMapInstance);

            // Marker for Current Unit Position
            const customIcon = L.divIcon({
                className: 'prop-map-unit-marker',
                html: `
                    <div style="background:#0284c7; color:#fff; padding:4px 8px; border-radius:6px; font-weight:800; font-size:11px; font-family:'JetBrains Mono',monospace; box-shadow:0 3px 8px rgba(0,0,0,0.3); border:2px solid #fff; white-space:nowrap; display:flex; align-items:center; gap:4px;">
                        <i class="fa-solid fa-truck-front"></i> ${escapeHtml(asset.id || 'UNIT')}
                    </div>
                `,
                iconSize: [80, 30],
                iconAnchor: [40, 15]
            });

            L.marker([lat, lng], { icon: customIcon })
                .bindPopup(`<strong>${escapeHtml(asset.id)}</strong><br>${escapeHtml(asset.make_model || '')}<br>Lokasi: ${escapeHtml(locName)}`)
                .addTo(propMapInstance)
                .openPopup();

            setTimeout(() => {
                if (propMapInstance) propMapInstance.invalidateSize();
            }, 200);
        } catch (err) {
            console.warn('Leaflet initialization exception:', err);
        }
    }

    /**
     * Render SECTION 3: Maintenance Plans, WOs & Spare Parts
     */
    function renderMaintenanceAndWorkOrders(asset, hash) {
        const curHm = Number(asset.last_hm_km || 34500);

        // PM Plans Table
        const tbPm = document.getElementById('propPmPlansTableBody');
        if (tbPm) {
            const intervals = [250, 500, 1000, 2000];
            let rows = '';
            intervals.forEach(inv => {
                const last = Math.floor(curHm / inv) * inv;
                const next = last + inv;
                const delta = next - curHm;
                const badge = delta < 0 ? '<span class="p2h-badge fail">Terlambat</span>' : (delta <= 50 ? '<span class="p2h-badge warning">Due Soon</span>' : '<span class="p2h-badge pass">Terjadwal</span>');
                const cost = inv === 250 ? 'Rp 2.500.000' : (inv === 500 ? 'Rp 4.850.000' : (inv === 1000 ? 'Rp 9.500.000' : 'Rp 16.000.000'));

                rows += `
                    <tr>
                        <td><strong>PS ${inv}</strong></td>
                        <td>${inv} Jam</td>
                        <td>${last.toLocaleString('id-ID')} HM</td>
                        <td><strong>${next.toLocaleString('id-ID')} HM</strong></td>
                        <td style="font-weight:700; color:${delta < 0 ? '#dc2626' : '#0284c7'};">${delta > 0 ? '+' : ''}${delta} HM</td>
                        <td>${badge}</td>
                        <td>${cost}</td>
                    </tr>
                `;
            });
            tbPm.innerHTML = rows;
        }

        // Work Orders Table
        const tbWo = document.getElementById('propWoTableBody');
        if (tbWo) {
            tbWo.innerHTML = `
                <tr>
                    <td><strong>WO-2026-08-0114</strong></td>
                    <td>Scheduled PM</td>
                    <td>Servis Berkala PS 500 (Ganti Oli Mesin, Filter Oli, Filter Solar, dan General Safety Check)</td>
                    <td>Joni Septian</td>
                    <td>5.5 Jam</td>
                    <td>Rp 4.850.000</td>
                    <td><span class="p2h-badge pass">Closed</span></td>
                </tr>
                <tr>
                    <td><strong>WO-2026-07-0092</strong></td>
                    <td>Corrective Breakdown</td>
                    <td>Penggantian hydraulic hose boom cylinder bocor dan bleeding hydraulic circuit</td>
                    <td>Afriyandi (Lead)</td>
                    <td>14.0 Jam</td>
                    <td>Rp 7.200.000</td>
                    <td><span class="p2h-badge pass">Closed</span></td>
                </tr>
            `;
        }

        // Spare Parts History Table
        const tbParts = document.getElementById('propPartsTableBody');
        if (tbParts) {
            tbParts.innerHTML = `
                <tr>
                    <td><code>15607-2190</code></td>
                    <td>Oil Filter Element Hino FM260/280</td>
                    <td>Filter</td>
                    <td>2 Pcs</td>
                    <td>Rp 570.000</td>
                    <td>SPB-2026-08-0114</td>
                    <td>10 Agu 2026</td>
                </tr>
                <tr>
                    <td><code>23304-EV090</code></td>
                    <td>Fuel Filter Primary & Secondary Set</td>
                    <td>Filter</td>
                    <td>1 Set</td>
                    <td>Rp 450.000</td>
                    <td>SPB-2026-08-0114</td>
                    <td>10 Agu 2026</td>
                </tr>
                <tr>
                    <td><code>OIL-MED-15W40</code></td>
                    <td>Oli Mesin Pertamina Meditran SX 15W-40</td>
                    <td>Pelumas</td>
                    <td>28 Liter</td>
                    <td>Rp 1.456.000</td>
                    <td>SPB-2026-08-0115</td>
                    <td>10 Agu 2026</td>
                </tr>
                <tr>
                    <td><code>HYD-HOSE-075-4W</code></td>
                    <td>Hydraulic Hose 3/4" 4SP 350 Bar + Fitting</td>
                    <td>Hidrolik</td>
                    <td>2 Pcs</td>
                    <td>Rp 3.700.000</td>
                    <td>SPB-2026-07-0092</td>
                    <td>24 Jul 2026</td>
                </tr>
            `;
        }
    }

    /**
     * Render SECTION 4: Diagnostics, 10-Tire Diagram, Oil PAP & P2H
     */
    function renderDiagnosticsAndTires(asset, hash) {
        // 10-Position Tire Blueprint Diagram
        const tireBlueprint = document.getElementById('propTireBlueprint');
        if (tireBlueprint) {
            tireBlueprint.innerHTML = `
                <!-- AXLE 1: FRONT STEERING -->
                <div class="prop-tire-axle-row">
                    <div class="prop-axle-line"></div>
                    <div class="prop-axle-label">AXLE 1 (STEERING)</div>
                    <div class="prop-wheel-group">
                        <div class="prop-tire-badge good" title="Posisi 1 - Depan Kiri">
                            <span class="tire-pos">P1 (FL)</span>
                            <span class="tire-psi">115 PSI</span>
                            <span class="tire-rtd">19 mm</span>
                        </div>
                    </div>
                    <div class="prop-wheel-group">
                        <div class="prop-tire-badge good" title="Posisi 2 - Depan Kanan">
                            <span class="tire-pos">P2 (FR)</span>
                            <span class="tire-psi">115 PSI</span>
                            <span class="tire-rtd">18 mm</span>
                        </div>
                    </div>
                </div>

                <!-- AXLE 2: DRIVE FORWARD (DUAL TIRE) -->
                <div class="prop-tire-axle-row">
                    <div class="prop-axle-line"></div>
                    <div class="prop-axle-label">AXLE 2 (DRIVE FORWARD)</div>
                    <div class="prop-wheel-group">
                        <div class="prop-tire-badge good" title="Posisi 3 - Tengah Luar Kiri">
                            <span class="tire-pos">P3 (RO)</span>
                            <span class="tire-psi">110 PSI</span>
                            <span class="tire-rtd">16 mm</span>
                        </div>
                        <div class="prop-tire-badge good" title="Posisi 4 - Tengah Dalam Kiri">
                            <span class="tire-pos">P4 (RI)</span>
                            <span class="tire-psi">112 PSI</span>
                            <span class="tire-rtd">15 mm</span>
                        </div>
                    </div>
                    <div class="prop-wheel-group">
                        <div class="prop-tire-badge warning" title="Posisi 5 - Tengah Dalam Kanan (Perlu Pantau)">
                            <span class="tire-pos">P5 (RI)</span>
                            <span class="tire-psi">102 PSI</span>
                            <span class="tire-rtd">13 mm</span>
                        </div>
                        <div class="prop-tire-badge good" title="Posisi 6 - Tengah Luar Kanan">
                            <span class="tire-pos">P6 (RO)</span>
                            <span class="tire-psi">110 PSI</span>
                            <span class="tire-rtd">16 mm</span>
                        </div>
                    </div>
                </div>

                <!-- AXLE 3: DRIVE REAR (DUAL TIRE) -->
                <div class="prop-tire-axle-row">
                    <div class="prop-axle-line"></div>
                    <div class="prop-axle-label">AXLE 3 (DRIVE REAR)</div>
                    <div class="prop-wheel-group">
                        <div class="prop-tire-badge good" title="Posisi 7 - Belakang Luar Kiri">
                            <span class="tire-pos">P7 (RO)</span>
                            <span class="tire-psi">114 PSI</span>
                            <span class="tire-rtd">17 mm</span>
                        </div>
                        <div class="prop-tire-badge good" title="Posisi 8 - Belakang Dalam Kiri">
                            <span class="tire-pos">P8 (RI)</span>
                            <span class="tire-psi">115 PSI</span>
                            <span class="tire-rtd">17 mm</span>
                        </div>
                    </div>
                    <div class="prop-wheel-group">
                        <div class="prop-tire-badge good" title="Posisi 9 - Belakang Dalam Kanan">
                            <span class="tire-pos">P9 (RI)</span>
                            <span class="tire-psi">112 PSI</span>
                            <span class="tire-rtd">16 mm</span>
                        </div>
                        <div class="prop-tire-badge good" title="Posisi 10 - Belakang Luar Kanan">
                            <span class="tire-pos">P10 (RO)</span>
                            <span class="tire-psi">114 PSI</span>
                            <span class="tire-rtd">18 mm</span>
                        </div>
                    </div>
                </div>
            `;
        }

        // Oil PAP Sampling Lab Table
        const tbPap = document.getElementById('propOilPapTableBody');
        if (tbPap) {
            tbPap.innerHTML = `
                <tr>
                    <td><strong>Engine Oil</strong></td>
                    <td>10 Agu 2026</td>
                    <td>18 ppm</td>
                    <td>4 ppm</td>
                    <td>6 ppm</td>
                    <td>8 ppm</td>
                    <td>14.2 cSt</td>
                    <td><span class="p2h-badge pass">Normal</span></td>
                    <td>Laju keausan wear metals dalam batas aman operasional.</td>
                </tr>
                <tr>
                    <td><strong>Hydraulic System</strong></td>
                    <td>10 Agu 2026</td>
                    <td>7 ppm</td>
                    <td>2 ppm</td>
                    <td>3 ppm</td>
                    <td>5 ppm</td>
                    <td>45.8 cSt</td>
                    <td><span class="p2h-badge pass">Normal</span></td>
                    <td>Viskositas stabil. Kontaminasi partikulat sangat rendah.</td>
                </tr>
                <tr>
                    <td><strong>Differential Gear</strong></td>
                    <td>18 Jun 2026</td>
                    <td>24 ppm</td>
                    <td>8 ppm</td>
                    <td>5 ppm</td>
                    <td>11 ppm</td>
                    <td>28.4 cSt</td>
                    <td><span class="p2h-badge pass">Normal</span></td>
                    <td>Keausan gear train normal sesuai interval 1000 HM.</td>
                </tr>
            `;
        }

        // P2H Pre-Start History Table
        const tbP2h = document.getElementById('propP2hTableBody');
        if (tbP2h) {
            tbP2h.innerHTML = `
                <tr>
                    <td><strong>27 Agu 2026</strong></td>
                    <td>Shift 1 (Siang)</td>
                    <td>Taufiq H (Operator)</td>
                    <td><span class="p2h-badge pass">Lulus P2H</span></td>
                    <td>OK (110-115 PSI)</td>
                    <td>Nihil</td>
                    <td>Unit siap operasi normal penambangan.</td>
                </tr>
                <tr>
                    <td><strong>26 Agu 2026</strong></td>
                    <td>Shift 2 (Malam)</td>
                    <td>Rezeki Siregar (Operator)</td>
                    <td><span class="p2h-badge pass">Lulus P2H</span></td>
                    <td>OK</td>
                    <td>Nihil</td>
                    <td>Operasi hauling lancar tanpa kendala.</td>
                </tr>
                <tr>
                    <td><strong>25 Agu 2026</strong></td>
                    <td>Shift 1 (Siang)</td>
                    <td>Taufiq H (Operator)</td>
                    <td><span class="p2h-badge warning">Catatan</span></td>
                    <td>P5 agak kurang angin (102 PSI)</td>
                    <td>Nihil</td>
                    <td>Dilakukan top up angin ban P5 saat pergantian shift.</td>
                </tr>
            `;
        }

        // Accident Investigation Table
        const tbAccident = document.getElementById('propAccidentTableBody');
        if (tbAccident) {
            if (asset.id === 'DT-00052') {
                tbAccident.innerHTML = `
                    <tr>
                        <td><strong>02/ACC/BRA/YARD/2026</strong></td>
                        <td>20 Jun 2026</td>
                        <td>Yard KM 12 Area Workshop</td>
                        <td>Suwardi (Mekanik)</td>
                        <td><span class="p2h-badge fail">Minor</span></td>
                        <td>Engsel pintu ombeng tersangkut tiang penyangga saat manuver mundur.</td>
                        <td>Rp 4.500.000</td>
                        <td><span class="p2h-badge pass">CAPA Completed</span></td>
                    </tr>
                `;
            } else {
                tbAccident.innerHTML = `
                    <tr>
                        <td colspan="8" style="text-align:center; padding:18px; color:#64748b;">
                            <i class="fa-solid fa-shield-halved" style="color:#16a34a; margin-right:6px;"></i> 
                            Tidak ada riwayat kecelakaan atau insiden keselamatan untuk unit ini (Zero Accident).
                        </td>
                    </tr>
                `;
            }
        }
    }

    /**
     * Hash Router Initializer
     */
    function handleHashChange() {
        const hash = window.location.hash || '';
        if (hash.startsWith('#unit-properties')) {
            const urlParams = new URLSearchParams(hash.replace(/^#unit-properties\??/, ''));
            const unitId = urlParams.get('id') || 'DT-00049';
            window.openUnitProperties(unitId);
        }
    }

    window.addEventListener('hashchange', handleHashChange);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(handleHashChange, 200);
        });
    } else {
        setTimeout(handleHashChange, 200);
    }

})();
