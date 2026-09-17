<?php
/**
 * Settings Tab: General
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fb_connected = WSP_Settings::is_facebook_connected();
$ig_connected = WSP_Settings::is_instagram_connected();
?>

<div class="wsp-section-box">
	<h2><?php esc_html_e( 'Platform Connection Status', 'woocommerce-social-publisher' ); ?></h2>
	<div class="wsp-status-cards">
		<div class="wsp-status-card <?php echo $fb_connected ? 'is-connected' : 'is-disconnected'; ?>">
			<div class="wsp-card-icon fb-icon">
				<span class="dashicons dashicons-facebook"></span>
			</div>
			<div class="wsp-card-content">
				<h3><?php esc_html_e( 'Facebook Page', 'woocommerce-social-publisher' ); ?></h3>
				<?php if ( $fb_connected ) : ?>
					<p class="status-text success">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php printf( esc_html__( 'Connected to "%s"', 'woocommerce-social-publisher' ), esc_html( $settings['fb_page_name'] ) ); ?>
					</p>
					<span class="wsp-badge"><?php printf( esc_html__( 'Page ID: %s', 'woocommerce-social-publisher' ), esc_html( $settings['fb_page_id'] ) ); ?></span>
				<?php else : ?>
					<p class="status-text error">
						<span class="dashicons dashicons-dismiss"></span>
						<?php esc_html_e( 'Not Connected', 'woocommerce-social-publisher' ); ?>
					</p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=woocommerce-social-publisher&tab=facebook' ) ); ?>" class="button button-secondary button-small">
						<?php esc_html_e( 'Connect Facebook', 'woocommerce-social-publisher' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<div class="wsp-status-card <?php echo $ig_connected ? 'is-connected' : 'is-disconnected'; ?>">
			<div class="wsp-card-icon ig-icon">
				<span class="dashicons dashicons-instagram"></span>
			</div>
			<div class="wsp-card-content">
				<h3><?php esc_html_e( 'Instagram Professional', 'woocommerce-social-publisher' ); ?></h3>
				<?php if ( $ig_connected ) : ?>
					<p class="status-text success">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php printf( esc_html__( 'Connected as @%s', 'woocommerce-social-publisher' ), esc_html( $settings['ig_username'] ) ); ?>
					</p>
					<span class="wsp-badge"><?php printf( esc_html__( 'Account ID: %s', 'woocommerce-social-publisher' ), esc_html( $settings['ig_account_id'] ) ); ?></span>
				<?php else : ?>
					<p class="status-text warning">
						<span class="dashicons dashicons-warning"></span>
						<?php esc_html_e( 'Not Linked to Page', 'woocommerce-social-publisher' ); ?>
					</p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=woocommerce-social-publisher&tab=instagram' ) ); ?>" class="button button-secondary button-small">
						<?php esc_html_e( 'View Requirements', 'woocommerce-social-publisher' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>

<div class="wsp-section-box">
	<h2><?php esc_html_e( 'Default Publishing Preferences', 'woocommerce-social-publisher' ); ?></h2>
	
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row">
					<label for="default_platform"><?php esc_html_e( 'Default Target Platform', 'woocommerce-social-publisher' ); ?></label>
				</th>
				<td>
					<select name="default_platform" id="default_platform" class="regular-text">
						<option value="both" <?php selected( $settings['default_platform'], 'both' ); ?>><?php esc_html_e( 'Facebook & Instagram', 'woocommerce-social-publisher' ); ?></option>
						<option value="facebook" <?php selected( $settings['default_platform'], 'facebook' ); ?>><?php esc_html_e( 'Facebook Page Only', 'woocommerce-social-publisher' ); ?></option>
						<option value="instagram" <?php selected( $settings['default_platform'], 'instagram' ); ?>><?php esc_html_e( 'Instagram Only', 'woocommerce-social-publisher' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Default destination selected when opening the preview screen.', 'woocommerce-social-publisher' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="default_image_source"><?php esc_html_e( 'Default Image Selection', 'woocommerce-social-publisher' ); ?></label>
				</th>
				<td>
					<select name="default_image_source" id="default_image_source" class="regular-text">
						<option value="featured" <?php selected( $settings['default_image_source'], 'featured' ); ?>><?php esc_html_e( 'WooCommerce Featured Product Image', 'woocommerce-social-publisher' ); ?></option>
						<option value="all" <?php selected( $settings['default_image_source'], 'all' ); ?>><?php esc_html_e( 'Product Gallery (Carousel Post)', 'woocommerce-social-publisher' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'You can still change or customize images per product on the preview screen.', 'woocommerce-social-publisher' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="currency_display"><?php esc_html_e( 'Price Currency Format', 'woocommerce-social-publisher' ); ?></label>
				</th>
				<td>
					<select name="currency_display" id="currency_display" class="regular-text">
						<option value="symbol" <?php selected( $settings['currency_display'], 'symbol' ); ?>>
							<?php printf( esc_html__( 'Currency Symbol (e.g. %s)', 'woocommerce-social-publisher' ), function_exists('get_woocommerce_currency_symbol') ? esc_html(get_woocommerce_currency_symbol()) : '$' ); ?>
						</option>
						<option value="code" <?php selected( $settings['currency_display'], 'code' ); ?>>
							<?php printf( esc_html__( 'Currency Code (e.g. %s)', 'woocommerce-social-publisher' ), function_exists('get_woocommerce_currency') ? esc_html(get_woocommerce_currency()) : 'USD' ); ?>
						</option>
					</select>
					<p class="description"><?php esc_html_e( 'How the {currency} placeholder token renders in social captions.', 'woocommerce-social-publisher' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="duplicate_behavior"><?php esc_html_e( 'Duplicate Prevention', 'woocommerce-social-publisher' ); ?></label>
				</th>
				<td>
					<select name="duplicate_behavior" id="duplicate_behavior" class="regular-text">
						<option value="warn" <?php selected( $settings['duplicate_behavior'], 'warn' ); ?>><?php esc_html_e( 'Show warning badge (Require "Publish Again" check)', 'woocommerce-social-publisher' ); ?></option>
						<option value="block" <?php selected( $settings['duplicate_behavior'], 'block' ); ?>><?php esc_html_e( 'Strictly block re-publishing previously posted products', 'woocommerce-social-publisher' ); ?></option>
						<option value="allow" <?php selected( $settings['duplicate_behavior'], 'allow' ); ?>><?php esc_html_e( 'Allow re-publishing without warnings', 'woocommerce-social-publisher' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Prevents accidental duplicate posts of the same WooCommerce product to Facebook or Instagram.', 'woocommerce-social-publisher' ); ?></p>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<div class="wsp-section-box">
	<h2><?php esc_html_e( 'Plugin Uninstall Data Cleanup', 'woocommerce-social-publisher' ); ?></h2>
	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row"><?php esc_html_e( 'Uninstall Behavior', 'woocommerce-social-publisher' ); ?></th>
				<td>
					<fieldset>
						<label for="delete_data_on_uninstall">
							<input type="checkbox" name="delete_data_on_uninstall" id="delete_data_on_uninstall" value="1" <?php checked( $settings['delete_data_on_uninstall'], 1 ); ?>>
							<?php esc_html_e( 'Delete all plugin data when uninstalling', 'woocommerce-social-publisher' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'By default (OFF), your publishing history and settings are preserved if the plugin is deactivated or uninstalled. Product and customer data in WooCommerce are NEVER deleted.', 'woocommerce-social-publisher' ); ?></p>
					</fieldset>
				</td>
			</tr>
		</tbody>
	</table>
</div>
