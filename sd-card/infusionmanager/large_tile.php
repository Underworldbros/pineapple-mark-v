<?php
$infusions_link = '/pineapple/components/infusions/';
$installed = array();
$has_tile = array();
$has_bin = array();

# Check symlinks (tiles)
if (is_dir($infusions_link)) {
    $items = scandir($infusions_link);
    foreach ($items as $item) {
        if ($item != '.' && $item != '..' && is_link($infusions_link . $item)) {
            $target = readlink($infusions_link . $item);
            if (strpos($target, '/sd/') === 0) {
                $installed[] = $item;
                $has_tile[] = $item;
            }
        }
    }
}

# Binary locations to check
$bin_paths = array('/sd/usr/sbin/', '/sd/usr/bin/', '/usr/sbin/', '/usr/bin/');

# Tools that can have binaries (not all tools have separate binary packages)
$bin_tools = array(
    'nmap' => 'nmap',
    'nbtscan' => 'nbtscan', 
    'p0f' => 'p0f',
    'reaver' => 'reaver',
    'bully' => 'bully',
    'pixiewps' => 'pixiewps',
    'mdk3' => 'mdk3',
    'aireplay' => 'aireplay'
);

foreach ($bin_tools as $tool => $bin) {
    foreach ($bin_paths as $path) {
        if (file_exists($path . $bin)) {
            if (!in_array($tool, $installed)) {
                $installed[] = $tool;
            }
            $has_bin[] = $tool;
            break;
        }
    }
}

function hasTile($name, &$has_tile) {
    return in_array($name, $has_tile);
}

function hasBin($name, &$has_bin) {
    return in_array($name, $has_bin);
}

function getStatus($name, &$installed) {
    return in_array($name, $installed) ? 'installed' : 'notinstalled';
}
?>
<div id="infusionmanager_content" style="padding:10px;">
<h2>Infusion Manager</h2>
<p>Install tiles (GUI) and/or binaries (CLI). <button onclick="location.reload()" style="cursor:pointer;background:#444;color:#fff;border:1px solid #666;padding:2px 8px;">Refresh</button></p>

<style>
.infusion-box { border:1px solid #444;padding:10px;min-width:280px;background:#222;border-radius:5px;margin:5px; }
.infusion-title { font-weight:bold;color:#4a9;margin-bottom:8px;font-size:14px; }
.dep-title { font-weight:bold;color:#f90;margin-bottom:8px;font-size:14px; }
.row { display:flex;align-items:center;margin:4px 0;font-size:12px; }
.name { width:100px;font-weight:bold; }
.col-tile { width:65px;text-align:center; }
.col-bin { width:65px;text-align:center; }
.badge-dep { background:#f90;color:#000;font-size:9px;padding:1px 4px;border-radius:3px;margin-left:4px; }
.status-installed { color: #4f4 !important; }
.status-notinstalled { color: #666 !important; }
.btn-install { background:#333 !important;color:#fff !important;cursor:pointer;border:1px solid #555;padding:2px 6px;font-size:10px; }
.btn-installed { background:#282 !important;color:#4f4 !important;cursor:default;border:1px solid #333;padding:2px 6px;font-size:10px; }
.btn-uninstall { background:#a33 !important;color:#fff !important;cursor:pointer;border:1px solid #722;padding:2px 6px;font-size:10px; }
.btn-disabled { background:#222 !important;color:#444 !important;cursor:not-allowed;border:1px solid #333;padding:2px 6px;font-size:10px; }
</style>

<div style="display:flex;flex-wrap:wrap;gap:10px;">

<div class="infusion-box">
<div class="infusion-title">Recon</div>
<div class="row">
    <div class="name">Tool</div>
    <div class="col-tile">Tile</div>
    <div class="col-bin">Bin</div>
</div>
<?php
function hasAnyBin($bins, &$has_bin) {
    if (!$bins) return false;
    $arr = explode(',', $bins);
    foreach ($arr as $b) {
        if (in_array($b, $has_bin)) return true;
    }
    return false;
}

$recon = array(
    array('sitesurvey', '', 'WiFi survey'),
    array('arping', '', 'ARP ping'),
    array('connectedclients', '', 'Client list'),
    array('monitor', '', 'Monitor mode'),
    array('nmap', 'nmap', 'Network scanner'),
    array('nbtscan', 'nbtscan', 'NetBIOS scanner', true),
    array('wps', 'reaver,bully,pixiewps', 'WPS attack', true)
);
foreach ($recon as $item) {
    $name = $item[0];
    $bins = $item[1];
    $has_dep = isset($item[3]) && $item[3];
    echo '<div class="row">';
    echo '<div class="name">'.$name.($has_dep ? '<span class="badge-dep">Dep</span>' : '').'</div>';
    if (hasTile($name, $has_tile)) {
        echo '<div class="col-tile"><span class="status-installed">&#9679;</span> <button class="btn-uninstall" onclick="uninstall(\''.$name.'\',\'tile\')">X</button></div>';
    } else {
        echo '<div class="col-tile"><button class="btn-install" onclick="install(\''.$name.'\',\'tile\')">+</button></div>';
    }
    if ($bins) {
        if (hasAnyBin($bins, $has_bin)) {
            echo '<div class="col-bin"><span class="status-installed">&#9679;</span> <button class="btn-uninstall" onclick="uninstall_bin(\''.$bins.'\')">X</button></div>';
        } else {
            echo '<div class="col-bin"><button class="btn-install" onclick="install_bin(\''.$bins.'\')">+</button></div>';
        }
    } else {
        echo '<div class="col-bin">-</div>';
    }
    echo '</div>';
}
?>
</div>

<div class="infusion-box">
<div class="infusion-title">Attack</div>
<div class="row">
    <div class="name">Tool</div>
    <div class="col-tile">Tile</div>
    <div class="col-bin">Bin</div>
</div>
<?php
$attack = array(
    array('ettercap', '', 'Ettercap'),
    array('sslsplit', '', 'SSLSplit'),
    array('dnsspoof', '', 'DNS spoof'),
    array('ardronepwn', '', 'ARDrone'),
    array('sslstrip', 'sslstrip', 'SSL strip'),
    array('strip-n-inject', 'sslstrip', 'Strip inject'),
    array('crafty', 'hping3', 'Crafty'),
    array('deauth', 'mdk3,aireplay', 'Deauth attacks', true),
    array('occupineapple', 'mdk3,aireplay', 'Occupineapple', true)
);
foreach ($attack as $item) {
    $name = $item[0];
    $bins = $item[1];
    $has_dep = isset($item[3]) && $item[3];
    echo '<div class="row">';
    echo '<div class="name">'.$name.($has_dep ? '<span class="badge-dep">Dep</span>' : '').'</div>';
    if (hasTile($name, $has_tile)) {
        echo '<div class="col-tile"><span class="status-installed">&#9679;</span> <button class="btn-uninstall" onclick="uninstall(\''.$name.'\',\'tile\')">X</button></div>';
    } else {
        echo '<div class="col-tile"><button class="btn-install" onclick="install(\''.$name.'\',\'tile\')">+</button></div>';
    }
    if ($bins) {
        if (hasAnyBin($bins, $has_bin)) {
            echo '<div class="col-bin"><span class="status-installed">&#9679;</span> <button class="btn-uninstall" onclick="uninstall_bin(\''.$bins.'\')">X</button></div>';
        } else {
            echo '<div class="col-bin"><button class="btn-install" onclick="install_bin(\''.$bins.'\')">+</button></div>';
        }
    } else {
        echo '<div class="col-bin">-</div>';
    }
    echo '</div>';
}
?>
</div>

<div class="infusion-box">
<div class="infusion-title">Logging</div>
<div class="row">
    <div class="name">Tool</div>
    <div class="col-tile">Tile</div>
    <div class="col-bin">Bin</div>
</div>
<?php
$logging = array(
    array('tcpdump', '', 'TCP dump'),
    array('urlsnarf', '', 'URL snarf'),
    array('logcheck', '', 'Log check'),
    array('pineapplestats', '', 'Stats'),
    array('trapcookies', '', 'Trap cookies'),
    array('randomroll', '', 'Random roll')
);
foreach ($logging as $item) {
    $name = $item[0];
    $bins = $item[1];
    echo '<div class="row">';
    echo '<div class="name">'.$name.'</div>';
    if (hasTile($name, $has_tile)) {
        echo '<div class="col-tile"><span class="status-installed">&#9679;</span> <button class="btn-uninstall" onclick="uninstall(\''.$name.'\',\'tile\')">X</button></div>';
    } else {
        echo '<div class="col-tile"><button class="btn-install" onclick="install(\''.$name.'\',\'tile\')">+</button></div>';
    }
    echo '<div class="col-bin">-</div></div>';
}
?>
</div>

<div class="infusion-box">
<div class="infusion-title">Portal</div>
<div class="row">
    <div class="name">Tool</div>
    <div class="col-tile">Tile</div>
    <div class="col-bin">Bin</div>
</div>
<?php
$portal = array(
    array('evilportal', '', 'Evil Portal'),
    array('portalauth', '', 'Portal Auth')
);
foreach ($portal as $item) {
    $name = $item[0];
    echo '<div class="row">';
    echo '<div class="name">'.$name.'</div>';
    if (hasTile($name, $has_tile)) {
        echo '<div class="col-tile"><span class="status-installed">&#9679;</span> <button class="btn-uninstall" onclick="uninstall(\''.$name.'\',\'tile\')">X</button></div>';
    } else {
        echo '<div class="col-tile"><button class="btn-install" onclick="install(\''.$name.'\',\'tile\')">+</button></div>';
    }
    echo '<div class="col-bin">-</div></div>';
}
?>
</div>

<div class="infusion-box">
<div class="infusion-title">Utilities</div>
<div class="row">
    <div class="name">Tool</div>
    <div class="col-tile">Tile</div>
    <div class="col-bin">Bin</div>
</div>
<?php
$utilities = array('status','wifimanager','notify','dnschanger','dipstatus','connect');
foreach ($utilities as $name) {
    echo '<div class="row">';
    echo '<div class="name">'.$name.'</div>';
    if (hasTile($name, $has_tile)) {
        echo '<div class="col-tile"><span class="status-installed">&#9679;</span> <button class="btn-uninstall" onclick="uninstall(\''.$name.'\',\'tile\')">X</button></div>';
    } else {
        echo '<div class="col-tile"><button class="btn-install" onclick="install(\''.$name.'\',\'tile\')">+</button></div>';
    }
    echo '<div class="col-bin">-</div></div>';
}
?>
</div>

<div class="infusion-box">
<div class="dep-title">Dependencies (Binary Only)</div>
<div class="row">
    <div class="name">Tool</div>
    <div class="col-bin">Binary</div>
</div>
<?php
$deps = array(
    # Binary only - used by other tiles
    array('mdk3', 'mdk3', 'WiFi attacks'),
    array('aireplay', 'aireplay', 'aireplay/airmon/airodump'),
    array('reaver', 'reaver', 'WPS brute force'),
    array('bully', 'bully', 'WPS alternate'),
    array('pixiewps', 'pixiewps', 'Pixie Dust attack'),
    array('nbtscan', 'nbtscan', 'NetBIOS scanner'),
    array('p0f', 'p0f', 'OS fingerprinting')
);
foreach ($deps as $item) {
    $name = $item[0];
    $bin = $item[1];
    echo '<div class="row">';
    echo '<div class="name">'.$name.'</div>';
    if (hasBin($name, $has_bin)) {
        echo '<div class="col-bin"><span class="status-installed">&#9679;</span> <button class="btn-uninstall" onclick="uninstall_bin(\''.$bin.'\')">X</button></div>';
    } else {
        echo '<div class="col-bin"><button class="btn-install" onclick="install_bin(\''.$bin.'\')">+</button></div>';
    }
    echo '</div>';
}
?>
</div>

<div class="infusion-box">
<div class="infusion-title">System</div>
<div class="row">
    <div class="name">Tool</div>
    <div class="col-tile">Tile</div>
    <div class="col-bin">Bin</div>
</div>
<?php
$system = array('phials','opkgmanager','blackout','bobthebuilder','get','base64encdec','datalocker','delorean','meterpreter','torgateway','rtlradiostreamer','adsbtracker');
foreach ($system as $name) {
    echo '<div class="row">';
    echo '<div class="name">'.$name.'</div>';
    if (hasTile($name, $has_tile)) {
        echo '<div class="col-tile"><span class="status-installed">&#9679;</span> <button class="btn-uninstall" onclick="uninstall(\''.$name.'\',\'tile\')">X</button></div>';
    } else {
        echo '<div class="col-tile"><button class="btn-install" onclick="install(\''.$name.'\',\'tile\')">+</button></div>';
    }
    echo '<div class="col-bin">-</div></div>';
}
?>
</div>

</div>

<script>
function install(name, type) {
    var btn = event.target;
    btn.disabled = true;
    btn.classList.add('btn-disabled');
    btn.innerHTML = '...';
    
    var csrfToken = '';
    var meta = document.querySelector('meta[name=_csrfToken]');
    if (meta) csrfToken = meta.getAttribute('content');
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/components/infusions/infusionmanager/handler.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4) {
            if (xhr.responseText.indexOf('SCHEDULED') > -1 || xhr.responseText.trim() === 'ok') {
                btn.innerHTML = '✓';
            } else {
                btn.disabled = false;
                btn.classList.remove('btn-disabled');
                btn.innerHTML = '+';
                alert('Error: ' + xhr.responseText);
            }
        }
    };
    xhr.send('action=install&name=' + encodeURIComponent(name) + '&_csrfToken=' + encodeURIComponent(csrfToken));
}

function install_bin(bins) {
    var btn = event.target;
    btn.disabled = true;
    btn.classList.add('btn-disabled');
    btn.innerHTML = '...';
    
    var csrfToken = '';
    var meta = document.querySelector('meta[name=_csrfToken]');
    if (meta) csrfToken = meta.getAttribute('content');
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/components/infusions/infusionmanager/handler.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4) {
            if (xhr.responseText.indexOf('SCHEDULED') > -1 || xhr.responseText.trim() === 'ok') {
                btn.innerHTML = '✓';
            } else {
                btn.disabled = false;
                btn.classList.remove('btn-disabled');
                btn.innerHTML = '+';
                alert('Error: ' + xhr.responseText);
            }
        }
    };
    xhr.send('action=install&name=' + encodeURIComponent(bins) + '&_csrfToken=' + encodeURIComponent(csrfToken));
}

function uninstall(name, type) {
    var btn = event.target;
    btn.disabled = true;
    btn.classList.add('btn-disabled');
    btn.innerHTML = '...';
    
    var csrfToken = '';
    var meta = document.querySelector('meta[name=_csrfToken]');
    if (meta) csrfToken = meta.getAttribute('content');
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/components/infusions/infusionmanager/handler.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4) {
            if (xhr.responseText.indexOf('UNSCHEDULED') > -1 || xhr.responseText.trim() === 'ok') {
                btn.innerHTML = '✓';
            } else {
                btn.disabled = false;
                btn.classList.remove('btn-disabled');
                btn.innerHTML = 'X';
                alert('Error: ' + xhr.responseText);
            }
        }
    };
    xhr.send('action=uninstall&name=' + encodeURIComponent(name) + '&_csrfToken=' + encodeURIComponent(csrfToken));
}

function uninstall_bin(bins) {
    var btn = event.target;
    btn.disabled = true;
    btn.classList.add('btn-disabled');
    btn.innerHTML = '...';
    
    var csrfToken = '';
    var meta = document.querySelector('meta[name=_csrfToken]');
    if (meta) csrfToken = meta.getAttribute('content');
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/components/infusions/infusionmanager/handler.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4) {
            if (xhr.responseText.indexOf('UNSCHEDULED') > -1 || xhr.responseText.trim() === 'ok') {
                btn.innerHTML = '✓';
            } else {
                btn.disabled = false;
                btn.classList.remove('btn-disabled');
                btn.innerHTML = 'X';
                alert('Error: ' + xhr.responseText);
            }
        }
    };
    xhr.send('action=uninstall&name=' + encodeURIComponent(bins) + '&_csrfToken=' + encodeURIComponent(csrfToken));
}
</script>
</div>
