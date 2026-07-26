(function () {
    'use strict';

    // =========================================================================
    // 1. DOMAIN DATA FROM TELEMATICS (KOMTRAX Jan 2026) & STANDBY FLEET REKAP
    // =========================================================================

    // Data 1: Komtrax Telematics 18 Units (from 20_BRA_KOMTRAX_Januari_2026.md)
    const komtraxFleetData = [
        { no: 1, model: 'D85ESS-2', sn: 'J21020', branch: 'PKB PEKANBARU', smr: 390.3, lastComm: '02/01/2026 06:08', days: 0, hours: 0, actualHours: 0, actualRatio: 0, eModeRatio: 0, travelRatio: 0, diggingRatio: 0, hoistRatio: 0, fuelLiters: 0, fuelLPH: 0, idlingRatio: 0, status: 'No Activity' },
        { no: 2, model: 'GD535-5', sn: '2066', branch: 'PKB PEKANBARU', smr: 3791.8, lastComm: '02/01/2026 14:14', days: 29, hours: 171.0, actualHours: 105.7, actualRatio: 61.8, eModeRatio: 61.7, travelRatio: 61.3, diggingRatio: 0, hoistRatio: 0, fuelLiters: 889.6, fuelLPH: 5.2, idlingRatio: 38.2, status: 'Active' },
        { no: 3, model: 'GD535-5', sn: '2259', branch: 'PLB PALEMBANG', smr: 1666.5, lastComm: '02/01/2026 06:23', days: 30, hours: 159.8, actualHours: 75.7, actualRatio: 47.4, eModeRatio: 46.8, travelRatio: 47.3, diggingRatio: 0, hoistRatio: 0, fuelLiters: 841.0, fuelLPH: 5.3, idlingRatio: 52.6, status: 'High Idling' },
        { no: 4, model: 'PC195LC-8', sn: 'J10309', branch: 'MDN MEDAN', smr: 4098.9, lastComm: '12/20/2025 13:29', days: 0, hours: 0, actualHours: 0, actualRatio: 0, eModeRatio: 0, travelRatio: 0, diggingRatio: 0, hoistRatio: 0, fuelLiters: 0, fuelLPH: 0, idlingRatio: 0, status: 'Comm Offline' },
        { no: 5, model: 'PC200-10M0', sn: 'C51502', branch: 'PKB PEKANBARU', smr: 6357.9, lastComm: '01/31/2026 01:06', days: 24, hours: 182.0, actualHours: 102.4, actualRatio: 56.3, eModeRatio: 41.2, travelRatio: 10.5, diggingRatio: 14.6, hoistRatio: 8.9, fuelLiters: 1960.4, fuelLPH: 10.8, idlingRatio: 43.7, status: 'Active' },
        { no: 6, model: 'PC200-10M0', sn: 'C51503', branch: 'PKB PEKANBARU', smr: 5626.2, lastComm: '01/31/2026 07:55', days: 25, hours: 187.7, actualHours: 124.4, actualRatio: 66.3, eModeRatio: 36.0, travelRatio: 9.4, diggingRatio: 20.2, hoistRatio: 12.9, fuelLiters: 2911.0, fuelLPH: 15.5, idlingRatio: 33.7, status: 'Top Workload' },
        { no: 7, model: 'PC200-10M0', sn: 'DBCH0302', branch: 'PLB PALEMBANG', smr: 8767.4, lastComm: '02/01/2026 06:22', days: 15, hours: 28.3, actualHours: 12.3, actualRatio: 43.7, eModeRatio: 42.3, travelRatio: 21.8, diggingRatio: 3.7, hoistRatio: 2.1, fuelLiters: 134.3, fuelLPH: 4.7, idlingRatio: 56.3, status: 'High Idling' },
        { no: 8, model: 'PC200-10M0', sn: 'DBCH0366', branch: 'PKB PEKANBARU', smr: 6514.7, lastComm: '01/31/2026 15:31', days: 23, hours: 174.9, actualHours: 73.7, actualRatio: 42.1, eModeRatio: 35.5, travelRatio: 10.5, diggingRatio: 7.7, hoistRatio: 5.5, fuelLiters: 1332.7, fuelLPH: 7.6, idlingRatio: 57.9, status: 'High Idling' },
        { no: 9, model: 'PC200-10M0', sn: 'DBCH0380', branch: 'PKB PEKANBARU', smr: 18082.6, lastComm: '02/01/2026 10:00', days: 24, hours: 169.9, actualHours: 73.9, actualRatio: 43.5, eModeRatio: 22.2, travelRatio: 23.5, diggingRatio: 3.2, hoistRatio: 2.2, fuelLiters: 1060.3, fuelLPH: 6.2, idlingRatio: 56.5, status: 'High Idling' },
        { no: 10, model: 'PC200-10M0', sn: 'DBCH1784', branch: 'PLB PALEMBANG', smr: 4804.1, lastComm: '01/31/2026 07:45', days: 15, hours: 96.3, actualHours: 71.0, actualRatio: 73.7, eModeRatio: 58.2, travelRatio: 9.5, diggingRatio: 20.5, hoistRatio: 11.6, fuelLiters: 1237.7, fuelLPH: 12.8, idlingRatio: 26.3, status: 'High Efficiency' },
        { no: 11, model: 'PC200-10M0', sn: 'DBCH1801', branch: 'PLB PALEMBANG', smr: 5261.9, lastComm: '02/01/2026 06:51', days: 22, hours: 121.1, actualHours: 49.0, actualRatio: 40.5, eModeRatio: 15.9, travelRatio: 12.1, diggingRatio: 9.2, hoistRatio: 6.8, fuelLiters: 1073.9, fuelLPH: 8.9, idlingRatio: 59.5, status: 'High Idling' },
        { no: 12, model: 'PC200-10M0', smr: 1737.9, sn: 'DBCH2244', branch: 'PKB PEKANBARU', lastComm: '01/31/2026 15:30', days: 23, hours: 118.6, actualHours: 60.2, actualRatio: 50.8, eModeRatio: 9.4, travelRatio: 14.6, diggingRatio: 10.5, hoistRatio: 7.8, fuelLiters: 1261.6, fuelLPH: 10.6, idlingRatio: 49.2, status: 'Active' },
        { no: 13, model: 'PC200-10M0', sn: 'DBCH2245', branch: 'PKB PEKANBARU', smr: 1658.6, lastComm: '02/01/2026 06:39', days: 24, hours: 146.3, actualHours: 85.1, actualRatio: 58.2, eModeRatio: 2.0, travelRatio: 17.0, diggingRatio: 10.7, hoistRatio: 7.6, fuelLiters: 1741.8, fuelLPH: 11.9, idlingRatio: 41.8, status: 'Active' },
        { no: 14, model: 'PC200-10M0', sn: 'DBCH2941', branch: 'PKB PEKANBARU', smr: 836.1, lastComm: '01/31/2026 07:54', days: 22, hours: 159.9, actualHours: 62.3, actualRatio: 39.0, eModeRatio: 29.0, travelRatio: 15.1, diggingRatio: 6.2, hoistRatio: 3.5, fuelLiters: 1258.8, fuelLPH: 7.9, idlingRatio: 61.0, status: 'Critical Idling' },
        { no: 15, model: 'PC210-10M0', sn: 'C01530', branch: 'PKB PEKANBARU', smr: 12383.0, lastComm: '02/01/2026 10:05', days: 6, hours: 16.9, actualHours: 13.6, actualRatio: 80.4, eModeRatio: 80.5, travelRatio: 15.9, diggingRatio: 14.3, hoistRatio: 8.5, fuelLiters: 211.5, fuelLPH: 12.5, idlingRatio: 19.6, status: 'High Efficiency' },
        { no: 16, model: 'PC210-10M0', sn: 'C01531', branch: 'SMG SEMARANG', smr: 8839.6, lastComm: '07/20/2025 02:49', days: 0, hours: 0, actualHours: 0, actualRatio: 0, eModeRatio: 0, travelRatio: 0, diggingRatio: 0, hoistRatio: 0, fuelLiters: 0, fuelLPH: 0, idlingRatio: 0, status: 'Comm Offline' },
        { no: 17, model: 'PC210-10M0', sn: 'C07004', branch: 'PLB PALEMBANG', smr: 1545.3, lastComm: '02/01/2026 08:26', days: 31, hours: 171.2, actualHours: 83.6, actualRatio: 48.8, eModeRatio: 47.9, travelRatio: 21.4, diggingRatio: 3.8, hoistRatio: 2.7, fuelLiters: 1409.1, fuelLPH: 8.2, idlingRatio: 51.2, status: 'High Idling' },
        { no: 18, model: 'PC210-10M0', sn: 'C07076', branch: 'PLB PALEMBANG', smr: 1570.8, lastComm: '02/01/2026 11:03', days: 29, hours: 142.2, actualHours: 73.1, actualRatio: 51.4, eModeRatio: 49.0, travelRatio: 14.3, diggingRatio: 4.6, hoistRatio: 4.9, fuelLiters: 1325.1, fuelLPH: 9.3, idlingRatio: 48.6, status: 'Active' }
    ];

    // Data 2: Standby Fleet Audit Rekap (from REKAP_UNIT_STANDBY.md)
    const standbySummary = {
        totalUnits: 48,
        locations: [
            { name: 'YARD DURI', count: 35, ratio: 72.9, dumpTrucks: 24, excavators: 2, compactors: 6, bulldozers: 3, trados: 0 },
            { name: 'YARD PRABUMULIH', count: 13, ratio: 27.1, dumpTrucks: 3, excavators: 6, compactors: 0, bulldozers: 2, trados: 2 }
        ],
        categories: [
            { type: 'Dump Truck', count: 27, ratio: 56.2, impact: 'Tinggi (Rugi Kapasitas Hauling)' },
            { type: 'Excavator', count: 8, ratio: 16.7, impact: 'Sedang (Rugi Kapasitas Loading)' },
            { type: 'Compactor', count: 6, ratio: 12.5, impact: 'Sedang (Rugi Kapasitas Compacting)' },
            { type: 'Bulldozer', count: 5, ratio: 10.4, impact: 'Sedang (Rugi Kapasitas Stripping)' },
            { type: 'Trado Heavy Transport', count: 2, ratio: 4.2, impact: 'Rendah (Peralatan Mobilisasi)' }
        ]
    };

    // Data 3: Fleet Overall KPIs (Aggregate metrics)
    const fleetKPIs = {
        physicalAvailability: 92.4, // Target >= 90%
        useOfAvailability: 81.8,     // Target >= 80%
        breakdownRate: 7.6,          // Target <= 10%
        utilizationRate: 52.1,       // Fleet average actual working ratio
        mtbf: 114.5,                 // Hours between failures
        mttr: 3.8,                   // Hours per repair
        totalWorkingHours: 2046.1,
        totalActualHours: 1066.0,
        totalFuelConsumed: 18648.8,
        avgFuelLPH: 9.11,
        avgIdlingRatio: 46.41
    };

    // =========================================================================
    // 2. MAIN MODULE RENDER FUNCTION
    // =========================================================================

    function createProductivityModule() {
        const container = document.getElementById('productivityModule');
        if (!container) return;

        container.innerHTML = `
            <div class="prod-container">
                <!-- Header -->
                <div class="prod-header">
                    <div class="prod-header-title">
                        <h2><i class="fa-solid fa-chart-line text-primary"></i> Manajemen Produktivitas & Fleet Availability (Telematics & KPI)</h2>
                        <p>Monitoring Physical Availability (PA), Use of Availability (UA), Rekonsiliasi Telematika KOMTRAX, dan Audit Fleet Standby</p>
                    </div>
                    <button class="btn btn-primary" onclick="window.exportProductivityReport()"><i class="fa-solid fa-file-csv"></i> Ekspor Laporan Produktivitas</button>
                </div>

                <!-- Nav Tabs -->
                <div class="prod-nav-tabs">
                    <button class="prod-tab-btn active" data-prod-tab="tab-kpi-dash"><i class="fa-solid fa-gauge-high"></i> Dashboard Availability & KPI</button>
                    <button class="prod-tab-btn" data-prod-tab="tab-komtrax-table"><i class="fa-solid fa-satellite-dish"></i> Rekonsiliasi Telematika KOMTRAX</button>
                    <button class="prod-tab-btn" data-prod-tab="tab-idling-anomaly"><i class="fa-solid fa-triangle-exclamation"></i> Idling Anomaly & Fuel Loss</button>
                    <button class="prod-tab-btn" data-prod-tab="tab-standby-fleet"><i class="fa-solid fa-boxes-stacked"></i> Audit Fleet Standby (48 Unit)</button>
                </div>

                <!-- TAB 1: AVAILABILITY & KPI DASHBOARD -->
                <div class="prod-tab-content active" id="tab-kpi-dash">
                    <div class="prod-cards-grid">
                        <div class="prod-card success">
                            <div class="prod-card-info">
                                <h4>Physical Availability (PA)</h4>
                                <div class="prod-val">${fleetKPIs.physicalAvailability}%</div>
                                <div class="prod-sub">Target Benchmark: ≥ 90.0%</div>
                            </div>
                            <div class="prod-card-icon"><i class="fa-solid fa-check-double text-success"></i></div>
                        </div>
                        <div class="prod-card success">
                            <div class="prod-card-info">
                                <h4>Use of Availability (UA)</h4>
                                <div class="prod-val">${fleetKPIs.useOfAvailability}%</div>
                                <div class="prod-sub">Target Benchmark: ≥ 80.0%</div>
                            </div>
                            <div class="prod-card-icon"><i class="fa-solid fa-person-digging text-success"></i></div>
                        </div>
                        <div class="prod-card info">
                            <div class="prod-card-info">
                                <h4>Breakdown Rate (BR)</h4>
                                <div class="prod-val">${fleetKPIs.breakdownRate}%</div>
                                <div class="prod-sub">Target Benchmark: ≤ 10.0%</div>
                            </div>
                            <div class="prod-card-icon"><i class="fa-solid fa-wrench text-info"></i></div>
                        </div>
                        <div class="prod-card warning">
                            <div class="prod-card-info">
                                <h4>Utilization Rate (UT)</h4>
                                <div class="prod-val">${fleetKPIs.utilizationRate}%</div>
                                <div class="prod-sub">Actual Working vs Total Hours</div>
                            </div>
                            <div class="prod-card-icon"><i class="fa-solid fa-chart-pie text-warning"></i></div>
                        </div>
                    </div>

                    <!-- Additional Secondary KPIs -->
                    <div class="prod-cards-grid">
                        <div class="prod-card primary">
                            <div class="prod-card-info">
                                <h4>MTBF (Mean Time Between Failures)</h4>
                                <div class="prod-val">${fleetKPIs.mtbf} Jam</div>
                                <div class="prod-sub">Target Benchmark: ≥ 100 Jam</div>
                            </div>
                            <div class="prod-card-icon"><i class="fa-solid fa-clock"></i></div>
                        </div>
                        <div class="prod-card primary">
                            <div class="prod-card-info">
                                <h4>MTTR (Mean Time to Repair)</h4>
                                <div class="prod-val">${fleetKPIs.mttr} Jam</div>
                                <div class="prod-sub">Target Benchmark: ≤ 4.0 Jam</div>
                            </div>
                            <div class="prod-card-icon"><i class="fa-solid fa-screwdriver"></i></div>
                        </div>
                        <div class="prod-card dark">
                            <div class="prod-card-info">
                                <h4>Total Fuel Fleet Consumed</h4>
                                <div class="prod-val">${fleetKPIs.totalFuelConsumed.toLocaleString()} L</div>
                                <div class="prod-sub">Rata-rata: ${fleetKPIs.avgFuelLPH} L/H</div>
                            </div>
                            <div class="prod-card-icon"><i class="fa-solid fa-gas-pump"></i></div>
                        </div>
                        <div class="prod-card danger">
                            <div class="prod-card-info">
                                <h4>Rata-rata Idling Fleet</h4>
                                <div class="prod-val">${fleetKPIs.avgIdlingRatio}%</div>
                                <div class="prod-sub">Memerlukan Evaluasi Sopir/Mekanik</div>
                            </div>
                            <div class="prod-card-icon"><i class="fa-solid fa-fire"></i></div>
                        </div>
                    </div>

                    <!-- Formula Guide Panel -->
                    <div class="pk-panel">
                        <div class="pk-panel-header">
                            <span><i class="fa-solid fa-square-root-variable"></i> Panduan Standar Formula Perhitungan Availability & Efisiensi BRA</span>
                        </div>
                        <div class="pk-panel-body">
                            <div class="prod-formula-grid">
                                <div class="prod-formula-box">
                                    <h4><span>Physical Availability (PA)</span> <span class="prod-badge prod-badge-success">Target ≥ 90%</span></h4>
                                    <code>PA = (Scheduled Hours - Breakdown Hours) / Scheduled Hours × 100%</code>
                                    <p style="font-size:0.8rem; color:var(--text-muted);">Mengukur kesiapan fisik alat berat untuk beroperasi bebas dari kerusakan mekanis.</p>
                                </div>
                                <div class="prod-formula-box">
                                    <h4><span>Use of Availability (UA)</span> <span class="prod-badge prod-badge-success">Target ≥ 80%</span></h4>
                                    <code>UA = Operating Hours / (Scheduled Hours - Breakdown Hours) × 100%</code>
                                    <p style="font-size:0.8rem; color:var(--text-muted);">Mengukur efektivitas pemanfaatan unit yang sedang berstatus siap pakai (Ready).</p>
                                </div>
                                <div class="prod-formula-box">
                                    <h4><span>Breakdown Rate (BR)</span> <span class="prod-badge prod-badge-info">Target ≤ 10%</span></h4>
                                    <code>BR = Breakdown Hours / Scheduled Hours × 100%</code>
                                    <p style="font-size:0.8rem; color:var(--text-muted);">Persentase total jam mati/kerusakan terhadap total jam kerja terjadwal.</p>
                                </div>
                                <div class="prod-formula-box">
                                    <h4><span>Mean Time Between Failures (MTBF)</span> <span class="prod-badge prod-badge-success">Target ≥ 100h</span></h4>
                                    <code>MTBF = Total Operating Time / Jumlah Kejadian Breakdown</code>
                                    <p style="font-size:0.8rem; color:var(--text-muted);">Rata-rata selang waktu jam kerja di antara dua insiden kerusakan.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: TELEMATICS KOMTRAX RECONCILIATION -->
                <div class="prod-tab-content" id="tab-komtrax-table">
                    <div class="pk-alert pk-alert-info">
                        <i class="fa-solid fa-satellite-dish"></i>
                        <div>
                            <strong>Data Telematika KOMTRAX (Periode Januari 2026):</strong> Menampilkan data jam kerja otomatis dari satelit Komtrax (18 Unit). Bandingkan jam SMR aktual dengan jam kerja laporan manual operator untuk mendeteksi ketidaksesuaian (*discrepancy*).
                        </div>
                    </div>

                    <div class="pk-panel">
                        <div class="pk-panel-header">
                            <span><i class="fa-solid fa-table"></i> Tabel Matriks Telematika KOMTRAX 18 Unit</span>
                            <div class="search-bar" style="max-width:240px;">
                                <input type="text" id="searchKomtrax" placeholder="Cari serial / model..." onkeyup="window.filterKomtraxTable()">
                            </div>
                        </div>
                        <div class="pk-panel-body no-padding">
                            <div class="table-responsive">
                                <table id="tbKomtraxTable">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Model Unit</th>
                                            <th>Serial No.</th>
                                            <th>Cabang Sub Group</th>
                                            <th>SMR (HM)</th>
                                            <th>Hari Kerja</th>
                                            <th>Working Hours</th>
                                            <th>Actual Work (H)</th>
                                            <th>Rasio Actual %</th>
                                            <th>Idling %</th>
                                            <th>Fuel (L/H)</th>
                                            <th>Status Monitoring</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbKomtraxBody">
                                        <!-- Populated via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: IDLING ANOMALY & FUEL LOSS -->
                <div class="prod-tab-content" id="tab-idling-anomaly">
                    <div class="pk-alert pk-alert-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <div>
                            <strong>Peringatan Pemborosan Idling Tinggi (> 50%):</strong> Unit dengan rasio idling tinggi membakar bahan bakar tanpa menghasilkan produksi. Di bawah ini adalah unit yang terdeteksi memiliki rasio idling di atas 50% pada data telematika KOMTRAX.
                        </div>
                    </div>

                    <div class="pk-panel">
                        <div class="pk-panel-header">
                            <span><i class="fa-solid fa-fire"></i> Matriks Unit dengan Rasio Idling Anomaly (> 50%)</span>
                        </div>
                        <div class="pk-panel-body no-padding">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Serial No.</th>
                                            <th>Model Unit</th>
                                            <th>Sub Group</th>
                                            <th>Working Hours</th>
                                            <th>Rasio Idling %</th>
                                            <th>Estimasi Bahan Bakar Idling (L)</th>
                                            <th>Estimasi Pemborosan Finansial (Rp)</th>
                                            <th>Rekomendasi Tindak Lanjut</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbIdlingAnomalyBody">
                                        <!-- Populated via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: STANDBY FLEET AUDIT -->
                <div class="prod-tab-content" id="tab-standby-fleet">
                    <div class="prod-cards-grid">
                        <div class="prod-card danger">
                            <div class="prod-card-info">
                                <h4>Total Unit Standby</h4>
                                <div class="prod-val">48 Unit</div>
                                <div class="prod-sub">Rekap Audit Lapangan</div>
                            </div>
                            <div class="prod-card-icon"><i class="fa-solid fa-pause"></i></div>
                        </div>
                        <div class="prod-card primary">
                            <div class="prod-card-info">
                                <h4>Lokasi Yard Duri</h4>
                                <div class="prod-val">35 Unit (72.9%)</div>
                                <div class="prod-sub">Didominasi Dump Truck</div>
                            </div>
                            <div class="prod-card-icon"><i class="fa-solid fa-warehouse"></i></div>
                        </div>
                        <div class="prod-card warning">
                            <div class="prod-card-info">
                                <h4>Lokasi Yard Prabumulih</h4>
                                <div class="prod-val">13 Unit (27.1%)</div>
                                <div class="prod-sub">Didominasi Excavator</div>
                            </div>
                            <div class="prod-card-icon"><i class="fa-solid fa-building-flag"></i></div>
                        </div>
                    </div>

                    <div class="pk-panel">
                        <div class="pk-panel-header">
                            <span><i class="fa-solid fa-layer-group"></i> Distribusi Kategori Armada Standby & Estimasi Dampak Operasional</span>
                        </div>
                        <div class="pk-panel-body no-padding">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Jenis Kategori Armada</th>
                                            <th>Jumlah Unit Standby</th>
                                            <th>Persentase Terhadap Total Standby</th>
                                            <th>Tingkat Dampak Ke Produksi</th>
                                            <th>Rekomendasi Tindakan Rilis / Re-Alokasi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbStandbyCatBody">
                                        <!-- Populated via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        `;

        bindProdEvents();
        renderProdModuleContent();
    }

    // =========================================================================
    // 3. BINDINGS & RENDER LOGIC
    // =========================================================================

    function bindProdEvents() {
        const tabBtns = document.querySelectorAll('.prod-tab-btn');
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-prod-tab');
                
                tabBtns.forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.prod-tab-content').forEach(c => c.classList.remove('active'));

                btn.classList.add('active');
                const targetContent = document.getElementById(targetId);
                if (targetContent) targetContent.classList.add('active');
            });
        });
    }

    function renderProdModuleContent() {
        renderKomtraxTable();
        renderIdlingAnomalyTable();
        renderStandbyCategoryTable();
    }

    function renderKomtraxTable() {
        const tbody = document.getElementById('tbKomtraxBody');
        if (!tbody) return;

        tbody.innerHTML = komtraxFleetData.map(u => {
            let badgeCls = 'prod-badge-success';
            if (u.status === 'High Idling' || u.status === 'Critical Idling') badgeCls = 'prod-badge-warning';
            else if (u.status === 'Comm Offline' || u.status === 'No Activity') badgeCls = 'prod-badge-danger';
            else if (u.status === 'High Efficiency' || u.status === 'Top Workload') badgeCls = 'prod-badge-info';

            return `
                <tr>
                    <td style="text-align:center;">${u.no}</td>
                    <td><strong>${escapeHtml(u.model)}</strong></td>
                    <td><strong class="text-primary">${escapeHtml(u.sn)}</strong></td>
                    <td><small class="text-muted">${escapeHtml(u.branch)}</small></td>
                    <td style="font-weight:600;">${u.smr.toFixed(1)} h</td>
                    <td style="text-align:center;">${u.days} Hari</td>
                    <td style="text-align:center;">${u.hours.toFixed(1)} h</td>
                    <td style="text-align:center; font-weight:700; color:var(--primary);">${u.actualHours.toFixed(1)} h</td>
                    <td style="text-align:center;">${u.actualRatio.toFixed(1)}%</td>
                    <td style="text-align:center; color:${u.idlingRatio > 50 ? 'var(--danger)' : 'var(--dark)'}; font-weight:${u.idlingRatio > 50 ? '700' : '400'};">${u.idlingRatio.toFixed(1)}%</td>
                    <td style="text-align:center;">${u.fuelLPH ? u.fuelLPH.toFixed(1) + ' L/H' : '-'}</td>
                    <td><span class="prod-badge ${badgeCls}">${escapeHtml(u.status)}</span></td>
                </tr>
            `;
        }).join('');
    }

    function renderIdlingAnomalyTable() {
        const tbody = document.getElementById('tbIdlingAnomalyBody');
        if (!tbody) return;

        const anomalyUnits = komtraxFleetData.filter(u => u.idlingRatio >= 50);
        const fuelCostPerLiter = 14500; // Rp 14,500 per liter solar industri

        tbody.innerHTML = anomalyUnits.map(u => {
            const estimatedIdleFuel = (u.fuelLiters * (u.idlingRatio / 100));
            const idleLossCost = estimatedIdleFuel * fuelCostPerLiter;

            return `
                <tr class="prod-anomaly-row">
                    <td><strong class="text-danger">${escapeHtml(u.sn)}</strong></td>
                    <td><strong>${escapeHtml(u.model)}</strong></td>
                    <td>${escapeHtml(u.branch)}</td>
                    <td style="text-align:center;">${u.hours.toFixed(1)} h</td>
                    <td style="text-align:center; font-weight:700; font-size:1.05rem;">${u.idlingRatio.toFixed(1)}%</td>
                    <td style="text-align:center; font-weight:700;">${estimatedIdleFuel.toFixed(1)} L</td>
                    <td style="font-weight:700; color:var(--danger);">Rp ${(idleLossCost / 1000000).toFixed(2)} Jt</td>
                    <td style="font-size:0.82rem;">Audit perilaku operator & matikan mesin saat standby > 5 menit.</td>
                </tr>
            `;
        }).join('');
    }

    function renderStandbyCategoryTable() {
        const tbody = document.getElementById('tbStandbyCatBody');
        if (!tbody) return;

        tbody.innerHTML = standbySummary.categories.map(c => `
            <tr>
                <td><strong>${escapeHtml(c.type)}</strong></td>
                <td style="text-align:center; font-weight:700; font-size:1.05rem;" class="text-danger">${c.count} Unit</td>
                <td style="text-align:center;">${c.ratio}%</td>
                <td><span class="prod-badge ${c.count > 10 ? 'prod-badge-danger' : 'prod-badge-warning'}">${escapeHtml(c.impact)}</span></td>
                <td style="font-size:0.82rem;">Evaluasi ketersediaan proyek & percepat kitting perbaikan PM/Breakdown.</td>
            </tr>
        `).join('');
    }

    window.filterKomtraxTable = function() {
        const input = document.getElementById('searchKomtrax');
        if (!input) return;
        const filter = input.value.toUpperCase();
        const trs = document.getElementById('tbKomtraxTable').getElementsByTagName('tr');

        for (let i = 1; i < trs.length; i++) {
            let visible = false;
            const tds = trs[i].getElementsByTagName('td');
            if (tds.length > 2) {
                const modelTxt = tds[1].textContent || '';
                const snTxt = tds[2].textContent || '';
                if (modelTxt.toUpperCase().indexOf(filter) > -1 || snTxt.toUpperCase().indexOf(filter) > -1) {
                    visible = true;
                }
            }
            trs[i].style.display = visible ? "" : "none";
        }
    };

    window.exportProductivityReport = function() {
        const headers = ['Model', 'Serial No', 'Branch', 'SMR (H)', 'Working Days', 'Working Hours', 'Actual Working Hours', 'Actual Ratio %', 'Idling Ratio %', 'Fuel (L/H)', 'Status'];
        const rows = komtraxFleetData.map(u => [
            u.model, u.sn, u.branch, u.smr, u.days, u.hours, u.actualHours, u.actualRatio, u.idlingRatio, u.fuelLPH, u.status
        ]);
        const csv = [headers, ...rows].map(row => row.map(val => `"${String(val).replace(/"/g, '""')}"`).join(',')).join('\r\n');
        const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'Laporan_Produktivitas_Komtrax_2026.csv';
        link.click();
        alert('Laporan Produktivitas & Fleet Availability berhasil diekspor ke CSV.');
    };

    // Auto Mount
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createProductivityModule);
    } else {
        createProductivityModule();
    }
})();
