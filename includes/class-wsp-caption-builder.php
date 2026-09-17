<?php
/**
 * Caption Builder Service for WooCommerce Social Publisher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_Caption_Builder {

	/**
	 * Build caption for a product using active settings and template
	 *
	 * @param int|WC_Product $product
	 * @param string|null $custom_template Optional override template
	 * @return string
	 */
	public static function build( $product, $custom_template = null ) {
		$product_data = new WSP_Product_Data( $product );
		if ( ! $product_data->is_valid() ) {
			return '';
		}

		$settings = WSP_Settings::get_all();
		$template = null !== $custom_template ? $custom_template : $settings['post_template'];

		// Prepare placeholder values based on enabled/disabled fields in Settings
		$tokens = [
			'{product_title}'     => ! empty( $settings['field_title'] ) ? $product_data->get_title() : '',
			'{description}'       => ! empty( $settings['field_description'] ) ? $product_data->get_description() : '',
			'{short_description}' => ! empty( $settings['field_short_description'] ) ? $product_data->get_short_description() : '',
			'{regular_price}'     => ! empty( $settings['field_regular_price'] ) ? $product_data->get_regular_price() : '',
			'{sale_price}'        => ! empty( $settings['field_sale_price'] ) ? $product_data->get_sale_price() : '',
			'{currency}'          => $product_data->get_currency(),
			'{sku}'               => ! empty( $settings['field_sku'] ) ? $product_data->get_sku() : '',
			'{product_url}'       => ! empty( $settings['field_product_url'] ) ? $product_data->get_permalink() : '',
			'{category}'          => ! empty( $settings['field_category'] ) ? $product_data->get_categories() : '',
			'{tags}'              => ! empty( $settings['field_tags'] ) ? $product_data->get_tags() : '',
			'{custom_text}'       => ! empty( $settings['field_custom_text'] ) ? trim( (string) $settings['custom_text'] ) : '',
			'{hashtags}'          => ! empty( $settings['field_hashtags'] ) ? trim( (string) $settings['hashtags'] ) : '',
		];

		// Render through template engine
		$caption = WSP_Template_Engine::render( $template, $tokens );

		return apply_filters( 'wsp_generated_caption', $caption, $product_data, $settings );
	}
}
