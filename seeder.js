const fs = require('fs');
const path = require('path');

const dataPath = path.join(__dirname, 'data.json');
let data = JSON.parse(fs.readFileSync(dataPath, 'utf-8'));

// 1. Biaya (Costs)
// Based on material/Biaya/Equipment_Expenses_Report_Tabulasi.md & home.php reference
data.costs = {
    total_repair_cost: 248500000, // Rp 248.500.000
    monthly_trend: [
        { month: 'Agt 2025', value: 180000000 },
        { month: 'Sep 2025', value: 195000000 },
        { month: 'Okt 2025', value: 170000000 },
        { month: 'Nov 2025', value: 210000000 },
        { month: 'Des 2025', value: 250000000 },
        { month: 'Jan 2026', value: 220000000 },
        { month: 'Feb 2026', value: 190000000 },
        { month: 'Mar 2026', value: 230000000 },
        { month: 'Apr 2026', value: 240000000 },
        { month: 'Mei 2026', value: 215000000 },
        { month: 'Jun 2026', value: 210000000 },
        { month: 'Jul 2026', value: 248500000 }
    ],
    unit_costs: [
        { unit: 'DT-098', cost: 15400000 },
        { unit: 'EX-021', cost: 12500000 },
        { unit: 'BD-012', cost: 9800000 },
        { unit: 'DT-054', cost: 8200000 },
        { unit: 'MG-003', cost: 7100000 }
    ]
};
data.summary.financials.total_repair_cost = "Rp 248.500.000";

// 2. Logistik
// Based on LOGISTIK material
data.logistics = {
    active_orders: 28,
    status_counts: {
        'Dipesan': 12,
        'Diproses Vendor': 7,
        'Dalam Pengiriman': 8,
        'Tiba': 15,
        'Tertunda': 6
    }
};

// 3. Preventive Maintenance (PM)
data.pm_schedules = [
    { unit: 'EX-021', type: 'Servis Berkala', meter: '8.450', next_meter: '8.500', date: '30 Jul 2026', status: 'Akan Service' },
    { unit: 'DT-054', type: 'Pengecekan', meter: '12.100', next_meter: '12.500', date: '05 Agt 2026', status: 'Terjadwal' },
    { unit: 'BD-012', type: 'Overhaul Ringan', meter: '15.050', next_meter: '15.000', date: '25 Jul 2026', status: 'Terlambat' },
    { unit: 'MG-003', type: 'Ganti Oli', meter: '5.990', next_meter: '6.000', date: '28 Jul 2026', status: 'Jatuh Tempo' }
];

// 4. Inspeksi Ban & Grease
data.inspections = {
    tires: {
        inspected: 145,
        rotation_needed: 24,
        replacement_needed: 8,
        details: [
            { unit: 'DT-098', position: 'Kiri Belakang Luar', condition: 'Tipis', pressure: 110 },
            { unit: 'DT-054', position: 'Kanan Depan', condition: 'Aus Tidak Rata', pressure: 115 },
            { unit: 'DT-112', position: 'Kiri Depan', condition: 'Harus Diganti', pressure: 90 }
        ]
    },
    grease: {
        status_counts: {
            'Sesuai Jadwal': 110,
            'Jatuh Tempo': 15,
            'Terlambat': 8,
            'Belum Ada Data': 12
        }
    }
};

// 5. Jam Kerja Aktual Mekanik (KPI)
data.mechanics = [
    { name: 'Budi Santoso', actual: 185.5, target: 160 },
    { name: 'Agus Pratama', actual: 172.0, target: 160 },
    { name: 'Rian Hidayat', actual: 150.5, target: 160 },
    { name: 'Dedi Kurniawan', actual: 190.0, target: 160 },
    { name: 'Hendra Saputra', actual: 145.0, target: 160 }
];

fs.writeFileSync(dataPath, JSON.stringify(data, null, 2));
console.log('Successfully seeded data.json with expanded metrics!');
