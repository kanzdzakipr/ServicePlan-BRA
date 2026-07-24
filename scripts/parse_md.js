const fs = require('fs');
const path = require('path');

const parseMarkdownTable = (filePath) => {
    if (!fs.existsSync(filePath)) {
        console.warn(`File not found: ${filePath}`);
        return [];
    }
    const content = fs.readFileSync(filePath, 'utf-8');
    const lines = content.split('\n');
    const tableData = [];
    let isTable = false;
    let headers = [];

    for (const line of lines) {
        const trimmed = line.trim();
        if (trimmed.startsWith('|') && trimmed.endsWith('|')) {
            const row = trimmed.slice(1, -1).split('|').map(cell => cell.trim());
            
            // Check if it's a separator line
            if (row[0].replace(/-/g, '').replace(/:/g, '') === '') {
                isTable = true;
                continue;
            }

            if (!isTable) {
                // It's a header line
                headers = row.map(h => h.toLowerCase().replace(/[^a-z0-9]/g, '_').replace(/_+/g, '_').replace(/_$/, ''));
            } else {
                // It's a data line
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
    
    // 1. Break Down Data
    const breakDownPath = path.join(materialDir, 'ADMINISTRASI-Laporan-Divisi-Equipment-Januari-2026', '01_Laporan_Break_Down_Januari_2026_Tabulasi.md');
    const breakDownData = parseMarkdownTable(breakDownPath);
    
    // 2. Standby Data
    const standbyPath = path.join(materialDir, 'AVAILABILITY_UNIT', 'REKAP_UNIT_STANDBY.md');
    const standbyData = parseMarkdownTable(standbyPath);

    // 3. Cash Out Data
    const cashOutPath = path.join(materialDir, 'ADMINISTRASI-Laporan-Divisi-Equipment-Januari-2026', '06_Laporan_Cash_Out_Januari_2026_Tabulasi.md');
    const cashOutData = parseMarkdownTable(cashOutPath);

    let totalCost = "Rp 0";
    cashOutData.forEach(item => {
        if (item.deskripsi === 'Total' && item.januari_2026_bra) {
            totalCost = item.januari_2026_bra.replace(/-/g, '').trim();
        }
    });

    // Normalize and combine data
    const summary = {
        total_units: 0,
        status_counts: {
            READY: 0,
            INSPEKSI: 0,
            BREAKDOWN: 0,
            STANDBY: 0
        },
        financials: {
            total_repair_cost: totalCost
        },
        units: []
    };

    // Process breakdown data
    breakDownData.forEach(item => {
        if (!item.alat_berat_truck) return; // Skip empty rows
        let status = (item.update_kondisi || '').toUpperCase();
        if (status.includes('READY')) status = 'READY';
        else if (status.includes('INSPEKSI')) status = 'INSPEKSI';
        else status = 'BREAKDOWN'; // Default for items in breakdown list not marked as ready/inspeksi

        summary.status_counts[status] = (summary.status_counts[status] || 0) + 1;
        summary.total_units++;

        summary.units.push({
            id: item.alat_berat_truck || `Unknown-${Math.random()}`,
            type: 'Break Down Report',
            status: status,
            downtime: item.downtime_jam || '-',
            issue: item.permasalahan_trouble_yang_terjadi || '-',
            location: item.lokasi_unit_down || '-'
        });
    });

    // Process standby data
    standbyData.forEach(item => {
        if (!item.nama_asset) return; // Skip empty rows
        summary.status_counts['STANDBY']++;
        summary.total_units++;
        
        summary.units.push({
            id: item.nama_asset || `Unknown-${Math.random()}`,
            type: 'Standby Unit',
            status: 'STANDBY',
            downtime: '-',
            issue: '-',
            location: item.lokasi || '-'
        });
    });

    const outputPath = path.join(rootDir, 'data.json');
    fs.writeFileSync(outputPath, JSON.stringify(summary, null, 2));
    console.log(`Data successfully parsed and written to ${outputPath}`);
};

main();
