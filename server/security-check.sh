#!/usr/bin/env bash
# =============================================================================
#  security-check.sh — ตรวจความปลอดภัยเซิร์ฟเวอร์ (เน้น PHP ตามคำเตือน ต.ค. 2569)
#
#  sudo bash security-check.sh          ตรวจอย่างเดียว ไม่แก้อะไร
#  sudo bash security-check.sh --fix    อัปเดต PHP ที่มีช่องโหว่ + ตั้งค่าลดความเสี่ยง + รีสตาร์ต PHP-FPM
#
#  อ้างอิง: USN-8948-1 (CVE-2026-109618, CVE-2026-109619) แก้ใน php8.3 8.3.6-0ubuntu0.24.04.13
#           CVE-2026-115560 ยังไม่มีแพตช์ → ปิด session.upload_progress.enabled
# =============================================================================
set -uo pipefail
[[ $EUID -eq 0 ]] || { echo "ต้องรันด้วย sudo"; exit 1; }
FIX=0; [[ "${1:-}" == "--fix" ]] && FIX=1

WEB_ROOT="/var/www/wordpress"
PHPV="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo 8.3)"
FPM="php${PHPV}-fpm"
PHP_FIXED="8.3.6-0ubuntu0.24.04.13"
P=0; W=0; F=0
ok()   { printf '\e[32m[PASS]\e[0m %s\n' "$*"; P=$((P+1)); }
warn() { printf '\e[33m[WARN]\e[0m %s\n' "$*"; W=$((W+1)); }
bad()  { printf '\e[31m[FAIL]\e[0m %s\n' "$*"; F=$((F+1)); }
hdr()  { printf '\n\e[1;36m== %s ==\e[0m\n' "$*"; }
WP()   { (cd "$WEB_ROOT" && sudo -u www-data WP_CLI_CACHE_DIR=/tmp/wp-cli-cache wp --path="$WEB_ROOT" "$@" 2>&1); }

# ------------------------------------------------------------------ 1. PHP
hdr "1. PHP: แพตช์ช่องโหว่ PHP2Shell (CVE-2026-109618/109619)"
cur="$(dpkg-query -W -f='${Version}' "$FPM" 2>/dev/null || echo none)"
echo "    ติดตั้งอยู่: $FPM $cur  (ต้อง ≥ $PHP_FIXED · php -v บอกแค่ 8.3.6 จึงใช้ยืนยันไม่ได้)"
if [[ "$cur" != none ]] && dpkg --compare-versions "$cur" ge "$PHP_FIXED"; then
  ok "PHP-FPM ได้แพตช์แล้ว"
else
  if (( FIX )); then
    apt-get update -q && apt-get install -yq --only-upgrade "$FPM" "php${PHPV}-cli" "php${PHPV}-common" "php${PHPV}-mysql" "php${PHPV}-curl" "php${PHPV}-gd" "php${PHPV}-intl" "php${PHPV}-mbstring" "php${PHPV}-xml" "php${PHPV}-zip" >/dev/null
    cur="$(dpkg-query -W -f='${Version}' "$FPM")"
    dpkg --compare-versions "$cur" ge "$PHP_FIXED" && ok "อัปเดตเป็น $cur แล้ว" || bad "อัปเดตแล้วยังได้ $cur — ตรวจ apt policy $FPM"
  else
    bad "PHP ยังไม่ได้แพตช์ — รัน: sudo bash security-check.sh --fix"
  fi
fi

hdr "2. PHP: ตั้งค่าลดความเสี่ยง (CVE-2026-115560 ยังไม่มีแพตช์)"
INI="/etc/php/${PHPV}/fpm/conf.d/99-security-2026.ini"
if (( FIX )); then
  cat > "$INI" <<'EOF'
; เพิ่มโดย security-check.sh — ลดความเสี่ยงช่องโหว่ PHP ต.ค. 2569
; WordPress ไม่ใช้ session upload progress จึงปิดได้โดยไม่กระทบเว็บ
session.upload_progress.enabled = 0
; ฟังก์ชันรันคำสั่งระบบ: WordPress ไม่ใช้ ถ้ามีคนฝังโค้ดได้ก็รันคำสั่งเครื่องไม่ได้ (มีผลเฉพาะ PHP-FPM ไม่กระทบ wp-cli)
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,pcntl_exec
allow_url_include = Off
max_file_uploads = 10
EOF
  echo "    เขียน $INI แล้ว"
fi
if [[ -f "$INI" ]] && grep -q '^session.upload_progress.enabled = 0' "$INI"; then
  ok "ปิด session.upload_progress แล้ว (FPM)"
else
  bad "session.upload_progress ยังเปิดอยู่ — รัน --fix"
fi
if grep -q '^disable_functions' "$INI" 2>/dev/null; then ok "ปิดฟังก์ชันรันคำสั่งระบบใน PHP-FPM แล้ว"; else warn "ยังไม่ได้ปิด exec/system/shell_exec ใน PHP-FPM"; fi

if (( FIX )); then systemctl restart "$FPM" && echo "    รีสตาร์ต $FPM แล้ว"; fi
pkg_time="$(stat -c %Y "/var/lib/dpkg/info/${FPM}.list" 2>/dev/null || echo 0)"
fpm_start="$(date -d "$(systemctl show -p ActiveEnterTimestamp --value "$FPM")" +%s 2>/dev/null || echo 0)"
if (( fpm_start >= pkg_time )); then ok "$FPM รีสตาร์ตหลังติดตั้งแพ็กเกจล่าสุด (worker ใช้โค้ดใหม่)"; else bad "$FPM ยังรันโค้ดเก่า — sudo systemctl restart $FPM"; fi

# ------------------------------------------------------------------ 3. ไฟล์ต้องสงสัย
hdr "3. ไฟล์ต้องสงสัย (web shell)"
up="$(find "$WEB_ROOT/wp-content/uploads" -type f \( -iname '*.php*' -o -iname '*.phtml' -o -iname '*.phar' \) 2>/dev/null)"
[[ -z "$up" ]] && ok "ไม่มีไฟล์ PHP ในโฟลเดอร์ uploads" || { bad "พบไฟล์ PHP ใน uploads (ปกติต้องไม่มี):"; echo "$up" | sed 's/^/      /'; }
recent="$(find "$WEB_ROOT" -type f -name '*.php' -mtime -7 -not -path '*/wp-content/themes/buu-informatics/*' 2>/dev/null | head -20)"
[[ -z "$recent" ]] && ok "ไม่มีไฟล์ PHP ที่ถูกแก้ใน 7 วัน (นอกธีมของเรา)" || { warn "ไฟล์ PHP ที่ถูกแก้ใน 7 วัน — ถ้าไม่ได้อัปเดต WordPress/ปลั๊กอินเอง ให้ตรวจ:"; echo "$recent" | sed 's/^/      /'; }
if command -v wp >/dev/null; then
  out="$(WP core verify-checksums)"
  mism="$(echo "$out" | grep -E "doesn't verify|should not exist" )"; miss="$(echo "$out" | grep -c "doesn't exist")"
  if echo "$out" | grep -q "^Success"; then ok "ไฟล์ WordPress core ตรงกับต้นฉบับ"
  elif [[ -n "$mism" ]]; then bad "ไฟล์ WordPress core ถูกแก้หรือมีไฟล์แปลกปลอม (ตรวจด่วน):"; echo "$mism" | head -15 | sed 's/^/      /'
  else warn "ไฟล์ core หายไป $miss ไฟล์ (มักเกิดจากอัปเดตไม่ครบ) — แก้: ดูหัวข้อ 'ซ่อม core' ใน README"; fi
  out="$(WP plugin verify-checksums --all)"; echo "$out" | grep -q "Success" && ok "ไฟล์ปลั๊กอินตรงกับต้นฉบับ" || { warn "ตรวจปลั๊กอิน:"; echo "$out" | head -10 | sed 's/^/      /'; }
  hdr "4. WordPress"
  admins="$(WP user list --role=administrator --field=user_login | tr '\n' ' ')"; echo "    ผู้ดูแลระบบ: $admins"
  echo "$admins" | grep -qw admin && bad "มีผู้ใช้ชื่อ admin (เดาง่าย)" || ok "ไม่มีผู้ใช้ชื่อ admin"
  upd="$(WP core check-update --format=count 2>/dev/null)"; [[ "$upd" =~ ^[1-9] ]] && warn "WordPress มีเวอร์ชันใหม่ — wp core update" || ok "WordPress core เป็นเวอร์ชันล่าสุด"
  pu="$(WP plugin list --update=available --format=count 2>/dev/null)"; [[ "$pu" =~ ^[1-9] ]] && warn "มีปลั๊กอินที่ต้องอัปเดต $pu ตัว" || ok "ปลั๊กอินเป็นเวอร์ชันล่าสุด"
  grep -q "DISALLOW_FILE_EDIT', true" "$WEB_ROOT/wp-config.php" && ok "ปิดการแก้โค้ดในหลังบ้าน (DISALLOW_FILE_EDIT)" || warn "ยังไม่ได้ตั้ง DISALLOW_FILE_EDIT"
fi
perm="$(stat -c %a "$WEB_ROOT/wp-config.php" 2>/dev/null)"; [[ "$perm" =~ ^(400|440|600|640)$ ]] && ok "wp-config.php สิทธิ์ $perm" || warn "wp-config.php สิทธิ์ $perm — แนะนำ: sudo chmod 640 $WEB_ROOT/wp-config.php"

# ------------------------------------------------------------------ 5. เว็บจากภายนอก
hdr "5. ทดสอบจากมุมคนนอก (ผ่าน localhost)"
HOST="${SITE_HOST:-$(nginx -T 2>/dev/null | grep -oP '^\s*server_name\s+\K[^;]+' | tr ' ' '\n' | grep -m1 '\.')}"
echo "    โดเมนที่ทดสอบ: ${HOST:-ไม่พบ (ตั้ง SITE_HOST=โดเมน แล้วรันใหม่)}"
code() { curl -sk --noproxy '*' --max-time 10 -o /dev/null -w '%{http_code}' --resolve "$HOST:443:127.0.0.1" "https://$HOST$1"; }
c="$(code /xmlrpc.php)";   [[ "$c" == 403 ]] && ok "xmlrpc.php → 403" || bad "xmlrpc.php → $c"
c="$(code /.env)";         [[ "$c" =~ ^(403|404)$ ]] && ok ".env → $c" || bad ".env → $c"
c="$(code '/?author=1')";  [[ "$c" =~ ^(403|404)$ ]] && ok "?author=1 → $c (ปิดการไล่หาชื่อผู้ใช้)" || warn "?author=1 → $c (อัปเดตธีมเป็น 3.7.0 เพื่อปิด)"
c="$(code '/?rest_route=/wp/v2/users')"; [[ "$c" =~ ^(401|403|404)$ ]] && ok "REST รายชื่อผู้ใช้ → $c" || warn "REST รายชื่อผู้ใช้ → $c (อัปเดตธีมเป็น 3.7.0 เพื่อปิด)"
curl -skI --noproxy '*' --max-time 10 --resolve "$HOST:443:127.0.0.1" "https://$HOST/" | grep -qi '^x-powered-by' && warn "ยังส่ง X-Powered-By (บอกเวอร์ชัน PHP)" || ok "ไม่บอกเวอร์ชัน PHP ใน header"

# ------------------------------------------------------------------ 6. ระบบ
hdr "6. ระบบและเครือข่าย"
pub="$(ss -ltnH | awk '{print $4}' | grep -vE '^(127\.|\[::1\])' | sed 's/.*://' | sort -un | tr '\n' ' ')"
echo "    พอร์ตที่เปิดรับจากภายนอก: $pub"
extra=""; for p in $pub; do [[ "$p" =~ ^(22|80|443)$ ]] || extra+="$p "; done
if [[ -z "$extra" ]]; then ok "เปิดเฉพาะ 22/80/443"; else
  warn "มีพอร์ตอื่นเปิดรับจากภายนอก: $extra (โปรแกรมที่เปิด:)"
  for p in $extra; do ss -ltnpH "sport = :$p" | grep -oP 'users:\(\("\K[^"]+' | sort -u | sed "s/^/      พอร์ต $p → /"; done
fi
ss -ltnH | grep -q '127.0.0.1:3306' && ok "MySQL รับเฉพาะ 127.0.0.1" || warn "ตรวจ MySQL bind-address"
s="$(sshd -T 2>/dev/null)"
echo "$s" | grep -q '^permitrootlogin no' && echo "$s" | grep -q '^passwordauthentication no' && ok "SSH: ปิด root + ปิดรหัสผ่าน" || bad "SSH ตั้งค่าไม่ครบ"
systemctl is-active -q fail2ban && ok "fail2ban ทำงานอยู่" || bad "fail2ban ไม่ทำงาน"
iptables -C INPUT -j GEO_IN 2>/dev/null && ok "Geo-firewall (ไทยเท่านั้น) ทำงานอยู่" || bad "Geo-firewall ไม่ทำงาน"
sec="$(apt-get -s upgrade 2>/dev/null | grep -c '^Inst.*security')"
(( sec == 0 )) && ok "ไม่มีอัปเดตความปลอดภัยค้าง" || warn "มีอัปเดตความปลอดภัยค้าง $sec แพ็กเกจ — sudo apt upgrade"
[[ -f /var/run/reboot-required ]] && warn "ระบบต้องการรีบูตเพื่อใช้ kernel/ไลบรารีใหม่" || ok "ไม่ต้องรีบูต"

printf '\n\e[1mสรุป: PASS %d · WARN %d · FAIL %d\e[0m\n' "$P" "$W" "$F"
(( F > 0 && FIX == 0 )) && echo "แก้อัตโนมัติ: sudo bash security-check.sh --fix"
exit $(( F > 0 ))
