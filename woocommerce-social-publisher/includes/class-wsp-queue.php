<?php
/**
 * Background Queue Handler using WooCommerce Action Scheduler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_Queue {

	const GROUP = 'wsp_social_publisher';
	const HOOK_SINGLE = 'wsp_process_social_job';

	/**
	 * Initialize queue hooks
	 */
	public static function init() {
		add_action( self::HOOK_SINGLE, [ __CLASS__, 'process_job' ], 10, 3 );
	}

	/**
	 * Enqueue an immediate background publishing job
	 *
	 * @param int $product_id
	 * @param string $platform 'facebook', 'instagram', or 'both'
	 * @param array $options
	 * @return bool
	 */
	public static function enqueue_job( $product_id, $platform, $options = [] ) {
		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action(
				self::HOOK_SINGLE,
				[ $product_id, $platform, $options ],
				self::GROUP
			);
			return true;
		}

		// Fallback to WP-Cron single event
		if ( ! wp_next_scheduled( self::HOOK_SINGLE, [ $product_id, $platform, $options ] ) ) {
			wp_schedule_single_event( time(), self::HOOK_SINGLE, [ $product_id, $platform, $options ] );
			return true;
		}

		return false;
	}

	/**
	 * Schedule a future publishing job at specific Unix timestamp
	 *
	 * @param int $timestamp
	 * @param int $product_id
	 * @param string $platform 'facebook', 'instagram', or 'both'
	 * @param array $options
	 * @return bool
	 */
	public static function schedule_job( $timestamp, $product_id, $platform, $options = [] ) {
		// Create scheduled DB draft entry
		$platforms = ( 'both' === $platform ) ? [ 'facebook', 'instagram' ] : [ $platform ];
		$product_data = new WSP_Product_Data( $product_id );
		$caption = ! empty( $options['caption'] ) ? $options['caption'] : WSP_Caption_Builder::build( $product_id );

		$gallery_urls = ! empty( $options['gallery_urls'] ) && is_array( $options['gallery_urls'] )
			? $options['gallery_urls']
			: $product_data->get_all_image_urls();

		$img_url = ! empty( $gallery_urls ) ? implode( ',', $gallery_urls ) : $product_data->get_featured_image_url();
		$post_type = count( $gallery_urls ) > 1 ? 'carousel' : 'single_image';

		foreach ( $platforms as $p ) {
			WSP_Database::insert_post( [
				'product_id'   => $product_id,
				'platform'     => $p,
				'post_type'    => $post_type,
				'caption'      => $caption,
				'media_url'    => $img_url,
				'status'       => 'scheduled',
				'scheduled_at' => date( 'Y-m-d H:i:s', $timestamp ),
			] );
		}

		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action(
				$timestamp,
				self::HOOK_SINGLE,
				[ $product_id, $platform, $options ],
				self::GROUP
			);
			return true;
		}

		// Fallback to WP-Cron
		wp_schedule_single_event( $timestamp, self::HOOK_SINGLE, [ $product_id, $platform, $options ] );
		return true;
	}

	/**
	 * Process a queued or scheduled publishing job
	 *
	 * @param int $product_id
	 * @param string $platform
	 * @param array $options
	 */
	public static function process_job( $product_id, $platform, $options = [] ) {
		WSP_Publisher::publish_product( $product_id, $platform, $options );
	}

	/**
	 * Clear scheduled actions on deactivation
	 */
	public static function clear_scheduled_actions() {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::HOOK_SINGLE, null, self::GROUP );
		}
	}
}
