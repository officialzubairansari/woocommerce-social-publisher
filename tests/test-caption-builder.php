<?php
/**
 * Test Suite: Caption Builder & WooCommerce Product Data Integration
 */

function test_caption_builder_requirement_7_example() {
	$product = new WC_Product();
	$product->name = 'Shein – Text Pattern Women’s Nightgown';
	$product->regular_price = '4500';
	$product->sale_price = '3500';

	// Configure Settings exactly as in Requirement 7
	WSP_Settings::set( 'field_title', 1 );
	WSP_Settings::set( 'field_description', 0 );
	WSP_Settings::set( 'field_short_description', 0 );
	WSP_Settings::set( 'field_regular_price', 1 );
	WSP_Settings::set( 'field_sale_price', 1 );
	WSP_Settings::set( 'field_sku', 0 );
	WSP_Settings::set( 'field_product_url', 0 );
	WSP_Settings::set( 'field_product_images', 1 );
	WSP_Settings::set( 'field_category', 0 );
	WSP_Settings::set( 'field_tags', 0 );
	WSP_Settings::set( 'field_custom_text', 1 );
	WSP_Settings::set( 'field_hashtags', 0 );
	WSP_Settings::set( 'custom_text', "#SkincareLovers #LipstickAddict #FlawlessBase #GlowUp" );

	$caption = WSP_Caption_Builder::build( $product );

	assert_true( strpos( $caption, 'Shein – Text Pattern Women’s Nightgown' ) !== false, 'Contains product title' );
	assert_true( strpos( $caption, 'Regular Price: 4500 PKR' ) !== false, 'Contains regular price' );
	assert_true( strpos( $caption, 'Sale Price: 3500 PKR' ) !== false, 'Contains sale price' );
	assert_true( strpos( $caption, '#SkincareLovers #LipstickAddict #FlawlessBase #GlowUp' ) !== false, 'Contains custom text' );
}

function test_caption_builder_public_url_enforcement() {
	$product = new WC_Product();
	$product->id = 77;
	WSP_Settings::set( 'field_product_url', 1 );
	WSP_Settings::set( 'post_template', "{product_title}\n\n{product_url}" );

	$caption = WSP_Caption_Builder::build( $product );

	assert_true( strpos( $caption, 'https://beauties.pk/product/item-77/' ) !== false, 'Uses public product permalink' );
	assert_true( strpos( $caption, 'wp-admin' ) === false, 'Never uses admin edit URL in social caption' );
}

function test_caption_builder_variable_product_pricing() {
	$product = new WC_Product();
	$product->type = 'variable';
	$pdata = new WSP_Product_Data( $product );

	$reg = $pdata->get_regular_price();
	assert_true( ! empty( $reg ), 'Variable product regular price resolved' );
}

function test_caption_builder_disabled_fields_resolve_empty() {
	$product = new WC_Product();
	WSP_Settings::set( 'field_sku', 0 );
	WSP_Settings::set( 'post_template', "SKU: {sku}\nTitle: {product_title}" );

	$caption = WSP_Caption_Builder::build( $product );

	assert_true( strpos( $caption, 'SH-NG-001' ) === false, 'Disabled SKU field omitted' );
}
