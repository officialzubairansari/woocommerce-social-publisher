<?php
/**
 * Post Template Engine for WooCommerce Social Publisher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_Template_Engine {

	/**
	 * Available placeholder tokens
	 */
	const TOKENS = [
		'{product_title}',
		'{description}',
		'{short_description}',
		'{regular_price}',
		'{sale_price}',
		'{currency}',
		'{sku}',
		'{product_url}',
		'{category}',
		'{tags}',
		'{custom_text}',
		'{hashtags}',
	];

	/**
	 * Render template with token values and cleanup
	 *
	 * @param string $template
	 * @param array $token_values [ '{token}' => 'value' ]
	 * @return string
	 */
	public static function render( $template, $token_values ) {
		if ( empty( $template ) ) {
			return '';
		}

		$lines = explode( "\n", str_replace( "\r\n", "\n", $template ) );
		$processed_lines = [];

		$is_sale_empty = empty( $token_values['{sale_price}'] );

		foreach ( $lines as $line ) {
			// If sale price is empty and this line contains {sale_price}, drop the entire line
			if ( $is_sale_empty && strpos( $line, '{sale_price}' ) !== false ) {
				continue;
			}

			// If SKU is empty/disabled and line contains {sku}
			if ( empty( $token_values['{sku}'] ) && strpos( $line, '{sku}' ) !== false && trim( str_replace( [ '{sku}', 'SKU:', 'SKU :' ], '', $line ) ) === '' ) {
				continue;
			}

			// If category is empty/disabled and line only has category
			if ( empty( $token_values['{category}'] ) && strpos( $line, '{category}' ) !== false && trim( str_replace( [ '{category}', 'Category:', 'Categories:' ], '', $line ) ) === '' ) {
				continue;
			}

			// If tags is empty/disabled and line only has tags
			if ( empty( $token_values['{tags}'] ) && strpos( $line, '{tags}' ) !== false && trim( str_replace( [ '{tags}', 'Tags:', 'Tag:' ], '', $line ) ) === '' ) {
				continue;
			}

			// Replace all placeholders in this line
			$rendered_line = strtr( $line, $token_values );

			// Also remove any remaining unreplaced tokens if any
			$rendered_line = preg_replace( '/\{[a-z0-9_]+\}/i', '', $rendered_line );

			$processed_lines[] = rtrim( $rendered_line );
		}

		$output = implode( "\n", $processed_lines );

		// Clean up unnecessary blank lines (normalize 3 or more newlines to 2)
		$output = preg_replace( "/\n{3,}/", "\n\n", $output );

		return trim( $output );
	}

	/**
	 * Get list of all supported tokens with descriptions
	 *
	 * @return array
	 */
	public static function get_tokens_info() {
		return [
			'{product_title}'     => __( 'WooCommerce Product Title', 'woocommerce-social-publisher' ),
			'{description}'       => __( 'Full Product Description (HTML stripped)', 'woocommerce-social-publisher' ),
			'{short_description}' => __( 'Short Product Description', 'woocommerce-social-publisher' ),
			'{regular_price}'     => __( 'Regular Price (formatted)', 'woocommerce-social-publisher' ),
			'{sale_price}'        => __( 'Sale Price (omitted if product is not on sale)', 'woocommerce-social-publisher' ),
			'{currency}'          => __( 'Store Currency Symbol or Code', 'woocommerce-social-publisher' ),
			'{sku}'               => __( 'Product SKU', 'woocommerce-social-publisher' ),
			'{product_url}'       => __( 'Public Product Permalink', 'woocommerce-social-publisher' ),
			'{category}'          => __( 'Assigned Product Categories', 'woocommerce-social-publisher' ),
			'{tags}'              => __( 'Assigned Product Tags', 'woocommerce-social-publisher' ),
			'{custom_text}'       => __( 'Global Custom Text configured in Settings', 'woocommerce-social-publisher' ),
			'{hashtags}'          => __( 'Hashtags configured in Settings', 'woocommerce-social-publisher' ),
		];
	}
}
