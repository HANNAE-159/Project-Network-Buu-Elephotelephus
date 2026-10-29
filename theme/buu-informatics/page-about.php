<?php
/**
 * หน้า "เกี่ยวกับเรา"
 * - ถ้าเขียนเนื้อหาในหน้าแก้ไข (Pages > เกี่ยวกับเรา) เว็บจะแสดงเนื้อหานั้น
 * - ถ้ายังว่าง จะแสดงเนื้อหาตัวอย่างด้านล่างแทน
 */
the_post();
if (trim(get_post_field('post_content', get_the_ID())) !== '') :
    get_header();
    buu_page_hero(get_the_title(), has_excerpt() ? get_the_excerpt() : '', get_the_title());
    ?>
    <main id="main"><div class="wrap"><article class="article">
      <?php if (has_post_thumbnail()) : ?><div class="article-img"><?php the_post_thumbnail('large'); ?></div><?php endif; ?>
      <div class="entry"><?php the_content(); ?></div>
    </article></div></main>
    <?php
    get_footer();
    return;
endif;

get_header();
buu_page_hero('เกี่ยวกับคณะวิทยาการสารสนเทศ', 'สถาบันการเรียนรู้ด้านดิจิทัลริมทะเลบางแสน มหาวิทยาลัยบูรพา', 'เกี่ยวกับคณะ');
$partners = [['เกาหลีใต้'], ['สิงคโปร์'], ['อินโดนีเซีย'], ['รัสเซีย'], ['ญี่ปุ่น']];
?>
<main id="main">
<section class="section"><div class="wrap split">
  <div class="pic">
    <?php if (has_post_thumbnail()) { the_post_thumbnail('large'); } else { echo '<div class="ph-in">ใส่รูปคณะ / อาคาร / นิสิต<br>(ตั้งเป็นภาพเด่นของหน้านี้)</div>'; } ?>
  </div>
  <div>
    <h2>แนะนำคณะ</h2>
    <p>คณะวิทยาการสารสนเทศ ตั้งอยู่ที่มหาวิทยาลัยบูรพา บางแสน จังหวัดชลบุรี เปิดสอนตั้งแต่ระดับปริญญาตรีจนถึงบัณฑิตศึกษา ครอบคลุมศาสตร์ดิจิทัล</p>
    <p>พร้อมพัฒนานิสิตผ่านการลงมือทำ งานวิจัย และความร่วมมือกับอุตสาหกรรม</p>
    <div class="btn-row">
      <a class="btn btn-gold" href="<?php echo esc_url(home_url('/programs/')); ?>">ดูหลักสูตรทั้งหมด</a>
      <a class="btn btn-ghost" href="<?php echo esc_url(buu_opt('youtube') ?: '#'); ?>">ดูสื่อมัลติมีเดีย</a>
    </div>
  </div>
</div></section>

<div class="wrap"><div class="stats-box" style="margin-bottom:24px">
  <div class="stat"><b>4</b><span>หลักสูตรปริญญาตรี</span></div>
  <div class="stat"><b>1</b><span>หลักสูตรบัณฑิตศึกษา</span></div>
  <div class="stat"><b>1</b><span>หลักสูตรปริญญาเอก</span></div>
  <div class="stat"><b>10+ ปี</b><span>ความร่วมมือกับอุตสาหกรรม</span></div>
</div></div>

<section class="section"><div class="wrap">
  <div class="sec-head"><h2>วิสัยทัศน์และพันธกิจ</h2><p>ใช้ข้อความจากคณะฯ ว่าด้วยทิศทางและเป้าหมายขององค์กร</p></div>
  <div class="vmv">
    <div class="vmv-card"><h3>วิสัยทัศน์</h3><p>เป็นองค์กรชั้นนำด้านวิทยาการสารสนเทศ</p></div>
    <div class="vmv-card"><h3>พันธกิจ</h3><p>ผลิตบัณฑิตคุณภาพ สร้างงานวิจัยและนวัตกรรม</p></div>
    <div class="vmv-card"><h3>ค่านิยม</h3><p>เรียนรู้ สร้างสรรค์ รับผิดชอบ และเติบโตไปด้วยกัน</p></div>
  </div>
</div></section>

<section class="section raised"><div class="wrap">
  <div class="sec-head"><h2>เครือข่ายความร่วมมือนานาชาติ</h2><p>ด้วยเครือข่ายความร่วมมือในต่างประเทศและสถาบันพันธมิตร</p></div>
  <div class="partners">
    <?php foreach ($partners as $p) : ?><div class="partner"><b><?php echo esc_html($p[0]); ?></b><span>มหาวิทยาลัยพันธมิตร</span></div><?php endforeach; ?>
  </div>
</div></section>

<section class="section"><div class="wrap dean">
  <div class="pic"><div class="ph-in">ภาพคณบดี</div></div>
  <div>
    <h2>สารจากคณบดี</h2>
    <blockquote>“เรามุ่งสร้างพื้นที่แห่งการเรียนรู้ที่เปิดโอกาสให้นิสิตทุกคนเติบโต”</blockquote>
    <cite>คณบดีคณะวิทยาการสารสนเทศ มหาวิทยาลัยบูรพา</cite>
  </div>
</div></section>
</main>
<?php get_footer();
