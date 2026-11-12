<!-- ==================================================================================== -->
<!-- 📊 eDebtor Hospital System (eDHS) - Modal Debtor Trend Analytics Component           -->
<!-- ==================================================================================== -->

<style>
  /* กำหนดให้ Modal Trend แสดงผลในลำดับชั้นที่เหมาะสม ไม่แทรกแซง Modal อื่น */
  #modalDebtorTrend,
  #modalDebtorTrend.modal {
    z-index: 1100 !important;
  }
  #modalDebtorTrend .modal-dialog {
    z-index: 1105 !important;
  }

  /* Modal Trend Styling */
  #modalDebtorTrend .modal-content {
    border-radius: 18px;
    border: none;
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.18);
    overflow: hidden;
  }
  #modalDebtorTrend .modal-header {
    background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
    color: #ffffff;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding: 18px 24px;
  }
  #modalDebtorTrend .modal-title {
    color: #ffffff;
    font-weight: 700;
    font-size: 1.15rem;
    display: flex;
    align-items: center;
  }
  #modalDebtorTrend .btn-close-white {
    filter: invert(1) grayscale(100%) brightness(200%);
  }

  /* Control Bar in Header */
  .trend-header-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
  }
  .trend-select-control {
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.25);
    color: #ffffff;
    border-radius: 8px;
    padding: 5px 12px;
    font-size: 0.85rem;
    font-weight: 500;
    outline: none;
  }
  .trend-select-control option {
    background: #1e293b;
    color: #ffffff;
  }
  .trend-range-btn-group .btn {
    padding: 5px 12px;
    font-size: 0.82rem;
    font-weight: 600;
    border-radius: 8px;
  }
  .trend-range-btn-group .btn.active {
    background: #6366f1 !important;
    color: #ffffff !important;
    border-color: #6366f1 !important;
  }

  /* KPI Stat Cards */
  .trend-kpi-card {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    padding: 16px 20px;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
    position: relative;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .trend-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
  }
  .trend-kpi-title {
    font-size: 0.82rem;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .trend-kpi-val {
    font-size: 1.55rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
  }
  .trend-kpi-sub {
    font-size: 0.78rem;
    color: #94a3b8;
    margin-top: 4px;
  }
  .trend-badge-up {
    background: #fee2e2;
    color: #dc2626;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
  }
  .trend-badge-down {
    background: #dcfce7;
    color: #16a34a;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.75rem;
  }

  /* Main Chart Container */
  .trend-chart-box {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(15, 23, 42, 0.04);
    margin-bottom: 24px;
  }
  .trend-chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f5f9;
  }

  /* Nav Tabs */
  .trend-nav-tabs {
    border-bottom: 2px solid #e2e8f0;
    gap: 10px;
  }
  .trend-nav-tabs .nav-link {
    border: none;
    border-bottom: 3px solid transparent;
    color: #64748b;
    font-weight: 600;
    font-size: 0.92rem;
    padding: 10px 18px;
    border-radius: 0;
    background: transparent;
    transition: all 0.2s ease;
  }
  .trend-nav-tabs .nav-link:hover {
    color: #4f46e5;
  }
  .trend-nav-tabs .nav-link.active {
    color: #4f46e5;
    border-bottom-color: #4f46e5;
    background: transparent;
  }

  /* Drill-down Tables */
  .trend-table-box {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    overflow: hidden;
  }
  .trend-table-box table {
    margin-bottom: 0;
  }
  .trend-table-box th {
    background: #f8fafc;
    color: #334155;
    font-weight: 700;
    font-size: 0.83rem;
    padding: 10px 14px;
    border-color: #e2e8f0;
  }
  .trend-table-box td {
    font-size: 0.84rem;
    padding: 10px 14px;
    color: #1e293b;
    border-color: #f1f5f9;
    vertical-align: middle;
  }

  /* Loading Overlay */
  .trend-loading-overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(255, 255, 255, 0.82);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    backdrop-filter: blur(3px);
  }

  /* Patient Detail Drawer / In-modal View */
  #trendPatientDetailDrawer {
    display: none;
    background: #f8fafc;
    border-radius: 14px;
    border: 1px solid #cbd5e1;
    padding: 18px;
    margin-top: 20px;
    animation: fadeInSlide 0.25s ease;
  }
  @keyframes fadeInSlide {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
  }
</style>

<!-- Modal Main Wrapper -->
<div class="modal fade" id="modalDebtorTrend" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 92%;">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <div class="d-flex align-items-center">
          <div class="me-3 d-flex align-items-center justify-content-center bg-primary rounded-3 text-white" style="width: 44px; height: 44px; background: rgba(99, 102, 241, 0.25) !important;">
            <i class='bx bx-line-chart fs-3'></i>
          </div>
          <div>
            <h5 class="modal-title mb-0">รายงานวิเคราะห์แนวโน้มลูกหนี้ย้อนหลังรายผังบัญชี</h5>
            <div class="small text-white-50 mt-1 d-flex align-items-center gap-2">
              <span id="trendHeaderAccBadge" class="badge bg-primary">OPD</span>
              <span id="trendHeaderAccTitle" class="fw-bold text-white">1102050101.203 - ลูกหนี้ค่ารักษา UC - OP นอก CUP (ในจังหวัดสังกัด สธ.)</span>
            </div>
          </div>
        </div>

        <div class="trend-header-controls ms-auto">
          <!-- ประเภท OPD / IPD -->
          <select id="trendSelectType" class="trend-select-control" onchange="onTrendTypeChange(this.value)">
            <option value="OPD">ผู้ป่วยนอก (OPD)</option>
            <option value="IPD">ผู้ป่วยใน (IPD)</option>
          </select>

          <!-- เลือกผังบัญชี -->
          <select id="trendSelectAccount" class="trend-select-control" style="max-width: 280px;" onchange="onTrendAccountChange(this.value)">
            <option value="1102050101.203">1102050101.203 - UC - OP นอก CUP (ในจังหวัด)</option>
          </select>

          <!-- ปุ่มลัดเลือกช่วงเวลา -->
          <div class="btn-group trend-range-btn-group" role="group">
            <button type="button" class="btn btn-outline-light" data-range="6m" onclick="onTrendRangeChange('6m')">6 เดือน</button>
            <button type="button" class="btn btn-outline-light" data-range="fiscal" onclick="onTrendRangeChange('fiscal')">ปีงบประมาณ</button>
            <button type="button" class="btn btn-outline-light active" data-range="12m" onclick="onTrendRangeChange('12m')">12 เดือน</button>
          </div>

          <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>

      <!-- Modal Body -->
      <div class="modal-body p-4 position-relative" style="background: #f8fafc; min-height: 550px;">
        
        <!-- Loading Overlay -->
        <div id="trendLoadingOverlay" class="trend-loading-overlay" style="display: none;">
          <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
          <span class="fw-bold text-dark fs-6">กำลังประมวลผลและคำนวณแนวโน้มลูกหนี้ย้อนหลัง...</span>
          <small class="text-muted mt-1">วิเคราะห์ข้อมูลรายเดือนและจำแนก 3 มิติ</small>
        </div>

        <!-- 1. KPI Cards Row -->
        <div class="row g-3 mb-4">
          <div class="col-12 col-sm-6 col-xl-3">
            <div class="trend-kpi-card" style="border-left: 5px solid #4f46e5;">
              <div class="trend-kpi-title">
                <span>ยอดภาระหนี้รวมทั้งหมด</span>
                <span id="kpiMomBadge" class="trend-badge-up"><i class='bx bx-trending-up'></i> 0%</span>
              </div>
              <div class="trend-kpi-val text-primary" id="kpiTotalDebit">฿ 0.00</div>
              <div class="trend-kpi-sub" id="kpiDebitSub">รวมยอดช่วงเวลาที่เลือก</div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-xl-3">
            <div class="trend-kpi-card" style="border-left: 5px solid #0284c7;">
              <div class="trend-kpi-title">
                <span>จำนวนเคสที่มารับบริการ</span>
                <i class='bx bx-user-check text-info fs-5'></i>
              </div>
              <div class="trend-kpi-val text-info" id="kpiTotalCases">0 ราย</div>
              <div class="trend-kpi-sub" id="kpiCasesSub">เฉลี่ย 0 เคส/เดือน</div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-xl-3">
            <div class="trend-kpi-card" style="border-left: 5px solid #f59e0b;">
              <div class="trend-kpi-title">
                <span>ภาระหนี้เฉลี่ยต่อเคส</span>
                <i class='bx bx-calculator text-warning fs-5'></i>
              </div>
              <div class="trend-kpi-val text-warning" id="kpiAvgDebit">฿ 0.00</div>
              <div class="trend-kpi-sub">บาท / ครั้งที่เข้ารับบริการ</div>
            </div>
          </div>

          <div class="col-12 col-sm-6 col-xl-3">
            <div class="trend-kpi-card" style="border-left: 5px solid #e11d48;">
              <div class="trend-kpi-title">
                <span>เดือนที่ภาระหนี้สูงสุด (Peak)</span>
                <i class='bx bx-bell text-danger fs-5'></i>
              </div>
              <div class="trend-kpi-val text-danger" id="kpiPeakDebit">฿ 0.00</div>
              <div class="trend-kpi-sub text-truncate" id="kpiPeakMonth">-</div>
            </div>
          </div>
        </div>

        <!-- 2. Main Dual-Axis Chart Box -->
        <div class="trend-chart-box">
          <div class="trend-chart-header">
            <div>
              <h6 class="fw-bold mb-1 text-dark"><i class='bx bx-bar-chart-alt-2 text-primary me-2'></i>กราฟเปรียบเทียบแนวโน้มรายเดือน: ภาระหนี้ (แกนซ้าย) เทียบกับ จำนวนคน (แกนขวา)</h6>
              <small class="text-muted">คลิกที่แท่งกราฟหรือจุดข้อมูลเพื่อดูรายละเอียด หรือขยายช่วงเวลาที่ต้องการ</small>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-label-primary px-3 py-2"><span class="badge badge-dot bg-primary me-1"></span> ภาระหนี้ (บาท)</span>
              <span class="badge bg-label-warning px-3 py-2"><span class="badge badge-dot bg-warning me-1"></span> จำนวนคน (เคส)</span>
            </div>
          </div>
          <!-- ApexChart Element -->
          <div id="chartDebtorTrend" style="min-height: 380px;"></div>
        </div>

        <!-- 3. Drill-Down Tabs Section (ตอบคำถามว่าทำไมหนี้เพิ่มขึ้น) -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
          <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs trend-nav-tabs px-3 pt-2" id="trendDrilldownTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="tab-hosp-btn" data-bs-toggle="tab" data-bs-target="#tab-hosp" type="button" role="tab">
                  <i class='bx bx-buildings me-1 text-primary'></i> 1. โรงพยาบาลต้นสังกัด (Hospmain)
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-clinic-btn" data-bs-toggle="tab" data-bs-target="#tab-clinic" type="button" role="tab">
                  <i class='bx bx-clinic me-1 text-info'></i> 2. แผนก/คลินิกที่รับบริการ
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-table-btn" data-bs-toggle="tab" data-bs-target="#tab-table" type="button" role="tab">
                  <i class='bx bx-table me-1 text-success'></i> 3. สรุปรายเดือน & คนไข้ภาระหนี้สูง
                </button>
              </li>
            </ul>
          </div>

          <div class="card-body p-4 bg-white">
            <div class="tab-content" id="trendDrilldownTabContent">

              <!-- Tab 1: Hospmain Breakdown -->
              <div class="tab-pane fade show active" id="tab-hosp" role="tabpanel">
                <div class="row g-4 align-items-center">
                  <div class="col-12 col-lg-6">
                    <h6 class="fw-bold text-dark mb-3"><i class='bx bx-pie-chart-alt text-primary me-1'></i> สัดส่วนลูกหนี้ตามโรงพยาบาลต้นสังกัด (Top 10 Hospmain)</h6>
                    <div id="chartHospBreakdown" style="min-height: 340px;"></div>
                  </div>
                  <div class="col-12 col-lg-6">
                    <div class="trend-table-box table-responsive">
                      <table class="table table-hover align-middle">
                        <thead>
                          <tr>
                            <th>รหัส รพ.</th>
                            <th>ชื่อสถานพยาบาลต้นสังกัด</th>
                            <th class="text-end">จำนวนเคส</th>
                            <th class="text-end">ภาระหนี้ (บาท)</th>
                            <th class="text-end">สัดส่วน (%)</th>
                          </tr>
                        </thead>
                        <tbody id="tbodyHospBreakdown">
                          <!-- Dynamic rows -->
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Tab 2: Clinic Breakdown -->
              <div class="tab-pane fade" id="tab-clinic" role="tabpanel">
                <div class="row g-4 align-items-center">
                  <div class="col-12 col-lg-6">
                    <h6 class="fw-bold text-dark mb-3"><i class='bx bx-bar-chart text-info me-1'></i> สัดส่วนตามแผนก/คลินิกที่รับบริการ</h6>
                    <div id="chartClinicBreakdown" style="min-height: 340px;"></div>
                  </div>
                  <div class="col-12 col-lg-6">
                    <div class="trend-table-box table-responsive">
                      <table class="table table-hover align-middle">
                        <thead>
                          <tr>
                            <th>ชื่อแผนก / คลินิก</th>
                            <th class="text-end">จำนวนเคส</th>
                            <th class="text-end">ภาระหนี้ (บาท)</th>
                            <th class="text-end">สัดส่วน (%)</th>
                          </tr>
                        </thead>
                        <tbody id="tbodyClinicBreakdown">
                          <!-- Dynamic rows -->
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Tab 3: Monthly Table & Top Debtors -->
              <div class="tab-pane fade" id="tab-table" role="tabpanel">
                <!-- ตารางเปรียบเทียบรายเดือน -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h6 class="fw-bold text-dark mb-0"><i class='bx bx-calendar-event text-success me-1'></i> ตารางเปรียบเทียบสถิติลูกหนี้รายเดือน</h6>
                  <button type="button" class="btn btn-sm btn-outline-success" onclick="exportMonthlyTrendToExcel()">
                    <i class='bx bx-download me-1'></i> ส่งออกสรุปรายเดือน (Excel)
                  </button>
                </div>
                <div class="trend-table-box table-responsive mb-4">
                  <table class="table table-hover table-striped align-middle">
                    <thead>
                      <tr>
                        <th>เดือน - ปี</th>
                        <th class="text-end">จำนวนคน (เคส)</th>
                        <th class="text-end">ค่ารักษาจริง (บาท)</th>
                        <th class="text-end">ภาระหนี้ลูกหนี้ (บาท)</th>
                        <th class="text-end">เฉลี่ย/เคส (บาท)</th>
                        <th class="text-end">การเปลี่ยนแปลง MoM</th>
                        <th class="text-center">ดูคนไข้รายเดือน</th>
                      </tr>
                    </thead>
                    <tbody id="tbodyMonthlyTrend">
                      <!-- Dynamic rows -->
                    </tbody>
                  </table>
                </div>

                <!-- ตาราง Top 10 High Cost Debtors -->
                <h6 class="fw-bold text-dark mb-3 mt-4"><i class='bx bx-award text-danger me-1'></i> ผู้ป่วยที่มีภาระหนี้สูงสุด 10 อันดับแรก (Top 10 High-Cost Cases)</h6>
                <div class="trend-table-box table-responsive">
                  <table class="table table-hover align-middle">
                    <thead>
                      <tr>
                        <th>VN / AN</th>
                        <th>HN</th>
                        <th>ชื่อ - สกุล</th>
                        <th>วันที่รับบริการ</th>
                        <th>รพ.ต้นสังกัด</th>
                        <th>แผนก/คลินิก</th>
                        <th class="text-end">ค่าใช้จ่าย</th>
                        <th class="text-end">ภาระหนี้ (บาท)</th>
                      </tr>
                    </thead>
                    <tbody id="tbodyTopDebtors">
                      <!-- Dynamic rows -->
                    </tbody>
                  </table>
                </div>
              </div>

            </div>
          </div>
        </div>

        <!-- 4. Patient Detail Drawer (เมื่อคลิกดูคนไข้รายเดือน) -->
        <div id="trendPatientDetailDrawer">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
              <h6 class="fw-bold text-dark mb-0">
                <i class='bx bx-user-voice text-primary me-1'></i> รายชื่อผู้ป่วยประจำเดือน: <span id="txtDrawerMonthLabel" class="text-primary">-</span>
              </h6>
              <small class="text-muted" id="txtDrawerSubSummary">พบ 0 รายการ ยอดภาระหนี้รวม 0.00 บาท</small>
            </div>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-sm btn-success" onclick="exportPatientListToExcel()">
                <i class='bx bx-download me-1'></i> ส่งออกรายชื่อคนไข้ (Excel)
              </button>
              <button type="button" class="btn btn-sm btn-secondary" onclick="$('#trendPatientDetailDrawer').slideUp(200);">
                <i class='bx bx-x me-1'></i> ซ่อน
              </button>
            </div>
          </div>
          <div class="trend-table-box table-responsive bg-white">
            <table class="table table-sm table-hover align-middle" id="tblDrawerPatients">
              <thead>
                <tr>
                  <th style="width: 50px;">ลำดับ</th>
                  <th>VN / AN</th>
                  <th>HN</th>
                  <th>ชื่อ - สกุล</th>
                  <th>วันที่</th>
                  <th>รพ.ต้นสังกัด</th>
                  <th>แผนก/คลินิก</th>
                  <th class="text-end">ค่ารักษา (บาท)</th>
                  <th class="text-end">ภาระหนี้ (บาท)</th>
                </tr>
              </thead>
              <tbody id="tbodyDrawerPatients">
                <!-- Dynamic rows -->
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="modal-footer bg-light px-4 py-2 border-top">
        <span class="text-muted small me-auto">
          <i class='bx bx-info-circle text-primary'></i> ข้อมูลประมวลผลจากฐานข้อมูล eDHS & HOSxP อัตโนมัติ รองรับการวิเคราะห์ภาระหนี้รายผัง
        </span>
        <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
          <i class='bx bx-x me-1'></i> ปิดหน้าต่าง
        </button>
      </div>

    </div>
  </div>
</div>

<!-- ==================================================================================== -->
<!-- 💡 Javascript Controller for Trend Analytics Modal                                   -->
<!-- ==================================================================================== -->
<script>
  // โหลด SheetJS (XLSX) อัตโนมัติหากหน้าดังกล่าวยังไม่ได้รวมไลบรารีนี้
  if (typeof XLSX === 'undefined') {
    const xlsxScript = document.createElement('script');
    xlsxScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
    document.head.appendChild(xlsxScript);
  }

  let trendCurrentAccount = '1102050101.203';
  let trendCurrentAccountName = 'ลูกหนี้ค่ารักษา UC - OP นอก CUP (ในจังหวัดสังกัด สธ.)';
  let trendCurrentType = 'OPD';
  let trendCurrentRange = '12m';
  let trendCachedData = null;
  let trendDrawerPatients = [];
  let trendDrawerMonth = '';

  let apexChartTrend = null;
  let apexChartHosp = null;
  let apexChartClinic = null;

  /**
   * ฟังก์ชันหลักสำหรับเปิด Modal วิเคราะห์แนวโน้มลูกหนี้รายผัง
   */
  function openDebtorTrendModal(accCode, accName, type) {
    console.log('[openDebtorTrendModal] Triggered:', accCode, accName, type);

    // 1. ตรวจสอบว่ามี element #modalDebtorTrend ในหน้าเว็บหรือไม่ หากไม่มีให้โหลดเข้ามา
    let $modal = $('#modalDebtorTrend');
    if ($modal.length === 0) {
      console.warn('[openDebtorTrendModal] #modalDebtorTrend not in DOM. Loading dynamically...');
      $.get('includes/modal_debtor_trend.php?v=' + Date.now(), function(html) {
        $('body').append(html);
        openDebtorTrendModal(accCode, accName, type);
      });
      return;
    }

    // 2. ย้าย Modal มาต่อท้าย <body> เสมอ เพื่อไม่ให้ติด Stacking Context ของ container เดิม
    if ($modal.parent().prop('tagName') !== 'BODY') {
      $modal.appendTo('body');
    }

    // 3. ปรับค่า z-index ทันที
    $modal.css('z-index', '1100');

    if (accCode) trendCurrentAccount = accCode;
    if (accName) trendCurrentAccountName = accName;
    if (type) trendCurrentType = type.toUpperCase();

    // 4. ตั้งค่า UI เริ่มต้น
    $('#trendSelectType').val(trendCurrentType);
    $('#trendHeaderAccBadge')
      .text(trendCurrentType)
      .removeClass('bg-primary bg-danger')
      .addClass(trendCurrentType === 'IPD' ? 'bg-danger' : 'bg-primary');
    $('#trendHeaderAccTitle').text(trendCurrentAccount + ' - ' + trendCurrentAccountName);

    // 5. เปิด Modal อย่างปลอดภัย รองรับทั้ง Bootstrap 5 และ jQuery Fallback
    const modalEl = document.getElementById('modalDebtorTrend');
    let opened = false;

    try {
      if (window.bootstrap && bootstrap.Modal) {
        let modalInstance = bootstrap.Modal.getOrCreateInstance 
          ? bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: true, keyboard: true }) 
          : new bootstrap.Modal(modalEl, { backdrop: true, keyboard: true });
        if (modalInstance) {
          modalInstance.show();
          opened = true;
        }
      }
    } catch (e) {
      console.warn('[openDebtorTrendModal] Bootstrap 5 instance error:', e);
    }

    if (!opened && window.jQuery && typeof $modal.modal === 'function') {
      $modal.modal('show');
      opened = true;
    }

    // 6. ไม่แตะต้อง z-index ของ backdrop ปล่อยให้ Bootstrap จัดการอย่างเป็นมาตรฐาน

    // 7. ปิด Drawer รายชื่อคนไข้ถ้าเปิดค้างไว้
    $('#trendPatientDetailDrawer').hide();

    // 8. โหลดรายชื่อผังบัญชีใส่ Dropdown (ถ้ายังไม่มี)
    loadAvailableAccountsList();

    // 9. โหลดข้อมูลแนวโน้ม
    loadTrendAnalyticsData();
  }

  /**
   * สลับประเภท OPD / IPD
   */
  function onTrendTypeChange(newType) {
    trendCurrentType = newType;
    $('#trendHeaderAccBadge').text(trendCurrentType).removeClass('bg-primary bg-danger').addClass(trendCurrentType === 'IPD' ? 'bg-danger' : 'bg-primary');
    loadAvailableAccountsList(true);
  }

  /**
   * สลับผังบัญชีจาก Dropdown
   */
  function onTrendAccountChange(newAccCode) {
    trendCurrentAccount = newAccCode;
    const selectedText = $('#trendSelectAccount option:selected').text();
    trendCurrentAccountName = selectedText.split(' - ')[1] || selectedText;
    $('#trendHeaderAccTitle').text(trendCurrentAccount + ' - ' + trendCurrentAccountName);
    loadTrendAnalyticsData();
  }

  /**
   * สลับช่วงเวลาย้อนหลัง
   */
  function onTrendRangeChange(newRange) {
    trendCurrentRange = newRange;
    $('.trend-range-btn-group .btn').removeClass('active');
    $(`.trend-range-btn-group .btn[data-range="${newRange}"]`).addClass('active');
    loadTrendAnalyticsData();
  }

  /**
   * โหลดรายชื่อผังบัญชีจาก API ใส่ Dropdown
   */
  function loadAvailableAccountsList(autoReloadData = false) {
    $.ajax({
      url: 'api_debtor_trend.php',
      type: 'GET',
      data: { action: 'get_accounts_list', type: trendCurrentType },
      dataType: 'json',
      success: function(res) {
        if (res.status === 'success' && res.accounts) {
          const $sel = $('#trendSelectAccount');
          $sel.empty();
          let found = false;
          res.accounts.forEach(acc => {
            const isSelected = (acc.code === trendCurrentAccount);
            if (isSelected) found = true;
            $sel.append(`<option value="${acc.code}" ${isSelected ? 'selected' : ''}>${acc.code} - ${acc.name}</option>`);
          });

          // ถ้าผังปัจจุบันไม่มีในประเภทใหม่ ให้เลือกรายการแรก
          if (!found && res.accounts.length > 0) {
            trendCurrentAccount = res.accounts[0].code;
            trendCurrentAccountName = res.accounts[0].name;
            $sel.val(trendCurrentAccount);
            $('#trendHeaderAccTitle').text(trendCurrentAccount + ' - ' + trendCurrentAccountName);
          }

          if (autoReloadData) {
            loadTrendAnalyticsData();
          }
        }
      }
    });
  }

  /**
   * โหลดข้อมูลแนวโน้มและสถิติหลักจาก API
   */
  function loadTrendAnalyticsData() {
    $('#trendLoadingOverlay').fadeIn(150);

    $.ajax({
      url: 'api_debtor_trend.php',
      type: 'GET',
      data: {
        action: 'get_trend',
        accountcode: trendCurrentAccount,
        type: trendCurrentType,
        range: trendCurrentRange
      },
      dataType: 'json',
      success: function(res) {
        $('#trendLoadingOverlay').fadeOut(150);
        if (res.status === 'success') {
          trendCachedData = res;
          renderKpis(res.kpi);
          renderMainTrendChart(res.monthly_trend);
          renderHospBreakdown(res.top_hospitals);
          renderClinicBreakdown(res.top_clinics);
          renderMonthlyTable(res.monthly_trend);
          renderTopDebtorsTable(res.top_debtors);
        } else {
          Swal.fire('แจ้งเตือน', res.message || 'ไม่สามารถโหลดข้อมูลแนวโน้มได้', 'warning');
        }
      },
      error: function(xhr, status, err) {
        $('#trendLoadingOverlay').fadeOut(150);
        console.error('Trend API Error:', err);
        Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อ API ดึงข้อมูลแนวโน้มได้', 'error');
      }
    });
  }

  /**
   * แสดงผล KPI Cards
   */
  function renderKpis(kpi) {
    $('#kpiTotalDebit').text('฿ ' + Number(kpi.total_debit || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $('#kpiTotalCases').text(Number(kpi.total_cases || 0).toLocaleString('en-US') + ' ราย');
    $('#kpiAvgDebit').text('฿ ' + Number(kpi.avg_debit_per_case || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $('#kpiPeakDebit').text('฿ ' + Number(kpi.peak_month_debit || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
    $('#kpiPeakMonth').text(kpi.peak_month_label || '-');

    const mom = Number(kpi.latest_mom_growth || 0);
    const $badge = $('#kpiMomBadge');
    if (mom > 0) {
      $badge.removeClass('trend-badge-down').addClass('trend-badge-up').html(`<i class='bx bx-trending-up'></i> +${mom}% MoM`);
      $('#kpiDebitSub').text('แนวโน้มภาระหนี้ปรับตัวสูงขึ้น');
    } else if (mom < 0) {
      $badge.removeClass('trend-badge-up').addClass('trend-badge-down').html(`<i class='bx bx-trending-down'></i> ${mom}% MoM`);
      $('#kpiDebitSub').text('แนวโน้มภาระหนี้ปรับตัวลดลง');
    } else {
      $badge.removeClass('trend-badge-up trend-badge-down').addClass('badge bg-light text-muted').html(`<i class='bx bx-minus'></i> คงที่ 0%`);
      $('#kpiDebitSub').text('ยอดคงที่เทียบเดือนก่อน');
    }
  }

  /**
   * วาดกราฟแนวโน้ม Dual-Axis Chart (ApexCharts)
   */
  function renderMainTrendChart(trendList) {
    const categories = [];
    const debitSeries = [];
    const casesSeries = [];

    trendList.forEach(item => {
      categories.push(item.label_short);
      debitSeries.push(Math.round(item.debit));
      casesSeries.push(item.cases);
    });

    const options = {
      series: [
        {
          name: 'ยอดภาระหนี้ลูกหนี้ (บาท)',
          type: 'column',
          data: debitSeries
        },
        {
          name: 'จำนวนคนไข้ (เคส)',
          type: 'line',
          data: casesSeries
        }
      ],
      chart: {
        height: 380,
        type: 'line',
        toolbar: { show: true, tools: { download: true, selection: false, zoom: true, zoomin: true, zoomout: true, pan: true, reset: true } },
        fontFamily: 'Noto Sans Thai, sans-serif'
      },
      stroke: {
        width: [0, 4],
        curve: 'smooth'
      },
      colors: ['#4f46e5', '#f59e0b'],
      plotOptions: {
        bar: {
          borderRadius: 6,
          columnWidth: '45%'
        }
      },
      fill: {
        opacity: [0.88, 1],
        gradient: {
          inverseColors: false,
          shade: 'light',
          type: "vertical",
          opacityFrom: 0.9,
          opacityTo: 0.75,
          stops: [0, 100]
        }
      },
      labels: categories,
      markers: {
        size: [0, 6],
        strokeWidth: 2,
        fillOpacity: 1,
        hover: { size: 8 }
      },
      xaxis: {
        type: 'category',
        labels: { style: { colors: '#64748b', fontSize: '12px', fontWeight: 600 } }
      },
      yaxis: [
        {
          title: { text: 'ภาระหนี้ลูกหนี้ (บาท)', style: { color: '#4f46e5', fontWeight: 700 } },
          labels: {
            formatter: val => Number(val || 0).toLocaleString('en-US') + ' ฿',
            style: { colors: '#4f46e5', fontWeight: 600 }
          }
        },
        {
          opposite: true,
          title: { text: 'จำนวนเคส (ราย)', style: { color: '#f59e0b', fontWeight: 700 } },
          labels: {
            formatter: val => Number(val || 0).toLocaleString('en-US') + ' เคส',
            style: { colors: '#f59e0b', fontWeight: 600 }
          }
        }
      ],
      tooltip: {
        shared: true,
        intersect: false,
        y: {
          formatter: function (y, { seriesIndex }) {
            if (typeof y !== "undefined") {
              return seriesIndex === 0 
                ? Number(y).toLocaleString('en-US', { minimumFractionDigits: 2 }) + ' บาท'
                : Number(y).toLocaleString('en-US') + ' ราย';
            }
            return y;
          }
        }
      },
      legend: {
        position: 'top',
        horizontalAlign: 'right',
        fontSize: '13px',
        fontWeight: 600
      }
    };

    if (apexChartTrend) {
      apexChartTrend.destroy();
    }
    apexChartTrend = new ApexCharts(document.querySelector("#chartDebtorTrend"), options);
    apexChartTrend.render();
  }

  /**
   * วาดกราฟและตารางโรงพยาบาลต้นสังกัด (Hospmain)
   */
  function renderHospBreakdown(hospList) {
    const $tbody = $('#tbodyHospBreakdown');
    $tbody.empty();

    if (!hospList || hospList.length === 0) {
      $tbody.html('<tr><td colspan="5" class="text-center text-muted py-3">ไม่พบข้อมูลโรงพยาบาลต้นสังกัด</td></tr>');
      if (apexChartHosp) apexChartHosp.destroy();
      return;
    }

    const categories = [];
    const seriesData = [];

    hospList.forEach(item => {
      categories.push(item.hospname);
      seriesData.push(Math.round(item.debit));

      $tbody.append(`
        <tr>
          <td><span class="badge bg-label-secondary font-monospace">${item.hospcode || '-'}</span></td>
          <td class="fw-semibold text-dark">${item.hospname}</td>
          <td class="text-end fw-bold">${Number(item.cases).toLocaleString('en-US')}</td>
          <td class="text-end fw-bold text-primary">฿ ${Number(item.debit).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
          <td class="text-end">
            <span class="badge bg-label-primary font-monospace">${item.percent}%</span>
          </td>
        </tr>
      `);
    });

    const options = {
      series: [{ name: 'ภาระหนี้ (บาท)', data: seriesData }],
      chart: { type: 'bar', height: 340, toolbar: { show: false }, fontFamily: 'Noto Sans Thai, sans-serif' },
      plotOptions: { bar: { borderRadius: 4, horizontal: true, distributed: true, dataLabels: { position: 'bottom' } } },
      colors: ['#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6', '#64748b', '#0284c7', '#14b8a6', '#f43f5e'],
      dataLabels: {
        enabled: true,
        textAnchor: 'start',
        style: { colors: ['#fff'], fontSize: '11px', fontWeight: 600 },
        formatter: val => Number(val).toLocaleString('en-US') + ' ฿',
        offsetX: 0
      },
      xaxis: { categories: categories, labels: { formatter: val => Number(val).toLocaleString('en-US') } },
      yaxis: { labels: { style: { fontSize: '12px', fontWeight: 600 } } },
      tooltip: { y: { formatter: val => Number(val).toLocaleString('en-US', { minimumFractionDigits: 2 }) + ' บาท' } },
      legend: { show: false }
    };

    if (apexChartHosp) apexChartHosp.destroy();
    apexChartHosp = new ApexCharts(document.querySelector("#chartHospBreakdown"), options);
    apexChartHosp.render();
  }

  /**
   * วาดกราฟและตารางแผนก / คลินิก
   */
  function renderClinicBreakdown(clinicList) {
    const $tbody = $('#tbodyClinicBreakdown');
    $tbody.empty();

    if (!clinicList || clinicList.length === 0) {
      $tbody.html('<tr><td colspan="4" class="text-center text-muted py-3">ไม่พบข้อมูลแผนก/คลินิก</td></tr>');
      if (apexChartClinic) apexChartClinic.destroy();
      return;
    }

    const categories = [];
    const seriesData = [];

    clinicList.forEach(item => {
      categories.push(item.clinic_name);
      seriesData.push(Math.round(item.debit));

      $tbody.append(`
        <tr>
          <td class="fw-semibold text-dark"><i class='bx bx-clinic text-info me-1'></i> ${item.clinic_name}</td>
          <td class="text-end fw-bold">${Number(item.cases).toLocaleString('en-US')}</td>
          <td class="text-end fw-bold text-info">฿ ${Number(item.debit).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
          <td class="text-end"><span class="badge bg-label-info font-monospace">${item.percent}%</span></td>
        </tr>
      `);
    });

    const options = {
      series: [{ name: 'ภาระหนี้ (บาท)', data: seriesData }],
      chart: { type: 'bar', height: 340, toolbar: { show: false }, fontFamily: 'Noto Sans Thai, sans-serif' },
      plotOptions: { bar: { borderRadius: 4, horizontal: true, barHeight: '65%' } },
      colors: ['#0284c7'],
      dataLabels: { enabled: true, formatter: val => Number(val).toLocaleString('en-US') + ' ฿' },
      xaxis: { categories: categories },
      tooltip: { y: { formatter: val => Number(val).toLocaleString('en-US', { minimumFractionDigits: 2 }) + ' บาท' } }
    };

    if (apexChartClinic) apexChartClinic.destroy();
    apexChartClinic = new ApexCharts(document.querySelector("#chartClinicBreakdown"), options);
    apexChartClinic.render();
  }

  /**
   * วาดตารางเปรียบเทียบรายเดือน
   */
  function renderMonthlyTable(trendList) {
    const $tbody = $('#tbodyMonthlyTrend');
    $tbody.empty();

    trendList.forEach(item => {
      let momHtml = `<span class="text-muted"><i class='bx bx-minus'></i> 0%</span>`;
      if (item.mom_percent > 0) {
        momHtml = `<span class="trend-badge-up"><i class='bx bx-trending-up'></i> +${item.mom_percent}%</span>`;
      } else if (item.mom_percent < 0) {
        momHtml = `<span class="trend-badge-down"><i class='bx bx-trending-down'></i> ${item.mom_percent}%</span>`;
      }

      $tbody.append(`
        <tr>
          <td class="fw-bold text-dark">${item.label_full}</td>
          <td class="text-end fw-bold text-info">${Number(item.cases).toLocaleString('en-US')}</td>
          <td class="text-end">${Number(item.income).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
          <td class="text-end fw-bold text-primary">฿ ${Number(item.debit).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
          <td class="text-end text-warning fw-semibold">${Number(item.avg_debit).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
          <td class="text-end">${momHtml}</td>
          <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" onclick="openMonthlyPatientsDrawer('${item.monthtxt}', '${item.label_full}')">
              <i class='bx bx-list-ul me-1'></i> ดูคนไข้ (${item.cases})
            </button>
          </td>
        </tr>
      `);
    });
  }

  /**
   * วาดตารางคนไข้ภาระหนี้สูงสุด Top 10
   */
  function renderTopDebtorsTable(debtorsList) {
    const $tbody = $('#tbodyTopDebtors');
    $tbody.empty();

    if (!debtorsList || debtorsList.length === 0) {
      $tbody.html('<tr><td colspan="8" class="text-center text-muted py-3">ไม่พบรายการผู้ป่วย</td></tr>');
      return;
    }

    debtorsList.forEach(item => {
      $tbody.append(`
        <tr>
          <td><span class="badge bg-label-primary font-monospace">${item.id}</span></td>
          <td class="font-monospace">${item.hn}</td>
          <td class="fw-semibold text-dark">${item.ptname || '-'}</td>
          <td>${item.vstdate || '-'}</td>
          <td><small class="text-muted">${item.hospname || '-'}</small></td>
          <td><small class="text-muted">${item.clinic || item.department || '-'}</small></td>
          <td class="text-end">${Number(item.income).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
          <td class="text-end fw-bold text-danger">฿ ${Number(item.debit).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
        </tr>
      `);
    });
  }

  /**
   * เปิดดูรายชื่อคนไข้รายเดือนใน In-modal Drawer
   */
  function openMonthlyPatientsDrawer(monthtxt, monthLabel) {
    trendDrawerMonth = monthtxt;
    $('#txtDrawerMonthLabel').text(monthLabel);
    $('#trendPatientDetailDrawer').slideDown(250);
    $('#tbodyDrawerPatients').html('<tr><td colspan="9" class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm me-2"></div> กำลังดึงรายชื่อผู้ป่วย...</td></tr>');

    // เลื่อนหน้าจอลงไปยัง Drawer
    $('html, body, #modalDebtorTrend .modal-body').animate({
      scrollTop: $('#trendPatientDetailDrawer').offset().top - 80
    }, 300);

    $.ajax({
      url: 'api_debtor_trend.php',
      type: 'GET',
      data: {
        action: 'get_patient_list',
        accountcode: trendCurrentAccount,
        type: trendCurrentType,
        monthtxt: monthtxt,
        limit: 200
      },
      dataType: 'json',
      success: function(res) {
        if (res.status === 'success' && res.patients) {
          trendDrawerPatients = res.patients;
          $('#txtDrawerSubSummary').text(`พบ ${res.total_count} รายการ ยอดภาระหนี้รวม ฿ ${Number(res.total_debit).toLocaleString('en-US', { minimumFractionDigits: 2 })} บาท`);

          const $tbody = $('#tbodyDrawerPatients');
          $tbody.empty();

          if (res.patients.length === 0) {
            $tbody.html('<tr><td colspan="9" class="text-center text-muted py-3">ไม่พบรายชื่อผู้ป่วยในเดือนนี้</td></tr>');
            return;
          }

          res.patients.forEach((p, idx) => {
            $tbody.append(`
              <tr>
                <td class="text-center text-muted">${idx + 1}</td>
                <td><span class="badge bg-label-secondary font-monospace">${p.id}</span></td>
                <td class="font-monospace">${p.hn}</td>
                <td class="fw-semibold text-dark">${p.ptname || '-'}</td>
                <td>${p.vstdate || '-'}</td>
                <td><small>${p.hospname || '-'}</small></td>
                <td><small>${p.clinic || p.department || '-'}</small></td>
                <td class="text-end">${Number(p.income).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                <td class="text-end fw-bold text-primary">฿ ${Number(p.debit).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
              </tr>
            `);
          });
        }
      }
    });
  }

  /**
   * ส่งออกสรุปรายเดือนเป็น Excel (SheetJS)
   */
  function exportMonthlyTrendToExcel() {
    if (!trendCachedData || !trendCachedData.monthly_trend) {
      Swal.fire('แจ้งเตือน', 'ไม่มีข้อมูลสำหรับส่งออก', 'info');
      return;
    }

    const rows = [
      ['รายงานแนวโน้มลูกหนี้ย้อนหลังรายผังบัญชี - eDHS'],
      ['รหัสผังบัญชี:', trendCachedData.meta.accountcode, 'ชื่อผังบัญชี:', trendCachedData.meta.accountname],
      ['ประเภท:', trendCachedData.meta.type, 'ช่วงเวลา:', trendCachedData.meta.range],
      [],
      ['งวดเดือน-ปี', 'จำนวนคน (เคส)', 'ผู้ป่วยไม่ซ้ำ (คน)', 'ค่ารักษาจริง (บาท)', 'ภาระหนี้ลูกหนี้ (บาท)', 'เฉลี่ยต่อเคส (บาท)', 'อัตราเติบโต MoM (%)']
    ];

    trendCachedData.monthly_trend.forEach(m => {
      rows.push([
        m.label_full,
        m.cases,
        m.distinct_patients,
        m.income,
        m.debit,
        m.avg_debit,
        m.mom_percent + '%'
      ]);
    });

    const ws = XLSX.utils.aoa_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Monthly_Trend");
    const fileName = `Debtor_Trend_${trendCachedData.meta.accountcode}_${trendCachedData.meta.type}.xlsx`;
    XLSX.writeFile(wb, fileName);
  }

  /**
   * ส่งออกรายชื่อคนไข้รายเดือนเป็น Excel (SheetJS)
   */
  function exportPatientListToExcel() {
    if (!trendDrawerPatients || trendDrawerPatients.length === 0) {
      Swal.fire('แจ้งเตือน', 'ไม่มีข้อมูลคนไข้สำหรับส่งออก', 'info');
      return;
    }

    const rows = [
      ['รายชื่อผู้ป่วยลูกหนี้รายเดือน - eDHS'],
      ['รหัสผังบัญชี:', trendCurrentAccount, 'ชื่อผังบัญชี:', trendCurrentAccountName],
      ['งวดเดือน:', trendDrawerMonth, 'ประเภท:', trendCurrentType],
      [],
      ['ลำดับ', 'VN/AN', 'HN', 'ชื่อ-สกุล', 'วันที่รับบริการ', 'รหัส รพ.', 'สถานพยาบาลต้นสังกัด', 'แผนก/คลินิก', 'ค่ารักษาพยาบาล (บาท)', 'ภาระหนี้ (บาท)']
    ];

    trendDrawerPatients.forEach((p, idx) => {
      rows.push([
        idx + 1,
        p.id,
        p.hn,
        p.ptname,
        p.vstdate,
        p.hospcode,
        p.hospname,
        p.clinic || p.department || '',
        p.income,
        p.debit
      ]);
    });

    const ws = XLSX.utils.aoa_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Patients_List");
    const fileName = `Patients_${trendCurrentAccount}_${trendDrawerMonth}.xlsx`;
    XLSX.writeFile(wb, fileName);
  }

  // จัดการลำดับชั้นของ Backdrop กรณีเปิด Modal ซ้อนเหนือ Modal อื่น
  $(document).on('show.bs.modal', '#modalDebtorTrend', function () {
    setTimeout(function () {
      var backdrops = document.querySelectorAll('.modal-backdrop');
      if (backdrops.length > 1) {
        backdrops[backdrops.length - 1].style.zIndex = '1095';
      }
    }, 20);
  });

</script>
