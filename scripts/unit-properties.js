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
    let incidentDetailTrigger = null;
    const NO_DATA = 'Data belum tersedia';

    function displayData(value) {
        if (value === null || value === undefined || String(value).trim() === '') return NO_DATA;
        const normalized = String(value).trim().toLowerCase();
        if (['belum tersedia', 'belum ada pic', 'belum tercatat', 'unknown', '-'].includes(normalized)) {
            return NO_DATA;
        }
        return String(value);
    }

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

    /** Open one database-backed safety incident in the compact detail modal. */
    window.openPropIncidentDetail = function (rowIndex) {
        const incidents = Array.isArray(currentPropPayload?.accidents) ? currentPropPayload.accidents : [];
        const incident = incidents[Number(rowIndex)];
        if (!incident) return;

        incidentDetailTrigger = document.activeElement;
        const values = {
            mpiReportNumber: displayData(incident.accident_id),
            mpiIncidentDate: formatDisplayDateTime(incident.incident_datetime || incident.report_date),
            mpiLocation: displayData(incident.location_name),
            mpiSeverity: displayData(incident.severity),
            mpiChronology: displayData(incident.chronology)
        };

        Object.entries(values).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) element.textContent = value;
        });

        if (typeof window.openModal === 'function') {
            window.openModal('modalPropIncidentDetail');
            window.setTimeout(() => document.getElementById('mpiCloseButton')?.focus(), 0);
        }
    };

    document.addEventListener('click', event => {
        const closeButton = event.target.closest('#modalPropIncidentDetail [onclick*="closeModal"]');
        if (!closeButton || !incidentDetailTrigger) return;
        window.setTimeout(() => incidentDetailTrigger?.focus(), 0);
    });

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
        if (!dateStr || String(dateStr).toLowerCase() === 'belum tersedia') return NO_DATA;
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
        if (!dateStr) return NO_DATA;
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
            const hull = asset.hull_number || '';
            const hullEl = document.getElementById('propUnitHull');
            if (hullEl) hullEl.textContent = displayData(hull);
        } catch (error) {
            console.warn('Gagal memuat data onboarding unit:', error);
        }
    }

    function renderAssetOnboarding(asset) {
        if (!asset) return;
        const show = value => escapeHtml(displayData(value));
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
                        <i class="fa-solid fa-file-shield"></i> STNK: <span class="prop-doc-muted-text">${NO_DATA}</span>
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
                        <i class="fa-solid fa-certificate"></i> KIR: <span class="prop-doc-muted-text">${NO_DATA}</span>
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
                        <i class="fa-solid fa-file-contract"></i> SILO: <span class="prop-doc-muted-text">${NO_DATA}</span>
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
                        <i class="fa-solid fa-file-pdf"></i> PDF JO: <span class="prop-doc-muted-text">${NO_DATA}</span>
                    </span>
                `;
            }

            docsContainer.innerHTML = docsHtml;
        }

        // 2. Vitals Cards Onboarding (Operator, Servis Terakhir, Interval, Target, Estimasi)
        const opVal = document.getElementById('propVitalOperator');
        const opSub = document.getElementById('propVitalOperatorSub');
        if (opVal) opVal.textContent = displayData(asset.current_operator_name);
        if (opSub) {
            opSub.textContent = asset.operator_shift
                ? `Shift ${asset.operator_shift}`
                : (asset.current_operator_name ? 'Penugasan Aktif' : NO_DATA);
        }

        const srvVal = document.getElementById('propVitalLastService');
        const srvSub = document.getElementById('propVitalLastServiceSub');
        if (srvVal) srvVal.textContent = asset.last_service_date ? formatDisplayDate(asset.last_service_date) : NO_DATA;
        if (srvSub) {
            srvSub.textContent = asset.last_service_hm_km != null
                ? `${Number(asset.last_service_hm_km).toLocaleString('id-ID')} ${asset.service_interval_unit || 'HM'}`
                : NO_DATA;
        }

        const intVal = document.getElementById('propVitalInterval');
        const intSub = document.getElementById('propVitalIntervalSub');
        if (intVal) {
            intVal.textContent = asset.service_interval_val != null
                ? `${Number(asset.service_interval_val).toLocaleString('id-ID')} ${asset.service_interval_unit || 'HM'}`
                : NO_DATA;
        }
        if (intSub) intSub.textContent = 'Siklus Rutin PM';

        const tgtVal = document.getElementById('propVitalNextTarget');
        const tgtSub = document.getElementById('propVitalNextTargetSub');
        if (tgtVal) {
            tgtVal.textContent = asset.next_service_due_hm_km != null
                ? `${Number(asset.next_service_due_hm_km).toLocaleString('id-ID')} ${asset.service_interval_unit || 'HM'}`
                : NO_DATA;
        }
        if (tgtSub) tgtSub.textContent = 'Ambang Batas Jadwal PM';

        const estVal = document.getElementById('propVitalEstimate');
        const estSub = document.getElementById('propVitalEstimateSub');
        if (estVal) estVal.textContent = asset.next_service_estimated_date ? formatDisplayDate(asset.next_service_estimated_date) : NO_DATA;
        if (estSub) {
            estSub.textContent = asset.daily_usage_coefficient != null
                ? `Laju: ${asset.daily_usage_coefficient} ${asset.service_interval_unit || 'HM'}/hari`
                : NO_DATA;
        }

        // 3. Card Galeri Foto Unit (3x3 Layout di Sisi Kanan)
        renderGallerySection(photoRows);
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
        const currentHull = currentAssetOnboardingCache?.hull_number || '';
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
        if (assetCodeEl) assetCodeEl.textContent = `${currentPropUnitId} (${currentAssetOnboardingCache?.hull_number || NO_DATA})`;
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
     * Notification Toast Helper for Unit Properties
     */
    function showPropToast(message, isError = false) {
        let toast = document.getElementById('propToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'propToast';
            toast.className = 'prop-toast';
            document.body.appendChild(toast);
        }
        toast.className = `prop-toast ${isError ? 'prop-toast-error' : 'prop-toast-success'}`;
        toast.innerHTML = `<i class="fa-solid ${isError ? 'fa-circle-exclamation' : 'fa-circle-check'}"></i> <span>${escapeHtml(message)}</span>`;
        requestAnimationFrame(() => toast.classList.add('active'));
        clearTimeout(showPropToast.timer);
        showPropToast.timer = setTimeout(() => {
            toast.classList.remove('active');
        }, 3500);
    }

    /**
     * Render Gallery Section: 3x3 Grid or Empty State with Upload Trigger
     */
    function renderGallerySection(photoRows) {
        const galleryCountEl = document.getElementById('propGalleryCount');
        if (galleryCountEl) galleryCountEl.textContent = `${photoRows.length} Foto`;

        const galleryContainer = document.getElementById('propGalleryContainer');
        if (!galleryContainer) return;

        if (photoRows.length === 0) {
            galleryContainer.innerHTML = `
                <div class="prop-gallery-empty-state clickable" onclick="window.triggerPropPhotoUpload()" role="button" tabindex="0" title="Klik untuk upload foto unit (PNG, JPG, JPEG)">
                    <div class="prop-gallery-empty-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                    <div class="prop-gallery-empty-title">Belum ada foto unit</div>
                    <div class="prop-gallery-empty-desc">Klik di sini atau tombol <strong>+ Upload</strong> untuk menambah foto</div>
                    <div style="margin-top:6px; font-size:0.68rem; color:#94a3b8; font-weight:500;">Format: PNG, JPG, JPEG (Maks. 5 MB)</div>
                </div>
            `;
        } else {
            const photosGridHtml = photoRows.map(photo => {
                const photoUrl = `api/asset_files.php?kind=photo&photo_id=${encodeURIComponent(photo.photo_id)}`;
                return `
                    <div class="prop-gallery-thumb-item" onclick="window.openPropPhotoLightbox('${photoUrl}', '${escapeHtml(photo.photo_title)}', '${escapeHtml(photo.photo_angle || 'Foto')}', '${escapeHtml(photo.uploaded_at || '')}', ${Number(photo.photo_id)})" title="${escapeHtml(photo.photo_title)} (${escapeHtml(photo.photo_angle || 'Foto')})">
                        <img src="${photoUrl}" alt="${escapeHtml(photo.photo_title)}" loading="lazy">
                        <span class="prop-gallery-thumb-angle">${escapeHtml(photo.photo_angle || 'Foto')}</span>
                    </div>
                `;
            }).join('');

            galleryContainer.innerHTML = `<div class="prop-gallery-grid-3x3">${photosGridHtml}</div>`;
        }
    }

    /**
     * Trigger File Picker for Unit Photo Upload
     */
    window.triggerPropPhotoUpload = function () {
        if (!currentPropUnitId) {
            showPropToast('Pilih unit terlebih dahulu sebelum mengunggah foto.', true);
            return;
        }
        const fileInput = document.getElementById('propPhotoFileInput');
        if (fileInput) {
            fileInput.value = '';
            fileInput.click();
        }
    };

    /**
     * Handle File Selection and Upload directly to database
     */
    window.handlePropPhotoSelected = async function (input) {
        if (!input || !input.files || input.files.length === 0) return;
        const file = input.files[0];
        if (!currentPropUnitId) {
            showPropToast('Pilih unit terlebih dahulu.', true);
            input.value = '';
            return;
        }

        // Validasi ekstensi & format: Hanya PNG, JPG, JPEG
        const allowedExts = ['png', 'jpg', 'jpeg'];
        const ext = (file.name.split('.').pop() || '').toLowerCase();
        const allowedMime = ['image/png', 'image/jpeg'];

        if (!allowedExts.includes(ext) || (file.type && !allowedMime.includes(file.type))) {
            showPropToast('Format file tidak didukung! Hanya file PNG, JPG, atau JPEG yang diperbolehkan.', true);
            input.value = '';
            return;
        }

        // Validasi ukuran: Maksimal 5 MB
        const maxSizeBytes = 5 * 1024 * 1024;
        if (file.size > maxSizeBytes) {
            showPropToast('Ukuran foto terlalu besar! Maksimal ukuran file adalah 5 MB.', true);
            input.value = '';
            return;
        }

        const galleryContainer = document.getElementById('propGalleryContainer');
        const prevHtml = galleryContainer ? galleryContainer.innerHTML : '';
        if (galleryContainer) {
            galleryContainer.innerHTML = `
                <div class="prop-gallery-uploading-state">
                    <i class="fa-solid fa-spinner fa-spin fa-2x" style="color:#0284c7; margin-bottom:8px;"></i>
                    <div style="font-size:0.82rem; font-weight:700; color:#0f172a;">Menyimpan foto ke database...</div>
                    <div style="font-size:0.72rem; color:#64748b; margin-top:2px;">${escapeHtml(file.name)}</div>
                </div>
            `;
        }

        const uploadBtn = document.getElementById('btnPropUploadPhoto');
        if (uploadBtn) uploadBtn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('asset_id', currentPropUnitId);
            formData.append('kind', 'photo');
            formData.append('file', file);
            const titleWithoutExt = file.name.replace(/\.[^/.]+$/, '').replace(/[_-]/g, ' ');
            formData.append('title', titleWithoutExt || `Foto Unit ${currentPropUnitId}`);
            formData.append('angle', 'Other');

            const response = await fetch('api/asset_files.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const result = await response.json().catch(() => null);

            if (!response.ok || !result || result.status !== 'success') {
                throw new Error(result?.message || 'Gagal menyimpan foto ke database.');
            }

            showPropToast('Foto berhasil diunggah dan disimpan ke database!');
            await window.reloadPropGallery(currentPropUnitId);
        } catch (error) {
            console.error('Upload foto unit gagal:', error);
            showPropToast(error.message || 'Terjadi kesalahan saat mengunggah foto.', true);
            if (galleryContainer && prevHtml) {
                galleryContainer.innerHTML = prevHtml;
            }
        } finally {
            if (uploadBtn) uploadBtn.disabled = false;
            input.value = '';
        }
    };

    /**
     * Reload Gallery Photos for an Asset
     */
    window.reloadPropGallery = async function (assetId) {
        if (!assetId) return;
        try {
            const response = await fetch(`api/unit_properties.php?asset_id=${encodeURIComponent(assetId)}`, {
                credentials: 'same-origin',
                cache: 'no-store'
            });
            const result = await response.json();
            if (response.ok && result.status === 'success' && result.data?.asset) {
                currentPropPayload = result.data;
                const photoRows = Array.isArray(result.data.asset.gallery_photos) ? result.data.asset.gallery_photos : [];
                renderGallerySection(photoRows);
            }
        } catch (e) {
            console.warn('Gagal memuat ulang galeri:', e);
        }
    };

    /**
     * Delete Photo from Database and Server
     */
    window.deletePropPhoto = async function (photoId) {
        if (!photoId) return;
        if (!confirm('Apakah Anda yakin ingin menghapus foto ini dari galeri dan database?')) {
            return;
        }
        try {
            const response = await fetch(`api/asset_files.php?kind=photo&photo_id=${encodeURIComponent(photoId)}`, {
                method: 'DELETE',
                credentials: 'same-origin'
            });
            const result = await response.json().catch(() => null);
            if (!response.ok || !result || result.status !== 'success') {
                throw new Error(result?.message || 'Gagal menghapus foto.');
            }
            if (typeof window.closeModal === 'function') {
                window.closeModal('modalPropPhotoPreview');
            }
            showPropToast('Foto berhasil dihapus dari database.');
            if (currentPropUnitId) {
                await window.reloadPropGallery(currentPropUnitId);
            }
        } catch (err) {
            showPropToast(err.message || 'Gagal menghapus foto.', true);
        }
    };

    /**
     * Modal Controller: Photo Lightbox Preview
     */
    window.openPropPhotoLightbox = function (photoUrl, title, angle, uploadedAt, photoId) {
        const imgEl = document.getElementById('mplPreviewImg');
        const titleEl = document.getElementById('mplPhotoTitle');
        const angleEl = document.getElementById('mplPhotoAngle');
        const dateEl = document.getElementById('mplPhotoDate');
        const downloadBtn = document.getElementById('mplDownloadBtn');
        const deleteBtn = document.getElementById('mplDeleteBtn');

        if (imgEl) imgEl.src = photoUrl;
        if (titleEl) titleEl.textContent = title || 'Foto Unit';
        if (angleEl) angleEl.textContent = angle || 'Angle';
        if (dateEl) dateEl.textContent = uploadedAt ? `Diunggah: ${uploadedAt}` : '';
        if (downloadBtn) {
            downloadBtn.href = photoUrl;
            downloadBtn.target = '_blank';
        }

        if (deleteBtn) {
            if (photoId) {
                deleteBtn.style.display = 'inline-flex';
                deleteBtn.onclick = function () {
                    window.deletePropPhoto(photoId);
                };
            } else {
                deleteBtn.style.display = 'none';
            }
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
            ['propAccidentTableBody', 4]
        ].forEach(([id, colspan]) => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = emptyTableRow(colspan, 'Memuat data unit...');
        });
        const tire = document.getElementById('propTireBlueprint');
        if (tire) tire.innerHTML = '<div style="padding:22px; text-align:center; color:#64748b;">Memuat pemeriksaan ban...</div>';
        ['propGaugeRpm', 'propGaugeCoolant', 'propGaugeOilPress', 'propGaugeVolt'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            el.textContent = NO_DATA;
            el.classList.add('prop-gauge-no-data');
            const unit = el.nextElementSibling;
            if (unit?.classList.contains('prop-gauge-unit')) unit.hidden = true;
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
            ['propAccidentTableBody', 4]
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
        if (modelEl) modelEl.textContent = displayData(asset.make_model);

        const plateEl = document.getElementById('propUnitPlate');
        if (plateEl) plateEl.textContent = displayData(asset.license_plate);

        const serialEl = document.getElementById('propUnitSerial');
        if (serialEl) serialEl.textContent = displayData(asset.serial_number);
        const hullEl = document.getElementById('propUnitHull');
        if (hullEl) hullEl.textContent = displayData(asset.hull_number);

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
            const st = asset.status ? String(asset.status).toUpperCase() : NO_DATA;
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
        const hmVal = hasMeter ? rawMeter.toLocaleString('id-ID') : NO_DATA;
        const elVitalHm = document.getElementById('propVitalHm');
        if (elVitalHm) elVitalHm.textContent = hasMeter ? `${hmVal} HM/KM` : hmVal;

        const elVitalLoc = document.getElementById('propVitalLoc');
        if (elVitalLoc) elVitalLoc.textContent = displayData(asset.location || asset.location_name);

        const elVitalHealth = document.getElementById('propVitalHealth');
        if (elVitalHealth) elVitalHealth.textContent = asset.health_score != null ? `${asset.health_score}%` : NO_DATA;

        const elVitalNextService = document.getElementById('propVitalNextService');
        if (elVitalNextService) elVitalNextService.textContent = asset.next_service_due_hm_km != null
            ? `${Number(asset.next_service_due_hm_km).toLocaleString('id-ID')} ${asset.service_interval_unit || 'HM'} · Est. ${asset.next_service_estimated_date || NO_DATA}`
            : NO_DATA;

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
            if (!el) return;
            const missing = value == null || value === '';
            el.textContent = missing ? NO_DATA : value;
            el.classList.toggle('prop-gauge-no-data', missing);
            const unit = el.nextElementSibling;
            if (unit?.classList.contains('prop-gauge-unit')) unit.hidden = missing;
        });

        const tbGps = document.getElementById('propGpsLogsTableBody');
        if (tbGps) {
            tbGps.innerHTML = gpsLogs.length
                ? gpsLogs.map(log => {
                    const ignition = displayData(log.ignition_status);
                    const badgeClass = ignition === 'ON' ? 'pass' : (ignition === 'IDLING' ? 'warning' : 'fail');
                    const speed = log.speed_kmh == null ? NO_DATA : `${Number(log.speed_kmh).toLocaleString('id-ID')} km/h`;
                    return `<tr>
                        <td><strong>${escapeHtml(formatDisplayDateTime(log.recorded_at))}</strong></td>
                        <td>${escapeHtml(log.latitude)}, ${escapeHtml(log.longitude)}</td>
                        <td>${escapeHtml(speed)}</td>
                        <td><span class="p2h-badge ${badgeClass}">${escapeHtml(ignition)}</span></td>
                    </tr>`;
                }).join('')
                : emptyTableRow(4, NO_DATA);
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
        const value = raw => displayData(raw);
        const metric = raw => raw == null
            ? NO_DATA
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
            mapContainer.innerHTML = `<div style="height:100%; display:flex; align-items:center; justify-content:center; color:#64748b; text-align:center; padding:24px;">${NO_DATA}</div>`;
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
                .bindPopup(`<strong>${escapeHtml(asset.asset_id || asset.id)}</strong><br>${escapeHtml(displayData(asset.make_model))}<br>Lokasi: ${escapeHtml(displayData(asset.location_name || asset.raw_location_notes))}`)
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
        const formatMeter = value => value == null ? NO_DATA : Number(value).toLocaleString('id-ID');
        const formatMoney = value => value == null
            ? NO_DATA
            : new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value));
        const statusBadge = status => {
            const normalized = displayData(status);
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
                    const deltaLabel = delta == null ? NO_DATA : `${delta > 0 ? '+' : ''}${delta.toLocaleString('id-ID')} HM`;
                    return `<tr>
                        <td><strong>PM ${escapeHtml(plan.interval_hm)} HM</strong></td>
                        <td>${escapeHtml(plan.interval_hm)} Jam</td>
                        <td>${Number(plan.last_service_hm) > 0 ? `${formatMeter(plan.last_service_hm)} HM` : NO_DATA}</td>
                        <td><strong>${hasTarget ? `${formatMeter(plan.target_due_hm)} HM` : NO_DATA}</strong></td>
                        <td>${escapeHtml(deltaLabel)}</td>
                        <td>${statusBadge(plan.status)}</td>
                        <td>${NO_DATA}</td>
                    </tr>`;
                }).join('')
                : emptyTableRow(7, NO_DATA);
        }

        const tbWoLive = document.getElementById('propWoTableBody');
        if (tbWoLive) {
            tbWoLive.innerHTML = workOrders.length
                ? workOrders.map(wo => {
                    const issue = wo.repair_action
                        ? `${wo.issue_description || ''} / Tindakan: ${wo.repair_action}`
                        : displayData(wo.issue_description);
                    const downtime = wo.downtime_formatted || (wo.downtime_minutes == null ? NO_DATA : `${wo.downtime_minutes} menit`);
                    return `<tr>
                        <td><strong>${escapeHtml(wo.wo_id)}</strong></td>
                        <td>${escapeHtml(displayData(wo.maintenance_type))}</td>
                        <td>${escapeHtml(issue)}</td>
                        <td>${escapeHtml(displayData(wo.assigned_mechanic))}</td>
                        <td>${escapeHtml(downtime)}</td>
                        <td>${escapeHtml(formatMoney(wo.total_cost))}</td>
                        <td>${statusBadge(wo.status)}</td>
                    </tr>`;
                }).join('')
                : emptyTableRow(7, NO_DATA);
        }

        const tbPartsLive = document.getElementById('propPartsTableBody');
        if (tbPartsLive) {
            tbPartsLive.innerHTML = partUsage.length
                ? partUsage.map(item => `<tr>
                    <td><code>${escapeHtml(displayData(item.part_number))}</code></td>
                    <td>${escapeHtml(displayData(item.part_name))}</td>
                    <td>${escapeHtml(displayData(item.category))}</td>
                    <td>${escapeHtml(item.quantity)} ${escapeHtml(item.unit_measure || '')}</td>
                    <td>${NO_DATA}</td>
                    <td>${escapeHtml(displayData(item.reference_number))}</td>
                    <td>${escapeHtml(formatDisplayDate(item.transaction_date))}</td>
                </tr>`).join('')
                : emptyTableRow(7, NO_DATA);
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
            const assetCategory = String(payload.asset?.category || '').trim();
            const isTracked = ['Excavator', 'Bulldozer', 'Reclaimer Spreader'].includes(assetCategory);

            if (tires.length > 0) {
                const tireMap = {};
                tires.forEach(t => {
                    const pos = String(t.tire_position || '').trim().toUpperCase();
                    tireMap[pos] = t;
                });

                const renderBadge = (posCode, label) => {
                    const tire = tireMap[posCode];
                    if (!tire) return '';
                    const color = String(tire.condition_color || '').toUpperCase();
                    const cls = color === 'RED' ? 'danger' : (color === 'YELLOW' ? 'warning' : 'good');
                    const pressure = Number(tire.air_pressure_psi) > 0 ? `${tire.air_pressure_psi} PSI` : NO_DATA;
                    const tread = Number(tire.tread_depth_mm) > 0 ? `${tire.tread_depth_mm} mm` : NO_DATA;
                    const serial = tire.tire_serial_no ? `SN: ${tire.tire_serial_no}` : '';
                    const inspectDate = tire.inspected_at ? ` | Insp: ${formatDisplayDate(tire.inspected_at)}` : '';
                    const tooltip = `${label || posCode} | ${serial}${inspectDate} | ${pressure} | ${tread}`;
                    return `<div class="prop-tire-badge ${cls}" title="${escapeHtml(tooltip)}">
                        <span class="tire-pos">${escapeHtml(posCode)}</span>
                        <span class="tire-psi">${escapeHtml(pressure)}</span>
                        <span class="tire-rtd">${escapeHtml(tread)}</span>
                    </div>`;
                };

                const hasSteer = Boolean(tireMap['FL'] || tireMap['FR']);
                const hasR1Dual = Boolean(tireMap['R1LO'] || tireMap['R1LI'] || tireMap['R1RI'] || tireMap['R1RO']);
                const hasR1Single = Boolean(tireMap['R1L'] || tireMap['R1R']);
                const hasR2Dual = Boolean(tireMap['R2LO'] || tireMap['R2LI'] || tireMap['R2RI'] || tireMap['R2RO']);
                const hasR2Single = Boolean(tireMap['R2L'] || tireMap['R2R'] || tireMap['RL'] || tireMap['RR']);
                const hasTrailer = Boolean(tireMap['T1LO'] || tireMap['T1LI'] || tireMap['T1RI'] || tireMap['T1RO']);
                const hasSpare = Boolean(tireMap['SPARE']);

                const handledPositions = new Set();
                let axlesHtml = '';

                if (hasSteer) {
                    handledPositions.add('FL');
                    handledPositions.add('FR');
                    axlesHtml += `
                    <div class="prop-tire-axle-row">
                        <div class="prop-axle-line"></div>
                        <div class="prop-axle-label">AXLE 1 (STEER FRONT)</div>
                        <div class="prop-wheel-group">
                            ${renderBadge('FL', 'Depan Kiri (FL)')}
                        </div>
                        <div class="prop-wheel-group">
                            ${renderBadge('FR', 'Depan Kanan (FR)')}
                        </div>
                    </div>`;
                }

                if (hasR1Dual) {
                    ['R1LO', 'R1LI', 'R1RI', 'R1RO'].forEach(p => handledPositions.add(p));
                    axlesHtml += `
                    <div class="prop-tire-axle-row">
                        <div class="prop-axle-line"></div>
                        <div class="prop-axle-label">AXLE 2 (DRIVE MID)</div>
                        <div class="prop-wheel-group">
                            ${renderBadge('R1LO', 'Tengah Luar Kiri (R1LO)')}
                            ${renderBadge('R1LI', 'Tengah Dalam Kiri (R1LI)')}
                        </div>
                        <div class="prop-wheel-group">
                            ${renderBadge('R1RI', 'Tengah Dalam Kanan (R1RI)')}
                            ${renderBadge('R1RO', 'Tengah Luar Kanan (R1RO)')}
                        </div>
                    </div>`;
                } else if (hasR1Single) {
                    ['R1L', 'R1R'].forEach(p => handledPositions.add(p));
                    axlesHtml += `
                    <div class="prop-tire-axle-row">
                        <div class="prop-axle-line"></div>
                        <div class="prop-axle-label">AXLE 2 (MID)</div>
                        <div class="prop-wheel-group">
                            ${renderBadge('R1L', 'Tengah Kiri (R1L)')}
                        </div>
                        <div class="prop-wheel-group">
                            ${renderBadge('R1R', 'Tengah Kanan (R1R)')}
                        </div>
                    </div>`;
                }

                if (hasR2Dual) {
                    ['R2LO', 'R2LI', 'R2RI', 'R2RO'].forEach(p => handledPositions.add(p));
                    axlesHtml += `
                    <div class="prop-tire-axle-row">
                        <div class="prop-axle-line"></div>
                        <div class="prop-axle-label">AXLE 3 (DRIVE REAR)</div>
                        <div class="prop-wheel-group">
                            ${renderBadge('R2LO', 'Belakang Luar Kiri (R2LO)')}
                            ${renderBadge('R2LI', 'Belakang Dalam Kiri (R2LI)')}
                        </div>
                        <div class="prop-wheel-group">
                            ${renderBadge('R2RI', 'Belakang Dalam Kanan (R2RI)')}
                            ${renderBadge('R2RO', 'Belakang Luar Kanan (R2RO)')}
                        </div>
                    </div>`;
                } else if (hasR2Single) {
                    const lPos = tireMap['R2L'] ? 'R2L' : 'RL';
                    const rPos = tireMap['R2R'] ? 'R2R' : 'RR';
                    handledPositions.add(lPos);
                    handledPositions.add(rPos);
                    axlesHtml += `
                    <div class="prop-tire-axle-row">
                        <div class="prop-axle-line"></div>
                        <div class="prop-axle-label">${hasR1Single ? 'AXLE 3 (REAR)' : 'AXLE 2 (DRIVE REAR)'}</div>
                        <div class="prop-wheel-group">
                            ${renderBadge(lPos, 'Belakang Kiri')}
                        </div>
                        <div class="prop-wheel-group">
                            ${renderBadge(rPos, 'Belakang Kanan')}
                        </div>
                    </div>`;
                }

                if (hasTrailer) {
                    ['T1LO', 'T1LI', 'T1RI', 'T1RO'].forEach(p => handledPositions.add(p));
                    axlesHtml += `
                    <div class="prop-tire-axle-row">
                        <div class="prop-axle-line"></div>
                        <div class="prop-axle-label">AXLE 4 (TRAILER / TAG)</div>
                        <div class="prop-wheel-group">
                            ${renderBadge('T1LO', 'Trailer Luar Kiri (T1LO)')}
                            ${renderBadge('T1LI', 'Trailer Dalam Kiri (T1LI)')}
                        </div>
                        <div class="prop-wheel-group">
                            ${renderBadge('T1RI', 'Trailer Dalam Kanan (T1RI)')}
                            ${renderBadge('T1RO', 'Trailer Luar Kanan (T1RO)')}
                        </div>
                    </div>`;
                }

                if (hasSpare) {
                    handledPositions.add('SPARE');
                    axlesHtml += `
                    <div style="display:flex; justify-content:center; align-items:center; gap:8px; margin-top:4px; padding-top:6px; border-top:1px dashed #334155; width:100%; max-width:440px;">
                        <span style="font-size:0.68rem; color:#94a3b8; font-weight:700;">BAN CADANGAN:</span>
                        ${renderBadge('SPARE', 'Ban Cadangan (Spare Tire)')}
                    </div>`;
                }

                const unhandledTires = tires.filter(t => !handledPositions.has(String(t.tire_position || '').trim().toUpperCase()));
                if (unhandledTires.length > 0) {
                    axlesHtml += `
                    <div style="display:flex; flex-wrap:wrap; justify-content:center; gap:8px; margin-top:8px; padding-top:6px; border-top:1px dashed #334155; width:100%; max-width:440px;">
                        ${unhandledTires.map(t => renderBadge(t.tire_position, t.tire_position)).join('')}
                    </div>`;
                }

                tireBlueprintLive.innerHTML = axlesHtml || `<div style="padding:22px; text-align:center; color:#64748b;">${NO_DATA}</div>`;
            } else if (isTracked) {
                tireBlueprintLive.innerHTML = `
                <div style="padding: 24px; text-align: center; color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-link" style="font-size: 2rem; color: #38bdf8;"></i>
                    <strong style="color: #f1f5f9; font-size: 0.95rem;">Sistem Penggerak: Crawler Undercarriage (Rantai Baja)</strong>
                    <span style="font-size: 0.78rem; max-width: 420px; line-height: 1.4; color:#94a3b8;">
                        Unit tipe perayap (crawler) beroperasi menggunakan sistem track link, track shoe, idler, sprocket, dan roller. Tidak menggunakan konfigurasi ban pneumatik.
                    </span>
                </div>`;
            } else {
                tireBlueprintLive.innerHTML = `<div style="padding:22px; text-align:center; color:#64748b;">${NO_DATA}</div>`;
            }
        }

        const tbPapLive = document.getElementById('propOilPapTableBody');
        if (tbPapLive) {
            tbPapLive.innerHTML = oilRows.length
                ? oilRows.map(row => `<tr>
                    <td>${escapeHtml(displayData(row.compartment))}</td>
                    <td>${escapeHtml(formatDisplayDate(row.sample_date))}</td>
                    <td>${escapeHtml(displayData(row.iron_fe))}</td>
                    <td>${escapeHtml(displayData(row.copper_cu))}</td>
                    <td>${escapeHtml(displayData(row.aluminium_al))}</td>
                    <td>${escapeHtml(displayData(row.silicon_si))}</td>
                    <td>${escapeHtml(displayData(row.viscosity))}</td>
                    <td>${escapeHtml(displayData(row.status))}</td>
                    <td>${escapeHtml(displayData(row.lab_diagnosis))}</td>
                </tr>`).join('')
                : emptyTableRow(9, NO_DATA);
        }

        const tbP2hLive = document.getElementById('propP2hTableBody');
        if (tbP2hLive) {
            tbP2hLive.innerHTML = inspections.length
                ? inspections.map(inspection => {
                    const detail = inspection.payload || {};
                    return `<tr>
                        <td><strong>${escapeHtml(formatDisplayDateTime(inspection.inspection_date))}</strong></td>
                        <td>${escapeHtml(displayData(detail.shift || detail.shiftName))}</td>
                        <td>${escapeHtml(displayData(detail.operatorName || detail.operator))}</td>
                        <td>${escapeHtml(displayData(inspection.overall_result || detail.status))}</td>
                        <td>${escapeHtml(displayData(detail.tireCondition || detail.kondisiBan))}</td>
                        <td>${escapeHtml(displayData(detail.leakage || detail.kebocoran))}</td>
                        <td>${escapeHtml(displayData(inspection.findings_summary || detail.notes))}</td>
                    </tr>`;
                }).join('')
                : emptyTableRow(7, NO_DATA);
        }

        const tbAccidentLive = document.getElementById('propAccidentTableBody');
        if (tbAccidentLive) {
            tbAccidentLive.innerHTML = accidents.length
                ? accidents.map((accident, index) => {
                    const reportNumber = displayData(accident.accident_id);
                    return `<tr>
                        <td><strong>${escapeHtml(reportNumber)}</strong></td>
                        <td>${escapeHtml(formatDisplayDateTime(accident.incident_datetime || accident.report_date))}</td>
                        <td>${escapeHtml(displayData(accident.operator_name))}</td>
                        <td><button type="button" class="prop-incident-detail-button" onclick="window.openPropIncidentDetail(${index})" aria-label="Lihat detail laporan ${escapeHtml(reportNumber)}"><i class="fa-solid fa-eye" aria-hidden="true"></i> Detail</button></td>
                    </tr>`;
                }).join('')
                : emptyTableRow(4, NO_DATA);
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
