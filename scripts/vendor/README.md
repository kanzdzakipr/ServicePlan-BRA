# Vendored document parsers

These browser distributions are pinned so document imports and OCR can run locally without uploading source documents or fetching parser code at runtime.

| Component | Version / source | License |
|---|---|---|
| JSZip | 3.10.1, cdnjs distribution | MIT |
| SheetJS Community Edition | 0.20.3, official SheetJS CDN distribution | Apache-2.0 |
| PDF.js | 3.11.174, cdnjs legacy browser distribution | Apache-2.0 |
| Tesseract.js | 5.1.1, jsDelivr distribution | Apache-2.0 |
| Tesseract.js Core | 5.1.1, jsDelivr WebAssembly distributions | Apache-2.0 |
| Tesseract `eng` and `ind` data | Project Naptha 4.0.0 trained data | Apache-2.0 |

Upstream projects:

- <https://github.com/Stuk/jszip>
- <https://git.sheetjs.com/sheetjs/sheetjs>
- <https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js>
- <https://github.com/mozilla/pdf.js>
- <https://github.com/naptha/tesseract.js>
- <https://github.com/naptha/tesseract.js-core>
- <https://github.com/tesseract-ocr/tessdata>

The application reads spreadsheet and legacy Word binary files inside `document-import-worker.js`; PDF.js uses its own worker. Tesseract uses the local worker, four local core variants, and local Indonesian/English language data.

SheetJS 0.20.3 is intentionally newer than the fixed versions named in the upstream
[CVE-2023-30533 advisory](https://cdn.sheetjs.com/advisories/CVE-2023-30533) and
[CVE-2024-22363 advisory](https://cdn.sheetjs.com/advisories/CVE-2024-22363).

SHA-256 checksums captured on 2026-07-26:

```text
eng.traineddata.gz                         ed350f3752f81ee8f38769edc14d92d997dababe23b565c59879372cc46a2468
ind.traineddata.gz                         46130be88c603e5a38e2fe5420540d8712f177fbaf46c904aaacccddcc0bc308
jszip-3.10.1.min.js                        acc7e41455a80765b5fd9c7ee1b8078a6d160bbbca455aeae854de65c947d59e
pdf-3.11.174.min.js                        5b5799e6f8c680663207ac5b42ee14eed2a406fa7af48f50c154f0c0b1566946
pdf-3.11.174.worker.min.js                 feabdf309770ed24bba31a5467836cdc8cf639c705af27d52b585b041bb8527b
tesseract-5.1.1.min.js                     a8e29918d098b2b06e1012bdaeffb4aec0445c5d5654709023e0bd1f442a80e8
tesseract-5.1.1.worker.min.js              aca1229639fc9907d86f96e825955a2b7c5716d17f3bc3acd71f9c7ab66181fc
tesseract-core.wasm                        b47a852b19181ae0999f9d970c368cd351135a320ea978b35bd38602d8bcc0bc
tesseract-core.wasm.js                     2b8c8c92b8788807061fb4bb16c5acdf000c149e100255f879f78d2c58ca9969
tesseract-core-lstm.wasm                   5db58ea4d1bd4256be81e8ae3b4fa226c4625dfba1850b1b3308dbf3700e9929
tesseract-core-lstm.wasm.js                8f04aa0cc81e7bde33f80e92fa01a7a665f0b4884d098acf5de9c7104a11dfaa
tesseract-core-simd.wasm                   baa2f54ef5af43ada4605fd7ae999bde3c6ecc96bfe0d0410b0b4f4a5f9c8b91
tesseract-core-simd.wasm.js                63f232c4f7a97b04e52eb940202700b2c6239783a75d0ff0553274fac530cd5c
tesseract-core-simd-lstm.wasm              66b601224a0c4a8977bc9d92dd39841189f9ca22cc4122fcd7208cdb0961eeef
tesseract-core-simd-lstm.wasm.js           ce20eda9533cbed1e6c2b4276fbae1e0adc61b6754b5513084be601787b457cf
xlsx-0.20.3.full.min.js                    cc015130aa8521e7f088f88898eba949ccdcbfb38df0bd129b44b7273c3a6f41
```

Verify the vendored files on Windows with:

```powershell
powershell -NoProfile -ExecutionPolicy Bypass -File .\tests\verify-vendor-checksums.ps1
```
