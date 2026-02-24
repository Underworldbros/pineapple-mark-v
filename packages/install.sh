#!/bin/sh
# Install pre-built packages for WiFi Pineapple Mark V
# Run this on your Pineapple: wget -O- https://raw.githubusercontent.com/Underworldbros/pineapple-mark-v/main/packages/install.sh | sh

PACKAGES_DIR="/sd/usr/bin"

echo "Installing pre-built packages..."

# Install mdk3
echo "Installing mdk3..."
if [ ! -f "$PACKAGES_DIR/mdk3" ]; then
    wget -q -O "$PACKAGES_DIR/mdk3" "https://raw.githubusercontent.com/Underworldbros/pineapple-mark-v/main/packages/mdk3"
    chmod +x "$PACKAGES_DIR/mdk3"
    echo "mdk3 installed successfully"
else
    echo "mdk3 already installed"
fi

echo "Done!"
