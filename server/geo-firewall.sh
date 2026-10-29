#!/usr/bin/env bash
# =============================================================================
#  geo-firewall.sh — ด่าน 2: รับการเชื่อมต่อจาก IP ประเทศไทยเท่านั้น (ipset + iptables)
#
#  sudo bash geo-firewall.sh apply    เปิดใช้ (ย้อนกลับเองใน 3 นาที ถ้าไม่ยืนยัน)
#  sudo bash geo-firewall.sh confirm  ยืนยันว่ายังเข้าได้ -> ใช้ถาวร + เปิดหลังรีบูต
#  sudo bash geo-firewall.sh update   ดึงรายการ IP ไทยล่าสุด (ตั้งให้รันทุกสัปดาห์แล้ว)
#  sudo bash geo-firewall.sh status   ดูสถานะ / ตัวนับแพ็กเก็ตที่ถูกบล็อก
#  sudo bash geo-firewall.sh off      ปิด firewall (ใช้ตอนฉุกเฉิน)
# =============================================================================
set -euo pipefail

COUNTRY="th"
# แหล่งรายการ IP ของประเทศ: ใช้ข้อมูลทางการของ APNIC (ผู้จัดสรร IP ของภูมิภาคเอเชียแปซิฟิก)
APNIC_URL="https://ftp.apnic.net/stats/apnic/delegated-apnic-latest"
FALLBACK_URL="https://www.ipdeny.com/ipblocks/data/aggregated/${COUNTRY}-aggregated.zone"
ZONE_FILE="/etc/geo-firewall/${COUNTRY}.zone"
SET="geo_${COUNTRY}"
CHAIN="GEO_IN"
SELF="/usr/local/sbin/geo-firewall.sh"
ROLLBACK_SEC=180
# IP/ช่วง IP นอกประเทศที่ยอมให้เข้าได้ (คั่นด้วยช่องว่าง) เช่น
# 35.235.240.0/20 = ปุ่ม SSH บนหน้าเว็บ Google Cloud (ปล่อยว่าง = ปิด)
ALLOW_EXTRA=""
# IP สาธารณะของเซิร์ฟเวอร์เอง: WordPress เรียกเว็บตัวเอง (WP-Cron, Site Health) ผ่านโดเมน ซึ่งวนกลับมาด้วย IP นี้ ต้องอนุญาต
SELF_IP="$(curl -s -m 3 -H 'Metadata-Flavor: Google' http://169.254.169.254/computeMetadata/v1/instance/network-interfaces/0/access-configs/0/external-ip 2>/dev/null || true)"
[[ "$SELF_IP" =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]] && ALLOW_EXTRA="$ALLOW_EXTRA $SELF_IP/32"

die() { printf '\e[31m[FAIL]\e[0m %s\n' "$*" >&2; exit 1; }
ok()  { printf '\e[32m[ OK ]\e[0m %s\n' "$*"; }
[[ $EUID -eq 0 ]] || die "ต้องรันด้วย sudo"

install_self() {
  command -v ipset >/dev/null || { apt-get update -q && apt-get -yq install ipset; }
  mkdir -p /etc/geo-firewall
  [[ "$(readlink -f "$0")" == "$SELF" ]] || install -m 700 "$0" "$SELF"
}

# แปลงรายการ APNIC (IP เริ่มต้น + จำนวน) เป็น CIDR ของประเทศที่กำหนด
apnic_to_cidr() {
  python3 -c '
import sys, ipaddress
cc = sys.argv[1].upper()
for line in sys.stdin:
    p = line.strip().split("|")
    if len(p) > 6 and p[1] == cc and p[2] == "ipv4" and p[6] in ("allocated", "assigned"):
        start = ipaddress.IPv4Address(p[3])
        end = start + int(p[4]) - 1
        for net in ipaddress.summarize_address_range(start, end):
            print(net)
' "$COUNTRY"
}

fetch_zone() {
  local tmp n; tmp=$(mktemp)
  if curl -fsSL --max-time 180 "$APNIC_URL" | apnic_to_cidr > "$tmp"; then
    n=$(grep -cE '^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+/[0-9]+$' "$tmp" || true)
    if (( n > 200 )); then
      install -m 644 "$tmp" "$ZONE_FILE"; rm -f "$tmp"; ok "ดาวน์โหลดช่วง IP ไทย $n ช่วง (APNIC)"; return 0
    fi
    echo "  APNIC ให้ข้อมูลผิดปกติ ($n บรรทัด) ลองแหล่งสำรอง"
  else
    echo "  ดาวน์โหลดจาก APNIC ไม่สำเร็จ ลองแหล่งสำรอง"
  fi
  if curl -fsSL --max-time 60 "$FALLBACK_URL" -o "$tmp"; then
    n=$(grep -cE '^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+/[0-9]+$' "$tmp" || true)
    if (( n > 200 )); then
      install -m 644 "$tmp" "$ZONE_FILE"; rm -f "$tmp"; ok "ดาวน์โหลดช่วง IP ไทย $n ช่วง (ipdeny)"; return 0
    fi
  fi
  rm -f "$tmp"
  [[ -s "$ZONE_FILE" ]] && { echo "  ใช้รายการเดิมที่มีอยู่แล้ว"; return 0; }
  die "ดาวน์โหลดรายการ IP ไทยไม่สำเร็จจากทุกแหล่ง"
}

load_set() {
  [[ -s "$ZONE_FILE" ]] || fetch_zone
  ipset create "$SET" hash:net family inet -exist
  ipset create "${SET}_tmp" hash:net family inet -exist
  ipset flush "${SET}_tmp"
  grep -E '^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+/[0-9]+$' "$ZONE_FILE" \
    | sed "s/^/add ${SET}_tmp /" | ipset restore -exist
  ipset swap "${SET}_tmp" "$SET"
  ipset destroy "${SET}_tmp"
}

build_chain() {
  iptables -N "$CHAIN" 2>/dev/null || iptables -F "$CHAIN"
  iptables -A "$CHAIN" -i lo -j RETURN
  iptables -A "$CHAIN" -m conntrack --ctstate ESTABLISHED,RELATED -j RETURN
  iptables -A "$CHAIN" -s 169.254.169.254 -j RETURN          # metadata/DHCP ของ cloud
  iptables -A "$CHAIN" -s 10.0.0.0/8 -j RETURN               # เครือข่ายภายในของ cloud
  iptables -A "$CHAIN" -m set --match-set "$SET" src -j RETURN
  for net in $ALLOW_EXTRA; do iptables -A "$CHAIN" -s "$net" -j RETURN; done
  iptables -A "$CHAIN" -m limit --limit 30/min --limit-burst 60 -j LOG --log-prefix "GEO-BLOCK: " --log-level 4
  iptables -A "$CHAIN" -j DROP
  iptables -C INPUT -j "$CHAIN" 2>/dev/null || iptables -I INPUT 1 -j "$CHAIN"

  # IPv6: ไม่มีรายการไทยแบบ v6 ในสคริปต์นี้ จึงปิดพอร์ตบริการทาง v6 ทั้งหมด
  if [[ -n "$(ip -6 addr show scope global 2>/dev/null)" ]]; then
    ip6tables -N "$CHAIN" 2>/dev/null || ip6tables -F "$CHAIN"
    ip6tables -A "$CHAIN" -i lo -j RETURN
    ip6tables -A "$CHAIN" -m conntrack --ctstate ESTABLISHED,RELATED -j RETURN
    ip6tables -A "$CHAIN" -p ipv6-icmp -j RETURN
    ip6tables -A "$CHAIN" -p tcp -m multiport --dports 22,80,443 -j DROP
    ip6tables -C INPUT -j "$CHAIN" 2>/dev/null || ip6tables -I INPUT 1 -j "$CHAIN"
  fi
}

remove_chain() {
  while iptables -D INPUT -j "$CHAIN" 2>/dev/null; do :; done
  iptables -F "$CHAIN" 2>/dev/null || true; iptables -X "$CHAIN" 2>/dev/null || true
  while ip6tables -D INPUT -j "$CHAIN" 2>/dev/null; do :; done
  ip6tables -F "$CHAIN" 2>/dev/null || true; ip6tables -X "$CHAIN" 2>/dev/null || true
}

check_my_ip() {
  local me="${SSH_CLIENT:-}"; me="${me%% *}"
  [[ -z "${me:-}" && -n "${SUDO_USER:-}" ]] && me=$(who -m 2>/dev/null | sed -n 's/.*(\(.*\)).*/\1/p')
  [[ -z "${me:-}" ]] && { echo "ไม่ทราบ IP ของคุณ (ไม่ได้ต่อผ่าน SSH?)"; return 0; }
  if ipset test "$SET" "$me" 2>/dev/null; then
    ok "IP ของคุณ ($me) อยู่ในไทย ปลอดภัย"
  else
    for net in $ALLOW_EXTRA; do [[ "$me" == "${net%/*}" ]] && return 0; done
    die "IP ของคุณ ($me) ไม่อยู่ในรายการไทย — ถ้าเปิดตอนนี้จะหลุดจากเซิร์ฟเวอร์ (ยกเลิกแล้ว)"
  fi
}

install_boot_service() {
  cat > /etc/systemd/system/geo-firewall.service <<EOF
[Unit]
Description=Thailand-only inbound firewall (ipset + iptables)
After=network-pre.target
Wants=network-pre.target
Before=fail2ban.service

[Service]
Type=oneshot
RemainAfterExit=yes
ExecStart=$SELF boot
ExecStop=$SELF off

[Install]
WantedBy=multi-user.target
EOF
  echo "17 4 * * 1 root $SELF update >> /var/log/geo-firewall.log 2>&1" > /etc/cron.d/geo-firewall
  systemctl daemon-reload
  systemctl enable geo-firewall.service >/dev/null
}

case "${1:-}" in
  apply)
    install_self
    load_set
    check_my_ip
    build_chain
    systemctl stop geo-rollback.timer 2>/dev/null || true
    systemctl reset-failed geo-rollback.service geo-rollback.timer 2>/dev/null || true
    systemd-run --quiet --unit=geo-rollback --on-active="$ROLLBACK_SEC" "$SELF" off
    ok "เปิด firewall เฉพาะไทยแล้ว"
    echo
    echo "  >>> ภายใน $((ROLLBACK_SEC/60)) นาที: เปิด terminal ใหม่ แล้ว SSH เข้ามาอีกครั้ง"
    echo "  >>> ถ้าเข้าได้ ให้รัน:  sudo bash $SELF confirm"
    echo "  >>> ถ้าไม่ยืนยัน firewall จะปิดตัวเอง (กันล็อกตัวเองออกจากเซิร์ฟเวอร์)"
    ;;
  confirm)
    systemctl stop geo-rollback.timer 2>/dev/null || true
    install_boot_service
    ok "ยืนยันแล้ว: firewall ทำงานถาวร เปิดเองหลังรีบูต อัปเดตรายการ IP ทุกวันจันทร์"
    ;;
  boot)
    load_set; build_chain ;;
  update)
    fetch_zone; load_set; ok "อัปเดตรายการ IP ไทยแล้ว" ;;
  off)
    remove_chain; ok "ปิด firewall เฉพาะไทยแล้ว" ;;
  status)
    if iptables -C INPUT -j "$CHAIN" 2>/dev/null; then ok "สถานะ: เปิดอยู่"; else echo "สถานะ: ปิดอยู่"; fi
    echo "จำนวนช่วง IP ไทย: $(ipset list "$SET" 2>/dev/null | grep -cE '^[0-9]' || echo 0)"
    iptables -L "$CHAIN" -v -n 2>/dev/null || true
    echo; echo "การบล็อกล่าสุด:"; journalctl -k --no-pager -n 10 --grep 'GEO-BLOCK' 2>/dev/null || true
    ;;
  *)
    sed -n '3,10p' "$0"; exit 1 ;;
esac
