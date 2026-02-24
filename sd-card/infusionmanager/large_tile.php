<?php
$infusions_link = '/pineapple/components/infusions/';
$installed = array();
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

function getInfusionStatus($name, &$installed) {
    return in_array($name, $installed) ? 'installed' : 'notinstalled';
}
?>
<div id="infusionmanager_content" style="padding:10px;">
<h2>Infusion Manager</h2>
<p>Click Install to enable an infusion.</p>

<div style="display:flex;flex-wrap:wrap;gap:10px;">
<div style="border:1px solid #444;padding:10px;min-width:140px;background:#222;border-radius:5px;">
<div style="font-weight:bold;color:#4a9;margin-bottom:8px;">Recon</div>
<div style="margin:4px 0;font-size:12px;"><span id="st-nmap" class="status-<?php echo getInfusionStatus('nmap', $installed); ?>" style="color:#888">&#9675;</span> nmap <button <?php echo getInfusionStatus('nmap', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'nmap\')"' : 'onclick="install(\'nmap\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('nmap', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-sitesurvey" class="status-<?php echo getInfusionStatus('sitesurvey', $installed); ?>" style="color:#888">&#9675;</span> sitesurvey <button <?php echo getInfusionStatus('sitesurvey', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'sitesurvey\')"' : 'onclick="install(\'sitesurvey\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('sitesurvey', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-wps" class="status-<?php echo getInfusionStatus('wps', $installed); ?>" style="color:#888">&#9675;</span> wps <button <?php echo getInfusionStatus('wps', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'wps\')"' : 'onclick="install(\'wps\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('wps', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-nbtscan" class="status-<?php echo getInfusionStatus('nbtscan', $installed); ?>" style="color:#888">&#9675;</span> nbtscan <button <?php echo getInfusionStatus('nbtscan', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'nbtscan\')"' : 'onclick="install(\'nbtscan\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('nbtscan', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-arping" class="status-<?php echo getInfusionStatus('arping', $installed); ?>" style="color:#888">&#9675;</span> arping <button <?php echo getInfusionStatus('arping', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'arping\')"' : 'onclick="install(\'arping\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('arping', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-connectedclients" class="status-<?php echo getInfusionStatus('connectedclients', $installed); ?>" style="color:#888">&#9675;</span> connectedclients <button <?php echo getInfusionStatus('connectedclients', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'connectedclients\')"' : 'onclick="install(\'connectedclients\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('connectedclients', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-monitor" class="status-<?php echo getInfusionStatus('monitor', $installed); ?>" style="color:#888">&#9675;</span> monitor <button <?php echo getInfusionStatus('monitor', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'monitor\')"' : 'onclick="install(\'monitor\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('monitor', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
</div>

<div style="border:1px solid #444;padding:10px;min-width:140px;background:#222;border-radius:5px;">
<div style="font-weight:bold;color:#4a9;margin-bottom:8px;">Attack</div>
<div style="margin:4px 0;font-size:12px;"><span id="st-deauth" class="status-<?php echo getInfusionStatus('deauth', $installed); ?>" style="color:#888">&#9675;</span> deauth <button <?php echo getInfusionStatus('deauth', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'deauth\')"' : 'onclick="install(\'deauth\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('deauth', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-ettercap" class="status-<?php echo getInfusionStatus('ettercap', $installed); ?>" style="color:#888">&#9675;</span> ettercap <button <?php echo getInfusionStatus('ettercap', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'ettercap\')"' : 'onclick="install(\'ettercap\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('ettercap', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-sslstrip" class="status-<?php echo getInfusionStatus('sslstrip', $installed); ?>" style="color:#888">&#9675;</span> sslstrip <button <?php echo getInfusionStatus('sslstrip', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'sslstrip\')"' : 'onclick="install(\'sslstrip\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('sslstrip', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-sslsplit" class="status-<?php echo getInfusionStatus('sslsplit', $installed); ?>" style="color:#888">&#9675;</span> sslsplit <button <?php echo getInfusionStatus('sslsplit', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'sslsplit\')"' : 'onclick="install(\'sslsplit\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('sslsplit', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-strip-n-inject" class="status-<?php echo getInfusionStatus('strip-n-inject', $installed); ?>" style="color:#888">&#9675;</span> strip-n-inject <button <?php echo getInfusionStatus('strip-n-inject', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'strip-n-inject\')"' : 'onclick="install(\'strip-n-inject\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('strip-n-inject', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-dnsspoof" class="status-<?php echo getInfusionStatus('dnsspoof', $installed); ?>" style="color:#888">&#9675;</span> dnsspoof <button <?php echo getInfusionStatus('dnsspoof', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'dnsspoof\')"' : 'onclick="install(\'dnsspoof\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('dnsspoof', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-ardronepwn" class="status-<?php echo getInfusionStatus('ardronepwn', $installed); ?>" style="color:#888">&#9675;</span> ardronepwn <button <?php echo getInfusionStatus('ardronepwn', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'ardronepwn\')"' : 'onclick="install(\'ardronepwn\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('ardronepwn', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-crafty" class="status-<?php echo getInfusionStatus('crafty', $installed); ?>" style="color:#888">&#9675;</span> crafty <button <?php echo getInfusionStatus('crafty', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'crafty\')"' : 'onclick="install(\'crafty\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('crafty', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-occupineapple" class="status-<?php echo getInfusionStatus('occupineapple', $installed); ?>" style="color:#888">&#9675;</span> occupineapple <button <?php echo getInfusionStatus('occupineapple', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'occupineapple\')"' : 'onclick="install(\'occupineapple\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('occupineapple', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
</div>

<div style="border:1px solid #444;padding:10px;min-width:140px;background:#222;border-radius:5px;">
<div style="font-weight:bold;color:#4a9;margin-bottom:8px;">Logging</div>
<div style="margin:4px 0;font-size:12px;"><span id="st-tcpdump" class="status-<?php echo getInfusionStatus('tcpdump', $installed); ?>" style="color:#888">&#9675;</span> tcpdump <button <?php echo getInfusionStatus('tcpdump', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'tcpdump\')"' : 'onclick="install(\'tcpdump\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('tcpdump', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-urlsnarf" class="status-<?php echo getInfusionStatus('urlsnarf', $installed); ?>" style="color:#888">&#9675;</span> urlsnarf <button <?php echo getInfusionStatus('urlsnarf', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'urlsnarf\')"' : 'onclick="install(\'urlsnarf\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('urlsnarf', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-logcheck" class="status-<?php echo getInfusionStatus('logcheck', $installed); ?>" style="color:#888">&#9675;</span> logcheck <button <?php echo getInfusionStatus('logcheck', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'logcheck\')"' : 'onclick="install(\'logcheck\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('logcheck', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-pineapplestats" class="status-<?php echo getInfusionStatus('pineapplestats', $installed); ?>" style="color:#888">&#9675;</span> pineapplestats <button <?php echo getInfusionStatus('pineapplestats', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'pineapplestats\')"' : 'onclick="install(\'pineapplestats\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('pineapplestats', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-trapcookies" class="status-<?php echo getInfusionStatus('trapcookies', $installed); ?>" style="color:#888">&#9675;</span> trapcookies <button <?php echo getInfusionStatus('trapcookies', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'trapcookies\')"' : 'onclick="install(\'trapcookies\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('trapcookies', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-p0f" class="status-<?php echo getInfusionStatus('p0f', $installed); ?>" style="color:#888">&#9675;</span> p0f <button <?php echo getInfusionStatus('p0f', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'p0f\')"' : 'onclick="install(\'p0f\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('p0f', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-randomroll" class="status-<?php echo getInfusionStatus('randomroll', $installed); ?>" style="color:#888">&#9675;</span> randomroll <button <?php echo getInfusionStatus('randomroll', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'randomroll\')"' : 'onclick="install(\'randomroll\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('randomroll', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
</div>

<div style="border:1px solid #444;padding:10px;min-width:140px;background:#222;border-radius:5px;">
<div style="font-weight:bold;color:#4a9;margin-bottom:8px;">Portal</div>
<div style="margin:4px 0;font-size:12px;"><span id="st-evilportal" class="status-<?php echo getInfusionStatus('evilportal', $installed); ?>" style="color:#888">&#9675;</span> evilportal <button <?php echo getInfusionStatus('evilportal', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'evilportal\')"' : 'onclick="install(\'evilportal\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('evilportal', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-portalauth" class="status-<?php echo getInfusionStatus('portalauth', $installed); ?>" style="color:#888">&#9675;</span> portalauth <button <?php echo getInfusionStatus('portalauth', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'portalauth\')"' : 'onclick="install(\'portalauth\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('portalauth', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
</div>

<div style="border:1px solid #444;padding:10px;min-width:140px;background:#222;border-radius:5px;">
<div style="font-weight:bold;color:#4a9;margin-bottom:8px;">Utilities</div>
<div style="margin:4px 0;font-size:12px;"><span id="st-status" class="status-<?php echo getInfusionStatus('status', $installed); ?>" style="color:#888">&#9675;</span> status <button <?php echo getInfusionStatus('status', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'status\')"' : 'onclick="install(\'status\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('status', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-wifimanager" class="status-<?php echo getInfusionStatus('wifimanager', $installed); ?>" style="color:#888">&#9675;</span> wifimanager <button <?php echo getInfusionStatus('wifimanager', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'wifimanager\')"' : 'onclick="install(\'wifimanager\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('wifimanager', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-notify" class="status-<?php echo getInfusionStatus('notify', $installed); ?>" style="color:#888">&#9675;</span> notify <button <?php echo getInfusionStatus('notify', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'notify\')"' : 'onclick="install(\'notify\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('notify', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-dnschanger" class="status-<?php echo getInfusionStatus('dnschanger', $installed); ?>" style="color:#888">&#9675;</span> dnschanger <button <?php echo getInfusionStatus('dnschanger', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'dnschanger\')"' : 'onclick="install(\'dnschanger\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('dnschanger', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-dipstatus" class="status-<?php echo getInfusionStatus('dipstatus', $installed); ?>" style="color:#888">&#9675;</span> dipstatus <button <?php echo getInfusionStatus('dipstatus', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'dipstatus\')"' : 'onclick="install(\'dipstatus\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('dipstatus', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-connect" class="status-<?php echo getInfusionStatus('connect', $installed); ?>" style="color:#888">&#9675;</span> connect <button <?php echo getInfusionStatus('connect', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'connect\')"' : 'onclick="install(\'connect\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('connect', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
</div>

<div style="border:1px solid #444;padding:10px;min-width:140px;background:#222;border-radius:5px;">
<div style="font-weight:bold;color:#4a9;margin-bottom:8px;">System</div>
<div style="margin:4px 0;font-size:12px;"><span id="st-phials" class="status-<?php echo getInfusionStatus('phials', $installed); ?>" style="color:#888">&#9675;</span> phials <button <?php echo getInfusionStatus('phials', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'phials\')"' : 'onclick="install(\'phials\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('phials', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-opkgmanager" class="status-<?php echo getInfusionStatus('opkgmanager', $installed); ?>" style="color:#888">&#9675;</span> opkgmanager <button <?php echo getInfusionStatus('opkgmanager', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'opkgmanager\')"' : 'onclick="install(\'opkgmanager\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('opkgmanager', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-blackout" class="status-<?php echo getInfusionStatus('blackout', $installed); ?>" style="color:#888">&#9675;</span> blackout <button <?php echo getInfusionStatus('blackout', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'blackout\')"' : 'onclick="install(\'blackout\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('blackout', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-bobthebuilder" class="status-<?php echo getInfusionStatus('bobthebuilder', $installed); ?>" style="color:#888">&#9675;</span> bobthebuilder <button <?php echo getInfusionStatus('bobthebuilder', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'bobthebuilder\')"' : 'onclick="install(\'bobthebuilder\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('bobthebuilder', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-get" class="status-<?php echo getInfusionStatus('get', $installed); ?>" style="color:#888">&#9675;</span> get <button <?php echo getInfusionStatus('get', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'get\')"' : 'onclick="install(\'get\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('get', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-base64encdec" class="status-<?php echo getInfusionStatus('base64encdec', $installed); ?>" style="color:#888">&#9675;</span> base64encdec <button <?php echo getInfusionStatus('base64encdec', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'base64encdec\')"' : 'onclick="install(\'base64encdec\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('base64encdec', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-datalocker" class="status-<?php echo getInfusionStatus('datalocker', $installed); ?>" style="color:#888">&#9675;</span> datalocker <button <?php echo getInfusionStatus('datalocker', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'datalocker\')"' : 'onclick="install(\'datalocker\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('datalocker', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-delorean" class="status-<?php echo getInfusionStatus('delorean', $installed); ?>" style="color:#888">&#9675;</span> delorean <button <?php echo getInfusionStatus('delorean', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'delorean\')"' : 'onclick="install(\'delorean\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('delorean', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-meterpreter" class="status-<?php echo getInfusionStatus('meterpreter', $installed); ?>" style="color:#888">&#9675;</span> meterpreter <button <?php echo getInfusionStatus('meterpreter', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'meterpreter\')"' : 'onclick="install(\'meterpreter\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('meterpreter', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-torgateway" class="status-<?php echo getInfusionStatus('torgateway', $installed); ?>" style="color:#888">&#9675;</span> torgateway <button <?php echo getInfusionStatus('torgateway', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'torgateway\')"' : 'onclick="install(\'torgateway\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('torgateway', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-rtlradiostreamer" class="status-<?php echo getInfusionStatus('rtlradiostreamer', $installed); ?>" style="color:#888">&#9675;</span> rtlradiostreamer <button <?php echo getInfusionStatus('rtlradiostreamer', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'rtlradiostreamer\')"' : 'onclick="install(\'rtlradiostreamer\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('rtlradiostreamer', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
<div style="margin:4px 0;font-size:12px;"><span id="st-adsbtracker" class="status-<?php echo getInfusionStatus('adsbtracker', $installed); ?>" style="color:#888">&#9675;</span> adsbtracker <button <?php echo getInfusionStatus('adsbtracker', $installed) == 'installed' ? 'class="btn-uninstall" onclick="uninstall(\'adsbtracker\')"' : 'onclick="install(\'adsbtracker\')"'; ?> style="cursor:pointer;"><?php echo getInfusionStatus('adsbtracker', $installed) == 'installed' ? 'Uninstall' : 'Install'; ?></button></div>
</div>
</div>

<style>
.status-installed { color: #4f4 !important; }
.status-notinstalled { color: #888 !important; }
.btn-installed { background: #282 !important; color: #4f4 !important; cursor: default !important; }
.btn-uninstall { background: #a33 !important; color: #fff !important; cursor: pointer !important; }
</style>
<script>
function install(name) {
    var btn = event.target;
    if (btn.classList.contains('btn-installed')) return;
    
    btn.disabled = true;
    btn.innerHTML = 'Installing...';
    
    var csrfToken = '';
    var meta = document.querySelector('meta[name=_csrfToken]');
    if (meta) csrfToken = meta.getAttribute('content');
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/components/infusions/infusionmanager/handler.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4) {
            var el = document.getElementById('st-' + name);
            if (xhr.responseText.indexOf('SCHEDULED') > -1) {
                setTimeout(function() {
                    if (el) {
                        el.innerHTML = '&#9679;';
                        el.className = 'status-installed';
                    }
                    btn.innerHTML = 'Uninstall';
                    btn.className = 'btn-uninstall';
                    btn.onclick = function() { uninstall(name); };
                }, 2000);
            } else {
                btn.disabled = false;
                btn.innerHTML = 'Install';
                alert('Error: ' + xhr.responseText);
            }
        }
    };
    xhr.send('action=install&name=' + encodeURIComponent(name) + '&_csrfToken=' + encodeURIComponent(csrfToken));
}

function uninstall(name) {
    var btn = event.target;
    btn.disabled = true;
    btn.innerHTML = 'Uninstalling...';
    
    var csrfToken = '';
    var meta = document.querySelector('meta[name=_csrfToken]');
    if (meta) csrfToken = meta.getAttribute('content');
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/components/infusions/infusionmanager/handler.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4) {
            var el = document.getElementById('st-' + name);
            if (xhr.responseText.indexOf('UNSCHEDULED') > -1) {
                setTimeout(function() {
                    if (el) {
                        el.innerHTML = '&#9675;';
                        el.className = 'status-notinstalled';
                    }
                    btn.innerHTML = 'Install';
                    btn.className = '';
                    btn.onclick = function() { install(name); };
                }, 2000);
            } else {
                btn.disabled = false;
                btn.innerHTML = 'Uninstall';
                alert('Error: ' + xhr.responseText);
            }
        }
    };
    xhr.send('action=uninstall&name=' + encodeURIComponent(name) + '&_csrfToken=' + encodeURIComponent(csrfToken));
}
</script>
</div>
