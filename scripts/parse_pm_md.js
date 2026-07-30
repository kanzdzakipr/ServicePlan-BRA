const fs = require('fs');
const path = require('path');

const mdDir = path.join(__dirname, '../material/DAFTAR_KEBUTUHAN_SERVICE_ALAT_BERAT_DAN_HARGA_Full_Tabulasi/DAFTAR_KEBUTUHAN_SERVICE_ALAT_BERAT_DAN_HARGA_Full_Tabulasi');
const outputFile = path.join(__dirname, 'pm_master_parts.json');

const files = fs.readdirSync(mdDir).filter(f => f.endsWith('.md'));

let masterData = [];

files.forEach(file => {
    const filePath = path.join(mdDir, file);
    const content = fs.readFileSync(filePath, 'utf8');
    
    // Extract unit type from filename (e.g., "37_BOMAG BW211D-40SL cummin.md" -> "BOMAG BW211D-40SL cummin")
    const match = file.match(/^\d+_(.*)\.md$/);
    if (!match) return;
    const modelName = match[1].trim();
    
    const lines = content.split('\n');
    let parts = [];
    let parsingTable = false;
    let colIndices = { partNo: -1, partOem: -1, desc: -1, qty: -1 };
    
    for (let i = 0; i < lines.length; i++) {
        const line = lines[i].trim();
        if (line.startsWith('|')) {
            const cols = line.split('|').map(c => c.trim()).filter((c, idx, arr) => idx !== 0 && idx !== arr.length - 1);
            
            if (!parsingTable) {
                // Check if this is a header row (e.g. contains "PART", "DESC", "QTY")
                const lowerCols = cols.map(c => c.toLowerCase());
                const descIdx = lowerCols.findIndex(c => c.includes('desc') || c.includes('nama part') || c.includes('description'));
                if (descIdx !== -1) {
                    parsingTable = true;
                    colIndices.desc = descIdx;
                    colIndices.partNo = lowerCols.findIndex(c => c.includes('part genuine') || c.includes('part no'));
                    colIndices.partOem = lowerCols.findIndex(c => c.includes('part oem'));
                    colIndices.qty = lowerCols.findIndex(c => c.includes('qty'));
                    continue; // Skip header separator
                }
            } else {
                // Skip separator rows
                if (cols[0] && cols[0].includes('---')) continue;
                
                // Extract part row
                if (colIndices.desc !== -1 && cols.length > colIndices.desc) {
                    const desc = cols[colIndices.desc];
                    if (desc && desc !== '—' && desc !== '-' && desc !== '' && !desc.toLowerCase().includes('total') && !desc.toLowerCase().includes('biaya')) {
                        const partNo = colIndices.partNo !== -1 && cols.length > colIndices.partNo ? cols[colIndices.partNo] : '';
                        const partOem = colIndices.partOem !== -1 && cols.length > colIndices.partOem ? cols[colIndices.partOem] : '';
                        const qty = colIndices.qty !== -1 && cols.length > colIndices.qty ? cols[colIndices.qty] : '';
                        
                        // Clean part numbers
                        const cleanPartNo = (partNo === '—' || partNo === '-') ? '' : partNo;
                        const cleanPartOem = (partOem === '—' || partOem === '-') ? '' : partOem;
                        const cleanQty = (qty === '—' || qty === '-') ? '' : qty;
                        
                        // Check if we already have this part (avoid duplicates across service intervals like 500, 1000)
                        const existingPart = parts.find(p => p.description === desc);
                        if (!existingPart) {
                            parts.push({
                                description: desc,
                                partNo: cleanPartNo || cleanPartOem || null,
                                qty: cleanQty || '1' // default 1 if empty
                            });
                        } else if (!existingPart.partNo && (cleanPartNo || cleanPartOem)) {
                            existingPart.partNo = cleanPartNo || cleanPartOem;
                        }
                    }
                }
            }
        }
    }
    
    // Fallback if no parts found via standard headers, try generic Excel column parsing
    if (parts.length === 0) {
        parsingTable = false;
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();
            if (line.startsWith('|')) {
                const cols = line.split('|').map(c => c.trim());
                if (cols.length > 5) {
                    // Usually C is Part No, D is OEM, E is Desc, F is Qty
                    // In array, index 3 is C, 4 is D, 5 is E, 6 is F
                    const desc = cols[5];
                    if (desc && desc !== '—' && desc !== '-' && desc !== '' && desc !== 'PART DESC' && desc !== 'E' && !desc.toLowerCase().includes('total') && !desc.toLowerCase().includes('biaya')) {
                        const partNo = cols[3] === '—' ? '' : cols[3];
                        const partOem = cols[4] === '—' ? '' : cols[4];
                        const qty = cols[6] === '—' ? '' : cols[6];
                        
                        const existingPart = parts.find(p => p.description === desc);
                        if (!existingPart) {
                            parts.push({
                                description: desc,
                                partNo: partNo || partOem || null,
                                qty: qty || '1'
                            });
                        }
                    }
                }
            }
        }
    }
    
    if (parts.length > 0) {
        masterData.push({
            modelName: modelName,
            totalItems: parts.length,
            parts: parts
        });
    }
});

fs.writeFileSync(outputFile, JSON.stringify(masterData, null, 2));
console.log('Successfully generated pm_master_parts.json with ' + masterData.length + ' models.');
