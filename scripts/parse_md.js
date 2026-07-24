const fs = require('fs');
const path = require('path');

const parseMarkdownTable = (filePath) => {
    if (!fs.existsSync(filePath)) return [];
    const content = fs.readFileSync(filePath, 'utf-8');
    const lines = content.split('\n');
    const tableData = [];
    let isTable = false;
    let headers = [];

    for (const line of lines) {
        const trimmed = line.trim();
        if (trimmed.startsWith('|') && trimmed.endsWith('|')) {
            const row = trimmed.slice(1, -1).split('|').map(cell => cell.trim());
            if (row[0].replace(/-/g, '').replace(/:/g, '') === '') {
                isTable = true;
                continue;
            }
            if (!isTable) {
                headers = row.map(h => h.toLowerCase().replace(/[^a-z0-9]/g, '_').replace(/_+/g, '_').replace(/_$/, ''));
            } else {
                const obj = {};
                headers.forEach((header, index) => {
                    obj[header] = row[index] || '';
                });
                tableData.push(obj);
            }
        } else {
            isTable = false;
        }
    }
    return tableData;
};

const main = () => {
    const rootDir = path.resolve(__dirname, '..');
    const materialDir = path.join(rootDir, 'material');
    
    // We only use files relevant to our CMMS core (Asset and WO)
    const breakDownPath = path.join(materialDir, 'ADMINISTRASI-Laporan-Divisi-Equipment-Januari-2026', '01_Laporan_Break_Down_Januari_2026_Tabulasi.md');
    const standbyPath = path.join(materialDir, 'AVAILABILITY_UNIT', 'REKAP_UNIT_STANDBY.md');
    const cashOutPath = path.join(materialDir, 'ADMINISTRASI-Laporan-Divisi-Equipment-Januari-2026', '06_Laporan_Cash_Out_Januari_2026_Tabulasi.md');
    
    const breakDownData = parseMarkdownTable(breakDownPath);
    const standbyData = parseMarkdownTable(standbyPath);
    const cashOutData = parseMarkdownTable(cashOutPath);

    let totalCost = "Rp 0";
    cashOutData.forEach(item => {
        if (item.deskripsi === 'Total' && item.januari_2026_bra) {
            totalCost = item.januari_2026_bra.replace(/-/g, '').trim();
        }
    });

    const output = {
        summary: {
            total_units: 0,
            status_counts: { READY: 0, INSPEKSI: 0, BREAKDOWN: 0, STANDBY: 0 },
            financials: { total_repair_cost: totalCost }
        },
        assets: [],
        work_orders: []
    };

    let woCounter = 1;

    // Process breakdown as Assets and Work Orders
    breakDownData.forEach(item => {
        if (!item.alat_berat_truck) return;
        let status = (item.update_kondisi || '').toUpperCase();
        if (status.includes('READY')) status = 'READY';
        else if (status.includes('INSPEKSI')) status = 'INSPEKSI';
        else status = 'BREAKDOWN';

        output.summary.status_counts[status] = (output.summary.status_counts[status] || 0) + 1;
        output.summary.total_units++;

        const assetId = item.alat_berat_truck;
        const location = item.lokasi_unit_down || 'Workshop';
        const issue = item.permasalahan_trouble_yang_terjadi || '';

        // Add to assets
        output.assets.push({
            id: assetId,
            type: 'Heavy Equipment',
            category: item.jenis_unit || 'Excavator', // Mock category if not present
            status: status,
            location: location,
            lastUpdate: '2 jam lalu'
        });

        // Add to work orders if broken down or has issue
        if (status === 'BREAKDOWN' || issue) {
            output.work_orders.push({
                woId: `WO-26-${String(woCounter++).padStart(3, '0')}`,
                assetId: assetId,
                location: location,
                issue: issue,
                downtime: item.downtime_jam || '0',
                status: status === 'READY' ? 'Closed' : (status === 'INSPEKSI' ? 'In Progress' : 'Open'),
                priority: status === 'BREAKDOWN' ? 'High' : 'Normal',
                assignedTo: 'Belum ada PIC'
            });
        }
    });

    // Process standby as Assets
    standbyData.forEach(item => {
        if (!item.nama_asset) return;
        output.summary.status_counts['STANDBY']++;
        output.summary.total_units++;
        
        output.assets.push({
            id: item.nama_asset,
            type: 'Heavy Equipment',
            category: 'Bulldozer / Dump Truck',
            status: 'STANDBY',
            location: item.lokasi || 'Yard',
            lastUpdate: '5 jam lalu'
        });
    });

    const outputPath = path.join(rootDir, 'data.json');
    fs.writeFileSync(outputPath, JSON.stringify(output, null, 2));
    console.log(`Parsed core CMMS data. Total Assets: ${output.assets.length}. Total WOs: ${output.work_orders.length}.`);
};

main();
