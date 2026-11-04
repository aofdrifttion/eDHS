<!-- ====================================================================================
     🏥 eDebtor Hospital System (eDHS) - STM Pre-Import Preview & Audit Center Modal
     ====================================================================================
     หน้าต่าง Modal พรีวิวและตรวจสอบความถูกต้องของ Statement สปสช. (STM UC) ก่อนยืนยันนำเข้า
     แสดง 4 KPI, จำแนก 8 หมวดเงินชดเชย, สรุปรายหนังสือ REP, และตัวอย่างรายชื่อคนไข้
     ==================================================================================== -->

<style>
/* 🚀 ดัน SweetAlert2 (Modal ยืนยัน) ให้แสดงผลอยู่หน้าสุดทับทุก Modal และ Backdrop */
.swal2-container {
  z-index: 9999999 !important;
}
#modalStmPreview {
  z-index: 1095 !important;
}
#modalStmPreview .modal-dialog {
  max-width: 1280px !important;
  width: 95% !important;
  margin: 1.5rem auto !important;
}
#modalStmPreview .modal-content {
  border: none !important;
  border-radius: 16px !important;
  box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.25) !important;
  overflow: hidden !important;
  display: flex !important;
  flex-direction: column !important;
  max-height: 92vh !important;
}
#modalStmPreview .stm-preview-header {
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  color: #ffffff;
  padding: 1.15rem 1.75rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
#modalStmPreview .stm-preview-title-group {
  display: flex;
  align-items: center;
  gap: 14px;
}
#modalStmPreview .stm-preview-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  box-shadow: 0 4px 12px rgba(13, 148, 136, 0.35);
  flex-shrink: 0;
}
#modalStmPreview .stm-preview-title {
  font-size: 1.2rem;
  font-weight: 700;
  margin: 0;
  color: #ffffff;
  letter-spacing: -0.2px;
}
#modalStmPreview .stm-preview-subtitle {
  font-size: 0.84rem;
  color: #94a3b8;
  margin: 2px 0 0 0;
}

/* KPI Chips Bar */
#modalStmPreview .stm-kpi-bar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 14px;
  padding: 14px 1.75rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}
#modalStmPreview .stm-kpi-chip {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
#modalStmPreview .stm-kpi-chip:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
}
#modalStmPreview .stm-kpi-icon-wrap {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}
#modalStmPreview .stm-kpi-val {
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.2;
}
#modalStmPreview .stm-kpi-lbl {
  font-size: 0.78rem;
  color: #64748b;
  margin-top: 1px;
}

/* Tab Navigation */
#modalStmPreview .stm-preview-tabs {
  background: #ffffff;
  padding: 10px 1.75rem 0 1.75rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  gap: 8px;
}
#modalStmPreview .stm-tab-btn {
  padding: 8px 18px;
  border-radius: 10px 10px 0 0;
  border: 1px solid transparent;
  background: transparent;
  color: #64748b;
  font-weight: 600;
  font-size: 13.5px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  cursor: pointer;
  transition: all 0.2s ease;
}
#modalStmPreview .stm-tab-btn.active {
  background: #f0fdfa;
  color: #0f766e;
  border: 1px solid #ccfbf1;
  border-bottom-color: #f0fdfa;
}
#modalStmPreview .stm-tab-btn:hover:not(.active) {
  background: #f8fafc;
  color: #334155;
}

/* Modal Body & Table Container */
#modalStmPreview .modal-body {
  padding: 18px 1.75rem;
  overflow-y: auto;
  flex: 1 1 auto;
  background: #ffffff;
}
#modalStmPreview .table-preview-scroll {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow-x: auto;
  overflow-y: auto;
  max-height: 48vh;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}
#modalStmPreview table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 13.5px;
}
#modalStmPreview thead th {
  position: sticky;
  top: 0;
  background: #1e293b;
  color: #ffffff;
  font-weight: 600;
  padding: 10px 14px;
  text-align: center;
  border-bottom: 1px solid #334155;
  z-index: 10;
  white-space: nowrap;
}
#modalStmPreview tbody td {
  padding: 9px 14px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}
#modalStmPreview tbody tr:hover {
  background-color: #f8fafc;
}
#modalStmPreview tfoot th {
  position: sticky;
  bottom: 0;
  background: #f1f5f9;
  font-weight: 700;
  padding: 10px 14px;
  border-top: 2px solid #cbd5e1;
}

/* Status Badges */
.badge-status-new {
  background: #f0fdf4;
  color: #166534;
  border: 1px solid #bbf7d0;
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}
.badge-status-dup {
  background: #fffbeb;
  color: #b45309;
  border: 1px solid #fde68a;
  padding: 5px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

/* Modal Footer */
#modalStmPreview .modal-footer {
  padding: 14px 1.75rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}
.btn-stm-cancel {
  background: #ffffff !important;
  color: #475569 !important;
  border: 1px solid #cbd5e1 !important;
  font-weight: 600 !important;
  padding: 9px 20px !important;
  border-radius: 10px !important;
  cursor: pointer;
  transition: all 0.2s ease;
}
.btn-stm-cancel:hover {
  background: #f1f5f9 !important;
  color: #1e293b !important;
}
.btn-stm-confirm {
  background: linear-gradient(135deg, #0d9488 0%, #059669 100%) !important;
  color: #ffffff !important;
  border: none !important;
  font-weight: 600 !important;
  padding: 10px 24px !important;
  border-radius: 10px !important;
  box-shadow: 0 4px 14px rgba(13, 148, 136, 0.35) !important;
  display: inline-flex !important;
  align-items: center !important;
  gap: 8px !important;
  cursor: pointer;
  transition: all 0.2s ease;
}
.btn-stm-confirm:hover {
  background: linear-gradient(135deg, #0f766e 0%, #047857 100%) !important;
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(13, 148, 136, 0.45) !important;
}
.btn-stm-overwrite {
  background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important;
  color: #ffffff !important;
  border: none !important;
  font-weight: 600 !important;
  padding: 10px 24px !important;
  border-radius: 10px !important;
  box-shadow: 0 4px 14px rgba(234, 88, 12, 0.35) !important;
  display: inline-flex !important;
  align-items: center !important;
  gap: 8px !important;
  cursor: pointer;
}

/* Hero CR Decision Cards */
.stm-cr-card {
  background: #ffffff;
  border-radius: 12px;
  padding: 14px 18px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.stm-cr-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
}
.stm-cr-card-ready {
  border: 1px solid #a7f3d0;
  border-left: 5px solid #059669;
  background: linear-gradient(180deg, #ffffff 0%, #f0fdf4 100%);
}
.stm-cr-card-revenue {
  border: 1px solid #fde68a;
  border-left: 5px solid #d97706;
  background: linear-gradient(180deg, #ffffff 0%, #fffbeb 100%);
}
.stm-cr-card-total {
  border: 1px solid #bfdbfe;
  border-left: 5px solid #2563eb;
  background: linear-gradient(180deg, #ffffff 0%, #eff6ff 100%);
}
</style>

<!-- Modal Container -->
<div class="modal fade" id="modalStmPreview" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <!-- 1. Luxury Header -->
      <div class="stm-preview-header">
        <div class="stm-preview-title-group">
          <div class="stm-preview-icon">
            <i class='bx bx-spreadsheet'></i>
          </div>
          <div>
            <h5 class="stm-preview-title">ตรวจสอบและดูตัวอย่างข้อมูล Statement (STM Preview & Audit Center)</h5>
            <p class="stm-preview-subtitle" id="stm_preview_doc_info">
              กำลังโหลดข้อมูลเอกสาร...
            </p>
          </div>
        </div>

        <div class="d-flex align-items-center gap-3">
          <div id="stm_preview_status_badge">
            <span class="badge-status-new"><i class='bx bx-check-shield'></i> พร้อมนำเข้า</span>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>

      <!-- 2. Hero 4 KPI Metrics Chips (สำหรับ STM ปกติ) -->
      <div class="stm-kpi-bar" id="stm_kpi_chips_container">
        <!-- KPI 1: ผู้ป่วยทั้งหมด -->
        <div class="stm-kpi-chip">
          <div class="stm-kpi-icon-wrap" style="background:#eff6ff; color:#2563eb;">
            <i class='bx bx-group'></i>
          </div>
          <div>
            <div class="stm-kpi-val" id="kpi_stm_total_cases">0 ราย</div>
            <div class="stm-kpi-lbl">จำนวนผู้ป่วยทั้งหมด (ผ่าน A)</div>
          </div>
        </div>

        <!-- KPI 2: เงินชดเชยรวม -->
        <div class="stm-kpi-chip">
          <div class="stm-kpi-icon-wrap" style="background:#f0fdf4; color:#059669;">
            <i class='bx bx-money'></i>
          </div>
          <div>
            <div class="stm-kpi-val" style="color:#059669;" id="kpi_stm_total_comp">฿0.00</div>
            <div class="stm-kpi-lbl">เงินชดเชยสุทธิที่จ่ายจริง</div>
          </div>
        </div>

        <!-- KPI 3: จำนวนหนังสือ REP -->
        <div class="stm-kpi-chip">
          <div class="stm-kpi-icon-wrap" style="background:#fdf4ff; color:#a855f7;">
            <i class='bx bx-book-bookmark'></i>
          </div>
          <div>
            <div class="stm-kpi-val" id="kpi_stm_rep_count">0 REPs</div>
            <div class="stm-kpi-lbl">จำนวนหนังสือส่งเบิกทั้งหมด</div>
          </div>
        </div>

        <!-- KPI 4: อัตราแมตช์ลูกหนี้ -->
        <div class="stm-kpi-chip">
          <div class="stm-kpi-icon-wrap" style="background:#fef3c7; color:#d97706;">
            <i class='bx bx-target-lock'></i>
          </div>
          <div>
            <div class="stm-kpi-val" style="color:#0f766e;" id="kpi_stm_match_rate">0%</div>
            <div class="stm-kpi-lbl" id="kpi_stm_match_sub">พบในทะเบียนลูกหนี้ eDHS</div>
          </div>
        </div>
      </div>

      <!-- 2.1 Hero Decision Cards สำหรับ STM บริการเฉพาะ (CR) -->
      <div class="stm-kpi-bar" id="stm_cr_decision_cards" style="display: none; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
        <!-- Card 1: พร้อมตัดหนี้ CR (ยอดปกติ) -->
        <div class="stm-cr-card stm-cr-card-ready">
          <div class="stm-kpi-icon-wrap" style="background: #d1fae5; color: #059669; width: 44px; height: 44px; font-size: 24px;">
            <i class='bx bx-check-shield'></i>
          </div>
          <div class="flex-grow-1">
            <div class="stm-kpi-val text-success" id="cr_card_ready_val">0 ราย | ฿0.00</div>
            <div class="stm-kpi-lbl fw-bold text-dark">พร้อมตัดหนี้ CR (ยอดชดเชยปกติ)</div>
            <div class="text-muted" style="font-size: 11px;">ตัดหนี้ผัง CR (1102050101.216) ตามยอดเงินที่ได้รับจริง</div>
          </div>
        </div>

        <!-- Card 1.2: ชดเชย 0.00 บาท (ตัดหนี้ผัง CR) -->
        <div class="stm-cr-card stm-cr-card-zero" style="background: #f0fdfa; border-left: 4px solid #0d9488;">
          <div class="stm-kpi-icon-wrap" style="background: #ccfbf1; color: #0d9488; width: 44px; height: 44px; font-size: 24px;">
            <i class='bx bx-check-double'></i>
          </div>
          <div class="flex-grow-1">
            <div class="stm-kpi-val" style="color: #0d9488;" id="cr_card_zero_val">0 ราย | ฿0.00</div>
            <div class="stm-kpi-lbl fw-bold text-dark">ชดเชย 0.00 บ. (ตัดหนี้ผัง CR)</div>
            <div class="text-muted" style="font-size: 11px;">ชดเชย 0.00 บาทตาม Statement -> ตัดหนี้และแนบใบเสร็จผัง CR</div>
          </div>
        </div>

        <!-- Card 2: รับรู้เป็นรายได้ชดเชย -->
        <div class="stm-cr-card stm-cr-card-revenue">
          <div class="stm-kpi-icon-wrap" style="background: #fef3c7; color: #d97706; width: 44px; height: 44px; font-size: 24px;">
            <i class='bx bx-shield-quarter'></i>
          </div>
          <div class="flex-grow-1">
            <div class="stm-kpi-val text-warning" id="cr_card_revenue_val">0 ราย | ฿0.00</div>
            <div class="stm-kpi-lbl fw-bold text-dark">รับรู้เป็นรายได้ชดเชย (ล็อกไม่ตัดหนี้ซ้ำ)</div>
            <div class="text-muted" style="font-size: 11px;">มีใบเสร็จในผังหลักแล้ว -> ไม่ตัดหนี้ซ้ำซ้อน รับรู้เป็นรายได้</div>
          </div>
        </div>

        <!-- Card 3: รวมเงินชดเชยทั้งฉบับ -->
        <div class="stm-cr-card stm-cr-card-total">
          <div class="stm-kpi-icon-wrap" style="background: #dbeafe; color: #2563eb; width: 44px; height: 44px; font-size: 24px;">
            <i class='bx bx-receipt'></i>
          </div>
          <div class="flex-grow-1">
            <div class="stm-kpi-val text-primary" id="cr_card_total_val">0 ราย | ฿0.00</div>
            <div class="stm-kpi-lbl fw-bold text-dark">ยอดเงินชดเชยรวมทั้งฉบับ (ครบ 100%)</div>
            <div class="text-muted" style="font-size: 11px;">ยอดเงินชดเชยสุทธิที่ได้รับจริงจาก สปสช.</div>
          </div>
        </div>
      </div>

      <!-- 3. Navigation Tabs -->
      <div class="stm-preview-tabs">
        <button type="button" class="stm-tab-btn active" id="tab_btn_subgroups" onclick="switchStmPreviewTab('subgroups')">
          <i class='bx bx-pie-chart-alt-2'></i> จำแนกหมวดเงินชดเชย (8 หมวด สปสช.)
        </button>
        <button type="button" class="stm-tab-btn" onclick="switchStmPreviewTab('reps')">
          <i class='bx bx-list-check'></i> สรุปรายหนังสือ REP
        </button>
        <button type="button" class="stm-tab-btn" onclick="switchStmPreviewTab('patients')">
          <i class='bx bx-user-pin'></i> ตัวอย่างรายชื่อคนไข้
        </button>
      </div>

      <!-- 4. Modal Body Content -->
      <div class="modal-body">

        <!-- TAB 1: จำแนกหมวดเงินชดเชย -->
        <div id="tab_stm_subgroups" class="stm-tab-content">
          <div class="table-preview-scroll">
            <table class="table mb-0">
              <thead id="thead_stm_subgroups">
                <tr>
                  <th style="width: 70px;">ลำดับ</th>
                  <th style="text-align: left;" id="th_subgroup_title">หมวดการชดเชยค่าบริการทางการแพทย์ (สปสช.)</th>
                  <th style="width: 140px;">รหัสคอลัมน์ Excel</th>
                  <th style="text-align: right; width: 220px;">ยอดเงินชดเชยที่จ่ายจริง (บาท)</th>
                  <th style="width: 160px;">สัดส่วน</th>
                </tr>
              </thead>
              <tbody id="tbody_stm_subgroups">
                <!-- ข้อมูลจะถูก render โดย JavaScript -->
              </tbody>
              <tfoot id="tfoot_stm_subgroups">
                <tr>
                  <th colspan="3" style="text-align: center; color: #0f172a;">รวมเงินชดเชยทั้งสิ้น (ข้อมูลปกติ + ข้อมูลอุทธรณ์)</th>
                  <th style="text-align: right; color: #059669; font-size: 15px;" id="tfoot_stm_subgroups_total">฿0.00</th>
                  <th style="text-align: center; color: #0f172a;">100.00%</th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- TAB 2: สรุปรายหนังสือ REP (27 REPs) -->
        <div id="tab_stm_reps" class="stm-tab-content" style="display: none;">
          <div class="table-preview-scroll">
            <table class="table mb-0">
              <thead>
                <tr>
                  <th style="width: 60px;">ลำดับ</th>
                  <th>เลขที่หนังสือ (REP NO.)</th>
                  <th>ประเภท</th>
                  <th style="text-align: center;">จำนวนราย</th>
                  <th style="text-align: right;">ยอดเรียกเก็บ (บาท)</th>
                  <th style="text-align: right;">เงินชดเชย (บาท)</th>
                  <th>แหล่งข้อมูล</th>
                </tr>
              </thead>
              <tbody id="tbody_stm_reps">
                <!-- ข้อมูลจะถูก render โดย JavaScript -->
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="3" style="text-align: center;">รวมทุกหนังสือ REP</th>
                  <th style="text-align: center;" id="tfoot_stm_reps_cases">0 ราย</th>
                  <th style="text-align: right;" id="tfoot_stm_reps_collected">฿0.00</th>
                  <th style="text-align: right; color: #059669;" id="tfoot_stm_reps_compensated">฿0.00</th>
                  <th></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- TAB 3: ตัวอย่างรายชื่อคนไข้ -->
        <div id="tab_stm_patients" class="stm-tab-content" style="display: none;">
          <!-- ค้นหาตัวอย่างคนไข้ Realtime & Quick Filter Pills สำหรับ CR -->
          <div class="row g-2 mb-3 align-items-center">
            <div class="col-md-5">
              <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class='bx bx-search'></i></span>
                <input type="text" id="input_filter_stm_patient" class="form-control border-start-0 ps-0" placeholder="พิมพ์ค้นหา ชื่อ-สกุล, HN, หรือ VN...">
              </div>
            </div>
            <div class="col-md-7 d-flex justify-content-md-end align-items-center flex-wrap gap-2">
              <div class="btn-group btn-group-sm" role="group" id="stm_cr_filter_pills" style="display: none;">
                <button type="button" class="btn btn-outline-secondary active" id="pill_filter_all" onclick="filterStmCrStatus('ALL')">
                  ทั้งหมด (<span id="cnt_pill_all">0</span>)
                </button>
                <button type="button" class="btn btn-outline-success" id="pill_filter_ready" onclick="filterStmCrStatus('READY_CR')">
                  🟢 ชดเชยปกติ (<span id="cnt_pill_ready">0</span>)
                </button>
                <button type="button" class="btn btn-outline-info" id="pill_filter_zero" onclick="filterStmCrStatus('ZERO_COMP')">
                  ⚪ ชดเชย 0.00 บ. (<span id="cnt_pill_zero">0</span>)
                </button>
                <button type="button" class="btn btn-outline-warning" id="pill_filter_billed" onclick="filterStmCrStatus('ALREADY_BILLED_PARENT')">
                  🟡 มีใบเสร็จเดิม (<span id="cnt_pill_billed">0</span>)
                </button>
              </div>
              <span class="text-muted small" id="lbl_patient_preview_count">กำลังแสดงรายการ</span>
            </div>
          </div>

          <div class="table-preview-scroll">
            <table class="table mb-0" id="table_stm_patients">
              <thead>
                <tr>
                  <th style="width: 55px;">ลำดับ</th>
                  <th>เลข REP</th>
                  <th id="th_stm_patient_vn">VN (SEQ NO)</th>
                  <th>HN</th>
                  <th style="text-align: left;">ชื่อ - สกุล</th>
                  <th>วันเข้ารับบริการ</th>
                  <th style="text-align: left;">ผังลูกหนี้ eDHS</th>
                  <th style="text-align: right;">เรียกเก็บ</th>
                  <th style="text-align: right;">ภาระหนี้เดิม</th>
                  <th style="text-align: right;">เงินชดเชย</th>
                  <th style="text-align: right;">ผลต่าง</th>
                  <th>สถานะแมตช์</th>
                </tr>
              </thead>
              <tbody id="tbody_stm_patients">
                <!-- ข้อมูลจะถูก render โดย JavaScript -->
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- 5. Modal Footer Actions -->
      <div class="modal-footer">
        <div class="d-flex align-items-center gap-2 flex-grow-1">
          <button type="button" class="btn btn-sm btn-outline-success" id="btn_export_cr_excel" style="display: none;" onclick="exportStmCrExcel()">
            <i class='bx bx-export'></i> ส่งออกสรุป CR (Excel/CSV)
          </button>
          <span id="stm_preview_notice" class="text-muted small">
            <i class='bx bx-info-circle text-info'></i> ข้อมูลนี้เป็นการพรีวิวในหน่วยความจำ ยังไม่มีการเขียนลงฐานข้อมูลจนกว่าจะกดยืนยัน
          </span>
        </div>

        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-stm-cancel" data-bs-dismiss="modal" data-dismiss="modal">
            <i class='bx bx-x'></i> ยกเลิก (Cancel)
          </button>

          <button type="button" id="btn_confirm_stm_import" class="btn btn-stm-confirm" onclick="executeStmConfirmImport(false)">
            <i class='bx bx-check-double'></i> ยืนยันนำเข้าข้อมูลเข้าสู่ระบบ
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
/**
 * สลับแท็บใน Modal พรีวิว Statement
 */
function switchStmPreviewTab(tabKey) {
  $('.stm-preview-tabs .stm-tab-btn').removeClass('active');
  $('.stm-tab-content').hide();

  if (tabKey === 'subgroups') {
    $('.stm-preview-tabs .stm-tab-btn').eq(0).addClass('active');
    $('#tab_stm_subgroups').show();
  } else if (tabKey === 'reps') {
    $('.stm-preview-tabs .stm-tab-btn').eq(1).addClass('active');
    $('#tab_stm_reps').show();
  } else if (tabKey === 'patients') {
    $('.stm-preview-tabs .stm-tab-btn').eq(2).addClass('active');
    $('#tab_stm_patients').show();
  }
}

/**
 * จัดรูปแบบตัวเลขการเงิน
 */
function formatStmMoney(num) {
  return Number(num || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/**
 * ฟังก์ชันหลักในการแสดงผลข้อมูลพรีวิวบน Modal
 */
function populateStmPreviewModal(data) {
  if (!data || data.status !== 'success') return;

  var meta = data.metadata;
  var kpi = data.summary_kpi;
  var sub = data.subgroup_totals;
  var reps = data.rep_summaries;
  var patients = data.sample_patients;
  var dup = data.duplicate_info;

  // 1. หัวเอกสาร (Header Metadata)
  var docInfoText = '<b>เลขที่เอกสาร:</b> ' + (meta.docno || '-') +
                    ' &nbsp;•&nbsp; <b>งวดเดือน:</b> ' + (meta.period || '-') +
                    ' &nbsp;•&nbsp; <b>หน่วยบริการ:</b> ' + (meta.hospcode || '') + ' ' + (meta.hospname || '') +
                    ' &nbsp;•&nbsp; <b>สิทธิ:</b> ' + (meta.fund || 'UCS') + ' (' + (meta.visit_type || 'OPD') + ')';
  $('#stm_preview_doc_info').html(docInfoText);
  if (meta.visit_type === 'IPD') {
    $('#th_stm_patient_vn').text('AN (ผู้ป่วยใน)');
  } else {
    $('#th_stm_patient_vn').text('VN (SEQ NO)');
  }

  // 2. ป้ายสถานะซ้ำซ้อน (Status Badge)
  var badgeHtml = '';
  var isDuplicate = dup && dup.is_duplicate;
  if (isDuplicate) {
    badgeHtml = '<span class="badge-status-dup" title="พบข้อมูลเอกสารหรือหนังสือนี้ในระบบแล้ว"><i class="bx bx-error"></i> มีข้อมูลในระบบแล้ว (' + dup.existing_count.toLocaleString() + ' รายการ)</span>';
    $('#btn_confirm_stm_import')
      .removeClass('btn-stm-confirm')
      .addClass('btn-stm-overwrite')
      .html('<i class="bx bx-revision"></i> ลบของเดิมแล้วนำเข้าใหม่ (Replace & Import)')
      .attr('onclick', 'executeStmConfirmImport(true)');
    
    $('#stm_preview_notice').html('<span class="text-warning fw-semibold"><i class="bx bxs-error-circle"></i> ตรวจพบข้อมูลเอกสารนี้ในระบบแล้ว หากกดยืนยัน ระบบจะลบชุดเดิมและเขียนทับใหม่</span>');
  } else {
    badgeHtml = '<span class="badge-status-new"><i class="bx bx-check-shield"></i> พร้อมนำเข้า (ข้อมูลใหม่)</span>';
    $('#btn_confirm_stm_import')
      .removeClass('btn-stm-overwrite')
      .addClass('btn-stm-confirm')
      .html('<i class="bx bx-check-double"></i> ยืนยันนำเข้าข้อมูลเข้าสู่ระบบ')
      .attr('onclick', 'executeStmConfirmImport(false)');

    $('#stm_preview_notice').html('<i class="bx bx-info-circle text-info"></i> ข้อมูลนี้เป็นการพรีวิวในหน่วยความจำ ยังไม่มีการเขียนลงฐานข้อมูลจนกว่าจะกดยืนยัน');
  }
  $('#stm_preview_status_badge').html(badgeHtml);

  // 3. KPI Chips / CR Hero Decision Cards Branching
  var isCr = (data.format_type === 'INDIVIDUAL_CR');

  if (isCr) {
    $('#stm_kpi_chips_container').hide();
    $('#stm_cr_decision_cards').show();
    $('#btn_export_cr_excel').show();
    $('#stm_cr_filter_pills').show();

    var zeroCases = kpi.zero_cr_cases || 0;
    var readyNonZeroCases = (kpi.ready_cr_cases || 0) - zeroCases;
    if (readyNonZeroCases < 0) readyNonZeroCases = 0;

    $('#cr_card_ready_val').text(readyNonZeroCases.toLocaleString() + ' ราย | ฿' + formatStmMoney(kpi.ready_cr_comp || 0));
    $('#cr_card_zero_val').text(zeroCases.toLocaleString() + ' ราย | ฿0.00');
    $('#cr_card_revenue_val').text((kpi.already_billed_cases || 0).toLocaleString() + ' ราย | ฿' + formatStmMoney(kpi.already_billed_comp || 0));
    $('#cr_card_total_val').text((kpi.total_cases || 0).toLocaleString() + ' ราย | ฿' + formatStmMoney(kpi.total_comp || 0));

    $('#cnt_pill_all').text((kpi.total_cases || 0).toLocaleString());
    $('#cnt_pill_ready').text(readyNonZeroCases.toLocaleString());
    $('#cnt_pill_zero').text(zeroCases.toLocaleString());
    $('#cnt_pill_billed').text((kpi.already_billed_cases || 0).toLocaleString());
  } else {
    $('#stm_cr_decision_cards').hide();
    $('#stm_kpi_chips_container').show();
    $('#btn_export_cr_excel').hide();
    $('#stm_cr_filter_pills').hide();

    $('#kpi_stm_total_cases').text(kpi.total_cases.toLocaleString() + ' ราย');
    $('#kpi_stm_total_comp').text('฿' + formatStmMoney(kpi.total_comp));
    $('#kpi_stm_rep_count').text(kpi.rep_count + ' REPs');
    $('#kpi_stm_match_rate').text(kpi.match_percent + '%');
    $('#kpi_stm_match_sub').text('แมตช์ ' + kpi.matched_debtor_count.toLocaleString() + '/' + kpi.total_cases.toLocaleString() + ' ราย');
  }

  // แจ้งเตือนสถานะความปลอดภัย
  $('#cr_safety_lock_alert').remove();

  // 4. TAB 1: Render หมวดเงินชดเชย
  if (isCr) {
    $('#tab_btn_subgroups').html("<i class='bx bx-pie-chart-alt-2'></i> จำแนกหมวดเงินชดเชยบริการเฉพาะ (8 หมวด CR)");
    var crTheadHtml = '<tr>' +
      '<th style="width: 55px;">ลำดับ</th>' +
      '<th style="text-align: left;">หมวดการชดเชยค่าบริการทางการแพทย์ (CR)</th>' +
      '<th style="text-align: right; width: 180px;">พร้อมตัดหนี้ CR (.216)</th>' +
      '<th style="text-align: right; width: 180px;">รับรู้เป็นรายได้ (มีใบเสร็จเดิม)</th>' +
      '<th style="text-align: right; width: 180px;">รวมเงินชดเชย (บาท)</th>' +
      '<th style="width: 100px; text-align: center;">สัดส่วน</th>' +
    '</tr>';
    $('#thead_stm_subgroups').html(crTheadHtml);

    var subHtml = '';
    var totalComp = kpi.total_comp > 0 ? kpi.total_comp : 1;
    (data.display_categories || []).forEach(function(item, idx) {
      var crVal = parseFloat(item.cr_comp || 0);
      var crCases = parseInt(item.cr_cases || 0);
      var revVal = parseFloat(item.revenue_comp || 0);
      var revCases = parseInt(item.revenue_cases || 0);
      var totVal = parseFloat(item.total_comp || item.val || 0);
      var totCases = parseInt(item.total_cases || 0);
      var pct = ((totVal / totalComp) * 100).toFixed(2);
      var rowClass = totVal > 0 ? 'fw-semibold' : 'text-muted';

      subHtml += '<tr>' +
        '<td class="text-center">' + (idx + 1) + '</td>' +
        '<td class="' + rowClass + '">' +
          '<div class="fw-bold text-dark">' + item.name + '</div>' +
          '<div class="text-muted small">' + (item.col || '') + '</div>' +
        '</td>' +
        '<td class="text-end">' +
          '<div class="' + (crVal > 0 ? 'text-success fw-bold' : 'text-muted') + '">฿' + formatStmMoney(crVal) + '</div>' +
          '<div class="text-muted small">' + crCases.toLocaleString() + ' ราย</div>' +
        '</td>' +
        '<td class="text-end">' +
          '<div class="' + (revVal > 0 ? 'text-warning fw-bold' : 'text-muted') + '">฿' + formatStmMoney(revVal) + '</div>' +
          '<div class="text-muted small">' + revCases.toLocaleString() + ' ราย</div>' +
        '</td>' +
        '<td class="text-end">' +
          '<div class="' + (totVal > 0 ? 'text-primary fw-bold' : 'text-muted') + '">฿' + formatStmMoney(totVal) + '</div>' +
          '<div class="text-muted small">' + totCases.toLocaleString() + ' ราย</div>' +
        '</td>' +
        '<td class="text-center text-muted fw-semibold">' + pct + '%</td>' +
      '</tr>';
    });
    $('#tbody_stm_subgroups').html(subHtml);

    var crTfootHtml = '<tr>' +
      '<th colspan="2" class="text-center" style="color: #0f172a; font-size: 14px;">รวมเงินชดเชยทั้งสิ้น (8 หมวดบริการเฉพาะ)</th>' +
      '<th class="text-end text-success" style="font-size: 14px;">' +
        '฿' + formatStmMoney(kpi.ready_cr_comp || 0) + '<br><small class="fw-normal text-muted">' + (kpi.ready_cr_cases || 0).toLocaleString() + ' ราย</small>' +
      '</th>' +
      '<th class="text-end text-warning" style="font-size: 14px;">' +
        '฿' + formatStmMoney(kpi.already_billed_comp || 0) + '<br><small class="fw-normal text-muted">' + (kpi.already_billed_cases || 0).toLocaleString() + ' ราย</small>' +
      '</th>' +
      '<th class="text-end text-primary" style="font-size: 14px;">' +
        '฿' + formatStmMoney(kpi.total_comp || 0) + '<br><small class="fw-normal text-muted">' + (kpi.total_cases || 0).toLocaleString() + ' ราย</small>' +
      '</th>' +
      '<th class="text-center" style="font-size: 14px;">100.00%</th>' +
    '</tr>';
    $('#tfoot_stm_subgroups').html(crTfootHtml);

  } else {
    // Non-CR Standard Rendering
    var isNonUc = (data.format_type === 'NON_UC' || (meta.fund && meta.fund !== 'UCS'));
    if (isNonUc) {
      $('#tab_btn_subgroups').html("<i class='bx bx-pie-chart-alt-2'></i> จำแนกหมวดเงินชดเชย (7 หมวด Non-UC)");
      $('#th_subgroup_title').text("หมวดการชดเชยค่าบริการทางการแพทย์ (" + (meta.fund || 'Non-UC') + ")");
    } else {
      $('#tab_btn_subgroups').html("<i class='bx bx-pie-chart-alt-2'></i> จำแนกหมวดเงินชดเชย (8 หมวด สปสช.)");
      $('#th_subgroup_title').text("หมวดการชดเชยค่าบริการทางการแพทย์ (สปสช.)");
    }

    var stdTheadHtml = '<tr>' +
      '<th style="width: 70px;">ลำดับ</th>' +
      '<th style="text-align: left;">' + (isNonUc ? "หมวดการชดเชยค่าบริการทางการแพทย์ (" + (meta.fund || 'Non-UC') + ")" : "หมวดการชดเชยค่าบริการทางการแพทย์ (สปสช.)") + '</th>' +
      '<th style="width: 140px;">รหัสคอลัมน์ Excel</th>' +
      '<th style="text-align: right; width: 220px;">ยอดเงินชดเชยที่จ่ายจริง (บาท)</th>' +
      '<th style="width: 160px;">สัดส่วน</th>' +
    '</tr>';
    $('#thead_stm_subgroups').html(stdTheadHtml);

    var subCategories = (data.display_categories && data.display_categories.length) ? data.display_categories : [
      { key: 'op', name: '1. OP (ผู้ป่วยนอกทั่วไป)', col: 'Col 21 (พึงรับ OP)', val: sub.op },
      { key: 'hc', name: '2. HC & ยาจิตเวช (Home Care / ยาเฉพาะ)', col: 'Col 24+25 (HC+DRUG)', val: sub.hc },
      { key: 'ae', name: '3. AE (ฉุกเฉิน / อุบัติเหตุ)', col: 'Col 26+27 (AE+DRUG)', val: sub.ae },
      { key: 'inst', name: '4. INST (อุปกรณ์และอวัยวะเทียม Fee Schedule)', col: 'Col 28 (INST)', val: sub.inst },
      { key: 'dmis', name: '5. DMIS (โรคเรื้อรังเฉพาะกลุ่ม)', col: 'Col 30+31 (DMIS+DRUG)', val: sub.dmis },
      { key: 'pallativecare', name: '6. Palliative Care (การดูแลระยะประคับประคอง)', col: 'Col 32 (Palliative)', val: sub.pallativecare },
      { key: 'pp', name: '7. PP (สร้างเสริมสุขภาพและป้องกันโรค)', col: 'Col 34 (PP)', val: sub.pp },
      { key: 'fs', name: '8. FS (บริการ Fee Schedule จ่ายตามรายการ)', col: 'Col 35 (FS)', val: sub.fs }
    ];

    var subHtml = '';
    var totalComp = kpi.total_comp > 0 ? kpi.total_comp : 1;
    subCategories.forEach(function(item, idx) {
      var val = parseFloat(item.val || 0);
      var pct = ((val / totalComp) * 100).toFixed(2);
      var rowClass = val > 0 ? 'fw-semibold' : 'text-muted';
      subHtml += '<tr>' +
        '<td class="text-center">' + (idx + 1) + '</td>' +
        '<td class="' + rowClass + '">' + item.name + '</td>' +
        '<td class="text-center text-muted small">' + item.col + '</td>' +
        '<td class="text-end ' + (val > 0 ? 'text-success fw-bold' : 'text-muted') + '">฿' + formatStmMoney(val) + '</td>' +
        '<td class="text-center text-muted">' + pct + '%</td>' +
      '</tr>';
    });
    $('#tbody_stm_subgroups').html(subHtml);

    var stdTfootHtml = '<tr>' +
      '<th colspan="3" style="text-align: center; color: #0f172a;">รวมเงินชดเชยทั้งสิ้น (ข้อมูลปกติ + ข้อมูลอุทธรณ์)</th>' +
      '<th style="text-align: right; color: #059669; font-size: 15px;" id="tfoot_stm_subgroups_total">฿' + formatStmMoney(kpi.total_comp) + '</th>' +
      '<th style="text-align: center; color: #0f172a;">100.00%</th>' +
    '</tr>';
    $('#tfoot_stm_subgroups').html(stdTfootHtml);
  }

  // 5. TAB 2: Render สรุปรายหนังสือ REP
  var repsHtml = '';
  reps.forEach(function(r, idx) {
    var typeBadge = r.is_appeal ?
      '<span class="badge bg-warning text-dark">อุทธรณ์</span>' :
      '<span class="badge bg-light text-secondary border">ปกติ</span>';
    repsHtml += '<tr>' +
      '<td class="text-center">' + (idx + 1) + '</td>' +
      '<td class="text-center fw-bold text-primary">' + r.rep + '</td>' +
      '<td class="text-center">' + typeBadge + '</td>' +
      '<td class="text-center">' + r.case_count.toLocaleString() + ' ราย</td>' +
      '<td class="text-end">' + formatStmMoney(r.total_collected) + '</td>' +
      '<td class="text-end fw-bold text-success">' + formatStmMoney(r.total_compensated) + '</td>' +
      '<td class="text-center"><span class="badge bg-light text-dark border">' + (r.source || 'FDH') + '</span></td>' +
    '</tr>';
  });
  $('#tbody_stm_reps').html(repsHtml);
  $('#tfoot_stm_reps_cases').text(kpi.total_cases.toLocaleString() + ' ราย');
  $('#tfoot_stm_reps_collected').text('฿' + formatStmMoney(kpi.total_collected));
  $('#tfoot_stm_reps_compensated').text('฿' + formatStmMoney(kpi.total_comp));

  // 6. TAB 3: Render ตัวอย่างรายชื่อคนไข้
  var patientsHtml = '';
  patients.forEach(function(p) {
    var matchBadge = '';
    var isZeroComp = (parseFloat(p.compensated) === 0);
    if (p.paidtype === 'READY_CR') {
      if (isZeroComp) {
        matchBadge = '<span class="badge bg-label-info text-info small" title="ชดเชย 0.00 บาทตาม Statement พร้อมตัดหนี้ผัง CR"><i class="bx bx-check-double"></i> พร้อมตัดหนี้ CR (0.00 บ.)</span>';
      } else {
        matchBadge = '<span class="badge bg-success small" title="พร้อมตัดหนี้ CR เข้าผัง 1102050101.216"><i class="bx bx-check-circle"></i> พร้อมตัดหนี้ CR (.216)</span>';
      }
    } else if (p.paidtype === 'ALREADY_BILLED_PARENT') {
      matchBadge = '<span class="badge bg-warning text-dark small" title="มีใบเสร็จในผังหลักแล้ว (ล็อกความปลอดภัย ไม่ตัดหนี้ซ้ำ)"><i class="bx bx-shield-quarter"></i> มีใบเสร็จเดิม (รับรู้รายได้)</span>';
    } else if (p.is_matched) {
      matchBadge = '<span class="badge bg-info small"><i class="bx bx-check"></i> พบในลูกหนี้</span>';
    } else {
      matchBadge = '<span class="badge bg-danger small"><i class="bx bx-x"></i> ไม่พบ</span>';
    }
    var diffClass = p.diff < 0 ? 'text-danger' : (p.diff > 0 ? 'text-success' : 'text-muted');

    patientsHtml += '<tr data-paidtype="' + (p.paidtype || '') + '" data-is-zero="' + (isZeroComp ? '1' : '0') + '">' +
      '<td class="text-center">' + p.seq + '</td>' +
      '<td class="text-center text-muted small">' + p.rep + '</td>' +
      '<td class="text-center fw-semibold text-primary">' + p.vn + '</td>' +
      '<td class="text-center">' + p.hn + '</td>' +
      '<td>' + p.ptname + '</td>' +
      '<td class="text-center small text-muted">' + p.admdate + '</td>' +
      '<td class="small text-truncate" style="max-width: 180px;">' + p.accountname + '</td>' +
      '<td class="text-end">' + formatStmMoney(p.collected) + '</td>' +
      '<td class="text-end">' + formatStmMoney(p.debit) + '</td>' +
      '<td class="text-end fw-bold ' + (isZeroComp ? 'text-muted' : 'text-success') + '">' + formatStmMoney(p.compensated) + '</td>' +
      '<td class="text-end fw-semibold ' + diffClass + '">' + formatStmMoney(p.diff) + '</td>' +
      '<td class="text-center">' + matchBadge + '</td>' +
    '</tr>';
  });
  $('#tbody_stm_patients').html(patientsHtml);

  // รีเซ็ตตัวกรองคนไข้
  currentCrStatusFilter = 'ALL';
  $('#stm_cr_filter_pills .btn').removeClass('active');
  $('#pill_filter_all').addClass('active');
  $('#input_filter_stm_patient').val('');
  $('#lbl_patient_preview_count').text('กำลังแสดง ' + patients.length.toLocaleString() + ' รายการ' + (isCr ? ' (ครบทั้งหมดจากไฟล์)' : ''));

  // เปิด Modal พรีวิว
  switchStmPreviewTab('subgroups');
  var previewEl = document.getElementById('modalStmPreview');
  if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
    bootstrap.Modal.getOrCreateInstance(previewEl).show();
  } else {
    $('#modalStmPreview').modal('show');
  }
}

/**
 * จัดการตัวกรองสถานะ CR (Pills ในแท็บ 3)
 */
var currentCrStatusFilter = 'ALL';
function filterStmCrStatus(statusKey) {
  currentCrStatusFilter = statusKey;
  $('#stm_cr_filter_pills .btn').removeClass('active');
  if (statusKey === 'ALL') $('#pill_filter_all').addClass('active');
  else if (statusKey === 'READY_CR') $('#pill_filter_ready').addClass('active');
  else if (statusKey === 'ZERO_COMP') $('#pill_filter_zero').addClass('active');
  else if (statusKey === 'ALREADY_BILLED_PARENT') $('#pill_filter_billed').addClass('active');
  applyStmPatientFilters();
}

/**
 * คำนวณและแสดงผลตัวกรองคนไข้ตามสถานะและคำค้นหา
 */
function applyStmPatientFilters() {
  var term = ($('#input_filter_stm_patient').val() || '').toLowerCase().trim();
  var visibleCount = 0;
  $('#tbody_stm_patients tr').each(function() {
    var row = $(this);
    var rowStatus = row.attr('data-paidtype') || '';
    var isRowZero = row.attr('data-is-zero') === '1';
    var matchesStatus = false;
    if (currentCrStatusFilter === 'ALL') {
      matchesStatus = true;
    } else if (currentCrStatusFilter === 'READY_CR') {
      matchesStatus = (rowStatus === 'READY_CR' && !isRowZero);
    } else if (currentCrStatusFilter === 'ZERO_COMP') {
      matchesStatus = (rowStatus === 'READY_CR' && isRowZero);
    } else if (currentCrStatusFilter === 'ALREADY_BILLED_PARENT') {
      matchesStatus = (rowStatus === 'ALREADY_BILLED_PARENT');
    }
    var matchesText = (term === '') || (row.text().toLowerCase().indexOf(term) > -1);
    if (matchesStatus && matchesText) {
      row.show();
      visibleCount++;
    } else {
      row.hide();
    }
  });
  $('#lbl_patient_preview_count').text('กำลังแสดง ' + visibleCount.toLocaleString() + ' รายการ');
}

/**
 * ส่งออกรายงานสรุป CR เป็น Excel/CSV
 */
function exportStmCrExcel() {
  window.location.href = 'apistm/api_stm_ucs.php?action=export_cr_summary';
}

/**
 * ตัวกรองค้นหารายชื่อคนไข้ในแท็บตัวอย่าง (หุ้มด้วย DOMContentLoaded เพื่อป้องกัน $ is not defined ก่อน jQuery โหลด)
 */
document.addEventListener('DOMContentLoaded', function() {
  if (typeof $ !== 'undefined') {
    $(document).on('keyup input', '#input_filter_stm_patient', function() {
      applyStmPatientFilters();
    });
  } else {
    var filterInput = document.getElementById('input_filter_stm_patient');
    if (filterInput) {
      filterInput.addEventListener('input', function() {
        applyStmPatientFilters();
      });
    }
  }
});

/**
 * ดำเนินการยืนยันการนำเข้าข้อมูล (Confirm Import)
 */
function executeStmConfirmImport(confirmOverwrite) {
  var confirmTitle = confirmOverwrite ?
    'ยืนยันการลบของเดิมและนำเข้าใหม่?' :
    'ยืนยันการนำเข้า Statement เข้าสู่ระบบ?';
  var confirmText = confirmOverwrite ?
    'ระบบจะลบข้อมูลเอกสารชุดเดิมออกและบันทึกชุดใหม่เข้าไปแทนที่ ยอดเงินชดเชยจะถูกอัปเดตใหม่ทันที' :
    'ข้อมูล Statement จะถูกบันทึกเข้าสู่ระบบ eDHS เพื่อใช้ตัดยอดลูกหนี้และกระทบยอดเงินชดเชย';

  Swal.fire({
    title: confirmTitle,
    text: confirmText,
    icon: confirmOverwrite ? 'warning' : 'question',
    showCancelButton: true,
    confirmButtonColor: confirmOverwrite ? '#ea580c' : '#0d9488',
    cancelButtonColor: '#64748b',
    confirmButtonText: confirmOverwrite ? 'ใช่, ดำเนินการเขียนทับ' : 'ใช่, ยืนยันนำเข้า',
    cancelButtonText: 'ยกเลิก'
  }).then(function(res) {
    if (res.isConfirmed) {
      Swal.fire({
        title: 'กำลังบันทึกข้อมูลเข้าสู่ระบบ...',
        text: 'กรุณารอสักครู่ ระบบกำลังจับคู่ผังลูกหนี้และบันทึกข้อมูลลงฐานข้อมูล',
        allowOutsideClick: false,
        didOpen: function() {
          Swal.showLoading();
        }
      });

      $.ajax({
        url: 'apistm/api_stm_ucs.php',
        type: 'POST',
        dataType: 'json',
        data: {
          action: 'confirm_import_stm',
          confirm_overwrite: confirmOverwrite ? '1' : '0'
        },
        timeout: 300000,
        success: function(resp) {
          Swal.close();
          if (resp.status === 'success') {
            var previewEl = document.getElementById('modalStmPreview');
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
              bootstrap.Modal.getOrCreateInstance(previewEl).hide();
            } else {
              $('#modalStmPreview').modal('hide');
            }
            Swal.fire({
              icon: 'success',
              title: 'นำเข้าข้อมูลสำเร็จเรียบร้อย! 🎉',
              html: '<p class="mb-1"><b>เลขที่เอกสาร:</b> ' + (resp.docno || '-') + '</p>' +
                    '<p class="mb-1"><b>บันทึกสำเร็จ:</b> ' + (resp.inserted_count || 0).toLocaleString() + ' รายการ</p>' +
                    '<p class="mb-1 text-success"><b>เงินชดเชยรวม:</b> ฿' + formatStmMoney(resp.total_compensated) + ' บาท</p>' +
                    '<p class="text-muted small">แมตช์ผังลูกหนี้สำเร็จ ' + (resp.matched_debtor_count || 0).toLocaleString() + ' ราย</p>',
              confirmButtonText: 'ตกลง'
            }).then(function() {
              location.reload();
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'เกิดข้อผิดพลาด!',
              text: resp.message || 'ไม่สามารถบันทึกข้อมูลได้'
            });
          }
        },
        error: function(xhr, status, error) {
          Swal.close();
          Swal.fire({
            icon: 'error',
            title: 'ข้อผิดพลาดในการเชื่อมต่อ!',
            text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้: ' + error
          });
        }
      });
    }
  });
}
</script>
