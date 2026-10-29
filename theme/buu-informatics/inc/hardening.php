<?php
/**
 * เสริมความปลอดภัยระดับธีม
 * - ปิดการไล่หาชื่อผู้ใช้ (user enumeration) สำหรับคนที่ไม่ได้ล็อกอิน: ?author=N, /author/ชื่อ/, REST /wp/v2/users, sitemap ผู้ใช้, oEmbed
 * - ซ่อนเวอร์ชัน WordPress, ปิด XML-RPC, ข้อความล็อกอินผิดแบบไม่บอกว่าชื่อหรือรหัสผิด
 * คนที่ล็อกอินแล้ว (ทีมงาน) ใช้งานได้ตามปกติ เพราะหน้าแก้ไขบทความต้องใช้ API รายชื่อผู้ใช้
 */
if (!defined('ABSPATH')) exit;

/* ?author=1 → 403 ก่อน WordPress จะ redirect ไปหน้าที่มีชื่อผู้ใช้ */
add_action('init', function () {
    if (!is_admin() && !is_user_logged_in() && isset($_GET['author'])) {   // phpcs:ignore WordPress.Security.NonceVerification -- อ่านแค่ว่ามีพารามิเตอร์หรือไม่
        status_header(403);
        nocache_headers();
        exit('Forbidden');
    }
}, 0);

/* /author/ชื่อ/ → 404 */
add_action('template_redirect', function () {
    if (is_author() && !is_user_logged_in()) {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
    }
});
add_filter('author_link', function ($link) { return is_user_logged_in() ? $link : home_url('/'); });

/* REST API: ซ่อนรายชื่อผู้ใช้จากคนที่ไม่ได้ล็อกอิน */
add_filter('rest_endpoints', function ($endpoints) {
    if (!is_user_logged_in()) {
        foreach (array_keys($endpoints) as $route) {
            if (strpos($route, '/wp/v2/users') === 0) unset($endpoints[$route]);
        }
    }
    return $endpoints;
});

/* sitemap และ oEmbed ไม่เปิดเผยผู้เขียน */
add_filter('wp_sitemaps_add_provider', function ($provider, $name) { return $name === 'users' ? false : $provider; }, 10, 2);
add_filter('oembed_response_data', function ($data) { unset($data['author_name'], $data['author_url']); return $data; });

/* ซ่อนเวอร์ชันและปิด XML-RPC (nginx ปิดไว้อีกชั้นแล้ว) */
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', '__return_empty_string');
add_filter('xmlrpc_enabled', '__return_false');
add_filter('wp_headers', function ($h) { unset($h['X-Pingback']); return $h; });

/* ล็อกอินผิด: ไม่บอกว่าชื่อผู้ใช้มีอยู่จริงหรือไม่ */
add_filter('login_errors', function () { return 'ข้อมูลเข้าสู่ระบบไม่ถูกต้อง'; });
