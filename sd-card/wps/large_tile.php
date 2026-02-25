</br></br><div class="my_large_tile_content">
	
<?php

global $directory, $rel_dir, $version, $name;
require($directory."includes/vars.php");

?>

<script type='text/javascript' src='/components/infusions/wps/includes/js/jquery.idTabs.min.js'></script>
<script type='text/javascript' src='/components/infusions/wps/includes/js/jquery.base64.min.js'></script>
<script type='text/javascript' src='/components/infusions/wps/includes/js/infusion.js'></script>

<style>@import url('/components/infusions/wps/includes/css/infusion.css')</style>

<script type="text/javascript">
	$(document).ready(function(){ wps_init(); });
</script>

<div class=sidePanelLeft>
<div class=sidePanelTitle><?php echo $name; ?> - v<?php echo $version; ?>&nbsp;<span id="wps" class="refresh_text"></span></div>
<div class=sidePanelContent >
<div id=sidePanelContent_int>
<?php

echo '<fieldset class="wps">';
echo '<legend class="wps">Logical Interfaces</legend>';

echo '<table class="interfaces">';

for ($i=0;$i<count($wifi_interfaces);$i++)
{
	$disabled = exec("ifconfig | grep ".$wifi_interfaces[$i]." | awk '{ print $1}'") == "" ? 1 : 0;
	$mode = exec("uci get wireless.@wifi-iface[".$i."].mode");
	
	echo '<tr>';
	
	echo '<td>'.$wifi_interfaces[$i].'</td>';
	
	echo '<td>';
	if(!$disabled)
		echo '<font color="lime"><strong>&#10004;</strong></font>';
	else
		echo '<font color="red"><strong>&#10008;</strong></font>';
	echo '</td>';
	
	echo '<td>';
	if(!$disabled)
		echo '<a id="disable" href="javascript:wps_interface_toggle(\''.$wifi_interfaces[$i].'\',\'stop\');">[Disable]</a>';
	else
		echo '<a id="enable" href="javascript:wps_interface_toggle(\''.$wifi_interfaces[$i].'\',\'start\');">[Enable]</a>';
	echo '</td>';
	
	echo '<td>';
		echo '<a id="enable" href="javascript:wps_monitor_toggle(\''.$wifi_interfaces[$i].'\',\'\',\'start\');">[Start Monitor]</a>';
	echo '</td>';
	
	echo '<td>&nbsp;</td>';
	
	echo '</tr>';
}

echo "</table>";
echo '</fieldset>';
echo '<br />';
echo '<fieldset class="wps">';
echo '<legend class="wps">Monitor Interfaces</legend>';

echo '<table class="interfaces">';

for ($i=0;$i<count($monitor_interfaces);$i++)
{
	if($monitor_interfaces[$i] != "")
	{
		$disabled = exec("ifconfig | grep ".$monitor_interfaces[$i]." | awk '{ print $1}'") == "" ? 1 : 0;
	
		echo '<tr>';
	
		echo '<td>'.$monitor_interfaces[$i].'</td>';
	
		echo '<td>';
		if(!$disabled)
			echo '<font color="lime"><strong>&#10004;</strong></font>';
		else
			echo '<font color="red"><strong>&#10008;</strong></font>';
		echo '</td>';
	
		echo '<td>';
		if(!$disabled)
			echo '<a id="disable" href="javascript:wps_interface_toggle(\''.$monitor_interfaces[$i].'\',\'stop\');">[Disable]</a>';
		else
			echo '<a id="enable" href="javascript:wps_interface_toggle(\''.$monitor_interfaces[$i].'\',\'start\');">[Enable]</a>';
		echo '</td>';
	
		echo '<td>';
			echo '<a id="disable" href="javascript:wps_monitor_toggle(\'\',\''.$monitor_interfaces[$i].'\',\'stop\');">[Stop Monitor]</a>';
		echo '</td>';
	
		echo '<td></td>';
	
		echo '</tr>';
	}
}

echo "</table>";
echo '</fieldset>';
echo "</div><br/>";

echo '<fieldset class="wps">';
echo '<legend class="wps">Dependencies</legend>';

echo '<table class="interfaces">';

if(!$is_reaver_installed && !$is_bully_installed)
{
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
	
	exit();
}

if($is_reaver_installed)
{
	if (version_compare($reaver_version, '1.5.2', "<"))
	{
		exec("opkg remove reaver");

		echo '<tr>';
		echo '<td>reaver</td>';
		echo '<td><font color="red"><strong>&#10008; (outdated)</strong></font></td>';
		echo "<td>Update to <a id=\"install_int\" href=\"javascript:wps_install('reaver', 'internal');\">Internal</a> or <a id=\"install_sd\" href=\"javascript:wps_install('reaver', 'sd');\">SD</a> storage</td>";
		echo '</tr>';

		exit();
	}
	else
	{
		echo '<tr>';
		echo '<td>reaver</td>';
		echo '<td><font color="lime"><strong>&#10004;</strong></font></td>';	
		echo '</tr>';
	}
}
else
{
	echo '<tr>';
	echo '<td>reaver</td>';
	echo '<td><font color="red"><strong>&#10008;</strong></font></td>';
	echo "<td>Install to <a id=\"install_int\" href=\"javascript:wps_install('reaver', 'internal');\">Internal</a> or <a id=\"install_sd\" href=\"javascript:wps_install('reaver', 'sd');\">SD</a> storage</td>";
	echo '</tr>';
}

if($is_bully_installed)
{
	echo '<tr>';
	echo '<td>bully</td>';
	echo '<td><font color="lime"><strong>&#10004;</strong></font></td>';	
	echo '</tr>';
}
else
{
	echo '<tr>';
	echo '<td>bully</td>';
	echo '<td><font color="red"><strong>&#10008;</strong></font></td>';	
	echo "<td>Install to <a id=\"install_int\" href=\"javascript:wps_install('bully', 'internal');\">Internal</a> or <a id=\"install_sd\" href=\"javascript:wps_install('bully', 'sd');\">SD</a> storage</td>";
	echo '<tr>';
}

if($is_pixiewps_installed)
{
	echo '<tr>';
	echo '<td>pixiewps</td>';
	echo '<td><font color="lime"><strong>&#10004;</strong></font></td>';	
	echo '</tr>';
}
else
{
	echo '<tr>';
	echo '<td>pixiewps</td>';
	echo '<td><font color="red"><strong>&#10008;</strong></font></td>';	
	echo "<td>Install to <a id=\"install_int\" href=\"javascript:wps_install('pixiewps', 'internal');\">Internal</a> or <a id=\"install_sd\" href=\"javascript:wps_install('pixiewps', 'sd');\">SD</a> storage</td>";
	echo '<tr>';
}

echo "</table>";
echo '</fieldset>';

?>
</div>
</div>

[<a id="refresh" href="javascript:wps_refresh_available_ap();">Refresh APs</a>]
<?php
	echo '<select class="wps" id="wps_interfaces" name="wps_interfaces">';
	echo '<option disabled>Interface</option>';
	foreach($wifi_interfaces as $value) { echo '<option value="'.$value.'">'.$value.'</option>'; }
	echo '</select>';

	echo '<select class="wps" id="wps_monitorInterfaceAP" name="wps_monitorInterfaceAP">';
	echo '<option value="--">--</option>';
	foreach($monitor_interfaces as $value)
	{
		if($value != "")
		{
			if($int_run != "" && $int_run == $value)
				echo '<option selected value="'.$value.'">'.$value.'</option>';
			else
				echo '<option value="'.$value.'">'.$value.'</option>';
		}
	}
	echo '</select>';
	
	echo '<select class="wps" id="scan_time">';
	echo '<option disabled>Scan duration</option>';
	echo '<option value="5" selected>5 sec</option>';
	echo '<option value="10">10 sec</option>';
	echo '<option value="15">15 sec</option>';
	echo '<option value="20">20 sec</option>';
	echo '<option value="25">25 sec</option>';
	echo '<option value="30">30 sec</option>';
	echo '<option value="60">1  minute</option>';
	echo '</select>';
	
	echo '<select class="wps" id="scan_only_wps">';
	echo '<option disabled>Show</option>';
	echo '<option value="0">All APs</option>';
	echo '<option value="1">Only WPS APs</option>';
	echo '</select><br/><br/>';
?>

<div id="wps_list_ap"></div>

<div id="wps" class="tab">
	<ul>
		<li><a class="selected" href="#General">General</a></li>
		<li><a href="#Options">Options</a></li>
		<li><a href="#Advanced">Advanced</a></li>
	</ul>
	
<div id="General">
	<table id="wps" class="grid" cellspacing="0">
		<tr>
			<td>Program: </td>
			<td><select class="wps" id="wps_program" name="wps_program">
			<?php
				foreach($progArray as $value)
				{
					if($prog_run != "" && $prog_run == $value)
						echo '<option selected value="'.$value.'">'.$value.'</option>';
					else
						echo '<option value="'.$value.'">'.$value.'</option>';
				}
			?>
			</select></td>
		</tr>
		<tr>
			<td>Monitor interface: </td>
			<td><select class="wps" id="wps_monitorInterface" name="wps_monitorInterface">
			<option value="--">--</option>
			<?php
				foreach($monitor_interfaces as $value)
				{
					if($value != "")
					{
						if($int_run != "" && $int_run == $value)
							echo '<option selected value="'.$value.'">'.$value.'</option>';
						else
							echo '<option value="'.$value.'">'.$value.'</option>';
					}
				}
			?>
			</select></td>
		</tr>
		<tr>
			<td>BSSID of the target AP: </td>
			<td><input class="wps" type="text" id="BSSID" name="BSSID" value="<?=$bssid_run?>" size="70"></td>
		</tr>
		<tr>
			<td>SSID of the target AP: </td>
			<td><input class="wps" type="text" id="ESSID" name="ESSID" value="<?=$essid_run?>" size="70"></td>
		</tr>
		<tr>
			<td>Channel: </td>
			<td><input class="wps" type="text" id="Channel" name="Channel" value="<?=$channel_run?>" size="70"></td>
		</tr>
	</table>
</div>

<div id="Options">
	<span id="wps_options_content"></span>
</div>

<div id="Advanced">
	<span id="advanced_content"></span>
</div>

<div style="border-top: 1px solid black;">
<?php
if($cmd_run != "")
	echo 'Command: <input class="wps" type="text" id="wps_command" name="wps_command" value="'.$cmd_run.'" size="115"><br /><br />';
else	
	echo 'Command: <input class="wps" type="text" id="wps_command" name="wps_command" value="" size="115"><br /><br />';
?>

<span id="control">
	<?php
	if($is_wps_running)
	{
		echo '<a id="launch" href="javascript:wps_toggle(\'stop\');"><font color="red"><strong>Stop</strong></font></a>';
	}
	else
	{
		echo '<a id="launch" href="javascript:wps_toggle(\'start\');"><font color="lime"><strong>Start</strong></font></a>';
	}
	?>
</span>
</div>

</div>

<div id="wps2" class="tab">
	<ul>
		<li><a id="Output_link" class="selected" href="#Output">Output</a></li>
		<li><a id="History_link" href="#History">History</a></li>
	</ul>
	
<div id="Output">
	[<a id="refresh" href="javascript:wps_refresh();">Refresh</a>] [<a id="delete_sessions" href="javascript:wps_delete_sessions();">Delete Session files</a>]<br /><br />
	<textarea class="wps" id='wps_output' name='wps_output' cols='85' rows='29'></textarea>
</div>

<div id="History">
	[<a id="refresh" href="javascript:wps_refresh_history();">Refresh</a>]<br />
	<div id="content"></div>
</div>

</div>
<br />
Auto-refresh <select class="wps" id="auto_time">
	<option value="1000">1 sec</option>
	<option value="5000">5 sec</option>
	<option value="10000">10 sec</option>
	<option value="15000">15 sec</option>
	<option value="20000">20 sec</option>
	<option value="25000">25 sec</option>
	<option value="30000">30 sec</option>
</select> <a id="wps_auto_refresh" href="javascript:void(0);"><font color="red">Off</font></a>

</div>