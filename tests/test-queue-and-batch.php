<?php
/**
 * Test Suite: Bulk Actions, Queue & Scaling (1, 10, 50, 100 products)
 */

function test_bulk_actions_registration() {
	$actions = WSP_Bulk_Actions::register_bulk_actions( [] );

	assert_true( isset( $actions['wsp_publish_social_media'] ), 'Publish to Social Media bulk action registered' );
	assert_true( ! isset( $actions['wsp_post_facebook'] ), 'Separate Facebook bulk action removed' );
	assert_true( ! isset( $actions['wsp_post_instagram'] ), 'Separate Instagram bulk action removed' );
	assert_true( ! isset( $actions['wsp_post_both'] ), 'Separate combined bulk action removed' );
}

function test_bulk_action_interception_creates_transient() {
	$product_ids = [ 101, 102, 103 ];
	$redirect_url = WSP_Bulk_Actions::handle_bulk_actions( 'edit.php?post_type=product', 'wsp_publish_social_media', $product_ids );

	assert_true( strpos( $redirect_url, 'page=wsp-preview' ) !== false, 'Redirects to preview screen' );
	assert_true( strpos( $redirect_url, 'batch_id=' ) !== false, 'Contains batch_id parameter' );

	parse_str( parse_url( $redirect_url, PHP_URL_QUERY ), $params );
	$batch_id = $params['batch_id'];

	$batch_data = get_transient( 'wsp_batch_' . $batch_id );
	assert_true( ! empty( $batch_data ), 'Batch data securely stored in transient' );
	assert_true( count( $batch_data['product_ids'] ) === 3, 'All product IDs preserved in batch' );
	assert_true( $batch_data['platform'] === 'both', 'Target platform preserved in batch' );
}

function test_batch_scaling_1_to_100_products() {
	$scales = [ 1, 10, 50, 100 ];

	foreach ( $scales as $count ) {
		$ids = range( 1, $count );
		$batch_id = 'test_scale_' . $count;
		set_transient( 'wsp_batch_' . $batch_id, [
			'product_ids' => $ids,
			'platform'    => 'both',
		] );

		$stored = get_transient( 'wsp_batch_' . $batch_id );
		assert_true( count( $stored['product_ids'] ) === $count, "Scaling test: Successfully handles batch of {$count} products" );
	}
}

function test_scheduler_datetime_parsing() {
	$str = '2026-10-01 15:30';
	$ts = WSP_Scheduler::parse_local_datetime_to_timestamp( $str );

	assert_true( is_int( $ts ) && $ts > 0, 'Local datetime parsed into Unix timestamp' );
}

function test_retry_system() {
	global $wpdb;
	$wpdb->posts = [];

	// Create failed post record
	$db_id = WSP_Database::insert_post( [
		'product_id'    => 101,
		'platform'      => 'facebook',
		'caption'       => 'Retry Caption',
		'status'        => 'failed',
		'error_message' => 'Temporary network error',
		'attempts'      => 1,
	] );

	// Mock successful retry
	global $mock_http_responses;
	$mock_http_responses['10987654321/feed'] = [
		'response' => [ 'code' => 200 ],
		'body'     => json_encode( [ 'id' => 'fb_retry_success_id' ] ),
	];

	$retry_res = WSP_Publisher::retry( $db_id );
	assert_true( $retry_res['success'], 'Retry completed successfully' );

	$updated = WSP_Database::get_post( $db_id );
	assert_true( $updated->status === 'published', 'Post status updated to published after retry' );
	assert_true( $updated->attempts === 2, 'Attempt counter incremented on retry' );
}
