<!-- Modal: Patient & Claim Drill-Down Details -->
<div class="modal fade" id="modalClaimDetail" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header bg-primary text-white py-3">
        <h5 class="modal-title text-white d-flex align-items-center" id="claimDetailModalTitle">
          <i class="bx bx-file me-2 fs-4"></i> รายละเอียดการเบิกชดเชยอุปกรณ์รายบุคคล
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <!-- Patient Profile Header -->
        <div class="d-flex align-items-center p-3 mb-4 rounded-3 bg-light border">
          <div class="avatar avatar-md me-3 bg-label-primary rounded-circle d-flex align-items-center justify-content-center">
            <i class="bx bx-user fs-3 text-primary"></i>
          </div>
          <div class="flex-grow-1">
            <h6 class="mb-0 fw-bold fs-5 text-dark" id="modal_ptname">-</h6>
            <div class="text-dark fw-semibold small mt-1">
              <span>HN: <strong id="modal_hn" class="text-primary">-</strong></span>
              <span class="mx-2">•</span>
              <span>อายุ: <strong id="modal_age" class="text-primary">-</strong> ปี</span>
              <span class="mx-2">•</span>
              <span>CID: <span id="modal_cid" class="text-dark fw-bold">-</span></span>
              <span class="mx-2">•</span>
              <span>VN: <span id="modal_vn" class="text-dark fw-bold">-</span></span>
            </div>
          </div>
          <div id="modal_status_badge">
            <span class="badge bg-secondary">-</span>
          </div>
        </div>

        <!-- 2-Column Info Grid -->
        <div class="row g-3 mb-3">
          <!-- Col 1: ข้อมูลการรับบริการ & สิทธิ -->
          <div class="col-md-6">
            <div class="card h-100 border">
              <div class="card-header py-2 px-3 bg-lighter border-bottom">
                <small class="fw-bold text-uppercase text-dark"><i class="bx bx-calendar-event me-1 text-primary"></i> ข้อมูลการรับบริการ & สิทธิ</small>
              </div>
              <div class="card-body p-3">
                <table class="table table-sm table-borderless mb-0">
                  <tr>
                    <td class="text-dark fw-bold py-1" style="width: 40%;">วันที่/เวลารับบริการ:</td>
                    <td class="fw-bold text-dark py-1">
                      <span id="modal_vstdate">-</span> 
                      <span class="badge bg-label-primary ms-1" id="modal_vsttime">-</span>
                    </td>
                  </tr>
                  <tr>
                    <td class="text-dark fw-bold py-1">สิทธิการรักษา:</td>
                    <td class="py-1 text-dark fw-bold" id="modal_pttype_name">-</td>
                  </tr>
                  <tr>
                    <td class="text-dark fw-bold py-1">กองทุนหลัก:</td>
                    <td class="py-1 text-dark fw-bold" id="modal_fund_type">-</td>
                  </tr>
                  <tr>
                    <td class="text-dark fw-bold py-1">แพทย์ผู้สั่ง:</td>
                    <td class="py-1 text-dark fw-bold" id="modal_doctor">-</td>
                  </tr>
                  <tr>
                    <td class="text-dark fw-bold py-1">กลุ่มงาน/จุดสั่ง:</td>
                    <td class="py-1 text-dark fw-bold">
                      <span class="badge bg-label-primary fw-bold" id="modal_dept_group">-</span>
                      <small class="text-dark ms-1 fw-bold" id="modal_dept_name"></small>
                    </td>
                  </tr>
                  <tr>
                    <td class="text-dark fw-bold py-1">การวินิจฉัย (ICD-10):</td>
                    <td class="py-1 text-dark">
                      <span class="badge bg-label-dark fw-bold" id="modal_diag">-</span>
                      <div class="fw-bold text-dark small mt-1" id="modal_diag_name">-</div>
                    </td>
                  </tr>
                  <tr>
                    <td class="text-dark fw-bold py-1">ที่อยู่ติดต่อ:</td>
                    <td class="py-1 text-dark small fw-semibold" id="modal_informaddr">-</td>
                  </tr>
                </table>
              </div>
            </div>
          </div>

          <!-- Col 2: ข้อมูลอุปกรณ์ & ยอดเงิน -->
          <div class="col-md-6">
            <div class="card h-100 border">
              <div class="card-header py-2 px-3 bg-lighter border-bottom">
                <small class="fw-bold text-uppercase text-dark"><i class="bx bx-purchase-tag-alt me-1 text-primary"></i> ข้อมูลอุปกรณ์ & การเงิน</small>
              </div>
              <div class="card-body p-3">
                <table class="table table-sm table-borderless mb-0">
                  <tr>
                    <td class="text-dark fw-bold py-1" style="width: 40%;">รหัสอุปกรณ์ (ADP):</td>
                    <td class="fw-bold text-primary py-1" id="modal_device_code">-</td>
                  </tr>
                  <tr>
                    <td class="text-dark fw-bold py-1">ชื่ออุปกรณ์:</td>
                    <td class="fw-bold text-dark py-1" id="modal_device_name">-</td>
                  </tr>
                  <tr>
                    <td class="text-dark fw-bold py-1">จำนวน x ราคา:</td>
                    <td class="py-1 text-dark fw-bold" id="modal_qty_price">-</td>
                  </tr>
                  <tr>
                    <td class="text-dark fw-bold py-1">ยอดเรียกเก็บ:</td>
                    <td class="fw-bold text-dark py-1" id="modal_claim_amount">-</td>
                  </tr>
                  <tr>
                    <td class="text-dark fw-bold py-1">ชดเชยแล้ว (STM):</td>
                    <td class="fw-bold text-success py-1" id="modal_compensated_amount">-</td>
                  </tr>
                  <tr>
                    <td class="text-dark fw-bold py-1">ส่วนต่างค้างรับ:</td>
                    <td class="fw-bold text-danger py-1" id="modal_diff_amount">-</td>
                  </tr>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Denial Section (Shown if rejected) -->
        <div id="modal_denial_section" class="p-3 mb-3 rounded-3 border border-danger bg-label-danger" style="display: none;">
          <div class="d-flex align-items-center mb-2">
            <i class="bx bx-error-circle fs-4 me-2 text-danger"></i>
            <h6 class="mb-0 fw-bold text-danger">รายการถูกปฏิเสธการจ่ายชดเชย (Rejection Details)</h6>
          </div>
          <div class="small mb-2 d-flex align-items-center flex-wrap gap-1">
            <span class="badge bg-warning text-dark fw-bold" id="modal_denial_type_badge">Corrective</span>
            <strong class="text-dark ms-1">รหัส:</strong> <span class="badge bg-danger" id="modal_denial_code">-</span>
            <span class="ms-2 fw-bold text-dark" id="modal_denial_desc">-</span>
            <span class="ms-auto small fw-bold text-dark" id="modal_error_codes_wrap"></span>
          </div>
          <div class="p-2 rounded bg-white border border-danger-subtle text-dark fw-semibold small" id="modal_denial_action">
            -
          </div>
          <div class="mt-2 text-end">
            <button type="button" class="btn btn-sm btn-outline-danger" id="btnOpenDenialGuideFromModal">
              <i class="bx bx-bulb me-1"></i> ดูคู่มือขั้นตอนแก้ไขอย่างละเอียด
            </button>
          </div>
        </div>

        <!-- Statement & Receipt Tracking References -->
        <div class="p-3 mb-3 rounded-3 bg-light border">
          <small class="fw-bold text-uppercase text-dark d-block mb-2"><i class="bx bx-receipt me-1 text-primary"></i> ข้อมูลอ้างอิง Statement & ใบเสร็จรับเงิน (Debtor Rights)</small>
          <div class="row g-2 small">
            <div class="col-sm-6">
              <span class="text-dark fw-bold">เลขที่รอบเบิก (REP No.):</span> <strong id="modal_rep_no" class="text-primary">-</strong>
            </div>
            <div class="col-sm-6">
              <span class="text-dark fw-bold">เลขที่ Statement (Doc No.):</span> <strong id="modal_stm_docno" class="text-primary">-</strong>
            </div>
            <div class="col-sm-6 mt-2 pt-1 border-top">
              <span class="text-dark fw-bold">เลขที่ใบเสร็จรับเงิน (Bill / Mobile):</span> 
              <span class="badge bg-label-info text-dark fw-bold fs-6 ms-1" id="modal_receipt_no_badge">
                <i class="bx bx-receipt me-1 text-info"></i><span id="modal_receipt_no">-</span>
              </span>
            </div>
            <div class="col-sm-6 mt-2 pt-1 border-top">
              <span class="text-dark fw-bold">วันที่ออกใบเสร็จ / แหล่งข้อมูล:</span> <span id="modal_receipt_date_source" class="text-dark fw-bold">-</span>
            </div>
          </div>
        </div>

        <!-- Action / Notes Section -->
        <div class="card border">
          <div class="card-header py-2 px-3 bg-lighter border-bottom">
            <small class="fw-bold text-uppercase text-dark"><i class="bx bx-edit me-1 text-primary"></i> การติดตามงาน & หมายเหตุเจ้าหน้าที่</small>
          </div>
          <div class="card-body p-3">
            <input type="hidden" id="modal_claim_id" value="">
            <div class="form-check form-switch mb-2">
              <input class="form-check-input" type="checkbox" id="modal_is_resubmitted">
              <label class="form-check-label fw-bold text-primary" for="modal_is_resubmitted">
                ทำเครื่องหมายว่า "ส่งเบิกซ้ำแล้ว" (Mark as Resubmitted)
              </label>
            </div>
            <div class="mb-2">
              <label for="modal_officer_note" class="form-label small text-dark fw-bold mb-1">บันทึกช่วยจำ / การประสานงาน:</label>
              <textarea class="form-control form-control-sm text-dark fw-semibold" id="modal_officer_note" rows="2" placeholder="เช่น แก้ไขรหัสและแนบใบสั่งอุปกรณ์ส่ง e-Claim รอบใหม่แล้ว เมื่อ 10/10/2026..."></textarea>
            </div>
            <div class="text-end">
              <button type="button" class="btn btn-sm btn-primary" id="btnSaveModalNote">
                <i class="bx bx-save me-1"></i> บันทึกข้อมูล
              </button>
            </div>
          </div>
        </div>

      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
      </div>
    </div>
  </div>
</div>
