# CS BUU WordPress — เว็บไซต์สาขาวิทยาการคอมพิวเตอร์บนเซิร์ฟเวอร์ที่ติดตั้งและป้องกันเอง

โปรเจกต์กลุ่ม รายวิชา 88633065 Computer Networks เครือข่ายคอมพิวเตอร์ ภาคเรียนที่ 1/2569 (ผู้สอน ผศ.ภูสิต กุลเกษม) คณะวิทยาการสารสนเทศ มหาวิทยาลัยบูรพา
เว็บไซต์จริง: https://elephotelephus.site (เปิดให้เข้าได้เฉพาะ IP ในประเทศไทย)

## โครงสร้าง repo

| โฟลเดอร์ | เนื้อหา |
| --- | --- |
| `theme/buu-informatics/` | ธีม WordPress ที่ทีมออกแบบและเขียนเอง (ตามแบบใน Figma) |
| `server/setup.sh` | ติดตั้งเซิร์ฟเวอร์ทั้งหมดในคำสั่งเดียว: SSH hardening, nginx, PHP-FPM, MySQL, WordPress, HTTPS (Let’s Encrypt DNS-01), fail2ban, สำรองข้อมูล |
| `server/geo-firewall.sh` | firewall รับเฉพาะ IP ไทย (ipset + iptables) พร้อม auto-rollback |
| `server/security-check.sh` | ตรวจความปลอดภัยเซิร์ฟเวอร์ รวมถึงช่องโหว่ PHP ต.ค. 2569 (`--fix` เพื่อแก้อัตโนมัติ) |
| `server/secdash/` | ตัวเก็บข้อมูล log สำหรับ dashboard ตรวจการโจมตี + สรุปด้วย AI |
| `docs/` | รายงาน (Markdown → LaTeX → PDF) |

## สถาปัตยกรรม

```
ผู้ใช้ ──DNS──> Cloudflare (A record → IP คงที่)
   │
   └──HTTPS/SSH──> Google Cloud VM (Ubuntu 24.04.5, e2-small)
                     ├─ Geo-firewall: ipset geo_th + iptables GEO_IN  (ต่างประเทศ → DROP + log)
                     ├─ nginx 1.24.0 ──> PHP-FPM 8.3.6 ──> WordPress 7.1.4 ──> MySQL 8.0.46 (127.0.0.1)
                     ├─ OpenSSH 9.6p1 (คีย์เท่านั้น · ปิด root) · fail2ban 1.0.2
                     └─ secdash (อ่าน log ทุก 10 นาที) ──> Gemini API (สรุปทุกชั่วโมง)
```

## ธีม

- แก้ข้อความทุกส่วนได้ที่ Appearance → Customize → “เนื้อหาเว็บไซต์” (ไม่ต้องแก้โค้ด)
- นำเข้าข่าวจากเว็บคณะผ่าน WordPress REST API ทุกชั่วโมง เก็บเป็นโพสต์ในฐานข้อมูล (`inc/import.php`)
- หน้า “ความปลอดภัย” ในหลังบ้าน แสดงข้อมูลการโจมตีจริงจาก log (`inc/secdash.php`)
- ปิดการไล่หาชื่อผู้ใช้ ซ่อนเวอร์ชัน ปิด XML-RPC (`inc/hardening.php`)
- ทุกข้อความที่แสดงผ่าน `esc_html` / `esc_attr` / `wp_kses_post` · ฟอร์มหลังบ้านตรวจ nonce และสิทธิ์

ติดตั้ง: zip โฟลเดอร์ `theme/buu-informatics` แล้วอัปโหลดที่ Appearance → Themes → Add New → Upload

## เซิร์ฟเวอร์

ดูขั้นตอนเต็มที่ [`server/INSTALL.md`](server/INSTALL.md)

```bash
sudo bash setup.sh                 # ติดตั้งทั้งหมด (ถาม Cloudflare token ตอนรัน ไม่เก็บใน repo)
sudo bash geo-firewall.sh apply    # เปิด firewall ไทยเท่านั้น แล้วพิมพ์ confirm ภายใน 3 นาที
sudo bash secdash/secdash-install.sh
sudo bash security-check.sh        # ตรวจความปลอดภัย
```

## ความปลอดภัยของ repo นี้

repo นี้ไม่มีรหัสผ่าน, API token, คีย์ SSH หรือไฟล์ตั้งค่าที่มีความลับ
ค่าลับทั้งหมดถูกถามตอนติดตั้งและเก็บบนเซิร์ฟเวอร์ใน `/root/wp-credentials.txt` และ `/etc/secdash/ai.env` (สิทธิ์ root เท่านั้น)

## ผู้จัดทำ

- 67160049 นายกนกกร บุญเขียว
- 67160079 นายสุกฤษฎ์ กาษร
- 67160092 นายณัฐสิทธิ์ จันทร์จิรานนท์
- 67160100 นายภัทรกร รัศมีดิษฐ์
- 67160102 นายเมธาภูมิ ศรีสวัสดิ์
- 67160255 นายชยพัทธ์ อินทรศักดิ์
- 67160266 นายภัครวรรธน์ แตรไตรรงค์
