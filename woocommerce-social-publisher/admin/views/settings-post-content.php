<?php
/**
 * Settings Tab: Post Content
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fields = [
	'field_title'             => [ 'label' => __( 'Product Title', 'woocommerce-social-publisher' ), 'desc' => __( 'Includes the main title of the WooCommerce product.', 'woocommerce-social-publisher' ) ],
	'field_description'       => [ 'label' => __( 'Product Description', 'woocommerce-social-publisher' ), 'desc' => __( 'Includes the full WooCommerce product description (HTML tags stripped).', 'woocommerce-social-publisher' ) ],
	'field_short_description' => [ 'label' => __( 'Product Short Description', 'woocommerce-social-publisher' ), 'desc' => __( 'Includes the brief excerpt/short description.', 'woocommerce-social-publisher' ) ],
	'field_regular_price'     => [ 'label' => __( 'Regular Price', 'woocommerce-social-publisher' ), 'desc' => __( 'Formatted store regular price.', 'woocommerce-social-publisher' ) ],
	'field_sale_price'        => [ 'label' => __( 'Sale Price', 'woocommerce-social-publisher' ), 'desc' => __( 'Special sale price (automatically omitted if product is not on sale).', 'woocommerce-social-publisher' ) ],
	'field_sku'               => [ 'label' => __( 'Product SKU', 'woocommerce-social-publisher' ), 'desc' => __( 'Stock keeping unit identifier.', 'woocommerce-social-publisher' ) ],
	'field_product_url'       => [ 'label' => __( 'Product URL', 'woocommerce-social-publisher' ), 'desc' => __( 'Public WooCommerce customer-facing permalink.', 'woocommerce-social-publisher' ) ],
	'field_product_images'    => [ 'label' => __( 'Product Images', 'woocommerce-social-publisher' ), 'desc' => __( 'Featured or gallery images uploaded to Facebook and Instagram.', 'woocommerce-social-publisher' ) ],
	'field_category'          => [ 'label' => __( 'Product Category', 'woocommerce-social-publisher' ), 'desc' => __( 'Categories assigned to the product.', 'woocommerce-social-publisher' ) ],
	'field_tags'              => [ 'label' => __( 'Product Tags', 'woocommerce-social-publisher' ), 'desc' => __( 'Tags assigned to the product.', 'woocommerce-social-publisher' ) ],
	'field_custom_text'       => [ 'label' => __( 'Custom Text', 'woocommerce-social-publisher' ), 'desc' => __( 'Global custom text snippet defined below.', 'woocommerce-social-publisher' ) ],
	'field_hashtags'          => [ 'label' => __( 'Hashtags', 'woocommerce-social-publisher' ), 'desc' => __( 'Global hashtags defined below.', 'woocommerce-social-publisher' ) ],
];
?>

<div class="wsp-section-box">
	<h2><?php esc_html_e( 'Enabled Post Content Fields', 'woocommerce-social-publisher' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Select which WooCommerce product attributes should be included in generated social media posts. If a field is unchecked, its placeholder will resolve to empty.', 'woocommerce-social-publisher' ); ?>
	</p>

	<div class="wsp-field-grid">
		<?php foreach ( $fields as $key => $info ) : ?>
			<div class="wsp-field-card">
				<label class="wsp-switch">
					<input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="1" <?php checked( ! empty( $settings[ $key ] ) ); ?>>
					<span class="wsp-slider round"></span>
				</label>
				<div class="wsp-field-card-text">
					<strong><?php echo esc_html( $info['label'] ); ?></strong>
					<span><?php echo esc_html( $info['desc'] ); ?></span>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>

<div class="wsp-section-box">
	<h2><?php esc_html_e( 'Global Custom Text', 'woocommerce-social-publisher' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'This text is inserted wherever {custom_text} appears in the post template, provided "Custom Text" is enabled above. Line breaks are preserved.', 'woocommerce-social-publisher' ); ?>
	</p>
	<textarea name="custom_text" rows="4" class="large-text code wsp-textarea" placeholder="<?php esc_attr_e( 'e.g. Visit our store today for limited-time offers! Free shipping on orders over $50.', 'woocommerce-social-publisher' ); ?>"><?php echo esc_textarea( $settings['custom_text'] ); ?></textarea>
</div>

<div class="wsp-section-box">
	<h2><?php esc_html_e( 'Global Hashtags', 'woocommerce-social-publisher' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Enter one or more hashtags. Inserted wherever {hashtags} appears in your post template, provided "Hashtags" is enabled above.', 'woocommerce-social-publisher' ); ?>
	</p>
	<textarea name="hashtags" rows="3" class="large-text code wsp-textarea" placeholder="<?php esc_attr_e( 'e.g. #SkincareLovers #LipstickAddict #FlawlessBase #GlowUp', 'woocommerce-social-publisher' ); ?>"><?php echo esc_textarea( $settings['hashtags'] ); ?></textarea>
</div>
