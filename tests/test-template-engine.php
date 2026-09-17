<?php
/**
 * Test Suite: Template Engine & Placeholder Processing
 */

function test_template_engine_basic_replacement() {
	$template = "{product_title}\n\nRegular Price: {regular_price} {currency}\n\nSale Price: {sale_price} {currency}\n\n{custom_text}\n\n{hashtags}";

	$tokens = [
		'{product_title}' => 'Shein – Text Pattern Women’s Nightgown',
		'{regular_price}' => '4500',
		'{sale_price}'    => '3500',
		'{currency}'      => 'PKR',
		'{custom_text}'   => '#SkincareLovers #LipstickAddict',
		'{hashtags}'      => '#FlawlessBase #GlowUp',
	];

	$rendered = WSP_Template_Engine::render( $template, $tokens );

	assert_true( strpos( $rendered, 'Shein – Text Pattern Women’s Nightgown' ) !== false, 'Product title replaced' );
	assert_true( strpos( $rendered, 'Regular Price: 4500 PKR' ) !== false, 'Regular price and currency replaced' );
	assert_true( strpos( $rendered, 'Sale Price: 3500 PKR' ) !== false, 'Sale price and currency replaced' );
	assert_true( strpos( $rendered, '#SkincareLovers #LipstickAddict' ) !== false, 'Custom text replaced' );
	assert_true( strpos( $rendered, '#FlawlessBase #GlowUp' ) !== false, 'Hashtags replaced' );
}

function test_template_engine_omits_sale_price_line_when_not_on_sale() {
	$template = "🔥 {product_title}\n\nWas: {regular_price} {currency}\nNow: {sale_price} {currency}\n\n{custom_text}";

	$tokens = [
		'{product_title}' => 'Cotton Summer Shirt',
		'{regular_price}' => '2500',
		'{sale_price}'    => '', // Not on sale
		'{currency}'      => 'PKR',
		'{custom_text}'   => 'Limited stock!',
	];

	$rendered = WSP_Template_Engine::render( $template, $tokens );

	assert_true( strpos( $rendered, 'Was: 2500 PKR' ) !== false, 'Regular price is present' );
	assert_true( strpos( $rendered, 'Now:' ) === false, 'Sale price line is cleanly omitted when empty' );
	assert_true( strpos( $rendered, '{sale_price}' ) === false, 'No raw token left in output' );
}

function test_template_engine_collapses_unnecessary_blank_lines() {
	$template = "Title: {product_title}\n\n\n\n\n\nPrice: {regular_price}\n\n\n\n\nEnd";

	$tokens = [
		'{product_title}' => 'Test Item',
		'{regular_price}' => '100',
	];

	$rendered = WSP_Template_Engine::render( $template, $tokens );

	assert_true( strpos( $rendered, "\n\n\n" ) === false, 'No triple or more consecutive newlines exist' );
	assert_true( strpos( $rendered, "\n\n" ) !== false, 'Double newlines preserved for paragraphs' );
}

function test_template_engine_unresolved_tokens_cleaned() {
	$template = "Product: {product_title} - SKU: {sku} - Extra: {non_existent_token}";

	$tokens = [
		'{product_title}' => 'Test Item',
		'{sku}'           => '',
	];

	$rendered = WSP_Template_Engine::render( $template, $tokens );

	assert_true( strpos( $rendered, '{non_existent_token}' ) === false, 'Unknown tokens stripped cleanly' );
}
