<?php
get_header();
while (have_posts()) : the_post();
  $cats = get_the_category();
  buu_page_hero(get_the_title(), buu_thai_date() . ($cats ? ' · ' . $cats[0]->name : ''), 'ข่าวสาร');
?>
<main id="main"><div class="wrap"><article class="article">
  <?php if (has_post_thumbnail()) : ?><div class="article-img"><?php the_post_thumbnail('large'); ?></div><?php endif; ?>
  <div class="entry"><?php the_content(); ?></div>
  <div class="btn-row"><a class="link-more" href="<?php echo esc_url(get_option('page_for_posts') ? get_permalink(get_option('page_for_posts')) : home_url('/')); ?>">กลับไปข่าวสารทั้งหมด</a></div>
</article></div></main>
<?php endwhile; get_footer();
