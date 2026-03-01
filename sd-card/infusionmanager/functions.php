<?php

$sd_directory = '/sd/';
$infusions_link = '/pineapple/components/infusions/';

$categories = array(
    'Recon' => array('nmap', 'sitesurvey', 'wps', 'nbtscan', 'arping', 'connectedclients', 'monitor'),
    'Attack' => array('deauth', 'ettercap', 'sslstrip', 'sslsplit', 'strip-n-inject', 'dnsspoof', 'ardronepwn', 'crafty', 'occupineapple'),
    'Logging' => array('tcpdump', 'urlsnarf', 'logcheck', 'pineapplestats', 'trapcookies', 'p0f', 'randomroll'),
    'Portal' => array('evilportal', 'portalauth'),
    'Utilities' => array('status', 'wifimanager', 'notify', 'dnschanger', 'dipstatus', 'connect'),
    'System' => array('phials', 'opkgmanager', 'blackout', 'bobthebuilder', 'get', 'base64encdec', 'datalocker', 'delorean', 'meterpreter', 'torgateway', 'rtlradiostreamer', 'adsbtracker')
);

function getInstalledInfusions() {
    $installed = array();
    $infusions_link = '/pineapple/components/infusions/';
    
    if (is_dir($infusions_link)) {
        $items = scandir($infusions_link);
        foreach ($items as $item) {
            if ($item != '.' && $item != '..' && is_link($infusions_link . $item)) {
                $target = readlink($infusions_link . $item);
                if (strpos($target, '/sd/') === 0) {
                    $installed[] = $item;
                }
            }
        }
    }
    return $installed;
}

function getAvailableInfusions() {
    $available = array();
    $sd_directory = '/sd/';
    
    if (is_dir($sd_directory)) {
        $items = scandir($sd_directory);
        foreach ($items as $item) {
            if (preg_match('/^(.+)-[\d.]+\.tar\.gz$/', $item, $matches)) {
                $name = $matches[1];
                if (!in_array($name, array('infusionmanager'))) {
                    $available[] = $name;
                }
            }
        }
    }
    return $available;
}

function installInfusion($name) {
    $sd_directory = '/sd/';
    $infusions_link = '/pineapple/components/infusions/';
    
    $tarball = findTarball($name);
    if (!$tarball) {
        return array('success' => false, 'message' => 'Tarball not found');
    }
    
    $extract_dir = $sd_directory . $name;
    if (is_dir($extract_dir)) {
        return array('success' => false, 'message' => 'Already installed');
    }
    
    exec("cd " . escapeshellarg($sd_directory) . " && tar -xzf " . escapeshellarg($tarball));
    
    if (is_dir($extract_dir)) {
        $link = $infusions_link . $name;
        if (!is_link($link)) {
            symlink($extract_dir, $link);
        }
        return array('success' => true, 'message' => 'Installed successfully');
    }
    
    return array('success' => false, 'message' => 'Installation failed');
}

function uninstallInfusion($name) {
    $sd_directory = '/sd/';
    $infusions_link = '/pineapple/components/infusions/';
    
    $link = $infusions_link . $name;
    if (is_link($link)) {
        unlink($link);
    }
    
    $dir = $sd_directory . $name;
    if (is_dir($dir)) {
        exec("rm -rf " . escapeshellarg($dir));
        return array('success' => true, 'message' => 'Uninstalled successfully');
    }
    
    return array('success' => false, 'message' => 'Directory not found');
}

function findTarball($name) {
    $sd_directory = '/sd/';
    
    if (is_dir($sd_directory)) {
        $items = scandir($sd_directory);
        foreach ($items as $item) {
            if (preg_match('/^' . preg_quote($name, '/') . '-[\d.]+\.tar\.gz$/', $item)) {
                return $sd_directory . $item;
            }
        }
    }
    return null;
}

function getInfusionStatus($name) {
    $infusions_link = '/pineapple/components/infusions/';
    $link = $infusions_link . $name;
    return is_link($link) ? 'enabled' : 'disabled';
}
