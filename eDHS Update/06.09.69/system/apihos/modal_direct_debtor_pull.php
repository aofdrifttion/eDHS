<!-- ====================================================================================
     🏥 eDebtor Hospital System (eDHS) - Direct HOSxP Debtor Pull Modal UI
     ====================================================================================
     หน้าต่าง Modal สำหรับดึงข้อมูลลูกหนี้ตรงจากฐานข้อมูล HOSxP ($conn2)
     รองรับ Live Preview (0.2s), เลือกสิทธิ, Batch Chunk Ingestion, Progress Bar, และ Safe Rollback
     ==================================================================================== -->

<?php
$hos_dp_prefix = file_exists(__DIR__ . '/../../assets/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.min.js') ? '../assets' : 'assets';
?>
<link rel="stylesheet" href="<?= $hos_dp_prefix ?>/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.min.css">

<style>
/* บังคับให้ Modal แสดงผลเต็มจอ (Fullscreen Modal) */
#modalDirectDebtorPull {
  z-index: 10050 !important;
}
#modalDirectDebtorPull .modal-dialog.modal-fullscreen {
  width: 100vw !important;
  max-width: 100vw !important;
  height: 100vh !important;
  margin: 0 !important;
  padding: 0 !important;
}
#modalDirectDebtorPull .modal-content {
  height: 100vh !important;
  border: none !important;
  border-radius: 0 !important;
  display: flex !important;
  flex-direction: column !important;
}
#modalDirectDebtorPull .modal-header {
  padding: 0.85rem 1.75rem !important;
  background: #ffffff;
  border-bottom: 1px solid #e2e8f0;
  box-shadow: 0 2px 6px -1px rgba(0, 0, 0, 0.04);
  flex-shrink: 0 !important;
  border-radius: 0 !important;
}
#modalDirectDebtorPull .modal-body {
  flex: 1 1 auto !important;
  overflow-y: auto !important;
  padding: 1rem 1.5rem !important;
}
#modalDirectDebtorPull .modal-footer {
  flex-shrink: 0 !important;
  border-radius: 0 !important;
  padding: 0.75rem 1.5rem !important;
}
/* Z-Index และความสวยงามของ Thai Bootstrap Datepicker */
.datepicker.datepicker-dropdown {
  z-index: 10060 !important;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  padding: 8px;
}
.datepicker table tr td.active, 
.datepicker table tr td.active:hover, 
.datepicker table tr td.active.disabled, 
.datepicker table tr td.active.disabled:hover {
  background-color: #2563eb !important;
  background-image: none !important;
  border-radius: 6px;
}
.datepicker table tr td.today, 
.datepicker table tr td.today:hover {
  background-color: #eff6ff !important;
  color: #2563eb !important;
  border-radius: 6px;
}
.datepicker table tr td.day:hover {
  background-color: #f1f5f9;
  border-radius: 6px;
}
.hos-custom-acc-select {
  font-size: 12px;
  max-width: 320px;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
}
.hos-row-unmapped {
  background-color: #fffbeb !important;
}
.badge-custom-mapped {
  font-size: 10.5px;
  background: #f0fdf4;
  color: #166534;
  border: 1px solid #bbf7d0;
  padding: 2px 6px;
  border-radius: 4px;
}
#modalDirectDebtorPull .hos-modal-icon {
  width: 46px;
  height: 46px;
  border-radius: 12px;
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  color: #1d4ed8;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.18);
  flex-shrink: 0;
}

/* ====================================================================
   Modern Segmented Tab Switcher (ตำแหน่งวางใหม่บน Header สวยงามมาตรฐาน)
   ==================================================================== */
.hos-header-tab-wrapper {
  background: #f1f5f9;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  padding: 3px;
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.04);
}
.hos-header-tab-pills {
  list-style: none;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}
.hos-header-tab-pills .nav-link {
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  padding: 0.45rem 1rem;
  border-radius: 8px;
  border: 1px solid transparent;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
  display: inline-flex;
  align-items: center;
  background: transparent;
  line-height: 1.25;
  white-space: nowrap;
}
.hos-header-tab-pills .nav-link:hover {
  color: #0f172a;
  background: rgba(255, 255, 255, 0.75);
}
/* ปุ่มนำเข้าข้อมูลลูกหนี้ - Active State */
.hos-header-tab-pills #hos-pull-tab.active {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
  color: #ffffff !important;
  font-weight: 700;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.28);
  border-color: #1d4ed8;
}
.hos-header-tab-pills #hos-pull-tab.active i {
  color: #ffffff !important;
}
/* ปุ่มลบข้อมูลลูกหนี้ปลอดภัย - Default & Active States */
.hos-header-tab-pills #hos-delete-tab {
  color: #dc2626;
}
.hos-header-tab-pills #hos-delete-tab:hover {
  color: #b91c1c;
  background: #fee2e2;
}
.hos-header-tab-pills #hos-delete-tab.active {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
  color: #ffffff !important;
  font-weight: 700;
  box-shadow: 0 2px 8px rgba(220, 38, 38, 0.28);
  border-color: #b91c1c;
}
.hos-header-tab-pills #hos-delete-tab.active i {
  color: #ffffff !important;
}
#modalDirectDebtorPull .hos-control-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.25rem 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
}
#modalDirectDebtorPull .hos-metric-card {
  background: #ffffff;
  border-radius: 10px;
  padding: 0.65rem 1rem;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.04);
  transition: all 0.2s ease;
  position: relative;
  overflow: hidden;
}
#modalDirectDebtorPull .hos-metric-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 14px rgba(15, 23, 42, 0.07);
}
#modalDirectDebtorPull .hos-card-opd {
  border-left: 4px solid #2563eb !important;
}
#modalDirectDebtorPull .hos-card-ipd {
  border-left: 4px solid #16a34a !important;
}
#modalDirectDebtorPull .hos-card-total {
  border-left: 4px solid #4f46e5 !important;
}
#modalDirectDebtorPull .hos-metric-icon {
  width: 24px;
  height: 24px;
  border-radius: 6px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  flex-shrink: 0;
}
#modalDirectDebtorPull .hos-card-opd .hos-metric-icon {
  background: #eff6ff;
  color: #2563eb;
}
#modalDirectDebtorPull .hos-card-ipd .hos-metric-icon {
  background: #f0fdf4;
  color: #16a34a;
}
#modalDirectDebtorPull .hos-card-total .hos-metric-icon {
  background: #eef2ff;
  color: #4f46e5;
}
#modalDirectDebtorPull .hos-metric-title {
  font-size: 12.5px;
  font-weight: 700;
  color: #0f172a;
}
#modalDirectDebtorPull .hos-badge-cases {
  font-weight: 700;
  font-size: 11px;
  padding: 2px 7px;
  border-radius: 12px;
}
#modalDirectDebtorPull .hos-card-opd .hos-badge-cases {
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #bfdbfe;
}
#modalDirectDebtorPull .hos-card-ipd .hos-badge-cases {
  background: #f0fdf4;
  color: #15803d;
  border: 1px solid #bbf7d0;
}
#modalDirectDebtorPull .hos-card-total .hos-badge-cases {
  background: #eef2ff;
  color: #4338ca;
  border: 1px solid #c7d2fe;
}
#modalDirectDebtorPull .hos-metric-val {
  font-size: 17px;
  font-weight: 700;
  letter-spacing: -0.2px;
  line-height: 1.25;
}
#modalDirectDebtorPull .hos-card-opd .hos-metric-val {
  color: #1d4ed8;
}
#modalDirectDebtorPull .hos-card-ipd .hos-metric-val {
  color: #15803d;
}
#modalDirectDebtorPull .hos-card-total .hos-metric-val {
  color: #312e81;
  font-size: 18px;
}
#modalDirectDebtorPull .hos-metric-sub {
  font-size: 11px;
  color: #475569;
  font-weight: 500;
}
#modalDirectDebtorPull .hos-badge-exec {
  background: #f8fafc;
  color: #475569;
  border: 1px solid #e2e8f0;
  font-size: 10px;
  font-weight: 600;
  padding: 1px 6px;
  border-radius: 4px;
}

/* Distinct Table Header & Modern Table Wrapper (Responsive Height in Fullscreen) */
#modalDirectDebtorPull .hos-preview-table-wrapper {
  max-height: calc(100vh - 410px);
  min-height: 400px;
  overflow-y: auto;
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
  background: #ffffff;
}
#modalDirectDebtorPull #tblHosRights {
  font-size: 13px;
  margin-bottom: 0;
  border-collapse: separate;
  border-spacing: 0;
}
#modalDirectDebtorPull #tblHosRights thead {
  position: sticky;
  top: 0;
  z-index: 15;
}
#modalDirectDebtorPull #tblHosRights thead th {
  background: #475569 !important;
  color: #ffffff !important;
  font-weight: 700 !important;
  font-size: 13px !important;
  text-align: center;
  vertical-align: middle;
  padding: 0.8rem 0.65rem !important;
  border: none !important;
  border-bottom: 2px solid #334155 !important;
  border-right: 1px solid rgba(255, 255, 255, 0.15) !important;
  white-space: nowrap;
  letter-spacing: 0.2px;
}
#modalDirectDebtorPull #tblHosRights thead th:last-child {
  border-right: none !important;
}
#modalDirectDebtorPull #tblHosRights thead th .text-light-muted {
  color: #cbd5e1;
  font-weight: 500;
  font-size: 11.5px;
}
#modalDirectDebtorPull #tblHosRights tbody td {
  padding: 0.65rem 0.65rem;
  vertical-align: middle;
  border-bottom: 1px solid #f1f5f9;
}
#modalDirectDebtorPull #tblHosRights tbody tr {
  transition: background-color 0.15s ease;
}
#modalDirectDebtorPull #tblHosRights tbody tr:hover {
  background-color: #f8fafc !important;
}

/* ====================================================================
   Dark Grey Checkbox Theme (กลมกลืนกับหัวตารางสีเทาเข้ม)
   ==================================================================== */
#modalDirectDebtorPull .form-check-input[type="checkbox"],
#modalDirectDebtorPull #tblHosRights .form-check-input {
  cursor: pointer;
  width: 18px !important;
  height: 18px !important;
  border: 1.5px solid #64748b !important;
  border-radius: 4px !important;
  transition: all 0.15s ease-in-out;
  accent-color: #475569 !important;
}
#modalDirectDebtorPull .form-check-input[type="checkbox"]:hover,
#modalDirectDebtorPull #tblHosRights .form-check-input:hover {
  border-color: #334155 !important;
}
#modalDirectDebtorPull .form-check-input[type="checkbox"]:focus,
#modalDirectDebtorPull #tblHosRights .form-check-input:focus {
  border-color: #475569 !important;
  outline: 0 !important;
  box-shadow: 0 0 0 0.2rem rgba(71, 85, 105, 0.25) !important;
}
#modalDirectDebtorPull .form-check-input[type="checkbox"]:checked,
#modalDirectDebtorPull .form-check-input[type="checkbox"]:indeterminate,
#modalDirectDebtorPull #tblHosRights .form-check-input:checked,
#modalDirectDebtorPull #tblHosRights tbody .hos-right-chk:checked {
  background-color: #475569 !important;
  border-color: #334155 !important;
  box-shadow: 0 1px 4px rgba(51, 65, 85, 0.3) !important;
}
/* Checkbox บนหัวตาราง (Master Checkbox on Dark Slate Grey Header) */
#modalDirectDebtorPull #tblHosRights thead .form-check-input,
#modalDirectDebtorPull #chk_select_all_header {
  border: 1.5px solid #cbd5e1 !important;
  background-color: transparent !important;
}
#modalDirectDebtorPull #tblHosRights thead .form-check-input:hover,
#modalDirectDebtorPull #chk_select_all_header:hover {
  border-color: #ffffff !important;
}
#modalDirectDebtorPull #tblHosRights thead .form-check-input:checked,
#modalDirectDebtorPull #chk_select_all_header:checked {
  background-color: #1e293b !important;
  border-color: #ffffff !important;
  box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.25), 0 1px 4px rgba(15, 23, 42, 0.5) !important;
}
#modalDirectDebtorPull #tblHosRights thead .form-check-input:focus,
#modalDirectDebtorPull #chk_select_all_header:focus {
  border-color: #ffffff !important;
  box-shadow: 0 0 0 0.2rem rgba(255, 255, 255, 0.3) !important;
}
/* สถานะ Disabled สำหรับสิทธิที่ถูกล็อก */
#modalDirectDebtorPull #tblHosRights .form-check-input:disabled,
#modalDirectDebtorPull #tblHosRights tbody .hos-right-chk:disabled {
  background-color: #e2e8f0 !important;
  border-color: #cbd5e1 !important;
  opacity: 0.6 !important;
  cursor: not-allowed !important;
  box-shadow: none !important;
}

/* Sortable Table Columns */
#modalDirectDebtorPull #tblHosRights thead th.sortable {
  cursor: pointer;
  user-select: none;
  transition: background-color 0.15s ease;
}
#modalDirectDebtorPull #tblHosRights thead th.sortable:hover {
  background-color: #334155 !important;
}
#modalDirectDebtorPull #tblHosRights thead th .sort-icon {
  font-size: 13px;
  margin-left: 4px;
  vertical-align: middle;
  opacity: 0.6;
  transition: opacity 0.15s ease, color 0.15s ease;
}
#modalDirectDebtorPull #tblHosRights thead th.sorted-asc .sort-icon,
#modalDirectDebtorPull #tblHosRights thead th.sorted-desc .sort-icon {
  opacity: 1;
  color: #fef08a !important;
}

/* OPD / IPD Type Filter Pills */
#modalDirectDebtorPull .hos-type-filter-pills .nav-link {
  font-size: 13px;
  font-weight: 600;
  padding: 0.38rem 0.85rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #ffffff;
  color: #475569;
  transition: all 0.2s ease;
}
#modalDirectDebtorPull .hos-type-filter-pills .nav-link:hover {
  background: #f1f5f9;
  color: #0f172a;
}
#modalDirectDebtorPull .hos-type-filter-pills .nav-link.active {
  background: #475569;
  color: #ffffff !important;
  border-color: #475569;
  box-shadow: 0 2px 6px rgba(71, 85, 105, 0.2);
}
#modalDirectDebtorPull .hos-type-filter-pills .nav-link.active.tab-opd {
  background: #2563eb;
  border-color: #2563eb;
  color: #ffffff !important;
}
#modalDirectDebtorPull .hos-type-filter-pills .nav-link.active.tab-unmapped {
  background: #d97706;
  border-color: #d97706;
  color: #ffffff !important;
  box-shadow: 0 2px 6px rgba(217, 119, 6, 0.25);
}
#modalDirectDebtorPull .hos-type-filter-pills .nav-link.active .badge {
  background: rgba(255, 255, 255, 0.25) !important;
  color: #ffffff !important;
  border: none !important;
}
#modalDirectDebtorPull .badge-acc {
  font-family: monospace;
  font-size: 11.5px;
  font-weight: 600;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  color: #0f172a;
  padding: 2px 6px;
  border-radius: 4px;
}

/* Patient-Level Inspection Modal & Interactive Case Badges */
.hos-clickable-pttypename {
  cursor: pointer;
  transition: color 0.15s ease;
}
.hos-clickable-pttypename:hover {
  color: #2563eb !important;
  text-decoration: underline;
}
.hos-btn-preview-pts {
  font-size: 12px;
  font-weight: 700;
  border-width: 1.5px;
  transition: all 0.15s ease-in-out;
  white-space: nowrap;
}
.hos-btn-preview-pts:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12) !important;
}
/* ====================================================================
   Patient Detail Table (#tblHosPatientsPreview) - High Contrast & Crisp Styling
   ==================================================================== */
#modalHosPatientPreview {
  z-index: 10070 !important;
}
#modalHosPatientPreview .hos-sub-table-wrapper {
  border: 1px solid #cbd5e1;
  border-radius: 10px;
  background: #ffffff;
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.05);
}
#modalHosPatientPreview #tblHosPatientsPreview {
  font-size: 12.5px !important;
  margin-bottom: 0;
  border-collapse: separate;
  border-spacing: 0;
  color: #0f172a !important;
}
#modalHosPatientPreview #tblHosPatientsPreview thead {
  position: sticky;
  top: 0;
  z-index: 10;
}
#modalHosPatientPreview #tblHosPatientsPreview thead th {
  background: #1e293b !important;
  color: #ffffff !important;
  font-weight: 700 !important;
  font-size: 12.5px !important;
  text-align: center;
  vertical-align: middle;
  padding: 0.75rem 0.55rem !important;
  border: none !important;
  border-bottom: 2px solid #0f172a !important;
  border-right: 1px solid rgba(255, 255, 255, 0.15) !important;
  white-space: nowrap;
  letter-spacing: 0.2px;
}
#modalHosPatientPreview #tblHosPatientsPreview th,
#modalHosPatientPreview #tblHosPatientsPreview td {
  white-space: nowrap !important;
  vertical-align: middle !important;
}
#modalHosPatientPreview #tblHosPatientsPreview thead th:last-child {
  border-right: none !important;
}
#modalHosPatientPreview #tblHosPatientsPreview tbody tr:nth-child(even) {
  background-color: #f8fafc;
}
#modalHosPatientPreview #tblHosPatientsPreview tbody tr:nth-child(odd) {
  background-color: #ffffff;
}
#modalHosPatientPreview #tblHosPatientsPreview tbody tr:hover {
  background-color: #e2e8f0 !important;
}
#modalHosPatientPreview #tblHosPatientsPreview tbody td {
  padding: 0.6rem 0.65rem;
  vertical-align: middle;
  border-bottom: 1px solid #e2e8f0;
  font-size: 12.5px !important;
  color: #0f172a !important;
  white-space: nowrap !important;
}
/* Enhanced High-Contrast Cells in Sub-Modal Table */
.hos-cell-idx {
  font-weight: 700;
  color: #475569;
  background: #f1f5f9;
  border-radius: 6px;
  display: inline-block;
  min-width: 26px;
  padding: 1px 5px;
  font-size: 11.5px;
}
.hos-cell-vn {
  font-family: inherit;
  font-weight: 700;
  color: #0f172a;
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.2px;
}
.hos-cell-hn {
  font-family: inherit;
  font-weight: 700;
  color: #1d4ed8;
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.2px;
}
.hos-cell-name {
  font-weight: 700;
  color: #0f172a;
}
.hos-cell-cid {
  font-family: inherit;
  font-weight: 700;
  color: #1e293b;
  font-variant-numeric: tabular-nums;
  letter-spacing: 0.3px;
  white-space: nowrap !important;
}
.hos-cell-pdx {
  font-family: monospace;
  font-weight: 700;
  color: #1e293b;
  background-color: #f1f5f9;
  border: 1px solid #cbd5e1;
  padding: 2px 7px;
  border-radius: 4px;
}
.hos-cell-debit-positive {
  font-weight: 700;
  color: #15803d !important;
}
.hos-cell-debit-zero {
  font-weight: 600;
  color: #64748b !important;
}
</style>

<div class="modal fade" id="modalDirectDebtorPull" tabindex="-1" aria-labelledby="modalDirectDebtorPullLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-fullscreen modal-dialog-scrollable" role="document">
    <div class="modal-content">
      
      <!-- Modal Header -->
      <div class="modal-header d-flex justify-content-between align-items-center py-2 px-3 px-md-4 bg-white border-bottom shadow-xs">
        <div class="d-flex align-items-center gap-3">
          <div class="hos-modal-icon">
            <i class='bx bx-cloud-download'></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2">
              <h5 class="modal-title fw-bold text-dark m-0" id="modalDirectDebtorPullLabel" style="font-size: 16px;">
                ดึงข้อมูลลูกหนี้ตรงจาก HOSxP
              </h5>
            </div>
            <small class="text-muted" style="font-size: 12px;">ดึงข้อมูลผู้ป่วยนอก (OPD) และผู้ป่วยใน (IPD) ตรงสู่ eDHS</small>
          </div>
        </div>

        <!-- Segmented Tab Navigation: สวยมาตรฐาน จัดวางบน Header อย่างลงตัว -->
        <div class="d-flex align-items-center gap-3">
          <div class="hos-header-tab-wrapper">
            <ul class="nav nav-pills hos-header-tab-pills m-0 p-1" id="hosTab" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active d-flex align-items-center gap-1.5" id="hos-pull-tab" data-bs-toggle="pill" data-bs-target="#hos-pull-pane" type="button" role="tab">
                  <i class='bx bx-cloud-download fs-5'></i> <span>นำเข้าข้อมูลลูกหนี้</span>
                </button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link d-flex align-items-center gap-1.5" id="hos-delete-tab" data-bs-toggle="pill" data-bs-target="#hos-delete-pane" type="button" role="tab">
                  <i class='bx bx-trash fs-5'></i> <span>ลบข้อมูลลูกหนี้ปลอดภัย</span>
                </button>
              </li>
            </ul>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>

      <!-- Modal Body -->
      <div class="modal-body bg-light p-3 p-md-4">
        
        <div class="tab-content" id="hosTabContent">
          
          <!-- ====================================================================
               TAB 1: นำเข้าข้อมูลลูกหนี้ (Direct Ingestion)
               ==================================================================== -->
          <div class="tab-pane fade show active" id="hos-pull-pane" role="tabpanel">
            
            <!-- Control Card -->
            <div class="hos-control-card mb-3">
              <div class="row g-3 align-items-end">
                
                <!-- เลือกโหมดช่วงเวลา -->
                <div class="col-12 col-md-3">
                  <label class="form-label fw-bold text-dark mb-1"><i class='bx bx-calendar-event text-primary me-1'></i> โหมดช่วงเวลา</label>
                  <div class="d-flex gap-3 mt-1">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="hos_date_mode" id="hos_mode_month" value="month" checked onchange="toggleHosDateMode()">
                      <label class="form-check-label fw-semibold" for="hos_mode_month">รายเดือน</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="hos_date_mode" id="hos_mode_range" value="range" onchange="toggleHosDateMode()">
                      <label class="form-check-label fw-semibold" for="hos_mode_range">ช่วงวันที่</label>
                    </div>
                  </div>
                </div>

                <!-- Input โหมดรายเดือน (Hybrid Month Picker Rule 85) -->
                <div class="col-12 col-md-4" id="hos_month_group">
                  <label class="form-label fw-bold text-dark mb-1"><i class='bx bx-calendar text-primary me-1'></i> งวดเดือน (เช่น 8-2026)</label>
                  <div class="input-group">
                    <input type="text" class="form-control fw-semibold" id="hos_month_input" placeholder="เช่น 8-2026" list="dl_hos_months" oninput="clearHosPreview()" onchange="clearHosPreview()">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="เลือกเดือนเร็ว">
                      📅
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" id="dd_hos_quick_months">
                      <!-- Dropdown items will be populated by JS -->
                    </ul>
                  </div>
                  <datalist id="dl_hos_months">
                    <!-- Populated by JS -->
                  </datalist>
                </div>

                <!-- Input โหมดช่วงวันที่ (Range) -->
                <div class="col-12 col-md-4" id="hos_range_group" style="display: none;">
                  <label class="form-label fw-bold text-dark mb-1"><i class='bx bx-calendar-check text-primary me-1'></i> เลือกช่วงวันที่ (จาก - ถึง)</label>
                  <div class="input-group">
                    <input type="text" class="form-control hos-thai-datepicker fw-semibold text-center" id="hos_date_start" placeholder="วว/ดด/ปปปป" autocomplete="off" onchange="clearHosPreview()">
                    <span class="input-group-text bg-light text-muted px-2">ถึง</span>
                    <input type="text" class="form-control hos-thai-datepicker fw-semibold text-center" id="hos_date_end" placeholder="วว/ดด/ปปปป" autocomplete="off" onchange="clearHosPreview()">
                  </div>
                </div>

                <!-- เลือกประเภทผู้ป่วย OPD / IPD -->
                <div class="col-12 col-md-3">
                  <label class="form-label fw-bold text-dark mb-1"><i class='bx bx-user-pin text-primary me-1'></i> ประเภทผู้ป่วย</label>
                  <div class="d-flex gap-3 mt-1">
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="chk_hos_opd" checked onchange="clearHosPreview()">
                      <label class="form-check-label fw-bold text-primary" for="chk_hos_opd">ผู้ป่วยนอก (OPD)</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="checkbox" id="chk_hos_ipd" checked onchange="clearHosPreview()">
                      <label class="form-check-label fw-bold text-success" for="chk_hos_ipd">ผู้ป่วยใน (IPD)</label>
                    </div>
                  </div>
                </div>

                <!-- ปุ่มเริ่ม Preview -->
                <div class="col-12 col-md-2 text-end">
                  <button type="button" class="btn btn-primary w-100 fw-bold d-flex align-items-center justify-content-center gap-1 shadow-sm py-2" id="btnPreviewHos" onclick="loadHosPreview()">
                    <i class='bx bx-search-alt fs-5'></i> <span>ตรวจสอบยอด</span>
                  </button>
                </div>

              </div>
            </div>

            <!-- กล่องแสดงสถานะโหลด (Loading Spinner) -->
            <div id="hosLoadingBox" class="text-center py-5" style="display: none;">
              <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
              <h6 class="fw-bold text-dark">กำลังเชื่อมต่อและดึงข้อมูลสรุปจาก HOSxP...</h6>
              <p class="text-muted small m-0">ระบบกำลังคำนวณสรุปยอดลูกหนี้แยกตามสิทธิและผังบัญชี กรุณารอสักครู่</p>
            </div>

            <!-- กล่องผลลัพธ์ Live Preview (ซ่อนไว้จนกว่าจะกดตรวจสอบยอด) -->
            <div id="hosPreviewContainer" style="display: none;">
              
              <!-- KPI Summary Cards (Compact Typography & Sleek Layout) -->
              <div class="row g-2 mb-2">
                <!-- การ์ด 1: ผู้ป่วยนอก (OPD) -->
                <div class="col-12 col-md-4">
                  <div class="hos-metric-card hos-card-opd" style="cursor: pointer;" onclick="filterHosType('OPD')" title="คลิกเพื่อดูเฉพาะผู้ป่วยนอก (OPD)">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <div class="d-flex align-items-center gap-2">
                        <span class="hos-metric-icon"><i class='bx bx-user-plus'></i></span>
                        <span class="hos-metric-title">ผู้ป่วยนอก (OPD)</span>
                      </div>
                      <span class="hos-badge-cases" id="badge_hos_opd_cases">0 เคส</span>
                    </div>
                    <div class="hos-metric-val mb-1" id="val_hos_opd_debit">0.00 ฿</div>
                    <div class="hos-metric-sub" id="sub_hos_opd_income"><i class='bx bx-receipt text-secondary me-1'></i>ค่าใช้จ่าย: 0.00 ฿</div>
                  </div>
                </div>

                <!-- การ์ด 2: ผู้ป่วยใน (IPD) -->
                <div class="col-12 col-md-4">
                  <div class="hos-metric-card hos-card-ipd" style="cursor: pointer;" onclick="filterHosType('IPD')" title="คลิกเพื่อดูเฉพาะผู้ป่วยใน (IPD)">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <div class="d-flex align-items-center gap-2">
                        <span class="hos-metric-icon"><i class='bx bx-hotel'></i></span>
                        <span class="hos-metric-title">ผู้ป่วยใน (IPD)</span>
                      </div>
                      <span class="hos-badge-cases" id="badge_hos_ipd_cases">0 เคส</span>
                    </div>
                    <div class="hos-metric-val mb-1" id="val_hos_ipd_debit">0.00 ฿</div>
                    <div class="hos-metric-sub" id="sub_hos_ipd_income"><i class='bx bx-receipt text-secondary me-1'></i>ค่าใช้จ่าย: 0.00 ฿</div>
                  </div>
                </div>

                <!-- การ์ด 3: รวมทั้งหมด -->
                <div class="col-12 col-md-4">
                  <div class="hos-metric-card hos-card-total" style="cursor: pointer;" onclick="filterHosType('all')" title="คลิกเพื่อดูข้อมูลทั้งหมด">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <div class="d-flex align-items-center gap-2">
                        <span class="hos-metric-icon"><i class='bx bx-coin-stack'></i></span>
                        <span class="hos-metric-title">รวมทั้งหมด</span>
                      </div>
                      <span class="hos-badge-cases" id="badge_hos_total_cases">0 เคส</span>
                    </div>
                    <div class="hos-metric-val mb-1" id="val_hos_total_debit">0.00 ฿</div>
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="hos-metric-sub" id="sub_hos_total_income"><i class='bx bx-calculator text-secondary me-1'></i>ค่าใช้จ่ายรวม: 0.00 ฿</div>
                      <span class="hos-badge-exec" id="hos_exec_badge">⚡ 0.00s</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Alert แจ้งเตือนสิทธิที่ถูกล็อกทางการเงิน (มีบิล/ตั้งลูกหนี้แล้ว) -->
              <div id="hos_locked_alert_box" class="alert alert-danger border-danger gap-3 align-items-start mb-3" style="display: none !important;">
                <i class='bx bx-lock-alt fs-3 text-danger flex-shrink-0 mt-1'></i>
                <div>
                  <h6 class="fw-bold text-danger mb-1"><i class='bx bx-shield-quarter'></i> ระบบล็อกความปลอดภัยทางการเงิน (ห้ามนำเข้าซ้ำเด็ดขาด)</h6>
                  <p class="small text-dark mb-0" id="hos_locked_alert_text">
                    ตรวจพบสิทธิการรักษาที่มีการ <strong>ออกใบเสร็จรับเงิน</strong> หรือ <strong>ยืนยันตั้งลูกหนี้แล้ว</strong> ในระบบ eDHS สิทธิเหล่านี้จะถูกล็อกไม่ให้เลือกนำเข้า เพื่อป้องกันการบันทึกทับหรือทำให้ยอดทางบัญชีเสียหาย 100%
                  </p>
                </div>
              </div>

              <!-- Action Bar & Rights Filter Header with OPD / IPD Tabs -->
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <div class="d-flex align-items-center flex-wrap gap-2">
                  <!-- OPD / IPD / ยังไม่ผูกผัง Filter Tabs -->
                  <ul class="nav nav-pills hos-type-filter-pills" id="hosTypeFilterTabs">
                    <li class="nav-item">
                      <button class="nav-link active" id="tab_btn_all" type="button" onclick="filterHosType('all')">
                        <i class='bx bx-list-ul me-1'></i> ทั้งหมด <span class="badge bg-secondary rounded-pill ms-1" id="tab_badge_all">0</span>
                      </button>
                    </li>
                    <li class="nav-item">
                      <button class="nav-link text-primary" id="tab_btn_opd" type="button" onclick="filterHosType('OPD')">
                        <i class='bx bx-user-plus me-1'></i> ผู้ป่วยนอก (OPD) <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill ms-1" id="tab_badge_opd">0</span>
                      </button>
                    </li>
                    <li class="nav-item">
                      <button class="nav-link text-success" id="tab_btn_ipd" type="button" onclick="filterHosType('IPD')">
                        <i class='bx bx-hotel me-1'></i> ผู้ป่วยใน (IPD) <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill ms-1" id="tab_badge_ipd">0</span>
                      </button>
                    </li>
                    <li class="nav-item">
                      <button class="nav-link text-warning" id="tab_btn_unmapped" type="button" onclick="filterHosType('unmapped')">
                        <i class='bx bx-error-circle me-1'></i> ยังไม่ผูกผัง <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill ms-1" id="tab_badge_unmapped">0</span>
                      </button>
                    </li>
                  </ul>
                  <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1" id="hos_selected_summary">เลือกแล้ว: 0 สิทธิ</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <div class="input-group input-group-sm" style="width: 210px;">
                    <span class="input-group-text bg-white"><i class='bx bx-search'></i></span>
                    <input type="text" class="form-control" id="hos_table_search" placeholder="ค้นหาสิทธิหรือผัง..." onkeyup="filterHosTable()">
                  </div>
                  <button type="button" class="btn btn-sm btn-outline-primary" onclick="setAllHosCheckboxes(true)" title="เลือกสิทธิที่เปิดทั้งหมด">
                    <i class='bx bx-check-double'></i> เลือกทั้งหมด
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setAllHosCheckboxes(false)" title="ยกเลิกการเลือกทั้งหมด">
                    <i class='bx bx-x'></i> ยกเลิกทั้งหมด
                  </button>
                </div>
              </div>

              <!-- ข้อความแนะนำสำหรับแท็บยังไม่ผูกผัง (Unmapped Notice Banner) -->
              <div id="hos_unmapped_notice_box" class="alert alert-warning align-items-center justify-content-between p-2 mb-2" style="display: none; background-color: #fffbeb; border: 1px solid #fcd34d; border-radius: 8px;">
                <div class="d-flex align-items-center gap-2">
                  <i class='bx bx-info-circle fs-4 text-warning flex-shrink-0'></i>
                  <span class="small fw-semibold text-dark">สิทธิการรักษาในแท็บนี้ยังไม่ได้กำหนดผังบัญชีจาก HOSxP ท่านสามารถเลือกผังลูกหนี้จากตาราง <code>tb_code</code> ได้ทันที ระบบจะบันทึกการจับคู่ลง config อัตโนมัติ</span>
                </div>
              </div>

              <!-- Rights Summary Table -->
              <div class="table-responsive hos-preview-table-wrapper mb-3">
                <table class="table table-hover align-middle mb-0" id="tblHosRights">
                  <thead>
                    <tr>
                      <th class="text-center" style="width: 44px;">
                        <input type="checkbox" class="form-check-input m-0" id="chk_select_all_header" checked onchange="toggleAllHosHeader(this)" title="เลือก/ยกเลิกทั้งหมด">
                      </th>
                      <th class="text-center sortable" style="width: 75px;" data-col="type" onclick="sortHosTable('type')" title="คลิกเพื่อเรียงลำดับ">
                        ประเภท <i class='bx bx-sort-alt-2 sort-icon'></i>
                      </th>
                      <th class="text-center sortable" style="width: 85px;" data-col="pttype" onclick="sortHosTable('pttype')" title="คลิกเพื่อเรียงลำดับ">
                        รหัสสิทธิ <i class='bx bx-sort-alt-2 sort-icon'></i>
                      </th>
                      <th class="text-start sortable" data-col="pttypename" onclick="sortHosTable('pttypename')" title="คลิกเพื่อเรียงลำดับ">
                        ชื่อสิทธิการรักษา (HOSxP) <i class='bx bx-sort-alt-2 sort-icon'></i>
                      </th>
                      <th class="text-center sortable" style="width: 140px;" data-col="accountcode" onclick="sortHosTable('accountcode')" title="คลิกเพื่อเรียงลำดับ">
                        รหัสผังบัญชี <i class='bx bx-sort-alt-2 sort-icon'></i>
                      </th>
                      <th class="text-start sortable" style="min-width: 200px;" data-col="accountname" onclick="sortHosTable('accountname')" title="คลิกเพื่อเรียงลำดับ">
                        ชื่อผังบัญชีลูกหนี้ <i class='bx bx-sort-alt-2 sort-icon'></i>
                      </th>
                      <th class="text-end sortable" style="width: 100px;" data-col="cases" onclick="sortHosTable('cases')" title="คลิกเพื่อเรียงลำดับ">
                        จำนวนเคส <i class='bx bx-sort-alt-2 sort-icon'></i>
                      </th>
                      <th class="text-end sortable" style="width: 130px;" data-col="income" onclick="sortHosTable('income')" title="คลิกเพื่อเรียงลำดับ">
                        ค่าใช้จ่าย <span class="text-light-muted">(฿)</span> <i class='bx bx-sort-alt-2 sort-icon'></i>
                      </th>
                      <th class="text-end sortable" style="width: 140px;" data-col="debit" onclick="sortHosTable('debit')" title="คลิกเพื่อเรียงลำดับ">
                        ยอดลูกหนี้ <span class="text-light-muted">(฿)</span> <i class='bx bx-sort-alt-2 sort-icon'></i>
                      </th>
                    </tr>
                  </thead>
                  <tbody id="tbodyHosRights">
                    <!-- Dynamic Rows Generated by JS -->
                  </tbody>
                </table>
              </div>

              <!-- Progress Box ระหว่างนำเข้าแบบ Chunk -->
              <div id="hosProgressBox" class="hos-progress-card" style="display: none;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="d-flex align-items-center gap-2">
                    <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                    <span class="fw-bold text-dark" id="hosProgressStatusText">กำลังเริ่มต้นการประมวลผล...</span>
                  </div>
                  <span class="fw-bold text-success fs-5" id="hosProgressPercentText">0%</span>
                </div>
                <div class="progress" style="height: 18px; border-radius: 9px;">
                  <div id="hosProgressBarInner" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 0%;"></div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
                  <span id="hosProgressDetailText">ประมวลผล 0 / 0 รายการ</span>
                  <div class="d-flex gap-3">
                    <span class="text-primary fw-semibold"><i class='bx bx-plus-circle'></i> บันทึกใหม่: <span id="cntHosInserted">0</span></span>
                    <span class="text-warning fw-semibold"><i class='bx bx-skip-next-circle'></i> ข้าม (ตั้งหนี้/มีบิลแล้ว): <span id="cntHosSkipped">0</span></span>
                    <span class="text-secondary fw-semibold"><i class='bx bx-refresh'></i> อัปเดต: <span id="cntHosUpdated">0</span></span>
                  </div>
                </div>
              </div>

            </div>

          </div>

          <!-- ====================================================================
               TAB 2: ลบข้อมูลลูกหนี้ปลอดภัย (Safe Rollback)
               ==================================================================== -->
          <div class="tab-pane fade" id="hos-delete-pane" role="tabpanel">
            <div class="hos-control-card">
              
              <div class="alert alert-warning d-flex gap-3 align-items-start mb-4 shadow-sm" style="background-color: #fffbeb; border: 1px solid #fcd34d; border-left: 6px solid #d97706; border-radius: 12px; padding: 1.25rem 1.4rem;">
                <div style="width: 44px; height: 44px; border-radius: 10px; background-color: #fef3c7; border: 1px solid #fde68a; display: flex; align-items: center; justify-content: center; flex-shrink: 0; color: #b45309; font-size: 26px;">
                  <i class='bx bx-shield-quarter'></i>
                </div>
                <div class="flex-grow-1">
                  <h6 class="fw-bold mb-2" style="color: #78350f; font-size: 16px;">
                    มาตรฐานความปลอดภัยทางการเงิน
                  </h6>
                  <p class="mb-0" style="color: #0f172a; font-size: 14px; line-height: 1.7;">
                    ระบบจะตรวจสอบข้ามทุกตารางหลัก ได้แก่ <strong style="color: #0f172a;">ผังลูกหนี้สิทธิ</strong> (ทั้งข้อมูลใบเสร็จรับเงิน), <strong style="color: #0f172a;">ทะเบียนคุมลูกหนี้</strong>, <strong style="color: #0f172a;">ประวัติการรับชำระ</strong>, <strong style="color: #0f172a;">ตารางแยกรับชำระ CR</strong>, และ <strong style="color: #0f172a;">ตารางแยกรับชำระประกันสังคม</strong> 
                    หากพบว่ามีรายการใดในงวดเดือนที่เลือกถูกออกใบเสร็จแล้ว หรือได้รับการยืนยันตั้งลูกหนี้แล้ว ระบบจะ <span class="badge bg-danger text-white fw-bold px-2 py-1 align-middle" style="font-size: 13.5px; letter-spacing: 0.3px;">บล็อกการลบ 100% ทันที</span> เพื่อป้องกันข้อมูลทางบัญชีเสียหาย
                  </p>
                </div>
              </div>

              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label fw-bold text-dark"><i class='bx bx-calendar me-1 text-danger'></i> งวดเดือนที่ต้องการลบ (เช่น 8-2026)</label>
                  <div class="input-group">
                    <input type="text" class="form-control" id="hos_delete_month" placeholder="เช่น 8-2026" list="dl_hos_months">
                    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="เลือกเดือนเร็ว">
                      📅
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" id="dd_hos_delete_quick_months">
                      <!-- Populated by JS -->
                    </ul>
                  </div>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold text-dark"><i class='bx bx-user me-1 text-danger'></i> ประเภทผู้ป่วย</label>
                  <select class="form-select" id="hos_delete_patient_type">
                    <option value="both" selected>ทั้งหมด (OPD และ IPD)</option>
                    <option value="opd">เฉพาะผู้ป่วยนอก (OPD)</option>
                    <option value="ipd">เฉพาะผู้ป่วยใน (IPD)</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold text-dark"><i class='bx bx-filter-alt me-1 text-secondary'></i> เฉพาะรหัสผังบัญชี (ไม่บังคับ)</label>
                  <input type="text" class="form-control" id="hos_delete_acc" placeholder="ว่างไว้ = ลบทุกผังในเดือนนั้น">
                </div>
                <div class="col-12 text-end mt-4">
                  <button type="button" class="btn btn-danger px-4 fw-bold shadow-sm" id="btnSafeDeleteHos" onclick="executeSafeDeleteHos()">
                    <i class='bx bx-trash me-1'></i> ตรวจสอบและดำเนินการลบข้อมูล
                  </button>
                </div>
              </div>

            </div>
          </div>

        </div>

      </div>

      <!-- Modal Footer -->
      <div class="modal-footer bg-white d-flex justify-content-between">
        <div>
          <small class="text-muted"><i class='bx bx-info-circle'></i> ข้อมูลจะถูกจัดเก็บตรงสู่ eDHS อย่างปลอดภัย</small>
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
          <button type="button" class="btn btn-success px-4 fw-bold shadow-sm" id="btnStartPull" onclick="startHosBatchPull()" style="display: none;">
            <i class='bx bx-cloud-download me-1'></i> ยืนยันดึงข้อมูลเข้าสู่ระบบ
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ====================================================================
     Sub-Modal: ดูรายละเอียดผู้ป่วยรายบุคคลก่อนนำเข้า (Patient-level Preview)
     ==================================================================== -->
<div class="modal fade" id="modalHosPatientPreview" tabindex="-1" aria-labelledby="modalHosPatientPreviewTitle" aria-hidden="true" style="z-index: 10070 !important;">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 96vw; width: 96vw; height: 92vh; margin: 2vh auto;">
    <div class="modal-content shadow-lg border-0" style="height: 100%; border-radius: 14px; overflow: hidden; display: flex; flex-direction: column;">
      
      <!-- Sub-Modal Header -->
      <div class="modal-header py-2 px-3 bg-white border-bottom flex-shrink-0" style="border-bottom: 1px solid #e2e8f0 !important;">
        <div class="d-flex align-items-center gap-2 flex-grow-1">
          <div style="width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); color: #1d4ed8; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; box-shadow: 0 2px 6px rgba(37, 99, 235, 0.15);">
            <i class='bx bx-group'></i>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <h6 class="modal-title fw-bold text-dark mb-0" id="modalHosPatientPreviewTitle" style="font-size: 15px;">
                รายชื่อผู้ป่วยก่อนนำเข้า (HOSxP)
              </h6>
              <span id="hos_sub_badge_type" class="badge bg-primary rounded-pill px-2 py-1">OPD</span>
              <span id="hos_sub_badge_pttype" class="badge bg-light text-dark border rounded-pill px-2 py-1 font-monospace">สิทธิ: -</span>
              <span id="hos_sub_badge_acc" class="badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-1">ผังบัญชี: -</span>
            </div>
            <small class="text-muted" id="hos_sub_modal_subtitle" style="font-size: 12px;">ช่วงวันที่: -</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Sub-Modal Body -->
      <div class="modal-body p-3 bg-light d-flex flex-column flex-grow-1" style="overflow-y: auto;">
        
        <!-- Summary Cards & Search Bar Toolbar (มีระยะห่างจากหัวตารางอย่างเหมาะสม) -->
        <div class="bg-white p-3 rounded-3 border shadow-sm flex-shrink-0" style="margin-bottom: 16px !important;">
          <div class="row g-2 align-items-center">
            <!-- Stat 1: Total Cases -->
            <div class="col-auto">
              <div class="d-flex align-items-center gap-2 px-3 py-1.5 bg-light rounded-2 border" style="border-left: 3px solid #2563eb !important;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #eff6ff; display: flex; align-items: center; justify-content: center; color: #2563eb; font-size: 18px;">
                  <i class='bx bx-user-check'></i>
                </div>
                <div>
                  <div class="text-secondary fw-semibold" style="font-size: 11px;">จำนวนผู้ป่วย</div>
                  <div class="fw-bold text-dark" style="font-size: 14px;" id="hos_sub_total_cases">0 ราย</div>
                </div>
              </div>
            </div>

            <!-- Stat 2: Total Income -->
            <div class="col-auto">
              <div class="d-flex align-items-center gap-2 px-3 py-1.5 bg-light rounded-2 border" style="border-left: 3px solid #64748b !important;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #475569; font-size: 18px;">
                  <i class='bx bx-receipt'></i>
                </div>
                <div>
                  <div class="text-secondary fw-semibold" style="font-size: 11px;">ค่าใช้จ่ายรวม</div>
                  <div class="fw-bold text-dark" style="font-size: 14px;" id="hos_sub_total_income">0.00 ฿</div>
                </div>
              </div>
            </div>

            <!-- Stat 3: Total Debit -->
            <div class="col-auto">
              <div class="d-flex align-items-center gap-2 px-3 py-1.5 bg-light rounded-2 border" style="border-left: 3px solid #16a34a !important;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: #f0fdf4; display: flex; align-items: center; justify-content: center; color: #16a34a; font-size: 18px;">
                  <i class='bx bx-wallet'></i>
                </div>
                <div>
                  <div class="text-secondary fw-semibold" style="font-size: 11px;">ยอดลูกหนี้รวม</div>
                  <div class="fw-bold text-success" style="font-size: 14px;" id="hos_sub_total_debit">0.00 ฿</div>
                </div>
              </div>
            </div>

            <!-- Spacer -->
            <div class="col"></div>

            <!-- Status Filter & Search -->
            <div class="col-auto d-flex align-items-center gap-2">
              <div class="btn-group btn-group-sm" role="group" id="hos_sub_status_filter_group">
                <input type="radio" class="btn-check" name="hos_sub_status_filter" id="hos_sub_filter_all" value="ALL" checked onchange="filterHosPatientTable()">
                <label class="btn btn-outline-secondary fw-bold" for="hos_sub_filter_all">ทั้งหมด (<span id="hos_sub_cnt_all">0</span>)</label>

                <input type="radio" class="btn-check" name="hos_sub_status_filter" id="hos_sub_filter_new" value="NEW" onchange="filterHosPatientTable()">
                <label class="btn btn-outline-success fw-bold" for="hos_sub_filter_new">ยังไม่เคยนำเข้า (<span id="hos_sub_cnt_new">0</span>)</label>

                <input type="radio" class="btn-check" name="hos_sub_status_filter" id="hos_sub_filter_imported" value="IMPORTED" onchange="filterHosPatientTable()">
                <label class="btn btn-outline-primary fw-bold" for="hos_sub_filter_imported">นำเข้าแล้ว (<span id="hos_sub_cnt_imported">0</span>)</label>

                <input type="radio" class="btn-check" name="hos_sub_status_filter" id="hos_sub_filter_billed" value="BILLED" onchange="filterHosPatientTable()">
                <label class="btn btn-outline-danger fw-bold" for="hos_sub_filter_billed">มีบิล/ตัดแล้ว (<span id="hos_sub_cnt_billed">0</span>)</label>
              </div>

              <div class="input-group input-group-sm" style="width: 250px;">
                <span class="input-group-text bg-white"><i class='bx bx-search text-muted'></i></span>
                <input type="text" class="form-control" id="hos_sub_search_input" placeholder="ค้นหา HN, ชื่อ, CID, VN, โรค..." onkeyup="filterHosPatientTable()">
                <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('hos_sub_search_input').value=''; filterHosPatientTable();" title="ล้างการค้นหา"><i class='bx bx-x'></i></button>
              </div>
            </div>

          </div>
        </div>

        <!-- Sub-Modal Loading Indicator -->
        <div id="hos_sub_loading_box" class="text-center py-5 bg-white rounded-3 border my-auto">
          <div class="spinner-border text-primary mb-3" style="width: 2.5rem; height: 2.5rem;" role="status"></div>
          <h6 class="fw-bold text-dark">กำลังดึงข้อมูลรายชื่อผู้ป่วยจาก HOSxP...</h6>
          <p class="text-muted small m-0">กรุณารอสักครู่ ระบบกำลังโหลดประวัติการรับบริการและตรวจสอบสถานะใน eDHS</p>
        </div>

        <!-- Sub-Modal Table Container -->
        <div id="hos_sub_table_container" class="hos-sub-table-wrapper bg-white rounded-3 border shadow-sm flex-grow-1" style="display: none; overflow-y: auto; max-height: calc(92vh - 225px);">
          <table class="table table-hover align-middle mb-0" id="tblHosPatientsPreview">
            <thead>
              <tr>
                <th class="text-center" style="width: 45px; white-space: nowrap;">#</th>
                <th class="text-center" style="min-width: 115px; white-space: nowrap;">VN / AN</th>
                <th class="text-center" style="min-width: 95px; white-space: nowrap;">HN</th>
                <th class="text-start" style="min-width: 180px; white-space: nowrap;">ชื่อ - สกุล ผู้ป่วย</th>
                <th class="text-center" style="min-width: 165px; white-space: nowrap;">เลขบัตร ปชช.</th>
                <th class="text-center" style="min-width: 110px; white-space: nowrap;">เพศ / อายุ</th>
                <th class="text-center" style="min-width: 180px; white-space: nowrap;">วันที่รับบริการ</th>
                <th class="text-start" style="min-width: 130px; white-space: nowrap;">แผนก / แผนกตรวจ</th>
                <th class="text-center" style="min-width: 80px; white-space: nowrap;">PDX</th>
                <th class="text-end" style="min-width: 105px; white-space: nowrap;">ค่าใช้จ่าย (฿)</th>
                <th class="text-end" style="min-width: 95px; white-space: nowrap;">ชำระแล้ว (฿)</th>
                <th class="text-end" style="min-width: 105px; white-space: nowrap;">ยอดลูกหนี้ (฿)</th>
                <th class="text-center" style="min-width: 130px; white-space: nowrap;">สถานะใน eDHS</th>
              </tr>
            </thead>
            <tbody id="tbodyHosPatientsPreview">
              <!-- Dynamically populated -->
            </tbody>
          </table>
        </div>

      </div>

      <!-- Sub-Modal Footer -->
      <div class="modal-footer py-2 px-3 bg-white border-top d-flex justify-content-between flex-shrink-0" style="border-top: 1px solid #e2e8f0 !important;">
        <div class="small text-muted">
          <i class='bx bx-info-circle me-1'></i>
          <span id="hos_sub_footer_count">แสดง 0 จากทั้งหมด 0 รายการ</span>
          <span id="hos_sub_limit_notice" class="text-warning fw-semibold ms-2" style="display: none;">(แสดงสูงสุด 1,000 รายแรก)</span>
        </div>
        <div>
          <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">
            <i class='bx bx-x me-1'></i> ปิดหน้าต่าง
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
/**
 * ====================================================================================
 * Controller JavaScript สำหรับ Direct HOSxP Debtor Pull Engine
 * ====================================================================================
 */
let hosPreviewData = null;
let isPullingInProgress = false;
let hosCustomMappings = {};
let hosDebtorAccounts = [];
let hosOpdAccounts = [];
let hosIpdAccounts = [];
let hosSortColumn = '';
let hosSortDirection = 'asc';
let hosCurrentTypeTab = 'all';

// ฟังก์ชันเริ่มต้นใช้งาน Thai Bootstrap Datepicker
// ซ่อนผลการ Preview และเคลียร์ Alert ทั้งหมดเมื่อผู้ใช้เปลี่ยนเงื่อนไขหรือเลือกเดือนใหม่
function clearHosPreview() {
  const previewContainer = document.getElementById('hosPreviewContainer');
  if (previewContainer) previewContainer.style.display = 'none';
  const alertBox = document.getElementById('hos_locked_alert_box');
  if (alertBox) {
    alertBox.classList.remove('d-flex');
    alertBox.style.setProperty('display', 'none', 'important');
  }
  const unmappedNotice = document.getElementById('hos_unmapped_notice_box');
  if (unmappedNotice) unmappedNotice.style.display = 'none';

  const btnStart = document.getElementById('btnStartPull');
  if (btnStart) btnStart.style.display = 'none';

  hosCurrentTypeTab = 'all';
  hosSortColumn = '';
  hosSortDirection = 'asc';
  document.querySelectorAll('#hosTypeFilterTabs .nav-link').forEach(btn => btn.classList.remove('active', 'tab-opd', 'tab-ipd', 'tab-unmapped'));
  const btnAll = document.getElementById('tab_btn_all');
  if (btnAll) btnAll.classList.add('active');
}

function initHosThaiDatePicker() {
  if (typeof $.fn.datepicker === 'undefined') {
    console.warn('bootstrap-datepicker plugin not ready');
    return;
  }
  $('.hos-thai-datepicker').datepicker({
    format: 'dd/mm/yyyy',
    language: 'th',
    autoclose: true,
    todayHighlight: true,
    orientation: 'bottom auto',
    clearBtn: false
  }).on('changeDate', function() {
    clearHosPreview();
  });
}

// เปิด Modal
function openDirectHosPullModal() {
  try {
    initHosMonthOptions();
    initHosThaiDatePicker();
  } catch (eInit) {
    console.warn('initHosMonthOptions error:', eInit);
  }

  var modalEl = document.getElementById('modalDirectDebtorPull');
  if (!modalEl) {
    console.error('modalDirectDebtorPull element not found in DOM');
    return;
  }

  // ย้าย element ไปต่อท้าย <body> เสมอ เพื่อป้องกัน Stacking Context ทับซ้อน
  if (modalEl.parentElement !== document.body) {
    document.body.appendChild(modalEl);
  }

  var opened = false;
  // 1. ลองเปิดด้วย Bootstrap 5 Native Modal API
  try {
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
      var modalInst = bootstrap.Modal.getOrCreateInstance ? bootstrap.Modal.getOrCreateInstance(modalEl) : new bootstrap.Modal(modalEl);
      if (modalInst) {
        modalInst.show();
        opened = true;
      }
    } else if (typeof Modal !== 'undefined' && Modal.getOrCreateInstance) {
      var modalInst = Modal.getOrCreateInstance(modalEl);
      if (modalInst) {
        modalInst.show();
        opened = true;
      }
    }
  } catch (errBs) {
    console.warn('Bootstrap Modal show failed, trying jQuery fallback:', errBs);
  }

  // 2. Fallback ด้วย jQuery Bootstrap Modal
  if (!opened && window.jQuery && typeof $('#modalDirectDebtorPull').modal === 'function') {
    $('#modalDirectDebtorPull').modal('show');
    opened = true;
  }

  // 3. ปรับระดับ z-index ของ backdrop ให้ไม่บัง Modal
  setTimeout(function() {
    $('#modalDirectDebtorPull').css('z-index', '10050');
    $('.modal-backdrop').last().css('z-index', '10040');
  }, 50);
}

// เริ่มต้นค่าตัวเลือกเดือน (Hybrid Month Picker)
function initHosMonthOptions() {
  const selectOpt = document.getElementById('selectTypeOpt');
  const inputMonth = document.getElementById('hos_month_input');
  const dlMonths = document.getElementById('dl_hos_months');
  const ddQuick = document.getElementById('dd_hos_quick_months');
  const ddDelQuick = document.getElementById('dd_hos_delete_quick_months');
  const delMonthInput = document.getElementById('hos_delete_month');
  
  // ดึงเดือนล่าสุดจาก selectTypeOpt ในหน้าหลัก
  let currentVal = '8-2026';
  if (selectOpt && selectOpt.value && selectOpt.value !== '0-0000') {
    currentVal = selectOpt.value;
  }
  
  if (inputMonth && !inputMonth.value) {
    inputMonth.value = currentVal;
  }
  if (delMonthInput && !delMonthInput.value) {
    delMonthInput.value = currentVal;
  }

  // สร้าง Datalist และ Dropdown รายเดือนย้อนหลัง 18 เดือน
  if (dlMonths && dlMonths.children.length === 0) {
    const thaiMonths = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    const now = new Date();
    let optHtml = '';
    let ddHtml = '';
    let ddDelHtml = '';

    for (let i = 0; i < 18; i++) {
      const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
      const m = d.getMonth() + 1;
      const yAd = d.getFullYear();
      const yBe = yAd + 543;
      const valStr = `${m}-${yAd}`;
      const labelStr = `${valStr} (${thaiMonths[m - 1]} ${yBe})`;
      
      optHtml += `<option value="${valStr}">${labelStr}</option>`;
      ddHtml += `<li><a class="dropdown-item" href="javascript:void(0)" onclick="setHosMonth('${valStr}')">${labelStr}</a></li>`;
      ddDelHtml += `<li><a class="dropdown-item" href="javascript:void(0)" onclick="setHosDeleteMonth('${valStr}')">${labelStr}</a></li>`;
    }

    dlMonths.innerHTML = optHtml;
    if (ddQuick) ddQuick.innerHTML = ddHtml;
    if (ddDelQuick) ddDelQuick.innerHTML = ddDelHtml;
  }

  // กำหนดวันที่เริ่มต้น-สิ้นสุด สำหรับ Range Mode เริ่มต้น (ปฏิทินไทย พ.ศ.)
  const dateStart = document.getElementById('hos_date_start');
  const dateEnd = document.getElementById('hos_date_end');
  if (dateStart && !dateStart.value) {
    const now = new Date();
    const yBe = now.getFullYear() + 543;
    const m = String(now.getMonth() + 1).padStart(2, '0');
    dateStart.value = `01/${m}/${yBe}`;
  }
  if (dateEnd && !dateEnd.value) {
    const now = new Date();
    const yBe = now.getFullYear() + 543;
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
    dateEnd.value = `${String(lastDay).padStart(2, '0')}/${m}/${yBe}`;
  }
}

function setHosMonth(val) {
  const input = document.getElementById('hos_month_input');
  if (input) input.value = val;
  clearHosPreview();
}

function setHosDeleteMonth(val) {
  const input = document.getElementById('hos_delete_month');
  if (input) input.value = val;
}

// สลับโหมดวัน / เดือน
function toggleHosDateMode() {
  clearHosPreview();
  const isMonth = document.getElementById('hos_mode_month').checked;
  const monthGrp = document.getElementById('hos_month_group');
  const rangeGrp = document.getElementById('hos_range_group');
  
  if (isMonth) {
    monthGrp.style.display = 'block';
    rangeGrp.style.display = 'none';
  } else {
    monthGrp.style.display = 'none';
    rangeGrp.style.display = 'block';
  }
}

// เรียก Live Preview 0.2s
function loadHosPreview() {
  if (isPullingInProgress) return;

  const isMonth = document.getElementById('hos_mode_month').checked;
  const monthVal = document.getElementById('hos_month_input').value.trim();
  const dateStart = document.getElementById('hos_date_start').value;
  const dateEnd = document.getElementById('hos_date_end').value;
  
  const chkOpd = document.getElementById('chk_hos_opd').checked;
  const chkIpd = document.getElementById('chk_hos_ipd').checked;

  if (!chkOpd && !chkIpd) {
    Swal.fire({
      icon: 'warning',
      title: 'กรุณาเลือกประเภทผู้ป่วย',
      text: 'ต้องเลือกอย่างน้อย 1 ประเภท (OPD หรือ IPD)'
    });
    return;
  }

  let patientType = 'both';
  if (chkOpd && !chkIpd) patientType = 'opd';
  if (!chkOpd && chkIpd) patientType = 'ipd';

  let postData = {
    action: 'preview_summary',
    mode: isMonth ? 'month' : 'range',
    patient_type: patientType
  };

  if (isMonth) {
    if (!monthVal) {
      Swal.fire({ icon: 'warning', title: 'กรุณาระบุเดือน', text: 'ตัวอย่างเช่น 8-2026' });
      return;
    }
    postData.month = monthVal;
  } else {
    if (!dateStart || !dateEnd) {
      Swal.fire({ icon: 'warning', title: 'กรุณาเลือกช่วงวันที่', text: 'ต้องระบุทั้งวันที่เริ่มต้นและสิ้นสุด' });
      return;
    }
    postData.date_start = dateStart;
    postData.date_end = dateEnd;
  }

  // แสดง Loading และเคลียร์ผลเดิม/Alert ทันที
  clearHosPreview();
  document.getElementById('hosLoadingBox').style.display = 'block';
  document.getElementById('btnPreviewHos').disabled = true;

  $.ajax({
    url: 'apihos/api_direct_debtor_pull.php',
    type: 'POST',
    data: postData,
    dataType: 'json',
    success: function(res) {
      document.getElementById('hosLoadingBox').style.display = 'none';
      document.getElementById('btnPreviewHos').disabled = false;

      if (res.status === 'success') {
        hosPreviewData = res;
        hosDebtorAccounts = res.debtor_accounts || [];
        hosOpdAccounts = res.opd_accounts || [];
        hosIpdAccounts = res.ipd_accounts || [];
        hosCustomMappings = {};
        renderHosPreviewUI(res);
      } else {
        Swal.fire({
          icon: 'error',
          title: 'ไม่สามารถดึงข้อมูลสรุปได้',
          text: res.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อ HOSxP'
        });
      }
    },
    error: function(xhr, status, err) {
      document.getElementById('hosLoadingBox').style.display = 'none';
      document.getElementById('btnPreviewHos').disabled = false;
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาดในการเรียก API',
        text: xhr.responseText || err
      });
    }
  });
}

// เรนเดอร์ UI Preview และตารางสิทธิ
function renderHosPreviewUI(data) {
  const sum = data.summary;
  
  // การ์ด OPD
  document.getElementById('badge_hos_opd_cases').textContent = Number(sum.opd_cases).toLocaleString() + ' เคส';
  document.getElementById('val_hos_opd_debit').textContent = Number(sum.opd_debit).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ฿';
  document.getElementById('sub_hos_opd_income').innerHTML = `<i class='bx bx-receipt text-secondary me-1'></i>ค่าใช้จ่าย: ` + Number(sum.opd_income).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ฿';

  // การ์ด IPD
  document.getElementById('badge_hos_ipd_cases').textContent = Number(sum.ipd_cases).toLocaleString() + ' เคส';
  document.getElementById('val_hos_ipd_debit').textContent = Number(sum.ipd_debit).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ฿';
  document.getElementById('sub_hos_ipd_income').innerHTML = `<i class='bx bx-receipt text-secondary me-1'></i>ค่าใช้จ่าย: ` + Number(sum.ipd_income).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ฿';

  // การ์ด รวมทั้งหมด
  document.getElementById('badge_hos_total_cases').textContent = Number(sum.total_cases).toLocaleString() + ' เคส';
  document.getElementById('val_hos_total_debit').textContent = Number(sum.total_debit).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ฿';
  document.getElementById('sub_hos_total_income').innerHTML = `<i class='bx bx-calculator text-secondary me-1'></i>ค่าใช้จ่ายรวม: ` + Number(sum.total_income).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ฿';
  document.getElementById('hos_exec_badge').textContent = '⚡ ' + data.execution_time_sec + 's';

  // ตรวจสอบสถานะการล็อกความปลอดภัยทางการเงิน
  const alertBox = document.getElementById('hos_locked_alert_box');
  const alertText = document.getElementById('hos_locked_alert_text');
  let totalLockedRights = 0;
  let totalBilledCases = sum.edhs_total_billed || 0;

  data.rights.forEach(r => {
    if (r.is_locked) totalLockedRights++;
  });

  if (alertBox) {
    if (totalLockedRights > 0 || totalBilledCases > 0 || sum.has_locked_rights) {
      alertBox.classList.add('d-flex');
      alertBox.style.setProperty('display', 'flex', 'important');
      if (alertText) {
        const countText = totalBilledCases > 0 ? ` (${Number(totalBilledCases).toLocaleString()} รายการ)` : '';
        alertText.innerHTML = `ตรวจพบ <strong>${totalLockedRights} สิทธิ</strong> ที่มีรายการ <strong>ออกใบเสร็จรับเงิน${countText}</strong> หรือ <strong>ยืนยันตั้งลูกหนี้แล้ว</strong> ในระบบ eDHS ระบบได้ทำการล็อกสิทธิเหล่านี้ไว้ไม่ให้เลือกนำเข้า เพื่อป้องกันการบันทึกทับหรือทำให้ยอดทางบัญชีเสียหาย 100%`;
      }
    } else {
      alertBox.classList.remove('d-flex');
      alertBox.style.setProperty('display', 'none', 'important');
    }
  }

  // นับจำนวนสิทธิแยก OPD / IPD / ยังไม่ผูกผัง สำหรับ Badge บนแท็บ
  let countOpd = 0;
  let countIpd = 0;
  let countUnmapped = 0;
  data.rights.forEach(r => {
    if (r.type === 'OPD') countOpd++;
    if (r.type === 'IPD') countIpd++;
    const isUn = r.is_unmapped || !r.accountcode || r.accountcode === '-' || r.accountname === 'ไม่ได้ผูกผังบัญชี';
    if (isUn) countUnmapped++;
  });
  const bAll = document.getElementById('tab_badge_all');
  const bOpd = document.getElementById('tab_badge_opd');
  const bIpd = document.getElementById('tab_badge_ipd');
  const bUnmapped = document.getElementById('tab_badge_unmapped');
  if (bAll) bAll.textContent = data.rights.length;
  if (bOpd) bOpd.textContent = countOpd;
  if (bIpd) bIpd.textContent = countIpd;
  if (bUnmapped) {
    bUnmapped.textContent = countUnmapped;
    if (countUnmapped > 0) {
      bUnmapped.className = 'badge bg-warning text-dark border border-warning-subtle rounded-pill ms-1';
    } else {
      bUnmapped.className = 'badge bg-secondary-subtle text-secondary rounded-pill ms-1';
    }
  }

  // สร้างแถวในตารางสิทธิ
  const tbody = document.getElementById('tbodyHosRights');
  let rowsHtml = '';

  data.rights.forEach((r, idx) => {
    const isOpd = r.type === 'OPD';
    const typeBadge = isOpd 
      ? '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">OPD</span>'
      : '<span class="badge bg-success-subtle text-success border border-success-subtle">IPD</span>';

    const isUnmapped = r.is_unmapped || !r.accountcode || r.accountcode === '-' || r.accountname === 'ไม่ได้ผูกผังบัญชี';
    
    // บันทึก custom mapping ถ้ามีกำหนดไว้เดิม
    if (r.accountcode && r.is_custom) {
      hosCustomMappings[`${r.type}:${r.pttype}`] = r.accountcode;
    }

    let accCodeHtml = '';
    let accNameHtml = '';

    if (isUnmapped) {
      // สำหรับสิทธิที่ยังไม่ได้ผูกผังบัญชี ให้แสดง Dropdown ให้ผู้ใช้เลือกจาก tb_code ได้ทันที
      const optHtml = buildHosDebtorAccountOptions(r.type, r.accountcode);
      accCodeHtml = `<span class="badge-acc" id="badge_acc_${r.type}_${r.pttype}">${r.accountcode || '-'}</span>`;
      accNameHtml = `
        <div class="d-flex align-items-center gap-1">
          <select class="form-select form-select-sm border-warning hos-custom-acc-select" 
                  id="select_acc_${r.type}_${r.pttype}"
                  data-type="${r.type}" 
                  data-pttype="${r.pttype}"
                  onchange="handleHosAccountSelect(this, '${r.type}', '${r.pttype}')">
            ${optHtml}
          </select>
          <span id="status_save_${r.type}_${r.pttype}"></span>
        </div>
      `;
    } else {
      const customBadge = r.is_custom ? `<span class="badge-custom-mapped ms-1"><i class='bx bx-check'></i> กำหนดเอง</span>` : '';
      accCodeHtml = `<span class="badge-acc">${r.accountcode || '-'}</span>${customBadge}`;
      accNameHtml = `<span class="text-dark small">${r.accountname || '-'}</span>`;
    }

    // สถานะความปลอดภัยทางการเงิน: ตรวจสอบการล็อก หรือมีลูกหนี้เดิม
    let chkHtml = '';
    let statusBadge = '';
    let rowClass = '';

    if (r.is_locked) {
      // ล็อกถาวร: มีใบเสร็จ หรือยืนยันตั้งลูกหนี้แล้ว
      chkHtml = `<input type="checkbox" class="form-check-input hos-right-chk" value="${r.type}:${r.pttype}" data-cases="${r.cases}" data-debit="${r.debit}" disabled title="${r.lock_reason || 'ห้ามนำเข้าซ้ำ'}">`;
      statusBadge = `<span class="badge bg-danger text-white ms-1" title="${r.lock_reason}"><i class='bx bx-lock-alt'></i> ${r.lock_reason || 'ห้ามนำเข้าซ้ำ'}</span>`;
      rowClass = 'table-danger-subtle opacity-75';
    } else if (r.is_already_set) {
      // มีข้อมูลลูกหนี้ในระบบแล้ว แต่ยังไม่มีใบเสร็จ: ให้เลือกได้ แต่ไม่ติ๊กเป็นค่าเริ่มต้น
      chkHtml = `<input type="checkbox" class="form-check-input hos-right-chk" value="${r.type}:${r.pttype}" data-cases="${r.cases}" data-debit="${r.debit}" onchange="updateHosSelectedSummary()">`;
      statusBadge = `<span class="badge bg-warning-subtle text-dark border border-warning ms-1"><i class='bx bx-info-circle'></i> มีลูกหนี้เดิม ${Number(r.edhs_cases).toLocaleString()} เคส</span>`;
      rowClass = isUnmapped ? 'hos-row-unmapped' : '';
    } else {
      // ข้อมูลใหม่ปกติ
      chkHtml = `<input type="checkbox" class="form-check-input hos-right-chk" value="${r.type}:${r.pttype}" data-cases="${r.cases}" data-debit="${r.debit}" checked onchange="updateHosSelectedSummary()">`;
      rowClass = isUnmapped ? 'hos-row-unmapped' : '';
    }

    rowsHtml += `
      <tr class="hos-right-row ${rowClass}" 
          id="row_${r.type}_${r.pttype}" 
          data-type="${r.type}"
          data-pttype="${r.pttype}"
          data-pttypename="${r.pttypename}"
          data-accountcode="${r.accountcode || ''}"
          data-accountname="${r.accountname || ''}"
          data-unmapped="${isUnmapped ? '1' : '0'}"
          data-initially-unmapped="${isUnmapped ? '1' : '0'}"
          data-cases="${r.cases}"
          data-income="${r.income}"
          data-debit="${r.debit}"
          data-search="${r.pttype} ${r.pttypename} ${r.accountcode} ${r.accountname} ${r.type}">
        <td class="text-center">
          ${chkHtml}
        </td>
        <td class="text-center">${typeBadge}</td>
        <td class="text-center font-monospace fw-bold text-dark">${r.pttype}</td>
        <td>
          <div class="d-flex align-items-center flex-wrap gap-1">
            <span class="fw-semibold text-dark hos-clickable-pttypename" role="button" onclick="openHosPatientPreviewModal('${r.type}', '${r.pttype}')" title="คลิกดูรายชื่อผู้ป่วยรายคน">${r.pttypename}</span>
            ${statusBadge}
          </div>
          <small class="text-muted">${r.pttype_eclaim_name || '-'}</small>
        </td>
        <td>
          ${accCodeHtml}
        </td>
        <td>
          ${accNameHtml}
        </td>
        <td class="text-end">
          <button type="button" 
                  class="btn btn-sm ${isOpd ? 'btn-outline-primary' : 'btn-outline-success'} py-0 px-2 rounded-pill fw-bold hos-btn-preview-pts shadow-none" 
                  onclick="openHosPatientPreviewModal('${r.type}', '${r.pttype}')"
                  title="คลิกเพื่อดูรายละเอียดผู้ป่วยรายคน (${Number(r.cases).toLocaleString()} ราย)">
            <i class='bx bx-user me-1'></i>${Number(r.cases).toLocaleString()} เคส
          </button>
        </td>
        <td class="text-end text-muted">${Number(r.income).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
        <td class="text-end fw-bold ${isOpd ? 'text-primary' : 'text-success'}">${Number(r.debit).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
      </tr>
    `;
  });

  // เพิ่มแถว Empty State สำหรับแท็บยังไม่ผูกผัง
  rowsHtml += `
    <tr id="hos_unmapped_empty_row" style="display: none;">
      <td colspan="9" class="text-center py-5 bg-white">
        <div class="text-success">
          <i class='bx bx-check-circle fs-1'></i>
          <h6 class="fw-bold mt-2 text-dark">สิทธิการรักษาทั้งหมดผูกผังบัญชีครบถ้วนแล้ว</h6>
          <p class="text-muted small m-0">ไม่พบสิทธิการรักษาที่ค้างการกำหนดผังบัญชีในงวดนี้</p>
        </div>
      </td>
    </tr>
  `;

  tbody.innerHTML = rowsHtml;
  applyHosTableFilter();

  // แสดง Container และปุ่มเริ่มดึง
  document.getElementById('hosPreviewContainer').style.display = 'block';
  document.getElementById('btnStartPull').style.display = 'inline-block';
}

// อัปเดตตัวเลขสรุปการเลือกสิทธิ
function updateHosSelectedSummary() {
  const allChks = document.querySelectorAll('.hos-right-chk');
  const chks = document.querySelectorAll('.hos-right-chk:checked');
  let totalCases = 0;
  let totalDebit = 0;

  chks.forEach(c => {
    totalCases += parseInt(c.dataset.cases || 0);
    totalDebit += parseFloat(c.dataset.debit || 0);
  });

  const summaryEl = document.getElementById('hos_selected_summary');
  if (summaryEl) {
    summaryEl.textContent = 
      `เลือกแล้ว: ${chks.length} / ${allChks.length} สิทธิ (${totalCases.toLocaleString()} เคส, ${totalDebit.toLocaleString(undefined, {minimumFractionDigits: 2})} ฿)`;
  }
  
  // ปรับสถานะปุ่มยืนยัน
  const btnStart = document.getElementById('btnStartPull');
  if (btnStart) {
    btnStart.disabled = (chks.length === 0);
  }
}

function toggleAllHosHeader(master) {
  const chks = document.querySelectorAll('.hos-right-chk:not(:disabled)');
  chks.forEach(c => {
    const tr = c.closest('tr');
    if (tr && tr.style.display !== 'none') {
      c.checked = master.checked;
    }
  });
  updateHosSelectedSummary();
}

function setAllHosCheckboxes(status) {
  const chks = document.querySelectorAll('.hos-right-chk:not(:disabled)');
  chks.forEach(c => {
    const tr = c.closest('tr');
    if (tr && tr.style.display !== 'none') {
      c.checked = status;
    }
  });
  const master = document.getElementById('chk_select_all_header');
  if (master) master.checked = status;
  updateHosSelectedSummary();
}

// สร้างตัวเลือก Dropdown ผังบัญชีลูกหนี้จาก tb_code
function buildHosDebtorAccountOptions(patientType, currentSelectedCode) {
  const isOpd = (patientType === 'OPD');
  const primaryList = isOpd ? hosOpdAccounts : hosIpdAccounts;
  const primaryCodes = new Set(primaryList.map(a => a.code));
  const otherList = (hosDebtorAccounts.length > 0 ? hosDebtorAccounts : (isOpd ? hosIpdAccounts : hosOpdAccounts))
                    .filter(a => !primaryCodes.has(a.code));

  let optHtml = `<option value="" class="text-danger">⚠️ กรุณาเลือกผังบัญชี (${patientType})...</option>`;
  
  // 1. หมวดผังลูกหนี้แนะนำสำหรับประเภทผู้ป่วยนี้
  if (primaryList.length > 0) {
    optHtml += `<optgroup label="-- ผังลูกหนี้แนะนำ (${patientType}) --">`;
    primaryList.forEach(acc => {
      const isSel = (currentSelectedCode && currentSelectedCode === acc.code) ? 'selected' : '';
      optHtml += `<option value="${acc.code}" ${isSel}>${acc.code} - ${acc.name}</option>`;
    });
    optHtml += `</optgroup>`;
  }
  
  // 2. หมวดผังลูกหนี้อื่นๆ ทั้งหมดใน tb_code
  if (otherList.length > 0) {
    optHtml += `<optgroup label="-- ผังลูกหนี้อื่นๆ ใน tb_code --">`;
    otherList.forEach(acc => {
      const isSel = (currentSelectedCode && currentSelectedCode === acc.code) ? 'selected' : '';
      optHtml += `<option value="${acc.code}" ${isSel}>${acc.code} - ${acc.name}</option>`;
    });
    optHtml += `</optgroup>`;
  }
  
  return optHtml;
}

// จัดการเมื่อผู้ใช้เลือกผังบัญชีด้วยตนเอง
function handleHosAccountSelect(selectEl, type, pttype) {
  const chosenCode = selectEl.value;
  const key = `${type}:${pttype}`;
  const badgeEl = document.getElementById(`badge_acc_${type}_${pttype}`);
  const rowEl = document.getElementById(`row_${type}_${pttype}`);
  const statusEl = document.getElementById(`status_save_${type}_${pttype}`);

  if (chosenCode) {
    hosCustomMappings[key] = chosenCode;
    if (badgeEl) {
      badgeEl.textContent = chosenCode;
      badgeEl.className = 'badge-acc bg-primary text-white border-primary';
    }
    selectEl.classList.remove('border-warning');
    selectEl.classList.add('border-success');
    if (rowEl) {
      rowEl.classList.remove('hos-row-unmapped');
      rowEl.dataset.accountcode = chosenCode;
      rowEl.dataset.unmapped = '0';
    }
    if (statusEl) {
      statusEl.innerHTML = `<span class="spinner-border spinner-border-sm text-primary" role="status"></span>`;
    }

    // บันทึกการจับคู่ลง config.json ทันทีผ่าน AJAX
    $.ajax({
      url: 'apihos/api_direct_debtor_pull.php',
      type: 'POST',
      data: {
        action: 'save_pttype_mapping',
        type: type,
        pttype: pttype,
        accountcode: chosenCode
      },
      dataType: 'json',
      success: function(res) {
        if (statusEl) {
          statusEl.innerHTML = `<span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size: 11px;" title="บันทึกการจับคู่ลงระบบเรียบร้อย"><i class='bx bx-check'></i> บันทึกแล้ว</span>`;
        }
        if (rowEl && res.data && res.data.accountname) {
          rowEl.dataset.accountname = res.data.accountname;
        }
      },
      error: function() {
        if (statusEl) {
          statusEl.innerHTML = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" style="font-size: 11px;"><i class='bx bx-x'></i> บันทึกไม่สำเร็จ</span>`;
        }
      }
    });

  } else {
    delete hosCustomMappings[key];
    if (badgeEl) {
      badgeEl.textContent = '-';
      badgeEl.className = 'badge-acc';
    }
    selectEl.classList.remove('border-success');
    selectEl.classList.add('border-warning');
    if (rowEl) {
      rowEl.classList.add('hos-row-unmapped');
      rowEl.dataset.accountcode = '';
      rowEl.dataset.unmapped = '1';
    }
    if (statusEl) {
      statusEl.innerHTML = '';
    }

    // ยกเลิกการจับคู่ใน config.json
    $.ajax({
      url: 'apihos/api_direct_debtor_pull.php',
      type: 'POST',
      data: {
        action: 'save_pttype_mapping',
        type: type,
        pttype: pttype,
        accountcode: ''
      },
      dataType: 'json'
    });
  }

  // คำนวณจำนวนสิทธิที่ยังไม่ได้ผูกผังคงเหลือ
  let remainingUnmapped = 0;
  document.querySelectorAll('.hos-right-row').forEach(tr => {
    if (tr.dataset.unmapped === '1') remainingUnmapped++;
  });
  const bUnmapped = document.getElementById('tab_badge_unmapped');
  if (bUnmapped) {
    bUnmapped.textContent = remainingUnmapped;
    if (remainingUnmapped > 0) {
      bUnmapped.className = 'badge bg-warning text-dark border border-warning-subtle rounded-pill ms-1';
    } else {
      bUnmapped.className = 'badge bg-secondary-subtle text-secondary rounded-pill ms-1';
    }
  }

  updateHosSelectedSummary();
}

// เรียงลำดับคอลัมน์ในตาราง
function sortHosTable(column) {
  const tbody = document.getElementById('tbodyHosRights');
  if (!tbody) return;

  const rows = Array.from(tbody.querySelectorAll('.hos-right-row'));
  if (rows.length === 0) return;

  // สลับทิศทางการเรียง
  if (hosSortColumn === column) {
    hosSortDirection = (hosSortDirection === 'asc') ? 'desc' : 'asc';
  } else {
    hosSortColumn = column;
    hosSortDirection = ['cases', 'income', 'debit'].includes(column) ? 'desc' : 'asc';
  }

  // อัปเดตไอคอนบนหัวตาราง
  document.querySelectorAll('#tblHosRights thead th.sortable').forEach(th => {
    th.classList.remove('sorted-asc', 'sorted-desc');
    const icon = th.querySelector('.sort-icon');
    if (icon) icon.className = 'bx bx-sort-alt-2 sort-icon';
  });

  const activeTh = document.querySelector(`#tblHosRights thead th[data-col="${column}"]`);
  if (activeTh) {
    activeTh.classList.add(hosSortDirection === 'asc' ? 'sorted-asc' : 'sorted-desc');
    const icon = activeTh.querySelector('.sort-icon');
    if (icon) {
      icon.className = `bx ${hosSortDirection === 'asc' ? 'bx-sort-up' : 'bx-sort-down'} sort-icon`;
    }
  }

  // ดำเนินการเรียงลำดับแถว
  rows.sort((a, b) => {
    let valA = a.dataset[column] || '';
    let valB = b.dataset[column] || '';

    if (['cases', 'income', 'debit'].includes(column)) {
      const numA = parseFloat(valA) || 0;
      const numB = parseFloat(valB) || 0;
      return hosSortDirection === 'asc' ? numA - numB : numB - numA;
    }

    if (column === 'pttype') {
      const numA = parseInt(valA, 10);
      const numB = parseInt(valB, 10);
      if (!isNaN(numA) && !isNaN(numB)) {
        return hosSortDirection === 'asc' ? numA - numB : numB - numA;
      }
    }

    const strA = String(valA);
    const strB = String(valB);
    const cmp = strA.localeCompare(strB, 'th', { sensitivity: 'base', numeric: true });
    return hosSortDirection === 'asc' ? cmp : -cmp;
  });

  // นำแถวที่เรียงแล้วกลับเข้า tbody
  rows.forEach(r => tbody.appendChild(r));
}

// กรองประเภทสิทธิ OPD / IPD / ยังไม่ผูกผัง (unmapped)
function filterHosType(type) {
  hosCurrentTypeTab = type;

  // อัปเดตสถานะปุ่มแท็บ
  document.querySelectorAll('#hosTypeFilterTabs .nav-link').forEach(btn => {
    btn.classList.remove('active', 'tab-opd', 'tab-ipd', 'tab-unmapped');
  });

  const activeBtn = document.getElementById(`tab_btn_${type.toLowerCase()}`);
  if (activeBtn) {
    activeBtn.classList.add('active');
    if (type === 'OPD') activeBtn.classList.add('tab-opd');
    if (type === 'IPD') activeBtn.classList.add('tab-ipd');
    if (type === 'unmapped') activeBtn.classList.add('tab-unmapped');
  }

  // แสดง/ซ่อนกล่องข้อความแนะนำเมื่ออยู่ในแท็บยังไม่ผูกผัง
  const unmappedNotice = document.getElementById('hos_unmapped_notice_box');
  if (unmappedNotice) {
    unmappedNotice.style.display = (type === 'unmapped') ? 'flex' : 'none';
  }

  applyHosTableFilter();
}

function applyHosTableFilter() {
  const term = (document.getElementById('hos_table_search')?.value || '').toLowerCase().trim();
  const rows = document.querySelectorAll('.hos-right-row');
  let visibleCount = 0;

  rows.forEach(r => {
    const rowType = r.dataset.type || '';
    const isUnmapped = (r.dataset.unmapped === '1' || r.dataset.initiallyUnmapped === '1');
    const text = (r.dataset.search || '').toLowerCase();

    let matchesType = false;
    if (hosCurrentTypeTab === 'all') {
      matchesType = true;
    } else if (hosCurrentTypeTab === 'OPD') {
      matchesType = (rowType === 'OPD');
    } else if (hosCurrentTypeTab === 'IPD') {
      matchesType = (rowType === 'IPD');
    } else if (hosCurrentTypeTab === 'unmapped') {
      matchesType = isUnmapped;
    }

    const matchesSearch = (!term || text.includes(term));

    if (matchesType && matchesSearch) {
      r.style.display = '';
      visibleCount++;
    } else {
      r.style.display = 'none';
    }
  });

  // แสดง Empty State เมื่อแท็บยังไม่ผูกผังไม่มีข้อมูลค้าง
  const emptyRow = document.getElementById('hos_unmapped_empty_row');
  if (emptyRow) {
    if (hosCurrentTypeTab === 'unmapped' && visibleCount === 0) {
      emptyRow.style.display = '';
    } else {
      emptyRow.style.display = 'none';
    }
  }

  updateHosSelectedSummary();
}

// ค้นหาในตาราง
function filterHosTable() {
  applyHosTableFilter();
}

// ====================================================================================
// เริ่มกระบวนการดึงข้อมูลแบบ Batch / Chunk Loop
// ====================================================================================
async function startHosBatchPull() {
  if (isPullingInProgress || !hosPreviewData) return;

  const checkedBoxes = document.querySelectorAll('.hos-right-chk:checked');
  if (checkedBoxes.length === 0) {
    Swal.fire({ icon: 'warning', title: 'ยังไม่ได้เลือกสิทธิ', text: 'กรุณาเลือกสิทธิที่ต้องการนำเข้าอย่างน้อย 1 สิทธิ' });
    return;
  }

  // แยกสิทธิที่เลือกระหว่าง OPD และ IPD
  const selectedOpdRights = [];
  const selectedIpdRights = [];
  let totalCasesToPull = 0;

  checkedBoxes.forEach(c => {
    const val = c.value;
    totalCasesToPull += parseInt(c.dataset.cases || 0);
    if (val.startsWith('OPD:')) {
      selectedOpdRights.push(val.replace('OPD:', ''));
    } else if (val.startsWith('IPD:')) {
      selectedIpdRights.push(val.replace('IPD:', ''));
    }
  });

  // ตรวจสอบความปลอดภัยทางการเงิน: ห้ามมีสิทธิที่ถูกล็อกหลุดเข้ามา
  for (const c of checkedBoxes) {
    const val = c.value;
    const [ptType, ptCode] = val.split(':');
    const row = hosPreviewData.rights.find(r => r.type === ptType && r.pttype === ptCode);
    if (row && row.is_locked) {
      Swal.fire({
        icon: 'error',
        title: 'พบสิทธิที่ถูกล็อกทางการเงิน',
        text: `สิทธิ [${ptType}] ${row.pttype} - ${row.pttypename} ถูกล็อก: ${row.lock_reason} (ห้ามนำเข้าซ้ำเด็ดขาด)`
      });
      return;
    }
  }

  // ตรวจสอบว่ามีสิทธิที่ยังไม่ได้เลือกผังบัญชีหรือไม่
  const unmappedWithoutSelection = [];
  checkedBoxes.forEach(c => {
    const val = c.value;
    const [ptType, ptCode] = val.split(':');
    const row = hosPreviewData.rights.find(r => r.type === ptType && r.pttype === ptCode);
    if (row && (row.is_unmapped || !row.accountcode || row.accountcode === '-')) {
      if (!hosCustomMappings[val] && !hosCustomMappings[ptCode]) {
        unmappedWithoutSelection.push(`[${ptType}] ${row.pttype} - ${row.pttypename}`);
      }
    }
  });

  if (unmappedWithoutSelection.length > 0) {
    const warnHtml = `
      <div class="text-start">
        <p class="text-warning fw-bold mb-2">⚠️ มี ${unmappedWithoutSelection.length} สิทธิที่ยังไม่ได้เลือกผังบัญชี:</p>
        <ul class="small text-muted mb-3" style="max-height: 150px; overflow-y: auto;">
          ${unmappedWithoutSelection.map(s => `<li>${s}</li>`).join('')}
        </ul>
        <p class="small mb-0">ท่านต้องการกลับไปเลือกผังบัญชีก่อน หรือยืนยันดึงต่อโดยไม่ระบุผังบัญชี?</p>
      </div>
    `;
    const warnRes = await Swal.fire({
      icon: 'warning',
      title: 'พบสิทธิที่ยังไม่ได้ผูกผังบัญชี',
      html: warnHtml,
      showCancelButton: true,
      confirmButtonColor: '#f59e0b',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'ดำเนินการต่อโดยไม่ระบุผัง',
      cancelButtonText: 'กลับไปเลือกผังบัญชี'
    });
    if (!warnRes.isConfirmed) {
      return;
    }
  }

  const confirmRes = await Swal.fire({
    title: 'ยืนยันการดึงข้อมูลจาก HOSxP?',
    html: `
      <div class="text-start">
        <p class="mb-2">ระบบจะดึงข้อมูลผู้ป่วยรายตัวและบันทึกลงฐานข้อมูล eDHS:</p>
        <ul class="small text-muted mb-3">
          <li><strong>จำนวนสิทธิที่เลือก:</strong> ${checkedBoxes.length} สิทธิ (ประมาณ ${totalCasesToPull.toLocaleString()} รายการ)</li>
          <li><strong>OPD:</strong> ${selectedOpdRights.length} สิทธิ | <strong>IPD:</strong> ${selectedIpdRights.length} สิทธิ</li>
        </ul>
      </div>
    `,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#10b981',
    cancelButtonColor: '#64748b',
    confirmButtonText: '<i class="bx bx-cloud-download"></i> ยืนยันเริ่มดึงข้อมูล',
    cancelButtonText: 'ยกเลิก'
  });

  if (!confirmRes.isConfirmed) return;

  // เตรียม UI แสดงความคืบหน้า
  isPullingInProgress = true;
  document.getElementById('btnStartPull').disabled = true;
  document.getElementById('btnPreviewHos').disabled = true;
  
  const progressBox = document.getElementById('hosProgressBox');
  const progressBar = document.getElementById('hosProgressBarInner');
  const statusText = document.getElementById('hosProgressStatusText');
  const percentText = document.getElementById('hosProgressPercentText');
  const detailText = document.getElementById('hosProgressDetailText');
  const cntInserted = document.getElementById('cntHosInserted');
  const cntUpdated = document.getElementById('cntHosUpdated');
  const cntSkipped = document.getElementById('cntHosSkipped');

  progressBox.style.display = 'block';
  progressBox.scrollIntoView({ behavior: 'smooth', block: 'center' });

  let totalInserted = 0;
  let totalUpdated = 0;
  let totalSkipped = 0;
  let grandProcessed = 0;
  const batchSize = 500;

  try {
    // ----------------------------------------------------
    // 1. ดึงชุดข้อมูล OPD (ถ้ามีสิทธิ OPD ที่เลือก)
    // ----------------------------------------------------
    if (selectedOpdRights.length > 0) {
      let offset = 0;
      let hasMore = true;
      let opdTotalCases = 0;

      while (hasMore) {
        statusText.textContent = `กำลังดึงข้อมูล OPD จาก HOSxP (Offset: ${offset.toLocaleString()})...`;
        
        const payload = {
          action: 'pull_batch',
          mode: hosPreviewData.params.mode,
          patient_type: 'opd',
          selected_rights: selectedOpdRights,
          custom_mappings: JSON.stringify(hosCustomMappings),
          batch_size: batchSize,
          offset: offset,
          total_cases: opdTotalCases
        };

        if (hosPreviewData.params.mode === 'month') {
          payload.month = hosPreviewData.params.monthtxt;
        } else {
          payload.date_start = hosPreviewData.params.date_start;
          payload.date_end = hosPreviewData.params.date_end;
        }

        const res = await $.ajax({
          url: 'apihos/api_direct_debtor_pull.php',
          type: 'POST',
          data: payload,
          dataType: 'json'
        });

        if (res.status !== 'success') {
          throw new Error(res.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล OPD');
        }

        opdTotalCases = res.total_cases;
        totalInserted += res.batch_inserted;
        totalUpdated += res.batch_updated;
        totalSkipped += (res.batch_skipped || 0);
        grandProcessed += res.rows_processed;

        cntInserted.textContent = totalInserted.toLocaleString();
        cntUpdated.textContent = totalUpdated.toLocaleString();
        if (cntSkipped) cntSkipped.textContent = totalSkipped.toLocaleString();

        const currentPct = totalCasesToPull > 0 ? Math.min(99, Math.round((grandProcessed / totalCasesToPull) * 100)) : 50;
        progressBar.style.width = currentPct + '%';
        percentText.textContent = currentPct + '%';
        detailText.textContent = `ประมวลผลแล้ว ${grandProcessed.toLocaleString()} / ~${totalCasesToPull.toLocaleString()} รายการ`;

        hasMore = res.has_more;
        offset = res.next_offset;
      }
    }

    // ----------------------------------------------------
    // 2. ดึงชุดข้อมูล IPD (ถ้ามีสิทธิ IPD ที่เลือก)
    // ----------------------------------------------------
    if (selectedIpdRights.length > 0) {
      let offset = 0;
      let hasMore = true;
      let ipdTotalCases = 0;

      while (hasMore) {
        statusText.textContent = `กำลังดึงข้อมูล IPD จาก HOSxP (Offset: ${offset.toLocaleString()})...`;

        const payload = {
          action: 'pull_batch',
          mode: hosPreviewData.params.mode,
          patient_type: 'ipd',
          selected_rights: selectedIpdRights,
          custom_mappings: JSON.stringify(hosCustomMappings),
          batch_size: batchSize,
          offset: offset,
          total_cases: ipdTotalCases
        };

        if (hosPreviewData.params.mode === 'month') {
          payload.month = hosPreviewData.params.monthtxt;
        } else {
          payload.date_start = hosPreviewData.params.date_start;
          payload.date_end = hosPreviewData.params.date_end;
        }

        const res = await $.ajax({
          url: 'apihos/api_direct_debtor_pull.php',
          type: 'POST',
          data: payload,
          dataType: 'json'
        });

        if (res.status !== 'success') {
          throw new Error(res.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล IPD');
        }

        ipdTotalCases = res.total_cases;
        totalInserted += res.batch_inserted;
        totalUpdated += res.batch_updated;
        totalSkipped += (res.batch_skipped || 0);
        grandProcessed += res.rows_processed;

        cntInserted.textContent = totalInserted.toLocaleString();
        cntUpdated.textContent = totalUpdated.toLocaleString();
        if (cntSkipped) cntSkipped.textContent = totalSkipped.toLocaleString();

        const currentPct = totalCasesToPull > 0 ? Math.min(99, Math.round((grandProcessed / totalCasesToPull) * 100)) : 90;
        progressBar.style.width = currentPct + '%';
        percentText.textContent = currentPct + '%';
        detailText.textContent = `ประมวลผลแล้ว ${grandProcessed.toLocaleString()} / ~${totalCasesToPull.toLocaleString()} รายการ`;

        hasMore = res.has_more;
        offset = res.next_offset;
      }
    }

    // เสร็จสิ้นกระบวนการ 100%
    progressBar.style.width = '100%';
    percentText.textContent = '100%';
    statusText.innerHTML = '<span class="text-success"><i class="bx bx-check-circle"></i> นำเข้าข้อมูลเสร็จสมบูรณ์ 100%</span>';

    await Swal.fire({
      icon: 'success',
      title: 'นำเข้าข้อมูลจาก HOSxP สำเร็จ!',
      html: `
        <div class="text-start">
          <p class="mb-2">ประมวลผลบันทึกข้อมูลเรียบร้อยแล้ว:</p>
          <ul class="small mb-0">
            <li><strong>รวมรายการประมวลผล:</strong> ${grandProcessed.toLocaleString()} รายการ</li>
            <li><strong>บันทึกใหม่:</strong> <span class="text-success fw-bold">${totalInserted.toLocaleString()}</span> รายการ</li>
            <li><strong>ข้าม (ตั้งหนี้/มีบิลแล้ว):</strong> <span class="text-warning fw-bold">${totalSkipped.toLocaleString()}</span> รายการ</li>
            <li><strong>อัปเดตข้อมูลเดิม:</strong> ${totalUpdated.toLocaleString()} รายการ</li>
            <li><strong>งวดเดือน:</strong> ${hosPreviewData.params.monthtxt}</li>
          </ul>
        </div>
      `,
      confirmButtonText: 'ตกลง'
    });

    // รีเฟรชหน้านำเข้าลูกหนี้สิทธิเพื่ออัปเดตตารางและกราฟ
    location.reload();

  } catch (err) {
    let errMsg = '';
    if (err && err.responseJSON && err.responseJSON.message) {
      errMsg = err.responseJSON.message;
    } else if (err && err.responseText) {
      try {
        const parsed = JSON.parse(err.responseText);
        errMsg = parsed.message || err.responseText;
      } catch (pe) {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = err.responseText;
        errMsg = (tempDiv.textContent || tempDiv.innerText || err.responseText).trim();
      }
      if (errMsg.length > 300) errMsg = errMsg.substring(0, 300) + '...';
    } else if (err && err.message) {
      errMsg = err.message;
    } else {
      errMsg = typeof err === 'object' ? JSON.stringify(err) : String(err);
    }

    statusText.innerHTML = `<span class="text-danger"><i class="bx bx-error"></i> เกิดข้อผิดพลาด: ${errMsg}</span>`;
    Swal.fire({
      icon: 'error',
      title: 'เกิดข้อผิดพลาดระหว่างนำเข้า',
      html: `<div class="text-start alert alert-danger mb-0" style="font-size:0.9rem; word-break:break-word;">${errMsg}</div>`
    });
  } finally {
    isPullingInProgress = false;
    document.getElementById('btnStartPull').disabled = false;
    document.getElementById('btnPreviewHos').disabled = false;
  }
}

// ====================================================================================
// ลบข้อมูลลูกหนี้สิทธิอย่างปลอดภัย (Safe Rollback)
// ====================================================================================
async function executeSafeDeleteHos() {
  const monthtxt = document.getElementById('hos_delete_month').value.trim();
  const patientType = document.getElementById('hos_delete_patient_type').value;
  const accCode = document.getElementById('hos_delete_acc').value.trim();

  if (!monthtxt) {
    Swal.fire({ icon: 'warning', title: 'กรุณาระบุงวดเดือน', text: 'ตัวอย่างเช่น 8-2026' });
    return;
  }

  const confirmRes = await Swal.fire({
    title: `ยืนยันการลบข้อมูลลูกหนี้?`,
    html: `
      <div class="text-start">
        <p class="text-danger fw-bold mb-2">คำเตือน: การลบข้อมูลจะไม่สามารถกู้คืนได้</p>
        <ul class="small text-muted mb-0">
          <li><strong>งวดเดือน:</strong> ${monthtxt}</li>
          <li><strong>ประเภท:</strong> ${patientType.toUpperCase()}</li>
          <li><strong>ผังบัญชี:</strong> ${accCode || 'ทุกผัง'}</li>
        </ul>
        <p class="small text-muted mt-2">ระบบจะบล็อกการลบทันที หากมีรายการใดออกใบเสร็จหรือได้รับเงินชดเชยแล้ว</p>
      </div>
    `,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#64748b',
    confirmButtonText: '<i class="bx bx-trash"></i> ยืนยันการลบ',
    cancelButtonText: 'ยกเลิก'
  });

  if (!confirmRes.isConfirmed) return;

  Swal.fire({
    title: 'กำลังตรวจสอบความปลอดภัยทางการเงิน...',
    text: 'ตรวจสอบสถานะใบเสร็จและเงินชดเชยข้าม 3 ตาราง',
    allowOutsideClick: false,
    didOpen: () => { Swal.showLoading(); }
  });

  $.ajax({
    url: 'apihos/api_direct_debtor_pull.php',
    type: 'POST',
    data: {
      action: 'delete_batch',
      monthtxt: monthtxt,
      patient_type: patientType,
      accountcode: accCode
    },
    dataType: 'json',
    success: function(res) {
      if (res.status === 'success') {
        Swal.fire({
          icon: 'success',
          title: 'ลบข้อมูลสำเร็จ',
          text: `ลบข้อมูลลูกหนี้งวด ${monthtxt} เรียบร้อยแล้ว (จำนวน ${res.total_deleted} รายการ)`
        }).then(() => {
          location.reload();
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'ไม่สามารถลบข้อมูลได้',
          text: res.message
        });
      }
    },
    error: function(xhr, status, err) {
      Swal.fire({
        icon: 'error',
        title: 'เกิดข้อผิดพลาดในการเรียก API',
        text: xhr.responseText || err
      });
    }
  });
}

// ผูก Event Listener เมื่อ Modal กำลังจะเปิด ให้ตั้งค่าตัวเลือกเดือนเสมอ
$(document).on('show.bs.modal', '#modalDirectDebtorPull', function() {
  try {
    initHosMonthOptions();
    initHosThaiDatePicker();
  } catch (e) {}
  var modalEl = document.getElementById('modalDirectDebtorPull');
  if (modalEl && modalEl.parentElement !== document.body) {
    document.body.appendChild(modalEl);
  }
});

// Event Delegation สำรองสำหรับปุ่มเปิด Modal
$(document).on('click', '#btnOpenDirectHosPull', function(e) {
  e.preventDefault();
  openDirectHosPullModal();
});

// ====================================================================
// Patient-Level Inspection Modal (ดูรายละเอียดผู้ป่วยรายคนก่อนนำเข้า)
// ====================================================================
let currentHosPatientList = [];
let currentHosPatientType = 'OPD';
let currentHosPatientPttype = '';

function formatHosCid(cid) {
  if (!cid) return '-';
  const c = String(cid).trim();
  if (c.length === 13) {
    return `${c.substring(0, 1)}-${c.substring(1, 5)}-${c.substring(5, 10)}-${c.substring(10, 12)}-${c.substring(12)}`;
  }
  return c;
}

function openHosPatientPreviewModal(type, pttype) {
  if (!hosPreviewData) {
    Swal.fire({ icon: 'warning', title: 'กรุณากดตรวจสอบยอดก่อน', text: 'ไม่พบข้อมูลสรุปยอดจาก HOSxP' });
    return;
  }

  type = (type || 'OPD').toUpperCase();
  currentHosPatientType = type;
  currentHosPatientPttype = pttype;

  let rightItem = null;
  if (hosPreviewData && Array.isArray(hosPreviewData.rights)) {
    rightItem = hosPreviewData.rights.find(x => x.type === type && String(x.pttype) === String(pttype));
  }

  const pttypename = rightItem ? rightItem.pttypename : `สิทธิ ${pttype}`;
  const accCode = rightItem ? (rightItem.accountcode || '') : '';
  const accName = rightItem ? (rightItem.accountname || '') : '';
  const isOpd = (type === 'OPD');

  // ตั้งค่า Header ของ Sub-Modal
  const badgeTypeEl = document.getElementById('hos_sub_badge_type');
  if (badgeTypeEl) {
    badgeTypeEl.className = `badge ${isOpd ? 'bg-primary' : 'bg-success'} rounded-pill px-2 py-1`;
    badgeTypeEl.innerHTML = `<i class='bx ${isOpd ? 'bx-user' : 'bx-hotel'} me-1'></i>${type}`;
  }

  const badgePttypeEl = document.getElementById('hos_sub_badge_pttype');
  if (badgePttypeEl) {
    badgePttypeEl.textContent = `[${pttype}] ${pttypename}`;
  }

  const badgeAccEl = document.getElementById('hos_sub_badge_acc');
  if (badgeAccEl) {
    badgeAccEl.textContent = accCode ? `ผัง: ${accCode} (${accName})` : 'ยังไม่ได้ผูกผังบัญชี';
    badgeAccEl.className = accCode ? 'badge bg-secondary-subtle text-secondary border rounded-pill px-2 py-1' : 'badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1';
  }

  // คำนวณช่วงวันที่สำหรับแสดงที่ Subtitle
  const isMonth = document.getElementById('hos_mode_month').checked;
  const monthVal = document.getElementById('hos_month_input').value.trim();
  const dateStart = document.getElementById('hos_date_start').value;
  const dateEnd = document.getElementById('hos_date_end').value;

  const subtitleEl = document.getElementById('hos_sub_modal_subtitle');
  if (subtitleEl) {
    subtitleEl.textContent = isMonth ? `งวดเดือน: ${monthVal}` : `ช่วงวันที่: ${dateStart} ถึง ${dateEnd}`;
  }

  // รีเซ็ตการค้นหาและตัวกรองสถานะ
  const searchInput = document.getElementById('hos_sub_search_input');
  if (searchInput) searchInput.value = '';
  const rdoAll = document.getElementById('hos_sub_filter_all');
  if (rdoAll) rdoAll.checked = true;

  // รีเซ็ตตัวเลขสรุป
  document.getElementById('hos_sub_total_cases').textContent = rightItem ? Number(rightItem.cases).toLocaleString() + ' ราย' : '-';
  document.getElementById('hos_sub_total_income').textContent = rightItem ? Number(rightItem.income).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ฿' : '-';
  document.getElementById('hos_sub_total_debit').textContent = rightItem ? Number(rightItem.debit).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ฿' : '-';

  document.getElementById('hos_sub_cnt_all').textContent = '0';
  document.getElementById('hos_sub_cnt_new').textContent = '0';
  document.getElementById('hos_sub_cnt_imported').textContent = '0';
  document.getElementById('hos_sub_cnt_billed').textContent = '0';

  // แสดง Loading Spinner และซ่อนตาราง
  document.getElementById('hos_sub_loading_box').style.display = 'block';
  document.getElementById('hos_sub_table_container').style.display = 'none';
  document.getElementById('hos_sub_limit_notice').style.display = 'none';

  // เปิด Sub-Modal
  const subModalEl = document.getElementById('modalHosPatientPreview');
  if (subModalEl) {
    if (subModalEl.parentElement !== document.body) {
      document.body.appendChild(subModalEl);
    }
    const bsModal = (typeof bootstrap !== 'undefined' && bootstrap.Modal)
      ? bootstrap.Modal.getOrCreateInstance(subModalEl)
      : (window.jQuery ? $('#modalHosPatientPreview').modal('show') : null);
    if (bsModal && typeof bsModal.show === 'function') {
      bsModal.show();
    }
  }

  // ส่งคำขอ AJAX ดึงรายชื่อผู้ป่วยจาก HOSxP
  let postData = {
    action: 'preview_patient_list',
    patient_type: type,
    pttype: pttype,
    mode: isMonth ? 'month' : 'range',
    limit: 1000
  };

  if (isMonth) {
    postData.month = monthVal;
  } else {
    postData.date_start = dateStart;
    postData.date_end = dateEnd;
  }

  $.ajax({
    url: 'apihos/api_direct_debtor_pull.php',
    type: 'POST',
    data: postData,
    dataType: 'json',
    success: function(res) {
      document.getElementById('hos_sub_loading_box').style.display = 'none';
      if (res.status === 'success') {
        currentHosPatientList = res.patients || [];
        renderHosPatientsTable(res);
      } else {
        document.getElementById('hos_sub_loading_box').style.display = 'block';
        document.getElementById('hos_sub_loading_box').innerHTML = `
          <div class="text-danger py-4">
            <i class='bx bx-error fs-1 mb-2'></i>
            <h6 class="fw-bold">ไม่สามารถดึงข้อมูลรายชื่อผู้ป่วยได้</h6>
            <p class="small text-muted mb-0">${res.message || 'เกิดข้อผิดพลาดในการติดต่อ HOSxP'}</p>
          </div>
        `;
      }
    },
    error: function(xhr, status, err) {
      document.getElementById('hos_sub_loading_box').style.display = 'block';
      document.getElementById('hos_sub_loading_box').innerHTML = `
        <div class="text-danger py-4">
          <i class='bx bx-error fs-1 mb-2'></i>
          <h6 class="fw-bold">เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์</h6>
          <p class="small text-muted mb-0">${xhr.responseText || err}</p>
        </div>
      `;
    }
  });
}

function renderHosPatientsTable(res) {
  // สรุปยอดจริงจาก response
  document.getElementById('hos_sub_total_cases').textContent = Number(res.total_cases).toLocaleString() + ' ราย';
  document.getElementById('hos_sub_total_income').textContent = Number(res.sum_income).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ฿';
  document.getElementById('hos_sub_total_debit').textContent = Number(res.sum_debit).toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ฿';

  // นับสถานะ
  let cntNew = 0;
  let cntImported = 0;
  let cntBilled = 0;

  currentHosPatientList.forEach(p => {
    if (p.edhs_status === 'BILLED') cntBilled++;
    else if (p.edhs_status === 'IMPORTED') cntImported++;
    else cntNew++;
  });

  document.getElementById('hos_sub_cnt_all').textContent = currentHosPatientList.length;
  document.getElementById('hos_sub_cnt_new').textContent = cntNew;
  document.getElementById('hos_sub_cnt_imported').textContent = cntImported;
  document.getElementById('hos_sub_cnt_billed').textContent = cntBilled;

  // เตือนกรณีเคสทั้งหมดเกิน Limit
  const limitNotice = document.getElementById('hos_sub_limit_notice');
  if (limitNotice) {
    if (res.total_cases > res.showing_count) {
      limitNotice.textContent = `(แสดง ${res.showing_count} รายแรก จากทั้งหมด ${Number(res.total_cases).toLocaleString()} ราย)`;
      limitNotice.style.display = 'inline-block';
    } else {
      limitNotice.style.display = 'none';
    }
  }

  // เรนเดอร์ตารางและแสดงผล
  filterHosPatientTable();
  document.getElementById('hos_sub_table_container').style.display = 'block';
}

function filterHosPatientTable() {
  const searchInput = document.getElementById('hos_sub_search_input');
  const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
  
  const selectedRadio = document.querySelector('input[name="hos_sub_status_filter"]:checked');
  const statusFilter = selectedRadio ? selectedRadio.value : 'ALL';

  const tbody = document.getElementById('tbodyHosPatientsPreview');
  if (!tbody) return;

  let filtered = currentHosPatientList.filter(p => {
    // กรองสถานะ
    if (statusFilter !== 'ALL') {
      if (statusFilter === 'NEW' && p.edhs_status !== 'NEW') return false;
      if (statusFilter === 'IMPORTED' && p.edhs_status !== 'IMPORTED') return false;
      if (statusFilter === 'BILLED' && p.edhs_status !== 'BILLED') return false;
    }

    // กรองค้นหา
    if (query) {
      const matchVn = (p.vn_an || '').toLowerCase().includes(query);
      const matchHn = (p.hn || '').toLowerCase().includes(query);
      const matchName = (p.ptname || '').toLowerCase().includes(query);
      const matchCid = (p.cid || '').toLowerCase().includes(query);
      const matchPdx = (p.pdx || '').toLowerCase().includes(query);
      const matchDep = (p.department || '').toLowerCase().includes(query);
      return matchVn || matchHn || matchName || matchCid || matchPdx || matchDep;
    }
    return true;
  });

  if (filtered.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="13" class="text-center py-5 text-muted">
          <i class='bx bx-search-alt fs-1 d-block mb-2 text-secondary opacity-50'></i>
          <span class="fw-semibold">ไม่พบรายชื่อผู้ป่วยที่ตรงกับเงื่อนไข</span>
        </td>
      </tr>
    `;
    document.getElementById('hos_sub_footer_count').textContent = `แสดง 0 จาก ${currentHosPatientList.length} รายการ`;
    return;
  }

  let rowsHtml = '';
  filtered.forEach((p, idx) => {
    let statusBadge = '';
    if (p.edhs_status === 'BILLED') {
      statusBadge = `<span class="badge bg-danger text-white rounded-pill px-2.5 py-1.5 fw-bold shadow-xs" style="font-size: 11px;"><i class='bx bx-lock-alt me-1'></i>มีบิล/ตัดแล้ว</span>`;
    } else if (p.edhs_status === 'IMPORTED') {
      statusBadge = `<span class="badge bg-secondary text-white rounded-pill px-2.5 py-1.5 fw-bold shadow-xs" style="font-size: 11px;"><i class='bx bx-check me-1'></i>นำเข้าแล้ว</span>`;
    } else {
      statusBadge = `<span class="badge bg-success text-white rounded-pill px-2.5 py-1.5 fw-bold shadow-xs" style="font-size: 11px;"><i class='bx bx-plus-circle me-1'></i>ยังไม่เคยนำเข้า</span>`;
    }

    const timeStr = p.service_time ? ` <span class="text-secondary fw-semibold ms-1" style="font-size: 11.5px; white-space: nowrap;"><i class='bx bx-time-five me-0.5'></i>${p.service_time} น.</span>` : '';
    const sexAgeStr = `<span class="fw-bold text-dark">${p.sex || '-'}</span> <span class="badge bg-light text-dark border font-monospace ms-1 px-1.5 py-0.5" style="font-size: 11px; white-space: nowrap;">${p.age ? p.age + ' ปี' : '-'}</span>`;
    const pdxBadge = p.pdx ? `<span class="hos-cell-pdx">${p.pdx}</span>` : '<span class="text-muted">-</span>';

    const debitVal = parseFloat(p.debit || 0);
    const debitClass = (debitVal > 0) ? (currentHosPatientType === 'OPD' ? 'text-primary fw-bold' : 'text-success fw-bold') : 'text-secondary fw-semibold';

    rowsHtml += `
      <tr>
        <td class="text-center"><span class="hos-cell-idx">${idx + 1}</span></td>
        <td class="text-center hos-cell-vn">${p.vn_an}</td>
        <td class="text-center hos-cell-hn">${p.hn}</td>
        <td class="text-start hos-cell-name">${p.ptname || '-'}</td>
        <td class="text-center hos-cell-cid">${formatHosCid(p.cid)}</td>
        <td class="text-center" style="white-space: nowrap;">${sexAgeStr}</td>
        <td class="text-center" style="white-space: nowrap;"><span class="fw-bold text-dark">${p.service_date_th || p.service_date}</span>${timeStr}</td>
        <td class="text-start" style="white-space: nowrap;"><span class="fw-semibold text-dark">${p.department || '-'}</span></td>
        <td class="text-center" style="white-space: nowrap;">${pdxBadge}</td>
        <td class="text-end fw-bold text-dark">${Number(p.income).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
        <td class="text-end fw-semibold text-secondary">${Number(p.paid_money).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
        <td class="text-end ${debitClass}">${Number(p.debit).toLocaleString(undefined, {minimumFractionDigits: 2})}</td>
        <td class="text-center" style="white-space: nowrap;">${statusBadge}</td>
      </tr>
    `;
  });

  tbody.innerHTML = rowsHtml;
  document.getElementById('hos_sub_footer_count').textContent = `แสดง ${filtered.length.toLocaleString()} จากทั้งหมด ${currentHosPatientList.length.toLocaleString()} รายการ`;
}

// จัดการ Stacking Context ของ Backdrop เมื่อเปิด Sub-Modal ซ้อน Fullscreen Modal
$(document).on('show.bs.modal', '#modalHosPatientPreview', function () {
  setTimeout(function () {
    var backdrops = document.querySelectorAll('.modal-backdrop');
    if (backdrops.length > 1) {
      backdrops[backdrops.length - 1].style.zIndex = '10065';
    }
  }, 20);
});

$(document).on('hidden.bs.modal', '#modalHosPatientPreview', function () {
  if ($('#modalDirectDebtorPull').hasClass('show')) {
    document.body.classList.add('modal-open');
  }
});
</script>

<script src="<?= $hos_dp_prefix ?>/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
<script src="<?= $hos_dp_prefix ?>/vendor/libs/bootstrap-datepicker/bootstrap-datepicker.th.min.js"></script>
<script>
$(document).ready(function() {
  initHosThaiDatePicker();
});
</script>

