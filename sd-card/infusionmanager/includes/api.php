<?php
// Simple API that bypasses CSRF for install/uninstall

$sd = '/sd/';
$link = '/pineapple/components/infusions/';

if (isset($_GET['do']) && isset($_GET['name'])) {
    $name = basename($_GET['name']);
    
    if ($_GET['do'] == 'install') {
        foreach (scandir($sd) as $f) {
            if (preg_match('/^'.$name.'-[\d\.]+\.tar\.gz$/', $f)) {
                exec("cd ".escapeshellarg($sd)." && tar -xzf ".escapeshellarg($sd.$f));
                if (is_dir($sd.$name) && !is_link($link.$name)) {
                    @symlink($sd.$name, $link.$name);
                }
                break;
            }
        }
    }
    
    if ($_GET['do'] == 'uninstall') {
        if (is_link($link.$name)) @unlink($link.$name);
        if (is_dir($sd.$name)) exec("rm -rf ".escapeshellarg($sd.$name));
    }
    
    header('Location: /');
    exit;
}

header('Location: /');
