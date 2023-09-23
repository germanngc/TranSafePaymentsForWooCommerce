<?php

defined('ABSPATH') or exit;

return [

	'enabled' => [
		'title'   => 'Enable/Disable',
		'type'    => 'checkbox',
		'label'   => 'Enable TranSafe Payment',
		'default' => 'yes'
	],
	
	'title' => [
		'title'       => 'Title',
		'type'        => 'text',
		'description' => 'This controls the title for the payment method the customer sees during checkout.',
		'default'     => 'TranSafe Payment',
		'desc_tip'    => true,
	],
	
	'description' => [
		'title'       => 'Description',
		'type'        => 'textarea',
		'description' => 'Payment method description that the customer will see on your checkout.',
		'default'     => 'Enter credit card details to complete this order.',
		'desc_tip'    => true,
	],

	'title_payment_processing' => [
		'title'   => 'Payment Processing',
		'type'    => 'title',
	],

	'server' => [
		'title'       => 'Payment Server',
		'type'        => 'select',
		'description' => 'This is the payment server that will generate the payment form and process the payment during the checkout process.',
		'default'     => 'test',
		'desc_tip'    => true,
		'options'     => [
			'test' => 'TranSafe Test Server',
			'live' => 'TranSafe Live/Production Server',
			'custom' => 'Custom'
		]
	],

	'host' => [
		'title'       => 'Payment Server Hostname',
		'type'        => 'text',
		'description' => 'If using a custom payment server, enter the hostname (including https://) here.',
		'default'     => '',
		'desc_tip'    => true,
	],

	'port' => [
		'title'       => 'Payment Server Port',
		'type'        => 'text',
		'description' => 'If using a custom payment server, enter the port number here. Generally, 8665 and 443 are valid port numbers.',
		'default'     => '8665',
		'desc_tip'    => true,
	],

	'apikey_id' => [
		'title'       => 'API Key ID',
		'type'        => 'text',
		'description' => 'Enter the API key ID that will be used to authenticate with the payment server.',
		'default'     => '',
		'desc_tip'    => true,
	],

	'apikey_secret_entry' => [
		'title'       => 'API Key Secret',
		'type'        => 'password',
		'description' => 'Enter the API key secret that will be used to authenticate with the payment server.',
		'default'     => '',
		'desc_tip'    => true,
	],

	'capture' => [
		'title'       => 'Payment Action',
		'description' => 'If this is set to "Authorize Only", initial placement of an order will only authorize (not capture) the transaction. Otherwise, the transaction will be authorized and captured.',
		'type'        => 'select',
		'desc_tip'    => true,
		'default'     => 'yes',
		'options'     => [
			'yes' => 'Authorize and Capture',
			'no' => 'Authorize Only',
		]
	],

	'title_payment_form' => [
		'title'   => 'Payment Form',
		'type'    => 'title',
	],

	'expdate_format' => [
		'title'       => 'Expiration Date Format',
		'description' => 'Format of the expiration date input on the payment form',
		'type'        => 'select',
		'desc_tip'    => true,
		'default'     => 'single-text',
		'options'     => [
			'single-text' => 'Freeform text entry (with auto MM/YY formatting)',
			'separate-selects' => 'Two dropdown select elements, one for month and one for year',
			'coupled-selects' => 'Two dropdown select elements, one for month and one for year, inside a container element (for styling purposes)',
		]
	],

	'auto_reload' => [
		'title'       => 'Auto-Reload',
		'label'       =>	'Automatically reload the checkout page every 15 minutes to avoid payment form timing out',
		'type'        => 'checkbox',
		'default'     => 'yes',
	],

	'autocomplete' => [
		'title'       => 'Autocomplete',
		'label'       =>	'Enable browser autocomplete for payment form fields',
		'type'        => 'checkbox',
		'default'     => 'no',
	],

	'css_url' => [
		'title'       => 'CSS Path',
		'type'        => 'text',
		'description' => 'Path to CSS file that will be used to style the payment form. Must be hosted on same domain as WooCommerce store. If empty, no custom CSS will be applied.',
		'default'     => '',
		'desc_tip'    => true,
	],

	'declined_payment_notice' => [
		'title'       => 'Declined Payment Notice',
		'type'        => 'textarea',
		'description' => 'Notice that the customer will see if payment is declined.',
		'default'     => 'Unable to process payment. Please try another form of payment or contact your bank or card issuer.',
		'desc_tip'    => true,
	],

	'payment_error_notice' => [
		'title'       => 'Payment Error Notice',
		'type'        => 'textarea',
		'description' => 'Generic customer-facing error message, shown if payment fails and there is not a more specific error message available.',
		'default'     => 'Unable to process payment. Please contact support.',
		'desc_tip'    => true,
	]

];
