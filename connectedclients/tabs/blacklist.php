<h2>Blacklisted Macs: <span id="blacklist_count"></span> <a href="#" onclick="get_blacklist_macs();return false;" style="text-decoration:none;color:#0af;margin-left:20px;margin-right:20px;"><b>↻ Refresh</b></a></h2>
<div id="blacklisted_macs">Loading Mac Blacklist ...</div>

<form name="mac_address_form" action="/components/infusions/connectedclients/functions.php?action=remove_blacklisted_mac" method="POST" onsubmit="$(this).AJAXifyForm(refresh_blacklist_tab); return false;">
<input type="hidden" id="remove_blacklist_csrf_token" name="_csrfToken" value="" />
<input type="hidden" id="mac_address" name="mac_address" value="" />
<input type="submit" id="remove_blacklist_mac_button" style="height: 0px; width: 0px; border: none; padding: 0px;" hidefocus="true" />
</form>

<script type="text/javascript">
    get_blacklist_macs();
</script>
