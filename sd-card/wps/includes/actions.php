<?php

require("/pineapple/components/infusions/wps/handler.php");

global $directory;

require($directory."includes/vars.php");

if (isset($_POST['interface']) && isset($_POST['action']) && isset($_POST['int']))
{
	if ($_POST['action'] == 'start') 
		exec("ifconfig ".$_POST['int']." up &");
	else
		exec("ifconfig ".$_POST['int']." down &");
}

if (isset($_POST['monitor']) && isset($_POST['action']) && isset($_POST['int']) && isset($_POST['mon']))
{
	if ($_POST['action'] == 'start') 
		exec("airmon-ng start ".$_POST['int']." &");
	else
		exec("airmon-ng stop ".$_POST['mon']." &");
}

if (isset($_GET['launch']))
{
	if (isset($_GET['cmd']))
	{
		if (isset($_GET['prog']))
		{
			$time = time(); $cmd = stripslashes(base64_decode($_GET['cmd']));

			$full_cmd = $cmd." -o ".$directory."includes/log/log_".$time.".log &";
		
			$filename = $directory."includes/wps.sh";
		
			$newdata = "#!/bin/sh\n".$full_cmd;
			$newdata = ereg_replace(13,  "", $newdata);
			$fw = fopen($filename, 'w+');
			$fb = fwrite($fw,stripslashes($newdata));
			fclose($fw);
		
			if (isset($_GET['int'])) $new_int_run = $_GET['int'];
			if (isset($_GET['bssid'])) $new_bssid_run = $_GET['bssid'];
			if (isset($_GET['essid'])) $new_essid_run = $_GET['essid'];
			if (isset($_GET['channel'])) $new_channel_run = $_GET['channel'];
			if (isset($_GET['prog'])) $new_prog_run = $_GET['prog'];
			$new_cmd_run = $cmd;
		
			$filename = $directory."includes/infusion.run";

			$newdata = "prog=\"".$new_prog_run."\"\n"."int=\"".$new_int_run."\"\n"."cmd=\"".base64_encode($new_cmd_run)."\"\n"."channel=\"".$new_channel_run."\"\n"."bssid=\"".$new_bssid_run."\"\n"."essid=\"".$new_essid_run."\"";
			$newdata = ereg_replace(13,  "", $newdata);
			$fw = fopen($filename, 'w');
			$fb = fwrite($fw,stripslashes($newdata));
			fclose($fw);
			
			shell_exec("chmod +x ".$directory."includes/wps.sh &");
			exec("echo ".$directory."includes/wps.sh | at now");
		}
	}
}

if (isset($_GET['cancel']))
{
	exec("echo -e \"prog=\nint=\ncmd=\nbssid=\nessid=\nchannel=\" > ".$directory."includes/infusion.run");
	exec("killall -9 reaver &");
	exec("killall -9 bully &");
}

if (isset($_GET['load']))
{
	if (isset($_GET['file']))
	{
		$log_date = gmdate("F d Y H:i:s", filemtime($directory."includes/log/".$_GET['file']));
		echo "<strong>wps log ".$_GET['file']." [".$log_date."]</strong><br/><br/>";
		
		echo '<textarea class="wps" cols="85" rows="29">';
		echo htmlentities(file_get_contents($directory."includes/log/".$_GET['file']), ENT_QUOTES|ENT_SUBSTITUTE);
		echo '</textarea>';
	}
}

if (isset($_GET['download']))
{
	if (isset($_GET['file']))
	{
		$file = $directory."includes/log/".basename($_GET['file']);
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.basename($file).'"'); 
		header('Content-Length: ' . filesize($file));
		readfile($file);
	}
}

if (isset($_GET['delete']))
{
	if (isset($_GET['file']))
	{
		exec("rm -rf ".$directory."includes/log/".$_GET['file']."*");
	}
}

if (isset($_GET['delete_sessions']))
{
	exec("rm -rf /root/.bully/*.run");
	
	echo '<font color="lime"><strong>done</strong></font>';
}

if (isset($_GET['install']))
{
	if (isset($_GET['where']))
	{
		$where = $_GET['where'];
		
		if (isset($_GET['what']))
		{
			$what = $_GET['what'];
	
			switch($where)
			{
				case 'sd': 
					exec("opkg update && opkg install ".$what." --dest sd");
					if($what == "reaver")
						exec("mkdir /etc/reaver && ln -s /sd/etc/reaver/reaver.db /etc/reaver/ && ln -s /sd/usr/bin/reaver /usr/bin/ && ln -s /sd/usr/bin/wash /usr/bin/");
				break;

				case 'internal': 
					exec("opkg update && opkg install ".$what.""); 
				break;
			}
		}
	}
}

?>
