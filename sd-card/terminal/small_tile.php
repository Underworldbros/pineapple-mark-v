<?php
$name = 'Terminal';
$updatable = 'false';
$version = '1.0';
?>
<script>
setInterval(function() {
    var tiles = document.querySelectorAll('.tile_small');
    tiles.forEach(function(tile) {
        if (tile.querySelector('#terminal_small')) {
            tile.style.height = '180px';
            tile.style.minHeight = '180px';
        }
    });
}, 500);
</script>
<div id="terminal_small" style="padding:5px;display:flex;flex-direction:column;height:150px;box-sizing:border-box;">
<div id="term_out_small" style="flex:1;min-height:80px;background:#000;border:1px solid #333;border-radius:3px;padding:5px;font-family:monospace;font-size:11px;color:#0f0;overflow:auto;margin-bottom:5px;" onclick="refreshSmall()"></div>
<div style="display:flex;align-items:center;">
<span style="color:#0f0;font-family:monospace;font-size:11px;margin-right:5px;">$</span>
<input type="text" id="term_in_small" style="flex:1;background:#111;color:#0f0;border:1px solid #333;padding:5px;font-family:monospace;font-size:11px;border-radius:3px;" placeholder="cmd" onkeypress="if(event.key==='Enter')execSmall()">
</div>
<script>
var csrfToken = '';
var meta = document.querySelector('meta[name=_csrfToken]');
if (meta) csrfToken = meta.getAttribute('content');

function execSmall() {
    var input = document.getElementById('term_in_small');
    var output = document.getElementById('term_out_small');
    var cmd = input.value.trim();
    if (!cmd) return;
    output.innerHTML += '$ ' + cmd + '\n';
    input.value = '';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/components/infusions/terminal/handler.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4) {
            if (xhr.status == 200) {
                output.innerHTML += xhr.responseText + '\n';
            } else {
                output.innerHTML += 'Error: ' + xhr.status + ' ' + xhr.statusText + '\n';
            }
            output.scrollTop = output.scrollHeight;
            setTimeout(refreshSmall, 500);
        }
    };
    xhr.send('action=exec&cmd=' + encodeURIComponent(cmd) + '&_csrfToken=' + encodeURIComponent(csrfToken));
}

function refreshSmall() {
    var output = document.getElementById('term_out_small');
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/components/infusions/terminal/handler.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            if (xhr.responseText.trim()) {
                output.innerHTML = xhr.responseText;
                output.scrollTop = output.scrollHeight;
            }
        }
    };
    xhr.send('action=exec&cmd=' + encodeURIComponent('echo ""') + '&_csrfToken=' + encodeURIComponent(csrfToken));
}

setInterval(refreshSmall, 3000);
</script>
</div>
