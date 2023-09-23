jQuery(function($) {

	var server_select = $('#woocommerce_transafe_server');
	var host_input_container = $('#woocommerce_transafe_host').closest('tr');
	var port_input_container = $('#woocommerce_transafe_port').closest('tr');
	var form_table = server_select.closest('.form-table');

	var generate_apikey_modal = $('<dialog id="transafe-generate-apikey-modal"></dialog>');
	var generate_apikey_button = $('<button id="transafe-generate-apikey-button" type="button">Generate API Key</button>');
	var generate_apikey_form = $('<form id="transafe-generate-apikey-form"></form>');
	var generate_apikey_modal_close = $('<a id="transafe-generate-apikey-close-modal"></a>');
	var generate_apikey_alert_container = $('<p id="transafe-generate-apikey-alert"></p>');

	var apikey_id_input = $('#woocommerce_transafe_apikey_id');
	var apikey_secret_input = $('#woocommerce_transafe_apikey_secret_entry');

	generate_apikey_form.append(
		'<label>TranSafe Username' +
			'<input type="text" id="transafe-username-input" />' + 
		'</label>'
	);

	generate_apikey_form.append(
		'<label>TranSafe Password' +
			'<input type="password" id="transafe-password-input" />' + 
		'</label>'
	);

	generate_apikey_form.append(
		'<div id="transafe-mfa-code-container" class="hidden">' +
			'<p class="transafe-generate-apikey-next-step">Please enter your multi-factor authentication code.</p>' +
			'<label>TranSafe MFA Code' +
				'<input disabled type="text" id="transafe-mfa-code-input" />' + 
			'</label>' + 
		'</div>'
	);

	generate_apikey_form.append(
		'<div id="transafe-profile-select-container" class="hidden">' +
			'<p class="transafe-generate-apikey-next-step">Please select a profile.</p>' +
			'<label>TranSafe Profile' +
				'<select disabled id="transafe-profile-select">' + 
					'<option value="">- Select a profile -</option>' +
				'</select>' +
			'</label>' + 
		'</div>'
	);

	generate_apikey_form.append('<button type="submit">Submit</button>');
	
	generate_apikey_modal.append(generate_apikey_modal_close);
	generate_apikey_modal.append(generate_apikey_alert_container);
	generate_apikey_modal.append(generate_apikey_form);

	form_table.before(generate_apikey_button);
	$('body').append(generate_apikey_modal);

	generate_apikey_modal_close.on('click', function() {
		generate_apikey_modal.get(0).close();
	});

	server_select.change(function() {

		if (server_select.val() == 'custom') {
			host_input_container.show();
			port_input_container.show();
		} else {
			host_input_container.hide();
			port_input_container.hide();
		}

	});

	generate_apikey_button.on('click', function() {
		generate_apikey_modal.get(0).showModal();
	});

	generate_apikey_form.on('submit', function(e) {
		e.preventDefault();

		var generate_apikey_form_submit = generate_apikey_form.find('button[type="submit"]');
		var profile_select = $('#transafe-profile-select');
		var mfa_code_input = $('#transafe-mfa-code-input');

		generate_apikey_form_submit.prop('disabled', true).text('Loading...');

		var data = {
			username: $('#transafe-username-input').val().replace(':', '|'),
			password: $('#transafe-password-input').val(),
			mfa_code: mfa_code_input.val(),
			profile_id: profile_select.val()
		};

		generate_apikey_alert_container.empty();

		$.ajax({
			type: 'POST',
			url: location.protocol + '//' + location.hostname + '/wp-json/woocommerce_transafe/v1/generate_api_key',
			data: data, 
			showLoader: true,
			success: function(response) {

				generate_apikey_form_submit.prop('disabled', false).text('Submit');

				if (response.code === 'success') {

					apikey_id_input.val(response.data.apikey_id);
					apikey_secret_input.val(response.data.apikey_secret);
					generate_apikey_modal.get(0).close();

				} else if (response.code === 'select_profile') {

					profile_select.find('option:not([value=""])').remove();

					response.data.profiles.forEach(function(profile) {
						var option = $('<option></option>');
						option.attr('value', profile.id);
						option.text(profile.display_name);
						profile_select.append(option);
					});

					profile_select.prop('disabled', false);
					$('#transafe-profile-select-container').removeClass('hidden');

				} else if (response.code === 'enter_mfa_code') {

					mfa_code_input.prop('disabled', false);
					$('#transafe-mfa-code-container').removeClass('hidden');

				} else {

					generate_apikey_alert_container.text(response.message);

				}
			},
			error: function(jqXHR) {
				var response;
				var message;
				if (jqXHR.responseText) {

					response = JSON.parse(jqXHR.responseText);
					message = response.message;

					generate_apikey_alert_container.text(response.message);

				}
				generate_apikey_form_submit.prop('disabled', false).text('Submit');
			},
			dataType: 'JSON'
		});
	});

	server_select.change();

});
