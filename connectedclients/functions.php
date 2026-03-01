<?php
date_default_timezone_set('UTC');

// Start session and check auth
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['logged_in'])) {
    header('Content-Type: application/json');
    echo json_encode(array('error' => 'Not logged in'));
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_csrfToken'])) {
    if ($_POST['_csrfToken'] != $_SESSION['_csrfToken']) {
        header('Content-Type: application/json');
        echo json_encode(array('error' => 'Invalid CSRF token'));
        exit();
    }
    unset($_POST['_csrfToken']);
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
    return preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/', $ip, $m)
        && $m[1] <= 255 && $m[2] <= 255 && $m[3] <= 255 && $m[4] <= 255;
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
    $content = file_get_contents('/tmp/dhcp.leases');
    if ($content === false) $content = '';

    // Build static MAC set
    $static_set = array();
    foreach (static_read_all() as $s) $static_set[strtolower($s['mac'])] = $s;

    // Tag dynamic lines whose MAC has a static entry, append pure-static ones
    $dynamic_macs = array();
    $tagged_lines = array();
    foreach (explode("\n", $content) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $parts = explode(' ', $line);
        $mac_lower = isset($parts[1]) ? strtolower($parts[1]) : '';
        if ($mac_lower && isset($static_set[$mac_lower])) {
            // Dynamic lease exists but MAC is static — append 'static' flag
            $line .= ' static';
            $dynamic_macs[$mac_lower] = true;
        }
        $tagged_lines[] = $line;
    }
    foreach ($static_set as $mac_lower => $s) {
        if (isset($dynamic_macs[$mac_lower])) continue;
        $hostname = $s['hostname'] ?: 'unknown';
        $tagged_lines[] = '0 ' . $s['mac'] . ' ' . $s['ip'] . ' ' . $hostname . ' static';
    }
    $content = implode("\n", $tagged_lines);

    $logs = array();
    array_push($logs, htmlspecialchars(trim($content)));
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

// Auto-cleanup stale leases with no ARP entry
function auto_cleanup_stale_leases() {
    $leases_file = '/tmp/dhcp.leases';
    if (!file_exists($leases_file)) {
        return false;
    }
    
    $current_time = time();
    $grace_period = 300;  // 5 minutes - devices may not respond to ARP right after standby
    $stale_threshold = 3600;  // 1 hour - if device offline for 1+ hour, it's definitely stale
    
    // Get ARP table for current connected devices
    // Only count RESOLVED ARP entries (0x2 flags = REACHABLE/STALE, not 0x0 = INCOMPLETE)
    $arp_macs = array();
    $arp_content = file_get_contents('/proc/net/arp');
    if ($arp_content) {
        $arp_lines = explode("\n", $arp_content);
        foreach ($arp_lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, 'IP address') === 0) continue;
            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 4) {
                // Check if ARP entry is valid (flags must have 0x2 = REACHABLE/STALE, not 0x0 = INCOMPLETE)
                $flags = $parts[2];
                if (strpos($flags, '0x2') !== false) {
                    $mac_lower = strtolower($parts[3]);
                    $arp_macs[$mac_lower] = true;
                }
            }
        }
    }
    
    // Check leases against ARP table
    $lines = file($leases_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $new_lines = array();
    $removed_any = false;
    
    foreach ($lines as $line) {
        $parts = explode(' ', $line);
        if (count($parts) >= 2) {
            $expiry_timestamp = (int)$parts[0];
            $mac_lower = strtolower(trim($parts[1]));
            $is_connected = isset($arp_macs[$mac_lower]);
            
            // Determine if we should keep this lease
            $keep_lease = true;
            
            if ($expiry_timestamp > $current_time) {
                // Lease is still VALID (not expired yet)
                if (!$is_connected) {
                    // Device is NOT connected, but lease is still valid
                    // Check if it's been offline too long (> 1 hour) - if so, it's definitely stale
                    $offline_duration = $current_time - ($expiry_timestamp - 43200); // estimate offline time (assuming 12h default lease)
                    if ($offline_duration > $stale_threshold) {
                        // Device has been offline for > 1 hour - it's stale, remove it
                        $keep_lease = false;
                        $removed_any = true;
                    }
                }
            } else {
                // Lease has EXPIRED
                $offline_duration = $current_time - $expiry_timestamp;
                
                if ($offline_duration > $grace_period) {
                    // Device is offline for > 5 minutes after expiry - REMOVE it
                    $keep_lease = false;
                    $removed_any = true;
                }
            }
            
            if ($keep_lease) {
                $new_lines[] = $line;
            }
        }
    }
    
    // Write back if we removed anything
    if ($removed_any && count($new_lines) > 0) {
        file_put_contents($leases_file, implode("\n", $new_lines) . "\n");
        return true;
    }
    return false;
}

// Get leases WITHOUT vendor lookup (fast load) - includes wireless and ARP data
function get_leases_without_vendors(){
    $leases = array();
    $leases_file = '/tmp/dhcp.leases';
    $current_time = time();
    
    // Auto-cleanup stale leases before processing
    // DISABLED - User needs to see all leases including offline ones to track what happened
    // Use "Cleanup Offline" button to manually remove when needed
    // auto_cleanup_stale_leases();
    
    // Get DHCP lease durations from dnsmasq config (fallback)
    $default_lease_duration = 43200;  // Default 12 hours
    $dhcp_config = file_get_contents('/etc/dnsmasq.conf');
    if (preg_match('/dhcp-lease-max=(\d+)/', $dhcp_config, $matches)) {
        $default_lease_duration = (int)$matches[1];
    }
    
    // Get ARP table for online status (read file directly - faster than exec)
    // Only count RESOLVED ARP entries (0x2 flags = REACHABLE/STALE)
    // Ignore incomplete entries (0x0 flags) - they're not real connections
    $arp_table = array();
    $arp_content = file_get_contents('/proc/net/arp');
    if ($arp_content) {
        $arp_lines = explode("\n", $arp_content);
        foreach ($arp_lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, 'IP address') === 0) continue;
            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 4) {
                // Check if ARP entry is valid (flags must have 0x2 = REACHABLE/STALE)
                $flags = $parts[2];
                if (strpos($flags, '0x2') !== false) {
                    $mac_lower = strtolower($parts[3]);
                    $ip = $parts[0];
                    $arp_table[$mac_lower] = $ip;  // MAC => IP
                }
            }
        }
    }
    
    // Get dnsmasq log for DHCP actions and DNS queries
    $dhcp_actions = get_dhcp_actions_from_log();
    $dns_queries = get_recent_dns_queries();
    
    // Get DHCP option data (device class, etc.)
    $dhcp_options = get_dhcp_client_info();
    
    // First pass: collect MACs that are on WiFi (wlan0 or wlan0-1)
    $wifi_macs = array();
    if (file_exists($leases_file)) {
        $lines = file($leases_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode(' ', $line);
            if (count($parts) >= 3) {
                $ip = trim($parts[2]);
                $mac = trim($parts[1]);
                // Check if on wlan0 or wlan0-1 IP ranges
                if (strpos($ip, '192.168.0') === 0 || strpos($ip, '10.0.0') === 0) {
                    // On rogue AP
                    $wifi_macs[strtolower($mac)] = 'wlan0';
                } elseif (strpos($ip, '172.16.42') === 0) {
                    // Might be on wlan0-1, check it
                    if (check_mac_on_wlan0_1($mac)) {
                        $wifi_macs[strtolower($mac)] = 'wlan0-1';
                    }
                }
            }
        }
    }
    
    // Build static MAC lookup so dynamic entries can be flagged as static
    $static_macs = array();
    foreach (static_read_all() as $s) $static_macs[strtolower($s['mac'])] = true;

    if (file_exists($leases_file)) {
        $lines = file($leases_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode(' ', $line);
            if (count($parts) >= 3) {
                 $expiry_timestamp = (int)$parts[0];
                $mac = trim($parts[1]);
                $ip = trim($parts[2]);
                $mac_lower = strtolower($mac);
                $hostname = count($parts) >= 4 ? trim($parts[3]) : 'unknown';
                
                // Skip br-lan lease if this MAC is on WiFi (show WiFi version instead)
                // But DON'T skip if it's the PRIMARY wlan0-1 lease (wlan0-1 is bridged to br-lan)
                if (strpos($ip, '172.16.42') === 0 && isset($wifi_macs[$mac_lower]) && $wifi_macs[$mac_lower] === 'wlan0') {
                    // This is br-lan IP but MAC is on rogue AP - skip the br-lan duplicate
                    continue;
                }
                
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
                    'last_seen' => $last_seen,
                    'static' => isset($static_macs[$mac_lower])
                );
            }
        }
    }
    
    // Merge static leases: add any that don't already have a dynamic entry
    $dynamic_macs = array();
    foreach ($leases as $l) $dynamic_macs[strtolower($l['mac'])] = true;

    $static_entries = static_read_all();
    foreach ($static_entries as $s) {
        $mac_lower = strtolower($s['mac']);
        if (isset($dynamic_macs[$mac_lower])) continue; // dynamic entry exists, skip

        $is_online = isset($arp_table[$mac_lower]);
        $ip = $s['ip'];

        if (strpos($ip, '172.16.42') === 0) {
            $network_type  = check_mac_on_wlan0_1($s['mac']) ? 'Secured AP (wlan0-1)' : 'Ethernet (br-lan)';
            $connection_type = check_mac_on_wlan0_1($s['mac']) ? 'WiFi (Secured)' : 'Ethernet';
        } elseif (strpos($ip, '192.168.0') === 0) {
            $network_type = 'Rogue AP (Home)';   $connection_type = 'WiFi (Rogue)';
        } elseif (strpos($ip, '10.0.0') === 0) {
            $network_type = 'Rogue AP (Business)'; $connection_type = 'WiFi (Rogue)';
        } else {
            $network_type = 'Unknown'; $connection_type = 'Unknown';
        }

        $leases[] = array(
            'mac'                      => $s['mac'],
            'ip'                       => $ip,
            'hostname'                 => $s['hostname'] ?: 'unknown',
            'vendor'                   => null,
            'expiry_timestamp'         => 0,
            'expiry_readable'          => '∞',
            'time_remaining'           => -1,
            'time_remaining_readable'  => '∞',
            'lease_age'                => 0,
            'lease_age_readable'       => '—',
            'lease_duration'           => 0,
            'lease_duration_readable'  => '∞',
            'network_type'             => $network_type,
            'connection_type'          => $connection_type,
            'is_online'                => $is_online,
            'last_dhcp_action'         => null,
            'recent_dns'               => null,
            'device_class'             => null,
            'client_hostname'          => null,
            'session_duration'         => 0,
            'session_duration_readable'=> '—',
            'lease_mtime'              => null,
            'renewal_count'            => 0,
            'first_seen'               => null,
            'last_seen'                => null,
            'static'                   => true
        );
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
    
    $counted_macs = array();
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
                                $counted_macs[$mac] = true;
                            }
                        } else {
                            $count++;
                            $counted_macs[$mac] = true;
                        }
                    }
                }
            }
        }
    }

    // Also count static leases whose IP is in this subnet, MAC is in ARP, and not already counted
    $static_entries = static_read_all();
    foreach ($static_entries as $s) {
        if (strpos($s['ip'], $subnet_prefix) !== 0) continue;
        $mac_lower = strtolower($s['mac']);
        if (isset($counted_macs[$mac_lower])) continue; // already counted via dynamic lease
        if ($subnet_prefix === '172.16.42.') {
            if (isset($online_macs[$mac_lower])) $count++;
        } else {
            $count++;
        }
    }

    return intval($count);
}

// Get active DHCP leases (no vendor lookup - use get_vendors_for_leases separately)
function get_leases(){
    $leases = array();
    $leases_file = '/tmp/dhcp.leases';
    
    if (file_exists($leases_file)) {
        $lines = file($leases_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode(' ', $line);
            if (count($parts) >= 3) {
                $leases[] = array(
                    'mac'      => $parts[1],
                    'ip'       => $parts[2],
                    'hostname' => count($parts) >= 4 ? $parts[3] : 'unknown'
                );
            }
        }
    }
    
    return json_encode($leases);
}

// Get MAC vendor - single grep against oui_cache.txt, no file loading into memory
function get_mac_vendor($mac) {
    if (!is_valid_mac($mac)) return 'Invalid MAC';

    $mac = strtolower(str_replace('-', ':', $mac));

    // Locally administered bit = MAC randomization
    if ((hexdec(substr($mac, 0, 2)) & 0x02) === 0x02) return 'Randomized';

    $oui_file = '/sd/connectedclients/oui_cache.txt';
    if (!file_exists($oui_file)) return 'Unknown';

    // OUI file format: "XXXXXX\tVendor" (6 uppercase hex chars, no separators)
    // MAC is already validated, so no shell injection risk
    $prefix = strtoupper(str_replace(':', '', substr($mac, 0, 8)));

    $result = array();
    exec('grep -m1 "^' . $prefix . '" ' . escapeshellarg($oui_file) . ' 2>/dev/null', $result);

    if (!empty($result[0])) {
        $parts = preg_split('/\s+/', trim($result[0]), 2);
        return isset($parts[1]) ? trim($parts[1]) : 'Unknown';
    }

    return 'Unknown';
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
            // CRITICAL: Kill and fully restart dnsmasq to clear memory
            // -HUP only reloads config, doesn't clear lease cache
            exec("killall dnsmasq 2>/dev/null");
            sleep(1);
            exec("dnsmasq -C /var/etc/dnsmasq.conf 2>/dev/null &");
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
            // Update the lease expiry time - client will renew on its own schedule
            $new_lines = array();
            foreach ($lines as $line) {
                $parts = explode(' ', $line);
                if (isset($parts[1]) && strtolower(trim($parts[1])) === $mac_lower) {
                    // Update expiry time and duration
                    $parts[0] = $new_expiry;
                    $parts[4] = $lease_duration;
                    $new_lines[] = implode(' ', $parts);
                } else {
                    $new_lines[] = $line;
                }
            }
            file_put_contents($leases_file, implode("\n", $new_lines) . "\n");
            // Log the renewal to our tracking file
            log_lease_renewal($mac, $lease_duration);
            // Reload dnsmasq to apply changes
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
    // Only count RESOLVED ARP entries (0x2 flags = STALE/REACHABLE, not 0x0 = INCOMPLETE)
    $connected_macs = array();
    $arp_content = file_get_contents('/proc/net/arp');
    if ($arp_content) {
        $arp_lines = explode("\n", $arp_content);
        foreach ($arp_lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, 'IP address') === 0) continue;
            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 3) {
                // Check if ARP entry is valid (flags must have 0x2 = REACHABLE/STALE)
                $flags = $parts[2];
                if (strpos($flags, '0x2') !== false) {
                    $mac_lower = strtolower($parts[3]);
                    $connected_macs[$mac_lower] = true;  // MAC => connected
                }
            }
        }
    }
    
    if (file_exists($leases_file)) {
        $lines = file($leases_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $new_lines = array();
        
        foreach ($lines as $line) {
            $parts = explode(' ', $line);
            if (count($parts) >= 4) {
                $expiry_timestamp = (int)$parts[0];
                $mac = trim($parts[1]);
                $ip = trim($parts[2]);
                $hostname = trim($parts[3]);
                $mac_lower = strtolower($mac);
                
                // Check if device is currently in ARP table with VALID flags (0x2)
                $is_connected = isset($connected_macs[$mac_lower]);
                
                // Remove if:
                // 1. Lease has expired, OR
                // 2. Device is NOT in ARP table with valid flags (offline/not connected), OR
                // 3. Lease has no hostname (*) AND device not in valid ARP (definitely stale/ghost)
                if ($expiry_timestamp <= $current_time) {
                    // Lease has expired - remove it
                    $removed_count++;
                    $removed_macs[] = $mac;
                    continue;
                } elseif ($hostname === '*' && !$is_connected) {
                    // Device has no hostname AND not in valid ARP - definitely a ghost lease
                    $removed_count++;
                    $removed_macs[] = $mac;
                    continue;
                } elseif (!$is_connected) {
                    // Device not in ARP table with valid flags - it's offline or a stale lease
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
            
            // CRITICAL: Kill dnsmasq completely and restart it
            // Using -HUP (SIGHUP) doesn't clear memory, just reloads config
            // We need to actually restart the process to clear in-memory leases
            exec("killall dnsmasq 2>/dev/null");
            sleep(1);
            exec("dnsmasq -C /var/etc/dnsmasq.conf 2>/dev/null &");
        }
    }
    
    return json_encode(array(
        'status' => 'success',
        'removed_count' => $removed_count,
        'removed_macs' => $removed_macs,
        'message' => $removed_count > 0 ? "Removed $removed_count stale lease(s)" : "No stale leases found"
    ));
}

// ============================================================================
// STATIC LEASE HELPERS - XOR encryption, stored on SD card
// Key = MD5 of root shadow hash. Key bytes = ASCII of each hex char in MD5.
// Each lease line is encrypted and stored as a hex string in static_leases.dat
// ============================================================================

function static_lease_key() {
    $shadow = file_get_contents('/etc/shadow');
    foreach (explode("\n", $shadow) as $line) {
        if (strpos($line, 'root:') === 0) {
            $parts = explode(':', $line);
            $hash = isset($parts[1]) ? $parts[1] : 'defaultkey';
            return md5($hash);  // 32 hex chars
        }
    }
    return md5('defaultkey');
}

function static_xor($text, $key) {
    $out = '';
    $klen = strlen($key);
    for ($i = 0; $i < strlen($text); $i++) {
        $kc = $key[$i % $klen];
        // Key byte = ASCII of hex character (0-9 = 48-57, a-f = 97-102)
        $kval = ord($kc);
        $out .= chr(ord($text[$i]) ^ $kval);
    }
    return $out;
}

function static_encrypt($plaintext) {
    $key = static_lease_key();
    return bin2hex(static_xor($plaintext, $key));
}

function static_decrypt($hexline) {
    $key = static_lease_key();
    $binary = pack('H*', $hexline);
    return static_xor($binary, $key);
}

function static_lease_file() {
    return '/sd/connectedclients/static_leases.dat';
}

function static_read_all() {
    $file = static_lease_file();
    $leases = array();
    if (!file_exists($file) || !filesize($file)) return $leases;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $hexline) {
        $plain = static_decrypt(trim($hexline));
        // format: mac,ip,hostname
        $parts = explode(',', $plain);
        if (count($parts) >= 2 && is_valid_mac(trim($parts[0]))) {
            $leases[] = array(
                'mac'      => trim($parts[0]),
                'ip'       => trim($parts[1]),
                'hostname' => isset($parts[2]) ? trim($parts[2]) : ''
            );
        }
    }
    return $leases;
}

function static_write_all($leases) {
    $file = static_lease_file();
    $out = '';
    foreach ($leases as $l) {
        $plain = $l['mac'] . ',' . $l['ip'] . (isset($l['hostname']) && $l['hostname'] ? ',' . $l['hostname'] : '');
        $out .= static_encrypt($plain) . "\n";
    }
    return file_put_contents($file, $out) !== false;
}

// Get static DHCP leases
function get_static_leases() {
    return json_encode(static_read_all());
}

// Add a static DHCP lease
function add_static_lease($mac, $ip, $hostname) {
    if (!is_valid_mac($mac))
        return json_encode(array('error' => 'Invalid MAC address format'));
    if (!is_valid_ipv4($ip))
        return json_encode(array('error' => 'Invalid IPv4 address'));
    if ($hostname) {
        if (!is_valid_hostname($hostname))
            return json_encode(array('error' => 'Invalid hostname format'));
        $hostname = sanitize_hostname($hostname);
    }

    $leases = static_read_all();
    foreach ($leases as $l) {
        if (strcasecmp($l['mac'], $mac) === 0)
            return json_encode(array('error' => 'Static lease already exists for this MAC'));
    }

    $leases[] = array('mac' => $mac, 'ip' => $ip, 'hostname' => $hostname);
    if (!static_write_all($leases))
        return json_encode(array('error' => 'Failed to write static_leases.dat'));

    exec("killall -HUP dnsmasq 2>/dev/null");
    return json_encode(array('status' => 'added', 'mac' => $mac, 'ip' => $ip));
}

// Delete a static DHCP lease
function delete_static_lease($mac) {
    if (!is_valid_mac($mac))
        return json_encode(array('error' => 'Invalid MAC address format'));

    $leases = static_read_all();
    $new = array();
    $found = false;
    foreach ($leases as $l) {
        if (strcasecmp($l['mac'], $mac) === 0) { $found = true; continue; }
        $new[] = $l;
    }
    if (!$found)
        return json_encode(array('error' => 'Static lease not found for this MAC'));

    if (!static_write_all($new))
        return json_encode(array('error' => 'Failed to write static_leases.dat'));

    exec("killall -HUP dnsmasq 2>/dev/null");
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
    $log_file = '/sd/log/dnsmasq.log';
    
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
    exec("> /sd/log/dnsmasq.log");
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
    $log_file = '/sd/log/dnsmasq.log';
    
    if (!file_exists($log_file)) {
        return $actions;
    }
    
    // Read last 500 lines of log (recent activity)
    $output = array();
    exec('tail -500 ' . escapeshellarg($log_file) . ' 2>/dev/null', $output);
    
    foreach ($output as $line) {
        // Parse DHCP lines: "dnsmasq-dhcp[1234]: DHCPACK(br-lan) 192.168.1.100 04:92:26:1d:0c:37 Hello"
        // or: "DHCPREQUEST(br-lan) 172.16.42.125 04:92:26:1d:0c:37"
        if (preg_match('/DHCP(ACK|REQUEST|OFFER|RELEASE|RENEW|DECLINE).*?([0-9a-f:]{17})/i', $line, $matches)) {
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
    $log_file = '/sd/log/dnsmasq.log';
    
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
    $log_file = '/sd/log/dnsmasq.log';
    
    if (!file_exists($log_file)) {
        return $info;
    }
    
    // Read last 500 lines
    $output = array();
    exec('tail -500 ' . escapeshellarg($log_file) . ' 2>/dev/null', $output);
    
    // Track current transaction ID and associated data
    $tx_vendors = array();  // transaction ID -> vendor class
    $tx_hostnames = array(); // transaction ID -> hostname
    $tx_macs = array(); // transaction ID -> MAC
    
    foreach ($output as $line) {
        // Extract transaction ID (the number like 3051707351)
        if (preg_match('/(\d+)\s+(client provides name|vendor class|DHCPREQUEST|DHCPACK)/', $line, $tx_match)) {
            $tx_id = $tx_match[1];
            
            // vendor class: "vendor class: MSFT 5.0"
            if (preg_match('/vendor class:\s*(\S+)/i', $line, $v_match)) {
                $tx_vendors[$tx_id] = $v_match[1];
            }
            
            // client provides name: "client provides name: Bael"
            if (preg_match('/client provides name:\s*(\S+)/i', $line, $h_match)) {
                $tx_hostnames[$tx_id] = $h_match[1];
            }
            
            // DHCPREQUEST/DHCPACK: has MAC "DHCPREQUEST(br-lan) 172.16.42.125 04:92:26:1d:0c:37"
            if (preg_match('/(DHCPREQUEST|DHCPACK).*\s([0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2}:[0-9a-f]{2})/i', $line, $mac_match)) {
                $mac_lower = strtolower($mac_match[2]);
                
                // Gather all info for this MAC from any transaction
                if (isset($tx_hostnames[$tx_id])) {
                    $info[$mac_lower]['hostname'] = $tx_hostnames[$tx_id];
                }
                if (isset($tx_vendors[$tx_id])) {
                    $info[$mac_lower]['class'] = parse_vendor_class($tx_vendors[$tx_id]);
                }
                
                // Also check for hostname in DHCPACK line itself "DHCPACK... Hello"
                if (!isset($info[$mac_lower]['hostname'])) {
                    if (preg_match('/DHCPACK.*\s([0-9a-f:]+)\s+(\S+)$/i', $line, $ack_match)) {
                        $hostname = $ack_match[2];
                        if ($hostname && $hostname !== '*') {
                            $info[$mac_lower]['hostname'] = $hostname;
                        }
                    }
                }
                
                $tx_macs[$tx_id] = $mac_lower;
            }
        }
    }
    
    return $info;
}

// Parse vendor class string to readable device class
function parse_vendor_class($vendor) {
    $vendor_lower = strtolower($vendor);
    
    if (strpos($vendor_lower, 'msft') !== false || strpos($vendor_lower, 'microsoft') !== false) {
        return 'Windows';
    } elseif (strpos($vendor_lower, 'apple') !== false) {
        return 'Apple';
    } elseif (strpos($vendor_lower, 'android') !== false) {
        return 'Android';
    } elseif (strpos($vendor_lower, 'linux') !== false) {
        return 'Linux';
    } elseif (strpos($vendor_lower, 'dhcpcd') !== false) {
        return 'Linux (dhcpcd)';
    } elseif (strpos($vendor_lower, 'prism') !== false) {
        return 'Wireless Bridge';
    } else {
        return $vendor; // Return as-is if unknown
    }
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

// Log a lease renewal to our tracking file
function log_lease_renewal($mac, $duration) {
    $renewal_log = '/tmp/connectedclients_renewals.log';
    $entry = time() . ' ' . strtolower($mac) . ' ' . $duration . ' manual' . "\n";
    file_put_contents($renewal_log, $entry, FILE_APPEND);
}

// Log an automatic renewal (half-time renewal)
function log_auto_renewal($mac, $duration) {
    $renewal_log = '/tmp/connectedclients_renewals.log';
    $entry = time() . ' ' . strtolower($mac) . ' ' . $duration . ' auto' . "\n";
    file_put_contents($renewal_log, $entry, FILE_APPEND);
}

// Enable dnsmasq logging if not already enabled
function ensure_dnsmasq_logging() {
    $config_file = '/var/etc/dnsmasq.conf';
    if (!file_exists($config_file)) {
        return;
    }
    
    $config = file_get_contents($config_file);
    // Check if log-queries is already there
    if (strpos($config, 'log-queries') === false) {
        $config .= "\nlog-queries\n";
        file_put_contents($config_file, $config);
        // Restart dnsmasq to apply
        exec("killall -HUP dnsmasq 2>/dev/null");
    }
}

// Count DHCP renewals for a MAC (both manual and automatic from dnsmasq log)
function count_dhcp_renewals($mac) {
    $count = 0;
    $renewal_log = '/tmp/connectedclients_renewals.log';
    $log_file = '/sd/log/dnsmasq.log';
    
    // First, ensure dnsmasq logging is enabled
    ensure_dnsmasq_logging();
    
    $mac_lower = strtolower($mac);
    
    // Count manual renewals from our tracking file
    if (file_exists($renewal_log)) {
        $lines = file($renewal_log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode(' ', $line);
            if (count($parts) >= 2 && strtolower($parts[1]) === $mac_lower) {
                $count++;
            }
        }
    }
    
    // Count from dnsmasq log (DHCPREQUEST = renewal, DHCPACK = lease granted)
    // Count all DHCP transactions for this MAC (initial + all renewals)
    if (file_exists($log_file)) {
        $output = array();
        exec('grep -i "' . escapeshellarg($mac_lower) . '" ' . escapeshellarg($log_file) . ' 2>/dev/null | grep -i "DHCP" | wc -l', $output);
        $total_transactions = isset($output[0]) ? (int)$output[0] : 0;
        
        // If we have transactions in the log, add those not already counted
        if ($total_transactions > 0) {
            $count = max($count, $total_transactions - 1);  // -1 for initial allocation
        }
    }
    
    return $count;
}

// Get first seen timestamp (earliest occurrence in log or lease file)
function get_first_seen($mac) {
    $log_file = '/sd/log/dnsmasq.log';
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
    $log_file = '/sd/log/dnsmasq.log';
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
