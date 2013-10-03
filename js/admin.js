jQuery(function($) {
	$('.mailchimp-login').click(function(e) {
		e.preventDefault();

		var login_window = window.open($(this).attr('href'), "ServiceAssociate", 'width=500,height=550'),
			auth_poll = null;

		auth_poll = setInterval(function() {
			if (login_window.closed) {
				clearInterval(auth_poll);
				window.location.reload();
			}
		}, 100);
	});
});
