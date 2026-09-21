<!-- ====================================================================================
     🏥 eDebtor Hospital System (eDHS) - Reconciliation Audit Center Modal
     ====================================================================================
     ศูนย์ตรวจสอบและยันยอดบัญชี (Reconciliation Audit Center)
     ตรวจสอบความถูกต้องระหว่างทะเบียนคุมลูกหนี้ (Col 12) กับยอดลูกหนี้รายตัว (debtor_excel.php)
     ==================================================================================== -->

<style>
/* Fullscreen modal styling for Reconciliation Audit */
#modalReconAudit .modal-dialog {
  width: 100vw !important;
  max-width: 100vw !important;
  height: 100vh !important;
  max-height: 100vh !important;
  margin: 0 !important;
  padding: 0 !important;
}

#modalReconAudit .modal-content {
  height: 100vh !important;
  min-height: 100vh !important;
  border-radius: 0 !important;
  border: none !important;
  display: flex !important;
  flex-direction: column !important;
}

#modalReconAudit .modal-header {
  flex-shrink: 0 !important;
  border-radius: 0 !important;
  padding: 1.1rem 2rem !important;
  background: #ffffff !important;
  border-bottom: 1px solid #e2e8f0 !important;
  box-shadow: 0 2px 6px -1px rgba(0, 0, 0, 0.04) !important;
}

#modalReconAudit .recon-header-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
  color: #15803d;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 26px;
  box-shadow: 0 2px 8px rgba(22, 163, 74, 0.15);
  flex-shrink: 0;
}

#modalReconAudit .recon-header-title {
  color: #0f172a !important;
  font-size: 1.25rem !important;
  font-weight: 700 !important;
  letter-spacing: -0.01em;
}

#modalReconAudit .recon-header-subtitle {
  color: #64748b !important;
  font-size: 13px !important;
  font-weight: 400 !important;
  margin-top: 2px;
}

#modalReconAudit .modal-body {
  flex: 1 1 auto !important;
  overflow-y: auto !important;
  padding: 1.25rem 1.75rem !important;
  background-color: #f8fafc !important;
}

#modalReconAudit .modal-footer {
  flex-shrink: 0 !important;
  border-radius: 0 !important;
  padding: 0.75rem 1.75rem !important;
}

#modalReconAudit .recon-table-wrapper {
  max-height: calc(100vh - 350px) !important;
  min-height: 460px !important;
}

/* Layering & Stacking for Nested Modals (ป้องกัน Modal ซ้อนทับและ Backdrop บังหน้า Modal) */
#modalReconAudit {
  z-index: 1090 !important;
}

#modalReconAuditDetail {
  z-index: 1105 !important;
  background: rgba(15, 23, 42, 0.65) !important;
  backdrop-filter: blur(3px) !important;
}

#modalReconAuditDetail .modal-dialog {
  max-width: 860px !important;
}

/* Header */
#modalReconAuditDetail .modal-header {
  background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important;
  border-bottom: 2px solid #0284c7 !important;
}

#modalReconAuditDetail #detailModalTitle {
  font-size: 18px !important;
  font-weight: 700 !important;
  color: #ffffff !important;
  letter-spacing: 0.2px !important;
}

#modalReconAuditDetail #detailModalSubtitle {
  font-size: 13.5px !important;
  color: #bae6fd !important;
  font-weight: 600 !important;
}

/* Quick Stats Banner */
#modalReconAuditDetail .stat-banner-card {
  border-radius: 14px !important;
  background: #ffffff !important;
  border: 1.5px solid #cbd5e1 !important;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
}

#modalReconAuditDetail .stat-label {
  font-size: 13px !important;
  font-weight: 700 !important;
  color: #475569 !important;
  margin-bottom: 4px !important;
}

#modalReconAuditDetail #statDetailCol12 {
  font-size: 22px !important;
  font-weight: 800 !important;
  color: #0f172a !important;
  font-variant-numeric: tabular-nums !important;
}

#modalReconAuditDetail #statDetailExcel {
  font-size: 22px !important;
  font-weight: 800 !important;
  color: #0284c7 !important;
  font-variant-numeric: tabular-nums !important;
}

#modalReconAuditDetail #statDetailCases {
  font-size: 13px !important;
  font-weight: 700 !important;
  color: #64748b !important;
  margin-top: 2px !important;
}

#modalReconAuditDetail #statDetailDiff {
  font-size: 22px !important;
  font-weight: 800 !important;
  font-variant-numeric: tabular-nums !important;
}

#modalReconAuditDetail #statDetailDiffComp .badge {
  font-size: 12px !important;
  font-weight: 700 !important;
  padding: 3px 8px !important;
}

/* Section Title */
#modalReconAuditDetail .section-title-3d {
  font-size: 15.5px !important;
  font-weight: 800 !important;
  color: #0f172a !important;
  margin-bottom: 14px !important;
}

/* Dimension Cards */
#modalReconAuditDetail .dim-card {
  border-radius: 12px !important;
  border: 1px solid #e2e8f0 !important;
  background: #ffffff !important;
  box-shadow: 0 2px 4px rgba(15, 23, 42, 0.04) !important;
  margin-bottom: 14px !important;
}

#modalReconAuditDetail .dim-card-title {
  font-size: 14.5px !important;
  font-weight: 800 !important;
  color: #0f172a !important;
}

#modalReconAuditDetail .dim-badge {
  font-size: 12.5px !important;
  font-weight: 700 !important;
  padding: 4px 12px !important;
  border-radius: 20px !important;
}

#modalReconAuditDetail .dim-metric-grid {
  background: #f8fafc !important;
  border: 1px solid #e2e8f0 !important;
  border-radius: 8px !important;
  padding: 10px 14px !important;
  margin-bottom: 8px !important;
}

#modalReconAuditDetail .dim-metric-label {
  font-size: 12.5px !important;
  font-weight: 700 !important;
  color: #475569 !important;
  margin-right: 4px !important;
}

#modalReconAuditDetail .dim-metric-val {
  font-size: 15px !important;
  font-weight: 800 !important;
  color: #0f172a !important;
  font-variant-numeric: tabular-nums !important;
}

#modalReconAuditDetail .dim-metric-val-diff {
  font-size: 15px !important;
  font-weight: 800 !important;
  font-variant-numeric: tabular-nums !important;
}

#modalReconAuditDetail .dim-desc-box {
  background: #f1f5f9 !important;
  border: 1px solid #cbd5e1 !important;
  border-left: 4px solid #3b82f6 !important;
  border-radius: 6px !important;
  padding: 9px 13px !important;
  font-size: 13.5px !important;
  font-weight: 600 !important;
  color: #1e293b !important;
  line-height: 1.55 !important;
}

#modalReconAuditDetail .dim-card-dim1 .dim-desc-box {
  border-left-color: #6366f1 !important;
}

#modalReconAuditDetail .dim-card-dim2 .dim-desc-box {
  border-left-color: #06b6d4 !important;
}

#modalReconAuditDetail .dim-card-dim3 .dim-desc-box {
  border-left-color: #f59e0b !important;
}

/* Action Guide Box */
#modalReconAuditDetail .guide-box {
  border-radius: 12px !important;
  background: #f0fdf4 !important;
  border: 1.5px solid #86efac !important;
  box-shadow: 0 2px 5px rgba(22, 101, 52, 0.06) !important;
}

#modalReconAuditDetail .guide-header {
  font-size: 15.5px !important;
  font-weight: 800 !important;
  color: #15803d !important;
  margin-bottom: 12px !important;
}

#modalReconAuditDetail .guide-section-title {
  font-size: 14px !important;
  font-weight: 800 !important;
  color: #065f46 !important;
  margin-bottom: 6px !important;
}

#modalReconAuditDetail .guide-list {
  padding-left: 22px !important;
}

#modalReconAuditDetail .guide-list li {
  font-size: 13.5px !important;
  font-weight: 600 !important;
  color: #0f172a !important;
  line-height: 1.65 !important;
  margin-bottom: 6px !important;
}

/* ==========================================================================
   หัวตารางสีเทาเข้ม (Dark Slate Gray) และตัวหนังสือคมชัด
   ========================================================================== */
#tableReconAudit thead,
#tableReconAudit thead.recon-table-header,
#tableReconAudit thead tr,
#tableReconAudit thead th {
  background-color: #334155 !important; /* เทาเข้ม Slate 700 */
  color: #ffffff !important;
  font-weight: 700 !important;
  font-size: 12.5px !important;
  letter-spacing: 0.2px !important;
  padding-top: 10px !important;
  padding-bottom: 10px !important;
  border-bottom: 2px solid #1e293b !important;
  border-top: none !important;
  vertical-align: middle !important;
  box-shadow: 0 2px 4px rgba(15, 23, 42, 0.12) !important;
}

#tableReconAudit thead th small {
  color: #cbd5e1 !important; /* เทาอ่อนสว่าง คมชัดบนพื้นเทาเข้ม */
  font-weight: 500 !important;
  font-size: 11px !important;
  display: block;
  margin-top: 1px;
}

/* ตัวหนังสือในตาราง คมชัด อ่านง่าย ขนาด 12.5px !important */
#tableReconAudit tbody tr td {
  color: #0f172a !important; /* สีเข้มชัดเจน */
  font-size: 12.5px !important;
  padding: 8px 10px !important;
  border-bottom: 1px solid #e2e8f0 !important;
  vertical-align: middle !important;
}

#tableReconAudit tbody tr td * {
  font-size: 12.5px !important;
}

#tableReconAudit tbody tr:hover td {
  background-color: #f1f5f9 !important;
}

.audit-subtext,
#tableReconAudit tbody tr td small,
#tableReconAudit tbody tr td .small {
  color: #475569 !important; /* ตัวหนังสือคำอธิบายเสริม สีเทาเข้มชัดเจน */
  font-weight: 500 !important;
  font-size: 11px !important;
}

/* ป้ายสถานะยันยอด คมชัด สีไม่กลืน ไม่จาง */
.audit-status-badge {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  padding: 6px 14px !important;
  font-size: 12.5px !important;
  font-weight: 700 !important;
  border-radius: 20px !important;
  line-height: 1.25 !important;
  letter-spacing: 0.1px !important;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06) !important;
  white-space: nowrap !important;
}

/* 1. รอดำเนินการ / รอตรวจสอบ: สีส้มอำพันเข้ม ตัวหนังสือเข้ม คอนทราสต์สูง อ่านง่ายชัดเจน */
.audit-badge-pending {
  background-color: #fef3c7 !important;
  color: #92400e !important; /* น้ำตาลส้มเข้ม คมชัด 100% */
  border: 1.5px solid #f59e0b !important;
}

/* 2. ตรงกัน 100%: สีเขียวสดใส ตัวหนังสือเขียวเข้ม */
.audit-badge-match {
  background-color: #dcfce7 !important;
  color: #14532d !important;
  border: 1.5px solid #22c55e !important;
}

/* 3. มียอดต่าง: สีแดงชัดเจน */
.audit-badge-mismatch {
  background-color: #fee2e2 !important;
  color: #991b1b !important;
  border: 1.5px solid #ef4444 !important;
}

/* 4. ไม่มีความเคลื่อนไหว */
.audit-badge-inactive {
  background-color: #f1f5f9 !important;
  color: #334155 !important;
  border: 1.5px solid #94a3b8 !important;
}
</style>

<div class="modal fade" id="modalReconAudit" tabindex="-1" aria-hidden="true" style="display: none;">
  <div class="modal-dialog modal-fullscreen modal-dialog-scrollable" role="document">
    <div class="modal-content border-0 shadow-none">
      
      <!-- Modal Header (Clean, Readable & Eye-Friendly) -->
      <div class="modal-header">
        <div class="d-flex align-items-center gap-3">
          <div class="recon-header-icon">
            <i class='bx bx-check-shield'></i>
          </div>
          <div>
            <div class="d-flex align-items-center flex-wrap gap-2">
              <h5 class="recon-header-title mb-0">ตรวจสอบและยันยอดบัญชี (Audit Center)</h5>
              <span class="badge bg-label-primary rounded-pill px-3 py-1" id="reconAuditMonthBadge" style="font-size: 13px; font-weight: 600;"><i class='bx bx-calendar me-1'></i>-</span>
              <span class="badge rounded-pill px-3 py-1" id="reconAuditClosingBadge" style="font-size: 12px; font-weight: 500; display: none;">-</span>
            </div>
            <div class="recon-header-subtitle">
              เปรียบเทียบยันยอด ทะเบียนคุมลูกหนี้ (งบทดลองบัญชี) กับยอดลูกหนี้รายตัวจริงในระบบ (กลุ่มงานประกันสุขภาพ)
            </div>
          </div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-none" onclick="refreshReconAuditData()" title="รีเฟรชข้อมูลยันยอด">
            <i class='bx bx-refresh me-1'></i>รีเฟรช
          </button>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="padding: 0.5rem;"></button>
        </div>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-4" style="background-color: #f8fafc;">
        
        <!-- Top KPI Dashboard Cards -->
        <div class="row g-3 mb-4">
          <div class="col-md-3 col-6">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; background: #ffffff; border-left: 4px solid #6366f1 !important;">
              <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <span class="text-muted small fw-semibold">ผังบัญชีทั้งหมด</span>
                    <h3 class="fw-bold mb-0 text-dark mt-1" id="kpiAuditTotal">0</h3>
                  </div>
                  <div class="badge bg-label-primary p-2 rounded-3">
                    <i class='bx bx-folder fs-4'></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-3 col-6">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; background: #ffffff; border-left: 4px solid #10b981 !important;">
              <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <span class="text-success small fw-semibold">ตรงกัน 100% (Match)</span>
                    <h3 class="fw-bold mb-0 text-success mt-1" id="kpiAuditMatched">0</h3>
                  </div>
                  <div class="badge bg-label-success p-2 rounded-3">
                    <i class='bx bx-check-double fs-4'></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-3 col-6">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; background: #ffffff; border-left: 4px solid #ef4444 !important;">
              <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <span class="text-danger small fw-semibold">มียอดต่าง (Mismatch)</span>
                    <h3 class="fw-bold mb-0 text-danger mt-1" id="kpiAuditMismatched">0</h3>
                  </div>
                  <div class="badge bg-label-danger p-2 rounded-3">
                    <i class='bx bx-error fs-4'></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-md-3 col-6">
            <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px; background: #ffffff; border-left: 4px solid #f59e0b !important;">
              <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <span class="text-warning small fw-semibold">รอดำเนินการ / รอยืนยัน</span>
                    <h3 class="fw-bold mb-0 text-warning mt-1" id="kpiAuditPending">0</h3>
                  </div>
                  <div class="badge bg-label-warning p-2 rounded-3">
                    <i class='bx bx-time fs-4'></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Alert Bar when there are Mismatches -->
        <div id="reconAuditAlertBox" class="alert alert-danger align-items-center gap-2 mb-3 shadow-sm d-none" style="border-radius: 10px;">
          <i class='bx bx-error-circle fs-4 flex-shrink-0'></i>
          <div class="d-flex justify-content-between align-items-center w-100">
            <div>
              <strong>ตรวจพบผลต่างการยันยอด:</strong> มี <span id="alertMismatchCount" class="fw-bold">0</span> ผังบัญชีที่ยอดไม่ตรงกัน รวมผลต่างสุทธิ <span id="alertTotalVariance" class="fw-bold text-decoration-underline">฿0.00</span>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="filterAuditTable('MISMATCH')">
              <i class='bx bx-filter'></i> กรองเฉพาะผังที่มีปัญหา
            </button>
          </div>
        </div>

        <!-- Filter Tabs -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
          <ul class="nav nav-pills gap-1" id="reconAuditTabs">
            <li class="nav-item">
              <button class="nav-link active rounded-pill px-3 py-1 fw-semibold text-sm" onclick="filterAuditTable('ALL', this)">
                ทั้งหมด (<span id="tabCntAll">0</span>)
              </button>
            </li>
            <li class="nav-item">
              <button class="nav-link rounded-pill px-3 py-1 fw-semibold text-sm text-danger" onclick="filterAuditTable('MISMATCH', this)">
                <i class='bx bx-error-circle me-1'></i>มียอดต่าง (<span id="tabCntMismatch">0</span>)
              </button>
            </li>
            <li class="nav-item">
              <button class="nav-link rounded-pill px-3 py-1 fw-semibold text-sm text-success" onclick="filterAuditTable('MATCH', this)">
                <i class='bx bx-check-circle me-1'></i>ตรงกัน 100% (<span id="tabCntMatch">0</span>)
              </button>
            </li>
            <li class="nav-item">
              <button class="nav-link rounded-pill px-3 py-1 fw-semibold text-sm text-secondary" onclick="filterAuditTable('PENDING', this)">
                <i class='bx bx-time me-1'></i>รอดำเนินการ (<span id="tabCntPending">0</span>)
              </button>
            </li>
          </ul>

          <div class="d-flex align-items-center gap-2">
            <input type="text" id="reconAuditSearchInput" class="form-control form-control-sm" placeholder="🔍 ค้นหารหัส หรือ ชื่อผัง..." style="width: 230px; border-radius: 8px;" onkeyup="searchAuditTable()">
            <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="refreshReconAuditData()" title="รีเฟรชข้อมูล">
              <i class='bx bx-refresh'></i>
            </button>
          </div>
        </div>

        <!-- Audit Table -->
        <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
          <div class="table-responsive recon-table-wrapper">
            <table class="table table-hover align-middle mb-0" id="tableReconAudit">
              <thead class="sticky-top recon-table-header" style="z-index: 10;">
                <tr>
                  <th style="width: 14%; text-align: center;">รหัสผังบัญชี</th>
                  <th style="width: 29%;">ชื่อผังบัญชี</th>
                  <th style="width: 13%; text-align: right;">ทะเบียนคุม<br><small>(งบทดลองบัญชี)</small></th>
                  <th style="width: 13%; text-align: right;">ลูกหนี้รายตัว<br><small>(ประกันสุขภาพ)</small></th>
                  <th style="width: 12%; text-align: right;">ส่วนต่าง (บาท)</th>
                  <th style="width: 12%; text-align: center;">สถานะยันยอด</th>
                  <th style="width: 7%; text-align: center;">เครื่องมือ</th>
                </tr>
              </thead>
              <tbody id="tbodyReconAudit">
                <tr>
                  <td colspan="7" class="text-center py-4 text-muted">
                    <i class='bx bx-loader-alt bx-spin fs-2 text-primary mb-2'></i><br>
                    กำลังดึงข้อมูลและประมวลผลการยันยอด...
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="modal-footer py-2 px-4 bg-light d-flex justify-content-between align-items-center border-top">
        <div class="text-muted small">
          <i class='bx bx-info-circle text-primary me-1'></i> <strong>คำแนะนำ:</strong> คลิกที่ปุ่ม <span class="badge bg-label-danger py-1 px-2"><i class='bx bx-search'></i> ตรวจหาสาเหตุ</span> ในแต่ละผังเพื่อดูการวินิจฉัย 3 มิติ และแนวทางปฏิบัติของ 2 ฝ่าย
        </div>
        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
          ปิดหน้าต่าง
        </button>
      </div>

    </div>
  </div>
</div>

<!-- ====================================================================================
     Sub-Modal: 3-Dimension Variance Diagnostics Detail (วินิจฉัยรายผังบัญชี)
     ==================================================================================== -->
<div class="modal fade" id="modalReconAuditDetail" tabindex="-1" aria-hidden="true" data-bs-backdrop="false" style="display: none; z-index: 1105; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(3px);">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
      
      <!-- Sub-Modal Header -->
      <div class="modal-header py-3 px-4">
        <div class="d-flex align-items-center gap-3">
          <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(255, 255, 255, 0.2); display: flex; align-items: center; justify-content: center; font-size: 24px;">
            <i class='bx bx-analyse text-white'></i>
          </div>
          <div>
            <h5 class="modal-title text-white mb-0" id="detailModalTitle">วินิจฉัยหาสาเหตุส่วนต่าง</h5>
            <div id="detailModalSubtitle">-</div>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Sub-Modal Body -->
      <div class="modal-body p-4" style="background-color: #f8fafc;">
        
        <!-- Quick Stats Banner -->
        <div class="card border-0 shadow-sm mb-4 stat-banner-card">
          <div class="card-body p-3">
            <div class="row text-center g-2 align-items-center">
              <div class="col-4 border-end">
                <div class="stat-label">ยอดทะเบียนคุม (งบทดลอง)</div>
                <div id="statDetailCol12">฿0.00</div>
              </div>
              <div class="col-4 border-end">
                <div class="stat-label">ลูกหนี้รายตัวคงค้างจริง</div>
                <div id="statDetailExcel">฿0.00</div>
                <div id="statDetailCases">(0 ราย)</div>
              </div>
              <div class="col-4">
                <div class="stat-label">ผลต่างสุทธิ (Excel - บัญชี)</div>
                <div id="statDetailDiff">฿0.00</div>
                <div id="statDetailDiffComp"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="section-title-3d d-flex align-items-center gap-2">
          <i class='bx bx-git-repo-forked text-primary fs-5'></i>
          <span>ผลการวินิจฉัยส่วนต่าง 3 มิติ (3-Dimension Analysis)</span>
        </div>

        <!-- Dimension 1 -->
        <div class="card border-0 dim-card dim-card-dim1 mb-3" style="border-left: 5px solid #6366f1 !important;">
          <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="dim-card-title"><i class='bx bx-calendar-plus text-primary me-1'></i> มิติที่ 1: การตั้งลูกหนี้ต้นงวด (Setup Variance)</span>
              <span class="badge dim-badge" id="badgeDim1">ตรวจเช็ค</span>
            </div>
            <div class="row g-2 dim-metric-grid align-items-center">
              <div class="col-4">
                <span class="dim-metric-label">ประกันฯ นำเข้า:</span>
                <span class="dim-metric-val" id="dim1Col5">฿0.00</span>
              </div>
              <div class="col-4">
                <span class="dim-metric-label">งบทดลองบัญชี:</span>
                <span class="dim-metric-val" id="dim1Col6">฿0.00</span>
              </div>
              <div class="col-4">
                <span class="dim-metric-label">ผลต่าง:</span>
                <span class="dim-metric-val-diff" id="dim1Diff">฿0.00</span>
              </div>
            </div>
            <div class="dim-desc-box mt-2" id="descDim1">-</div>
          </div>
        </div>

        <!-- Dimension 2 -->
        <div class="card border-0 dim-card dim-card-dim2 mb-3" style="border-left: 5px solid #06b6d4 !important;">
          <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="dim-card-title"><i class='bx bx-money-withdraw text-info me-1'></i> มิติที่ 2: การตัดหนี้ / รับชำระระหว่างงวด (Settlement Variance)</span>
              <span class="badge dim-badge" id="badgeDim2">ตรวจเช็ค</span>
            </div>
            <div class="row g-2 dim-metric-grid align-items-center">
              <div class="col-4">
                <span class="dim-metric-label">บัญชีตัดหนี้:</span>
                <span class="dim-metric-val" id="dim2Col10">฿0.00</span>
              </div>
              <div class="col-4">
                <span class="dim-metric-label">ใบเสร็จในระบบผู้ป่วย:</span>
                <span class="dim-metric-val" id="dim2Receipts">฿0.00</span>
              </div>
              <div class="col-4">
                <span class="dim-metric-label">ผลต่างตัดหนี้:</span>
                <span class="dim-metric-val-diff" id="dim2Diff">฿0.00</span>
              </div>
            </div>
            <div class="dim-desc-box mt-2" id="descDim2">-</div>
          </div>
        </div>

        <!-- Dimension 3 -->
        <div class="card border-0 dim-card dim-card-dim3 mb-4" style="border-left: 5px solid #f59e0b !important;">
          <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="dim-card-title"><i class='bx bx-history text-warning me-1'></i> มิติที่ 3: ยอดต่างสะสมยกมาจากเดือนก่อนหน้า (Historical Variance)</span>
              <span class="badge dim-badge" id="badgeDim3">ตรวจเช็ค</span>
            </div>
            <div class="row g-2 dim-metric-grid align-items-center">
              <div class="col-4">
                <span class="dim-metric-label">ยอดยกมา:</span>
                <span class="dim-metric-val" id="dim3Col3">฿0.00</span>
              </div>
              <div class="col-4">
                <span class="dim-metric-label">ลูกหนี้สิ้นเดือนก่อน:</span>
                <span class="dim-metric-val" id="dim3LastExcel">฿0.00</span>
              </div>
              <div class="col-4">
                <span class="dim-metric-label">ผลต่างสะสมอดีต:</span>
                <span class="dim-metric-val-diff" id="dim3Diff">฿0.00</span>
              </div>
            </div>
            <div class="dim-desc-box mt-2" id="descDim3">-</div>
          </div>
        </div>

        <!-- Action Guide Box -->
        <div class="card border-0 shadow-sm guide-box">
          <div class="card-body p-3">
            <div class="guide-header d-flex align-items-center gap-2">
              <i class='bx bxs-check-shield fs-5 text-success'></i>
              <span>แนวทางปฏิบัติสำหรับ 2 กลุ่มงาน (Action Guide)</span>
            </div>
            
            <div class="mb-3">
              <div class="guide-section-title d-flex align-items-center gap-1">
                <span>📌</span> <strong>กลุ่มงานประกันสุขภาพ (Insurance):</strong>
              </div>
              <ul class="guide-list mb-0" id="guideInsuranceList">
                <li>กำลังโหลด...</li>
              </ul>
            </div>

            <div>
              <div class="guide-section-title d-flex align-items-center gap-1">
                <span>📌</span> <strong>กลุ่มงานบัญชี / การเงิน (Accounting):</strong>
              </div>
              <ul class="guide-list mb-0" id="guideAccountingList">
                <li>กำลังโหลด...</li>
              </ul>
            </div>
          </div>
        </div>

      </div>

      <!-- Sub-Modal Footer -->
      <div class="modal-footer py-2 px-4 bg-light d-flex justify-content-between align-items-center border-top">
        <a id="btnExportDetailExcel" href="#" target="_blank" class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm" style="font-size: 13px !important;">
          <i class='bx bx-download me-1'></i> ส่งออกลูกหนี้รายตัว (Excel)
        </a>
        <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4 fw-bold" style="font-size: 13px !important;" data-bs-dismiss="modal">
          ปิดหน้าต่าง
        </button>
      </div>

    </div>
  </div>
</div>
