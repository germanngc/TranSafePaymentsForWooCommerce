jQuery(function($) {

	var body = $('body');
	var iframe_id = 'wc-transafe-iframe';
	var paymentFrame = null;
	var checkout_form = $('form.checkout');
	var submit_message_sent = false;
	var payment_ticket_response_received = false;
	var payment_ticket;
	var iframeElement;
	var payment_form_host;
	var transafe_payment_is_selected;
	var transafe_payment_radio_input = $('#payment_method_transafe');


	function handlePaymentTicketResponse(response) {

		if (response.code === 'AUTH' && typeof response.ticket !== 'undefined') {
			payment_ticket = response.ticket;
			checkout_form.append(
				'<input type="hidden" name="transafe_payment_ticket" value="' + payment_ticket + '" />'
			);
		} else {
			var error_verbiage;
			if (typeof response.verbiage !== 'undefined') {
				error_verbiage = response.verbiage;
			} else {
				error_verbiage = '';
			}
			checkout_form.append(
				'<input type="hidden" name="transafe_payment_error" value="' + error_verbiage + '" />'
			);
		}
		payment_ticket_response_received = true;
		checkout_form.submit();
	}

	function requestPaymentFrameIfNeeded(iframe_id, payment_form_host) {

		if (paymentFrame !== null) {
			return;
		}

		paymentFrame = new PaymentFrame(
			iframe_id,
			payment_form_host
		);

		paymentFrame.setPaymentSubmittedCallback(handlePaymentTicketResponse);
		
		paymentFrame.request();

	}

	function determineWhetherTransafePaymentIsSelected() {
		var payment_method = checkout_form.find('input[name="payment_method"]:checked').val();
		if (payment_method === 'transafe') {
			transafe_payment_is_selected = true;
		} else {
			transafe_payment_is_selected = false;
		}
	}

	body.on('updated_checkout', function() {

		iframeElement = document.getElementById(iframe_id);
		payment_form_host = iframeElement.dataset.paymentFormHost;

		paymentFrame = null;

		requestPaymentFrameIfNeeded(iframe_id, payment_form_host);

	});

	body.on('payment_method_selected', function() {

		if (typeof iframeElement !== 'undefined' && typeof payment_form_host !== 'undefined') {
			iframeElement.contentWindow.postMessage(
				JSON.stringify({ type: "getHeight" }), 
				payment_form_host
			);
		}

		determineWhetherTransafePaymentIsSelected();
	});

	checkout_form.on('checkout_place_order', function() {

		if (!transafe_payment_is_selected) {
			return true;
		}

		if (payment_ticket_response_received) {
			return true;
		}

		if (!submit_message_sent) {

			iframeElement.contentWindow.postMessage(
				JSON.stringify({ type: "submitPaymentData" }), 
				payment_form_host
			);

			submit_message_sent = true;

		}

		return false;

	});

	window.addEventListener("beforeunload", function(e) {
		iframeElement.parentElement.removeChild(iframeElement);
	});

	determineWhetherTransafePaymentIsSelected();

});
