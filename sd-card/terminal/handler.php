<?php
@session_start();

// Handle POST for command execution
if (isset($_POST['action']) && $_POST['action'] == 'exec' && isset($_POST['cmd'])) {
    $cmd = $_POST['cmd'];
    $output = shell_exec($cmd . " 2>&1");
    echo $output;
    exit;
}

// Clear terminal
if (isset($_POST['action']) && $_POST['action'] == 'clear') {
    echo "OK";
    exit;
}

// Terminal tile loading
$name = 'Terminal';
$updatable = 'false';
$version = '1.0';

$directory = realpath(dirname(__FILE__)).'/';
$rel_dir = str_replace('/pineapple', '', $directory);

include('/pineapple/includes/api/handler_helper.php');
?>
