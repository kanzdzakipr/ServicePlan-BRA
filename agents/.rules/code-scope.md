# Aturan Pemindaian Kode Sumber Fitur Reader
- **Allowed Paths:** Seluruh kode JavaScript utama di bawah direktori `scripts/` (kecuali subfolder vendor) dan berkas pengujian di bawah direktori `tests/`.
- **Prohibited Paths:** JANGAN PERNAH memindai, membaca, memodifikasi, atau memuat ulang isi direktori `scripts/vendor/`. Folder tersebut berisi berkas biner `.wasm`, `.gz`, dan pustaka eksternal yang sudah final.
