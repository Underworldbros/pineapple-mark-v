#!/bin/sh
# Decrypt /sd/connectedclients/static_leases.dat -> /tmp/static_dhcp_hosts
# Called on boot/dnsmasq reload to apply static DHCP leases
#
# Encryption: XOR with repeating key, stored as hex
# Key = MD5 of root shadow hash
# Key bytes = ASCII value of each hex character in the MD5 string
#   '0'-'9' = 48-57,  'a'-'f' = 97-102

LEASE_FILE="/sd/connectedclients/static_leases.dat"
OUT_FILE="/tmp/static_dhcp_hosts"

# Always write output file so dnsmasq can include it
> "$OUT_FILE"

[ ! -f "$LEASE_FILE" ] && exit 0
[ ! -s "$LEASE_FILE" ] && exit 0

KEY=$(grep "^root:" /etc/shadow | cut -d: -f2 | md5sum | cut -c1-32)

awk -v key="$KEY" '
function hex2dec(h,    digits, i, val) {
    digits = "0123456789abcdef"
    val = 0
    for (i = 1; i <= length(h); i++)
        val = val * 16 + (index(digits, substr(h,i,1)) - 1)
    return val
}
function xorbits(a, b,    r, p) {
    r = 0; p = 1
    while (a > 0 || b > 0) {
        if ((a % 2) != (b % 2)) r += p
        a = int(a/2); b = int(b/2); p *= 2
    }
    return r
}
function kbyte(kc) {
    if (kc >= "0" && kc <= "9") return 48 + (index("0123456789", kc) - 1)
    if (kc == "a") return 97
    if (kc == "b") return 98
    if (kc == "c") return 99
    if (kc == "d") return 100
    if (kc == "e") return 101
    if (kc == "f") return 102
    return 0
}
{
    hexline = $0
    keylen = length(key)
    out = ""
    for (i = 1; i <= length(hexline); i += 2) {
        byte = hex2dec(substr(hexline, i, 2))
        ki   = ((int((i-1)/2)) % keylen) + 1
        dec  = xorbits(byte, kbyte(substr(key, ki, 1)))
        out  = out sprintf("%c", dec)
    }
    # out is now "mac,ip" or "mac,ip,hostname"
    n = split(out, parts, ",")
    if (n >= 2)
        print "dhcp-host=" parts[1] "," parts[2] (n >= 3 ? "," parts[3] : "")
}
' "$LEASE_FILE" >> "$OUT_FILE"
