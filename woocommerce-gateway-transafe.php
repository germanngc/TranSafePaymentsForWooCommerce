<?php
/**
 * Plugin Name:       TranSafe Payments for WooCommerce
 * Plugin URI:        https://www.transafe.com
 * Description:       Accept credit card payments using TranSafe Gateway
 * Version:           2.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.0
 * Author:            Monetra Technologies, LLC
 * Author URI:        https://www.monetra.com
 * License:           MIT
 */

defined('ABSPATH') or exit;

require 'includes/dependencies/autoload.php';
require 'includes/class.transafe-request-handler.php';
require 'includes/class.transafe-apikey-generator.php';

use Defuse\Crypto\Crypto;

function wc_transafe_missing_wc_notice() {
	echo 
		'<div class="error"><p><strong>TranSafe requires the WooCommerce plugin to be installed and active.' .  
		'<br />You can install or activate WooCommerce from the Plugins section here in your Wordpress admin interface.</strong></p></div>';
}

if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	add_action('admin_notices', 'wc_transafe_missing_wc_notice');
	return;
}

function wc_transafe_add_to_gateways($gateways) {
	$gateways[] = 'WC_Transafe';
	return $gateways;
}

add_action('plugins_loaded', 'wc_transafe_init');

add_filter('woocommerce_payment_gateways', 'wc_transafe_add_to_gateways');

add_action('rest_api_init', 'wc_transafe_init_rest_api');

function wc_transafe_init_rest_api() {
	register_rest_route('woocommerce_transafe/v1', '/generate_api_key', [
		'methods' => 'POST',
		'permissions_callback' => 'wc_transafe_generate_api_key_perms_check',
		'callback' => ['TransafeApikeyGenerator', 'handleRequest']
	]);
}

function wc_transafe_generate_api_key_perms_check() {
	if (current_user_can('administrator')) {
		return true;
	}
	return new WP_Error(
		'forbidden', 
		'You do not have permission to access this resource.',
		['status' => 401]
	);
}

function wc_transafe_plugin_action_links($links) {
	$plugin_links = [
		'<a href="admin.php?page=wc-settings&tab=checkout&section=transafe">Settings</a>'
	];
	return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_transafe_plugin_action_links');

function wc_transafe_init() {

	class WC_Transafe extends WC_Payment_Gateway {

		const ERROR_LOG_PREFIX = 'TRANSAFE WOOCOMMERCE PLUGIN: ';
		const LEGACY_PASSWORD_PREFIX = '-legacy-pw|';

		public function __construct() {

			$this->id                 = 'transafe';
			$this->has_fields         = true;
			$this->method_title       = 'TranSafe Payments';
			$this->method_description = 'Accept credit card payments using TranSafe Gateway.';

			$current_password = $this->get_option('password');

			if (!empty($current_password)) {
				if (strpos($current_password, self::LEGACY_PASSWORD_PREFIX) !== 0) {
					$encrypted_password = self::encryptValueForStorage($current_password);
					$this->update_option('password', self::LEGACY_PASSWORD_PREFIX . $encrypted_password);
				}
			}

			add_filter('wc_transafe_form_fields', function($fields) {

				$current_apikey_secret = $this->get_option('apikey_secret');

				if (!empty($current_apikey_secret)) {
					$fields['apikey_secret_entry']['placeholder'] = 'Saved';
				}
				return $fields;

			});

			$this->init_form_fields();
			$this->init_settings();

			$this->title        = $this->get_option( 'title' );
			$this->description  = $this->get_option( 'description' );

			$this->supports = ['refunds'];

			require_once dirname(__FILE__) . '/includes/class.transafe-payment-frame.php';

			add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
			add_action('wp_enqueue_scripts', [$this, 'payment_scripts']);
			add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);

			add_filter('pre_update_option_woocommerce_transafe_settings', function($new_value, $old_value) {

				if (!empty($new_value['apikey_secret_entry'])) {

					$encrypted_apikey_secret = self::encryptValueForStorage($new_value['apikey_secret_entry']);
					$new_value['apikey_secret'] = $encrypted_apikey_secret;

					unset($new_value['password']);
					unset($new_value['user']);

				} elseif (isset($old_value['password'])
				&& strpos($old_value['password'], self::LEGACY_PASSWORD_PREFIX) !== 0) {

					$encrypted_password = self::encryptValueForStorage($old_value['password']);
					$new_value['password'] = self::LEGACY_PASSWORD_PREFIX . $encrypted_password;

				}

				unset($new_value['apikey_secret_entry']);

				return $new_value;

			}, 10, 2);

		}

		public function init_form_fields() {

			$fields = require(dirname(__FILE__) . '/includes/admin/settings.php');

			$this->form_fields = apply_filters('wc_transafe_form_fields', $fields);

		}

		public function payment_fields() {

			$config = [
				'css-url' => $this->get_option('css_url'),
				'include-cardholdername' => 'no',
				'include-street' => 'no',
				'include-zip' => 'no',
				'expdate-format' => $this->get_option('expdate_format'),
				'auto-reload' => $this->get_option('auto_reload'),
				'autocomplete' => $this->get_option('autocomplete'),
				'include-submit-button' => 'no',
				'payment-server-origin' => TransafeRequestHandler::paymentServerOrigin()
			];

			$config = array_merge($config, $this->getStoredCredentials());

			$paymentframe = new TransafePaymentFrame($config);

			echo $paymentframe->getHtml();
		}

		public function admin_scripts() {
			wp_register_style('admin', plugins_url('assets/css/admin.css', __FILE__ ), [], false);
			wp_enqueue_style('admin');

			wp_register_script('admin', plugins_url('assets/js/admin.js', __FILE__ ), [], false, true);
			wp_enqueue_script('admin');
		}

		public function payment_scripts() {

			wp_register_style('checkout', plugins_url('assets/css/checkout.css', __FILE__ ), [], false);
			wp_enqueue_style('checkout');

			$server = $this->get_option('server');

			$paymentframe_script_domain = TransafeRequestHandler::paymentServerOrigin();

			wp_register_script('paymentframe', $paymentframe_script_domain . '/PaymentFrame/PaymentFrame.js', [], false, true);
			wp_register_script('checkout', plugins_url('assets/js/checkout.js', __FILE__ ), [], false, true);
			
			wp_enqueue_script('paymentframe');
			wp_enqueue_script('checkout');
		}

		public function process_payment($order_id) {
			global $woocommerce;

			$order = new WC_Order($order_id);

			if (empty($_POST['transafe_payment_ticket'])) {

				if (!empty($_POST['transafe_payment_error'])) {
					$error_message = sanitize_text_field($_POST['transafe_payment_error']);
				} else {
					$error_message = $this->get_option('payment_error_notice');
				}
				wc_add_notice($error_message, 'error');
				return;

			}

			$ticket = sanitize_text_field($_POST['transafe_payment_ticket']);

			$payment_response = $this->sendPaymentToPaymentServer($ticket, $order);

			if ($payment_response['code'] === 'AUTH') {
			
				$order->payment_complete($payment_response['ttid']);
				
				wc_reduce_stock_levels($order);
				
				$woocommerce->cart->empty_cart();
				
				return [
					'result' 	=> 'success',
					'redirect'	=> $this->get_return_url($order)
				];

			} else {

				wc_add_notice($this->get_option('declined_payment_notice'), 'error');

				return;

			}
		}

		public function process_refund($order_id, $amount = null, $reason = '') {

			$order = new WC_Order($order_id);

			$refund_response = $this->sendRefundToPaymentServer($amount, $order);

			if (!empty($refund_response) && $refund_response['code'] === 'AUTH') {

				return true;

			} else {

				return false;

			}

		}

		private function sendPaymentToPaymentServer($payment_ticket, $order)
		{
			$path = 'transaction/purchase';

			$cardholdername = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
			$street = $order->get_billing_address_1() . ' ' . $order->get_billing_address_2();
			$zip = $order->get_billing_postcode();
			$amount = $order->get_total();
			$tax = $order->get_total_tax();
			$ordernum = $order->get_order_number();
			$capture = $this->get_option('capture');

			$transaction_data = [
				'account_data' => [
					'cardshieldticket' => $payment_ticket,
					'cardholdername' => $cardholdername
				],
				'verification' => [
					'street' => $street,
					'zip' => $zip
				],
				'money' => [
					'amount' => $amount,
					'tax' => $tax
				],
				'order' => [
					'ordernum' => $ordernum
				],
				'processing_options' => [
					'capture' => $capture
				]
			];

			$credentials = $this->getStoredCredentials();
			$transaction_response = TransafeRequestHandler::sendApiRequest($credentials, 'POST', $path, $transaction_data);

			return $transaction_response;
		}

		private function sendRefundToPaymentServer($refund_amount, $order) {

			$ttid = $order->get_transaction_id();
			$ordernum = $order->get_order_number();
			$order_total = $order->get_total();

			$credentials = $this->getStoredCredentials();
			$transaction_details = TransafeRequestHandler::sendApiRequest($credentials, 'GET', "transaction/$ttid");

			if ($transaction_details['code'] !== 'AUTH') {

				error_log(
					self::ERROR_LOG_PREFIX . "Unable to retrieve prior transaction details from payment server. Response verbiage: " .
					$transaction_details['verbiage']
				);
				return null;

			}
			
			$status_flags = explode('|', $transaction_details['txnstatus']);

			if (in_array('COMPLETE', $status_flags)) {
				$method = 'POST';
				$path = "transaction/$ttid/refund";
				$data = [
					'money' => [
						'amount' => $refund_amount
					],
					'order' => [
						'ordernum' => $ordernum
					]
				];
			} else {

				/* Do not allow partial void/reversal */
				if ($refund_amount < $order_total) {
					error_log(self::ERROR_LOG_PREFIX . 'Partial void of an unsettled transaction is not allowed.');
					return null;
				}

				$method = 'DELETE';
				$path = "transaction/$ttid";
				$data = null;
			}

			$refund_response = TransafeRequestHandler::sendApiRequest($credentials, $method, $path, $data);

			if ($refund_response['code'] !== 'AUTH') {
				error_log(
					self::ERROR_LOG_PREFIX . 'Unable to process refund through payment server. Response verbiage: ' .
					$refund_response['verbiage']
				);
			}

			return $refund_response;
		}

		private function getStoredCredentials()
		{
			$apikey_id = $this->get_option('apikey_id');
			$encrypted_apikey_secret = $this->get_option('apikey_secret');
			if (!empty($apikey_id) && !empty($encrypted_apikey_secret)) {
				return [
					'apikey_id' => $apikey_id,
					'apikey_secret' => self::decryptStoredValue($encrypted_apikey_secret)
				];
			}

			$username = $this->get_option('user');
			$encrypted_password = str_replace(self::LEGACY_PASSWORD_PREFIX, '', $this->get_option('password'));
			if (!empty($username) && !empty($encrypted_password)) {
				return [
					'username' => $username,
					'password' => self::decryptStoredValue($encrypted_password)
				];
			}

			error_log(self::ERROR_LOG_PREFIX . 'No stored credentials found');
			return [];
		}

		private static function encryptValueForStorage($value)
		{
			try {
				$encrypted_value = Crypto::encryptWithPassword($value, \SECURE_AUTH_KEY);
				return $encrypted_value;
			} catch (\Exception $e) {
				error_log(self::ERROR_LOG_PREFIX . 'Unable to encrypt value');
				return "";
			}
		}

		private static function decryptStoredValue($encrypted_value)
		{
			try {
				$value = Crypto::decryptWithPassword($encrypted_value, \SECURE_AUTH_KEY);
				return $value;
			} catch (\Exception $e) {
				error_log(self::ERROR_LOG_PREFIX . 'Unable to decrypt value');
				return "";
			}
		}

	}

}
