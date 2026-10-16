<?php
/**
 * Rehab & Medical Device Compensation Tracker Plugin
 * Tab 5: Department & Clinic Summary Report (รายงานแยกตามกลุ่มงาน/แผนก)
 * eDebtor Hospital System (eDHS) - Phonsai Hospital
 */
?>
<!-- ========================================== -->
<!-- TAB 5: DEPARTMENT & CLINIC REPORT          -->
<!-- ========================================== -->
<div class="tab-pane fade" id="tab-department" role="tabpanel">

  <!-- Header Actions Card -->
  <div class="card border shadow-sm mb-4">
    <div class="card-header py-3 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom bg-light">
      <div>
        <h6 class="fw-bold text-dark mb-0">
          <i class="bx bx-buildings me-2 text-info fs-5"></i> รายงานสรุปการสั่งใช้และเบิกชดเชยอุปกรณ์ แยกตามกลุ่มงาน / แผนก
        </h6>
        <small class="text-dark fw-bold">จำแนกตามจุดบริการที่สั่งจ่ายอุปกรณ์ (Order Department: กายภาพบำบัด, ทันตกรรม, แพทย์แผนไทย, OPD, ER, IPD) • โรงพยาบาลโพนทราย</small>
      </div>
      <div class="d-flex gap-2 align-items-center">
        <button type="button" class="btn btn-sm btn-success shadow-sm" id="btnExportDeptExcel">
          <i class="bx bx-download me-1"></i> ส่งออก Excel รายงานแยกกลุ่มงาน
        </button>
      </div>
    </div>

    <!-- Row 1: KPI Metric Cards by Department (6 Groups) -->
    <div class="p-3 border-bottom bg-white">
      <div class="row g-3" id="dept_kpi_cards_container">
        <!-- กายภาพบำบัด -->
        <div class="col-6 col-md-4 col-xl-2">
          <div class="card border p-2 h-100 shadow-none bg-label-primary rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="small fw-bold text-dark">กายภาพบำบัด</span>
              <i class="bx bx-accessibility text-primary fs-4"></i>
            </div>
            <h5 class="fw-bolder text-primary mb-0" id="kpi_dept_rehab_amt">฿0</h5>
            <small class="text-dark fw-bold"><span id="kpi_dept_rehab_cases">0</span> ครั้ง (<span id="kpi_dept_rehab_comp">฿0</span>)</small>
          </div>
        </div>

        <!-- ผู้ป่วยนอก OPD -->
        <div class="col-6 col-md-4 col-xl-2">
          <div class="card border p-2 h-100 shadow-none bg-label-success rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="small fw-bold text-dark">ผู้ป่วยนอก (OPD)</span>
              <i class="bx bx-clinic text-success fs-4"></i>
            </div>
            <h5 class="fw-bolder text-success mb-0" id="kpi_dept_opd_amt">฿0</h5>
            <small class="text-dark fw-bold"><span id="kpi_dept_opd_cases">0</span> ครั้ง (<span id="kpi_dept_opd_comp">฿0</span>)</small>
          </div>
        </div>

        <!-- ทันตกรรม -->
        <div class="col-6 col-md-4 col-xl-2">
          <div class="card border p-2 h-100 shadow-none bg-label-info rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="small fw-bold text-dark">ทันตกรรม</span>
              <i class="bx bx-smile text-info fs-4"></i>
            </div>
            <h5 class="fw-bolder text-info mb-0" id="kpi_dept_dental_amt">฿0</h5>
            <small class="text-dark fw-bold"><span id="kpi_dept_dental_cases">0</span> ครั้ง (<span id="kpi_dept_dental_comp">฿0</span>)</small>
          </div>
        </div>

        <!-- อุบัติเหตุและฉุกเฉิน ER -->
        <div class="col-6 col-md-4 col-xl-2">
          <div class="card border p-2 h-100 shadow-none bg-label-danger rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="small fw-bold text-dark">ฉุกเฉิน (ER)</span>
              <i class="bx bx-plus-medical text-danger fs-4"></i>
            </div>
            <h5 class="fw-bolder text-danger mb-0" id="kpi_dept_er_amt">฿0</h5>
            <small class="text-dark fw-bold"><span id="kpi_dept_er_cases">0</span> ครั้ง (<span id="kpi_dept_er_comp">฿0</span>)</small>
          </div>
        </div>

        <!-- ผู้ป่วยใน IPD -->
        <div class="col-6 col-md-4 col-xl-2">
          <div class="card border p-2 h-100 shadow-none bg-label-warning rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="small fw-bold text-dark">ผู้ป่วยใน (IPD)</span>
              <i class="bx bx-bed text-warning fs-4"></i>
            </div>
            <h5 class="fw-bolder text-dark mb-0" id="kpi_dept_ipd_amt">฿0</h5>
            <small class="text-dark fw-bold"><span id="kpi_dept_ipd_cases">0</span> ครั้ง (<span id="kpi_dept_ipd_comp">฿0</span>)</small>
          </div>
        </div>

        <!-- แพทย์แผนไทย -->
        <div class="col-6 col-md-4 col-xl-2">
          <div class="card border p-2 h-100 shadow-none bg-label-secondary rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <span class="small fw-bold text-dark">แพทย์แผนไทย</span>
              <i class="bx bx-spa text-secondary fs-4"></i>
            </div>
            <h5 class="fw-bolder text-dark mb-0" id="kpi_dept_ttm_amt">฿0</h5>
            <small class="text-dark fw-bold"><span id="kpi_dept_ttm_cases">0</span> ครั้ง (<span id="kpi_dept_ttm_comp">฿0</span>)</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Row 2: Charts Row (Donut Share & Stacked Bar Comparison) -->
    <div class="p-3 border-bottom bg-light">
      <div class="row g-3">
        <!-- Chart 1: Donut Chart -->
        <div class="col-lg-5">
          <div class="card border p-3 bg-white shadow-none h-100 rounded-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="fw-bold text-dark mb-0">
                <i class="bx bx-pie-chart-alt me-1 text-primary"></i> สัดส่วนมูลค่าเรียกเก็บแยกตามกลุ่มงาน (% Share)
              </h6>
            </div>
            <div id="chart_dept_donut" style="min-height: 280px;"></div>
          </div>
        </div>

        <!-- Chart 2: Clustered Bar Chart -->
        <div class="col-lg-7">
          <div class="card border p-3 bg-white shadow-none h-100 rounded-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="fw-bold text-dark mb-0">
                <i class="bx bx-bar-chart-alt me-1 text-success"></i> เปรียบเทียบยอดเรียกเก็บ vs ชดเชยจริง vs ค้างรับ
              </h6>
              <span class="badge bg-label-primary fw-bold" style="font-size:0.75rem;">หน่วย: บาท</span>
            </div>
            <div id="chart_dept_bar" style="min-height: 280px;"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 1: Summary Table by 6 Department Groups -->
    <div class="p-3 border-bottom">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="fw-bold text-primary mb-0">
          <i class="bx bx-table me-1"></i> 1. ตารางสรุปภาพรวมตามกลุ่มงาน / จุดบริการสั่งจ่าย (Department Summary Table)
        </h6>
        <span class="badge bg-dark rounded-pill px-2 py-1">จัดอันดับตามมูลค่าเรียกเก็บ</span>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table table-sm table-bordered table-hover mb-0" id="tableDeptGroups">
          <thead class="table-info text-center">
            <tr>
              <th style="width: 5%;">ลำดับ</th>
              <th style="width: 25%;" class="text-start">กลุ่มงาน / จุดบริการ (Department Group)</th>
              <th style="width: 10%;" class="text-end">ผู้ป่วย (คน)</th>
              <th style="width: 10%;" class="text-end">จำนวนครั้ง</th>
              <th style="width: 10%;" class="text-end">จำนวนชิ้น</th>
              <th style="width: 12%;" class="text-end">ยอดเรียกเก็บ (บาท)</th>
              <th style="width: 12%;" class="text-end">ชดเชยแล้วจริง (บาท)</th>
              <th style="width: 12%;" class="text-end">ค้างชดเชย (บาท)</th>
              <th style="width: 8%;" class="text-end">% สำเร็จ</th>
              <th style="width: 8%;" class="text-center">เจาะลึก</th>
            </tr>
          </thead>
          <tbody id="tbodyDeptGroups">
            <tr><td colspan="10" class="text-center py-3 text-muted">กำลังประมวลผลข้อมูลกลุ่มงาน...</td></tr>
          </tbody>
          <tfoot class="table-light fw-bold" id="tfootDeptGroups"></tfoot>
        </table>
      </div>
    </div>

    <!-- Section 2: Detailed Breakdown of Top Equipment by Department -->
    <div class="p-3">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="fw-bold text-dark mb-0">
          <i class="bx bx-list-check me-1 text-primary"></i> 2. รายละเอียดอุปกรณ์ยอดนิยมในแต่ละกลุ่มงาน (Top Equipment by Department)
        </h6>
        <div class="d-flex align-items-center gap-2">
          <label class="small fw-bold text-dark mb-0">กรองเฉพาะกลุ่มงาน:</label>
          <select class="form-select form-select-sm" id="sel_dept_breakdown_filter" style="width: 220px;">
            <option value="ALL">แสดงทุกกลุ่มงาน</option>
            <option value="กายภาพบำบัด">กายภาพบำบัด</option>
            <option value="ผู้ป่วยนอก (OPD)">ผู้ป่วยนอก (OPD)</option>
            <option value="ทันตกรรม">ทันตกรรม</option>
            <option value="อุบัติเหตุและฉุกเฉิน (ER)">อุบัติเหตุและฉุกเฉิน (ER)</option>
            <option value="ผู้ป่วยใน (IPD)">ผู้ป่วยใน (IPD)</option>
            <option value="แพทย์แผนไทย">แพทย์แผนไทย</option>
          </select>
        </div>
      </div>
      <div class="table-responsive text-nowrap" style="max-height: 420px;">
        <table class="table table-sm table-hover table-bordered mb-0" id="tableDeptItems">
          <thead class="table-light text-center">
            <tr>
              <th style="width: 5%;">ลำดับ</th>
              <th style="width: 18%;" class="text-start">กลุ่มงาน</th>
              <th style="width: 10%;">รหัสอุปกรณ์ (ADP)</th>
              <th style="width: 27%;" class="text-start">ชื่ออุปกรณ์ทางการแพทย์</th>
              <th style="width: 8%;" class="text-end">ผู้ป่วย (คน)</th>
              <th style="width: 8%;" class="text-end">จำนวนครั้ง</th>
              <th style="width: 8%;" class="text-end">จำนวนชิ้น</th>
              <th style="width: 10%;" class="text-end">ยอดเรียกเก็บ (บาท)</th>
              <th style="width: 10%;" class="text-end">ชดเชยจริง (บาท)</th>
              <th style="width: 6%;" class="text-end">% สำเร็จ</th>
            </tr>
          </thead>
          <tbody id="tbodyDeptItems">
            <tr><td colspan="10" class="text-center py-3 text-muted">กำลังโหลดรายละเอียดอุปกรณ์ตามกลุ่มงาน...</td></tr>
          </tbody>
        </table>
      </div>
    </div>

  </div>

</div>
