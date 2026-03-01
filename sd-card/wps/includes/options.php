<?php

require("/pineapple/components/infusions/wps/handler.php");
require("/pineapple/components/infusions/wps/functions.php");

global $directory;

require($directory."includes/vars.php");

if (isset($_GET['options_bully']))
{
	echo '<table id="wps" class="grid" cellspacing="0">';
	echo "<tr><td><strong>Bully</strong></td></tr>";
	echo '<tr>';
	echo '<td colspan="2">';
	foreach($bully_advanced_options_with_inputs as $key => $value)
	{
		echo '<input class="wps" type="checkbox" id="'.$value[0].'" name="'.$key.'" value="'.$value[0].'" />&nbsp;<input class="wps" type="text" id="'.$value[0].'_input" name="'.$value[0].'_input" value="'.$value[1].'" size="4">&nbsp;'.$key."<br />";
	}
	echo '</td>';
	echo '</tr>';
	echo '</table>';
}

if (isset($_GET['advanced_bully']))
{
	echo '<table id="wps" class="grid" cellspacing="0">';
	echo "<tr><td><strong>Bully</strong></td></tr>";
	echo '<tr>';
	echo '<td colspan="2">';
	foreach($bully_advanced_options as $key => $value)
	{
		echo '<input class="wps" type="checkbox" id="'.$value.'" name="'.$key.'" value="'.$value.'" />&nbsp;'.$key."<br />";
	}
	echo '</td>';
	echo '</tr>';
	echo '</table>';
}

if (isset($_GET['options_reaver']))
{
	echo '<table id="wps" class="grid" cellspacing="0">';
	echo "<tr><td><strong>Reaver</strong></td></tr>";
	echo '<tr>';
	echo '<td colspan="2">';
	foreach($reaver_advanced_options_with_inputs as $key => $value)
	{
		echo '<input class="wps" type="checkbox" id="'.$value[0].'" name="'.$key.'" value="'.$value[0].'" />&nbsp;<input class="wps" type="text" id="'.$value[0].'_input" name="'.$value[0].'_input" value="'.$value[1].'" size="4">&nbsp;'.$key."<br />";
	}
	echo '</td>';
	echo '</tr>';
	echo '</table>';
}

if (isset($_GET['advanced_reaver']))
{
	echo '<table id="wps" class="grid" cellspacing="0">';
	echo "<tr><td><strong>Reaver</strong></td></tr>";
	echo '<tr>';
	echo '<td colspan="2">';
	foreach($reaver_advanced_options as $key => $value)
	{
		echo '<input class="wps" type="checkbox" id="'.$value.'" name="'.$key.'" value="'.$value.'" />&nbsp;'.$key."<br />";
	}
	echo '</td>';
	echo '</tr>';
	echo '</table>';
}

?>