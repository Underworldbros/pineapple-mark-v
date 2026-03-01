<script type='text/javascript' src='/components/infusions/connectedclients/js/helpers.js'></script>

<style>
#cc_tabs { list-style: none; padding: 0; margin: 0 0 10px 0; }
#cc_tabs li { display: inline-block; margin-right: 3px; }
#cc_tabs li a { padding: 5px 10px; cursor: pointer; text-decoration: none; color: #ccc; }
#cc_tabs li a.cc_active { color: #fff; border-bottom: 2px solid #fff; }
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.pineapple-spinner {
    display: inline-block;
    animation: spin 1s linear infinite;
}
.pineapple-spinner img {
    width: 60px;
    height: 60px;
}
</style>

<ul id="cc_tabs">
  <li><a class="cc_active" data-url="/components/infusions/connectedclients/tabs/dhcp_manager.php" onclick="cc_select_tab(this)">DHCP Manager</a></li>
  <li><a data-url="/components/infusions/connectedclients/tabs/connected_clients.php" onclick="cc_select_tab(this)">Connected Clients</a></li>
  <li><a data-url="/components/infusions/connectedclients/tabs/rogue_config.php" onclick="cc_select_tab(this)">Rogue AP</a></li>
  <li><a data-url="/components/infusions/connectedclients/tabs/blacklist.php" onclick="cc_select_tab(this)">Blacklist</a></li>
  <li><a data-url="/components/infusions/connectedclients/tabs/about.php" onclick="cc_select_tab(this)">About</a></li>
</ul>
<div id="cc_tab_content">Loading...</div>

<script type="text/javascript">
function cc_select_tab(el) {
  var url = $(el).data('url');
  $('#cc_tabs li a').removeClass('cc_active');
  $(el).addClass('cc_active');
  $.get(url, function(data) {
    $('#cc_tab_content').html(data);
  });
}
// Load first tab on open
cc_select_tab($('#cc_tabs li a:first')[0]);
</script>
