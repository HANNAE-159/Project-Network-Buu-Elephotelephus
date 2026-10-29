<?php
get_header();
$is_main = is_home() && !is_paged() && !is_search() && !is_category();
$cur = is_category() ? get_queried_object_id() : 0;
if (is_search()) { $title = 'ผลการค้นหา'; $sub = 'คำค้น: ' . get_search_query(); }
elseif (is_category()) { $title = single_cat_title('', false); $sub = 'ข่าวสารในหมวดนี้'; }
else { $title = buu_c('newspage_title'); $sub = buu_c('newspage_sub'); }
$news_url = get_option('page_for_posts') ? get_permalink(get_option('page_for_posts')) : home_url('/');
buu_page_hero($title, $sub, 'ข่าวสาร');
?>
<main id="main" class="section">
  <div class="wrap">
    <?php if ($is_main && have_posts()) : the_post(); $cats = get_the_category(); ?>
      <article class="feature">
        <a class="thumb" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php has_post_thumbnail() ? the_post_thumbnail('large') : print buu_placeholder(); ?></a>
        <div class="body">
          <div class="meta"><span class="cat-tag"><?php echo esc_html($cats ? $cats[0]->name : 'ข่าวเด่น'); ?></span><time><?php echo esc_html(buu_thai_date()); ?></time></div>
          <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
          <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 34, '…')); ?></p>
          <a class="btn btn-gold btn-sm" href="<?php the_permalink(); ?>">อ่านข่าวนี้</a>
        </div>
      </article>
    <?php endif; ?>

    <div class="filters">
      <div class="pills">
        <a class="pill<?php echo (!$cur && !is_search()) ? ' on' : ''; ?>" href="<?php echo esc_url($news_url); ?>">ทั้งหมด</a>
        <?php foreach (get_categories(['hide_empty' => true]) as $c) : ?>
          <a class="pill<?php echo $cur === $c->term_id ? ' on' : ''; ?>" href="<?php echo esc_url(get_category_link($c)); ?>"><?php echo esc_html($c->name); ?></a>
        <?php endforeach; ?>
      </div>
      <form class="news-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
        <label class="screen-reader-text" for="q" style="position:absolute;left:-999px">ค้นหาข่าวสาร</label>
        <input id="q" type="search" name="s" placeholder="ค้นหาข่าวสาร" value="<?php echo esc_attr(get_search_query()); ?>">
        <input type="hidden" name="post_type" value="post">
        <button type="submit">ค้นหา</button>
      </form>
    </div>

    <?php if (have_posts()) : ?>
      <div class="news-grid"><?php while (have_posts()) : the_post(); buu_news_card(); endwhile; ?></div>
      <nav class="pagination" aria-label="เปลี่ยนหน้า"><?php echo paginate_links(['prev_text' => 'ก่อนหน้า', 'next_text' => 'ถัดไป']); // phpcs:ignore ?></nav>
    <?php else : ?>
      <p class="empty">ไม่พบข่าวสารที่ตรงกับที่ค้นหา ลองใช้คำอื่น หรือเลือกดูทั้งหมด</p>
    <?php endif; ?>

    <div class="news-cta">
      <div><h3><?php echo esc_html(buu_c('newscta_t')); ?></h3><p><?php echo esc_html(buu_c('newscta_d')); ?></p></div>
      <a class="btn btn-ghost btn-sm" href="<?php echo esc_url(buu_opt('facebook') ?: '#'); ?>">ติดตามข่าวสาร</a>
    </div>
  </div>
</main>
<?php get_footer();
