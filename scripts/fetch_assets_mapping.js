const fs = require('fs');
const https = require('https');

const URL = "https://docs.google.com/spreadsheets/d/1lrElHvYPB4ezXR13kOxy6WdECbRN4C3-H2UOF8ZcXtE/gviz/tq?tqx=out:csv&sheet=List%20Data%20Aset%20BRA";

// Simple CSV parser that handles quotes and commas inside quotes
function parseCSVRow(text) {
    const regex = /,(?=(?:(?:[^"]*"){2})*[^"]*$)/;
    return text.split(regex).map(field => field.replace(/^"|"$/g, '').trim());
}

function parseCSV(csvText) {
    const lines = csvText.split(/\r?\n/);
    const result = [];
    for (let i = 0; i < lines.length; i++) {
        if (!lines[i].trim()) continue;
        result.push(parseCSVRow(lines[i]));
    }
    return result;
}

function fetchUrl(url) {
    return new Promise((resolve, reject) => {
        https.get(url, (res) => {
            if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
                return resolve(fetchUrl(res.headers.location)); // handle redirects
            }
            if (res.statusCode !== 200) {
                return reject(new Error('Request Failed. Status Code: ' + res.statusCode));
            }

            res.setEncoding('utf8');
            let rawData = '';
            res.on('data', (chunk) => { rawData += chunk; });
            res.on('end', () => resolve(rawData));
        }).on('error', (e) => reject(e));
    });
}

async function main() {
    try {
        console.log("Fetching Asset Data...");
        const csv = await fetchUrl(URL);
        const rows = parseCSV(csv);
        
        const mapping = {};

        for (let i = 1; i < rows.length; i++) {
            const r = rows[i];
            if (!r || r.length < 10) continue;
            
            const serialNumber = r[5] || '';
            const kodeUnit = r[8] || '';
            const nomorLambung = r[9] || '';
            
            if (kodeUnit) mapping[kodeUnit.toUpperCase().trim()] = serialNumber;
            if (nomorLambung) mapping[nomorLambung.toUpperCase().trim()] = serialNumber;
        }

        const jsCode = `window.assetMappingData = ${JSON.stringify(mapping, null, 2)};`;
        // Save to scripts/asset_mapping_data.js
        fs.writeFileSync('asset_mapping_data.js', jsCode, 'utf-8');
        console.log("Data successfully saved to asset_mapping_data.js");
    } catch (e) {
        console.error("Error:", e);
    }
}

main();
