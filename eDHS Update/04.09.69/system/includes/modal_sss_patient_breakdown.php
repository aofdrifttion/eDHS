<!-- 🌟 Modal แสดงรายละเอียดรายการ Instrument ประกันสังคมของคนไข้รายตัว (Patient SSS Breakdown Modal) -->
<style>
/* 🔒 ล็อคขนาดและสไตล์ของ Modal SSS แยกขาดจาก Modal อื่นในระบบโดยสิ้นเชิง */
#modalSssPatientBreakdown.modal {
  padding: 0 !important;
  background: rgba(15, 23, 42, 0.45) !important;
  z-index: 1099 !important;
}

#modalSssPatientBreakdown.modal.show {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

#modalSssPatientBreakdown .modal-dialog {
  width: 95% !important;
  max-width: 1120px !important;
  min-width: 320px !important;
  height: auto !important;
  min-height: auto !important;
  max-height: 90vh !important;
  margin: auto !important;
  display: flex !important;
  flex-direction: column !important;
  align-self: center !important;
  flex: 0 1 auto !important;
}

#modalSssPatientBreakdown .modal-content {
  border: none !important;
  border-radius: 14px !important;
  background-color: #ffffff !important;
  height: auto !important;
  min-height: auto !important;
  max-height: 90vh !important;
  width: 100% !important;
  max-width: 100% !important;
  display: flex !important;
  flex-direction: column !important;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3) !important;
  overflow: hidden !important;
}

#modalSssPatientBreakdown .modal-header {
  flex-shrink: 0 !important;
  padding: 10px 16px !important;
  background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%) !important;
  color: #ffffff !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.15) !important;
}

#modalSssPatientBreakdown .modal-body {
  flex: 1 1 auto !important;
  max-height: calc(92vh - 110px) !important;
  overflow-y: auto !important;
  padding: 12px 16px !important;
  background: #f8fafc !important;
}

#modalSssPatientBreakdown .modal-footer {
  flex-shrink: 0 !important;
  padding: 8px 16px !important;
  background: #f1f5f9 !important;
  border-top: 1px solid #e2e8f0 !important;
}

#modalSssPatientBreakdown .sss-info-card {
  border-radius: 10px !important;
  background: #ffffff !important;
  border: 1px solid #e2e8f0 !important;
}

#modalSssPatientBreakdown .sss-kpi-card {
  border-radius: 8px !important;
  padding: 8px !important;
  text-align: center !important;
  background: #ffffff !important;
  border: 1px solid #e2e8f0 !important;
}

#modalSssPatientBreakdown .sss-kpi-card.highlight {
  background: #eff6ff !important;
  border-color: #93c5fd !important;
}

#modalSssPatientBreakdown .text-muted,
#modalSssPatientBreakdown thead th,
#modalSssPatientBreakdown thead tr {
  color: #1e3a8a !important;
}
</style>

<div class="modal fade" id="modalSssPatientBreakdown" tabindex="-1" aria-labelledby="modalSssPatientBreakdownLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      
      <!-- Header -->
      <div class="modal-header">
        <div class="d-flex align-items-center gap-2.5">
          <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center shadow-xs" style="width: 34px; height: 34px; min-width: 34px;">
            <i class='bx bx-cube-alt fs-5'></i>
          </div>
          <div>
            <h6 class="modal-title fw-bold mb-0 text-white" id="modalSssPatientBreakdownLabel" style="font-size: 15px;">
              รายละเอียดรายการและค่ารักษาที่จัดเข้าหมวด Instrument (ประกันสังคม)
            </h6>
            <small class="text-white-50" style="font-size: 11.5px;">ข้อมูลจำแนกอุปกรณ์/อวัยวะเทียม และการตัดแยกยอดลูกหนี้</small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="font-size: 11px;"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        
        <!-- ข้อมูลคนไข้ & ผังบัญชี -->
        <div class="card border-0 sss-info-card shadow-xs mb-2">
          <div class="card-body p-2">
            <div class="row g-1.5 align-items-center" style="font-size: 13px;">
              <div class="col-sm-6 col-md-5">
                <span class="text-muted small">ชื่อผู้ป่วย:</span> 
                <strong class="text-dark ms-1" id="sssModalPtName" style="font-size: 13.5px;">-</strong>
              </div>
              <div class="col-sm-3 col-md-4">
                <span class="text-muted small">VN/AN:</span> 
                <strong class="text-primary font-monospace ms-1" id="sssModalVn">-</strong>
              </div>
              <div class="col-sm-3 col-md-3">
                <span class="text-muted small">HN:</span> 
                <strong class="text-dark font-monospace ms-1" id="sssModalHn">-</strong>
              </div>
              <div class="col-sm-6 col-md-4">
                <span class="text-muted small">วันที่รับบริการ:</span> 
                <span class="text-dark fw-semibold ms-1" id="sssModalVstDate">-</span>
              </div>
              <div class="col-sm-6 col-md-4">
                <span class="text-muted small">สิทธิ:</span> 
                <span class="text-dark fw-semibold ms-1" id="sssModalPttype">-</span>
              </div>
              <div class="col-sm-12 col-md-4">
                <span class="text-muted small">Hospmain:</span> 
                <span class="text-dark fw-semibold ms-1" id="sssModalHospmain">-</span>
              </div>
              <div class="col-12 pt-1 border-top mt-1 d-flex align-items-center flex-wrap gap-1.5" style="font-size: 12px;">
                <span class="text-muted">เส้นทางการโอน:</span> 
                <span class="badge bg-label-info font-monospace py-0.5 px-2" id="sssModalOriginAcc" style="font-size: 11px;">ผังต้นทาง: -</span>
                <i class='bx bx-right-arrow-alt text-muted'></i>
                <span class="badge bg-label-primary font-monospace py-0.5 px-2" id="sssModalTargetAcc" style="font-size: 11px;">ผังปลายทาง: ประกันสังคม Instrument</span>
              </div>
            </div>
          </div>
        </div>

        <!-- การ์ด 5 สรุปยอดเงิน (Compact Mini Cards) -->
        <div class="row row-cols-2 row-cols-md-5 g-2 mb-2">
          <div class="col">
            <div class="sss-kpi-card shadow-xs h-100">
              <div class="text-muted small mb-0.5" style="font-size: 11px;">ค่าใช้จ่ายรวม</div>
              <div class="fw-bold text-secondary" id="sssModalIncome" style="font-size: 14px;">฿0.00</div>
            </div>
          </div>
          <div class="col">
            <div class="sss-kpi-card shadow-xs h-100">
              <div class="text-muted small mb-0.5" style="font-size: 11px;">ชำระแล้ว</div>
              <div class="fw-bold text-muted" id="sssModalPaid" style="font-size: 14px;">฿0.00</div>
            </div>
          </div>
          <div class="col">
            <div class="sss-kpi-card shadow-xs h-100">
              <div class="text-muted small mb-0.5" style="font-size: 11px;">ภาระหนี้เดิม</div>
              <div class="fw-bold text-dark" id="sssModalDebitOriginal" style="font-size: 14px;">฿0.00</div>
            </div>
          </div>
          <div class="col">
            <div class="sss-kpi-card highlight shadow-xs h-100">
              <div class="text-primary small fw-semibold mb-0.5" style="font-size: 11px;">ยอดจัดเข้า Instrument</div>
              <div class="fw-bold text-primary" id="sssModalSssAmount" style="font-size: 14px;">฿0.00</div>
            </div>
          </div>
          <div class="col">
            <div class="sss-kpi-card shadow-xs h-100">
              <div class="text-muted small mb-0.5" style="font-size: 11px;">คงเหลือในผังเดิม</div>
              <div class="fw-bold text-success" id="sssModalRemainDebit" style="font-size: 14px;">฿0.00</div>
            </div>
          </div>
        </div>

        <!-- ตารางแจกแจงรายการ Instrument -->
        <div class="card border-0 sss-info-card shadow-xs" style="overflow: hidden;">
          <div class="card-header bg-white py-1.5 px-2.5 border-bottom d-flex align-items-center justify-content-between">
            <span class="fw-bold text-dark" style="font-size: 12.5px;">
              <i class='bx bx-list-check text-primary me-1'></i> รายการอุปกรณ์และอวัยวะเทียม (Instrument)
            </span>
            <span class="badge bg-primary py-0.5 px-2" id="sssModalItemsCount" style="font-size: 11px;">0 รายการ</span>
          </div>
          <div class="table-responsive" style="max-height: 38vh; min-height: 100px;">
            <table class="table table-hover table-striped table-bordered align-middle mb-0" style="font-size: 12.5px;">
              <thead class="table-light sticky-top" style="background: #f1f5f9;">
                <tr class="text-nowrap text-muted" style="font-size: 11.5px;">
                  <th class="text-center" style="width: 36px;">#</th>
                  <th style="width: 125px;">กลุ่มย่อย SSS</th>
                  <th style="width: 80px;">ICODE</th>
                  <th>ชื่อรายการ / อุปกรณ์</th>
                  <th style="width: 80px;">รหัส ADP</th>
                  <th class="text-center" style="width: 55px;">จำนวน</th>
                  <th class="text-end" style="width: 90px;">ราคา/หน่วย</th>
                  <th class="text-end" style="width: 105px;">ยอดเงิน (บาท)</th>
                </tr>
              </thead>
              <tbody id="sssModalItemsTableBody">
                <!-- แถวจะถูกสร้างผ่าน JS -->
              </tbody>
              <tfoot class="table-light fw-bold border-top" style="background: #f8fafc;">
                <tr>
                  <td colspan="7" class="text-end" style="font-size: 12.5px;">รวมยอดรายการ Instrument ทั้งหมด:</td>
                  <td class="text-end text-primary fw-bold" id="sssModalItemsTotalAmount" style="font-size: 14px;">฿0.00</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

      </div>

      <!-- Footer -->
      <div class="modal-footer d-flex justify-content-between align-items-center">
        <small class="text-muted" style="font-size: 11px;">
          <i class='bx bx-info-circle me-0.5 text-primary'></i> ข้อมูลจำแนกตามเกณฑ์หมวดย่อยประกันสังคมของงวดบัญชี
        </small>
        <button type="button" class="btn btn-secondary btn-sm px-3 py-1" data-bs-dismiss="modal" style="border-radius: 6px; font-size: 12px;">
          <i class='bx bx-x me-0.5'></i> ปิดหน้าต่าง
        </button>
      </div>

    </div>
  </div>
</div>

<script>
if (typeof openSssPatientBreakdownModal !== 'function') {
    function openSssPatientBreakdownModal(el) {
        var raw = $(el).attr('data-sss-payload');
        if (!raw) return;
        try {
            // ย้าย modal ออกมาอยู่ที่ <body> เสมอ เพื่อป้องกันไม่ให้โดน CSS ของ Modal แม่ครอบ
            var modalDom = $('#modalSssPatientBreakdown');
            if (modalDom.length && modalDom.parent().prop('tagName') !== 'BODY') {
                $('body').append(modalDom);
            }

            var data = typeof raw === 'object' ? raw : JSON.parse(raw);
            $('#sssModalPtName').text(data.ptname || '-');
            $('#sssModalVn').text(data.vn || '-');
            $('#sssModalHn').text(data.hn || '-');
            $('#sssModalVstDate').text(data.vstdate || '-');
            $('#sssModalPttype').text(data.pttypename || '-');
            $('#sssModalHospmain').text(data.hospmain || '-');

            var origTxt = data.origin_acc || '-';
            if (origTxt.indexOf('1102050101.') !== -1) {
                origTxt = 'ผังต้นทาง: ' + origTxt.replace('1102050101.', '.');
            }
            $('#sssModalOriginAcc').text(origTxt);
            $('#sssModalTargetAcc').text('ผังปลายทาง: ' + (data.target_acc || '1102050101.309'));

            var debit = parseFloat(data.debit || 0);
            var income = parseFloat(data.income !== undefined ? data.income : debit);
            var paid = parseFloat(data.paid !== undefined ? data.paid : (income > debit ? income - debit : 0));
            var sssAmt = parseFloat(data.sss_amount || 0);
            var remain = parseFloat(data.remain_debit !== undefined ? data.remain_debit : (debit - sssAmt));
            if (remain < 0) remain = 0;

            $('#sssModalIncome').text('฿' + income.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#sssModalPaid').text('฿' + paid.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#sssModalDebitOriginal').text('฿' + debit.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#sssModalSssAmount').text('฿' + sssAmt.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            $('#sssModalRemainDebit').text('฿' + remain.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

            var tbody = $('#sssModalItemsTableBody');
            tbody.empty();

            var items = data.items || [];
            var itemsSum = 0;

            if (items.length > 0) {
                items.forEach(function(item, idx) {
                    var amt = parseFloat(item.amount || 0);
                    itemsSum += amt;
                    var qty = parseFloat(item.qty || 1);
                    var unitPrice = parseFloat(item.unit_price !== undefined && item.unit_price > 0 ? item.unit_price : (amt / (qty || 1)));

                    var rowHtml = `
                        <tr>
                          <td class="text-center text-muted">${idx + 1}</td>
                          <td><span class="badge bg-label-primary font-monospace py-0.5 px-1.5" style="font-size: 11px;">${item.sss_type || 'SSS-Instrument'}</span></td>
                          <td class="font-monospace text-muted" style="font-size: 11.5px;">${item.icode || '-'}</td>
                          <td><strong class="text-dark">${item.item_name || '-'}</strong></td>
                          <td class="font-monospace text-muted" style="font-size: 11.5px;">${item.adp_code || '-'}</td>
                          <td class="text-center">${qty.toLocaleString('th-TH')}</td>
                          <td class="text-end text-muted font-monospace">${unitPrice.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                          <td class="text-end fw-semibold text-primary font-monospace">฿${amt.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        </tr>
                    `;
                    tbody.append(rowHtml);
                });
            } else {
                tbody.append(`
                    <tr>
                      <td class="text-center text-muted">1</td>
                      <td><span class="badge bg-label-primary font-monospace py-0.5 px-1.5" style="font-size: 11px;">SSS-Instrument</span></td>
                      <td class="font-monospace text-muted" style="font-size: 11.5px;">AUTO</td>
                      <td><strong class="text-dark">อุปกรณ์และอวัยวะเทียม (Instrument)</strong></td>
                      <td class="font-monospace text-muted" style="font-size: 11.5px;">7004</td>
                      <td class="text-center">1</td>
                      <td class="text-end text-muted font-monospace">${sssAmt.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                      <td class="text-end fw-semibold text-primary font-monospace">฿${sssAmt.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    </tr>
                `);
                itemsSum = sssAmt;
            }

            $('#sssModalItemsCount').text((items.length > 0 ? items.length : 1) + ' รายการ');
            $('#sssModalItemsTotalAmount').text('฿' + itemsSum.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

            var modalEl = document.getElementById('modalSssPatientBreakdown');
            var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            bsModal.show();
        } catch (e) {
            console.error('Error opening SSS patient breakdown modal:', e);
        }
    }
}
</script>
