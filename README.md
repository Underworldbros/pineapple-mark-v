# WiFi Pineapple Mark V Configuration

Complete backup of WiFi Pineapple Mark V - SD card contents + internal flash configs.

## Contents

### Firmware (`flash/`)
- `mk5_factory.bin` - Factory firmware image (16.5MB)
- `upgrade-3.0.0.bin` - Upgrade firmware image (14.3MB)

### Internal Flash (`internal-flash/`)
Custom configurations from `/etc/` - non-stock settings:
- **etc/opkg.conf** - Modified OPKG with Chaos Calmer repo + SD card as default storage
- **etc/nginx.conf** - Nginx web server with HTTPS (port 1471)
- **etc/rc.local** - Sets LD_LIBRARY_PATH for SD card binaries
- **etc/profile** - Adds SD bin paths to PATH
- **etc/ssl/** - SSL certificate and private key
- **etc/config/pineap** - PineAP settings
- **etc/config/system** - System config (hostname, timezone)
- **etc/config/wireless** - WiFi config with `disassoc_low_ack 0` on rogue AP
- **etc/init.d/php5-fastcgi** - PHP worker count reduced to 1 (saves ~18MB RAM)
- **etc/pineapple/mk5.db** - Pineapple database
- **etc/pineapple/tracking_script** - Custom tracking script

### SD Card Contents (`sd-card/`)
- **Infusion Tarballs** - All 43+ infusions (*.tar.gz)
  - **connectedclients-1.5.tar.gz** - DHCP Manager module with OUI database
- **infusionmanager/** - Custom infusion manager module with Dependencies category
- **wps/** - Updated WPS infusion with VERSION file support
- **usr/bin/** - Pre-installed packages (htop, nmap, screen)
- **Pre-built Packages** - Cross-compiled tools for ar71xx (mdk3, aireplay, reaver, bully, pixiewps, p0f, nbtscan)

### Scripts (`scripts/`)
- **install-cert.sh** - Auto-install SSL certificate on your computer

## Quick Restore

### Option 1: Fresh Flash + SD Card
1. Flash `flash/mk5_factory.bin` or `flash/upgrade-3.0.bin`
2. Power on and wait for boot
3. Connect via Ethernet - you'll get IP 172.16.42.2
4. **Enable SSH:** Go to http://172.16.42.1 → Settings → SSH Enable
5. **Copy SD card contents:**
   ```bash
   scp -r sd-card/* root@172.16.42.1:/sd/
   ```
6. **Enable Infusion Manager (CRITICAL):**
   ```bash
   ssh root@172.16.42.1
   ln -s /sd/infusionmanager /pineapple/components/infusions/infusionmanager
   ```
7. **Apply internal flash configs:**
   ```bash
   scp internal-flash/etc/init.d/php5-fastcgi root@172.16.42.1:/etc/init.d/php5-fastcgi
   ssh root@172.16.42.1 "/etc/init.d/php5-fastcgi restart"
   ```
8. **Refresh web UI** - Infusion Manager tile should appear

### Option 2: Running System
```bash
# Copy configs
scp internal-flash/etc/opkg.conf root@172.16.42.1:/etc/opkg.conf
scp internal-flash/etc/nginx.conf root@172.16.42.1:/etc/nginx/nginx.conf
scp internal-flash/etc/ssl/* root@172.16.42.1:/etc/ssl/
scp internal-flash/etc/rc.local root@172.16.42.1:/etc/rc.local
scp internal-flash/etc/init.d/php5-fastcgi root@172.16.42.1:/etc/init.d/php5-fastcgi

# Copy SD contents
scp -r sd-card/* root@172.16.42.1:/sd/

# Enable Infusion Manager
ssh root@172.16.42.1 "ln -s /sd/infusionmanager /pineapple/components/infusions/infusionmanager"

# Restart services
ssh root@172.16.42.1 "/etc/init.d/nginx restart && /etc/init.d/php5-fastcgi restart"
```

### Post-install: Static Lease Support
Wire dnsmasq to pick up static leases on boot:
```bash
ssh root@172.16.42.1
grep -q 'dhcp-hostsfile' /var/etc/dnsmasq.conf || echo 'dhcp-hostsfile=/tmp/static_dhcp_hosts' >> /var/etc/dnsmasq.conf
/sd/connectedclients/decrypt_leases.sh
```

---

## DHCP Manager Module (Connected Clients v1.5)

The **DHCP Manager** infusion provides comprehensive management of DHCP leases, rogue AP configuration, and connected device monitoring.

### Features (v1.5)

- **DHCP Dashboard** - Real-time interface status and DHCP pool usage
- **Lease Management** - View, release, and renew DHCP leases with configurable durations (30 min to 7 days)
- **Device Discovery** - Real-time monitoring across all interfaces (br-lan, wlan0, wlan0-1) with ARP-based online/offline detection
- **MAC Vendor Lookup** - 86,098-entry OUI database via `grep` (on-demand, not loaded into memory)
- **Static DHCP Leases** - Assign fixed IPs to devices, stored encrypted on SD card
- **Rogue AP Configuration** - Quick network preset switching
- **Blacklist Management** - Block specific MAC addresses
- **DHCP Ranges & Logs** - View and export DHCP configuration and transaction logs

### What's New in v1.5

**Features:**
- **Static DHCP Leases** - Add/delete fixed IP assignments; stored XOR-encrypted on SD card, decrypted by shell script for dnsmasq
- **Configurable Renew Duration** - Select lease renewal duration from dropdown (30 min to 7 days)
- **Configurable Initial Lease Duration** - Adjust DHCP lease timeout (30 min to 7 days, default 12h)
- **Full Lease Visibility** - All leases shown with accurate online/offline status
- **Manual Cleanup Button** - Fully restarts dnsmasq to clear ghost leases

**Bug Fixes:**
- **Fixed Rogue AP reconnection** - Disabled `disassoc_low_ack` on wlan0; allows MAC randomization
- **Fixed lease release** - Full dnsmasq restart instead of SIGHUP (prevents ghost leases)
- **Fixed online/offline detection** - Checks ARP flags (0x2 = online, 0x0 = offline/incomplete)
- **Fixed duplicate WiFi leases** - Devices on wlan0-1 no longer appear on br-lan
- **Fixed IP validation** - Replaced `filter_var()` (unavailable on this PHP build) with regex
- **Fixed OUI grep** - Corrected shell escaping that prevented vendor matches
- **Fixed static lease handling** - Dynamic leases are never deleted; translated to static in UI only

**Performance:**
- **OUI lookup** - Single `grep` call instead of PHP line-by-line file scan (no memory spike)
- **Removed vendor lookup from lease polling** - Vendors only loaded on explicit button press
- **PHP workers reduced to 1** - Saves ~18MB RAM (from 3 workers to 1 in php5-fastcgi)
- **Small tile poll interval** - Increased from 5s to 30s (6x fewer PHP invocations)

### Vendor Lookup

The module includes a pre-cached OUI database at `/sd/connectedclients/oui_cache.txt` (86,098 entries).

- Vendors are **not** loaded on page load - click **"Load Vendors"** on the Leases tab
- Each lookup is a single `grep` against the file - no memory overhead
- **Randomized** = locally administered MAC bit set (modern phones with MAC privacy)
- **Unknown** = real OUI not in database

#### Updating the OUI Database
```bash
curl -O https://github.com/Ringmast4r/OUI-Master-Database/raw/master/LISTS/master_oui.txt
scp master_oui.txt root@172.16.42.1:/sd/connectedclients/oui_cache.txt
```

### Static DHCP Leases

Assign fixed IPs to specific devices. Leases survive reboots via encrypted SD card storage.

**Lease Behavior:**
- Dynamic leases come from `/tmp/dhcp.leases` (managed by dnsmasq)
- Static leases are stored in the module (`/sd/connectedclients/static_leases.dat`) - deleted on module uninstall
- The UI translates dynamic to static when the same MAC exists in the static list - visually only, the dynamic lease remains in `/tmp/dhcp.leases`
- Dynamic leases are only removed by:
  - Natural expiration
  - Release button in the manager
  - Cleanup button in the manager
  - Manual external deletion (SSH, etc.)

**How it works:**
- PHP encrypts each lease (XOR with MD5 of root password hash) and appends to `/sd/connectedclients/static_leases.dat`
- `/sd/connectedclients/decrypt_leases.sh` decrypts at runtime → outputs `dhcp-host=` lines to `/tmp/static_dhcp_hosts`
- dnsmasq reads `/tmp/static_dhcp_hosts` via `dhcp-hostsfile` directive

**Adding a static lease:**
1. Go to **DHCP Manager** → **Static** tab
2. Click **"Add Static Lease"**
3. Enter MAC, IP, and optional hostname → click **Add**
4. dnsmasq reloads automatically

**Manual SSH management:**
```bash
# View decrypted leases
/sd/connectedclients/decrypt_leases.sh && cat /tmp/static_dhcp_hosts

# Clear all static leases
echo -n > /sd/connectedclients/static_leases.dat
```

---

## Pre-built Packages

Cross-compiled packages for ar71xx (MIPS):

| Package | Status | Description |
|---------|--------|-------------|
| mdk3 | Ready | WiFi attack tool |
| aireplay-ng | Ready | Part of aircrack-ng |
| reaver | Ready | WPS brute force (v1.6.6) |
| bully | Ready | WPS brute force alternative |
| pixiewps | Ready | Pixie Dust attack |
| p0f | Ready | Passive OS fingerprinting |
| nbtscan | Ready | NetBIOS scanner |

### Installing via Infusion Manager

1. Go to **Infusion Manager** → **Dependencies** category (orange)
2. Click **Install** next to any tool

Auto-installed dependencies:

| Infusion | Auto-installs |
|----------|---------------|
| deauth | mdk3, aireplay |
| occupineapple | mdk3, aireplay |
| wps | reaver, bully, pixiewps |
| strip-n-inject | sslstrip |

---

## OPKG Usage

```bash
ssh root@172.16.42.1
opkg update
opkg install <package> --dest sd  # Installs to SD card
```

---

## Default Access

- **HTTP:** http://172.16.42.1
- **HTTPS:** https://172.16.42.1:1471 (self-signed cert)
- **SSH:** `sshpass -p "root" ssh root@172.16.42.1`

## SSL Certificate

To avoid browser warnings:

1. **Download cert:** Visit `http://172.16.42.1/ssl-cert.php` → save as `pineapple.crt`
2. **Import to OS:**
   - **Windows:** Double-click → Install → Trusted Root CA
   - **macOS:** Keychain Access → File → Import → Set to "Always Trust"
   - **Linux:** Copy to `/usr/local/share/ca-certificates/` → run `update-ca-certificates`

Or auto-install:
```bash
curl -O https://raw.githubusercontent.com/Underworldbros/pineapple-mark-v/main/scripts/install-cert.sh
chmod +x install-cert.sh && ./install-cert.sh
```

---

## Known Issues & Troubleshooting

### High Memory Usage (~90%)
Normal for this hardware. The Pineapple Mark V has only 64MB RAM. With nginx, hostapd, wpa_supplicant, netifd, and PHP workers the baseline is ~50MB. Swap is configured on the SD card to handle overflow.

Mitigations applied:
- PHP workers reduced from 3 to 1 (`/etc/init.d/php5-fastcgi`)
- Tile auto-refresh reduced from 5s to 30s
- OUI lookup uses `grep` instead of loading file into PHP memory

### WiFi Reconnection After Standby - FIXED

**Issue:** Devices with MAC randomization fail to reconnect to rogue AP after standby.

**Fix:** `disassoc_low_ack 0` set on wlan0 in `/etc/config/wireless`. Persists across reboots.

---

## Notes

- Root filesystem is only 3.1MB - always install packages to SD (`--dest sd`)
- Default credentials: `root` / `root`
- Network: `172.16.42.1/24`
- Binaries on SD need `/sd/usr/lib` in `LD_LIBRARY_PATH` (set in `rc.local`)
- Static lease encryption key is derived from root password hash - changing the root password will break existing static leases (clear and re-add them)
