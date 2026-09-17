<?php
/**
 * Meta OAuth Authentication Handler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_Meta_Auth {

	const OAUTH_DIALOG_URL = 'https://www.facebook.com';

	/**
	 * Required Meta Graph API Permissions
	 */
	const REQUIRED_SCOPES = [
		'pages_manage_posts',
		'pages_read_engagement',
		'pages_show_list',
		'instagram_content_publish',
		'instagram_basic',
	];

	/**
	 * Generate Meta OAuth Authorization URL
	 *
	 * @return string|WP_Error
	 */
	public static function get_authorization_url() {
		$app_id = WSP_Settings::get( 'meta_app_id' );
		if ( empty( $app_id ) ) {
			return new WP_Error( 'missing_app_id', __( 'Please enter your Meta App ID in settings first.', 'woocommerce-social-publisher' ) );
		}

		$api_version = WSP_Settings::get( 'meta_api_version', 'v21.0' );
		$redirect_uri = WSP_Settings::get_oauth_redirect_uri();

		// Generate secure state token
		$state = wp_generate_password( 32, false );
		set_transient( 'wsp_oauth_state_' . get_current_user_id(), $state, 15 * MINUTE_IN_SECONDS );

		$params = [
			'client_id'     => $app_id,
			'redirect_uri'  => $redirect_uri,
			'state'         => $state,
			'response_type' => 'code',
			'scope'         => implode( ',', self::REQUIRED_SCOPES ),
		];

		return sprintf( '%s/%s/dialog/oauth?%s', self::OAUTH_DIALOG_URL, $api_version, http_build_query( $params ) );
	}

	/**
	 * Handle OAuth callback from Meta
	 *
	 * @param string $code
	 * @param string $state
	 * @return array [ 'success' => bool, 'message' => string ]
	 */
	public static function handle_callback( $code, $state ) {
		// 1. Verify CSRF state token
		$expected_state = get_transient( 'wsp_oauth_state_' . get_current_user_id() );
		delete_transient( 'wsp_oauth_state_' . get_current_user_id() );

		if ( empty( $state ) || empty( $expected_state ) || ! hash_equals( $expected_state, $state ) ) {
			return [
				'success' => false,
				'message' => __( 'Security verification failed (invalid OAuth state). Please try connecting again.', 'woocommerce-social-publisher' ),
			];
		}

		$app_id     = WSP_Settings::get( 'meta_app_id' );
		$app_secret = WSP_Settings::get( 'meta_app_secret' );
		$redirect   = WSP_Settings::get_oauth_redirect_uri();

		if ( empty( $app_id ) || empty( $app_secret ) ) {
			return [
				'success' => false,
				'message' => __( 'Meta App ID or App Secret is missing from settings.', 'woocommerce-social-publisher' ),
			];
		}

		$api = new WSP_Meta_API();

		// 2. Exchange code for short-lived User Access Token
		$token_res = $api->get( 'oauth/access_token', [
			'client_id'     => $app_id,
			'client_secret' => $app_secret,
			'redirect_uri'  => $redirect,
			'code'          => $code,
		] );

		if ( ! $token_res['success'] || empty( $token_res['data']['access_token'] ) ) {
			return [
				'success' => false,
				'message' => sprintf( __( 'Could not exchange code for access token: %s', 'woocommerce-social-publisher' ), $token_res['error'] ),
			];
		}

		$short_token = $token_res['data']['access_token'];

		// 3. Exchange short-lived token for 60-day Long-Lived User Access Token
		$long_token_res = $api->get( 'oauth/access_token', [
			'grant_type'        => 'fb_exchange_token',
			'client_id'         => $app_id,
			'client_secret'     => $app_secret,
			'fb_exchange_token' => $short_token,
		] );

		$user_token = ( $long_token_res['success'] && ! empty( $long_token_res['data']['access_token'] ) )
			? $long_token_res['data']['access_token']
			: $short_token;

		WSP_Settings::set( 'user_access_token', $user_token );

		// 4. Discover Facebook Pages & connected Instagram accounts
		return self::fetch_and_sync_accounts( $user_token );
	}

	/**
	 * Fetch Facebook Pages & connected Instagram accounts for user token
	 *
	 * @param string|null $user_token
	 * @return array [ 'success' => bool, 'message' => string ]
	 */
	public static function fetch_and_sync_accounts( $user_token = null ) {
		if ( empty( $user_token ) ) {
			$user_token = WSP_Settings::get( 'user_access_token' );
		}

		if ( empty( $user_token ) ) {
			return [
				'success' => false,
				'message' => __( 'No active Meta User Access Token available. Please authenticate.', 'woocommerce-social-publisher' ),
			];
		}

		$api = new WSP_Meta_API();

		// Call /me/accounts with fields including connected Instagram business account
		$accounts_res = $api->get( 'me/accounts', [
			'fields' => 'id,name,access_token,category,instagram_business_account{id,username,name,profile_picture_url}',
			'limit'  => 100,
		], $user_token );

		if ( ! $accounts_res['success'] ) {
			return [
				'success' => false,
				'message' => sprintf( __( 'Failed to retrieve Facebook Pages: %s', 'woocommerce-social-publisher' ), $accounts_res['error'] ),
			];
		}

		$raw_pages = isset( $accounts_res['data']['data'] ) ? (array) $accounts_res['data']['data'] : [];

		if ( empty( $raw_pages ) ) {
			return [
				'success' => false,
				'message' => __( 'No Facebook Pages found for this Meta account. Make sure you are an administrator of at least one Facebook Page.', 'woocommerce-social-publisher' ),
			];
		}

		$available_pages = [];
		foreach ( $raw_pages as $page ) {
			$ig_data = null;
			if ( ! empty( $page['instagram_business_account'] ) && is_array( $page['instagram_business_account'] ) ) {
				$ig = $page['instagram_business_account'];
				$ig_data = [
					'id'                  => isset( $ig['id'] ) ? $ig['id'] : '',
					'username'            => isset( $ig['username'] ) ? $ig['username'] : '',
					'name'                => isset( $ig['name'] ) ? $ig['name'] : '',
					'profile_picture_url' => isset( $ig['profile_picture_url'] ) ? $ig['profile_picture_url'] : '',
				];
			}

			$available_pages[ $page['id'] ] = [
				'id'                   => $page['id'],
				'name'                 => $page['name'],
				'access_token'         => WSP_Security::encrypt( $page['access_token'] ),
				'category'             => isset( $page['category'] ) ? $page['category'] : '',
				'instagram'            => $ig_data,
			];
		}

		WSP_Settings::set( 'available_pages', $available_pages );

		// Auto-select first page if none currently selected
		$current_page_id = WSP_Settings::get( 'fb_page_id' );
		if ( empty( $current_page_id ) || ! isset( $available_pages[ $current_page_id ] ) ) {
			$first_page_id = key( $available_pages );
			self::select_active_page( $first_page_id );
		} else {
			// Refresh active page details
			self::select_active_page( $current_page_id );
		}

		return [
			'success' => true,
			'message' => sprintf( __( 'Successfully connected! Discovered %d Facebook Page(s).', 'woocommerce-social-publisher' ), count( $available_pages ) ),
		];
	}

	/**
	 * Select and activate a Facebook Page and sync its linked Instagram account
	 *
	 * @param string $page_id
	 * @return bool
	 */
	public static function select_active_page( $page_id ) {
		$available = WSP_Settings::get( 'available_pages', [] );
		if ( ! isset( $available[ $page_id ] ) ) {
			return false;
		}

		$page = $available[ $page_id ];
		$page_token = WSP_Security::decrypt( $page['access_token'] );

		WSP_Settings::set( 'fb_page_id', $page['id'] );
		WSP_Settings::set( 'fb_page_name', $page['name'] );
		WSP_Settings::set( 'fb_page_access_token', $page_token );

		// Sync Instagram details
		if ( ! empty( $page['instagram'] ) ) {
			$ig = $page['instagram'];
			WSP_Settings::set( 'ig_account_id', $ig['id'] );
			WSP_Settings::set( 'ig_username', $ig['username'] );
			WSP_Settings::set( 'ig_name', $ig['name'] );
			WSP_Settings::set( 'ig_profile_picture_url', $ig['profile_picture_url'] );
		} else {
			WSP_Settings::set( 'ig_account_id', '' );
			WSP_Settings::set( 'ig_username', '' );
			WSP_Settings::set( 'ig_name', '' );
			WSP_Settings::set( 'ig_profile_picture_url', '' );
		}

		return true;
	}

	/**
	 * Test active connection validity
	 *
	 * @return array [ 'success' => bool, 'facebook' => array, 'instagram' => array ]
	 */
	public static function test_connection() {
		$page_id    = WSP_Settings::get( 'fb_page_id' );
		$page_token = WSP_Settings::get( 'fb_page_access_token' );
		$ig_id      = WSP_Settings::get( 'ig_account_id' );

		$results = [
			'success'   => false,
			'facebook'  => [ 'connected' => false, 'message' => '' ],
			'instagram' => [ 'connected' => false, 'message' => '' ],
		];

		if ( empty( $page_id ) || empty( $page_token ) ) {
			$results['facebook']['message'] = __( 'No Facebook Page selected or Page Access Token is missing.', 'woocommerce-social-publisher' );
			return $results;
		}

		$api = new WSP_Meta_API();

		// Test Facebook Page endpoint
		$fb_res = $api->get( $page_id, [ 'fields' => 'id,name' ], $page_token );
		if ( $fb_res['success'] ) {
			$results['facebook']['connected'] = true;
			$results['facebook']['message']   = sprintf( __( 'Connected as "%s" (ID: %s)', 'woocommerce-social-publisher' ), $fb_res['data']['name'], $fb_res['data']['id'] );
		} else {
			$results['facebook']['message'] = $fb_res['error'];
		}

		// Test Instagram account endpoint if configured
		if ( ! empty( $ig_id ) ) {
			$ig_res = $api->get( $ig_id, [ 'fields' => 'id,username,name' ], $page_token );
			if ( $ig_res['success'] ) {
				$results['instagram']['connected'] = true;
				$results['instagram']['message']   = sprintf( __( 'Connected as @%s (ID: %s)', 'woocommerce-social-publisher' ), $ig_res['data']['username'], $ig_res['data']['id'] );
			} else {
				$results['instagram']['message'] = $ig_res['error'];
			}
		} else {
			$results['instagram']['message'] = __( 'No Instagram Business or Creator account is linked to this Facebook Page.', 'woocommerce-social-publisher' );
		}

		$results['success'] = $results['facebook']['connected'];

		return $results;
	}

	/**
	 * Disconnect active Meta connection
	 */
	public static function disconnect() {
		WSP_Settings::set( 'user_access_token', '' );
		WSP_Settings::set( 'fb_page_id', '' );
		WSP_Settings::set( 'fb_page_name', '' );
		WSP_Settings::set( 'fb_page_access_token', '' );
		WSP_Settings::set( 'available_pages', [] );
		WSP_Settings::set( 'ig_account_id', '' );
		WSP_Settings::set( 'ig_username', '' );
		WSP_Settings::set( 'ig_name', '' );
		WSP_Settings::set( 'ig_profile_picture_url', '' );
	}
}
