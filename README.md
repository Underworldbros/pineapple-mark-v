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
- **etc/pineapple/mk5.db** - Pineapple database
- **etc/pineapple/tracking_script** - Custom tracking script

### SD Card Contents (`sd-card/`)
- **Infusion Tarballs** - All 43+ infusions (*.tar.gz)
  - **connectedclients-1.5.tar.gz** - DHCP Manager module (2.5MB with OUI database) - v1.5 with configurable renew durations and stale lease filtering
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
7. **Refresh web UI** - Infusion Manager tile should appear

### Option 2: Running System
```bash
# Copy configs
scp internal-flash/etc/opkg.conf root@172.16.42.1:/etc/opkg.conf
scp internal-flash/etc/nginx.conf root@172.16.42.1:/etc/nginx/nginx.conf
scp internal-flash/etc/ssl/* root@172.16.42.1:/etc/ssl/
scp internal-flash/etc/rc.local root@172.16.42.1:/etc/rc.local

# Copy SD contents
scp -r sd-card/* root@172.16.42.1:/sd/

# Enable Infusion Manager
ssh root@172.16.42.1 "ln -s /sd/infusionmanager /pineapple/components/infusions/infusionmanager"

# Restart services
ssh root@172.16.42.1 "/etc/init.d/nginx restart"
```

## DHCP Manager Module (Connected Clients v1.5)

The **DHCP Manager** infusion provides comprehensive management of DHCP leases, rogue AP configuration, and connected device monitoring.

### Features (v1.5)

- **DHCP Dashboard** - Real-time interface status and DHCP pool usage
- **Lease Management** - View, release, and renew DHCP leases with configurable renewal durations (30 min to 7 days)
- **Device Discovery** - Real-time monitoring of connected clients across all network interfaces (br-lan, wlan0, wlan0-1) with automatic stale lease filtering
- **MAC Vendor Lookup** - Comprehensive OUI database (86,098 entries) identifies device manufacturers
- **Rogue AP Configuration** - Quick switching between home and business network presets
- **Blacklist Management** - Block specific MAC addresses from connecting
- **DHCP Ranges & Logs** - View and export DHCP configuration and transaction logs

### What's New in v1.5

**Features:**
- **Configurable Renew Duration** - Select lease renewal duration from dropdown (30 min to 7 days)
- **Configurable Initial Lease Duration** - Adjust DHCP lease timeout in tab header (30 min to 7 days, default 12h)
- **Full Lease Visibility** - All leases shown with online/offline status (no auto-hiding)
- **Manual Cleanup Button** - "Cleanup Offline" button fully restarts dnsmasq to remove stale leases

**Bug Fixes:**
- **Fixed Rogue AP reconnection** - Disabled aggressive `disassoc_low_ack` on wlan0 to allow MAC randomization
- **Fixed lease release** - Now fully restarts dnsmasq instead of reload-only (prevents ghost leases)
- **Fixed online/offline detection** - Checks ARP flags (0x2 = online, 0x0 = offline)
- **Fixed duplicate WiFi leases** - Devices on wlan0-1 no longer duplicate on br-lan
- **Fixed stale lease cleanup** - Only removes truly offline devices from leases file

### Quick Start

1. Install the module via Infusion Manager
2. Click the **DHCP Manager** tile in the web UI
3. To identify device vendors:
   - Go to **DHCP Manager** → **Leases** tab
   - Leases load instantly
   - Click **"Load Vendors"** button to show device manufacturer names
   - Vendor status appears in the "Vendor" column:
     - **Device name** = Recognized manufacturer (Samsung, Apple, ASUS, etc.)
     - **🔒 Randomized** = MAC randomization enabled (privacy feature)
     - **❓ Not found** = Real OUI but not in database

### Vendor Lookup

The module includes a pre-cached OUI database with 86,098 MAC vendor entries (sources: Samsung, Apple, Intel, Cisco, etc.).

#### Using Vendor Lookup

1. Click **Leases** tab in DHCP Manager
2. Leases load instantly without vendor names (fast!)
3. Click **"Load Vendors"** button to asynchronously populate device manufacturer names
4. Vendor status appears for each device:
   - **Recognized vendors** = Device manufacturer name (Samsung, Apple, ASUS, Intel, etc.)
   - **🔒 Randomized** = Device uses MAC address randomization (common on modern phones for privacy)
   - **❓ Not found** = Real OUI prefix but not in the 86,098-entry database

#### Understanding Vendor Status

- **Vendor Name Found** - Device is using its real MAC address and matches OUI database
- **🔒 Randomized** - Device intentionally changes its MAC for privacy (modern iPhones, Android phones)
  - These are not real manufacturer OUIs, so they'll never be identified
  - This is a security feature, not a bug
- **❓ Not Found** - Device uses real MAC but manufacturer not in current database (add new OUI file to fix)

#### Updating the OUI Database

The vendor database is manually updated:

1. **Download a fresh OUI list**:
   ```bash
   curl -O https://github.com/Ringmast4r/OUI-Master-Database/raw/master/LISTS/master_oui.txt
   ```

2. **Upload to pineapple** (replace the cached OUI file):
   ```bash
   scp master_oui.txt root@172.16.42.1:/sd/connectedclients/oui_cache.txt
   ```

3. **Refresh the module** - Navigate away from Leases tab and back to reload vendor data

**Note:** The OUI database is stored at `/sd/connectedclients/oui_cache.txt` and includes 86,098 MAC vendor entries. Vendors are loaded on-demand when you click "Load Vendors", so the page loads instantly without waiting for database lookups.

### Static DHCP Leases

You can assign fixed IP addresses to specific devices using static DHCP leases. This is useful for:
- Always-on servers or network appliances
- Devices that need consistent IP addresses
- Testing purposes

#### Adding Static Leases

1. Go to **DHCP Manager** → **Static** tab
2. Click **"Add Static Lease"** button
3. Fill in:
   - **MAC:** Device MAC address (e.g., `aa:bb:cc:dd:ee:ff`)
   - **IP:** Fixed IP to assign (e.g., `172.16.42.100`)
   - **Hostname:** (Optional) DNS name for the device
4. Click **Add**
5. The device will be assigned the fixed IP on next connection

**Note:** Static leases take effect immediately after creation. If the device is already connected, it may need to release and re-request its lease (renew) to get the static IP.

#### Managing Static Leases

- **View all static leases** - They appear in the **Static** tab with their MAC, IP, and hostname
- **Delete a lease** - Click **Delete** next to the lease
- **Configuration file** - Static leases are stored in `/etc/dnsmasq.d/static_dhcp` (editable via SSH if needed)

## Pre-built Packages

This repository includes cross-compiled packages for ar71xx (MIPS):

| Package | Status | Description |
|---------|--------|-------------|
| mdk3 | ✅ Ready | WiFi attack tool |
| aireplay-ng | ✅ Ready | Part of aircrack-ng (aireplay, airmon, airodump) |
| reaver | ✅ Ready | WPS brute force (v1.6.6) |
| bully | ✅ Ready | WPS brute force alternative |
| pixiewps | ✅ Ready | Pixie Dust attack |
| p0f | ✅ Ready | Passive OS fingerprinting |
| nbtscan | ✅ Ready | NetBIOS scanner |

### Installing via Infusion Manager

1. Go to **Infusion Manager** in the web UI
2. Find the **Dependencies** category (orange)
3. Click **Install** next to any tool

The installer will:
- Extract the package to `/sd/<name>/`
- Copy binaries to `/sd/usr/sbin/`
- Copy VERSION file for version tracking
- Auto-install dependencies when you install infusions (e.g., installing WPS auto-installs reaver, bully, pixiewps)

### Auto-Dependencies

When you install these infusions, dependencies are auto-installed:

| Infusion | Auto-installs |
|----------|---------------|
| deauth | mdk3, aireplay |
| occupineapple | mdk3, aireplay |
| wps | reaver, bully, pixiewps |
| strip-n-inject | sslstrip |

## OPKG Usage

```bash
ssh root@172.16.42.1
opkg update
opkg install <package> --dest sd  # Installs to SD
```

**Note:** The Chaos Calmer repo in opkg.conf requires internet access.

## Default Access

- **HTTP:** http://172.16.42.1
- **HTTPS:** https://172.16.42.1:1471 (self-signed cert)
- **SSH:** ssh root@172.16.42.1

## SSL Certificate

The Pineapple uses a self-signed SSL certificate. To avoid browser warnings:

1. **Download cert:** Visit `http://172.16.42.1/ssl-cert.php` and save as `pineapple.crt`
2. **Import to OS:**
   - **Windows:** Double-click cert → Install → Trusted Root CA
   - **macOS:** Keychain Access → File → Import → Set to "Always Trust"
   - **Linux:** Copy to `/usr/local/share/ca-certificates/` and run `update-ca-certificates`
   - **Firefox:** Settings → Privacy → View Certificates → Import (manual per-browser)

Or use the auto-install script:
```bash
curl -O https://raw.githubusercontent.com/Underworldbros/pineapple-mark-v/main/scripts/install-cert.sh
chmod +x install-cert.sh
./install-cert.sh
```

## Known Issues & Troubleshooting

### WiFi Connection Issues After Device Standby - ✅ FIXED

**Issue:** Phones connecting to the rogue AP (wlan0) fail to reconnect after standby/sleep, especially with MAC randomization enabled.

**Root Cause:** The hostapd configuration was set to `disassoc_low_ack=1` on wlan0, which automatically disconnects clients when frame ACK rates drop. This was too aggressive for devices that:
- Use MAC randomization (phones with privacy features)
- Have intermittent connectivity after waking from standby
- Reconnect with a different MAC address

**Fix Applied:** The `disassoc_low_ack` setting has been disabled on wlan0 (set to 0) in `/etc/config/wireless`. This change:
- ✅ Allows phones with MAC randomization to reconnect after standby
- ✅ Persists across reboots (set in UCI wireless config)
- ✅ Keeps `disassoc_low_ack=1` on the legitimate AP (wlan0-1) to prevent spam clients

**Implementation:**
- Modified `/etc/config/wireless` to add `option disassoc_low_ack 0` to the Pineapple5_167C interface
- Restarted network services to apply changes
- Config is now part of the backup in `internal-flash/etc/config/wireless`

## Notes

- Root filesystem is only 3.1MB - always install packages to SD
- Default credentials: root/root
- Network: 172.16.42.1/24
- Binaries on SD need `/sd/usr/lib` in LD_LIBRARY_PATH (set in rc.local)
- VERSION files track package versions for update detection
