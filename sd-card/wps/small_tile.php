<?php

global $directory, $rel_dir, $version, $name;
require($directory."includes/vars.php");

?>

<script type='text/javascript' src='/components/infusions/wps/includes/js/jquery.base64.min.js'></script>
<script type='text/javascript' src='/components/infusions/wps/includes/js/infusion.js'></script>

<style>@import url('/components/infusions/wps/includes/css/infusion.css')</style>

<script type="text/javascript">
	$(document).ready(function(){ wps_init_small(); });
</script>

<div style='text-align:right'><a href="#" id="wps_loading" class="refresh" onclick='javascript:wps_refresh_tile();'></a></div>

<?php

if(!$is_reaver_installed && !$is_bully_installed && !$is_pixiewps_installed)
{
	echo '<fieldset class="wps">';
	echo '<legend class="wps">Dependencies</legend>';

	echo '<table class="interfaces">';
	
	echo '<tr>';
	echo '<td>reaver</td>';
	echo '<td><font color="red"><strong>&#10008;</strong></font></td>';
	echo "<td>Install to <a id=\"install_int\" href=\"javascript:wps_install('reaver', 'internal');\">Internal</a> or <a id=\"install_sd\" href=\"javascript:wps_install('reaver', 'sd');\">SD</a> storage</td>";
	echo '</tr>';
	
	echo '<tr>';
	echo '<td>bully</td>';
	echo '<td><font color="red"><strong>&#10008;</strong></font></td>';
	echo "<td>Install to <a id=\"install_int\" href=\"javascript:wps_install('bully', 'internal');\">Internal</a> or <a id=\"install_sd\" href=\"javascript:wps_install('bully', 'sd');\">SD</a> storage</td>";
	echo '</tr>';
	
	echo '<tr>';
	echo '<td>pixiewps</td>';
	echo '<td><font color="red"><strong>&#10008;</strong></font></td>';
	echo "<td>Install to <a id=\"install_int\" href=\"javascript:wps_install('pixiewps', 'internal');\">Internal</a> or <a id=\"install_sd\" href=\"javascript:wps_install('pixiewps', 'sd');\">SD</a> storage</td>";
	echo '</tr>';

	echo "</table>";
	echo '</fieldset>';
	
	exit();
}

echo '<input class="wps" type="text" id="BSSID_small" name="BSSID_small" placeholder="BSSID" value="'.$bssid_run.'" size="15">&nbsp;';
echo '<input class="wps" type="text" id="ESSID_small" name="ESSID_small" placeholder="SSID" value="'.$essid_run.'" size="15">&nbsp;';
echo '<input class="wps" type="text" id="Channel_small" name="Channel_small" placeholder="Channel" value="'.$channel_run.'" size="5">&nbsp;';

echo '<select class="wps" id="wps_monitorInterface_small" name="wps_monitorInterface_small"><option>--</option>';
foreach($monitor_interfaces as $value)
{
	if($int_run != "" && $int_run == $value)
		echo '<option selected value="'.$value.'">'.$value.'</option>';
	else
		echo '<option value="'.$value.'">'.$value.'</option>';
}
echo '</select>&nbsp;';

echo '<select class="wps" id="wps_program_small" name="wps_program_small">';
foreach($progArray as $value)
{
	if($prog_run != "" && $prog_run == $value)
		echo '<option selected value="'.$value.'">'.$value.'</option>';
	else
		echo '<option value="'.$value.'">'.$value.'</option>';
}
echo '</select>&nbsp;';

echo '<span id="control_small">';
if($is_wps_running)
{
	echo '<a id="launch_small" href="javascript:wps_toggle_small(\'stop\');"><font color="red"><strong>Stop</strong></font></a>';
}
else
{
	echo '<a id="launch_small" href="javascript:wps_toggle_small(\'start\');"><font color="lime"><strong>Start</strong></font></a>';
}
echo '</span>&nbsp;';

if($cmd_run != "")
	echo '<input class="wps" type="text" id="wps_command_small" name="wps_command_small" value="'.$cmd_run.'" size="70"><br /><br />';
else
	echo '<input class="wps" type="text" id="wps_command_small" name="wps_command_small" value="" size="70"><br /><br />';

echo "<textarea readonly class='wps' id='wps_output_small' name='wps_output_small'></textarea>";

?>