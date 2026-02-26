<div style="max-height:600px;overflow-y:auto;padding:10px;background:#1a1a1a;border:1px solid #444;border-radius:4px;">

<h2>About DHCP Manager</h2>
<h3>Overview</h3>
<p>DHCP Manager provides comprehensive management of DHCP leases across all network interfaces, real-time client monitoring, and flexible rogue AP configuration for different engagement scenarios.</p>

<h3>DHCP Manager Tab</h3>
<p>The DHCP Manager tab provides a complete dashboard of active leases across all interfaces (Ethernet, Rogue AP, Internet). View lease details including hostname, IP address, MAC address, expiry time, and network type. Manage static leases, configure DHCP ranges, and review DHCP logs. Export lease data for reporting.</p>
<p><b>New in v1.5:</b> Configurable renew duration dropdown allows you to select how long to extend a lease when renewing (30 minutes to 7 days). The Force Refresh button has been removed as Release button handles all lease management needs.</p>

<h3>Rogue AP Configuration</h3>
<p>The Rogue AP tab allows you to switch the wlan0 (Rogue AP) subnet between presets for different engagement scenarios.  This only affects wlan0 - eth0 and wlan0-1 remain on br-lan (172.16.42.1) and are not affected.</p>
<ul>
<li><b>Home</b> (192.168.0.1/24) - Mimics a typical home router subnet. Modern devices recognise this as a trusted home network.</li>
<li><b>Business</b> (10.0.0.1/24) - Mimics a corporate/business network subnet.</li>
<li><b>Default</b> (172.16.42.1/24) - Factory configuration. wlan0 rejoins br-lan alongside eth0 and wlan0-1.</li>
</ul>
<p>Subnet changes persist across reboots.</p>

<h3>Connected Clients Tab</h3>
<p>The Connected Clients tab uses the 'iw' command to get a list of all the clients currently associated with the pineapple.  The clients listed on this tab are broken down according to the interface they are connected to.  wlan0 is the rogue AP interface.  wlan0-1 is the backend access point protected by WPA2.</p>
<p>The deauthenticate and disassociate buttons can be clicked to send the respective command to the selected client.  By first blacklisting a client's mac address, and then either deauthenticating them or disassociating them, you can kick the selected device off of networks provided by the pineapple and simultaneously block the client from re-connecting.</p>
<p><b>Fixed in v1.5:</b> Stale DHCP leases are now automatically filtered out using ARP table detection. Devices that appear on multiple networks will only show on their actual connected interface.</p>

<h3>Blacklist Management</h3>
<p>The Blacklist tab shows the mac addresses currently in Karma's blacklist.  Macs listed in the blacklist will not be allowed to associate with the pineapple.</p>
<p>The remove button will remove the selected mac address from Karma's blacklist.</p>

<h2>Changelog</h2>

<h3>v1.5</h3>
<p><b>Features:</b></p>
<ul>
<li>Added configurable Renew Duration dropdown selector in DHCP Manager tab (8 duration options: 30 min to 7 days)</li>
<li>Removed Force Refresh button - Release button handles all lease management needs</li>
</ul>
<p><b>Bug Fixes:</b></p>
<ul>
<li>Fixed stale DHCP lease filtering using ARP table detection - prevents duplicate device listings</li>
<li>Fixed hostname display in all client listing tabs by properly trimming whitespace</li>
<li>Improved online/offline device detection accuracy using ARP presence check instead of iw station dump</li>
<li>Fixed CSV export form ID attribute for proper export functionality</li>
</ul>

<h3>v1.4</h3>
<p>Renamed to DHCP Manager - now the primary module for comprehensive DHCP lease management. Enhanced small tile to show detailed lease info (hostname, IP, MAC, expiry time, network type). Added DHCP Manager dashboard tab for lease management (dashboard, leases, static IPs, ranges, logs). Added Rogue AP configuration tab with animated subnet switching (Home/Business/Default presets). wlan0 can now be placed on its own 192.168.0.x or 10.0.0.x subnet for better client compatibility. Fixed network restart to preserve br-lan and wlan0-1 leases.</p>

<h3>v1.3</h3>
<p>Updated code to work with pineapple firmware 2.1.0.  Added the deauthenticate and disassociate buttons to the connected clients tab.  Split the connected clients tab into wlan0 and wlan0-1.</p>

<h3>v1.2</h3>
<p>Connected Clients tab changed to show currently connected mac addresses from 'iw' output.  DHCP leases from /tmp/dhcp.leases moved to DHCP Leases tab.</p>

<h3>v1.1</h3>
<p>Added Blacklist tab and ability to blacklist mac addresses from DHCP leases.</p>

<h3>v1.0</h3>
<p>Initial Release.  Shows DHCP leases.</p>

</div>
