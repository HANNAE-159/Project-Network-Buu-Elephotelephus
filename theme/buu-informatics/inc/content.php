<?php
/**
 * ข้อความทั้งหมดของเว็บที่แก้ได้จากหน้าแอดมิน
 * Appearance > Customize > "เนื้อหาเว็บไซต์"
 *
 * - ช่อง "รายการ" ใส่ได้ 1 บรรทัดต่อ 1 รายการ
 * - ลบ "หัวข้อ" ของส่วนไหนออก ส่วนนั้นจะถูกซ่อนจากหน้าแรก
 */
if (!defined('ABSPATH')) exit;

function buu_content_schema() {
    return [
        'buu_c_hero' => ['หน้าแรก: ส่วนหัว', [
            'hero_tag'     => ['ป้ายสีทอง', 'text', 'TCAS'],
            'hero_sticker' => ['ข้อความข้างป้าย', 'text', 'เปิดรับนักศึกษาใหม่ หลักสูตร 4 ปี'],
            'hero_eyebrow' => ['ข้อความเหนือหัวข้อ', 'text', 'คณะวิทยาการสารสนเทศ มหาวิทยาลัยบูรพา'],
            'hero_title'   => ['หัวข้อใหญ่ (บรรทัด 1)', 'text', 'เรียนวิทยาการคอมพิวเตอร์'],
            'hero_title2'  => ['หัวข้อใหญ่ (บรรทัด 2 สีฟ้า)', 'text', 'สร้างสิ่งที่ยังไม่มีใครสร้าง'],
            'hero_lead'    => ['คำอธิบาย', 'textarea', 'ตั้งแต่โค้ดบรรทัดแรก ไปจนถึงสร้าง AI ทำเกม และป้องกันระบบจากแฮกเกอร์ เรียนจริง ทำจริง ที่มหาวิทยาลัยริมทะเลบางแสน'],
            'hero_btn1'    => ['ปุ่มหลัก (ลิงก์ไปหน้าสมัคร)', 'text', 'สมัครเรียน'],
            'hero_btn2'    => ['ปุ่มรอง (เลื่อนไปส่วนเรียนอะไร)', 'text', 'ดูว่าเรียนอะไรบ้าง'],
            'fact1_t' => ['กล่องข้อมูล 1: หัวข้อ', 'text', 'วิทยาศาสตรบัณฑิต'],
            'fact1_s' => ['กล่องข้อมูล 1: รายละเอียด', 'text', 'หลักสูตร 4 ปี'],
            'fact2_t' => ['กล่องข้อมูล 2: หัวข้อ', 'text', 'เรียนที่บางแสน'],
            'fact2_s' => ['กล่องข้อมูล 2: รายละเอียด', 'text', 'ชลบุรี ใกล้พื้นที่ EEC'],
            'fact3_t' => ['กล่องข้อมูล 3: หัวข้อ', 'text', 'สมัครผ่าน TCAS'],
            'fact3_s' => ['กล่องข้อมูล 3: รายละเอียด', 'text', 'เปิดรับหลายรอบต่อปี'],
        ]],
        'buu_c_build' => ['หน้าแรก: เรียนแล้วได้ทำอะไร', [
            'build_title' => ['หัวข้อส่วน (ลบเพื่อซ่อน)', 'text', 'เรียน CS แล้วได้ทำอะไรบ้าง?'],
            'build_sub'   => ['คำอธิบายส่วน', 'textarea', 'ไม่ใช่แค่นั่งฟังทฤษฎี ทุกวิชาหลักมีงานให้ลงมือเขียนโค้ดจริง จบ 4 ปีคุณจะมีผลงานของตัวเองไว้โชว์'],
            'build1_code' => ['การ์ด 1: โค้ดเล็ก', 'text', 'app.run()'],
            'build1_t'    => ['การ์ด 1: หัวข้อ', 'text', 'สร้างแอปและเว็บ'],
            'build1_d'    => ['การ์ด 1: รายละเอียด', 'textarea', 'ออกแบบและเขียนแอปตั้งแต่หน้าจอจนถึงฐานข้อมูล แล้วปล่อยให้คนใช้งานจริง'],
            'build2_code' => ['การ์ด 2: โค้ดเล็ก', 'text', 'model.fit()'],
            'build2_t'    => ['การ์ด 2: หัวข้อ', 'text', 'สอนให้ AI คิดเป็น'],
            'build2_d'    => ['การ์ด 2: รายละเอียด', 'textarea', 'เตรียมข้อมูล เทรนโมเดล AI และวัดผลว่าโมเดลของคุณฉลาดแค่ไหน'],
            'build3_code' => ['การ์ด 3: โค้ดเล็ก', 'text', 'sudo ufw enable'],
            'build3_t'    => ['การ์ด 3: หัวข้อ', 'text', 'ป้องกันระบบ'],
            'build3_d'    => ['การ์ด 3: รายละเอียด', 'textarea', 'ตั้งเซิร์ฟเวอร์ ตั้งไฟร์วอลล์ และหาช่องโหว่ก่อนที่แฮกเกอร์จะเจอ'],
            'build4_code' => ['การ์ด 4: โค้ดเล็ก', 'text', 'player.jump()'],
            'build4_t'    => ['การ์ด 4: หัวข้อ', 'text', 'สร้างเกมและกราฟิก'],
            'build4_d'    => ['การ์ด 4: รายละเอียด', 'textarea', 'ใช้คณิตศาสตร์และอัลกอริทึมสร้างโลกที่ขยับได้และเล่นได้จริง'],
        ]],
        'buu_c_years' => ['หน้าแรก: เส้นทาง 4 ปี', [
            'years_title' => ['หัวข้อส่วน (ลบเพื่อซ่อน)', 'text', '4 ปี จากโค้ดบรรทัดแรกสู่งานจริง'],
            'years_sub'   => ['คำอธิบายส่วน', 'textarea', 'ปีแรกปูพื้นให้แน่น ปีสามเลือกแขนงที่ชอบ ปีสุดท้ายทำโปรเจกต์และฝึกงานกับบริษัทจริง'],
            'year1_t' => ['ปี 1: หัวข้อ', 'text', 'ปูพื้นฐาน'],
            'year1_tag' => ['ปี 1: คำโปรย', 'text', '"Hello, World!"'],
            'year1_items' => ['ปี 1: รายวิชา (1 บรรทัดต่อ 1 วิชา)', 'textarea', "การเขียนโปรแกรมเบื้องต้น\nคณิตศาสตร์ดิสครีตและตรรกะ\nพื้นฐานระบบคอมพิวเตอร์"],
            'year2_t' => ['ปี 2: หัวข้อ', 'text', 'สร้างให้เป็น'],
            'year2_tag' => ['ปี 2: คำโปรย', 'text', 'เขียนให้ถูก แล้วเขียนให้ดี'],
            'year2_items' => ['ปี 2: รายวิชา (1 บรรทัดต่อ 1 วิชา)', 'textarea', "โครงสร้างข้อมูล\nการเขียนโปรแกรมเชิงวัตถุ\nระบบฐานข้อมูล\nสถาปัตยกรรมคอมพิวเตอร์"],
            'year3_t' => ['ปี 3: หัวข้อ', 'text', 'เลือกทางของคุณ'],
            'year3_tag' => ['ปี 3: คำโปรย', 'text', 'ลงลึกในสิ่งที่ชอบ'],
            'year3_items' => ['ปี 3: รายวิชา (1 บรรทัดต่อ 1 วิชา)', 'textarea', "การออกแบบอัลกอริทึม\nระบบปฏิบัติการและเครือข่าย\nวิศวกรรมซอฟต์แวร์"],
            'year3_branches' => ['ปี 3: แขนงวิชาเลือก (1 บรรทัดต่อ 1 แขนง)', 'textarea', "AI & Data\nSoftware\nNetwork & Security\nGame & Graphics"],
            'year4_t' => ['ปี 4: หัวข้อ', 'text', 'ลงสนามจริง'],
            'year4_tag' => ['ปี 4: คำโปรย', 'text', 'พร้อมทำงาน'],
            'year4_items' => ['ปี 4: รายวิชา (1 บรรทัดต่อ 1 วิชา)', 'textarea', "โครงงานวิทยาการคอมพิวเตอร์\nสหกิจศึกษาหรือฝึกงานในบริษัท\nสัมมนาเทคโนโลยีใหม่"],
            'years_note' => ['หมายเหตุใต้ส่วน', 'text', 'รายวิชาข้างต้นเป็นภาพรวมโดยสรุป'],
        ]],
        'buu_c_careers' => ['หน้าแรก: อาชีพ', [
            'careers_title' => ['หัวข้อส่วน (ลบเพื่อซ่อน)', 'text', 'จบแล้วไปทางไหนได้บ้าง?'],
            'careers_sub'   => ['คำอธิบายส่วน', 'textarea', 'ทักษะ CS ใช้ได้ในเกือบทุกอุตสาหกรรม ตั้งแต่ธนาคาร โรงงาน สตาร์ทอัพ ไปจนถึงบริษัทเทคโนโลยีระดับโลก'],
            'careers_list'  => ['รายการอาชีพ (1 บรรทัดต่อ 1 อาชีพ รูปแบบ: ชื่อ | คำอธิบาย)', 'textarea', "Software Engineer | พัฒนาแอป เว็บ และระบบหลังบ้าน\nAI / ML Engineer | สร้างและนำโมเดล AI ไปใช้งานจริง\nData Scientist | วิเคราะห์ข้อมูลเพื่อหาคำตอบให้ธุรกิจ\nCybersecurity | ป้องกันระบบและรับมือการโจมตี\nCloud / DevOps | ดูแลระบบให้ทำงานได้ตลอดเวลา\nGame Developer | สร้างเกมและประสบการณ์แบบโต้ตอบ\nนักวิจัย / เรียนต่อ | ต่อโทและเอก ทั้งในและต่างประเทศ\nเจ้าของสตาร์ทอัพ | เปลี่ยนไอเดียให้เป็นผลิตภัณฑ์"],
        ]],
        'buu_c_why' => ['หน้าแรก: ทำไมต้องบูรพา', [
            'why_title' => ['หัวข้อส่วน (ลบเพื่อซ่อน)', 'text', 'ทำไมต้องเรียน CS ที่บูรพา?'],
            'why_sub'   => ['คำอธิบายส่วน', 'textarea', 'เพราะ 4 ปีในมหาวิทยาลัยควรเป็นช่วงเวลาที่ดีที่สุดช่วงหนึ่ง'],
            'why1_t' => ['ข้อ 1: หัวข้อ', 'text', 'เรียนริมทะเล'],
            'why1_d' => ['ข้อ 1: รายละเอียด', 'textarea', 'มหาวิทยาลัยอยู่ติดชายหาดบางแสน เขียนโค้ดเสร็จแล้วไปดูพระอาทิตย์ตกได้ในไม่กี่นาที'],
            'why2_t' => ['ข้อ 2: หัวข้อ', 'text', 'ใกล้แหล่งงานจริง'],
            'why2_d' => ['ข้อ 2: รายละเอียด', 'textarea', 'ชลบุรีอยู่ในเขตพัฒนาพิเศษภาคตะวันออก (EEC) มีทั้งโรงงานอัจฉริยะและบริษัทเทคโนโลยี เปิดโอกาสฝึกงานและทำงาน'],
            'why3_t' => ['ข้อ 3: หัวข้อ', 'text', 'เพื่อนสายเดียวกันทั้งคณะ'],
            'why3_d' => ['ข้อ 3: รายละเอียด', 'textarea', 'คณะวิทยาการสารสนเทศรวมสาขาดิจิทัลไว้ด้วยกัน มีเพื่อน รุ่นพี่ และอาจารย์ที่ชอบเรื่องเดียวกับคุณ'],
        ]],
        'buu_c_apply' => ['หน้าแรก: สมัครเรียน TCAS', [
            'apply_title' => ['หัวข้อส่วน (ลบเพื่อซ่อน)', 'text', 'สมัครเรียนผ่าน TCAS'],
            'apply_sub'   => ['คำอธิบายส่วน', 'textarea', 'เลือกรอบที่เหมาะกับคุณ คุณสมบัติและกำหนดการแต่ละปีอาจเปลี่ยน ตรวจสอบที่ mytcas.com และประกาศของคณะทุกครั้ง'],
            'round1_t' => ['รอบ 1: ชื่อ', 'text', 'Portfolio'],
            'round1_d' => ['รอบ 1: รายละเอียด', 'textarea', 'ยื่นแฟ้มสะสมผลงาน เหมาะกับคนที่มีผลงานหรือเคยแข่งด้านคอมพิวเตอร์'],
            'round2_t' => ['รอบ 2: ชื่อ', 'text', 'Quota'],
            'round2_d' => ['รอบ 2: รายละเอียด', 'textarea', 'โควตาพื้นที่และโครงการพิเศษของมหาวิทยาลัย'],
            'round3_t' => ['รอบ 3: ชื่อ', 'text', 'Admission'],
            'round3_d' => ['รอบ 3: รายละเอียด', 'textarea', 'ใช้คะแนนสอบกลางตามสัดส่วนที่หลักสูตรกำหนด'],
            'round4_t' => ['รอบ 4: ชื่อ', 'text', 'Direct Admission'],
            'round4_d' => ['รอบ 4: รายละเอียด', 'textarea', 'รอบรับตรงสำหรับที่นั่งที่เหลือ'],
            'applybox_t'   => ['กล่องสมัคร: หัวข้อ', 'text', 'พร้อมเริ่มต้นสายคอมแล้วใช่ไหม?'],
            'applybox_d'   => ['กล่องสมัคร: รายละเอียด', 'text', 'ดูรอบที่เปิดอยู่และยื่นสมัครได้เลย'],
            'applybox_btn' => ['กล่องสมัคร: ข้อความปุ่ม', 'text', 'สมัครเรียน →'],
        ]],
        'buu_c_faq' => ['หน้าแรก: คำถามที่พบบ่อย', [
            'faq_title' => ['หัวข้อส่วน (ลบเพื่อซ่อน)', 'text', 'คำถามที่น้องๆ ถามบ่อย'],
            'faq1_q' => ['คำถาม 1', 'text', 'ไม่เคยเขียนโปรแกรมมาก่อน เรียนได้ไหม?'],
            'faq1_a' => ['คำตอบ 1', 'textarea', 'ได้ หลักสูตรเริ่มสอนตั้งแต่พื้นฐาน สิ่งที่สำคัญกว่าประสบการณ์เดิมคือความอยากรู้และการฝึกเขียนโค้ดสม่ำเสมอ'],
            'faq2_q' => ['คำถาม 2', 'text', 'ต้องเก่งคณิตศาสตร์แค่ไหน?'],
            'faq2_a' => ['คำตอบ 2', 'textarea', 'CS ใช้คณิตศาสตร์ เช่น ตรรกะ ดิสครีต และสถิติ ไม่จำเป็นต้องเป็นอัจฉริยะ แต่ควรสนุกกับการคิดแก้ปัญหาเป็นขั้นตอน'],
            'faq3_q' => ['คำถาม 3', 'text', 'CS ต่างจาก IT, SE หรือ Data Science อย่างไร?'],
            'faq3_a' => ['คำตอบ 3', 'textarea', 'CS เน้นแก่นของคอมพิวเตอร์ เช่น อัลกอริทึม การเขียนโปรแกรม และระบบ จึงต่อยอดได้หลายทาง IT เน้นการนำเทคโนโลยีไปใช้และดูแลระบบในองค์กร SE เน้นกระบวนการสร้างซอฟต์แวร์ขนาดใหญ่ ส่วน Data Science เน้นการวิเคราะห์ข้อมูล'],
            'faq4_q' => ['คำถาม 4', 'text', 'ต้องมีโน้ตบุ๊กสเปกแรงไหม?'],
            'faq4_a' => ['คำตอบ 4', 'textarea', 'ปีแรกใช้โน้ตบุ๊กทั่วไปที่เขียนโปรแกรมได้ก็พอ งานที่ใช้เครื่องแรง เช่น เทรน AI มักทำบนเครื่องของคณะหรือบนคลาวด์'],
            'faq5_q' => ['คำถาม 5 (เว้นว่างได้)', 'text', ''],
            'faq5_a' => ['คำตอบ 5', 'textarea', ''],
            'faq6_q' => ['คำถาม 6 (เว้นว่างได้)', 'text', ''],
            'faq6_a' => ['คำตอบ 6', 'textarea', ''],
        ]],
        'buu_c_more' => ['หน้าแรก: ข่าวและสาขาอื่น', [
            'news_title'   => ['หัวข้อส่วนข่าว (ลบเพื่อซ่อน)', 'text', 'ข่าวสารและกิจกรรม'],
            'news_sub'     => ['คำอธิบายส่วนข่าว', 'text', 'อัปเดตข่าว กิจกรรม และความเคลื่อนไหวของสาขา'],
            'others_title' => ['แถบสาขาอื่น: หัวข้อ (ลบเพื่อซ่อน)', 'text', 'ยังเลือกไม่ได้?'],
            'others_sub'   => ['แถบสาขาอื่น: คำอธิบาย', 'text', 'ดูสาขาอื่นในคณะวิทยาการสารสนเทศ'],
        ]],
        'buu_c_feed' => ['หน้าแรก: ข่าวจากเว็บคณะ', [
            'import_count' => ['นำเข้าเป็นข่าวในเว็บเรา: จำนวนข่าวล่าสุดที่ดึง 1–100 (0 = ไม่นำเข้า แสดงเป็นส่วนแยกแทน)', 'text', '10'],
            'feed_api'   => ['ลิงก์ REST API ของเว็บคณะ (ใช้เป็นหลัก มีรูปข่าว · เว้นว่างได้)', 'url', 'https://www.informatics.buu.ac.th/?rest_route=/wp/v2/posts'],
            'feed_url'   => ['ลิงก์ RSS ของเว็บคณะ (สำรอง) · ลบทั้ง 2 ช่องเพื่อซ่อนส่วนนี้', 'url', 'https://www.informatics.buu.ac.th/?feed=rss2'],
            'feed_title' => ['หัวข้อส่วน', 'text', 'ข่าวล่าสุดจากคณะ'],
            'feed_sub'   => ['คำอธิบายส่วน', 'text', 'ดึงอัตโนมัติจากเว็บไซต์คณะวิทยาการสารสนเทศ อัปเดตทุกชั่วโมง'],
            'feed_count' => ['จำนวนข่าวที่แสดง (1–6)', 'text', '3'],
        ]],
        'buu_c_pages' => ['หน้าอื่นๆ และแถบท้ายเว็บ', [
            'programs_title' => ['หน้าหลักสูตร: หัวข้อ', 'text', 'หลักสูตรในคณะวิทยาการสารสนเทศ'],
            'programs_sub'   => ['หน้าหลักสูตร: คำอธิบาย', 'textarea', 'วิทยาการคอมพิวเตอร์และสาขาอื่นในคณะ เทียบกันแล้วเลือกเส้นทางที่ใช่สำหรับคุณ'],
            'newspage_title' => ['หน้าข่าว: หัวข้อ', 'text', 'ข่าวสารและกิจกรรม'],
            'newspage_sub'   => ['หน้าข่าว: คำอธิบาย', 'textarea', 'ติดตามข่าวสาร ประกาศ และทุกความเคลื่อนไหวของสาขาวิทยาการคอมพิวเตอร์'],
            'newscta_t'      => ['หน้าข่าว: กล่องติดตามข่าว หัวข้อ', 'text', 'ไม่พลาดข่าวจาก CS บูรพา'],
            'newscta_d'      => ['หน้าข่าว: กล่องติดตามข่าว รายละเอียด', 'text', 'ติดตามข่าวสาร กิจกรรม และประกาศสำคัญผ่านช่องทางของคณะ'],
            'cta_title'      => ['แถบสมัครท้ายเว็บ: หัวข้อ', 'text', 'มาเป็นส่วนหนึ่งของครอบครัว CS บูรพา'],
            'cta_sub'        => ['แถบสมัครท้ายเว็บ: รายละเอียด', 'text', 'เริ่มต้นเส้นทางสายคอมพิวเตอร์ที่บางแสน สมัครผ่านระบบ TCAS'],
            'cta_btn'        => ['แถบสมัครท้ายเว็บ: ข้อความปุ่ม', 'text', 'สมัครเรียน'],
            'foot_name'      => ['ท้ายเว็บ: ชื่อ', 'text', 'วิทยาการคอมพิวเตอร์'],
            'foot_desc'      => ['ท้ายเว็บ: คำอธิบาย', 'textarea', "คณะวิทยาการสารสนเทศ มหาวิทยาลัยบูรพา\nริมทะเลบางแสน จังหวัดชลบุรี"],
        ]],
    ];
}

/** ค่าเริ่มต้นทั้งหมด (key => default) */
function buu_content_defaults() {
    static $d = null;
    if ($d === null) {
        $d = [];
        foreach (buu_content_schema() as $sec) {
            foreach ($sec[1] as $k => $f) { $d[$k] = $f[2]; }
        }
    }
    return $d;
}

/** อ่านข้อความ (ค่าที่แก้ใน Customizer หรือค่าเริ่มต้น) */
function buu_c($key) {
    $d = buu_content_defaults();
    return (string) get_theme_mod('buu_c_' . $key, isset($d[$key]) ? $d[$key] : '');
}

/** แยกรายการแบบ 1 บรรทัดต่อ 1 รายการ */
function buu_lines($key) {
    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', buu_c($key)))));
}

/** แสดงข้อความหลายบรรทัด: escape แล้วแปลงขึ้นบรรทัดเป็น <br> */
function buu_ml($key) {
    return nl2br(esc_html(buu_c($key)), false);
}

add_action('customize_register', function ($c) {
    $c->add_panel('buu_content', [
        'title' => 'เนื้อหาเว็บไซต์',
        'description' => 'แก้ข้อความในหน้าเว็บได้ทุกส่วน · ช่องรายการใส่ 1 บรรทัดต่อ 1 รายการ · ลบหัวข้อของส่วนไหนออก ส่วนนั้นจะถูกซ่อน',
        'priority' => 25,
    ]);
    $prio = 10;
    foreach (buu_content_schema() as $sid => $sec) {
        $c->add_section($sid, ['title' => $sec[0], 'panel' => 'buu_content', 'priority' => $prio++]);
        foreach ($sec[1] as $k => $f) {
            $c->add_setting('buu_c_' . $k, [
                'default' => $f[2],
                'sanitize_callback' => $f[1] === 'textarea' ? 'sanitize_textarea_field' : ($f[1] === 'url' ? 'esc_url_raw' : 'sanitize_text_field'),
            ]);
            $c->add_control('buu_c_' . $k, ['label' => $f[0], 'section' => $sid, 'type' => $f[1]]);
        }
    }
});


/* ---------- ข่าวจากเว็บคณะ (RSS) ---------- */

/** เก็บผล RSS ไว้ 1 ชั่วโมง เว็บเราไม่ช้าตาม และถ้าเว็บคณะล่มยังแสดงชุดเดิมได้ */
add_filter('wp_feed_cache_transient_lifetime', function ($seconds, $url) {
    return $url === buu_c('feed_url') ? HOUR_IN_SECONDS : $seconds;
}, 10, 2);

/** ข้อความจากภายนอก → ตัวอักษรล้วน */
function buu_plain($html) {
    return trim(wp_strip_all_tags(html_entity_decode((string) $html, ENT_QUOTES, 'UTF-8')));
}

/** ดึงข่าวผ่าน REST API (มีรูปปกข่าว) */
function buu_faculty_news_api($n) {
    $api = buu_c('feed_api');
    if ($api === '' || !preg_match('#^https?://#i', $api)) return null;
    $url = add_query_arg([
        'per_page' => $n + 3,
        '_embed'   => 'wp:featuredmedia,wp:term',
        '_fields'  => 'date_gmt,link,title,excerpt,_links,_embedded',
    ], $api);
    // wp_safe_remote_get: ปฏิเสธการยิงไปยัง IP ภายในหรือ localhost (กัน SSRF)
    $res = wp_safe_remote_get($url, ['timeout' => 8, 'redirection' => 2, 'user-agent' => 'BUU-CS-Site/1.0']);
    if (is_wp_error($res) || (int) wp_remote_retrieve_response_code($res) !== 200) return null;
    $data = json_decode(wp_remote_retrieve_body($res), true);
    if (!is_array($data)) return null;
    $out = [];
    foreach ($data as $p) {
        if (count($out) >= $n) break;
        if (!is_array($p)) continue;
        $link = esc_url_raw(isset($p['link']) ? (string) $p['link'] : '', ['http', 'https']);
        if ($link === '') continue;
        $img = '';
        $m = isset($p['_embedded']['wp:featuredmedia'][0]) ? $p['_embedded']['wp:featuredmedia'][0] : null;
        if (is_array($m)) {
            foreach (['medium_large', 'large', 'medium'] as $size) {
                if (!empty($m['media_details']['sizes'][$size]['source_url'])) { $img = $m['media_details']['sizes'][$size]['source_url']; break; }
            }
            if ($img === '' && !empty($m['source_url'])) $img = $m['source_url'];
        }
        $img = preg_match('#^https://#i', (string) $img) ? esc_url_raw($img, ['https']) : '';
        $cat = isset($p['_embedded']['wp:term'][0][0]['name']) ? buu_plain($p['_embedded']['wp:term'][0][0]['name']) : 'ข่าวคณะ';
        $out[] = [
            'title'   => buu_plain(isset($p['title']['rendered']) ? $p['title']['rendered'] : ''),
            'link'    => $link,
            'excerpt' => wp_trim_words(buu_plain(isset($p['excerpt']['rendered']) ? $p['excerpt']['rendered'] : ''), 28, '…'),
            'date'    => !empty($p['date_gmt']) ? (int) strtotime($p['date_gmt'] . ' UTC') : 0,
            'cat'     => $cat !== '' ? $cat : 'ข่าวคณะ',
            'img'     => $img,
        ];
    }
    return $out ?: null;
}

/** ดึงข่าวผ่าน RSS (สำรอง ไม่มีรูป) */
function buu_faculty_news_rss($n) {
    $url = buu_c('feed_url');
    if ($url === '' || !preg_match('#^https?://#i', $url)) return null;
    if (!function_exists('fetch_feed')) include_once ABSPATH . WPINC . '/feed.php';
    $feed = fetch_feed($url);                 // ใช้ wp_safe_remote_get ภายใน
    if (is_wp_error($feed)) return null;
    $out = [];
    foreach ($feed->get_items(0, $n + 5) as $item) {
        if (count($out) >= $n) break;
        $link = esc_url_raw((string) $item->get_permalink(), ['http', 'https']);
        if ($link === '') continue;
        $cat = $item->get_category();
        $out[] = [
            'title'   => buu_plain($item->get_title()),
            'link'    => $link,
            'excerpt' => wp_trim_words(buu_plain($item->get_description()), 28, '…'),
            'date'    => (int) $item->get_date('U'),
            'cat'     => $cat ? buu_plain($cat->get_label()) : 'ข่าวคณะ',
            'img'     => '',
        ];
    }
    return $out ?: null;
}

/**
 * ข่าวจากเว็บคณะ: ลอง REST API ก่อน → ถ้าไม่ได้ใช้ RSS → ถ้าไม่ได้ทั้งคู่ใช้ชุดล่าสุดที่เคยดึงสำเร็จ
 * ผลลัพธ์ถูกเก็บไว้ 1 ชั่วโมง ข้อมูลทุกช่องเป็นข้อความล้วนแล้ว
 */
function buu_faculty_news() {
    if (buu_c('feed_api') === '' && buu_c('feed_url') === '') return [];
    $n = max(1, min(6, (int) buu_c('feed_count')));
    $key = 'buu_fac_news_' . md5(buu_c('feed_api') . '|' . buu_c('feed_url') . '|' . $n);
    $cached = get_transient($key);
    if (is_array($cached)) return $cached;
    $news = buu_faculty_news_api($n);
    if ($news === null) $news = buu_faculty_news_rss($n);
    if ($news === null) {
        $last = get_option('buu_fac_news_last');
        $news = is_array($last) ? $last : [];
        set_transient($key, $news, 10 * MINUTE_IN_SECONDS);   // ลองใหม่เร็วขึ้นเมื่อดึงไม่สำเร็จ
        return $news;
    }
    set_transient($key, $news, HOUR_IN_SECONDS);
    update_option('buu_fac_news_last', $news, false);
    return $news;
}
