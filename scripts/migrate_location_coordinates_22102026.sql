-- ----------------------------------------------------------------------------
-- Additive Migration: Populate coordinates for all site locations
-- Ensures all sites/locations have baseline latitude & longitude for unit position fallback
-- Date: 22/10/2026
-- ----------------------------------------------------------------------------

UPDATE `locations`
SET `latitude` = 1.2854600, `longitude` = 101.2185700, `location_type` = 'Yard', `region` = 'Duri, Riau'
WHERE `location_name` = 'Yard KM 12 Duri' AND (`latitude` IS NULL OR `longitude` IS NULL);

UPDATE `locations`
SET `latitude` = 1.7321000, `longitude` = 100.8214000, `location_type` = 'Site Area', `region` = 'Bangko, Riau'
WHERE `location_name` = 'Site Bangko' AND (`latitude` IS NULL OR `longitude` IS NULL);

UPDATE `locations`
SET `latitude` = 1.3054000, `longitude` = 101.2215000, `location_type` = 'Site Area', `region` = 'Duri, Riau'
WHERE `location_name` = 'Site NDD' AND (`latitude` IS NULL OR `longitude` IS NULL);

UPDATE `locations`
SET `latitude` = 1.3012000, `longitude` = 101.1984000, `location_type` = 'Borrow Pit', `region` = 'Duri, Riau'
WHERE `location_name` = 'Site Celcin' AND (`latitude` IS NULL OR `longitude` IS NULL);

UPDATE `locations`
SET `latitude` = 0.5070680, `longitude` = 101.4477790, `location_type` = 'Branch', `region` = 'Pekanbaru, Riau'
WHERE `location_name` = 'Head Office Pekanbaru' AND (`latitude` IS NULL OR `longitude` IS NULL);

-- Duri Oilfield Well Pads: populate deterministic coordinates in Duri steamflood operational zone
UPDATE `locations`
SET `latitude` = ROUND(1.2950000 + (CAST(CRC32(location_name) % 180 AS SIGNED) - 90) * 0.00025, 7),
    `longitude` = ROUND(101.2250000 + (CAST((CRC32(location_name) >> 8) % 180 AS SIGNED) - 90) * 0.00025, 7),
    `location_type` = 'Site Area',
    `region` = 'Duri, Riau'
WHERE (`location_name` LIKE 'Well Pad %' OR `location_name` LIKE 'Pad %')
  AND (`latitude` IS NULL OR `longitude` IS NULL);

-- Any remaining locations in Riau/Pekanbaru fallback to Duri yard coordinates
UPDATE `locations`
SET `latitude` = 1.2854600, `longitude` = 101.2185700, `region` = 'Duri, Riau'
WHERE `latitude` IS NULL OR `longitude` IS NULL;

-- Ensure CS-41001 has assigned location (Yard KM 12 Duri)
UPDATE `assets`
SET `current_location_id` = 12, `raw_location_notes` = 'Yard KM 12 Duri'
WHERE `asset_id` = 'CS-41001' AND `current_location_id` IS NULL;

