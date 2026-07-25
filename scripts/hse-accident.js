(function () {
    'use strict';

    // =========================================================================
    // 1. DOMAIN DATA FROM LAPORAN_ACCIDENT & TAR
    // =========================================================================

    const initialAccidentLogs = [
        {
            docNo: '01/ACC/BRA/DURI/2026',
            reportDate: '2026-05-14',
            incidentDate: '2026-05-11 14:30',
            unitName: 'Powder Binder Spreader XCMG XKC185',
            unitCode: 'CS-41001',
            makeModel: 'XCMG XKC185',
            licensePlate: 'BM 9012 RWI',
            location: 'Site RWI Duri (Project Zona 4)',
            operatorName: 'M. Fajar DC',
            operatorTenure: '< 1 bulan (Unit Baru)',
            chronology: 'Pada tanggal 11 Mei 2026 pukul 14.30 WIB, unit sedang melakukan pengisian material powder binder/cement di area pengisian. Terjadi Error System Penaburan cement yang disebabkan pada saat pengisian cement operator tidak menyalakan tombol pada monitor sehingga sistem error dan cement tidak dapat ditabur.',
            environment: {
                weather: 'Cerah',
                roadCondition: 'Kering / Rata',
                lighting: 'Siang Hari (Terang)',
                density: 'Padat Operasional',
                cargo: 'Overload Pengisian Cement'
            },
            impact: {
                physicalDamage: 'Error System Penaburan & Valve Blockage',
                estimatedRepairCost: 15500000,
                estimatedDowntimeDays: 3,
                productionImpact: 'Penaburan Semen Terhenti (Delay Stabilisasi)',
                totalFinancialImpact: 28000000
            },
            causeFactors: ['Human factor', 'Procedural / System failure'],
            causeExplanation: 'Operator tidak mengikuti urutan tombol pengisian monitor saat mengisi material cement, serta belum ada interlock otomatis.',
            correctiveAction: 'Unit dihentikan operasional (ACCIDENT_HOLD). Sebagian cement dibongkar dan diisi ulang oleh teknisi XCMG.',
            preventiveAction: 'Refresher training operator penabur semen, update SOP serah terima unit baru, dan penambahan label urutan instruksi di kabin.',
            severity: 'Moderate',
            isUnitLocked: true,
            status: 'CAPA_Pending',
            tarNo: '01/TAR/05/2026'
        },
        {
            docNo: '02/ACC/BRA/YARD/2026',
            reportDate: '2026-06-20',
            incidentDate: '2026-06-19 16:15',
            unitName: 'Dump Truck Hino Ranger FM 280 JD',
            unitCode: 'DT-00052',
            makeModel: 'Hino Ranger FM 280 JD',
            licensePlate: 'B 9642 KYW',
            location: 'Yard KM 12 Area Workshop',
            operatorName: 'Suwardi (Welder/Mekanik)',
            operatorTenure: '2 Tahun',
            chronology: 'Saat bermanuver mundur di area bay workshop, engsel pintu Ombeng tersangkut pada tiang penyangga sehingga engsel patah, kunci pintu bengkok, dan pelat lantai robek.',
            environment: {
                weather: 'Hujan Gerimis',
                roadCondition: 'Lumpur / Licin',
                lighting: 'Sore Hari',
                density: 'Padat Unit Standby',
                cargo: 'Kosong'
            },
            impact: {
                physicalDamage: 'Engsel Pintu Patah, Kunci Ombeng Bengkok, Lantai Robek',
                estimatedRepairCost: 4500000,
                estimatedDowntimeDays: 1,
                productionImpact: 'Keterlambatan Mobilisasi ke Site',
                totalFinancialImpact: 6000000
            },
            causeFactors: ['Human factor', 'Environmental factor'],
            causeExplanation: 'Pandangan terhalang saat mundur di area padat dan kondisi permukaan jalan licin.',
            correctiveAction: 'Fabrikasi dan pengelasan ulang engsel pintu ombeng, pelurusan kunci pintu, serta pengelasan pelat lantai.',
            preventiveAction: 'Pemasangan spotter/flagman saat manufaktur/manuver di area workshop bay.',
            severity: 'Minor',
            isUnitLocked: false,
            status: 'Closed',
            tarNo: '-'
        },
        {
            docNo: '03/ACC/BRA/PIT/2026',
            reportDate: '2026-07-05',
            incidentDate: '2026-07-04 11:20',
            unitName: 'Bulldozer Komatsu D85ESS-2',
            unitCode: 'DZ-00002',
            makeModel: 'Komatsu D85ESS-2',
            licensePlate: 'SN P6G01656',
            location: 'Borrow Pit Harapan Baru',
            operatorName: 'Joni Septian',
            operatorTenure: '1.5 Tahun',
            chronology: 'Unit beroperasi di tebing slope pit. Terjadi kemiringan tak terduga akibat tanah gembur ambles sehingga blade membentur batu keras dan dudukan hydraulic cylinder retak.',
            environment: {
                weather: 'Cerah',
                roadCondition: 'Gembur / Unstable Slope',
                lighting: 'Siang Hari',
                density: 'Sepi',
                cargo: 'Heavy Soil'
            },
            impact: {
                physicalDamage: 'Dudukan Hydraulic Cylinder Blade Retak',
                estimatedRepairCost: 32000000,
                estimatedDowntimeDays: 5,
                productionImpact: 'Stripping Overburden Terhenti',
                totalFinancialImpact: 45000000
            },
            causeFactors: ['Environmental factor', 'Mechanical factor'],
            causeExplanation: 'Kondisi struktur geologi tanah gembur pasca hujan deras malam sebelumnya.',
            correctiveAction: 'Unit di-lock (ACCIDENT_HOLD), tim mekanik welder diterjunkan untuk penggantian bracket cylinder.',
            preventiveAction: 'Inspeksi kestabilan slope pit oleh K3L sebelum unit berat beroperasi.',
            severity: 'Critical',
            isUnitLocked: true,
            status: 'Investigating',
            tarNo: '03/TAR/07/2026'
        }
    ];

    let currentStep = 1;

    // =========================================================================
    // 2. MAIN MODULE RENDER FUNCTION
    // =========================================================================

    function createHSEModule() {
        const container = document.getElementById('hseAccidentModule');
        if (!container) return;

        container.innerHTML = `
            <div class="hse-container">
                <!-- Header -->
                <div class="hse-header">
                    <div class="hse-header-title">
                        <h2><i class="fa-solid fa-triangle-exclamation text-danger"></i> Manajemen HSE & Laporan Insiden Kecelakaan Unit</h2>
                        <p>Pencatatan resmi insiden, kronologi, kondisi lingkungan, analisa akar penyebab, dan kontrol penahanan unit (ACCIDENT_HOLD)</p>
                    </div>
                    <div style="display:flex; gap:10px;">
                        <button class="btn btn-danger" onclick="window.openNewIncidentModal()"><i class="fa-solid fa-plus"></i> Laporkan Insiden Baru</button>
                        <button class="btn btn-primary" onclick="window.exportHSEReport()"><i class="fa-solid fa-file-csv"></i> Ekspor CSV</button>
                    </div>
                </div>

                <!-- Unit Lock Warning Banner if active locks exist -->
                <div id="hseActiveLockBanner"></div>

                <!-- Nav Tabs -->
                <div class="hse-nav-tabs">
                    <button class="hse-tab-btn active" data-hse-tab="tab-incident-log"><i class="fa-solid fa-list-check"></i> Register Laporan Insiden</button>
                    <button class="hse-tab-btn" data-hse-tab="tab-tar-case"><i class="fa-solid fa-microscope"></i> Technical Analysis Report (TAR)</button>
                    <button class="hse-tab-btn" data-hse-tab="tab-capa-tracker"><i class="fa-solid fa-shield-halved"></i> CAPA & Release Approval</button>
                </div>

                <!-- TAB 1: INCIDENT REGISTER LOG -->
                <div class="hse-tab-content active" id="tab-incident-log">
                    <div class="hse-cards-grid">
                        <div class="hse-card danger">
                            <div class="hse-card-info">
                                <h4>Total Insiden dilaporkan</h4>
                                <div class="hse-val" id="lblTotalAccidents">3 Kasus</div>
                                <div class="hse-sub">Tahun 2026</div>
                            </div>
                            <div class="hse-card-icon"><i class="fa-solid fa-car-burst"></i></div>
                        </div>
                        <div class="hse-card warning">
                            <div class="hse-card-info">
                                <h4>Unit Locked (HOLD)</h4>
                                <div class="hse-val" id="lblLockedUnits">2 Unit</div>
                                <div class="hse-sub">Status ACCIDENT_HOLD</div>
                            </div>
                            <div class="hse-card-icon"><i class="fa-solid fa-lock"></i></div>
                        </div>
                        <div class="hse-card primary">
                            <div class="hse-card-info">
                                <h4>Estimasi Total Kerugian</h4>
                                <div class="hse-val" id="lblTotalLoss">Rp 79 Jt</div>
                                <div class="hse-sub">Kerugian Finansial & Down</div>
                            </div>
                            <div class="hse-card-icon"><i class="fa-solid fa-money-bill-trend-up"></i></div>
                        </div>
                        <div class="hse-card success">
                            <div class="hse-card-info">
                                <h4>Kasus Selesai (Closed)</h4>
                                <div class="hse-val" id="lblClosedAccidents">1 Kasus</div>
                                <div class="hse-sub">CAPA Completed & Released</div>
                            </div>
                            <div class="hse-card-icon"><i class="fa-solid fa-circle-check"></i></div>
                        </div>
                    </div>

                    <div class="pk-panel">
                        <div class="pk-panel-header">
                            <span><i class="fa-solid fa-table-list"></i> Daftar Laporan Resmi Insiden & Accident Alat Berat</span>
                            <div class="search-bar" style="max-width:240px;">
                                <input type="text" id="searchAccident" placeholder="Cari kode unit / lokasi..." onkeyup="window.filterAccidentTable()">
                            </div>
                        </div>
                        <div class="pk-panel-body no-padding">
                            <div class="table-responsive">
                                <table id="tbAccidentTable">
                                    <thead>
                                        <tr>
                                            <th>No. Dokumen</th>
                                            <th>Tanggal & Jam</th>
                                            <th>Kode Unit</th>
                                            <th>Lokasi Kejadian</th>
                                            <th>Operator</th>
                                            <th>Severitas</th>
                                            <th>Estimasi Kerugian</th>
                                            <th>Status Lock Unit</th>
                                            <th>Status CAPA</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbAccidentBody">
                                        <!-- Populated via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: TECHNICAL ANALYSIS REPORT (TAR) -->
                <div class="hse-tab-content" id="tab-tar-case">
                    <div class="pk-alert pk-alert-info">
                        <i class="fa-solid fa-circle-info"></i>
                        <div>
                            <strong>Technical Analysis Report (TAR) Standard:</strong> TAR diterbitkan untuk menginvestigasi insiden teknis/overload berat (contoh: TAR No. 01/TAR/05/2026 Unit CS-41001 XCMG XKC185). Laporan memuat identifikasi failure mode, dukungan vendor OEM, dan estimasi biaya klaim warranty.
                        </div>
                    </div>

                    <div class="pk-panel">
                        <div class="pk-panel-header">
                            <span><i class="fa-solid fa-file-medical"></i> Case Study TAR Resmi: Unit CS-41001 (XCMG XKC185 Powder Binder Spreader)</span>
                            <span class="hse-badge hse-badge-moderate">Trouble Date: 11-MEI-2026</span>
                        </div>
                        <div class="pk-panel-body">
                            <div style="display:grid; grid-template-columns: 1fr 2fr; gap:20px;">
                                <div style="background:#f8fafc; padding:16px; border-radius:6px; border:1px solid var(--border);">
                                    <h4 style="font-size:0.95rem; margin-bottom:10px; color:var(--dark);"><i class="fa-solid fa-truck text-danger"></i> Identitas Dokumen & Unit</h4>
                                    <table style="font-size:0.85rem; width:100%;">
                                        <tr><td style="font-weight:600; padding:4px 0;">No TAR</td><td>: 01/TAR/05/2026</td></tr>
                                        <tr><td style="font-weight:600; padding:4px 0;">No Dokumen Form</td><td>: 02/TAR/BRA/PHR.DURI/2026</td></tr>
                                        <tr><td style="font-weight:600; padding:4px 0;">Unit Code</td><td>: CS-41001</td></tr>
                                        <tr><td style="font-weight:600; padding:4px 0;">Model Make</td><td>: XCMG XKC185 Spreader</td></tr>
                                        <tr><td style="font-weight:600; padding:4px 0;">Pelapor</td><td>: M. FAJAR DC</td></tr>
                                        <tr><td style="font-weight:600; padding:4px 0;">Tujuan Laporan</td><td>: HRD & Equipment Mgr</td></tr>
                                    </table>
                                </div>

                                <div>
                                    <h4 style="font-size:0.95rem; margin-bottom:8px; color:var(--dark);"><i class="fa-solid fa-triangle-exclamation text-warning"></i> Problem & Reason of Failure Analysis</h4>
                                    <div class="pk-alert pk-alert-warning" style="margin-bottom:12px;">
                                        <div>
                                            <strong>Problem / Issue:</strong> Error System Pengisian Cement sehingga Cement tidak bisa ditabur pada project pekerjan jalan stabilisasi.
                                            <br><strong>Reason of Failure:</strong> Terjadi Error System Penaburan cement yang disebabkan pada saat pengisian cement operator tidak menyalakan tombol di monitor sehingga system error (Overload System).
                                        </div>
                                    </div>

                                    <h4 style="font-size:0.95rem; margin-bottom:6px; color:var(--dark);"><i class="fa-solid fa-wrench text-success"></i> Action Taken & Vendor Warranty</h4>
                                    <p style="font-size:0.85rem; color:var(--text-main); margin-bottom:10px;">
                                        Sebagian cement dibongkar secara manual dan dilakukan pembongkaran/penyesuaian sistem pengisian oleh teknisi resmi OEM XCMG. Unit kini berstatus <strong>CLOSED (REPAIRED BY XCMG VENDOR)</strong>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: CAPA & RELEASE APPROVAL -->
                <div class="hse-tab-content" id="tab-capa-tracker">
                    <div class="pk-panel">
                        <div class="pk-panel-header">
                            <span><i class="fa-solid fa-shield-cat"></i> Corrective & Preventive Action (CAPA) Tracking Matrix</span>
                        </div>
                        <div class="pk-panel-body no-padding">
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Kode Unit</th>
                                            <th>Insiden Terkait</th>
                                            <th>Tindakan Segera (Corrective Action)</th>
                                            <th>Rekomendasi Pencegahan (Preventive Action)</th>
                                            <th>PIC Action</th>
                                            <th>Unit Lock Status</th>
                                            <th>Otorisasi Rilis Unit</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbCapaBody">
                                        <!-- Populated via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL LAPOR INSIDEN BARU (STEPPER FORM) -->
            <div class="hse-modal-overlay" id="modalIncidentReport">
                <div class="hse-modal-card">
                    <div class="hse-modal-header">
                        <h3><i class="fa-solid fa-file-circle-exclamation"></i> Form Pelaporan Resmi Insiden / Accident Unit</h3>
                        <button onclick="window.closeIncidentModal()" style="border:none; background:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
                    </div>
                    <div class="hse-modal-body">
                        <!-- Stepper Progress Header -->
                        <div class="hse-stepper-header">
                            <div class="hse-step-item active" id="stepIndicator1">
                                <div class="hse-step-num">1</div>
                                <div class="hse-step-lbl">I. Data Umum & Kronologi</div>
                            </div>
                            <div class="hse-step-item" id="stepIndicator2">
                                <div class="hse-step-num">2</div>
                                <div class="hse-step-lbl">II. Lingkungan & Dampak</div>
                            </div>
                            <div class="hse-step-item" id="stepIndicator3">
                                <div class="hse-step-num">3</div>
                                <div class="hse-step-lbl">III. Penyebab & CAPA</div>
                            </div>
                        </div>

                        <!-- Form Steps Containers -->
                        <form id="formIncident">
                            <!-- STEP 1 -->
                            <div id="formStep1">
                                <div class="hse-form-grid">
                                    <div class="hse-form-group">
                                        <label>Pilih Unit Alat Berat</label>
                                        <select id="incAssetId" class="hse-form-control">
                                            <!-- Populated via JS -->
                                        </select>
                                    </div>
                                    <div class="hse-form-group">
                                        <label>Tanggal & Jam Kejadian</label>
                                        <input type="datetime-local" id="incDatetime" class="hse-form-control">
                                    </div>
                                    <div class="hse-form-group">
                                        <label>Lokasi Kejadian (Site/Pit/Yard)</label>
                                        <input type="text" id="incLocation" placeholder="Misal: Pit Harapan Baru / Yard KM12" class="hse-form-control">
                                    </div>
                                </div>

                                <div class="hse-form-grid">
                                    <div class="hse-form-group">
                                        <label>Nama Operator / Driver</label>
                                        <input type="text" id="incOperator" placeholder="Nama operator saat kejadian" class="hse-form-control">
                                    </div>
                                    <div class="hse-form-group">
                                        <label>Masa Kerja Operator</label>
                                        <input type="text" id="incTenure" placeholder="Misal: 6 Bulan / Unit Baru (<1 Bln)" class="hse-form-control">
                                    </div>
                                    <div class="hse-form-group">
                                        <label>Tingkat Severitas Insiden</label>
                                        <select id="incSeverity" class="hse-form-control">
                                            <option value="Minor">Minor (Kerusakan Ringan, No Downtime > 1 Hari)</option>
                                            <option value="Moderate">Moderate (Kerusakan Sedang, Lock Unit ACCIDENT_HOLD)</option>
                                            <option value="Critical">Critical (Kerusakan Komponen Utama / Major Impact)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="hse-form-group">
                                    <label>Kronologi Kejadian (Objektif Tanpa Opini)</label>
                                    <textarea id="incChronology" rows="3" class="hse-form-control" placeholder="Jelaskan alur kejadian dari awal manuver hingga terjadi dampak kerusakan..."></textarea>
                                </div>
                            </div>

                            <!-- STEP 2 -->
                            <div id="formStep2" style="display:none;">
                                <h4 style="font-size:0.9rem; margin-bottom:10px; color:var(--dark);">Kondisi Lingkungan Saat Kejadian:</h4>
                                <div class="hse-form-grid">
                                    <div class="hse-form-group">
                                        <label>Cuaca</label>
                                        <select id="envWeather" class="hse-form-control">
                                            <option>Cerah</option>
                                            <option>Hujan Gerimis</option>
                                            <option>Hujan Deras</option>
                                            <option>Bersebab / Kabut</option>
                                        </select>
                                    </div>
                                    <div class="hse-form-group">
                                        <label>Kondisi Jalan / Area</label>
                                        <select id="envRoad" class="hse-form-control">
                                            <option>Kering & Rata</option>
                                            <option>Lumpur / Licin</option>
                                            <option>Gembur / Unstable Slope</option>
                                            <option>Berlubang / Berbatu</option>
                                        </select>
                                    </div>
                                    <div class="hse-form-group">
                                        <label>Penerangan Area</label>
                                        <select id="envLighting" class="hse-form-control">
                                            <option>Siang Hari (Terang)</option>
                                            <option>Sore Hari (Redup)</option>
                                            <option>Malam Hari (Lampu Memadai)</option>
                                            <option>Malam Hari (Gelap/Kurang Lampu)</option>
                                        </select>
                                    </div>
                                </div>

                                <h4 style="font-size:0.9rem; margin-bottom:10px; color:var(--dark); margin-top:15px;">Dampak Insiden & Estimasi Kerugian:</h4>
                                <div class="hse-form-grid">
                                    <div class="hse-form-group">
                                        <label>Bagian Fisik Terdampak</label>
                                        <input type="text" id="impPhysical" placeholder="Misal: Engsel Pintu Ombeng / Bucket Hydraulic" class="hse-form-control">
                                    </div>
                                    <div class="hse-form-group">
                                        <label>Estimasi Biaya Perbaikan (Rp)</label>
                                        <input type="number" id="impCost" placeholder="Misal: 5000000" class="hse-form-control">
                                    </div>
                                    <div class="hse-form-group">
                                        <label>Estimasi Downtime (Hari)</label>
                                        <input type="number" id="impDowntime" placeholder="Misal: 2" class="hse-form-control">
                                    </div>
                                </div>
                            </div>

                            <!-- STEP 3 -->
                            <div id="formStep3" style="display:none;">
                                <h4 style="font-size:0.9rem; margin-bottom:10px; color:var(--dark);">Analisa Awal Faktor Penyebab (Bolehh Pilih > 1):</h4>
                                <div class="hse-checkbox-grid">
                                    <label class="hse-checkbox-item"><input type="checkbox" id="chkHuman" value="Human factor"> Human Factor (Operator/Mekanik)</label>
                                    <label class="hse-checkbox-item"><input type="checkbox" id="chkMechanical" value="Mechanical factor"> Mechanical Factor (Komponen/Peralatan)</label>
                                    <label class="hse-checkbox-item"><input type="checkbox" id="chkEnvironmental" value="Environmental factor"> Environmental Factor (Cuaca/Jalan)</label>
                                    <label class="hse-checkbox-item"><input type="checkbox" id="chkProcedural" value="Procedural / System failure"> Procedural / System Failure (SOP)</label>
                                </div>

                                <div class="hse-form-group" style="margin-top:15px;">
                                    <label>Tindakan Segera (Corrective Action)</label>
                                    <textarea id="incCorrective" rows="2" class="hse-form-control" placeholder="Misal: Hentikan operasional unit, pengelasan ulang..."></textarea>
                                </div>

                                <div class="hse-form-group" style="margin-top:10px;">
                                    <label>Rekomendasi Pencegahan (Preventive Action / CAPA)</label>
                                    <textarea id="incPreventive" rows="2" class="hse-form-control" placeholder="Misal: Refresher training operator, update SOP..."></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="hse-modal-footer">
                        <button class="btn btn-secondary" id="btnPrevStep" onclick="window.navigateStep(-1)" style="display:none;">Kembali</button>
                        <button class="btn btn-primary" id="btnNextStep" onclick="window.navigateStep(1)">Lanjut Step 2</button>
                        <button class="btn btn-danger" id="btnSubmitIncident" onclick="window.submitIncidentForm()" style="display:none;">Simpan Laporan & Lock Unit</button>
                    </div>
                </div>
            </div>

            <!-- MODAL DETAIL ACCIDENT REPORT -->
            <div class="hse-modal-overlay" id="modalAccidentDetail">
                <div class="hse-modal-card">
                    <div class="hse-modal-header">
                        <h3><i class="fa-solid fa-file-lines"></i> Detail Resmi Laporan Insiden: <span id="detDocNo"></span></h3>
                        <button onclick="window.closeAccidentDetailModal()" style="border:none; background:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
                    </div>
                    <div class="hse-modal-body" id="detModalContent">
                        <!-- Populated via JS -->
                    </div>
                    <div class="hse-modal-footer">
                        <button class="btn btn-secondary" onclick="window.closeAccidentDetailModal()">Tutup</button>
                    </div>
                </div>
            </div>
        `;

        bindHSEEvents();
        renderHSEModuleContent();
    }

    // =========================================================================
    // 3. BINDINGS & EVENT HANDLERS
    // =========================================================================

    function bindHSEEvents() {
        const tabBtns = document.querySelectorAll('.hse-tab-btn');
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-hse-tab');
                
                tabBtns.forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.hse-tab-content').forEach(c => c.classList.remove('active'));

                btn.classList.add('active');
                const targetContent = document.getElementById(targetId);
                if (targetContent) targetContent.classList.add('active');
            });
        });
    }

    function renderHSEModuleContent() {
        renderLockBanner();
        renderAccidentTable();
        renderCapaTable();
        populateAssetSelect();
    }

    function renderLockBanner() {
        const bannerContainer = document.getElementById('hseActiveLockBanner');
        if (!bannerContainer) return;

        const locked = initialAccidentLogs.filter(a => a.isUnitLocked);
        if (locked.length > 0) {
            bannerContainer.innerHTML = `
                <div class="hse-lock-banner">
                    <div class="hse-lock-content">
                        <div class="hse-lock-icon"><i class="fa-solid fa-lock"></i></div>
                        <div class="hse-lock-text">
                            <h4>Peringatan Keselamatan: ${locked.length} Unit Dalam Status ACCIDENT_HOLD</h4>
                            <p>Unit berikut dikunci dari operasional & WO baru: <strong>${locked.map(a => a.unitCode).join(', ')}</strong>. Diperlukan persetujuan rilis resmi dari Equipment Mgr / Safety Head.</p>
                        </div>
                    </div>
                    <button class="btn btn-danger" onclick="document.querySelector('[data-hse-tab=\\'tab-capa-tracker\\']').click()">Cek Status Rilis</button>
                </div>
            `;
        } else {
            bannerContainer.innerHTML = '';
        }
    }

    function renderAccidentTable() {
        const tbody = document.getElementById('tbAccidentBody');
        if (!tbody) return;

        tbody.innerHTML = initialAccidentLogs.map((log, idx) => {
            let sevBadge = `<span class="hse-badge hse-badge-minor">${log.severity}</span>`;
            if (log.severity === 'Moderate') sevBadge = `<span class="hse-badge hse-badge-moderate">${log.severity}</span>`;
            else if (log.severity === 'Critical') sevBadge = `<span class="hse-badge hse-badge-critical">${log.severity}</span>`;

            let lockBadge = log.isUnitLocked ? 
                `<span class="hse-badge hse-badge-hold"><i class="fa-solid fa-lock"></i> ACCIDENT_HOLD</span>` : 
                `<span class="hse-badge hse-badge-released"><i class="fa-solid fa-lock-open"></i> RELEASED</span>`;

            let capaBadge = `<span class="pk-badge pk-badge-warning">${log.status}</span>`;
            if (log.status === 'Closed') capaBadge = `<span class="pk-badge pk-badge-success">Closed</span>`;
            else if (log.status === 'Investigating') capaBadge = `<span class="pk-badge pk-badge-danger">Investigating</span>`;

            return `
                <tr>
                    <td><strong>${escapeHtml(log.docNo)}</strong></td>
                    <td>${escapeHtml(log.incidentDate)}</td>
                    <td><strong class="text-primary">${escapeHtml(log.unitCode)}</strong><br><small class="text-muted">${escapeHtml(log.makeModel)}</small></td>
                    <td>${escapeHtml(log.location)}</td>
                    <td>${escapeHtml(log.operatorName)}</td>
                    <td>${sevBadge}</td>
                    <td style="font-weight:700;">Rp ${(log.impact.totalFinancialImpact / 1000000).toFixed(1)} Jt</td>
                    <td>${lockBadge}</td>
                    <td>${capaBadge}</td>
                    <td>
                        <button class="btn btn-primary" style="padding:4px 8px; font-size:0.78rem;" onclick="window.openAccidentDetail(${idx})"><i class="fa-solid fa-eye"></i> Detail</button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderCapaTable() {
        const tbody = document.getElementById('tbCapaBody');
        if (!tbody) return;

        tbody.innerHTML = initialAccidentLogs.map((log, idx) => {
            let lockBtn = log.isUnitLocked ? 
                `<button class="btn btn-success" style="padding:4px 10px; font-size:0.78rem;" onclick="window.releaseUnitHold(${idx})"><i class="fa-solid fa-key"></i> Otorisasi Rilis Unit</button>` :
                `<span class="pk-badge pk-badge-success"><i class="fa-solid fa-check"></i> Disetujui & Rilis</span>`;

            return `
                <tr>
                    <td><strong>${escapeHtml(log.unitCode)}</strong></td>
                    <td>${escapeHtml(log.docNo)} (${escapeHtml(log.severity)})</td>
                    <td style="font-size:0.82rem;">${escapeHtml(log.correctiveAction)}</td>
                    <td style="font-size:0.82rem;">${escapeHtml(log.preventiveAction)}</td>
                    <td><strong>${escapeHtml(log.operatorName)} / Safety PIC</strong></td>
                    <td>${log.isUnitLocked ? '<span class="hse-badge hse-badge-hold">HOLD</span>' : '<span class="hse-badge hse-badge-released">RELEASED</span>'}</td>
                    <td>${lockBtn}</td>
                </tr>
            `;
        }).join('');
    }

    function populateAssetSelect() {
        const select = document.getElementById('incAssetId');
        if (!select || !window.globalData || !window.globalData.assets) return;
        select.innerHTML = window.globalData.assets.map(a => `<option value="${a.id}">${a.id} (${a.category}) - Status: ${a.status}</option>`).join('');
    }

    // =========================================================================
    // 4. STEPPER & MODAL CONTROLLERS
    // =========================================================================

    window.openNewIncidentModal = function() {
        currentStep = 1;
        updateStepVisibility();
        populateAssetSelect();
        const overlay = document.getElementById('modalIncidentReport');
        if (overlay) overlay.classList.add('active');
    };

    window.closeIncidentModal = function() {
        const overlay = document.getElementById('modalIncidentReport');
        if (overlay) overlay.classList.remove('active');
    };

    window.navigateStep = function(direction) {
        if (direction === 1) {
            // Validation step 1
            if (currentStep === 1) {
                const chronology = document.getElementById('incChronology').value;
                if (!chronology) return alert('Mohon isi uraian kronologi kejadian terlebih dahulu.');
            }
        }
        currentStep += direction;
        if (currentStep < 1) currentStep = 1;
        if (currentStep > 3) currentStep = 3;
        updateStepVisibility();
    };

    function updateStepVisibility() {
        document.getElementById('formStep1').style.display = currentStep === 1 ? 'block' : 'none';
        document.getElementById('formStep2').style.display = currentStep === 2 ? 'block' : 'none';
        document.getElementById('formStep3').style.display = currentStep === 3 ? 'block' : 'none';

        document.getElementById('stepIndicator1').className = `hse-step-item ${currentStep === 1 ? 'active' : (currentStep > 1 ? 'completed' : '')}`;
        document.getElementById('stepIndicator2').className = `hse-step-item ${currentStep === 2 ? 'active' : (currentStep > 2 ? 'completed' : '')}`;
        document.getElementById('stepIndicator3').className = `hse-step-item ${currentStep === 3 ? 'active' : ''}`;

        document.getElementById('btnPrevStep').style.display = currentStep > 1 ? 'inline-block' : 'none';
        document.getElementById('btnNextStep').style.display = currentStep < 3 ? 'inline-block' : 'none';
        document.getElementById('btnSubmitIncident').style.display = currentStep === 3 ? 'inline-block' : 'none';

        if (currentStep === 1) document.getElementById('btnNextStep').innerText = 'Lanjut Step 2 (Lingkungan & Dampak)';
        else if (currentStep === 2) document.getElementById('btnNextStep').innerText = 'Lanjut Step 3 (Penyebab & CAPA)';
    }

    window.submitIncidentForm = function() {
        const assetId = document.getElementById('incAssetId').value;
        const datetime = document.getElementById('incDatetime').value || '2026-07-25 09:00';
        const location = document.getElementById('incLocation').value || 'Site Project';
        const operator = document.getElementById('incOperator').value || 'Driver Operasional';
        const tenure = document.getElementById('incTenure').value || '1 Tahun';
        const severity = document.getElementById('incSeverity').value;
        const chronology = document.getElementById('incChronology').value;

        const weather = document.getElementById('envWeather').value;
        const road = document.getElementById('envRoad').value;
        const lighting = document.getElementById('envLighting').value;
        const physical = document.getElementById('impPhysical').value || 'Kerusakan Fisik Unit';
        const cost = parseFloat(document.getElementById('impCost').value) || 5000000;
        const downtime = parseInt(document.getElementById('impDowntime').value) || 2;

        const causes = [];
        if (document.getElementById('chkHuman').checked) causes.push('Human factor');
        if (document.getElementById('chkMechanical').checked) causes.push('Mechanical factor');
        if (document.getElementById('chkEnvironmental').checked) causes.push('Environmental factor');
        if (document.getElementById('chkProcedural').checked) causes.push('Procedural / System failure');

        const corrective = document.getElementById('incCorrective').value || 'Unit dihentikan untuk perbaikan.';
        const preventive = document.getElementById('incPreventive').value || 'Safety briefing ulang operator.';

        const docNo = `0${initialAccidentLogs.length + 1}/ACC/BRA/SITE/2026`;
        const codeOnly = assetId.split(' ')[0];

        const isLocked = severity === 'Moderate' || severity === 'Critical';

        initialAccidentLogs.unshift({
            docNo: docNo,
            reportDate: '2026-07-25',
            incidentDate: datetime.replace('T', ' '),
            unitName: assetId,
            unitCode: codeOnly,
            makeModel: assetId,
            licensePlate: 'Plat Unit',
            location: location,
            operatorName: operator,
            operatorTenure: tenure,
            chronology: chronology,
            environment: { weather, roadCondition: road, lighting, density: 'Normal', cargo: 'Standar' },
            impact: { physicalDamage: physical, estimatedRepairCost: cost, estimatedDowntimeDays: downtime, productionImpact: 'Delay Operasional', totalFinancialImpact: cost * 1.5 },
            causeFactors: causes.length ? causes : ['Human factor'],
            causeExplanation: 'Analisa awal faktor operasional.',
            correctiveAction: corrective,
            preventiveAction: preventive,
            severity: severity,
            isUnitLocked: isLocked,
            status: 'Investigating',
            tarNo: '-'
        });

        // Trigger System State Change if locked
        if (isLocked && window.globalData && window.globalData.assets) {
            const targetAsset = window.globalData.assets.find(a => a.id === assetId);
            if (targetAsset) {
                if (window.globalData.summary && window.globalData.summary.status_counts[targetAsset.status]) {
                    window.globalData.summary.status_counts[targetAsset.status]--;
                }
                targetAsset.status = 'ACCIDENT_HOLD';
                if (!window.globalData.summary.status_counts['ACCIDENT_HOLD']) window.globalData.summary.status_counts['ACCIDENT_HOLD'] = 0;
                window.globalData.summary.status_counts['ACCIDENT_HOLD']++;
            }
        }

        window.closeIncidentModal();
        alert(`Laporan Insiden ${docNo} berhasil disimpan. ${isLocked ? 'Unit otomatis dikunci ke status ACCIDENT_HOLD.' : ''}`);
        renderHSEModuleContent();
    };

    window.openAccidentDetail = function(idx) {
        const log = initialAccidentLogs[idx];
        if (!log) return;

        document.getElementById('detDocNo').innerText = log.docNo;
        const container = document.getElementById('detModalContent');

        container.innerHTML = `
            <div style="background:#f8fafc; padding:15px; border-radius:6px; border:1px solid var(--border);">
                <h4 style="margin-bottom:8px; color:var(--dark);"><i class="fa-solid fa-id-card text-primary"></i> I. DATA UMUM & IDENTITAS UNIT</h4>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:0.85rem;">
                    <div><strong>Kode Unit:</strong> ${escapeHtml(log.unitCode)}</div>
                    <div><strong>Nama Unit:</strong> ${escapeHtml(log.unitName)}</div>
                    <div><strong>Lokasi Kejadian:</strong> ${escapeHtml(log.location)}</div>
                    <div><strong>Waktu Kejadian:</strong> ${escapeHtml(log.incidentDate)}</div>
                    <div><strong>Nama Operator:</strong> ${escapeHtml(log.operatorName)}</div>
                    <div><strong>Masa Kerja:</strong> ${escapeHtml(log.operatorTenure)}</div>
                </div>
            </div>

            <div>
                <h4 style="margin-bottom:8px; color:var(--dark);"><i class="fa-solid fa-align-left text-danger"></i> II. KRONOLOGI KEJADIAN</h4>
                <p style="font-size:0.88rem; background:#fff; padding:12px; border:1px solid var(--border); border-radius:6px; line-height:1.6;">${escapeHtml(log.chronology)}</p>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                <div style="background:#fff; padding:12px; border:1px solid var(--border); border-radius:6px;">
                    <h4 style="font-size:0.9rem; margin-bottom:8px;"><i class="fa-solid fa-cloud-sun text-warning"></i> III. KONDISI LINGKUNGAN</h4>
                    <ul style="font-size:0.82rem; padding-left:16px; line-height:1.5;">
                        <li><strong>Cuaca:</strong> ${escapeHtml(log.environment.weather)}</li>
                        <li><strong>Kondisi Jalan:</strong> ${escapeHtml(log.environment.roadCondition)}</li>
                        <li><strong>Penerangan:</strong> ${escapeHtml(log.environment.lighting)}</li>
                    </ul>
                </div>
                <div style="background:#fff; padding:12px; border:1px solid var(--border); border-radius:6px;">
                    <h4 style="font-size:0.9rem; margin-bottom:8px;"><i class="fa-solid fa-coins text-danger"></i> IV. DAMPAK INSIDEN</h4>
                    <ul style="font-size:0.82rem; padding-left:16px; line-height:1.5;">
                        <li><strong>Kerusakan Fisik:</strong> ${escapeHtml(log.impact.physicalDamage)}</li>
                        <li><strong>Estimasi Downtime:</strong> ${log.impact.estimatedDowntimeDays} Hari</li>
                        <li><strong>Total Kerugian:</strong> Rp ${(log.impact.totalFinancialImpact / 1000000).toFixed(1)} Jt</li>
                    </ul>
                </div>
            </div>

            <div style="background:#fff; padding:12px; border:1px solid var(--border); border-radius:6px;">
                <h4 style="font-size:0.9rem; margin-bottom:8px;"><i class="fa-solid fa-magnifying-glass text-info"></i> V. ANALISA PENYEBAB & CAPA ACTION</h4>
                <p style="font-size:0.85rem;"><strong>Faktor Utama:</strong> ${log.causeFactors.join(', ')}</p>
                <p style="font-size:0.85rem; margin-top:4px;"><strong>Corrective Action:</strong> ${escapeHtml(log.correctiveAction)}</p>
                <p style="font-size:0.85rem; margin-top:4px;"><strong>Preventive Action:</strong> ${escapeHtml(log.preventiveAction)}</p>
            </div>
        `;

        const modal = document.getElementById('modalAccidentDetail');
        if (modal) modal.classList.add('active');
    };

    window.closeAccidentDetailModal = function() {
        const modal = document.getElementById('modalAccidentDetail');
        if (modal) modal.classList.remove('active');
    };

    window.releaseUnitHold = function(idx) {
        const log = initialAccidentLogs[idx];
        if (!log) return;

        if (confirm(`Apakah Anda yakin memverifikasi bahwa unit ${log.unitCode} telah lulus perbaikan & safety audit untuk di-RELEASE dari ACCIDENT_HOLD?`)) {
            log.isUnitLocked = false;
            log.status = 'Closed';

            // Restore asset status if available
            if (window.globalData && window.globalData.assets) {
                const asset = window.globalData.assets.find(a => a.id.indexOf(log.unitCode) > -1);
                if (asset) {
                    asset.status = 'READY';
                }
            }

            alert(`Unit ${log.unitCode} berhasil di-RELEASE. Status kembali READY.`);
            renderHSEModuleContent();
        }
    };

    window.filterAccidentTable = function() {
        const input = document.getElementById('searchAccident');
        if (!input) return;
        const filter = input.value.toUpperCase();
        const trs = document.getElementById('tbAccidentTable').getElementsByTagName('tr');

        for (let i = 1; i < trs.length; i++) {
            let visible = false;
            const tds = trs[i].getElementsByTagName('td');
            if (tds.length > 2) {
                const codeTxt = tds[2].textContent || '';
                const locTxt = tds[3].textContent || '';
                if (codeTxt.toUpperCase().indexOf(filter) > -1 || locTxt.toUpperCase().indexOf(filter) > -1) {
                    visible = true;
                }
            }
            trs[i].style.display = visible ? "" : "none";
        }
    };

    window.exportHSEReport = function() {
        const headers = ['No Dokumen', 'Tanggal', 'Kode Unit', 'Lokasi', 'Operator', 'Severitas', 'Estimasi Biaya', 'Status Lock', 'Status CAPA'];
        const rows = initialAccidentLogs.map(l => [
            l.docNo, l.incidentDate, l.unitCode, l.location, l.operatorName, l.severity, l.impact.totalFinancialImpact, l.isUnitLocked ? 'LOCKED' : 'RELEASED', l.status
        ]);
        const csv = [headers, ...rows].map(row => row.map(val => `"${String(val).replace(/"/g, '""')}"`).join(',')).join('\r\n');
        const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'Laporan_Insiden_Accident_HSE_2026.csv';
        link.click();
        alert('Laporan Insiden & HSE berhasil diekspor ke CSV.');
    };

    // Auto Mount
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createHSEModule);
    } else {
        createHSEModule();
    }
})();
