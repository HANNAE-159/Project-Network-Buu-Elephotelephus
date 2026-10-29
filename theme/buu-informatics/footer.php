<?php $news_page = (int) get_option('page_for_posts'); ?>
<section class="cta">
  <div class="wrap">
    <div>
      <h2><?php echo esc_html(buu_c('cta_title')); ?></h2>
      <p><?php echo esc_html(buu_c('cta_sub')); ?></p>
    </div>
    <a class="btn btn-gold" href="<?php echo esc_url(buu_opt('apply_url')); ?>"><?php echo esc_html(buu_c('cta_btn')); ?></a>
  </div>
</section>
<footer class="site-footer">
  <div class="wrap foot-grid">
    <div>
      <div class="foot-name"><?php echo esc_html(buu_c('foot_name')); ?></div>
      <p><?php echo buu_ml('foot_desc'); // phpcs:ignore ?></p>
      <div class="social">
        <?php foreach (['facebook' => ['f', 'Facebook'], 'youtube' => ['yt', 'YouTube'], 'instagram' => ['ig', 'Instagram'], 'linkedin' => ['in', 'LinkedIn']] as $k => $s) :
            $u = buu_opt($k); if (!$u) continue; ?>
          <a href="<?php echo esc_url($u); ?>" aria-label="<?php echo esc_attr($s[1]); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($s[0]); ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <div>
      <h4>หลักสูตร CS</h4>
      <p><a href="<?php echo esc_url(home_url('/#curriculum')); ?>">เรียนอะไรบ้าง</a></p>
      <p><a href="<?php echo esc_url(home_url('/#careers')); ?>">อาชีพหลังจบ</a></p>
      <p><a href="<?php echo esc_url(home_url('/#apply')); ?>">สมัครเรียน</a></p>
    </div>
    <div>
      <h4>คณะ</h4>
      <p><a href="<?php echo esc_url(home_url('/programs/')); ?>">หลักสูตรทั้งหมด</a></p>
      <p><a href="<?php echo esc_url($news_page ? get_permalink($news_page) : home_url('/')); ?>">ข่าวสาร</a></p>
      <p><?php echo esc_html(buu_opt('address')); ?></p>
    </div>
    <div>
      <h4>ติดต่อ</h4>
      <p><a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', buu_opt('phone'))); ?>"><?php echo esc_html(buu_opt('phone')); ?></a></p>
      <p><a href="mailto:<?php echo esc_attr(buu_opt('email')); ?>"><?php echo esc_html(buu_opt('email')); ?></a></p>
    </div>
  </div>
  <div class="wrap copy">© <?php echo esc_html(wp_date('Y')); ?> Computer Science, Faculty of Informatics, Burapha University</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
