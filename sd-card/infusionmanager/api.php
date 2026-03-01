<?php

$sd_directory = '/sd/';
$infusions_link = '/pineapple/components/infusions/';

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

$action = isset($_GET['action']) ? $_GET['action'] : (isset($argv[1]) ? $argv[1] : '');
$name = isset($_GET['name']) ? $_GET['name'] : (isset($argv[2]) ? $argv[2] : '');

if ($action == 'install' && !empty($name)) {
    header('Content-Type: application/json');
    
    $tarball = findTarball($name);
    if (!$tarball) {
        echo json_encode(array('success' => false, 'message' => 'Tarball not found'));
        exit;
    }
    
    $extract_dir = $sd_directory . $name;
    if (is_dir($extract_dir)) {
        echo json_encode(array('success' => false, 'message' => 'Already installed'));
        exit;
    }
    
    exec("cd " . escapeshellarg($sd_directory) . " && tar -xzf " . escapeshellarg($tarball));
    
    if (is_dir($extract_dir)) {
        $link = $infusions_link . $name;
        if (!is_link($link)) {
            symlink($extract_dir, $link);
        }
        echo json_encode(array('success' => true, 'message' => 'Installed ' . $name));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Installation failed'));
    }
    exit;
}

if ($action == 'uninstall' && !empty($name)) {
    header('Content-Type: application/json');
    
    $link = $infusions_link . $name;
    if (is_link($link)) {
        unlink($link);
    }
    
    $dir = $sd_directory . $name;
    if (is_dir($dir)) {
        exec("rm -rf " . escapeshellarg($dir));
        echo json_encode(array('success' => true, 'message' => 'Uninstalled ' . $name));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Directory not found'));
    }
    exit;
}

echo json_encode(array('success' => false, 'message' => 'Invalid action'));
