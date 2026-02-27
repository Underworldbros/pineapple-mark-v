<script type='text/javascript' src='/components/infusions/connectedclients/js/helpers.js'></script>

<div style='text-align: right'><a href='#' class="refresh" onclick='get_leases_detail(); return false;'> </a></div>

<div id="small_tile_leases">Loading...</div>

<script type="text/javascript">

function get_leases_detail() {
    $.get('/components/infusions/connectedclients/functions.php?action=get_dhcp_leases', function(data) {
        try {
            data = JSON.parse(data);
            var leases_raw = data[0];
            var lines = leases_raw.split('\n').filter(function(line) { return line.trim() !== ''; });
            
            var now = Math.floor(Date.now() / 1000);
            var html = '';
            
            html += '<table style="width:100%; font-size:10px; line-height:1.6; border-collapse:collapse;">';
            html += '<tr style="background:#222; border-bottom:2px solid #444; position:sticky; top:0;">';
            html += '<td style="padding:4px 3px; border-right:1px solid #444;"><b>Hostname</b></td>';
            html += '<td style="padding:4px 3px; border-right:1px solid #444;"><b>IP Address</b></td>';
            html += '<td style="padding:4px 3px; border-right:1px solid #444;"><b>MAC Address</b></td>';
            html += '<td style="padding:4px 3px; border-right:1px solid #444;"><b>Expires In</b></td>';
            html += '<td style="padding:4px 3px;"><b>Network</b></td>';
            html += '</tr>';
            
            if (lines.length > 0) {
                for (var i = 0; i < lines.length; i++) {
                    var parts = lines[i].split(' ');
                    if (parts.length >= 4) {
                        var expiry_unix = parseInt(parts[0]);
                        var mac = parts[1];
                        var ip = parts[2];
                        var hostname = parts[3] !== '*' ? parts[3] : '<em>unknown</em>';
                        var is_static = parts.length >= 5 && parts[4] === 'static';

                        // Determine network type
                        var network = '';
                        var network_color = '';
                        if (ip.startsWith('172.16.42.')) {
                            network = 'Ethernet';
                            network_color = '#0af';
                        } else if (ip.startsWith('192.168.0.')) {
                            network = 'Rogue (Home)';
                            network_color = '#0f0';
                        } else if (ip.startsWith('10.0.0.')) {
                            network = 'Rogue (Business)';
                            network_color = '#f50';
                        } else if (ip.startsWith('192.168.1.')) {
                            network = 'Internet';
                            network_color = '#f0f';
                        }

                        // Calculate time remaining
                        var expires_text = '';
                        var expires_color = '#0f0';

                        if (is_static) {
                            expires_text = '∞';
                            expires_color = '#0af';
                        } else {
                            var seconds_left = expiry_unix - now;
                            if (seconds_left <= 0) {
                                expires_text = 'EXPIRED';
                                expires_color = '#f00';
                            } else if (seconds_left < 300) {
                                expires_text = Math.floor(seconds_left) + 's';
                                expires_color = '#f50';
                            } else if (seconds_left < 3600) {
                                expires_text = Math.floor(seconds_left / 60) + 'm';
                                expires_color = '#ff0';
                            } else if (seconds_left < 86400) {
                                expires_text = Math.floor(seconds_left / 3600) + 'h';
                            } else {
                                expires_text = Math.floor(seconds_left / 86400) + 'd';
                            }
                        }

                        var row_bg = (i % 2 === 0) ? '#1a1a1a' : '#0d0d0d';

                        html += '<tr style="background:' + row_bg + '; border-bottom:1px solid #333;">';
                        html += '<td style="padding:3px; border-right:1px solid #333; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:80px;">';
                        html += hostname;
                        if (is_static) html += ' <span style="background:#0af;color:#000;font-size:8px;font-weight:bold;padding:1px 3px;border-radius:2px;">S</span>';
                        html += '</td>';
                        html += '<td style="padding:3px; border-right:1px solid #333; font-family:monospace; font-size:9px;">' + ip + '</td>';
                        html += '<td style="padding:3px; border-right:1px solid #333; font-family:monospace; font-size:8px;">' + mac + '</td>';
                        html += '<td style="padding:3px; border-right:1px solid #333; color:' + expires_color + '; font-weight:bold;">' + expires_text + '</td>';
                        html += '<td style="padding:3px; color:' + network_color + ';"><b>' + network + '</b></td>';
                        html += '</tr>';
                    }
                }
            } else {
                html += '<tr><td colspan="5" style="padding:10px; text-align:center; color:#999;">No active leases</td></tr>';
            }
            
            html += '</table>';
            
            $('#small_tile_leases').html(html);
            
        } catch(e) {
            $('#small_tile_leases').html('<span style="color:#f00;">Error loading leases: ' + e.message + '</span>');
        }
    });
}

// Auto-load on initial display
setTimeout(function() { 
    get_leases_detail();
}, 500);

// Auto-refresh every 30 seconds (reduce CPU/memory pressure on low-RAM device)
setInterval(function() { 
    get_leases_detail();
}, 30000);
</script>
