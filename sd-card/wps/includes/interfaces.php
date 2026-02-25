<?php

require("/pineapple/components/infusions/wps/handler.php");

global $directory;

require($directory."includes/vars.php");
require($directory."includes/iwlist_parser.php");

if(isset($_GET['monitor']))
{
	echo '<option value="--">--</option>';
	foreach($monitor_interfaces as $value) 
	{ 
		if($value != "")
		{
			echo '<option value="'.$value.'">'.$value.'</option>';
		}
	}
}

if(isset($_GET['interface']))
{
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
}

if(isset($_GET['available_ap']))
{
	if (isset($_GET['int'])) $interface = $_GET['int'];
	if (isset($_GET['mon'])) $monitor = $_GET['mon'];
	if (isset($_GET['scan_time'])) $scan_time = $_GET['scan_time'];
	if (isset($_GET['scan_only_wps'])) $scan_only_wps = $_GET['scan_only_wps'];
		
	// Start airodump
	if($monitor != "" && $monitor != "--")
	{
		shell_exec("rm -rf /tmp/wps-* && airodump-ng -a --output-format cap -w /tmp/wps ".$monitor." &> /dev/null &");
		
		if($scan_time != "" && $scan_time != "null")
			sleep($scan_time);
	}
	
	// List APs
	$iwlistparse = new iwlist_parser();
	$p = $iwlistparse->parseScanDev($interface);

	if(!empty($p))
	{
		echo '<em>Click on row to select AP</em><br/><br/>';
		echo '<table id="wps-survey-grid" class="grid" cellspacing="0">';
		echo '<tr class="header">';
		echo '<td>SSID</td>';
		echo '<td>BSSID</td>';
		echo '<td>Signal level</td>';
		echo '<td colspan="2">Quality level</td>';
		echo '<td>Ch</td>';
		echo '<td>Encryption</td>';
		echo '<td>Cipher</td>';
		echo '<td>Auth</td>';
		echo '<td>WPS</td>';
		echo '</tr>';
	}
	else
	{
		echo "<em>No data...</em>";
	}

	for($i=1;$i<=count($p[$interface]);$i++)
	{
		$wps_enabled = trim(shell_exec("wash -f /tmp/wps-01.cap -o /tmp/wps-01.wash &> /dev/null && cat /tmp/wps-01.wash | tail -n +3 | grep ".$p[$interface][$i]["Address"]." | awk '{ print $5; }'"));
		if($wps_enabled == "No" || $wps_enabled == "Yes") $wps_enabled = 1; else $wps_enabled = 0;
			
		if(($scan_only_wps && $wps_enabled) || !$scan_only_wps)
		{
			$quality = $p[$interface][$i]["Quality"];
	
			if($quality <= 25) $graph = "red";
			else if($quality <= 50) $graph = "yellow";
			else if($quality <= 100) $graph = "green";
	
			echo '<tr class="odd" name="'.$p[$interface][$i]["ESSID"].'|'.$p[$interface][$i]["Address"].'|'.$p[$interface][$i]["Channel"].'">';
	
			echo '<td>'.$p[$interface][$i]["ESSID"].'</td>';
	
			$MAC_address = explode(":", $p[$interface][$i]["Address"]);
			echo '<td>'.$p[$interface][$i]["Address"].'</td>';
			echo '<td>'.$p[$interface][$i]["Signal level"].'</td>';
			echo "<td>".$quality."%</td>";
			echo "<td width='150'>";
			echo '<div class="graph-border">';
			echo '<div class="graph-bar" style="width: '.$quality.'%; background: '.$graph.';"></div>';
			echo '</div>';
			echo "</td>";
			echo '<td>'.$p[$interface][$i]["Channel"].'</td>';
		
			if($p[$interface][$i]["Encryption key"] == "on")
			{
				$WPA = strstr($p[$interface][$i]["IE"], "WPA Version 1");
				$WPA2 = strstr($p[$interface][$i]["IE"], "802.11i/WPA2 Version 1");
		
				$auth_type = str_replace("\n"," ",$p[$interface][$i]["Authentication Suites (1)"]);
				$auth_type = implode(' ',array_unique(explode(' ', $auth_type)));
		
				$cipher = $p[$interface][$i]["Pairwise Ciphers (2)"] ? $p[$interface][$i]["Pairwise Ciphers (2)"] : $p[$interface][$i]["Pairwise Ciphers (1)"];
				$cipher = str_replace("\n"," ",$cipher);
				$cipher = implode(',',array_unique(explode(' ', $cipher)));
	
				if($WPA2 != "" && $WPA != "")
					echo '<td>WPA,WPA2</td>';
				else if($WPA2 != "")
					echo '<td>WPA2</td>';
				else if($WPA != "")
					echo '<td>WPA</td>';
				else
					echo '<td>WEP</td>';
			
				echo '<td>'.$cipher.'</td>';
				echo '<td>'.$auth_type.'</td>';
			}
			else
			{
				echo '<td>None</td>';
				echo '<td>&nbsp;</td>';
				echo '<td>&nbsp;</td>';
			}

			if($wps_enabled) echo '<td>WPS</td>'; else echo '<td>-</td>';
		
			echo '</tr>';
		}
	}
	
	shell_exec("killall -9 airodump-ng");
	shell_exec("rm -rf /tmp/wps-*");
	
}

?>