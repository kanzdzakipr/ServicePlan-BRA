/**
 * ServicePlan-BRA - Heavy Equipment Properties & Diagnostics Inspector
 * Module: scripts/unit-properties.js (Unified Single-View Spec Sheet Layout)
 */

(function () {
    'use strict';

    let currentPropUnitId = null;
    let previousViewName = 'dashboard';
    let propMapInstance = null;

    // Mining Sites & Coordinates Reference (PT BRA Operational Sectors)
    const siteLocations = {
        'Borrow Pit Harapan Baru': { lat: 1.2789000, lng: 101.2112000, radius: 600, color: '#f59e0b' },
        'Yard KM 12 Duri': { lat: 1.2854600, lng: 101.2185700, radius: 450, color: '#0284c7' },
        'Minas Field Project': { lat: 0.7234000, lng: 101.4421000, radius: 800, color: '#16a34a' },
        'Site Celcin': { lat: 1.3012000, lng: 101.1984000, radius: 500, color: '#8b5cf6' },
        'WUR EW Project': { lat: 1.2645000, lng: 101.2341000, radius: 700, color: '#ec4899' },
        'Head Office Pekanbaru': { lat: 0.5071000, lng: 101.4478000, radius: 300, color: '#64748b' }
    };

    /**
     * Escape HTML string helper
     */
    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    /**
     * Simple CRC32 hash for consistent mock variation
     */
    function crc32(str) {
        let hash = 0;
        for (let i = 0; i < (str || '').length; i++) {
            hash = ((hash << 5) - hash) + str.charCodeAt(i);
            hash |= 0;
        }
        return hash;
    }

    /**
     * Find asset in globalData or build fallback model
     */
    function getAssetData(assetId) {
        let asset = null;
        if (window.globalData && Array.isArray(window.globalData.assets)) {
            const cleanId = String(assetId || '').trim().toUpperCase();
            asset = window.globalData.assets.find(a => {
                if (!a) return false;
                if (a.id === assetId || a.asset_id === assetId || a.asset_code === assetId) return true;
                if (String(a.id || '').toUpperCase() === cleanId) return true;
                if (window.parseAssetId) {
                    const parsed = window.parseAssetId(a.id);
                    if (parsed && (parsed.unitId === assetId || parsed.unitId.toUpperCase() === cleanId)) return true;
                }
                return false;
            });
        }

        const idStr = String(assetId || 'DT-00049').toUpperCase();
        const hash = Math.abs(crc32(idStr));

        if (!asset) {
            asset = { id: assetId, asset_id: assetId, asset_code: assetId };
        }

        return asset;
    }

    /**
     * Open Unit Properties View in Main Content (Unified Single-View)
     */
    window.openUnitProperties = function (assetId) {
        if (!assetId) return;
        currentPropUnitId = assetId;

        // Close any active overlay modal (e.g. Tabulasi Unit Detail, WO modal)
        if (typeof window.closeModal === 'function') {
            window.closeModal('modalStatusDetail');
            window.closeModal('modalWoDetail');
            window.closeModal('modalAssetDetail');
        }

        // Remember active view before switching
        const currentActiveSection = document.querySelector('.view-section.active');
        if (currentActiveSection && currentActiveSection.id !== 'view-unit-properties') {
            previousViewName = currentActiveSection.id.replace('view-', '');
        }

        // Hide other view-sections and show unit properties
        document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
        const propView = document.getElementById('view-unit-properties');
        if (propView) {
            propView.classList.add('active');
        }

        // Clear sidebar active states
        if (typeof window.clearActiveMenus === 'function') {
            window.clearActiveMenus();
        }

        // Scroll to top
        const content = document.querySelector('.content');
        if (content) content.scrollTop = 0;

        // Update URL hash for persistence
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', `#unit-properties?id=${encodeURIComponent(assetId)}`);
        }

        // Populate unit selector dropdown
        populateUnitSelector(assetId);

        // Render complete unified properties sheet
        renderUnitPropertiesData(assetId);
        loadHullHistory(assetId);
    };

    let currentAssetOnboardingCache = null;

    /**
     * Format date string to user-friendly Indonesian format (DD MMM YYYY)
     */
    function formatDisplayDate(dateStr) {
        if (!dateStr || dateStr === 'Belum tersedia') return 'Belum tersedia';
        try {
            const d = new Date(dateStr.replace(' ', 'T'));
            if (isNaN(d.getTime())) return dateStr;
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const day = String(d.getDate()).padStart(2, '0');
            const month = months[d.getMonth()];
            const year = d.getFullYear();
            return `${day} ${month} ${year}`;
        } catch (e) {
            return dateStr;
        }
    }

    async function loadHullHistory(assetId) {
        try {
            const response = await fetch(`api/assets.php?id=${encodeURIComponent(assetId)}`, { credentials: 'same-origin' });
            const payload = await response.json();
            if (!response.ok || payload.status !== 'success') throw new Error(payload.message || 'Gagal memuat');
            if (currentPropUnitId !== assetId) return;
            const asset = payload.data || {};
            currentAssetOnboardingCache = asset;
            renderAssetOnboarding(asset);
            const hull = asset.hull_number || asset.alias_name || '';
            const hullEl = document.getElementById('propUnitHull');
            if (hullEl) hullEl.textContent = hull || 'Belum tersedia';
        } catch (error) {
            console.warn('Gagal memuat data onboarding unit:', error);
        }
    }

    function renderAssetOnboarding(asset) {
        if (!asset) return;
        const show = value => value == null || value === '' ? 'Belum tersedia' : escapeHtml(value);
        const operatorRows = Array.isArray(asset.operator_history) ? asset.operator_history : [];
        const serviceRows = Array.isArray(asset.service_history) ? asset.service_history : [];
        const photoRows = Array.isArray(asset.gallery_photos) ? asset.gallery_photos : [];

        // 1. Dokumen Legalitas & Berkas JO Strip (STNK, KIR, SILO, PDF JO)
        const docsContainer = document.getElementById('propDocsLinksContainer');
        if (docsContainer) {
            let docsHtml = '';

            // STNK
            if (asset.legal_stnk_expiry) {
                docsHtml += `
                    <a href="javascript:void(0)" class="prop-doc-link active" onclick="window.openPropDocumentModal('STNK', 'Surat Tanda Nomor Kendaraan (STNK)', '${escapeHtml(asset.legal_stnk_expiry)}', null)" title="Lihat Dokumen STNK (Masa Berlaku: ${escapeHtml(asset.legal_stnk_expiry)})">
                        <i class="fa-solid fa-file-shield"></i> Lihat STNK
                        <span class="prop-doc-pill-meta">${escapeHtml(asset.legal_stnk_expiry)}</span>
                    </a>
                `;
            } else {
                docsHtml += `
                    <span class="prop-doc-link disabled" title="Dokumen STNK belum diunggah">
                        <i class="fa-solid fa-file-shield"></i> STNK: <span class="prop-doc-muted-text">Belum tersedia</span>
                    </span>
                `;
            }

            // KIR
            if (asset.legal_kir_expiry) {
                docsHtml += `
                    <a href="javascript:void(0)" class="prop-doc-link active" onclick="window.openPropDocumentModal('KIR', 'Uji Kelayakan Kendaraan Bermotor (KIR)', '${escapeHtml(asset.legal_kir_expiry)}', null)" title="Lihat Dokumen KIR (Masa Berlaku: ${escapeHtml(asset.legal_kir_expiry)})">
                        <i class="fa-solid fa-certificate"></i> Lihat KIR
                        <span class="prop-doc-pill-meta">${escapeHtml(asset.legal_kir_expiry)}</span>
                    </a>
                `;
            } else {
                docsHtml += `
                    <span class="prop-doc-link disabled" title="Dokumen KIR belum diunggah">
                        <i class="fa-solid fa-certificate"></i> KIR: <span class="prop-doc-muted-text">Belum tersedia</span>
                    </span>
                `;
            }

            // SILO
            if (asset.legal_silo_expiry) {
                docsHtml += `
                    <a href="javascript:void(0)" class="prop-doc-link active" onclick="window.openPropDocumentModal('SILO', 'Surat Izin Layak Operasi (SILO K3)', '${escapeHtml(asset.legal_silo_expiry)}', null)" title="Lihat Dokumen SILO (Masa Berlaku: ${escapeHtml(asset.legal_silo_expiry)})">
                        <i class="fa-solid fa-file-contract"></i> Lihat SILO
                        <span class="prop-doc-pill-meta">${escapeHtml(asset.legal_silo_expiry)}</span>
                    </a>
                `;
            } else {
                docsHtml += `
                    <span class="prop-doc-link disabled" title="Dokumen SILO belum diunggah">
                        <i class="fa-solid fa-file-contract"></i> SILO: <span class="prop-doc-muted-text">Belum tersedia</span>
                    </span>
                `;
            }

            // PDF JO
            if (asset.service_manual_pdf_url) {
                const pdfUrl = `api/asset_files.php?kind=pdf&asset_id=${encodeURIComponent(asset.asset_id)}`;
                docsHtml += `
                    <a href="javascript:void(0)" class="prop-doc-link active" onclick="window.openPropDocumentModal('PDF JO', 'Job Order & Dokumen Servis Berkala', null, '${pdfUrl}')" title="Buka Dokumen PDF Job Order Servis">
                        <i class="fa-solid fa-file-pdf"></i> Lihat PDF JO
                        <span class="prop-doc-pill-meta"><i class="fa-solid fa-arrow-up-right-from-square" style="font-size:0.65rem;"></i></span>
                    </a>
                `;
            } else {
                docsHtml += `
                    <span class="prop-doc-link disabled" title="Dokumen PDF JO belum diunggah">
                        <i class="fa-solid fa-file-pdf"></i> PDF JO: <span class="prop-doc-muted-text">Belum tersedia</span>
                    </span>
                `;
            }

            docsContainer.innerHTML = docsHtml;
        }

        // 2. Vitals Cards Onboarding (Operator, Servis Terakhir, Interval, Target, Estimasi)
        const opVal = document.getElementById('propVitalOperator');
        const opSub = document.getElementById('propVitalOperatorSub');
        if (opVal) opVal.textContent = asset.current_operator_name || 'Belum tersedia';
        if (opSub) {
            opSub.textContent = asset.operator_shift
                ? `Shift ${asset.operator_shift}`
                : (asset.current_operator_name ? 'Penugasan Aktif' : 'Belum Ditugaskan');
        }

        const srvVal = document.getElementById('propVitalLastService');
        const srvSub = document.getElementById('propVitalLastServiceSub');
        if (srvVal) srvVal.textContent = asset.last_service_date ? formatDisplayDate(asset.last_service_date) : 'Belum tersedia';
        if (srvSub) {
            srvSub.textContent = asset.last_service_hm_km != null
                ? `${Number(asset.last_service_hm_km).toLocaleString('id-ID')} ${asset.service_interval_unit || 'HM'}`
                : 'Belum tercatat';
        }

        const intVal = document.getElementById('propVitalInterval');
        const intSub = document.getElementById('propVitalIntervalSub');
        if (intVal) {
            intVal.textContent = asset.service_interval_val != null
                ? `${Number(asset.service_interval_val).toLocaleString('id-ID')} ${asset.service_interval_unit || 'HM'}`
                : 'Belum tersedia';
        }
        if (intSub) intSub.textContent = 'Siklus Rutin PM';

        const tgtVal = document.getElementById('propVitalNextTarget');
        const tgtSub = document.getElementById('propVitalNextTargetSub');
        if (tgtVal) {
            tgtVal.textContent = asset.next_service_due_hm_km != null
                ? `${Number(asset.next_service_due_hm_km).toLocaleString('id-ID')} ${asset.service_interval_unit || 'HM'}`
                : 'Belum tersedia';
        }
        if (tgtSub) tgtSub.textContent = 'Ambang Batas Jadwal PM';

        const estVal = document.getElementById('propVitalEstimate');
        const estSub = document.getElementById('propVitalEstimateSub');
        if (estVal) estVal.textContent = asset.next_service_estimated_date ? formatDisplayDate(asset.next_service_estimated_date) : 'Belum tersedia';
        if (estSub) {
            estSub.textContent = asset.daily_usage_coefficient != null
                ? `Laju: ${asset.daily_usage_coefficient} ${asset.service_interval_unit || 'HM'}/hari`
                : 'Kalkulasi Beban Operasi';
        }

        // 3. Card Galeri Foto Unit (3x3 Layout di Sisi Kanan)
        const galleryCountEl = document.getElementById('propGalleryCount');
        if (galleryCountEl) galleryCountEl.textContent = `${photoRows.length} Foto`;

        const galleryContainer = document.getElementById('propGalleryContainer');
        if (galleryContainer) {
            if (photoRows.length === 0) {
                galleryContainer.innerHTML = `
                    <div class="prop-gallery-empty-state">
                        <div class="prop-gallery-empty-icon"><i class="fa-regular fa-image"></i></div>
                        <div class="prop-gallery-empty-title">Tidak ada foto dokumentasi</div>
                        <div class="prop-gallery-empty-desc">Belum ada foto unit yang diunggah untuk unit ini.</div>
                    </div>
                `;
            } else {
                const photosGridHtml = photoRows.map(photo => {
                    const photoUrl = `api/asset_files.php?kind=photo&photo_id=${encodeURIComponent(photo.photo_id)}`;
                    return `
                        <div class="prop-gallery-thumb-item" onclick="window.openPropPhotoLightbox('${photoUrl}', '${escapeHtml(photo.photo_title)}', '${escapeHtml(photo.photo_angle || 'Foto')}', '${escapeHtml(photo.uploaded_at || '')}')" title="${escapeHtml(photo.photo_title)} (${escapeHtml(photo.photo_angle || 'Foto')})">
                            <img src="${photoUrl}" alt="${escapeHtml(photo.photo_title)}" loading="lazy">
                            <span class="prop-gallery-thumb-angle">${escapeHtml(photo.photo_angle || 'Foto')}</span>
                        </div>
                    `;
                }).join('');

                galleryContainer.innerHTML = `<div class="prop-gallery-grid-3x3">${photosGridHtml}</div>`;
            }
        }
    }

    /**
     * Modal Controller: Riwayat Perubahan Nomor Lambung
     */
    window.openPropHullHistoryModal = function () {
        const modal = document.getElementById('modalPropHullHistory');
        const titleEl = document.getElementById('mrlAssetTitle');
        const listContainer = document.getElementById('mrlHistoryList');
        if (titleEl) titleEl.textContent = currentPropUnitId || 'Unit';

        const history = Array.isArray(currentAssetOnboardingCache?.hull_history)
            ? currentAssetOnboardingCache.hull_history
            : [];

        if (listContainer) {
            if (history.length === 0) {
                listContainer.innerHTML = `
                    <div style="text-align:center; padding:32px 16px; color:#64748b;">
                        <i class="fa-solid fa-clock-rotate-left fa-2x" style="color:#cbd5e1; margin-bottom:8px;"></i>
                        <div style="font-weight:600; color:#475569;">Belum Ada Riwayat Perubahan</div>
                        <div style="font-size:0.75rem;">Nomor lambung unit ini belum pernah diubah semenjak didaftarkan.</div>
                    </div>
                `;
            } else {
                listContainer.innerHTML = history.map((row, idx) => `
                    <div style="padding:12px 14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:10px; position:relative;">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:4px;">
                            <span style="font-size:0.95rem; font-weight:800; color:#0f172a; font-family:monospace;">
                                <span style="color:#64748b; font-weight:600;">${escapeHtml(row.old_hull_number || 'Awal')}</span> 
                                <i class="fa-solid fa-arrow-right" style="font-size:0.75rem; color:#0284c7; margin:0 4px;"></i> 
                                <span style="color:#0284c7;">${escapeHtml(row.new_hull_number)}</span>
                            </span>
                            <span style="font-size:0.75rem; font-weight:600; background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:12px;">
                                Berlaku: ${escapeHtml(row.effective_date || '-')}
                            </span>
                        </div>
                        <div style="font-size:0.82rem; color:#334155; margin-bottom:4px;">
                            <strong>Alasan:</strong> ${escapeHtml(row.change_reason || '-')}
                        </div>
                        <div style="font-size:0.73rem; color:#94a3b8; display:flex; align-items:center; gap:8px;">
                            <span><i class="fa-solid fa-user-check"></i> ${escapeHtml(row.changed_by_name || 'Admin')}</span>
                            <span>&bull;</span>
                            <span><i class="fa-solid fa-clock"></i> Dicatat: ${escapeHtml(row.changed_at || '-')}</span>
                        </div>
                    </div>
                `).join('');
            }
        }

        if (typeof window.openModal === 'function') {
            window.openModal('modalPropHullHistory');
        }
    };

    /**
     * Trigger Rename Hull modal from Unit Properties
     */
    window.openPropRenameModal = function () {
        const currentHull = currentAssetOnboardingCache?.hull_number || currentAssetOnboardingCache?.alias_name || '';
        if (typeof window.openRenameHullModal === 'function') {
            window.openRenameHullModal(currentPropUnitId, currentHull);
        }
    };

    /**
     * Modal Controller: Riwayat Penugasan Operator
     */
    window.openPropOperatorHistoryModal = function () {
        const titleEl = document.getElementById('mroAssetTitle');
        const listContainer = document.getElementById('mroHistoryList');
        if (titleEl) titleEl.textContent = currentPropUnitId || 'Unit';

        const history = Array.isArray(currentAssetOnboardingCache?.operator_history)
            ? currentAssetOnboardingCache.operator_history
            : [];

        if (listContainer) {
            if (history.length === 0) {
                listContainer.innerHTML = `
                    <div style="text-align:center; padding:32px 16px; color:#64748b;">
                        <i class="fa-solid fa-user-clock fa-2x" style="color:#cbd5e1; margin-bottom:8px;"></i>
                        <div style="font-weight:600; color:#475569;">Belum Ada Riwayat Operator</div>
                        <div style="font-size:0.75rem;">Riwayat penugasan operator belum tercatat untuk unit ini.</div>
                    </div>
                `;
            } else {
                listContainer.innerHTML = `
                    <table style="width:100%; border-collapse:collapse; font-size:0.83rem; text-align:left;">
                        <thead>
                            <tr style="background:#f1f5f9; color:#475569; font-size:0.75rem; text-transform:uppercase;">
                                <th style="padding:8px 12px; border-bottom:1px solid #cbd5e1;">Nama Operator</th>
                                <th style="padding:8px 12px; border-bottom:1px solid #cbd5e1;">Periode Penugasan</th>
                                <th style="padding:8px 12px; border-bottom:1px solid #cbd5e1;">Shift</th>
                                <th style="padding:8px 12px; border-bottom:1px solid #cbd5e1;">Catatan Handover</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${history.map(row => `
                                <tr style="border-bottom:1px solid #e2e8f0;">
                                    <td style="padding:10px 12px; font-weight:700; color:#0f172a;">${escapeHtml(row.operator_name)}</td>
                                    <td style="padding:10px 12px; color:#475569;">${escapeHtml(row.start_date || '-')} s/d ${escapeHtml(row.end_date || 'Aktif')}</td>
                                    <td style="padding:10px 12px; color:#0284c7; font-weight:600;">${escapeHtml(row.shift_type || 'Normal')}</td>
                                    <td style="padding:10px 12px; color:#64748b;">${escapeHtml(row.handover_notes || '-')}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }
        }

        if (typeof window.openModal === 'function') {
            window.openModal('modalPropOperatorHistory');
        }
    };

    /**
     * Modal Controller: Riwayat Perawatan & Servis PM
     */
    window.openPropServiceHistoryModal = function () {
        const titleEl = document.getElementById('mrsAssetTitle');
        const listContainer = document.getElementById('mrsHistoryList');
        if (titleEl) titleEl.textContent = currentPropUnitId || 'Unit';

        const history = Array.isArray(currentAssetOnboardingCache?.service_history)
            ? currentAssetOnboardingCache.service_history
            : [];

        if (listContainer) {
            if (history.length === 0) {
                listContainer.innerHTML = `
                    <div style="text-align:center; padding:32px 16px; color:#64748b;">
                        <i class="fa-solid fa-wrench fa-2x" style="color:#cbd5e1; margin-bottom:8px;"></i>
                        <div style="font-weight:600; color:#475569;">Belum Ada Riwayat Perawatan</div>
                        <div style="font-size:0.75rem;">Riwayat perawatan terverifikasi belum tercatat untuk unit ini.</div>
                    </div>
                `;
            } else {
                listContainer.innerHTML = `
                    <table style="width:100%; border-collapse:collapse; font-size:0.83rem; text-align:left;">
                        <thead>
                            <tr style="background:#f1f5f9; color:#475569; font-size:0.75rem; text-transform:uppercase;">
                                <th style="padding:8px 12px; border-bottom:1px solid #cbd5e1;">Tanggal Servis</th>
                                <th style="padding:8px 12px; border-bottom:1px solid #cbd5e1;">Meter HM/KM</th>
                                <th style="padding:8px 12px; border-bottom:1px solid #cbd5e1;">Jenis Servis</th>
                                <th style="padding:8px 12px; border-bottom:1px solid #cbd5e1;">Mekanik PIC</th>
                                <th style="padding:8px 12px; border-bottom:1px solid #cbd5e1;">No. JO / Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${history.map(row => `
                                <tr style="border-bottom:1px solid #e2e8f0;">
                                    <td style="padding:10px 12px; font-weight:700; color:#0f172a;">${formatDisplayDate(row.service_date)}</td>
                                    <td style="padding:10px 12px; color:#0284c7; font-weight:600;">${Number(row.service_hm_km).toLocaleString('id-ID')} ${currentAssetOnboardingCache?.service_interval_unit || 'HM'}</td>
                                    <td style="padding:10px 12px; color:#334155; font-weight:600;">${escapeHtml(row.service_type || '-')}</td>
                                    <td style="padding:10px 12px; color:#475569;">${escapeHtml(row.mechanic_pic || '-')}</td>
                                    <td style="padding:10px 12px; color:#64748b;">
                                        ${row.jo_report_number ? `<span style="font-weight:600; color:#0f172a;">JO: ${escapeHtml(row.jo_report_number)}</span><br>` : ''}
                                        ${escapeHtml(row.notes || '-')}
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }
        }

        if (typeof window.openModal === 'function') {
            window.openModal('modalPropServiceHistory');
        }
    };

    /**
     * Modal Controller: Document Viewer (STNK, KIR, SILO, PDF JO)
     */
    window.openPropDocumentModal = function (docType, docName, expiryDate, fileUrl) {
        const titleEl = document.getElementById('mpdDocTitle');
        const assetCodeEl = document.getElementById('mpdAssetCode');
        const descEl = document.getElementById('mpdDocDesc');
        const expiryBadgeEl = document.getElementById('mpdExpiryBadge');
        const actionBtnEl = document.getElementById('mpdActionBtn');

        if (titleEl) titleEl.textContent = `Dokumen: ${docType}`;
        if (assetCodeEl) assetCodeEl.textContent = `${currentPropUnitId} (${currentAssetOnboardingCache?.hull_number || currentAssetOnboardingCache?.alias_name || 'Tanpa Lambung'})`;
        if (descEl) descEl.textContent = docName || `Dokumen legalitas ${docType}`;

        if (expiryBadgeEl) {
            if (expiryDate) {
                expiryBadgeEl.style.display = 'inline-flex';
                expiryBadgeEl.innerHTML = `<i class="fa-solid fa-calendar-check" style="margin-right:5px;"></i> Masa Berlaku: <strong>${escapeHtml(expiryDate)}</strong>`;
            } else {
                expiryBadgeEl.style.display = 'none';
            }
        }

        if (actionBtnEl) {
            if (fileUrl) {
                actionBtnEl.style.display = 'inline-flex';
                actionBtnEl.href = fileUrl;
                actionBtnEl.target = '_blank';
                actionBtnEl.rel = 'noopener';
                actionBtnEl.innerHTML = '<i class="fa-solid fa-file-pdf"></i> Buka / Unduh Berkas PDF';
            } else {
                actionBtnEl.style.display = 'none';
            }
        }

        if (typeof window.openModal === 'function') {
            window.openModal('modalPropDocumentViewer');
        }
    };

    /**
     * Modal Controller: Photo Lightbox Preview
     */
    window.openPropPhotoLightbox = function (photoUrl, title, angle, uploadedAt) {
        const imgEl = document.getElementById('mplPreviewImg');
        const titleEl = document.getElementById('mplPhotoTitle');
        const angleEl = document.getElementById('mplPhotoAngle');
        const dateEl = document.getElementById('mplPhotoDate');
        const downloadBtn = document.getElementById('mplDownloadBtn');

        if (imgEl) imgEl.src = photoUrl;
        if (titleEl) titleEl.textContent = title || 'Foto Unit';
        if (angleEl) angleEl.textContent = angle || 'Angle';
        if (dateEl) dateEl.textContent = uploadedAt ? `Diunggah: ${uploadedAt}` : '';
        if (downloadBtn) {
            downloadBtn.href = photoUrl;
            downloadBtn.target = '_blank';
        }

        if (typeof window.openModal === 'function') {
            window.openModal('modalPropPhotoPreview');
        }
    };

    /**
     * Return back to previous view
     */
    window.backFromUnitProperties = function () {
        const target = previousViewName || 'dashboard';
        if (typeof window.showView === 'function') {
            window.showView(target, target.toUpperCase(), `menu-${target}`);
        } else {
            document.querySelectorAll('.view-section').forEach(el => el.classList.remove('active'));
            const prevEl = document.getElementById('view-' + target) || document.getElementById('view-dashboard');
            if (prevEl) prevEl.classList.add('active');
        }
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', `#${target}`);
        }
    };

    /**
     * Print Equipment Spec Sheet
     */
    window.printUnitProperties = function () {
        window.print();
    };

    /**
     * Populate Unit Selector Dropdown
     */
    function populateUnitSelector(selectedId) {
        const selectEl = document.getElementById('propUnitSelector');
        if (!selectEl) return;

        let options = '';
        if (window.globalData && window.globalData.assets && window.globalData.assets.length > 0) {
            window.globalData.assets.forEach(a => {
                const isSel = a.id === selectedId ? 'selected' : '';
                options += `<option value="${escapeHtml(a.id)}" ${isSel}>${escapeHtml(a.id)} - ${escapeHtml(a.category || 'Heavy Eq')} (${escapeHtml(a.location || 'Site')})</option>`;
            });
        } else {
            const defaults = ['DT-00049', 'DT-00050', 'DT-00051', 'DT-00052', 'EX-00010', 'MG-00003', 'VB-00004'];
            options = defaults.map(id => `<option value="${id}" ${id === selectedId ? 'selected' : ''}>${id} - Heavy Equipment</option>`).join('');
        }
        selectEl.innerHTML = options;
    }

    /**
     * Render Complete Unit Properties View (Unified Single Page)
     */
    function renderUnitPropertiesData(assetId) {
        const asset = getAssetData(assetId);
        const hash = Math.abs(crc32(assetId));

        // 1. Identity & Spec Header
        const unitIdEls = document.querySelectorAll('.prop-render-unit-id');
        unitIdEls.forEach(el => el.textContent = asset.id || assetId);

        const modelEl = document.getElementById('propUnitModel');
        if (modelEl) modelEl.textContent = asset.make_model || asset.model || 'Heavy Equipment';

        const plateEl = document.getElementById('propUnitPlate');
        if (plateEl) plateEl.textContent = asset.license_plate || 'Belum tersedia';

        const serialEl = document.getElementById('propUnitSerial');
        if (serialEl) serialEl.textContent = asset.serial_number || 'Belum tersedia';
        const hullEl = document.getElementById('propUnitHull');
        if (hullEl) hullEl.textContent = asset.hull_number || asset.alias_name || 'Belum tersedia';

        const iconBox = document.getElementById('propCategoryIcon');
        if (iconBox) {
            const cat = (asset.category || '').toLowerCase();
            if (cat.includes('excavator')) iconBox.innerHTML = '<i class="fa-solid fa-person-digging"></i>';
            else if (cat.includes('grader')) iconBox.innerHTML = '<i class="fa-solid fa-road"></i>';
            else if (cat.includes('compactor') || cat.includes('vibro')) iconBox.innerHTML = '<i class="fa-solid fa-compact-disc"></i>';
            else if (cat.includes('dozer')) iconBox.innerHTML = '<i class="fa-solid fa-tractor"></i>';
            else iconBox.innerHTML = '<i class="fa-solid fa-truck-front"></i>';
        }

        // Status Pill with Pulse
        const statusPill = document.getElementById('propUnitStatusPill');
        if (statusPill) {
            const st = (asset.status || 'OPERATING').toUpperCase();
            let statusCls = 'operating';
            if (st === 'READY') statusCls = 'ready';
            else if (st === 'STANDBY') statusCls = 'standby';
            else if (st === 'BREAKDOWN') statusCls = 'breakdown';
            else if (st === 'ACCIDENT HOLD' || st.includes('ACCIDENT')) statusCls = 'accident';

            statusPill.className = `prop-status-pill ${statusCls}`;
            statusPill.innerHTML = `<span class="prop-pulse-dot"></span> ${escapeHtml(st)}`;
        }

        // Vitals
        const hmVal = asset.last_hm_km != null ? Number(asset.last_hm_km).toLocaleString('id-ID') : 'Belum tersedia';
        const elVitalHm = document.getElementById('propVitalHm');
        if (elVitalHm) elVitalHm.textContent = `${hmVal} HM`;

        const elVitalLoc = document.getElementById('propVitalLoc');
        if (elVitalLoc) elVitalLoc.textContent = asset.location || asset.location_name || 'Belum tersedia';

        const elVitalHealth = document.getElementById('propVitalHealth');
        if (elVitalHealth) elVitalHealth.textContent = asset.health_score != null ? `${asset.health_score}%` : 'Belum tersedia';

        const elVitalNextService = document.getElementById('propVitalNextService');
        if (elVitalNextService) elVitalNextService.textContent = asset.next_service_due_hm_km != null
            ? `${Number(asset.next_service_due_hm_km).toLocaleString('id-ID')} ${asset.service_interval_unit || 'HM'} · Est. ${asset.next_service_estimated_date || 'belum tersedia'}`
            : 'Belum tersedia';

        // SECTION 1: Top Live Map & Telemetry CANbus
        renderTopLocationAndTelemetry(asset, hash);

        // SECTION 2: Technical Specifications Listing (Non-Card)
        renderTechnicalSpecsListing(asset, hash);

        // SECTION 3: Maintenance Plans, WOs & Spare Parts
        renderMaintenanceAndWorkOrders(asset, hash);

        // SECTION 4: Diagnostics, 10-Tire Diagram, Oil PAP & P2H
        renderDiagnosticsAndTires(asset, hash);
    }

    /**
     * Render SECTION 1: Top Live Map & Telemetry CANbus
     */
    function renderTopLocationAndTelemetry(asset, hash) {
        const locName = asset.location || 'Borrow Pit Harapan Baru';
        const siteRef = siteLocations[locName] || siteLocations['Borrow Pit Harapan Baru'];
        const jitter = (hash % 100) * 0.0002;
        const currentLat = siteRef.lat + jitter;
        const currentLng = siteRef.lng + (jitter * 0.7);

        // Initialize / Update Leaflet Map
        setTimeout(() => {
            initPropertiesMap(currentLat, currentLng, locName, asset);
        }, 100);

        // Telemetry Live Gauges Values
        const isOperating = (asset.status || '').toUpperCase() === 'OPERATING';
        const rpm = isOperating ? (1500 + (hash % 350)) : (asset.status === 'STANDBY' ? 750 : 0);
        const coolant = isOperating ? (82 + (hash % 6)) : 34;
        const oilPress = isOperating ? (3.8 + ((hash % 10) * 0.05)).toFixed(1) : '0.0';
        const volt = (27.2 + ((hash % 8) * 0.1)).toFixed(1);

        const elRpm = document.getElementById('propGaugeRpm');
        if (elRpm) elRpm.textContent = rpm;

        const elCoolant = document.getElementById('propGaugeCoolant');
        if (elCoolant) elCoolant.textContent = coolant;

        const elOilPress = document.getElementById('propGaugeOilPress');
        if (elOilPress) elOilPress.textContent = oilPress;

        const elVolt = document.getElementById('propGaugeVolt');
        if (elVolt) elVolt.textContent = volt;

        // GPS Breadcrumb Logs Table
        const tbGps = document.getElementById('propGpsLogsTableBody');
        if (tbGps) {
            let rows = '';
            for (let i = 0; i < 4; i++) {
                const d = new Date(Date.now() - (i * 900000));
                const timeStr = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                const lat = (currentLat - (i * 0.0005)).toFixed(6);
                const lng = (currentLng - (i * 0.0004)).toFixed(6);
                const speed = i === 0 ? (asset.status === 'OPERATING' ? '26.4 km/h' : '0.0 km/h') : `${20 + (i * 3)} km/h`;
                const ign = i === 0 ? (asset.status === 'OPERATING' ? '<span class="p2h-badge pass">ON</span>' : '<span class="p2h-badge warning">IDLING</span>') : '<span class="p2h-badge pass">ON</span>';

                rows += `
                    <tr>
                        <td><strong>${timeStr}</strong></td>
                        <td>${lat}, ${lng}</td>
                        <td>${speed}</td>
                        <td>${ign}</td>
                    </tr>
                `;
            }
            tbGps.innerHTML = rows;
        }

        // BAST Movements History Table
        const tbMov = document.getElementById('propMovementTableBody');
        if (tbMov) {
            tbMov.innerHTML = `
                <tr>
                    <td><strong>BAST/MUT/2026/08/042</strong></td>
                    <td>14 Agu 2026</td>
                    <td>Yard KM 12 &rarr; <strong>${escapeHtml(locName)}</strong></td>
                    <td>Mobilisasi unit untuk overburden hauling.</td>
                    <td><span class="p2h-badge pass">Approved</span></td>
                </tr>
            `;
        }
    }

    /**
     * Render SECTION 2: Technical Specifications Listing (Non-Card Format)
     */
    function renderTechnicalSpecsListing(asset, hash) {
        const cat = (asset.category || '').toLowerCase();
        let engine = 'Hino J08E-WD Turbo Intercooler (280 PS / 2.100 RPM)';
        let trans = 'Eaton 9-Speed Manual w/ Hi-Lo Splitter';
        let payload = '26.000 KG (Gross Vehicle Weight)';
        let oilCap = '28 Liter (Engine Oil 15W-40)';
        let fuelCap = '200 Liter High Grade Solar';
        let drive = '6x4 Rigid Heavy Duty Axle';

        if (cat.includes('excavator')) {
            engine = 'Komatsu SAA6D107E-1 Turbocharged Diesel (148 HP / 2.000 RPM)';
            trans = 'Dual Variable Displacement Axial Piston Hydraulic';
            payload = '0.93 m³ Bucket Capacity / 20.5 Ton Operating Class';
            oilCap = '23.1 Liter Engine / 135 Liter Hydraulic Tank';
            fuelCap = '400 Liter Solar';
            drive = 'Full Hydrostatic Crawler Track 600mm Triple Grouser';
        } else if (cat.includes('grader')) {
            engine = 'Komatsu SAA6D107E-1 (145 HP / 2.000 RPM)';
            trans = 'Direct Drive Power Shift 8 Forward - 4 Reverse';
            payload = 'Blade Width 3.71 Meter / Operating Weight 14.5 Ton';
            oilCap = '20 Liter Engine / 70 Liter Hydraulic';
            fuelCap = '340 Liter Solar';
            drive = '6x4 Tandem Drive with Differential Lock';
        }

        const listEl = document.getElementById('propSpecListGrid');
        if (listEl) {
            listEl.innerHTML = `
                <!-- ROW 1 (White) -->
                <div class="prop-spec-row">
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-truck"></i> Tipe &amp; Kategori Alat</div>
                        <div class="prop-spec-val">${escapeHtml(asset.category || 'Heavy Equipment')}</div>
                    </div>
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-gears"></i> Model Mesin (Powertrain)</div>
                        <div class="prop-spec-val">${escapeHtml(engine)}</div>
                    </div>
                </div>

                <!-- ROW 2 (Grey Striped) -->
                <div class="prop-spec-row">
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-code-branch"></i> Transmisi &amp; Drive Train</div>
                        <div class="prop-spec-val">${escapeHtml(trans)}</div>
                    </div>
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-weight-hanging"></i> Kapasitas Muatan / GVW</div>
                        <div class="prop-spec-val">${escapeHtml(payload)}</div>
                    </div>
                </div>

                <!-- ROW 3 (White) -->
                <div class="prop-spec-row">
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-road"></i> Konfigurasi Penggerak / Drive</div>
                        <div class="prop-spec-val">${escapeHtml(drive)}</div>
                    </div>
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-oil-can"></i> Kapasitas Pelumas &amp; Hidrolik</div>
                        <div class="prop-spec-val">${escapeHtml(oilCap)}</div>
                    </div>
                </div>

                <!-- ROW 4 (Grey Striped) -->
                <div class="prop-spec-row">
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-gas-pump"></i> Kapasitas Tangki Bahan Bakar</div>
                        <div class="prop-spec-val">${escapeHtml(fuelCap)}</div>
                    </div>
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-shield-halved"></i> Status Kepemilikan</div>
                        <div class="prop-spec-val">${escapeHtml(asset.ownership || 'Milik Sendiri (PT BRA)')}</div>
                    </div>
                </div>

                <!-- ROW 5 (White) -->
                <div class="prop-spec-row">
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-building-flag"></i> Cabang &amp; Wilayah Operasi</div>
                        <div class="prop-spec-val">${escapeHtml(asset.branch || 'PKB PEKANBARU Branch')}</div>
                    </div>
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-calendar-check"></i> Tahun Pembuatan Unit</div>
                        <div class="prop-spec-val">${escapeHtml(asset.year_manufacture || '2023')}</div>
                    </div>
                </div>

                <!-- ROW 6 (Grey Striped) -->
                <div class="prop-spec-row">
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-heart-pulse"></i> Mean Time Between Failures (MTBF)</div>
                        <div class="prop-spec-val">168 Jam Operasi Normal</div>
                    </div>
                    <div class="prop-spec-cell">
                        <div class="prop-spec-label"><i class="fa-solid fa-stopwatch"></i> Mean Time to Repair (MTTR)</div>
                        <div class="prop-spec-val">4.5 Jam Perbaikan Rata-rata</div>
                    </div>
                </div>
            `;
        }
    }

    /**
     * Leaflet Map Initializer for Properties
     */
    function initPropertiesMap(lat, lng, locName, asset) {
        const mapContainer = document.getElementById('propUnitMap');
        if (!mapContainer || typeof window.L === 'undefined') return;

        if (propMapInstance) {
            try {
                propMapInstance.remove();
            } catch (e) {
                console.warn('Map cleanup warning:', e);
            }
            propMapInstance = null;
        }

        try {
            propMapInstance = L.map('propUnitMap', {
                zoomControl: true,
                attributionControl: false
            }).setView([lat, lng], 14);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19
            }).addTo(propMapInstance);

            // Add Mining Site Geofences
            Object.entries(siteLocations).forEach(([name, data]) => {
                const isCurrent = name === locName;
                L.circle([data.lat, data.lng], {
                    color: data.color,
                    fillColor: data.color,
                    fillOpacity: isCurrent ? 0.25 : 0.1,
                    radius: data.radius,
                    weight: isCurrent ? 3 : 1.5,
                    dashArray: isCurrent ? null : '4, 4'
                }).bindPopup(`<strong>Geofence Site: ${name}</strong><br>Radius: ${data.radius} Meter`).addTo(propMapInstance);
            });

            // Add GPS Trail Breadcrumbs Polyline
            const trailPoints = [
                [lat - 0.0035, lng - 0.0028],
                [lat - 0.0022, lng - 0.0019],
                [lat - 0.0012, lng - 0.0009],
                [lat, lng]
            ];
            L.polyline(trailPoints, {
                color: '#0284c7',
                weight: 4,
                opacity: 0.8,
                dashArray: '6, 6'
            }).addTo(propMapInstance);

            // Marker for Current Unit Position
            const customIcon = L.divIcon({
                className: 'prop-map-unit-marker',
                html: `
                    <div style="background:#0284c7; color:#fff; padding:4px 8px; border-radius:6px; font-weight:800; font-size:11px; font-family:'JetBrains Mono',monospace; box-shadow:0 3px 8px rgba(0,0,0,0.3); border:2px solid #fff; white-space:nowrap; display:flex; align-items:center; gap:4px;">
                        <i class="fa-solid fa-truck-front"></i> ${escapeHtml(asset.id || 'UNIT')}
                    </div>
                `,
                iconSize: [80, 30],
                iconAnchor: [40, 15]
            });

            L.marker([lat, lng], { icon: customIcon })
                .bindPopup(`<strong>${escapeHtml(asset.id)}</strong><br>${escapeHtml(asset.make_model || '')}<br>Lokasi: ${escapeHtml(locName)}`)
                .addTo(propMapInstance)
                .openPopup();

            setTimeout(() => {
                if (propMapInstance) propMapInstance.invalidateSize();
            }, 200);
        } catch (err) {
            console.warn('Leaflet initialization exception:', err);
        }
    }

    /**
     * Render SECTION 3: Maintenance Plans, WOs & Spare Parts
     */
    function renderMaintenanceAndWorkOrders(asset, hash) {
        const curHm = Number(asset.last_hm_km || 34500);

        // PM Plans Table
        const tbPm = document.getElementById('propPmPlansTableBody');
        if (tbPm) {
            const intervals = [250, 500, 1000, 2000];
            let rows = '';
            intervals.forEach(inv => {
                const last = Math.floor(curHm / inv) * inv;
                const next = last + inv;
                const delta = next - curHm;
                const badge = delta < 0 ? '<span class="p2h-badge fail">Terlambat</span>' : (delta <= 50 ? '<span class="p2h-badge warning">Due Soon</span>' : '<span class="p2h-badge pass">Terjadwal</span>');
                const cost = inv === 250 ? 'Rp 2.500.000' : (inv === 500 ? 'Rp 4.850.000' : (inv === 1000 ? 'Rp 9.500.000' : 'Rp 16.000.000'));

                rows += `
                    <tr>
                        <td><strong>PS ${inv}</strong></td>
                        <td>${inv} Jam</td>
                        <td>${last.toLocaleString('id-ID')} HM</td>
                        <td><strong>${next.toLocaleString('id-ID')} HM</strong></td>
                        <td style="font-weight:700; color:${delta < 0 ? '#dc2626' : '#0284c7'};">${delta > 0 ? '+' : ''}${delta} HM</td>
                        <td>${badge}</td>
                        <td>${cost}</td>
                    </tr>
                `;
            });
            tbPm.innerHTML = rows;
        }

        // Work Orders Table
        const tbWo = document.getElementById('propWoTableBody');
        if (tbWo) {
            tbWo.innerHTML = `
                <tr>
                    <td><strong>WO-2026-08-0114</strong></td>
                    <td>Scheduled PM</td>
                    <td>Servis Berkala PS 500 (Ganti Oli Mesin, Filter Oli, Filter Solar, dan General Safety Check)</td>
                    <td>Joni Septian</td>
                    <td>5.5 Jam</td>
                    <td>Rp 4.850.000</td>
                    <td><span class="p2h-badge pass">Closed</span></td>
                </tr>
                <tr>
                    <td><strong>WO-2026-07-0092</strong></td>
                    <td>Corrective Breakdown</td>
                    <td>Penggantian hydraulic hose boom cylinder bocor dan bleeding hydraulic circuit</td>
                    <td>Afriyandi (Lead)</td>
                    <td>14.0 Jam</td>
                    <td>Rp 7.200.000</td>
                    <td><span class="p2h-badge pass">Closed</span></td>
                </tr>
            `;
        }

        // Spare Parts History Table
        const tbParts = document.getElementById('propPartsTableBody');
        if (tbParts) {
            tbParts.innerHTML = `
                <tr>
                    <td><code>15607-2190</code></td>
                    <td>Oil Filter Element Hino FM260/280</td>
                    <td>Filter</td>
                    <td>2 Pcs</td>
                    <td>Rp 570.000</td>
                    <td>SPB-2026-08-0114</td>
                    <td>10 Agu 2026</td>
                </tr>
                <tr>
                    <td><code>23304-EV090</code></td>
                    <td>Fuel Filter Primary & Secondary Set</td>
                    <td>Filter</td>
                    <td>1 Set</td>
                    <td>Rp 450.000</td>
                    <td>SPB-2026-08-0114</td>
                    <td>10 Agu 2026</td>
                </tr>
                <tr>
                    <td><code>OIL-MED-15W40</code></td>
                    <td>Oli Mesin Pertamina Meditran SX 15W-40</td>
                    <td>Pelumas</td>
                    <td>28 Liter</td>
                    <td>Rp 1.456.000</td>
                    <td>SPB-2026-08-0115</td>
                    <td>10 Agu 2026</td>
                </tr>
                <tr>
                    <td><code>HYD-HOSE-075-4W</code></td>
                    <td>Hydraulic Hose 3/4" 4SP 350 Bar + Fitting</td>
                    <td>Hidrolik</td>
                    <td>2 Pcs</td>
                    <td>Rp 3.700.000</td>
                    <td>SPB-2026-07-0092</td>
                    <td>24 Jul 2026</td>
                </tr>
            `;
        }
    }

    /**
     * Render SECTION 4: Diagnostics, 10-Tire Diagram, Oil PAP & P2H
     */
    function renderDiagnosticsAndTires(asset, hash) {
        // 10-Position Tire Blueprint Diagram
        const tireBlueprint = document.getElementById('propTireBlueprint');
        if (tireBlueprint) {
            tireBlueprint.innerHTML = `
                <!-- AXLE 1: FRONT STEERING -->
                <div class="prop-tire-axle-row">
                    <div class="prop-axle-line"></div>
                    <div class="prop-axle-label">AXLE 1 (STEERING)</div>
                    <div class="prop-wheel-group">
                        <div class="prop-tire-badge good" title="Posisi 1 - Depan Kiri">
                            <span class="tire-pos">P1 (FL)</span>
                            <span class="tire-psi">115 PSI</span>
                            <span class="tire-rtd">19 mm</span>
                        </div>
                    </div>
                    <div class="prop-wheel-group">
                        <div class="prop-tire-badge good" title="Posisi 2 - Depan Kanan">
                            <span class="tire-pos">P2 (FR)</span>
                            <span class="tire-psi">115 PSI</span>
                            <span class="tire-rtd">18 mm</span>
                        </div>
                    </div>
                </div>

                <!-- AXLE 2: DRIVE FORWARD (DUAL TIRE) -->
                <div class="prop-tire-axle-row">
                    <div class="prop-axle-line"></div>
                    <div class="prop-axle-label">AXLE 2 (DRIVE FORWARD)</div>
                    <div class="prop-wheel-group">
                        <div class="prop-tire-badge good" title="Posisi 3 - Tengah Luar Kiri">
                            <span class="tire-pos">P3 (RO)</span>
                            <span class="tire-psi">110 PSI</span>
                            <span class="tire-rtd">16 mm</span>
                        </div>
                        <div class="prop-tire-badge good" title="Posisi 4 - Tengah Dalam Kiri">
                            <span class="tire-pos">P4 (RI)</span>
                            <span class="tire-psi">112 PSI</span>
                            <span class="tire-rtd">15 mm</span>
                        </div>
                    </div>
                    <div class="prop-wheel-group">
                        <div class="prop-tire-badge warning" title="Posisi 5 - Tengah Dalam Kanan (Perlu Pantau)">
                            <span class="tire-pos">P5 (RI)</span>
                            <span class="tire-psi">102 PSI</span>
                            <span class="tire-rtd">13 mm</span>
                        </div>
                        <div class="prop-tire-badge good" title="Posisi 6 - Tengah Luar Kanan">
                            <span class="tire-pos">P6 (RO)</span>
                            <span class="tire-psi">110 PSI</span>
                            <span class="tire-rtd">16 mm</span>
                        </div>
                    </div>
                </div>

                <!-- AXLE 3: DRIVE REAR (DUAL TIRE) -->
                <div class="prop-tire-axle-row">
                    <div class="prop-axle-line"></div>
                    <div class="prop-axle-label">AXLE 3 (DRIVE REAR)</div>
                    <div class="prop-wheel-group">
                        <div class="prop-tire-badge good" title="Posisi 7 - Belakang Luar Kiri">
                            <span class="tire-pos">P7 (RO)</span>
                            <span class="tire-psi">114 PSI</span>
                            <span class="tire-rtd">17 mm</span>
                        </div>
                        <div class="prop-tire-badge good" title="Posisi 8 - Belakang Dalam Kiri">
                            <span class="tire-pos">P8 (RI)</span>
                            <span class="tire-psi">115 PSI</span>
                            <span class="tire-rtd">17 mm</span>
                        </div>
                    </div>
                    <div class="prop-wheel-group">
                        <div class="prop-tire-badge good" title="Posisi 9 - Belakang Dalam Kanan">
                            <span class="tire-pos">P9 (RI)</span>
                            <span class="tire-psi">112 PSI</span>
                            <span class="tire-rtd">16 mm</span>
                        </div>
                        <div class="prop-tire-badge good" title="Posisi 10 - Belakang Luar Kanan">
                            <span class="tire-pos">P10 (RO)</span>
                            <span class="tire-psi">114 PSI</span>
                            <span class="tire-rtd">18 mm</span>
                        </div>
                    </div>
                </div>
            `;
        }

        // Oil PAP Sampling Lab Table
        const tbPap = document.getElementById('propOilPapTableBody');
        if (tbPap) {
            tbPap.innerHTML = `
                <tr>
                    <td><strong>Engine Oil</strong></td>
                    <td>10 Agu 2026</td>
                    <td>18 ppm</td>
                    <td>4 ppm</td>
                    <td>6 ppm</td>
                    <td>8 ppm</td>
                    <td>14.2 cSt</td>
                    <td><span class="p2h-badge pass">Normal</span></td>
                    <td>Laju keausan wear metals dalam batas aman operasional.</td>
                </tr>
                <tr>
                    <td><strong>Hydraulic System</strong></td>
                    <td>10 Agu 2026</td>
                    <td>7 ppm</td>
                    <td>2 ppm</td>
                    <td>3 ppm</td>
                    <td>5 ppm</td>
                    <td>45.8 cSt</td>
                    <td><span class="p2h-badge pass">Normal</span></td>
                    <td>Viskositas stabil. Kontaminasi partikulat sangat rendah.</td>
                </tr>
                <tr>
                    <td><strong>Differential Gear</strong></td>
                    <td>18 Jun 2026</td>
                    <td>24 ppm</td>
                    <td>8 ppm</td>
                    <td>5 ppm</td>
                    <td>11 ppm</td>
                    <td>28.4 cSt</td>
                    <td><span class="p2h-badge pass">Normal</span></td>
                    <td>Keausan gear train normal sesuai interval 1000 HM.</td>
                </tr>
            `;
        }

        // P2H Pre-Start History Table
        const tbP2h = document.getElementById('propP2hTableBody');
        if (tbP2h) {
            tbP2h.innerHTML = `
                <tr>
                    <td><strong>27 Agu 2026</strong></td>
                    <td>Shift 1 (Siang)</td>
                    <td>Taufiq H (Operator)</td>
                    <td><span class="p2h-badge pass">Lulus P2H</span></td>
                    <td>OK (110-115 PSI)</td>
                    <td>Nihil</td>
                    <td>Unit siap operasi normal penambangan.</td>
                </tr>
                <tr>
                    <td><strong>26 Agu 2026</strong></td>
                    <td>Shift 2 (Malam)</td>
                    <td>Rezeki Siregar (Operator)</td>
                    <td><span class="p2h-badge pass">Lulus P2H</span></td>
                    <td>OK</td>
                    <td>Nihil</td>
                    <td>Operasi hauling lancar tanpa kendala.</td>
                </tr>
                <tr>
                    <td><strong>25 Agu 2026</strong></td>
                    <td>Shift 1 (Siang)</td>
                    <td>Taufiq H (Operator)</td>
                    <td><span class="p2h-badge warning">Catatan</span></td>
                    <td>P5 agak kurang angin (102 PSI)</td>
                    <td>Nihil</td>
                    <td>Dilakukan top up angin ban P5 saat pergantian shift.</td>
                </tr>
            `;
        }

        // Accident Investigation Table
        const tbAccident = document.getElementById('propAccidentTableBody');
        if (tbAccident) {
            if (asset.id === 'DT-00052') {
                tbAccident.innerHTML = `
                    <tr>
                        <td><strong>02/ACC/BRA/YARD/2026</strong></td>
                        <td>20 Jun 2026</td>
                        <td>Yard KM 12 Area Workshop</td>
                        <td>Suwardi (Mekanik)</td>
                        <td><span class="p2h-badge fail">Minor</span></td>
                        <td>Engsel pintu ombeng tersangkut tiang penyangga saat manuver mundur.</td>
                        <td>Rp 4.500.000</td>
                        <td><span class="p2h-badge pass">CAPA Completed</span></td>
                    </tr>
                `;
            } else {
                tbAccident.innerHTML = `
                    <tr>
                        <td colspan="8" style="text-align:center; padding:18px; color:#64748b;">
                            <i class="fa-solid fa-shield-halved" style="color:#16a34a; margin-right:6px;"></i> 
                            Tidak ada riwayat kecelakaan atau insiden keselamatan untuk unit ini (Zero Accident).
                        </td>
                    </tr>
                `;
            }
        }
    }

    /**
     * Hash Router Initializer
     */
    function handleHashChange() {
        const hash = window.location.hash || '';
        if (hash.startsWith('#unit-properties')) {
            const urlParams = new URLSearchParams(hash.replace(/^#unit-properties\??/, ''));
            const unitId = urlParams.get('id') || 'DT-00049';
            window.openUnitProperties(unitId);
        }
    }

    window.addEventListener('hashchange', handleHashChange);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(handleHashChange, 200);
        });
        setTimeout(handleHashChange, 200);
    }

    // Expose loadHullHistory globally for real-time refresh after modal rename
    window.loadHullHistory = loadHullHistory;

})();
