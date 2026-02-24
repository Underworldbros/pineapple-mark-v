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
1. Flash `flash/mk5_factory.bin` or `flash/upgrade-3.0.0.bin`
2. Copy `internal-flash/etc/opkg.conf` to `/etc/opkg.conf`
3. Copy `internal-flash/etc/nginx.conf` to `/etc/nginx/nginx.conf`
4. Install SSL certs from `internal-flash/etc/ssl/`
5. Upload infusion tarballs from `sd-card/` via web interface

### Option 2: Running System
```bash
# Copy configs
scp internal-flash/etc/opkg.conf root@172.16.42.1:/etc/opkg.conf
scp internal-flash/etc/nginx.conf root@172.16.42.1:/etc/nginx/nginx.conf
scp internal-flash/etc/ssl/* root@172.16.42.1:/etc/ssl/

# Restart services
ssh root@172.16.421 "/etc/init.d/nginx restart"
```

## OPKG Usage

```bash
ssh root@172.16.42.1
opkg update
opkg install <package>  # Installs to SD by default
```

## Pen-Testing Tools

The Pineapple's infusion tools (ettercap, sslstrip, nmap, tcpdump, etc.) are bundled in the infusions themselves.

**Known dependency issues:**
- Some older tools (mdk3, reaver, bully, pixiewps) are listed in the deprecated Pineapple cloud repo but the actual .ipk files return 404 (not found)
- These would need to be cross-compiled from source or found elsewhere

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
