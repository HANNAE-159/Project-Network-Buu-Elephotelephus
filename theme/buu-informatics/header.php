<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="theme-color" content="#0a2e57">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip" href="#main">ข้ามไปยังเนื้อหา</a>
<header class="site-header">
  <div class="wrap nav-row">
    <a class="brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="หน้าหลัก วิทยาการคอมพิวเตอร์ มหาวิทยาลัยบูรพา">
      <?php if (has_custom_logo()) : the_custom_logo(); else : ?>
        <span class="brand-logo">BU<i>U</i></span>
        <span class="brand-text"><strong>วิทยาการคอมพิวเตอร์</strong>คณะวิทยาการสารสนเทศ</span>
      <?php endif; ?>
    </a>
    <button class="burger" aria-label="เปิดเมนู" aria-expanded="false" aria-controls="nav"><span></span></button>
    <nav class="main-nav" id="nav" aria-label="เมนูหลัก">
      <?php wp_nav_menu(['theme_location' => 'primary', 'container' => false, 'depth' => 1, 'fallback_cb' => function () {
          echo '<ul><li><a href="' . esc_url(home_url('/')) . '">หน้าหลัก</a></li><li><a href="' . esc_url(home_url('/#curriculum')) . '">เรียนอะไร</a></li><li><a href="' . esc_url(home_url('/#careers')) . '">อาชีพ</a></li><li><a href="' . esc_url(home_url('/programs/')) . '">หลักสูตร</a></li></ul>';
      }]); ?>
    </nav>
    <a class="btn btn-gold btn-sm nav-cta" href="<?php echo esc_url(buu_opt('apply_url')); ?>">สมัครเรียน</a>
  </div>
</header>
