<!-- ============================================================== -->
<!-- MODAL: DATA AUDIT VERIFICATION & QUALITY CHECKLIST             -->
<!-- ============================================================== -->
<div class="modal fade" id="modalDataAudit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg rounded-4">
      
      <!-- Modal Header -->
      <div class="modal-header bg-primary text-white py-3 px-4 rounded-top-4">
        <div class="d-flex align-items-center gap-2">
          <div class="avatar avatar-sm bg-white rounded-circle d-flex align-items-center justify-content-center shadow-sm">
            <i class="bx bx-check-shield fs-4 text-primary"></i>
          </div>
          <div>
            <h5 class="modal-title text-white fw-bold mb-0">ผลการ Data Audit ตรวจสอบความถูกต้องของข้อมูล</h5>
            <small class="text-white-50">ระบบตรวจสอบความสมบูรณ์ HOSxP, การชนยอด STM, คุณภาพข้อมูล และเคสตกหล่น</small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-4 bg-light">
        
        <!-- Top Audit Score & Summary Card -->
        <div class="card border-0 shadow-sm rounded-3 p-3 mb-4 bg-white">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-circle p-3 d-flex align-items-center justify-content-center shadow-sm" id="auditScoreCircle" style="width: 70px; height: 70px; background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                <span class="text-white fw-bolder fs-4" id="auditHealthScoreText">100%</span>
              </div>
              <div>
                <h6 class="fw-bold text-dark mb-1">คะแนนความสมบูรณ์ของข้อมูล (Data Integrity Score)</h6>
                <p class="text-muted small mb-0" id="auditScoreDesc">ข้อมูลผ่านการตรวจสอบเทียบกับ HOSxP และ Statement ครบถ้วน</p>
                <small class="badge bg-label-info fw-bold mt-1" id="auditPeriodBadge">ช่วงเวลา: ปีงบประมาณปัจจุบัน</small>
              </div>
            </div>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-outline-primary" id="btnReRunAudit" onclick="runDataAuditCheck(true)">
                <i class="bx bx-refresh me-1"></i> ตรวจสอบสดใหม่
              </button>
            </div>
          </div>
        </div>

        <!-- 4 Detailed Checklist Cards -->
        <div class="row g-3">
          
          <!-- Checklist 1: HOSxP vs eDHS Completeness -->
          <div class="col-12 col-md-6">
            <div class="card border shadow-sm rounded-3 h-100 p-3 bg-white hover-elevate">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-label-primary p-2 rounded-circle"><i class="bx bx-server fs-5 text-primary"></i></span>
                  <strong class="text-dark">1. ความครบถ้วนกับ HOSxP</strong>
                </div>
                <span class="badge bg-success" id="chk1_badge"><i class="bx bx-check me-1"></i>สมบูรณ์ 100%</span>
              </div>
              <p class="small text-muted mb-2">ตรวจสอบรายการอุปกรณ์ที่สั่งใช้ใน HOSxP เทียบกับที่นำเข้าสู่ระบบ eDHS</p>
              <div class="p-2 rounded bg-light border small text-dark">
                <div class="d-flex justify-content-between mb-1">
                  <span>นำเข้า eDHS แล้ว:</span>
                  <strong class="text-primary" id="chk1_edhs_cnt">0 รายการ</strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span>ต้นทาง HOSxP:</span>
                  <strong class="text-dark" id="chk1_hos_cnt">0 รายการ</strong>
                </div>
                <div class="d-flex justify-content-between pt-1 border-top">
                  <span>รายการตกหล่น/ยังไม่ดึง:</span>
                  <strong class="text-success" id="chk1_gap_cnt">0 รายการ</strong>
                </div>
              </div>
              <div class="mt-2 text-end">
                <button type="button" class="btn btn-xs btn-outline-primary" onclick="triggerBackgroundAutoAudit(false)">
                  <i class="bx bx-sync me-1"></i> ดึง HOSxP ใหม่
                </button>
              </div>
            </div>
          </div>

          <!-- Checklist 2: STM Statement Reconciliation -->
          <div class="col-12 col-md-6">
            <div class="card border shadow-sm rounded-3 h-100 p-3 bg-white hover-elevate">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-label-success p-2 rounded-circle"><i class="bx bx-check-double fs-5 text-success"></i></span>
                  <strong class="text-dark">2. การชนยอด Statement (STM)</strong>
                </div>
                <span class="badge bg-success" id="chk2_badge"><i class="bx bx-check me-1"></i>ตรวจสอบแล้ว</span>
              </div>
              <p class="small text-muted mb-2">สถานะการตอบรับและชดเชยจาก สปสช. / กรมบัญชีกลาง / กองทุนต่างๆ</p>
              <div class="p-2 rounded bg-light border small text-dark">
                <div class="d-flex justify-content-between mb-1">
                  <span>ชดเชยครบ (PAID):</span>
                  <strong class="text-success" id="chk2_paid_cnt">0 รายการ</strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span>ชดเชยบางส่วน (PARTIAL):</span>
                  <strong class="text-info" id="chk2_partial_cnt">0 รายการ</strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span>รอผล STM (WAIT_STM):</span>
                  <strong class="text-warning" id="chk2_wait_cnt">0 รายการ</strong>
                </div>
                <div class="d-flex justify-content-between pt-1 border-top">
                  <span>ปฏิเสธการจ่าย (REJECTED):</span>
                  <strong class="text-danger" id="chk2_reject_cnt">0 รายการ</strong>
                </div>
              </div>
              <div class="mt-2 text-end">
                <button type="button" class="btn btn-xs btn-outline-danger" onclick="filterAuditRejectedCases()">
                  <i class="bx bx-shield-x me-1"></i> ดูรายการปฏิเสธ
                </button>
              </div>
            </div>
          </div>

          <!-- Checklist 3: Pre-claim Quality Audit (Missing CID / PDX) -->
          <div class="col-12 col-md-6">
            <div class="card border shadow-sm rounded-3 h-100 p-3 bg-white hover-elevate">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-label-warning p-2 rounded-circle"><i class="bx bx-id-card fs-5 text-warning"></i></span>
                  <strong class="text-dark">3. คุณภาพข้อมูลก่อนส่งเคลม</strong>
                </div>
                <span class="badge bg-success" id="chk3_badge"><i class="bx bx-check me-1"></i>ผ่านเกณฑ์</span>
              </div>
              <p class="small text-muted mb-2">ตรวจสอบเลขบัตรประชาชน 13 หลัก และรหัสโรคหลัก (ICD-10 PDX)</p>
              <div class="p-2 rounded bg-light border small text-dark">
                <div class="d-flex justify-content-between mb-1">
                  <span>ขาดเลขบัตร ปชช. (CID):</span>
                  <strong class="text-dark" id="chk3_cid_cnt">0 รายการ</strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span>ขาดรหัสโรคหลัก (PDX):</span>
                  <strong class="text-dark" id="chk3_diag_cnt">0 รายการ</strong>
                </div>
                <div class="d-flex justify-content-between pt-1 border-top">
                  <span>เคสที่เสี่ยงติด C-Deny:</span>
                  <strong class="text-danger" id="chk3_invalid_cnt">0 รายการ</strong>
                </div>
              </div>
              <div class="mt-2 text-end">
                <button type="button" class="btn btn-xs btn-outline-warning text-dark" onclick="filterAuditQualityCases()">
                  <i class="bx bx-search-alt me-1"></i> กรองดูเคสที่ต้องแก้ไข
                </button>
              </div>
            </div>
          </div>

          <!-- Checklist 4: Unclaimed & Aging Risk Audit -->
          <div class="col-12 col-md-6">
            <div class="card border shadow-sm rounded-3 h-100 p-3 bg-white hover-elevate">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-label-danger p-2 rounded-circle"><i class="bx bx-time fs-5 text-danger"></i></span>
                  <strong class="text-dark">4. ความเสี่ยงค้างส่งเคลม (Aging)</strong>
                </div>
                <span class="badge bg-success" id="chk4_badge"><i class="bx bx-check me-1"></i>ปกติ</span>
              </div>
              <p class="small text-muted mb-2">ตรวจสอบเคสที่ยังไม่ได้ส่งเคลม หรือค้างรอผล STM นานเกินกำหนด</p>
              <div class="p-2 rounded bg-light border small text-dark">
                <div class="d-flex justify-content-between mb-1">
                  <span>รอผลตรวจสอบ (31 - 90 วัน):</span>
                  <strong class="text-warning" id="chk4_aging_cnt">0 รายการ</strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span>ค้างเกิน 90 วัน (เสี่ยงหมดอายุความ):</span>
                  <strong class="text-danger" id="chk4_overdue_cnt">0 รายการ</strong>
                </div>
                <div class="d-flex justify-content-between pt-1 border-top">
                  <span>มูลค่าเสี่ยงสูญเสียโอกาส:</span>
                  <strong class="text-danger" id="chk4_overdue_amt">฿0</strong>
                </div>
              </div>
              <div class="mt-2 text-end">
                <button type="button" class="btn btn-xs btn-outline-danger" onclick="filterAuditUnclaimedCases()">
                  <i class="bx bx-list-check me-1"></i> ดูรายการค้างส่ง
                </button>
              </div>
            </div>
          </div>

        </div>

        <!-- Historical Audit Panel (Accordion / Collapse) -->
        <div class="card border shadow-sm rounded-3 mt-4 bg-white">
          <div class="card-header py-2 px-3 bg-light d-flex align-items-center justify-content-between">
            <span class="fw-bold text-dark small"><i class="bx bx-history me-1 text-primary"></i> รัน Data Audit ย้อนหลังตามปีงบประมาณ (Historical Audit)</span>
            <button class="btn btn-xs btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#collapseHistoricalAudit">
              <i class="bx bx-chevron-down"></i> ตัวเลือก
            </button>
          </div>
          <div class="collapse" id="collapseHistoricalAudit">
            <div class="card-body p-3 small">
              <p class="text-muted mb-2">เลือกปีงบประมาณที่ต้องการรันการดึงข้อมูลจาก HOSxP และ Audit ชนยอด Statement ย้อนหลังอย่างละเอียด:</p>
              <div class="row g-2 align-items-center">
                <div class="col-8 col-sm-6">
                  <select class="form-select form-select-sm text-dark fw-bold" id="historical_audit_year">
                    <!-- Populated dynamically via JS -->
                  </select>
                </div>
                <div class="col-4 col-sm-4">
                  <button type="button" class="btn btn-sm btn-primary w-100" id="btnExecuteHistoricalAudit" onclick="executeHistoricalAudit()">
                    <i class="bx bx-play me-1"></i> เริ่มรัน Audit
                  </button>
                </div>
              </div>
              <div id="historicalAuditProgress" class="mt-2" style="display: none;">
                <div class="d-flex align-items-center gap-2 text-primary">
                  <span class="spinner-border spinner-border-sm"></span>
                  <span id="historicalAuditProgressText">กำลังประมวลผลข้อมูล HOSxP...</span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="modal-footer py-2 px-4 bg-white rounded-bottom-4">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
          <i class="bx bx-x me-1"></i> ปิดหน้าต่าง
        </button>
      </div>

    </div>
  </div>
</div>
