<!-- Modal: Smart Denial Action Guide -->
<div class="modal fade" id="modalDenialGuide" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header bg-danger text-white py-3">
        <h5 class="modal-title text-white d-flex align-items-center">
          <i class="bx bx-bulb me-2 fs-4"></i> ศูนย์แนะนำแนวทางแก้ไขรหัสปฏิเสธ (Smart Denial Action Guide)
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        
        <!-- Header Badge with Denial Code -->
        <div class="d-flex align-items-center justify-content-between p-3 mb-3 rounded-3 bg-lighter border">
          <div>
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-danger fs-6 px-3 py-2" id="guide_code_badge">D179</span>
              <span class="badge" id="guide_severity_badge">แก้ไขส่งใหม่ได้ (RETRY)</span>
            </div>
            <h6 class="mt-2 mb-0 fw-bold text-dark fs-6" id="guide_desc_th">
              ระบุรหัสอุปกรณ์กายอุปกรณ์ไม่ถูกต้อง หรือไม่มีในบัญชีรายการที่ สปสช. กำหนด
            </h6>
          </div>
          <div class="text-end d-none d-sm-block">
            <i class="bx bx-shield-x fs-1 text-danger opacity-50"></i>
          </div>
        </div>

        <!-- Section 1: สาเหตุที่พบบ่อย (Root Causes) -->
        <div class="card border mb-3 shadow-none">
          <div class="card-header py-2 px-3 bg-light border-bottom">
            <span class="fw-bold text-dark small"><i class="bx bx-search-alt me-1 text-warning"></i> สาเหตุที่พบบ่อย (Root Causes)</span>
          </div>
          <div class="card-body p-3 text-dark small" id="guide_common_causes">
            -
          </div>
        </div>

        <!-- Section 2: ขั้นตอนการแก้ไขเพื่อส่งเบิกใหม่ (Actionable Resolution Guide) -->
        <div class="card border border-success-subtle mb-3 shadow-none">
          <div class="card-header py-2 px-3 bg-label-success border-bottom">
            <span class="fw-bold text-success small"><i class="bx bx-check-shield me-1"></i> ขั้นตอนการแก้ไขเพื่อส่งเบิกซ้ำ (Resolution Steps)</span>
          </div>
          <div class="card-body p-3 text-dark" id="guide_action_steps" style="line-height: 1.8;">
            -
          </div>
        </div>

        <!-- Section 3: รายการเอกสารที่ต้องแนบเพิ่ม (Checklist) -->
        <div class="p-3 mb-3 rounded-3 bg-light border">
          <span class="fw-bold text-dark small d-block mb-2"><i class="bx bx-paperclip me-1 text-primary"></i> รายการเอกสารที่มักต้องแนบเพิ่มเติมใน e-Claim:</span>
          <ul class="mb-0 small text-muted ps-3">
            <li>ใบสั่งอุปกรณ์การแพทย์ (Medical Order Sheet) ลงนามโดยแพทย์หรือนักกายภาพบำบัด</li>
            <li>แบบประเมินความจำเป็นทางเวชกรรมฟื้นฟู (Functional Assessment Form)</li>
            <li>สำเนาบัตรประจำตัวคนพิการ (กรณีเบิกในหมวดคนพิการ/กองทุนฟื้นฟู)</li>
            <li>ภาพถ่ายผู้ป่วยพร้อมอุปกรณ์ที่ส่งมอบ (กรณีอุปกรณ์ราคาสูง เช่น รถนั่งคนพิการเฉพาะราย)</li>
          </ul>
        </div>

        <input type="hidden" id="guide_target_claim_id" value="">

      </div>
      <div class="modal-footer py-2 justify-content-between">
        <div>
          <button type="button" class="btn btn-sm btn-success" id="btnGuideMarkResubmitted">
            <i class="bx bx-check-circle me-1"></i> บันทึกว่าส่งเบิกซ้ำแล้ว
          </button>
        </div>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
      </div>
    </div>
  </div>
</div>
