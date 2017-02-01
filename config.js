/**
 * 
 */
window.onload = function() {
 
    $('#pluginFlyvemdm-mqtt-test').on('click', function() {
    	data = $("#pluginFlyvemdm-config input,#pluginFlyvemdm-config select").serialize();
    	posting = jQuery.post('../ajax/mqtt_test.php', data, 'json');
    	posting.done(function(data) {
    		var successIcon = '&nbsp;OK';
    		var failIcon = '&nbsp;KO';
    	    var feedback = $.parseJSON(data);
    	    if (feedback.status == "Test message sent") {
    	    	$('#pluginFlyvemdm-test-feedback').empty().append(successIcon);
    	    } else {
    	    	$('#pluginFlyvemdm-test-feedback').empty().append(failIcon);
    	    }
    	    
    	});
    	return false;
    });
};