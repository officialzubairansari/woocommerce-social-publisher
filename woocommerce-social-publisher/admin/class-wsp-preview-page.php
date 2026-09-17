<?php
/**
 * Configuration and Preview Screen Controller
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_Preview_Page {

	/**
	 * Initialize preview screen AJAX actions
	 */
	public static function init() {
		add_action( 'wp_ajax_wsp_publish_single_item', [ __CLASS__, 'ajax_publish_single_item' ] );
		add_action( 'wp_ajax_wsp_schedule_batch', [ __CLASS__, 'ajax_schedule_batch' ] );
	}

	/**
	 * Render the Preview/Configuration screen
	 */
	public static function render() {
		WSP_Security::check_permissions_or_abort();

		$batch_id = isset( $_GET['batch_id'] ) ? sanitize_text_field( wp_unslash( $_GET['batch_id'] ) ) : '';
		$nonce    = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

		if ( empty( $batch_id ) || ! wp_verify_nonce( $nonce, 'wsp_preview_' . $batch_id ) ) {
			?>
			<div class="wrap wsp-wrap">
				<h1><?php esc_html_e( 'Social Publisher Preview', 'woocommerce-social-publisher' ); ?></h1>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Invalid or expired preview session. Please select products and initiate bulk action again from WooCommerce Products page.', 'woocommerce-social-publisher' ); ?></p>
					<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Back to Products', 'woocommerce-social-publisher' ); ?></a></p>
				</div>
			</div>
			<?php
			return;
		}

		$batch_data = get_transient( 'wsp_batch_' . $batch_id );
		if ( empty( $batch_data ) || empty( $batch_data['product_ids'] ) ) {
			?>
			<div class="wrap wsp-wrap">
				<h1><?php esc_html_e( 'Social Publisher Preview', 'woocommerce-social-publisher' ); ?></h1>
				<div class="notice notice-warning">
					<p><?php esc_html_e( 'This preview session has expired or contains no products.', 'woocommerce-social-publisher' ); ?></p>
					<p><a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Back to Products', 'woocommerce-social-publisher' ); ?></a></p>
				</div>
			</div>
			<?php
			return;
		}

		$product_ids       = (array) $batch_data['product_ids'];
		$selected_platform = ! empty( $batch_data['platform'] ) ? $batch_data['platform'] : 'both';

		// Prepare items for rendering
		$items = [];
		foreach ( $product_ids as $pid ) {
			$product_data = new WSP_Product_Data( $pid );
			if ( ! $product_data->is_valid() ) {
				continue;
			}

			$caption      = WSP_Caption_Builder::build( $pid );
			$all_images   = $product_data->get_all_images();
			$featured_url = $product_data->get_featured_image_url();
			$duplicates   = WSP_Duplicate_Checker::check_all( $pid );

			$items[] = [
				'id'           => $pid,
				'title'        => $product_data->get_title(),
				'price'        => $product_data->get_regular_price(),
				'sale_price'   => $product_data->get_sale_price(),
				'currency'     => $product_data->get_currency(),
				'permalink'    => $product_data->get_permalink(),
				'caption'      => $caption,
				'featured_url' => $featured_url,
				'images'       => $all_images,
				'duplicates'   => $duplicates,
			];
		}

		$is_fb_connected = WSP_Settings::is_facebook_connected();
		$is_ig_connected = WSP_Settings::is_instagram_connected();
		$fb_page_name    = WSP_Settings::get( 'fb_page_name' );
		$ig_username     = WSP_Settings::get( 'ig_username' );

		include WSP_PATH . 'admin/views/preview-screen.php';
	}

	/**
	 * AJAX handler: Publish a single product item in batch
	 */
	public static function ajax_publish_single_item() {
		WSP_Security::verify_ajax( 'wsp_preview_ajax_nonce', 'nonce' );

		$product_id      = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$platform        = isset( $_POST['platform'] ) ? sanitize_text_field( wp_unslash( $_POST['platform'] ) ) : 'both';
		$caption         = isset( $_POST['caption'] ) ? sanitize_textarea_field( wp_unslash( $_POST['caption'] ) ) : '';
		$image_url       = isset( $_POST['image_url'] ) ? esc_url_raw( wp_unslash( $_POST['image_url'] ) ) : '';
		$publish_again   = ! empty( $_POST['publish_again'] );
		$gallery_urls    = isset( $_POST['gallery_urls'] ) && is_array( $_POST['gallery_urls'] ) ? array_map( 'esc_url_raw', wp_unslash( $_POST['gallery_urls'] ) ) : [];

		if ( empty( $product_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Missing Product ID.', 'woocommerce-social-publisher' ) ] );
		}

		$options = [
			'caption'       => $caption,
			'image_url'     => $image_url,
			'gallery_urls'  => $gallery_urls,
			'publish_again' => $publish_again,
		];

		$results = WSP_Publisher::publish_product( $product_id, $platform, $options );

		wp_send_json_success( [
			'product_id' => $product_id,
			'results'    => $results,
		] );
	}

	/**
	 * AJAX handler: Schedule batch publishing for future date/time
	 */
	public static function ajax_schedule_batch() {
		WSP_Security::verify_ajax( 'wsp_preview_ajax_nonce', 'nonce' );

		$items_raw     = isset( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : [];
		$datetime_str  = isset( $_POST['scheduled_time'] ) ? sanitize_text_field( wp_unslash( $_POST['scheduled_time'] ) ) : '';

		if ( empty( $datetime_str ) ) {
			wp_send_json_error( [ 'message' => __( 'Please select a valid date and time for scheduling.', 'woocommerce-social-publisher' ) ] );
		}

		$timestamp = WSP_Scheduler::parse_local_datetime_to_timestamp( $datetime_str );
		if ( ! $timestamp || $timestamp <= time() ) {
			wp_send_json_error( [ 'message' => __( 'Scheduled time must be in the future.', 'woocommerce-social-publisher' ) ] );
		}

		$items = is_array( $items_raw ) ? $items_raw : json_decode( $items_raw, true );
		if ( empty( $items ) || ! is_array( $items ) ) {
			wp_send_json_error( [ 'message' => __( 'No items to schedule.', 'woocommerce-social-publisher' ) ] );
		}

		$scheduled_count = 0;
		foreach ( $items as $item ) {
			$product_id   = isset( $item['product_id'] ) ? absint( $item['product_id'] ) : 0;
			$platform     = isset( $item['platform'] ) ? sanitize_text_field( $item['platform'] ) : 'both';
			$caption      = isset( $item['caption'] ) ? sanitize_textarea_field( $item['caption'] ) : '';
			$image_url    = isset( $item['image_url'] ) ? esc_url_raw( $item['image_url'] ) : '';
			$gallery_urls = isset( $item['gallery_urls'] ) && is_array( $item['gallery_urls'] ) ? array_map( 'esc_url_raw', $item['gallery_urls'] ) : [];

			if ( $product_id > 0 ) {
				WSP_Queue::schedule_job( $timestamp, $product_id, $platform, [
					'caption'      => $caption,
					'image_url'    => $image_url,
					'gallery_urls' => $gallery_urls,
				] );
				$scheduled_count++;
			}
		}

		wp_send_json_success( [
			'message'         => sprintf( __( 'Successfully scheduled %d product posts.', 'woocommerce-social-publisher' ), $scheduled_count ),
			'scheduled_count' => $scheduled_count,
		] );
	}
}
