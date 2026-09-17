<?php
/**
 * Settings Handler for WooCommerce Social Publisher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_Settings {

	const OPTION_KEY = 'wsp_settings';

	/**
	 * Get default settings
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return [
			// General
			'default_platform'           => 'both', // facebook, instagram, both
			'default_image_source'       => 'featured', // featured, all
			'currency_display'           => 'symbol', // symbol, code, formatted
			'duplicate_behavior'         => 'warn', // warn, block, allow
			'delete_data_on_uninstall'   => 0,

			// Post Content Fields (enabled/disabled)
			'field_title'                => 1,
			'field_description'          => 0,
			'field_short_description'    => 0,
			'field_regular_price'        => 1,
			'field_sale_price'           => 1,
			'field_sku'                  => 0,
			'field_product_url'          => 1,
			'field_product_images'       => 1,
			'field_category'             => 0,
			'field_tags'                 => 0,
			'field_custom_text'          => 1,
			'field_hashtags'             => 1,

			// Custom Text & Hashtags
			'custom_text'                => "Visit our online store for the best deals!",
			'hashtags'                   => "#Shopping #OnlineStore #SpecialOffer",

			// Post Template
			'post_template'              => "{product_title}\n\nRegular Price: {regular_price} {currency}\n\nSale Price: {sale_price} {currency}\n\n{custom_text}\n\n{hashtags}",

			// Meta / Facebook Credentials
			'meta_app_id'                => '',
			'meta_app_secret'            => '',
			'meta_api_version'           => defined( 'WSP_DEFAULT_META_API_VERSION' ) ? WSP_DEFAULT_META_API_VERSION : 'v21.0',
			'user_access_token'          => '',
			'fb_page_id'                 => '',
			'fb_page_name'               => '',
			'fb_page_access_token'       => '',
			'available_pages'            => [],

			// Instagram Details
			'ig_account_id'              => '',
			'ig_username'                => '',
			'ig_name'                    => '',
			'ig_profile_picture_url'     => '',

			// Scheduling
			'schedule_default_delay_mins'=> 60,
		];
	}

	/**
	 * Set default options if not existing
	 */
	public static function set_defaults() {
		$existing = get_option( self::OPTION_KEY );
		if ( false === $existing ) {
			update_option( self::OPTION_KEY, self::get_defaults() );
		}
	}

	/**
	 * Get all settings merged with defaults
	 *
	 * @return array
	 */
	public static function get_all() {
		$options = get_option( self::OPTION_KEY, [] );
		$defaults = self::get_defaults();
		$merged = wp_parse_args( $options, $defaults );

		// Decrypt sensitive tokens if encrypted
		if ( ! empty( $merged['fb_page_access_token'] ) ) {
			$merged['fb_page_access_token'] = WSP_Security::decrypt( $merged['fb_page_access_token'] );
		}
		if ( ! empty( $merged['user_access_token'] ) ) {
			$merged['user_access_token'] = WSP_Security::decrypt( $merged['user_access_token'] );
		}

		return $merged;
	}

	/**
	 * Get a single setting value
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$settings = self::get_all();
		if ( isset( $settings[ $key ] ) ) {
			return $settings[ $key ];
		}
		return $default;
	}

	/**
	 * Update settings
	 *
	 * @param array $new_settings
	 * @return bool
	 */
	public static function update_all( $new_settings ) {
		$current = get_option( self::OPTION_KEY, [] );
		$merged = wp_parse_args( $new_settings, $current );

		// Encrypt sensitive tokens before persisting
		if ( ! empty( $merged['fb_page_access_token'] ) ) {
			$merged['fb_page_access_token'] = WSP_Security::encrypt( $merged['fb_page_access_token'] );
		}
		if ( ! empty( $merged['user_access_token'] ) ) {
			$merged['user_access_token'] = WSP_Security::encrypt( $merged['user_access_token'] );
		}

		return update_option( self::OPTION_KEY, $merged );
	}

	/**
	 * Update a single setting
	 *
	 * @param string $key
	 * @param mixed $val
	 * @return bool
	 */
	public static function set( $key, $val ) {
		$settings = self::get_all();
		$settings[ $key ] = $val;
		return self::update_all( $settings );
	}

	/**
	 * Check if Meta connection is active
	 *
	 * @return bool
	 */
	public static function is_facebook_connected() {
		$settings = self::get_all();
		return ! empty( $settings['fb_page_id'] ) && ! empty( $settings['fb_page_access_token'] );
	}

	/**
	 * Check if Instagram connection is active
	 *
	 * @return bool
	 */
	public static function is_instagram_connected() {
		$settings = self::get_all();
		return self::is_facebook_connected() && ! empty( $settings['ig_account_id'] );
	}

	/**
	 * Get Meta OAuth redirect URI
	 *
	 * @return string
	 */
	public static function get_oauth_redirect_uri() {
		return admin_url( 'admin.php?page=woocommerce-social-publisher&tab=facebook&wsp_action=oauth_callback' );
	}
}
