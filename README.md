# WiFi Pineapple Mark 5 Configuration

Custom configurations and modules for the WiFi Pineapple Mark 5.

## What's Included

### Configurations
- `config/opkg.conf` - OPKG package manager config with SD card as default storage
- `config/nginx.conf` - Nginx web server config with SSL support

### Custom Modules
- `modules/infusionmanager/` - Infusion manager module
- `modules/terminal/` - Web-based terminal module

## Setup

1. **OPKG with SD Card Default:**
   Copy `config/opkg.conf` to `/etc/opkg.conf` on the Pineapple.

2. **Enable HTTPS:**
   Copy `config/nginx.conf` to `/etc/nginx/nginx.conf` and restart nginx.

## Package Installation

The configured opkg uses the OpenWRT Chaos Calmer 15.05.1 repository which has working ar71xx packages.

```bash
opkg update
opkg install <package>
```

Packages install to SD card by default.

## Notes

- Root filesystem is only 3.1MB - always install packages to SD
- Use `opkg install -d sd <package>` or the default SD destination
- Some packages from Chaos Calmer may have library version mismatches
