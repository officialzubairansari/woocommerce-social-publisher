<?php
/**
 * Test Suite: Duplicate Prevention & Security Handler
 */

function test_duplicate_prevention_flow() {
	global $wpdb;
	$wpdb->posts = [];

	// Simulate existing published record
	$wpdb->insert( 'wsp_social_posts', [
		'product_id'       => 101,
		'platform'         => 'facebook',
		'status'           => 'published',
		'published_at'     => '2026-09-17 08:00:00',
		'external_post_id' => '12345_67890',
	] );

	// 1. Duplicate check with allow_duplicate = false should block with warning
	WSP_Settings::set( 'duplicate_behavior', 'warn' );
	$check1 = WSP_Duplicate_Checker::is_allowed( 101, 'facebook', false );
	assert_true( ! $check1['allowed'], 'Duplicate post detected and blocked' );
	assert_true( strpos( $check1['warning'], 'already published' ) !== false, 'Duplicate warning generated' );

	// 2. Duplicate check with allow_duplicate = true (Publish Again) should allow
	$check2 = WSP_Duplicate_Checker::is_allowed( 101, 'facebook', true );
	assert_true( $check2['allowed'], 'Duplicate allowed when "Publish Again" is checked' );

	// 3. Different platform (Instagram) should not be blocked if not yet published to IG
	$check3 = WSP_Duplicate_Checker::is_allowed( 101, 'instagram', false );
	assert_true( $check3['allowed'], 'Instagram allowed if not previously published to Instagram' );
}

function test_security_secret_masking() {
	$secret = '1a2b3c4d5e6f7g8h9i0j';
	$masked = WSP_Security::mask_secret( $secret );

	assert_true( strpos( $masked, '••••' ) !== false, 'Middle characters masked with bullet points' );
	assert_true( substr( $masked, 0, 4 ) === '1a2b', 'Leading visible chars preserved' );
	assert_true( substr( $masked, -4 ) === '9i0j', 'Trailing visible chars preserved' );
}

function test_security_encryption_and_decryption() {
	$raw_token = 'EAABwzLIX123456789LongLivedTokenSampleSecretValue';
	$encrypted = WSP_Security::encrypt( $raw_token );

	assert_true( $encrypted !== $raw_token, 'Token was encrypted' );

	$decrypted = WSP_Security::decrypt( $encrypted );
	assert_true( $decrypted === $raw_token, 'Token correctly decrypted back to original' );
}

function test_image_validation_formats() {
	// Valid JPEG
	$res1 = WSP_Image_Validator::validate( 'https://example.com/uploads/photo.jpg', 'both' );
	assert_true( $res1['valid'], 'JPEG accepted for both platforms' );

	// GIF not supported on Instagram
	$res2 = WSP_Image_Validator::validate( 'https://example.com/uploads/anim.gif', 'instagram' );
	assert_true( ! $res2['valid'], 'GIF rejected for Instagram' );

	// Localhost warning
	$res3 = WSP_Image_Validator::validate( 'http://localhost/test/image.jpg', 'facebook' );
	assert_true( ! empty( $res3['warnings'] ), 'Localhost domain flagged with warning' );
}
