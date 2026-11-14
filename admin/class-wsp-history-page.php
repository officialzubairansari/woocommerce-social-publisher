<?php
/**
 * Publishing History Page Controller
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_History_Page {

	/**
	 * Initialize history hooks and AJAX actions
	 */
	public static function init() {
		add_action( 'wp_ajax_wsp_reschedule_post', [ __CLASS__, 'ajax_reschedule_post' ] );
		add_action( 'wp_ajax_wsp_bulk_reschedule_posts', [ __CLASS__, 'ajax_bulk_reschedule_posts' ] );
		add_action( 'wp_ajax_wsp_delete_post', [ __CLASS__, 'ajax_delete_post' ] );
		add_action( 'wp_ajax_wsp_bulk_delete_posts', [ __CLASS__, 'ajax_bulk_delete_posts' ] );
		add_action( 'wp_ajax_wsp_get_post_details', [ __CLASS__, 'ajax_get_post_details' ] );
	}

	/**
	 * Render the Publishing History screen
	 */
	public static function render() {
		WSP_Security::check_permissions_or_abort();

		$current_status   = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'published';
		if ( ! in_array( $current_status, [ 'published', 'failed', 'scheduled', 'processing', 'queued' ], true ) ) {
			$current_status = 'published';
		}
		$current_platform = isset( $_GET['platform'] ) ? sanitize_text_field( $_GET['platform'] ) : '';
		$paged            = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$per_page         = isset( $_GET['per_page'] ) ? max( 1, min( 200, absint( $_GET['per_page'] ) ) ) : 20;

		$history = WSP_History::get_list( [
			'status'   => $current_status,
			'platform' => $current_platform,
			'paged'    => $paged,
			'per_page' => $per_page,
		] );

		include WSP_PATH . 'admin/views/history-table.php';
	}

	/** AJAX handler: Move an eligible post to a new date and time. */
	public static function ajax_reschedule_post() {
		WSP_Security::verify_ajax( 'wsp_history_ajax_nonce', 'nonce' );
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$result = WSP_Queue::reschedule_post( $id, self::requested_timestamp() );
		if ( ! $result['success'] ) {
			wp_send_json_error( [ 'message' => $result['error'] ] );
		}
		wp_send_json_success( [ 'message' => __( 'Post rescheduled successfully.', 'woocommerce-social-publisher' ) ] );
	}

	/** AJAX handler: Apply the same date and time to selected eligible records. */
	public static function ajax_bulk_reschedule_posts() {
		WSP_Security::verify_ajax( 'wsp_history_ajax_nonce', 'nonce' );
		$timestamp = self::requested_timestamp();
		$ids = isset( $_POST['ids'] ) ? array_values( array_unique( array_filter( array_map( 'absint', (array) $_POST['ids'] ) ) ) ) : [];
		if ( ! $ids || count( $ids ) > 200 ) {
			wp_send_json_error( [ 'message' => __( 'Select between 1 and 200 records to reschedule.', 'woocommerce-social-publisher' ) ] );
		}
		$rescheduled = 0;
		$skipped = 0;
		$errors = [];
		foreach ( $ids as $id ) {
			$record = WSP_Database::get_post( $id );
			if ( $record && ! WSP_Queue::can_reschedule( $record->status ) ) {
				$skipped++;
				continue;
			}
			$result = WSP_Queue::reschedule_post( $id, $timestamp );
			if ( $result['success'] ) {
				$rescheduled++;
			} else {
				$errors[] = sprintf( __( 'Record #%1$d: %2$s', 'woocommerce-social-publisher' ), $id, $result['error'] );
			}
		}
		$data = [
			'rescheduled' => $rescheduled,
			'skipped' => $skipped,
			'failed' => count( $errors ),
			'message' => sprintf( __( 'Rescheduled: %1$d. Skipped (Published or Queued): %2$d. Failed: %3$d.', 'woocommerce-social-publisher' ), $rescheduled, $skipped, count( $errors ) ) . ( $errors ? "\n" . implode( "\n", $errors ) : '' ),
		];
		if ( ! $rescheduled ) {
			wp_send_json_error( $data );
		}
		wp_send_json_success( $data );
	}

	/** Validate the common site-local datetime submitted by either dialog. */
	private static function requested_timestamp() {
		$datetime = isset( $_POST['scheduled_time'] ) ? sanitize_text_field( wp_unslash( $_POST['scheduled_time'] ) ) : '';
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d\\TH:i', $datetime, wp_timezone() );
		if ( ! $date || $date->format( 'Y-m-d\\TH:i' ) !== $datetime || $date->getTimestamp() <= time() ) {
			wp_send_json_error( [ 'message' => __( 'Please select a valid date and time in the future.', 'woocommerce-social-publisher' ) ] );
		}
		return $date->getTimestamp();
	}

	/**
	 * AJAX handler: Delete a post log
	 */
	public static function ajax_delete_post() {
		WSP_Security::verify_ajax( 'wsp_history_ajax_nonce', 'nonce' );

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		if ( empty( $id ) ) {
			wp_send_json_error( [ 'message' => __( 'Missing post log ID.', 'woocommerce-social-publisher' ) ] );
		}

		if ( WSP_History::delete( $id ) ) {
			wp_send_json_success();
		} else {
			wp_send_json_error( [ 'message' => __( 'Failed to delete record.', 'woocommerce-social-publisher' ) ] );
		}
	}

	/**
	 * AJAX handler: Bulk delete logs
	 */
	public static function ajax_bulk_delete_posts() {
		WSP_Security::verify_ajax( 'wsp_history_ajax_nonce', 'nonce' );

		$ids = isset( $_POST['ids'] ) ? array_map( 'absint', (array) $_POST['ids'] ) : [];
		if ( empty( $ids ) ) {
			wp_send_json_error( [ 'message' => __( 'No records selected.', 'woocommerce-social-publisher' ) ] );
		}

		$count = WSP_History::bulk_delete( $ids );
		wp_send_json_success( [ 'deleted' => $count ] );
	}

	/**
	 * AJAX handler: Fetch full post log details for modal viewer
	 */
	public static function ajax_get_post_details() {
		WSP_Security::verify_ajax( 'wsp_history_ajax_nonce', 'nonce' );

		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$record = WSP_Database::get_post( $id );

		if ( ! $record ) {
			wp_send_json_error( [ 'message' => __( 'Record not found.', 'woocommerce-social-publisher' ) ] );
		}

		$product_data = new WSP_Product_Data( $record->product_id );

		wp_send_json_success( [
			'id'                    => $record->id,
			'product_id'            => $record->product_id,
			'product_title'         => $product_data->is_valid() ? $product_data->get_title() : __( '[Deleted Product]', 'woocommerce-social-publisher' ),
			'product_url'           => $product_data->is_valid() ? $product_data->get_permalink() : '',
			'platform'              => $record->platform,
			'post_type'             => $record->post_type,
			'caption'               => $record->caption,
			'media_url'             => $record->media_url,
			'status'                => $record->status,
			'scheduled_at'          => $record->scheduled_at,
			'published_at'          => $record->published_at,
			'external_post_id'      => $record->external_post_id,
			'external_container_id' => $record->external_container_id,
			'error_message'         => $record->error_message,
			'attempts'              => $record->attempts,
			'created_at'            => $record->created_at,
		] );
	}
}
