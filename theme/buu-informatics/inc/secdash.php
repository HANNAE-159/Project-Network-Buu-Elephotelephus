<?php
/**
 * หน้า "ความปลอดภัย" ในหลังบ้าน WordPress — Dashboard ตรวจสอบการโจมตี (Stage 2)
 * อ่านไฟล์สรุปที่ secdash-collect.py สร้างไว้ (/var/lib/secdash/summary.json)
 * เปิดดูได้เฉพาะผู้ดูแลระบบ (manage_options) · ไม่เปิดสู่สาธารณะ
 */
if (!defined('ABSPATH')) exit;

const BUU_SECDASH_FILE = '/var/lib/secdash/summary.json';

add_action('admin_menu', function () {
    add_menu_page('ความปลอดภัย', 'ความปลอดภัย', 'manage_options', 'buu-secdash', 'buu_secdash_page', 'dashicons-shield-alt', 3);
});

/** อ่านไฟล์สรุป */
function buu_secdash_data() {
    if (!is_readable(BUU_SECDASH_FILE)) return null;
    $raw = file_get_contents(BUU_SECDASH_FILE);
    $d = json_decode($raw, true);
    return is_array($d) ? $d : null;
}

function buu_sd_name($d, $type) {
    return isset($d['type_info'][$type]['name']) ? $d['type_info'][$type]['name'] : $type;
}
function buu_sd_sev($d, $type) {
    return isset($d['type_info'][$type]['sev']) ? (int) $d['type_info'][$type]['sev'] : 0;
}
function buu_sd_sevclass($s) {
    return $s >= 5 ? 'crit' : ($s >= 4 ? 'high' : ($s >= 1 ? 'med' : 'ok'));
}
function buu_sd_where($w) {
    return ['th' => '🇹🇭 ไทย', 'foreign' => '🌐 ต่างประเทศ', 'local' => 'ภายใน'][$w] ?? '—';
}
function buu_sd_ago($ts) {
    $s = time() - (int) $ts;
    if ($s < 60) return 'เมื่อสักครู่';
    if ($s < 3600) return floor($s / 60) . ' นาทีที่แล้ว';
    if ($s < 86400) return floor($s / 3600) . ' ชั่วโมงที่แล้ว';
    return floor($s / 86400) . ' วันที่แล้ว';
}

function buu_sd_num($n) { return number_format((int) $n); }

function buu_secdash_page() {
    if (!current_user_can('manage_options')) return;
    $d = buu_secdash_data();
    echo '<div class="wrap buu-sd">';
    echo '<div class="sd-top"><div><h1>ศูนย์ตรวจการโจมตี</h1><p class="sd-sub">เว็บไซต์หลักสูตร วท.บ. วิทยาการคอมพิวเตอร์ · ข้อมูลจริงจาก log ของเซิร์ฟเวอร์</p></div>';

    if ($d === null) {
        echo '</div><div class="notice notice-warning"><p>ยังไม่มีข้อมูล — ต้องติดตั้งตัวเก็บข้อมูลบนเซิร์ฟเวอร์ก่อน (secdash-install.sh) '
           . 'เมื่อติดตั้งแล้ว ระบบจะอ่าน log ทุก 10 นาที แล้วแสดงผลที่หน้านี้</p></div></div>';
        buu_secdash_css();
        return;
    }

    $kpi = $d['kpi']; $lv = $d['level'];
    $lvmap = ['high' => ['อันตราย', 'crit', 'High'], 'medium' => ['เฝ้าระวัง', 'high', 'Medium'], 'low' => ['ปลอดภัย', 'ok', 'Low']];
    [$lvtext, $lvcls, $lven] = $lvmap[$lv] ?? ['—', 'ok', ''];
    $fw = $d['firewall'] ?? [];
    echo '<div class="sd-meta"><span class="sd-level sd-' . esc_attr($lvcls) . '"><i></i>' . esc_html($lvtext) . ' (' . esc_html($lven) . ')</span>'
       . '<span>อัปเดต ' . esc_html(buu_sd_ago($d['generated'])) . '</span>'
       . '<a class="button" href="' . esc_url(admin_url('admin.php?page=buu-secdash')) . '">รีเฟรช</a></div></div>';

    // ----- AI -----
    $ai = $d['ai'] ?? [];
    echo '<section class="sd-card sd-ai"><div class="sd-h"><h2><span class="dashicons dashicons-superhero"></span> ผลการวิเคราะห์ความปลอดภัยโดย AI</h2>';
    if (!empty($ai['ok'])) echo '<span class="sd-tag">' . esc_html(($ai['provider'] ?? '') . ' · ' . buu_sd_ago($ai['t'] ?? time())) . '</span>';
    echo '</div>';
    if (!empty($ai['enabled']) && !empty($ai['ok'])) {
        echo '<div class="sd-exec"><b>บทสรุปผู้บริหาร (Executive Summary)</b><p>' . esc_html($ai['summary'] ?? '') . '</p></div>';
        echo '<div class="sd-ai-cols"><div class="sd-find"><h3>ประเด็นสำคัญที่ตรวจพบ <em>' . count($ai['findings'] ?? []) . ' รายการ</em></h3><ol>';
        foreach (($ai['findings'] ?? []) as $x) echo '<li>' . esc_html($x) . '</li>';
        echo '</ol></div><div class="sd-rec"><h3>ข้อเสนอแนะเชิงป้องกัน <em>แนวทางสู่ผู้ดูแล</em></h3><ol>';
        foreach (($ai['actions'] ?? []) as $x) echo '<li>' . esc_html($x) . '</li>';
        echo '</ol><p class="sd-note">AI อ่านอย่างเดียว ไม่มีสิทธิ์แบน IP หรือแก้ระบบเอง</p></div></div>';
    } elseif (!empty($ai['enabled'])) {
        echo '<p class="sd-muted">' . esc_html($ai['error'] ?? 'ยังไม่มีผลวิเคราะห์') . '</p>';
    } else {
        echo '<p class="sd-muted">ยังไม่เปิดใช้ AI · ใส่ API key ที่ <code>/etc/secdash/ai.env</code> แล้ว AI จะสรุปเป็นภาษาไทยทุกชั่วโมง</p>';
    }
    echo '</section>';

    // ----- KPI -----
    $cards = [
        ['คำขอเข้าเว็บ', $kpi['requests_24h'], '24 ชม.', 'blue'],
        ['การโจมตีที่ตรวจพบ', $kpi['attacks_24h'], '24 ชม.', 'amber'],
        ['แพ็กเก็ตต่างประเทศที่ firewall ทิ้ง', $fw['dropped_pkts'] ?? 0, 'ตั้งแต่เปิด firewall', 'red'],
        ['IP ต่างประเทศที่ถูกบล็อก', $fw['blocked_ips_7d'] ?? 0, '7 วัน', 'red'],
        ['ถูก fail2ban แบนอยู่', $kpi['banned_now'], 'ตอนนี้', 'violet'],
        ['IP น่าสงสัย', $kpi['unique_attackers_7d'], '7 วัน', 'slate'],
    ];
    echo '<div class="sd-kpis">';
    foreach ($cards as $c) echo '<div class="sd-kpi k-' . esc_attr($c[3]) . '"><span>' . esc_html($c[0]) . '</span><b>' . esc_html(buu_sd_num($c[1])) . '</b><small>' . esc_html($c[2]) . '</small></div>';
    echo '</div>';

    if (!empty($d['anomaly']['flag'])) {
        echo '<div class="sd-alert">ชั่วโมงล่าสุดมีคำขอ ' . (int) $d['anomaly']['last_hour'] . ' ครั้ง สูงกว่าค่าเฉลี่ย (' . esc_html($d['anomaly']['mean']) . ') ผิดปกติ · อาจมีบอทยิงเว็บจำนวนมาก</div>';
    }

    // ----- กราฟหลัก -----
    echo '<section class="sd-card"><div class="sd-h"><div><h2>แนวโน้มทราฟฟิกและการโจมตี</h2><p class="sd-muted">คำขอเข้าเว็บ · การโจมตีที่ตรวจพบ · IP ต่างประเทศที่ firewall บล็อก (นับจาก log)</p></div>'
       . '<div class="sd-tabs"><input type="radio" name="sdr" id="sdr24" checked><label for="sdr24">24 ชั่วโมง</label><input type="radio" name="sdr" id="sdr7"><label for="sdr7">7 วัน</label>'
       . '<div class="sd-pane p24">' . buu_sd_line($d['timeline'] ?? [], 3) . '</div>'
       . '<div class="sd-pane p7">' . buu_sd_line($d['timeline_7d'] ?? [], 24) . '</div></div></div>'
       . '<div class="sd-legend"><span class="l-req">คำขอทั้งหมด</span><span class="l-atk">การโจมตี</span><span class="l-blk">ถูกบล็อก</span></div></section>';

    echo '<div class="sd-row3">';

    // ----- donut สถานะ HTTP -----
    $st = $d['status_24h'] ?? [];
    echo '<section class="sd-card"><div class="sd-h"><h2>ผลลัพธ์ HTTP <em class="sd-tag">24 ชม.</em></h2></div>' . buu_sd_donut($st) . '</section>';

    // ----- ระดับความเสี่ยง -----
    $rk = $d['risk_7d'] ?? ['high' => 0, 'medium' => 0, 'low' => 0];
    $rmax = max(1, max($rk));
    $rdesc = ['high' => ['ความเสี่ยงสูง', 'SQLi · XSS · traversal · สแกนไฟล์ลับ · SSH ชื่อผิด · โดนแบน', 'crit'],
              'medium' => ['ความเสี่ยงปานกลาง', 'เดารหัส · ไล่หาชื่อผู้ใช้ · เครื่องมือสแกน · ยิงถี่เกิน', 'high'],
              'low' => ['ความเสี่ยงต่ำ', 'เรียก xmlrpc · IP ต่างประเทศถูกบล็อก', 'ok']];
    echo '<section class="sd-card"><div class="sd-h"><h2>สัดส่วนระดับความเสี่ยง <em class="sd-tag">7 วัน</em></h2></div><div class="sd-risk">';
    foreach ($rdesc as $k => $r) {
        echo '<div class="r r-' . esc_attr($r[2]) . '"><div class="rt"><b>' . esc_html($r[0]) . '</b><span>' . esc_html(buu_sd_num($rk[$k] ?? 0)) . ' เหตุการณ์</span></div>'
           . '<div class="rb"><i style="width:' . esc_attr(max(2, round(($rk[$k] ?? 0) / $rmax * 100))) . '%"></i></div><small>' . esc_html($r[1]) . '</small></div>';
    }
    echo '</div></section>';

    // ----- เป้าหมายที่ถูกโจมตีบ่อย -----
    $tg = $d['top_targets'] ?? [];
    echo '<section class="sd-card"><div class="sd-h"><h2>เป้าหมายที่ถูกโจมตีบ่อยที่สุด <em class="sd-tag">7 วัน</em></h2></div>';
    if ($tg) {
        $tmax = max(1, $tg[0]['count']);
        echo '<ol class="sd-targets">';
        foreach ($tg as $t) echo '<li><div><code>' . esc_html($t['path']) . '</code><span>' . esc_html(buu_sd_num($t['count'])) . ' ครั้ง</span></div><i style="width:' . esc_attr(max(3, round($t['count'] / $tmax * 100))) . '%"></i></li>';
        echo '</ol>';
    } else echo '<p class="sd-none">ยังไม่พบ URL ที่ถูกโจมตี</p>';
    echo '</section>';
    echo '</div>';

    // ----- firewall -----
    echo '<section class="sd-card sd-fw"><div class="sd-h"><h2>Geo-firewall (ไทยเท่านั้น)</h2><span class="sd-tag ' . (!empty($fw['active']) ? 'on' : 'off') . '">' . (!empty($fw['active']) ? 'ทำงานอยู่' : 'ปิดอยู่') . '</span></div><div class="sd-fwgrid">'
       . '<div><b>' . esc_html(buu_sd_num($fw['entries'] ?? 0)) . '</b><span>ช่วง IP ไทยในรายการ</span></div>'
       . '<div><b>' . esc_html(buu_sd_num($fw['dropped_pkts'] ?? 0)) . '</b><span>แพ็กเก็ตต่างประเทศที่ถูกทิ้ง</span></div>'
       . '<div><b>' . esc_html(size_format((int) ($fw['dropped_bytes'] ?? 0))) . '</b><span>ข้อมูลที่ถูกทิ้ง</span></div>'
       . '<div><b>' . esc_html(buu_sd_num($fw['blocked_ips_7d'] ?? 0)) . '</b><span>IP ต่างประเทศไม่ซ้ำ (7 วัน)</span></div>'
       . '</div><p class="sd-muted">ตัวเลขแพ็กเก็ตมาจากตัวนับของ iptables โดยตรง จึงนับครบทุกแพ็กเก็ต · ส่วนรายชื่อ IP มาจาก log ที่จำกัดความถี่ไว้กัน log ล้น</p></section>';

    echo '<div class="sd-row2">';
    // ----- IP น่าสงสัย -----
    echo '<section class="sd-card"><div class="sd-h"><h2>IP ที่น่าสงสัยที่สุด</h2></div><table class="sd-tbl"><thead><tr><th>IP</th><th>ที่มา</th><th>พฤติกรรม</th><th>คะแนน</th><th>ครั้ง</th><th>ล่าสุด</th><th></th></tr></thead><tbody>';
    foreach (array_slice($d['top_attackers'] ?? [], 0, 10) as $a) {
        $types = array_map(function ($t) use ($d) { return buu_sd_name($d, $t); }, $a['types']);
        echo '<tr><td><code>' . esc_html($a['ip']) . '</code></td><td>' . esc_html(buu_sd_where($a['where'] ?? '')) . '</td>'
           . '<td class="sm">' . esc_html(implode(', ', $types)) . '</td><td><span class="sd-score">' . (int) $a['score'] . '</span></td><td>' . (int) $a['count'] . '</td>'
           . '<td class="sm">' . esc_html(buu_sd_ago($a['last'])) . '</td><td>' . ($a['banned'] ? '<span class="sd-pill crit">แบนแล้ว</span>' : '<span class="sd-pill">เฝ้าดู</span>') . '</td></tr>';
    }
    if (empty($d['top_attackers'])) echo '<tr><td colspan="7" class="sd-none">ยังไม่พบ IP น่าสงสัย</td></tr>';
    echo '</tbody></table></section>';

    // ----- การเข้าระบบ -----
    echo '<section class="sd-card"><div class="sd-h"><h2>การเข้าระบบสำเร็จ</h2></div><table class="sd-tbl"><thead><tr><th>ช่องทาง</th><th>IP</th><th>เมื่อ</th></tr></thead><tbody>';
    foreach (array_slice($d['logins'] ?? [], 0, 10) as $l) {
        echo '<tr><td>' . ($l['type'] === 'ssh_ok' ? 'SSH' : 'WordPress') . '</td><td><code>' . esc_html($l['ip']) . '</code></td><td class="sm">' . esc_html(buu_sd_ago($l['t'])) . '</td></tr>';
    }
    if (empty($d['logins'])) echo '<tr><td colspan="3" class="sd-none">ไม่มีข้อมูล</td></tr>';
    echo '</tbody></table></section></div>';

    // ----- เหตุการณ์ล่าสุด -----
    echo '<section class="sd-card"><div class="sd-h"><h2>เหตุการณ์ล่าสุด</h2></div><table class="sd-tbl"><thead><tr><th>เมื่อ</th><th>IP</th><th>ประเภท</th><th>รายละเอียด</th></tr></thead><tbody>';
    foreach (array_slice($d['events'] ?? [], 0, 30) as $e) {
        $cls = buu_sd_sevclass(buu_sd_sev($d, $e['type']));
        echo '<tr><td class="sm">' . esc_html(buu_sd_ago($e['t'])) . '</td><td><code>' . esc_html($e['ip']) . '</code></td>'
           . '<td><span class="sd-pill ' . esc_attr($cls) . '">' . esc_html(buu_sd_name($d, $e['type'])) . '</span></td>'
           . '<td class="sd-detail">' . esc_html($e['detail']) . ($e['ua'] ? ' <span>· ' . esc_html($e['ua']) . '</span>' : '') . '</td></tr>';
    }
    if (empty($d['events'])) echo '<tr><td colspan="4" class="sd-none">ยังไม่พบเหตุการณ์</td></tr>';
    echo '</tbody></table></section>';

    echo '<p class="sd-foot">ทุกตัวเลขมาจาก log จริงของเซิร์ฟเวอร์ (nginx, SSH, firewall, fail2ban) · ข้อความรายละเอียดมาจากผู้โจมตี แสดงเป็นตัวอักษรล้วน · หน้านี้เห็นเฉพาะผู้ดูแลระบบ</p>';
    echo '</div>';
    buu_secdash_css();
}

/** เส้นโค้งเรียบผ่านจุด (Catmull-Rom → Bézier) */
function buu_sd_path($pts, $close_y = null) {
    $n = count($pts); if ($n < 2) return '';
    $d = 'M' . round($pts[0][0], 1) . ' ' . round($pts[0][1], 1);
    for ($i = 0; $i < $n - 1; $i++) {
        $p0 = $pts[max(0, $i - 1)]; $p1 = $pts[$i]; $p2 = $pts[$i + 1]; $p3 = $pts[min($n - 1, $i + 2)];
        $c1x = $p1[0] + ($p2[0] - $p0[0]) / 6; $c1y = $p1[1] + ($p2[1] - $p0[1]) / 6;
        $c2x = $p2[0] - ($p3[0] - $p1[0]) / 6; $c2y = $p2[1] - ($p3[1] - $p1[1]) / 6;
        if ($close_y !== null) { $c1y = min($c1y, $close_y); $c2y = min($c2y, $close_y); }
        $d .= ' C' . round($c1x, 1) . ' ' . round($c1y, 1) . ' ' . round($c2x, 1) . ' ' . round($c2y, 1) . ' ' . round($p2[0], 1) . ' ' . round($p2[1], 1);
    }
    return $d;
}

/** กราฟเส้น 3 ชุด: คำขอ / โจมตี / บล็อก — $every = ระยะห่างของป้ายแกน x */
function buu_sd_line($tl, $every) {
    $n = count($tl); if ($n < 2) return '<p class="sd-none">ไม่มีข้อมูล</p>';
    $W = 1000; $H = 280; $L = 48; $R = 12; $T = 14; $B = 30; $gw = $W - $L - $R; $gh = $H - $T - $B;
    $max = 1;
    foreach ($tl as $h) $max = max($max, $h['requests'], $h['attacks'], $h['blocked']);
    $step = pow(10, floor(log10($max))); $nice = ceil($max / $step) * $step; if ($nice / $step > 5) $step *= 2; $max = $nice;
    $X = fn($i) => $L + $gw * $i / ($n - 1);
    $Y = fn($v) => $T + $gh - $v / $max * $gh;
    $base = $T + $gh;
    $svg = '<svg viewBox="0 0 ' . $W . ' ' . $H . '" class="sd-chart" role="img" aria-label="กราฟทราฟฟิกและการโจมตี"><defs>'
         . '<linearGradient id="gReq' . $every . '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#0b6fd0" stop-opacity=".28"/><stop offset="1" stop-color="#0b6fd0" stop-opacity="0"/></linearGradient>'
         . '<linearGradient id="gBlk' . $every . '" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#dc2626" stop-opacity=".18"/><stop offset="1" stop-color="#dc2626" stop-opacity="0"/></linearGradient></defs>';
    for ($k = 0; $k <= 4; $k++) {
        $v = $max * $k / 4; $y = round($Y($v), 1);
        $svg .= '<line x1="' . $L . '" x2="' . ($W - $R) . '" y1="' . $y . '" y2="' . $y . '" class="g"/><text x="' . ($L - 8) . '" y="' . ($y + 4) . '" class="ax ya">' . esc_html(buu_sd_num($v)) . '</text>';
    }
    foreach (['requests' => 'req', 'blocked' => 'blk', 'attacks' => 'atk'] as $key => $cls) {
        $pts = []; foreach ($tl as $i => $h) $pts[] = [$X($i), $Y($h[$key])];
        $line = buu_sd_path($pts, $base);
        if ($cls !== 'atk') $svg .= '<path d="' . $line . ' L' . round($X($n - 1), 1) . ' ' . $base . ' L' . $L . ' ' . $base . ' Z" fill="url(#g' . ($cls === 'req' ? 'Req' : 'Blk') . $every . ')"/>';
        $svg .= '<path d="' . $line . '" class="ln ' . $cls . '"/>';
    }
    foreach ($tl as $i => $h) {
        $lab = $every >= 24 ? substr($h['hour'], 8, 2) . '/' . substr($h['hour'], 5, 2) : substr($h['hour'], 11, 5);
        if ($i % $every === 0) $svg .= '<text x="' . round($X($i), 1) . '" y="' . ($H - 8) . '" class="ax xa">' . esc_html($lab) . '</text>';
        $svg .= '<rect x="' . round($X($i) - $gw / $n / 2, 1) . '" y="' . $T . '" width="' . round($gw / $n, 2) . '" height="' . $gh . '" class="hv"><title>'
              . esc_attr($h['hour'] . ' · คำขอ ' . $h['requests'] . ' · โจมตี ' . $h['attacks'] . ' · บล็อก ' . $h['blocked']) . '</title></rect>';
    }
    return $svg . '</svg>';
}

/** donut สถานะ HTTP */
function buu_sd_donut($st) {
    $cls = ['2xx' => ['สำเร็จ (2xx)', '#16a34a'], '3xx' => ['ส่งต่อ (3xx)', '#0b6fd0'], '4xx' => ['ถูกปฏิเสธ/ไม่พบ (4xx)', '#f59e0b'], '5xx' => ['ผิดพลาด (5xx)', '#dc2626']];
    $tot = array_sum($st);
    if (!$tot) return '<p class="sd-none">ไม่มีข้อมูล</p>';
    $r = 52; $c = 2 * M_PI * $r; $off = 0;
    $svg = '<div class="sd-donut"><svg viewBox="0 0 140 140" role="img" aria-label="สัดส่วนผลลัพธ์ HTTP"><circle cx="70" cy="70" r="' . $r . '" fill="none" stroke="#eef2f7" stroke-width="18"/>';
    foreach ($cls as $k => $v) {
        $n = (int) ($st[$k] ?? 0); if (!$n) continue;
        $len = $n / $tot * $c;
        $svg .= '<circle cx="70" cy="70" r="' . $r . '" fill="none" stroke="' . $v[1] . '" stroke-width="18" stroke-dasharray="' . round($len, 2) . ' ' . round($c - $len, 2) . '" stroke-dashoffset="' . round(-$off, 2) . '" transform="rotate(-90 70 70)"/>';
        $off += $len;
    }
    $ok = round(($st['2xx'] ?? 0) / $tot * 100, 1);
    $svg .= '<text x="70" y="66" class="dn-n">' . esc_html(buu_sd_num($tot)) . '</text><text x="70" y="84" class="dn-l">คำขอทั้งหมด</text></svg><ul>';
    foreach ($cls as $k => $v) $svg .= '<li><i style="background:' . $v[1] . '"></i>' . esc_html($v[0]) . '<b>' . esc_html(buu_sd_num($st[$k] ?? 0)) . '</b></li>';
    return $svg . '</ul><p class="sd-ok">สำเร็จ ' . esc_html($ok) . '%</p></div>';
}

function buu_secdash_css() {
    ?>
    <style>
    .buu-sd{max-width:1400px;color:#0f172a}
    .buu-sd h1{font-size:26px;font-weight:700;margin:6px 0 2px;padding:0}
    .buu-sd .sd-top{display:flex;justify-content:space-between;align-items:flex-end;gap:16px;flex-wrap:wrap;margin-bottom:18px}
    .buu-sd .sd-sub,.buu-sd .sd-muted{color:#64748b;margin:2px 0 0;font-size:13px}
    .buu-sd .sd-meta{display:flex;align-items:center;gap:14px;color:#64748b;font-size:13px}
    .sd-level{display:inline-flex;align-items:center;gap:8px;padding:6px 14px;border-radius:999px;font-weight:600;font-size:14px}
    .sd-level i{width:9px;height:9px;border-radius:50%;background:currentColor}
    .sd-level.sd-ok{background:#dcfce7;color:#15803d}.sd-level.sd-high{background:#fef3c7;color:#b45309}.sd-level.sd-crit{background:#fee2e2;color:#b91c1c}
    .buu-sd .sd-card{background:#fff;border:1px solid #e5e9f0;border-radius:16px;padding:20px 22px;margin-bottom:16px;box-shadow:0 1px 2px rgba(15,23,42,.04)}
    .buu-sd .sd-h{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:14px;flex-wrap:wrap}
    .buu-sd .sd-h h2{font-size:16px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px}
    .buu-sd .sd-tag{font-size:11px;font-style:normal;font-weight:600;background:#eef2ff;color:#4338ca;border-radius:999px;padding:3px 10px}
    .buu-sd .sd-tag.on{background:#dcfce7;color:#15803d}.buu-sd .sd-tag.off{background:#f1f5f9;color:#64748b}
    .sd-ai .dashicons{color:#7c3aed}
    .sd-exec{background:linear-gradient(90deg,#f5f3ff,#eef6ff);border-left:4px solid #7c3aed;border-radius:10px;padding:14px 16px;margin-bottom:14px}
    .sd-exec b{color:#5b21b6;font-size:13px}.sd-exec p{margin:6px 0 0;font-size:14px;line-height:1.6}
    .sd-ai-cols{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .sd-find,.sd-rec{border:1px solid #e5e9f0;border-radius:12px;padding:14px 16px;border-top:4px solid #f59e0b}
    .sd-rec{border-top-color:#16a34a}
    .sd-find h3,.sd-rec h3{font-size:14px;margin:0 0 10px;display:flex;justify-content:space-between;align-items:center}
    .sd-find h3 em,.sd-rec h3 em{font-style:normal;font-size:11px;font-weight:600;padding:2px 9px;border-radius:999px;background:#fef3c7;color:#b45309}
    .sd-rec h3 em{background:#dcfce7;color:#15803d}
    .sd-find ol,.sd-rec ol{margin:0;padding:0;list-style:none;counter-reset:f}
    .sd-find li,.sd-rec li{counter-increment:f;display:flex;gap:10px;font-size:13px;line-height:1.55;padding:8px 10px;border:1px solid #eef2f7;border-radius:8px;margin-bottom:8px}
    .sd-find li::before,.sd-rec li::before{content:counter(f,decimal-leading-zero);flex:none;font-size:11px;font-weight:700;color:#fff;background:#6366f1;border-radius:5px;padding:2px 5px;height:fit-content}
    .sd-rec li::before{background:#16a34a}
    .sd-note{font-size:12px;color:#64748b;margin:4px 0 0}
    .sd-kpis{display:grid;grid-template-columns:repeat(6,1fr);gap:12px;margin-bottom:16px}
    .sd-kpi{background:#fff;border:1px solid #e5e9f0;border-radius:14px;padding:14px 16px;position:relative;overflow:hidden}
    .sd-kpi::before{content:"";position:absolute;inset:0 auto 0 0;width:4px;background:#94a3b8}
    .sd-kpi span{display:block;font-size:12px;color:#64748b;min-height:32px}.sd-kpi b{display:block;font-size:28px;font-weight:700;line-height:1.15;margin-top:4px;font-variant-numeric:tabular-nums}.sd-kpi small{color:#94a3b8;font-size:11px}
    .k-blue::before{background:#0b6fd0}.k-amber::before{background:#f59e0b}.k-red::before{background:#dc2626}.k-violet::before{background:#7c3aed}.k-slate::before{background:#475569}
    .sd-alert{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:12px;padding:12px 16px;margin-bottom:16px;font-weight:600}
    .sd-tabs{display:flex;flex-wrap:wrap;gap:6px;align-items:center;width:100%}
    .sd-tabs>input{display:none}.sd-tabs>label{padding:5px 14px;border-radius:999px;font-size:12px;font-weight:600;background:#f1f5f9;color:#475569;cursor:pointer;order:-1}
    .sd-h>div:first-child{flex:1}.sd-h .sd-tabs{flex-basis:100%}
    #sdr24:checked+label,#sdr7:checked+label{background:#0a2e57;color:#fff}
    .sd-pane{display:none;width:100%;margin-top:8px}#sdr24:checked~.p24,#sdr7:checked~.p7{display:block}
    .sd-chart{width:100%;height:auto;display:block}
    .sd-chart .g{stroke:#eef2f7}.sd-chart .ax{fill:#94a3b8;font-size:11px}.sd-chart .ya{text-anchor:end}.sd-chart .xa{text-anchor:middle}
    .sd-chart .ln{fill:none;stroke-width:2.4;stroke-linejoin:round;stroke-linecap:round}
    .sd-chart .ln.req{stroke:#0b6fd0}.sd-chart .ln.atk{stroke:#f59e0b;stroke-width:2.2}.sd-chart .ln.blk{stroke:#dc2626;stroke-dasharray:5 4;stroke-width:1.8}
    .sd-chart .hv{fill:transparent}.sd-chart .hv:hover{fill:rgba(11,111,208,.06)}
    .sd-legend{display:flex;gap:18px;font-size:12px;color:#475569}
    .sd-legend span::before{content:"";display:inline-block;width:16px;height:3px;border-radius:2px;margin-right:6px;vertical-align:3px}
    .sd-legend .l-req::before{background:#0b6fd0}.sd-legend .l-atk::before{background:#f59e0b}.sd-legend .l-blk::before{background:#dc2626}
    .sd-row3{display:grid;grid-template-columns:1fr 1.15fr 1.15fr;gap:16px}.sd-row3 .sd-card{margin-bottom:16px}
    .sd-row2{display:grid;grid-template-columns:1.6fr 1fr;gap:16px}
    .sd-donut{display:flex;align-items:center;gap:16px;flex-wrap:wrap}.sd-donut svg{width:150px;height:150px;flex:none}
    .sd-donut .dn-n{font-size:22px;font-weight:700;text-anchor:middle;fill:#0f172a}.sd-donut .dn-l{font-size:9px;text-anchor:middle;fill:#64748b}
    .sd-donut ul{margin:0;padding:0;list-style:none;flex:1;min-width:150px}.sd-donut li{display:flex;align-items:center;gap:8px;font-size:12px;color:#475569;margin:5px 0}
    .sd-donut li i{width:10px;height:10px;border-radius:3px;flex:none}.sd-donut li b{margin-left:auto;color:#0f172a;font-variant-numeric:tabular-nums}
    .sd-ok{width:100%;margin:4px 0 0;font-size:12px;font-weight:600;color:#15803d}
    .sd-risk .r{margin-bottom:14px}.sd-risk .rt{display:flex;justify-content:space-between;font-size:13px}.sd-risk .rt span{color:#475569;font-variant-numeric:tabular-nums}
    .sd-risk .rb{height:7px;background:#f1f5f9;border-radius:999px;margin:6px 0 4px;overflow:hidden}.sd-risk .rb i{display:block;height:100%;border-radius:999px}
    .sd-risk small{font-size:11px;color:#94a3b8}
    .r-crit b{color:#b91c1c}.r-crit i{background:#dc2626}.r-high b{color:#b45309}.r-high i{background:#f59e0b}.r-ok b{color:#15803d}.r-ok i{background:#16a34a}
    .sd-targets{margin:0;padding:0;list-style:none;counter-reset:t}.sd-targets li{counter-increment:t;margin-bottom:12px}
    .sd-targets li div{display:flex;justify-content:space-between;gap:8px;font-size:12px}.sd-targets li div::before{content:counter(t);color:#94a3b8;width:14px;flex:none}
    .sd-targets code{flex:1;background:none;padding:0;font-size:12px;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .sd-targets span{color:#475569;font-variant-numeric:tabular-nums;white-space:nowrap}
    .sd-targets i{display:block;max-width:calc(100% - 22px);height:4px;background:#6366f1;border-radius:999px;margin:5px 0 0 22px}
    .sd-fwgrid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:8px}
    .sd-fwgrid div{background:#f8fafc;border-radius:12px;padding:14px}.sd-fwgrid b{display:block;font-size:24px;font-variant-numeric:tabular-nums}.sd-fwgrid span{font-size:12px;color:#64748b}
    .sd-tbl{width:100%;border-collapse:collapse;font-size:13px}.sd-tbl th{text-align:left;font-size:11px;color:#64748b;font-weight:600;padding:8px;border-bottom:1px solid #e5e9f0}
    .sd-tbl td{padding:9px 8px;border-bottom:1px solid #f1f5f9;vertical-align:top}.sd-tbl .sm{font-size:12px;color:#475569}
    .sd-tbl code{background:#f1f5f9;padding:2px 6px;border-radius:5px;font-size:12px}
    .sd-detail{font-size:12px;color:#475569;word-break:break-all}.sd-detail span{color:#94a3b8}
    .sd-score{display:inline-block;min-width:30px;text-align:center;background:#0a2e57;color:#fff;border-radius:6px;padding:1px 6px;font-weight:700;font-size:12px}
    .sd-pill{display:inline-block;padding:2px 9px;border-radius:999px;font-size:11px;background:#e2e8f0;color:#334155;white-space:nowrap}
    .sd-pill.med{background:#dbeafe;color:#1e40af}.sd-pill.high{background:#fef3c7;color:#92400e}.sd-pill.crit{background:#fee2e2;color:#991b1b}
    .sd-none{color:#94a3b8;text-align:center;padding:16px 0}
    .sd-foot{color:#94a3b8;font-size:12px}
    @media(max-width:1280px){.sd-kpis{grid-template-columns:repeat(3,1fr)}.sd-row3,.sd-row2,.sd-ai-cols{grid-template-columns:1fr}.sd-fwgrid{grid-template-columns:repeat(2,1fr)}}
    </style>
    <?php
}

/** การ์ดสรุปสั้นๆ บนหน้า Dashboard หลักของ WordPress */
add_action('wp_dashboard_setup', function () {
    if (!current_user_can('manage_options')) return;
    wp_add_dashboard_widget('buu_secdash_widget', 'ความปลอดภัยเว็บไซต์', function () {
        $d = buu_secdash_data();
        if ($d === null) { echo '<p>ยังไม่มีข้อมูล (ติดตั้งตัวเก็บข้อมูลบนเซิร์ฟเวอร์ก่อน)</p>'; return; }
        $lvmap = ['high' => '🔴 สูง', 'medium' => '🟠 ปานกลาง', 'low' => '🟢 ปกติ'];
        echo '<p><strong>ระดับความเสี่ยง:</strong> ' . esc_html($lvmap[$d['level']] ?? '—') . ' <span style="color:#94a3b8">· อัปเดต ' . esc_html(buu_sd_ago($d['generated'])) . '</span></p>';
        echo '<ul style="margin:0">';
        echo '<li>การโจมตีที่ตรวจพบ (24 ชม.): <strong>' . (int) $d['kpi']['attacks_24h'] . '</strong></li>';
        echo '<li>IP ต่างประเทศถูกบล็อก: <strong>' . (int) $d['kpi']['blocked_24h'] . '</strong></li>';
        echo '<li>ถูก fail2ban แบนอยู่: <strong>' . (int) $d['kpi']['banned_now'] . '</strong></li>';
        echo '</ul><p><a href="' . esc_url(admin_url('admin.php?page=buu-secdash')) . '">ดูรายละเอียดทั้งหมด →</a></p>';
    });
});
