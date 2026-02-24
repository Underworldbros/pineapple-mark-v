# WiFi Pineapple Mark V Configuration

Complete backup of WiFi Pineapple Mark V - SD card contents + internal flash configs.

## Contents

### Firmware (`flash/`)
- `mk5_factory.bin` - Factory firmware image (16.5MB)
- `upgrade-3.0.0.bin` - Upgrade firmware image (14.3MB)

### Internal Flash (`internal-flash/`)
Custom configurations from `/etc/` - non-stock settings:
- **etc/opkg.conf** - Modified OPKG with SD card as default storage
- **etc/nginx.conf** - Nginx web server with HTTPS (port 1471)
- **etc/ssl/** - SSL certificate and private key
- **etc/config/pineap** - PineAP settings
- **etc/config/system** - System config (hostname, timezone)
- **etc/pineapple/mk5.db** - Pineapple database
- **etc/pineapple/tracking_script** - Custom tracking script

### SD Card Contents (`sd-card/`)
- **Infusion Tarballs** - All 43+ infusions (*.tar.gz)
- **infusionmanager/** - Custom infusion manager module
- **terminal/** - Custom web-based terminal module
- **status/** - Status module
- **usr/bin/** - Pre-installed packages (htop, nmap, screen)

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
   # Mount your SD card on computer, copy all files from sd-card/ to SD root
   # Or use SCP after connecting:
   scp -r sd-card/* root@172.16.42.1:/sd/
   ```
6. **Enable Infusion Manager (CRITICAL):**
   ```bash
   ssh root@172.16.42.1
   ln -s /sd/infusionmanager /pineapple/components/infusions/infusionmanager
   ```
7. **Refresh web UI** - Infusion Manager tile should appear in the sidebar

### Option 2: Running System (already configured)
```bash
# Copy configs
scp internal-flash/etc/opkg.conf root@172.16.42.1:/etc/opkg.conf
scp internal-flash/etc/nginx.conf root@172.16.42.1:/etc/nginx/nginx.conf
scp internal-flash/etc/ssl/* root@172.16.42.1:/etc/ssl/

# Copy SD contents
scp -r sd-card/* root@172.16.42.1:/sd/

# Enable Infusion Manager
ssh root@172.16.42.1 "ln -s /sd/infusionmanager /pineapple/components/infusions/infusionmanager"

# Restart services
ssh root@172.16.42.1 "/etc/init.d/nginx restart"
```

## OPKG Usage

```bash
ssh root@172.16.42.1
opkg update
opkg install <package>  # Installs to SD by default
```

## Pen-Testing Tools

The Pineapple's infusion tools (ettercap, sslstrip, nmap, tcpdump, etc.) are bundled in the infusions themselves.

### Pre-built Packages (`packages/`)

This repository includes pre-built packages for ar71xx architecture:

| Package | Status | Notes |
|---------|--------|-------|
| mdk3 | ✅ Ready | Built from source for ar71xx |
| aircrack-ng | ⚠️ Partial | See notes below |

### Installing Pre-built Packages

#### Option 1: Infusion Manager (Recommended)

1. Go to your Pineapple's web interface
2. Navigate to **Infusion Manager**
3. Find the **Dependencies** category (orange color)
4. Click **Install** next to mdk3

The infusion manager will install mdk3 to `/sd/usr/bin/mdk3`.

#### Option 2: Manual

```bash
# Copy mdk3 to your Pineapple
scp packages/mdk3 root@172.16.42.1:/sd/usr/bin/
ssh root@172.16.42.1 "chmod +x /sd/usr/bin/mdk3"
```

### Known Dependency Issues

Some older tools require additional packages:

| Tool | Infusion | Status | Solution |
|------|----------|--------|----------|
| mdk3 | deauth, occupineapple | ✅ Ready | Install via Infusion Manager → Dependencies |
| aireplay-ng | deauth | ✅ Ready | Install via Infusion Manager → Dependencies |
| airmon-ng | deauth | ✅ Ready | Install via Infusion Manager → Dependencies |
| sslstrip | sslstrip, strip-n-inject | ❌ Missing | Requires Python Twisted |
| nbtscan | nbtscan | ❌ Missing | Needs compilation |
| hping3 | crafty | ❌ Missing | Needs compilation |
| p0f | p0f | ❌ Missing | Needs compilation |
| reaver | wps | ❌ Missing | Needs compilation |
| bully | wps | ❌ Missing | Needs compilation |
| pixiewps | wps | ❌ Missing | Needs compilation |

### Building from Source

The OpenWRT SDK (Chaos Calmer 15.05) can be used to build these tools:

1. Download OpenWRT SDK:
```bash
wget https://archive.openwrt.org/chaos_calmer/15.05.1/ar71xx/generic/OpenWrt-SDK-15.05.1-ar71xx-generic_gcc-4.8-linaro_uClibc-0.9.33.2.Linux-x86_64.tar.bz2
```

2. Use the toolchain to cross-compile tools for mips32r2 (ar71xx)

Note: Pre-built packages for mipsel_24kc (newer routers) exist at https://github.com/xiv3r/openwrt-pentest but are NOT compatible with ar71xx.

## Default Access

- **HTTP:** http://172.16.42.1
- **HTTPS:** https://172.16.42.1:1471 (self-signed cert)

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
