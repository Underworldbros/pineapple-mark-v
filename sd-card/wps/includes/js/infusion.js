var wps_auto_refresh;
var wps_showDots;

var wps_showLoadingDots = function() {
    clearInterval(wps_showDots);

	if (!$("#wps_loadingDots").length>0) return false;
    wps_showDots = setInterval(function(){            
        var d = $("#wps_loadingDots");
        d.text().length >= 3 ? d.text('') : d.append('.');
    },300);
}

$.fn.toggleClick=function() {
	var functions=arguments, iteration=0
		return this.click(function(){
			functions[iteration].apply(this,arguments)
			iteration= (iteration+1) %functions.length
		})
}

function wps_myajaxStart()
{
	$('#wps_loading').css("background-image", "url(/includes/img/throbber.gif)");
	
	if(wps_auto_refresh == null)
	{
		$("#wps.refresh_text").html('<em>Loading<span id="wps_loadingDots"></span></em>'); 
		wps_showLoadingDots();
	}
}

function wps_myajaxStop(msg)
{
	$('#wps_loading').css("background-image", "url(/includes/img/refresh.png)");
	
	if(wps_auto_refresh == null)
	{
		$("#wps.refresh_text").html(msg); 
		clearInterval(wps_showDots);
	}
}

function wps_init_small() {
	$('#wps_program_small').change(function() { wps_update_small() });
	$('#wps_monitorInterface_small').change(function() { wps_update_small() });
	$('#BSSID_small').keyup(function() { wps_update_small() });
	$('#ESSID_small').keyup(function() { wps_update_small() });
	$('#Channel_small').keyup(function() { wps_update_small() });
	
	wps_update_small();
	
	wps_refresh_tile();
}

function wps_init() {
	
	wps_refresh();
	wps_refresh_available_ap();
	wps_refresh_history();
	
	wps_update_program("reaver");
	
	$("#wps ul").idTabs();
	$("#wps2 ul").idTabs();
	
	$('#wps_program').change(function() { wps_update_program($('#wps_program').val()) });
	$('#wps_monitorInterface').change(function() { wps_update() });
	$(':checkbox').click(function() { wps_update() });
		
	$('#BSSID').keyup(function() { wps_update() });
	$('#ESSID').keyup(function() { wps_update() });
	$('#Channel').keyup(function() { wps_update() });
	
	$("#wps_auto_refresh").toggleClick(function() {
			$("#wps_auto_refresh").html('<font color="lime">On</font>');
			$('#auto_time').attr('disabled', 'disabled');
			
			wps_auto_refresh = setInterval(
			function ()
			{
				wps_refresh();
			},
			$("#auto_time").val());
		}, function() {
			$("#wps_auto_refresh").html('<font color="red">Off</font>');
			$('#auto_time').removeAttr('disabled');
				
            clearInterval(wps_auto_refresh);
			wps_auto_refresh = null;
		});
}

function wps_refresh() {
	$.ajax({
		type: "GET",
		data: "lastlog",
		beforeSend: wps_myajaxStart(),
		url: "/components/infusions/wps/includes/data.php",
		success: function(msg){
			wps_myajaxStop('');
			$("#wps_output").val(msg).scrollTop($("#wps_output")[0].scrollHeight - $("#wps_output").height());
		}
	});
}

function wps_refresh_history() {
	$.ajax({
		type: "GET",
		data: "history",
		beforeSend: wps_myajaxStart(),
		url: "/components/infusions/wps/includes/data.php",
		success: function(msg){
			$("#content").html(msg);
			wps_myajaxStop('');
		}
	});
}

function wps_toggle(action) {	
	$.get('/components/infusions/wps/includes/actions.php?wps&'+action, {action: action} , function() { refresh_small('wps','infusions'); });
	
	if(action == 'start') {
		wps_start();
		$("#launch").html('<font color="red"><strong>Stop</strong></font>');
		$("#launch").attr("href", "javascript:wps_toggle('stop');");
	}
	else {
		wps_cancel();
		$("#launch").html('<font color="lime"><strong>Start</strong></font>');
		$("#launch").attr("href", "javascript:wps_toggle('start');");
	}
}

function wps_toggle_small(action) {	
	$.get('/components/infusions/wps/includes/actions.php?wps&'+action, {action: action});
	
	if(action == 'start') {
		wps_start_small();
		$("#launch_small").html('<font color="red"><strong>Stop</strong></font>');
		$("#launch_small").attr("href", "javascript:wps_toggle_small('stop');");
	}
	else {
		wps_cancel_small();
		$("#launch_small").html('<font color="lime"><strong>Start</strong></font>');
		$("#launch_small").attr("href", "javascript:wps_toggle_small('start');");
	}
}

function wps_update() {
	$('#wps_command').val(wps_program() + wps_interface() + wps_options() + wps_BSSID() + wps_ESSID() + wps_Channel());
}

function wps_update_small() {
	$('#wps_command_small').val(wps_program_small() + wps_interface_small() + wps_BSSID_small() + wps_ESSID_small() + wps_Channel_small());
}

function wps_update_program(which) {
	$.ajax({
		type: "GET",
		data: "options_"+which,
		beforeSend: wps_myajaxStart(),
		url: "/components/infusions/wps/includes/options.php",
		success: function(msg){
			wps_myajaxStop('');
			
			$("#wps_options_content").html(msg);
			
			$(':checkbox').click(function() { wps_update() });
			wps_update();
		}
	});
	
	$.ajax({
		type: "GET",
		data: "advanced_"+which,
		beforeSend: wps_myajaxStart(),
		url: "/components/infusions/wps/includes/options.php",
		success: function(msg){
			wps_myajaxStop('');
			
			$("#advanced_content").html(msg);
			
			$(':checkbox').click(function() { wps_update() });
			wps_update();
		}
	});
}

function wps_refresh_interfaces() {
	$('#sidePanelContent_int').load('/components/infusions/wps/includes/interfaces.php?interface');
}

function wps_interface_toggle(interface, action) {		
	$.ajax({
		type: "POST",
		data: "interface=1&action="+action+"&int="+interface,
		beforeSend: wps_myajaxStart(),
		url: "/components/infusions/wps/includes/actions.php",
		success: function(msg){
			wps_myajaxStop(msg);
			
			wps_refresh(); wps_refresh_interfaces();
						
			wps_refresh_tile();
		}
	});

}

function wps_monitor_toggle(interface, monitor, action) {	
	$.ajax({
		type: "POST",
		data: "monitor=1&action="+action+"&int="+interface+"&mon="+monitor,
		beforeSend: wps_myajaxStart(),
		url: "/components/infusions/wps/includes/actions.php",
		success: function(msg){
			wps_myajaxStop(msg);
			
			wps_refresh(); wps_refresh_interfaces(); wps_refresh_monitors();
						
			wps_refresh_tile();
		}
	});
}

function wps_refresh_monitors() {
	
	var previous_val = $('#wps_monitorInterface option:selected').text();
	var previous_val2 = $('#wps_monitorInterfaceAP option:selected').text();
	
	$.ajax({
		type: "GET",
		data: "monitor",
		beforeSend: wps_myajaxStart(),
		url: "/components/infusions/wps/includes/interfaces.php",
		success: function(msg){
			wps_myajaxStop('');
			
			$('#wps_monitorInterface').html(msg);
			$('#wps_monitorInterface').val(previous_val);
			
			$('#wps_monitorInterfaceAP').html(msg);
			$('#wps_monitorInterfaceAP').val(previous_val2);
		}
	});
}

function wps_start() {
	$.ajax({
		type: "GET",
		data: "launch&int="+$('#wps_monitorInterface').find(":selected").text()+"&prog="+$('#wps_program').val()+"&bssid="+$('#BSSID').val()+"&essid="+$('#ESSID').val()+"&channel="+$('#Channel').val()+"&cmd="+$.base64.encode($('#wps_command').val()),
		beforeSend: wps_myajaxStart(),
		url: "/components/infusions/wps/includes/actions.php",
		success: function(msg){
			$("#wps_output").val(msg);
			$('#wps_output').val('wps is running...');
			wps_myajaxStop('');
			
			wps_refresh_history();
			
			wps_refresh_interfaces();
			
			refresh_small('wps','infusions');
		}
	});
}

function wps_start_small() {
	$.ajax({
		type: "GET",
		data: "launch&int="+$('#wps_monitorInterface_small').find(":selected").text()+"&prog="+$('#wps_program_small').val()+"&bssid="+$('#BSSID_small').val()+"&essid="+$('#ESSID_small').val()+"&channel="+$('#Channel_small').val()+"&cmd="+$.base64.encode($('#wps_command_small').val()),
		beforeSend: wps_myajaxStart(),
		url: "/components/infusions/wps/includes/actions.php",
		success: function(msg){
			$("#wps_output_small").val(msg);
			$('#wps_output_small').val('wps is running...');
			wps_myajaxStop('');
			
			wps_refresh_history();
		}
	});
}

function wps_cancel() {
	$.ajax({
		type: "GET",
		data: "cancel",
		beforeSend: wps_myajaxStart(),
		url: "/components/infusions/wps/includes/actions.php",
		success: function(msg){
			$("#wps_output").val(msg);
			$('#wps_output').val('wps has been stopped...');
			wps_myajaxStop('');
			
			wps_refresh_history();
			
			wps_refresh_interfaces();
			
			refresh_small('wps','infusions');
		}
	});
}

function wps_cancel_small() {
	$.ajax({
		type: "GET",
		data: "cancel",
		beforeSend: wps_myajaxStart(),
		url: "/components/infusions/wps/includes/actions.php",
		success: function(msg){
			$("#wps_output_small").val(msg);
			$('#wps_output_small').val('wps has been stopped...');
			wps_myajaxStop('');
		}
	});
}

function wps_delete_file(what) {
	$.ajax({
		type: "GET",
		data: "delete&file=" + what,
		beforeSend: wps_myajaxStart(),
		url: "/components/infusions/wps/includes/actions.php",
		success: function(msg){
			$("#content").html(msg);
			wps_refresh_history();
			wps_myajaxStop('');
		}
	});
}

function wps_load_file(which) {
    $.get('/components/infusions/wps/includes/actions.php?load', {file: which}, function(data){
	    $('.popup_content').html(data);
	    $('.popup').css('visibility', 'visible');
    });
}

function wps_program() {
	var return_value = "";
		
	if($("#wps_program").val() != "")
		return_value = $("#wps_program").val() + " ";
	
	return return_value;
}

function wps_BSSID() {
	var return_value = "";
		
	if($("#BSSID").val() != "")
		return_value = "-b " + $("#BSSID").val() + " ";
	
	return return_value;
}

function wps_ESSID() {
	var return_value = "";
		
	if($("#ESSID").val() != "")
		return_value = "-e \"" + $("#ESSID").val() + "\" ";
	
	return return_value;
}

function wps_Channel() {
	var return_value = "";
		
	if($("#Channel").val() != "")
		return_value = "-c " + $("#Channel").val();
	
	return return_value;
}

function wps_options(which) {
	var return_value = "";
	
    $('input:checked').each(function() {				
		if($("#" + $(this).attr('id') + "_input").length > 0)
			if($("#" + $(this).attr('id') + "_input").val() != "")
      			return_value += $(this).val() + " " + $("#" + $(this).attr('id') + "_input").val() + " ";
			else
      			return_value += $(this).val() + " ";
		else
			return_value += $(this).val() + " ";
    });
	
	return return_value;
}

function wps_interface() {
    var return_value = "";
	
	if($("#wps_monitorInterface").val() != "--")
	{
		if($("#wps_program").val() == "bully")
			return_value = $("#wps_monitorInterface").val() + " ";
		else
			return_value = "-i " + $("#wps_monitorInterface").val() + " ";
	}
	
	return return_value;
}

function wps_program_small() {
	var return_value = "";
		
	if($("#wps_program_small").val() != "")
		return_value = $("#wps_program_small").val() + " ";
	
	return return_value;
}

function wps_interface_small() {
    var return_value = "";

	if($("#wps_monitorInterface_small").val() != "--")
	{
		if($("#wps_program_small").val() == "bully")
			return_value = $("#wps_monitorInterface_small").val() + " ";
		else
			return_value = "-i " + $("#wps_monitorInterface_small").val() + " ";
	}
	
	return return_value;
}

function wps_BSSID_small() {
	var return_value = "";
		
	if($("#BSSID_small").val() != "")
		return_value = "-b " + $("#BSSID_small").val() + " ";
	
	return return_value;
}

function wps_ESSID_small() {
	var return_value = "";
		
	if($("#ESSID_small").val() != "")
		return_value = "-e " + $("#ESSID_small").val() + " ";
	
	return return_value;
}

function wps_Channel_small() {
	var return_value = "";
		
	if($("#Channel_small").val() != "")
		return_value = "-c " + $("#Channel_small").val();
	
	return return_value;
}

function wps_reload() {
	draw_large_tile('wps', 'infusions');
	refresh_small('wps','infusions');
}

function wps_install(what, where) {
	$.ajax({
		type: "GET",
		data: "install&where=" + where +"&what=" + what,
		beforeSend: wps_myajaxStart(),
		url: "/components/infusions/wps/includes/actions.php",
		success: function(msg){
			$("#wps_output").val(msg);
			wps_myajaxStop('');
			
			wps_reload();
		}
	});
}

function wps_refresh_available_ap() {
	$.ajax({
		type: "GET",
		data: "available_ap&int="+$("#wps_interfaces").val()+"&mon="+$("#wps_monitorInterfaceAP").val()+"&scan_time="+$("#scan_time").val()+"&scan_only_wps="+$("#scan_only_wps").val(),
		beforeSend: wps_myajaxStart(),
		url: "/components/infusions/wps/includes/interfaces.php",
		success: function(msg){
			$("#wps_list_ap").html(msg);
			wps_myajaxStop('');
			
			$('#wps-survey-grid tr').click(function() { 
			    var arr  = $(this).attr("name").split('|');
			    $("#ESSID").val(arr[0]);
			    $("#BSSID").val(arr[1]);
			    $("#Channel").val(arr[2]);
				
				wps_update();

				return false;
			});
		}
	});
}

function wps_delete_sessions() {
	$.ajax({
		type: "GET",
		data: "delete_sessions",
		beforeSend: wps_myajaxStart(),
		url: "/components/infusions/wps/includes/actions.php",
		success: function(msg){
			wps_myajaxStop(msg);
			$('#wps_output').val('deleting sessions files...');
		}
	});
}

function wps_refresh_tile() {
	$.ajax({
		type: "GET",
		data: "lastlog",
		beforeSend: wps_myajaxStart(),
		url: "/components/infusions/wps/includes/data.php",
		success: function(msg){
			wps_myajaxStop('');
			$("#wps_output_small").val(msg).scrollTop($("#wps_output_small")[0].scrollHeight - $("#wps_output_small").height());
		}
	});
}