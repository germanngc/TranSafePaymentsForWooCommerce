<?php

defined('ABSPATH') or exit;

class TransafePaymentFrame {

	private $config;

	public function __construct($config)
	{
		$this->config = $config;
	}

	public function getHtml()
	{
		$iframe_attributes = $this->getIframeAttributes();

		$iframe_attribute_string_parts = [];
		foreach ($iframe_attributes as $key => $value) {
			$iframe_attribute_string_parts[] = $key . '="' . $value . '"';
		}
		$iframe_attribute_string = implode(' ', $iframe_attribute_string_parts);

		$html = [];

		$html[] = '<div id="wc-transafe-iframe-container">';
		$html[] = '<iframe ' . $iframe_attribute_string . '></iframe>';
		$html[] = '</div>';

		return implode('', $html);
	}

	private function getIframeAttributes()
	{
		$hmac_data = $this->generateHmacData();

		$iframe_name = 'wc-transafe-iframe-' . uniqid();

		$iframe_attributes = [
			'id' => 'wc-transafe-iframe',
			'name' => $iframe_name,
			'data-payment-form-host' => $this->config['payment-server-origin'],
			'data-hmac-hmacsha256' => $hmac_data['hmac']
		];

		foreach ($hmac_data['fields'] as $key => $value) {
			$iframe_attributes['data-hmac-' . $key] = $value;
		}

		return $iframe_attributes;
	}

	private function generateHmacData()
	{
		$host_domain = 'https://' . $_SERVER['HTTP_HOST'];

		$apikey_id = $this->config['apikey_id'];
		$apikey_secret = $this->config['apikey_secret'];

		if (empty($apikey_id) || empty($apikey_secret)) {

			$username = $this->config['username'];
			$using_apikey = false;
			$hmac_key = $this->config['password'];

		} else {

			$using_apikey = true;
			$hmac_key = $this->config['apikey_secret'];

		}

		$hmac_fields = [];

		$hmac_fields["timestamp"] = time();

		$hmac_fields["domain"] = $host_domain;

		$hmac_fields["sequence"] = bin2hex(random_bytes(16));

		if ($using_apikey) {
			$hmac_fields['auth_apikey_id'] = $apikey_id;
		} else {
			$hmac_fields['username'] = $username;
		}

		if (!empty($this->config['css-url'])) {
			$hmac_fields["css-url"] = $host_domain . "/" . $this->config['css-url'];
		}

		$hmac_fields["include-cardholdername"] = $this->config['include-cardholdername'];
		$hmac_fields["include-street"] = $this->config['include-street'];
		$hmac_fields["include-zip"] = $this->config['include-zip'];
		$hmac_fields["expdate-format"] = $this->config['expdate-format'];

		$hmac_fields["auto-reload"] = $this->config['auto-reload'];
		$hmac_fields["autocomplete"] = $this->config['autocomplete'];

		$hmac_fields["include-submit-button"] = $this->config['include-submit-button'];

		$data_to_hash = implode("", $hmac_fields);

		$hmac = hash_hmac('sha256', $data_to_hash, $hmac_key);

		return [
			'hmac' => $hmac,
			'fields' => $hmac_fields
		];
	}

}
