<div class="my_tile_content">
	
<?php

global $directory, $rel_dir, $version, $name;
require($directory."includes/vars.php");

?>

<script type='text/javascript' src='/components/infusions/status/includes/js/infusion.js'></script>
<script type='text/javascript' src='/components/infusions/status/includes/js/jquery.idTabs.min.js'></script>
<style>@import url('/components/infusions/status/includes/css/infusion.css')</style>

<script type="text/javascript">
	$(document).ready(function(){ status_init_small(); });
</script>

<div style='text-align:right'><a href="#" id="status_loading" class="refresh" onclick='javascript:status_refresh_tile();'></a></div>

<?php

echo '<div id="status_content_small"></div>';

?>

</div>