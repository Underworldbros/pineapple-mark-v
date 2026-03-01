#!/bin/bash
# Sync from WiFi Pineapple Mark V to local backup using scp
# Run from local machine

PINEAPPLE="root@172.16.42.1"
SCP="sshpass -p root scp -o HostKeyAlgorithms=+ssh-rsa -o PubkeyAcceptedKeyTypes=+ssh-rsa"
SSH="sshpass -p root ssh -o HostKeyAlgorithms=+ssh-rsa -o PubkeyAcceptedKeyTypes=+ssh-rsa"

echo "=== Syncing from Pineapple ==="

# Get list of SD card contents (exclude tarballs)
echo "Listing /sd/ contents..."
$SSH $PINEAPPLE "ls /sd/" | grep -v '\.tar\.gz$' > /tmp/sd_contents.txt

# Sync each directory from SD card
echo "Syncing /sd/ directories..."
for dir in $(cat /tmp/sd_contents.txt); do
    if [ -d "$dir" ] || [ -f "$dir" ]; then
        continue
    fi
    echo "  Syncing $dir..."
    $SCP -r $PINEAPPLE:/sd/$dir sd-card/ 2>/dev/null
done

# Sync infusion manager separately
echo "Syncing infusion manager..."
$SCP -r $PINEAPPLE:/sd/infusionmanager sd-card/

# Sync /etc (internal flash) 
echo "Syncing /etc/ (internal flash)..."
$SCP -r $PINEAPPLE:/etc/* internal-flash/etc/ 2>/dev/null

echo "=== Sync Complete ==="
