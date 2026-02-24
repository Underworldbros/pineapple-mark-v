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

## Default Access

- **HTTP:** http://172.16.42.1
- **HTTPS:** https://172.16.42.1:1471 (self-signed cert)

## Notes

- Root filesystem is only 3.1MB - always install packages to SD
- Default credentials: root/root
- Network: 172.16.42.1/24
