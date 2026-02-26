<?php
// Auth validation
include_once('/pineapple/includes/api/auth.php');

// CSRF validation only for POST requests (write operations)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include_once('/pineapple/includes/api/csrf_check.php');
}

// ============================================================================
// INPUT VALIDATION HELPERS
// ============================================================================

/**
 * Validate MAC address format (XX:XX:XX:XX:XX:XX or XX-XX-XX-XX-XX-XX)
 */
function is_valid_mac($mac) {
    // Check if MAC address matches standard format
    return preg_match('/^([0-9a-fA-F]{2}[:-]){5}([0-9a-fA-F]{2})$/', $mac) === 1;
}

/**
 * Validate IPv4 address
 */
function is_valid_ipv4($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
}

/**
 * Validate hostname (alphanumeric, hyphen, period, underscore)
 */
function is_valid_hostname($hostname) {
    return preg_match('/^[a-zA-Z0-9._-]+$/', $hostname) === 1;
}

/**
 * Sanitize hostname for dnsmasq config file (remove special chars)
 */
function sanitize_hostname($hostname) {
    // Keep only alphanumeric, hyphen, period, underscore
    return preg_replace('/[^a-zA-Z0-9._-]/', '', $hostname);
}

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'get_dhcp_leases':
            echo get_dhcp_leases_from_log();
            break;
        case 'get_blacklisted_macs':
            echo get_blacklisted_macs();
            break;
        case 'remove_blacklisted_mac':
            echo remove_mac_from_blacklist($_POST['mac_address']);
            break;
        case 'add_blacklisted_mac':
            echo add_mac_to_blacklist($_POST['mac_address']);
            break;
        case 'get_iw_wlan0_clients':
            echo get_iw_wlan0_connected_clients();
            break;
        case 'get_iw_wlan0_1_clients':
            echo get_iw_wlan0_1_connected_clients();
            break;
        case 'deauthenticate_mac_wlan0':
            echo deauth_wlan0_connected_mac($_POST['deauth_wlan0_mac_address']);
            break;
        case 'deauthenticate_mac_wlan0_1':
            echo deauth_wlan0_1_connected_mac($_POST['deauth_wlan0_1_mac_address']);
            break;
        case 'disassociate_mac_wlan0':
            echo disassociate_wlan0_connected_mac($_POST['disassociate_wlan0_mac_address']);
            break;
        case 'disassociate_mac_wlan0_1':
            echo disassociate_wlan0_1_connected_mac($_POST['disassociate_wlan0_1_mac_address']);
            break;
        case 'get_rogue_config':
            echo get_rogue_config();
            break;
        case 'set_rogue_subnet':
            echo set_rogue_subnet($_POST['preset']);
            break;
        case 'get_brlan_clients':
            echo get_brlan_connected_clients();
            break;
        case 'get_rogue_clients':
            echo get_rogue_connected_clients();
            break;
        case 'get_dashboard':
            echo get_dashboard();
            break;
        case 'get_leases':
            echo get_leases();
            break;
        case 'get_leases_without_vendors':
            echo get_leases_without_vendors();
            break;
        case 'get_vendors_for_leases':
            echo get_vendors_for_leases($_POST['macs']);
            break;
        case 'release_lease':
            echo release_lease($_POST['mac']);
            break;
         case 'renew_lease':
            echo renew_lease($_POST['mac']);
            break;
        case 'cleanup_offline_leases':
            echo cleanup_offline_leases();
            break;
        case 'get_static_leases':
            echo get_static_leases();
            break;
        case 'add_static_lease':
            echo add_static_lease($_POST['mac'], $_POST['ip'], isset($_POST['hostname']) ? $_POST['hostname'] : '');
            break;
        case 'delete_static_lease':
            echo delete_static_lease($_POST['mac']);
            break;
        case 'get_dhcp_ranges':
            echo get_dhcp_ranges();
            break;
        case 'get_dhcp_log':
            echo get_dhcp_log();
            break;
        case 'clear_log':
            echo clear_dhcp_log();
            break;
        case 'export_leases':
            export_leases_csv();
            break;
        case 'set_initial_duration':
            echo set_initial_lease_duration($_POST['duration']);
            break;
    }
}

// This function parses the DHCP leases in /tmp/dhcp.leases file
// it is used to build the clients tab
function get_dhcp_leases_from_log(){
    $logs = array();
    array_push($logs, htmlspecialchars(file_get_contents('/tmp/dhcp.leases')));
    $html = json_encode($logs);
    return $html;
}

// Helper: Check if MAC is connected to wlan0-1 (secured AP)
function check_mac_on_wlan0_1($mac) {
    $mac_lower = strtolower($mac);
    $output = array();
    exec('iw dev wlan0-1 station dump 2>/dev/null | grep -i ' . escapeshellarg($mac_lower), $output);
    return !empty($output);
}

// Get leases WITHOUT vendor lookup (fast load) - includes wireless and ARP data
function get_leases_without_vendors(){
    $leases = array();
    $leases_file = '/tmp/dhcp.leases';
    $current_time = time();
    
    // Get DHCP lease durations from dnsmasq config (fallback)
    $default_lease_duration = 43200;  // Default 12 hours
    $dhcp_config = file_get_contents('/etc/dnsmasq.conf');
    if (preg_match('/dhcp-lease-max=(\d+)/', $dhcp_config, $matches)) {
        $default_lease_duration = (int)$matches[1];
    }
    
    // Get ARP table for online status (read file directly - faster than exec)
    $arp_table = array();
    $arp_content = file_get_contents('/proc/net/arp');
    if ($arp_content) {
        $arp_lines = explode("\n", $arp_content);
        foreach ($arp_lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, 'IP address') === 0) continue;
            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 4) {
                $mac_lower = strtolower($parts[3]);
                $ip = $parts[0];
                $arp_table[$mac_lower] = $ip;  // MAC => IP
            }
        }
    }
    
    // Get dnsmasq log for DHCP actions and DNS queries
    $dhcp_actions = get_dhcp_actions_from_log();
    $dns_queries = get_recent_dns_queries();
    
    // Get DHCP option data (device class, etc.)
    $dhcp_options = get_dhcp_client_info();
    
    if (file_exists($leases_file)) {
        $lines = file($leases_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode(' ', $line);
            if (count($parts) >= 3) {
                 $expiry_timestamp = (int)$parts[0];
                $mac = trim($parts[1]);
                $ip = trim($parts[2]);
                $hostname = count($parts) >= 4 ? trim($parts[3]) : 'unknown';
                
                // Get lease duration: stored value (5th field) or use default from dnsmasq config
                $lease_duration = (count($parts) >= 5 && is_numeric($parts[4])) ? (int)$parts[4] : $default_lease_duration;
                
                // Calculate lease info
                $time_remaining = $expiry_timestamp - $current_time;
                $lease_age = $lease_duration - $time_remaining;
                
                 // Determine network type and connection type from IP and wireless status
                $network_type = 'Unknown';
                $connection_type = 'Unknown';
                
                if (strpos($ip, '172.16.42') === 0) {
                    // 172.16.42.x could be Ethernet OR wlan0-1 (both on lan bridge)
                    // Check if this MAC is connected to wlan0-1
                    $is_wlan0_1 = check_mac_on_wlan0_1($mac);
                    if ($is_wlan0_1) {
                        $network_type = 'Secured AP (wlan0-1)';
                        $connection_type = 'WiFi (Secured)';
                    } else {
                        $network_type = 'Ethernet (br-lan)';
                        $connection_type = 'Ethernet';
                    }
                } elseif (strpos($ip, '192.168.0') === 0) {
                    $network_type = 'Rogue AP (Home)';
                    $connection_type = 'WiFi (Rogue)';
                } elseif (strpos($ip, '10.0.0') === 0) {
                    $network_type = 'Rogue AP (Business)';
                    $connection_type = 'WiFi (Rogue)';
                } elseif (strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
                    $network_type = 'Internet Sharing';
                    $connection_type = 'WiFi (Internet)';
                }
                
                // Check ARP status (online/offline)
                $mac_lower = strtolower($mac);
                $is_online = isset($arp_table[$mac_lower]) ? true : false;
                
                // Get additional data for this lease
                $last_dhcp_action = isset($dhcp_actions[$mac_lower]) ? $dhcp_actions[$mac_lower] : null;
                $recent_dns = isset($dns_queries[$mac_lower]) ? $dns_queries[$mac_lower] : null;
                $device_class = isset($dhcp_options[$mac_lower]['class']) ? $dhcp_options[$mac_lower]['class'] : null;
                $client_hostname = isset($dhcp_options[$mac_lower]['hostname']) ? $dhcp_options[$mac_lower]['hostname'] : null;
                
                // Session duration (from lease age)
                $session_duration = max(0, $lease_age);
                
                // NEW: Get lease modification time
                $lease_mtime = get_lease_mtime($mac);
                
                // NEW: Count DHCP renewals
                $renewal_count = count_dhcp_renewals($mac);
                
                // NEW: Get first seen and last seen timestamps
                $first_seen = get_first_seen($mac);
                $last_seen = get_last_seen($mac);
                
                $leases[] = array(
                    'mac' => $mac,
                    'ip' => $ip,
                    'hostname' => $hostname,
                    'vendor' => null,  // No vendor lookup yet
                    'expiry_timestamp' => $expiry_timestamp,
                    'expiry_readable' => date('Y-m-d H:i:s', $expiry_timestamp),
                    'time_remaining' => $time_remaining,
                    'time_remaining_readable' => format_time_remaining($time_remaining),
                    'lease_age' => max(0, $lease_age),
                    'lease_age_readable' => format_time_remaining(max(0, $lease_age)),
                    'lease_duration' => $lease_duration,
                    'lease_duration_readable' => format_duration($lease_duration),
                    'network_type' => $network_type,
                    'connection_type' => $connection_type,
                    'is_online' => $is_online,
                    'last_dhcp_action' => $last_dhcp_action,
                    'recent_dns' => $recent_dns,
                    'device_class' => $device_class,
                    'client_hostname' => $client_hostname,
                    'session_duration' => $session_duration,
                    'session_duration_readable' => format_time_remaining($session_duration),
                    'lease_mtime' => $lease_mtime,
                    'renewal_count' => $renewal_count,
                    'first_seen' => $first_seen,
                    'last_seen' => $last_seen
                );
            }
        }
    }
    
    return json_encode($leases);
}

// Format time remaining as human readable
function format_time_remaining($seconds) {
    if ($seconds < 0) {
        return 'Expired';
    }
    
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    if ($days > 0) {
        return $days . 'd ' . $hours . 'h';
    } elseif ($hours > 0) {
        return $hours . 'h ' . $minutes . 'm';
    } else {
        return $minutes . 'm';
    }
}

// Format duration as human readable
function format_duration($seconds) {
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    
    if ($days > 0) {
        return $days . 'd ' . $hours . 'h';
    } else {
        return $hours . 'h';
    }
}

// Get vendors for specific MACs (async call)
function get_vendors_for_leases($macs_json){
    if (!is_string($macs_json)) {
        return json_encode(array('error' => 'Invalid input'));
    }
    
    $macs = json_decode($macs_json, true);
    if (!is_array($macs)) {
        return json_encode(array('error' => 'Invalid MAC list'));
    }
    
    $vendors = array();
    foreach ($macs as $mac) {
        // Validate each MAC format
        if (!is_valid_mac($mac)) {
            $vendors[$mac] = 'Invalid MAC';
            continue;
        }
        $vendors[$mac] = get_mac_vendor($mac);
    }
    
    return json_encode($vendors);
}

// This function gets the mac addresses in karmas blacklist
function get_blacklisted_macs(){
    exec("pineapple karma list_macs", $mac_list);
    $html = json_encode($mac_list);
    return $html;
} 

// This function removes a mac address from karmas blacklist
function remove_mac_from_blacklist($mac){
    // Validate MAC format
    if (!is_valid_mac($mac)) {
        return json_encode(array('error' => 'Invalid MAC address format'));
    }
    
    // Use escapeshellarg for safe command execution
    exec('pineapple karma del_mac ' . escapeshellarg($mac), $output, $return_code);
    
    if ($return_code !== 0) {
        return json_encode(array('error' => 'Failed to remove MAC from blacklist'));
    }
    
    return json_encode(array('status' => 'removed', 'mac' => $mac));
}

// This function adds a mac address to karmas blacklist
function add_mac_to_blacklist($mac){
    // Validate MAC format
    if (!is_valid_mac($mac)) {
        return json_encode(array('error' => 'Invalid MAC address format'));
    }
    
    // Use escapeshellarg for safe command execution
    exec('pineapple karma add_mac ' . escapeshellarg($mac), $output, $return_code);
    
    if ($return_code !== 0) {
        return json_encode(array('error' => 'Failed to add MAC to blacklist'));
    }
    
    return json_encode(array('status' => 'added', 'mac' => $mac));
}

// This function gets the list of clients connected to wlan0 from iw
function get_iw_wlan0_connected_clients(){
    exec("iw dev wlan0 station dump | grep Station | awk '{print $2}'", $iw_connected_clients);
    $html = json_encode($iw_connected_clients);
    return $html;
}

// This function gets the list of clients connected to wlan0-1 from DHCP leases
// wlan0-1 is bridged to lan (172.16.42.x) so get clients from that network
function get_iw_wlan0_1_connected_clients(){
    // First, get MACs from iw to know which devices are actually connected to wlan0-1
    $iw_macs = array();
    exec("iw dev wlan0-1 station dump | grep Station | awk '{print $2}'", $iw_macs);
    
    // Trim whitespace and convert to lowercase for comparison
    $iw_macs_lower = array_map(function($mac) {
        return strtolower(trim($mac));
    }, $iw_macs);
    
    // Now get detailed info from DHCP leases for these MACs
    $leases_file = '/tmp/dhcp.leases';
    $clients = array();
    
    if (file_exists($leases_file)) {
        $lines = file($leases_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode(' ', $line);
            if (count($parts) >= 3) {
                $mac = $parts[1];
                $mac_lower = strtolower($mac);
                $ip = $parts[2];
                
                // Only include if this MAC is currently connected to wlan0-1 (found in iw output)
                if (in_array($mac_lower, $iw_macs_lower)) {
                    $hostname = count($parts) >= 4 ? trim($parts[3]) : '';
                    $clients[] = array(
                        'mac' => $mac, 
                        'ip' => $ip, 
                        'hostname' => $hostname,
                        'interface' => 'wlan0-1',
                        'network' => 'lan (172.16.42.x)'
                    );
                }
            }
        }
    }
    
    // If no DHCP info found, return just the MACs from iw
    if (empty($clients) && !empty($iw_macs)) {
        foreach ($iw_macs as $mac) {
            $clients[] = array(
                'mac' => $mac,
                'ip' => 'not in leases',
                'hostname' => '',
                'interface' => 'wlan0-1',
                'network' => 'lan (172.16.42.x)'
            );
        }
    }
    
    return json_encode($clients);
}

// This function gets clients on br-lan (ethernet/wired) from DHCP leases - only 172.16.42.x
// EXCLUDES wlan0-1 clients (even though they're on 172.16.42.x, they're wireless)
function get_brlan_connected_clients(){
    // First, get MACs connected to wlan0-1 so we can exclude them
    $wlan0_1_macs = array();
    exec("iw dev wlan0-1 station dump 2>/dev/null | grep Station | awk '{print $2}'", $wlan0_1_macs);
    $wlan0_1_macs_lower = array_map(function($mac) {
        return strtolower(trim($mac));
    }, $wlan0_1_macs);
    
    // Get ARP table to check which devices are on br-lan
    $arp_present = array();
    $arp_output = array();
    exec("cat /proc/net/arp | grep ' br-lan$'", $arp_output);
    foreach ($arp_output as $arp_line) {
        $parts = preg_split('/\s+/', trim($arp_line));
        if (count($parts) >= 4) {
            $mac = strtolower($parts[3]);
            // Include if MAC has any ARP entry on br-lan (means device is/was on this network)
            $arp_present[strtolower($mac)] = true;
        }
    }
    
    $leases_file = '/tmp/dhcp.leases';
    $clients = array();
    
    if (file_exists($leases_file)) {
        $lines = file($leases_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode(' ', $line);
            if (count($parts) >= 3) {
                $ip = $parts[2];
                // Only show br-lan clients (172.16.42.x)
                if (strpos($ip, '172.16.42.') === 0) {
                    $mac = trim($parts[1]);
                    $mac_lower = strtolower($mac);
                    
                    // EXCLUDE if this MAC is on wlan0-1
                    // EXCLUDE if not in ARP table for br-lan (stale leases)
                    if (!in_array($mac_lower, $wlan0_1_macs_lower) && isset($arp_present[$mac_lower])) {
                        $hostname = count($parts) >= 4 ? trim($parts[3]) : '';
                        $clients[] = array('mac' => $mac, 'ip' => $ip, 'hostname' => $hostname);
                    }
                }
            }
        }
    }
    
    return json_encode($clients);
}

// This function gets clients on rogue (wlan0) from DHCP leases - 192.168.0.x or 10.0.0.x
function get_rogue_connected_clients(){
    // Get ARP table to check which devices are on wlan0
    $arp_present = array();
    $arp_output = array();
    exec("cat /proc/net/arp | grep ' wlan0$'", $arp_output);
    foreach ($arp_output as $arp_line) {
        $parts = preg_split('/\s+/', trim($arp_line));
        if (count($parts) >= 4) {
            $mac = strtolower($parts[3]);
            // Include if MAC has any ARP entry on wlan0 (means device is/was on this network)
            $arp_present[strtolower($mac)] = true;
        }
    }
    
    $leases_file = '/tmp/dhcp.leases';
    $clients = array();
    
    if (file_exists($leases_file)) {
        $lines = file($leases_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode(' ', $line);
            if (count($parts) >= 3) {
                $ip = $parts[2];
                // Only show rogue clients (192.168.0.x or 10.0.0.x)
                if (strpos($ip, '192.168.0.') === 0 || strpos($ip, '10.0.0.') === 0) {
                    $mac = strtolower($parts[1]);
                    // Only include if in ARP table for wlan0 (filters out stale leases)
                    if (isset($arp_present[$mac])) {
                        $hostname = count($parts) >= 4 ? trim($parts[3]) : '';
                        $clients[] = array('mac' => $parts[1], 'ip' => $ip, 'hostname' => $hostname);
                    }
                }
            }
        }
    }
    
    return json_encode($clients);
}

// This function deauthenticates a mac address connected to wlan0 using hostapd_cli
function deauth_wlan0_connected_mac($mac){
   if (!is_valid_mac($mac)) {
       return json_encode(array('error' => 'Invalid MAC address format'));
   }
   exec('hostapd_cli -p /var/run/hostapd-phy0 -i wlan0 deauthenticate ' . escapeshellarg($mac) . ' 2>&1', $output, $return_code);
   
   if ($return_code !== 0) {
       return json_encode(array('error' => 'Failed to deauthenticate client', 'details' => implode("\n", $output)));
   }
   return json_encode(array('status' => 'deauthenticated', 'mac' => $mac));
}

// This function deauthenticates a mac address connected to wlan0-1 using hostapd_cli
function deauth_wlan0_1_connected_mac($mac){
   if (!is_valid_mac($mac)) {
       return json_encode(array('error' => 'Invalid MAC address format'));
   }
   exec('hostapd_cli -p /var/run/hostapd-phy0 -i wlan0-1 deauthenticate ' . escapeshellarg($mac) . ' 2>&1', $output, $return_code);
   
   if ($return_code !== 0) {
       return json_encode(array('error' => 'Failed to deauthenticate client', 'details' => implode("\n", $output)));
   }
   return json_encode(array('status' => 'deauthenticated', 'mac' => $mac));
}

// This function disassociates a mac address connected to wlan0 using hostapd_cli
function disassociate_wlan0_connected_mac($mac){
   if (!is_valid_mac($mac)) {
       return json_encode(array('error' => 'Invalid MAC address format'));
   }
   exec('hostapd_cli -p /var/run/hostapd-phy0 -i wlan0 disassociate ' . escapeshellarg($mac) . ' 2>&1', $output, $return_code);
   
   if ($return_code !== 0) {
       return json_encode(array('error' => 'Failed to disassociate client', 'details' => implode("\n", $output)));
   }
   return json_encode(array('status' => 'disassociated', 'mac' => $mac));
}

// This function disassociates a mac address connected to wlan0-1 using hostapd_cli
function disassociate_wlan0_1_connected_mac($mac){
   if (!is_valid_mac($mac)) {
       return json_encode(array('error' => 'Invalid MAC address format'));
   }
   exec('hostapd_cli -p /var/run/hostapd-phy0 -i wlan0-1 disassociate ' . escapeshellarg($mac) . ' 2>&1', $output, $return_code);
   
   if ($return_code !== 0) {
       return json_encode(array('error' => 'Failed to disassociate client', 'details' => implode("\n", $output)));
   }
   return json_encode(array('status' => 'disassociated', 'mac' => $mac));
}

// Get current rogue AP configuration status
function get_rogue_config(){
    exec('sh /sd/connectedclients/switch_subnet.sh status 2>&1', $output);
    return implode("\n", $output);
}

// Switch rogue AP subnet preset
function set_rogue_subnet($preset){
    // Validate preset
    $allowed = array('home', 'business', 'default');
    if (!in_array($preset, $allowed)) {
        return json_encode(array('error' => 'Invalid preset'));
    }
    exec('sh /sd/connectedclients/switch_subnet.sh ' . escapeshellarg($preset) . ' 2>&1', $output);
    return json_encode(array('result' => implode("\n", $output)));
}

// DHCP Manager Functions

// Get DHCP Manager dashboard info
function get_dashboard(){
    $data = array();
    
    // Get br-lan info
    exec("uci get network.lan.ipaddr", $brlan_ip);
    exec("uci get network.lan.netmask", $brlan_mask);
    exec("uci get dhcp.lan.limit", $brlan_limit);
    
    $brlan_clients = intval(count_clients_in_subnet('172.16.42.'));
    $brlan_total = isset($brlan_limit[0]) ? intval($brlan_limit[0]) : 150;
    
    $data['brlan'] = array(
        'ip' => isset($brlan_ip[0]) ? trim($brlan_ip[0]) : '172.16.42.1',
        'subnet' => '172.16.42',
        'clients' => $brlan_clients,
        'total_ips' => $brlan_total
    );
    
    // Get rogue AP info
    exec("sh /sd/connectedclients/switch_subnet.sh status 2>&1", $rogue_status);
    $preset = strpos(implode(" ", $rogue_status), 'business') !== false ? 'business' : 'home';
    
    $rogue_clients = intval(count_clients_in_subnet($preset === 'business' ? '10.0.0.' : '192.168.0.'));
    
    $data['rogue'] = array(
        'ip' => $preset === 'business' ? '10.0.0.1' : '192.168.0.1',
        'preset' => $preset,
        'clients' => $rogue_clients
    );
    
    // Get wlan0-1 info
    $wlan0_1_clients = intval(count_clients_in_subnet('192.168.1.'));
    $data['wlan0_1'] = array(
        'clients' => $wlan0_1_clients
    );
    
    // Count total leases
    $data['total_leases'] = intval($brlan_clients + $rogue_clients + $wlan0_1_clients);
    
    return json_encode($data);
}

// Count clients in a subnet
// For br-lan (172.16.42.x), only count clients that are actually on ethernet (br-lan interface)
function count_clients_in_subnet($subnet_prefix){
    $count = 0;
    $leases_file = '/tmp/dhcp.leases';
    
    // If counting br-lan, exclude devices that are NOT in ARP (wireless devices may have stale leases)
    // Use ARP table to verify device is actually connected and on br-lan
    $online_macs = array();
    if ($subnet_prefix === '172.16.42.') {
        $arp_content = file_get_contents('/proc/net/arp');
        if ($arp_content) {
            $arp_lines = explode("\n", $arp_content);
            foreach ($arp_lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, 'IP address') === 0) continue;
                $parts = preg_split('/\s+/', $line);
                if (count($parts) >= 4) {
                    $ip = $parts[0];
                    $mac_lower = strtolower($parts[3]);
                    $device = $parts[5];
                    // Only count if it's on br-lan device and in the target subnet
                    if ($device === 'br-lan' && strpos($ip, $subnet_prefix) === 0) {
                        $online_macs[$mac_lower] = true;
                    }
                }
            }
        }
    }
    
    if (file_exists($leases_file)) {
        $lines = file($leases_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($lines) && count($lines) > 0) {
            foreach ($lines as $line) {
                if (!empty($line)) {
                    $parts = explode(' ', $line);
                    if (count($parts) >= 3 && !empty($parts[2]) && strpos($parts[2], $subnet_prefix) === 0) {
                        $mac = strtolower(trim($parts[1]));
                        // Only count if online in ARP
                        if ($subnet_prefix === '172.16.42.') {
                            if (isset($online_macs[$mac])) {
                                $count++;
                            }
                        } else {
                            // For non-br-lan, count all leases (no ARP filtering needed)
                            $count++;
                        }
                    }
                }
            }
        }
    }
    
    return intval($count);
}

// Get active DHCP leases
function get_leases(){
    $leases = array();
    $leases_file = '/tmp/dhcp.leases';
    
    if (file_exists($leases_file)) {
        $lines = file($leases_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode(' ', $line);
            if (count($parts) >= 3) {
                $mac = $parts[1];
                $ip = $parts[2];
                $hostname = count($parts) >= 4 ? $parts[3] : 'unknown';
                
                $leases[] = array(
                    'mac' => $mac,
                    'ip' => $ip,
                    'hostname' => $hostname,
                    'vendor' => get_mac_vendor($mac)
                );
            }
        }
    }
    
    return json_encode($leases);
}

// Lookup OUI from IEEE database file
function lookup_oui_from_file($mac, $oui_file) {
    // Convert MAC to both formats for matching
    // Get first 3 octets (8 chars with colons: "xx:xx:xx")
    $prefix_dashes = strtoupper(str_replace(':', '-', substr($mac, 0, 8)));
    // Get first 3 octets without separators (6 hex chars: "xxxxxx")
    $prefix_no_sep = strtoupper(str_replace(':', '', substr($mac, 0, 8)));
    
    $file = fopen($oui_file, 'r');
    if (!$file) {
        return 'Unknown';
    }
    
    while (($line = fgets($file)) !== false) {
        $trimmed = trim($line);
        // Skip comments and empty lines
        if (empty($trimmed) || $trimmed[0] === '#') {
            continue;
        }
        
        // Try matching both formats
        // IEEE OUI format: 00-00-00   (hex)    Organization Name
        // OR simple format: 000000	Vendor Name
        if (strpos($trimmed, $prefix_dashes) === 0 || strpos($trimmed, $prefix_no_sep) === 0) {
            // Parse the line - can be either "XXXXXX\tVendor" or "XX-XX-XX   (hex)   Vendor"
            $parts = preg_split('/\s+/', $trimmed, 2);
            fclose($file);
            // Return the second part (vendor name)
            return isset($parts[1]) ? trim($parts[1]) : 'Unknown';
        }
    }
    
    fclose($file);
    return 'Unknown';
}

// Download and cache IEEE OUI database
function ensure_oui_database() {
    $cache_dir = '/sd/connectedclients';
    $oui_cache = $cache_dir . '/oui_cache.txt';
    
    // Check if cache exists and is less than 30 days old
    if (file_exists($oui_cache) && (time() - filemtime($oui_cache)) < 2592000) {
        return $oui_cache;
    }
    
    // Try to download from IEEE
    $url = 'http://standards-oui.ieee.org/oui/oui.txt';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200 && $response) {
        // Cache the database
        if (is_dir($cache_dir)) {
            file_put_contents($oui_cache, $response);
            return $oui_cache;
        }
    }
    
    return null;
}

// Lookup OUI from cached IEEE database
function lookup_oui_from_cache($mac) {
    $oui_cache = ensure_oui_database();
    
    if (!$oui_cache || !file_exists($oui_cache)) {
        return 'Unknown';
    }
    
    // Convert MAC to both formats for matching
    // Get first 3 octets (8 chars with colons: "xx:xx:xx")
    $prefix_dashes = strtoupper(str_replace(':', '-', substr($mac, 0, 8)));
    // Get first 3 octets without separators (6 hex chars: "xxxxxx")
    $prefix_no_sep = strtoupper(str_replace(':', '', substr($mac, 0, 8)));
    
    $file = fopen($oui_cache, 'r');
    if (!$file) {
        return 'Unknown';
    }
    
    while (($line = fgets($file)) !== false) {
        $trimmed = trim($line);
        // Skip comments and empty lines
        if (empty($trimmed) || $trimmed[0] === '#') {
            continue;
        }
        
        // Try matching both formats
        if (strpos($trimmed, $prefix_dashes) === 0 || strpos($trimmed, $prefix_no_sep) === 0) {
            $parts = preg_split('/\s+/', $trimmed, 2);
            fclose($file);
            return isset($parts[1]) ? trim($parts[1]) : 'Unknown';
        }
    }
    
    fclose($file);
    return 'Unknown';
}

// Get MAC vendor from first 3 octets (OUI lookup)
function get_mac_vendor($mac){
    // Validate and normalize MAC address format
    if (!is_valid_mac($mac)) {
        return 'Invalid MAC';
    }
    
    // Normalize to lowercase with colons (xx:xx:xx:xx:xx:xx)
    $mac = strtolower(str_replace('-', ':', $mac));
    
    // Check for locally administered MAC (indicates MAC randomization)
    $first_octet = substr($mac, 0, 2);
    $first_byte = hexdec($first_octet);
    // Bit 0 of first octet = 1 means locally administered
    if (($first_byte & 0x02) === 0x02) {
        return '🔒 Randomized';
    }
    
    // Check if cached OUI database exists
    $oui_cache = '/sd/connectedclients/oui_cache.txt';
    if (file_exists($oui_cache)) {
        $vendor = lookup_oui_from_cache($mac);
        if ($vendor !== 'Unknown') {
            return $vendor;
        }
    }
    
    // Hardcoded vendor database
    $vendors = array(
        // ASUS
        '04:92:26' => 'ASUSTek',
        
        // Apple
        'c6:d3:e0' => 'Apple',
        '00:03:93' => 'Apple',
        '00:04:75' => 'Apple',
        '00:0a:95' => 'Apple',
        '00:0b:46' => 'Apple',
        '00:1a:92' => 'Apple',
        '00:1d:4f' => 'Apple',
        '00:1e:52' => 'Apple',
        '00:1e:c2' => 'Apple',
        '00:1f:5b' => 'Apple',
        '00:21:82' => 'Apple',
        '00:22:41' => 'Apple',
        '00:23:12' => 'Apple',
        '00:23:6c' => 'Apple',
        '00:24:36' => 'Apple',
        '00:25:00' => 'Apple',
        '00:25:86' => 'Apple',
        '00:25:bc' => 'Apple',
        '00:26:b0' => 'Apple',
        '00:26:f2' => 'Apple',
        '00:27:13' => 'Apple',
        '00:2a:f7' => 'Apple',
        '00:2e:6f' => 'Apple',
        '00:30:65' => 'Apple',
        '00:3e:e1' => 'Apple',
        '00:50:2f' => 'Apple',
        '00:60:1d' => 'Apple',
        '00:66:4b' => 'Apple',
        '00:6b:f1' => 'Apple',
        '00:84:47' => 'Apple',
        '00:87:95' => 'Apple',
        '00:8f:f0' => 'Apple',
        '00:9a:9d' => 'Apple',
        '00:a0:40' => 'Apple',
        '00:a1:97' => 'Apple',
        '00:ad:d0' => 'Apple',
        '00:b0:d0' => 'Apple',
        '00:c6:10' => 'Apple',
        '00:d4:97' => 'Apple',
        '00:e0:4c' => 'Apple',
        '00:f4:b9' => 'Apple',
        '04:f1:38' => 'Apple',
        '0c:74:c3' => 'Apple',
        '10:40:f3' => 'Apple',
        '14:cc:20' => 'Apple',
        '18:65:90' => 'Apple',
        '18:8b:45' => 'Apple',
        '1c:52:16' => 'Apple',
        '20:c5:d9' => 'Apple',
        '24:a0:74' => 'Apple',
        '28:37:37' => 'Apple',
        '2c:f0:ee' => 'Apple',
        '30:10:e8' => 'Apple',
        '34:15:9c' => 'Apple',
        '34:ab:37' => 'Apple',
        '38:c0:14' => 'Apple',
        '3c:15:c2' => 'Apple',
        '40:16:8e' => 'Apple',
        '44:2a:60' => 'Apple',
        '48:d7:05' => 'Apple',
        '4c:00:10' => 'Apple',
        '50:3d:e5' => 'Apple',
        '54:26:96' => 'Apple',
        '58:1f:aa' => 'Apple',
        '5c:0a:5b' => 'Apple',
        '60:33:4b' => 'Apple',
        '64:76:ba' => 'Apple',
        '68:a8:6d' => 'Apple',
        '6c:40:08' => 'Apple',
        '70:48:0f' => 'Apple',
        '74:e5:0b' => 'Apple',
        '78:31:f1' => 'Apple',
        '7c:d1:c3' => 'Apple',
        '80:00:6e' => 'Apple',
        '80:e6:50' => 'Apple',
        '84:38:35' => 'Apple',
        '88:63:df' => 'Apple',
        '8c:2e:85' => 'Apple',
        '90:84:0d' => 'Apple',
        '94:de:80' => 'Apple',
        '98:01:a7' => 'Apple',
        '98:ca:ed' => 'Apple',
        '9c:29:19' => 'Apple',
        'a0:14:3d' => 'Apple',
        'a4:83:e7' => 'Apple',
        'a8:5b:78' => 'Apple',
        'ac:3c:0b' => 'Apple',
        'ac:de:80' => 'Apple',
        'b0:34:95' => 'Apple',
        'b4:55:cc' => 'Apple',
        'b8:09:8a' => 'Apple',
        'b8:78:2e' => 'Apple',
        'bc:54:2e' => 'Apple',
        'c0:25:06' => 'Apple',
        'c4:2c:03' => 'Apple',
        'c8:6c:87' => 'Apple',
        'cc:2d:e0' => 'Apple',
        'd0:23:db' => 'Apple',
        'd4:6e:0e' => 'Apple',
        'd8:30:62' => 'Apple',
        'dc:2b:61' => 'Apple',
        'e0:ac:cb' => 'Apple',
        'e4:8b:46' => 'Apple',
        'e8:84:a5' => 'Apple',
        'ec:35:86' => 'Apple',
        'f0:18:98' => 'Apple',
        'f4:0f:24' => 'Apple',
        'f8:01:13' => 'Apple',
        'fc:65:de' => 'Apple',
        
        // Samsung
        '00:07:ab' => 'Samsung',
        '00:09:18' => 'Samsung',
        '00:12:fb' => 'Samsung',
        '00:13:77' => 'Samsung',
        '00:16:6b' => 'Samsung',
        '00:1a:8a' => 'Samsung',
        '00:1e:4c' => 'Samsung',
        '00:22:a1' => 'Samsung',
        '00:23:be' => 'Samsung',
        '00:24:be' => 'Samsung',
        '00:26:37' => 'Samsung',
        '00:37:6b' => 'Samsung',
        '00:3e:98' => 'Samsung',
        '14:cc:20' => 'Samsung',
        '18:3a:2c' => 'Samsung',
        '20:1a:4a' => 'Samsung',
        '28:21:86' => 'Samsung',
        '2c:11:22' => 'Samsung',
        '34:80:0d' => 'Samsung',
        '38:ca:da' => 'Samsung',
        '3c:37:86' => 'Samsung',
        '40:d8:55' => 'Samsung',
        '44:e9:dd' => 'Samsung',
        '48:93:fe' => 'Samsung',
        '4c:41:1e' => 'Samsung',
        '50:64:2f' => 'Samsung',
        '54:27:1e' => 'Samsung',
        '58:03:b5' => 'Samsung',
        '5c:2e:5e' => 'Samsung',
        '5c:ab:94' => 'Samsung',
        '60:d8:19' => 'Samsung',
        '64:31:50' => 'Samsung',
        '68:d7:d0' => 'Samsung',
        '6c:72:e7' => 'Samsung',
        '70:3b:09' => 'Samsung',
        '74:40:bb' => 'Samsung',
        '78:47:1d' => 'Samsung',
        '7c:4c:a5' => 'Samsung',
        '80:00:60' => 'Samsung',
        '84:2a:fb' => 'Samsung',
        '88:50:f3' => 'Samsung',
        '8c:21:0a' => 'Samsung',
        '90:09:b6' => 'Samsung',
        '94:35:0a' => 'Samsung',
        '98:b6:e9' => 'Samsung',
        '9c:2a:70' => 'Samsung',
        'a0:0b:ba' => 'Samsung',
        'a4:12:69' => 'Samsung',
        'a8:5e:60' => 'Samsung',
        'ac:64:17' => 'Samsung',
        'b0:22:7a' => 'Samsung',
        'b4:79:a7' => 'Samsung',
        'b8:e8:56' => 'Samsung',
        'bc:14:01' => 'Samsung',
        'c0:06:c3' => 'Samsung',
        'c4:85:08' => 'Samsung',
        'c8:09:a6' => 'Samsung',
        'cc:20:e6' => 'Samsung',
        'd0:12:16' => 'Samsung',
        'd4:6d:7e' => 'Samsung',
        'd8:0f:99' => 'Samsung',
        'dc:37:14' => 'Samsung',
        'e0:28:6d' => 'Samsung',
        'e4:ce:8f' => 'Samsung',
        'e8:92:a4' => 'Samsung',
        'ec:1a:59' => 'Samsung',
        'f0:72:ea' => 'Samsung',
        
        // Google
        '00:18:0a' => 'Google',
        '00:26:12' => 'Google',
        '14:cc:20' => 'Google',
        '28:69:6a' => 'Google',
        '34:08:04' => 'Google',
        '38:63:bb' => 'Google',
        '40:16:3e' => 'Google',
        '48:5a:3f' => 'Google',
        '50:f5:da' => 'Google',
        '5a:e1:db' => 'Google',
        '64:9c:0e' => 'Google',
        '6a:c7:17' => 'Google',
        '74:6d:28' => 'Google',
        '7a:66:a8' => 'Google',
        '84:ef:18' => 'Google',
        '8a:36:9f' => 'Google',
        '90:e2:ba' => 'Google',
        '9e:75:a7' => 'Google',
        'a4:36:bc' => 'Google',
        
        // Microsoft
        '00:1d:c1' => 'Microsoft',
        '00:50:f2' => 'Microsoft',
        '14:cc:20' => 'Microsoft',
        '1c:6f:65' => 'Microsoft',
        '24:4a:3e' => 'Microsoft',
        '28:18:78' => 'Microsoft',
        '38:2c:4a' => 'Microsoft',
        '44:fb:42' => 'Microsoft',
        '4c:cc:6a' => 'Microsoft',
        '54:53:ed' => 'Microsoft',
        '5c:f9:38' => 'Microsoft',
        '64:1c:41' => 'Microsoft',
        '6c:ad:ad' => 'Microsoft',
        '74:6e:0b' => 'Microsoft',
        '7c:1e:52' => 'Microsoft',
        '84:8d:6b' => 'Microsoft',
        '8c:89:a5' => 'Microsoft',
        '94:de:80' => 'Microsoft',
        '9c:37:f5' => 'Microsoft',
        'a4:ba:db' => 'Microsoft',
        'ac:5f:3e' => 'Microsoft',
        'b4:6b:fc' => 'Microsoft',
        'bc:77:37' => 'Microsoft',
        'c4:46:19' => 'Microsoft',
        'cc:40:d9' => 'Microsoft',
        'd4:25:8b' => 'Microsoft',
        'dc:37:14' => 'Microsoft',
        'e4:11:5b' => 'Microsoft',
        'ec:f4:bb' => 'Microsoft',
        'f4:4e:fd' => 'Microsoft',
        
        // Intel
        '00:02:b3' => 'Intel',
        '00:1f:3c' => 'Intel',
        '14:cc:20' => 'Intel',
        '1c:6f:65' => 'Intel',
        '24:be:05' => 'Intel',
        '3c:97:0e' => 'Intel',
        '54:ab:3a' => 'Intel',
        '5c:f3:70' => 'Intel',
        '64:9a:04' => 'Intel',
        '7c:7a:91' => 'Intel',
        '84:7b:9b' => 'Intel',
        '94:de:80' => 'Intel',
        'a4:ba:db' => 'Intel',
        'ac:5f:3e' => 'Intel',
        'c4:85:08' => 'Intel',
        'cc:2d:e0' => 'Intel',
        'd4:6e:0e' => 'Intel',
        'ec:f4:bb' => 'Intel',
        
        // Broadcom/Qualcomm
        '00:04:4b' => 'Broadcom',
        '00:0f:66' => 'Broadcom',
        '00:19:35' => 'Broadcom',
        '00:1e:3a' => 'Broadcom',
        '00:23:f8' => 'Broadcom',
        '14:cc:20' => 'Broadcom',
        '1c:5f:2b' => 'Broadcom',
        '24:77:03' => 'Broadcom',
        '2c:f0:ee' => 'Broadcom',
        '34:13:e8' => 'Broadcom',
        '3c:37:86' => 'Broadcom',
        '44:48:c1' => 'Broadcom',
        '4c:5e:0c' => 'Broadcom',
        '54:04:a6' => 'Broadcom',
        '5c:51:88' => 'Broadcom',
        '64:9d:99' => 'Broadcom',
        '6c:85:56' => 'Broadcom',
        '74:de:2b' => 'Broadcom',
        '7c:b2:6d' => 'Broadcom',
        '84:1b:5e' => 'Broadcom',
        '8c:79:59' => 'Broadcom',
        '94:10:3e' => 'Broadcom',
        '9c:b6:54' => 'Broadcom',
        'a4:ae:12' => 'Broadcom',
        'ac:7b:a1' => 'Broadcom',
        'b4:00:20' => 'Broadcom',
        'bc:14:01' => 'Broadcom',
        'c4:00:ad' => 'Broadcom',
        'cc:40:d9' => 'Broadcom',
        'd4:6e:0e' => 'Broadcom',
        'dc:a9:04' => 'Broadcom',
        'e4:f4:c6' => 'Broadcom',
        'ec:55:f9' => 'Broadcom',
        'f4:4e:fd' => 'Broadcom',
        
        // Realtek
        '00:0c:43' => 'Realtek',
        '00:13:d4' => 'Realtek',
        '00:1a:4b' => 'Realtek',
        '00:1c:de' => 'Realtek',
        '00:20:f8' => 'Realtek',
        '00:24:b2' => 'Realtek',
        '00:26:5a' => 'Realtek',
        '00:8d:7c' => 'Realtek',
        '00:e0:4c' => 'Realtek',
        '10:1f:74' => 'Realtek',
        '1c:1a:c0' => 'Realtek',
        '20:16:d8' => 'Realtek',
        '30:5a:3a' => 'Realtek',
        '38:de:ad' => 'Realtek',
        '40:4c:ca' => 'Realtek',
        '50:02:91' => 'Realtek',
        '60:a4:4c' => 'Realtek',
        '70:4f:57' => 'Realtek',
        '80:1f:02' => 'Realtek',
        '90:fb:a6' => 'Realtek',
        'a8:5e:60' => 'Realtek',
        'b8:ae:ed' => 'Realtek',
        'c8:3a:6b' => 'Realtek',
        'd8:47:32' => 'Realtek',
        'e8:9e:b4' => 'Realtek',
        'f8:d1:11' => 'Realtek',
        
        // ASUS
        '00:13:10' => 'ASUS',
        '00:14:8a' => 'ASUS',
        '00:1e:8f' => 'ASUS',
        '00:23:54' => 'ASUS',
        '00:26:18' => 'ASUS',
        '00:30:1f' => 'ASUS',
        '1c:87:2c' => 'ASUS',
        '30:39:f2' => 'ASUS',
        '48:d7:d5' => 'ASUS',
        '50:9c:fb' => 'ASUS',
        '70:77:81' => 'ASUS',
        '88:71:11' => 'ASUS',
        'a0:a3:e1' => 'ASUS',
        'c8:f7:33' => 'ASUS',
        
        // Lenovo
        '00:1f:3c' => 'Lenovo',
        '00:21:28' => 'Lenovo',
        '00:22:4d' => 'Lenovo',
        '00:24:8c' => 'Lenovo',
        '00:25:d5' => 'Lenovo',
        '1c:6f:65' => 'Lenovo',
        '28:d2:44' => 'Lenovo',
        '38:60:77' => 'Lenovo',
        '50:46:5d' => 'Lenovo',
        '68:a8:6d' => 'Lenovo',
        '78:11:dc' => 'Lenovo',
        '88:ae:1d' => 'Lenovo',
        'a8:5e:60' => 'Lenovo',
        'c8:9f:1d' => 'Lenovo',
        
        // HP
        '00:1a:4b' => 'HP',
        '00:1c:c4' => 'HP',
        '00:1e:0b' => 'HP',
        '00:24:be' => 'HP',
        '00:25:86' => 'HP',
        '00:30:f1' => 'HP',
        '1c:6f:65' => 'HP',
        '30:e1:71' => 'HP',
        '50:9a:4c' => 'HP',
        '68:a3:c4' => 'HP',
        '78:ca:f7' => 'HP',
        '88:18:26' => 'HP',
        'a4:2b:8c' => 'HP',
        'c8:5a:8e' => 'HP',
    );
    
    $prefix = strtolower(substr($mac, 0, 8));
    if (isset($vendors[$prefix])) {
        return $vendors[$prefix];
    }
    
    // Return status indicator for unknown vendors
    return '❓ Not found';
}



// Release a DHCP lease (remove from leases file)
function release_lease($mac){
    // Validate MAC address
    if (!is_valid_mac($mac)) {
        return json_encode(array('error' => 'Invalid MAC address format'));
    }
    
    $mac_lower = strtolower($mac);
    $leases_file = '/tmp/dhcp.leases';
    $found = false;
    
    // Remove the lease from dhcp.leases file
    if (file_exists($leases_file)) {
        $lines = file($leases_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $new_lines = array();
        
        foreach ($lines as $line) {
            $parts = explode(' ', $line);
            if (count($parts) >= 2 && strtolower(trim($parts[1])) === $mac_lower) {
                $found = true;
                continue;  // Skip this line (remove it)
            }
            $new_lines[] = $line;
        }
        
        if ($found) {
            file_put_contents($leases_file, implode("\n", $new_lines) . "\n");
            // Restart dnsmasq to reload
            exec("killall -HUP dnsmasq 2>/dev/null");
        }
    }
    
    if (!$found) {
        return json_encode(array('error' => 'Lease not found for MAC', 'mac' => $mac));
    }
    
    return json_encode(array('status' => 'released', 'mac' => $mac, 'message' => 'Lease released'));
}

// Renew a DHCP lease (set expiry to future time)
function renew_lease($mac){
    // Validate MAC address
    if (!is_valid_mac($mac)) {
        return json_encode(array('error' => 'Invalid MAC address format'));
    }
    
    $mac_lower = strtolower($mac);
    $leases_file = '/tmp/dhcp.leases';
    $found = false;
    
    // Get duration from POST parameter (in seconds)
    $lease_duration = isset($_POST['duration']) ? intval($_POST['duration']) : 3600;  // Default 1 hour
    
    // Validate duration is reasonable (30 mins to 30 days)
    if ($lease_duration < 1800) $lease_duration = 1800;      // Min 30 minutes
    if ($lease_duration > 2592000) $lease_duration = 2592000; // Max 30 days
    
    $new_expiry = time() + $lease_duration;
    
    // Renew the lease by updating its expiry time and storing the duration
    if (file_exists($leases_file)) {
        $lines = file($leases_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $new_lines = array();
        
        foreach ($lines as $line) {
            $parts = explode(' ', $line);
            if (count($parts) >= 3 && strtolower(trim($parts[1])) === $mac_lower) {
                // Found the lease - renew it by updating expiry time and duration
                $parts[0] = $new_expiry;
                // Store duration as 5th field for future reference
                if (count($parts) < 5) {
                    $parts[4] = $lease_duration;
                } else {
                    $parts[4] = $lease_duration;
                }
                $new_lines[] = implode(' ', $parts);
                $found = true;
            } else {
                $new_lines[] = $line;
            }
        }
        
        if ($found) {
            file_put_contents($leases_file, implode("\n", $new_lines) . "\n");
            // Restart dnsmasq to reload
            exec("killall -HUP dnsmasq 2>/dev/null");
        }
    }
    
    if (!$found) {
        return json_encode(array('error' => 'Lease not found for MAC', 'mac' => $mac));
    }
    
    // Format duration for display
    if ($lease_duration >= 86400) {
        $days = floor($lease_duration / 86400);
        $duration_str = $days . ' day' . ($days > 1 ? 's' : '');
    } elseif ($lease_duration >= 3600) {
        $hours = floor($lease_duration / 3600);
        $duration_str = $hours . ' hour' . ($hours > 1 ? 's' : '');
    } else {
        $mins = floor($lease_duration / 60);
        $duration_str = $mins . ' minute' . ($mins > 1 ? 's' : '');
    }
    
    return json_encode(array('status' => 'renewed', 'mac' => $mac, 'message' => 'Lease renewed for ' . $duration_str));
}

// Set the initial DHCP lease duration (updates dnsmasq config)
function set_initial_lease_duration($duration) {
    // Validate duration is reasonable (30 mins to 30 days)
    $duration = intval($duration);
    if ($duration < 1800) $duration = 1800;      // Min 30 minutes
    if ($duration > 2592000) $duration = 2592000; // Max 30 days
    
    // Convert seconds to dnsmasq duration format (e.g., 3600s, 1h, 1d)
    if ($duration % 86400 === 0) {
        $duration_str = ($duration / 86400) . 'd';
    } elseif ($duration % 3600 === 0) {
        $duration_str = ($duration / 3600) . 'h';
    } elseif ($duration % 60 === 0) {
        $duration_str = ($duration / 60) . 'm';
    } else {
        $duration_str = $duration . 's';
    }
    
    // Format duration for display
    if ($duration >= 86400) {
        $days = floor($duration / 86400);
        $duration_display = $days . ' day' . ($days > 1 ? 's' : '');
    } elseif ($duration >= 3600) {
        $hours = floor($duration / 3600);
        $duration_display = $hours . ' hour' . ($hours > 1 ? 's' : '');
    } else {
        $mins = floor($duration / 60);
        $duration_display = $mins . ' minute' . ($mins > 1 ? 's' : '');
    }
    
    // Update all dhcp-range entries in dnsmasq config
    $dhcp_config_file = '/var/etc/dnsmasq.conf';
    if (file_exists($dhcp_config_file)) {
        $config = file_get_contents($dhcp_config_file);
        // Replace all dhcp-range duration values with the new one
        $config = preg_replace('/dhcp-range=([^,]+),([^,]+),([^,]+),([^,]+),([\d\w]+)/', 
                              'dhcp-range=$1,$2,$3,$4,' . $duration_str, $config);
        
        file_put_contents($dhcp_config_file, $config);
        
        // Restart dnsmasq to apply changes
        exec("killall -HUP dnsmasq 2>/dev/null");
        
        return json_encode(array(
            'status' => 'success',
            'message' => 'Initial lease duration set to ' . $duration_display,
            'duration' => $duration,
            'duration_display' => $duration_display
        ));
    }
    
    return json_encode(array('error' => 'dnsmasq config file not found'));
}

// Clean up offline leases (remove expired/stale leases)
function cleanup_offline_leases() {
    $leases_file = '/tmp/dhcp.leases';
    $current_time = time();
    $removed_count = 0;
    $removed_macs = array();
    
    // Get ARP table for detecting online devices
    // (iw station dump doesn't work reliably on all systems, especially with bridge configs)
    $connected_macs = array();
    $arp_content = file_get_contents('/proc/net/arp');
    if ($arp_content) {
        $arp_lines = explode("\n", $arp_content);
        foreach ($arp_lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, 'IP address') === 0) continue;
            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 4) {
                $mac_lower = strtolower($parts[3]);
                $connected_macs[$mac_lower] = true;  // MAC => connected
            }
        }
    }
    
    if (file_exists($leases_file)) {
        $lines = file($leases_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $new_lines = array();
        
        foreach ($lines as $line) {
            $parts = explode(' ', $line);
            if (count($parts) >= 2) {
                $expiry_timestamp = (int)$parts[0];
                $mac = trim($parts[1]);
                $ip = trim($parts[2]);
                $mac_lower = strtolower($mac);
                
                // Check if device is currently in ARP table (online)
                $is_connected = isset($connected_macs[$mac_lower]);
                
                // Remove if:
                // 1. Lease has expired, OR
                // 2. Device is NOT in ARP table (offline/not connected)
                if ($expiry_timestamp <= $current_time) {
                    // Lease has expired - remove it
                    $removed_count++;
                    $removed_macs[] = $mac;
                    continue;
                } elseif (!$is_connected) {
                    // Device not in ARP table - it's offline or a stale lease
                    $removed_count++;
                    $removed_macs[] = $mac;
                    continue;
                }
            }
            
            // Keep this lease
            $new_lines[] = $line;
        }
        
        // Write back cleaned leases file
        if ($removed_count > 0) {
            file_put_contents($leases_file, implode("\n", $new_lines) . "\n");
            
            // Restart dnsmasq to reload
            exec("killall -HUP dnsmasq 2>/dev/null");
        }
    }
    
    return json_encode(array(
        'status' => 'success',
        'removed_count' => $removed_count,
        'removed_macs' => $removed_macs,
        'message' => $removed_count > 0 ? "Removed $removed_count stale lease(s)" : "No stale leases found"
    ));
}

// Get static DHCP leases
function get_static_leases(){
    $leases = array();
    $static_file = '/etc/dnsmasq.d/static_dhcp';
    
    if (file_exists($static_file)) {
        $lines = file($static_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, 'dhcp-host=') === 0) {
                $parts = explode(',', str_replace('dhcp-host=', '', $line));
                if (count($parts) >= 2) {
                    $leases[] = array(
                        'mac' => trim($parts[0]),
                        'ip' => trim($parts[1]),
                        'hostname' => isset($parts[2]) ? trim($parts[2]) : ''
                    );
                }
            }
        }
    }
    
    return json_encode($leases);
}

// Add a static DHCP lease
function add_static_lease($mac, $ip, $hostname){
    // Validate MAC address
    if (!is_valid_mac($mac)) {
        return json_encode(array('error' => 'Invalid MAC address format'));
    }
    
    // Validate IP address
    if (!is_valid_ipv4($ip)) {
        return json_encode(array('error' => 'Invalid IPv4 address'));
    }
    
    // Validate and sanitize hostname if provided
    if ($hostname) {
        if (!is_valid_hostname($hostname)) {
            return json_encode(array('error' => 'Invalid hostname format'));
        }
        $hostname = sanitize_hostname($hostname);
    }
    
    // Check if static entry already exists for this MAC
    $static_file = '/etc/dnsmasq.d/static_dhcp';
    if (file_exists($static_file)) {
        $lines = file($static_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (preg_match('/dhcp-host=' . preg_quote($mac) . ',/', $line)) {
                return json_encode(array('error' => 'Static lease already exists for this MAC'));
            }
        }
    }
    
    // Create entry
    $entry = "dhcp-host=" . $mac . "," . $ip;
    if ($hostname) {
        $entry .= "," . $hostname;
    }
    $entry .= "\n";
    
    // Write to file
    if (!file_put_contents($static_file, $entry, FILE_APPEND)) {
        return json_encode(array('error' => 'Failed to write static lease'));
    }
    
    // Reload dnsmasq
    exec("killall -HUP dnsmasq 2>/dev/null", $output, $return_code);
    
    return json_encode(array('status' => 'added', 'mac' => $mac, 'ip' => $ip));
}

// Delete a static DHCP lease
function delete_static_lease($mac){
    // Validate MAC address
    if (!is_valid_mac($mac)) {
        return json_encode(array('error' => 'Invalid MAC address format'));
    }
    
    $static_file = '/etc/dnsmasq.d/static_dhcp';
    $found = false;
    
    if (file_exists($static_file)) {
        $lines = file($static_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $new_lines = array();
        
        foreach ($lines as $line) {
            // Check if this line is for our MAC (exact match for dhcp-host=MAC,...)
            if (preg_match('/^dhcp-host=' . preg_quote($mac) . ',/', $line)) {
                $found = true;
                continue;  // Skip this line (delete it)
            }
            $new_lines[] = $line;
        }
        
        if ($found) {
            // Only write if we found and removed an entry
            if (!file_put_contents($static_file, implode("\n", $new_lines) . "\n")) {
                return json_encode(array('error' => 'Failed to write static lease file'));
            }
            exec("killall -HUP dnsmasq 2>/dev/null", $output, $return_code);
        }
    }
    
    if (!$found) {
        return json_encode(array('error' => 'Static lease not found for this MAC'));
    }
    
    return json_encode(array('status' => 'deleted', 'mac' => $mac));
}

// Get DHCP ranges
function get_dhcp_ranges(){
    $ranges = array();
    
    // br-lan range
    exec("uci get dhcp.lan.start", $brlan_start);
    exec("uci get dhcp.lan.limit", $brlan_limit);
    $ranges[] = array(
        'interface' => 'br-lan (172.16.42.x)',
        'start' => isset($brlan_start[0]) ? $brlan_start[0] : '100',
        'limit' => isset($brlan_limit[0]) ? $brlan_limit[0] : '150'
    );
    
    // Rogue range
    $ranges[] = array(
        'interface' => 'Rogue (192.168.0.x/10.0.0.x)',
        'start' => '100',
        'limit' => '150'
    );
    
    return json_encode($ranges);
}

// Get DHCP log
function get_dhcp_log(){
    $log = array();
    $log_file = '/var/log/dnsmasq.log';
    
    if (file_exists($log_file)) {
        $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        // Get last 100 lines
        $lines = array_slice($lines, -100);
        
        foreach ($lines as $line) {
            if (strpos($line, 'DHCP') !== false) {
                $log[] = htmlspecialchars($line);
            }
        }
    }
    
    return json_encode($log);
}

// Clear DHCP log
function clear_dhcp_log(){
    exec("> /var/log/dnsmasq.log");
    return json_encode(array('status' => 'cleared'));
}

// Export leases as CSV
function export_leases_csv(){
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="dhcp_leases_' . date('Y-m-d_H-i-s') . '.csv"');
    
    $leases_file = '/tmp/dhcp.leases';
    echo "MAC,IP,Hostname,Vendor\n";
    
    if (file_exists($leases_file)) {
        $lines = file($leases_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode(' ', $line);
            if (count($parts) >= 3) {
                $mac = $parts[1];
                $ip = $parts[2];
                $hostname = count($parts) >= 4 ? $parts[3] : 'unknown';
                $vendor = get_mac_vendor($mac);
                
                echo "\"" . $mac . "\",\"" . $ip . "\",\"" . $hostname . "\",\"" . $vendor . "\"\n";
            }
        }
    }
    
    exit;
}

// Get last DHCP action for each MAC from dnsmasq log
function get_dhcp_actions_from_log() {
    $actions = array();
    $log_file = '/var/log/dnsmasq.log';
    
    if (!file_exists($log_file)) {
        return $actions;
    }
    
    // Read last 500 lines of log (recent activity)
    $output = array();
    exec('tail -500 ' . escapeshellarg($log_file) . ' 2>/dev/null', $output);
    
    foreach ($output as $line) {
        // Parse DHCP lines like: "dnsmasq-dhcp[1234]: DHCPACK(br-lan) 192.168.1.100 04:92:26:1d:0c:37"
        if (preg_match('/DHCP(ACK|OFFER|RELEASE|RENEW|DECLINE).*\s([0-9a-f:]+)$/i', $line, $matches)) {
            $mac_lower = strtolower($matches[2]);
            $action = strtoupper($matches[1]);
            $timestamp = extract_log_timestamp($line);
            
            if ($mac_lower && $action) {
                $actions[$mac_lower] = array(
                    'action' => $action,
                    'timestamp' => $timestamp
                );
            }
        }
    }
    
    return $actions;
}

// Get recent DNS query for each MAC from dnsmasq log
function get_recent_dns_queries() {
    $queries = array();
    $log_file = '/var/log/dnsmasq.log';
    
    if (!file_exists($log_file)) {
        return $queries;
    }
    
    // Read last 1000 lines (DNS queries)
    $output = array();
    exec('tail -1000 ' . escapeshellarg($log_file) . ' 2>/dev/null', $output);
    
    foreach ($output as $line) {
        // Parse DNS queries like: "192.168.1.100/50000 query[A] example.com from 192.168.1.100"
        if (preg_match('/(\d+\.\d+\.\d+\.\d+)\/\d+\s+query.*?\s([a-z0-9.-]+)\s+from/i', $line, $matches)) {
            $ip = $matches[1];
            $domain = $matches[2];
            
            // Find MAC for this IP from leases
            $mac_for_ip = find_mac_for_ip($ip);
            if ($mac_for_ip) {
                $mac_lower = strtolower($mac_for_ip);
                $queries[$mac_lower] = $domain;  // Keep last one (most recent)
            }
        }
    }
    
    return $queries;
}

// Get DHCP client info (device class, hostname) from dnsmasq log
function get_dhcp_client_info() {
    $info = array();
    $log_file = '/var/log/dnsmasq.log';
    
    if (!file_exists($log_file)) {
        return $info;
    }
    
    // Read last 500 lines
    $output = array();
    exec('tail -500 ' . escapeshellarg($log_file) . ' 2>/dev/null', $output);
    
    foreach ($output as $line) {
        // Parse DHCP with client info like: "dnsmasq-dhcp[1234]: DHCPACK(br-lan) 192.168.1.100 04:92:26:1d:0c:37 iphone"
        if (preg_match('/DHCP(ACK|OFFER).*\s([0-9a-f:]+)\s+(\S+)$/i', $line, $matches)) {
            $mac_lower = strtolower($matches[2]);
            $client_info = $matches[3];
            
            if ($mac_lower && $client_info && $client_info !== '*') {
                $info[$mac_lower]['hostname'] = $client_info;
                
                // Try to detect device class from hostname/info
                $info_lower = strtolower($client_info);
                if (strpos($info_lower, 'iphone') !== false || strpos($info_lower, 'ipad') !== false) {
                    $info[$mac_lower]['class'] = 'iOS';
                } elseif (strpos($info_lower, 'android') !== false) {
                    $info[$mac_lower]['class'] = 'Android';
                } elseif (strpos($info_lower, 'windows') !== false || strpos($info_lower, 'msft') !== false) {
                    $info[$mac_lower]['class'] = 'Windows';
                } elseif (strpos($info_lower, 'mac') !== false || strpos($info_lower, 'darwin') !== false) {
                    $info[$mac_lower]['class'] = 'macOS';
                } elseif (strpos($info_lower, 'linux') !== false) {
                    $info[$mac_lower]['class'] = 'Linux';
                }
            }
        }
    }
    
    return $info;
}

// Helper: Extract timestamp from dnsmasq log line
function extract_log_timestamp($line) {
    // dnsmasq format: "Feb 26 10:30:45 hostname dnsmasq..."
    if (preg_match('/^([A-Za-z]+\s+\d+\s+\d+:\d+:\d+)/', $line, $matches)) {
        $date_str = $matches[1] . ' ' . date('Y');  // Add current year
        $timestamp = strtotime($date_str);
        return $timestamp ? date('H:i:s', $timestamp) : 'unknown';
    }
    return 'unknown';
}

// Helper: Find MAC address for given IP from leases
function find_mac_for_ip($ip) {
    $leases_file = '/tmp/dhcp.leases';
    
    if (file_exists($leases_file)) {
        $lines = file($leases_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode(' ', $line);
            if (count($parts) >= 3 && $parts[2] === $ip) {
                return $parts[1];
            }
        }
    }
    
    return null;
}

// Get lease modification time (indicates renewal activity)
function get_lease_mtime($mac) {
    $leases_file = '/tmp/dhcp.leases';
    
    if (!file_exists($leases_file)) {
        return null;
    }
    
    $file_mtime = filemtime($leases_file);
    return $file_mtime ? array(
        'timestamp' => $file_mtime,
        'readable' => date('Y-m-d H:i:s', $file_mtime),
        'time_ago' => format_time_ago($file_mtime)
    ) : null;
}

// Count DHCP renewals for a MAC from log
function count_dhcp_renewals($mac) {
    $count = 0;
    $log_file = '/var/log/dnsmasq.log';
    
    if (!file_exists($log_file)) {
        return $count;
    }
    
    $mac_lower = strtolower($mac);
    $output = array();
    exec('grep -i "DHCPACK" ' . escapeshellarg($log_file) . ' 2>/dev/null | grep -i ' . escapeshellarg($mac_lower) . ' | wc -l', $output);
    
    return isset($output[0]) ? (int)$output[0] : 0;
}

// Get first seen timestamp (earliest occurrence in log or lease file)
function get_first_seen($mac) {
    $log_file = '/var/log/dnsmasq.log';
    $leases_file = '/tmp/dhcp.leases';
    
    // Try dnsmasq log first
    if (file_exists($log_file)) {
        $mac_lower = strtolower($mac);
        $output = array();
        exec('grep -i "DHCP" ' . escapeshellarg($log_file) . ' 2>/dev/null | grep -i ' . escapeshellarg($mac_lower) . ' | head -1', $output);
        
        if (!empty($output[0])) {
            $timestamp = extract_log_timestamp($output[0]);
            if ($timestamp !== 'unknown') {
                return array(
                    'readable' => $timestamp,
                    'raw_line' => substr($output[0], 0, 100)
                );
            }
        }
    }
    
    // Fallback: Use lease file info (first entry in lease file is earliest)
    if (file_exists($leases_file)) {
        $lines = file($leases_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode(' ', $line);
            if (count($parts) >= 2 && strtolower($parts[1]) === strtolower($mac)) {
                $expiry_timestamp = (int)$parts[0];
                $lease_duration = 43200; // Default 12 hours
                $dhcp_config = file_get_contents('/etc/dnsmasq.conf');
                if (preg_match('/dhcp-lease-max=(\d+)/', $dhcp_config, $matches)) {
                    $lease_duration = (int)$matches[1];
                }
                // Calculate first seen as approximately lease_age minutes ago
                $first_seen_time = $expiry_timestamp - $lease_duration;
                return array(
                    'readable' => date('H:i:s', $first_seen_time),
                    'raw_line' => 'From lease file (approx)'
                );
            }
        }
    }
    
    return null;
}

// Get last seen timestamp (most recent occurrence in log or current time)
function get_last_seen($mac) {
    $log_file = '/var/log/dnsmasq.log';
    $leases_file = '/tmp/dhcp.leases';
    
    // Try dnsmasq log first
    if (file_exists($log_file)) {
        $mac_lower = strtolower($mac);
        $output = array();
        exec('grep -i "DHCP" ' . escapeshellarg($log_file) . ' 2>/dev/null | grep -i ' . escapeshellarg($mac_lower) . ' | tail -1', $output);
        
        if (!empty($output[0])) {
            $timestamp = extract_log_timestamp($output[0]);
            if ($timestamp !== 'unknown') {
                return array(
                    'readable' => $timestamp,
                    'raw_line' => substr($output[0], 0, 100)
                );
            }
        }
    }
    
    // Fallback: Use current time if device is online, lease file if in leases
    if (file_exists($leases_file)) {
        $lines = file($leases_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode(' ', $line);
            if (count($parts) >= 2 && strtolower($parts[1]) === strtolower($mac)) {
                // Device exists in leases - it was just seen
                // Check ARP to see if it's currently online
                $arp_content = file_get_contents('/proc/net/arp');
                if ($arp_content && stripos($arp_content, $mac) !== false) {
                    // Online - use current time
                    return array(
                        'readable' => date('H:i:s'),
                        'raw_line' => 'Active now'
                    );
                } else {
                    // Offline - use lease expiry time as last seen
                    $expiry_timestamp = (int)$parts[0];
                    return array(
                        'readable' => date('H:i:s', $expiry_timestamp),
                        'raw_line' => 'From lease expiry'
                    );
                }
            }
        }
    }
    
    return null;
}

// Format time ago (e.g., "5 minutes ago")
function format_time_ago($timestamp) {
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
}

// Extract all DHCP options from client info string
function parse_dhcp_options($option_string) {
    $options = array();
    
    if (empty($option_string)) {
        return $options;
    }
    
    // Try to identify vendor class (Option 60) and client identifier (Option 61)
    // These are embedded in the info string from dnsmasq logs
    $info_lower = strtolower($option_string);
    
    // Device class detection (Option 60 - Vendor Class Identifier)
    if (preg_match('/(iphone|ipad)/i', $option_string)) {
        $options['vendor_class'] = 'Apple iOS';
    } elseif (preg_match('/(android)/i', $option_string)) {
        $options['vendor_class'] = 'Android';
    } elseif (preg_match('/(windows|msft)/i', $option_string)) {
        $options['vendor_class'] = 'Microsoft Windows';
    } elseif (preg_match('/(mac|darwin|macbook)/i', $option_string)) {
        $options['vendor_class'] = 'Apple macOS';
    } elseif (preg_match('/(linux|ubuntu|debian|centos)/i', $option_string)) {
        $options['vendor_class'] = 'Linux';
    }
    
    // Client hostname (Option 61)
    if (preg_match('/hostname[=:\s]+([a-z0-9._-]+)/i', $option_string, $matches)) {
        $options['client_hostname'] = $matches[1];
    }
    
    return $options;
}

// Parse wireless station info to get signal strength and TX/RX rates (optimized)
function get_wireless_station_info_fast() {
    $wireless_info = array();
    
    // Get info from both interfaces in a single exec call with a timeout
    $output = array();
    exec('(iw dev wlan0 station dump 2>/dev/null; iw dev wlan0-1 station dump 2>/dev/null) &', $output);
    
    // If no output, try sequential (slower but reliable)
    if (empty($output)) {
        exec('iw dev wlan0 station dump 2>/dev/null', $output);
        if (empty($output)) {
            exec('iw dev wlan0-1 station dump 2>/dev/null', $output);
        }
    }
    
    $current_mac = null;
    foreach ($output as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Parse MAC address
        if (strpos($line, 'Station ') === 0) {
            $current_mac = strtolower(trim(substr($line, 8)));
            if (!isset($wireless_info[$current_mac])) {
                $wireless_info[$current_mac] = array();
            }
        }
        
        // Parse signal strength (RSSI)
        if ($current_mac && strpos($line, 'signal:') === 0) {
            if (preg_match('/signal:\s*(-?\d+)\s*dBm/', $line, $matches)) {
                $wireless_info[$current_mac]['signal'] = (int)$matches[1];
            }
        }
        
        // Parse TX/RX rates
        if ($current_mac && strpos($line, 'tx bitrate:') === 0) {
            if (preg_match('/tx bitrate:\s*([0-9.]+\s*Mbit\/s)/', $line, $matches)) {
                $wireless_info[$current_mac]['tx_rate'] = trim($matches[1]);
            }
        }
        
        if ($current_mac && strpos($line, 'rx bitrate:') === 0) {
            if (preg_match('/rx bitrate:\s*([0-9.]+\s*Mbit\/s)/', $line, $matches)) {
                $wireless_info[$current_mac]['rx_rate'] = trim($matches[1]);
            }
        }
    }
    
    return $wireless_info;
}

?>
