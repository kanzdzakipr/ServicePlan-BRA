"use strict";

const fs = require("fs");
const path = require("path");

const root = path.resolve(__dirname, "..");
const view = fs.readFileSync(path.join(root, "dashboard.view.php"), "utf8");

let passed = 0;
function check(condition, message) {
  if (!condition) throw new Error(`FAIL: ${message}`);
  passed += 1;
  process.stdout.write(`PASS: ${message}\n`);
}

for (const prefix of ["na", "ea"]) {
  for (const field of [
    "current-meter",
    "last-service-date",
    "last-service-hm",
    "service-interval",
    "service-unit",
    "daily-rate",
  ]) {
    const id = `${prefix}-${field}`;
    check(
      (view.match(new RegExp(`id=["']${id}["']`, "g")) || []).length === 1,
      `${id} tersedia tepat sekali`,
    );
  }
}

check(
  view.includes("last_hm_km: document.getElementById('ea-current-meter')"),
  "edit Master Asset mengirim meter unit saat ini",
);
check(
  view.includes("service_interval_val: document.getElementById('ea-service-interval')"),
  "edit Master Asset mengirim interval PM",
);
check(
  view.includes("'ea-service-interval': assetObj.service_interval_val"),
  "form edit memuat ulang interval dari database",
);
check(
  view.includes("window.PreventiveMaintenance?.refresh"),
  "Forecast PM dimuat ulang setelah Master Asset disimpan",
);

process.stdout.write(`\nMaster Asset PM edit UI smoke: ${passed} passed.\n`);
