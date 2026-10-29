<?php
get_header();
while (have_posts()) : the_post();
  buu_page_hero(get_the_title());
?>
<main id="main"><div class="wrap"><article class="article"><div class="entry"><?php the_content(); ?></div></article></div></main>
<?php endwhile; get_footer();
