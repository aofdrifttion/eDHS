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
  background: rgba(15, 23, 42, 0.6) !important;
  backdrop-filter: blur(2px) !important;
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
              <thead class="table-light sticky-top" style="z-index: 10;">
                <tr>
                  <th style="width: 14%; text-align: center;">รหัสผังบัญชี</th>
                  <th style="width: 30%;">ชื่อผังบัญชี</th>
                  <th style="width: 13%; text-align: right;">ทะเบียนคุม<br><small class="text-muted">(งบทดลองบัญชี)</small></th>
                  <th style="width: 13%; text-align: right;">ลูกหนี้รายตัว<br><small class="text-muted">(ประกันสุขภาพ)</small></th>
                  <th style="width: 12%; text-align: right;">ส่วนต่าง (บาท)</th>
                  <th style="width: 10%; text-align: center;">สถานะยันยอด</th>
                  <th style="width: 8%; text-align: center;">เครื่องมือ</th>
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
<div class="modal fade" id="modalReconAuditDetail" tabindex="-1" aria-hidden="true" data-bs-backdrop="false" style="display: none; z-index: 1105; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(2px);">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
      
      <!-- Sub-Modal Header -->
      <div class="modal-header py-3 px-4 bg-primary text-white">
        <div class="d-flex align-items-center gap-3">
          <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(255, 255, 255, 0.2); display: flex; align-items: center; justify-content: center; font-size: 22px;">
            <i class='bx bx-analyse'></i>
          </div>
          <div>
            <h5 class="modal-title text-white fw-bold mb-0" id="detailModalTitle">วินิจฉัยหาสาเหตุส่วนต่าง</h5>
            <small class="text-white-50" id="detailModalSubtitle">-</small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Sub-Modal Body -->
      <div class="modal-body p-4" style="background-color: #f8fafc;">
        
        <!-- Quick Stats Banner -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; background: #ffffff;">
          <div class="card-body p-3">
            <div class="row text-center g-2 align-items-center">
              <div class="col-4 border-end">
                <small class="text-muted d-block">ทะเบียนคุม</small>
                <strong class="fs-6 text-dark" id="statDetailCol12">฿0.00</strong>
              </div>
              <div class="col-4 border-end">
                <small class="text-muted d-block">ลูกหนี้รายตัว</small>
                <strong class="fs-6 text-primary" id="statDetailExcel">฿0.00</strong>
                <small class="text-muted d-block" id="statDetailCases">(0 ราย)</small>
              </div>
              <div class="col-4">
                <small class="text-muted d-block">ผลต่างสุทธิ</small>
                <strong class="fs-6" id="statDetailDiff">฿0.00</strong>
                <div id="statDetailDiffComp" style="font-size: 11px; margin-top: 2px;"></div>
              </div>
            </div>
          </div>
        </div>

        <h6 class="fw-bold text-dark mb-3">
          <i class='bx bx-git-repo-forked text-primary me-1'></i> ผลการวินิจฉัยส่วนต่าง 3 มิติ (3-Dimension Analysis)
        </h6>

        <!-- Dimension 1 -->
        <div class="card border-0 shadow-sm mb-3" style="border-radius: 10px; border-left: 4px solid #6366f1 !important;">
          <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-bold text-dark"><i class='bx bx-calendar-plus text-primary me-1'></i> มิติที่ 1: การตั้งลูกหนี้ต้นงวด (Setup Variance)</span>
              <span class="badge" id="badgeDim1">ตรวจเช็ค</span>
            </div>
            <div class="row g-2 mb-2 small bg-light p-2 rounded">
              <div class="col-4"><strong>ประกันฯ นำเข้า:</strong> <span id="dim1Col5">฿0.00</span></div>
              <div class="col-4"><strong>งบทดลองบัญชี:</strong> <span id="dim1Col6">฿0.00</span></div>
              <div class="col-4"><strong>ผลต่าง:</strong> <span id="dim1Diff" class="fw-bold">฿0.00</span></div>
            </div>
            <p class="mb-0 text-muted small" id="descDim1">-</p>
          </div>
        </div>

        <!-- Dimension 2 -->
        <div class="card border-0 shadow-sm mb-3" style="border-radius: 10px; border-left: 4px solid #06b6d4 !important;">
          <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-bold text-dark"><i class='bx bx-money-withdraw text-info me-1'></i> มิติที่ 2: การตัดหนี้ / รับชำระระหว่างงวด (Settlement Variance)</span>
              <span class="badge" id="badgeDim2">ตรวจเช็ค</span>
            </div>
            <div class="row g-2 mb-2 small bg-light p-2 rounded">
              <div class="col-4"><strong>บัญชีตัดหนี้:</strong> <span id="dim2Col10">฿0.00</span></div>
              <div class="col-4"><strong>ใบเสร็จในระบบผู้ป่วย:</strong> <span id="dim2Receipts">฿0.00</span></div>
              <div class="col-4"><strong>ผลต่างตัดหนี้:</strong> <span id="dim2Diff" class="fw-bold">฿0.00</span></div>
            </div>
            <p class="mb-0 text-muted small" id="descDim2">-</p>
          </div>
        </div>

        <!-- Dimension 3 -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius: 10px; border-left: 4px solid #f59e0b !important;">
          <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fw-bold text-dark"><i class='bx bx-history text-warning me-1'></i> มิติที่ 3: ยอดต่างสะสมยกมาจากเดือนก่อนหน้า (Historical Variance)</span>
              <span class="badge" id="badgeDim3">ตรวจเช็ค</span>
            </div>
            <div class="row g-2 mb-2 small bg-light p-2 rounded">
              <div class="col-4"><strong>ยอดยกมา:</strong> <span id="dim3Col3">฿0.00</span></div>
              <div class="col-4"><strong>ลูกหนี้สิ้นเดือนก่อน:</strong> <span id="dim3LastExcel">฿0.00</span></div>
              <div class="col-4"><strong>ผลต่างสะสมอดีต:</strong> <span id="dim3Diff" class="fw-bold">฿0.00</span></div>
            </div>
            <p class="mb-0 text-muted small" id="descDim3">-</p>
          </div>
        </div>

        <!-- Action Guide Box -->
        <div class="card border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0;">
          <div class="card-body p-3">
            <h6 class="fw-bold text-success mb-2">
              <i class='bx bx-check-circle me-1'></i> แนวทางปฏิบัติสำหรับ 2 กลุ่มงาน (Action Guide)
            </h6>
            
            <div class="mb-2">
              <strong class="text-dark small d-block mb-1">📌 กลุ่มงานประกันสุขภาพ (Insurance):</strong>
              <ul class="mb-2 ps-3 small text-muted" id="guideInsuranceList">
                <li>กำลังโหลด...</li>
              </ul>
            </div>

            <div>
              <strong class="text-dark small d-block mb-1">📌 กลุ่มงานบัญชี / การเงิน (Accounting):</strong>
              <ul class="mb-0 ps-3 small text-muted" id="guideAccountingList">
                <li>กำลังโหลด...</li>
              </ul>
            </div>
          </div>
        </div>

      </div>

      <!-- Sub-Modal Footer -->
      <div class="modal-footer py-2 px-4 bg-light d-flex justify-content-between align-items-center border-top">
        <a id="btnExportDetailExcel" href="#" target="_blank" class="btn btn-sm btn-success rounded-pill px-3">
          <i class='bx bx-download me-1'></i> ส่งออกลูกหนี้รายตัว (Excel)
        </a>
        <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" data-bs-dismiss="modal">
          ปิดหน้าต่าง
        </button>
      </div>

    </div>
  </div>
</div>
