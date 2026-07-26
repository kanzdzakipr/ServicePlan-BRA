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
                        <div class="print-logo-mark company-logo"><span>BRA</span></div>
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
                        <div class="print-logo-mark company-logo"><span>BRA</span></div>
                        <div><h2>PT BINA REKAYASA ANUGERAH</h2><small>Departemen Equipment</small></div>
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
