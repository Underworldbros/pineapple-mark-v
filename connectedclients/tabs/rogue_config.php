<h2>Rogue AP Configuration <a href="#" onclick="get_rogue_status();return false;" style="text-decoration:none;color:#0af;margin-left:20px;margin-right:20px;"><b>↻ Refresh</b></a></h2>

<div id="rogue_status">Loading...</div>

<hr />

<h3>Switch Subnet Preset</h3>
<p style="color:#999; font-size:11px;">Changes wlan0 (Rogue AP) subnet. eth0 and wlan0-1 are not affected.</p>

<table style="border-spacing: 10px 5px;">
<tr>
    <td><a href="#" id="btn_home" style="color:#0f0;">[Home]</a></td>
    <td>192.168.0.1/24 &mdash; Common home router subnet</td>
</tr>
<tr>
    <td><a href="#" id="btn_business" style="color:#0af;">[Business]</a></td>
    <td>10.0.0.1/24 &mdash; Common corporate/business subnet</td>
</tr>
</table>

<div id="rogue_result" style="margin-top:10px; text-align:center;"></div>

<form name="rogue_preset_form" action="/components/infusions/connectedclients/functions.php?action=set_rogue_subnet" method="POST" onsubmit="$(this).AJAXifyForm(rogue_switch_done); return false;">
<input type="hidden" id="rogue_preset_csrf" name="_csrfToken" value="" />
<input type="hidden" id="rogue_preset_value" name="preset" value="" />
<input type="submit" id="rogue_preset_submit" style="height:0px;width:0px;border:none;padding:0px;" hidefocus="true" />
</form>

<script type="text/javascript">
    get_rogue_status();
    
    // Set up button handlers - these execute IMMEDIATELY on click
    $('#btn_home').click(function(e) {
        e.preventDefault();
        $('#rogue_result').html('<div style="background-image: url(\'/includes/img/throbber.gif\'); background-repeat: no-repeat; background-position: center; width: 60px; height: 60px; margin: 0 auto; display: block;"></div><span style="color:#ff0; display: block; margin-top:10px;">Switching to home...</span>');
        document.getElementById('rogue_preset_value').value = 'home';
        document.getElementById('rogue_preset_csrf').value = $('meta[name=_csrfToken]').attr('content');
        document.getElementById('rogue_preset_submit').click();
    });
    
    $('#btn_business').click(function(e) {
        e.preventDefault();
        $('#rogue_result').html('<div style="background-image: url(\'/includes/img/throbber.gif\'); background-repeat: no-repeat; background-position: center; width: 60px; height: 60px; margin: 0 auto; display: block;"></div><span style="color:#ff0; display: block; margin-top:10px;">Switching to business...</span>');
        document.getElementById('rogue_preset_value').value = 'business';
        document.getElementById('rogue_preset_csrf').value = $('meta[name=_csrfToken]').attr('content');
        document.getElementById('rogue_preset_submit').click();
    });
</script>
