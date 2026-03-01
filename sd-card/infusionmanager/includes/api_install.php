<?php
// Standalone API - no auth required
header('Content-Type: text/plain');

$sd = '/sd/';
$link = '/pineapple/components/infusions/';

if (isset($_GET['install'])) {
    $n = basename($_GET['install']);
    
    foreach (scandir($sd) as $f) {
        if (preg_match('/^'.$n.'-[\d\.]+\.tar\.gz$/', $f)) {
            exec("cd ".escapeshellarg($sd)." && tar -xzf ".escapeshellarg($sd.$f));
            if (is_dir($sd.$n) && !is_link($link.$n)) {
                symlink($sd.$n, $link.$n);
            }
            echo "INSTALLED: $n";
            exit;
        }
    }
    echo "NOTFOUND: $n";
    exit;
}

if (isset($_GET['uninstall'])) {
    $n = basename($_GET['uninstall']);
    if (is_link($link.$n)) unlink($link.$n);
    if (is_dir($sd.$n)) exec("rm -rf ".escapeshellarg($sd.$n));
    echo "UNINSTALLED: $n";
    exit;
}

echo "USE: ?install=name or ?uninstall=name";
