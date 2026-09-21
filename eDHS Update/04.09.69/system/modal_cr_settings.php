<?php
/**
 * ====================================================================================
 * 🏥 eDebtor Hospital System (eDHS) - Modal CR Subgroups Configuration
 * ====================================================================================
 * หน้าต่าง Modal สำหรับผู้ดูแลระบบ (Admin) ตั้งค่าระบบจำแนกและตัดแยก 8 กลุ่มย่อย CR
 * รองรับการเลือกเดือนที่เริ่มใช้งาน, ผังบัญชีเป้าหมาย, เงื่อนไขรายกลุ่ม และทดสอบผลกระทบ
 * ====================================================================================
 */
?>

<style>
/* บังคับให้ Modal และ SweetAlert2 แสดงผลด้านหน้า backdrop เสมอ */
#modalCrSubgroupSetting {
    z-index: 10050 !important;
}
#modalCrSubgroupSetting .modal-dialog {
    max-width: 1240px !important;
    width: 95% !important;
    margin: 1.75rem auto !important;
}
#modalCrSubgroupSetting .modal-body {
    min-height: 520px !important;
    background-color: #f8fafc !important;
}
#modalAddNewCrSubgroup {
    z-index: 10060 !important;
}
.swal2-container {
    z-index: 9999999 !important;
}

/* 🎨 สไตล์แท็บหลัก 3.1 OPD และ 3.2 IPD */
#crMainTypeTabs .nav-link {
    border-radius: 10px 10px 0 0 !important;
    border: 1px solid #cbd5e1 !important;
    border-bottom: none !important;
    font-size: 15px !important;
    padding: 12px 20px !important;
    background-color: #f1f5f9;
    color: #64748b;
    transition: all 0.25s ease-in-out;
}
#crMainTypeTabs #tab_main_opd_btn.active {
    background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%) !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    border-color: #1d4ed8 !important;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35) !important;
}
#crMainTypeTabs #tab_main_ipd_btn.active {
    background: linear-gradient(135deg, #047857 0%, #10b981 100%) !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    border-color: #047857 !important;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.35) !important;
}

/* 🎨 สไตล์แท็บย่อย Subgroup Pills (OPD) */
#crSettingSubgroupTabsOpd .nav-link {
    border: 1px solid #cbd5e1;
    color: #475569;
    font-weight: 600;
    border-radius: 8px;
    padding: 6px 14px;
    background-color: #ffffff;
    transition: all 0.2s ease;
}
#crSettingSubgroupTabsOpd .nav-link:hover {
    background-color: #eff6ff;
    color: #1d4ed8;
    border-color: #93c5fd;
}
#crSettingSubgroupTabsOpd .nav-link.active {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
    color: #ffffff !important;
    border-color: #1d4ed8 !important;
    box-shadow: 0 3px 8px rgba(37, 99, 235, 0.3) !important;
}

/* 🎨 สไตล์แท็บย่อย Subgroup Pills (IPD) */
#crSettingSubgroupTabsIpd .nav-link {
    border: 1px solid #cbd5e1;
    color: #475569;
    font-weight: 600;
    border-radius: 8px;
    padding: 6px 14px;
    background-color: #ffffff;
    transition: all 0.2s ease;
}
#crSettingSubgroupTabsIpd .nav-link:hover {
    background-color: #ecfdf5;
    color: #047857;
    border-color: #a7f3d0;
}
#crSettingSubgroupTabsIpd .nav-link.active {
    background: linear-gradient(135deg, #059669 0%, #047857 100%) !important;
    color: #ffffff !important;
    border-color: #047857 !important;
    box-shadow: 0 3px 8px rgba(5, 150, 105, 0.3) !important;
}
</style>

<!-- 🌟 Modal ตั้งค่าการแยกกลุ่มย่อย CR (CR Subgroups Settings) -->
<div class="modal fade" id="modalCrSubgroupSetting" tabindex="-1" aria-labelledby="modalCrSubgroupSettingTitle" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
      
      <!-- Modal Header -->
      <div class="modal-header px-4 py-3" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); color: #ffffff;">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 44px; height: 44px; background: rgba(255, 255, 255, 0.22); border: 1px solid rgba(255, 255, 255, 0.35);">
            <i class='bx bx-slider-alt fs-4'></i>
          </div>
          <div>
            <h5 class="modal-title text-white fw-bold mb-0" id="modalCrSubgroupSettingTitle">
              ⚙️ ตั้งค่าการแยกกลุ่มย่อยลูกหนี้ CR (CR Subgroups Configuration)
            </h5>
            <small class="text-white-50">กำหนดเงื่อนไข 8 กลุ่มย่อย, ผังบัญชีเป้าหมาย และเดือนที่เริ่มมีผลบังคับใช้</small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-4" style="background-color: #f8fafc; font-size: 14px;">
        
        <!-- Loading Spinner -->
        <div id="crSettingsLoading" class="text-center py-5">
          <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">กำลังโหลด...</span>
          </div>
          <div class="mt-3 text-muted fw-semibold">กำลังดึงข้อมูลการตั้งค่า CR...</div>
        </div>

        <!-- Settings Content Form -->
        <div id="crSettingsFormWrapper" style="display: none;">
          
          <!-- Card 1: เดือนที่เริ่มใช้งานระบบ & โหมดการตัดยอดเงิน -->
          <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px;">
            <div class="card-body p-3 p-md-4">
              <h6 class="fw-bold text-primary mb-3 d-flex align-items-center gap-2">
                <i class='bx bx-calendar-event fs-5'></i> 1. กำหนดเดือนที่เปิดใช้งานระบบ CR และนโยบายตัดยอดเงิน
              </h6>

              <div class="row g-3">
                <!-- ส่วนกำหนดเดือนที่เปิดใช้งาน -->
                <div class="col-md-7 border-end-md pe-md-3">
                  <label class="form-label fw-semibold text-dark mb-2">
                    📅 นโยบายการเปิดใช้งานระบบจำแนก 8 กลุ่มย่อย CR ประจำเดือน (Month Activation Policy)
                  </label>
                  
                  <div class="d-flex flex-wrap gap-2 mb-3">
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="crMonthModeRadio" id="crMonthModeAll" value="ALL" checked onchange="handleMonthModeChange()">
                      <label class="form-check-label fw-semibold text-dark" for="crMonthModeAll">🌐 ทุกเดือน</label>
                    </div>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="crMonthModeRadio" id="crMonthModeSpecific" value="SPECIFIC" onchange="handleMonthModeChange()">
                      <label class="form-check-label fw-semibold text-dark" for="crMonthModeSpecific">📅 เลือกเฉพาะเดือนที่ต้องการ</label>
                    </div>
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="crMonthModeRadio" id="crMonthModeStart" value="START_FROM" onchange="handleMonthModeChange()">
                      <label class="form-check-label fw-semibold text-dark" for="crMonthModeStart">⏳ กำหนดเดือนเริ่มต้น</label>
                    </div>
                  </div>

                  <!-- กล่องเลือกเฉพาะเดือน (Specific Months Checklist) -->
                  <div id="crSpecificMonthsWrapper" class="p-3 border rounded bg-white" style="display: none;">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">
                      <span class="small fw-bold text-muted"><i class='bx bx-check-square me-1'></i> ติ๊กเลือกเดือนที่ต้องการเปิดระบบ CR:</span>
                      <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-primary btn-xs py-0 px-2" onclick="selectAllMonthsChecklist(true)">เลือกทั้งหมด</button>
                        <button type="button" class="btn btn-outline-secondary btn-xs py-0 px-2" onclick="selectAllMonthsChecklist(false)">ล้างทั้งหมด</button>
                      </div>
                    </div>
                    <div id="crMonthsChecklistContainer" class="d-flex flex-wrap gap-2" style="max-height: 140px; overflow-y: auto;">
                      <!-- โหลดรายการเดือน checkbox ผ่าน JS -->
                    </div>
                    <div class="form-text text-muted mt-2">
                      <i class='bx bx-info-circle text-primary'></i> เดือนที่ถูกติ๊กเลือกจะเปิดระบบตัดแยก CR ส่วนเดือนที่ไม่ได้เลือกจะคงข้อมูลบัญชีดั้งเดิมไว้
                    </div>
                  </div>

                  <!-- กล่องเลือกเดือนเริ่มต้น (Start From Month) -->
                  <div id="crStartFromMonthWrapper" style="display: none;">
                    <select id="crEffectiveMonthSelect" class="form-select border-primary" style="font-weight: 600;">
                      <option value="ALL">🌐 เปิดใช้งานทุกเดือน (ไม่มีกำหนดเริ่มต้น)</option>
                      <!-- โหลดรายการเดือนแบบไดนามิกผ่าน JS -->
                    </select>
                    <div class="form-text text-muted">
                      <i class='bx bx-info-circle text-primary'></i> ข้อมูลเดือนที่ต่ำกว่าเดือนที่เลือกจะคงข้อมูลบัญชีเดิม ส่วนเดือนตั้งแต่ที่เลือกเป็นต้นไปจะเปิดระบบตัดแยก 8 กลุ่มย่อย CR
                    </div>
                  </div>

                  <!-- คำอธิบายโหมดทุกเดือน -->
                  <div id="crAllMonthsNotice" class="alert alert-light border py-2 px-3 mb-0 small text-muted">
                    <i class='bx bx-check-circle text-success me-1'></i> ระบบจะทำการจำแนกและตัดแยกหนี้ CR ให้กับ<strong>ทุกงวดเดือนบัญชี</strong>ในระบบ
                  </div>
                </div>

                <!-- สวิตช์ Auto-Debit Splitting แยก OPD / IPD -->
                <div class="col-md-5 ps-md-3">
                  <label class="form-label fw-semibold text-dark mb-2">
                    💸 นโยบายการตัดแยกยอดเงินจริง (Expense Splitting Policy)
                  </label>
                  <div class="d-flex flex-column gap-2">
                    
                    <!-- 1.1 สวิตช์ตัดยอด CR OPD -->
                    <div class="p-2 px-3 border rounded bg-white shadow-xs">
                      <div class="d-flex align-items-center justify-content-between">
                        <div>
                          <span class="fw-bold text-primary fs-6"><i class='bx bx-walk me-1'></i> ตัดแยกยอดจริง CR OPD</span>
                          <small class="text-muted d-block" style="font-size: 11.5px;">
                            หักยอดเงินจากผังแม่ OPD (.201, .209, .203) โอนเข้าผัง CR OPD (.216)
                          </small>
                        </div>
                        <div class="form-check form-switch ms-2">
                          <input class="form-check-input" type="checkbox" id="crAutoSplitOpdSwitch" checked style="transform: scale(1.3); cursor: pointer;" onchange="handleAutoSplitOpdToggle(true)">
                        </div>
                      </div>
                      <div class="mt-2 pt-1 border-top">
                        <span class="badge bg-label-primary w-100 text-center py-1" id="badgeAutoSplitOpdStatus" style="font-size: 11.5px;">
                          <i class='bx bx-check-double me-1'></i> สถานะ: เปิดตัดแยก CR OPD
                        </span>
                      </div>
                    </div>

                    <!-- 1.2 สวิตช์ตัดยอด CR IPD -->
                    <div class="p-2 px-3 border rounded bg-white shadow-xs">
                      <div class="d-flex align-items-center justify-content-between">
                        <div>
                          <span class="fw-bold text-success fs-6"><i class='bx bx-bed me-1'></i> ตัดแยกยอดจริง CR IPD</span>
                          <small class="text-muted d-block" style="font-size: 11.5px;">
                            หักยอดเงินจากผังแม่ IPD (.202) โอนเข้าผัง CR IPD (.217)
                          </small>
                        </div>
                        <div class="form-check form-switch ms-2">
                          <input class="form-check-input" type="checkbox" id="crAutoSplitIpdSwitch" checked style="transform: scale(1.3); cursor: pointer;" onchange="handleAutoSplitIpdToggle(true)">
                        </div>
                      </div>
                      <div class="mt-2 pt-1 border-top">
                        <span class="badge bg-label-success w-100 text-center py-1" id="badgeAutoSplitIpdStatus" style="font-size: 11.5px;">
                          <i class='bx bx-check-double me-1'></i> สถานะ: เปิดตัดแยก CR IPD
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
                <i class='bx bx-git-repo-forked fs-5'></i> 2. ผังบัญชีเป้าหมายที่เปิดใช้งานการตรวจจับ CR (Parent Accounts)
              </h6>

              <div class="row g-3">
                <!-- ฝั่ง OPD (4 ผัง) -->
                <div class="col-md-6">
                  <div class="p-3 border rounded h-100 bg-white">
                    <div class="fw-bold text-primary mb-2 border-bottom pb-2">
                      <i class='bx bx-user me-1'></i> ฝั่งผู้ป่วยนอก (OPD) - 4 ผังหลัก
                    </div>
                    <div class="d-flex flex-column gap-2" id="crParentOpdContainer">
                      <div class="form-check form-switch">
                        <input class="form-check-input parent-acc-switch" type="checkbox" data-acc="1102050101.201" id="acc_201" checked>
                        <label class="form-check-label fw-semibold" for="acc_201">
                          1102050101.201 <span class="text-muted fw-normal">(ลูกหนี้ค่ารักษา UC- OP ใน CUP)</span>
                        </label>
                      </div>
                      <div class="form-check form-switch">
                        <input class="form-check-input parent-acc-switch" type="checkbox" data-acc="1102050101.209" id="acc_209" checked>
                        <label class="form-check-label fw-semibold" for="acc_209">
                          1102050101.209 <span class="text-muted fw-normal">(ลูกหนี้สร้างเสริมสุขภาพและป้องกันโรค P&P)</span>
                        </label>
                      </div>
                      <div class="form-check form-switch">
                        <input class="form-check-input parent-acc-switch" type="checkbox" data-acc="1102050101.203" id="acc_203" checked>
                        <label class="form-check-label fw-semibold" for="acc_203">
                          1102050101.203 <span class="text-muted fw-normal">(ลูกหนี้ค่ารักษา UC - OP นอก CUP)</span>
                        </label>
                      </div>
                      <div class="form-check form-switch">
                        <input class="form-check-input parent-acc-switch" type="checkbox" data-acc="1102050101.216" id="acc_216" checked>
                        <label class="form-check-label fw-semibold" for="acc_216">
                          1102050101.216 <span class="text-muted fw-normal">(ลูกหนี้ค่ารักษา UC - OP บริการเฉพาะ CR)</span>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- ฝั่ง IPD (2 ผัง) -->
                <div class="col-md-6">
                  <div class="p-3 border rounded h-100 bg-white">
                    <div class="fw-bold text-success mb-2 border-bottom pb-2">
                      <i class='bx bx-bed me-1'></i> ฝั่งผู้ป่วยใน (IPD) - 2 ผังหลัก
                    </div>
                    <div class="d-flex flex-column gap-2" id="crParentIpdContainer">
                      <div class="form-check form-switch">
                        <input class="form-check-input parent-acc-switch" type="checkbox" data-acc="1102050101.202" id="acc_202" checked>
                        <label class="form-check-label fw-semibold" for="acc_202">
                          1102050101.202 <span class="text-muted fw-normal">(ลูกหนี้ค่ารักษา UC - IP)</span>
                        </label>
                      </div>
                      <div class="form-check form-switch">
                        <input class="form-check-input parent-acc-switch" type="checkbox" data-acc="1102050101.217" id="acc_217" checked>
                        <label class="form-check-label fw-semibold" for="acc_217">
                          1102050101.217 <span class="text-muted fw-normal">(ลูกหนี้ค่ารักษา UC - IP บริการเฉพาะ CR)</span>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card 3: การตั้งค่าเงื่อนไข 8 กลุ่มย่อย (Subgroup Rules Tabs) -->
          <div class="card border-0 shadow-sm mb-3" id="crSubgroupSettingsCard" style="border-radius: 12px; transition: all 0.3s ease;">
            <div class="card-body p-3 p-md-4">
              
              <!-- ส่วนแท็บและเนื้อหากลุ่มย่อย -->
              <div id="crSubgroupsMainContainer">
                <div class="d-flex align-items-center justify-content-between mb-3">
                  <h6 class="fw-bold text-primary mb-0 d-flex align-items-center gap-2">
                    <i class='bx bx-category fs-5'></i> 3. ตั้งค่าเงื่อนไขการจำแนกกลุ่มย่อย CR (Subgroup Rules)
                  </h6>
                </div>

                <!-- แท็บหลักเลือกระหว่าง OPD และ IPD -->
                <ul class="nav nav-tabs nav-fill mb-3" id="crMainTypeTabs" role="tablist">
                  <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-bold text-primary py-2 d-flex align-items-center justify-content-center gap-2" id="tab_main_opd_btn" data-bs-toggle="tab" data-bs-target="#tab_main_opd" type="button" role="tab">
                      <i class='bx bx-walk fs-5'></i> 3.1 กลุ่มย่อยผู้ป่วยนอก (OPD Subgroups)
                    </button>
                  </li>
                  <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold text-success py-2 d-flex align-items-center justify-content-center gap-2" id="tab_main_ipd_btn" data-bs-toggle="tab" data-bs-target="#tab_main_ipd" type="button" role="tab">
                      <i class='bx bx-bed fs-5'></i> 3.2 กลุ่มย่อยผู้ป่วยใน (IPD Subgroups)
                    </button>
                  </li>
                </ul>

                <!-- เนื้อหาแท็บหลัก OPD และ IPD -->
                <div class="tab-content" id="crMainTypeTabContent">
                  
                  <!-- ========================================== -->
                  <!-- 3.1 TAB: ผู้ป่วยนอก (OPD Subgroups) -->
                  <!-- ========================================== -->
                  <div class="tab-pane fade show active" id="tab_main_opd" role="tabpanel">
                    
                    <!-- แถบแจ้งเตือนเมื่อปิด Auto-Split OPD -->
                    <div id="crSubgroupsDisabledNoticeOpd" class="alert alert-warning border-0 shadow-xs mb-3" style="display:none; border-radius: 10px;">
                      <div class="d-flex align-items-center">
                        <i class='bx bx-error-circle fs-4 me-2 text-warning'></i>
                        <div>
                          <strong>ระบบตัดแยกยอดเงินจริง CR OPD ถูกปิดใช้งานอยู่</strong>
                          <div class="small">ระบบจะไม่ตัดแยกยอดเงินไปยังผัง CR OPD (.216) หากต้องการเปิดใช้งาน กรุณาเปิดสวิตช์ CR OPD ในข้อ 1</div>
                        </div>
                      </div>
                    </div>

                    <div id="crSubgroupsContentOpd" style="transition: all 0.3s ease;">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-semibold text-muted">
                          <i class='bx bx-info-circle text-primary'></i> ผังเป้าหมายหลัก: <code class="text-primary font-monospace">1102050101.216</code> (ลูกหนี้ค่ารักษา UC - OP บริการเฉพาะ CR)
                        </span>
                        <button class="btn btn-sm btn-outline-primary fw-semibold" type="button" onclick="openAddNewCustomSubgroupModal('OPD')">
                          <i class='bx bx-plus-circle me-1'></i> ➕ เพิ่มกลุ่มใหม่ (OPD)
                        </button>
                      </div>

                    <!-- Nav Pills OPD -->
                    <ul class="nav nav-pills mb-3 gap-1" id="crSettingSubgroupTabsOpd" role="tablist">
                      <li class="nav-item"><button class="nav-link active py-1 px-3" data-bs-toggle="pill" data-bs-target="#tab_opd_inst" type="button">CR-Instrument</button></li>
                      <li class="nav-item"><button class="nav-link py-1 px-3" data-bs-toggle="pill" data-bs-target="#tab_opd_walkin" type="button">CR-walkin</button></li>
                      <li class="nav-item"><button class="nav-link py-1 px-3" data-bs-toggle="pill" data-bs-target="#tab_opd_ae" type="button">CR-AE</button></li>
                      <li class="nav-item"><button class="nav-link py-1 px-3" data-bs-toggle="pill" data-bs-target="#tab_opd_newborn" type="button">CR-เกิดสิทธิทันที</button></li>
                      <li class="nav-item"><button class="nav-link py-1 px-3" data-bs-toggle="pill" data-bs-target="#tab_opd_pall" type="button">CR-palliative</button></li>
                      <li class="nav-item"><button class="nav-link py-1 px-3" data-bs-toggle="pill" data-bs-target="#tab_opd_tele" type="button">CR-Tele</button></li>
                      <li class="nav-item"><button class="nav-link py-1 px-3" data-bs-toggle="pill" data-bs-target="#tab_opd_clopi" type="button">CR-ยาclopi</button></li>
                      <li class="nav-item"><button class="nav-link py-1 px-3" data-bs-toggle="pill" data-bs-target="#tab_opd_herb" type="button">CR-ยาสมุนไพร</button></li>
                    </ul>

                    <!-- Tab Content OPD -->
                    <div class="tab-content border rounded p-3 bg-white" id="crSettingSubgroupTabContentOpd">
                      
                      <!-- 1. OPD: CR-Instrument -->
                      <div class="tab-pane fade show active" id="tab_opd_inst">
                        <div class="row g-3">
                          <div class="col-md-12">
                            <div class="p-2 px-3 border rounded bg-light d-flex align-items-center justify-content-between">
                              <div>
                                <span class="fw-bold text-dark"><i class='bx bx-power-off me-1 text-primary'></i> สถานะการใช้งานกลุ่ม CR-Instrument (OPD)</span>
                                <div class="small text-muted">หากปิดสวิตช์ ระบบจะไม่ตรวจจับและไม่ตัดยกยอดค่าใช้จ่ายสำหรับอุปกรณ์/อวัยวะเทียม OPD</div>
                              </div>
                              <div class="form-check form-switch ms-3">
                                <input class="form-check-input sg-enable-switch" type="checkbox" id="cfg_opd_inst_enabled" checked style="transform: scale(1.3); cursor: pointer;">
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ชื่อกลุ่ม / คำอธิบาย</label>
                            <input type="text" class="form-control" id="cfg_opd_inst_title" value="CR-Instrument (อุปกรณ์และอวัยวะเทียม)">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ผังบัญชีปลายทาง</label>
                            <input type="text" class="form-control" id="cfg_opd_inst_acc" value="1102050101.216_CR_INST">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">📅 เดือนเริ่มต้นบังคับใช้ (Start Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_opd_inst_start_month" value="ALL" placeholder="ALL หรือ 08-2569">
                            <small class="text-muted" style="font-size: 11px;">ใส่ ALL เพื่อมีผลทุกเดือน หรือระบุ MM-YYYY เช่น 08-2569</small>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">⏳ เดือนสิ้นสุดบังคับใช้ (End Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_opd_inst_end_month" value="" placeholder="เว้นว่างถ้าไม่มีกำหนดสิ้นสุด">
                            <small class="text-muted" style="font-size: 11px;">เว้นว่างถ้ามีผลตลอดไป หรือระบุ MM-YYYY</small>
                          </div>
                          <div class="col-md-12">
                            <div class="form-check form-switch mb-2">
                              <input class="form-check-input" type="checkbox" id="cfg_opd_inst_auto_adp2" checked>
                              <label class="form-check-label fw-semibold" for="cfg_opd_inst_auto_adp2">ตรวจจับรหัสหมวดอุปกรณ์ nhso_adp_type = '2' อัตโนมัติ</label>
                            </div>
                          </div>
                          <div class="col-md-12">
                            <label class="form-label fw-semibold">รหัส ADP พิเศษ (คั่นด้วยจุลภาค ,)</label>
                            <input type="text" class="form-control" id="cfg_opd_inst_adp_codes" value="7004, 7005, 8612, 8813, 8814">
                          </div>
                        </div>
                      </div>

                      <!-- 2. OPD: CR-walkin -->
                      <div class="tab-pane fade" id="tab_opd_walkin">
                        <div class="row g-3">
                          <div class="col-md-12">
                            <div class="p-2 px-3 border rounded bg-light d-flex align-items-center justify-content-between">
                              <div>
                                <span class="fw-bold text-dark"><i class='bx bx-power-off me-1 text-primary'></i> สถานะการใช้งานกลุ่ม CR-walkin</span>
                                <div class="small text-muted">หากปิดสวิตช์ ระบบจะไม่ตรวจจับและไม่ตัดยกยอดค่าใช้จ่ายสำหรับบริการ Walk-in ข้าม CUP</div>
                              </div>
                              <div class="form-check form-switch ms-3">
                                <input class="form-check-input sg-enable-switch" type="checkbox" id="cfg_opd_walkin_enabled" checked style="transform: scale(1.3); cursor: pointer;">
                              </div>
                            </div>
                          </div>
                          
                          <!-- สรุปเกณฑ์การจำแนก 3 ข้อ -->
                          <div class="col-12">
                            <div class="p-3 border rounded-3 bg-white shadow-sm">
                              <div class="fw-bold text-dark mb-2" style="font-size: 13.5px;">
                                <i class='bx bx-git-branch text-primary me-1'></i> เกณฑ์การจำแนกผู้ป่วยนอกและการดึงยอดเข้ากลุ่ม CR-walkin:
                              </div>
                              <div class="row g-2">
                                <div class="col-md-4">
                                  <div class="p-2 border rounded-2 h-100" style="background: #f8fafc;">
                                    <div class="fw-bold text-secondary small mb-1">
                                      <span class="badge bg-secondary me-1">ข้อ 1</span> ใน CUP ตัวเอง
                                    </div>
                                    <div class="small text-muted" style="font-size: 11.5px; line-height: 1.5;">
                                      hospmain เป็นหน่วยบริการนี้หรือใน CUP &rarr; หักจากงบ OP เหมาจ่ายรายหัวตามปกติ (ผัง <strong>1102050101.201</strong>)
                                      <div class="mt-1"><span class="badge bg-label-secondary" style="font-size: 10px;">❌ ไม่เข้า CR-walkin</span></div>
                                    </div>
                                  </div>
                                </div>
                                <div class="col-md-4">
                                  <div class="p-2 border rounded-2 h-100" style="background: #f0fdf4; border-color: #bbf7d0 !important;">
                                    <div class="fw-bold text-success small mb-1">
                                      <span class="badge bg-success me-1">ข้อ 2</span> ต่าง CUP ในจังหวัดเดียวกัน
                                    </div>
                                    <div class="small text-dark" style="font-size: 11.5px; line-height: 1.5;">
                                      hospmain อยู่ต่าง CUP แต่อยู่ในจังหวัดเดียวกัน &rarr; บันทึกส่งเคลมเป็นกลุ่ม <strong>"นค." (ในจังหวัด)</strong> (ผัง <strong>1102050101.203</strong>)
                                      <div class="mt-1"><span class="badge bg-label-success" style="font-size: 10px;">🛡️ คงไว้ในผัง นค.</span></div>
                                    </div>
                                  </div>
                                </div>
                                <div class="col-md-4">
                                  <div class="p-2 border rounded-2 h-100" style="background: #eff6ff; border-color: #bfdbfe !important;">
                                    <div class="fw-bold text-primary small mb-1">
                                      <span class="badge bg-primary me-1">ข้อ 3</span> ต่างจังหวัด / ต่างเขต
                                    </div>
                                    <div class="small text-primary" style="font-size: 11.5px; line-height: 1.5; font-weight: 500;">
                                      hospmain อยู่ต่างจังหวัด/ต่างเขต (Cross Region) &rarr; <strong>บันทึกส่งเคลมเป็นกลุ่ม "CR-walkin" เพื่อดึงเงินจากส่วนกลาง</strong> (ผัง <strong>1102050101.216_CR_WALKIN</strong>)
                                      <div class="mt-1"><span class="badge bg-primary text-white" style="font-size: 10px;">⭐ ดึงยอดเข้า CR-walkin</span></div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>

                          <div class="col-md-6">
                            <label class="form-label fw-bold">ชื่อกลุ่ม / คำอธิบาย</label>
                            <input type="text" class="form-control" id="cfg_opd_walkin_title" value="CR-walkin (บริการข้าม CUP / 30 บาทรักษาทุกที่)">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ผังบัญชีปลายทาง</label>
                            <input type="text" class="form-control" id="cfg_opd_walkin_acc" value="1102050101.216_CR_WALKIN">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">📅 เดือนเริ่มต้นบังคับใช้ (Start Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_opd_walkin_start_month" value="ALL" placeholder="ALL หรือ 08-2569">
                            <small class="text-muted" style="font-size: 11px;">ใส่ ALL เพื่อมีผลทุกเดือน หรือระบุ MM-YYYY เช่น 08-2569</small>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">⏳ เดือนสิ้นสุดบังคับใช้ (End Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_opd_walkin_end_month" value="" placeholder="เว้นว่างถ้าไม่มีกำหนดสิ้นสุด">
                            <small class="text-muted" style="font-size: 11px;">เว้นว่างถ้ามีผลตลอดไป หรือระบุ MM-YYYY</small>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">รหัสสิทธิการรักษา pttype (คั่นด้วยจุลภาค ,)</label>
                            <input type="text" class="form-control" id="cfg_opd_walkin_pttypes" value="23" placeholder="เช่น 23">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">คำสำคัญชื่อสิทธิ pttypename (คั่นด้วยจุลภาค ,)</label>
                            <input type="text" class="form-control" id="cfg_opd_walkin_pttypenames" value="Walkin, walkin, WALKIN" placeholder="เช่น Walkin, walkin, WALKIN, 30 บาทรักษาทุกที่">
                          </div>
                          <div class="col-md-12">
                            <label class="form-label text-muted small fw-semibold">⚡ เลือกด่วนจากสิทธิการรักษาที่มีในระบบ (คลิกเพื่อเพิ่มรหัส):</label>
                            <select class="form-select form-select-sm" id="cfg_opd_walkin_pttype_select" onchange="addPttypeToInput('cfg_opd_walkin_pttypes', this.value); this.value='';">
                              <option value="">-- เลือกสิทธิการรักษาเพื่อเพิ่มเข้ารายการ --</option>
                            </select>
                          </div>

                          <!-- เงื่อนไขการตรวจจับ -->
                          <div class="col-md-12">
                            <div class="p-3 border rounded-3 bg-light">
                              <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="cfg_opd_walkin_check_hospmain" checked style="cursor: pointer;">
                                <label class="form-check-label fw-bold text-dark" for="cfg_opd_walkin_check_hospmain">
                                  ตรวจจับเฉพาะผู้ป่วยต่างจังหวัด / ต่างเขต (Cross Region) ตามเกณฑ์ข้อ 3 เท่านั้น (เพื่อดึงเงินจากส่วนกลาง)
                                </label>
                              </div>
                              <div class="small text-muted ps-4 mb-3" style="font-size: 12px;">
                                ระบบจะตรวจสอบรหัสจังหวัดของ hospmain เทียบกับรหัสจังหวัดของหน่วยบริการนี้ หากเป็นต่างจังหวัด/ต่างเขตจะถูกตัดยอดเข้ากลุ่ม <strong>CR-walkin</strong> เพื่อดึงเงินจากส่วนกลาง ส่วนต่าง CUP ในจังหวัดเดียวกันจะคงไว้ในผัง <strong>1102050101.203 (นค. ในจังหวัด)</strong> ตามเดิม ไม่ถูกตัดยอด
                              </div>
                              
                              <div class="row g-2 ps-4 align-items-center">
                                <div class="col-md-6">
                                  <label class="form-label small text-dark fw-semibold mb-1">รหัสจังหวัดของหน่วยบริการนี้ (ใช้เทียบ Cross Region)</label>
                                  <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class='bx bx-map-pin text-danger'></i></span>
                                    <input type="text" class="form-control" id="cfg_opd_walkin_my_chwpart" placeholder="เช่น 45 (ร้อยเอ็ด)" value="">
                                    <button class="btn btn-outline-primary" type="button" onclick="autoDetectHospitalProvince()" title="ตรวจจับรหัสจังหวัดอัตโนมัติจาก HOSxP">
                                      <i class='bx bx-refresh me-1'></i> ตรวจจับอัตโนมัติ
                                    </button>
                                  </div>
                                  <div class="mt-1" id="cfg_opd_walkin_prov_hint" style="font-size: 11px; color: #64748b;">
                                    ดึงค่าอัตโนมัติจากตาราง opdconfig และ hospcode ใน HOSxP
                                  </div>
                                </div>
                                <div class="col-md-6">
                                  <div class="form-check form-switch mt-3">
                                    <input class="form-check-input" type="checkbox" id="cfg_opd_walkin_check_adp" checked style="cursor: pointer;">
                                    <label class="form-check-label small fw-semibold text-dark" for="cfg_opd_walkin_check_adp">
                                      ตรวจจับรหัส ADP 'WALKIN' เพิ่มเติม (เฉพาะกรณีผู้ป่วยต่างจังหวัด/ต่างเขต)
                                    </label>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- 3. OPD: CR-AE -->
                      <div class="tab-pane fade" id="tab_opd_ae">
                        <div class="row g-3">
                          <div class="col-md-12">
                            <div class="p-2 px-3 border rounded bg-light d-flex align-items-center justify-content-between">
                              <div>
                                <span class="fw-bold text-dark"><i class='bx bx-power-off me-1 text-primary'></i> สถานะการใช้งานกลุ่ม CR-AE</span>
                                <div class="small text-muted">หากปิดสวิตช์ ระบบจะไม่ตรวจจับและไม่ตัดยกยอดค่าใช้จ่ายสำหรับอุบัติเหตุ/ฉุกเฉินเฉพาะจุด</div>
                              </div>
                              <div class="form-check form-switch ms-3">
                                <input class="form-check-input sg-enable-switch" type="checkbox" id="cfg_opd_ae_enabled" checked style="transform: scale(1.3); cursor: pointer;">
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ชื่อกลุ่ม / คำอธิบาย</label>
                            <input type="text" class="form-control" id="cfg_opd_ae_title" value="CR-AE (อุบัติเหตุ/ฉุกเฉินเฉพาะจุด)">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ผังบัญชีปลายทาง</label>
                            <input type="text" class="form-control" id="cfg_opd_ae_acc" value="1102050101.216_CR_AE">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">📅 เดือนเริ่มต้นบังคับใช้ (Start Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_opd_ae_start_month" value="ALL" placeholder="ALL หรือ 08-2569">
                            <small class="text-muted" style="font-size: 11px;">ใส่ ALL เพื่อมีผลทุกเดือน หรือระบุ MM-YYYY เช่น 08-2569</small>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">⏳ เดือนสิ้นสุดบังคับใช้ (End Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_opd_ae_end_month" value="" placeholder="เว้นว่างถ้าไม่มีกำหนดสิ้นสุด">
                            <small class="text-muted" style="font-size: 11px;">เว้นว่างถ้ามีผลตลอดไป หรือระบุ MM-YYYY</small>
                          </div>
                          <div class="col-md-12">
                            <div class="alert alert-primary py-2 px-3 mb-1 small d-flex align-items-center" style="border-radius: 8px;">
                              <i class='bx bx-transfer text-primary fs-5 me-2'></i>
                              <span><strong>นโยบายการโอนยอด:</strong> กลุ่ม CR-AE จะโอนค่าใช้จ่ายมายังผังลูก CR <strong>100%</strong> เต็มจำนวนตามเงื่อนไขสิทธิการรักษา</span>
                            </div>
                          </div>

                          <!-- เงื่อนไขคัดกรองเฉพาะเคสต่างจังหวัด (Cross Region) -->
                          <div class="col-md-12">
                            <div class="p-3 border rounded-3 bg-light">
                              <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="cfg_opd_ae_check_cross_region" checked style="cursor: pointer;">
                                <label class="form-check-label fw-bold text-dark" for="cfg_opd_ae_check_cross_region">
                                  คัดเอาเฉพาะเคสต่างจังหวัด / ต่างเขต (Cross Region) เน้นสำหรับเคสฉุกเฉินข้ามจังหวัด
                                </label>
                              </div>
                              <div class="small text-muted ps-4 mb-3" style="font-size: 12px;">
                                ระบบจะคัดกรองเฉพาะผู้ป่วยฉุกเฉิน/อุบัติเหตุที่มีสถานพยาบาลหลัก (hospmain) อยู่ต่างจังหวัด/ต่างเขต เพื่อดึงเงินชดเชยฉุกเฉินข้ามจังหวัดจากส่วนกลางเข้ากลุ่ม <strong>CR-AE</strong> ส่วนเคสฉุกเฉินใน CUP หรือต่าง CUP ในจังหวัดเดียวกันจะคงไว้ในผังลูกหนี้ปกติของจังหวัดตามเดิม (ไม่ถูกตัดแยก)
                              </div>
                              <div class="row g-2 ps-4 align-items-center">
                                <div class="col-md-6">
                                  <label class="form-label small text-dark fw-semibold mb-1">รหัสจังหวัดของหน่วยบริการนี้ (ใช้เทียบ Cross Region)</label>
                                  <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white"><i class='bx bx-map-pin text-danger'></i></span>
                                    <input type="text" class="form-control" id="cfg_opd_ae_my_chwpart" placeholder="เช่น 45 (ร้อยเอ็ด)" value="">
                                    <button class="btn btn-outline-primary" type="button" onclick="autoDetectHospitalProvince('ae')" title="ตรวจจับรหัสจังหวัดอัตโนมัติจาก HOSxP">
                                      <i class='bx bx-refresh me-1'></i> ตรวจจับอัตโนมัติ
                                    </button>
                                  </div>
                                  <div class="mt-1" id="cfg_opd_ae_prov_hint" style="font-size: 11px; color: #64748b;">
                                    ดึงค่าอัตโนมัติจากตาราง opdconfig และ hospcode ใน HOSxP
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">รหัสสิทธิการรักษา pttype (คั่นด้วยจุลภาค ,)</label>
                            <input type="text" class="form-control" id="cfg_opd_ae_pttypes" value="98, 97, 91, AM" placeholder="เช่น 98, 97, 91, AM">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">คำสำคัญชื่อสิทธิ pttypename (คั่นด้วยจุลภาค ,)</label>
                            <input type="text" class="form-control" id="cfg_opd_ae_pttypenames" value="ฉุกเฉิน, อุบัติเหตุ, AE" placeholder="เช่น ฉุกเฉิน, อุบัติเหตุ, AE">
                          </div>
                          <div class="col-md-12">
                            <label class="form-label text-muted small fw-semibold">⚡ เลือกด่วนจากสิทธิการรักษาที่มีในระบบ (คลิกเพื่อเพิ่มรหัส):</label>
                            <select class="form-select form-select-sm" id="cfg_opd_ae_pttype_select" onchange="addPttypeToInput('cfg_opd_ae_pttypes', this.value); this.value='';">
                              <option value="">-- เลือกสิทธิการรักษาเพื่อเพิ่มเข้ารายการ --</option>
                            </select>
                          </div>
                        </div>
                      </div>

                      <!-- 4. OPD: CR-เกิดสิทธิทันที -->
                      <div class="tab-pane fade" id="tab_opd_newborn">
                        <div class="row g-3">
                          <div class="col-md-12">
                            <div class="p-2 px-3 border rounded bg-light d-flex align-items-center justify-content-between">
                              <div>
                                <span class="fw-bold text-dark"><i class='bx bx-power-off me-1 text-primary'></i> สถานะการใช้งานกลุ่ม CR-เกิดสิทธิทันที</span>
                                <div class="small text-muted">หากปิดสวิตช์ ระบบจะไม่ตรวจจับและไม่ตัดยกยอดค่าใช้จ่ายสำหรับเด็กแรกเกิด/เปลี่ยนสิทธิ</div>
                              </div>
                              <div class="form-check form-switch ms-3">
                                <input class="form-check-input sg-enable-switch" type="checkbox" id="cfg_opd_newborn_enabled" checked style="transform: scale(1.3); cursor: pointer;">
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ชื่อกลุ่ม / คำอธิบาย</label>
                            <input type="text" class="form-control" id="cfg_opd_newborn_title" value="CR-เกิดสิทธิทันที (New Born / เปลี่ยนสิทธิแรกเกิด)">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ผังบัญชีปลายทาง</label>
                            <input type="text" class="form-control" id="cfg_opd_newborn_acc" value="1102050101.216_CR_NEWBORN">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">📅 เดือนเริ่มต้นบังคับใช้ (Start Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_opd_newborn_start_month" value="ALL" placeholder="ALL หรือ 08-2569">
                            <small class="text-muted" style="font-size: 11px;">ใส่ ALL เพื่อมีผลทุกเดือน หรือระบุ MM-YYYY เช่น 08-2569</small>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">⏳ เดือนสิ้นสุดบังคับใช้ (End Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_opd_newborn_end_month" value="" placeholder="เว้นว่างถ้าไม่มีกำหนดสิ้นสุด">
                            <small class="text-muted" style="font-size: 11px;">เว้นว่างถ้ามีผลตลอดไป หรือระบุ MM-YYYY</small>
                          </div>
                          <div class="col-md-12">
                            <div class="alert alert-primary py-2 px-3 mb-1 small d-flex align-items-center" style="border-radius: 8px;">
                              <i class='bx bx-transfer text-primary fs-5 me-2'></i>
                              <span><strong>นโยบายการโอนยอด:</strong> กลุ่ม CR-เกิดสิทธิทันที จะโอนค่าใช้จ่ายมายังผังลูก CR <strong>100%</strong> เต็มจำนวนตามเงื่อนไขสิทธิการรักษา</span>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">รหัสสิทธิการรักษา pttype (คั่นด้วยจุลภาค ,)</label>
                            <input type="text" class="form-control" id="cfg_opd_newborn_pttypes" value="XX" placeholder="เช่น XX">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">คำสำคัญชื่อสิทธิ pttypename (คั่นด้วยจุลภาค ,)</label>
                            <input type="text" class="form-control" id="cfg_opd_newborn_pttypenames" value="สิทธิว่าง, แรกเกิด, Newborn, NEWBORN" placeholder="เช่น สิทธิว่าง, แรกเกิด, Newborn">
                          </div>
                          <div class="col-md-12">
                            <label class="form-label text-muted small fw-semibold">⚡ เลือกด่วนจากสิทธิการรักษาที่มีในระบบ (คลิกเพื่อเพิ่มรหัส):</label>
                            <select class="form-select form-select-sm" id="cfg_opd_newborn_pttype_select" onchange="addPttypeToInput('cfg_opd_newborn_pttypes', this.value); this.value='';">
                              <option value="">-- เลือกสิทธิการรักษาเพื่อเพิ่มเข้ารายการ --</option>
                            </select>
                          </div>
                        </div>
                      </div>

                      <!-- 5. OPD: CR-palliative -->
                      <div class="tab-pane fade" id="tab_opd_pall">
                        <div class="row g-3">
                          <div class="col-md-12">
                            <div class="p-2 px-3 border rounded bg-light d-flex align-items-center justify-content-between">
                              <div>
                                <span class="fw-bold text-dark"><i class='bx bx-power-off me-1 text-primary'></i> สถานะการใช้งานกลุ่ม CR-palliative</span>
                                <div class="small text-muted">หากปิดสวิตช์ ระบบจะไม่ตรวจจับและไม่ตัดยกยอดค่าใช้จ่ายสำหรับผู้ป่วยระยะประคับประคอง</div>
                              </div>
                              <div class="form-check form-switch ms-3">
                                <input class="form-check-input sg-enable-switch" type="checkbox" id="cfg_opd_pall_enabled" checked style="transform: scale(1.3); cursor: pointer;">
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ชื่อกลุ่ม / คำอธิบาย</label>
                            <input type="text" class="form-control" id="cfg_opd_pall_title" value="CR-palliative (บริการดูแลผู้ป่วยระยะประคับประคอง)">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ผังบัญชีปลายทาง</label>
                            <input type="text" class="form-control" id="cfg_opd_pall_acc" value="1102050101.216_CR_PALLIATIVE">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">📅 เดือนเริ่มต้นบังคับใช้ (Start Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_opd_pall_start_month" value="ALL" placeholder="ALL หรือ 08-2569">
                            <small class="text-muted" style="font-size: 11px;">ใส่ ALL เพื่อมีผลทุกเดือน หรือระบุ MM-YYYY เช่น 08-2569</small>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">⏳ เดือนสิ้นสุดบังคับใช้ (End Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_opd_pall_end_month" value="" placeholder="เว้นว่างถ้าไม่มีกำหนดสิ้นสุด">
                            <small class="text-muted" style="font-size: 11px;">เว้นว่างถ้ามีผลตลอดไป หรือระบุ MM-YYYY</small>
                          </div>
                          <div class="col-md-12">
                            <label class="form-label fw-semibold">รหัสโรค ICD-10 (คั่นด้วยจุลภาค ,)</label>
                            <input type="text" class="form-control" id="cfg_opd_pall_icd10" value="Z515, Z51.5">
                          </div>
                        </div>
                      </div>

                      <!-- 6. OPD: CR-Tele -->
                      <div class="tab-pane fade" id="tab_opd_tele">
                        <div class="row g-3">
                          <div class="col-md-12">
                            <div class="p-2 px-3 border rounded bg-light d-flex align-items-center justify-content-between">
                              <div>
                                <span class="fw-bold text-dark"><i class='bx bx-power-off me-1 text-primary'></i> สถานะการใช้งานกลุ่ม CR-Tele (OPD)</span>
                                <div class="small text-muted">หากปิดสวิตช์ ระบบจะไม่ตรวจจับและไม่ตัดยกยอดค่าใช้จ่ายสำหรับบริการการแพทย์ทางไกล Telemedicine</div>
                              </div>
                              <div class="form-check form-switch ms-3">
                                <input class="form-check-input sg-enable-switch" type="checkbox" id="cfg_opd_tele_enabled" checked style="transform: scale(1.3); cursor: pointer;">
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ชื่อกลุ่ม / คำอธิบาย</label>
                            <input type="text" class="form-control" id="cfg_opd_tele_title" value="CR-Tele (บริการการแพทย์ทางไกล / Telemedicine)">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ผังบัญชีปลายทาง</label>
                            <input type="text" class="form-control" id="cfg_opd_tele_acc" value="1102050101.216_CR_TELE">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">📅 เดือนเริ่มต้นบังคับใช้ (Start Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_opd_tele_start_month" value="ALL" placeholder="ALL หรือ 08-2569">
                            <small class="text-muted" style="font-size: 11px;">ใส่ ALL เพื่อมีผลทุกเดือน หรือระบุ MM-YYYY เช่น 08-2569</small>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">⏳ เดือนสิ้นสุดบังคับใช้ (End Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_opd_tele_end_month" value="" placeholder="เว้นว่างถ้าไม่มีกำหนดสิ้นสุด">
                            <small class="text-muted" style="font-size: 11px;">เว้นว่างถ้ามีผลตลอดไป หรือระบุ MM-YYYY</small>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">รหัส ADP พิเศษ (คั่นด้วยจุลภาค ,)</label>
                            <input type="text" class="form-control" id="cfg_opd_tele_adp_codes" value="TELMED">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">รหัสประเภทการมา ovstist export_code (คั่นด้วยจุลภาค ,)</label>
                            <input type="text" class="form-control" id="cfg_opd_tele_ovstist_export_codes" value="5">
                          </div>
                          <div class="col-md-12">
                            <label class="form-label fw-semibold">รหัสรายการบริการ/icode ใน HOSxP (คั่นด้วยจุลภาค ,)</label>
                            <input type="text" class="form-control" id="cfg_opd_tele_icodes" value="" placeholder="ระบุ icode เช่น 3900xxx (หากมี)">
                          </div>
                        </div>
                      </div>

                      <!-- 7. OPD: CR-ยาclopi -->
                      <div class="tab-pane fade" id="tab_opd_clopi">
                        <div class="row g-3">
                          <div class="col-md-12">
                            <div class="p-2 px-3 border rounded bg-light d-flex align-items-center justify-content-between">
                              <div>
                                <span class="fw-bold text-dark"><i class='bx bx-power-off me-1 text-primary'></i> สถานะการใช้งานกลุ่ม CR-ยาclopi</span>
                                <div class="small text-muted">หากปิดสวิตช์ ระบบจะไม่ตรวจจับและไม่ตัดยกยอดค่าใช้จ่ายสำหรับยา Clopidogrel</div>
                              </div>
                              <div class="form-check form-switch ms-3">
                                <input class="form-check-input sg-enable-switch" type="checkbox" id="cfg_opd_clopi_enabled" checked style="transform: scale(1.3); cursor: pointer;">
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ชื่อกลุ่ม / คำอธิบาย</label>
                            <input type="text" class="form-control" id="cfg_opd_clopi_title" value="CR-ยาclopi (ยา Clopidogrel Fee Schedule)">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ผังบัญชีปลายทาง</label>
                            <input type="text" class="form-control" id="cfg_opd_clopi_acc" value="1102050101.216_CR_CLOPI">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">📅 เดือนเริ่มต้นบังคับใช้ (Start Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_opd_clopi_start_month" value="ALL" placeholder="ALL หรือ 08-2569">
                            <small class="text-muted" style="font-size: 11px;">ใส่ ALL เพื่อมีผลทุกเดือน หรือระบุ MM-YYYY เช่น 08-2569</small>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">⏳ เดือนสิ้นสุดบังคับใช้ (End Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_opd_clopi_end_month" value="" placeholder="เว้นว่างถ้าไม่มีกำหนดสิ้นสุด">
                            <small class="text-muted" style="font-size: 11px;">เว้นว่างถ้ามีผลตลอดไป หรือระบุ MM-YYYY</small>
                          </div>
                          <div class="col-md-12">
                            <label class="form-label fw-semibold">Regex ชื่อยา (Regular Expression)</label>
                            <input type="text" class="form-control" id="cfg_opd_clopi_regex" value="Clopidrogrel|Clopidogrel">
                          </div>
                        </div>
                      </div>

                      <!-- 8. OPD: CR-ยาสมุนไพร -->
                      <div class="tab-pane fade" id="tab_opd_herb">
                        <div class="row g-3">
                          <div class="col-md-12">
                            <div class="p-2 px-3 border rounded bg-light d-flex align-items-center justify-content-between">
                              <div>
                                <span class="fw-bold text-dark"><i class='bx bx-power-off me-1 text-primary'></i> สถานะการใช้งานกลุ่ม CR-ยาสมุนไพร</span>
                                <div class="small text-muted">หากปิดสวิตช์ ระบบจะไม่ตรวจจับและไม่ตัดยกยอดค่าใช้จ่ายสำหรับยาสมุนไพร/แพทย์แผนไทย</div>
                              </div>
                              <div class="form-check form-switch ms-3">
                                <input class="form-check-input sg-enable-switch" type="checkbox" id="cfg_opd_herb_enabled" checked style="transform: scale(1.3); cursor: pointer;">
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ชื่อกลุ่ม / คำอธิบาย</label>
                            <input type="text" class="form-control" id="cfg_opd_herb_title" value="CR-ยาสมุนไพร (ยาสมุนไพร/แพทย์แผนไทย)">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ผังบัญชีปลายทาง</label>
                            <input type="text" class="form-control" id="cfg_opd_herb_acc" value="1102050101.216_CR_HERB">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">📅 เดือนเริ่มต้นบังคับใช้ (Start Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_opd_herb_start_month" value="ALL" placeholder="ALL หรือ 08-2569">
                            <small class="text-muted" style="font-size: 11px;">ใส่ ALL เพื่อมีผลทุกเดือน หรือระบุ MM-YYYY เช่น 08-2569</small>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">⏳ เดือนสิ้นสุดบังคับใช้ (End Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_opd_herb_end_month" value="" placeholder="เว้นว่างถ้าไม่มีกำหนดสิ้นสุด">
                            <small class="text-muted" style="font-size: 11px;">เว้นว่างถ้ามีผลตลอดไป หรือระบุ MM-YYYY</small>
                          </div>
                          <div class="col-md-12">
                            <div class="form-check form-switch">
                              <input class="form-check-input" type="checkbox" id="cfg_opd_herb_adp4" checked>
                              <label class="form-check-label fw-semibold" for="cfg_opd_herb_adp4">ตรวจจับรหัสหมวดยาสมุนไพร nhso_adp_type = '4' และบริการแผนไทย ttmt</label>
                            </div>
                          </div>
                        </div>
                      </div>

                    </div>
                    </div><!-- /crSubgroupsContentOpd -->
                  </div>

                  <!-- ========================================== -->
                  <!-- 3.2 TAB: ผู้ป่วยใน (IPD Subgroups) -->
                  <!-- ========================================== -->
                  <div class="tab-pane fade" id="tab_main_ipd" role="tabpanel">
                    
                    <!-- แถบแจ้งเตือนเมื่อปิด Auto-Split IPD -->
                    <div id="crSubgroupsDisabledNoticeIpd" class="alert alert-warning border-0 shadow-xs mb-3" style="display:none; border-radius: 10px;">
                      <div class="d-flex align-items-center">
                        <i class='bx bx-error-circle fs-4 me-2 text-warning'></i>
                        <div>
                          <strong>ระบบตัดแยกยอดเงินจริง CR IPD ถูกปิดใช้งานอยู่</strong>
                          <div class="small">ระบบจะไม่ตัดแยกยอดเงินไปยังผัง CR IPD (.217) หากต้องการเปิดใช้งาน กรุณาเปิดสวิตช์ CR IPD ในข้อ 1</div>
                        </div>
                      </div>
                    </div>

                    <div id="crSubgroupsContentIpd" style="transition: all 0.3s ease;">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="small fw-semibold text-muted">
                          <i class='bx bx-info-circle text-success'></i> ผังเป้าหมายหลัก: <code class="text-success font-monospace">1102050101.217</code> (ลูกหนี้ค่ารักษา UC - IP บริการเฉพาะ CR)
                        </span>
                        <button class="btn btn-sm btn-outline-success fw-semibold" type="button" onclick="openAddNewCustomSubgroupModal('IPD')">
                          <i class='bx bx-plus-circle me-1'></i> ➕ เพิ่มกลุ่มใหม่ (IPD)
                        </button>
                      </div>

                    <!-- Nav Pills IPD -->
                    <ul class="nav nav-pills mb-3 gap-1" id="crSettingSubgroupTabsIpd" role="tablist">
                      <li class="nav-item"><button class="nav-link active py-1 px-3" data-bs-toggle="pill" data-bs-target="#tab_ipd_inst" type="button">CR-Instrument</button></li>
                      <li class="nav-item"><button class="nav-link py-1 px-3" data-bs-toggle="pill" data-bs-target="#tab_ipd_refer" type="button">CR-รถรีเฟอร์</button></li>
                      <li class="nav-item"><button class="nav-link py-1 px-3" data-bs-toggle="pill" data-bs-target="#tab_ipd_ae" type="button">CR-AE</button></li>
                      <li class="nav-item"><button class="nav-link py-1 px-3" data-bs-toggle="pill" data-bs-target="#tab_ipd_newborn" type="button">CR-เกิดสิทธิทันที</button></li>
                    </ul>

                    <!-- Tab Content IPD -->
                    <div class="tab-content border rounded p-3 bg-white" id="crSettingSubgroupTabContentIpd">
                      
                      <!-- 1. IPD: CR-Instrument -->
                      <div class="tab-pane fade show active" id="tab_ipd_inst">
                        <div class="row g-3">
                          <div class="col-md-12">
                            <div class="p-2 px-3 border rounded bg-light d-flex align-items-center justify-content-between">
                              <div>
                                <span class="fw-bold text-dark"><i class='bx bx-power-off me-1 text-success'></i> สถานะการใช้งานกลุ่ม CR-Instrument (IPD)</span>
                                <div class="small text-muted">หากปิดสวิตช์ ระบบจะไม่ตรวจจับและไม่ตัดยกยอดค่าใช้จ่ายสำหรับอุปกรณ์/อวัยวะเทียม IPD</div>
                              </div>
                              <div class="form-check form-switch ms-3">
                                <input class="form-check-input sg-enable-switch" type="checkbox" id="cfg_ipd_inst_enabled" checked style="transform: scale(1.3); cursor: pointer;">
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ชื่อกลุ่ม / คำอธิบาย</label>
                            <input type="text" class="form-control" id="cfg_ipd_inst_title" value="CR-Instrument (อุปกรณ์และอวัยวะเทียม IPD)">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ผังบัญชีปลายทาง</label>
                            <input type="text" class="form-control" id="cfg_ipd_inst_acc" value="1102050101.217_CR_INST">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">📅 เดือนเริ่มต้นบังคับใช้ (Start Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_ipd_inst_start_month" value="ALL" placeholder="ALL หรือ 08-2569">
                            <small class="text-muted" style="font-size: 11px;">ใส่ ALL เพื่อมีผลทุกเดือน หรือระบุ MM-YYYY เช่น 08-2569</small>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">⏳ เดือนสิ้นสุดบังคับใช้ (End Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_ipd_inst_end_month" value="" placeholder="เว้นว่างถ้าไม่มีกำหนดสิ้นสุด">
                            <small class="text-muted" style="font-size: 11px;">เว้นว่างถ้ามีผลตลอดไป หรือระบุ MM-YYYY</small>
                          </div>
                          <div class="col-md-12">
                            <div class="form-check form-switch mb-2">
                              <input class="form-check-input" type="checkbox" id="cfg_ipd_inst_auto_adp2" checked>
                              <label class="form-check-label fw-semibold" for="cfg_ipd_inst_auto_adp2">ตรวจจับรหัสหมวดอุปกรณ์ nhso_adp_type = '2' อัตโนมัติ</label>
                            </div>
                          </div>
                          <div class="col-md-12">
                            <label class="form-label fw-semibold">รหัส ADP พิเศษ (คั่นด้วยจุลภาค ,)</label>
                            <input type="text" class="form-control" id="cfg_ipd_inst_adp_codes" value="7004, 7005, 8612, 8813, 8814">
                          </div>
                        </div>
                      </div>

                      <!-- 2. IPD: CR-รถรีเฟอร์ -->
                      <div class="tab-pane fade" id="tab_ipd_refer">
                        <div class="row g-3">
                          <div class="col-md-12">
                            <div class="p-2 px-3 border rounded bg-light d-flex align-items-center justify-content-between">
                              <div>
                                <span class="fw-bold text-dark"><i class='bx bx-power-off me-1 text-success'></i> สถานะการใช้งานกลุ่ม CR-รถรีเฟอร์ (IPD)</span>
                                <div class="small text-muted">หากปิดสวิตช์ ระบบจะไม่ตรวจจับและไม่ตัดยกยอดค่าใช้จ่ายสำหรับค่ารถพยาบาลส่งต่อ Refer IPD</div>
                              </div>
                              <div class="form-check form-switch ms-3">
                                <input class="form-check-input sg-enable-switch" type="checkbox" id="cfg_ipd_refer_enabled" checked style="transform: scale(1.3); cursor: pointer;">
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ชื่อกลุ่ม / คำอธิบาย</label>
                            <input type="text" class="form-control" id="cfg_ipd_refer_title" value="CR-รถรีเฟอร์ (ค่ารถพยาบาลส่งต่อ Refer IPD)">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ผังบัญชีปลายทาง</label>
                            <input type="text" class="form-control" id="cfg_ipd_refer_acc" value="1102050101.217_CR_REFER">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">📅 เดือนเริ่มต้นบังคับใช้ (Start Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_ipd_refer_start_month" value="ALL" placeholder="ALL หรือ 08-2569">
                            <small class="text-muted" style="font-size: 11px;">ใส่ ALL เพื่อมีผลทุกเดือน หรือระบุ MM-YYYY เช่น 08-2569</small>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">⏳ เดือนสิ้นสุดบังคับใช้ (End Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_ipd_refer_end_month" value="" placeholder="เว้นว่างถ้าไม่มีกำหนดสิ้นสุด">
                            <small class="text-muted" style="font-size: 11px;">เว้นว่างถ้ามีผลตลอดไป หรือระบุ MM-YYYY</small>
                          </div>
                          <div class="col-md-12">
                            <label class="form-label fw-semibold">รหัส ADP พิเศษ (คั่นด้วยจุลภาค ,)</label>
                            <input type="text" class="form-control" id="cfg_ipd_refer_adp_codes" value="S1801, S1802, COVV01, 55999, REFER">
                          </div>
                          <div class="col-md-12">
                            <label class="form-label fw-semibold">รหัสรายการยา/เวชภัณฑ์ใน HOSxP icode (คั่นด้วยจุลภาค ,)</label>
                            <input type="text" class="form-control" id="cfg_ipd_refer_icodes" value="3900320, 3002101, 3900188, 3900189, 3900190, 3900191, 3900192, 3900279, 3900288, 3900309, 3900498, 3900612, 3900614">
                          </div>
                        </div>
                      </div>

                      <!-- 3. IPD: CR-AE -->
                      <div class="tab-pane fade" id="tab_ipd_ae">
                        <div class="row g-3">
                          <div class="col-md-12">
                            <div class="p-2 px-3 border rounded bg-light d-flex align-items-center justify-content-between">
                              <div>
                                <span class="fw-bold text-dark"><i class='bx bx-power-off me-1 text-success'></i> สถานะการใช้งานกลุ่ม CR-AE (IPD)</span>
                                <div class="small text-muted">หากปิดสวิตช์ ระบบจะไม่ตรวจจับและไม่ตัดยกยอดค่าใช้จ่ายสำหรับอุบัติเหตุ/ฉุกเฉินเฉพาะจุด IPD</div>
                              </div>
                              <div class="form-check form-switch ms-3">
                                <input class="form-check-input sg-enable-switch" type="checkbox" id="cfg_ipd_ae_enabled" checked style="transform: scale(1.3); cursor: pointer;">
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ชื่อกลุ่ม / คำอธิบาย</label>
                            <input type="text" class="form-control" id="cfg_ipd_ae_title" value="CR-AE (อุบัติเหตุ/ฉุกเฉินเฉพาะจุด IPD)">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ผังบัญชีปลายทาง</label>
                            <input type="text" class="form-control" id="cfg_ipd_ae_acc" value="1102050101.217_CR_AE">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">📅 เดือนเริ่มต้นบังคับใช้ (Start Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_ipd_ae_start_month" value="ALL" placeholder="ALL หรือ 08-2569">
                            <small class="text-muted" style="font-size: 11px;">ใส่ ALL เพื่อมีผลทุกเดือน หรือระบุ MM-YYYY เช่น 08-2569</small>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">⏳ เดือนสิ้นสุดบังคับใช้ (End Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_ipd_ae_end_month" value="" placeholder="เว้นว่างถ้าไม่มีกำหนดสิ้นสุด">
                            <small class="text-muted" style="font-size: 11px;">เว้นว่างถ้ามีผลตลอดไป หรือระบุ MM-YYYY</small>
                          </div>
                          <div class="col-md-12">
                            <div class="alert alert-success py-2 px-3 mb-1 small d-flex align-items-center" style="border-radius: 8px;">
                              <i class='bx bx-transfer text-success fs-5 me-2'></i>
                              <span><strong>นโยบายการโอนยอด:</strong> กลุ่ม CR-AE (IPD) จะโอนค่าใช้จ่ายมายังผังลูก CR <strong>100%</strong> เต็มจำนวนตามเงื่อนไขสิทธิการรักษา</span>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">รหัสสิทธิการรักษา pttype (คั่นด้วยจุลภาค ,)</label>
                            <input type="text" class="form-control" id="cfg_ipd_ae_pttypes" value="91, AM" placeholder="เช่น 91, AM">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">คำสำคัญชื่อสิทธิ pttypename (คั่นด้วยจุลภาค ,)</label>
                            <input type="text" class="form-control" id="cfg_ipd_ae_pttypenames" value="ฉุกเฉิน, อุบัติเหตุ, AE" placeholder="เช่น ฉุกเฉิน, อุบัติเหตุ, AE">
                          </div>
                          <div class="col-md-12">
                            <label class="form-label text-muted small fw-semibold">⚡ เลือกด่วนจากสิทธิการรักษา IPD (คลิกเพื่อเพิ่มรหัส):</label>
                            <select class="form-select form-select-sm" id="cfg_ipd_ae_pttype_select" onchange="addPttypeToInput('cfg_ipd_ae_pttypes', this.value); this.value='';">
                              <option value="">-- เลือกสิทธิการรักษา IPD เพื่อเพิ่มเข้ารายการ --</option>
                            </select>
                          </div>
                        </div>
                      </div>

                      <!-- 4. IPD: CR-เกิดสิทธิทันที -->
                      <div class="tab-pane fade" id="tab_ipd_newborn">
                        <div class="row g-3">
                          <div class="col-md-12">
                            <div class="p-2 px-3 border rounded bg-light d-flex align-items-center justify-content-between">
                              <div>
                                <span class="fw-bold text-dark"><i class='bx bx-power-off me-1 text-success'></i> สถานะการใช้งานกลุ่ม CR-เกิดสิทธิทันที (IPD)</span>
                                <div class="small text-muted">หากปิดสวิตช์ ระบบจะไม่ตรวจจับและไม่ตัดยกยอดค่าใช้จ่ายสำหรับเด็กแรกเกิด/เปลี่ยนสิทธิ IPD</div>
                              </div>
                              <div class="form-check form-switch ms-3">
                                <input class="form-check-input sg-enable-switch" type="checkbox" id="cfg_ipd_newborn_enabled" checked style="transform: scale(1.3); cursor: pointer;">
                              </div>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ชื่อกลุ่ม / คำอธิบาย</label>
                            <input type="text" class="form-control" id="cfg_ipd_newborn_title" value="CR-เกิดสิทธิทันที (New Born / เปลี่ยนสิทธิแรกเกิด IPD)">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-bold">ผังบัญชีปลายทาง</label>
                            <input type="text" class="form-control" id="cfg_ipd_newborn_acc" value="1102050101.217_CR_NEWBORN">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">📅 เดือนเริ่มต้นบังคับใช้ (Start Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_ipd_newborn_start_month" value="ALL" placeholder="ALL หรือ 08-2569">
                            <small class="text-muted" style="font-size: 11px;">ใส่ ALL เพื่อมีผลทุกเดือน หรือระบุ MM-YYYY เช่น 08-2569</small>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">⏳ เดือนสิ้นสุดบังคับใช้ (End Month)</label>
                            <input type="text" class="form-control font-monospace" id="cfg_ipd_newborn_end_month" value="" placeholder="เว้นว่างถ้าไม่มีกำหนดสิ้นสุด">
                            <small class="text-muted" style="font-size: 11px;">เว้นว่างถ้ามีผลตลอดไป หรือระบุ MM-YYYY</small>
                          </div>
                          <div class="col-md-12">
                            <div class="alert alert-success py-2 px-3 mb-1 small d-flex align-items-center" style="border-radius: 8px;">
                              <i class='bx bx-transfer text-success fs-5 me-2'></i>
                              <span><strong>นโยบายการโอนยอด:</strong> กลุ่ม CR-เกิดสิทธิทันที (IPD) จะโอนค่าใช้จ่ายมายังผังลูก CR <strong>100%</strong> เต็มจำนวนตามเงื่อนไขสิทธิการรักษา</span>
                            </div>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">รหัสสิทธิการรักษา pttype (คั่นด้วยจุลภาค ,)</label>
                            <input type="text" class="form-control" id="cfg_ipd_newborn_pttypes" value="XX" placeholder="เช่น XX">
                          </div>
                          <div class="col-md-6">
                            <label class="form-label fw-semibold">คำสำคัญชื่อสิทธิ pttypename (คั่นด้วยจุลภาค ,)</label>
                            <input type="text" class="form-control" id="cfg_ipd_newborn_pttypenames" value="สิทธิว่าง, แรกเกิด, Newborn, NEWBORN" placeholder="เช่น สิทธิว่าง, แรกเกิด, Newborn">
                          </div>
                          <div class="col-md-12">
                            <label class="form-label text-muted small fw-semibold">⚡ เลือกด่วนจากสิทธิการรักษา IPD (คลิกเพื่อเพิ่มรหัส):</label>
                            <select class="form-select form-select-sm" id="cfg_ipd_newborn_pttype_select" onchange="addPttypeToInput('cfg_ipd_newborn_pttypes', this.value); this.value='';">
                              <option value="">-- เลือกสิทธิการรักษา IPD เพื่อเพิ่มเข้ารายการ --</option>
                            </select>
                          </div>
                        </div>
                      </div>

                    </div>
                    </div><!-- /crSubgroupsContentIpd -->
                  </div>

                </div><!-- /crMainTypeTabContent -->
              </div><!-- /crSubgroupsMainContainer -->
            </div><!-- /card-body Card 3 -->
          </div><!-- /card Card 3 -->

        </div><!-- /modal-body -->

        <!-- Modal Footer -->
        <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between">
          <div>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="restoreCrDefaultSettings()">
              <i class='bx bx-reset me-1'></i> คืนค่าเริ่มต้น (Defaults)
            </button>
          </div>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
              <i class='bx bx-x me-1'></i> ปิดหน้าต่าง
            </button>
            <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" onclick="saveCrSettingsData()">
              <i class='bx bx-save me-1'></i> บันทึกการตั้งค่า
            </button>
          </div>
        </div>

      </div><!-- /modal-content -->
    </div><!-- /modal-dialog -->
  </div><!-- /modalCrSubgroupSetting -->

<!-- 🌟 Sub-Modal เพิ่มกลุ่มย่อย CR ใหม่ (Add New Subgroup Sub-Modal) -->
<div class="modal fade" id="modalAddNewCrSubgroup" tabindex="-1" aria-labelledby="modalAddNewCrSubgroupTitle" aria-hidden="true" style="z-index: 1065;">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
      <div class="modal-header px-4 py-3 bg-primary text-white" id="modalAddNewCrSubgroupHeader">
        <h6 class="modal-title fw-bold mb-0 text-white" id="modalAddNewCrSubgroupTitle">
          <i class='bx bx-plus-circle me-1'></i> เพิ่มกลุ่มย่อย CR ใหม่
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" style="background: #f8fafc; font-size: 13.5px;">
        <input type="hidden" id="new_sg_type" value="OPD">
        
        <div class="mb-3">
          <div class="p-2 border rounded bg-light d-flex align-items-center justify-content-between">
            <div>
              <label class="form-label fw-bold text-dark mb-0">สถานะเปิดใช้งานกลุ่มนี้ (Enabled)</label>
              <div class="small text-muted">เปิดใช้งานการตรวจจับและตัดยกยอดค่าใช้จ่าย</div>
            </div>
            <div class="form-check form-switch ms-3">
              <input class="form-check-input" type="checkbox" id="new_sg_enabled" checked style="transform: scale(1.3); cursor: pointer;">
            </div>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold text-dark">ประเภทผู้ป่วย (Target Category):</label>
          <div class="badge bg-primary px-3 py-2 fs-6 w-100 text-start d-flex align-items-center justify-content-between" id="new_sg_type_badge">
            <span><i class='bx bx-walk me-1'></i> ผู้ป่วยนอก (OPD)</span>
            <small class="opacity-75">ผังหลัก 1102050101.216</small>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold text-dark">รหัสกลุ่มย่อย (Subgroup Code): <span class="text-danger">*</span></label>
          <input type="text" id="new_sg_id" class="form-control" placeholder="เช่น CR-ทันตกรรม, CR-ไตเทียม" required>
          <small class="text-muted">ใช้เป็นชื่อแท็บและรหัสอ้างอิงในระบบ</small>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold text-dark">ชื่อกลุ่ม / คำอธิบาย (Title): <span class="text-danger">*</span></label>
          <input type="text" id="new_sg_title" class="form-control" placeholder="เช่น CR-ทันตกรรม (บริการทันตกรรมเฉพาะ Fee Schedule)" required>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold text-dark">รหัสผังบัญชีย่อยปลายทาง (Target Account Code):</label>
          <input type="text" id="new_sg_acc" class="form-control" placeholder="เช่น 1102050101.216_CR_DENT">
          <small class="text-muted">หากเว้นว่าง ระบบจะสร้างรหัสผังบัญชีย่อยให้อัตโนมัติ</small>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold text-dark">รหัส ADP พิเศษ (คั่นด้วยเครื่องหมายจุลภาค ,):</label>
          <input type="text" id="new_sg_adp" class="form-control" placeholder="เช่น 9001, 9002, 9003">
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold text-dark">รหัสรายการยา/เวชภัณฑ์ใน HOSxP icode (คั่นด้วยเครื่องหมายจุลภาค ,):</label>
          <input type="text" id="new_sg_icodes" class="form-control" placeholder="เช่น 3900123, 3900456">
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label fw-bold text-dark">📅 เดือนเริ่มต้นบังคับใช้:</label>
            <input type="text" id="new_sg_start_month" class="form-control font-monospace" placeholder="ALL หรือ 09-2569" value="ALL">
            <small class="text-muted">ใส่ ALL หรือ MM-YYYY เช่น 09-2569</small>
          </div>
          <div class="col-6">
            <label class="form-label fw-bold text-dark">⏳ เดือนสิ้นสุดบังคับใช้:</label>
            <input type="text" id="new_sg_end_month" class="form-control font-monospace" placeholder="เว้นว่างถ้าไม่จำกัด">
            <small class="text-muted">เว้นว่างถ้ามีผลตลอดไป หรือระบุ MM-YYYY</small>
          </div>
        </div>
      </div>
      <div class="modal-footer px-4 py-2 bg-light d-flex justify-content-between">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
          <i class='bx bx-x me-1'></i> ยกเลิก
        </button>
        <button type="button" class="btn btn-primary btn-sm px-3 fw-bold" onclick="submitAddNewCustomSubgroup()">
          <i class='bx bx-check me-1'></i> บันทึกเพิ่มกลุ่มย่อย
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// =========================================================================
// 🌟 JavaScript Controller สำหรับจัดการ Modal ตั้งค่ากลุ่มย่อย CR (OPD & IPD)
// =========================================================================
var currentCrConfig = null;
var allAvailableMonths = [];

function openCrSubgroupSettingModal() {
    var modalEl = document.getElementById('modalCrSubgroupSetting');
    if (!modalEl) {
        console.error('modalCrSubgroupSetting element not found in DOM');
        return;
    }
    if (modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
    loadCrSettingsData();
}

function handleMonthModeChange() {
    var mode = $('input[name="crMonthModeRadio"]:checked').val() || 'ALL';
    if (mode === 'ALL') {
        $('#crAllMonthsNotice').slideDown(150);
        $('#crSpecificMonthsWrapper').slideUp(150);
        $('#crStartFromMonthWrapper').slideUp(150);
    } else if (mode === 'SPECIFIC') {
        $('#crAllMonthsNotice').slideUp(150);
        $('#crSpecificMonthsWrapper').slideDown(150);
        $('#crStartFromMonthWrapper').slideUp(150);
    } else if (mode === 'START_FROM') {
        $('#crAllMonthsNotice').slideUp(150);
        $('#crSpecificMonthsWrapper').slideUp(150);
        $('#crStartFromMonthWrapper').slideDown(150);
    }
}

function selectAllMonthsChecklist(select) {
    $('.cr-month-check').prop('checked', select);
}

function handleAutoSplitOpdToggle(animate) {
    if (animate === undefined) animate = true;
    var isEnabled = $('#crAutoSplitOpdSwitch').is(':checked');
    var badge = $('#badgeAutoSplitOpdStatus');
    var notice = $('#crSubgroupsDisabledNoticeOpd');
    var container = $('#crSubgroupsContentOpd');

    if (isEnabled) {
        badge.removeClass('bg-label-secondary').addClass('bg-label-primary').html("<i class='bx bx-check-double me-1'></i> สถานะ: เปิดตัดแยก CR OPD");
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
        badge.removeClass('bg-label-primary').addClass('bg-label-secondary').html("<i class='bx bx-x-circle me-1'></i> สถานะ: ปิดตัดแยก CR OPD");
        if (animate) {
            notice.slideDown(200);
        } else {
            notice.show();
        }
        container.css({
            'opacity': '0.45',
            'pointer-events': 'none',
            'filter': 'grayscale(60%)'
        });
    }
}

function handleAutoSplitIpdToggle(animate) {
    if (animate === undefined) animate = true;
    var isEnabled = $('#crAutoSplitIpdSwitch').is(':checked');
    var badge = $('#badgeAutoSplitIpdStatus');
    var notice = $('#crSubgroupsDisabledNoticeIpd');
    var container = $('#crSubgroupsContentIpd');

    if (isEnabled) {
        badge.removeClass('bg-label-secondary').addClass('bg-label-success').html("<i class='bx bx-check-double me-1'></i> สถานะ: เปิดตัดแยก CR IPD");
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
        badge.removeClass('bg-label-success').addClass('bg-label-secondary').html("<i class='bx bx-x-circle me-1'></i> สถานะ: ปิดตัดแยก CR IPD");
        if (animate) {
            notice.slideDown(200);
        } else {
            notice.show();
        }
        container.css({
            'opacity': '0.45',
            'pointer-events': 'none',
            'filter': 'grayscale(60%)'
        });
    }
}

function loadCrSettingsData() {
    $('#crSettingsLoading').show();
    $('#crSettingsFormWrapper').hide();

    $.ajax({
        url: 'api_cr_debtor.php?action=get_months',
        method: 'GET',
        dataType: 'json',
        success: function(mRes) {
            if (mRes.status === 'success' && mRes.months) {
                allAvailableMonths = mRes.months;
                renderMonthControls(allAvailableMonths);
            }

            // โหลดสิทธิการรักษา pttype สำหรับ Dropdown ช่วยเลือก
            $.ajax({
                url: 'api_cr_debtor.php?action=get_pttypes',
                method: 'GET',
                dataType: 'json',
                success: function(ptRes) {
                    if (ptRes.status === 'success' && ptRes.pttypes) {
                        populatePttypeSelects(ptRes.pttypes);
                    }
                }
            });

            $.ajax({
                url: 'api_cr_debtor.php?action=get_config',
                method: 'GET',
                dataType: 'json',
                success: function(cfgRes) {
                    $('#crSettingsLoading').hide();
                    $('#crSettingsFormWrapper').css('display', 'block');
                    if (cfgRes.status === 'success' && cfgRes.config) {
                        currentCrConfig = cfgRes.config;
                        window.crHospitalInfo = cfgRes.hospital_info || null;
                        populateCrSettingsUI(cfgRes.config);
                    }
                },
                error: function() {
                    $('#crSettingsLoading').hide();
                    $('#crSettingsFormWrapper').css('display', 'block');
                    Swal.fire({
                        icon: 'error',
                        title: 'ข้อผิดพลาด',
                        text: 'ไม่สามารถโหลดข้อมูลการตั้งค่า CR ได้'
                    });
                }
            });
        },
        error: function() {
            $('#crSettingsLoading').hide();
            $('#crSettingsFormWrapper').css('display', 'block');
            Swal.fire({
                icon: 'error',
                title: 'ข้อผิดพลาด',
                text: 'ไม่สามารถเชื่อมต่อ API ได้'
            });
        }
    });
}

function populatePttypeSelects(ptData) {
    var opdOptions = '<option value="">-- เลือกสิทธิการรักษา OPD เพื่อเพิ่มเข้ารายการ --</option>';
    if (ptData.opd && Array.isArray(ptData.opd)) {
        ptData.opd.forEach(function(item) {
            opdOptions += `<option value="${item.pttype}">${item.pttype} : ${item.pttypename}</option>`;
        });
    }
    $('#cfg_opd_walkin_pttype_select').html(opdOptions);
    $('#cfg_opd_ae_pttype_select').html(opdOptions);
    $('#cfg_opd_newborn_pttype_select').html(opdOptions);

    var ipdOptions = '<option value="">-- เลือกสิทธิการรักษา IPD เพื่อเพิ่มเข้ารายการ --</option>';
    if (ptData.ipd && Array.isArray(ptData.ipd)) {
        ptData.ipd.forEach(function(item) {
            ipdOptions += `<option value="${item.pttype}">${item.pttype} : ${item.pttypename}</option>`;
        });
    }
    $('#cfg_ipd_ae_pttype_select').html(ipdOptions);
    $('#cfg_ipd_newborn_pttype_select').html(ipdOptions);
}

function addPttypeToInput(fieldId, pttypeCode) {
    if (!pttypeCode) return;
    var field = $('#' + fieldId);
    var currentVal = field.val().trim();
    var currentItems = currentVal ? currentVal.split(',').map(function(s) { return s.trim(); }).filter(function(s) { return s.length > 0; }) : [];
    if (!currentItems.includes(pttypeCode)) {
        currentItems.push(pttypeCode);
        field.val(currentItems.join(', '));
    }
}

function renderMonthControls(months) {
    var selectHtml = '<option value="ALL">🌐 เปิดใช้งานทุกเดือน (ไม่มีกำหนดเริ่มต้น)</option>';
    var checkHtml = '';

    months.forEach(function(m) {
        selectHtml += `<option value="${m}">${m}</option>`;
        checkHtml += `
            <div class="col-6 col-sm-4 col-md-3">
              <div class="form-check border rounded px-2 py-1 bg-light">
                <input class="form-check-input cr-month-check" type="checkbox" value="${m}" id="chk_month_${m.replace(/[^A-Za-z0-9]/g, '_')}">
                <label class="form-check-label small fw-semibold" for="chk_month_${m.replace(/[^A-Za-z0-9]/g, '_')}">${m}</label>
              </div>
            </div>
        `;
    });

    $('#crEffectiveMonthSelect').html(selectHtml);
    $('#crMonthsChecklistContainer').html(checkHtml);
}

function populateCrSettingsUI(cfg) {
    // 1. Month Mode & Values
    var monthMode = cfg.month_mode || 'ALL';
    if (!cfg.month_mode && cfg.effective_start_month && cfg.effective_start_month !== 'ALL') {
        monthMode = 'START_FROM';
    }
    $('input[name="crMonthModeRadio"][value="' + monthMode + '"]').prop('checked', true);
    handleMonthModeChange();

    var startMonth = cfg.effective_start_month || 'ALL';
    $('#crEffectiveMonthSelect').val(startMonth);

    if (cfg.active_months && Array.isArray(cfg.active_months)) {
        $('.cr-month-check').each(function() {
            var mVal = $(this).val();
            $(this).prop('checked', cfg.active_months.includes(mVal));
        });
    } else {
        $('.cr-month-check').prop('checked', true);
    }

    // 2. Auto-split switches (แยก OPD และ IPD)
    var autoSplitOpd = (cfg.auto_split_opd_enabled !== undefined) ? (cfg.auto_split_opd_enabled !== false) : (cfg.auto_split_enabled !== false);
    var autoSplitIpd = (cfg.auto_split_ipd_enabled !== undefined) ? (cfg.auto_split_ipd_enabled !== false) : (cfg.auto_split_enabled !== false);

    $('#crAutoSplitOpdSwitch').prop('checked', autoSplitOpd);
    $('#crAutoSplitIpdSwitch').prop('checked', autoSplitIpd);
    handleAutoSplitOpdToggle(false);
    handleAutoSplitIpdToggle(false);

    // 3. Parent Accounts
    if (cfg.parent_accounts) {
        $('.parent-acc-switch').each(function() {
            var acc = $(this).data('acc');
            var isEnabled = cfg.parent_accounts[acc] ? cfg.parent_accounts[acc].enabled : false;
            $(this).prop('checked', isEnabled);
        });
    }

    // ล้าง Custom Subgroup Tabs เดิมออกก่อน
    $('.custom-sg-nav-item').remove();
    $('.custom-sg-tab-pane').remove();

    // -------------------------------------------------------------
    // 4.1 Subgroups OPD (8 มาตรฐาน + Custom OPD)
    // -------------------------------------------------------------
    var sgOpd = cfg.subgroups_opd || cfg.subgroups || {};
    if (sgOpd['CR-Instrument']) {
        $('#cfg_opd_inst_enabled').prop('checked', sgOpd['CR-Instrument'].enabled !== false);
        $('#cfg_opd_inst_title').val(sgOpd['CR-Instrument'].title || '');
        $('#cfg_opd_inst_acc').val(sgOpd['CR-Instrument'].target_accountcode || '');
        $('#cfg_opd_inst_auto_adp2').prop('checked', sgOpd['CR-Instrument'].auto_adp_type_2 !== false);
        $('#cfg_opd_inst_adp_codes').val((sgOpd['CR-Instrument'].custom_adp_codes || []).join(', '));
        $('#cfg_opd_inst_start_month').val(sgOpd['CR-Instrument'].effective_start_month || 'ALL');
        $('#cfg_opd_inst_end_month').val(sgOpd['CR-Instrument'].effective_end_month || '');
    }
    if (sgOpd['CR-walkin']) {
        $('#cfg_opd_walkin_enabled').prop('checked', sgOpd['CR-walkin'].enabled !== false);
        $('#cfg_opd_walkin_title').val(sgOpd['CR-walkin'].title || '');
        $('#cfg_opd_walkin_acc').val(sgOpd['CR-walkin'].target_accountcode || '');
        var isCrossReg = sgOpd['CR-walkin'].check_cross_region_only !== false && sgOpd['CR-walkin'].check_hospmain_diff !== false;
        $('#cfg_opd_walkin_check_hospmain').prop('checked', isCrossReg);

        var myChw = sgOpd['CR-walkin'].hospital_chwpart || (window.crHospitalInfo ? window.crHospitalInfo.chwpart : '');
        $('#cfg_opd_walkin_my_chwpart').val(myChw);

        if (window.crHospitalInfo && window.crHospitalInfo.chwpart) {
            $('#cfg_opd_walkin_prov_hint').html(`<span class="text-success"><i class='bx bx-check-circle'></i> ตรวจพบ: ${window.crHospitalInfo.hospitalname} จ.${window.crHospitalInfo.province_name} (รหัสจังหวัด ${window.crHospitalInfo.chwpart})</span>`);
        }
        $('#cfg_opd_walkin_pttypes').val((sgOpd['CR-walkin'].pttypes || ['23']).join(', '));
        $('#cfg_opd_walkin_pttypenames').val((sgOpd['CR-walkin'].pttypenames || ['Walkin', 'walkin', 'WALKIN']).join(', '));
        $('#cfg_opd_walkin_check_adp').prop('checked', sgOpd['CR-walkin'].check_adp_walkin !== false);
        $('#cfg_opd_walkin_start_month').val(sgOpd['CR-walkin'].effective_start_month || 'ALL');
        $('#cfg_opd_walkin_end_month').val(sgOpd['CR-walkin'].effective_end_month || '');
    }
    if (sgOpd['CR-AE']) {
        $('#cfg_opd_ae_enabled').prop('checked', sgOpd['CR-AE'].enabled !== false);
        $('#cfg_opd_ae_title').val(sgOpd['CR-AE'].title || '');
        $('#cfg_opd_ae_acc').val(sgOpd['CR-AE'].target_accountcode || '');
        $('#cfg_opd_ae_pttypes').val((sgOpd['CR-AE'].pttypes || ['98', '97', '91', 'AM']).join(', '));
        $('#cfg_opd_ae_pttypenames').val((sgOpd['CR-AE'].pttypenames || ['ฉุกเฉิน', 'อุบัติเหตุ', 'AE']).join(', '));
        $('#cfg_opd_ae_check_cross_region').prop('checked', sgOpd['CR-AE'].check_cross_region_only !== false);
        var aeChw = sgOpd['CR-AE'].hospital_chwpart || (window.crHospitalInfo ? window.crHospitalInfo.chwpart : '');
        $('#cfg_opd_ae_my_chwpart').val(aeChw);
        $('#cfg_opd_ae_start_month').val(sgOpd['CR-AE'].effective_start_month || 'ALL');
        $('#cfg_opd_ae_end_month').val(sgOpd['CR-AE'].effective_end_month || '');
        if (window.crHospitalInfo && window.crHospitalInfo.chwpart) {
            $('#cfg_opd_ae_prov_hint').html(`<span class="text-success"><i class='bx bx-check-circle'></i> ตรวจพบ: ${window.crHospitalInfo.hospitalname} จ.${window.crHospitalInfo.province_name} (รหัสจังหวัด ${window.crHospitalInfo.chwpart})</span>`);
        }
    }
    if (sgOpd['CR-เกิดสิทธิทันที']) {
        $('#cfg_opd_newborn_enabled').prop('checked', sgOpd['CR-เกิดสิทธิทันที'].enabled !== false);
        $('#cfg_opd_newborn_title').val(sgOpd['CR-เกิดสิทธิทันที'].title || '');
        $('#cfg_opd_newborn_acc').val(sgOpd['CR-เกิดสิทธิทันที'].target_accountcode || '');
        $('#cfg_opd_newborn_pttypes').val((sgOpd['CR-เกิดสิทธิทันที'].pttypes || ['XX']).join(', '));
        $('#cfg_opd_newborn_pttypenames').val((sgOpd['CR-เกิดสิทธิทันที'].pttypenames || ['สิทธิว่าง', 'แรกเกิด', 'Newborn', 'NEWBORN']).join(', '));
        $('#cfg_opd_newborn_start_month').val(sgOpd['CR-เกิดสิทธิทันที'].effective_start_month || 'ALL');
        $('#cfg_opd_newborn_end_month').val(sgOpd['CR-เกิดสิทธิทันที'].effective_end_month || '');
    }
    if (sgOpd['CR-palliative']) {
        $('#cfg_opd_pall_enabled').prop('checked', sgOpd['CR-palliative'].enabled !== false);
        $('#cfg_opd_pall_title').val(sgOpd['CR-palliative'].title || '');
        $('#cfg_opd_pall_acc').val(sgOpd['CR-palliative'].target_accountcode || '');
        $('#cfg_opd_pall_icd10').val((sgOpd['CR-palliative'].icd10_rules || []).join(', '));
        $('#cfg_opd_pall_start_month').val(sgOpd['CR-palliative'].effective_start_month || 'ALL');
        $('#cfg_opd_pall_end_month').val(sgOpd['CR-palliative'].effective_end_month || '');
    }
    if (sgOpd['CR-Tele']) {
        $('#cfg_opd_tele_enabled').prop('checked', sgOpd['CR-Tele'].enabled !== false);
        $('#cfg_opd_tele_title').val(sgOpd['CR-Tele'].title || '');
        $('#cfg_opd_tele_acc').val(sgOpd['CR-Tele'].target_accountcode || '');
        $('#cfg_opd_tele_adp_codes').val((sgOpd['CR-Tele'].adp_codes || ['TELMED']).join(', '));
        $('#cfg_opd_tele_ovstist_export_codes').val((sgOpd['CR-Tele'].ovstist_export_codes || ['5']).join(', '));
        $('#cfg_opd_tele_icodes').val((sgOpd['CR-Tele'].custom_icodes || []).join(', '));
        $('#cfg_opd_tele_start_month').val(sgOpd['CR-Tele'].effective_start_month || 'ALL');
        $('#cfg_opd_tele_end_month').val(sgOpd['CR-Tele'].effective_end_month || '');
    }
    if (sgOpd['CR-ยาclopi']) {
        $('#cfg_opd_clopi_enabled').prop('checked', sgOpd['CR-ยาclopi'].enabled !== false);
        $('#cfg_opd_clopi_title').val(sgOpd['CR-ยาclopi'].title || '');
        $('#cfg_opd_clopi_acc').val(sgOpd['CR-ยาclopi'].target_accountcode || '');
        $('#cfg_opd_clopi_regex').val(sgOpd['CR-ยาclopi'].name_regex || '');
        $('#cfg_opd_clopi_start_month').val(sgOpd['CR-ยาclopi'].effective_start_month || 'ALL');
        $('#cfg_opd_clopi_end_month').val(sgOpd['CR-ยาclopi'].effective_end_month || '');
    }
    if (sgOpd['CR-ยาสมุนไพร']) {
        $('#cfg_opd_herb_enabled').prop('checked', sgOpd['CR-ยาสมุนไพร'].enabled !== false);
        $('#cfg_opd_herb_title').val(sgOpd['CR-ยาสมุนไพร'].title || '');
        $('#cfg_opd_herb_acc').val(sgOpd['CR-ยาสมุนไพร'].target_accountcode || '');
        $('#cfg_opd_herb_start_month').val(sgOpd['CR-ยาสมุนไพร'].effective_start_month || 'ALL');
        $('#cfg_opd_herb_end_month').val(sgOpd['CR-ยาสมุนไพร'].effective_end_month || '');
    }

    // วนลูป Custom Subgroups OPD
    var standardKeysOpd = ['CR-Instrument', 'CR-walkin', 'CR-AE', 'CR-เกิดสิทธิทันที', 'CR-palliative', 'CR-Tele', 'CR-ยาclopi', 'CR-ยาสมุนไพร'];
    Object.keys(sgOpd).forEach(function(k) {
        if (!standardKeysOpd.includes(k)) {
            renderCustomSubgroupTab(sgOpd[k], 'OPD');
        }
    });

    // -------------------------------------------------------------
    // 4.2 Subgroups IPD (4 มาตรฐาน: Instrument, รถรีเฟอร์, CR-AE, CR-เกิดสิทธิทันที + Custom IPD)
    // -------------------------------------------------------------
    var sgIpd = cfg.subgroups_ipd || {};
    if (sgIpd['CR-Instrument']) {
        $('#cfg_ipd_inst_enabled').prop('checked', sgIpd['CR-Instrument'].enabled !== false);
        $('#cfg_ipd_inst_title').val(sgIpd['CR-Instrument'].title || '');
        $('#cfg_ipd_inst_acc').val(sgIpd['CR-Instrument'].target_accountcode || '');
        $('#cfg_ipd_inst_auto_adp2').prop('checked', sgIpd['CR-Instrument'].auto_adp_type_2 !== false);
        $('#cfg_ipd_inst_adp_codes').val((sgIpd['CR-Instrument'].custom_adp_codes || []).join(', '));
        $('#cfg_ipd_inst_start_month').val(sgIpd['CR-Instrument'].effective_start_month || 'ALL');
        $('#cfg_ipd_inst_end_month').val(sgIpd['CR-Instrument'].effective_end_month || '');
    }
    if (sgIpd['CR-รถรีเฟอร์']) {
        $('#cfg_ipd_refer_enabled').prop('checked', sgIpd['CR-รถรีเฟอร์'].enabled !== false);
        $('#cfg_ipd_refer_title').val(sgIpd['CR-รถรีเฟอร์'].title || '');
        $('#cfg_ipd_refer_acc').val(sgIpd['CR-รถรีเฟอร์'].target_accountcode || '');
        $('#cfg_ipd_refer_adp_codes').val((sgIpd['CR-รถรีเฟอร์'].custom_adp_codes || []).join(', '));
        $('#cfg_ipd_refer_icodes').val((sgIpd['CR-รถรีเฟอร์'].custom_icodes || []).join(', '));
        $('#cfg_ipd_refer_start_month').val(sgIpd['CR-รถรีเฟอร์'].effective_start_month || 'ALL');
        $('#cfg_ipd_refer_end_month').val(sgIpd['CR-รถรีเฟอร์'].effective_end_month || '');
    }
    if (sgIpd['CR-AE']) {
        $('#cfg_ipd_ae_enabled').prop('checked', sgIpd['CR-AE'].enabled !== false);
        $('#cfg_ipd_ae_title').val(sgIpd['CR-AE'].title || '');
        $('#cfg_ipd_ae_acc').val(sgIpd['CR-AE'].target_accountcode || '');
        $('#cfg_ipd_ae_pttypes').val((sgIpd['CR-AE'].pttypes || ['91', 'AM']).join(', '));
        $('#cfg_ipd_ae_pttypenames').val((sgIpd['CR-AE'].pttypenames || ['ฉุกเฉิน', 'อุบัติเหตุ', 'AE']).join(', '));
        $('#cfg_ipd_ae_start_month').val(sgIpd['CR-AE'].effective_start_month || 'ALL');
        $('#cfg_ipd_ae_end_month').val(sgIpd['CR-AE'].effective_end_month || '');
    }
    if (sgIpd['CR-เกิดสิทธิทันที']) {
        $('#cfg_ipd_newborn_enabled').prop('checked', sgIpd['CR-เกิดสิทธิทันที'].enabled !== false);
        $('#cfg_ipd_newborn_title').val(sgIpd['CR-เกิดสิทธิทันที'].title || '');
        $('#cfg_ipd_newborn_acc').val(sgIpd['CR-เกิดสิทธิทันที'].target_accountcode || '');
        $('#cfg_ipd_newborn_pttypes').val((sgIpd['CR-เกิดสิทธิทันที'].pttypes || ['XX']).join(', '));
        $('#cfg_ipd_newborn_pttypenames').val((sgIpd['CR-เกิดสิทธิทันที'].pttypenames || ['สิทธิว่าง', 'แรกเกิด', 'Newborn', 'NEWBORN']).join(', '));
        $('#cfg_ipd_newborn_start_month').val(sgIpd['CR-เกิดสิทธิทันที'].effective_start_month || 'ALL');
        $('#cfg_ipd_newborn_end_month').val(sgIpd['CR-เกิดสิทธิทันที'].effective_end_month || '');
    }

    // วนลูป Custom Subgroups IPD
    var standardKeysIpd = ['CR-Instrument', 'CR-รถรีเฟอร์', 'CR-AE', 'CR-เกิดสิทธิทันที'];
    Object.keys(sgIpd).forEach(function(k) {
        if (!standardKeysIpd.includes(k)) {
            renderCustomSubgroupTab(sgIpd[k], 'IPD');
        }
    });
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getCustomSubgroupSafeId(id) {
    if (!id) return 'sg_' + Math.random().toString(36).substring(2, 9);
    var hash = 0;
    for (var i = 0; i < id.length; i++) {
        hash = ((hash << 5) - hash) + id.charCodeAt(i);
        hash |= 0;
    }
    var cleanPrefix = id.replace(/[^A-Za-z0-9]/g, '').substring(0, 10);
    return (cleanPrefix ? cleanPrefix + '_' : '') + Math.abs(hash).toString(36);
}

function parseCsv(str) {
    return (str || '').split(',').map(function(s) { return s.trim(); }).filter(function(s) { return s.length > 0; });
}

function renderCustomSubgroupTab(item, visitType) {
    if (!visitType) visitType = 'OPD';
    var isIpd = (visitType === 'IPD');
    var safeId = getCustomSubgroupSafeId(item.id);
    var tabId = (isIpd ? 'tab_ipd_custom_' : 'tab_opd_custom_') + safeId;
    if ($('#' + tabId).length) return; // มีอยู่แล้ว

    var isChecked = (item.enabled !== false) ? 'checked' : '';
    var navContainer = isIpd ? '#crSettingSubgroupTabsIpd' : '#crSettingSubgroupTabsOpd';
    var contentContainer = isIpd ? '#crSettingSubgroupTabContentIpd' : '#crSettingSubgroupTabContentOpd';

    var escapedId = escapeHtml(item.id);
    var escapedTitle = escapeHtml(item.title || item.id);
    var escapedAcc = escapeHtml(item.target_accountcode || '');
    var escapedStartMonth = escapeHtml(item.effective_start_month || 'ALL');
    var escapedEndMonth = escapeHtml(item.effective_end_month || '');
    var escapedAdp = escapeHtml((item.custom_adp_codes || []).join(', '));
    var escapedIcodes = escapeHtml((item.custom_icodes || []).join(', '));

    var navBtn = `
        <li class="nav-item custom-sg-nav-item" id="nav_${tabId}" data-sg-id="${escapedId}" data-visit-type="${visitType}">
          <button class="nav-link py-1 px-3" data-bs-toggle="pill" data-bs-target="#${tabId}" type="button">
            ${escapedId} <span class="badge ${isIpd ? 'bg-success' : 'bg-primary'} ms-1">Custom</span>
          </button>
        </li>
    `;
    var tabContent = `
        <div class="tab-pane fade custom-sg-tab-pane" id="${tabId}" data-sg-id="${escapedId}" data-visit-type="${visitType}">
          <div class="row g-3">
            <div class="col-md-12">
              <div class="p-2 px-3 border rounded bg-light d-flex align-items-center justify-content-between">
                <div>
                  <span class="fw-bold text-dark"><i class='bx bx-power-off me-1 ${isIpd ? 'text-success' : 'text-primary'}'></i> สถานะการใช้งานกลุ่ม ${escapedId} (${visitType})</span>
                  <div class="small text-muted">หากปิดสวิตช์ ระบบจะไม่ตรวจจับและไม่ตัดยกยอดค่าใช้จ่ายสำหรับกลุ่มนี้</div>
                </div>
                <div class="form-check form-switch ms-3">
                  <input class="form-check-input custom-sg-enabled" type="checkbox" data-sg-id="${escapedId}" data-visit-type="${visitType}" ${isChecked} style="transform: scale(1.3); cursor: pointer;">
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">ชื่อกลุ่ม / คำอธิบาย</label>
              <input type="text" class="form-control custom-sg-title" data-sg-id="${escapedId}" data-visit-type="${visitType}" value="${escapedTitle}">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">ผังบัญชีปลายทาง</label>
              <input type="text" class="form-control custom-sg-acc" data-sg-id="${escapedId}" data-visit-type="${visitType}" value="${escapedAcc}">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">📅 เดือนเริ่มต้นบังคับใช้ (Start Month)</label>
              <input type="text" class="form-control font-monospace custom-sg-start-month" data-sg-id="${escapedId}" data-visit-type="${visitType}" value="${escapedStartMonth}" placeholder="ALL หรือ 09-2569">
              <small class="text-muted">ใส่ ALL เพื่อบังคับใช้ทุกเดือน หรือระบุ MM-YYYY เช่น 09-2569</small>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">⏳ เดือนสิ้นสุดบังคับใช้ (End Month)</label>
              <input type="text" class="form-control font-monospace custom-sg-end-month" data-sg-id="${escapedId}" data-visit-type="${visitType}" value="${escapedEndMonth}" placeholder="เว้นว่างถ้าไม่มีกำหนดสิ้นสุด">
              <small class="text-muted">เว้นว่างถ้ามีผลตลอดไป หรือระบุ MM-YYYY</small>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">รหัส ADP พิเศษ (คั่นด้วยจุลภาค ,)</label>
              <input type="text" class="form-control custom-sg-adp" data-sg-id="${escapedId}" data-visit-type="${visitType}" value="${escapedAdp}">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">รหัส icode ยา/บริการ (คั่นด้วยจุลภาค ,)</label>
              <input type="text" class="form-control custom-sg-icodes" data-sg-id="${escapedId}" data-visit-type="${visitType}" value="${escapedIcodes}">
            </div>
            <div class="col-md-12 pt-3 border-top mt-2 d-flex justify-content-between align-items-center">
              <span class="text-muted small"><i class='bx bx-info-circle me-1'></i> กลุ่มย่อยที่กำหนดเอง (Custom Subgroup: ${visitType})</span>
              <button type="button" class="btn btn-outline-danger btn-sm px-3 btn-delete-custom-sg" data-sg-id="${escapedId}" data-safe-id="${safeId}" data-visit-type="${visitType}">
                <i class='bx bx-trash me-1'></i> ลบกลุ่มย่อยนี้
              </button>
            </div>
          </div>
        </div>
    `;

    $(navContainer).append(navBtn);
    $(contentContainer).append(tabContent);
}

function deleteCustomSubgroup(id, safeId, visitType) {
    if (!visitType) visitType = 'OPD';
    if (!safeId) safeId = getCustomSubgroupSafeId(id);

    Swal.fire({
        title: `ยืนยันการลบกลุ่มย่อย ${id}?`,
        html: `คุณต้องการลบกลุ่มย่อย <b>"${escapeHtml(id)}"</b> (${visitType}) ออกจากระบบใช่หรือไม่?<br><small class="text-muted">ระบบจะตรวจสอบประวัติการใช้งานก่อนลบเพื่อความปลอดภัยของข้อมูล</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, ลบกลุ่มย่อย',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังตรวจสอบและลบ...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: 'api_cr_debtor.php?action=delete_subgroup',
                method: 'POST',
                data: JSON.stringify({ subgroup_id: id, visit_type: visitType }),
                contentType: 'application/json',
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        var isIpd = (visitType === 'IPD');
                        var tabId = (isIpd ? 'tab_ipd_custom_' : 'tab_opd_custom_') + safeId;

                        // ลบ DOM Elements ทั้งแท็บและเนื้อหา
                        $('#nav_' + tabId).remove();
                        $('#' + tabId).remove();
                        $('.custom-sg-nav-item[data-sg-id="' + id + '"][data-visit-type="' + visitType + '"]').remove();
                        $('.custom-sg-tab-pane[data-sg-id="' + id + '"][data-visit-type="' + visitType + '"]').remove();

                        var groupKey = isIpd ? 'subgroups_ipd' : 'subgroups_opd';
                        if (currentCrConfig && currentCrConfig[groupKey] && currentCrConfig[groupKey][id]) {
                            delete currentCrConfig[groupKey][id];
                        }

                        // สลับไปแท็บแรก
                        var navContainer = isIpd ? '#crSettingSubgroupTabsIpd' : '#crSettingSubgroupTabsOpd';
                        $(navContainer + ' .nav-link').first().tab('show');

                        Swal.fire({
                            icon: 'success',
                            title: 'ลบสำเร็จ',
                            text: res.message || ('ลบกลุ่มย่อย ' + id + ' เรียบร้อยแล้ว'),
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'warning',
                            title: 'ไม่สามารถลบกลุ่มย่อยได้',
                            text: res.message,
                            confirmButtonText: 'เข้าใจแล้ว',
                            confirmButtonColor: '#2563eb'
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'ข้อผิดพลาด',
                        text: 'ไม่สามารถเชื่อมต่อ API ได้ (HTTP ' + (xhr.status || 'Error') + ')'
                    });
                }
            });
        }
    });
}

// ผูก Event Handler ให้ปุ่มลบกลุ่มย่อย Custom
$(document).on('click', '.btn-delete-custom-sg', function(e) {
    e.preventDefault();
    var id = $(this).attr('data-sg-id') || $(this).data('sg-id');
    var safeId = $(this).attr('data-safe-id') || $(this).data('safe-id');
    var visitType = $(this).attr('data-visit-type') || $(this).data('visit-type') || 'OPD';
    deleteCustomSubgroup(id, safeId, visitType);
});

function openAddNewCustomSubgroupModal(visitType) {
    if (!visitType) visitType = 'OPD';
    $('#new_sg_type').val(visitType);
    $('#new_sg_enabled').prop('checked', true);
    $('#new_sg_id').val('');
    $('#new_sg_title').val('');
    $('#new_sg_acc').val('');
    $('#new_sg_start_month').val('ALL');
    $('#new_sg_end_month').val('');
    $('#new_sg_adp').val('');
    $('#new_sg_icodes').val('');

    var isIpd = (visitType === 'IPD');
    if (isIpd) {
        $('#modalAddNewCrSubgroupHeader').removeClass('bg-primary').addClass('bg-success');
        $('#modalAddNewCrSubgroupTitle').html("<i class='bx bx-plus-circle me-1'></i> เพิ่มกลุ่มย่อย CR ผู้ป่วยใน (IPD Subgroup)");
        $('#new_sg_type_badge').removeClass('bg-primary').addClass('bg-success').html("<span><i class='bx bx-bed me-1'></i> ผู้ป่วยใน (IPD)</span><small class='opacity-75'>ผังหลัก 1102050101.217</small>");
        $('#new_sg_acc').attr('placeholder', 'เช่น 1102050101.217_CR_SPECIAL');
    } else {
        $('#modalAddNewCrSubgroupHeader').removeClass('bg-success').addClass('bg-primary');
        $('#modalAddNewCrSubgroupTitle').html("<i class='bx bx-plus-circle me-1'></i> เพิ่มกลุ่มย่อย CR ผู้ป่วยนอก (OPD Subgroup)");
        $('#new_sg_type_badge').removeClass('bg-success').addClass('bg-primary').html("<span><i class='bx bx-walk me-1'></i> ผู้ป่วยนอก (OPD)</span><small class='opacity-75'>ผังหลัก 1102050101.216</small>");
        $('#new_sg_acc').attr('placeholder', 'เช่น 1102050101.216_CR_DENT');
    }

    var subModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAddNewCrSubgroup'));
    subModal.show();
}

function submitAddNewCustomSubgroup() {
    var visitType = $('#new_sg_type').val() || 'OPD';
    var isIpd = (visitType === 'IPD');
    var id = $('#new_sg_id').val().trim();
    var title = $('#new_sg_title').val().trim();
    var acc = $('#new_sg_acc').val().trim();
    var startMonth = ($('#new_sg_start_month').val() || 'ALL').trim();
    var endMonth = ($('#new_sg_end_month').val() || '').trim();
    var adp = $('#new_sg_adp').val().trim();
    var icodes = $('#new_sg_icodes').val().trim();
    var isEnabled = $('#new_sg_enabled').is(':checked');

    if (!id || !title) {
        Swal.fire({
            icon: 'warning',
            title: 'ข้อมูลไม่ครบถ้วน',
            text: 'กรุณาระบุรหัสกลุ่มย่อยและชื่อกลุ่ม'
        });
        return;
    }

    if (!currentCrConfig) currentCrConfig = {};
    var groupKey = isIpd ? 'subgroups_ipd' : 'subgroups_opd';
    if (!currentCrConfig[groupKey]) currentCrConfig[groupKey] = {};

    var defaultPrefix = isIpd ? '1102050101.217_' : '1102050101.216_';
    var targetAcc = acc || (defaultPrefix + id);
    var item = {
        id: id,
        enabled: isEnabled,
        title: title,
        short_name: id,
        target_accountcode: targetAcc,
        accountname: '- ลูกหนี้ ' + title,
        icon: '',
        effective_start_month: startMonth,
        effective_end_month: endMonth,
        custom_adp_codes: parseCsv(adp),
        custom_icodes: parseCsv(icodes)
    };

    currentCrConfig[groupKey][id] = item;
    renderCustomSubgroupTab(item, visitType);

    // Active tab ใหม่
    var safeId = getCustomSubgroupSafeId(id);
    var tabId = (isIpd ? 'tab_ipd_custom_' : 'tab_opd_custom_') + safeId;
    var navContainer = isIpd ? '#crSettingSubgroupTabsIpd' : '#crSettingSubgroupTabsOpd';
    var contentContainer = isIpd ? '#crSettingSubgroupTabContentIpd' : '#crSettingSubgroupTabContentOpd';

    $(navContainer + ' .nav-link').removeClass('active');
    $(contentContainer + ' .tab-pane').removeClass('show active');
    $('#nav_' + tabId + ' .nav-link').addClass('active');
    $('#' + tabId).addClass('show active');

    // ปิด Sub Modal
    bootstrap.Modal.getInstance(document.getElementById('modalAddNewCrSubgroup')).hide();

    Swal.fire({
        icon: 'success',
        title: 'เพิ่มกลุ่มย่อยสำเร็จ',
        text: 'เพิ่มกลุ่มย่อย ' + id + ' (' + visitType + ') เรียบร้อยแล้ว อย่าลืมกด "บันทึกการตั้งค่า"',
        timer: 2000,
        showConfirmButton: false
    });
}

function buildConfigFromUI() {
    var cfg = currentCrConfig || {};

    var monthMode = $('input[name="crMonthModeRadio"]:checked').val() || 'ALL';
    cfg.month_mode = monthMode;
    cfg.effective_start_month = $('#crEffectiveMonthSelect').val() || 'ALL';
    
    cfg.active_months = [];
    $('.cr-month-check:checked').each(function() {
        cfg.active_months.push($(this).val());
    });

    var autoSplitOpd = $('#crAutoSplitOpdSwitch').is(':checked');
    var autoSplitIpd = $('#crAutoSplitIpdSwitch').is(':checked');
    cfg.auto_split_opd_enabled = autoSplitOpd;
    cfg.auto_split_ipd_enabled = autoSplitIpd;
    cfg.auto_split_enabled = (autoSplitOpd || autoSplitIpd);

    if (!cfg.parent_accounts) cfg.parent_accounts = {};
    $('.parent-acc-switch').each(function() {
        var acc = $(this).data('acc');
        if (!cfg.parent_accounts[acc]) cfg.parent_accounts[acc] = {};
        cfg.parent_accounts[acc].enabled = $(this).is(':checked');
    });

    // -------------------------------------------------------------
    // Subgroups OPD (8 กลุ่มมาตรฐาน + Custom OPD)
    // -------------------------------------------------------------
    if (!cfg.subgroups_opd) cfg.subgroups_opd = {};

    if (!cfg.subgroups_opd['CR-Instrument']) cfg.subgroups_opd['CR-Instrument'] = {};
    cfg.subgroups_opd['CR-Instrument'].enabled = $('#cfg_opd_inst_enabled').is(':checked');
    cfg.subgroups_opd['CR-Instrument'].title = $('#cfg_opd_inst_title').val();
    cfg.subgroups_opd['CR-Instrument'].target_accountcode = $('#cfg_opd_inst_acc').val();
    cfg.subgroups_opd['CR-Instrument'].auto_adp_type_2 = $('#cfg_opd_inst_auto_adp2').is(':checked');
    cfg.subgroups_opd['CR-Instrument'].custom_adp_codes = parseCsv($('#cfg_opd_inst_adp_codes').val());
    cfg.subgroups_opd['CR-Instrument'].effective_start_month = ($('#cfg_opd_inst_start_month').val() || 'ALL').trim();
    cfg.subgroups_opd['CR-Instrument'].effective_end_month = ($('#cfg_opd_inst_end_month').val() || '').trim();

    if (!cfg.subgroups_opd['CR-walkin']) cfg.subgroups_opd['CR-walkin'] = {};
    cfg.subgroups_opd['CR-walkin'].enabled = $('#cfg_opd_walkin_enabled').is(':checked');
    cfg.subgroups_opd['CR-walkin'].title = $('#cfg_opd_walkin_title').val();
    cfg.subgroups_opd['CR-walkin'].target_accountcode = $('#cfg_opd_walkin_acc').val();
    cfg.subgroups_opd['CR-walkin'].check_hospmain_diff = $('#cfg_opd_walkin_check_hospmain').is(':checked');
    cfg.subgroups_opd['CR-walkin'].check_cross_region_only = $('#cfg_opd_walkin_check_hospmain').is(':checked');
    cfg.subgroups_opd['CR-walkin'].hospital_chwpart = $('#cfg_opd_walkin_my_chwpart').val().trim();
    cfg.subgroups_opd['CR-walkin'].check_adp_walkin = $('#cfg_opd_walkin_check_adp').is(':checked');
    cfg.subgroups_opd['CR-walkin'].pttypes = parseCsv($('#cfg_opd_walkin_pttypes').val());
    cfg.subgroups_opd['CR-walkin'].pttypenames = parseCsv($('#cfg_opd_walkin_pttypenames').val());
    cfg.subgroups_opd['CR-walkin'].effective_start_month = ($('#cfg_opd_walkin_start_month').val() || 'ALL').trim();
    cfg.subgroups_opd['CR-walkin'].effective_end_month = ($('#cfg_opd_walkin_end_month').val() || '').trim();

    if (!cfg.subgroups_opd['CR-AE']) cfg.subgroups_opd['CR-AE'] = {};
    cfg.subgroups_opd['CR-AE'].enabled = $('#cfg_opd_ae_enabled').is(':checked');
    cfg.subgroups_opd['CR-AE'].title = $('#cfg_opd_ae_title').val();
    cfg.subgroups_opd['CR-AE'].target_accountcode = $('#cfg_opd_ae_acc').val();
    cfg.subgroups_opd['CR-AE'].check_cross_region_only = $('#cfg_opd_ae_check_cross_region').is(':checked');
    cfg.subgroups_opd['CR-AE'].hospital_chwpart = $('#cfg_opd_ae_my_chwpart').val().trim();
    cfg.subgroups_opd['CR-AE'].pttypes = parseCsv($('#cfg_opd_ae_pttypes').val());
    cfg.subgroups_opd['CR-AE'].pttypenames = parseCsv($('#cfg_opd_ae_pttypenames').val());
    cfg.subgroups_opd['CR-AE'].transfer_percent = 100;
    cfg.subgroups_opd['CR-AE'].effective_start_month = ($('#cfg_opd_ae_start_month').val() || 'ALL').trim();
    cfg.subgroups_opd['CR-AE'].effective_end_month = ($('#cfg_opd_ae_end_month').val() || '').trim();

    if (!cfg.subgroups_opd['CR-เกิดสิทธิทันที']) cfg.subgroups_opd['CR-เกิดสิทธิทันที'] = {};
    cfg.subgroups_opd['CR-เกิดสิทธิทันที'].enabled = $('#cfg_opd_newborn_enabled').is(':checked');
    cfg.subgroups_opd['CR-เกิดสิทธิทันที'].title = $('#cfg_opd_newborn_title').val();
    cfg.subgroups_opd['CR-เกิดสิทธิทันที'].target_accountcode = $('#cfg_opd_newborn_acc').val();
    cfg.subgroups_opd['CR-เกิดสิทธิทันที'].pttypes = parseCsv($('#cfg_opd_newborn_pttypes').val());
    cfg.subgroups_opd['CR-เกิดสิทธิทันที'].pttypenames = parseCsv($('#cfg_opd_newborn_pttypenames').val());
    cfg.subgroups_opd['CR-เกิดสิทธิทันที'].transfer_percent = 100;
    cfg.subgroups_opd['CR-เกิดสิทธิทันที'].effective_start_month = ($('#cfg_opd_newborn_start_month').val() || 'ALL').trim();
    cfg.subgroups_opd['CR-เกิดสิทธิทันที'].effective_end_month = ($('#cfg_opd_newborn_end_month').val() || '').trim();

    if (!cfg.subgroups_opd['CR-palliative']) cfg.subgroups_opd['CR-palliative'] = {};
    cfg.subgroups_opd['CR-palliative'].enabled = $('#cfg_opd_pall_enabled').is(':checked');
    cfg.subgroups_opd['CR-palliative'].title = $('#cfg_opd_pall_title').val();
    cfg.subgroups_opd['CR-palliative'].target_accountcode = $('#cfg_opd_pall_acc').val();
    cfg.subgroups_opd['CR-palliative'].icd10_rules = parseCsv($('#cfg_opd_pall_icd10').val());
    cfg.subgroups_opd['CR-palliative'].effective_start_month = ($('#cfg_opd_pall_start_month').val() || 'ALL').trim();
    cfg.subgroups_opd['CR-palliative'].effective_end_month = ($('#cfg_opd_pall_end_month').val() || '').trim();

    if (!cfg.subgroups_opd['CR-Tele']) cfg.subgroups_opd['CR-Tele'] = {};
    cfg.subgroups_opd['CR-Tele'].enabled = $('#cfg_opd_tele_enabled').is(':checked');
    cfg.subgroups_opd['CR-Tele'].title = $('#cfg_opd_tele_title').val();
    cfg.subgroups_opd['CR-Tele'].target_accountcode = $('#cfg_opd_tele_acc').val();
    cfg.subgroups_opd['CR-Tele'].adp_codes = parseCsv($('#cfg_opd_tele_adp_codes').val());
    cfg.subgroups_opd['CR-Tele'].ovstist_export_codes = parseCsv($('#cfg_opd_tele_ovstist_export_codes').val());
    cfg.subgroups_opd['CR-Tele'].custom_icodes = parseCsv($('#cfg_opd_tele_icodes').val());
    cfg.subgroups_opd['CR-Tele'].effective_start_month = ($('#cfg_opd_tele_start_month').val() || 'ALL').trim();
    cfg.subgroups_opd['CR-Tele'].effective_end_month = ($('#cfg_opd_tele_end_month').val() || '').trim();

    if (!cfg.subgroups_opd['CR-ยาclopi']) cfg.subgroups_opd['CR-ยาclopi'] = {};
    cfg.subgroups_opd['CR-ยาclopi'].enabled = $('#cfg_opd_clopi_enabled').is(':checked');
    cfg.subgroups_opd['CR-ยาclopi'].title = $('#cfg_opd_clopi_title').val();
    cfg.subgroups_opd['CR-ยาclopi'].target_accountcode = $('#cfg_opd_clopi_acc').val();
    cfg.subgroups_opd['CR-ยาclopi'].name_regex = $('#cfg_opd_clopi_regex').val();
    cfg.subgroups_opd['CR-ยาclopi'].effective_start_month = ($('#cfg_opd_clopi_start_month').val() || 'ALL').trim();
    cfg.subgroups_opd['CR-ยาclopi'].effective_end_month = ($('#cfg_opd_clopi_end_month').val() || '').trim();

    if (!cfg.subgroups_opd['CR-ยาสมุนไพร']) cfg.subgroups_opd['CR-ยาสมุนไพร'] = {};
    cfg.subgroups_opd['CR-ยาสมุนไพร'].enabled = $('#cfg_opd_herb_enabled').is(':checked');
    cfg.subgroups_opd['CR-ยาสมุนไพร'].title = $('#cfg_opd_herb_title').val();
    cfg.subgroups_opd['CR-ยาสมุนไพร'].target_accountcode = $('#cfg_opd_herb_acc').val();
    cfg.subgroups_opd['CR-ยาสมุนไพร'].effective_start_month = ($('#cfg_opd_herb_start_month').val() || 'ALL').trim();
    cfg.subgroups_opd['CR-ยาสมุนไพร'].effective_end_month = ($('#cfg_opd_herb_end_month').val() || '').trim();

    // วนลูปอ่าน Custom OPD
    $('.custom-sg-title[data-visit-type="OPD"]').each(function() {
        var sgId = $(this).data('sg-id');
        if (!cfg.subgroups_opd[sgId]) cfg.subgroups_opd[sgId] = { id: sgId, short_name: sgId, icon: '' };
        cfg.subgroups_opd[sgId].enabled = $('.custom-sg-enabled[data-sg-id="' + sgId + '"][data-visit-type="OPD"]').is(':checked');
        cfg.subgroups_opd[sgId].title = $(this).val();
        cfg.subgroups_opd[sgId].target_accountcode = $('.custom-sg-acc[data-sg-id="' + sgId + '"][data-visit-type="OPD"]').val();
        cfg.subgroups_opd[sgId].effective_start_month = ($('.custom-sg-start-month[data-sg-id="' + sgId + '"][data-visit-type="OPD"]').val() || 'ALL').trim();
        cfg.subgroups_opd[sgId].effective_end_month = ($('.custom-sg-end-month[data-sg-id="' + sgId + '"][data-visit-type="OPD"]').val() || '').trim();
        cfg.subgroups_opd[sgId].custom_adp_codes = parseCsv($('.custom-sg-adp[data-sg-id="' + sgId + '"][data-visit-type="OPD"]').val());
        cfg.subgroups_opd[sgId].custom_icodes = parseCsv($('.custom-sg-icodes[data-sg-id="' + sgId + '"][data-visit-type="OPD"]').val());
    });

    // -------------------------------------------------------------
    // Subgroups IPD (4 กลุ่มมาตรฐาน: Instrument, รถรีเฟอร์, CR-AE, CR-เกิดสิทธิทันที + Custom IPD)
    // -------------------------------------------------------------
    if (!cfg.subgroups_ipd) cfg.subgroups_ipd = {};

    if (!cfg.subgroups_ipd['CR-Instrument']) cfg.subgroups_ipd['CR-Instrument'] = {};
    cfg.subgroups_ipd['CR-Instrument'].enabled = $('#cfg_ipd_inst_enabled').is(':checked');
    cfg.subgroups_ipd['CR-Instrument'].title = $('#cfg_ipd_inst_title').val();
    cfg.subgroups_ipd['CR-Instrument'].target_accountcode = $('#cfg_ipd_inst_acc').val();
    cfg.subgroups_ipd['CR-Instrument'].auto_adp_type_2 = $('#cfg_ipd_inst_auto_adp2').is(':checked');
    cfg.subgroups_ipd['CR-Instrument'].custom_adp_codes = parseCsv($('#cfg_ipd_inst_adp_codes').val());
    cfg.subgroups_ipd['CR-Instrument'].effective_start_month = ($('#cfg_ipd_inst_start_month').val() || 'ALL').trim();
    cfg.subgroups_ipd['CR-Instrument'].effective_end_month = ($('#cfg_ipd_inst_end_month').val() || '').trim();

    if (!cfg.subgroups_ipd['CR-รถรีเฟอร์']) cfg.subgroups_ipd['CR-รถรีเฟอร์'] = {};
    cfg.subgroups_ipd['CR-รถรีเฟอร์'].enabled = $('#cfg_ipd_refer_enabled').is(':checked');
    cfg.subgroups_ipd['CR-รถรีเฟอร์'].title = $('#cfg_ipd_refer_title').val();
    cfg.subgroups_ipd['CR-รถรีเฟอร์'].target_accountcode = $('#cfg_ipd_refer_acc').val();
    cfg.subgroups_ipd['CR-รถรีเฟอร์'].custom_adp_codes = parseCsv($('#cfg_ipd_refer_adp_codes').val());
    cfg.subgroups_ipd['CR-รถรีเฟอร์'].custom_icodes = parseCsv($('#cfg_ipd_refer_icodes').val());
    cfg.subgroups_ipd['CR-รถรีเฟอร์'].effective_start_month = ($('#cfg_ipd_refer_start_month').val() || 'ALL').trim();
    cfg.subgroups_ipd['CR-รถรีเฟอร์'].effective_end_month = ($('#cfg_ipd_refer_end_month').val() || '').trim();

    if (!cfg.subgroups_ipd['CR-AE']) cfg.subgroups_ipd['CR-AE'] = {};
    cfg.subgroups_ipd['CR-AE'].enabled = $('#cfg_ipd_ae_enabled').is(':checked');
    cfg.subgroups_ipd['CR-AE'].title = $('#cfg_ipd_ae_title').val();
    cfg.subgroups_ipd['CR-AE'].target_accountcode = $('#cfg_ipd_ae_acc').val();
    cfg.subgroups_ipd['CR-AE'].pttypes = parseCsv($('#cfg_ipd_ae_pttypes').val());
    cfg.subgroups_ipd['CR-AE'].pttypenames = parseCsv($('#cfg_ipd_ae_pttypenames').val());
    cfg.subgroups_ipd['CR-AE'].transfer_percent = 100;
    cfg.subgroups_ipd['CR-AE'].effective_start_month = ($('#cfg_ipd_ae_start_month').val() || 'ALL').trim();
    cfg.subgroups_ipd['CR-AE'].effective_end_month = ($('#cfg_ipd_ae_end_month').val() || '').trim();

    if (!cfg.subgroups_ipd['CR-เกิดสิทธิทันที']) cfg.subgroups_ipd['CR-เกิดสิทธิทันที'] = {};
    cfg.subgroups_ipd['CR-เกิดสิทธิทันที'].enabled = $('#cfg_ipd_newborn_enabled').is(':checked');
    cfg.subgroups_ipd['CR-เกิดสิทธิทันที'].title = $('#cfg_ipd_newborn_title').val();
    cfg.subgroups_ipd['CR-เกิดสิทธิทันที'].target_accountcode = $('#cfg_ipd_newborn_acc').val();
    cfg.subgroups_ipd['CR-เกิดสิทธิทันที'].pttypes = parseCsv($('#cfg_ipd_newborn_pttypes').val());
    cfg.subgroups_ipd['CR-เกิดสิทธิทันที'].pttypenames = parseCsv($('#cfg_ipd_newborn_pttypenames').val());
    cfg.subgroups_ipd['CR-เกิดสิทธิทันที'].transfer_percent = 100;
    cfg.subgroups_ipd['CR-เกิดสิทธิทันที'].effective_start_month = ($('#cfg_ipd_newborn_start_month').val() || 'ALL').trim();
    cfg.subgroups_ipd['CR-เกิดสิทธิทันที'].effective_end_month = ($('#cfg_ipd_newborn_end_month').val() || '').trim();

    // วนลูปอ่าน Custom IPD
    $('.custom-sg-title[data-visit-type="IPD"]').each(function() {
        var sgId = $(this).data('sg-id');
        if (!cfg.subgroups_ipd[sgId]) cfg.subgroups_ipd[sgId] = { id: sgId, short_name: sgId, icon: '' };
        cfg.subgroups_ipd[sgId].enabled = $('.custom-sg-enabled[data-sg-id="' + sgId + '"][data-visit-type="IPD"]').is(':checked');
        cfg.subgroups_ipd[sgId].title = $(this).val();
        cfg.subgroups_ipd[sgId].target_accountcode = $('.custom-sg-acc[data-sg-id="' + sgId + '"][data-visit-type="IPD"]').val();
        cfg.subgroups_ipd[sgId].effective_start_month = ($('.custom-sg-start-month[data-sg-id="' + sgId + '"][data-visit-type="IPD"]').val() || 'ALL').trim();
        cfg.subgroups_ipd[sgId].effective_end_month = ($('.custom-sg-end-month[data-sg-id="' + sgId + '"][data-visit-type="IPD"]').val() || '').trim();
        cfg.subgroups_ipd[sgId].custom_adp_codes = parseCsv($('.custom-sg-adp[data-sg-id="' + sgId + '"][data-visit-type="IPD"]').val());
        cfg.subgroups_ipd[sgId].custom_icodes = parseCsv($('.custom-sg-icodes[data-sg-id="' + sgId + '"][data-visit-type="IPD"]').val());
    });

    return cfg;
}

function saveCrSettingsData() {
    var newConfig = buildConfigFromUI();

    Swal.fire({
        title: 'กำลังบันทึก...',
        text: 'กำลังอัปเดตการตั้งค่าระบบ CR',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    $.ajax({
        url: 'api_cr_debtor.php?action=save_config',
        method: 'POST',
        data: JSON.stringify(newConfig),
        contentType: 'application/json',
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'บันทึกสำเร็จ',
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    $('#modalCrSubgroupSetting').modal('hide');
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'ข้อผิดพลาด',
                    text: res.message
                });
            }
        },
        error: function() {
            Swal.fire({
                icon: 'error',
                title: 'ข้อผิดพลาด',
                text: 'ไม่สามารถเชื่อมต่อ API ได้'
            });
        }
    });
}

function restoreCrDefaultSettings() {
    Swal.fire({
        title: 'ยืนยันคืนค่าเริ่มต้น?',
        text: 'การตั้งค่ากลุ่มย่อย CR ทั้งหมดจะถูกรีเซ็ตกลับเป็นค่ามาตรฐาน',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'ใช่, คืนค่าเริ่มต้น',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'กำลังคืนค่า...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            $.ajax({
                url: 'api_cr_debtor.php?action=restore_defaults',
                method: 'POST',
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success' && res.config) {
                        currentCrConfig = res.config;
                        populateCrSettingsUI(res.config);
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: 'คืนค่าการตั้งค่าเริ่มต้นเรียบร้อยแล้ว'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'ข้อผิดพลาด',
                            text: res.message
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'ข้อผิดพลาด',
                        text: 'ไม่สามารถเชื่อมต่อ API ได้'
                    });
                }
            });
        }
    });
}

function testCrImpactPreview() {
    var selectedMonth = $('#crEffectiveMonthSelect').val();
    if (selectedMonth === 'ALL') selectedMonth = '';

    Swal.fire({
        title: 'กำลังทดสอบสแกน...',
        text: 'กำลังจำลองผลกระทบการจำแนก CR จากข้อมูลจริงในระบบ HOSxP',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    $.ajax({
        url: 'api_cr_debtor.php',
        method: 'GET',
        data: { action: 'test_preview', month: selectedMonth },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                $('#crTestPreviewBox').show();
                $('#crTestMonthBadge').text('เดือน: ' + (res.month || 'ล่าสุด'));

                var statsHtml = `
                    <div class="col-6 col-md-3">
                      <div class="p-2 border rounded bg-white">
                        <div class="text-muted small">สแกนทั้งหมด</div>
                        <div class="fw-bold text-dark fs-6">${Number(res.total_scanned_cases || 0).toLocaleString()} เคส</div>
                      </div>
                    </div>
                    <div class="col-6 col-md-3">
                      <div class="p-2 border rounded bg-white">
                        <div class="text-muted small">พบตรงเกณฑ์ CR</div>
                        <div class="fw-bold text-primary fs-6">${Number(res.matched_cr_cases || 0).toLocaleString()} เคส</div>
                      </div>
                    </div>
                    <div class="col-6 col-md-3">
                      <div class="p-2 border rounded bg-white">
                        <div class="text-muted small">ภาระหนี้เดิม</div>
                        <div class="fw-bold text-dark fs-6">${Number(res.total_scanned_debit || 0).toLocaleString('en-US', {minimumFractionDigits: 2})} บ.</div>
                      </div>
                    </div>
                    <div class="col-6 col-md-3">
                      <div class="p-2 border rounded bg-white">
                        <div class="text-muted small">ยอดที่จะย้ายไป CR</div>
                        <div class="fw-bold text-success fs-6">${Number(res.matched_cr_debit || 0).toLocaleString('en-US', {minimumFractionDigits: 2})} บ.</div>
                      </div>
                    </div>
                `;
                $('#crTestStatsContainer').html(statsHtml);

                // เลื่อนหน้าจอลงมาที่กล่องแสดงผลการทดสอบ
                var boxEl = document.getElementById('crTestPreviewBox');
                if (boxEl) {
                    boxEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }

                // แจ้งเตือนสรุปผลการทดสอบด้านหน้า
                Swal.fire({
                    icon: 'success',
                    title: 'ทดสอบสแกนสำเร็จ!',
                    html: `
                      <div class="text-start p-2" style="font-size: 14px;">
                        <div class="mb-1">🗓️ <b>เดือนที่สแกน:</b> <span class="badge bg-primary">${res.month || 'ล่าสุด'}</span></div>
                        <div class="mb-1">👥 <b>จำนวนเคสที่สแกน:</b> <b>${Number(res.total_scanned_cases || 0).toLocaleString()}</b> เคส</div>
                        <div class="mb-1">💰 <b>ภาระหนี้เดิมรวม:</b> <b>${Number(res.total_scanned_debit || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</b> บาท</div>
                        <div class="mt-2 p-2 bg-success bg-opacity-10 border border-success rounded text-success fw-bold">
                          ✨ พบตรงเกณฑ์ CR: ${Number(res.matched_cr_cases || 0).toLocaleString()} เคส (มูลค่า ${Number(res.matched_cr_debit || 0).toLocaleString('en-US', {minimumFractionDigits: 2})} บ.)
                        </div>
                      </div>
                    `,
                    confirmButtonText: 'ดูตารางสรุปผล',
                    confirmButtonColor: '#2563eb'
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'ข้อผิดพลาด',
                    text: res.message
                });
            }
        },
        error: function(xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'ข้อผิดพลาด',
                text: 'ไม่สามารถเชื่อมต่อ API ได้ (' + error + ')'
            });
        }
    });
}

function autoDetectHospitalProvince(target) {
    $.ajax({
        url: 'api_cr_debtor.php?action=get_hospital_info',
        method: 'GET',
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success' && res.chwpart) {
                if (!target || target === 'walkin') {
                    $('#cfg_opd_walkin_my_chwpart').val(res.chwpart);
                    $('#cfg_opd_walkin_prov_hint').html(`<span class="text-success fw-bold"><i class='bx bx-check-circle'></i> ตรวจพบ: ${res.hospitalname} จ.${res.province_name} (รหัสจังหวัด ${res.chwpart})</span>`);
                }
                if (!target || target === 'ae') {
                    $('#cfg_opd_ae_my_chwpart').val(res.chwpart);
                    $('#cfg_opd_ae_prov_hint').html(`<span class="text-success fw-bold"><i class='bx bx-check-circle'></i> ตรวจพบ: ${res.hospitalname} จ.${res.province_name} (รหัสจังหวัด ${res.chwpart})</span>`);
                }
                Swal.fire({
                    icon: 'success',
                    title: 'ตรวจพบหน่วยบริการ',
                    html: `<b>${res.hospitalname}</b><br>จังหวัด: <b>${res.province_name} (รหัส ${res.chwpart})</b>`,
                    timer: 2500,
                    showConfirmButton: false
                });
            } else {
                Swal.fire('แจ้งเตือน', 'ไม่พบข้อมูลรหัสจังหวัดในตาราง hospcode ของ HOSxP', 'warning');
            }
        },
        error: function() {
            Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อ API ดึงข้อมูลโรงพยาบาลได้', 'error');
        }
    });
}
</script>

