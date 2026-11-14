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
	const HOOK_RECORD = 'wsp_process_scheduled_record';

	/**
	 * Initialize queue hooks
	 */
	public static function init() {
		add_action( self::HOOK_SINGLE, [ __CLASS__, 'process_job' ], 10, 3 );
		add_action( self::HOOK_RECORD, [ __CLASS__, 'process_scheduled_record' ], 10, 2 );
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
			$id = WSP_Database::insert_post( [
				'product_id'   => $product_id,
				'platform'     => $p,
				'post_type'    => $post_type,
				'caption'      => $caption,
				'media_url'    => $img_url,
				'status'       => 'scheduled',
				'scheduled_at' => gmdate( 'Y-m-d H:i:s', $timestamp ),
			] );
			if ( ! $id || ! self::schedule_record( $id, $timestamp ) ) {
				if ( $id ) {
					WSP_Database::update_post( $id, [ 'status' => 'failed', 'error_message' => __( 'Unable to create scheduled job.', 'woocommerce-social-publisher' ) ] );
				}
				return false;
			}
		}
		return true;
	}

	/** Schedule by record and timestamp so obsolete callbacks cannot publish. */
	private static function schedule_record( $id, $timestamp ) {
		if ( function_exists( 'as_schedule_single_action' ) ) {
			return (bool) as_schedule_single_action( $timestamp, self::HOOK_RECORD, [ (int) $id, (int) $timestamp ], self::GROUP );
		}
		return (bool) wp_schedule_single_event( $timestamp, self::HOOK_RECORD, [ (int) $id, (int) $timestamp ] );
	}

	private static function cancel_record( $id, $timestamp ) {
		$args = [ (int) $id, (int) $timestamp ];
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::HOOK_RECORD, $args, self::GROUP );
		}
		wp_clear_scheduled_hook( self::HOOK_RECORD, $args );
	}

	/** Published and queued posts are excluded from rescheduling. */
	public static function can_reschedule( $status ) {
		return ! in_array( $status, [ 'published', 'queued' ], true );
	}

	/** Change a history record's schedule without creating another history row. */
	public static function reschedule_post( $id, $timestamp ) {
		$record = WSP_Database::get_post( $id );
		if ( ! $record || ! self::can_reschedule( $record->status ) ) {
			return [ 'success' => false, 'error' => __( 'Record not found or already Published or Queued.', 'woocommerce-social-publisher' ) ];
		}
		if ( $timestamp <= time() ) {
			return [ 'success' => false, 'error' => __( 'Scheduled time must be in the future.', 'woocommerce-social-publisher' ) ];
		}
		$old_timestamp = $record->scheduled_at ? strtotime( $record->scheduled_at . ' UTC' ) : 0;
		if ( $old_timestamp === (int) $timestamp && 'scheduled' === $record->status ) {
			return [ 'success' => true ];
		}
		// Older releases used product-level jobs. Convert these before moving a row.
		if ( 'scheduled' === $record->status && ! self::convert_legacy_job( $record, $old_timestamp ) ) {
			return [ 'success' => false, 'error' => __( 'Unable to replace the original scheduled job. It may already be processing. Please refresh and try again.', 'woocommerce-social-publisher' ) ];
		}
		if ( ! self::schedule_record( $id, $timestamp ) ) {
			return [ 'success' => false, 'error' => __( 'Unable to create scheduled job. Please try again.', 'woocommerce-social-publisher' ) ];
		}
		if ( ! WSP_Database::update_post( $id, [ 'status' => 'scheduled', 'scheduled_at' => gmdate( 'Y-m-d H:i:s', $timestamp ), 'error_message' => null ] ) ) {
			self::cancel_record( $id, $timestamp );
			return [ 'success' => false, 'error' => __( 'Unable to save the new schedule.', 'woocommerce-social-publisher' ) ];
		}
		if ( $old_timestamp ) {
			self::cancel_record( $id, $old_timestamp );
		}
		return [ 'success' => true ];
	}

	/** Convert a legacy job, including both-platform jobs, to record-specific jobs. */
	private static function convert_legacy_job( $record, $timestamp ) {
		$record_args = [ (int) $record->id, (int) $timestamp ];
		$next = function_exists( 'as_next_scheduled_action' ) ? as_next_scheduled_action( self::HOOK_RECORD, $record_args, self::GROUP ) : false;
		if ( true === $next ) {
			return false;
		}
		if ( $next || wp_next_scheduled( self::HOOK_RECORD, $record_args ) ) {
			return true;
		}
		$jobs = [];
		if ( function_exists( 'as_get_scheduled_actions' ) ) {
			foreach ( as_get_scheduled_actions( [ 'hook' => self::HOOK_SINGLE, 'group' => self::GROUP, 'status' => 'pending', 'date' => $timestamp, 'date_compare' => '=', 'per_page' => -1 ] ) as $action_id => $action ) {
				$jobs[] = [ 'args' => $action->get_args(), 'action_id' => $action_id ];
			}
		}
		foreach ( (array) _get_cron_array() as $cron_timestamp => $hooks ) {
			if ( (int) $cron_timestamp === $timestamp && isset( $hooks[ self::HOOK_SINGLE ] ) ) {
				foreach ( $hooks[ self::HOOK_SINGLE ] as $event ) {
					$jobs[] = [ 'args' => $event['args'], 'action_id' => 0 ];
				}
			}
		}
		$matches = [];
		foreach ( $jobs as $job ) {
			$args = array_values( $job['args'] );
			if ( (int) ( $args[0] ?? 0 ) === (int) $record->product_id && in_array( $args[1] ?? '', [ $record->platform, 'both' ], true ) && ( empty( $args[2]['caption'] ) || $args[2]['caption'] === $record->caption ) ) {
				$matches[] = $job;
			}
		}
		if ( count( $matches ) !== 1 ) {
			return false;
		}
		$job = $matches[0];
		$args = array_values( $job['args'] );
		global $wpdb;
		$table = WSP_Database::get_table_name();
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = 'scheduled' AND product_id = %d AND scheduled_at = %s", $record->product_id, $record->scheduled_at ) );
		$converted = [];
		$matched_rows = [];
		foreach ( $rows as $row ) {
			if ( (int) $row->product_id === (int) $record->product_id && $row->scheduled_at === $record->scheduled_at && ( 'both' === $args[1] || $row->platform === $args[1] ) && $row->caption === $record->caption ) {
				if ( isset( $matched_rows[ $row->platform ] ) ) {
					return false;
				}
				$matched_rows[ $row->platform ] = $row;
			}
		}
		foreach ( $matched_rows as $row ) {
			if ( ! self::schedule_record( $row->id, $timestamp ) ) {
				foreach ( $converted as $converted_id ) {
					self::cancel_record( $converted_id, $timestamp );
				}
				return false;
			}
			$converted[] = $row->id;
		}
		if ( $job['action_id'] ) {
			try {
				ActionScheduler::store()->cancel_action( $job['action_id'] );
			} catch ( Exception $e ) {
				foreach ( $converted as $converted_id ) {
					self::cancel_record( $converted_id, $timestamp );
				}
				return false;
			}
		} else {
			$result = wp_unschedule_event( $timestamp, self::HOOK_SINGLE, $job['args'] );
			if ( false === $result || is_wp_error( $result ) ) {
				foreach ( $converted as $converted_id ) {
					self::cancel_record( $converted_id, $timestamp );
				}
				return false;
			}
		}
		return true;
	}

	/** Publish only the current version of a scheduled history record. */
	public static function process_scheduled_record( $id, $timestamp ) {
		$record = WSP_Database::get_post( $id );
		if ( ! $record || 'scheduled' !== $record->status || $record->scheduled_at !== gmdate( 'Y-m-d H:i:s', $timestamp ) || $timestamp > time() ) {
			return;
		}
		$product = new WSP_Product_Data( $record->product_id );
		$error = $product->is_valid() ? '' : __( 'Product no longer exists.', 'woocommerce-social-publisher' );
		if ( ! $error && 0 === (int) $record->attempts ) {
			$duplicate = WSP_Duplicate_Checker::is_allowed( $record->product_id, $record->platform );
			$error = $duplicate['allowed'] ? '' : $duplicate['warning'];
		}
		$images = array_filter( array_map( 'trim', explode( ',', (string) $record->media_url ) ) );
		foreach ( $images as $image ) {
			$validation = WSP_Image_Validator::validate( $image, $record->platform );
			if ( ! $validation['valid'] ) {
				$error = implode( '; ', $validation['errors'] );
				break;
			}
		}
		if ( ! $images && 'instagram' === $record->platform ) {
			$error = __( 'Instagram requires an image to create a post.', 'woocommerce-social-publisher' );
		}
		if ( $error ) {
			WSP_Database::update_post( $id, [ 'status' => 'failed', 'error_message' => $error ] );
			return;
		}
		WSP_Publisher::publish_record( $id );
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
			as_unschedule_all_actions( self::HOOK_RECORD, null, self::GROUP );
		}
		wp_unschedule_hook( self::HOOK_RECORD );
	}
}
