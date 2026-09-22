<?php
/**
 * eDHS Version & Changelog Helper Module
 * ระบบจัดการเวอร์ชั่นและประวัติการอัปเดตระบบ
 */

if (!function_exists('get_system_version_data')) {
    function get_system_version_data() {
        static $versionData = null;
        if ($versionData !== null) {
            return $versionData;
        }

        $filePath = __DIR__ . '/../database_config/changelog.json';
        if (!file_exists($filePath)) {
            $versionData = [
                'current_version' => '03.09.69',
                'last_updated' => date('Y-m-d'),
                'releases' => []
            ];
            return $versionData;
        }

        $content = @file_get_contents($filePath);
        $json = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $versionData = $json;
        } else {
            $versionData = [
                'current_version' => '03.09.69',
                'last_updated' => date('Y-m-d'),
                'releases' => []
            ];
        }

        return $versionData;
    }
}

if (!function_exists('get_system_version')) {
    function get_system_version() {
        $data = get_system_version_data();
        return isset($data['current_version']) && !empty($data['current_version']) 
            ? $data['current_version'] 
            : '03.09.69';
    }
}

if (!function_exists('get_system_version_date')) {
    function get_system_version_date() {
        $data = get_system_version_data();
        return isset($data['last_updated']) && !empty($data['last_updated']) 
            ? $data['last_updated'] 
            : date('Y-m-d');
    }
}

if (!function_exists('get_system_changelog')) {
    function get_system_changelog($includeUpcoming = true) {
        $data = get_system_version_data();
        $releases = isset($data['releases']) && is_array($data['releases']) 
            ? $data['releases'] 
            : [];

        if (!$includeUpcoming) {
            return $releases;
        }

        // ตรวจสอบว่ามีเวอร์ชันใหม่ที่ยังไม่ได้ติดตั้งหรือไม่
        $updateInfo = check_system_update_available();
        if (!empty($updateInfo['available']) && !empty($updateInfo['latest_version'])) {
            $latestVer = $updateInfo['latest_version'];
            
            // เช็คว่าใน releases มีเวอร์ชันนี้แล้วหรือยัง
            $alreadyExists = false;
            foreach ($releases as $r) {
                if (isset($r['version']) && $r['version'] === $latestVer) {
                    $alreadyExists = true;
                    break;
                }
            }

            if (!$alreadyExists) {
                $baseDir = dirname(__DIR__, 2);
                $upcomingRelease = null;

                // 1. ดึงจาก eDHS Update/{$latestVer}/system/database_config/changelog.json
                $patchChangelogPath = $baseDir . '/eDHS Update/' . $latestVer . '/system/database_config/changelog.json';
                if (file_exists($patchChangelogPath)) {
                    $patchData = json_decode(@file_get_contents($patchChangelogPath), true);
                    if (!empty($patchData['releases']) && is_array($patchData['releases'])) {
                        foreach ($patchData['releases'] as $pr) {
                            if (isset($pr['version']) && $pr['version'] === $latestVer) {
                                $upcomingRelease = $pr;
                                break;
                            }
                        }
                    }
                }

                // 2. ถ้ายังไม่พบ ให้ดึงจาก version.json หรือ meta
                if (!$upcomingRelease) {
                    $meta = $updateInfo['meta'] ?? [];
                    $changes = [];
                    if (!empty($meta['changes']) && is_array($meta['changes'])) {
                        $changes = $meta['changes'];
                    } elseif (!empty($meta['changelog_summary']) && is_array($meta['changelog_summary'])) {
                        foreach ($meta['changelog_summary'] as $sumText) {
                            $changes[] = [
                                'category' => 'improve',
                                'tag' => 'รายการปรับปรุง',
                                'color' => 'success',
                                'icon' => 'bx-check-circle',
                                'description' => $sumText
                            ];
                        }
                    }

                    $upcomingRelease = [
                        'version' => $latestVer,
                        'date' => $meta['release_date'] ?? date('Y-m-d'),
                        'title' => $meta['title'] ?? "การปรับปรุงระบบ (v$latestVer)",
                        'type' => 'feature',
                        'badge' => 'bg-warning',
                        'changes' => $changes
                    ];
                }

                if ($upcomingRelease) {
                    $upcomingRelease['is_upcoming'] = true;
                    array_unshift($releases, $upcomingRelease);
                }
            }
        }

        return $releases;
    }
}

if (!function_exists('format_changelog_date')) {
    function format_changelog_date($dateStr) {
        if (empty($dateStr)) return '';
        if (function_exists('DateThai')) {
            return DateThai($dateStr);
        }
        $timestamp = strtotime($dateStr);
        if (!$timestamp) return $dateStr;
        $thaiYear = date('Y', $timestamp) + 543;
        $thaiMonths = ["", "มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
        $month = (int)date('n', $timestamp);
        $day = (int)date('j', $timestamp);
        return "$day {$thaiMonths[$month]} $thaiYear";
    }
}

/**
 * กำหนดไอคอน สี และแท็กมาตรฐานสำหรับแต่ละหมวดหมู่ เพื่อให้หัวข้อเดียวกันแสดงผลเหมือนกันเสมอ
 */
if (!function_exists('get_changelog_category_meta')) {
    function get_changelog_category_meta($category = '', $tag = '') {
        $cat = strtolower(trim($category ?? ''));
        $tagText = trim($tag ?? '');

        // 1. ฟีเจอร์ใหม่ (Feature)
        if ($cat === 'feature' || mb_strpos($tagText, 'ฟีเจอร์') !== false || mb_strpos($tagText, 'ใหม่') !== false) {
            return [
                'category' => 'feature',
                'tag'      => !empty($tagText) ? $tagText : 'ฟีเจอร์ใหม่',
                'color'    => 'success',
                'icon'     => 'bx-gift' // ใช้ไอคอนกล่องของขวัญสำหรับฟีเจอร์ใหม่เสมอ
            ];
        }

        // 2. ปรับปรุงระบบ / ประสิทธิภาพ (Improvement)
        if ($cat === 'improve' || $cat === 'improvement' || mb_strpos($tagText, 'ปรับปรุง') !== false || mb_strpos($tagText, 'พัฒนา') !== false) {
            return [
                'category' => 'improve',
                'tag'      => !empty($tagText) ? $tagText : 'ปรับปรุง',
                'color'    => 'info',
                'icon'     => 'bx-wrench' // ใช้ไอคอนประแจสำหรับงานปรับปรุงเสมอ
            ];
        }

        // 3. แก้ไขข้อผิดพลาด / บั๊ก (Bug Fix)
        if ($cat === 'fix' || $cat === 'bug' || mb_strpos($tagText, 'แก้ไข') !== false || mb_strpos($tagText, 'บั๊ก') !== false) {
            return [
                'category' => 'fix',
                'tag'      => !empty($tagText) ? $tagText : 'แก้ไขข้อผิดพลาด',
                'color'    => 'danger',
                'icon'     => 'bx-bug' // ใช้ไอคอนบั๊กสำหรับงานแก้ไขข้อผิดพลาดเสมอ
            ];
        }

        // 4. ความปลอดภัย (Security)
        if ($cat === 'security' || mb_strpos($tagText, 'ความปลอดภัย') !== false || mb_strpos($tagText, 'security') !== false) {
            return [
                'category' => 'security',
                'tag'      => !empty($tagText) ? $tagText : 'ความปลอดภัย',
                'color'    => 'warning',
                'icon'     => 'bx-shield-quarter' // ใช้ไอคอนโล่สำหรับงานความปลอดภัยเสมอ
            ];
        }

        // ค่าเริ่มต้น
        return [
            'category' => 'improve',
            'tag'      => !empty($tagText) ? $tagText : 'ปรับปรุง',
            'color'    => 'info',
            'icon'     => 'bx-wrench'
        ];
    }
}

/**
 * ตรวจสอบว่ามีเวอร์ชันใหม่อัปเดตหรือไม่
 */
if (!function_exists('check_system_update_available')) {
    function check_system_update_available() {
        $currentVersion = get_system_version();
        $baseDir = dirname(__DIR__, 2);
        
        $versionJsonPath = $baseDir . '/version.json';
        $latestMeta = null;
        if (file_exists($versionJsonPath)) {
            $latestMeta = json_decode(@file_get_contents($versionJsonPath), true);
        }
        
        // Fallback: ตรวจดูโฟลเดอร์ล่าสุดใน eDHS Update/
        if (!$latestMeta || empty($latestMeta['latest_version'])) {
            $updateDir = $baseDir . '/eDHS Update';
            if (is_dir($updateDir)) {
                $patches = array_diff(scandir($updateDir), ['.', '..']);
                natsort($patches);
                if (!empty($patches)) {
                    $latestFolder = end($patches);
                    $latestMeta = [
                        'latest_version' => $latestFolder,
                        'patch_folder'   => $latestFolder,
                        'title'          => "อัปเดตเวอร์ชัน $latestFolder",
                        'release_date'   => date('Y-m-d')
                    ];
                }
            }
        }
        
        if (!$latestMeta || empty($latestMeta['latest_version'])) {
            return ['available' => false];
        }
        
        $latestVersion = $latestMeta['latest_version'];
        $hasUpdate = ($latestVersion !== $currentVersion && $latestVersion !== '00.00.00');
        
        return [
            'available'       => $hasUpdate,
            'current_version' => $currentVersion,
            'latest_version'  => $latestVersion,
            'meta'            => $latestMeta
        ];
    }
}

/**
 * แถบแจ้งเตือนเมื่อมีเวอร์ชันใหม่อัปเดต (Render Notification Banner)
 */
if (!function_exists('render_update_notification_banner')) {
    function render_update_notification_banner() {
        $updateInfo = check_system_update_available();
        $isAvailable = $updateInfo['available'];

        $latestVer = htmlspecialchars($updateInfo['latest_version'] ?? '');
        $currentVer = htmlspecialchars($updateInfo['current_version'] ?? '');
        $meta = $updateInfo['meta'] ?? [];
        $title = htmlspecialchars($meta['title'] ?? "มีการอัปเดตเวอร์ชันใหม่ $latestVer");
        $relDate = !empty($meta['release_date']) ? format_changelog_date($meta['release_date']) : '';

        ob_start();
        ?>
        <div class="col-12 mb-4" id="edhsUpdateBannerContainer" style="<?= $isAvailable ? '' : 'display: none;' ?>">
          <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #696cff 0%, #4338ca 100%); border-radius: 16px; overflow: hidden; color: #fff;">
            <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
              <div class="d-flex align-items-center gap-3">
                <div class="p-3 rounded-circle bg-white text-primary d-flex align-items-center justify-content-center shadow-sm" style="width: 52px; height: 52px; flex-shrink: 0;">
                  <i class="bx bx-rocket font-size-28" style="font-size: 28px; animation: pulseIcon 2s infinite;"></i>
                </div>
                <div>
                  <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <span class="badge bg-warning text-dark fw-bold rounded-pill px-2 py-1" style="font-size: 11px;">
                      <i class="bx bxs-bell-ring me-1"></i>มีเวอร์ชั่นใหม่พร้อมอัปเดต
                    </span>
                    <span class="badge bg-white text-primary fw-bold rounded-pill px-2 py-1" id="bannerVersionBadge" style="font-size: 11px;">
                      v<?= $currentVer ?> &rarr; v<?= $latestVer ?>
                    </span>
                    <?php if ($relDate): ?>
                      <small class="text-white-50" id="bannerRelDate" style="font-size: 11.5px;"><i class="bx bx-calendar me-1"></i><?= $relDate ?></small>
                    <?php endif; ?>
                  </div>
                  <h5 class="text-white mb-0 fw-bold" id="bannerTitle" style="font-size: 16px;">
                    <?= $title ?>
                  </h5>
                </div>
              </div>
              <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                <button type="button" class="btn btn-outline-light rounded-pill px-3 py-2 fw-semibold d-inline-flex align-items-center" onclick="openChangelogModal()" style="font-size: 13px;">
                  <i class="bx bx-list-ul me-1"></i>ดูรายละเอียด
                </button>
                <button type="button" class="btn btn-warning text-dark rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center shadow" id="bannerBtnUpdate" onclick="triggerSystemUpdate('<?= $latestVer ?>')" style="font-size: 13.5px; transition: transform 0.2s;">
                  <i class="bx bx-refresh me-1 font-size-18"></i>อัปเดตระบบเดี๋ยวนี้ ⚡
                </button>
              </div>
            </div>
          </div>
        </div>

        <style>
        @keyframes pulseIcon {
          0% { transform: scale(1); }
          50% { transform: scale(1.1); }
          100% { transform: scale(1); }
        }
        </style>

        <script>
        // ตรวจสอบอัปเดตจาก GitHub ในพื้นหลังแบบ Asynchronous
        document.addEventListener('DOMContentLoaded', function() {
            fetch('api_update.php?action=check')
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data && data.update_available) {
                    var container = document.getElementById('edhsUpdateBannerContainer');
                    if (container) {
                        var verBadge = document.getElementById('bannerVersionBadge');
                        var titleEl = document.getElementById('bannerTitle');
                        var btnUpdate = document.getElementById('bannerBtnUpdate');

                        if (verBadge) verBadge.innerHTML = 'v' + data.current_version + ' &rarr; v' + data.latest_version;
                        if (titleEl && data.meta && data.meta.title) titleEl.innerText = data.meta.title;
                        if (btnUpdate) btnUpdate.setAttribute('onclick', "triggerSystemUpdate('" + data.latest_version + "')");
                        
                        container.style.display = 'block';
                    }

                    // อัปเดตข้อมูลใน Modal รายละเอียดการปรับปรุง (Changelog Modal)
                    if (data.upcoming_release) {
                        var cardId = 'card-release-' + data.latest_version;
                        var existingCard = document.getElementById(cardId);
                        var changelogContainer = document.querySelector('#changelogModal .changelog-container');
                        if (!existingCard && changelogContainer) {
                            var rel = data.upcoming_release;
                            var changesHtml = '';
                            if (rel.changes && rel.changes.length > 0) {
                                rel.changes.forEach(function(ch) {
                                    var tag = ch.tag || 'ปรับปรุง';
                                    var desc = ch.description || '';
                                    var icon = ch.icon || 'bx-check-circle';
                                    var badgeColor = ch.color || 'info';
                                    var colorMap = {
                                        'success': 'background-color: #e8fadf; color: #28a745; border: 1px solid #c3e6cb;',
                                        'danger':  'background-color: #ffeef0; color: #dc3545; border: 1px solid #f5c6cb;',
                                        'warning': 'background-color: #fff8e6; color: #ff9800; border: 1px solid #ffeeba;',
                                        'primary': 'background-color: #ebeefe; color: #696cff; border: 1px solid #d4dafd;',
                                        'info':    'background-color: #e7f7ff; color: #007bff; border: 1px solid #b8daff;'
                                    };
                                    var bStyle = colorMap[badgeColor] || colorMap['info'];
                                    changesHtml += '<div class="p-3 rounded-3" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">' +
                                        '<div class="d-flex align-items-center gap-2 mb-2 flex-wrap">' +
                                          '<span class="badge rounded-pill px-2.5 py-1 d-inline-flex align-items-center fw-bold" style="' + bStyle + ' font-size: 12px;">' +
                                            '<i class="bx ' + icon + ' me-1"></i>' + tag +
                                          '</span>' +
                                        '</div>' +
                                        '<div class="text-secondary ps-1" style="font-size: 13.5px; line-height: 1.65; word-break: break-word;">' +
                                          desc +
                                        '</div>' +
                                      '</div>';
                                });
                            } else {
                                changesHtml = '<p class="text-muted mb-0 py-2" style="font-size: 13px;">ไม่มีรายละเอียดการเปลี่ยนแปลงย่อย</p>';
                            }

                            var newCardHtml = '<div class="card mb-4 border-0 shadow-sm" id="' + cardId + '" style="border-radius: 14px; overflow: hidden; border-left: 5px solid #ff9800 !important; background: #fffdf5;">' +
                                '<div class="card-header bg-white pt-3 pb-3 px-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-color: #ffeeba !important;">' +
                                  '<div class="d-flex align-items-center gap-2 flex-wrap">' +
                                    '<span class="badge bg-warning text-dark rounded-pill px-3 py-1.5 fw-bold shadow-xs" style="font-size: 13.5px;">' +
                                      '<i class="bx bx-bell-ring me-1"></i>v' + rel.version + ' (เวอร์ชั่นใหม่ที่จะปรับปรุง)' +
                                    '</span>' +
                                    '<span class="badge rounded-pill px-2.5 py-1 fw-bold shadow-xs" style="background-color: #ffeeba; color: #856404; font-size: 11.5px;">' +
                                      '<i class="bx bx-up-arrow-circle me-1"></i>พร้อมติดตั้ง' +
                                    '</span>' +
                                  '</div>' +
                                  '<div class="d-flex align-items-center gap-2">' +
                                    '<span class="text-muted" style="font-size: 13px;"><i class="bx bx-calendar me-1 text-primary"></i>' + (rel.date || '') + '</span>' +
                                    '<button type="button" class="btn btn-warning btn-sm rounded-pill px-3 py-1 fw-bold text-dark d-inline-flex align-items-center shadow-xs" onclick="triggerSystemUpdate(\'' + rel.version + '\')">' +
                                      '<i class="bx bx-refresh me-1 font-size-16"></i>อัปเดตระบบเดี๋ยวนี้ ⚡' +
                                    '</button>' +
                                  '</div>' +
                                '</div>' +
                                '<div class="px-4 pt-3 pb-2 bg-white">' +
                                  '<h6 class="fw-bold text-dark mb-0" style="font-size: 14.5px; line-height: 1.6;">' + (rel.title || '') + '</h6>' +
                                '</div>' +
                                '<div class="card-body px-4 py-3" style="background-color: #ffffff;">' +
                                  '<div class="d-flex align-items-center mb-3">' +
                                    '<span class="fw-bold text-dark" style="font-size: 13.5px;"><i class="bx bx-list-check me-1 text-warning font-size-18"></i>รายการที่จะปรับปรุงในเวอร์ชั่นนี้:</span>' +
                                  '</div>' +
                                  '<div class="d-flex flex-column gap-3">' + changesHtml + '</div>' +
                                '</div>' +
                              '</div>';

                            changelogContainer.insertAdjacentHTML('afterbegin', newCardHtml);
                        }
                    }
                }
            })
            .catch(function(err) {
                console.log('Background update check:', err);
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('render_version_badge')) {
    function render_version_badge($showDate = true, $extraClasses = "") {
        $version = get_system_version();
        $date = get_system_version_date();
        $formattedDate = format_changelog_date($date);
        $updateInfo = check_system_update_available();
        $hasUpdate = $updateInfo['available'];

        $html = '<div class="d-inline-flex align-items-center flex-wrap gap-2 ' . htmlspecialchars($extraClasses) . '">';
        $html .= '<button type="button" class="btn btn-sm ' . ($hasUpdate ? 'btn-primary' : 'btn-outline-primary') . ' rounded-pill px-3 py-1 d-inline-flex align-items-center shadow-xs border-dashed" onclick="openChangelogModal()" style="font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;" title="คลิกเพื่อดูประวัติการอัปเดตระบบ">';
        $html .= '<i class="bx bx-git-repo-forked me-1 font-size-14"></i>เวอร์ชั่น ' . htmlspecialchars($version);
        if ($hasUpdate) {
            $html .= '<span class="badge bg-warning text-dark rounded-pill ms-2 px-2 fw-bold" style="font-size: 10px;"><i class="bx bx-up-arrow-circle me-1"></i>New Update</span>';
        } else {
            $html .= '<span class="badge bg-primary text-white rounded-pill ms-2 px-2" style="font-size: 10px; font-weight: 500;">What\'s New</span>';
        }
        $html .= '</button>';

        if ($showDate) {
            $html .= '<small class="text-muted d-inline-flex align-items-center" style="font-size: 12.5px;">';
            $html .= '<i class="bx bx-calendar me-1 text-secondary"></i>ข้อมูล ณ วันที่ ' . $formattedDate;
            $html .= '</small>';
        }
        $html .= '</div>';

        return $html;
    }
}

if (!function_exists('render_changelog_modal')) {
    function render_changelog_modal() {
        $version = get_system_version();
        $releases = get_system_changelog();
        $updateInfo = check_system_update_available();
        $hasUpdate = $updateInfo['available'];
        $latestVer = $updateInfo['latest_version'] ?? $version;

        ob_start();
        ?>
        <!-- Modal ประวัติการอัปเดตระบบ (Changelog & Release Notes) -->
        <div class="modal fade" id="changelogModal" tabindex="-1" aria-labelledby="changelogModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl" style="max-width: 960px;" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
              
              <!-- Modal Header -->
              <div class="modal-header text-white px-4 py-3 align-items-center" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
                <div class="d-flex align-items-center">
                  <div class="p-2 rounded-circle bg-white text-dark me-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px;">
                    <i class="bx bx-gift font-size-24" style="font-size: 24px; color: #696cff;"></i>
                  </div>
                  <div>
                    <h5 class="modal-title text-white mb-0 fw-bold" id="changelogModalLabel" style="font-size: 17px; letter-spacing: 0.3px;">
                      ประวัติการอัปเดตระบบ (Release Notes)
                    </h5>
                    <small class="text-white-50" style="font-size: 12.5px;">
                      ระบบบริหารลูกหนี้โรงพยาบาลอิเล็กทรอนิกส์ (eDHS)
                    </small>
                  </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <?php if ($hasUpdate): ?>
                    <button type="button" class="btn btn-warning btn-sm rounded-pill px-3 py-1 fw-bold shadow-xs text-dark d-inline-flex align-items-center" onclick="triggerSystemUpdate('<?= htmlspecialchars($latestVer) ?>')">
                      <i class="bx bx-refresh me-1 font-size-16"></i>อัปเดตเป็น v<?= htmlspecialchars($latestVer); ?>
                    </button>
                  <?php else: ?>
                    <span class="badge bg-white text-dark rounded-pill px-3 py-1.5 fw-semibold shadow-xs d-inline-flex align-items-center" style="font-size: 12px;">
                      <i class="bx bx-check-circle me-1 text-success font-size-16"></i>v<?= htmlspecialchars($version); ?> (เวอร์ชันล่าสุด)
                    </span>
                  <?php endif; ?>
                  <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
              </div>

              <!-- Modal Body -->
              <div class="modal-body p-4" style="background-color: #f1f5f9; max-height: 75vh; overflow-x: hidden;">
                <?php if (empty($releases)): ?>
                  <div class="text-center py-5">
                    <i class="bx bx-folder-open text-muted" style="font-size: 54px;"></i>
                    <p class="text-muted mt-2 fs-6">ยังไม่มีข้อมูลบันทึกประวัติการอัปเดตในระบบ</p>
                  </div>
                <?php else: ?>
                  <div class="changelog-container">
                    <?php foreach ($releases as $index => $rel): 
                      $relVersion = $rel['version'] ?? '-';
                      $isUpcoming = !empty($rel['is_upcoming']);
                      $isCurrent = ($relVersion === $version && !$isUpcoming);
                      $relDate = isset($rel['date']) ? format_changelog_date($rel['date']) : '';
                      $relTitle = $rel['title'] ?? 'การปรับปรุงระบบ';
                      $changes = $rel['changes'] ?? [];
                      $cardBorder = $isUpcoming 
                          ? 'border-left: 5px solid #ff9800 !important; background-color: #fffdf5;' 
                          : ($isCurrent ? 'border-left: 5px solid #696cff !important;' : 'border-left: 5px solid #94a3b8 !important;');
                    ?>
                      <div class="card mb-4 border-0 shadow-sm" id="card-release-<?= htmlspecialchars($relVersion); ?>" style="border-radius: 14px; overflow: hidden; <?= $cardBorder; ?>">
                        
                        <!-- Version Header -->
                        <div class="card-header bg-white pt-3 pb-3 px-4 border-bottom" style="border-color: <?= $isUpcoming ? '#ffeeba' : '#e2e8f0'; ?> !important;">
                          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                              <?php if ($isUpcoming): ?>
                                <span class="badge bg-warning text-dark rounded-pill px-3 py-1.5 fw-bold shadow-xs" style="font-size: 13.5px; letter-spacing: 0.5px;">
                                  <i class="bx bx-bell-ring me-1"></i>v<?= htmlspecialchars($relVersion); ?> (เวอร์ชั่นใหม่ที่จะปรับปรุง)
                                </span>
                                <span class="badge rounded-pill px-2.5 py-1 fw-bold shadow-xs" style="background-color: #ffeeba; color: #856404; font-size: 11.5px;">
                                  <i class="bx bx-up-arrow-circle me-1"></i>พร้อมติดตั้ง
                                </span>
                              <?php else: ?>
                                <span class="badge <?= $isCurrent ? 'bg-primary' : 'bg-secondary'; ?> rounded-pill px-3 py-1.5 fw-bold shadow-xs" style="font-size: 13.5px; letter-spacing: 0.5px;">
                                  <i class="bx bx-git-commit me-1"></i>v<?= htmlspecialchars($relVersion); ?>
                                </span>
                                <?php if ($isCurrent): ?>
                                  <span class="badge rounded-pill px-2.5 py-1 fw-semibold shadow-xs" style="background-color: #e8fadf; color: #28a745; font-size: 11.5px;">
                                    <i class="bx bx-star me-1"></i>เวอร์ชั่นปัจจุบันที่ใช้งานอยู่
                                  </span>
                                <?php endif; ?>
                              <?php endif; ?>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                              <div class="text-muted d-flex align-items-center" style="font-size: 13px;">
                                <i class="bx bx-calendar me-1.5 text-primary"></i><?= $relDate; ?>
                              </div>
                              <?php if ($isUpcoming): ?>
                                <button type="button" class="btn btn-warning btn-sm rounded-pill px-3 py-1 fw-bold text-dark d-inline-flex align-items-center shadow-xs" onclick="triggerSystemUpdate('<?= htmlspecialchars($relVersion) ?>')">
                                  <i class="bx bx-refresh me-1 font-size-16"></i>อัปเดตระบบเดี๋ยวนี้ ⚡
                                </button>
                              <?php endif; ?>
                            </div>
                          </div>
                          <h6 class="fw-bold text-dark mb-0" style="font-size: 14.5px; line-height: 1.6;">
                            <?= htmlspecialchars($relTitle); ?>
                          </h6>
                        </div>

                        <!-- Changes Items -->
                        <div class="card-body px-4 py-3" style="background-color: #ffffff;">
                          <?php if ($isUpcoming): ?>
                            <div class="d-flex align-items-center mb-3">
                              <span class="fw-bold text-dark" style="font-size: 13.5px;"><i class="bx bx-list-check me-1 text-warning font-size-18"></i>รายการที่จะปรับปรุงในเวอร์ชั่นนี้:</span>
                            </div>
                          <?php endif; ?>
                          <?php if (!empty($changes)): ?>
                            <div class="d-flex flex-column gap-3">
                              <?php foreach ($changes as $change): 
                                $meta = get_changelog_category_meta(
                                    $change['category'] ?? '', 
                                    $change['tag'] ?? ''
                                );
                                $tag = $meta['tag'];
                                $color = $meta['color'];
                                $icon = $meta['icon'];
                                $desc = $change['description'] ?? '';

                                // Mapping สีสำหรับพื้นหลังและตัวอักษร
                                $badgeStyles = [
                                    'success' => 'background-color: #e8fadf; color: #28a745; border: 1px solid #c3e6cb;',
                                    'info'    => 'background-color: #e7f7ff; color: #007bff; border: 1px solid #b8daff;',
                                    'danger'  => 'background-color: #ffeef0; color: #dc3545; border: 1px solid #f5c6cb;',
                                    'warning' => 'background-color: #fff8e6; color: #ff9800; border: 1px solid #ffeeba;',
                                    'primary' => 'background-color: #ebeefe; color: #696cff; border: 1px solid #d4dafd;'
                                ];
                                $badgeStyle = $badgeStyles[$color] ?? $badgeStyles['info'];
                              ?>
                                <div class="p-3 rounded-3" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                                  <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                                    <span class="badge rounded-pill px-2.5 py-1 d-inline-flex align-items-center fw-bold" style="<?= $badgeStyle ?> font-size: 12px;">
                                      <i class="bx <?= htmlspecialchars($icon); ?> me-1"></i><?= htmlspecialchars($tag); ?>
                                    </span>
                                  </div>
                                  <div class="text-secondary ps-1" style="font-size: 13.5px; line-height: 1.65; word-break: break-word;">
                                    <?= htmlspecialchars($desc); ?>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            </div>
                          <?php else: ?>
                            <p class="text-muted mb-0 py-2" style="font-size: 13px;">ไม่มีรายละเอียดการเปลี่ยนแปลงย่อย</p>
                          <?php endif; ?>
                        </div>

                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Modal Footer -->
              <div class="modal-footer bg-white border-top px-4 py-3 d-flex justify-content-between align-items-center">
                <small class="text-muted d-flex align-items-center">
                  <i class="bx bx-info-circle me-1.5 text-primary"></i>หากพบปัญหาการใช้งาน กรุณาติดต่อทีมพัฒนาระบบ eDHS
                </small>
                <div class="d-flex gap-2">
                  <?php if ($hasUpdate): ?>
                    <button type="button" class="btn btn-warning text-dark px-4 py-2 rounded-pill fw-bold shadow-xs d-inline-flex align-items-center" onclick="triggerSystemUpdate('<?= htmlspecialchars($latestVer) ?>')">
                      <i class="bx bx-refresh me-1.5 font-size-18"></i>อัปเดตระบบเดี๋ยวนี้ ⚡
                    </button>
                  <?php endif; ?>
                  <button type="button" class="btn btn-primary px-4 py-2 rounded-pill fw-semibold shadow-xs" data-bs-dismiss="modal">
                    <i class="bx bx-check me-1"></i>ปิดหน้าต่าง
                  </button>
                </div>
              </div>

            </div>
          </div>
        </div>

        <script>
        function openChangelogModal() {
            var modalEl = document.getElementById('changelogModal');
            if (!modalEl) return;
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var modal = bootstrap.Modal.getInstance(modalEl);
                if (!modal) {
                    modal = new bootstrap.Modal(modalEl);
                }
                modal.show();
            } else if (typeof $ !== 'undefined') {
                $('#changelogModal').modal('show');
            }
        }

        function triggerSystemUpdate(version) {
            var targetVer = version || '';
            var confirmMsg = targetVer ? 'คุณต้องการอัปเดตระบบเป็นเวอร์ชัน ' + targetVer + ' หรือไม่?' : 'คุณต้องการอัปเดตระบบเป็นเวอร์ชันล่าสุดหรือไม่?';
            
            var runUpdate = function() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'กำลังดำเนินการอัปเดตระบบ...',
                        html: '<div class="text-center py-2"><div class="spinner-border text-primary mb-3" role="status"></div><p class="text-muted mb-0">ระบบกำลังสำรองข้อมูลและติดตั้งไฟล์แพตช์ กรุณารอสักครู่...</p></div>',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false
                    });
                }

                var formData = new FormData();
                formData.append('action', 'apply');
                if (targetVer) formData.append('version', targetVer);

                fetch('api_update.php?action=apply', {
                    method: 'POST',
                    body: formData
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'อัปเดตระบบสำเร็จ!',
                                text: data.message || 'ระบบได้รับการอัปเดตเป็นเวอร์ชันล่าสุดเรียบร้อยแล้ว',
                                confirmButtonText: 'ตกลง (โหลดหน้าใหม่)',
                                confirmButtonColor: '#696cff'
                            }).then(function() {
                                window.location.reload();
                            });
                        } else {
                            alert('อัปเดตระบบสำเร็จ: ' + (data.message || ''));
                            window.location.reload();
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'เกิดข้อผิดพลาดในการอัปเดต',
                                text: data.message || 'ไม่สามารถติดตั้งอัปเดตได้ กรุณาตรวจสอบบันทึก Log',
                                confirmButtonColor: '#d33'
                            });
                        } else {
                            alert('เกิดข้อผิดพลาด: ' + (data.message || ''));
                        }
                    }
                })
                .catch(function(err) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'การเชื่อมต่อขัดข้อง',
                            text: 'ไม่สามารถติดต่อเซิร์ฟเวอร์เพื่อทำการอัปเดตได้: ' + err.message,
                            confirmButtonColor: '#d33'
                        });
                    } else {
                        alert('การเชื่อมต่อขัดข้อง: ' + err.message);
                    }
                });
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'ยืนยันการอัปเดตระบบ',
                    text: confirmMsg,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#696cff',
                    cancelButtonColor: '#8592a3',
                    confirmButtonText: 'ยืนยัน อัปเดตทันที',
                    cancelButtonText: 'ยกเลิก'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        runUpdate();
                    }
                });
            } else {
                if (confirm(confirmMsg)) {
                    runUpdate();
                }
            }
        }
        </script>
        <?php
        return ob_get_clean();
    }
}
?>
