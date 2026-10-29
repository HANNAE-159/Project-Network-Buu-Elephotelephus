<?php
get_header();
$q = new WP_Query(['post_type' => 'program', 'posts_per_page' => 12, 'orderby' => 'menu_order', 'order' => 'ASC']);
$extra = '<div class="mini-stats"><div><b>' . (int) $q->found_posts . '</b><span>หลักสูตร</span></div><div><b>4 ปี</b><span>ระยะเวลาเรียน</span></div><div><b>TCAS</b><span>ระบบรับสมัคร</span></div></div>';
buu_page_hero(buu_c('programs_title'), buu_c('programs_sub'), 'หลักสูตร', $extra);
?>
<main id="main">
  <section class="section">
    <div class="wrap">
      <div class="sec-head"><span class="prompt">ls programs/</span><h2>ระดับปริญญาตรี</h2><p>หลักสูตรวิทยาศาสตรบัณฑิต 4 ปี</p></div>
      <div class="progs">
        <?php while ($q->have_posts()) : $q->the_post(); buu_program_card($q->current_post + 1); endwhile; wp_reset_postdata(); ?>
      </div>
    </div>
  </section>
  <section class="section raised">
    <div class="wrap">
      <div class="sec-head"><h2>เส้นทางการสมัครเรียน</h2><p>เตรียมตัวให้พร้อม แล้วยื่นสมัครผ่านระบบ TCAS</p></div>
      <div class="steps">
        <div class="step"><h3>เลือกหลักสูตร</h3><p>อ่านรายละเอียดและคุณสมบัติของแต่ละสาขา</p></div>
        <div class="step"><h3>เตรียมเอกสาร</h3><p>ตรวจสอบรอบรับสมัครและเตรียมแฟ้มผลงาน</p></div>
        <div class="step"><h3>สมัครผ่าน TCAS</h3><p>ยื่นสมัครแล้วติดตามประกาศผล</p></div>
      </div>
    </div>
  </section>
</main>
<?php get_footer();
