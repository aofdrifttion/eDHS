<!-- 🌟 Modal แสดงรายละเอียดรายการ CR ของคนไข้รายตัว (Patient CR Breakdown Modal) ล็อคขนาด Compact พอดีจอ ไม่ขยายตาม Modal อื่น -->
<style>
/* 🔒 ล็อคขนาดและสไตล์ของ Modal CR แยกขาดจาก Modal อื่นในระบบโดยสิ้นเชิง */
#modalCrPatientBreakdown.modal {
  padding: 0 !important;
  background: rgba(15, 23, 42, 0.45) !important;
  z-index: 1099 !important;
}

#modalCrPatientBreakdown.modal.show {
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

#modalCrPatientBreakdown .modal-dialog {
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

#modalCrPatientBreakdown .modal-content {
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

#modalCrPatientBreakdown .modal-header {
  flex-shrink: 0 !important;
  padding: 10px 16px !important;
  background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
  color: #ffffff !important;
  border-bottom: 1px solid rgba(255, 255, 255, 0.15) !important;
}

#modalCrPatientBreakdown .modal-body {
  flex: 1 1 auto !important;
  max-height: calc(92vh - 110px) !important;
  overflow-y: auto !important;
  padding: 12px 16px !important;
  background: #f8fafc !important;
}

#modalCrPatientBreakdown .modal-footer {
  flex-shrink: 0 !important;
  padding: 8px 16px !important;
  background: #f1f5f9 !important;
  border-top: 1px solid #e2e8f0 !important;
}

#modalCrPatientBreakdown .cr-info-card {
  border-radius: 10px !important;
  background: #ffffff !important;
  border: 1px solid #e2e8f0 !important;
}

#modalCrPatientBreakdown .cr-kpi-card {
  border-radius: 8px !important;
  padding: 8px !important;
  text-align: center !important;
  background: #ffffff !important;
  border: 1px solid #e2e8f0 !important;
}

#modalCrPatientBreakdown .cr-kpi-card.highlight {
  background: #f0fdf4 !important;
  border-color: #86efac !important;
}

#modalCrPatientBreakdown .text-muted,
#modalCrPatientBreakdown thead th,
#modalCrPatientBreakdown thead tr {
  color: #003065 !important;
}
</style>

<div class="modal fade" id="modalCrPatientBreakdown" tabindex="-1" aria-labelledby="modalCrPatientBreakdownLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      
      <!-- Header กระชับ สวยงาม -->
      <div class="modal-header">
        <div class="d-flex align-items-center gap-2.5">
          <div class="rounded-circle bg-white text-primary d-flex align-items-center justify-content-center shadow-xs" style="width: 34px; height: 34px; min-width: 34px;">
            <i class='bx bx-receipt fs-5'></i>
          </div>
          <div>
            <h6 class="modal-title fw-bold mb-0 text-white" id="modalCrPatientBreakdownLabel" style="font-size: 15px;">
              รายละเอียดรายการและค่ารักษาที่จัดเข้า CR
            </h6>
            <small class="text-white-50" style="font-size: 11.5px;">ข้อมูลจำแนกกลุ่มย่อยและการโอนย้ายยอดลูกหนี้</small>
          </div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="font-size: 11px;"></button>
      </div>

      <!-- Body แบบ Compact -->
      <div class="modal-body">
        
        <!-- ข้อมูลคนไข้ & ผังบัญชี (กล่องด้านบน) -->
        <div class="card border-0 cr-info-card shadow-xs mb-2">
          <div class="card-body p-2">
            <div class="row g-1.5 align-items-center" style="font-size: 13px;">
              <div class="col-sm-6 col-md-5">
                <span class="text-muted small">ชื่อผู้ป่วย:</span> 
                <strong class="text-dark ms-1" id="crModalPtName" style="font-size: 13.5px;">-</strong>
              </div>
              <div class="col-sm-3 col-md-4">
                <span class="text-muted small">VN/AN:</span> 
                <strong class="text-primary font-monospace ms-1" id="crModalVn">-</strong>
              </div>
              <div class="col-sm-3 col-md-3">
                <span class="text-muted small">HN:</span> 
                <strong class="text-dark font-monospace ms-1" id="crModalHn">-</strong>
              </div>
              <div class="col-sm-6 col-md-4">
                <span class="text-muted small">วันที่รับบริการ:</span> 
                <span class="text-dark fw-semibold ms-1" id="crModalVstDate">-</span>
              </div>
              <div class="col-sm-6 col-md-4">
                <span class="text-muted small">สิทธิ:</span> 
                <span class="text-dark fw-semibold ms-1" id="crModalPttype">-</span>
              </div>
              <div class="col-sm-12 col-md-4">
                <span class="text-muted small">Hospmain:</span> 
                <span class="text-dark fw-semibold ms-1" id="crModalHospmain">-</span>
              </div>
              <div class="col-12 pt-1 border-top mt-1 d-flex align-items-center flex-wrap gap-1.5" style="font-size: 12px;">
                <span class="text-muted">เส้นทางการโอน:</span> 
                <span class="badge bg-label-info font-monospace py-0.5 px-2" id="crModalOriginAcc" style="font-size: 11px;">ผังต้นทาง: -</span>
                <i class='bx bx-right-arrow-alt text-muted'></i>
                <span class="badge bg-label-success font-monospace py-0.5 px-2" id="crModalTargetAcc" style="font-size: 11px;">ผังปลายทาง: CR</span>
              </div>
            </div>
          </div>
        </div>

        <!-- การ์ด 5 สรุปยอดเงิน (Compact Mini Cards) -->
        <div class="row row-cols-2 row-cols-md-5 g-2 mb-2">
          <div class="col">
            <div class="cr-kpi-card shadow-xs h-100">
              <div class="text-muted small mb-0.5" style="font-size: 11px;">ค่าใช้จ่ายรวม</div>
              <div class="fw-bold text-secondary" id="crModalIncome" style="font-size: 14px;">฿0.00</div>
            </div>
          </div>
          <div class="col">
            <div class="cr-kpi-card shadow-xs h-100">
              <div class="text-muted small mb-0.5" style="font-size: 11px;">ชำระแล้ว</div>
              <div class="fw-bold text-muted" id="crModalPaid" style="font-size: 14px;">฿0.00</div>
            </div>
          </div>
          <div class="col">
            <div class="cr-kpi-card shadow-xs h-100">
              <div class="text-muted small mb-0.5" style="font-size: 11px;">ภาระหนี้เดิม</div>
              <div class="fw-bold text-dark" id="crModalDebitOriginal" style="font-size: 14px;">฿0.00</div>
            </div>
          </div>
          <div class="col">
            <div class="cr-kpi-card highlight shadow-xs h-100">
              <div class="text-success small fw-semibold mb-0.5" style="font-size: 11px;">ยอดจัดเข้า CR</div>
              <div class="fw-bold text-success" id="crModalCrAmount" style="font-size: 14px;">฿0.00</div>
            </div>
          </div>
          <div class="col">
            <div class="cr-kpi-card shadow-xs h-100">
              <div class="text-muted small mb-0.5" style="font-size: 11px;">คงเหลือในผังเดิม</div>
              <div class="fw-bold text-primary" id="crModalRemainDebit" style="font-size: 14px;">฿0.00</div>
            </div>
          </div>
        </div>

        <!-- ตารางแจกแจงรายการ CR ขนาดพอดีตา -->
        <div class="card border-0 cr-info-card shadow-xs" style="overflow: hidden;">
          <div class="card-header bg-white py-1.5 px-2.5 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
              <span class="fw-bold text-dark" style="font-size: 12.5px;">
                <i class='bx bx-list-check text-primary me-1'></i> รายการยา เวชภัณฑ์ หรือบริการเฉพาะ CR
              </span>
              <span id="crModalSubgroupBadge" class="badge bg-label-info font-monospace py-0.5 px-2" style="font-size: 11px; display: none;"></span>
            </div>
            <span class="badge bg-primary py-0.5 px-2" id="crModalItemsCount" style="font-size: 11px;">0 รายการ</span>
          </div>

          <!-- 🌟 Mini Tab/Pills สลับดูเฉพาะกลุ่มย่อย หรือดูทั้งหมด (แสดงเมื่อคนไข้มีบริการ CR มากกว่า 1 กลุ่มย่อย) -->
          <div id="crModalSubgroupPills" class="p-2 bg-light border-bottom d-flex flex-wrap align-items-center gap-1.5" style="display: none;">
            <!-- สร้างผ่าน JS -->
          </div>

          <div class="table-responsive" style="max-height: 38vh; min-height: 100px;">
            <table class="table table-hover table-striped table-bordered align-middle mb-0" style="font-size: 12.5px;">
              <thead class="table-light sticky-top" style="background: #f1f5f9;">
                <tr class="text-nowrap text-muted" style="font-size: 11.5px;">
                  <th class="text-center" style="width: 36px;">#</th>
                  <th style="width: 115px;">กลุ่มย่อย CR</th>
                  <th style="width: 80px;">ICODE</th>
                  <th>ชื่อรายการ / บริการ</th>
                  <th style="width: 80px;">รหัส ADP</th>
                  <th class="text-center" style="width: 55px;">จำนวน</th>
                  <th class="text-end" style="width: 90px;">ราคา/หน่วย</th>
                  <th class="text-end" style="width: 105px;">ยอดเงิน (บาท)</th>
                </tr>
              </thead>
              <tbody id="crModalItemsTableBody">
                <!-- แถวจะถูกสร้างผ่าน JS -->
              </tbody>
              <tfoot class="table-light fw-bold border-top" style="background: #f8fafc;">
                <tr>
                  <td colspan="7" class="text-end" style="font-size: 12.5px;" id="crModalItemsTotalLabel">รวมยอดรายการ CR:</td>
                  <td class="text-end text-success fw-bold" id="crModalItemsTotalAmount" style="font-size: 14px;">฿0.00</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

      </div>

      <!-- Footer แบบ Compact -->
      <div class="modal-footer d-flex align-items-center justify-content-between">
        <small class="text-muted d-flex align-items-center gap-1" style="font-size: 11px;">
          <i class='bx bx-shield-quarter text-success'></i>
          ระบบจำแนกกลุ่มย่อยอัตโนมัติ (Automated CR Subgroup Classifier)
        </small>
        <button type="button" class="btn btn-secondary btn-sm px-3 py-1" data-bs-dismiss="modal" style="border-radius: 6px; font-size: 12px;">
          <i class='bx bx-x me-0.5'></i> ปิดหน้าต่าง
        </button>
      </div>

    </div>
  </div>
</div>

<script>
window._currentCrModalData = null;
window._currentCrModalFilter = 'ALL';

function openCrPatientBreakdownModal(el, forceSubgroup) {
    var raw = $(el).attr('data-cr-payload');
    if (!raw) return;
    try {
        var modalDom = $('#modalCrPatientBreakdown');
        if (modalDom.length && modalDom.parent().prop('tagName') !== 'BODY') {
            $('body').append(modalDom);
        }

        var data = typeof raw === 'object' ? raw : JSON.parse(raw);
        window._currentCrModalData = data;

        // 🎯 ตรวจหา Subgroup เป้าหมาย
        var targetSubgroup = forceSubgroup || $(el).attr('data-filter-subgroup');
        if (!targetSubgroup && typeof activeCrSubgroupFilter !== 'undefined') {
            targetSubgroup = activeCrSubgroupFilter;
        }
        if (!targetSubgroup) targetSubgroup = 'ALL';

        // กรอกข้อมูลทั่วไปของคนไข้
        $('#crModalPtName').text(data.ptname || '-');
        $('#crModalVn').text(data.vn || '-');
        $('#crModalHn').text(data.hn || '-');
        $('#crModalVstDate').text(data.vstdate || '-');
        $('#crModalPttype').text(data.pttypename || '-');
        $('#crModalHospmain').text(data.hospmain || '-');

        var origTxt = data.origin_acc || '-';
        if (origTxt.indexOf('1102050101.') !== -1) {
            origTxt = 'ผังต้นทาง: ' + origTxt.replace('1102050101.', '.');
        }
        $('#crModalOriginAcc').text(origTxt);
        $('#crModalTargetAcc').text('ผังปลายทาง: ' + (data.target_acc || '1102050101.216'));

        var debit = parseFloat(data.debit || 0);
        var income = parseFloat(data.income !== undefined ? data.income : debit);
        var paid = parseFloat(data.paid !== undefined ? data.paid : (income > debit ? income - debit : 0));

        $('#crModalIncome').text('฿' + income.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#crModalPaid').text('฿' + paid.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#crModalDebitOriginal').text('฿' + debit.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

        // เรนเดอร์รายการ items ตามกลุ่มย่อยที่เลือก
        renderCrModalBreakdownView(targetSubgroup);

        var modalEl = document.getElementById('modalCrPatientBreakdown');
        if (modalEl) {
            var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    } catch(e) {
        console.error('Error opening CR breakdown modal:', e);
    }
}

function renderCrModalBreakdownView(targetSubgroup) {
    var data = window._currentCrModalData;
    if (!data) return;
    targetSubgroup = targetSubgroup || 'ALL';
    window._currentCrModalFilter = targetSubgroup;

    var allItems = data.items || [];
    var debit = parseFloat(data.debit || 0);
    var fullCrAmt = parseFloat(data.cr_amount || 0);

    // 1. วิเคราะห์และคำนวณยอดเงินแต่ละรายการ (จัดสรรยอดหนี้คงเหลือที่ยังไม่ได้จัดสรรให้กลุ่มย่อยที่ไม่มีราคารายการ เช่น CR-walkin, CR-AE)
    var itemsSumRaw = 0;
    var zeroItemsIndices = [];
    allItems.forEach(function(it, idx) {
        var a = parseFloat(it.amount || 0);
        itemsSumRaw += a;
        if (a <= 0.001) zeroItemsIndices.push(idx);
    });

    var unallocated = Math.max(0, fullCrAmt - itemsSumRaw);
    if (unallocated > 0 && zeroItemsIndices.length > 0) {
        var eachZeroAmt = unallocated / zeroItemsIndices.length;
        zeroItemsIndices.forEach(function(idx) {
            allItems[idx].computed_amount = eachZeroAmt;
        });
    }

    var sgMap = {};
    allItems.forEach(function(it) {
        var sg = (it.cr_type || 'CR-บริการเฉพาะ').trim();
        if (!sgMap[sg]) sgMap[sg] = { count: 0, amount: 0 };
        sgMap[sg].count++;
        var itAmt = it.computed_amount !== undefined ? it.computed_amount : parseFloat(it.amount || 0);
        sgMap[sg].amount += itAmt;
    });
    var distinctSubgroups = Object.keys(sgMap);

    // 2. กรอง items
    var displayItems = allItems;
    var isFiltered = false;
    if (targetSubgroup !== 'ALL' && distinctSubgroups.length > 0) {
        var tgLower = targetSubgroup.trim().toLowerCase();
        var matched = allItems.filter(function(it) {
            var itSg = (it.cr_type || '').trim().toLowerCase();
            return itSg === tgLower || itSg.indexOf(tgLower) > -1 || tgLower.indexOf(itSg) > -1;
        });
        if (matched.length > 0) {
            displayItems = matched;
            isFiltered = true;
        }
    }

    // 3. คำนวณยอด crAmt สำหรับกลุ่มย่อยที่กำลังแสดงผล
    var crAmt = fullCrAmt;
    if (isFiltered) {
        var subSum = 0;
        displayItems.forEach(function(it) {
            var itAmt = it.computed_amount !== undefined ? it.computed_amount : parseFloat(it.amount || 0);
            subSum += itAmt;
        });
        if (subSum > 0) {
            crAmt = subSum;
        }
    }

    var remain = Math.max(0, debit - crAmt);
    $('#crModalCrAmount').text('฿' + crAmt.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#crModalRemainDebit').text('฿' + remain.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}));

    // 4. แสดงป้าย Badge กลุ่มย่อย
    if (isFiltered) {
        $('#crModalSubgroupBadge').html('<i class="bx bx-filter-alt me-0.5"></i> เฉพาะกลุ่มย่อย: ' + targetSubgroup).show();
        $('#crModalItemsTotalLabel').text('รวมยอดเฉพาะกลุ่มย่อย (' + targetSubgroup + '):');
    } else {
        $('#crModalSubgroupBadge').hide();
        $('#crModalItemsTotalLabel').text('รวมยอดรายการ CR ทั้งหมด:');
    }

    // 5. เรนเดอร์ Mini Tab/Pills สลับดูเฉพาะกลุ่มย่อย หรือดูทั้งหมด
    var pillsContainer = $('#crModalSubgroupPills');
    if (distinctSubgroups.length > 1) {
        pillsContainer.empty().show();
        var allActive = (targetSubgroup === 'ALL') ? 'btn-primary text-white shadow-xs' : 'btn-outline-secondary bg-white text-dark';
        var allPill = `
            <button type="button" class="btn btn-sm py-0.5 px-2.5 d-inline-flex align-items-center gap-1 ${allActive}" style="font-size: 11.5px; border-radius: 20px;" onclick="renderCrModalBreakdownView('ALL')">
                <i class='bx bx-grid-alt'></i> 
                <span>ดูทุกกลุ่มย่อย</span>
                <span class="badge bg-white text-primary rounded-pill py-0 px-1 font-monospace" style="font-size: 10px;">${allItems.length}</span>
                <span class="ms-0.5 fw-bold text-success">฿${fullCrAmt.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
            </button>
        `;
        pillsContainer.append(allPill);

        distinctSubgroups.forEach(function(sg) {
            var sgInfo = sgMap[sg];
            var tgLower = targetSubgroup.trim().toLowerCase();
            var sgLower = sg.trim().toLowerCase();
            var isThisActive = (targetSubgroup !== 'ALL' && (sgLower === tgLower || sgLower.indexOf(tgLower) > -1 || tgLower.indexOf(sgLower) > -1));
            var sgClass = isThisActive ? 'btn-primary text-white shadow-xs' : 'btn-outline-secondary bg-white text-dark';
            var pillHtml = `
                <button type="button" class="btn btn-sm py-0.5 px-2.5 d-inline-flex align-items-center gap-1 ${sgClass}" style="font-size: 11.5px; border-radius: 20px;" onclick="renderCrModalBreakdownView('${sg}')">
                    <i class='bx bx-layer'></i>
                    <span>${sg}</span>
                    <span class="badge ${isThisActive ? 'bg-white text-primary' : 'bg-secondary'} rounded-pill py-0 px-1 font-monospace" style="font-size: 10px;">${sgInfo.count}</span>
                    <span class="ms-0.5 fw-bold ${isThisActive ? 'text-white' : 'text-success'}">฿${sgInfo.amount.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                </button>
            `;
            pillsContainer.append(pillHtml);
        });
    } else {
        pillsContainer.hide().empty();
    }

    // 6. เรนเดอร์แถวรายการในตาราง
    var tbody = $('#crModalItemsTableBody');
    tbody.empty();
    var itemsSum = 0;

    if (displayItems.length > 0) {
        displayItems.forEach(function(item, idx) {
            var amt = item.computed_amount !== undefined ? item.computed_amount : parseFloat(item.amount || 0);
            itemsSum += amt;
            var qty = parseFloat(item.qty || 1);
            var unitPrice = parseFloat(item.unit_price !== undefined && item.unit_price > 0 ? item.unit_price : (amt / (qty || 1)));

            var rowHtml = `
                <tr>
                  <td class="text-center text-muted">${idx + 1}</td>
                  <td><span class="badge bg-label-primary font-monospace py-0.5 px-1.5" style="font-size: 11px;">${item.cr_type || '-'}</span></td>
                  <td class="font-monospace text-muted" style="font-size: 11.5px;">${item.icode || '-'}</td>
                  <td><strong class="text-dark">${item.item_name || '-'}</strong></td>
                  <td class="font-monospace text-muted" style="font-size: 11.5px;">${item.adp_code || '-'}</td>
                  <td class="text-center">${qty.toLocaleString()}</td>
                  <td class="text-end">${unitPrice.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                  <td class="text-end fw-bold text-success">฿${amt.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                </tr>
            `;
            tbody.append(rowHtml);
        });
        $('#crModalItemsCount').text(displayItems.length + ' รายการ');
        $('#crModalItemsTotalAmount').text('฿' + itemsSum.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    } else {
        var typesText = targetSubgroup !== 'ALL' ? targetSubgroup : ((data.cr_types || []).join(', ') || 'บริการเฉพาะ CR');
        var rowHtml = `
            <tr>
              <td class="text-center text-muted">1</td>
              <td><span class="badge bg-label-primary font-monospace py-0.5 px-1.5" style="font-size: 11px;">${typesText}</span></td>
              <td class="font-monospace text-muted" style="font-size: 11.5px;">VISIT-CR</td>
              <td><strong class="text-dark">บริการเฉพาะเหมาจ่ายตามเกณฑ์กลุ่มย่อย (${typesText})</strong></td>
              <td class="font-monospace text-muted" style="font-size: 11.5px;">CR-FULL</td>
              <td class="text-center">1</td>
              <td class="text-end">${crAmt.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
              <td class="text-end fw-bold text-success">฿${crAmt.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            </tr>
        `;
        tbody.append(rowHtml);
        $('#crModalItemsCount').text('1 บริการเหมาจ่าย');
        $('#crModalItemsTotalAmount').text('฿' + crAmt.toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    }
}
</script>
