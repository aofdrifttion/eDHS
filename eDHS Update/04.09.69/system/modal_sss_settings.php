<?php
/**
 * ====================================================================================
 * 🏥 eDebtor Hospital System (eDHS) - Modal SSS Subgroups Configuration
 * ====================================================================================
 * หน้าต่าง Modal สำหรับผู้ดูแลระบบ (Admin) ตั้งค่าระบบจำแนกและตัดแยกหมวดย่อยประกันสังคม
 * รองรับการเลือกเดือนที่เริ่มใช้งาน, ผังบัญชีเป้าหมาย, เงื่อนไขรายกลุ่ม (OPD & IPD) และทดสอบผลกระทบ
 * ====================================================================================
 */
?>

<style>
/* บังคับให้ Modal และ SweetAlert2 แสดงผลด้านหน้า backdrop เสมอ */
#modalSssSubgroupSetting {
    z-index: 10050 !important;
}
#modalSssSubgroupSetting .modal-dialog {
    max-width: 1240px !important;
    width: 95% !important;
    margin: 1.75rem auto !important;
}
#modalSssSubgroupSetting .modal-body {
    min-height: 520px !important;
    background-color: #f8fafc !important;
}
#modalAddNewSssSubgroup {
    z-index: 10060 !important;
}
.swal2-container {
    z-index: 9999999 !important;
}

/* 🎨 สไตล์แท็บหลัก 3.1 OPD และ 3.2 IPD */
#sssMainTypeTabs .nav-link {
    border-radius: 10px 10px 0 0 !important;
    border: 1px solid #cbd5e1 !important;
    border-bottom: none !important;
    font-size: 15px !important;
    padding: 12px 20px !important;
    background-color: #f1f5f9;
    color: #64748b;
    transition: all 0.25s ease-in-out;
}
#sssMainTypeTabs #tab_sss_opd_btn.active {
    background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%) !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    border-color: #1d4ed8 !important;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35) !important;
}
#sssMainTypeTabs #tab_sss_ipd_btn.active {
    background: linear-gradient(135deg, #047857 0%, #10b981 100%) !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    border-color: #047857 !important;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35) !important;
}

/* 🎨 สไตล์แท็บย่อย Subgroup Pills (OPD) */
#sssSettingSubgroupTabsOpd .nav-link {
    border: 1px solid #cbd5e1;
    color: #475569;
    font-weight: 600;
    border-radius: 8px;
    padding: 6px 14px;
    background-color: #ffffff;
    transition: all 0.2s ease;
}
#sssSettingSubgroupTabsOpd .nav-link:hover {
    background-color: #eff6ff;
    color: #1d4ed8;
    border-color: #93c5fd;
}
#sssSettingSubgroupTabsOpd .nav-link.active {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
    color: #ffffff !important;
    border-color: #1d4ed8 !important;
    box-shadow: 0 3px 8px rgba(37, 99, 235, 0.3) !important;
}

/* 🎨 สไตล์แท็บย่อย Subgroup Pills (IPD) */
#sssSettingSubgroupTabsIpd .nav-link {
    border: 1px solid #cbd5e1;
    color: #475569;
    font-weight: 600;
    border-radius: 8px;
    padding: 6px 14px;
    background-color: #ffffff;
    transition: all 0.2s ease;
}
#sssSettingSubgroupTabsIpd .nav-link:hover {
    background-color: #ecfdf5;
    color: #047857;
    border-color: #a7f3d0;
}
#sssSettingSubgroupTabsIpd .nav-link.active {
    background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
    color: #ffffff !important;
    border-color: #047857 !important;
    box-shadow: 0 3px 8px rgba(5, 150, 105, 0.3) !important;
}
</style>

<!-- 🌟 Modal ตั้งค่าการแยกกลุ่มย่อยประกันสังคม (SSS Subgroups Settings) -->
<div class="modal fade" id="modalSssSubgroupSetting" tabindex="-1" aria-labelledby="modalSssSubgroupSettingTitle" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
      
      <!-- Modal Header -->
      <div class="modal-header px-4 py-3" style="background: linear-gradient(135deg, #0f2b5c 0%, #1d4ed8 100%); color: #ffffff;">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 44px; height: 44px; background: rgba(255, 255, 255, 0.22); border: 1px solid rgba(255, 255, 255, 0.35);">
            <i class='bx bx-shield-quarter fs-4'></i>
          </div>
          <div>
            <h5 class="modal-title text-white fw-bold mb-0" id="modalSssSubgroupSettingTitle">
              ⚙️ ตั้งค่าการแยกหมวดย่อยลูกหนี้ประกันสังคม (Social Security Subgroups Configuration)
            </h5>
            <small class="text-white-50">กำหนดเงื่อนไขหมวด Instrument (อุปกรณ์และอวัยวะเทียม), ผังเป้าหมาย OPD (.309) / IPD (.310) และเดือนที่เริ่มบังคับใช้</small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-4" style="background-color: #f8fafc; font-size: 14px;">
        
        <!-- Loading Spinner -->
        <div id="sssSettingsLoading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">กำลังโหลด...</span>
          </div>
          <div class="mt-3 text-muted fw-semibold">กำลังดึงข้อมูลการตั้งค่าประกันสังคม...</div>
        </div>

        <!-- Settings Content Form -->
        <div id="sssSettingsFormWrapper" style="display: none;">
          
          <!-- Card 1: เดือนที่เริ่มใช้งานระบบ & โหมดการตัดยอดเงิน -->
          <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
            <div class="card-body p-3 p-md-4">
              <h6 class="fw-bold text-primary mb-3 d-flex align-items-center gap-2">
                <i class='bx bx-calendar-event fs-5'></i> 1. กำหนดเดือนที่เปิดใช้งานระบบประกันสังคม และนโยบายตัดยอดเงิน
              </h6>

              <div class="row g-3">
                <!-- ส่วนกำหนดเดือนที่เปิดใช้งาน -->
                <div class="col-md-7 border-end-md pe-md-3">
                  <label class="form-label fw-semibold text-dark mb-2">
                    📅 นโยบายการเปิดใช้งานระบบตัดแยกประกันสังคมประจำเดือน (Month Activation Policy)
                  </label>
                  
                  <div class="d-flex flex-wrap gap-2 mb-3">
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="sssMonthModeRadio" id="sssMonthModeAll" value="ALL" checked onchange="handleSssMonthModeChange()">
                      <label class="form-check-label fw-semibold text-dark" for="sssMonthModeAll">🌐 ทุกเดือน</label>
                    </div>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="sssMonthModeRadio" id="sssMonthModeSpecific" value="SPECIFIC" onchange="handleSssMonthModeChange()">
                      <label class="form-check-label fw-semibold text-dark" for="sssMonthModeSpecific">📅 เลือกเฉพาะเดือนที่ต้องการ</label>
                    </div>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="sssMonthModeRadio" id="sssMonthModeStart" value="START_FROM" onchange="handleSssMonthModeChange()">
                      <label class="form-check-label fw-semibold text-dark" for="sssMonthModeStart">⏳ กำหนดเดือนเริ่มต้น</label>
                    </div>
                  </div>

                  <!-- กล่องเลือกเฉพาะเดือน (Specific Months Checklist) -->
                  <div id="sssSpecificMonthsWrapper" class="p-3 border rounded bg-white" style="display: none;">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                      <span class="small fw-bold text-muted"><i class='bx bx-check-square me-1'></i> ติ๊กเลือกเดือนที่ต้องการเปิดระบบประกันสังคม:</span>
                      <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2" onclick="selectAllSssMonthsChecklist(true)">เลือกทั้งหมด</button>
                        <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-2" onclick="selectAllSssMonthsChecklist(false)">ล้างทั้งหมด</button>
                      </div>
                    </div>
                    <div id="sssMonthsChecklistContainer" class="d-flex flex-wrap gap-2" style="max-height: 140px; overflow-y: auto;">
                      <!-- โหลดรายการเดือน checkbox ผ่าน JS -->
                    </div>
                    <div class="form-text text-muted mt-2">
                      <i class='bx bx-info-circle text-primary'></i> เดือนที่ถูกติ๊กเลือกจะเปิดระบบตัดแยก Instrument ส่วนเดือนที่ไม่ได้เลือกจะคงข้อมูลบัญชีดั้งเดิมไว้
                    </div>
                  </div>

                  <!-- กล่องเลือกเดือนเริ่มต้น (Start From Month) -->
                  <div id="sssStartFromMonthWrapper" style="display: none;">
                    <select id="sssEffectiveMonthSelect" class="form-select border-primary" style="font-weight: 600;">
                      <option value="ALL">🌐 เปิดใช้งานทุกเดือน (ไม่มีกำหนดเริ่มต้น)</option>
                    </select>
                    <div class="form-text text-muted">
                      <i class='bx bx-info-circle text-primary'></i> ข้อมูลเดือนที่ต่ำกว่าเดือนที่เลือกจะคงข้อมูลเดิม ส่วนเดือนตั้งแต่ที่เลือกเป็นต้นไปจะเปิดระบบตัดแยก Instrument
                    </div>
                  </div>

                  <!-- คำอธิบายโหมดทุกเดือน -->
                  <div id="sssAllMonthsNotice" class="alert alert-light border py-2 px-3 mb-0 small text-muted">
                    <i class='bx bx-check-circle text-success me-1'></i> ระบบจะทำการจำแนกและตัดแยกหนี้ Instrument ให้กับ<strong>ทุกงวดเดือนบัญชี</strong>ในระบบ
                  </div>
                </div>

                <!-- สวิตช์ Auto-Debit Splitting แยก OPD / IPD -->
                <div class="col-md-5 ps-md-3">
                  <label class="form-label fw-semibold text-dark mb-2">
                    💸 นโยบายการตัดแยกยอดเงินจริง (Expense Splitting Policy)
                  </label>
                  <div class="d-flex flex-column gap-2">
                    
                    <!-- สวิตช์ตัดยอด SSS OPD -->
                    <div class="p-2 px-3 border rounded bg-white shadow-xs">
                      <div class="d-flex align-items-center justify-content-between">
                        <div>
                          <span class="fw-bold text-primary fs-6"><i class='bx bx-walk me-1'></i> ตัดแยกยอดจริง SSS OPD</span>
                          <small class="text-muted d-block" style="font-size: 11.5px;">
                            หักค่า Instrument จากผังแม่ OPD (.301, .303, .307, .308) โอนเข้าผัง .309
                          </small>
                        </div>
                        <div class="form-check form-switch ms-2">
                          <input class="form-check-input" type="checkbox" id="sssAutoSplitOpdSwitch" checked style="transform: scale(1.3); cursor: pointer;" onchange="handleSssAutoSplitOpdToggle(true)">
                        </div>
                      </div>
                      <div class="mt-2 pt-1 border-top">
                        <span class="badge bg-label-primary w-100 text-center py-1" id="badgeAutoSplitSssOpdStatus" style="font-size: 11.5px;">
                          <i class='bx bx-check-double me-1'></i> สถานะ: เปิดตัดแยก SSS OPD
                        </span>
                      </div>
                    </div>

                    <!-- สวิตช์ตัดยอด SSS IPD -->
                    <div class="p-2 px-3 border rounded bg-white shadow-xs">
                      <div class="d-flex align-items-center justify-content-between">
                        <div>
                          <span class="fw-bold text-success fs-6"><i class='bx bx-bed me-1'></i> ตัดแยกยอดจริง SSS IPD</span>
                          <small class="text-muted d-block" style="font-size: 11.5px;">
                            หักค่า Instrument จากผังแม่ IPD (.302, .304) โอนเข้าผัง .310
                          </small>
                        </div>
                        <div class="form-check form-switch ms-2">
                          <input class="form-check-input" type="checkbox" id="sssAutoSplitIpdSwitch" checked style="transform: scale(1.3); cursor: pointer;" onchange="handleSssAutoSplitIpdToggle(true)">
                        </div>
                      </div>
                      <div class="mt-2 pt-1 border-top">
                        <span class="badge bg-label-success w-100 text-center py-1" id="badgeAutoSplitSssIpdStatus" style="font-size: 11.5px;">
                          <i class='bx bx-check-double me-1'></i> สถานะ: เปิดตัดแยก SSS IPD
                        </span>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card 2: ผังบัญชีเป้าหมายที่เปิดใช้งาน (Parent Accounts) -->
          <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
            <div class="card-body p-3 p-md-4">
              <h6 class="fw-bold text-primary mb-3 d-flex align-items-center gap-2">
                <i class='bx bx-git-repo-forked fs-5'></i> 2. ผังบัญชีประกันสังคมที่เปิดใช้งานการตรวจจับ Instrument (Parent Accounts)
              </h6>

              <div class="row g-3">
                <!-- ฝั่ง OPD (5 ผัง) -->
                <div class="col-md-6">
                  <div class="p-3 border rounded h-100 bg-white">
                    <div class="fw-bold text-primary mb-2 border-bottom pb-2">
                      <i class='bx bx-user me-1'></i> ฝั่งผู้ป่วยนอก (OPD) - ผังประกันสังคม
                    </div>
                    <div class="d-flex flex-column gap-2" id="sssParentOpdContainer">
                      <div class="form-check form-switch">
                        <input class="form-check-input parent-sss-acc-switch" type="checkbox" data-acc="1102050101.301" id="acc_301" checked>
                        <label class="form-check-label fw-semibold" for="acc_301">
                          1102050101.301 <span class="text-muted fw-normal">(ลูกหนี้ค่ารักษาประกันสังคม OP -เครือข่าย)</span>
                        </label>
                      </div>
                      <div class="form-check form-switch">
                        <input class="form-check-input parent-sss-acc-switch" type="checkbox" data-acc="1102050101.303" id="acc_303" checked>
                        <label class="form-check-label fw-semibold" for="acc_303">
                          1102050101.303 <span class="text-muted fw-normal">(ลูกหนี้ค่ารักษาประกันสังคม OP - นอกเครือข่าย สังกัด สป.สธ.)</span>
                        </label>
                      </div>
                      <div class="form-check form-switch">
                        <input class="form-check-input parent-sss-acc-switch" type="checkbox" data-acc="1102050101.307" id="acc_307" checked>
                        <label class="form-check-label fw-semibold" for="acc_307">
                          1102050101.307 <span class="text-muted fw-normal">(ลูกหนี้ค่ารักษาประกันสังคม - กองทุนทดแทน)</span>
                        </label>
                      </div>
                      <div class="form-check form-switch">
                        <input class="form-check-input parent-sss-acc-switch" type="checkbox" data-acc="1102050101.308" id="acc_308" checked>
                        <label class="form-check-label fw-semibold" for="acc_308">
                          1102050101.308 <span class="text-muted fw-normal">(ลูกหนี้ค่ารักษาประกันสังคม 72 ชั่วโมงแรก)</span>
                        </label>
                      </div>
                      <div class="form-check form-switch border-top pt-2">
                        <input class="form-check-input parent-sss-acc-switch" type="checkbox" data-acc="1102050101.309" id="acc_309" checked>
                        <label class="form-check-label fw-semibold text-primary" for="acc_309">
                          1102050101.309 <span class="badge bg-label-primary ms-1">ผังเป้าหมายรับโอน OPD</span>
                          <div class="text-muted small fw-normal">(ลูกหนี้ค่ารักษาประกันสังคม - ค่าใช้จ่ายสูง/อุบัติเหตุ/ฉุกเฉิน OP)</div>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- ฝั่ง IPD (3 ผัง) -->
                <div class="col-md-6">
                  <div class="p-3 border rounded h-100 bg-white">
                    <div class="fw-bold text-success mb-2 border-bottom pb-2">
                      <i class='bx bx-bed me-1'></i> ฝั่งผู้ป่วยใน (IPD) - ผังประกันสังคม
                    </div>
                    <div class="d-flex flex-column gap-2" id="sssParentIpdContainer">
                      <div class="form-check form-switch">
                        <input class="form-check-input parent-sss-acc-switch" type="checkbox" data-acc="1102050101.302" id="acc_302" checked>
                        <label class="form-check-label fw-semibold" for="acc_302">
                          1102050101.302 <span class="text-muted fw-normal">(ลูกหนี้ค่ารักษาประกันสังคม IP - เครือข่าย)</span>
                        </label>
                      </div>
                      <div class="form-check form-switch">
                        <input class="form-check-input parent-sss-acc-switch" type="checkbox" data-acc="1102050101.304" id="acc_304" checked>
                        <label class="form-check-label fw-semibold" for="acc_304">
                          1102050101.304 <span class="text-muted fw-normal">(ลูกหนี้ค่ารักษาประกันสังคม IP - นอกเครือข่าย สังกัด สป.สธ.)</span>
                        </label>
                      </div>
                      <div class="form-check form-switch border-top pt-2">
                        <input class="form-check-input parent-sss-acc-switch" type="checkbox" data-acc="1102050101.310" id="acc_310" checked>
                        <label class="form-check-label fw-semibold text-success" for="acc_310">
                          1102050101.310 <span class="badge bg-label-success ms-1">ผังเป้าหมายรับโอน IPD</span>
                          <div class="text-muted small fw-normal">(ลูกหนี้ค่ารักษาประกันสังคม - ค่าใช้จ่ายสูง IP)</div>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card 3: กำหนดเงื่อนไขหมวดย่อยประกันสังคม (แยก OPD vs IPD) -->
          <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
            <div class="card-body p-3 p-md-4">
              
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h6 class="fw-bold text-primary mb-0 d-flex align-items-center gap-2">
                  <i class='bx bx-list-check fs-5'></i> 3. กำหนดเงื่อนไขการตรวจจับหมวดย่อยประกันสังคม (SSS Subgroups Criteria)
                </h6>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill shadow-xs" onclick="openAddNewSssSubgroupModal()">
                  <i class='bx bx-plus me-1'></i> เพิ่มกลุ่มย่อยใหม่
                </button>
              </div>

              <!-- เมนูสลับแท็บ 3.1 OPD และ 3.2 IPD -->
              <ul class="nav nav-tabs border-bottom mb-3" id="sssMainTypeTabs" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link active fw-bold" id="tab_sss_opd_btn" data-bs-toggle="tab" data-bs-target="#tab_sss_opd_content" type="button" role="tab">
                    <i class='bx bx-walk me-1'></i> 3.1 ผู้ป่วยนอก (OPD)
                    <span class="badge bg-white text-primary ms-2 rounded-pill px-2" id="badge_count_sss_opd">1</span>
                  </button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link fw-bold" id="tab_sss_ipd_btn" data-bs-toggle="tab" data-bs-target="#tab_sss_ipd_content" type="button" role="tab">
                    <i class='bx bx-bed me-1'></i> 3.2 ผู้ป่วยใน (IPD)
                    <span class="badge bg-white text-success ms-2 rounded-pill px-2" id="badge_count_sss_ipd">1</span>
                  </button>
                </li>
              </ul>

              <div class="tab-content" id="sssMainTypeTabContent">
                
                <!-- ========================================== -->
                <!-- 🌟 TAB 3.1: กลุ่มย่อยผู้ป่วยนอก (OPD) -->
                <!-- ========================================== -->
                <div class="tab-pane fade show active" id="tab_sss_opd_content" role="tabpanel">
                  
                  <!-- แถบแจ้งเตือนเมื่อปิด Auto-Split OPD -->
                  <div id="sssSubgroupsDisabledNoticeOpd" class="alert alert-warning border-0 shadow-xs mb-3" style="display: none; border-radius: 10px;">
                    <div class="d-flex align-items-center">
                      <i class='bx bx-error-circle fs-4 me-2 text-warning'></i>
                      <div>
                        <strong>ระบบตัดแยกยอดเงินจริง SSS OPD ถูกปิดใช้งานอยู่</strong>
                        <div class="small">ระบบจะไม่ตัดแยกยอดเงินไปยังผัง SSS OPD (.309) หากต้องการเปิดใช้งาน กรุณาเปิดสวิตช์ SSS OPD ในข้อ 1</div>
                      </div>
                    </div>
                  </div>

                  <div id="sssSubgroupsContentOpd" style="transition: all 0.3s ease;">
                    <!-- Nav Tabs สำหรับกลุ่มย่อย OPD -->
                    <ul class="nav nav-pills gap-2 mb-3 pb-2 border-bottom" id="sssSettingSubgroupTabsOpd" role="tablist">
                      <!-- แท็บ pills จะถูกสร้างผ่าน JS -->
                    </ul>

                    <!-- เนื้อหารายละเอียดกลุ่มย่อย OPD -->
                    <div class="tab-content border rounded p-3 p-md-4 bg-white shadow-xs" id="sssSettingSubgroupTabContentOpd">
                      <!-- เนื้อหาจะถูกสร้างผ่าน JS -->
                    </div>
                  </div>

                </div>

                <!-- ========================================== -->
                <!-- 🌟 TAB 3.2: กลุ่มย่อยผู้ป่วยใน (IPD) -->
                <!-- ========================================== -->
                <div class="tab-pane fade" id="tab_sss_ipd_content" role="tabpanel">
                  
                  <!-- แถบแจ้งเตือนเมื่อปิด Auto-Split IPD -->
                  <div id="sssSubgroupsDisabledNoticeIpd" class="alert alert-warning border-0 shadow-xs mb-3" style="display: none; border-radius: 10px;">
                    <div class="d-flex align-items-center">
                      <i class='bx bx-error-circle fs-4 me-2 text-warning'></i>
                      <div>
                        <strong>ระบบตัดแยกยอดเงินจริง SSS IPD ถูกปิดใช้งานอยู่</strong>
                        <div class="small">ระบบจะไม่ตัดแยกยอดเงินไปยังผัง SSS IPD (.310) หากต้องการเปิดใช้งาน กรุณาเปิดสวิตช์ SSS IPD ในข้อ 1</div>
                      </div>
                    </div>
                  </div>

                  <div id="sssSubgroupsContentIpd" style="transition: all 0.3s ease;">
                    <!-- Nav Tabs สำหรับกลุ่มย่อย IPD -->
                    <ul class="nav nav-pills gap-2 mb-3 pb-2 border-bottom" id="sssSettingSubgroupTabsIpd" role="tablist">
                      <!-- แท็บ pills จะถูกสร้างผ่าน JS -->
                    </ul>

                    <!-- เนื้อหารายละเอียดกลุ่มย่อย IPD -->
                    <div class="tab-content border rounded p-3 p-md-4 bg-white shadow-xs" id="sssSettingSubgroupTabContentIpd">
                      <!-- เนื้อหาจะถูกสร้างผ่าน JS -->
                    </div>
                  </div>

                </div>

              </div>

            </div>
          </div>

          <!-- Card 4: จำลองผลกระทบต่อยอดเงินและจำนวนเคส (Live Impact Simulation / Preview Tool) -->
          <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
            <div class="card-body p-3 p-md-4">
              <h6 class="fw-bold text-primary mb-3 d-flex align-items-center gap-2">
                <i class='bx bx-line-chart fs-5'></i> 4. จำลองผลกระทบต่อยอดเงินและจำนวนเคส (Live Impact Simulation / Preview Tool)
              </h6>
              
              <div class="row g-3 align-items-end mb-3">
                <div class="col-md-4">
                  <label class="form-label small fw-semibold text-muted mb-1">เลือกเดือนที่ต้องการทดสอบ:</label>
                  <select id="sssPreviewMonthSelect" class="form-select border-primary" style="font-weight: 600;">
                    <!-- โหลดรายการเดือนผ่าน JS -->
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-semibold text-muted mb-1">ประเภทผู้ป่วย:</label>
                  <select id="sssPreviewVisitTypeSelect" class="form-select border-primary" style="font-weight: 600;">
                    <option value="OPD" selected>ผู้ป่วยนอก (OPD - ตัดเข้า .309)</option>
                    <option value="IPD">ผู้ป่วยใน (IPD - ตัดเข้า .310)</option>
                  </select>
                </div>
                <div class="col-md-5">
                  <button type="button" class="btn btn-primary w-100" id="btnRunSssPreview" onclick="runSssImpactPreview()">
                    <i class='bx bx-play me-1'></i> ประมวลผลจำลองผลกระทบ
                  </button>
                </div>
              </div>

              <!-- ผลลัพธ์จำลอง -->
              <div id="sssPreviewResultWrapper" style="display: none;">
                <div class="alert alert-info border py-2.5 px-3 mb-3 d-flex align-items-center justify-content-between">
                  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                      <i class='bx bx-check-shield fs-5 me-1 text-primary'></i> 
                      ผลการจำลองสำหรับเดือน <strong id="sssPreviewResultMonth" class="text-primary font-monospace">-</strong> 
                      (<span id="sssPreviewResultVisitType" class="fw-bold">-</span>)
                    </div>
                    <div class="small">
                      ตัดโอนเข้าผังเป้าหมาย: <strong id="sssPreviewTotalCases" class="text-primary font-monospace">0</strong> | 
                      ยอดจัดเข้า: <strong id="sssPreviewTotalAmount" class="text-success font-monospace">฿0.00</strong>
                    </div>
                  </div>
                </div>

                <!-- ตารางเปรียบเทียบก่อนและหลังตัดยอด -->
                <div class="table-responsive">
                  <table class="table table-bordered table-sm align-middle text-center bg-white" style="font-size: 13px;">
                    <thead class="table-light">
                      <tr>
                        <th class="text-start">รหัสและชื่อผังบัญชี</th>
                        <th>จำนวนเคสเดิม</th>
                        <th>ยอดหนี้เดิม</th>
                        <th>ยอด Instrument ตัดโอน (-) / รับเข้า (+)</th>
                        <th class="text-primary">ยอดหนี้คงเหลือหลังตัด / สุทธิ</th>
                      </tr>
                    </thead>
                    <tbody id="sssPreviewTransfersTbody">
                      <!-- แถวสร้างผ่าน JS -->
                    </tbody>
                  </table>
                </div>
              </div>

            </div>
          </div>

        </div> <!-- /sssSettingsFormWrapper -->

      </div> <!-- /modal-body -->

      <!-- Modal Footer -->
      <div class="modal-footer px-4 py-3 bg-white border-top d-flex justify-content-between align-items-center">
        <button type="button" class="btn btn-outline-danger" onclick="confirmRestoreSssDefaults()">
          <i class='bx bx-reset me-1'></i> คืนค่าเริ่มต้น (Restore Defaults)
        </button>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">
            ยกเลิก
          </button>
          <button type="button" class="btn btn-success px-4" id="btnSaveSssSettings" onclick="saveSssSettingsData()">
            <i class='bx bx-save me-1'></i> บันทึกการตั้งค่า
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- 🌟 Modal ย่อย: เพิ่มกลุ่มย่อยประกันสังคมใหม่ -->
<div class="modal fade" id="modalAddNewSssSubgroup" tabindex="-1" aria-labelledby="modalAddNewSssSubgroupTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px;">
      <div class="modal-header bg-primary text-white py-2.5 px-3">
        <h6 class="modal-title text-white fw-bold mb-0" id="modalAddNewSssSubgroupTitle">
          <i class='bx bx-plus-circle me-1'></i> เพิ่มกลุ่มย่อยประกันสังคมใหม่
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-3">
        <div class="mb-2.5">
          <label class="form-label small fw-semibold text-muted mb-1">ประเภทผู้ป่วย:</label>
          <select id="newSssSubgroupVisitType" class="form-select form-select-sm border-primary fw-semibold">
            <option value="OPD" selected>ผู้ป่วยนอก (OPD - เข้าผัง .309)</option>
            <option value="IPD">ผู้ป่วยใน (IPD - เข้าผัง .310)</option>
          </select>
        </div>
        <div class="mb-2.5">
          <label class="form-label small fw-semibold text-muted mb-1">รหัสกลุ่มย่อย (ID เช่น SSS-Custom):</label>
          <input type="text" id="newSssSubgroupId" class="form-select form-select-sm font-monospace" placeholder="SSS-MyGroup">
        </div>
        <div class="mb-2.5">
          <label class="form-label small fw-semibold text-muted mb-1">ชื่อเต็มกลุ่มย่อย:</label>
          <input type="text" id="newSssSubgroupTitle" class="form-select form-select-sm" placeholder="SSS-Custom (รายการเฉพาะ)">
        </div>
        <div class="mb-2.5">
          <label class="form-label small fw-semibold text-muted mb-1">ชื่อย่อ (สำหรับแท็บและ Badge):</label>
          <input type="text" id="newSssSubgroupShortName" class="form-select form-select-sm" placeholder="SSS-Custom">
        </div>
        <div class="mb-2.5">
          <label class="form-label small fw-semibold text-muted mb-1">รหัสผังย่อยเป้าหมาย:</label>
          <input type="text" id="newSssSubgroupTargetAccount" class="form-select form-select-sm font-monospace" value="1102050101.309_CUSTOM">
        </div>
      </div>
      <div class="modal-footer py-2 px-3 bg-light">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-primary btn-sm px-3" onclick="submitCreateSssSubgroup()">
          <i class='bx bx-check me-1'></i> สร้างกลุ่มย่อย
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// =========================================================================
// 🌟 JavaScript Controller สำหรับจัดการ Modal ตั้งค่ากลุ่มย่อยประกันสังคม SSS
// =========================================================================
var currentSssConfig = null;
var allAvailableSssMonths = [];

function openSssSubgroupSettingModal() {
    var modalEl = document.getElementById('modalSssSubgroupSetting');
    if (!modalEl) {
        console.error('modalSssSubgroupSetting element not found in DOM');
        return;
    }
    if (modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
    loadSssSettingsData();
}

function handleSssMonthModeChange() {
    var mode = $('input[name="sssMonthModeRadio"]:checked').val() || 'ALL';
    if (mode === 'ALL') {
        $('#sssAllMonthsNotice').slideDown(150);
        $('#sssSpecificMonthsWrapper').slideUp(150);
        $('#sssStartFromMonthWrapper').slideUp(150);
    } else if (mode === 'SPECIFIC') {
        $('#sssAllMonthsNotice').slideUp(150);
        $('#sssSpecificMonthsWrapper').slideDown(150);
        $('#sssStartFromMonthWrapper').slideUp(150);
    } else if (mode === 'START_FROM') {
        $('#sssAllMonthsNotice').slideUp(150);
        $('#sssSpecificMonthsWrapper').slideUp(150);
        $('#sssStartFromMonthWrapper').slideDown(150);
    }
}

function selectAllSssMonthsChecklist(select) {
    $('.sss-month-check').prop('checked', select);
}

function handleSssAutoSplitOpdToggle(animate) {
    if (animate === undefined) animate = true;
    var isEnabled = $('#sssAutoSplitOpdSwitch').is(':checked');
    var badge = $('#badgeAutoSplitSssOpdStatus');
    var notice = $('#sssSubgroupsDisabledNoticeOpd');
    var container = $('#sssSubgroupsContentOpd');

    if (isEnabled) {
        badge.removeClass('bg-label-secondary').addClass('bg-label-primary').html("<i class='bx bx-check-double me-1'></i> สถานะ: เปิดตัดแยก SSS OPD");
        if (animate) {
            notice.slideUp(200);
        } else {
            notice.hide();
        }
        container.css({
            'opacity': '1',
            'pointer-events': 'auto',
            'filter': 'none'
        });
    } else {
        badge.removeClass('bg-label-primary').addClass('bg-label-secondary').html("<i class='bx bx-x me-1'></i> สถานะ: ปิดตัดแยก SSS OPD");
        if (animate) {
            notice.slideDown(200);
        } else {
            notice.show();
        }
        // 🔒 ปิดให้เทาๆ คลิกไม่ได้
        container.css({
            'opacity': '0.45',
            'pointer-events': 'none',
            'filter': 'grayscale(60%)'
        });
    }
}

function handleSssAutoSplitIpdToggle(animate) {
    if (animate === undefined) animate = true;
    var isEnabled = $('#sssAutoSplitIpdSwitch').is(':checked');
    var badge = $('#badgeAutoSplitSssIpdStatus');
    var notice = $('#sssSubgroupsDisabledNoticeIpd');
    var container = $('#sssSubgroupsContentIpd');

    if (isEnabled) {
        badge.removeClass('bg-label-secondary').addClass('bg-label-success').html("<i class='bx bx-check-double me-1'></i> สถานะ: เปิดตัดแยก SSS IPD");
        if (animate) {
            notice.slideUp(200);
        } else {
            notice.hide();
        }
        container.css({
            'opacity': '1',
            'pointer-events': 'auto',
            'filter': 'none'
        });
    } else {
        badge.removeClass('bg-label-success').addClass('bg-label-secondary').html("<i class='bx bx-x me-1'></i> สถานะ: ปิดตัดแยก SSS IPD");
        if (animate) {
            notice.slideDown(200);
        } else {
            notice.show();
        }
        // 🔒 ปิดให้เทาๆ คลิกไม่ได้
        container.css({
            'opacity': '0.45',
            'pointer-events': 'none',
            'filter': 'grayscale(60%)'
        });
    }
}

function loadSssSettingsData() {
    $('#sssSettingsLoading').show();
    $('#sssSettingsFormWrapper').hide();

    // ดึงงวดเดือน
    $.getJSON('./api_sss_debtor.php?action=get_months', function(resM) {
        if (resM.status === 'success' && Array.isArray(resM.months)) {
            allAvailableSssMonths = resM.months;
            populateSssMonthControls(resM.months);
        }

        // ดึง Config ปัจจุบัน
        $.getJSON('./api_sss_debtor.php?action=get_config', function(resCfg) {
            $('#sssSettingsLoading').hide();
            $('#sssSettingsFormWrapper').fadeIn(200);

            if (resCfg.status === 'success' && resCfg.config) {
                currentSssConfig = resCfg.config;
                renderSssSettingsForm(currentSssConfig);
            }
        }).fail(function() {
            $('#sssSettingsLoading').hide();
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถดึงข้อมูลการตั้งค่าประกันสังคมได้'
            });
        });
    }).fail(function() {
        $('#sssSettingsLoading').hide();
    });
}

function populateSssMonthControls(months) {
    var checkContainer = $('#sssMonthsChecklistContainer');
    checkContainer.empty();
    var selectEffective = $('#sssEffectiveMonthSelect');
    selectEffective.find('option:not(:first)').remove();
    var selectPreview = $('#sssPreviewMonthSelect');
    selectPreview.empty();

    if (!months || months.length === 0) {
        checkContainer.html('<div class="text-muted small p-2">ไม่พบข้อมูลเดือนในระบบ</div>');
        selectPreview.append('<option value="">-- ไม่พบเดือน --</option>');
        return;
    }

    months.forEach(function(m) {
        var chkHtml = `
            <div class="form-check form-check-inline m-0">
                <input class="form-check-input sss-month-check" type="checkbox" value="${m}" id="sss_m_${m}">
                <label class="form-check-label font-monospace" for="sss_m_${m}">${m}</label>
            </div>
        `;
        checkContainer.append(chkHtml);
        selectEffective.append(`<option value="${m}">${m}</option>`);
        selectPreview.append(`<option value="${m}">${m}</option>`);
    });
}

function renderSssSettingsForm(config) {
    // 1. นโยบายเดือน
    var mode = config.month_mode || 'ALL';
    $(`input[name="sssMonthModeRadio"][value="${mode}"]`).prop('checked', true);
    handleSssMonthModeChange();

    if (mode === 'SPECIFIC' && Array.isArray(config.active_months)) {
        $('.sss-month-check').prop('checked', false);
        config.active_months.forEach(function(m) {
            $(`#sss_m_${m}`).prop('checked', true);
        });
    }

    if (config.effective_start_month) {
        $('#sssEffectiveMonthSelect').val(config.effective_start_month);
    }

    // 2. สวิตช์ Auto-Debit
    var opdEnabled = config.auto_split_opd_enabled !== false;
    var ipdEnabled = config.auto_split_ipd_enabled !== false;
    $('#sssAutoSplitOpdSwitch').prop('checked', opdEnabled);
    $('#sssAutoSplitIpdSwitch').prop('checked', ipdEnabled);
    handleSssAutoSplitOpdToggle(false);
    handleSssAutoSplitIpdToggle(false);

    // 3. ผังแม่ที่เปิดใช้งาน
    if (config.parent_accounts) {
        $.each(config.parent_accounts, function(acc, pinfo) {
            var isChk = pinfo.enabled !== false;
            $(`.parent-sss-acc-switch[data-acc="${acc}"]`).prop('checked', isChk);
        });
    }

    // 4. กลุ่มย่อย OPD & IPD
    renderSssSubgroups('OPD', config.subgroups_opd || {});
    renderSssSubgroups('IPD', config.subgroups_ipd || {});
}

function escapeSssHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function renderSssSubgroups(visitType, subgroups) {
    var isIpd = (visitType === 'IPD');
    var suffix = isIpd ? 'Ipd' : 'Opd';
    var tabsContainer = $(`#sssSettingSubgroupTabs${suffix}`);
    var contentContainer = $(`#sssSettingSubgroupTabContent${suffix}`);
    var badgeCount = $(`#badge_count_sss_${suffix.toLowerCase()}`);

    tabsContainer.empty();
    contentContainer.empty();

    var keys = Object.keys(subgroups);
    badgeCount.text(keys.length);

    if (keys.length === 0) {
        tabsContainer.html('<li class="nav-item"><span class="text-muted small">ยังไม่มีกลุ่มย่อย</span></li>');
        contentContainer.html('<div class="text-center py-4 text-muted">ยังไม่มีการตั้งค่ากลุ่มย่อยสำหรับ ' + visitType + '</div>');
        return;
    }

    keys.forEach(function(sgId, idx) {
        var sg = subgroups[sgId];
        var isActive = (idx === 0);
        var activeCls = isActive ? 'active' : '';
        var tabId = `sss_tab_${suffix}_${sgId.replace(/[^a-zA-Z0-9]/g, '_')}`;
        var contentId = `sss_content_${suffix}_${sgId.replace(/[^a-zA-Z0-9]/g, '_')}`;

        // Tab Pill
        var tabHtml = `
            <li class="nav-item" role="presentation">
                <button class="nav-link ${activeCls}" id="${tabId}" data-bs-toggle="pill" data-bs-target="#${contentId}" type="button" role="tab">
                    <span class="fw-semibold">${sg.short_name || sgId}</span>
                </button>
            </li>
        `;
        tabsContainer.append(tabHtml);

        // Subgroup Content Form
        var isEnabled = sg.enabled !== false;
        var adp2Checked = (sg.auto_adp_type_2 !== false) ? 'checked' : '';
        var customAdps = Array.isArray(sg.custom_adp_codes) ? sg.custom_adp_codes.join(', ') : '';
        var customIcodes = Array.isArray(sg.custom_icodes) ? sg.custom_icodes.join(', ') : '';
        var startMonth = escapeSssHtml(sg.effective_start_month || 'ALL');
        var endMonth = escapeSssHtml(sg.effective_end_month || '');

        var formHtml = `
            <div class="tab-pane fade ${isActive ? 'show active' : ''}" id="${contentId}" role="tabpanel">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <div>
                        <h6 class="fw-bold text-primary mb-0">${sg.title || sgId}</h6>
                        <small class="text-muted">รหัสกลุ่มย่อย: <span class="font-monospace fw-bold text-dark">${sgId}</span></small>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input sss-sg-enable-switch" type="checkbox" id="enable_${suffix}_${sgId}" data-type="${visitType}" data-id="${sgId}" ${isEnabled ? 'checked' : ''} style="transform: scale(1.2);">
                        <label class="form-check-label fw-semibold" for="enable_${suffix}_${sgId}">เปิดใช้งานกลุ่มนี้</label>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted mb-1">ชื่อเต็มกลุ่มย่อย:</label>
                        <input type="text" class="form-control form-control-sm sss-sg-title" data-type="${visitType}" data-id="${sgId}" value="${sg.title || ''}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted mb-1">ชื่อย่อ (สำหรับแท็บและ Badge):</label>
                        <input type="text" class="form-control form-control-sm sss-sg-shortname" data-type="${visitType}" data-id="${sgId}" value="${sg.short_name || ''}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted mb-1">รหัสผังย่อยเป้าหมายสำหรับตั้งหนี้:</label>
                        <input type="text" class="form-control form-control-sm font-monospace sss-sg-targetacc" data-type="${visitType}" data-id="${sgId}" value="${sg.target_accountcode || (isIpd ? '1102050101.310_INST' : '1102050101.309_INST')}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted mb-1">ชื่อผังบัญชีย่อย:</label>
                        <input type="text" class="form-control form-control-sm sss-sg-accname" data-type="${visitType}" data-id="${sgId}" value="${sg.accountname || '- ลูกหนี้ค่าอุปกรณ์/อวัยวะเทียม ประกันสังคม'}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted mb-1">📅 เดือนเริ่มต้นบังคับใช้ (Start Month):</label>
                        <input type="text" class="form-control form-control-sm font-monospace sss-sg-startmonth" data-type="${visitType}" data-id="${sgId}" value="${startMonth}" placeholder="ALL หรือ 08-2569">
                        <small class="text-muted" style="font-size: 11px;">ใส่ ALL เพื่อบังคับใช้ทุกเดือน หรือระบุ MM-YYYY เช่น 08-2569</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted mb-1">⏳ เดือนสิ้นสุดบังคับใช้ (End Month):</label>
                        <input type="text" class="form-control form-control-sm font-monospace sss-sg-endmonth" data-type="${visitType}" data-id="${sgId}" value="${endMonth}" placeholder="เว้นว่างถ้าไม่มีกำหนดสิ้นสุด">
                        <small class="text-muted" style="font-size: 11px;">เว้นว่างถ้ามีผลตลอดไป หรือระบุ MM-YYYY</small>
                    </div>

                    <!-- การตั้งค่าเกณฑ์ตรวจจับ Instrument -->
                    <div class="col-12 mt-3">
                        <div class="p-3 border rounded bg-light">
                            <div class="fw-bold text-dark mb-2">
                                <i class='bx bx-slider me-1 text-primary'></i> เกณฑ์การตรวจจับอุปกรณ์และอวัยวะเทียม (Instrument Criteria)
                            </div>
                            
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input sss-sg-autoadp2" type="checkbox" id="autoadp2_${suffix}_${sgId}" data-type="${visitType}" data-id="${sgId}" ${adp2Checked}>
                                <label class="form-check-label fw-semibold" for="autoadp2_${suffix}_${sgId}">
                                    ตรวจจับอัตโนมัติจาก ADP TYPE 2 (อุปกรณ์และอวัยวะเทียมมาตรฐาน สปสช./ประกันสังคม)
                                </label>
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-semibold text-muted mb-1">
                                    รหัส ADP Code เพิ่มเติม (คั่นด้วยจุลภาค เช่น 7004, 7005, 8612, 8813, 8814):
                                </label>
                                <input type="text" class="form-control form-control-sm font-monospace sss-sg-adpcodes" data-type="${visitType}" data-id="${sgId}" value="${customAdps}">
                            </div>

                            <div class="mb-0">
                                <label class="form-label small fw-semibold text-muted mb-1">
                                    รหัส icode เฉพาะเจาะจง (คั่นด้วยจุลภาค เช่น 3900001, 3900002):
                                </label>
                                <input type="text" class="form-control form-control-sm font-monospace sss-sg-icodes" data-type="${visitType}" data-id="${sgId}" value="${customIcodes}" placeholder="ระบุ icode หรือปล่อยว่างหากใช้ตาม ADP Type 2">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        contentContainer.append(formHtml);
    });

    // เพิ่ม Event ดักจับเมื่อผู้ใช้เปิดสวิตช์กลุ่มย่อย ให้เปิดสวิตช์หลักอัตโนมัติ
    contentContainer.find('.sss-sg-enable-switch').off('change').on('change', function() {
        var vType = $(this).data('type');
        if ($(this).is(':checked')) {
            if (vType === 'OPD' && !$('#sssAutoSplitOpdSwitch').is(':checked')) {
                $('#sssAutoSplitOpdSwitch').prop('checked', true);
                handleSssAutoSplitOpdToggle(true);
            } else if (vType === 'IPD' && !$('#sssAutoSplitIpdSwitch').is(':checked')) {
                $('#sssAutoSplitIpdSwitch').prop('checked', true);
                handleSssAutoSplitIpdToggle(true);
            }
        }
    });
}

function saveSssSettingsData() {
    if (!currentSssConfig) currentSssConfig = {};

    // 1. นโยบายเดือน
    var mode = $('input[name="sssMonthModeRadio"]:checked').val() || 'ALL';
    currentSssConfig.month_mode = mode;

    if (mode === 'SPECIFIC') {
        var selectedMonths = [];
        $('.sss-month-check:checked').each(function() {
            selectedMonths.push($(this).val());
        });
        currentSssConfig.active_months = selectedMonths;
    } else {
        currentSssConfig.active_months = [];
    }

    if (mode === 'START_FROM') {
        currentSssConfig.effective_start_month = $('#sssEffectiveMonthSelect').val() || 'ALL';
    } else {
        currentSssConfig.effective_start_month = 'ALL';
    }

    // 2. สวิตช์ Auto-Debit
    currentSssConfig.auto_split_opd_enabled = $('#sssAutoSplitOpdSwitch').is(':checked');
    currentSssConfig.auto_split_ipd_enabled = $('#sssAutoSplitIpdSwitch').is(':checked');
    currentSssConfig.auto_split_enabled = (currentSssConfig.auto_split_opd_enabled || currentSssConfig.auto_split_ipd_enabled);

    // 3. ผังแม่
    if (!currentSssConfig.parent_accounts) currentSssConfig.parent_accounts = {};
    $('.parent-sss-acc-switch').each(function() {
        var acc = $(this).attr('data-acc');
        var isChk = $(this).is(':checked');
        if (currentSssConfig.parent_accounts[acc]) {
            currentSssConfig.parent_accounts[acc].enabled = isChk;
        } else {
            currentSssConfig.parent_accounts[acc] = { enabled: isChk };
        }
    });

    // 4. บันทึกข้อมูล Subgroups OPD & IPD
    ['OPD', 'IPD'].forEach(function(vType) {
        var groupKey = (vType === 'IPD') ? 'subgroups_ipd' : 'subgroups_opd';
        if (!currentSssConfig[groupKey]) currentSssConfig[groupKey] = {};

        var keys = Object.keys(currentSssConfig[groupKey]);
        keys.forEach(function(sgId) {
            var isEnabled = $(`.sss-sg-enable-switch[data-type="${vType}"][data-id="${sgId}"]`).is(':checked');
            var title = $(`.sss-sg-title[data-type="${vType}"][data-id="${sgId}"]`).val();
            var shortName = $(`.sss-sg-shortname[data-type="${vType}"][data-id="${sgId}"]`).val();
            var targetAcc = $(`.sss-sg-targetacc[data-type="${vType}"][data-id="${sgId}"]`).val();
            var accName = $(`.sss-sg-accname[data-type="${vType}"][data-id="${sgId}"]`).val();
            var startM = ($(`.sss-sg-startmonth[data-type="${vType}"][data-id="${sgId}"]`).val() || 'ALL').trim();
            var endM = ($(`.sss-sg-endmonth[data-type="${vType}"][data-id="${sgId}"]`).val() || '').trim();
            var autoAdp2 = $(`.sss-sg-autoadp2[data-type="${vType}"][data-id="${sgId}"]`).is(':checked');
            var adpStr = $(`.sss-sg-adpcodes[data-type="${vType}"][data-id="${sgId}"]`).val() || '';
            var icodeStr = $(`.sss-sg-icodes[data-type="${vType}"][data-id="${sgId}"]`).val() || '';

            var adpArr = adpStr.split(',').map(function(s) { return s.trim(); }).filter(function(s) { return s.length > 0; });
            var icodeArr = icodeStr.split(',').map(function(s) { return s.trim(); }).filter(function(s) { return s.length > 0; });

            currentSssConfig[groupKey][sgId] = {
                id: sgId,
                enabled: isEnabled,
                title: title || sgId,
                short_name: shortName || sgId,
                target_accountcode: targetAcc,
                accountname: accName,
                icon: '',
                adp_type: '2',
                auto_adp_type_2: autoAdp2,
                effective_start_month: startM,
                effective_end_month: endM,
                custom_adp_codes: adpArr,
                custom_icodes: icodeArr
            };
        });
    });

    var btn = $('#btnSaveSssSettings');
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> กำลังบันทึก...');

    $.ajax({
        url: './api_sss_debtor.php?action=save_config',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(currentSssConfig),
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).html("<i class='bx bx-save me-1'></i> บันทึกการตั้งค่า");
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกสำเร็จ',
                    text: 'บันทึกการตั้งค่าระบบตัดแยกประกันสังคมเรียบร้อยแล้ว',
                    timer: 1500,
                    showConfirmButton: false
                }).then(function() {
                    // ปิดหน้าต่าง Modal การตั้งค่า
                    var modalEl = document.getElementById('modalSssSubgroupSetting');
                    if (modalEl) {
                        var modalInstance = bootstrap.Modal.getInstance(modalEl);
                        if (modalInstance) {
                            modalInstance.hide();
                        } else {
                            $('#modalSssSubgroupSetting').modal('hide');
                        }
                    }
                    // รีโหลดข้อมูลหน้าปัจจุบัน (ถ้ามี)
                    if (typeof triggerLoad === 'function') {
                        triggerLoad();
                    } else if (typeof viewopdlist === 'function' && typeof $ !== 'undefined' && $('#selectTypeOpt').val()) {
                        viewopdlist($('#selectTypeOpt').val());
                    }
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: res.message || 'ไม่สามารถบันทึกการตั้งค่าได้'
                });
            }
        },
        error: function() {
            btn.prop('disabled', false).html("<i class='bx bx-save me-1'></i> บันทึกการตั้งค่า");
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้'
            });
        }
    });
}

function confirmRestoreSssDefaults() {
    Swal.fire({
        title: 'ยืนยันการคืนค่าเริ่มต้น?',
        text: 'การคืนค่าจะรีเซ็ตการตั้งค่าระบบประกันสังคมกลับเป็นค่าตั้งต้นทั้งหมด',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, คืนค่าเริ่มต้น',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post('./api_sss_debtor.php?action=restore_defaults', {}, function(res) {
                if (res.status === 'success') {
                    currentSssConfig = res.config;
                    renderSssSettingsForm(currentSssConfig);
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ',
                        text: 'คืนค่าการตั้งค่าเริ่มต้นเรียบร้อยแล้ว',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            }, 'json');
        }
    });
}

function runSssImpactPreview() {
    var month = $('#sssPreviewMonthSelect').val();
    var visitType = $('#sssPreviewVisitTypeSelect').val() || 'OPD';
    if (!month) {
        Swal.fire({ icon: 'warning', title: 'กรุณาเลือกเดือน', text: 'เลือกเดือนที่ต้องการทดสอบจำลองผลกระทบ' });
        return;
    }

    var btn = $('#btnRunSssPreview');
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> กำลังประมวลผล...');

    $.ajax({
        url: `./api_sss_debtor.php?action=preview_impact&monthtxt=${encodeURIComponent(month)}&visit_type=${encodeURIComponent(visitType)}`,
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ config: currentSssConfig }),
        dataType: 'json',
        success: function(res) {
            btn.prop('disabled', false).html("<i class='bx bx-play me-1'></i> ประมวลผลจำลองผลกระทบ");
            if (res.status === 'success') {
                $('#sssPreviewResultWrapper').slideDown(200);
                $('#sssPreviewResultMonth').text(res.monthtxt);
                $('#sssPreviewResultVisitType').text(res.visit_type);

                var inCases = parseInt(res.transfer_in_cases || 0);
                var inAmt = parseFloat(res.transfer_in_amount || 0);
                var origCases = parseInt(res.target_original_cases || 0);
                var origDebit = parseFloat(res.target_original_debit || 0);
                var finalCases = parseInt(res.final_target_cases || (origCases + inCases));
                var finalDebit = parseFloat(res.final_target_debit || (origDebit + inAmt));

                $('#sssPreviewTotalCases').text(inCases.toLocaleString('th-TH') + ' ราย (รวมผังเป้าหมาย ' + finalCases.toLocaleString('th-TH') + ' ราย)');
                $('#sssPreviewTotalAmount').text('+฿' + inAmt.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' (รวมสุทธิ ฿' + finalDebit.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ')');

                var tbody = $('#sssPreviewTransfersTbody');
                tbody.empty();

                if (res.transfers_by_account) {
                    $.each(res.transfers_by_account, function(acc, tr) {
                        var orig = parseFloat(tr.original_debit || 0);
                        var out = parseFloat(tr.transfer_out || 0);
                        var remain = parseFloat(tr.remain_debit || 0);
                        var cases = parseInt(tr.cases || 0);

                        tbody.append(`
                            <tr>
                                <td class="text-start font-monospace"><strong>${acc}</strong></td>
                                <td>${cases.toLocaleString('th-TH')}</td>
                                <td class="font-monospace">฿${orig.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td class="text-danger font-monospace">-${out.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                <td class="text-primary fw-bold font-monospace">฿${remain.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            </tr>
                        `);
                    });

                    // แถวของผังรับโอน
                    var targetAcc = res.target_account;
                    tbody.append(`
                        <tr class="table-primary fw-bold">
                            <td class="text-start font-monospace">${targetAcc} (ผังรับโอน Instrument)</td>
                            <td>${origCases.toLocaleString('th-TH')} ${inCases > 0 ? '<span class="badge bg-success ms-1">+' + inCases + '</span>' : ''}</td>
                            <td class="font-monospace">฿${origDebit.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            <td class="text-success font-monospace">+${inAmt.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            <td class="text-success font-monospace">฿${finalDebit.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        </tr>
                    `);
                }
            } else {
                Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: res.message || 'ไม่สามารถจำลองผลกระทบได้' });
            }
        },
        error: function() {
            btn.prop('disabled', false).html("<i class='bx bx-play me-1'></i> ประมวลผลจำลองผลกระทบ");
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์ได้' });
        }
    });
}

function openAddNewSssSubgroupModal() {
    var modalEl = document.getElementById('modalAddNewSssSubgroup');
    if (!modalEl) return;
    if (modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function submitCreateSssSubgroup() {
    var vType = $('#newSssSubgroupVisitType').val();
    var id = $('#newSssSubgroupId').val().trim();
    var title = $('#newSssSubgroupTitle').val().trim();
    var shortName = $('#newSssSubgroupShortName').val().trim();
    var targetAcc = $('#newSssSubgroupTargetAccount').val().trim();

    if (!id) {
        Swal.fire({ icon: 'warning', title: 'กรุณากรอกข้อมูล', text: 'กรุณากรอกรหัสกลุ่มย่อย' });
        return;
    }

    var groupKey = (vType === 'IPD') ? 'subgroups_ipd' : 'subgroups_opd';
    if (!currentSssConfig[groupKey]) currentSssConfig[groupKey] = {};

    currentSssConfig[groupKey][id] = {
        id: id,
        enabled: true,
        title: title || id,
        short_name: shortName || id,
        target_accountcode: targetAcc || (vType === 'IPD' ? '1102050101.310_INST' : '1102050101.309_INST'),
        accountname: '- ลูกหนี้ค่าอุปกรณ์/อวัยวะเทียม ประกันสังคม',
        icon: '',
        adp_type: '2',
        auto_adp_type_2: true,
        custom_adp_codes: ["7004", "7005", "8612", "8813", "8814"],
        custom_icodes: []
    };

    renderSssSubgroups('OPD', currentSssConfig.subgroups_opd || {});
    renderSssSubgroups('IPD', currentSssConfig.subgroups_ipd || {});

    var modalEl = document.getElementById('modalAddNewSssSubgroup');
    var modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();

    Swal.fire({
        icon: 'success',
        title: 'เพิ่มกลุ่มย่อยเรียบร้อย',
        text: 'อย่าลืมกด "บันทึกการตั้งค่า" เพื่อบันทึกการเปลี่ยนแปลงลงฐานข้อมูล',
        timer: 2000,
        showConfirmButton: false
    });
}
</script>
