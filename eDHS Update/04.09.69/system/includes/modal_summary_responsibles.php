<?php
// system/includes/modal_summary_responsibles.php
// Modal ตั้งค่ารายชื่อและหน้าที่ผู้ลงนามรายงานสรุปลูกหนี้สิทธิ (แยก 3 ส่วน + ทะเบียนคุมภาพรวม)

if (!isset($conn)) {
    require_once __DIR__ . '/../database_config/config.php';
}
require_once __DIR__ . '/signers_helper.php';

// ดึงข้อมูลการตั้งค่าผ่าน signers_helper (Zero-Migration JSON + Fallback DB)
$signers_cfg = get_signers_config($conn);

$officers_list = $signers_cfg['officers_list'] ?? [];

// ค่าตั้งต้นสำหรับ 1. ตั้งลูกหนี้ (งานประกันสุขภาพ - ตั้งลูกหนี้.php)
$setup_name1 = $signers_cfg['setup']['name1'] ?? '';
$setup_pos1  = $signers_cfg['setup']['pos1'] ?? '';
$setup_role1 = $signers_cfg['setup']['role1'] ?? "ผู้จัดทำรายงานลูกหนี้สิทธิ\nกลุ่มงานประกันสุขภาพ";

$setup_name2 = $signers_cfg['setup']['name2'] ?? '';
$setup_pos2  = $signers_cfg['setup']['pos2'] ?? '';
$setup_role2 = $signers_cfg['setup']['role2'] ?? "ผู้ตรวจสอบรายงานลูกหนี้สิทธิ\nกลุ่มงานประกันสุขภาพ";

$setup_name3 = $signers_cfg['setup']['name3'] ?? '';
$setup_pos3  = $signers_cfg['setup']['pos3'] ?? '';
$setup_role3 = $signers_cfg['setup']['role3'] ?? "หัวหน้ากลุ่มงานประกันสุขภาพ";

// ค่าตั้งต้นสำหรับ 2. การตัดลูกหนี้ (Debtor-rights.php)
$cut_name1 = $signers_cfg['cut']['name1'] ?? '';
$cut_pos1  = $signers_cfg['cut']['pos1'] ?? '';
$cut_role1 = $signers_cfg['cut']['role1'] ?? "ผู้จัดทำรายงานลูกหนี้สิทธิ\nกลุ่มงานประกันสุขภาพ";

$cut_name2 = $signers_cfg['cut']['name2'] ?? '';
$cut_pos2  = $signers_cfg['cut']['pos2'] ?? '';
$cut_role2 = $signers_cfg['cut']['role2'] ?? "ผู้ตรวจสอบรายงานลูกหนี้สิทธิ\nกลุ่มงานประกันสุขภาพ";

$cut_name3 = $signers_cfg['cut']['name3'] ?? '';
$cut_pos3  = $signers_cfg['cut']['pos3'] ?? '';
$cut_role3 = $signers_cfg['cut']['role3'] ?? "ผู้บันทึกลูกหนี้สิทธิ\nกลุ่มงานบัญชี";

// ค่าตั้งต้นสำหรับ 3. การตัดลูกหนี้รายตัว (ลูกหนี้รายตัว2.php)
$item_name1 = $signers_cfg['item']['name1'] ?? '';
$item_pos1  = $signers_cfg['item']['pos1'] ?? '';
$item_role1 = $signers_cfg['item']['role1'] ?? "ผู้จัดทำรายงานลูกหนี้สิทธิ";

$item_name2 = $signers_cfg['item']['name2'] ?? '';
$item_pos2  = $signers_cfg['item']['pos2'] ?? '';
$item_role2 = $signers_cfg['item']['role2'] ?? "ผู้ตรวจสอบรายงานลูกหนี้สิทธิ";

$item_name3 = $signers_cfg['item']['name3'] ?? '';
$item_pos3  = $signers_cfg['item']['pos3'] ?? '';
$item_role3 = $signers_cfg['item']['role3'] ?? "ผู้บันทึกลูกหนี้สิทธิ";

// ค่าตั้งต้นสำหรับ 4. ทะเบียนคุมภาพรวม (5 ท่าน)
$s1 = $signers_cfg['overview']['name1'] ?? '';
$p1 = $signers_cfg['overview']['pos1'] ?? '';
$s2 = $signers_cfg['overview']['name2'] ?? '';
$p2 = $signers_cfg['overview']['pos2'] ?? '';
$s3 = $signers_cfg['overview']['name3'] ?? '';
$p3 = $signers_cfg['overview']['pos3'] ?? '';
$s4 = $signers_cfg['overview']['name4'] ?? '';
$p4 = $signers_cfg['overview']['pos4'] ?? '';
$s5 = $signers_cfg['overview']['name5'] ?? '';
$p5 = $signers_cfg['overview']['pos5'] ?? 'ผู้อำนวยการโรงพยาบาล';
?>

<!-- Hidden inputs เก็บค่าสำหรับระบบเดิมและฟังก์ชัน settingtxt() -->
<input type="hidden" id="s1txt" name="s1" value="<?= htmlspecialchars($s1) ?>">
<input type="hidden" id="p1txt" name="p1" value="<?= htmlspecialchars($p1) ?>">
<input type="hidden" id="s2txt" name="s2" value="<?= htmlspecialchars($s2) ?>">
<input type="hidden" id="p2txt" name="p2" value="<?= htmlspecialchars($p2) ?>">
<input type="hidden" id="s3txt" name="s3" value="<?= htmlspecialchars($s3) ?>">
<input type="hidden" id="p3txt" name="p3" value="<?= htmlspecialchars($p3) ?>">
<input type="hidden" id="s4txt" name="s4" value="<?= htmlspecialchars($s4) ?>">
<input type="hidden" id="p4txt" name="p4" value="<?= htmlspecialchars($p4) ?>">
<input type="hidden" id="s5txt" name="s5" value="<?= htmlspecialchars($s5) ?>">
<input type="hidden" id="p5txt" name="p5" value="<?= htmlspecialchars($p5) ?>">

<!-- Modal ตั้งค่ารายชื่อและหน้าที่ผู้ลงนามรายงานสรุปลูกหนี้สิทธิ -->
<div class="modal fade" id="setting_summary-report" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg border-0">
      
      <div class="modal-header bg-primary text-white py-3">
        <h5 class="modal-title text-white d-flex align-items-center mb-0" id="exampleModalLabel4">
          <i class="bx bx-user-check fs-4 me-2"></i> ตั้งค่ารายชื่อและหน้าที่ผู้ลงนามรายงานสรุปลูกหนี้สิทธิ
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body p-4" style="background-color: #f8fafc;">
        
        <!-- แถบคำแนะนำ -->
        <div class="alert alert-primary d-flex align-items-center mb-3 shadow-sm bg-white" role="alert" style="border-left: 5px solid #3b82f6;">
          <i class="bx bx-info-circle fs-3 me-3 text-primary"></i>
          <div>
            <div class="fw-bold text-dark mb-1">คำแนะนำการกำหนดผู้ลงนาม (Signatory Roles Management):</div>
            <div class="text-muted small">
              ท่านสามารถจัดการบัญชีรายชื่อบุคลากร และกำหนดผู้ลงนาม 3 ท่านแยกตามแต่ละรายงาน (การตั้งลูกหนี้ของงานประกันฯ, การตัดลูกหนี้, และการตัดลูกหนี้รายตัว) ได้อย่างอิสระ พร้อมระบบจำลองลายเซ็นแบบ Real-time
            </div>
          </div>
        </div>

        <!-- 1. กล่องบัญชีรายชื่อบุคลากร (Officer Roster) -->
        <div class="card border mb-4 shadow-sm">
          <div class="card-header bg-white py-2 px-3 d-flex justify-content-between align-items-center border-bottom">
            <span class="fw-bold text-dark d-flex align-items-center">
              <i class="bx bx-group text-primary me-2 fs-5"></i> บัญชีรายชื่อบุคลากร (Officer Roster)
              <span class="badge bg-label-secondary ms-2 small" id="officerCountBadge"><?= count($officers_list) ?> ท่าน</span>
            </span>
            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" id="btnAddOfficerRow">
              <i class="bx bx-plus me-1"></i> เพิ่มรายชื่อบุคลากร
            </button>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
              <table class="table table-sm table-hover align-middle mb-0" id="tableOfficers">
                <thead class="table-light sticky-top">
                  <tr>
                    <th style="width: 5%; text-align: center;" class="py-2">#</th>
                    <th style="width: 45%;" class="py-2">ชื่อ - นามสกุล ผู้รับผิดชอบ</th>
                    <th style="width: 44%;" class="py-2">ตำแหน่ง</th>
                    <th style="width: 6%; text-align: center;" class="py-2">ลบ</th>
                  </tr>
                </thead>
                <tbody id="officerTableBody">
                  <?php foreach ($officers_list as $rowIdx => $off): ?>
                  <tr class="officer-row">
                    <td class="text-center row-num fw-bold text-muted"><?= ($rowIdx + 1) ?></td>
                    <td>
                      <input type="text" class="form-control form-control-sm officer-name" placeholder="ระบุชื่อ-นามสกุล..." value="<?= htmlspecialchars($off['name'] ?? '') ?>">
                    </td>
                    <td>
                      <input type="text" class="form-control form-control-sm officer-pos" placeholder="ระบุตำแหน่ง..." value="<?= htmlspecialchars($off['pos'] ?? '') ?>">
                    </td>
                    <td class="text-center">
                      <button type="button" class="btn btn-sm btn-icon btn-outline-danger btn-remove-officer" title="ลบแถว">
                        <i class="bx bx-trash"></i>
                      </button>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- 2. แท็บกำหนดผู้ลงนามแยกตามรายงาน 4 แท็บ -->
        <div class="card border shadow-sm">
          <div class="card-header bg-white p-2 border-bottom">
            <ul class="nav nav-pills nav-justified flex-nowrap w-100 gap-1" id="signatoryTabs" role="tablist">
              <li class="nav-item flex-fill" role="presentation">
                <button class="nav-link active fw-bold py-2 px-1 text-nowrap" id="tab-setup-btn" data-bs-toggle="pill" data-bs-target="#tab-setup" type="button" role="tab" aria-selected="true">
                  <i class="bx bx-file-blank me-1"></i> 1. ตั้งลูกหนี้
                </button>
              </li>
              <li class="nav-item flex-fill" role="presentation">
                <button class="nav-link fw-bold py-2 px-1 text-nowrap" id="tab-cut-btn" data-bs-toggle="pill" data-bs-target="#tab-cut" type="button" role="tab" aria-selected="false">
                  <i class="bx bx-cut me-1"></i> 2. การตัดลูกหนี้
                </button>
              </li>
              <li class="nav-item flex-fill" role="presentation">
                <button class="nav-link fw-bold py-2 px-1 text-nowrap" id="tab-item-btn" data-bs-toggle="pill" data-bs-target="#tab-item" type="button" role="tab" aria-selected="false">
                  <i class="bx bx-user-pin me-1"></i> 3. ตัดลูกหนี้รายตัว
                </button>
              </li>
              <li class="nav-item flex-fill" role="presentation">
                <button class="nav-link fw-bold py-2 px-1 text-nowrap" id="tab-overview-btn" data-bs-toggle="pill" data-bs-target="#tab-overview" type="button" role="tab" aria-selected="false">
                  <i class="bx bx-shield-quarter me-1"></i> 4. ทะเบียนคุมภาพรวม
                </button>
              </li>
            </ul>
          </div>
          
          <div class="card-body p-3 bg-white">
            <div class="tab-content" id="signatoryTabContent">
              
              <!-- ========================================================
                   แท็บ 1: การตั้งลูกหนี้ (งานประกันสุขภาพ - ตั้งลูกหนี้.php)
                   ======================================================== -->
              <div class="tab-pane fade show active" id="tab-setup" role="tabpanel">
                <div class="alert alert-info py-2 px-3 mb-3 small d-flex align-items-center rounded-3">
                  <i class="bx bx-info-circle fs-5 me-2"></i>
                  <div><b>รายงานสรุปการตั้งลูกหนี้ (OPD / IPD):</b> ข้อมูลออกจาก<b>กลุ่มงานประกันสุขภาพ</b> มีผู้ลงนาม 3 ท่าน สามารถเลือกรายชื่อ ปรับตำแหน่ง และระบุบทบาทหน้าที่ได้</div>
                </div>

                <div class="row g-3">
                  <!-- ลำดับที่ 1 (ซ้าย) -->
                  <div class="col-md-4">
                    <div class="border rounded-3 p-3 bg-light h-100 shadow-none">
                      <span class="badge bg-primary px-2 py-1 mb-2">1. ผู้ลงนามคนที่ 1 (ซ้าย)</span>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">เลือกจากบัญชีรายชื่อ:</label>
                        <select class="form-select form-select-sm select-officer-picker" data-target-name="#setup_name1" data-target-pos="#setup_pos1">
                          <option value="">-- เลือกเพื่อใส่ชื่อและตำแหน่ง --</option>
                        </select>
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">ชื่อ - นามสกุล:</label>
                        <input type="text" class="form-control form-control-sm sig-name" id="setup_name1" value="<?= htmlspecialchars($setup_name1) ?>">
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">ตำแหน่ง:</label>
                        <input type="text" class="form-control form-control-sm sig-pos" id="setup_pos1" value="<?= htmlspecialchars($setup_pos1) ?>">
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">บทบาท / ข้อความกำกับใต้ชื่อ:</label>
                        <textarea class="form-control form-control-sm sig-role" id="setup_role1" rows="2"><?= htmlspecialchars($setup_role1) ?></textarea>
                      </div>
                      <div class="p-2 border rounded bg-white text-center mt-3">
                        <div class="text-muted" style="font-size: 10.5px;">ตัวอย่างในรายงาน PDF:</div>
                        <div class="fw-bold text-dark mt-1" id="pv_setup_name1">-</div>
                        <div class="text-muted small" id="pv_setup_pos1">-</div>
                        <div class="text-primary fw-bold small mt-1" id="pv_setup_role1">-</div>
                      </div>
                    </div>
                  </div>

                  <!-- ลำดับที่ 2 (กลาง) -->
                  <div class="col-md-4">
                    <div class="border rounded-3 p-3 bg-light h-100 shadow-none">
                      <span class="badge bg-info px-2 py-1 mb-2">2. ผู้ลงนามคนที่ 2 (กลาง)</span>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">เลือกจากบัญชีรายชื่อ:</label>
                        <select class="form-select form-select-sm select-officer-picker" data-target-name="#setup_name2" data-target-pos="#setup_pos2">
                          <option value="">-- เลือกเพื่อใส่ชื่อและตำแหน่ง --</option>
                        </select>
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">ชื่อ - นามสกุล:</label>
                        <input type="text" class="form-control form-control-sm sig-name" id="setup_name2" value="<?= htmlspecialchars($setup_name2) ?>">
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">ตำแหน่ง:</label>
                        <input type="text" class="form-control form-control-sm sig-pos" id="setup_pos2" value="<?= htmlspecialchars($setup_pos2) ?>">
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">บทบาท / ข้อความกำกับใต้ชื่อ:</label>
                        <textarea class="form-control form-control-sm sig-role" id="setup_role2" rows="2"><?= htmlspecialchars($setup_role2) ?></textarea>
                      </div>
                      <div class="p-2 border rounded bg-white text-center mt-3">
                        <div class="text-muted" style="font-size: 10.5px;">ตัวอย่างในรายงาน PDF:</div>
                        <div class="fw-bold text-dark mt-1" id="pv_setup_name2">-</div>
                        <div class="text-muted small" id="pv_setup_pos2">-</div>
                        <div class="text-info fw-bold small mt-1" id="pv_setup_role2">-</div>
                      </div>
                    </div>
                  </div>

                  <!-- ลำดับที่ 3 (ขวา) -->
                  <div class="col-md-4">
                    <div class="border rounded-3 p-3 bg-light h-100 shadow-none">
                      <span class="badge bg-success px-2 py-1 mb-2">3. ผู้ลงนามคนที่ 3 (ขวา)</span>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">เลือกจากบัญชีรายชื่อ:</label>
                        <select class="form-select form-select-sm select-officer-picker" data-target-name="#setup_name3" data-target-pos="#setup_pos3">
                          <option value="">-- เลือกเพื่อใส่ชื่อและตำแหน่ง --</option>
                        </select>
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">ชื่อ - นามสกุล:</label>
                        <input type="text" class="form-control form-control-sm sig-name" id="setup_name3" value="<?= htmlspecialchars($setup_name3) ?>">
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">ตำแหน่ง:</label>
                        <input type="text" class="form-control form-control-sm sig-pos" id="setup_pos3" value="<?= htmlspecialchars($setup_pos3) ?>">
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">บทบาท / ข้อความกำกับใต้ชื่อ:</label>
                        <textarea class="form-control form-control-sm sig-role" id="setup_role3" rows="2"><?= htmlspecialchars($setup_role3) ?></textarea>
                      </div>
                      <div class="p-2 border rounded bg-white text-center mt-3">
                        <div class="text-muted" style="font-size: 10.5px;">ตัวอย่างในรายงาน PDF:</div>
                        <div class="fw-bold text-dark mt-1" id="pv_setup_name3">-</div>
                        <div class="text-muted small" id="pv_setup_pos3">-</div>
                        <div class="text-success fw-bold small mt-1" id="pv_setup_role3">-</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ========================================================
                   แท็บ 2: การตัดลูกหนี้ (Debtor-rights.php)
                   ======================================================== -->
              <div class="tab-pane fade" id="tab-cut" role="tabpanel">
                <div class="alert alert-info py-2 px-3 mb-3 small d-flex align-items-center rounded-3">
                  <i class="bx bx-info-circle fs-5 me-2"></i>
                  <div><b>รายงานสรุปการตัดลูกหนี้ (Debtor-rights.php / Debtor-rightsckd.php):</b> มีผู้ลงนาม 3 ท่าน สามารถเลือกคน ตำแหน่ง และระบุหน้าที่ได้</div>
                </div>

                <div class="row g-3">
                  <div class="col-md-4">
                    <div class="border rounded-3 p-3 bg-light h-100 shadow-none">
                      <span class="badge bg-primary px-2 py-1 mb-2">1. ผู้ลงนามคนที่ 1 (ซ้าย)</span>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">เลือกจากบัญชีรายชื่อ:</label>
                        <select class="form-select form-select-sm select-officer-picker" data-target-name="#cut_name1" data-target-pos="#cut_pos1">
                          <option value="">-- เลือกเพื่อใส่ชื่อและตำแหน่ง --</option>
                        </select>
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">ชื่อ - นามสกุล:</label>
                        <input type="text" class="form-control form-control-sm sig-name" id="cut_name1" value="<?= htmlspecialchars($cut_name1) ?>">
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">ตำแหน่ง:</label>
                        <input type="text" class="form-control form-control-sm sig-pos" id="cut_pos1" value="<?= htmlspecialchars($cut_pos1) ?>">
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">บทบาท / ข้อความกำกับใต้ชื่อ:</label>
                        <textarea class="form-control form-control-sm sig-role" id="cut_role1" rows="2"><?= htmlspecialchars($cut_role1) ?></textarea>
                      </div>
                      <div class="p-2 border rounded bg-white text-center mt-3">
                        <div class="text-muted" style="font-size: 10.5px;">ตัวอย่างในรายงาน PDF:</div>
                        <div class="fw-bold text-dark mt-1" id="pv_cut_name1">-</div>
                        <div class="text-muted small" id="pv_cut_pos1">-</div>
                        <div class="text-primary fw-bold small mt-1" id="pv_cut_role1">-</div>
                      </div>
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="border rounded-3 p-3 bg-light h-100 shadow-none">
                      <span class="badge bg-info px-2 py-1 mb-2">2. ผู้ลงนามคนที่ 2 (กลาง)</span>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">เลือกจากบัญชีรายชื่อ:</label>
                        <select class="form-select form-select-sm select-officer-picker" data-target-name="#cut_name2" data-target-pos="#cut_pos2">
                          <option value="">-- เลือกเพื่อใส่ชื่อและตำแหน่ง --</option>
                        </select>
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">ชื่อ - นามสกุล:</label>
                        <input type="text" class="form-control form-control-sm sig-name" id="cut_name2" value="<?= htmlspecialchars($cut_name2) ?>">
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">ตำแหน่ง:</label>
                        <input type="text" class="form-control form-control-sm sig-pos" id="cut_pos2" value="<?= htmlspecialchars($cut_pos2) ?>">
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">บทบาท / ข้อความกำกับใต้ชื่อ:</label>
                        <textarea class="form-control form-control-sm sig-role" id="cut_role2" rows="2"><?= htmlspecialchars($cut_role2) ?></textarea>
                      </div>
                      <div class="p-2 border rounded bg-white text-center mt-3">
                        <div class="text-muted" style="font-size: 10.5px;">ตัวอย่างในรายงาน PDF:</div>
                        <div class="fw-bold text-dark mt-1" id="pv_cut_name2">-</div>
                        <div class="text-muted small" id="pv_cut_pos2">-</div>
                        <div class="text-info fw-bold small mt-1" id="pv_cut_role2">-</div>
                      </div>
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="border rounded-3 p-3 bg-light h-100 shadow-none">
                      <span class="badge bg-warning px-2 py-1 mb-2">3. ผู้ลงนามคนที่ 3 (ขวา)</span>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">เลือกจากบัญชีรายชื่อ:</label>
                        <select class="form-select form-select-sm select-officer-picker" data-target-name="#cut_name3" data-target-pos="#cut_pos3">
                          <option value="">-- เลือกเพื่อใส่ชื่อและตำแหน่ง --</option>
                        </select>
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">ชื่อ - นามสกุล:</label>
                        <input type="text" class="form-control form-control-sm sig-name" id="cut_name3" value="<?= htmlspecialchars($cut_name3) ?>">
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">ตำแหน่ง:</label>
                        <input type="text" class="form-control form-control-sm sig-pos" id="cut_pos3" value="<?= htmlspecialchars($cut_pos3) ?>">
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">บทบาท / ข้อความกำกับใต้ชื่อ:</label>
                        <textarea class="form-control form-control-sm sig-role" id="cut_role3" rows="2"><?= htmlspecialchars($cut_role3) ?></textarea>
                      </div>
                      <div class="p-2 border rounded bg-white text-center mt-3">
                        <div class="text-muted" style="font-size: 10.5px;">ตัวอย่างในรายงาน PDF:</div>
                        <div class="fw-bold text-dark mt-1" id="pv_cut_name3">-</div>
                        <div class="text-muted small" id="pv_cut_pos3">-</div>
                        <div class="text-warning fw-bold small mt-1" id="pv_cut_role3">-</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ========================================================
                   แท็บ 3: การตัดลูกหนี้รายตัว (ลูกหนี้รายตัว2.php)
                   ======================================================== -->
              <div class="tab-pane fade" id="tab-item" role="tabpanel">
                <div class="alert alert-info py-2 px-3 mb-3 small d-flex align-items-center rounded-3">
                  <i class="bx bx-info-circle fs-5 me-2"></i>
                  <div><b>รายงานการตัดลูกหนี้รายตัว (ลูกหนี้รายตัว2.php):</b> มีผู้ลงนาม 3 ท่าน สามารถเลือกคนได้ตามต้องการ</div>
                </div>

                <div class="row g-3">
                  <div class="col-md-4">
                    <div class="border rounded-3 p-3 bg-light h-100 shadow-none">
                      <span class="badge bg-primary px-2 py-1 mb-2">1. ผู้ลงนามคนที่ 1 (ซ้าย)</span>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">เลือกจากบัญชีรายชื่อ:</label>
                        <select class="form-select form-select-sm select-officer-picker" data-target-name="#item_name1" data-target-pos="#item_pos1">
                          <option value="">-- เลือกเพื่อใส่ชื่อและตำแหน่ง --</option>
                        </select>
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">ชื่อ - นามสกุล:</label>
                        <input type="text" class="form-control form-control-sm sig-name" id="item_name1" value="<?= htmlspecialchars($item_name1) ?>">
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">ตำแหน่ง:</label>
                        <input type="text" class="form-control form-control-sm sig-pos" id="item_pos1" value="<?= htmlspecialchars($item_pos1) ?>">
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">บทบาท / ข้อความกำกับใต้ชื่อ:</label>
                        <textarea class="form-control form-control-sm sig-role" id="item_role1" rows="2"><?= htmlspecialchars($item_role1) ?></textarea>
                      </div>
                      <div class="p-2 border rounded bg-white text-center mt-3">
                        <div class="text-muted" style="font-size: 10.5px;">ตัวอย่างในรายงาน:</div>
                        <div class="fw-bold text-dark mt-1" id="pv_item_name1">-</div>
                        <div class="text-muted small" id="pv_item_pos1">-</div>
                        <div class="text-primary fw-bold small mt-1" id="pv_item_role1">-</div>
                      </div>
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="border rounded-3 p-3 bg-light h-100 shadow-none">
                      <span class="badge bg-info px-2 py-1 mb-2">2. ผู้ลงนามคนที่ 2 (กลาง)</span>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">เลือกจากบัญชีรายชื่อ:</label>
                        <select class="form-select form-select-sm select-officer-picker" data-target-name="#item_name2" data-target-pos="#item_pos2">
                          <option value="">-- เลือกเพื่อใส่ชื่อและตำแหน่ง --</option>
                        </select>
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">ชื่อ - นามสกุล:</label>
                        <input type="text" class="form-control form-control-sm sig-name" id="item_name2" value="<?= htmlspecialchars($item_name2) ?>">
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">ตำแหน่ง:</label>
                        <input type="text" class="form-control form-control-sm sig-pos" id="item_pos2" value="<?= htmlspecialchars($item_pos2) ?>">
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">บทบาท / ข้อความกำกับใต้ชื่อ:</label>
                        <textarea class="form-control form-control-sm sig-role" id="item_role2" rows="2"><?= htmlspecialchars($item_role2) ?></textarea>
                      </div>
                      <div class="p-2 border rounded bg-white text-center mt-3">
                        <div class="text-muted" style="font-size: 10.5px;">ตัวอย่างในรายงาน:</div>
                        <div class="fw-bold text-dark mt-1" id="pv_item_name2">-</div>
                        <div class="text-muted small" id="pv_item_pos2">-</div>
                        <div class="text-info fw-bold small mt-1" id="pv_item_role2">-</div>
                      </div>
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="border rounded-3 p-3 bg-light h-100 shadow-none">
                      <span class="badge bg-success px-2 py-1 mb-2">3. ผู้ลงนามคนที่ 3 (ขวา)</span>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">เลือกจากบัญชีรายชื่อ:</label>
                        <select class="form-select form-select-sm select-officer-picker" data-target-name="#item_name3" data-target-pos="#item_pos3">
                          <option value="">-- เลือกเพื่อใส่ชื่อและตำแหน่ง --</option>
                        </select>
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">ชื่อ - นามสกุล:</label>
                        <input type="text" class="form-control form-control-sm sig-name" id="item_name3" value="<?= htmlspecialchars($item_name3) ?>">
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">ตำแหน่ง:</label>
                        <input type="text" class="form-control form-control-sm sig-pos" id="item_pos3" value="<?= htmlspecialchars($item_pos3) ?>">
                      </div>
                      <div class="mb-2">
                        <label class="form-label small fw-bold mb-1">บทบาท / ข้อความกำกับใต้ชื่อ:</label>
                        <textarea class="form-control form-control-sm sig-role" id="item_role3" rows="2"><?= htmlspecialchars($item_role3) ?></textarea>
                      </div>
                      <div class="p-2 border rounded bg-white text-center mt-3">
                        <div class="text-muted" style="font-size: 10.5px;">ตัวอย่างในรายงาน:</div>
                        <div class="fw-bold text-dark mt-1" id="pv_item_name3">-</div>
                        <div class="text-muted small" id="pv_item_pos3">-</div>
                        <div class="text-success fw-bold small mt-1" id="pv_item_role3">-</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ========================================================
                   แท็บ 4: ทะเบียนคุมภาพรวม (5 ท่าน)
                   ======================================================== -->
              <div class="tab-pane fade" id="tab-overview" role="tabpanel">
                <div class="alert alert-info py-2 px-3 mb-3 small d-flex align-items-center rounded-3">
                  <i class="bx bx-info-circle fs-5 me-2"></i>
                  <div><b>ทะเบียนคุมภาพรวมทั้งโรงพยาบาล (5 ท่าน):</b> กำหนด 5 ตำแหน่งมาตรฐานสำหรับทะเบียนคุมลูกหนี้สิทธิภาพรวม</div>
                </div>

                <div class="row g-2">
                  <div class="col-md-4">
                    <div class="border rounded p-2 bg-light">
                      <span class="badge bg-primary mb-1">1. ผู้จัดทำรายงาน (ประกันฯ)</span>
                      <select class="form-select form-select-sm select-officer-picker mb-1" data-target-name="#s1_input" data-target-pos="#p1_input">
                        <option value="">-- เลือกรายชื่อ --</option>
                      </select>
                      <input type="text" class="form-control form-control-sm sig-name mb-1" id="s1_input" placeholder="ชื่อ-นามสกุล" value="<?= htmlspecialchars($s1) ?>">
                      <input type="text" class="form-control form-control-sm sig-pos" id="p1_input" placeholder="ตำแหน่ง" value="<?= htmlspecialchars($p1) ?>">
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="border rounded p-2 bg-light">
                      <span class="badge bg-info mb-1">2. ตรวจสอบรายงาน (ประกันฯ)</span>
                      <select class="form-select form-select-sm select-officer-picker mb-1" data-target-name="#s2_input" data-target-pos="#p2_input">
                        <option value="">-- เลือกรายชื่อ --</option>
                      </select>
                      <input type="text" class="form-control form-control-sm sig-name mb-1" id="s2_input" placeholder="ชื่อ-นามสกุล" value="<?= htmlspecialchars($s2) ?>">
                      <input type="text" class="form-control form-control-sm sig-pos" id="p2_input" placeholder="ตำแหน่ง" value="<?= htmlspecialchars($p2) ?>">
                    </div>
                  </div>

                  <div class="col-md-4">
                    <div class="border rounded p-2 bg-light">
                      <span class="badge bg-success mb-1">3. บันทึกลูกหนี้สิทธิ (บัญชี)</span>
                      <select class="form-select form-select-sm select-officer-picker mb-1" data-target-name="#s3_input" data-target-pos="#p3_input">
                        <option value="">-- เลือกรายชื่อ --</option>
                      </select>
                      <input type="text" class="form-control form-control-sm sig-name mb-1" id="s3_input" placeholder="ชื่อ-นามสกุล" value="<?= htmlspecialchars($s3) ?>">
                      <input type="text" class="form-control form-control-sm sig-pos" id="p3_input" placeholder="ตำแหน่ง" value="<?= htmlspecialchars($p3) ?>">
                    </div>
                  </div>

                  <div class="col-md-6 mt-2">
                    <div class="border rounded p-2 bg-light">
                      <span class="badge bg-warning mb-1">4. ตรวจสอบบันทึกบัญชี</span>
                      <select class="form-select form-select-sm select-officer-picker mb-1" data-target-name="#s4_input" data-target-pos="#p4_input">
                        <option value="">-- เลือกรายชื่อ --</option>
                      </select>
                      <input type="text" class="form-control form-control-sm sig-name mb-1" id="s4_input" placeholder="ชื่อ-นามสกุล" value="<?= htmlspecialchars($s4) ?>">
                      <input type="text" class="form-control form-control-sm sig-pos" id="p4_input" placeholder="ตำแหน่ง" value="<?= htmlspecialchars($p4) ?>">
                    </div>
                  </div>

                  <div class="col-md-6 mt-2">
                    <div class="border rounded p-2 bg-light">
                      <span class="badge bg-danger mb-1">5. หัวหน้าหน่วยงาน (ผอ.)</span>
                      <select class="form-select form-select-sm select-officer-picker mb-1" data-target-name="#s5_input" data-target-pos="#p5_input">
                        <option value="">-- เลือกรายชื่อ --</option>
                      </select>
                      <input type="text" class="form-control form-control-sm sig-name mb-1" id="s5_input" placeholder="ชื่อ-นามสกุล" value="<?= htmlspecialchars($s5) ?>">
                      <input type="text" class="form-control form-control-sm sig-pos" id="p5_input" placeholder="ตำแหน่ง" value="<?= htmlspecialchars($p5) ?>">
                    </div>
                  </div>
                </div>

              </div>

            </div>
          </div>
        </div>

      </div>

      <div class="modal-footer bg-light px-4 py-3">
        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
          <i class="bx bx-x me-1"></i> ยกเลิก
        </button>
        <button type="button" id="saveSummaryBtn" class="btn btn-primary rounded-pill px-4 shadow">
          <i class="bx bx-save me-1"></i> บันทึกการตั้งค่า
        </button>
      </div>

    </div>
  </div>
</div>

<script>
(function() {
    function initSignersModal() {
        if (typeof jQuery === 'undefined' || typeof $ === 'undefined') {
            setTimeout(initSignersModal, 50);
            return;
        }

        // ฟังก์ชันรวบรวมรายชื่อบุคลากรจากตาราง
        function getRosterOfficers() {
            var list = [];
            $('#officerTableBody tr').each(function() {
                var name = $(this).find('.officer-name').val().trim();
                var pos  = $(this).find('.officer-pos').val().trim();
                if (name !== '' || pos !== '') {
                    list.push({ name: name, pos: pos });
                }
            });
            return list;
        }

        // ฟังก์ชันอัปเดตตัวเลือกใน Dropdown บุคลากรทุกแท็บ
        function refreshOfficerDropdowns() {
            var roster = getRosterOfficers();
            $('#officerCountBadge').text(roster.length + ' ท่าน');

            $('.select-officer-picker').each(function() {
                var select = $(this);
                select.empty();
                select.append('<option value="">-- เลือกเพื่อใส่ชื่อและตำแหน่ง --</option>');
                roster.forEach(function(item, idx) {
                    var opt = $('<option></option>')
                        .attr('value', idx)
                        .attr('data-name', item.name)
                        .attr('data-pos', item.pos)
                        .text(item.name + (item.pos ? ' (' + item.pos + ')' : ''));
                    select.append(opt);
                });
            });
        }

        // เมื่อเลือก Dropdown บุคลากรในแท็บ
        $(document).off('change.officerPicker').on('change.officerPicker', '.select-officer-picker', function() {
            var selectedOpt = $(this).find('option:selected');
            var name = selectedOpt.data('name') || '';
            var pos  = selectedOpt.data('pos') || '';
            var targetName = $(this).data('target-name');
            var targetPos  = $(this).data('target-pos');

            if (name && targetName) {
                $(targetName).val(name).trigger('input');
            }
            if (pos && targetPos) {
                $(targetPos).val(pos).trigger('input');
            }
        });

        // ฟังก์ชันอัปเดต Live Preview ในแต่ละแท็บ
        function updateLivePreviews() {
            // Tab 1: Setup
            for (var i = 1; i <= 3; i++) {
                var n = $('#setup_name' + i).val().trim() || '(ชื่อ-สกุล)';
                var p = $('#setup_pos' + i).val().trim() || '';
                var r = $('#setup_role' + i).val().trim() || '';
                $('#pv_setup_name' + i).text('(' + n + ')');
                $('#pv_setup_pos' + i).text(p ? 'ตำแหน่ง ' + p : '');
                $('#pv_setup_role' + i).html(r.replace(/\n/g, '<br>'));
            }

            // Tab 2: Cut
            for (var i = 1; i <= 3; i++) {
                var n = $('#cut_name' + i).val().trim() || '(ชื่อ-สกุล)';
                var p = $('#cut_pos' + i).val().trim() || '';
                var r = $('#cut_role' + i).val().trim() || '';
                $('#pv_cut_name' + i).text('(' + n + ')');
                $('#pv_cut_pos' + i).text(p ? 'ตำแหน่ง ' + p : '');
                $('#pv_cut_role' + i).html(r.replace(/\n/g, '<br>'));
            }

            // Tab 3: Item
            for (var i = 1; i <= 3; i++) {
                var n = $('#item_name' + i).val().trim() || '(ชื่อ-สกุล)';
                var p = $('#item_pos' + i).val().trim() || '';
                var r = $('#item_role' + i).val().trim() || '';
                $('#pv_item_name' + i).text('(' + n + ')');
                $('#pv_item_pos' + i).text(p ? 'ตำแหน่ง ' + p : '');
                $('#pv_item_role' + i).html(r.replace(/\n/g, '<br>'));
            }

            // Tab 4: Overview
            for (var i = 1; i <= 5; i++) {
                var n = $('#s' + i + '_input').val().trim();
                var p = $('#p' + i + '_input').val().trim();
                $('#s' + i + 'txt').val(n);
                $('#p' + i + 'txt').val(p);
            }

            if (typeof settingtxt === 'function') {
                settingtxt();
            }
        }

        // Event Input Listener
        $(document).off('input.sigInputs').on('input.sigInputs', '.sig-name, .sig-pos, .sig-role, .officer-name, .officer-pos', function() {
            updateLivePreviews();
            if ($(this).hasClass('officer-name') || $(this).hasClass('officer-pos')) {
                refreshOfficerDropdowns();
            }
        });

        // ปุ่มเพิ่มรายชื่อบุคลากร
        $('#btnAddOfficerRow').off('click').on('click', function() {
            var rowCount = $('#officerTableBody tr').length + 1;
            var newRow = `
              <tr class="officer-row">
                <td class="text-center row-num fw-bold text-muted">${rowCount}</td>
                <td>
                  <input type="text" class="form-control form-control-sm officer-name" placeholder="ระบุชื่อ-นามสกุล...">
                </td>
                <td>
                  <input type="text" class="form-control form-control-sm officer-pos" placeholder="ระบุตำแหน่ง...">
                </td>
                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-icon btn-outline-danger btn-remove-officer" title="ลบแถว">
                    <i class="bx bx-trash"></i>
                  </button>
                </td>
              </tr>
            `;
            $('#officerTableBody').append(newRow);
            refreshOfficerDropdowns();
        });

        // ปุ่มลบรายชื่อบุคลากร
        $(document).off('click.removeOfficer').on('click.removeOfficer', '.btn-remove-officer', function() {
            if ($('#officerTableBody tr').length <= 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'ไม่สามารถลบได้',
                    text: 'ต้องมีรายชื่อบุคลากรอย่างน้อย 1 รายการ'
                });
                return;
            }
            $(this).closest('tr').remove();
            $('#officerTableBody tr').each(function(idx) {
                $(this).find('.row-num').text(idx + 1);
            });
            refreshOfficerDropdowns();
            updateLivePreviews();
        });

        // เริ่มต้นรัน Dropdown และ Preview
        refreshOfficerDropdowns();
        updateLivePreviews();

        // เมื่อเปิด Modal ให้รีเฟรชข้อมูล dropdown และ preview อีกครั้งเพื่อความสมบูรณ์
        $('#setting_summary-report').off('shown.bs.modal').on('shown.bs.modal', function() {
            refreshOfficerDropdowns();
            updateLivePreviews();
        });

        // บันทึกการตั้งค่า
        $('#saveSummaryBtn').off('click').on('click', function() {
            var roster = getRosterOfficers();
            if (roster.length === 0) {
                Swal.fire('ข้อมูลไม่ครบถ้วน', 'กรุณาระบุรายชื่อบุคลากรอย่างน้อย 1 ท่าน', 'warning');
                return;
            }

            var payload = {
                officers_json: JSON.stringify(roster),

                // Tab 1: ตั้งลูกหนี้
                setup_name1: $('#setup_name1').val().trim(),
                setup_pos1:  $('#setup_pos1').val().trim(),
                setup_role1: $('#setup_role1').val().trim(),
                setup_name2: $('#setup_name2').val().trim(),
                setup_pos2:  $('#setup_pos2').val().trim(),
                setup_role2: $('#setup_role2').val().trim(),
                setup_name3: $('#setup_name3').val().trim(),
                setup_pos3:  $('#setup_pos3').val().trim(),
                setup_role3: $('#setup_role3').val().trim(),

                // Tab 2: ตัดลูกหนี้
                cut_name1: $('#cut_name1').val().trim(),
                cut_pos1:  $('#cut_pos1').val().trim(),
                cut_role1: $('#cut_role1').val().trim(),
                cut_name2: $('#cut_name2').val().trim(),
                cut_pos2:  $('#cut_pos2').val().trim(),
                cut_role2: $('#cut_role2').val().trim(),
                cut_name3: $('#cut_name3').val().trim(),
                cut_pos3:  $('#cut_pos3').val().trim(),
                cut_role3: $('#cut_role3').val().trim(),

                // Tab 3: ตัดลูกหนี้รายตัว
                item_name1: $('#item_name1').val().trim(),
                item_pos1:  $('#item_pos1').val().trim(),
                item_role1: $('#item_role1').val().trim(),
                item_name2: $('#item_name2').val().trim(),
                item_pos2:  $('#item_pos2').val().trim(),
                item_role2: $('#item_role2').val().trim(),
                item_name3: $('#item_name3').val().trim(),
                item_pos3:  $('#item_pos3').val().trim(),
                item_role3: $('#item_role3').val().trim(),

                // Tab 4: ทะเบียนคุม 5 ท่าน
                s1: $('#s1_input').val().trim(),
                p1: $('#p1_input').val().trim(),
                s2: $('#s2_input').val().trim(),
                p2: $('#p2_input').val().trim(),
                s3: $('#s3_input').val().trim(),
                p3: $('#p3_input').val().trim(),
                s4: $('#s4_input').val().trim(),
                p4: $('#p4_input').val().trim(),
                s5: $('#s5_input').val().trim(),
                p5: $('#p5_input').val().trim(),
            };

            // Fallback s1..s5 ถ้าว่าง
            if (!payload.s1 && payload.setup_name1) payload.s1 = payload.setup_name1;
            if (!payload.p1 && payload.setup_pos1)  payload.p1 = payload.setup_pos1;
            if (!payload.s2 && payload.setup_name2) payload.s2 = payload.setup_name2;
            if (!payload.p2 && payload.setup_pos2)  payload.p2 = payload.setup_pos2;
            if (!payload.s3 && payload.cut_name3)   payload.s3 = payload.cut_name3;
            if (!payload.p3 && payload.cut_pos3)    payload.p3 = payload.cut_pos3;

            $.ajax({
                url: 'save_summary.php',
                method: 'POST',
                data: payload,
                success: function(response) {
                    if (response && response.indexOf('บันทึกข้อมูลสำเร็จ') !== -1) {
                        if (typeof settingtxt === 'function') {
                            settingtxt();
                        }
                        Swal.fire({
                            icon: 'success',
                            title: 'บันทึกสำเร็จ!',
                            text: 'บันทึกรายชื่อและหน้าที่ผู้ลงนามรายงานเรียบร้อยแล้ว',
                            showConfirmButton: false,
                            timer: 1500
                        });
                        setTimeout(function() {
                            $('#setting_summary-report').modal('hide');
                        }, 1500);
                    } else {
                        Swal.fire('ไม่สามารถบันทึกได้', response || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล', 'warning');
                    }
                },
                error: function() {
                    Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่อีกครั้ง', 'error');
                }
            });
        });
    }

    initSignersModal();
})();
</script>
