<?php
/**
 * Security and Authorization Handler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_Security {

	const CAPABILITY = 'manage_woocommerce';

	/**
	 * Check if current user has permission to manage WooCommerce social publishing
	 *
	 * @return bool
	 */
	public static function current_user_can_manage() {
		return current_user_can( self::CAPABILITY );
	}

	/**
	 * Verify admin permissions or die
	 */
	public static function check_permissions_or_abort() {
		if ( ! self::current_user_can_manage() ) {
			wp_die(
				esc_html__( 'You do not have sufficient permissions to access this page.', 'woocommerce-social-publisher' ),
				esc_html__( 'Access Denied', 'woocommerce-social-publisher' ),
				[ 'response' => 403 ]
			);
		}
	}

	/**
	 * Verify AJAX request nonce and capability
	 *
	 * @param string $action
	 * @param string $query_arg
	 */
	public static function verify_ajax( $action = 'wsp_ajax_nonce', $query_arg = 'nonce' ) {
		if ( ! self::current_user_can_manage() ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'woocommerce-social-publisher' ) ], 403 );
		}

		$nonce = isset( $_REQUEST[ $query_arg ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $query_arg ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid security token (nonce).', 'woocommerce-social-publisher' ) ], 403 );
		}
	}

	/**
	 * Mask sensitive credentials for display (e.g. App Secret, Access Tokens)
	 *
	 * @param string $secret
	 * @param int $visible_chars
	 * @return string
	 */
	public static function mask_secret( $secret, $visible_chars = 4 ) {
		if ( empty( $secret ) ) {
			return '';
		}

		$len = strlen( $secret );
		if ( $len <= $visible_chars * 2 ) {
			return str_repeat( '•', $len );
		}

		return substr( $secret, 0, $visible_chars ) . str_repeat( '•', max( 8, $len - ( $visible_chars * 2 ) ) ) . substr( $secret, -$visible_chars );
	}

	/**
	 * Simple reversible obfuscation for stored tokens using WordPress salt
	 *
	 * @param string $data
	 * @return string
	 */
	public static function encrypt( $data ) {
		if ( empty( $data ) || ! function_exists( 'openssl_encrypt' ) ) {
			return $data;
		}

		$key = defined( 'AUTH_KEY' ) ? substr( hash( 'sha256', AUTH_KEY ), 0, 32 ) : substr( hash( 'sha256', 'wsp_default_key' ), 0, 32 );
		$iv  = openssl_random_pseudo_bytes( 16 );
		$cipher = openssl_encrypt( $data, 'AES-256-CBC', $key, 0, $iv );

		return base64_encode( $iv . '::' . $cipher );
	}

	/**
	 * Decrypt stored token
	 *
	 * @param string $data
	 * @return string
	 */
	public static function decrypt( $data ) {
		if ( empty( $data ) || ! function_exists( 'openssl_decrypt' ) ) {
			return $data;
		}

		$raw = base64_decode( $data, true );
		if ( false === $raw || strpos( $raw, '::' ) === false ) {
			return $data; // Not encrypted or legacy plain text
		}

		$parts = explode( '::', $raw, 2 );
		if ( count( $parts ) !== 2 ) {
			return $data;
		}

		$key = defined( 'AUTH_KEY' ) ? substr( hash( 'sha256', AUTH_KEY ), 0, 32 ) : substr( hash( 'sha256', 'wsp_default_key' ), 0, 32 );
		$decrypted = openssl_decrypt( $parts[1], 'AES-256-CBC', $key, 0, $parts[0] );

		return false !== $decrypted ? $decrypted : $data;
	}
}
