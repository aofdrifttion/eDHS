<!-- Modal: Patients Drill-Down List for Medical Device -->
<div class="modal fade" id="modalDevicePatients" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
      
      <!-- Modal Header -->
      <div class="modal-header bg-primary text-white py-3 px-4 d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
          <div class="avatar avatar-md bg-white text-primary rounded-circle me-3 d-flex align-items-center justify-content-center shadow-sm">
            <i class="bx bx-accessibility fs-3"></i>
          </div>
          <div>
            <h5 class="modal-title text-white fw-bold mb-0 d-flex align-items-center flex-wrap gap-2">
              <span>รายชื่อผู้ป่วยที่ได้รับอุปกรณ์</span>
              <span class="badge bg-white text-primary fw-bold fs-6" id="mdp_device_badge">-</span>
              <span id="mdp_device_name" class="fw-bold text-white text-truncate" style="max-width: 450px;">-</span>
            </h5>
            <small class="text-white-50 mt-1 d-block" id="mdp_filter_subtitle">
              <i class="bx bx-calendar-event me-1"></i> กรองตามช่วงเวลาและสิทธิการรักษาปัจจุบัน
            </small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Hidden Tracking Fields -->
      <input type="hidden" id="mdp_target_device_code" value="">
      <input type="hidden" id="mdp_target_device_name" value="">

      <!-- Modal Body -->
      <div class="modal-body p-4 bg-light">

        <!-- Summary KPI Row -->
        <div class="row g-2 mb-3">
          <div class="col-6 col-md-4 col-lg-2">
            <div class="card p-2 text-center border bg-white shadow-sm h-100">
              <small class="text-dark fw-bold d-block">ผู้ป่วยทั้งหมด</small>
              <h5 class="mb-0 fw-bold text-dark mt-1" id="mdp_kpi_patients">0 คน</h5>
            </div>
          </div>
          <div class="col-6 col-md-4 col-lg-2">
            <div class="card p-2 text-center border bg-white shadow-sm h-100">
              <small class="text-dark fw-bold d-block">จำนวนที่จ่าย</small>
              <h5 class="mb-0 fw-bold text-dark mt-1" id="mdp_kpi_qty">0 ชิ้น</h5>
            </div>
          </div>
          <div class="col-6 col-md-4 col-lg-3">
            <div class="card p-2 text-center border bg-white shadow-sm h-100">
              <small class="text-dark fw-bold d-block">มูลค่าเรียกเก็บรวม</small>
              <h5 class="mb-0 fw-bold text-primary mt-1" id="mdp_kpi_claim">฿0.00</h5>
            </div>
          </div>
          <div class="col-6 col-md-4 col-lg-3">
            <div class="card p-2 text-center border bg-white shadow-sm h-100">
              <small class="text-dark fw-bold d-block">ชดเชยแล้วจริง</small>
              <h5 class="mb-0 fw-bold text-success mt-1" id="mdp_kpi_comp">฿0.00</h5>
            </div>
          </div>
          <div class="col-12 col-md-4 col-lg-2">
            <div class="card p-2 text-center border bg-white shadow-sm h-100">
              <small class="text-dark fw-bold d-block">ส่วนต่างค้างรับ</small>
              <h5 class="mb-0 fw-bold text-danger mt-1" id="mdp_kpi_diff">฿0.00</h5>
            </div>
          </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="card border p-3 mb-3 bg-white shadow-sm">
          <div class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0"><i class="bx bx-search text-dark"></i></span>
                <input type="text" class="form-control border-start-0 text-dark fw-semibold" id="mdp_search_input" placeholder="พิมพ์ค้นหา HN, ชื่อ-สกุล, CID, หรือ VN...">
              </div>
            </div>
            <div class="col-6 col-md-3">
              <select class="form-select form-select-sm text-dark fw-semibold" id="mdp_status_filter">
                <option value="ALL">ทุกสถานะการเบิกจ่าย</option>
                <option value="PAID">เคลมผ่าน / ชดเชยแล้ว</option>
                <option value="WAIT_STM">รอผล Statement</option>
                <option value="REJECTED">ปฏิเสธ / เคลมไม่ผ่าน</option>
                <option value="UNCLAIMED">รอผลตรวจสอบ</option>
              </select>
            </div>
            <div class="col-6 col-md-4 text-end d-flex gap-2 justify-content-end">
              <button type="button" class="btn btn-sm btn-outline-success" id="btnExportDevicePatientsExcel" title="ส่งออกรายชื่อผู้ป่วยอุปกรณ์นี้เป็น Excel">
                <i class="bx bx-download me-1"></i> Excel
              </button>
              <button type="button" class="btn btn-sm btn-outline-primary" id="btnJumpToClaimsTab" title="เปิดดูรายการในแท็บสถานะการเบิก (Tab 2)">
                <i class="bx bx-link-external me-1"></i> ดูในแท็บหลัก
              </button>
            </div>
          </div>
        </div>

        <!-- Table Container -->
        <div class="card border shadow-sm bg-white">
          <div class="table-responsive text-nowrap" style="max-height: 460px;">
            <table class="table table-hover table-striped table-bordered mb-0" id="tableDevicePatients">
              <thead class="table-light sticky-top">
                <tr style="background-color: #f1f5f9; border-bottom: 2px solid #cbd5e1;">
                  <th style="width: 50px;" class="text-center text-dark fw-bold">#</th>
                  <th style="width: 100px;" class="text-center text-dark fw-bold">วันที่รับบริการ</th>
                  <th style="width: 90px;" class="text-center text-dark fw-bold">HN</th>
                  <th style="min-width: 160px;" class="text-dark fw-bold">ชื่อ - สกุล ผู้ป่วย</th>
                  <th style="width: 130px;" class="text-center text-dark fw-bold">เลขบัตร ปชช. (CID)</th>
                  <th style="min-width: 150px;" class="text-dark fw-bold">สิทธิการรักษา</th>
                  <th style="width: 60px;" class="text-end text-dark fw-bold">จำนวน</th>
                  <th style="width: 110px;" class="text-end text-dark fw-bold">ยอดเรียกเก็บ</th>
                  <th style="width: 110px;" class="text-end text-dark fw-bold">ชดเชยแล้ว</th>
                  <th style="width: 100px;" class="text-end text-dark fw-bold">ส่วนต่าง</th>
                  <th style="width: 110px;" class="text-center text-dark fw-bold">สถานะ</th>
                  <th style="width: 60px;" class="text-center text-dark fw-bold">จัดการ</th>
                </tr>
              </thead>
              <tbody id="tbodyDevicePatients">
                <tr>
                  <td colspan="12" class="text-center py-5 text-dark fw-bold">
                    <div class="spinner-border text-primary me-2" role="status"></div> กำลังโหลดรายชื่อผู้ป่วย...
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="modal-footer py-2 px-4 bg-white d-flex justify-content-between align-items-center border-top">
        <span class="text-dark fw-bold small" id="mdp_count_label">แสดง 0 รายการ</span>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
          <i class="bx bx-x me-1"></i> ปิดหน้าต่าง
        </button>
      </div>

    </div>
  </div>
</div>
