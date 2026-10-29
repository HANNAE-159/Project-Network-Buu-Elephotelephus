## ภาคผนวก

### ภาคผนวก ก คำสั่งตรวจสอบระบบ

คำสั่งเหล่านี้ใช้เก็บหลักฐานในบทที่ 6 และใช้สาธิตต่ออาจารย์ได้

```bash
# ขั้น 1: SSH
sudo sshd -T | grep -Ei 'permitrootlogin|passwordauthentication|authenticationmethods'
ssh root@34.124.128.152            # ต้องได้ Permission denied
last -n 20                          # ประวัติการเข้าเครื่อง

# ขั้น 1: เวอร์ชันและบริการ
sudo cat /root/versions.txt
sudo fail2ban-client status
sudo ss -ltnp | grep 3306           # ต้องเป็น 127.0.0.1:3306

# ขั้น 2: firewall
sudo bash geo-firewall.sh status
sudo journalctl -k --since -1d --grep GEO-BLOCK | tail

# ส่วนเพิ่ม: secdash
sudo python3 /usr/local/sbin/secdash-collect.py
```

&#91;ตรวจตำแหน่งไฟล์ versions.txt และ geo-firewall.sh บนเซิร์ฟเวอร์จริงก่อนส่ง\]

### ภาคผนวก ข ภาพหน้าจอประกอบ

ใส่ภาพตามลำดับ พร้อมคำบรรยายใต้ภาพ

- [ ] ภาพ ข.1 หน้าแรกของเว็บไซต์บนคอมพิวเตอร์และมือถือ (เห็นรูปแม่กุญแจ HTTPS)
- [ ] ภาพ ข.2 ผล sudo sshd -T
- [ ] ภาพ ข.3 ssh root ถูกปฏิเสธ
- [ ] ภาพ ข.4 ไฟล์ versions.txt
- [ ] ภาพ ข.5 fail2ban-client status
- [ ] ภาพ ข.6 ผล check-host.net ที่เครื่องต่างประเทศ timeout
- [ ] ภาพ ข.7 geo-firewall status และ log GEO-BLOCK
- [ ] ภาพ ข.8 หน้าตั้งค่า “เนื้อหาเว็บไซต์” ใน Customizer
- [ ] ภาพ ข.9 ข่าวที่นำเข้าใน DBeaver (ต่อผ่าน SSH tunnel)
- [ ] ภาพ ข.10 หน้า dashboard “ความปลอดภัย” และกล่องสรุปจาก AI

### ภาคผนวก ค รายการไฟล์ที่พัฒนา

| ไฟล์ | หน้าที่ |
| --- | --- |
| setup.sh | ติดตั้งและตั้งค่าเซิร์ฟเวอร์ทั้งหมด (SSH, LEMP, WordPress, HTTPS, fail2ban, สำรองข้อมูล) |
| geo-firewall.sh | firewall รับเฉพาะ IP ไทย (apply, confirm, status, update, off) |
| secdash/secdash-collect.py | อ่าน log จัดประเภทการโจมตี เรียก AI และเขียน summary.json |
| secdash/secdash-install.sh | ติดตั้งตัวเก็บข้อมูลและ cron |
| ธีม buu-informatics: functions.php, front-page.php, header.php, footer.php | โครงสร้างและหน้าต่างๆ ของเว็บไซต์ |
| inc/content.php | ค่าตั้งเนื้อหาที่แก้ได้ใน Customizer และการดึงข่าว |
| inc/import.php | นำเข้าข่าวคณะเป็นโพสต์ WordPress |
| inc/secdash.php | หน้า dashboard “ความปลอดภัย” ในหลังบ้าน |
