<?php
/**
 * Automated CLI Test Suite Runner for WooCommerce Social Publisher
 *
 * Run via: php tests/run-tests.php
 */

require_once __DIR__ . '/bootstrap.php';

// Assertions & Counters
global $total_assertions, $passed_assertions, $failed_assertions, $failures;
$total_assertions  = 0;
$passed_assertions = 0;
$failed_assertions = 0;
$failures          = [];

function assert_true( $condition, $message = '' ) {
	global $total_assertions, $passed_assertions, $failed_assertions, $failures;
	$total_assertions++;

	if ( $condition ) {
		$passed_assertions++;
		echo "  \033[32m✓\033[0m {$message}\n";
	} else {
		$failed_assertions++;
		$failures[] = $message;
		echo "  \033[31m✕ FAILED:\033[0m {$message}\n";
	}
}

echo "\n========================================================\n";
echo "  WooCommerce Social Publisher - Automated Test Suite\n";
echo "========================================================\n\n";

$start_time = microtime( true );

// 1. Template Engine Tests
echo "\033[1;34m[Suite 1: Post Template Engine]\033[0m\n";
require_once __DIR__ . '/test-template-engine.php';
test_template_engine_basic_replacement();
test_template_engine_omits_sale_price_line_when_not_on_sale();
test_template_engine_collapses_unnecessary_blank_lines();
test_template_engine_unresolved_tokens_cleaned();
echo "\n";

// 2. Caption Builder Tests
echo "\033[1;34m[Suite 2: Caption Builder & Product Data]\033[0m\n";
require_once __DIR__ . '/test-caption-builder.php';
test_caption_builder_requirement_7_example();
test_caption_builder_public_url_enforcement();
test_caption_builder_variable_product_pricing();
test_caption_builder_disabled_fields_resolve_empty();
echo "\n";

// 3. Duplicate Prevention & Security
echo "\033[1;34m[Suite 3: Duplicate Prevention & Security]\033[0m\n";
require_once __DIR__ . '/test-duplicate-and-security.php';
test_duplicate_prevention_flow();
test_security_secret_masking();
test_security_encryption_and_decryption();
test_image_validation_formats();
echo "\n";

// 4. Meta Graph API Publishing
echo "\033[1;34m[Suite 4: Meta Graph API Publishing & Diagnostics]\033[0m\n";
require_once __DIR__ . '/test-meta-publishing.php';
test_facebook_photo_publishing();
test_instagram_container_publishing_workflow();
test_meta_error_diagnostic_guidance();
test_independent_platform_tracking();
test_multi_image_gallery_carousel_publishing();
echo "\n";

// 5. Bulk Queue, Batch Scaling & Retries
echo "\033[1;34m[Suite 5: Bulk Actions, Queue & Scaling (1-100 Products)]\033[0m\n";
require_once __DIR__ . '/test-queue-and-batch.php';
test_bulk_actions_registration();
test_bulk_action_interception_creates_transient();
test_batch_scaling_1_to_100_products();
test_scheduler_datetime_parsing();
test_retry_system();
echo "\n";

$elapsed = round( ( microtime( true ) - $start_time ) * 1000, 2 );

echo "========================================================\n";
if ( $failed_assertions === 0 ) {
	echo "\033[1;32m  ALL TESTS PASSED! ({$passed_assertions}/{$total_assertions} assertions in {$elapsed} ms)\033[0m\n";
	echo "========================================================\n\n";
	exit( 0 );
} else {
	echo "\033[1;31m  {$failed_assertions} ASSERTION(S) FAILED out of {$total_assertions} in {$elapsed} ms\033[0m\n";
	echo "========================================================\n\n";
	exit( 1 );
}
