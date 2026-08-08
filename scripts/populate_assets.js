const fs = require('fs');
const path = require('path');

const sqlPath = path.join(__dirname, 'u646470441_ServicePlanBRA.sql');
let rawSql = fs.readFileSync(sqlPath, 'utf8');

// 1. Extract existing asset IDs from SQL dump
const existingAssetIds = new Set();
const assetIdRegex = /\('([^']+)'/g;
let match;

// Find the line for INSERT INTO `assets`
const lines = rawSql.split('\n');
let insertLineIndex = -1;

for (let i = 0; i < lines.length; i++) {
    if (lines[i].startsWith('INSERT INTO `assets`')) {
        insertLineIndex = i;
        while ((match = assetIdRegex.exec(lines[i])) !== null) {
            existingAssetIds.add(match[1]);
        }
    }
}

console.log(`Existing assets in SQL: ${existingAssetIds.size} units`);

// Helper function to map category
function getCategory(code, nameStr) {
    const str = (code + ' ' + nameStr).toUpperCase();
    if (str.includes('DUMP TRUCK') || code.startsWith('DT-') || code.startsWith('BRA-4') || code.startsWith('BRA-5')) return 'Dump Truck';
    if (str.includes('EXCAVATOR') || code.startsWith('EXC-') || code.startsWith('EX-') || code.startsWith('EXCA-') || code.startsWith('HE-6')) return 'Excavator';
    if (str.includes('BULLDOZER') || code.startsWith('DZ-') || code.startsWith('BRA-01') || code.startsWith('BRA-07')) return 'Bulldozer';
    if (str.includes('MOTOR GRADER') || code.startsWith('MG-')) return 'Motor Grader';
    if (str.includes('COMPACTOR') || code.startsWith('PF-') || code.startsWith('SD-') || code.startsWith('VIBRO-')) return 'Vibro Compactor';
    if (str.includes('WATER') || code.startsWith('WT-')) return 'Water Truck';
    if (str.includes('PRIME MOVER') || code.startsWith('PM-')) return 'Prime Mover';
    if (str.includes('DOUBLE CABIN') || code.startsWith('LV-') || code.startsWith('B91') || code.startsWith('B92') || code.startsWith('B96') || code.startsWith('B97')) return 'Light Vehicle';
    if (str.includes('TRADO') || code.startsWith('SL-') || code.startsWith('FBT-') || code.startsWith('LB-')) return 'Trado';
    if (str.includes('RECLAIMER') || str.includes('SPREADER') || code.startsWith('CS-') || code.startsWith('RM-')) return 'Reclaimer Spreader';
    return 'Other';
}

function getType(cat) {
    if (cat === 'Light Vehicle') return 'Light Vehicle';
    if (cat === 'Other') return 'Support Equipment';
    return 'Heavy Equipment';
}

const unitMap = new Map();

// 2. Read REKAP_DAFTAR_ASET_STANDBY_ALAT_BERAT.md
const standbyFile = path.join(__dirname, '../material/REKAP_DAFTAR_ASET_STANDBY_ALAT_BERAT.md');
if (fs.existsSync(standbyFile)) {
    const text = fs.readFileSync(standbyFile, 'utf8');
    const rows = text.split('\n');
    for (const r of rows) {
        const parts = r.split('|').map(s => s.trim());
        if (parts.length >= 11 && /^\d+$/.test(parts[1])) {
            const name = parts[2];
            const dealer = parts[3];
            const year = parseInt(parts[4]) || 2023;
            const serialOrNopol = parts[5];
            const kodeUnit = parts[7];
            const nopolAktual = parts[8];
            const project = parts[9];
            const lokasi = parts[10];
            const status = parts[11] || 'STANDBY';

            let unitId = (nopolAktual && nopolAktual !== '-') ? nopolAktual.replace(/\s+/g, '') : kodeUnit.replace(/\s+/g, '');
            unitId = unitId.replace('VIBROBW', '').trim();

            if (unitId && unitId !== '-') {
                const cat = getCategory(unitId, name);
                unitMap.set(unitId, {
                    id: unitId,
                    code: unitId,
                    name: name,
                    category: cat,
                    makeModel: `${name} (${dealer})`,
                    year: year,
                    serial: /^[A-Z0-9]{5,}$/.test(serialOrNopol) ? serialOrNopol : null,
                    plate: /\b[A-Z]{1,2}\s*\d+\s*[A-Z]{1,3}\b/.test(serialOrNopol) ? serialOrNopol : (nopolAktual !== '-' ? nopolAktual : null),
                    status: status,
                    location: lokasi
                });
            }
        }
    }
}

// 3. Read ASSET_REKAP_MUTASI_UNIT_DURI_sheet_REKAP.md
const mutasiFile = path.join(__dirname, '../material/ASSET_REKAP_MUTASI_UNIT_DURI_sheet_REKAP.md');
if (fs.existsSync(mutasiFile)) {
    const text = fs.readFileSync(mutasiFile, 'utf8');
    const rows = text.split('\n');
    for (const r of rows) {
        const parts = r.split('|').map(s => s.trim());
        if (parts.length >= 8 && /^\d+$/.test(parts[1])) {
            const nopolLama = parts[3];
            const jenis = parts[4];
            const proyek = parts[5];
            const nopolBaru = parts[7];

            let unitId = (nopolBaru && nopolBaru !== '') ? nopolBaru.replace(/\s+/g, '') : nopolLama.replace(/\s+/g, '');
            if (unitId && unitId !== 'Nopol' && !unitMap.has(unitId)) {
                const cat = getCategory(unitId, jenis);
                unitMap.set(unitId, {
                    id: unitId,
                    code: unitId,
                    name: `${jenis} (${nopolLama})`,
                    category: cat,
                    makeModel: jenis,
                    year: 2022,
                    serial: null,
                    plate: nopolBaru || nopolLama,
                    status: 'OPERATING',
                    location: `${proyek} Project`
                });
            }
        }
    }
}

console.log(`Total unique unit records extracted from material docs: ${unitMap.size}`);

// Generate new SQL tuples for missing units
const newTuples = [];

for (const [id, u] of unitMap.entries()) {
    if (!existingAssetIds.has(id)) {
        const idEsc = id.replace(/'/g, "''");
        const codeEsc = u.code.replace(/'/g, "''");
        const nameEsc = u.name.replace(/'/g, "''");
        const catEsc = u.category.replace(/'/g, "''");
        const typeEsc = getType(u.category);
        const modelEsc = u.makeModel.replace(/'/g, "''");
        const statusEsc = u.status || 'READY';
        const plateEsc = u.plate ? `'${u.plate.replace(/'/g, "''")}'` : 'NULL';
        const serialEsc = u.serial ? `'${u.serial.replace(/'/g, "''")}'` : 'NULL';
        const locNotesEsc = u.location ? `'${u.location.replace(/'/g, "''")}'` : "'Yard KM 12 Duri'";
        let locId = 12;
        if (u.location && u.location.includes('WUR')) locId = 15;
        else if (u.location && u.location.includes('Palembang')) locId = 2;
        else if (u.location && u.location.includes('Pekanbaru')) locId = 64;

        const tuple = `('${idEsc}','${codeEsc}',${serialEsc},${plateEsc},NULL,'${idEsc}','${typeEsc}','${catEsc}','${modelEsc}','PKB PEKANBARU Branch',${u.year},'Milik Sendiri','${statusEsc}',${locId},${locNotesEsc},0.00,NULL,NULL,NULL,NULL,1,NOW(),NOW())`;
        newTuples.push(tuple);
    }
}

console.log(`New missing assets to be added to SQL dump: ${newTuples.length}`);

if (newTuples.length > 0 && insertLineIndex >= 0) {
    let line = lines[insertLineIndex];
    if (line.endsWith(';')) {
        line = line.slice(0, -1);
    }
    lines[insertLineIndex] = line + ',\n' + newTuples.join(',\n') + ';';
    fs.writeFileSync(sqlPath, lines.join('\n'), 'utf8');
    console.log(`Successfully appended ${newTuples.length} new missing assets to u646470441_ServicePlanBRA.sql!`);
}
