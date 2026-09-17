<?php
/**
 * Product Data Extractor for WooCommerce Social Publisher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_Product_Data {

	/**
	 * @var WC_Product|null
	 */
	protected $product;

	/**
	 * @var int
	 */
	protected $product_id;

	/**
	 * Constructor
	 *
	 * @param int|WC_Product $product
	 */
	public function __construct( $product ) {
		if ( $product instanceof WC_Product ) {
			$this->product = $product;
			$this->product_id = $product->get_id();
		} else {
			$this->product_id = absint( $product );
			$this->product = function_exists( 'wc_get_product' ) ? wc_get_product( $this->product_id ) : null;
		}
	}

	/**
	 * Check if product is valid
	 *
	 * @return bool
	 */
	public function is_valid() {
		return $this->product instanceof WC_Product;
	}

	/**
	 * Get Product ID
	 *
	 * @return int
	 */
	public function get_id() {
		return $this->product_id;
	}

	/**
	 * Get Product Title
	 *
	 * @return string
	 */
	public function get_title() {
		if ( ! $this->is_valid() ) {
			return '';
		}
		return html_entity_decode( $this->product->get_name(), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Get SKU
	 *
	 * @return string
	 */
	public function get_sku() {
		if ( ! $this->is_valid() ) {
			return '';
		}
		return (string) $this->product->get_sku();
	}

	/**
	 * Get Currency representation (symbol or code based on settings)
	 *
	 * @return string
	 */
	public function get_currency() {
		$display = WSP_Settings::get( 'currency_display', 'symbol' );

		if ( ! function_exists( 'get_woocommerce_currency' ) ) {
			return 'USD';
		}

		if ( 'code' === $display ) {
			return get_woocommerce_currency();
		}

		return function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) : get_woocommerce_currency();
	}

	/**
	 * Get formatted regular price
	 *
	 * @return string
	 */
	public function get_regular_price() {
		if ( ! $this->is_valid() ) {
			return '';
		}

		if ( $this->product->is_type( 'variable' ) ) {
			$min = $this->product->get_variation_regular_price( 'min', true );
			$max = $this->product->get_variation_regular_price( 'max', true );
			if ( $min === $max || empty( $max ) ) {
				return $this->format_price_value( $min );
			}
			return $this->format_price_value( $min ) . ' - ' . $this->format_price_value( $max );
		}

		$price = $this->product->get_regular_price();
		if ( '' === $price || null === $price ) {
			$price = $this->product->get_price();
		}

		return $this->format_price_value( $price );
	}

	/**
	 * Get formatted sale price (returns empty string if not on sale)
	 *
	 * @return string
	 */
	public function get_sale_price() {
		if ( ! $this->is_valid() ) {
			return '';
		}

		if ( ! $this->product->is_on_sale() ) {
			return '';
		}

		if ( $this->product->is_type( 'variable' ) ) {
			$min = $this->product->get_variation_sale_price( 'min', true );
			$max = $this->product->get_variation_sale_price( 'max', true );
			if ( empty( $min ) ) {
				return '';
			}
			if ( $min === $max || empty( $max ) ) {
				return $this->format_price_value( $min );
			}
			return $this->format_price_value( $min ) . ' - ' . $this->format_price_value( $max );
		}

		$sale_price = $this->product->get_sale_price();
		if ( '' === $sale_price || null === $sale_price || (float) $sale_price <= 0 ) {
			return '';
		}

		return $this->format_price_value( $sale_price );
	}

	/**
	 * Format numeric price value according to WooCommerce decimals and separators
	 *
	 * @param float|string $price
	 * @return string
	 */
	public function format_price_value( $price ) {
		if ( '' === $price || null === $price ) {
			return '';
		}

		$num_decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;
		$dec_point    = function_exists( 'wc_get_price_decimal_separator' ) ? wc_get_price_decimal_separator() : '.';
		$thousands_sep= function_exists( 'wc_get_price_thousand_separator' ) ? wc_get_price_thousand_separator() : ',';

		$formatted = number_format( (float) $price, $num_decimals, $dec_point, $thousands_sep );

		// Clean up trailing zeros if decimals are .00 and setting allows, or keep standard
		return $formatted;
	}

	/**
	 * Get Short Description (clean plain text)
	 *
	 * @return string
	 */
	public function get_short_description() {
		if ( ! $this->is_valid() ) {
			return '';
		}
		$raw = $this->product->get_short_description();
		return $this->clean_text( $raw );
	}

	/**
	 * Get Full Description (clean plain text)
	 *
	 * @return string
	 */
	public function get_description() {
		if ( ! $this->is_valid() ) {
			return '';
		}
		$raw = $this->product->get_description();
		return $this->clean_text( $raw );
	}

	/**
	 * Get Public Product Permalink
	 * Note: Always uses the customer-facing permalink, never admin URL
	 *
	 * @return string
	 */
	public function get_permalink() {
		if ( ! $this->is_valid() ) {
			return '';
		}
		return (string) get_permalink( $this->product_id );
	}

	/**
	 * Get Featured Image URL
	 *
	 * @return string
	 */
	public function get_featured_image_url() {
		if ( ! $this->is_valid() ) {
			return '';
		}

		$image_id = $this->product->get_image_id();
		if ( ! $image_id ) {
			return '';
		}

		$url = wp_get_attachment_image_url( $image_id, 'full' );
		return $url ? $url : '';
	}

	/**
	 * Get Gallery Image URLs
	 *
	 * @return array
	 */
	public function get_gallery_image_urls() {
		if ( ! $this->is_valid() ) {
			return [];
		}

		$image_ids = $this->product->get_gallery_image_ids();
		$urls = [];

		if ( ! empty( $image_ids ) && is_array( $image_ids ) ) {
			foreach ( $image_ids as $id ) {
				$url = wp_get_attachment_image_url( $id, 'full' );
				if ( $url ) {
					$urls[] = [
						'id'  => $id,
						'url' => $url,
					];
				}
			}
		}

		return $urls;
	}

	/**
	 * Get All available images for this product (featured + gallery)
	 *
	 * @return array
	 */
	public function get_all_images() {
		$images = [];

		$feat_id = $this->product ? $this->product->get_image_id() : 0;
		$feat_url = $this->get_featured_image_url();

		if ( ! empty( $feat_url ) ) {
			$images[] = [
				'id'          => $feat_id,
				'url'         => $feat_url,
				'is_featured' => true,
			];
		}

		$gallery = $this->get_gallery_image_urls();
		foreach ( $gallery as $item ) {
			if ( $item['id'] !== $feat_id ) {
				$images[] = [
					'id'          => $item['id'],
					'url'         => $item['url'],
					'is_featured' => false,
				];
			}
		}

		return $images;
	}

	/**
	 * Get list of all image URLs (Featured image first, followed by all gallery images)
	 *
	 * @return array
	 */
	public function get_all_image_urls() {
		$images = $this->get_all_images();
		$urls = [];

		foreach ( $images as $img ) {
			if ( ! empty( $img['url'] ) ) {
				$urls[] = $img['url'];
			}
		}

		return array_values( array_unique( $urls ) );
	}

	/**
	 * Get Categories (comma separated)
	 *
	 * @return string
	 */
	public function get_categories() {
		if ( ! $this->is_valid() ) {
			return '';
		}
		$terms = get_the_terms( $this->product_id, 'product_cat' );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}
		return implode( ', ', wp_list_pluck( $terms, 'name' ) );
	}

	/**
	 * Get Tags (comma separated)
	 *
	 * @return string
	 */
	public function get_tags() {
		if ( ! $this->is_valid() ) {
			return '';
		}
		$terms = get_the_terms( $this->product_id, 'product_tag' );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}
		return implode( ', ', wp_list_pluck( $terms, 'name' ) );
	}

	/**
	 * Clean text: strip HTML tags, decode entities, normalize line breaks
	 *
	 * @param string $text
	 * @return string
	 */
	protected function clean_text( $text ) {
		if ( empty( $text ) ) {
			return '';
		}
		// Convert <p> and <br> to line breaks
		$text = preg_replace( '/<br\s*\/?>/i', "\n", $text );
		$text = preg_replace( '/<\/p>/i', "\n\n", $text );
		$text = wp_strip_all_tags( $text );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		return trim( $text );
	}
}
