#!/usr/bin/env bash
# =============================================================================
#  secdash-install.sh — ติดตั้งตัวเก็บข้อมูลสำหรับหน้า "ความปลอดภัย" ในหลังบ้าน WordPress
#  วิธีใช้:  sudo bash secdash-install.sh
# =============================================================================
set -euo pipefail
[[ $EUID -eq 0 ]] || { echo "ต้องรันด้วย sudo"; exit 1; }
cd "$(dirname "$0")"

install -m 750 -o root -g root secdash-collect.py /usr/local/sbin/secdash-collect.py
install -d -m 750 -o root -g www-data /var/lib/secdash
install -d -m 700 -o root -g root /etc/secdash
if [[ ! -f /etc/secdash/ai.env ]]; then
  cat > /etc/secdash/ai.env <<'EOF'
# ตั้งค่า AI สำหรับวิเคราะห์ log (เว้นว่าง = ไม่ใช้ AI ส่วนอื่นของ dashboard ยังทำงานครบ)
# AI_PROVIDER=gemini      หรือ anthropic
# AI_API_KEY=ใส่คีย์ที่นี่
# AI_MODEL=               (ไม่ใส่ก็ได้ ใช้ค่าเริ่มต้น)
EOF
  chmod 600 /etc/secdash/ai.env
fi

echo '*/10 * * * * root /usr/bin/python3 /usr/local/sbin/secdash-collect.py >> /var/log/secdash.log 2>&1' > /etc/cron.d/secdash
chmod 644 /etc/cron.d/secdash

/usr/bin/python3 /usr/local/sbin/secdash-collect.py
ls -l /var/lib/secdash/summary.json
echo
echo "เสร็จแล้ว: เก็บข้อมูลทุก 10 นาที · เปิดดูที่หลังบ้าน WordPress เมนู 'ความปลอดภัย'"
echo "เปิดใช้ AI: sudo nano /etc/secdash/ai.env  แล้วรัน  sudo python3 /usr/local/sbin/secdash-collect.py"
