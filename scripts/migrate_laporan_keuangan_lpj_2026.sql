-- ============================================================================
-- Migration: Sinkronisasi LPJ Bulanan 2026 dari material/LAPORAN-KEUANGAN
-- Sumber Data: 02_LPJ_Bulanan_Budget_vs_Realisasi_2026.md & 01_Monitoring_Progress...md
-- Plafon Budget Resmi 2026: Rp 773.876.683 / bulan (Rp 9.286.520.190 / tahun)
-- ============================================================================

-- Bersihkan data legacy dummy / duplikat (cost_id <= 20)
DELETE FROM `cost_financial_monthly` WHERE `cost_id` <= 20;
DELETE FROM `cost_financial_monthly` WHERE `month_label` IN (
  'Jan 2026', 'Feb 2026', 'Mar 2026', 'Apr 2026', 'Mei 2026', 'Jun 2026',
  'Jul 2026', 'Agu 2026', 'Sep 2026', 'Okt 2026', 'Nov 2026', 'Des 2026'
);

-- Masukkan 12 Bulan Resmi Tahun Anggaran 2026
INSERT INTO `cost_financial_monthly` (`month_label`, `year_period`, `budget_amount`, `actual_amount`) VALUES
('Jan 2026', 2026, 773876683.00, 801938719.00),
('Feb 2026', 2026, 773876683.00, 810292794.00),
('Mar 2026', 2026, 773876683.00, 849084485.00),
('Apr 2026', 2026, 773876683.00, 925757929.00),
('Mei 2026', 2026, 773876683.00, 1222546323.00),
('Jun 2026', 2026, 773876683.00, 777691954.00),
('Jul 2026', 2026, 773876683.00, 820000000.00),
('Agu 2026', 2026, 773876683.00, 1628644231.00),
('Sep 2026', 2026, 773876683.00, 1605534315.00),
('Okt 2026', 2026, 773876683.00, 1010493854.00),
('Nov 2026', 2026, 773876683.00, 0.00),
('Des 2026', 2026, 773876683.00, 0.00);

