<?php
/**
 * Image Validator for Meta Graph API Requirements
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_Image_Validator {

	/**
	 * Supported formats per platform
	 */
	const FB_FORMATS = [ 'jpg', 'jpeg', 'png', 'gif', 'webp' ];
	const IG_FORMATS = [ 'jpg', 'jpeg', 'png' ];

	/**
	 * Validate image for Meta publishing
	 *
	 * @param string $image_url
	 * @param string $platform 'facebook', 'instagram', or 'both'
	 * @return array [ 'valid' => bool, 'errors' => array, 'warnings' => array ]
	 */
	public static function validate( $image_url, $platform = 'both' ) {
		$errors   = [];
		$warnings = [];

		if ( empty( $image_url ) ) {
			return [
				'valid'    => false,
				'errors'   => [ __( 'No image provided. A product image is required for image publishing.', 'woocommerce-social-publisher' ) ],
				'warnings' => [],
			];
		}

		// 1. Validate URL syntax
		if ( ! filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
			$errors[] = sprintf( __( 'Invalid image URL: %s', 'woocommerce-social-publisher' ), esc_url( $image_url ) );
			return [ 'valid' => false, 'errors' => $errors, 'warnings' => $warnings ];
		}

		// 2. Check public reachability (Localhost / Intranet warning)
		if ( self::is_private_or_local_url( $image_url ) ) {
			$warnings[] = __( 'Notice: This site appears to be running on localhost or a local test domain. Meta requires publicly accessible HTTP/HTTPS URLs to fetch images. Posts will fail unless exposed via an ngrok or Cloudflare tunnel.', 'woocommerce-social-publisher' );
		}

		// 3. Validate file extension
		$path = wp_parse_url( $image_url, PHP_URL_PATH );
		$ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );

		if ( in_array( $platform, [ 'instagram', 'both' ], true ) ) {
			if ( ! in_array( $ext, self::IG_FORMATS, true ) ) {
				$errors[] = sprintf(
					__( 'Unsupported image format "%s" for Instagram. Instagram requires JPEG or PNG.', 'woocommerce-social-publisher' ),
					$ext ? $ext : 'unknown'
				);
			}
		}

		if ( in_array( $platform, [ 'facebook', 'both' ], true ) ) {
			if ( ! in_array( $ext, self::FB_FORMATS, true ) ) {
				$errors[] = sprintf(
					__( 'Unsupported image format "%s" for Facebook. Supported: JPG, PNG, GIF, WebP.', 'woocommerce-social-publisher' ),
					$ext ? $ext : 'unknown'
				);
			}
		}

		return [
			'valid'    => empty( $errors ),
			'errors'   => $errors,
			'warnings' => $warnings,
		];
	}

	/**
	 * Check if URL points to a local or private address
	 *
	 * @param string $url
	 * @return bool
	 */
	public static function is_private_or_local_url( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( empty( $host ) ) {
			return true;
		}

		$local_patterns = [
			'localhost',
			'127.0.0.1',
			'::1',
			'.local',
			'.test',
			'.example',
			'.invalid',
			'.localhost',
		];

		foreach ( $local_patterns as $pattern ) {
			if ( $host === $pattern || substr( $host, -strlen( $pattern ) ) === $pattern ) {
				return true;
			}
		}

		// Check private IP ranges
		$ip = filter_var( $host, FILTER_VALIDATE_IP ) ? $host : @gethostbyname( $host );
		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false ) {
			return true;
		}

		return false;
	}
}
