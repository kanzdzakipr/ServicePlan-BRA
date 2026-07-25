(function () {
    'use strict';

    const pmPlans = [
        { no: 1, code: 'PF 05', id: 'PF-00001', asset: 'Vibro Bomag Pad Foot Compactor BW 211D-40SL', year: 2024, warranty: 'No Warranty', current: 1894.2, tracking: '2026-06-30', interval: 500, last: 1509, lastDate: '2026-03-30', target: 2009, completed: true, actual: 2036.4, actualDate: '2026-07-22' },
        { no: 2, code: 'SD 02', id: 'SD-00001', asset: 'Vibro Bomag Smooth Drum Compactor BW 211D-40SL', year: 2020, warranty: 'No Warranty', current: 6365, tracking: '2026-06-22', interval: 500, last: 6045, lastDate: '2026-04-24', target: 6545, completed: true, actual: 6532.2, actualDate: '2026-07-20' },
        { no: 3, code: 'GR 5', id: 'MG-00004', asset: 'Motor Grader XCMG GR135 MAX', year: 2025, warranty: 'No Warranty', current: 2446.1, tracking: '2026-07-02', interval: 500, last: 2051.3, lastDate: '2026-04-27', target: 2551.3, completed: true, actual: null, actualDate: null, note: 'Ditandai realisasi pada sumber, tetapi HM/KM dan tanggal PM kosong.' },
        { no: 4, code: 'Dozer 08', id: 'DZ-00001', asset: 'Bulldozer Komatsu D85ESS-2', year: 2024, warranty: 'No Warranty', current: 2465.9, tracking: '2026-07-01', interval: 500, last: 2066, lastDate: '2026-03-05', target: 2566, completed: true, actual: 25781, actualDate: '2026-07-22', note: 'Realisasi 25.781 perlu verifikasi; tidak konsisten dengan tracking dan target sekitar 2.500.' },
        { no: 5, code: 'Dozer 03', id: 'DZ-00002', asset: 'Bulldozer Caterpillar D6G 2XL', year: 2024, warranty: 'No Warranty', current: 2177, tracking: '2026-06-30', interval: 500, last: 1709, lastDate: '2025-11-03', target: 2209, completed: true, actual: 2294.3, actualDate: '2026-07-21' },
        { no: 6, code: 'Exca 24', id: 'EXC-00001', asset: 'Excavator Komatsu PC200-10M0 CE', year: 2024, warranty: 'Warranty - 4000', current: 2443, tracking: '2026-06-24', interval: 500, last: 2001, lastDate: '2026-04-11', target: 2501, completed: false },
        { no: 7, code: 'Exca 01', id: 'EXC-00008', asset: 'Excavator Komatsu PC210-10M0', year: 2025, warranty: 'Warranty - 10000', current: 10537.3, tracking: '2026-07-01', interval: 500, last: 10154, lastDate: '2026-04-13', target: 10654, completed: false },
        { no: 8, code: 'EXCA-29', id: 'EXC-00009', asset: 'Excavator Komatsu PC200-10M0 CE', year: 2025, warranty: 'Warranty - 1000', current: 1436.9, tracking: '2026-07-01', interval: 500, last: 1117.3, lastDate: '2026-04-16', target: 1617.3, completed: true, actual: 1451.3, actualDate: '2026-07-03', note: 'Warranty UT.' },
        { no: 9, code: 'Exca BRA-23', id: 'EXC-00012', asset: 'Excavator Komatsu PC200-10M0 CE', year: 2024, warranty: 'No Warranty', current: 5970, tracking: '2026-06-27', interval: 500, last: 5532.3, lastDate: '2025-04-07', target: 6032.3, completed: false },
        { no: 10, code: 'DT 055', id: 'DT-00001', asset: 'Dump Truck Hino Ranger FM 280 JD', year: 2024, warranty: 'No Warranty', current: 37720, tracking: '2026-06-30', interval: 10000, last: 29118, lastDate: '2026-03-04', target: 39118, completed: false },
        { no: 11, code: 'DT 058', id: 'DT-00004', asset: 'Dump Truck Hino Ranger FM 280 JD', year: 2024, warranty: 'No Warranty', current: 38146, tracking: '2026-06-30', interval: 10000, last: 30066, lastDate: '2026-02-12', target: 40066, completed: true, actual: 40031, actualDate: '2026-07-11' },
        { no: 12, code: 'DT 061', id: 'DT-00007', asset: 'Dump Truck Hino Ranger FM 280 JD', year: 2024, warranty: 'No Warranty', current: 37555, tracking: '2026-06-26', interval: 10000, last: 28742, lastDate: '2026-03-06', target: 38742, completed: false },
        { no: 13, code: 'DT 064', id: 'DT-00010', asset: 'Dump Truck Hino Ranger FM 280 JD', year: 2024, warranty: 'No Warranty', current: 39146, tracking: '2026-06-27', interval: 10000, last: 19946, lastDate: '2025-11-29', target: 29946, completed: true, actual: 40346, actualDate: '2026-07-10', note: 'Catatan service di KM 30 ribu tidak tersedia.' },
        { no: 14, code: 'DT 067', id: 'DT-00013', asset: 'Dump Truck Hino Ranger FM 280 JD', year: 2024, warranty: 'No Warranty', current: 35397, tracking: '2026-05-18', interval: 10000, last: 19725, lastDate: '2025-11-04', target: 29725, completed: false, note: 'Histori service KM 30 ribu dan informasi KM operator tidak tersedia.' },
        { no: 15, code: 'DT 071', id: 'DT-00017', asset: 'Dump Truck Hino Ranger FM 280 JD', year: 2024, warranty: 'No Warranty', current: 39890, tracking: '2026-06-30', interval: 10000, last: 29966, lastDate: '2026-02-25', target: 39966, completed: true, actual: 40906, actualDate: '2026-07-07' },
        { no: 16, code: 'DT 072', id: 'DT-00018', asset: 'Dump Truck Hino Ranger FM 280 JD', year: 2024, warranty: 'No Warranty', current: 38522, tracking: '2026-06-27', interval: 10000, last: 29753, lastDate: '2026-03-04', target: 39753, completed: false },
        { no: 17, code: 'DT 075', id: 'DT-00021', asset: 'Dump Truck Hino Ranger FM 280 JD', year: 2024, warranty: 'No Warranty', current: 38348, tracking: '2026-07-07', interval: 10000, last: 19903, lastDate: '2025-11-18', target: 29903, completed: true, actual: 38348, actualDate: '2026-07-07' },
        { no: 18, code: 'DT 079', id: 'DT-00025', asset: 'Dump Truck Hino Ranger FM 280 JD', year: 2024, warranty: 'No Warranty', current: 38756, tracking: '2026-06-30', interval: 10000, last: 18932, lastDate: '2025-11-03', target: 28932, completed: false, note: 'Catatan service di KM 30 ribu tidak tersedia.' },
        { no: 19, code: 'DT 080', id: 'DT-00026', asset: 'Dump Truck Hino Ranger FM 280 JD', year: 2024, warranty: 'No Warranty', current: 35140, tracking: '2026-05-18', interval: 10000, last: 20128, lastDate: '2025-11-18', target: 30128, completed: false, note: 'Catatan service di KM 30 ribu tidak tersedia.' },
        { no: 20, code: 'DT 081', id: 'DT-00027', asset: 'Dump Truck Hino Ranger FM 280 JD', year: 2024, warranty: 'No Warranty', current: 37984, tracking: '2026-06-30', interval: 10000, last: 20665, lastDate: '2025-11-27', target: 30665, completed: false, note: 'Catatan service di KM 30 ribu tidak tersedia.' },
        { no: 21, code: 'DT 038', id: 'DT-00041', asset: 'Dump Truck Hino Ranger FM 280 JD', year: 2023, warranty: 'No Warranty', current: 77131, tracking: '2026-06-26', interval: 10000, last: 67729, lastDate: '2026-02-24', target: 77729, completed: true, actual: 77506, actualDate: '2026-07-08' },
        { no: 22, code: 'DT 039', id: 'DT-00042', asset: 'Dump Truck Hino Ranger FM 280 JD', year: 2023, warranty: 'No Warranty', current: 70935, tracking: '2026-06-30', interval: 10000, last: 62804, lastDate: '2025-12-07', target: 72804, completed: false },
        { no: 23, code: 'DT 041', id: 'DT-00044', asset: 'Dump Truck Hino Ranger FM 280 JD', year: 2023, warranty: 'No Warranty', current: 77071, tracking: '2026-06-30', interval: 10000, last: 68506, lastDate: '2026-03-13', target: 78506, completed: false },
        { no: 24, code: 'DT 042', id: 'DT-00045', asset: 'Dump Truck Hino Ranger FM 280 JD', year: 2023, warranty: 'No Warranty', current: 74329, tracking: '2026-06-30', interval: 10000, last: 65199, lastDate: '2026-03-02', target: 75199, completed: true, actual: 75177, actualDate: '2026-07-08' },
        { no: 25, code: 'DT 054', id: 'DT-00062', asset: 'Dump Truck Hino Ranger FM 260 JD', year: 2019, warranty: 'No Warranty', current: 79494, tracking: '2026-06-27', interval: 7500, last: 74306, lastDate: '2026-04-30', target: 81806, completed: false },
        { no: 26, code: 'DT 104', id: 'DT-04025', asset: 'Dump Truck Hino Ranger FM 280 JD', year: 2022, warranty: 'No Warranty', current: 98230, tracking: '2026-06-30', interval: 10000, last: 90672, lastDate: '2026-02-23', target: 100672, completed: false },
        { no: 27, code: 'DT 101', id: 'DT-04036', asset: 'Dump Truck Hino Ranger FM 280 JD', year: 2022, warranty: 'No Warranty', current: 91068, tracking: '2026-07-23', interval: 10000, last: 80414, lastDate: '2026-02-08', target: 90414, completed: false },
        { no: 28, code: 'DT 093', id: 'DT-04040', asset: 'Dump Truck Hino Ranger FM 280 JD', year: 2022, warranty: 'No Warranty', current: 92939, tracking: '2026-06-30', interval: 10000, last: 83447, lastDate: '2026-02-05', target: 93447, completed: true, actual: 93965, actualDate: '2026-07-07' },
        { no: 29, code: 'DT 100', id: 'DT-04042', asset: 'Dump Truck Hino Ranger FM 280 JD', year: 2022, warranty: 'No Warranty', current: 87515, tracking: '2026-06-30', interval: 10000, last: 79988, lastDate: '2026-02-27', target: 89988, completed: false },
        { no: 30, code: 'DT 094', id: 'DT-04027 / DT-04053', asset: 'Dump Truck Hino FM8JN2D-XGJ', year: 2022, warranty: 'No Warranty', current: 72830, tracking: '2026-06-30', interval: 10000, last: 63495, lastDate: '2026-01-19', target: 73495, completed: true, actual: 73763, actualDate: '2026-07-08' },
        { no: 31, code: 'PM 02', id: 'PM-00001', asset: 'Prime Mover UD Truck GWE350 6x4', year: 2024, warranty: 'No Warranty', current: 35552, tracking: '2026-06-30', interval: 10000, last: 26043, lastDate: '2026-02-07', target: 36043, completed: true, actual: 36624, actualDate: '2026-07-21' },
        { no: 32, code: 'TR11', id: 'LV-00007', asset: 'Double Cabin Mitsubishi Triton', year: 2024, warranty: 'No Warranty', current: 69580, tracking: '2026-07-01', interval: 10000, last: 59489, lastDate: '2026-03-12', target: 69489, completed: false },
        { no: 33, code: '', id: 'LV-00022', asset: 'Double Cabin Toyota Hilux', year: 2022, warranty: 'No Warranty', current: 90898, tracking: '2026-07-01', interval: 10000, last: 81044, lastDate: '2026-03-03', target: 91044, completed: false, note: 'Kode unit kosong pada sumber.' },
        { no: 34, code: '', id: 'LV-00027', asset: 'Light Vehicle Toyota Hilux', year: 2022, warranty: 'No Warranty', current: 243559, tracking: '2026-07-01', interval: 10000, last: 233053, lastDate: '2026-02-06', target: 243053, completed: false, note: 'Kode unit kosong pada sumber.' }
    ];

    const kitReferences = [
        { no: 1, code: 'SD 02', id: 'SD-00001', model: 'Bomag BW 211D-40SL', items: 7, issues: 1 },
        { no: 2, code: 'Dozer 06', id: 'Belum terpetakan', model: 'Caterpillar D6R', items: 8, issues: 0 },
        { no: 3, code: 'Exca 21', id: 'EXC-00003', model: 'Komatsu PC200-10M0 CE', items: 11, issues: 0 },
        { no: 4, code: 'Exca 06', id: 'EXC-00005', model: 'Caterpillar 320 GX', items: 8, issues: 3 },
        { no: 5, code: 'EXCA-29', id: 'EXC-00009', model: 'Komatsu PC200-10M0 CE', items: 5, issues: 2 },
        { no: 6, code: 'Exca 18', id: 'HE.6.001', model: 'Caterpillar 305.5E2', items: 6, issues: 0 },
        { no: 7, code: 'Exca 16', id: 'HE.6.004', model: 'Caterpillar 305.5E2', items: 6, issues: 0 },
        { no: 8, code: 'CR02', id: 'CC-00002', model: 'XCMG XGC35HD', items: 8, issues: 7 },
        { no: 9, code: 'DT 010', id: 'DT-00047', model: 'Hino Ranger FM 260 JD', items: 11, issues: 2 },
        { no: 10, code: 'DT 098', id: 'DT-04001', model: 'Hino Ranger FM 280 JD', items: 9, issues: 2 },
        { no: 11, code: 'DT 103', id: 'DT-04024', model: 'Hino Ranger FM 280 JD', items: 9, issues: 1 },
        { no: 12, code: 'DT 104', id: 'DT-04025', model: 'Hino Ranger FM 280 JD', items: 9, issues: 1 },
        { no: 13, code: 'DT 092', id: 'DT-04030', model: 'Hino Ranger FM 280 JD', items: 9, issues: 1 },
        { no: 14, code: 'DT 097', id: 'DT-04032', model: 'Hino Ranger FM 280 JD', items: 9, issues: 1 },
        { no: 15, code: 'DT 101', id: 'DT-04036', model: 'Hino Ranger FM 280 JD', items: 9, issues: 1 },
        { no: 16, code: 'DT 091', id: 'DT-04038', model: 'Hino Ranger FM 280 JD', items: 9, issues: 1 },
        { no: 17, code: 'DT 093', id: 'DT-04040', model: 'Hino Ranger FM 280 JD', items: 9, issues: 1 },
        { no: 18, code: 'DT 100', id: 'DT-04042', model: 'Hino Ranger FM 280 JD', items: 9, issues: 1 },
        { no: 19, code: 'DT 096', id: 'DT-04044', model: 'Hino Ranger FM 280 JD', items: 9, issues: 1 },
        { no: 20, code: 'DT 107', id: 'DT-04048', model: 'Hino Ranger FM 280 JD', items: 9, issues: 1 }
    ];

    let activeTab = 'forecast';
    let thresholds = { HM: 50, KM: 500 };
    let overrides = {};
    const storageKey = 'fleetmonitor-pm-overrides-v1';
    const thresholdKey = 'fleetmonitor-pm-thresholds-v1';

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatNumber(value) {
        if (value == null || value === '') return '—';
        return Number(value).toLocaleString('id-ID', { maximumFractionDigits: 1 });
    }

    function formatDate(value) {
        if (!value) return '—';
        const parts = value.split('-');
        return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
            .format(new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2])));
    }

    function categoryOf(plan) {
        const name = plan.asset.toLowerCase();
        if (name.includes('dump truck')) return 'Dump Truck';
        if (name.includes('excavator')) return 'Excavator';
        if (name.includes('bulldozer')) return 'Bulldozer';
        if (name.includes('compactor')) return 'Compactor';
        if (name.includes('grader')) return 'Motor Grader';
        if (name.includes('prime mover')) return 'Prime Mover';
        return 'Light Vehicle';
    }

    function meterType(plan) {
        return ['Dump Truck', 'Prime Mover', 'Light Vehicle'].includes(categoryOf(plan)) ? 'KM' : 'HM';
    }

    function mergedPlan(plan) {
        const override = overrides[plan.id] || {};
        return {
            ...plan,
            ...override,
            completed: plan.completed || Boolean(override.actualDate)
        };
    }

    function varianceOf(plan) {
        if (plan.current == null || plan.target == null) return null;
        return Math.round((Number(plan.current) - Number(plan.target)) * 10) / 10;
    }

    function statusOf(rawPlan) {
        const plan = mergedPlan(rawPlan);
        if (plan.completed) return 'COMPLETED';
        const variance = varianceOf(plan);
        if (variance == null) return 'NO DATA';
        if (variance > 0) return 'OVERDUE';
        if (variance === 0) return 'DUE';
        if (Math.abs(variance) <= thresholds[meterType(plan)]) return 'DUE SOON';
        return 'NOT DUE';
    }

    function statusLabel(status) {
        return {
            COMPLETED: 'COMPLETED',
            OVERDUE: 'OVERDUE',
            DUE: 'DUE',
            'DUE SOON': 'DUE SOON',
            'NOT DUE': 'NOT DUE',
            'NO DATA': 'NO DATA'
        }[status] || status;
    }

    function statusClass(status) {
        return status.toLowerCase().replace(/\s+/g, '-');
    }

    function intervalUsage(plan) {
        if (plan.current == null || plan.last == null || !plan.interval) return 0;
        return Math.max(0, Math.round(((plan.current - plan.last) / plan.interval) * 100));
    }

    function qualityFlags(rawPlan) {
        const plan = mergedPlan(rawPlan);
        const flags = [];
        if (!plan.code) flags.push('Kode unit tidak tersedia pada sumber.');
        if (plan.completed && (!plan.actualDate || plan.actual == null)) flags.push('Realisasi ditandai, tetapi tanggal atau meter realisasi belum lengkap.');
        if (plan.actual != null && Math.abs(plan.actual - plan.target) > plan.interval * 2) flags.push('Meter realisasi menyimpang jauh dari target; perlu validasi.');
        if (plan.note) flags.push(plan.note);
        return [...new Set(flags)];
    }

    function loadLocalState() {
        try {
            overrides = JSON.parse(localStorage.getItem(storageKey)) || {};
            thresholds = { ...thresholds, ...(JSON.parse(localStorage.getItem(thresholdKey)) || {}) };
        } catch (error) {
            overrides = {};
        }
    }

    function saveLocalState() {
        try {
            localStorage.setItem(storageKey, JSON.stringify(overrides));
            localStorage.setItem(thresholdKey, JSON.stringify(thresholds));
        } catch (error) {
            showToast('Browser tidak mengizinkan penyimpanan lokal.', true);
        }
    }

    function createModule() {
        const root = document.getElementById('preventiveMaintenanceModule');
        if (!root) return;
        loadLocalState();
        root.innerHTML = `
            <div class="pm-page-header">
                <div>
                    <div class="pm-eyebrow">Maintenance Planning · M05</div>
                    <h1>Preventive Maintenance Control Center</h1>
                    <p>Forecast interval HM/KM, realisasi service, kualitas histori, dan kesiapan referensi filter dalam satu tampilan kerja planner.</p>
                </div>
                <div class="pm-header-actions">
                    <button class="pm-button secondary" id="pmMethodButton"><i class="fa-solid fa-calculator"></i> Metodologi</button>
                    <button class="pm-button primary" id="pmExportButton"><i class="fa-solid fa-file-export"></i> Export Tracker</button>
                </div>
            </div>
            <div class="pm-context-bar">
                <div class="pm-context-group">
                    <i class="fa-regular fa-calendar"></i>
                    <div><div class="pm-context-label">Periode aktif</div><div class="pm-context-value">Juli 2026 · WUR EW Project</div></div>
                </div>
                <div class="pm-context-group">
                    <i class="fa-solid fa-bell"></i>
                    <div>
                        <div class="pm-context-label">Warning window</div>
                        <div class="pm-context-value">
                            HM <input class="pm-threshold-input" id="pmHmThreshold" type="number" min="1" value="${thresholds.HM}">
                            &nbsp; KM <input class="pm-threshold-input" id="pmKmThreshold" type="number" min="1" value="${thresholds.KM}">
                        </div>
                    </div>
                </div>
                <div class="pm-source-note"><i class="fa-solid fa-database"></i> Acuan: Plan Service Juli 2026 + Kebutuhan Filter Januari 2026</div>
            </div>
            <nav class="pm-tabs" aria-label="Submenu Preventive Maintenance">
                <button class="pm-tab active" data-pm-tab="forecast"><i class="fa-solid fa-gauge-high"></i> Forecast & Due Tracker</button>
                <button class="pm-tab" data-pm-tab="calendar"><i class="fa-regular fa-calendar-days"></i> Kalender Eksekusi</button>
                <button class="pm-tab" data-pm-tab="kitting"><i class="fa-solid fa-box-open"></i> Kitting & Validasi Part</button>
            </nav>
            <div id="pmPanelForecast" class="pm-tab-panel active"></div>
            <div id="pmPanelCalendar" class="pm-tab-panel"></div>
            <div id="pmPanelKitting" class="pm-tab-panel"></div>
            <div class="pm-detail-overlay" id="pmDetailOverlay" role="dialog" aria-modal="true"></div>
            <div class="pm-toast" id="pmToast"><i class="fa-solid fa-circle-check"></i><span></span></div>
        `;

        root.querySelectorAll('[data-pm-tab]').forEach(button => {
            button.addEventListener('click', () => switchTab(button.dataset.pmTab));
        });
        document.getElementById('pmHmThreshold').addEventListener('change', updateThresholds);
        document.getElementById('pmKmThreshold').addEventListener('change', updateThresholds);
        document.getElementById('pmMethodButton').addEventListener('click', () => {
            switchTab('forecast');
            setTimeout(() => document.getElementById('pmFormulaStrip').scrollIntoView({ behavior: 'smooth', block: 'center' }), 50);
        });
        document.getElementById('pmExportButton').addEventListener('click', exportTracker);
        document.getElementById('pmDetailOverlay').addEventListener('click', event => {
            if (event.target.id === 'pmDetailOverlay') closeDetail();
        });
        renderAll();
    }

    function updateThresholds() {
        thresholds.HM = Math.max(1, Number(document.getElementById('pmHmThreshold').value) || 50);
        thresholds.KM = Math.max(1, Number(document.getElementById('pmKmThreshold').value) || 500);
        saveLocalState();
        renderAll();
        showToast('Warning window diperbarui dan status dihitung ulang.');
    }

    function switchTab(tab) {
        activeTab = tab;
        document.querySelectorAll('.pm-tab').forEach(button => button.classList.toggle('active', button.dataset.pmTab === tab));
        document.querySelectorAll('.pm-tab-panel').forEach(panel => panel.classList.remove('active'));
        document.getElementById(`pmPanel${tab.charAt(0).toUpperCase() + tab.slice(1)}`).classList.add('active');
    }

    function metrics() {
        const statuses = pmPlans.map(statusOf);
        const completed = statuses.filter(status => status === 'COMPLETED').length;
        return {
            total: pmPlans.length,
            completed,
            achievement: completed / pmPlans.length * 100,
            overdue: statuses.filter(status => status === 'OVERDUE').length,
            dueSoon: statuses.filter(status => ['DUE SOON', 'DUE'].includes(status)).length,
            issues: pmPlans.filter(plan => qualityFlags(plan).length).length
        };
    }

    function renderAll() {
        renderForecast();
        renderCalendar();
        renderKitting();
        switchTab(activeTab);
    }

    function renderForecast() {
        const panel = document.getElementById('pmPanelForecast');
        const summary = metrics();
        panel.innerHTML = `
            <div class="pm-kpi-grid">
                ${kpiCard('blue', 'Rencana Service', summary.total, 'unit periode Juli', 'fa-calendar-check')}
                ${kpiCard('green', 'Realisasi', summary.completed, `${summary.achievement.toFixed(1).replace('.', ',')}% ketercapaian`, 'fa-circle-check', summary.achievement)}
                ${kpiCard('red', 'Overdue Aktif', summary.overdue, 'belum direalisasikan', 'fa-triangle-exclamation')}
                ${kpiCard('amber', 'Due / Due Soon', summary.dueSoon, `HM ≤ ${thresholds.HM} · KM ≤ ${thresholds.KM}`, 'fa-bell')}
                ${kpiCard('purple', 'Isu Kualitas Data', summary.issues, 'butuh verifikasi planner', 'fa-shield-halved')}
            </div>
            <div class="pm-overview-grid">
                <section class="pm-card">
                    <div class="pm-card-header">
                        <div><div class="pm-card-title"><i class="fa-solid fa-fire-flame-curved"></i> Prioritas PM Belum Ditangani</div><div class="pm-card-caption">Diurutkan dari selisih jatuh tempo terbesar</div></div>
                        <span class="pm-status overdue">${summary.overdue} OVERDUE</span>
                    </div>
                    <ul class="pm-priority-list">${priorityListMarkup()}</ul>
                </section>
                <section class="pm-card">
                    <div class="pm-card-header">
                        <div class="pm-card-title"><i class="fa-solid fa-chart-pie"></i> Ketercapaian Juli</div>
                    </div>
                    <div class="pm-attainment">
                        <div class="pm-donut" style="--value:${summary.achievement}">
                            <div class="pm-donut-label"><strong>${summary.achievement.toFixed(1).replace('.', ',')}%</strong><span>${summary.completed} dari ${summary.total} unit</span></div>
                        </div>
                        <h3>Sisa ${summary.total - summary.completed} unit</h3>
                        <p>Realisasi dihitung dari penanda sumber dan pembaruan lokal planner. Satu realisasi sumber belum memiliki detail HM/KM dan tanggal.</p>
                    </div>
                </section>
            </div>
            <section class="pm-card">
                <div class="pm-card-header">
                    <div><div class="pm-card-title"><i class="fa-solid fa-list-check"></i> PM Forecast Tracker</div><div class="pm-card-caption">Target = service terakhir + interval · selisih positif berarti overdue</div></div>
                    <span class="pm-card-caption" id="pmTableCount"></span>
                </div>
                <div class="pm-filter-bar">
                    <div class="pm-field"><label>Pencarian unit</label><input id="pmSearch" class="pm-input" type="search" placeholder="Kode, lambung, atau nama asset..."></div>
                    <div class="pm-field"><label>Status</label><select id="pmStatusFilter" class="pm-select"><option value="">Semua status</option>${['COMPLETED','OVERDUE','DUE SOON','DUE','NOT DUE','NO DATA'].map(status => `<option>${status}</option>`).join('')}</select></div>
                    <div class="pm-field"><label>Kategori</label><select id="pmCategoryFilter" class="pm-select"><option value="">Semua kategori</option>${[...new Set(pmPlans.map(categoryOf))].sort().map(category => `<option>${escapeHtml(category)}</option>`).join('')}</select></div>
                    <button class="pm-button secondary" id="pmResetFilter"><i class="fa-solid fa-rotate-left"></i> Reset</button>
                </div>
                <div class="pm-table-wrap">
                    <table class="pm-table">
                        <thead><tr><th>Unit</th><th>Asset</th><th>Tracking</th><th>Service terakhir</th><th>Target berikutnya</th><th>Pemakaian interval</th><th>Selisih</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody id="pmTrackerBody"></tbody>
                    </table>
                </div>
                <div class="pm-table-footer"><span>Jenis meter HM/KM diturunkan secara indikatif dari kelompok aset.</span><span>Data tersimpan lokal untuk prototipe.</span></div>
            </section>
            <div class="pm-formula-strip" id="pmFormulaStrip">
                <i class="fa-solid fa-calculator"></i>
                <div><strong>Target service</strong>Service terakhir + interval</div>
                <div><strong>Selisih jatuh tempo</strong>Meter terkini − target service</div>
                <div><strong>Status otomatis</strong>Positif = Overdue · 0 = Due · dalam warning window = Due Soon</div>
            </div>
        `;

        const renderFiltered = () => renderTrackerRows();
        document.getElementById('pmSearch').addEventListener('input', renderFiltered);
        document.getElementById('pmStatusFilter').addEventListener('change', renderFiltered);
        document.getElementById('pmCategoryFilter').addEventListener('change', renderFiltered);
        document.getElementById('pmResetFilter').addEventListener('click', () => {
            document.getElementById('pmSearch').value = '';
            document.getElementById('pmStatusFilter').value = '';
            document.getElementById('pmCategoryFilter').value = '';
            renderFiltered();
        });
        renderTrackerRows();
    }

    function kpiCard(color, label, value, sub, icon, progress) {
        return `<div class="pm-kpi ${color}">
            <i class="fa-solid ${icon} pm-kpi-icon"></i>
            <div class="pm-kpi-label">${label}</div>
            <div class="pm-kpi-value">${value}</div>
            <div class="pm-kpi-sub">${sub}</div>
            ${progress == null ? '' : `<div class="pm-progress-track"><span style="width:${Math.min(100, progress)}%"></span></div>`}
        </div>`;
    }

    function priorityListMarkup() {
        const priority = pmPlans
            .filter(plan => ['OVERDUE', 'DUE', 'DUE SOON'].includes(statusOf(plan)))
            .sort((a, b) => (varianceOf(b) || 0) - (varianceOf(a) || 0));
        if (!priority.length) return '<li class="pm-priority-item"><div class="pm-priority-copy"><strong>Tidak ada prioritas aktif</strong><span>Seluruh jadwal berada dalam kondisi aman.</span></div></li>';
        return priority.map(plan => {
            const variance = varianceOf(plan);
            const status = statusOf(plan);
            return `<li class="pm-priority-item">
                <div class="pm-priority-meter">${meterType(plan)}</div>
                <div class="pm-priority-copy"><strong>${escapeHtml(plan.code || plan.id)} · ${escapeHtml(plan.id)}</strong><span>${escapeHtml(plan.asset)}</span></div>
                <div class="pm-priority-variance">${status === 'OVERDUE' ? '+' : ''}${formatNumber(variance)} ${meterType(plan)}<br><button class="pm-row-action" data-pm-detail="${escapeHtml(plan.id)}">Tindak lanjut</button></div>
            </li>`;
        }).join('');
    }

    function renderTrackerRows() {
        const body = document.getElementById('pmTrackerBody');
        if (!body) return;
        const query = document.getElementById('pmSearch').value.trim().toLowerCase();
        const statusFilter = document.getElementById('pmStatusFilter').value;
        const categoryFilter = document.getElementById('pmCategoryFilter').value;
        const filtered = pmPlans.filter(rawPlan => {
            const plan = mergedPlan(rawPlan);
            const haystack = `${plan.code} ${plan.id} ${plan.asset}`.toLowerCase();
            return (!query || haystack.includes(query))
                && (!statusFilter || statusOf(plan) === statusFilter)
                && (!categoryFilter || categoryOf(plan) === categoryFilter);
        });
        document.getElementById('pmTableCount').textContent = `${filtered.length} dari ${pmPlans.length} unit`;
        body.innerHTML = filtered.map(rawPlan => {
            const plan = mergedPlan(rawPlan);
            const status = statusOf(plan);
            const variance = varianceOf(plan);
            const usage = intervalUsage(plan);
            const barClass = status === 'COMPLETED' ? 'complete' : status === 'OVERDUE' ? 'danger' : ['DUE', 'DUE SOON'].includes(status) ? 'warning' : '';
            return `<tr>
                <td class="pm-unit-cell"><strong>${escapeHtml(plan.code || 'Kode belum ada')}</strong><span>${escapeHtml(plan.id)} · ${meterType(plan)}</span></td>
                <td class="pm-asset-cell"><strong title="${escapeHtml(plan.asset)}">${escapeHtml(plan.asset)}</strong><span>${plan.year} · ${escapeHtml(plan.warranty)}</span></td>
                <td><strong>${formatNumber(plan.current)} ${meterType(plan)}</strong><br><span class="pm-card-caption">${formatDate(plan.tracking)}</span></td>
                <td><strong>${formatNumber(plan.last)}</strong><br><span class="pm-card-caption">${formatDate(plan.lastDate)}</span></td>
                <td><strong>${formatNumber(plan.target)}</strong><br><span class="pm-card-caption">Interval ${formatNumber(plan.interval)}</span></td>
                <td><div class="pm-meter-progress"><div class="pm-meter-progress-head"><span>${Math.min(usage, 999)}%</span><span>${formatNumber(plan.current - plan.last)} / ${formatNumber(plan.interval)}</span></div><div class="pm-meter-bar"><span class="${barClass}" style="width:${Math.min(100, usage)}%"></span></div></div></td>
                <td><strong style="color:${variance > 0 ? 'var(--pm-red)' : '#4c586c'}">${variance > 0 ? '+' : ''}${formatNumber(variance)}</strong><br><span class="pm-card-caption">${meterType(plan)}</span></td>
                <td><span class="pm-status ${statusClass(status)}">${statusLabel(status)}</span></td>
                <td><button class="pm-row-action" data-pm-detail="${escapeHtml(plan.id)}"><i class="fa-regular fa-eye"></i> Detail</button></td>
            </tr>`;
        }).join('');
        bindDetailButtons(body);
        const priorityContainer = document.querySelector('.pm-priority-list');
        if (priorityContainer) bindDetailButtons(priorityContainer);
    }

    function renderCalendar() {
        const panel = document.getElementById('pmPanelCalendar');
        const events = {};
        pmPlans.forEach(rawPlan => {
            const plan = mergedPlan(rawPlan);
            if (plan.actualDate && plan.actualDate.startsWith('2026-07')) {
                const day = Number(plan.actualDate.slice(-2));
                if (!events[day]) events[day] = [];
                events[day].push(plan);
            }
        });
        const firstDayOffset = (new Date(2026, 6, 1).getDay() + 6) % 7;
        const cells = Array.from({ length: firstDayOffset }, () => '<div class="pm-calendar-day empty"></div>');
        for (let day = 1; day <= 31; day++) {
            cells.push(`<div class="pm-calendar-day"><div class="pm-calendar-number">${day}</div>${(events[day] || []).map(plan => `<button class="pm-calendar-event ${plan.actual == null ? 'incomplete' : ''}" data-pm-detail="${escapeHtml(plan.id)}" title="${escapeHtml(plan.code || plan.id)}">${escapeHtml(plan.code || plan.id)}</button>`).join('')}</div>`);
        }
        const attention = pmPlans.filter(plan => ['OVERDUE', 'DUE', 'DUE SOON'].includes(statusOf(plan)) || (mergedPlan(plan).completed && !mergedPlan(plan).actualDate));
        panel.innerHTML = `
            <div class="pm-calendar-layout">
                <section class="pm-card">
                    <div class="pm-card-header"><div><div class="pm-card-title"><i class="fa-regular fa-calendar-days"></i> Juli 2026</div><div class="pm-card-caption">Tanggal menampilkan realisasi yang tercatat, bukan estimasi due date</div></div><span class="pm-status completed">${Object.values(events).flat().length} TERCATAT</span></div>
                    <div class="pm-calendar">
                        ${['Sen','Sel','Rab','Kam','Jum','Sab','Min'].map(day => `<div class="pm-calendar-head">${day}</div>`).join('')}
                        ${cells.join('')}
                    </div>
                </section>
                <section class="pm-card">
                    <div class="pm-card-header"><div><div class="pm-card-title"><i class="fa-solid fa-clipboard-list"></i> Perlu Dijadwalkan / Dilengkapi</div><div class="pm-card-caption">Tidak membuat tanggal estimasi tanpa data usage rate</div></div></div>
                    <ul class="pm-unscheduled-list">
                        ${attention.map(rawPlan => {
                            const plan = mergedPlan(rawPlan);
                            const status = statusOf(plan);
                            return `<li><div><strong>${escapeHtml(plan.code || plan.id)}</strong><span>${status === 'COMPLETED' ? 'Detail realisasi belum lengkap' : `${status} · ${varianceOf(plan) > 0 ? '+' : ''}${formatNumber(varianceOf(plan))} ${meterType(plan)}`}</span></div><button class="pm-row-action" data-pm-detail="${escapeHtml(plan.id)}">Buka</button></li>`;
                        }).join('')}
                    </ul>
                </section>
            </div>
        `;
        bindDetailButtons(panel);
    }

    function renderKitting() {
        const panel = document.getElementById('pmPanelKitting');
        const totalItems = kitReferences.reduce((sum, kit) => sum + kit.items, 0);
        const issues = kitReferences.reduce((sum, kit) => sum + kit.issues, 0);
        const usable = totalItems - issues;
        panel.innerHTML = `
            <div class="pm-kitting-summary">
                <div class="pm-kit-metric"><span>Unit referensi Januari</span><strong>${kitReferences.length}</strong></div>
                <div class="pm-kit-metric"><span>Baris filter / komponen</span><strong>${totalItems}</strong></div>
                <div class="pm-kit-metric"><span>PN langsung operasional</span><strong>${usable}</strong></div>
                <div class="pm-kit-metric"><span>Perlu verifikasi / kosong</span><strong style="color:var(--pm-red)">${issues}</strong></div>
            </div>
            <div class="pm-kit-warning">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Daftar sumber tidak memiliki Qty per service, stok, reserved stock, lead time, atau jumlah pembelian. Karena itu readiness di bawah hanya menunjukkan <strong>kelengkapan/validasi part number</strong>, bukan kesiapan stok fisik atau kuantitas order.</span>
            </div>
            <section class="pm-card">
                <div class="pm-card-header">
                    <div><div class="pm-card-title"><i class="fa-solid fa-box-open"></i> Referensi Kitting Filter per Unit</div><div class="pm-card-caption">Acuan tambahan: Kebutuhan Filter PM Januari 2026</div></div>
                    <span class="pm-card-caption">9/20 unit memiliki data service yang dapat dihitung pada sumber Januari</span>
                </div>
                <div class="pm-table-wrap">
                    <table class="pm-table">
                        <thead><tr><th>Unit</th><th>Model</th><th>Total item</th><th>PN dapat digunakan</th><th>Perlu verifikasi</th><th>Kelengkapan PN</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody>
                            ${kitReferences.map(kit => {
                                const usableItems = kit.items - kit.issues;
                                const readiness = Math.round(usableItems / kit.items * 100);
                                return `<tr>
                                    <td class="pm-unit-cell"><strong>${escapeHtml(kit.code)}</strong><span>${escapeHtml(kit.id)}</span></td>
                                    <td class="pm-asset-cell"><strong>${escapeHtml(kit.model)}</strong></td>
                                    <td>${kit.items}</td><td>${usableItems}</td><td>${kit.issues}</td>
                                    <td><div class="pm-kit-readiness"><div class="pm-meter-bar"><span class="${readiness < 70 ? 'danger' : readiness < 100 ? 'warning' : 'complete'}" style="width:${readiness}%"></span></div><strong>${readiness}%</strong></div></td>
                                    <td><span class="pm-status ${kit.issues ? 'due-soon' : 'completed'}">${kit.issues ? 'VERIFIKASI PN' : 'PN LENGKAP'}</span></td>
                                    <td><button class="pm-row-action" data-kit-detail="${kit.no}">Tinjau</button></td>
                                </tr>`;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
                <div class="pm-table-footer"><span>Part alternatif dipilih berdasarkan serial number, konfigurasi terpasang, dan parts book.</span><span>Belum dapat menjadi purchase request.</span></div>
            </section>
            <div class="pm-formula-strip">
                <i class="fa-solid fa-boxes-stacked"></i>
                <div><strong>Input berikutnya</strong>Qty required · stock available · reserved · on order</div>
                <div><strong>Shortage</strong>MAX(Qty required − stock yang dapat dialokasikan, 0)</div>
                <div><strong>Qty to order</strong>MAX(Shortage − on order, 0) setelah PN tervalidasi</div>
            </div>
        `;
        panel.querySelectorAll('[data-kit-detail]').forEach(button => {
            button.addEventListener('click', () => openKitDetail(Number(button.dataset.kitDetail)));
        });
    }

    function bindDetailButtons(container) {
        container.querySelectorAll('[data-pm-detail]').forEach(button => {
            button.addEventListener('click', () => openPlanDetail(button.dataset.pmDetail));
        });
    }

    function openPlanDetail(planId) {
        const original = pmPlans.find(plan => plan.id === planId);
        if (!original) return;
        const plan = mergedPlan(original);
        const status = statusOf(plan);
        const variance = varianceOf(plan);
        const flags = qualityFlags(plan);
        const overlay = document.getElementById('pmDetailOverlay');
        overlay.innerHTML = `
            <div class="pm-detail-dialog">
                <div class="pm-detail-header">
                    <div class="pm-detail-title"><i class="fa-solid fa-screwdriver-wrench"></i><div><h2>${escapeHtml(plan.code || 'Kode belum tersedia')} · ${escapeHtml(plan.id)}</h2><p>${escapeHtml(plan.asset)}</p></div></div>
                    <button class="pm-close" data-close-pm><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="pm-detail-body">
                    <div class="pm-detail-grid">
                        ${detailStat('Status', `<span class="pm-status ${statusClass(status)}">${status}</span>`)}
                        ${detailStat('Meter terkini', `${formatNumber(plan.current)} ${meterType(plan)}`)}
                        ${detailStat('Target service', `${formatNumber(plan.target)} ${meterType(plan)}`)}
                        ${detailStat('Selisih', `${variance > 0 ? '+' : ''}${formatNumber(variance)} ${meterType(plan)}`)}
                        ${detailStat('Interval', `${formatNumber(plan.interval)} ${meterType(plan)}`)}
                        ${detailStat('Warranty', escapeHtml(plan.warranty))}
                    </div>
                    <div class="pm-detail-section">
                        <h3>Dasar Perhitungan</h3>
                        <div class="pm-detail-grid">
                            ${detailStat('Service terakhir', `${formatNumber(plan.last)} · ${formatDate(plan.lastDate)}`)}
                            ${detailStat('Tracking terakhir', `${formatNumber(plan.current)} · ${formatDate(plan.tracking)}`)}
                            ${detailStat('Pemakaian interval', `${intervalUsage(plan)}%`)}
                        </div>
                    </div>
                    <div class="pm-detail-section">
                        <h3>Rekam / Lengkapi Realisasi PM</h3>
                        <form class="pm-detail-form" id="pmExecutionForm">
                            <div class="pm-field"><label>Tanggal aktual</label><input class="pm-input" name="actualDate" type="date" value="${escapeHtml(plan.actualDate || '')}" required></div>
                            <div class="pm-field"><label>HM/KM aktual</label><input class="pm-input" name="actual" type="number" step="0.1" value="${plan.actual == null ? '' : plan.actual}" required></div>
                            <div class="pm-field"><label>Nomor Work Order</label><input class="pm-input" name="wo" value="${escapeHtml(plan.wo || '')}" placeholder="WO-PM-..."></div>
                            <div class="pm-field"><label>PIC mekanik</label><input class="pm-input" name="mechanic" value="${escapeHtml(plan.mechanic || '')}" placeholder="Nama PIC"></div>
                            <div class="pm-field"><label>Kesiapan filter</label><select class="pm-select" name="filterReady"><option value="">Belum dicek</option>${['Siap','Parsial','Belum siap'].map(option => `<option ${plan.filterReady === option ? 'selected' : ''}>${option}</option>`).join('')}</select></div>
                            <div class="pm-field"><label>Kesiapan oli & grease</label><select class="pm-select" name="fluidReady"><option value="">Belum dicek</option>${['Siap','Parsial','Belum siap'].map(option => `<option ${plan.fluidReady === option ? 'selected' : ''}>${option}</option>`).join('')}</select></div>
                            <div class="pm-field full"><label>Analisa / catatan planner</label><textarea class="pm-input" name="plannerNote" rows="3">${escapeHtml(plan.plannerNote || '')}</textarea></div>
                        </form>
                    </div>
                    ${flags.length ? `<div class="pm-quality-flag"><i class="fa-solid fa-triangle-exclamation"></i><span><strong>Quality gate:</strong><br>${flags.map(escapeHtml).join('<br>')}</span></div>` : ''}
                </div>
                <div class="pm-detail-footer">
                    <button class="pm-button secondary" data-close-pm>Batal</button>
                    <button class="pm-button primary" id="pmSaveExecution"><i class="fa-regular fa-floppy-disk"></i> Simpan Realisasi</button>
                </div>
            </div>
        `;
        overlay.classList.add('active');
        overlay.querySelectorAll('[data-close-pm]').forEach(button => button.addEventListener('click', closeDetail));
        document.getElementById('pmSaveExecution').addEventListener('click', () => saveExecution(plan.id));
    }

    function detailStat(label, value) {
        return `<div class="pm-detail-stat"><span>${label}</span><strong>${value}</strong></div>`;
    }

    function saveExecution(planId) {
        const form = document.getElementById('pmExecutionForm');
        if (!form.reportValidity()) return;
        const data = Object.fromEntries(new FormData(form).entries());
        data.actual = Number(data.actual);
        overrides[planId] = { ...(overrides[planId] || {}), ...data, completed: true };
        saveLocalState();
        closeDetail();
        renderAll();
        showToast(`Realisasi ${planId} disimpan; KPI dan status telah dihitung ulang.`);
    }

    function openKitDetail(number) {
        const kit = kitReferences.find(item => item.no === number);
        if (!kit) return;
        const usable = kit.items - kit.issues;
        const overlay = document.getElementById('pmDetailOverlay');
        overlay.innerHTML = `
            <div class="pm-detail-dialog" style="max-width:650px">
                <div class="pm-detail-header">
                    <div class="pm-detail-title"><i class="fa-solid fa-box-open"></i><div><h2>${escapeHtml(kit.code)} · ${escapeHtml(kit.id)}</h2><p>${escapeHtml(kit.model)}</p></div></div>
                    <button class="pm-close" data-close-pm><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="pm-detail-body">
                    <div class="pm-detail-grid">
                        ${detailStat('Item referensi', kit.items)}
                        ${detailStat('PN dapat digunakan', usable)}
                        ${detailStat('Perlu verifikasi', kit.issues)}
                    </div>
                    <div class="pm-kit-warning"><i class="fa-solid fa-circle-info"></i><span>Kelengkapan ini berasal dari tabulasi Januari 2026. Serial number dan konfigurasi unit wajib diverifikasi sebelum membuat reservasi stok atau purchase request.</span></div>
                    <div class="pm-detail-section">
                        <h3>Data yang masih diperlukan untuk kitting produksi</h3>
                        <div class="pm-detail-grid">
                            ${detailStat('Kuantitas', 'Qty per service')}
                            ${detailStat('Inventory', 'Stock & reserved')}
                            ${detailStat('Procurement', 'On order & ETA')}
                            ${detailStat('Validasi', 'Validated by/date')}
                            ${detailStat('Referensi', 'Serial & parts book')}
                            ${detailStat('Eksekusi', 'Required date / WO')}
                        </div>
                    </div>
                </div>
                <div class="pm-detail-footer"><button class="pm-button primary" data-close-pm>Tutup</button></div>
            </div>
        `;
        overlay.classList.add('active');
        overlay.querySelectorAll('[data-close-pm]').forEach(button => button.addEventListener('click', closeDetail));
    }

    function closeDetail() {
        const overlay = document.getElementById('pmDetailOverlay');
        overlay.classList.remove('active');
        overlay.innerHTML = '';
    }

    function exportTracker() {
        const headers = ['Kode Unit','No Lambung','Asset','Jenis Meter','Tracking','Service Terakhir','Interval','Target','Selisih','Status','Realisasi Meter','Tanggal Realisasi','Catatan'];
        const rows = pmPlans.map(rawPlan => {
            const plan = mergedPlan(rawPlan);
            return [plan.code, plan.id, plan.asset, meterType(plan), plan.current, plan.last, plan.interval, plan.target, varianceOf(plan), statusOf(plan), plan.actual || '', plan.actualDate || '', plan.plannerNote || plan.note || ''];
        });
        const csv = [headers, ...rows].map(row => row.map(value => `"${String(value == null ? '' : value).replace(/"/g, '""')}"`).join(',')).join('\r\n');
        const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'PM_Tracker_Juli_2026.csv';
        link.click();
        URL.revokeObjectURL(link.href);
        showToast('Tracker PM berhasil diekspor ke CSV.');
    }

    function showToast(message, error = false) {
        const toast = document.getElementById('pmToast');
        if (!toast) return;
        toast.style.background = error ? '#a92f3d' : '#1f2937';
        toast.querySelector('i').className = error ? 'fa-solid fa-circle-exclamation' : 'fa-solid fa-circle-check';
        toast.querySelector('span').textContent = message;
        toast.classList.add('show');
        clearTimeout(showToast.timer);
        showToast.timer = setTimeout(() => toast.classList.remove('show'), 2800);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createModule);
    } else {
        createModule();
    }
})();
