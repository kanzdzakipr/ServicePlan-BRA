-- ============================================================================
-- scripts/migrate_batch16_fleet_governance_views.sql
-- Batch 16 Migration: Database Fleet Governance Views & Scoping
-- ============================================================================

-- 1. View: Active Assets (Single Source of Truth per October 2026 Fleet)
CREATE OR REPLACE VIEW v_active_assets AS
SELECT 
    a.asset_id,
    a.asset_code,
    a.serial_number,
    a.license_plate,
    a.previous_license_plate,
    a.hull_number,
    a.alias_name,
    a.category,
    a.make_model,
    a.type,
    a.capacity,
    a.year_manufacture,
    a.ownership,
    a.dealer,
    a.warranty,
    a.project_name,
    a.operational_position,
    a.raw_location_notes,
    a.current_operator_name,
    a.status,
    a.current_location_id,
    a.last_hm_km,
    a.last_service_date,
    a.last_service_hm_km,
    a.next_service_due_hm_km,
    a.next_service_estimated_date,
    a.service_interval_val,
    a.service_interval_unit,
    a.legal_stnk_expiry,
    a.legal_kir_expiry,
    a.legal_silo_expiry,
    a.is_active,
    a.created_at,
    a.last_update_timestamp
FROM assets a
WHERE a.is_active = 1 
  AND a.status != 'INACTIVE';

-- 2. View: Archived Assets (Complete Audit Trail of Sold & Demobilized Fleet)
CREATE OR REPLACE VIEW v_archived_assets AS
SELECT 
    a.asset_id,
    a.asset_code,
    a.serial_number,
    a.license_plate,
    a.previous_license_plate,
    a.hull_number,
    a.alias_name,
    a.category,
    a.make_model,
    a.project_name,
    a.operational_position,
    a.raw_location_notes,
    a.status,
    a.is_active,
    arc.archived_at,
    arc.archived_by_name,
    arc.reason AS archive_reason
FROM assets a
LEFT JOIN archived_items arc 
    ON arc.item_type = 'asset' 
   AND arc.item_id = a.asset_id
WHERE a.is_active = 0 
   OR a.status = 'INACTIVE';

-- 3. View: Fleet Project Summary (Real-time aggregation per project & category)
CREATE OR REPLACE VIEW v_fleet_project_summary AS
SELECT 
    COALESCE(NULLIF(a.project_name, ''), '(Unassigned)') AS project_name,
    a.category,
    COUNT(*) AS total_units,
    SUM(CASE WHEN a.status = 'OPERATING' THEN 1 ELSE 0 END) AS operating_units,
    SUM(CASE WHEN a.status = 'STANDBY' THEN 1 ELSE 0 END) AS standby_units,
    SUM(CASE WHEN a.status = 'INSPEKSI' THEN 1 ELSE 0 END) AS inspeksi_units,
    SUM(CASE WHEN a.status = 'BREAKDOWN' THEN 1 ELSE 0 END) AS breakdown_units,
    SUM(CASE WHEN a.status = 'ACCIDENT_HOLD' THEN 1 ELSE 0 END) AS accident_hold_units
FROM assets a
WHERE a.is_active = 1 
  AND a.status != 'INACTIVE'
GROUP BY COALESCE(NULLIF(a.project_name, ''), '(Unassigned)'), a.category
ORDER BY total_units DESC, project_name ASC;

