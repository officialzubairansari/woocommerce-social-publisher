<?php
/**
 * Native WooCommerce Products Bulk Actions Handler
 *
 * Integrates directly into /wp-admin/edit.php?post_type=product
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_Bulk_Actions {

	/**
	 * Initialize bulk action filters
	 */
	public static function init() {
		add_filter( 'bulk_actions-edit-product', [ __CLASS__, 'register_bulk_actions' ] );
		add_filter( 'handle_bulk_actions-edit-product', [ __CLASS__, 'handle_bulk_actions' ], 10, 3 );
	}

	/**
	 * Register native bulk actions on Products list table
	 *
	 * @param array $actions
	 * @return array
	 */
	public static function register_bulk_actions( $actions ) {
		$actions['wsp_publish_social_media'] = __( 'Publish to Social Media', 'woocommerce-social-publisher' );
		return $actions;
	}

	/**
	 * Intercept bulk action click and redirect to Preview/Configuration screen
	 *
	 * @param string $redirect_to
	 * @param string $action
	 * @param array $post_ids
	 * @return string
	 */
	public static function handle_bulk_actions( $redirect_to, $action, $post_ids ) {
		$valid_actions = [
			'wsp_publish_social_media' => 'both',
			// Backward compatibility aliases
			'wsp_post_both'            => 'both',
			'wsp_post_facebook'        => 'facebook',
			'wsp_post_instagram'       => 'instagram',
		];

		if ( ! isset( $valid_actions[ $action ] ) ) {
			return $redirect_to;
		}

		if ( ! WSP_Security::current_user_can_manage() ) {
			return $redirect_to;
		}

		if ( empty( $post_ids ) || ! is_array( $post_ids ) ) {
			return $redirect_to;
		}

		$platform = $valid_actions[ $action ];

		// Generate secure batch token
		$batch_id = wp_generate_password( 24, false );
		$batch_data = [
			'product_ids' => array_map( 'absint', $post_ids ),
			'platform'    => $platform,
			'created_at'  => time(),
			'user_id'     => get_current_user_id(),
		];

		set_transient( 'wsp_batch_' . $batch_id, $batch_data, 30 * MINUTE_IN_SECONDS );

		// Redirect to configuration/preview screen
		$preview_url = add_query_arg(
			[
				'page'     => 'wsp-preview',
				'batch_id' => $batch_id,
				'_wpnonce' => wp_create_nonce( 'wsp_preview_' . $batch_id ),
			],
			admin_url( 'admin.php' )
		);

		return $preview_url;
	}
}
