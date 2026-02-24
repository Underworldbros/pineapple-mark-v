<script type="text/javascript">
	$(document).ready(function(){ 
		$(function(){
		    $('fieldset .fieldset_content').hide();
		    $('legend').click(function(){
		        $(this).parent().find('.fieldset_content').slideToggle("slow");
				
				if($(this).parent().find('.toggle').text() == "[+]")
					$(this).parent().find('.toggle').text('[_]');
				else
					$(this).parent().find('.toggle').text('[+]');
		    });
		});
	});
	$('#cpu .fieldset_content').slideToggle("slow");
</script>

<?php

require("/pineapple/components/infusions/status/handler.php");
require("/pineapple/components/infusions/status/functions.php");

global $directory;

require($directory."includes/vars.php");

echo '<fieldset id="cpu" class="status">';
echo '<legend class="status">CPU <span style="cursor: pointer;" class="toggle">[_]</span></legend>';

$stat1 = GetCoreInformation(); sleep(1); $stat2 = GetCoreInformation();
$data = GetCpuPercentages($stat1, $stat2);
$cpu_load_ptg = 100 - $data['cpu0']['idle'];
$cpu_load_all = exec("uptime | awk -F 'average:' '{ print $2}'");

echo '<div class="fieldset_content">';

echo '<div class="setting">';
echo '<div class="label">Load Average</div>';
echo '<span id="cpu_load">'.$cpu_load_all.'</span>&nbsp;';
echo '</div>';

echo '</div>';
echo '</fieldset>';

echo '<br />';

echo '<fieldset class="status">';
echo '<legend class="status">Memory <span style="cursor: pointer;" class="toggle">[+]</span></legend>';

$mem_total = exec("free | grep \"Mem:\" | awk '{ print $2 }'");
$mem_used = exec("free | grep \"Mem:\" | awk '{ print $3 }'");
$mem_free = exec("free | grep \"Mem:\" | awk '{ print $4 }'");

$mem_free_ptg = round(($mem_free / $mem_total) * 100);
$mem_used_ptg = 100 - $mem_free_ptg;

echo '<div class="fieldset_content">';

echo '<div class="setting">';
echo '<div class="label">Total Available</div>';
echo '<span id="mem_total">'.kbytes_to_string($mem_total).'</span>&nbsp;';
echo '</div>';

echo '<div class="setting">';
echo '<div class="label">Free</div>';
echo '<span id="mem_free">'.kbytes_to_string($mem_free).'</span>&nbsp;';
echo '</div>';

echo '<div class="setting">';
echo '<div class="label">Used</div>';
echo '<span id="mem_used">'.kbytes_to_string($mem_used).'</span>&nbsp;';
echo '</div>';

echo '</div>';

echo '</fieldset>';

echo '<br />';

echo '<fieldset class="status">';
echo '<legend class="status">Swap <span style="cursor: pointer;" class="toggle">[+]</span></legend>';

$swap_total = exec("free | grep \"Swap:\" | awk '{ print $2 }'");
$swap_used = exec("free | grep \"Swap:\" | awk '{ print $3 }'");
$swap_free = exec("free | grep \"Swap:\" | awk '{ print $4 }'");

if($swap_total != 0) $swap_free_ptg = round(($swap_free / $swap_total) * 100); else $swap_free_ptg = 0;
$swap_used_ptg = 100 - $swap_free_ptg;

echo '<div class="fieldset_content">';

if($swap_total != 0)
{
	echo '<div class="setting">';
	echo '<div class="label">Total Available</div>';
	echo '<span id="mem_total">'.kbytes_to_string($swap_total).'</span>&nbsp;';
	echo '</div>';

	echo '<div class="setting">';
	echo '<div class="label">Free</div>';
	echo '<span id="mem_free">'.kbytes_to_string($swap_free).'</span>&nbsp;';
	echo '</div>';

	echo '<div class="setting">';
	echo '<div class="label">Used</div>';
	echo '<span id="mem_used">'.kbytes_to_string($swap_used).'</span>&nbsp;';
	echo '</div>';
}
else
{
	echo '<div class="setting">';
	echo '<div class="label">Total Available</div>';
	echo '<span id="mem_total"><em>No Swap</em></span>&nbsp;';
	echo '</div>';
}

echo '</div>';

echo '</fieldset>';

echo '<br />';

echo '<fieldset class="status">';

echo '<legend class="status">Storage <span style="cursor: pointer;" class="toggle">[+]</span></legend>';

$df = explode("\n", trim(shell_exec("df | grep -v \"Filesystem\"")));

echo '<div class="fieldset_content">';

for($i=0;$i<count($df);$i++)
{
	$df_name = exec("df | grep -v \"Filesystem\" | grep \"".$df[$i]."\" | awk '{ print $1}'");
	$df_mount = exec("df | grep -v \"Filesystem\" | grep \"".$df[$i]."\" | awk '{ print $6}'");
	$df_total = exec("df | grep -v \"Filesystem\" | grep \"".$df[$i]."\" | awk '{ print $2}'");
	$df_used = exec("df | grep -v \"Filesystem\" | grep \"".$df[$i]."\" | awk '{ print $3}'");
	$df_used_ptg = exec("df | grep -v \"Filesystem\" | grep \"".$df[$i]."\" | awk '{ print $5}'");
	
	echo '<div class="setting">';
	echo '<div class="label">['.$df_mount.']</div>';
	echo '<span id="df_used">'.kbytes_to_string($df_used).'/'.kbytes_to_string($df_total).'</span>&nbsp;';
	echo '</div>';
}

echo '</div>';

echo '</fieldset>';

?>
