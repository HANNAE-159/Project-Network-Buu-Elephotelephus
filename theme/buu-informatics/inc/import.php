<?php
/**
 * นำเข้าข่าวจากเว็บคณะ (REST API) เป็นโพสต์ของ WordPress → เก็บใน MySQL และแสดงในหน้า "ข่าวสาร"
 *
 * - ทำงานอัตโนมัติทุกชั่วโมง (WP-Cron) และกดนำเข้าเองได้ที่ Tools > นำเข้าข่าวจากคณะ
 * - ไม่นำเข้าซ้ำ: จำลิงก์ต้นฉบับไว้ใน post meta "_buu_src"
 * - เนื้อหาถูกกรองด้วย wp_kses_post (ตัดสคริปต์/โค้ดอันตราย) และมีลิงก์ต้นฉบับท้ายข่าว
 * - ตั้งจำนวนข่าวที่ Customize > เนื้อหาเว็บไซต์ > หน้าแรก: ข่าวจากเว็บคณะ (0 = ปิดการนำเข้า)
 */
if (!defined('ABSPATH')) exit;

const BUU_IMPORT_HOOK = 'buu_import_faculty_news';
const BUU_IMPORT_CAT  = 'ข่าวจากคณะ';
const BUU_IMPORT_MAX_NEW = 25;   // นำเข้าใหม่ได้สูงสุดต่อรอบ (กันหน้าเว็บค้างตอนโหลดรูปจำนวนมาก) ที่เหลือจะเข้าในรอบถัดไป

/** จำนวนข่าวที่จะนำเข้าต่อรอบ (0 = ปิด) */
function buu_import_count() {
    return max(0, min(100, (int) buu_c('import_count')));   // API ของ WordPress ให้ได้สูงสุด 100 ข่าวต่อครั้ง
}

/* ---------- ตั้งเวลา ---------- */
add_action('init', function () {
    $on = buu_import_count() > 0 && buu_c('feed_api') !== '';
    $next = wp_next_scheduled(BUU_IMPORT_HOOK);
    if ($on && !$next) {
        wp_schedule_event(time() + 60, 'hourly', BUU_IMPORT_HOOK);
    } elseif (!$on && $next) {
        wp_clear_scheduled_hook(BUU_IMPORT_HOOK);
    }
});
add_action('switch_theme', function () { wp_clear_scheduled_hook(BUU_IMPORT_HOOK); });
add_action(BUU_IMPORT_HOOK, function () { buu_import_news(); });

/**
 * นำเข้าข่าว คืนค่า ['new' => int, 'skipped' => int, 'error' => string]
 */
function buu_import_news() {
    $result = ['new' => 0, 'skipped' => 0, 'pending' => 0, 'error' => ''];
    $n = buu_import_count();
    $api = buu_c('feed_api');
    if ($n === 0 || $api === '' || !preg_match('#^https?://#i', $api)) {
        $result['error'] = 'ปิดการนำเข้าอยู่ (จำนวนข่าวเป็น 0 หรือไม่มีลิงก์ API)';
        return $result;
    }
    if (get_transient('buu_import_lock')) {
        $result['error'] = 'กำลังนำเข้าอยู่ ลองใหม่อีกครู่';
        return $result;
    }
    set_transient('buu_import_lock', 1, 10 * MINUTE_IN_SECONDS);
    if (function_exists('set_time_limit')) @set_time_limit(300);

    $url = add_query_arg([
        'per_page' => $n,
        '_embed'   => 'wp:featuredmedia,wp:term',
        '_fields'  => 'date_gmt,link,title,content,excerpt,_links,_embedded',
    ], $api);
    $res = wp_safe_remote_get($url, ['timeout' => 30, 'redirection' => 2, 'user-agent' => 'BUU-CS-Site/1.0']);
    if (is_wp_error($res) || (int) wp_remote_retrieve_response_code($res) !== 200) {
        delete_transient('buu_import_lock');
        $result['error'] = 'ดึงข้อมูลจากเว็บคณะไม่สำเร็จ';
        return $result;
    }
    $data = json_decode(wp_remote_retrieve_body($res), true);
    if (!is_array($data)) {
        delete_transient('buu_import_lock');
        $result['error'] = 'ข้อมูลจากเว็บคณะไม่อยู่ในรูปแบบที่อ่านได้';
        return $result;
    }

    $cat = term_exists(BUU_IMPORT_CAT, 'category');
    if (!$cat) $cat = wp_insert_term(BUU_IMPORT_CAT, 'category');
    $cat_id = is_array($cat) ? (int) $cat['term_id'] : (int) $cat;
    $author = buu_import_author();

    foreach ($data as $p) {                          // ใหม่ไปเก่า ถ้าเกินโควตาต่อรอบ ข่าวใหม่จะได้เข้าก่อน
        if (!is_array($p)) continue;
        $link = esc_url_raw(isset($p['link']) ? (string) $p['link'] : '', ['http', 'https']);
        if ($link === '') continue;

        $exists = get_posts([
            'post_type' => 'post', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids',
            'meta_key' => '_buu_src', 'meta_value' => $link, 'no_found_rows' => true,
        ]);
        if ($exists) { $result['skipped']++; continue; }
        if ($result['new'] >= BUU_IMPORT_MAX_NEW) { $result['pending']++; continue; }

        $title   = buu_plain(isset($p['title']['rendered']) ? $p['title']['rendered'] : '');
        $content = wp_kses_post(isset($p['content']['rendered']) ? (string) $p['content']['rendered'] : '');
        $excerpt = wp_trim_words(buu_plain(isset($p['excerpt']['rendered']) ? $p['excerpt']['rendered'] : ''), 40, '…');
        if ($title === '') continue;
        $content .= "\n\n" . '<p class="buu-source"><em>ที่มา: <a href="' . esc_url($link) . '" target="_blank" rel="noopener noreferrer">เว็บไซต์คณะวิทยาการสารสนเทศ มหาวิทยาลัยบูรพา</a></em></p>';

        $date_gmt = !empty($p['date_gmt']) ? gmdate('Y-m-d H:i:s', (int) strtotime($p['date_gmt'] . ' UTC')) : current_time('mysql', true);
        $cats = [$cat_id];
        if (!empty($p['_embedded']['wp:term'][0][0]['name'])) {
            $src_cat = buu_plain($p['_embedded']['wp:term'][0][0]['name']);
            if ($src_cat !== '') {
                $t = term_exists($src_cat, 'category');
                if (!$t) $t = wp_insert_term($src_cat, 'category');
                if (!is_wp_error($t)) $cats[] = is_array($t) ? (int) $t['term_id'] : (int) $t;
            }
        }

        $post_id = wp_insert_post([
            'post_type'     => 'post',
            'post_status'   => 'publish',
            'post_title'    => $title,
            'post_content'  => $content,
            'post_excerpt'  => $excerpt,
            'post_date_gmt' => $date_gmt,
            'post_date'     => get_date_from_gmt($date_gmt),
            'post_author'   => $author,
            'post_category' => array_values(array_unique(array_filter($cats))),
            'comment_status'=> 'closed',
            'meta_input'    => ['_buu_src' => $link],
        ], true);
        if (is_wp_error($post_id) || !$post_id) continue;

        $img = buu_import_image_url($p);
        if ($img !== '') buu_import_set_thumbnail($post_id, $img, $title);
        $result['new']++;
    }

    delete_transient('buu_import_lock');
    update_option('buu_import_last', ['time' => time(), 'new' => $result['new'], 'skipped' => $result['skipped'], 'pending' => $result['pending']], false);
    return $result;
}

/** ผู้เขียนของข่าวที่นำเข้า: แอดมินคนแรกของเว็บ */
function buu_import_author() {
    $admins = get_users(['role' => 'administrator', 'number' => 1, 'orderby' => 'ID', 'fields' => 'ID']);
    return $admins ? (int) $admins[0] : 0;
}

/** หา URL รูปปกจากข้อมูล API (รับเฉพาะ https) */
function buu_import_image_url($p) {
    $m = isset($p['_embedded']['wp:featuredmedia'][0]) ? $p['_embedded']['wp:featuredmedia'][0] : null;
    if (!is_array($m)) return '';
    $img = '';
    foreach (['large', 'medium_large', 'full'] as $size) {
        if (!empty($m['media_details']['sizes'][$size]['source_url'])) { $img = $m['media_details']['sizes'][$size]['source_url']; break; }
    }
    if ($img === '' && !empty($m['source_url'])) $img = $m['source_url'];
    return preg_match('#^https://#i', (string) $img) ? esc_url_raw($img, ['https']) : '';
}

/** ดาวน์โหลดรูปปกเข้า Media Library แล้วตั้งเป็นรูปเด่นของโพสต์ */
function buu_import_set_thumbnail($post_id, $url, $title) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $tmp = download_url($url, 20);                     // ใช้ wp_safe_remote_get ภายใน
    if (is_wp_error($tmp)) return;
    $name = sanitize_file_name(wp_basename((string) wp_parse_url($url, PHP_URL_PATH)));
    if (!preg_match('/\.(jpe?g|png|gif|webp)$/i', $name)) $name .= '.jpg';
    $att = media_handle_sideload(['name' => $name, 'tmp_name' => $tmp], $post_id, $title);  // WordPress ตรวจชนิดไฟล์ให้
    if (is_wp_error($att)) { @unlink($tmp); return; }
    set_post_thumbnail($post_id, $att);
}

/* ---------- หน้า Tools > นำเข้าข่าวจากคณะ (กดนำเข้าเองได้) ---------- */
add_action('admin_menu', function () {
    add_management_page('นำเข้าข่าวจากคณะ', 'นำเข้าข่าวจากคณะ', 'manage_options', 'buu-import', 'buu_import_page');
});

function buu_import_page() {
    if (!current_user_can('manage_options')) return;
    $msg = '';
    if (isset($_POST['buu_import_now'])) {
        check_admin_referer('buu_import_now');
        $r = buu_import_news();
        $msg = $r['error'] !== ''
            ? '<div class="notice notice-error"><p>' . esc_html($r['error']) . '</p></div>'
            : '<div class="notice notice-success"><p>' . esc_html(sprintf('นำเข้าข่าวใหม่ %d ข่าว · ข้ามข่าวที่มีอยู่แล้ว %d ข่าว', $r['new'], $r['skipped'])
              . ($r['pending'] ? sprintf(' · เหลืออีก %d ข่าว กด "นำเข้าตอนนี้" อีกครั้ง หรือรอรอบอัตโนมัติ', $r['pending']) : '')) . '</p></div>';
    }
    $last = get_option('buu_import_last');
    $next = wp_next_scheduled(BUU_IMPORT_HOOK);
    ?>
    <div class="wrap">
      <h1>นำเข้าข่าวจากคณะ</h1>
      <?php echo $msg; // phpcs:ignore -- escaped above ?>
      <p>ดึงข่าวล่าสุดจากเว็บไซต์คณะวิทยาการสารสนเทศ แล้วบันทึกเป็นโพสต์ในหมวด "<?php echo esc_html(BUU_IMPORT_CAT); ?>" ข่าวที่เคยนำเข้าแล้วจะไม่ถูกนำเข้าซ้ำ</p>
      <table class="form-table" role="presentation">
        <tr><th>จำนวนข่าวต่อรอบ</th><td><?php echo (int) buu_import_count(); ?> <span class="description">(แก้ที่ Customize → เนื้อหาเว็บไซต์ → หน้าแรก: ข่าวจากเว็บคณะ · 0 = ปิด)</span></td></tr>
        <tr><th>นำเข้าล่าสุด</th><td><?php echo is_array($last) ? esc_html(wp_date('j M Y H:i', $last['time']) . sprintf(' · ใหม่ %d · ข้าม %d', $last['new'], $last['skipped'])) : 'ยังไม่เคย'; ?></td></tr>
        <tr><th>รอบถัดไป (อัตโนมัติ)</th><td><?php echo $next ? esc_html(wp_date('j M Y H:i', $next)) : 'ไม่ได้ตั้งเวลา'; ?></td></tr>
      </table>
      <form method="post"><?php wp_nonce_field('buu_import_now'); ?>
        <p><button class="button button-primary" name="buu_import_now" value="1">นำเข้าตอนนี้</button></p>
      </form>
    </div>
    <?php
}
