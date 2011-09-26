/* Form submission functions for the MailChimp Widget */
;(function($){
	$(function($) {
		// Change our submit type from HTML (default) to JS
		$('#mc_submit_type').val('js');
		
		// Attach our form submitter action
		$('#mc_signup_form').ajaxForm({
			url: mailchimpSF.ajax_url, 
			type: 'POST', 
			dataType: 'text',
			beforeSubmit: mc_beforeForm,
			success: mc_success
		});
	});
	
	function mc_beforeForm(){
		// Disable the submit button
		$('#mc_signup_submit').attr("disabled","disabled");
	}
	function mc_success(data){
		// Re-enable the submit button
		$('#mc_signup_submit').removeAttr("disabled");
		
		// Put the response in the message div
		$('#mc_message').html(data);
		
		// See if we're successful, if so, wipe the fields
		var reg = new RegExp("class='mc_success_msg'", 'i');
		if (reg.test(data)){
			$('#mc_signup_form').each(function(){
				this.reset();
			});
			$('#mc_submit_type').val('js');
		}
		$.scrollTo('#mc_signup', {offset: {top: -28}});
	}
})(jQuery);
