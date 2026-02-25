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

## Notes

- Root filesystem is only 3.1MB - always install packages to SD
- Default credentials: root/root
- Network: 172.16.42.1/24
- Binaries on SD need `/sd/usr/lib` in LD_LIBRARY_PATH (set in rc.local)
- VERSION files track package versions for update detection
