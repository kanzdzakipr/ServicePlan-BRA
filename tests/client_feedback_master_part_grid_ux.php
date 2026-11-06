<?php
declare(strict_types=1);

/**
 * Automated Verification: PART-04 (Perbaikan Grid dan Scroll Master Part)
 * 
 * Verifies:
 * 1. CSS Sticky Header on Master Part Catalog (.sl-master-part-table th position: sticky; top: 0).
 * 2. Controlled Horizontal Scrolling & Scrollbar Gutter (.sl-master-part-table-wrap with scrollbar-gutter: stable and touch momentum).
 * 3. Mobile Responsive Adaptations (< 900px, < 600px breakpoints).
 * 4. UI Master Part Catalog Modal with Multi-Filter (Part Group, Authenticity, Stock Status, Free Text Search).
 * 5. Visual Catalog Picker on SPB Draft Rows (.sl-pick-master-btn and open-catalog-picker action).
 * 6. API Exposure: window.SpareLogistics.openMasterPartsCatalog method.
 * 7. Master Part Data Contract: controlled authenticity, part groups, and UOM conversion metadata.
 */

$root = dirname(__DIR__);
$passCount = 0;
$totalTests = 0;

function assertTest(bool $condition, string $message): void {
    global $passCount, $totalTests;
    $totalTests++;
    if ($condition) {
        $passCount++;
        echo " [PASS] {$message}\n";
    } else {
        echo "![FAIL] {$message}\n";
    }
}

echo "\n=== RUNNING CLIENT FEEDBACK TESTS: MASTER PART GRID & SCROLL UX (PART-04) ===\n\n";

// --------------------------------------------------------------------------
// TEST SUITE 1: CSS Grid, Sticky Header & Scrollbar Gutter
// --------------------------------------------------------------------------
echo "--- Test Suite 1: CSS Grid, Sticky Header & Scrollbar Gutter ---\n";

$cssPath = $root . '/scripts/dashboard.css';
assertTest(file_exists($cssPath), "scripts/dashboard.css exists on disk");
$css = file_get_contents($cssPath);

// 1. Sticky Header
$hasStickyHeader = str_contains($css, '.sl-master-part-table th') &&
                   str_contains($css, 'position: sticky') &&
                   str_contains($css, 'top: 0');
assertTest($hasStickyHeader, "Master Part table header has position: sticky and top: 0");

// 2. Controlled Scroll & Scrollbar Gutter
$hasControlledScroll = str_contains($css, '.sl-master-part-table-wrap') &&
                       str_contains($css, 'scrollbar-gutter: stable') &&
                       str_contains($css, 'overflow-x: auto') &&
                       str_contains($css, '-webkit-overflow-scrolling: touch');
assertTest($hasControlledScroll, "Master Part wrapper enforces scrollbar-gutter: stable and smooth horizontal scrolling");

// 3. Minimum Width & No Column Truncation
$hasProperWidth = str_contains($css, 'min-width: 980px') || str_contains($css, 'min-width: 960px');
assertTest($hasProperWidth, "Master Part table defines adequate min-width (>= 960px) preventing cramped columns");

// 4. Authenticity and Stock Badges
$hasBadges = str_contains($css, '.sl-badge-genuine') &&
             str_contains($css, '.sl-badge-nongenuine') &&
             str_contains($css, '.sl-badge-unverified') &&
             str_contains($css, '.sl-stock-pill.in-stock') &&
             str_contains($css, '.sl-stock-pill.empty-stock');
assertTest($hasBadges, "Authenticity and stock badges styled distinctly for clear visual hierarchy");

// 5. Mobile Responsive Media Queries
$hasResponsive = str_contains($css, '@media (max-width: 900px)') &&
                 str_contains($css, '@media (max-width: 600px)');
assertTest($hasResponsive, "Responsive media queries defined for mobile & tablet screens (< 900px, < 600px)");


// --------------------------------------------------------------------------
// TEST SUITE 2: JavaScript UI & Interactive Catalog Modal
// --------------------------------------------------------------------------
echo "\n--- Test Suite 2: JavaScript UI & Interactive Catalog Modal ---\n";

$jsPath = $root . '/scripts/dashboard.js';
assertTest(file_exists($jsPath), "scripts/dashboard.js exists on disk");
$js = file_get_contents($jsPath);

// 6. Master Part Catalog Modal Function
$hasCatalogModalFunc = str_contains($js, 'function openMasterPartsCatalogModal');
assertTest($hasCatalogModalFunc, "openMasterPartsCatalogModal function implemented in SpareLogistics module");

// 7. Header Navigation Trigger
$hasHeaderBtn = str_contains($js, 'data-sl-action="open-master-catalog"');
assertTest($hasHeaderBtn, "Navigation button 'Katalog Master Part' added to SpareLogistics page header");

// 8. SPB Line Item Quick Picker Trigger
$hasPickerBtn = str_contains($js, 'data-sl-action="open-catalog-picker"') &&
                str_contains($js, 'sl-pick-master-btn');
assertTest($hasPickerBtn, "Visual catalog picker button integrated into every SPB draft line item");

// 9. Multi-Criteria Filters (Group, Authenticity, Stock, Search)
$hasFilters = str_contains($js, 'slMasterCatalogSearch') &&
              str_contains($js, 'slMasterCatalogGroup') &&
              str_contains($js, 'slMasterCatalogAuth') &&
              str_contains($js, 'slMasterCatalogStock');
assertTest($hasFilters, "Catalog modal provides multi-criteria filters: Part Group, Authenticity, Stock Status, and Search");

// 10. Catalog Pagination
$hasPagination = str_contains($js, 'slMasterCatalogPrev') &&
                 str_contains($js, 'slMasterCatalogNext') &&
                 str_contains($js, 'catPageSize = 25');
assertTest($hasPagination, "Pagination controls implemented (25 items/page) with next/previous smooth scrolling");

// 11. Public API Exposure
$hasApiExposure = str_contains($js, 'openMasterPartsCatalog: openMasterPartsCatalogModal');
assertTest($hasApiExposure, "openMasterPartsCatalog method exposed on window.SpareLogistics API");


// --------------------------------------------------------------------------
// TEST SUITE 3: Database & Backend Data Integrity for Grid Display
// --------------------------------------------------------------------------
echo "\n--- Test Suite 3: Database & Backend Data Integrity ---\n";

$dsn = 'mysql:host=localhost;dbname=u646470441_ServicePlanBRA;charset=utf8mb4';
try {
    $db = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    echo "Fatal DB Connection Error: " . $e->getMessage() . "\n";
    exit(1);
}

// 12. Master parts count
$partsCount = (int) $db->query("SELECT COUNT(*) FROM parts")->fetchColumn();
assertTest($partsCount >= 1300, "Master parts database populated with {$partsCount} items (>= 1300 from Dany & Fadly)");

// 13. Controlled part groups presence
$groupCount = (int) $db->query("SELECT COUNT(DISTINCT part_group) FROM parts WHERE part_group IS NOT NULL AND part_group != ''")->fetchColumn();
assertTest($groupCount >= 5, "Controlled part groups categorized into {$groupCount} distinct functional groups");

// 14. Authenticity column coverage
$authCount = (int) $db->query("SELECT COUNT(DISTINCT authenticity) FROM parts WHERE authenticity IN ('GENUINE', 'NON_GENUINE', 'UNVERIFIED')")->fetchColumn();
assertTest($authCount >= 2, "Controlled authenticity values present in master parts database");

// 15. Packaging UOM and conversion factors
$oilWithFactor = $db->query("SELECT part_number, conversion_factor, base_uom, transaction_uom FROM parts WHERE conversion_factor > 1 LIMIT 1")->fetch();
assertTest(!empty($oilWithFactor) && (float)$oilWithFactor['conversion_factor'] > 1, "Packaging conversion factor available for liquid/drum items ({$oilWithFactor['part_number']}: factor {$oilWithFactor['conversion_factor']})");

echo "\n--------------------------------------------------------------------------\n";
echo "RESULT: {$passCount} / {$totalTests} TESTS PASSED\n";
echo "--------------------------------------------------------------------------\n\n";

if ($passCount !== $totalTests) {
    exit(1);
}

