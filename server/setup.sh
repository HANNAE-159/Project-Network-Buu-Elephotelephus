#!/usr/bin/env bash
# =============================================================================
#  setup.sh — ติดตั้งเซิร์ฟเวอร์ WordPress แบบปลอดภัย (Ubuntu 24.04 LTS)
#  nginx + PHP-FPM + MySQL + WordPress + HTTPS (Let's Encrypt ผ่าน Cloudflare DNS)
#  + SSH hardening + fail2ban + อัปเดตอัตโนมัติ + backup รายวัน
#
#  วิธีใช้:  sudo bash setup.sh
#  รันซ้ำได้ (ขั้นที่ทำไปแล้วจะข้ามหรือทับค่าเดิมอย่างปลอดภัย)
# =============================================================================
set -euo pipefail

# ----------------------------- ตั้งค่า ---------------------------------------
DOMAINS=("elephotelephus.site" "cs.elephotelephus.site")   # ชื่อแรก = ชื่อหลักของเว็บ
ADMIN_USER="k"                       # user ธรรมดาที่ใช้ sudo (ต้องมีอยู่แล้ว หรือจะถูกสร้าง)
WP_TITLE="Computer Science BUU"      # ชื่อเว็บ (แก้ภายหลังในหน้าแอดมินได้)
WP_LOCALE="en_US"                    # ภาษาหลังบ้าน (เปลี่ยนเป็นไทยได้ใน Settings > General > Site Language)
TIMEZONE="Asia/Bangkok"
WEB_ROOT="/var/www/wordpress"
DB_NAME="wordpress"
DB_USER="wp_app"
# -----------------------------------------------------------------------------

PRIMARY="${DOMAINS[0]}"
CRED_FILE="/root/wp-credentials.txt"
LOG="/var/log/server-setup.log"

c_ok()   { printf '\e[32m[ OK ]\e[0m %s\n' "$*"; }
c_step() { printf '\n\e[1;36m==> %s\e[0m\n' "$*"; }
c_warn() { printf '\e[33m[WARN]\e[0m %s\n' "$*"; }
die()    { printf '\e[31m[FAIL]\e[0m %s\n' "$*" >&2; exit 1; }

[[ $EUID -eq 0 ]] || die "ต้องรันด้วย sudo:  sudo bash setup.sh"
grep -q 'Ubuntu' /etc/os-release || c_warn "สคริปต์นี้ทดสอบกับ Ubuntu เท่านั้น"
exec > >(tee -a "$LOG") 2>&1
export DEBIAN_FRONTEND=noninteractive

# สุ่มรหัสผ่าน (ไม่ใช้ tr </dev/urandom | head เพราะโดน SIGPIPE แล้ว set -e หยุดสคริปต์เงียบ ๆ)
randpw() { local s; s=$(head -c 1024 /dev/urandom | LC_ALL=C tr -dc 'A-Za-z0-9'); printf '%s' "${s:0:${1:-28}}"; }

cf_api() { curl -sS --max-time 20 -H "Authorization: Bearer ${CF_Token}" "https://api.cloudflare.com/client/v4/$1"; }
cf_token_ok() { cf_api "user/tokens/verify" | grep -q '"status":"active"'; }
cf_zone_ok()  { cf_api "zones/${CF_Zone_ID}" | grep -q '"success":true'; }

# ----------------------------- รับค่าลับ --------------------------------------
c_step "ข้อมูลที่ต้องใช้ (ค่าลับจะไม่แสดงบนหน้าจอ)"
if [[ -f "$CRED_FILE" ]]; then
  # shellcheck disable=SC1090
  source "$CRED_FILE"
  c_ok "พบค่าเดิมใน $CRED_FILE จะใช้ค่าเดิม"
fi
while [[ -z "${ADMIN_EMAIL:-}" ]]; do read -rp "อีเมลผู้ดูแลเว็บ: " ADMIN_EMAIL; done

for try in 1 2 3; do
  if [[ -z "${CF_Token:-}" ]]; then
    read -rsp "Cloudflare API Token (คลิกขวาวางครั้งเดียว แล้วกด Enter): " CF_Token; echo
    CF_Token="${CF_Token//[[:space:]]/}"
  fi
  if [[ -n "$CF_Token" ]] && cf_token_ok; then c_ok "Cloudflare API Token ถูกต้อง"; break; fi
  c_warn "Token ไม่ถูกต้อง หรือวางไม่เข้า (ยาว ${#CF_Token} ตัวอักษร) — ลองใหม่ ($try/3)"
  CF_Token=""
done
[[ -n "$CF_Token" ]] || die "Token ไม่ถูกต้อง 3 ครั้ง — สร้าง token ใหม่ใน Cloudflare แล้วรันสคริปต์อีกครั้ง"

for try in 1 2 3; do
  if [[ -z "${CF_Zone_ID:-}" ]]; then
    read -rp "Cloudflare Zone ID: " CF_Zone_ID
    CF_Zone_ID="${CF_Zone_ID//[[:space:]]/}"
  fi
  if [[ -n "$CF_Zone_ID" ]] && cf_zone_ok; then c_ok "Zone ID ถูกต้อง"; break; fi
  c_warn "Zone ID ไม่ถูกต้อง หรือ token ไม่มีสิทธิ์กับโดเมนนี้ — ลองใหม่ ($try/3)"
  CF_Zone_ID=""
done
[[ -n "$CF_Zone_ID" ]] || die "Zone ID ไม่ถูกต้อง 3 ครั้ง"

DB_PASS="${DB_PASS:-$(randpw 32)}"
WP_ADMIN="${WP_ADMIN:-cs_$(randpw 6 | tr 'A-Z' 'a-z')}"   # ไม่ใช้ชื่อ admin ที่เดาง่าย
WP_ADMIN_PASS="${WP_ADMIN_PASS:-$(randpw 24)}"

umask 077
cat > "$CRED_FILE" <<EOF
# สร้างโดย setup.sh — ไฟล์ลับ อ่านได้เฉพาะ root
ADMIN_EMAIL='$ADMIN_EMAIL'
CF_Token='$CF_Token'
CF_Zone_ID='$CF_Zone_ID'
DB_PASS='$DB_PASS'
WP_ADMIN='$WP_ADMIN'
WP_ADMIN_PASS='$WP_ADMIN_PASS'
EOF
chmod 600 "$CRED_FILE"
umask 022

# ----------------------------- 1. ระบบพื้นฐาน ---------------------------------
c_step "1/9 อัปเดตระบบและตั้งค่าพื้นฐาน"
apt-get update -q
apt-get -yq upgrade
apt-get -yq install curl wget unzip ca-certificates gnupg socat cron \
  unattended-upgrades fail2ban python3-systemd ipset
timedatectl set-timezone "$TIMEZONE" || true

echo 'unattended-upgrades unattended-upgrades/enable_auto_updates boolean true' | debconf-set-selections
dpkg-reconfigure -f noninteractive unattended-upgrades
c_ok "เปิดอัปเดตความปลอดภัยอัตโนมัติแล้ว"

# swap: เครื่อง RAM น้อยต้องมี ไม่งั้น MySQL ล่มง่าย
MEM_MB=$(awk '/MemTotal/{print int($2/1024)}' /proc/meminfo)
if [[ -z "$(swapon --noheadings)" && ! -f /swapfile ]] && (( MEM_MB < 4096 )); then
  fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile >/dev/null && swapon /swapfile
  grep -q '^/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
  sysctl -q vm.swappiness=10; echo 'vm.swappiness=10' > /etc/sysctl.d/99-swap.conf
  c_ok "สร้าง swap 2GB (RAM ${MEM_MB}MB)"
fi

# ----------------------------- 2. user + SSH ---------------------------------
c_step "2/9 user ผู้ดูแลและ SSH hardening"
if ! id "$ADMIN_USER" &>/dev/null; then
  adduser --disabled-password --gecos "" "$ADMIN_USER"
  c_warn "สร้าง user $ADMIN_USER ใหม่ — อย่าลืมใส่ public key ก่อนออกจากเซสชันนี้"
fi
usermod -aG sudo "$ADMIN_USER"
AUTH_KEYS="/home/$ADMIN_USER/.ssh/authorized_keys"
if [[ ! -s "$AUTH_KEYS" ]]; then
  die "ไม่พบ public key ใน $AUTH_KEYS — ถ้าปิดการล็อกอินด้วยรหัสผ่านตอนนี้จะเข้าเครื่องไม่ได้ ใส่ key ก่อนแล้วรันใหม่"
fi
chmod 700 "/home/$ADMIN_USER/.ssh"; chmod 600 "$AUTH_KEYS"
chown -R "$ADMIN_USER:$ADMIN_USER" "/home/$ADMIN_USER/.ssh"

# ไฟล์ชื่อ 00- เพื่อให้ถูกอ่านก่อนไฟล์อื่น (sshd ใช้ค่าแรกที่เจอ เช่น 50-cloud-init.conf)
cat > /etc/ssh/sshd_config.d/00-hardening.conf <<'EOF'
# SSH hardening — ด่าน 1
PermitRootLogin no
PasswordAuthentication no
KbdInteractiveAuthentication no
PermitEmptyPasswords no
PubkeyAuthentication yes
AuthenticationMethods publickey
MaxAuthTries 3
LoginGraceTime 30
X11Forwarding no
AllowAgentForwarding no
ClientAliveInterval 300
ClientAliveCountMax 2
EOF
sshd -t || die "ค่า SSH ผิดพลาด (ยังไม่ได้ restart ssh)"
systemctl restart ssh
c_ok "SSH: ปิด root login, ปิด password, ใช้ key เท่านั้น"

# ----------------------------- 3. web stack ----------------------------------
c_step "3/9 ติดตั้ง nginx, PHP-FPM, MySQL"
apt-get -yq install nginx mysql-server \
  php-fpm php-mysql php-curl php-gd php-intl php-mbstring php-xml php-zip \
  php-bcmath php-imagick
PHPV=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
PHP_SOCK="/run/php/php${PHPV}-fpm.sock"

cat > "/etc/php/${PHPV}/fpm/conf.d/99-hardening.ini" <<'EOF'
expose_php = Off
upload_max_filesize = 64M
post_max_size = 64M
memory_limit = 256M
max_execution_time = 120
allow_url_include = Off
display_errors = Off
log_errors = On
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
session.upload_progress.enabled = 0
EOF
systemctl restart "php${PHPV}-fpm"
c_ok "PHP ${PHPV} (ซ่อนเวอร์ชันแล้ว)"

# MySQL: ฟังเฉพาะในเครื่อง, user แยกเฉพาะ WordPress
cat > /etc/mysql/mysql.conf.d/99-local.cnf <<'EOF'
[mysqld]
bind-address = 127.0.0.1
mysqlx-bind-address = 127.0.0.1
local_infile = 0
EOF
systemctl restart mysql
mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
DELETE FROM mysql.user WHERE User='';
DROP DATABASE IF EXISTS test;
FLUSH PRIVILEGES;
SQL
c_ok "MySQL: ฐานข้อมูล ${DB_NAME}, user ${DB_USER} (ฟังเฉพาะ 127.0.0.1)"

# ----------------------------- 4. HTTPS --------------------------------------
c_step "4/9 ออกใบรับรอง HTTPS (Let's Encrypt ผ่าน Cloudflare DNS)"
# ใช้ DNS challenge: ไม่ต้องเปิดพอร์ตให้ต่างประเทศ จึงใช้ร่วมกับ firewall เฉพาะไทยได้
ACME="/root/.acme.sh/acme.sh"
if [[ ! -x "$ACME" ]]; then
  curl -fsSL https://get.acme.sh | sh -s email="$ADMIN_EMAIL"
fi
"$ACME" --set-default-ca --server letsencrypt
D_ARGS=(); for d in "${DOMAINS[@]}"; do D_ARGS+=(-d "$d"); done
export CF_Token CF_Zone_ID
"$ACME" --issue --dns dns_cf "${D_ARGS[@]}" --keylength ec-256 || {
  rc=$?; [[ $rc -eq 2 ]] && c_ok "ใบรับรองยังไม่หมดอายุ ใช้ใบเดิม" || die "ออกใบรับรองไม่สำเร็จ ตรวจ token / Zone ID"; }
mkdir -p /etc/nginx/ssl && chmod 700 /etc/nginx/ssl
"$ACME" --install-cert -d "$PRIMARY" --ecc \
  --key-file /etc/nginx/ssl/site.key \
  --fullchain-file /etc/nginx/ssl/fullchain.pem \
  --reloadcmd "systemctl reload nginx || true"
c_ok "HTTPS พร้อม (ต่ออายุอัตโนมัติผ่าน cron ของ acme.sh)"

# ----------------------------- 5. nginx --------------------------------------
c_step "5/9 ตั้งค่า nginx"
sed -i 's/^\s*#\?\s*server_tokens.*/\tserver_tokens off;/' /etc/nginx/nginx.conf
grep -q 'server_tokens off' /etc/nginx/nginx.conf || \
  sed -i '/http {/a \\tserver_tokens off;' /etc/nginx/nginx.conf

cat > /etc/nginx/conf.d/00-security.conf <<'EOF'
# จำกัดความถี่การยิงหน้า login (กันเดารหัสผ่าน)
limit_req_zone $binary_remote_addr zone=wplogin:10m rate=6r/m;
limit_req_status 429;
client_max_body_size 64M;
EOF

cat > /etc/nginx/snippets/security-headers.conf <<'EOF'
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;
EOF

SERVER_NAMES="${DOMAINS[*]}"
cat > /etc/nginx/sites-available/wordpress <<EOF
# เข้าด้วย IP หรือชื่อแปลก ๆ = ตัดการเชื่อมต่อ (ลดการสแกนอัตโนมัติ)
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name _;
    return 444;
}
server {
    listen 443 ssl default_server;
    listen [::]:443 ssl default_server;
    server_name _;
    ssl_reject_handshake on;
}

# http -> https
server {
    listen 80;
    listen [::]:80;
    server_name ${SERVER_NAMES};
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${SERVER_NAMES};
    root ${WEB_ROOT};
    index index.php index.html;

    ssl_certificate     /etc/nginx/ssl/fullchain.pem;
    ssl_certificate_key /etc/nginx/ssl/site.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 1d;
    ssl_session_tickets off;

    include snippets/security-headers.conf;

    access_log /var/log/nginx/wordpress.access.log;
    error_log  /var/log/nginx/wordpress.error.log;

    location / {
        try_files \$uri \$uri/ /index.php?\$args;
    }

    # ปิดช่องทางที่ถูกโจมตีบ่อย
    location = /xmlrpc.php            { deny all; return 403; }
    location ~ /\.(?!well-known)      { deny all; }
    location ~* /(wp-config\.php|readme\.html|license\.txt)$ { deny all; }
    location ~* ^/wp-content/uploads/.*\.(php|phtml|phar)$     { deny all; }
    location ~* ^/wp-includes/[^/]+\.php$ { deny all; }

    location = /wp-login.php {
        limit_req zone=wplogin burst=5 nodelay;
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:${PHP_SOCK};
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:${PHP_SOCK};
    }

    location ~* \.(css|js|png|jpe?g|gif|svg|webp|ico|woff2?)$ {
        expires 30d;
        access_log off;
        include snippets/security-headers.conf;
        add_header Cache-Control "public";
    }
}
EOF
ln -sf /etc/nginx/sites-available/wordpress /etc/nginx/sites-enabled/wordpress
rm -f /etc/nginx/sites-enabled/default
mkdir -p "$WEB_ROOT"
nginx -t || die "ค่า nginx ผิดพลาด"
systemctl enable --now nginx && systemctl reload nginx
c_ok "nginx พร้อม: ${SERVER_NAMES}"

# ----------------------------- 6. WordPress ----------------------------------
c_step "6/9 ติดตั้ง WordPress"
if [[ ! -x /usr/local/bin/wp ]]; then
  curl -fsSL -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
  chmod +x /usr/local/bin/wp
fi
chown -R www-data:www-data "$WEB_ROOT"
WP() { cd "$WEB_ROOT" && sudo -u www-data WP_CLI_CACHE_DIR=/tmp/wp-cli-cache wp --path="$WEB_ROOT" "$@"; }

if [[ ! -f "$WEB_ROOT/wp-includes/version.php" ]]; then
  WP core download --locale="$WP_LOCALE"
fi
if [[ ! -f "$WEB_ROOT/wp-config.php" ]]; then
  WP config create --dbname="$DB_NAME" --dbuser="$DB_USER" --dbpass="$DB_PASS" \
    --dbhost=localhost --dbcharset=utf8mb4 --locale="$WP_LOCALE" --extra-php <<'PHP'
define( 'DISALLOW_FILE_EDIT', true );   // ห้ามแก้โค้ดธีม/ปลั๊กอินจากหน้าแอดมิน
define( 'FORCE_SSL_ADMIN', true );
define( 'WP_AUTO_UPDATE_CORE', 'minor' );
define( 'WP_POST_REVISIONS', 10 );
PHP
fi
chmod 640 "$WEB_ROOT/wp-config.php"
if ! WP core is-installed 2>/dev/null; then
  WP core install --url="https://${PRIMARY}" --title="$WP_TITLE" \
    --admin_user="$WP_ADMIN" --admin_password="$WP_ADMIN_PASS" \
    --admin_email="$ADMIN_EMAIL" --skip-email
fi
WP option update timezone_string "$TIMEZONE"
WP rewrite structure '/%postname%/'
WP plugin delete hello akismet 2>/dev/null || true
WP plugin install two-factor --activate || c_warn "ติดตั้งปลั๊กอิน 2FA ไม่สำเร็จ (ติดตั้งเองภายหลังได้)"
find "$WEB_ROOT" -type d -exec chmod 755 {} \;
find "$WEB_ROOT" -type f -exec chmod 644 {} \;
chmod 640 "$WEB_ROOT/wp-config.php"
c_ok "WordPress พร้อม: https://${PRIMARY}"

# ----------------------------- 7. fail2ban -----------------------------------
c_step "7/9 fail2ban"
cat > /etc/fail2ban/filter.d/wordpress-login.conf <<'EOF'
[Definition]
failregex = ^<HOST> -[^"]*"POST /wp-login\.php
ignoreregex =
EOF
cat > /etc/fail2ban/jail.local <<'EOF'
[DEFAULT]
bantime  = 1h
findtime = 10m
maxretry = 5
banaction = iptables-multiport

[sshd]
enabled = true
backend = systemd

[wordpress-login]
enabled  = true
port     = http,https
filter   = wordpress-login
logpath  = /var/log/nginx/wordpress.access.log
maxretry = 6

[nginx-limit-req]
enabled  = true
port     = http,https
logpath  = /var/log/nginx/wordpress.error.log
EOF
systemctl enable --now fail2ban && systemctl restart fail2ban
c_ok "fail2ban: sshd, wordpress-login, nginx-limit-req"

# ----------------------------- 8. backup -------------------------------------
c_step "8/9 backup รายวัน"
cat > /usr/local/sbin/wp-backup.sh <<EOF
#!/usr/bin/env bash
set -euo pipefail
DEST=/var/backups/wordpress
mkdir -p "\$DEST"; chmod 700 "\$DEST"
TS=\$(date +%F_%H%M)
mysqldump --single-transaction --no-tablespaces ${DB_NAME} | gzip > "\$DEST/db_\$TS.sql.gz"
tar -czf "\$DEST/files_\$TS.tar.gz" -C ${WEB_ROOT} wp-content wp-config.php
find "\$DEST" -type f -mtime +7 -delete
EOF
chmod 700 /usr/local/sbin/wp-backup.sh
echo '30 3 * * * root /usr/local/sbin/wp-backup.sh >> /var/log/wp-backup.log 2>&1' > /etc/cron.d/wp-backup
/usr/local/sbin/wp-backup.sh
c_ok "backup ทุกวัน 03:30 เก็บ 7 วัน ที่ /var/backups/wordpress"

# ----------------------------- 9. สรุปเวอร์ชัน ---------------------------------
c_step "9/9 บันทึกเวอร์ชันโปรแกรม (ใช้ในรายงานด่าน 1)"
{
  echo "=== Server stack versions ($(date '+%F %T')) ==="
  echo "OS:        $(lsb_release -ds)"
  echo "Kernel:    $(uname -r)"
  echo "nginx:     $(nginx -v 2>&1 | sed 's/.*\///')"
  echo "PHP:       $(php -r 'echo PHP_VERSION;')"
  echo "MySQL:     $(mysql --version | awk '{print $3}')"
  echo "WordPress: $(WP core version)"
  echo "OpenSSH:   $(ssh -V 2>&1 | awk '{print $1}')"
  echo "fail2ban:  $(fail2ban-client --version 2>&1 | head -1)"
} | tee /root/versions.txt

cat <<EOF

=====================================================================
 ติดตั้งเสร็จแล้ว
 เว็บ:          https://${PRIMARY}
 หน้าแอดมิน:   https://${PRIMARY}/wp-admin
 ชื่อผู้ใช้/รหัสผ่านแอดมิน: ดูด้วยคำสั่ง  sudo cat ${CRED_FILE}
 เวอร์ชันโปรแกรม: /root/versions.txt

 ขั้นต่อไป:
  1. ทดสอบ SSH จากอีกหน้าต่าง ก่อนปิดหน้าต่างนี้
  2. ล็อกอินแอดมิน แล้วเปิด 2FA ที่ Users > Profile
  3. อัปโหลดธีม buu-informatics.zip
  4. เมื่อพร้อม รัน firewall เฉพาะไทย:  sudo bash geo-firewall.sh apply
=====================================================================
EOF
