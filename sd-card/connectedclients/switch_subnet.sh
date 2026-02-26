#!/bin/sh
# switch_subnet.sh - Switch wlan0 (Rogue AP) between subnet presets
# Usage: switch_subnet.sh [home|business|status]
#
# home     - wlan0 on 192.168.0.1/24 (separate from br-lan)
# business - wlan0 on 10.0.0.1/24 (separate from br-lan)
# status   - show current configuration

PRESET="$1"
CONFIG_FILE="/sd/connectedclients/rogue_preset.conf"

ensure_dhcp_ranges() {
    # Ensure all DHCP ranges exist in both configs (UCI generation is broken on this device)
    if ! grep -q 'dhcp-range=172.16.42' /etc/dnsmasq.conf 2>/dev/null; then
        cat >> /etc/dnsmasq.conf << 'EOF'
dhcp-range=172.16.42.100,172.16.42.249,255.255.255.0,12h
dhcp-range=192.168.0.100,192.168.0.249,255.255.255.0,12h
dhcp-range=10.0.0.100,10.0.0.249,255.255.255.0,12h
EOF
    fi
    if ! grep -q 'dhcp-range=172.16.42' /var/etc/dnsmasq.conf 2>/dev/null; then
        cat >> /var/etc/dnsmasq.conf << 'EOF'
dhcp-range=172.16.42.100,172.16.42.249,255.255.255.0,12h
dhcp-range=192.168.0.100,192.168.0.249,255.255.255.0,12h
dhcp-range=10.0.0.100,10.0.0.249,255.255.255.0,12h
dhcp-no-override
EOF
    fi
}

get_current_preset() {
    if [ -f "$CONFIG_FILE" ]; then
        cat "$CONFIG_FILE"
    else
        # If config file doesn't exist, detect from actual wlan0 IP
        WLAN0_IP=$(ifconfig wlan0 2>/dev/null | grep 'inet addr' | awk -F'inet addr:' '{print $2}' | awk '{print $1}')
        if [ -z "$WLAN0_IP" ]; then
            echo "default"
        elif echo "$WLAN0_IP" | grep -q "^192\.168\.0\."; then
            echo "home"
        elif echo "$WLAN0_IP" | grep -q "^10\.0\.0\."; then
            echo "business"
        else
            echo "default"
        fi
    fi
}

get_status() {
    CURRENT=$(get_current_preset)
    WLAN0_IP=$(ifconfig wlan0 2>/dev/null | grep 'inet addr' | awk -F'inet addr:' '{print $2}' | awk '{print $1}')
    BRLAN_IP=$(ifconfig br-lan 2>/dev/null | grep 'inet addr' | awk -F'inet addr:' '{print $2}' | awk '{print $1}')
    IN_BRIDGE=$(brctl show br-lan 2>/dev/null | grep -c wlan0)

    echo "{\"preset\":\"$CURRENT\",\"wlan0_ip\":\"$WLAN0_IP\",\"brlan_ip\":\"$BRLAN_IP\",\"in_bridge\":$IN_BRIDGE}"
}

setup_rogue_interface() {
    SUBNET_IP="$1"
    SUBNET_NET="$2"
    SUBNET_START="$3"
    SUBNET_LIMIT="$4"

    # Ensure DHCP ranges exist
    ensure_dhcp_ranges

    # Step 1: Remove wlan0 from br-lan bridge
    brctl delif br-lan wlan0 2>/dev/null

     # Step 2: Set up the 'rogue' network interface via UCI
     uci set network.rogue=interface
     uci set network.rogue.proto='static'
     uci set network.rogue.ipaddr="$SUBNET_IP"
     uci set network.rogue.netmask='255.255.255.0'
     uci commit network

     # Step 3: Set up DHCP for rogue interface (wlan0 will connect to this)
     uci set dhcp.rogue=dhcp
     uci set dhcp.rogue.interface='rogue'
     uci set dhcp.rogue.start="$SUBNET_START"
     uci set dhcp.rogue.limit="$SUBNET_LIMIT"
     uci set dhcp.rogue.leasetime='12h'
     uci set dhcp.rogue.force='1'
     uci delete dhcp.rogue.dhcp_option 2>/dev/null
     uci add_list dhcp.rogue.dhcp_option="3,$SUBNET_IP"
     uci add_list dhcp.rogue.dhcp_option="6,$SUBNET_IP"
     uci commit dhcp

     # Step 4: Set up firewall zone and forwarding
     # Remove old rogue zone/forwarding if exists
     remove_firewall_rogue

     # Add rogue zone
     uci add firewall zone
     uci set firewall.@zone[-1].name='rogue'
     uci set firewall.@zone[-1].network='rogue'
     uci set firewall.@zone[-1].input='ACCEPT'
     uci set firewall.@zone[-1].output='ACCEPT'
     uci set firewall.@zone[-1].forward='ACCEPT'

     # Add forwarding rogue -> wan (internet access for victims)
     uci add firewall forwarding
     uci set firewall.@forwarding[-1].src='rogue'
     uci set firewall.@forwarding[-1].dest='wan'

     # Add forwarding wan -> rogue (for responses)
     uci add firewall forwarding
     uci set firewall.@forwarding[-1].src='wan'
     uci set firewall.@forwarding[-1].dest='rogue'

     uci commit firewall

     # Step 5: Remove wlan0 from bridge and apply IP manually (no UCI wireless changes needed)
     brctl delif br-lan wlan0 2>/dev/null
     sleep 0.5
     
     # Step 6: Apply IP directly to wlan0
     ifconfig wlan0 "$SUBNET_IP" netmask 255.255.255.0 up
     
     # Step 7: Remove bridge from hostapd config 
     sed -i 's/bridge=br-lan/#bridge=br-lan/' /var/run/hostapd-phy0.conf
     
     # Step 8: Add NAT for rogue subnet BEFORE restarting services
     iptables -t nat -D POSTROUTING -s "$SUBNET_NET" -j MASQUERADE 2>/dev/null
     iptables -t nat -A POSTROUTING -s "$SUBNET_NET" -o wlan1 -j MASQUERADE

     # Step 9: Ensure IP forwarding
     echo 1 > /proc/sys/net/ipv4/ip_forward

     # Step 10: Block DHCP from wlan1 to prevent home router interference
     iptables -D FORWARD -i wlan1 -p udp --sport 67 --dport 68 -j DROP 2>/dev/null
     iptables -I FORWARD -i wlan1 -p udp --sport 67 --dport 68 -j DROP

     # Step 11: Ensure DHCP ranges exist in dnsmasq.conf
     if ! grep -q 'dhcp-range=172.16.42' /etc/dnsmasq.conf; then
         cat >> /etc/dnsmasq.conf << 'EOF'

# DHCP ranges
dhcp-range=172.16.42.100,172.16.42.249,255.255.255.0,12h
dhcp-range=192.168.0.100,192.168.0.249,255.255.255.0,12h
dhcp-range=10.0.0.100,10.0.0.249,255.255.255.0,12h
EOF
     fi

     # Ensure dhcp-no-override is set
     if ! grep -q 'dhcp-no-override' /etc/dnsmasq.conf; then
         echo 'dhcp-no-override' >> /etc/dnsmasq.conf
     fi

     # Step 12: Restart dnsmasq FIRST to update DHCP config
     /etc/init.d/dnsmasq restart
     sleep 2

     # Step 13: Restart hostapd to apply bridge config changes
     killall -9 hostapd 2>/dev/null
     sleep 1
     /etc/init.d/hostapd start 2>/dev/null
     sleep 2
     
     # Step 14: Reload network config LAST to make system recognize all changes
     /etc/init.d/network reload 2>/dev/null
     sleep 2
}

remove_firewall_rogue() {
    # Remove all firewall zones named 'rogue'
    while true; do
        FOUND=0
        IDX=0
        for zone in $(uci show firewall 2>/dev/null | grep '=zone$' | sed 's/=zone//'); do
            NAME=$(uci get "${zone}.name" 2>/dev/null)
            if [ "$NAME" = "rogue" ]; then
                uci delete "$zone" 2>/dev/null
                FOUND=1
                break
            fi
            IDX=$((IDX+1))
        done
        [ "$FOUND" = "0" ] && break
    done

    # Remove all forwardings with src or dest 'rogue'
    while true; do
        FOUND=0
        for fwd in $(uci show firewall 2>/dev/null | grep '=forwarding$' | sed 's/=forwarding//'); do
            SRC=$(uci get "${fwd}.src" 2>/dev/null)
            DEST=$(uci get "${fwd}.dest" 2>/dev/null)
            if [ "$SRC" = "rogue" ] || [ "$DEST" = "rogue" ]; then
                uci delete "$fwd" 2>/dev/null
                FOUND=1
                break
            fi
        done
        [ "$FOUND" = "0" ] && break
    done
}

restore_default() {
    # Ensure DHCP ranges exist
    ensure_dhcp_ranges

    # Step 1: Remove rogue network config
    uci delete network.rogue 2>/dev/null
    uci commit network

    # Step 2: Point wlan0 back to lan
    uci set wireless.@wifi-iface[0].network='lan'
    uci commit wireless

    # Step 3: Remove rogue DHCP config
    uci delete dhcp.rogue 2>/dev/null
    uci commit dhcp

    # Step 4: Remove rogue firewall rules
    remove_firewall_rogue
    uci commit firewall

    # Step 6: Remove rogue NAT rules (restore to just lan->wan)
    iptables -t nat -D POSTROUTING -s 192.168.0.0/24 -o wlan1 -j MASQUERADE 2>/dev/null
    iptables -t nat -D POSTROUTING -s 10.0.0.0/24 -o wlan1 -j MASQUERADE 2>/dev/null

    # Step 7: Remove DHCP block from wlan1 for Default mode
    iptables -D FORWARD -i wlan1 -p udp --sport 67 --dport 68 -j DROP 2>/dev/null

    # Step 8: Restore bridge in hostapd config - add bridge=br-lan after ssid line
    sed -i '/ssid=Pineapple5_167C/a bridge=br-lan' /var/run/hostapd-phy0.conf

    # Step 7: Remove IP from wlan0 and add it back to bridge
    ifconfig wlan0 0.0.0.0 2>/dev/null
    brctl addif br-lan wlan0 2>/dev/null

    # Step 8: Ensure DHCP ranges exist in dnsmasq.conf (UCI may be broken)
    if ! grep -q 'dhcp-range=172.16.42' /etc/dnsmasq.conf; then
        cat >> /etc/dnsmasq.conf << 'EOF'

# DHCP ranges
dhcp-range=172.16.42.100,172.16.42.249,255.255.255.0,12h
dhcp-range=192.168.0.100,192.168.0.249,255.255.255.0,12h
dhcp-range=10.0.0.100,10.0.0.249,255.255.255.0,12h
EOF
    fi

    # Step 9: Restart dnsmasq
    /etc/init.d/dnsmasq restart
}

# Main
case "$PRESET" in
    home)
        setup_rogue_interface "192.168.0.1" "192.168.0.0/24" "100" "150"
        echo "home" > "$CONFIG_FILE"
        echo "Switched to Home preset (192.168.0.1/24)"
        ;;
    business)
        setup_rogue_interface "10.0.0.1" "10.0.0.0/24" "100" "150"
        echo "business" > "$CONFIG_FILE"
        echo "Switched to Business preset (10.0.0.1/24)"
        ;;
    status)
        get_status
        ;;
    *)
        echo "Usage: $0 [home|business|status]"
        exit 1
        ;;
esac
