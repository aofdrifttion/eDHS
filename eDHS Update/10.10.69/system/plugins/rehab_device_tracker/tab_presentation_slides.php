<!-- ============================================================== -->
<!-- TAB 5: EXECUTIVE PRESENTATION SLIDES & INSIGHTS                -->
<!-- ============================================================== -->
<div class="tab-pane fade" id="tab-presentation" role="tabpanel">

<style>
/* Presentation Fullscreen & Responsive Display Styles */
#presentationWrapper.pseudo-fullscreen,
#presentationWrapper:fullscreen,
#presentationWrapper:-webkit-full-screen,
#presentationWrapper:-moz-full-screen,
#presentationWrapper:-ms-fullscreen {
  position: fixed !important;
  top: 0 !important;
  left: 0 !important;
  width: 100vw !important;
  height: 100vh !important;
  z-index: 99999 !important;
  background-color: #f1f5f9 !important;
  overflow-y: auto !important;
  overflow-x: hidden !important;
  padding: 1.25rem 2rem 3rem 2rem !important;
  box-sizing: border-box !important;
  display: flex !important;
  flex-direction: column !important;
}

#presentationWrapper.pseudo-fullscreen .presentation-toolbar,
#presentationWrapper:fullscreen .presentation-toolbar,
#presentationWrapper:-webkit-full-screen .presentation-toolbar {
  position: sticky !important;
  top: 0 !important;
  z-index: 1050 !important;
  border-radius: 0.75rem !important;
  box-shadow: 0 4px 16px rgba(15, 23, 42, 0.12) !important;
  margin-bottom: 1.25rem !important;
  background-color: #ffffff !important;
}

#presentationWrapper.pseudo-fullscreen #presentationCanvas,
#presentationWrapper:fullscreen #presentationCanvas,
#presentationWrapper:-webkit-full-screen #presentationCanvas {
  flex-grow: 1 !important;
  width: 100% !important;
  max-width: 100% !important;
  border: 1px solid #e2e8f0 !important;
  box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06) !important;
  margin-bottom: 2rem !important;
  border-radius: 1rem !important;
}

#presentationCanvas:fullscreen,
#presentationCanvas:-webkit-full-screen {
  overflow-y: auto !important;
  background-color: #ffffff !important;
  padding: 2rem !important;
  box-sizing: border-box !important;
}
</style>

  <div id="presentationWrapper" class="presentation-wrapper w-100">
    <!-- Presentation Control Toolbar -->
    <div class="card border mb-3 bg-white shadow-sm p-3 presentation-toolbar">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
          <div class="btn-group" role="group">
            <button type="button" class="btn btn-sm btn-outline-primary active" id="btnSlide1" onclick="switchPresentationSlide(1)">
              <i class="bx bx-line-chart me-1"></i> ผู้รับบริการ & รายได้ 5 ปี
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnSlide2" onclick="switchPresentationSlide(2)">
              <i class="bx bx-award me-1"></i> ชดเชยอุปกรณ์แยกตามสิทธิ & Top 10
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger" id="btnSlide3" onclick="switchPresentationSlide(3)">
              <i class="bx bx-error-circle me-1"></i> จุดรั่วไหล & โอกาสดึงเงินคืน รพ.
            </button>
          </div>
        </div>

        <div class="d-flex align-items-center gap-2">
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="prevPresentationSlide()">
            <i class="bx bx-chevron-left me-1"></i> ก่อนหน้า
          </button>
          <button type="button" class="btn btn-sm btn-outline-secondary" onclick="nextPresentationSlide()">
            ถัดไป <i class="bx bx-chevron-right ms-1"></i>
          </button>
          <button type="button" class="btn btn-sm btn-dark" id="btnToggleFs" onclick="toggleFullscreenPresentation()" title="โหมดนำเสนอเต็มหน้าจอสำหรับที่ประชุม">
            <i class="bx bx-fullscreen me-1"></i> เต็มหน้าจอ (Fullscreen)
          </button>
          <button type="button" class="btn btn-sm btn-outline-success" onclick="window.print()" title="พิมพ์หรือบันทึกเป็น PDF">
            <i class="bx bx-printer me-1"></i> พิมพ์ / PDF
          </button>
        </div>
      </div>
    </div>

    <!-- Main Presentation Display Canvas -->
    <div id="presentationCanvas" class="presentation-canvas bg-white border rounded-3 p-4 shadow-sm mb-4 position-relative" style="min-height: 650px;">

    <!-- ============================================================== -->
    <!-- SLIDE 1: สถิติผู้รับบริการ และเปรียบเทียบรายได้ย้อนหลัง 5 ปี (ครบทุกกลุ่มงาน) -->
    <!-- ============================================================== -->
    <div id="slide-section-1" class="slide-content-view">
      <!-- Title Header -->
      <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-3 mb-4">
        <div class="d-flex align-items-center gap-3">
          <div class="avatar avatar-lg bg-label-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm">
            <i class="bx bx-line-chart fs-2 text-primary"></i>
          </div>
          <div>
            <h3 class="fw-bolder text-dark mb-0">
              สถิติผู้รับบริการ และการเติบโตของรายได้ <span class="text-primary">ย้อนหลัง 5 ปี</span>
            </h3>
            <small class="text-dark fw-bold" id="slide1_subtitle">จำแนกตาม 6 กลุ่มงานสั่งใช้และเบิกชดเชยอุปกรณ์การแพทย์ (ปีงบประมาณ 2565 – 2569) • ข้อมูลจริงจาก HOSxP โรงพยาบาลโพนทราย</small>
          </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-1 mt-2 mt-md-0">
          <span class="badge px-2 py-1 small fw-bold" style="background-color: #696cff; color: #fff;"><i class="bx bx-accessibility me-1"></i>กายภาพบำบัด</span>
          <span class="badge px-2 py-1 small fw-bold" style="background-color: #03c3ec; color: #fff;"><i class="bx bx-smile me-1"></i>ทันตกรรม</span>
          <span class="badge px-2 py-1 small fw-bold" style="background-color: #71dd37; color: #fff;"><i class="bx bx-clinic me-1"></i>OPD</span>
          <span class="badge px-2 py-1 small fw-bold" style="background-color: #ff3e1d; color: #fff;"><i class="bx bx-plus-medical me-1"></i>ER</span>
          <span class="badge px-2 py-1 small fw-bold" style="background-color: #ffab00; color: #fff;"><i class="bx bx-bed me-1"></i>IPD</span>
          <span class="badge px-2 py-1 small fw-bold" style="background-color: #8592a3; color: #fff;"><i class="bx bx-spa me-1"></i>แผนไทย</span>
          <button type="button" class="btn btn-xs btn-outline-primary ms-1" id="btnRefresh5Years" onclick="load5YearsTrendData(true)" title="ดึงข้อมูลคำนวณสดใหม่จาก HOSxP">
            <i class="bx bx-refresh me-1"></i> อัปเดตสด
          </button>
        </div>
      </div>

      <!-- 3 Key Growth Highlight Cards -->
      <div class="row g-3 mb-4">
        <!-- Card 1: Rehab Growth -->
        <div class="col-12 col-md-4">
          <div class="card border p-3 shadow-sm rounded-4 h-100 hover-elevate" style="background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%); border-color: #c7d2fe !important;">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="badge bg-primary rounded-pill px-3 py-1"><i class="bx bx-accessibility me-1"></i>กายภาพบำบัด (Rehab)</span>
              <span class="badge bg-white text-primary fw-bold shadow-sm">5 ปีย้อนหลัง</span>
            </div>
            <div class="d-flex justify-content-between text-dark fw-bold my-2 small">
              <span id="slide1_card1_span1">ปี 2565: ฿109,670 (98 คน)</span>
              <span id="slide1_card1_span2">ปี 2569: ฿1,108,320 (693 คน)</span>
            </div>
            <hr class="my-2 border-primary-subtle">
            <div class="d-flex align-items-center justify-content-between">
              <span class="fw-bold text-dark">เติบโตก้าวกระโดด</span>
              <h3 class="mb-0 fw-bolder text-primary" id="slide1_card1_growth">
                <i class="bx bx-trending-up"></i> +910.6%
              </h3>
            </div>
          </div>
        </div>

        <!-- Card 2: Dental Growth -->
        <div class="col-12 col-md-4">
          <div class="card border p-3 shadow-sm rounded-4 h-100 hover-elevate" style="background: linear-gradient(135deg, #ecfeff 0%, #cffafe 100%); border-color: #a5f3fc !important;">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="badge bg-info rounded-pill px-3 py-1 text-white"><i class="bx bx-smile me-1"></i>ทันตกรรม (Dental)</span>
              <span class="badge bg-white text-info fw-bold shadow-sm">5 ปีย้อนหลัง</span>
            </div>
            <div class="d-flex justify-content-between text-dark fw-bold my-2 small">
              <span id="slide1_card2_span1">ปี 2565: ฿56,700 (25 คน)</span>
              <span id="slide1_card2_span2">ปี 2569: ฿108,900</span>
            </div>
            <hr class="my-2 border-info-subtle">
            <div class="d-flex align-items-center justify-content-between">
              <span class="fw-bold text-dark" id="slide1_card2_label">อัตราเติบโต</span>
              <h3 class="mb-0 fw-bolder text-info" id="slide1_card2_growth">
                <i class="bx bx-trending-up"></i> +92.1%
              </h3>
            </div>
          </div>
        </div>

        <!-- Card 3: Hospital Total Devices Growth -->
        <div class="col-12 col-md-4">
          <div class="card border p-3 shadow-sm rounded-4 h-100 hover-elevate" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-color: #bbf7d0 !important;">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span class="badge bg-success rounded-pill px-3 py-1 text-white"><i class="bx bx-devices me-1"></i>ยอดรวมอุปกรณ์ รพ. ทั้งหมด</span>
              <span class="badge bg-white text-success fw-bold shadow-sm">5 ปีย้อนหลัง</span>
            </div>
            <div class="d-flex justify-content-between text-dark fw-bold my-2 small">
              <span id="slide1_card3_span1">ปี 2565: ฿425,610 (388 คน)</span>
              <span id="slide1_card3_span2">ปี 2569: ฿1,395,260 (997 คน)</span>
            </div>
            <hr class="my-2 border-success-subtle">
            <div class="d-flex align-items-center justify-content-between">
              <span class="fw-bold text-dark" id="slide1_card3_label">อัตราเติบโต</span>
              <h3 class="mb-0 fw-bolder text-success" id="slide1_card3_growth">
                <i class="bx bx-trending-up"></i> +227.8%
              </h3>
            </div>
          </div>
        </div>
      </div>

      <!-- 2 Side-by-Side Charts: Volume vs Revenue across 6 Departments -->
      <div class="row g-3 mb-4">
        <!-- Chart 1: Volume Patients by Department (6 Groups) -->
        <div class="col-lg-6">
          <div class="card border p-3 bg-white shadow-sm rounded-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="fw-bold text-dark mb-0"><i class="bx bx-user me-1 text-primary"></i> 1. สถิติจำนวนผู้รับบริการอุปกรณ์การแพทย์ แยกรายกลุ่มงาน (คน)</h6>
              <small class="text-muted fw-bold" id="slide1_chart1_sub">5 ปีย้อนหลัง</small>
            </div>
            <div id="slide1_volume_chart" style="height: 330px;"></div>
          </div>
        </div>

        <!-- Chart 2: Revenue Growth by Department (6 Groups) -->
        <div class="col-lg-6">
          <div class="card border p-3 bg-white shadow-sm rounded-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="fw-bold text-dark mb-0"><i class="bx bx-line-chart me-1 text-success"></i> 2. มูลค่าการเรียกเก็บอุปกรณ์การแพทย์ แยกรายกลุ่มงาน (บาท)</h6>
              <small class="text-muted fw-bold" id="slide1_chart2_sub">5 ปีย้อนหลัง</small>
            </div>
            <div id="slide1_revenue_chart" style="height: 330px;"></div>
          </div>
        </div>
      </div>

      <!-- 5-Year Data Table (Complete Department Breakdown) -->
      <div class="table-responsive">
        <table class="table table-bordered text-center align-middle bg-white shadow-sm mb-0" id="tableSlide1Summary">
          <thead style="background-color: #f8fafc;">
            <tr>
              <th class="text-dark fw-bold text-start" style="width: 32%;">ตัวชี้วัดสำคัญ (Key Indicators) - จำแนกครบทุกกลุ่มงาน</th>
              <th class="text-dark fw-bold" style="width: 11%;">ปี 2565</th>
              <th class="text-dark fw-bold" style="width: 11%;">ปี 2566</th>
              <th class="text-dark fw-bold" style="width: 11%;">ปี 2567</th>
              <th class="text-dark fw-bold" style="width: 11%;">ปี 2568</th>
              <th class="text-dark fw-bold text-primary" style="width: 12%;">ปี 2569</th>
              <th class="text-dark fw-bold text-success" style="width: 12%;">อัตราเติบโต (%)</th>
            </tr>
          </thead>
          <tbody id="tbodySlide1Rows">
            <!-- 1. กายภาพบำบัด -->
            <tr>
              <td class="fw-bold text-start text-primary"><i class="bx bx-accessibility me-1"></i> 1. กายภาพบำบัด (มูลค่าอุปกรณ์)</td>
              <td class="fw-semibold text-dark">
                <div>฿109,670</div>
                <small class="badge bg-light text-primary border" style="font-size:0.75rem;">98 คน / 106 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿143,020</div>
                <small class="badge bg-light text-primary border" style="font-size:0.75rem;">164 คน / 188 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿202,640</div>
                <small class="badge bg-light text-primary border" style="font-size:0.75rem;">220 คน / 253 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿658,250</div>
                <small class="badge bg-light text-primary border" style="font-size:0.75rem;">623 คน / 701 ครั้ง</small>
              </td>
              <td class="fw-bold text-primary fs-6">
                <div>฿1,108,320</div>
                <small class="badge bg-light text-primary border" style="font-size:0.75rem;">693 คน / 772 ครั้ง</small>
              </td>
              <td>
                <div class="fw-bold text-primary"><i class="bx bx-up-arrow-alt"></i> เงิน +910.6%</div>
                <small class="fw-bold text-success"><i class="bx bx-up-arrow-alt"></i> +628.3% (ครั้ง)</small>
              </td>
            </tr>
            <!-- 2. ทันตกรรม -->
            <tr>
              <td class="fw-bold text-start text-info"><i class="bx bx-smile me-1"></i> 2. ทันตกรรม (มูลค่าอุปกรณ์)</td>
              <td class="fw-semibold text-dark">
                <div>฿56,700</div>
                <small class="badge bg-light text-info border" style="font-size:0.75rem;">25 คน / 35 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿393,460</div>
                <small class="badge bg-light text-info border" style="font-size:0.75rem;">146 คน / 208 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿374,050</div>
                <small class="badge bg-light text-info border" style="font-size:0.75rem;">98 คน / 154 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿282,170</div>
                <small class="badge bg-light text-info border" style="font-size:0.75rem;">90 คน / 125 ครั้ง</small>
              </td>
              <td class="fw-bold text-info fs-6">
                <div>฿108,900</div>
                <small class="badge bg-light text-info border" style="font-size:0.75rem;">42 คน / 53 ครั้ง</small>
              </td>
              <td>
                <div class="fw-bold text-info"><i class="bx bx-up-arrow-alt"></i> เงิน +92.1%</div>
                <small class="fw-bold text-success"><i class="bx bx-up-arrow-alt"></i> +51.4% (ครั้ง)</small>
              </td>
            </tr>
            <!-- 3. ผู้ป่วยนอก OPD -->
            <tr>
              <td class="fw-bold text-start text-success"><i class="bx bx-clinic me-1"></i> 3. ผู้ป่วยนอก (OPD) (มูลค่าอุปกรณ์)</td>
              <td class="fw-semibold text-dark">
                <div>฿46,630</div>
                <small class="badge bg-light text-success border" style="font-size:0.75rem;">45 คน / 71 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿91,360</div>
                <small class="badge bg-light text-success border" style="font-size:0.75rem;">78 คน / 165 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿78,680</div>
                <small class="badge bg-light text-success border" style="font-size:0.75rem;">95 คน / 133 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿128,570</div>
                <small class="badge bg-light text-success border" style="font-size:0.75rem;">112 คน / 160 ครั้ง</small>
              </td>
              <td class="fw-bold text-success fs-6">
                <div>฿124,780</div>
                <small class="badge bg-light text-success border" style="font-size:0.75rem;">88 คน / 142 ครั้ง</small>
              </td>
              <td>
                <div class="fw-bold text-success"><i class="bx bx-up-arrow-alt"></i> เงิน +167.6%</div>
                <small class="fw-bold text-success"><i class="bx bx-up-arrow-alt"></i> +100.0% (ครั้ง)</small>
              </td>
            </tr>
            <!-- 4. อุบัติเหตุและฉุกเฉิน ER -->
            <tr>
              <td class="fw-bold text-start text-danger"><i class="bx bx-plus-medical me-1"></i> 4. อุบัติเหตุและฉุกเฉิน (ER) (มูลค่าอุปกรณ์)</td>
              <td class="fw-semibold text-dark">
                <div>฿46,540</div>
                <small class="badge bg-light text-danger border" style="font-size:0.75rem;">198 คน / 227 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿44,650</div>
                <small class="badge bg-light text-danger border" style="font-size:0.75rem;">199 คน / 218 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿37,220</div>
                <small class="badge bg-light text-danger border" style="font-size:0.75rem;">194 คน / 216 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿50,960</div>
                <small class="badge bg-light text-danger border" style="font-size:0.75rem;">239 คน / 269 ครั้ง</small>
              </td>
              <td class="fw-bold text-danger fs-6">
                <div>฿48,930</div>
                <small class="badge bg-light text-danger border" style="font-size:0.75rem;">228 คน / 254 ครั้ง</small>
              </td>
              <td>
                <div class="fw-bold text-danger"><i class="bx bx-up-arrow-alt"></i> เงิน +5.1%</div>
                <small class="fw-bold text-success"><i class="bx bx-up-arrow-alt"></i> +11.9% (ครั้ง)</small>
              </td>
            </tr>
            <!-- 5. ผู้ป่วยใน IPD (มูลค่าอุปกรณ์) -->
            <tr>
              <td class="fw-bold text-start text-warning"><i class="bx bx-bed me-1"></i> 5. ผู้ป่วยใน (IPD) (มูลค่าอุปกรณ์)</td>
              <td class="fw-semibold text-dark">
                <div>฿166,070</div>
                <small class="badge bg-light text-warning border" style="font-size:0.75rem;">53 คน / 342 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿1,900</div>
                <small class="badge bg-light text-warning border" style="font-size:0.75rem;">18 คน / 18 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿1,450</div>
                <small class="badge bg-light text-warning border" style="font-size:0.75rem;">12 คน / 13 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿2,650</div>
                <small class="badge bg-light text-warning border" style="font-size:0.75rem;">26 คน / 26 ครั้ง</small>
              </td>
              <td class="fw-bold text-warning fs-6">
                <div>฿4,180</div>
                <small class="badge bg-light text-warning border" style="font-size:0.75rem;">25 คน / 28 ครั้ง</small>
              </td>
              <td>
                <div class="fw-bold text-warning"><i class="bx bx-down-arrow-alt"></i> เงิน -97.5%</div>
                <small class="fw-bold text-danger"><i class="bx bx-down-arrow-alt"></i> -91.8% (ครั้ง)</small>
              </td>
            </tr>

            <!-- 6. แพทย์แผนไทย (มูลค่าอุปกรณ์) -->
            <tr>
              <td class="fw-bold text-start text-secondary"><i class="bx bx-spa me-1"></i> 6. แพทย์แผนไทย (มูลค่าอุปกรณ์)</td>
              <td class="fw-semibold text-dark">
                <div>฿0</div>
                <small class="badge bg-light text-secondary border" style="font-size:0.75rem;">0 คน / 0 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿2,400</div>
                <small class="badge bg-light text-secondary border" style="font-size:0.75rem;">1 คน / 1 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿3,290</div>
                <small class="badge bg-light text-secondary border" style="font-size:0.75rem;">3 คน / 3 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿0</div>
                <small class="badge bg-light text-secondary border" style="font-size:0.75rem;">0 คน / 0 ครั้ง</small>
              </td>
              <td class="fw-bold text-secondary fs-6">
                <div>฿150</div>
                <small class="badge bg-light text-secondary border" style="font-size:0.75rem;">1 คน / 1 ครั้ง</small>
              </td>
              <td>
                <div class="fw-bold text-secondary">- (เงิน)</div>
                <small class="fw-bold text-muted">- (ครั้ง)</small>
              </td>
            </tr>

            <!-- 7. ยอดเรียกเก็บอุปกรณ์รวม รพ. -->
            <tr style="background-color: #f0fdf4;" class="border-top border-2">
              <td class="fw-bolder text-start text-dark"><i class="bx bx-check-shield me-1 text-success"></i> 7. รวมยอดเรียกเก็บอุปกรณ์การแพทย์ทั้งสิ้น (บาท)</td>
              <td class="fw-bold text-dark">
                <div>฿425,610</div>
                <small class="badge bg-success text-white" style="font-size:0.75rem;">388 คน / 781 ครั้ง</small>
              </td>
              <td class="fw-bold text-dark">
                <div>฿676,790</div>
                <small class="badge bg-success text-white" style="font-size:0.75rem;">549 คน / 798 ครั้ง</small>
              </td>
              <td class="fw-bold text-dark">
                <div>฿697,330</div>
                <small class="badge bg-success text-white" style="font-size:0.75rem;">563 คน / 772 ครั้ง</small>
              </td>
              <td class="fw-bold text-dark">
                <div>฿1,122,600</div>
                <small class="badge bg-success text-white" style="font-size:0.75rem;">979 คน / 1,281 ครั้ง</small>
              </td>
              <td class="fw-bolder text-success fs-5">
                <div>฿1,395,260</div>
                <small class="badge bg-success text-white" style="font-size:0.75rem;">998 คน / 1,250 ครั้ง</small>
              </td>
              <td>
                <div class="fw-bolder text-success fs-6"><i class="bx bx-up-arrow-alt"></i> เงิน +227.8%</div>
                <small class="fw-bold text-success"><i class="bx bx-up-arrow-alt"></i> +60.1% (ครั้ง)</small>
              </td>
            </tr>

            <!-- หมวด 14: ส่วนหัวแยกกลุ่มงาน -->
            <tr style="background-color: #f1f5f9;" class="border-top border-2">
              <td colspan="7" class="text-start fw-bolder text-dark py-2">
                <i class="bx bx-donate-heart me-1 text-primary"></i> <b>ค่าบริการทางกายภาพบำบัดและเวชศาสตร์ฟื้นฟู (HOSxP หมวด 14) - แยกรายกลุ่มงาน (ยอดเงิน & จำนวนคน / ครั้ง)</b>
              </td>
            </tr>

            <!-- 8. กายภาพบำบัด (หมวด 14) -->
            <tr>
              <td class="fw-bold text-start text-primary">
                <i class="bx bx-accessibility me-1"></i> 8. กายภาพบำบัด (ค่าบริการหมวด 14)
              </td>
              <td class="fw-semibold text-dark">
                <div>฿437,990</div>
                <small class="badge bg-light text-primary border" style="font-size:0.75rem;">325 คน / 1,157 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿870,260</div>
                <small class="badge bg-light text-primary border" style="font-size:0.75rem;">747 คน / 2,324 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿1,235,940</div>
                <small class="badge bg-light text-primary border" style="font-size:0.75rem;">1,696 คน / 3,253 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿5,868,975</div>
                <small class="badge bg-light text-primary border" style="font-size:0.75rem;">1,921 คน / 4,677 ครั้ง</small>
              </td>
              <td class="fw-bold text-primary fs-6">
                <div>฿4,783,425</div>
                <small class="badge bg-light text-primary border" style="font-size:0.75rem;">1,378 คน / 4,464 ครั้ง</small>
              </td>
              <td>
                <div class="fw-bold text-primary"><i class="bx bx-up-arrow-alt"></i> เงิน +992.1%</div>
                <small class="text-dark fw-bold">ครั้ง +285.8%</small>
              </td>
            </tr>

            <!-- 9. ผู้ป่วยนอก OPD (หมวด 14) -->
            <tr>
              <td class="fw-bold text-start text-success">
                <i class="bx bx-clinic me-1"></i> 9. ผู้ป่วยนอก (OPD) (ค่าบริการหมวด 14)
              </td>
              <td class="fw-semibold text-dark">
                <div>฿2,670</div>
                <small class="badge bg-light text-success border" style="font-size:0.75rem;">4 คน / 8 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿14,140</div>
                <small class="badge bg-light text-success border" style="font-size:0.75rem;">18 คน / 49 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿90,220</div>
                <small class="badge bg-light text-success border" style="font-size:0.75rem;">93 คน / 246 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿30,880</div>
                <small class="badge bg-light text-success border" style="font-size:0.75rem;">45 คน / 90 ครั้ง</small>
              </td>
              <td class="fw-bold text-success fs-6">
                <div>฿824,040</div>
                <small class="badge bg-light text-success border" style="font-size:0.75rem;">1,117 คน / 1,243 ครั้ง</small>
              </td>
              <td>
                <div class="fw-bold text-success"><i class="bx bx-up-arrow-alt"></i> เงิน +30,763%</div>
                <small class="text-dark fw-bold">ครั้ง +15,438%</small>
              </td>
            </tr>

            <!-- 10. ผู้ป่วยใน IPD (หมวด 14) -->
            <tr>
              <td class="fw-bold text-start text-warning">
                <i class="bx bx-bed me-1"></i> 10. ผู้ป่วยใน (IPD) (ค่าบริการหมวด 14)
              </td>
              <td class="fw-semibold text-dark">
                <div>฿15,750</div>
                <small class="badge bg-light text-dark border" style="font-size:0.75rem;">122 คน / 129 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿11,020</div>
                <small class="badge bg-light text-dark border" style="font-size:0.75rem;">80 คน / 84 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿7,280</div>
                <small class="badge bg-light text-dark border" style="font-size:0.75rem;">34 คน / 35 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿8,480</div>
                <small class="badge bg-light text-dark border" style="font-size:0.75rem;">29 คน / 29 ครั้ง</small>
              </td>
              <td class="fw-bold text-warning fs-6">
                <div>฿17,090</div>
                <small class="badge bg-light text-dark border" style="font-size:0.75rem;">28 คน / 28 ครั้ง</small>
              </td>
              <td>
                <div class="fw-bold text-warning"><i class="bx bx-up-arrow-alt"></i> เงิน +8.5%</div>
                <small class="text-danger fw-bold">ครั้ง -78.3%</small>
              </td>
            </tr>

            <!-- 11. อุบัติเหตุและฉุกเฉิน ER (หมวด 14) -->
            <tr>
              <td class="fw-bold text-start text-danger">
                <i class="bx bx-plus-medical me-1"></i> 11. อุบัติเหตุและฉุกเฉิน (ER) (ค่าบริการหมวด 14)
              </td>
              <td class="fw-semibold text-dark">
                <div>฿1,120</div>
                <small class="badge bg-light text-danger border" style="font-size:0.75rem;">11 คน / 11 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿400</div>
                <small class="badge bg-light text-danger border" style="font-size:0.75rem;">7 คน / 7 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿4,620</div>
                <small class="badge bg-light text-danger border" style="font-size:0.75rem;">26 คน / 27 ครั้ง</small>
              </td>
              <td class="fw-semibold text-dark">
                <div>฿7,980</div>
                <small class="badge bg-light text-danger border" style="font-size:0.75rem;">25 คน / 29 ครั้ง</small>
              </td>
              <td class="fw-bold text-danger fs-6">
                <div>฿3,330</div>
                <small class="badge bg-light text-danger border" style="font-size:0.75rem;">7 คน / 7 ครั้ง</small>
              </td>
              <td>
                <div class="fw-bold text-danger"><i class="bx bx-up-arrow-alt"></i> เงิน +197.3%</div>
                <small class="text-danger fw-bold">ครั้ง -36.4%</small>
              </td>
            </tr>

            <!-- 12. รวมค่าบริการหมวด 14 ทั้งโรงพยาบาล -->
            <tr style="background-color: #eef2ff;" class="border-top border-2">
              <td class="fw-bolder text-start text-dark">
                <i class="bx bx-donate-heart me-1 text-primary"></i> 12. รวมค่าบริการกายภาพบำบัดทั้งสิ้น (HOSxP หมวด 14)
              </td>
              <td class="fw-bold text-dark">
                <div>฿457,530</div>
                <small class="badge bg-primary text-white" style="font-size:0.75rem;">438 คน / 1,287 ครั้ง</small>
              </td>
              <td class="fw-bold text-dark">
                <div>฿895,820</div>
                <small class="badge bg-primary text-white" style="font-size:0.75rem;">836 คน / 2,455 ครั้ง</small>
              </td>
              <td class="fw-bold text-dark">
                <div>฿1,338,060</div>
                <small class="badge bg-primary text-white" style="font-size:0.75rem;">1,828 คน / 3,553 ครั้ง</small>
              </td>
              <td class="fw-bold text-dark">
                <div>฿5,916,315</div>
                <small class="badge bg-primary text-white" style="font-size:0.75rem;">1,993 คน / 4,819 ครั้ง</small>
              </td>
              <td class="fw-bolder text-primary fs-5">
                <div>฿5,627,885</div>
                <small class="badge bg-primary text-white" style="font-size:0.75rem;">2,361 คน / 5,732 ครั้ง</small>
              </td>
              <td>
                <div class="fw-bolder text-primary fs-6"><i class="bx bx-up-arrow-alt"></i> เงิน +1,129.8%</div>
                <small class="text-primary fw-bolder">ครั้ง +345.4%</small>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- SLIDE 2: ผลการเบิกชดเชยอุปกรณ์ ควบคู่กับสิทธิการรักษา & TOP 3   -->
    <!-- ============================================================== -->
    <div id="slide-section-2" class="slide-content-view" style="display: none;">
      <!-- Title Header -->
      <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-3 mb-4">
        <div class="d-flex align-items-center gap-3">
          <div class="avatar avatar-lg bg-label-success rounded-circle d-flex align-items-center justify-content-center shadow-sm">
            <i class="bx bx-shield-quarter fs-2 text-success"></i>
          </div>
          <div>
            <h3 class="fw-bolder text-dark mb-0">
              ผลการเบิกชดเชยอุปกรณ์ <span class="text-primary">ควบคู่สิทธิการรักษา & 10 อันดับสูงสุด (TOP 10)</span>
            </h3>
            <small class="text-dark fw-bold">วิเคราะห์แยกตามกองทุนหลัก (UC, OFC, LGO, SSS) และอุปกรณ์มูลค่าสูงสุด • โรงพยาบาลโพนทราย</small>
          </div>
        </div>
        <div class="badge bg-label-primary px-3 py-2 fs-6">
          <i class="bx bx-check-double me-1"></i> สิทธิที่มีการเรียกเก็บและชดเชยจริง
        </div>
      </div>

      <div class="row g-4">
        <!-- Col Left: ผลการเบิกชดเชยแยกตามสิทธิการรักษา -->
        <div class="col-lg-6">
          <div class="card border p-3 bg-white shadow-sm h-100 rounded-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h6 class="fw-bold text-dark mb-0">
                <i class="bx bx-pie-chart-alt me-1 text-primary"></i> 1. สรุปผลการเบิกชดเชยแยกตามกองทุน / สิทธิการรักษา
              </h6>
              <span class="badge bg-dark rounded-pill px-2 py-1">รอบปีงบประมาณ 2569</span>
            </div>

            <!-- Rights Breakdown Table -->
            <div class="table-responsive mb-3">
              <table class="table table-sm table-bordered align-middle text-center mb-0">
                <thead style="background-color: #f8fafc;">
                  <tr>
                    <th class="text-start text-dark fw-bold">สิทธิ / กองทุน</th>
                    <th class="text-end text-dark fw-bold">ผู้ป่วย</th>
                    <th class="text-end text-dark fw-bold">เรียกเก็บ (บาท)</th>
                    <th class="text-end text-dark fw-bold">ชดเชยจริง (บาท)</th>
                    <th class="text-end text-dark fw-bold">ค้างรับ (บาท)</th>
                    <th class="text-center text-dark fw-bold">% สำเร็จ</th>
                  </tr>
                </thead>
                <tbody id="slide2_tbody_rights">
                  <tr>
                    <td class="text-start fw-bold text-success"><i class="bx bx-circle me-1 text-success"></i> UC สปสช. (บัตรทอง / ท.74)</td>
                    <td class="text-end fw-semibold">630</td>
                    <td class="text-end fw-bold text-dark">฿668,930</td>
                    <td class="text-end fw-bold text-success">฿782,193</td>
                    <td class="text-end fw-bold text-danger">฿681</td>
                    <td class="text-center"><span class="badge bg-success">116.9%</span></td>
                  </tr>
                  <tr>
                    <td class="text-start fw-bold text-primary"><i class="bx bx-circle me-1 text-primary"></i> OFC เบิกจ่ายตรงข้าราชการ</td>
                    <td class="text-end fw-semibold">181</td>
                    <td class="text-end fw-bold text-dark">฿519,160</td>
                    <td class="text-end fw-bold text-success">฿857,216</td>
                    <td class="text-end fw-bold text-danger">฿14,120</td>
                    <td class="text-center"><span class="badge bg-success">165.1%</span></td>
                  </tr>
                  <tr>
                    <td class="text-start fw-bold text-info"><i class="bx bx-circle me-1 text-info"></i> LGO เบิกจ่ายตรง อปท.</td>
                    <td class="text-end fw-semibold">52</td>
                    <td class="text-end fw-bold text-dark">฿108,830</td>
                    <td class="text-end fw-bold text-success">฿40,240</td>
                    <td class="text-end fw-bold text-danger">฿74,340</td>
                    <td class="text-center"><span class="badge bg-primary">37.0%</span></td>
                  </tr>
                  <tr>
                    <td class="text-start fw-bold text-warning"><i class="bx bx-circle me-1 text-warning"></i> SSS ประกันสังคม</td>
                    <td class="text-end fw-semibold">44</td>
                    <td class="text-end fw-bold text-dark">฿41,600</td>
                    <td class="text-end fw-bold text-success">฿29,050</td>
                    <td class="text-end fw-bold text-danger">฿12,550</td>
                    <td class="text-center"><span class="badge bg-primary">69.8%</span></td>
                  </tr>
                  <tr>
                    <td class="text-start fw-bold text-secondary"><i class="bx bx-circle me-1 text-secondary"></i> OTHER พรบ. / อื่นๆ</td>
                    <td class="text-end fw-semibold">43</td>
                    <td class="text-end fw-bold text-dark">฿13,240</td>
                    <td class="text-end fw-bold text-success">฿11,184</td>
                    <td class="text-end fw-bold text-danger">฿3,150</td>
                    <td class="text-center"><span class="badge bg-success">84.5%</span></td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Mini Visual Rights Bar Chart -->
            <div id="slide2_rights_chart" style="height: 220px;"></div>
          </div>
        </div>

        <!-- Col Right: 10 อันดับอุปกรณ์ที่มีมูลค่าสูงสุด (Top 10) -->
        <div class="col-lg-6">
          <div class="card border p-3 bg-white shadow-sm h-100 rounded-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <h6 class="fw-bold text-dark mb-0">
                <i class="bx bx-award me-1 text-warning"></i> 2. 10 อันดับอุปกรณ์ที่มีมูลค่าสูงสุด (TOP 10)
              </h6>
              <span class="badge bg-label-primary fw-bold" style="font-size:0.75rem;">Top 10 Equipment</span>
            </div>
            <small class="text-muted d-block mb-3">จัดอันดับตามมูลค่าเรียกเก็บและชดเชยรวม (คลิกดูรายชื่อผู้ป่วย)</small>

            <div id="slide2_top10_container" style="max-height: 490px; overflow-y: auto; padding-right: 4px;">
              <!-- Item 1 -->
              <div class="p-2 mb-2 border rounded-3 bg-white shadow-sm hover-elevate position-relative" style="cursor: pointer; border-left: 5px solid #f43f5e !important;" onclick="openDevicePatientsModal('8307', 'L-S Support')" title="คลิกดูรายชื่อผู้ป่วยที่ได้รับอุปกรณ์ L-S Support">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <div class="d-flex align-items-center gap-2 text-truncate" style="max-width: 75%;">
                    <span class="badge bg-danger rounded-circle p-1 px-2 fw-bold" style="font-size: 0.75rem;">1</span>
                    <span class="badge bg-dark fw-bold" style="font-size: 0.7rem;">ADP: 8307</span>
                    <strong class="text-dark text-truncate small" title="L-S Support (กายอุปกรณ์ประคองเอวและหลัง)">L-S Support (ประคองเอวและหลัง)</strong>
                  </div>
                  <span class="badge bg-success" style="font-size: 0.72rem;">ชดเชย 109.3%</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-1 border-top" style="font-size: 0.75rem;">
                  <span class="text-dark">จ่าย: <strong class="text-dark">433 ชิ้น (385 คน)</strong> | ชดเชย: <strong class="text-success">฿473,423</strong></span>
                  <strong class="text-primary">฿433,000</strong>
                </div>
              </div>

              <!-- Item 2 -->
              <div class="p-2 mb-2 border rounded-3 bg-white shadow-sm hover-elevate position-relative" style="cursor: pointer; border-left: 5px solid #3b82f6 !important;" onclick="openDevicePatientsModal('8610', '8610 OFC-อุปกรณ์พยุงส้นเท้าและฝ้าเท้าชนิดหล่อพิเศษเฉพาะราย')" title="คลิกดูรายชื่อผู้ป่วยที่ได้รับอุปกรณ์ 8610">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <div class="d-flex align-items-center gap-2 text-truncate" style="max-width: 75%;">
                    <span class="badge bg-primary rounded-circle p-1 px-2 fw-bold" style="font-size: 0.75rem;">2</span>
                    <span class="badge bg-dark fw-bold" style="font-size: 0.7rem;">ADP: 8610</span>
                    <strong class="text-dark text-truncate small" title="อุปกรณ์พยุงส้นเท้าและฝ่าเท้าชนิดหล่อพิเศษเฉพาะราย">พยุงส้นเท้า/ฝ่าเท้าหล่อพิเศษเฉพาะราย</strong>
                  </div>
                  <span class="badge bg-success" style="font-size: 0.72rem;">ชดเชย 123.2%</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-1 border-top" style="font-size: 0.75rem;">
                  <span class="text-dark">จ่าย: <strong class="text-dark">206 ชิ้น (103 คน)</strong> | ชดเชย: <strong class="text-success">฿507,410</strong></span>
                  <strong class="text-primary">฿412,000</strong>
                </div>
              </div>

              <!-- Item 3 -->
              <div class="p-2 mb-2 border rounded-3 bg-white shadow-sm hover-elevate position-relative" style="cursor: pointer; border-left: 5px solid #eab308 !important;" onclick="openDevicePatientsModal('8902', 'รถนั่งคนพิการ แบบปรับไม่ได้')" title="คลิกดูรายชื่อผู้ป่วยที่ได้รับรถนั่งคนพิการ">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <div class="d-flex align-items-center gap-2 text-truncate" style="max-width: 75%;">
                    <span class="badge bg-warning text-dark rounded-circle p-1 px-2 fw-bold" style="font-size: 0.75rem;">3</span>
                    <span class="badge bg-dark fw-bold" style="font-size: 0.7rem;">ADP: 8902</span>
                    <strong class="text-dark text-truncate small" title="รถนั่งคนพิการ แบบปรับไม่ได้ (Wheelchair)">รถนั่งคนพิการ แบบปรับไม่ได้ (Wheelchair)</strong>
                  </div>
                  <span class="badge bg-success" style="font-size: 0.72rem;">ชดเชย 103.1%</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-1 border-top" style="font-size: 0.75rem;">
                  <span class="text-dark">จ่าย: <strong class="text-dark">43 ชิ้น (37 คน)</strong> | ชดเชย: <strong class="text-success">฿195,070</strong></span>
                  <strong class="text-primary">฿189,200</strong>
                </div>
              </div>

              <!-- Item 4 -->
              <div class="p-2 mb-2 border rounded-3 bg-white shadow-sm hover-elevate" style="cursor: pointer;" onclick="openDevicePatientsModal('8608', 'Knee Support')" title="คลิกดูรายชื่อผู้ป่วยที่ได้รับ Knee Support">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <div class="d-flex align-items-center gap-2 text-truncate" style="max-width: 75%;">
                    <span class="badge bg-secondary rounded-circle p-1 px-2 fw-bold" style="font-size: 0.75rem;">4</span>
                    <span class="badge bg-dark fw-bold" style="font-size: 0.7rem;">ADP: 8608</span>
                    <strong class="text-dark text-truncate small" title="Knee Support">Knee Support (ผ้ายืดพยุงเข่า)</strong>
                  </div>
                  <span class="badge bg-success" style="font-size: 0.72rem;">ชดเชย 126.1%</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-1 border-top" style="font-size: 0.75rem;">
                  <span class="text-dark">จ่าย: <strong class="text-dark">132 ชิ้น (82 คน)</strong> | ชดเชย: <strong class="text-success">฿133,190</strong></span>
                  <strong class="text-primary">฿105,600</strong>
                </div>
              </div>

              <!-- Item 5 -->
              <div class="p-2 mb-2 border rounded-3 bg-white shadow-sm hover-elevate" style="cursor: pointer;" onclick="openDevicePatientsModal('8704', 'ไม้เท้าชนิด 3 หรือ 4 ปุ่ม')" title="คลิกดูรายชื่อผู้ป่วยที่ได้รับไม้เท้า 3-4 ปุ่ม">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <div class="d-flex align-items-center gap-2 text-truncate" style="max-width: 75%;">
                    <span class="badge bg-secondary rounded-circle p-1 px-2 fw-bold" style="font-size: 0.75rem;">5</span>
                    <span class="badge bg-dark fw-bold" style="font-size: 0.7rem;">ADP: 8704</span>
                    <strong class="text-dark text-truncate small" title="ไม้เท้าชนิด 3 หรือ 4 ปุ่ม">ไม้เท้าชนิด 3 หรือ 4 ปุ่ม</strong>
                  </div>
                  <span class="badge bg-success" style="font-size: 0.72rem;">ชดเชย 99.0%</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-1 border-top" style="font-size: 0.75rem;">
                  <span class="text-dark">จ่าย: <strong class="text-dark">66 ชิ้น (61 คน)</strong> | ชดเชย: <strong class="text-success">฿39,189</strong></span>
                  <strong class="text-primary">฿39,600</strong>
                </div>
              </div>

              <!-- Item 6 -->
              <div class="p-2 mb-2 border rounded-3 bg-white shadow-sm hover-elevate" style="cursor: pointer;" onclick="openDevicePatientsModal('8701', 'เครื่องช่วยเดินชนิด 4 ขา')" title="คลิกดูรายชื่อผู้ป่วยที่ได้รับ Walker">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <div class="d-flex align-items-center gap-2 text-truncate" style="max-width: 75%;">
                    <span class="badge bg-secondary rounded-circle p-1 px-2 fw-bold" style="font-size: 0.75rem;">6</span>
                    <span class="badge bg-dark fw-bold" style="font-size: 0.7rem;">ADP: 8701</span>
                    <strong class="text-dark text-truncate small" title="เครื่องช่วยเดินชนิด 4 ขา (pick-up-walker)">เครื่องช่วยเดินชนิด 4 ขา (Walker)</strong>
                  </div>
                  <span class="badge bg-success" style="font-size: 0.72rem;">ชดเชย 199.7%</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-1 border-top" style="font-size: 0.75rem;">
                  <span class="text-dark">จ่าย: <strong class="text-dark">46 ชิ้น (39 คน)</strong> | ชดเชย: <strong class="text-success">฿64,316</strong></span>
                  <strong class="text-primary">฿32,200</strong>
                </div>
              </div>

              <!-- Item 7 -->
              <div class="p-2 mb-2 border rounded-3 bg-white shadow-sm hover-elevate" style="cursor: pointer;" onclick="openDevicePatientsModal('8703', 'ไม้เท้า 1 ปุ่ม')" title="คลิกดูรายชื่อผู้ป่วย">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <div class="d-flex align-items-center gap-2 text-truncate" style="max-width: 75%;">
                    <span class="badge bg-secondary rounded-circle p-1 px-2 fw-bold" style="font-size: 0.75rem;">7</span>
                    <span class="badge bg-dark fw-bold" style="font-size: 0.7rem;">ADP: 8703</span>
                    <strong class="text-dark text-truncate small" title="ไม้เท้า 1 ปุ่ม">ไม้เท้า 1 ปุ่ม</strong>
                  </div>
                  <span class="badge bg-success" style="font-size: 0.72rem;">ชดเชย 158.9%</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-1 border-top" style="font-size: 0.75rem;">
                  <span class="text-dark">จ่าย: <strong class="text-dark">90 ชิ้น (78 คน)</strong> | ชดเชย: <strong class="text-success">฿27,169</strong></span>
                  <strong class="text-primary">฿17,100</strong>
                </div>
              </div>

              <!-- Item 8 -->
              <div class="p-2 mb-2 border rounded-3 bg-white shadow-sm hover-elevate" style="cursor: pointer;" onclick="openDevicePatientsModal('8602', '/ARM SLING NO.M')" title="คลิกดูรายชื่อผู้ป่วย">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <div class="d-flex align-items-center gap-2 text-truncate" style="max-width: 75%;">
                    <span class="badge bg-secondary rounded-circle p-1 px-2 fw-bold" style="font-size: 0.75rem;">8</span>
                    <span class="badge bg-dark fw-bold" style="font-size: 0.7rem;">ADP: 8602</span>
                    <strong class="text-dark text-truncate small" title="/ARM SLING NO.M">ARM SLING (ผ้ายกแขน)</strong>
                  </div>
                  <span class="badge bg-success" style="font-size: 0.72rem;">ชดเชย 147.0%</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-1 border-top" style="font-size: 0.75rem;">
                  <span class="text-dark">จ่าย: <strong class="text-dark">101 ชิ้น (89 คน)</strong> | ชดเชย: <strong class="text-success">฿22,274</strong></span>
                  <strong class="text-primary">฿15,150</strong>
                </div>
              </div>

              <!-- Item 9 -->
              <div class="p-2 mb-2 border rounded-3 bg-white shadow-sm hover-elevate" style="cursor: pointer;" onclick="openDevicePatientsModal('8707', 'ไม้เท้าอลูมิเนียมแบบสามขา')" title="คลิกดูรายชื่อผู้ป่วย">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <div class="d-flex align-items-center gap-2 text-truncate" style="max-width: 75%;">
                    <span class="badge bg-secondary rounded-circle p-1 px-2 fw-bold" style="font-size: 0.75rem;">9</span>
                    <span class="badge bg-dark fw-bold" style="font-size: 0.7rem;">ADP: 8707</span>
                    <strong class="text-dark text-truncate small" title="ไม้เท้าอลูมิเนียมแบบสามขา">ไม้เท้าอลูมิเนียมแบบ 3 ขา</strong>
                  </div>
                  <span class="badge bg-success" style="font-size: 0.72rem;">ชดเชย 172.7%</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-1 border-top" style="font-size: 0.75rem;">
                  <span class="text-dark">จ่าย: <strong class="text-dark">11 ชิ้น (11 คน)</strong> | ชดเชย: <strong class="text-success">฿11,396</strong></span>
                  <strong class="text-primary">฿6,600</strong>
                </div>
              </div>

              <!-- Item 10 -->
              <div class="p-2 mb-2 border rounded-3 bg-white shadow-sm hover-elevate" style="cursor: pointer;" onclick="openDevicePatientsModal('8711', 'ไม้ค้ำยันรักแร้แบบไม้')" title="คลิกดูรายชื่อผู้ป่วย">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <div class="d-flex align-items-center gap-2 text-truncate" style="max-width: 75%;">
                    <span class="badge bg-secondary rounded-circle p-1 px-2 fw-bold" style="font-size: 0.75rem;">10</span>
                    <span class="badge bg-dark fw-bold" style="font-size: 0.7rem;">ADP: 8711</span>
                    <strong class="text-dark text-truncate small" title="ไม้ค้ำยันรักแร้แบบไม้">ไม้ค้ำยันรักแร้แบบไม้</strong>
                  </div>
                  <span class="badge bg-success" style="font-size: 0.72rem;">ชดเชย 195.1%</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-1 border-top" style="font-size: 0.75rem;">
                  <span class="text-dark">จ่าย: <strong class="text-dark">14 ชิ้น (14 คน)</strong> | ชดเชย: <strong class="text-success">฿9,558</strong></span>
                  <strong class="text-primary">฿4,900</strong>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- SLIDE 3: จุดรั่วไหลของรายได้ & ยุทธศาสตร์ดึงเงินคืน รพ. (EXECUTIVE) -->
    <!-- ============================================================== -->
    <div id="slide-section-3" class="slide-content-view" style="display: none;">
      <!-- Title Header -->
      <div class="d-flex flex-wrap justify-content-between align-items-center border-bottom pb-3 mb-4">
        <div class="d-flex align-items-center gap-3">
          <div class="avatar avatar-lg bg-label-danger rounded-circle d-flex align-items-center justify-content-center shadow-sm">
            <i class="bx bx-shield-x fs-2 text-danger"></i>
          </div>
          <div>
            <h3 class="fw-bolder text-dark mb-0">
              จุดรั่วไหลของรายได้ & ยุทธศาสตร์ <span class="text-danger">การดึงเงินกลับคืน รพ.</span>
            </h3>
            <small class="text-dark fw-bold">สิ่งที่ผู้บริหารต้องทราบเพื่ออุดรอยรั่วและเร่งรัดรายได้ • กลุ่มงานเวชกรรมฟื้นฟู รพ.โพนทราย</small>
          </div>
        </div>
        <div class="badge bg-danger px-3 py-2 fs-6 text-white" id="slide3_opportunity_badge">
          <i class="bx bx-bell me-1"></i> โอกาสดึงเงินกลับคืน รพ. กว่า ฿104,640
        </div>
      </div>

      <!-- 3 Financial Pipeline Status Cards -->
      <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
          <div class="card border p-3 bg-white shadow-sm rounded-3 border-secondary h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="badge bg-secondary">สถานะ 1: ค้างท่อในระบบ</span>
              <i class="bx bx-time fs-4 text-secondary"></i>
            </div>
            <h6 class="fw-bold text-dark mb-1">รอผลตรวจสอบ (Pending Audit)</h6>
            <h3 class="fw-bolder text-dark my-1" id="slide3_unclaimed_val">฿104,640</h3>
            <small class="text-danger fw-bold"><i class="bx bx-error-circle me-1"></i>รายการค้างส่งใน HOSxP ต้องเร่งส่ง e-Claim ทันรอบ</small>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <div class="card border p-3 bg-white shadow-sm rounded-3 border-warning h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="badge bg-warning text-dark">สถานะ 2: รอ Statement</span>
              <i class="bx bx-transfer fs-4 text-warning"></i>
            </div>
            <h6 class="fw-bold text-dark mb-1">รอผลชดเชย (Wait STM)</h6>
            <h3 class="fw-bolder text-warning my-1" id="slide3_wait_val">฿0</h3>
            <small class="text-dark fw-semibold"><i class="bx bx-check me-1"></i>ส่งข้อมูลแล้ว อยู่ในรอบการประมวลผลกองทุน</small>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <div class="card border p-3 bg-white shadow-sm rounded-3 border-danger h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="badge bg-danger">สถานะ 3: เคลมไม่ผ่าน / ปฏิเสธ</span>
              <i class="bx bx-x-circle fs-4 text-danger"></i>
            </div>
            <h6 class="fw-bold text-dark mb-1">ถูกปฏิเสธ (Rejected / Audit)</h6>
            <h3 class="fw-bolder text-danger my-1" id="slide3_rejected_val">฿0</h3>
            <small class="text-success fw-bold"><i class="bx bx-bulb me-1"></i>แก้ไขเอกสาร/รหัส แล้ว Re-submit ดึงเงินกลับได้ 100%</small>
          </div>
        </div>
      </div>

      <!-- Action Plan Grid: 3 สาเหตุยอดฮิต & สิ่งที่ผู้บริหารสั่งการได้ทันที -->
      <div class="row g-3">
        <div class="col-lg-7">
          <div class="card border p-3 bg-white shadow-sm h-100 rounded-3">
            <h6 class="fw-bold text-dark mb-3">
              <i class="bx bx-target-lock me-1 text-danger"></i> 3 สาเหตุหลักที่ทำให้เงินถูกปฏิเสธ (Top Denial Causes)
            </h6>
            
            <div id="slide3_denial_causes_container">
              <div class="p-3 mb-2 border rounded-3 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <strong class="text-danger"><i class="bx bx-error me-1"></i> 1. รหัสอุปกรณ์ไม่ตรงตามประกาศ (D179 / 179)</strong>
                  <span class="badge bg-success">โอกาสผ่านสูง (RETRY)</span>
                </div>
                <small class="text-dark d-block">
                  <strong>สาเหตุ:</strong> รหัสอุปกรณ์ใน HOSxP ยังเป็นรหัสเดิม หรือเลือกหมวดหมู่อุปกรณ์ไม่ตรงกับที่ สปสช. กำหนด<br>
                  <strong class="text-primary">แนวทางแก้ไข:</strong> ปรับปรุงรหัส ADP ในระบบ HOSxP ให้ตรงกับหมวด 2 ล่าสุด และแนบใบประเมินส่งซ้ำ
                </small>
              </div>

              <div class="p-3 mb-2 border rounded-3 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <strong class="text-danger"><i class="bx bx-error me-1"></i> 2. รหัสโรคหลัก (PDX) ไม่สอดคล้องกับข้อบ่งชี้ (D349 / E01)</strong>
                  <span class="badge bg-success">โอกาสผ่านสูง (RETRY)</span>
                </div>
                <small class="text-dark d-block">
                  <strong>สาเหตุ:</strong> แพทย์ระบุรหัสโรคทั่วไป (Z-code) เช่น มาขออุปกรณ์ แทนที่จะเป็นรหัสโรคเฉพาะ (M54.5 ปวดหลัง, G81 อัมพาต)<br>
                  <strong class="text-primary">แนวทางแก้ไข:</strong> ประสานงานแพทย์ผู้ตรวจปรับปรุงรหัสการวินิจฉัยโรคให้ครอบคลุมตามเกณฑ์
                </small>
              </div>

              <div class="p-3 mb-2 border rounded-3 bg-light">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <strong class="text-danger"><i class="bx bx-error me-1"></i> 3. ส่งข้อมูลเกินระยะเวลาที่กำหนด (D210 / C02)</strong>
                  <span class="badge bg-warning text-dark">ต้องยื่นอุทธรณ์ (APPEAL)</span>
                </div>
                <small class="text-dark d-block">
                  <strong>สาเหตุ:</strong> ข้อมูลตกค้างในระบบ ส่งเลยกำหนด 30 วันหลังสิ้นสุดรอบบิล<br>
                  <strong class="text-primary">แนวทางแก้ไข:</strong> จัดทำหนังสือขออุทธรณ์ชี้แจงเหตุผลความจำเป็นลงนามโดย ผอ.รพ. เพื่อขอเบิกจ่ายกรณีพิเศษ
                </small>
              </div>
            </div>

          </div>
        </div>

        <!-- Col Right: ข้อเสนอแนะเชิงนโยบายสำหรับผู้บริหาร -->
        <div class="col-lg-5">
          <div class="card border p-3 bg-white shadow-sm h-100 rounded-3" style="background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%);">
            <h6 class="fw-bold text-success mb-3">
              <i class="bx bx-check-shield me-1"></i> ข้อเสนอแนะเชิงนโยบายเพื่อเพิ่มรายได้ รพ.
            </h6>
            
            <ul class="list-unstyled mb-3 small text-dark">
              <li class="mb-3 d-flex gap-2">
                <i class="bx bxs-check-circle text-success fs-5"></i>
                <div>
                  <strong>1. กำหนด Fast-Track การส่งเบิกกายอุปกรณ์:</strong>
                  <span class="text-muted d-block">สั่งการให้ส่งออกข้อมูล e-Claim ทุกวันศุกร์ของสัปดาห์ ไม่ปล่อยให้ข้ามเดือน ป้องกันปัญหา D210</span>
                </div>
              </li>
              <li class="mb-3 d-flex gap-2">
                <i class="bx bxs-check-circle text-success fs-5"></i>
                <div>
                  <strong>2. ระบบ Auto-Sync & Audit อัตโนมัติ:</strong>
                  <span class="text-muted d-block">ระบบ eDHS เตรียมข้อมูลและ Audit ชนยอดจาก HOSxP อัตโนมัติทุกครั้งที่เปิดใช้งาน ป้องกันเคสตกหล่น 100%</span>
                </div>
              </li>
              <li class="mb-2 d-flex gap-2">
                <i class="bx bxs-check-circle text-success fs-5"></i>
                <div>
                  <strong>3. ขยายบริการคลินิกเฉพาะโรคและ Tele-Rehab:</strong>
                  <span class="text-muted d-block">เพื่อรองรับผู้ป่วยกลุ่ม IMC และกองทุนฟื้นฟูฯ เพิ่มยอดการชดเชยตามเป้าหมายปี 2570</span>
                </div>
              </li>
            </ul>

            <div class="p-3 rounded-3 bg-white border border-success-subtle text-center shadow-sm mt-auto">
              <small class="text-muted d-block">ศักยภาพการเพิ่มรายได้ รพ.โพนทราย</small>
              <h4 class="fw-bolder text-success mb-0 mt-1">+15% ถึง +20% ต่อปี</h4>
            </div>

          </div>
        </div>
      </div>
    </div> <!-- /#slide-section-3 -->
  </div> <!-- /#presentationCanvas -->
</div> <!-- /#presentationWrapper -->
</div> <!-- /#tab-presentation -->


