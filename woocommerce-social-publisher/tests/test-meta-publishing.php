<?php
/**
 * Test Suite: Meta Graph API Publishing & Error Handling (Mocked)
 */

function test_facebook_photo_publishing() {
	global $mock_http_responses;
	WSP_Settings::set( 'fb_page_id', '10987654321' );
	WSP_Settings::set( 'fb_page_access_token', 'mock_fb_page_token' );

	$mock_http_responses['10987654321/photos'] = [
		'response' => [ 'code' => 200 ],
		'body'     => json_encode( [
			'id'      => 'photo_99887766',
			'post_id' => '10987654321_99887766',
		] ),
	];

	$res = WSP_Facebook::publish( [
		'caption'   => 'Test FB Caption',
		'image_url' => 'https://beauties.pk/wp-content/uploads/photo.jpg',
	] );

	assert_true( $res['success'], 'Facebook photo published successfully' );
	assert_true( $res['post_id'] === '10987654321_99887766', 'Received correct Facebook post ID' );
}

function test_instagram_container_publishing_workflow() {
	global $mock_http_responses;
	WSP_Settings::set( 'ig_account_id', '17841400000000000' );
	WSP_Settings::set( 'fb_page_access_token', 'mock_token' );

	// Mock Step 1: Create Container
	$mock_http_responses['17841400000000000/media'] = [
		'response' => [ 'code' => 200 ],
		'body'     => json_encode( [ 'id' => 'container_112233' ] ),
	];

	// Mock Step 2: Container Status
	$mock_http_responses['container_112233'] = [
		'response' => [ 'code' => 200 ],
		'body'     => json_encode( [ 'status_code' => 'FINISHED', 'id' => 'container_112233' ] ),
	];

	// Mock Step 3: Publish Container
	$mock_http_responses['17841400000000000/media_publish'] = [
		'response' => [ 'code' => 200 ],
		'body'     => json_encode( [ 'id' => 'ig_media_post_554433' ] ),
	];

	$res = WSP_Instagram::publish( [
		'caption'   => 'Test IG Caption',
		'image_url' => 'https://beauties.pk/wp-content/uploads/photo.jpg',
	] );

	assert_true( $res['success'], 'Instagram container workflow succeeded' );
	assert_true( $res['post_id'] === 'ig_media_post_554433', 'Received Instagram post ID' );
	assert_true( $res['container_id'] === 'container_112233', 'Received Instagram container ID' );
}

function test_meta_error_diagnostic_guidance() {
	// Error 190 (Expired token)
	$err1 = WSP_Meta_API::format_meta_error( 'Session has expired', 190, 463 );
	assert_true( strpos( $err1, 'expired' ) !== false, 'Diagnoses expired token' );

	// Error 200 (Permission denied)
	$err2 = WSP_Meta_API::format_meta_error( 'Requires pages_manage_posts', 200 );
	assert_true( strpos( $err2, 'pages_manage_posts' ) !== false, 'Diagnoses missing permission' );

	// Error 100 (Aspect ratio)
	$err3 = WSP_Meta_API::format_meta_error( 'Invalid aspect ratio', 100 );
	assert_true( strpos( $err3, 'aspect ratio' ) !== false, 'Diagnoses aspect ratio requirements' );
}

function test_independent_platform_tracking() {
	global $mock_http_responses, $wpdb;
	$wpdb->posts = [];

	// Mock Facebook success
	$mock_http_responses['10987654321/photos'] = [
		'response' => [ 'code' => 200 ],
		'body'     => json_encode( [ 'id' => 'fb_success_id', 'post_id' => 'fb_success_id' ] ),
	];

	// Mock Instagram failure (permission error)
	$mock_http_responses['17841400000000000/media'] = [
		'response' => [ 'code' => 400 ],
		'body'     => json_encode( [
			'error' => [
				'message' => 'Permission denied',
				'code'    => 200,
			],
		] ),
	];

	$results = WSP_Publisher::publish_product( 101, 'both', [
		'caption'      => 'Dual Post',
		'image_url'    => 'https://beauties.pk/wp-content/uploads/photo.jpg',
		'gallery_urls' => [ 'https://beauties.pk/wp-content/uploads/photo.jpg' ], // Single image test
	] );

	assert_true( $results['facebook']['success'] === true, 'Facebook succeeded independently' );
	assert_true( $results['instagram']['success'] === false, 'Instagram failed independently' );
	assert_true( strpos( $results['instagram']['error'], 'Permission denied' ) !== false, 'Instagram returned detailed error' );
}

function test_multi_image_gallery_carousel_publishing() {
	global $mock_http_responses, $wpdb;

	// Product 101 has gallery images [55, 56, 57]
	$product_data = new WSP_Product_Data( 101 );
	$all_urls = $product_data->get_all_image_urls();

	assert_true( count( $all_urls ) >= 3, 'Product data retrieves all featured and gallery image URLs' );

	// Mock FB multi-photo upload and feed post
	$mock_http_responses['10987654321/photos'] = [
		'response' => [ 'code' => 200 ],
		'body'     => json_encode( [ 'id' => 'fb_photo_fbid_123' ] ),
	];
	$mock_http_responses['10987654321/feed'] = [
		'response' => [ 'code' => 200 ],
		'body'     => json_encode( [ 'id' => 'fb_multi_photo_post_789' ] ),
	];

	$fb_res = WSP_Facebook::publish( [
		'caption'      => 'Multi photo test',
		'gallery_urls' => $all_urls,
	] );

	assert_true( $fb_res['success'] === true, 'Facebook multi-photo post published with all images' );
	assert_true( $fb_res['post_id'] === 'fb_multi_photo_post_789', 'Facebook post ID received for carousel' );
}
