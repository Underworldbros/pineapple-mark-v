<h2 style="display:inline-block;">DHCP Leases: <span id="connected_clients_count"></span> <button onclick="get_large_tab_clients();return false;" title="Refresh" style="background:none;border:none;color:#0af;font-size:16px;cursor:pointer;padding:0 8px;margin:0;vertical-align:middle;">↻</button></h2>                                              
<center><div id='clients_report'>Loading data, please wait.</div></center>

<form name="mac_address_form" action="/components/infusions/connectedclients/functions.php?action=add_blacklisted_mac" method="POST" onsubmit="$(this).AJAXifyForm(refresh_dhcp_tab); return false;">
<input type="hidden" id="add_blacklist_csrf_token" name="_csrfToken" value="" />
<input type="hidden" id="mac_address" name="mac_address" value="" />
<input type="submit" id="add_blacklist_mac_button" style="height: 0px; width: 0px; border: none; padding: 0px;" hidefocus="true" />
</form>

<script type="text/javascript">
   get_large_tab_clients();
</script>
