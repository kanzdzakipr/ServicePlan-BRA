(function () {
    'use strict';

    // =========================================================================
    // 1. DATA TEMPLATES & FORM CHECKLISTS FROM SOP / MATERIAL
    // =========================================================================

    const P2H_TEMPLATES = {
        'Excavator': {
            name: 'Hydraulic Excavator (PC200 / 320 Series)',
            sopRef: 'Form_P2H_Hydraulic_Excavator_Tabulasi.md',
            sections: [
                {
                    title: 'A. Sebelum Pemanasan (Pre-Start Check)',
                    items: [
                        { id: 'exc_01', text: 'Kekencangan Track & penambahan bila perlu', critical: true },
                        { id: 'exc_02a', text: 'Kebocoran pada Roller (Track Roller / Carrier Roller)', critical: false },
                        { id: 'exc_02b', text: 'Kebocoran pada Front Idler', critical: false },
                        { id: 'exc_02c', text: 'Kebocoran pada Final Drive & Travel Motor', critical: true },
                        { id: 'exc_03', text: 'Keausan / kondisi Teeth Bucket & Side Cutter', critical: false },
                        { id: 'exc_04', text: 'Kebocoran Boom, Arm & Bucket Cylinder', critical: true },
                        { id: 'exc_05', text: 'Level Oli Hydraulic & tambah bila kurang', critical: true },
                        { id: 'exc_06', text: 'Level Bahan Bakar (Solar)', critical: false },
                        { id: 'exc_07', text: 'Drain air & endapan dari Tangki Bahan Bakar', critical: false },
                        { id: 'exc_08', text: 'Level Oli Swing Motor & Swing Reduction', critical: false },
                        { id: 'exc_09', text: 'Kondisi Battery, terminal & kabel aki', critical: false },
                        { id: 'exc_10', text: 'Level Oli Engine (Dipstick) & tambah bila kurang', critical: true },
                        { id: 'exc_11', text: 'Level Air Radiator & Reservoir Tank', critical: true },
                        { id: 'exc_12', text: 'Kekencangan & kondisi Fan Belt & Alternator Belt', critical: false },
                        { id: 'exc_13', text: 'Kondisi Water Separator & Fuel Filter', critical: false },
                        { id: 'exc_14', text: 'Kondisi Oil Filter & Corrosion Resistor Filter', critical: false },
                        { id: 'exc_15', text: 'Pemberian Grease (Greasing) pada Pin Boom, Arm, Bucket & Circle', critical: false },
                        { id: 'exc_16', text: 'Kondisi & tekanan Accumulator', critical: false },
                        { id: 'exc_17', text: 'Keretakan / kondisi Frame, Boom, Arm structural', critical: true }
                    ]
                },
                {
                    title: 'B. Pemanasan Mesin (Engine Warm-Up ±5 Mnt)',
                    items: [
                        { id: 'exc_201', text: 'Indikator Tekanan Oli Engine (Engine Oil Pressure Gauge)', critical: true },
                        { id: 'exc_202', text: 'Gauge Temperatur Air Engine (Radiator Temperature)', critical: true },
                        { id: 'exc_203', text: 'Indikator Pengisian Battery (Battery Charging Lamp)', critical: false },
                        { id: 'exc_204', text: 'Indikator Filter Udara (Dust Indicator / Air Filter)', critical: false },
                        { id: 'exc_205', text: 'Fungsi seluruh Instrument Panel Group & Monitor Display', critical: false },
                        { id: 'exc_206', text: 'Warna Asap Gas Buang (Hitam/Putih/Normal)', critical: false },
                        { id: 'exc_207', text: 'Suara & bunyi aneh dari Mesin atau Hydraulic Pump', critical: true },
                        { id: 'exc_208', text: 'Operasional Attachment (Boom Up/Down, Arm In/Out, Bucket)', critical: true },
                        { id: 'exc_209', text: 'Fungsi Travel Left/Right & Brake System', critical: true },
                        { id: 'exc_210', text: 'Fungsi Swing Left/Right & Swing Lock', critical: true },
                        { id: 'exc_211', text: 'Visual check kebocoran pada Hose, Pipe, & Fitting saat tekanan tinggi', critical: true },
                        { id: 'exc_212', text: 'Fungsi Seluruh Lampu Kerja, Rotary Lamp, Horn (Klakson) & Alarm Mundur', critical: true }
                    ]
                },
                {
                    title: 'C. Selesai Operasi (Post-Operation Shutdown)',
                    items: [
                        { id: 'exc_301', text: 'Unit diparkir di tempat aman, rata & bebas bahaya longsor', critical: false },
                        { id: 'exc_302', text: 'Attachment (Bucket) diturunkan menyentuh tanah', critical: false },
                        { id: 'exc_303', text: 'Engine didinginkan (Cool Down Idle) ±5 menit sebelum dimatikan', critical: false },
                        { id: 'exc_304', text: 'Lever Pengaman / Lock Lever terpasang pada posisi LOCK', critical: true },
                        { id: 'exc_305', text: 'Kunci kontak di-OFF-kan & dilepas', critical: false },
                        { id: 'exc_306', text: 'Tangki bahan bakar diisi penuh (mencegah kondensasi)', critical: false },
                        { id: 'exc_307', text: 'Unit dibersihkan dari lumpur & kotoran menempel', critical: false }
                    ]
                }
            ]
        },
        'Compactor': {
            name: 'Single Drum Rollers / Vibro Compactor',
            sopRef: 'Form_P2H_Single_Drum_Rollers_Tabulasi.md',
            sections: [
                {
                    title: 'A. Sebelum Pemanasan (Pre-Start Check)',
                    items: [
                        { id: 'vib_01', text: 'Kondisi Roda Ban Belakang & Tekanan Angin Ban', critical: true },
                        { id: 'vib_02', text: 'Kondisi Drum Roll depan & Scraper pembuka tanah/lumpur', critical: false },
                        { id: 'vib_03', text: 'Kebocoran Oli Vibrating Motor & Drum Bearing', critical: true },
                        { id: 'vib_04', text: 'Level Oli Engine & Air Radiator', critical: true },
                        { id: 'vib_05', text: 'Level Oli Hidrolik Steering & Drive', critical: true },
                        { id: 'vib_06', text: 'Kondisi Rubber Shock Absorber Damper pada Drum', critical: true },
                        { id: 'vib_07', text: 'Level Bahan Bakar & Drain Water Separator', critical: false },
                        { id: 'vib_08', text: 'Greasing Pin Articulated Frame & Steering Cylinder', critical: false }
                    ]
                },
                {
                    title: 'B. Pemanasan Mesin (Engine Warm-Up)',
                    items: [
                        { id: 'vib_201', text: 'Indikator Tekanan Oli Engine & Temp Gauge', critical: true },
                        { id: 'vib_202', text: 'Fungsi Sistem Vibrasi (High & Low Frequency Vibrating)', critical: true },
                        { id: 'vib_203', text: 'Fungsi Steering Articulated Joint & Rem Parkir', critical: true },
                        { id: 'vib_204', text: 'Fungsi Lampu Kerja, Rotary Light & Klakson', critical: false }
                    ]
                },
                {
                    title: 'C. Selesai Operasi',
                    items: [
                        { id: 'vib_301', text: 'Parkir tanah rata, ganjal drum & ban jika perlu', critical: false },
                        { id: 'vib_302', text: 'Engine Idle 5 mnt, Kunci kontak OFF & tangki BBM diisi', critical: false }
                    ]
                }
            ]
        },
        'Dump Truck': {
            name: 'Dump Truck / Heavy Transport (A2B MDE-02)',
            sopRef: 'Form_Kartu_Pemeriksaan_A2B_MDE-02_Tabulasi.md',
            sections: [
                {
                    title: 'A. Sebelum Operasi (Pemeriksaan fisik & kelengkapan)',
                    items: [
                        { id: 'dt_01', text: 'Pemeriksaan baut roda & kondisi Ban (Tekanan, aus, robek)', critical: true },
                        { id: 'dt_02', text: 'Pemeriksaan Kebocoran Oli Mesin, Transmisi & Gardan', critical: true },
                        { id: 'dt_03', text: 'Level Air Radiator, Oli Rem, Oli Power Steering & Air Wiper', critical: true },
                        { id: 'dt_04', text: 'Kondisi Hydrolic Dump Cylinder & Kebocoran Hose Dump', critical: true },
                        { id: 'dt_05', text: 'Kondisi Per (Leaf Spring), Shock Absorber & Torque Rod', critical: true },
                        { id: 'dt_06', text: 'APAR (Alat Pemadam Api Ringan) & Segel Keselamatan', critical: true },
                        { id: 'dt_07', text: 'Persetujuan Safety Kit: Kacamata, Helm, Rompi, Sepatu Safety', critical: false }
                    ]
                },
                {
                    title: 'B. Saat Pemanasan & Uji Fungsi',
                    items: [
                        { id: 'dt_201', text: 'Fungsi Rem Utama (Service Brake) & Rem Tangan (Park Brake)', critical: true },
                        { id: 'dt_202', text: 'Fungsi Sistem Dump Vessel (Raise / Lower Test)', critical: true },
                        { id: 'dt_203', text: 'Fungsi Klakson, Lampu Utama, Lampu Sein, & Alarm Mundur', critical: true }
                    ]
                },
                {
                    title: 'C. Selesai Operasi',
                    items: [
                        { id: 'dt_301', text: 'Vessel diturunkan penuh, Parkir posisi aman & ganjal ban', critical: false },
                        { id: 'dt_302', text: 'Cool Down Engine 5 mnt, matikan saklar utama listrik (Master Switch)', critical: false }
                    ]
                }
            ]
        }
    };

    // Default Seed Inspections History Data
    let inspectionHistory = [
        {
            id: 'P2H-20260727-001',
            date: '2026-07-27 06:45',
            unitId: 'EXC-201',
            category: 'Excavator',
            operator: 'Budi Santoso',
            nrp: 'OP-BRA-089',
            site: 'Yard Duri',
            hmStart: 8450.0,
            hmEnd: 8458.5,
            status: 'LULUS (PASS)',
            criticalFails: 0,
            warnings: 1,
            notes: 'Semua item kritikal normal. Ditemukan baut cover pelindung agak kendur, sudah dikencangkan.',
            details: {}
        },
        {
            id: 'P2H-20260727-002',
            date: '2026-07-27 07:10',
            unitId: 'DT-054',
            category: 'Dump Truck',
            operator: 'Rudi Hermawan',
            nrp: 'OP-BRA-112',
            site: 'Borrow Pit',
            hmStart: 12100.0,
            hmEnd: 12100.0,
            status: 'GAGAL (CRITICAL FAIL)',
            criticalFails: 2,
            warnings: 0,
            notes: 'Terjadi kebocoran oli rem utama pada roda belakang kiri & baut roda kendur 2 pcs. Unit ditahan!',
            details: {}
        },
        {
            id: 'P2H-20260726-003',
            date: '2026-07-26 16:30',
            unitId: 'SD-101',
            category: 'Compactor',
            operator: 'Ahmad Dahlan',
            nrp: 'OP-BRA-045',
            site: 'Site Alpha',
            hmStart: 3420.0,
            hmEnd: 3427.0,
            status: 'LULUS (PASS)',
            criticalFails: 0,
            warnings: 0,
            notes: 'P2H rutin selesai operasi. Alat siap beroperasi besok.',
            details: {}
        }
    ];

    // Current Active Form State
    let currentFormData = {
        unitId: '',
        category: 'Excavator',
        template: P2H_TEMPLATES['Excavator'],
        answers: {}, // id -> 'PASS' | 'FAIL' | 'WARN'
        notes: {}    // id -> string
    };

    // =========================================================================
    // 2. MAIN MODULE RENDERER
    // =========================================================================

    function createInspectionModule() {
        const container = document.getElementById('inspectionModule');
        if (!container) return;

        container.innerHTML = `
            <div class="p2h-container">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <div>
                        <h1 class="page-title" style="margin-bottom:5px;">Modul Inspeksi & Pelaksanaan Perawatan Harian (P2H)</h1>
                        <p style="color:var(--text-muted); font-size:0.9rem;">Pemeriksaan Kesiapan Alat Berat Sebelum & Sesudah Operasi Berdasarkan SOP Enterprise PT BRA.</p>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button class="btn btn-primary" onclick="window.switchP2HTab('form')">
                            <i class="fa-solid fa-plus-circle"></i> Input P2H Baru
                        </button>
                        <button class="btn" style="background:#6c757d; color:#fff;" onclick="window.exportP2HReport()">
                            <i class="fa-solid fa-file-excel"></i> Ekspor CSV
                        </button>
                    </div>
                </div>

                <!-- KPI Summary Cards -->
                <div class="p2h-kpi-grid">
                    <div class="p2h-kpi-card info">
                        <div class="p2h-kpi-info">
                            <h4>Total P2H Bulan Ini</h4>
                            <div class="value" id="p2hKpiTotal">142</div>
                        </div>
                        <div class="p2h-kpi-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                    </div>
                    <div class="p2h-kpi-card pass">
                        <div class="p2h-kpi-info">
                            <h4>Lulus Operasional (PASS)</h4>
                            <div class="value" id="p2hKpiPass">134</div>
                        </div>
                        <div class="p2h-kpi-icon"><i class="fa-solid fa-circle-check"></i></div>
                    </div>
                    <div class="p2h-kpi-card fail">
                        <div class="p2h-kpi-info">
                            <h4>Critical Fail (Auto-WO)</h4>
                            <div class="value" id="p2hKpiFail">5</div>
                        </div>
                        <div class="p2h-kpi-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    </div>
                    <div class="p2h-kpi-card warn">
                        <div class="p2h-kpi-info">
                            <h4>Peringatan (Warning)</h4>
                            <div class="value" id="p2hKpiWarn">3</div>
                        </div>
                        <div class="p2h-kpi-icon"><i class="fa-solid fa-wrench"></i></div>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div class="panel" style="margin-bottom: 0;">
                    <div class="p2h-nav-tabs">
                        <button class="p2h-tab-btn active" id="p2hTabBtnHistory" onclick="window.switchP2HTab('history')">
                            <i class="fa-solid fa-list-check"></i> Riwayat & Tabulasi P2H
                        </button>
                        <button class="p2h-tab-btn" id="p2hTabBtnForm" onclick="window.switchP2HTab('form')">
                            <i class="fa-solid fa-pen-to-square"></i> Formulir Pengisian P2H
                        </button>
                        <button class="p2h-tab-btn" id="p2hTabBtnSop" onclick="window.switchP2HTab('sop')">
                            <i class="fa-solid fa-book"></i> Acuan SOP & Checksheet Material
                        </button>
                    </div>

                    <div class="panel-body" style="padding: 20px;">
                        <!-- TAB 1: HISTORY -->
                        <div id="p2hSectionHistory">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap; gap:10px;">
                                <div class="search-bar" style="max-width:300px; width:100%;">
                                    <input type="text" id="searchP2HHistory" placeholder="Cari Unit ID, Operator, atau Site..." onkeyup="window.filterP2HHistoryTable()" style="width:100%;">
                                </div>
                                <div style="display:flex; gap:10px; align-items:center;">
                                    <label style="font-size:0.85rem; font-weight:bold;">Filter Status:</label>
                                    <select id="filterP2HStatus" class="form-control" style="width:auto; margin-bottom:0;" onchange="window.filterP2HHistoryTable()">
                                        <option value="ALL">Semua Status</option>
                                        <option value="PASS">LULUS (PASS)</option>
                                        <option value="FAIL">GAGAL (CRITICAL FAIL)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="tbP2HHistory" style="width:100%;">
                                    <thead>
                                        <tr>
                                            <th>No. Tiket P2H</th>
                                            <th>Tanggal & Waktu</th>
                                            <th>Unit ID / Kategori</th>
                                            <th>Operator / NRP</th>
                                            <th>Site / Lokasi</th>
                                            <th>Reading HM</th>
                                            <th>Status Kelayakan</th>
                                            <th>Catatan Temuan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbP2HHistoryBody">
                                        <!-- Rendered via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 2: FORM INPUT -->
                        <div id="p2hSectionForm" style="display:none;">
                            <div class="p2h-form-header">
                                <div>
                                    <label style="font-size:0.85rem; font-weight:bold;">Pilih Unit Alat Berat (*)</label>
                                    <select id="p2hFormAssetSelect" class="form-control" style="margin-bottom:0;" onchange="window.onP2HAssetSelectChange(this.value)">
                                        <option value="">-- Pilih Kode Lambung / Asset --</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-size:0.85rem; font-weight:bold;">Kategori Checksheet</label>
                                    <input type="text" id="p2hFormCategoryLabel" class="form-control" style="margin-bottom:0; background:#e9ecef;" readonly value="Hydraulic Excavator">
                                </div>
                                <div>
                                    <label style="font-size:0.85rem; font-weight:bold;">Nama Operator (*)</label>
                                    <input type="text" id="p2hFormOperator" class="form-control" style="margin-bottom:0;" placeholder="Nama Operator">
                                </div>
                                <div>
                                    <label style="font-size:0.85rem; font-weight:bold;">NRP Operator (*)</label>
                                    <input type="text" id="p2hFormNRP" class="form-control" style="margin-bottom:0;" placeholder="NRP/ID Operator">
                                </div>
                                <div>
                                    <label style="font-size:0.85rem; font-weight:bold;">HM Sebelum Operasi (*)</label>
                                    <input type="number" step="0.1" id="p2hFormHmStart" class="form-control" style="margin-bottom:0;" placeholder="0.0">
                                </div>
                                <div>
                                    <label style="font-size:0.85rem; font-weight:bold;">HM Selesai Operasi</label>
                                    <input type="number" step="0.1" id="p2hFormHmEnd" class="form-control" style="margin-bottom:0;" placeholder="0.0">
                                </div>
                            </div>

                            <!-- Live Counter Badge -->
                            <div style="background:#e0f2fe; border:1px solid #7dd3fc; padding:12px 18px; border-radius:8px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                                <div style="font-weight:600; color:#0369a1;">
                                    <i class="fa-solid fa-shield-halved"></i> Modul Evaluasi Kelayakan Unit Real-Time
                                </div>
                                <div style="display:flex; gap:12px; font-weight:bold; font-size:0.88rem;">
                                    <span style="color:var(--p2h-pass);"><i class="fa-solid fa-check"></i> Normal: <span id="formCounterPass">0</span></span>
                                    <span style="color:var(--p2h-warn);"><i class="fa-solid fa-triangle-exclamation"></i> Warning: <span id="formCounterWarn">0</span></span>
                                    <span style="color:var(--p2h-fail);"><i class="fa-solid fa-circle-xmark"></i> Critical Fail: <span id="formCounterFail">0</span></span>
                                </div>
                            </div>

                            <!-- Dynamic Form Sections -->
                            <div id="p2hDynamicChecklistContainer">
                                <!-- Generated by renderDynamicChecklist() -->
                            </div>

                            <!-- General Notes & Submit -->
                            <div class="p2h-form-section">
                                <div class="p2h-form-section-title">
                                    <span><i class="fa-solid fa-comment-medical"></i> Catatan Temuan & Rekomendasi Tindakan</span>
                                </div>
                                <textarea id="p2hFormGeneralNotes" class="form-control" rows="3" placeholder="Tuliskan catatan tambahan, rekomendasi mekanik, atau detail kerusakan jika ada..."></textarea>
                                
                                <div style="margin-top:15px; display:flex; justify-content:flex-end; gap:10px;">
                                    <button type="button" class="btn" style="background:#e2e8f0; color:#334155;" onclick="window.switchP2HTab('history')">Batal</button>
                                    <button type="button" class="btn btn-primary" style="padding:10px 25px;" onclick="window.submitP2HForm()">
                                        <i class="fa-solid fa-paper-plane"></i> Simpan & Kirim P2H
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: SOP REFERENCE -->
                        <div id="p2hSectionSop" style="display:none;">
                            <h3 style="margin-bottom:15px;"><i class="fa-solid fa-book-open"></i> Katalog SOP & Acuan Checksheet Material</h3>
                            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:15px;">
                                <div style="border:1px solid var(--p2h-border); border-radius:8px; padding:15px; background:#fff;">
                                    <h4 style="color:var(--primary); margin-bottom:8px;"><i class="fa-solid fa-file-lines"></i> Form P2H Hydraulic Excavator</h4>
                                    <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:10px;">Digunakan untuk Komatsu PC200-8 MO, PC200-10 MO, CAT 320GX/GC. Mencakup 19 poin pre-start, 12 poin warm-up, & 7 poin post-operation.</p>
                                    <span class="badge bg-operating">SOP Tabulasi Material Active</span>
                                </div>
                                <div style="border:1px solid var(--p2h-border); border-radius:8px; padding:15px; background:#fff;">
                                    <h4 style="color:var(--primary); margin-bottom:8px;"><i class="fa-solid fa-file-lines"></i> Form P2H Single Drum Rollers</h4>
                                    <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:10px;">Digunakan untuk Vibro Compactor. Fokus pada pemeriksaan sistem vibrasi, rubber damper drum, steering articulated joint, & ban.</p>
                                    <span class="badge bg-operating">SOP Tabulasi Material Active</span>
                                </div>
                                <div style="border:1px solid var(--p2h-border); border-radius:8px; padding:15px; background:#fff;">
                                    <h4 style="color:var(--primary); margin-bottom:8px;"><i class="fa-solid fa-file-lines"></i> Kartu Pemeriksaan A2B MDE-02</h4>
                                    <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:10px;">Checksheet harian armada Dump Truck & Hauling. Memverifikasi rem utama, vessel dump cylinder, kekencangan baut roda, & kelengkapan APAR.</p>
                                    <span class="badge bg-operating">SOP Tabulasi Material Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Detail P2H -->
            <div class="modal-overlay" id="modalDetailP2H" style="display:none;">
                <div class="modal-content" style="max-width:750px;">
                    <div class="modal-header">
                        <h2>Detail Rincian P2H: <span id="mdP2HId"></span></h2>
                        <button onclick="window.closeModal('modalDetailP2H')" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
                    </div>
                    <div class="modal-body" id="mdP2HBody">
                        <!-- Populated dynamically -->
                    </div>
                </div>
            </div>
        `;

        populateAssetDropdown();
        renderHistoryTable();
        updateKpiSummary();
        renderDynamicChecklist();
    }

    // =========================================================================
    // 3. ASSET SELECTION & FORM LOGIC
    // =========================================================================

    function populateAssetDropdown() {
        const select = document.getElementById('p2hFormAssetSelect');
        if (!select) return;

        let assets = [];
        if (window.globalData && window.globalData.assets) {
            assets = window.globalData.assets;
        } else {
            assets = [
                { id: 'EXC-201', category: 'Excavator', last_hm_km: 8450.0 },
                { id: 'EXC-210', category: 'Excavator', last_hm_km: 6350.0 },
                { id: 'DT-054', category: 'Dump Truck', last_hm_km: 12100.0 },
                { id: 'SD-101', category: 'Compactor', last_hm_km: 3420.0 }
            ];
        }

        select.innerHTML = '<option value="">-- Pilih Kode Lambung / Asset --</option>' +
            assets.map(a => `<option value="${escapeHtml(a.id || a.asset_id)}" data-cat="${escapeHtml(a.category)}" data-hm="${a.last_hm_km || a.hm || 0}">${escapeHtml(a.id || a.asset_id)} - (${escapeHtml(a.category)})</option>`).join('');
    }

    window.onP2HAssetSelectChange = function (assetId) {
        const select = document.getElementById('p2hFormAssetSelect');
        if (!select || !assetId) return;

        const selectedOption = select.options[select.selectedIndex];
        const cat = selectedOption.getAttribute('data-cat') || 'Excavator';
        const lastHm = selectedOption.getAttribute('data-hm') || 0;

        currentFormData.unitId = assetId;

        // Map Category to P2H Template Key
        let templateKey = 'Excavator';
        if (cat.toLowerCase().includes('truck')) templateKey = 'Dump Truck';
        else if (cat.toLowerCase().includes('vibro') || cat.toLowerCase().includes('compactor') || cat.toLowerCase().includes('roller')) templateKey = 'Compactor';

        currentFormData.category = templateKey;
        currentFormData.template = P2H_TEMPLATES[templateKey] || P2H_TEMPLATES['Excavator'];

        const catLabel = document.getElementById('p2hFormCategoryLabel');
        if (catLabel) catLabel.value = currentFormData.template.name;

        const hmStart = document.getElementById('p2hFormHmStart');
        if (hmStart) hmStart.value = lastHm;

        // Reset answers & re-render
        currentFormData.answers = {};
        currentFormData.notes = {};
        renderDynamicChecklist();
    };

    function renderDynamicChecklist() {
        const container = document.getElementById('p2hDynamicChecklistContainer');
        if (!container) return;

        const template = currentFormData.template;
        let html = '';

        template.sections.forEach((sec, sIdx) => {
            html += `
                <div class="p2h-form-section">
                    <div class="p2h-form-section-title">
                        <span><i class="fa-solid fa-list-check"></i> ${escapeHtml(sec.title)}</span>
                        <span style="font-size:0.8rem; font-weight:normal; color:var(--text-muted);">${sec.items.length} Poin Pemeriksaan</span>
                    </div>
            `;

            sec.items.forEach((item) => {
                const ans = currentFormData.answers[item.id] || 'PASS'; // Default PASS
                currentFormData.answers[item.id] = ans;

                html += `
                    <div class="p2h-item-row" id="row_${item.id}">
                        <div class="p2h-item-label">
                            ${escapeHtml(item.text)}
                            ${item.critical ? '<span class="p2h-critical-tag"><i class="fa-solid fa-shield"></i> CRITICAL</span>' : ''}
                            <input type="text" class="p2h-notes-input" id="note_${item.id}" placeholder="Tuliskan detail catatan temuan..." onchange="window.setP2HNote('${item.id}', this.value)" style="display:${ans !== 'PASS' ? 'block' : 'none'};">
                        </div>
                        <div class="p2h-item-options">
                            <button type="button" class="p2h-radio-btn pass ${ans === 'PASS' ? 'active' : ''}" onclick="window.setP2HAnswer('${item.id}', 'PASS')">
                                <i class="fa-solid fa-check"></i> Baik (V)
                            </button>
                            <button type="button" class="p2h-radio-btn warn ${ans === 'WARN' ? 'active' : ''}" onclick="window.setP2HAnswer('${item.id}', 'WARN')">
                                <i class="fa-solid fa-exclamation"></i> Warning
                            </button>
                            <button type="button" class="p2h-radio-btn fail ${ans === 'FAIL' ? 'active' : ''}" onclick="window.setP2HAnswer('${item.id}', 'FAIL')">
                                <i class="fa-solid fa-xmark"></i> Rusak (X)
                            </button>
                        </div>
                    </div>
                `;
            });

            html += `</div>`;
        });

        container.innerHTML = html;
        updateLiveCounters();
    }

    window.setP2HAnswer = function (itemId, value) {
        currentFormData.answers[itemId] = value;
        const row = document.getElementById(`row_${itemId}`);
        if (row) {
            const btns = row.querySelectorAll('.p2h-radio-btn');
            btns.forEach(b => b.classList.remove('active'));
            if (value === 'PASS') row.querySelector('.pass').classList.add('active');
            else if (value === 'WARN') row.querySelector('.warn').classList.add('active');
            else if (value === 'FAIL') row.querySelector('.fail').classList.add('active');

            const noteInput = document.getElementById(`note_${itemId}`);
            if (noteInput) {
                noteInput.style.display = value !== 'PASS' ? 'block' : 'none';
            }
        }
        updateLiveCounters();
    };

    window.setP2HNote = function (itemId, text) {
        currentFormData.notes[itemId] = text;
    };

    function updateLiveCounters() {
        let pass = 0, warn = 0, fail = 0;
        Object.keys(currentFormData.answers).forEach(key => {
            const val = currentFormData.answers[key];
            if (val === 'PASS') pass++;
            else if (val === 'WARN') warn++;
            else if (val === 'FAIL') fail++;
        });

        const elPass = document.getElementById('formCounterPass');
        const elWarn = document.getElementById('formCounterWarn');
        const elFail = document.getElementById('formCounterFail');
        if (elPass) elPass.innerText = pass;
        if (elWarn) elWarn.innerText = warn;
        if (elFail) elFail.innerText = fail;
    }

    // =========================================================================
    // 4. SUBMIT FORM & AUTOMATIC WORK ORDER TRIGGER ENGINE
    // =========================================================================

    window.submitP2HForm = function () {
        const assetId = document.getElementById('p2hFormAssetSelect').value;
        const operator = document.getElementById('p2hFormOperator').value.trim();
        const nrp = document.getElementById('p2hFormNRP').value.trim();
        const hmStart = parseFloat(document.getElementById('p2hFormHmStart').value) || 0;
        const hmEnd = parseFloat(document.getElementById('p2hFormHmEnd').value) || hmStart;
        const generalNotes = document.getElementById('p2hFormGeneralNotes').value.trim();

        if (!assetId) return alert('Silakan pilih Kode Lambung / Asset terlebih dahulu!');
        if (!operator || !nrp) return alert('Nama Operator dan NRP wajib diisi!');

        // Check for Critical Fails
        let criticalFailsCount = 0;
        let warningsCount = 0;
        let failedItemsList = [];

        currentFormData.template.sections.forEach(sec => {
            sec.items.forEach(item => {
                const ans = currentFormData.answers[item.id];
                if (ans === 'FAIL') {
                    if (item.critical) criticalFailsCount++;
                    failedItemsList.push(item.text);
                } else if (ans === 'WARN') {
                    warningsCount++;
                }
            });
        });

        const isCriticalFail = criticalFailsCount > 0;
        const statusLabel = isCriticalFail ? 'GAGAL (CRITICAL FAIL)' : (warningsCount > 0 ? 'LULUS DENGAN CATATAN' : 'LULUS (PASS)');

        const newP2H = {
            id: 'P2H-' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '-' + Math.floor(100 + Math.random()*900),
            date: new Date().toISOString().slice(0,16).replace('T',' '),
            unitId: assetId,
            category: currentFormData.category,
            operator: operator,
            nrp: nrp,
            site: 'Yard Duri / Site Alpha',
            hmStart: hmStart,
            hmEnd: hmEnd,
            status: statusLabel,
            criticalFails: criticalFailsCount,
            warnings: warningsCount,
            notes: generalNotes || (isCriticalFail ? `Temuan Kerusakan Kritikal: ${failedItemsList.join(', ')}` : 'Form P2H diisi lengkap.'),
            answers: { ...currentFormData.answers },
            itemNotes: { ...currentFormData.notes }
        };

        // Add to History
        inspectionHistory.unshift(newP2H);

        // Update global assets & trigger Auto Work Order if Critical Fail
        if (window.globalData) {
            if (!window.globalData.inspections) window.globalData.inspections = [];
            window.globalData.inspections.unshift(newP2H);

            // Update Asset status & HM
            const targetAsset = window.globalData.assets.find(a => (a.id || a.asset_id) === assetId);
            if (targetAsset) {
                targetAsset.last_hm_km = hmEnd;
                if (isCriticalFail) {
                    targetAsset.status = 'INSPEKSI';
                }
            }

            // AUTO-WORK ORDER TRIGGER RULE ENGINE
            if (isCriticalFail) {
                const autoWoId = 'WO-P2H-' + Math.floor(1000 + Math.random() * 9000);
                const autoWo = {
                    woId: autoWoId,
                    assetId: assetId,
                    date: new Date().toISOString().slice(0, 10),
                    status: 'Open',
                    priority: 'High',
                    type: 'Breakdown / Inspection Finding',
                    description: `[AUTO-WO VIA P2H] Unit ditahan akibat temuan kritikal pada P2H (${newP2H.id}). Temuan: ${failedItemsList.join('; ')}`,
                    downtime: 0,
                    assignedTo: 'Mekanik Shift 1',
                    source: 'P2H Trigger'
                };
                if (!window.globalData.work_orders) window.globalData.work_orders = [];
                window.globalData.work_orders.unshift(autoWo);

                alert(`⚠️ WARN / ATENSI AUTO-WO!\n\nP2H Ditolak karena ditemukan ${criticalFailsCount} item temuan KRITIKAL!\n\nSistem otomatis:\n1. Mengubah status unit ${assetId} -> 'INSPEKSI / BREAKDOWN'\n2. Membuat Tiket Work Order Darurat #${autoWoId}`);
            } else {
                alert(`✅ Successful!\n\nForm P2H #${newP2H.id} untuk unit ${assetId} berhasil disimpan dengan status: ${statusLabel}.`);
            }
        } else {
            alert(`✅ Form P2H #${newP2H.id} berhasil disimpan.`);
        }

        renderHistoryTable();
        updateKpiSummary();
        window.switchP2HTab('history');
    };

    // =========================================================================
    // 5. HISTORY TABLE & FILTERING
    // =========================================================================

    function renderHistoryTable() {
        const tbody = document.getElementById('tbP2HHistoryBody');
        if (!tbody) return;

        const dataToRender = window.globalData && window.globalData.inspections ? window.globalData.inspections : inspectionHistory;

        if (dataToRender.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:20px; color:var(--text-muted);">Belum ada data riwayat P2H.</td></tr>`;
            return;
        }

        tbody.innerHTML = dataToRender.map(p => {
            let badgeClass = 'p2h-badge-pass';
            if (p.status.includes('GAGAL') || p.status.includes('FAIL')) badgeClass = 'p2h-badge-fail';
            else if (p.status.includes('CATATAN') || p.status.includes('WARN')) badgeClass = 'p2h-badge-warn';

            return `
                <tr>
                    <td><strong>${escapeHtml(p.id)}</strong></td>
                    <td style="font-size:0.85rem;">${escapeHtml(p.date)}</td>
                    <td><strong>${escapeHtml(p.unitId)}</strong> <span style="font-size:0.78rem; color:var(--text-muted);">(${escapeHtml(p.category)})</span></td>
                    <td>${escapeHtml(p.operator)} <br><small style="color:var(--text-muted);">${escapeHtml(p.nrp)}</small></td>
                    <td>${escapeHtml(p.site)}</td>
                    <td style="font-weight:bold;">${p.hmStart} Jam</td>
                    <td><span class="p2h-badge ${badgeClass}">${escapeHtml(p.status)}</span></td>
                    <td style="font-size:0.82rem; max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${escapeHtml(p.notes)}">${escapeHtml(p.notes)}</td>
                    <td>
                        <button type="button" class="btn" style="padding:3px 8px; font-size:0.78rem; background:var(--primary); color:#fff;" onclick="window.viewP2HDetail('${escapeHtml(p.id)}')">
                            <i class="fa-solid fa-eye"></i> Detail
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    window.filterP2HHistoryTable = function () {
        const searchInput = document.getElementById('searchP2HHistory');
        const filterStatus = document.getElementById('filterP2HStatus');
        if (!searchInput || !filterStatus) return;

        const query = searchInput.value.toUpperCase();
        const statusVal = filterStatus.value;
        const trs = document.getElementById('tbP2HHistory').getElementsByTagName('tr');

        for (let i = 1; i < trs.length; i++) {
            const tds = trs[i].getElementsByTagName('td');
            if (tds.length < 8) continue;

            const idTxt = tds[0].textContent.toUpperCase();
            const unitTxt = tds[2].textContent.toUpperCase();
            const opTxt = tds[3].textContent.toUpperCase();
            const statusTxt = tds[6].textContent.toUpperCase();

            const matchQuery = idTxt.includes(query) || unitTxt.includes(query) || opTxt.includes(query);
            let matchStatus = true;
            if (statusVal === 'PASS') matchStatus = statusTxt.includes('LULUS') || statusTxt.includes('PASS');
            else if (statusVal === 'FAIL') matchStatus = statusTxt.includes('GAGAL') || statusTxt.includes('FAIL');

            trs[i].style.display = (matchQuery && matchStatus) ? '' : 'none';
        }
    };

    window.viewP2HDetail = function (p2hId) {
        const allData = window.globalData && window.globalData.inspections ? window.globalData.inspections : inspectionHistory;
        const p = allData.find(x => x.id === p2hId);
        if (!p) return alert('Data P2H tidak ditemukan!');

        const modalId = document.getElementById('mdP2HId');
        const modalBody = document.getElementById('mdP2HBody');
        if (modalId) modalId.innerText = p.id;

        if (modalBody) {
            modalBody.innerHTML = `
                <table style="width:100%; border-collapse:collapse; margin-bottom:15px;" border="1" cellpadding="8">
                    <tr><th style="width:30%; background:#f8f9fa;">Unit ID</th><td><strong>${escapeHtml(p.unitId)}</strong> (${escapeHtml(p.category)})</td></tr>
                    <tr><th style="background:#f8f9fa;">Tanggal / Waktu</th><td>${escapeHtml(p.date)}</td></tr>
                    <tr><th style="background:#f8f9fa;">Operator / NRP</th><td>${escapeHtml(p.operator)} (${escapeHtml(p.nrp)})</td></tr>
                    <tr><th style="background:#f8f9fa;">HM Start - End</th><td>${p.hmStart} Jam - ${p.hmEnd} Jam</td></tr>
                    <tr><th style="background:#f8f9fa;">Status Kelayakan</th><td><strong>${escapeHtml(p.status)}</strong></td></tr>
                    <tr><th style="background:#f8f9fa;">Catatan Temuan</th><td>${escapeHtml(p.notes)}</td></tr>
                </table>
                <button type="button" class="btn btn-primary" style="width:100%;" onclick="window.closeModal('modalDetailP2H')">Tutup</button>
            `;
        }
        window.openModal('modalDetailP2H');
    };

    function updateKpiSummary() {
        const allData = window.globalData && window.globalData.inspections ? window.globalData.inspections : inspectionHistory;
        const total = allData.length;
        const pass = allData.filter(x => x.status.includes('PASS') || x.status.includes('LULUS')).length;
        const fail = allData.filter(x => x.status.includes('FAIL') || x.status.includes('GAGAL')).length;
        const warn = allData.filter(x => x.status.includes('WARN') || x.status.includes('CATATAN')).length;

        const elTotal = document.getElementById('p2hKpiTotal');
        const elPass = document.getElementById('p2hKpiPass');
        const elFail = document.getElementById('p2hKpiFail');
        const elWarn = document.getElementById('p2hKpiWarn');

        if (elTotal) elTotal.innerText = total;
        if (elPass) elPass.innerText = pass;
        if (elFail) elFail.innerText = fail;
        if (elWarn) elWarn.innerText = warn;
    }

    // Navigation & Tabs Switcher
    window.switchP2HTab = function (tabName) {
        document.querySelectorAll('.p2h-tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('p2hSectionHistory').style.display = 'none';
        document.getElementById('p2hSectionForm').style.display = 'none';
        document.getElementById('p2hSectionSop').style.display = 'none';

        if (tabName === 'form') {
            document.getElementById('p2hTabBtnForm').classList.add('active');
            document.getElementById('p2hSectionForm').style.display = 'block';
        } else if (tabName === 'sop') {
            document.getElementById('p2hTabBtnSop').classList.add('active');
            document.getElementById('p2hSectionSop').style.display = 'block';
        } else {
            document.getElementById('p2hTabBtnHistory').classList.add('active');
            document.getElementById('p2hSectionHistory').style.display = 'block';
        }
    };

    window.exportP2HReport = function () {
        const headers = ['Tiket P2H', 'Tanggal', 'Unit ID', 'Kategori', 'Operator', 'NRP', 'Site', 'HM Start', 'HM End', 'Status', 'Catatan'];
        const allData = window.globalData && window.globalData.inspections ? window.globalData.inspections : inspectionHistory;
        const rows = allData.map(p => [
            p.id, p.date, p.unitId, p.category, p.operator, p.nrp, p.site, p.hmStart, p.hmEnd, p.status, p.notes
        ]);
        const csv = [headers, ...rows].map(row => row.map(val => `"${String(val).replace(/"/g, '""')}"`).join(',')).join('\r\n');
        const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `Laporan_P2H_PT_BRA_${new Date().toISOString().slice(0,10)}.csv`;
        link.click();
        alert('Laporan Rekapitulasi P2H berhasil di-export ke format CSV.');
    };

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
        document.addEventListener('DOMContentLoaded', createInspectionModule);
    } else {
        createInspectionModule();
    }
})();
