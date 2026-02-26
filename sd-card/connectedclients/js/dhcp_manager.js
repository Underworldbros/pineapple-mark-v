function dhcp_show_tab(tab) {
    $('#dhcp_tabs a').css('background','#222').css('color','#ccc');
    $('#tab_' + tab).css('background','#333').css('color','#fff');
    
    if (tab == 'dashboard') {
        dhcp_load_dashboard();
    } else if (tab == 'leases') {
        dhcp_load_leases();
    } else if (tab == 'static') {
        dhcp_load_static();
    } else if (tab == 'ranges') {
        dhcp_load_ranges();
    } else if (tab == 'log') {
        dhcp_load_log();
    }
}

function dhcp_load_dashboard() {
    $.get('/components/infusions/connectedclients/functions.php?action=get_dashboard', function(data) {
        try {
            var d = JSON.parse(data);
            
            var rogue_label = d.rogue.preset == 'home' ? 'Home (192.168.0.x)' : 'Business (10.0.0.x)';
            var rogue_color = d.rogue.preset == 'home' ? '#0f0' : '#0af';
            
            var html = '<table style="border-spacing:15px 5px;width:100%;">';
            
            html += '<tr><td colspan="3"><h3>Interface Status</h3></td></tr>';
            html += '<tr><td style="padding:10px;background:#222;border:1px solid #444;width:33%;">';
            html += '<b style="color:#0af;">Ethernet (br-lan)</b><br/>';
            html += 'IP: ' + d.brlan.ip + '<br/>';
            html += 'Subnet: ' + d.brlan.subnet + '.x<br/>';
            html += 'Clients: <span style="color:#0f0;">' + d.brlan.clients + '</span>';
            html += '</td>';
            
            html += '<td style="padding:10px;background:#222;border:1px solid #444;width:33%;">';
            html += '<b style="color:' + rogue_color + ';">Rogue AP (wlan0)</b><br/>';
            html += 'IP: ' + (d.rogue.ip || 'N/A') + '<br/>';
            html += 'Preset: ' + rogue_label + '<br/>';
            html += 'Clients: <span style="color:#0f0;">' + d.rogue.clients + '</span>';
            html += '</td>';
            
            html += '<td style="padding:10px;background:#222;border:1px solid #444;width:33%;">';
            html += '<b style="color:#fa0;">wlan0-1</b><br/>';
            html += 'Internet Sharing<br/>';
            html += 'Clients: <span style="color:#0f0;">' + d.wlan0_1.clients + '</span>';
            html += '</td></tr>';
            
            html += '<tr><td colspan="3"><h3>DHCP Pool Status</h3></td></tr>';
            
            var brlan_pct = Math.round((d.brlan.clients / d.brlan.total_ips) * 100);
            var brlan_color = brlan_pct > 80 ? '#f00' : (brlan_pct > 60 ? '#fa0' : '#0f0');
            
            html += '<tr><td style="padding:10px;background:#222;border:1px solid #444;">';
            html += '<b>br-lan Pool</b><br/>';
            html += '<div style="background:#444;height:20px;width:100%;margin:5px 0;">';
            html += '<div style="background:' + brlan_color + ';height:100%;width:' + brlan_pct + '%;"></div>';
            html += '</div>';
            html += d.brlan.clients + ' / ' + d.brlan.total_ips + ' used (' + brlan_pct + '%)';
            html += '</td>';
            
            html += '<td style="padding:10px;background:#222;border:1px solid #444;">';
            html += '<b>Rogue Pool</b><br/>';
            html += '<div style="background:#444;height:20px;width:100%;margin:5px 0;">';
            html += '<div style="background:#0f0;height:100%;width:' + Math.min(100, Math.round((d.rogue.clients / 150) * 100)) + '%;"></div>';
            html += '</div>';
            html += d.rogue.clients + ' / 150 used';
            html += '</td>';
            
            html += '<td style="padding:10px;background:#222;border:1px solid #444;">';
            html += '<b>Total Active Leases</b><br/>';
            html += '<span style="font-size:24px;color:#0f0;">' + d.total_leases + '</span>';
            html += '</td></tr>';
            
            html += '</table>';
            
            $('#dhcp_tab_content').html(html);
        } catch(e) {
            $('#dhcp_tab_content').html('Error: ' + e);
        }
    });
}

function dhcp_load_leases() {
    // Load leases WITHOUT vendors first (fast)
    $.get('/components/infusions/connectedclients/functions.php?action=get_leases_without_vendors', function(data) {
        try {
            var leases = JSON.parse(data);
            
            var html = '<div style="margin-bottom:10px;">';
            html += '<a href="#" id="btn_export" style="cursor:pointer;text-decoration:none;color:#0af;margin-right:10px;"><b>Export CSV</b></a> ';
            html += '<a href="#" id="btn_refresh" style="cursor:pointer;text-decoration:none;color:#0af;margin-right:10px;"><b>Refresh</b></a> ';
            html += '<a href="#" id="btn_cleanup" style="cursor:pointer;text-decoration:none;color:#0af;margin-right:10px;"><b>Cleanup Offline</b></a> ';
            html += '<a href="#" id="btn_vendors" style="cursor:pointer;text-decoration:none;color:#00dd00;margin-right:10px;"><b>Load Vendors</b></a>';
            html += '<span id="vendor_load_status" style="margin-left:10px; font-weight:bold;"></span>';
            html += '</div>';
            
            if (leases.length == 0) {
                html += '<p>No active leases</p>';
            } else {
                html += '<div style="border-collapse:collapse;width:100%;">';
                
                for (var i = 0; i < leases.length; i++) {
                    var row_bg = i % 2 == 0 ? '#222' : '#1a1a1a';
                    
                    // Time remaining (with color coding)
                    var remaining_color = '#0f0';
                    if (leases[i].time_remaining < 3600) remaining_color = '#f80';  // < 1 hour = orange
                    if (leases[i].time_remaining < 600) remaining_color = '#f00';   // < 10 mins = red
                    
                    // Main collapsed row
                    html += '<div style="background:' + row_bg + ';border-bottom:1px solid #444;padding:12px;cursor:pointer;display:flex;justify-content:space-between;align-items:center;" onclick="dhcp_toggle_lease(' + i + ')">';
                    
                    // Online status indicator (very left)
                    var status_color = leases[i].is_online ? '#0f0' : '#f66';
                    html += '<div style="width:12px;height:12px;border-radius:50%;background:' + status_color + ';margin-right:12px;flex-shrink:0;"></div>';
                    
                    // Left side: Hostname & IP
                    html += '<div style="flex:1.2;">';
                    html += '<div style="font-weight:bold;font-size:13px;">';
                    html += (leases[i].hostname && leases[i].hostname !== 'unknown' ? leases[i].hostname : '<em style="color:#666;">Unknown</em>');
                    html += ' <span style="color:#999;font-size:11px;font-weight:normal;">(' + leases[i].ip + ')</span>';
                    html += '</div>';
                    html += '</div>';
                    
                    // Middle-left: Age & Duration
                    html += '<div style="flex:0.6;text-align:center;padding:0 10px;">';
                    html += '<div style="font-size:11px;color:#999;">Age: <span style="color:#0f0;font-weight:bold;">' + leases[i].lease_age_readable + '</span></div>';
                    html += '<div style="font-size:11px;color:#999;margin-top:2px;">Duration: <span style="color:#0af;font-weight:bold;">' + leases[i].lease_duration_readable + '</span></div>';
                    html += '</div>';
                    
                    // Middle-right: Release/Renew/Add Static buttons
                    html += '<div style="flex:1.1;text-align:center;white-space:nowrap;">';
                    html += '<a href="#" onclick="event.stopPropagation(); dhcp_release_lease(\'' + leases[i].mac + '\'); return false;" style="color:#f66;text-decoration:none;font-size:11px;margin-right:6px;"><b>Release</b></a>';
                    html += '<a href="#" onclick="event.stopPropagation(); dhcp_renew_lease(\'' + leases[i].mac + '\', get_selected_renew_duration()); return false;" style="color:#6f6;text-decoration:none;font-size:11px;margin-right:6px;"><b>Renew</b></a>';
                    html += '<a href="#" onclick="event.stopPropagation(); dhcp_add_to_static(\'' + leases[i].mac + '\', \'' + leases[i].ip + '\', \'' + (leases[i].hostname || '') + '\'); return false;" style="color:#0af;text-decoration:none;font-size:11px;"><b>Static</b></a>';
                    html += '</div>';
                    
                    // Right side: Time remaining
                    html += '<div style="flex:0.6;text-align:right;padding-right:15px;">';
                    html += '<div style="color:' + remaining_color + ';font-weight:bold;font-size:12px;">' + leases[i].time_remaining_readable + '</div>';
                    html += '<div style="font-size:10px;color:#999;">remaining</div>';
                    html += '</div>';
                    
                    // Expand/collapse arrow
                    html += '<div style="color:#666;font-size:16px;width:20px;" id="arrow_' + i + '">▶</div>';
                    html += '</div>';
                    
                    // Expanded details (hidden by default)
                    html += '<div id="details_' + i + '" style="display:none;background:' + row_bg + ';border-bottom:2px solid #555;padding:15px;border-left:3px solid #0af;">';
                    html += '<table style="width:100%;font-size:12px;line-height:1.8;">';
                    
                    // Device identification
                    html += '<tr><td style="width:35%;color:#999;"><b>MAC Address:</b></td><td style="font-family:monospace;color:#0f0;">' + leases[i].mac + '</td></tr>';
                    html += '<tr><td style="color:#999;"><b>Vendor:</b></td><td id="vendor_detail_' + i + '" style="color:#666;"><em>Loading...</em></td></tr>';
                    html += '<tr><td style="color:#999;"><b>Device Class:</b></td><td>' + (leases[i].device_class ? leases[i].device_class : '<em>—</em>') + '</td></tr>';
                    html += '<tr><td style="color:#999;"><b>DHCP Hostname:</b></td><td style="font-family:monospace;color:#aaf;">' + (leases[i].hostname && leases[i].hostname !== 'unknown' ? leases[i].hostname : '<em>—</em>') + '</td></tr>';
                    html += '<tr><td style="color:#999;"><b>Client Hostname:</b></td><td>' + (leases[i].client_hostname ? leases[i].client_hostname : '<em>—</em>') + '</td></tr>';
                    
                    // Network & connection info
                    html += '<tr style="border-top:1px solid #444;padding-top:8px;margin-top:8px;"><td style="color:#999;"><b>Connection Type:</b></td><td>' + leases[i].connection_type + '</td></tr>';
                    html += '<tr><td style="color:#999;"><b>Network Type:</b></td><td>' + leases[i].network_type + '</td></tr>';
                    html += '<tr><td style="color:#999;"><b>Status:</b></td><td style="' + (leases[i].is_online ? 'color:#0f0;font-weight:bold;' : 'color:#f66;') + '">' + (leases[i].is_online ? '● Online' : '● Offline') + '</td></tr>';
                    
                    // DHCP & Activity
                    html += '<tr style="border-top:1px solid #444;padding-top:8px;margin-top:8px;"><td style="color:#999;"><b>Last DHCP Action:</b></td><td>';
                    if (leases[i].last_dhcp_action) {
                        var action_color = leases[i].last_dhcp_action.action === 'ACK' ? '#0f0' : '#0af';
                        html += '<span style="color:' + action_color + ';font-weight:bold;">' + leases[i].last_dhcp_action.action + '</span> at ' + leases[i].last_dhcp_action.timestamp;
                    } else {
                        html += '<em>—</em>';
                    }
                    html += '</td></tr>';
                    
                    // NEW: Renewal activity
                    html += '<tr><td style="color:#999;"><b>Renewals:</b></td><td><span style="color:#0af;font-weight:bold;">' + (leases[i].renewal_count || 0) + '</span> times</td></tr>';
                    
                    if (leases[i].lease_mtime) {
                        html += '<tr><td style="color:#999;"><b>Last Modified:</b></td><td>' + leases[i].lease_mtime.time_ago + ' (' + leases[i].lease_mtime.readable + ')</td></tr>';
                    }
                    
                    html += '<tr><td style="color:#999;"><b>Session Duration:</b></td><td>' + leases[i].session_duration_readable + '</td></tr>';
                    
                    if (leases[i].recent_dns) {
                        html += '<tr><td style="color:#999;"><b>Last DNS Query:</b></td><td style="font-family:monospace;font-size:11px;">' + leases[i].recent_dns + '</td></tr>';
                    }
                    
                    // NEW: First seen / Last seen
                    html += '<tr style="border-top:1px solid #444;padding-top:8px;margin-top:8px;"><td style="color:#999;"><b>First Seen:</b></td><td>' + (leases[i].first_seen ? leases[i].first_seen.readable : '<em>—</em>') + '</td></tr>';
                    html += '<tr><td style="color:#999;"><b>Last Seen:</b></td><td>' + (leases[i].last_seen ? leases[i].last_seen.readable : '<em>—</em>') + '</td></tr>';
                    
                    // Lease timing
                    html += '<tr style="border-top:1px solid #444;padding-top:8px;margin-top:8px;"><td style="color:#999;"><b>Expires:</b></td><td>' + leases[i].expiry_readable + '</td></tr>';
                    html += '<tr><td style="color:#999;"><b>Time Remaining:</b></td><td style="color:' + remaining_color + ';font-weight:bold;">' + leases[i].time_remaining_readable + '</td></tr>';
                    
                    html += '</table>';
                    html += '</div>';
                }
                
                html += '</div>';
            }
            
            // Store leases for later vendor lookup
            window.currentLeases = leases;
            
            $('#dhcp_tab_content').html(html);
            
            // Attach click handlers to links (unbind first to avoid stacking)
            $('#btn_export').unbind('click').click(function(e) {
                e.preventDefault();
                dhcp_export_csv();
                return false;
            });
            $('#btn_refresh').unbind('click').click(function(e) {
                e.preventDefault();
                dhcp_load_leases();
                return false;
            });
            $('#btn_cleanup').unbind('click').click(function(e) {
                e.preventDefault();
                dhcp_cleanup_offline();
                return false;
            });
            $('#btn_vendors').unbind('click').click(function(e) {
                e.preventDefault();
                dhcp_load_vendors_async();
                return false;
            });
        } catch(e) {
            $('#dhcp_tab_content').html('Error: ' + e);
        }
    });
}

function dhcp_toggle_lease(index) {
    var details = $('#details_' + index);
    var arrow = $('#arrow_' + index);
    
    if (details.is(':visible')) {
        details.slideUp(150);
        arrow.text('▶');
    } else {
        details.slideDown(150);
        arrow.text('▼');
    }
}

function dhcp_load_vendors_async() {
    if (!window.currentLeases || window.currentLeases.length === 0) {
        alert('No leases to load vendors for');
        return;
    }
    
    var statusEl = $('#vendor_load_status');
    statusEl.text('Loading vendors...');
    statusEl.css('color', '#ffaa00');
    
    var macs = window.currentLeases.map(function(lease) { return lease.mac; });
    
    $.ajax({
        url: '/components/infusions/connectedclients/functions.php?action=get_vendors_for_leases',
        type: 'POST',
        data: { macs: JSON.stringify(macs), _csrfToken: $('meta[name=_csrfToken]').attr('content') },
        dataType: 'json',
        timeout: 15000,
        success: function(vendors) {
            // Update vendor cells in the collapsed and expanded rows
            for (var i = 0; i < window.currentLeases.length; i++) {
                var mac = window.currentLeases[i].mac;
                var vendor = vendors[mac];
                var displayText = vendor;
                var displayColor = '#ccc';
                
                if (!vendor || vendor === 'Unknown') {
                    // Check if it looks like a randomized MAC (typically odd first octet)
                    var firstOctet = parseInt(mac.split(':')[0], 16);
                    if (firstOctet % 2 === 1) {  // Locally administered (randomized)
                        displayText = '🔒 Randomized';
                        displayColor = '#f80';
                    } else {
                        displayText = '❓ Not found';
                        displayColor = '#888';
                    }
                }
                
                // Update both the collapsed and expanded vendor displays
                $('#vendor_' + i).text(displayText).css('color', displayColor);
                $('#vendor_detail_' + i).text(displayText).css('color', displayColor);
            }
            
            statusEl.text('✓ Vendors loaded');
            statusEl.css('color', '#00dd00');
            setTimeout(function() { statusEl.text(''); }, 3000);
        },
        error: function(xhr, ajaxStatus, errorThrown) {
            var errorMsg = 'Failed to load vendors';
            if (ajaxStatus === 'timeout') {
                errorMsg = 'Timeout loading vendors';
            }
            statusEl.text('✗ ' + errorMsg);
            statusEl.css('color', '#ff4444');
        }
    });
}

function dhcp_release_lease(mac) {
    console.log('Release lease called for:', mac);
    $.post('/components/infusions/connectedclients/functions.php?action=release_lease', 
        { mac: mac, _csrfToken: $('meta[name=_csrfToken]').attr('content') },
        function(data) {
            console.log('Release response:', data);
            try {
                var response = JSON.parse(data);
                if (response.error) {
                    alert('Error: ' + response.error);
                } else {
                    alert('Lease released for ' + mac);
                    dhcp_load_leases();
                }
            } catch(e) {
                alert('Error: Invalid response');
            }
        }
    ).fail(function(xhr, status, error) {
        console.log('Release failed:', status, error);
        alert('Error: ' + error);
    });
}

function dhcp_renew_lease(mac, duration) {
    console.log('Renew lease called for:', mac, 'Duration:', duration);
    duration = duration || 3600; // Default to 1 hour if not specified
    $.post('/components/infusions/connectedclients/functions.php?action=renew_lease', 
        { mac: mac, duration: duration, _csrfToken: $('meta[name=_csrfToken]').attr('content') },
        function(data) {
            console.log('Renew response:', data);
            try {
                var response = JSON.parse(data);
                if (response.error) {
                    alert('Error: ' + response.error);
                } else {
                    alert('Lease renewed for ' + mac + '\n\nDuration: ' + format_seconds(duration));
                    dhcp_load_leases();
                }
            } catch(e) {
                alert('Error: Invalid response');
            }
        }
    ).fail(function(xhr, status, error) {
        console.log('Renew failed:', status, error);
        alert('Error: ' + error);
    });
}

function dhcp_add_to_static(mac, ip, hostname) {
    // Switch to static tab and populate form
    dhcp_show_tab('static');
    
    // Pre-fill the form
    $('#static_mac').val(mac);
    $('#static_ip').val(ip);
    $('#static_hostname').val(hostname);
    
    // Show the form
    $('#add_static_form').show();
    
    // Scroll to form
    $('html, body').animate({scrollTop: 0}, 200);
}

function dhcp_export_csv() {
    document.getElementById('dhcp_export_csrf').value = $('meta[name=_csrfToken]').attr('content');
    document.getElementById('dhcp_export_form').submit();
}

function dhcp_load_static() {
    $.get('/components/infusions/connectedclients/functions.php?action=get_static_leases', function(data) {
        try {
            var leases = JSON.parse(data);
            
            var html = '<div style="margin-bottom:10px;">';
            html += '<button onclick="dhcp_show_add_static()">Add Static Lease</button> ';
            html += '<button onclick="dhcp_load_static()">Refresh</button>';
            html += '</div>';
            
            html += '<div id="add_static_form" style="display:none;padding:10px;background:#222;margin-bottom:10px;">';
            html += '<h4>Add Static Lease</h4>';
            html += 'MAC: <input type="text" id="static_mac" placeholder="aa:bb:cc:dd:ee:ff"><br/><br/>';
            html += 'IP: <input type="text" id="static_ip" placeholder="172.16.42.100"><br/><br/>';
            html += 'Hostname: <input type="text" id="static_hostname" placeholder="mydevice"><br/><br/>';
            html += '<button onclick="dhcp_add_static()">Add</button> ';
            html += '<button onclick="$(\'#add_static_form\').hide()">Cancel</button>';
            html += '</div>';
            
            if (leases.length == 0) {
                html += '<p>No static leases configured</p>';
            } else {
                html += '<table style="width:100%;border-collapse:collapse;border-spacing:0;">';
                html += '<tr style="background:#333;"><th style="padding:8px;text-align:left;">MAC</th><th style="padding:8px;text-align:left;">IP</th><th style="padding:8px;text-align:left;">Hostname</th><th style="padding:8px;text-align:left;">Actions</th></tr>';
                
                for (var i = 0; i < leases.length; i++) {
                    var row_bg = i % 2 == 0 ? '#222' : '#1a1a1a';
                    html += '<tr style="background:' + row_bg + ';">';
                    html += '<td style="padding:6px;">' + leases[i].mac + '</td>';
                    html += '<td style="padding:6px;">' + leases[i].ip + '</td>';
                    html += '<td style="padding:6px;">' + (leases[i].hostname || '<em>none</em>') + '</td>';
                    html += '<td style="padding:6px;">';
                    html += '<a href="#" onclick="dhcp_delete_static(\'' + leases[i].mac + '\')" style="color:#f66;">Delete</a>';
                    html += '</td></tr>';
                }
                html += '</table>';
            }
            
            $('#dhcp_tab_content').html(html);
        } catch(e) {
            $('#dhcp_tab_content').html('Error: ' + e);
        }
    });
}

function dhcp_show_add_static() {
    $('#add_static_form').show();
}

function dhcp_add_static() {
    var mac = $('#static_mac').val();
    var ip = $('#static_ip').val();
    var hostname = $('#static_hostname').val();
    
    if (!mac || !ip) {
        alert('MAC and IP are required');
        return;
    }
    
    // Basic client-side validation
    if (!is_valid_mac_format(mac)) {
        alert('Invalid MAC address format (use xx:xx:xx:xx:xx:xx)');
        return;
    }
    
    if (!is_valid_ipv4_format(ip)) {
        alert('Invalid IP address format');
        return;
    }
    
    $.post('/components/infusions/connectedclients/functions.php?action=add_static_lease',
        { mac: mac, ip: ip, hostname: hostname, _csrfToken: $('meta[name=_csrfToken]').attr('content') },
        function(data) {
            try {
                var response = JSON.parse(data);
                if (response.error) {
                    alert('Error: ' + response.error);
                } else {
                    $('#add_static_form').hide();
                    $('#static_mac').val('');
                    $('#static_ip').val('');
                    $('#static_hostname').val('');
                    dhcp_load_static();
                }
            } catch(e) {
                alert('Error: Invalid response');
            }
        }
    );
}

function dhcp_delete_static(mac) {
    $.post('/components/infusions/connectedclients/functions.php?action=delete_static_lease',
        { mac: mac, _csrfToken: $('meta[name=_csrfToken]').attr('content') },
        function(data) {
            try {
                var response = JSON.parse(data);
                if (response.error) {
                    alert('Error: ' + response.error);
                } else {
                    dhcp_load_static();
                }
            } catch(e) {
                alert('Error: Invalid response');
            }
        }
    );
}

function dhcp_load_ranges() {
    $.get('/components/infusions/connectedclients/functions.php?action=get_dhcp_ranges', function(data) {
        try {
            var ranges = JSON.parse(data);
            
            var html = '<table style="width:100%;border-collapse:collapse;border-spacing:0;">';
            html += '<tr style="background:#333;"><th style="padding:8px;text-align:left;">Interface</th><th style="padding:8px;text-align:left;">Start IP</th><th style="padding:8px;text-align:left;">Limit</th><th style="padding:8px;text-align:left;">Actions</th></tr>';
            
            for (var i = 0; i < ranges.length; i++) {
                var row_bg = i % 2 == 0 ? '#222' : '#1a1a1a';
                html += '<tr style="background:' + row_bg + ';">';
                html += '<td style="padding:6px;">' + ranges[i].interface + '</td>';
                html += '<td style="padding:6px;">' + ranges[i].start + '</td>';
                html += '<td style="padding:6px;">' + ranges[i].limit + '</td>';
                html += '<td style="padding:6px;">';
                html += '<span style="color:#666;">View only</span>';
                html += '</td></tr>';
            }
            html += '</table>';
            
            $('#dhcp_tab_content').html(html);
        } catch(e) {
            $('#dhcp_tab_content').html('Error: ' + e);
        }
    });
}

function dhcp_load_log() {
    $.get('/components/infusions/connectedclients/functions.php?action=get_dhcp_log', function(data) {
        try {
            var log = JSON.parse(data);
            
            var html = '<div style="margin-bottom:10px;">';
            html += '<button onclick="dhcp_clear_log()">Clear Log</button> ';
            html += '<button onclick="dhcp_load_log()">Refresh</button>';
            html += '</div>';
            
            if (log.length == 0) {
                html += '<p>No log entries</p>';
            } else {
                html += '<pre style="background:#111;padding:10px;overflow:auto;max-height:400px;">';
                for (var i = 0; i < log.length; i++) {
                    html += log[i] + '\n';
                }
                html += '</pre>';
            }
            
            $('#dhcp_tab_content').html(html);
        } catch(e) {
            $('#dhcp_tab_content').html('Error: ' + e);
        }
    });
}

function dhcp_clear_log() {
    $.post('/components/infusions/connectedclients/functions.php?action=clear_log',
        { _csrfToken: $('meta[name=_csrfToken]').attr('content') },
        function(data) {
            dhcp_load_log();
        }
    );
}

// ============================================================================
// CLIENT-SIDE VALIDATION HELPERS
// ============================================================================

function is_valid_mac_format(mac) {
    // Check for valid MAC address format (xx:xx:xx:xx:xx:xx or xx-xx-xx-xx-xx-xx)
    return /^([0-9a-fA-F]{2}[:-]){5}([0-9a-fA-F]{2})$/.test(mac);
}

function is_valid_ipv4_format(ip) {
    // Check for valid IPv4 address format
    return /^(\d{1,3}\.){3}\d{1,3}$/.test(ip) && 
           ip.split('.').every(function(octet) { 
               var num = parseInt(octet);
               return num >= 0 && num <= 255;
           });
}



function dhcp_cleanup_offline() {
    // Note: confirm() doesn't work in this environment, so we proceed without confirmation
    // The operation is safe - it only removes truly offline leases
    console.log('Cleanup offline leases initiated');
    
    var csrfToken = $('meta[name=_csrfToken]').attr('content');
    console.log('CSRF Token:', csrfToken ? 'present' : 'MISSING');
    
    $.post('/components/infusions/connectedclients/functions.php?action=cleanup_offline_leases', 
        { _csrfToken: csrfToken },
        function(data) {
            console.log('POST response received:', data);
            try {
                var response = JSON.parse(data);
                var msg = 'Cleanup Complete!\n\n' + response.message;
                if (response.removed_count > 0) {
                    msg += '\n\nRemoved MACs:\n';
                    for (var i = 0; i < response.removed_macs.length; i++) {
                        msg += '  - ' + response.removed_macs[i] + '\n';
                    }
                }
                alert(msg);
                dhcp_load_leases();
            } catch(e) {
                console.log('Error parsing response:', e);
                alert('Error: Invalid response - ' + data);
            }
        }
    ).fail(function(xhr, status, error) {
        console.log('POST failed:', status, error);
        alert('Error: ' + error);
    });
}

// Get the selected initial duration from the dropdown
function get_selected_initial_duration() {
    var duration = $('#initial_duration').val();
    return duration ? parseInt(duration) : 43200; // Default to 12 hours
}

// Handle initial duration change
function on_initial_duration_change() {
    var duration = get_selected_initial_duration();
    console.log('Setting initial lease duration to:', duration);
    
    $.post('/components/infusions/connectedclients/functions.php?action=set_initial_duration',
        { duration: duration, _csrfToken: $('meta[name=_csrfToken]').attr('content') },
        function(data) {
            console.log('Initial duration response:', data);
            try {
                var response = JSON.parse(data);
                if (response.error) {
                    alert('Error: ' + response.error);
                } else {
                    alert('Initial DHCP lease duration set to ' + response.duration_display);
                }
            } catch(e) {
                alert('Error: Invalid response');
            }
        }
    ).fail(function(xhr, status, error) {
        console.log('Initial duration failed:', status, error);
        alert('Error: ' + error);
    });
}

// Get the selected renew duration from the dropdown
function get_selected_renew_duration() {
    var duration = $('#renew_duration').val();
    return duration ? parseInt(duration) : 3600; // Default to 1 hour
}

// Format seconds into human-readable time
function format_seconds(seconds) {
    if (seconds < 60) {
        return seconds + ' second' + (seconds !== 1 ? 's' : '');
    } else if (seconds < 3600) {
        var mins = Math.floor(seconds / 60);
        return mins + ' minute' + (mins !== 1 ? 's' : '');
    } else if (seconds < 86400) {
        var hours = Math.floor(seconds / 3600);
        return hours + ' hour' + (hours !== 1 ? 's' : '');
    } else {
        var days = Math.floor(seconds / 86400);
        return days + ' day' + (days !== 1 ? 's' : '');
    }
}
