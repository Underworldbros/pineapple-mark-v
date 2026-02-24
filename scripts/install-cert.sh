#!/bin/bash
# Download and install Pineapple SSL certificate

PINEAPPLE="172.16.42.1"

echo "Downloading SSL certificate from Pineapple..."
curl -s -k "http://${PINEAPPLE}/ssl-cert.php" -o /tmp/pineapple.crt

if [ $? -eq 0 ]; then
    echo "Certificate downloaded to /tmp/pineapple.crt"
    
    # Detect OS and install cert
    if [ -f /etc/debian_version ]; then
        # Debian/Ubuntu
        echo "Installing on Debian/Ubuntu..."
        sudo cp /tmp/pineapple.crt /usr/local/share/ca-certificates/pineapple.crt
        sudo update-ca-certificates
    elif [ -f /etc/redhat-release ]; then
        # RedHat/CentOS/Fedora
        echo "Installing on RedHat/CentOS/Fedora..."
        sudo cp /tmp/pineapple.crt /etc/pki/ca-trust/source/anchors/pineapple.crt
        sudo update-ca-trust
    elif [ -d /etc/ca-certificates/trust-source/archlinux ]; then
        # Arch Linux
        echo "Installing on Arch Linux..."
        sudo cp /tmp/pineapple.crt /etc/ca-certificates/trust-source/anchors/
        sudo trust extract-compat
    elif [ "$(uname)" = "Darwin" ]; then
        # macOS
        echo "Installing on macOS..."
        sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain /tmp/pineapple.crt
    else
        echo "Manual installation required:"
        echo "  Copy /tmp/pineapple.crt to your system's trusted certificates"
    fi
    
    echo "Done! You may need to restart your browser."
else
    echo "Failed to download certificate"
fi
