/**
 * 
 */
window.onload = function() {
 
    $('#pluginStorkmdm-mqtt-test').on('click', function() {
    	data = $("#pluginStorkmdm-config input,#pluginStorkmdm-config select").serialize();
    	posting = jQuery.post('../ajax/mqtt_test.php', data, 'json');
    	posting.done(function(data) {
    		var successIcon = '&nbsp;OK';
    		var failIcon = '&nbsp;KO';
    	    var feedback = $.parseJSON(data);
    	    if (feedback.status == "Test message sent") {
    	    	$('#pluginStorkmdm-test-feedback').empty().append(successIcon);
    	    } else {
    	    	$('#pluginStorkmdm-test-feedback').empty().append(failIcon);
    	    }
    	    
    	});
    	return false;
    });
};