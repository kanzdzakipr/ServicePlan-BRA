/**
 * ServicePlan-BRA - Heavy Equipment Properties & Diagnostics Inspector
 * Module: scripts/unit-properties.js (Unified Single-View Spec Sheet Layout)
 */

(function () {
    'use strict';

    let currentPropUnitId = null;
    let currentPropPayload = null;
    let previousViewName = 'dashboard';
    let propMapInstance = null;

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

    /** Find an existing asset only; never manufacture a fallback asset. */
    function getAssetData(assetId) {
        if (!window.globalData || !Array.isArray(window.globalData.assets)) return null;
        const cleanId = String(assetId || '').trim().toUpperCase();
        return window.globalData.assets.find(a => {
            if (!a) return false;
            return [a.id, a.asset_id, a.asset_code]
                .some(value => String(value || '').trim().toUpperCase() === cleanId);
        }) || null;
    }

    /**
     * Open Unit Properties View in Main Content (Unified Single-View)
     */
    window.openUnitProperties = async function (assetId) {
        if (!assetId) return;
        const exactAsset = getAssetData(assetId);
        const requestedId = String(exactAsset?.asset_id || exactAsset?.id || assetId).trim();
        currentPropUnitId = requestedId;
        currentPropPayload = null;

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
            window.history.replaceState(null, '', `#unit-properties?id=${encodeURIComponent(requestedId)}`);
        }

        // Populate unit selector dropdown
        populateUnitSelector(requestedId);
        renderPropertiesLoading(requestedId);

        try {
            const response = await fetch(`api/unit_properties.php?asset_id=${encodeURIComponent(requestedId)}`, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Cache-Control': 'no-cache' }
            });
            const result = await response.json();
            if (!response.ok || result.status !== 'success' || !result.data?.asset) {
                throw new Error(result.message || 'Data properties unit tidak dapat dimuat.');
            }
            if (currentPropUnitId !== requestedId) return;
            currentPropPayload = result.data;
            renderUnitPropertiesData(result.data);
            loadHullHistory(requestedId);
        } catch (error) {
            if (currentPropUnitId !== requestedId) return;
            console.warn('Gagal memuat properties unit:', error);
            renderPropertiesError(requestedId, error.message || 'Data tidak dapat dimuat.');
        }
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

    function formatDisplayDateTime(dateStr) {
        if (!dateStr) return 'Belum tersedia';
        const parsed = new Date(String(dateStr).replace(' ', 'T'));
        if (Number.isNaN(parsed.getTime())) return String(dateStr);
        return parsed.toLocaleString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
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
                const id = String(a.asset_id || a.id || '').trim();
                if (!id) return;
                const isSel = id === selectedId ? 'selected' : '';
                const category = a.category ? ` - ${escapeHtml(a.category)}` : '';
                const location = a.location ? ` (${escapeHtml(a.location)})` : '';
                options += `<option value="${escapeHtml(id)}" ${isSel}>${escapeHtml(id)}${category}${location}</option>`;
            });
        } else {
            options = '<option value="">Data aset belum tersedia</option>';
        }
        selectEl.innerHTML = options;
    }

    function emptyTableRow(colspan, message) {
        return `<tr><td colspan="${colspan}" style="text-align:center; padding:18px; color:#64748b;">${escapeHtml(message)}</td></tr>`;
    }

    function renderPropertiesLoading(assetId) {
        document.querySelectorAll('.prop-render-unit-id').forEach(el => { el.textContent = assetId; });
        const listEl = document.getElementById('propSpecListGrid');
        if (listEl) listEl.innerHTML = '<div style="grid-column:1/-1; padding:22px; text-align:center; color:#64748b;">Memuat data asli unit...</div>';
        [
            ['propGpsLogsTableBody', 4], ['propPmPlansTableBody', 7],
            ['propWoTableBody', 7], ['propPartsTableBody', 7],
            ['propOilPapTableBody', 9], ['propP2hTableBody', 7],
            ['propAccidentTableBody', 8]
        ].forEach(([id, colspan]) => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = emptyTableRow(colspan, 'Memuat data unit...');
        });
        const tire = document.getElementById('propTireBlueprint');
        if (tire) tire.innerHTML = '<div style="padding:22px; text-align:center; color:#64748b;">Memuat pemeriksaan ban...</div>';
        ['propGaugeRpm', 'propGaugeCoolant', 'propGaugeOilPress', 'propGaugeVolt'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = '—';
        });
    }

    function renderPropertiesError(assetId, message) {
        renderPropertiesLoading(assetId);
        const listEl = document.getElementById('propSpecListGrid');
        if (listEl) listEl.innerHTML = `<div style="grid-column:1/-1; padding:22px; text-align:center; color:#b91c1c;">${escapeHtml(message)}</div>`;
        [
            ['propGpsLogsTableBody', 4], ['propPmPlansTableBody', 7],
            ['propWoTableBody', 7], ['propPartsTableBody', 7],
            ['propOilPapTableBody', 9], ['propP2hTableBody', 7],
            ['propAccidentTableBody', 8]
        ].forEach(([id, colspan]) => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = emptyTableRow(colspan, message);
        });
    }

    /**
     * Render Complete Unit Properties View (Unified Single Page)
     */
    function renderUnitPropertiesData(payload) {
        const asset = payload.asset;
        const assetId = String(asset.asset_id || asset.id || currentPropUnitId || '');

        // 1. Identity & Spec Header
        const unitIdEls = document.querySelectorAll('.prop-render-unit-id');
        unitIdEls.forEach(el => el.textContent = asset.id || assetId);

        const modelEl = document.getElementById('propUnitModel');
        if (modelEl) modelEl.textContent = asset.make_model || 'Belum tersedia';

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
            const st = asset.status ? String(asset.status).toUpperCase() : 'BELUM TERSEDIA';
            let statusCls = 'operating';
            if (st === 'READY') statusCls = 'ready';
            else if (st === 'STANDBY') statusCls = 'standby';
            else if (st === 'BREAKDOWN') statusCls = 'breakdown';
            else if (st === 'ACCIDENT HOLD' || st.includes('ACCIDENT')) statusCls = 'accident';

            statusPill.className = `prop-status-pill ${statusCls}`;
            statusPill.innerHTML = `<span class="prop-pulse-dot"></span> ${escapeHtml(st)}`;
        }

        // Vitals
        const rawMeter = Number(asset.last_hm_km);
        const hasMeter = Number.isFinite(rawMeter) && rawMeter > 0;
        const hmVal = hasMeter ? rawMeter.toLocaleString('id-ID') : 'Belum tersedia';
        const elVitalHm = document.getElementById('propVitalHm');
        if (elVitalHm) elVitalHm.textContent = hasMeter ? `${hmVal} HM/KM` : hmVal;

        const elVitalLoc = document.getElementById('propVitalLoc');
        if (elVitalLoc) elVitalLoc.textContent = asset.location || asset.location_name || 'Belum tersedia';

        const elVitalHealth = document.getElementById('propVitalHealth');
        if (elVitalHealth) elVitalHealth.textContent = asset.health_score != null ? `${asset.health_score}%` : 'Belum tersedia';

        const elVitalNextService = document.getElementById('propVitalNextService');
        if (elVitalNextService) elVitalNextService.textContent = asset.next_service_due_hm_km != null
            ? `${Number(asset.next_service_due_hm_km).toLocaleString('id-ID')} ${asset.service_interval_unit || 'HM'} · Est. ${asset.next_service_estimated_date || 'belum tersedia'}`
            : 'Belum tersedia';

        // SECTION 1: Top Live Map & Telemetry CANbus
        renderTopLocationAndTelemetry(asset, payload);

        // SECTION 2: Technical Specifications Listing (Non-Card)
        renderTechnicalSpecsListing(asset, payload.calculated || {});

        // SECTION 3: Maintenance Plans, WOs & Spare Parts
        renderMaintenanceAndWorkOrders(payload);

        // SECTION 4: Diagnostics, 10-Tire Diagram, Oil PAP & P2H
        renderDiagnosticsAndTires(payload);
    }

    /**
     * Render SECTION 1: Top Live Map & Telemetry CANbus
     */
    function renderTopLocationAndTelemetry(asset, payload) {
        const gpsLogs = Array.isArray(payload.gps_logs) ? payload.gps_logs : [];
        const latestGps = gpsLogs[0] || null;
        const rawLat = latestGps?.latitude ?? asset.last_latitude;
        const rawLng = latestGps?.longitude ?? asset.last_longitude;
        const lat = rawLat == null || rawLat === '' ? NaN : Number(rawLat);
        const lng = rawLng == null || rawLng === '' ? NaN : Number(rawLng);
        const hasCoordinate = Number.isFinite(lat) && Number.isFinite(lng);

        setTimeout(() => initPropertiesMap(
            hasCoordinate ? lat : null,
            hasCoordinate ? lng : null,
            asset,
            gpsLogs
        ), 100);

        const canbus = payload.canbus || {};
        const gaugeValues = {
            propGaugeRpm: canbus.engine_rpm,
            propGaugeCoolant: canbus.coolant_temperature_c,
            propGaugeOilPress: canbus.oil_pressure_bar,
            propGaugeVolt: canbus.alternator_voltage
        };
        Object.entries(gaugeValues).forEach(([id, value]) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value == null || value === '' ? '—' : value;
        });

        const tbGps = document.getElementById('propGpsLogsTableBody');
        if (tbGps) {
            tbGps.innerHTML = gpsLogs.length
                ? gpsLogs.map(log => {
                    const ignition = String(log.ignition_status || 'Belum tersedia');
                    const badgeClass = ignition === 'ON' ? 'pass' : (ignition === 'IDLING' ? 'warning' : 'fail');
                    const speed = log.speed_kmh == null ? 'Belum tersedia' : `${Number(log.speed_kmh).toLocaleString('id-ID')} km/h`;
                    return `<tr>
                        <td><strong>${escapeHtml(formatDisplayDateTime(log.recorded_at))}</strong></td>
                        <td>${escapeHtml(log.latitude)}, ${escapeHtml(log.longitude)}</td>
                        <td>${escapeHtml(speed)}</td>
                        <td><span class="p2h-badge ${badgeClass}">${escapeHtml(ignition)}</span></td>
                    </tr>`;
                }).join('')
                : emptyTableRow(4, 'Belum ada riwayat GPS untuk unit ini.');
        }

        // BAST Movements History Table
        const tbMov = document.getElementById('propMovementTableBody');
        if (tbMov) {
            const movements = Array.isArray(payload.movements) ? payload.movements : [];
            if (movements.length > 0) {
                tbMov.innerHTML = movements.map(m => {
                    const dest = m.to_location || asset.location || 'Yard Duri';
                    const orig = m.from_location || 'Yard KM 12';
                    return `
                        <tr>
                            <td><strong>${escapeHtml(m.bast_number || 'BAST/MUT/AUTO')}</strong></td>
                            <td>${escapeHtml(formatDisplayDate(m.movement_date))}</td>
                            <td>${escapeHtml(orig)} &rarr; <a href="javascript:void(0)" class="site-prop-hover-link" onclick="window.openSiteProperties('${escapeHtml(dest)}', 'unit-properties'); return false;" title="Buka Detail Lembar Site ${escapeHtml(dest)}"><span class="site-name-text">${escapeHtml(dest)}</span> <i class="fa-solid fa-arrow-up-right-from-square site-prop-hover-icon"></i></a></td>
                            <td>Mobilisasi unit untuk operasional site.</td>
                            <td><span class="p2h-badge pass">Approved</span></td>
                        </tr>
                    `;
                }).join('');
            } else {
                const locName = asset.location || asset.location_name || 'Yard Duri';
                tbMov.innerHTML = `
                    <tr>
                        <td><strong>BAST/MUT/2026/08/042</strong></td>
                        <td>14 Agu 2026</td>
                        <td>Yard KM 12 &rarr; <a href="javascript:void(0)" class="site-prop-hover-link" onclick="window.openSiteProperties('${escapeHtml(locName)}', 'unit-properties'); return false;" title="Buka Detail Lembar Site ${escapeHtml(locName)}"><span class="site-name-text">${escapeHtml(locName)}</span> <i class="fa-solid fa-arrow-up-right-from-square site-prop-hover-icon"></i></a></td>
                        <td>Mobilisasi unit untuk operasional tambang.</td>
                        <td><span class="p2h-badge pass">Approved</span></td>
                    </tr>
                `;
            }
        }
    }

    /**
     * Render SECTION 2: Technical Specifications Listing (Non-Card Format)
     */
    function renderTechnicalSpecsListing(asset, calculated) {
        const value = raw => raw == null || String(raw).trim() === '' ? 'Belum tersedia' : String(raw);
        const metric = raw => raw == null
            ? 'Belum dapat dihitung'
            : `${Number(raw).toLocaleString('id-ID', { maximumFractionDigits: 2 })} Jam`;
        const row = (leftIcon, leftLabel, leftValue, rightIcon, rightLabel, rightValue) => `
            <div class="prop-spec-row">
                <div class="prop-spec-cell"><div class="prop-spec-label"><i class="fa-solid ${leftIcon}"></i> ${escapeHtml(leftLabel)}</div><div class="prop-spec-val">${escapeHtml(value(leftValue))}</div></div>
                <div class="prop-spec-cell"><div class="prop-spec-label"><i class="fa-solid ${rightIcon}"></i> ${escapeHtml(rightLabel)}</div><div class="prop-spec-val">${escapeHtml(value(rightValue))}</div></div>
            </div>`;
        const listEl = document.getElementById('propSpecListGrid');
        if (!listEl) return;
        listEl.innerHTML = [
            row('fa-truck', 'Tipe & Kategori Alat', asset.category || asset.type, 'fa-tag', 'Merek / Model Unit', asset.make_model),
            row('fa-barcode', 'Nomor Seri (SN)', asset.serial_number, 'fa-id-card', 'Nomor Polisi', asset.license_plate),
            row('fa-shield-halved', 'Status Kepemilikan', asset.ownership, 'fa-building-flag', 'Cabang & Wilayah Operasi', asset.sub_group_branch),
            row('fa-calendar-check', 'Tahun Pembuatan Unit', asset.year_manufacture, 'fa-location-dot', 'Lokasi Aktif', asset.location_name || asset.raw_location_notes),
            row('fa-gears', 'Model Mesin (Powertrain)', asset.engine_model, 'fa-code-branch', 'Transmisi & Drive Train', asset.transmission_drive_train),
            row('fa-weight-hanging', 'Kapasitas Muatan / GVW', asset.payload_gvw, 'fa-road', 'Konfigurasi Penggerak / Drive', asset.drive_configuration),
            row('fa-oil-can', 'Kapasitas Pelumas & Hidrolik', asset.lubricant_hydraulic_capacity, 'fa-gas-pump', 'Kapasitas Tangki Bahan Bakar', asset.fuel_tank_capacity),
            row('fa-heart-pulse', 'Mean Time Between Failures (MTBF)', metric(calculated.mtbf_hours), 'fa-stopwatch', 'Mean Time to Repair (MTTR)', metric(calculated.mttr_hours))
        ].join('');
    }

    /**
     * Leaflet Map Initializer for Properties
     */
    function initPropertiesMap(lat, lng, asset, gpsLogs) {
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

        if (lat == null || lng == null) {
            mapContainer.innerHTML = '<div style="height:100%; display:flex; align-items:center; justify-content:center; color:#64748b; text-align:center; padding:24px;">Koordinat unit belum tersedia.</div>';
            return;
        }

        try {
            mapContainer.innerHTML = '';
            propMapInstance = L.map('propUnitMap', {
                zoomControl: true,
                attributionControl: false
            }).setView([lat, lng], 14);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19
            }).addTo(propMapInstance);

            const trailPoints = (gpsLogs || [])
                .map(log => [Number(log.latitude), Number(log.longitude)])
                .filter(point => Number.isFinite(point[0]) && Number.isFinite(point[1]))
                .reverse();
            if (trailPoints.length > 1) {
                L.polyline(trailPoints, { color: '#0284c7', weight: 4, opacity: 0.8 }).addTo(propMapInstance);
            }

            // Marker for Current Unit Position
            const customIcon = L.divIcon({
                className: 'prop-map-unit-marker',
                html: `
                    <div style="background:#0284c7; color:#fff; padding:4px 8px; border-radius:6px; font-weight:800; font-size:11px; font-family:'JetBrains Mono',monospace; box-shadow:0 3px 8px rgba(0,0,0,0.3); border:2px solid #fff; white-space:nowrap; display:flex; align-items:center; gap:4px;">
                        <i class="fa-solid fa-truck-front"></i> ${escapeHtml(asset.asset_id || asset.id)}
                    </div>
                `,
                iconSize: [80, 30],
                iconAnchor: [40, 15]
            });

            L.marker([lat, lng], { icon: customIcon })
                .bindPopup(`<strong>${escapeHtml(asset.asset_id || asset.id)}</strong><br>${escapeHtml(asset.make_model || 'Model belum tersedia')}<br>Lokasi: ${escapeHtml(asset.location_name || asset.raw_location_notes || 'Belum tersedia')}`)
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
    function renderMaintenanceAndWorkOrders(payload) {
        const pmPlans = Array.isArray(payload.pm_plans) ? payload.pm_plans : [];
        const workOrders = Array.isArray(payload.work_orders) ? payload.work_orders : [];
        const partUsage = Array.isArray(payload.part_usage) ? payload.part_usage : [];
        const formatMeter = value => value == null ? 'Belum tersedia' : Number(value).toLocaleString('id-ID');
        const formatMoney = value => value == null
            ? 'Belum tersedia'
            : new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));
        const statusBadge = status => {
            const normalized = String(status || 'Belum tersedia');
            const upper = normalized.toUpperCase();
            const cls = ['CLOSED', 'COMPLETED'].includes(upper) ? 'pass'
                : (['DUE_SOON', 'IN PROGRESS', 'WAITING PART', 'TESTING'].includes(upper) ? 'warning'
                    : (['OVERDUE', 'CANCELLED'].includes(upper) ? 'fail' : ''));
            return `<span class="p2h-badge ${cls}">${escapeHtml(normalized)}</span>`;
        };

        const tbPmLive = document.getElementById('propPmPlansTableBody');
        if (tbPmLive) {
            tbPmLive.innerHTML = pmPlans.length
                ? pmPlans.map(plan => {
                    const hasTarget = Number(plan.target_due_hm) > 0;
                    const hasCurrent = Number(plan.current_smr) > 0;
                    const delta = hasTarget && hasCurrent
                        ? Number(plan.target_due_hm) - Number(plan.current_smr)
                        : null;
                    const deltaLabel = delta == null ? 'Belum dapat dihitung' : `${delta > 0 ? '+' : ''}${delta.toLocaleString('id-ID')} HM`;
                    return `<tr>
                        <td><strong>PM ${escapeHtml(plan.interval_hm)} HM</strong></td>
                        <td>${escapeHtml(plan.interval_hm)} Jam</td>
                        <td>${Number(plan.last_service_hm) > 0 ? `${formatMeter(plan.last_service_hm)} HM` : 'Belum tersedia'}</td>
                        <td><strong>${hasTarget ? `${formatMeter(plan.target_due_hm)} HM` : 'Belum tersedia'}</strong></td>
                        <td>${escapeHtml(deltaLabel)}</td>
                        <td>${statusBadge(plan.status)}</td>
                        <td>Belum tersedia</td>
                    </tr>`;
                }).join('')
                : emptyTableRow(7, 'Belum ada rencana PM yang terhubung ke unit ini.');
        }

        const tbWoLive = document.getElementById('propWoTableBody');
        if (tbWoLive) {
            tbWoLive.innerHTML = workOrders.length
                ? workOrders.map(wo => {
                    const issue = wo.repair_action
                        ? `${wo.issue_description || ''} / Tindakan: ${wo.repair_action}`
                        : (wo.issue_description || 'Belum tersedia');
                    const downtime = wo.downtime_formatted || (wo.downtime_minutes == null ? 'Belum tersedia' : `${wo.downtime_minutes} menit`);
                    return `<tr>
                        <td><strong>${escapeHtml(wo.wo_id)}</strong></td>
                        <td>${escapeHtml(wo.maintenance_type || 'Belum tersedia')}</td>
                        <td>${escapeHtml(issue)}</td>
                        <td>${escapeHtml(wo.assigned_mechanic || 'Belum tersedia')}</td>
                        <td>${escapeHtml(downtime)}</td>
                        <td>${escapeHtml(formatMoney(wo.total_cost))}</td>
                        <td>${statusBadge(wo.status)}</td>
                    </tr>`;
                }).join('')
                : emptyTableRow(7, 'Belum ada Work Order yang terhubung ke unit ini.');
        }

        const tbPartsLive = document.getElementById('propPartsTableBody');
        if (tbPartsLive) {
            tbPartsLive.innerHTML = partUsage.length
                ? partUsage.map(item => `<tr>
                    <td><code>${escapeHtml(item.part_number || 'Belum tersedia')}</code></td>
                    <td>${escapeHtml(item.part_name || 'Belum tersedia')}</td>
                    <td>${escapeHtml(item.category || 'Belum tersedia')}</td>
                    <td>${escapeHtml(item.quantity)} ${escapeHtml(item.unit_measure || '')}</td>
                    <td>Belum tersedia</td>
                    <td>${escapeHtml(item.reference_number || 'Belum tersedia')}</td>
                    <td>${escapeHtml(formatDisplayDate(item.transaction_date))}</td>
                </tr>`).join('')
                : emptyTableRow(7, 'Belum ada konsumsi suku cadang dengan asset_id unit ini.');
        }
    }

    /**
     * Render SECTION 4: Diagnostics, 10-Tire Diagram, Oil PAP & P2H
     */
    function renderDiagnosticsAndTires(payload) {
        const tires = Array.isArray(payload.tire_inspections) ? payload.tire_inspections : [];
        const oilRows = Array.isArray(payload.oil_analysis) ? payload.oil_analysis : [];
        const inspections = Array.isArray(payload.inspections) ? payload.inspections : [];
        const accidents = Array.isArray(payload.accidents) ? payload.accidents : [];

        const tireBlueprintLive = document.getElementById('propTireBlueprint');
        if (tireBlueprintLive) {
            tireBlueprintLive.innerHTML = tires.length
                ? `<div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(105px,1fr)); gap:10px; width:100%;">${tires.map(tire => {
                    const color = String(tire.condition_color || '').toUpperCase();
                    const cls = color === 'RED' ? 'danger' : (color === 'YELLOW' ? 'warning' : 'good');
                    const pressure = Number(tire.air_pressure_psi) > 0 ? `${tire.air_pressure_psi} PSI` : '—';
                    const tread = Number(tire.tread_depth_mm) > 0 ? `${tire.tread_depth_mm} mm` : '—';
                    return `<div class="prop-tire-badge ${cls}" title="Inspeksi ${escapeHtml(formatDisplayDateTime(tire.inspected_at))}">
                        <span class="tire-pos">${escapeHtml(tire.tire_position)}</span>
                        <span class="tire-psi">${escapeHtml(pressure)}</span>
                        <span class="tire-rtd">${escapeHtml(tread)}</span>
                    </div>`;
                }).join('')}</div>`
                : '<div style="padding:22px; text-align:center; color:#64748b;">Belum ada pemeriksaan ban untuk unit ini.</div>';
        }

        const tbPapLive = document.getElementById('propOilPapTableBody');
        if (tbPapLive) {
            tbPapLive.innerHTML = oilRows.length
                ? oilRows.map(row => `<tr>
                    <td>${escapeHtml(row.compartment || 'Belum tersedia')}</td>
                    <td>${escapeHtml(formatDisplayDate(row.sample_date))}</td>
                    <td>${escapeHtml(row.iron_fe ?? 'Belum tersedia')}</td>
                    <td>${escapeHtml(row.copper_cu ?? 'Belum tersedia')}</td>
                    <td>${escapeHtml(row.aluminium_al ?? 'Belum tersedia')}</td>
                    <td>${escapeHtml(row.silicon_si ?? 'Belum tersedia')}</td>
                    <td>${escapeHtml(row.viscosity ?? 'Belum tersedia')}</td>
                    <td>${escapeHtml(row.status ?? 'Belum tersedia')}</td>
                    <td>${escapeHtml(row.lab_diagnosis ?? 'Belum tersedia')}</td>
                </tr>`).join('')
                : emptyTableRow(9, 'Belum ada sumber data PAP yang terhubung ke unit ini.');
        }

        const tbP2hLive = document.getElementById('propP2hTableBody');
        if (tbP2hLive) {
            tbP2hLive.innerHTML = inspections.length
                ? inspections.map(inspection => {
                    const detail = inspection.payload || {};
                    return `<tr>
                        <td><strong>${escapeHtml(formatDisplayDateTime(inspection.inspection_date))}</strong></td>
                        <td>${escapeHtml(detail.shift || detail.shiftName || 'Belum tersedia')}</td>
                        <td>${escapeHtml(detail.operatorName || detail.operator || 'Belum tersedia')}</td>
                        <td>${escapeHtml(inspection.overall_result || detail.status || 'Belum tersedia')}</td>
                        <td>${escapeHtml(detail.tireCondition || detail.kondisiBan || 'Belum tersedia')}</td>
                        <td>${escapeHtml(detail.leakage || detail.kebocoran || 'Belum tersedia')}</td>
                        <td>${escapeHtml(inspection.findings_summary || detail.notes || 'Belum tersedia')}</td>
                    </tr>`;
                }).join('')
                : emptyTableRow(7, 'Belum ada inspeksi P2H yang terhubung ke unit ini.');
        }

        const tbAccidentLive = document.getElementById('propAccidentTableBody');
        if (tbAccidentLive) {
            tbAccidentLive.innerHTML = accidents.length
                ? accidents.map(accident => {
                    const loss = accident.total_financial_loss ?? accident.estimated_repair_cost;
                    const lossLabel = loss == null || Number(loss) <= 0
                        ? 'Belum tersedia'
                        : new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(loss));
                    return `<tr>
                        <td><strong>${escapeHtml(accident.accident_id)}</strong></td>
                        <td>${escapeHtml(formatDisplayDateTime(accident.incident_datetime))}</td>
                        <td>${escapeHtml(accident.location_name || 'Belum tersedia')}</td>
                        <td>${escapeHtml(accident.operator_name || 'Belum tersedia')}</td>
                        <td>${escapeHtml(accident.severity || 'Belum tersedia')}</td>
                        <td>${escapeHtml(accident.chronology || 'Belum tersedia')}</td>
                        <td>${escapeHtml(lossLabel)}</td>
                        <td>${escapeHtml(accident.status || 'Belum tersedia')}</td>
                    </tr>`;
                }).join('')
                : emptyTableRow(8, 'Belum ada laporan insiden yang tercatat untuk unit ini; status Zero Accident tidak diasumsikan.');
        }
    }

    /**
     * Hash Router Initializer
     */
    function handleHashChange() {
        const hash = window.location.hash || '';
        if (hash.startsWith('#unit-properties')) {
            const urlParams = new URLSearchParams(hash.replace(/^#unit-properties\??/, ''));
            const unitId = urlParams.get('id');
            if (unitId) window.openUnitProperties(unitId);
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
