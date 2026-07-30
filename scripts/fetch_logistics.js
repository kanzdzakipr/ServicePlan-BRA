const fs = require('fs');
const https = require('https');

const URL_PARTS_MASUK = "https://docs.google.com/spreadsheets/d/1fkL1Utswll6JF_kdi_0xCM2EXW-8cUO6/export?format=csv";
const URL_PARTS_KELUAR = "https://docs.google.com/spreadsheets/d/1xAGD5biXazNIgn3IcLA1_CFIkmleyluK/export?format=csv";

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
        console.log("Fetching Parts Masuk...");
        const csvMasuk = await fetchUrl(URL_PARTS_MASUK);
        const rowsMasuk = parseCSV(csvMasuk);

        let headerIdx = 0;
        for (let i = 0; i < Math.min(20, rowsMasuk.length); i++) {
            if (rowsMasuk[i][0] && rowsMasuk[i][0].toUpperCase() === 'TANGGAL') {
                headerIdx = i; break;
            }
        }

        const dataPartsMasuk = [];
        for (let i = headerIdx + 2; i < rowsMasuk.length; i++) {
            const r = rowsMasuk[i];
            if (!r || r.length < 5 || !r[0]) continue;

            let sparepartUnit = r[8] || '';
            let nSpb = r[9] || '';
            if (!nSpb && r.length > 10) nSpb = r[10];
            if (!nSpb && r.length > 11) nSpb = r[11];
            
            dataPartsMasuk.push({
                tanggal: r[0],
                noBukti: r[2] || r[1],
                terimaDari: r[3],
                namaParts: r[4],
                partNumber: r[5],
                merk: r[6],
                satuan: r[7],
                jml: r[8],
                unit: sparepartUnit !== r[8] ? sparepartUnit : '',
                noSpb: nSpb
            });
        }

        console.log("Fetching Parts Keluar...");
        const csvKeluar = await fetchUrl(URL_PARTS_KELUAR);
        const rowsKeluar = parseCSV(csvKeluar);

        const dataPartsKeluar = [];
        for (let i = 1; i < rowsKeluar.length; i++) {
            const r = rowsKeluar[i];
            if (!r || r.length < 5 || !r[0]) continue;
            dataPartsKeluar.push({
                no: r[0],
                noSpb: r[1],
                tglSpb: r[2],
                noJo: r[3],
                idUnit: r[4],
                namaSparepart: r[5],
                spesifikasi: r[6],
                qty: r[7],
                satuan: r[8],
                status: r[16],
                kesimpulan: r[17] || ''
            });
        }

        const outputData = {
            masuk: dataPartsMasuk,
            keluar: dataPartsKeluar
        };

        fs.writeFileSync('../logistics_data.json', JSON.stringify(outputData, null, 2), 'utf-8');
        console.log("Data successfully saved to logistics_data.json");
    } catch (e) {
        console.error("Error:", e);
    }
}

main();
