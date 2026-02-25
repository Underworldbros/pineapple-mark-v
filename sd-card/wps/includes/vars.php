<?php

putenv('LD_LIBRARY_PATH='.getenv('LD_LIBRARY_PATH').':/sd/lib:/sd/usr/lib');   
putenv('PATH='.getenv('PATH').':/sd/usr/bin:/sd/usr/sbin');

global $directory, $rel_dir;

$is_bully_installed = exec("which bully") != "" ? 1 : 0;
$is_reaver_installed = file_exists("/sd/usr/sbin/reaver") ? 1 : 0; $reaver_version = file_exists("/sd/reaver/VERSION") ? trim(file_get_contents("/sd/reaver/VERSION")) : "unknown";
$is_pixiewps_installed = exec("which pixiewps") != "" ? 1 : 0;

$is_wps_running = exec("ps auxww | grep 'bully\|reaver' | grep -v -e grep | grep -v -e php") != "" ? 1 : 0;

$wifi_interfaces = array_reverse(explode("\n", trim(shell_exec("iwconfig 2> /dev/null | grep \"wlan*\" | grep -v \"mon*\" | awk '{print $1}'"))));
$monitor_interfaces = array_reverse(explode("\n", trim(shell_exec("iwconfig 2> /dev/null | grep \"mon*\" | awk '{print $1}'"))));

$progArray = array();
if($is_reaver_installed) array_push($progArray, "reaver");
if($is_bully_installed) array_push($progArray, "bully");

$wps_run = parse_ini_file($directory."includes/infusion.run");
$prog_run = $wps_run['prog'];
$int_run = $wps_run['int'];
$bssid_run = $wps_run['bssid'];
$essid_run = $wps_run['essid'];
$channel_run = $wps_run['channel'];
$cmd_run = base64_decode($wps_run['cmd']);

$bully_advanced_options_with_inputs = array(
				"Starting pin index (7 or 8 digits) [Auto]" => array("-i","7"),  
				"Seconds to wait if the AP locks WPS [43]" => array("-l","43"),
				"Starting pin number (7 or 8 digits) [Auto]" => array("-p","12345"),  
				"Verbosity level 1-3, 1 is quietest [3]" => array("-v","3"),
				"Resend packets N times when not acked [2]" => array("-r","2"),
				"Delay M seconds every Nth nack at M5 [0,1]" => array("-1","0,1"),
				"Delay M seconds every Nth nack at M7 [5,1]" => array("-2","5,1")
				 );	

$bully_advanced_options = array(
				"Bruteforce the WPS pin checksum digit" => "-B", 
				"Force continue in spite of warnings" => "-F", 
 				"Sequential pins (do not randomize)" => "-S", 
 				"Test mode (do not inject any packets)" => "-T",
				"Disable ACK check for sent packets" => "-A",
				"Skip CRC/FCS validation (performance)" => "-C",
				"Detect WPS lockouts unreported by AP" => "-D",
				"EAP Failure terminate every exchange" => "-E",
				"Ignore WPS locks reported by the AP" => "-L",
				"M5/M7 timeouts treated as WSC_NACK's" => "-M",
				"Packets don't contain the FCS field" => "-N",
				"Use probe request for nonbeaconing AP" => "-P",
				"Assume radiotap headers are present" => "-R",
				"Masquerade as a Windows 7 registrar" => "-W",
				"Suppress packet throttling algorithm" => "-Z"
 				 );
				 
$reaver_advanced_options_with_inputs = array(
			"Set the delay between pin attempts [1]" => array("-d","1"),  
			"Set the time to wait if the AP locks WPS pin attempts [60]" => array("-l","60"),
			"Quit after num pin attempts" => array("-g","100"),  
			"Set the time to sleep after 10 unexpected failures [0]" => array("-x","0"), 
			"Set the receive timeout period [5]" => array("-t","5"),
			"Set the M5/M7 timeout period [0.20]" => array("-T","0.20"),
			"Sleep for y seconds every x pin attempts" => array("-r","1:10"),
			"Default Pin Generator by devttys0 team [1] Belkin [2] D-Link" => array("-W","1")
			 );

$reaver_advanced_options = array(
			"Auto detect the best advanced options for the target AP" => "-a", 
			"Do not associate with the AP (association must be done by another application)" => "-A", 
			"Do not send NACK messages when out of order packets are received" => "-N", 
			"Use small DH keys to improve crack speed" => "-S", 
			"Ignore locked state reported by the target AP" => "-L", 
			"Terminate each WPS session with an EAP FAIL packet" => "-E", 
			"Target AP always sends a NACK [Auto]" => "-n", 
			"Mimic a Windows 7 registrar [False]" => "-w",
			"Set exhaustive mode from the beginning of the session [False]" => "-X",
			"Set initial array index for the first half of the pin [False]" => "-1",
			"Set initial array index for the second half of the pin [False]" => "-2",
			"Set into PixieLoop mode (doesn't send M4, and loops through to M3) [False]" => "-P",
			"Enables logging of sequence completed PixieHashes" => "-H",
			"Do NOT run reaver to auto retrieve WPA password if pixiewps attack is successful" => "-Z",
			"[1] Run pixiewps with PKE, PKR, E-Hash1, E-Hash2, E-Nonce and Authkey (Ralink, Broadcom & Realtek)" => "-K 1",
			"Display non-critical warnings" => "-vv",
			"Display PixieHashes / enable pixiedust modes" => "-vvv",
			"Only display critical messages" => "-q"
			 );
?>
