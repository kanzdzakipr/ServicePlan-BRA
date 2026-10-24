<?php
declare(strict_types=1);

if (!defined('DASHBOARD_RENDER_ALLOWED') || DASHBOARD_RENDER_ALLOWED !== true) {
    header('Location: index.html?reason=session', true, 302);
    exit;
}

$settingsMap = [];
try {
    $stSettings = $db->query("SELECT setting_key, setting_value FROM operational_settings");
    if ($stSettings) {
        while ($sRow = $stSettings->fetch(PDO::FETCH_ASSOC)) {
            $settingsMap[$sRow['setting_key']] = $sRow['setting_value'];
        }
    }
} catch (Throwable $t) {
    // Graceful fallback if operational_settings table is not yet migrated
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Maintenance & Fleet Monitoring System</title>
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet for Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="scripts/dashboard.css?v=<?= filemtime(__DIR__ . '/scripts/dashboard.css') ?>">
    <link rel="stylesheet" href="scripts/unit-properties.css?v=20261023-vital-link-1">
    <link rel="stylesheet" href="scripts/site-properties.css?v=20261023-cluster-2">
    <meta name="csrf-token" content="<?= htmlspecialchars(api_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <script>window.APP_CSRF_TOKEN = '<?= htmlspecialchars(api_csrf_token(), ENT_QUOTES, 'UTF-8') ?>';</script>
    <script src="scripts/auth-client.js?v=20260809-1"></script>
    <script src="scripts/rbac.js?v=20260823-1"></script>
    <script src="scripts/logistics_data.js?v=20260801-1"></script>
    <script src="scripts/report-xlsx-template.js?v=20260731-2"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="scripts/dashboard.js?v=<?= filemtime(__DIR__ . '/scripts/dashboard.js') ?>"></script>
    <script src="scripts/unit-properties.js?v=20261023-vital-link-1"></script>
    <script src="scripts/site-properties.js?v=20261023-cluster-2"></script>
</head>

<body>
    <!-- Floating Side Hover Tooltip Element -->
    <div id="sidebarHoverTooltip" class="sidebar-hover-tooltip"></div>

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="assets/logo-pt-bina-rekayasa-anugrah.png" alt="PT Bina Rekayasa Anugrah" class="sidebar-logo-img sidebar-logo-full">
            <div class="sidebar-logo-circle-container" title="PT Bina Rekayasa Anugrah">
                <img src="assets/logogram-pt-bina-rekayasa-anugrah.png" alt="PT BRA Logogram" class="sidebar-logo-circle-img">
            </div>
            <span class="brand-label">FleetMonitor</span>
        </div>
        <ul class="sidebar-menu">
            <li class="nav-section-label">Overview</li>
            <li><a onclick="showView('dashboard')" id="menu-dashboard" class="active" role="button" tabindex="0"
                    aria-current="page" data-title="Dashboard"><i class="fa-solid fa-gauge-high"></i><span
                        class="nav-label">Dashboard</span></a></li>
            <li><a onclick="showView('monitoring')" id="menu-monitoring" role="button" tabindex="0"
                    data-title="Monitoring Unit"><i class="fa-solid fa-location-dot"></i><span class="nav-label">Monitoring
                        Unit</span></a></li>

            <li class="nav-section-label">Asset & Maintenance</li>
            <li><a onclick="showView('asset')" id="menu-asset" role="button" tabindex="0" data-title="Master Asset"><i
                        class="fa-solid fa-cubes"></i><span class="nav-label">Master Asset</span></a></li>
            <li><a onclick="showView('inspection', '', 'menu-inspection')" id="menu-inspection" role="button"
                    tabindex="0" data-title="Inspeksi & P2H"><i class="fa-solid fa-clipboard-check"></i><span
                        class="nav-label">Inspeksi & P2H</span></a></li>
            <li><a onclick="showView('wo')" id="menu-wo" role="button" tabindex="0" data-title="Work Order"><i
                        class="fa-solid fa-screwdriver-wrench"></i><span class="nav-label">Work Order</span></a></li>
            <li><a onclick="showView('pm')" id="menu-pm" role="button" tabindex="0" data-title="Preventive Maintenance"><i
                        class="fa-regular fa-calendar-check"></i><span class="nav-label">Preventive Maint.</span></a>
            </li>
            <li><a onclick="showView('condition', '', 'menu-condition')" id="menu-condition" role="button" tabindex="0"
                    data-title="Condition Monitoring"><i class="fa-solid fa-stethoscope"></i><span
                        class="nav-label">Condition Monitoring</span></a></li>

            <li class="nav-section-label">Operations</li>
            <li><a onclick="showView('logistics', '', 'menu-logistics')" id="menu-logistics" role="button" tabindex="0"
                    data-title="Spare Part & Logistik"><i class="fa-solid fa-boxes-stacked"></i><span class="nav-label">Spare
                        Part & Logistik</span></a></li>
            <li><a onclick="showView('fuel', '', 'menu-fuel'); window.refreshFuelLogsFromServer?.()" id="menu-fuel" role="button" tabindex="0"
                    data-title="Fuel Management"><i class="fa-solid fa-gas-pump"></i><span class="nav-label">Fuel</span></a>
            </li>
            <li><a onclick="showView('productivity', '', 'menu-productivity')" id="menu-productivity" role="button"
                    tabindex="0" data-title="Produktivitas"><i class="fa-solid fa-chart-line"></i><span
                        class="nav-label">Produktivitas</span></a></li>
            <li><a onclick="showView('biaya')" id="menu-biaya" role="button" tabindex="0" data-title="Biaya"><i
                        class="fa-solid fa-money-bill-trend-up"></i><span class="nav-label">Biaya</span></a></li>

            <li class="nav-section-label">Governance</li>
            <li><a onclick="showView('people', '', 'menu-people')" id="menu-people" role="button" tabindex="0"
                    data-title="People & KPI"><i class="fa-solid fa-users-gear"></i><span class="nav-label">People &
                        KPI</span></a></li>
            <li><a onclick="showView('hse', '', 'menu-hse')" id="menu-hse" role="button" tabindex="0"
                    data-title="HSE / Accident"><i class="fa-solid fa-triangle-exclamation"></i><span class="nav-label">HSE /
                        Accident</span></a></li>
            <li><a onclick="showView('reports')" id="menu-reports" role="button" tabindex="0" data-title="Laporan & Form"><i
                        class="fa-solid fa-file-invoice"></i><span class="nav-label">Laporan & Form</span></a></li>
            <li><a onclick="showView('approval', '', 'menu-approval')" id="menu-approval" role="button" tabindex="0"
                    data-title="Approval"><i class="fa-solid fa-check-double"></i><span class="nav-label">Approval</span></a>
            </li>

            <li class="nav-section-label">System</li>
            <li><a onclick="showView('archive', '', 'menu-archive'); window.loadArchiveRecords?.();" id="menu-archive"
                    role="button" tabindex="0" data-title="Archive"><i class="fa-solid fa-box-archive"></i><span
                        class="nav-label">Archive</span></a>
            </li>
            <li><a onclick="showView('settings', '', 'menu-settings')" id="menu-settings" role="button" tabindex="0"
                    data-title="Pengaturan"><i class="fa-solid fa-gear"></i><span class="nav-label">Pengaturan</span></a></li>
        </ul>
    </aside>

    <!-- Main Wrapper -->
    <div class="main-wrapper">

        <!-- Header -->
        <header class="top-header">
            <div class="header-left">
                <button type="button" id="sidebarToggle" onclick="toggleSidebar()" class="btn-sidebar-toggle"
                    title="Kecilkan / Perluas Navbar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="dropdown">
                    <select id="globalProjectFilter" onchange="applyGlobalProjectFilter(this.value)"
                        aria-label="Filter global berdasarkan project atau lokasi">
                        <option value="">Semua</option>
                    </select>
                </div>
            </div>
            <div class="header-right">
                <!-- Mobile Search Icon Toggle Button -->
                <button type="button" class="btn-mobile-search-toggle" id="btnMobileSearchToggle"
                    onclick="toggleMobileSearch(event)" title="Pencarian Unit & WO" aria-label="Buka pencarian aset dan unit">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>

                <!-- System Notifications Bell Wrapper -->
                <div class="notification-wrapper" style="position: relative;">
                    <button type="button" class="btn-notification-bell" id="btnNotificationBell" onclick="toggleNotificationDropdown(event)" title="Notifikasi Aktivitas Sistem">
                        <i class="fa-solid fa-bell"></i>
                        <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                    </button>
                    <!-- Floating Notification Dropdown Panel -->
                    <div class="notification-dropdown-panel" id="notificationDropdownPanel" style="display: none;" onclick="event.stopPropagation()">
                        <div class="notification-panel-header">
                            <div class="notification-title-group">
                                <h3><i class="fa-solid fa-bell text-primary"></i> Notifikasi Aktivitas</h3>
                                <span class="notification-count-pill" id="notifUnreadPill">0 Belum Dibaca</span>
                            </div>
                            <button type="button" class="btn-mark-all-read" onclick="markAllNotificationsAsRead()" title="Tandai Semua Dibaca">
                                <i class="fa-solid fa-check-double"></i> Tandai Dibaca
                            </button>
                        </div>
                        <div class="notification-panel-tabs">
                            <button type="button" class="notif-tab-btn active" data-filter="ALL" onclick="filterNotifications('ALL', this)">Semua</button>
                            <button type="button" class="notif-tab-btn" data-filter="UNREAD" onclick="filterNotifications('UNREAD', this)">Belum Dibaca</button>
                        </div>
                        <div class="notification-panel-list" id="notificationPanelList">
                            <!-- Populated dynamically via JS -->
                        </div>
                        <div class="notification-panel-footer">
                            <small><i class="fa-solid fa-shield-halved"></i> Audit Trail & Activity Log Terintegrasi</small>
                        </div>
                    </div>
                </div>

                <div class="search-bar" id="globalSearchBarWrapper" style="position: relative;">
                    <input type="search" id="globalSearchInput" oninput="handleGlobalSearch()"
                        onfocus="handleGlobalSearch()" aria-controls="globalSearchResults" aria-expanded="false"
                        aria-label="Cari kode unit, nomor lambung, plat, atau nomor seri"
                        placeholder="Cari kode unit, lambung, plat/SN..." autocomplete="off">
                    <div id="globalSearchResults" role="region" aria-label="Hasil pencarian aset" aria-live="polite"
                        style="display: none; position: absolute; top: calc(100% + 5px); left: 0; width: 100%; min-width: 350px; background: white; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); z-index: 1000; max-height: 400px; overflow-y: auto;">
                        <!-- Results injected here -->
                    </div>
                </div>
                <div class="profile-wrapper" style="position: relative;">
                    <button type="button" class="btn-profile-avatar" id="btnProfileAvatar"
                        onclick="toggleProfileDropdown(event)" title="Profil Pengguna & Sesi Login">
                        <img src="https://ui-avatars.com/api/?name=Admin+Manager&background=0284c7&color=fff&font-weight=700"
                            alt="User" id="headerProfileAvatarImg">
                    </button>
                    <!-- Floating Profile Popover Panel -->
                    <div class="profile-dropdown-panel" id="profileDropdownPanel" style="display: none;"
                        onclick="event.stopPropagation()">
                        <div class="profile-dropdown-header">
                            <img src="https://ui-avatars.com/api/?name=Admin+Manager&background=0284c7&color=fff&font-weight=700"
                                alt="User" id="profileDropdownAvatarImg" class="profile-dropdown-avatar">
                            <div class="profile-dropdown-info">
                                <strong id="profileDropdownName">Memuat pengguna...</strong>
                                <small id="profileDropdownRole">Administrator</small>
                            </div>
                        </div>
                        <div class="profile-dropdown-divider"></div>
                        <div class="profile-dropdown-actions">
                            <button type="button" id="btnChangePassword" class="btn-profile-password" onclick="openSelfChangePasswordModal()" title="Ubah kata sandi akun">
                                <i class="fa-solid fa-key profile-btn-icon"></i>
                                <span class="profile-btn-text">Ganti Password</span>
                                <i class="fa-solid fa-chevron-right profile-btn-chevron"></i>
                            </button>
                            <button type="button" id="logoutButton" class="btn-profile-logout" title="Keluar dari sesi">
                                <i class="fa-solid fa-right-from-bracket profile-btn-icon"></i>
                                <span class="profile-btn-text">Keluar / Logout</span>
                                <i class="fa-solid fa-chevron-right profile-btn-chevron"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="content">

            <!-- VIEW: DASHBOARD (MVP 1) -->
            <div id="view-dashboard" class="view-section active">
                <h1 class="page-title">Executive Dashboard</h1>

                <!-- Ringkasan Status Telemetri Fleet -->
                <div class="telemetry-status-grid">
                    <div class="telemetry-metric-item">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Total Unit Fleet</span>
                            <i class="fa-solid fa-truck" style="color: var(--text-muted); font-size: 0.9rem;"></i>
                        </div>
                        <div class="telemetry-metric-value" id="kpiTotal">-</div>
                        <div class="telemetry-metric-footer">
                            <span class="badge badge-info">Semua Unit</span>
                            <button type="button" onclick="openStatusDetailModal('ALL')" class="telemetry-action-link"
                                title="Lihat Tabulasi Semua Unit">
                                Detail <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>

                    <div class="telemetry-metric-item">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Operating / Ready</span>
                            <i class="fa-solid fa-circle-check" style="color: var(--success); font-size: 0.9rem;"></i>
                        </div>
                        <div class="telemetry-metric-value" id="kpiReady" style="color: var(--success);">-</div>
                        <div class="telemetry-metric-footer">
                            <span class="badge badge-success">Operational</span>
                            <button type="button" onclick="openStatusDetailModal('READY')" class="telemetry-action-link"
                                title="Lihat Tabulasi Unit Ready">
                                Detail <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>

                    <div class="telemetry-metric-item">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Unit Standby</span>
                            <i class="fa-solid fa-pause-circle" style="color: var(--info); font-size: 0.9rem;"></i>
                        </div>
                        <div class="telemetry-metric-value" id="kpiStandby" style="color: var(--info);">-</div>
                        <div class="telemetry-metric-footer">
                            <span class="badge badge-info">Idle / Standby</span>
                            <button type="button" onclick="openStatusDetailModal('STANDBY')"
                                class="telemetry-action-link" title="Lihat Tabulasi Unit Standby">
                                Detail <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>

                    <div class="telemetry-metric-item">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Breakdown Unit</span>
                            <i class="fa-solid fa-triangle-exclamation"
                                style="color: var(--danger); font-size: 0.9rem;"></i>
                        </div>
                        <div class="telemetry-metric-value" id="kpiBreakdown" style="color: var(--danger);">-</div>
                        <div class="telemetry-metric-footer">
                            <span class="badge badge-danger">Out of Order</span>
                            <button type="button" onclick="openStatusDetailModal('BREAKDOWN')"
                                class="telemetry-action-link" title="Lihat Tabulasi Unit Breakdown">
                                Detail <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>

                    <div class="telemetry-metric-item">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Inspeksi & PM</span>
                            <i class="fa-solid fa-clipboard-list" style="color: var(--warning); font-size: 0.9rem;"></i>
                        </div>
                        <div class="telemetry-metric-value" id="kpiInspeksi" style="color: var(--warning);">-</div>
                        <div class="telemetry-metric-footer">
                            <span class="badge badge-warning">In Service</span>
                            <button type="button" onclick="openStatusDetailModal('INSPEKSI')"
                                class="telemetry-action-link" title="Lihat Tabulasi Unit Inspeksi/PM">
                                Detail <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="dashboard-layout">
                    <div class="panel">
                        <div class="panel-header"
                            style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; position:relative;">
                            <span
                                style="font-weight:700; font-size:0.98rem; display:inline-flex; align-items:center; gap:8px;">
                                <i class="fa-solid fa-map-location-dot" style="color:var(--primary);"></i> Live Map
                                (Posisi Terakhir Unit)
                            </span>

                            <!-- Compact Filter Button & Dropdown Popover -->
                            <div style="position:relative;">
                                <button type="button" id="btnMapFilterToggle" onclick="toggleMapFilterPopover(event)"
                                    class="btn btn-sm"
                                    style="font-size:0.82rem; font-weight:600; padding:5px 12px; display:inline-flex; align-items:center; gap:6px; background:#ffffff; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer; color:#334155; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                                    <i class="fa-solid fa-sliders" style="color:var(--primary);"></i> Filter...
                                    <span id="mapFilterBadge"
                                        style="background:var(--primary); color:#fff; font-size:10px; padding:1px 6px; border-radius:10px; display:none;">0</span>
                                </button>

                                <!-- Floating Popover Card -->
                                <div id="mapFilterPopover" onclick="event.stopPropagation()" class="map-filter-card">
                                    <div
                                        style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; border-bottom:1px solid var(--border); padding-bottom:6px;">
                                        <strong style="color:var(--text-main); font-size:0.88rem;"><i
                                                class="fa-solid fa-filter" style="color:var(--primary);"></i> Filter
                                            Atribut Peta</strong>
                                        <button type="button" onclick="resetMapFilters()"
                                            style="background:none; border:none; color:var(--primary); font-size:0.78rem; font-weight:600; cursor:pointer;">Reset</button>
                                    </div>

                                    <!-- Status Filter -->
                                    <div class="map-filter-group">
                                        <label class="map-filter-label">Status Unit:</label>
                                        <select id="mapStatusFilter" class="form-control map-filter-input"
                                            onchange="applyMapFilters()">
                                            <option value="ALL">Semua Status</option>
                                            <option value="READY">Ready (Siap Kerja)</option>
                                            <option value="OPERATING">Operating (Beroperasi)</option>
                                            <option value="BREAKDOWN">Breakdown (Merah)</option>
                                            <option value="INSPEKSI">Inspeksi / P2H (Kuning)</option>
                                            <option value="STANDBY">Standby (Biru)</option>
                                        </select>
                                    </div>

                                    <!-- Location Filter -->
                                    <div class="map-filter-group">
                                        <label class="map-filter-label">Lokasi / Cabang:</label>
                                        <select id="mapLocationFilter" class="form-control map-filter-input"
                                            onchange="applyMapFilters()">
                                            <option value="ALL">Semua Lokasi / Cabang</option>
                                            <option value="pekanbaru">Pekanbaru Branch</option>
                                            <option value="duri">Duri Branch</option>
                                            <option value="perawang">Perawang Branch</option>
                                            <option value="dumai">Dumai Branch</option>
                                            <option value="pelalawan">Pelalawan Branch</option>
                                            <option value="kuansing">Kuansing Branch</option>
                                            <option value="rohil">Rohil Branch</option>
                                        </select>
                                    </div>

                                    <!-- Time Recency Filter -->
                                    <div class="map-filter-group">
                                        <label class="map-filter-label">Waktu Update Terakhir:</label>
                                        <select id="mapTimeFilter" class="form-control map-filter-input"
                                            onchange="applyMapFilters()">
                                            <option value="ALL">Semua Waktu Update</option>
                                            <option value="24H">&lt; 24 Jam Terakhir</option>
                                            <option value="7D">1 - 7 Hari Terakhir</option>
                                            <option value="OLD">&gt; 7 Hari</option>
                                        </select>
                                    </div>

                                    <!-- Search Input -->
                                    <div class="map-filter-group">
                                        <label class="map-filter-label">Cari Kode Unit / Kategori:</label>
                                        <input type="text" id="mapSearchInput"
                                            placeholder="Ketik Kode Unit (mis. DT-00001)..." onkeyup="applyMapFilters()"
                                            class="map-filter-input">
                                    </div>

                                    <!-- Toggle Cluster -->
                                    <div class="map-cluster-box">
                                        <span
                                            style="font-weight:600; color:var(--text-secondary); font-size:0.78rem;"><i
                                                class="fa-solid fa-object-group" style="color:var(--primary);"></i>
                                            Klusterisasi Titik</span>
                                        <input type="checkbox" id="mapClusterToggle" checked
                                            onchange="applyMapFilters()"
                                            style="cursor:pointer; width:16px; height:16px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="panel-body" style="padding: 0;">
                            <div id="map" style="height: 530px; border-radius: 0 0 8px 8px;"></div>
                        </div>
                    </div>
                    <div class="panel">
                        <div class="panel-header"><span><i class="fa-solid fa-triangle-exclamation"></i> Panel Perhatian
                                (WO Emergency)</span></div>
                        <div class="panel-body attention-panel-body">
                            <ul class="attention-list" id="attentionList" aria-live="polite"></ul>
                        </div>
                    </div>
                </div>

                <div class="dashboard-layout dashboard-data-layout" style="margin-top: 20px;">
                    <div class="panel dashboard-data-panel dashboard-category-panel">
                        <div class="panel-header"><span><i class="fa-solid fa-list"></i> Unit per Kategori</span></div>
                        <div class="panel-body" style="padding: 0;">
                            <div class="table-responsive dashboard-data-table-wrap">
                                <table class="dashboard-data-table dashboard-category-table">
                                    <colgroup>
                                        <col class="col-category">
                                        <col class="col-ready">
                                        <col class="col-standby">
                                        <col class="col-breakdown">
                                        <col class="col-inspection">
                                        <col class="col-total">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th>Kategori</th>
                                            <th class="dashboard-status-heading">
                                                <span class="dashboard-status-icon dashboard-status-ready" data-tooltip="Ready / OK" tabindex="0" role="img" aria-label="Ready / OK">
                                                    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                                </span>
                                            </th>
                                            <th class="dashboard-status-heading">
                                                <span class="dashboard-status-icon dashboard-status-standby" data-tooltip="Standby" tabindex="0" role="img" aria-label="Standby">
                                                    <i class="fa-solid fa-circle-pause" aria-hidden="true"></i>
                                                </span>
                                            </th>
                                            <th class="dashboard-status-heading">
                                                <span class="dashboard-status-icon dashboard-status-breakdown" data-tooltip="Breakdown" tabindex="0" role="img" aria-label="Breakdown">
                                                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                                                </span>
                                            </th>
                                            <th class="dashboard-status-heading">
                                                <span class="dashboard-status-icon dashboard-status-inspection" data-tooltip="Inspeksi / PM" tabindex="0" role="img" aria-label="Inspeksi / PM">
                                                    <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
                                                </span>
                                            </th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody id="dashboardCategoryTable">
                                        <!-- Diisi via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="panel dashboard-data-panel dashboard-work-order-panel">
                        <div class="panel-header dashboard-wo-panel-header">
                            <a href="#work-order" class="dashboard-panel-link" onclick="event.preventDefault(); showView('wo');"><i class="fa-solid fa-clipboard-list"></i> Work Order Aktif (Terbaru)</a>
                            <label class="dashboard-wo-category-filter" for="dashboardWoCategoryFilter">
                                <span>Jenis Unit</span>
                                <span class="dashboard-wo-filter-control">
                                    <i class="fa-solid fa-filter" aria-hidden="true"></i>
                                    <select id="dashboardWoCategoryFilter" aria-label="Filter Work Order berdasarkan jenis unit" onchange="window.filterDashboardWoByCategory(this.value)">
                                        <option value="ALL">Semua Jenis Unit</option>
                                    </select>
                                </span>
                            </label>
                        </div>
                        <div class="panel-body" style="padding: 0;">
                            <div class="table-responsive dashboard-data-table-wrap dashboard-wo-scroll" id="dashboardWoScroll">
                                <table class="dashboard-data-table dashboard-wo-table">
                                    <colgroup>
                                        <col class="col-wo-unit">
                                        <col class="col-status">
                                        <col class="col-priority">
                                        <col class="col-mechanic">
                                        <col class="col-downtime">
                                    </colgroup>
                                    <thead>
                                        <tr>
                                            <th>Work Order / Unit</th>
                                            <th>Status</th>
                                            <th>Prioritas</th>
                                            <th>Mekanik</th>
                                            <th>Downtime</th>
                                        </tr>
                                    </thead>
                                    <tbody id="dashboardWoTable">
                                        <!-- Diisi via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4 PANEL ANALYTICS EXECUTIVE DASHBOARD (GAMBAR 1 - 4) -->
                <div id="dashAnalyticsPanelsContainer"></div>
            </div>

            <!-- VIEW: MONITORING UNIT (Sesuai Permintaan User) -->
            <div id="view-monitoring" class="view-section">
                <div class="monitoring-title-row" style="margin-bottom:12px;">
                    <div>
                        <h1 class="page-title" style="margin:0;">Monitoring Unit</h1>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel-body">
                        <div class="monitoring-dashboard-layout">
                            <div id="monitoringSummary" class="monitoring-summary"></div>
                            <div class="monitoring-chart-card">
                                <div class="monitoring-chart-header">
                                    <span><i class="fa-solid fa-chart-pie" style="color:var(--primary); margin-right:4px;"></i> Komposisi Status Armada</span>
                                    <span id="monitoringChartTotalBadge" class="monitoring-chart-badge">0 Unit</span>
                                </div>
                                <div id="monitoringStatusDonutChart" style="min-height: 220px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel-header">
                        <span><i class="fa-solid fa-list-check"></i> Daftar Monitoring Status Unit</span>
                        <div class="monitoring-toolbar">
                            <select id="monitoringStatusFilter" class="form-control monitoring-status-select"
                                onchange="setMonitoringStatusFilter(this.value)" aria-label="Filter status monitoring">
                                <option value="ALL">Semua Status Unit</option>
                                <option value="READY">Ready</option>
                                <option value="OPERATING">Operating</option>
                                <option value="STANDBY">Standby</option>
                                <option value="INSPEKSI">Inspeksi / P2H</option>
                                <option value="BREAKDOWN">Breakdown</option>
                                <option value="ACCIDENT_HOLD">Accident Hold</option>
                            </select>
                            <div class="monitoring-search-wrap">
                                <i class="fa-solid fa-magnifying-glass monitoring-search-icon"></i>
                                <input type="text" id="searchMonitoring" placeholder="Cari unit ID, lokasi, status..."
                                    onkeyup="searchTableMonitoring()" aria-label="Cari unit monitoring">
                            </div>
                        </div>
                    </div>
                    <div class="panel-body" style="padding:0;">
                        <div class="table-responsive">
                            <table id="monitoringTable" style="margin-top:0;">
                                <thead>
                                    <tr>
                                        <th>Unit ID</th>
                                        <th>Kategori</th>
                                        <th>Lokasi</th>
                                        <th>Status Terakhir</th>
                                        <th>Tindakan Lanjutan</th>
                                    </tr>
                                </thead>
                                <tbody id="monTableBody">
                                    <!-- Populated via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW: MASTER ASSET (MVP 1) -->
            <!-- VIEW: MASTER ASSET -->
            <div id="view-asset" class="view-section">
                <div
                    style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap; gap:10px;">
                    <h1 class="page-title" style="margin:0;">Master Asset Armada</h1>
                    <div>
                        <button class="btn btn-primary" onclick="openModal('modalNewAsset')"><i
                                class="fa-solid fa-plus"></i> Registrasi Unit Baru</button>
                    </div>
                </div>

                <!-- Synchronized Master Asset Telemetry Strip -->
                <div class="telemetry-status-grid" style="grid-template-columns: repeat(6, 1fr);">
                    <div class="telemetry-metric-item" title="Total Armada Aset Terdaftar">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Total Armada Aset</span>
                            <i class="fa-solid fa-truck-monster" style="color: var(--primary); font-size: 0.9rem;"></i>
                        </div>
                        <div class="telemetry-metric-value" id="kpiAssetTotal">48 Unit</div>
                        <div class="telemetry-metric-footer">
                            <span class="badge badge-info">Inventaris Aset</span>
                        </div>
                    </div>

                    <div class="telemetry-metric-item" title="Unit Status Ready / Operasional">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Ready / Operasional</span>
                            <i class="fa-solid fa-circle-check" style="color: var(--success); font-size: 0.9rem;"></i>
                        </div>
                        <div class="telemetry-metric-value" id="kpiAssetReady" style="color: var(--success);">28 Unit
                        </div>
                        <div class="telemetry-metric-footer">
                            <span class="badge badge-success">Active Fleet</span>
                        </div>
                    </div>

                    <div class="telemetry-metric-item" title="Unit Status Standby / Idle">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Standby / Idle</span>
                            <i class="fa-solid fa-pause" style="color: var(--warning); font-size: 0.9rem;"></i>
                        </div>
                        <div class="telemetry-metric-value" id="kpiAssetStandby" style="color: var(--warning);">14 Unit
                        </div>
                        <div class="telemetry-metric-footer">
                            <span class="badge badge-warning">Available</span>
                        </div>
                    </div>

                    <div class="telemetry-metric-item" title="Unit Status Breakdown / Under Repair">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Breakdown / WO</span>
                            <i class="fa-solid fa-triangle-exclamation"
                                style="color: var(--danger); font-size: 0.9rem;"></i>
                        </div>
                        <div class="telemetry-metric-value" id="kpiAssetBreakdown" style="color: var(--danger);">6 Unit
                        </div>
                        <div class="telemetry-metric-footer">
                            <span class="badge badge-danger">Maintenance</span>
                        </div>
                    </div>

                    <div class="telemetry-metric-item" title="Total Valuasi Pasaran Armada Aset">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Valuasi Aset</span>
                            <i class="fa-solid fa-money-bill-trend-up"
                                style="color: var(--info); font-size: 0.9rem;"></i>
                        </div>
                        <div class="telemetry-metric-value tabular-nums" id="kpiAssetMarketVal"
                            style="color: var(--info); font-size: 1.35rem;">Rp 18.450 Jt</div>
                        <div class="telemetry-metric-footer">
                            <span class="badge badge-info">Market Valuation</span>
                        </div>
                    </div>

                    <div class="telemetry-metric-item" title="Physical Availability (PA) Ketersediaan Fisik Armada">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Phys. Availability</span>
                            <i class="fa-solid fa-chart-pie" style="color: var(--primary); font-size: 0.9rem;"></i>
                        </div>
                        <div class="telemetry-metric-value tabular-nums" id="kpiAssetPA"
                            style="color: var(--primary); font-size: 1.5rem;">87.5%</div>
                        <div class="telemetry-metric-footer">
                            <span class="badge badge-info">PA Ratio</span>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-header" style="display:flex; justify-content:space-between; align-items:center;">
                        <span><i class="fa-solid fa-list-check"></i> Daftar Unit Armada & Linkage Sistem
                            Integrasi</span>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <button class="btn btn-sm btn-primary" onclick="window.openMasterAssetPrintDialog()"><i class="fa-solid fa-print"></i> Cetak Laporan</button>
                            <button class="btn btn-sm"
                                style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;"
                                onclick="showView('archive'); window.switchArchiveTab('asset');"><i
                                    class="fa-solid fa-box-archive"></i> Lihat Arsip</button>
                        </div>
                    </div>
                    <div class="panel-body">
                        <!-- Master Asset Filter & Search Container -->
                        <div class="master-asset-filter-wrapper" style="border: 1px solid #dbe3ec; border-radius: 8px; background: #f8fafc; padding: 10px 12px; margin-bottom: 14px;">
                            <!-- Top Bar: Search Input + Advanced Link + Reset Button (Icon Only) -->
                            <div style="position: relative; display: flex; align-items: center; gap: 10px;">
                                <div style="position: relative; flex: 1;">
                                    <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--primary); font-size: 0.95rem;"></i>
                                    <input type="text" id="asset3ParamSearch" class="form-control" style="padding-left: 40px; padding-right: 14px; height: 40px; font-size: 0.88rem; border-radius: 6px; border: 1.5px solid #cbd5e1; background: #fff;" placeholder="Pencarian..." oninput="window.filterMasterAsset3Param(this.value)">
                                </div>
                                <a href="javascript:void(0)" id="toggleMasterAssetAdvanced" onclick="window.toggleMasterAssetAdvanced()" style="font-size: 0.84rem; font-weight: 600; color: #0284c7; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap; padding: 6px 8px; cursor: pointer;" title="Tampilkan / Sembunyikan Filter Spesifik">
                                    <span>Advanced</span>
                                    <i class="fa-solid fa-chevron-down" id="iconMasterAssetAdvanced" style="font-size: 0.72rem; transition: transform 0.2s ease;"></i>
                                </a>
                                <button type="button" class="btn btn-sm" onclick="window.resetMasterAssetFilters()" style="height: 40px; width: 40px; padding: 0; display: inline-flex; align-items: center; justify-content: center; background: #ffffff; border: 1.5px solid #cbd5e1; color: #475569; border-radius: 6px;" title="Reset Semua Filter"><i class="fa-solid fa-rotate-left"></i></button>
                            </div>

                            <!-- Dropdown / Collapsible Specific Filters (5x2) -->
                            <div id="masterAssetAdvancedFilters" style="display: none; margin-top: 10px; padding-top: 10px; border-top: 1px dashed #cbd5e1;">
                                <div class="master-asset-filterbar" style="margin: 0; padding: 0; border: none; background: transparent;" aria-label="Filter Spesifik Master Asset">
                                    <input type="text" id="filterAssetId" class="form-control" placeholder="Asset ID" oninput="window.filterMasterAsset('id', this.value)">
                                    <input type="text" id="filterAssetHull" class="form-control" placeholder="Nomor lambung" oninput="window.filterMasterAsset('hull', this.value)">
                                    <input type="text" id="filterAssetModel" class="form-control" placeholder="Nama aset / tahun" oninput="window.filterMasterAsset('model', this.value)">
                                    <input type="text" id="filterAssetSn" class="form-control" placeholder="SN / pelat" oninput="window.filterMasterAsset('sn', this.value)">
                                    <input type="text" id="filterAssetWarranty" class="form-control" placeholder="Garansi" oninput="window.filterMasterAsset('warranty', this.value)">
                                    <input type="text" id="filterAssetChassis" class="form-control" placeholder="Nomor rangka" oninput="window.filterMasterAsset('chassis', this.value)">
                                    <select id="filterAssetCat" class="form-control" onchange="window.filterMasterAsset('category', this.value)">
                                        <option value="">Semua kategori</option><option value="Excavator">Excavator</option><option value="Bulldozer">Bulldozer</option><option value="Dump Truck">Dump Truck</option><option value="Compactor">Compactor</option><option value="Motor Grader">Motor Grader</option><option value="Wheel Loader">Wheel Loader</option><option value="Light Vehicle">Light Vehicle</option><option value="Water Truck">Water Truck</option><option value="Prime Mover">Prime Mover</option><option value="Trado">Trado</option><option value="Other">Other</option>
                                    </select>
                                    <select id="filterAssetLoc" class="form-control" onchange="window.filterMasterAsset('location', this.value)">
                                        <option value="">Semua lokasi</option><option value="Borrow Pit">Borrow Pit</option><option value="Site Celcin">Site Celcin</option><option value="Yard">Yard</option><option value="Workshop">Workshop</option><option value="Site Alpha">Site Alpha</option><option value="Minas">Minas Field</option>
                                    </select>
                                    <select id="filterAssetComp" class="form-control" onchange="window.filterMasterAsset('completeness', this.value)"><option value="">Semua kelengkapan</option><option value="complete">Lengkap</option><option value="incomplete">Belum lengkap</option></select>
                                    <select id="filterAssetStat" class="form-control" onchange="window.filterMasterAsset('status', this.value)"><option value="">Semua status</option><option value="READY">READY</option><option value="OPERATING">OPERATING</option><option value="STANDBY">STANDBY</option><option value="BREAKDOWN">BREAKDOWN</option><option value="INSPEKSI">INSPEKSI</option><option value="ACCIDENT_HOLD">ACCIDENT HOLD</option></select>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive master-asset-table-scroll dashboard-wo-scroll">
                            <table class="table" id="assetTable">
                                <colgroup>
                                    <col class="asset-col-number">
                                    <col class="asset-col-unit">
                                    <col class="asset-col-detail">
                                    <col class="asset-col-identity">
                                    <col class="asset-col-warranty">
                                    <col class="asset-col-placement">
                                    <col class="asset-col-condition">
                                    <col class="asset-col-documents">
                                    <col class="asset-col-actions">
                                </colgroup>
                                <thead id="assetTableHeader">
                                    <tr>
                                        <th>No.</th>
                                        <th onclick="window.sortMasterAsset('id')" style="cursor:pointer">Identitas Unit <i class="fa-solid fa-sort"></i></th>
                                        <th onclick="window.sortMasterAsset('model')" style="cursor:pointer">Detail Aset <i class="fa-solid fa-sort"></i></th>
                                        <th onclick="window.sortMasterAsset('sn')" style="cursor:pointer">Identitas Kendaraan <i class="fa-solid fa-sort"></i></th>
                                        <th onclick="window.sortMasterAsset('warranty')" style="cursor:pointer">Garansi <i class="fa-solid fa-sort"></i></th>
                                        <th onclick="window.sortMasterAsset('location')" style="cursor:pointer">Penempatan <i class="fa-solid fa-sort"></i></th>
                                        <th onclick="window.sortMasterAsset('status')" style="cursor:pointer">Kondisi Data <i class="fa-solid fa-sort"></i></th>
                                        <th>Dokumen</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="assetTableBody">
                                    <!-- Populated via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW: WORK ORDER (MVP 2) -->
            <div id="view-wo" class="view-section">
                <div class="wo-workspace-header">
                    <div>
                        <span class="wo-workspace-eyebrow">Corrective maintenance &middot; data WO aktif</span>
                        <h1 class="page-title">Manajemen Work Order</h1>
                        <p>WO dipertahankan sebagai tiket kerusakan. Nomor JO lama tidak diubah menjadi SPK secara
                            otomatis.</p>
                    </div>
                    <div class="wo-workspace-actions">
                        <div class="wo-view-switch" role="group" aria-label="Mode tampilan Work Order">
                            <button type="button" class="active" data-wo-mode="kanban"
                                onclick="switchWorkOrderMode('kanban')"><i class="fa-solid fa-table-columns"></i>
                                Kanban</button>
                            <button type="button" data-wo-mode="table" onclick="switchWorkOrderMode('table')"><i
                                    class="fa-solid fa-table-list"></i> Tabel</button>
                        </div>
                        <button class="btn btn-danger" onclick="openNewWOModal()"><i class="fa-solid fa-plus"></i> Buat
                            Tiket WO</button>
                    </div>
                </div>

                <div class="wo-workspace-toolbar">
                    <div class="wo-workspace-search"><i class="fa-solid fa-magnifying-glass"></i><input type="search"
                            id="woWorkspaceSearch" oninput="filterWorkOrderWorkspace()"
                            placeholder="Cari nomor WO, unit, mekanik, atau keluhan..."></div>
                    <select id="woWorkspaceStatus" onchange="filterWorkOrderWorkspace()"
                        aria-label="Filter status Work Order">
                        <option value="">Semua status</option>
                        <option>Open</option>
                        <option>In Progress</option>
                        <option>Closed</option>
                    </select>
                    <select id="woWorkspacePriority" onchange="filterWorkOrderWorkspace()"
                        aria-label="Filter prioritas Work Order">
                        <option value="">Semua prioritas</option>
                        <option>High</option>
                        <option>Normal</option>
                    </select>
                    <span id="woWorkspaceCount" class="wo-workspace-count">0 WO</span>
                </div>

                <div id="woKanbanPanel">
                    <div class="kanban-board" id="woKanbanBoard">
                        <!-- Populated via JS -->
                    </div>
                </div>
                <div id="woTablePanel" class="wo-table-panel" style="display:none;">
                    <div class="table-responsive">
                        <table class="table wo-workspace-table">
                            <thead>
                                <tr>
                                    <th>WO</th>
                                    <th>Unit</th>
                                    <th>Project/Lokasi</th>
                                    <th>Keluhan</th>
                                    <th>Prioritas</th>
                                    <th>Status</th>
                                    <th>Mekanik</th>
                                    <th>Target Downtime</th>
                                    <th>Downtime Aktual</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="woWorkspaceTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- VIEW: PREVENTIVE MAINTENANCE -->
            <div id="view-pm" class="view-section">
                <div style="display:flex;justify-content:flex-end;margin:0 0 12px;"><button type="button" class="btn btn-primary" onclick="openModal('modalNewAsset')"><i class="fa-solid fa-plus"></i> Tambah Unit Baru</button></div>
                <div id="preventiveMaintenanceModule"></div>
            </div>



            <!-- VIEW: LAPORAN & FORM -->
            <div id="view-reports" class="view-section">
                <div id="reportModule"></div>
            </div>

            <!-- VIEW: PEOPLE & KPI -->
            <div id="view-people" class="view-section">
                <div id="peopleKpiModule"></div>
            </div>

            <!-- VIEW: HSE / ACCIDENT -->
            <div id="view-hse" class="view-section">
                <div id="hseAccidentModule"></div>
            </div>

            <!-- VIEW: PRODUKTIVITAS -->
            <div id="view-productivity" class="view-section">
                <div id="productivityModule"></div>
            </div>

            <!-- VIEW: INSPEKSI & P2H -->
            <div id="view-inspection" class="view-section">
                <div id="inspectionModule"></div>
            </div>

            <!-- VIEW: UNDER CONSTRUCTION -->
            <div id="view-uc" class="view-section">
                <h1 class="page-title" id="ucTitle">Modul</h1>
                <div class="panel">
                    <div class="panel-body uc-container">
                        <i class="fa-solid fa-person-digging"></i>
                        <h2>Modul Dalam Pengembangan</h2>
                        <p>Modul ini masuk dalam antrean MVP berikutnya berdasarkan dokumen Blueprint.<br>Fokus saat ini
                            berada di Master Asset dan Work Order (Core Maintenance).</p>
                    </div>
                </div>
            </div>

            <!-- VIEW: LOGISTICS -->
            <div id="view-logistics" class="view-section">
                <div id="spareLogisticsApp" aria-live="polite">
                    <div style="text-align:center; padding:45px; color:var(--text-muted);">
                        <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
                        <p style="margin-top:10px;">Menyiapkan integrasi Master Asset dan kebutuhan spare part...</p>
                    </div>
                </div>

                <div class="panel" style="margin-bottom: 20px;">
                    <div class="panel-header">
                        <span><i class="fa-solid fa-file-pen"></i> Input Administrasi & Logistik</span>
                    </div>
                    <div class="panel-body" style="padding: 16px;">
                        <div
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">

                            <!-- 1. SPB & No Polisi -->
                            <div class="panel" style="padding: 16px; margin: 0; background: var(--bg-subtle);">
                                <h4
                                    style="margin-top: 0; margin-bottom: 12px; font-size: 0.92rem; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-truck" style="color: var(--primary);"></i> Input SPB & Unit
                                </h4>
                                <div class="form-group" style="margin-bottom: 10px;">
                                    <label
                                        style="font-size: 0.78rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 4px; display: block;">Kode
                                        Nomor SPB</label>
                                    <input type="text" class="form-control" placeholder="Contoh: SPB-2026-001">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label
                                        style="font-size: 0.78rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 4px; display: block;">No
                                        Polisi / Unit</label>
                                    <input type="text" class="form-control" placeholder="Contoh: B 1234 XYZ / DT-001">
                                </div>
                                <button class="btn btn-primary" style="margin-top: 12px; width: 100%;"><i
                                        class="fa-solid fa-save"></i> Simpan Data SPB</button>
                            </div>

                            <!-- 2. Sheet Oli -->
                            <div class="panel" style="padding: 16px; margin: 0; background: var(--bg-subtle);">
                                <h4
                                    style="margin-top: 0; margin-bottom: 12px; font-size: 0.92rem; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-oil-can" style="color: var(--warning);"></i> Sheet Oli
                                    (Pemakaian/Pemasukan)
                                </h4>
                                <div class="form-group" style="margin-bottom: 10px;">
                                    <label
                                        style="font-size: 0.78rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 4px; display: block;">Jenis
                                        Transaksi</label>
                                    <select class="form-control">
                                        <option>Pemakaian Oli</option>
                                        <option>Pemasukan Oli</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label
                                        style="font-size: 0.78rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 4px; display: block;">Upload
                                        Sheet (Excel/CSV)</label>
                                    <input type="file" class="form-control" accept=".xlsx,.xls,.csv"
                                        style="background: white;">
                                </div>
                                <button class="btn btn-primary" style="margin-top: 12px; width: 100%;"><i
                                        class="fa-solid fa-upload"></i> Upload Sheet Oli</button>
                            </div>

                            <!-- 3. Upload Foto & PDF -->
                            <div class="panel" style="padding: 16px; margin: 0; background: var(--bg-subtle);">
                                <h4
                                    style="margin-top: 0; margin-bottom: 12px; font-size: 0.92rem; color: var(--text-main); display: flex; align-items: center; gap: 8px;">
                                    <i class="fa-solid fa-file-pdf" style="color: var(--danger);"></i> Upload
                                    Administrasi Logistik
                                </h4>
                                <div class="form-group" style="margin-bottom: 10px;">
                                    <label
                                        style="font-size: 0.78rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 4px; display: block;">Foto
                                        Barang Keluar & Masuk</label>
                                    <input type="file" class="form-control" accept="image/*" multiple
                                        style="background: white;" title="Pilih foto barang masuk/keluar">
                                </div>
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label
                                        style="font-size: 0.78rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; margin-bottom: 4px; display: block;">Laporan
                                        PDF Administrasi</label>
                                    <input type="file" class="form-control" accept=".pdf" style="background: white;">
                                </div>
                                <button class="btn btn-primary" style="margin-top: 12px; width: 100%;"><i
                                        class="fa-solid fa-cloud-arrow-up"></i> Upload Berkas</button>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="warehouse-ledger-heading">
                    <div>
                        <h2 class="page-title" style="margin-bottom:4px; font-size:1.25rem;"><i class="fa-solid fa-book"
                                style="color:var(--primary);"></i> Buku Harian Warehouse</h2>
                        <p style="color:var(--text-muted); font-size:0.88rem; margin:0;">
                            Data lokal tersinkronisasi dari Bank Data Equipment dan Pemakaian Filter DT Januari 2026
                        </p>
                    </div>
                </div>

                <div id="logisticsSyncSummary" class="panel warehouse-sync-summary" style="margin-bottom:12px;">
                    <div class="panel-body" style="padding:12px 16px; color:var(--text-muted); font-size:0.84rem;">
                        <i class="fa-solid fa-database"></i> Menyiapkan ringkasan sumber data logistik...
                    </div>
                </div>

                <div class="panel warehouse-ledger-panel">
                    <div class="panel-body">
                        <nav class="tabs-container logistics-ledger-tabs" role="tablist"
                            aria-label="Jenis data buku harian warehouse">
                            <button type="button" class="tab-link active" role="tab" aria-selected="true"
                                onclick="switchLogisticsTab('masuk')"><i class="fa-solid fa-arrow-down"></i><span>Barang
                                    Masuk</span><small class="logistics-tab-count"
                                    data-logistics-count="masuk">0</small></button>
                            <button type="button" class="tab-link" role="tab" aria-selected="false"
                                onclick="switchLogisticsTab('keluar')"><i class="fa-solid fa-arrow-up"></i><span>Barang
                                    Keluar</span><small class="logistics-tab-count"
                                    data-logistics-count="keluar">0</small></button>
                            <button type="button" class="tab-link" role="tab" aria-selected="false"
                                onclick="switchLogisticsTab('stock')"><i
                                    class="fa-solid fa-boxes-stacked"></i><span>Saldo Stok</span><small
                                    class="logistics-tab-count" data-logistics-count="stock">0</small></button>
                            <button type="button" class="tab-link" role="tab" aria-selected="false"
                                onclick="switchLogisticsTab('oil')"><i class="fa-solid fa-oil-can"></i><span>Oli &
                                    Lubricant</span><small class="logistics-tab-count"
                                    data-logistics-count="oil">0</small></button>
                            <button type="button" class="tab-link" role="tab" aria-selected="false"
                                onclick="switchLogisticsTab('used')"><i class="fa-solid fa-recycle"></i><span>Barang
                                    Bekas</span><small class="logistics-tab-count"
                                    data-logistics-count="usedGoods">0</small></button>
                        </nav>

                        <!-- TAB BARANG MASUK -->
                        <div id="logistics-tab-masuk" class="logistics-tab-content active" style="display: block;">
                            <div class="table-toolbar logistics-data-toolbar"
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <div class="search-box logistics-search-box"
                                    style="flex: 1; max-width: 400px; position: relative;">
                                    <i class="fa-solid fa-search"
                                        style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                                    <input type="text" id="search-logistics-masuk"
                                        placeholder="Cari Nama Parts, No SPB, atau Merk..."
                                        style="width: 100%; padding: 10px 15px 10px 40px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit;">
                                </div>
                                <div>
                                    <button class="btn btn-primary" onclick="loadLogisticsData()"
                                        style="padding: 10px 15px;">
                                        <i class="fa-solid fa-arrows-rotate"></i> Refresh Data
                                    </button>
                                </div>
                            </div>

                            <div id="logistics-masuk-loader" class="logistics-data-loader"
                                style="text-align: center; padding: 40px; color: var(--text-light);">
                                <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
                                <p style="margin-top: 10px;">Memuat Bank Data Equipment...</p>
                            </div>

                            <div class="table-responsive logistics-data-table-wrap">
                                <table class="table logistics-data-table" id="table-logistics-masuk"
                                    style="display: none;">
                                    <thead>
                                        <tr>
                                            <th>TANGGAL</th>
                                            <th>NO. BUKTI</th>
                                            <th>TERIMA DARI</th>
                                            <th>NAMA PARTS</th>
                                            <th>PART NUMBER</th>
                                            <th>MERK</th>
                                            <th>SATUAN</th>
                                            <th>JUMLAH</th>
                                            <th>KETERANGAN (UNIT)</th>
                                            <th>SN / PLAT KENDARAAN</th>
                                            <th>NO. SPB</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be injected here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB BARANG KELUAR -->
                        <div id="logistics-tab-keluar" class="logistics-tab-content" style="display: none;">
                            <div class="table-toolbar logistics-data-toolbar"
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <div class="search-box logistics-search-box"
                                    style="flex: 1; max-width: 400px; position: relative;">
                                    <i class="fa-solid fa-search"
                                        style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--text-light);"></i>
                                    <input type="text" id="search-logistics-keluar"
                                        placeholder="Cari Nama Parts, ID Unit, atau No SPB..."
                                        style="width: 100%; padding: 10px 15px 10px 40px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit;">
                                </div>
                                <div>
                                    <button class="btn btn-primary" onclick="loadLogisticsData()"
                                        style="padding: 10px 15px;">
                                        <i class="fa-solid fa-arrows-rotate"></i> Refresh Data
                                    </button>
                                </div>
                            </div>

                            <div id="logistics-keluar-loader" class="logistics-data-loader"
                                style="text-align: center; padding: 40px; color: var(--text-light);">
                                <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
                                <p style="margin-top: 10px;">Mengunduh data Barang Keluar...</p>
                            </div>

                            <div class="table-responsive logistics-data-table-wrap">
                                <table class="table logistics-data-table" id="table-logistics-keluar"
                                    style="display: none;">
                                    <thead>
                                        <tr>
                                            <th>NO</th>
                                            <th>NO SPB</th>
                                            <th>TGL SPB</th>
                                            <th>NO. JO LEGACY / MEKANIK</th>
                                            <th>ID UNIT</th>
                                            <th>SN / PLAT KENDARAAN</th>
                                            <th>NAMA SPARE PART</th>
                                            <th>SPESIFIKASI / PART NO</th>
                                            <th>QTY</th>
                                            <th>SATUAN</th>
                                            <th>STATUS</th>
                                            <th>STATUS BARANG BEKAS</th>
                                            <th>SUMBER</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be injected here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB SALDO STOK -->
                        <div id="logistics-tab-stock" class="logistics-tab-content" style="display: none;">
                            <div class="table-toolbar logistics-data-toolbar"
                                style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; gap:12px;">
                                <div class="search-box logistics-search-box"
                                    style="flex:1; max-width:400px; position:relative;">
                                    <i class="fa-solid fa-search"
                                        style="position:absolute; left:15px; top:50%; transform:translateY(-50%); color:var(--text-light);"></i>
                                    <input type="text" id="search-logistics-stock"
                                        placeholder="Cari nama parts atau part number..."
                                        style="width:100%; padding:10px 15px 10px 40px; border:1px solid var(--border-color); border-radius:8px; font-family:inherit;">
                                </div>
                                <span style="font-size:0.78rem; color:var(--text-muted);">Periode sumber: 26 April-25
                                    Mei 2026</span>
                            </div>
                            <div id="logistics-stock-loader" class="logistics-data-loader"
                                style="text-align:center; padding:40px; color:var(--text-light);">
                                <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
                                <p style="margin-top:10px;">Memuat saldo stok...</p>
                            </div>
                            <div class="table-responsive logistics-data-table-wrap">
                                <table class="table logistics-data-table" id="table-logistics-stock"
                                    style="display:none;">
                                    <thead>
                                        <tr>
                                            <th>NAMA PARTS</th>
                                            <th>PART NUMBER</th>
                                            <th>SATUAN</th>
                                            <th>PENERIMAAN TOTAL</th>
                                            <th>PEMAKAIAN TOTAL</th>
                                            <th>SALDO</th>
                                            <th>SUMBER</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB OLI & LUBRICANT -->
                        <div id="logistics-tab-oil" class="logistics-tab-content" style="display:none;">
                            <div class="table-toolbar logistics-data-toolbar">
                                <div class="search-box logistics-search-box">
                                    <i class="fa-solid fa-search"></i>
                                    <input type="search" id="search-logistics-oil"
                                        placeholder="Cari produk oli, coolant, unit, atau tanggal...">
                                </div>
                                <span class="logistics-source-note"><i class="fa-solid fa-database"></i> Ledger aktual
                                    &middot;
                                    Yard KM 12 Kulim</span>
                            </div>
                            <div id="logistics-oil-loader" class="logistics-data-loader"><i
                                    class="fa-solid fa-spinner fa-spin fa-2x"></i>
                                <p>Memuat pemakaian oli dan coolant...</p>
                            </div>
                            <div class="table-responsive logistics-data-table-wrap">
                                <table class="table logistics-data-table" id="table-logistics-oil"
                                    style="display:none;">
                                    <thead>
                                        <tr>
                                            <th>NO</th>
                                            <th>TANGGAL</th>
                                            <th>NO. BUKTI / BON</th>
                                            <th>NAMA PRODUK</th>
                                            <th>KODE PRODUK</th>
                                            <th>SATUAN</th>
                                            <th>JUMLAH</th>
                                            <th>UNIT / KETERANGAN</th>
                                            <th>SN / PLAT</th>
                                            <th>NILAI SUMBER</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB BARANG BEKAS -->
                        <div id="logistics-tab-used" class="logistics-tab-content" style="display:none;">
                            <div class="table-toolbar logistics-data-toolbar">
                                <div class="search-box logistics-search-box">
                                    <i class="fa-solid fa-search"></i>
                                    <input type="search" id="search-logistics-used"
                                        placeholder="Cari unit, parts, nomor bukti, atau status...">
                                </div>
                                <select id="filter-logistics-used" class="logistics-status-filter"
                                    aria-label="Filter status barang bekas">
                                    <option value="">Semua status</option>
                                    <option value="ADA">Ada</option>
                                    <option value="BELUM ADA">Belum ada</option>
                                    <option value="TIDAK ADA">Tidak ada</option>
                                    <option value="BELUM">Belum</option>
                                </select>
                            </div>
                            <div class="logistics-data-notice"><i class="fa-solid fa-circle-info"></i><span>Tab ini
                                    merupakan turunan transaksi Barang Keluar yang memiliki isian status barang bekas;
                                    bukan transaksi stok barang bekas terpisah.</span></div>
                            <div id="logistics-used-loader" class="logistics-data-loader"><i
                                    class="fa-solid fa-spinner fa-spin fa-2x"></i>
                                <p>Menyiapkan status barang bekas...</p>
                            </div>
                            <div class="table-responsive logistics-data-table-wrap">
                                <table class="table logistics-data-table" id="table-logistics-used"
                                    style="display:none;">
                                    <thead>
                                        <tr>
                                            <th>NO</th>
                                            <th>TANGGAL</th>
                                            <th>NO. BUKTI</th>
                                            <th>NO. JO LEGACY</th>
                                            <th>ID UNIT</th>
                                            <th>SN / PLAT</th>
                                            <th>NAMA PARTS</th>
                                            <th>PART NUMBER</th>
                                            <th>QTY</th>
                                            <th>STATUS BARANG BEKAS</th>
                                            <th>SUMBER</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW: CONDITION MONITORING -->
            <div id="view-condition" class="view-section">
                <div class="cm-page-heading">
                    <div class="cm-page-heading-copy">
                        <h1 class="page-title"><i class="fa-solid fa-stethoscope"></i> Condition Monitoring</h1>
                        <p>Inspeksi komponen kritis per-unit dan tindak lanjut terintegrasi.</p>
                    </div>
                    <div class="cm-direct-inspection" role="search" aria-label="Cek inspeksi Dump Truck">
                        <label for="cm-direct-input"><i class="fa-solid fa-truck-moving" aria-hidden="true"></i><span>Cek inspeksi DT</span></label>
                        <input type="text" id="cm-direct-input" class="form-control" placeholder="Contoh: DT-00049"
                            onkeydown="if (event.key === 'Enter') ConditionMonitoring.openDirectUnit()">
                        <button type="button" class="btn btn-primary" onclick="ConditionMonitoring.openDirectUnit()"><i
                                class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Lihat hasil</span></button>
                    </div>
                </div>
                <div id="conditionMonitoringApp" class="cm-app" aria-live="polite">
                    <div class="cm-loading"><i class="fa-solid fa-spinner fa-spin"></i> Menyiapkan data inspeksi unit...
                    </div>
                </div>
            </div>

            <!-- VIEW: FUEL MANAGEMENT -->
            <div id="view-fuel" class="view-section">
                <div
                    style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:15px;">
                    <div>
                        <h1 class="page-title" style="margin-bottom:4px;"><i class="fa-solid fa-gas-pump"
                                style="color:var(--primary);"></i> Monitoring & Efisiensi BBM Unit</h1>
                        <p style="color:var(--text-muted); font-size:0.88rem; margin:0;">
                            Pencatatan Penyaluran (FRM-BBM-01), Deteksi Anomali Konsumsi, Rekonsiliasi Tangki
                            (FRM-STK-02) & Integration Audit Trail
                        </p>
                    </div>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <button class="btn btn-primary" onclick="openFuelInputModal()"><i class="fa-solid fa-plus"></i>
                            Input Pengisian (FRM-BBM-01)</button>
                        <button class="btn btn-success" onclick="openStockSoundingModal()"><i
                                class="fa-solid fa-droplet"></i> Sounding Stok (FRM-STK-02)</button>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="panel" style="margin-bottom: 15px;">
                    <div class="panel-body"
                        style="padding: 10px 15px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <strong style="font-size:0.88rem;"><i class="fa-solid fa-filter"></i> Filter BBM:</strong>
                        <select id="fuelFilterPeriode" class="form-control"
                            style="width: auto; margin-bottom: 0; padding: 5px;" onchange="filterFuelDashboard()">
                            <option value="ALL">Periode: Semua</option>
                            <option value="TODAY" selected>Periode: Hari Ini</option>
                            <option value="MONTH">Periode: Bulan Ini</option>
                        </select>
                        <select id="fuelFilterLokasi" class="form-control"
                            style="width: auto; margin-bottom: 0; padding: 5px;" onchange="filterFuelDashboard()">
                            <option value="ALL">Lokasi: Semua Site</option>
                            <option value="Yard KM 12 Duri">Yard KM 12 Duri</option>
                            <option value="Pit">Pit (Harapan Baru)</option>
                            <option value="Borrow Pit">Borrow Pit</option>
                            <option value="Yard Prabumulih">Yard Prabumulih</option>
                        </select>
                        <select id="fuelFilterKategori" class="form-control"
                            style="width: auto; margin-bottom: 0; padding: 5px;" onchange="filterFuelDashboard()">
                            <option value="ALL">Kategori: Semua Alat</option>
                            <option value="Excavator">Excavator</option>
                            <option value="Dump Truck">Dump Truck</option>
                            <option value="Bulldozer">Bulldozer</option>
                            <option value="Motor Grader">Motor Grader</option>
                        </select>
                        <select id="fuelFilterStatus" class="form-control"
                            style="width: auto; margin-bottom: 0; padding: 5px;" onchange="filterFuelDashboard()">
                            <option value="ALL">Status Anomali: Semua</option>
                            <option value="RED">Merah (Deviasi >20% Boros)</option>
                            <option value="ORANGE">Oranye (Deviasi 10-20%)</option>
                            <option value="YELLOW">Kuning (Deviasi 5-10%)</option>
                            <option value="GREEN">Hijau (Normal &le; 5%)</option>
                        </select>
                        <button class="btn btn-primary" style="padding: 5px 15px;" onclick="filterFuelDashboard()"><i
                                class="fa-solid fa-magnifying-glass"></i> Terapkan</button>
                        <button class="btn btn-secondary" style="padding: 5px 15px; background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; border-radius: 8px; font-weight: 700;" onclick="resetFuelFilter()"><i
                                class="fa-solid fa-rotate-left"></i> Reset</button>
                    </div>
                </div>

                <!-- Analytics Reconciliation & Source Data Transparency Banner (Batch F6) -->
                <div id="fuelReconciliationBanner" style="background:#f8fafc; border:1px solid #e2e8f0; border-left:4px solid #0284c7; border-radius:8px; padding:10px 14px; margin-bottom:14px; font-size:0.83rem; color:#334155; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <div>
                        <i class="fa-solid fa-circle-check" style="color:#0284c7;"></i>
                        <strong>Rekonsiliasi Analitik BBM (FRM-BBM-01 & DB):</strong>
                        <span id="lblFuelSourceRows">Memuat baris log BBM...</span>
                        <span style="color:#64748b; margin-left:8px;">| Rumus Biaya: <code>Total Liter × Standar Rp 12.000/L</code> | Deviasi Anomali: <code>((LPH - Baseline) / Baseline) &gt; 20%</code></span>
                    </div>
                    <div id="lblFuelDataQuality" style="font-weight:600; color:#0f766e;">
                        <i class="fa-solid fa-database"></i> Sumber Terverifikasi
                    </div>
                </div>

                <!-- Synchronized Telemetry Status Strip (6 Columns) -->
                <div class="telemetry-status-grid" style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));">
                    <div class="telemetry-metric-item">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Liter Hari Ini</span>
                        </div>
                        <div class="telemetry-metric-value" id="kpiFuelToday">2,450 L</div>
                        <div class="telemetry-metric-footer">
                            <span style="font-size:0.75rem; color:var(--text-muted);"><i
                                    class="fa-solid fa-gas-pump"></i> Consumed</span>
                        </div>
                    </div>
                    <div class="telemetry-metric-item">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Liter Bulan Ini</span>
                        </div>
                        <div class="telemetry-metric-value" id="kpiFuelMonth">45,200 L</div>
                        <div class="telemetry-metric-footer">
                            <span style="font-size:0.75rem; color:var(--text-muted);"><i
                                    class="fa-solid fa-droplet"></i> MTD Total</span>
                        </div>
                    </div>
                    <div class="telemetry-metric-item">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Biaya BBM Bulan Ini</span>
                        </div>
                        <div class="telemetry-metric-value font-mono" id="kpiFuelCost"
                            style="font-size: 1.25rem; color: var(--success);">Rp 542,4 Jt</div>
                        <div class="telemetry-metric-footer">
                            <span style="font-size:0.75rem; color:var(--text-muted);"><i class="fa-solid fa-coins"></i>
                                Valuation</span>
                        </div>
                    </div>
                    <div class="telemetry-metric-item" style="cursor:pointer;" onclick="filterFuelByAnomaly('RED')"
                        title="Klik untuk filter unit boros">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Unit Boros (Anomali)</span>
                        </div>
                        <div class="telemetry-metric-value font-mono" id="kpiFuelBoros" style="color: var(--danger);">4
                            Unit</div>
                        <div class="telemetry-metric-footer">
                            <button class="telemetry-action-link"><i class="fa-solid fa-triangle-exclamation"></i>
                                Filter Anomali</button>
                        </div>
                    </div>
                    <div class="telemetry-metric-item" style="cursor:pointer;" onclick="filterFuelByStatus('Diajukan')"
                        title="Klik untuk filter transaksi belum diverifikasi">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Belum Diverifikasi</span>
                        </div>
                        <div class="telemetry-metric-value font-mono" id="kpiFuelUnverified"
                            style="color: var(--warning);">12 Trx</div>
                        <div class="telemetry-metric-footer">
                            <button class="telemetry-action-link"><i class="fa-solid fa-clock-rotate-left"></i> Review
                                Trx</button>
                        </div>
                    </div>
                    <div class="telemetry-metric-item">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Stok Tangki Site</span>
                        </div>
                        <div class="telemetry-metric-value font-mono" id="kpiFuelStock"
                            style="font-size:1.25rem; color:var(--primary);">32.810 L</div>
                        <div class="telemetry-metric-footer">
                            <span style="font-size:0.75rem; color:var(--text-muted);"><i
                                    class="fa-solid fa-warehouse"></i> Storage Tank</span>
                        </div>
                    </div>
                </div>

                <div class="dashboard-layout" style="margin-top: 15px;">
                    <!-- Grafik Pemakaian Harian -->
                    <div class="panel">
                        <div class="panel-header"><span><i class="fa-solid fa-chart-column"></i> Tren Pemakaian BBM
                                Harian & Biaya</span></div>
                        <div class="panel-body"
                            style="height: 250px; display: flex; align-items: flex-end; gap: 12px; border-bottom: 1px solid var(--border); padding-top: 40px; position: relative;"
                            id="fuelChartContainer">
                            <!-- Rendered dynamically by JS -->
                        </div>
                    </div>

                    <!-- Top Unit Boros vs Standar Baseline -->
                    <div class="panel">
                        <div class="panel-header"><span><i class="fa-solid fa-triangle-exclamation"
                                    style="color:var(--danger);"></i> Top Unit Boros vs Standar Baseline (LPH /
                                KPL)</span></div>
                        <div class="panel-body" style="padding: 15px;" id="fuelTopBorosContainer">
                            <!-- Rendered dynamically by JS -->
                        </div>
                    </div>
                </div>

                <!-- Multi-Tab Data Tables -->
                <div class="panel" style="margin-top: 15px;">
                    <div class="panel-header"
                        style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; padding: 0 15px;">
                        <div class="asset-tabs" id="fuelMainTabs" style="margin-bottom:0; border-bottom:none;">
                            <div class="asset-tab active" onclick="switchFuelTab('trx', this)"><i
                                    class="fa-solid fa-list-check"></i> Transaksi BBM & Anomali (FRM-BBM-01)</div>
                            <div class="asset-tab" onclick="switchFuelTab('stock', this)"><i
                                    class="fa-solid fa-warehouse"></i> Tangki Storage & Sounding (FRM-STK-02)</div>
                            <div class="asset-tab" onclick="switchFuelTab('baseline', this)"><i
                                    class="fa-solid fa-sliders"></i> Master Standar Baseline LPH/KPL</div>
                        </div>
                    </div>
                    <div class="panel-body" style="padding: 15px;">

                        <!-- TAB 1: TRANSAKSI & ANOMALI -->
                        <div id="fuelTabSecTrx">
                            <div
                                style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:10px;">
                                <input type="text" id="searchFuelInput" class="form-control"
                                    placeholder="Cari Unit ID, Voucher, Operator, atau Location..."
                                    style="max-width:320px; margin-bottom:0;" onkeyup="searchFuelTable()">
                                <div style="font-size:0.85rem; color:var(--text-muted);">
                                    Menampilkan <strong id="fuelTrxCount">0</strong> Transaksi BBM
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table style="margin: 0; font-size: 0.85rem;" id="fuelTable">
                                    <thead>
                                        <tr>
                                            <th>No. Voucher / Jam</th>
                                            <th>Kode Unit</th>
                                            <th>Kategori & Lokasi</th>
                                            <th>Fuelman / Operator</th>
                                            <th>Meter Awal &rarr; Akhir</th>
                                            <th>Liter</th>
                                            <th>Aktual vs Standar</th>
                                            <th>Deviasi</th>
                                            <th>Total Biaya (Rp)</th>
                                            <th>Kewajaran</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="fuelTableBody">
                                        <!-- Rendered via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 2: STOK TANGKI & SOUNDING REKONSILIASI -->
                        <div id="fuelTabSecStock" style="display:none;">
                            <h3 style="margin-bottom:12px; font-size:1.05rem;"><i class="fa-solid fa-warehouse"></i>
                                Status Tangki Dispensasi & Rekonsiliasi Sounding Fisik</h3>
                            <div class="table-responsive" style="margin-bottom:20px;">
                                <table style="margin: 0; font-size: 0.88rem;">
                                    <thead>
                                        <tr>
                                            <th>Kode Tangki / Sumber</th>
                                            <th>Lokasi Site</th>
                                            <th>Kapasitas Tangki</th>
                                            <th>Saldo Sistem</th>
                                            <th>Sounding Fisik Terakhir</th>
                                            <th>Selisih Liter</th>
                                            <th>Status Selisih</th>
                                            <th>Waktu Rekonsiliasi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="fuelStockTableBody">
                                        <!-- Rendered via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 3: MASTER STANDAR BASELINE -->
                        <div id="fuelTabSecBaseline" style="display:none;">
                            <h3 style="margin-bottom:12px; font-size:1.05rem;"><i class="fa-solid fa-sliders"></i>
                                Master Standar Konsumsi Baseline menurut Kategori & Model</h3>
                            <div class="table-responsive">
                                <table style="margin: 0; font-size: 0.88rem;">
                                    <thead>
                                        <tr>
                                            <th>Kategori / Model Unit</th>
                                            <th>Satuan Meter</th>
                                            <th>Target Baseline</th>
                                            <th>Batas Minimum</th>
                                            <th>Batas Warning (Oranye)</th>
                                            <th>Batas Anomali (Merah)</th>
                                            <th>Kondisi Operations</th>
                                            <th>Status Master</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Excavator PC200 / PC210</strong></td>
                                            <td><span class="badge bg-operating">Liter / HM</span></td>
                                            <td>14.0 L/HM</td>
                                            <td>10.0 L/HM</td>
                                            <td>15.5 L/HM (+10.7%)</td>
                                            <td>17.0 L/HM (+21.4%)</td>
                                            <td>Loading Pit & Heavy Excavation</td>
                                            <td><span class="badge bg-operating">Aktif</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Dump Truck Mercy Axor / Hino 500</strong></td>
                                            <td><span class="badge bg-standby">Km / Liter</span></td>
                                            <td>3.5 Km/L</td>
                                            <td>4.5 Km/L</td>
                                            <td>3.0 Km/L (-14.2%)</td>
                                            <td>2.5 Km/L (-28.5%)</td>
                                            <td>Hauling Material Distance 12 KM</td>
                                            <td><span class="badge bg-operating">Aktif</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Bulldozer Komatsu D85ESS</strong></td>
                                            <td><span class="badge bg-operating">Liter / HM</span></td>
                                            <td>16.0 L/HM</td>
                                            <td>12.0 L/HM</td>
                                            <td>18.0 L/HM (+12.5%)</td>
                                            <td>20.0 L/HM (+25.0%)</td>
                                            <td>Land Clearing & Ripping Operations</td>
                                            <td><span class="badge bg-operating">Aktif</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Motor Grader Komatsu GD535</strong></td>
                                            <td><span class="badge bg-operating">Liter / HM</span></td>
                                            <td>13.0 L/HM</td>
                                            <td>9.5 L/HM</td>
                                            <td>14.5 L/HM (+11.5%)</td>
                                            <td>16.0 L/HM (+23.0%)</td>
                                            <td>Road Maintenance & Grading</td>
                                            <td><span class="badge bg-operating">Aktif</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- VIEW: BIAYA / COST CONTROL -->
            <div id="view-biaya" class="view-section">
                <div
                    style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:15px;">
                    <div>
                        <h1 class="page-title" style="margin-bottom:4px;"><i class="fa-solid fa-money-bill-trend-up"
                                style="color:var(--success);"></i> Modul Biaya & Cost Control Armada</h1>
                        <p style="color:var(--text-muted); font-size:0.88rem; margin:0;">
                            Pengendalian Budget vs Realisasi, Valuasi Nilai Buku & Pasaran Aset, Rekapitulasi PO
                            Sparepart & LPJ Divisi Equipment
                        </p>
                    </div>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <button class="btn btn-primary" onclick="openCostInputModal()"><i class="fa-solid fa-plus"></i>
                            Catat Transaksi Biaya</button>
                        <button class="btn btn-secondary" onclick="exportCostReport()"><i
                                class="fa-solid fa-file-excel"></i> Export Laporan Keuangan</button>
                    </div>
                </div>

                <!-- Filter Bar Biaya -->
                <div class="panel" style="margin-bottom: 15px;">
                    <div class="panel-body"
                        style="padding: 10px 15px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <strong style="font-size:0.88rem;"><i class="fa-solid fa-filter"></i> Filter Biaya:</strong>
                        <select id="costFilterTahun" class="form-control"
                            style="width: auto; margin-bottom: 0; padding: 5px;" onchange="filterCostDashboard()">
                            <option value="ALL">Tahun: Semua</option>
                            <option value="2026" selected>Tahun 2026</option>
                            <option value="2025">Tahun 2025</option>
                        </select>
                        <select id="costFilterLokasi" class="form-control"
                            style="width: auto; margin-bottom: 0; padding: 5px;" onchange="filterCostDashboard()">
                            <option value="ALL">Lokasi: Semua Site</option>
                            <option value="Duri">Yard KM 12 Duri</option>
                            <option value="Pit">Pit (Harapan Baru)</option>
                            <option value="Prabumulih">Yard Prabumulih</option>
                        </select>
                        <select id="costFilterKategori" class="form-control"
                            style="width: auto; margin-bottom: 0; padding: 5px;" onchange="filterCostDashboard()">
                            <option value="ALL">Kategori: Semua Biaya</option>
                            <option value="Sparepart">Sparepart & Consumable</option>
                            <option value="Maintenance">Preventive Maintenance</option>
                            <option value="External">Jasa Outsource / Sub-contractor</option>
                            <option value="Capex">Overhaul / Capital Expenses</option>
                        </select>
                        <button class="btn btn-primary" style="padding: 5px 15px;" onclick="filterCostDashboard()"><i
                                class="fa-solid fa-magnifying-glass"></i> Terapkan Filter</button>
                    </div>
                </div>

                <!-- Analytics Reconciliation & Source Data Transparency Banner (Batch F6) -->
                <div id="costReconciliationBanner" style="background:#f8fafc; border:1px solid #e2e8f0; border-left:4px solid #0284c7; border-radius:8px; padding:10px 14px; margin-bottom:14px; font-size:0.83rem; color:#334155; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <div>
                        <i class="fa-solid fa-circle-check" style="color:#0284c7;"></i>
                        <strong>Rekonsiliasi Buku Kas Biaya (Asset Cost Ledger):</strong>
                        <span id="lblCostSourceRows">Memuat baris kas...</span>
                        <span style="color:#64748b; margin-left:8px;">| Kebijakan Transaksi: <code>MANUAL Verified Ledger</code> | Status PO/SPPU: <code>Posting Otomatis Dinonaktifkan (Cegah Double Counting)</code></span>
                    </div>
                    <div id="lblCostDataQuality" style="font-weight:600; color:#0f766e;">
                        <i class="fa-solid fa-database"></i> Buku Kas Sinkron
                    </div>
                </div>

                <!-- 6 Synchronized Telemetry Status Strip (6 Columns) -->
                <div class="telemetry-status-grid"
                    style="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); margin-bottom: 15px;">
                    <div class="telemetry-metric-item" title="Total Budget Berjalan: Rp 2.960 Jt">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Total Budget Berjalan</span>
                        </div>
                        <div class="telemetry-metric-value font-mono" id="kpiCostBudget">Rp 2.960 Jt</div>
                        <div class="telemetry-metric-footer">
                            <span style="font-size:0.72rem; color:var(--text-muted);"><i class="fa-solid fa-wallet"></i>
                                Budget YTD</span>
                        </div>
                    </div>
                    <div class="telemetry-metric-item" title="Pengeluaran Aktual: Rp 12.144 Jt">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Pengeluaran Aktual</span>
                        </div>
                        <div class="telemetry-metric-value font-mono" id="kpiCostActual" style="color: var(--danger);">
                            Rp 12.144 Jt</div>
                        <div class="telemetry-metric-footer">
                            <span style="font-size:0.72rem; color:var(--text-muted);"><i
                                    class="fa-solid fa-file-invoice-dollar"></i> Actual Expenses</span>
                        </div>
                    </div>
                    <div class="telemetry-metric-item" title="Deviasi Budget: +310.2% (Over)">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Deviasi Budget</span>
                        </div>
                        <div class="telemetry-metric-value font-mono" id="kpiCostDev" style="color: var(--warning);">
                            +310.2% (Over)</div>
                        <div class="telemetry-metric-footer">
                            <span style="font-size:0.72rem; color:var(--text-muted);"><i
                                    class="fa-solid fa-chart-line"></i> Variance Rate</span>
                        </div>
                    </div>
                    <div class="telemetry-metric-item" title="Nilai Buku Aset (7 Unit): Rp 3.347 Jt">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Nilai Buku Aset (7 Unit)</span>
                        </div>
                        <div class="telemetry-metric-value font-mono" id="kpiCostBookValue"
                            style="color: var(--success);">Rp 3.347 Jt</div>
                        <div class="telemetry-metric-footer">
                            <span style="font-size:0.72rem; color:var(--text-muted);"><i class="fa-solid fa-book"></i>
                                Book Value</span>
                        </div>
                    </div>
                    <div class="telemetry-metric-item" title="Harga Penawaran Buyer: Rp 4.070 Jt">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Harga Penawaran Buyer</span>
                        </div>
                        <div class="telemetry-metric-value font-mono" id="kpiCostMarketValue"
                            style="color: var(--primary);">Rp 4.070 Jt</div>
                        <div class="telemetry-metric-footer">
                            <span style="font-size:0.72rem; color:var(--text-muted);"><i class="fa-solid fa-tags"></i>
                                Buyer Bids</span>
                        </div>
                    </div>
                    <div class="telemetry-metric-item" title="Proyeksi Net Gain BRA: +Rp 610 Jt">
                        <div class="telemetry-metric-header">
                            <span class="telemetry-metric-label">Proyeksi Net Gain BRA</span>
                        </div>
                        <div class="telemetry-metric-value font-mono" id="kpiCostMargin" style="color: var(--success);">
                            +Rp 610 Jt</div>
                        <div class="telemetry-metric-footer">
                            <span style="font-size:0.72rem; color:var(--text-muted);"><i
                                    class="fa-solid fa-hand-holding-dollar"></i> Net Margin</span>
                        </div>
                    </div>
                </div>

                <!-- Main Section Layout -->
                <div class="dashboard-layout" style="margin-top: 15px;">
                    <!-- Visual Chart Panel Budget vs Actual -->
                    <div class="panel" style="flex: 1.2;">
                        <div class="panel-header"
                            style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                            <span><i class="fa-solid fa-chart-column" style="color:var(--primary);"></i> Budget vs
                                Actual Cost (8 Bulan Terakhir)</span>
                            <div style="display:flex; gap:12px; font-size:0.85rem; flex-wrap:wrap;">
                                <span><span
                                        style="display:inline-block; width:10px; height:10px; background:#2563eb; border-radius:2px; margin-right:4px;"></span>
                                    Budget Planning</span>
                                <span><span
                                        style="display:inline-block; width:10px; height:10px; background:#dc2626; border-radius:2px; margin-right:4px;"></span>
                                    Actual (Over Budget)</span>
                                <span><span
                                        style="display:inline-block; width:10px; height:10px; background:#16a34a; border-radius:2px; margin-right:4px;"></span>
                                    Actual (Under Budget / Hemat)</span>
                            </div>
                        </div>
                        <div class="panel-body"
                            style="height: 310px; position: relative; padding: 15px 15px 35px 15px; overflow: visible;"
                            id="costChartContainer">
                            <!-- Rendered via JS -->
                        </div>
                        <div
                            style="text-align:center; font-size:0.85rem; color:var(--text-muted); font-weight:600; padding-bottom:8px;">
                            <i class="fa-solid fa-calendar-days"></i> Bulan Operasional Armada (Mei - Desember 2026)
                        </div>
                    </div>

                    <!-- Side Financial Highlights Panel -->
                    <div class="panel" style="flex: 0.8;">
                        <div class="panel-header"><span><i class="fa-solid fa-scale-balanced"></i> Ringkasan Alokasi LPJ
                                Equipment Duri</span></div>
                        <div class="panel-body" style="padding: 15px;" id="costSummaryPanel">
                            <div style="margin-bottom:12px;">
                                <div
                                    style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:4px;">
                                    <span>Corrective Repair (Spareparts)</span>
                                    <strong style="color:var(--danger);">Rp 326.2 Jt</strong>
                                </div>
                                <div style="width:100%; background:#e9ecef; height:8px; border-radius:4px;">
                                    <div style="width:72%; background:var(--danger); height:100%; border-radius:4px;">
                                    </div>
                                </div>
                            </div>
                            <div style="margin-bottom:12px;">
                                <div
                                    style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:4px;">
                                    <span>Preventive Maintenance (PM)</span>
                                    <strong style="color:var(--primary);">Rp 128.5 Jt</strong>
                                </div>
                                <div style="width:100%; background:#e9ecef; height:8px; border-radius:4px;">
                                    <div style="width:45%; background:var(--primary); height:100%; border-radius:4px;">
                                    </div>
                                </div>
                            </div>
                            <div style="margin-bottom:12px;">
                                <div
                                    style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:4px;">
                                    <span>Pemeliharaan Light Vehicle (LV)</span>
                                    <strong style="color:#fd7e14;">Rp 85.0 Jt</strong>
                                </div>
                                <div style="width:100%; background:#e9ecef; height:8px; border-radius:4px;">
                                    <div style="width:30%; background:#fd7e14; height:100%; border-radius:4px;"></div>
                                </div>
                            </div>
                            <div
                                style="background:#f8f9fa; padding:10px 12px; border-radius:6px; border:1px solid var(--border); font-size:0.82rem; margin-top:15px;">
                                <i class="fa-solid fa-circle-info" style="color:var(--primary);"></i> <strong>Catatan
                                    Audit Keuangan:</strong><br>
                                Pengeluaran corrective terbanyak tercatat di bulan Juli & November akibat overhaul
                                excavator utama & perbaikan transmisi dozer.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Multi-Tab Data Tables (4 Tabs) -->
                <div class="panel" style="margin-top: 15px;">
                    <div class="panel-header"
                        style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; padding: 0 15px;">
                        <div class="asset-tabs" id="costMainTabs" style="margin-bottom:0; border-bottom:none;">
                            <div class="asset-tab active" onclick="switchCostTab('valuation', this)"><i
                                    class="fa-solid fa-truck-ramp-box"></i> Tabulasi Valuasi & Harga Jual Unit (7 Unit)
                            </div>
                            <div class="asset-tab" onclick="switchCostTab('lpj', this)"><i
                                    class="fa-solid fa-file-invoice"></i> LPJ Bulanan Budget vs Realisasi</div>
                            <div class="asset-tab" onclick="switchCostTab('po', this)"><i
                                    class="fa-solid fa-receipt"></i> Register PO & Pengeluaran Part (WO Linked)</div>
                            <div class="asset-tab" onclick="switchCostTab('pmcomp', this)"><i
                                    class="fa-solid fa-list-check"></i> Rencana vs Realisasi PM</div>
                        </div>
                    </div>
                    <div class="panel-body" style="padding: 15px;">

                        <!-- TAB 1: VALUASI & HARGA JUAL UNIT -->
                        <div id="costTabSecValuation">
                            <div
                                style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:10px;">
                                <input type="text" id="searchCostValuationInput" class="form-control"
                                    placeholder="Cari Kode Unit, Name, SN..." style="max-width:320px; margin-bottom:0;"
                                    onkeyup="searchCostValuationTable()">
                                <div style="font-size:0.85rem; color:var(--text-muted);">
                                    Menampilkan <strong>7 Unit Utama</strong> (Total Biaya Repair: Rp 2.249.290.290)
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table style="margin: 0; font-size: 0.85rem;" id="costValuationTable">
                                    <thead>
                                        <tr>
                                            <th>Unit ID & Nama Alat</th>
                                            <th>SN / Tahun</th>
                                            <th>Biaya Repair Kumulatif</th>
                                            <th>Kondisi Health / Body</th>
                                            <th>Harga Beli Awal</th>
                                            <th>Pendapatan Unit</th>
                                            <th>Nilai Buku (Book Value)</th>
                                            <th>Harga Pasaran</th>
                                            <th>Harga Ditawarkan Buyer</th>
                                            <th>Net ke BRA</th>
                                            <th>Margin Selisih</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="costValuationTableBody">
                                        <!-- Rendered via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 2: LPJ BULANAN BUDGET VS REALISASI -->
                        <div id="costTabSecLpj" style="display:none;">
                            <h3 style="margin-bottom:12px; font-size:1.05rem;"><i class="fa-solid fa-file-invoice"></i>
                                Laporan Pertanggungjawaban (LPJ) Keuangan Divisi Aset & Equipment</h3>
                            <div class="table-responsive">
                                <table style="margin: 0; font-size: 0.88rem;">
                                    <thead>
                                        <tr>
                                            <th>Bulan / Periode</th>
                                            <th>Budget Planning</th>
                                            <th>Realisasi Cash (Pembayaran)</th>
                                            <th>Kewajiban Utang (AP)</th>
                                            <th>Total Realisasi</th>
                                            <th>Sisa / Defisit Budget</th>
                                            <th>Status Varian</th>
                                        </tr>
                                    </thead>
                                    <tbody id="costLpjTableBody">
                                        <!-- Rendered via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 3: REGISTER BIAYA, PO & TRANSAKSI WO (BR-09A / BR-09B) -->
                        <div id="costTabSecPo" style="display:none;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:10px;">
                                <h3 style="margin:0; font-size:1.05rem;"><i class="fa-solid fa-receipt"></i>
                                    Buku Besar Biaya Perbaikan (Cost Ledger) &amp; Transaksi WO</h3>
                                <button type="button" class="btn btn-primary" onclick="openCostInputModal()"><i class="fa-solid fa-plus"></i> Catat Transaksi Biaya</button>
                            </div>

                            <!-- Decision Gate Note (BR-09B) -->
                            <div class="pk-alert pk-alert-info" style="margin-bottom:15px; border-left: 4px solid var(--primary);">
                                <i class="fa-solid fa-circle-question"></i>
                                <div>
                                    <strong>Decision Gate Integrasi Otomatis PO &amp; Formulir SPPU (BR-09B - STATUS: NEEDS DECISION):</strong><br>
                                    Saat ini transaksi biaya dicatat dan ditelusuri per Unit &amp; WO melalui Cost Ledger ini dengan pencegahan double counting. Usulan integrasi otomatis memerlukan keputusan manajemen:
                                    <div style="margin-top:6px; font-size:0.83rem;">
                                        &bull; <strong>Opsi A:</strong> Biaya WO otomatis terbentuk dari Purchase Order (PO) yang disetujui.<br>
                                        &bull; <strong>Opsi B:</strong> Biaya WO otomatis terbentuk dari formulir SPPU yang difinalkan.<br>
                                        &bull; <strong>Opsi C:</strong> Keduanya dengan deduplikasi (reconciliation matching).<br>
                                        &bull; <strong>Opsi D:</strong> Tetap dicatat manual melalui Cost Ledger ini (default).
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table style="margin: 0; font-size: 0.88rem;">
                                    <thead>
                                        <tr>
                                            <th>Kode Transaksi / Tgl</th>
                                            <th>Pemasok / Vendor</th>
                                            <th>Uraian Pengeluaran &amp; Referensi</th>
                                            <th>Unit &amp; WO Linked</th>
                                            <th>Kategori</th>
                                            <th>Total Biaya (Rp)</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="costPoTableBody">
                                        <!-- Rendered via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 4: RENCANA VS REALISASI PM -->
                        <div id="costTabSecPmComp" style="display:none;">
                            <div
                                style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:10px;">
                                <h3 style="margin:0; font-size:1.05rem;"><i class="fa-solid fa-list-check"></i>
                                    Perbandingan Rencana vs Realisasi PM Jan-Feb 2026</h3>
                                <button class="btn btn-primary" onclick="openNewPMModal()"><i
                                        class="fa-solid fa-plus"></i> Rencana Service Berkala (PM)</button>
                            </div>
                            <div class="table-responsive">
                                <table style="margin: 0; font-size: 0.88rem;">
                                    <thead>
                                        <tr>
                                            <th>Unit ID & Model</th>
                                            <th>Jadwal Service PM</th>
                                            <th>Lokasi Site</th>
                                            <th>Estimasi Budget Rencana</th>
                                            <th>Realisasi Biaya PM</th>
                                            <th>Selisih Biaya</th>
                                            <th>Keterangan Pelaksanaan</th>
                                        </tr>
                                    </thead>
                                    <tbody id="costPmCompTableBody">
                                        <!-- Rendered via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- VIEW: APPROVAL -->
            <div id="view-approval" class="view-section">
                <h1 class="page-title">Approval Inbox</h1>
                <div class="panel">
                    <div class="panel-header">
                        <span><i class="fa-solid fa-inbox"></i> Menunggu Persetujuan Anda</span>
                    </div>
                    <div class="panel-body" style="padding: 0;">
                        <div class="table-responsive">
                            <table style="margin: 0;">
                                <thead>
                                    <tr>
                                        <th>ID Dokumen</th>
                                        <th>Jenis</th>
                                        <th>Pemohon</th>
                                        <th>Detail / Nominal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="approval-list"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW: PENGATURAN & KONFIGURASI SISTEM (DUAL PANE MENU DI DALAM MENU) -->
            <div id="view-settings" class="view-section">
                <div
                    style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; flex-wrap:wrap; gap:10px;">
                    <div>
                        <h1 class="page-title" style="margin-bottom:4px;"><i class="fa-solid fa-gears"
                                style="color:var(--primary);"></i> Pengaturan & Konfigurasi Sistem</h1>
                        <p style="color:var(--text-muted); font-size:0.88rem; margin:0;">
                            Manajemen Hak Akses (RBAC 10 Roles x 16 Modul), Threshold SLA, Master Site Project, Kategori
                            Asset, & Security System
                        </p>
                    </div>
                    <div>
                        <button class="btn btn-primary" onclick="saveAllSettings()"><i
                                class="fa-solid fa-floppy-disk"></i> Simpan Seluruh Konfigurasi</button>
                    </div>
                </div>

                <!-- Dual-Pane Settings Layout ("Menu di Dalam Menu") -->
                <div class="settings-layout" style="display:flex; gap:20px; flex-wrap:wrap; align-items:flex-start;">

                    <!-- Left Sub-Menu Navigation Sidebar -->
                    <div class="settings-sidebar-nav"
                        style="flex: 0 0 270px; min-width: 240px; background: var(--bg-surface); border-radius: var(--radius-panel); border: 1px solid var(--border); padding: 12px;">
                        <div
                            style="font-size:0.85rem; font-weight:700; text-transform:uppercase; color:var(--text-muted); padding:6px 10px 10px 10px; letter-spacing:0.05em; border-bottom:1px solid #e2e8f0; margin-bottom:10px;">
                            <i class="fa-solid fa-bars-staggered"></i> Menu Pengaturan
                        </div>
                        <div class="settings-nav-item" onclick="switchSettingsTab('rbac', this)">
                            <i class="fa-solid fa-shield-halved"></i>
                            <div>
                                <strong>Manajemen Hak Akses</strong>
                                <small>Tinjauan Matriks & Manajemen User</small>
                            </div>
                        </div>
                        <div class="settings-nav-item" onclick="switchSettingsTab('threshold', this)">
                            <i class="fa-solid fa-sliders"></i>
                            <div>
                                <strong>Threshold & SLA Parameter</strong>
                                <small>WO SLA, Red Line, Fuel & Idling</small>
                            </div>
                        </div>
                        <div class="settings-nav-item" onclick="switchSettingsTab('location', this)">
                            <i class="fa-solid fa-location-dot"></i>
                            <div>
                                <strong>Master Site & Yard Project</strong>
                                <small><span class="badge badge-warning" style="font-size:0.7rem; padding:1px 5px;">Read-Only</span> Master Lokasi</small>
                            </div>
                        </div>
                        <div class="settings-nav-item" onclick="switchSettingsTab('category', this)">
                            <i class="fa-solid fa-tags"></i>
                            <div>
                                <strong>Kategori & Job Standard Rates</strong>
                                <small><span class="badge badge-secondary" style="font-size:0.7rem; padding:1px 5px;">Referensi</span> Klasifikasi Standar</small>
                            </div>
                        </div>
                        <div class="settings-nav-item" onclick="switchSettingsTab('template', this)">
                            <i class="fa-solid fa-file-signature"></i>
                            <div>
                                <strong>Template Dokumen & Kop Surat</strong>
                                <small>Penomoran WO, PO, & Kop BRA</small>
                            </div>
                        </div>
                        <div class="settings-nav-item" onclick="switchSettingsTab('security', this)">
                            <i class="fa-solid fa-lock"></i>
                            <div>
                                <strong>Keamanan & Audit Trail Log</strong>
                                <small>Session Timeout & Audit Log</small>
                            </div>
                        </div>
                    </div>

                    <!-- Right Content Panels Area -->
                    <div class="settings-content-area"
                        style="flex:1; min-width:300px; background:#fff; border-radius:10px; border:1px solid var(--border); padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.04);">

                        <!-- Default State Empty Placeholder -->
                        <div id="settings-default-empty" class="settings-panel" style="display:flex; flex-direction:column; align-items:center; justify-content:center; padding: 60px 20px; text-align:center;">
                            <div style="width:64px; height:64px; border-radius:50%; background:#eff6ff; color:#0284c7; display:flex; align-items:center; justify-content:center; font-size:1.8rem; margin-bottom:16px;">
                                <i class="fa-solid fa-sliders"></i>
                            </div>
                            <h3 style="font-size:1.1rem; color:#0f172a; margin:0 0 6px 0; font-weight:800;">Pilih Submenu Pengaturan</h3>
                            <p style="color:#64748b; font-size:0.84rem; max-width:420px; margin:0; line-height:1.5;">
                                Silakan pilih salah satu opsi menu konfigurasi di sebelah kiri untuk melihat dan mengedit parameter sistem.
                            </p>
                        </div>

                        <!-- Panel 1: Hak Akses RBAC (10 Roles x 16 Modul Matrix) -->
                        <div id="settings-rbac" class="settings-panel" style="display:none;">
                            <div style="background:#eff6ff; border:1px solid #bfdbfe; border-left:4px solid #0284c7; border-radius:6px; padding:10px 14px; margin-bottom:14px; font-size:0.82rem; color:#1e40af;">
                                <i class="fa-solid fa-circle-info"></i> <strong>Otorisasi Terpusat Server:</strong> Kebijakan hak akses modul ditegakkan oleh lapisan keamanan backend (<code>api/security.php</code>). Matriks di bawah ini merupakan visualisasi standar perizinan 10 Peran. Pengelolaan akun pengguna per role dikelola persisten di database via tombol <strong>Daftar Pengguna</strong> pada masing-masing peran.
                            </div>
                            <div
                                style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:15px;">
                                <div>
                                    <h3 style="margin:0; font-size:1.1rem; color:var(--text-dark);"><i
                                            class="fa-solid fa-user-shield" style="color:var(--primary);"></i> Matriks
                                        Hak Akses Pengguna (RBAC 10 Roles)</h3>
                                    <p style="font-size:0.82rem; color:var(--text-muted); margin:3px 0 0 0;">
                                        Matriks Distribusi Hak Akses Terstandarisasi Sistem (10 User Roles x 16 Modul
                                        Operasional)
                                    </p>
                                </div>
                                <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                                    <button class="btn btn-secondary" style="padding:4px 12px; font-size:0.78rem;"
                                        onclick="resetRbacToDefault()"><i class="fa-solid fa-rotate-left"></i> Reset</button>
                                    <div style="display:flex; gap:5px; flex-wrap:wrap; font-size:0.85rem;">
                                        <span
                                            style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:3px 7px; border-radius:4px; font-weight:700;">R
                                            = Read</span>
                                        <span
                                            style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:3px 7px; border-radius:4px; font-weight:700;">C
                                            = Create</span>
                                        <span
                                            style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:3px 7px; border-radius:4px; font-weight:700;">U
                                            = Update</span>
                                        <span
                                            style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:3px 7px; border-radius:4px; font-weight:700;">A
                                            = Approve</span>
                                        <span
                                            style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:3px 7px; border-radius:4px; font-weight:700;">O
                                            = Owner</span>
                                        <span
                                            style="background:#f8fafc; color:#94a3b8; border:1px solid #e2e8f0; padding:3px 7px; border-radius:4px; font-weight:700;">-
                                            = Denied</span>
                                    </div>
                                </div>
                            </div>

                            <!-- 10 Roles Filter Pills Strip -->
                            <div id="rbacRoleFilterButtons" class="rbac-role-filter-strip"
                                style="display:flex; gap:6px; overflow-x:auto; padding-bottom:8px; margin-bottom:12px; -webkit-overflow-scrolling:touch; scrollbar-width:thin;">
                                <!-- Rendered dynamically via renderRbacMatrix -->
                            </div>

                            <!-- Selected Role Summary Card -->
                            <div id="rbacRoleSummaryBox"
                                style="background:#f8fafc; border:1px solid #e2e8f0; border-left:4px solid var(--primary); border-radius:8px; padding:12px 15px; margin-bottom:15px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
                                <!-- Rendered via JS -->
                            </div>

                            <!-- Interactive RBAC Matrix Table -->
                            <div class="table-scroll-hint rbac-table-scroll-hint"
                                style="display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; font-size:0.75rem; color:var(--text-muted);">
                                <span><i class="fa-solid fa-arrows-left-right"></i> Geser ke samping untuk melihat 16 modul operasional</span>
                                <span style="font-weight:700; color:var(--primary); font-size:0.75rem;">10 Roles &times; 16 Modul</span>
                            </div>
                            <div class="table-responsive rbac-matrix-container"
                                style="max-height:520px; overflow-x:auto; overflow-y:auto; border:1px solid #e2e8f0; border-radius:8px; -webkit-overflow-scrolling:touch; width:100%;">
                                <table class="table rbac-matrix-table"
                                    style="font-size:0.78rem; white-space:nowrap; margin:0; width:max-content; min-width:2400px;"
                                    id="rbacMatrixTable">
                                    <!-- Rendered via JS -->
                                </table>
                            </div>
                        </div>

                        <!-- Panel 2: Threshold & SLA Parameter -->
                        <div id="settings-threshold" class="settings-panel" style="display:none;">
                            <h3 style="margin-bottom:15px; font-size:1.1rem; color:var(--text-dark);"><i
                                    class="fa-solid fa-sliders" style="color:var(--primary);"></i> Konfigurasi
                                Threshold, SLA & Alarm Operasional</h3>
                            <div
                                style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:15px;">
                                <div class="form-group">
                                    <label style="font-weight:600; font-size:0.85rem;">SLA Tiket WO dari Unit Down
                                        (Menit)</label>
                                    <input type="number" id="cfg-sla-wo" class="form-control" value="<?= htmlspecialchars((string) ($settingsMap['cfg_sla_wo'] ?? '30'), ENT_QUOTES, 'UTF-8') ?>">
                                    <small style="color:var(--text-muted);">Target respon maksimal mekanik setelah P2H
                                        merah</small>
                                </div>
                                <div class="form-group">
                                    <label style="font-weight:600; font-size:0.85rem;">Batas Merah Aus Ban / Tread Limit
                                        (mm)</label>
                                    <input type="number" id="cfg-tire-red" class="form-control" value="<?= htmlspecialchars((string) ($settingsMap['cfg_tire_red'] ?? '3.2'), ENT_QUOTES, 'UTF-8') ?>" step="0.1">
                                    <small style="color:var(--text-muted);">Tread depth di bawah nilai ini dianggap
                                        kritis</small>
                                </div>
                                <div class="form-group">
                                    <label style="font-weight:600; font-size:0.85rem;">Batas HM Warning PM Due Soon
                                        (HM)</label>
                                    <input type="number" id="cfg-pm-warning" class="form-control" value="<?= htmlspecialchars((string) ($settingsMap['cfg_pm_warning'] ?? '50'), ENT_QUOTES, 'UTF-8') ?>">
                                    <small style="color:var(--text-muted);">Peringatan PM otomatis muncul H-50
                                        HM</small>
                                </div>
                                <div class="form-group">
                                    <label style="font-weight:600; font-size:0.85rem;">Toleransi % Anomali Konsumsi BBM
                                        (%)</label>
                                    <input type="number" id="cfg-fuel-anomali" class="form-control" value="<?= htmlspecialchars((string) ($settingsMap['cfg_fuel_anomali'] ?? '15.0'), ENT_QUOTES, 'UTF-8') ?>"
                                        step="0.1">
                                    <small style="color:var(--text-muted);">LPH/KPL di luar toleransi memicu alarm
                                        sounding</small>
                                </div>
                                <div class="form-group">
                                    <label style="font-weight:600; font-size:0.85rem;">Telematika Komtrax Idling Alarm
                                        Limit (%)</label>
                                    <input type="number" id="cfg-idling-limit" class="form-control" value="<?= htmlspecialchars((string) ($settingsMap['cfg_idling_limit'] ?? '50.0'), ENT_QUOTES, 'UTF-8') ?>"
                                        step="1.0">
                                    <small style="color:var(--text-muted);">Persentase engine idling dianggap pemborosan
                                        solar</small>
                                </div>
                                <div class="form-group">
                                    <label style="font-weight:600; font-size:0.85rem;">Safety Stock Minimum Cutting Bit
                                        (Pieces)</label>
                                    <input type="number" id="cfg-cuttingbit-safety" class="form-control" value="<?= htmlspecialchars((string) ($settingsMap['cfg_cuttingbit_safety'] ?? '380'), ENT_QUOTES, 'UTF-8') ?>">
                                    <small style="color:var(--text-muted);">Persediaan 20% unit RM XCMG
                                        BRR-RM-4101</small>
                                </div>
                            </div>
                            <div style="margin-top:15px;">
                                <button class="btn btn-primary" onclick="saveSettings()"><i
                                        class="fa-solid fa-check"></i> Simpan Parameter Threshold</button>
                            </div>
                        </div>

                        <!-- Panel 3: Master Site & Yard Project -->
                        <div id="settings-location" class="settings-panel" style="display:none;">
                            <div style="background:#fefce8; border:1px solid #fef08a; border-left:4px solid #ca8a04; border-radius:6px; padding:10px 14px; margin-bottom:14px; font-size:0.82rem; color:#854d0e;">
                                <i class="fa-solid fa-triangle-exclamation"></i> <strong>Panel Nonaktif:</strong> Master Site & Yard Project saat ini dikelola terpusat pada tabel master sistem (<code>locations</code>). Form mutasi lokasi mandiri sedang dinonaktifkan untuk menjaga integritas relasi inventaris alat berat.
                            </div>
                            <div
                                style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                                <h3 style="margin:0; font-size:1.1rem; color:var(--text-dark);"><i
                                        class="fa-solid fa-location-dot" style="color:var(--primary);"></i> Master Site
                                    Project & Base Camp Armada</h3>
                                <span class="badge badge-warning" style="font-size:0.78rem;">Read-Only</span>
                            </div>
                            <div
                                style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:12px;">
                                <div
                                    style="border:1px solid #e2e8f0; border-radius:8px; padding:12px 15px; background:#fafafa;">
                                    <div style="display:flex; justify-content:space-between; align-items:center;">
                                        <strong style="font-size:0.92rem; color:var(--primary);">Yard KM 12
                                            Duri</strong>
                                        <span class="badge badge-success">Utama (Base Camp)</span>
                                    </div>
                                    <p style="font-size:0.8rem; color:var(--text-muted); margin:6px 0 8px 0;">Bengkel
                                        Utama Overhaul & Gudang Part Duri, Riau</p>
                                    <div style="font-size:0.85rem; color:#475569;">
                                        <div><i class="fa-solid fa-user"></i> <strong>Penanggung Jawab:</strong> Budi
                                            Santoso (Eq Manager)</div>
                                        <div><i class="fa-solid fa-truck-monster"></i> <strong>Aset Aktif:</strong> 42
                                            Unit</div>
                                        <div><i class="fa-solid fa-map-pin"></i> <strong>GPS:</strong> 1.2584&deg; N,
                                            101.2145&deg; E</div>
                                    </div>
                                </div>

                                <div
                                    style="border:1px solid #e2e8f0; border-radius:8px; padding:12px 15px; background:#fafafa;">
                                    <div style="display:flex; justify-content:space-between; align-items:center;">
                                        <strong style="font-size:0.92rem; color:var(--warning);">Harapan Baru Pit
                                            Field</strong>
                                        <span class="badge badge-info">Site Tambang</span>
                                    </div>
                                    <p style="font-size:0.8rem; color:var(--text-muted); margin:6px 0 8px 0;">Lokasi
                                        Pengerjaan Pit Excavator & Heavy Dozer</p>
                                    <div style="font-size:0.85rem; color:#475569;">
                                        <div><i class="fa-solid fa-user"></i> <strong>Penanggung Jawab:</strong> Joko
                                            Widodo (Planner)</div>
                                        <div><i class="fa-solid fa-truck-monster"></i> <strong>Aset Aktif:</strong> 18
                                            Unit</div>
                                        <div><i class="fa-solid fa-map-pin"></i> <strong>GPS:</strong> 1.2890&deg; N,
                                            101.2400&deg; E</div>
                                    </div>
                                </div>

                                <div
                                    style="border:1px solid #e2e8f0; border-radius:8px; padding:12px 15px; background:#fafafa;">
                                    <div style="display:flex; justify-content:space-between; align-items:center;">
                                        <strong style="font-size:0.92rem; color:var(--success);">Yard
                                            Prabumulih</strong>
                                        <span class="badge badge-secondary">Cabang Sumsel</span>
                                    </div>
                                    <p style="font-size:0.8rem; color:var(--text-muted); margin:6px 0 8px 0;">Depo
                                        Peralatan & Service Center Sumatra Selatan</p>
                                    <div style="font-size:0.85rem; color:#475569;">
                                        <div><i class="fa-solid fa-user"></i> <strong>Penanggung Jawab:</strong> Ahmad
                                            Dahlan (Logistics)</div>
                                        <div><i class="fa-solid fa-truck-monster"></i> <strong>Aset Aktif:</strong> 14
                                            Unit</div>
                                        <div><i class="fa-solid fa-map-pin"></i> <strong>GPS:</strong> 3.4312&deg; S,
                                            104.2341&deg; E</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel 4: Kategori & Standard Rates -->
                        <div id="settings-category" class="settings-panel" style="display:none;">
                            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-left:4px solid #64748b; border-radius:6px; padding:10px 14px; margin-bottom:14px; font-size:0.82rem; color:#334155;">
                                <i class="fa-solid fa-book"></i> <strong>Panel Referensi Standard:</strong> Tarif dan klasifikasi di bawah ini merupakan pedoman standar operasional BRA. Penyesuaian harga riil tercatat pada transaksi Purchase Order dan kontrak vendor terkait.
                            </div>
                            <h3 style="margin-bottom:15px; font-size:1.1rem; color:var(--text-dark);"><i
                                    class="fa-solid fa-tags" style="color:var(--primary);"></i> Klasifikasi Sparepart &
                                Standard Job Rates</h3>
                            <div
                                style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:15px;">
                                <div
                                    style="border:1px solid #e2e8f0; border-radius:8px; padding:15px; background:#fafafa;">
                                    <h4 style="margin-top:0; font-size:0.9rem; color:var(--primary);">Klasifikasi ABC
                                        Spareparts</h4>
                                    <ul style="padding-left:18px; font-size:0.82rem; color:var(--text-dark); margin:0;">
                                        <li><strong>Kategori A (High Value & Fast Moving):</strong> Filter, Oli Engine,
                                            Cutting Bit, Track Shoe</li>
                                        <li><strong>Kategori B (Medium Value):</strong> Hose Hydraulic, Seal Kit,
                                            Injector Nozzle</li>
                                        <li><strong>Kategori C (Low Value / Consumables):</strong> Baut Track, Grease,
                                            Rag, O-Ring</li>
                                    </ul>
                                </div>
                                <div
                                    style="border:1px solid #e2e8f0; border-radius:8px; padding:15px; background:#fafafa;">
                                    <h4 style="margin-top:0; font-size:0.9rem; color:var(--success);">Standard Outsource
                                        Job Rates</h4>
                                    <div style="font-size:0.82rem; color:var(--text-dark);">
                                        <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                                            <span>Jasa Welder Outsource:</span>
                                            <strong>Rp 150.000 / Jam</strong>
                                        </div>
                                        <div style="display:flex; justify-content:space-between; margin-bottom:5px;">
                                            <span>Overhaul Engine Subkontraktor:</span>
                                            <strong>Rp 45.000.000 / Unit</strong>
                                        </div>
                                        <div style="display:flex; justify-content:space-between;">
                                            <span>Kalibrasi Pump & Injector:</span>
                                            <strong>Rp 8.500.000 / Set</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel 5: Template Dokumen & Kop Surat -->
                        <div id="settings-template" class="settings-panel" style="display:none;">
                            <h3 style="margin-bottom:15px; font-size:1.1rem; color:var(--text-dark);"><i
                                    class="fa-solid fa-file-signature" style="color:var(--primary);"></i> Template
                                Penomoran & Kop Surat Laporan</h3>
                            <div
                                style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:15px;">
                                <div class="form-group">
                                    <label style="font-weight:600; font-size:0.85rem;">Prefix Penomoran Work Order
                                        (WO)</label>
                                    <input type="text" id="cfg-wo-prefix" class="form-control" value="<?= htmlspecialchars((string) ($settingsMap['cfg_wo_prefix'] ?? 'WO-BRA-YYYYMM-XXXX'), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="form-group">
                                    <label style="font-weight:600; font-size:0.85rem;">Prefix Penomoran Purchase Order
                                        (PO)</label>
                                    <input type="text" id="cfg-po-prefix" class="form-control" value="<?= htmlspecialchars((string) ($settingsMap['cfg_po_prefix'] ?? 'PO-BRA-YYYY-XXXX'), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="form-group" style="grid-column:1 / -1;">
                                    <label style="font-weight:600; font-size:0.85rem;">Nama Perusahaan & Header Kop
                                        Surat</label>
                                    <input type="text" id="cfg-company-header" class="form-control"
                                        value="<?= htmlspecialchars((string) ($settingsMap['cfg_company_header'] ?? 'PT BINA REKAYASA ANUGERAH - Departemen Equipment & Maintenance'), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                            </div>
                            <div style="margin-top:15px;">
                                <button class="btn btn-primary" onclick="saveAllSettings()"><i
                                        class="fa-solid fa-check"></i> Simpan Konfigurasi Template</button>
                            </div>
                        </div>

                        <!-- Panel 6: Keamanan & Audit Trail Log -->
                        <div id="settings-security" class="settings-panel" style="display:none;">
                            <h3 style="margin-bottom:15px; font-size:1.1rem; color:var(--text-dark);"><i
                                    class="fa-solid fa-lock" style="color:var(--primary);"></i> Parameter Keamanan
                                System & Audit Trail</h3>
                            <div
                                style="display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:15px;">
                                <div class="form-group">
                                    <label style="font-weight:600; font-size:0.85rem;">Idle Session Timeout
                                        (Menit)</label>
                                    <input type="number" id="cfg-idle-timeout" class="form-control" value="<?= htmlspecialchars((string) ($settingsMap['cfg_idle_timeout'] ?? '30'), ENT_QUOTES, 'UTF-8') ?>">
                                </div>
                                <div class="form-group">
                                    <label style="font-weight:600; font-size:0.85rem;">Frekuensi Automatic Backup
                                        Database</label>
                                    <?php $curBackup = (string) ($settingsMap['cfg_backup_frequency'] ?? 'Setiap Hari (00:00 WIB)'); ?>
                                    <select id="cfg-backup-frequency" class="form-control">
                                        <option value="Setiap Hari (00:00 WIB)" <?= $curBackup === 'Setiap Hari (00:00 WIB)' ? 'selected' : '' ?>>Setiap Hari (00:00 WIB)</option>
                                        <option value="Setiap 12 Jam" <?= $curBackup === 'Setiap 12 Jam' ? 'selected' : '' ?>>Setiap 12 Jam</option>
                                        <option value="Setiap Minggu" <?= $curBackup === 'Setiap Minggu' ? 'selected' : '' ?>>Setiap Minggu</option>
                                    </select>
                                </div>
                            </div>
                            <div style="margin-top:15px;">
                                <button class="btn btn-primary" onclick="saveAllSettings()"><i
                                        class="fa-solid fa-check"></i> Simpan Parameter Keamanan</button>
                            </div>
                            <div style="margin-top:15px; border-top:1px solid #e2e8f0; padding-top:12px;">
                                <strong style="font-size:0.85rem; color:var(--text-dark);"><i
                                        class="fa-solid fa-history"></i> Ringkasan Integration Audit Log (5 Aktivitas
                                    Terakhir):</strong>
                                <ul
                                    style="font-size:0.78rem; color:var(--text-muted); padding-left:18px; margin:8px 0 0 0;">
                                    <li>[29/07 19:42] User <strong>briannugraha</strong> memperbarui status Work Order
                                        WO-202607-004 ke CLOSED / READY.</li>
                                    <li>[29/07 18:30] System otomatis mendeteksi anomali sounding BBM unit Exca 16 (Site
                                        Duri).</li>
                                    <li>[29/07 15:10] User <strong>seagrass489-cell</strong> menyetujui PO Spareparts
                                        PO_DFT_01508 Trakindo Utama.</li>
                                </ul>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- VIEW: ARCHIVE -->
            <div id="view-archive" class="view-section">
                <h1 class="page-title"><i class="fa-solid fa-box-archive"></i> Archive Data</h1>

                <div class="panel">
                    <div class="p2h-nav-tabs">
                        <button class="p2h-tab-btn active" id="archiveTabBtnAsset"
                            onclick="window.switchArchiveTab('asset')">
                            <i class="fa-solid fa-truck"></i> Unit Armada
                        </button>
                        <button class="p2h-tab-btn" id="archiveTabBtnP2H" onclick="window.switchArchiveTab('p2h')">
                            <i class="fa-solid fa-list-check"></i> Riwayat P2H
                        </button>
                        <button class="p2h-tab-btn" id="archiveTabBtnAccident"
                            onclick="window.switchArchiveTab('accident')">
                            <i class="fa-solid fa-file-medical"></i> Insiden / Accident
                        </button>
                    </div>

                    <div class="panel-body" style="padding: 20px;">
                        <!-- TAB: ASSET ARCHIVE -->
                        <div id="archiveSectionAsset">
                            <div class="table-responsive">
                                <table class="table" id="tbArchiveAsset">
                                    <thead>
                                        <tr>
                                            <th>Asset ID / Kode</th>
                                            <th>Nomor Lambung</th>
                                            <th>Kategori Unit</th>
                                            <th>Status</th>
                                            <th>Lokasi / Base</th>
                                            <th>Reading Akhir</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbArchiveAssetBody">
                                        <!-- Rendered via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB: P2H ARCHIVE -->
                        <div id="archiveSectionP2H" style="display:none;">
                            <div class="table-responsive">
                                <table class="table" id="tbArchiveP2H">
                                    <thead>
                                        <tr>
                                            <th>No. Tiket P2H</th>
                                            <th>Tanggal & Waktu</th>
                                            <th>Unit ID / Kategori</th>
                                            <th>Operator / NRP</th>
                                            <th>Site / Lokasi</th>
                                            <th>Reading HM</th>
                                            <th>Status Kelayakan</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbArchiveP2HBody">
                                        <!-- Rendered via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB: ACCIDENT ARCHIVE -->
                        <div id="archiveSectionAccident" style="display:none;">
                            <div class="table-responsive">
                                <table class="table" id="tbArchiveAccident">
                                    <thead>
                                        <tr>
                                            <th>No. Dokumen</th>
                                            <th>Tanggal & Jam</th>
                                            <th>Kode Unit</th>
                                            <th>Lokasi Kejadian</th>
                                            <th>Operator</th>
                                            <th>Severitas</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbArchiveAccidentBody">
                                        <!-- Rendered via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW: HEAVY EQUIPMENT PROPERTIES & DIAGNOSTICS INSPECTOR (UNIFIED SINGLE VIEW) -->
            <div id="view-unit-properties" class="view-section">
                <!-- Top Navigation & Action Bar -->
                <div class="prop-top-bar">
                    <div class="prop-breadcrumb-group">
                        <button type="button" class="prop-btn-back" onclick="window.backFromUnitProperties()">
                            <i class="fa-solid fa-arrow-left"></i> Kembali
                        </button>
                        <div class="prop-breadcrumbs">
                            <span>Fleet Management</span>
                            <i class="fa-solid fa-chevron-right" style="font-size:0.65rem; color:#94a3b8;"></i>
                            <span>Properties Alat Berat</span>
                            <i class="fa-solid fa-chevron-right" style="font-size:0.65rem; color:#94a3b8;"></i>
                            <strong class="prop-render-unit-id">Data belum tersedia</strong>
                        </div>
                    </div>
                    <div class="prop-top-actions">
                        <select id="propUnitSelector" class="prop-unit-selector" aria-label="Pilih unit untuk melihat properties" onchange="window.openUnitProperties(this.value)">
                            <!-- Populated via JS -->
                        </select>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="window.printUnitProperties()" style="display:inline-flex; align-items:center; gap:6px; font-weight:600;">
                            <i class="fa-solid fa-print"></i> Cetak Lembar Properties
                        </button>
                    </div>
                </div>

                <!-- Industrial Equipment Identity Card & Vitals -->
                <div class="prop-spec-header-card">
                    <div class="prop-header-main-row">
                        <div class="prop-unit-id-block">
                            <div class="prop-category-icon-box" id="propCategoryIcon">
                                <i class="fa-solid fa-truck-front"></i>
                            </div>
                            <div class="prop-unit-title">
                                <h1>
                                    <span class="prop-render-unit-id">Data belum tersedia</span>
                                    <span id="propUnitStatusPill" class="prop-status-pill">
                                        <span class="prop-pulse-dot"></span> Data belum tersedia
                                    </span>
                                </h1>
                                <div class="unit-model-subtitle">
                                    <span id="propUnitModel">Data belum tersedia</span> &bull;
                                    Plat: <strong id="propUnitPlate" style="color:#0f172a;">Data belum tersedia</strong> &bull;
                                    SN: <code id="propUnitSerial" style="color:#475569;">Data belum tersedia</code>
                                </div>
                                <div class="prop-hull-interactive-row">
                                    <span class="prop-hull-label">Nomor lambung:</span>
                                    <span class="prop-hull-interactive-group">
                                        <a href="javascript:void(0)" class="prop-hull-link" id="propUnitHullLink" onclick="window.openPropHullHistoryModal()" title="Klik untuk melihat riwayat nomor lambung">
                                            <strong id="propUnitHull">Data belum tersedia</strong>
                                            <i class="fa-solid fa-clock-rotate-left prop-hull-history-badge-icon" title="Riwayat Nomor Lambung"></i>
                                        </a>
                                        <button type="button" class="prop-btn-icon-rename" id="btnPropRenameHull" onclick="window.openPropRenameModal()" title="Ubah / Rename Nomor Lambung">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Layout Utama: Kolom Kiri (Dokumen + Bento Vitals) & Kolom Kanan (Card Galeri 3x3) -->
                    <div class="prop-hero-layout-grid">
                        <!-- Kolom Kiri -->
                        <div class="prop-hero-left">
                            <!-- Strip Dokumen Legalitas & Berkas JO (STNK, KIR, SILO, PDF JO) -->
                            <div class="prop-docs-strip-card" id="propDocsStrip">
                                <div class="prop-docs-strip-label">
                                    <i class="fa-solid fa-file-shield" style="color:var(--primary, #0284c7);"></i>
                                    <span>Legalitas &amp; Dokumen:</span>
                                </div>
                                <div class="prop-docs-links-container" id="propDocsLinksContainer">
                                    <!-- Rendered dynamically via JS -->
                                </div>
                            </div>

                            <!-- Bento Grid Vitals: 5 Card Onboarding & PM + 4 Card Telemetrik Operasional -->
                            <div class="prop-vitals-bento-grid">
                                <!-- Vitals Onboarding & PM -->
                                <div class="prop-vital-cell" id="cardVitalOperator">
                                    <div class="vital-label-row">
                                        <div class="vital-label"><i class="fa-solid fa-user-gear"></i> Operator Saat Ini</div>
                                        <a href="javascript:void(0)" class="vital-history-link" onclick="window.openPropOperatorHistoryModal()" title="Lihat Riwayat Penugasan Operator">
                                            <i class="fa-solid fa-clock-rotate-left"></i>
                                        </a>
                                    </div>
                                    <div class="vital-val" id="propVitalOperator">Data belum tersedia</div>
                                    <div class="vital-sub" id="propVitalOperatorSub">Data belum tersedia</div>
                                </div>

                                <div class="prop-vital-cell" id="cardVitalLastService">
                                    <div class="vital-label-row">
                                        <div class="vital-label"><i class="fa-solid fa-wrench"></i> Servis Terakhir</div>
                                        <a href="javascript:void(0)" class="vital-history-link" onclick="window.openPropServiceHistoryModal()" title="Lihat Riwayat Perawatan & Servis">
                                            <i class="fa-solid fa-clock-rotate-left"></i>
                                        </a>
                                    </div>
                                    <div class="vital-val" id="propVitalLastService">Data belum tersedia</div>
                                    <div class="vital-sub" id="propVitalLastServiceSub">Data belum tersedia</div>
                                </div>

                                <div class="prop-vital-cell" id="cardVitalInterval">
                                    <div class="vital-label-row">
                                        <div class="vital-label"><i class="fa-solid fa-arrows-rotate"></i> Interval Servis</div>
                                    </div>
                                    <div class="vital-val" id="propVitalInterval">Data belum tersedia</div>
                                    <div class="vital-sub" id="propVitalIntervalSub">Data belum tersedia</div>
                                </div>

                                <div class="prop-vital-cell" id="cardVitalNextTarget">
                                    <div class="vital-label-row">
                                        <div class="vital-label"><i class="fa-solid fa-bullseye"></i> Target Berikutnya</div>
                                    </div>
                                    <div class="vital-val" id="propVitalNextTarget">Data belum tersedia</div>
                                    <div class="vital-sub" id="propVitalNextTargetSub">Data belum tersedia</div>
                                </div>

                                <div class="prop-vital-cell" id="cardVitalEstimate">
                                    <div class="vital-label-row">
                                        <div class="vital-label"><i class="fa-solid fa-calendar-check"></i> Estimasi Servis</div>
                                    </div>
                                    <div class="vital-val" id="propVitalEstimate">Data belum tersedia</div>
                                    <div class="vital-sub" id="propVitalEstimateSub">Data belum tersedia</div>
                                </div>

                                <!-- Core Telemetry Vitals -->
                                <div class="prop-vital-cell">
                                    <div class="vital-label-row">
                                        <div class="vital-label"><i class="fa-solid fa-gauge-high"></i> Total Jam Kerja (HM)</div>
                                    </div>
                                    <div class="vital-val" id="propVitalHm">Data belum tersedia</div>
                                    <div class="vital-sub">Data belum tersedia</div>
                                </div>
                                <div class="prop-vital-cell" id="cardVitalLocation">
                                    <div class="vital-label-row">
                                        <div class="vital-label"><i class="fa-solid fa-location-dot"></i> Posisi Terkini</div>
                                        <a href="javascript:void(0)" class="vital-history-link" onclick="window.openSiteProperties(document.getElementById('propVitalLoc').textContent.trim(), 'unit-properties')" title="Buka Detail Lembar Properties Site Ini">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        </a>
                                    </div>
                                    <div class="vital-val" id="propVitalLoc" style="font-size:0.95rem; cursor:pointer;" onclick="window.openSiteProperties(this.textContent.trim(), 'unit-properties')" title="Klik untuk membuka lembar properties site ini">Data belum tersedia</div>
                                    <div class="vital-sub" id="propVitalLocSub">Mining Sector Area</div>
                                </div>
                                <div class="prop-vital-cell">
                                    <div class="vital-label-row">
                                        <div class="vital-label"><i class="fa-solid fa-heart-pulse"></i> Health Score</div>
                                    </div>
                                    <div class="vital-val" id="propVitalHealth">Data belum tersedia</div>
                                    <div class="vital-sub">Data belum tersedia</div>
                                </div>
                                <div class="prop-vital-cell">
                                    <div class="vital-label-row">
                                        <div class="vital-label"><i class="fa-solid fa-clock"></i> Servis PM Berikut</div>
                                    </div>
                                    <div class="vital-val" id="propVitalNextService" style="font-size:0.95rem;">Data belum tersedia</div>
                                    <div class="vital-sub">Data belum tersedia</div>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Kanan: Card Galeri Foto Unit Sizing 3x3 -->
                        <div class="prop-hero-right">
                            <div class="prop-gallery-card">
                                <div class="prop-gallery-header">
                                    <div class="prop-gallery-header-title">
                                        <i class="fa-solid fa-camera"></i>
                                        <span>Galeri Foto Unit</span>
                                    </div>
                                    <div class="prop-gallery-header-actions">
                                        <span class="prop-gallery-badge" id="propGalleryCount">0 Foto</span>
                                        <button type="button" class="btn-prop-upload" id="btnPropUploadPhoto" onclick="window.triggerPropPhotoUpload()" title="Upload Foto Unit (Format: PNG, JPG, JPEG)">
                                            <i class="fa-solid fa-plus"></i> <span>Upload</span>
                                        </button>
                                        <input type="file" id="propPhotoFileInput" accept=".png,.jpg,.jpeg,image/png,image/jpeg" style="display:none;" onchange="window.handlePropPhotoSelected(this)">
                                    </div>
                                </div>
                                <div class="prop-gallery-body" id="propGalleryContainer">
                                    <!-- Rendered dynamically via JS: 3x3 grid or empty state -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 1: LIVE MAP & TELEMETRICS (PLACED AT TOP) -->
                <div class="prop-section-heading">
                    <i class="fa-solid fa-map-location-dot"></i> 1. Peta Lokasi, Telematika &amp; Status Operasional Lapangan
                </div>
                <div class="prop-panel-grid-top-map">
                    <!-- Leaflet Live Map -->
                    <div class="prop-card">
                        <div class="prop-card-header" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                            <h3><i class="fa-solid fa-map" style="color:#0284c7;"></i> Peta Lokasi Unit</h3>
                            <div id="propMapStatusBadge"></div>
                        </div>
                        <div class="prop-card-body" style="padding:10px;">
                            <div id="propUnitMap"></div>
                        </div>
                    </div>

                    <!-- Right Column: CANbus Gauges & GPS Breadcrumb -->
                    <div>
                        <!-- Live Telemetry CANbus Gauges -->
                        <div class="prop-card">
                            <div class="prop-card-header">
                                <h3><i class="fa-solid fa-satellite-dish" style="color:#0284c7;"></i> Telemetri CANbus</h3>
                            </div>
                            <div class="prop-card-body" style="padding:10px;">
                                <div class="prop-telemetry-grid">
                                    <div class="prop-gauge-box">
                                        <div class="prop-gauge-icon"><i class="fa-solid fa-gauge"></i></div>
                                        <div class="prop-gauge-val"><span id="propGaugeRpm" class="prop-gauge-no-data">Data belum tersedia</span><span class="prop-gauge-unit" hidden>RPM</span></div>
                                        <div class="prop-gauge-title">Engine Speed</div>
                                    </div>
                                    <div class="prop-gauge-box">
                                        <div class="prop-gauge-icon" style="color:#ef4444;"><i class="fa-solid fa-temperature-high"></i></div>
                                        <div class="prop-gauge-val"><span id="propGaugeCoolant" class="prop-gauge-no-data">Data belum tersedia</span><span class="prop-gauge-unit" hidden>&deg;C</span></div>
                                        <div class="prop-gauge-title">Coolant Temp</div>
                                    </div>
                                    <div class="prop-gauge-box">
                                        <div class="prop-gauge-icon" style="color:#f59e0b;"><i class="fa-solid fa-oil-can"></i></div>
                                        <div class="prop-gauge-val"><span id="propGaugeOilPress" class="prop-gauge-no-data">Data belum tersedia</span><span class="prop-gauge-unit" hidden>Bar</span></div>
                                        <div class="prop-gauge-title">Oil Pressure</div>
                                    </div>
                                    <div class="prop-gauge-box">
                                        <div class="prop-gauge-icon" style="color:#16a34a;"><i class="fa-solid fa-bolt"></i></div>
                                        <div class="prop-gauge-val"><span id="propGaugeVolt" class="prop-gauge-no-data">Data belum tersedia</span><span class="prop-gauge-unit" hidden>V</span></div>
                                        <div class="prop-gauge-title">Alternator</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- GPS Logs & BAST Movements -->
                        <div class="prop-card">
                            <div class="prop-card-header">
                                <h3><i class="fa-solid fa-route" style="color:#0284c7;"></i> Log Koordinat Unit</h3>
                            </div>
                            <div class="prop-card-body" style="padding:0;">
                                <table class="prop-table">
                                    <thead>
                                        <tr>
                                            <th>Waktu Log</th>
                                            <th>Koordinat (Lat, Lng)</th>
                                            <th>Speed</th>
                                            <th>Ignition</th>
                                        </tr>
                                    </thead>
                                    <tbody id="propGpsLogsTableBody">
                                        <!-- Populated via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: TECHNICAL SPECIFICATIONS (LISTING FORMAT - NON-CARD) -->
                <div class="prop-section-heading">
                    <i class="fa-solid fa-file-lines"></i> 2. Lembar Spesifikasi Teknis Powertrain &amp; Identitas Unit (Technical Data Listing)
                </div>
                <div class="prop-spec-listing-container">
                    <div class="prop-spec-list-grid" id="propSpecListGrid">
                        <!-- Populated via JS Listing Format -->
                    </div>
                </div>

                <!-- SECTION 3: PREVENTIVE MAINTENANCE, WO & SPARE PARTS -->
                <div class="prop-section-heading">
                    <i class="fa-solid fa-screwdriver-wrench"></i> 3. Pemeliharaan Berkala (PM), Work Orders &amp; Konsumsi Suku Cadang
                </div>
                <!-- PM Plans Table -->
                <div class="prop-card">
                    <div class="prop-card-header">
                        <h3><i class="fa-solid fa-calendar-check" style="color:#0284c7;"></i> Matriks Rencana Service Berkala (Preventive Maintenance)</h3>
                    </div>
                    <div class="prop-card-body" style="padding:0;">
                        <table class="prop-table">
                            <thead>
                                <tr>
                                    <th>Program Servis</th>
                                    <th>Interval</th>
                                    <th>Servis Terakhir</th>
                                    <th>Target Due HM</th>
                                    <th>Selisih (Delta)</th>
                                    <th>Status PM</th>
                                    <th>Estimasi Biaya</th>
                                </tr>
                            </thead>
                            <tbody id="propPmPlansTableBody">
                                <!-- Populated via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="prop-panel-grid-2">
                    <!-- Work Orders & Repair History -->
                    <div class="prop-card">
                        <div class="prop-card-header">
                            <h3><i class="fa-solid fa-wrench" style="color:#0284c7;"></i> Riwayat Work Order (WO) &amp; Perbaikan</h3>
                        </div>
                        <div class="prop-card-body" style="padding:0;">
                            <table class="prop-table">
                                <thead>
                                    <tr>
                                        <th>No. WO</th>
                                        <th>Tipe</th>
                                        <th>Keluhan / Tindakan</th>
                                        <th>Mekanik PIC</th>
                                        <th>Downtime</th>
                                        <th>Total Biaya</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="propWoTableBody">
                                    <!-- Populated via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Spare Parts History -->
                    <div class="prop-card">
                        <div class="prop-card-header">
                            <h3><i class="fa-solid fa-gears" style="color:#0284c7;"></i> Log Konsumsi Suku Cadang &amp; SPB</h3>
                        </div>
                        <div class="prop-card-body" style="padding:0;">
                            <table class="prop-table">
                                <thead>
                                    <tr>
                                        <th>Part Number</th>
                                        <th>Nama Suku Cadang</th>
                                        <th>Kategori</th>
                                        <th>Qty</th>
                                        <th>Total Nilai</th>
                                        <th>No. SPB</th>
                                        <th>Tgl Pasang</th>
                                    </tr>
                                </thead>
                                <tbody id="propPartsTableBody">
                                    <!-- Populated via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- SECTION 4: DIAGNOSTICS, TIRES, PAP & P2H -->
                <div class="prop-section-heading">
                    <i class="fa-solid fa-clipboard-check"></i> 4. Diagnostic Kondisi, 10-Tire Blueprint, Oil PAP &amp; Keselamatan
                </div>
                <div class="prop-panel-grid-2">
                    <!-- Tire Schematic Blueprint -->
                    <div class="prop-card">
                        <div class="prop-card-header">
                            <h3><i class="fa-solid fa-circle-dot" style="color:#0284c7;"></i> Diagram Posisi Roda &amp; Tekanan Ban (PSI / RTD)</h3>
                        </div>
                        <div class="prop-card-body">
                            <div class="prop-tire-chassis-container" id="propTireBlueprint">
                                <!-- Populated via JS -->
                            </div>
                            <div style="display:flex; justify-content:center; gap:16px; margin-top:12px; font-size:0.75rem;">
                                <span style="display:inline-flex; align-items:center; gap:5px;"><span style="width:10px; height:10px; border-radius:2px; background:#22c55e;"></span> Kondisi Baik (&gt;14mm)</span>
                                <span style="display:inline-flex; align-items:center; gap:5px;"><span style="width:10px; height:10px; border-radius:2px; background:#f59e0b;"></span> Perlu Rotasi / Pantau</span>
                                <span style="display:inline-flex; align-items:center; gap:5px;"><span style="width:10px; height:10px; border-radius:2px; background:#ef4444;"></span> Kritis / Ganti</span>
                            </div>
                        </div>
                    </div>

                    <!-- Oil PAP Lab Sampling -->
                    <div class="prop-card">
                        <div class="prop-card-header">
                            <h3><i class="fa-solid fa-flask" style="color:#0284c7;"></i> Program Analisa Pelumas (PAP Oil Wear Metals)</h3>
                        </div>
                        <div class="prop-card-body" style="padding:0;">
                            <table class="prop-table">
                                <thead>
                                    <tr>
                                        <th>Kompartemen</th>
                                        <th>Tgl Sampel</th>
                                        <th>Besi (Fe)</th>
                                        <th>Tembaga (Cu)</th>
                                        <th>Aluminium (Al)</th>
                                        <th>Silikon (Si)</th>
                                        <th>Viskositas</th>
                                        <th>Status</th>
                                        <th>Diagnosa Lab</th>
                                    </tr>
                                </thead>
                                <tbody id="propOilPapTableBody">
                                    <!-- Populated via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="prop-panel-grid-2">
                    <!-- Daily P2H Pre-Start Inspections -->
                    <div class="prop-card">
                        <div class="prop-card-header">
                            <h3><i class="fa-solid fa-clipboard-check" style="color:#0284c7;"></i> Riwayat Inspeksi P2H Harian Operator</h3>
                        </div>
                        <div class="prop-card-body" style="padding:0;">
                            <table class="prop-table">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Shift</th>
                                        <th>Operator</th>
                                        <th>Hasil P2H</th>
                                        <th>Kondisi Ban</th>
                                        <th>Kebocoran</th>
                                        <th>Catatan Pemeriksaan</th>
                                    </tr>
                                </thead>
                                <tbody id="propP2hTableBody">
                                    <!-- Populated via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Accidents & Incidents -->
                    <div class="prop-card">
                        <div class="prop-card-header">
                            <h3><i class="fa-solid fa-triangle-exclamation" style="color:#f59e0b;"></i> Log Insiden / Investigasi Keselamatan (TAR)</h3>
                        </div>
                        <div class="prop-card-body" style="padding:0;">
                            <table class="prop-table prop-incident-table">
                                <thead>
                                    <tr>
                                        <th>No. Laporan</th>
                                        <th>Tanggal</th>
                                        <th>Personel Terlibat</th>
                                        <th>Keterangan</th>
                                    </tr>
                                </thead>
                                <tbody id="propAccidentTableBody">
                                    <!-- Populated via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW: SITE LOCATION PROPERTIES & FLEET GEO-DIAGNOSTICS -->
            <div id="view-site-properties" class="view-section">
                <!-- Top Navigation & Action Bar -->
                <div class="site-top-bar">
                    <div class="site-breadcrumb-group">
                        <button type="button" class="site-btn-back" onclick="window.backFromSiteProperties()">
                            <i class="fa-solid fa-arrow-left"></i> Kembali
                        </button>
                        <div class="site-breadcrumbs">
                            <span>Fleet Management</span>
                            <i class="fa-solid fa-chevron-right" style="font-size:0.65rem; color:#94a3b8;"></i>
                            <span>Site Location</span>
                            <i class="fa-solid fa-chevron-right" style="font-size:0.65rem; color:#94a3b8;"></i>
                            <strong id="siteBreadcrumbName">Borrow Pit Harapan Baru</strong>
                        </div>
                    </div>
                    <div class="site-top-actions">
                        <select id="siteLocationSelector" class="site-location-selector" aria-label="Pilih lokasi site untuk melihat properties" onchange="window.openSiteProperties(this.value)">
                            <!-- Populated via JS -->
                        </select>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="window.printSiteProperties()" style="display:inline-flex; align-items:center; gap:6px; font-weight:600;">
                            <i class="fa-solid fa-print"></i> Cetak Ringkasan Site
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="window.exportSiteUnitsCsv()" style="display:inline-flex; align-items:center; gap:6px; font-weight:600;">
                            <i class="fa-solid fa-file-csv"></i> Unduh Data Unit (.CSV)
                        </button>
                    </div>
                </div>

                <!-- Site Identity & Status Hero Card -->
                <div class="site-spec-header-card">
                    <div class="site-header-main-row">
                        <div class="site-identity-block">
                            <div class="site-category-icon-box">
                                <i class="fa-solid fa-industry"></i>
                            </div>
                            <div class="site-title">
                                <h1>
                                    <span id="sitePropTitle">Borrow Pit Harapan Baru</span>
                                    <span id="sitePropStatusPill" class="site-badge-pill active">
                                        <span class="prop-pulse-dot"></span> ACTIVE OPERATIONS
                                    </span>
                                </h1>
                                <div class="site-meta-subtitle">
                                    <span id="sitePropType">Borrow Pit Area</span> &bull; 
                                    Wilayah: <strong id="sitePropRegion" style="color:#0f172a;">Duri, Riau</strong> &bull; 
                                    Radius Geofence: <code id="sitePropRadius">600 Meter</code>
                                </div>
                                <div class="site-pic-row">
                                    <div class="site-pic-item">
                                        <i class="fa-solid fa-user-shield"></i>
                                        <span>Pengawas Site: <strong id="sitePropPic">Joni Septian (Foreman) / Rahmad K (Mekanik)</strong></span>
                                    </div>
                                    <div class="site-pic-item">
                                        <i class="fa-solid fa-walkie-talkie"></i>
                                        <span>Saluran Radio: <strong id="sitePropRadio">CH 14 (156.700 MHz VHF)</strong></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bento Grid Site Vitals (8 KPIs) -->
                    <div class="site-vitals-bento-grid">
                        <div class="site-vital-cell">
                            <div class="vital-label-row">
                                <div class="vital-label"><i class="fa-solid fa-cubes"></i> Total Armada di Site</div>
                            </div>
                            <div class="vital-val" id="siteVitalTotalUnits">18 Unit</div>
                            <div class="vital-sub">Alat Berat &amp; Support</div>
                        </div>
                        <div class="site-vital-cell">
                            <div class="vital-label-row">
                                <div class="vital-label"><i class="fa-solid fa-circle-check" style="color:#16a34a;"></i> Ready / Operating</div>
                            </div>
                            <div class="vital-val" id="siteVitalReadyUnits" style="color:#16a34a;">14 Unit</div>
                            <div class="vital-sub">Siap Operasi / Bekerja</div>
                        </div>
                        <div class="site-vital-cell">
                            <div class="vital-label-row">
                                <div class="vital-label"><i class="fa-solid fa-pause" style="color:#d97706;"></i> Standby</div>
                            </div>
                            <div class="vital-val" id="siteVitalStandbyUnits" style="color:#d97706;">3 Unit</div>
                            <div class="vital-sub">Menunggu Giliran / Antrian</div>
                        </div>
                        <div class="site-vital-cell">
                            <div class="vital-label-row">
                                <div class="vital-label"><i class="fa-solid fa-triangle-exclamation" style="color:#dc2626;"></i> Breakdown / Perbaikan</div>
                            </div>
                            <div class="vital-val" id="siteVitalBreakdownUnits" style="color:#dc2626;">1 Unit</div>
                            <div class="vital-sub">Dalam Penanganan WO</div>
                        </div>
                        <div class="site-vital-cell">
                            <div class="vital-label-row">
                                <div class="vital-label"><i class="fa-solid fa-gauge-high"></i> Rata-rata HM Armada</div>
                            </div>
                            <div class="vital-val" id="siteVitalAvgHm">18.450 HM</div>
                            <div class="vital-sub">SMR Operasi Rata-rata</div>
                        </div>
                        <div class="site-vital-cell">
                            <div class="vital-label-row">
                                <div class="vital-label"><i class="fa-solid fa-heart-pulse"></i> Health Score Site</div>
                            </div>
                            <div class="vital-val" id="siteVitalHealth" style="color:#16a34a;">94%</div>
                            <div class="vital-sub">Kondisi Fisik Prima</div>
                        </div>
                        <div class="site-vital-cell">
                            <div class="vital-label-row">
                                <div class="vital-label"><i class="fa-solid fa-wrench"></i> Work Order Aktif</div>
                            </div>
                            <div class="vital-val" id="siteVitalActiveWo" style="color:#0284c7;">2 Tiket</div>
                            <div class="vital-sub">Tiket Terbuka di Site</div>
                        </div>
                        <div class="site-vital-cell">
                            <div class="vital-label-row">
                                <div class="vital-label"><i class="fa-solid fa-satellite"></i> Akurasi Geofence</div>
                            </div>
                            <div class="vital-val" id="siteVitalGeofenceLock" style="color:#0284c7;">100% Locked</div>
                            <div class="vital-sub">0 Pelanggaran Batas</div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 1: PETA INTERAKTIF SITE & LOKASI SETIAP UNIT -->
                <div class="site-section-heading">
                    <div class="site-section-heading-left">
                        <i class="fa-solid fa-map-location-dot"></i>
                        <span>1. Peta Interaktif Site &amp; Lokasi Unit Armada Tambang</span>
                    </div>
                </div>
                <div class="site-panel-grid-top-map">
                    <!-- Leaflet Map Container -->
                    <div class="site-card">
                        <div class="site-card-header" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <h3><i class="fa-solid fa-map" style="color:#0284c7;"></i> Peta Geofence Site &amp; Sebaran Unit Real-Time</h3>
                                <span style="font-size:0.75rem; color:#64748b;"><i class="fa-solid fa-circle-info"></i> Klik marker untuk rincian</span>
                            </div>
                            <div class="site-map-mode-toggle" id="siteMapModeToggleContainer">
                                <button type="button" class="btn-map-mode active" id="btnSiteMapModeAuto" onclick="window.setSiteMapDisplayMode('auto')" title="Mode Otomatis (Ringkas saat zoom out, detail saat zoom in)"><i class="fa-solid fa-arrows-split-up-and-left"></i> Auto</button>
                                <button type="button" class="btn-map-mode" id="btnSiteMapModeCompact" onclick="window.setSiteMapDisplayMode('compact')" title="Tampilan Ringkas (Logo Alat &amp; Status)"><i class="fa-solid fa-layer-group"></i> Ringkas</button>
                                <button type="button" class="btn-map-mode" id="btnSiteMapModeDetail" onclick="window.setSiteMapDisplayMode('detail')" title="Tampilan Detail (Semua Pin Unit)"><i class="fa-solid fa-location-dot"></i> Detail</button>
                            </div>
                        </div>
                        <div class="site-card-body" style="padding:10px;">
                            <div id="siteLocationMap"></div>
                        </div>
                    </div>

                    <!-- Right Side: Distribusi Armada & Info Geofence -->
                    <div>
                        <!-- Distribusi Berdasarkan Kategori Alat -->
                        <div class="site-card">
                            <div class="site-card-header">
                                <h3><i class="fa-solid fa-chart-pie" style="color:#0284c7;"></i> Komposisi Armada di Site</h3>
                            </div>
                            <div class="site-card-body" style="padding:14px;">
                                <div class="site-cat-dist-list" id="siteCategoryDistributionList">
                                    <!-- Rendered dynamically via JS -->
                                </div>

                                <!-- Geofence Coordinates Hub -->
                                <div class="site-geofence-info-box">
                                    <div class="site-geofence-item">
                                        <span class="site-geofence-item-label">Titik Acuan (Lat, Lng)</span>
                                        <span class="site-geofence-item-val" id="siteGeofenceCenter">1.31245, 101.24512</span>
                                    </div>
                                    <div class="site-geofence-item">
                                        <span class="site-geofence-item-label">Radius Geofence</span>
                                        <span class="site-geofence-item-val" id="siteGeofenceRadiusVal">600 Meter</span>
                                    </div>
                                    <div class="site-geofence-item">
                                        <span class="site-geofence-item-label">Sinyal Telemetri GPS</span>
                                        <span class="site-geofence-item-val" style="color:#16a34a;"><i class="fa-solid fa-signal"></i> 4G / Satelit OK</span>
                                    </div>
                                    <div class="site-geofence-item">
                                        <span class="site-geofence-item-label">Kondisi Lapangan</span>
                                        <span class="site-geofence-item-val" style="color:#0284c7;"><i class="fa-solid fa-cloud-sun"></i> Kering / Aman</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: TABULASI ALAT BERAT & PROPERTIES IDENTIFICATION -->
                <div class="site-section-heading">
                    <div class="site-section-heading-left">
                        <i class="fa-solid fa-table-list"></i>
                        <span>2. Tabulasi Alat Berat &amp; Properties Identification (Armada di Site Ini)</span>
                    </div>
                </div>

                <div class="site-card">
                    <!-- Toolbar & Filter -->
                    <div class="site-table-toolbar">
                        <div class="site-table-search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="siteUnitSearchInput" class="site-table-search-input" placeholder="Cari kode unit, lambung, plat, SN, model..." aria-label="Cari unit di site ini">
                        </div>
                        <div class="site-table-filters">
                            <select id="siteUnitStatusFilter" class="site-filter-select" aria-label="Filter status operasional">
                                <option value="ALL">Semua Status</option>
                                <option value="OPERATING">Operating / Ready</option>
                                <option value="STANDBY">Standby</option>
                                <option value="BREAKDOWN">Breakdown</option>
                                <option value="INSPEKSI">Inspeksi</option>
                            </select>
                            <select id="siteUnitCategoryFilter" class="site-filter-select" aria-label="Filter kategori alat">
                                <option value="ALL">Semua Kategori</option>
                                <option value="Dump Truck">Dump Truck</option>
                                <option value="Excavator">Excavator</option>
                                <option value="Bulldozer">Bulldozer</option>
                                <option value="Motor Grader">Motor Grader</option>
                                <option value="Vibro Compactor">Vibro Compactor</option>
                                <option value="Water Truck">Water Truck</option>
                                <option value="Light Vehicle">Light Vehicle</option>
                            </select>
                            <span id="siteUnitsCountLabel" style="font-size:0.78rem; font-weight:600; color:#64748b; margin-left:8px;">Menghitung unit...</span>
                        </div>
                    </div>

                    <!-- Table Data -->
                    <div class="site-prop-table-responsive">
                        <table class="site-prop-table">
                            <thead>
                                <tr>
                                    <th style="width:40px; text-align:center;">No.</th>
                                    <th>Unit ID / Lambung</th>
                                    <th>Nomor Lambung Fisik</th>
                                    <th>Merek &amp; Model</th>
                                    <th>Kategori Alat</th>
                                    <th>Legal &amp; Serial Number</th>
                                    <th>Jam Kerja (HM)</th>
                                    <th>Status Operasional</th>
                                    <th>Health Score</th>
                                    <th style="text-align:center; min-width:140px;">Aksi Aksesibilitas</th>
                                </tr>
                            </thead>
                            <tbody id="siteUnitsTableBody">
                                <!-- Rendered via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- Modal Override Hak Akses (RBAC Customizer) -->
    <div class="modal-overlay" id="modalEditRbacPermission" style="z-index:999999;">
        <div class="modal-content" style="max-width: 480px; padding: 22px;">
            <div class="modal-header"
                style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:10px; margin-bottom:15px;">
                <h3 style="margin:0; font-size:1.1rem; color:var(--text-dark);"><i class="fa-solid fa-user-pen"
                        style="color:var(--primary);"></i> Override Hak Akses Modul</h3>
                <button onclick="closeModal('modalEditRbacPermission')"
                    style="border:none; background:none; font-size:1.4rem; cursor:pointer;">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editRbacRoleIndex">
                <input type="hidden" id="editRbacModuleIndex">

                <div
                    style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px; margin-bottom:15px;">
                    <div style="font-size:0.85rem; color:var(--text-muted); text-transform:uppercase; font-weight:700;">
                        Target User Role & Modul</div>
                    <div style="font-size:0.95rem; font-weight:700; color:var(--primary);" id="editRbacRoleName">-
                    </div>
                    <div style="font-size:0.85rem; font-weight:600; color:var(--text-dark);" id="editRbacModuleName">-
                    </div>
                </div>

                <div style="margin-bottom:15px;">
                    <label
                        style="display:block; font-size:0.82rem; font-weight:700; color:var(--text-dark); margin-bottom:8px;">
                        Pilih Hak Akses yang Diberikan (RCUAO):
                    </label>

                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <label
                            style="display:flex; align-items:center; gap:8px; cursor:pointer; background:#f8fafc; padding:7px 10px; border-radius:6px; border:1px solid #e2e8f0;">
                            <input type="checkbox" id="chkRbacR" style="width:16px; height:16px;">
                            <span style="font-weight:700; color:#334155;">R - Read / View</span>
                            <small style="color:var(--text-muted);">(Melihat data modul)</small>
                        </label>
                        <label
                            style="display:flex; align-items:center; gap:8px; cursor:pointer; background:#f8fafc; padding:7px 10px; border-radius:6px; border:1px solid #e2e8f0;">
                            <input type="checkbox" id="chkRbacC" style="width:16px; height:16px;">
                            <span style="font-weight:700; color:#334155;">C - Create / Add</span>
                            <small style="color:var(--text-muted);">(Menambah data baru)</small>
                        </label>
                        <label
                            style="display:flex; align-items:center; gap:8px; cursor:pointer; background:#f8fafc; padding:7px 10px; border-radius:6px; border:1px solid #e2e8f0;">
                            <input type="checkbox" id="chkRbacU" style="width:16px; height:16px;">
                            <span style="font-weight:700; color:#334155;">U - Update / Edit</span>
                            <small style="color:var(--text-muted);">(Mengubah data eksisting)</small>
                        </label>
                        <label
                            style="display:flex; align-items:center; gap:8px; cursor:pointer; background:#f8fafc; padding:7px 10px; border-radius:6px; border:1px solid #e2e8f0;">
                            <input type="checkbox" id="chkRbacA" style="width:16px; height:16px;">
                            <span style="font-weight:700; color:#334155;">A - Approve / Sign</span>
                            <small style="color:var(--text-muted);">(Menyetujui dokumen/WO/PO)</small>
                        </label>
                        <label
                            style="display:flex; align-items:center; gap:8px; cursor:pointer; background:#f8fafc; padding:7px 10px; border-radius:6px; border:1px solid #e2e8f0;">
                            <input type="checkbox" id="chkRbacO" style="width:16px; height:16px;">
                            <span style="font-weight:700; color:#334155;">O - Owner / Override</span>
                            <small style="color:var(--text-muted);">(Akses pengubah master sistem)</small>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer"
                style="display:flex; justify-content:flex-end; gap:8px; border-top:1px solid #e2e8f0; padding-top:12px;">
                <button class="btn btn-secondary" onclick="closeModal('modalEditRbacPermission')">Batal</button>
                <button class="btn btn-primary" onclick="saveRbacCellOverride()"><i class="fa-solid fa-floppy-disk"></i>
                    Simpan Hak Akses</button>
            </div>
        </div>
    </div>

    <!-- Modal Manajemen Pengguna per Role -->
    <div class="modal-overlay" id="modalRoleUsers" style="z-index:999998;">
        <div class="modal-content" style="max-width: 840px; width: 92%; padding: 22px; max-height: 90vh; display: flex; flex-direction: column;">
            <div class="modal-header" style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:1px solid #e2e8f0; padding-bottom:12px; margin-bottom:15px;">
                <div>
                    <h3 style="margin:0; font-size:1.15rem; color:var(--text-dark); display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-users-gear" style="color:var(--primary);"></i>
                        <span>Manajemen Pengguna Role: <strong id="mruRoleTitle" style="color:var(--primary);">-</strong></span>
                    </h3>
                    <p style="margin:4px 0 0 0; font-size:0.82rem; color:var(--text-muted);">
                        Daftar akun pengguna aktif dari database MySQL untuk role ini. Anda dapat menambah pengguna, mengganti password, atau menghapus akun.
                    </p>
                </div>
                <button type="button" onclick="closeModal('modalRoleUsers')" style="border:none; background:none; font-size:1.4rem; cursor:pointer; color:var(--text-muted); padding:0 4px;">&times;</button>
            </div>

            <div class="modal-body" style="padding:14px 18px; overflow-y:auto; flex:1; display:flex; flex-direction:column; gap:12px;">
                <!-- Modal Action Bar: Search & Add Button -->
                <div class="mru-action-bar" style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:4px; flex-wrap:wrap;">
                    <div style="position:relative; flex:1; min-width:200px;">
                        <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:0.85rem;"></i>
                        <input type="text" id="mruSearchInput" class="form-control" placeholder="Cari username atau nama lengkap..." 
                               style="padding-left:32px; font-size:0.84rem; height:36px;" oninput="filterRoleUsersTable(this.value)">
                    </div>
                    <button type="button" class="btn btn-primary" style="padding:6px 14px; font-size:0.84rem; display:inline-flex; align-items:center; gap:6px;" onclick="toggleAddUserForm()">
                        <i class="fa-solid fa-user-plus"></i> Tambah User Baru
                    </button>
                </div>

                <!-- Inline Form Tambah User (Collapsible) -->
                <div id="mruAddUserFormContainer" style="display:none; background:#f8fafc; border:1px solid #cbd5e1; border-radius:8px; padding:14px; margin-bottom:4px;">
                    <div style="font-weight:700; font-size:0.88rem; color:var(--text-dark); margin-bottom:10px; display:flex; align-items:center; gap:6px;">
                        <i class="fa-solid fa-user-plus" style="color:var(--primary);"></i> Form Pendaftaran Pengguna Baru
                    </div>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:10px; margin-bottom:12px;">
                        <div>
                            <label style="display:block; font-size:0.75rem; font-weight:700; color:#475569; margin-bottom:4px;">Username *</label>
                            <input type="text" id="mruNewUsername" class="form-control" placeholder="contoh: anton_m" style="font-size:0.82rem; height:34px;">
                            <small style="font-size:0.7rem; color:#64748b;">Huruf kecil/angka/strip (unik)</small>
                        </div>
                        <div>
                            <label style="display:block; font-size:0.75rem; font-weight:700; color:#475569; margin-bottom:4px;">Nama Lengkap *</label>
                            <input type="text" id="mruNewFullName" class="form-control" placeholder="contoh: Anton Maulana" style="font-size:0.82rem; height:34px;">
                        </div>
                        <div>
                            <label style="display:block; font-size:0.75rem; font-weight:700; color:#475569; margin-bottom:4px;">Password Awal *</label>
                            <input type="password" id="mruNewPassword" class="form-control" placeholder="Minimal 6 karakter" style="font-size:0.82rem; height:34px;">
                        </div>
                        <div>
                            <label style="display:block; font-size:0.75rem; font-weight:700; color:#475569; margin-bottom:4px;">Role Pengguna *</label>
                            <select id="mruNewRoleId" class="form-control" style="font-size:0.82rem; height:34px;">
                                <!-- Populated dynamically via JS -->
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-size:0.75rem; font-weight:700; color:#475569; margin-bottom:4px;">Lokasi Penugasan</label>
                            <select id="mruNewLocationId" class="form-control" style="font-size:0.82rem; height:34px;">
                                <option value="">-- Pilih Lokasi Penugasan (Opsional) --</option>
                                <!-- Populated dynamically via JS -->
                            </select>
                            <small style="font-size:0.7rem; color:#64748b;">Site/Yard/Workshop penugasan</small>
                        </div>
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:8px;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAddUserForm(false)">Batal</button>
                        <button type="button" class="btn btn-primary btn-sm" id="btnSubmitNewUser" onclick="submitNewUser()">
                            <i class="fa-solid fa-floppy-disk"></i> Simpan Pengguna
                        </button>
                    </div>
                </div>

                <!-- Table Tabulasi Pengguna -->
                <div class="table-scroll-hint mru-scroll-hint"
                    style="display:flex; align-items:center; justify-content:space-between; margin-bottom:4px; font-size:0.75rem; color:var(--text-muted);">
                    <span><i class="fa-solid fa-arrows-left-right"></i> Geser ke samping untuk melihat seluruh kolom pengguna</span>
                    <span style="font-weight:700; color:var(--primary); font-size:0.75rem;">7 Kolom</span>
                </div>
                <div class="table-responsive mru-table-wrap" style="flex:1; overflow-x:auto; overflow-y:auto; border:1px solid #e2e8f0; border-radius:8px; min-height:220px; max-height:420px; -webkit-overflow-scrolling:touch; width:100%;">
                    <table class="table" style="font-size:0.82rem; margin:0; width:max-content; min-width:780px; white-space:nowrap;" id="mruUsersTable">
                        <thead>
                            <tr style="background:#f1f5f9; color:#334155; position:sticky; top:0; z-index:3;">
                                <th style="width:40px; text-align:center; padding:9px 8px; white-space:nowrap;">#</th>
                                <th style="padding:9px 14px; white-space:nowrap;">Username</th>
                                <th style="padding:9px 14px; white-space:nowrap;">Nama Lengkap</th>
                                <th style="padding:9px 14px; white-space:nowrap;">Role</th>
                                <th style="padding:9px 14px; white-space:nowrap;">Lokasi Penugasan</th>
                                <th style="padding:9px 12px; text-align:center; white-space:nowrap;">Status</th>
                                <th style="padding:9px 14px; text-align:center; width:180px; white-space:nowrap;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="mruUsersTableBody">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer" style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #e2e8f0; padding-top:12px; margin-top:14px;">
                <div style="font-size:0.78rem; color:var(--text-muted);" id="mruTotalCountLabel">
                    Total: 0 user
                </div>
                <button type="button" class="btn btn-secondary" onclick="closeModal('modalRoleUsers')">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Modal Ganti Password -->
    <div class="modal-overlay" id="modalChangePassword" style="z-index:999999;" onclick="if(event.target === this) closeModal('modalChangePassword')">
        <div class="modal-content modal-change-pwd-content" onclick="event.stopPropagation()">
            <div class="mcp-header">
                <div class="mcp-header-left">
                    <div class="mcp-header-badge" id="mcpHeaderBadge">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <div>
                        <h4 class="mcp-header-title" id="mcpModalTitle">Ganti Password Akun</h4>
                        <p class="mcp-header-subtitle" id="mcpModalSubtitle">Perbarui kata sandi untuk menjaga keamanan akun Anda.</p>
                    </div>
                </div>
                <button type="button" class="mcp-btn-close" onclick="closeModal('modalChangePassword')" aria-label="Tutup modal">&times;</button>
            </div>
            <div class="modal-body" style="padding: 0;">
                <input type="hidden" id="mcpUserId">

                <!-- User Account Info Banner -->
                <div class="mcp-account-card">
                    <div class="mcp-account-avatar">
                        <i class="fa-solid fa-user-shield"></i>
                    </div>
                    <div class="mcp-account-details">
                        <div class="mcp-account-name" id="mcpTargetUserLabel">Memuat pengguna...</div>
                        <div class="mcp-account-role" id="mcpTargetUserSub"><i class="fa-solid fa-shield-halved"></i> Sesi Aktif Terverifikasi</div>
                    </div>
                </div>

                <!-- Password Saat Ini (Hanya untuk Ganti Password Sendiri) -->
                <div id="mcpCurrentPasswordWrapper" class="mcp-form-group">
                    <label class="mcp-label">
                        <i class="fa-solid fa-lock" style="color: #64748b; font-size: 0.8rem;"></i>
                        Password Saat Ini <span style="color:#ef4444;">*</span>
                    </label>
                    <div class="mcp-input-wrap">
                        <input type="password" id="mcpCurrentPassword" class="mcp-input" placeholder="Masukkan password saat ini" autocomplete="current-password">
                        <button type="button" class="btn-toggle-pwd" onclick="togglePasswordVisibility('mcpCurrentPassword', this)" title="Lihat/Sembunyikan password" aria-label="Tampilkan password saat ini">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Password Baru -->
                <div class="mcp-form-group">
                    <label class="mcp-label">
                        <i class="fa-solid fa-key" style="color: #64748b; font-size: 0.8rem;"></i>
                        Password Baru <span style="color:#ef4444;">*</span>
                    </label>
                    <div class="mcp-input-wrap">
                        <input type="password" id="mcpNewPassword" class="mcp-input" placeholder="Minimal 6 karakter" autocomplete="new-password">
                        <button type="button" class="btn-toggle-pwd" onclick="togglePasswordVisibility('mcpNewPassword', this)" title="Lihat/Sembunyikan password" aria-label="Tampilkan password baru">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <div class="mcp-hint-text">
                        <i class="fa-solid fa-circle-info"></i> Panjang minimal 6 karakter
                    </div>
                </div>

                <!-- Konfirmasi Password Baru -->
                <div class="mcp-form-group" style="margin-bottom: 8px;">
                    <label class="mcp-label">
                        <i class="fa-solid fa-check-double" style="color: #64748b; font-size: 0.8rem;"></i>
                        Konfirmasi Password Baru <span style="color:#ef4444;">*</span>
                    </label>
                    <div class="mcp-input-wrap">
                        <input type="password" id="mcpConfirmPassword" class="mcp-input" placeholder="Ketik ulang password baru" autocomplete="new-password">
                        <button type="button" class="btn-toggle-pwd" onclick="togglePasswordVisibility('mcpConfirmPassword', this)" title="Lihat/Sembunyikan password" aria-label="Tampilkan konfirmasi password">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Alert Box -->
                <div id="mcpAlertBox" style="display:none; padding:10px 14px; border-radius:8px; font-size:0.83rem; margin-top:12px; margin-bottom: 4px;"></div>
            </div>

            <!-- Footer -->
            <div class="mcp-footer">
                <button type="button" class="mcp-btn-cancel" onclick="closeModal('modalChangePassword')">Batal</button>
                <button type="button" class="mcp-btn-submit" id="btnSubmitChangePassword" onclick="submitChangePassword()">
                    <i class="fa-solid fa-check"></i> Simpan Password
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Service Berkala PM -->
    <div class="modal-overlay" id="modalNewPM">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2>Rencana Service Berkala (PM) Baru</h2>
                <button onclick="closeModal('modalNewPM')"
                    style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <div class="modal-body form-group">
                <label>Pilih Unit Alat Berat</label>
                <select id="npm-id" class="form-control" onchange="autoFillPmParts()">
                    <!-- Populated via JS -->
                </select>
                <label>Jadwal Service PM</label>
                <input type="text" id="npm-schedule" class="form-control"
                    placeholder="Contoh: PM 250 Hours, PM 500 Hours">

                <label>Rekomendasi Parts & Consumable (Auto-fill by Unit Type)</label>
                <textarea id="npm-parts" rows="2" class="form-control" readonly
                    style="background:#f1f5f9; cursor:not-allowed;"
                    title="Sesuai master data spreadsheet Service Berkala"></textarea>

                <label>Estimasi Budget Rencana (Rp)</label>
                <input type="number" id="npm-cost" class="form-control" readonly
                    style="background:#f1f5f9; cursor:not-allowed;"
                    title="Sesuai master data spreadsheet Service Berkala">

                <label>Lokasi Pelaksanaan (Site)</label>
                <input type="text" id="npm-loc" class="form-control" placeholder="Contoh: Pit, Workshop Duri">
            </div>
            <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:8px;">
                <button class="btn btn-secondary" onclick="closeModal('modalNewPM')">Batal</button>
                <button class="btn btn-primary" onclick="submitNewPM()">Simpan Rencana PM</button>
            </div>
        </div>
    </div>

    <!-- Modal Detail 360 -->
    <div class="modal-overlay" id="assetModal">
        <div class="modal-content" style="max-width: 850px;">
            <div class="modal-header" style="display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button id="btnBackAssetModal" onclick="backToStatusDetailModal()"
                        style="border: 1px solid var(--border); background: #f8f9fa; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: 600; display: none; align-items: center; gap: 5px;"
                        title="Kembali ke Daftar Tabulasi Unit">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </button>
                    <h2 style="margin: 0; font-size: 1.2rem;">Detail 360&deg; Unit: <span id="modalAssetId"></span></h2>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" class="btn btn-sm btn-primary" onclick="closeModal('assetModal'); window.openUnitProperties(document.getElementById('modalAssetId').textContent);" style="display:inline-flex; align-items:center; gap:6px; font-size:0.8rem; font-weight:600;">
                        <i class="fa-solid fa-sliders"></i> Buka Properties Unit
                    </button>
                    <button onclick="closeModal('assetModal')"
                        style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
                </div>
            </div>
            <div class="modal-body">
                <div class="asset-tabs" id="assetModalTabs">
                    <div class="asset-tab active" onclick="window.switchAssetModalTab('ringkasan', this)">Ringkasan
                    </div>
                    <div class="asset-tab" onclick="window.switchAssetModalTab('lokasi', this)">Lokasi</div>
                    <div class="asset-tab" onclick="window.switchAssetModalTab('wo', this)">Breakdown & WO</div>
                    <div class="asset-tab" onclick="window.switchAssetModalTab('p2h', this)">Inspeksi & P2H</div>
                </div>

                <!-- TAB 1: RINGKASAN & GALERI 3 SUDUT PANDANG -->
                <div id="assetTabSecRingkasan" class="asset-tab-sec">
                    <div class="asset-360-grid">
                        <div>
                            <div
                                style="background:#f8f9fa; padding:20px; border-radius:8px; border:1px solid var(--border); margin-bottom:15px;">
                                <p style="color:var(--text-muted); font-size:0.85rem;">Status Efektif</p>
                                <h3 id="modalStatus" style="margin-bottom:4px;">-</h3>
                                <p id="modalStatusSource" class="status-source-line" style="margin-bottom:15px;">-</p>
                                <p style="color:var(--text-muted); font-size:0.85rem;">Kategori Alat</p>
                                <h4 id="modalKategori" style="margin-bottom:15px;">-</h4>
                                <p style="color:var(--text-muted); font-size:0.85rem;">Lokasi Terakhir</p>
                                <h4 id="modalLokasi">-</h4>
                            </div>
                            <button class="btn btn-primary" style="width:100%; margin-bottom:10px;"
                                onclick="triggerUpdateStatus()">Update Status</button>
                            <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:8px;">
                                <button class="btn" style="background:#f59e0b; color:#fff;"
                                    onclick="openIntegratedP2H(document.getElementById('modalAssetId').textContent, 'history')"><i
                                        class="fa-solid fa-clipboard-check"></i> P2H</button>
                                <button class="btn" style="background:#0284c7; color:#fff;"
                                    onclick="ConditionMonitoring.openForAsset(document.getElementById('modalAssetId').textContent)"><i
                                        class="fa-solid fa-stethoscope"></i> Kondisi</button>
                                <button class="btn" style="background:#dc3545; color:#fff;"
                                    onclick="openIntegratedWO(document.getElementById('modalAssetId').textContent)"><i
                                        class="fa-solid fa-wrench"></i> Work Order</button>
                            </div>
                        </div>
                        <div>
                            <h3 style="margin-bottom:10px;">Spesifikasi Unit</h3>
                            <table style="width:100%; border:1px solid var(--border); font-size:0.9rem;">
                                <tr>
                                    <td style="width:40%; font-weight:bold; background:#f8f9fa;">Merek / Model</td>
                                    <td id="modalModelSpec">Komatsu / Heavy Equipment</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold; background:#f8f9fa;">Tahun Manufaktur</td>
                                    <td id="modalYearSpec">2021</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold; background:#f8f9fa;">Nomor Seri (SN)</td>
                                    <td id="modalSnSpec">-</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold; background:#f8f9fa;">Nomor Pelat</td>
                                    <td id="modalPlateSpec">-</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold; background:#f8f9fa;">Status Kelengkapan</td>
                                    <td id="modalCompletenessSpec">-</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold; background:#f8f9fa;">Ownership</td>
                                    <td>Milik Sendiri (PT BRA)</td>
                                </tr>
                                <tr>
                                    <td style="font-weight:bold; background:#f8f9fa;">HM / KM Terakhir</td>
                                    <td id="modalHmSpec">8.450 Jam</td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Placeholder View Tampak Depan, Belakang & Samping -->
                    <h3 style="margin-top:20px; margin-bottom:10px; font-size:1.05rem;"><i
                            class="fa-solid fa-camera"></i> Visualisasi 360&deg; Sudut Pandang Unit</h3>
                    <div class="asset-view-gallery">
                        <!-- Tampak Depan -->
                        <div class="asset-view-card">
                            <div class="asset-view-header">
                                <span class="asset-angle-badge">TAMPAK DEPAN</span>
                                <span style="font-size:0.85rem; color:var(--text-muted);"><i
                                        class="fa-solid fa-eye"></i> Front View</span>
                            </div>
                            <div class="asset-view-img-box">
                                <div class="asset-view-svg-demo">
                                    <i class="fa-solid fa-truck-front"></i>
                                    <span style="font-size:0.85rem; font-weight:bold;">FRONT VIEW</span>
                                </div>
                            </div>
                            <div class="asset-view-spec-tag">Kabin, Lampu Utama & Attachment Depan</div>
                        </div>

                        <!-- Tampak Belakang -->
                        <div class="asset-view-card">
                            <div class="asset-view-header">
                                <span class="asset-angle-badge" style="background:#059669;">TAMPAK BELAKANG</span>
                                <span style="font-size:0.85rem; color:var(--text-muted);"><i
                                        class="fa-solid fa-eye"></i> Rear View</span>
                            </div>
                            <div class="asset-view-img-box">
                                <div class="asset-view-svg-demo">
                                    <i class="fa-solid fa-truck-monster" style="color:#059669;"></i>
                                    <span style="font-size:0.85rem; font-weight:bold; color:#059669;">REAR VIEW</span>
                                </div>
                            </div>
                            <div class="asset-view-spec-tag">Counterweight, Engine Hood & Exhaust</div>
                        </div>

                        <!-- Tampak Samping -->
                        <div class="asset-view-card">
                            <div class="asset-view-header">
                                <span class="asset-angle-badge" style="background:#d97706;">TAMPAK SAMPING</span>
                                <span style="font-size:0.85rem; color:var(--text-muted);"><i
                                        class="fa-solid fa-eye"></i> Side View</span>
                            </div>
                            <div class="asset-view-img-box">
                                <div class="asset-view-svg-demo">
                                    <i class="fa-solid fa-truck-pickup" style="color:#d97706;"></i>
                                    <span style="font-size:0.85rem; font-weight:bold; color:#d97706;">SIDE VIEW</span>
                                </div>
                            </div>
                            <div class="asset-view-spec-tag">Profile Undercarriage Track / Roda & Boom</div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: LOKASI (LEAFLET MAP & TIMELINE REKAP) -->
                <div id="assetTabSecLokasi" class="asset-tab-sec" style="display:none;">
                    <div
                        style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
                        <h3 style="margin:0; font-size:1.05rem;"><i class="fa-solid fa-location-dot"></i> Peta Posisi
                            GPS Terkini Unit</h3>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span id="modalGpsCoord"
                                style="font-size:0.8rem; background:#e0f2fe; color:#0369a1; padding:4px 10px; border-radius:4px; font-weight:bold;">GPS:
                                1.4851, 101.3524</span>
                            <a id="btnGoogleMaps" href="https://maps.google.com/?q=1.4851,101.3524" target="_blank"
                                rel="noopener noreferrer" class="btn-gmaps" title="Buka Lokasi Unit di Google Maps">
                                <i class="fa-solid fa-map-location-dot" style="color: #ea4335;"></i>
                                <span>Google Maps</span>
                                <i class="fa-solid fa-arrow-up-right-from-square"
                                    style="font-size:0.8rem; color: #6c757d;"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Individual Unit Leaflet Map -->
                    <div id="modalUnitMap"></div>

                    <h4 style="margin-top:15px; margin-bottom:10px;"><i class="fa-solid fa-route"></i> Riwayat
                        Perpindahan Lokasi & BAST</h4>
                    <div class="movement-timeline" id="movementTimeline">
                        <div class="movement-timeline-item latest">
                            <div class="movement-timeline-title">Memuat riwayat perpindahan...</div>
                            <div class="movement-timeline-meta">Data bersumber dari database Laragon.</div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: BREAKDOWN & WORK ORDER -->
                <div id="assetTabSecWo" class="asset-tab-sec" style="display:none;">
                    <h3 style="margin-bottom:12px; font-size:1.05rem;"><i class="fa-solid fa-wrench"></i> Riwayat Work
                        Order (WO) Unit</h3>
                    <div class="table-responsive">
                        <table style="width:100%; font-size:0.85rem;">
                            <thead>
                                <tr>
                                    <th>WO ID</th>
                                    <th>Tanggal Down</th>
                                    <th>Trouble / Issue Diagnosis</th>
                                    <th>Downtime</th>
                                    <th>Prioritas</th>
                                    <th>Status WO</th>
                                </tr>
                            </thead>
                            <tbody id="tbModalUnitWoBody">
                                <!-- Rendered via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB 4: INSPEKSI & P2H -->
                <div id="assetTabSecP2h" class="asset-tab-sec" style="display:none;">
                    <h3 style="margin-bottom:12px; font-size:1.05rem;"><i class="fa-solid fa-clipboard-check"></i>
                        Riwayat Inspeksi & P2H Unit</h3>
                    <div class="table-responsive">
                        <table style="width:100%; font-size:0.85rem;">
                            <thead>
                                <tr>
                                    <th>Tiket P2H</th>
                                    <th>Tanggal / Waktu</th>
                                    <th>Operator / NRP</th>
                                    <th>Reading HM</th>
                                    <th>Status Kelayakan</th>
                                    <th>Catatan Temuan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="tbModalUnitP2hBody">
                                <!-- Rendered via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal FRM-BBM-01 Input Pengisian BBM -->
    <div class="modal-overlay" id="modalFuelInput">
        <div class="modal-content" style="max-width: 720px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-gas-pump" style="color:var(--primary);"></i> Form Pengisian BBM Unit
                    (FRM-BBM-01)</h2>
                <button onclick="closeModal('modalFuelInput')"
                    style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <div class="modal-body">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>No. Voucher / Transaksi</label>
                        <input type="text" id="frmFuelVoucher" class="form-control" readonly
                            style="background:#e9ecef; font-weight:bold;">
                    </div>
                    <div class="form-group">
                        <label>Tanggal & Jam Pengisian</label>
                        <input type="datetime-local" id="frmFuelDatetime" class="form-control">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Pilih Unit ID *</label>
                        <select id="frmFuelUnitId" class="form-control" onchange="onFuelUnitSelectChange(this.value)">
                            <!-- Populated via JS -->
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Lokasi Operasional Site *</label>
                        <select id="frmFuelLocation" class="form-control">
                            <option value="Yard KM 12 Duri">Yard KM 12 Duri</option>
                            <option value="Pit">Pit (Harapan Baru)</option>
                            <option value="Borrow Pit">Borrow Pit</option>
                            <option value="Yard Prabumulih">Yard Prabumulih</option>
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Sumber / Tangki BBM *</label>
                        <select id="frmFuelSource" class="form-control">
                            <option value="Fuel Tank Yard Duri">Fuel Tank Yard Duri (25.000 L)</option>
                            <option value="Fuel Truck 01 Site Alpha">Fuel Truck 01 Site Alpha (8.000 L)</option>
                            <option value="Fuel Tank Borrow Pit">Fuel Tank Borrow Pit (15.000 L)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Fuelman / Petugas</label>
                        <input type="text" id="frmFuelman" class="form-control" value="Fuelman A">
                    </div>
                    <div class="form-group">
                        <label>Operator / Driver</label>
                        <input type="text" id="frmFuelOperator" class="form-control" value="Operator Senior B">
                    </div>
                </div>

                <div
                    style="background:#f8f9fa; padding:15px; border-radius:8px; border:1px solid var(--border); margin-bottom:15px;">
                    <h4 style="margin:0 0 10px 0; font-size:0.95rem; color:var(--dark);"><i
                            class="fa-solid fa-gauge-high"></i> Reading Meter & Kalkulasi Efisiensi</h4>
                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label id="lblPrevMeter">HM/KM Sebelumnya</label>
                            <input type="number" id="frmFuelMeterPrev" class="form-control" style="background:#e9ecef;"
                                readonly oninput="calcFuelMetrics()">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label id="lblCurrMeter">HM/KM Sekarang *</label>
                            <input type="number" id="frmFuelMeterCurr" class="form-control" placeholder="Masukkan meter"
                                oninput="calcFuelMetrics()">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Jumlah Liter BBM *</label>
                            <input type="number" id="frmFuelLiter" class="form-control" placeholder="Jumlah Liter"
                                oninput="calcFuelMetrics()">
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-top: 10px;">
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Aktual Konsumsi</label>
                            <input type="text" id="frmFuelActualRate" class="form-control" readonly
                                style="font-weight:bold;">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Baseline & Deviasi</label>
                            <input type="text" id="frmFuelDeviation" class="form-control" readonly
                                style="font-weight:bold;">
                        </div>
                        <div class="form-group" style="margin-bottom:0;">
                            <label>Estimasi Biaya (Rp)</label>
                            <input type="text" id="frmFuelCost" class="form-control" readonly
                                style="font-weight:bold; color:var(--primary);">
                        </div>
                    </div>

                    <!-- Live Anomaly Alert Box -->
                    <div id="frmFuelAnomalyBox"
                        style="display:none; margin-top:12px; padding:10px 12px; background:#fff3bf; border-left:4px solid #f59f00; border-radius:4px; font-size:0.85rem;">
                        <i class="fa-solid fa-triangle-exclamation" style="color:#f59f00;"></i> <strong
                            id="frmFuelAnomalyText">Peringatan: Konsumsi Melebihi Standar Baseline!</strong>
                    </div>
                </div>

                <div class="form-group">
                    <label>Catatan / Alasan (Wajib jika Anomali atau Meter diganti)</label>
                    <textarea id="frmFuelNotes" class="form-control" rows="2"
                        placeholder="Tuliskan catatan pengisian..."></textarea>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:15px;">
                    <button class="btn" style="background:#e9ecef;"
                        onclick="closeModal('modalFuelInput')">Batal</button>
                    <button class="btn btn-primary" onclick="submitFuelForm()"><i class="fa-solid fa-check"></i> Simpan
                        Transaksi (Diajukan)</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal FRM-STK-02 Sounding Stok Tangki -->
    <div class="modal-overlay" id="modalStockSounding">
        <div class="modal-content" style="max-width: 550px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-droplet" style="color:var(--success);"></i> Rekonsiliasi Sounding Stok Tangki
                    (FRM-STK-02)</h2>
                <button onclick="closeModal('modalStockSounding')"
                    style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Pilih Tangki Storage Site *</label>
                    <select id="stkTankId" class="form-control" onchange="onTankSelectChange(this.value)">
                        <option value="TANK-01">Fuel Tank Yard Duri (25.000 L)</option>
                        <option value="FT-01">Fuel Truck 01 Site Alpha (8.000 L)</option>
                        <option value="TANK-02">Fuel Tank Borrow Pit (15.000 L)</option>
                    </select>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="form-group">
                        <label>Saldo Sistem (Liter)</label>
                        <input type="text" id="stkSystemBalance" class="form-control" readonly
                            style="background:#e9ecef; font-weight:bold;">
                    </div>
                    <div class="form-group">
                        <label>Sounding Fisik (Liter) *</label>
                        <input type="number" id="stkPhysicalSounding" class="form-control"
                            placeholder="Input hasil dipstick" oninput="calcTankVariance()">
                    </div>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div class="form-group">
                        <label>Selisih Sounding (Gain/Loss)</label>
                        <input type="text" id="stkVariance" class="form-control" readonly style="font-weight:bold;">
                    </div>
                    <div class="form-group">
                        <label>Petugas Logistik</label>
                        <input type="text" id="stkOfficer" class="form-control" value="Logistik Site Alpha">
                    </div>
                </div>
                <div class="form-group">
                    <label>Catatan Rekonsiliasi / Alasan Selisih</label>
                    <textarea id="stkNotes" class="form-control" rows="2"
                        placeholder="Catatan evaporasi/tumpahan/penyesuaian..."></textarea>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:15px;">
                    <button class="btn" style="background:#e9ecef;"
                        onclick="closeModal('modalStockSounding')">Batal</button>
                    <button class="btn btn-success" onclick="submitTankSounding()"><i class="fa-solid fa-check"></i>
                        Simpan Sounding Rekonsiliasi</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalMasterAssetPrint">
        <div class="modal-content master-asset-print-dialog">
            <div class="modal-header">
                <h2><i class="fa-solid fa-print"></i> Cetak Laporan Master Asset</h2>
                <button type="button" class="asset-registration-close" onclick="closeModal('modalMasterAssetPrint')" aria-label="Tutup">&times;</button>
            </div>
            <div class="modal-body">
                <p class="master-asset-print-description">Laporan menggunakan format A4 landscape. Serial Number dan pelat kendaraan dicetak pada kolom terpisah, termasuk usia setiap unit.</p>
                <label class="master-asset-print-option"><input type="radio" name="masterAssetPrintScope" value="all" checked onchange="window.updateMasterAssetPrintFilters()"><span><strong>Seluruh Master Asset</strong><small id="masterAssetPrintAllCount">Semua unit aktif</small></span></label>
                <label class="master-asset-print-option"><input type="radio" name="masterAssetPrintScope" value="filtered" onchange="window.updateMasterAssetPrintFilters()"><span><strong>Hasil filter saat ini</strong><small id="masterAssetPrintFilteredCount">Pilih filter laporan</small></span></label>
                <section class="master-asset-print-filters" id="masterAssetPrintFilters" aria-label="Pilihan filter laporan" hidden>
                    <div class="master-asset-print-filter-heading">
                        <strong>Filter prioritas</strong>
                        <small>Centang filter yang ingin digunakan, lalu pilih nilainya.</small>
                    </div>
                    <div class="master-asset-print-filter-grid">
                        <label class="master-asset-print-filter-row">
                            <input type="checkbox" id="masterAssetPrintUseCategory" onchange="window.updateMasterAssetPrintFilters()">
                            <span>Kategori</span>
                            <select id="masterAssetPrintCategory" class="form-control" aria-label="Pilih kategori laporan" onchange="window.updateMasterAssetPrintFilters()" disabled><option value="">Pilih kategori</option></select>
                        </label>
                        <label class="master-asset-print-filter-row">
                            <input type="checkbox" id="masterAssetPrintUseStatus" onchange="window.updateMasterAssetPrintFilters()">
                            <span>Status</span>
                            <select id="masterAssetPrintStatus" class="form-control" aria-label="Pilih status laporan" onchange="window.updateMasterAssetPrintFilters()" disabled><option value="">Pilih status</option></select>
                        </label>
                        <label class="master-asset-print-filter-row">
                            <input type="checkbox" id="masterAssetPrintUseSite" onchange="window.updateMasterAssetPrintFilters()">
                            <span>Site</span>
                            <select id="masterAssetPrintSite" class="form-control" aria-label="Pilih site laporan" onchange="window.updateMasterAssetPrintFilters()" disabled><option value="">Pilih site</option></select>
                        </label>
                    </div>
                    <p class="master-asset-print-filter-note" id="masterAssetPrintFilterNote">Pilih minimal satu filter untuk melanjutkan.</p>
                </section>
                <div class="asset-registration-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalMasterAssetPrint')">Batal</button>
                    <button type="button" class="btn btn-primary" id="masterAssetPrintSubmit" onclick="window.printMasterAssetReport()"><i class="fa-solid fa-print"></i> Buka Pratinjau Cetak</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="modalAssetLegalDocuments">
        <div class="modal-content asset-legal-documents-modal">
            <div class="modal-header">
                <div><h2><i class="fa-solid fa-folder-open"></i> Legalitas & Dokumentasi</h2><p id="assetLegalDocumentsSubtitle">Master Asset</p></div>
                <button type="button" class="asset-registration-close" onclick="closeModal('modalAssetLegalDocuments')" aria-label="Tutup">&times;</button>
            </div>
            <div class="modal-body" id="assetLegalDocumentsBody">
                <div class="asset-document-loading"><i class="fa-solid fa-spinner fa-spin"></i> Memuat data legalitas dan dokumentasi...</div>
            </div>
        </div>
    </div>

    <!-- Modal Tambah Aset -->
    <div class="modal-overlay asset-registration-overlay" id="modalNewAsset">
        <div class="modal-content asset-registration-modal">
            <div class="modal-header">
                <div>
                    <span class="asset-registration-eyebrow">Master Asset</span>
                    <h2><i class="fa-solid fa-circle-plus text-primary"></i> Registrasi Unit Aset Baru</h2>
                    <p>Catat identitas, penempatan, dan dokumen unit dalam satu formulir.</p>
                </div>
                <button type="button" class="asset-registration-close" onclick="closeModal('modalNewAsset')"
                    aria-label="Tutup formulir registrasi">&times;</button>
            </div>
            <div class="modal-body asset-registration-body">
                <section class="asset-registration-section asset-registration-type-section" aria-labelledby="newAssetTypeTitle">
                    <div class="asset-registration-section-heading">
                        <div>
                            <span class="asset-registration-step">01</span>
                            <h3 id="newAssetTypeTitle">Pilih tipe aset</h3>
                        </div>
                        <span class="asset-registration-auto-id"><i class="fa-solid fa-wand-magic-sparkles"></i> ID unit dibuat otomatis</span>
                    </div>
                    <label class="asset-registration-field asset-registration-type-field">
                        <span>Tipe aset <b>*</b></span>
                        <select id="na-cat" class="form-control" onchange="window.updateNewAssetFormLayout()" required>
                            <option value="Excavator">Excavator</option>
                            <option value="Bulldozer">Bulldozer</option>
                            <option value="Dump Truck">Dump Truck</option>
                            <option value="Motor Grader">Motor Grader</option>
                            <option value="Vibro Compactor">Vibro Compactor</option>
                            <option value="Water Truck">Water Truck</option>
                            <option value="Prime Mover">Prime Mover</option>
                            <option value="Light Vehicle">Light Vehicle</option>
                            <option value="Reclaimer Spreader">Reclaimer Spreader</option>
                            <option value="Trado">Trado</option>
                            <option value="Other">Other</option>
                        </select>
                    </label>
                </section>

                <section class="asset-registration-section" aria-labelledby="newAssetDataTitle">
                    <div class="asset-registration-section-heading">
                        <div>
                            <span class="asset-registration-step">02</span>
                            <h3 id="newAssetDataTitle">Data utama unit</h3>
                        </div>
                        <span id="na-form-mode" class="asset-registration-mode">Formulir umum</span>
                    </div>

                    <div class="asset-registration-grid">
                        <label class="asset-registration-field asset-registration-field-wide">
                            <span>Nama aset <b>*</b></span>
                            <input type="text" id="na-model" placeholder="Contoh: Komatsu PC200-10M0 CE" class="form-control" required>
                        </label>
                        <label class="asset-registration-field">
                            <span>Kode Unit</span>
                            <input type="text" id="na-asset-code" placeholder="Contoh: DT-00152" class="form-control" maxlength="50">
                        </label>
                        <label class="asset-registration-field">
                            <span>Status Warranty</span>
                            <select id="na-warranty" class="form-control">
                                <option value="Belum diketahui">Belum diketahui</option>
                                <option value="Aktif">Aktif</option>
                                <option value="Tidak ada warranty">Tidak ada warranty</option>
                                <option value="Kedaluwarsa">Kedaluwarsa</option>
                            </select>
                        </label>
                        <label class="asset-registration-field">
                            <span>Kapasitas</span>
                            <input type="text" id="na-capacity" placeholder="Contoh: 20 ton, 1,2 m³, atau 10.000 liter" class="form-control" maxlength="100">
                        </label>
                        <label class="asset-registration-field" data-form-variant="general">
                            <span>Dealer</span>
                            <input type="text" id="na-dealer" placeholder="Nama dealer atau cabang" class="form-control">
                        </label>
                        <label class="asset-registration-field">
                            <span>Tahun <b>*</b></span>
                            <input type="number" id="na-year" placeholder="2026" min="1970" max="2099" class="form-control" oninput="window.updateNewAssetAge()" required>
                        </label>
                        <label class="asset-registration-field" data-form-variant="general">
                            <span>Serial number</span>
                            <input type="text" id="na-sn" placeholder="Nomor seri pabrikan" class="form-control">
                        </label>
                        <label class="asset-registration-field" data-form-variant="dump">
                            <span>Serial number / pelat kendaraan</span>
                            <input type="text" id="na-dump-identity" placeholder="Nomor seri atau pelat kendaraan" class="form-control">
                        </label>
                        <label class="asset-registration-field">
                            <span id="na-hull-label">Nomor lambung aktual</span>
                            <input type="text" id="na-hull" placeholder="Kode fisik yang terpasang pada unit" class="form-control" maxlength="100">
                        </label>
                        <label class="asset-registration-field">
                            <span>Project</span>
                        <select id="na-loc" class="form-control">
                                <option value="">Memuat daftar project...</option>
                            </select>
                        </label>
                        <label class="asset-registration-field">
                            <span id="na-position-label">Lokasi</span>
                            <input type="text" id="na-position" placeholder="Lokasi atau posisi aktual unit" class="form-control">
                        </label>
                        <label class="asset-registration-field">
                            <span>Status</span>
                            <select id="na-status" class="form-control">
                                <option value="READY">READY - Siap Operasi</option>
                                <option value="OPERATING">OPERATING - Sedang Operasi</option>
                                <option value="STANDBY">STANDBY - Cadangan</option>
                                <option value="INSPEKSI">INSPEKSI - Pengecekan</option>
                                <option value="BREAKDOWN">BREAKDOWN - Perbaikan</option>
                            </select>
                        </label>
                        <label class="asset-registration-field" data-form-variant="general">
                            <span>Lokasi invoice</span>
                            <input type="text" id="na-invoice-location" placeholder="Tempat penyimpanan invoice" class="form-control">
                        </label>
                        <label class="asset-registration-field" data-form-variant="dump">
                            <span>Lokasi STNK</span>
                            <input type="text" id="na-stnk-location" placeholder="Tempat penyimpanan STNK" class="form-control">
                        </label>
                        <label class="asset-registration-field" data-form-variant="dump">
                            <span>Lokasi BPKB</span>
                            <input type="text" id="na-bpkb-location" placeholder="Tempat penyimpanan BPKB" class="form-control">
                        </label>
                        <label class="asset-registration-field" data-form-variant="dump">
                            <span>Nomor rangka</span>
                            <input type="text" id="na-chassis" placeholder="Nomor rangka kendaraan" class="form-control">
                        </label>
                        <label class="asset-registration-field" data-form-variant="dump">
                            <span>Tipe</span>
                            <input type="text" id="na-vehicle-type" placeholder="Contoh: FM280JD (6x4)" class="form-control">
                        </label>
                        <label class="asset-registration-field">
                            <span>Usia</span>
                            <div class="asset-registration-age-control">
                                <input type="number" id="na-age" min="0" max="100" placeholder="Otomatis" class="form-control" readonly>
                                <span>tahun</span>
                            </div>
                        </label>
                    </div>
                </section>

                <section class="asset-registration-section" aria-labelledby="newAssetPmTitle">
                    <div class="asset-registration-section-heading">
                        <div>
                            <span class="asset-registration-step">03</span>
                            <h3 id="newAssetPmTitle">Konfigurasi Preventive Maintenance</h3>
                        </div>
                        <span class="asset-registration-optional">Opsional · isi satu paket</span>
                    </div>
                    <p class="asset-registration-section-note">Data ini menjadi satu-satunya sumber jadwal pada menu Preventive Maintenance. Jika mulai diisi, meter saat ini, tanggal dan meter servis terakhir, interval, jenis meter, serta pemakaian harian wajib lengkap.</p>
                    <div class="asset-registration-grid">
                        <label class="asset-registration-field"><span>Meter unit saat ini</span><input id="na-current-meter" type="number" min="0" step="0.1" class="form-control" placeholder="Contoh: 1250"></label>
                        <label class="asset-registration-field"><span>Tanggal servis terakhir</span><input id="na-last-service-date" type="date" class="form-control"></label>
                        <label class="asset-registration-field"><span>Meter servis terakhir</span><input id="na-last-service-hm" type="number" min="0" step="0.1" class="form-control" placeholder="Contoh: 1000"></label>
                        <label class="asset-registration-field"><span>Interval servis</span><input id="na-service-interval" type="number" min="1" step="1" class="form-control" placeholder="Contoh: 250"></label>
                        <label class="asset-registration-field"><span>Jenis meter</span><select id="na-service-unit" class="form-control"><option value="HM">HM</option><option value="KM">KM</option></select></label>
                        <label class="asset-registration-field"><span>Pemakaian rata-rata per hari</span><input id="na-daily-rate" type="number" min="0.01" step="0.01" class="form-control" placeholder="Contoh: 10"></label>
                        <label class="asset-registration-field asset-registration-field-wide"><span>Riwayat servis sebelumnya</span><textarea id="na-service-history" rows="3" class="form-control" placeholder="Satu baris: YYYY-MM-DD,meter,jenis&#10;Contoh: 2026-01-10,750,PM 250"></textarea><small>Maksimal 30 baris; data terbaru/servis terakhir tetap diisi pada kolom di atas.</small></label>
                    </div>
                </section>

                <section class="asset-registration-section" aria-labelledby="newAssetLegalTitle">
                    <div class="asset-registration-section-heading">
                        <div>
                            <span class="asset-registration-step">04</span>
                            <h3 id="newAssetLegalTitle">Legalitas dan Dokumentasi</h3>
                        </div>
                        <span class="asset-registration-optional">Opsional</span>
                    </div>
                    <div class="asset-registration-legal-grid">
                        <label class="asset-registration-field"><span>STNK berlaku sampai</span><input id="na-stnk" type="date" class="form-control"></label>
                        <label class="asset-registration-field"><span>KIR berlaku sampai</span><input id="na-kir" type="date" class="form-control"></label>
                        <label class="asset-registration-field"><span>SILO berlaku sampai</span><input id="na-silo" type="date" class="form-control"></label>
                    </div>
                    <div class="asset-registration-upload-grid">
                        <label class="asset-registration-upload">
                            <span><i class="fa-regular fa-image"></i> Foto unit</span>
                            <small>JPEG, PNG, atau WebP · maks. 5 MB per foto</small>
                            <input id="na-photos" type="file" accept="image/jpeg,image/png,image/webp" multiple class="form-control" onchange="window.previewAssetPhotos(this.files)">
                        </label>
                        <label class="asset-registration-upload">
                            <span><i class="fa-regular fa-file-pdf"></i> PDF JO servis</span>
                            <small>Dokumen PDF · maks. 10 MB</small>
                            <input id="na-service-pdf" type="file" accept="application/pdf" class="form-control">
                        </label>
                    </div>
                    <div id="na-photo-preview" class="asset-registration-photo-preview"></div>
                </section>

                <div id="na-error-alert" class="asset-registration-error" role="alert"></div>

                <div class="asset-registration-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalNewAsset')">Batal</button>
                    <button type="button" class="btn btn-primary" id="btnSubmitNewAsset" onclick="submitNewAsset()"><i class="fa-solid fa-floppy-disk"></i> Simpan unit aset</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Edit Aset -->
    <div class="modal-overlay asset-registration-overlay" id="modalEditAsset">
        <div class="modal-content asset-registration-modal">
            <div class="modal-header">
                <div>
                    <span class="asset-registration-eyebrow">Master Asset</span>
                    <h2><i class="fa-solid fa-pen-to-square text-primary"></i> Edit Data Unit: <span id="ea-id-label"></span></h2>
                    <p>Perbarui identitas, penempatan, perawatan, dan legalitas unit.</p>
                </div>
                <button type="button" class="asset-registration-close" onclick="closeModal('modalEditAsset')"
                    aria-label="Tutup formulir edit">&times;</button>
            </div>
            <div class="modal-body asset-registration-body">
                <input type="hidden" id="ea-id">

                <section class="asset-registration-section asset-registration-type-section" aria-labelledby="editAssetTypeTitle">
                    <div class="asset-registration-section-heading">
                        <div>
                            <span class="asset-registration-step">01</span>
                            <h3 id="editAssetTypeTitle">Tipe aset</h3>
                        </div>
                        <span class="asset-registration-auto-id"><i class="fa-solid fa-fingerprint"></i> ID unit tetap</span>
                    </div>
                    <label class="asset-registration-field asset-registration-type-field">
                        <span>Tipe aset <b>*</b></span>
                        <select id="ea-cat" class="form-control" onchange="window.updateEditAssetFormLayout()" required>
                            <option value="Excavator">Excavator</option>
                            <option value="Bulldozer">Bulldozer</option>
                            <option value="Dump Truck">Dump Truck</option>
                            <option value="Motor Grader">Motor Grader</option>
                            <option value="Vibro Compactor">Vibro Compactor</option>
                            <option value="Water Truck">Water Truck</option>
                            <option value="Prime Mover">Prime Mover</option>
                            <option value="Light Vehicle">Light Vehicle</option>
                            <option value="Reclaimer Spreader">Reclaimer Spreader</option>
                            <option value="Trado">Trado</option>
                            <option value="Other">Other</option>
                        </select>
                    </label>
                </section>

                <section class="asset-registration-section" aria-labelledby="editAssetDataTitle">
                    <div class="asset-registration-section-heading">
                        <div>
                            <span class="asset-registration-step">02</span>
                            <h3 id="editAssetDataTitle">Data utama unit</h3>
                        </div>
                        <span id="ea-form-mode" class="asset-registration-mode">Formulir umum</span>
                    </div>

                    <div class="asset-registration-grid">
                        <label class="asset-registration-field asset-registration-field-wide">
                            <span>Nama aset <b>*</b></span>
                            <input type="text" id="ea-model" placeholder="Contoh: Komatsu PC200-10M0 CE" class="form-control" required>
                        </label>
                        <label class="asset-registration-field">
                            <span>Kode Unit</span>
                            <input type="text" id="ea-asset-code" class="form-control" readonly aria-describedby="ea-asset-code-help">
                            <small id="ea-asset-code-help">Kode unit adalah identitas permanen.</small>
                        </label>
                        <label class="asset-registration-field">
                            <span>Status Warranty</span>
                            <select id="ea-warranty" class="form-control">
                                <option value="Belum diketahui">Belum diketahui</option>
                                <option value="Aktif">Aktif</option>
                                <option value="Tidak ada warranty">Tidak ada warranty</option>
                                <option value="Kedaluwarsa">Kedaluwarsa</option>
                            </select>
                        </label>
                        <label class="asset-registration-field">
                            <span>Kapasitas</span>
                            <input type="text" id="ea-capacity" placeholder="Contoh: 20 ton, 1,2 m³, atau 10.000 liter" class="form-control" maxlength="100">
                        </label>
                        <label class="asset-registration-field" data-edit-form-variant="general">
                            <span>Dealer</span>
                            <input type="text" id="ea-dealer" placeholder="Nama dealer atau cabang" class="form-control">
                        </label>
                        <label class="asset-registration-field">
                            <span>Tahun <b>*</b></span>
                            <input type="number" id="ea-year" placeholder="2026" min="1970" max="2099" class="form-control" oninput="window.updateEditAssetAge()" required>
                        </label>
                        <label class="asset-registration-field" data-edit-form-variant="general">
                            <span>Serial number</span>
                            <input type="text" id="ea-sn" placeholder="Nomor seri pabrikan" class="form-control">
                        </label>
                        <label class="asset-registration-field" data-edit-form-variant="dump">
                            <span>Serial number / pelat kendaraan</span>
                            <input type="text" id="ea-dump-identity" placeholder="Nomor seri atau pelat kendaraan" class="form-control">
                        </label>
                        <label class="asset-registration-field">
                            <span id="ea-hull-label">Nomor lambung aktual</span>
                            <div class="asset-registration-field-action">
                                <input type="text" id="ea-hull" class="form-control" readonly aria-describedby="ea-hull-help">
                                <button type="button" onclick="window.openEditAssetHullRename()" title="Ubah melalui riwayat nomor lambung"><i class="fa-solid fa-pen"></i></button>
                            </div>
                            <small id="ea-hull-help">Perubahan kode disimpan dalam riwayat identitas unit.</small>
                        </label>
                        <label class="asset-registration-field">
                            <span>Project</span>
                            <select id="ea-loc" class="form-control">
                                <option value="">Memuat daftar project...</option>
                            </select>
                        </label>
                        <label class="asset-registration-field">
                            <span id="ea-position-label">Lokasi</span>
                            <input type="text" id="ea-position" placeholder="Lokasi atau posisi aktual unit" class="form-control">
                        </label>
                        <label class="asset-registration-field">
                            <span>Status</span>
                            <select id="ea-status" class="form-control">
                                <option value="READY">READY - Siap Operasi</option>
                                <option value="OPERATING">OPERATING - Sedang Operasi</option>
                                <option value="STANDBY">STANDBY - Cadangan</option>
                                <option value="INSPEKSI">INSPEKSI - Pengecekan</option>
                                <option value="BREAKDOWN">BREAKDOWN - Perbaikan</option>
                                <option value="ACCIDENT_HOLD">ACCIDENT HOLD - Tahan Investigasi</option>
                            </select>
                        </label>
                        <label class="asset-registration-field" data-edit-form-variant="general">
                            <span>Lokasi invoice</span>
                            <input type="text" id="ea-invoice-location" placeholder="Tempat penyimpanan invoice" class="form-control">
                        </label>
                        <label class="asset-registration-field" data-edit-form-variant="dump">
                            <span>Lokasi STNK</span>
                            <input type="text" id="ea-stnk-location" placeholder="Tempat penyimpanan STNK" class="form-control">
                        </label>
                        <label class="asset-registration-field" data-edit-form-variant="dump">
                            <span>Lokasi BPKB</span>
                            <input type="text" id="ea-bpkb-location" placeholder="Tempat penyimpanan BPKB" class="form-control">
                        </label>
                        <label class="asset-registration-field" data-edit-form-variant="dump">
                            <span>Nomor rangka</span>
                            <input type="text" id="ea-chassis" placeholder="Nomor rangka kendaraan" class="form-control">
                        </label>
                        <label class="asset-registration-field" data-edit-form-variant="dump">
                            <span>Tipe</span>
                            <input type="text" id="ea-vehicle-type" placeholder="Contoh: FM280JD (6x4)" class="form-control">
                        </label>
                        <label class="asset-registration-field">
                            <span>Usia</span>
                            <div class="asset-registration-age-control">
                                <input type="number" id="ea-age" min="0" max="100" placeholder="Otomatis" class="form-control" readonly>
                                <span>tahun</span>
                            </div>
                        </label>
                    </div>
                </section>

                <section class="asset-registration-section" aria-labelledby="editAssetPmTitle">
                    <div class="asset-registration-section-heading">
                        <div>
                            <span class="asset-registration-step">03</span>
                            <h3 id="editAssetPmTitle">Konfigurasi Preventive Maintenance</h3>
                        </div>
                        <span class="asset-registration-auto-id"><i class="fa-solid fa-link"></i> Sumber jadwal PM</span>
                    </div>
                    <p class="asset-registration-section-note">Perubahan di sini langsung menjadi sumber Forecast PM setelah disimpan. Kosongkan seluruh paket bila jadwal unit memang belum ditetapkan.</p>
                    <div class="asset-registration-grid">
                        <label class="asset-registration-field"><span>Meter unit saat ini</span><input id="ea-current-meter" type="number" min="0" step="0.1" class="form-control"></label>
                        <label class="asset-registration-field"><span>Tanggal servis terakhir</span><input id="ea-last-service-date" type="date" class="form-control"></label>
                        <label class="asset-registration-field"><span>Meter servis terakhir</span><input id="ea-last-service-hm" type="number" min="0" step="0.1" class="form-control"></label>
                        <label class="asset-registration-field"><span>Interval servis</span><input id="ea-service-interval" type="number" min="1" step="1" class="form-control"></label>
                        <label class="asset-registration-field"><span>Jenis meter</span><select id="ea-service-unit" class="form-control"><option value="HM">HM</option><option value="KM">KM</option></select></label>
                        <label class="asset-registration-field"><span>Pemakaian rata-rata per hari</span><input id="ea-daily-rate" type="number" min="0.01" step="0.01" class="form-control"></label>
                    </div>
                </section>

                <section class="asset-registration-section" aria-labelledby="editAssetLegalTitle">
                    <div class="asset-registration-section-heading">
                        <div>
                            <span class="asset-registration-step">04</span>
                            <h3 id="editAssetLegalTitle">Legalitas dan Dokumentasi</h3>
                        </div>
                        <span class="asset-registration-optional">Opsional</span>
                    </div>
                    <div class="asset-registration-legal-grid">
                        <label class="asset-registration-field"><span>STNK berlaku sampai</span><input id="ea-stnk" type="date" class="form-control"></label>
                        <label class="asset-registration-field"><span>KIR berlaku sampai</span><input id="ea-kir" type="date" class="form-control"></label>
                        <label class="asset-registration-field"><span>SILO berlaku sampai</span><input id="ea-silo" type="date" class="form-control"></label>
                    </div>
                    <div id="ea-document-status" class="asset-registration-document-status" aria-live="polite">Memuat dokumentasi unit...</div>
                    <div class="asset-registration-upload-grid">
                        <label class="asset-registration-upload">
                            <span><i class="fa-regular fa-image"></i> Tambah / ganti foto unit</span>
                            <small>JPEG atau PNG · maks. 5 MB per foto</small>
                            <input id="ea-photos" type="file" accept="image/jpeg,image/png" multiple class="form-control" onchange="window.previewEditAssetPhotos(this.files)">
                        </label>
                        <label class="asset-registration-upload">
                            <span><i class="fa-regular fa-file-pdf"></i> Ganti PDF JO servis</span>
                            <small>Dokumen baru menggantikan PDF lama · maks. 10 MB</small>
                            <input id="ea-service-pdf" type="file" accept="application/pdf" class="form-control">
                        </label>
                    </div>
                    <div id="ea-photo-preview" class="asset-registration-photo-preview"></div>
                </section>

                <div id="ea-error-alert" class="asset-registration-error" role="alert"></div>

                <div class="asset-registration-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalEditAsset')">Batal</button>
                    <button type="button" class="btn btn-primary" id="btnSubmitEditAsset" onclick="submitEditAsset()"><i class="fa-solid fa-floppy-disk"></i> Simpan perubahan</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Update Status -->
    <div class="modal-overlay" id="modalUpdateStatus">
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header">
                <h2>Update Status Unit: <span id="us-id-label"></span></h2>
                <button onclick="closeModal('modalUpdateStatus')"
                    style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <div class="modal-body form-group">
                <input type="hidden" id="us-id">
                <label>Status Baru</label>
                <select id="us-status" class="form-control">
                    <option value="READY">READY - Unit Siap Operasi Full</option>
                    <option value="OPERATING">OPERATING - Unit Langsung Beroperasi</option>
                    <option value="STANDBY">STANDBY - Unit Siap Pakai Cadangan</option>
                    <option value="INSPEKSI">INSPEKSI - Dalam Pengecekan / PM</option>
                    <option value="BREAKDOWN">BREAKDOWN - Perbaikan Lanjutan Masih Dibutuhkan</option>
                    <option value="ACCIDENT HOLD">ACCIDENT HOLD - Tahan Investigasi</option>
                </select>
                <label>Update Lokasi (Opsional)</label>
                <input type="text" id="us-loc" placeholder="Biarkan kosong jika tidak pindah" class="form-control">
                <label>Keterangan / Kondisi Fisik</label>
                <textarea id="us-notes" rows="3" class="form-control"
                    placeholder="Tuliskan keterangan perpindahan atau alasan status..."></textarea>
                <button class="btn btn-primary" onclick="submitUpdateStatus()" style="width:100%;">Update
                    Status</button>
            </div>
        </div>
    </div>

    <!-- Modal Lapor Breakdown / WO -->
    <div class="modal-overlay" id="modalNewWO">
        <div class="modal-content wo-create-dialog" role="dialog" aria-modal="true" aria-labelledby="newWoTitle">
            <div class="modal-header wo-create-header">
                <div>
                    <span class="wo-create-kicker">FRM-WO-01 &middot; Pelaporan lapangan</span>
                    <h2 id="newWoTitle">Buat Work Order / Job Order</h2>
                    <p>Catat kondisi unit, waktu penanganan, PIC, dan bukti awal dalam satu formulir.</p>
                </div>
                <button type="button" class="wo-create-close" onclick="closeModal('modalNewWO')" aria-label="Tutup formulir">&times;</button>
            </div>
            <form id="newWoForm" class="modal-body wo-create-body" onsubmit="event.preventDefault(); submitNewWO();">
                <section class="wo-form-section" aria-labelledby="woSectionIdentity">
                    <div class="wo-section-heading">
                        <span class="wo-section-index">01</span>
                        <div><h3 id="woSectionIdentity">Identitas alat & lokasi</h3><p>Pilih unit untuk mengisi data master secara otomatis.</p></div>
                    </div>
                    <div class="wo-form-grid">
                        <div class="wo-field wo-field-span-2">
                            <label for="nwo-unit-search">Cari unit</label>
                            <div class="wo-unit-search"><i class="fa-solid fa-magnifying-glass"></i><input id="nwo-unit-search" type="search" placeholder="Kode unit, nomor lambung, serial, atau plat" autocomplete="off" oninput="filterNewWOUnits(this.value)"></div>
                            <select id="nwo-id" class="form-control" onchange="updateNewWOAssetSummary()" required><option value="">Pilih unit alat berat</option></select>
                            <div class="wo-asset-snapshot" id="nwo-asset-snapshot">Pilih unit untuk melihat identitas dan status terakhir.</div>
                        </div>
                        <div class="wo-field"><label for="nwo-equipment-type">Jenis alat berat</label><input id="nwo-equipment-type" class="form-control" readonly placeholder="Terisi otomatis"></div>
                        <div class="wo-field"><label for="nwo-hull-number">Nomor lambung project</label><input id="nwo-hull-number" class="form-control" placeholder="Boleh disesuaikan dengan nomor di lapangan"></div>
                        <div class="wo-field"><label for="nwo-location">Lokasi trouble <span>*</span></label><input id="nwo-location" class="form-control" list="nwo-location-options" placeholder="Contoh: Pit Barat Front 3" required><datalist id="nwo-location-options"><option value="Pit Barat"></option><option value="Front 2"></option><option value="Disposal Selatan"></option><option value="Hauling Road KM 14"></option><option value="Workshop Induk"></option></datalist></div>
                        <div class="wo-field"><label for="nwo-hm-km">HM / KM aktual <span>*</span></label><input id="nwo-hm-km" class="form-control font-mono" type="number" min="0" step="0.1" placeholder="0.0" required></div>
                    </div>
                </section>

                <section class="wo-form-section" aria-labelledby="woSectionTime">
                    <div class="wo-section-heading">
                        <span class="wo-section-index">02</span>
                        <div><h3 id="woSectionTime">Waktu & klasifikasi pekerjaan</h3><p>Waktu selesai dapat dikoreksi saat tiket ditutup.</p></div>
                    </div>
                    <div class="wo-form-grid">
                        <div class="wo-field"><label for="nwo-trouble-at">Tanggal & waktu trouble <span>*</span></label><input id="nwo-trouble-at" class="form-control font-mono" type="datetime-local" required></div>
                        <div class="wo-field"><label for="nwo-type">Sifat maintenance</label><select id="nwo-type" class="form-control"><option value="Corrective">Corrective / Breakdown</option><option value="Preventive">Preventive / Berkala</option><option value="Inspection">Inspection / Temuan</option><option value="Emergency">Emergency / Darurat</option></select></div>
                        <div class="wo-field"><label for="nwo-started-at">Mulai pekerjaan</label><input id="nwo-started-at" class="form-control font-mono" type="datetime-local"></div>
                        <div class="wo-field"><label for="nwo-completed-at">Estimasi / waktu selesai</label><input id="nwo-completed-at" class="form-control font-mono" type="datetime-local"></div>
                        <div class="wo-field"><label for="nwo-prio">Prioritas</label><select id="nwo-prio" class="form-control"><option value="Normal">Normal / Terjadwal</option><option value="High">High / Mendesak</option><option value="Emergency">Emergency / Hentikan unit</option></select></div>
                        <div class="wo-field"><label for="nwo-down">Status operasi unit</label><select id="nwo-down" class="form-control"><option value="Yes">Berhenti total / Breakdown</option><option value="No">Masih beroperasi terbatas</option></select></div>
                    </div>
                </section>

                <section class="wo-form-section" aria-labelledby="woSectionDiagnosis">
                    <div class="wo-section-heading">
                        <span class="wo-section-index">03</span>
                        <div><h3 id="woSectionDiagnosis">Diagnosis & tindakan mekanik</h3><p>Pisahkan gejala, dugaan penyebab, dan pekerjaan yang ditindaklanjuti.</p></div>
                    </div>
                    <div class="wo-form-grid">
                        <div class="wo-field wo-field-span-2"><label for="nwo-issue">Uraian jenis trouble <span>*</span></label><textarea id="nwo-issue" rows="3" class="form-control" placeholder="Gejala, indikator panel, bunyi abnormal, atau komponen terdampak" required></textarea></div>
                        <div class="wo-field"><label for="nwo-root-cause">Dugaan penyebab</label><textarea id="nwo-root-cause" rows="3" class="form-control" placeholder="Isi jika penyebab awal sudah diketahui"></textarea></div>
                        <div class="wo-field"><label for="nwo-repair-action">Pekerjaan / tindakan mekanik</label><textarea id="nwo-repair-action" rows="3" class="form-control" placeholder="Pemeriksaan, penyetelan, perbaikan, atau penggantian komponen"></textarea></div>
                        <div class="wo-field wo-field-span-2">
                            <div class="wo-parts-section-card">
                                <div class="wo-parts-section-header">
                                    <div>
                                        <span class="wo-parts-kicker"><i class="fa-solid fa-boxes-stacked"></i> Kebutuhan Material & Logistik</span>
                                        <h4 class="wo-parts-title">Spare Part yang Di-request</h4>
                                        <p class="wo-parts-subtitle">Daftar suku cadang yang diminta untuk perbaikan unit, kuantitas kebutuhan, serta estimasi jam & tanggal kedatangan part di lokasi.</p>
                                    </div>
                                    <span class="wo-parts-badge"><i class="fa-solid fa-truck-ramp-box"></i> Request Part</span>
                                </div>

                                <div class="table-responsive wo-parts-table-wrap">
                                    <table id="nwo-spb-item-table" class="wo-parts-table">
                                        <thead>
                                            <tr>
                                                <th style="width:38%;">Spare Part yang Di-request</th>
                                                <th style="width:20%;">Jumlah (Qty & Satuan)</th>
                                                <th style="width:30%;">Jam & Tanggal Spare Part Tiba</th>
                                                <th style="width:12%; text-align:center;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody id="nwo-spb-items">
                                            <tr><td colspan="4" class="wo-parts-empty"><i class="fa-solid fa-box-open" style="font-size:1.3rem; color:#cbd5e1; display:block; margin-bottom:5px;"></i>Belum ada spare part yang di-request. Gunakan tombol Tambah Spare Part di bawah.</td></tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="wo-parts-help">
                                    <i class="fa-solid fa-circle-info"></i>
                                    <span>Pilih part dari katalog gudang atau <strong>+ Part baru / input manual</strong>. Masukkan perkiraan jam & tanggal tiba part agar tim mekanik dapat menjadwalkan perbaikan dengan tepat.</span>
                                </div>

                                <div class="wo-parts-actions-bar">
                                    <button type="button" class="btn btn-sm btn-outline-primary wo-parts-add-btn" id="nwo-btn-add-part" onclick="window.addNwoPartRow ? window.addNwoPartRow() : (window.NewWorkOrderForm && window.NewWorkOrderForm.addPart ? window.NewWorkOrderForm.addPart() : null)">
                                        <i class="fa-solid fa-plus"></i> Tambah Spare Part
                                    </button>
                                    <span id="nwo-spb-counter" class="wo-parts-counter">0 spare part tercatat</span>
                                </div>

                                <div id="nwo-parts-flow-alert" style="display:none; align-items:flex-start; gap:10px; padding:10px 14px; border-radius:8px; font-size:0.8rem; line-height:1.45; margin-top:10px; transition:all 0.2s ease;"></div>

                                <textarea id="nwo-parts-needed" style="display:none;"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="wo-mitigation-card" id="nwo-mitigation-card">
                        <div class="wo-mitigation-head">
                            <div><span class="wo-risk-label"><i class="fa-solid fa-triangle-exclamation"></i> Operasi bersyarat</span><strong>Alat tetap dipaksakan beroperasi untuk mengejar target</strong><small>WO tetap mencatat unit rusak dan wajib memuat batasan operasinya.</small></div>
                            <label class="wo-switch"><input id="nwo-forced-operation" type="checkbox" onchange="toggleNewWOMitigation(this.checked)"><span aria-hidden="true"></span><em>Aktifkan</em></label>
                        </div>
                        <div class="wo-mitigation-details" id="nwo-mitigation-details" hidden>
                            <span class="wo-rfu-badge"><i class="fa-solid fa-gauge-high"></i> RFU Bersyarat</span>
                            <label for="nwo-mitigation">Kendala teknik, tindakan mitigasi, dan batas operasi <span>*</span></label>
                            <textarea id="nwo-mitigation" rows="3" class="form-control" placeholder="Contoh: batasi kecepatan, inspeksi tiap pergantian shift, hentikan unit bila temperatur melebihi batas"></textarea>
                        </div>
                    </div>
                </section>

                <section class="wo-form-section" aria-labelledby="woSectionTeam">
                    <div class="wo-section-heading">
                        <span class="wo-section-index">04</span>
                        <div><h3 id="woSectionTeam">PIC & dokumentasi</h3><p>Tambahkan lebih dari satu mekanik dan foto kondisi awal.</p></div>
                    </div>
                    <div class="wo-form-grid wo-team-grid">
                        <div class="wo-field">
                            <label for="nwo-mechanic-input">PIC mekanik / teknisi (Multi-Pilih)</label>
                            <div class="wo-chip-composer"><input id="nwo-mechanic-input" list="nwo-mechanic-options" placeholder="Ketik nama lalu Enter" onkeydown="handleNewWOMechanicKey(event)"><button type="button" onclick="addNewWOMechanic()" aria-label="Tambah PIC"><i class="fa-solid fa-plus"></i></button></div>
                            <datalist id="nwo-mechanic-options"><option value="Suryanto (Lead Engine)"></option><option value="Bambang (Hydraulic)"></option><option value="Agus W. (Electrical)"></option><option value="Joko P. (Undercarriage)"></option><option value="Dedi K. (Powertrain)"></option><option value="Apeng (Lead Mechanic)"></option><option value="Maman (Senior Technician)"></option><option value="Soleh (Hydraulic Specialist)"></option><option value="Darmawan (Electrical Mechanic)"></option><option value="Regar (Junior Mechanic)"></option></datalist>
                            <div class="wo-quick-mechanics" style="display:flex; align-items:center; gap:5px; flex-wrap:wrap; margin-top:6px;">
                                <span style="font-size:0.72rem; color:var(--text-muted); font-weight:600;">Pilih Cepat:</span>
                                <button type="button" class="btn btn-xs" style="padding:2px 7px; font-size:0.72rem; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:12px; cursor:pointer;" onclick="addNewWOMechanic('Suryanto')">+ Suryanto</button>
                                <button type="button" class="btn btn-xs" style="padding:2px 7px; font-size:0.72rem; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:12px; cursor:pointer;" onclick="addNewWOMechanic('Bambang')">+ Bambang</button>
                                <button type="button" class="btn btn-xs" style="padding:2px 7px; font-size:0.72rem; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:12px; cursor:pointer;" onclick="addNewWOMechanic('Agus W.')">+ Agus W.</button>
                                <button type="button" class="btn btn-xs" style="padding:2px 7px; font-size:0.72rem; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:12px; cursor:pointer;" onclick="addNewWOMechanic('Apeng')">+ Apeng</button>
                                <button type="button" class="btn btn-xs" style="padding:2px 7px; font-size:0.72rem; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:12px; cursor:pointer;" onclick="addNewWOMechanic('Maman')">+ Maman</button>
                                <button type="button" class="btn btn-xs" style="padding:2px 7px; font-size:0.72rem; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:12px; cursor:pointer;" onclick="addNewWOMechanic('Soleh')">+ Soleh</button>
                            </div>
                            <div class="wo-chip-list" id="nwo-mechanic-chips" aria-live="polite"><span class="wo-chip-empty">Belum ada PIC dipilih</span></div>
                            <input type="hidden" id="nwo-mechanic" value="Belum ada PIC">
                        </div>
                        <div class="wo-field">
                            <label for="nwo-photo-input">Dokumentasi foto lapangan</label>
                            <label class="wo-photo-dropzone" id="nwo-photo-dropzone" for="nwo-photo-input"><i class="fa-solid fa-camera"></i><strong>Pilih atau jatuhkan foto</strong><small>JPG, PNG, atau WebP &middot; maksimal 5 MB</small></label>
                            <input id="nwo-photo-input" class="wo-file-input" type="file" accept="image/jpeg,image/png,image/webp" onchange="previewNewWOPhotos(this.files)">
                            <div class="wo-photo-preview" id="nwo-photo-preview"></div>
                        </div>
                    </div>
                </section>

                <div class="wo-form-message" id="nwo-form-message" role="status" aria-live="polite" hidden></div>
                <div class="wo-create-actions"><button type="button" class="btn btn-secondary" onclick="closeModal('modalNewWO')">Batal</button><button type="submit" class="btn btn-danger" id="nwo-submit"><i class="fa-solid fa-file-circle-plus"></i> Buat tiket WO</button></div>
            </form>
        </div>
    </div>



    <!-- Modal Tabulasi Detail Status Unit -->
    <div class="modal-overlay" id="modalStatusDetail">
        <div class="modal-content" style="max-width: 900px; width: 95%;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0; font-size: 1.25rem;"><i class="fa-solid fa-table-list"></i> <span
                        id="statusDetailTitle">Tabulasi Unit Alat</span></h2>
                <button onclick="closeModal('modalStatusDetail')"
                    style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <div class="modal-body" style="padding: 15px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                        <input type="text" id="statusDetailSearch" class="form-control"
                            placeholder="Cari unit ID, kategori, atau lokasi..." style="max-width: 300px; margin-bottom: 0;"
                            onkeyup="filterStatusDetailTable()">

                        <!-- Segmented Sub-Filter Select for OPERATING / READY -->
                        <div id="opReadySubFilterWrapper" style="display: none; align-items: center; gap: 4px; background: #f1f5f9; padding: 4px; border-radius: 8px; border: 1px solid #cbd5e1;">
                            <button type="button" class="op-sub-btn active" id="btnSubAll" onclick="setOpReadySubFilter('ALL', this)">
                                <span class="op-sub-count" id="countSubAll">0</span> Semua
                            </button>
                            <button type="button" class="op-sub-btn" id="btnSubOperating" onclick="setOpReadySubFilter('OPERATING', this)">
                                <span class="op-sub-count badge-op" id="countSubOperating">0</span> OPERATING
                            </button>
                            <button type="button" class="op-sub-btn" id="btnSubReady" onclick="setOpReadySubFilter('READY', this)">
                                <span class="op-sub-count badge-rd" id="countSubReady">0</span> READY
                            </button>
                        </div>
                    </div>
                    <div style="font-size: 0.85rem; color: var(--text-muted);">
                        Total Ditemukan: <strong id="statusDetailCount" style="color: var(--dark);">0</strong> Unit
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table style="width: 100%; margin: 0; font-size: 0.88rem;">
                        <thead style="position: sticky; top: 0; background: #f8f9fa; z-index: 2;">
                            <tr>
                                <th>Unit ID / Kode Lambung</th>
                                <th>SN / Plat Kendaraan</th>
                                <th>Kategori Alat</th>
                                <th>Lokasi Aktif</th>
                                <th>Last Update</th>
                                <th>Status</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="statusDetailTableBody">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Modal Detail Work Order 360° Command Center -->
        <div class="modal-overlay" id="modalWoDetail">
            <div class="modal-content wo-command-center-dialog">
                <!-- Header -->
                <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border); padding:16px 22px; background:#f8fafc; flex-shrink:0;">
                    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                        <div style="width:36px; height:36px; border-radius:8px; background:rgba(0,123,255,0.1); color:var(--primary); display:flex; align-items:center; justify-content:center; font-size:1.15rem;">
                            <i class="fa-solid fa-screwdriver-wrench"></i>
                        </div>
                        <div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <h2 style="margin:0; font-size:1.2rem; font-weight:700; color:#0f172a;">
                                    Work Order: <span id="modalWoId"></span>
                                </h2>
                                <span id="modalWoStatusBadge"></span>
                                <span id="modalWoPriorityBadge"></span>
                            </div>
                            <div style="font-size:0.82rem; color:var(--text-muted); margin-top:2px;">
                                <span id="modalWoUnitSubtitle">Memuat data unit...</span>
                            </div>
                        </div>
                    </div>
                    <button onclick="closeModal('modalWoDetail')" style="border:none; background:none; font-size:1.6rem; color:#64748b; cursor:pointer; line-height:1; padding:4px 8px; border-radius:6px;" title="Tutup">&times;</button>
                </div>

                <!-- Tab Navigation Bar -->
                <div class="wo-detail-tabs-nav">
                    <button type="button" class="wo-tab-btn active" onclick="switchWoDetailTab('tabWoInfo')">
                        <i class="fa-solid fa-circle-info"></i> Informasi &amp; Diagnosa
                    </button>
                    <button type="button" class="wo-tab-btn" onclick="switchWoDetailTab('tabWoParts')">
                        <i class="fa-solid fa-boxes-stacked"></i> Suku Cadang &amp; SPB 
                        <span id="modalWoPartsTabBadge" class="badge bg-secondary" style="margin-left:4px; font-size:0.7rem;">0</span>
                    </button>
                    <button type="button" class="wo-tab-btn" onclick="switchWoDetailTab('tabWoServices')">
                        <i class="fa-solid fa-wrench"></i> Layanan Workshop
                        <span id="modalWoServicesTabBadge" class="badge bg-secondary" style="margin-left:4px; font-size:0.7rem;">0</span>
                    </button>
                    <button type="button" class="wo-tab-btn" onclick="switchWoDetailTab('tabWoCosts')">
                        <i class="fa-solid fa-coins"></i> Biaya Perbaikan
                        <span id="modalWoCostsTabBadge" class="badge bg-secondary" style="margin-left:4px; font-size:0.7rem;">0</span>
                    </button>
                    <button type="button" class="wo-tab-btn" onclick="switchWoDetailTab('tabWoOvertime')">
                        <i class="fa-solid fa-business-time"></i> Lembur (SPL)
                        <span id="modalWoOvertimeTabBadge" class="badge bg-secondary" style="margin-left:4px; font-size:0.7rem;">0</span>
                    </button>
                    <button type="button" class="wo-tab-btn" onclick="switchWoDetailTab('tabWoTimeline')">
                        <i class="fa-solid fa-timeline"></i> Timeline
                    </button>
                </div>

                <!-- Tab Body Area (Scrollable) -->
                <div class="modal-body" style="padding:0; flex:1 1 auto; overflow-y:auto; min-height:0; background:#ffffff;">
                    <!-- TAB 1: Informasi & Diagnosa -->
                    <div id="tabWoInfo" class="wo-tab-pane active" style="padding:20px;">
                        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:12px; margin-bottom:18px; background:#f8fafc; border:1px solid #e2e8f0; padding:14px; border-radius:10px; font-size:0.86rem;">
                            <div><strong style="color:#64748b; font-size:0.78rem; display:block; text-transform:uppercase;">Unit Alat Berat</strong> <span id="modalWoAsset" style="font-weight:600;">-</span></div>
                            <div><strong style="color:#64748b; font-size:0.78rem; display:block; text-transform:uppercase;">Status Pekerjaan</strong> <span id="modalWoStatus">-</span></div>
                            <div><strong style="color:#64748b; font-size:0.78rem; display:block; text-transform:uppercase;">Tingkat Prioritas</strong> <span id="modalWoPriority">-</span></div>
                            <div><strong style="color:#64748b; font-size:0.78rem; display:block; text-transform:uppercase;">Downtime Operasional</strong> <span id="modalWoDowntime" style="color:#dc2626; font-weight:700;">-</span></div>
                            <div><strong style="color:#64748b; font-size:0.78rem; display:block; text-transform:uppercase;">Jenis Pemeliharaan</strong> <span id="modalWoMaintenanceType" style="font-weight:600;">-</span></div>
                            <div><strong style="color:#64748b; font-size:0.78rem; display:block; text-transform:uppercase;">HM / KM Aktual</strong> <span id="modalWoHmKm" style="font-weight:600;">-</span></div>
                            <div><strong style="color:#64748b; font-size:0.78rem; display:block; text-transform:uppercase;">Lokasi Trouble</strong> <span id="modalWoLocation" style="font-weight:500;">-</span></div>
                            <div><strong style="color:#64748b; font-size:0.78rem; display:block; text-transform:uppercase;">PIC Mekanik</strong> <span id="modalWoMechanic" style="font-weight:600;">-</span></div>
                            <div><strong style="color:#64748b; font-size:0.78rem; display:block; text-transform:uppercase;">Dilaporkan</strong> <span id="modalWoReportedAt">-</span></div>
                            <div><strong style="color:#64748b; font-size:0.78rem; display:block; text-transform:uppercase;">Mulai Dikerjakan</strong> <span id="modalWoStartedAt">-</span></div>
                            <div><strong style="color:#64748b; font-size:0.78rem; display:block; text-transform:uppercase;">Target Selesai</strong> <span id="modalWoEstimatedAt">-</span></div>
                            <div><strong style="color:#64748b; font-size:0.78rem; display:block; text-transform:uppercase;">Selesai Ditutup</strong> <span id="modalWoClosedAt">-</span></div>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                            <div style="background:#ffffff; border:1px solid var(--border); border-radius:8px; padding:12px;">
                                <label style="font-weight:700; display:block; margin-bottom:6px; font-size:0.86rem; color:#1e293b;">
                                    <i class="fa-solid fa-triangle-exclamation" style="color:var(--warning);"></i> Keluhan / Kerusakan:
                                </label>
                                <div id="modalWoIssue" style="font-size:0.86rem; line-height:1.5; color:#334155; min-height:48px; white-space:pre-wrap;"></div>
                            </div>
                            <div style="background:#ffffff; border:1px solid var(--border); border-radius:8px; padding:12px;">
                                <label style="font-weight:700; display:block; margin-bottom:6px; font-size:0.86rem; color:#1e293b;">
                                    <i class="fa-solid fa-magnifying-glass" style="color:var(--info);"></i> Penyebab Kerusakan (Root Cause):
                                </label>
                                <div id="modalWoRootCause" style="font-size:0.86rem; line-height:1.5; color:#334155; min-height:48px; white-space:pre-wrap;">-</div>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
                            <div style="background:#ffffff; border:1px solid var(--border); border-radius:8px; padding:12px;">
                                <label style="font-weight:700; display:block; margin-bottom:6px; font-size:0.86rem; color:#1e293b;">
                                    <i class="fa-solid fa-wrench" style="color:var(--success);"></i> Tindakan Perbaikan (Repair Action):
                                </label>
                                <div id="modalWoRepairAction" style="font-size:0.86rem; line-height:1.5; color:#334155; min-height:48px; white-space:pre-wrap;">-</div>
                            </div>
                            <div style="background:#ffffff; border:1px solid var(--border); border-radius:8px; padding:12px;">
                                <label style="font-weight:700; display:block; margin-bottom:6px; font-size:0.86rem; color:#1e293b;">
                                    <i class="fa-solid fa-shield" style="color:#64748b;"></i> Tindakan Mitigasi / Operasi Paksa:
                                </label>
                                <div id="modalWoMitigationAction" style="font-size:0.86rem; line-height:1.5; color:#334155; min-height:48px; white-space:pre-wrap;">-</div>
                            </div>
                        </div>

                        <!-- Foto Trouble jika ada -->
                        <div id="modalWoPhotoContainer" style="display:none; margin-top:14px; background:#f8fafc; border:1px solid var(--border); border-radius:8px; padding:12px;">
                            <label style="font-weight:700; display:block; margin-bottom:8px; font-size:0.86rem;">
                                <i class="fa-solid fa-camera"></i> Foto Dokumentasi Sebelum / Trouble:
                            </label>
                            <img id="modalWoPhotoImg" src="" alt="Dokumentasi Work Order" style="max-height:220px; border-radius:6px; border:1px solid #cbd5e1; object-fit:contain; cursor:pointer;" onclick="window.open(this.src, '_blank')">
                        </div>

                        <div id="modalWoFallbackNotice" style="display:none; background:#fff3cd; color:#856404; padding:10px 14px; border-radius:8px; font-size:0.84rem; margin-top:14px; border:1px solid #ffeeba;">
                            <i class="fa-solid fa-triangle-exclamation"></i> Work Order ini dicatat langsung dari laporan insiden darurat. Silakan buat SPB atau cek histori unit.
                        </div>
                    </div>

                    <!-- TAB 2: Suku Cadang & SPB -->
                    <div id="tabWoParts" class="wo-tab-pane" style="padding:20px;">
                        <!-- Procurement Stepper Pipeline -->
                        <div style="margin-bottom:16px;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                <strong style="font-size:0.88rem; color:#0f172a;"><i class="fa-solid fa-diagram-project" style="color:var(--primary);"></i> Jalur Pengadaan Suku Cadang (Procurement Pipeline)</strong>
                                <span id="modalWoPipelineStatusBadge" class="badge bg-secondary">Memuat...</span>
                            </div>
                            <div class="procurement-stepper" id="modalWoPipelineStepper">
                                <div class="procurement-step" id="stepSpb">
                                    <div class="step-icon"><i class="fa-solid fa-file-invoice"></i></div>
                                    <span>SPB Dibuat</span>
                                </div>
                                <div class="procurement-step-line" id="lineSpbApprove"></div>
                                <div class="procurement-step" id="stepApprove">
                                    <div class="step-icon"><i class="fa-solid fa-check-double"></i></div>
                                    <span>Approval</span>
                                </div>
                                <div class="procurement-step-line" id="lineApprovePpb"></div>
                                <div class="procurement-step" id="stepPpb">
                                    <div class="step-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                                    <span>PO / PPB</span>
                                </div>
                                <div class="procurement-step-line" id="linePpbReceipt"></div>
                                <div class="procurement-step" id="stepReceipt">
                                    <div class="step-icon"><i class="fa-solid fa-truck-ramp-box"></i></div>
                                    <span>Barang Tiba</span>
                                </div>
                                <div class="procurement-step-line" id="lineReceiptIssued"></div>
                                <div class="procurement-step" id="stepIssued">
                                    <div class="step-icon"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                                    <span>Terpasang</span>
                                </div>
                            </div>
                        </div>

                        <!-- Decision & Action Bar -->
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                            <div>
                                <span style="font-size:0.78rem; text-transform:uppercase; color:#64748b; font-weight:700; display:block;">Status Kebutuhan Suku Cadang</span>
                                <span id="modalWoPartsReqStatus" style="font-weight:700; font-size:0.95rem;">-</span>
                                <small id="modalWoPartsReqNote" style="display:block; color:#64748b; font-size:0.8rem; margin-top:2px;"></small>
                            </div>
                            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                <button type="button" class="btn btn-primary" id="btnWoCreateSpb" style="padding:6px 14px; font-size:0.82rem;">
                                    <i class="fa-solid fa-plus"></i> Buat SPB untuk WO Ini
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="btnWoMarkNoParts" style="padding:6px 14px; font-size:0.82rem;">
                                    <i class="fa-solid fa-circle-check"></i> Konfirmasi Tanpa Part
                                </button>
                            </div>
                        </div>

                        <!-- Daftar SPB Terkait -->
                        <div style="margin-bottom:20px;">
                            <h4 style="font-size:0.92rem; font-weight:700; margin-bottom:10px; color:#1e293b; display:flex; align-items:center; gap:8px;">
                                <i class="fa-solid fa-list-check" style="color:var(--primary);"></i> Surat Permintaan Barang (SPB) Terkait WO
                            </h4>
                            <div id="modalWoSpbContainer">
                                <div style="color:var(--text-muted); font-size:0.86rem; padding:12px; text-align:center;">Memuat daftar SPB...</div>
                            </div>
                        </div>

                        <!-- Daftar PPB (Purchase Orders) Terkait -->
                        <div style="margin-bottom:20px;">
                            <h4 style="font-size:0.92rem; font-weight:700; margin-bottom:10px; color:#1e293b; display:flex; align-items:center; gap:8px;">
                                <i class="fa-solid fa-cart-shopping" style="color:var(--info);"></i> Purchase Order (PPB) Terkait
                            </h4>
                            <div id="modalWoPpbContainer">
                                <div style="color:var(--text-muted); font-size:0.86rem;">Belum ada Purchase Order (PPB).</div>
                            </div>
                        </div>

                        <!-- Realisasi Pemakaian Part (Inventory OUT) -->
                        <div>
                            <h4 style="font-size:0.92rem; font-weight:700; margin-bottom:10px; color:#1e293b; display:flex; align-items:center; gap:8px;">
                                <i class="fa-solid fa-arrow-right-from-bracket" style="color:var(--warning);"></i> Realisasi Pengeluaran Suku Cadang (Inventory OUT)
                            </h4>
                            <div id="modalWoUsageContainer">
                                <div style="color:var(--text-muted); font-size:0.86rem;">Belum ada pengeluaran part tercatat untuk WO ini.</div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: Layanan Bengkel -->
                    <div id="tabWoServices" class="wo-tab-pane" style="padding:20px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:8px;">
                            <strong style="font-size:0.92rem; color:#1e293b;"><i class="fa-solid fa-wrench" style="color:var(--primary);"></i> Layanan Workshop &amp; Vendor Eksternal</strong>
                            <button type="button" class="btn btn-sm btn-primary" onclick="toggleWoServiceForm()">
                                <i class="fa-solid fa-plus"></i> Ajukan Layanan Bengkel
                            </button>
                        </div>

                        <!-- Form Ajukan Layanan (Collapsible) -->
                        <div id="woServiceFormCollapse" style="display:none; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; margin-bottom:16px;">
                            <h5 style="margin:0 0 10px; font-size:0.88rem; font-weight:700;">Pengajuan Layanan Perbaikan Baru</h5>
                            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:10px; margin-bottom:10px;">
                                <div>
                                    <label style="font-size:0.8rem; font-weight:600; display:block; margin-bottom:4px;">Jenis Layanan</label>
                                    <select id="modalWoServiceType" class="form-control" style="font-size:0.84rem;">
                                        <option value="INTERNAL_WORKSHOP">Bengkel Internal</option>
                                        <option value="EXTERNAL_WORKSHOP">Bengkel Eksternal</option>
                                        <option value="FIELD_SERVICE">Layanan Lapangan (Field Service)</option>
                                        <option value="SPECIALIST_VENDOR">Vendor Spesialis</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-size:0.8rem; font-weight:600; display:block; margin-bottom:4px;">Prioritas</label>
                                    <select id="modalWoServicePriority" class="form-control" style="font-size:0.84rem;">
                                        <option value="Normal">Normal</option>
                                        <option value="Urgent">Urgent</option>
                                        <option value="Emergency">Emergency</option>
                                    </select>
                                </div>
                                <div>
                                    <label style="font-size:0.8rem; font-weight:600; display:block; margin-bottom:4px;">Penyedia / Nama Bengkel</label>
                                    <input id="modalWoServiceProvider" class="form-control" placeholder="Contoh: PT United Tractors" style="font-size:0.84rem;">
                                </div>
                                <div>
                                    <label style="font-size:0.8rem; font-weight:600; display:block; margin-bottom:4px;">PIC / Petugas Ditunjuk</label>
                                    <input id="modalWoServiceAssignee" class="form-control" placeholder="Nama teknisi / lead" style="font-size:0.84rem;">
                                </div>
                            </div>
                            <div style="margin-bottom:10px;">
                                <label style="font-size:0.8rem; font-weight:600; display:block; margin-bottom:4px;">Deskripsi Pekerjaan <span class="text-danger">*</span></label>
                                <textarea id="modalWoServiceDesc" class="form-control" rows="2" placeholder="Uraikan perbaikan/pekerjaan bengkel yang diminta..." style="font-size:0.84rem;"></textarea>
                            </div>
                            <div style="display:flex; justify-content:flex-end; gap:8px;">
                                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleWoServiceForm()">Batal</button>
                                <button type="button" class="btn btn-sm btn-success" onclick="submitModalWoServiceRequest()"><i class="fa-solid fa-paper-plane"></i> Kirim Permintaan Layanan</button>
                            </div>
                        </div>

                        <!-- Service Request List -->
                        <div id="modalWoServicesContainer">
                            <div style="color:var(--text-muted); font-size:0.86rem; padding:12px; text-align:center;">Memuat data layanan bengkel...</div>
                        </div>
                    </div>

                    <!-- TAB 4: Biaya Perbaikan -->
                    <div id="tabWoCosts" class="wo-tab-pane" style="padding:20px;">
                        <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px; margin-bottom:18px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                            <div>
                                <span style="font-size:0.8rem; color:#64748b; font-weight:600; display:block; text-transform:uppercase;">Total Biaya Perbaikan (Realisasi Aktif)</span>
                                <h3 id="modalWoCostTotalFormatted" style="margin:2px 0 0; font-size:1.6rem; font-weight:800; color:var(--success);">Rp 0</h3>
                            </div>
                            <button type="button" class="btn btn-primary" id="modalWoAddCostBtn" style="padding:8px 16px; font-size:0.84rem;">
                                <i class="fa-solid fa-plus"></i> Catat Transaksi Biaya
                            </button>
                        </div>

                        <h4 style="font-size:0.92rem; font-weight:700; margin-bottom:12px; color:#1e293b;">
                            <i class="fa-solid fa-receipt" style="color:var(--primary);"></i> Rincian Transaksi Biaya WO
                        </h4>
                        <div id="modalWoCostsContainer">
                            <div style="color:var(--text-muted); font-size:0.86rem; padding:12px; text-align:center;">Memuat transaksi biaya...</div>
                        </div>
                    </div>

                    <!-- TAB 5: Timeline -->
                    <div id="tabWoTimeline" class="wo-tab-pane" style="padding:20px;">
                        <div id="modalWoTimeline" style="border-left:3px solid var(--primary); padding-left:14px; font-size:0.85rem; line-height:1.6;">
                            <div style="color:var(--text-muted);">Memuat timeline penanganan...</div>
                        </div>
                    </div>

                    <!-- TAB 6: Lembur (SPL) -->
                    <div id="tabWoOvertime" class="wo-tab-pane" style="padding:20px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; flex-wrap:wrap; gap:8px;">
                            <div>
                                <strong style="font-size:0.92rem; color:#1e293b;"><i class="fa-solid fa-business-time" style="color:var(--primary);"></i> Surat Perintah Lembur (SPL) Terkait</strong>
                                <div style="font-size:0.78rem; color:var(--text-muted); margin-top:2px;">Penugasan jam lembur personel untuk percepatan penanganan perbaikan darurat / breakdown unit.</div>
                            </div>
                            <button type="button" class="btn btn-sm btn-primary" onclick="window.createSplForCurrentWo()">
                                <i class="fa-solid fa-file-circle-plus"></i> Buat SPL Lembur
                            </button>
                        </div>
                        <div id="modalWoOvertimeContainer">
                            <div style="color:var(--text-muted); font-size:0.86rem; padding:12px; text-align:center;">Memuat riwayat SPL...</div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer" style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border); padding:12px 20px; background:#f8fafc; flex-shrink:0; flex-wrap:wrap; gap:8px;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <label for="modalWoDocumentAlias" style="font-size:0.84rem; font-weight:600; margin:0;">Dokumen:</label>
                        <select id="modalWoDocumentAlias" style="font-size:0.82rem; padding:4px 8px; border-radius:4px; border:1px solid #cbd5e1;">
                            <option value="WO">Work Order (WO)</option>
                            <option value="JO">Job Order (JO)</option>
                        </select>
                        <button class="btn btn-secondary btn-sm" id="modalWoPrintDocumentBtn" type="button" disabled style="padding:4px 10px; font-size:0.82rem;">
                            <i class="fa-solid fa-print"></i> Cetak / PDF
                        </button>
                    </div>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <button class="btn btn-outline-warning btn-sm" id="modalWoOvertimeFooterBtn" onclick="window.createSplForCurrentWo()" type="button" style="padding:5px 12px; font-size:0.82rem;" title="Ajukan Surat Perintah Lembur Mekanik untuk WO Ini">
                            <i class="fa-solid fa-business-time"></i> Ajukan SPL Lembur
                        </button>
                        <button class="btn btn-outline-primary btn-sm" id="modalWoAssetBtn" style="padding:5px 12px; font-size:0.82rem;">
                            <i class="fa-solid fa-truck"></i> Detail Aset 360&deg;
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="closeModal('modalWoDetail')" style="padding:5px 14px; font-size:0.82rem;">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Catat Transaksi Biaya Baru (Revisi 9 / BR-09A & Decision BR-09B) -->
        <div class="modal-overlay" id="modalCostInput">
            <div class="modal-content" style="max-width: 620px; width: 95%;">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border);">
                    <h2 style="margin: 0; font-size: 1.25rem;"><i class="fa-solid fa-money-bill-wave" style="color: var(--success);"></i> Catat Transaksi Biaya Baru</h2>
                    <button onclick="closeModal('modalCostInput')" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
                </div>
                <form id="formCostInput" onsubmit="window.submitCostTransaction(event)">
                    <div class="modal-body" style="padding: 20px;">
                        <div class="form-group" style="margin-bottom:12px;">
                            <label style="font-weight:600; font-size:0.88rem;">Unit Alat Berat (Asset) <span class="text-danger">*</span></label>
                            <select id="costInputAssetId" class="form-control" required style="width:100%;">
                                <option value="">-- Pilih Unit Alat --</option>
                            </select>
                        </div>
                        <div class="form-group" style="margin-bottom:12px;">
                            <label style="font-weight:600; font-size:0.88rem;">Work Order Terkait (Opsional)</label>
                            <input type="text" id="costInputWoId" class="form-control" placeholder="Contoh: WO-26-101 (atau kosongkan)">
                        </div>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                            <div class="form-group">
                                <label style="font-weight:600; font-size:0.88rem;">Kategori Biaya <span class="text-danger">*</span></label>
                                <select id="costInputCategory" class="form-control" required>
                                    <option value="Sparepart">Sparepart & Consumable</option>
                                    <option value="Maintenance" selected>Preventive Maintenance</option>
                                    <option value="External">Jasa Bengkel / Outsource</option>
                                    <option value="Capex">Overhaul / Capex</option>
                                    <option value="Fuel">Fuel / Pelumas</option>
                                    <option value="Other">Lain-lain</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label style="font-weight:600; font-size:0.88rem;">Tanggal Transaksi <span class="text-danger">*</span></label>
                                <input type="date" id="costInputDate" class="form-control" required>
                            </div>
                        </div>
                        <div style="display:grid; grid-template-columns:1.2fr 1fr; gap:12px; margin-bottom:12px;">
                            <div class="form-group">
                                <label style="font-weight:600; font-size:0.88rem;">Nominal Biaya (Rp) <span class="text-danger">*</span></label>
                                <input type="number" id="costInputAmount" class="form-control" min="1000" step="1000" placeholder="Contoh: 15000000" required>
                            </div>
                            <div class="form-group">
                                <label style="font-weight:600; font-size:0.88rem;">No. Referensi (PO / SPPU / Invoice)</label>
                                <input type="text" id="costInputRef" class="form-control" placeholder="Contoh: PO-2026-0812">
                            </div>
                        </div>
                        <div class="form-group" style="margin-bottom:12px;">
                            <label style="font-weight:600; font-size:0.88rem;">Vendor / Bengkel / Pihak Penyedia</label>
                            <input type="text" id="costInputVendor" class="form-control" placeholder="Contoh: PT United Tractors / Bengkel Presisi Riau">
                        </div>
                        <div class="form-group" style="margin-bottom:12px;">
                            <label style="font-weight:600; font-size:0.88rem;">Uraian / Deskripsi Pengeluaran <span class="text-danger">*</span></label>
                            <textarea id="costInputDesc" class="form-control" rows="2" placeholder="Uraikan perbaikan atau komponen yang dibeli..." required></textarea>
                        </div>
                        <div class="pk-alert pk-alert-info" style="font-size:0.8rem; padding:8px 12px; margin-top:12px;">
                            <i class="fa-solid fa-circle-info"></i> <strong>Catatan Alur Biaya (BR-09B):</strong> Biaya perbaikan dicatat ke ledger biaya dengan pencegahan double counting. Integrasi otomatis langsung dari PO/SPPU ditandai sebagai <code>[NEEDS DECISION]</code> untuk persetujuan manajemen.
                        </div>
                    </div>
                    <div class="modal-footer" style="padding:15px 20px; display:flex; justify-content:flex-end; gap:10px; border-top:1px solid var(--border);">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('modalCostInput')">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSubmitCostTransaction"><i class="fa-solid fa-floppy-disk"></i> Simpan Transaksi Biaya</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Revisi 4: kebutuhan part dan layanan yang tertaut langsung ke Work Order -->
        <div class="modal-overlay" id="modalWoSupport">
            <div class="modal-content" style="max-width: 920px; width: 96%; max-height: 92vh; overflow-y: auto;">
                <div class="modal-header"
                    style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border);">
                    <h2 style="margin:0; font-size:1.2rem;"><i class="fa-solid fa-screwdriver-wrench"
                            style="color:var(--primary);"></i> Kebutuhan WO <span id="supportWoTitle"></span></h2>
                    <button onclick="closeModal('modalWoSupport')"
                        style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
                </div>
                <div class="modal-body" style="padding:20px;">
                    <input type="hidden" id="supportWoId">
                    <input type="hidden" id="supportAssetId">
                    <div id="supportLoading" style="padding:12px; background:#f8fafc; border-radius:8px; margin-bottom:15px;">
                        Memuat kebutuhan Work Order...
                    </div>

                    <section style="border:1px solid var(--border); border-radius:10px; padding:15px; margin-bottom:16px;">
                        <div style="display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap;">
                            <div>
                                <strong style="display:block;">Keputusan kebutuhan suku cadang</strong>
                                <span id="supportPartsStatus" style="font-size:0.86rem; color:var(--text-muted);">Belum dinilai</span>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="openPartRequestFromSupport()"><i
                                    class="fa-solid fa-boxes-stacked"></i> Buat SPB dari WO</button>
                        </div>
                        <div style="display:flex; gap:8px; margin-top:12px; flex-wrap:wrap;">
                            <input id="supportNoPartsReason" class="form-control" style="flex:1; min-width:260px;"
                                placeholder="Alasan tidak memerlukan suku cadang (wajib)">
                            <button type="button" class="btn btn-secondary" onclick="markWoNoParts()"><i
                                    class="fa-solid fa-circle-check"></i> Tidak Memerlukan Part</button>
                        </div>
                        <div id="supportSpbList" style="margin-top:12px;"></div>
                    </section>

                    <section style="border:1px solid var(--border); border-radius:10px; padding:15px; margin-bottom:16px;">
                        <strong style="display:block; margin-bottom:10px;">Riwayat penggunaan suku cadang pada WO ini</strong>
                        <div id="supportPartUsageList" style="display:grid; gap:8px;"></div>
                    </section>

                    <section style="border:1px solid var(--border); border-radius:10px; padding:15px; margin-bottom:16px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:10px;">
                            <strong style="margin:0;"><i class="fa-solid fa-business-time" style="color:var(--primary);"></i> Surat Perintah Lembur (SPL)</strong>
                            <button type="button" class="btn btn-sm btn-outline-warning" onclick="openSplFromSupport()">
                                <i class="fa-solid fa-file-circle-plus"></i> Buat SPL dari WO
                            </button>
                        </div>
                        <div id="supportSplList" style="display:grid; gap:8px;"></div>
                    </section>

                    <section style="border:1px solid var(--border); border-radius:10px; padding:15px; margin-bottom:16px;">
                        <strong style="display:block; margin-bottom:12px;">Ajukan layanan perbaikan / bengkel</strong>
                        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:10px;">
                            <label>Jenis layanan
                                <select id="supportServiceType" class="form-control">
                                    <option value="INTERNAL_WORKSHOP">Bengkel internal</option>
                                    <option value="EXTERNAL_WORKSHOP">Bengkel eksternal</option>
                                    <option value="FIELD_SERVICE">Layanan lapangan</option>
                                    <option value="SPECIALIST_VENDOR">Vendor spesialis</option>
                                </select>
                            </label>
                            <label>Prioritas
                                <select id="supportServicePriority" class="form-control">
                                    <option>Normal</option><option>Urgent</option><option>Emergency</option>
                                </select>
                            </label>
                            <label>Penyedia / bengkel
                                <input id="supportServiceProvider" class="form-control" placeholder="Opsional">
                            </label>
                            <label>PIC layanan
                                <input id="supportServiceAssignee" class="form-control" placeholder="Opsional">
                            </label>
                        </div>
                        <label style="display:block; margin-top:10px;">Deskripsi pekerjaan
                            <textarea id="supportServiceDescription" class="form-control" rows="3"
                                placeholder="Jelaskan pekerjaan yang diminta..."></textarea>
                        </label>
                        <div style="text-align:right; margin-top:10px;">
                            <button type="button" class="btn btn-success" onclick="submitWoServiceRequest()"><i
                                    class="fa-solid fa-paper-plane"></i> Ajukan Layanan</button>
                        </div>
                    </section>

                    <section style="border:1px solid var(--border); border-radius:10px; padding:15px;">
                        <strong style="display:block; margin-bottom:10px;">Tindak lanjut layanan</strong>
                        <div id="supportServiceList" style="display:grid; gap:10px;"></div>
                    </section>
                </div>
            </div>
        </div>

        <!-- Modal Konfirmasi Perubahan Status WO & Form Laporan (FRM-WO-01) -->
        <div class="modal-overlay" id="modalWoStatusConfirm">
            <div class="modal-content" style="max-width: 650px; width: 95%;">
                <div class="modal-header"
                    style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border);">
                    <h2 style="margin: 0; font-size: 1.2rem;">
                        <i class="fa-solid fa-arrows-rotate" style="color: var(--primary);"></i>
                        Konfirmasi Perubahan Status & Form Laporan (FRM-WO-01)
                    </h2>
                    <button onclick="closeModal('modalWoStatusConfirm')"
                        style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
                </div>
                <div class="modal-body form-group" style="padding: 20px;">
                    <input type="hidden" id="woConfirmId">
                    <input type="hidden" id="woConfirmAssetId">

                    <div
                        style="background: #eef2ff; border: 1px solid #c7d2fe; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 0.88rem;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
                            <span><strong>Work Order ID:</strong> <span id="woConfirmIdLabel"
                                    style="color:var(--primary); font-weight:bold;"></span></span>
                            <span><strong>Asset ID:</strong> <span id="woConfirmAssetLabel"></span></span>
                        </div>
                        <div style="font-size: 0.85rem; color: #3730a3; font-weight:600;">
                            Perubahan Status: <span class="badge bg-draft" id="woConfirmFromStatus"></span> <i
                                class="fa-solid fa-arrow-right"></i> 
                            <select id="woConfirmToStatus" class="form-control" style="display:inline-block; width:auto; padding: 2px 24px 2px 8px; font-size: 0.85rem; height: auto; border-radius: 12px; font-weight: bold; background-color: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe;" onchange="window.updateWoConfirmAutofill()">
                                <option value="Open">Open</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Waiting Part">Waiting Part</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                    </div>

                    <label style="font-weight:bold;">Mekanik / Teknisi Penanggung Jawab (FRM-WO-01)</label>
                    <select id="woConfirmMechanic" class="form-control" style="margin-bottom: 12px;">
                        <option value="Apeng (Lead Mechanic)">Apeng (Lead Mechanic)</option>
                        <option value="Maman (Senior Technician)">Maman (Senior Technician)</option>
                        <option value="Soleh (Hydraulic Specialist)">Soleh (Hydraulic Specialist)</option>
                        <option value="Darmawan (Electrical Mechanic)">Darmawan (Electrical Mechanic)</option>
                        <option value="Regar (Junior Mechanic)">Regar (Junior Mechanic)</option>
                    </select>

                    <label style="font-weight:bold;">Penyebab Kerusakan <span style="color:#dc3545;">*</span></label>
                    <textarea id="woConfirmRootCause" rows="2" class="form-control"
                        placeholder="Wajib diisi sebelum WO ditutup" style="margin-bottom: 12px;"></textarea>

                    <label style="font-weight:bold;">Jenis Pemeliharaan</label>
                    <select id="woConfirmMaintenanceType" class="form-control" style="margin-bottom: 12px;">
                        <option value="Corrective">Corrective</option>
                        <option value="Preventive">Preventive</option>
                        <option value="Inspection">Inspection</option>
                        <option value="Emergency">Emergency</option>
                    </select>

                    <label style="font-weight:bold;">Tindakan Perbaikan & Kelengkapan Sparepart</label>
                    <textarea id="woConfirmAction" rows="3" class="form-control"
                        placeholder="Tuliskan rincian tindakan perbaikan, penggantian sparepart, atau hasil inspeksi..."
                        style="margin-bottom: 12px;"></textarea>

                    <label style="font-weight:bold;">Status Efektif Unit Setelah Perbaikan</label>
                    <select id="woConfirmUnitStatus" class="form-control" style="margin-bottom: 15px;">
                        <option value="READY">READY - Unit Siap Operasi Full</option>
                        <option value="OPERATING">OPERATING - Unit Langsung Beroperasi</option>
                        <option value="STANDBY">STANDBY - Unit Siap Pakai Cadangan</option>
                        <option value="BREAKDOWN">BREAKDOWN - Perbaikan Lanjutan Masih Dibutuhkan</option>
                    </select>

                    <div
                        style="display: flex; gap: 10px; justify-content: flex-end; border-top: 1px solid var(--border); padding-top: 15px;">
                        <button class="btn btn-secondary" onclick="closeModal('modalWoStatusConfirm')">Batal</button>
                        <button class="btn btn-primary" onclick="submitWoStatusChange()"><i
                                class="fa-solid fa-check"></i> Simpan & Update Status WO</button>
                    </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal PM Card Details -->
        <div class="modal-overlay" id="modalPmCardDetails">
            <div class="modal-content" style="max-width: 900px; width: 95%;">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border);">
                    <h2 style="margin: 0; font-size: 1.2rem;" id="pmCardDetailsTitle">
                        <i class="fa-solid fa-list" style="color: var(--primary);"></i>
                        Detail Data
                    </h2>
                    <button onclick="closeModal('modalPmCardDetails')" style="border:none; background:none; font-size:1.5rem; cursor:pointer;">&times;</button>
                </div>
                <div class="modal-body" style="padding: 20px; overflow-x: auto; max-height: 70vh; overflow-y: auto;">
                    <table class="pm-table" style="width: 100%;">
                        <thead>
                            <tr>
                                <th>Unit & Model</th>
                                <th>Target PM</th>
                                <th>HM/KM Terakhir</th>
                                <th>Service Terakhir</th>
                                <th>Selisih</th>
                                <th>Status</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody id="pmCardDetailsBody">
                        </tbody>
                    </table>
                </div>
                <div style="display: flex; justify-content: flex-end; border-top: 1px solid var(--border); padding: 15px 20px; background: #fafafa; border-radius: 0 0 12px 12px;">
                    <button class="btn btn-secondary" onclick="closeModal('modalPmCardDetails')">Tutup</button>
                </div>
            </div>
        </div>

        <!-- Modal Rename Nomor Lambung Aktual (Masukan Demo & Rapat) -->
        <div class="modal-overlay" id="modalRenameHullNumber">
            <div class="modal-content" style="max-width: 480px; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15);">
                <div class="modal-header" style="background: linear-gradient(135deg, #1e293b, #0f172a); color: #fff; padding: 18px 24px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:36px; height:36px; border-radius:8px; background:rgba(245,158,11,0.2); display:flex; align-items:center; justify-content:center; color:#fbbf24;">
                            <i class="fa-solid fa-tag" style="font-size:1.1rem;"></i>
                        </div>
                        <div>
                            <h3 style="margin:0; font-size:1.05rem; color:#fff;">Rename Nomor Lambung</h3>
                            <p style="margin:0; font-size:0.75rem; color:#94a3b8;">Penyesuaian nomor lambung fisik aktual armada lapangan</p>
                        </div>
                    </div>
                    <button onclick="closeModal('modalRenameHullNumber')" style="border:none; background:none; font-size:1.5rem; color:#94a3b8; cursor:pointer;" aria-label="Tutup">&times;</button>
                </div>
                <div class="modal-body" style="padding: 20px 24px;">
                    <input type="hidden" id="renameHullAssetId">
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label style="font-size:0.8rem; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Kode Aset / Unit ID</label>
                        <input type="text" id="renameHullAssetCode" class="form-control" readonly style="background:#f1f5f9; font-weight:600; font-family:var(--font-mono); color:#1e293b;">
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label style="font-size:0.8rem; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Nomor Lambung Terdaftar Saat Ini</label>
                        <input type="text" id="renameHullCurrent" class="form-control font-mono" readonly style="background:#f8fafc; font-style:italic; color:#64748b;">
                    </div>
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label style="font-size:0.8rem; font-weight:700; color:#0f172a; display:block; margin-bottom:4px;">Nomor Lambung Baru (Aktual Lapangan) <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="renameHullNewInput" class="form-control font-mono" placeholder="Contoh: BRA-45, DT-00088, DZ-00005" required style="font-weight:700; color:#0369a1; border:1.5px solid #7dd3fc; background:#f0f9ff;">
                        <small style="color:#64748b; font-size:0.72rem; margin-top:3px; display:block;">Masukkan nomor fisik yang tercetak pada bodi alat berat saat ini.</small>
                    </div>
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label style="font-size:0.8rem; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Alasan Perubahan <span style="color:#ef4444;">*</span></label>
                        <textarea id="renameHullNotes" rows="2" class="form-control" required placeholder="Contoh: Mutasi proyek atau penggantian nomor fisik"></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label style="font-size:0.8rem; font-weight:600; color:#475569; display:block; margin-bottom:4px;">Tanggal Efektif <span style="color:#ef4444;">*</span></label>
                        <input type="date" id="renameHullEffectiveDate" class="form-control" required>
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:10px;">
                        <button type="button" class="btn btn-secondary" onclick="closeModal('modalRenameHullNumber')">Batal</button>
                        <button type="button" class="btn btn-primary" id="btnSubmitRenameHull" onclick="window.submitRenameHull()"><i class="fa-solid fa-floppy-disk"></i> Simpan Nomor Lambung</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Riwayat Perubahan Nomor Lambung Unit Properties -->
        <div class="modal-overlay" id="modalPropHullHistory">
            <div class="modal-content" style="max-width: 540px; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);">
                <div class="modal-header" style="background: linear-gradient(135deg, #1e293b, #0f172a); color: #fff; padding: 16px 20px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:36px; height:36px; border-radius:8px; background:rgba(2,132,199,0.2); display:flex; align-items:center; justify-content:center; color:#38bdf8;">
                            <i class="fa-solid fa-clock-rotate-left" style="font-size:1.1rem;"></i>
                        </div>
                        <div>
                            <h3 style="margin:0; font-size:1.05rem; color:#fff;">Riwayat Perubahan Nomor Lambung</h3>
                            <p style="margin:0; font-size:0.75rem; color:#94a3b8;">Unit: <strong id="mrlAssetTitle" style="color:#38bdf8;">-</strong></p>
                        </div>
                    </div>
                    <button onclick="closeModal('modalPropHullHistory')" style="border:none; background:none; font-size:1.5rem; color:#94a3b8; cursor:pointer;" aria-label="Tutup">&times;</button>
                </div>
                <div class="modal-body" style="padding: 18px 20px; max-height: 65vh; overflow-y: auto;">
                    <div id="mrlHistoryList">Memuat riwayat...</div>
                </div>
                <div class="modal-footer" style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modalPropHullHistory')">Tutup</button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="closeModal('modalPropHullHistory'); window.openPropRenameModal();">
                        <i class="fa-solid fa-pen-to-square"></i> Ubah Nomor Lambung
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Riwayat Penugasan Operator Unit Properties -->
        <div class="modal-overlay" id="modalPropOperatorHistory">
            <div class="modal-content" style="max-width: 680px; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);">
                <div class="modal-header" style="background: linear-gradient(135deg, #1e293b, #0f172a); color: #fff; padding: 16px 20px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:36px; height:36px; border-radius:8px; background:rgba(22,163,74,0.2); display:flex; align-items:center; justify-content:center; color:#4ade80;">
                            <i class="fa-solid fa-user-gear" style="font-size:1.1rem;"></i>
                        </div>
                        <div>
                            <h3 style="margin:0; font-size:1.05rem; color:#fff;">Riwayat Penugasan Operator</h3>
                            <p style="margin:0; font-size:0.75rem; color:#94a3b8;">Unit: <strong id="mroAssetTitle" style="color:#4ade80;">-</strong></p>
                        </div>
                    </div>
                    <button onclick="closeModal('modalPropOperatorHistory')" style="border:none; background:none; font-size:1.5rem; color:#94a3b8; cursor:pointer;" aria-label="Tutup">&times;</button>
                </div>
                <div class="modal-body" style="padding: 18px 20px; max-height: 65vh; overflow-y: auto;">
                    <div id="mroHistoryList">Memuat data operator...</div>
                </div>
                <div class="modal-footer" style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display:flex; justify-content:flex-end;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modalPropOperatorHistory')">Tutup</button>
                </div>
            </div>
        </div>

        <!-- Modal Riwayat Perawatan & Servis PM Unit Properties -->
        <div class="modal-overlay" id="modalPropServiceHistory">
            <div class="modal-content" style="max-width: 760px; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);">
                <div class="modal-header" style="background: linear-gradient(135deg, #1e293b, #0f172a); color: #fff; padding: 16px 20px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:36px; height:36px; border-radius:8px; background:rgba(234,88,12,0.2); display:flex; align-items:center; justify-content:center; color:#fb923c;">
                            <i class="fa-solid fa-wrench" style="font-size:1.1rem;"></i>
                        </div>
                        <div>
                            <h3 style="margin:0; font-size:1.05rem; color:#fff;">Riwayat Perawatan &amp; Servis PM</h3>
                            <p style="margin:0; font-size:0.75rem; color:#94a3b8;">Unit: <strong id="mrsAssetTitle" style="color:#fb923c;">-</strong></p>
                        </div>
                    </div>
                    <button onclick="closeModal('modalPropServiceHistory')" style="border:none; background:none; font-size:1.5rem; color:#94a3b8; cursor:pointer;" aria-label="Tutup">&times;</button>
                </div>
                <div class="modal-body" style="padding: 18px 20px; max-height: 65vh; overflow-y: auto;">
                    <div id="mrsHistoryList">Memuat data servis...</div>
                </div>
                <div class="modal-footer" style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display:flex; justify-content:flex-end;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modalPropServiceHistory')">Tutup</button>
                </div>
            </div>
        </div>

        <!-- Modal Detail Log Insiden / Investigasi Keselamatan Unit Properties -->
        <div class="modal-overlay" id="modalPropIncidentDetail" role="dialog" aria-modal="true" aria-labelledby="mpiTitle" aria-describedby="mpiChronology" onclick="if (event.target === this) window.closeModal('modalPropIncidentDetail')">
            <div class="modal-content prop-incident-modal">
                <div class="prop-incident-modal-header">
                    <div class="prop-incident-modal-heading">
                        <span class="prop-incident-modal-icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
                        <div>
                            <h3 id="mpiTitle">Detail Insiden Keselamatan</h3>
                            <p><strong id="mpiReportNumber">Data belum tersedia</strong><span aria-hidden="true"> &bull; </span><span id="mpiIncidentDate">Data belum tersedia</span></p>
                        </div>
                    </div>
                    <button type="button" class="prop-incident-modal-close" id="mpiCloseButton" onclick="window.closeModal('modalPropIncidentDetail')" aria-label="Tutup detail insiden">&times;</button>
                </div>
                <div class="prop-incident-modal-body">
                    <dl class="prop-incident-detail-grid">
                        <div class="prop-incident-detail-item">
                            <dt><i class="fa-solid fa-location-dot" aria-hidden="true"></i> Lokasi Kejadian</dt>
                            <dd id="mpiLocation">Data belum tersedia</dd>
                        </div>
                        <div class="prop-incident-detail-item">
                            <dt><i class="fa-solid fa-gauge-high" aria-hidden="true"></i> Severity</dt>
                            <dd><span class="prop-incident-severity" id="mpiSeverity">Data belum tersedia</span></dd>
                        </div>
                        <div class="prop-incident-detail-item prop-incident-detail-chronology">
                            <dt><i class="fa-solid fa-align-left" aria-hidden="true"></i> Kronologi Singkat</dt>
                            <dd id="mpiChronology">Data belum tersedia</dd>
                        </div>
                    </dl>
                </div>
                <div class="prop-incident-modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="window.closeModal('modalPropIncidentDetail')">Tutup</button>
                </div>
            </div>
        </div>

        <!-- Modal Popup Dokumen Legalitas (STNK, KIR, SILO, PDF JO) -->
        <div class="modal-overlay" id="modalPropDocumentViewer">
            <div class="modal-content" style="max-width: 520px; border-radius: 12px; overflow: hidden; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);">
                <div class="modal-header" style="background: linear-gradient(135deg, #1e293b, #0f172a); color: #fff; padding: 16px 20px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:36px; height:36px; border-radius:8px; background:rgba(2,132,199,0.2); display:flex; align-items:center; justify-content:center; color:#38bdf8;">
                            <i class="fa-solid fa-file-shield" style="font-size:1.1rem;"></i>
                        </div>
                        <div>
                            <h3 style="margin:0; font-size:1.05rem; color:#fff;" id="mpdDocTitle">Dokumen Legalitas</h3>
                            <p style="margin:0; font-size:0.75rem; color:#94a3b8;">Unit: <strong id="mpdAssetCode" style="color:#38bdf8;">-</strong></p>
                        </div>
                    </div>
                    <button onclick="closeModal('modalPropDocumentViewer')" style="border:none; background:none; font-size:1.5rem; color:#94a3b8; cursor:pointer;" aria-label="Tutup">&times;</button>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px; margin-bottom:16px;">
                        <div style="font-size:0.88rem; font-weight:700; color:#0f172a; margin-bottom:4px;" id="mpdDocDesc">-</div>
                        <div id="mpdExpiryBadge" style="display:inline-flex; align-items:center; font-size:0.8rem; font-weight:600; color:#1e40af; background:#dbeafe; padding:4px 10px; border-radius:20px; margin-top:6px;">
                            <!-- Masa Berlaku -->
                        </div>
                    </div>
                    <div style="font-size:0.78rem; color:#64748b; line-height:1.5;">
                        <i class="fa-solid fa-circle-info" style="color:#0284c7;"></i> Dokumen resmi ini diverifikasi pada saat proses onboarding unit armada dan tersimpan dalam pangkalan data PT Bukit Ringgit Aktiva.
                    </div>
                </div>
                <div class="modal-footer" style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="closeModal('modalPropDocumentViewer')">Tutup</button>
                    <a href="javascript:void(0)" class="btn btn-primary btn-sm" id="mpdActionBtn" style="display:none;">
                        <i class="fa-solid fa-file-pdf"></i> Buka / Unduh Berkas
                    </a>
                </div>
            </div>
        </div>

        <!-- Modal Lightbox Preview Foto Galeri Unit Properties -->
        <div class="modal-overlay" id="modalPropPhotoPreview">
            <div class="modal-content" style="max-width: 680px; border-radius: 12px; overflow: hidden; box-shadow: 0 25px 35px -5px rgba(0, 0, 0, 0.3); background:#0f172a;">
                <div class="modal-header" style="background:#0f172a; color:#fff; padding: 12px 18px; border-bottom:1px solid rgba(255,255,255,0.1);">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span id="mplPhotoAngle" style="font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:12px; background:#0284c7; color:#fff; text-transform:uppercase;">Angle</span>
                        <h4 style="margin:0; font-size:0.95rem; color:#fff;" id="mplPhotoTitle">Foto Unit</h4>
                    </div>
                    <button onclick="closeModal('modalPropPhotoPreview')" style="border:none; background:none; font-size:1.5rem; color:#94a3b8; cursor:pointer;" aria-label="Tutup">&times;</button>
                </div>
                <div class="modal-body" style="padding: 0; background:#020617; display:flex; align-items:center; justify-content:center; min-height:300px; max-height:70vh; overflow:hidden;">
                    <img id="mplPreviewImg" src="" alt="Preview Foto Unit" style="max-width:100%; max-height:70vh; object-fit:contain; display:block;">
                </div>
                <div class="modal-footer" style="padding: 10px 18px; background:#0f172a; border-top:1px solid rgba(255,255,255,0.1); display:flex; justify-content:space-between; align-items:center;">
                    <span id="mplPhotoDate" style="font-size:0.75rem; color:#94a3b8;"></span>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <button type="button" class="btn btn-danger btn-sm" id="mplDeleteBtn" style="display:none; font-size:0.8rem; padding:4px 10px; background:#ef4444; color:#fff; border:none; border-radius:6px; cursor:pointer;">
                            <i class="fa-solid fa-trash"></i> Hapus Foto
                        </button>
                        <a id="mplDownloadBtn" href="" target="_blank" class="btn btn-primary btn-sm" style="font-size:0.8rem; padding:4px 10px;">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> Buka Resolusi Penuh
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Galeri Foto Preventif Maintenance (11 Kolom PM) -->
        <div class="modal-overlay" id="modalPmGallery">
            <div class="modal-content" style="max-width: 720px; border-radius: 12px; overflow: hidden; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);">
                <div class="modal-header" style="background:#0f172a; color:#fff; padding: 16px 22px;">
                    <div>
                        <h3 style="margin:0; font-size:1.05rem; color:#fff;" id="pmGalleryTitle"><i class="fa-solid fa-images" style="color:#38bdf8; margin-right:8px;"></i> Dokumentasi Foto Servis Unit</h3>
                        <p style="margin:0; font-size:0.75rem; color:#94a3b8;" id="pmGallerySubtitle">Dokumentasi fisik penggantian part, oli, dan kondisi komponen</p>
                    </div>
                    <button onclick="closeModal('modalPmGallery')" style="border:none; background:none; font-size:1.5rem; color:#94a3b8; cursor:pointer;" aria-label="Tutup">&times;</button>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    <div id="pmGalleryGrid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:14px; margin-bottom:16px;">
                        <!-- Rendered via JS -->
                    </div>
                    <div id="pmGalleryEmpty" style="display:none; text-align:center; padding:30px; color:#94a3b8;">
                        <i class="fa-regular fa-image" style="font-size:2.5rem; margin-bottom:10px; display:block;"></i>
                        Belum ada foto servis yang diunggah untuk unit ini.
                    </div>
                    <div style="background:#f8fafc; border:1px dashed #cbd5e1; border-radius:8px; padding:14px; text-align:center;">
                        <label for="pmUploadGalleryInput" style="cursor:pointer; display:inline-flex; align-items:center; gap:8px; color:var(--primary); font-weight:600; font-size:0.84rem;">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Unggah Dokumentasi Foto Servis Baru
                        </label>
                        <input type="file" id="pmUploadGalleryInput" accept="image/*" multiple style="display:none;" onchange="window.handleUploadPmPhoto(this.files)">
                        <div style="font-size:0.72rem; color:#64748b; margin-top:4px;">Format JPG, PNG, WebP (maksimal 5MB per foto)</div>
                    </div>
                </div>
                <div style="display:flex; justify-content:flex-end; padding:12px 20px; background:#f8fafc; border-top:1px solid #e2e8f0;">
                    <button class="btn btn-secondary" onclick="closeModal('modalPmGallery')">Tutup</button>
                </div>
            </div>
        </div>

        <!-- Modal Pemberkasan PDF JO Servis Berkala (11 Kolom PM) -->
        <div class="modal-overlay" id="modalPmPdfDoc">
            <div class="modal-content" style="max-width: 600px; border-radius: 12px; overflow: hidden; box-shadow:0 20px 25px -5px rgba(0,0,0,0.2);">
                <div class="modal-header" style="background:#0f172a; color:#fff; padding: 16px 22px;">
                    <div>
                        <h3 style="margin:0; font-size:1.05rem; color:#fff;" id="pmPdfTitle"><i class="fa-solid fa-file-pdf" style="color:#ef4444; margin-right:8px;"></i> Pemberkasan JO Servis Berkala</h3>
                        <p style="margin:0; font-size:0.75rem; color:#94a3b8;" id="pmPdfSubtitle">Dokumen acuan Job Order dan lembar verifikasi teknis servis berkala</p>
                    </div>
                    <button onclick="closeModal('modalPmPdfDoc')" style="border:none; background:none; font-size:1.5rem; color:#94a3b8; cursor:pointer;" aria-label="Tutup">&times;</button>
                </div>
                <div class="modal-body" style="padding: 20px;">
                    <div id="pmPdfCard" style="background:#fff1f2; border:1px solid #fecdd3; border-radius:8px; padding:16px; display:flex; align-items:center; justify-content:space-between; margin-bottom:16px;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <i class="fa-solid fa-file-pdf" style="font-size:2.2rem; color:#e11d48;"></i>
                            <div>
                                <strong id="pmPdfFileName" style="font-size:0.9rem; color:#881337; display:block;">JO-SB-BERKALA-2026.pdf</strong>
                                <span id="pmPdfMeta" style="font-size:0.75rem; color:#9f1239;">Arsip JO Servis Berkala Terlampir &middot; 420 KB</span>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm" id="pmBtnDownloadPdf" style="background:#be123c; color:#fff;" onclick="window.previewPmPdfDoc()"><i class="fa-solid fa-eye"></i> Pratinjau / Download</button>
                    </div>
                    <div style="background:#f8fafc; border:1.5px dashed #cbd5e1; border-radius:8px; padding:18px; text-align:center;">
                        <i class="fa-solid fa-file-arrow-up" style="font-size:1.8rem; color:var(--primary); margin-bottom:8px; display:block;"></i>
                        <label for="pmUploadPdfInput" style="cursor:pointer; display:inline-block; font-weight:600; color:var(--primary); font-size:0.88rem; margin-bottom:4px;">
                            Pilih File PDF Baru untuk Diunggah
                        </label>
                        <input type="file" id="pmUploadPdfInput" accept="application/pdf" style="display:none;" onchange="window.handleUploadPmPdf(this.files)">
                        <div style="font-size:0.75rem; color:#64748b;">Unggah lembar Job Order (JO) servis berkala resmi yang telah ditandatangani pengawas (Format PDF maks. 10MB)</div>
                    </div>
                </div>
                <div style="display:flex; justify-content:flex-end; padding:12px 20px; background:#f8fafc; border-top:1px solid #e2e8f0;">
                    <button class="btn btn-secondary" onclick="closeModal('modalPmPdfDoc')">Tutup</button>
                </div>
            </div>
        </div>

        <!-- Leaflet Map Script -->
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
        <script>
            function escapeHtml(str) {
                if (str === null || str === undefined) return '';
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }
            window.escapeHtml = escapeHtml;

            function getAssetStatusBadge(status) {
                const s = String(status || '').toUpperCase().trim();
                if (s === 'OPERATING') {
                    return '<span class="badge bg-operating" style="display:inline-flex; align-items:center; gap:4px;"><i class="fa-solid fa-play" style="font-size:10px;"></i> OPERATING</span>';
                }
                if (s === 'READY') {
                    return '<span class="badge" style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; font-weight:700; display:inline-flex; align-items:center; gap:4px;"><i class="fa-solid fa-circle-check" style="font-size:10px;"></i> READY</span>';
                }
                if (s === 'STANDBY') {
                    return '<span class="badge bg-standby" style="display:inline-flex; align-items:center; gap:4px;"><i class="fa-solid fa-pause-circle" style="font-size:10px;"></i> STANDBY</span>';
                }
                if (s === 'BREAKDOWN') {
                    return '<span class="badge bg-breakdown" style="display:inline-flex; align-items:center; gap:4px;"><i class="fa-solid fa-triangle-exclamation" style="font-size:10px;"></i> BREAKDOWN</span>';
                }
                if (s === 'INSPEKSI' || s === 'INSPECTION' || s === 'P2H') {
                    return '<span class="badge bg-inspeksi" style="display:inline-flex; align-items:center; gap:4px;"><i class="fa-solid fa-clipboard-check" style="font-size:10px;"></i> INSPECTION</span>';
                }
                if (s.includes('ACCIDENT') || s.includes('HOLD')) {
                    return '<span class="badge" style="background:#fef2f2; color:#991b1b; border:1px solid #fecaca; font-weight:700; display:inline-flex; align-items:center; gap:4px;"><i class="fa-solid fa-hand" style="font-size:10px;"></i> ACCIDENT HOLD</span>';
                }
                return `<span class="badge bg-secondary" style="display:inline-flex; align-items:center; gap:4px;"><i class="fa-solid fa-circle-info" style="font-size:10px;"></i> ${escapeHtml(status)}</span>`;
            }
            window.getAssetStatusBadge = getAssetStatusBadge;

            window.openModal = function (id) {
                const el = document.getElementById(id);
                if (el) {
                    if (id === 'modalNewAsset' || id === 'modalEditAsset') window.loadAssetLocations();
                    if (id === 'modalNewAsset') window.updateNewAssetFormLayout();
                    if (id === 'modalEditAsset') window.updateEditAssetFormLayout();
                    // Overlay yang berada di dalam view tersembunyi tetap berukuran 0x0
                    // walaupun memakai position:fixed. Pindahkan ke body sebelum dibuka.
                    if (el.classList.contains('modal-overlay') && el.parentElement !== document.body) {
                        document.body.appendChild(el);
                    }
                    if (id === 'modalWoStatusConfirm') {
                        document.body.classList.add('wo-confirm-modal-open');
                    }
                    el.classList.add('active');
                    el.style.display = 'flex';
                }
            };
            window.loadAssetLocations = async function() {
                try {
                    const response = await fetch('api/assets.php?references=locations', { cache: 'no-store' });
                    const result = await response.json();
                    if (!response.ok || result.status !== 'success') throw new Error(result.message || 'Lokasi tidak tersedia');
                    for (const id of ['na-loc', 'ea-loc']) {
                        const select = document.getElementById(id);
                        if (!select) continue;
                        const chosen = id === 'na-loc' ? '' : (select.dataset.requestedLocationId || select.value);
                        const requestedProjectName = id === 'ea-loc' ? (select.dataset.requestedProjectName || '') : '';
                        select.replaceChildren();
                        const blank = document.createElement('option'); blank.value = ''; blank.textContent = 'Pilih lokasi'; select.appendChild(blank);
                        for (const location of result.data) {
                            const option = document.createElement('option'); option.value = String(location.location_id); option.textContent = location.location_name; select.appendChild(option);
                        }
                        if (chosen && Array.from(select.options).some(option => option.value === chosen)) {
                            select.value = chosen;
                        } else if (requestedProjectName) {
                            const matched = Array.from(select.options).find(option => option.textContent.trim().toLowerCase() === requestedProjectName.trim().toLowerCase());
                            select.value = matched?.value || '';
                        } else {
                            select.value = '';
                        }
                        if (id === 'ea-loc') {
                            delete select.dataset.requestedLocationId;
                            delete select.dataset.requestedProjectName;
                        }
                    }
                } catch (error) { console.warn('Referensi lokasi gagal dimuat:', error); }
            };

            window.closeModal = function (id) {
                const el = document.getElementById(id);
                if (el) {
                    el.classList.remove('active');
                    el.style.display = 'none';
                    if (id === 'modalWoStatusConfirm') {
                        document.body.classList.remove('wo-confirm-modal-open');
                    }
                }
            };

            window.openSpbForWo = function (woId, assetId) {
                if (window.SpareLogistics?.openFullSpbFormForWorkOrder?.(woId, assetId)) return;
                if (!window.SpareLogistics?.openFullSpbFormForWorkOrder) {
                    window.alert('Form SPB belum siap. Muat ulang halaman lalu coba lagi.');
                }
            };

            const woSupportTypeLabels = {
                INTERNAL_WORKSHOP: 'Bengkel internal',
                EXTERNAL_WORKSHOP: 'Bengkel eksternal',
                FIELD_SERVICE: 'Layanan lapangan',
                SPECIALIST_VENDOR: 'Vendor spesialis'
            };

            async function requestWoSupport(payload, method = 'POST') {
                const url = method === 'GET'
                    ? `api/work_order_support.php?wo_id=${encodeURIComponent(payload.woId)}`
                    : 'api/work_order_support.php';
                const response = await fetch(url, {
                    method,
                    headers: { Accept: 'application/json', ...(method === 'POST' ? { 'Content-Type': 'application/json' } : {}) },
                    cache: 'no-store',
                    ...(method === 'POST' ? { body: JSON.stringify(payload) } : {})
                });
                const result = await response.json();
                if (!response.ok || result.status !== 'success') {
                    throw new Error(result.message || 'Kebutuhan Work Order gagal diproses.');
                }
                return result.data;
            }

            function renderWoSupport(data) {
                const loading = document.getElementById('supportLoading');
                const partsStatus = document.getElementById('supportPartsStatus');
                const spbList = document.getElementById('supportSpbList');
                const serviceList = document.getElementById('supportServiceList');
                const usageList = document.getElementById('supportPartUsageList');
                const workOrder = data?.workOrder || {};
                const requirementLabels = {
                    UNASSESSED: 'Belum dinilai',
                    REQUIRED: 'Memerlukan suku cadang',
                    NOT_REQUIRED: 'Tidak memerlukan suku cadang'
                };
                if (loading) loading.style.display = 'none';
                if (partsStatus) {
                    partsStatus.innerHTML = `<strong>${escapeHtml(requirementLabels[workOrder.partsRequirement] || 'Belum dinilai')}</strong>${workOrder.partsRequirementNote ? ` &mdash; ${escapeHtml(workOrder.partsRequirementNote)}` : ''}`;
                }

                const purchaseRequests = Array.isArray(data?.purchaseRequests) ? data.purchaseRequests : [];
                if (spbList) {
                    spbList.innerHTML = purchaseRequests.length
                        ? purchaseRequests.map(spb => `<div style="padding:9px 11px; background:#f8fafc; border-radius:7px; margin-top:6px; font-size:0.84rem;"><strong>${escapeHtml(spb.spbId)}</strong> &middot; ${escapeHtml(spb.status)} &middot; ${Number(spb.itemCount || 0)} item / ${Number(spb.totalQuantity || 0)} qty${spb.notes ? `<br><span style="color:var(--text-muted);">${escapeHtml(spb.notes)}</span>` : ''}</div>`).join('')
                        : '<div style="font-size:0.84rem; color:var(--text-muted);">Belum ada SPB yang tertaut ke WO ini.</div>';
                }

                if (usageList) {
                    const usage = Array.isArray(data?.partUsage) ? data.partUsage : [];
                    usageList.innerHTML = usage.length
                        ? usage.map(item => `<div style="padding:9px 11px; background:#f8fafc; border-radius:7px; font-size:0.84rem;"><strong>${escapeHtml(item.partNumber)} · ${escapeHtml(item.partName)}</strong><br>${escapeHtml(item.date)} · ${Number(item.quantity)} ${escapeHtml(item.unit)} · Unit ${escapeHtml(item.assetId)} · WO ${escapeHtml(item.woId)}<br><span style="color:var(--text-muted);">Bukti ${escapeHtml(item.referenceNumber)} · Laporan ${escapeHtml(item.reportNumber)}</span></div>`).join('')
                        : '<div style="font-size:0.84rem; color:var(--text-muted);">Belum ada transaksi barang keluar yang tertaut ke WO ini.</div>';
                }

                const splList = document.getElementById('supportSplList');
                if (splList) {
                    const splRecords = Array.isArray(data?.overtimeRecords) ? data.overtimeRecords : [];
                    splList.innerHTML = splRecords.length
                        ? splRecords.map(s => `
                            <div style="padding:9px 11px; background:#f8fafc; border-radius:7px; font-size:0.84rem;">
                                <strong>${escapeHtml(s.splNumber)}</strong> &middot; ${escapeHtml(s.splDate)} &middot; ${escapeHtml(s.employeeName)} (${Number(s.durationHours || 0).toFixed(1)} Jam)<br>
                                <span style="color:var(--text-muted);">${escapeHtml(s.activityDescription || s.generalInstruction || '-')}</span>
                            </div>`).join('')
                        : '<div style="font-size:0.84rem; color:var(--text-muted);">Belum ada SPL lembur yang tertaut ke WO ini.</div>';
                }

                const serviceRequests = Array.isArray(data?.serviceRequests) ? data.serviceRequests : [];
                if (serviceList) {
                    serviceList.innerHTML = serviceRequests.length
                        ? serviceRequests.map(service => {
                            const transitions = {
                                Submitted: ['Assigned', 'In Progress', 'Cancelled'],
                                Assigned: ['In Progress', 'Completed', 'Cancelled'],
                                'In Progress': ['Completed', 'Cancelled'],
                                Completed: [],
                                Cancelled: []
                            }[service.status] || [];
                            const controls = transitions.length ? `<div style="display:flex; gap:7px; margin-top:9px; flex-wrap:wrap;">
                                <input id="supportServiceAssignee-${escapeHtml(service.serviceRequestId)}" class="form-control" style="flex:1; min-width:160px;" value="${escapeHtml(service.assignedTo || '')}" placeholder="PIC layanan">
                                <select id="supportServiceStatus-${escapeHtml(service.serviceRequestId)}" class="form-control" style="width:auto;">${transitions.map(status => `<option>${escapeHtml(status)}</option>`).join('')}</select>
                                <button class="btn btn-primary" type="button" onclick="updateWoServiceStatus('${escapeHtml(service.serviceRequestId)}')">Update</button>
                            </div>` : '<div style="margin-top:7px; color:var(--text-muted); font-size:0.8rem;">Status akhir; tidak ada tindak lanjut berikutnya.</div>';
                            return `<div style="border:1px solid var(--border); padding:11px; border-radius:8px;">
                                <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap;"><strong>${escapeHtml(service.serviceRequestId)}</strong><span class="badge bg-operating">${escapeHtml(service.status)}</span></div>
                                <div style="font-size:0.84rem; margin-top:5px;"><strong>${escapeHtml(woSupportTypeLabels[service.serviceType] || service.serviceType)}</strong> &middot; ${escapeHtml(service.priority)}</div>
                                <div style="font-size:0.84rem; margin-top:4px;">${escapeHtml(service.description)}</div>
                                <div style="font-size:0.8rem; color:var(--text-muted); margin-top:4px;">Penyedia: ${escapeHtml(service.providerName || '-')} &middot; PIC: ${escapeHtml(service.assignedTo || '-')}</div>
                                ${controls}
                            </div>`;
                        }).join('')
                        : '<div style="font-size:0.84rem; color:var(--text-muted);">Belum ada permintaan layanan untuk WO ini.</div>';
                }
            }

            window.loadWoSupport = async function () {
                const woId = document.getElementById('supportWoId')?.value || '';
                if (!woId) return;
                const loading = document.getElementById('supportLoading');
                if (loading) {
                    loading.style.display = 'block';
                    loading.textContent = 'Memuat kebutuhan Work Order...';
                }
                try {
                    renderWoSupport(await requestWoSupport({ woId }, 'GET'));
                } catch (error) {
                    if (loading) loading.textContent = error.message;
                }
            };

            window.openWoSupportModal = function (woId, assetId) {
                document.getElementById('supportWoId').value = woId || '';
                document.getElementById('supportAssetId').value = assetId || '';
                document.getElementById('supportWoTitle').textContent = woId || '-';
                document.getElementById('supportNoPartsReason').value = '';
                openModal('modalWoSupport');
                loadWoSupport();
            };

            window.openPartRequestFromSupport = function () {
                const woId = document.getElementById('supportWoId')?.value || '';
                const assetId = document.getElementById('supportAssetId')?.value || '';
                closeModal('modalWoSupport');
                openSpbForWo(woId, assetId);
            };

            window.openSplFromSupport = function () {
                const woId = document.getElementById('supportWoId')?.value || '';
                const assetId = document.getElementById('supportAssetId')?.value || '';
                closeModal('modalWoSupport');
                if (typeof window.openSplFormForWorkOrder === 'function') {
                    window.openSplFormForWorkOrder(woId, assetId);
                }
            };

            window.markWoNoParts = async function () {
                const woId = document.getElementById('supportWoId')?.value || '';
                const reason = document.getElementById('supportNoPartsReason')?.value.trim() || '';
                if (!reason) return alert('Alasan tidak memerlukan suku cadang wajib diisi.');
                try {
                    const data = await requestWoSupport({ action: 'mark_no_parts', woId, reason });
                    renderWoSupport(data.overview);
                    alert('Keputusan tidak memerlukan suku cadang berhasil disimpan.');
                } catch (error) {
                    alert(error.message);
                }
            };

            window.submitWoServiceRequest = async function () {
                const woId = document.getElementById('supportWoId')?.value || '';
                const description = document.getElementById('supportServiceDescription')?.value.trim() || '';
                if (!description) return alert('Deskripsi layanan wajib diisi.');
                try {
                    const data = await requestWoSupport({
                        action: 'create_service',
                        woId,
                        serviceType: document.getElementById('supportServiceType').value,
                        priority: document.getElementById('supportServicePriority').value,
                        providerName: document.getElementById('supportServiceProvider').value,
                        assignedTo: document.getElementById('supportServiceAssignee').value,
                        description
                    });
                    document.getElementById('supportServiceProvider').value = '';
                    document.getElementById('supportServiceAssignee').value = '';
                    document.getElementById('supportServiceDescription').value = '';
                    renderWoSupport(data.overview);
                    alert('Permintaan layanan berhasil ditautkan ke Work Order.');
                } catch (error) {
                    alert(error.message);
                }
            };

            window.updateWoServiceStatus = async function (serviceRequestId) {
                const status = document.getElementById(`supportServiceStatus-${serviceRequestId}`)?.value || '';
                const assignedTo = document.getElementById(`supportServiceAssignee-${serviceRequestId}`)?.value || '';
                try {
                    const data = await requestWoSupport({ action: 'update_service_status', serviceRequestId, status, assignedTo });
                    renderWoSupport(data.overview);
                } catch (error) {
                    alert(error.message);
                }
            };

            // Shared helpers for Work Order status workflow.

            function getWoById(woId) {
                if (!woId || typeof globalData === 'undefined' || !globalData || !Array.isArray(globalData.work_orders)) {
                    return null;
                }
                return globalData.work_orders.find(wo => wo.woId === woId) || null;
            }

            function normalizeWorkOrder(wo) {
                return {
                    ...wo,
                    woId: String(wo.woId ?? wo.wo_id ?? ''),
                    assetId: String(wo.assetId ?? wo.asset_id ?? ''),
                    issue: String(wo.issue ?? wo.issue_description ?? ''),
                    downtime: String(wo.downtime ?? wo.downtime_formatted ?? ''),
                    status: String(wo.status || 'Open'),
                    priority: String(wo.priority || 'Normal'),
                    maintenanceType: String(wo.maintenanceType ?? wo.maintenance_type ?? 'Corrective'),
                    assignedTo: String(wo.assignedTo ?? wo.assigned_mechanic ?? 'Belum ada PIC'),
                    rootCause: String(wo.rootCause ?? wo.root_cause ?? ''),
                    repairAction: String(wo.repairAction ?? wo.repair_action ?? ''),
                    hullNumber: String(wo.hullNumber ?? wo.hull_number ?? ''),
                    equipmentType: String(wo.equipmentType ?? wo.equipment_type ?? ''),
                    currentHmKm: Number(wo.currentHmKm ?? wo.current_hm_km ?? 0),
                    troubleAt: wo.troubleAt ?? wo.trouble_at ?? null,
                    rawLocation: String(wo.rawLocation ?? wo.raw_location ?? ''),
                    isForcedOperation: Boolean(Number(wo.isForcedOperation ?? wo.is_forced_operation ?? 0)),
                    mitigationAction: String(wo.mitigationAction ?? wo.mitigation_action ?? ''),
                    estimatedCompletionAt: wo.estimatedCompletionAt ?? wo.estimated_completion_at ?? null,
                    beforePhotoUrl: wo.beforePhotoUrl ?? wo.before_photo_url ?? null,
                    reportedAt: wo.reportedAt ?? wo.reported_at ?? null,
                    repairStartedAt: wo.repairStartedAt ?? wo.repair_started_at ?? null,
                    closedAt: wo.closedAt ?? wo.closed_at ?? null,
                    handlingDurationMinutes: Number(wo.handlingDurationMinutes ?? wo.handling_duration_minutes ?? 0),
                    timeline: Array.isArray(wo.timeline) ? wo.timeline : []
                };
            }

            function formatWoDateTime(value) {
                if (!value) return 'Belum tercatat';
                const parsed = new Date(String(value).replace(' ', 'T'));
                return Number.isNaN(parsed.getTime())
                    ? String(value)
                    : parsed.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
            }

            function formatWoDuration(rawMinutes) {
                const minutes = Math.max(0, Number(rawMinutes) || 0);
                const days = Math.floor(minutes / 1440);
                const hours = Math.floor((minutes % 1440) / 60);
                const remainder = Math.floor(minutes % 60);
                return `${days ? `${days} hari ` : ''}${hours} jam ${String(remainder).padStart(2, '0')} menit`;
            }

            function renderWoTimeline(timeline) {
                if (!Array.isArray(timeline) || !timeline.length) {
                    return '<div style="color:var(--text-muted);">Belum ada aktivitas timeline.</div>';
                }
                return timeline.map(item => `
                    <div style="position:relative; padding:0 0 12px 8px;">
                        <strong style="display:block; color:var(--text-main);">${escapeHtml(item.title || 'Aktivitas WO')}</strong>
                        <span style="display:block; color:var(--text-muted); margin:2px 0;">${escapeHtml(formatWoDateTime(item.occurred_at))}${item.actor_name ? ` · ${escapeHtml(item.actor_name)}` : ''}</span>
                        ${item.description ? `<div style="line-height:1.45;">${escapeHtml(item.description)}</div>` : ''}
                    </div>
                `).join('');
            }

            window.handleWoCardClick = function (e, woId, assetId) {
                window.openWoDetailModal(woId, assetId);
            };

            function showWoStatusToast(message) {
                const previousToast = document.getElementById('woStatusToast');
                if (previousToast) previousToast.remove();

                const toast = document.createElement('div');
                toast.id = 'woStatusToast';
                toast.className = 'wo-status-toast';
                toast.innerHTML = `<i class="fa-solid fa-circle-check"></i><span>${escapeHtml(message)}</span>`;
                document.body.appendChild(toast);
                requestAnimationFrame(() => toast.classList.add('active'));
                setTimeout(() => {
                    toast.classList.remove('active');
                    setTimeout(() => toast.remove(), 250);
                }, 3500);
            }

            /*
             * Work Order Drag & Drop Engine v2
             * Satu-satunya engine yang diaktifkan oleh initWorkOrderView.
             * Menggunakan Pointer Events + pointer capture sehingga tidak bergantung
             * pada perilaku native HTML5 drop di dalam container overflow.
             */
            const WorkOrderDragDrop = (() => {
                const VALID_STATUSES = new Set(['Open', 'In Progress', 'Closed']);
                let dragState = null;
                let suppressCardClickUntil = 0;

                function getColumnFromElement(element, board) {
                    if (!element || typeof element.closest !== 'function') return null;
                    const column = element.closest('.kanban-column[data-status]');
                    return column && board.contains(column) ? column : null;
                }

                function getColumnAtPoint(clientX, clientY, board) {
                    if (!Number.isFinite(clientX) || !Number.isFinite(clientY)) return null;
                    const elements = typeof document.elementsFromPoint === 'function'
                        ? document.elementsFromPoint(clientX, clientY)
                        : [document.elementFromPoint(clientX, clientY)];
                    for (const element of elements) {
                        const column = getColumnFromElement(element, board);
                        if (column) return column;
                    }
                    return null;
                }

                function setActiveColumn(board, activeColumn) {
                    board.querySelectorAll('.kanban-column').forEach(column => {
                        column.classList.toggle('wo-drop-target-active', column === activeColumn);
                    });
                }

                function createDragGhost(card) {
                    const rect = card.getBoundingClientRect();
                    const ghost = card.cloneNode(true);
                    ghost.removeAttribute('id');
                    ghost.removeAttribute('onclick');
                    ghost.removeAttribute('draggable');
                    ghost.setAttribute('aria-hidden', 'true');
                    ghost.className = 'kanban-card wo-dnd-ghost';
                    ghost.style.width = `${rect.width}px`;
                    ghost.style.height = `${rect.height}px`;
                    document.body.appendChild(ghost);
                    return ghost;
                }

                function moveGhost(ghost, clientX, clientY) {
                    if (!ghost) return;
                    ghost.style.left = `${clientX + 16}px`;
                    ghost.style.top = `${clientY + 16}px`;
                }

                function cleanupDrag() {
                    if (!dragState) return;
                    const { board, card, ghost, pointerId } = dragState;
                    try {
                        if (card.hasPointerCapture(pointerId)) card.releasePointerCapture(pointerId);
                    } catch (error) { }
                    card.classList.remove('wo-dnd-source');
                    card.setAttribute('aria-grabbed', 'false');
                    if (ghost && ghost.parentNode) ghost.remove();
                    board.classList.remove('wo-dnd-active');
                    board.querySelectorAll('.kanban-column').forEach(column => {
                        column.classList.remove('wo-drop-zone-ready', 'wo-drop-target-active', 'wo-drop-source');
                    });
                    document.body.classList.remove('wo-dnd-body-active');
                }

                function startVisualDrag() {
                    if (!dragState || dragState.dragging) return;
                    dragState.dragging = true;
                    dragState.card.classList.add('wo-dnd-source');
                    dragState.card.setAttribute('aria-grabbed', 'true');
                    dragState.ghost = createDragGhost(dragState.card);
                    dragState.board.classList.add('wo-dnd-active');
                    dragState.board.querySelectorAll('.kanban-column').forEach(column => {
                        column.classList.add('wo-drop-zone-ready');
                        if (column.dataset.status === dragState.sourceStatus) {
                            column.classList.add('wo-drop-source');
                        }
                    });
                    document.body.classList.add('wo-dnd-body-active');
                }

                function openStatusConfirmation(payload, targetStatus) {
                    if (!payload || !VALID_STATUSES.has(targetStatus)) return;
                    const wo = getWoById(payload.woId);
                    if (!wo || wo.status === targetStatus) return;

                    // Tunggu sampai pointer capture dan ghost benar-benar dibersihkan.
                    requestAnimationFrame(() => {
                        closeModal('modalWoDetail');
                        window.openWoStatusConfirmModal(
                            wo.woId,
                            wo.status,
                            targetStatus,
                            wo.assetId || wo.unitId || payload.assetId || '',
                            wo.issue || wo.description || payload.issue || ''
                        );
                    });
                }

                function bind(board) {
                    if (!board || board.dataset.woDndEngine === 'v2') return;
                    board.dataset.woDndEngine = 'v2';

                    board.addEventListener('pointerdown', event => {
                        if (event.button !== 0 || dragState) return;
                        if (event.target.closest('button, a, input, select, textarea')) return;

                        const card = event.target.closest('.kanban-card[data-wo-id]');
                        if (!card || !board.contains(card)) return;
                        const wo = getWoById(card.dataset.woId);
                        if (!wo) return;

                        dragState = {
                            board,
                            card,
                            pointerId: event.pointerId,
                            startX: event.clientX,
                            startY: event.clientY,
                            lastX: event.clientX,
                            lastY: event.clientY,
                            sourceStatus: wo.status,
                            targetStatus: null,
                            dragging: false,
                            ghost: null,
                            payload: {
                                woId: wo.woId,
                                assetId: wo.assetId || wo.unitId || '',
                                issue: wo.issue || wo.description || ''
                            }
                        };

                        try { card.setPointerCapture(event.pointerId); } catch (error) { }
                    }, true);

                    window.addEventListener('pointermove', event => {
                        if (!dragState || event.pointerId !== dragState.pointerId) return;
                        dragState.lastX = event.clientX;
                        dragState.lastY = event.clientY;

                        const movement = Math.hypot(
                            event.clientX - dragState.startX,
                            event.clientY - dragState.startY
                        );
                        if (!dragState.dragging && movement >= 5) startVisualDrag();
                        if (!dragState.dragging) return;

                        event.preventDefault();
                        moveGhost(dragState.ghost, event.clientX, event.clientY);
                        const targetColumn = getColumnAtPoint(event.clientX, event.clientY, dragState.board);
                        dragState.targetStatus = targetColumn ? targetColumn.dataset.status : null;
                        setActiveColumn(dragState.board, targetColumn);
                    }, { capture: true, passive: false });

                    window.addEventListener('pointerup', event => {
                        if (!dragState || event.pointerId !== dragState.pointerId) return;
                        const completedDrag = dragState;
                        const wasDragging = completedDrag.dragging;
                        const releaseColumn = getColumnAtPoint(
                            event.clientX,
                            event.clientY,
                            completedDrag.board
                        );
                        const targetStatus = (releaseColumn && releaseColumn.dataset.status)
                            || completedDrag.targetStatus;

                        cleanupDrag();
                        dragState = null;

                        if (!wasDragging) return;
                        event.preventDefault();
                        event.stopPropagation();
                        suppressCardClickUntil = Date.now() + 350;
                        openStatusConfirmation(completedDrag.payload, targetStatus);
                    }, true);

                    window.addEventListener('pointercancel', event => {
                        if (!dragState || event.pointerId !== dragState.pointerId) return;
                        cleanupDrag();
                        dragState = null;
                        suppressCardClickUntil = Date.now() + 200;
                    }, true);

                    board.addEventListener('click', event => {
                        if (Date.now() >= suppressCardClickUntil) return;
                        if (!event.target.closest('.kanban-card[data-wo-id]')) return;
                        event.preventDefault();
                        event.stopImmediatePropagation();
                    }, true);
                }

                return { bind };
            })();

            window.updateWoConfirmAutofill = function (passedWoId, passedAssetId, passedIssue, passedMechanic) {
                const woId = passedWoId || document.getElementById('woConfirmId').value;
                const assetId = passedAssetId || document.getElementById('woConfirmAssetId').value;
                
                let issue = passedIssue || '';
                let assignedTo = passedMechanic;
                
                if (!issue || !assignedTo) {
                    const wo = getWoById(woId);
                    if (wo) {
                        issue = String(wo.issue || wo.description || '');
                        assignedTo = wo.assignedTo || 'Mekanik';
                    }
                }

                const toStatus = document.getElementById('woConfirmToStatus').value;
                
                // Autofill Mechanic
                const mechanicSelect = document.getElementById('woConfirmMechanic');
                if (mechanicSelect && assignedTo && assignedTo !== 'Pending') {
                    for (let option of mechanicSelect.options) {
                        if (option.value.includes(assignedTo)) {
                            option.selected = true;
                            break;
                        }
                    }
                }
                const currentMechanic = mechanicSelect ? mechanicSelect.value : 'Mekanik';

                // Autofill Unit Effective Status based on WO transition
                const unitStatusSelect = document.getElementById('woConfirmUnitStatus');
                let suggestedUnitStatus = 'BREAKDOWN';
                if (toStatus === 'Closed') suggestedUnitStatus = 'READY';
                else if (toStatus === 'In Progress') suggestedUnitStatus = 'STANDBY';
                else if (toStatus === 'Open') suggestedUnitStatus = 'BREAKDOWN';

                if (unitStatusSelect) unitStatusSelect.value = suggestedUnitStatus;

                // Autofill Action & Repair Log
                const actionText = document.getElementById('woConfirmAction');
                if (actionText) {
                    const issueBrief = issue ? ` (${issue.substring(0, 40)})` : '';
                    if (toStatus === 'Closed') {
                        actionText.value = `[PERBAIKAN SELESAI] Penanganan WO ${woId} pada unit ${assetId}${issueBrief} oleh ${currentMechanic} telah selesai. Unit dinyatakan READY operasional.`;
                    } else if (toStatus === 'In Progress') {
                        actionText.value = `[SEDANG DIPERBAIKI] Pengerjaan WO ${woId} pada unit ${assetId}${issueBrief} sedang ditangani oleh ${currentMechanic}.`;
                    } else if (toStatus === 'Waiting Part') {
                        actionText.value = `[MENUNGGU PART] Pengerjaan WO ${woId} pada unit ${assetId}${issueBrief} ditunda sementara menunggu ketersediaan sparepart.`;
                    } else {
                        actionText.value = `[PENGAJUAN ULANG] WO ${woId} unit ${assetId} dikembalikan ke antrean Open.`;
                    }
                }
            };

            window.openWoStatusConfirmModal = function (woId, fromStatus, toStatus, assetId, issue) {
                const allowedStatuses = ['Open', 'In Progress', 'Waiting Part', 'Closed'];
                const wo = getWoById(woId);
                
                if (!toStatus) toStatus = 'Closed';
                
                if (!wo || !allowedStatuses.includes(toStatus) || wo.status === toStatus) return;

                fromStatus = wo.status;
                assetId = wo.assetId || wo.unitId || assetId || '';
                issue = String(wo.issue || wo.description || issue || '');

                // Tidak boleh ada modal detail lain yang menutupi konfirmasi status.
                document.querySelectorAll('.modal-overlay.active').forEach(modal => {
                    if (modal.id && modal.id !== 'modalWoStatusConfirm') closeModal(modal.id);
                });

                document.getElementById('woConfirmId').value = woId;
                document.getElementById('woConfirmAssetId').value = assetId;

                document.getElementById('woConfirmIdLabel').textContent = woId;
                document.getElementById('woConfirmAssetLabel').textContent = assetId;

                const fromBadge = document.getElementById('woConfirmFromStatus');
                const toSelect = document.getElementById('woConfirmToStatus');
                if (fromBadge) fromBadge.textContent = fromStatus;
                if (toSelect) toSelect.value = toStatus;

                const rootCauseInput = document.getElementById('woConfirmRootCause');
                const maintenanceTypeSelect = document.getElementById('woConfirmMaintenanceType');
                if (rootCauseInput) rootCauseInput.value = wo.rootCause || '';
                if (maintenanceTypeSelect) maintenanceTypeSelect.value = wo.maintenanceType || 'Corrective';

                window.updateWoConfirmAutofill(woId, assetId, issue, wo.assignedTo);

                openModal('modalWoStatusConfirm');
            };

            window.submitWoStatusChange = async function () {
                const woId = document.getElementById('woConfirmId').value;
                const targetStatus = document.getElementById('woConfirmToStatus').value;
                const assetId = document.getElementById('woConfirmAssetId').value;
                const mechanic = document.getElementById('woConfirmMechanic').value;
                const actionLog = document.getElementById('woConfirmAction').value;
                const unitStatus = document.getElementById('woConfirmUnitStatus').value;
                const rootCause = document.getElementById('woConfirmRootCause').value.trim();
                const maintenanceType = document.getElementById('woConfirmMaintenanceType').value;

                if (!globalData || !Array.isArray(globalData.work_orders)) return;

                const wo = getWoById(woId);
                if (!wo) {
                    alert(`Work Order ${woId} tidak ditemukan. Muat ulang data lalu coba kembali.`);
                    return;
                }

                const previousStatus = wo.status;
                if (targetStatus === 'Closed' && !rootCause) {
                    alert('Penyebab kerusakan wajib diisi sebelum Work Order ditutup.');
                    document.getElementById('woConfirmRootCause').focus();
                    return;
                }
                const payload = {
                    wo_id: woId,
                    status: targetStatus,
                    assigned_mechanic: mechanic,
                    root_cause: rootCause,
                    repair_action: actionLog,
                    maintenance_type: maintenanceType
                };

                try {
                    const response = await fetch('./api/work_orders.php', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const res = await response.json();

                    if (res.status === 'success') {
                        wo.status = targetStatus;
                        wo.lastUpdate = new Date().toISOString();
                        wo.assignedTo = mechanic;
                        wo.rootCause = rootCause;
                        wo.repairAction = actionLog;
                        wo.maintenanceType = maintenanceType;
                        wo.statusHistory = Array.isArray(wo.statusHistory) ? wo.statusHistory : [];
                        wo.statusHistory.unshift({
                            fromStatus: previousStatus,
                            toStatus: targetStatus,
                            mechanic,
                            action: actionLog,
                            updatedAt: wo.lastUpdate
                        });
                        // Tempatkan WO terbaru di urutan pertama agar perpindahannya langsung
                        // terlihat, termasuk pada kolom Closed yang berisi ratusan card.
                        const updatedWoIndex = globalData.work_orders.findIndex(item => item.woId === woId);
                        if (updatedWoIndex > 0) {
                            globalData.work_orders.splice(updatedWoIndex, 1);
                            globalData.work_orders.unshift(wo);
                        }

                        // Sync integrated status for Asset & Monitoring Unit
                        if (assetId && globalData.assets) {
                            const updatedAsset = setIntegratedAssetStatus(assetId, unitStatus, 'Work Order', woId, actionLog);
                            if (updatedAsset) updatedAsset.syncExpectedStatus = unitStatus;
                        }

                        closeModal('modalWoStatusConfirm');

                        // Synchronize state and update all UI views instantly
                        window.syncFleetState();

                        const movedCard = document.getElementById(`wo-card-${woId}`);
                        if (movedCard) {
                            movedCard.classList.add('status-change-success');
                            movedCard.scrollIntoView({ behavior: 'auto', block: 'center', inline: 'center' });
                            setTimeout(() => movedCard.classList.remove('status-change-success'), 3200);
                        }

                        showWoStatusToast(`${woId} berhasil dipindahkan ke ${targetStatus}`);
                    } else {
                        alert('Gagal update WO: ' + res.message);
                    }
                } catch (e) {
                    console.error(e);
                    alert('Gagal terhubung ke server.');
                }
            };

            if (!window.formatAssetIdentityDetails) {
                window.formatAssetIdentityDetails = function(asset) {
                    if (!asset) return { unitId: '-', hull: '-', sn: 'Belum tersedia', plate: 'Belum tersedia', model: 'Belum tersedia', year: 'Belum tersedia' };
                    const parsed = window.parseAssetId ? window.parseAssetId(asset.id || asset.asset_id || '') : { unitId: asset.id, snPlat: '-' };
                    const unitId = asset.asset_code || parsed.unitId || asset.id || '-';
                    
                    let sn = asset.serial_number ? String(asset.serial_number).trim() : '';
                    if ((!sn || sn === '-') && parsed.snPlat && parsed.snPlat !== '-') {
                        sn = parsed.snPlat;
                    }
                    const snText = (sn && sn !== '-') ? sn : 'Belum tersedia';

                    const plate = asset.license_plate ? String(asset.license_plate).trim() : '';
                    const plateText = (plate && plate !== '-') ? plate : 'Belum tersedia';

                    const model = asset.make_model ? String(asset.make_model).trim() : '';
                    const modelText = (model && model !== '-') ? model : 'Belum tersedia';

                    const year = asset.year_manufacture ? parseInt(asset.year_manufacture, 10) : 0;
                    const yearText = (year && year >= 1970) ? String(year) : 'Belum tersedia';

                    let hull = asset.hull_number || asset.alias_name || '';
                    if (!hull && window.assetDbMapping) {
                        hull = window.assetDbMapping[unitId] || window.assetDbMapping[asset.id] || '';
                    }
                    const hullText = (hull && hull !== '-') ? hull : '-';

                    return {
                        unitId,
                        hull: hullText,
                        sn: snText,
                        plate: plateText,
                        model: modelText,
                        year: yearText
                    };
                };
            }

            window.switchWoDetailTab = function (tabId) {
                const modal = document.getElementById('modalWoDetail');
                if (!modal) return;
                modal.querySelectorAll('.wo-tab-btn').forEach(btn => {
                    const match = btn.getAttribute('onclick')?.includes(tabId);
                    btn.classList.toggle('active', !!match);
                });
                modal.querySelectorAll('.wo-tab-pane').forEach(pane => {
                    pane.classList.toggle('active', pane.id === tabId);
                });
            };

            window.toggleWoServiceForm = function () {
                const form = document.getElementById('woServiceFormCollapse');
                if (form) {
                    form.style.display = form.style.display === 'none' || form.style.display === '' ? 'block' : 'none';
                }
            };

            window.submitModalWoServiceRequest = async function () {
                const woId = document.getElementById('modalWoId')?.textContent?.trim() || '';
                const serviceType = document.getElementById('modalWoServiceType')?.value || 'INTERNAL_WORKSHOP';
                const priority = document.getElementById('modalWoServicePriority')?.value || 'Normal';
                const providerName = document.getElementById('modalWoServiceProvider')?.value?.trim() || '';
                const assignedTo = document.getElementById('modalWoServiceAssignee')?.value?.trim() || '';
                const description = document.getElementById('modalWoServiceDesc')?.value?.trim() || '';

                if (!woId) return alert('ID Work Order tidak valid.');
                if (!description) return alert('Deskripsi perbaikan/pekerjaan bengkel wajib diisi.');

                try {
                    await requestWoSupport({
                        action: 'create_service',
                        woId,
                        serviceType,
                        priority,
                        providerName,
                        assignedTo,
                        description
                    });
                    alert('Permintaan layanan workshop berhasil dikirim.');
                    if (document.getElementById('modalWoServiceDesc')) document.getElementById('modalWoServiceDesc').value = '';
                    if (document.getElementById('modalWoServiceProvider')) document.getElementById('modalWoServiceProvider').value = '';
                    if (document.getElementById('modalWoServiceAssignee')) document.getElementById('modalWoServiceAssignee').value = '';
                    window.toggleWoServiceForm();
                    window.openWoDetailModal(woId);
                } catch (err) {
                    alert('Gagal mengirim permintaan layanan: ' + err.message);
                }
            };

            window.updateModalWoServiceStatus = async function (serviceRequestId) {
                const assignee = document.getElementById(`srvAssignee-${serviceRequestId}`)?.value.trim() || '';
                const status = document.getElementById(`srvStatus-${serviceRequestId}`)?.value || '';
                const woId = document.getElementById('modalWoId')?.textContent?.trim() || '';
                try {
                    await requestWoSupport({
                        action: 'update_service_status',
                        serviceRequestId,
                        status,
                        assignedTo: assignee
                    });
                    alert(`Status layanan ${serviceRequestId} berhasil diperbarui.`);
                    if (woId) window.openWoDetailModal(woId);
                } catch (e) {
                    alert('Gagal memperbarui status: ' + e.message);
                }
            };

            function renderWoCommandCenterSupport(wo, support) {
                const pipeline = support.procurementPipeline || { overallStatus: 'NO_PARTS', totalSpb: 0, totalItems: 0, ordered: 0, received: 0 };
                const statusBadge = document.getElementById('modalWoPipelineStatusBadge');
                if (statusBadge) {
                    const statusLabels = {
                        NO_PARTS: 'Tanpa Kebutuhan Part',
                        DRAFT: 'SPB Draf',
                        SUBMITTED: 'SPB Diajukan',
                        APPROVED: 'SPB Disetujui',
                        ORDERED: 'Dalam Pemesanan (PO/PPB)',
                        PARTIALLY_RECEIVED: 'Sebagian Diterima Gudang',
                        RECEIVED: 'Barang Tiba di Gudang',
                        ISSUED: 'Suku Cadang Dikeluarkan'
                    };
                    statusBadge.textContent = statusLabels[pipeline.overallStatus] || pipeline.overallStatus;
                    statusBadge.className = `badge ${['RECEIVED', 'ISSUED'].includes(pipeline.overallStatus) ? 'bg-operating' : (['ORDERED', 'APPROVED'].includes(pipeline.overallStatus) ? 'bg-standby' : 'bg-secondary')}`;
                }

                const statusWeight = {
                    NO_PARTS: 0,
                    DRAFT: 1,
                    SUBMITTED: 2,
                    APPROVED: 3,
                    ORDERED: 4,
                    PARTIALLY_RECEIVED: 4.5,
                    RECEIVED: 5,
                    ISSUED: 6
                }[pipeline.overallStatus] || 0;

                const stepSpb = document.getElementById('stepSpb');
                const lineSpbApprove = document.getElementById('lineSpbApprove');
                const stepApprove = document.getElementById('stepApprove');
                const lineApprovePpb = document.getElementById('lineApprovePpb');
                const stepPpb = document.getElementById('stepPpb');
                const linePpbReceipt = document.getElementById('linePpbReceipt');
                const stepReceipt = document.getElementById('stepReceipt');
                const lineReceiptIssued = document.getElementById('lineReceiptIssued');
                const stepIssued = document.getElementById('stepIssued');

                if (stepSpb) stepSpb.className = `procurement-step ${statusWeight >= 2 ? 'completed' : (statusWeight >= 1 ? 'active' : '')}`;
                if (lineSpbApprove) lineSpbApprove.className = `procurement-step-line ${statusWeight >= 3 ? 'completed' : ''}`;
                if (stepApprove) stepApprove.className = `procurement-step ${statusWeight >= 3 ? 'completed' : ''}`;
                if (lineApprovePpb) lineApprovePpb.className = `procurement-step-line ${statusWeight >= 4 ? 'completed' : ''}`;
                if (stepPpb) stepPpb.className = `procurement-step ${statusWeight >= 4 ? 'completed' : ''}`;
                if (linePpbReceipt) linePpbReceipt.className = `procurement-step-line ${statusWeight >= 5 ? 'completed' : ''}`;
                if (stepReceipt) stepReceipt.className = `procurement-step ${statusWeight >= 5 ? 'completed' : ''}`;
                if (lineReceiptIssued) lineReceiptIssued.className = `procurement-step-line ${statusWeight >= 6 ? 'completed' : ''}`;
                if (stepIssued) stepIssued.className = `procurement-step ${statusWeight >= 6 ? 'completed' : ''}`;

                const reqStatusEl = document.getElementById('modalWoPartsReqStatus');
                const reqNoteEl = document.getElementById('modalWoPartsReqNote');
                const partsReq = support.workOrder?.partsRequirement || wo.partsRequirement || 'UNASSESSED';
                const partsReqLabels = {
                    UNASSESSED: '<span style="color:#64748b;"><i class="fa-solid fa-circle-question"></i> Belum Dinilai</span>',
                    REQUIRED: '<span style="color:var(--danger);"><i class="fa-solid fa-circle-exclamation"></i> Memerlukan Suku Cadang</span>',
                    NOT_REQUIRED: '<span style="color:var(--success);"><i class="fa-solid fa-circle-check"></i> Tidak Memerlukan Suku Cadang</span>'
                };
                if (reqStatusEl) reqStatusEl.innerHTML = partsReqLabels[partsReq] || partsReq;
                if (reqNoteEl) reqNoteEl.textContent = support.workOrder?.partsRequirementNote || '';

                const btnCreateSpb = document.getElementById('btnWoCreateSpb');
                const btnMarkNoParts = document.getElementById('btnWoMarkNoParts');
                if (btnCreateSpb) {
                    btnCreateSpb.onclick = () => {
                        closeModal('modalWoDetail');
                        openSpbForWo(wo.woId, wo.assetId);
                    };
                }
                if (btnMarkNoParts) {
                    btnMarkNoParts.onclick = async () => {
                        const reason = prompt('Masukkan alasan perbaikan tidak memerlukan suku cadang:');
                        if (!reason || !reason.trim()) return;
                        try {
                            await requestWoSupport({ action: 'mark_no_parts', woId: wo.woId, reason: reason.trim() });
                            alert('Keputusan berhasil disimpan.');
                            window.openWoDetailModal(wo.woId);
                        } catch (e) {
                            alert('Gagal: ' + e.message);
                        }
                    };
                }

                // SPB List
                const spbContainer = document.getElementById('modalWoSpbContainer');
                const spbTabBadge = document.getElementById('modalWoPartsTabBadge');
                const purchaseRequests = Array.isArray(support.purchaseRequests) ? support.purchaseRequests : [];
                if (spbTabBadge) spbTabBadge.textContent = purchaseRequests.length;

                if (spbContainer) {
                    if (!purchaseRequests.length) {
                        spbContainer.innerHTML = `
                            <div style="background:#f8fafc; border:1px dashed #cbd5e1; border-radius:8px; padding:18px; text-align:center; color:var(--text-muted); font-size:0.86rem;">
                                <i class="fa-solid fa-boxes-stacked" style="font-size:1.5rem; margin-bottom:6px; display:block; opacity:0.6;"></i>
                                Belum ada Surat Permintaan Barang (SPB) yang diajukan untuk Work Order ini.
                                <div style="margin-top:8px;">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="closeModal('modalWoDetail'); openSpbForWo('${escapeHtml(wo.woId)}', '${escapeHtml(wo.assetId)}')">
                                        <i class="fa-solid fa-plus"></i> Buat SPB Sekarang
                                    </button>
                                </div>
                            </div>`;
                    } else {
                        spbContainer.innerHTML = purchaseRequests.map(spb => {
                            const items = Array.isArray(spb.items) ? spb.items : [];
                            const itemsHtml = items.length ? `
                                <div style="margin-top:10px; border-top:1px solid #e2e8f0; padding-top:8px;">
                                    <table style="width:100%; border-collapse:collapse; font-size:0.8rem;">
                                        <thead>
                                            <tr style="color:#64748b; text-align:left; border-bottom:1px solid #f1f5f9;">
                                                <th style="padding:4px 6px;">Part Number</th>
                                                <th style="padding:4px 6px;">Deskripsi Item</th>
                                                <th style="padding:4px 6px; text-align:center;">Qty Minta</th>
                                                <th style="padding:4px 6px; text-align:center;">Stok Gudang</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${items.map(item => {
                                                const reqQty = Number(item.qtyRequested || 0);
                                                const stockQty = Number(item.currentStock || 0);
                                                const isStockSufficient = stockQty >= reqQty;
                                                const stockBadge = isStockSufficient
                                                    ? `<span class="badge part-stock-badge-good"><i class="fa-solid fa-check"></i> Tersedia (${stockQty})</span>`
                                                    : `<span class="badge part-stock-badge-low"><i class="fa-solid fa-triangle-exclamation"></i> Kurang (${stockQty})</span>`;
                                                return `
                                                    <tr style="border-bottom:1px solid #f8fafc;">
                                                        <td style="padding:5px 6px; font-family:var(--font-mono); font-weight:600;">
                                                            <a href="javascript:void(0)" class="clickable-part-link" onclick="closeModal('modalWoDetail'); showView('logistics', '', 'menu-logistics');">${escapeHtml(item.partNumber || '-')}</a>
                                                        </td>
                                                        <td style="padding:5px 6px;">${escapeHtml(item.partName || '-')}</td>
                                                        <td style="padding:5px 6px; text-align:center; font-weight:600;">${reqQty} ${escapeHtml(item.unit || '')}</td>
                                                        <td style="padding:5px 6px; text-align:center;">${stockBadge}</td>
                                                    </tr>`;
                                            }).join('')}
                                        </tbody>
                                    </table>
                                </div>` : '';

                            return `
                                <div class="spb-card-item">
                                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <strong style="color:var(--primary); font-size:0.92rem;">${escapeHtml(spb.spbId)}</strong>
                                            <span class="badge ${['Issued', 'Closed'].includes(spb.status) ? 'bg-operating' : (['Approved', 'Process'].includes(spb.status) ? 'bg-standby' : 'bg-secondary')}">${escapeHtml(spb.status)}</span>
                                            <span style="font-size:0.78rem; color:#64748b;">Diajukan: ${formatWoDateTime(spb.requestedAt)}</span>
                                        </div>
                                        <div style="font-size:0.8rem; color:#64748b;">
                                            <span>${Number(spb.itemCount || 0)} Jenis Item &bull; ${Number(spb.totalQuantity || 0)} Total Qty</span>
                                        </div>
                                    </div>
                                    ${spb.notes ? `<div style="font-size:0.82rem; color:#475569; margin-top:6px; background:#f1f5f9; padding:6px 10px; border-radius:4px;"><i class="fa-solid fa-note-sticky" style="color:#64748b; margin-right:4px;"></i>${escapeHtml(spb.notes)}</div>` : ''}
                                    ${itemsHtml}
                                </div>`;
                        }).join('');
                    }
                }

                // PPB List
                const ppbContainer = document.getElementById('modalWoPpbContainer');
                const purchaseOrders = Array.isArray(support.purchaseOrders) ? support.purchaseOrders : [];
                if (ppbContainer) {
                    if (!purchaseOrders.length) {
                        ppbContainer.innerHTML = '<div style="color:var(--text-muted); font-size:0.84rem; padding:8px 0;">Belum ada Purchase Order (PPB) yang diterbitkan untuk SPB WO ini.</div>';
                    } else {
                        ppbContainer.innerHTML = purchaseOrders.map(ppb => `
                            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-bottom:8px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px;">
                                    <div>
                                        <strong style="color:var(--info); font-size:0.88rem;">${escapeHtml(ppb.ppbId)}</strong>
                                        <span class="badge ${ppb.status === 'Received' ? 'bg-operating' : 'bg-standby'}" style="margin-left:6px; font-size:0.72rem;">${escapeHtml(ppb.status)}</span>
                                        <span style="font-size:0.78rem; color:#64748b; margin-left:8px;">SPB Ref: ${escapeHtml(ppb.spbId || '-')}</span>
                                    </div>
                                    <div style="font-size:0.82rem; font-weight:700; color:#0f172a;">
                                        ${ppb.formattedTotalCost || `Rp ${Number(ppb.totalCost || 0).toLocaleString('id-ID')}`}
                                    </div>
                                </div>
                                <div style="font-size:0.8rem; color:#64748b; margin-top:4px;">
                                    Supplier/Vendor: <strong>${escapeHtml(ppb.supplierName || '-')}</strong> &bull; Estimasi Tiba: ${ppb.estimatedDeliveryDate || '-'}
                                </div>
                            </div>`).join('');
                    }
                }

                // Part Usage List
                const usageContainer = document.getElementById('modalWoUsageContainer');
                const partUsage = Array.isArray(support.partUsage) ? support.partUsage : [];
                if (usageContainer) {
                    if (!partUsage.length) {
                        usageContainer.innerHTML = '<div style="color:var(--text-muted); font-size:0.84rem; padding:8px 0;">Belum ada pengeluaran suku cadang fisik dari gudang (Inventory OUT) untuk WO ini.</div>';
                    } else {
                        usageContainer.innerHTML = `
                            <table style="width:100%; border-collapse:collapse; font-size:0.82rem; background:#ffffff; border:1px solid #e2e8f0; border-radius:6px; overflow:hidden;">
                                <thead>
                                    <tr style="background:#f8fafc; color:#475569; text-align:left; border-bottom:1px solid #e2e8f0;">
                                        <th style="padding:8px 10px;">Tanggal</th>
                                        <th style="padding:8px 10px;">Part Number</th>
                                        <th style="padding:8px 10px;">Nama Part</th>
                                        <th style="padding:8px 10px; text-align:center;">Qty</th>
                                        <th style="padding:8px 10px;">No. Referensi / BKB</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${partUsage.map(u => `
                                        <tr style="border-bottom:1px solid #f1f5f9;">
                                            <td style="padding:7px 10px; color:#64748b;">${escapeHtml(u.date || '-')}</td>
                                            <td style="padding:7px 10px; font-family:var(--font-mono); font-weight:600;">${escapeHtml(u.partNumber || '-')}</td>
                                            <td style="padding:7px 10px;">${escapeHtml(u.partName || '-')}</td>
                                            <td style="padding:7px 10px; text-align:center; font-weight:700; color:var(--success);">${Number(u.quantity || 0)} ${escapeHtml(u.unit || '')}</td>
                                            <td style="padding:7px 10px; font-size:0.78rem; color:#64748b;">${escapeHtml(u.referenceNumber || u.reportNumber || '-')}</td>
                                        </tr>`).join('')}
                                </tbody>
                            </table>`;
                    }
                }

                // Services List
                const servicesContainer = document.getElementById('modalWoServicesContainer');
                const servicesTabBadge = document.getElementById('modalWoServicesTabBadge');
                const serviceRequests = Array.isArray(support.serviceRequests) ? support.serviceRequests : [];
                if (servicesTabBadge) servicesTabBadge.textContent = serviceRequests.length;

                if (servicesContainer) {
                    if (!serviceRequests.length) {
                        servicesContainer.innerHTML = '<div style="color:var(--text-muted); font-size:0.86rem; padding:18px; text-align:center; background:#f8fafc; border-radius:8px; border:1px dashed #cbd5e1;">Belum ada pengajuan layanan bengkel/workshop untuk WO ini.</div>';
                    } else {
                        servicesContainer.innerHTML = serviceRequests.map(srv => {
                            const transitions = {
                                Submitted: ['Assigned', 'In Progress', 'Cancelled'],
                                Assigned: ['In Progress', 'Completed', 'Cancelled'],
                                'In Progress': ['Completed', 'Cancelled'],
                                Completed: [],
                                Cancelled: []
                            }[srv.status] || [];

                            const statusColors = {
                                Submitted: 'bg-secondary',
                                Assigned: 'bg-standby',
                                'In Progress': 'bg-breakdown',
                                Completed: 'bg-operating',
                                Cancelled: 'bg-secondary'
                            };

                            const controls = transitions.length ? `
                                <div style="display:flex; gap:8px; margin-top:10px; flex-wrap:wrap; align-items:center;">
                                    <input id="srvAssignee-${escapeHtml(srv.serviceRequestId)}" class="form-control" style="flex:1; min-width:140px; font-size:0.8rem; padding:4px 8px;" value="${escapeHtml(srv.assignedTo || '')}" placeholder="PIC Teknisi">
                                    <select id="srvStatus-${escapeHtml(srv.serviceRequestId)}" class="form-control" style="width:auto; font-size:0.8rem; padding:4px 8px;">
                                        ${transitions.map(s => `<option value="${escapeHtml(s)}">${escapeHtml(s)}</option>`).join('')}
                                    </select>
                                    <button class="btn btn-sm btn-primary" type="button" onclick="updateModalWoServiceStatus('${escapeHtml(srv.serviceRequestId)}')">Simpan</button>
                                </div>` : '<div style="margin-top:6px; color:#64748b; font-size:0.78rem;"><i class="fa-solid fa-lock"></i> Layanan selesai / status final.</div>';

                            return `
                                <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:8px; padding:14px; margin-bottom:10px;">
                                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:6px;">
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <strong style="color:var(--primary); font-size:0.88rem;">${escapeHtml(srv.serviceRequestId)}</strong>
                                            <span class="badge ${statusColors[srv.status] || 'bg-secondary'}" style="font-size:0.72rem;">${escapeHtml(srv.status)}</span>
                                            <span class="badge bg-secondary" style="font-size:0.7rem;">${escapeHtml(woSupportTypeLabels[srv.serviceType] || srv.serviceType)}</span>
                                        </div>
                                        <span style="font-size:0.78rem; font-weight:600; color:${srv.priority === 'Emergency' ? '#dc2626' : '#2563eb'};">${escapeHtml(srv.priority)}</span>
                                    </div>
                                    <div style="font-size:0.84rem; color:#1e293b; line-height:1.4; margin-bottom:6px;">${escapeHtml(srv.description)}</div>
                                    <div style="font-size:0.8rem; color:#64748b;">Penyedia: <strong>${escapeHtml(srv.providerName || '-')}</strong> &bull; PIC: <strong>${escapeHtml(srv.assignedTo || '-')}</strong></div>
                                    ${controls}
                                </div>`;
                        }).join('');
                    }
                }

                // Costs List
                const costsContainer = document.getElementById('modalWoCostsContainer');
                const costsTabBadge = document.getElementById('modalWoCostsTabBadge');
                const totalFormattedEl = document.getElementById('modalWoCostTotalFormatted');
                const addCostBtn = document.getElementById('modalWoAddCostBtn');

                if (addCostBtn) {
                    addCostBtn.onclick = () => openCostInputModal(wo.assetId, wo.woId);
                }

                const costsData = support.costs || {};
                const costItems = Array.isArray(costsData.items) ? costsData.items : [];
                if (costsTabBadge) costsTabBadge.textContent = costItems.length;
                if (totalFormattedEl) totalFormattedEl.textContent = costsData.formattedTotalCost || 'Rp 0';

                if (costsContainer) {
                    if (!costItems.length) {
                        costsContainer.innerHTML = `
                            <div style="color:var(--text-muted); font-size:0.86rem; padding:18px; text-align:center; background:#f8fafc; border-radius:8px; border:1px dashed #cbd5e1;">
                                Belum ada transaksi biaya perbaikan yang dicatat untuk WO ini.
                            </div>`;
                    } else {
                        costsContainer.innerHTML = `
                            <table style="width:100%; border-collapse:collapse; font-size:0.82rem; background:#ffffff; border:1px solid #e2e8f0; border-radius:6px; overflow:hidden;">
                                <thead>
                                    <tr style="background:#f8fafc; color:#475569; text-align:left; border-bottom:1px solid #e2e8f0;">
                                        <th style="padding:8px 10px;">Tanggal</th>
                                        <th style="padding:8px 10px;">Kategori</th>
                                        <th style="padding:8px 10px;">Kode Biaya</th>
                                        <th style="padding:8px 10px;">Deskripsi Transaksi</th>
                                        <th style="padding:8px 10px; text-align:right;">Nominal (Rp)</th>
                                        <th style="padding:8px 10px;">No. Bukti</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${costItems.map(c => `
                                        <tr style="border-bottom:1px solid #f1f5f9;">
                                            <td style="padding:7px 10px; color:#64748b;">${escapeHtml(c.transactionDate || c.date || '-')}</td>
                                            <td style="padding:7px 10px;"><span class="badge bg-secondary" style="font-size:0.7rem;">${escapeHtml(c.costCategory || c.category || '-')}</span></td>
                                            <td style="padding:7px 10px; font-family:var(--font-mono); font-weight:600;">${escapeHtml(c.costCode || '-')}</td>
                                            <td style="padding:7px 10px;">${escapeHtml(c.description || '-')}</td>
                                            <td style="padding:7px 10px; text-align:right; font-weight:700; color:#0f172a;">${c.formattedAmount || `Rp ${Number(c.amount || 0).toLocaleString('id-ID')}`}</td>
                                            <td style="padding:7px 10px; font-size:0.78rem; color:#64748b;">${escapeHtml(c.referenceNumber || '-')}</td>
                                        </tr>`).join('')}
                                </tbody>
                            </table>`;
                    }
                }

                // Overtime (SPL) List
                const overtimeContainer = document.getElementById('modalWoOvertimeContainer');
                const overtimeTabBadge = document.getElementById('modalWoOvertimeTabBadge');
                const splRecords = Array.isArray(support.overtimeRecords) ? support.overtimeRecords : [];
                if (overtimeTabBadge) overtimeTabBadge.textContent = splRecords.length;

                if (overtimeContainer) {
                    if (!splRecords.length) {
                        overtimeContainer.innerHTML = `
                            <div style="text-align:center; padding:24px; color:var(--text-muted); background:#f8fafc; border-radius:8px; border:1px dashed #cbd5e1;">
                                <i class="fa-solid fa-business-time" style="font-size:2rem; color:#cbd5e1; margin-bottom:8px; display:block;"></i>
                                Belum ada Surat Perintah Lembur (SPL) yang diterbitkan untuk pekerjaan WO ini.<br>
                                <small>Gunakan tombol <strong>"Buat SPL Lembur"</strong> di atas jika perbaikan unit memerlukan waktu kerja lembur mekanik.</small>
                            </div>`;
                    } else {
                        overtimeContainer.innerHTML = `
                            <table style="width:100%; border-collapse:collapse; font-size:0.82rem; background:#ffffff; border:1px solid #e2e8f0; border-radius:6px; overflow:hidden;">
                                <thead>
                                    <tr style="background:#f8fafc; color:#475569; text-align:left; border-bottom:1px solid #e2e8f0;">
                                        <th style="padding:8px 10px;">No. SPL</th>
                                        <th style="padding:8px 10px;">Tanggal</th>
                                        <th style="padding:8px 10px;">Personel (Role)</th>
                                        <th style="padding:8px 10px;">Jam Kerja</th>
                                        <th style="padding:8px 10px; text-align:center;">Durasi</th>
                                        <th style="padding:8px 10px;">Verifikasi</th>
                                        <th style="padding:8px 10px;">Uraian Pekerjaan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${splRecords.map(s => `
                                        <tr style="border-bottom:1px solid #f1f5f9;">
                                            <td style="padding:7px 10px; font-family:var(--font-mono); font-weight:700; color:var(--primary);">${escapeHtml(s.splNumber || '-')}</td>
                                            <td style="padding:7px 10px; color:#64748b;">${escapeHtml(s.splDate || '-')}</td>
                                            <td style="padding:7px 10px; font-weight:600;">${escapeHtml(s.employeeName || '-')} <small style="color:#64748b; font-weight:normal;">(${escapeHtml(s.employeeRole || 'Mekanik')})</small></td>
                                            <td style="padding:7px 10px; font-family:var(--font-mono);">${escapeHtml(s.periodStart || '')} - ${escapeHtml(s.periodEnd || '')}</td>
                                            <td style="padding:7px 10px; text-align:center; font-weight:700; color:#059669;">${Number(s.durationHours || 0).toFixed(1)} Jam</td>
                                            <td style="padding:7px 10px;">
                                                ${s.endVerified 
                                                    ? '<span class="badge bg-operating" style="font-size:0.7rem;"><i class="fa-solid fa-check-double"></i> Terverifikasi</span>' 
                                                    : (s.startVerified ? '<span class="badge bg-standby" style="font-size:0.7rem;"><i class="fa-solid fa-clock"></i> Berjalan</span>' : '<span class="badge bg-secondary" style="font-size:0.7rem;">Draft / Belum TTD</span>')}
                                            </td>
                                            <td style="padding:7px 10px; font-size:0.8rem; color:#334155;">${escapeHtml(s.activityDescription || s.generalInstruction || '-')}</td>
                                        </tr>`).join('')}
                                </tbody>
                            </table>`;
                    }
                }
            }

            window.openWoDetailModal = async function (woId, fallbackAssetId, fallbackIssue) {
                window.switchWoDetailTab('tabWoInfo');

                const cachedWorkOrders = typeof globalData !== 'undefined' && Array.isArray(globalData?.work_orders)
                    ? globalData.work_orders
                    : [];
                let wo = cachedWorkOrders.find(w => w.woId === woId) || null;

                const modalWoId = document.getElementById('modalWoId');
                const modalWoStatusBadge = document.getElementById('modalWoStatusBadge');
                const modalWoPriorityBadge = document.getElementById('modalWoPriorityBadge');
                const modalWoUnitSubtitle = document.getElementById('modalWoUnitSubtitle');

                const modalWoAsset = document.getElementById('modalWoAsset');
                const modalWoStatus = document.getElementById('modalWoStatus');
                const modalWoPriority = document.getElementById('modalWoPriority');
                const modalWoDowntime = document.getElementById('modalWoDowntime');
                const modalWoIssue = document.getElementById('modalWoIssue');
                const modalWoMaintenanceType = document.getElementById('modalWoMaintenanceType');
                const modalWoHmKm = document.getElementById('modalWoHmKm');
                const modalWoLocation = document.getElementById('modalWoLocation');
                const modalWoMechanic = document.getElementById('modalWoMechanic');
                const modalWoReportedAt = document.getElementById('modalWoReportedAt');
                const modalWoStartedAt = document.getElementById('modalWoStartedAt');
                const modalWoEstimatedAt = document.getElementById('modalWoEstimatedAt');
                const modalWoClosedAt = document.getElementById('modalWoClosedAt');
                const modalWoDuration = document.getElementById('modalWoDuration');
                const modalWoRootCause = document.getElementById('modalWoRootCause');
                const modalWoRepairAction = document.getElementById('modalWoRepairAction');
                const modalWoMitigationAction = document.getElementById('modalWoMitigationAction');
                const modalWoTimeline = document.getElementById('modalWoTimeline');
                const noticeEl = document.getElementById('modalWoFallbackNotice');

                const assetBtn = document.getElementById('modalWoAssetBtn');
                const documentBtn = document.getElementById('modalWoPrintDocumentBtn');
                const documentAlias = document.getElementById('modalWoDocumentAlias');
                if (documentBtn) { documentBtn.disabled = true; documentBtn.onclick = null; }

                const targetAssetId = wo ? (wo.assetId || wo.unitId) : (fallbackAssetId || '');
                const allAssetsList = typeof getAllAssets === 'function' ? getAllAssets() : (typeof globalData !== 'undefined' && Array.isArray(globalData?.assets) ? globalData.assets : []);
                const assetObj = allAssetsList.find(a => a.id === targetAssetId || a.asset_id === targetAssetId || a.asset_code === targetAssetId);
                const ident = window.formatAssetIdentityDetails ? window.formatAssetIdentityDetails(assetObj || { id: targetAssetId }) : { unitId: targetAssetId || 'N/A', sn: 'Belum tersedia', plate: 'Belum tersedia' };

                if (modalWoId) modalWoId.textContent = woId || '-';
                if (modalWoUnitSubtitle) modalWoUnitSubtitle.textContent = `Unit: ${ident.unitId} • SN: ${ident.sn} • Plat: ${ident.plate}`;
                if (modalWoAsset) modalWoAsset.innerHTML = `<strong>${escapeHtml(ident.unitId)}</strong> <span style="font-size:0.82rem; color:var(--text-muted); font-weight:normal; margin-left:6px;">(SN: ${escapeHtml(ident.sn)} | Plat: ${escapeHtml(ident.plate)})</span>`;
                if (modalWoIssue) modalWoIssue.textContent = wo ? (wo.issue || 'Tidak ada deskripsi.') : (fallbackIssue || 'Penanganan darurat breakdown unit.');

                openModal('modalWoDetail');

                try {
                    const response = await fetch(`api/work_orders.php?id=${encodeURIComponent(woId)}`, {
                        headers: { Accept: 'application/json' },
                        cache: 'no-store'
                    });
                    const result = await response.json();
                    if (!response.ok || result.status !== 'success' || !result.data) {
                        throw new Error(result.message || 'Detail Work Order tidak tersedia.');
                    }
                    const rawData = result.data;
                    const detail = normalizeWorkOrder(rawData);
                    detail.support = rawData.support || null;

                    if (wo) Object.assign(wo, detail);
                    else wo = detail;

                    const isEmergency = ['High', 'Emergency'].includes(detail.priority);
                    const statusClass = detail.status === 'Open' ? 'bg-breakdown' : (detail.status === 'In Progress' ? 'bg-standby' : (detail.status === 'Waiting Part' ? 'bg-standby' : 'bg-operating'));
                    if (modalWoStatusBadge) modalWoStatusBadge.innerHTML = `<span class="badge ${statusClass}">${escapeHtml(detail.status)}</span>`;
                    if (modalWoPriorityBadge) modalWoPriorityBadge.innerHTML = `<span class="badge ${isEmergency ? 'bg-breakdown' : 'bg-operating'}">${escapeHtml(detail.priority)}</span>`;

                    if (modalWoId) modalWoId.textContent = detail.woId;
                    if (modalWoAsset) modalWoAsset.innerHTML = `<strong>${escapeHtml(detail.assetId)}</strong>`;
                    if (modalWoStatus) modalWoStatus.innerHTML = `<span class="badge ${statusClass}">${escapeHtml(detail.status)}</span>`;
                    if (modalWoPriority) modalWoPriority.innerHTML = `<span class="badge ${isEmergency ? 'bg-breakdown' : 'bg-operating'}">${escapeHtml(detail.priority)}</span>`;
                    if (modalWoDowntime) modalWoDowntime.textContent = detail.downtime || '0 jam 00 menit';
                    if (modalWoIssue) modalWoIssue.textContent = detail.issue || 'Tidak ada deskripsi.';
                    if (modalWoMaintenanceType) modalWoMaintenanceType.textContent = detail.maintenanceType;
                    if (modalWoHmKm) modalWoHmKm.textContent = detail.currentHmKm ? `${Number(detail.currentHmKm).toLocaleString('id-ID')} HM/KM` : '-';
                    if (modalWoLocation) modalWoLocation.textContent = detail.rawLocation || '-';
                    if (modalWoMechanic) modalWoMechanic.textContent = detail.assignedTo;
                    if (modalWoReportedAt) modalWoReportedAt.textContent = formatWoDateTime(detail.reportedAt);
                    if (modalWoStartedAt) modalWoStartedAt.textContent = formatWoDateTime(detail.repairStartedAt);
                    if (modalWoEstimatedAt) modalWoEstimatedAt.textContent = formatWoDateTime(detail.estimatedCompletionAt);
                    if (modalWoClosedAt) modalWoClosedAt.textContent = formatWoDateTime(detail.closedAt);
                    if (modalWoDuration) modalWoDuration.textContent = formatWoDuration(detail.handlingDurationMinutes);
                    if (modalWoRootCause) modalWoRootCause.textContent = detail.rootCause || 'Belum dicatat.';
                    if (modalWoRepairAction) modalWoRepairAction.textContent = detail.repairAction || 'Belum ada tindakan tercatat.';
                    if (modalWoMitigationAction) modalWoMitigationAction.textContent = detail.mitigationAction || '-';
                    if (modalWoTimeline) modalWoTimeline.innerHTML = renderWoTimeline(detail.timeline);
                    if (noticeEl) noticeEl.style.display = 'none';

                    const photoContainer = document.getElementById('modalWoPhotoContainer');
                    const photoImg = document.getElementById('modalWoPhotoImg');
                    if (photoContainer && photoImg) {
                        if (detail.beforePhotoUrl) {
                            photoImg.src = detail.beforePhotoUrl;
                            photoContainer.style.display = 'block';
                        } else {
                            photoContainer.style.display = 'none';
                        }
                    }

                    if (assetBtn) assetBtn.onclick = () => { closeModal('modalWoDetail'); openAssetModal(detail.assetId, 'BREAKDOWN', 'Heavy Equipment', 'Site Project'); };
                    if (documentBtn) {
                        documentBtn.disabled = false;
                        documentBtn.onclick = () => window.open(`api/work_order_document.php?id=${encodeURIComponent(detail.woId)}&doc_alias=${encodeURIComponent(documentAlias?.value || 'WO')}`, '_blank', 'noopener');
                    }

                    renderWoCommandCenterSupport(detail, detail.support || {});

                } catch (error) {
                    console.error('Detail Work Order gagal dimuat:', error);
                    if (modalWoTimeline) modalWoTimeline.innerHTML = `<div style="color:#dc3545;">${escapeHtml(error.message)}</div>`;
                }
            };

            async function loadWoCosts(woId, assetId) {
                const totalEl = document.getElementById('modalWoCostTotalFormatted') || document.getElementById('modalWoCostTotal');
                const listEl = document.getElementById('modalWoCostsContainer') || document.getElementById('modalWoCostList');
                const tabBadge = document.getElementById('modalWoCostsTabBadge');
                if (totalEl) totalEl.textContent = 'Memuat...';
                if (listEl) listEl.innerHTML = '<span class="text-muted"><i class="fa-solid fa-spinner fa-spin"></i> Memuat rincian biaya WO...</span>';

                try {
                    const response = await fetch(`api/costs.php?wo_id=${encodeURIComponent(woId)}`, {
                        headers: { Accept: 'application/json' },
                        cache: 'no-store'
                    });
                    const result = await response.json();
                    if (response.ok && result.status === 'success' && result.data) {
                        const costData = result.data;
                        if (totalEl) totalEl.textContent = costData.formattedTotalCost || 'Rp 0';
                        if (tabBadge && Array.isArray(costData.items)) tabBadge.textContent = costData.items.length;
                        if (listEl) {
                            if (Array.isArray(costData.items) && costData.items.length > 0) {
                                listEl.innerHTML = `
                                    <table style="width:100%; border-collapse:collapse; font-size:0.82rem; background:#ffffff; border:1px solid #e2e8f0; border-radius:6px; overflow:hidden;">
                                        <thead>
                                            <tr style="background:#f8fafc; color:#475569; text-align:left; border-bottom:1px solid #e2e8f0;">
                                                <th style="padding:8px 10px;">Tanggal</th>
                                                <th style="padding:8px 10px;">Kategori</th>
                                                <th style="padding:8px 10px;">Kode Biaya</th>
                                                <th style="padding:8px 10px;">Deskripsi Transaksi</th>
                                                <th style="padding:8px 10px; text-align:right;">Nominal (Rp)</th>
                                                <th style="padding:8px 10px;">No. Bukti</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${costData.items.map(it => `
                                                <tr style="border-bottom:1px solid #f1f5f9;">
                                                    <td style="padding:7px 10px; color:#64748b;">${escapeHtml(it.transactionDate || it.date || '-')}</td>
                                                    <td style="padding:7px 10px;"><span class="badge bg-secondary" style="font-size:0.7rem;">${escapeHtml(it.costCategory || it.category || '-')}</span></td>
                                                    <td style="padding:7px 10px; font-family:var(--font-mono); font-weight:600;">${escapeHtml(it.costCode || '-')}</td>
                                                    <td style="padding:7px 10px;">${escapeHtml(it.description || '-')}</td>
                                                    <td style="padding:7px 10px; text-align:right; font-weight:700; color:${it.isReversed ? '#94a3b8; text-decoration:line-through;' : '#0f172a;'}">${escapeHtml(it.formattedAmount || `Rp ${Number(it.amount || 0).toLocaleString('id-ID')}`)}</td>
                                                    <td style="padding:7px 10px; font-size:0.78rem; color:#64748b;">${escapeHtml(it.referenceNumber || '-')}</td>
                                                </tr>`).join('')}
                                        </tbody>
                                    </table>`;
                            } else {
                                listEl.innerHTML = '<span class="text-muted">Belum ada transaksi biaya perbaikan untuk Work Order ini.</span>';
                            }
                        }
                    }
                } catch (e) {
                    if (totalEl) totalEl.textContent = 'Rp 0';
                    if (listEl) listEl.innerHTML = '<span class="text-muted">Rincian biaya belum dapat dimuat.</span>';
                }
            }

            window.openWoDetailView = function (woId, assetId, issueText) {
                // First: Open Modal Detail for Instant 360&deg; Visual Feedback
                openWoDetailModal(woId, assetId, issueText);

                // Second: Switch to Work Order Kanban view and highlight card if present
                showView('wo');
                if (woId) {
                    setTimeout(() => {
                        const card = document.getElementById('wo-card-' + woId);
                        if (card) {
                            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            card.style.outline = '3px solid #dc3545';
                            card.style.boxShadow = '0 0 20px rgba(220, 53, 69, 0.7)';
                            setTimeout(() => {
                                card.style.outline = '';
                                card.style.boxShadow = '';
                            }, 3500);
                        }
                    }, 250);
                }
            };

            let globalData = null;
            let mapInitialized = false;
            let dashboardMapInstance = null;
            const ASSET_STATUS_PRIORITY = { READY: 1, STANDBY: 2, INSPEKSI: 3, BREAKDOWN: 4, 'ACCIDENT HOLD': 5 };
            const MASTER_LOCATION_COORDINATES = Object.freeze({
                'Yard Duri': { lat: 1.2854300, lng: 101.2185400 },
                'Yard Prabumulih': { lat: -3.4354200, lng: 104.2384500 },
                'Borrow Pit Harapan Baru': { lat: 1.3124500, lng: 101.2451200 },
                'Workshop Bay KM 12': { lat: 1.2789000, lng: 101.2112000 },
                'Site Sunter Area Stadium': { lat: -6.1451200, lng: 106.8741500 },
                'Site Alpha Duri': { lat: 1.2991000, lng: 101.2311000 },
                'PKB PEKANBARU Branch': { lat: 0.5070680, lng: 101.4477790 },
                'PLB PALEMBANG Branch': { lat: -2.9760740, lng: 104.7754310 },
                'MDN MEDAN Branch': { lat: 3.5951960, lng: 98.6722230 },
                'SMG SEMARANG Rep Office': { lat: -6.9666670, lng: 110.4166640 },
                'Minas Field Project': { lat: 0.7321400, lng: 101.4421100 }
            });

            function validMapCoordinate(lat, lng) {
                return Number.isFinite(lat) && Number.isFinite(lng)
                    && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180;
            }

            function stableLocationHash(value) {
                let hash = 2166136261;
                for (const character of String(value || '')) {
                    hash ^= character.charCodeAt(0);
                    hash = Math.imul(hash, 16777619);
                }
                return hash >>> 0;
            }

            function representativeLocationPosition(location) {
                const normalized = String(location || '').trim().toLowerCase();
                if (!normalized) return null;

                let anchor = MASTER_LOCATION_COORDINATES['Site Alpha Duri'];
                if (normalized.includes('km 12')) anchor = MASTER_LOCATION_COORDINATES['Workshop Bay KM 12'];
                else if (normalized.includes('pekanbaru')) anchor = MASTER_LOCATION_COORDINATES['PKB PEKANBARU Branch'];
                else if (normalized.includes('prabumulih')) anchor = MASTER_LOCATION_COORDINATES['Yard Prabumulih'];
                else if (normalized.includes('minas')) anchor = MASTER_LOCATION_COORDINATES['Minas Field Project'];
                else if (normalized.includes('sunter')) anchor = MASTER_LOCATION_COORDINATES['Site Sunter Area Stadium'];
                else if (normalized.includes('palembang')) anchor = MASTER_LOCATION_COORDINATES['PLB PALEMBANG Branch'];
                else if (normalized.includes('medan')) anchor = MASTER_LOCATION_COORDINATES['MDN MEDAN Branch'];
                else if (normalized.includes('semarang')) anchor = MASTER_LOCATION_COORDINATES['SMG SEMARANG Rep Office'];

                const hash = stableLocationHash(normalized);
                const angle = (hash % 360) * Math.PI / 180;
                const radius = 0.004 + ((hash >>> 9) % 42) / 1000;
                const longitudeScale = Math.max(0.3, Math.cos(anchor.lat * Math.PI / 180));
                return {
                    lat: anchor.lat + Math.sin(angle) * radius,
                    lng: anchor.lng + Math.cos(angle) * radius / longitudeScale,
                    source: 'Posisi representatif berdasarkan nama lokasi',
                    isRepresentative: true
                };
            }

            function resolveAssetMapPosition(asset, fallbackLocation = '') {
                const individualPairs = [
                    [asset?.last_latitude, asset?.last_longitude],
                    [asset?.lastLatitude, asset?.lastLongitude],
                    [asset?.latitude, asset?.longitude],
                    [asset?.lat, asset?.lng]
                ];

                for (const [rawLat, rawLng] of individualPairs) {
                    if (rawLat === null || rawLat === undefined || rawLat === ''
                        || rawLng === null || rawLng === undefined || rawLng === '') continue;
                    const lat = Number(rawLat);
                    const lng = Number(rawLng);
                    if (validMapCoordinate(lat, lng)) {
                        return { lat, lng, source: 'Koordinat individual aset', isRepresentative: false };
                    }
                }

                const location = String(asset?.location || asset?.location_name || fallbackLocation || '').trim();
                const masterPosition = MASTER_LOCATION_COORDINATES[location];
                if (masterPosition) {
                    return { ...masterPosition, source: 'Koordinat master lokasi', isRepresentative: false };
                }
                return representativeLocationPosition(location);
            }

            function rebuildFleetSummary() {
                if (!globalData || !globalData.assets) return;
                const counts = { READY: 0, STANDBY: 0, INSPEKSI: 0, BREAKDOWN: 0, 'ACCIDENT HOLD': 0 };
                globalData.assets.forEach(asset => {
                    const key = asset.status || 'READY';
                    counts[key] = (counts[key] || 0) + 1;
                });
                globalData.summary = globalData.summary || {};
                globalData.summary.total_units = globalData.assets.length;
                globalData.summary.status_counts = counts;
            }

            function extractCleanAssetId(rawId) {
                if (!rawId) return '';
                const str = String(rawId).trim();
                const standardMatch = str.match(/(?:^|\s)([A-Za-z0-9]{2,6}-\d{1,5})(?:\s|[-_:]|$)/);
                if (standardMatch) {
                    return standardMatch[1].toUpperCase();
                }
                const customMatch = str.match(/([A-Z0-9]+-(?:BRA|PM|DT|DZ|EX)\s*\d+)/i);
                if (customMatch) {
                    return customMatch[1].toUpperCase().replace(/\s+/, '-');
                }
                const fallback = str.split(/\s*[-_]\s*/)[0].trim().toUpperCase();
                return fallback || str.toUpperCase();
            }

            function normalizeAssetRegistry(data) {
                const registry = new Map();
                (data.assets || []).forEach(rawAsset => {
                    let rawId = (rawAsset.id || rawAsset.asset_id || '').trim();
                    if (!rawId) return;

                    // Records enriched from api/assets.php carry the canonical
                    // database key in asset_id. Preserve it verbatim; parsing is
                    // only for legacy display strings that mix an ID with SN/
                    // plate metadata. Otherwise IDs such as QA-F3C-BROWSER are
                    // truncated to "QA" and later fail server-side access checks.
                    let cleanId = rawAsset.asset_id
                        ? String(rawAsset.asset_id).trim()
                        : extractCleanAssetId(rawId);
                    let serialNumber = rawAsset.serialNumber || '';
                    let plateNumber = rawAsset.plateNumber || '';

                    if (rawId !== cleanId) {
                        const remainder = rawId.replace(new RegExp(cleanId, 'i'), '').replace(/^[\s\-_:]+/, '').trim();
                        if (remainder) {
                            if (/^SN/i.test(remainder)) {
                                serialNumber = remainder.toUpperCase();
                            } else if (/^[A-Z]{1,2}\s*\d{1,4}\s*[A-Z]{1,3}/i.test(remainder)) {
                                plateNumber = remainder.toUpperCase();
                            } else if (!plateNumber && !serialNumber) {
                                plateNumber = remainder;
                            }
                        }
                    }

                    const candidate = {
                        ...rawAsset,
                        id: cleanId,
                        rawId: rawId,
                        serialNumber: serialNumber || rawAsset.serialNumber || '',
                        plateNumber: plateNumber || rawAsset.plateNumber || ''
                    };

                    const current = registry.get(cleanId);
                    if (!current) {
                        registry.set(cleanId, candidate);
                        return;
                    }
                    const currentRank = ASSET_STATUS_PRIORITY[current.status] || 0;
                    const candidateRank = ASSET_STATUS_PRIORITY[candidate.status] || 0;
                    if (candidateRank > currentRank) {
                        registry.set(cleanId, { ...current, ...candidate, id: cleanId });
                    }
                });

                data.assets = Array.from(registry.values()).map(asset => ({
                    ...asset,
                    status: asset.status || 'READY',
                    statusSource: asset.statusSource || 'Master Asset',
                    statusReference: asset.statusReference || 'Data awal',
                    statusEvents: Array.isArray(asset.statusEvents) ? asset.statusEvents : []
                }));
            }

            let activeProjectLocation = '';

            function getAllAssets() {
                return Array.isArray(globalData?.assets) ? globalData.assets : [];
            }

            function getAllWorkOrders() {
                return Array.isArray(globalData?.work_orders) ? globalData.work_orders : [];
            }

            window.getProjectScopedAssets = function () {
                const assets = getAllAssets();
                return activeProjectLocation
                    ? assets.filter(asset => String(asset.location || '').trim() === activeProjectLocation)
                    : assets;
            };

            window.getProjectScopedWorkOrders = function (source = null) {
                const workOrders = Array.isArray(source) ? source : getAllWorkOrders();
                if (!activeProjectLocation) return workOrders;
                const assetIds = new Set(window.getProjectScopedAssets().map(asset => asset.id));
                return workOrders.filter(wo => assetIds.has(extractCleanAssetId(wo.assetId || wo.unitId || '')));
            };

            window.getActiveProjectLocation = function () {
                return activeProjectLocation;
            };

            function buildProjectScopedData() {
                const assets = window.getProjectScopedAssets();
                const workOrders = window.getProjectScopedWorkOrders();
                const statusCounts = { READY: 0, STANDBY: 0, INSPEKSI: 0, BREAKDOWN: 0, 'ACCIDENT HOLD': 0 };
                assets.forEach(asset => {
                    const status = asset.status || 'READY';
                    statusCounts[status] = (statusCounts[status] || 0) + 1;
                });
                return {
                    ...globalData,
                    assets,
                    work_orders: workOrders,
                    summary: {
                        ...(globalData?.summary || {}),
                        total_units: assets.length,
                        status_counts: statusCounts
                    }
                };
            }

            function renderProjectMaster() {
                const panel = document.getElementById('settings-location');
                if (!panel || !globalData) return;
                const locations = new Map();
                getAllAssets().forEach(asset => {
                    const location = String(asset.location || 'Belum ditentukan').trim() || 'Belum ditentukan';
                    if (!locations.has(location)) locations.set(location, []);
                    locations.get(location).push(asset);
                });
                const sortedLocations = Array.from(locations.entries()).sort((a, b) => b[1].length - a[1].length);
                panel.innerHTML = `
                    <div class="project-master-heading">
                        <div>
                            <span class="project-master-eyebrow">Master berbasis data aset</span>
                            <h3><i class="fa-solid fa-location-dot"></i> Project / Lokasi Operasional</h3>
                            <p>${sortedLocations.length} lokasi ditemukan dari ${getAllAssets().length} unit. Data ini dipakai sebagai filter lintas Dashboard, Master Asset, WO, dan katalog spare part.</p>
                        </div>
                        <span class="project-master-source"><i class="fa-solid fa-database"></i> Sumber: Master Asset</span>
                    </div>
                    <div class="project-master-notice"><i class="fa-solid fa-circle-info"></i><span>Nama lokasi masih mengikuti data sumber. Karena belum tersedia ID project, PIC, koordinat, dan periode kontrak yang tervalidasi, sistem tidak membuat data project fiktif.</span></div>
                    <div class="project-master-grid">
                        ${sortedLocations.map(([location, assets]) => {
                    const active = assets.filter(asset => !['BREAKDOWN', 'ACCIDENT HOLD'].includes(asset.status)).length;
                    const breakdown = assets.filter(asset => asset.status === 'BREAKDOWN').length;
                    const categories = new Set(assets.map(asset => asset.category).filter(Boolean)).size;
                    return `<button type="button" class="project-master-card${activeProjectLocation === location ? ' active' : ''}" data-project-location="${escapeHtml(location)}" onclick="applyGlobalProjectFilter(this.dataset.projectLocation)">
                                <span class="project-master-card-icon"><i class="fa-solid fa-map-location-dot"></i></span>
                                <span class="project-master-card-body"><strong>${escapeHtml(location)}</strong><small>${assets.length} unit &middot; ${categories} kategori</small></span>
                                <span class="project-master-card-metrics"><b>${active}</b><small>aktif</small><b class="danger">${breakdown}</b><small>breakdown</small></span>
                            </button>`;
                }).join('')}
                    </div>`;
            }

            function initializeProjectMaster() {
                const select = document.getElementById('globalProjectFilter');
                if (!select || !globalData) return;
                const counts = new Map();
                getAllAssets().forEach(asset => {
                    const location = String(asset.location || 'Belum ditentukan').trim() || 'Belum ditentukan';
                    counts.set(location, (counts.get(location) || 0) + 1);
                });
                const options = Array.from(counts.entries()).sort((a, b) => b[1] - a[1]);
                select.innerHTML = `<option value="">Semua (${getAllAssets().length})</option>`
                    + options.map(([location, count]) => `<option value="${escapeHtml(location)}">${escapeHtml(location)} (${count})</option>`).join('');
                select.value = activeProjectLocation;
                renderProjectMaster();
            }

            window.applyGlobalProjectFilter = function (location) {
                activeProjectLocation = String(location || '').trim();
                const scopedData = buildProjectScopedData();
                initDashboard(scopedData);
                initMonitoringView(scopedData.assets);
                initAssetView(scopedData.assets);
                initWorkOrderView(scopedData.work_orders);
                if (mapInitialized) initMap(scopedData.assets);
                window.SpareLogistics?.refresh?.();
                renderProjectMaster();
                document.dispatchEvent(new CustomEvent('fleetproject:change', {
                    detail: { location: activeProjectLocation, assets: scopedData.assets, workOrders: scopedData.work_orders }
                }));
            };

            window.resolveAsset = function (query) {
                if (!globalData || !Array.isArray(globalData.assets)) return null;
                if (!query) return globalData.assets[0] || null;

                const rawStr = String(query).trim();
                const q = rawStr.toLowerCase();
                const assets = globalData.assets;

                // 1. Exact ID match
                let match = assets.find(a => String(a.id).toLowerCase() === q);
                if (match) return match;

                // 2. Clean ID match
                const cleanQ = extractCleanAssetId(rawStr).toLowerCase();
                match = assets.find(a => String(a.id).toLowerCase() === cleanQ || (a.rawId && String(a.rawId).toLowerCase() === q));
                if (match) return match;

                // 3. Matching with shortCode
                match = assets.find(a => {
                    const sc1 = typeof window.shortCode === 'function' ? window.shortCode(a.id) : a.id.split(' ')[0];
                    const sc2 = typeof window.shortCode === 'function' ? window.shortCode(rawStr) : rawStr.split(' ')[0];
                    return sc1.toLowerCase() === sc2.toLowerCase();
                });
                if (match) return match;

                // 4. Substring match
                match = assets.find(a => String(a.id).toLowerCase().includes(q) || q.includes(String(a.id).toLowerCase()) || (a.rawId && String(a.rawId).toLowerCase().includes(q)));
                if (match) return match;

                // 5. Match by Serial Number or Plate Number
                match = assets.find(a => (a.serialNumber && a.serialNumber.toLowerCase().includes(q)) || (a.plateNumber && a.plateNumber.toLowerCase().includes(q)));
                if (match) return match;

                return assets[0] || null;
            };

            async function persistFleetState(options = {}) {
                if (!globalData || (!options.assetId && !options.workOrderId)) return;
                const asset = options.assetId
                    ? globalData.assets?.find(item => item.id === options.assetId)
                    : null;
                const workOrder = options.workOrderId
                    ? globalData.work_orders?.find(item => item.woId === options.workOrderId)
                    : null;
                if ((options.assetId && !asset) || (options.workOrderId && !workOrder)) {
                    throw new Error('Objek sinkronisasi tidak ditemukan. Muat ulang data.');
                }
                const payload = {
                    mode: 'targeted',
                    assets: asset ? [{ id: asset.id, status: asset.status, expected_status: asset.syncExpectedStatus || asset.status, last_hm_km: asset.last_hm_km }] : [],
                    work_orders: workOrder ? [workOrder] : []
                };
                const response = await fetch('api/sync.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.APP_CSRF_TOKEN || '' },
                    body: JSON.stringify(payload)
                });
                const result = await response.json();
                if (!response.ok || result.status !== 'success') {
                    throw new Error(result.message || 'Perubahan unit/WO belum tersimpan di database.');
                }
                if (asset) asset.syncExpectedStatus = asset.status;
                return result;
            }

            function setIntegratedAssetStatus(assetId, status, source, reference, note = '') {
                if (!globalData || !globalData.assets) return null;
                const asset = window.resolveAsset(assetId);
                if (!asset) return null;
                const now = new Date();
                if (!asset.syncExpectedStatus) asset.syncExpectedStatus = asset.status;
                asset.status = status;
                asset.lastUpdate = 'Baru saja';
                asset.statusUpdatedAt = now.toISOString();
                asset.statusSource = source || 'Master Asset';
                asset.statusReference = reference || 'Update manual';
                asset.statusNote = note;
                asset.statusEvents = Array.isArray(asset.statusEvents) ? asset.statusEvents : [];
                asset.statusEvents.unshift({
                    at: now.toISOString(),
                    status,
                    source: asset.statusSource,
                    reference: asset.statusReference,
                    note
                });
                rebuildFleetSummary();
                return asset;
            }
            window.setIntegratedAssetStatus = setIntegratedAssetStatus;

            function linkExistingWorkflowRecords() {
                if (!globalData) return;
                (globalData.assets || []).forEach(asset => {
                    if (asset.statusSource !== 'Master Asset' || asset.statusReference !== 'Data awal') return;
                    const unitWos = (globalData.work_orders || []).filter(wo =>
                        wo.assetId === asset.id && wo.status !== 'Closed'
                    );
                    const activeWo = unitWos.find(wo => wo.priority === 'High')
                        || unitWos.find(wo => wo.status === 'In Progress')
                        || unitWos[0];
                    if (activeWo) {
                        asset.statusSource = 'Work Order';
                        asset.statusReference = activeWo.woId;
                    }
                });
            }

            function getUnitIntegrationContext(asset) {
                const inspections = (globalData && globalData.inspections) || [];
                const workOrders = (globalData && globalData.work_orders) || [];
                const latestInspection = inspections
                    .filter(item => item.unitId === asset.id)
                    .sort((a, b) => String(b.date || '').localeCompare(String(a.date || '')))[0] || null;
                const unitWos = workOrders.filter(wo => (wo.assetId || wo.unitId) === asset.id);
                const activeWos = unitWos.filter(wo => wo.status !== 'Closed');
                const activeWo = activeWos.find(wo => wo.priority === 'High')
                    || activeWos.find(wo => wo.status === 'In Progress')
                    || activeWos.find(wo => wo.status === 'Open')
                    || null;
                return { asset, latestInspection, activeWo, unitWos };
            }

            window.refreshAllViews = function () {
                if (!globalData) return;
                const scopedData = buildProjectScopedData();
                initDashboard(scopedData);
                if (window.initExecutiveAnalyticsPanels) window.initExecutiveAnalyticsPanels();
                initMonitoringView(scopedData.assets);
                initAssetView(scopedData.assets);
                initWorkOrderView(scopedData.work_orders);
                if (globalData.costs) initBiayaView(globalData.costs);
            };

            window.syncFleetState = function (options = {}) {
                if (!globalData) return;
                rebuildFleetSummary();
                const syncResult = persistFleetState(options).then(result => {
                    refreshAllViews();
                    if (window.FleetInspectionModule && options.refreshInspection !== false) {
                        window.FleetInspectionModule.refresh();
                    }
                    return result;
                }).catch(error => {
                    console.error('Sinkronisasi terarah gagal:', error);
                    // Revert state lokal jika sinkronisasi ke server ditolak atau gagal
                    if (options.assetId && globalData.assets) {
                        const targetAsset = globalData.assets.find(item => item.id === options.assetId);
                        if (targetAsset && targetAsset.syncExpectedStatus) {
                            targetAsset.status = targetAsset.syncExpectedStatus;
                        }
                    }
                    rebuildFleetSummary();
                    refreshAllViews();
                    if (window.FleetInspectionModule && options.refreshInspection !== false) {
                        window.FleetInspectionModule.refresh();
                    }
                    alert(`Perubahan unit/WO belum tersimpan di database: ${error.message}`);
                    return null;
                });
                return syncResult;
            };

            window.openIntegratedStatusUpdate = function (assetId, defaultTargetStatus = null) {
                const asset = globalData && globalData.assets.find(item => item.id === assetId);
                if (!asset) return;
                document.getElementById('us-id').value = asset.id;
                document.getElementById('us-id-label').textContent = asset.id;
                document.getElementById('us-status').value = defaultTargetStatus || asset.status;
                document.getElementById('us-loc').value = '';
                document.getElementById('us-notes').value = '';
                closeModal('assetModal');
                openModal('modalUpdateStatus');
            };

            window.openIntegratedP2H = function (assetId, mode = 'history') {
                closeModal('assetModal');
                showView('inspection');
                setTimeout(() => {
                    if (window.FleetInspectionModule) {
                        window.FleetInspectionModule.openForAsset(assetId, mode);
                        return;
                    }
                    window.switchP2HTab?.(mode === 'form' ? 'form' : 'history');
                    const search = document.getElementById('searchP2HHistory');
                    if (search) {
                        search.value = assetId;
                        window.filterP2HHistoryTable?.();
                    }
                }, 50);
            };

            window.openIntegratedWO = function (assetId) {
                const asset = globalData && globalData.assets.find(item => item.id === assetId);
                if (!asset) return;
                closeModal('assetModal');
                const { activeWo } = getUnitIntegrationContext(asset);
                if (activeWo) {
                    openWoDetailView(activeWo.woId, assetId, activeWo.issue || activeWo.description || '');
                } else {
                    openNewWOModal(assetId);
                    document.getElementById('nwo-down').value = asset.status === 'BREAKDOWN' ? 'Yes' : 'No';
                }
            };

            async function fetchJsonSafely(url) {
                try {
                    const cacheBusterUrl = url + (url.includes('?') ? '&' : '?') + '_t=' + Date.now();
                    const response = await fetch(cacheBusterUrl, { cache: 'no-store', headers: { 'Cache-Control': 'no-cache' } });
                    if (!response.ok) return null;
                    const rawBody = await response.text();
                    if (!rawBody.trim()) return null;
                    return JSON.parse(rawBody.replace(/^\uFEFF/, ''));
                } catch (error) {
                    console.warn(`Sumber data ${url} tidak tersedia:`, error.message || error);
                    return null;
                }
            }

            async function fetchArchiveMarkersSafely() {
                try {
                    const auth = window.FleetAuthReady ? await window.FleetAuthReady : window.FleetAuth;
                    const permissions = Array.isArray(auth?.user?.permissions) ? auth.user.permissions : [];
                    const mayReadArchive = permissions.includes('*') || permissions.includes('archive.read');
                    if (!mayReadArchive) {
                        // Active-register APIs already exclude archived rows server-side. A role
                        // without archive.read therefore needs no archive marker payload and must
                        // not receive a misleading synchronization warning.
                        return { status: 'success', data: [], permission_skipped: true };
                    }
                    return await fetchJsonSafely('api/archive.php');
                } catch (error) {
                    console.warn('Penanda arsip tidak dapat diverifikasi:', error.message || error);
                    return null;
                }
            }

            function mapFuelApiRecord(f) {
                const rateActual = parseFloat(f.calculated_lph) || 0;
                const targetRate = parseFloat(f.baseline_lph) || 0;
                let deviationPct = 0;
                if (targetRate > 0) deviationPct = ((rateActual - targetRate) / targetRate) * 100;

                let anomalyStatus = 'GREEN';
                if (deviationPct > 20) anomalyStatus = 'RED';
                else if (deviationPct > 10) anomalyStatus = 'ORANGE';
                else if (deviationPct > 5) anomalyStatus = 'YELLOW';

                const fromLho = Boolean(f.source_report_id);
                return {
                    trxId: String(f.fuel_log_id ?? ''),
                    timeStr: String(f.refuel_date || ''),
                    unitId: String(f.asset_id || ''),
                    category: String(f.asset_category || 'Unknown'),
                    location: String(f.source_site || 'Auto'),
                    fuelman: fromLho ? 'Laporan LHO' : 'System',
                    sourceReportNumber: String(f.source_report_number || ''),
                    operator: String(f.driver_name || 'Unknown'),
                    meterType: fromLho ? 'HM' : 'HM/KM',
                    meterPrev: parseFloat(fromLho ? f.source_hm_start : f.flowmeter_start) || 0,
                    meterCurr: parseFloat(fromLho ? f.source_hm_end : f.flowmeter_end) || 0,
                    meterDiff: parseFloat(fromLho ? f.source_hm_operation : f.current_hm_km) || 0,
                    liter: parseFloat(f.liters_issued) || 0,
                    rateActual,
                    targetRate,
                    deviationPct: parseFloat(deviationPct.toFixed(1)),
                    costRp: (parseFloat(f.liters_issued) || 0) * 12000,
                    anomalyStatus: (f.is_anomaly == 1) ? 'RED' : anomalyStatus,
                    status: String(f.source_status || 'Terverifikasi'),
                    notes: f.source_notes || f.notes || ''
                };
            }

            document.addEventListener('DOMContentLoaded', async () => {
                try {
                    if (window.loadAssetMapping) {
                        await window.loadAssetMapping();
                        // Re-render logistics to apply SN/Plat
                        if (typeof renderTableMasuk === 'function') renderTableMasuk();
                        if (typeof renderTableKeluar === 'function') renderTableKeluar();
                    }

                    // Fetch all initialization data concurrently
                    const [apiInitData, assetPayload, fuelData, pmData, tireData, archiveDataRes, inspectionsRes, accidentsRes] = await Promise.all([
                        fetchJsonSafely('api/init.php'),
                        fetchJsonSafely('api/assets.php'),
                        fetchJsonSafely('api/fuel_logs.php'),
                        fetchJsonSafely('api/pm_plans.php'),
                        fetchJsonSafely('api/tire_inspections.php'),
                        fetchArchiveMarkersSafely(),
                        fetchJsonSafely('api/inspections.php'),
                        fetchJsonSafely('api/accidents.php')
                    ]);

                    globalData = Array.isArray(apiInitData?.assets) ? apiInitData : null;
                    if (!globalData || !Array.isArray(globalData.assets)) {
                        const fallbackData = await fetchJsonSafely('data.json');
                        if (fallbackData && Array.isArray(fallbackData.assets)) {
                            globalData = fallbackData;
                        } else {
                            throw new Error('Data Master Asset tidak dapat dimuat dari API terproteksi');
                        }
                    }

                    // Enrich the initialization payload with spatial asset fields.
                    // api/init.php intentionally returns a compact asset shape, while
                    // api/assets.php exposes last_latitude/last_longitude and location_name.
                    if (assetPayload?.status === 'success' && Array.isArray(assetPayload.data)) {
                        const databaseAssetsById = new Map();
                        assetPayload.data.forEach(asset => {
                            const rawId = String(asset.asset_id || asset.id || '').trim();
                            if (!rawId) return;
                            databaseAssetsById.set(rawId.toLowerCase(), asset);
                            databaseAssetsById.set(extractCleanAssetId(rawId).toLowerCase(), asset);
                            if (asset.asset_code) {
                                databaseAssetsById.set(String(asset.asset_code).trim().toLowerCase(), asset);
                            }
                        });

                        globalData.assets = (globalData.assets || []).map(asset => {
                            const rawId = String(asset.id || asset.asset_id || '').trim();
                            const databaseAsset = databaseAssetsById.get(rawId.toLowerCase())
                                || databaseAssetsById.get(extractCleanAssetId(rawId).toLowerCase());
                            if (!databaseAsset) return asset;
                            return {
                                ...asset,
                                // api/init.php only returns the compact dashboard shape.
                                // Keep the database record authoritative for every Master
                                // Asset field so edited values survive a page refresh.
                                ...databaseAsset,
                                id: asset.id || databaseAsset.asset_id,
                                location: databaseAsset.location_name || asset.location || databaseAsset.raw_location_notes || '',
                                location_name: databaseAsset.location_name || '',
                                current_location_id: databaseAsset.current_location_id ?? asset.current_location_id,
                                last_latitude: databaseAsset.last_latitude ?? asset.last_latitude,
                                last_longitude: databaseAsset.last_longitude ?? asset.last_longitude,
                                gps_updated_at: databaseAsset.gps_updated_at ?? asset.gps_updated_at
                            };
                        });
                    }

                    // Assign Fuel Logs
                    if (fuelData?.status === 'success' && Array.isArray(fuelData.data)) {
                        globalFuelLogs = fuelData.data.map(mapFuelApiRecord);
                    }

                    // Assign PM Plans
                    if (pmData?.status === 'success' && Array.isArray(pmData.data)) {
                        globalPmComparisons = pmData.data.map(p => ({
                            unitId: p.asset_id,
                            model: p.asset_code || 'Unknown',
                            schedule: 'PM ' + p.interval_hm + ' Hours',
                            location: 'Workshop',
                            planCost: 0,
                            actCost: 0,
                            diff: 0,
                            notes: p.planner_note || ''
                        }));
                    }

                    // Assign Tire Inspections
                    if (tireData?.status === 'success' && Array.isArray(tireData.data)) {
                        window.globalTireInspectionsByAsset = {};
                        tireData.data.forEach(t => {
                            const rawId = String(t.asset_id || '').trim().toLowerCase();
                            if (!rawId) return;
                            if (!window.globalTireInspectionsByAsset[rawId]) {
                                window.globalTireInspectionsByAsset[rawId] = {};
                            }
                            window.globalTireInspectionsByAsset[rawId][t.tire_position] = t;
                            tireInspectionStore[t.tire_position] = {
                                tread: t.tread_depth_mm,
                                pressure: t.air_pressure_psi
                            };
                        });
                    }

                    normalizeAssetRegistry(globalData);
                    linkExistingWorkflowRecords();
                    rebuildFleetSummary();
                    if (inspectionsRes?.status === 'success' && Array.isArray(inspectionsRes.data)) {
                        globalData.inspections = inspectionsRes.data;
                    } else {
                        globalData.inspections = [];
                    }

                    if (accidentsRes?.status === 'success' && Array.isArray(accidentsRes.data)) {
                        globalData.accidents = accidentsRes.data;
                    } else {
                        globalData.accidents = [];
                    }

                    if (archiveDataRes?.status === 'success' && Array.isArray(archiveDataRes.data)) {
                        window.archiveRecords = archiveDataRes.data;
                        window.archiveRecordsInitialized = true;
                        const archivedAssets = new Set(archiveDataRes.data.filter(x => x.item_type === 'asset').map(x => String(x.item_id).trim()));
                        const archivedP2H = new Set(archiveDataRes.data.filter(x => x.item_type === 'p2h').map(x => String(x.item_id).trim()));
                        const archivedAccidents = new Set(archiveDataRes.data.filter(x => x.item_type === 'accident').map(x => String(x.item_id).trim()));
                        if (globalData.assets) {
                            globalData.assets.forEach(a => { if (archivedAssets.has(String(a.id).trim())) a.isArchived = true; });
                        }
                        if (globalData.inspections) {
                            globalData.inspections.forEach(a => { if (archivedP2H.has(String(a.id).trim())) a.isArchived = true; });
                        }
                        if (globalData.accidents) {
                            globalData.accidents.forEach(a => { if (archivedAccidents.has(String(a.docNo).trim())) a.isArchived = true; });
                        }
                    } else {
                        console.warn("PERINGATAN: Penanda data arsip gagal dimuat dari server.");
                        window.archiveDataLoadFailed = true;
                        const banner = document.createElement("div");
                        banner.id = "archive-load-warning-banner";
                        banner.style.cssText = "background:#fee2e2; border-bottom:1px solid #ef4444; color:#b91c1c; padding:8px 16px; font-size:0.85rem; font-weight:600; text-align:center; position:sticky; top:0; z-index:9999;";
                        banner.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Peringatan Sistem: Penanda data arsip gagal disinkronkan dari server. Beberapa data arsip mungkin tampil dalam register utama.';
                        document.body.prepend(banner);
                    }

                    window.globalData = globalData;
                    initializeProjectMaster();

                    const initialData = buildProjectScopedData();
                    initDashboard(initialData);
                    if (window.initExecutiveAnalyticsPanels) window.initExecutiveAnalyticsPanels();
                    initMonitoringView(initialData.assets);
                    initAssetView(initialData.assets);
                    initWorkOrderView(initialData.work_orders);
                    initBiayaView(globalData.costs);
                    initFuelEngine(globalData);
                    initNewViews();
                    window.FleetInspectionModule?.refresh();
                    window.ConditionMonitoring?.refresh();
                    if (window.initExecutiveAnalyticsPanels) window.initExecutiveAnalyticsPanels();
                    initMonitoringView(initialData.assets);

                    if (window.renderHistoryTable) window.renderHistoryTable();
                    if (window.renderAccidentTable) window.renderAccidentTable();
                    if (window.renderCapaTable) window.renderCapaTable();

                    document.dispatchEvent(new CustomEvent('fleetdata:ready', { detail: initialData }));
                    setTimeout(() => window.initExecutiveAnalyticsPanels?.(), 150);

                    const queryView = new URLSearchParams(window.location.search).get('view') || '';
                    const hashView = window.location.hash.replace(/^#/, '');
                    const requestedView = hashView || queryView;
                    if (/^condition(?:\/|$)/i.test(requestedView)) {
                        showView('condition', '', 'menu-condition');
                        const conditionPanel = requestedView.split('/')[1];
                        if (['overview', 'tire', 'grease', 'cutting', 'battery', 'history'].includes(conditionPanel)) {
                            window.ConditionMonitoring?.switchTab(conditionPanel);
                        }
                    } else if (/^reports(?:\/|$)/i.test(requestedView)) {
                        showView('reports');
                        const reportPanel = requestedView.split('/')[1];
                        if (reportPanel === 'import' || reportPanel === 'history') {
                            window.FleetReportForms?.selectPanel(reportPanel);
                        }
                    } else if (requestedView && document.getElementById(`view-${requestedView}`)) {
                        showView(requestedView);
                    } else {
                        showView('dashboard');
                    }
                } catch (error) {
                    console.error("Error loading dashboard data: ", error);
                }
            });

            window.getAssetStatusBadge = function (status) {
                const s = (status || '').toUpperCase().trim();
                if (s === 'OPERATING') {
                    return '<span class="badge bg-operating" style="display:inline-flex; align-items:center; gap:4px;"><i class="fa-solid fa-play" style="font-size:10px;"></i> OPERATING</span>';
                }
                if (s === 'READY') {
                    return '<span class="badge" style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; font-weight:700; display:inline-flex; align-items:center; gap:4px;"><i class="fa-solid fa-circle-check" style="font-size:10px;"></i> READY</span>';
                }
                if (s === 'STANDBY') {
                    return '<span class="badge bg-standby" style="display:inline-flex; align-items:center; gap:4px;"><i class="fa-solid fa-pause-circle" style="font-size:10px;"></i> STANDBY</span>';
                }
                if (s === 'BREAKDOWN') {
                    return '<span class="badge bg-breakdown" style="display:inline-flex; align-items:center; gap:4px;"><i class="fa-solid fa-triangle-exclamation" style="font-size:10px;"></i> BREAKDOWN</span>';
                }
                if (s === 'INSPEKSI' || s === 'INSPECTION' || s === 'P2H') {
                    return '<span class="badge bg-inspeksi" style="display:inline-flex; align-items:center; gap:4px;"><i class="fa-solid fa-clipboard-check" style="font-size:10px;"></i> INSPECTION</span>';
                }
                if (s.includes('ACCIDENT') || s.includes('HOLD')) {
                    return '<span class="badge" style="background:#fef2f2; color:#991b1b; border:1px solid #fecaca; font-weight:700; display:inline-flex; align-items:center; gap:4px;"><i class="fa-solid fa-hand" style="font-size:10px;"></i> ACCIDENT HOLD</span>';
                }
                return `<span class="badge bg-secondary" style="display:inline-flex; align-items:center; gap:4px;"><i class="fa-solid fa-circle-info" style="font-size:10px;"></i> ${escapeHtml(status)}</span>`;
            };

            const EMERGENCY_WO_BATCH_SIZE = 10;
            let emergencyWoState = { items: [], rendered: 0, loading: false, list: null };

            function parseDowntimeMinutes(value) {
                if (value === null || value === undefined || value === '') return 0;
                if (typeof value === 'number' && Number.isFinite(value)) return Math.max(0, value);
                const text = String(value).toLowerCase().trim();
                if (!text || text === '-' || text === 'normal') return 0;
                const dayMatch = text.match(/(\d+(?:[.,]\d+)?)\s*(?:hari|day|h)\b/);
                const hourMatch = text.match(/(\d+(?:[.,]\d+)?)\s*(?:jam|hour|hours|j)\b/);
                const minuteMatch = text.match(/(\d+(?:[.,]\d+)?)\s*(?:menit|minute|minutes|m)\b/);
                if (dayMatch || hourMatch || minuteMatch) {
                    const days = dayMatch ? Number(dayMatch[1].replace(',', '.')) : 0;
                    const hours = hourMatch ? Number(hourMatch[1].replace(',', '.')) : 0;
                    const minutes = minuteMatch ? Number(minuteMatch[1].replace(',', '.')) : 0;
                    return Math.max(0, Math.round((days * 1440) + (hours * 60) + minutes));
                }
                const numeric = Number(text.replace(',', '.'));
                return Number.isFinite(numeric) ? Math.max(0, Math.round(numeric * 60)) : 0;
            }

            function getWorkOrderDowntimeMinutes(wo) {
                for (const key of ['downtime_minutes', 'downtimeMinutes']) {
                    const minutes = Number(wo?.[key]);
                    if (Number.isFinite(minutes)) return Math.max(0, minutes);
                }
                const hours = Number(wo?.downtime_hours);
                if (Number.isFinite(hours)) return Math.max(0, Math.round(hours * 60));
                return parseDowntimeMinutes(wo?.downtime);
            }

            function formatDowntime(valueOrWo) {
                const minutes = typeof valueOrWo === 'object' ? getWorkOrderDowntimeMinutes(valueOrWo) : parseDowntimeMinutes(valueOrWo);
                return `${Math.floor(minutes / 1440)} h ${String(Math.floor((minutes % 1440) / 60)).padStart(2, '0')} j ${String(minutes % 60).padStart(2, '0')} m`;
            }

            function getWorkOrderMechanic(wo) {
                return wo?.assignedTo || wo?.assigned_mechanic || wo?.mechanic || 'Belum ada PIC';
            }

            function formatWorkOrderSubmittedAt(wo) {
                const rawValue = wo?.reportedAt ?? wo?.reported_at ?? wo?.troubleAt ?? wo?.trouble_at ?? null;
                if (!rawValue) return 'Belum tercatat';
                const parsed = new Date(String(rawValue).replace(' ', 'T'));
                return Number.isNaN(parsed.getTime())
                    ? String(rawValue)
                    : parsed.toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' });
            }

            function getDashboardWoDateTooltip() {
                let tooltip = document.getElementById('dashboardWoDateTooltip');
                if (tooltip) return tooltip;
                tooltip = document.createElement('div');
                tooltip.id = 'dashboardWoDateTooltip';
                tooltip.className = 'dashboard-wo-date-tooltip';
                tooltip.setAttribute('role', 'tooltip');
                tooltip.setAttribute('aria-hidden', 'true');
                tooltip.innerHTML = '<span>Tanggal pengajuan WO</span><strong></strong>';
                document.body.appendChild(tooltip);
                return tooltip;
            }

            window.showDashboardWoDateTooltip = function (trigger) {
                if (!trigger) return;
                const tooltip = getDashboardWoDateTooltip();
                const value = trigger.dataset.submittedAt || 'Belum tercatat';
                tooltip.querySelector('strong').textContent = value;
                tooltip.classList.add('is-visible');
                tooltip.setAttribute('aria-hidden', 'false');
                trigger.setAttribute('aria-describedby', tooltip.id);

                const triggerRect = trigger.getBoundingClientRect();
                const tooltipRect = tooltip.getBoundingClientRect();
                const viewportGap = 8;
                const left = Math.min(
                    window.innerWidth - tooltipRect.width - viewportGap,
                    Math.max(viewportGap, triggerRect.left + (triggerRect.width - tooltipRect.width) / 2)
                );
                let top = triggerRect.bottom + 8;
                if (top + tooltipRect.height > window.innerHeight - viewportGap) {
                    top = triggerRect.top - tooltipRect.height - 8;
                }
                tooltip.style.left = `${Math.round(left)}px`;
                tooltip.style.top = `${Math.round(Math.max(viewportGap, top))}px`;
            };

            window.hideDashboardWoDateTooltip = function (trigger) {
                const tooltip = document.getElementById('dashboardWoDateTooltip');
                if (!tooltip) return;
                tooltip.classList.remove('is-visible');
                tooltip.setAttribute('aria-hidden', 'true');
                trigger?.removeAttribute('aria-describedby');
            };

            window.toggleAttentionIssue = function (button) {
                const issueBlock = button?.closest('.attention-issue-summary');
                if (!issueBlock) return;
                const expanded = issueBlock.classList.toggle('is-expanded');
                button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                button.innerHTML = expanded
                    ? '<i class="fa-solid fa-chevron-up" aria-hidden="true"></i><span>Tutup detail</span>'
                    : '<i class="fa-solid fa-chevron-down" aria-hidden="true"></i><span>Detail kendala</span>';
            };

            function updateAttentionIssueDisclosure(root = document) {
                requestAnimationFrame(() => {
                    root.querySelectorAll('.attention-issue-summary').forEach(issueBlock => {
                        if (issueBlock.classList.contains('is-expanded')) return;
                        const issueText = issueBlock.querySelector('.attention-issue-text');
                        const toggle = issueBlock.querySelector('.attention-issue-toggle');
                        if (!issueText || !toggle) return;
                        toggle.hidden = issueText.scrollHeight <= issueText.clientHeight + 1;
                    });
                });
            }

            function renderEmergencyWorkOrder(wo) {
                const woIdEsc = escapeHtml(wo.woId);
                const rawAssetId = wo.assetId || wo.unitId || '-';
                const issueText = wo.issue || wo.description || 'Tidak ada deskripsi kerusakan.';
                const issueEsc = escapeHtml(issueText);
                const mechanicEsc = escapeHtml(getWorkOrderMechanic(wo));
                const submittedAtEsc = escapeHtml(formatWorkOrderSubmittedAt(wo));
                const downtimeEsc = escapeHtml(`Down ${formatDowntime(wo)}`);
                return `<li class="attention-item attention-item-compact" aria-label="Work Order ${woIdEsc}">
                    <div class="attention-priority-row">
                        <span class="attention-wo-name"><i class="fa-solid fa-fire attention-severity-icon" aria-hidden="true"></i><strong>${woIdEsc}</strong></span>
                        <span class="attention-issue-summary" title="${issueEsc}"><strong class="attention-issue-label">Kendala:</strong><span class="attention-issue-text">${issueEsc}</span><button type="button" class="attention-issue-toggle" aria-expanded="false" onclick="toggleAttentionIssue(this)" hidden><i class="fa-solid fa-chevron-down" aria-hidden="true"></i><span>Detail kendala</span></button></span>
                        <span class="attention-downtime attention-downtime-strong" tabindex="0" data-submitted-at="${submittedAtEsc}" aria-label="${downtimeEsc}. Tanggal pengajuan WO: ${submittedAtEsc}" onmouseenter="showDashboardWoDateTooltip(this)" onmouseleave="hideDashboardWoDateTooltip(this)" onfocus="showDashboardWoDateTooltip(this)" onblur="hideDashboardWoDateTooltip(this)"><i class="fa-regular fa-clock" aria-hidden="true"></i><span>${downtimeEsc}</span></span>
                    </div>
                    <div class="attention-support-row">
                        <span class="attention-mechanic-name" title="Mekanik: ${mechanicEsc}"><i class="fa-solid fa-user-gear" aria-label="Mekanik"></i><span>${mechanicEsc}</span></span>
                        <span class="attention-compact-actions">
                            <button type="button" class="attention-action-btn" onclick="openWoDetailView('${woIdEsc}', '${escapeHtml(rawAssetId)}')" title="Buka detail Work Order ${woIdEsc}"><i class="fa-solid fa-wrench" aria-hidden="true"></i><span>Detail WO</span></button>
                            <button type="button" class="attention-action-btn btn-spb" onclick="openSpbForWo('${woIdEsc}', '${escapeHtml(rawAssetId)}')" title="Buat permintaan part untuk ${woIdEsc}"><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i><span>Minta Part</span></button>
                        </span>
                    </div>
                </li>`;
            }

            function loadNextEmergencyWoBatch() {
                const state = emergencyWoState;
                if (!state.list || state.loading || state.rendered >= state.items.length) return;
                state.loading = true;
                const loadingItem = document.createElement('li');
                loadingItem.className = 'attention-loading';
                loadingItem.innerHTML = '<span class="attention-loading-bar"></span><span>Memuat 10 WO berikutnya...</span>';
                state.list.appendChild(loadingItem);
                requestAnimationFrame(() => {
                    const nextItems = state.items.slice(state.rendered, state.rendered + EMERGENCY_WO_BATCH_SIZE);
                    loadingItem.remove();
                    state.list.insertAdjacentHTML('beforeend', nextItems.map(renderEmergencyWorkOrder).join(''));
                    updateAttentionIssueDisclosure(state.list);
                    state.rendered += nextItems.length;
                    state.loading = false;
                    if (state.rendered < state.items.length) {
                        const hint = document.createElement('li');
                        hint.className = 'attention-load-more-hint';
                        hint.innerHTML = `<i class="fa-solid fa-arrow-down" aria-hidden="true"></i> ${(state.items.length - state.rendered).toLocaleString('id-ID')} WO masih tersedia di bawah`;
                        state.list.appendChild(hint);
                    }
                });
            }

            function setupEmergencyWoList(items) {
                const attentionList = document.getElementById('attentionList');
                if (!attentionList) return;
                emergencyWoState = { items, rendered: 0, loading: false, list: attentionList };
                attentionList.replaceChildren();
                if (!items.length) {
                    attentionList.innerHTML = '<li class="attention-item attention-empty"><div class="attention-title"><i class="fa-solid fa-circle-check"></i><strong>Semua Unit Beroperasi Normal</strong></div><div class="attention-desc">Tidak ada Work Order darurat (High Priority) yang aktif saat ini.</div></li>';
                    return;
                }
                loadNextEmergencyWoBatch();
                if (attentionList.dataset.emergencyScrollBound !== 'true') {
                    attentionList.dataset.emergencyScrollBound = 'true';
                    attentionList.addEventListener('scroll', () => {
                        hideDashboardWoDateTooltip();
                        if (attentionList.scrollTop + attentionList.clientHeight >= attentionList.scrollHeight - 24) {
                            attentionList.querySelector('.attention-load-more-hint')?.remove();
                            loadNextEmergencyWoBatch();
                        }
                    });
                }
            }

            const DASHBOARD_WO_BATCH_SIZE = 10;
            let dashboardWoState = { allItems: [], items: [], rendered: 0, loading: false, scroll: null, selectedCategory: 'ALL' };

            function getDashboardWoContext(wo) {
                const rawAssetId = wo.assetId || wo.unitId || '-';
                const allAssetsList = typeof getAllAssets === 'function'
                    ? getAllAssets()
                    : (typeof globalData !== 'undefined' && Array.isArray(globalData?.assets) ? globalData.assets : []);
                const assetObj = allAssetsList.find(asset => (
                    asset.id === rawAssetId
                    || asset.asset_id === rawAssetId
                    || asset.asset_code === rawAssetId
                ));
                const ident = window.formatAssetIdentityDetails
                    ? window.formatAssetIdentityDetails(assetObj || { id: rawAssetId })
                    : { unitId: rawAssetId, sn: 'Belum tersedia', plate: 'Belum tersedia' };

                return {
                    rawAssetId,
                    ident,
                    targetUnitId: ident.unitId || rawAssetId,
                    category: String(assetObj?.category || wo.category || wo.assetCategory || wo.equipmentType || 'Lainnya').trim() || 'Lainnya'
                };
            }

            function renderDashboardWoRow(entry) {
                const wo = entry.wo || entry;
                const context = entry.context || getDashboardWoContext(wo);
                const badgeColor = wo.priority === 'High' ? 'bg-breakdown' : 'bg-operating';
                const statusText = wo.status || 'Open';
                const statusClass = statusText === 'Open' ? 'is-open' : 'is-progress';
                return `<tr class="dashboard-wo-row" data-category="${escapeHtml(context.category)}">
                    <td class="dashboard-wo-unit">
                        <div class="dashboard-wo-number">${escapeHtml(wo.woId)}</div>
                        <a href="javascript:void(0)" class="unit-prop-hover-link" onclick="window.openUnitProperties('${escapeHtml(context.targetUnitId)}'); return false;" title="Buka Properties Unit ${escapeHtml(context.targetUnitId)}">
                            <span class="unit-code-text">${escapeHtml(context.ident.unitId)}</span>
                            <i class="fa-solid fa-arrow-up-right-from-square unit-prop-hover-icon" aria-hidden="true"></i>
                        </a>
                    </td>
                    <td class="dashboard-wo-status"><span class="dashboard-wo-status-value ${statusClass}">${escapeHtml(statusText)}</span></td>
                    <td class="dashboard-wo-priority"><span class="badge ${badgeColor}">${escapeHtml(wo.priority || 'Normal')}</span></td>
                    <td class="dashboard-wo-mechanic">${escapeHtml(getWorkOrderMechanic(wo))}</td>
                    <td class="dashboard-wo-downtime">${escapeHtml(formatDowntime(wo))}</td>
                </tr>`;
            }

            function loadNextDashboardWoBatch() {
                const state = dashboardWoState;
                const tbody = document.getElementById('dashboardWoTable');
                if (!tbody || state.loading || state.rendered >= state.items.length) return;
                state.loading = true;
                const loadingRow = document.createElement('tr');
                loadingRow.className = 'dashboard-wo-loading';
                loadingRow.innerHTML = '<td colspan="5"><span class="attention-loading-bar"></span> Memuat 10 Work Order berikutnya...</td>';
                tbody.appendChild(loadingRow);

                requestAnimationFrame(() => {
                    const nextItems = state.items.slice(state.rendered, state.rendered + DASHBOARD_WO_BATCH_SIZE);
                    loadingRow.remove();
                    tbody.insertAdjacentHTML('beforeend', nextItems.map(renderDashboardWoRow).join(''));
                    state.rendered += nextItems.length;
                    state.loading = false;
                    if (state.rendered < state.items.length) {
                        const hintRow = document.createElement('tr');
                        hintRow.className = 'dashboard-wo-load-more-hint';
                        hintRow.innerHTML = `<td colspan="5"><i class="fa-solid fa-arrow-down" aria-hidden="true"></i> ${(state.items.length - state.rendered).toLocaleString('id-ID')} WO masih tersedia di bawah</td>`;
                        tbody.appendChild(hintRow);
                    }
                });
            }

            window.filterDashboardWoByCategory = function (category) {
                const state = dashboardWoState;
                const tbody = document.getElementById('dashboardWoTable');
                if (!tbody || !state.scroll) return;

                const availableCategories = new Set(state.allItems.map(entry => entry.category));
                const selectedCategory = category === 'ALL' || availableCategories.has(category) ? category : 'ALL';
                state.selectedCategory = selectedCategory;
                state.items = selectedCategory === 'ALL'
                    ? [...state.allItems]
                    : state.allItems.filter(entry => entry.category === selectedCategory);
                state.rendered = 0;
                state.loading = false;
                state.scroll.scrollTop = 0;
                tbody.replaceChildren();

                const filter = document.getElementById('dashboardWoCategoryFilter');
                if (filter && filter.value !== selectedCategory) filter.value = selectedCategory;

                if (!state.items.length) {
                    const message = selectedCategory === 'ALL'
                        ? 'Tidak ada Work Order aktif.'
                        : `Tidak ada Work Order aktif untuk jenis unit ${selectedCategory}.`;
                    tbody.innerHTML = `<tr><td colspan="5" class="dashboard-wo-empty">${escapeHtml(message)}</td></tr>`;
                    return;
                }

                loadNextDashboardWoBatch();
            };

            function setupDashboardWoList(items) {
                const scroll = document.getElementById('dashboardWoScroll');
                const tbody = document.getElementById('dashboardWoTable');
                if (!scroll || !tbody) return;
                const allItems = items.map((wo, originalIndex) => {
                    const context = getDashboardWoContext(wo);
                    return { wo, context, category: context.category, originalIndex };
                });
                const categories = [...new Set(allItems.map(entry => entry.category))]
                    .sort((left, right) => left.localeCompare(right, 'id', { sensitivity: 'base', numeric: true }));
                const filter = document.getElementById('dashboardWoCategoryFilter');
                const previousSelection = filter?.value || 'ALL';

                if (filter) {
                    filter.replaceChildren(new Option('Semua Jenis Unit', 'ALL'));
                    categories.forEach(category => filter.add(new Option(category, category)));
                    filter.disabled = categories.length === 0;
                }

                const selectedCategory = categories.includes(previousSelection) ? previousSelection : 'ALL';
                dashboardWoState = { allItems, items: [], rendered: 0, loading: false, scroll, selectedCategory };
                window.filterDashboardWoByCategory(selectedCategory);
                if (scroll.dataset.dashboardWoScrollBound !== 'true') {
                    scroll.dataset.dashboardWoScrollBound = 'true';
                    scroll.addEventListener('scroll', () => {
                        if (scroll.scrollTop + scroll.clientHeight >= scroll.scrollHeight - 24) {
                            tbody.querySelector('.dashboard-wo-load-more-hint')?.remove();
                            loadNextDashboardWoBatch();
                        }
                    });
                }
            }

            function initDashboard(data) {
                const assets = (data.assets || []).filter(a => !a.isArchived);
                const totalUnits = assets.length;
                const readyOpCount = assets.filter(a => a.status === 'READY' || a.status === 'OPERATING').length;
                const standbyCount = assets.filter(a => a.status === 'STANDBY').length;
                const breakdownCount = assets.filter(a => a.status === 'BREAKDOWN').length;
                const inspeksiCount = assets.filter(a => a.status === 'INSPEKSI').length;

                document.getElementById('kpiTotal').textContent = totalUnits;
                document.getElementById('kpiReady').textContent = readyOpCount;
                document.getElementById('kpiStandby').textContent = standbyCount;
                document.getElementById('kpiBreakdown').textContent = breakdownCount;
                document.getElementById('kpiInspeksi').textContent = inspeksiCount;

                // Show only high priority / breakdown WOs as emergency attention cards
                const emergencyWOs = data.work_orders
                    .filter(wo => wo.priority === 'High' && wo.status !== 'Closed')
                    .sort((a, b) => getWorkOrderDowntimeMinutes(b) - getWorkOrderDowntimeMinutes(a));
                setupEmergencyWoList(emergencyWOs);

                // Populate Unit per Kategori
                function getCategoryIcon(catName) {
                    const c = (catName || '').toLowerCase();
                    if (c.includes('dump') || c.includes('truck')) {
                        return '<i class="fa-solid fa-truck-front" style="color: #16a34a; margin-right: 8px; width: 16px; text-align: center;"></i>';
                    }
                    if (c.includes('excavator') || c.includes('pc')) {
                        return '<i class="fa-solid fa-truck-monster" style="color: #0284c7; margin-right: 8px; width: 16px; text-align: center;"></i>';
                    }
                    if (c.includes('bulldozer') || c.includes('dozer')) {
                        return '<i class="fa-solid fa-trowel-bricks" style="color: #ea580c; margin-right: 8px; width: 16px; text-align: center;"></i>';
                    }
                    if (c.includes('grader')) {
                        return '<i class="fa-solid fa-road" style="color: #8b5cf6; margin-right: 8px; width: 16px; text-align: center;"></i>';
                    }
                    if (c.includes('roller') || c.includes('vibro') || c.includes('compactor')) {
                        return '<i class="fa-solid fa-compact-disc" style="color: #06b6d4; margin-right: 8px; width: 16px; text-align: center;"></i>';
                    }
                    if (c.includes('berat') || c.includes('heavy')) {
                        return '<i class="fa-solid fa-truck-monster" style="color: #2563eb; margin-right: 8px; width: 16px; text-align: center;"></i>';
                    }
                    if (c.includes('ringan') || c.includes('car') || c.includes('vehicle') || c.includes('lv')) {
                        return '<i class="fa-solid fa-car" style="color: #ec4899; margin-right: 8px; width: 16px; text-align: center;"></i>';
                    }
                    if (c.includes('pendukung') || c.includes('support')) {
                        return '<i class="fa-solid fa-gears" style="color: #f59e0b; margin-right: 8px; width: 16px; text-align: center;"></i>';
                    }
                    return '<i class="fa-solid fa-cubes" style="color: #64748b; margin-right: 8px; width: 16px; text-align: center;"></i>';
                }

                const catStats = {};
                (data.assets || []).forEach(a => {
                    if (a.isArchived) return;
                    const c = a.category || 'Lainnya';
                    if (!catStats[c]) {
                        catStats[c] = { readyOp: 0, standby: 0, breakdown: 0, inspeksi: 0, total: 0 };
                    }
                    catStats[c].total++;
                    const st = (a.subStatus || a.status || '').toUpperCase();
                    if (st === 'READY' || st === 'OPERATING') {
                        catStats[c].readyOp++;
                    } else if (st === 'STANDBY') {
                        catStats[c].standby++;
                    } else if (st === 'BREAKDOWN') {
                        catStats[c].breakdown++;
                    } else if (st === 'INSPEKSI' || st === 'INSPECTION' || st === 'P2H') {
                        catStats[c].inspeksi++;
                    } else {
                        catStats[c].readyOp++;
                    }
                });

                const catArr = Object.entries(catStats).sort((a, b) => b[1].total - a[1].total).slice(0, 6);
                const catTable = document.getElementById('dashboardCategoryTable');
                if (catTable) {
                    catTable.innerHTML = catArr.map(([cat, stats]) => `
                    <tr>
                        <td class="dashboard-category-name">${getCategoryIcon(cat)} <button type="button" class="category-link" data-category="${escapeHtml(cat)}" onclick="openCategoryAssetModal(this.dataset.category)">${escapeHtml(cat)}</button></td>
                        <td style="text-align: center; font-weight: 700; color: #16a34a;">${stats.readyOp}</td>
                        <td style="text-align: center; font-weight: 700; color: #0284c7;">${stats.standby}</td>
                        <td style="text-align: center; font-weight: 700; color: #dc2626;">${stats.breakdown}</td>
                        <td style="text-align: center; font-weight: 700; color: #d97706;">${stats.inspeksi}</td>
                        <td style="text-align: center; font-weight: 800; color: var(--text-main); font-family: var(--font-mono, monospace);">${stats.total}</td>
                    </tr>
                `).join('');
                }

                // Populate all active WOs from the same database payload as the WO view.
                const activeWos = data.work_orders.filter(wo => wo.status !== 'Closed');
                setupDashboardWoList(activeWos);
            }

            let currentStatusDetailList = [];
            let currentBaseOpReadyList = [];
            let currentOpReadySubFilter = 'ALL';

            window.openCategoryAssetModal = function (category) {
                if (!globalData || !Array.isArray(globalData.assets)) {
                    alert('Data aset sedang dimuat, silakan coba beberapa saat lagi.');
                    return;
                }

                const normalizedCategory = String(category || '').trim();
                const scopedAssets = window.getProjectScopedAssets();
                const filtered = scopedAssets.filter(asset => String(asset.category || 'Lainnya').trim() === normalizedCategory);
                currentBaseOpReadyList = [];
                currentOpReadySubFilter = 'ALL';
                document.getElementById('opReadySubFilterWrapper')?.style.setProperty('display', 'none');
                document.getElementById('statusDetailTitle').textContent = `Tabulasi Unit Kategori: ${normalizedCategory} (${filtered.length} Unit)`;
                currentStatusDetailList = filtered;
                const search = document.getElementById('statusDetailSearch');
                if (search) search.value = '';
                window.renderStatusDetailTable(filtered);
                window.openModal('modalStatusDetail');
            };

            window.openStatusDetailModal = function (statusKey) {
                if (!globalData || !globalData.assets) {
                    alert("Data aset sedang dimuat, silakan coba beberapa saat lagi.");
                    return;
                }

                const scopedAssets = window.getProjectScopedAssets();
                const subFilterWrapper = document.getElementById('opReadySubFilterWrapper');

                if (statusKey === 'READY' || statusKey === 'OPERATING') {
                    let opReadyAssets = scopedAssets.filter(a => a.status === 'READY' || a.status === 'OPERATING');

                    currentBaseOpReadyList = opReadyAssets;
                    currentOpReadySubFilter = 'ALL';

                    const totalAll = opReadyAssets.length;
                    const totalOp = opReadyAssets.filter(a => (a.status || a.subStatus) === 'OPERATING').length;
                    const totalRd = opReadyAssets.filter(a => (a.status || a.subStatus) === 'READY').length;

                    document.getElementById('countSubAll').textContent = totalAll;
                    document.getElementById('countSubOperating').textContent = totalOp;
                    document.getElementById('countSubReady').textContent = totalRd;

                    document.querySelectorAll('.op-sub-btn').forEach(btn => btn.classList.remove('active'));
                    document.getElementById('btnSubAll')?.classList.add('active');

                    if (subFilterWrapper) subFilterWrapper.style.display = 'inline-flex';

                    document.getElementById('statusDetailTitle').textContent = `Tabulasi Unit Status: OPERATING / READY`;
                    currentStatusDetailList = opReadyAssets;
                } else {
                    if (subFilterWrapper) subFilterWrapper.style.display = 'none';
                    currentBaseOpReadyList = [];

                    let titleText = "Tabulasi Semua Unit Alat";
                    let filtered = scopedAssets;

                    if (statusKey === 'STANDBY') {
                        titleText = "Tabulasi Unit Status: STANDBY";
                        filtered = scopedAssets.filter(a => a.status === 'STANDBY');
                    } else if (statusKey === 'BREAKDOWN') {
                        titleText = "Tabulasi Unit Status: BREAKDOWN";
                        filtered = scopedAssets.filter(a => a.status === 'BREAKDOWN');
                    } else if (statusKey === 'INSPEKSI') {
                        titleText = "Tabulasi Unit Status: INSPEKSI / PM";
                        filtered = scopedAssets.filter(a => a.status === 'INSPEKSI');
                    }

                    document.getElementById('statusDetailTitle').textContent = titleText + ` (${filtered.length} Unit)`;
                    currentStatusDetailList = filtered;
                }

                document.getElementById('statusDetailSearch').value = '';
                window.renderStatusDetailTable(currentStatusDetailList);
                window.openModal('modalStatusDetail');
            };

            window.setOpReadySubFilter = function (subKey, btnEl) {
                currentOpReadySubFilter = subKey;

                document.querySelectorAll('.op-sub-btn').forEach(btn => btn.classList.remove('active'));
                if (btnEl) {
                    btnEl.classList.add('active');
                } else {
                    if (subKey === 'ALL') document.getElementById('btnSubAll')?.classList.add('active');
                    if (subKey === 'OPERATING') document.getElementById('btnSubOperating')?.classList.add('active');
                    if (subKey === 'READY') document.getElementById('btnSubReady')?.classList.add('active');
                }

                let filtered = currentBaseOpReadyList;
                if (subKey === 'OPERATING') {
                    filtered = currentBaseOpReadyList.filter(a => (a.status || a.subStatus) === 'OPERATING');
                } else if (subKey === 'READY') {
                    filtered = currentBaseOpReadyList.filter(a => (a.status || a.subStatus) === 'READY');
                }

                currentStatusDetailList = filtered;
                window.filterStatusDetailTable();
            };

            window.renderStatusDetailTable = function (assets) {
                const tbody = document.getElementById('statusDetailTableBody');
                document.getElementById('statusDetailCount').textContent = assets.length;

                if (assets.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding: 20px; color: var(--text-muted);">Tidak ada unit ditemukan untuk kriteria ini.</td></tr>';
                    return;
                }

                tbody.innerHTML = assets.map(asset => {
                    const parsed = window.parseAssetId ? window.parseAssetId(asset.id) : { unitId: asset.id, snPlat: '-' };
                    const targetUnitId = parsed.unitId || asset.id;
                    const effStat = asset.status || asset.subStatus;
                    const badge = window.getAssetStatusBadge(effStat);

                    return `
                    <tr>
                        <td>
                            <a href="javascript:void(0)" 
                               class="unit-prop-hover-link" 
                               onclick="closeModal('modalStatusDetail'); window.openUnitProperties('${escapeHtml(targetUnitId)}'); return false;" 
                               title="Buka Properties Unit ${escapeHtml(targetUnitId)}">
                                <span class="unit-code-text">${escapeHtml(parsed.unitId)}</span>
                                <i class="fa-solid fa-arrow-up-right-from-square unit-prop-hover-icon" aria-hidden="true"></i>
                            </a>
                        </td>
                        <td>${escapeHtml(parsed.snPlat)}</td>
                        <td>${escapeHtml(asset.category)}</td>
                        <td>
                            <a href="javascript:void(0)" 
                               class="site-prop-hover-link" 
                               onclick="closeModal('modalStatusDetail'); window.openSiteProperties('${escapeHtml(asset.location)}', 'dashboard'); return false;" 
                               title="Buka Properties &amp; Peta Site ${escapeHtml(asset.location)}">
                                <span class="site-name-text">${escapeHtml(asset.location)}</span>
                                <i class="fa-solid fa-arrow-up-right-from-square site-prop-hover-icon" aria-hidden="true"></i>
                            </a>
                        </td>
                        <td>${escapeHtml(asset.lastUpdate || '2 jam lalu')}</td>
                        <td>${badge}</td>
                        <td style="text-align: center;">
                            <button onclick="closeModal('modalStatusDetail'); openAssetModal('${escapeHtml(asset.id)}', '${escapeHtml(asset.status)}', '${escapeHtml(asset.category)}', '${escapeHtml(asset.location)}', 'modalStatusDetail')" 
                                style="border:1px solid var(--border); background:#fff; padding:4px 8px; border-radius:4px; cursor:pointer;" title="Lihat Detail Asset 360&deg;">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                `;
                }).join('');
            };

            window.filterStatusDetailTable = function () {
                const query = document.getElementById('statusDetailSearch').value.toLowerCase().trim();
                let baseList = currentStatusDetailList;

                if (!query) {
                    window.renderStatusDetailTable(baseList);
                    return;
                }

                const filtered = baseList.filter(a =>
                    (a.id || '').toLowerCase().includes(query) ||
                    (a.category || '').toLowerCase().includes(query) ||
                    (a.location || '').toLowerCase().includes(query) ||
                    (a.subStatus || a.status || '').toLowerCase().includes(query)
                );
                window.renderStatusDetailTable(filtered);
            };

            window.masterAssetSort = { col: null, dir: 'asc' };
            window.masterAssetFilters = { id: '', hull: '', model: '', sn: '', warranty: '', chassis: '', category: '', location: '', completeness: '', status: '', query3Param: '' };

            window.getAssetCompleteness = function(asset) {
                if (!asset) return { isComplete: false, missing: ['Data tidak valid'], label: 'Belum Lengkap' };
                const missing = [];
                
                const sn = (asset.serial_number && String(asset.serial_number).trim() !== '-' && String(asset.serial_number).trim() !== '') 
                    ? String(asset.serial_number).trim() 
                    : (window.parseAssetId ? window.parseAssetId(asset.id || '').snPlat : '');
                const cleanSn = (sn && sn !== '-' && sn !== 'Belum tersedia') ? sn : '';
                const cleanPlate = (asset.license_plate && String(asset.license_plate).trim() !== '-' && String(asset.license_plate).trim() !== '')
                    ? String(asset.license_plate).trim() : '';

                if (!cleanSn && !cleanPlate) {
                    missing.push('SN / Plat');
                }

                const model = (asset.make_model && String(asset.make_model).trim() !== '-' && String(asset.make_model).trim() !== '')
                    ? String(asset.make_model).trim() : '';
                if (!model) {
                    missing.push('Merek / Model');
                }

                const year = asset.year_manufacture ? parseInt(asset.year_manufacture, 10) : 0;
                if (!year || year < 1970) {
                    missing.push('Tahun');
                }

                const loc = (asset.location && String(asset.location).trim() !== '-' && String(asset.location).trim() !== '')
                    ? String(asset.location).trim() : '';
                if (!loc) {
                    missing.push('Lokasi');
                }

                return {
                    isComplete: missing.length === 0,
                    missing: missing,
                    label: missing.length === 0 ? 'Lengkap' : 'Belum Lengkap'
                };
            };

            window.formatAssetIdentityDetails = function(asset) {
                if (!asset) return { unitId: '-', hull: '-', sn: 'Belum tersedia', plate: 'Belum tersedia', model: 'Belum tersedia', year: 'Belum tersedia' };
                const parsed = window.parseAssetId ? window.parseAssetId(asset.id || asset.asset_id || '') : { unitId: asset.id, snPlat: '-' };
                const unitId = asset.asset_code || parsed.unitId || asset.id || '-';
                
                let sn = asset.serial_number ? String(asset.serial_number).trim() : '';
                if ((!sn || sn === '-') && parsed.snPlat && parsed.snPlat !== '-') {
                    sn = parsed.snPlat;
                }
                const snText = (sn && sn !== '-') ? sn : 'Belum tersedia';

                const plate = asset.license_plate ? String(asset.license_plate).trim() : '';
                const plateText = (plate && plate !== '-') ? plate : 'Belum tersedia';

                const model = asset.make_model ? String(asset.make_model).trim() : '';
                const modelText = (model && model !== '-') ? model : 'Belum tersedia';

                const year = asset.year_manufacture ? parseInt(asset.year_manufacture, 10) : 0;
                const yearText = (year && year >= 1970) ? String(year) : 'Belum tersedia';

                let hull = asset.hull_number || asset.alias_name || '';
                if (!hull && window.assetDbMapping) {
                    hull = window.assetDbMapping[unitId] || window.assetDbMapping[asset.id] || '';
                }
                const hullText = (hull && hull !== '-') ? hull : '-';

                return {
                    unitId,
                    hull: hullText,
                    sn: snText,
                    plate: plateText,
                    model: modelText,
                    year: yearText
                };
            };

            window.sortMasterAsset = function(col) {
                if (window.masterAssetSort.col === col) {
                    window.masterAssetSort.dir = window.masterAssetSort.dir === 'asc' ? 'desc' : 'asc';
                } else {
                    window.masterAssetSort.col = col;
                    window.masterAssetSort.dir = 'asc';
                }
                
                // Update icons
                const ths = document.querySelectorAll('#assetTableHeader tr:first-child th');
                ths.forEach(th => {
                    const icon = th.querySelector('i');
                    if(icon && th.getAttribute('onclick')) {
                        icon.className = 'fa-solid fa-sort';
                        if(th.getAttribute('onclick').includes(`'${col}'`)) {
                            icon.className = window.masterAssetSort.dir === 'asc' ? 'fa-solid fa-sort-up' : 'fa-solid fa-sort-down';
                        }
                    }
                });
                
                window.renderAssetTableBody();
            };

            window.filterMasterAsset = function(col, val) {
                window.masterAssetFilters[col] = val;
                window.renderAssetTableBody();
            };

            window.filterMasterAsset3Param = function(val) {
                if (!window.masterAssetFilters) window.masterAssetFilters = {};
                window.masterAssetFilters.query3Param = (val || '').trim().toLowerCase();
                window.renderAssetTableBody();
            };

            window.toggleMasterAssetAdvanced = function() {
                const el = document.getElementById('masterAssetAdvancedFilters');
                const icon = document.getElementById('iconMasterAssetAdvanced');
                if (!el) return;
                const isHidden = el.style.display === 'none' || getComputedStyle(el).display === 'none';
                el.style.display = isHidden ? 'block' : 'none';
                if (icon) {
                    icon.className = isHidden ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down';
                }
            };

            window.resetMasterAssetFilters = function() {
                window.masterAssetFilters = { id: '', hull: '', model: '', sn: '', warranty: '', chassis: '', category: '', location: '', completeness: '', status: '', query3Param: '' };
                const ids = ['filterAssetId', 'filterAssetHull', 'filterAssetModel', 'filterAssetSn', 'filterAssetWarranty', 'filterAssetChassis', 'asset3ParamSearch'];
                ids.forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
                const selects = ['filterAssetCat', 'filterAssetLoc', 'filterAssetComp', 'filterAssetStat'];
                selects.forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
                window.renderAssetTableBody();
            };

            window.renderAssetTableBody = function() {
                const tbody = document.getElementById('assetTableBody');
                if (!tbody) return;
                
                let assets = window.currentAssetData || [];
                
                // Apply Filters
                assets = assets.filter(a => {
                    const f = window.masterAssetFilters;
                    const ident = window.formatAssetIdentityDetails(a);
                    const compl = window.getAssetCompleteness(a);
                    
                    // Simultan 3-Parameter Search: Kode Unit, Nomor Lambung, SN / Plat
                    if (f.query3Param) {
                        const q = f.query3Param;
                        const matchCode = ident.unitId.toLowerCase().includes(q) || (a.id || '').toLowerCase().includes(q) || (a.asset_code || '').toLowerCase().includes(q);
                        const matchHull = ident.hull.toLowerCase().includes(q) || (a.alias_name || '').toLowerCase().includes(q) || (a.hull_number || '').toLowerCase().includes(q);
                        const matchSn = ident.sn.toLowerCase().includes(q) || ident.plate.toLowerCase().includes(q) || (a.serial_number || '').toLowerCase().includes(q) || (a.license_plate || '').toLowerCase().includes(q);
                        if (!matchCode && !matchHull && !matchSn) return false;
                    }

                    if (f.id && !ident.unitId.toLowerCase().includes(f.id.toLowerCase()) && !(a.id || '').toLowerCase().includes(f.id.toLowerCase())) return false;
                    if (f.hull && !ident.hull.toLowerCase().includes(f.hull.toLowerCase())) return false;
                    if (f.model && !ident.model.toLowerCase().includes(f.model.toLowerCase()) && !ident.year.includes(f.model)) return false;
                    if (f.sn && !ident.sn.toLowerCase().includes(f.sn.toLowerCase()) && !ident.plate.toLowerCase().includes(f.sn.toLowerCase())) return false;
                    if (f.warranty && !(a.warranty || '0 HM').toLowerCase().includes(f.warranty.toLowerCase())) return false;
                    if (f.chassis && !(a.chassis_number || a.chassis || '').toLowerCase().includes(f.chassis.toLowerCase())) return false;
                    if (f.location && !(a.location || '').toLowerCase().includes(f.location.toLowerCase())) return false;
                    if (f.category && f.category !== a.category) return false;
                    if (f.completeness) {
                        if (f.completeness === 'complete' && !compl.isComplete) return false;
                        if (f.completeness === 'incomplete' && compl.isComplete) return false;
                    }
                    if (f.status) {
                        const effStat = (a.subStatus || a.status || '').toUpperCase().replace(/\s+/g, '_');
                        const filterStat = f.status.toUpperCase().replace(/\s+/g, '_');
                        if (effStat !== filterStat) return false;
                    }
                    return true;
                });

                // Apply Sorting
                if (window.masterAssetSort.col) {
                    assets.sort((a, b) => {
                        const identA = window.formatAssetIdentityDetails(a);
                        const identB = window.formatAssetIdentityDetails(b);
                        const complA = window.getAssetCompleteness(a);
                        const complB = window.getAssetCompleteness(b);

                        let valA = a[window.masterAssetSort.col] || '';
                        let valB = b[window.masterAssetSort.col] || '';
                        
                        if (window.masterAssetSort.col === 'id') {
                            valA = identA.unitId;
                            valB = identB.unitId;
                        } else if (window.masterAssetSort.col === 'hull') {
                            valA = identA.hull;
                            valB = identB.hull;
                        } else if (window.masterAssetSort.col === 'model') {
                            valA = identA.model;
                            valB = identB.model;
                        } else if (window.masterAssetSort.col === 'sn') {
                            valA = identA.sn;
                            valB = identB.sn;
                        } else if (window.masterAssetSort.col === 'warranty') {
                            valA = a.warranty || '0 HM';
                            valB = b.warranty || '0 HM';
                        } else if (window.masterAssetSort.col === 'chassis') {
                            valA = a.chassis_number || a.chassis || '';
                            valB = b.chassis_number || b.chassis || '';
                        } else if (window.masterAssetSort.col === 'completeness') {
                            valA = complA.isComplete ? '1' : '0';
                            valB = complB.isComplete ? '1' : '0';
                        }

                        if (valA < valB) return window.masterAssetSort.dir === 'asc' ? -1 : 1;
                        if (valA > valB) return window.masterAssetSort.dir === 'asc' ? 1 : -1;
                        return 0;
                    });
                }

                window.filteredMasterAssetData = assets.slice();
                if (assets.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="9" class="master-asset-empty"><i class="fa-solid fa-filter"></i> Tidak ada unit yang cocok dengan filter atau pencarian saat ini.</td></tr>';
                    return;
                }

                tbody.innerHTML = assets.slice(0, 100).map((asset, rowIndex) => {
                    const ident = window.formatAssetIdentityDetails(asset);
                    const compl = window.getAssetCompleteness(asset);
                    const badge = window.getAssetStatusBadge(asset.status);
                    const calculatedAge = asset.asset_age ?? ((Number(ident.year) >= 1970) ? Math.max(0, new Date().getFullYear() - Number(ident.year)) : null);
                    const shown = value => value && value !== '-' && value !== 'Belum tersedia' ? escapeHtml(value) : '<em>Belum tersedia</em>';

                    return `
                    <tr data-asset-id="${escapeHtml(asset.id)}"
                        data-asset-status="${escapeHtml(asset.status)}"
                        data-asset-category="${escapeHtml(asset.category)}"
                        data-asset-location="${escapeHtml(asset.location)}"
                        onclick="openAssetActionCalloutFromElement(event, this)" 
                        style="cursor:pointer;" 
                        title="Klik 1x untuk membuka Opsi Aksi Integrasi Unit ${escapeHtml(asset.id)}">
                        <td style="color:var(--text-muted); font-variant-numeric:tabular-nums;">${rowIndex + 1}</td>
                        <td onclick="event.stopPropagation();"><a href="javascript:void(0)" class="unit-prop-hover-link" onclick="window.openUnitProperties('${escapeHtml(ident.unitId)}'); return false;"><strong class="unit-code-text">${escapeHtml(asset.id || asset.asset_id || ident.unitId)}</strong><i class="fa-solid fa-arrow-up-right-from-square unit-prop-hover-icon"></i></a><div class="asset-cell-line"><span>Kode</span><b>${shown(asset.asset_code)}</b></div><div class="asset-cell-line"><span>Lambung</span><b>${shown(ident.hull)}</b><button type="button" class="asset-inline-action" onclick="event.stopPropagation(); window.openRenameHullModal('${escapeHtml(asset.id)}', '${escapeHtml(ident.hull)}')" title="Ubah nomor lambung"><i class="fa-solid fa-pen"></i></button></div></td>
                        <td><strong>${shown(ident.model)}</strong><div class="asset-cell-line"><span>Tahun</span><b>${shown(ident.year)}</b></div><div class="asset-cell-line"><span>Usia</span><b>${calculatedAge === null ? 'Belum tersedia' : `${calculatedAge} tahun`}</b></div><div class="asset-cell-line"><span>Kapasitas</span><b>${shown(asset.capacity)}</b></div><span class="asset-cell-tag">${escapeHtml(asset.category || 'Other')}</span></td>
                        <td><div class="asset-cell-line"><span>SN</span><b class="asset-mono">${shown(ident.sn)}</b></div><div class="asset-cell-line"><span>Pelat</span><b class="asset-mono">${shown(ident.plate)}</b></div><div class="asset-cell-line"><span>Rangka</span><b class="asset-mono">${shown(asset.chassis_number || asset.chassis)}</b></div></td>
                        <td><span class="asset-warranty-value"><i class="fa-solid fa-shield-halved"></i> ${escapeHtml(asset.warranty || 'Belum diketahui')}</span></td>
                        <td onclick="event.stopPropagation();">
                            <div class="asset-cell-line"><span>Project</span><b><a href="javascript:void(0)" class="site-prop-hover-link" onclick="window.openSiteProperties('${escapeHtml(asset.location || asset.project_name || '')}', 'asset'); return false;" title="Buka Detail Lembar Properties &amp; Peta Site ${escapeHtml(asset.location || asset.project_name || '')}"><i class="fa-solid fa-location-dot site-loc-pin-icon"></i><span class="site-name-text">${shown(asset.project_name || asset.location)}</span><i class="fa-solid fa-arrow-up-right-from-square site-prop-hover-icon"></i></a></b></div><div class="asset-cell-line"><span>Posisi</span><b>${shown(asset.operational_position || asset.raw_location_notes)}</b></div>
                        </td>
                        <td><div class="asset-condition-stack">${badge}${compl.isComplete ? '<span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Lengkap</span>' : `<span class="badge badge-warning" title="${escapeHtml(compl.missing.join(', '))}"><i class="fa-solid fa-triangle-exclamation"></i> Belum lengkap</span><small>Kurang: ${escapeHtml(compl.missing.join(', '))}</small>`}</div></td>
                        <td onclick="event.stopPropagation();"><button type="button" class="btn btn-sm asset-document-button" onclick="window.openAssetLegalDocuments('${escapeHtml(asset.id)}')" title="Cek legalitas dan dokumentasi unit"><i class="fa-solid fa-folder-open"></i><span>Dokumen</span></button></td>
                        <td onclick="event.stopPropagation();">
                            <div class="asset-row-actions">
                                <button type="button" class="btn btn-sm btn-primary" onclick="window.openEditAssetModal('${escapeHtml(asset.id)}')" style="padding:3px 7px; font-size:0.72rem; border-radius:3px;" title="Edit & Lengkapi Data Unit"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                                <button type="button" class="btn btn-sm" onclick="openAssetActionCalloutFromElement(event, this.closest('tr'))" style="background:#f1f5f9; color:#475569; padding:3px 6px; font-size:0.72rem; border-radius:3px;" title="Menu Aksi Lainnya"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                            </div>
                        </td>
                    </tr>
                    `;
                }).join('');
            };

            window.openAssetLegalDocuments = async function(assetId) {
                const subtitle = document.getElementById('assetLegalDocumentsSubtitle');
                const body = document.getElementById('assetLegalDocumentsBody');
                if (subtitle) subtitle.textContent = assetId || 'Master Asset';
                if (body) body.innerHTML = '<div class="asset-document-loading"><i class="fa-solid fa-spinner fa-spin"></i> Memuat data legalitas dan dokumentasi...</div>';
                openModal('modalAssetLegalDocuments');
                try {
                    const response = await fetch(`api/assets.php?id=${encodeURIComponent(assetId)}`, { cache: 'no-store' });
                    const result = await response.json();
                    if (!response.ok || result.status !== 'success') throw new Error(result.message || 'Data tidak dapat dimuat.');
                    const asset = result.data || {};
                    const photos = Array.isArray(asset.gallery_photos) ? asset.gallery_photos : [];
                    const formatDate = value => value ? new Date(`${String(value).slice(0, 10)}T00:00:00`).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : 'Belum tersedia';
                    const legalItems = [
                        ['STNK berlaku sampai', asset.legal_stnk_expiry],
                        ['KIR berlaku sampai', asset.legal_kir_expiry],
                        ['SILO berlaku sampai', asset.legal_silo_expiry]
                    ];
                    body.innerHTML = `
                        <section class="asset-legal-summary">
                            ${legalItems.map(([label, value]) => `<div><span>${label}</span><strong>${formatDate(value)}</strong></div>`).join('')}
                        </section>
                        <section class="asset-document-section">
                            <div class="asset-document-section-heading"><div><h3>Foto unit</h3><p>${photos.length} foto tersimpan</p></div><button type="button" class="btn btn-sm" onclick="closeModal('modalAssetLegalDocuments'); window.openEditAssetModal('${escapeHtml(assetId)}')"><i class="fa-solid fa-pen-to-square"></i> Kelola dokumentasi</button></div>
                            <div class="asset-document-gallery">${photos.length ? photos.map(photo => `<a href="api/asset_files.php?kind=photo&amp;photo_id=${encodeURIComponent(photo.photo_id)}" target="_blank" rel="noopener"><img src="api/asset_files.php?kind=photo&amp;photo_id=${encodeURIComponent(photo.photo_id)}" alt="${escapeHtml(photo.photo_title || 'Foto unit')}"><span>${escapeHtml(photo.photo_title || 'Foto unit')}</span></a>`).join('') : '<div class="asset-document-empty-state"><i class="fa-regular fa-images"></i> Belum ada foto unit.</div>'}</div>
                        </section>
                        <section class="asset-document-section">
                            <div class="asset-document-section-heading"><div><h3>Dokumen PDF</h3><p>Dokumen JO servis unit</p></div></div>
                            ${asset.service_manual_pdf_url ? `<a class="asset-document-pdf-link" href="api/asset_files.php?kind=pdf&amp;asset_id=${encodeURIComponent(assetId)}" target="_blank" rel="noopener"><i class="fa-regular fa-file-pdf"></i><span><strong>Buka PDF JO servis</strong><small>Dokumen tersimpan</small></span><i class="fa-solid fa-arrow-up-right-from-square"></i></a>` : '<div class="asset-document-empty-state"><i class="fa-regular fa-file-pdf"></i> Belum ada PDF JO servis.</div>'}
                        </section>`;
                } catch (error) {
                    if (body) body.innerHTML = `<div class="asset-document-error"><i class="fa-solid fa-triangle-exclamation"></i> ${escapeHtml(error?.message || 'Data legalitas dan dokumentasi gagal dimuat.')}</div>`;
                }
            };

            window.openMasterAssetPrintDialog = function() {
                const all = window.currentAssetData || [];
                const allCount = document.getElementById('masterAssetPrintAllCount');
                if (allCount) allCount.textContent = `${all.length} unit aktif`;
                const optionSets = {
                    masterAssetPrintCategory: [...new Set(all.map(asset => String(asset.category || '').trim()).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'id')),
                    masterAssetPrintStatus: [...new Set(all.map(asset => String(asset.subStatus || asset.status || '').trim().replaceAll('_', ' ')).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'id')),
                    masterAssetPrintSite: [...new Set(all.map(asset => String(asset.location || asset.project_name || '').trim()).filter(Boolean))].sort((a, b) => a.localeCompare(b, 'id'))
                };
                Object.entries(optionSets).forEach(([id, values]) => {
                    const select = document.getElementById(id);
                    if (!select) return;
                    const previous = select.value;
                    const placeholder = id === 'masterAssetPrintCategory' ? 'Pilih kategori' : id === 'masterAssetPrintStatus' ? 'Pilih status' : 'Pilih site';
                    select.innerHTML = `<option value="">${placeholder}</option>${values.map(value => `<option value="${escapeHtml(value)}">${escapeHtml(value)}</option>`).join('')}`;
                    if (values.includes(previous)) select.value = previous;
                });
                window.updateMasterAssetPrintFilters();
                openModal('modalMasterAssetPrint');
            };

            window.getMasterAssetPrintSelection = function() {
                const definitions = [
                    { key: 'category', checkbox: 'masterAssetPrintUseCategory', select: 'masterAssetPrintCategory', label: 'Kategori' },
                    { key: 'status', checkbox: 'masterAssetPrintUseStatus', select: 'masterAssetPrintStatus', label: 'Status' },
                    { key: 'site', checkbox: 'masterAssetPrintUseSite', select: 'masterAssetPrintSite', label: 'Site' }
                ];
                return definitions.map(definition => ({
                    ...definition,
                    active: Boolean(document.getElementById(definition.checkbox)?.checked),
                    value: String(document.getElementById(definition.select)?.value || '').trim()
                }));
            };

            window.getMasterAssetPrintFilteredAssets = function() {
                const all = window.currentAssetData || [];
                const activeFilters = window.getMasterAssetPrintSelection().filter(filter => filter.active);
                if (!activeFilters.length || activeFilters.some(filter => !filter.value)) return [];
                const normalized = value => String(value || '').trim().replaceAll('_', ' ').toLocaleLowerCase('id');
                return all.filter(asset => activeFilters.every(filter => {
                    if (filter.key === 'category') return normalized(asset.category) === normalized(filter.value);
                    if (filter.key === 'status') return normalized(asset.subStatus || asset.status) === normalized(filter.value);
                    return normalized(asset.location || asset.project_name) === normalized(filter.value);
                }));
            };

            window.updateMasterAssetPrintFilters = function() {
                const scope = document.querySelector('input[name="masterAssetPrintScope"]:checked')?.value || 'all';
                const isFiltered = scope === 'filtered';
                const panel = document.getElementById('masterAssetPrintFilters');
                const count = document.getElementById('masterAssetPrintFilteredCount');
                const note = document.getElementById('masterAssetPrintFilterNote');
                const submit = document.getElementById('masterAssetPrintSubmit');
                if (panel) panel.hidden = !isFiltered;
                const selections = window.getMasterAssetPrintSelection();
                selections.forEach(filter => {
                    const select = document.getElementById(filter.select);
                    if (select) select.disabled = !filter.active;
                });
                if (!isFiltered) {
                    if (submit) submit.disabled = false;
                    return;
                }
                const active = selections.filter(filter => filter.active);
                const incomplete = active.some(filter => !filter.value);
                const assets = (!active.length || incomplete) ? [] : window.getMasterAssetPrintFilteredAssets();
                if (count) count.textContent = !active.length ? 'Pilih minimal satu filter' : incomplete ? 'Lengkapi pilihan filter' : `${assets.length} unit sesuai filter`;
                if (note) {
                    note.textContent = !active.length
                        ? 'Pilih minimal satu filter untuk melanjutkan.'
                        : incomplete
                            ? 'Pilih nilai pada setiap filter yang dicentang.'
                            : `${active.map(filter => `${filter.label}: ${filter.value}`).join(' · ')} · ${assets.length} unit`;
                    note.classList.toggle('is-ready', active.length > 0 && !incomplete && assets.length > 0);
                }
                if (submit) submit.disabled = !active.length || incomplete || assets.length === 0;
            };

            window.printMasterAssetReport = function() {
                const scope = document.querySelector('input[name="masterAssetPrintScope"]:checked')?.value || 'all';
                const all = window.currentAssetData || [];
                const assets = scope === 'filtered' ? window.getMasterAssetPrintFilteredAssets() : all;
                if (!assets.length) {
                    alert('Tidak ada data Master Asset untuk dicetak.');
                    return;
                }
                const reportWindow = window.open('', '_blank');
                if (!reportWindow) {
                    alert('Popup pratinjau diblokir browser. Izinkan popup lalu coba lagi.');
                    return;
                }
                reportWindow.opener = null;
                const text = value => escapeHtml(value === null || value === undefined || value === '' || value === '-' ? 'Belum tersedia' : String(value));
                const rows = assets.map((asset, index) => {
                    const ident = window.formatAssetIdentityDetails(asset);
                    const compl = window.getAssetCompleteness(asset);
                    const age = asset.asset_age ?? ((Number(ident.year) >= 1970) ? Math.max(0, new Date().getFullYear() - Number(ident.year)) : null);
                    const status = String(asset.subStatus || asset.status || 'Belum tersedia').replaceAll('_', ' ');
                    return `<tr><td>${index + 1}</td><td>${text(asset.id || asset.asset_id)}</td><td>${text(asset.asset_code)}</td><td>${text(ident.hull)}</td><td>${text(ident.model)}</td><td>${text(asset.dealer || asset.vehicle_type)}</td><td>${text(ident.year)}</td><td>${age === null ? 'Belum tersedia' : `${age} tahun`}</td><td>${text(asset.capacity)}</td><td>${text(ident.sn)}</td><td>${text(ident.plate)}</td><td>${text(asset.chassis_number || asset.chassis)}</td><td>${text(asset.category)}</td><td>${text(asset.project_name)}</td><td>${text(asset.location || asset.operational_position)}</td><td>${text(status)}</td><td>${text(asset.warranty || 'Belum diketahui')}</td><td>${compl.isComplete ? 'Lengkap' : `Kurang: ${text(compl.missing.join(', '))}`}</td></tr>`;
                }).join('');
                const printedAt = new Date().toLocaleString('id-ID', { timeZone: 'Asia/Bangkok', dateStyle: 'long', timeStyle: 'short' });
                const logoUrl = new URL('assets/logo-pt-bina-rekayasa-anugrah.png', window.location.href).href;
                const reportStats = {
                    total: assets.length,
                    ready: assets.filter(asset => String(asset.status || '').toUpperCase() === 'READY').length,
                    operating: assets.filter(asset => String(asset.status || '').toUpperCase() === 'OPERATING').length,
                    breakdown: assets.filter(asset => ['BREAKDOWN', 'INSPEKSI'].includes(String(asset.status || '').toUpperCase())).length,
                    complete: assets.filter(asset => window.getAssetCompleteness(asset).isComplete).length
                };
                const reportScopeLabel = scope === 'filtered'
                    ? `Filter: ${window.getMasterAssetPrintSelection().filter(filter => filter.active).map(filter => `${filter.label} = ${filter.value}`).join(' · ')}`
                    : 'Seluruh unit aktif Master Asset';
                reportWindow.document.write(`<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Laporan Master Asset</title><style>
                    @page { size: A4 landscape; margin: 5mm; }
                    * { box-sizing: border-box; }
                    html { min-height:100%; background:#e9eef4; }
                    body { width:min(297mm, 100%); min-height:210mm; margin:0 auto; padding:5mm; color:#172033; background:#fff; box-shadow:0 2mm 8mm rgba(23,58,99,.14); font-family:Arial, Helvetica, sans-serif; font-size:7pt; }
                    .report-head { display:grid; grid-template-columns:1fr 1.4fr 1fr; align-items:center; gap:6mm; border-bottom:0.7mm solid #173a63; padding-bottom:3mm; margin-bottom:3mm; }
                    .brand { display:flex; align-items:center; gap:3mm; min-width:0; }
                    .brand img { width:28mm; height:15mm; object-fit:contain; object-position:left center; }
                    .brand strong { display:block; color:#102a4c; font-size:8pt; line-height:1.2; }
                    .brand small { display:block; margin-top:1mm; color:#64748b; font-size:6.5pt; }
                    .report-title { text-align:center; }
                    h1 { margin:0; font-size:16pt; line-height:1.15; color:#102a4c; letter-spacing:.01em; }
                    .report-title p { margin-top:1mm; color:#526174; font-size:7pt; }
                    .meta { text-align:right; font-size:7pt; color:#526174; } .meta strong { display:block; color:#172033; font-size:9pt; }
                    .report-summary { display:grid; grid-template-columns:repeat(5, 1fr); gap:1.5mm; margin-bottom:3mm; }
                    .summary-item { padding:1.6mm 2mm; border:0.25mm solid #c8d3df; background:#f5f8fb; }
                    .summary-item span { display:block; color:#64748b; font-size:6pt; text-transform:uppercase; letter-spacing:.04em; }
                    .summary-item b { display:block; margin-top:.5mm; color:#172b45; font-size:8.5pt; }
                    .table-viewport { width:100%; overflow-x:auto; }
                    table { width:100%; height:auto; border-collapse:collapse; table-layout:fixed; }
                    thead { display:table-header-group; }
                    tr, th, td { height:auto; }
                    th, td { border:0.25mm solid #b9c5d4; padding:1.1mm .85mm; text-align:left; vertical-align:top; overflow-wrap:break-word; word-break:normal; line-height:1.2; }
                    th { background:#dfe8f2; color:#172b45; font-size:6pt; text-transform:uppercase; font-weight:700; }
                    td { font-size:6.2pt; }
                    tbody tr:nth-child(even) { background:#f5f7fa; }
                    th:nth-child(1), td:nth-child(1), th:nth-child(7) { white-space:nowrap; }
                    td:nth-child(7) { white-space:normal; overflow-wrap:break-word; }
                    td:nth-child(2), td:nth-child(3), td:nth-child(4), td:nth-child(10), td:nth-child(11), td:nth-child(12) { overflow-wrap:anywhere; }
                    .print-actions { position:sticky; top:0; z-index:2; margin-bottom:4mm; padding:2mm; text-align:right; background:#fff; } button { border:0; border-radius:3px; background:#0b73d9; color:white; padding:2.5mm 4mm; font-weight:bold; cursor:pointer; }
                    .page-footer { position:fixed; left:0; right:0; bottom:0; display:flex; justify-content:space-between; gap:5mm; padding-top:1.5mm; border-top:0.2mm solid #cbd5e1; color:#64748b; font-size:6.2pt; background:#fff; }
                    @media screen and (max-width:1200px) { .table-viewport { border-right:0.25mm solid #b9c5d4; } table { min-width:1180px; } }
                    @media print { html { background:#fff; } .print-actions { display:none; } body { width:auto; min-height:0; margin:0; padding:0; box-shadow:none; } .table-viewport { overflow:visible; } tr { break-inside:avoid; page-break-inside:avoid; } }
                </style></head><body><div class="print-actions"><button onclick="window.print()">Cetak / Simpan PDF</button></div><header class="report-head"><div class="brand"><img src="${text(logoUrl)}" alt="Logo PT Bina Rekayasa Anugrah"><div><strong>PT Bina Rekayasa Anugrah</strong><small>Equipment &amp; Maintenance</small></div></div><div class="report-title"><h1>Laporan Master Asset</h1><p>${text(reportScopeLabel)}</p></div><div class="meta"><strong>${assets.length} unit</strong>Data aktif yang dilaporkan</div></header><section class="report-summary"><div class="summary-item"><span>Total unit</span><b>${reportStats.total}</b></div><div class="summary-item"><span>Ready</span><b>${reportStats.ready}</b></div><div class="summary-item"><span>Operating</span><b>${reportStats.operating}</b></div><div class="summary-item"><span>Breakdown / inspeksi</span><b>${reportStats.breakdown}</b></div><div class="summary-item"><span>Data lengkap</span><b>${reportStats.complete}</b></div></section><div class="table-viewport"><table><colgroup><col style="width:2%"><col style="width:5%"><col style="width:5%"><col style="width:6.5%"><col style="width:6.5%"><col style="width:5.5%"><col style="width:3%"><col style="width:3.5%"><col style="width:5%"><col style="width:7.5%"><col style="width:5.5%"><col style="width:7.5%"><col style="width:5.5%"><col style="width:5.5%"><col style="width:6%"><col style="width:5%"><col style="width:5.5%"><col style="width:10%"></colgroup><thead><tr><th>No.</th><th>Asset ID</th><th>Kode Unit</th><th>No. Lambung</th><th>Nama Aset</th><th>Dealer / Tipe</th><th>Tahun</th><th>Usia</th><th>Kapasitas</th><th>Serial Number</th><th>Pelat Kendaraan</th><th>Nomor Rangka</th><th>Kategori</th><th>Project</th><th>Lokasi / Posisi</th><th>Status</th><th>Status Warranty</th><th>Kelengkapan</th></tr></thead><tbody>${rows}</tbody></table></div><footer class="page-footer"><span>Dicetak: ${text(printedAt)}</span><span>Master Asset · PT Bina Rekayasa Anugrah · Legalitas dan dokumentasi tersedia di aplikasi web</span></footer></body></html>`);
                reportWindow.document.close();
                closeModal('modalMasterAssetPrint');
            };

            window.openRenameHullModal = function(assetId, currentHull) {
                const asset = (window.currentAssetData || []).find(a => (a.id || a.asset_id) === assetId);
                const elId = document.getElementById('renameHullAssetId');
                const elCode = document.getElementById('renameHullAssetCode');
                const elCurr = document.getElementById('renameHullCurrent');
                const elNew = document.getElementById('renameHullNewInput');
                const elNotes = document.getElementById('renameHullNotes');
                if (elId) elId.value = assetId;
                if (elCode) elCode.value = (asset ? asset.asset_code : assetId) || assetId;
                if (elCurr) elCurr.value = (currentHull && currentHull !== '-') ? currentHull : 'Belum ditetapkan';
                if (elNew) elNew.value = (currentHull && currentHull !== '-') ? currentHull : '';
                if (elNotes) elNotes.value = '';
                const effectiveDate = document.getElementById('renameHullEffectiveDate');
                if (effectiveDate) effectiveDate.value = new Date().toLocaleDateString('en-CA');
                window.openModal('modalRenameHullNumber');
                setTimeout(() => elNew && elNew.focus(), 80);
            };

            window.submitRenameHull = async function() {
                const assetId = document.getElementById('renameHullAssetId')?.value;
                const newHull = document.getElementById('renameHullNewInput')?.value.trim();
                const notes = document.getElementById('renameHullNotes')?.value.trim();
                const effectiveDate = document.getElementById('renameHullEffectiveDate')?.value;
                if (!newHull || !notes || !effectiveDate) {
                    alert('Nomor lambung, alasan, dan tanggal efektif wajib diisi.');
                    return;
                }
                const btn = document.getElementById('btnSubmitRenameHull');
                if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...'; }
                try {
                    const res = await fetch(`api/assets.php?id=${encodeURIComponent(assetId)}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({
                            asset_id: assetId,
                            new_hull_number: newHull,
                            action: 'rename_hull',
                            change_reason: notes,
                            effective_date: effectiveDate
                        })
                    });
                    const json = await res.json();
                    if (!res.ok || json.status === 'error') throw new Error(json.message || 'Gagal update nomor lambung');
                    
                    // Update in-memory data
                    const asset = (window.currentAssetData || []).find(a => (a.id || a.asset_id) === assetId);
                    if (asset) {
                        asset.alias_name = newHull;
                        asset.hull_number = newHull;
                    }
                    if (!window.assetDbMapping) window.assetDbMapping = {};
                    window.assetDbMapping[assetId] = newHull;
                    if (asset && asset.asset_code) window.assetDbMapping[asset.asset_code] = newHull;

                    window.closeModal('modalRenameHullNumber');
                    if (typeof window.renderAssetTableBody === 'function') {
                        window.renderAssetTableBody();
                    }
                    if (typeof window.loadHullHistory === 'function') {
                        window.loadHullHistory(assetId);
                    }
                    alert(`Nomor lambung unit ${assetId} berhasil diubah menjadi ${newHull}.`);
                } catch (err) {
                    console.error(err);
                    alert('Gagal mengubah nomor lambung: ' + err.message);
                } finally {
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Simpan Nomor Lambung'; }
                }
            };

            function initAssetView(assets) {
                const tbody = document.getElementById('assetTableBody');
                if (!assets || !tbody) return;

                window.currentAssetData = assets.filter(a => !a.isArchived);
                assets = window.currentAssetData;

                // 1. Calculate Master Asset KPI Stats
                const totalCount = assets.length || 48;
                const readyCount = assets.filter(a => a.status === 'READY').length;
                const standbyCount = assets.filter(a => a.status === 'STANDBY').length;
                const breakdownCount = assets.filter(a => a.status === 'BREAKDOWN' || a.status === 'INSPEKSI').length;
                const paPercentage = totalCount > 0 ? (((readyCount + standbyCount) / totalCount) * 100).toFixed(1) + '%' : '87.5%';

                const elTotal = document.getElementById('kpiAssetTotal');
                const elReady = document.getElementById('kpiAssetReady');
                const elStandby = document.getElementById('kpiAssetStandby');
                const elBreakdown = document.getElementById('kpiAssetBreakdown');
                const elPA = document.getElementById('kpiAssetPA');

                if (elTotal) elTotal.innerText = `${totalCount} Unit`;
                if (elReady) elReady.innerText = `${readyCount} Unit`;
                if (elStandby) elStandby.innerText = `${standbyCount} Unit`;
                if (elBreakdown) elBreakdown.innerText = `${breakdownCount} Unit`;
                if (elPA) elPA.innerText = paPercentage;

                // 2. Render Asset Rows with Rich Linkage Actions
                window.renderAssetTableBody();
            }

            function renderKanbanCardHtml(wo, statusKey) {
                const isEmergency = ['High', 'Emergency'].includes(wo.priority);
                const issueText = wo.issue || wo.description || 'Tidak ada deskripsi kerusakan.';
                const issueShort = escapeHtml(issueText.length > 55 ? issueText.substring(0, 55) + '...' : issueText);
                const rawAssetId = wo.assetId || wo.unitId || '';
                const allAssetsList = typeof getAllAssets === 'function' ? getAllAssets() : (typeof globalData !== 'undefined' && Array.isArray(globalData?.assets) ? globalData.assets : []);
                const assetObj = allAssetsList.find(item => item.id === rawAssetId || item.asset_id === rawAssetId || item.asset_code === rawAssetId);
                const ident = window.formatAssetIdentityDetails
                    ? window.formatAssetIdentityDetails(assetObj || { id: rawAssetId })
                    : { unitId: rawAssetId || '-', sn: 'Belum tersedia', plate: 'Belum tersedia' };
                const escapedAsset = escapeHtml(ident.unitId);

                const partsReq = wo.partsRequirement || wo.parts_requirement || '';
                const partsBadgeHtml = partsReq === 'REQUIRED'
                    ? `<span class="badge kanban-part-badge" title="Pekerjaan memerlukan suku cadang / SPB"><i class="fa-solid fa-boxes-stacked"></i> Butuh Part</span>`
                    : (statusKey === 'Waiting Part' ? `<span class="badge kanban-part-badge" style="background:#fffbeb; color:#b45309; border-color:#fde68a;" title="Menunggu kedatangan suku cadang"><i class="fa-solid fa-hourglass-half"></i> Waiting Part</span>` : '');

                return `
                <div class="kanban-card ${wo.priority}" 
                     id="wo-card-${escapeHtml(wo.woId)}" 
                     data-wo-id="${escapeHtml(wo.woId)}"
                     data-status="${statusKey}"
                     data-asset="${escapeHtml(rawAssetId)}"
                     draggable="false"
                     aria-grabbed="false"
                     onclick="handleWoCardClick(event, '${escapeHtml(wo.woId)}', '${escapeHtml(rawAssetId)}')"
                     style="cursor:grab; background:var(--bg-surface); border:1px solid var(--border); border-left:3px solid ${isEmergency ? 'var(--danger)' : 'var(--primary)'}; border-radius:2px; padding:10px 12px; margin-bottom:8px; box-shadow:none;" 
                     title="Klik untuk Detail 360&deg; | Drag untuk ubah status">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:1px dashed var(--border); padding-bottom:6px; margin-bottom:6px;">
                        <span class="font-mono" style="font-weight:700; color:var(--primary); font-size:0.85rem;">${escapeHtml(wo.woId)}</span>
                        <div style="text-align:right;">
                            <span class="font-mono" style="font-weight:700; color:var(--text-main); font-size:0.82rem;">${escapedAsset}</span>
                            <div style="font-size:0.68rem; color:var(--text-muted);">SN: ${escapeHtml(ident.sn)} &bull; Plat: ${escapeHtml(ident.plate)}</div>
                        </div>
                    </div>
                    ${wo.sourceReportNumber ? `<div style="font-size:0.7rem; color:var(--success); margin-bottom:6px;"><i class="fa-solid fa-file-circle-check"></i> ${escapeHtml(wo.sourceReportNumber)}</div>` : ''}
                    <div style="font-size:0.7rem; color:var(--primary); margin-bottom:5px; text-transform:uppercase; letter-spacing:.04em;">${escapeHtml(wo.maintenanceType || 'Corrective')}</div>
                    <div style="font-size:0.8rem; color:var(--text-main); line-height:1.35; margin-bottom:8px; font-weight:500;">
                        ${issueShort}
                    </div>
                    <div style="font-size:0.75rem; color:var(--text-muted); margin-bottom:6px; display:flex; align-items:center; gap:5px;">
                        <i class="fa-solid fa-user-gear"></i> <span style="font-weight:600;">${escapeHtml(getWorkOrderMechanic(wo))}</span>
                    </div>
                    ${partsBadgeHtml ? `<div style="margin-bottom:6px;">${partsBadgeHtml}</div>` : ''}
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; font-size:0.75rem;">
                        <span style="color:var(--text-muted); font-family:var(--font-mono);">
                            ${wo.downtime ? `<i class="fa-regular fa-clock" style="color:var(--danger);"></i> <strong style="color:var(--danger);">${escapeHtml(wo.downtime)}</strong>` : '<i class="fa-regular fa-clock"></i> Normal'}
                        </span>
                        <span style="display:flex; align-items:center; gap:6px;">
                            <span class="badge ${isEmergency ? 'bg-breakdown' : 'bg-operating'}" style="border-radius:2px; font-size:0.7rem; padding:2px 6px;">
                                ${isEmergency ? 'EMERGENCY' : escapeHtml(wo.priority || 'NORMAL')}
                            </span>
                            <button type="button" class="btn btn-primary" onclick="event.stopPropagation(); openWoDetailModal('${escapeHtml(wo.woId)}', '${escapedAsset}')" style="padding:3px 7px; font-size:0.7rem; border-radius:3px;" title="Buka detail Work Order"><i class="fa-solid fa-eye"></i> Detail</button>
                        </span>
                    </div>
                </div>
            `;
            }

            let workOrderViewMode = 'kanban';
            let workOrderSource = [];

            window.switchWorkOrderMode = function (mode) {
                workOrderViewMode = mode === 'table' ? 'table' : 'kanban';
                const kanbanPanel = document.getElementById('woKanbanPanel');
                const tablePanel = document.getElementById('woTablePanel');
                if (kanbanPanel) kanbanPanel.style.display = workOrderViewMode === 'kanban' ? '' : 'none';
                if (tablePanel) tablePanel.style.display = workOrderViewMode === 'table' ? '' : 'none';
                document.querySelectorAll('[data-wo-mode]').forEach(button => {
                    const active = button.dataset.woMode === workOrderViewMode;
                    button.classList.toggle('active', active);
                    button.setAttribute('aria-pressed', String(active));
                });
            };

            function getFilteredWorkOrders() {
                const query = String(document.getElementById('woWorkspaceSearch')?.value || '').trim().toLowerCase();
                const status = document.getElementById('woWorkspaceStatus')?.value || '';
                const priority = document.getElementById('woWorkspacePriority')?.value || '';
                return workOrderSource.filter(wo => {
                    if (status && wo.status !== status) return false;
                    if (priority && wo.priority !== priority) return false;
                    if (!query) return true;
                    const asset = getAllAssets().find(item => item.id === extractCleanAssetId(wo.assetId || wo.unitId || ''));
                    return [wo.woId, wo.sourceReportNumber, wo.assetId, wo.unitId, wo.issue, wo.description, wo.assignedTo, wo.mechanic, asset?.location]
                        .some(value => String(value || '').toLowerCase().includes(query));
                });
            }

            function renderWorkOrderTable(workOrders) {
                const tbody = document.getElementById('woWorkspaceTableBody');
                if (!tbody) return;
                if (!workOrders.length) {
                    tbody.innerHTML = '<tr><td colspan="9" class="wo-table-empty"><i class="fa-solid fa-inbox"></i><span>Tidak ada Work Order yang sesuai filter.</span></td></tr>';
                    return;
                }
                tbody.innerHTML = workOrders.map(wo => {
                    const assetId = extractCleanAssetId(wo.assetId || wo.unitId || '');
                    const asset = getAllAssets().find(item => item.id === assetId || item.asset_id === assetId || item.asset_code === assetId);
                    const ident = window.formatAssetIdentityDetails
                        ? window.formatAssetIdentityDetails(asset || { id: assetId })
                        : { unitId: assetId, sn: 'Belum tersedia', plate: 'Belum tersedia' };
                    const issue = wo.issue || wo.description || 'Belum ada uraian keluhan';
                    const mechanic = wo.assignedTo || wo.mechanic || 'Belum ditugaskan';
                    const statusClass = wo.status === 'Closed' ? 'closed' : wo.status === 'In Progress' ? 'progress' : 'open';
                    const priorityClass = ['High', 'Emergency'].includes(wo.priority) ? 'high' : 'normal';
                    return `<tr>
                        <td><button type="button" class="wo-id-link" onclick="openWoDetailModal('${escapeHtml(wo.woId)}', '${escapeHtml(assetId)}')">${escapeHtml(wo.woId)}</button>${wo.sourceReportNumber ? `<small style="display:block; color:var(--success); margin-top:4px;"><i class="fa-solid fa-file-circle-check"></i> ${escapeHtml(wo.sourceReportNumber)}</small>` : ''}</td>
                        <td>
                            <strong>${escapeHtml(ident.unitId || '-')}</strong>
                            <small style="display:block; font-size:0.75rem; color:var(--text-muted, #64748b);">SN: ${escapeHtml(ident.sn)} | Plat: ${escapeHtml(ident.plate)}</small>
                        </td>
                        <td>${escapeHtml(asset?.location || 'Belum terpetakan')}</td>
                        <td class="wo-issue-cell" title="${escapeHtml(issue)}">${escapeHtml(issue)}</td>
                        <td><span class="wo-priority-pill ${priorityClass}">${escapeHtml(wo.priority || 'Normal')}</span></td>
                        <td><span class="wo-status-pill ${statusClass}">${escapeHtml(wo.status || 'Open')}</span></td>
                        <td>${escapeHtml(mechanic)}</td>
                        
                        ${(() => {
                            // Target Downtime SOP: High/Emergency = 2 Days (2880 mins), Normal = 7 Days (10080 mins)
                            const isHigh = wo.priority === 'High' || wo.priority === 'Emergency';
                            const targetMins = isHigh ? 2880 : 10080;
                            const targetStr = isHigh ? '2 Hari' : '7 Hari';
                            
                            const actualMins = getWorkOrderDowntimeMinutes(wo);
                            const isOverdue = actualMins > targetMins;
                            
                            const actualStr = escapeHtml(wo.downtime || '0 jam 00 menit');
                            
                            if (isOverdue) {
                                return `
                                    <td><span class="badge" style="background:#fef2f2; color:#dc2626; border:1px solid #fecaca; padding:2px 8px; border-radius:12px; font-size:0.85rem;"><i class="fa-solid fa-bullseye"></i> ${targetStr}</span></td>
                                    <td style="color:#dc2626; font-weight:700;"><i class="fa-solid fa-triangle-exclamation" title="Melebihi target downtime"></i> ${actualStr}</td>
                                `;
                            } else {
                                return `
                                    <td><span class="badge" style="background:#f8fafc; color:#64748b; border:1px solid #e2e8f0; padding:2px 8px; border-radius:12px; font-size:0.85rem;"><i class="fa-solid fa-bullseye"></i> ${targetStr}</span></td>
                                    <td>${actualStr}</td>
                                `;
                            }
                        })()}
                        <td><button type="button" class="wo-row-action" onclick="window.openWoStatusConfirmModal('${escapeHtml(wo.woId)}', '${escapeHtml(wo.status)}', '${wo.status === 'Open' ? 'In Progress' : (wo.status === 'In Progress' ? 'Closed' : 'Open')}', '${escapeHtml(assetId)}')" aria-label="Konfirmasi perubahan status ${escapeHtml(wo.woId)}"><i class="fa-solid fa-pen-to-square"></i></button></td>
                    </tr>`;
                }).join('');
            }

            window.filterWorkOrderWorkspace = function () {
                initWorkOrderView(workOrderSource, true);
            };

            function initWorkOrderView(wos, preserveSource = false) {
                const kanbanBoard = document.getElementById('woKanbanBoard');
                if (!kanbanBoard) return;
                if (!preserveSource) {
                    workOrderSource = Array.isArray(wos) ? [...wos] : [];
                }
                const visibleWos = getFilteredWorkOrders();
                const count = document.getElementById('woWorkspaceCount');
                if (count) count.textContent = `${visibleWos.length.toLocaleString('id-ID')} WO`;
                renderWorkOrderTable(visibleWos);
                WorkOrderDragDrop.bind(kanbanBoard);

                const openWos = visibleWos.filter(w => w.status === 'Open');
                const progressWos = visibleWos.filter(w => w.status === 'In Progress');
                const closedWos = visibleWos.filter(w => w.status === 'Closed');

                // Update global badge counters
                const badgeO = document.getElementById('count-Open');
                const badgeP = document.getElementById('count-InProgress');
                const badgeC = document.getElementById('count-Closed');
                if (badgeO) badgeO.textContent = openWos.length;
                if (badgeP) badgeP.textContent = progressWos.length;
                if (badgeC) badgeC.textContent = closedWos.length;

                function renderParentColumn(statusKey, titleText, badgeClass, woList) {
                    const count = woList.length;

                    if (count === 0) {
                        return `
                        <div class="kanban-column" id="col-${statusKey}" data-status="${statusKey}">
                            <h3><span>${titleText}</span> <span class="badge ${badgeClass}">0</span></h3>
                            <div class="card-container" data-status="${statusKey}" style="padding:20px; color:var(--text-muted); text-align:center; font-size:0.85rem;">Tidak ada data WO.</div>
                        </div>
                    `;
                    }

                    const chunkSize = 50;
                    let bodyHtml = '';

                    if (count <= chunkSize) {
                        const cardsHtml = woList.map(wo => renderKanbanCardHtml(wo, statusKey)).join('');
                        bodyHtml = `
                        <div class="card-container" data-status="${statusKey}">
                            ${cardsHtml}
                        </div>
                    `;
                    } else {
                        let subListsHtml = '';
                        for (let i = 0; i < count; i += chunkSize) {
                            const chunk = woList.slice(i, i + chunkSize);
                            const cardsHtml = chunk.map(wo => renderKanbanCardHtml(wo, statusKey)).join('');

                            subListsHtml += `
                            <div class="card-sublist" data-status="${statusKey}">
                                ${cardsHtml}
                            </div>
                        `;
                        }
                        bodyHtml = `
                        <div class="card-container multi-column-track" data-status="${statusKey}">
                            ${subListsHtml}
                        </div>
                    `;
                    }

                    return `
                    <div class="kanban-column ${count > chunkSize ? 'expanded-column' : ''}" id="col-${statusKey}" data-status="${statusKey}">
                        <h3><span>${titleText}</span> <span class="badge ${badgeClass}">${count}</span></h3>
                        ${bodyHtml}
                    </div>
                `;
                }

                const openCol = renderParentColumn('Open', 'Open / Submitted', 'bg-draft', openWos);
                const progressCol = renderParentColumn('In Progress', 'In Progress (Mekanik)', 'bg-progress', progressWos);
                const closedCol = renderParentColumn('Closed', 'Closed / Ready', 'bg-operating', closedWos);

                kanbanBoard.innerHTML = openCol + progressCol + closedCol;
                window.switchWorkOrderMode(workOrderViewMode);
            }

            window.refreshWorkOrdersFromDatabase = async function () {
                try {
                    const response = await fetch('api/work_orders.php', {
                        headers: { Accept: 'application/json' },
                        cache: 'no-store'
                    });
                    const payload = await response.json();
                    if (!response.ok || payload?.status !== 'success' || !Array.isArray(payload.data)) {
                        throw new Error(payload?.message || 'Data Work Order tidak tersedia.');
                    }
                    const workOrders = payload.data.map(wo => normalizeWorkOrder({
                        ...wo,
                        sourceReportId: String(wo.source_report_id || ''),
                        sourceReportNumber: String(wo.source_report_number || '')
                    }));
                    globalData.work_orders = workOrders;
                    window.globalData = globalData;
                    initWorkOrderView(workOrders);
                    return workOrders;
                } catch (error) {
                    console.error('Work Order database gagal dimuat ulang:', error);
                    return null;
                }
            };

            document.addEventListener('fleetreport:finalized', event => {
                if (event.detail?.schemaId === 'repair-overhaul') window.refreshWorkOrdersFromDatabase();
            });
            document.addEventListener('fleetreport:voided', event => {
                if (event.detail?.schemaId === 'repair-overhaul') window.refreshWorkOrdersFromDatabase();
            });

            let previousModalId = null;
            let modalUnitMapInstance = null;

            window.openEditAssetModal = function (id) {
                const assetObj = (globalData && globalData.assets ? globalData.assets.find(a => a.id === id) : null)
                    || (window.currentAssetData ? window.currentAssetData.find(a => a.id === id) : null);
                if (!assetObj) {
                    alert('Data unit tidak ditemukan.');
                    return;
                }

                document.getElementById('ea-id-label').textContent = assetObj.id;
                document.getElementById('ea-id').value = assetObj.id;
                document.getElementById('ea-cat').value = assetObj.category || 'Other';
                
                const normStatus = (assetObj.subStatus || assetObj.status || 'READY').toUpperCase().replace(/\s+/g, '_');
                const statSelect = document.getElementById('ea-status');
                if (statSelect) {
                    statSelect.value = Array.from(statSelect.options).some(o => o.value === normStatus) ? normStatus : 'READY';
                }

                document.getElementById('ea-model').value = (assetObj.make_model && assetObj.make_model !== '-') ? assetObj.make_model : '';
                document.getElementById('ea-asset-code').value = assetObj.asset_code || assetObj.id || '';
                document.getElementById('ea-warranty').value = ['Aktif', 'Tidak ada warranty', 'Kedaluwarsa'].includes(assetObj.warranty) ? assetObj.warranty : 'Belum diketahui';
                document.getElementById('ea-capacity').value = assetObj.capacity || '';
                document.getElementById('ea-year').value = (assetObj.year_manufacture && parseInt(assetObj.year_manufacture, 10) >= 1970) ? assetObj.year_manufacture : '';
                
                let snVal = (assetObj.serial_number && assetObj.serial_number !== '-') ? assetObj.serial_number : '';
                if (!snVal && window.parseAssetId) {
                    const parsed = window.parseAssetId(assetObj.id);
                    if (parsed && parsed.snPlat && parsed.snPlat !== '-') snVal = parsed.snPlat;
                }
                document.getElementById('ea-sn').value = snVal;
                const plateVal = (assetObj.license_plate && assetObj.license_plate !== '-') ? assetObj.license_plate : '';
                const dumpIdentity = document.getElementById('ea-dump-identity');
                if (dumpIdentity) {
                    dumpIdentity.value = plateVal || snVal;
                    dumpIdentity.dataset.identityKind = plateVal ? 'license_plate' : 'serial_number';
                }
                document.getElementById('ea-hull').value = assetObj.hull_number || assetObj.alias_name || '';
                document.getElementById('ea-dealer').value = assetObj.dealer || '';
                document.getElementById('ea-position').value = assetObj.operational_position || assetObj.raw_location_notes || '';
                document.getElementById('ea-invoice-location').value = assetObj.invoice_location || '';
                document.getElementById('ea-stnk-location').value = assetObj.stnk_location || '';
                document.getElementById('ea-bpkb-location').value = assetObj.bpkb_location || '';
                document.getElementById('ea-vehicle-type').value = assetObj.vehicle_type || '';
                if (document.getElementById('ea-chassis')) document.getElementById('ea-chassis').value = (assetObj.chassis_number && assetObj.chassis_number !== '-') ? assetObj.chassis_number : '';

                const locSelect = document.getElementById('ea-loc');
                if (locSelect) {
                    if (assetObj.current_location_id) {
                        locSelect.dataset.requestedLocationId = String(assetObj.current_location_id);
                        locSelect.value = String(assetObj.current_location_id);
                    } else if (assetObj.project_name || assetObj.location) {
                        locSelect.dataset.requestedProjectName = assetObj.project_name || assetObj.location;
                        const matchedOpt = Array.from(locSelect.options).find(option =>
                            option.text.toLowerCase().includes((assetObj.project_name || assetObj.location || '').toLowerCase())
                        );
                        if (matchedOpt) locSelect.value = matchedOpt.value;
                    }
                }

                const fieldValues = {
                    'ea-current-meter': assetObj.last_hm_km,
                    'ea-last-service-date': String(assetObj.last_service_date || '').slice(0, 10),
                    'ea-last-service-hm': assetObj.last_service_hm_km,
                    'ea-service-interval': assetObj.service_interval_val,
                    'ea-service-unit': assetObj.service_interval_unit || 'HM',
                    'ea-daily-rate': assetObj.daily_usage_coefficient,
                    'ea-stnk': assetObj.legal_stnk_expiry,
                    'ea-kir': assetObj.legal_kir_expiry,
                    'ea-silo': assetObj.legal_silo_expiry
                };
                Object.entries(fieldValues).forEach(([key, value]) => { const field = document.getElementById(key); if (field) field.value = value ?? ''; });
                for (const id of ['ea-photos', 'ea-service-pdf']) {
                    const input = document.getElementById(id);
                    if (input) input.value = '';
                }
                document.getElementById('ea-photo-preview')?.replaceChildren();
                window.updateEditAssetFormLayout();

                const errAlert = document.getElementById('ea-error-alert');
                if (errAlert) {
                    errAlert.style.display = 'none';
                    errAlert.textContent = '';
                }

                openModal('modalEditAsset');
                window.loadEditAssetDocuments(assetObj.id);
            };

            function openAssetModal(id, status, category, location, fromModal = null, targetTab = 'ringkasan') {
                previousModalId = fromModal;
                const assetObj = (globalData && globalData.assets ? globalData.assets.find(a => a.id === id) : null)
                    || (window.currentAssetData ? window.currentAssetData.find(a => a.id === id) : null);
                document.getElementById('modalAssetId').textContent = id;
                document.getElementById('modalStatus').textContent = status;
                document.getElementById('modalKategori').textContent = category;
                document.getElementById('modalLokasi').textContent = location;
                const statusSource = document.getElementById('modalStatusSource');
                if (statusSource) {
                    statusSource.innerHTML = `<i class="fa-solid fa-link"></i> ${escapeHtml(assetObj?.statusSource || 'Master Asset')} &middot; ${escapeHtml(assetObj?.statusReference || 'Data awal')}`;
                }

                const ident = window.formatAssetIdentityDetails ? window.formatAssetIdentityDetails(assetObj || { id, category, location }) : { model: 'Belum tersedia', year: 'Belum tersedia', sn: 'Belum tersedia', plate: 'Belum tersedia' };
                const compl = window.getAssetCompleteness ? window.getAssetCompleteness(assetObj || { id, category, location }) : { isComplete: false, missing: [] };

                // Populate Specs with actual asset properties
                const modelSpec = document.getElementById('modalModelSpec');
                const yearSpec = document.getElementById('modalYearSpec');
                const snSpec = document.getElementById('modalSnSpec');
                const plateSpec = document.getElementById('modalPlateSpec');
                const complSpec = document.getElementById('modalCompletenessSpec');
                const hmSpec = document.getElementById('modalHmSpec');

                if (modelSpec) {
                    modelSpec.innerHTML = ident.model !== 'Belum tersedia' 
                        ? escapeHtml(ident.model) 
                        : `<span style="color:var(--text-muted); font-style:italic;">Belum tersedia</span>`;
                }
                if (yearSpec) {
                    yearSpec.innerHTML = ident.year !== 'Belum tersedia'
                        ? escapeHtml(ident.year)
                        : `<span style="color:var(--text-muted); font-style:italic;">Belum tersedia</span>`;
                }
                if (snSpec) {
                    snSpec.innerHTML = ident.sn !== 'Belum tersedia'
                        ? `<strong style="font-family:var(--font-mono);">${escapeHtml(ident.sn)}</strong>`
                        : `<span style="color:var(--text-muted); font-style:italic;">Belum tersedia</span>`;
                }
                if (plateSpec) {
                    plateSpec.innerHTML = ident.plate !== 'Belum tersedia'
                        ? `<strong style="font-family:var(--font-mono); color:#0369a1;">${escapeHtml(ident.plate)}</strong>`
                        : `<span style="color:var(--text-muted); font-style:italic;">Belum tersedia</span>`;
                }
                if (complSpec) {
                    complSpec.innerHTML = compl.isComplete
                        ? `<span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> Lengkap</span>`
                        : `<span class="badge badge-warning" title="Kurang: ${escapeHtml(compl.missing.join(', '))}"><i class="fa-solid fa-triangle-exclamation"></i> Belum Lengkap (${escapeHtml(compl.missing.join(', '))})</span>`;
                }
                if (hmSpec) {
                    hmSpec.innerText = (assetObj && assetObj.last_hm_km !== undefined ? assetObj.last_hm_km : '0.00') + ' Jam';
                }

                const backBtn = document.getElementById('btnBackAssetModal');
                if (backBtn) {
                    backBtn.style.display = previousModalId ? 'inline-flex' : 'none';
                }

                // Set Tab
                const tabs = Array.from(document.querySelectorAll('#assetModalTabs .asset-tab'));
                const targetEl = tabs.find(t => t.getAttribute('onclick') && t.getAttribute('onclick').includes(`'${targetTab}'`)) || tabs[0];
                if (targetEl) window.switchAssetModalTab(targetTab, targetEl);

                // Pre-render WO & P2H data for unit
                renderModalUnitWo(id);
                renderModalUnitP2h(id);
                updateModalMovementTimeline(id, location);

                openModal('assetModal');
            }

            window.switchAssetModalTab = function (tabName, el) {
                if (el) {
                    document.querySelectorAll('#assetModalTabs .asset-tab').forEach(t => t.classList.remove('active'));
                    el.classList.add('active');
                }

                document.getElementById('assetTabSecRingkasan').style.display = 'none';
                document.getElementById('assetTabSecLokasi').style.display = 'none';
                document.getElementById('assetTabSecWo').style.display = 'none';
                document.getElementById('assetTabSecP2h').style.display = 'none';

                if (tabName === 'lokasi') {
                    document.getElementById('assetTabSecLokasi').style.display = 'block';
                    setTimeout(() => {
                        initModalUnitMap();
                    }, 150);
                } else if (tabName === 'wo') {
                    document.getElementById('assetTabSecWo').style.display = 'block';
                } else if (tabName === 'p2h') {
                    document.getElementById('assetTabSecP2h').style.display = 'block';
                } else {
                    document.getElementById('assetTabSecRingkasan').style.display = 'block';
                }
            };

            function initModalUnitMap() {
                const mapContainer = document.getElementById('modalUnitMap');
                if (!mapContainer || typeof L === 'undefined') return;

                const unitId = document.getElementById('modalAssetId').textContent || 'EXC-201';
                const locName = document.getElementById('modalLokasi').textContent || 'Yard Duri';
                const statusName = document.getElementById('modalStatus').textContent || 'READY';

                const asset = (globalData?.assets || []).find(item => String(item.id) === String(unitId))
                    || { id: unitId, location: locName };
                const position = resolveAssetMapPosition(asset, locName);

                const gpsCoordEl = document.getElementById('modalGpsCoord');
                const btnGmaps = document.getElementById('btnGoogleMaps');

                if (modalUnitMapInstance) {
                    modalUnitMapInstance.remove();
                    modalUnitMapInstance = null;
                }

                if (!position) {
                    if (gpsCoordEl) gpsCoordEl.innerText = 'Koordinat belum tersedia';
                    if (btnGmaps) {
                        btnGmaps.removeAttribute('href');
                        btnGmaps.setAttribute('aria-disabled', 'true');
                        btnGmaps.style.pointerEvents = 'none';
                        btnGmaps.style.opacity = '0.55';
                    }
                    mapContainer.innerHTML = `<div style="height:100%; display:flex; align-items:center; justify-content:center; padding:24px; text-align:center; color:var(--text-muted); background:var(--bg-body);">
                        <div><i class="fa-solid fa-location-crosshairs" style="font-size:2rem; margin-bottom:10px;"></i><br>
                        Koordinat untuk <strong>${escapeHtml(locName)}</strong> belum tersedia.<br><small>Lokasi tidak digabung dengan lokasi lain karena nama datanya berbeda.</small></div>
                    </div>`;
                    return;
                }

                const { lat, lng } = position; if (gpsCoordEl) {
                    gpsCoordEl.innerText = position.isRepresentative
                        ? `${position.source} - koordinat aktual belum tersedia`
                        : `${position.source}: ${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                }
                if (btnGmaps) {
                    btnGmaps.href = `https://www.google.com/maps?q=${lat.toFixed(6)},${lng.toFixed(6)}`;
                    btnGmaps.removeAttribute('aria-disabled');
                    btnGmaps.title = position.isRepresentative
                        ? 'Buka posisi representatif pada Google Maps'
                        : 'Buka posisi pada Google Maps';
                    btnGmaps.style.pointerEvents = '';
                    btnGmaps.style.opacity = '';
                }

                mapContainer.innerHTML = '';

                modalUnitMapInstance = L.map('modalUnitMap').setView([lat, lng], 14);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap'
                }).addTo(modalUnitMapInstance);

                const markerColor = statusName === 'READY' ? '#28a745' : (statusName === 'BREAKDOWN' ? '#dc3545' : '#17a2b8');
                const customIcon = L.divIcon({
                    className: 'custom-unit-marker',
                    html: `<div style="background:${markerColor}; color:#fff; padding:4px 8px; border-radius:12px; font-weight:bold; font-size:11px; border:2px solid #fff; box-shadow:0 2px 6px rgba(0,0,0,0.3); font-family:sans-serif;">
                        <i class="fa-solid fa-location-dot"></i> ${escapeHtml(unitId)}
                       </div>`,
                    iconSize: [80, 30],
                    iconAnchor: [40, 15]
                });

                L.marker([lat, lng], { icon: customIcon })
                    .addTo(modalUnitMapInstance)
                    .bindPopup(`<b>${escapeHtml(unitId)}</b><br>Lokasi: ${escapeHtml(locName)}<br>Status: <b>${escapeHtml(statusName)}</b><br><small>${escapeHtml(position.source)}${position.isRepresentative ? ' - bukan GPS aktual' : ''}</small>`)
                    .openPopup();

                setTimeout(() => {
                    if (modalUnitMapInstance) modalUnitMapInstance.invalidateSize();
                }, 200);
            }

            function renderModalUnitWo(unitId) {
                const tbody = document.getElementById('tbModalUnitWoBody');
                if (!tbody) return;

                let wos = [];
                if (globalData && globalData.work_orders) {
                    wos = globalData.work_orders.filter(w => (w.assetId || w.unitId) === unitId);
                }

                if (wos.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:15px; color:var(--text-muted);">Belum ada riwayat Work Order untuk unit ini.</td></tr>`;
                    return;
                }

                tbody.innerHTML = wos.map(w => `
                <tr>
                    <td><strong>${escapeHtml(w.woId)}</strong></td>
                    <td>${escapeHtml(w.date || '2026-07-26')}</td>
                    <td>${escapeHtml(w.issue || w.description || 'Perbaikan Rutin')}</td>
                    <td style="font-weight:bold;">${w.downtime || '0'} Jam</td>
                    <td><span class="badge ${w.priority === 'High' ? 'bg-breakdown' : 'bg-operating'}">${w.priority}</span></td>
                    <td><span class="p2h-badge ${w.status === 'Closed' ? 'p2h-badge-pass' : 'p2h-badge-fail'}">${w.status}</span></td>
                </tr>
            `).join('');
            }

            function renderModalUnitP2h(unitId) {
                const tbody = document.getElementById('tbModalUnitP2hBody');
                if (!tbody) return;

                let p2hs = [];
                if (globalData && globalData.inspections) {
                    p2hs = globalData.inspections.filter(i => i.unitId === unitId);
                }

                if (p2hs.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:15px; color:var(--text-muted);">Belum ada riwayat P2H untuk unit ini.</td></tr>`;
                    return;
                }

                tbody.innerHTML = p2hs.map(p => `
                <tr>
                    <td><strong>${escapeHtml(p.id)}</strong></td>
                    <td>${escapeHtml(p.date)}</td>
                    <td>${escapeHtml(p.operator)} (${escapeHtml(p.nrp)})</td>
                    <td style="font-weight:bold;">${p.hmStart} Jam</td>
                    <td><span class="p2h-badge ${p.status.includes('PASS') || p.status.includes('LULUS') ? 'p2h-badge-pass' : 'p2h-badge-fail'}">${escapeHtml(p.status)}</span></td>
                    <td style="font-size:0.8rem; max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="${escapeHtml(p.notes)}">${escapeHtml(p.notes)}</td>
                    <td>
                        <button type="button" class="btn" style="padding:2px 6px; font-size:0.85rem; background:var(--primary); color:#fff;" onclick="window.viewP2HDetail('${escapeHtml(p.id)}')">
                            <i class="fa-solid fa-eye"></i> Detail
                        </button>
                    </td>
                </tr>
            `).join('');
            }

            function updateModalMovementTimeline(unitId, currentLoc) {
                const timeline = document.getElementById('movementTimeline');
                if (!timeline) return;
                const movements = Array.isArray(globalData?.asset_movements)
                    ? globalData.asset_movements.filter(item => String(item.assetId || '') === String(unitId))
                    : [];
                const shipments = Array.isArray(globalData?.asset_shipments)
                    ? globalData.asset_shipments.filter(item => String(item.assetId || '') === String(unitId))
                    : [];
                const latestMovementId = movements[0]?.movementId;
                const events = [
                    ...movements.map(item => ({ type: 'movement', date: item.movementDate, item })),
                    ...shipments.map(item => ({ type: 'shipment', date: item.shipmentDate, item }))
                ].sort((a, b) => String(b.date || '').localeCompare(String(a.date || '')));

                const activeLocation = movements.length ? '' : `
                    <div class="movement-timeline-item latest">
                        <div class="movement-timeline-title">${escapeHtml(currentLoc || 'Lokasi belum ditentukan')} (Lokasi Aktif)</div>
                        <div class="movement-timeline-meta">Lokasi aktif tidak berubah saat unit hanya diserahkan kepada ekspedisi.</div>
                    </div>`;
                if (!events.length) {
                    timeline.innerHTML = activeLocation.replace(
                        'Lokasi aktif tidak berubah saat unit hanya diserahkan kepada ekspedisi.',
                        'Belum ada riwayat BAST, BAPE, atau perpindahan pada database.'
                    );
                    return;
                }

                timeline.innerHTML = activeLocation + events.map(event => {
                    const item = event.item;
                    const date = event.date
                        ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(new Date(event.date))
                        : '-';
                    if (event.type === 'shipment') {
                        const status = item.shipmentStatus === 'DISCREPANCY' ? 'Selisih' : 'Dalam perjalanan';
                        const condition = ({ GOOD: 'Baik', DAMAGED: 'Rusak', MISSING: 'Kurang' })[item.unitCondition] || item.unitCondition || '-';
                        const details = [
                            item.bapeNumber || 'Tanpa nomor BAPE', date,
                            `Dari ${item.originLocation || 'lokasi belum tercatat'}`,
                            `Status ${status}`, `Kondisi ${condition}`,
                            item.transportPlate ? `Kendaraan ${item.transportPlate}` : '',
                            item.senderName ? `Oleh ${item.senderName}` : ''
                        ].filter(Boolean).join(' · ');
                        return `
                            <div class="movement-timeline-item">
                                <div class="movement-timeline-title">Diserahkan ke ekspedisi: ${escapeHtml(item.carrierName || '-')}</div>
                                <div class="movement-timeline-meta">${escapeHtml(details)}</div>
                            </div>`;
                    }
                    const isActive = String(item.movementId) === String(latestMovementId);
                    const route = `${item.fromLocation || 'Lokasi awal tidak tercatat'} → ${item.toLocation || '-'}`;
                    const details = [item.bastNumber || 'Tanpa nomor BAST', date, route, item.requestedBy ? `Oleh ${item.requestedBy}` : '']
                        .filter(Boolean).join(' · ');
                    return `
                        <div class="movement-timeline-item ${isActive ? 'latest' : ''}">
                            <div class="movement-timeline-title">${escapeHtml(item.toLocation || currentLoc || '-')} ${isActive ? '(Lokasi Aktif)' : ''}</div>
                            <div class="movement-timeline-meta">${escapeHtml(details)}</div>
                        </div>`;
                }).join('');
            }

            function backToStatusDetailModal() {
                closeModal('assetModal');
                if (previousModalId) {
                    openModal(previousModalId);
                }
            }

            function openModal(id) { window.openModal(id); }
            function closeModal(id) { window.closeModal(id); }

            window.previewAssetPhotos = function(files) {
                const target = document.getElementById('na-photo-preview');
                if (!target) return;
                target.replaceChildren();
                for (const file of Array.from(files || [])) {
                    const image = document.createElement('img');
                    image.alt = file.name;
                    image.title = file.name;
                    image.style.cssText = 'width:90px;height:70px;object-fit:cover;border:1px solid #cbd5e1;border-radius:4px;';
                    const url = URL.createObjectURL(file);
                    image.src = url;
                    image.onload = () => URL.revokeObjectURL(url);
                    target.appendChild(image);
                }
            };

            window.previewEditAssetPhotos = function(files) {
                const target = document.getElementById('ea-photo-preview');
                if (!target) return;
                target.replaceChildren();
                for (const file of Array.from(files || [])) {
                    const image = document.createElement('img');
                    image.alt = `Foto baru: ${file.name}`;
                    image.title = file.name;
                    const url = URL.createObjectURL(file);
                    image.src = url;
                    image.onload = () => URL.revokeObjectURL(url);
                    target.appendChild(image);
                }
            };

            window.loadEditAssetDocuments = async function(assetId) {
                const status = document.getElementById('ea-document-status');
                if (!status || !assetId) return;
                status.textContent = 'Memuat dokumentasi unit...';
                try {
                    const response = await fetch(`api/assets.php?id=${encodeURIComponent(assetId)}`, { cache: 'no-store' });
                    const result = await response.json();
                    if (!response.ok || result.status !== 'success') throw new Error(result.message || 'Dokumentasi tidak dapat dimuat.');
                    const asset = result.data || {};
                    status.replaceChildren();
                    const heading = document.createElement('div');
                    heading.className = 'asset-registration-document-heading';
                    heading.textContent = 'Dokumentasi saat ini';
                    status.appendChild(heading);
                    const items = document.createElement('div');
                    items.className = 'asset-registration-current-files';
                    for (const photo of Array.isArray(asset.gallery_photos) ? asset.gallery_photos : []) {
                        const card = document.createElement('div');
                        card.className = 'asset-registration-current-photo';
                        const image = document.createElement('img');
                        image.src = `api/asset_files.php?kind=photo&photo_id=${encodeURIComponent(photo.photo_id)}`;
                        image.alt = photo.photo_title || 'Foto unit';
                        const remove = document.createElement('button');
                        remove.type = 'button';
                        remove.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
                        remove.setAttribute('aria-label', `Hapus ${photo.photo_title || 'foto unit'}`);
                        remove.title = 'Hapus foto';
                        remove.onclick = () => window.deleteEditAssetPhoto(assetId, photo.photo_id);
                        card.append(image, remove);
                        items.appendChild(card);
                    }
                    if (asset.service_manual_pdf_url) {
                        const pdf = document.createElement('a');
                        pdf.className = 'asset-registration-current-pdf';
                        pdf.href = `api/asset_files.php?kind=pdf&asset_id=${encodeURIComponent(assetId)}`;
                        pdf.target = '_blank';
                        pdf.rel = 'noopener';
                        pdf.innerHTML = '<i class="fa-regular fa-file-pdf"></i><span>PDF JO servis saat ini</span><i class="fa-solid fa-arrow-up-right-from-square"></i>';
                        items.appendChild(pdf);
                    }
                    if (!items.children.length) {
                        const empty = document.createElement('span');
                        empty.className = 'asset-registration-document-empty';
                        empty.textContent = 'Belum ada foto atau PDF tersimpan.';
                        items.appendChild(empty);
                    }
                    status.appendChild(items);
                } catch (error) {
                    status.textContent = error?.message || 'Dokumentasi tidak dapat dimuat.';
                }
            };

            window.deleteEditAssetPhoto = async function(assetId, photoId) {
                if (!confirm('Hapus foto ini dari dokumentasi unit?')) return;
                try {
                    const response = await fetch(`api/asset_files.php?photo_id=${encodeURIComponent(photoId)}`, { method: 'DELETE' });
                    const result = await response.json();
                    if (!response.ok || result.status !== 'success') throw new Error(result.message || 'Foto gagal dihapus.');
                    await window.loadEditAssetDocuments(assetId);
                } catch (error) {
                    const alertBox = document.getElementById('ea-error-alert');
                    if (alertBox) {
                        alertBox.textContent = error?.message || 'Foto gagal dihapus.';
                        alertBox.style.display = 'block';
                    }
                }
            };

            window.updateNewAssetAge = function() {
                const yearInput = document.getElementById('na-year');
                const ageInput = document.getElementById('na-age');
                if (!yearInput || !ageInput) return;
                const year = Number.parseInt(yearInput.value, 10);
                const currentYear = new Date().getFullYear();
                ageInput.value = Number.isInteger(year) && year >= 1970 && year <= currentYear
                    ? String(currentYear - year)
                    : '';
            };

            window.updateNewAssetFormLayout = function() {
                const category = document.getElementById('na-cat')?.value || '';
                const isDumpTruck = category === 'Dump Truck';
                document.querySelectorAll('#modalNewAsset [data-form-variant]').forEach(field => {
                    const visible = field.dataset.formVariant === (isDumpTruck ? 'dump' : 'general');
                    field.hidden = !visible;
                    field.querySelectorAll('input, select, textarea').forEach(control => {
                        control.disabled = !visible;
                    });
                });
                const mode = document.getElementById('na-form-mode');
                const hullLabel = document.getElementById('na-hull-label');
                const positionLabel = document.getElementById('na-position-label');
                if (mode) mode.textContent = isDumpTruck ? 'Khusus Dump Truck' : 'Formulir umum';
                if (hullLabel) hullLabel.textContent = isDumpTruck ? 'Kode aktual' : 'Nomor lambung aktual';
                if (positionLabel) positionLabel.textContent = isDumpTruck ? 'Posisi' : 'Lokasi';
                window.updateNewAssetAge();
            };

            window.updateEditAssetAge = function() {
                const yearInput = document.getElementById('ea-year');
                const ageInput = document.getElementById('ea-age');
                if (!yearInput || !ageInput) return;
                const year = Number.parseInt(yearInput.value, 10);
                const currentYear = new Date().getFullYear();
                ageInput.value = Number.isInteger(year) && year >= 1970 && year <= currentYear
                    ? String(currentYear - year)
                    : '';
            };

            window.updateEditAssetFormLayout = function() {
                const category = document.getElementById('ea-cat')?.value || '';
                const isDumpTruck = category === 'Dump Truck';
                document.querySelectorAll('#modalEditAsset [data-edit-form-variant]').forEach(field => {
                    const visible = field.dataset.editFormVariant === (isDumpTruck ? 'dump' : 'general');
                    field.hidden = !visible;
                    field.querySelectorAll('input, select, textarea').forEach(control => {
                        control.disabled = !visible;
                    });
                });
                const mode = document.getElementById('ea-form-mode');
                const hullLabel = document.getElementById('ea-hull-label');
                const positionLabel = document.getElementById('ea-position-label');
                if (mode) mode.textContent = isDumpTruck ? 'Khusus Dump Truck' : 'Formulir umum';
                if (hullLabel) hullLabel.textContent = isDumpTruck ? 'Kode aktual' : 'Nomor lambung aktual';
                if (positionLabel) positionLabel.textContent = isDumpTruck ? 'Posisi' : 'Lokasi';
                window.updateEditAssetAge();
            };

            window.openEditAssetHullRename = function() {
                const assetId = document.getElementById('ea-id')?.value || '';
                const hull = document.getElementById('ea-hull')?.value || '';
                if (!assetId) return;
                closeModal('modalEditAsset');
                window.openRenameHullModal(assetId, hull);
            };

            window.uploadAssetAttachment = async function(assetId, kind, file) {
                const form = new FormData();
                form.append('asset_id', assetId);
                form.append('kind', kind);
                form.append('file', file);
                if (kind === 'photo') form.append('title', file.name.replace(/\.[^.]+$/, ''));
                const response = await fetch('api/asset_files.php', { method: 'POST', body: form });
                const result = await response.json();
                if (!response.ok || result.status !== 'success') throw new Error(result.message || 'Unggahan gagal');
                return result;
            };

            async function submitNewAsset() {
                const cat = document.getElementById('na-cat').value.trim();
                const model = document.getElementById('na-model').value.trim();
                const year = document.getElementById('na-year').value.trim();
                const isDumpTruck = cat === 'Dump Truck';
                const sn = isDumpTruck
                    ? document.getElementById('na-dump-identity').value.trim()
                    : document.getElementById('na-sn').value.trim();
                const dealer = document.getElementById('na-dealer')?.value.trim() || '';
                const chassisNumber = document.getElementById('na-chassis') ? document.getElementById('na-chassis').value.trim() : '';
                const vehicleType = document.getElementById('na-vehicle-type')?.value.trim() || '';
                const position = document.getElementById('na-position')?.value.trim() || '';
                const invoiceLocation = document.getElementById('na-invoice-location')?.value.trim() || '';
                const stnkLocation = document.getElementById('na-stnk-location')?.value.trim() || '';
                const bpkbLocation = document.getElementById('na-bpkb-location')?.value.trim() || '';
                const assetAge = document.getElementById('na-age')?.value.trim() || '';
                const assetCode = document.getElementById('na-asset-code')?.value.trim() || '';
                const warranty = document.getElementById('na-warranty')?.value || 'Belum diketahui';
                const capacity = document.getElementById('na-capacity')?.value.trim() || '';
                const locSelect = document.getElementById('na-loc');
                const locId = locSelect ? locSelect.value : '';
                const locName = locId && locSelect && locSelect.selectedIndex >= 0 ? locSelect.options[locSelect.selectedIndex].text : '';
                const status = document.getElementById('na-status').value.trim();
                const errAlert = document.getElementById('na-error-alert');
                const submitBtn = document.getElementById('btnSubmitNewAsset');

                if (errAlert) { errAlert.style.display = 'none'; errAlert.textContent = ''; }

                if (!model) {
                    if (errAlert) {
                        errAlert.textContent = 'Nama aset wajib diisi.';
                        errAlert.style.display = 'block';
                    }
                    document.getElementById('na-model')?.focus();
                    return;
                }

                const parsedYear = parseInt(year, 10);
                const currentYear = new Date().getFullYear();
                if (!year || isNaN(parsedYear) || parsedYear < 1970 || parsedYear > currentYear) {
                    if (errAlert) {
                        errAlert.textContent = `Tahun harus berupa angka antara 1970 dan ${currentYear}.`;
                        errAlert.style.display = 'block';
                    }
                    document.getElementById('na-year')?.focus();
                    return;
                }

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';
                }

                try {
                    const onboardingInput = {
                        current_operator_name: document.getElementById('na-operator')?.value?.trim() || null,
                        operator_start_date: document.getElementById('na-operator-start')?.value || null,
                        operator_shift: document.getElementById('na-operator-shift')?.value?.trim() || null,
                        last_service_date: document.getElementById('na-last-service-date')?.value || null,
                        last_service_hm_km: document.getElementById('na-last-service-hm')?.value || null,
                        service_interval_val: document.getElementById('na-service-interval')?.value || null,
                        service_interval_unit: document.getElementById('na-service-unit')?.value || 'HM',
                        daily_usage_coefficient: document.getElementById('na-daily-rate')?.value || null,
                        legal_stnk_expiry: document.getElementById('na-stnk')?.value || null,
                        legal_kir_expiry: document.getElementById('na-kir')?.value || null,
                        legal_silo_expiry: document.getElementById('na-silo')?.value || null
                    };
                    const photoFiles = Array.from(document.getElementById('na-photos')?.files || []);
                    const pdfFile = document.getElementById('na-service-pdf')?.files?.[0] || null;
                    const historyEl = document.getElementById('na-service-history');
                    const historicalServices = historyEl && historyEl.value
                        ? historyEl.value.split(/\r?\n/).map(line => line.trim()).filter(Boolean).map(line => {
                            const parts = line.split(',').map(part => part.trim());
                            if (parts.length !== 3 || !parts[0] || !parts[1] || !parts[2]) throw new Error('Riwayat servis harus ditulis tanggal,meter,jenis per baris.');
                            return { date: parts[0], meter: parts[1], type: parts[2] };
                        })
                        : [];
                    const payload = {
                        asset_code: assetCode || null,
                        hull_number: document.getElementById('na-hull')?.value.trim() || null,
                        category: cat,
                        type: 'Heavy Equipment',
                        status: status || 'READY',
                        warranty: warranty,
                        capacity: capacity || null,
                        make_model: model || null,
                        year_manufacture: parsedYear,
                        serial_number: sn || null,
                        license_plate: null,
                        dealer: dealer || null,
                        project_name: locName || null,
                        operational_position: position || null,
                        invoice_location: invoiceLocation || null,
                        stnk_location: stnkLocation || null,
                        bpkb_location: bpkbLocation || null,
                        vehicle_type: vehicleType || null,
                        asset_age: assetAge ? parseInt(assetAge, 10) : null,
                        chassis_number: chassisNumber || null,
                        current_location_id: locId ? parseInt(locId, 10) : null,
                        last_hm_km: document.getElementById('na-current-meter')?.value || 0,
                        ...onboardingInput,
                        historical_services: historicalServices
                    };

                    const response = await fetch('api/assets.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();

                    if (response.ok && result.status === 'success') {
                        const savedAssetId = result.data.asset_id;
                        const uploadErrors = [];
                        for (const file of photoFiles) {
                            try { await window.uploadAssetAttachment(savedAssetId, 'photo', file); }
                            catch (error) { uploadErrors.push(`${file.name}: ${error.message}`); }
                        }
                        if (pdfFile) {
                            try { await window.uploadAssetAttachment(savedAssetId, 'pdf', pdfFile); }
                            catch (error) { uploadErrors.push(`${pdfFile.name}: ${error.message}`); }
                        }
                        const newAsset = {
                            id: savedAssetId,
                            asset_id: savedAssetId,
                            asset_code: result.data.asset_code || savedAssetId,
                            hull_number: document.getElementById('na-hull')?.value.trim() || null,
                            ...result.data.onboarding,
                            type: 'Heavy Equipment',
                            category: cat,
                            status: status || 'READY',
                            warranty: warranty,
                            capacity: capacity || null,
                            make_model: model || null,
                            year_manufacture: parsedYear,
                            serial_number: sn || null,
                            license_plate: null,
                            dealer: dealer || null,
                            project_name: locName || null,
                            operational_position: position || null,
                            invoice_location: invoiceLocation || null,
                            stnk_location: stnkLocation || null,
                            bpkb_location: bpkbLocation || null,
                            vehicle_type: vehicleType || null,
                            asset_age: assetAge ? parseInt(assetAge, 10) : null,
                            chassis_number: chassisNumber || null,
                            current_location_id: locId ? parseInt(locId, 10) : null,
                            location: locName,
                            location_name: locName,
                            last_hm_km: Number(document.getElementById('na-current-meter')?.value || 0),
                            lastUpdate: 'Baru saja',
                            statusSource: 'Master Asset',
                            statusReference: 'Registrasi unit baru',
                            statusEvents: []
                        };

                        if (!globalData.assets) globalData.assets = [];
                        globalData.assets.unshift(newAsset);
                        if (!window.currentAssetData) window.currentAssetData = [];
                        window.currentAssetData.unshift(newAsset);

                        // Reset input fields
                        for (const field of ['na-model','na-asset-code','na-capacity','na-dealer','na-year','na-age','na-sn','na-dump-identity','na-hull','na-position','na-invoice-location','na-stnk-location','na-bpkb-location','na-chassis','na-vehicle-type','na-operator','na-operator-start','na-operator-shift','na-current-meter','na-last-service-date','na-last-service-hm','na-service-interval','na-daily-rate','na-stnk','na-kir','na-silo','na-photos','na-service-pdf','na-service-history']) {
                            const el = document.getElementById(field);
                            if (el) el.value = '';
                        }
                        document.getElementById('na-photo-preview')?.replaceChildren();

                        closeModal('modalNewAsset');
                        initAssetView(globalData.assets);
                        if (typeof rebuildFleetSummary === 'function') rebuildFleetSummary();
                        if (typeof window.syncFleetState === 'function') window.syncFleetState();
                        if (window.PreventiveMaintenance?.refresh) window.PreventiveMaintenance.refresh();
                        alert(uploadErrors.length ? `Unit '${savedAssetId}' tersimpan, tetapi unggahan berikut gagal dan perlu dicoba lagi: ${uploadErrors.join('; ')}` : `Unit aset '${savedAssetId}' berhasil didaftarkan ke database.`);
                    } else {
                        const msg = result.message || 'Gagal menyimpan aset ke database.';
                        if (errAlert) {
                            errAlert.textContent = msg;
                            errAlert.style.display = 'block';
                        } else {
                            alert(msg);
                        }
                    }
                } catch (error) {
                    console.error('Error saving asset:', error);
                    if (errAlert) {
                        errAlert.textContent = error?.message || 'Terjadi kesalahan koneksi atau server saat menyimpan aset.';
                        errAlert.style.display = 'block';
                    }
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Simpan unit aset';
                    }
                }
            }

            async function submitEditAsset() {
                const id = document.getElementById('ea-id').value.trim();
                const cat = document.getElementById('ea-cat').value.trim();
                const status = document.getElementById('ea-status').value.trim();
                const model = document.getElementById('ea-model').value.trim();
                const year = document.getElementById('ea-year').value.trim();
                const isDumpTruck = cat === 'Dump Truck';
                const generalSerial = document.getElementById('ea-sn')?.value.trim() || '';
                const dumpIdentityInput = document.getElementById('ea-dump-identity');
                const dumpIdentity = dumpIdentityInput?.value.trim() || '';
                const dumpIdentityKind = dumpIdentityInput?.dataset.identityKind === 'license_plate' ? 'license_plate' : 'serial_number';
                const chassisNumber = document.getElementById('ea-chassis') ? document.getElementById('ea-chassis').value.trim() : '';
                const dealer = document.getElementById('ea-dealer')?.value.trim() || '';
                const position = document.getElementById('ea-position')?.value.trim() || '';
                const invoiceLocation = document.getElementById('ea-invoice-location')?.value.trim() || '';
                const stnkLocation = document.getElementById('ea-stnk-location')?.value.trim() || '';
                const bpkbLocation = document.getElementById('ea-bpkb-location')?.value.trim() || '';
                const vehicleType = document.getElementById('ea-vehicle-type')?.value.trim() || '';
                const assetAge = document.getElementById('ea-age')?.value.trim() || '';
                const warranty = document.getElementById('ea-warranty')?.value || 'Belum diketahui';
                const capacity = document.getElementById('ea-capacity')?.value.trim() || '';
                const locSelect = document.getElementById('ea-loc');
                const locId = locSelect ? locSelect.value : '';
                const locName = locId && locSelect && locSelect.selectedIndex >= 0 ? locSelect.options[locSelect.selectedIndex].text : '';
                const errAlert = document.getElementById('ea-error-alert');
                const submitBtn = document.getElementById('btnSubmitEditAsset');
                const photoFiles = Array.from(document.getElementById('ea-photos')?.files || []);
                const pdfFile = document.getElementById('ea-service-pdf')?.files?.[0] || null;

                if (errAlert) { errAlert.style.display = 'none'; errAlert.textContent = ''; }

                if (!id) {
                    if (errAlert) {
                        errAlert.textContent = 'ID unit tidak valid.';
                        errAlert.style.display = 'block';
                    }
                    return;
                }

                if (!model) {
                    if (errAlert) {
                        errAlert.textContent = 'Nama aset wajib diisi.';
                        errAlert.style.display = 'block';
                    }
                    document.getElementById('ea-model')?.focus();
                    return;
                }

                const parsedYear = parseInt(year, 10);
                const currentYear = new Date().getFullYear();
                if (!year || isNaN(parsedYear) || parsedYear < 1970 || parsedYear > currentYear) {
                    if (errAlert) {
                        errAlert.textContent = `Tahun harus berupa angka antara 1970 dan ${currentYear}.`;
                        errAlert.style.display = 'block';
                    }
                    document.getElementById('ea-year')?.focus();
                    return;
                }

                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan Perubahan...';
                }

                try {
                    const payload = {
                        asset_id: id,
                        category: cat,
                        status: status,
                        warranty: warranty,
                        capacity: capacity || null,
                        make_model: model,
                        year_manufacture: parsedYear,
                        project_name: locName || null,
                        operational_position: position || null,
                        asset_age: assetAge ? parseInt(assetAge, 10) : null,
                        current_location_id: locId ? parseInt(locId, 10) : null,
                        last_hm_km: document.getElementById('ea-current-meter')?.value || 0,
                        last_service_date: document.getElementById('ea-last-service-date')?.value || null,
                        last_service_hm_km: document.getElementById('ea-last-service-hm')?.value || null,
                        service_interval_val: document.getElementById('ea-service-interval')?.value || null,
                        service_interval_unit: document.getElementById('ea-service-unit')?.value || null,
                        daily_usage_coefficient: document.getElementById('ea-daily-rate')?.value || null,
                        legal_stnk_expiry: document.getElementById('ea-stnk').value || null,
                        legal_kir_expiry: document.getElementById('ea-kir').value || null,
                        legal_silo_expiry: document.getElementById('ea-silo').value || null
                    };
                    if (isDumpTruck) {
                        payload[dumpIdentityKind] = dumpIdentity || null;
                        payload.stnk_location = stnkLocation || null;
                        payload.bpkb_location = bpkbLocation || null;
                        payload.vehicle_type = vehicleType || null;
                        payload.chassis_number = chassisNumber || null;
                    } else {
                        payload.serial_number = generalSerial || null;
                        payload.dealer = dealer || null;
                        payload.invoice_location = invoiceLocation || null;
                    }

                    const response = await fetch('api/assets.php', {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    const result = await response.json();

                    if (response.ok && result.status === 'success') {
                        const uploadErrors = [];
                        for (const file of photoFiles) {
                            try { await window.uploadAssetAttachment(id, 'photo', file); }
                            catch (error) { uploadErrors.push(`${file.name}: ${error.message}`); }
                        }
                        if (pdfFile) {
                            try { await window.uploadAssetAttachment(id, 'pdf', pdfFile); }
                            catch (error) { uploadErrors.push(`${pdfFile.name}: ${error.message}`); }
                        }
                        const updateTarget = (item) => {
                            if (!item) return;
                            item.category = cat;
                            item.status = status;
                            item.warranty = warranty;
                            item.capacity = capacity || null;
                            item.syncExpectedStatus = status;
                            item.make_model = model || null;
                            item.year_manufacture = parsedYear;
                            if (isDumpTruck) item[dumpIdentityKind] = dumpIdentity || null;
                            else item.serial_number = generalSerial || null;
                            item.project_name = payload.project_name;
                            item.operational_position = payload.operational_position;
                            item.asset_age = payload.asset_age;
                            if (isDumpTruck) {
                                item.stnk_location = payload.stnk_location;
                                item.bpkb_location = payload.bpkb_location;
                                item.vehicle_type = payload.vehicle_type;
                                item.chassis_number = payload.chassis_number;
                            } else {
                                item.dealer = payload.dealer;
                                item.invoice_location = payload.invoice_location;
                            }
                            item.current_location_id = payload.current_location_id;
                            item.last_hm_km = Number(payload.last_hm_km || 0);
                            item.last_service_date = payload.last_service_date;
                            item.last_service_hm_km = payload.last_service_hm_km == null ? null : Number(payload.last_service_hm_km);
                            item.service_interval_val = payload.service_interval_val == null ? null : Number(payload.service_interval_val);
                            item.service_interval_unit = payload.service_interval_unit;
                            item.daily_usage_coefficient = payload.daily_usage_coefficient == null ? null : Number(payload.daily_usage_coefficient);
                            item.location = locName;
                            item.location_name = locName;
                            for (const field of ['legal_stnk_expiry','legal_kir_expiry','legal_silo_expiry']) item[field] = payload[field];
                            item.lastUpdate = 'Baru saja';
                        };

                        if (globalData && globalData.assets) {
                            const a = globalData.assets.find(x => x.id === id || x.asset_id === id);
                            updateTarget(a);
                        }
                        if (window.currentAssetData) {
                            const a = window.currentAssetData.find(x => x.id === id || x.asset_id === id);
                            updateTarget(a);
                        }

                        closeModal('modalEditAsset');
                        initAssetView(globalData.assets);
                        if (typeof rebuildFleetSummary === 'function') rebuildFleetSummary();
                        if (typeof window.syncFleetState === 'function') window.syncFleetState();
                        if (window.PreventiveMaintenance?.refresh) window.PreventiveMaintenance.refresh();
                        alert(uploadErrors.length
                            ? `Data unit '${id}' tersimpan, tetapi unggahan berikut gagal: ${uploadErrors.join('; ')}`
                            : `Data identitas dan dokumentasi unit '${id}' berhasil diperbarui.`);
                    } else {
                        const msg = result.message || 'Gagal memperbarui data unit.';
                        if (errAlert) {
                            errAlert.textContent = msg;
                            errAlert.style.display = 'block';
                        } else {
                            alert(msg);
                        }
                    }
                } catch (error) {
                    console.error('Error updating asset:', error);
                    if (errAlert) {
                        errAlert.textContent = error?.message || 'Terjadi kesalahan koneksi atau server saat memperbarui aset.';
                        errAlert.style.display = 'block';
                    }
                } finally {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Simpan perubahan';
                    }
                }
            }

            function triggerUpdateStatus() {
                const id = document.getElementById('modalAssetId').textContent;
                window.openIntegratedStatusUpdate(id);
            }

            async function submitUpdateStatus() {
                const id = document.getElementById('us-id').value;
                const status = document.getElementById('us-status').value;
                const loc = document.getElementById('us-loc').value;
                const notes = document.getElementById('us-notes').value.trim();

                const asset = globalData.assets.find(a => a.id === id);
                if (asset) {
                    const activeWo = globalData.work_orders.find(wo => wo.assetId === asset.id && wo.status !== 'Closed');
                    if ((status === 'READY' || status === 'OPERATING') && activeWo) {
                        alert(`Unit belum dapat diubah ke ${status} karena ${activeWo.woId} masih aktif. Tutup Work Order terlebih dahulu.`);
                        return;
                    }

                    try {
                        let payload = {
                            mode: 'targeted',
                            assets: [{
                                id: id,
                                status: status,
                                expected_status: asset.status,
                                location: loc || null,
                                last_hm_km: asset.last_hm_km || 0
                            }],
                            work_orders: []
                        };

                        if (status === 'BREAKDOWN') {
                            if (!activeWo) {
                                const woId = 'WO-26-' + String(Date.now()).slice(-5);
                                payload.work_orders.push({
                                    woId: woId,
                                    assetId: id,
                                    status: 'Open',
                                    priority: 'High',
                                    issue: notes || 'Status unit diperbarui menjadi BREAKDOWN dari Master Asset',
                                    assignedTo: 'Pending',
                                    downtime: '0 jam'
                                });
                            }
                        }

                        const response = await fetch('api/sync.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.APP_CSRF_TOKEN || '' },
                            body: JSON.stringify(payload)
                        });

                        const result = await response.json();

                        if (result.status === 'success') {
                            if (loc) asset.location = loc;
                            setIntegratedAssetStatus(id, status, 'Master Asset', 'Update status manual', notes);
                            asset.syncExpectedStatus = status;

                            if (status === 'BREAKDOWN') {
                                if (!activeWo) {
                                    const woId = payload.work_orders[0].woId;
                                    globalData.work_orders.unshift({
                                        woId,
                                        assetId: id,
                                        location: asset.location,
                                        issue: payload.work_orders[0].issue,
                                        downtime: '0 jam',
                                        status: 'Open',
                                        priority: 'High',
                                        assignedTo: 'Pending',
                                        source: 'Master Asset'
                                    });
                                    asset.statusReference = woId;
                                    alert(`Status diupdate ke BREAKDOWN dan tersimpan di database. Tiket WO darurat dibuat otomatis: ${woId}`);
                                } else {
                                    asset.statusReference = activeWo.woId;
                                    alert(`Status diupdate ke BREAKDOWN di database dan ditautkan ke WO aktif ${activeWo.woId}.`);
                                }
                            } else {
                                alert('Status berhasil diupdate ke database');
                            }

                            closeModal('modalUpdateStatus');
                            window.syncFleetState();
                        } else {
                            alert('Gagal mengupdate status ke database: ' + result.message);
                        }
                    } catch (error) {
                        console.error('Error updating status:', error);
                        alert('Terjadi kesalahan jaringan atau server saat mengupdate status');
                    }
                }
            }

            function openNewWOModal(assetId = '') {
                window.NewWorkOrderForm.open(assetId);
            }

            async function submitNewWO() {
                return window.NewWorkOrderForm.submit();
            }

            function refreshAllViews() {
                initDashboard(globalData);
                initMonitoringView(globalData.assets);
                initAssetView(globalData.assets);
                initWorkOrderView(globalData.work_orders);
                initNewViews();
            }

            function toggleSidebar() {
                document.body.classList.toggle('sidebar-collapsed');
                const isCollapsed = document.body.classList.contains('sidebar-collapsed');
                try {
                    localStorage.setItem('fleetmonitor_sidebar_collapsed', isCollapsed ? '1' : '0');
                } catch (e) { }
                if (window.dashboardMapInstance) {
                    setTimeout(() => { window.dashboardMapInstance.invalidateSize(); }, 250);
                }
            }
            window.toggleSidebar = toggleSidebar;

            if (localStorage.getItem('fleetmonitor_sidebar_collapsed') === '1') {
                document.body.classList.add('sidebar-collapsed');
            }

            function clearActiveMenus() {
                document.querySelectorAll('.sidebar-menu a').forEach(el => {
                    el.classList.remove('active');
                    el.removeAttribute('aria-current');
                });
            }

            function showView(viewName, title = '', menuId = '') {
                if (window.FleetRBAC) {
                    let checkMenu = viewName;
                    if (checkMenu === 'workorder') checkMenu = 'wo';
                    if (checkMenu !== 'dashboard' && checkMenu !== 'uc' && checkMenu !== 'archive' && checkMenu !== 'unit-properties' && checkMenu !== 'site-properties' && !window.FleetRBAC.hasAccess(checkMenu, 'R')) {
                        console.warn(`RBAC: Akses ditolak untuk menu ${checkMenu}`);
                        alert('Akses Ditolak: Anda tidak memiliki hak akses untuk menu ini.');
                        if (viewName !== 'dashboard') {
                            showView('dashboard');
                        }
                        return;
                    }
                }

                clearActiveMenus();

                const resolvedMenuId = menuId || `menu-${viewName}`;
                const activeMenu = document.getElementById(resolvedMenuId);
                if (activeMenu) {
                    activeMenu.classList.add('active');
                    activeMenu.setAttribute('aria-current', 'page');
                }

                if (viewName === 'uc') {
                    document.getElementById('ucTitle').innerText = title;
                }

                document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
                const targetView = document.getElementById('view-' + viewName);
                if (!targetView) return;
                targetView.classList.add('active');
                if (viewName === 'approval') window.loadSpbApprovalInbox?.();

                const content = document.querySelector('.content');
                if (content) content.scrollTop = 0;

                if (viewName === 'dashboard' && globalData) {
                    if (!mapInitialized) {
                        initMap(window.getProjectScopedAssets?.() || globalData.assets);
                        mapInitialized = true;
                    } else {
                        setTimeout(() => dashboardMapInstance?.invalidateSize(), 100);
                    }
                }
                if (viewName === 'condition' && window.ConditionMonitoring) {
                    window.ConditionMonitoring.refresh();
                }
                if (viewName === 'logistics' && window.SpareLogistics) {
                    window.SpareLogistics.refresh();
                }
                if (viewName === 'biaya' && typeof window.loadCostDashboard === 'function') {
                    window.loadCostDashboard();
                }
                if (viewName === 'monitoring') {
                    if (typeof initMonitoringView === 'function') {
                        initMonitoringView();
                    } else if (typeof renderMonitoringTable === 'function') {
                        renderMonitoringTable();
                    }
                    if (typeof updateMonitoringActiveCard === 'function') {
                        const currentFilter = document.getElementById('monitoringStatusFilter')?.value || 'ALL';
                        updateMonitoringActiveCard(currentFilter);
                    }
                }
                if (viewName === 'reports') {
                    if (!/^#reports(?:\/|$)/i.test(window.location.hash)) {
                        window.history.replaceState(null, '', '#reports');
                        window.FleetReportForms?.selectPanel('templates');
                    }
                } else if (/^#reports(?:\/|$)/i.test(window.location.hash)) {
                    window.history.replaceState(null, '', `${window.location.pathname}${window.location.search}`);
                }
                if (viewName === 'settings') {
                    const activeSettingsTab = document.querySelector('#view-settings .settings-nav-item.active');
                    if (!activeSettingsTab && typeof window.switchSettingsTab === 'function') {
                        window.switchSettingsTab('rbac');
                    }
                    if (typeof window.loadOperationalSettings === 'function') {
                        window.loadOperationalSettings();
                    }
                }
            }

            document.querySelectorAll('.sidebar-menu a[role="button"]').forEach(menuItem => {
                menuItem.addEventListener('keydown', event => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        menuItem.click();
                    }
                });
            });

            let currentRawAssets = [];
            let mapMarkersLayer = null;
            let coverageControlInstance = null;

            function toggleMapFilterPopover(event) {
                if (event) event.stopPropagation();
                const popover = document.getElementById('mapFilterPopover');
                if (!popover) return;
                const isVisible = popover.style.display === 'block';
                popover.style.display = isVisible ? 'none' : 'block';
            }

            document.addEventListener('click', (e) => {
                const popover = document.getElementById('mapFilterPopover');
                const btn = document.getElementById('btnMapFilterToggle');
                if (popover && popover.style.display === 'block') {
                    if (!popover.contains(e.target) && !btn.contains(e.target)) {
                        popover.style.display = 'none';
                    }
                }
            });

            function resetMapFilters() {
                if (document.getElementById('mapStatusFilter')) document.getElementById('mapStatusFilter').value = 'ALL';
                if (document.getElementById('mapLocationFilter')) document.getElementById('mapLocationFilter').value = 'ALL';
                if (document.getElementById('mapTimeFilter')) document.getElementById('mapTimeFilter').value = 'ALL';
                if (document.getElementById('mapSearchInput')) document.getElementById('mapSearchInput').value = '';
                if (document.getElementById('mapClusterToggle')) document.getElementById('mapClusterToggle').checked = true;
                applyMapFilters(true);
            }

            function applyMapFilters(shouldFitBounds = false) {
                if (!dashboardMapInstance) return;

                const statusVal = document.getElementById('mapStatusFilter')?.value || 'ALL';
                const locationVal = document.getElementById('mapLocationFilter')?.value || 'ALL';
                const timeVal = document.getElementById('mapTimeFilter')?.value || 'ALL';
                const isCluster = document.getElementById('mapClusterToggle')?.checked ?? true;
                const searchVal = (document.getElementById('mapSearchInput')?.value || '').trim().toLowerCase();

                let activeFilterCount = 0;
                if (statusVal !== 'ALL') activeFilterCount++;
                if (locationVal !== 'ALL') activeFilterCount++;
                if (timeVal !== 'ALL') activeFilterCount++;
                if (searchVal) activeFilterCount++;

                const badgeEl = document.getElementById('mapFilterBadge');
                if (badgeEl) {
                    if (activeFilterCount > 0) {
                        badgeEl.innerText = activeFilterCount;
                        badgeEl.style.display = 'inline-block';
                    } else {
                        badgeEl.style.display = 'none';
                    }
                }

                const now = new Date();

                const filteredAssets = (currentRawAssets || []).filter(asset => {
                    // 1. Status Filter
                    if (statusVal !== 'ALL') {
                        const s = String(asset.status || 'READY').toUpperCase();
                        if (statusVal === 'BREAKDOWN' && !['BREAKDOWN', 'ACCIDENT HOLD'].includes(s)) return false;
                        if (statusVal !== 'BREAKDOWN' && s !== statusVal) return false;
                    }

                    // 2. Location Filter (Flexible keyword matching e.g. "pekanbaru" matches "PKB PEKANBARU Branch", "Pekanbaru Yard", etc.)
                    if (locationVal !== 'ALL') {
                        const loc = String(asset.location || asset.location_name || '').toLowerCase();
                        const keyword = locationVal.toLowerCase().replace(/\s*branch\s*/g, '').trim();
                        if (!loc.includes(keyword)) return false;
                    }

                    // 3. Search Filter
                    if (searchVal) {
                        const code = String(asset.id || asset.asset_code || '').toLowerCase();
                        const cat = String(asset.category || '').toLowerCase();
                        const loc = String(asset.location || asset.location_name || '').toLowerCase();
                        if (!code.includes(searchVal) && !cat.includes(searchVal) && !loc.includes(searchVal)) return false;
                    }

                    // 4. Time Recency Filter
                    if (timeVal !== 'ALL') {
                        const timeStr = asset.gps_updated_at || asset.updated_at || asset.created_at;
                        if (timeStr) {
                            const updatedAt = new Date(timeStr);
                            if (!isNaN(updatedAt.getTime())) {
                                const diffHours = (now - updatedAt) / (1000 * 60 * 60);
                                if (timeVal === '24H' && diffHours > 24) return false;
                                if (timeVal === '7D' && (diffHours < 24 || diffHours > 168)) return false;
                                if (timeVal === 'OLD' && diffHours <= 168) return false;
                            }
                        }
                    }

                    return true;
                });

                renderMapMarkers(filteredAssets, isCluster, shouldFitBounds);
            }

            function renderMapMarkers(assets, enableClustering, shouldFitBounds = false) {
                const map = dashboardMapInstance;
                if (!map) return;

                if (mapMarkersLayer) {
                    map.removeLayer(mapMarkersLayer);
                }
                mapMarkersLayer = L.layerGroup().addTo(map);

                const coordinateGroups = new Map();
                const unmappedAssets = [];

                (Array.isArray(assets) ? assets : []).forEach(asset => {
                    const position = resolveAssetMapPosition(asset);
                    if (!position) {
                        unmappedAssets.push(asset);
                        return;
                    }
                    const location = String(asset.location || asset.location_name || 'Lokasi belum ditetapkan').trim();
                    const key = `${location}|${position.lat.toFixed(6)}|${position.lng.toFixed(6)}|${position.source}`;
                    if (!coordinateGroups.has(key)) {
                        coordinateGroups.set(key, { ...position, location, assets: [] });
                    }
                    coordinateGroups.get(key).assets.push(asset);
                });

                const rawNodes = Array.from(coordinateGroups.values());

                // Perform Deterministic Geographic Distance Clustering
                let finalClusters = [];

                if (enableClustering) {
                    const currentZoom = map.getZoom() || 10;
                    // Geographic distance threshold (in degrees lat/lng) scales with zoom
                    let geoRadiusDeg = 0.05;
                    if (currentZoom >= 13) geoRadiusDeg = 0.008;      // ~800 meters
                    else if (currentZoom >= 11) geoRadiusDeg = 0.02;  // ~2 km
                    else if (currentZoom >= 9) geoRadiusDeg = 0.05;   // ~5 km
                    else geoRadiusDeg = 0.12;                        // ~12 km

                    const processed = new Set();

                    rawNodes.forEach((nodeA, i) => {
                        if (processed.has(i)) return;

                        const clusterGroup = [nodeA];
                        processed.add(i);

                        rawNodes.forEach((nodeB, j) => {
                            if (processed.has(j)) return;
                            const dLat = nodeA.lat - nodeB.lat;
                            const dLng = nodeA.lng - nodeB.lng;
                            const geoDist = Math.sqrt(dLat * dLat + dLng * dLng);

                            if (geoDist <= geoRadiusDeg) {
                                clusterGroup.push(nodeB);
                                processed.add(j);
                            }
                        });

                        // Merge nodes into cluster
                        const totalAssets = clusterGroup.flatMap(g => g.assets);
                        const avgLat = clusterGroup.reduce((sum, g) => sum + g.lat, 0) / clusterGroup.length;
                        const avgLng = clusterGroup.reduce((sum, g) => sum + g.lng, 0) / clusterGroup.length;
                        const locations = Array.from(new Set(clusterGroup.map(g => g.location))).join(', ');
                        const isRep = clusterGroup.some(g => g.isRepresentative);

                        finalClusters.push({
                            lat: avgLat,
                            lng: avgLng,
                            location: clusterGroup.length > 1 ? `Kluster Area (${clusterGroup.length} Titik)` : nodeA.location,
                            assets: totalAssets,
                            isRepresentative: isRep,
                            nodes: clusterGroup
                        });
                    });
                } else {
                    finalClusters = rawNodes;
                }

                const bounds = [];

                finalClusters.forEach(cluster => {
                    const statusCounts = cluster.assets.reduce((counts, asset) => {
                        const status = String(asset.status || 'READY').toUpperCase();
                        counts[status] = (counts[status] || 0) + 1;
                        return counts;
                    }, {});

                    const dominantStatus = cluster.assets.reduce((selected, asset) => {
                        const status = String(asset.status || 'READY').toUpperCase();
                        return (ASSET_STATUS_PRIORITY[status] || 0) > (ASSET_STATUS_PRIORITY[selected] || 0) ? status : selected;
                    }, 'READY');

                    const markerColor = ['BREAKDOWN', 'ACCIDENT HOLD'].includes(dominantStatus) ? '#dc3545'
                        : dominantStatus === 'INSPEKSI' ? '#ffc107'
                            : dominantStatus === 'STANDBY' ? '#17a2b8' : '#28a745';

                    const isMulti = cluster.assets.length > 1;
                    const countLabel = cluster.assets.length > 99 ? '99+' : String(cluster.assets.length);
                    const markerBorder = cluster.isRepresentative ? '2px dashed #fd7e14' : '2px solid #fff';

                    const iconHtml = `<div style="background:${markerColor}; min-width:30px; height:30px; padding:0 6px; border-radius:15px; border:${markerBorder}; box-shadow:0 3px 10px rgba(0,0,0,.45); color:#fff; display:flex; align-items:center; justify-content:center; font:800 12px sans-serif; cursor:pointer;">
                        ${isMulti ? `<i class="fa-solid fa-layer-group" style="font-size:10px; margin-right:4px;"></i>` : ''}${countLabel}
                    </div>`;

                    const icon = L.divIcon({
                        className: 'custom-div-icon',
                        html: iconHtml,
                        iconSize: [34, 34],
                        iconAnchor: [17, 17]
                    });

                    const statusSummary = Object.entries(statusCounts)
                        .map(([status, count]) => `<span class="badge" style="background:${status === 'READY' ? '#28a745' : (status === 'BREAKDOWN' ? '#dc3545' : '#ffc107')}; color:#fff; padding:2px 6px; font-size:10px;">${escapeHtml(status)}: ${count}</span>`)
                        .join(' ');

                    const assetRows = cluster.assets
                        .slice()
                        .sort((a, b) => String(a.id).localeCompare(String(b.id), 'id'))
                        .map(asset => `<button type="button" class="map-asset-link" data-map-asset-id="${escapeHtml(asset.id)}" style="display:flex; width:100%; gap:8px; justify-content:space-between; border:0; border-bottom:1px solid #eee; background:#fff; padding:7px 4px; cursor:pointer; text-align:left;">
                            <span><strong>${escapeHtml(asset.id)}</strong><br><small>${escapeHtml(asset.category || 'Alat Berat')} &middot; ${escapeHtml(asset.location || '')}</small></span>
                            <span class="badge" style="align-self:center; font-size:11px;">${escapeHtml(asset.status || 'READY')}</span>
                        </button>`).join('');

                    const popup = `<div style="min-width:270px; max-width:370px;">
                        <strong style="font-size:14px; color:#1e293b;">${escapeHtml(cluster.location)}</strong><br>
                        <small style="color:#64748b;">${cluster.isRepresentative ? 'Koordinat representatif cabang' : `${cluster.lat.toFixed(6)}, ${cluster.lng.toFixed(6)}`}</small>
                        <p style="margin:8px 0;"><b>Total ${cluster.assets.length} Unit Armada</b><br><div style="display:flex; flex-wrap:wrap; gap:4px; margin-top:4px;">${statusSummary}</div></p>
                        <div style="max-height:220px; overflow-y:auto; border-top:1px solid #e2e8f0; margin-top:6px;">${assetRows}</div>
                        <div style="margin-top:10px; display:flex; justify-content:space-between; align-items:center; gap:8px;">
                            <a href="https://www.google.com/maps?q=${cluster.lat.toFixed(6)},${cluster.lng.toFixed(6)}" target="_blank" rel="noopener noreferrer" style="color:#0d6efd; font-weight:600; text-decoration:none; font-size:11px;">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i> Google Maps
                            </a>
                            <button type="button" class="btn btn-sm btn-primary" onclick="window.openSiteProperties('${escapeHtml(cluster.location)}', 'dashboard')" style="padding:3px 8px; font-size:11px; font-weight:700; display:inline-flex; align-items:center; gap:4px;">
                                <i class="fa-solid fa-industry"></i> Properties Site &rarr;
                            </button>
                        </div>
                    </div>`;

                    const marker = L.marker([cluster.lat, cluster.lng], { icon }).addTo(mapMarkersLayer).bindPopup(popup, { maxWidth: 400 });

                    if (isMulti && enableClustering) {
                        marker.on('dblclick', () => {
                            map.setView([cluster.lat, cluster.lng], map.getZoom() + 2);
                        });
                    }

                    bounds.push([cluster.lat, cluster.lng]);
                });

                // Update Coverage Control Card
                if (coverageControlInstance) {
                    map.removeControl(coverageControlInstance);
                }

                coverageControlInstance = L.control({ position: 'topright' });
                coverageControlInstance.onAdd = function () {
                    const control = L.DomUtil.create('div');
                    control.style.cssText = 'background:#fff;padding:8px 12px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.2);font:12px/1.4 Segoe UI,sans-serif; border:1px solid #e2e8f0;';
                    const totalFiltered = assets.length;
                    const clusterCount = finalClusters.length;
                    const mappedCount = totalFiltered - unmappedAssets.length;

                    control.innerHTML = `
                        <div style="font-weight:700; color:#0f172a; margin-bottom:4px; font-size:13px;"><i class="fa-solid fa-layer-group" style="color:var(--primary);"></i> Cakupan & Kluster Peta</div>
                        <div style="color:#198754; font-weight:600;"><i class="fa-solid fa-circle-check"></i> ${mappedCount} unit terpetakan</div>
                        <div style="color:#0d6efd; font-weight:600;"><i class="fa-solid fa-object-group"></i> ${clusterCount} titik kluster kelompok</div>
                        <div style="color:${unmappedAssets.length ? '#dc3545' : '#64748b'};"><i class="fa-solid fa-triangle-exclamation"></i> ${unmappedAssets.length} tanpa data lokasi</div>
                    `;
                    L.DomEvent.disableClickPropagation(control);
                    return control;
                };
                coverageControlInstance.addTo(map);

                // Only fit bounds on explicit filter changes or initial load, NOT on user zoom events
                if (shouldFitBounds) {
                    if (bounds.length === 1) {
                        map.setView(bounds[0], 11);
                    } else if (bounds.length > 1) {
                        map.fitBounds(bounds, { padding: [40, 40], maxZoom: 12 });
                    } else {
                        map.setView([1.1500, 101.4000], 10);
                    }
                }
            }

            function initMap(assets) {
                const mapContainer = document.getElementById('map');
                if (!mapContainer || typeof L === 'undefined') return;

                currentRawAssets = Array.isArray(assets) ? assets : [];

                if (dashboardMapInstance) {
                    dashboardMapInstance.remove();
                    dashboardMapInstance = null;
                }
                mapContainer.innerHTML = '';

                // Default focus view centered on Riau Operations Region (Pekanbaru/Duri)
                const map = L.map('map', { minZoom: 3, maxZoom: 19 }).setView([1.1500, 101.4000], 10);
                dashboardMapInstance = map;

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                // Re-cluster on user zoom without resetting camera position
                map.on('zoomend', () => {
                    const isCluster = document.getElementById('mapClusterToggle')?.checked ?? true;
                    if (isCluster) {
                        applyMapFilters(false);
                    }
                });

                map.on('popupopen', event => {
                    event.popup.getElement()?.querySelectorAll('[data-map-asset-id]').forEach(button => {
                        button.addEventListener('click', () => {
                            const asset = (globalData?.assets || []).find(item => String(item.id) === button.dataset.mapAssetId);
                            if (asset) openAssetModal(asset.id, asset.status, asset.category, asset.location);
                        });
                    });
                });

                applyMapFilters(true);
// Multi-pass size invalidation to ensure map markers render perfectly after container layout settles
                setTimeout(() => map.invalidateSize(), 150);
                setTimeout(() => map.invalidateSize(), 500);
            }

            window.setMonitoringStatusFilter = function (status, fromChart = false) {
                const filterEl = document.getElementById('monitoringStatusFilter');
                const targetStatus = status || 'ALL';
                const norm = s => String(s || '').trim().toUpperCase().replace(/[\s_-]+/g, '');
                const targetNorm = norm(targetStatus);
                
                if (filterEl) {
                    let matched = false;
                    for (let i = 0; i < filterEl.options.length; i++) {
                        if (norm(filterEl.options[i].value) === targetNorm) {
                            filterEl.selectedIndex = i;
                            matched = true;
                            break;
                        }
                    }
                    if (!matched) {
                        filterEl.value = targetStatus;
                    }
                }
                
                updateMonitoringActiveCard(targetStatus);
                renderMonitoringTable();
            };

            window.toggleMonitoringCardFilter = function (status) {
                const filterEl = document.getElementById('monitoringStatusFilter');
                const norm = s => String(s || '').trim().toUpperCase().replace(/[\s_-]+/g, '');
                const currentVal = filterEl ? norm(filterEl.value) : 'ALL';
                const targetNorm = norm(status);
                if (currentVal === targetNorm) {
                    window.setMonitoringStatusFilter('ALL');
                } else {
                    window.setMonitoringStatusFilter(status);
                }
            };

            function updateMonitoringActiveCard(selectedStatus) {
                const summaryEl = document.getElementById('monitoringSummary');
                if (!summaryEl) return;
                const norm = s => String(s || '').trim().toUpperCase().replace(/[\s_-]+/g, '');
                const targetNorm = norm(selectedStatus);
                const cards = summaryEl.querySelectorAll('.mon-card-clickable');
                cards.forEach(card => {
                    const cardStatus = norm(card.getAttribute('data-status'));
                    if (cardStatus === targetNorm) {
                        card.classList.add('active-filter');
                    } else {
                        card.classList.remove('active-filter');
                    }
                });
            }

            function renderMonitoringTable(assets) {
                if (!assets || !Array.isArray(assets) || assets.length === 0) {
                    assets = window.currentMonitoringAssets || (typeof buildProjectScopedData === 'function' ? buildProjectScopedData().assets : (window.globalData ? window.globalData.assets : []));
                }
                const filterEl = document.getElementById('monitoringStatusFilter');
                const selectedStatus = filterEl ? filterEl.value : 'ALL';

                const norm = s => String(s || '').trim().toUpperCase().replace(/[\s_-]+/g, '');
                const targetNorm = norm(selectedStatus);

                const filtered = (!selectedStatus || targetNorm === 'ALL')
                    ? assets
                    : assets.filter(asset => {
                        const assetStat = norm(asset.status || asset.subStatus);
                        return assetStat === targetNorm;
                    });

                const tbody = document.getElementById('monTableBody');
                if (!tbody) return;
                if (!filtered || filtered.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="monitoring-empty"><i class="fa-solid fa-circle-check"></i> Tidak ada unit untuk filter ini.</td></tr>';
                    return;
                }

                tbody.innerHTML = filtered.map(asset => {
                    let badge = '';
                    let action = '';
                    const context = (typeof getUnitIntegrationContext === 'function')
                        ? getUnitIntegrationContext(asset)
                        : { activeWo: null, latestInspection: null };
                    const activeWo = context.activeWo;
                    const inspection = context.latestInspection;
                    const source = asset.statusSource || (activeWo ? 'Work Order' : inspection ? 'Inspeksi & P2H' : 'Master Asset');
                    const reference = asset.statusReference || (activeWo ? activeWo.woId : inspection ? inspection.id : 'Data awal');

                    badge = (typeof window.getAssetStatusBadge === 'function')
                        ? window.getAssetStatusBadge(asset.status)
                        : (typeof getAssetStatusBadge === 'function' ? getAssetStatusBadge(asset.status) : `<span class="badge bg-secondary">${escapeHtml(asset.status || '')}</span>`);

                    const statusKey = norm(asset.status || asset.subStatus);
                    if (statusKey === 'READY') {
                        action = `
                        <button class="integrated-action success" onclick="openIntegratedStatusUpdate('${escapeHtml(asset.id)}', 'OPERATING')"><i class="fa-solid fa-play"></i> Tugaskan</button>
                        <button class="integrated-action primary" onclick="openAssetModal('${escapeHtml(asset.id)}', '${escapeHtml(asset.status)}', '${escapeHtml(asset.category)}', '${escapeHtml(asset.location)}', null, 'lokasi')"><i class="fa-solid fa-gauge-high"></i> Telemetri</button>
                        <button class="integrated-action" onclick="openIntegratedStatusUpdate('${escapeHtml(asset.id)}')"><i class="fa-solid fa-pen"></i> Update</button>`;
                    }
                    else if (statusKey === 'OPERATING') {
                        action = `
                        <button class="integrated-action primary" onclick="openAssetModal('${escapeHtml(asset.id)}', '${escapeHtml(asset.status)}', '${escapeHtml(asset.category)}', '${escapeHtml(asset.location)}')"><i class="fa-solid fa-gauge-high"></i> Telemetri</button>
                        <button class="integrated-action" onclick="openIntegratedStatusUpdate('${escapeHtml(asset.id)}')"><i class="fa-solid fa-pen"></i> Update</button>`;
                    }
                    else if (statusKey === 'STANDBY') {
                        action = `
                        <button class="integrated-action primary" onclick="openIntegratedStatusUpdate('${escapeHtml(asset.id)}')"><i class="fa-solid fa-pen"></i> Evaluasi Status</button>
                        <button class="integrated-action" onclick="openIntegratedP2H('${escapeHtml(asset.id)}', 'form')"><i class="fa-solid fa-clipboard-check"></i> Input P2H</button>`;
                    }
                    else if (statusKey === 'BREAKDOWN') {
                        action = `
                        <button class="integrated-action danger" onclick="openIntegratedWO('${escapeHtml(asset.id)}')"><i class="fa-solid fa-screwdriver-wrench"></i> ${activeWo ? escapeHtml(activeWo.woId) : 'Buat WO'}</button>
                        <button class="integrated-action" onclick="openIntegratedStatusUpdate('${escapeHtml(asset.id)}')"><i class="fa-solid fa-pen"></i> Update</button>`;
                    }
                    else if (statusKey === 'INSPEKSI') {
                        action = `
                        <button class="integrated-action warning" onclick="openIntegratedP2H('${escapeHtml(asset.id)}', 'history')"><i class="fa-solid fa-clipboard-check"></i> Review P2H</button>
                        ${activeWo ? `<button class="integrated-action" onclick="openIntegratedWO('${escapeHtml(asset.id)}')"><i class="fa-solid fa-wrench"></i> ${escapeHtml(activeWo.woId)}</button>` : `<button class="integrated-action" onclick="openIntegratedP2H('${escapeHtml(asset.id)}', 'form')"><i class="fa-solid fa-plus"></i> Inspeksi</button>`}`;
                    }
                    else if (statusKey === 'ACCIDENTHOLD') {
                        action = `
                        <button class="integrated-action danger" onclick="openIntegratedWO('${escapeHtml(asset.id)}')"><i class="fa-solid fa-shield-cat"></i> Investigasi</button>
                        <button class="integrated-action" onclick="openIntegratedStatusUpdate('${escapeHtml(asset.id)}')"><i class="fa-solid fa-pen"></i> Update</button>`;
                    }
                    else {
                        action = `<button class="integrated-action" onclick="openIntegratedStatusUpdate('${escapeHtml(asset.id)}')"><i class="fa-solid fa-pen"></i> Update Status</button>`;
                    }

                    return `
                    <tr class="monitoring-unit-row">
                        <td>
                            <div style="display:inline-flex; align-items:center; gap:6px;">
                                <button class="unit-link-button" onclick="openAssetModal('${escapeHtml(asset.id)}', '${escapeHtml(asset.status)}', '${escapeHtml(asset.category)}', '${escapeHtml(asset.location)}')">${escapeHtml(asset.id)}</button>
                                <a href="javascript:void(0)" onclick="window.openUnitProperties('${escapeHtml(asset.id)}')" class="unit-prop-link" title="Buka Properties Lengkap Unit ${escapeHtml(asset.id)}" style="font-size:0.75rem;"><i class="fa-solid fa-sliders"></i></a>
                            </div>
                            <small>${escapeHtml(asset.lastUpdate || 'Belum ada waktu update')}</small>
                        </td>
                        <td>${escapeHtml(asset.category || '')}</td>
                        <td>
                            <a href="javascript:void(0)" 
                               class="site-prop-hover-link" 
                               onclick="window.openSiteProperties('${escapeHtml(asset.location || '')}', 'monitoring'); return false;" 
                               title="Buka Properties &amp; Peta Site ${escapeHtml(asset.location || '')}">
                                <span class="site-name-text">${escapeHtml(asset.location || '-')}</span>
                                <i class="fa-solid fa-arrow-up-right-from-square site-prop-hover-icon" aria-hidden="true"></i>
                            </a>
                        </td>
                        <td>
                            ${badge}
                            <span class="status-source-line"><i class="fa-solid fa-link"></i> ${escapeHtml(source)} &middot; ${escapeHtml(reference)}</span>
                        </td>
                        <td><div class="integrated-actions">${action}</div></td>
                    </tr>
                `;
                }).join('');
                if (typeof searchTableMonitoring === 'function') {
                    searchTableMonitoring();
                }
            }

            function initMonitoringView(assets) {
                if (!assets || !Array.isArray(assets) || assets.length === 0) {
                    assets = typeof buildProjectScopedData === 'function' ? buildProjectScopedData().assets : (window.globalData ? window.globalData.assets : []);
                }
                if (!assets || !Array.isArray(assets)) return;
                window.currentMonitoringAssets = assets;

                const filterEl = document.getElementById('monitoringStatusFilter');
                const selectedStatus = filterEl ? filterEl.value : 'ALL';
                const norm = s => String(s || '').trim().toUpperCase().replace(/[\s_-]+/g, '');

                const totalCount = assets.length;
                const readyCount = assets.filter(a => norm(a.status || a.subStatus) === 'READY').length;
                const operatingCount = assets.filter(a => norm(a.status || a.subStatus) === 'OPERATING').length;
                const breakdownCount = assets.filter(a => norm(a.status || a.subStatus) === 'BREAKDOWN').length;
                const inspectionCount = assets.filter(a => norm(a.status || a.subStatus) === 'INSPEKSI').length;
                const standbyCount = assets.filter(a => norm(a.status || a.subStatus) === 'STANDBY').length;
                const activeWos = (globalData?.work_orders || []).filter(wo => wo.status !== 'Closed').length;

                const summaryEl = document.getElementById('monitoringSummary');
                if (summaryEl) {
                    summaryEl.innerHTML = `
                    <div class="total mon-card-clickable ${norm(selectedStatus) === 'ALL' ? 'active-filter' : ''}" data-status="ALL" onclick="window.toggleMonitoringCardFilter('ALL')" title="Klik untuk menampilkan semua unit">
                        <div class="mon-card-hdr">
                            <span class="mon-card-lbl">Total Unit Armada</span>
                            <div class="mon-card-icon total"><i class="fa-solid fa-cubes"></i></div>
                        </div>
                        <div class="mon-card-body-row">
                            <strong style="color:var(--text-main); font-size:1.5rem; margin:0;">${totalCount} <span style="font-size:0.8rem; font-weight:600; color:var(--text-muted);">Unit</span></strong>
                            <div class="mon-card-ftr"><i class="fa-solid fa-layer-group"></i> Master Asset Terdaftar</div>
                        </div>
                    </div>
                    <div class="success mon-card-clickable ${norm(selectedStatus) === 'READY' ? 'active-filter' : ''}" data-status="READY" onclick="window.toggleMonitoringCardFilter('READY')" title="Klik untuk memfilter unit Ready">
                        <div class="mon-card-hdr">
                            <span class="mon-card-lbl">Ready</span>
                            <div class="mon-card-icon success"><i class="fa-solid fa-circle-check"></i></div>
                        </div>
                        <strong style="color:#16a34a;">${readyCount}</strong>
                        <div class="mon-card-ftr"><i class="fa-solid fa-check"></i> Siap Dioperasikan</div>
                    </div>
                    <div class="info mon-card-clickable ${norm(selectedStatus) === 'OPERATING' ? 'active-filter' : ''}" data-status="OPERATING" onclick="window.toggleMonitoringCardFilter('OPERATING')" title="Klik untuk memfilter unit Operating">
                        <div class="mon-card-hdr">
                            <span class="mon-card-lbl">Operating</span>
                            <div class="mon-card-icon info"><i class="fa-solid fa-play"></i></div>
                        </div>
                        <strong style="color:#0284c7;">${operatingCount}</strong>
                        <div class="mon-card-ftr"><i class="fa-solid fa-truck-fast"></i> Beroperasi di Lapangan</div>
                    </div>
                    <div class="standby mon-card-clickable ${norm(selectedStatus) === 'STANDBY' ? 'active-filter' : ''}" data-status="STANDBY" onclick="window.toggleMonitoringCardFilter('STANDBY')" title="Klik untuk memfilter unit Standby">
                        <div class="mon-card-hdr">
                            <span class="mon-card-lbl">Standby</span>
                            <div class="mon-card-icon standby"><i class="fa-solid fa-pause-circle"></i></div>
                        </div>
                        <strong style="color:#0ea5e9;">${standbyCount}</strong>
                        <div class="mon-card-ftr"><i class="fa-solid fa-clock"></i> Cadangan / Standby</div>
                    </div>
                    <div class="warning mon-card-clickable ${norm(selectedStatus) === 'INSPEKSI' ? 'active-filter' : ''}" data-status="INSPEKSI" onclick="window.toggleMonitoringCardFilter('INSPEKSI')" title="Klik untuk memfilter unit Inspeksi / PM">
                        <div class="mon-card-hdr">
                            <span class="mon-card-lbl">Inspeksi & PM</span>
                            <div class="mon-card-icon warning"><i class="fa-solid fa-clipboard-check"></i></div>
                        </div>
                        <strong style="color:#f59e0b;">${inspectionCount}</strong>
                        <div class="mon-card-ftr"><i class="fa-solid fa-magnifying-glass"></i> Perawatan / P2H</div>
                    </div>
                    <div class="danger mon-card-clickable ${norm(selectedStatus) === 'BREAKDOWN' ? 'active-filter' : ''}" data-status="BREAKDOWN" onclick="window.toggleMonitoringCardFilter('BREAKDOWN')" title="Klik untuk memfilter unit Breakdown">
                        <div class="mon-card-hdr">
                            <span class="mon-card-lbl">Breakdown</span>
                            <div class="mon-card-icon danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
                        </div>
                        <strong style="color:#dc2626;">${breakdownCount}</strong>
                        <div class="mon-card-ftr" style="color:#dc2626;"><i class="fa-solid fa-triangle-exclamation"></i> Kerusakan Unit</div>
                    </div>
                    <div class="purple mon-card-clickable" onclick="showView('wo')" title="Buka modul Work Order">
                        <div class="mon-card-hdr">
                            <span class="mon-card-lbl">WO Aktif</span>
                            <div class="mon-card-icon purple"><i class="fa-solid fa-wrench"></i></div>
                        </div>
                        <strong style="color:#8b5cf6;">${activeWos}</strong>
                        <div class="mon-card-ftr" style="color:#8b5cf6;"><i class="fa-solid fa-screwdriver-wrench"></i> Work Order Berjalan</div>
                    </div>
                `;
                }

                // Update Total Badge pada Header Chart
                const chartBadgeEl = document.getElementById('monitoringChartTotalBadge');
                if (chartBadgeEl) {
                    chartBadgeEl.textContent = `${totalCount} Unit`;
                }

                // Synchronize ApexCharts Donut Chart
                if (typeof ApexCharts !== 'undefined') {
                    const elChart = document.getElementById('monitoringStatusDonutChart');
                    if (elChart) {
                        const seriesData = [readyCount, operatingCount, standbyCount, inspectionCount, breakdownCount];
                        const labelsData = ['Ready', 'Operating', 'Standby', 'Inspeksi & PM', 'Breakdown'];
                        const colorsData = ['#16a34a', '#0284c7', '#0ea5e9', '#f59e0b', '#dc2626'];
                        const statusMapping = ['READY', 'OPERATING', 'STANDBY', 'INSPEKSI', 'BREAKDOWN'];

                        const chartOptions = {
                            series: seriesData,
                            labels: labelsData,
                            chart: {
                                type: 'donut',
                                height: 220,
                                fontFamily: "'Inter', sans-serif",
                                toolbar: { show: false },
                                events: {
                                    dataPointSelection: function (event, chartContext, config) {
                                        const clickedIdx = config.dataPointIndex;
                                        const isSelected = config.selectedDataPoints && config.selectedDataPoints[0] && config.selectedDataPoints[0].length > 0;
                                        
                                        let targetStatus = 'ALL';
                                        if (clickedIdx >= 0 && clickedIdx < statusMapping.length) {
                                            targetStatus = isSelected ? statusMapping[clickedIdx] : 'ALL';
                                        }
                                        window.setMonitoringStatusFilter(targetStatus, true);
                                    },
                                    legendClick: function (chartContext, seriesIndex, config) {
                                        if (seriesIndex >= 0 && seriesIndex < statusMapping.length) {
                                            window.toggleMonitoringCardFilter(statusMapping[seriesIndex]);
                                        }
                                        return false; // prevent hiding series from donut
                                    }
                                }
                            },
                            colors: colorsData,
                            legend: {
                                show: true,
                                position: 'bottom',
                                fontSize: '11px',
                                fontFamily: "'Inter', sans-serif",
                                markers: { width: 8, height: 8, radius: 4 },
                                itemMargin: { horizontal: 6, vertical: 2 },
                                onItemClick: {
                                    toggleDataSeries: false
                                }
                            },
                            dataLabels: {
                                enabled: true,
                                style: { fontSize: '10px', fontFamily: "'Inter', sans-serif", fontWeight: 700 },
                                dropShadow: { enabled: false },
                                formatter: (val) => val.toFixed(0) + '%'
                            },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        size: '68%',
                                        labels: {
                                             show: true,
                                            name: { show: true, fontSize: '11px', fontWeight: 600, color: '#64748b', offsetY: -4 },
                                            value: { show: true, fontSize: '18px', fontWeight: 800, color: '#0f172a', offsetY: 4, formatter: (val) => val },
                                            total: {
                                                show: true,
                                                label: 'TOTAL UNIT',
                                                fontSize: '10px',
                                                fontWeight: 700,
                                                color: '#64748b',
                                                formatter: () => `${totalCount}`
                                            }
                                        }
                                    }
                                }
                            },
                            stroke: { width: 2, colors: ['#ffffff'] },
                            tooltip: {
                                y: {
                                    formatter: (val) => `${val} Unit`
                                }
                            }
                        };

                        if (window.monitoringDonutChartInstance) {
                            window.monitoringDonutChartInstance.updateOptions({
                                series: seriesData,
                                labels: labelsData,
                                colors: colorsData,
                                plotOptions: chartOptions.plotOptions
                            });
                        } else {
                            elChart.innerHTML = '';
                            window.monitoringDonutChartInstance = new ApexCharts(elChart, chartOptions);
                            window.monitoringDonutChartInstance.render();
                        }
                    }
                }

                renderMonitoringTable(assets);
                updateMonitoringActiveCard(selectedStatus);
            }
            window.initMonitoringView = initMonitoringView;
            window.renderMonitoringTable = renderMonitoringTable;

            function searchTableMonitoring() {
                const input = document.getElementById('searchMonitoring');
                const filter = input ? input.value.toUpperCase().trim() : '';
                const table = document.getElementById('monitoringTable');
                if (!table) return;
                const tr = table.getElementsByTagName('tr');

                for (let i = 1; i < tr.length; i++) {
                    if (tr[i].querySelector('.monitoring-empty')) {
                        tr[i].style.display = "";
                        continue;
                    }
                    if (!filter) {
                        tr[i].style.display = "";
                        continue;
                    }
                    let visible = false;
                    const tds = tr[i].getElementsByTagName('td');
                    for (let j = 0; j < tds.length; j++) {
                        if (tds[j]) {
                            if (tds[j].textContent.toUpperCase().indexOf(filter) > -1) {
                                visible = true;
                                break;
                            }
                        }
                    }
                    tr[i].style.display = visible ? "" : "none";
                }
            }

            let activeCalloutTarget = null;
            let lastSelectedRow = null;
            let cachedCalloutEl = null;

            function getOrCreateCalloutContainer() {
                let callout = document.getElementById('assetActionCallout');
                if (!callout) {
                    callout = document.createElement('div');
                    callout.id = 'assetActionCallout';
                    callout.className = 'asset-action-callout';
                    callout.onclick = function (e) { e.stopPropagation(); };
                    callout.innerHTML = `
                        <div class="callout-header-compact" style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding-bottom:6px; margin-bottom:6px; border-bottom:1px solid #f1f5f9;">
                            <div class="callout-title-wrap" style="display:flex; align-items:center; gap:8px;">
                                <span id="cUnitBadge" class="callout-unit-badge" style="font-weight:800; color:#0284c7; background:#eff6ff; border:1px solid #bfdbfe; font-family:'JetBrains Mono', monospace, sans-serif; font-size:0.88rem; padding:2px 8px; border-radius:5px; line-height:1.2;"></span>
                                <span id="cUnitCat" class="callout-unit-category" style="font-size:0.72rem; background:#f1f5f9; color:#475569; padding:2px 7px; border-radius:4px; font-weight:600; line-height:1.2;"></span>
                                <span id="cUnitLoc" style="font-size:0.73rem; color:#64748b; font-weight:500;"></span>
                            </div>
                            <button type="button" class="callout-close-btn" onclick="closeAssetActionCallout()" style="background:none; border:none; font-size:1.2rem; color:#94a3b8; cursor:pointer; padding:0 4px; line-height:1; font-weight:400;" title="Tutup">&times;</button>
                        </div>
                        <div class="callout-actions-row" style="display:flex; align-items:center; gap:6px; flex-wrap:nowrap;">
                            <button type="button" id="cBtn360" class="callout-btn-pill primary" style="background:#eff6ff; border:1px solid #93c5fd; color:#0284c7; border-radius:6px; padding:5px 10px; display:inline-flex; align-items:center; gap:5px; cursor:pointer; font-size:0.76rem; font-weight:700; white-space:nowrap; transition:all 0.15s ease;" title="Detail 360&deg; Master Asset & Telemetri">
                                <i class="fa-solid fa-cube"></i> <span>360&deg;</span>
                            </button>
                            <button type="button" id="cBtnP2H" class="callout-btn-pill success" style="background:#f0fdf4; border:1px solid #86efac; color:#16a34a; border-radius:6px; padding:5px 10px; display:inline-flex; align-items:center; gap:5px; cursor:pointer; font-size:0.76rem; font-weight:700; white-space:nowrap; transition:all 0.15s ease;" title="Inspeksi & P2H Unit">
                                <i class="fa-solid fa-clipboard-check"></i> <span>P2H</span>
                            </button>
                            <button type="button" id="cBtnWO" class="callout-btn-pill warning" style="background:#fffbeb; border:1px solid #fde68a; color:#d97706; border-radius:6px; padding:5px 10px; display:inline-flex; align-items:center; gap:5px; cursor:pointer; font-size:0.76rem; font-weight:700; white-space:nowrap; transition:all 0.15s ease;" title="Work Order & Reparasi">
                                <i class="fa-solid fa-screwdriver-wrench"></i> <span>WO</span>
                            </button>
                            <button type="button" id="cBtnCond" class="callout-btn-pill info" style="background:#f0f9ff; border:1px solid #7dd3fc; color:#0284c7; border-radius:6px; padding:5px 10px; display:inline-flex; align-items:center; gap:5px; cursor:pointer; font-size:0.76rem; font-weight:700; white-space:nowrap; transition:all 0.15s ease;" title="Condition Monitoring">
                                <i class="fa-solid fa-gauge-high"></i> <span>Condition</span>
                            </button>
                            <button type="button" id="cBtnEdit" class="callout-btn-pill neutral" style="background:#f8fafc; border:1px solid #cbd5e1; color:#475569; border-radius:6px; padding:5px 10px; display:inline-flex; align-items:center; gap:5px; cursor:pointer; font-size:0.76rem; font-weight:700; white-space:nowrap; transition:all 0.15s ease;" title="Edit Metadata Unit">
                                <i class="fa-solid fa-pen-to-square"></i> <span>Edit</span>
                            </button>
                            <button type="button" id="cBtnArsip" class="callout-btn-pill danger" style="background:#fef2f2; border:1px solid #fca5a5; color:#dc2626; border-radius:6px; padding:5px 10px; display:inline-flex; align-items:center; gap:5px; cursor:pointer; font-size:0.76rem; font-weight:700; white-space:nowrap; transition:all 0.15s ease;" title="Arsipkan Unit">
                                <i class="fa-solid fa-box-archive"></i> <span>Arsip</span>
                            </button>
                        </div>
                    `;
                    document.body.appendChild(callout);
                } else {
                    if (!document.getElementById('cUnitBadge')) {
                        callout.innerHTML = `
                            <div class="callout-header-compact" style="display:flex; align-items:center; justify-content:space-between; gap:12px; padding-bottom:6px; margin-bottom:6px; border-bottom:1px solid #f1f5f9;">
                                <div class="callout-title-wrap" style="display:flex; align-items:center; gap:8px;">
                                    <span id="cUnitBadge" class="callout-unit-badge" style="font-weight:800; color:#0284c7; background:#eff6ff; border:1px solid #bfdbfe; font-family:'JetBrains Mono', monospace, sans-serif; font-size:0.88rem; padding:2px 8px; border-radius:5px; line-height:1.2;"></span>
                                    <span id="cUnitCat" class="callout-unit-category" style="font-size:0.72rem; background:#f1f5f9; color:#475569; padding:2px 7px; border-radius:4px; font-weight:600; line-height:1.2;"></span>
                                    <span id="cUnitLoc" style="font-size:0.73rem; color:#64748b; font-weight:500;"></span>
                                </div>
                                <button type="button" class="callout-close-btn" onclick="closeAssetActionCallout()" style="background:none; border:none; font-size:1.2rem; color:#94a3b8; cursor:pointer; padding:0 4px; line-height:1; font-weight:400;" title="Tutup">&times;</button>
                            </div>
                            <div class="callout-actions-row" style="display:flex; align-items:center; gap:6px; flex-wrap:nowrap;">
                                <button type="button" id="cBtn360" class="callout-btn-pill primary" style="background:#eff6ff; border:1px solid #93c5fd; color:#0284c7; border-radius:6px; padding:5px 10px; display:inline-flex; align-items:center; gap:5px; cursor:pointer; font-size:0.76rem; font-weight:700; white-space:nowrap; transition:all 0.15s ease;" title="Detail 360&deg; Master Asset & Telemetri">
                                    <i class="fa-solid fa-cube"></i> <span>360&deg;</span>
                                </button>
                                <button type="button" id="cBtnP2H" class="callout-btn-pill success" style="background:#f0fdf4; border:1px solid #86efac; color:#16a34a; border-radius:6px; padding:5px 10px; display:inline-flex; align-items:center; gap:5px; cursor:pointer; font-size:0.76rem; font-weight:700; white-space:nowrap; transition:all 0.15s ease;" title="Inspeksi & P2H Unit">
                                    <i class="fa-solid fa-clipboard-check"></i> <span>P2H</span>
                                </button>
                                <button type="button" id="cBtnWO" class="callout-btn-pill warning" style="background:#fffbeb; border:1px solid #fde68a; color:#d97706; border-radius:6px; padding:5px 10px; display:inline-flex; align-items:center; gap:5px; cursor:pointer; font-size:0.76rem; font-weight:700; white-space:nowrap; transition:all 0.15s ease;" title="Work Order & Reparasi">
                                    <i class="fa-solid fa-screwdriver-wrench"></i> <span>WO</span>
                                </button>
                                <button type="button" id="cBtnCond" class="callout-btn-pill info" style="background:#f0f9ff; border:1px solid #7dd3fc; color:#0284c7; border-radius:6px; padding:5px 10px; display:inline-flex; align-items:center; gap:5px; cursor:pointer; font-size:0.76rem; font-weight:700; white-space:nowrap; transition:all 0.15s ease;" title="Condition Monitoring">
                                    <i class="fa-solid fa-gauge-high"></i> <span>Condition</span>
                                </button>
                                <button type="button" id="cBtnEdit" class="callout-btn-pill neutral" style="background:#f8fafc; border:1px solid #cbd5e1; color:#475569; border-radius:6px; padding:5px 10px; display:inline-flex; align-items:center; gap:5px; cursor:pointer; font-size:0.76rem; font-weight:700; white-space:nowrap; transition:all 0.15s ease;" title="Edit Metadata Unit">
                                    <i class="fa-solid fa-pen-to-square"></i> <span>Edit</span>
                                </button>
                                <button type="button" id="cBtnArsip" class="callout-btn-pill danger" style="background:#fef2f2; border:1px solid #fca5a5; color:#dc2626; border-radius:6px; padding:5px 10px; display:inline-flex; align-items:center; gap:5px; cursor:pointer; font-size:0.76rem; font-weight:700; white-space:nowrap; transition:all 0.15s ease;" title="Arsipkan Unit">
                                    <i class="fa-solid fa-box-archive"></i> <span>Arsip</span>
                                </button>
                            </div>
                        `;
                    }
                    if (callout.parentElement !== document.body) {
                        document.body.appendChild(callout);
                    }
                }
                cachedCalloutEl = callout;
                return callout;
            }

            function openAssetActionCalloutFromElement(event, el) {
                if (event) {
                    if (event.stopPropagation) event.stopPropagation();
                    if (event.stopImmediatePropagation) event.stopImmediatePropagation();
                    if (event.preventDefault) event.preventDefault();
                }
                if (!el) return;
                const assetId = el.getAttribute('data-asset-id') || '';
                const status = el.getAttribute('data-asset-status') || '';
                const category = el.getAttribute('data-asset-category') || '';
                const location = el.getAttribute('data-asset-location') || '';

                openAssetActionCallout(event, assetId, status, category, location, el);
            }

            function openAssetActionCallout(event, assetId, status, category, location, triggerEl) {
                if (event && event.stopPropagation) event.stopPropagation();
                
                // O(1) Fast row highlight
                const targetRow = triggerEl ? (triggerEl.tagName === 'TR' ? triggerEl : triggerEl.closest('tr')) : (event && event.target ? event.target.closest('tr') : null);
                if (lastSelectedRow && lastSelectedRow !== targetRow) {
                    lastSelectedRow.classList.remove('asset-table-row-selected');
                }
                if (targetRow) {
                    targetRow.classList.add('asset-table-row-selected');
                    lastSelectedRow = targetRow;
                }

                const callout = getOrCreateCalloutContainer();

                // Fast O(1) DOM text update without innerHTML string parsing
                const bId = document.getElementById('cUnitBadge');
                const bCat = document.getElementById('cUnitCat');
                const bLoc = document.getElementById('cUnitLoc');

                if (bId) bId.textContent = assetId;
                if (bCat) bCat.textContent = category;
                if (bLoc) bLoc.innerHTML = `<i class="fa-solid fa-location-dot" style="margin-right:2px;"></i> ${escapeHtml(location)}`;

                // Update button action click handlers instantly
                const btn360 = document.getElementById('cBtn360');
                const btnP2H = document.getElementById('cBtnP2H');
                const btnWO = document.getElementById('cBtnWO');
                const btnCond = document.getElementById('cBtnCond');
                const btnEdit = document.getElementById('cBtnEdit');
                const btnArsip = document.getElementById('cBtnArsip');

                if (btn360) btn360.onclick = () => { closeAssetActionCallout(); openAssetModal(assetId, status, category, location); };
                if (btnP2H) btnP2H.onclick = () => { closeAssetActionCallout(); openIntegratedP2H(assetId, 'history'); };
                if (btnWO) btnWO.onclick = () => { closeAssetActionCallout(); openIntegratedWO(assetId); };
                if (btnCond) btnCond.onclick = () => { closeAssetActionCallout(); ConditionMonitoring.openForAsset(assetId, 'overview'); };
                if (btnEdit) btnEdit.onclick = () => { closeAssetActionCallout(); openAssetModal(assetId, status, category, location); };
                if (btnArsip) btnArsip.onclick = () => { closeAssetActionCallout(); window.archiveData('asset', assetId); };

                // Target Asset ID cell for left alignment & scroll locking
                activeCalloutTarget = targetRow ? (targetRow.querySelector('td') || targetRow) : (triggerEl || (event ? (event.currentTarget || event.target) : null));
                
                callout.style.display = 'block';
                updateAssetCalloutPosition();
            }

            function updateAssetCalloutPosition() {
                const callout = cachedCalloutEl || document.getElementById('assetActionCallout');
                if (!callout || callout.style.display === 'none' || !activeCalloutTarget) return;

                if (!document.body.contains(activeCalloutTarget)) {
                    closeAssetActionCallout();
                    return;
                }

                const rect = activeCalloutTarget.getBoundingClientRect();

                // Hide callout if target row is scrolled out of viewport
                if (rect.bottom < 10 || rect.top > window.innerHeight - 10 || rect.right < 10 || rect.left > window.innerWidth - 10) {
                    callout.style.display = 'none';
                    return;
                }

                const calloutWidth = 470;
                const calloutHeight = 85;

                let left = rect.left; // Align left under Asset ID
                let top = rect.bottom + 8;
                let arrowClass = 'arrow-top';

                // Prevent overflow off right edge of window
                if (left + calloutWidth > window.innerWidth - 10) {
                    left = window.innerWidth - calloutWidth - 10;
                }
                if (left < 10) left = 10;

                // Flip to open above row if overflowing bottom
                if (top + calloutHeight > window.innerHeight) {
                    top = rect.top - calloutHeight - 8;
                    arrowClass = 'arrow-bottom';
                }

                callout.className = `asset-action-callout ${arrowClass}`;
                callout.style.left = Math.round(left) + 'px';
                callout.style.top = Math.round(top) + 'px';
            }

            function closeAssetActionCallout() {
                const callout = cachedCalloutEl || document.getElementById('assetActionCallout');
                if (callout) callout.style.display = 'none';
                activeCalloutTarget = null;
                if (lastSelectedRow) {
                    lastSelectedRow.classList.remove('asset-table-row-selected');
                    lastSelectedRow = null;
                }
            }

            window.openAssetActionCalloutFromElement = openAssetActionCalloutFromElement;
            window.openAssetActionCallout = openAssetActionCallout;
            window.closeAssetActionCallout = closeAssetActionCallout;
            window.updateAssetCalloutPosition = updateAssetCalloutPosition;

            // Live Scroll & Resize listener in capture phase so scrolling any table/div container syncs popover position!
            window.addEventListener('scroll', updateAssetCalloutPosition, { capture: true, passive: true });
            window.addEventListener('resize', updateAssetCalloutPosition, { passive: true });

            document.addEventListener('click', function (e) {
                const callout = cachedCalloutEl || document.getElementById('assetActionCallout');
                if (!callout || callout.style.display === 'none') return;
                if (!callout.contains(e.target) && !e.target.closest('.btn-asset-action-popover') && !e.target.closest('#assetTableBody tr')) {
                    closeAssetActionCallout();
                }
            });

            function initBiayaView(costs) {
                if (!costs) return;

                // Format currency
                const fmt = (num) => 'Rp ' + (num / 1000000).toFixed(0) + ' Jt';

                let totalB = costs.budget.reduce((a, b) => a + b, 0);
                let totalA = costs.actual.reduce((a, b) => a + b, 0);

                document.getElementById('kpiBudget').innerText = fmt(totalB);
                document.getElementById('kpiActual').innerText = fmt(totalA);

                // Chart
                const maxVal = Math.max(...costs.budget, ...costs.actual);
                let chartHtml = '';
                let labelHtml = '';

                for (let i = 0; i < costs.labels.length; i++) {
                    const bH = (costs.budget[i] / maxVal) * 100;
                    const aH = (costs.actual[i] / maxVal) * 100;

                    chartHtml += `
                    <div style="display:flex; flex-direction:column; justify-content:flex-end; align-items:center; width:30px; margin:0 5px;">
                        <div style="display:flex; gap:2px; align-items:flex-end; height:200px;">
                            <div style="width:12px; height:${bH}%; background:var(--primary); border-radius:2px 2px 0 0;" title="Budget: ${fmt(costs.budget[i])}"></div>
                            <div style="width:12px; height:${aH}%; background:var(--danger); border-radius:2px 2px 0 0;" title="Actual: ${fmt(costs.actual[i])}"></div>
                        </div>
                    </div>
                `;
                    labelHtml += `<div style="width:40px; text-align:center;">${costs.labels[i]}</div>`;
                }
                document.getElementById('chartContainer').innerHTML = chartHtml;
                document.getElementById('chartLabels').innerHTML = labelHtml;

                // Table
                document.getElementById('valTableBody').innerHTML = costs.unit_valuations.map(val => `
                <tr>
                    <td><strong>${escapeHtml(val.id)}</strong><br><small class="text-muted">${escapeHtml(val.unit)}</small></td>
                    <td>${escapeHtml(val.total_perbaikan)}</td>
                    <td>${escapeHtml(val.nilai_buku)}</td>
                    <td><span class="badge bg-operating">${escapeHtml(val.harga_pasaran)}</span></td>
                </tr>
            `).join('');
            }

            function addSpbItem(prefill = {}) {
                return window.SpareLogistics?.addDraftItem(prefill);
            }

            function submitSpb() {
                return window.SpareLogistics?.submitDraft();
            }

            function inspectTire(pos) {
                document.getElementById('selected-tire-label').innerText = pos;
                document.getElementById('selected-tire').value = pos;
                document.getElementById('tire-tread').value = '';
                document.getElementById('tire-pressure').value = '';
            }

            function saveTireInspection() {
                const pos = document.getElementById('selected-tire').value;
                const tread = parseFloat(document.getElementById('tire-tread').value);
                if (isNaN(tread)) { alert('Masukkan nilai tread depth'); return; }
                const el = document.getElementById('tire-' + pos.toLowerCase());
                if (el) {
                    el.classList.remove('warning', 'danger');
                    if (tread < 3.2) el.classList.add('danger');
                    else if (tread <= 8.5) el.classList.add('warning');
                }
                alert('Inspeksi ban posisi ' + pos + ' tersimpan.');
            }

            // ==========================================
            // COMPREHENSIVE FUEL MANAGEMENT ENGINE
            // ==========================================
            let globalFuelLogs = [];

            window.refreshFuelLogsFromServer = async function () {
                const fuelData = await fetchJsonSafely('api/fuel_logs.php');
                if (fuelData?.status !== 'success' || !Array.isArray(fuelData.data)) return;
                globalFuelLogs = fuelData.data.map(mapFuelApiRecord);
                renderFuelDashboard(globalFuelLogs);
            };

            let globalTanks = [];

            window.initFuelEngine = function (data) {
                if (!data) return;

                const unitSelect = document.getElementById('frmFuelUnitId');
                if (unitSelect && data.assets) {
                    unitSelect.innerHTML = data.assets.map(a => `<option value="${escapeHtml(a.id)}">${escapeHtml(a.id)} (${escapeHtml(a.category)} - ${escapeHtml(a.location)})</option>`).join('');
                    if (data.assets.length > 0) onFuelUnitSelectChange(data.assets[0].id);
                }

                renderFuelDashboard(globalFuelLogs);
                renderTankStockTable();
            };

            window.renderFuelDashboard = function (fuelLogs) {
                const today = new Date().toDateString();
                const todayLiters = fuelLogs.filter(l => l.timeStr.includes('Hari ini') || (new Date(l.timeStr).toDateString() === today)).reduce((acc, curr) => acc + (parseFloat(curr.liter) || 0), 0);
                const monthLiters = fuelLogs.reduce((acc, curr) => acc + (parseFloat(curr.liter) || 0), 0);
                const monthCostRp = monthLiters * 12000;
                const anomalyCount = fuelLogs.filter(l => l.anomalyStatus === 'RED' || l.anomalyStatus === 'ORANGE').length;
                const unverifiedCount = fuelLogs.filter(l => l.status === 'Diajukan').length;
                const totalStock = globalTanks.reduce((acc, curr) => acc + (parseFloat(curr.phySounding) || 0), 0);
                const p = document.getElementById('fuelFilterPeriode')?.value || 'ALL';
                const l = document.getElementById('fuelFilterLokasi')?.value || 'ALL';
                const k = document.getElementById('fuelFilterKategori')?.value || 'ALL';
                const s = document.getElementById('fuelFilterStatus')?.value || 'ALL';

                let filtered = fuelLogs;

                if (p === 'TODAY') filtered = filtered.filter(log => log.timeStr.includes('Hari ini') || (new Date(log.timeStr).toDateString() === today));
                if (l !== 'ALL') filtered = filtered.filter(log => log.location.toLowerCase().includes(l.toLowerCase()));
                if (k !== 'ALL') filtered = filtered.filter(log => log.category.toLowerCase().includes(k.toLowerCase()));
                if (s !== 'ALL') filtered = filtered.filter(log => log.anomalyStatus === s);

                document.getElementById('kpiFuelToday').textContent = Math.round(todayLiters).toLocaleString('id-ID') + ' L';
                document.getElementById('kpiFuelMonth').textContent = Math.round(monthLiters).toLocaleString('id-ID') + ' L';
                document.getElementById('kpiFuelCost').textContent = 'Rp ' + (monthCostRp / 1000000).toFixed(1) + ' Jt';
                document.getElementById('kpiFuelBoros').textContent = anomalyCount + ' Unit';
                document.getElementById('kpiFuelUnverified').textContent = unverifiedCount + ' Trx';
                document.getElementById('kpiFuelStock').textContent = Math.round(totalStock).toLocaleString('id-ID') + ' L';

                // Update Reconciliation Transparency Elements (Batch F6)
                const sourceEl = document.getElementById('lblFuelSourceRows');
                if (sourceEl) {
                    sourceEl.innerHTML = `Sumber: <strong>${fuelLogs.length} baris transaksi BBM</strong> di database (Filter aktif: <strong>${filtered.length} baris</strong> terpilih)`;
                }
                const qualityEl = document.getElementById('lblFuelDataQuality');
                if (qualityEl) {
                    if (fuelLogs.length === 0) {
                        qualityEl.innerHTML = '<span style="color:#b45309;"><i class="fa-solid fa-triangle-exclamation"></i> Tidak Ada Data Sumber</span>';
                    } else {
                        qualityEl.innerHTML = '<span style="color:#0f766e;"><i class="fa-solid fa-database"></i> Database Sinkron (100%)</span>';
                    }
                }

                renderFuelChart(filtered);
                renderFuelTopBoros(filtered);
                renderFuelTable(filtered);
            };

            window.resetFuelFilter = function () {
                const p = document.getElementById('fuelFilterPeriode');
                const l = document.getElementById('fuelFilterLokasi');
                const k = document.getElementById('fuelFilterKategori');
                const s = document.getElementById('fuelFilterStatus');
                if (p) p.value = 'ALL';
                if (l) l.value = 'ALL';
                if (k) k.value = 'ALL';
                if (s) s.value = 'ALL';
                if (typeof window.filterFuelDashboard === 'function') {
                    window.filterFuelDashboard();
                }
            };

            let fuelTrendChartInstance = null;
            window.renderFuelChart = function (logs) {
                const chartContainer = document.getElementById('fuelChartContainer');
                if (!chartContainer) return;
                if (typeof ApexCharts === 'undefined') {
                    chartContainer.innerHTML = '<div style="padding:20px; text-align:center; color:#64748b;">ApexCharts belum dimuat.</div>';
                    return;
                }

                if (fuelTrendChartInstance) {
                    fuelTrendChartInstance.destroy();
                    fuelTrendChartInstance = null;
                }

                chartContainer.innerHTML = '';
                chartContainer.style.display = 'block';
                chartContainer.style.height = '250px';
                chartContainer.style.padding = '0';

                const days = ['21 Jul', '22 Jul', '23 Jul', '24 Jul', '25 Jul', '26 Jul', '27 Jul'];
                const litersData = [1500, 1850, 1400, 2100, 1950, 2250, 2450];
                const costData = [18.0, 22.2, 16.8, 25.2, 23.4, 27.0, 29.4];

                const options = {
                    series: [{
                        name: 'Volume Konsumsi (Liter)',
                        type: 'column',
                        data: litersData
                    }, {
                        name: 'Estimasi Biaya (Jt Rp)',
                        type: 'line',
                        data: costData
                    }],
                    chart: {
                        height: 240,
                        type: 'line',
                        toolbar: { show: false },
                        fontFamily: 'Inter, system-ui, sans-serif'
                    },
                    stroke: {
                        width: [0, 3],
                        curve: 'smooth'
                    },
                    plotOptions: {
                        bar: {
                            columnWidth: '38%',
                            borderRadius: 6
                        }
                    },
                    colors: ['#0284c7', '#10b981'],
                    fill: {
                        opacity: [0.9, 1]
                    },
                    labels: days,
                    xaxis: {
                        type: 'category',
                        labels: {
                            style: { colors: '#64748b', fontSize: '11px', fontWeight: 600 }
                        }
                    },
                    yaxis: [{
                        title: { text: 'Volume (Liter)', style: { color: '#0284c7', fontWeight: 700, fontSize: '11px' } },
                        labels: {
                            style: { colors: '#64748b', fontSize: '11px' },
                            formatter: val => Math.round(val) + ' L'
                        }
                    }, {
                        opposite: true,
                        title: { text: 'Biaya (Jt Rp)', style: { color: '#10b981', fontWeight: 700, fontSize: '11px' } },
                        labels: {
                            style: { colors: '#64748b', fontSize: '11px' },
                            formatter: val => 'Rp ' + val.toFixed(1) + ' Jt'
                        }
                    }],
                    tooltip: {
                        shared: true,
                        intersect: false,
                        y: {
                            formatter: function (y, { seriesIndex }) {
                                if (typeof y !== "undefined") {
                                    return seriesIndex === 0 ? y.toLocaleString('id-ID') + " Liter" : "Rp " + y.toFixed(1) + " Jt";
                                }
                                return y;
                            }
                        }
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'right',
                        fontSize: '11px',
                        fontWeight: 600
                    }
                };

                fuelTrendChartInstance = new ApexCharts(chartContainer, options);
                fuelTrendChartInstance.render();
            };

            let fuelTopBorosChartInstance = null;
            window.renderFuelTopBoros = function (logs) {
                const container = document.getElementById('fuelTopBorosContainer');
                if (!container) return;
                if (typeof ApexCharts === 'undefined') {
                    container.innerHTML = '<div style="padding:20px; text-align:center; color:#64748b;">ApexCharts belum dimuat.</div>';
                    return;
                }

                if (fuelTopBorosChartInstance) {
                    fuelTopBorosChartInstance.destroy();
                    fuelTopBorosChartInstance = null;
                }

                container.innerHTML = '';
                container.style.display = 'block';
                container.style.height = '250px';

                const options = {
                    series: [{
                        name: 'Konsumsi Aktual Unit',
                        data: [19.0, 2.8, 18.0, 14.0]
                    }, {
                        name: 'Standar Baseline (LPH / KPL)',
                        data: [15.0, 3.5, 16.0, 13.0]
                    }],
                    chart: {
                        type: 'bar',
                        height: 240,
                        toolbar: { show: false },
                        fontFamily: 'Inter, system-ui, sans-serif'
                    },
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            barHeight: '55%',
                            borderRadius: 5,
                            dataLabels: { position: 'top' }
                        }
                    },
                    colors: ['#ef4444', '#0284c7'],
                    dataLabels: {
                        enabled: true,
                        offsetX: 24,
                        style: {
                            fontSize: '10px',
                            fontWeight: 700,
                            colors: ['#334155']
                        },
                        formatter: val => val + ' L'
                    },
                    stroke: {
                        show: true,
                        width: 1,
                        colors: ['transparent']
                    },
                    tooltip: {
                        shared: true,
                        intersect: false
                    },
                    xaxis: {
                        categories: ['EXC-210 (Pit)', 'DT-054 (Borrow Pit)', 'DZ-00002 (Yard Duri)', 'GRD-535 (Site Alpha)'],
                        labels: {
                            style: { colors: '#64748b', fontSize: '11px' }
                        }
                    },
                    yaxis: {
                        labels: {
                            style: { colors: '#0f172a', fontSize: '11px', fontWeight: 700 }
                        }
                    },
                    legend: {
                        position: 'top',
                        horizontalAlign: 'right',
                        fontSize: '11px',
                        fontWeight: 600
                    }
                };

                fuelTopBorosChartInstance = new ApexCharts(container, options);
                fuelTopBorosChartInstance.render();
            };

            window.renderFuelTable = function (logs) {
                const tbody = document.getElementById('fuelTableBody');
                if (!tbody) return;

                document.getElementById('fuelTrxCount').textContent = logs.length;

                if (logs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="12" style="text-align:center; padding:20px; color:var(--text-muted);">Tidak ada transaksi BBM ditemukan untuk kriteria filter ini.</td></tr>';
                    return;
                }

                tbody.innerHTML = logs.map(l => {
                    let badgeAnomaly = '';
                    if (l.anomalyStatus === 'RED') badgeAnomaly = '<span class="badge bg-breakdown">Merah (>20%)</span>';
                    else if (l.anomalyStatus === 'ORANGE') badgeAnomaly = '<span class="badge" style="background:#fd7e14;">Oranye (10-20%)</span>';
                    else if (l.anomalyStatus === 'YELLOW') badgeAnomaly = '<span class="badge bg-inspeksi">Kuning (5-10%)</span>';
                    else badgeAnomaly = '<span class="badge bg-operating">Hijau (Normal)</span>';

                    let badgeStatus = l.status === 'Terverifikasi' ? '<span class="badge bg-operating">Terverifikasi</span>' : '<span class="badge bg-draft">Diajukan</span>';

                    const unitShort = l.unitId.split(' ')[0];

                    return `
                    <tr>
                        <td><strong>${escapeHtml(l.trxId)}</strong><br><small class="text-muted">${escapeHtml(l.timeStr)}</small></td>
                        <td>
                            <a onclick="openAssetModal('${escapeHtml(unitShort)}', 'READY', '${escapeHtml(l.category)}', '${escapeHtml(l.location)}')" style="cursor:pointer; color:var(--primary); font-weight:bold; text-decoration:underline;">
                                ${escapeHtml(l.unitId)}
                            </a>
                        </td>
                        <td>${escapeHtml(l.category)}<br><small class="text-muted">${escapeHtml(l.location)}</small></td>
                        <td>${escapeHtml(l.fuelman)}<br><small class="text-muted">Op: ${escapeHtml(l.operator)}</small>${l.sourceReportNumber ? `<br><small class="text-muted">Ref: ${escapeHtml(l.sourceReportNumber)}</small>` : ''}</td>
                        <td>${l.meterPrev} &rarr; ${l.meterCurr} <small>(${l.meterDiff} ${l.meterType})</small></td>
                        <td><strong>${l.liter} L</strong></td>
                        <td>${l.rateActual} ${l.meterType === 'HM' ? 'L/HM' : 'Km/L'} <br><small class="text-muted">Std: ${l.targetRate}</small></td>
                        <td style="font-weight:bold; color:${l.deviationPct > 10 || l.deviationPct < -15 ? 'var(--danger)' : 'var(--success)'};">
                            ${l.deviationPct > 0 ? '+' : ''}${l.deviationPct}%
                        </td>
                        <td>Rp ${(l.costRp || 0).toLocaleString('id-ID')}</td>
                        <td>${badgeAnomaly}</td>
                        <td>${badgeStatus}</td>
                        <td>
                            <div style="display:flex; gap:4px;">
                                <button onclick="openAssetModal('${escapeHtml(unitShort)}', 'READY', '${escapeHtml(l.category)}', '${escapeHtml(l.location)}')" class="btn" style="padding:3px 6px; font-size:0.85rem; background:#f8f9fa; border:1px solid var(--border);" title="Detail 360&deg; Unit">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                ${l.anomalyStatus === 'RED' ? `
                                    <button onclick="openFuelWoModal('${escapeHtml(unitShort)}', 'Konsumsi BBM Anomali Merah (${l.rateActual} vs Std ${l.targetRate})')" class="btn btn-danger" style="padding:3px 6px; font-size:0.85rem;" title="Lapor WO Breakdown / Service">
                                        <i class="fa-solid fa-wrench"></i>
                                    </button>
                                ` : ''}
                                ${l.status === 'Diajukan' ? `
                                    <button onclick="verifyFuelLog('${l.trxId}')" class="btn btn-success" style="padding:3px 6px; font-size:0.85rem;" title="Verifikasi Transaksi Sah">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `;
                }).join('');
            };

            window.renderTankStockTable = function () {
                const tbody = document.getElementById('fuelStockTableBody');
                if (!tbody) return;

                tbody.innerHTML = globalTanks.map(t => {
                    let badgeDiff = t.diff < -50 ? '<span class="badge bg-breakdown">Warning (Losses)</span>' : '<span class="badge bg-operating">Aman</span>';
                    return `
                    <tr>
                        <td><strong>${escapeHtml(t.name)}</strong><br><small class="text-muted">${t.id}</small></td>
                        <td>${escapeHtml(t.location)}</td>
                        <td>${t.capacity.toLocaleString('id-ID')} L</td>
                        <td><strong>${t.sysBalance.toLocaleString('id-ID')} L</strong></td>
                        <td><strong>${t.phySounding.toLocaleString('id-ID')} L</strong></td>
                        <td style="font-weight:bold; color:${t.diff < 0 ? 'var(--danger)' : 'var(--success)'};">${t.diff} L</td>
                        <td>${badgeDiff}</td>
                        <td>${t.lastUpdate}</td>
                    </tr>
                `;
                }).join('');
            };

            window.switchFuelTab = function (tabName, el) {
                if (el) {
                    document.querySelectorAll('#fuelMainTabs .asset-tab').forEach(t => t.classList.remove('active'));
                    el.classList.add('active');
                }
                document.getElementById('fuelTabSecTrx').style.display = 'none';
                document.getElementById('fuelTabSecStock').style.display = 'none';
                document.getElementById('fuelTabSecBaseline').style.display = 'none';

                if (tabName === 'stock') document.getElementById('fuelTabSecStock').style.display = 'block';
                else if (tabName === 'baseline') document.getElementById('fuelTabSecBaseline').style.display = 'block';
                else document.getElementById('fuelTabSecTrx').style.display = 'block';
            };

            window.filterFuelDashboard = function () {
                const p = document.getElementById('fuelFilterPeriode').value;
                const l = document.getElementById('fuelFilterLokasi').value;
                const k = document.getElementById('fuelFilterKategori').value;
                const s = document.getElementById('fuelFilterStatus').value;

                let filtered = globalFuelLogs;

                if (p === 'TODAY') filtered = filtered.filter(log => log.timeStr.includes('Hari ini'));
                if (l !== 'ALL') filtered = filtered.filter(log => log.location.toLowerCase().includes(l.toLowerCase()));
                if (k !== 'ALL') filtered = filtered.filter(log => log.category.toLowerCase().includes(k.toLowerCase()));
                if (s !== 'ALL') filtered = filtered.filter(log => log.anomalyStatus === s);

                renderFuelDashboard(filtered);
            };

            window.filterFuelByAnomaly = function (status) {
                document.getElementById('fuelFilterStatus').value = status;
                filterFuelDashboard();
            };

            window.filterFuelByStatus = function (statusStr) {
                const filtered = globalFuelLogs.filter(l => l.status === statusStr);
                renderFuelDashboard(filtered);
            };

            window.searchFuelTable = function () {
                const q = document.getElementById('searchFuelInput').value.toLowerCase().trim();
                if (!q) {
                    renderFuelTable(globalFuelLogs);
                    return;
                }
                const filtered = globalFuelLogs.filter(l =>
                    String(l.trxId).toLowerCase().includes(q) ||
                    String(l.unitId).toLowerCase().includes(q) ||
                    String(l.operator).toLowerCase().includes(q) ||
                    String(l.location).toLowerCase().includes(q) ||
                    String(l.sourceReportNumber || '').toLowerCase().includes(q)
                );
                renderFuelTable(filtered);
            };

            window.openFuelInputModal = function () {
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const randNum = Math.floor(1000 + Math.random() * 9000);

                document.getElementById('frmFuelVoucher').value = `BBM-${year}${month}${day}-${randNum}`;
                document.getElementById('frmFuelDatetime').value = now.toISOString().slice(0, 16);
                document.getElementById('frmFuelMeterCurr').value = '';
                document.getElementById('frmFuelLiter').value = '';
                document.getElementById('frmFuelActualRate').value = '-';
                document.getElementById('frmFuelDeviation').value = '-';
                document.getElementById('frmFuelCost').value = 'Rp 0';
                document.getElementById('frmFuelNotes').value = '';
                document.getElementById('frmFuelAnomalyBox').style.display = 'none';

                openModal('modalFuelInput');
            };

            window.onFuelUnitSelectChange = function (unitId) {
                let meterPrev = 8450;
                let meterType = 'HM';

                if (unitId.toLowerCase().includes('dt') || unitId.toLowerCase().includes('truck')) {
                    meterType = 'KM';
                    meterPrev = 45200;
                } else if (unitId.toLowerCase().includes('grd') || unitId.toLowerCase().includes('grader')) {
                    meterType = 'HM';
                    meterPrev = 3120;
                } else if (unitId.toLowerCase().includes('dz') || unitId.toLowerCase().includes('dozer')) {
                    meterType = 'HM';
                    meterPrev = 5120;
                }

                document.getElementById('lblPrevMeter').textContent = `${meterType} Sebelumnya`;
                document.getElementById('lblCurrMeter').textContent = `${meterType} Sekarang *`;
                document.getElementById('frmFuelMeterPrev').value = meterPrev;

                calcFuelMetrics();
            };

            window.calcFuelMetrics = function () {
                const prev = parseFloat(document.getElementById('frmFuelMeterPrev').value) || 0;
                const curr = parseFloat(document.getElementById('frmFuelMeterCurr').value) || 0;
                const liter = parseFloat(document.getElementById('frmFuelLiter').value) || 0;
                const unitId = document.getElementById('frmFuelUnitId').value || '';
                const diff = curr - prev;

                const cost = liter * 12000;
                document.getElementById('frmFuelCost').value = 'Rp ' + cost.toLocaleString('id-ID');

                if (diff <= 0 || liter <= 0) {
                    document.getElementById('frmFuelActualRate').value = '-';
                    document.getElementById('frmFuelDeviation').value = '-';
                    document.getElementById('frmFuelAnomalyBox').style.display = 'none';
                    return;
                }

                let isKm = unitId.toLowerCase().includes('dt') || unitId.toLowerCase().includes('truck');
                let actualRate = 0, targetRate = 15.0;

                if (isKm) {
                    actualRate = parseFloat((diff / liter).toFixed(2));
                    targetRate = 3.5;
                } else {
                    actualRate = parseFloat((liter / diff).toFixed(2));
                    if (unitId.toLowerCase().includes('grd')) targetRate = 13.0;
                    else if (unitId.toLowerCase().includes('dz')) targetRate = 16.0;
                    else targetRate = 14.0;
                }

                let devPct = parseFloat((((actualRate - targetRate) / targetRate) * 100).toFixed(1));
                if (isKm) devPct = parseFloat((((targetRate - actualRate) / targetRate) * 100).toFixed(1));

                document.getElementById('frmFuelActualRate').value = `${actualRate} ${isKm ? 'Km/L' : 'L/HM'}`;
                document.getElementById('frmFuelDeviation').value = `${devPct > 0 ? '+' : ''}${devPct}% (Std: ${targetRate})`;

                const anomalyBox = document.getElementById('frmFuelAnomalyBox');
                const anomalyText = document.getElementById('frmFuelAnomalyText');

                if (devPct > 20 || (isKm && devPct > 20)) {
                    anomalyBox.style.display = 'block';
                    anomalyBox.style.background = '#f8d7da';
                    anomalyBox.style.borderColor = '#dc3545';
                    anomalyText.innerHTML = `ANOMALI MERAH: Konsumsi terdeteksi sangat boros (+${devPct}% dari standar ${targetRate})! Wajib isi catatan alasan.`;
                } else if (devPct > 10 || (isKm && devPct > 10)) {
                    anomalyBox.style.display = 'block';
                    anomalyBox.style.background = '#fff3bf';
                    anomalyBox.style.borderColor = '#f59f00';
                    anomalyText.innerHTML = `WARNING ORANYE: Konsumsi melebih standar (+${devPct}% dari standar ${targetRate}). Perhatikan beban kerja unit.`;
                } else {
                    anomalyBox.style.display = 'none';
                }
            };

            window.submitFuelForm = async function () {
                const voucher = document.getElementById('frmFuelVoucher').value;
                const unitId = document.getElementById('frmFuelUnitId').value;
                const loc = document.getElementById('frmFuelLocation').value;
                const fuelman = document.getElementById('frmFuelman').value;
                const operator = document.getElementById('frmFuelOperator').value;
                const prev = parseFloat(document.getElementById('frmFuelMeterPrev').value) || 0;
                const curr = parseFloat(document.getElementById('frmFuelMeterCurr').value) || 0;
                const liter = parseFloat(document.getElementById('frmFuelLiter').value) || 0;
                const notes = document.getElementById('frmFuelNotes').value;

                if (!curr || curr <= prev) return alert('Meter sekarang harus lebih besar dari meter sebelumnya!');
                if (!liter || liter <= 0) return alert('Jumlah liter BBM wajib diisi!');

                const diff = curr - prev;
                const isKm = unitId.toLowerCase().includes('dt') || unitId.toLowerCase().includes('truck');
                let actualRate = isKm ? parseFloat((diff / liter).toFixed(2)) : parseFloat((liter / diff).toFixed(2));
                let targetRate = isKm ? 3.5 : (unitId.toLowerCase().includes('grd') ? 13.0 : (unitId.toLowerCase().includes('dz') ? 16.0 : 14.0));
                let devPct = parseFloat((((actualRate - targetRate) / targetRate) * 100).toFixed(1));

                let anomalyStatus = 'GREEN';
                if (devPct > 20) anomalyStatus = 'RED';
                else if (devPct > 10) anomalyStatus = 'ORANGE';
                else if (devPct > 5) anomalyStatus = 'YELLOW';

                const payload = {
                    asset_id: unitId,
                    refuel_date: new Date().toISOString(),
                    flowmeter_start: prev,
                    flowmeter_end: curr,
                    liters_issued: liter,
                    current_hm_km: curr,
                    calculated_lph: actualRate,
                    baseline_lph: targetRate,
                    is_anomaly: anomalyStatus !== 'GREEN',
                    driver_name: operator,
                    notes: notes
                };

                try {
                    const response = await fetch('./api/fuel_logs.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const res = await response.json();
                    if (res.status === 'success') {
                        closeModal('modalFuelInput');
                        alert(`Transaksi pengisian BBM ${voucher} berhasil diajukan dan dicatat di database!`);
                        // Ideally, we fetch logs from the server again here.
                        // For now, we manually push to the local array to update the UI without full reload.
                        const newLog = {
                            trxId: voucher,
                            timeStr: 'Baru saja',
                            unitId: unitId,
                            category: isKm ? 'Dump Truck' : 'Excavator',
                            location: loc,
                            fuelman: fuelman,
                            operator: operator,
                            meterType: isKm ? 'KM' : 'HM',
                            meterPrev: prev,
                            meterCurr: curr,
                            meterDiff: diff,
                            liter: liter,
                            rateActual: actualRate,
                            targetRate: targetRate,
                            deviationPct: devPct,
                            costRp: liter * 12000,
                            anomalyStatus: anomalyStatus,
                            status: 'Diajukan',
                            notes: notes || 'Pengisian rutin site'
                        };
                        globalFuelLogs.unshift(newLog);
                        renderFuelDashboard(globalFuelLogs);
                    } else {
                        alert('Gagal menyimpan: ' + res.message);
                    }
                } catch (e) {
                    console.error(e);
                    alert('Gagal terhubung ke server.');
                }
            };

            window.verifyFuelLog = function (trxId) {
                const log = globalFuelLogs.find(l => l.trxId === trxId);
                if (log) {
                    log.status = 'Terverifikasi';
                    renderFuelDashboard(globalFuelLogs);
                    alert(`Transaksi ${trxId} telah diverifikasi secara sah!`);
                }
            };

            window.openFuelWoModal = function (assetId, issueText) {
                openNewWOModal(assetId);
                const issueInput = document.getElementById('nwo-issue');
                if (issueInput) issueInput.value = issueText;
            };

            window.openStockSoundingModal = function () {
                onTankSelectChange('TANK-01');
                document.getElementById('stkPhysicalSounding').value = '';
                document.getElementById('stkVariance').value = '-';
                document.getElementById('stkNotes').value = '';
                openModal('modalStockSounding');
            };

            window.onTankSelectChange = function (tankId) {
                const tank = globalTanks.find(t => t.id === tankId);
                if (tank) {
                    document.getElementById('stkSystemBalance').value = tank.sysBalance.toLocaleString('id-ID') + ' L';
                }
            };

            window.calcTankVariance = function () {
                const tankId = document.getElementById('stkTankId').value;
                const tank = globalTanks.find(t => t.id === tankId);
                const phy = parseFloat(document.getElementById('stkPhysicalSounding').value) || 0;
                if (!tank || !phy) {
                    document.getElementById('stkVariance').value = '-';
                    return;
                }
                const diff = phy - tank.sysBalance;
                document.getElementById('stkVariance').value = `${diff >= 0 ? '+' : ''}${diff} Liter (${diff < -50 ? 'Losses Warning' : 'Aman'})`;
            };

            window.submitTankSounding = function () {
                const tankId = document.getElementById('stkTankId').value;
                const phy = parseFloat(document.getElementById('stkPhysicalSounding').value);
                if (isNaN(phy)) return alert('Masukkan nilai sounding fisik yang valid!');

                const tank = globalTanks.find(t => t.id === tankId);
                if (tank) {
                    tank.phySounding = phy;
                    tank.diff = phy - tank.sysBalance;
                    tank.lastUpdate = 'Hari ini, ' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    tank.status = tank.diff < -50 ? 'Warning' : 'Aman';
                    renderTankStockTable();
                    closeModal('modalStockSounding');
                    alert(`Sounding fisik untuk ${tank.name} tersimpan. Selisih: ${tank.diff} Liter.`);
                }
            };

            // ==========================================
            // COMPREHENSIVE COST & VALUATION ENGINE
            // ==========================================
            let globalValuations = [
                {
                    id: 'Dozer 06',
                    name: 'Bulldozer Caterpillar D6R',
                    sn: 'S6X00348',
                    year: 2011,
                    repairCost: 421415277,
                    health: '<50%',
                    body: '>80%',
                    buyPrice: 600000000,
                    revenue: 1640000000,
                    buyYear: 2022,
                    depreciationPct: 40,
                    depreciationVal: 240000000,
                    marketRange: '450.000.000 - 520.000.000',
                    offeredPrice: 550000000,
                    netPriceBra: 450000000,
                    selisih: 100000000,
                    bookValue: 360000000
                },
                {
                    id: 'Dozer 04',
                    name: 'Bulldozer Komatsu D85ESS-2',
                    sn: 'J16298',
                    year: 2013,
                    repairCost: 403684851,
                    health: '<50%',
                    body: '>80%',
                    buyPrice: 1220000000,
                    revenue: 1185564516,
                    buyYear: 2023,
                    depreciationPct: 30,
                    depreciationVal: 366000000,
                    marketRange: '750.000.000 - 900.000.000',
                    offeredPrice: 950000000,
                    netPriceBra: 820000000,
                    selisih: 130000000,
                    bookValue: 854000000
                },
                {
                    id: 'Exca 16',
                    name: 'Excavator Caterpillar 305.5E2',
                    sn: 'WE217079',
                    year: 2023,
                    repairCost: 35606864,
                    health: '<50%',
                    body: '<80%',
                    buyPrice: 841380000,
                    revenue: 1165833333,
                    buyYear: 2023,
                    depreciationPct: 30,
                    depreciationVal: 252414000,
                    marketRange: '420.000.000 - 480.000.000',
                    offeredPrice: 520000000,
                    netPriceBra: 460000000,
                    selisih: 60000000,
                    bookValue: 588966000
                },
                {
                    id: 'Exca 17',
                    name: 'Excavator Caterpillar 305.5E2',
                    sn: 'WE217080',
                    year: 2023,
                    repairCost: 66808545,
                    health: '<50%',
                    body: '<80%',
                    buyPrice: 841380000,
                    revenue: 1800000000,
                    buyYear: 2023,
                    depreciationPct: 30,
                    depreciationVal: 252414000,
                    marketRange: '420.000.000 - 480.000.000',
                    offeredPrice: 510000000,
                    netPriceBra: 450000000,
                    selisih: 60000000,
                    bookValue: 588966000
                },
                {
                    id: 'Dozer 07',
                    name: 'Bulldozer Caterpillar D7G2',
                    sn: '7MB05410',
                    year: 2010,
                    repairCost: 286553522,
                    health: '<50%',
                    body: '<80%',
                    buyPrice: 800000000,
                    revenue: 1530000000,
                    buyYear: 2022,
                    depreciationPct: 40,
                    depreciationVal: 320000000,
                    marketRange: '420.000.000 - 500.000.000',
                    offeredPrice: 520000000,
                    netPriceBra: 450000000,
                    selisih: 70000000,
                    bookValue: 480000000
                },
                {
                    id: 'Dozer 01',
                    name: 'Bulldozer Caterpillar D6G',
                    sn: 'P6G00163',
                    year: 2006,
                    repairCost: 579012317,
                    health: '<50%',
                    body: '<80%',
                    buyPrice: 415000000,
                    revenue: 1395000000,
                    buyYear: 2023,
                    depreciationPct: 30,
                    depreciationVal: 124500000,
                    marketRange: '400.000.000 - 550.000.000',
                    offeredPrice: 500000000,
                    netPriceBra: 430000000,
                    selisih: 70000000,
                    bookValue: 290500000
                },
                {
                    id: 'Exca 04',
                    name: 'Excavator Caterpillar 320D',
                    sn: 'BZP03563',
                    year: 2012,
                    repairCost: 456208914,
                    health: '<50%',
                    body: '<80%',
                    buyPrice: 370000000,
                    revenue: 1755000000,
                    buyYear: 2021,
                    depreciationPct: 50,
                    depreciationVal: 185000000,
                    marketRange: '400.000.000 - 580.000.000',
                    offeredPrice: 520000000,
                    netPriceBra: 400000000,
                    selisih: 120000000,
                    bookValue: 185000000
                }
            ];

            let globalLpjReports = [
                { month: 'Mei 2025', budget: 330000000, cash: 393758107, debt: 0, total: 393758107, diff: -63758107, status: 'Over Budget' },
                { month: 'Juni 2025', budget: 330000000, cash: 553477901, debt: 45000000, total: 598477901, diff: -268477901, status: 'Over Budget' },
                { month: 'Juli 2025', budget: 430000000, cash: 542509893, debt: 32000000, total: 574509893, diff: -144509893, status: 'Over Budget' },
                { month: 'Agustus 2025', budget: 330000000, cash: 439682327, debt: 15000000, total: 454682327, diff: -124682327, status: 'Over Budget' },
                { month: 'September 2025', budget: 330000000, cash: 264006655, debt: 0, total: 264006655, diff: 65993345, status: 'Under Budget' },
                { month: 'Oktober 2025', budget: 330000000, cash: 321656974, debt: 0, total: 321656974, diff: 8343026, status: 'Under Budget' },
                { month: 'November 2025', budget: 330000000, cash: 429717728, debt: 54000000, total: 483717728, diff: -153717728, status: 'Over Budget' },
                { month: 'Desember 2025', budget: 550000000, cash: 449174956, debt: 0, total: 449174956, diff: 100825044, status: 'Under Budget' },
                { month: 'Januari 2026', budget: 550000000, cash: 358764378, debt: 25000000, total: 383764378, diff: 166235622, status: 'Under Budget' },
                { month: 'Februari 2026', budget: 550000000, cash: 582886025, debt: 35000000, total: 617886025, diff: -67886025, status: 'Over Budget' }
            ];

            let globalPos = [
                { poId: 'PO-DFT.01508', date: '03 Jun 2026', vendor: 'PT Trakindo Utama', desc: 'Bearing As, Bulk Seal, Dowel, Bolt Hex Excavator Mini', woId: 'WO-26-892', spbId: 'SPB-2026-06-015', subtotal: 77021550, ppn: 8472371, total: 85493921, status: 'Lunas' },
                { poId: 'PO-10969', date: '15 Mei 2026', vendor: 'PT United Tractors Tbk', desc: 'Main Hydraulic Pump Repair Kit PC200-8', woId: 'WO-26-885', spbId: 'SPB-2026-05-099', subtotal: 145000000, ppn: 15950000, total: 160950000, status: 'Lunas' },
                { poId: 'SPPU-001-02-26', date: '10 Feb 2026', vendor: 'Kas Operasional Zona 4', desc: 'Pengeluaran Uang Tanggap Darurat Repair Site', woId: 'WO-26-870', spbId: 'SPB-2026-02-004', subtotal: 3585000, ppn: 0, total: 3585000, status: 'Lunas' }
            ];

            let globalPmComparisons = [];

            window.initBiayaEngine = function (costsData) {
                renderCostDashboard();
            };
            window.initBiayaView = window.initBiayaEngine;

            window.renderCostDashboard = function () {
                renderCostChart();
                renderCostValuationTable(globalValuations);
                renderCostLpjTable(globalLpjReports);
                renderCostPoTable(globalPos);
                renderCostPmCompTable(globalPmComparisons);
            };

            window.renderCostChart = function () {
                const container = document.getElementById('costChartContainer');
                if (!container) return;

                const recentMonths = [
                    { month: 'Mei', budget: 330, actual: 393.7 },
                    { month: 'Jun', budget: 330, actual: 553.4 },
                    { month: 'Jul', budget: 430, actual: 542.5 },
                    { month: 'Agu', budget: 330, actual: 439.6 },
                    { month: 'Sep', budget: 330, actual: 264.0 },
                    { month: 'Okt', budget: 330, actual: 321.6 },
                    { month: 'Nov', budget: 330, actual: 429.7 },
                    { month: 'Des', budget: 550, actual: 449.1 }
                ];

                const maxVal = 600;
                const yTicks = [600, 450, 300, 150, 0];

                let html = `
                <!-- Y-Axis Ticks & Grid Lines -->
                <div class="chart-y-axis" style="position:absolute; left:40px; top:20px; bottom:30px; width:calc(100% - 55px); pointer-events:none; z-index:1;">
                    ${yTicks.map(t => {
                    const pct = 100 - (t / maxVal) * 100;
                    return `
                            <div style="position:absolute; top:${pct}%; left:0; width:100%; border-top:1px dashed #e2e8f0; display:flex; justify-content:flex-start; align-items:center;">
                                <span style="position:absolute; left:-40px; font-size:0.85rem; color:var(--text-muted); font-weight:600;">${t} Jt</span>
                            </div>
                        `;
                }).join('')}
                    <div style="position:absolute; top:-16px; left:-40px; font-size:0.85rem; font-weight:700; color:var(--text-muted); text-transform:uppercase;">
                        Biaya (Rp Jt)
                    </div>
                </div>

                <!-- Bars Container -->
                <div style="display:flex; height:100%; width:calc(100% - 40px); margin-left:40px; align-items:flex-end; gap:10px; position:relative; z-index:2; padding-top:20px; padding-bottom:25px;">
            `;

                html += recentMonths.map(m => {
                    const isOver = m.actual > m.budget;
                    const diff = (m.actual - m.budget).toFixed(1);
                    const diffPct = Math.abs((((m.actual - m.budget) / m.budget) * 100)).toFixed(1);
                    const pctB = Math.min(100, (m.budget / maxVal) * 100);
                    const pctA = Math.min(100, (m.actual / maxVal) * 100);

                    const diffText = isOver
                        ? `<strong style="color:#dc2626;">+Rp ${diff} Jt (+${diffPct}% Over)</strong>`
                        : `<strong style="color:#16a34a;">-Rp ${Math.abs(diff).toFixed(1)} Jt (-${diffPct}% Hemat)</strong>`;

                    const statusBadge = isOver
                        ? `<span style="background:#fee2e2; color:#dc2626; padding:2px 6px; border-radius:4px; font-weight:700; font-size:0.85rem;">OVER BUDGET</span>`
                        : `<span style="background:#dcfce7; color:#16a34a; padding:2px 6px; border-radius:4px; font-weight:700; font-size:0.85rem;">UNDER BUDGET (HEMAT)</span>`;

                    return `
                    <div class="cost-chart-bar-group" style="flex:1; display:flex; flex-direction:column; align-items:center; height:100%; justify-content:flex-end; position:relative; cursor:pointer;">
                        <!-- Rich Tooltip Card -->
                        <div class="cost-chart-tooltip">
                            <div style="font-weight:700; border-bottom:1px solid #e2e8f0; padding-bottom:4px; margin-bottom:6px; font-size:0.8rem; color:#1e293b;">
                                📅 Bulan ${m.month} 2026
                            </div>
                            <div style="display:flex; justify-content:space-between; gap:12px; margin-bottom:3px;">
                                <span><span style="color:#2563eb;">&bull; </span> Budget Planning:</span>
                                <strong>Rp ${m.budget} Jt</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; gap:12px; margin-bottom:6px;">
                                <span><span style="color:${isOver ? '#dc2626' : '#16a34a'};">&bull; </span> Realisasi Actual:</span>
                                <strong style="color:${isOver ? '#dc2626' : '#16a34a'};">Rp ${m.actual} Jt</strong>
                            </div>
                            <div style="border-top:1px dashed #cbd5e1; padding-top:4px; font-size:0.85rem; display:flex; justify-content:space-between; align-items:center; gap:8px;">
                                <span>Variance (Selisih):</span>
                                ${diffText}
                            </div>
                            <div style="margin-top:5px; text-align:right;">
                                ${statusBadge}
                            </div>
                        </div>

                        <!-- Bar Pair -->
                        <div style="display:flex; gap:4px; align-items:flex-end; width:100%; height:100%; justify-content:center; position:relative;">
                            <!-- Budget Bar -->
                            <div class="cost-bar" style="width:42%; background:#2563eb; height:${pctB}%; border-radius:4px 4px 0 0; position:relative;" title="${m.month}: Budget Rp ${m.budget} Jt">
                                <span class="bar-value-label">${m.budget}</span>
                            </div>
                            <!-- Actual Bar -->
                            <div class="cost-bar" style="width:42%; background:${isOver ? '#dc2626' : '#16a34a'}; height:${pctA}%; border-radius:4px 4px 0 0; position:relative;" title="${m.month}: Actual Rp ${m.actual} Jt (${isOver ? 'Over' : 'Hemat'})">
                                <span class="bar-value-label" style="color:${isOver ? '#b91c1c' : '#15803d'};">${m.actual}</span>
                            </div>
                        </div>

                        <!-- Month Label -->
                        <div style="font-size:0.78rem; font-weight:700; color:var(--text-dark); margin-top:8px;">${m.month}</div>
                    </div>
                `;
                }).join('') + `</div>`;

                container.innerHTML = html;
            };

            window.renderCostValuationTable = function (items) {
                const tbody = document.getElementById('costValuationTableBody');
                if (!tbody) return;

                if (items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="12" style="text-align:center; padding:20px; color:var(--text-muted);">Tidak ada data valuasi aset ditemukan.</td></tr>';
                    return;
                }

                tbody.innerHTML = items.map(v => {
                    const shortId = v.id.replace(/\s+/g, '');
                    return `
                    <tr>
                        <td>
                            <a onclick="openAssetModal('${escapeHtml(shortId)}', 'READY', 'Bulldozer', 'Yard Duri')" style="cursor:pointer; color:var(--primary); font-weight:bold; text-decoration:underline;">
                                ${escapeHtml(v.id)}
                            </a>
                            <br><small class="text-muted">${escapeHtml(v.name)}</small>
                        </td>
                        <td>${escapeHtml(v.sn)}<br><small class="text-muted">Tahun: ${v.year}</small></td>
                        <td style="font-weight:bold; color:var(--danger);">Rp ${(v.repairCost).toLocaleString('id-ID')}</td>
                        <td>
                            <span class="badge bg-breakdown">Health: ${v.health}</span>
                            <br><span class="badge bg-operating" style="margin-top:2px;">Body: ${v.body}</span>
                        </td>
                        <td>Rp ${(v.buyPrice).toLocaleString('id-ID')}</td>
                        <td>Rp ${(v.revenue).toLocaleString('id-ID')}</td>
                        <td style="font-weight:bold; color:var(--primary);">Rp ${(v.bookValue).toLocaleString('id-ID')}</td>
                        <td><span class="badge bg-operating" style="font-size:0.78rem;">${v.marketRange}</span></td>
                        <td><strong style="color:var(--info);">${(v.offeredPrice / 1000000).toFixed(0)} Jt</strong></td>
                        <td><strong>${(v.netPriceBra / 1000000).toFixed(0)} Jt</strong></td>
                        <td style="font-weight:bold; color:var(--success);">+${(v.selisih / 1000000).toFixed(0)} Jt</td>
                        <td>
                            <button onclick="openAssetModal('${escapeHtml(shortId)}', 'READY', 'Bulldozer', 'Yard Duri')" class="btn" style="padding:3px 6px; font-size:0.85rem; background:#f8f9fa; border:1px solid var(--border);" title="Lihat Detail 360&deg; Aset">
                                <i class="fa-solid fa-eye"></i> Detail 360&deg;
                            </button>
                        </td>
                    </tr>
                `;
                }).join('');
            };

            window.renderCostLpjTable = function (lpjList) {
                const tbody = document.getElementById('costLpjTableBody');
                if (!tbody) return;

                tbody.innerHTML = lpjList.map(l => {
                    const badgeClass = l.diff < 0 ? 'bg-breakdown' : 'bg-operating';
                    return `
                    <tr>
                        <td><strong>${escapeHtml(l.month)}</strong></td>
                        <td>Rp ${(l.budget).toLocaleString('id-ID')}</td>
                        <td>Rp ${(l.cash).toLocaleString('id-ID')}</td>
                        <td>Rp ${(l.debt).toLocaleString('id-ID')}</td>
                        <td><strong>Rp ${(l.total).toLocaleString('id-ID')}</strong></td>
                        <td style="font-weight:bold; color:${l.diff < 0 ? 'var(--danger)' : 'var(--success)'};">
                            ${l.diff > 0 ? '+' : ''}${(l.diff).toLocaleString('id-ID')}
                        </td>
                        <td><span class="badge ${badgeClass}">${l.status}</span></td>
                    </tr>
                `;
                }).join('');
            };

            window.renderCostPoTable = function (poList) {
                const tbody = document.getElementById('costPoTableBody');
                if (!tbody) return;

                tbody.innerHTML = poList.map(p => `
                <tr>
                    <td><strong>${escapeHtml(p.poId)}</strong><br><small class="text-muted">${p.date}</small></td>
                    <td>${escapeHtml(p.vendor)}</td>
                    <td>${escapeHtml(p.desc)}</td>
                    <td>
                        <span class="badge bg-operating">${p.woId}</span>
                        <br><small class="text-muted">${p.spbId}</small>
                    </td>
                    <td>Rp ${(p.subtotal).toLocaleString('id-ID')}</td>
                    <td>Rp ${(p.ppn).toLocaleString('id-ID')}</td>
                    <td><strong>Rp ${(p.total).toLocaleString('id-ID')}</strong></td>
                    <td><span class="badge bg-operating">${p.status}</span></td>
                    <td>
                        <button onclick="showView('logistics')" class="btn" style="padding:3px 6px; font-size:0.85rem; background:#e9ecef;" title="Cek SPB / Stok Logistik">
                            <i class="fa-solid fa-boxes-stacked"></i> Logistik
                        </button>
                    </td>
                </tr>
            `).join('');
            };

            const pmPartsDictionary = {
                "SD 01": { estCost: 2000000, parts: "Track Roller 203-30-00140" },
                "SD 02": { estCost: 2000000, parts: "Track Link Assy 203-32-00201" },
                "SD 03": { estCost: 2000000, parts: "Element Air Cleaner InYN11P01013P1" },
                "SD 05": { estCost: 2000000, parts: "Element Air Cleaner OutYN11P00001S002" },
                "SD 06": { estCost: 2000000, parts: "Element2446U287S4" },
                "PF 01": { estCost: 2000000, parts: "Accu / Batery N - 120 (24 Volt)" }
            };

            const getPmDataForUnit = (unitCode) => {
                if (pmPartsDictionary[unitCode]) return pmPartsDictionary[unitCode];
                // Fallbacks for other units based on prefixes from spreadsheet
                if (unitCode.startsWith('SD') || unitCode.startsWith('PF')) return { estCost: 2000000, parts: "Filter Set & Oli 15w40 (Standard PM)" };
                if (unitCode.startsWith('GR') || unitCode.startsWith('Dozer')) return { estCost: 5000000, parts: "Filter Set & Oli Transmisi (Standard PM)" };
                if (unitCode.startsWith('Exca')) return { estCost: 3000000, parts: "Filter Set, Oli Hidrolik & Oli Mesin (Standard PM)" };
                if (unitCode.startsWith('DT')) return { estCost: 4000000, parts: "Oli Mesin, Filter Oli, Filter Solar (Standard PM)" };
                return { estCost: 2000000, parts: "General Parts Consumable (Oli 15w40, dsb)" };
            };

            window.openNewPMModal = function () {
                const select = document.getElementById('npm-id');
                // Populasikan daftar aset aktif
                select.innerHTML = '<option value="" disabled selected>-- Pilih Unit --</option>' +
                    globalData.assets.map(a => `<option value="${a.id}">${a.id} (${a.category}) - ${a.name}</option>`).join('');

                document.getElementById('npm-schedule').value = '';
                document.getElementById('npm-parts').value = '';
                document.getElementById('npm-cost').value = '';
                document.getElementById('npm-loc').value = '';

                openModal('modalNewPM');
            };

            window.autoFillPmParts = function () {
                const unitId = document.getElementById('npm-id').value;
                const pmData = getPmDataForUnit(unitId);
                document.getElementById('npm-cost').value = pmData.estCost;
                document.getElementById('npm-parts').value = pmData.parts;
            };

            window.submitNewPM = async function () {
                const unitId = document.getElementById('npm-id').value;
                const schedule = document.getElementById('npm-schedule').value;
                const parts = document.getElementById('npm-parts').value;
                const cost = parseInt(document.getElementById('npm-cost').value) || 0;
                const loc = document.getElementById('npm-loc').value;

                if (!unitId || !schedule) {
                    alert('Unit dan Jadwal Service harus diisi!');
                    return;
                }

                const asset = globalData.assets.find(a => a.id === unitId);
                const modelName = asset ? asset.name : 'Unknown Model';

                const intervalVal = parseInt(schedule.replace(/\D/g, '')) || 250;

                const payload = {
                    asset_id: unitId,
                    interval_hm: intervalVal,
                    current_smr: 0,
                    target_due_hm: intervalVal,
                    planner_note: `Draft PM: Butuh parts (${parts}). Estimasi biaya: Rp ${cost}. Lokasi: ${loc}`
                };

                try {
                    const response = await fetch('./api/pm_plans.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const res = await response.json();
                    if (res.status === 'success') {
                        // Masukkan ke globalPmComparisons
                        globalPmComparisons.unshift({
                            unitId: unitId,
                            model: modelName,
                            schedule: schedule,
                            location: loc || 'Workshop',
                            planCost: cost,
                            actCost: 0, // Belum direalisasi
                            diff: cost,
                            notes: `Draft PM: Butuh parts (${parts})`
                        });

                        renderCostPmCompTable(globalPmComparisons);
                        closeModal('modalNewPM');
                        alert('Rencana Service PM Berhasil Ditambahkan ke Database!');
                    } else {
                        alert('Gagal menyimpan: ' + res.message);
                    }
                } catch (e) {
                    console.error(e);
                    alert('Gagal terhubung ke server.');
                }
            };

            window.renderCostPmCompTable = function (pmList) {
                const tbody = document.getElementById('costPmCompTableBody');
                if (!tbody) return;

                tbody.innerHTML = pmList.map(p => `
                <tr>
                    <td><strong>${escapeHtml(p.unitId)}</strong><br><small class="text-muted">${escapeHtml(p.model)}</small></td>
                    <td><span class="badge bg-operating">${escapeHtml(p.schedule)}</span></td>
                    <td>${escapeHtml(p.location)}</td>
                    <td>Rp ${(p.planCost).toLocaleString('id-ID')}</td>
                    <td><strong>Rp ${(p.actCost).toLocaleString('id-ID')}</strong></td>
                    <td style="font-weight:bold; color:${p.diff < 0 ? 'var(--danger)' : 'var(--success)'};">
                        ${p.diff > 0 ? '+' : ''}${(p.diff).toLocaleString('id-ID')}
                    </td>
                    <td>${escapeHtml(p.notes)}</td>
                </tr>
            `).join('');
            };

            window.switchCostTab = function (tabName, el) {
                if (el) {
                    document.querySelectorAll('#costMainTabs .asset-tab').forEach(t => t.classList.remove('active'));
                    el.classList.add('active');
                }
                document.getElementById('costTabSecValuation').style.display = 'none';
                document.getElementById('costTabSecLpj').style.display = 'none';
                document.getElementById('costTabSecPo').style.display = 'none';
                document.getElementById('costTabSecPmComp').style.display = 'none';

                if (tabName === 'lpj') document.getElementById('costTabSecLpj').style.display = 'block';
                else if (tabName === 'po') {
                    document.getElementById('costTabSecPo').style.display = 'block';
                    if (typeof window.loadCostDashboard === 'function') window.loadCostDashboard();
                }
                else if (tabName === 'pmcomp') document.getElementById('costTabSecPmComp').style.display = 'block';
                else document.getElementById('costTabSecValuation').style.display = 'block';
            };

            window.filterCostDashboard = function () {
                const yr = document.getElementById('costFilterTahun').value;
                const loc = document.getElementById('costFilterLokasi').value;

                let filteredValuations = globalValuations;
                if (loc !== 'ALL') {
                    filteredValuations = filteredValuations.filter(v => v.name.toLowerCase().includes(loc.toLowerCase()) || loc === 'Duri');
                }
                renderCostValuationTable(filteredValuations);
            };

            window.searchCostValuationTable = function () {
                const q = document.getElementById('searchCostValuationInput').value.toLowerCase().trim();
                if (!q) {
                    renderCostValuationTable(globalValuations);
                    return;
                }
                const filtered = globalValuations.filter(v =>
                    v.id.toLowerCase().includes(q) ||
                    v.name.toLowerCase().includes(q) ||
                    v.sn.toLowerCase().includes(q)
                );
                renderCostValuationTable(filtered);
            };

            // =========================================================================
            // REVISI 9: PENCATATAN BIAYA & TRANSAKSI (BR-09A) & PO/SPPU DECISION (BR-09B)
            // =========================================================================

            window.openCostInputModal = function (defaultAssetId = '', defaultWoId = '') {
                const selectAsset = document.getElementById('costInputAssetId');
                if (selectAsset) {
                    const allAssets = typeof getAllAssets === 'function' ? getAllAssets() : (typeof globalData !== 'undefined' && Array.isArray(globalData?.assets) ? globalData.assets : []);
                    selectAsset.innerHTML = '<option value="">-- Pilih Unit Alat Berat --</option>' + 
                        allAssets.map(a => {
                            const code = a.asset_code || a.id || a.asset_id;
                            const model = a.make_model || a.model || '';
                            const sn = a.serial_number || '';
                            return `<option value="${escapeHtml(code)}">${escapeHtml(code)} - ${escapeHtml(model)} ${sn ? '(SN: ' + escapeHtml(sn) + ')' : ''}</option>`;
                        }).join('');

                    if (defaultAssetId) {
                        selectAsset.value = defaultAssetId;
                    }
                }

                const woInput = document.getElementById('costInputWoId');
                if (woInput) woInput.value = defaultWoId || '';

                const dateInput = document.getElementById('costInputDate');
                if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];

                const amountInput = document.getElementById('costInputAmount');
                if (amountInput) amountInput.value = '';

                const refInput = document.getElementById('costInputRef');
                if (refInput) refInput.value = '';

                const vendorInput = document.getElementById('costInputVendor');
                if (vendorInput) vendorInput.value = '';

                const descInput = document.getElementById('costInputDesc');
                if (descInput) descInput.value = defaultWoId ? `Biaya perbaikan tiket ${defaultWoId}` : '';

                openModal('modalCostInput');
            };

            window.submitCostTransaction = async function (e) {
                if (e) e.preventDefault();
                const btn = document.getElementById('btnSubmitCostTransaction');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';
                }

                const assetId = document.getElementById('costInputAssetId')?.value || '';
                const woId = document.getElementById('costInputWoId')?.value || '';
                const category = document.getElementById('costInputCategory')?.value || 'Maintenance';
                const date = document.getElementById('costInputDate')?.value || '';
                const amount = parseFloat(document.getElementById('costInputAmount')?.value || 0);
                const ref = document.getElementById('costInputRef')?.value || '';
                const vendor = document.getElementById('costInputVendor')?.value || '';
                const desc = document.getElementById('costInputDesc')?.value || '';

                const payload = {
                    asset_id: assetId,
                    wo_id: woId || null,
                    cost_category: category,
                    transaction_date: date,
                    amount: amount,
                    reference_no: ref || null,
                    vendor_name: vendor || null,
                    description: desc,
                    source_type: 'MANUAL'
                };

                try {
                    const response = await fetch('api/costs.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const result = await response.json();
                    if (!response.ok || result.status !== 'success') {
                        throw new Error(result.message || 'Gagal menyimpan transaksi biaya.');
                    }

                    closeModal('modalCostInput');
                    alert(`Transaksi biaya berhasil dicatat! Kode: ${result.data?.costCode || ''}`);

                    // Refresh table & WO modal if opened
                    window.loadCostDashboard();
                    if (woId && typeof loadWoCosts === 'function') {
                        loadWoCosts(woId, assetId);
                    }
                } catch (err) {
                    alert('Gagal mencatat transaksi biaya: ' + err.message);
                } finally {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Simpan Transaksi Biaya';
                    }
                }
            };

            window.loadCostDashboard = async function () {
                const tbody = document.getElementById('costPoTableBody');
                if (!tbody) return;

                const yr = document.getElementById('costFilterTahun')?.value || 'ALL';
                const loc = document.getElementById('costFilterLokasi')?.value || 'ALL';
                const cat = document.getElementById('costFilterKategori')?.value || 'ALL';

                let url = 'api/costs.php?1=1';
                if (cat !== 'ALL') url += `&cost_category=${encodeURIComponent(cat)}`;

                try {
                    const response = await fetch(url, { headers: { Accept: 'application/json' }, cache: 'no-store' });
                    const result = await response.json();
                    if (response.ok && result.status === 'success' && result.data) {
                        const transactions = result.data.transactions || [];
                        const summary = result.data.summary || {};

                        // Update KPI elements if present
                        const actualEl = document.getElementById('kpiCostActual');
                        if (actualEl && summary.formattedTotalActive) {
                            actualEl.textContent = summary.formattedTotalActive;
                        }

                        // Dynamic Variance Calculation from real total (Batch F6)
                        const budgetNominal = 2960000000; // Rp 2.960 Jt
                        const devEl = document.getElementById('kpiCostDev');
                        if (devEl && summary.totalActiveAmount !== undefined) {
                            const diffPct = ((summary.totalActiveAmount - budgetNominal) / budgetNominal) * 100;
                            const sign = diffPct >= 0 ? '+' : '';
                            devEl.textContent = `${sign}${diffPct.toFixed(1)}% (${diffPct > 0 ? 'Over' : 'Under'})`;
                            devEl.style.color = diffPct > 0 ? 'var(--danger)' : 'var(--success)';
                        }

                        // Update Reconciliation Transparency Elements (Batch F6)
                        const sourceCostEl = document.getElementById('lblCostSourceRows');
                        if (sourceCostEl) {
                            sourceCostEl.innerHTML = `Sumber Data: <strong>${summary.activeCount || 0} transaksi aktif</strong> (${summary.formattedTotalActive || 'Rp 0'}), <strong>${summary.reversedCount || 0} dibatalkan (void)</strong> (Total ${transactions.length} baris dimuat)`;
                        }
                        const qualityCostEl = document.getElementById('lblCostDataQuality');
                        if (qualityCostEl) {
                            qualityCostEl.innerHTML = '<span style="color:#0f766e;"><i class="fa-solid fa-database"></i> Buku Kas Sinkron</span>';
                        }

                        if (transactions.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:20px; color:var(--text-muted);">Belum ada data transaksi biaya perbaikan.</td></tr>';
                            return;
                        }

                        tbody.innerHTML = transactions.map(t => {
                            const isReversed = t.isReversed;
                            const catColors = {
                                'Sparepart': '#3b82f6',
                                'Maintenance': '#10b981',
                                'External': '#f59e0b',
                                'Capex': '#8b5cf6',
                                'Fuel': '#06b6d4',
                                'Other': '#64748b'
                            };
                            const catColor = catColors[t.category] || '#64748b';

                            const woCell = t.woId
                                ? `<button type="button" class="wo-id-link" onclick="openWoDetailModal('${escapeHtml(t.woId)}', '${escapeHtml(t.assetId)}')"><i class="fa-solid fa-screwdriver-wrench"></i> ${escapeHtml(t.woId)}</button>`
                                : `<span class="text-muted" style="font-size:0.8rem;">Non-WO</span>`;

                            const assetCell = `<strong>${escapeHtml(t.asset_code || t.assetId)}</strong> <small style="display:block; color:var(--text-muted);">${escapeHtml(t.make_model || '')}</small>`;

                            const statusBadge = isReversed
                                ? `<span class="badge" style="background:#fee2e2; color:#b91c1c;"><i class="fa-solid fa-ban"></i> Dibatalkan</span>`
                                : `<span class="badge" style="background:#dcfce7; color:#15803d;"><i class="fa-solid fa-check"></i> Aktif</span>`;

                            const actionBtn = isReversed
                                ? `<small class="text-muted" title="${escapeHtml(t.reversalReason || '')}">Audit: ${escapeHtml(t.reversalReason || 'Dibatalkan')}</small>`
                                : `<button type="button" class="btn btn-sm btn-danger" style="padding:2px 8px; font-size:0.75rem;" onclick="window.reverseCostTransaction(${t.transactionId}, '${escapeHtml(t.costCode)}')"><i class="fa-solid fa-ban"></i> Void</button>`;

                            return `
                                <tr style="${isReversed ? 'background:#f8fafc; opacity:0.75;' : ''}">
                                    <td>
                                        <strong style="color:var(--primary);">${escapeHtml(t.costCode)}</strong>
                                        <small style="display:block; color:var(--text-muted);">${escapeHtml(t.date)}</small>
                                    </td>
                                    <td>${escapeHtml(t.vendor || '-')}</td>
                                    <td>
                                        <strong>${escapeHtml(t.description)}</strong>
                                        ${t.referenceNo ? `<small style="display:block; color:var(--primary);"><i class="fa-solid fa-file-lines"></i> Ref: ${escapeHtml(t.referenceNo)}</small>` : ''}
                                    </td>
                                    <td>${assetCell}<div style="margin-top:3px;">${woCell}</div></td>
                                    <td><span class="badge" style="background:${catColor}20; color:${catColor}; font-weight:600;">${escapeHtml(t.category)}</span></td>
                                    <td style="font-weight:700; ${isReversed ? 'text-decoration:line-through; color:#94a3b8;' : 'color:var(--danger);'}">${escapeHtml(t.formattedAmount)}</td>
                                    <td>${statusBadge}</td>
                                    <td>${actionBtn}</td>
                                </tr>
                            `;
                        }).join('');
                    }
                } catch (e) {
                    console.warn('Gagal memuat ledger biaya:', e);
                }
            };

            window.reverseCostTransaction = async function (transactionId, costCode) {
                const reason = prompt(`Masukkan alasan pembatalan transaksi ${costCode} (wajib untuk kepatuhan audit):`);
                if (!reason || reason.trim() === '') {
                    return;
                }

                try {
                    const response = await fetch('api/costs.php?action=reverse', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({ transaction_id: transactionId, reason: reason.trim() })
                    });
                    const result = await response.json();
                    if (!response.ok || result.status !== 'success') {
                        throw new Error(result.message || 'Gagal membatalkan transaksi.');
                    }

                    alert(`Transaksi ${costCode} berhasil dibatalkan. Riwayat audit tersimpan.`);
                    window.loadCostDashboard();
                } catch (err) {
                    alert('Gagal membatalkan transaksi: ' + err.message);
                }
            };

            window.exportCostReport = function () {
                window.open('api/costs.php', '_blank');
            };

            window.loadSpbApprovalInbox = async function () {
                const tbody = document.getElementById('approval-list');
                if (!tbody) return;
                tbody.innerHTML = '<tr><td colspan="5">Memuat SPB dari database...</td></tr>';
                try {
                    const response = await fetch('api/spb_workflow.php', { cache: 'no-store' });
                    const payload = await response.json();
                    if (!response.ok || payload.status !== 'success') throw new Error(payload.message || 'Gagal memuat approval.');
                    const pending = payload.data.filter(item => item.status === 'Submitted');
                    const poResponse = await fetch('api/purchase_order_approval.php', { cache: 'no-store' });
                    const poPayload = await poResponse.json();
                    if (!poResponse.ok || poPayload.status !== 'success') throw new Error(poPayload.message || 'Gagal memuat PO.');
                    const pendingPo = poPayload.data.filter(item => ['Submitted', 'Approved'].includes(item.status));
                    const spbRows = pending.map(item => `<tr>
                        <td>${escapeHtml(item.spb_id)}</td><td>SPB Spare Part</td>
                        <td>${escapeHtml(item.requester_name || 'Pengguna #' + item.requested_by)}</td>
                        <td>${Number(item.item_count)} item · ${Number(item.total_quantity)} total qty · WO ${escapeHtml(item.wo_id)}</td>
                        <td>${payload.canApprove ? `<button type="button" class="btn btn-success" data-spb-decision="approve" data-spb-id="${escapeHtml(item.spb_id)}">Setuju</button>
                        <button type="button" class="btn btn-danger" data-spb-decision="reject" data-spb-id="${escapeHtml(item.spb_id)}">Tolak</button>` : 'Hanya lihat'}</td></tr>`).join('');
                    const poRows = pendingPo.map(item => `<tr>
                        <td>${escapeHtml(item.ppb_id)}</td><td>PO / PPB</td>
                        <td>${escapeHtml(item.vendor || '-')}</td>
                        <td>${escapeHtml(item.status)} · SPB ${escapeHtml(item.spb_id)} · WO ${escapeHtml(item.wo_id)} · Rp ${Number(item.total_amount).toLocaleString('id-ID')}</td>
                        <td>${poPayload.canApprove ? (item.status === 'Submitted' ? `<button type="button" class="btn btn-success" data-po-decision="approve" data-po-id="${escapeHtml(item.ppb_id)}">Setuju</button>
                        <button type="button" class="btn btn-danger" data-po-decision="reject" data-po-id="${escapeHtml(item.ppb_id)}">Tolak</button>`
                        : `<button type="button" class="btn btn-danger" data-po-decision="cancel" data-po-id="${escapeHtml(item.ppb_id)}">Batalkan PO &amp; biaya</button>`) : 'Hanya lihat'}</td></tr>`).join('');
                    tbody.innerHTML = spbRows + poRows || '<tr><td colspan="5">Tidak ada SPB atau PO menunggu persetujuan.</td></tr>';
                } catch (error) {
                    tbody.innerHTML = `<tr><td colspan="5">${escapeHtml(error.message)} — data approval tidak tersedia.</td></tr>`;
                }
            };

            document.getElementById('approval-list')?.addEventListener('click', async event => {
                const poButton = event.target.closest('[data-po-decision]');
                if (poButton) {
                    const ppbId = poButton.dataset.poId;
                    const decision = poButton.dataset.poDecision;
                    let reason = '';
                    if (decision === 'reject' || decision === 'cancel') {
                        const entered = prompt(`Alasan ${decision === 'cancel' ? 'membatalkan' : 'menolak'} PO ${ppbId}:`);
                        if (entered === null) return;
                        reason = entered.trim();
                        if (!reason || reason.length > 500) return alert('Alasan penolakan wajib diisi (maksimal 500 karakter).');
                    } else if (!confirm(`Setujui PO ${ppbId} dan catat biaya pembelian?`)) return;
                    poButton.disabled = true;
                    try {
                        const response = await fetch('api/purchase_order_approval.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.APP_CSRF_TOKEN || '' },
                            body: JSON.stringify({ ppbId, decision, reason })
                        });
                        const result = await response.json();
                        if (!response.ok || result.status !== 'success') throw new Error(result.message || 'Keputusan PO gagal.');
                        await window.loadSpbApprovalInbox();
                    } catch (error) {
                        alert(error.message);
                        poButton.disabled = false;
                    }
                    return;
                }
                const button = event.target.closest('[data-spb-decision]');
                if (!button) return;
                const spbId = button.dataset.spbId;
                const decision = button.dataset.spbDecision;
                let reason = '';
                if (decision === 'reject') {
                    const entered = prompt(`Alasan menolak ${spbId}:`);
                    if (entered === null) return;
                    reason = entered.trim();
                    if (!reason || reason.length > 500) return alert('Alasan penolakan wajib diisi, maksimal 500 karakter.');
                } else if (!confirm(`Setujui SPB ${spbId}?`)) return;
                button.disabled = true;
                try {
                    const response = await fetch('api/spb_workflow.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': window.APP_CSRF_TOKEN || '' },
                        body: JSON.stringify({ spbId, decision, reason })
                    });
                    const payload = await response.json();
                    if (!response.ok || payload.status !== 'success') throw new Error(payload.message || 'Keputusan gagal disimpan.');
                    await window.loadSpbApprovalInbox();
                    alert(`SPB ${spbId} ${decision === 'approve' ? 'disetujui' : 'ditolak'} dan tercatat di database.`);
                } catch (error) {
                    alert(error.message);
                    button.disabled = false;
                }
            });

            // ============================================================================
            // PENGATURAN SYSTEM & RBAC 10 ROLES OVERRIDE ENGINE
            // ============================================================================
            const rbacModulesList = [
                '1. Exec. Dashboard', '2. Monitoring Unit', '3. Master Asset', '4. Inspeksi & P2H',
                '5. Work Order', '6. Preventive Maint.', '7. Part & Logistik', '8. Condition Maint.',
                '9. Fuel Management', '10. Produktivitas', '11. Biaya Armada', '12. People & KPI',
                '13. HSE & Incident', '14. Laporan & Form', '15. Approval Inbox', '16. Pengaturan System'
            ];

            const defaultRbacRolesMaster = [
                {
                    id: 'admin',
                    name: '1. Administrator',
                    badge: 'System Admin',
                    icon: 'fa-user-gear',
                    color: '#2563eb',
                    desc: 'Hak Akses Penuh Seluruh Modul & Konfigurasi Sistem Utama (Owner)',
                    permissions: ['R C U O', 'R C U O', 'R C U O', 'R C U O', 'R C U O', 'R C U O', 'R C U O', 'R C U O', 'R C U O', 'R C U O', 'R C U O', 'R C U O', 'R C U O', 'R C U O', 'R A O', 'R C U O']
                },
                {
                    id: 'eqmgr',
                    name: '2. Equipment Manager',
                    badge: 'Head of Equipment',
                    icon: 'fa-user-tie',
                    color: '#0d9488',
                    desc: 'Persetujuan Akhir Work Order, Budget Biaya, & Operasional Armada',
                    permissions: ['R U A', 'R U A', 'R U A', 'R U A', 'R C U A', 'R C U A', 'R A', 'R U A', 'R U A', 'R U A', 'R U A', 'R C U A', 'R C U A', 'R C U', 'R A', '-']
                },
                {
                    id: 'planner',
                    name: '3. Maintenance Planner',
                    badge: 'Planner & Scheduler',
                    icon: 'fa-calendar-check',
                    color: '#d97706',
                    desc: 'Perencanaan Jadwal PM, Work Order, & Kebutuhan Spareparts Field',
                    permissions: ['R U', 'R U', 'R U', 'R U', 'R C U A', 'R C U A', 'R C U', 'R C U', 'R U', 'R U', '-', 'R U', 'R', 'R C U', 'R', '-']
                },
                {
                    id: 'mekaniks',
                    name: '4. Mekanik Senior',
                    badge: 'Lead Technician',
                    icon: 'fa-screwdriver-wrench',
                    color: '#4f46e5',
                    desc: 'Eksekusi Work Order, Update Status Efektif Unit, & Inspeksi P2H',
                    permissions: ['R', 'R', 'R', 'R C', 'R C U', 'R', 'R C', 'R', 'R', 'R', '-', 'R', 'R', 'R', '-', '-']
                },
                {
                    id: 'mekanikj',
                    name: '5. Mekanik Junior / Helper',
                    badge: 'Field Helper',
                    icon: 'fa-wrench',
                    color: '#64748b',
                    desc: 'Input Hasil P2H Harian & Progres Fisik Pengerjaan Work Order',
                    permissions: ['R', 'R', 'R', 'R C', 'R U', 'R', '-', '-', '-', 'R', '-', 'R', '-', 'R', '-', '-']
                },
                {
                    id: 'welder',
                    name: '6. Welder / Fabrikator',
                    badge: 'Welding Specialist',
                    icon: 'fa-fire-burner',
                    color: '#ea580c',
                    desc: 'Perbaikan Structure Body, Bucket Excavator, & Fabrikasi Frame',
                    permissions: ['R', 'R', 'R', 'R C', 'R U', '-', '-', '-', '-', 'R', '-', 'R', '-', 'R', '-', '-']
                },
                {
                    id: 'safety',
                    name: '7. Inspector K3L / Safety',
                    badge: 'Safety Officer',
                    icon: 'fa-shield-halved',
                    color: '#16a34a',
                    desc: 'Audit K3L, Temuan Incident, Inspeksi Alat, & Sounding Tangki',
                    permissions: ['R', 'R U', 'R', 'R C U A', 'R C', '-', '-', 'R C U', 'R C U', 'R', '-', '-', 'R C U A', 'R C U', '-', '-']
                },
                {
                    id: 'logistics',
                    name: '8. Logistic Head',
                    badge: 'Warehouse Head',
                    icon: 'fa-boxes-stacked',
                    color: '#9333ea',
                    desc: 'Manajemen Stok Sparepart, PO Trakindo/UT, & Sounding BBM',
                    permissions: ['R', 'R', 'R', '-', 'R', 'R', 'R C U A', 'R', 'R C U', 'R', '-', '-', '-', 'R C U', 'R A', '-']
                },
                {
                    id: 'hrd',
                    name: '9. HRD Manager',
                    badge: 'Human Resources',
                    icon: 'fa-users-gear',
                    color: '#db2777',
                    desc: 'Manajemen Manpower, Absensi Mekanik, & Assessment KPI Staff',
                    permissions: ['R', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', 'R C U A', '-', 'R C U', 'R A', '-']
                },
                {
                    id: 'asset',
                    name: '10. Asset Manager',
                    badge: 'Asset Controller',
                    icon: 'fa-cubes',
                    color: '#0891b2',
                    desc: 'Valuasi Nilai Buku & Pasaran Unit, Cost Control, & Margin Sales',
                    permissions: ['R', 'R U A', 'R C U A', '-', 'R', '-', '-', 'R U', 'R', 'R U A', 'R C U A', 'R', 'R A', 'R C U', 'R A', '-']
                }
            ];

            // Helper to load persisted RBAC custom overrides from localStorage
            function loadSavedRbacData() {
                try {
                    const saved = localStorage.getItem('fleetmonitor_rbac_custom_matrix');
                    if (saved) {
                        const parsed = JSON.parse(saved);
                        if (Array.isArray(parsed) && parsed.length === defaultRbacRolesMaster.length) {
                            return parsed;
                        }
                    }
                } catch (e) {
                    console.warn('Failed to load saved RBAC matrix from localStorage', e);
                }
                return JSON.parse(JSON.stringify(defaultRbacRolesMaster));
            }

            // Active State Store (Support Override & Persistence)
            let activeRbacRolesData = loadSavedRbacData();
            let currentActiveRoleIndex = 1; // Default: Equipment Manager

            window.switchSettingsTab = function (tabName, el) {
                document.querySelectorAll('#view-settings .settings-nav-item').forEach(t => t.classList.remove('active'));
                if (el) {
                    el.classList.add('active');
                } else {
                    const navEl = document.querySelector(`#view-settings .settings-nav-item[onclick*="${tabName}"]`);
                    if (navEl) navEl.classList.add('active');
                }

                document.querySelectorAll('#view-settings .settings-panel').forEach(p => p.style.display = 'none');
                const target = document.getElementById('settings-' + tabName);
                if (target) target.style.display = 'block';

                if (tabName === 'rbac') {
                    window.renderRbacMatrix(currentActiveRoleIndex);
                } else if (tabName === 'threshold' || tabName === 'template' || tabName === 'security') {
                    if (typeof window.loadOperationalSettings === 'function') {
                        window.loadOperationalSettings();
                    }
                }
            };

            window.selectRbacRole = function (index) {
                currentActiveRoleIndex = index;
                window.renderRbacMatrix(index);
            };

            window.openRbacOverrideModal = function (roleIdx, modIdx) {
                if (window.FleetRBAC && !window.FleetRBAC.hasAccess('settings', 'O')) {
                    alert("Akses Ditolak: Hanya Administrator yang berhak mengubah matriks hak akses.");
                    return;
                }
                const role = activeRbacRolesData[roleIdx];
                const moduleName = rbacModulesList[modIdx];
                if (!role) return;

                document.getElementById('editRbacRoleIndex').value = roleIdx;
                document.getElementById('editRbacModuleIndex').value = modIdx;
                document.getElementById('editRbacRoleName').innerText = role.name;
                document.getElementById('editRbacModuleName').innerText = moduleName;

                const currentPerm = role.permissions[modIdx] || '-';
                document.getElementById('chkRbacR').checked = currentPerm.includes('R');
                document.getElementById('chkRbacC').checked = currentPerm.includes('C');
                document.getElementById('chkRbacU').checked = currentPerm.includes('U');
                document.getElementById('chkRbacA').checked = currentPerm.includes('A');
                document.getElementById('chkRbacO').checked = currentPerm.includes('O');

                openModal('modalEditRbacPermission');
            };

            window.saveRbacCellOverride = function () {
                if (window.FleetRBAC && !window.FleetRBAC.hasAccess('settings', 'O')) {
                    alert("Akses Ditolak: Hanya Administrator yang berhak menyimpan perubahan hak akses.");
                    return;
                }
                const roleIdx = parseInt(document.getElementById('editRbacRoleIndex').value);
                const modIdx = parseInt(document.getElementById('editRbacModuleIndex').value);
                const role = activeRbacRolesData[roleIdx];
                if (!role) return;

                const parts = [];
                if (document.getElementById('chkRbacR').checked) parts.push('R');
                if (document.getElementById('chkRbacC').checked) parts.push('C');
                if (document.getElementById('chkRbacU').checked) parts.push('U');
                if (document.getElementById('chkRbacA').checked) parts.push('A');
                if (document.getElementById('chkRbacO').checked) parts.push('O');

                const newPerm = parts.length > 0 ? parts.join(' ') : '-';
                role.permissions[modIdx] = newPerm;
                role.isCustomized = true;

                try {
                    localStorage.setItem('fleetmonitor_rbac_custom_matrix', JSON.stringify(activeRbacRolesData));
                } catch (e) {
                    console.error('Gagal menyimpan RBAC ke localStorage:', e);
                }

                closeModal('modalEditRbacPermission');
                window.renderRbacMatrix(roleIdx);
                if (window.FleetRBAC) window.FleetRBAC.applyUIPermissions();
                alert(`Hak akses [${role.name}] pada modul [${rbacModulesList[modIdx]}] berhasil di-override menjadi: ${newPerm}`);
            };

            window.resetRbacToDefault = function () {
                if (confirm('Apakah Anda yakin ingin mengembalikan seluruh hak akses 10 Roles ke konfigurasi standar default master?')) {
                    activeRbacRolesData = JSON.parse(JSON.stringify(defaultRbacRolesMaster));
                    try {
                        localStorage.removeItem('fleetmonitor_rbac_custom_matrix');
                    } catch (e) {}
                    window.renderRbacMatrix(currentActiveRoleIndex);
                    if (window.FleetRBAC) window.FleetRBAC.applyUIPermissions();
                    alert('Seluruh matriks hak akses berhasil dikembalikan ke standar default master.');
                }
            };

            window.renderRbacMatrix = function (highlightIndex = 1) {
                const filterContainer = document.getElementById('rbacRoleFilterButtons');
                const summaryBox = document.getElementById('rbacRoleSummaryBox');
                const tableEl = document.getElementById('rbacMatrixTable');
                if (!tableEl) return;

                // 1. Render Filter Buttons
                if (filterContainer) {
                    filterContainer.innerHTML = activeRbacRolesData.map((role, idx) => {
                        const isActive = idx === highlightIndex;
                        const hasCustom = role.isCustomized || JSON.stringify(role.permissions) !== JSON.stringify(defaultRbacRolesMaster[idx].permissions);
                        return `
                        <button type="button" class="btn btn-sm ${isActive ? 'btn-primary' : 'btn-secondary'}" 
                            style="font-size:0.85rem; padding:4px 10px; border-radius:20px; transition:all 0.2s ease; position:relative;" 
                            onclick="selectRbacRole(${idx})">
                            <i class="fa-solid ${role.icon}"></i> ${role.name}
                            ${hasCustom ? '<span style="display:inline-block; width:7px; height:7px; background:#f59e0b; border-radius:50%; margin-left:4px;" title="Customized Override"></span>' : ''}
                        </button>
                    `;
                    }).join('');
                }

                // 2. Render Selected Role Summary Card
                const activeRole = activeRbacRolesData[highlightIndex] || activeRbacRolesData[0];
                const defaultRole = defaultRbacRolesMaster[highlightIndex] || defaultRbacRolesMaster[0];
                const isRoleCustomized = activeRole.isCustomized || JSON.stringify(activeRole.permissions) !== JSON.stringify(defaultRole.permissions);
                const grantedCount = activeRole.permissions.filter(p => p !== '-').length;

                if (summaryBox) {
                    summaryBox.style.borderLeftColor = activeRole.color;
                    summaryBox.innerHTML = `
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div style="width:42px; height:42px; border-radius:8px; background:${activeRole.color}; color:#fff; display:flex; align-items:center; justify-content:center; font-size:1.3rem;">
                            <i class="fa-solid ${activeRole.icon}"></i>
                        </div>
                        <div>
                            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                                <strong style="font-size:1.05rem; color:var(--text-dark);">${activeRole.name}</strong>
                                <span class="badge" style="background:${activeRole.color}; color:#fff; font-size:0.8rem;">${activeRole.badge}</span>
                                ${isRoleCustomized ? '<span class="badge" style="background:#f59e0b; color:#fff; font-size:0.85rem;"><i class="fa-solid fa-pen-to-square"></i> Customized Override</span>' : ''}
                            </div>
                            <p style="margin:2px 0 0 0; font-size:0.82rem; color:var(--text-muted);">${activeRole.desc}</p>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div style="text-align:right;">
                            <div style="font-size:0.85rem; color:var(--text-muted);">Akses Modul Aktif</div>
                            <strong style="font-size:1.2rem; color:${activeRole.color};">${grantedCount} / 16 Modul</strong>
                        </div>
                    </div>
                `;
                }

                    // 3. Render Matrix Table Header
                    let tableHtml = `
                <thead>
                    <tr style="background:#f1f5f9; color:#334155;">
                        <th style="position:sticky; left:0; background:#f1f5f9; z-index:7; width:220px; min-width:220px; border-right:2px solid #cbd5e1; padding:10px 14px; white-space:nowrap; box-shadow:2px 0 6px rgba(0,0,0,0.06);">User Role (10 Roles)</th>
                        ${rbacModulesList.map(m => `<th style="text-align:center; padding:10px 14px; min-width:145px; white-space:nowrap; word-break:keep-all; overflow-wrap:normal;">${m}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
            `;

                    const isAdmin = window.FleetRBAC && window.FleetRBAC.hasAccess('settings', 'O');

                    // 4. Helper function to format permission badge pill
                    const formatPill = (permStr) => {
                        if (permStr === '-') {
                            return `<span style="display:inline-block; padding:2px 6px; border-radius:4px; background:#f8fafc; color:#cbd5e1; font-weight:600; font-size:0.85rem; white-space:nowrap;">-</span>`;
                        }
                        const parts = permStr.split(' ');
                        return parts.map(p => {
                            let bg = '#f1f5f9', col = '#475569';
                            return `<span style="display:inline-block; padding:2px 5px; border-radius:3px; background:${bg}; color:${col}; font-weight:800; font-size:0.85rem; margin:1px; border:1px solid #e2e8f0; white-space:nowrap;">${p}</span>`;
                        }).join('');
                    };

                    // 5. Render Rows with Highlight for Selected Role
                    tableHtml += activeRbacRolesData.map((role, idx) => {
                        const isSelected = idx === highlightIndex;
                        const rowBg = isSelected ? '#eff6ff' : (idx % 2 === 0 ? '#ffffff' : '#f8fafc');
                        const rowBorder = isSelected ? 'border-left: 4px solid var(--primary);' : '';
                        const stickyBg = isSelected ? '#dbeafe' : (idx % 2 === 0 ? '#ffffff' : '#f8fafc');

                        return `
                    <tr style="background:${rowBg}; ${rowBorder}">
                        <td style="position:sticky; left:0; background:${stickyBg}; z-index:5; width:220px; min-width:220px; border-right:2px solid #cbd5e1; font-weight:${isSelected ? '700' : '600'}; padding:8px 12px; cursor:pointer; white-space:nowrap; box-shadow:2px 0 6px rgba(0,0,0,0.06);" onclick="selectRbacRole(${idx})">
                            <a href="javascript:void(0)" 
                               class="rbac-role-hyperlink" 
                               onclick="event.stopPropagation(); window.openRoleUsersModal(${idx + 1}, '${escapeHtml(role.name)}')" 
                               title="Klik untuk melihat & kelola akun pengguna role ${escapeHtml(role.name)}"
                               style="color:${isSelected ? 'var(--primary)' : 'var(--text-dark)'}; text-decoration:none; display:inline-flex; align-items:center; gap:8px; white-space:nowrap;">
                                <i class="fa-solid ${role.icon}" style="color:${role.color}; width:16px; text-align:center;"></i>
                                <span>${role.name}</span>
                            </a>
                        </td>
                        ${role.permissions.map((p, modIdx) => {
                        const isCellDiff = defaultRbacRolesMaster[idx] && p !== defaultRbacRolesMaster[idx].permissions[modIdx];
                        return `
                                <td style="text-align:center; padding:6px 10px; min-width:145px; white-space:nowrap; word-break:keep-all; overflow-wrap:normal; background:${isSelected ? (p !== '-' ? 'rgba(37,99,235,0.08)' : 'transparent') : (isCellDiff ? '#fef3c7' : 'transparent')}; cursor:${isAdmin ? 'pointer' : 'default'}; position:relative;" 
                                    ${isAdmin ? `onclick="openRbacOverrideModal(${idx}, ${modIdx})"` : ''} 
                                    title="${isAdmin ? `Klik untuk override hak akses ${role.name} pada ${rbacModulesList[modIdx]} (Saat ini: ${p})` : `Hak akses ${role.name} pada ${rbacModulesList[modIdx]}: ${p}`}">
                                    ${formatPill(p)}
                                    ${isCellDiff ? '<i class="fa-solid fa-pen-to-square" style="font-size:0.8rem; color:#d97706; position:absolute; top:2px; right:2px;" title="Override Custom"></i>' : ''}
                                </td>
                            `;
                    }).join('')}
                    </tr>
                `;
                    }).join('') + `</tbody>`;

                tableEl.innerHTML = tableHtml;
            };

            // ==========================================
            // MODAL MANAJEMEN PENGGUNA PER ROLE (DATABASE)
            // ==========================================
            let currentModalRoleId = null;
            let currentModalRoleName = '';
            let currentRoleUsersCache = [];
            let allRolesListCache = [];
            let allLocationsListCache = [];

            async function getCsrfToken() {
                if (window.APP_CSRF_TOKEN) return window.APP_CSRF_TOKEN;
                try {
                    const res = await fetch('api/auth.php', { cache: 'no-store' });
                    const json = await res.json();
                    if (json && json.csrf_token) {
                        window.APP_CSRF_TOKEN = json.csrf_token;
                        return json.csrf_token;
                    }
                } catch (e) {
                    console.warn('Gagal memuat token CSRF:', e);
                }
                return '';
            }

            window.openRoleUsersModal = async function(roleId, roleName) {
                currentModalRoleId = roleId;
                currentModalRoleName = roleName;

                const titleEl = document.getElementById('mruRoleTitle');
                if (titleEl) titleEl.textContent = roleName;

                const searchInput = document.getElementById('mruSearchInput');
                if (searchInput) searchInput.value = '';

                window.toggleAddUserForm(false);

                const tbody = document.getElementById('mruUsersTableBody');
                if (tbody) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="7" style="text-align:center; padding:32px 20px; color:#64748b;">
                                <i class="fa-solid fa-circle-notch fa-spin fa-2x" style="color:var(--primary); margin-bottom:10px;"></i>
                                <div style="font-weight:600;">Memuat daftar pengguna dari database MySQL...</div>
                            </td>
                        </tr>
                    `;
                }

                window.openModal('modalRoleUsers');

                try {
                    const res = await fetch(`api/users.php?role_id=${encodeURIComponent(roleId)}`, { cache: 'no-store' });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const json = await res.json();

                    if (json.status === 'success') {
                        currentRoleUsersCache = Array.isArray(json.data) ? json.data : [];
                        if (Array.isArray(json.roles) && json.roles.length > 0) {
                            allRolesListCache = json.roles;
                            populateRoleSelectOptions(json.roles, roleId);
                        }
                        if (Array.isArray(json.locations)) {
                            allLocationsListCache = json.locations;
                            populateLocationSelectOptions(json.locations);
                        }
                        renderRoleUsersTable(currentRoleUsersCache);
                    } else {
                        throw new Error(json.message || 'Gagal memuat data pengguna');
                    }
                } catch (err) {
                    if (tbody) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="7" style="text-align:center; padding:24px; color:#ef4444;">
                                    <i class="fa-solid fa-triangle-exclamation fa-2x" style="margin-bottom:8px;"></i>
                                    <div>Gagal mengambil data dari database: ${escapeHtml(err.message)}</div>
                                </td>
                            </tr>
                        `;
                    }
                }
            };

            function populateRoleSelectOptions(roles, selectedId) {
                const select = document.getElementById('mruNewRoleId');
                if (!select) return;
                select.innerHTML = roles.map(r => `
                    <option value="${r.role_id}" ${Number(r.role_id) === Number(selectedId) ? 'selected' : ''}>
                        ${escapeHtml(r.role_name)}
                    </option>
                `).join('');
            }

            function populateLocationSelectOptions(locations, selectedId) {
                const select = document.getElementById('mruNewLocationId');
                if (!select) return;
                const opts = ['<option value="">-- Pilih Lokasi Penugasan (Opsional) --</option>'];
                locations.forEach(loc => {
                    const sel = selectedId && Number(loc.location_id) === Number(selectedId) ? 'selected' : '';
                    const typeLabel = loc.location_type ? ` [${escapeHtml(loc.location_type)}]` : '';
                    const regionLabel = loc.region ? ` - ${escapeHtml(loc.region)}` : '';
                    opts.push(`<option value="${loc.location_id}" ${sel}>${escapeHtml(loc.location_name)}${typeLabel}${regionLabel}</option>`);
                });
                select.innerHTML = opts.join('');
            }

            function renderRoleUsersTable(users) {
                const tbody = document.getElementById('mruUsersTableBody');
                const totalLabel = document.getElementById('mruTotalCountLabel');
                if (!tbody) return;

                if (totalLabel) {
                    totalLabel.textContent = `Total: ${users.length} pengguna`;
                }

                if (users.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="7" style="text-align:center; padding:35px 20px; color:#64748b;">
                                <i class="fa-solid fa-user-slash fa-2x" style="color:#cbd5e1; margin-bottom:10px;"></i>
                                <div style="font-weight:600; font-size:0.9rem; color:#475569;">Belum ada user dengan role ini di database</div>
                                <div style="font-size:0.8rem; color:#94a3b8; margin-top:4px;">Klik tombol "+ Tambah User Baru" di atas untuk mendaftarkan akun.</div>
                            </td>
                        </tr>
                    `;
                    return;
                }

                tbody.innerHTML = users.map((u, i) => {
                    const initial = (u.full_name || u.username || 'U').charAt(0).toUpperCase();
                    const isActive = Number(u.is_active) === 1;
                    const isSuperAdmin = Number(u.user_id) === 1 || u.username === 'admin';
                    const locName = u.assigned_location_name || '';
                    const statusTitle = isSuperAdmin
                        ? 'Administrator Utama tidak dapat dinonaktifkan'
                        : (isActive ? 'Klik untuk menonaktifkan akun' : 'Klik untuk mengaktifkan akun');
                    const statusAction = isSuperAdmin
                        ? 'disabled aria-disabled="true"'
                        : `onclick="toggleUserStatus(${u.user_id}, '${escapeHtml(u.username)}', ${isActive ? 1 : 0})"`;

                    return `
                        <tr style="border-bottom:1px solid #f1f5f9; background:#ffffff;">
                            <td style="text-align:center; font-weight:600; color:#94a3b8; padding:10px 8px;">${i + 1}</td>
                            <td style="padding:10px 12px;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="width:28px; height:28px; border-radius:50%; background:#dbeafe; color:#1d4ed8; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.8rem;">
                                        ${initial}
                                    </div>
                                    <strong style="color:#1e293b;">${escapeHtml(u.username)}</strong>
                                </div>
                            </td>
                            <td style="padding:10px 12px; font-weight:500; color:#334155;">
                                ${escapeHtml(u.full_name)}
                            </td>
                            <td style="padding:10px 12px;">
                                <span class="badge" style="background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; font-weight:600; font-size:0.75rem; padding:3px 8px;">
                                    ${escapeHtml(u.role_name || currentModalRoleName)}
                                </span>
                            </td>
                            <td style="padding:10px 12px;">
                                ${locName ? `
                                    <span style="display:inline-flex; align-items:center; gap:5px; font-weight:600; color:#0369a1; background:#e0f2fe; padding:2px 8px; border-radius:4px; font-size:0.75rem;">
                                        <i class="fa-solid fa-location-dot" style="font-size:0.7rem;"></i>
                                        ${escapeHtml(locName)}
                                    </span>
                                ` : `
                                    <span style="color:#94a3b8; font-size:0.78rem; font-style:italic;">Semua Lokasi / Pusat</span>
                                `}
                            </td>
                            <td style="padding:10px 10px; text-align:center;">
                                <button type="button" class="badge ${isActive ? 'badge-success' : 'badge-secondary'}"
                                    ${statusAction} title="${statusTitle}"
                                    style="font-size:0.75rem; padding:4px 9px; border:1px solid ${isActive ? '#86efac' : '#cbd5e1'}; border-radius:999px; cursor:${isSuperAdmin ? 'not-allowed' : 'pointer'}; ${isActive ? 'background:#dcfce7; color:#166534;' : 'background:#f1f5f9; color:#64748b;'}">
                                    <i class="fa-solid fa-circle" style="font-size:0.5rem; margin-right:4px;"></i>
                                    ${isActive ? 'Aktif' : 'Nonaktif'}
                                </button>
                            </td>
                            <td style="padding:10px 12px; text-align:center;">
                                <div style="display:inline-flex; gap:6px;">
                                    <button type="button" class="btn btn-sm" 
                                            onclick="openChangePasswordModal(${u.user_id}, '${escapeHtml(u.username)}', '${escapeHtml(u.full_name)}')"
                                            title="Ganti Password Akun"
                                            style="padding:3px 8px; font-size:0.75rem; background:#fef3c7; color:#92400e; border:1px solid #fde68a; border-radius:4px; cursor:pointer;">
                                        <i class="fa-solid fa-key"></i> Password
                                    </button>
                                    ${isSuperAdmin ? `
                                        <span title="Administrator Utama Terproteksi" style="padding:3px 8px; font-size:0.75rem; color:#94a3b8; background:#f8fafc; border:1px dashed #cbd5e1; border-radius:4px;">
                                            <i class="fa-solid fa-shield"></i> Terproteksi
                                        </span>
                                    ` : `
                                        <button type="button" class="btn btn-sm" 
                                                onclick="confirmDeleteUser(${u.user_id}, '${escapeHtml(u.username)}')"
                                                title="Hapus Pengguna"
                                                style="padding:3px 8px; font-size:0.75rem; background:#fee2e2; color:#991b1b; border:1px solid #fecaca; border-radius:4px; cursor:pointer;">
                                            <i class="fa-solid fa-trash"></i> Hapus
                                        </button>
                                    `}
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }

            window.filterRoleUsersTable = function(term) {
                term = (term || '').toLowerCase().trim();
                if (!term) {
                    renderRoleUsersTable(currentRoleUsersCache);
                    return;
                }
                const filtered = currentRoleUsersCache.filter(u => 
                    (u.username && u.username.toLowerCase().includes(term)) ||
                    (u.full_name && u.full_name.toLowerCase().includes(term)) ||
                    (u.assigned_location_name && u.assigned_location_name.toLowerCase().includes(term))
                );
                renderRoleUsersTable(filtered);
            };

            window.toggleAddUserForm = function(forceState) {
                const formEl = document.getElementById('mruAddUserFormContainer');
                if (!formEl) return;
                const willShow = typeof forceState === 'boolean' ? forceState : (formEl.style.display === 'none' || formEl.style.display === '');
                formEl.style.display = willShow ? 'block' : 'none';
                if (willShow) {
                    document.getElementById('mruNewUsername').value = '';
                    document.getElementById('mruNewFullName').value = '';
                    document.getElementById('mruNewPassword').value = '';
                    const select = document.getElementById('mruNewRoleId');
                    if (select && currentModalRoleId) select.value = currentModalRoleId;
                    const locSelect = document.getElementById('mruNewLocationId');
                    if (locSelect) locSelect.value = '';
                    document.getElementById('mruNewUsername').focus();
                }
            };

            window.submitNewUser = async function() {
                const username = (document.getElementById('mruNewUsername').value || '').trim();
                const fullName = (document.getElementById('mruNewFullName').value || '').trim();
                const password = document.getElementById('mruNewPassword').value || '';
                const roleId = Number(document.getElementById('mruNewRoleId').value) || currentModalRoleId;
                const locationVal = document.getElementById('mruNewLocationId')?.value || '';
                const assignedLocationId = locationVal !== '' ? Number(locationVal) : null;

                if (!username || username.length < 3) {
                    alert('Username wajib diisi minimal 3 karakter.');
                    return;
                }
                if (!fullName || fullName.length < 2) {
                    alert('Nama lengkap wajib diisi minimal 2 karakter.');
                    return;
                }
                if (!password || password.length < 6) {
                    alert('Password wajib diisi minimal 6 karakter.');
                    return;
                }

                const btn = document.getElementById('btnSubmitNewUser');
                const origHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyimpan...';

                try {
                    const csrf = await getCsrfToken();
                    const res = await fetch('api/users.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrf
                        },
                        body: JSON.stringify({
                            action: 'create',
                            username: username,
                            full_name: fullName,
                            password: password,
                            role_id: roleId,
                            assigned_location_id: assignedLocationId
                        })
                    });
                    const json = await res.json();
                    if (!res.ok || json.status !== 'success') {
                        throw new Error(json.message || 'Gagal menambahkan pengguna baru.');
                    }

                    alert(json.message || 'Pengguna baru berhasil ditambahkan.');
                    window.toggleAddUserForm(false);
                    // Refresh data
                    window.openRoleUsersModal(currentModalRoleId, currentModalRoleName);
                } catch (err) {
                    alert('Kesalahan: ' + err.message);
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }
            };

            window.openSelfChangePasswordModal = function() {
                const profilePanel = document.getElementById('profileDropdownPanel');
                if (profilePanel) profilePanel.style.display = 'none';

                const user = window.FleetAuth?.user || {};
                const userId = user.id || user.user_id || 0;
                const username = user.username || 'Anda';
                const fullName = user.full_name || 'Pengguna';
                const roleName = user.primary_role || user.role || 'Administrator';

                const idInput = document.getElementById('mcpUserId');
                if (idInput) idInput.value = userId;

                const targetLabel = document.getElementById('mcpTargetUserLabel');
                if (targetLabel) targetLabel.textContent = `${fullName} (${username})`;

                const targetSub = document.getElementById('mcpTargetUserSub');
                if (targetSub) targetSub.innerHTML = `<i class="fa-solid fa-shield-halved"></i> Sesi Aktif • Role: ${escapeHtml(roleName)}`;

                const titleEl = document.getElementById('mcpModalTitle');
                if (titleEl) titleEl.textContent = 'Ganti Password Akun';

                const subtitleEl = document.getElementById('mcpModalSubtitle');
                if (subtitleEl) subtitleEl.textContent = 'Perbarui kata sandi untuk menjaga keamanan akun Anda.';

                const badgeEl = document.getElementById('mcpHeaderBadge');
                if (badgeEl) {
                    badgeEl.style.background = 'linear-gradient(135deg, #e0f2fe 0%, #bae6fd 100%)';
                    badgeEl.style.color = '#0284c7';
                    badgeEl.innerHTML = '<i class="fa-solid fa-key"></i>';
                }

                const currWrapper = document.getElementById('mcpCurrentPasswordWrapper');
                if (currWrapper) currWrapper.style.display = 'block';

                const alertBox = document.getElementById('mcpAlertBox');
                if (alertBox) {
                    alertBox.style.display = 'none';
                    alertBox.textContent = '';
                }

                const currPass = document.getElementById('mcpCurrentPassword');
                if (currPass) currPass.value = '';
                const newPass = document.getElementById('mcpNewPassword');
                if (newPass) newPass.value = '';
                const confirmPass = document.getElementById('mcpConfirmPassword');
                if (confirmPass) confirmPass.value = '';

                // Reset visibility toggles to password
                ['mcpCurrentPassword', 'mcpNewPassword', 'mcpConfirmPassword'].forEach(id => {
                    const inp = document.getElementById(id);
                    if (inp) inp.type = 'password';
                });
                document.querySelectorAll('#modalChangePassword .btn-toggle-pwd i').forEach(ic => {
                    ic.className = 'fa-solid fa-eye';
                });

                window.openModal('modalChangePassword');

                setTimeout(() => {
                    if (currPass) currPass.focus();
                }, 150);
            };

            window.openChangePasswordModal = function(userId, username, fullName) {
                const idInput = document.getElementById('mcpUserId');
                if (idInput) idInput.value = userId || '';

                const targetLabel = document.getElementById('mcpTargetUserLabel');
                if (targetLabel) targetLabel.textContent = `${fullName || ''} (${username || ''})`;

                const targetSub = document.getElementById('mcpTargetUserSub');
                if (targetSub) targetSub.innerHTML = `<i class="fa-solid fa-user-gear"></i> Reset Kata Sandi oleh Administrator`;

                const titleEl = document.getElementById('mcpModalTitle');
                if (titleEl) titleEl.textContent = 'Reset Password Pengguna';

                const subtitleEl = document.getElementById('mcpModalSubtitle');
                if (subtitleEl) subtitleEl.textContent = 'Tentukan kata sandi baru untuk akun pengguna ini.';

                const badgeEl = document.getElementById('mcpHeaderBadge');
                if (badgeEl) {
                    badgeEl.style.background = 'linear-gradient(135deg, #fef3c7 0%, #fde68a 100%)';
                    badgeEl.style.color = '#d97706';
                    badgeEl.innerHTML = '<i class="fa-solid fa-key"></i>';
                }

                const currWrapper = document.getElementById('mcpCurrentPasswordWrapper');
                if (currWrapper) currWrapper.style.display = 'none';

                const alertBox = document.getElementById('mcpAlertBox');
                if (alertBox) {
                    alertBox.style.display = 'none';
                    alertBox.textContent = '';
                }

                const currPass = document.getElementById('mcpCurrentPassword');
                if (currPass) currPass.value = '';
                const newPass = document.getElementById('mcpNewPassword');
                if (newPass) newPass.value = '';
                const confirmPass = document.getElementById('mcpConfirmPassword');
                if (confirmPass) confirmPass.value = '';

                // Reset visibility toggles to password
                ['mcpCurrentPassword', 'mcpNewPassword', 'mcpConfirmPassword'].forEach(id => {
                    const inp = document.getElementById(id);
                    if (inp) inp.type = 'password';
                });
                document.querySelectorAll('#modalChangePassword .btn-toggle-pwd i').forEach(ic => {
                    ic.className = 'fa-solid fa-eye';
                });

                window.openModal('modalChangePassword');

                setTimeout(() => {
                    if (newPass) newPass.focus();
                }, 150);
            };

            window.togglePasswordVisibility = function(inputId, btn) {
                const input = document.getElementById(inputId);
                if (!input) return;
                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.className = isPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
                }
            };

            // Enter key shortcut for change password inputs
            document.addEventListener('DOMContentLoaded', () => {
                ['mcpCurrentPassword', 'mcpNewPassword', 'mcpConfirmPassword'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.addEventListener('keydown', function(e) {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                window.submitChangePassword();
                            }
                        });
                    }
                });
            });

            window.submitChangePassword = async function() {
                const userId = Number(document.getElementById('mcpUserId').value);
                const currPassEl = document.getElementById('mcpCurrentPassword');
                const currPassWrapper = document.getElementById('mcpCurrentPasswordWrapper');
                const isSelf = currPassWrapper && currPassWrapper.style.display !== 'none';
                const currPass = currPassEl ? currPassEl.value : '';
                const newPass = document.getElementById('mcpNewPassword').value || '';
                const confirmPass = document.getElementById('mcpConfirmPassword').value || '';

                const alertBox = document.getElementById('mcpAlertBox');
                const showAlert = (msg, type = 'danger') => {
                    if (!alertBox) { alert(msg); return; }
                    alertBox.style.display = 'flex';
                    alertBox.style.alignItems = 'center';
                    alertBox.style.gap = '8px';
                    alertBox.style.background = type === 'danger' ? '#fef2f2' : '#f0fdf4';
                    alertBox.style.color = type === 'danger' ? '#991b1b' : '#166534';
                    alertBox.style.border = `1px solid ${type === 'danger' ? '#fecaca' : '#bbf7d0'}`;
                    alertBox.innerHTML = `<i class="fa-solid ${type === 'danger' ? 'fa-circle-exclamation' : 'fa-circle-check'}"></i> <span>${escapeHtml(msg)}</span>`;
                };

                if (isSelf && !currPass) {
                    showAlert('Silakan masukkan password saat ini.');
                    if (currPassEl) currPassEl.focus();
                    return;
                }
                if (newPass.length < 6) {
                    showAlert('Password baru minimal 6 karakter.');
                    document.getElementById('mcpNewPassword').focus();
                    return;
                }
                if (newPass !== confirmPass) {
                    showAlert('Konfirmasi password tidak cocok dengan password baru.');
                    document.getElementById('mcpConfirmPassword').focus();
                    return;
                }

                const btn = document.getElementById('btnSubmitChangePassword');
                const origHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Memproses...';

                try {
                    const csrf = typeof getCsrfToken === 'function' ? await getCsrfToken() : (window.APP_CSRF_TOKEN || '');
                    const payload = {
                        action: 'change_password',
                        user_id: userId,
                        new_password: newPass
                    };
                    if (isSelf && currPass) {
                        payload.current_password = currPass;
                    }

                    const res = await fetch('api/users.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrf
                        },
                        body: JSON.stringify(payload)
                    });
                    const json = await res.json();
                    if (!res.ok || json.status !== 'success') {
                        throw new Error(json.message || 'Gagal mengubah password.');
                    }

                    showAlert(json.message || 'Password berhasil diperbarui!', 'success');
                    setTimeout(() => {
                        window.closeModal('modalChangePassword');
                    }, 800);
                } catch (err) {
                    showAlert(err.message || 'Kesalahan saat menyimpan password.');
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = origHtml;
                }
            };

            window.confirmDeleteUser = async function(userId, username) {
                if (!confirm(`Apakah Anda yakin ingin menghapus pengguna '${username}' dari sistem?\n\nPerhatian: Jika akun memiliki jejak audit operasional, sistem akan menonaktifkannya secara aman demi menjaga integritas data audit.`)) {
                    return;
                }

                try {
                    const csrf = await getCsrfToken();
                    const res = await fetch('api/users.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrf
                        },
                        body: JSON.stringify({
                            action: 'delete',
                            user_id: userId
                        })
                    });
                    const json = await res.json();
                    if (!res.ok || json.status !== 'success') {
                        throw new Error(json.message || 'Gagal menghapus pengguna.');
                    }

                    alert(json.message || 'Pengguna berhasil dihapus.');
                    window.openRoleUsersModal(currentModalRoleId, currentModalRoleName);
                } catch (err) {
                    alert('Kesalahan: ' + err.message);
                }
            };

            window.toggleUserStatus = async function(userId, username, currentStatus) {
                const actionLabel = Number(currentStatus) === 1 ? 'menonaktifkan' : 'mengaktifkan';
                if (!confirm(`Apakah Anda yakin ingin ${actionLabel} akun '${username}'?`)) return;

                try {
                    const csrf = await getCsrfToken();
                    const res = await fetch('api/users.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': csrf
                        },
                        body: JSON.stringify({
                            action: 'toggle_status',
                            user_id: userId
                        })
                    });
                    const json = await res.json();
                    if (!res.ok || json.status !== 'success') {
                        throw new Error(json.message || 'Gagal mengubah status pengguna.');
                    }

                    alert(json.message || 'Status pengguna berhasil diperbarui.');
                    await window.openRoleUsersModal(currentModalRoleId, currentModalRoleName);
                } catch (err) {
                    alert('Kesalahan: ' + (err.message || 'Status pengguna gagal diperbarui.'));
                }
            };

            let tireInspectionStore = {};

            window.inspectTire = function (tireCode) {
                if (!tireCode) return;
                const code = tireCode.toUpperCase();

                // 1. Remove ring highlight from all tires
                document.querySelectorAll('.tire-indicator').forEach(el => {
                    el.classList.remove('selected-tire-ring');
                });

                // 2. Add ring highlight indicator to targeted tire
                const targetId = 'tire-' + code.toLowerCase();
                const targetEl = document.getElementById(targetId);
                if (targetEl) {
                    targetEl.classList.add('selected-tire-ring');
                }

                // 3. Update form title and hidden input
                const labelEl = document.getElementById('selected-tire-label');
                if (labelEl) labelEl.textContent = code;

                const hiddenEl = document.getElementById('selected-tire');
                if (hiddenEl) hiddenEl.value = code;

                // 4. Populate form inputs with store data
                const data = tireInspectionStore[code] || { tread: '', pressure: '' };
                const treadInput = document.getElementById('tire-tread');
                const pressureInput = document.getElementById('tire-pressure');
                if (treadInput) treadInput.value = data.tread;
                if (pressureInput) pressureInput.value = data.pressure;
            };

            window.saveTireInspection = async function () {
                const hiddenEl = document.getElementById('selected-tire');
                const code = hiddenEl ? hiddenEl.value.toUpperCase() : 'FL';
                const treadInput = document.getElementById('tire-tread');
                const pressureInput = document.getElementById('tire-pressure');

                const tread = parseFloat(treadInput ? treadInput.value : 0);
                const pressure = parseFloat(pressureInput ? pressureInput.value : 0);

                if (isNaN(tread) || tread <= 0) {
                    alert('Silakan masukkan kedalaman alur ban (Tread Depth) yang valid!');
                    return;
                }

                let colorCondition = 'GREEN';
                if (tread < 3.2) {
                    colorCondition = 'RED';
                } else if (tread <= 8.5) {
                    colorCondition = 'YELLOW';
                }

                // Temporary fallback for asset_id if not explicitly selected in this view
                const assetId = window.activeAssetId || 'DT-00049'; // fallback ke Hino Ranger

                const payload = {
                    asset_id: assetId,
                    tire_position: code,
                    tread_depth_mm: tread,
                    air_pressure_psi: pressure,
                    condition_color: colorCondition
                };

                try {
                    const response = await fetch('./api/tire_inspections.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });
                    const res = await response.json();

                    if (res.status === 'success') {
                        tireInspectionStore[code] = { tread, pressure };

                        // Dynamically update tire indicator status badge color
                        const targetId = 'tire-' + code.toLowerCase();
                        const targetEl = document.getElementById(targetId);
                        if (targetEl) {
                            targetEl.classList.remove('warning', 'danger');
                            if (tread < 3.2) {
                                targetEl.classList.add('danger');
                            } else if (tread <= 8.5) {
                                targetEl.classList.add('warning');
                            }
                        }

                        alert(`Inspeksi Ban (${code}) Berhasil Disimpan ke Database:\n- Tread Depth: ${tread} mm\n- Tekanan Angin: ${pressure || '-'} PSI`);
                    } else {
                        alert('Gagal menyimpan inspeksi ban: ' + res.message);
                    }
                } catch (e) {
                    console.error(e);
                    alert('Gagal terhubung ke server.');
                }
            };

            function initNewViews() {
                if (window.ConditionMonitoring) {
                    window.ConditionMonitoring.refresh();
                } else if (window.inspectTire) {
                    window.inspectTire('FL');
                }

                if (globalData) {
                    window.SpareLogistics?.refresh();

                    const fuelSelect = document.getElementById('frmFuelUnitId') || document.getElementById('fuel-asset-id');
                    if (fuelSelect && globalData.assets) {
                        fuelSelect.innerHTML = globalData.assets
                            .map(a => `<option value="${escapeHtml(a.id)}">${escapeHtml(a.id)} (${escapeHtml(a.category)} - ${escapeHtml(a.location)})</option>`)
                            .join('');
                    }
                }

            }

            function escapeHtml(unsafe) {
                return (unsafe || '').toString()
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }

        </script>
        <script src="scripts/vendor/jszip-3.10.1.min.js"></script>
        <script src="scripts/vendor/pdf-3.11.174.min.js"></script>
        <script src="scripts/vendor/tesseract-5.1.1.min.js"></script>
        <!-- Global Condition Monitoring Modal Container (Works on top of any menu) -->
        <div id="globalCmModalContainer"></div>

        <!-- Floating Asset Action Callout Container -->
        <div id="assetActionCallout" class="asset-action-callout" style="display:none;" onclick="event.stopPropagation();">
            <div class="callout-header">
                <div class="callout-title-wrap">
                    <span class="callout-unit-badge" id="calloutUnitId">CS-41001</span>
                    <span class="callout-unit-category" id="calloutUnitCategory">Excavator</span>
                </div>
                <button class="callout-close-btn" onclick="closeAssetActionCallout()">&times;</button>
            </div>
            <div class="callout-sub-header" id="calloutUnitLocation">
                <i class="fa-solid fa-location-dot"></i> Site RWI Duri
            </div>
            <div class="callout-body">
                <div class="callout-actions-grid">
                    <button type="button" class="callout-action-btn primary" id="calloutBtn360">
                        <div class="btn-icon"><i class="fa-solid fa-cube"></i></div>
                        <div class="btn-text">
                            <strong>Detail 360&deg;</strong>
                            <small>Master & Telemetri</small>
                        </div>
                    </button>
                    <button type="button" class="callout-action-btn" id="calloutBtnP2H">
                        <div class="btn-icon success"><i class="fa-solid fa-clipboard-check"></i></div>
                        <div class="btn-text">
                            <strong>P2H & Inspeksi</strong>
                            <small>Riwayat & Form</small>
                        </div>
                    </button>
                    <button type="button" class="callout-action-btn" id="calloutBtnWO">
                        <div class="btn-icon warning"><i class="fa-solid fa-screwdriver-wrench"></i></div>
                        <div class="btn-text">
                            <strong>Work Order</strong>
                            <small>Tiket Repair</small>
                        </div>
                    </button>
                    <button type="button" class="callout-action-btn" id="calloutBtnCondition">
                        <div class="btn-icon info"><i class="fa-solid fa-gauge-high"></i></div>
                        <div class="btn-text">
                            <strong>Condition</strong>
                            <small>Monitoring Status</small>
                        </div>
                    </button>
                    <button type="button" class="callout-action-btn" id="calloutBtnEdit">
                        <div class="btn-icon neutral"><i class="fa-solid fa-pen-to-square"></i></div>
                        <div class="btn-text">
                            <strong>Edit Asset</strong>
                            <small>Ubah Metadata</small>
                        </div>
                    </button>
                    <button type="button" class="callout-action-btn danger" id="calloutBtnArsip">
                        <div class="btn-icon danger"><i class="fa-solid fa-box-archive"></i></div>
                        <div class="btn-text">
                            <strong>Arsipkan Unit</strong>
                            <small>Pindahkan Arsip</small>
                        </div>
                    </button>
                </div>
            </div>
        </div>
<script src="scripts/mobile-responsive.js?v=20260929-2"></script>
</body>

</html>
