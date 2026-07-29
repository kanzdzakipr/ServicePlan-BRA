(function () {
    'use strict';

    const WORKFLOW_STATE_KEY = 'fleetmonitor-workflow-state-v2';
    const state = {
        site: 'ALL',
        approvals: [],
        audit: []
    };

    const seedApprovals = [
        {
            id: 'SPB-2026-07-101',
            type: 'SPB Sparepart',
            requester: 'Foreman A',
            detail: 'Rp 7.500.000 (Filter Set)',
            project: 'Site Alpha',
            unitId: '',
            relatedWo: '',
            sla: '4 jam tersisa',
            risk: 'Tinggi',
            status: 'Pending'
        },
        {
            id: 'WO-26-892',
            type: 'Work Order Major',
            requester: 'Maintenance Planner',
            detail: 'Est. Rp 25.000.000 (Overhaul)',
            project: 'Site Alpha',
            unitId: '',
            relatedWo: 'WO-26-892',
            sla: '2 jam tersisa',
            risk: 'Kritis',
            status: 'Pending'
        },
        {
            id: 'TAR-01/05/2026',
            type: 'Rilis Unit Insiden',
            requester: 'HSE Head',
            detail: 'Insiden Moderate (CS-41001)',
            project: 'Sunter Area Stadium',
            unitId: 'CS-41001',
            relatedWo: '',
            sla: 'Melewati SLA',
            risk: 'Kritis',
            status: 'Pending'
        }
    ];

    const legacySubmitSpb = window.submitSpb;
    const legacySubmitWoStatusChange = window.submitWoStatusChange;
    const legacyOpenAssetModal = window.openAssetModal;
    const legacySyncFleetState = window.syncFleetState;

    function data() {
        return window.globalData || null;
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function parseDowntimeHours(value) {
        const text = String(value || '');
        const hours = Number((text.match(/(\d+)\s*jam/i) || [0, 0])[1]);
        const minutes = Number((text.match(/(\d+)\s*menit/i) || [0, 0])[1]);
        return hours + (minutes / 60);
    }

    function loadWorkflowState() {
        try {
            const saved = JSON.parse(localStorage.getItem(WORKFLOW_STATE_KEY) || 'null');
            if (saved && saved.version === 2) {
                state.approvals = Array.isArray(saved.approvals) ? saved.approvals : [];
                state.audit = Array.isArray(saved.audit) ? saved.audit : [];
            }
        } catch (error) {
            console.warn('Workflow state tidak dapat dipulihkan:', error);
        }
        if (!state.approvals.length) {
            state.approvals = seedApprovals.map(item => ({ ...item }));
        }
    }

    function persistWorkflowState() {
        try {
            localStorage.setItem(WORKFLOW_STATE_KEY, JSON.stringify({
                version: 2,
                approvals: state.approvals,
                audit: state.audit.slice(0, 100)
            }));
        } catch (error) {
            console.warn('Workflow state tidak dapat disimpan:', error);
        }
    }

    function formatNow() {
        return new Intl.DateTimeFormat('id-ID', {
            dateStyle: 'medium',
            timeStyle: 'short'
        }).format(new Date());
    }

    function notify(title, message, type = 'info') {
        let container = document.getElementById('fleetIntegrationNotifications');
        if (!container) {
            container = document.createElement('div');
            container.id = 'fleetIntegrationNotifications';
            container.setAttribute('aria-live', 'polite');
            container.style.cssText = 'position:fixed;right:20px;bottom:20px;z-index:5000;width:min(380px,calc(100vw - 40px));display:grid;gap:8px;';
            document.body.appendChild(container);
        }
        const colors = {
            info: 'var(--primary)',
            success: 'var(--success)',
            warning: 'var(--warning)',
            danger: 'var(--danger)'
        };
        const toast = document.createElement('div');
        toast.style.cssText = `background:#fff;border:1px solid var(--border);border-left:5px solid ${colors[type] || colors.info};border-radius:6px;box-shadow:0 4px 14px rgba(0,0,0,.16);padding:12px 14px;`;
        toast.innerHTML = `<strong style="display:block;margin-bottom:3px;font-size:.88rem;">${escapeHtml(title)}</strong><span style="font-size:.8rem;color:var(--text-muted);">${escapeHtml(message)}</span>`;
        container.appendChild(toast);
        window.setTimeout(() => toast.remove(), 4200);
    }

    function scopedAssets() {
        const current = data();
        if (!current) return [];
        if (state.site === 'ALL') return current.assets || [];
        return (current.assets || []).filter(asset => asset.location === state.site);
    }

    function scopedWorkOrders() {
        const current = data();
        if (!current) return [];
        if (state.site === 'ALL') return current.work_orders || [];
        const assetIds = new Set(scopedAssets().map(asset => asset.id));
        return (current.work_orders || []).filter(wo => assetIds.has(wo.assetId || wo.unitId));
    }

    function activeWorkOrder(assetId) {
        const current = data();
        if (!current) return null;
        const candidates = (current.work_orders || []).filter(wo =>
            (wo.assetId || wo.unitId) === assetId && wo.status !== 'Closed'
        );
        return candidates.find(wo => wo.priority === 'High') ||
            candidates.find(wo => wo.status === 'In Progress') ||
            candidates[0] ||
            null;
    }

    function effectiveSource(asset, wo) {
        return {
            source: asset.statusSource || (wo ? 'Work Order' : 'Master Asset'),
            reference: asset.statusReference || (wo ? wo.woId : 'Data awal')
        };
    }

    function statusBadge(status) {
        const cssClass = status === 'BREAKDOWN' || status === 'ACCIDENT_HOLD'
            ? 'bg-breakdown'
            : status === 'INSPEKSI'
                ? 'bg-inspeksi'
                : status === 'STANDBY'
                    ? 'bg-standby'
                    : 'bg-operating';
        return `<span class="badge ${cssClass}">${escapeHtml(status)}</span>`;
    }

    function renderDashboardMetrics() {
        const assets = scopedAssets();
        const counts = assets.reduce((result, asset) => {
            result[asset.status] = (result[asset.status] || 0) + 1;
            return result;
        }, {});
        const values = {
            kpiTotal: assets.length,
            kpiReady: counts.READY || 0,
            kpiStandby: counts.STANDBY || 0,
            kpiBreakdown: (counts.BREAKDOWN || 0) + (counts.ACCIDENT_HOLD || 0),
            kpiInspeksi: counts.INSPEKSI || 0
        };
        Object.entries(values).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) element.textContent = value;
        });
    }

    function renderDecisionSupportLegacy() {
        const container = document.getElementById('fleetDecisionSupport');
        if (!container) return;
        const riskRank = { ACCIDENT_HOLD: 5, BREAKDOWN: 4, INSPEKSI: 3, STANDBY: 2, READY: 1 };
        const risks = scopedAssets()
            .filter(asset => asset.status !== 'READY')
            .map(asset => {
                const wo = activeWorkOrder(asset.id);
                return {
                    asset,
                    wo,
                    score: (riskRank[asset.status] || 0) * 100000 + parseDowntimeHours(wo && wo.downtime)
                };
            })
            .sort((left, right) => right.score - left.score)
            .slice(0, 5);
        const workOrders = scopedWorkOrders();
        const active = workOrders.filter(wo => wo.status !== 'Closed');
        const pendingSpb = state.approvals.filter(item =>
            item.status === 'Pending' && /SPB|Sparepart/i.test(item.type)
        ).length;
        const pendingApproval = state.approvals.filter(item => item.status === 'Pending').length;
        const steps = [
            ['Master Asset', scopedAssets().length],
            ['Inspection Hold', scopedAssets().filter(asset => asset.status === 'INSPEKSI').length],
            ['WO Aktif', active.length],
            ['SPB Pending', pendingSpb],
            ['Approval Pending', pendingApproval],
            ['WO Closed', workOrders.filter(wo => wo.status === 'Closed').length]
        ];

        const riskHtml = risks.length
            ? risks.map(({ asset, wo }) => {
                const source = effectiveSource(asset, wo);
                const issue = wo ? (wo.issue || wo.description || 'Issue belum dirinci') : 'Belum memiliki WO aktif';
                return `
                    <li style="align-items:flex-start;">
                        <div class="attention-details" style="min-width:0;">
                            <strong>
                                <button type="button" onclick="openAssetModal('${escapeHtml(asset.id)}','${escapeHtml(asset.status)}','${escapeHtml(asset.category)}','${escapeHtml(asset.location)}')" style="border:0;background:none;padding:0;color:var(--primary);font-weight:700;cursor:pointer;text-decoration:underline;">${escapeHtml(asset.id)}</button>
                                ${statusBadge(asset.status)}
                            </strong>
                            <small style="display:block;margin-top:4px;">${escapeHtml(issue.length > 90 ? issue.slice(0, 90) + '…' : issue)}</small>
                            <small style="display:block;color:var(--text-muted);margin-top:3px;">${escapeHtml(source.source)} · ${escapeHtml(source.reference)} · ${escapeHtml(wo ? (wo.assignedTo || 'Belum ada PIC') : 'Belum ada PIC')}</small>
                        </div>
                        <button class="btn btn-primary" style="padding:6px 9px;white-space:nowrap;" onclick="${wo ? `openWoDetailView('${escapeHtml(wo.woId)}','${escapeHtml(asset.id)}')` : `openIntegratedWO('${escapeHtml(asset.id)}')`}">${wo ? 'Detail WO' : 'Buat WO'}</button>
                    </li>`;
            }).join('')
            : '<li><div class="attention-details"><strong>Tidak ada exception</strong><small>Semua unit dalam scope berstatus ready.</small></div></li>';

        container.innerHTML = `
            <div class="dashboard-layout" style="margin-bottom:20px;">
                <div class="panel">
                    <div class="panel-header">
                        <span><i class="fa-solid fa-triangle-exclamation"></i> Prioritas Risiko Armada</span>
                        <button class="btn btn-primary" style="padding:6px 10px;" onclick="showView('monitoring')">Lihat Monitoring</button>
                    </div>
                    <div class="panel-body" style="padding:10px 15px;">
                        <ul class="attention-list">${riskHtml}</ul>
                    </div>
                </div>
                <div class="panel">
                    <div class="panel-header"><span><i class="fa-solid fa-link"></i> Alur Maintenance Terintegrasi</span></div>
                    <div class="panel-body" style="padding:10px 15px;">
                        <ul class="attention-list">
                            ${steps.map((step, index) => `
                                <li>
                                    <div class="attention-details">
                                        <strong>${index + 1}. ${escapeHtml(step[0])}</strong>
                                    </div>
                                    <span class="badge ${step[1] > 0 ? 'bg-progress' : 'bg-operating'}">${step[1]}</span>
                                </li>`).join('')}
                        </ul>
                    </div>
                </div>
            </div>`;
    }

    function renderDecisionSupport() {
        const container = document.getElementById('fleetDecisionSupport');
        if (!container) return;
        const riskRank = { ACCIDENT_HOLD: 5, BREAKDOWN: 4, INSPEKSI: 3, STANDBY: 2, READY: 1 };
        const riskPool = scopedAssets()
            .filter(asset => asset.status !== 'READY')
            .map(asset => {
                const wo = activeWorkOrder(asset.id);
                return {
                    asset,
                    wo,
                    score: (riskRank[asset.status] || 0) * 100000 + parseDowntimeHours(wo && wo.downtime)
                };
            })
            .sort((left, right) => right.score - left.score);
        const risks = riskPool.slice(0, 4);
        const breakdownCount = riskPool.filter(item =>
            item.asset.status === 'BREAKDOWN' || item.asset.status === 'ACCIDENT_HOLD'
        ).length;
        const inspectionCount = riskPool.filter(item => item.asset.status === 'INSPEKSI').length;
        const workOrders = scopedWorkOrders();
        const active = workOrders.filter(wo => wo.status !== 'Closed');
        const pendingSpb = state.approvals.filter(item =>
            item.status === 'Pending' && /SPB|Sparepart/i.test(item.type)
        ).length;
        const pendingApproval = state.approvals.filter(item => item.status === 'Pending').length;
        const steps = [
            ['Master Asset', 'Sumber identitas unit', scopedAssets().length, 'asset'],
            ['Inspection Hold', 'Perlu review P2H', inspectionCount, 'inspection'],
            ['WO Aktif', 'Backlog pekerjaan berjalan', active.length, 'wo'],
            ['SPB Pending', 'Permintaan part diproses', pendingSpb, 'logistics'],
            ['Approval Pending', 'Perlu keputusan approver', pendingApproval, 'approval'],
            ['WO Closed', 'Riwayat pekerjaan selesai', workOrders.filter(wo => wo.status === 'Closed').length, 'wo']
        ];

        const riskHtml = risks.length
            ? risks.map(({ asset, wo }, index) => {
                const source = effectiveSource(asset, wo);
                const issue = wo ? (wo.issue || wo.description || 'Issue belum dirinci') : 'Belum memiliki WO aktif';
                const pic = wo ? (wo.assignedTo || 'Belum ditugaskan') : 'Belum ditugaskan';
                const downtime = wo ? (wo.downtime || 'Belum tersedia') : 'Belum ada WO';
                const statusLabel = asset.status === 'ACCIDENT_HOLD' ? 'SAFETY HOLD' : asset.status;
                return `
                    <article class="fleet-risk-item">
                        <span class="fleet-risk-rank">${String(index + 1).padStart(2, '0')}</span>
                        <div class="fleet-risk-content">
                            <div class="fleet-risk-title">
                                <button type="button" class="fleet-unit-link" onclick="openAssetModal('${escapeHtml(asset.id)}','${escapeHtml(asset.status)}','${escapeHtml(asset.category)}','${escapeHtml(asset.location)}')">${escapeHtml(asset.id)}</button>
                                <span class="badge ${asset.status === 'BREAKDOWN' || asset.status === 'ACCIDENT_HOLD' ? 'bg-breakdown' : asset.status === 'INSPEKSI' ? 'bg-inspeksi' : 'bg-standby'}">${escapeHtml(statusLabel)}</span>
                            </div>
                            <p class="fleet-risk-issue" title="${escapeHtml(issue)}">${escapeHtml(issue)}</p>
                            <div class="fleet-risk-meta">
                                <span><i class="fa-solid fa-location-dot"></i>${escapeHtml(asset.location || 'Site belum tersedia')}</span>
                                <span><i class="fa-solid fa-link"></i>${escapeHtml(source.source)} · ${escapeHtml(source.reference)}</span>
                                <span class="fleet-meta-alert"><i class="fa-regular fa-clock"></i>${escapeHtml(downtime)}</span>
                                <span><i class="fa-solid fa-user-gear"></i>${escapeHtml(pic)}</span>
                            </div>
                        </div>
                        <div class="fleet-risk-actions">
                            <button class="btn btn-primary" onclick="${wo ? `openWoDetailView('${escapeHtml(wo.woId)}','${escapeHtml(asset.id)}')` : `openIntegratedWO('${escapeHtml(asset.id)}')`}"><i class="fa-solid fa-wrench"></i> ${wo ? 'Buka WO' : 'Buat WO'}</button>
                            <button class="btn btn-secondary" onclick="openAssetModal('${escapeHtml(asset.id)}','${escapeHtml(asset.status)}','${escapeHtml(asset.category)}','${escapeHtml(asset.location)}')"><i class="fa-solid fa-truck"></i> Unit 360°</button>
                        </div>
                    </article>`;
            }).join('')
            : '<div class="fleet-risk-empty"><i class="fa-solid fa-circle-check"></i><strong>Tidak ada exception aktif</strong><br><small>Semua unit dalam scope berstatus ready.</small></div>';

        container.innerHTML = `
            <div class="fleet-decision-layout">
                <section class="panel">
                    <div class="panel-header">
                        <div class="fleet-panel-heading">
                            <strong><i class="fa-solid fa-triangle-exclamation"></i> Prioritas Risiko Armada</strong>
                            <small>Diurutkan berdasarkan tingkat risiko dan durasi downtime.</small>
                        </div>
                        <button class="btn btn-primary fleet-header-action" onclick="showView('monitoring')">Buka Monitoring <i class="fa-solid fa-arrow-right"></i></button>
                    </div>
                    <div class="fleet-risk-overview" aria-label="Ringkasan unit berisiko">
                        <div class="fleet-risk-metric">
                            <strong>${riskPool.length}</strong>
                            <span>Unit perlu perhatian</span>
                        </div>
                        <div class="fleet-risk-metric danger">
                            <strong>${breakdownCount}</strong>
                            <span>Breakdown / safety hold</span>
                        </div>
                        <div class="fleet-risk-metric warning">
                            <strong>${inspectionCount}</strong>
                            <span>Menunggu review P2H</span>
                        </div>
                    </div>
                    <div class="fleet-risk-list">${riskHtml}</div>
                    ${riskPool.length > risks.length ? `
                        <div class="fleet-risk-footer">
                            <span>Menampilkan ${risks.length} dari ${riskPool.length} unit prioritas.</span>
                            <button class="fleet-text-action" onclick="showView('monitoring')">Lihat ${riskPool.length - risks.length} unit lainnya <i class="fa-solid fa-arrow-right"></i></button>
                        </div>` : ''}
                </section>
                <section class="panel">
                    <div class="panel-header">
                        <div class="fleet-panel-heading">
                            <strong><i class="fa-solid fa-link"></i> Alur Maintenance</strong>
                            <small>Klik tahap untuk membuka data sumbernya.</small>
                        </div>
                    </div>
                    <div class="fleet-workflow-list">
                        ${steps.map((step, index) => `
                            <button class="fleet-workflow-step" onclick="showView('${escapeHtml(step[3])}')">
                                <span class="fleet-step-number">${index + 1}</span>
                                <span class="fleet-step-copy">
                                    <strong>${escapeHtml(step[0])}</strong>
                                    <small>${escapeHtml(step[1])}</small>
                                </span>
                                <span class="fleet-step-count">${step[2]}</span>
                            </button>`).join('')}
                    </div>
                </section>
            </div>`;
    }

    function renderMonitoring(assets) {
        const filterElement = document.getElementById('monitoringStatusFilter');
        const selectedStatus = filterElement ? filterElement.value : 'ALL';
        const allProblematic = (assets || []).filter(asset => asset.status !== 'READY');
        const filtered = selectedStatus === 'ALL'
            ? allProblematic
            : allProblematic.filter(asset => asset.status === selectedStatus);
        const summary = document.getElementById('monitoringSummary');
        if (summary) {
            const count = status => allProblematic.filter(asset => asset.status === status).length;
            summary.innerHTML = `
                <div><strong>${assets.length}</strong><span>Unit dalam scope</span></div>
                <div class="danger"><strong>${count('BREAKDOWN') + count('ACCIDENT_HOLD')}</strong><span>Breakdown / Hold</span></div>
                <div class="warning"><strong>${count('INSPEKSI')}</strong><span>Perlu P2H</span></div>
                <div class="info"><strong>${count('STANDBY')}</strong><span>Standby</span></div>
                <div><strong>${scopedWorkOrders().filter(wo => wo.status !== 'Closed').length}</strong><span>WO aktif</span></div>`;
        }
        const tbody = document.getElementById('monTableBody');
        if (!tbody) return;
        if (!filtered.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="monitoring-empty"><i class="fa-solid fa-circle-check"></i> Tidak ada unit untuk filter ini.</td></tr>';
            return;
        }
        const slaMinutes = Number((document.getElementById('cfg-sla-wo') || {}).value || 30);
        tbody.innerHTML = filtered
            .map(asset => ({ asset, wo: activeWorkOrder(asset.id) }))
            .sort((left, right) => parseDowntimeHours(right.wo && right.wo.downtime) - parseDowntimeHours(left.wo && left.wo.downtime))
            .map(({ asset, wo }) => {
                const source = effectiveSource(asset, wo);
                const overdue = wo && (
                    wo.priority === 'High' ||
                    (parseDowntimeHours(wo.downtime) * 60) > slaMinutes
                );
                const action = asset.status === 'BREAKDOWN' || asset.status === 'ACCIDENT_HOLD'
                    ? `<button class="integrated-action danger" onclick="openIntegratedWO('${escapeHtml(asset.id)}')"><i class="fa-solid fa-wrench"></i> ${wo ? escapeHtml(wo.woId) : 'Buat WO'}</button>`
                    : asset.status === 'INSPEKSI'
                        ? `<button class="integrated-action warning" onclick="openIntegratedP2H('${escapeHtml(asset.id)}','history')"><i class="fa-solid fa-clipboard-check"></i> Review P2H</button>`
                        : `<button class="integrated-action primary" onclick="openIntegratedStatusUpdate('${escapeHtml(asset.id)}')"><i class="fa-solid fa-pen"></i> Evaluasi</button>`;
                return `
                    <tr class="monitoring-unit-row">
                        <td>
                            <button class="unit-link-button" onclick="openAssetModal('${escapeHtml(asset.id)}','${escapeHtml(asset.status)}','${escapeHtml(asset.category)}','${escapeHtml(asset.location)}')">${escapeHtml(asset.id)}</button>
                            <small>${escapeHtml(asset.category)} · ${escapeHtml(asset.location)}</small>
                        </td>
                        <td>${statusBadge(asset.status)}</td>
                        <td>
                            <strong>${escapeHtml(source.source)}</strong>
                            <span class="status-source-line"><i class="fa-solid fa-link"></i> ${escapeHtml(source.reference)} · ${escapeHtml(asset.lastUpdate || 'Waktu belum tersedia')}</span>
                        </td>
                        <td>
                            <strong>${escapeHtml(wo ? wo.woId : 'Belum ada WO')}</strong>
                            <small>${escapeHtml(wo ? (wo.downtime || 'Downtime belum tersedia') : 'Buat WO bila diperlukan')}</small>
                        </td>
                        <td>
                            <strong>${escapeHtml(wo ? (wo.assignedTo || 'Belum ada PIC') : 'Belum ada PIC')}</strong>
                            <small style="display:block;margin-top:4px;color:${overdue ? 'var(--danger)' : 'var(--success)'};font-weight:700;">${overdue ? `Melewati SLA ${slaMinutes} menit` : 'Dalam SLA'}</small>
                        </td>
                        <td><div class="integrated-actions">${action}</div></td>
                    </tr>`;
            }).join('');
        if (typeof window.searchTableMonitoring === 'function') {
            window.searchTableMonitoring();
        }
    }

    window.initMonitoringView = function (assets) {
        const scoped = state.site === 'ALL'
            ? (assets || [])
            : (assets || []).filter(asset => asset.location === state.site);
        renderMonitoring(scoped);
    };

    function renderApprovalInbox() {
        const tbody = document.getElementById('approval-list');
        if (!tbody) return;
        const pending = state.approvals.filter(item => item.status === 'Pending');
        if (!pending.length) {
            tbody.innerHTML = '<tr><td colspan="5" style="padding:35px;text-align:center;color:var(--text-muted);"><i class="fa-solid fa-circle-check" style="color:var(--success);"></i> Tidak ada dokumen yang menunggu keputusan.</td></tr>';
        } else {
            tbody.innerHTML = pending.map(item => `
                <tr>
                    <td>
                        <strong>${escapeHtml(item.id)}</strong>
                        ${item.unitId || item.relatedWo ? `<small style="display:block;color:var(--text-muted);">${escapeHtml(item.unitId || item.relatedWo)}</small>` : ''}
                    </td>
                    <td>
                        ${escapeHtml(item.type)}
                        <small style="display:block;color:var(--text-muted);">${escapeHtml(item.project || 'Project belum tersedia')}</small>
                    </td>
                    <td>
                        ${escapeHtml(item.requester)}
                        <small style="display:block;color:${item.sla === 'Melewati SLA' ? 'var(--danger)' : 'var(--text-muted)'};">${escapeHtml(item.sla || 'SLA belum tersedia')}</small>
                    </td>
                    <td>
                        ${escapeHtml(item.detail)}
                        <small style="display:block;color:var(--text-muted);">Risiko: ${escapeHtml(item.risk || 'Normal')}</small>
                    </td>
                    <td style="white-space:nowrap;">
                        <button class="btn btn-success" style="padding:5px 10px;" onclick="decideIntegratedApproval('${escapeHtml(item.id)}','Approved')" title="Setujui"><i class="fa-solid fa-check"></i></button>
                        <button class="btn btn-danger" style="padding:5px 10px;" onclick="openIntegratedReject('${escapeHtml(item.id)}')" title="Tolak"><i class="fa-solid fa-xmark"></i></button>
                    </td>
                </tr>`).join('');
        }
        renderApprovalAudit();
    }

    function renderApprovalAudit() {
        const container = document.getElementById('approvalAuditTrail');
        if (!container) return;
        const entries = state.audit.slice(0, 8);
        container.innerHTML = `
            <div class="panel">
                <div class="panel-header"><span><i class="fa-solid fa-clock-rotate-left"></i> Audit Trail Keputusan</span></div>
                <div class="panel-body" style="padding:0;">
                    <div class="table-responsive">
                        <table style="margin:0;">
                            <thead><tr><th>Waktu</th><th>Dokumen</th><th>Tindakan</th><th>Actor</th><th>Catatan</th></tr></thead>
                            <tbody>
                                ${entries.length ? entries.map(entry => `
                                    <tr>
                                        <td>${escapeHtml(entry.at)}</td>
                                        <td><strong>${escapeHtml(entry.documentId)}</strong></td>
                                        <td><span class="badge ${entry.action === 'Rejected' ? 'bg-breakdown' : entry.action === 'Approved' || entry.action === 'Part Issued' ? 'bg-operating' : 'bg-progress'}">${escapeHtml(entry.action)}</span></td>
                                        <td>${escapeHtml(entry.actor)}</td>
                                        <td>${escapeHtml(entry.note)}</td>
                                    </tr>`).join('') : '<tr><td colspan="5" style="padding:25px;text-align:center;color:var(--text-muted);">Belum ada tindakan workflow.</td></tr>'}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>`;
    }

    function updateSourceDocument(approval, decision, note) {
        const current = data();
        if (!current) return;
        const wo = approval.relatedWo
            ? (current.work_orders || []).find(item => item.woId === approval.relatedWo)
            : null;
        if (wo) {
            wo.approvalStatus = decision;
            wo.approvalNote = note;
            wo.lastUpdate = new Date().toISOString();
        }
        const asset = approval.unitId
            ? (current.assets || []).find(item => item.id === approval.unitId || item.id.includes(approval.unitId))
            : null;
        if (asset && /Rilis Unit Insiden/i.test(approval.type)) {
            asset.safetyReleaseApproval = decision;
            if (decision === 'Approved' && asset.status === 'ACCIDENT_HOLD') {
                asset.status = 'INSPEKSI';
                asset.statusSource = 'HSE Release Approval';
                asset.statusReference = approval.id;
                asset.statusNote = 'Release disetujui; mandatory inspection tetap diperlukan.';
            }
        }
    }

    function commitApprovalDecision(id, decision, note) {
        const approval = state.approvals.find(item => item.id === id);
        if (!approval || approval.status !== 'Pending') return;
        approval.status = decision;
        approval.decisionNote = note || '';
        approval.decidedAt = new Date().toISOString();
        if (/SPB|Sparepart/i.test(approval.type)) {
            approval.fulfillmentStatus = decision === 'Approved' ? 'Ready to Issue' : 'Returned';
        }
        updateSourceDocument(approval, decision, note || '');
        state.audit.unshift({
            documentId: approval.id,
            action: decision,
            actor: 'Manager Equipment',
            note: note || (decision === 'Approved' ? 'Dokumen memenuhi pemeriksaan approver.' : 'Dikembalikan kepada pemohon.'),
            at: formatNow()
        });
        persistWorkflowState();
        renderAll();
        notify(
            decision === 'Approved' ? 'Dokumen disetujui' : 'Dokumen ditolak',
            `${approval.id} telah memperbarui status dokumen sumber.`,
            decision === 'Approved' ? 'success' : 'danger'
        );
    }

    window.decideIntegratedApproval = function (id, decision) {
        if (decision === 'Approved') {
            commitApprovalDecision(id, decision, '');
        }
    };

    window.openIntegratedReject = function (id) {
        let modal = document.getElementById('modalIntegratedReject');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'modalIntegratedReject';
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal-content" style="max-width:560px;">
                    <div class="modal-header">
                        <h2 style="font-size:1.15rem;">Tolak Dokumen</h2>
                        <button onclick="closeModal('modalIntegratedReject')" style="border:none;background:none;font-size:1.5rem;cursor:pointer;">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="integratedRejectId">
                        <div class="form-group">
                            <label for="integratedRejectReason">Alasan Penolakan</label>
                            <textarea id="integratedRejectReason" class="form-control" rows="5" placeholder="Jelaskan data atau tindakan yang perlu diperbaiki..."></textarea>
                        </div>
                        <div style="display:flex;justify-content:flex-end;gap:8px;">
                            <button class="btn btn-secondary" onclick="closeModal('modalIntegratedReject')">Batal</button>
                            <button class="btn btn-danger" onclick="submitIntegratedReject()">Tolak & Kembalikan</button>
                        </div>
                    </div>
                </div>`;
            document.body.appendChild(modal);
        }
        document.getElementById('integratedRejectId').value = id;
        document.getElementById('integratedRejectReason').value = '';
        window.openModal('modalIntegratedReject');
    };

    window.submitIntegratedReject = function () {
        const id = document.getElementById('integratedRejectId').value;
        const reason = document.getElementById('integratedRejectReason').value.trim();
        if (!reason) {
            notify('Alasan wajib diisi', 'Jelaskan koreksi yang harus dilakukan pemohon.', 'warning');
            return;
        }
        commitApprovalDecision(id, 'Rejected', reason);
        window.closeModal('modalIntegratedReject');
    };

    function renderPartWorkflow() {
        const container = document.getElementById('partWorkflowRegister');
        if (!container) return;
        const records = state.approvals.filter(item => /SPB|Sparepart/i.test(item.type));
        container.innerHTML = `
            <div class="panel">
                <div class="panel-header">
                    <span><i class="fa-solid fa-boxes-stacked"></i> Register SPB & Part Fulfillment</span>
                    <button class="btn btn-primary" style="padding:6px 10px;" onclick="showView('approval')">Buka Approval</button>
                </div>
                <div class="panel-body" style="padding:0;">
                    <div class="table-responsive">
                        <table style="margin:0;">
                            <thead>
                                <tr><th>SPB / WO</th><th>Unit / Site</th><th>Item</th><th>Approval</th><th>Fulfillment</th><th>Aksi</th></tr>
                            </thead>
                            <tbody>
                                ${records.length ? records.map(record => {
                                    const fulfillment = record.fulfillmentStatus ||
                                        (record.status === 'Approved' ? 'Ready to Issue' : record.status === 'Rejected' ? 'Returned' : 'Waiting Approval');
                                    const itemText = Array.isArray(record.items) && record.items.length
                                        ? record.items.map(item => `${item.partNo} (${item.qty} ${item.uom})`).join(', ')
                                        : record.detail;
                                    let action = '<span class="badge bg-draft">Menunggu</span>';
                                    if (record.status === 'Pending') {
                                        action = '<button class="btn btn-primary" style="padding:5px 8px;" onclick="showView(\'approval\')">Review</button>';
                                    } else if (record.status === 'Approved' && fulfillment !== 'Issued' && record.relatedWo) {
                                        action = `<button class="btn btn-success" style="padding:5px 8px;" onclick="issueIntegratedParts('${escapeHtml(record.id)}')">Issue Part</button>`;
                                    } else if (fulfillment === 'Issued') {
                                        action = '<span class="badge bg-operating">Selesai</span>';
                                    } else if (!record.relatedWo) {
                                        action = '<span style="color:var(--warning);font-size:.78rem;">WO belum tertaut</span>';
                                    } else {
                                        action = '<span class="badge bg-breakdown">Dikembalikan</span>';
                                    }
                                    return `
                                        <tr>
                                            <td><strong>${escapeHtml(record.id)}</strong><small style="display:block;color:var(--text-muted);">${escapeHtml(record.relatedWo || 'WO belum tertaut')}</small></td>
                                            <td>${escapeHtml(record.unitId || 'Unit belum tersedia')}<small style="display:block;color:var(--text-muted);">${escapeHtml(record.project || 'Site belum tersedia')}</small></td>
                                            <td>${escapeHtml(itemText)}</td>
                                            <td><span class="badge ${record.status === 'Approved' ? 'bg-operating' : record.status === 'Rejected' ? 'bg-breakdown' : 'bg-draft'}">${escapeHtml(record.status)}</span></td>
                                            <td>${escapeHtml(fulfillment)}${record.issuedAt ? `<small style="display:block;color:var(--text-muted);">${escapeHtml(record.issuedAt)}</small>` : ''}</td>
                                            <td>${action}</td>
                                        </tr>`;
                                }).join('') : '<tr><td colspan="6" style="padding:25px;text-align:center;color:var(--text-muted);">Belum ada SPB.</td></tr>'}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>`;
    }

    function createSpbApproval() {
        const rows = [...document.querySelectorAll('#spb-items tr')];
        const woSelect = document.getElementById('spb-wo-id');
        if (!rows.length) {
            notify('Item belum tersedia', 'Tambahkan minimal satu spare part.', 'warning');
            return;
        }
        const items = [];
        let invalid = false;
        rows.forEach(row => {
            const values = [...row.querySelectorAll('input')].map(input => input.value.trim());
            if (values.length < 4 || values.some(value => !value)) invalid = true;
            items.push({
                partNo: values[0],
                description: values[1],
                qty: values[2],
                uom: values[3]
            });
        });
        if (invalid) {
            notify('Data item belum lengkap', 'Part number, deskripsi, qty, dan satuan wajib diisi.', 'warning');
            return;
        }
        const wo = data()
            ? (data().work_orders || []).find(item => item.woId === woSelect.value)
            : null;
        const sequence = String(state.approvals.filter(item => /SPB|Sparepart/i.test(item.type)).length + 102).padStart(3, '0');
        const id = `SPB-2026-07-${sequence}`;
        state.approvals.unshift({
            id,
            type: 'SPB Sparepart',
            requester: 'Manager Equipment',
            detail: `${items.length} item / ${items.reduce((sum, item) => sum + Number(item.qty || 0), 0)} total qty`,
            project: wo ? wo.location : 'Site belum tersedia',
            unitId: wo ? wo.assetId : '',
            relatedWo: woSelect.value,
            sla: '8 jam tersisa',
            risk: wo && wo.priority === 'High' ? 'Kritis' : 'Normal',
            status: 'Pending',
            items
        });
        state.audit.unshift({
            documentId: id,
            action: 'Submitted',
            actor: 'Manager Equipment',
            note: `Dibuat dari ${woSelect.value}${wo ? ` untuk ${wo.assetId}` : ''}.`,
            at: formatNow()
        });
        persistWorkflowState();
        document.getElementById('spb-items').innerHTML = '';
        renderAll();
        notify('SPB masuk Approval Inbox', `${id} terhubung ke ${woSelect.value}.`, 'success');
        window.showView('approval');
    }

    window.submitSpb = createSpbApproval;

    window.issueIntegratedParts = function (id) {
        const record = state.approvals.find(item => item.id === id);
        if (!record || record.status !== 'Approved' || record.fulfillmentStatus === 'Issued') return;
        record.fulfillmentStatus = 'Issued';
        record.issuedAt = formatNow();
        const wo = data() && record.relatedWo
            ? (data().work_orders || []).find(item => item.woId === record.relatedWo)
            : null;
        if (wo) {
            wo.partStatus = 'Issued';
            wo.partReference = record.id;
            wo.lastUpdate = new Date().toISOString();
        }
        state.audit.unshift({
            documentId: record.id,
            action: 'Part Issued',
            actor: 'Manager Equipment',
            note: `${record.relatedWo} · ${record.unitId || 'Unit belum tersedia'}.`,
            at: record.issuedAt
        });
        persistWorkflowState();
        renderAll();
        notify('Part berhasil di-issue', `${record.id} memperbarui ${record.relatedWo}.`, 'success');
    };

    if (typeof legacySubmitWoStatusChange === 'function') {
        window.submitWoStatusChange = function () {
            const targetStatus = document.getElementById('woConfirmTargetStatus');
            const woId = document.getElementById('woConfirmId');
            if (targetStatus && targetStatus.value === 'Closed' && woId) {
                const incomplete = state.approvals.filter(item =>
                    item.relatedWo === woId.value &&
                    /SPB|Sparepart/i.test(item.type) &&
                    item.fulfillmentStatus !== 'Issued' &&
                    item.status !== 'Rejected'
                );
                if (incomplete.length) {
                    notify('WO belum dapat ditutup', `${incomplete[0].id} belum menyelesaikan part issue.`, 'danger');
                    window.closeModal('modalWoStatusConfirm');
                    window.showView('logistics');
                    renderPartWorkflow();
                    return;
                }
            }
            const result = legacySubmitWoStatusChange.apply(this, arguments);
            window.setTimeout(renderAll, 0);
            return result;
        };
    }

    function renderUnitIntegrationSnapshot(assetId) {
        const current = data();
        if (!current) return;
        const asset = (current.assets || []).find(item => item.id === assetId);
        if (!asset) return;
        const wo = activeWorkOrder(asset.id);
        const source = effectiveSource(asset, wo);
        let panel = document.getElementById('unitIntegrationSnapshot');
        if (!panel) {
            panel = document.createElement('div');
            panel.id = 'unitIntegrationSnapshot';
            const ringkasan = document.getElementById('assetTabSecRingkasan');
            const grid = ringkasan && ringkasan.querySelector('.asset-360-grid');
            if (grid) grid.insertAdjacentElement('afterend', panel);
        }
        if (!panel) return;
        const operational = asset.status === 'READY' ? 'Available' : asset.status === 'STANDBY' ? 'Standby' : 'Unavailable';
        const maintenance = wo ? `${wo.status} · ${wo.woId}` : asset.status === 'INSPEKSI' ? 'Inspection Hold' : 'No Active Work';
        const safety = asset.status === 'ACCIDENT_HOLD' ? 'Safety Hold' : 'Clear';
        const relatedSpb = state.approvals.filter(item => item.unitId === asset.id && /SPB|Sparepart/i.test(item.type));
        panel.innerHTML = `
            <div style="margin-top:16px;background:#f8f9fa;border:1px solid var(--border);border-radius:8px;padding:15px;">
                <h3 style="margin:0 0 10px;font-size:1rem;"><i class="fa-solid fa-link"></i> Snapshot Integrasi Unit</h3>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;">
                    <div><small style="color:var(--text-muted);">Operational Status</small><strong style="display:block;">${escapeHtml(operational)}</strong></div>
                    <div><small style="color:var(--text-muted);">Maintenance Status</small><strong style="display:block;">${escapeHtml(maintenance)}</strong></div>
                    <div><small style="color:var(--text-muted);">Safety Status</small><strong style="display:block;">${escapeHtml(safety)}</strong></div>
                    <div><small style="color:var(--text-muted);">Status Source</small><strong style="display:block;">${escapeHtml(source.source)} · ${escapeHtml(source.reference)}</strong></div>
                    <div><small style="color:var(--text-muted);">SPB Terkait</small><strong style="display:block;">${relatedSpb.length}</strong></div>
                </div>
            </div>`;
    }

    if (typeof legacyOpenAssetModal === 'function') {
        window.openAssetModal = function (id) {
            const result = legacyOpenAssetModal.apply(this, arguments);
            window.setTimeout(() => renderUnitIntegrationSnapshot(id), 0);
            return result;
        };
    }

    function setupSiteFilter() {
        const select = document.getElementById('globalProjectFilter');
        if (!select || !data()) return;
        const locations = [...new Set((data().assets || []).map(asset => asset.location).filter(Boolean))]
            .sort((left, right) => left.localeCompare(right, 'id'));
        select.innerHTML = '<option value="ALL">Project: Semua Project</option>' +
            locations.map(location => `<option value="${escapeHtml(location)}">Project/Site: ${escapeHtml(location)}</option>`).join('');
        select.addEventListener('change', () => {
            state.site = select.value || 'ALL';
            renderAll();
            notify('Scope data diperbarui', select.options[select.selectedIndex].textContent, 'info');
        });
    }

    function setupGlobalSearch() {
        const input = document.getElementById('globalFleetSearch');
        if (!input || !data()) return;
        input.title = 'Ketik Asset ID atau WO ID lalu tekan Enter';
        input.addEventListener('keydown', event => {
            if (event.key !== 'Enter') return;
            const query = input.value.trim().toLowerCase();
            if (!query) return;
            const asset = (data().assets || []).find(item =>
                [item.id, item.category, item.location].some(value => String(value || '').toLowerCase().includes(query))
            );
            if (asset) {
                window.openAssetModal(asset.id, asset.status, asset.category, asset.location);
                return;
            }
            const wo = (data().work_orders || []).find(item =>
                [item.woId, item.assetId, item.issue].some(value => String(value || '').toLowerCase().includes(query))
            );
            if (wo) {
                window.openWoDetailView(wo.woId, wo.assetId, wo.issue || '');
                return;
            }
            notify('Data tidak ditemukan', `Tidak ada unit atau WO yang cocok dengan "${input.value}".`, 'warning');
        });
    }

    function renderAll() {
        if (!data()) return;
        renderDashboardMetrics();
        renderDecisionSupport();
        window.initMonitoringView(scopedAssets());
        renderApprovalInbox();
        renderPartWorkflow();
    }

    if (typeof legacySyncFleetState === 'function') {
        window.syncFleetState = function () {
            const result = legacySyncFleetState.apply(this, arguments);
            window.setTimeout(renderAll, 0);
            return result;
        };
    }

    function waitForData(attempt) {
        if (data()) {
            setupSiteFilter();
            setupGlobalSearch();
            renderAll();
            return;
        }
        if (attempt < 100) {
            window.setTimeout(() => waitForData(attempt + 1), 100);
        } else {
            const container = document.getElementById('fleetDecisionSupport');
            if (container) {
                container.innerHTML = '<div class="panel"><div class="panel-body" style="color:var(--danger);">Data tidak dapat dimuat. Jalankan halaman melalui local server agar data.json dapat diakses.</div></div>';
            }
        }
    }

    loadWorkflowState();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => waitForData(0), { once: true });
    } else {
        waitForData(0);
    }
})();
