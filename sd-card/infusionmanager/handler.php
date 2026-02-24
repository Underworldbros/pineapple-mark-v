<?php

// Handle POST action for install - schedule via at job (like Pineapple Bar)
if (isset($_POST['action']) && $_POST['action'] == 'install' && isset($_POST['name'])) {
    $name = basename($_POST['name']);
    
    $sd = '/sd/';
    $installer = dirname(__FILE__) . '/files/installer';
    
    if (is_file($installer)) {
        $tarball = null;
        foreach (scandir($sd) as $f) {
            if (preg_match('/^'.preg_quote($name, '/').'-[\d\.]+\.tar\.gz$/', $f)) {
                $tarball = $f;
                break;
            }
        }
        
        if ($tarball) {
            $cmd = "bash " . escapeshellarg($installer) . " " . escapeshellarg($name);
            exec("echo '" . $cmd . "' | at now");
            echo "SCHEDULED";
            exit;
        }
    }
    
    echo "ERROR";
    exit;
}

// Handle POST action for uninstall - schedule via at job
if (isset($_POST['action']) && $_POST['action'] == 'uninstall' && isset($_POST['name'])) {
    $name = basename($_POST['name']);
    
    $uninstaller = dirname(__FILE__) . '/files/uninstaller';
    
    if (is_file($uninstaller)) {
        $cmd = "bash " . escapeshellarg($uninstaller) . " " . escapeshellarg($name);
        exec("echo '" . $cmd . "' | at now");
        echo "UNSCHEDULED";
        exit;
    }
    
    echo "ERROR";
    exit;
}

// Normal tile loading - minimal includes
$name = 'Infusion Manager';  
$updatable = 'false';  
$version = '1.0';

$directory = realpath(dirname(__FILE__)).'/';
$rel_dir = str_replace('/pineapple', '', $directory);

include('/pineapple/includes/api/handler_helper.php');

?>
