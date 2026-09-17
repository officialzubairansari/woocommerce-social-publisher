<?php
/**
 * Duplicate Publishing Prevention Handler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_Duplicate_Checker {

	/**
	 * Check if product has already been successfully published to a platform
	 *
	 * @param int $product_id
	 * @param string $platform 'facebook' or 'instagram'
	 * @return array [ 'published' => bool, 'published_at' => string|null, 'post_id' => int|null, 'external_id' => string|null ]
	 */
	public static function check( $product_id, $platform ) {
		global $wpdb;
		$table = WSP_Database::get_table_name();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, published_at, external_post_id FROM {$table} 
				WHERE product_id = %d AND platform = %s AND status = 'published' 
				ORDER BY published_at DESC LIMIT 1",
				absint( $product_id ),
				sanitize_text_field( $platform )
			)
		);

		if ( $row ) {
			return [
				'published'    => true,
				'published_at' => $row->published_at,
				'post_id'      => (int) $row->id,
				'external_id'  => $row->external_post_id,
			];
		}

		return [
			'published'    => false,
			'published_at' => null,
			'post_id'      => null,
			'external_id'  => null,
		];
	}

	/**
	 * Check status for both platforms
	 *
	 * @param int $product_id
	 * @return array [ 'facebook' => array, 'instagram' => array ]
	 */
	public static function check_all( $product_id ) {
		return [
			'facebook'  => self::check( $product_id, 'facebook' ),
			'instagram' => self::check( $product_id, 'instagram' ),
		];
	}

	/**
	 * Check if publishing is allowed considering duplicate settings and explicit override
	 *
	 * @param int $product_id
	 * @param string $platform
	 * @param bool $allow_duplicate
	 * @return array [ 'allowed' => bool, 'warning' => string ]
	 */
	public static function is_allowed( $product_id, $platform, $allow_duplicate = false ) {
		$behavior = WSP_Settings::get( 'duplicate_behavior', 'warn' );

		// If user explicitly checked "Publish Again" or duplicate behavior is 'allow'
		if ( $allow_duplicate || 'allow' === $behavior ) {
			return [ 'allowed' => true, 'warning' => '' ];
		}

		$status = self::check( $product_id, $platform );

		if ( $status['published'] ) {
			$platform_label = 'facebook' === $platform ? 'Facebook' : 'Instagram';
			$date_str = ! empty( $status['published_at'] ) ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $status['published_at'] ) ) : '';

			$msg = sprintf(
				__( 'This product was already published to %s on %s.', 'woocommerce-social-publisher' ),
				$platform_label,
				$date_str
			);

			if ( 'block' === $behavior ) {
				return [ 'allowed' => false, 'warning' => $msg ];
			}

			// If 'warn', block unless $allow_duplicate is passed
			return [ 'allowed' => false, 'warning' => $msg . ' ' . __( 'Check "Publish Again" to proceed.', 'woocommerce-social-publisher' ) ];
		}

		return [ 'allowed' => true, 'warning' => '' ];
	}
}
