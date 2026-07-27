(function () {
    'use strict';

    // =========================================================================
    // 1. DATA SOURCES & CONFIGURATIONS
    // =========================================================================

    const serviceBerkalaData = [
        { unit: 'Bulldozer D6G-04', hmAktual: '10.420 HM', hmService: '10.250 HM', diff: '+170 HM', date: '10 Jul 2026', status: 'Terlambat', priority: 'Sangat Tinggi', badge: 'badge-soft-danger', diffColor: 'color:var(--danger);' },
        { unit: 'Dump Truck DT-061', hmAktual: '186.750 KM', hmService: '185.000 KM', diff: '+1.750 KM', date: '11 Jul 2026', status: 'Terlambat', priority: 'Sangat Tinggi', badge: 'badge-soft-danger', diffColor: 'color:var(--danger);' },
        { unit: 'Excavator PC200-21', hmAktual: '8.496 HM', hmService: '8.500 HM', diff: '-4 HM', date: '13 Jul 2026', status: 'Hari Ini', priority: 'Tinggi', badge: 'badge-soft-warning', diffColor: 'color:var(--text-main);' },
        { unit: 'Motor Grader MG-009', hmAktual: '6.920 HM', hmService: '7.000 HM', diff: '-80 HM', date: '16 Jul 2026', status: 'Akan Service', priority: 'Sedang', badge: 'badge-soft-info', diffColor: 'color:var(--text-main);' },
        { unit: 'Vibro Roller VR-002', hmAktual: '4.840 HM', hmService: '5.000 HM', diff: '-160 HM', date: '21 Jul 2026', status: 'Terjadwal', priority: 'Normal', badge: 'badge-soft-info', diffColor: 'color:var(--text-main);' },
        { unit: 'Dump Truck DT-054', hmAktual: '178.300 KM', hmService: '180.000 KM', diff: '-1.700 KM', date: '25 Jul 2026', status: 'Aman', priority: 'Normal', badge: 'badge-soft-success', diffColor: 'color:var(--text-main);' }
    ];

    const logisticsStatusData = [
        { status: 'Dipesan', count: 12, pct: '24,0%', badge: 'badge-soft-info' },
        { status: 'Diproses Vendor', count: 7, pct: '14,0%', badge: 'badge-soft-warning' },
        { status: 'Dalam Pengiriman', count: 8, pct: '16,0%', badge: 'badge-soft-info' },
        { status: 'Tiba', count: 15, pct: '30,0%', badge: 'badge-soft-success' },
        { status: 'Tertunda', count: 6, pct: '12,0%', badge: 'badge-soft-danger' }
    ];

    const tireInspectionData = [
        { unit: 'DT-017', pos: 'Belakang Kanan', cond: 'Tipis', pressure: '82 PSI', badge: 'badge-soft-danger' },
        { unit: 'DT-054', pos: 'Depan Kiri', cond: 'Aus Tidak Rata', pressure: '88 PSI', badge: 'badge-soft-warning' },
        { unit: 'EX-021', pos: 'Track/Undercarriage', cond: 'Baik', pressure: '-', badge: 'badge-soft-success' },
        { unit: 'MG-009', pos: 'Belakang Kiri', cond: 'Rotasi', pressure: '90 PSI', badge: 'badge-soft-info' },
        { unit: 'VR-002', pos: 'Depan', cond: 'Baik', pressure: '86 PSI', badge: 'badge-soft-success' }
    ];

    const greaseStatusData = [
        { unit: 'D6G-04', lastHm: '218 jam', status: 'Terlambat', badge: 'badge-soft-danger' },
        { unit: 'PC200-21', lastHm: '192 jam', status: 'Jatuh Tempo', badge: 'badge-soft-warning' },
        { unit: 'DT-061', lastHm: '148 jam', status: 'Aman', badge: 'badge-soft-success' },
        { unit: 'MG-009', lastHm: '176 jam', status: 'Jatuh Tempo', badge: 'badge-soft-warning' }
    ];

    const mechanicProductivityData = [
        { name: 'Apeng', actual: 182, target: 176, pct: 103, color: 'badge-soft-success' },
        { name: 'Maman', actual: 174, target: 176, pct: 99, color: 'badge-soft-info' },
        { name: 'Soleh', actual: 168, target: 176, pct: 95, color: 'badge-soft-info' },
        { name: 'Darmawan', actual: 156, target: 176, pct: 89, color: 'badge-soft-warning' },
        { name: 'Regar', actual: 149, target: 176, pct: 85, color: 'badge-soft-danger' }
    ];

    const monthlyCostTrendData = [
        { month: 'Mei 2023', val: 118 },
        { month: 'Jun 2023', val: 145 },
        { month: 'Jul 2023', val: 148 },
        { month: 'Agu 2023', val: 125 },
        { month: 'Sep 2023', val: 168 },
        { month: 'Okt 2023', val: 175 },
        { month: 'Nov 2023', val: 170 },
        { month: 'Des 2023', val: 172 },
        { month: 'Jan 2024', val: 188 },
        { month: 'Feb 2024', val: 162 },
        { month: 'Mar 2024', val: 178 },
        { month: 'Apr 2024', val: 195 },
        { month: 'Mei 2024', val: 215 }
    ];

    // =========================================================================
    // 2. RENDER FUNCTIONS FOR 4 ANALYTICS ROWS
    // =========================================================================

    function initExecutiveAnalyticsPanels() {
        const targetContainer = document.getElementById('dashAnalyticsPanelsContainer');
        if (!targetContainer) return;

        targetContainer.innerHTML = `
            <!-- ROW 1: STATUS SERVICE BERKALA UNIT & DISTRIBUSI (GAMBAR 1) -->
            <div class="dash-analytics-row">
                <div class="dash-grid-2">
                    <!-- Left: Table & Counter Header -->
                    <div class="panel">
                        <div class="panel-header">
                            <span><i class="fa-solid fa-clock-rotate-left"></i> Status Service Berkala Unit</span>
                        </div>
                        <div class="panel-body">
                            <!-- 5 Counter Cards -->
                            <div class="service-counter-bar">
                                <div class="service-counter-card danger">
                                    <div class="lbl">Terlambat</div>
                                    <div class="val">12</div>
                                </div>
                                <div class="service-counter-card warning">
                                    <div class="lbl">Jatuh Tempo</div>
                                    <div class="val">7</div>
                                </div>
                                <div class="service-counter-card info">
                                    <div class="lbl">Service &le; 7 Hari</div>
                                    <div class="val">18</div>
                                </div>
                                <div class="service-counter-card success">
                                    <div class="lbl">Selesai Bulan Ini</div>
                                    <div class="val">43</div>
                                </div>
                                <div class="service-counter-card muted">
                                    <div class="lbl">Belum Ada Data</div>
                                    <div class="val">5</div>
                                </div>
                            </div>

                            <!-- Table -->
                            <div class="table-responsive">
                                <table style="margin-top:5px; font-size:0.85rem;">
                                    <thead>
                                        <tr>
                                            <th>Unit</th>
                                            <th>HM/KM Aktual</th>
                                            <th>HM/KM Service</th>
                                            <th>Selisih</th>
                                            <th>Rencana Service</th>
                                            <th>Status</th>
                                            <th>Prioritas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${serviceBerkalaData.map(s => `
                                            <tr>
                                                <td><strong>${escapeHtml(s.unit)}</strong></td>
                                                <td>${s.hmAktual}</td>
                                                <td>${s.hmService}</td>
                                                <td style="font-weight:bold; ${s.diffColor}">${s.diff}</td>
                                                <td>${s.date}</td>
                                                <td><span class="p2h-badge ${s.badge}">${s.status}</span></td>
                                                <td style="font-weight:600; color:${s.priority.includes('Tinggi') ? 'var(--danger)' : 'var(--text-main)'};">${s.priority}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Donut Chart & Compliance Metrics -->
                    <div class="panel">
                        <div class="panel-header">
                            <span><i class="fa-solid fa-chart-pie"></i> Distribusi Status Service</span>
                        </div>
                        <div class="panel-body" style="padding:15px;">
                            <div class="donut-chart-wrapper">
                                ${renderSvgDonut([
                                    { pct: 15, color: '#dc2626' }, // Terlambat
                                    { pct: 9, color: '#f59e0b' },  // Hari ini
                                    { pct: 23, color: '#0284c7' }, // <=7 Hari
                                    { pct: 47, color: '#16a34a' }, // Selesai
                                    { pct: 6, color: '#64748b' }   // Belum Ada Data
                                ])}

                                <div class="donut-legend">
                                    <div class="donut-legend-item">
                                        <div class="donut-legend-left"><span class="donut-color-dot" style="background:#dc2626;"></span> Terlambat</div>
                                        <strong>12 unit (15%)</strong>
                                    </div>
                                    <div class="donut-legend-item">
                                        <div class="donut-legend-left"><span class="donut-color-dot" style="background:#f59e0b;"></span> Hari Ini</div>
                                        <strong>7 unit (9%)</strong>
                                    </div>
                                    <div class="donut-legend-item">
                                        <div class="donut-legend-left"><span class="donut-color-dot" style="background:#0284c7;"></span> &le; 7 Hari</div>
                                        <strong>18 unit (23%)</strong>
                                    </div>
                                    <div class="donut-legend-item">
                                        <div class="donut-legend-left"><span class="donut-color-dot" style="background:#16a34a;"></span> Selesai</div>
                                        <strong>43 unit (47%)</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="compliance-list">
                                <div class="compliance-item">
                                    <span class="lbl">Compliance Service Berkala</span>
                                    <span class="val text-success">86,4%</span>
                                </div>
                                <div class="compliance-item">
                                    <span class="lbl">Unit Terlambat > 100 HM/KM</span>
                                    <span class="val text-danger">8 unit</span>
                                </div>
                                <div class="compliance-item">
                                    <span class="lbl">WO Service Belum Dibuat</span>
                                    <span class="val text-warning">6 unit</span>
                                </div>
                                <div class="compliance-item">
                                    <span class="lbl">Spare Part Service Belum Siap</span>
                                    <span class="val text-danger">4 unit</span>
                                </div>
                            </div>

                            <div class="dash-alert-banner">
                                <i class="fa-solid fa-circle-info" style="margin-top:2px;"></i>
                                <div>Prioritaskan pembuatan work order dan kesiapan filter, oli, grease, serta consumable minimal 3-7 hari sebelum service.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ROW 2: NILAI ASET, STATUS PERBAIKAN & LOGISTIK (GAMBAR 2) -->
            <div class="dash-analytics-row">
                <div class="dash-grid-3">
                    <!-- Panel 2A: Nilai Aset per Kategori -->
                    <div class="panel">
                        <div class="panel-header"><span><i class="fa-solid fa-chart-donut"></i> Nilai Aset per Kategori</span></div>
                        <div class="panel-body" style="padding:15px;">
                            <div class="donut-chart-wrapper">
                                ${renderSvgDonut([
                                    { pct: 53.5, color: '#0284c7' },
                                    { pct: 29.0, color: '#16a34a' },
                                    { pct: 10.2, color: '#f59e0b' },
                                    { pct: 5.3, color: '#ec4899' },
                                    { pct: 2.0, color: '#64748b' }
                                ])}
                                <div class="donut-legend">
                                    <div class="donut-legend-item">
                                        <div class="donut-legend-left"><span class="donut-color-dot" style="background:#0284c7;"></span> Alat Berat</div>
                                        <strong>53,5%</strong>
                                    </div>
                                    <div class="donut-legend-item">
                                        <div class="donut-legend-left"><span class="donut-color-dot" style="background:#16a34a;"></span> Dump Truck</div>
                                        <strong>29,0%</strong>
                                    </div>
                                    <div class="donut-legend-item">
                                        <div class="donut-legend-left"><span class="donut-color-dot" style="background:#f59e0b;"></span> Pendukung</div>
                                        <strong>10,2%</strong>
                                    </div>
                                    <div class="donut-legend-item">
                                        <div class="donut-legend-left"><span class="donut-color-dot" style="background:#ec4899;"></span> Kendaraan Ringan</div>
                                        <strong>5,3%</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel 2B: Status Perbaikan Unit -->
                    <div class="panel">
                        <div class="panel-header"><span><i class="fa-solid fa-wrench"></i> Status Perbaikan Unit</span></div>
                        <div class="panel-body" style="padding:15px;">
                            <div class="donut-chart-wrapper">
                                ${renderSvgDonut([
                                    { pct: 42.7, color: '#16a34a' }, // Selesai 38
                                    { pct: 30.3, color: '#0284c7' }, // Dalam Perbaikan 27
                                    { pct: 18.0, color: '#f59e0b' }, // Menunggu Spare Part 16
                                    { pct: 9.0, color: '#dc2626' }   // Breakdown 8
                                ])}
                                <div class="donut-legend">
                                    <div class="donut-legend-item">
                                        <div class="donut-legend-left"><span class="donut-color-dot" style="background:#16a34a;"></span> Selesai</div>
                                        <strong>38 unit</strong>
                                    </div>
                                    <div class="donut-legend-item">
                                        <div class="donut-legend-left"><span class="donut-color-dot" style="background:#0284c7;"></span> Dalam Perbaikan</div>
                                        <strong>27 unit</strong>
                                    </div>
                                    <div class="donut-legend-item">
                                        <div class="donut-legend-left"><span class="donut-color-dot" style="background:#f59e0b;"></span> Menunggu Spare Part</div>
                                        <strong>16 unit</strong>
                                    </div>
                                    <div class="donut-legend-item">
                                        <div class="donut-legend-left"><span class="donut-color-dot" style="background:#dc2626;"></span> Breakdown</div>
                                        <strong>8 unit</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel 2C: Status Pemesanan Barang Logistik -->
                    <div class="panel">
                        <div class="panel-header"><span><i class="fa-solid fa-boxes-packing"></i> Status Pemesanan Logistik</span></div>
                        <div class="panel-body" style="padding:15px;">
                            <table style="width:100%; font-size:0.83rem;">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th style="text-align:center;">Jumlah</th>
                                        <th style="text-align:right;">Persentase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${logisticsStatusData.map(l => `
                                        <tr>
                                            <td><span class="p2h-badge ${l.badge}">${escapeHtml(l.status)}</span></td>
                                            <td style="text-align:center; font-weight:bold;">${l.count}</td>
                                            <td style="text-align:right; font-weight:bold; color:var(--text-muted);">${l.pct}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ROW 3: CONDITION MONITORING BAN, GREASE & PRODUKTIVITAS MEKANIK (GAMBAR 3) -->
            <div class="dash-analytics-row">
                <div class="dash-grid-3">
                    <!-- Panel 3A: Analisis Inspeksi Ban -->
                    <div class="panel">
                        <div class="panel-header"><span><i class="fa-solid fa-circle-dot"></i> Analisis Inspeksi Ban</span></div>
                        <div class="panel-body" style="padding:15px;">
                            <div class="tire-kpi-bar">
                                <div class="tire-kpi-box">
                                    <div class="lbl">Ban Diperiksa</div>
                                    <div class="val">186</div>
                                </div>
                                <div class="tire-kpi-box">
                                    <div class="lbl">Perlu Rotasi</div>
                                    <div class="val text-warning">21</div>
                                </div>
                                <div class="tire-kpi-box">
                                    <div class="lbl">Harus Diganti</div>
                                    <div class="val text-danger">8</div>
                                </div>
                            </div>

                            <table style="width:100%; font-size:0.8rem;">
                                <thead>
                                    <tr>
                                        <th>Unit</th>
                                        <th>Posisi Ban</th>
                                        <th>Kondisi</th>
                                        <th>Tekanan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${tireInspectionData.map(t => `
                                        <tr>
                                            <td><strong>${escapeHtml(t.unit)}</strong></td>
                                            <td>${escapeHtml(t.pos)}</td>
                                            <td><span class="p2h-badge ${t.badge}">${escapeHtml(t.cond)}</span></td>
                                            <td>${t.pressure}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>

                            <div class="dash-alert-banner pink">
                                <i class="fa-solid fa-triangle-exclamation" style="margin-top:2px;"></i>
                                <div>Prioritas: 8 ban harus diganti segera dan 21 ban perlu rotasi untuk mencegah keausan tidak merata.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel 3B: Status Grease Unit -->
                    <div class="panel">
                        <div class="panel-header"><span><i class="fa-solid fa-oil-can"></i> Status Grease Unit</span></div>
                        <div class="panel-body" style="padding:15px;">
                            <div class="grease-progress-list">
                                <div class="grease-progress-item">
                                    <div class="grease-progress-hdr"><span>Sesuai Jadwal</span><strong>74 unit</strong></div>
                                    <div class="grease-bar-bg"><div class="grease-bar-fill" style="width:74%; background:#16a34a;"></div></div>
                                </div>
                                <div class="grease-progress-item">
                                    <div class="grease-progress-hdr"><span>Jatuh Tempo Hari Ini</span><strong style="color:#f59e0b;">12 unit</strong></div>
                                    <div class="grease-bar-bg"><div class="grease-bar-fill" style="width:12%; background:#f59e0b;"></div></div>
                                </div>
                                <div class="grease-progress-item">
                                    <div class="grease-progress-hdr"><span>Terlambat Grease</span><strong style="color:#dc2626;">9 unit</strong></div>
                                    <div class="grease-bar-bg"><div class="grease-bar-fill" style="width:9%; background:#dc2626;"></div></div>
                                </div>
                            </div>

                            <table style="width:100%; font-size:0.8rem;">
                                <thead>
                                    <tr>
                                        <th>Unit</th>
                                        <th>Jam Terakhir</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${greaseStatusData.map(g => `
                                        <tr>
                                            <td><strong>${escapeHtml(g.unit)}</strong></td>
                                            <td>${g.lastHm}</td>
                                            <td><span class="p2h-badge ${g.badge}">${g.status}</span></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>

                            <div class="dash-alert-banner">
                                <i class="fa-solid fa-lightbulb" style="margin-top:2px;"></i>
                                <div>Fokuskan grease pada unit dengan jam operasi tinggi, area pin-bushing, steering linkage, & undercarriage.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel 3C: Jam Kerja Aktual Mekanik -->
                    <div class="panel">
                        <div class="panel-header"><span><i class="fa-solid fa-user-clock"></i> Jam Kerja Aktual Mekanik</span></div>
                        <div class="panel-body" style="padding:15px;">
                            <div class="mechanic-bar-chart">
                                ${mechanicProductivityData.map(m => `
                                    <div class="mechanic-bar-group">
                                        <div class="mechanic-bars-pair">
                                            <div class="bar-actual" style="height:${(m.actual/200)*100}%;" title="Aktual: ${m.actual} Jam"></div>
                                            <div class="bar-target" style="height:${(m.target/200)*100}%;" title="Target: ${m.target} Jam"></div>
                                        </div>
                                        <div class="mechanic-name-lbl">${escapeHtml(m.name)}</div>
                                    </div>
                                `).join('')}
                            </div>

                            <table style="width:100%; font-size:0.8rem;">
                                <thead>
                                    <tr>
                                        <th>Mekanik</th>
                                        <th style="text-align:center;">Jam Aktual</th>
                                        <th style="text-align:center;">Target</th>
                                        <th style="text-align:right;">Produktivitas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${mechanicProductivityData.map(m => `
                                        <tr>
                                            <td><strong>${escapeHtml(m.name)}</strong></td>
                                            <td style="text-align:center; font-weight:bold; color:#0284c7;">${m.actual} jam</td>
                                            <td style="text-align:center; color:var(--text-muted);">${m.target} jam</td>
                                            <td style="text-align:right;"><span class="p2h-badge ${m.color}">${m.pct}%</span></td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ROW 4: TREN BIAYA PERBAIKAN BULANAN & QUICK ACTIONS (GAMBAR 4) -->
            <div class="dash-analytics-row">
                <div class="dash-grid-2">
                    <!-- Left: 12-Month Maintenance Cost Trend Bar Chart -->
                    <div class="panel">
                        <div class="panel-header"><span><i class="fa-solid fa-chart-simple"></i> Tren Biaya Perbaikan Bulanan</span></div>
                        <div class="panel-body" style="padding:20px 15px 15px 15px;">
                            <div class="cost-bar-chart">
                                <div class="cost-y-axis">
                                    <span>Rp 250 Jt</span>
                                    <span>Rp 200 Jt</span>
                                    <span>Rp 150 Jt</span>
                                    <span>Rp 100 Jt</span>
                                    <span>Rp 50 Jt</span>
                                    <span>Rp 0 Jt</span>
                                </div>
                                ${monthlyCostTrendData.map(c => `
                                    <div class="cost-col">
                                        <div class="cost-bar-fill" style="height:${(c.val/250)*100}%;" title="${c.month}: Rp ${c.val} Jt"></div>
                                        <div class="cost-month-lbl">${c.month.slice(0,3)} ${c.month.slice(-2)}</div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>

                    <!-- Right: Quick Actions Widget -->
                    <div class="panel">
                        <div class="panel-header"><span><i class="fa-solid fa-bolt"></i> Quick Actions</span></div>
                        <div class="panel-body" style="padding:15px;">
                            <div class="quick-action-list">
                                <a class="quick-action-item" onclick="window.openModal('modalNewAsset')">
                                    <i class="fa-solid fa-plus-circle"></i> Tambah Aset Baru
                                </a>
                                <a class="quick-action-item" onclick="window.openNewWOModal()">
                                    <i class="fa-solid fa-wrench"></i> Buat Permintaan Maintenance
                                </a>
                                <a class="quick-action-item" onclick="window.showView('logistics', '', 'menu-logistics')">
                                    <i class="fa-solid fa-cart-plus"></i> Buat Pesanan Barang Logistik
                                </a>
                                <a class="quick-action-item" onclick="window.showView('pm')">
                                    <i class="fa-solid fa-calendar-plus"></i> Jadwalkan Maintenance
                                </a>
                                <a class="quick-action-item" onclick="window.showView('reports')">
                                    <i class="fa-solid fa-file-export"></i> Buat Laporan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // SVG Donut Helper Generator
    function renderSvgDonut(segments) {
        let accumulatedPct = 0;
        const circumference = 2 * Math.PI * 40; // r=40 -> 251.32

        const circlesSvg = segments.map(seg => {
            const dashArray = `${(seg.pct / 100) * circumference} ${circumference}`;
            const dashOffset = -((accumulatedPct / 100) * circumference);
            accumulatedPct += seg.pct;

            return `<circle cx="50" cy="50" r="40" fill="transparent" stroke="${seg.color}" stroke-width="15" stroke-dasharray="${dashArray}" stroke-dashoffset="${dashOffset}"></circle>`;
        }).join('');

        return `
            <svg class="donut-svg" viewBox="0 0 100 100">
                ${circlesSvg}
            </svg>
        `;
    }

    function escapeHtml(unsafe) {
        return (unsafe || '').toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Auto Mount
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initExecutiveAnalyticsPanels);
    } else {
        initExecutiveAnalyticsPanels();
    }
})();
