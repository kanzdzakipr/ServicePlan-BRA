(function () {
    'use strict';

    // =========================================================================
    // 1. DOMAIN DATA FROM KPI-TEAM DIRECTORY
    // =========================================================================

    // Data 1: Head of Equipment KPI Assessment Template (from Template_KPI_Head_of_Equipment.md)
    const kpiHeadIndicators = [
        { id: 1, aspect: 'RTW & Downtime', indicator: '% Unit Selesai ≤ Target RTW', target: '≥ 90%', score: 5, weight: 15, notes: 'Target RTW final disepakati & lulus test function' },
        { id: 2, aspect: 'RTW & Downtime', indicator: 'Rata-rata Downtime per Unit', target: '≤ Standar', score: 4, weight: 20, notes: 'Dump Truck ≤ 5-7 hari; Tidak ada unit >7 hari / kronis >14 hari' },
        { id: 3, aspect: 'RTW & Downtime', indicator: 'Kepatuhan PM Tepat Waktu', target: '≥ 95%', score: 4, weight: 10, notes: 'PM on-time %; Breakdown akibat PM gagal NIHIL' },
        { id: 4, aspect: 'Percepatan', indicator: 'Waktu Respon Awal Kerusakan', target: '≤ 24 jam', score: 3, weight: 10, notes: 'Respons awal troubleshooting & JO diterbitkan' },
        { id: 5, aspect: 'Percepatan', indicator: 'Keterlambatan karena Spare Part', target: '≤ 10%', score: 1, weight: 10, notes: 'Penurunan kerugian downtime ≥30-40%/bln' },
        { id: 6, aspect: 'Percepatan', indicator: 'Keterlambatan karena Manpower', target: '≤ 5%', score: 1, weight: 5, notes: 'Ketersediaan mekanik, welder, dan vendor' },
        { id: 7, aspect: 'Biaya & Kualitas', indicator: 'Deviasi Biaya Corrective', target: '≤ 110%', score: 1, weight: 10, notes: 'Realisasi biaya vs budget rencana corrective' },
        { id: 8, aspect: 'Biaya & Kualitas', indicator: 'Repeat Breakdown ≤ 30 Hari', target: '≤ 5%', score: 1, weight: 10, notes: 'Kerusakan berulang unit/komponen yang sama' },
        { id: 9, aspect: 'Kepemimpinan', indicator: 'Monitoring & Pelaporan Unit', target: 'Konsisten', score: 1, weight: 5, notes: 'Disiplin update JO mekanik, target RTW, & report harian' },
        { id: 10, aspect: 'Kepemimpinan', indicator: 'Inisiatif Percepatan Perbaikan', target: 'Aktif', score: 1, weight: 5, notes: 'Solusi percepatan (shift tambahan, vendor, prioritas)' }
    ];

    const kpiMonthlyTrend = [
        { month: 'Jan', score: 54, category: 'Tidak efektif / perlu evaluasi' },
        { month: 'Feb', score: null, category: '-' },
        { month: 'Mar', score: null, category: '-' },
        { month: 'Apr', score: null, category: '-' },
        { month: 'Mei', score: null, category: '-' },
        { month: 'Jun', score: null, category: '-' },
        { month: 'Jul', score: null, category: '-' },
        { month: 'Agu', score: null, category: '-' },
        { month: 'Sep', score: null, category: '-' },
        { month: 'Okt', score: null, category: '-' },
        { month: 'Nov', score: null, category: '-' },
        { month: 'Des', score: null, category: '-' }
    ];

    // Data 2: Mechanic Productivity Analysis Feb 2026 (from analisis_produktivitas_mekanik_feb2026.md)
    const mechanicProductivityList = [
        { rank: 1, name: 'Rahmad K', role: 'Mekanik Senior', totalHrs: 92.75, normalHrs: 72.36, overtimeHrs: 20.39, jobCount: 46, avgHrsPerJob: 2.02, delayCount: 2, delayRatio: 4, effectiveness: 44.6 },
        { rank: 2, name: 'Urwatul Usk', role: 'Helper Mekanik', totalHrs: 73.88, normalHrs: 66.54, overtimeHrs: 7.34, jobCount: 67, avgHrsPerJob: 1.10, delayCount: 0, delayRatio: 0, effectiveness: 35.5 },
        { rank: 3, name: 'Joni (Jhoni Ist Kandar)', role: 'Mekanik', totalHrs: 66.83, normalHrs: 58.82, overtimeHrs: 8.01, jobCount: 35, avgHrsPerJob: 1.91, delayCount: 2, delayRatio: 6, effectiveness: 32.1 },
        { rank: 4, name: 'Afriyandi', role: 'Mekanik Senior', totalHrs: 60.10, normalHrs: 54.65, overtimeHrs: 5.45, jobCount: 28, avgHrsPerJob: 2.23, delayCount: 0, delayRatio: 0, effectiveness: 28.9 },
        { rank: 5, name: 'Darmawan', role: 'Mekanik', totalHrs: 54.55, normalHrs: 47.70, overtimeHrs: 6.85, jobCount: 27, avgHrsPerJob: 2.02, delayCount: 4, delayRatio: 15, effectiveness: 26.2 },
        { rank: 6, name: 'Hendrik', role: 'Teknisi Listrik / Welder', totalHrs: 47.07, normalHrs: 45.93, overtimeHrs: 1.13, jobCount: 34, avgHrsPerJob: 1.38, delayCount: 3, delayRatio: 9, effectiveness: 22.6 },
        { rank: 7, name: 'Rezeki Siregar', role: 'Mekanik', totalHrs: 39.71, normalHrs: 37.64, overtimeHrs: 2.07, jobCount: 46, avgHrsPerJob: 0.86, delayCount: 2, delayRatio: 4, effectiveness: 19.1 },
        { rank: 8, name: 'Suwardi', role: 'Mekanik Welding', totalHrs: 35.04, normalHrs: 28.27, overtimeHrs: 6.78, jobCount: 20, avgHrsPerJob: 1.75, delayCount: 0, delayRatio: 0, effectiveness: 16.8 },
        { rank: 9, name: 'Gabriel', role: 'Mekanik', totalHrs: 13.47, normalHrs: 12.98, overtimeHrs: 0.49, jobCount: 11, avgHrsPerJob: 1.22, delayCount: 0, delayRatio: 0, effectiveness: 6.5 },
        { rank: 10, name: 'Agung S', role: 'Mekanik', totalHrs: 6.22, normalHrs: 5.22, overtimeHrs: 1.00, jobCount: 4, avgHrsPerJob: 1.56, delayCount: 1, delayRatio: 25, effectiveness: 3.0 }
    ];

    const topLongestJobs = [
        { id: 567, date: '2026-02-15', unit: 'LB-02', plate: 'B 9012 ZEH', duration: 10.30, mechanics: 'Afriyandi, Darmawan', issue: 'LORY LOWBOY PADA SUSPENSI PATAH & LEPAS' },
        { id: 645, date: '2026-02-22', unit: 'RWI Fab', plate: '-', duration: 10.00, mechanics: 'Rahmad K, Suwardi', issue: 'Pabrikasi Whellcock RWI' },
        { id: 646, date: '2026-02-23', unit: 'RWI Fab', plate: '-', duration: 10.00, mechanics: 'Rahmad K, Suwardi', issue: 'Pabrikasi Whellcock RWI' },
        { id: 430, date: '2026-02-06', unit: 'DT-056', plate: 'B 9115 ZYT', duration: 8.92, mechanics: 'Joni (Jhoni Ist Kandar)', issue: 'DISMANTLE DISC CLUTH LIMIT' },
        { id: 618, date: '2026-02-20', unit: 'DT-021', plate: 'B 9121 EO', duration: 8.47, mechanics: 'Darmawan, Rahmad K', issue: 'Merubah dimensi underun RH/LH, APAR & pabrikasi bracket' },
        { id: 415, date: '2026-02-05', unit: 'DT-06', plate: 'B 9102 ZYT', duration: 8.37, mechanics: 'Afriyandi, Rahmad K, Urwatul Usk', issue: 'Proses lanjutan pemasangan dish clutch' },
        { id: 462, date: '2026-02-09', unit: 'DT-101', plate: 'BM 9682 JO', duration: 8.33, mechanics: 'Agung S, Rahmad K', issue: 'ELECTRICAL SYSTEM ERROR (kabel short)' },
        { id: 466, date: '2026-02-09', unit: 'DT-096', plate: 'BM 9287 JO', duration: 8.30, mechanics: 'Rahmad K, Suwardi', issue: 'Engsel Pintu Ombeng patah & kunci pintu bengkok' },
        { id: 568, date: '2026-02-15', unit: 'DT-010', plate: 'B 9701 PYW', duration: 7.77, mechanics: 'Hendrik, Rahmad K', issue: 'Muffler broken & Lantai Dump Robek' },
        { id: 560, date: '2026-02-14', unit: 'DT-097', plate: 'BM 9510 QO', duration: 7.58, mechanics: 'Afriyandi', issue: 'Pengantian tingtong 4pcs, alaram, kunci pintu, oli hidrolik' }
    ];

    // Data 3: Maintenance Planner Qualification & Evaluation (from Evaluasi_P_Martin_dan_Standar_Planner.md)
    const plannerCompetencyMatrix = [
        { competency: 'Dasar Maintenance Alat Berat', standard: 'D3/S1 Teknik Mesin', actual: 'D3 Akuntansi', targetLvl: 4, actualLvl: 2, gap: -2, action: 'Pelatihan Dasar Teknik Mesin Alat Berat & System Diagnosis' },
        { competency: 'PM Scheduling & Meter Reading', standard: 'Update Real-Time Status KM/HM', actual: 'Jadwal terbuat, Update KM tertinggal', targetLvl: 4, actualLvl: 3, gap: -1, action: 'Standardisasi Audit Meter Reading Harian Operator' },
        { competency: 'Estimasi Durasi Repair', standard: 'Akurat per Komponen', actual: 'Belum menguasai estimasi waktu', targetLvl: 4, actualLvl: 2, gap: -2, action: 'Praktik & Benchmark Standard Job Time per Kategori Breakdown' },
        { competency: 'Spare Parts Control & Kitting', standard: 'Parts Ready sebelum Job Start', actual: 'Memahami Logistik, Parts Kitting belum terikat JO', targetLvl: 4, actualLvl: 3, gap: -1, action: 'Integrasi Form SPB dengan Nomor WO/PM secara Wajib' },
        { competency: 'Komunikasi Teknis Mekanik', standard: 'Kondusif & Responsif', actual: 'Komunikasi kurang terkondusif', targetLvl: 4, actualLvl: 2, gap: -2, action: 'SOP Briefing Pagi & Penerbitan JO Awal sebelum Pekerjaan Dimulai' },
        { competency: 'Analisis Machine History (CMMS)', standard: 'Prediksi & Cegah Breakdown', actual: 'Input Data aktif, Analisis belum jalan', targetLvl: 4, actualLvl: 2, gap: -2, action: 'Review Mingguan Unit Kronis (>14 Hari) & Repeat Breakdown' },
        { competency: 'Administrasi & Keuangan (KESDMAN)', standard: 'Tertib Administrasi', actual: 'Menguasai Administrasi & Akuntansi', targetLvl: 4, actualLvl: 5, gap: 1, action: 'Dipertahankan sebagai keunggulan kontrol dokumen' }
    ];

    // Data 4: Attendance & Overtime Jan 2026 (from ABSEN_DAN_LEMBUR_JANUARI_2026_YARD_KM12.md)
    const attendanceOvertimeLeaderboard = [
        { rank: 1, site: 'YARD KM12', name: 'Suwardi', role: 'Mekanik Welding', kj: 26, kl: 2, o: 3, otHours: 129.0 },
        { rank: 2, site: 'YARD KM12', name: 'Taufiq H', role: 'Security', kj: 26, kl: 2, o: 3, otHours: 124.0 },
        { rank: 3, site: 'YARD KM12', name: 'Hendrik', role: 'Teknisi Listrik / Welder', kj: 25, kl: 3, o: 3, otHours: 118.5 },
        { rank: 4, site: 'YARD KM12', name: 'Jorlan Sibatuara', role: 'Koordinator Security', kj: 26, kl: 2, o: 3, otHours: 116.0 },
        { rank: 5, site: 'YARD KM12', name: 'Firlanda Dolok Saribu', role: 'Helper Mekanik', kj: 25, kl: 2, o: 4, otHours: 115.5 },
        { rank: 6, site: 'YARD KM12', name: 'Afriyandi', role: 'Mekanik', kj: 24, kl: 2, o: 5, otHours: 88.0 },
        { rank: 7, site: 'LAPANGAN', name: 'Soleh Al Muzakar', role: 'Welder', kj: 23, kl: 4, o: 4, otHours: 75.5 },
        { rank: 8, site: 'LAPANGAN', name: 'Joni Septian', role: 'Mekanik', kj: 23, kl: 4, o: 4, otHours: 74.5 },
        { rank: 9, site: 'YARD KM12', name: 'Darmawan', role: 'Mekanik', kj: 24, kl: 2, o: 5, otHours: 70.5 },
        { rank: 10, site: 'LAPANGAN', name: 'Daniel Sitepu', role: 'Mekanik', kj: 22, kl: 4, o: 5, otHours: 68.0 },
        { rank: 11, site: 'YARD KM12', name: 'Wagiman Barutu', role: 'Mekanik', kj: 23, kl: 2, o: 6, otHours: 64.0 },
        { rank: 12, site: 'LAPANGAN', name: 'Rezeki Siregar', role: 'Mekanik', kj: 22, kl: 4, o: 5, otHours: 63.0 },
        { rank: 13, site: 'LAPANGAN', name: 'Urwatul Uska', role: 'Helper Mekanik', kj: 22, kl: 4, o: 5, otHours: 60.0 }
    ];

    // Data 5: Official SPL July 23, 2026 Verification (from SPL_23_JULI_2026_konversi_dan_penjelasan.md)
    const splJulyData = {
        docDate: '23 Juli 2026 (Kamis)',
        location: 'Workshop KM 12',
        window: '16:00 - 17:00 (1 Jam)',
        personnelCount: 2,
        totalPersonHours: '2 Jam-Orang',
        personnel: [
            {
                name: 'Suwardi',
                role: 'Mekanik Welding',
                tasks: [
                    'Finishing safety Underround samping kiri',
                    'Fabrikasi Underround Protection',
                    'Ganti selang sirkulasi air out',
                    'Pasang Kotrek gantungan ban serep WTT BK 8115 EO'
                ],
                startTTD: false,
                endTTD: false
            },
            {
                name: 'Hendrik',
                role: 'Mekanik Welding',
                tasks: [
                    'Fabrikasi & Repair Pintu/kunci Ombeng (plate kropos DT Isuzu ex Prabu)',
                    'Fabrikasi & melengkapi pasang baru Underround Protection',
                    'Repair Safety Underround samping kanan dan kiri'
                ],
                startTTD: false,
                endTTD: false
            }
        ],
        approvals: [
            { role: 'Dibuat oleh', name: 'Samsul Bahri', status: 'Approved / Signed' },
            { role: 'Instruksi Kerja (Head of Equipment)', name: 'Dany Agung', status: 'Approved / Signed' },
            { role: 'Diketahui (Logistic Head)', name: 'Guswan Arizal', status: 'Approved / Signed' },
            { role: 'Diketahui (HRD)', name: 'Rani Simanungkalit', status: 'Pending TTD' },
            { role: 'Disetujui (Asset Manager)', name: 'Widya Apriani', status: 'Pending TTD' }
        ]
    };

    // State Variables for Dynamic Calculator
    let currentHeadKPI = JSON.parse(JSON.stringify(kpiHeadIndicators));

    // =========================================================================
    // 2. MAIN RENDER FUNCTION & HTML INJECTION
    // =========================================================================

    function createPeopleKPIModule() {
        const container = document.getElementById('peopleKpiModule');
        if (!container) return;

        container.innerHTML = `
            <div class="pk-container">
                <!-- Header -->
                <div class="pk-header">
                    <div class="pk-header-title">
                        <h2><i class="fa-solid fa-users-gear text-primary"></i> Manajemen People, Performa & KPI Tim Maintenance</h2>
                        <p>Integrasi terpadu Penilaian Head of Equipment, Produktivitas Mekanik, Evaluasi Planner, dan Audit Lembur SPL</p>
                    </div>
                    <button class="btn btn-primary" onclick="window.exportKPIReport()"><i class="fa-solid fa-download"></i> Ekspor Laporan KPI</button>
                </div>

                <!-- Nav Tabs -->
                <div class="pk-nav-tabs">
                    <button class="pk-tab-btn active" data-pk-tab="tab-head"><i class="fa-solid fa-award"></i> KPI Head of Equipment</button>
                    <button class="pk-tab-btn" data-pk-tab="tab-mechanic"><i class="fa-solid fa-screwdriver-wrench"></i> Produktivitas Mekanik</button>
                    <button class="pk-tab-btn" data-pk-tab="tab-planner"><i class="fa-solid fa-clipboard-user"></i> Evaluasi Maintenance Planner</button>
                    <button class="pk-tab-btn" data-pk-tab="tab-attendance"><i class="fa-solid fa-clock"></i> Absensi & Lembur Tim</button>
                </div>

                <!-- TAB 1: KPI HEAD OF EQUIPMENT -->
                <div class="pk-tab-content active" id="tab-head">
                    <div class="pk-cards-grid">
                        <div class="pk-card danger" id="cardHeadScore">
                            <div class="pk-card-info">
                                <h4>Total Skor KPI Head</h4>
                                <div class="pk-val" id="lblTotalHeadScore">54 / 100</div>
                                <div class="pk-sub" id="lblHeadCategory">Kategori: Tidak Efektif / Evaluasi</div>
                            </div>
                            <div class="pk-card-icon"><i class="fa-solid fa-gauge-simple-high"></i></div>
                        </div>
                        <div class="pk-card success">
                            <div class="pk-card-info">
                                <h4>Target RTW & Downtime</h4>
                                <div class="pk-val">31 / 45</div>
                                <div class="pk-sub">Bobot Total Aspek: 45%</div>
                            </div>
                            <div class="pk-card-icon"><i class="fa-solid fa-truck-fast"></i></div>
                        </div>
                        <div class="pk-card warning">
                            <div class="pk-card-info">
                                <h4>Percepatan & Logistics</h4>
                                <div class="pk-val">9 / 25</div>
                                <div class="pk-sub">Bobot Total Aspek: 25%</div>
                            </div>
                            <div class="pk-card-icon"><i class="fa-solid fa-boxes-packing"></i></div>
                        </div>
                        <div class="pk-card info">
                            <div class="pk-card-info">
                                <h4>Biaya, Quality & Leadership</h4>
                                <div class="pk-val">14 / 30</div>
                                <div class="pk-sub">Bobot Total Aspek: 30%</div>
                            </div>
                            <div class="pk-card-icon"><i class="fa-solid fa-user-shield"></i></div>
                        </div>
                    </div>

                    <div class="pk-alert pk-alert-warning">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <div>
                            <strong>Catatan Audit Templat KPI Head of Equipment:</strong> Total skor saat ini (54) berada di kategori <span class="pk-badge pk-badge-danger">Perlu Evaluasi Serius</span>. Skor dipicu oleh keterlambatan spare part (Skor 1), keterlambatan manpower (Skor 1), deviasi biaya corrective (Skor 1), dan repeat breakdown (Skor 1). Anda dapat mengubah skor (1-5) pada tabel di bawah untuk mensimulasikan nilai bobot secara otomatis.
                        </div>
                    </div>

                    <div class="pk-panel">
                        <div class="pk-panel-header">
                            <span><i class="fa-solid fa-list-check"></i> Matriks 10 Indikator Kinerja Utama (Head of Equipment)</span>
                            <span class="text-muted" style="font-size:0.85rem;">Formula: Nilai Bobot = Skor × Bobot ÷ 5</span>
                        </div>
                        <div class="pk-panel-body no-padding">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th style="width:40px;">No</th>
                                            <th>Aspek KPI</th>
                                            <th>Indikator Kinerja</th>
                                            <th>Target Standard</th>
                                            <th style="width:110px;">Skor (1-5)</th>
                                            <th style="width:90px;">Bobot (%)</th>
                                            <th style="width:100px;">Nilai Bobot</th>
                                            <th>Catatan & Definisi Operasional</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbHeadKPIBody">
                                        <!-- Populated via JS -->
                                    </tbody>
                                    <tfoot>
                                        <tr style="background:#f8fafc; font-weight:700;">
                                            <td colspan="5" style="text-align:right;">TOTAL SKOR AKUMULASI:</td>
                                            <td>100%</td>
                                            <td id="tfTotalScore" style="font-size:1.1rem; color:var(--primary);">54</td>
                                            <td id="tfTotalInterpretation"><span class="pk-badge pk-badge-danger">Tidak efektif / perlu evaluasi serius (<65)</span></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Trend Graph Container -->
                    <div class="pk-panel">
                        <div class="pk-panel-header">
                            <span><i class="fa-solid fa-chart-line"></i> Rekapitualisasi Trend Nilai KPI Bulanan (Januari - Desember 2026)</span>
                        </div>
                        <div class="pk-panel-body">
                            <div style="display:flex; gap:10px; justify-content:space-between; align-items:flex-end; height:180px; border-bottom:2px solid var(--border); padding-bottom:10px;" id="pkHeadMonthlyChart">
                                <!-- Bars populated via JS -->
                            </div>
                            <div style="display:flex; justify-content:space-between; margin-top:10px; font-size:0.8rem; color:var(--text-muted);" id="pkHeadMonthlyLabels">
                                <!-- Labels via JS -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: PRODUKTIVITAS MEKANIK -->
                <div class="pk-tab-content" id="tab-mechanic">
                    <div class="pk-cards-grid">
                        <div class="pk-card primary">
                            <div class="pk-card-info">
                                <h4>Total Jam Dialokasikan</h4>
                                <div class="pk-val">489.62 Jam</div>
                                <div class="pk-sub">10 Mekanik Tim Utama</div>
                            </div>
                            <div class="pk-card-icon"><i class="fa-solid fa-business-time"></i></div>
                        </div>
                        <div class="pk-card success">
                            <div class="pk-card-info">
                                <h4>Jam Normal (≤16:00)</h4>
                                <div class="pk-val">430.11 Jam</div>
                                <div class="pk-sub">87.8% Jam Operasional</div>
                            </div>
                            <div class="pk-card-icon"><i class="fa-solid fa-sun"></i></div>
                        </div>
                        <div class="pk-card warning">
                            <div class="pk-card-info">
                                <h4>Jam Lembur (>16:00)</h4>
                                <div class="pk-val">59.51 Jam</div>
                                <div class="pk-sub">12.2% Porsi Lembur</div>
                            </div>
                            <div class="pk-card-icon"><i class="fa-solid fa-moon"></i></div>
                        </div>
                        <div class="pk-card info">
                            <div class="pk-card-info">
                                <h4>Pencatatan Job (Coverage)</h4>
                                <div class="pk-val">78.2%</div>
                                <div class="pk-sub">308 bertiming dari 394 job</div>
                            </div>
                            <div class="pk-card-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                        </div>
                    </div>

                    <div class="pk-panel">
                        <div class="pk-panel-header">
                            <span><i class="fa-solid fa-ranking-star"></i> Leaderboard Produktivitas Mekanik (Februari 2026 - Benchmark Standard 208 Jam/Bln)</span>
                            <div class="search-bar" style="max-width:240px;">
                                <input type="text" id="searchMechanic" placeholder="Cari nama mekanik..." onkeyup="window.filterMechanicTable()">
                            </div>
                        </div>
                        <div class="pk-panel-body no-padding">
                            <div class="table-responsive">
                                <table id="tbMechanicsTable">
                                    <thead>
                                        <tr>
                                            <th style="width:50px;">Rank</th>
                                            <th>Nama Mekanik</th>
                                            <th>Peran / Posisi</th>
                                            <th>Total Jam</th>
                                            <th>Jam Normal</th>
                                            <th>Jam Lembur</th>
                                            <th>Jumlah Job</th>
                                            <th>Rata Jam/Job</th>
                                            <th>Delay Sparepart</th>
                                            <th>Efektivitas vs 208h</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbMechanicsBody">
                                        <!-- Populated via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Top Longest Jobs -->
                    <div class="pk-panel">
                        <div class="pk-panel-header">
                            <span><i class="fa-solid fa-stopwatch"></i> Top 10 Job Berdurasi Terpanjang (Februari 2026)</span>
                        </div>
                        <div class="pk-panel-body no-padding">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Tanggal</th>
                                            <th>Kode Unit</th>
                                            <th>No. Polisi</th>
                                            <th>Durasi (Jam)</th>
                                            <th>Mekanik Bertugas</th>
                                            <th>Uraian Pekerjaan / Problem</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbLongestJobsBody">
                                        <!-- Populated via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: EVALUASI MAINTENANCE PLANNER -->
                <div class="pk-tab-content" id="tab-planner">
                    <div class="pk-alert pk-alert-info">
                        <i class="fa-solid fa-circle-info"></i>
                        <div>
                            <strong>Evaluasi Kualifikasi & Competency Gap Maintenance Planner:</strong> Membandingkan standar kualifikasi jabatan Planner (D3/S1 Teknik Mesin, CMMS Expert, Estimasi Repair) terhadap profil evaluasi personel (P. Martin - D3 Akuntansi).
                        </div>
                    </div>

                    <div class="pk-matrix-grid">
                        <div class="pk-matrix-card">
                            <h4><span><i class="fa-solid fa-circle-check text-success"></i> Area Keunggulan & Kekuatan</span> <span class="pk-badge pk-badge-success">Strong</span></h4>
                            <div class="pk-matrix-item">
                                <div class="pk-item-lbl"><span>Administrasi & KESDMAN</span> <span>Level 5/5</span></div>
                                <p style="font-size:0.8rem; color:var(--text-muted);">Sangat rapi dalam mengelola kelengkapan dokumen administratif, laporan audit KESDMAN, dan pembukuan.</p>
                            </div>
                            <div class="pk-matrix-item">
                                <div class="pk-item-lbl"><span>Penguasaan Logistik & Parts</span> <span>Level 4/5</span></div>
                                <p style="font-size:0.8rem; color:var(--text-muted);">Memahami alur perpindahan barang gudang dan penginputan reservasi order spare part.</p>
                            </div>
                            <div class="pk-matrix-item">
                                <div class="pk-item-lbl"><span>Penjadwalan Routine PM</span> <span>Level 3/5</span></div>
                                <p style="font-size:0.8rem; color:var(--text-muted);">Telah menyusun jadwal perawatan berkala sesuai kalender kerja.</p>
                            </div>
                        </div>

                        <div class="pk-matrix-card">
                            <h4><span><i class="fa-solid fa-circle-xmark text-danger"></i> Area Kesenjangan (Gap) Utama</span> <span class="pk-badge pk-badge-danger">Needs Training</span></h4>
                            <div class="pk-matrix-item">
                                <div class="pk-item-lbl"><span>Estimasi Durasi Repair</span> <span>Gap: -2 Level</span></div>
                                <p style="font-size:0.8rem; color:var(--text-muted);">Belum mampu mengestimasi durasi pekerjaan breakdown sehingga target RTW kurang akurat.</p>
                            </div>
                            <div class="pk-matrix-item">
                                <div class="pk-item-lbl"><span>Live Update Status Meter (KM/HM)</span> <span>Gap: -1 Level</span></div>
                                <p style="font-size:0.8rem; color:var(--text-muted);">Pembaruan bacaan KM/HM dari lapangan sering terlambat dibanding waktu rilis service.</p>
                            </div>
                            <div class="pk-matrix-item">
                                <div class="pk-item-lbl"><span>Komunikasi Teknis & Penerbitan JO</span> <span>Gap: -2 Level</span></div>
                                <p style="font-size:0.8rem; color:var(--text-muted);">Penerbitan JO awal sebelum pekerjaan dimulai belum konsisten; komunikasi lapangan perlu diperbaiki.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Competency Gap Table -->
                    <div class="pk-panel">
                        <div class="pk-panel-header">
                            <span><i class="fa-solid fa-sliders"></i> Matriks Evaluasi 7 Kompetensi Inti Maintenance Planner</span>
                        </div>
                        <div class="pk-panel-body no-padding">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Kompetensi Inti</th>
                                            <th>Standar Kualifikasi Jabatan</th>
                                            <th>Evaluasi Realita Personel</th>
                                            <th style="width:120px;">Target vs Actual</th>
                                            <th>Gap Status</th>
                                            <th>Rencana Tindak Lanjut (Action Plan)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbPlannerMatrixBody">
                                        <!-- Populated via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- 30-60-90 Roadmap -->
                    <div class="pk-panel">
                        <div class="pk-panel-header">
                            <span><i class="fa-solid fa-map-location-dot"></i> Rencana Pengembangan Kompetensi Planner (Roadmap 30-60-90 Hari)</span>
                        </div>
                        <div class="pk-panel-body">
                            <div class="pk-roadmap-grid">
                                <div class="pk-roadmap-card">
                                    <h4><i class="fa-solid fa-calendar-day text-primary"></i> 30 Hari Pertama (Fondasi)</h4>
                                    <ul>
                                        <li>Pelatihan dasar sistem mekanikal & hidrolik alat berat.</li>
                                        <li>Standardisasi update HM/KM harian via P2H.</li>
                                        <li>Penerbitan JO Awal wajib sebelum mekanik start kerja.</li>
                                    </ul>
                                </div>
                                <div class="pk-roadmap-card days-60">
                                    <h4><i class="fa-solid fa-calendar-week text-warning"></i> 60 Hari Kedua (Penguatan)</h4>
                                    <ul>
                                        <li>Latihan estimasi waktu repair per jenis kerusakan.</li>
                                        <li>Kitting spare part wajib terikat nomor tiket WO/PM.</li>
                                        <li>Review mingguan backlog maintenance & service overdue.</li>
                                    </ul>
                                </div>
                                <div class="pk-roadmap-card days-90">
                                    <h4><i class="fa-solid fa-flag-checkered text-success"></i> 90 Hari Ketiga (Kemandirian)</h4>
                                    <ul>
                                        <li>Memimpin pembuatan Weekly Maintenance Plan.</li>
                                        <li>Menghitung indikator PA, UA, MTBF, dan MTTR mandiri.</li>
                                        <li>Evaluasi ulang matriks kompetensi & sertifikasi POP.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: ABSENSI & LEMBUR TIM -->
                <div class="pk-tab-content" id="tab-attendance">
                    <div class="pk-cards-grid">
                        <div class="pk-card primary">
                            <div class="pk-card-info">
                                <h4>Total Personel Terdata</h4>
                                <div class="pk-val">27 Orang</div>
                                <div class="pk-sub">22 Yard KM12, 5 Lapangan</div>
                            </div>
                            <div class="pk-card-icon"><i class="fa-solid fa-users"></i></div>
                        </div>
                        <div class="pk-card warning">
                            <div class="pk-card-info">
                                <h4>Total Jam Lembur Audit</h4>
                                <div class="pk-val">1.397 Jam</div>
                                <div class="pk-sub">31 Hari Periode Januari 2026</div>
                            </div>
                            <div class="pk-card-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        </div>
                        <div class="pk-card success">
                            <div class="pk-card-info">
                                <h4>Lembur Welder Terbanyak</h4>
                                <div class="pk-val">129.0 Jam</div>
                                <div class="pk-sub">Suwardi (Mekanik Welding)</div>
                            </div>
                            <div class="pk-card-icon"><i class="fa-solid fa-fire"></i></div>
                        </div>
                        <div class="pk-card info">
                            <div class="pk-card-info">
                                <h4>Lembur Mekanik Terbanyak</h4>
                                <div class="pk-val">88.0 Jam</div>
                                <div class="pk-sub">Afriyandi (Mekanik Yard)</div>
                            </div>
                            <div class="pk-card-icon"><i class="fa-solid fa-wrench"></i></div>
                        </div>
                    </div>

                    <!-- Attendance Leaderboard Table -->
                    <div class="pk-panel">
                        <div class="pk-panel-header">
                            <span><i class="fa-solid fa-list-ol"></i> Rekapitulasi Jam Lembur & Absensi Personel (Januari 2026)</span>
                        </div>
                        <div class="pk-panel-body no-padding">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th style="width:50px;">Rank</th>
                                            <th>Lokasi Site</th>
                                            <th>Nama Personel</th>
                                            <th>Jabatan / Posisi</th>
                                            <th>Hari Kerja (KJ)</th>
                                            <th>Kerja Libur (KL)</th>
                                            <th>Hari Off (O)</th>
                                            <th>Total Jam Lembur Audit</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbAttendanceBody">
                                        <!-- Populated via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Official SPL Verification Widget -->
                    <div class="pk-panel">
                        <div class="pk-panel-header">
                            <span><i class="fa-solid fa-file-signature"></i> Audit Verifikasi Surat Perintah Lembur Resmi (SPL 23 Juli 2026)</span>
                            <span class="pk-badge pk-badge-warning">Jendela Lembur: 16:00 - 17:00 (Workshop KM12)</span>
                        </div>
                        <div class="pk-panel-body">
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                                <div>
                                    <h4 style="font-size:0.95rem; margin-bottom:10px;"><i class="fa-solid fa-user-gear text-primary"></i> Personel 1: Suwardi (Mekanik Welding)</h4>
                                    <ul style="padding-left:18px; font-size:0.85rem; line-height:1.6; color:var(--text-main);">
                                        <li>Finishing safety Underround samping kiri</li>
                                        <li>Fabrikasi Underround Protection</li>
                                        <li>Ganti selang sirkulasi air out</li>
                                        <li>Pasang Kotrek gantungan ban serep WTT BK 8115 EO</li>
                                    </ul>
                                </div>
                                <div>
                                    <h4 style="font-size:0.95rem; margin-bottom:10px;"><i class="fa-solid fa-user-gear text-primary"></i> Personel 2: Hendrik (Mekanik Welding)</h4>
                                    <ul style="padding-left:18px; font-size:0.85rem; line-height:1.6; color:var(--text-main);">
                                        <li>Fabrikasi & Repair Pintu/kunci Ombeng (plate kropos DT Isuzu ex Prabu)</li>
                                        <li>Fabrikasi & melengkapi pasang baru Underround Protection</li>
                                        <li>Repair Safety Underround samping kanan dan kiri</li>
                                    </ul>
                                </div>
                            </div>

                            <hr style="margin:15px 0; border:none; border-top:1px solid var(--border);">

                            <h4 style="font-size:0.9rem; color:var(--dark); margin-bottom:8px;">Matriks Otorisasi & Verifikasi Tanda Tangan SPL:</h4>
                            <div class="pk-spl-signature-grid" id="pkSplSignatures">
                                <!-- Populated via JS -->
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        `;

        // Initialize Inner Interactivity
        bindEvents();
        renderTabContent();
    }

    // =========================================================================
    // 3. TAB EVENT & BINDINGS
    // =========================================================================

    function bindEvents() {
        const tabBtns = document.querySelectorAll('.pk-tab-btn');
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-pk-tab');
                
                tabBtns.forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.pk-tab-content').forEach(c => c.classList.remove('active'));

                btn.classList.add('active');
                const targetContent = document.getElementById(targetId);
                if (targetContent) targetContent.classList.add('active');
            });
        });
    }

    function renderTabContent() {
        renderHeadKPI();
        renderMechanicProductivity();
        renderPlannerEvaluation();
        renderOvertimeAttendance();
    }

    // =========================================================================
    // 4. RENDERING LOGIC FOR EACH SECTION
    // =========================================================================

    // Section 1: Head of Equipment KPI
    function renderHeadKPI() {
        const tbody = document.getElementById('tbHeadKPIBody');
        if (!tbody) return;

        let totalWeightedScore = 0;

        tbody.innerHTML = currentHeadKPI.map((item, idx) => {
            const weighted = (item.score * item.weight) / 5;
            totalWeightedScore += weighted;

            let scoreBadgeClass = 'pk-badge-success';
            if (item.score <= 2) scoreBadgeClass = 'pk-badge-danger';
            else if (item.score <= 3) scoreBadgeClass = 'pk-badge-warning';

            return `
                <tr>
                    <td style="text-align:center;">${item.id}</td>
                    <td><strong>${escapeHtml(item.aspect)}</strong></td>
                    <td>${escapeHtml(item.indicator)}</td>
                    <td><span class="pk-badge pk-badge-info">${escapeHtml(item.target)}</span></td>
                    <td>
                        <input type="number" min="1" max="5" class="pk-score-input" value="${item.score}" onchange="window.updateHeadKPIScore(${idx}, this.value)">
                    </td>
                    <td style="text-align:center;">${item.weight}%</td>
                    <td style="text-align:center; font-weight:700;" id="weighted-${idx}">${weighted.toFixed(1)}</td>
                    <td style="font-size:0.82rem; color:var(--text-muted);">${escapeHtml(item.notes)}</td>
                </tr>
            `;
        }).join('');

        // Update Total Score Labels
        updateTotalHeadScoreDisplay(totalWeightedScore);

        // Render Monthly Trend Chart
        renderHeadMonthlyChart();
    }

    function updateTotalHeadScoreDisplay(total) {
        const totalRound = Math.round(total);
        const lblVal = document.getElementById('lblTotalHeadScore');
        const lblCat = document.getElementById('lblHeadCategory');
        const tfScore = document.getElementById('tfTotalScore');
        const tfInterp = document.getElementById('tfTotalInterpretation');
        const cardHead = document.getElementById('cardHeadScore');

        if (lblVal) lblVal.innerText = `${totalRound} / 100`;
        if (tfScore) tfScore.innerText = totalRound;

        let interpText = 'Tidak efektif / perlu evaluasi serius (<65)';
        let badgeClass = 'pk-badge-danger';
        let cardClass = 'danger';

        if (totalRound >= 85) {
            interpText = 'Sangat Baik (≥85)';
            badgeClass = 'pk-badge-success';
            cardClass = 'success';
        } else if (totalRound >= 75) {
            interpText = 'Baik (75-84)';
            badgeClass = 'pk-badge-info';
            cardClass = 'info';
        } else if (totalRound >= 65) {
            interpText = 'Cukup, Perlu Perbaikan (65-74)';
            badgeClass = 'pk-badge-warning';
            cardClass = 'warning';
        }

        if (lblCat) lblCat.innerText = `Kategori: ${interpText}`;
        if (tfInterp) tfInterp.innerHTML = `<span class="pk-badge ${badgeClass}">${interpText}</span>`;
        if (cardHead) {
            cardHead.className = `pk-card ${cardClass}`;
        }
    }

    window.updateHeadKPIScore = function(index, newScoreVal) {
        let val = parseInt(newScoreVal, 10);
        if (isNaN(val) || val < 1) val = 1;
        if (val > 5) val = 5;

        currentHeadKPI[index].score = val;

        // Recalculate
        let total = 0;
        currentHeadKPI.forEach((item, i) => {
            const w = (item.score * item.weight) / 5;
            total += w;
            const el = document.getElementById(`weighted-${i}`);
            if (el) el.innerText = w.toFixed(1);
        });

        updateTotalHeadScoreDisplay(total);
    };

    function renderHeadMonthlyChart() {
        const container = document.getElementById('pkHeadMonthlyChart');
        const labelsContainer = document.getElementById('pkHeadMonthlyLabels');
        if (!container || !labelsContainer) return;

        let barsHtml = '';
        let labelsHtml = '';

        kpiMonthlyTrend.forEach(item => {
            const scoreVal = item.score || 0;
            const heightPct = (scoreVal / 100) * 100;
            const bgStyle = scoreVal >= 85 ? 'var(--success)' : (scoreVal >= 65 ? 'var(--warning)' : 'var(--danger)');

            barsHtml += `
                <div style="display:flex; flex-direction:column; justify-content:flex-end; align-items:center; width:28px;">
                    <div style="width:14px; height:${heightPct}%; background:${scoreVal ? bgStyle : '#cbd5e1'}; border-radius:3px 3px 0 0;" title="${item.month}: ${scoreVal || 'Kosong'}"></div>
                </div>
            `;
            labelsHtml += `<div style="width:30px; text-align:center;">${item.month}</div>`;
        });

        container.innerHTML = barsHtml;
        labelsContainer.innerHTML = labelsHtml;
    }

    // Section 2: Mechanic Productivity
    function renderMechanicProductivity() {
        const tbody = document.getElementById('tbMechanicsBody');
        if (!tbody) return;

        tbody.innerHTML = mechanicProductivityList.map(m => {
            let rankBadge = `<span class="pk-leaderboard-rank pk-rank-normal">${m.rank}</span>`;
            if (m.rank === 1) rankBadge = `<span class="pk-leaderboard-rank pk-rank-1"><i class="fa-solid fa-trophy"></i></span>`;
            else if (m.rank === 2) rankBadge = `<span class="pk-leaderboard-rank pk-rank-2">2</span>`;
            else if (m.rank === 3) rankBadge = `<span class="pk-leaderboard-rank pk-rank-3">3</span>`;

            let delayBadge = `<span class="pk-badge pk-badge-success">${m.delayRatio}% (${m.delayCount} job)</span>`;
            if (m.delayRatio > 10) delayBadge = `<span class="pk-badge pk-badge-danger">${m.delayRatio}% (${m.delayCount} job)</span>`;
            else if (m.delayRatio > 0) delayBadge = `<span class="pk-badge pk-badge-warning">${m.delayRatio}% (${m.delayCount} job)</span>`;

            return `
                <tr>
                    <td style="text-align:center;">${rankBadge}</td>
                    <td><strong>${escapeHtml(m.name)}</strong></td>
                    <td><small class="text-muted">${escapeHtml(m.role)}</small></td>
                    <td style="font-weight:700;">${m.totalHrs.toFixed(2)} h</td>
                    <td>${m.normalHrs.toFixed(2)} h</td>
                    <td style="color:var(--danger); font-weight:600;">${m.overtimeHrs.toFixed(2)} h</td>
                    <td><span class="pk-badge pk-badge-info">${m.jobCount} Job</span></td>
                    <td>${m.avgHrsPerJob.toFixed(2)} h</td>
                    <td>${delayBadge}</td>
                    <td>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span>${m.effectiveness.toFixed(1)}%</span>
                            <div class="pk-progress-bg" style="width:60px;">
                                <div class="pk-progress-fill bg-primary" style="width:${Math.min(m.effectiveness, 100)}%;"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        // Top Longest Jobs Table
        const tbLong = document.getElementById('tbLongestJobsBody');
        if (tbLong) {
            tbLong.innerHTML = topLongestJobs.map(j => `
                <tr>
                    <td><strong>#${j.id}</strong></td>
                    <td>${j.date}</td>
                    <td><span class="pk-badge pk-badge-secondary">${escapeHtml(j.unit || 'Yard RWI')}</span></td>
                    <td>${escapeHtml(j.plate || '-')}</td>
                    <td style="font-weight:700; color:var(--danger);">${j.duration.toFixed(2)} Jam</td>
                    <td><strong>${escapeHtml(j.mechanics)}</strong></td>
                    <td style="font-size:0.82rem;">${escapeHtml(j.issue)}</td>
                </tr>
            `).join('');
        }
    }

    window.filterMechanicTable = function() {
        const input = document.getElementById('searchMechanic');
        if (!input) return;
        const filter = input.value.toUpperCase();
        const trs = document.getElementById('tbMechanicsTable').getElementsByTagName('tr');

        for (let i = 1; i < trs.length; i++) {
            let visible = false;
            const tds = trs[i].getElementsByTagName('td');
            if (tds.length > 1) {
                const nameTxt = tds[1].textContent || '';
                const roleTxt = tds[2].textContent || '';
                if (nameTxt.toUpperCase().indexOf(filter) > -1 || roleTxt.toUpperCase().indexOf(filter) > -1) {
                    visible = true;
                }
            }
            trs[i].style.display = visible ? "" : "none";
        }
    };

    // Section 3: Planner Evaluation
    function renderPlannerEvaluation() {
        const tbody = document.getElementById('tbPlannerMatrixBody');
        if (!tbody) return;

        tbody.innerHTML = plannerCompetencyMatrix.map(item => {
            let gapBadge = `<span class="pk-badge pk-badge-success">+${item.gap} Level</span>`;
            if (item.gap < 0) gapBadge = `<span class="pk-badge pk-badge-danger">${item.gap} Level</span>`;

            return `
                <tr>
                    <td><strong>${escapeHtml(item.competency)}</strong></td>
                    <td style="font-size:0.82rem;">${escapeHtml(item.standard)}</td>
                    <td style="font-size:0.82rem; color:var(--text-muted);">${escapeHtml(item.actual)}</td>
                    <td>
                        <div style="font-size:0.8rem; font-weight:700; margin-bottom:2px;">Act: ${item.actualLvl} / Tgt: ${item.targetLvl}</div>
                        <div class="pk-progress-bg">
                            <div class="pk-progress-fill ${item.actualLvl >= item.targetLvl ? 'bg-success' : 'bg-danger'}" style="width:${(item.actualLvl / 5) * 100}%;"></div>
                        </div>
                    </td>
                    <td>${gapBadge}</td>
                    <td style="font-size:0.82rem;">${escapeHtml(item.action)}</td>
                </tr>
            `;
        }).join('');
    }

    // Section 4: Attendance & Overtime
    function renderOvertimeAttendance() {
        const tbody = document.getElementById('tbAttendanceBody');
        if (!tbody) return;

        tbody.innerHTML = attendanceOvertimeLeaderboard.map(item => {
            let rankBadge = `<span class="pk-leaderboard-rank pk-rank-normal">${item.rank}</span>`;
            if (item.rank === 1) rankBadge = `<span class="pk-leaderboard-rank pk-rank-1"><i class="fa-solid fa-crown"></i></span>`;
            else if (item.rank === 2) rankBadge = `<span class="pk-leaderboard-rank pk-rank-2">2</span>`;
            else if (item.rank === 3) rankBadge = `<span class="pk-leaderboard-rank pk-rank-3">3</span>`;

            return `
                <tr>
                    <td style="text-align:center;">${rankBadge}</td>
                    <td><span class="pk-badge ${item.site === 'YARD KM12' ? 'pk-badge-info' : 'pk-badge-warning'}">${escapeHtml(item.site)}</span></td>
                    <td><strong>${escapeHtml(item.name)}</strong></td>
                    <td>${escapeHtml(item.role)}</td>
                    <td style="text-align:center;">${item.kj} Hari</td>
                    <td style="text-align:center;">${item.kl} Hari</td>
                    <td style="text-align:center;">${item.o} Hari</td>
                    <td style="font-weight:700; color:var(--primary); font-size:0.95rem;">${item.otHours.toFixed(1)} Jam</td>
                </tr>
            `;
        }).join('');

        // SPL Verification Matrix
        const splGrid = document.getElementById('pkSplSignatures');
        if (splGrid) {
            splGrid.innerHTML = splJulyData.approvals.map(app => {
                let badgeCls = 'pk-badge-success';
                if (app.status.indexOf('Pending') > -1) badgeCls = 'pk-badge-warning';

                return `
                    <div class="pk-spl-sig-box">
                        <div class="pk-role">${escapeHtml(app.role)}</div>
                        <div class="pk-name">${escapeHtml(app.name)}</div>
                        <div class="pk-status"><span class="pk-badge ${badgeCls}">${escapeHtml(app.status)}</span></div>
                    </div>
                `;
            }).join('');
        }
    }

    // Global Export Function
    window.exportKPIReport = function() {
        const headers = ['Aspek', 'Indikator', 'Target', 'Skor', 'Bobot (%)', 'Nilai Bobot', 'Catatan Audit'];
        const rows = currentHeadKPI.map(item => [
            item.aspect, item.indicator, item.target, item.score, item.weight, ((item.score * item.weight) / 5).toFixed(1), item.notes
        ]);

        const csv = [headers, ...rows].map(row => row.map(val => `"${String(val).replace(/"/g, '""')}"`).join(',')).join('\r\n');
        const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.revokeObjectURL(link.href) || URL.createObjectURL(blob);
        link.download = 'Laporan_KPI_People_Equipment_2026.csv';
        link.click();
        alert('Laporan KPI & People berhasil diekspor ke CSV.');
    };

    // Auto Mount Handler
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createPeopleKPIModule);
    } else {
        createPeopleKPIModule();
    }

})();
