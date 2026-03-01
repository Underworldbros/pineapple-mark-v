<script type='text/javascript' src='/components/infusions/connectedclients/js/dhcp_manager.js'></script>

<h2 style="display:flex;justify-content:space-between;align-items:center;">
  <span>DHCP Manager</span>
  <span style="font-size:14px;margin-right:15px;">
    <a href="#" onclick="dhcp_load_dashboard();return false;" style="text-decoration:none;color:#0af;margin-right:20px;"><b>↻ Refresh</b></a>
    <a href="#" onclick="parent.pineapple.closePanel();return false;" style="text-decoration:none;color:#f66;"><b>✕ Close</b></a>
  </span>
</h2>

<hr />

<ul id="dhcp_tabs" style="list-style:none;padding:0;margin:0 0 10px 0;">
<li style="display:inline-block;margin-right:3px;"><a href="#" id="tab_dashboard" class="dhcp_tab_active" onclick="dhcp_show_tab('dashboard');return false;" style="padding:5px 10px;cursor:pointer;text-decoration:none;color:#fff;background:#333;">Dashboard</a></li>
<li style="display:inline-block;margin-right:3px;"><a href="#" id="tab_leases" onclick="dhcp_show_tab('leases');return false;" style="padding:5px 10px;cursor:pointer;text-decoration:none;color:#ccc;background:#222;">Leases</a></li>
<li style="display:inline-block;margin-right:3px;"><a href="#" id="tab_static" onclick="dhcp_show_tab('static');return false;" style="padding:5px 10px;cursor:pointer;text-decoration:none;color:#ccc;background:#222;">Static</a></li>
<li style="display:inline-block;margin-right:3px;"><a href="#" id="tab_ranges" onclick="dhcp_show_tab('ranges');return false;" style="padding:5px 10px;cursor:pointer;text-decoration:none;color:#ccc;background:#222;">Ranges</a></li>
<li style="display:inline-block;margin-right:3px;"><a href="#" id="tab_log" onclick="dhcp_show_tab('log');return false;" style="padding:5px 10px;cursor:pointer;text-decoration:none;color:#ccc;background:#222;">Log</a></li>
<li style="display:inline-block;margin-left:20px;"><label style="color:#999;margin-right:8px;">Initial Duration:</label><select id="initial_duration" onchange="on_initial_duration_change();" style="padding:4px 8px;background:#222;color:#0f0;border:1px solid #444;"><option value="1800">30 minutes</option><option value="3600">1 hour</option><option value="7200">2 hours</option><option value="14400">4 hours</option><option value="28800">8 hours</option><option value="43200" selected>12 hours</option><option value="86400">24 hours</option><option value="604800">7 days</option></select></li>
<li style="display:inline-block;margin-left:20px;"><label style="color:#999;margin-right:8px;">Renew Duration:</label><select id="renew_duration" onchange="on_renew_duration_change();" style="padding:4px 8px;background:#222;color:#0f0;border:1px solid #444;"><option value="1800">30 minutes</option><option value="3600" selected>1 hour</option><option value="7200">2 hours</option><option value="14400">4 hours</option><option value="28800">8 hours</option><option value="43200">12 hours</option><option value="86400">24 hours</option><option value="604800">7 days</option></select></li>
</ul>

<div id="dhcp_tab_content">Loading...</div>

<form id="dhcp_export_form" name="dhcp_export_form" action="/components/infusions/connectedclients/functions.php?action=export_leases" method="POST" target="_blank">
<input type="hidden" id="dhcp_export_csrf" name="_csrfToken" value="" />
</form>

<script type="text/javascript">
// Initialize localStorage values and auto-load dashboard on tab open
dhcp_init_storage();
dhcp_load_dashboard();
</script>
