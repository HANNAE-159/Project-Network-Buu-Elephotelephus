<?php
if (!defined('ABSPATH')) exit;
define('BUU_VER', '3.7.1');
require_once __DIR__ . '/inc/content.php';
require_once __DIR__ . '/inc/import.php';
require_once __DIR__ . '/inc/secdash.php';
require_once __DIR__ . '/inc/hardening.php';

/* ---------- Setup ---------- */
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('custom-logo', ['height' => 60, 'width' => 220, 'flex-width' => true, 'flex-height' => true]);
    register_nav_menus(['primary' => 'เมนูหลัก']);
    add_image_size('buu-card', 800, 500, true);
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('buu-fonts', 'https://fonts.googleapis.com/css2?family=Anuphan:wght@500;600;700;800&family=IBM+Plex+Sans+Thai:wght@400;500;600&family=JetBrains+Mono:wght@400;500;600;700&display=swap', [], null);
    wp_enqueue_style('buu-style', get_stylesheet_uri(), [], BUU_VER);
    wp_enqueue_script('buu-main', get_template_directory_uri() . '/assets/main.js', [], BUU_VER, true);
});

/* ---------- Program post type ---------- */
add_action('init', function () {
    register_post_type('program', [
        'labels' => ['name' => 'หลักสูตร', 'singular_name' => 'หลักสูตร', 'add_new_item' => 'เพิ่มหลักสูตร', 'edit_item' => 'แก้ไขหลักสูตร', 'all_items' => 'หลักสูตรทั้งหมด'],
        'public' => true, 'has_archive' => 'programs', 'rewrite' => ['slug' => 'programs'],
        'menu_icon' => 'dashicons-welcome-learn-more', 'show_in_rest' => true,
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'],
    ]);
});

add_action('add_meta_boxes', function () {
    add_meta_box('buu_prog', 'ข้อมูลหลักสูตร', function ($post) {
        wp_nonce_field('buu_prog', 'buu_prog_n');
        $f = ['buu_en' => 'ชื่อภาษาอังกฤษ', 'buu_code' => 'อักษรย่อ (เช่น CS)', 'buu_duration' => 'ระยะเวลาและหน่วยกิต', 'buu_careers' => 'อาชีพที่รองรับ'];
        foreach ($f as $k => $l) {
            printf('<p><label><strong>%s</strong><br><input type="text" class="widefat" name="%s" value="%s"></label></p>',
                esc_html($l), esc_attr($k), esc_attr(get_post_meta($post->ID, $k, true)));
        }
    }, 'program', 'normal', 'high');
});

add_action('save_post_program', function ($id) {
    if (!isset($_POST['buu_prog_n']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['buu_prog_n'])), 'buu_prog')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $id)) return;
    foreach (['buu_en', 'buu_code', 'buu_duration', 'buu_careers'] as $k) {
        if (isset($_POST[$k])) update_post_meta($id, $k, sanitize_text_field(wp_unslash($_POST[$k])));
    }
});

/* ---------- Customizer: ข้อมูลคณะ ---------- */
function buu_defaults() {
    return [
        'apply_url' => '#', 'phone' => '038-103009-11', 'email' => 'informatics@buu.ac.th',
        'address' => 'มหาวิทยาลัยบูรพา ชลบุรี', 'facebook' => '', 'youtube' => '', 'instagram' => '', 'linkedin' => '',
    ];
}
function buu_opt($k) {
    $d = buu_defaults();
    return get_theme_mod('buu_' . $k, isset($d[$k]) ? $d[$k] : '');
}
add_action('customize_register', function ($c) {
    $c->add_section('buu', ['title' => 'ข้อมูลคณะ', 'priority' => 30]);
    $f = [
        'apply_url' => ['ลิงก์สมัครเรียน (TCAS)', 'esc_url_raw'], 'phone' => ['เบอร์โทร', 'sanitize_text_field'],
        'email' => ['อีเมล', 'sanitize_email'], 'address' => ['ที่อยู่', 'sanitize_text_field'],
        'facebook' => ['ลิงก์ Facebook', 'esc_url_raw'], 'youtube' => ['ลิงก์ YouTube', 'esc_url_raw'],
        'instagram' => ['ลิงก์ Instagram', 'esc_url_raw'], 'linkedin' => ['ลิงก์ LinkedIn', 'esc_url_raw'],
    ];
    $d = buu_defaults();
    foreach ($f as $k => $v) {
        $c->add_setting('buu_' . $k, ['default' => $d[$k], 'sanitize_callback' => $v[1]]);
        $c->add_control('buu_' . $k, ['label' => $v[0], 'section' => 'buu', 'type' => 'text']);
    }
});

/* ---------- Helpers ---------- */
function buu_thai_date($post = null, $short = false) {
    return buu_thai_date_ts(get_post_time('U', false, $post), $short);
}

/** แปลง timestamp เป็นวันที่ภาษาไทยแบบ พ.ศ. */
function buu_thai_date_ts($t, $short = false) {
    $full = ['', 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
    $abbr = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $m = (int) wp_date('n', $t);
    return (int) wp_date('j', $t) . ' ' . ($short ? $abbr[$m] : $full[$m]) . ' ' . ((int) wp_date('Y', $t) + 543);
}

/** ภาพแทนเมื่อข่าวไม่มีรูป: ภาพประกอบแบบเดียวกับในดีไซน์ Figma */
function buu_placeholder() {
    return '<div class="ph" aria-hidden="true"><div class="ph-card"><i></i><i></i><i></i><b></b></div></div>';
}

/** หาโพสต์หลักสูตรจากอักษรย่อ เช่น CS */
function buu_program_by_code($code) {
    $q = get_posts([
        'post_type' => 'program', 'posts_per_page' => 1, 'post_status' => 'publish',
        'meta_key' => 'buu_code', 'meta_value' => $code, 'fields' => 'ids',
    ]);
    return $q ? (int) $q[0] : 0;
}

/** ไอคอนเส้น (stroke) ใช้ในวงกลมฟ้า */
function buu_icon($name) {
    $p = [
        'code'   => '<path d="M8 8l-5 4 5 4M16 8l5 4-5 4M14 5l-4 14"/>',
        'ai'     => '<rect x="6" y="6" width="12" height="12" rx="2"/><path d="M9 2v4M15 2v4M9 18v4M15 18v4M2 9h4M2 15h4M18 9h4M18 15h4M10 10h4v4h-4z"/>',
        'shield' => '<path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M8.5 12l2.5 2.5 4.5-5"/>',
        'game'   => '<rect x="2" y="7" width="20" height="11" rx="5"/><path d="M7 11v3M5.5 12.5h3M15.5 12h.01M18 14h.01"/>',
        'cap'    => '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c3 2.5 9 2.5 12 0v-5M22 9v6"/>',
        'wave'   => '<path d="M2 14c2.5 0 2.5-2 5-2s2.5 2 5 2 2.5-2 5-2 2.5 2 5 2M2 19c2.5 0 2.5-2 5-2s2.5 2 5 2 2.5-2 5-2 2.5 2 5 2"/><circle cx="16" cy="6" r="3"/>',
        'pin'    => '<path d="M12 21s7-6.2 7-12a7 7 0 10-14 0c0 5.8 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/>',
        'ticket' => '<path d="M3 8a2 2 0 002-2h14a2 2 0 002 2v2a2 2 0 000 4v2a2 2 0 00-2 2H5a2 2 0 00-2-2v-2a2 2 0 000-4z"/><path d="M13 6v12" stroke-dasharray="2 2"/>',
        'factory'=> '<path d="M3 21V10l6 4V10l6 4V6l6 3v12z"/><path d="M7 17h2M12 17h2M17 17h2"/>',
        'users'  => '<circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M3 19c0-3 3-5 6-5s6 2 6 5M15 15c3 0 6 1.5 6 4"/>',
        'chart'  => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'cloud'  => '<path d="M7 18a5 5 0 01-.6-9.96A6 6 0 0118 9a4.5 4.5 0 01-.5 9z"/>',
        'rocket' => '<path d="M5 19l3-1 2 2-1 3M14 4c3 0 6 0 6 0s0 3 0 6l-7 7-6-6zM15 9h.01"/>',
        'book'   => '<path d="M4 4h6a2 2 0 012 2v14a2 2 0 00-2-2H4zM20 4h-6a2 2 0 00-2 2v14a2 2 0 012-2h6z"/>',
        'bulb'   => '<path d="M9 18h6M10 21h4M12 3a6 6 0 00-4 10.5c.8.8 1 1.5 1 2.5h6c0-1 .2-1.7 1-2.5A6 6 0 0012 3z"/>',
    ];
    $d = isset($p[$name]) ? $p[$name] : $p['code'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}

/** ภาพประกอบหน้าแรก: โน้ตบุ๊กเขียนโค้ด + การ์ดลอย (AI / ความปลอดภัย / เกม) โทนเดียวกับดีไซน์ Figma */
function buu_hero_art() {
    ?>
    <svg viewBox="0 0 520 440" role="img" aria-label="ภาพประกอบ โน้ตบุ๊กกำลังเขียนโค้ด">
      <circle cx="270" cy="220" r="190" fill="#dcebf9"/>
      <circle cx="270" cy="220" r="140" fill="#e9f3fc"/>
      <!-- laptop -->
      <g>
        <rect x="128" y="120" width="284" height="184" rx="14" fill="#fff" stroke="#0b6fd0" stroke-width="4"/>
        <rect x="146" y="138" width="248" height="148" rx="6" fill="#0a2e57"/>
        <circle cx="162" cy="152" r="4" fill="#ff6b5e"/><circle cx="176" cy="152" r="4" fill="#f5b70a"/><circle cx="190" cy="152" r="4" fill="#3fd17f"/>
        <rect x="162" y="172" width="58" height="8" rx="4" fill="#5fb6ff"/><rect x="228" y="172" width="90" height="8" rx="4" fill="#f5b70a"/>
        <rect x="178" y="190" width="120" height="8" rx="4" fill="#9fd0ff"/>
        <rect x="178" y="208" width="72" height="8" rx="4" fill="#3fd17f"/><rect x="258" y="208" width="60" height="8" rx="4" fill="#5fb6ff"/>
        <rect x="162" y="226" width="40" height="8" rx="4" fill="#5fb6ff"/>
        <rect x="162" y="250" width="104" height="18" rx="9" fill="#0b6fd0"/>
        <text x="214" y="263" text-anchor="middle" font-family="JetBrains Mono, monospace" font-size="10" fill="#fff">run ▶</text>
        <path d="M100 304h340l-18 22H118z" fill="#0b6fd0"/>
        <rect x="236" y="306" width="68" height="6" rx="3" fill="#dcebf9"/>
      </g>
      <!-- floating cards -->
      <g class="float">
        <rect x="24" y="96" width="132" height="62" rx="14" fill="#fff" filter="url(#sh)"/>
        <rect x="38" y="110" width="34" height="34" rx="10" fill="#eef5fc"/>
        <text x="55" y="132" text-anchor="middle" font-family="Anuphan, sans-serif" font-weight="800" font-size="14" fill="#0b6fd0">AI</text>
        <rect x="82" y="116" width="58" height="7" rx="3.5" fill="#0a2e57"/><rect x="82" y="130" width="40" height="7" rx="3.5" fill="#bcd9f5"/>
      </g>
      <g class="float b">
        <rect x="392" y="62" width="112" height="96" rx="16" fill="#fff" filter="url(#sh)"/>
        <path d="M448 78l22 8v16c0 14-9.5 22-22 25-12.5-3-22-11-22-25V86z" fill="#eef5fc" stroke="#0b6fd0" stroke-width="3"/>
        <path d="M438 103l7 7 12-13" fill="none" stroke="#0b6fd0" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
      </g>
      <g class="float c">
        <rect x="370" y="318" width="124" height="60" rx="14" fill="#fff" filter="url(#sh)"/>
        <text x="398" y="356" text-anchor="middle" font-family="JetBrains Mono, monospace" font-weight="700" font-size="20" fill="#f5b70a">{ }</text>
        <rect x="424" y="336" width="54" height="7" rx="3.5" fill="#0a2e57"/><rect x="424" y="350" width="36" height="7" rx="3.5" fill="#bcd9f5"/>
      </g>
      <g class="float b">
        <rect x="40" y="300" width="96" height="70" rx="14" fill="#0b6fd0" filter="url(#sh)"/>
        <path d="M60 352l14-14 10 10 18-20" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
        <circle cx="102" cy="328" r="5" fill="#f5b70a"/>
      </g>
      <circle cx="372" cy="42" r="7" fill="#f5b70a"/><circle cx="182" cy="70" r="5" fill="#12a3e8"/><circle cx="470" cy="236" r="6" fill="#12a3e8"/>
      <defs><filter id="sh" x="-20%" y="-20%" width="140%" height="160%"><feDropShadow dx="0" dy="10" stdDeviation="10" flood-color="#0a2e57" flood-opacity=".14"/></filter></defs>
    </svg>
    <?php
}

function buu_news_card() {
    $cats = get_the_category();
    $cat = $cats ? $cats[0]->name : 'ข่าวสาร';
    ?>
    <article class="news-card">
        <a class="thumb" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
            <?php has_post_thumbnail() ? the_post_thumbnail('buu-card') : print buu_placeholder(); ?>
        </a>
        <div class="body">
            <div class="meta"><span class="cat-tag"><?php echo esc_html($cat); ?></span><time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(buu_thai_date()); ?></time></div>
            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
            <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 26, '…')); ?></p>
            <a class="link-more" href="<?php the_permalink(); ?>">อ่านรายละเอียด</a>
        </div>
    </article>
    <?php
}

function buu_program_card($i = 1) {
    $id = get_the_ID();
    $en = get_post_meta($id, 'buu_en', true);
    $code = get_post_meta($id, 'buu_code', true);
    $dur = get_post_meta($id, 'buu_duration', true);
    $car = get_post_meta($id, 'buu_careers', true);
    ?>
    <a class="prog<?php echo $code === 'CS' ? ' is-cs' : ''; ?>" href="<?php the_permalink(); ?>">
        <div class="prog-top">
            <span class="code" aria-hidden="true"><?php echo esc_html($code); ?></span>
            <span class="lvl"><?php echo $code === 'CS' ? 'หลักสูตรแนะนำ' : 'ปริญญาตรี'; ?></span>
            <h3><?php the_title(); ?></h3>
            <small><?php echo esc_html($en); ?></small>
        </div>
        <div class="prog-body">
            <p><?php echo esc_html(get_the_excerpt()); ?></p>
            <dl class="prog-meta">
                <?php if ($dur) : ?><div><dt>ระยะเวลาและหน่วยกิต</dt><dd><?php echo esc_html($dur); ?></dd></div><?php endif; ?>
                <?php if ($car) : ?><div><dt>อาชีพที่รองรับ</dt><dd><?php echo esc_html($car); ?></dd></div><?php endif; ?>
            </dl>
            <span class="link-more">ดูรายละเอียดหลักสูตร</span>
        </div>
    </a>
    <?php
}

function buu_page_hero($title, $sub = '', $crumb = '', $extra = '') {
    ?>
    <section class="page-hero"><div class="wrap">
        <div>
            <div class="crumbs"><a href="<?php echo esc_url(home_url('/')); ?>">หน้าหลัก</a> / <?php echo esc_html($crumb ?: $title); ?></div>
            <h1><?php echo esc_html($title); ?></h1>
            <?php if ($sub) : ?><p><?php echo esc_html($sub); ?></p><?php endif; ?>
        </div>
        <?php echo $extra; // phpcs:ignore WordPress.Security.EscapeOutput -- trusted markup built in templates ?>
    </div></section>
    <?php
}

/* ---------- ข้อมูลตัวอย่าง (สร้างครั้งเดียวตอนเปิดใช้ธีม) ---------- */
add_action('after_switch_theme', function () {
    if (get_option('buu_seeded')) return;
    if (!current_user_can('manage_options')) return;

    $page = function ($title, $slug) {
        return wp_insert_post(['post_title' => $title, 'post_name' => $slug, 'post_status' => 'publish', 'post_type' => 'page']);
    };
    $home = $page('หน้าหลัก', 'home');
    $about = $page('เกี่ยวกับเรา', 'about');
    $news = $page('ข่าวสาร', 'news');
    $contact = $page('ติดต่อเรา', 'contact');
    update_option('show_on_front', 'page');
    update_option('page_on_front', $home);
    update_option('page_for_posts', $news);

    $progs = [
        ['วิทยาการคอมพิวเตอร์', 'Computer Science', 'CS', 'Software Engineer · AI Engineer · Cybersecurity', 'เรียนแก่นของคอมพิวเตอร์ ตั้งแต่การเขียนโปรแกรมและอัลกอริทึม ไปจนถึง AI ระบบเครือข่าย และความปลอดภัย พร้อมลงมือทำโปรเจกต์จริงทุกปี'],
        ['เทคโนโลยีสารสนเทศ', 'Information Technology', 'IT', 'IT Specialist · Network Engineer', 'เรียนรู้การออกแบบและดูแลระบบสารสนเทศ เครือข่าย และความมั่นคงปลอดภัยที่องค์กรต้องการ'],
        ['วิทยาการข้อมูล', 'Data Science', 'DS', 'Data Analyst · Data Scientist', 'วิเคราะห์ข้อมูลจริงเพื่อหาคำตอบทางธุรกิจ ด้วยสถิติ การเขียนโปรแกรม และการสื่อสารด้วยข้อมูล'],
        ['ข้อมูลและปัญญาประดิษฐ์', 'Data and Artificial Intelligence', 'AI', 'AI Engineer · ML Engineer', 'สร้างระบบปัญญาประดิษฐ์และโมเดลการเรียนรู้ของเครื่อง ตั้งแต่เตรียมข้อมูลจนถึงนำไปใช้งานจริง'],
    ];
    foreach ($progs as $i => $p) {
        wp_insert_post([
            'post_type' => 'program', 'post_status' => 'publish', 'post_title' => $p[0], 'post_excerpt' => $p[4], 'menu_order' => $i + 1,
            'post_content' => '<p>' . $p[4] . '</p><h2>สิ่งที่คุณจะได้เรียนรู้</h2><ul><li>แก้โจทย์จริงผ่านโปรเจกต์และกิจกรรมในแต่ละภาคเรียน</li><li>ฝึกงานและทำงานร่วมกับผู้เชี่ยวชาญจากภาคอุตสาหกรรม</li><li>เตรียมแฟ้มผลงานที่พร้อมใช้สมัครงาน</li></ul><p><em>แก้ไขเนื้อหานี้ได้ที่เมนู หลักสูตร ในหน้าผู้ดูแลระบบ</em></p>',
            'meta_input' => ['buu_en' => $p[1], 'buu_code' => $p[2], 'buu_duration' => '4 ปี · ไม่น้อยกว่า 120 หน่วยกิต', 'buu_careers' => $p[3]],
        ]);
    }

    $cat = [];
    foreach (['ข่าวประชาสัมพันธ์', 'รับสมัคร', 'กิจกรรม', 'ประกาศ', 'งานวิจัย'] as $c) {
        $t = wp_insert_term($c, 'category');
        $cat[$c] = is_wp_error($t) ? 0 : $t['term_id'];
    }
    $posts = [
        ['นิสิตคณะวิทยาการสารสนเทศคว้ารางวัลระดับประเทศ', 'ข่าวประชาสัมพันธ์', '2025-01-18', 'ทีม BUU Digital Innovator สร้างผลงานแพลตฟอร์มเพื่อชุมชน คว้ารางวัลชนะเลิศจากเวทีนวัตกรรมเทคโนโลยีระดับประเทศ'],
        ['เปิดรับสมัครนักศึกษาใหม่ TCAS รอบที่ 2 Quota', 'รับสมัคร', '2025-01-15', 'เปิดโอกาสสู่เส้นทางสายเทคโนโลยี สมัครเรียน 4 หลักสูตรแห่งอนาคต'],
        ['อบรมเชิงปฏิบัติการ AI & Machine Learning', 'กิจกรรม', '2025-01-10', 'เสริมทักษะด้านปัญญาประดิษฐ์ผ่านโจทย์จริง โดยผู้เชี่ยวชาญจากภาคอุตสาหกรรม'],
        ['ประกาศรายชื่อผู้มีสิทธิ์สอบสัมภาษณ์ ประจำปี 2568', 'ประกาศ', '2025-01-08', 'ตรวจสอบรายชื่อ กำหนดการ และเอกสารที่ต้องเตรียมสำหรับการสอบสัมภาษณ์'],
    ];
    foreach ($posts as $p) {
        wp_insert_post([
            'post_type' => 'post', 'post_status' => 'publish', 'post_title' => $p[0], 'post_date' => $p[2] . ' 09:00:00',
            'post_excerpt' => $p[3], 'post_content' => '<p>' . $p[3] . '</p><p>แก้ไขข่าวนี้ และใส่รูปภาพปก ได้ที่เมนู เขียนข่าว ในหน้าผู้ดูแลระบบ</p>',
            'post_category' => [$cat[$p[1]]],
        ]);
    }
    $hello = get_post(1);
    if ($hello && $hello->post_name === 'hello-world') wp_delete_post(1, true);

    $menu_id = wp_create_nav_menu('เมนูหลัก');
    if (!is_wp_error($menu_id)) {
        $items = [['หน้าหลัก', home_url('/'), 0], ['เกี่ยวกับเรา', '', $about], ['หลักสูตร', home_url('/programs/'), 0], ['ข่าวสาร', '', $news], ['ติดต่อเรา', '', $contact]];
        foreach ($items as $k => $it) {
            $args = ['menu-item-title' => $it[0], 'menu-item-status' => 'publish', 'menu-item-position' => $k + 1];
            if ($it[2]) { $args += ['menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $it[2]]; }
            else { $args += ['menu-item-type' => 'custom', 'menu-item-url' => $it[1]]; }
            wp_update_nav_menu_item($menu_id, 0, $args);
        }
        $loc = get_theme_mod('nav_menu_locations', []);
        $loc['primary'] = $menu_id;
        set_theme_mod('nav_menu_locations', $loc);
    }
    flush_rewrite_rules();
    update_option('buu_seeded', 1);
});
