function get_large_tab_clients(){

  $.get('/components/infusions/connectedclients/functions.php?action=get_dhcp_leases', function(data){

    data = JSON.parse(data);
    var clients = new Array();
    var dhcp = data[0].split('\n');
    for (var i = dhcp.length - 1; i >= 0; i--) {
      dhcp[i] = dhcp[i].split(' ');
    }

    var dhcp_length = dhcp.length - 1;    
    var html_to_print = "<table style='border-spacing: 25px 2px'><tr><th>UNIX timestamp</th><th>HW Address</th><th>IP Address</th><th>hostname</th><th>Add to Blacklist</th></tr>";
    if(dhcp.length != 0){
      for (var x = 0; x < dhcp_length; x++){
        html_to_print += "<tr><td>"+dhcp[x][0]+"</td><td>"+dhcp[x][1]+"</td><td>"+dhcp[x][2]+"</td><td>";
        if(dhcp[x][3] == "*"){
          html_to_print += "&lt;host name undefined&gt;</td>";
        }else{
          html_to_print += dhcp[x][3]+"</td>";
        }
        html_to_print += "<td><a href=\"#\" onclick=\"add_blacklisted_mac('" + dhcp[x][1] + "')\">blacklist</a></td></tr>";
      }
    }else{
      html_to_print += "<tr>No DHCP clients found</tr>";
    }
    html_to_print += "</table>";
    
    $('#clients_report').html(html_to_print);
    $('#connected_clients_count').html(dhcp_length);
  });
}

function get_small_tab_clients(){

  $.get('/components/infusions/connectedclients/functions.php?action=get_dhcp_leases', function(data){

    data = JSON.parse(data);
    var clients = new Array();
    var dhcp = data[0].split('\n');
    for (var i = dhcp.length - 1; i >= 0; i--) {
      dhcp[i] = dhcp[i].split(' ');
    }

    var dhcp_length = dhcp.length - 1;
    var html_to_print = "DHCP Leases: " + dhcp_length + "<br /><br />";
    if(dhcp.length != 0){
      for (var x = 0; x < dhcp_length; x++){
        if(dhcp[x][3] == "*"){
          html_to_print += "&lt;host name undefined&gt;<br />";
        }else{
          html_to_print += dhcp[x][3]+"<br />";
        }
      }
    }else{
      html_to_print += "No DHCP clients found<br />";
    }
    $('#small_tab_clients_list').html(html_to_print);
  });
}

function get_blacklist_macs(){
  $.get('/components/infusions/connectedclients/functions.php?action=get_blacklisted_macs', function(data){
  
    var macs = JSON.parse(data);
    var macs_length = macs.length;
    $('#blacklist_count').html(macs_length);
    var html_to_print = "";
    for (var x = 0; x < macs_length; x++){
      html_to_print += macs[x] + " <a href=\"#\" onclick=\"remove_blacklisted_mac('" + macs[x] + "')\">remove</a><br />";
    }
    $('#blacklisted_macs').html(html_to_print);
  });
}

function remove_blacklisted_mac(mac_to_remove){
  document.getElementById('mac_address').value=mac_to_remove;
  document.getElementById('remove_blacklist_csrf_token').value=$('meta[name=_csrfToken]').attr('content');
  document.getElementById("remove_blacklist_mac_button").click();
}

function add_blacklisted_mac(mac_to_add){
  document.getElementById('mac_address').value=mac_to_add;
  document.getElementById('add_blacklist_csrf_token').value=$('meta[name=_csrfToken]').attr('content');
  document.getElementById("add_blacklist_mac_button").click();
}

function deauthenticate_wlan0_mac(mac_to_deauth){
  document.getElementById('deauth_wlan0_mac_address').value=mac_to_deauth;
  document.getElementById('deauth_wlan0_mac_csrf_token').value=$('meta[name=_csrfToken]').attr('content');
  document.getElementById("deauthenticate_wlan0_mac_button").click();
}

function deauthenticate_wlan0_1_mac(mac_to_deauth){
  document.getElementById('deauth_wlan0_1_mac_address').value=mac_to_deauth;
  document.getElementById('deauth_wlan0_1_mac_csrf_token').value=$('meta[name=_csrfToken]').attr('content');
  document.getElementById("deauthenticate_wlan0_1_mac_button").click();
}

function disassociate_wlan0_mac(mac_to_disassociate){
  document.getElementById('disassociate_wlan0_mac_address').value=mac_to_disassociate;
  document.getElementById('disassociate_wlan0_mac_csrf_token').value=$('meta[name=_csrfToken]').attr('content');
  document.getElementById("disassociate_wlan0_mac_button").click();
}

function disassociate_wlan0_1_mac(mac_to_disassociate){
  document.getElementById('disassociate_wlan0_1_mac_address').value=mac_to_disassociate;
  document.getElementById('disassociate_wlan0_1_mac_csrf_token').value=$('meta[name=_csrfToken]').attr('content');
  document.getElementById("disassociate_wlan0_1_mac_button").click();
}

function cc_refresh_current_tab() {
  var active = $('#cc_tabs li a.cc_active');
  if (active.length) { cc_select_tab(active[0]); }
}

function refresh_blacklist_tab() {
  cc_refresh_current_tab();
}

function refresh_dhcp_tab() {
  cc_refresh_current_tab();
}

function get_iw_connected_clients(){
  // Get br-lan (ethernet) clients from DHCP leases
  $.get('/components/infusions/connectedclients/functions.php?action=get_brlan_clients', function(data){
    var clients = JSON.parse(data);
    $('#brlan_clients_count').html(clients.length);
    if (clients.length > 0){
      var html_to_print = "<table style='border-spacing: 25px 2px'><tr><th>MAC Address</th><th>IP Address</th><th>Hostname</th><th>Action</th></tr>";
      for (var x = 0; x < clients.length; x++){
        var hostname = clients[x].hostname ? clients[x].hostname : '<em>unknown</em>';
        html_to_print += "<tr><td>" + clients[x].mac + "</td><td>" + clients[x].ip + "</td><td>" + hostname + "</td><td><a href=\"#\" onclick=\"add_blacklisted_mac('" + clients[x].mac + "')\">blacklist</a></td></tr>";
      }
      html_to_print += "</table>";
    } else {
      var html_to_print = "<em>No ethernet clients found</em>";
    }
    $('#brlan_clients').html(html_to_print);
  });

  // Get rogue AP (wlan0) clients from DHCP leases
  $.get('/components/infusions/connectedclients/functions.php?action=get_rogue_clients', function(data){
    var clients = JSON.parse(data);
    $('#rogue_clients_count').html(clients.length);
    if (clients.length > 0){
      var html_to_print = "<table style='border-spacing: 25px 2px'><tr><th>MAC Address</th><th>IP Address</th><th>Hostname</th><th>Action</th></tr>";
      for (var x = 0; x < clients.length; x++){
        var hostname = clients[x].hostname ? clients[x].hostname : '<em>unknown</em>';
        html_to_print += "<tr><td>" + clients[x].mac + "</td><td>" + clients[x].ip + "</td><td>" + hostname + "</td><td><a href=\"#\" onclick=\"add_blacklisted_mac('" + clients[x].mac + "')\">blacklist</a></td></tr>";
      }
      html_to_print += "</table>";
    } else {
      var html_to_print = "<em>No rogue AP clients found</em>";
    }
    $('#rogue_clients').html(html_to_print);
  });

   $.get('/components/infusions/connectedclients/functions.php?action=get_iw_wlan0_1_clients', function(data){

     var clients = JSON.parse(data);
     $('#wlan0_1_clients_count').html(clients.length);
     if (clients.length > 0){
       var html_to_print = "<table style='border-spacing: 25px 2px'><tr><th>MAC Address</th><th>IP Address</th><th>Hostname</th><th>Action</th></tr>";
       for (var x = 0; x < clients.length; x++){
         var hostname = clients[x].hostname ? clients[x].hostname : '<em>unknown</em>';
         html_to_print += "<tr><td>" + clients[x].mac + "</td><td>" + clients[x].ip + "</td><td>" + hostname + "</td><td><a href=\"#\" onclick=\"add_blacklisted_mac('" + clients[x].mac + "')\">blacklist</a></td></tr>";
       }
       html_to_print += "</table>";
     } else {
       var html_to_print = "<em>No wlan0-1 clients found</em>";
     }
     $('#wlan0_1_client_macs').html(html_to_print);
   });
   
}


function refresh_clients_tab() {
  cc_refresh_current_tab();
}

function get_rogue_status() {
  $.get('/components/infusions/connectedclients/functions.php?action=get_rogue_config', function(data){
    try {
      var config = JSON.parse(data);
      var preset = config.preset || 'unknown';
      var wlan0_ip = config.wlan0_ip || 'none';
      var brlan_ip = config.brlan_ip || 'none';
      var in_bridge = config.in_bridge > 0 ? 'Yes' : 'No';

      var preset_label = preset;
      if (preset == 'home') preset_label = '<span style="color:#0f0;">Home (192.168.0.1/24)</span>';
      else if (preset == 'business') preset_label = '<span style="color:#0af;">Business (10.0.0.1/24)</span>';
      else preset_label = '<span style="color:#fa0;">' + preset + '</span>';

      var html = '<table style="border-spacing: 15px 3px;">';
      html += '<tr><td>Active Preset:</td><td><b>' + preset_label + '</b></td></tr>';
      html += '<tr><td>wlan0 IP:</td><td>' + wlan0_ip + '</td></tr>';
      html += '<tr><td>br-lan IP:</td><td>' + brlan_ip + '</td></tr>';
      html += '<tr><td>wlan0 in bridge:</td><td>' + in_bridge + '</td></tr>';
      html += '</table>';
      $('#rogue_status').html(html);
    } catch(e) {
      $('#rogue_status').html('Error reading config: ' + data);
    }
  });
}

function show_spinner() {
   $('#rogue_result').html('<span class="pineapple-spinner"><img src="/pineapple/includes/img/mk5_logo.gif" alt="Loading" /></span><br /><span style="color:#ff0; margin-top:10px;">Switching subnet... please wait</span>');
}

function switch_rogue_preset(preset) {
   document.getElementById('rogue_preset_value').value = preset;
   document.getElementById('rogue_preset_csrf').value = $('meta[name=_csrfToken]').attr('content');
   document.getElementById('rogue_preset_submit').click();
}

function rogue_switch_done() {
  setTimeout(function(){
    $('#rogue_result').html('<span style="color:#0f0;">Done. Refreshing status...</span>');
    get_rogue_status();
    setTimeout(function(){ $('#rogue_result').html(''); }, 3000);
  }, 2000);
}   
