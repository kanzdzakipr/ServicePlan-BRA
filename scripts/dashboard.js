(function () {
    'use strict';

    const field = (key, label, type = 'text', required = false, options = [], full = false, placeholder = '') => ({
        key, label, type, required, options, full, placeholder
    });
    const column = (key, label, type = 'text', readonly = false, options = []) => ({
        key, label, type, readonly, options
    });

    const formSchemas = [
        {
            id: 'bapp', code: 'BAPP', category: 'Logistik & Warehouse',
            title: 'Berita Acara Penerimaan/Penyerahan Barang',
            description: 'Pencatatan penerimaan barang, kondisi fisik, kekurangan, dan pengesahan.',
            source: 'FORM_BAPP_Penerimaan_Penyerahan_Barang_Parts_Tabulasi.md',
            seedFields: {
                nomor: 'BAPP / ___ / ___ / ___ / 20__'
            },
            fields: [
                field('nomor', 'Nomor BAPP', 'text', true, [], false, 'BAPP / ___ / ___ / ___ / 20__'),
                field('tanggal', 'Tanggal penerimaan', 'date', true),
                field('pengirim', 'Pengirim', 'text', true),
                field('alamat', 'Alamat pengirim', 'textarea', false, [], true),
                field('nomor_polisi', 'Nomor polisi kendaraan'),
                field('nomor_po', 'Order pembelian nomor'),
                field('tanggal_po', 'Tanggal order pembelian', 'date'),
                field('nomor_faktur', 'Nomor faktur'),
                field('tanggal_faktur', 'Tanggal faktur', 'date')
            ],
            tableTitle: 'Barang / bahan / spare parts',
            columns: [
                column('nama', 'Nama barang / spare parts'),
                column('satuan', 'Sat'),
                column('jumlah', 'Jumlah', 'number'),
                column('baik', 'Baik', 'number'),
                column('rusak', 'Rusak', 'number'),
                column('kurang', 'Kurang', 'number'),
                column('keterangan', 'Keterangan')
            ]
        },
        {
            id: 'bast-mde1', code: 'MDE-1', category: 'Asset & Serah Terima',
            title: 'Berita Acara Serah Terima Alat Berat',
            description: 'Dokumen empat rangkap untuk serah terima alat berat dan dasar mobilisasi.',
            source: 'Form_BAST_MDE-1_Tabulasi.md',
            fields: [
                field('project', 'Project', 'text', true),
                field('tanggal', 'Tanggal', 'date', true),
                field('nomor_urut', 'Nomor urut'),
                field('kode_alat', 'Nomor kode alat', 'text', true),
                field('dari', 'Telah diterima dari', 'text', true),
                field('kepada', 'Diserahkan kepada', 'text', true),
                field('alamat_penyerah', 'Alamat yang menyerahkan', 'textarea'),
                field('alamat_penerima', 'Alamat yang menerima', 'textarea'),
                field('jenis_alat', 'Nama barang / jenis alat', 'text', true),
                field('merek_model', 'Merek / engine model'),
                field('jenis_serah_terima', 'Jenis serah terima', 'select', true, ['Pembelian baru', 'Mobilisasi', 'Sewa-menyewa', 'Pinjaman', 'Pemakaian karya terakhir']),
                field('nomor_kontrak', 'Nomor kontrak / dasar'),
                field('project_asal', 'Project asal'),
                field('project_tujuan', 'Project tujuan'),
                field('hm_om', 'HM/OM saat serah terima', 'number'),
                field('lampiran', 'Nomor lampiran checklist')
            ],
            tableTitle: 'Kelengkapan dan catatan serah terima',
            columns: [column('item', 'Item / kelengkapan'), column('jumlah', 'Jumlah', 'number'), column('kondisi', 'Kondisi', 'select', false, ['Baik', 'Rusak', 'Kurang', 'Tidak ada']), column('keterangan', 'Keterangan')]
        },
        {
            id: 'bhw-in', code: 'BHW-IN', category: 'Logistik & Warehouse',
            title: 'Buku Harian Warehouse (Barang Masuk)',
            description: 'Log penerimaan spare parts dan pembaruan saldo persediaan.',
            source: 'FORM_BHW_IN_Tabulasi.md',
            fields: [
                field('nomor_log', 'Nomor log', 'text', true),
                field('job_kontrak', 'Job kontrak'),
                field('project', 'Project', 'text', true),
                field('tanggal', 'Tanggal laporan', 'date', true)
            ],
            tableTitle: 'Transaksi penerimaan barang',
            calculation: 'warehouseIn',
            calculationNote: 'Saldo sekarang dihitung otomatis: saldo sebelumnya + jumlah diterima.',
            columns: [
                column('tanggal', 'Tanggal', 'date'),
                column('bapb', 'No. BAPB'),
                column('dari', 'Terima dari'),
                column('nama', 'Nama parts'),
                column('satuan', 'Satuan'),
                column('jumlah', 'Jumlah', 'number'),
                column('saldo_lalu', 'Saldo lalu', 'number'),
                column('saldo_sekarang', 'Saldo sekarang', 'number', true),
                column('keterangan', 'Keterangan')
            ]
        },
        {
            id: 'bhw-out', code: 'BHW-OUT', category: 'Logistik & Warehouse',
            title: 'Buku Harian Warehouse (Barang Keluar)',
            description: 'Log pengeluaran spare parts, penerima, persediaan, dan sisa stok.',
            source: 'FORM_BHW_OUT_Tabulasi.md',
            fields: [
                field('nomor_log', 'Nomor log', 'text', true),
                field('job_kontrak', 'Job kontrak'),
                field('project', 'Project', 'text', true),
                field('tanggal', 'Tanggal laporan', 'date', true)
            ],
            tableTitle: 'Transaksi pengeluaran barang',
            calculation: 'warehouseOut',
            calculationNote: 'Sisa dihitung otomatis: persediaan warehouse − jumlah diberikan.',
            columns: [
                column('tanggal', 'Tanggal', 'date'),
                column('nomor_bukti', 'No. bukti kirim'),
                column('tujuan', 'Dikirim ke'),
                column('nama', 'Nama parts'),
                column('satuan', 'Satuan'),
                column('persediaan', 'Persediaan', 'number'),
                column('diberikan', 'Diberikan', 'number'),
                column('sisa', 'Sisa', 'number', true),
                column('keterangan', 'Keterangan')
            ]
        },
        {
            id: 'bukti-kirim', code: 'BK', category: 'Logistik & Warehouse',
            title: 'Bukti Kirim/Terima Barang Intern',
            description: 'Bukti mutasi barang internal dari warehouse ke penerima atau tujuan.',
            source: 'FORM_Bukti_Kirim_dari_Warehouse_Tabulasi.md',
            fields: [
                field('transaksi', 'Jenis transaksi', 'select', true, ['Kirim', 'Terima']),
                field('nomor', 'Nomor bukti', 'text', true, [], false, 'BK/____/Dept-Equip/__/20__'),
                field('dari', 'Dari', 'text', true, [], false, 'Warehouse'),
                field('ke', 'Ke', 'text', true),
                field('kota', 'Kota'),
                field('tanggal', 'Tanggal', 'date', true)
            ],
            tableTitle: 'Barang / suku cadang',
            columns: [column('nama', 'Nama barang / suku cadang'), column('satuan', 'Sat'), column('jumlah', 'Jumlah', 'number'), column('keterangan', 'Keterangan')]
        },
        {
            id: 'mde-02', code: 'MDE-02', category: 'Asset & Serah Terima',
            title: 'Kartu Pemeriksaan Alat Berat',
            description: 'Pemeriksaan data teknis, attachment, perlengkapan, dan kondisi unit.',
            source: 'Form_Kartu_Pemeriksaan_A2B_MDE-02_Tabulasi.md',
            fields: [
                field('project', 'Project', 'text', true),
                field('tanggal', 'Tanggal pemeriksaan', 'date', true),
                field('nomor_urut', 'Nomor urut'),
                field('kode_alat', 'Nomor kode alat', 'text', true),
                field('merek_alat', 'Merek alat'),
                field('jenis_alat', 'Jenis alat', 'text', true),
                field('tipe_alat', 'Tipe alat'),
                field('tahun', 'Tahun pembuatan', 'number'),
                field('nomor_seri', 'Nomor seri'),
                field('kapasitas', 'Kapasitas'),
                field('merek_mesin', 'Merek mesin / motor'),
                field('tipe_mesin', 'Tipe mesin / motor')
            ],
            tableTitle: 'Item pemeriksaan unit',
            columns: [column('kelompok', 'Kelompok', 'select', false, ['Attachment', 'Perlengkapan / tools', 'Engine Group', 'Electrical Group', 'Transmission Group', 'Undercarriage', 'Safety']), column('item', 'Uraian pemeriksaan'), column('kondisi', 'Kondisi', 'select', false, ['Baik', 'Normal', 'Perlu perbaikan', 'Tidak ada']), column('keterangan', 'Keterangan')]
        },
        {
            id: 'kalibrasi', code: 'LPK', category: 'Maintenance',
            title: 'Laporan Pelaksanaan Kalibrasi',
            description: 'Rencana, realisasi, hasil kalibrasi, dan tindak lanjut alat ukur.',
            source: 'FORM_Laporan_Pelaksanaan_Kalibrasi_Tabulasi.md',
            fields: [
                field('periode', 'Periode laporan', 'month', true),
                field('lokasi', 'Lokasi pengesahan', 'text', true),
                field('tanggal', 'Tanggal pengesahan', 'date', true),
                field('dibuat_oleh', 'Dibuat oleh'),
                field('diperiksa_oleh', 'Diperiksa oleh')
            ],
            tableTitle: 'Pelaksanaan kalibrasi',
            columns: [
                column('nama', 'Nama alat survey / ukur'),
                column('identifikasi', 'Nomor identifikasi'),
                column('merk', 'Merk / type'),
                column('rencana', 'Rencana kalibrasi', 'date'),
                column('pelaksanaan', 'Tanggal pelaksanaan', 'date'),
                column('jenis', 'Pelaksanaan', 'select', false, ['Intern', 'Ekstern']),
                column('hasil', 'Hasil', 'select', false, ['Memenuhi', 'Tidak memenuhi']),
                column('tindak_lanjut', 'Tindak lanjut', 'select', false, ['Selesai', 'Perlu perbaikan', 'Kalibrasi ulang', 'Menunggu sertifikat']),
                column('keterangan', 'Keterangan')
            ]
        },
        {
            id: 'lho', code: 'LHO', category: 'Operasional',
            title: 'Laporan Harian Operasi Alat',
            description: 'Rekap jam operasi, hour meter, BBM, cuaca, dan verifikasi operator.',
            source: 'FORM_LHO_Tabulasi.md',
            fields: [
                field('periode', 'Bulan / tahun', 'month', true),
                field('jenis_alat', 'Jenis alat', 'text', true),
                field('tipe_merk', 'Tipe / merk'),
                field('lokasi', 'Lokasi alat', 'text', true),
                field('operator', 'Operator', 'text', true),
                field('id_alat', 'ID alat', 'text', true)
            ],
            tableTitle: 'Operasi harian',
            calculation: 'lho',
            calculationNote: 'Jumlah jam kerja dan HM operasi dihitung otomatis dari nilai awal dan akhir.',
            columns: [
                column('tanggal', 'Tanggal', 'date'),
                column('jam_awal', 'Jam awal', 'time'),
                column('jam_akhir', 'Jam akhir', 'time'),
                column('jam_kerja', 'Jam kerja', 'number', true),
                column('hm_awal', 'HM awal', 'number'),
                column('hm_akhir', 'HM akhir', 'number'),
                column('hm_operasi', 'HM operasi', 'number', true),
                column('site', 'Site area'),
                column('bbm', 'BBM (L)', 'number'),
                column('cuaca', 'Cuaca', 'select', false, ['Cerah', 'Berawan', 'Hujan']),
                column('keterangan', 'Keterangan'),
                column('status', 'Verifikasi', 'select', false, ['Draft', 'Terverifikasi', 'Perlu koreksi'])
            ]
        },
        {
            id: 'p2h-excavator', code: 'P2H-HEX', category: 'Operasional',
            title: 'P2H (Hydraulic Excavator)',
            description: 'Pelaksanaan perawatan harian excavator dengan kondisi dan tindakan per slot.',
            source: 'Form_P2H_Hydraulic_Excavator_Tabulasi.md',
            fields: [
                field('bulan', 'Bulan pemeriksaan', 'month', true),
                field('model', 'Model / unit', 'select', true, ['PC 200-8 MO', 'PC 200-10 MO CE', '320 GX', '320 GC']),
                field('operator', 'Nama operator', 'text', true),
                field('nrp', 'NRP'),
                field('code_number', 'Code number', 'text', true),
                field('serial_number', 'Serial number'),
                field('job_site', 'Job site', 'text', true),
                field('tanggal_slot', 'Tanggal pelaksanaan', 'date', true),
                field('hm_sebelum', 'HM sebelum operasi', 'number'),
                field('hm_selesai', 'HM selesai operasi', 'number')
            ],
            tableTitle: 'Checklist pemeriksaan harian',
            columns: [
                column('kelompok', 'Tahap', 'select', false, ['Sebelum pemanasan', 'Setelah pemanasan', 'Saat operasi', 'Setelah operasi']),
                column('item', 'Item pemeriksaan'),
                column('kondisi', 'Kondisi', 'select', false, ['V — Normal', 'X — Tidak normal', 'OK — Sudah diperbaiki']),
                column('tambahan', 'Penambahan oil / cairan (L)', 'number'),
                column('tindakan', 'Tindakan / catatan')
            ],
            seedRows: [
                { kelompok: 'Sebelum pemanasan', item: 'Kekencangan track dan kebocoran undercarriage' },
                { kelompok: 'Sebelum pemanasan', item: 'Oil engine, hydraulic, coolant, dan fuel' },
                { kelompok: 'Setelah pemanasan', item: 'Panel indikator dan sistem electrical' }
            ]
        },
        {
            id: 'p2h-roller', code: 'P2H-SDR', category: 'Operasional',
            title: 'P2H (Single Drum Rollers)',
            description: 'Pelaksanaan perawatan harian compactor/roller dengan checklist kondisi.',
            source: 'Form_P2H_Single_Drum_Rollers_Tabulasi.md',
            fields: [
                field('bulan', 'Bulan pemeriksaan', 'month', true),
                field('model', 'Model / unit', 'select', true, ['BW211D-40', 'CS10 GC', 'SV525']),
                field('operator', 'Nama operator', 'text', true),
                field('nrp', 'NRP'),
                field('code_number', 'Code number', 'text', true),
                field('serial_number', 'Serial number'),
                field('job_site', 'Job site', 'text', true),
                field('tanggal_slot', 'Tanggal pelaksanaan', 'date', true),
                field('hm_sebelum', 'HM sebelum operasi', 'number'),
                field('hm_selesai', 'HM selesai operasi', 'number')
            ],
            tableTitle: 'Checklist pemeriksaan harian',
            columns: [
                column('kelompok', 'Tahap', 'select', false, ['Sebelum pemanasan', 'Setelah pemanasan', 'Saat operasi', 'Setelah operasi']),
                column('item', 'Item pemeriksaan'),
                column('kondisi', 'Kondisi', 'select', false, ['V — Normal', 'X — Tidak normal', 'OK — Sudah diperbaiki']),
                column('tambahan', 'Penambahan oil / cairan (L)', 'number'),
                column('tindakan', 'Tindakan / catatan')
            ],
            seedRows: [
                { kelompok: 'Sebelum pemanasan', item: 'Safety device, fan belt, blower, dan alternator' },
                { kelompok: 'Sebelum pemanasan', item: 'Oil engine dan oil hydraulic' },
                { kelompok: 'Sebelum pemanasan', item: 'Kondisi drum dan mounting rubber' }
            ]
        },
        {
            id: 'penyerahan-ekspedisi', code: 'BAPE', category: 'Asset & Serah Terima',
            title: 'Penyerahan A2B kepada Ekspedisi',
            description: 'Berita acara alat berat yang diserahkan kepada penyedia ekspedisi.',
            source: 'FORM_Penyerahan_A2B_kepada_Expedisi_Tabulasi.md',
            fields: [
                field('nomor', 'Nomor BAPE', 'text', true, [], false, '___ / BAPE / ALAT / YARD / ___ / 20__'),
                field('pengirim', 'Pengirim', 'text', true),
                field('alamat', 'Alamat', 'textarea', false, [], true),
                field('nomor_polisi', 'Nomor polisi'),
                field('kontrak_angkutan', 'Nomor kontrak angkutan'),
                field('tanggal', 'Tanggal', 'date', true),
                field('penerima', 'Dikirim kepada', 'text', true),
                field('konfirmasi', 'Konfirmasi / kontak')
            ],
            tableTitle: 'Alat berat yang diserahkan',
            columns: [
                column('nama', 'Nama / identitas alat berat'),
                column('satuan', 'Satuan'),
                column('jumlah', 'Jumlah', 'number'),
                column('baik', 'Baik', 'number'),
                column('rusak', 'Rusak', 'number'),
                column('kurang', 'Kurang', 'number'),
                column('keterangan', 'Keterangan')
            ]
        },
        {
            id: 'ppb', code: 'P-3', category: 'Logistik & Warehouse',
            title: 'PPB (Pesanan Pembelian/Pengadaan Barang)',
            description: 'Pesanan pembelian dengan harga, PPN 11%, nilai total, dan penyerahan.',
            source: 'FORM_PPB_Pengadaan_Barang_Tabulasi.md',
            seedFields: {
                nomor_ppb: '___ / Dept-Equip / ___ / 20__'
            },
            fields: [
                field('nomor_ppb', 'Nomor PPB', 'text', true, [], false, '___ / Dept-Equip / ___ / 20__'),
                field('kepada', 'Kepada Yth.', 'text', true),
                field('nomor_kontrak', 'Nomor kontrak'),
                field('project', 'Project', 'text', true),
                field('nomor_spb', 'Nomor SPB'),
                field('nomor_penawaran', 'Nomor surat penawaran'),
                field('tanggal_penawaran', 'Tanggal surat penawaran', 'date'),
                field('batas_penyerahan', 'Penyerahan paling lambat', 'date', true),
                field('tempat_penyerahan', 'Tempat penyerahan', 'textarea', true, [], true)
            ],
            tableTitle: 'Barang yang dipesan',
            calculation: 'ppb',
            calculationNote: 'Jumlah harga, subtotal, PPN 11%, dan total dihitung otomatis.',
            columns: [
                column('nama', 'Nama barang / bahan'),
                column('sc', 'Satuan / No. SC'),
                column('satuan', 'Sat'),
                column('jumlah', 'Jumlah', 'number'),
                column('harga', 'Harga satuan (Rp)', 'number'),
                column('total', 'Jumlah harga (Rp)', 'number', true),
                column('keterangan', 'Keterangan')
            ]
        },
        {
            id: 'repair-overhaul', code: 'R&O', category: 'Maintenance',
            title: 'Surat Permohonan Perbaikan (Repair & Overhaul)',
            description: 'Permohonan perbaikan komponen, analisa, solusi, estimasi, dan lampiran.',
            source: 'FORM_Repair_dan_Overhoul_RO_Tabulasi.md',
            fields: [
                field('nomor', 'Nomor surat', 'text', true),
                field('tanggal', 'Tanggal', 'date', true),
                field('asset', 'Heavy equipment asset', 'text', true),
                field('serial_number', 'Serial number'),
                field('kode_unit', 'Kode unit', 'text', true),
                field('hm', 'Hour meter', 'number'),
                field('nama_parts', 'Nama parts', 'text', true),
                field('part_number', 'Part number'),
                field('temuan', 'Temuan / analisa kerusakan', 'textarea', true, [], true),
                field('riwayat', 'Riwayat penanganan', 'textarea', false, [], true),
                field('urgensi', 'Tingkat urgensi', 'select', true, ['Normal', 'Mendesak', 'Emergency']),
                field('estimasi_min', 'Estimasi biaya minimum (Rp)', 'number'),
                field('estimasi_max', 'Estimasi biaya maksimum (Rp)', 'number'),
                field('lampiran', 'Referensi lampiran / dokumentasi', 'textarea', false, [], true)
            ],
            tableTitle: 'Solusi yang diusulkan',
            columns: [column('solusi', 'Uraian solusi'), column('pic', 'PIC'), column('target', 'Target', 'date'), column('keterangan', 'Keterangan')]
        },
        {
            id: 'spb', code: 'P-1', category: 'Logistik & Warehouse',
            title: 'SPB (Surat Permintaan Barang)',
            description: 'Permintaan barang atau spare parts dari project kepada bagian logistik.',
            source: 'FORM_SPB_Permintaan_Parts_Tabulasi.md',
            seedFields: {
                kepada: 'Bagian Logistik',
                perusahaan: 'PT Bina Rekayasa Anugerah',
                nomor_spb: 'SPB / ___ / ___ / ___ / 20__'
            },
            fields: [
                field('nomor_kontrak', 'Nomor kontrak project'),
                field('project', 'Project', 'text', true),
                field('kepada', 'Kepada Yth.', 'text', true, [], false, 'Bagian Logistik'),
                field('perusahaan', 'Perusahaan', 'text', true, [], false, 'PT Bina Rekayasa Anugerah'),
                field('lokasi', 'Lokasi', 'text', true),
                field('nomor_spb', 'Nomor SPB', 'text', true, [], false, 'SPB / ___ / ___ / ___ / 20__'),
                field('tanggal', 'Tanggal permintaan', 'date', true),
                field('tanggal_kesiapan', 'Tanggal kesiapan barang', 'date')
            ],
            tableTitle: 'Permintaan barang / parts',
            columns: [
                column('nama', 'Nama barang / jenis / spare parts'),
                column('spesifikasi', 'Spesifikasi SC / PN'),
                column('satuan', 'Sat'),
                column('jumlah', 'Jumlah', 'number'),
                column('keterangan', 'Keterangan'),
                column('status', 'Status pemenuhan', 'select', false, ['Diajukan', 'Diproses', 'Tersedia', 'Parsial', 'Tidak tersedia'])
            ]
        },
        {
            id: 'spl', code: 'SPL', category: 'Operasional',
            title: 'Surat Perintah Lembur (Yard)',
            description: 'Penugasan lembur, daftar personel, durasi, dan total jam-orang.',
            source: 'FORM_SPL_Yard_Tabulasi.md',
            fields: [
                field('tanggal_spl', 'Tanggal SPL', 'date', true),
                field('mulai_periode', 'Mulai pelaksanaan', 'date', true),
                field('akhir_periode', 'Selesai pelaksanaan', 'date', true),
                field('lokasi', 'Area / lokasi', 'text', true),
                field('target', 'Target waktu / penyelesaian'),
                field('uraian', 'Uraian tugas lembur', 'textarea', true, [], true)
            ],
            tableTitle: 'Personel lembur',
            calculation: 'spl',
            calculationNote: 'Durasi tiap personel dan total jam-orang dihitung otomatis dari jam mulai dan berakhir.',
            columns: [
                column('nama', 'Nama'),
                column('jabatan', 'Jabatan'),
                column('lokasi', 'Lokasi'),
                column('mulai', 'Mulai', 'time'),
                column('selesai', 'Berakhir', 'time'),
                column('durasi', 'Jumlah jam', 'number', true)
            ]
        },
        {
            id: 'sppu', code: 'SPPU', category: 'Logistik & Warehouse',
            title: 'Surat Permintaan Parts Urgent (Yard)',
            description: 'Permintaan parts mendesak disertai HM, analisa kerusakan, dan solusi.',
            source: 'FORM_SPPU_Yard_Tabulasi.md',
            seedFields: {
                nomor: '___ / SPPU / YARD / BRA / ___ / 20__'
            },
            fields: [
                field('nomor', 'Nomor SPPU', 'text', true, [], false, '___ / SPPU / YARD / BRA / ___ / 20__'),
                field('tanggal', 'Tanggal pengajuan', 'date', true),
                field('lokasi', 'Lokasi', 'text', true),
                field('diajukan_oleh', 'Diajukan oleh / mekanik', 'text', true),
                field('verifikator', 'Verifikator Head Equipment'),
                field('approver', 'Asset Manager')
            ],
            tableTitle: 'Parts urgent',
            columns: [
                column('nama', 'Nama parts'),
                column('hm', 'HM', 'number'),
                column('operator', 'Operator'),
                column('pn', 'Part number'),
                column('jumlah', 'Jumlah', 'number'),
                column('satuan', 'Sat'),
                column('analisa', 'Analisa kerusakan'),
                column('solusi', 'Solusi')
            ]
        },
        {
            id: 'sppu-006-pf04-cs10', code: 'SPPU-006', category: 'Logistik & Warehouse',
            title: 'Template SPPU 006 (Parts Urgent PF-04 / CS-10)',
            description: 'Template kosong siap pakai untuk permintaan parts urgent, analisa kerusakan, tindak lanjut, lampiran, dan pengesahan.',
            source: 'SPPU_006_PF-04_CS10_Tabulasi.md',
            seedFields: {
                nomor: '___/SPPU/PF-04/WS/__/20__'
            },
            fields: [
                field('nomor', 'Nomor SPPU', 'text', true, [], false, '___/SPPU/PF-04/WS/__/20__'),
                field('tanggal', 'Tanggal pengajuan', 'date', true),
                field('lokasi', 'Lokasi', 'text', true, [], false, 'Contoh: Duri'),
                field('prioritas', 'Tingkat kebutuhan', 'select', true, ['Urgent dan diprioritaskan', 'Urgent', 'Prioritas normal']),
                field('jenis_unit', 'Jenis unit', 'text', true, [], false, 'Contoh: Soil Compactor CS-10'),
                field('serial_number', 'Serial number', 'text', true),
                field('hour_meter', 'Hour meter unit', 'number', true, [], false, '0'),
                field('operator', 'Operator'),
                field('project', 'Project / kebutuhan', 'text', true),
                field('sistem', 'Sistem terkait', 'text', false, [], false, 'Contoh: Fitur pemadatan dan brake system'),
                field('analisa', 'Analisa kerusakan', 'textarea', true, [], true, 'Jelaskan gejala, faktor penyebab, hasil pemeriksaan, dan riwayat perbaikan.'),
                field('dampak', 'Dampak operasional', 'textarea', true, [], true, 'Jelaskan fungsi unit yang terganggu dan risiko bila parts belum diganti.'),
                field('tindak_lanjut', 'Solusi dan tindak lanjut', 'textarea', true, [], true, 'Tuliskan tindakan penggantian, pemeriksaan wiring, pengujian fungsi, dan langkah lanjutan lain.'),
                field('instruksi', 'Instruksi / catatan prioritas', 'textarea', false, [], true),
                field('diajukan_oleh', 'Diajukan oleh'),
                field('jabatan_pengaju', 'Jabatan pengaju'),
                field('diadakan_oleh', 'Diadakan oleh'),
                field('jabatan_pengadaan', 'Jabatan pengadaan'),
                field('disetujui_oleh', 'Mengetahui / disetujui oleh'),
                field('jabatan_penyetuju', 'Jabatan penyetuju'),
                field('lampiran', 'Dokumentasi lampiran', 'textarea', false, [], true, 'Daftar foto unit, pemeriksaan, komponen, panel instrumen, atau dokumen pendukung.')
            ],
            tableTitle: 'Tabulasi permintaan parts',
            calculation: 'sppu006',
            calculationNote: 'Jumlah jenis parts, total kuantitas, dan item dengan kuantitas terbesar dihitung otomatis.',
            columns: [
                column('nama', 'Nama parts'),
                column('hm', 'HM', 'number'),
                column('operator', 'Operator'),
                column('pn', 'Part number'),
                column('jumlah', 'Jumlah', 'number'),
                column('satuan', 'Satuan'),
                column('kelompok', 'Kelompok parts')
            ],
            seedRows: [{}, {}, {}, {}, {}, {}, {}, {}, {}],
            approvals: [
                { label: 'Diajukan oleh', nameField: 'diajukan_oleh', roleField: 'jabatan_pengaju' },
                { label: 'Diadakan oleh', nameField: 'diadakan_oleh', roleField: 'jabatan_pengadaan' },
                { label: 'Mengetahui / Disetujui', nameField: 'disetujui_oleh', roleField: 'jabatan_penyetuju' }
            ]
        },
        {
            id: 'maintenance-board', code: 'MB-A2B', category: 'Maintenance',
            title: 'Maintenance Board A2B',
            description: 'Rencana maintenance 500–2000 HM dan status pemesanan parts.',
            source: 'Maintenance_Board_A2B_Tabulasi.md',
            fields: [
                field('lokasi', 'Lokasi', 'text', true),
                field('tanggal', 'Tanggal pembaruan', 'date', true),
                field('dibuat_oleh', 'Dibuat oleh', 'text', true),
                field('jabatan', 'Jabatan'),
                field('departemen', 'Departemen', 'text', false, [], false, 'A2B Departement Equipment')
            ],
            tableTitle: 'Jadwal maintenance unit',
            columns: [
                column('kode', 'Kode unit'),
                column('jenis', 'Jenis A2B'),
                column('hm_awal', 'HM awal', 'number'),
                column('interval', 'Interval', 'select', false, ['500 HM', '1000 HM', '1500 HM', '2000 HM']),
                column('tanggal_hm', 'Tgl HM', 'date'),
                column('realisasi', 'Realisasi', 'date'),
                column('parts_pesan', 'Parts dipesan', 'date'),
                column('parts_tiba', 'Parts tiba', 'date'),
                column('keterangan', 'Keterangan')
            ]
        },
        {
            id: 'parts-weekly', code: 'RPW', category: 'Logistik & Warehouse',
            title: 'Report Parts Weekly',
            description: 'Rekap mingguan parts masuk, keluar, saldo kuantitas, dan nilai persediaan.',
            source: 'REPORT_PARTS_WEEKLY_Tabulasi.md',
            fields: [
                field('yard', 'Yard', 'text', true),
                field('jenis_parts', 'Jenis parts', 'text', true),
                field('pekan', 'Pekan ke-', 'number', true),
                field('tahun', 'Tahun', 'number', true),
                field('tanggal', 'Tanggal laporan', 'date', true)
            ],
            tableTitle: 'Rekap parts mingguan',
            calculation: 'weeklyParts',
            calculationNote: 'Kumulatif masuk/keluar, nilai transaksi, saldo, dan nilai saldo dihitung otomatis.',
            columns: [
                column('nama', 'Nama spare parts'),
                column('satuan', 'Sat'),
                column('harga', 'Harga satuan', 'number'),
                column('in_lalu', 'In s/d pekan lalu', 'number'),
                column('in_ini', 'In pekan ini', 'number'),
                column('in_total', 'In s/d pekan ini', 'number', true),
                column('out_lalu', 'Out s/d pekan lalu', 'number'),
                column('out_ini', 'Out pekan ini', 'number'),
                column('out_total', 'Out s/d pekan ini', 'number', true),
                column('saldo', 'Saldo', 'number', true),
                column('nilai_saldo', 'Nilai saldo', 'number', true),
                column('keterangan', 'Keterangan')
            ]
        }
    ];

    formSchemas.forEach(schema => {
        schema.fields.forEach(item => {
            item.required = true;
        });
        schema.columns.forEach(item => {
            item.required = !item.readonly;
        });
    });

    let currentPage = 1;
    let activeSchema = null;
    let activeDraft = null;
    const pageSize = 8;
    const storagePrefix = 'fleetmonitor-report-draft-';
    const historyStorageKey = 'fleetmonitor-report-history-v1';
    const evidenceRequiredFormIds = new Set(['ppb', 'spb', 'sppu', 'sppu-006-pf04-cs10']);
    const calculatedRowKeys = new Set(['saldo_sekarang', 'sisa', 'jam_kerja', 'hm_operasi', 'total', 'durasi', 'in_total', 'out_total', 'saldo', 'nilai_saldo']);
    const importRowMetadataKeys = new Set(['_import']);
    const maxSourceImageBytes = 8 * 1024 * 1024;
    const minImageLongSide = 1280;
    const minImageShortSide = 720;
    const maxStoredImportTypeIssues = 200;
    const maxTypeIssueRawLength = 240;

    function cloneData(value) {
        return JSON.parse(JSON.stringify(value));
    }

    function createEmptyDraft(schema) {
        const seedRows = schema.seedRows || [{}];
        return {
            fields: { ...(schema.seedFields || {}) },
            rows: seedRows.map(row => ({ ...row })),
            updatedAt: null
        };
    }

    function requiresEvidence(schema = activeSchema) {
        return Boolean(schema && evidenceRequiredFormIds.has(schema.id));
    }

    function readHistory() {
        try {
            const records = JSON.parse(localStorage.getItem(historyStorageKey));
            return Array.isArray(records) ? records : [];
        } catch (error) {
            return [];
        }
    }

    function writeHistory(records) {
        localStorage.setItem(historyStorageKey, JSON.stringify(records));
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatRupiah(value) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency', currency: 'IDR', maximumFractionDigits: 0
        }).format(Number(value) || 0);
    }

    function numberValue(value) {
        const number = Number(value);
        return Number.isFinite(number) ? number : 0;
    }

    function importedValueIsEmpty(value) {
        return value == null || (typeof value === 'string' && !value.trim());
    }

    function importedScalarText(value) {
        if (['string', 'number', 'boolean', 'bigint'].includes(typeof value)) {
            return String(value);
        }
        return null;
    }

    function comparableImportedValue(value) {
        return String(value == null ? '' : value)
            .normalize('NFKC')
            .toLocaleLowerCase('id')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function canonicalDateParts(yearValue, monthValue, dayValue) {
        const year = Number(yearValue);
        const month = Number(monthValue);
        const day = Number(dayValue);
        if (
            !Number.isInteger(year)
            || !Number.isInteger(month)
            || !Number.isInteger(day)
            || year < 1000
            || year > 9999
            || month < 1
            || month > 12
            || day < 1
            || day > 31
        ) return '';
        const probe = new Date(0);
        probe.setUTCHours(0, 0, 0, 0);
        probe.setUTCFullYear(year, month - 1, day);
        if (
            probe.getUTCFullYear() !== year
            || probe.getUTCMonth() !== month - 1
            || probe.getUTCDate() !== day
        ) return '';
        return `${String(year).padStart(4, '0')}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    }

    function normalizeImportedDate(value) {
        const text = importedScalarText(value)?.trim() || '';
        let match = text.match(/^(\d{4})[-/.](\d{1,2})[-/.](\d{1,2})$/);
        if (match) return canonicalDateParts(match[1], match[2], match[3]);
        match = text.match(/^(\d{1,2})[-/.](\d{1,2})[-/.](\d{4})$/);
        return match ? canonicalDateParts(match[3], match[2], match[1]) : '';
    }

    function normalizeImportedMonth(value) {
        const text = importedScalarText(value)?.trim() || '';
        const date = normalizeImportedDate(text);
        if (date) return date.slice(0, 7);
        let match = text.match(/^(\d{4})[-/.](\d{1,2})$/);
        if (!match) {
            match = text.match(/^(\d{1,2})[-/.](\d{4})$/);
            if (match) match = [match[0], match[2], match[1]];
        }
        if (!match) return '';
        const year = Number(match[1]);
        const month = Number(match[2]);
        if (year < 1000 || year > 9999 || month < 1 || month > 12) return '';
        return `${String(year).padStart(4, '0')}-${String(month).padStart(2, '0')}`;
    }

    function normalizeImportedTime(value) {
        const text = importedScalarText(value)?.trim() || '';
        const match = text.match(/^([01]?\d|2[0-3])[:.]([0-5]\d)(?::([0-5]\d))?$/);
        if (!match || (match[3] && match[3] !== '00')) return '';
        return `${String(match[1]).padStart(2, '0')}:${match[2]}`;
    }

    function normalizeImportedNumber(value, options = {}) {
        if (typeof value === 'number') {
            if (!Number.isFinite(value) || (!options.allowNegative && value < 0)) return '';
            return String(Object.is(value, -0) ? 0 : value);
        }
        const text = importedScalarText(value)?.trim() || '';
        if (/^[+-]?[1-9]\d*\.\d{3}$/.test(text)) return '';
        const sign = options.allowNegative ? '[+-]?' : '\\+?';
        const pattern = new RegExp(`^${sign}(?:\\d+(?:\\.\\d*)?|\\.\\d+)(?:[eE][+-]?\\d+)?$`);
        if (!pattern.test(text)) return '';
        const number = Number(text);
        if (!Number.isFinite(number) || (!options.allowNegative && number < 0)) return '';
        return String(Object.is(number, -0) ? 0 : number);
    }

    function normalizeImportedValue(definition, rawValue, options = {}) {
        if (definition?.readonly && !options.allowReadonly) {
            return {
                value: undefined,
                issue: importedValueIsEmpty(rawValue) ? null : 'readonly_ignored'
            };
        }
        if (importedValueIsEmpty(rawValue)) return { value: '', issue: null };
        const scalar = importedScalarText(rawValue);
        if (scalar == null) return { value: '', issue: 'unsupported_value_type' };
        const type = definition?.type || 'text';
        if (type === 'select') {
            const comparable = comparableImportedValue(scalar);
            const option = (definition.options || []).find(candidate => (
                comparableImportedValue(candidate) === comparable
            ));
            return option == null
                ? { value: '', issue: 'select_option_mismatch' }
                : { value: option, issue: null };
        }
        if (type === 'number') {
            const value = normalizeImportedNumber(rawValue, {
                allowNegative: Boolean(options.allowNegative)
            });
            return value === ''
                ? { value: '', issue: 'invalid_number' }
                : { value, issue: null };
        }
        if (type === 'date') {
            const value = normalizeImportedDate(rawValue);
            return value
                ? { value, issue: null }
                : { value: '', issue: 'invalid_date' };
        }
        if (type === 'month') {
            const value = normalizeImportedMonth(rawValue);
            return value
                ? { value, issue: null }
                : { value: '', issue: 'invalid_month' };
        }
        if (type === 'time') {
            const value = normalizeImportedTime(rawValue);
            return value
                ? { value, issue: null }
                : { value: '', issue: 'invalid_time' };
        }
        return {
            value: type === 'textarea'
                ? scalar.replace(/\r\n?/g, '\n').trim()
                : scalar.trim(),
            issue: null
        };
    }

    function draftValueTypeIssue(definition, value) {
        if (importedValueIsEmpty(value)) return null;
        const normalized = normalizeImportedValue(definition, value, {
            allowReadonly: true,
            allowNegative: Boolean(definition.readonly)
        });
        if (normalized.issue) return normalized.issue;
        if (
            ['select', 'date', 'month', 'time'].includes(definition.type)
            && String(value).trim() !== String(normalized.value)
        ) return 'non_canonical_value';
        return null;
    }

    function importTypeIssueRawPreview(value) {
        let text;
        try {
            text = typeof value === 'string' ? value : JSON.stringify(value);
        } catch (error) {
            text = String(value);
        }
        if (text == null) text = String(value);
        return {
            value: text.slice(0, maxTypeIssueRawLength),
            truncated: text.length > maxTypeIssueRawLength
        };
    }

    function timeDifference(start, end) {
        if (!start || !end) return 0;
        const startParts = start.split(':').map(Number);
        const endParts = end.split(':').map(Number);
        let minutes = (endParts[0] * 60 + endParts[1]) - (startParts[0] * 60 + startParts[1]);
        if (minutes < 0) minutes += 24 * 60;
        return Math.round((minutes / 60) * 100) / 100;
    }

    function createModuleMarkup() {
        const module = document.getElementById('reportModule');
        if (!module) return;
        module.innerHTML = `
            <nav class="report-view-tabs" aria-label="Navigasi laporan">
                <button class="report-view-tab active" type="button" role="tab" aria-selected="true" data-report-panel="templates">
                    <i class="fa-regular fa-file-lines"></i> Template Form
                </button>
                <button class="report-view-tab" type="button" role="tab" aria-selected="false" data-report-panel="import">
                    <i class="fa-solid fa-file-import"></i> Impor Dokumen
                    <span id="reportImportCount">0</span>
                </button>
                <button class="report-view-tab" type="button" role="tab" aria-selected="false" data-report-panel="history">
                    <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Laporan
                    <span id="reportHistoryCount">0</span>
                </button>
            </nav>
            <section class="catalog-workspace" id="reportCatalog">
                <div class="report-heading">
                    <div>
                        <div class="report-eyebrow">Pusat dokumen operasional</div>
                        <h1>Laporan & Form Terstandarisasi</h1>
                        <p>Pilih jenis dokumen, lengkapi data, lalu siapkan keluaran laporan yang konsisten dengan form Departemen Equipment.</p>
                    </div>
                    <div class="report-counter">
                        <i class="fa-solid fa-layer-group"></i>
                        <div><strong>${formSchemas.length}</strong><span>FORM TERSEDIA</span></div>
                    </div>
                </div>
                <div class="report-filter">
                    <div class="report-filter-field">
                        <label for="reportSearch">Pencarian</label>
                        <div class="report-control-wrap">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input id="reportSearch" class="report-input" type="search" placeholder="Cari nama, kode, atau fungsi form...">
                        </div>
                    </div>
                    <div class="report-filter-field">
                        <label for="reportCategory">Kategori</label>
                        <select id="reportCategory" class="report-select">
                            <option value="">Semua kategori</option>
                            ${[...new Set(formSchemas.map(item => item.category))].map(category => `<option>${escapeHtml(category)}</option>`).join('')}
                        </select>
                    </div>
                    <div class="report-filter-field">
                        <label for="reportSort">Urutkan</label>
                        <select id="reportSort" class="report-select">
                            <option value="title-asc">Nama A–Z</option>
                            <option value="title-desc">Nama Z–A</option>
                            <option value="category">Kategori</option>
                        </select>
                    </div>
                    <button class="report-apply" id="reportApply"><i class="fa-solid fa-filter"></i> Terapkan Filter</button>
                </div>
                <div class="report-results-meta">
                    <span id="reportResultCount"></span>
                    <span>Referensi: <strong>material/SOP/Form</strong></span>
                </div>
                <div class="report-grid" id="reportGrid"></div>
                <div class="report-pagination" id="reportPagination"></div>
            </section>
            <section class="report-history-workspace hidden" id="reportHistory">
                <div class="report-heading report-history-heading">
                    <div>
                        <div class="report-eyebrow">Database laporan lokal</div>
                        <h1>Riwayat Laporan Final</h1>
                        <p>Laporan yang sudah disimpan dapat dilihat, dicetak, atau digunakan ulang sebagai dasar laporan baru.</p>
                    </div>
                    <div class="history-storage-note"><i class="fa-solid fa-database"></i> Prototipe: tersimpan di browser ini</div>
                </div>
                <div class="history-toolbar">
                    <div class="report-control-wrap">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input id="historySearch" class="report-input" type="search" placeholder="Cari nomor atau jenis laporan...">
                    </div>
                </div>
                <div id="historyList"></div>
                <div class="form-preview" id="historyPrintArea"></div>
            </section>
            <section class="report-import-workspace hidden" id="reportImport">
                <div id="documentImportModule"></div>
            </section>
            <section class="form-workspace" id="formWorkspace"></section>
            <div class="report-toast" id="reportToast" role="status" aria-live="polite"><i class="fa-solid fa-circle-check"></i><span></span></div>
        `;

        module.querySelectorAll('[data-report-panel]').forEach(button => {
            button.addEventListener('click', () => switchReportPanel(button.dataset.reportPanel));
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') closeDetailedGuide();
        });
        document.getElementById('historySearch').addEventListener('input', renderHistory);

        document.getElementById('reportApply').addEventListener('click', () => {
            currentPage = 1;
            renderCatalog();
        });
        document.getElementById('reportSearch').addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                currentPage = 1;
                renderCatalog();
            }
        });
        document.getElementById('reportCategory').addEventListener('change', () => {
            currentPage = 1;
            renderCatalog();
        });
        document.getElementById('reportSort').addEventListener('change', () => {
            currentPage = 1;
            renderCatalog();
        });
        renderCatalog();
        renderHistory();

        const queryRoute = new URLSearchParams(window.location.search).get('view');
        const route = window.location.hash.match(/^#reports(?:\/([a-z0-9-]+))?$/i)
            || (queryRoute ? queryRoute.match(/^reports(?:\/([a-z0-9-]+))?$/i) : null);
        if (route) {
            document.querySelectorAll('.view-section').forEach(section => section.classList.remove('active'));
            document.getElementById('view-reports').classList.add('active');
            document.querySelectorAll('.sidebar-menu a').forEach(link => link.classList.remove('active'));
            document.getElementById('menu-reports').classList.add('active');
            if (route[1] === 'import') {
                switchReportPanel('import');
            } else if (route[1] === 'history') {
                switchReportPanel('history');
            } else if (route[1] && formSchemas.some(item => item.id === route[1])) {
                openForm(route[1]);
            }
        }
    }

    function formatDateTime(value) {
        if (!value) return '—';
        return new Intl.DateTimeFormat('id-ID', {
            dateStyle: 'long',
            timeStyle: 'short'
        }).format(new Date(value));
    }

    function getReportNumberKey(schema, fields = {}) {
        const preferredKeys = ['nomor', 'nomor_ppb', 'nomor_spb', 'nomor_log', 'nomor_bukti', 'kode'];
        return preferredKeys.find(key => (
            Object.prototype.hasOwnProperty.call(fields, key)
            || schema.fields.some(item => item.key === key)
        )) || schema.fields.find(item => /nomor|kode/i.test(item.key))?.key;
    }

    function getReportNumber(schema, fields) {
        const key = getReportNumberKey(schema, fields);
        return key && fields[key] ? fields[key] : `${schema.code} / TANPA NOMOR`;
    }

    function isRowPopulated(row) {
        return Object.entries(row).some(([key, value]) => {
            if (key.startsWith('_') || calculatedRowKeys.has(key)) return false;
            return String(value == null ? '' : value).trim();
        });
    }

    function switchReportPanel(panel) {
        const showHistory = panel === 'history';
        const showImport = panel === 'import';
        const showTemplates = !showHistory && !showImport;
        document.body.classList.remove('guide-modal-open');
        document.getElementById('reportCatalog').classList.toggle('hidden', !showTemplates);
        document.getElementById('reportHistory').classList.toggle('hidden', !showHistory);
        document.getElementById('reportImport').classList.toggle('hidden', !showImport);
        document.getElementById('formWorkspace').classList.remove('active');
        document.getElementById('formWorkspace').innerHTML = '';
        document.querySelectorAll('[data-report-panel]').forEach(button => {
            const selected = button.dataset.reportPanel === panel;
            button.classList.toggle('active', selected);
            button.setAttribute('aria-selected', String(selected));
        });
        const panelRoute = showImport ? '#reports/import' : showHistory ? '#reports/history' : '#reports';
        if (window.location.hash !== panelRoute) {
            window.history.replaceState(null, '', panelRoute);
        }
        if (showHistory) renderHistory();
        if (showImport) {
            document.dispatchEvent(new CustomEvent('fleetreport:import-visible'));
        }
        activeSchema = null;
        activeDraft = null;
    }

    function closeDetailedGuide() {
        document.getElementById('formGuideModal')?.classList.remove('active');
        document.body.classList.remove('guide-modal-open');
    }

    function renderHistory() {
        const list = document.getElementById('historyList');
        if (!list) return;
        const records = readHistory();
        const count = document.getElementById('reportHistoryCount');
        if (count) count.textContent = records.length;
        const query = (document.getElementById('historySearch')?.value || '').trim().toLocaleLowerCase('id');
        const filtered = records.filter(record => (
            `${record.reportNumber} ${record.title} ${record.code}`.toLocaleLowerCase('id').includes(query)
        ));

        list.innerHTML = filtered.length ? `
            <div class="history-table-wrap">
                <table class="history-table">
                    <thead><tr>
                        <th>Waktu dibuat</th>
                        <th>Nomor laporan</th>
                        <th>Jenis laporan</th>
                        <th>Item</th>
                        <th>Aksi</th>
                    </tr></thead>
                    <tbody>
                        ${filtered.map(record => `
                            <tr>
                                <td><strong>${escapeHtml(formatDateTime(record.createdAt))}</strong><small>${escapeHtml(record.id)}</small></td>
                                <td>${escapeHtml(record.reportNumber)}</td>
                                <td><span class="history-code">${escapeHtml(record.code)}</span>${escapeHtml(record.title)}</td>
                                <td>${record.draft.rows.filter(isRowPopulated).length}</td>
                                <td>
                                    <div class="history-actions">
                                        <button type="button" data-history-view="${escapeHtml(record.id)}"><i class="fa-regular fa-eye"></i> Lihat/Cetak</button>
                                        <button type="button" class="primary" data-history-clone="${escapeHtml(record.id)}"><i class="fa-regular fa-copy"></i> Gunakan Ulang</button>
                                        <button type="button" class="danger" data-history-delete="${escapeHtml(record.id)}"><i class="fa-regular fa-trash-can"></i> Hapus</button>
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        ` : `
            <div class="report-empty history-empty">
                <i class="fa-solid fa-box-archive"></i>
                <strong>${records.length ? 'Riwayat tidak ditemukan' : 'Belum ada laporan final'}</strong>
                <p>${records.length ? 'Ubah kata kunci pencarian.' : 'Isi form lalu gunakan tombol Simpan Laporan untuk membuat riwayat.'}</p>
            </div>
        `;

        list.querySelectorAll('[data-history-view]').forEach(button => {
            button.addEventListener('click', () => viewSavedReport(button.dataset.historyView));
        });
        list.querySelectorAll('[data-history-clone]').forEach(button => {
            button.addEventListener('click', () => duplicateSavedReport(button.dataset.historyClone));
        });
        list.querySelectorAll('[data-history-delete]').forEach(button => {
            button.addEventListener('click', () => deleteSavedReport(button.dataset.historyDelete));
        });
    }

    function viewSavedReport(recordId) {
        const record = readHistory().find(item => item.id === recordId);
        const schema = record && formSchemas.find(item => item.id === record.schemaId);
        if (!record || !schema) {
            showToast('Data laporan tidak ditemukan.', true);
            return;
        }
        const rows = record.draft.rows.filter(isRowPopulated);
        renderPreview(rows, {
            schema,
            draft: cloneData(record.draft),
            createdAt: record.createdAt,
            targetId: 'historyPrintArea',
            finalized: true
        });
    }

    function duplicateSavedReport(recordId) {
        const record = readHistory().find(item => item.id === recordId);
        const schema = record && formSchemas.find(item => item.id === record.schemaId);
        if (!record || !schema) {
            showToast('Data laporan tidak ditemukan.', true);
            return;
        }
        const draft = cloneData(record.draft);
        const reportNumberKey = getReportNumberKey(schema, draft.fields);
        if (reportNumberKey) draft.fields[reportNumberKey] = schema.seedFields?.[reportNumberKey] || '';
        delete draft.finalizedAt;
        draft.updatedAt = new Date().toISOString();
        localStorage.setItem(storagePrefix + schema.id, JSON.stringify(draft));
        switchReportPanel('templates');
        openForm(schema.id);
        showToast('Laporan diduplikat. Perbarui nomor dan detail yang berubah.');
    }

    function deleteSavedReport(recordId) {
        const records = readHistory();
        const record = records.find(item => item.id === recordId);
        if (!record) {
            showToast('Data laporan tidak ditemukan.', true);
            return;
        }
        const confirmed = window.confirm(
            `Hapus laporan “${record.reportNumber}”?\n\nData final dan lampirannya akan dihapus dari riwayat browser ini dan tidak dapat dipulihkan.`
        );
        if (!confirmed) return;
        try {
            writeHistory(records.filter(item => item.id !== recordId));
            const preview = document.getElementById('historyPrintArea');
            if (preview) {
                preview.classList.remove('active');
                preview.innerHTML = '';
            }
            renderHistory();
            showToast('Laporan berhasil dihapus dari riwayat.');
        } catch (error) {
            showToast('Laporan gagal dihapus. Silakan coba kembali.', true);
        }
    }

    function filteredForms() {
        const query = (document.getElementById('reportSearch').value || '').trim().toLocaleLowerCase('id');
        const category = document.getElementById('reportCategory').value;
        const sort = document.getElementById('reportSort').value;
        const result = formSchemas.filter(item => {
            const haystack = `${item.title} ${item.code} ${item.description} ${item.category}`.toLocaleLowerCase('id');
            return (!query || haystack.includes(query)) && (!category || item.category === category);
        });

        result.sort((a, b) => {
            if (sort === 'title-desc') return b.title.localeCompare(a.title, 'id');
            if (sort === 'category') return a.category.localeCompare(b.category, 'id') || a.title.localeCompare(b.title, 'id');
            return a.title.localeCompare(b.title, 'id');
        });
        return result;
    }

    function renderCatalog() {
        const items = filteredForms();
        const totalPages = Math.max(1, Math.ceil(items.length / pageSize));
        if (currentPage > totalPages) currentPage = totalPages;
        const start = (currentPage - 1) * pageSize;
        const visibleItems = items.slice(start, start + pageSize);
        const grid = document.getElementById('reportGrid');

        document.getElementById('reportResultCount').innerHTML = items.length
            ? `Menampilkan <strong>${start + 1}–${Math.min(start + pageSize, items.length)}</strong> dari <strong>${items.length}</strong> form`
            : 'Tidak ada form yang sesuai filter';

        grid.innerHTML = visibleItems.length ? visibleItems.map(item => `
            <article class="report-card">
                <div class="report-file-icon" data-code="${escapeHtml(item.code)}">
                    <i class="fa-regular fa-file-lines"></i>
                </div>
                <div class="report-card-copy">
                    <div class="report-card-title">${escapeHtml(item.title)}</div>
                    <div class="report-card-meta">
                        <span><i class="fa-solid fa-folder-open"></i>${escapeHtml(item.category)}</span>
                        <span><i class="fa-solid fa-list-check"></i>${item.fields.length + item.columns.length} field</span>
                        <span><i class="fa-solid fa-floppy-disk"></i>Auto-save</span>
                    </div>
                </div>
                <button class="report-open-btn" data-open-form="${escapeHtml(item.id)}">Isi Form</button>
            </article>
        `).join('') : `
            <div class="report-empty">
                <i class="fa-regular fa-folder-open"></i>
                <strong>Form tidak ditemukan</strong>
                <p>Coba gunakan kata kunci atau kategori yang berbeda.</p>
            </div>
        `;

        grid.querySelectorAll('[data-open-form]').forEach(button => {
            button.addEventListener('click', () => openForm(button.dataset.openForm));
        });

        document.getElementById('reportPagination').innerHTML = items.length > pageSize
            ? Array.from({ length: totalPages }, (_, index) => `
                <button class="report-page-btn ${currentPage === index + 1 ? 'active' : ''}" data-page="${index + 1}">${index + 1}</button>
            `).join('')
            : '';
        document.querySelectorAll('[data-page]').forEach(button => {
            button.addEventListener('click', () => {
                currentPage = Number(button.dataset.page);
                renderCatalog();
                document.getElementById('reportModule').scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    }

    function loadDraft(schema) {
        let stored = null;
        try {
            stored = JSON.parse(localStorage.getItem(storagePrefix + schema.id));
        } catch (error) {
            stored = null;
        }
        return stored && stored.fields && Array.isArray(stored.rows)
            ? { ...stored, fields: { ...(schema.seedFields || {}), ...stored.fields } }
            : createEmptyDraft(schema);
    }

    function saveDraft(showMessage = false) {
        if (!activeSchema || !activeDraft) return false;
        activeDraft.updatedAt = new Date().toISOString();
        try {
            localStorage.setItem(storagePrefix + activeSchema.id, JSON.stringify(activeDraft));
            const badge = document.getElementById('autosaveBadge');
            if (badge) badge.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Tersimpan lokal';
            if (showMessage) showToast('Draft berhasil disimpan di perangkat ini.');
            return true;
        } catch (error) {
            if (showMessage) showToast('Penyimpanan lokal tidak tersedia pada browser ini.', true);
            return false;
        }
    }

    function getNumberTemplate(schema, item) {
        if (!schema || !item || item.type !== 'text') return '';
        const candidate = schema.seedFields?.[item.key] || item.placeholder || '';
        return candidate.includes('_') ? candidate : '';
    }

    function tokenizeNumberTemplate(template) {
        return template.split(/(_+)/).filter(Boolean).map(value => ({
            value,
            editable: /^_+$/.test(value)
        }));
    }

    function escapeRegExp(value) {
        return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function extractTemplateNumberValues(template, currentValue) {
        const tokens = tokenizeNumberTemplate(template);
        if (!currentValue || currentValue === template) {
            return tokens.filter(token => token.editable).map(() => '');
        }
        const pattern = tokens.map(token => token.editable ? '(.*?)' : escapeRegExp(token.value)).join('');
        const match = new RegExp(`^${pattern}$`).exec(currentValue);
        return match ? match.slice(1) : tokens.filter(token => token.editable).map(() => '');
    }

    function templateNumberControl(item, value, template) {
        const tokens = tokenizeNumberTemplate(template);
        const values = extractTemplateNumberValues(template, value);
        let valueIndex = 0;
        return `
            <div class="template-number-control" data-template-number="${escapeHtml(item.key)}" data-template-pattern="${escapeHtml(template)}">
                ${tokens.map(token => {
                    if (!token.editable) return `<span class="template-number-fixed">${escapeHtml(token.value)}</span>`;
                    const currentIndex = valueIndex++;
                    const width = Math.max(3, Math.min(10, token.value.length + 1));
                    return `<input
                        class="template-number-part"
                        type="text"
                        inputmode="text"
                        autocomplete="off"
                        data-number-field="${escapeHtml(item.key)}"
                        data-number-index="${currentIndex}"
                        value="${escapeHtml(values[currentIndex] || '')}"
                        placeholder="isi"
                        maxlength="12"
                        size="${width}"
                        aria-label="${escapeHtml(`${item.label} bagian ${currentIndex + 1}`)}"
                        ${item.required ? 'required' : ''}
                    >`;
                }).join('')}
            </div>
            <small class="template-number-hint"><i class="fa-solid fa-lock"></i> Bagian format baku dikunci; isi hanya kotak yang tersedia.</small>
        `;
    }

    function composeTemplateNumber(fieldKey) {
        const container = document.querySelector(`[data-template-number="${fieldKey}"]`);
        if (!container) return;
        const template = container.dataset.templatePattern;
        const values = [...container.querySelectorAll('[data-number-index]')].map(input => input.value.trim());
        let valueIndex = 0;
        activeDraft.fields[fieldKey] = tokenizeNumberTemplate(template).map(token => (
            token.editable ? (values[valueIndex++] || token.value) : token.value
        )).join('');
    }

    function fieldInstruction(item) {
        const label = item.label.toLowerCase();
        const key = item.key.toLowerCase();
        if (getNumberTemplate(activeSchema, item)) {
            return 'Isi setiap kotak dinamis sesuai nomor register resmi. Bagian teks, kode baku, garis miring, dan awalan tahun tidak perlu diketik ulang.';
        }
        if (item.type === 'date') return `Pilih tanggal ${label.replace(/^tanggal\s*/, '')} sesuai dokumen atau kejadian sebenarnya.`;
        if (item.type === 'month') return `Pilih bulan dan tahun untuk ${label}.`;
        if (item.type === 'time') return `Isi waktu ${label} menggunakan format 24 jam.`;
        if (item.type === 'select') return `Pilih satu opsi ${label} yang paling sesuai dari daftar yang tersedia.`;
        if (item.type === 'number') {
            if (/harga|biaya|estimasi|nilai/.test(key)) return `Isi ${label} dalam Rupiah berupa angka tanpa simbol mata uang atau pemisah ribuan.`;
            if (/hm|hour|meter/.test(key + label)) return `Isi pembacaan ${label} sesuai panel/unit; desimal diperbolehkan.`;
            return `Isi ${label} berupa angka nol atau lebih; gunakan desimal hanya bila diperlukan.`;
        }
        if (/alamat|tempat_penyerahan/.test(key)) return `Tuliskan ${label} lengkap agar lokasi dapat dikenali tanpa penjelasan tambahan.`;
        if (/analisa|temuan|dampak|solusi|tindak|riwayat|uraian|catatan|lampiran/.test(key)) {
            return `Jelaskan ${label} secara ringkas tetapi lengkap, termasuk fakta, kondisi, dan tindak lanjut yang relevan.`;
        }
        if (/project|lokasi|site|yard/.test(key)) return `Isi ${label} dengan nama resmi project/site/lokasi operasional.`;
        if (/pengirim|penerima|dari|kepada|operator|mekanik|diajukan|disetujui|dibuat|diperiksa/.test(key)) {
            return `Isi ${label} dengan nama pihak/personel yang dapat diidentifikasi pada dokumen.`;
        }
        if (/serial|part_number|kode|identifikasi|nomor_polisi|nrp/.test(key)) {
            return `Salin ${label} persis seperti label unit, komponen, kartu identitas, atau dokumen sumber.`;
        }
        if (item.placeholder) return `Isi ${label}. Acuan format/contoh: ${item.placeholder}.`;
        return `Isi ${label} sesuai data sumber yang benar dan dapat diverifikasi.`;
    }

    function fieldPlaceholder(item) {
        if (item.placeholder) return item.placeholder;
        if (item.type === 'number') return `Masukkan angka ${item.label.toLowerCase()}`;
        if (item.type === 'textarea') return `Tuliskan ${item.label.toLowerCase()} secara jelas...`;
        return `Isi ${item.label.toLowerCase()}`;
    }

    function columnInstruction(item) {
        const label = item.label.toLowerCase();
        if (item.readonly) return `${item.label} dihitung otomatis oleh sistem dari data pada kolom terkait.`;
        if (item.type === 'select') return `Pilih ${label} yang sesuai untuk item pada baris ini.`;
        if (item.type === 'date') return `Pilih tanggal ${label} untuk item pada baris ini.`;
        if (item.type === 'time') return `Isi ${label} dengan format 24 jam untuk item pada baris ini.`;
        if (item.type === 'number') return `Isi ${label} berupa angka nol atau lebih. Jangan gunakan teks atau simbol satuan.`;
        if (/keterangan|catatan|analisa|solusi|tindakan/.test(item.key)) return `Tuliskan ${label} untuk menjelaskan kondisi atau tindak lanjut item pada baris ini.`;
        return `Isi ${label} sesuai identitas atau data sumber item pada baris ini.`;
    }

    function columnPlaceholder(item) {
        if (item.readonly) return 'Otomatis';
        if (item.type === 'number') return '0';
        if (item.type === 'date') return 'Pilih tanggal';
        if (item.type === 'time') return '00:00';
        return `Isi ${item.label}`;
    }

    function formControl(item, value) {
        const required = item.required ? 'required' : '';
        const placeholder = `placeholder="${escapeHtml(fieldPlaceholder(item))}"`;
        const nonNegative = item.type === 'number' ? 'min="0" step="any"' : '';
        const instruction = `title="${escapeHtml(fieldInstruction(item))}" aria-label="${escapeHtml(`${item.label}. ${fieldInstruction(item)}`)}"`;
        const numberTemplate = getNumberTemplate(activeSchema, item);
        if (numberTemplate) return templateNumberControl(item, value, numberTemplate);
        if (item.type === 'textarea') {
            return `<textarea class="builder-input" data-field="${escapeHtml(item.key)}" ${required} ${placeholder} ${instruction}>${escapeHtml(value)}</textarea>`;
        }
        if (item.type === 'select') {
            return `<select class="builder-input" data-field="${escapeHtml(item.key)}" ${required} ${instruction}>
                <option value="">Pilih ${escapeHtml(item.label.toLowerCase())}...</option>
                ${item.options.map(option => `<option value="${escapeHtml(option)}" ${value === option ? 'selected' : ''}>${escapeHtml(option)}</option>`).join('')}
            </select>`;
        }
        return `<input class="builder-input" data-field="${escapeHtml(item.key)}" type="${escapeHtml(item.type)}" value="${escapeHtml(value)}" ${required} ${placeholder} ${nonNegative} ${instruction}>`;
    }

    function tableControl(item, value, rowIndex) {
        const common = `class="table-cell-input" data-row="${rowIndex}" data-key="${escapeHtml(item.key)}" title="${escapeHtml(columnInstruction(item))}" aria-label="${escapeHtml(`${item.label} baris ${rowIndex + 1}. ${columnInstruction(item)}`)}"`;
        const required = item.required ? 'required' : '';
        if (item.type === 'select') {
            return `<select ${common} ${required}>
                <option value="">Pilih ${escapeHtml(item.label.toLowerCase())}...</option>
                ${item.options.map(option => `<option value="${escapeHtml(option)}" ${value === option ? 'selected' : ''}>${escapeHtml(option)}</option>`).join('')}
            </select>`;
        }
        const nonNegative = item.type === 'number' && !item.readonly ? 'min="0" step="any"' : '';
        return `<input ${common} type="${escapeHtml(item.type)}" value="${escapeHtml(value)}" placeholder="${escapeHtml(columnPlaceholder(item))}" ${required} ${nonNegative} ${item.readonly ? 'readonly tabindex="-1"' : ''}>`;
    }

    function evidenceControl(row, rowIndex) {
        const evidence = row._evidence;
        return `
            <div class="evidence-control ${evidence?.dataUrl ? 'has-file' : ''}">
                ${evidence?.dataUrl ? `
                    <img src="${escapeHtml(evidence.dataUrl)}" alt="Bukti ${rowIndex + 1}">
                    <span title="${escapeHtml(evidence.name)}">${escapeHtml(evidence.name)}</span>
                    <button type="button" data-remove-evidence="${rowIndex}" title="Hapus gambar"><i class="fa-solid fa-xmark"></i></button>
                    <input class="evidence-caption-input" type="text" data-evidence-caption-row="${rowIndex}" value="${escapeHtml(evidence.caption || '')}" placeholder="Keterangan/konteks foto *">
                ` : `
                    <label>
                        <i class="fa-solid fa-camera"></i>
                        <span>Unggah gambar <b>*</b></span>
                        <input type="file" accept="image/jpeg,image/png,image/webp" data-evidence-row="${rowIndex}">
                    </label>
                `}
            </div>
        `;
    }

    function readImage(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => {
                const image = new Image();
                image.onload = () => {
                    const longSide = Math.max(image.naturalWidth, image.naturalHeight);
                    const shortSide = Math.min(image.naturalWidth, image.naturalHeight);
                    if (longSide < minImageLongSide || shortSide < minImageShortSide) {
                        reject({ code: 'LOW_RESOLUTION', width: image.naturalWidth, height: image.naturalHeight });
                        return;
                    }
                    const scale = Math.min(1, 1920 / longSide);
                    const width = Math.round(image.naturalWidth * scale);
                    const height = Math.round(image.naturalHeight * scale);
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const context = canvas.getContext('2d');
                    context.fillStyle = '#ffffff';
                    context.fillRect(0, 0, width, height);
                    context.drawImage(image, 0, 0, width, height);
                    resolve({
                        name: file.name,
                        type: 'image/jpeg',
                        originalType: file.type,
                        size: file.size,
                        width,
                        height,
                        dataUrl: canvas.toDataURL('image/jpeg', 0.9)
                    });
                };
                image.onerror = () => reject({ code: 'INVALID_IMAGE' });
                image.src = reader.result;
            };
            reader.onerror = () => reject(reader.error);
            reader.readAsDataURL(file);
        });
    }

    async function handleEvidenceUpload(input) {
        const file = input.files?.[0];
        if (!file) return;
        if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
            input.value = '';
            showToast('Format gambar harus JPG, PNG, atau WebP.', true);
            return;
        }
        if (file.size > maxSourceImageBytes) {
            input.value = '';
            showToast('Ukuran file sumber maksimal 8 MB per item.', true);
            return;
        }
        try {
            const rowIndex = Number(input.dataset.evidenceRow);
            activeDraft.rows[rowIndex]._evidence = await readImage(file);
            if (!saveDraft()) {
                delete activeDraft.rows[rowIndex]._evidence;
                showToast('Gambar tidak dapat disimpan karena kapasitas browser penuh.', true);
                renderRows();
                return;
            }
            renderRows();
            showToast('Gambar bukti berhasil dilampirkan.');
        } catch (error) {
            if (error?.code === 'LOW_RESOLUTION') {
                showToast(`Resolusi ${error.width}×${error.height}px terlalu kecil. Gunakan minimal HD 1280×720px.`, true);
            } else {
                showToast('Gambar gagal dibaca. Silakan pilih ulang.', true);
            }
        }
    }

    function renderFields() {
        return activeSchema.fields.map(item => `
            <div class="builder-field ${item.full ? 'full' : ''}">
                <label>
                    ${escapeHtml(item.label)}<span class="required-mark">*</span>
                </label>
                ${formControl(item, activeDraft.fields[item.key] || '')}
            </div>
        `).join('');
    }

    function tableHeaderMarkup(item) {
        return `
            <div class="table-header-content">
                <span>${escapeHtml(item.label)}${item.readonly ? '<em>Otomatis</em>' : '<b class="required-mark">*</b>'}</span>
            </div>
        `;
    }

    function renderDetailedGuide() {
        return `
            <div class="guide-modal" id="formGuideModal" role="dialog" aria-modal="true" aria-labelledby="formGuideTitle">
                <button type="button" class="guide-modal-backdrop" data-close-guide aria-label="Tutup panduan"></button>
                <section class="guide-modal-panel">
                    <header>
                        <div>
                            <span>PANDUAN LENGKAP</span>
                            <h2 id="formGuideTitle">${escapeHtml(activeSchema.title)}</h2>
                            <p>Gunakan panduan ini untuk memahami isi setiap field dan kolom tabel.</p>
                        </div>
                        <button type="button" class="guide-modal-close" data-close-guide><i class="fa-solid fa-xmark"></i></button>
                    </header>
                    <div class="guide-modal-body">
                        <section class="guide-quick-start">
                            <h3>Urutan pengerjaan yang disarankan</h3>
                            <div class="guide-steps">
                                <div><span>1</span><strong>Identitas</strong><small>Isi data dokumen dan pihak terkait.</small></div>
                                <div><span>2</span><strong>Detail tabel</strong><small>Masukkan satu item pada setiap baris.</small></div>
                                <div><span>3</span><strong>Periksa</strong><small>Simpan draft dan tinjau melalui Preview.</small></div>
                                <div><span>4</span><strong>Finalisasi</strong><small>Simpan laporan setelah seluruh data benar.</small></div>
                            </div>
                        </section>
                        <section class="guide-reference">
                            <div class="guide-reference-heading">
                                <div><h3>Identitas & informasi dokumen</h3><p>Penjelasan mengenai data pada bagian atas form.</p></div>
                                <div class="guide-legend"><span class="required">Semua wajib diisi</span></div>
                            </div>
                            <div class="guide-reference-table">
                                <div class="guide-reference-row head"><span>Field</span><span>Status</span><span>Cara mengisi</span></div>
                                ${activeSchema.fields.map(item => `
                                    <div class="guide-reference-row">
                                        <strong>${escapeHtml(item.label)}</strong>
                                        <span><em class="required">Wajib</em></span>
                                        <p>${escapeHtml(fieldInstruction(item))}</p>
                                    </div>
                                `).join('')}
                            </div>
                        </section>
                        <section class="guide-reference">
                            <div class="guide-reference-heading">
                                <div><h3>Tabel ${escapeHtml(activeSchema.tableTitle)}</h3><p>Satu baris digunakan untuk satu item atau satu kejadian.</p></div>
                                <div class="guide-legend"><span class="required">Wajib</span><span class="automatic">Otomatis</span></div>
                            </div>
                            <div class="guide-reference-table">
                                <div class="guide-reference-row head"><span>Kolom</span><span>Status</span><span>Cara mengisi</span></div>
                                ${activeSchema.columns.map(item => `
                                    <div class="guide-reference-row">
                                        <strong>${escapeHtml(item.label)}</strong>
                                        <span><em class="${item.readonly ? 'automatic' : 'required'}">${item.readonly ? 'Otomatis' : 'Wajib'}</em></span>
                                        <p>${escapeHtml(columnInstruction(item))}</p>
                                    </div>
                                `).join('')}
                                ${requiresEvidence() ? `
                                    <div class="guide-reference-row">
                                        <strong>Bukti gambar & keterangan</strong>
                                        <span><em class="required">Wajib</em></span>
                                        <p>Unggah satu foto minimal HD 1280×720px untuk setiap item, lalu tuliskan konteks foto. Foto akan menjadi halaman lampiran tersendiri.</p>
                                    </div>
                                ` : ''}
                            </div>
                        </section>
                    </div>
                    <footer class="guide-modal-footer">
                        <span><i class="fa-solid fa-floppy-disk"></i> Gunakan Simpan Draft selama data belum final.</span>
                        <button type="button" data-close-guide>Saya mengerti, kembali ke form</button>
                    </footer>
                </section>
            </div>
        `;
    }

    function renderFormGuidance() {
        const requiredLabels = activeSchema.fields.filter(item => item.required).map(item => item.label);
        const hasTemplateNumber = activeSchema.fields.some(item => getNumberTemplate(activeSchema, item));
        const steps = [
            `Lengkapi identitas wajib: ${requiredLabels.slice(0, 6).join(', ')}${requiredLabels.length > 6 ? ', dan field wajib lainnya' : ''}.`,
            `Isi tabel “${activeSchema.tableTitle}” per item/baris. Pastikan identitas, jumlah, kondisi, atau hasil pemeriksaan saling konsisten.`
        ];
        if (hasTemplateNumber) {
            steps.unshift('Pada nomor dokumen, bagian teks baku sudah dikunci. Isi hanya kotak nomor urut, kode/periode, bulan, atau tahun yang tersedia.');
        }
        if (requiresEvidence()) {
            steps.push('Setiap baris item yang terisi wajib memiliki gambar bukti minimal HD 1280×720px dan keterangan foto. Gunakan foto fokus, cukup cahaya, dan menampilkan objek terkait.');
        }
        if (activeSchema.calculationNote) {
            steps.push(activeSchema.calculationNote);
        }
        steps.push('Gunakan Simpan Draft untuk pekerjaan sementara, Preview untuk pengecekan, lalu Simpan Laporan setelah seluruh data benar. Form akan kembali kosong setelah finalisasi berhasil.');

        return `
            <aside class="form-guidance">
                <div class="form-guidance-heading">
                    <i class="fa-solid fa-circle-info"></i>
                    <div><strong>Panduan pengisian ${escapeHtml(activeSchema.code)}</strong><span>Baca sebelum menyimpan laporan final.</span></div>
                    <button type="button" id="openDetailedGuide"><i class="fa-solid fa-book-open"></i> Baca panduan lengkap</button>
                </div>
                <ol>${steps.map(step => `<li>${escapeHtml(step)}</li>`).join('')}</ol>
            </aside>
        `;
    }

    function renderRows() {
        const tbody = document.getElementById('formRows');
        if (!tbody) return;
        tbody.innerHTML = activeDraft.rows.map((row, rowIndex) => `
            <tr>
                <td style="width:42px;text-align:center;color:#7b8798">${rowIndex + 1}</td>
                ${activeSchema.columns.map(item => `<td>${tableControl(item, row[item.key] == null ? '' : row[item.key], rowIndex)}</td>`).join('')}
                ${requiresEvidence() ? `<td class="evidence-cell">${evidenceControl(row, rowIndex)}</td>` : ''}
                <td style="width:42px"><button class="row-remove-btn" data-remove-row="${rowIndex}" title="Hapus baris"><i class="fa-solid fa-trash-can"></i></button></td>
            </tr>
        `).join('');
        tbody.querySelectorAll('[data-evidence-row]').forEach(input => {
            input.addEventListener('change', () => handleEvidenceUpload(input));
        });
        tbody.querySelectorAll('[data-remove-evidence]').forEach(button => {
            button.addEventListener('click', () => {
                delete activeDraft.rows[Number(button.dataset.removeEvidence)]._evidence;
                saveDraft();
                renderRows();
            });
        });
        tbody.querySelectorAll('[data-remove-row]').forEach(button => {
            button.addEventListener('click', () => {
                if (activeDraft.rows.length === 1) {
                    activeDraft.rows[0] = {};
                } else {
                    activeDraft.rows.splice(Number(button.dataset.removeRow), 1);
                }
                calculateAllRows();
                renderRows();
                renderSummary();
                saveDraft();
            });
        });
        renderSummary();
    }

    function calculateRow(row) {
        switch (activeSchema.calculation) {
            case 'warehouseIn':
                row.saldo_sekarang = numberValue(row.saldo_lalu) + numberValue(row.jumlah);
                break;
            case 'warehouseOut':
                row.sisa = numberValue(row.persediaan) - numberValue(row.diberikan);
                break;
            case 'lho':
                row.jam_kerja = timeDifference(row.jam_awal, row.jam_akhir);
                row.hm_operasi = Math.max(0, Math.round((numberValue(row.hm_akhir) - numberValue(row.hm_awal)) * 100) / 100);
                break;
            case 'ppb':
                row.total = numberValue(row.jumlah) * numberValue(row.harga);
                break;
            case 'spl':
                row.durasi = timeDifference(row.mulai, row.selesai);
                break;
            case 'weeklyParts':
                row.in_total = numberValue(row.in_lalu) + numberValue(row.in_ini);
                row.out_total = numberValue(row.out_lalu) + numberValue(row.out_ini);
                row.saldo = row.in_total - row.out_total;
                row.nilai_saldo = row.saldo * numberValue(row.harga);
                break;
        }
    }

    function calculateAllRows() {
        activeDraft.rows.forEach(calculateRow);
    }

    function summaryMarkup(schema = activeSchema, draft = activeDraft) {
        if (!schema?.calculation || !draft) return '';
        if (schema.calculation === 'ppb') {
            const subtotal = draft.rows.reduce((sum, row) => sum + numberValue(row.total), 0);
            const ppn = subtotal * 0.11;
            return `
                <div class="summary-line"><span>Subtotal</span><strong>${formatRupiah(subtotal)}</strong></div>
                <div class="summary-line"><span>PPN 11%</span><strong>${formatRupiah(ppn)}</strong></div>
                <div class="summary-line total"><span>Total</span><strong>${formatRupiah(subtotal + ppn)}</strong></div>
            `;
        }
        if (schema.calculation === 'spl') {
            const total = draft.rows.reduce((sum, row) => sum + numberValue(row.durasi), 0);
            const people = draft.rows.filter(row => row.nama).length;
            return `
                <div class="summary-line"><span>Jumlah personel</span><strong>${people} orang</strong></div>
                <div class="summary-line total"><span>Total jam-orang</span><strong>${total.toLocaleString('id-ID')} jam</strong></div>
            `;
        }
        if (schema.calculation === 'lho') {
            const hours = draft.rows.reduce((sum, row) => sum + numberValue(row.jam_kerja), 0);
            const hm = draft.rows.reduce((sum, row) => sum + numberValue(row.hm_operasi), 0);
            const fuel = draft.rows.reduce((sum, row) => sum + numberValue(row.bbm), 0);
            return `
                <div class="summary-line"><span>Total jam kerja</span><strong>${hours.toLocaleString('id-ID')} jam</strong></div>
                <div class="summary-line"><span>Total HM operasi</span><strong>${hm.toLocaleString('id-ID')} HM</strong></div>
                <div class="summary-line total"><span>Total BBM</span><strong>${fuel.toLocaleString('id-ID')} liter</strong></div>
            `;
        }
        if (schema.calculation === 'weeklyParts') {
            const stockValue = draft.rows.reduce((sum, row) => sum + numberValue(row.nilai_saldo), 0);
            return `<div class="summary-line total"><span>Total nilai saldo</span><strong>${formatRupiah(stockValue)}</strong></div>`;
        }
        if (schema.calculation === 'warehouseIn') {
            const received = draft.rows.reduce((sum, row) => sum + numberValue(row.jumlah), 0);
            return `<div class="summary-line total"><span>Total barang masuk</span><strong>${received.toLocaleString('id-ID')} unit</strong></div>`;
        }
        if (schema.calculation === 'warehouseOut') {
            const issued = draft.rows.reduce((sum, row) => sum + numberValue(row.diberikan), 0);
            return `<div class="summary-line total"><span>Total barang keluar</span><strong>${issued.toLocaleString('id-ID')} unit</strong></div>`;
        }
        if (schema.calculation === 'sppu006') {
            const populatedRows = draft.rows.filter(row => row.nama || row.pn || numberValue(row.jumlah));
            const totalQuantity = populatedRows.reduce((sum, row) => sum + numberValue(row.jumlah), 0);
            const largest = populatedRows.reduce((selected, row) => (
                !selected || numberValue(row.jumlah) > numberValue(selected.jumlah) ? row : selected
            ), null);
            const largestLabel = largest
                ? `${largest.nama || largest.pn || 'Parts tanpa nama'} — ${numberValue(largest.jumlah).toLocaleString('id-ID')} ${largest.satuan || 'unit'}`
                : 'Belum ada data';
            return `
                <div class="summary-line"><span>Jumlah jenis parts</span><strong>${populatedRows.length}</strong></div>
                <div class="summary-line"><span>Total kuantitas</span><strong>${totalQuantity.toLocaleString('id-ID')}</strong></div>
                <div class="summary-line total"><span>Kuantitas terbesar</span><strong>${escapeHtml(largestLabel)}</strong></div>
            `;
        }
        return '';
    }

    function renderSummary() {
        const container = document.getElementById('calculationSummary');
        if (!container) return;
        const markup = summaryMarkup();
        container.innerHTML = markup ? `<div class="summary-card">${markup}</div>` : '';
    }

    function openForm(formId) {
        activeSchema = formSchemas.find(item => item.id === formId);
        if (!activeSchema) return;
        if (window.location.hash !== `#reports/${formId}`) {
            window.history.replaceState(null, '', `#reports/${formId}`);
        }
        activeDraft = loadDraft(activeSchema);
        calculateAllRows();

        const workspace = document.getElementById('formWorkspace');
        workspace.innerHTML = `
            <div class="form-breadcrumb">
                <button class="form-back-btn" id="backToCatalog"><i class="fa-solid fa-arrow-left"></i> Semua form</button>
                <i class="fa-solid fa-chevron-right"></i>
                <span>${escapeHtml(activeSchema.code)}</span>
            </div>
            <div class="form-builder-shell">
                <div class="form-builder-header">
                    <div class="form-title-wrap">
                        <div class="form-title-icon"><i class="fa-regular fa-file-lines"></i></div>
                        <div>
                            <h2>${escapeHtml(activeSchema.title)}</h2>
                            <p>${escapeHtml(activeSchema.description)}<br>Acuan field: material/SOP/Form/${escapeHtml(activeSchema.source)}</p>
                        </div>
                    </div>
                    <div class="autosave-badge" id="autosaveBadge"><i class="fa-solid fa-cloud"></i> Auto-save aktif</div>
                </div>
                ${activeDraft.importSource ? `
                    <div class="imported-source-banner">
                        <i class="fa-solid fa-file-shield"></i>
                        <div>
                            <strong>Draft dibuat dari ${escapeHtml(activeDraft.importSource.fileName || 'dokumen impor')}</strong>
                            <span>SHA-256 ${escapeHtml(String(activeDraft.importSource.sha256 || '').slice(0, 16))}… ·
                            pemetaan ${Math.round(Number(activeDraft.importSource.mappingCoverage || 0) * 100)}% ·
                            ${Number(activeDraft.importSource.unmappedFragments || 0)} fragmen tetap tersimpan di arsip impor.
                            ${activeDraft.importSource.rowsLimited
                                ? ` Draft memakai ${Number(activeDraft.importSource.appliedRows || 0).toLocaleString('id-ID')} dari ${Number(activeDraft.importSource.totalMappedRows || 0).toLocaleString('id-ID')} baris terpetakan.`
                                : ''}
                            ${Number(activeDraft.importSource.typeIssueCount || 0)
                                ? ` ${Number(activeDraft.importSource.typeIssueCount).toLocaleString('id-ID')} nilai tidak diterapkan langsung karena tipe, opsi, atau kolomnya tidak cocok dengan schema dan perlu direview.`
                                : ''}</span>
                        </div>
                    </div>
                ` : ''}
                <form id="dynamicReportForm" novalidate>
                    <div class="form-builder-body">
                        <section class="builder-section">
                            <div class="builder-section-title">
                                <i class="fa-solid fa-id-card"></i><h3>Identitas & informasi dokumen</h3>
                                <span><span class="required-mark">*</span> wajib diisi</span>
                            </div>
                            <div class="builder-fields">${renderFields()}</div>
                        </section>
                        <section class="builder-section">
                            <div class="builder-section-title">
                                <i class="fa-solid fa-table-list"></i><h3>${escapeHtml(activeSchema.tableTitle)}</h3>
                                <span>Baris dapat ditambah sesuai kebutuhan</span>
                            </div>
                            <div class="form-table-wrap">
                                <table class="form-table">
                                    <thead><tr>
                                        <th>No.</th>
                                        ${activeSchema.columns.map(item => `<th>${tableHeaderMarkup(item)}</th>`).join('')}
                                        ${requiresEvidence() ? '<th>Bukti gambar <span class="required-mark">*</span></th>' : ''}
                                        <th>Aksi</th>
                                    </tr></thead>
                                    <tbody id="formRows"></tbody>
                                </table>
                            </div>
                            <button type="button" class="row-add-btn" id="addFormRow"><i class="fa-solid fa-plus"></i> Tambah baris</button>
                            <div class="calculation-summary" id="calculationSummary"></div>
                        </section>
                        ${renderFormGuidance()}
                    </div>
                    <div class="form-builder-footer">
                        <div class="form-footer-note"><i class="fa-solid fa-shield-halved"></i> Data prototipe tersimpan lokal di browser, belum dikirim ke server.</div>
                        <div class="form-footer-actions">
                            <button type="button" class="form-secondary-btn" id="saveReportDraft"><i class="fa-regular fa-floppy-disk"></i> Simpan Draft</button>
                            <button type="button" class="form-secondary-btn danger-soft" id="resetReportForm"><i class="fa-solid fa-rotate-left"></i> Reset</button>
                            <button type="submit" class="form-secondary-btn preview-btn"><i class="fa-regular fa-eye"></i> Preview</button>
                            <button type="button" class="form-primary-btn" id="finalizeReport"><i class="fa-solid fa-check"></i> Simpan Laporan</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="form-preview" id="reportPrintArea"></div>
            ${renderDetailedGuide()}
        `;

        document.getElementById('reportCatalog').classList.add('hidden');
        workspace.classList.add('active');
        document.getElementById('backToCatalog').addEventListener('click', closeForm);
        document.getElementById('saveReportDraft').addEventListener('click', () => saveDraft(true));
        document.getElementById('resetReportForm').addEventListener('click', resetActiveForm);
        document.getElementById('finalizeReport').addEventListener('click', finalizeReport);
        document.getElementById('openDetailedGuide').addEventListener('click', () => {
            document.getElementById('formGuideModal').classList.add('active');
            document.body.classList.add('guide-modal-open');
        });
        document.querySelectorAll('[data-close-guide]').forEach(button => {
            button.addEventListener('click', closeDetailedGuide);
        });
        document.getElementById('addFormRow').addEventListener('click', () => {
            activeDraft.rows.push({});
            renderRows();
            saveDraft();
        });
        document.getElementById('dynamicReportForm').addEventListener('input', handleFormInput);
        document.getElementById('dynamicReportForm').addEventListener('change', handleFormInput);
        document.getElementById('dynamicReportForm').addEventListener('submit', event => {
            event.preventDefault();
            validateAndPreview();
        });
        renderRows();
        document.getElementById('reportModule').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function handleFormInput(event) {
        const target = event.target;
        if (target.dataset.numberField) {
            target.value = target.value.replace(/[\\/]/g, '');
            composeTemplateNumber(target.dataset.numberField);
        }
        if (target.dataset.evidenceCaptionRow != null) {
            const rowIndex = Number(target.dataset.evidenceCaptionRow);
            if (activeDraft.rows[rowIndex]._evidence) {
                activeDraft.rows[rowIndex]._evidence.caption = target.value;
            }
        }
        if (target.dataset.field) {
            activeDraft.fields[target.dataset.field] = target.value;
        }
        if (target.dataset.row != null && target.dataset.key) {
            const rowIndex = Number(target.dataset.row);
            activeDraft.rows[rowIndex][target.dataset.key] = target.value;
            calculateRow(activeDraft.rows[rowIndex]);
            activeSchema.columns.filter(item => item.readonly).forEach(item => {
                const output = document.querySelector(`[data-row="${rowIndex}"][data-key="${item.key}"]`);
                if (output) output.value = activeDraft.rows[rowIndex][item.key] == null ? '' : activeDraft.rows[rowIndex][item.key];
            });
            renderSummary();
        }
        saveDraft();
    }

    function closeForm() {
        closeDetailedGuide();
        saveDraft();
        window.history.replaceState(null, '', '#reports');
        document.getElementById('formWorkspace').classList.remove('active');
        document.getElementById('formWorkspace').innerHTML = '';
        document.getElementById('reportCatalog').classList.remove('hidden');
        activeSchema = null;
        activeDraft = null;
        document.getElementById('reportModule').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function resetActiveForm() {
        if (!activeSchema) return;
        const schemaId = activeSchema.id;
        if (!window.confirm('Kosongkan seluruh isian dan hapus draft form ini?')) return;
        localStorage.removeItem(storagePrefix + schemaId);
        openForm(schemaId);
        showToast('Form dan draft berhasil direset.');
    }

    function validateDraft() {
        const form = document.getElementById('dynamicReportForm');
        const missingNumberPart = form?.querySelector('.template-number-part:invalid')
            || [...(form?.querySelectorAll('.template-number-part') || [])].find(input => !input.value.trim());
        if (missingNumberPart) {
            missingNumberPart.focus();
            showToast('Lengkapi seluruh bagian dinamis pada nomor dokumen.', true);
            return null;
        }
        const missing = activeSchema.fields.filter(item => (
            item.required && !String(activeDraft.fields[item.key] ?? '').trim()
        ));
        if (missing.length) {
            const input = document.querySelector(`[data-field="${missing[0].key}"]`);
            if (input) {
                input.focus();
                input.reportValidity();
            }
            showToast(`Lengkapi field wajib: ${missing[0].label}.`, true);
            return null;
        }
        const invalidField = activeSchema.fields
            .map(item => ({
                item,
                issue: draftValueTypeIssue(item, activeDraft.fields[item.key])
            }))
            .find(candidate => candidate.issue);
        if (invalidField) {
            const input = document.querySelector(`[data-field="${invalidField.item.key}"]`);
            input?.focus();
            input?.reportValidity?.();
            showToast(
                `Format ${invalidField.item.label} tidak sesuai tipe ${invalidField.item.type}. Periksa kembali nilai impor.`,
                true
            );
            return null;
        }
        const populatedRows = activeDraft.rows.filter(isRowPopulated);
        if (!populatedRows.length) {
            showToast('Isi minimal satu baris data sebelum membuat preview.', true);
            return null;
        }
        for (let rowIndex = 0; rowIndex < activeDraft.rows.length; rowIndex += 1) {
            const row = activeDraft.rows[rowIndex];
            if (!isRowPopulated(row)) continue;
            const missingColumn = activeSchema.columns.find(item => (
                item.required
                && !item.readonly
                && !String(row[item.key] == null ? '' : row[item.key]).trim()
            ));
            if (missingColumn) {
                const input = document.querySelector(`[data-row="${rowIndex}"][data-key="${missingColumn.key}"]`);
                input?.focus();
                showToast(`Lengkapi kolom ${missingColumn.label} pada baris ${rowIndex + 1}.`, true);
                return null;
            }
            const invalidColumn = activeSchema.columns
                .map(item => ({
                    item,
                    issue: draftValueTypeIssue(item, row[item.key])
                }))
                .find(candidate => candidate.issue);
            if (invalidColumn) {
                const input = document.querySelector(
                    `[data-row="${rowIndex}"][data-key="${invalidColumn.item.key}"]`
                );
                input?.focus();
                input?.reportValidity?.();
                showToast(
                    `Format kolom ${invalidColumn.item.label} pada baris ${rowIndex + 1} tidak sesuai tipe ${invalidColumn.item.type}.`,
                    true
                );
                return null;
            }
        }
        const invalidControl = form?.querySelector(':invalid');
        if (invalidControl) {
            invalidControl.focus();
            invalidControl.reportValidity?.();
            showToast('Periksa isian yang kosong atau menggunakan format yang tidak valid.', true);
            return null;
        }
        if (requiresEvidence()) {
            const missingEvidenceIndex = activeDraft.rows.findIndex(row => isRowPopulated(row) && !row._evidence?.dataUrl);
            if (missingEvidenceIndex >= 0) {
                const input = document.querySelector(`[data-evidence-row="${missingEvidenceIndex}"]`);
                input?.focus();
                showToast(`Unggah gambar bukti untuk item baris ${missingEvidenceIndex + 1}.`, true);
                return null;
            }
            const missingCaptionIndex = activeDraft.rows.findIndex(row => isRowPopulated(row) && !row._evidence?.caption?.trim());
            if (missingCaptionIndex >= 0) {
                const input = document.querySelector(`[data-evidence-caption-row="${missingCaptionIndex}"]`);
                input?.focus();
                showToast(`Isi keterangan/konteks gambar untuk item baris ${missingCaptionIndex + 1}.`, true);
                return null;
            }
        }
        return populatedRows;
    }

    function validateAndPreview() {
        const populatedRows = validateDraft();
        if (!populatedRows) return;
        saveDraft();
        renderPreview(populatedRows);
    }

    function finalizeReport() {
        const populatedRows = validateDraft();
        if (!populatedRows) return;
        const createdAt = new Date().toISOString();
        const record = {
            id: `RPT-${Date.now()}-${Math.random().toString(36).slice(2, 7).toUpperCase()}`,
            schemaId: activeSchema.id,
            code: activeSchema.code,
            title: activeSchema.title,
            reportNumber: getReportNumber(activeSchema, activeDraft.fields),
            createdAt,
            draft: cloneData({ ...activeDraft, finalizedAt: createdAt })
        };
        try {
            const records = readHistory();
            records.unshift(record);
            writeHistory(records);
            const schemaId = activeSchema.id;
            localStorage.removeItem(storagePrefix + schemaId);
            openForm(schemaId);
            renderHistory();
            showToast('Laporan berhasil disimpan. Form sudah dikosongkan untuk pengisian berikutnya.');
        } catch (error) {
            showToast('Laporan gagal disimpan. Kapasitas penyimpanan browser mungkin penuh.', true);
        }
    }

    function displayValue(item, value) {
        if (item.type === 'number' && /harga|total|nilai|estimasi/.test(item.key)) return formatRupiah(value);
        return value === 0 || value === '0' ? '0' : value || '—';
    }

    function renderDocumentationPages(schema, rows, reportNumber, createdAt) {
        if (!requiresEvidence(schema)) return '';
        return rows.filter(row => row._evidence?.dataUrl).map((row, index) => {
            const identityCells = schema.columns
                .filter(columnItem => row[columnItem.key] !== '' && row[columnItem.key] != null)
                .slice(0, 8)
                .map(columnItem => `
                    <div><strong>${escapeHtml(columnItem.label)}</strong><span>${escapeHtml(displayValue(columnItem, row[columnItem.key]))}</span></div>
                `).join('');
            const itemName = row.nama || row.item || row.solusi || row.pn || `Item ${index + 1}`;
            return `
                <article class="print-documentation-sheet">
                    <header class="documentation-header">
                        <img src="assets/logo-pt-bina-rekayasa-anugrah.png" alt="Logo PT Bina Rekayasa Anugrah" class="print-logo-img company-logo-img">
                        <div>
                            <span>LAMPIRAN DOKUMENTASI</span>
                            <h2>${escapeHtml(schema.title)}</h2>
                            <small>Nomor: ${escapeHtml(reportNumber)}</small>
                        </div>
                        <strong>ITEM ${index + 1}</strong>
                    </header>
                    <div class="documentation-title">
                        <span>Bukti fisik / dokumentasi pendukung</span>
                        <h3>${escapeHtml(itemName)}</h3>
                    </div>
                    <figure class="documentation-figure">
                        <img src="${escapeHtml(row._evidence.dataUrl)}" alt="Dokumentasi ${escapeHtml(itemName)}">
                        <figcaption>
                            <strong>${escapeHtml(row._evidence.caption || `Dokumentasi item ${index + 1}`)}</strong>
                            <span>File: ${escapeHtml(row._evidence.name || '—')}</span>
                            <span>Resolusi tersimpan: ${escapeHtml(`${row._evidence.width || '—'} × ${row._evidence.height || '—'} px`)}</span>
                        </figcaption>
                    </figure>
                    <section class="documentation-context">
                        <h4>Identitas dan konteks gambar</h4>
                        <div class="documentation-context-grid">
                            ${identityCells || '<div><strong>Keterangan</strong><span>Dokumentasi pendukung laporan</span></div>'}
                        </div>
                    </section>
                    <footer class="print-footer">
                        <span>Lampiran dokumentasi ${index + 1} dari ${rows.filter(item => item._evidence?.dataUrl).length}</span>
                        <strong>Dibuat: ${escapeHtml(formatDateTime(createdAt))}</strong>
                    </footer>
                </article>
            `;
        }).join('');
    }

    async function printReportPreview(preview, button) {
        if (!preview || !button) return;
        const originalMarkup = button.innerHTML;
        const printAnchor = document.createComment('report-print-anchor');
        const originalParent = preview.parentNode;
        button.disabled = true;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyiapkan halaman...';
        try {
            if (document.fonts?.ready) await document.fonts.ready;
            const images = [...preview.querySelectorAll('img')];
            await Promise.all(images.map(image => {
                if (image.complete && image.naturalWidth) {
                    return typeof image.decode === 'function' ? image.decode().catch(() => undefined) : Promise.resolve();
                }
                return new Promise(resolve => {
                    image.addEventListener('load', resolve, { once: true });
                    image.addEventListener('error', resolve, { once: true });
                });
            }));
            originalParent.insertBefore(printAnchor, preview);
            document.body.appendChild(preview);
            preview.classList.add('print-root-active');
            document.body.classList.add('report-printing');
            await new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
            window.print();
        } finally {
            document.body.classList.remove('report-printing');
            preview.classList.remove('print-root-active');
            if (printAnchor.parentNode) {
                printAnchor.parentNode.insertBefore(preview, printAnchor);
                printAnchor.remove();
            }
            button.disabled = false;
            button.innerHTML = originalMarkup;
        }
    }

    function renderPreview(rows, options = {}) {
        const schema = options.schema || activeSchema;
        const draft = options.draft || activeDraft;
        const preview = document.getElementById(options.targetId || 'reportPrintArea');
        if (!preview || !schema || !draft) return;
        const createdAt = options.createdAt || draft.finalizedAt || new Date().toISOString();
        const approvals = schema.approvals || [
            { label: 'Pengaju' },
            { label: 'Pengada' },
            { label: 'Pihak yang diajukan' }
        ];
        const reportNumber = getReportNumber(schema, draft.fields);
        const projectName = draft.fields.project || draft.fields.lokasi || 'PROJECT / MITRA';
        const summary = summaryMarkup(schema, draft);
        preview.innerHTML = `
            <div class="preview-toolbar">
                <strong><i class="fa-regular fa-file-pdf"></i> ${options.finalized ? 'Laporan final' : 'Preview dokumen terstandarisasi'}</strong>
                <div>
                    <button type="button" data-close-preview><i class="fa-solid fa-xmark"></i> Tutup</button>
                    <button type="button" data-print-report><i class="fa-solid fa-print"></i> Cetak / Simpan PDF</button>
                </div>
            </div>
            <article class="print-sheet">
                <section class="print-identification-section">
                <header class="print-brand">
                    <div class="print-logo-block">
                        <img src="assets/logo-pt-bina-rekayasa-anugrah.png" alt="Logo PT Bina Rekayasa Anugrah" class="print-logo-img company-logo-img">
                        <div><h2>PT BINA REKAYASA ANUGERAH</h2><small>Departemen Equipment & Maintenance</small></div>
                    </div>
                    <div class="print-logo-block partner">
                        <div><strong>${escapeHtml(projectName)}</strong><small>Project / Mitra</small></div>
                        <div class="print-logo-mark partner-logo"><span>MITRA</span></div>
                    </div>
                </header>
                <div class="print-title">
                    <span>${escapeHtml(schema.code)} · DOKUMEN TERKENDALI</span>
                    <h1>${escapeHtml(schema.title)}</h1>
                    <div class="print-document-number">Nomor: ${escapeHtml(reportNumber)}</div>
                </div>
                <div class="print-meta">
                    ${schema.fields.map(item => `
                        <div><strong>${escapeHtml(item.label)}</strong><span>${escapeHtml(displayValue(item, draft.fields[item.key]))}</span></div>
                    `).join('')}
                </div>
                </section>
                <section class="print-data-section">
                    <header class="print-section-header">
                        <div>
                            <small>RINCIAN DATA LAPORAN</small>
                            <h2>${escapeHtml(schema.tableTitle)}</h2>
                        </div>
                        <div class="print-section-identity">
                            <strong>${escapeHtml(schema.code)}</strong>
                            <span>${escapeHtml(reportNumber)}</span>
                        </div>
                    </header>
                <table class="print-table">
                    <thead><tr>
                        <th>No.</th>
                        ${schema.columns.map(item => `<th>${escapeHtml(item.label)}</th>`).join('')}
                    </tr></thead>
                    <tbody>
                        ${rows.map((row, index) => `<tr>
                            <td>${index + 1}</td>
                            ${schema.columns.map(item => `<td>${escapeHtml(displayValue(item, row[item.key]))}</td>`).join('')}
                        </tr>`).join('')}
                    </tbody>
                </table>
                <section class="print-closing-section">
                ${summary ? `<div class="calculation-summary"><div class="summary-card">${summary}</div></div>` : ''}
                <div class="print-approvals" style="grid-template-columns:repeat(${approvals.length},1fr)">
                    ${approvals.map(item => {
                        const name = item.nameField ? draft.fields[item.nameField] : '';
                        const role = item.roleField ? draft.fields[item.roleField] : '';
                        return `<div><span>${escapeHtml(item.label)}</span><div class="signature-space"></div><strong>( ${escapeHtml(name || '__________________')} )</strong>${role ? `<small>${escapeHtml(role)}</small>` : ''}</div>`;
                    }).join('')}
                </div>
                <footer class="print-footer">
                    <span>Dibuat otomatis oleh FleetMonitor</span>
                    <strong>Tanggal pembuatan: ${escapeHtml(formatDateTime(createdAt))}</strong>
                </footer>
                </section>
                </section>
            </article>
            ${renderDocumentationPages(schema, rows, reportNumber, createdAt)}
        `;
        preview.classList.add('active');
        preview.querySelector('[data-close-preview]').addEventListener('click', () => preview.classList.remove('active'));
        const printButton = preview.querySelector('[data-print-report]');
        printButton.addEventListener('click', () => printReportPreview(preview, printButton));
        preview.scrollIntoView({ behavior: 'smooth', block: 'start' });
        showToast(options.finalized ? 'Laporan final siap dicetak.' : 'Validasi selesai. Preview laporan siap ditinjau.');
    }

    function showToast(message, isError = false) {
        const toast = document.getElementById('reportToast');
        if (!toast) return;
        toast.style.background = isError ? '#a92f3d' : '#1f2937';
        toast.querySelector('i').className = isError ? 'fa-solid fa-circle-exclamation' : 'fa-solid fa-circle-check';
        toast.querySelector('span').textContent = message;
        toast.classList.add('show');
        window.clearTimeout(showToast.timeout);
        showToast.timeout = window.setTimeout(() => toast.classList.remove('show'), 2800);
    }

    function draftHasUserData(draft, schema = activeSchema) {
        if (!draft || !schema) return false;
        if (draft.importSource) return true;
        const empty = createEmptyDraft(schema);
        const fieldValues = (schema.fields || []).some(item => (
            String(draft.fields?.[item.key] ?? '') !== String(empty.fields?.[item.key] ?? '')
        ));
        if (fieldValues) return true;
        const draftRows = Array.isArray(draft.rows) ? draft.rows : [];
        const seedRows = empty.rows || [];
        const columns = schema.columns || [];
        const rowCount = Math.max(draftRows.length, seedRows.length);
        for (let rowIndex = 0; rowIndex < rowCount; rowIndex += 1) {
            const draftRow = draftRows[rowIndex] || {};
            const seedRow = seedRows[rowIndex] || {};
            const differs = columns.some(column => (
                String(draftRow[column.key] ?? '') !== String(seedRow[column.key] ?? '')
            ));
            if (differs) return true;
        }
        return false;
    }

    function importDraft(payload, options = {}) {
        const schemaId = payload?.schemaId;
        const schema = formSchemas.find(item => item.id === schemaId);
        if (!schema) throw new Error('Tipe laporan tujuan tidak ditemukan.');
        const existing = loadDraft(schema);
        const replace = options.replace !== false;
        const typeIssues = [];
        let typeIssueCount = 0;
        const recordTypeIssue = (path, definition, rawValue, reason) => {
            typeIssueCount += 1;
            if (typeIssues.length >= maxStoredImportTypeIssues) return;
            const preview = importTypeIssueRawPreview(rawValue);
            typeIssues.push({
                path,
                key: definition?.key || '',
                label: definition?.label || path,
                expectedType: definition?.type || 'schema-value',
                reason,
                rawValue: preview.value,
                rawValueTruncated: preview.truncated
            });
        };

        const sourceFields = payload?.fields;
        const importedFieldValues = (
            sourceFields
            && typeof sourceFields === 'object'
            && !Array.isArray(sourceFields)
        ) ? sourceFields : {};
        if (sourceFields != null && importedFieldValues !== sourceFields) {
            recordTypeIssue(
                'fields',
                { key: 'fields', label: 'Field laporan', type: 'object' },
                sourceFields,
                'invalid_field_container'
            );
        }
        const fieldDefinitions = new Map(schema.fields.map(item => [item.key, item]));
        Object.keys(importedFieldValues).forEach(key => {
            if (!fieldDefinitions.has(key)) {
                recordTypeIssue(
                    `fields.${String(key).slice(0, 100)}`,
                    { key, label: key, type: 'schema-field' },
                    importedFieldValues[key],
                    'unknown_field_ignored'
                );
            }
        });
        const normalizedFields = {};
        schema.fields.forEach(item => {
            if (!Object.prototype.hasOwnProperty.call(importedFieldValues, item.key)) return;
            const normalized = normalizeImportedValue(item, importedFieldValues[item.key]);
            if (normalized.issue) {
                recordTypeIssue(
                    `fields.${item.key}`,
                    item,
                    importedFieldValues[item.key],
                    normalized.issue
                );
            }
            if (normalized.value !== undefined) normalizedFields[item.key] = normalized.value;
        });

        let importedRows;
        if (Array.isArray(payload?.rows) && payload.rows.length) {
            const columnDefinitions = new Map(schema.columns.map(item => [item.key, item]));
            importedRows = payload.rows.map((sourceRow, rowIndex) => {
                if (!sourceRow || typeof sourceRow !== 'object' || Array.isArray(sourceRow)) {
                    recordTypeIssue(
                        `rows[${rowIndex}]`,
                        { key: '', label: `Baris ${rowIndex + 1}`, type: 'object' },
                        sourceRow,
                        'invalid_row'
                    );
                    return {};
                }
                Object.keys(sourceRow).forEach(key => {
                    if (importRowMetadataKeys.has(key)) return;
                    if (!columnDefinitions.has(key)) {
                        recordTypeIssue(
                            `rows[${rowIndex}].${String(key).slice(0, 100)}`,
                            { key, label: key, type: 'schema-column' },
                            sourceRow[key],
                            'unknown_column_ignored'
                        );
                    }
                });
                const normalizedRow = {};
                schema.columns.forEach(item => {
                    if (!Object.prototype.hasOwnProperty.call(sourceRow, item.key)) return;
                    const normalized = normalizeImportedValue(item, sourceRow[item.key]);
                    if (normalized.issue) {
                        recordTypeIssue(
                            `rows[${rowIndex}].${item.key}`,
                            item,
                            sourceRow[item.key],
                            normalized.issue
                        );
                    }
                    if (normalized.value !== undefined) normalizedRow[item.key] = normalized.value;
                });
                return normalizedRow;
            });
        } else {
            if (payload?.rows != null && !Array.isArray(payload.rows)) {
                recordTypeIssue(
                    'rows',
                    { key: 'rows', label: 'Baris laporan', type: 'array' },
                    payload.rows,
                    'invalid_row_container'
                );
            }
            importedRows = (schema.seedRows || [{}]).map(row => ({ ...row }));
        }

        const sourceMetadata = (
            payload?.importSource
            && typeof payload.importSource === 'object'
            && !Array.isArray(payload.importSource)
        ) ? { ...payload.importSource } : {};
        const importSource = {
            ...sourceMetadata,
            typeIssues,
            typeIssueCount,
            typeIssuesTruncated: typeIssueCount > typeIssues.length
        };
        const draft = {
            fields: replace
                ? { ...(schema.seedFields || {}), ...normalizedFields }
                : { ...(schema.seedFields || {}), ...(existing.fields || {}), ...normalizedFields },
            rows: replace
                ? importedRows
                : [
                    ...(existing.rows || []).filter(isRowPopulated),
                    ...importedRows
                ],
            importSource,
            updatedAt: new Date().toISOString()
        };
        if (!draft.rows.length) draft.rows = [{}];
        try {
            localStorage.setItem(storagePrefix + schema.id, JSON.stringify(draft));
        } catch (error) {
            throw new Error(`Draft tidak dapat disimpan di browser: ${error.message}`);
        }
        switchReportPanel('templates');
        openForm(schema.id);
        showToast(
            typeIssueCount
                ? `${typeIssueCount.toLocaleString('id-ID')} nilai impor tidak diterapkan langsung karena tipe, opsi, atau kolomnya tidak cocok dengan schema. Review nilai tersebut.`
                : 'Hasil ekstraksi diterapkan sebagai draft. Review field bertanda wajib sebelum menyimpan laporan.',
            typeIssueCount > 0
        );
        return cloneData(draft);
    }

    function isImportReferenced(importId) {
        if (!importId) return false;
        const referencedByDraft = formSchemas.some(schema => (
            loadDraft(schema)?.importSource?.importId === importId
        ));
        if (referencedByDraft) return true;
        return readHistory().some(record => (
            record?.draft?.importSource?.importId === importId
        ));
    }

    window.FleetReportForms = Object.freeze({
        version: '1.1.0',
        getSchemas: () => cloneData(formSchemas),
        getDraftState(schemaId) {
            const schema = formSchemas.find(item => item.id === schemaId);
            if (!schema) return null;
            const draft = loadDraft(schema);
            const hasData = draftHasUserData(draft, schema);
            return { hasData, draft: cloneData(draft) };
        },
        importDraft,
        isImportReferenced,
        openForm,
        selectPanel: switchReportPanel,
        notify: showToast
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createModuleMarkup);
    } else {
        createModuleMarkup();
    }
})();
(function (global) {
    'use strict';

    const VERSION = '1.0.1';
    const EOCD_SIGNATURE = 0x06054b50;
    const ZIP64_LOCATOR_SIGNATURE = 0x07064b50;
    const CENTRAL_SIGNATURE = 0x02014b50;
    const LOCAL_SIGNATURE = 0x04034b50;
    const DEFAULT_LIMITS = Object.freeze({
        maxEntries: 10000,
        maxEntryBytes: 32 * 1024 * 1024,
        maxUncompressedBytes: 64 * 1024 * 1024
    });

    function uint16(bytes, offset) {
        return bytes[offset] + bytes[offset + 1] * 0x100;
    }

    function uint32(bytes, offset) {
        return bytes[offset]
            + bytes[offset + 1] * 0x100
            + bytes[offset + 2] * 0x10000
            + bytes[offset + 3] * 0x1000000;
    }

    function signatureAt(bytes, offset, signature) {
        return offset >= 0
            && offset + 4 <= bytes.length
            && uint32(bytes, offset) === signature;
    }

    function bytesEqual(left, right) {
        if (left.length !== right.length) return false;
        for (let index = 0; index < left.length; index += 1) {
            if (left[index] !== right[index]) return false;
        }
        return true;
    }

    function parseExtraFields(bytes, start, length, label) {
        const identifiers = new Set();
        const end = start + length;
        let position = start;
        while (position < end) {
            if (position + 4 > end) throw new Error(`${label} memiliki extra field ZIP yang terpotong.`);
            const identifier = uint16(bytes, position);
            const valueLength = uint16(bytes, position + 2);
            position += 4;
            if (position + valueLength > end) {
                throw new Error(`${label} memiliki panjang extra field ZIP yang tidak valid.`);
            }
            identifiers.add(identifier);
            position += valueLength;
        }
        return identifiers;
    }

    function decodeName(rawName, utf8, label) {
        if (!utf8 && [...rawName].some(byte => byte > 0x7f)) {
            throw new Error(
                `${label} memiliki nama non-ASCII tanpa flag UTF-8; paket ditolak agar nama tidak berubah setelah preflight.`
            );
        }
        try {
            return new TextDecoder('utf-8', { fatal: true }).decode(rawName);
        } catch (error) {
            throw new Error(`${label} memiliki nama entry UTF-8 yang tidak valid.`);
        }
    }

    function validateName(name, isDirectory, label) {
        if (
            !name
            || name.includes('\\')
            || name.includes('\u0000')
            || /[\u0001-\u001f\u007f]/.test(name)
            || name.startsWith('/')
            || /^[A-Za-z]:/.test(name)
        ) {
            throw new Error(`${label} memiliki nama entry ZIP yang tidak aman: ${name || '(kosong)'}.`);
        }
        const path = isDirectory ? name.slice(0, -1) : name;
        const segments = path.split('/');
        if (
            !path
            || segments.some(segment => (
                !segment
                || segment === '.'
                || segment === '..'
                || /[. ]$/.test(segment)
                || ['__proto__', 'prototype', 'constructor'].includes(segment.toLowerCase())
            ))
        ) {
            throw new Error(`${label} memiliki path entry ZIP yang ambigu: ${name}.`);
        }
        return `${path.normalize('NFC').toLocaleLowerCase('en-US')}${isDirectory ? '/' : ''}`;
    }

    function inspect(input, label = 'Paket ZIP', customLimits = {}) {
        const bytes = input instanceof Uint8Array ? input : new Uint8Array(input);
        const limits = { ...DEFAULT_LIMITS, ...customLimits };
        if (bytes.length < 22) throw new Error(`${label} terlalu pendek untuk menjadi ZIP.`);

        const searchFloor = Math.max(0, bytes.length - 22 - 0xffff);
        let eocdOffset = -1;
        for (let offset = bytes.length - 22; offset >= searchFloor; offset -= 1) {
            if (!signatureAt(bytes, offset, EOCD_SIGNATURE)) continue;
            const commentLength = uint16(bytes, offset + 20);
            if (offset + 22 + commentLength === bytes.length) {
                eocdOffset = offset;
                break;
            }
        }
        if (eocdOffset < 0) throw new Error(`${label} tidak memiliki EOCD ZIP yang valid.`);
        if (signatureAt(bytes, eocdOffset - 20, ZIP64_LOCATOR_SIGNATURE)) {
            throw new Error(`${label} memakai ZIP64 yang tidak didukung importer browser.`);
        }

        const diskNumber = uint16(bytes, eocdOffset + 4);
        const centralDisk = uint16(bytes, eocdOffset + 6);
        const entriesOnDisk = uint16(bytes, eocdOffset + 8);
        const totalEntries = uint16(bytes, eocdOffset + 10);
        const centralSize = uint32(bytes, eocdOffset + 12);
        const centralOffset = uint32(bytes, eocdOffset + 16);
        if (diskNumber || centralDisk || entriesOnDisk !== totalEntries) {
            throw new Error(`${label} memakai ZIP multi-disk yang tidak didukung.`);
        }
        if (
            totalEntries === 0xffff
            || centralSize === 0xffffffff
            || centralOffset === 0xffffffff
        ) {
            throw new Error(`${label} memakai nilai ZIP64 yang tidak didukung.`);
        }
        if (totalEntries > limits.maxEntries) {
            throw new Error(`${label} memiliki ${totalEntries} record; batas aman ${limits.maxEntries}.`);
        }
        const centralEnd = centralOffset + centralSize;
        if (centralEnd !== eocdOffset || centralEnd > bytes.length) {
            throw new Error(`${label} memiliki rentang Central Directory yang tidak konsisten.`);
        }

        const records = [];
        const collisionKeys = new Set();
        const localOffsets = new Set();
        let position = centralOffset;
        let uncompressedBytes = 0;
        for (let index = 0; index < totalEntries; index += 1) {
            if (!signatureAt(bytes, position, CENTRAL_SIGNATURE) || position + 46 > centralEnd) {
                throw new Error(`${label} memiliki central header ke-${index + 1} yang rusak.`);
            }
            const flags = uint16(bytes, position + 8);
            const method = uint16(bytes, position + 10);
            const crc32 = uint32(bytes, position + 16);
            const compressedSize = uint32(bytes, position + 20);
            const uncompressedSize = uint32(bytes, position + 24);
            const nameLength = uint16(bytes, position + 28);
            const extraLength = uint16(bytes, position + 30);
            const commentLength = uint16(bytes, position + 32);
            const startDisk = uint16(bytes, position + 34);
            const externalAttributes = uint32(bytes, position + 38);
            const localOffset = uint32(bytes, position + 42);
            if ((flags & 0x0001) || (flags & 0x0040) || (flags & 0x2000)) {
                throw new Error(`${label} memiliki entry terenkripsi/berheader tersamar.`);
            }
            if ((flags & ~0x080e) !== 0 || (method !== 8 && (flags & 0x0006) !== 0)) {
                throw new Error(`${label} memakai general-purpose flag ZIP yang tidak didukung.`);
            }
            if (![0, 8].includes(method)) {
                throw new Error(`${label} memakai metode kompresi ZIP ${method} yang tidak diizinkan.`);
            }
            if (
                compressedSize === 0xffffffff
                || uncompressedSize === 0xffffffff
                || localOffset === 0xffffffff
                || startDisk !== 0
            ) {
                throw new Error(`${label} memiliki entry ZIP64 atau multi-disk.`);
            }

            const nameStart = position + 46;
            const extraStart = nameStart + nameLength;
            const recordEnd = extraStart + extraLength + commentLength;
            if (!nameLength || recordEnd > centralEnd) {
                throw new Error(`${label} memiliki panjang central header yang tidak valid.`);
            }
            const extraIdentifiers = parseExtraFields(bytes, extraStart, extraLength, label);
            if (extraIdentifiers.has(0x0001) || extraIdentifiers.has(0x7075)) {
                throw new Error(`${label} memiliki extra field ZIP64/Unicode Path yang tidak diizinkan.`);
            }
            const rawName = bytes.slice(nameStart, nameStart + nameLength);
            const name = decodeName(rawName, Boolean(flags & 0x0800), label);
            const isDirectory = name.endsWith('/') || Boolean(externalAttributes & 0x10);
            if (isDirectory !== name.endsWith('/')) {
                throw new Error(`${label} memiliki penanda file/direktori yang tidak konsisten: ${name}.`);
            }
            const collisionKey = validateName(name, isDirectory, label).replace(/\/$/, '');
            if (collisionKeys.has(collisionKey)) {
                throw new Error(`${label} memiliki collision nama entry ZIP: ${name}.`);
            }
            collisionKeys.add(collisionKey);
            if (uncompressedSize > limits.maxEntryBytes) {
                throw new Error(
                    `${label} memiliki entry ${name} sebesar ${(uncompressedSize / 1024 / 1024).toFixed(1)} MB; `
                    + `batas aman ${limits.maxEntryBytes / 1024 / 1024} MB.`
                );
            }
            if (method === 0 && compressedSize !== uncompressedSize) {
                throw new Error(`${label} memiliki ukuran entry STORE yang tidak konsisten: ${name}.`);
            }
            if (isDirectory && (uncompressedSize !== 0 || crc32 !== 0)) {
                throw new Error(`${label} memiliki entry direktori dengan data/CRC tidak kosong: ${name}.`);
            }
            uncompressedBytes += uncompressedSize;
            if (uncompressedBytes > limits.maxUncompressedBytes) {
                throw new Error(
                    `${label} mengembang menjadi ${(uncompressedBytes / 1024 / 1024).toFixed(1)} MB; `
                    + `batas aman ${limits.maxUncompressedBytes / 1024 / 1024} MB.`
                );
            }
            if (localOffsets.has(localOffset)) {
                throw new Error(`${label} memakai offset local header berulang.`);
            }
            localOffsets.add(localOffset);
            records.push({
                name,
                rawName,
                flags,
                method,
                crc32,
                compressedSize,
                uncompressedSize,
                localOffset,
                isDirectory
            });
            position = recordEnd;
        }
        if (position !== centralEnd) {
            throw new Error(`${label} memiliki data tambahan di Central Directory.`);
        }

        const spans = [];
        records.forEach(record => {
            const offset = record.localOffset;
            if (!signatureAt(bytes, offset, LOCAL_SIGNATURE) || offset + 30 > centralOffset) {
                throw new Error(`${label} memiliki local header rusak untuk ${record.name}.`);
            }
            const localFlags = uint16(bytes, offset + 6);
            const localMethod = uint16(bytes, offset + 8);
            const localCrc32 = uint32(bytes, offset + 14);
            const localCompressedSize = uint32(bytes, offset + 18);
            const localUncompressedSize = uint32(bytes, offset + 22);
            const localNameLength = uint16(bytes, offset + 26);
            const localExtraLength = uint16(bytes, offset + 28);
            const localNameStart = offset + 30;
            const localExtraStart = localNameStart + localNameLength;
            const dataStart = localExtraStart + localExtraLength;
            const dataEnd = dataStart + record.compressedSize;
            if (
                dataEnd > centralOffset
                || localFlags !== record.flags
                || localMethod !== record.method
                || !bytesEqual(
                    bytes.subarray(localNameStart, localNameStart + localNameLength),
                    record.rawName
                )
            ) {
                throw new Error(`${label} memiliki local header tidak konsisten untuk ${record.name}.`);
            }
            const localExtras = parseExtraFields(bytes, localExtraStart, localExtraLength, label);
            if (localExtras.has(0x0001) || localExtras.has(0x7075)) {
                throw new Error(`${label} memiliki local extra field yang tidak diizinkan.`);
            }
            if (
                !(record.flags & 0x0008)
                && (
                    localCrc32 !== record.crc32
                    || localCompressedSize !== record.compressedSize
                    || localUncompressedSize !== record.uncompressedSize
                )
            ) {
                throw new Error(`${label} memiliki CRC/ukuran local header yang tidak konsisten.`);
            }
            spans.push({ start: offset, end: dataEnd, name: record.name });
        });
        spans.sort((left, right) => left.start - right.start);
        for (let index = 1; index < spans.length; index += 1) {
            if (spans[index].start < spans[index - 1].end) {
                throw new Error(
                    `${label} memiliki rentang data entry yang overlap: `
                    + `${spans[index - 1].name} / ${spans[index].name}.`
                );
            }
        }

        return {
            version: VERSION,
            entries: totalEntries,
            uncompressedBytes,
            centralDirectoryBytes: centralSize,
            records
        };
    }

    function verifyJsZip(zip, audit, label = 'Paket ZIP') {
        const files = Object.values(zip?.files || {});
        if (files.length !== audit.records.length) {
            throw new Error(
                `${label} berubah dari ${audit.records.length} record raw menjadi ${files.length} entry JSZip.`
            );
        }
        const byName = new Map(files.map(entry => [entry.name, entry]));
        audit.records.forEach(record => {
            const entry = byName.get(record.name);
            if (!entry || Boolean(entry.dir) !== record.isDirectory) {
                throw new Error(`${label} kehilangan atau mengubah entry ${record.name} setelah JSZip.`);
            }
            const parsedData = entry._data;
            if (
                (
                    !record.isDirectory
                    && (
                        !parsedData
                        || !Number.isFinite(Number(parsedData.uncompressedSize))
                        || !Number.isFinite(Number(parsedData.compressedSize))
                        || !Number.isFinite(Number(parsedData.crc32))
                        || Number(parsedData.uncompressedSize) !== record.uncompressedSize
                        || Number(parsedData.compressedSize) !== record.compressedSize
                        || (Number(parsedData.crc32) >>> 0) !== record.crc32
                    )
                )
                || (entry.unsafeOriginalName && entry.unsafeOriginalName !== record.name)
            ) {
                throw new Error(`${label} mengubah CRC/ukuran/nama entry ${record.name} setelah JSZip.`);
            }
        });
        return true;
    }

    global.FleetZipPreflight = Object.freeze({
        version: VERSION,
        inspect,
        verifyJsZip
    });
})(typeof self !== 'undefined' ? self : window);
(function (global) {
    'use strict';

    const ENGINE_VERSION = '1.1.1';
    const ENVELOPE_VERSION = '1.0.0';
    const SUPPORTED_EXTENSIONS = new Set([
        '.doc',
        '.docx',
        '.pdf',
        '.xls',
        '.xlsx',
        '.xlsm',
        '.csv',
        '.tsv',
        '.jpg',
        '.jpeg',
        '.png',
        '.webp'
    ]);
    const AUDITED_UNSUPPORTED_EXTENSIONS = new Set(['.rar', '.zip', '.7z']);
    const SPREADSHEET_EXTENSIONS = new Set(['.xls', '.xlsx', '.xlsm', '.csv', '.tsv']);
    const IMAGE_EXTENSIONS = new Set(['.jpg', '.jpeg', '.png', '.webp']);
    const MAX_SOURCE_BYTES = 100 * 1024 * 1024;
    const MAX_ZIP_ENTRIES = 10000;
    const MAX_ZIP_UNCOMPRESSED_BYTES = 64 * 1024 * 1024;
    const MAX_ZIP_ENTRY_BYTES = 32 * 1024 * 1024;
    const MAX_RETAINED_FRAGMENTS = 600000;
    const MAX_FRAGMENT_CHARACTERS = 2 * 1024 * 1024;
    const MAX_RETAINED_CHARACTERS = 64 * 1024 * 1024;
    const MAX_WORKER_TASK_MS = 240000;
    const MAX_PDF_PAGES = 1000;
    const MAX_PDF_TEXT_ITEMS_PER_PAGE = 100000;
    const MAX_PDF_TEXT_CHARACTERS_PER_PAGE = 5 * 1024 * 1024;
    const MAX_PDF_TASK_MS = 15 * 60 * 1000;
    const MAX_OCR_TILES_PER_PAGE = 100;
    const MAX_RASTER_PIXELS = 40 * 1000 * 1000;
    const MAX_RASTER_DIMENSION = 20000;
    const MAX_MAPPING_CANDIDATES = 50000;
    const MAX_MAPPED_ROWS = 2000;
    const APPLICATION_ROOT = (() => {
        const scriptSource = document.currentScript?.src;
        return scriptSource ? new URL('../', scriptSource).href : document.baseURI;
    })();

    const SCHEMA_HINTS = {
        bapp: ['bapp', 'berita acara penerimaan penyerahan barang', 'penerimaan barang parts'],
        'bast-mde1': ['bast', 'mde 1', 'serah terima alat berat', 'berita acara serah terima'],
        'bhw-in': ['bhw in', 'barang masuk', 'buku harian warehouse masuk'],
        'bhw-out': ['bhw out', 'barang keluar', 'buku harian warehouse keluar'],
        'bukti-kirim': ['bukti kirim', 'bukti terima barang intern', 'dept equip'],
        'mde-02': ['mde 02', 'kartu pemeriksaan a2b', 'kartu pemeriksaan alat berat'],
        kalibrasi: ['laporan pelaksanaan kalibrasi', 'kalibrasi alat ukur'],
        lho: ['form lho', 'laporan harian operasi alat', 'lho'],
        'p2h-excavator': ['p2h hydraulic excavator', 'p2h excavator', 'pc 200'],
        'p2h-roller': ['p2h single drum rollers', 'p2h roller', 'bw211', 'cs10 gc'],
        'penyerahan-ekspedisi': ['penyerahan a2b kepada expedisi', 'penyerahan alat berat kepada ekspedisi'],
        ppb: ['form ppb', 'pesanan pembelian', 'pengadaan barang', 'p 3'],
        'repair-overhaul': ['repair overhoul', 'repair overhaul', 'r o', 'permohonan perbaikan'],
        spb: ['form spb', 'surat permintaan barang', 'p 1'],
        spl: ['form spl', 'surat perintah lembur', 'spl yard'],
        sppu: ['form sppu', 'surat permintaan parts urgent', 'sppu yard'],
        'sppu-006-pf04-cs10': ['sppu 006', 'pf 04', 'cs 10'],
        'maintenance-board': ['maintenance board a2b', 'maintenace board'],
        'parts-weekly': ['report parts weekly', 'laporan parts mingguan', 'parts weekly']
    };

    const FIELD_ALIASES = {
        nomor: ['no', 'nomor', 'nomor dokumen', 'document no'],
        nomor_urut: ['no urut', 'nomor urut'],
        nomor_log: ['no log', 'nomor log'],
        nomor_bukti: ['no bukti', 'nomor bukti'],
        nomor_po: ['no po', 'nomor po', 'purchase order'],
        nomor_spb: ['no spb', 'nomor spb'],
        nomor_ppb: ['no ppb', 'nomor ppb'],
        nomor_polisi: ['no polisi', 'nomor polisi', 'nopol', 'plat nomor'],
        nomor_faktur: ['no faktur', 'nomor faktur', 'invoice'],
        tanggal: ['tgl', 'tanggal', 'date'],
        tanggal_po: ['tanggal po', 'tgl po'],
        tanggal_faktur: ['tanggal faktur', 'tgl faktur'],
        periode: ['periode', 'bulan tahun', 'period'],
        bulan: ['bulan', 'month'],
        project: ['project', 'proyek', 'job site', 'site'],
        job_site: ['job site', 'site', 'lokasi kerja'],
        lokasi: ['lokasi', 'location', 'site'],
        kode_alat: ['kode alat', 'nomor kode alat', 'unit code', 'kode unit'],
        id_alat: ['id alat', 'unit id', 'kode unit'],
        code_number: ['code number', 'kode unit', 'unit code'],
        serial_number: ['serial number', 'nomor seri', 'no seri'],
        nomor_seri: ['serial number', 'nomor seri', 'no seri'],
        jenis_alat: ['jenis alat', 'tipe unit', 'equipment type'],
        tipe_alat: ['tipe alat', 'model unit', 'type'],
        merek_alat: ['merek alat', 'brand', 'merk alat'],
        merek_model: ['merek model', 'brand model', 'merk model'],
        model: ['model', 'unit model'],
        operator: ['operator', 'nama operator'],
        nrp: ['nrp', 'nik', 'employee id'],
        hm_om: ['hm om', 'hour meter', 'odometer'],
        hm_awal: ['hm awal', 'hour meter awal'],
        hm_akhir: ['hm akhir', 'hour meter akhir'],
        hm_sebelum: ['hm sebelum', 'hour meter sebelum'],
        hm_selesai: ['hm selesai', 'hour meter selesai'],
        nama: ['nama', 'nama barang', 'nama parts', 'part name', 'description', 'uraian'],
        item: ['item', 'uraian', 'description', 'pemeriksaan'],
        pn: ['pn', 'p n', 'part number', 'no part', 'nomor part'],
        part_number: ['part number', 'pn', 'p n', 'nomor part'],
        jumlah: ['jumlah', 'qty', 'quantity', 'kuantitas'],
        satuan: ['satuan', 'sat', 'uom', 'unit'],
        harga: ['harga', 'unit price', 'harga satuan'],
        total: ['total', 'jumlah harga', 'amount'],
        keterangan: ['keterangan', 'ket', 'remark', 'remarks', 'catatan', 'notes'],
        kondisi: ['kondisi', 'condition', 'status'],
        tanggal_mulai: ['tanggal mulai', 'start date'],
        tanggal_selesai: ['tanggal selesai', 'end date'],
        jam_mulai: ['jam mulai', 'start time'],
        jam_selesai: ['jam selesai', 'end time'],
        dibuat_oleh: ['dibuat oleh', 'prepared by', 'pembuat'],
        diperiksa_oleh: ['diperiksa oleh', 'checked by', 'pemeriksa'],
        disetujui_oleh: ['disetujui oleh', 'approved by', 'menyetujui']
    };

    const STOP_WORDS = new Set([
        'dan', 'atau', 'yang', 'untuk', 'dari', 'pada', 'dengan', 'ke', 'di', 'oleh',
        'form', 'laporan', 'surat', 'berita', 'acara', 'the', 'of', 'a', 'an'
    ]);

    let sharedWorker = null;
    let workerSequence = 0;
    const workerPending = new Map();

    function extensionOf(fileName) {
        const match = String(fileName || '').toLowerCase().match(/(\.[a-z0-9]+)$/);
        return match ? match[1] : '';
    }

    function assetUrl(relativePath) {
        return new URL(relativePath, APPLICATION_ROOT).href;
    }

    function normalizeSpace(value) {
        return String(value == null ? '' : value)
            .replace(/\u0000/g, '')
            .replace(/\r\n?/g, '\n')
            .replace(/[ \t\u00A0]+/g, ' ')
            .replace(/ *\n */g, '\n')
            .replace(/\n{3,}/g, '\n\n')
            .trim();
    }

    function normalizeKey(value) {
        return String(value == null ? '' : value)
            .normalize('NFKD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLocaleLowerCase('id')
            .replace(/&/g, ' dan ')
            .replace(/[^a-z0-9]+/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function tokens(value) {
        return normalizeKey(value)
            .split(' ')
            .filter(token => token.length > 1 && !STOP_WORDS.has(token));
    }

    function unique(values) {
        return [...new Set(values.filter(value => value !== '' && value != null))];
    }

    function clamp(value, minimum = 0, maximum = 1) {
        return Math.max(minimum, Math.min(maximum, value));
    }

    function createExtraction(format, parserName) {
        return {
            format,
            parser: {
                name: parserName,
                version: ENGINE_VERSION
            },
            sections: [],
            tables: [],
            fragments: [],
            artifacts: [],
            warnings: [],
            properties: {},
            stats: {
                sections: 0,
                tables: 0,
                fragments: 0,
                characters: 0
            },
            _fragmentSequence: 0,
            _characterCount: 0
        };
    }

    function nextFragmentId(extraction) {
        if (!Number.isFinite(extraction._fragmentSequence)) {
            extraction._fragmentSequence = extraction.fragments.reduce((maximum, fragment) => {
                const value = Number(String(fragment.id || '').replace(/\D/g, ''));
                return Number.isFinite(value) ? Math.max(maximum, value) : maximum;
            }, 0);
        }
        extraction._fragmentSequence += 1;
        return `F${String(extraction._fragmentSequence).padStart(7, '0')}`;
    }

    function addFragment(extraction, kind, value, sourceRef, meta = {}) {
        const text = normalizeSpace(value);
        if (!text && !meta.formula && !meta.comment && !meta.hyperlink) return null;
        if (text.length > MAX_FRAGMENT_CHARACTERS) {
            throw new Error(
                `${sourceRef || kind} memuat ${text.length.toLocaleString('id-ID')} karakter; `
                + `batas aman per fragmen ${MAX_FRAGMENT_CHARACTERS.toLocaleString('id-ID')}.`
            );
        }
        if (extraction.fragments.length >= MAX_RETAINED_FRAGMENTS) {
            throw new Error(
                `Dokumen menghasilkan lebih dari ${MAX_RETAINED_FRAGMENTS.toLocaleString('id-ID')} fragmen; `
                + 'impor dihentikan untuk melindungi memori browser.'
            );
        }
        const nextCharacterCount = Number(extraction._characterCount || 0) + text.length;
        if (nextCharacterCount > MAX_RETAINED_CHARACTERS) {
            throw new Error(
                `Dokumen menghasilkan lebih dari ${MAX_RETAINED_CHARACTERS / 1024 / 1024} MiB teks; `
                + 'impor dihentikan untuk melindungi memori browser.'
            );
        }
        extraction._characterCount = nextCharacterCount;
        const fragment = {
            id: nextFragmentId(extraction),
            kind,
            value: text,
            sourceRef,
            meta
        };
        extraction.fragments.push(fragment);
        return fragment.id;
    }

    function addWarning(extraction, code, message, severity = 'warning', sourceRef = '') {
        extraction.warnings.push({ code, message, severity, sourceRef });
    }

    function finalizeExtraction(extraction) {
        const characterCount = extraction.fragments.reduce(
            (sum, fragment) => sum + String(fragment.value || '').length,
            0
        );
        if (characterCount > MAX_RETAINED_CHARACTERS) {
            throw new Error(
                `Dokumen menghasilkan ${(characterCount / 1024 / 1024).toFixed(1)} MiB teks; `
                + `batas aman ${MAX_RETAINED_CHARACTERS / 1024 / 1024} MiB.`
            );
        }
        extraction.stats = {
            ...extraction.stats,
            sections: extraction.sections.length,
            tables: extraction.tables.length,
            fragments: extraction.fragments.length,
            characters: characterCount
        };
        delete extraction._fragmentSequence;
        delete extraction._characterCount;
        return extraction;
    }

    async function sha256(buffer) {
        if (!global.crypto?.subtle) {
            throw new Error('Browser tidak menyediakan Web Crypto SHA-256. Impor dihentikan agar deduplikasi tidak memakai checksum lemah.');
        }
        const digest = await global.crypto.subtle.digest('SHA-256', buffer);
        return [...new Uint8Array(digest)]
            .map(byte => byte.toString(16).padStart(2, '0'))
            .join('');
    }

    function detectSignature(bytes) {
        if (bytes.length >= 5 && new TextDecoder('ascii').decode(bytes.subarray(0, 5)) === '%PDF-') {
            return 'pdf';
        }
        if (bytes.length >= 4 && bytes[0] === 0x50 && bytes[1] === 0x4b) return 'zip';
        if (
            bytes.length >= 8
            && bytes[0] === 0xd0
            && bytes[1] === 0xcf
            && bytes[2] === 0x11
            && bytes[3] === 0xe0
        ) return 'cfb';
        if (bytes.length >= 3 && bytes[0] === 0xff && bytes[1] === 0xd8 && bytes[2] === 0xff) return 'jpeg';
        if (
            bytes.length >= 8
            && bytes[0] === 0x89
            && bytes[1] === 0x50
            && bytes[2] === 0x4e
            && bytes[3] === 0x47
        ) return 'png';
        if (
            bytes.length >= 6
            && String.fromCharCode(...bytes.subarray(0, 6)) === 'Rar!\x1a\x07'
        ) return 'rar';
        return 'text-or-unknown';
    }

    function signatureExpectedFor(extension) {
        if (extension === '.pdf') return ['pdf'];
        if (['.docx', '.xlsx', '.xlsm'].includes(extension)) return ['zip'];
        if (['.doc', '.xls'].includes(extension)) return ['cfb'];
        if (extension === '.jpg' || extension === '.jpeg') return ['jpeg'];
        if (extension === '.png') return ['png'];
        if (extension === '.rar') return ['rar'];
        return [];
    }

    function zipUint16(bytes, offset) {
        return bytes[offset] + bytes[offset + 1] * 0x100;
    }

    function zipUint32(bytes, offset) {
        return bytes[offset]
            + bytes[offset + 1] * 0x100
            + bytes[offset + 2] * 0x10000
            + bytes[offset + 3] * 0x1000000;
    }

    function zipSignatureAt(bytes, offset, signature) {
        return offset >= 0
            && offset + 4 <= bytes.length
            && zipUint32(bytes, offset) === signature;
    }

    function sanitizeZipEntryName(name) {
        const parts = [];
        String(name || '').replace(/\\/g, '/').split('/').forEach(part => {
            if (!part || part === '.') return;
            if (part === '..') {
                parts.pop();
                return;
            }
            parts.push(part);
        });
        return parts.join('/');
    }

    function preflightZipCentralDirectory(bytes, fileName) {
        const minimumEocdSize = 22;
        const searchFloor = Math.max(0, bytes.length - minimumEocdSize - 0xffff);
        let eocdOffset = -1;
        for (let offset = bytes.length - minimumEocdSize; offset >= searchFloor; offset -= 1) {
            if (!zipSignatureAt(bytes, offset, 0x06054b50)) continue;
            const commentLength = zipUint16(bytes, offset + 20);
            if (offset + minimumEocdSize + commentLength <= bytes.length) {
                eocdOffset = offset;
                break;
            }
        }
        if (eocdOffset < 0) {
            throw new Error(`${fileName} tidak memiliki End of Central Directory ZIP yang valid.`);
        }

        const diskNumber = zipUint16(bytes, eocdOffset + 4);
        const centralDisk = zipUint16(bytes, eocdOffset + 6);
        const entriesOnDisk = zipUint16(bytes, eocdOffset + 8);
        const totalEntries = zipUint16(bytes, eocdOffset + 10);
        const centralSize = zipUint32(bytes, eocdOffset + 12);
        const centralOffset = zipUint32(bytes, eocdOffset + 16);
        if (
            diskNumber !== 0
            || centralDisk !== 0
            || entriesOnDisk !== totalEntries
        ) {
            throw new Error(`${fileName} memakai ZIP multi-disk yang tidak didukung.`);
        }
        if (
            totalEntries === 0xffff
            || centralSize === 0xffffffff
            || centralOffset === 0xffffffff
        ) {
            throw new Error(`${fileName} memakai ZIP64; format ini ditolak oleh batas impor browser.`);
        }
        if (totalEntries > MAX_ZIP_ENTRIES) {
            throw new Error(`${fileName} memiliki ${totalEntries} record ZIP; batas aman ${MAX_ZIP_ENTRIES}.`);
        }
        const centralEnd = centralOffset + centralSize;
        if (
            centralOffset < 0
            || centralEnd > eocdOffset
            || centralEnd > bytes.length
        ) {
            throw new Error(`${fileName} memiliki Central Directory ZIP di luar batas file.`);
        }

        const utf8Decoder = new TextDecoder('utf-8');
        const legacyDecoder = new TextDecoder('windows-1252');
        const seenNames = new Set();
        let position = centralOffset;
        let uncompressedBytes = 0;
        for (let index = 0; index < totalEntries; index += 1) {
            if (!zipSignatureAt(bytes, position, 0x02014b50) || position + 46 > centralEnd) {
                throw new Error(`${fileName} memiliki record Central Directory ZIP yang rusak.`);
            }
            const flags = zipUint16(bytes, position + 8);
            const compressedSize = zipUint32(bytes, position + 20);
            const uncompressedSize = zipUint32(bytes, position + 24);
            const nameLength = zipUint16(bytes, position + 28);
            const extraLength = zipUint16(bytes, position + 30);
            const commentLength = zipUint16(bytes, position + 32);
            if (compressedSize === 0xffffffff || uncompressedSize === 0xffffffff) {
                throw new Error(`${fileName} memiliki entry ZIP64 yang tidak didukung.`);
            }
            if (flags & 0x0001) {
                throw new Error(`${fileName} memiliki entry ZIP terenkripsi yang tidak dapat diaudit.`);
            }
            const nameStart = position + 46;
            const recordEnd = nameStart + nameLength + extraLength + commentLength;
            if (!nameLength || recordEnd > centralEnd || recordEnd > bytes.length) {
                throw new Error(`${fileName} memiliki panjang record ZIP yang tidak valid.`);
            }
            const rawName = bytes.subarray(nameStart, nameStart + nameLength);
            const decodedName = (flags & 0x0800 ? utf8Decoder : legacyDecoder).decode(rawName);
            const safeName = sanitizeZipEntryName(decodedName);
            if (!safeName || decodedName.includes('\u0000')) {
                throw new Error(`${fileName} memiliki nama entry ZIP yang tidak valid.`);
            }
            if (seenNames.has(safeName)) {
                throw new Error(`${fileName} memiliki nama entry ZIP duplikat setelah sanitasi: ${safeName}.`);
            }
            seenNames.add(safeName);
            if (uncompressedSize > MAX_ZIP_ENTRY_BYTES) {
                throw new Error(
                    `${fileName} memiliki entry ${safeName} sebesar `
                    + `${(uncompressedSize / 1024 / 1024).toFixed(1)} MB; `
                    + `batas aman per entry ${MAX_ZIP_ENTRY_BYTES / 1024 / 1024} MB.`
                );
            }
            uncompressedBytes += uncompressedSize;
            if (uncompressedBytes > MAX_ZIP_UNCOMPRESSED_BYTES) {
                throw new Error(
                    `${fileName} mengembang menjadi ${(uncompressedBytes / 1024 / 1024).toFixed(1)} MB; `
                    + `batas aman ${MAX_ZIP_UNCOMPRESSED_BYTES / 1024 / 1024} MB.`
                );
            }
            position = recordEnd;
        }
        if (position > centralEnd) {
            throw new Error(`${fileName} memiliki Central Directory ZIP yang tidak konsisten.`);
        }
        return {
            entries: totalEntries,
            uncompressedBytes,
            centralDirectoryBytes: centralSize
        };
    }

    function ensureWorker() {
        if (sharedWorker) return sharedWorker;
        if (!global.Worker) throw new Error('Browser ini tidak mendukung Web Worker.');
        try {
            sharedWorker = new Worker(`${assetUrl('scripts/document-import-worker.js')}?v=20260726-15`);
        } catch (error) {
            throw new Error(`Web Worker tidak dapat dijalankan. Buka dashboard melalui HTTP/HTTPS. ${error.message}`);
        }
        sharedWorker.addEventListener('message', event => {
            const { id, type, result, error, progress } = event.data || {};
            const pending = workerPending.get(id);
            if (!pending) return;
            if (type === 'progress') {
                pending.onProgress?.(progress);
                return;
            }
            workerPending.delete(id);
            clearTimeout(pending.timer);
            if (type === 'error') {
                const workerError = new Error(error?.message || 'Worker gagal memproses dokumen.');
                workerError.name = error?.name || 'WorkerError';
                workerError.stack = error?.stack || '';
                pending.reject(workerError);
            } else {
                pending.resolve(result);
            }
        });
        sharedWorker.addEventListener('error', event => {
            workerPending.forEach(pending => {
                clearTimeout(pending.timer);
                pending.reject(new Error(`Worker dokumen berhenti: ${event.message || 'unknown error'}`));
            });
            workerPending.clear();
            sharedWorker?.terminate();
            sharedWorker = null;
        });
        return sharedWorker;
    }

    function runWorker(action, buffer, fileName, extension, onProgress, preserveBuffer = false) {
        const worker = ensureWorker();
        workerSequence += 1;
        const id = `IMPORT-WORK-${Date.now()}-${workerSequence}`;
        return new Promise((resolve, reject) => {
            const timer = setTimeout(() => {
                const timedOut = workerPending.get(id);
                if (!timedOut) return;
                workerPending.delete(id);
                timedOut.reject(new Error(
                    `${fileName} melewati batas waktu parser ${Math.round(MAX_WORKER_TASK_MS / 1000)} detik.`
                ));
                workerPending.forEach(pending => {
                    clearTimeout(pending.timer);
                    pending.reject(new Error('Worker direset setelah tugas lain melewati batas waktu.'));
                });
                workerPending.clear();
                worker.terminate();
                if (sharedWorker === worker) sharedWorker = null;
            }, MAX_WORKER_TASK_MS);
            workerPending.set(id, { resolve, reject, onProgress, timer });
            const transferable = preserveBuffer ? buffer.slice(0) : buffer;
            try {
                worker.postMessage(
                    { id, action, buffer: transferable, fileName, extension },
                    [transferable]
                );
            } catch (error) {
                clearTimeout(timer);
                workerPending.delete(id);
                reject(error);
            }
        });
    }

    class OcrSession {
        constructor(onProgress) {
            this.onProgress = onProgress;
            this.workerPromise = null;
        }

        async getWorker() {
            if (!global.Tesseract?.createWorker) {
                throw new Error('Tesseract OCR tidak termuat.');
            }
            if (!this.workerPromise) {
                const vendorBase = assetUrl('scripts/vendor/').replace(/\/$/, '');
                this.workerPromise = global.Tesseract.createWorker(['ind', 'eng'], 1, {
                    workerPath: assetUrl('scripts/vendor/tesseract-5.1.1.worker.min.js'),
                    corePath: vendorBase,
                    langPath: vendorBase,
                    gzip: true,
                    logger: message => {
                        this.onProgress?.({
                            stage: 'ocr',
                            current: Number(message.progress || 0),
                            total: 1,
                            message: message.status || 'OCR'
                        });
                    }
                }).then(async worker => {
                    await worker.setParameters({
                        preserve_interword_spaces: '1',
                        user_defined_dpi: '300'
                    });
                    return worker;
                });
            }
            return this.workerPromise;
        }

        async recognize(image, sourceRef) {
            const worker = await this.getWorker();
            const result = await worker.recognize(image, {}, {
                text: true,
                tsv: true,
                blocks: true
            });
            return {
                text: normalizeSpace(result?.data?.text || ''),
                confidence: Number(result?.data?.confidence || 0),
                sourceRef,
                words: parseOcrTsv(result?.data?.tsv || '')
            };
        }

        async terminate() {
            if (!this.workerPromise) return;
            try {
                const worker = await this.workerPromise;
                await worker.terminate();
            } catch (error) {
                // OCR cleanup should never hide a successful extraction.
            }
            this.workerPromise = null;
        }
    }

    function parseOcrTsv(tsv) {
        const rows = String(tsv || '').split(/\r?\n/);
        if (rows.length < 2) return [];
        const headers = rows[0].split('\t');
        const indexes = Object.fromEntries(headers.map((header, index) => [header, index]));
        return rows.slice(1).map(row => row.split('\t')).filter(columns => (
            columns[indexes.level] === '5' && columns[indexes.text]?.trim()
        )).map(columns => ({
            text: columns.slice(indexes.text).join('\t').trim(),
            confidence: Number(columns[indexes.conf] || 0),
            left: Number(columns[indexes.left] || 0),
            top: Number(columns[indexes.top] || 0),
            width: Number(columns[indexes.width] || 0),
            height: Number(columns[indexes.height] || 0),
            block: Number(columns[indexes.block_num] || 0),
            paragraph: Number(columns[indexes.par_num] || 0),
            line: Number(columns[indexes.line_num] || 0),
            word: Number(columns[indexes.word_num] || 0)
        }));
    }

    async function extractFile(file, options = {}, onProgress) {
        if (!file || typeof file.arrayBuffer !== 'function') {
            throw new TypeError('Input harus berupa File/Blob browser.');
        }
        if (Number(file.size || 0) > MAX_SOURCE_BYTES) {
            throw new Error(
                `Ukuran file ${Math.ceil(file.size / 1024 / 1024)} MB melewati batas aman `
                + `${MAX_SOURCE_BYTES / 1024 / 1024} MB.`
            );
        }
        const extension = extensionOf(file.name);
        const buffer = await file.arrayBuffer();
        const bytes = new Uint8Array(buffer);
        const signature = detectSignature(bytes);
        const expectedSignatures = signatureExpectedFor(extension);
        const checksum = await sha256(buffer);
        const source = {
            fileName: file.name || 'dokumen-tanpa-nama',
            relativePath: file.webkitRelativePath || file.relativePath || file.name || '',
            extension,
            mimeType: file.type || '',
            size: Number(file.size || buffer.byteLength),
            lastModified: file.lastModified ? new Date(file.lastModified).toISOString() : null,
            sha256: checksum,
            signature
        };
        const requestedPdfScale = Number(options.pdfScale);
        const extractionOptions = {
            ...options,
            ocrMode: ['off', 'auto', 'all'].includes(options.ocrMode) ? options.ocrMode : 'auto',
            pdfScale: Number.isFinite(requestedPdfScale)
                ? clamp(requestedPdfScale, 1.25, 3)
                : 2
        };
        const zipPreflightAudit = signature === 'zip' && expectedSignatures.includes('zip')
            ? global.FleetZipPreflight?.inspect(bytes, source.fileName, {
                maxEntries: MAX_ZIP_ENTRIES,
                maxEntryBytes: MAX_ZIP_ENTRY_BYTES,
                maxUncompressedBytes: MAX_ZIP_UNCOMPRESSED_BYTES
            })
            : null;
        if (signature === 'zip' && expectedSignatures.includes('zip') && !zipPreflightAudit) {
            throw new Error('Modul preflight ZIP tidak termuat; dokumen OOXML tidak diproses.');
        }

        onProgress?.({ stage: 'hash', current: 1, total: 1, message: 'Checksum SHA-256 selesai' });

        let extraction;
        if (expectedSignatures.length && !expectedSignatures.includes(signature)) {
            extraction = createExtraction(extension.replace('.', '') || 'unknown', 'File signature audit adapter');
            addWarning(
                extraction,
                'signature_extension_mismatch',
                `Isi file terdeteksi sebagai ${signature}, tidak sesuai ekstensi ${extension}. Parser tidak dijalankan demi keamanan.`,
                'error',
                source.fileName
            );
            extraction.artifacts.push({
                kind: 'signature-mismatch-source',
                name: source.fileName,
                size: source.size,
                sourceRef: source.relativePath || source.fileName
            });
            extraction.stats.bytes = source.size;
            extraction.stats.signature = signature;
            extraction.stats.supported = false;
            return { source, extraction: finalizeExtraction(extraction) };
        } else if (extension === '.pdf') {
            extraction = await extractPdf(buffer, source, extractionOptions, onProgress);
        } else if (extension === '.docx') {
            extraction = await extractDocx(buffer, source, extractionOptions, onProgress);
        } else if (extension === '.doc') {
            extraction = await runWorker('legacy-doc', buffer, source.fileName, extension, onProgress);
        } else if (SPREADSHEET_EXTENSIONS.has(extension)) {
            const needsOriginalBuffer = extractionOptions.ocrMode === 'all'
                && ['.xlsx', '.xlsm'].includes(extension);
            extraction = await runWorker(
                'spreadsheet',
                buffer,
                source.fileName,
                extension,
                onProgress,
                needsOriginalBuffer
            );
            if (extractionOptions.ocrMode === 'all' && ['.xlsx', '.xlsm'].includes(extension)) {
                await appendSpreadsheetMediaOcr(buffer, extraction, extractionOptions, onProgress);
            }
        } else if (IMAGE_EXTENSIONS.has(extension)) {
            extraction = await extractImage(file, source, extractionOptions, onProgress, bytes);
        } else {
            extraction = extractUnsupported(source);
        }

        if (zipPreflightAudit) {
            extraction.properties.zipPreflight = {
                version: zipPreflightAudit.version,
                entries: zipPreflightAudit.entries,
                uncompressedBytes: zipPreflightAudit.uncompressedBytes,
                centralDirectoryBytes: zipPreflightAudit.centralDirectoryBytes
            };
        }
        extraction = finalizeExtraction(extraction);
        extraction.stats.bytes = source.size;
        extraction.stats.signature = signature;
        extraction.stats.supported = SUPPORTED_EXTENSIONS.has(extension);
        return { source, extraction };
    }

    function extractUnsupported(source) {
        const extraction = createExtraction(source.extension.replace('.', '') || 'unknown', 'File audit adapter');
        const isArchive = AUDITED_UNSUPPORTED_EXTENSIONS.has(source.extension);
        addWarning(
            extraction,
            isArchive ? 'archive_requires_unpack' : 'unsupported_file_type',
            isArchive
                ? `Arsip ${source.extension.toUpperCase()} dicatat tetapi tidak dibuka. Ekstrak arsip lalu impor file di dalamnya.`
                : `Jenis file ${source.extension || '(tanpa ekstensi)'} belum didukung.`,
            'error',
            source.fileName
        );
        extraction.artifacts.push({
            kind: 'unsupported-source',
            name: source.fileName,
            size: source.size,
            sourceRef: source.relativePath || source.fileName
        });
        extraction.stats.supported = false;
        return extraction;
    }

    async function extractDocx(buffer, source, options, onProgress) {
        if (!global.JSZip) throw new Error('JSZip tidak tersedia untuk membaca DOCX.');
        const extraction = createExtraction('docx', 'Fleet OOXML complete-part adapter + JSZip 3.10.1');
        const rawZipAudit = global.FleetZipPreflight.inspect(
            new Uint8Array(buffer),
            source.fileName,
            {
                maxEntries: MAX_ZIP_ENTRIES,
                maxEntryBytes: MAX_ZIP_ENTRY_BYTES,
                maxUncompressedBytes: MAX_ZIP_UNCOMPRESSED_BYTES
            }
        );
        const zip = await global.JSZip.loadAsync(buffer);
        global.FleetZipPreflight.verifyJsZip(zip, rawZipAudit, source.fileName);
        const allEntries = Object.values(zip.files).filter(entry => !entry.dir);
        validateZipEntries(allEntries, source.fileName);
        if (!zip.file('word/document.xml')) {
            throw new Error('Paket ZIP tidak memiliki word/document.xml dan bukan DOCX yang valid.');
        }
        extraction.properties.packageParts = allEntries.map(entry => ({
            name: entry.name,
            size: entry._data?.uncompressedSize || 0
        }));

        const orderedParts = allEntries
            .filter(entry => (
                /^word\/document\.xml$/i.test(entry.name)
                || /^word\/header\d+\.xml$/i.test(entry.name)
                || /^word\/footer\d+\.xml$/i.test(entry.name)
                || /^word\/(footnotes|endnotes|comments)\.xml$/i.test(entry.name)
            ))
            .sort((left, right) => docxPartOrder(left.name) - docxPartOrder(right.name)
                || left.name.localeCompare(right.name));
        let xmlFailureCount = 0;
        let tableCount = 0;
        let paragraphCount = 0;
        let fieldCodeCount = 0;

        for (let partIndex = 0; partIndex < orderedParts.length; partIndex += 1) {
            const entry = orderedParts[partIndex];
            onProgress?.({
                stage: 'docx-part',
                current: partIndex + 1,
                total: orderedParts.length,
                message: `Membaca ${entry.name}`
            });
            const xml = await entry.async('text');
            const documentNode = new DOMParser().parseFromString(xml, 'application/xml');
            if (documentNode.querySelector('parsererror')) {
                xmlFailureCount += 1;
                addWarning(
                    extraction,
                    'docx_xml_part_failed',
                    `Bagian ${entry.name} tidak valid dan tidak dapat diurai.`,
                    'error',
                    `docx:${entry.name}`
                );
                continue;
            }
            const section = {
                id: `DOCX-PART-${partIndex + 1}`,
                title: docxPartTitle(entry.name),
                kind: docxPartKind(entry.name),
                sourceRef: `docx:${entry.name}`,
                fragmentRefs: [],
                meta: { packagePart: entry.name }
            };
            extraction.sections.push(section);
            const container = firstByLocalName(documentNode, 'body') || documentNode.documentElement;
            const blocks = docxContentBlocks(container);
            let localParagraph = 0;
            let localTable = 0;

            blocks.forEach((block, blockIndex) => {
                if (block.localName === 'p') {
                    localParagraph += 1;
                    paragraphCount += 1;
                    const text = openXmlVisibleText(block);
                    const sourceRef = `docx:${entry.name}#p${localParagraph}`;
                    if (text) {
                        const fragmentId = addFragment(
                            extraction,
                            'paragraph',
                            text,
                            sourceRef,
                            { part: entry.name, blockIndex }
                        );
                        if (fragmentId) section.fragmentRefs.push(fragmentId);
                    }
                    const fieldCodes = elementsByLocalName(block, 'instrText')
                        .map(node => normalizeSpace(node.textContent))
                        .filter(Boolean);
                    fieldCodes.forEach((fieldCode, fieldIndex) => {
                        const fragmentId = addFragment(
                            extraction,
                            'field-code',
                            fieldCode,
                            `${sourceRef}:field${fieldIndex + 1}`,
                            { part: entry.name }
                        );
                        if (fragmentId) section.fragmentRefs.push(fragmentId);
                        fieldCodeCount += 1;
                    });
                    const drawingDescriptions = elementsByLocalName(block, 'docPr')
                        .flatMap(node => unique([
                            node.getAttribute('title'),
                            node.getAttribute('descr'),
                            node.getAttribute('name')
                        ]))
                        .filter(Boolean);
                    drawingDescriptions.forEach((description, descriptionIndex) => {
                        const fragmentId = addFragment(
                            extraction,
                            'image-description',
                            description,
                            `${sourceRef}:drawing${descriptionIndex + 1}`,
                            { part: entry.name }
                        );
                        if (fragmentId) section.fragmentRefs.push(fragmentId);
                    });
                } else if (block.localName === 'tbl') {
                    localTable += 1;
                    tableCount += 1;
                    const table = parseDocxTable(
                        extraction,
                        block,
                        entry.name,
                        localTable,
                        tableCount
                    );
                    if (table) {
                        extraction.tables.push(table);
                        section.fragmentRefs.push(
                            ...table.headerFragmentRefs,
                            ...table.rows.flatMap(row => row.fragmentRefs)
                        .filter(Boolean));
                    }
                }
            });
            section.meta.paragraphs = localParagraph;
            section.meta.tables = localTable;
        }

        const relationshipEntries = allEntries.filter(entry => /_rels\/.*\.rels$/i.test(entry.name));
        for (const entry of relationshipEntries) {
            try {
                const xml = await entry.async('text');
                const relationDocument = new DOMParser().parseFromString(xml, 'application/xml');
                const relationships = elementsByLocalName(relationDocument, 'Relationship');
                relationships.forEach(relation => {
                    const target = relation.getAttribute('Target') || '';
                    const type = relation.getAttribute('Type') || '';
                    const targetMode = relation.getAttribute('TargetMode') || '';
                    extraction.artifacts.push({
                        kind: targetMode === 'External' ? 'external-relationship' : 'package-relationship',
                        name: target,
                        size: 0,
                        sourceRef: `docx:${entry.name}`,
                        meta: { type, targetMode }
                    });
                    if (targetMode === 'External' && target) {
                        addFragment(
                            extraction,
                            'hyperlink',
                            target,
                            `docx:${entry.name}#${relation.getAttribute('Id') || target}`,
                            { type }
                        );
                    }
                });
            } catch (error) {
                addWarning(
                    extraction,
                    'docx_relationship_audit_failed',
                    `Relasi ${entry.name} gagal diaudit: ${error.message}`,
                    'warning',
                    `docx:${entry.name}`
                );
            }
        }

        await readDocxProperties(zip, extraction);
        const mediaEntries = allEntries.filter(entry => /^word\/media\//i.test(entry.name));
        const embeddedEntries = allEntries.filter(entry => /^word\/embeddings\//i.test(entry.name));
        mediaEntries.forEach(entry => {
            extraction.artifacts.push({
                kind: 'embedded-media',
                name: entry.name,
                size: entry._data?.uncompressedSize || 0,
                sourceRef: `docx:${entry.name}`
            });
        });
        embeddedEntries.forEach(entry => {
            extraction.artifacts.push({
                kind: 'embedded-object',
                name: entry.name,
                size: entry._data?.uncompressedSize || 0,
                sourceRef: `docx:${entry.name}`
            });
        });

        const visibleCharacterCount = extraction.fragments
            .filter(fragment => (
                ['paragraph', 'cell'].includes(fragment.kind)
                && /^docx:word\/document\.xml/i.test(fragment.sourceRef)
            ))
            .reduce(
                (sum, fragment) => sum + String(fragment.value || '').length,
            0
            );
        const shouldOcrMedia = options.ocrMode === 'all'
            || (options.ocrMode === 'auto' && visibleCharacterCount < 80);
        let ocrMediaCount = 0;
        if (shouldOcrMedia && mediaEntries.length) {
            const ocr = new OcrSession(onProgress);
            try {
                for (let mediaIndex = 0; mediaIndex < mediaEntries.length; mediaIndex += 1) {
                    const entry = mediaEntries[mediaIndex];
                    if (!isOcrImageName(entry.name)) continue;
                    onProgress?.({
                        stage: 'docx-media-ocr',
                        current: mediaIndex + 1,
                        total: mediaEntries.length,
                        message: `OCR media ${entry.name}`
                    });
                    try {
                        const blob = await entry.async('blob');
                        await assertSafeRasterBlob(blob, entry.name);
                        const result = await ocr.recognize(blob, `docx:${entry.name}:ocr`);
                        if (result.text) {
                            addFragment(
                                extraction,
                                'ocr-text',
                                result.text,
                                result.sourceRef,
                                { confidence: result.confidence, mediaName: entry.name }
                            );
                            if (result.confidence < 55) {
                                addWarning(
                                    extraction,
                                    'low_ocr_confidence',
                                    `Confidence OCR ${entry.name} hanya ${Math.round(result.confidence)}%; verifikasi isi gambar.`,
                                    'warning',
                                    result.sourceRef
                                );
                            }
                            ocrMediaCount += 1;
                        }
                    } catch (error) {
                        addWarning(
                            extraction,
                            'docx_media_ocr_failed',
                            `OCR ${entry.name} gagal: ${error.message}`,
                            'warning',
                            `docx:${entry.name}`
                        );
                    }
                }
            } finally {
                await ocr.terminate();
            }
        } else if (mediaEntries.length) {
            addWarning(
                extraction,
                'docx_media_catalogued_not_ocr',
                `${mediaEntries.length} gambar tertanam dicatat. Gunakan mode OCR maksimal bila gambar memuat teks penting.`,
                'info',
                'docx:word/media'
            );
        }
        if (embeddedEntries.length) {
            addWarning(
                extraction,
                'docx_embedded_objects',
                `${embeddedEntries.length} objek tertanam dicatat tetapi membutuhkan aplikasi asal untuk interpretasi penuh.`,
                'warning',
                'docx:word/embeddings'
            );
        }
        if (xmlFailureCount) {
            addWarning(
                extraction,
                'docx_incomplete_xml_coverage',
                `${xmlFailureCount} bagian XML gagal diproses; hasil tidak boleh difinalkan sebelum review.`,
                'error',
                'docx:package'
            );
        }

        extraction.stats.packageParts = allEntries.length;
        extraction.stats.xmlPartsProcessed = orderedParts.length - xmlFailureCount;
        extraction.stats.xmlPartsFailed = xmlFailureCount;
        extraction.stats.paragraphs = paragraphCount;
        extraction.stats.tables = tableCount;
        extraction.stats.fieldCodes = fieldCodeCount;
        extraction.stats.embeddedMedia = mediaEntries.length;
        extraction.stats.embeddedObjects = embeddedEntries.length;
        extraction.stats.ocrMedia = ocrMediaCount;
        return finalizeExtraction(extraction);
    }

    function validateZipEntries(entries, fileName) {
        if (entries.length > MAX_ZIP_ENTRIES) {
            throw new Error(`${fileName} memiliki ${entries.length} entry; batas aman ${MAX_ZIP_ENTRIES}.`);
        }
        const oversizedEntry = entries.find(
            entry => Number(entry._data?.uncompressedSize || 0) > MAX_ZIP_ENTRY_BYTES
        );
        if (oversizedEntry) {
            throw new Error(
                `${fileName} memiliki entry ${oversizedEntry.name} sebesar `
                + `${(Number(oversizedEntry._data?.uncompressedSize || 0) / 1024 / 1024).toFixed(1)} MB; `
                + `batas aman per entry ${MAX_ZIP_ENTRY_BYTES / 1024 / 1024} MB.`
            );
        }
        const uncompressedBytes = entries.reduce(
            (sum, entry) => sum + Number(entry._data?.uncompressedSize || 0),
            0
        );
        if (uncompressedBytes > MAX_ZIP_UNCOMPRESSED_BYTES) {
            throw new Error(
                `${fileName} mengembang menjadi ${(uncompressedBytes / 1024 / 1024).toFixed(1)} MB; `
                + `batas aman ${MAX_ZIP_UNCOMPRESSED_BYTES / 1024 / 1024} MB.`
            );
        }
        return uncompressedBytes;
    }

    function docxPartOrder(name) {
        if (/word\/document\.xml$/i.test(name)) return 0;
        if (/word\/header/i.test(name)) return 10;
        if (/word\/footer/i.test(name)) return 20;
        if (/word\/footnotes/i.test(name)) return 30;
        if (/word\/endnotes/i.test(name)) return 40;
        if (/word\/comments/i.test(name)) return 50;
        return 100;
    }

    function docxPartTitle(name) {
        if (/document\.xml$/i.test(name)) return 'Isi utama dokumen';
        if (/header/i.test(name)) return `Header — ${name.split('/').pop()}`;
        if (/footer/i.test(name)) return `Footer — ${name.split('/').pop()}`;
        if (/footnotes/i.test(name)) return 'Catatan kaki';
        if (/endnotes/i.test(name)) return 'Catatan akhir';
        if (/comments/i.test(name)) return 'Komentar/review';
        return name;
    }

    function docxPartKind(name) {
        if (/document\.xml$/i.test(name)) return 'document-body';
        if (/header/i.test(name)) return 'header';
        if (/footer/i.test(name)) return 'footer';
        if (/footnotes/i.test(name)) return 'footnotes';
        if (/endnotes/i.test(name)) return 'endnotes';
        if (/comments/i.test(name)) return 'comments';
        return 'xml-part';
    }

    function firstByLocalName(root, localName) {
        return elementsByLocalName(root, localName)[0] || null;
    }

    function elementsByLocalName(root, localName) {
        if (!root?.getElementsByTagNameNS) return [];
        return [...root.getElementsByTagNameNS('*', localName)];
    }

    function directElementChildren(node) {
        return [...(node?.childNodes || [])].filter(child => child.nodeType === 1);
    }

    function docxContentBlocks(container) {
        const blocks = [];
        function visit(node) {
            directElementChildren(node).forEach(child => {
                if (child.localName === 'p' || child.localName === 'tbl') {
                    blocks.push(child);
                    return;
                }
                visit(child);
            });
        }
        visit(container);
        return blocks;
    }

    function attributeByLocalName(node, localName) {
        return [...(node?.attributes || [])].find(attribute => attribute.localName === localName)?.value || '';
    }

    function openXmlVisibleText(node) {
        const parts = [];
        function walk(current) {
            if (current.nodeType === 3) return;
            if (current.nodeType !== 1) return;
            switch (current.localName) {
            case 't':
                parts.push(current.textContent || '');
                return;
            case 'delText':
                parts.push(` [TEKS DIHAPUS: ${current.textContent || ''}] `);
                return;
            case 'tab':
                parts.push('\t');
                return;
            case 'br':
            case 'cr':
                parts.push('\n');
                return;
            case 'noBreakHyphen':
                parts.push('‑');
                return;
            case 'softHyphen':
                parts.push('\u00AD');
                return;
            case 'sym': {
                const hexadecimal = attributeByLocalName(current, 'char');
                const codePoint = Number.parseInt(hexadecimal, 16);
                parts.push(
                    Number.isFinite(codePoint) && codePoint >= 0 && codePoint <= 0x10ffff
                        ? String.fromCodePoint(codePoint)
                        : `[SYMBOL ${hexadecimal || '?'}]`
                );
                return;
            }
            case 'checkBox': {
                const checked = elementsByLocalName(current, 'checked')[0];
                const value = attributeByLocalName(checked, 'val').toLowerCase();
                parts.push(checked && !['0', 'false', 'off'].includes(value) ? '☒' : '☐');
                return;
            }
            case 'instrText':
                return;
            default:
                [...current.childNodes].forEach(walk);
            }
        }
        walk(node);
        return normalizeSpace(parts.join(''));
    }

    function parseDocxTable(extraction, tableNode, partName, localTableIndex, globalTableIndex) {
        const rowNodes = directElementChildren(tableNode).filter(child => child.localName === 'tr');
        if (!rowNodes.length) return null;
        const rawRows = rowNodes.map((rowNode, rowIndex) => {
            const cells = directElementChildren(rowNode).filter(child => child.localName === 'tc');
            const logicalCells = [];
            cells.forEach((cellNode, physicalColumnIndex) => {
                const value = normalizeSpace(
                    directElementChildren(cellNode)
                        .map(child => child.localName === 'tbl'
                            ? elementsByLocalName(child, 't').map(node => node.textContent).join(' ')
                            : openXmlVisibleText(child))
                        .filter(Boolean)
                        .join('\n')
                );
                const gridSpanNode = elementsByLocalName(cellNode, 'gridSpan')[0];
                const gridSpan = clamp(Number.parseInt(attributeByLocalName(gridSpanNode, 'val') || '1', 10), 1, 256);
                const verticalMergeNode = elementsByLocalName(cellNode, 'vMerge')[0];
                const verticalMerge = verticalMergeNode
                    ? attributeByLocalName(verticalMergeNode, 'val') || 'continue'
                    : '';
                const logicalColumnIndex = logicalCells.length;
                const sourceRef = `docx:${partName}#table${localTableIndex}:r${rowIndex + 1}:c${logicalColumnIndex + 1}`;
                const fragmentId = addFragment(extraction, 'cell', value, sourceRef, {
                    part: partName,
                    table: localTableIndex,
                    row: rowIndex + 1,
                    column: logicalColumnIndex + 1,
                    physicalColumn: physicalColumnIndex + 1,
                    gridSpan,
                    verticalMerge
                });
                logicalCells.push({
                    value,
                    fragmentId,
                    gridSpan,
                    verticalMerge
                });
                for (let spanIndex = 1; spanIndex < gridSpan; spanIndex += 1) {
                    logicalCells.push({
                        value: '',
                        fragmentId: null,
                        mergedFrom: logicalColumnIndex + 1,
                        verticalMerge
                    });
                }
            });
            return logicalCells;
        });
        const maxColumns = Math.max(...rawRows.map(row => row.length), 1);
        const firstRowValues = rawRows[0].map(cell => cell.value);
        const headerLooksUseful = firstRowValues.filter(Boolean).length >= Math.min(2, maxColumns)
            && new Set(firstRowValues.map(normalizeKey).filter(Boolean)).size === firstRowValues.filter(Boolean).length;
        const headers = Array.from({ length: maxColumns }, (_, columnIndex) => (
            headerLooksUseful && firstRowValues[columnIndex]
                ? firstRowValues[columnIndex]
                : `Kolom ${columnIndex + 1}`
        ));
        const dataRows = headerLooksUseful ? rawRows.slice(1) : rawRows;
        return {
            id: `DOCX-TABLE-${globalTableIndex}`,
            title: `${docxPartTitle(partName)} — tabel ${localTableIndex}`,
            sourceRef: `docx:${partName}#table${localTableIndex}`,
            headers,
            headerRowNumber: headerLooksUseful ? 1 : null,
            headerFragmentRefs: headerLooksUseful
                ? Array.from({ length: maxColumns }, (_, index) => rawRows[0][index]?.fragmentId || null)
                : [],
            preamble: [],
            rows: dataRows.map((row, rowIndex) => ({
                rowNumber: rowIndex + (headerLooksUseful ? 2 : 1),
                values: Array.from({ length: maxColumns }, (_, columnIndex) => row[columnIndex]?.value || ''),
                fragmentRefs: Array.from({ length: maxColumns }, (_, columnIndex) => row[columnIndex]?.fragmentId || null)
            })),
            meta: {
                derivedFromCells: true,
                part: partName,
                headerDetected: headerLooksUseful,
                mergedCellsPreserved: rawRows.some(row => row.some(cell => (
                    cell.gridSpan > 1 || cell.verticalMerge
                )))
            }
        };
    }

    async function readDocxProperties(zip, extraction) {
        const propertyFiles = ['docProps/core.xml', 'docProps/app.xml', 'docProps/custom.xml'];
        const properties = {};
        for (const fileName of propertyFiles) {
            const entry = zip.file(fileName);
            if (!entry) continue;
            try {
                const xml = await entry.async('text');
                const documentNode = new DOMParser().parseFromString(xml, 'application/xml');
                const values = {};
                directElementChildren(documentNode.documentElement).forEach(child => {
                    const value = normalizeSpace(child.textContent);
                    if (!value) return;
                    const key = child.getAttribute('name') || child.localName;
                    values[key] = value;
                    addFragment(
                        extraction,
                        'document-property',
                        `${key}: ${value}`,
                        `docx:${fileName}#${key}`,
                        { key, value }
                    );
                });
                properties[fileName] = values;
            } catch (error) {
                addWarning(
                    extraction,
                    'docx_property_audit_failed',
                    `Properti ${fileName} gagal dibaca: ${error.message}`,
                    'warning',
                    `docx:${fileName}`
                );
            }
        }
        extraction.properties.documentProperties = properties;
    }

    function isOcrImageName(name) {
        return /\.(png|jpe?g|webp|bmp|tiff?)$/i.test(name);
    }

    function readRasterDimensions(bytes, fileName = '') {
        const lowerName = String(fileName).toLowerCase();
        if (
            bytes.length >= 24
            && bytes[0] === 0x89
            && bytes[1] === 0x50
            && bytes[2] === 0x4e
            && bytes[3] === 0x47
            && String.fromCharCode(...bytes.subarray(12, 16)) === 'IHDR'
        ) {
            const view = new DataView(bytes.buffer, bytes.byteOffset, bytes.byteLength);
            return {
                width: view.getUint32(16, false),
                height: view.getUint32(20, false),
                format: 'png'
            };
        }
        if (bytes.length >= 4 && bytes[0] === 0xff && bytes[1] === 0xd8) {
            const startOfFrameMarkers = new Set([
                0xc0, 0xc1, 0xc2, 0xc3, 0xc5, 0xc6, 0xc7,
                0xc9, 0xca, 0xcb, 0xcd, 0xce, 0xcf
            ]);
            let offset = 2;
            while (offset + 3 < bytes.length) {
                while (offset < bytes.length && bytes[offset] !== 0xff) offset += 1;
                while (offset < bytes.length && bytes[offset] === 0xff) offset += 1;
                if (offset >= bytes.length) break;
                const marker = bytes[offset];
                offset += 1;
                if (marker === 0xd9 || marker === 0xda) break;
                if (marker === 0x01 || (marker >= 0xd0 && marker <= 0xd7)) continue;
                if (offset + 2 > bytes.length) break;
                const segmentLength = bytes[offset] * 0x100 + bytes[offset + 1];
                if (segmentLength < 2 || offset + segmentLength > bytes.length) break;
                if (startOfFrameMarkers.has(marker) && segmentLength >= 7) {
                    return {
                        width: bytes[offset + 5] * 0x100 + bytes[offset + 6],
                        height: bytes[offset + 3] * 0x100 + bytes[offset + 4],
                        format: 'jpeg'
                    };
                }
                offset += segmentLength;
            }
        }
        const ascii = (offset, length) => (
            offset + length <= bytes.length
                ? String.fromCharCode(...bytes.subarray(offset, offset + length))
                : ''
        );
        if (bytes.length >= 30 && ascii(0, 4) === 'RIFF' && ascii(8, 4) === 'WEBP') {
            const chunk = ascii(12, 4);
            if (chunk === 'VP8X' && bytes.length >= 30) {
                return {
                    width: 1 + bytes[24] + bytes[25] * 0x100 + bytes[26] * 0x10000,
                    height: 1 + bytes[27] + bytes[28] * 0x100 + bytes[29] * 0x10000,
                    format: 'webp'
                };
            }
            if (
                chunk === 'VP8 '
                && bytes.length >= 30
                && bytes[23] === 0x9d
                && bytes[24] === 0x01
                && bytes[25] === 0x2a
            ) {
                return {
                    width: (bytes[26] + bytes[27] * 0x100) & 0x3fff,
                    height: (bytes[28] + bytes[29] * 0x100) & 0x3fff,
                    format: 'webp'
                };
            }
            if (chunk === 'VP8L' && bytes.length >= 25 && bytes[20] === 0x2f) {
                return {
                    width: 1 + bytes[21] + ((bytes[22] & 0x3f) << 8),
                    height: 1 + (bytes[22] >> 6) + (bytes[23] << 2) + ((bytes[24] & 0x0f) << 10),
                    format: 'webp'
                };
            }
        }
        if (/\.(png|jpe?g|webp)$/i.test(lowerName)) return null;
        return null;
    }

    function assertSafeRasterBytes(bytes, fileName) {
        const dimensions = readRasterDimensions(bytes, fileName);
        if (!dimensions || !dimensions.width || !dimensions.height) {
            throw new Error(`Header dimensi raster ${fileName} tidak valid atau tidak didukung.`);
        }
        const pixels = dimensions.width * dimensions.height;
        if (
            dimensions.width > MAX_RASTER_DIMENSION
            || dimensions.height > MAX_RASTER_DIMENSION
            || !Number.isSafeInteger(pixels)
            || pixels > MAX_RASTER_PIXELS
        ) {
            throw new Error(
                `Raster ${fileName} berukuran ${dimensions.width} x ${dimensions.height} piksel; `
                + `batas aman ${MAX_RASTER_DIMENSION} piksel per sisi dan `
                + `${MAX_RASTER_PIXELS.toLocaleString('id-ID')} piksel total.`
            );
        }
        return dimensions;
    }

    async function assertSafeRasterBlob(blob, fileName) {
        return assertSafeRasterBytes(new Uint8Array(await blob.arrayBuffer()), fileName);
    }

    function pdfDeadlineError(label) {
        const error = new Error(`${label} melewati batas waktu aman PDF.`);
        error.code = 'pdf_deadline';
        return error;
    }

    function withPdfDeadline(promise, deadline, label, cancel) {
        const remaining = deadline - Date.now();
        if (remaining <= 0) {
            const error = pdfDeadlineError(label);
            try { cancel?.(error); } catch (unused) {}
            return Promise.reject(error);
        }
        return new Promise((resolve, reject) => {
            const timer = setTimeout(() => {
                const error = pdfDeadlineError(label);
                try { cancel?.(error); } catch (unused) {}
                reject(error);
            }, remaining);
            Promise.resolve(promise).then(
                value => {
                    clearTimeout(timer);
                    resolve(value);
                },
                error => {
                    clearTimeout(timer);
                    reject(error);
                }
            );
        });
    }

    async function readPdfTextItems(page, pageNumber, deadline, destroyDocument) {
        if (typeof page.streamTextContent !== 'function') {
            throw new Error('PDF.js tidak menyediakan streaming text layer yang dibutuhkan untuk batas memori.');
        }
        const reader = page.streamTextContent({
            includeMarkedContent: false,
            disableNormalization: false
        }).getReader();
        const items = [];
        let characters = 0;
        let completed = false;
        try {
            while (true) {
                const chunk = await withPdfDeadline(
                    reader.read(),
                    deadline,
                    `Text layer halaman ${pageNumber}`,
                    error => {
                        void reader.cancel(error).catch(() => {});
                        destroyDocument();
                    }
                );
                if (chunk.done) {
                    completed = true;
                    break;
                }
                const chunkItems = Array.isArray(chunk.value?.items) ? chunk.value.items : [];
                const chunkCharacters = chunkItems.reduce(
                    (sum, item) => sum + (typeof item?.str === 'string' ? item.str.length : 0),
                    0
                );
                if (
                    items.length + chunkItems.length > MAX_PDF_TEXT_ITEMS_PER_PAGE
                    || characters + chunkCharacters > MAX_PDF_TEXT_CHARACTERS_PER_PAGE
                ) {
                    const error = new Error(
                        `Text layer halaman ${pageNumber} melewati batas `
                        + `${MAX_PDF_TEXT_ITEMS_PER_PAGE.toLocaleString('id-ID')} item / `
                        + `${MAX_PDF_TEXT_CHARACTERS_PER_PAGE / 1024 / 1024} MiB karakter.`
                    );
                    error.code = 'pdf_text_limit';
                    await reader.cancel(error).catch(() => {});
                    throw error;
                }
                items.push(...chunkItems);
                characters += chunkCharacters;
            }
        } finally {
            if (!completed) {
                await reader.cancel(new Error('Pembacaan text layer dihentikan.')).catch(() => {});
            }
            try { reader.releaseLock(); } catch (unused) {}
        }
        return items;
    }

    async function extractPdf(buffer, source, options, onProgress) {
        if (!global.pdfjsLib) throw new Error('PDF.js tidak tersedia.');
        global.pdfjsLib.GlobalWorkerOptions.workerSrc = assetUrl(
            'scripts/vendor/pdf-3.11.174.worker.min.js'
        );
        const extraction = createExtraction('pdf', 'PDF.js 3.11.174 coordinate adapter + Tesseract OCR');
        const deadline = Date.now() + MAX_PDF_TASK_MS;
        let documentDestroyed = false;
        const loadingTask = global.pdfjsLib.getDocument({
            data: new Uint8Array(buffer),
            useSystemFonts: true,
            isEvalSupported: false,
            verbosity: 0
        });
        const destroyDocument = () => {
            if (documentDestroyed) return;
            documentDestroyed = true;
            void loadingTask.destroy().catch(() => {});
        };
        const pdf = await withPdfDeadline(
            loadingTask.promise,
            deadline,
            'Membuka dokumen PDF',
            destroyDocument
        );
        const pageTotal = pdf.numPages;
        if (pageTotal > MAX_PDF_PAGES) {
            await pdf.destroy().catch(() => {});
            throw new Error(`PDF memiliki ${pageTotal} halaman; batas aman ${MAX_PDF_PAGES} halaman per file.`);
        }
        extraction.stats.pagesExpected = pageTotal;
        let pageFailureCount = 0;
        let nativeTextPages = 0;
        let ocrPages = 0;
        let ocrRequiredPages = 0;
        let ocrFailedPages = 0;
        let ocrEmptyPages = 0;
        const fullyExtractedPages = new Set();
        const ocr = new OcrSession(onProgress);

        try {
            const metadata = await withPdfDeadline(
                pdf.getMetadata(),
                deadline,
                'Metadata PDF',
                destroyDocument
            ).catch(error => {
                if (error?.code === 'pdf_deadline') throw error;
                return null;
            });
            extraction.properties.pdfInfo = serializableObject(metadata?.info || {});
            extraction.properties.pdfMetadata = metadata?.metadata?.getAll?.() || {};
            Object.entries(extraction.properties.pdfInfo).forEach(([key, value]) => {
                if (value == null || value === '') return;
                addFragment(
                    extraction,
                    'document-property',
                    `${key}: ${String(value)}`,
                    `pdf:metadata#${key}`,
                    { key, value: serializableObject(value) }
                );
            });

            extraction.stats.pdfInteractiveAudit = 'skipped-memory-safety';
            addWarning(
                extraction,
                'pdf_interactive_objects_skipped',
                'Outline, anotasi, field interaktif, dan lampiran PDF tidak dimuat karena PDF.js 3.11 dapat '
                + 'mengembangkan isi lampiran saat objek tersebut dibaca. Teks halaman dan OCR tetap diproses; '
                + 'ekspor field/lampiran secara terpisah bila dokumen menggunakannya.',
                'warning',
                'pdf:interactive-objects'
            );

            for (let pageNumber = 1; pageNumber <= pageTotal; pageNumber += 1) {
                let page = null;
                onProgress?.({
                    stage: 'pdf-page',
                    current: pageNumber,
                    total: pdf.numPages,
                    message: `Membaca halaman ${pageNumber} dari ${pdf.numPages}`
                });
                try {
                    page = await withPdfDeadline(
                        pdf.getPage(pageNumber),
                        deadline,
                        `Membuka halaman ${pageNumber}`,
                        destroyDocument
                    );
                    const textItems = await readPdfTextItems(
                        page,
                        pageNumber,
                        deadline,
                        destroyDocument
                    );
                    const lines = pdfItemsToLines(textItems);
                    const nativeText = normalizeSpace(lines.map(line => line.text).join('\n'));
                    const garbledRatio = nativeText
                        ? (nativeText.match(/[\uFFFD□�]/g) || []).length / nativeText.length
                        : 1;
                    const nativeReadable = nativeText.length >= 30 && garbledRatio < 0.08;
                    if (nativeReadable) nativeTextPages += 1;

                    const section = {
                        id: `PDF-PAGE-${pageNumber}`,
                        title: `Halaman ${pageNumber}`,
                        kind: 'pdf-page',
                        sourceRef: `pdf:p${pageNumber}`,
                        fragmentRefs: [],
                        meta: {
                            pageNumber,
                            nativeCharacters: nativeText.length,
                            garbledRatio
                        }
                    };
                    extraction.sections.push(section);
                    lines.forEach((line, lineIndex) => {
                        const fragmentId = addFragment(
                            extraction,
                            'paragraph',
                            line.text,
                            `pdf:p${pageNumber}:line${lineIndex + 1}`,
                            { page: pageNumber, bbox: line.bbox, cells: line.cells }
                        );
                        if (fragmentId) {
                            line.fragmentId = fragmentId;
                            section.fragmentRefs.push(fragmentId);
                        }
                    });
                    pdfLinesToTables(lines, pageNumber).forEach(table => extraction.tables.push(table));
                    section.meta.interactiveObjectAudit = 'skipped-memory-safety';
                    section.meta.imageObjectAudit = 'skipped-memory-safety';
                    const visualOcrRequired = !nativeReadable || nativeText.length < 500;
                    const needsOcr = options.ocrMode === 'all'
                        || (options.ocrMode === 'auto' && visualOcrRequired);
                    if (needsOcr) {
                        try {
                            const ocrResult = await ocrPdfPageTiled(
                                page,
                                pageNumber,
                                ocr,
                                options,
                                onProgress,
                                deadline,
                                destroyDocument
                            );
                            section.meta.ocrTileFailures = ocrResult.tileFailures.length;
                            if (ocrResult.tileFailures.length) {
                                ocrFailedPages += 1;
                                addWarning(
                                    extraction,
                                    'pdf_ocr_tile_failed',
                                    `${ocrResult.tileFailures.length} dari ${ocrResult.tiles} tile OCR halaman ${pageNumber} gagal; hasil tile lain tetap dipertahankan.`,
                                    'error',
                                    `pdf:p${pageNumber}`
                                );
                            }
                            if (ocrResult.lines.length) {
                                ocrResult.lines.forEach((line, lineIndex) => {
                                    const fragmentId = addFragment(
                                        extraction,
                                        'ocr-text',
                                        line.text,
                                        line.sourceRef || `pdf:p${pageNumber}:ocr-line${lineIndex + 1}`,
                                        {
                                            page: pageNumber,
                                            confidence: line.confidence,
                                            nativeTextCharacters: nativeText.length,
                                            bbox: line.bbox,
                                            cells: line.cells,
                                            tile: line.tile
                                        }
                                    );
                                    if (fragmentId) {
                                        line.fragmentId = fragmentId;
                                        section.fragmentRefs.push(fragmentId);
                                    }
                                });
                                pdfLinesToTables(ocrResult.lines, pageNumber, 'OCR')
                                    .forEach(table => extraction.tables.push(table));
                                section.meta.ocrTiles = ocrResult.tiles;
                                section.meta.ocrScale = ocrResult.scale;
                                section.meta.ocrCharacters = ocrResult.lines.reduce(
                                    (sum, line) => sum + line.text.length,
                                    0
                                );
                                const ocrConfidence = ocrResult.lines.reduce(
                                    (sum, line) => sum + Number(line.confidence || 0),
                                    0
                                ) / ocrResult.lines.length;
                                section.meta.ocrConfidence = ocrConfidence;
                                if (ocrConfidence < 55) {
                                    addWarning(
                                        extraction,
                                        'low_ocr_confidence',
                                        `Confidence OCR halaman ${pageNumber} hanya ${Math.round(ocrConfidence)}%; verifikasi nilai penting dan tabel.`,
                                        'warning',
                                        `pdf:p${pageNumber}`
                                    );
                                }
                                ocrPages += 1;
                                if (!ocrResult.tileFailures.length) fullyExtractedPages.add(pageNumber);
                            } else {
                                ocrEmptyPages += 1;
                                addWarning(
                                    extraction,
                                    'pdf_ocr_empty',
                                    `OCR halaman ${pageNumber} tidak menemukan teks.`,
                                    visualOcrRequired ? 'error' : 'warning',
                                    `pdf:p${pageNumber}`
                                );
                                if (!visualOcrRequired && nativeReadable) fullyExtractedPages.add(pageNumber);
                            }
                        } catch (error) {
                            if (error?.code === 'pdf_deadline') throw error;
                            ocrFailedPages += 1;
                            addWarning(
                                extraction,
                                'pdf_ocr_failed',
                                `OCR halaman ${pageNumber} gagal: ${error.message}`,
                                'error',
                                `pdf:p${pageNumber}`
                            );
                            if (!visualOcrRequired && nativeReadable) fullyExtractedPages.add(pageNumber);
                        }
                    } else if (options.ocrMode === 'off' && visualOcrRequired) {
                        ocrRequiredPages += 1;
                        addWarning(
                            extraction,
                            'pdf_ocr_required',
                            `Halaman ${pageNumber} tidak memiliki text layer yang memadai; aktifkan OCR.`,
                            'error',
                            `pdf:p${pageNumber}`
                        );
                    } else if (nativeReadable) {
                        fullyExtractedPages.add(pageNumber);
                    }
                } catch (error) {
                    if (error?.code === 'pdf_deadline') throw error;
                    pageFailureCount += 1;
                    addWarning(
                        extraction,
                        'pdf_page_failed',
                        `Halaman ${pageNumber} gagal diproses: ${error.message}`,
                        'error',
                        `pdf:p${pageNumber}`
                    );
                } finally {
                    page?.cleanup?.();
                }
            }
        } finally {
            await ocr.terminate();
            await pdf.cleanup().catch(() => {});
            await pdf.destroy().catch(() => {});
        }

        extraction.stats.pagesProcessed = pageTotal - pageFailureCount;
        extraction.stats.pagesFailed = pageFailureCount;
        extraction.stats.pagesFullyExtracted = fullyExtractedPages.size;
        extraction.stats.nativeTextPages = nativeTextPages;
        extraction.stats.ocrPages = ocrPages;
        extraction.stats.ocrRequiredPages = ocrRequiredPages;
        extraction.stats.ocrFailedPages = ocrFailedPages;
        extraction.stats.ocrEmptyPages = ocrEmptyPages;
        extraction.stats.imageObjects = null;
        extraction.stats.annotations = null;
        extraction.stats.formFields = null;
        extraction.stats.interactiveObjectAudit = 'skipped-memory-safety';
        extraction.stats.imageObjectAudit = 'skipped-memory-safety';
        if (pageFailureCount) {
            addWarning(
                extraction,
                'pdf_incomplete_page_coverage',
                `${pageFailureCount} dari ${pageTotal} halaman gagal diproses.`,
                'error',
                'pdf'
            );
        }
        return finalizeExtraction(extraction);
    }

    function serializableObject(value) {
        if (value instanceof Date) return value.toISOString();
        if (Array.isArray(value)) return value.map(serializableObject);
        if (value && typeof value === 'object') {
            return Object.fromEntries(
                Object.entries(value).map(([key, child]) => [key, serializableObject(child)])
            );
        }
        return value;
    }

    function flattenPdfOutline(items, depth = 0, output = []) {
        items.forEach(item => {
            if (item.title) output.push({ title: item.title, depth });
            if (item.items?.length) flattenPdfOutline(item.items, depth + 1, output);
        });
        return output;
    }

    function pdfItemsToLines(items) {
        const textItems = items
            .map((item, index) => ({
                text: normalizeSpace(item.str || ''),
                x: Number(item.transform?.[4] || 0),
                y: Number(item.transform?.[5] || 0),
                width: Number(item.width || 0),
                height: Math.abs(Number(item.height || item.transform?.[3] || 10)),
                index,
                hasEOL: Boolean(item.hasEOL)
            }))
            .filter(item => item.text);
        const groups = [];
        textItems.forEach(item => {
            let group = groups.find(candidate => Math.abs(candidate.y - item.y) <= Math.max(2, item.height * 0.22));
            if (!group) {
                group = { y: item.y, items: [], firstIndex: item.index };
                groups.push(group);
            }
            group.items.push(item);
            group.firstIndex = Math.min(group.firstIndex, item.index);
        });
        groups.sort((left, right) => right.y - left.y || left.firstIndex - right.firstIndex);
        return groups.map(group => {
            const sorted = group.items.sort((left, right) => left.x - right.x || left.index - right.index);
            const cells = [];
            let current = '';
            let previousEnd = null;
            sorted.forEach(item => {
                const averageCharacter = item.text.length ? item.width / item.text.length : item.height * 0.5;
                const gap = previousEnd == null ? 0 : item.x - previousEnd;
                const splitThreshold = Math.max(14, item.height * 1.35, averageCharacter * 3.2);
                if (current && gap > splitThreshold) {
                    cells.push(normalizeSpace(current));
                    current = item.text;
                } else {
                    current += `${current ? ' ' : ''}${item.text}`;
                }
                previousEnd = Math.max(previousEnd == null ? item.x : previousEnd, item.x + item.width);
            });
            if (current) cells.push(normalizeSpace(current));
            const minimumX = Math.min(...sorted.map(item => item.x));
            const maximumX = Math.max(...sorted.map(item => item.x + item.width));
            const height = Math.max(...sorted.map(item => item.height));
            return {
                text: normalizeSpace(cells.join(' | ')),
                cells,
                bbox: [minimumX, group.y - height, maximumX, group.y],
                fragmentId: null
            };
        }).filter(line => line.text);
    }

    function pdfLinesToTables(lines, pageNumber, sourceType = 'NATIVE') {
        const blocks = [];
        let current = [];
        lines.forEach(line => {
            if (line.cells.length >= 2) {
                current.push(line);
            } else if (current.length) {
                blocks.push(current);
                current = [];
            }
        });
        if (current.length) blocks.push(current);
        return blocks.filter(block => block.length >= 2).map((block, blockIndex) => {
            const maxColumns = Math.max(...block.map(line => line.cells.length));
            const headers = Array.from({ length: maxColumns }, (_, index) => (
                block[0].cells[index] || `Kolom ${index + 1}`
            ));
            return {
                id: `PDF-${sourceType}-TABLE-${pageNumber}-${blockIndex + 1}`,
                title: `Halaman ${pageNumber} — tabel ${sourceType === 'OCR' ? 'OCR' : 'text layer'} ${blockIndex + 1}`,
                sourceRef: `pdf:p${pageNumber}:${sourceType.toLowerCase()}-table${blockIndex + 1}`,
                headers,
                headerRowNumber: 1,
                headerFragmentRefs: Array.from({ length: maxColumns }, () => block[0].fragmentId),
                preamble: [],
                rows: block.slice(1).map((line, rowIndex) => ({
                    rowNumber: rowIndex + 2,
                    values: Array.from({ length: maxColumns }, (_, columnIndex) => line.cells[columnIndex] || ''),
                    fragmentRefs: Array.from({ length: maxColumns }, () => line.fragmentId)
                })),
                meta: {
                    derivedFromCoordinates: true,
                    reviewRequired: true,
                    page: pageNumber,
                    sourceType
                }
            };
        });
    }

    async function ocrPdfPageTiled(
        page,
        pageNumber,
        ocr,
        options,
        onProgress,
        deadline,
        destroyDocument
    ) {
        const baseViewport = page.getViewport({ scale: 1 });
        const longestBaseSide = Math.max(baseViewport.width, baseViewport.height);
        const targetLongSide = options.ocrMode === 'all' ? 12000 : 10000;
        const scale = clamp(
            targetLongSide / Math.max(longestBaseSide, 1),
            Math.max(3, Number(options.pdfScale || 2)),
            14
        );
        const viewport = page.getViewport({ scale });
        const tileSize = options.ocrMode === 'all' ? 2600 : 2400;
        const overlap = 120;
        const step = tileSize - overlap;
        const columns = Math.max(1, Math.ceil((viewport.width - overlap) / step));
        const rows = Math.max(1, Math.ceil((viewport.height - overlap) / step));
        const totalTiles = columns * rows;
        if (totalTiles > MAX_OCR_TILES_PER_PAGE) {
            throw new Error(
                `Halaman ${pageNumber} membutuhkan ${totalTiles} tile OCR; `
                + `batas aman ${MAX_OCR_TILES_PER_PAGE}.`
            );
        }
        const lines = [];
        const tileFailures = [];
        let tileNumber = 0;

        for (let row = 0; row < rows; row += 1) {
            for (let column = 0; column < columns; column += 1) {
                tileNumber += 1;
                const left = Math.round(column * step);
                const top = Math.round(row * step);
                const width = Math.max(1, Math.min(tileSize, Math.ceil(viewport.width - left)));
                const height = Math.max(1, Math.min(tileSize, Math.ceil(viewport.height - top)));
                onProgress?.({
                    stage: 'pdf-ocr-tile',
                    current: tileNumber,
                    total: totalTiles,
                    message: `OCR halaman ${pageNumber}, tile ${tileNumber}/${totalTiles}`
                });
                const canvas = document.createElement('canvas');
                try {
                    canvas.width = width;
                    canvas.height = height;
                    const context = canvas.getContext('2d', { alpha: false, willReadFrequently: true });
                    if (!context) throw new Error('Canvas 2D tidak tersedia.');
                    context.fillStyle = '#fff';
                    context.fillRect(0, 0, width, height);
                    const renderTask = page.render({
                        canvasContext: context,
                        viewport,
                        transform: [1, 0, 0, 1, -left, -top],
                        annotationMode: global.pdfjsLib.AnnotationMode.DISABLE
                    });
                    await withPdfDeadline(
                        renderTask.promise,
                        deadline,
                        `Render PDF halaman ${pageNumber}`,
                        error => {
                            renderTask.cancel();
                            destroyDocument(error);
                        }
                    );
                    const sourceRef = `pdf:p${pageNumber}:ocr@${left},${top},${width},${height}`;
                    const result = await withPdfDeadline(
                        ocr.recognize(canvas, sourceRef),
                        deadline,
                        `OCR PDF halaman ${pageNumber}`,
                        destroyDocument
                    );
                    const tileLines = ocrWordsToLines(result.words, {
                        left,
                        top,
                        width,
                        height,
                        sourceRef,
                        fallbackText: result.text,
                        confidence: result.confidence
                    });
                    lines.push(...tileLines);
                } catch (error) {
                    if (error?.code === 'pdf_deadline') throw error;
                    tileFailures.push({
                        tile: tileNumber,
                        left,
                        top,
                        width,
                        height,
                        error: error.message
                    });
                } finally {
                    canvas.width = 1;
                    canvas.height = 1;
                }
            }
        }
        return {
            lines: deduplicateOcrLines(lines),
            tiles: totalTiles,
            tileFailures,
            scale
        };
    }

    function ocrWordsToLines(words, tile) {
        if (!words?.length) {
            const fallbackLines = String(tile.fallbackText || '')
                .split(/\n/)
                .map(normalizeSpace)
                .filter(Boolean);
            const safeWidth = Math.max(1, Number(tile.width || 0));
            const safeHeight = Math.max(1, Number(tile.height || 0));
            const lineHeight = safeHeight / Math.max(1, fallbackLines.length);
            return fallbackLines.map((text, index) => ({
                text,
                cells: [text],
                bbox: [
                    Number(tile.left || 0),
                    Number(tile.top || 0) + index * lineHeight,
                    Number(tile.left || 0) + safeWidth,
                    Number(tile.top || 0) + Math.min(safeHeight, (index + 1) * lineHeight)
                ],
                confidence: tile.confidence,
                sourceRef: `${tile.sourceRef}:line${index + 1}`,
                tile
            }));
        }
        const groups = new Map();
        words.forEach(word => {
            const key = `${word.block}-${word.paragraph}-${word.line}`;
            if (!groups.has(key)) groups.set(key, []);
            groups.get(key).push(word);
        });
        return [...groups.values()].map((lineWords, lineIndex) => {
            const sorted = lineWords.sort((left, right) => left.left - right.left || left.word - right.word);
            const cells = [];
            let current = '';
            let previousEnd = null;
            sorted.forEach(word => {
                const gap = previousEnd == null ? 0 : word.left - previousEnd;
                const averageCharacter = word.text.length ? word.width / word.text.length : word.height * 0.5;
                const splitThreshold = Math.max(32, word.height * 1.7, averageCharacter * 4);
                if (current && gap > splitThreshold) {
                    cells.push(normalizeSpace(current));
                    current = word.text;
                } else {
                    current += `${current ? ' ' : ''}${word.text}`;
                }
                previousEnd = word.left + word.width;
            });
            if (current) cells.push(normalizeSpace(current));
            const left = Math.min(...sorted.map(word => word.left)) + tile.left;
            const top = Math.min(...sorted.map(word => word.top)) + tile.top;
            const right = Math.max(...sorted.map(word => word.left + word.width)) + tile.left;
            const bottom = Math.max(...sorted.map(word => word.top + word.height)) + tile.top;
            const confidence = sorted.reduce((sum, word) => sum + word.confidence, 0) / sorted.length;
            return {
                text: normalizeSpace(cells.join(' | ')),
                cells,
                bbox: [left, top, right, bottom],
                confidence,
                sourceRef: `${tile.sourceRef}:line${lineIndex + 1}`,
                tile: {
                    left: tile.left,
                    top: tile.top,
                    width: tile.width,
                    height: tile.height
                }
            };
        }).filter(line => line.text);
    }

    function deduplicateOcrLines(lines) {
        const output = [];
        lines
            .sort((left, right) => left.bbox[1] - right.bbox[1] || left.bbox[0] - right.bbox[0])
            .forEach(line => {
                const normalized = normalizeKey(line.text);
                if (!normalized) return;
                const duplicate = output.find(existing => {
                    const sameText = normalizeKey(existing.text) === normalized;
                    const closeY = Math.abs(existing.bbox[1] - line.bbox[1]) < 80;
                    const overlapX = Math.min(existing.bbox[2], line.bbox[2])
                        - Math.max(existing.bbox[0], line.bbox[0]);
                    return sameText && closeY && overlapX > -100;
                });
                if (!duplicate) output.push(line);
            });
        return output;
    }

    async function extractImage(file, source, options, onProgress, bytes) {
        const extraction = createExtraction(
            source.extension.replace('.', '') || 'image',
            'Tesseract OCR image adapter'
        );
        extraction.artifacts.push({
            kind: 'source-image',
            name: source.fileName,
            size: source.size,
            sourceRef: source.relativePath || source.fileName
        });
        const dimensions = assertSafeRasterBytes(bytes, source.fileName);
        extraction.stats.rasterWidth = dimensions.width;
        extraction.stats.rasterHeight = dimensions.height;
        extraction.stats.rasterPixels = dimensions.width * dimensions.height;
        if (options.ocrMode === 'off') {
            addWarning(
                extraction,
                'image_ocr_disabled',
                'File gambar dicatat tetapi teks belum dibaca karena OCR dinonaktifkan.',
                'error',
                source.fileName
            );
            return extraction;
        }

        const ocr = new OcrSession(onProgress);
        try {
            const result = await ocr.recognize(file, `image:${source.fileName}:ocr`);
            if (result.text) {
                const imageWidth = Math.max(dimensions.width, result.words.reduce(
                    (maximum, word) => Math.max(maximum, Number(word.left || 0) + Number(word.width || 0)),
                    1
                ));
                const imageHeight = Math.max(dimensions.height, result.words.reduce(
                    (maximum, word) => Math.max(maximum, Number(word.top || 0) + Number(word.height || 0)),
                    1
                ));
                const lines = ocrWordsToLines(result.words, {
                    left: 0,
                    top: 0,
                    width: imageWidth,
                    height: imageHeight,
                    sourceRef: result.sourceRef,
                    fallbackText: result.text,
                    confidence: result.confidence
                });
                const fragmentRefs = lines.map(line => addFragment(
                    extraction,
                    'ocr-text',
                    line.text,
                    line.sourceRef,
                    {
                        confidence: line.confidence,
                        bbox: line.bbox,
                        cells: line.cells
                    }
                )).filter(Boolean);
                extraction.sections.push({
                    id: 'IMAGE-OCR',
                    title: 'Hasil OCR gambar',
                    kind: 'image-ocr',
                    sourceRef: result.sourceRef,
                    fragmentRefs,
                    meta: {
                        confidence: result.confidence,
                        width: imageWidth,
                        height: imageHeight,
                        lines: lines.length
                    }
                });
                if (result.confidence < 55) {
                    addWarning(
                        extraction,
                        'low_ocr_confidence',
                        `Confidence OCR gambar hanya ${Math.round(result.confidence)}%; verifikasi nomor, jam, dan nama.`,
                        'warning',
                        source.fileName
                    );
                }
                extraction.stats.ocrImages = 1;
            } else {
                addWarning(
                    extraction,
                    'image_ocr_empty',
                    'OCR selesai tetapi tidak menemukan teks pada gambar.',
                    'error',
                    source.fileName
                );
            }
        } catch (error) {
            addWarning(
                extraction,
                'image_ocr_failed',
                `OCR gambar gagal: ${error.message}`,
                'error',
                source.fileName
            );
        } finally {
            await ocr.terminate();
        }
        return finalizeExtraction(extraction);
    }

    async function appendSpreadsheetMediaOcr(buffer, extraction, options, onProgress) {
        if (!global.JSZip) return;
        let zip;
        try {
            const rawZipAudit = global.FleetZipPreflight.inspect(
                new Uint8Array(buffer),
                'Spreadsheet media',
                {
                    maxEntries: MAX_ZIP_ENTRIES,
                    maxEntryBytes: MAX_ZIP_ENTRY_BYTES,
                    maxUncompressedBytes: MAX_ZIP_UNCOMPRESSED_BYTES
                }
            );
            zip = await global.JSZip.loadAsync(buffer);
            global.FleetZipPreflight.verifyJsZip(zip, rawZipAudit, 'Spreadsheet media');
            validateZipEntries(
                Object.values(zip.files).filter(entry => !entry.dir),
                'Spreadsheet media'
            );
        } catch (error) {
            return;
        }
        const mediaEntries = Object.values(zip.files).filter(entry => (
            !entry.dir && /^xl\/media\//i.test(entry.name) && isOcrImageName(entry.name)
        ));
        if (!mediaEntries.length) return;
        const ocr = new OcrSession(onProgress);
        let recognized = 0;
        try {
            for (let index = 0; index < mediaEntries.length; index += 1) {
                const entry = mediaEntries[index];
                onProgress?.({
                    stage: 'spreadsheet-media-ocr',
                    current: index + 1,
                    total: mediaEntries.length,
                    message: `OCR gambar workbook ${entry.name}`
                });
                try {
                    const blob = await entry.async('blob');
                    await assertSafeRasterBlob(blob, entry.name);
                    const result = await ocr.recognize(blob, `xlsx-media:${entry.name}:ocr`);
                    if (!result.text) continue;
                    addFragment(
                        extraction,
                        'ocr-text',
                        result.text,
                        result.sourceRef,
                        { confidence: result.confidence, mediaName: entry.name }
                    );
                    if (result.confidence < 55) {
                        addWarning(
                            extraction,
                            'low_ocr_confidence',
                            `Confidence OCR ${entry.name} hanya ${Math.round(result.confidence)}%; verifikasi isi gambar.`,
                            'warning',
                            result.sourceRef
                        );
                    }
                    recognized += 1;
                } catch (error) {
                    addWarning(
                        extraction,
                        'spreadsheet_media_ocr_failed',
                        `OCR ${entry.name} gagal: ${error.message}`,
                        'warning',
                        `xlsx-media:${entry.name}`
                    );
                }
            }
        } finally {
            await ocr.terminate();
        }
        extraction.stats.ocrMedia = recognized;
        if (recognized) {
            extraction.warnings = extraction.warnings.filter(
                warning => warning.code !== 'spreadsheet_media_catalogued'
            );
        }
        finalizeExtraction(extraction);
    }

    function fieldAliases(item) {
        const keyAliases = FIELD_ALIASES[item.key] || [];
        return unique([
            item.label,
            item.key,
            String(item.key || '').replace(/_/g, ' '),
            ...keyAliases
        ]).map(normalizeKey).filter(Boolean);
    }

    function textSimilarity(leftValue, rightValue) {
        const left = normalizeKey(leftValue);
        const right = normalizeKey(rightValue);
        if (!left || !right) return 0;
        if (left === right) return 1;
        if (left.length >= 3 && right.includes(left)) return Math.min(0.94, 0.72 + left.length / right.length * 0.2);
        if (right.length >= 3 && left.includes(right)) return Math.min(0.92, 0.7 + right.length / left.length * 0.2);
        const leftTokens = new Set(tokens(left));
        const rightTokens = new Set(tokens(right));
        if (!leftTokens.size || !rightTokens.size) return 0;
        const intersection = [...leftTokens].filter(token => rightTokens.has(token)).length;
        const union = new Set([...leftTokens, ...rightTokens]).size;
        const jaccard = union ? intersection / union : 0;
        const prefixBonus = [...leftTokens].some(leftToken => (
            [...rightTokens].some(rightToken => (
                leftToken.length >= 3
                && rightToken.length >= 3
                && (leftToken.startsWith(rightToken) || rightToken.startsWith(leftToken))
            ))
        )) ? 0.12 : 0;
        return clamp(jaccard + prefixBonus);
    }

    function bestAliasScore(value, aliases) {
        return aliases.reduce(
            (maximum, alias) => Math.max(maximum, textSimilarity(value, alias)),
            0
        );
    }

    function classifySchemas(source, extraction, schemas, forcedSchemaId = '') {
        const normalizedFileName = normalizeKey(source.fileName);
        const tableHeaderText = extraction.tables
            .flatMap(table => table.headers || [])
            .slice(0, 2000)
            .join(' ');
        const sampleSize = Math.min(6000, extraction.fragments.length);
        const sampledFragments = Array.from({ length: sampleSize }, (_, index) => (
            extraction.fragments[Math.floor(index * extraction.fragments.length / Math.max(sampleSize, 1))]?.value || ''
        ));
        const sampledText = normalizeKey([
            source.fileName,
            tableHeaderText,
            ...sampledFragments
        ].join(' ').slice(0, 1800000));
        const results = schemas.map(schema => {
            const hints = unique([
                ...(SCHEMA_HINTS[schema.id] || []),
                schema.code,
                schema.title,
                String(schema.source || '').replace(/\.[^.]+$/, '').replace(/_/g, ' ')
            ]).map(normalizeKey).filter(value => value.length >= 2);
            const labels = [...(schema.fields || []), ...(schema.columns || [])]
                .flatMap(fieldAliases);
            const exactFileHints = hints.filter(hint => normalizedFileName.includes(hint));
            const bodyHints = hints.filter(hint => sampledText.includes(hint));
            const labelMatches = labels.filter(label => sampledText.includes(label));
            const fileTitleSimilarity = textSimilarity(normalizedFileName, schema.title);
            const sourceSimilarity = textSimilarity(
                normalizedFileName,
                String(schema.source || '').replace(/\.[^.]+$/, '')
            );
            const labelCoverage = labels.length
                ? unique(labelMatches).length / unique(labels).length
                : 0;
            let score = 0;
            if (exactFileHints.length) {
                score += Math.min(0.58, 0.42 + exactFileHints.length * 0.08);
            }
            if (bodyHints.length) score += Math.min(0.24, bodyHints.length * 0.07);
            score += fileTitleSimilarity * 0.2;
            score += sourceSimilarity * 0.24;
            score += Math.min(0.32, labelCoverage * 0.9);
            return {
                schemaId: schema.id,
                title: schema.title,
                code: schema.code,
                score: clamp(score),
                evidence: {
                    filenameHints: exactFileHints.slice(0, 8),
                    contentHints: bodyHints.slice(0, 8),
                    labelMatches: unique(labelMatches).slice(0, 20),
                    labelCoverage
                }
            };
        }).sort((left, right) => right.score - left.score);

        if (forcedSchemaId === '__none__') {
            return {
                selectedSchemaId: null,
                mode: 'manual',
                confidence: 'manual',
                score: 0,
                margin: 1,
                alternatives: results.slice(0, 5)
            };
        }
        if (forcedSchemaId) {
            const forced = results.find(result => result.schemaId === forcedSchemaId);
            return {
                selectedSchemaId: forced ? forcedSchemaId : null,
                mode: 'manual',
                confidence: forced ? 'manual' : 'none',
                score: forced?.score || 0,
                margin: 1,
                alternatives: results.slice(0, 5)
            };
        }

        const best = results[0];
        const second = results[1];
        const margin = best ? best.score - (second?.score || 0) : 0;
        const accepted = Boolean(best && best.score >= 0.32 && (margin >= 0.035 || best.score >= 0.72));
        const confidence = !accepted
            ? 'none'
            : best.score >= 0.74 && margin >= 0.1
                ? 'high'
                : best.score >= 0.52 && margin >= 0.055
                    ? 'medium'
                    : 'low';
        return {
            selectedSchemaId: accepted ? best.schemaId : null,
            mode: 'automatic',
            confidence,
            score: best?.score || 0,
            margin,
            alternatives: results.slice(0, 5)
        };
    }

    function collectLabelValueCandidates(extraction) {
        const candidates = [];
        let truncated = false;
        function pushCandidate(candidate) {
            if (candidates.length >= MAX_MAPPING_CANDIDATES) {
                truncated = true;
                return false;
            }
            candidates.push(candidate);
            return true;
        }
        extraction.tables.forEach(table => {
            const rows = [
                ...(table.preamble || []),
                ...(table.rows || [])
            ];
            rows.forEach(row => {
                const values = (row.values || []).map(value => normalizeSpace(value));
                const nonEmptyIndexes = values
                    .map((value, index) => ({ value, index }))
                    .filter(item => item.value);
                if (nonEmptyIndexes.length < 2) return;
                for (let offset = 0; offset < nonEmptyIndexes.length - 1; offset += 1) {
                    const labelCell = nonEmptyIndexes[offset];
                    const valueCell = nonEmptyIndexes[offset + 1];
                    if (labelCell.value.length > 120 || valueCell.value.length > 1200) continue;
                    pushCandidate({
                        label: labelCell.value,
                        value: valueCell.value,
                        sourceRefs: unique([
                            row.fragmentRefs?.[labelCell.index],
                            row.fragmentRefs?.[valueCell.index]
                        ]),
                        sourceRef: `${table.sourceRef}:r${row.rowNumber || offset + 1}`,
                        origin: 'table-pair',
                        baseConfidence: 0.88
                    });
                }
            });
        });

        const fragments = extraction.fragments;
        fragments.forEach((fragment, index) => {
            if (!['paragraph', 'form-field', 'annotation', 'ocr-text', 'cell'].includes(fragment.kind)) return;
            const lines = String(fragment.value || '').split(/\n/).map(normalizeSpace).filter(Boolean);
            lines.forEach(line => {
                const separatorMatch = line.match(/^(.{1,120}?)(?:\s*[:=]\s*|\s+\|\s+)(.{1,1200})$/);
                if (separatorMatch) {
                    pushCandidate({
                        label: separatorMatch[1],
                        value: separatorMatch[2],
                        sourceRefs: [fragment.id],
                        sourceRef: fragment.sourceRef,
                        origin: 'labelled-text',
                        baseConfidence: fragment.kind === 'ocr-text' ? 0.68 : 0.84
                    });
                }
            });
            const shortLabel = normalizeSpace(fragment.value);
            const next = fragments[index + 1];
            if (
                shortLabel
                && shortLabel.length <= 80
                && !/[:=]/.test(shortLabel)
                && next
                && normalizeSpace(next.value)
                && normalizeSpace(next.value).length <= 600
                && sourceBoundary(fragment.sourceRef) === sourceBoundary(next.sourceRef)
            ) {
                pushCandidate({
                    label: shortLabel,
                    value: normalizeSpace(next.value),
                    sourceRefs: [fragment.id, next.id],
                    sourceRef: fragment.sourceRef,
                    origin: 'adjacent-fragment',
                    baseConfidence: 0.62
                });
            }
        });
        return { candidates, truncated };
    }

    function sourceBoundary(sourceRef) {
        const value = String(sourceRef || '');
        return value.match(/^pdf:p\d+/i)?.[0]
            || value.match(/^xlsx:[^!]+/i)?.[0]
            || value.match(/^docx:[^#]+/i)?.[0]
            || value.match(/^doc:(?:table-\d+|paragraph-\d+)/i)?.[0]
            || value.match(/^image:[^:]+/i)?.[0]
            || value.split('#')[0]
            || value;
    }

    function normalizeMappedValue(rawValue, item) {
        const raw = normalizeSpace(rawValue);
        if (!raw || /^[-_.\s/]+$/.test(raw) || /_{2,}/.test(raw)) return '';
        if (item.type === 'number') return normalizeNumber(raw);
        if (item.type === 'date') return normalizeDate(raw);
        if (item.type === 'month') {
            const date = normalizeDate(raw);
            if (date) return date.slice(0, 7);
            const match = raw.match(/\b(20\d{2})[-/.](0?[1-9]|1[0-2])\b/);
            return match ? `${match[1]}-${String(match[2]).padStart(2, '0')}` : '';
        }
        if (item.type === 'time') {
            const match = raw.match(/\b([01]?\d|2[0-3])[:.]([0-5]\d)\b/);
            return match ? `${String(match[1]).padStart(2, '0')}:${match[2]}` : '';
        }
        if (item.type === 'select' && item.options?.length) {
            const ranked = item.options
                .map(option => ({ option, score: textSimilarity(raw, option) }))
                .sort((left, right) => right.score - left.score);
            return ranked[0]?.score >= 0.48 ? ranked[0].option : '';
        }
        return raw;
    }

    function normalizeNumber(rawValue) {
        let value = String(rawValue).replace(/[^\d,.\-()]/g, '').trim();
        if (!value) return '';
        const negative = /^\(.*\)$/.test(value) || value.startsWith('-');
        value = value.replace(/[()\-]/g, '');
        const lastComma = value.lastIndexOf(',');
        const lastDot = value.lastIndexOf('.');
        let decimalSeparator = '';
        if (lastComma >= 0 && lastDot >= 0) {
            decimalSeparator = lastComma > lastDot ? ',' : '.';
        } else if (lastComma >= 0 && value.length - lastComma - 1 <= 2) {
            decimalSeparator = ',';
        } else if (lastDot >= 0 && value.length - lastDot - 1 <= 2) {
            decimalSeparator = '.';
        }
        if (decimalSeparator) {
            const separatorIndex = value.lastIndexOf(decimalSeparator);
            const integer = value.slice(0, separatorIndex).replace(/[.,]/g, '');
            const decimal = value.slice(separatorIndex + 1).replace(/[.,]/g, '');
            value = `${integer}.${decimal}`;
        } else {
            value = value.replace(/[.,]/g, '');
        }
        const number = Number(value);
        if (!Number.isFinite(number)) return '';
        return String(negative ? -number : number);
    }

    function normalizeDate(rawValue) {
        const raw = normalizeKey(rawValue);
        const iso = raw.match(/\b(20\d{2})[-/.](0?[1-9]|1[0-2])[-/.]([0-2]?\d|3[01])\b/);
        if (iso) return `${iso[1]}-${String(iso[2]).padStart(2, '0')}-${String(iso[3]).padStart(2, '0')}`;
        const dmy = raw.match(/\b([0-2]?\d|3[01])[-/.](0?[1-9]|1[0-2])[-/.](\d{2,4})\b/);
        if (dmy) {
            const year = dmy[3].length === 2 ? `20${dmy[3]}` : dmy[3];
            return `${year}-${String(dmy[2]).padStart(2, '0')}-${String(dmy[1]).padStart(2, '0')}`;
        }
        const monthNames = {
            januari: 1, january: 1, jan: 1,
            februari: 2, february: 2, feb: 2,
            maret: 3, march: 3, mar: 3,
            april: 4, apr: 4,
            mei: 5, may: 5,
            juni: 6, june: 6, jun: 6,
            juli: 7, july: 7, jul: 7,
            agustus: 8, august: 8, agu: 8, aug: 8,
            september: 9, sep: 9,
            oktober: 10, october: 10, okt: 10, oct: 10,
            november: 11, nov: 11,
            desember: 12, december: 12, des: 12, dec: 12
        };
        const named = raw.match(/\b([0-2]?\d|3[01])\s+([a-z]+)\s+(20\d{2})\b/);
        if (named && monthNames[named[2]]) {
            return `${named[3]}-${String(monthNames[named[2]]).padStart(2, '0')}-${String(named[1]).padStart(2, '0')}`;
        }
        const parsed = Date.parse(rawValue);
        if (Number.isFinite(parsed)) return new Date(parsed).toISOString().slice(0, 10);
        return '';
    }

    function mapFields(extraction, schema, usedFragmentIds, conflicts) {
        const candidateResult = collectLabelValueCandidates(extraction);
        const candidates = candidateResult.candidates;
        const fields = {};
        const provenance = {};
        (schema.fields || []).forEach(item => {
            const aliases = fieldAliases(item);
            let selected = null;
            let alternative = null;
            candidates.forEach(candidate => {
                const labelScore = bestAliasScore(candidate.label, aliases);
                if (labelScore < 0.58) return;
                const normalized = normalizeMappedValue(candidate.value, item);
                if (normalized === '') return;
                const score = labelScore * candidate.baseConfidence;
                const result = { candidate, labelScore, normalized, score };
                if (!selected || result.score > selected.score) {
                    alternative = selected;
                    selected = result;
                } else if (!alternative || result.score > alternative.score) {
                    alternative = result;
                }
            });
            if (!selected) return;
            fields[item.key] = selected.normalized;
            provenance[item.key] = {
                rawValue: selected.candidate.value,
                normalizedValue: selected.normalized,
                confidence: clamp(selected.score),
                sourceRef: selected.candidate.sourceRef,
                fragmentIds: selected.candidate.sourceRefs,
                method: selected.candidate.origin
            };
            selected.candidate.sourceRefs.forEach(id => usedFragmentIds.add(id));
            if (
                alternative
                && alternative.normalized !== selected.normalized
                && selected.score - alternative.score < 0.08
            ) {
                conflicts.push({
                    type: 'field',
                    key: item.key,
                    label: item.label,
                    selected: provenance[item.key],
                    alternative: {
                        rawValue: alternative.candidate.value,
                        normalizedValue: alternative.normalized,
                        confidence: clamp(alternative.score),
                        sourceRef: alternative.candidate.sourceRef
                    }
                });
            }
        });
        return { fields, provenance, candidatesTruncated: candidateResult.truncated };
    }

    function headerMapping(headers, columns) {
        const assignments = [];
        const usedColumnKeys = new Set();
        headers.forEach((header, sourceIndex) => {
            const ranked = columns
                .filter(column => !column.readonly && !usedColumnKeys.has(column.key))
                .map(column => ({
                    column,
                    score: bestAliasScore(header, fieldAliases(column))
                }))
                .sort((left, right) => right.score - left.score);
            if (ranked[0]?.score >= 0.5) {
                usedColumnKeys.add(ranked[0].column.key);
                assignments.push({
                    sourceIndex,
                    sourceHeader: header,
                    targetKey: ranked[0].column.key,
                    targetLabel: ranked[0].column.label,
                    score: ranked[0].score
                });
            }
        });
        const ratio = columns.length ? assignments.length / columns.filter(column => !column.readonly).length : 0;
        const average = assignments.length
            ? assignments.reduce((sum, assignment) => sum + assignment.score, 0) / assignments.length
            : 0;
        return {
            assignments,
            score: average * 0.7 + Math.min(1, ratio) * 0.3
        };
    }

    function bestTableMapping(table, schema) {
        const declaredHeaderRow = {
            rowNumber: table.headerRowNumber || 0,
            values: table.headers || [],
            fragmentRefs: table.headerFragmentRefs || []
        };
        const allRows = [
            ...(table.preamble || []),
            declaredHeaderRow,
            ...(table.rows || [])
        ].sort((left, right) => Number(left.rowNumber || 0) - Number(right.rowNumber || 0));
        const variants = [];
        allRows.slice(0, 8).forEach((row, index) => {
            variants.push({
                headers: row.values || [],
                dataRows: allRows.filter((unused, rowIndex) => rowIndex !== index),
                headerSource: `row-${row.rowNumber || index + 1}`,
                headerFragmentRefs: row.fragmentRefs || []
            });
        });
        return variants
            .map(variant => ({
                ...variant,
                mapping: headerMapping(variant.headers, schema.columns || [])
            }))
            .sort((left, right) => right.mapping.score - left.mapping.score)[0];
    }

    function mapRows(extraction, schema, usedFragmentIds) {
        const rows = [];
        const provenance = [];
        const fragmentSourceRefs = new Map(
            extraction.fragments.map(fragment => [fragment.id, fragment.sourceRef])
        );
        let truncated = false;
        extraction.tables.forEach(table => {
            if (rows.length >= MAX_MAPPED_ROWS) {
                truncated = true;
                return;
            }
            const variant = bestTableMapping(table, schema);
            if (!variant?.mapping.assignments.length) return;
            const minimumAssignments = (schema.columns || []).length <= 2 ? 1 : 2;
            if (
                variant.mapping.assignments.length < minimumAssignments
                || variant.mapping.score < 0.48
            ) return;
            variant.dataRows.forEach((sourceRow, sourceRowIndex) => {
                if (rows.length >= MAX_MAPPED_ROWS) {
                    truncated = true;
                    return;
                }
                const mappedRow = {};
                const rowSources = {};
                variant.mapping.assignments.forEach(assignment => {
                    const column = schema.columns.find(item => item.key === assignment.targetKey);
                    const rawValue = sourceRow.values?.[assignment.sourceIndex] ?? '';
                    const normalized = normalizeMappedValue(rawValue, column);
                    if (normalized === '') return;
                    mappedRow[assignment.targetKey] = normalized;
                    const fragmentId = sourceRow.fragmentRefs?.[assignment.sourceIndex] || null;
                    if (fragmentId) usedFragmentIds.add(fragmentId);
                    rowSources[assignment.targetKey] = {
                        rawValue,
                        normalizedValue: normalized,
                        confidence: assignment.score,
                        sourceRef: fragmentId
                            ? fragmentSourceRefs.get(fragmentId)
                            : `${table.sourceRef}:r${sourceRow.rowNumber || sourceRowIndex + 1}`,
                        fragmentId,
                        sourceHeader: assignment.sourceHeader
                    };
                });
                if (!Object.keys(mappedRow).length) return;
                const rowId = global.crypto?.randomUUID?.()
                    || `ROW-${Date.now()}-${rows.length + 1}`;
                mappedRow._import = {
                    rowId,
                    sourceRef: table.sourceRef,
                    confidence: variant.mapping.score
                };
                rows.push(mappedRow);
                provenance.push({
                    rowId,
                    tableId: table.id,
                    sourceRef: table.sourceRef,
                    confidence: variant.mapping.score,
                    fields: rowSources
                });
            });
        });
        return { rows, provenance, truncated };
    }

    function calculateExtractionCoverage(extraction) {
        if (extraction.stats.supported === false) return 0;
        if (extraction.stats.pagesExpected) {
            const completePages = Number.isFinite(Number(extraction.stats.pagesFullyExtracted))
                ? Number(extraction.stats.pagesFullyExtracted)
                : Number(extraction.stats.pagesProcessed || 0);
            return clamp(completePages / extraction.stats.pagesExpected);
        }
        if (extraction.stats.xmlPartsFailed) {
            const total = extraction.stats.xmlPartsProcessed + extraction.stats.xmlPartsFailed;
            return total ? clamp(extraction.stats.xmlPartsProcessed / total) : 0;
        }
        return extraction.warnings.some(warning => (
            warning.severity === 'error'
            && /failed|incomplete|required|mismatch|unsupported|encrypted/.test(warning.code)
        )) ? 0.75 : 1;
    }

    function analyzeImport(extracted, schemas, options = {}) {
        if (!extracted?.source || !extracted?.extraction) {
            throw new TypeError('Hasil ekstraksi tidak valid.');
        }
        const schemaList = Array.isArray(schemas) ? schemas : [];
        const classification = classifySchemas(
            extracted.source,
            extracted.extraction,
            schemaList,
            options.targetSchemaId || ''
        );
        const selectedSchema = schemaList.find(
            schema => schema.id === classification.selectedSchemaId
        ) || null;
        const usedFragmentIds = new Set();
        const conflicts = [];
        let mapping = {
            fields: {},
            fieldProvenance: {},
            rows: [],
            rowProvenance: [],
            conflicts,
            mappedFragmentIds: [],
            unmappedCount: extracted.extraction.fragments.length,
            unmappedPreview: extracted.extraction.fragments.slice(0, 200)
        };

        if (selectedSchema) {
            const fieldResult = mapFields(
                extracted.extraction,
                selectedSchema,
                usedFragmentIds,
                conflicts
            );
            const rowResult = mapRows(
                extracted.extraction,
                selectedSchema,
                usedFragmentIds
            );
            mapping = {
                fields: fieldResult.fields,
                fieldProvenance: fieldResult.provenance,
                rows: rowResult.rows,
                rowProvenance: rowResult.provenance,
                conflicts,
                mappedFragmentIds: [...usedFragmentIds],
                unmappedCount: Math.max(0, extracted.extraction.fragments.length - usedFragmentIds.size),
                unmappedPreview: extracted.extraction.fragments
                    .filter(fragment => !usedFragmentIds.has(fragment.id))
                    .slice(0, 200),
                candidatesTruncated: fieldResult.candidatesTruncated,
                rowsTruncated: rowResult.truncated
            };
        }

        const requiredFields = selectedSchema
            ? (selectedSchema.fields || []).filter(item => item.required)
            : [];
        const filledRequiredFields = requiredFields.filter(item => (
            String(mapping.fields[item.key] ?? '').trim()
        ));
        const extractionCoverage = calculateExtractionCoverage(extracted.extraction);
        const mappingCoverage = extracted.extraction.fragments.length
            ? usedFragmentIds.size / extracted.extraction.fragments.length
            : 0;
        const requiredCoverage = requiredFields.length
            ? filledRequiredFields.length / requiredFields.length
            : selectedSchema ? 1 : 0;
        const errorCount = extracted.extraction.warnings.filter(
            warning => warning.severity === 'error'
        ).length;
        const warningCount = extracted.extraction.warnings.filter(
            warning => warning.severity === 'warning'
        ).length;

        const qualityWarnings = [...extracted.extraction.warnings];
        if (!selectedSchema) {
            qualityWarnings.push({
                code: 'report_template_not_detected',
                message: 'Ekstraksi berhasil, tetapi tidak ada tipe laporan yang cukup cocok. Pilih template secara manual atau kembangkan template baru.',
                severity: 'warning',
                sourceRef: extracted.source.fileName
            });
        } else {
            const missing = requiredFields.filter(item => !String(mapping.fields[item.key] ?? '').trim());
            if (missing.length) {
                qualityWarnings.push({
                    code: 'required_fields_missing',
                    message: `${missing.length} field wajib belum ditemukan otomatis: ${missing.slice(0, 8).map(item => item.label).join(', ')}${missing.length > 8 ? ', …' : ''}.`,
                    severity: 'warning',
                    sourceRef: selectedSchema.id
                });
            }
            if (classification.mode === 'automatic' && classification.confidence === 'low') {
                qualityWarnings.push({
                    code: 'low_template_confidence',
                    message: 'Kecocokan tipe laporan rendah; konfirmasi template sebelum membuat draft.',
                    severity: 'warning',
                    sourceRef: selectedSchema.id
                });
            }
        }
        if (mapping.unmappedCount) {
            qualityWarnings.push({
                code: 'unmapped_fragments_preserved',
                message: `${mapping.unmappedCount} fragmen belum masuk field laporan, tetapi tetap dipertahankan di paket ekstraksi/JSON.`,
                severity: 'info',
                sourceRef: extracted.source.fileName
            });
        }
        if (conflicts.length) {
            qualityWarnings.push({
                code: 'mapping_conflicts',
                message: `${conflicts.length} kandidat pemetaan memiliki nilai alternatif dan perlu review.`,
                severity: 'warning',
                sourceRef: selectedSchema?.id || ''
            });
        }
        if (mapping.candidatesTruncated) {
            qualityWarnings.push({
                code: 'mapping_candidate_limit',
                message: `Pemetaan otomatis dibatasi pada ${MAX_MAPPING_CANDIDATES.toLocaleString('id-ID')} kandidat agar workbook besar tetap responsif. Seluruh fragmen sumber tetap tersimpan untuk pencarian/review.`,
                severity: 'warning',
                sourceRef: extracted.source.fileName
            });
        }
        if (mapping.rowsTruncated) {
            qualityWarnings.push({
                code: 'mapping_row_limit',
                message: `Pemetaan draft dibatasi ${MAX_MAPPED_ROWS.toLocaleString('id-ID')} baris. Baris selebihnya tetap tersimpan di arsip ekstraksi dan envelope database.`,
                severity: 'warning',
                sourceRef: selectedSchema?.id || extracted.source.fileName
            });
        }

        const createdAt = new Date().toISOString();
        const importId = global.crypto?.randomUUID?.()
            || `IMP-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
        return {
            schemaVersion: ENVELOPE_VERSION,
            importId,
            createdAt,
            updatedAt: createdAt,
            source: extracted.source,
            extraction: extracted.extraction,
            classification,
            target: selectedSchema
                ? {
                    schemaId: selectedSchema.id,
                    code: selectedSchema.code,
                    title: selectedSchema.title,
                    schemaVersion: 'prototype-1'
                }
                : null,
            mapping,
            quality: {
                extractionCoverage,
                mappingCoverage,
                requiredCoverage,
                sourceFragments: extracted.extraction.fragments.length,
                mappedFragments: usedFragmentIds.size,
                unmappedFragments: mapping.unmappedCount,
                requiredFields: requiredFields.length,
                filledRequiredFields: filledRequiredFields.length,
                errors: errorCount,
                warningCount,
                conflicts: conflicts.length,
                reviewRequired: true,
                canCreateDraft: Boolean(
                    selectedSchema
                    && extracted.extraction.stats.supported !== false
                    && errorCount === 0
                ),
                requiresIncompleteOverride: Boolean(
                    selectedSchema
                    && extracted.extraction.stats.supported !== false
                    && errorCount > 0
                ),
                canFinalizeAutomatically: false,
                warnings: qualityWarnings
            }
        };
    }

    function buildDatabaseEnvelope(record) {
        if (!record) throw new TypeError('Record impor tidak tersedia.');
        return {
            schemaVersion: record.schemaVersion || ENVELOPE_VERSION,
            importJob: {
                id: record.importId,
                status: record.quality?.errors ? 'review_required' : 'extracted',
                createdAt: record.createdAt,
                updatedAt: record.updatedAt,
                parserVersion: ENGINE_VERSION
            },
            sourceDocument: {
                id: `DOC-${record.source.sha256}`,
                fileName: record.source.fileName,
                relativePath: record.source.relativePath,
                mimeType: record.source.mimeType,
                extension: record.source.extension,
                size: record.source.size,
                sha256: record.source.sha256,
                lastModified: record.source.lastModified,
                signature: record.source.signature
            },
            parser: record.extraction.parser,
            extraction: {
                format: record.extraction.format,
                stats: record.extraction.stats,
                properties: record.extraction.properties,
                sections: record.extraction.sections,
                tables: record.extraction.tables,
                fragments: record.extraction.fragments,
                artifacts: record.extraction.artifacts,
                warnings: record.extraction.warnings
            },
            target: record.target,
            classification: record.classification,
            mapping: {
                fields: record.mapping.fields,
                fieldProvenance: record.mapping.fieldProvenance,
                rows: record.mapping.rows,
                rowProvenance: record.mapping.rowProvenance,
                mappedFragmentIds: record.mapping.mappedFragmentIds,
                conflicts: record.mapping.conflicts
            },
            quality: record.quality
        };
    }

    function prepareDraftPayload(record) {
        if (!record?.target) throw new Error('Pilih tipe laporan sebelum membuat draft.');
        return {
            schemaId: record.target.schemaId,
            fields: { ...record.mapping.fields },
            rows: record.mapping.rows.map(row => ({ ...row })),
            importSource: {
                importId: record.importId,
                fileName: record.source.fileName,
                relativePath: record.source.relativePath,
                sha256: record.source.sha256,
                parser: record.extraction.parser,
                targetConfidence: record.classification.confidence,
                extractionCoverage: record.quality.extractionCoverage,
                mappingCoverage: record.quality.mappingCoverage,
                requiredCoverage: record.quality.requiredCoverage,
                mappedFragments: record.quality.mappedFragments,
                unmappedFragments: record.quality.unmappedFragments,
                warningCodes: record.quality.warnings.map(warning => warning.code),
                importedAt: new Date().toISOString()
            }
        };
    }

    global.FleetDocumentImportEngine = Object.freeze({
        version: ENGINE_VERSION,
        envelopeVersion: ENVELOPE_VERSION,
        supportedExtensions: [...SUPPORTED_EXTENSIONS],
        extractFile,
        analyzeImport,
        buildDatabaseEnvelope,
        prepareDraftPayload,
        normalizeKey,
        textSimilarity,
        terminateWorker() {
            workerPending.forEach(pending => {
                clearTimeout(pending.timer);
                pending.reject(new Error('Worker dokumen dihentikan sebelum tugas selesai.'));
            });
            sharedWorker?.terminate();
            sharedWorker = null;
            workerPending.clear();
        }
    });
})(window);
(function () {
    'use strict';

    const DB_NAME = 'fleetmonitor-document-imports';
    const DB_VERSION = 1;
    const RECORD_STORE = 'records';
    const SUMMARY_STORE = 'summaries';
    const MAX_BATCH_FILES = 500;
    const MAX_BATCH_BYTES = 500 * 1024 * 1024;
    const MAX_RECORD_CACHE = 3;
    const MAX_DRAFT_ROWS = 1000;
    const state = {
        initialized: false,
        summaries: [],
        records: new Map(),
        selectedId: null,
        processing: false,
        detailTab: 'mapping',
        tableIndex: 0,
        detailGeneration: 0,
        currentBatch: null,
        enumerationWarnings: [],
        db: null,
        storageWarning: '',
        hydrating: false,
        hydrationPromise: null
    };

    const engine = () => window.FleetDocumentImportEngine;
    const reports = () => window.FleetReportForms;

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatBytes(bytes) {
        const value = Number(bytes || 0);
        if (value < 1024) return `${value} B`;
        if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
        return `${(value / 1024 / 1024).toFixed(2)} MB`;
    }

    function formatPercent(value) {
        return `${Math.round(Number(value || 0) * 100)}%`;
    }

    function formatDateTime(value) {
        if (!value) return '—';
        try {
            return new Intl.DateTimeFormat('id-ID', {
                dateStyle: 'medium',
                timeStyle: 'short'
            }).format(new Date(value));
        } catch (error) {
            return value;
        }
    }

    function statusMeta(status) {
        const values = {
            queued: { label: 'Antre', icon: 'fa-clock', className: 'neutral' },
            processing: { label: 'Memproses', icon: 'fa-spinner fa-spin', className: 'processing' },
            ready: { label: 'Siap jadi draft', icon: 'fa-circle-check', className: 'success' },
            extracted: { label: 'Perlu pilih tipe', icon: 'fa-layer-group', className: 'warning' },
            review: { label: 'Perlu review', icon: 'fa-triangle-exclamation', className: 'warning' },
            duplicate: { label: 'Duplikat', icon: 'fa-copy', className: 'neutral' },
            unsupported: { label: 'Tidak didukung', icon: 'fa-ban', className: 'danger' },
            failed: { label: 'Gagal', icon: 'fa-circle-xmark', className: 'danger' }
        };
        return values[status] || values.queued;
    }

    function makeSummary(record, overrides = {}) {
        const unsupported = record.extraction?.stats?.supported === false;
        let status = unsupported
            ? 'unsupported'
            : record.quality?.errors
                ? 'review'
                : record.target
                    ? 'ready'
                    : 'extracted';
        if (record.duplicateOf) status = 'duplicate';
        return {
            importId: record.importId,
            fileName: record.source.fileName,
            relativePath: record.source.relativePath,
            extension: record.source.extension,
            size: record.source.size,
            sha256: record.source.sha256,
            createdAt: record.createdAt,
            updatedAt: record.updatedAt,
            status,
            target: record.target,
            classification: record.classification,
            quality: {
                extractionCoverage: record.quality?.extractionCoverage || 0,
                mappingCoverage: record.quality?.mappingCoverage || 0,
                requiredCoverage: record.quality?.requiredCoverage || 0,
                errors: record.quality?.errors || 0,
                warnings: record.quality?.warnings?.length || 0,
                sourceFragments: record.quality?.sourceFragments || 0,
                unmappedFragments: record.quality?.unmappedFragments || 0
            },
            stats: record.extraction?.stats || {},
            duplicateOf: record.duplicateOf || null,
            ...overrides
        };
    }

    function openDatabase() {
        return new Promise((resolve, reject) => {
            if (!window.indexedDB) {
                reject(new Error('IndexedDB tidak tersedia.'));
                return;
            }
            const request = indexedDB.open(DB_NAME, DB_VERSION);
            let settled = false;
            const finish = (callback, value) => {
                if (settled) return;
                settled = true;
                window.clearTimeout(timeoutId);
                callback(value);
            };
            const timeoutId = window.setTimeout(() => {
                finish(reject, new Error('IndexedDB tidak merespons dalam 10 detik.'));
            }, 10000);
            request.onupgradeneeded = () => {
                const database = request.result;
                if (!database.objectStoreNames.contains(RECORD_STORE)) {
                    const records = database.createObjectStore(RECORD_STORE, { keyPath: 'importId' });
                    records.createIndex('sha256', 'source.sha256', { unique: false });
                    records.createIndex('createdAt', 'createdAt', { unique: false });
                }
                if (!database.objectStoreNames.contains(SUMMARY_STORE)) {
                    const summaries = database.createObjectStore(SUMMARY_STORE, { keyPath: 'importId' });
                    summaries.createIndex('sha256', 'sha256', { unique: false });
                    summaries.createIndex('createdAt', 'createdAt', { unique: false });
                }
            };
            request.onsuccess = () => {
                if (settled) {
                    request.result.close();
                    return;
                }
                finish(resolve, request.result);
            };
            request.onerror = () => finish(
                reject,
                request.error || new Error('Database impor gagal dibuka.')
            );
            request.onblocked = () => finish(
                reject,
                new Error('IndexedDB diblokir oleh tab lain. Tutup tab dashboard lama lalu muat ulang.')
            );
        });
    }

    function dbRequest(storeName, mode, action) {
        return new Promise((resolve, reject) => {
            if (!state.db) {
                reject(new Error('IndexedDB belum aktif.'));
                return;
            }
            const transaction = state.db.transaction(storeName, mode);
            const store = transaction.objectStore(storeName);
            let request;
            let result;
            let settled = false;
            try {
                request = action(store);
            } catch (error) {
                reject(error);
                return;
            }
            request.onsuccess = () => {
                result = request.result;
            };
            request.onerror = () => {
                if (settled) return;
                settled = true;
                reject(request.error || transaction.error);
            };
            transaction.oncomplete = () => {
                if (settled) return;
                settled = true;
                resolve(result);
            };
            transaction.onerror = () => {
                if (settled) return;
                settled = true;
                reject(transaction.error || new Error('Transaksi IndexedDB gagal.'));
            };
            transaction.onabort = transaction.onerror;
        });
    }

    function writeRecordAndSummary(record, summary) {
        return new Promise((resolve, reject) => {
            if (!state.db) {
                reject(new Error('IndexedDB belum aktif.'));
                return;
            }
            const transaction = state.db.transaction(
                [RECORD_STORE, SUMMARY_STORE],
                'readwrite'
            );
            transaction.objectStore(RECORD_STORE).put(record);
            transaction.objectStore(SUMMARY_STORE).put(summary);
            transaction.oncomplete = () => resolve();
            transaction.onerror = () => reject(transaction.error || new Error('Transaksi arsip gagal.'));
            transaction.onabort = transaction.onerror;
        });
    }

    function rememberRecord(record) {
        state.records.delete(record.importId);
        state.records.set(record.importId, record);
        while (state.records.size > MAX_RECORD_CACHE) {
            const removableId = [...state.records.keys()].find(importId => (
                importId !== state.selectedId && importId !== record.importId
            ));
            if (!removableId) break;
            state.records.delete(removableId);
        }
    }

    async function saveRecord(record) {
        rememberRecord(record);
        const summary = makeSummary(record);
        const index = state.summaries.findIndex(item => item.importId === record.importId);
        if (index >= 0) state.summaries[index] = summary;
        else state.summaries.unshift(summary);
        updateImportCount();
        if (!state.db) return;
        try {
            await writeRecordAndSummary(record, summary);
        } catch (error) {
            state.storageWarning = `Hasil tetap tersedia selama halaman ini terbuka, tetapi gagal disimpan ke IndexedDB: ${error.message}`;
        }
    }

    async function saveSummary(summary) {
        const index = state.summaries.findIndex(item => item.importId === summary.importId);
        if (index >= 0) state.summaries[index] = summary;
        else state.summaries.unshift(summary);
        updateImportCount();
        if (!state.db) return;
        try {
            await dbRequest(SUMMARY_STORE, 'readwrite', store => store.put(summary));
        } catch (error) {
            state.storageWarning = `Status impor gagal disimpan: ${error.message}`;
        }
    }

    async function loadRecord(importId) {
        if (state.records.has(importId)) return state.records.get(importId);
        if (!state.db) return null;
        try {
            const record = await dbRequest(RECORD_STORE, 'readonly', store => store.get(importId));
            if (record) rememberRecord(record);
            return record || null;
        } catch (error) {
            state.storageWarning = `Detail arsip gagal dibaca: ${error.message}`;
            return null;
        }
    }

    async function deleteRecord(importId) {
        if (state.db) {
            await new Promise((resolve, reject) => {
                const transaction = state.db.transaction(
                    [RECORD_STORE, SUMMARY_STORE],
                    'readwrite'
                );
                transaction.objectStore(RECORD_STORE).delete(importId);
                transaction.objectStore(SUMMARY_STORE).delete(importId);
                transaction.oncomplete = () => resolve();
                transaction.onerror = () => reject(transaction.error || new Error('Arsip gagal dihapus.'));
                transaction.onabort = transaction.onerror;
            });
        }
        state.records.delete(importId);
        state.summaries = state.summaries.filter(item => item.importId !== importId);
        if (state.selectedId === importId) {
            state.selectedId = state.summaries[0]?.importId || null;
        }
        updateImportCount();
        render();
    }

    async function clearAllRecords() {
        if (state.db) {
            await new Promise((resolve, reject) => {
                const transaction = state.db.transaction(
                    [RECORD_STORE, SUMMARY_STORE],
                    'readwrite'
                );
                transaction.objectStore(RECORD_STORE).clear();
                transaction.objectStore(SUMMARY_STORE).clear();
                transaction.oncomplete = () => resolve();
                transaction.onerror = () => reject(transaction.error || new Error('Arsip gagal dikosongkan.'));
                transaction.onabort = transaction.onerror;
            });
        }
        state.records.clear();
        state.summaries = [];
        state.selectedId = null;
        state.currentBatch = null;
        updateImportCount();
        render();
    }

    async function hydrateSummaries() {
        try {
            state.db = await openDatabase();
            const summaries = await dbRequest(SUMMARY_STORE, 'readonly', store => store.getAll());
            const merged = new Map(
                state.summaries.map(summary => [summary.importId, summary])
            );
            for (const summary of summaries || []) {
                if (!merged.has(summary.importId)) merged.set(summary.importId, summary);
            }
            state.summaries = [...merged.values()].sort(
                (left, right) => new Date(right.createdAt) - new Date(left.createdAt)
            );
            if (!state.selectedId) {
                state.selectedId = state.summaries[0]?.importId || null;
            }
        } catch (error) {
            state.storageWarning = `Arsip impor persisten tidak tersedia: ${error.message}`;
            state.db = null;
        }
        updateImportCount();
    }

    function setImportControlsDisabled(disabled) {
        [
            'documentFileInput',
            'documentFolderInput',
            'chooseImportFiles',
            'chooseImportFolder',
            'documentOcrMode',
            'clearAllImports'
        ].forEach(id => {
            const control = document.getElementById(id);
            if (control) control.disabled = disabled;
        });
        const dropzone = document.getElementById('documentDropzone');
        if (dropzone) {
            dropzone.setAttribute('aria-disabled', disabled ? 'true' : 'false');
            dropzone.setAttribute('aria-busy', disabled ? 'true' : 'false');
            dropzone.tabIndex = disabled ? -1 : 0;
        }
    }

    function updateImportCount() {
        const counter = document.getElementById('reportImportCount');
        if (counter) counter.textContent = state.summaries.length;
    }

    function createMarkup() {
        const module = document.getElementById('documentImportModule');
        if (!module || module.dataset.ready === 'true') return;
        module.dataset.ready = 'true';
        module.innerHTML = `
            <div class="import-heading">
                <div>
                    <div class="report-eyebrow">Ingest dokumen & staging data</div>
                    <h1>Ubah Dokumen Menjadi Draft Laporan</h1>
                    <p>Impor Word, PDF, Excel, atau CSV. Seluruh pemrosesan berjalan di perangkat ini; hasil ekstraksi, sumber nilai, konflik, dan informasi yang belum terpetakan tetap diaudit.</p>
                </div>
                <div class="import-local-badge">
                    <i class="fa-solid fa-shield-halved"></i>
                    <div><strong>LOCAL PROCESSING</strong><span>Tidak mengunggah file ke layanan luar</span></div>
                </div>
            </div>
            <section class="import-dropzone" id="documentDropzone" tabindex="0" role="button" aria-label="Pilih atau jatuhkan dokumen">
                <div class="import-drop-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                <div>
                    <h2>Jatuhkan file atau folder di sini</h2>
                    <p>DOCX/DOC, PDF, XLSX/XLS, CSV/TSV, serta gambar JPG/PNG untuk OCR. File RAR akan dicatat sebagai arsip yang perlu diekstrak.</p>
                    <div class="import-drop-actions">
                        <button type="button" class="import-primary-button" id="chooseImportFiles">
                            <i class="fa-solid fa-file-circle-plus"></i> Pilih File
                        </button>
                        <button type="button" class="import-secondary-button" id="chooseImportFolder">
                            <i class="fa-solid fa-folder-tree"></i> Audit Satu Folder
                        </button>
                    </div>
                </div>
                <input id="documentFileInput" type="file" multiple hidden
                    accept=".doc,.docx,.pdf,.xls,.xlsx,.xlsm,.csv,.tsv,.jpg,.jpeg,.png,.webp,.rar">
                <input id="documentFolderInput" type="file" multiple webkitdirectory directory hidden>
            </section>
            <div class="import-options-bar">
                <div>
                    <label for="documentOcrMode">Mode pembacaan gambar / scan</label>
                    <select id="documentOcrMode" class="report-select">
                        <option value="auto">OCR otomatis pada halaman tanpa teks</option>
                        <option value="all">OCR maksimal, termasuk semua gambar tertanam</option>
                        <option value="off">Tanpa OCR (audit akan menandai halaman scan)</option>
                    </select>
                </div>
                <div class="import-pipeline">
                    <span><b>1</b> Hash</span><i class="fa-solid fa-chevron-right"></i>
                    <span><b>2</b> Ekstrak</span><i class="fa-solid fa-chevron-right"></i>
                    <span><b>3</b> Cocokkan</span><i class="fa-solid fa-chevron-right"></i>
                    <span><b>4</b> Review</span>
                </div>
            </div>
            <div class="import-privacy-note">
                <i class="fa-solid fa-lock"></i>
                <span>Hasil ekstraksi dapat memuat data pribadi dan disimpan di IndexedDB browser ini tanpa enkripsi aplikasi. Hapus arsip ketika review selesai, terutama pada perangkat bersama.</span>
                <button type="button" class="import-secondary-button" id="clearAllImports">
                    <i class="fa-regular fa-trash-can"></i> Hapus Semua Arsip
                </button>
            </div>
            <div id="importStorageWarning"></div>
            <div id="importBatchSummary"></div>
            <div class="import-workbench">
                <aside class="import-queue-panel">
                    <div class="import-panel-title">
                        <div><h2>Antrean & Arsip</h2><span id="importQueueCount"></span></div>
                        <button type="button" id="clearImportSelection" title="Tutup detail" aria-label="Tutup detail dokumen"><i class="fa-solid fa-xmark"></i></button>
                    </div>
                    <div id="documentImportQueue"></div>
                </aside>
                <section class="import-detail-panel" id="documentImportDetail"></section>
            </div>
        `;
        bindBaseEvents();
    }

    function bindBaseEvents() {
        const dropzone = document.getElementById('documentDropzone');
        const fileInput = document.getElementById('documentFileInput');
        const folderInput = document.getElementById('documentFolderInput');
        document.getElementById('chooseImportFiles').addEventListener('click', event => {
            event.stopPropagation();
            if (state.hydrating) return;
            fileInput.click();
        });
        document.getElementById('chooseImportFolder').addEventListener('click', event => {
            event.stopPropagation();
            if (state.hydrating) return;
            folderInput.click();
        });
        fileInput.addEventListener('change', () => {
            enqueueFiles([...fileInput.files]);
            fileInput.value = '';
        });
        folderInput.addEventListener('change', () => {
            enqueueFiles([...folderInput.files]);
            folderInput.value = '';
        });
        dropzone.addEventListener('click', event => {
            if (state.hydrating || event.target.closest('button')) return;
            fileInput.click();
        });
        dropzone.addEventListener('keydown', event => {
            if (state.hydrating || event.target !== dropzone) return;
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                fileInput.click();
            }
        });
        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, event => {
                event.preventDefault();
                dropzone.classList.add('dragging');
            });
        });
        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, event => {
                event.preventDefault();
                dropzone.classList.remove('dragging');
            });
        });
        dropzone.addEventListener('drop', async event => {
            if (state.hydrating) return;
            const result = await filesFromDrop(event.dataTransfer);
            state.enumerationWarnings = result.warnings;
            enqueueFiles(result.files);
        });
        document.getElementById('clearImportSelection').addEventListener('click', () => {
            state.selectedId = null;
            render();
        });
        document.getElementById('clearAllImports').addEventListener('click', async event => {
            if (state.processing) {
                reports()?.notify('Tunggu batch aktif selesai sebelum menghapus arsip.', true);
                return;
            }
            if (!state.summaries.length) {
                reports()?.notify('Arsip impor sudah kosong.');
                return;
            }
            const referenced = state.summaries.filter(summary => (
                reports()?.isImportReferenced?.(summary.importId)
            )).length;
            const confirmed = window.confirm(
                `Hapus seluruh ${state.summaries.length} arsip impor dari browser ini?\n\n`
                + `${referenced ? `${referenced} arsip masih direferensikan oleh draft/laporan. ` : ''}`
                + 'File sumber asli tidak berubah. Tindakan ini tidak dapat dipulihkan.'
            );
            if (!confirmed) return;
            event.currentTarget.disabled = true;
            try {
                await clearAllRecords();
                reports()?.notify('Seluruh arsip impor di browser ini telah dihapus.');
            } catch (error) {
                reports()?.notify(`Arsip gagal dihapus: ${error.message}`, true);
            } finally {
                if (event.currentTarget.isConnected) {
                    event.currentTarget.disabled = state.processing || state.hydrating;
                }
            }
        });
    }

    async function filesFromDrop(dataTransfer) {
        const items = [...(dataTransfer?.items || [])];
        if (!items.length) {
            return { files: [...(dataTransfer?.files || [])], warnings: [] };
        }
        const files = [];
        const warnings = [];
        for (const item of items.filter(candidate => candidate.kind === 'file')) {
            const entry = item.webkitGetAsEntry?.();
            if (entry) {
                await readDroppedEntry(entry, '', files, warnings);
            } else {
                const file = item.getAsFile?.();
                if (file) files.push(file);
                else warnings.push('Satu item drop tidak dapat dibaca sebagai file.');
            }
        }
        [...(dataTransfer?.files || [])].forEach(file => {
            const key = `${file.webkitRelativePath || file.name}|${file.size}|${file.lastModified}`;
            const exists = files.some(existing => (
                `${existing.webkitRelativePath || existing.relativePath || existing.name}|${existing.size}|${existing.lastModified}` === key
            ));
            if (!exists) files.push(file);
        });
        return { files, warnings };
    }

    function readDroppedEntry(entry, parentPath, output, warnings) {
        return new Promise(resolve => {
            if (entry.isFile) {
                entry.file(file => {
                    Object.defineProperty(file, 'relativePath', {
                        value: `${parentPath}${file.name}`,
                        configurable: true
                    });
                    output.push(file);
                    resolve();
                }, error => {
                    warnings.push(`File ${parentPath}${entry.name} gagal dibaca: ${error?.message || 'unknown error'}`);
                    resolve();
                });
                return;
            }
            if (!entry.isDirectory) {
                resolve();
                return;
            }
            const reader = entry.createReader();
            const children = [];
            const readBatch = () => {
                reader.readEntries(async batch => {
                    if (!batch.length) {
                        for (const child of children) {
                            await readDroppedEntry(child, `${parentPath}${entry.name}/`, output, warnings);
                        }
                        resolve();
                        return;
                    }
                    children.push(...batch);
                    readBatch();
                }, error => {
                    warnings.push(`Folder ${parentPath}${entry.name} gagal dibaca: ${error?.message || 'unknown error'}`);
                    resolve();
                });
            };
            readBatch();
        });
    }

    async function enqueueFiles(files) {
        if (state.hydrationPromise) await state.hydrationPromise;
        if (!files.length || state.processing) {
            if (state.processing) reports()?.notify('Tunggu antrean saat ini selesai diproses.', true);
            return;
        }
        if (files.length > MAX_BATCH_FILES) {
            reports()?.notify(`Batch berisi ${files.length} file; batas aman ${MAX_BATCH_FILES} file. Pecah folder menjadi beberapa batch.`, true);
            return;
        }
        const batchBytes = files.reduce((sum, file) => sum + Number(file.size || 0), 0);
        if (batchBytes > MAX_BATCH_BYTES) {
            reports()?.notify(
                `Total batch ${formatBytes(batchBytes)} melewati batas aman ${formatBytes(MAX_BATCH_BYTES)}.`,
                true
            );
            return;
        }
        state.processing = true;
        const ocrMode = document.getElementById('documentOcrMode')?.value || 'auto';
        const batchId = globalThis.crypto?.randomUUID?.() || `BATCH-${Date.now()}`;
        state.currentBatch = {
            batchId,
            startedAt: new Date().toISOString(),
            enumerated: files.length,
            processed: 0,
            failed: 0,
            warnings: [...state.enumerationWarnings],
            complete: false
        };
        state.enumerationWarnings = [];
        const queueIds = [];
        files.forEach((file, index) => {
            const importId = `QUEUE-${Date.now()}-${index}`;
            queueIds.push(importId);
            state.summaries.unshift({
                importId,
                fileName: file.name,
                relativePath: file.webkitRelativePath || file.relativePath || file.name,
                extension: file.name.match(/\.[^.]+$/)?.[0]?.toLowerCase() || '',
                size: file.size,
                createdAt: new Date().toISOString(),
                status: 'queued',
                progress: { current: 0, total: 1, message: 'Menunggu antrean' },
                batchId,
                batchExpected: files.length,
                transient: true
            });
        });
        state.selectedId = queueIds[0];
        render();

        for (let index = 0; index < files.length; index += 1) {
            const file = files[index];
            const queueId = queueIds[index];
            updateTransient(queueId, {
                status: 'processing',
                progress: { current: 0, total: 1, message: 'Menyiapkan parser…' }
            });
            try {
                const extracted = await engine().extractFile(file, { ocrMode }, progress => {
                    updateTransient(queueId, { status: 'processing', progress }, false);
                });
                const schemas = reports()?.getSchemas?.() || [];
                const record = engine().analyzeImport(extracted, schemas);
                record.batchId = batchId;
                record.batchExpected = files.length;
                const duplicate = state.summaries.find(summary => (
                    !summary.transient
                    && summary.sha256
                    && summary.sha256 === record.source.sha256
                    && summary.importId !== record.importId
                ));
                if (duplicate) {
                    record.duplicateOf = duplicate.importId;
                    record.quality.warnings.push({
                        code: 'duplicate_sha256',
                        message: `Isi file identik dengan ${duplicate.fileName}. Keduanya tetap tercatat untuk audit folder.`,
                        severity: 'info',
                        sourceRef: record.source.fileName
                    });
                }
                removeTransient(queueId);
                await saveRecord(record);
                state.selectedId = record.importId;
                state.currentBatch.processed += 1;
            } catch (error) {
                const failed = {
                    importId: queueId,
                    fileName: file.name,
                    relativePath: file.webkitRelativePath || file.relativePath || file.name,
                    extension: file.name.match(/\.[^.]+$/)?.[0]?.toLowerCase() || '',
                    size: file.size,
                    createdAt: new Date().toISOString(),
                    updatedAt: new Date().toISOString(),
                    status: 'failed',
                    error: error.message,
                    batchId,
                    batchExpected: files.length,
                    transient: false
                };
                const summaryIndex = state.summaries.findIndex(item => item.importId === queueId);
                if (summaryIndex >= 0) state.summaries[summaryIndex] = failed;
                await saveSummary(failed);
                state.selectedId = queueId;
                state.currentBatch.processed += 1;
                state.currentBatch.failed += 1;
            }
            render();
        }
        state.processing = false;
        state.currentBatch.complete = true;
        state.currentBatch.completedAt = new Date().toISOString();
        render();
    }

    function updateTransient(importId, values, shouldRender = true) {
        const summary = state.summaries.find(item => item.importId === importId);
        if (summary) Object.assign(summary, values);
        if (shouldRender) renderQueue();
        else {
            const card = document.querySelector(`[data-import-id="${CSS.escape(importId)}"]`);
            if (card) {
                const progress = card.querySelector('.import-card-progress span');
                const label = card.querySelector('.import-card-progress small');
                const current = Number(values.progress?.current || 0);
                const total = Number(values.progress?.total || 1);
                if (progress) progress.style.width = `${Math.round(current / Math.max(total, 1) * 100)}%`;
                if (label) label.textContent = values.progress?.message || 'Memproses…';
            }
        }
    }

    function removeTransient(importId) {
        state.summaries = state.summaries.filter(item => item.importId !== importId);
    }

    function render() {
        renderStorageWarning();
        renderBatchSummary();
        renderQueue();
        renderDetail();
        updateImportCount();
    }

    function renderStorageWarning() {
        const container = document.getElementById('importStorageWarning');
        if (!container) return;
        container.innerHTML = state.storageWarning
            ? `<div class="import-inline-alert danger"><i class="fa-solid fa-database"></i><span>${escapeHtml(state.storageWarning)}</span></div>`
            : '';
    }

    function renderBatchSummary() {
        const container = document.getElementById('importBatchSummary');
        if (!container) return;
        const persistent = state.summaries.filter(item => !item.transient);
        if (!persistent.length && !state.currentBatch) {
            container.innerHTML = '';
            return;
        }
        const counts = persistent.reduce((result, item) => {
            result[item.status] = (result[item.status] || 0) + 1;
            return result;
        }, {});
        const totalBytes = persistent.reduce((sum, item) => sum + Number(item.size || 0), 0);
        const batch = state.currentBatch;
        container.innerHTML = `
            ${batch ? `
                <div class="import-batch-manifest ${batch.complete && batch.failed === 0 && !batch.warnings.length ? 'complete' : ''}">
                    <i class="fa-solid ${batch.complete ? 'fa-clipboard-check' : 'fa-spinner fa-spin'}"></i>
                    <div>
                        <strong>Batch ${escapeHtml(batch.batchId.slice(0, 8))}: ${batch.processed}/${batch.enumerated} file diproses</strong>
                        <span>${batch.failed} gagal · ${batch.warnings.length} masalah enumerasi${batch.complete ? ' · audit selesai' : ' · sedang berjalan'}</span>
                        ${batch.warnings.length ? `<small>${escapeHtml(batch.warnings.slice(0, 3).join(' · '))}</small>` : ''}
                    </div>
                </div>
            ` : ''}
            <div class="import-summary-strip">
                <div><strong>${persistent.length}</strong><span>FILE DIAUDIT</span></div>
                <div><strong>${counts.ready || 0}</strong><span>SIAP DRAFT</span></div>
                <div><strong>${(counts.review || 0) + (counts.extracted || 0)}</strong><span>PERLU REVIEW</span></div>
                <div><strong>${counts.duplicate || 0}</strong><span>DUPLIKAT HASH</span></div>
                <div><strong>${(counts.unsupported || 0) + (counts.failed || 0)}</strong><span>GAGAL / ARSIP</span></div>
                <div><strong>${formatBytes(totalBytes)}</strong><span>TOTAL SUMBER</span></div>
            </div>
        `;
    }

    function renderQueue() {
        const container = document.getElementById('documentImportQueue');
        const count = document.getElementById('importQueueCount');
        if (!container) return;
        if (count) count.textContent = `${state.summaries.length} item`;
        if (!state.summaries.length) {
            container.innerHTML = `
                <div class="import-empty-queue">
                    <i class="fa-regular fa-folder-open"></i>
                    <strong>Belum ada dokumen</strong>
                    <span>Pilih file atau audit satu folder.</span>
                </div>
            `;
            return;
        }
        container.innerHTML = state.summaries.map(summary => {
            const status = statusMeta(summary.status);
            const progressCurrent = Number(summary.progress?.current || 0);
            const progressTotal = Math.max(1, Number(summary.progress?.total || 1));
            const progressPercent = Math.round(progressCurrent / progressTotal * 100);
            return `
                <button type="button"
                    class="import-queue-card ${state.selectedId === summary.importId ? 'active' : ''}"
                    data-import-id="${escapeHtml(summary.importId)}">
                    <div class="import-file-type">${escapeHtml((summary.extension || '?').replace('.', '').slice(0, 4).toUpperCase())}</div>
                    <div class="import-queue-copy">
                        <strong title="${escapeHtml(summary.relativePath || summary.fileName)}">${escapeHtml(summary.fileName)}</strong>
                        <span>${formatBytes(summary.size)} · ${escapeHtml(summary.target?.code || 'Belum dipetakan')}</span>
                        <em class="import-status ${status.className}"><i class="fa-solid ${status.icon}"></i>${status.label}</em>
                        ${summary.status === 'processing' || summary.status === 'queued' ? `
                            <div class="import-card-progress" role="progressbar" aria-label="Progres ${escapeHtml(summary.fileName)}"
                                aria-valuemin="0" aria-valuemax="100" aria-valuenow="${progressPercent}">
                                <div><span style="width:${progressPercent}%"></span></div>
                                <small>${escapeHtml(summary.progress?.message || 'Memproses…')}</small>
                            </div>
                        ` : ''}
                    </div>
                </button>
            `;
        }).join('');
        container.querySelectorAll('[data-import-id]').forEach(button => {
            button.addEventListener('click', async () => {
                state.selectedId = button.dataset.importId;
                state.detailTab = 'mapping';
                state.tableIndex = 0;
                renderQueue();
                await renderDetail();
            });
        });
    }

    async function renderDetail() {
        const container = document.getElementById('documentImportDetail');
        if (!container) return;
        const generation = ++state.detailGeneration;
        if (!state.selectedId) {
            container.innerHTML = `
                <div class="import-detail-empty">
                    <i class="fa-solid fa-file-circle-check"></i>
                    <h2>Review hasil ekstraksi</h2>
                    <p>Pilih salah satu dokumen pada antrean untuk melihat kecocokan template, provenance, tabel, warning, dan paket database.</p>
                </div>
            `;
            return;
        }
        const summary = state.summaries.find(item => item.importId === state.selectedId);
        if (!summary) {
            state.selectedId = null;
            renderDetail();
            return;
        }
        if (summary.status === 'processing' || summary.status === 'queued') {
            container.innerHTML = `
                <div class="import-detail-empty processing">
                    <i class="fa-solid fa-gears"></i>
                    <h2>${escapeHtml(summary.fileName)}</h2>
                    <p>${escapeHtml(summary.progress?.message || 'Dokumen sedang diproses…')}</p>
                </div>
            `;
            return;
        }
        if (summary.status === 'failed') {
            container.innerHTML = `
                <div class="import-detail-empty failed">
                    <i class="fa-solid fa-circle-xmark"></i>
                    <h2>Ekstraksi gagal</h2>
                    <p>${escapeHtml(summary.error || 'Terjadi kesalahan yang tidak diketahui.')}</p>
                    <button type="button" class="import-danger-button" data-delete-import="${escapeHtml(summary.importId)}">
                        <i class="fa-regular fa-trash-can"></i> Hapus catatan
                    </button>
                </div>
            `;
            bindDeleteButtons(container);
            return;
        }

        container.innerHTML = `
            <div class="import-detail-loading">
                <i class="fa-solid fa-spinner fa-spin"></i> Memuat hasil ekstraksi…
            </div>
        `;
        const record = await loadRecord(summary.importId);
        if (
            generation !== state.detailGeneration
            || state.selectedId !== summary.importId
            || !container.isConnected
        ) return;
        if (!record) {
            container.innerHTML = `
                <div class="import-detail-empty failed">
                    <i class="fa-solid fa-database"></i>
                    <h2>Detail arsip tidak ditemukan</h2>
                    <p>Ringkasan tersedia, tetapi payload IndexedDB tidak dapat dibaca.</p>
                </div>
            `;
            return;
        }
        renderRecordDetail(container, record);
    }

    function renderRecordDetail(container, record) {
        const schemas = reports()?.getSchemas?.() || [];
        const status = statusMeta(makeSummary(record).status);
        const warningCounts = record.quality.warnings.reduce((result, warning) => {
            result[warning.severity] = (result[warning.severity] || 0) + 1;
            return result;
        }, {});
        const stats = record.extraction.stats || {};
        const unitSummary = record.extraction.format === 'pdf'
            ? `${stats.pagesProcessed || 0}/${stats.pagesExpected || 0} halaman`
            : ['xlsx', 'xls', 'xlsm', 'csv', 'tsv'].includes(record.extraction.format)
                ? `${stats.sheets || 0} sheet · ${(stats.nonEmptyCells || 0).toLocaleString('id-ID')} sel berisi data`
                : record.extraction.format === 'docx'
                    ? `${stats.xmlPartsProcessed || 0} bagian · ${stats.tables || 0} tabel`
                    : `${(stats.fragments || 0).toLocaleString('id-ID')} fragmen`;

        container.innerHTML = `
            <header class="import-detail-header">
                <div class="import-detail-title">
                    <div class="import-file-hero">${escapeHtml(record.source.extension.replace('.', '').toUpperCase())}</div>
                    <div>
                        <div class="import-detail-kicker">${escapeHtml(record.source.relativePath || record.source.fileName)}</div>
                        <h2>${escapeHtml(record.source.fileName)}</h2>
                        <p>${formatBytes(record.source.size)} · ${escapeHtml(unitSummary)} · SHA-256 <code>${escapeHtml(record.source.sha256.slice(0, 16))}…</code></p>
                    </div>
                </div>
                <div class="import-detail-actions">
                    <button type="button" class="import-secondary-button" data-export-import="${escapeHtml(record.importId)}">
                        <i class="fa-solid fa-file-code"></i> Ekspor JSON
                    </button>
                    <button type="button" class="import-danger-icon" data-delete-import="${escapeHtml(record.importId)}" title="Hapus arsip" aria-label="Hapus arsip ${escapeHtml(record.source.fileName)}">
                        <i class="fa-regular fa-trash-can"></i>
                    </button>
                </div>
            </header>
            <div class="import-review-strip">
                <div class="import-template-selector">
                    <label for="importTargetSchema">Tipe laporan tujuan</label>
                    <select id="importTargetSchema" class="report-select">
                        <option value="__none__" ${!record.target && record.classification.mode === 'manual' ? 'selected' : ''}>— Belum ada template yang dipilih —</option>
                        ${schemas.map(schema => `
                            <option value="${escapeHtml(schema.id)}" ${record.target?.schemaId === schema.id ? 'selected' : ''}>
                                ${escapeHtml(schema.code)} — ${escapeHtml(schema.title)}
                            </option>
                        `).join('')}
                    </select>
                    <span>Deteksi ${escapeHtml(confidenceLabel(record.classification.confidence))} · skor ${formatPercent(record.classification.score)}</span>
                </div>
                <div class="import-review-action">
                    <em class="import-status ${status.className}"><i class="fa-solid ${status.icon}"></i>${status.label}</em>
                    <button type="button" class="import-primary-button" data-apply-import="${escapeHtml(record.importId)}"
                        ${record.target && record.extraction.stats.supported !== false ? '' : 'disabled'}>
                        <i class="fa-solid fa-wand-magic-sparkles"></i> ${record.quality.requiresIncompleteOverride ? 'Buat Draft Review' : 'Buat Draft Laporan'}
                    </button>
                </div>
            </div>
            <div class="import-quality-grid">
                ${qualityGauge('Cakupan ekstraksi', record.quality.extractionCoverage, 'Semua halaman/sheet/bagian yang berhasil dibaca')}
                ${qualityGauge('Field wajib terisi', record.quality.requiredCoverage, `${record.quality.filledRequiredFields}/${record.quality.requiredFields} field`)}
                ${qualityGauge('Masuk ke schema', record.quality.mappingCoverage, `${record.quality.mappedFragments}/${record.quality.sourceFragments} fragmen`)}
                <div class="import-quality-card warnings">
                    <span>Audit & konflik</span>
                    <strong>${(warningCounts.error || 0) + (warningCounts.warning || 0)}</strong>
                    <small>${warningCounts.error || 0} error · ${warningCounts.warning || 0} warning · ${record.quality.conflicts || 0} konflik</small>
                </div>
            </div>
            ${record.duplicateOf ? `
                <div class="import-inline-alert">
                    <i class="fa-solid fa-copy"></i>
                    <span>Checksum identik dengan arsip <code>${escapeHtml(record.duplicateOf)}</code>. File tetap dicatat agar audit folder berjumlah lengkap.</span>
                </div>
            ` : ''}
            <div class="import-warning-list">
                ${record.quality.warnings.slice(0, 20).map(warning => `
                    <div class="${escapeHtml(warning.severity)}">
                        <i class="fa-solid ${warning.severity === 'error' ? 'fa-circle-xmark' : warning.severity === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-info'}"></i>
                        <span><strong>${escapeHtml(warning.code)}</strong>${escapeHtml(warning.message)}<small>${escapeHtml(warning.sourceRef || '')}</small></span>
                    </div>
                `).join('')}
            </div>
            <nav class="import-detail-tabs" aria-label="Detail hasil impor">
                ${detailTabButton('mapping', 'fa-diagram-project', 'Pemetaan')}
                ${detailTabButton('content', 'fa-align-left', 'Konten')}
                ${detailTabButton('tables', 'fa-table-cells', `Tabel (${record.extraction.tables.length})`)}
                ${detailTabButton('audit', 'fa-database', 'Audit & Database')}
            </nav>
            <div id="importDetailBody">${detailBody(record, schemas)}</div>
        `;
        bindDetailEvents(container, record, schemas);
    }

    function confidenceLabel(value) {
        const labels = {
            high: 'tinggi',
            medium: 'sedang',
            low: 'rendah',
            none: 'belum meyakinkan',
            manual: 'dipilih manual'
        };
        return labels[value] || value || '—';
    }

    function qualityGauge(label, value, note) {
        const percentage = Math.round(Number(value || 0) * 100);
        const tone = percentage >= 80 ? 'good' : percentage >= 45 ? 'medium' : 'low';
        return `
            <div class="import-quality-card ${tone}">
                <span>${escapeHtml(label)}</span>
                <strong>${percentage}%</strong>
                <div><i style="width:${percentage}%"></i></div>
                <small>${escapeHtml(note)}</small>
            </div>
        `;
    }

    function detailTabButton(tab, icon, label) {
        const selected = state.detailTab === tab;
        return `
            <button type="button" role="tab" aria-selected="${selected}" data-import-detail-tab="${tab}" class="${selected ? 'active' : ''}">
                <i class="fa-solid ${icon}"></i>${escapeHtml(label)}
            </button>
        `;
    }

    function detailBody(record, schemas) {
        if (state.detailTab === 'content') return contentDetail(record);
        if (state.detailTab === 'tables') return tablesDetail(record);
        if (state.detailTab === 'audit') return auditDetail(record);
        return mappingDetail(record, schemas);
    }

    function mappingDetail(record, schemas) {
        if (!record.target) {
            return `
                <div class="import-tab-empty">
                    <i class="fa-solid fa-arrow-up"></i>
                    <strong>Pilih tipe laporan tujuan</strong>
                    <p>Konten sudah diekstrak dan disimpan. Pemilihan template akan menjalankan ulang pemetaan field dan tabel.</p>
                </div>
            `;
        }
        const schema = schemas.find(item => item.id === record.target.schemaId);
        const fields = schema?.fields || [];
        const mappedFields = fields.filter(field => (
            record.mapping.fields[field.key] !== undefined
            && record.mapping.fields[field.key] !== ''
        ));
        const missingFields = fields.filter(field => (
            field.required
            && (record.mapping.fields[field.key] === undefined || record.mapping.fields[field.key] === '')
        ));
        return `
            <div class="import-mapping-summary">
                <div>
                    <strong>${mappedFields.length}</strong>
                    <span>field identitas terisi</span>
                </div>
                <div>
                    <strong>${record.mapping.rows.length}</strong>
                    <span>baris data dibentuk</span>
                </div>
                <div>
                    <strong>${missingFields.length}</strong>
                    <span>field wajib perlu input</span>
                </div>
                <div>
                    <strong>${record.mapping.unmappedCount}</strong>
                    <span>fragmen tetap di arsip</span>
                </div>
            </div>
            <section class="import-detail-section">
                <div class="import-section-title"><h3>Pemetaan field identitas</h3><span>Nilai normalized + sumber asli</span></div>
                <div class="import-mapping-table-wrap">
                    <table class="import-mapping-table">
                        <thead><tr><th>Field laporan</th><th>Nilai hasil</th><th>Confidence</th><th>Sumber</th></tr></thead>
                        <tbody>
                            ${fields.map(field => {
                                const provenance = record.mapping.fieldProvenance[field.key];
                                const value = record.mapping.fields[field.key];
                                return `
                                    <tr class="${value === undefined || value === '' ? 'missing' : ''}">
                                        <td><strong>${escapeHtml(field.label)}</strong>${field.required ? '<small>WAJIB</small>' : ''}</td>
                                        <td>${value !== undefined && value !== '' ? escapeHtml(value) : '<em>Belum ditemukan</em>'}</td>
                                        <td>${provenance ? formatPercent(provenance.confidence) : '—'}</td>
                                        <td><code>${escapeHtml(provenance?.sourceRef || '—')}</code></td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            </section>
            <section class="import-detail-section">
                <div class="import-section-title"><h3>Preview baris laporan</h3><span>${record.mapping.rows.length > 50 ? '50 baris pertama ditampilkan' : 'Semua baris ditampilkan'}</span></div>
                ${mappedRowsTable(record, schema)}
            </section>
            <details class="import-unmapped-details">
                <summary>Preview informasi belum terpetakan (${record.mapping.unmappedCount})</summary>
                <p>Fragmen berikut belum cocok dengan field template, tetapi tetap ada dalam JSON/IndexedDB.</p>
                <div class="import-fragment-list">
                    ${record.mapping.unmappedPreview.slice(0, 100).map(fragment => `
                        <div><code>${escapeHtml(fragment.sourceRef)}</code><span>${escapeHtml(fragment.value)}</span></div>
                    `).join('') || '<em>Tidak ada fragmen.</em>'}
                </div>
            </details>
        `;
    }

    function mappedRowsTable(record, schema) {
        if (!record.mapping.rows.length) {
            return `<div class="import-tab-empty compact"><p>Belum ada tabel sumber yang cukup cocok dengan kolom ${escapeHtml(schema?.title || '')}.</p></div>`;
        }
        const columns = (schema?.columns || []).filter(column => !column.readonly);
        return `
            <div class="import-source-table-wrap">
                <table class="import-source-table">
                    <thead><tr>${columns.map(column => `<th>${escapeHtml(column.label)}</th>`).join('')}</tr></thead>
                    <tbody>
                        ${record.mapping.rows.slice(0, 50).map(row => `
                            <tr>${columns.map(column => `<td>${escapeHtml(row[column.key] ?? '')}</td>`).join('')}</tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    function contentDetail(record) {
        return `
            <div class="import-content-toolbar">
                <div>
                    <strong>${record.extraction.fragments.length.toLocaleString('id-ID')} fragmen dipertahankan</strong>
                    <span>Ditampilkan maksimal 500; gunakan pencarian untuk memfilter preview.</span>
                </div>
                <input type="search" class="report-input" id="importContentSearch" placeholder="Cari isi atau source reference…">
            </div>
            <div class="import-fragment-list large" id="importContentList">
                ${fragmentListMarkup(record.extraction.fragments.slice(0, 500))}
            </div>
        `;
    }

    function fragmentListMarkup(fragments) {
        return fragments.map(fragment => `
            <div>
                <code>${escapeHtml(fragment.sourceRef)}</code>
                <span>${escapeHtml(fragment.value)}</span>
                <small>${escapeHtml(fragment.kind)}</small>
            </div>
        `).join('') || '<div class="import-tab-empty compact"><p>Tidak ada teks yang berhasil diekstrak.</p></div>';
    }

    function tablesDetail(record) {
        const tables = record.extraction.tables;
        if (!tables.length) {
            return `<div class="import-tab-empty"><i class="fa-solid fa-table-cells"></i><strong>Tabel tidak terdeteksi</strong><p>Konten teks tetap tersedia pada tab Konten.</p></div>`;
        }
        state.tableIndex = Math.min(state.tableIndex, tables.length - 1);
        const table = tables[state.tableIndex];
        const maxColumns = Math.min(30, table.headers?.length || 0);
        return `
            <div class="import-table-toolbar">
                <div>
                    <label for="importTableSelect">Tabel sumber</label>
                    <select id="importTableSelect" class="report-select">
                        ${tables.map((item, index) => `
                            <option value="${index}" ${index === state.tableIndex ? 'selected' : ''}>
                                ${escapeHtml(item.title)} — ${(item.rows || []).length} baris
                            </option>
                        `).join('')}
                    </select>
                </div>
                <span><code>${escapeHtml(table.sourceRef)}</code></span>
            </div>
            <div class="import-source-table-wrap">
                <table class="import-source-table">
                    <thead><tr>${(table.headers || []).slice(0, maxColumns).map(header => `<th>${escapeHtml(header)}</th>`).join('')}</tr></thead>
                    <tbody>
                        ${(table.rows || []).slice(0, 100).map(row => `
                            <tr>${(row.values || []).slice(0, maxColumns).map(value => `<td>${escapeHtml(value)}</td>`).join('')}</tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            <p class="import-table-note">${(table.rows || []).length > 100 ? 'Preview dibatasi 100 baris; seluruh baris tetap ada pada ekspor JSON.' : 'Seluruh baris tabel ini ditampilkan.'}${(table.headers || []).length > 30 ? ' Preview dibatasi 30 kolom.' : ''}</p>
        `;
    }

    function auditDetail(record) {
        const databasePreview = {
            schemaVersion: record.schemaVersion,
            importJob: {
                id: record.importId,
                status: record.quality.errors ? 'review_required' : 'extracted'
            },
            sourceDocument: {
                fileName: record.source.fileName,
                sha256: record.source.sha256,
                size: record.source.size
            },
            target: record.target,
            counts: {
                sections: record.extraction.sections.length,
                tables: record.extraction.tables.length,
                fragments: record.extraction.fragments.length,
                artifacts: record.extraction.artifacts.length,
                mappedRows: record.mapping.rows.length
            }
        };
        return `
            <div class="import-audit-grid">
                <section class="import-detail-section">
                    <div class="import-section-title"><h3>Coverage manifest</h3><span>Parser ${escapeHtml(record.extraction.parser.name)}</span></div>
                    <pre>${escapeHtml(JSON.stringify(record.extraction.stats, null, 2))}</pre>
                </section>
                <section class="import-detail-section">
                    <div class="import-section-title"><h3>Envelope database</h3><span>Versi ${escapeHtml(record.schemaVersion)}</span></div>
                    <pre>${escapeHtml(JSON.stringify(databasePreview, null, 2))}</pre>
                </section>
            </div>
            <section class="import-detail-section">
                <div class="import-section-title"><h3>Artefak & bagian non-teks</h3><span>${record.extraction.artifacts.length} item</span></div>
                <div class="import-artifact-list">
                    ${record.extraction.artifacts.slice(0, 300).map(artifact => `
                        <div><i class="fa-regular fa-file"></i><span><strong>${escapeHtml(artifact.name)}</strong><small>${escapeHtml(artifact.kind)} · ${formatBytes(artifact.size)} · ${escapeHtml(artifact.sourceRef)}</small></span></div>
                    `).join('') || '<em>Tidak ada artefak tambahan.</em>'}
                </div>
            </section>
            <div class="import-database-note">
                <i class="fa-solid fa-code-branch"></i>
                <div>
                    <strong>Siap dipindahkan ke staging database</strong>
                    <p>Envelope memisahkan source document, extraction fragments, field/row mapping, provenance, quality, serta warning. File asli nantinya disimpan di object storage; JSON ini masuk ke tabel staging sebelum menjadi report final.</p>
                </div>
                <button type="button" class="import-primary-button" data-export-import="${escapeHtml(record.importId)}">
                    <i class="fa-solid fa-download"></i> Unduh Envelope Lengkap
                </button>
            </div>
        `;
    }

    function bindDetailEvents(container, record, schemas) {
        container.querySelectorAll('[data-import-detail-tab]').forEach(button => {
            button.addEventListener('click', () => {
                state.detailTab = button.dataset.importDetailTab;
                renderRecordDetail(container, record);
            });
        });
        const schemaSelect = container.querySelector('#importTargetSchema');
        schemaSelect?.addEventListener('change', async () => {
            const targetSchemaId = schemaSelect.value;
            const mutableButtons = [
                ...container.querySelectorAll(
                    '[data-apply-import], [data-export-import], [data-delete-import]'
                )
            ];
            const disabledStates = mutableButtons.map(button => [button, button.disabled]);
            schemaSelect.disabled = true;
            schemaSelect.setAttribute('aria-busy', 'true');
            mutableButtons.forEach(button => {
                button.disabled = true;
            });
            try {
                const reanalyzed = engine().analyzeImport(
                    { source: record.source, extraction: record.extraction },
                    schemas,
                    { targetSchemaId }
                );
                reanalyzed.importId = record.importId;
                reanalyzed.createdAt = record.createdAt;
                reanalyzed.updatedAt = new Date().toISOString();
                reanalyzed.duplicateOf = record.duplicateOf || null;
                await saveRecord(reanalyzed);
                render();
            } catch (error) {
                reports()?.notify(`Pemetaan ulang gagal: ${error.message}`, true);
                if (schemaSelect.isConnected) {
                    schemaSelect.disabled = false;
                    schemaSelect.removeAttribute('aria-busy');
                    disabledStates.forEach(([button, wasDisabled]) => {
                        if (button.isConnected) button.disabled = wasDisabled;
                    });
                }
            }
        });
        container.querySelectorAll('[data-apply-import]').forEach(button => {
            button.addEventListener('click', () => {
                const latestRecord = state.records.get(record.importId) || record;
                applyToDraft(latestRecord, button);
            });
        });
        bindDeleteButtons(container);
        container.querySelectorAll('[data-export-import]').forEach(button => {
            button.addEventListener('click', () => exportRecord(record, button));
        });
        const contentSearch = container.querySelector('#importContentSearch');
        let searchTimer = null;
        contentSearch?.addEventListener('input', () => {
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(() => {
                const query = contentSearch.value.trim().toLocaleLowerCase('id');
                const filtered = [];
                for (const fragment of record.extraction.fragments) {
                    if (
                        !query
                        || `${fragment.value} ${fragment.sourceRef} ${fragment.kind}`
                            .toLocaleLowerCase('id')
                            .includes(query)
                    ) {
                        filtered.push(fragment);
                        if (filtered.length >= 500) break;
                    }
                }
                const list = container.querySelector('#importContentList');
                if (list) list.innerHTML = fragmentListMarkup(filtered);
            }, 180);
        });
        const tableSelect = container.querySelector('#importTableSelect');
        tableSelect?.addEventListener('change', () => {
            state.tableIndex = Number(tableSelect.value || 0);
            renderRecordDetail(container, record);
        });
    }

    function bindDeleteButtons(container) {
        container.querySelectorAll('[data-delete-import]').forEach(button => {
            button.addEventListener('click', async () => {
                const summary = state.summaries.find(
                    item => item.importId === button.dataset.deleteImport
                );
                const referenced = reports()?.isImportReferenced?.(button.dataset.deleteImport);
                const confirmed = window.confirm(
                    `Hapus arsip impor “${summary?.fileName || button.dataset.deleteImport}”?\n\n`
                    + `${referenced ? 'Arsip ini masih direferensikan oleh draft/laporan; provenance detail tidak lagi dapat dibuka. ' : ''}`
                    + 'Hasil ekstraksi di browser ini akan dihapus. File sumber asli tidak berubah.'
                );
                if (!confirmed) return;
                button.disabled = true;
                try {
                    await deleteRecord(button.dataset.deleteImport);
                } catch (error) {
                    button.disabled = false;
                    reports()?.notify(`Arsip gagal dihapus: ${error.message}`, true);
                }
            });
        });
    }

    function applyToDraft(record, button) {
        if (!record.target) {
            reports()?.notify('Pilih tipe laporan tujuan terlebih dahulu.', true);
            return;
        }
        if (record.quality.errors) {
            const confirmed = window.confirm(
                `Ekstraksi “${record.source.fileName}” memiliki ${record.quality.errors} error audit.\n\n`
                + 'Draft akan tetap berstatus review dan tidak difinalkan otomatis. Lanjutkan membuat draft parsial?'
            );
            if (!confirmed) return;
        }
        const draftState = reports()?.getDraftState?.(record.target.schemaId);
        if (draftState?.hasData) {
            const confirmed = window.confirm(
                `Template ${record.target.code} sudah memiliki draft.\n\nGanti draft tersebut dengan hasil ekstraksi “${record.source.fileName}”?`
            );
            if (!confirmed) return;
        }
        try {
            button.disabled = true;
            const payload = engine().prepareDraftPayload(record);
            const totalMappedRows = payload.rows.length;
            if (totalMappedRows > MAX_DRAFT_ROWS) {
                payload.rows = payload.rows.slice(0, MAX_DRAFT_ROWS);
                payload.importSource.totalMappedRows = totalMappedRows;
                payload.importSource.appliedRows = MAX_DRAFT_ROWS;
                payload.importSource.rowsLimited = true;
            }
            reports().importDraft(payload, { replace: true });
        } catch (error) {
            button.disabled = false;
            reports()?.notify(`Draft gagal dibuat: ${error.message}`, true);
        }
    }

    function safeDownloadName(value) {
        return String(value || 'dokumen')
            .replace(/\.[^.]+$/, '')
            .replace(/[<>:"/\\|?*\u0000-\u001F]/g, '_')
            .slice(0, 120);
    }

    async function exportRecord(record, button) {
        const original = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyusun JSON…';
        await new Promise(resolve => setTimeout(resolve, 30));
        try {
            const envelope = engine().buildDatabaseEnvelope(record);
            const json = JSON.stringify(envelope);
            const blob = new Blob([json], { type: 'application/json;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `${safeDownloadName(record.source.fileName)}_extraction_${record.source.sha256.slice(0, 8)}.json`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.setTimeout(() => URL.revokeObjectURL(url), 1000);
            reports()?.notify(`Envelope JSON berhasil dibuat (${formatBytes(blob.size)}).`);
        } catch (error) {
            reports()?.notify(`Ekspor JSON gagal: ${error.message}`, true);
        } finally {
            button.disabled = false;
            button.innerHTML = original;
        }
    }

    async function initialize() {
        if (state.initialized) return;
        if (!engine() || !reports()) {
            const module = document.getElementById('documentImportModule');
            if (module) {
                module.innerHTML = `
                    <div class="import-inline-alert danger">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <span>Mesin impor atau API laporan tidak berhasil dimuat.</span>
                    </div>
                `;
            }
            return;
        }
        state.initialized = true;
        createMarkup();
        state.hydrating = true;
        setImportControlsDisabled(true);
        state.hydrationPromise = hydrateSummaries();
        try {
            await state.hydrationPromise;
        } finally {
            state.hydrating = false;
            setImportControlsDisabled(false);
            render();
        }
    }

    document.addEventListener('fleetreport:import-visible', initialize);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            if (document.getElementById('documentImportModule')) initialize();
        });
    } else if (document.getElementById('documentImportModule')) {
        initialize();
    }
})();
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
            unitId: 'DT-00027 - B 9136 ZYT',
            category: 'Excavator',
            operator: 'Budi Santoso',
            nrp: 'OP-BRA-089',
            site: 'Yard Duri',
            hmStart: 8450.0,
            hmEnd: 8458.5,
            status: 'LULUS DENGAN CATATAN',
            criticalFails: 0,
            warnings: 1,
            notes: 'Semua item kritikal normal. Ditemukan baut cover pelindung agak kendur, sudah dikencangkan.',
            details: {}
        },
        {
            id: 'P2H-20260727-002',
            date: '2026-07-27 07:10',
            unitId: 'DT-00050 - B 9105 ZYT',
            category: 'Excavator',
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
            unitId: 'DT-04024 - BM 9285 JO',
            category: 'Excavator',
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
                    if (window.setIntegratedAssetStatus) {
                        window.setIntegratedAssetStatus(assetId, 'BREAKDOWN', 'Inspeksi & P2H', newP2H.id, newP2H.notes);
                    } else {
                        targetAsset.status = 'BREAKDOWN';
                    }
                } else if (warningsCount > 0) {
                    const hasBlockingWo = (window.globalData.work_orders || []).some(wo =>
                        wo.assetId === assetId && wo.status !== 'Closed' && targetAsset.status === 'BREAKDOWN'
                    );
                    if (!hasBlockingWo) {
                        if (window.setIntegratedAssetStatus) {
                            window.setIntegratedAssetStatus(assetId, 'INSPEKSI', 'Inspeksi & P2H', newP2H.id, newP2H.notes);
                        } else {
                            targetAsset.status = 'INSPEKSI';
                        }
                    }
                } else {
                    const hasActiveWo = (window.globalData.work_orders || []).some(wo => wo.assetId === assetId && wo.status !== 'Closed');
                    if (!hasActiveWo) {
                        if (window.setIntegratedAssetStatus) {
                            window.setIntegratedAssetStatus(assetId, 'READY', 'Inspeksi & P2H', newP2H.id, 'P2H lulus tanpa temuan aktif.');
                        } else {
                            targetAsset.status = 'READY';
                        }
                    }
                }
            }

            // AUTO-WORK ORDER TRIGGER RULE ENGINE
            if (isCriticalFail) {
                if (!window.globalData.work_orders) window.globalData.work_orders = [];
                const existingWo = window.globalData.work_orders.find(wo => wo.assetId === assetId && wo.status !== 'Closed');
                const autoWoId = existingWo ? existingWo.woId : 'WO-P2H-' + Math.floor(1000 + Math.random() * 9000);
                if (!existingWo) {
                    window.globalData.work_orders.unshift({
                        woId: autoWoId,
                        assetId: assetId,
                        date: new Date().toISOString().slice(0, 10),
                        status: 'Open',
                        priority: 'High',
                        type: 'Breakdown / Inspection Finding',
                        description: `[AUTO-WO VIA P2H] Unit ditahan akibat temuan kritikal pada P2H (${newP2H.id}). Temuan: ${failedItemsList.join('; ')}`,
                        issue: `[AUTO-WO VIA P2H] Unit ditahan akibat temuan kritikal pada P2H (${newP2H.id}). Temuan: ${failedItemsList.join('; ')}`,
                        downtime: 0,
                        assignedTo: 'Mekanik Shift 1',
                        source: 'P2H Trigger'
                    });
                }
                if (targetAsset) targetAsset.statusReference = autoWoId;

                alert(`⚠️ ATENSI AUTO-WO!\n\nP2H ditolak karena ditemukan ${criticalFailsCount} item kritikal.\n\nSistem otomatis:\n1. Mengubah status unit ${assetId} menjadi BREAKDOWN\n2. ${existingWo ? 'Menautkan temuan ke Work Order aktif' : 'Membuat tiket Work Order darurat'} #${autoWoId}`);
            } else {
                alert(`✅ Successful!\n\nForm P2H #${newP2H.id} untuk unit ${assetId} berhasil disimpan dengan status: ${statusLabel}.`);
            }
        } else {
            alert(`✅ Form P2H #${newP2H.id} berhasil disimpan.`);
        }

        renderHistoryTable();
        updateKpiSummary();
        window.syncFleetState?.({ refreshInspection: false });
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

    window.FleetInspectionModule = {
        refresh() {
            if (window.globalData) {
                if (!Array.isArray(window.globalData.inspections)) {
                    window.globalData.inspections = [...inspectionHistory];
                }
                populateAssetDropdown();
                renderHistoryTable();
                updateKpiSummary();
            }
        },
        openForAsset(assetId, mode = 'history') {
            this.refresh();
            if (mode === 'form') {
                window.switchP2HTab('form');
                const select = document.getElementById('p2hFormAssetSelect');
                if (select) {
                    select.value = assetId;
                    window.onP2HAssetSelectChange(assetId);
                }
                return;
            }
            window.switchP2HTab('history');
            const search = document.getElementById('searchP2HHistory');
            if (search) {
                search.value = assetId;
                window.filterP2HHistoryTable();
            }
        }
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
                        <div class="panel-header" style="display:flex; justify-content:space-between; align-items:center;">
                            <span><i class="fa-solid fa-user-clock"></i> Jam Kerja Aktual Mekanik</span>
                            <span style="font-size:0.75rem; color:var(--text-muted); font-weight:normal;">
                                <span style="display:inline-block; width:16px; border-top:2px dashed #dc2626; margin-right:4px; vertical-align:middle;"></span> Baseline Target (176 Jam)
                            </span>
                        </div>
                        <div class="panel-body" style="padding:15px;">
                            <div style="margin-bottom:15px;">
                                <!-- Shared Track Container (150px height) -->
                                <div style="position:relative; height:150px; width:100%; border-bottom:2px solid var(--border); box-sizing:border-box;">
                                    
                                    <!-- Red Dashed Baseline Line at EXACT 132px from bottom (176 / 200 * 150px = 132px) -->
                                    <div style="position:absolute; bottom:132px; left:0; right:0; border-top:2px dashed #dc2626; z-index:10; pointer-events:none;" title="Target Baseline: 176 Jam">
                                        <span style="position:absolute; right:4px; top:-16px; font-size:0.68rem; font-weight:bold; color:#dc2626; background:#ffffff; padding:1px 6px; border-radius:3px; border:1px solid #fca5a5; box-shadow:0 1px 3px rgba(0,0,0,0.1);">Target: 176 Jam</span>
                                    </div>

                                    <!-- Bars Grid (Full 150px track height) -->
                                    <div style="display:flex; justify-content:space-around; align-items:flex-end; height:150px; width:100%; position:relative; z-index:5;">
                                        ${mechanicProductivityData.map(m => {
                                            const barPx = Math.round((m.actual / 200) * 150);
                                            const isExceed = m.actual >= m.target;
                                            let barBg = '#0284c7';
                                            if (!isExceed) {
                                                if (m.pct >= 95) barBg = '#38bdf8';
                                                else if (m.pct >= 88) barBg = '#f59e0b';
                                                else barBg = '#ef4444';
                                            }
                                            return `
                                                <div style="display:flex; flex-direction:column; align-items:center; justify-content:flex-end; height:100%; width:18%;">
                                                    <div class="bar-actual" style="width:34px; height:${barPx}px; background-color:${barBg} !important; border-radius:4px 4px 0 0; box-shadow:0 3px 6px rgba(0,0,0,0.18); transition:all 0.3s ease; cursor:pointer; display:flex; justify-content:center; align-items:flex-start; padding-top:4px;" title="${escapeHtml(m.name)}: ${m.actual} Jam (${m.pct}% dari Target 176 Jam)">
                                                        <span style="font-size:0.7rem; font-weight:bold; color:#ffffff; text-shadow:0 1px 2px rgba(0,0,0,0.6);">${m.actual}j</span>
                                                    </div>
                                                </div>
                                            `;
                                        }).join('')}
                                    </div>
                                </div>

                                <!-- Mechanic Names under the bottom border -->
                                <div style="display:flex; justify-content:space-around; width:100%; margin-top:8px;">
                                    ${mechanicProductivityData.map(m => `
                                        <div style="width:18%; text-align:center; font-size:0.8rem; color:var(--text-main); font-weight:700;">${escapeHtml(m.name)}</div>
                                    `).join('')}
                                </div>
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

    // Expose Globally
    window.initExecutiveAnalyticsPanels = initExecutiveAnalyticsPanels;

    // Auto Mount
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initExecutiveAnalyticsPanels);
    } else {
        initExecutiveAnalyticsPanels();
    }
})();
