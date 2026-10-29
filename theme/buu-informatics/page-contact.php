<?php
get_header();
buu_page_hero('ติดต่อเรา', 'สอบถามหลักสูตร การสมัครเรียน หรือเยี่ยมชมคณะ ทีมงานยินดีให้คำตอบ');
?>
<main id="main"><div class="wrap"><div class="article" style="max-width:980px">
  <div class="contact-cards">
    <div class="c-card"><b>ที่อยู่</b><?php echo esc_html(buu_opt('address')); ?></div>
    <div class="c-card"><b>โทรศัพท์</b><a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', buu_opt('phone'))); ?>"><?php echo esc_html(buu_opt('phone')); ?></a></div>
    <div class="c-card"><b>อีเมล</b><a href="mailto:<?php echo esc_attr(buu_opt('email')); ?>"><?php echo esc_html(buu_opt('email')); ?></a></div>
  </div>
  <div class="entry"><?php while (have_posts()) : the_post(); the_content(); endwhile; ?></div>
</div></div></main>
<?php get_footer();
