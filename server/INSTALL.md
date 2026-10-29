# คู่มือใช้สคริปต์ติดตั้งเซิร์ฟเวอร์

ไฟล์ในชุดนี้
- `setup.sh` ติดตั้งทั้งหมด: SSH hardening, nginx, PHP, MySQL, WordPress, HTTPS, fail2ban, backup
- `geo-firewall.sh` ด่าน 2: รับการเชื่อมต่อจากประเทศไทยเท่านั้น

## ก่อนรัน (ทำครั้งเดียว)

### 1. สร้าง Cloudflare API Token (ใช้ขอใบรับรอง HTTPS)
1. dash.cloudflare.com → รูปโปรไฟล์มุมขวาบน → **My Profile** → **API Tokens** → **Create Token**
2. เลือกเทมเพลต **Edit zone DNS** → กด Use template
3. Zone Resources: **Include → Specific zone → elephotelephus.site**
4. Continue → Create Token → **ก๊อป token เก็บไว้** (แสดงครั้งเดียว ห้ามแชร์ในกลุ่มแชท)

### 2. หา Zone ID
dash.cloudflare.com → คลิกโดเมน → หน้า Overview ด้านขวาล่าง มีช่อง **Zone ID** → ก๊อปไว้

### 3. ชี้โดเมนไปที่เซิร์ฟเวอร์
DNS → Records → Add record ทำ 2 รายการ (Proxy = ปิด, เมฆสีเทา)

| Type | Name | IPv4 |
|---|---|---|
| A | `@` | External IP ของเซิร์ฟเวอร์ |
| A | `cs` | External IP ของเซิร์ฟเวอร์ |

## รันสคริปต์

### 1. ส่งไฟล์ขึ้นเซิร์ฟเวอร์ (พิมพ์ใน PowerShell บนเครื่องตัวเอง ในโฟลเดอร์ที่มีไฟล์)
```
scp setup.sh geo-firewall.sh k@IP-ของเซิร์ฟเวอร์:~
```

### 2. เข้าเซิร์ฟเวอร์แล้วติดตั้ง
```
ssh k@IP-ของเซิร์ฟเวอร์
sudo bash setup.sh
```
สคริปต์จะถาม อีเมล, Cloudflare API Token และ Zone ID แล้วทำงานเองประมาณ 5–10 นาที

**สำคัญ:** เมื่อเสร็จ อย่าเพิ่งปิดหน้าต่างเดิม เปิด PowerShell ใหม่แล้วลอง `ssh k@IP` ถ้าเข้าได้แปลว่าปลอดภัย

### 3. ดูรหัสผ่านแอดมิน WordPress
```
sudo cat /root/wp-credentials.txt
```
เข้า `https://elephotelephus.site/wp-admin` → ล็อกอิน → เปิด 2FA ที่ Users → Profile → อัปโหลดธีม

### 4. เปิด firewall เฉพาะไทย (ด่าน 2)
```
sudo bash geo-firewall.sh apply
```
แล้ว**ภายใน 3 นาที** เปิด PowerShell ใหม่ SSH เข้ามาอีกครั้ง ถ้าเข้าได้ ให้รัน
```
sudo bash geo-firewall.sh confirm
```
ถ้าไม่ยืนยัน firewall จะปิดตัวเองใน 3 นาที (กันล็อกตัวเองออก)

หมายเหตุ: หลังเปิด firewall ปุ่ม **SSH บนหน้าเว็บ Google Cloud จะใช้ไม่ได้** เพราะต่อมาจาก IP ต่างประเทศ ต้องใช้ SSH จากเครื่องตัวเองแทน

## คำสั่งที่ใช้เก็บหลักฐานส่งอาจารย์

| ด่าน | คำสั่ง |
|---|---|
| 1: เวอร์ชันโปรแกรม | `sudo cat /root/versions.txt` |
| 1: ค่า SSH | `sudo sshd -T \| grep -Ei 'permitrootlogin\|passwordauthentication\|pubkeyauthentication'` |
| 1: ทดสอบ root ถูกปฏิเสธ | (บนเครื่องตัวเอง) `ssh root@IP` |
| 2: กฎ firewall | `sudo bash geo-firewall.sh status` |
| 2: fail2ban | `sudo fail2ban-client status` |
| ทดสอบเข้าจากต่างประเทศ | เปิด VPN ประเทศอื่นแล้วเข้าเว็บ ต้องเข้าไม่ได้ |
