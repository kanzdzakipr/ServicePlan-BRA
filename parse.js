const fs = require('fs');

const content = fs.readFileSync('C:\\Users\\KANZ\\.gemini\\antigravity-ide\\brain\\5a259132-29d7-4f02-8150-2076bfb50010\\.system_generated\\steps\\10\\content.md', 'utf8');
const lines = content.split('\n');

const results = [];
for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    // Very simple CSV parser for this specific structure
    const parts = line.split(/,(?=(?:(?:[^"]*"){2})*[^"]*$)/);
    if (parts.length > 17) {
        const unitName = parts[9] ? parts[9].trim() : '';
        const unitCode = parts[10] ? parts[10].trim() : '';
        let sparepart = parts[17] ? parts[17].trim() : '';
        // Some might be in col 16 or 15 due to empty columns, let's just grab the last non-empty column after 12
        for(let j = parts.length - 1; j > 11; j--) {
            if (parts[j] && parts[j].trim() !== '') {
                sparepart = parts[j].trim();
                break;
            }
        }
        
        if (unitName && unitCode && unitCode.length < 10) {
            results.push({
                unitName: unitName.replace(/^"|"$/g, ''),
                unitCode: unitCode.replace(/^"|"$/g, ''),
                sparepart: sparepart.replace(/^"|"$/g, '')
            });
        }
    }
}

fs.writeFileSync('parts_data.json', JSON.stringify(results, null, 2));
console.log('Done');
