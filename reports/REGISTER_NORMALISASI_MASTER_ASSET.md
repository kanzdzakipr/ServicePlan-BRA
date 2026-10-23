# Register Normalisasi Master Asset: Matriks Audit & Staging Triase

**Tanggal Ekstraksi:** 23 Oktober 2026  
**Mode:** Read-Only Audit & Preview Diff (Tidak ada update database)  
**Basis Data:** `u646470441_ServicePlanBRA` (283 Unit Aktif)  
**Sumber Verifikasi:** `REKAP 2026` (332 Baris), `Mutasi Unit Duri`, dan `KOMTRAX 2026`  

## 1. Ringkasan Statistik Triase

| Kategori Triase | Jumlah | Definisi Tindakan |
|---|---:|---|
| `MATCH_AUTO` | 151 | Skor >= 95. Identitas unik terkonfirmasi tanpa kontradiksi. Siap diajukan ke pilot staging. |
| `MATCH_REVIEW` | 15 | Skor 80-94. Indikasi kuat namun membutuhkan konfirmasi verifikator/PIC. |
| `CONFLICT` | 5 | Baris bertentangan atau identitas diklaim > 1 unit. Pembaruan wajib ditahan. |
| `NEW_CANDIDATE` | 174 | Baris REKAP tidak cocok ke 283 unit aktif (kandidat unit baru, rental, atau nonaktif). |
| `NO_EVIDENCE` | 120 | Unit aktif database belum ditemukan di sumber REKAP 2026. Perlu cek fisik/lapangan. |

## 2. Rekomendasi 10 Unit Pilot (Sesuai Rencana Strategis Section 9 & 16)

10 Unit berikut dipilih sebagai sampel representatif untuk uji coba dry-run tahap pertama:

| No | Asset ID | Kode Unit | Kategori | Model | Usulan Pengisian Kunci | Sumber Bukti |
|---:|---|---|---|---|---|---|
| 1 | `PF-00011` | `PF-00011` | Vibro Compactor | Vibro Compactor Bomag BW211D-40SL / Sakai | <b>hull_number:</b> SD 05<br><b>dealer:</b> United Tractors<br><b>capacity:</b> 138 HP / 103 kW<br><b>project_name:</b> WUR EW | REKAP 2026 (Skor: 100) |
| 2 | `SUP-00009` | `SUP-00009` | Other | Bomag Smooth Drum BW211D-40 (United Tractors) | <b>hull_number:</b> SD 06<br><b>dealer:</b> United Tractors<br><b>capacity:</b> 138 HP / 103 kW<br><b>project_name:</b> RWI | REKAP 2026 (Skor: 100) |
| 3 | `PF-00001` | `PF-00001` | Vibro Compactor | Vibro Bomag Pad Foot BW 211D-40SL | <b>hull_number:</b> PF 01<br><b>dealer:</b> United Tractors<br><b>capacity:</b> 138 HP / 103 kW<br><b>project_name:</b> KENDARI | REKAP 2026 (Skor: 100) |
| 4 | `PF-00004` | `PF-00004` | Vibro Compactor | Vibro Compactor Bomag BW211D-40SL / Sakai | <b>hull_number:</b> PF 02<br><b>dealer:</b> United Tractors<br><b>capacity:</b> 138 HP / 103 kW<br><b>project_name:</b> WUR EW | REKAP 2026 (Skor: 185) |
| 5 | `PF-00003` | `PF-00003` | Vibro Compactor | Vibro Compactor Bomag BW211D-40SL / Sakai | <b>hull_number:</b> PF 03<br><b>dealer:</b> United Tractors<br><b>capacity:</b> 138 HP / 103 kW<br><b>project_name:</b> WUR EW | REKAP 2026 (Skor: 185) |
| 6 | `PF-00012` | `PF-00012` | Vibro Compactor | Vibro Compactor Bomag BW211D-40SL / Sakai | <b>hull_number:</b> SD 09<br><b>dealer:</b> United Tractors<br><b>capacity:</b> 138 HP / 103 kW<br><b>project_name:</b> WUR EW | REKAP 2026 (Skor: 100) |
| 7 | `PF-00010` | `PF-00010` | Vibro Compactor | Vibro Compactor Bomag BW211D-40SL / Sakai | <b>hull_number:</b> SD 10<br><b>dealer:</b> United Tractors<br><b>capacity:</b> 138 HP / 103 kW<br><b>project_name:</b> WUR EW | REKAP 2026 (Skor: 100) |
| 8 | `MG-00005` | `MG-00005` | Motor Grader | Motor Grader XCMG GR135 MAX | <b>serial_number:</b> SZN01229<br><b>hull_number:</b> GR 1<br><b>dealer:</b> Trakindo<br><b>capacity:</b> 120 HP<br><b>project_name:</b> WUR EW | REKAP 2026 (Skor: 95) |
| 9 | `DZ-00010` | `DZ-00010` | Bulldozer | Bulldozer Caterpillar D6G (Trakindo) | <b>hull_number:</b> Dozer 01<br><b>dealer:</b> trakindo<br><b>capacity:</b> 160 HP<br><b>project_name:</b> Yard Palembang | REKAP 2026 (Skor: 110) |
| 10 | `DZ-00002` | `DZ-00002` | Bulldozer | Caterpillar D6G 2XL | <b>hull_number:</b> Dozer 03<br><b>dealer:</b> trakindo<br><b>capacity:</b> 160 HP<br><b>project_name:</b> DUMAI | REKAP 2026 (Skor: 195) |

## 3. Sampel Register MATCH_AUTO (Skor Tinggi, Siap Pilot)

| Asset ID | Kode DB | Lambung REKAP | Model REKAP | SN/Pelat Sumber | Usulan Perubahan | Skor |
|---|---|---|---|---|---|---:|
| `PF-00011` | PF-00011 | SD-00004 | Bomag Smooth Drum Compactor  BW211D-40 | 961884301945 | hull_number: SD 05; dealer: United Tractors; capacity: 138 HP / 103 kW; project_name: WUR EW | 100 |
| `SUP-00009` | SUP-00009 | VIBRO BW BRA-06 | Bomag Smooth Drum BW211D-40 | 961884302060 | hull_number: SD 06; dealer: United Tractors; capacity: 138 HP / 103 kW; project_name: RWI | 100 |
| `PF-00001` | PF-00001 | VIBRO BW BRA-02 | Bomag Pad Foot Compactor  BW211D-40 | 961884301016 | hull_number: PF 01; dealer: United Tractors; capacity: 138 HP / 103 kW; project_name: KENDARI | 100 |
| `PF-00004` | PF-00004 | PF-00004 | Bomag Pad Foot Compactor  BW211D-40 | 961884301614 | hull_number: PF 02; dealer: United Tractors; capacity: 138 HP / 103 kW; project_name: WUR EW | 185 |
| `PF-00003` | PF-00003 | PF-00003 | Bomag Pad Foot Compactor  BW211D-40SL | 961582391152 | hull_number: PF 03; dealer: United Tractors; capacity: 138 HP / 103 kW; project_name: WUR EW | 185 |
| `PF-00012` | PF-00012 | SD-00005 | Bomag Smooth Drum Compactor  BW211D-40SL | 961582391661 | hull_number: SD 09; dealer: United Tractors; capacity: 138 HP / 103 kW; project_name: WUR EW | 100 |
| `PF-00010` | PF-00010 | SD-00002 | Bomag Smooth Drum Compactor  BW211D-40 SL | 961582391716 | hull_number: SD 10; dealer: United Tractors; capacity: 138 HP / 103 kW; project_name: WUR EW | 100 |
| `MG-00005` | MG-00005 | MG-00005 | MOTOR GRADER CAT 120 K | SZN01229 | serial_number: SZN01229; hull_number: GR 1; dealer: Trakindo; capacity: 120 HP; project_name: WUR EW | 95 |
| `DZ-00010` | DZ-00010 | BRA-01 | Bulldozer Caterpillar D6G | P6G00163 | hull_number: Dozer 01; dealer: trakindo; capacity: 160 HP; project_name: Yard Palembang | 110 |
| `DZ-00002` | DZ-00002 | DZ-00002 | Bulldozer Caterpillar D6G  2XL | P6G01656 | hull_number: Dozer 03; dealer: trakindo; capacity: 160 HP; project_name: DUMAI | 195 |
| `DZ-00005` | DZ-00005 | DZ-00005 | Bulldozer Komatsu D85ESS-2 | J16298 | serial_number: J16298; hull_number: Dozer 04; dealer: KOMATSU; capacity: 215 HP; project_name: YARD DURI | 95 |
| `DZ-00003` | DZ-00003 | DZ-00003 | Bulldozer Caterpillar D6G 2XL | P6G01529 | serial_number: P6G01529; hull_number: Dozer 05; dealer: Trakindo; capacity: 160 HP; project_name: KENDARI | 95 |
| `DZ-00011` | DZ-00011 | BRA-07 | Bulldozer Caterpillar D7G2 | 7MB05410 | hull_number: Dozer 07; dealer: Trakindo; capacity: 200 HP; project_name: Yard Palembang | 110 |
| `DZ-00001` | DZ-00001 | DZ-00001 | Bulldozer Komatsu D85ESS-2 | J21020 | serial_number: J21020; hull_number: Dozer 08; dealer: United Tractors; capacity: 215 HP; project_name: WUR EW | 95 |
| `EXC-00014` | EXC-00014 | EXC-00008 | Excavator PC 210-10M0/P1 | C01531 | hull_number: Exca 01; dealer: United Tractors; capacity: 165 HP / 1.2 M3; project_name: WUR EW | 110 |
| `EXC-00013` | EXC-00013 | EXC-00013 | Excavator PC 210-10M0/P1 | C01530 | hull_number: Exca 02; dealer: United Tractors; capacity: 165 HP / 1.2 M3; project_name: KELMI/LABAN | 195 |
| `EXC-00011` | EXC-00011 | EXC-00005 | Excavator CAT 320 GX new | SYW01364 | hull_number: Exca 06; dealer: Trakindo; capacity: 138 HP / 0.90 M3; project_name: WUR EW | 110 |
| `EXC-00014` | EXC-00014 | EXC-00014 | Excavator Komatsu PC 200-10 MOCE | DBCHO302 | hull_number: Exca 07; dealer: United Tractors; capacity: 138 HP / 1.0 M3; project_name: WUR EW | 95 |
| `EXC-00010` | EXC-00010 | EXC-00004 | Excavator Komatsu PC 200-10 MOCE | DBCH0366 | hull_number: Exca 13; dealer: United Tractors; capacity: 138 HP / 1.0 M3; project_name: WUR EW | 110 |
| `EXC-06006` | EXC-06006 | HE-6.006 | Excavator Caterpillar 305.5E2 | WE217081 | hull_number: Exca 18; dealer: Trakindo; capacity: 41 HP / 0.22 M3; project_name: YARD DURI | 110 |
| `EXC-00009` | EXC-00009 | EXC-00003 | Excavator PC 200-10 MO | C51503 | hull_number: Exca 21; dealer: United Tractors; capacity: 138 HP / 1.0 M3; project_name: WUR EW | 110 |
| `EXC-41001` | EXC-41001 | EXC-41001 | Excavator PC 200-10 MO | DBCH1784 | hull_number: Exca 23; dealer: United Tractors; capacity: 138 HP / 1.0 M3; project_name: KELMI/LABAN | 195 |
| `EXC-00001` | EXC-00001 | EXC-00001 | Excavator PC 200-10 MO | DBCH2244 | hull_number: Exca 24; dealer: United Tractors; capacity: 138 HP / 1.0 M3; project_name: WUR EW | 95 |
| `EXC-00002` | EXC-00002 | EXC-00002 | Excavator PC 200-10 MO | DBCH2245 | serial_number: DBCH2245; hull_number: Exca 25; dealer: United Tractors; capacity: 138 HP / 1.0 M3; project_name: WUR EW | 95 |
| `EXC-00015` | EXC-00015 | EXC-00009 | Excavator PC 200-10 MO | DBCH2941 | hull_number: Exca 29; dealer: United Tractors; capacity: 138 HP / 1.0 M3; project_name: WUR EW | 100 |

## 4. Antrean Kasus Konflik & Ambigu (Wajib Verifikasi Manusia)

| Baris REKAP | Nama Asset | Identitas Sumber | Unit DB Terkait | Catatan Evaluator |
|---|---|---|---|---|
| Baris 15 | Excavator PC 200-10 MO | C51502 | EXC-00001 (EXC-00001), EXC-00012 (EXC-00012) | Terdeteksi skor sama pada lebih dari satu unit DB. Ditahan. |
| Baris 17 | Excavator PC 200-10 MO | DBCH1801 | EXC-00017 (EXC-00017), EXC-00022 (EXC-00022) | Terdeteksi skor sama pada lebih dari satu unit DB. Ditahan. |
| Baris 1 | Truck Box Isuzu NMR 81 | B 9096 ZYT | EXC-00006 (EXC-00006), ML-00002 (ML-00002) | Terdeteksi skor sama pada lebih dari satu unit DB. Ditahan. |
| Baris 1 | PRIMOVER MERCEDES-BENZ AXOR 4928 T | KT 9287 KU | FBT-00008 (FBT-00008), PM-00004 (PM-00004) | Terdeteksi skor sama pada lebih dari satu unit DB. Ditahan. |
| Baris 50 | TOYOTA HILUX | B9123ZBA | LV-00030 (LV-00030), LV-00051 (LV-00051) | Terdeteksi skor sama pada lebih dari satu unit DB. Ditahan. |

## 5. Sampel NEW_CANDIDATE dari REKAP 2026 (Tidak Ada di 283 Unit DB)

| Baris | Nama Asset | Kode Unit Sumber | Lambung | SN / Pelat | Kategori | Lokasi |
|---|---|---|---|---|---|---|
| 1 | Bomag Smooth Drum Compactor  BW211D-40 SL | SD 01 | VIBRO BW BRA-01 | 961582391008 | VIBROROLLER | MAILI |
| 2 | Bomag Smooth Drum Compactor  BW211D-40 | SD 02 | SD-00001 | 961582391036 | VIBROROLLER | NDD |
| 3 | Bomag Smooth Drum Compactor  BW211D-40SL | SD 03 | VIBRO BW BRA-03 | 961582391145 | VIBROROLLER | MALILI |
| 9 | CAT Smoothdrum CS10GC | PF 04 | SD-41002 | T8F00361 | VIBROROLLER | KUBU |
| 10 | Bomag Smooth Drum Compactor  BW211D-40SL | SD 07 | VIBRO BW BRA-07 | 961582391103 | VIBROROLLER | KENDARI |
| 11 | Bomag Smooth Drum Compactor  BW211D-40SL | SD 08 | VIBRO BW BRA-08 | 961582391660 | VIBROROLLER | YARD DURI |
| 16 | CAT Pad Foot CS10GC | SD 11 |  | T8F03605 | VIBROROLLER | ADERA |
| 17 | CAT Pad Foot CS10GC | SD 12 |  | T8F03606 | VIBROROLLER | ADERA |
| 19 | Sheep Foot XCMG  XS250PD | PF08 | PF-41002 | XUG01131PSJE06060 | VIBROROLLER | CENTRAL |
| 20 | Sheep Foot XCMG  X113E PD | PF09 | SD-41003 |  | VIBROROLLER | CENTRAL |
| 2 | MOTOR GRADER KOMATSU GD535-5 | GR 2 | MG-02 | 2066 | MOTOR GRADER | ADERA |
| 3 | MOTOR GRADER KOMATSU GD535-5 | GR3 | MG-41002 | 1372 | MOTOR GRADER | CENTRAL |
| 4 | MOTOR GRADER KOMATSU GD535-5 | GR4 | MG-04 | 2259 | MOTOR GRADER | ADERA |
| 6 | MOTOR GRADER KOMATSU GD535-5 | GR6 | MG BRA-06 | 1319 | MOTOR GRADER | BAHODOPI |
| 7 | MOTOR GRADER KOMATSU GD535-5 |  |  |  |  |  |
