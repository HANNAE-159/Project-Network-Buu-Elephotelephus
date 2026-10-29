<?php
/**
 * หน้าแรก: โปรโมทหลักสูตรวิทยาการคอมพิวเตอร์ (CS) มหาวิทยาลัยบูรพา
 * ข้อความทั้งหมดแก้ได้ที่ Appearance > Customize > เนื้อหาเว็บไซต์ (ดู inc/content.php)
 */
get_header();
$apply   = buu_opt('apply_url');
$cs      = buu_program_by_code('CS');
$cs_url  = $cs ? get_permalink($cs) : home_url('/programs/');
$others  = new WP_Query([
    'post_type' => 'program', 'posts_per_page' => 8, 'orderby' => 'menu_order', 'order' => 'ASC',
    'meta_query' => [['key' => 'buu_code', 'value' => 'CS', 'compare' => '!=']],
]);
$news     = new WP_Query(['post_type' => 'post', 'posts_per_page' => 3, 'ignore_sticky_posts' => true]);
$has_news = $news->have_posts();
$news_url = get_option('page_for_posts') ? get_permalink(get_option('page_for_posts')) : home_url('/');
$e = function ($k) { echo esc_html(buu_c($k)); };
$icon = function ($n) { echo buu_icon($n); }; // phpcs:ignore -- static svg from theme
?>
<main id="main">

<!-- ===== Hero ===== -->
<section class="hero">
  <div class="wrap hero-grid">
    <div class="hero-copy">
      <?php if (buu_c('hero_sticker') !== '' || buu_c('hero_tag') !== '') : ?>
        <span class="sticker"><?php if (buu_c('hero_tag') !== '') : ?><b><?php $e('hero_tag'); ?></b><?php endif; ?> <?php $e('hero_sticker'); ?></span>
      <?php endif; ?>
      <span class="eyebrow"><?php $e('hero_eyebrow'); ?></span>
      <h1><?php $e('hero_title'); ?><span><?php $e('hero_title2'); ?></span></h1>
      <p class="lead"><?php echo buu_ml('hero_lead'); // phpcs:ignore -- escaped in buu_ml ?></p>
      <div class="hero-cta">
        <a class="btn btn-blue" href="<?php echo esc_url($apply); ?>"><?php $e('hero_btn1'); ?></a>
        <?php if (buu_c('hero_btn2') !== '') : ?><a class="btn btn-ghost" href="#curriculum"><?php $e('hero_btn2'); ?></a><?php endif; ?>
      </div>
    </div>
    <div class="hero-art"><?php buu_hero_art(); ?></div>
  </div>
  <div class="wrap">
    <div class="hero-facts">
      <?php foreach ([1 => 'cap', 2 => 'wave', 3 => 'ticket'] as $i => $ic) : ?>
        <div><span class="ic"><?php $icon($ic); ?></span><p style="margin:0"><b><?php $e("fact{$i}_t"); ?></b><span><?php $e("fact{$i}_s"); ?></span></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<div class="after-hero"></div>

<!-- ===== สิ่งที่จะได้ลงมือทำ ===== -->
<?php if (buu_c('build_title') !== '') : ?>
<section class="section" id="build" style="padding-top:0">
  <div class="wrap">
    <div class="sec-head"><h2><?php $e('build_title'); ?></h2><p><?php echo buu_ml('build_sub'); // phpcs:ignore ?></p></div>
    <div class="builds">
      <?php foreach ([1 => 'code', 2 => 'ai', 3 => 'shield', 4 => 'game'] as $i => $ic) : if (buu_c("build{$i}_t") === '') continue; ?>
        <div class="build"><div class="ic-circle"><?php $icon($ic); ?></div><?php if (buu_c("build{$i}_code") !== '') : ?><code><?php $e("build{$i}_code"); ?></code><?php endif; ?><h3><?php $e("build{$i}_t"); ?></h3><p><?php echo buu_ml("build{$i}_d"); // phpcs:ignore ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===== เส้นทาง 4 ปี ===== -->
<?php if (buu_c('years_title') !== '') : ?>
<section class="section tint" id="curriculum">
  <div class="wrap">
    <div class="sec-head"><h2><?php $e('years_title'); ?></h2><p><?php echo buu_ml('years_sub'); // phpcs:ignore ?></p></div>
    <div class="years">
      <?php for ($i = 1; $i <= 4; $i++) : ?>
        <div class="year<?php echo $i === 4 ? ' last' : ''; ?>">
          <span class="year-num">ปี <?php echo (int) $i; ?></span>
          <div class="year-card">
            <h3><?php $e("year{$i}_t"); ?></h3>
            <?php if (buu_c("year{$i}_tag") !== '') : ?><span class="tag-line"><?php $e("year{$i}_tag"); ?></span><?php endif; ?>
            <ul><?php foreach (buu_lines("year{$i}_items") as $li) : ?><li><?php echo esc_html($li); ?></li><?php endforeach; ?></ul>
            <?php if ($i === 3 && buu_lines('year3_branches')) : ?>
              <div class="branches"><span>วิชาเลือกตามความสนใจ</span><?php foreach (buu_lines('year3_branches') as $b) : ?><em><?php echo esc_html($b); ?></em><?php endforeach; ?></div>
            <?php endif; ?>
          </div>
        </div>
      <?php endfor; ?>
    </div>
    <p class="note"><?php $e('years_note'); ?><?php echo buu_c('years_note') !== '' ? ' · ' : ''; ?><a href="<?php echo esc_url($cs_url); ?>">ดูรายละเอียดหลักสูตร</a></p>
  </div>
</section>
<?php endif; ?>

<!-- ===== อาชีพ ===== -->
<?php if (buu_c('careers_title') !== '') : ?>
<section class="section" id="careers">
  <div class="wrap">
    <div class="sec-head"><h2><?php $e('careers_title'); ?></h2><p><?php echo buu_ml('careers_sub'); // phpcs:ignore ?></p></div>
    <ul class="careers">
      <?php $icons = ['code', 'ai', 'chart', 'shield', 'cloud', 'game', 'book', 'rocket', 'bulb', 'users'];
      foreach (buu_lines('careers_list') as $n => $line) :
          $parts = array_map('trim', explode('|', $line, 2)); ?>
        <li><div class="ic-circle"><?php $icon($icons[$n % count($icons)]); ?></div><b><?php echo esc_html($parts[0]); ?></b><?php if (!empty($parts[1])) : ?><span><?php echo esc_html($parts[1]); ?></span><?php endif; ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>

<!-- ===== ทำไมต้องบูรพา ===== -->
<?php if (buu_c('why_title') !== '') : ?>
<section class="section tint" id="why">
  <div class="wrap">
    <div class="sec-head"><h2><?php $e('why_title'); ?></h2><p><?php echo buu_ml('why_sub'); // phpcs:ignore ?></p></div>
    <div class="why3">
      <?php foreach ([1 => 'wave', 2 => 'factory', 3 => 'users'] as $i => $ic) : if (buu_c("why{$i}_t") === '') continue; ?>
        <div class="build"><div class="ic-circle"><?php $icon($ic); ?></div><h3><?php $e("why{$i}_t"); ?></h3><p><?php echo buu_ml("why{$i}_d"); // phpcs:ignore ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===== สมัครเรียน TCAS ===== -->
<?php if (buu_c('apply_title') !== '') : ?>
<section class="section" id="apply">
  <div class="wrap">
    <div class="sec-head"><h2><?php $e('apply_title'); ?></h2><p><?php echo buu_ml('apply_sub'); // phpcs:ignore ?></p></div>
    <div class="rounds">
      <?php for ($i = 1; $i <= 4; $i++) : if (buu_c("round{$i}_t") === '') continue; ?>
        <div class="round"><h3><?php $e("round{$i}_t"); ?></h3><p><?php echo buu_ml("round{$i}_d"); // phpcs:ignore ?></p></div>
      <?php endfor; ?>
    </div>
    <div class="apply-box">
      <div><h3><?php $e('applybox_t'); ?></h3><p><?php $e('applybox_d'); ?></p></div>
      <a class="btn" href="<?php echo esc_url($apply); ?>"><?php $e('applybox_btn'); ?></a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===== คำถามที่พบบ่อย ===== -->
<?php if (buu_c('faq_title') !== '') : ?>
<section class="section tint" id="faq">
  <div class="wrap">
    <div class="sec-head"><h2><?php $e('faq_title'); ?></h2></div>
    <div class="faq">
      <?php for ($i = 1; $i <= 6; $i++) : if (buu_c("faq{$i}_q") === '') continue; ?>
        <details><summary><?php $e("faq{$i}_q"); ?></summary><p><?php echo buu_ml("faq{$i}_a"); // phpcs:ignore ?></p></details>
      <?php endfor; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===== ข่าว ===== -->
<?php if (buu_c('news_title') !== '' && $news->have_posts()) : ?>
<section class="section" id="news">
  <div class="wrap">
    <div class="sec-head row">
      <div><h2><?php $e('news_title'); ?></h2><p><?php $e('news_sub'); ?></p></div>
      <a class="link-more" href="<?php echo esc_url($news_url); ?>">ข่าวทั้งหมด</a>
    </div>
    <div class="news-grid">
      <?php while ($news->have_posts()) : $news->the_post(); buu_news_card(); endwhile; wp_reset_postdata(); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===== ข่าวจากเว็บคณะ (RSS) ===== -->
<?php $fac_news = buu_import_count() > 0 ? [] : buu_faculty_news(); if ($fac_news) : ?>
<section class="section<?php echo ($has_news && buu_c('news_title') !== '') ? ' tint' : ''; ?>" id="faculty-news">
  <div class="wrap">
    <div class="sec-head row">
      <div><h2><?php $e('feed_title'); ?></h2><p><?php $e('feed_sub'); ?></p></div>
      <a class="link-more" href="https://www.informatics.buu.ac.th/" target="_blank" rel="noopener noreferrer">ไปที่เว็บคณะ</a>
    </div>
    <div class="news-grid">
      <?php foreach ($fac_news as $fn) : ?>
        <article class="news-card">
          <a class="thumb" href="<?php echo esc_url($fn['link']); ?>" target="_blank" rel="noopener noreferrer" tabindex="-1" aria-hidden="true"><?php if (!empty($fn['img'])) : ?><img src="<?php echo esc_url($fn['img']); ?>" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer"><?php else : echo buu_placeholder(); /* phpcs:ignore -- static markup */ endif; ?></a>
          <div class="body">
            <div class="meta"><span class="cat-tag"><?php echo esc_html($fn['cat']); ?></span><?php if ($fn['date']) : ?><time datetime="<?php echo esc_attr(gmdate('c', $fn['date'])); ?>"><?php echo esc_html(buu_thai_date_ts($fn['date'])); ?></time><?php endif; ?></div>
            <h3><a href="<?php echo esc_url($fn['link']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($fn['title']); ?></a></h3>
            <p><?php echo esc_html($fn['excerpt']); ?></p>
            <a class="link-more" href="<?php echo esc_url($fn['link']); ?>" target="_blank" rel="noopener noreferrer">อ่านต่อที่เว็บคณะ</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ===== สาขาอื่นในคณะ ===== -->
<?php if (buu_c('others_title') !== '' && $others->have_posts()) : ?>
<section class="others-sec" aria-label="สาขาอื่นในคณะ">
  <div class="wrap">
    <div class="others">
      <div><h3><?php $e('others_title'); ?></h3><p><?php $e('others_sub'); ?></p></div>
      <div class="others-list">
        <?php while ($others->have_posts()) : $others->the_post(); ?>
          <a href="<?php the_permalink(); ?>"><code><?php echo esc_html(get_post_meta(get_the_ID(), 'buu_code', true)); ?></code><?php the_title(); ?></a>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

</main>
<?php get_footer();
