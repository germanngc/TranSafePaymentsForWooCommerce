<?php

defined('ABSPATH') or exit;

class TransafeRequestHandler {

	const TEST_SERVER_URL = 'https://test.transafe.com';
	const TEST_SERVER_PORT = '443';

	const LIVE_SERVER_URL = 'https://post.live.transafe.com';
	const LIVE_SERVER_PORT = '443';

	public static function paymentServerOrigin()
	{
		$options = get_option('woocommerce_transafe_settings');

		$server = $options['server'];

		if ($server === 'test') {

			return self::TEST_SERVER_URL . ':' . self::TEST_SERVER_PORT;
		
		} elseif ($server === 'live') {
			
			return self::LIVE_SERVER_URL . ':' . self::LIVE_SERVER_PORT;
		
		} else {

			$custom_host = $options['host'];

			if (strpos($custom_host, 'https://') !== 0) {
				if (strpos($custom_host, 'http://') === 0) {
					$custom_host = str_replace('http://', 'https://', $custom_host);
				} else {
					$custom_host = 'https://' . $custom_host;
				}
			}
			
			return $custom_host . ':' . $options['port'];
			
		}
	}

	public static function sendApiRequest($credentials, $method, $path, $data = null)
	{
		$url = self::paymentServerOrigin() . '/api/v2/' . $path;
		
		if (empty($credentials['apikey_id']) || empty($credentials['apikey_secret'])) {

			$username = str_replace(':', '|', $credentials['username']);

			$headers = [
				'Authorization' => 'Basic ' . base64_encode($username . ':' . $credentials['password'])
			];
			if (isset($credentials['mfa_code']) && trim($credentials['mfa_code']) !== "") {
				$headers['X-MFA-CODE'] = $credentials['mfa_code'];
			}

		} else {

			$headers = [
				'X-API-KEY-ID' => $credentials['apikey_id'],
				'X-API-KEY' => $credentials['apikey_secret']
			];

		}

		if ($method === 'GET') {

			if (!empty($data)) {
				$url .= '?' . http_build_query($data);
			}
			$response = wp_remote_get($url, [
				'headers' => $headers
			]);

		} elseif ($method === 'POST') {

			$request_body = json_encode($data);
			$headers["Content-Type"] = "application/json";
			$headers["Content-Length"] = strlen($request_body);

			$response = wp_remote_post($url, [
				'headers' => $headers,
				'body' => $request_body
			]);

		} else {
			$params = [
				'method' => $method,
				'headers' => $headers
			];
			if (!empty($data)) {
				$params['body'] = json_encode($data);
			}

			$response = wp_remote_request($url, $params);
		}

		$response_body = wp_remote_retrieve_body($response);

		return json_decode($response_body, true);
	}

}