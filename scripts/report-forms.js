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
            title: 'Buku Harian Warehouse — Barang Masuk',
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
            title: 'Buku Harian Warehouse — Barang Keluar',
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
            title: 'P2H — Hydraulic Excavator',
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
            title: 'P2H — Single Drum Rollers',
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
            title: 'PPB — Pesanan Pembelian/Pengadaan Barang',
            description: 'Pesanan pembelian dengan harga, PPN 11%, nilai total, dan penyerahan.',
            source: 'FORM_PPB_Pengadaan_Barang_Tabulasi.md',
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
            title: 'SPB — Surat Permintaan Barang',
            description: 'Permintaan barang atau spare parts dari project kepada bagian logistik.',
            source: 'FORM_SPB_Permintaan_Parts_Tabulasi.md',
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
            title: 'Surat Perintah Lembur — Yard',
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
            title: 'Surat Permintaan Parts Urgent — Yard',
            description: 'Permintaan parts mendesak disertai HM, analisa kerusakan, dan solusi.',
            source: 'FORM_SPPU_Yard_Tabulasi.md',
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

    let currentPage = 1;
    let activeSchema = null;
    let activeDraft = null;
    const pageSize = 8;
    const storagePrefix = 'fleetmonitor-report-draft-';

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
            <section class="form-workspace" id="formWorkspace"></section>
            <div class="report-toast" id="reportToast"><i class="fa-solid fa-circle-check"></i><span></span></div>
        `;

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

        const queryRoute = new URLSearchParams(window.location.search).get('view');
        const route = window.location.hash.match(/^#reports(?:\/([a-z0-9-]+))?$/i)
            || (queryRoute ? queryRoute.match(/^reports(?:\/([a-z0-9-]+))?$/i) : null);
        if (route) {
            document.querySelectorAll('.view-section').forEach(section => section.classList.remove('active'));
            document.getElementById('view-reports').classList.add('active');
            document.querySelectorAll('.sidebar-menu a').forEach(link => link.classList.remove('active'));
            document.getElementById('menu-reports').classList.add('active');
            if (route[1] && formSchemas.some(item => item.id === route[1])) openForm(route[1]);
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
                    <div class="report-card-description">${escapeHtml(item.description)}</div>
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
        const seedRows = schema.seedRows || [{}];
        return stored && stored.fields && Array.isArray(stored.rows)
            ? stored
            : { fields: {}, rows: seedRows.map(row => ({ ...row })), updatedAt: null };
    }

    function saveDraft(showMessage = false) {
        if (!activeSchema || !activeDraft) return;
        activeDraft.updatedAt = new Date().toISOString();
        try {
            localStorage.setItem(storagePrefix + activeSchema.id, JSON.stringify(activeDraft));
            const badge = document.getElementById('autosaveBadge');
            if (badge) badge.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> Tersimpan lokal';
            if (showMessage) showToast('Draft berhasil disimpan di perangkat ini.');
        } catch (error) {
            if (showMessage) showToast('Penyimpanan lokal tidak tersedia pada browser ini.', true);
        }
    }

    function formControl(item, value) {
        const required = item.required ? 'required' : '';
        const placeholder = item.placeholder ? `placeholder="${escapeHtml(item.placeholder)}"` : '';
        if (item.type === 'textarea') {
            return `<textarea class="builder-input" data-field="${escapeHtml(item.key)}" ${required} ${placeholder}>${escapeHtml(value)}</textarea>`;
        }
        if (item.type === 'select') {
            return `<select class="builder-input" data-field="${escapeHtml(item.key)}" ${required}>
                <option value="">Pilih...</option>
                ${item.options.map(option => `<option value="${escapeHtml(option)}" ${value === option ? 'selected' : ''}>${escapeHtml(option)}</option>`).join('')}
            </select>`;
        }
        return `<input class="builder-input" data-field="${escapeHtml(item.key)}" type="${escapeHtml(item.type)}" value="${escapeHtml(value)}" ${required} ${placeholder}>`;
    }

    function tableControl(item, value, rowIndex) {
        const common = `class="table-cell-input" data-row="${rowIndex}" data-key="${escapeHtml(item.key)}"`;
        if (item.type === 'select') {
            return `<select ${common}>
                <option value="">Pilih...</option>
                ${item.options.map(option => `<option value="${escapeHtml(option)}" ${value === option ? 'selected' : ''}>${escapeHtml(option)}</option>`).join('')}
            </select>`;
        }
        return `<input ${common} type="${escapeHtml(item.type)}" value="${escapeHtml(value)}" ${item.readonly ? 'readonly tabindex="-1"' : ''}>`;
    }

    function renderFields() {
        return activeSchema.fields.map(item => `
            <div class="builder-field ${item.full ? 'full' : ''}">
                <label>${escapeHtml(item.label)}${item.required ? '<span class="required-mark">*</span>' : ''}</label>
                ${formControl(item, activeDraft.fields[item.key] || '')}
            </div>
        `).join('');
    }

    function renderRows() {
        const tbody = document.getElementById('formRows');
        if (!tbody) return;
        tbody.innerHTML = activeDraft.rows.map((row, rowIndex) => `
            <tr>
                <td style="width:42px;text-align:center;color:#7b8798">${rowIndex + 1}</td>
                ${activeSchema.columns.map(item => `<td>${tableControl(item, row[item.key] == null ? '' : row[item.key], rowIndex)}</td>`).join('')}
                <td style="width:42px"><button class="row-remove-btn" data-remove-row="${rowIndex}" title="Hapus baris"><i class="fa-solid fa-trash-can"></i></button></td>
            </tr>
        `).join('');
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

    function summaryMarkup() {
        if (!activeSchema.calculation) return '';
        if (activeSchema.calculation === 'ppb') {
            const subtotal = activeDraft.rows.reduce((sum, row) => sum + numberValue(row.total), 0);
            const ppn = subtotal * 0.11;
            return `
                <div class="summary-line"><span>Subtotal</span><strong>${formatRupiah(subtotal)}</strong></div>
                <div class="summary-line"><span>PPN 11%</span><strong>${formatRupiah(ppn)}</strong></div>
                <div class="summary-line total"><span>Total</span><strong>${formatRupiah(subtotal + ppn)}</strong></div>
            `;
        }
        if (activeSchema.calculation === 'spl') {
            const total = activeDraft.rows.reduce((sum, row) => sum + numberValue(row.durasi), 0);
            const people = activeDraft.rows.filter(row => row.nama).length;
            return `
                <div class="summary-line"><span>Jumlah personel</span><strong>${people} orang</strong></div>
                <div class="summary-line total"><span>Total jam-orang</span><strong>${total.toLocaleString('id-ID')} jam</strong></div>
            `;
        }
        if (activeSchema.calculation === 'lho') {
            const hours = activeDraft.rows.reduce((sum, row) => sum + numberValue(row.jam_kerja), 0);
            const hm = activeDraft.rows.reduce((sum, row) => sum + numberValue(row.hm_operasi), 0);
            const fuel = activeDraft.rows.reduce((sum, row) => sum + numberValue(row.bbm), 0);
            return `
                <div class="summary-line"><span>Total jam kerja</span><strong>${hours.toLocaleString('id-ID')} jam</strong></div>
                <div class="summary-line"><span>Total HM operasi</span><strong>${hm.toLocaleString('id-ID')} HM</strong></div>
                <div class="summary-line total"><span>Total BBM</span><strong>${fuel.toLocaleString('id-ID')} liter</strong></div>
            `;
        }
        if (activeSchema.calculation === 'weeklyParts') {
            const stockValue = activeDraft.rows.reduce((sum, row) => sum + numberValue(row.nilai_saldo), 0);
            return `<div class="summary-line total"><span>Total nilai saldo</span><strong>${formatRupiah(stockValue)}</strong></div>`;
        }
        if (activeSchema.calculation === 'warehouseIn') {
            const received = activeDraft.rows.reduce((sum, row) => sum + numberValue(row.jumlah), 0);
            return `<div class="summary-line total"><span>Total barang masuk</span><strong>${received.toLocaleString('id-ID')} unit</strong></div>`;
        }
        if (activeSchema.calculation === 'warehouseOut') {
            const issued = activeDraft.rows.reduce((sum, row) => sum + numberValue(row.diberikan), 0);
            return `<div class="summary-line total"><span>Total barang keluar</span><strong>${issued.toLocaleString('id-ID')} unit</strong></div>`;
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
                                        ${activeSchema.columns.map(item => `<th>${escapeHtml(item.label)}</th>`).join('')}
                                        <th>Aksi</th>
                                    </tr></thead>
                                    <tbody id="formRows"></tbody>
                                </table>
                            </div>
                            <button type="button" class="row-add-btn" id="addFormRow"><i class="fa-solid fa-plus"></i> Tambah baris</button>
                            ${activeSchema.calculationNote ? `<div class="calculation-note"><i class="fa-solid fa-calculator"></i><span>${escapeHtml(activeSchema.calculationNote)}</span></div>` : ''}
                            <div class="calculation-summary" id="calculationSummary"></div>
                        </section>
                    </div>
                    <div class="form-builder-footer">
                        <div class="form-footer-note"><i class="fa-solid fa-shield-halved"></i> Data prototipe tersimpan lokal di browser, belum dikirim ke server.</div>
                        <div class="form-footer-actions">
                            <button type="button" class="form-secondary-btn" id="saveReportDraft"><i class="fa-regular fa-floppy-disk"></i> Simpan Draft</button>
                            <button type="submit" class="form-primary-btn"><i class="fa-regular fa-eye"></i> Validasi & Preview</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="form-preview" id="reportPrintArea"></div>
        `;

        document.getElementById('reportCatalog').classList.add('hidden');
        workspace.classList.add('active');
        document.getElementById('backToCatalog').addEventListener('click', closeForm);
        document.getElementById('saveReportDraft').addEventListener('click', () => saveDraft(true));
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
        saveDraft();
        window.history.replaceState(null, '', '#reports');
        document.getElementById('formWorkspace').classList.remove('active');
        document.getElementById('formWorkspace').innerHTML = '';
        document.getElementById('reportCatalog').classList.remove('hidden');
        activeSchema = null;
        activeDraft = null;
        document.getElementById('reportModule').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function validateAndPreview() {
        const missing = activeSchema.fields.filter(item => item.required && !String(activeDraft.fields[item.key] || '').trim());
        if (missing.length) {
            const input = document.querySelector(`[data-field="${missing[0].key}"]`);
            if (input) {
                input.focus();
                input.reportValidity();
            }
            showToast(`Lengkapi field wajib: ${missing[0].label}.`, true);
            return;
        }
        const populatedRows = activeDraft.rows.filter(row => Object.values(row).some(value => String(value || '').trim()));
        if (!populatedRows.length) {
            showToast('Isi minimal satu baris data sebelum membuat preview.', true);
            return;
        }
        saveDraft();
        renderPreview(populatedRows);
    }

    function displayValue(item, value) {
        if (item.type === 'number' && /harga|total|nilai|estimasi/.test(item.key)) return formatRupiah(value);
        return value || '—';
    }

    function renderPreview(rows) {
        const preview = document.getElementById('reportPrintArea');
        preview.innerHTML = `
            <div class="preview-toolbar">
                <strong><i class="fa-regular fa-file-pdf"></i> Preview dokumen terstandarisasi</strong>
                <div>
                    <button type="button" id="closePreview"><i class="fa-solid fa-xmark"></i> Tutup</button>
                    <button type="button" id="printReport"><i class="fa-solid fa-print"></i> Cetak / Simpan PDF</button>
                </div>
            </div>
            <article class="print-sheet">
                <header class="print-brand">
                    <div><h2>PT BINA REKAYASA ANUGERAH</h2><small>Departemen Equipment</small></div>
                    <div style="text-align:right"><strong>${escapeHtml(activeSchema.code)}</strong><br><small>Dokumen terkendali</small></div>
                </header>
                <div class="print-title">
                    <h1>${escapeHtml(activeSchema.title)}</h1>
                </div>
                <div class="print-meta">
                    ${activeSchema.fields.map(item => `
                        <div><strong>${escapeHtml(item.label)}</strong><span>${escapeHtml(displayValue(item, activeDraft.fields[item.key]))}</span></div>
                    `).join('')}
                </div>
                <table class="print-table">
                    <thead><tr><th>No.</th>${activeSchema.columns.map(item => `<th>${escapeHtml(item.label)}</th>`).join('')}</tr></thead>
                    <tbody>
                        ${rows.map((row, index) => `<tr>
                            <td>${index + 1}</td>
                            ${activeSchema.columns.map(item => `<td>${escapeHtml(displayValue(item, row[item.key]))}</td>`).join('')}
                        </tr>`).join('')}
                    </tbody>
                </table>
                ${summaryMarkup() ? `<div class="calculation-summary"><div class="summary-card">${summaryMarkup()}</div></div>` : ''}
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:40px;margin-top:55px;text-align:center;font-size:.72rem">
                    <div>Dibuat oleh<br><br><br><strong>( __________________ )</strong></div>
                    <div>Diperiksa oleh<br><br><br><strong>( __________________ )</strong></div>
                    <div>Disetujui oleh<br><br><br><strong>( __________________ )</strong></div>
                </div>
            </article>
        `;
        preview.classList.add('active');
        document.getElementById('closePreview').addEventListener('click', () => preview.classList.remove('active'));
        document.getElementById('printReport').addEventListener('click', () => window.print());
        preview.scrollIntoView({ behavior: 'smooth', block: 'start' });
        showToast('Validasi selesai. Preview laporan siap ditinjau.');
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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', createModuleMarkup);
    } else {
        createModuleMarkup();
    }
})();
