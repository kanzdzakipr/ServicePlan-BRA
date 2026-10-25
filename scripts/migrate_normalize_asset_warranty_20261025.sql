-- Migration: Normalize 'Warranty (Tidak Ada HM)' to 'Warranty' in assets table
UPDATE `assets`
SET `warranty` = 'Warranty'
WHERE `warranty` = 'Warranty (Tidak Ada HM)'
   OR LOWER(`warranty`) = 'warranty (tidak ada hm)';
