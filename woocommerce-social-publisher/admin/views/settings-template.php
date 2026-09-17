<?php
/**
 * Settings Tab: Post Template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tokens = WSP_Template_Engine::get_tokens_info();
?>

<div class="wsp-section-box">
	<div class="wsp-section-header-flex">
		<div>
			<h2><?php esc_html_e( 'Social Media Post Template', 'woocommerce-social-publisher' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Define the layout and formatting of generated social media posts. Click any placeholder below to insert it at your cursor.', 'woocommerce-social-publisher' ); ?>
			</p>
		</div>
		<div>
			<button type="button" id="wsp-reset-template-btn" class="button button-secondary">
				<span class="dashicons dashicons-undo"></span>
				<?php esc_html_e( 'Reset to Default Template', 'woocommerce-social-publisher' ); ?>
			</button>
		</div>
	</div>

	<div class="wsp-template-editor-wrap">
		<textarea name="post_template" id="wsp_post_template" rows="10" class="large-text code wsp-template-editor" spellcheck="false"><?php echo esc_textarea( $settings['post_template'] ); ?></textarea>
	</div>

	<div class="wsp-tokens-container">
		<h3><?php esc_html_e( 'Available Placeholders (Click to insert)', 'woocommerce-social-publisher' ); ?></h3>
		<div class="wsp-token-chips">
			<?php foreach ( $tokens as $token => $label ) : ?>
				<button type="button" class="wsp-token-chip" data-token="<?php echo esc_attr( $token ); ?>" title="<?php echo esc_attr( $label ); ?>">
					<code><?php echo esc_html( $token ); ?></code>
					<span><?php echo esc_html( $label ); ?></span>
				</button>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="wsp-template-hints">
		<div class="notice notice-info inline">
			<p>
				<strong><?php esc_html_e( 'Smart Formatting Rules:', 'woocommerce-social-publisher' ); ?></strong><br>
				• <?php esc_html_e( 'If a product is not on sale, any line containing {sale_price} is automatically omitted.', 'woocommerce-social-publisher' ); ?><br>
				• <?php esc_html_e( 'If a field is disabled in "Post Content", its placeholder resolves to an empty value.', 'woocommerce-social-publisher' ); ?><br>
				• <?php esc_html_e( 'Multiple consecutive blank lines are automatically collapsed to ensure a clean post.', 'woocommerce-social-publisher' ); ?>
			</p>
		</div>
	</div>
</div>
