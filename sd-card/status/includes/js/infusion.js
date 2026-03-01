var status_showDots;

var status_showLoadingDots = function() {
    clearInterval(status_showDots);
	if (!$("#status_loadingDots").length>0) return false;
    status_showDots = setInterval(function(){            
        var d = $("#status_loadingDots");
        d.text().length >= 3 ? d.text('') : d.append('.');
    },300);
}

function status_myajaxStart()
{
	$('#status_loading').css("background-image", "url(/includes/img/throbber.gif)");
	
	$("#status.refresh_text").html('<em>Loading<span id="status_loadingDots"></span></em>'); 
	status_showLoadingDots();
}

function status_myajaxStop(msg)
{
	$('#status_loading').css("background-image", "url(/includes/img/refresh.png)");
	
	$("#status.refresh_text").html(msg); 
	clearInterval(status_showDots);
}

function status_init_small() {

	status_refresh_tile();
}

function status_init() {

	status_refresh();
}

function status_refresh() {
	$.ajax({
		type: "POST",
		beforeSend: status_myajaxStart(),
		url: "/components/infusions/status/includes/data.php",
		success: function(msg){
			$("#status_content").html(msg);
			status_myajaxStop('');
		}
	});
}

function status_getOUIFromMAC(mac) {
    var tab = new Array();
	tab = mac.split(mac.substr(2,1));
	
	$.get('/components/infusions/status/includes/mac.php', {w: tab[0] + '-' + tab[1] + '-' + tab[2]}, function(data){
	    $('.popup_content').html(data);
	    $('.popup').css('visibility', 'visible');
    });
}

function status_graph(what) {
    $.get('/components/infusions/status/includes/graph.php', {w: what}, function(data){
	    $('.popup_content').html(data);
	    $('.popup').css('visibility', 'visible');
    });
}

function status_execute(what) {
    $.get('/components/infusions/status/includes/execute.php', {cmd: what}, function(data){
	    $('.popup_content').html(data);
	    $('.popup').css('visibility', 'visible');
    });
}

function status_refresh_tile() {
	$.ajax({
		type: "GET",
		data: "interface",
		beforeSend: status_myajaxStart(),
		url: "/components/infusions/status/includes/data_small.php",
		success: function(msg){
			$("#status_content_small").html(msg);
			status_myajaxStop('');
		}
	});
}