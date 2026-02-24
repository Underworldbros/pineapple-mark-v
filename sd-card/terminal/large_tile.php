<?php
$name = 'Terminal';
$updatable = 'false';
$version = '1.0';
?>
<div id="terminal_content" style="padding:10px;width:100%;">
<h2>Terminal <button onclick="refreshOutput()" style="margin-left:10px;padding:2px 8px;background:#282;color:#0f0;border:1px solid #444;border-radius:3px;cursor:pointer;font-size:11px;">Refresh</button> <button onclick="clearScreen()" style="padding:2px 8px;background:#333;color:#888;border:1px solid #444;border-radius:3px;cursor:pointer;font-size:11px;">Clear</button></h2>
<div style="background:#111;border:1px solid #444;border-radius:5px;padding:10px;height:400px;overflow:hidden;display:flex;flex-direction:column;">
<div id="terminal_output" style="flex:1;overflow-y:auto;font-family:monospace;font-size:12px;color:#0f0;white-space:pre-wrap;word-break:break-all;margin-bottom:10px;background:#000;padding:8px;border-radius:3px;" onclick="refreshOutput()"></div>
<div style="display:flex;align-items:center;">
<span style="color:#0f0;font-family:monospace;margin-right:5px;">$</span>
<input type="text" id="terminal_input" style="flex:1;background:#222;color:#0f0;border:1px solid #444;padding:8px;font-family:monospace;font-size:12px;border-radius:3px;" placeholder="Enter command..." autofocus onkeypress="if(event.key==='Enter')execute()">
<button onclick="execute()" style="margin-left:8px;padding:8px 16px;background:#282;color:#0f0;border:1px solid #444;border-radius:3px;cursor:pointer;">Run</button>
</div>
</div>

<script>
var csrfToken = '';
var meta = document.querySelector('meta[name=_csrfToken]');
if (meta) csrfToken = meta.getAttribute('content');

function execute() {
    var input = document.getElementById('terminal_input');
    var output = document.getElementById('terminal_output');
    var cmd = input.value.trim();
    
    if (!cmd) return;
    
    output.innerHTML += '$ ' + cmd + '\n';
    input.value = '';
    
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/components/infusions/terminal/handler.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4) {
            output.innerHTML += xhr.responseText + '\n';
            output.scrollTop = output.scrollHeight;
            // Auto refresh after command
            setTimeout(refreshOutput, 500);
        }
    };
    xhr.send('action=exec&cmd=' + encodeURIComponent(cmd) + '&_csrfToken=' + encodeURIComponent(csrfToken));
}

function refreshOutput() {
    var output = document.getElementById('terminal_output');
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/components/infusions/terminal/handler.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4) {
            if (xhr.responseText.trim()) {
                output.innerHTML = xhr.responseText;
                output.scrollTop = output.scrollHeight;
            }
        }
    };
    xhr.send('action=exec&cmd=' + encodeURIComponent('echo ""') + '&_csrfToken=' + encodeURIComponent(csrfToken));
}

function clearScreen() {
    var output = document.getElementById('terminal_output');
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/components/infusions/terminal/handler.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4) {
            output.innerHTML = '';
        }
    };
    xhr.send('action=clear&_csrfToken=' + encodeURIComponent(csrfToken));
}

// Auto refresh every 2 seconds
setInterval(refreshOutput, 2000);

document.getElementById('terminal_input').focus();
</script>
</div>
