<?php
get_header();
while (have_posts()) : the_post();
  $en = get_post_meta(get_the_ID(), 'buu_en', true);
  $dur = get_post_meta(get_the_ID(), 'buu_duration', true);
  $car = get_post_meta(get_the_ID(), 'buu_careers', true);
  buu_page_hero(get_the_title(), $en, 'หลักสูตร');
?>
<main id="main"><div class="wrap"><article class="article">
  <div class="facts">
    <div class="fact"><b>ระดับ</b>ปริญญาตรี</div>
    <?php if ($dur) : ?><div class="fact"><b>ระยะเวลาและหน่วยกิต</b><?php echo esc_html($dur); ?></div><?php endif; ?>
    <?php if ($car) : ?><div class="fact"><b>อาชีพที่รองรับ</b><?php echo esc_html($car); ?></div><?php endif; ?>
  </div>
  <div class="entry"><?php the_content(); ?></div>
  <div class="btn-row">
    <a class="btn btn-gold" href="<?php echo esc_url(buu_opt('apply_url')); ?>">สมัครเรียนหลักสูตรนี้</a>
    <a class="link-more" href="<?php echo esc_url(home_url('/programs/')); ?>" style="align-self:center">กลับไปหลักสูตรทั้งหมด</a>
  </div>
</article></div></main>
<?php endwhile; get_footer();
