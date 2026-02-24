# WiFi Pineapple Mark V Configuration

Complete backup of SD card contents for WiFi Pineapple Mark V.

## Contents

### Firmware (`flash/`)
- `mk5_factory.bin` - Factory firmware image
- `upgrade-3.0.0.bin` - Upgrade firmware image

### SD Card Contents (`sd-card/`)
- **Infusion Tarballs** - All 43+ infusion installations (*.tar.gz)
- **infusionmanager/** - Custom infusion manager module
- **terminal/** - Custom web-based terminal module  
- **status/** - Status module
- **usr/bin/** - Pre-installed packages (htop, nmap, etc.)

## What's Installed

### Packages on SD
- htop - Process viewer
- nmap - Network scanner
- (additional packages available via opkg)

### Configuration Files (`config/`)
- `opkg.conf` - OPKG with SD card as default storage
- `nginx.conf` - Nginx with SSL (port 1471)

## Quick Restore

1. **Install packages to SD:**
   Copy `config/opkg.conf` to `/etc/opkg.conf` on Pineapple

2. **Enable HTTPS:**
   Copy `config/nginx.conf` to `/etc/nginx/nginx.conf`

3. **Install Infusions:**
   Upload tarballs from `sd-card/` via web interface

## OPKG Usage

```bash
opkg update
opkg install <package>  # Installs to SD by default
```

## Notes

- Root filesystem is only 3.1MB - always install packages to SD
- HTTPS available at https://172.16.42.1:1471
- HTTP redirects to HTTPS
