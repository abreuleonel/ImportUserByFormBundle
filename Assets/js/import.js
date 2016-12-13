Mautic.loadAjaxEvent = function() { 
	
};

function getAjaxData() {
	var query = "action=plugin:ImportUserByForm:getJobData";

	mQuery.ajax({
        url: mauticAjaxUrl,
        type: "POST",
        data: query,
        success: function (response) {
        	
        	if(response.process_started) {
        		mQuery('#process_queue').hide();
        		mQuery('#ajax_result').show();
        		mQuery('#ajax_result h3').text('The process is in progress');
        		mQuery('#ajax_result #csv_name').text(response.message.file);
        		mQuery('#ajax_result #form_id').text(response.message.form_id);
        		mQuery('#ajax_result #csv_total_rows').text(response.message.total_rows);
        		mQuery('#ajax_result #imported_rows').text(response.message.total_rows_processed);
        	} else {
        		mQuery('#ajax_result').hide();
        		mQuery('#ajax_result #csv_name').text('');
        		mQuery('#ajax_result #csv_total_rows').text('');
        		mQuery('#ajax_result #imported_rows').text('');
        	}
        },
        error: function (request, textStatus, errorThrown) {
            Mautic.processAjaxError(request, textStatus, errorThrown);
        },
    });
	
}

window.setInterval('getAjaxData()', 2000);