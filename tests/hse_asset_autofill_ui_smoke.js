"use strict";

const fs = require("fs");
const path = require("path");

const source = fs.readFileSync(
  path.resolve(__dirname, "..", "scripts", "dashboard.js"),
  "utf8",
);
const view = fs.readFileSync(
  path.resolve(__dirname, "..", "dashboard.view.php"),
  "utf8",
);

let passed = 0;
function check(condition, message) {
  if (!condition) throw new Error(`FAIL: ${message}`);
  passed += 1;
  process.stdout.write(`PASS: ${message}\n`);
}

check(
  source.includes(
    'onchange="window.autofillHseAssetDetails(this.value)"',
  ),
  "pemilihan unit memicu autofill HSE",
);
check(
  source.includes("window.autofillHseAssetDetails = function (assetId)"),
  "fungsi autofill HSE tersedia",
);
check(
  source.includes(
    'asset.location_name || asset.location || asset.raw_location_notes',
  ),
  "lokasi diambil dari Master Asset dengan fallback catatan lokasi",
);
check(
  source.includes('asset.current_operator_name || ""'),
  "operator diambil dari PIC aktif Master Asset",
);
check(
  source.includes('<select id="incOperator"') &&
    !source.includes('<input type="text" id="incOperator"'),
  "operator menggunakan pilihan, bukan input teks bebas",
);
check(
  source.includes("function populateHseOperatorSelect") &&
    source.includes("asset.current_operator_name"),
  "pilihan operator dibangun dari PIC aktif Master Asset",
);
check(
  source.includes("new Set(") && source.includes("localeCompare"),
  "nama operator dideduplikasi dan diurutkan",
);
check(
  source.includes("populateHseOperatorSelect(operator)"),
  "PIC unit terpilih otomatis menjadi pilihan awal",
);
check(
  !source.includes('|| "Driver Operasional"'),
  "laporan tidak lagi memakai nama operator rekaan",
);
check(
  source.includes("Mohon pilih operator dari daftar PIC Master Asset."),
  "operator wajib dipilih sebelum melanjutkan form",
);
check(
  source.includes('const canonicalId = String(a.asset_id || a.id || "").trim();'),
  "dropdown HSE memakai ID kanonis database",
);
check(
  source.includes(
    'const codeOnly = String(selectedAsset?.asset_id || assetId).trim();',
  ),
  "payload insiden mempertahankan ID database secara utuh",
);
check(
  view.includes("let cleanId = rawAsset.asset_id") &&
    view.includes(": extractCleanAssetId(rawId);"),
  "normalisasi registry mempertahankan asset_id database dan hanya mem-parsing data lama",
);
check(
  source.includes('if (form) form.reset();'),
  "form insiden dibersihkan setiap kali dibuka",
);
check(
  source.includes('datetimeInput.value = now.toISOString().slice(0, 16);'),
  "waktu kejadian awal menggunakan waktu lokal saat form dibuka",
);
check(
  source.includes("operator dipilih dari daftar PIC terdaftar"),
  "sumber pilihan operator dijelaskan kepada pengguna",
);

process.stdout.write(`\nHSE asset autofill UI smoke: ${passed} passed.\n`);
