<?php

defined('ABSPATH') or exit;

class TransafeApikeyGenerator {

	private static $apikey_admin_perms = [
		'TOKEN_ADD',
		'TOKEN_LIST',
		'TOKEN_EDIT',
		'TOKEN_DEL',
		'TRAN_DETAIL',
		'TICKETREQUEST'
	];

	private static $apikey_trans_perms = [
		'SALE',
		'PREAUTH',
		'PREAUTHCOMPLETE',
		'CAPTURE',
		'REFUND',
		'REVERSAL',
		'VOID'
	];

	public static function handleRequest($request)
	{
		$params = $request->get_params();

		if (empty($params['username']) || empty($params['password'])) {
			return new WP_Error(
				'no_credentials', 
				'Username and password must be provided.',
				['status' => 400]
			);
		}

		$credentials = [
			'username' => $params['username'],
			'password' => $params['password']
		];
		if (!empty($params['mfa_code'])) {
			$credentials['mfa_code'] = $params['mfa_code'];
		}

		if (empty($params['profile_id'])) {

			$transafe_user_info = TransafeRequestHandler::sendApiRequest($credentials, 'GET', 'user/permissions');

			if ($transafe_user_info['code'] !== 'AUTH') {

				if ($transafe_user_info['msoft_code'] === 'ACCT_MFA_REQUIRED') {

					return new WP_REST_Response([
						'code' => 'enter_mfa_code',
						'message' => 'Please enter your multi-factor authentication code.'
					]);

				} elseif ($transafe_user_info['msoft_code'] === 'ACCT_MFA_GENERATE') {

					return new WP_Error(
						'mfa_generate', 
						'Multi-factor authentication must be set up before a key can be generated.',
						['status' => 403]
					);

				} elseif ($transafe_user_info['msoft_code'] === 'ACCT_PASSEXPIRED') {

					return new WP_Error(
						'password_expired', 
						'Your password has expired. It must be changed before a key can be generated.',
						['status' => 403]
					);

				} else {

					return new WP_Error(
						'bad_credentials', 
						'Credentials are incorrect.',
						['status' => 401]
					);

				}

			}

			$user_can_list_profiles = false;

			if (isset($transafe_user_info['sys_perms'])) {

				$user_sys_perms = explode('|', $transafe_user_info['sys_perms']);

				if (in_array('PROFILE_LIST', $user_sys_perms)) {
					$user_can_list_profiles = true;
				}

			}

			if (isset($transafe_user_info['profile_id'])) {

				$profile_id = $transafe_user_info['profile_id'];

			} elseif ($user_can_list_profiles) {

				$profiles = self::getProfileList($credentials);

				return new WP_REST_Response([
					'code' => 'select_profile',
					'message' => 'Profile must be selected.',
					'data' => ['profiles' => $profiles]
				]);

			} else {

				return new WP_Error(
					'no_profile', 
					'User has no default profile, and cannot list profiles.',
					['status' => 403]
				);

			}

		} else {

			$profile_id = $params['profile_id'];

		}

		$apikey_data = self::generateKey($credentials, $profile_id);

		if ($apikey_data['code'] !== 'AUTH') {
			return new WP_Error(
				'apikey_failure', 
				$apikey_data['verbiage'],
				['status' => 400]
			);
		}

		return new WP_REST_Response([
			'code' => 'success',
			'data' => $apikey_data
		]);
	}

	private static function getProfileList($credentials)
	{
		$profile_data = TransafeRequestHandler::sendApiRequest($credentials, 'GET', 'boarding/profile');

		$profile_list = [];

		foreach ($profile_data['report'] as $profile) {
			$profile_list_item = [
				'id' => $profile['id'],
				'display_name' => $profile['profile_name']
			];
			if (isset($profile['name'])) {
				$profile_list_item['display_name'] .= ' (' . $profile['name'] . ')';
			}
			$profile_list[] = $profile_list_item;
		}

		return $profile_list;
	}

	private static function generateKey($credentials, $profile_id = null)
	{
		$apikey_options = [
			'type' => 'profile',
			'name' => 'WooCommerce Key ' . time(),
			'admin_perms' => implode('|', self::$apikey_admin_perms),
			'trans_perms' => implode('|', self::$apikey_trans_perms),
			'expire_sec' => 'infinite',
			'profile_id' => $profile_id
		];

		$apikey_data = TransafeRequestHandler::sendApiRequest(
			$credentials, 
			'POST', 
			'apikey', 
			$apikey_options
		);

		return $apikey_data;
	}

}