<?php
/**
 * Settings Tab: Facebook & Meta OAuth Configuration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$redirect_uri   = WSP_Settings::get_oauth_redirect_uri();
$is_connected   = WSP_Settings::is_facebook_connected();
$available_pages= WSP_Settings::get( 'available_pages', [] );
$active_page_id = WSP_Settings::get( 'fb_page_id' );
$app_id         = WSP_Settings::get( 'meta_app_id' );
$app_secret     = WSP_Settings::get( 'meta_app_secret' );
$masked_secret  = ! empty( $app_secret ) ? WSP_Security::mask_secret( $app_secret ) : '';
$api_version    = WSP_Settings::get( 'meta_api_version', 'v21.0' );

$auth_url_or_error = ! empty( $app_id ) ? WSP_Meta_Auth::get_authorization_url() : null;
?>

<div class="wsp-section-box">
	<h2><?php esc_html_e( 'Meta Developer App Credentials', 'woocommerce-social-publisher' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'To publish to Facebook and Instagram via official Meta Graph APIs, create a Meta App in developers.facebook.com with "Business" type.', 'woocommerce-social-publisher' ); ?>
	</p>

	<table class="form-table" role="presentation">
		<tbody>
			<tr>
				<th scope="row">
					<label for="meta_app_id"><?php esc_html_e( 'Meta App ID', 'woocommerce-social-publisher' ); ?> <span class="wsp-required">*</span></label>
				</th>
				<td>
					<input type="text" name="meta_app_id" id="meta_app_id" class="regular-text" value="<?php echo esc_attr( $app_id ); ?>" placeholder="e.g. 123456789012345">
					<p class="description"><?php esc_html_e( 'Found in your Meta App Dashboard under App settings > Basic.', 'woocommerce-social-publisher' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="meta_app_secret"><?php esc_html_e( 'Meta App Secret', 'woocommerce-social-publisher' ); ?> <span class="wsp-required">*</span></label>
				</th>
				<td>
					<input type="password" name="meta_app_secret" id="meta_app_secret" class="regular-text" value="<?php echo esc_attr( $masked_secret ); ?>" placeholder="<?php esc_attr_e( 'Enter App Secret', 'woocommerce-social-publisher' ); ?>">
					<p class="description"><?php esc_html_e( 'Stored securely in WordPress. Never exposed to frontend visitors or JavaScript.', 'woocommerce-social-publisher' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label for="meta_api_version"><?php esc_html_e( 'Graph API Version', 'woocommerce-social-publisher' ); ?></label>
				</th>
				<td>
					<input type="text" name="meta_api_version" id="meta_api_version" class="small-text" value="<?php echo esc_attr( $api_version ); ?>" placeholder="v21.0">
					<p class="description"><?php esc_html_e( 'Current recommended version: v21.0 (or v26.0).', 'woocommerce-social-publisher' ); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row">
					<label><?php esc_html_e( 'Valid OAuth Redirect URI', 'woocommerce-social-publisher' ); ?></label>
				</th>
				<td>
					<div class="wsp-copy-box">
						<code id="wsp-redirect-uri"><?php echo esc_html( $redirect_uri ); ?></code>
						<button type="button" class="button button-secondary wsp-copy-btn" data-clipboard-target="#wsp-redirect-uri">
							<span class="dashicons dashicons-clipboard"></span> <?php esc_html_e( 'Copy', 'woocommerce-social-publisher' ); ?>
						</button>
					</div>
					<p class="description">
						<?php esc_html_e( 'Add this exact URL in your Meta App Dashboard under Facebook Login for Business > Settings > "Valid OAuth Redirect URIs".', 'woocommerce-social-publisher' ); ?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>
</div>

<div class="wsp-section-box">
	<h2><?php esc_html_e( 'Facebook Connection & Page Selection', 'woocommerce-social-publisher' ); ?></h2>

	<?php if ( $is_connected ) : ?>
		<div class="wsp-connected-banner">
			<div class="wsp-banner-left">
				<span class="dashicons dashicons-yes-alt"></span>
				<div>
					<strong><?php esc_html_e( 'Active Facebook Page:', 'woocommerce-social-publisher' ); ?></strong>
					<span class="wsp-page-highlight"><?php echo esc_html( $settings['fb_page_name'] ); ?> (ID: <?php echo esc_html( $settings['fb_page_id'] ); ?>)</span>
				</div>
			</div>
			<div class="wsp-banner-right">
				<button type="button" id="wsp-test-conn-btn" class="button button-secondary">
					<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Test Connection', 'woocommerce-social-publisher' ); ?>
				</button>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=woocommerce-social-publisher&tab=facebook&wsp_action=disconnect' ), 'wsp_disconnect_nonce' ) ); ?>" 
				   class="button button-link-delete" onclick="return confirm('<?php esc_attr_e( 'Disconnect from Meta? You will need to re-authenticate to publish posts.', 'woocommerce-social-publisher' ); ?>');">
					<?php esc_html_e( 'Disconnect', 'woocommerce-social-publisher' ); ?>
				</a>
			</div>
		</div>

		<div id="wsp-test-result" style="display:none;" class="wsp-test-result-box"></div>

		<?php if ( ! empty( $available_pages ) && count( $available_pages ) > 1 ) : ?>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="fb_page_select"><?php esc_html_e( 'Switch Active Page', 'woocommerce-social-publisher' ); ?></label>
						</th>
						<td>
							<select name="fb_page_select" id="fb_page_select" class="regular-text">
								<?php foreach ( $available_pages as $pid => $pinfo ) : ?>
									<option value="<?php echo esc_attr( $pid ); ?>" <?php selected( $active_page_id, $pid ); ?>>
										<?php echo esc_html( $pinfo['name'] ); ?> (<?php echo esc_html( $pid ); ?>)
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Select which Facebook Page this store publishes to. Save changes to update.', 'woocommerce-social-publisher' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		<?php endif; ?>

		<div class="wsp-reauth-row">
			<?php if ( ! is_wp_error( $auth_url_or_error ) && ! empty( $auth_url_or_error ) ) : ?>
				<a href="<?php echo esc_url( $auth_url_or_error ); ?>" class="button button-secondary">
					<span class="dashicons dashicons-image-rotate"></span>
					<?php esc_html_e( 'Re-Authorize / Refresh Pages with Meta', 'woocommerce-social-publisher' ); ?>
				</a>
			<?php endif; ?>
		</div>

	<?php else : ?>
		<div class="wsp-disconnected-prompt">
			<p><?php esc_html_e( 'Authenticate with your Meta account to connect your Facebook Page and linked Instagram Business account.', 'woocommerce-social-publisher' ); ?></p>

			<?php if ( empty( $app_id ) || empty( $app_secret ) ) : ?>
				<p class="description">
					<em><?php esc_html_e( 'Please enter and save your Meta App ID and App Secret above before connecting.', 'woocommerce-social-publisher' ); ?></em>
				</p>
			<?php elseif ( is_wp_error( $auth_url_or_error ) ) : ?>
				<div class="notice notice-error inline">
					<p><?php echo esc_html( $auth_url_or_error->get_error_message() ); ?></p>
				</div>
			<?php else : ?>
				<a href="<?php echo esc_url( $auth_url_or_error ); ?>" class="button button-primary button-hero wsp-meta-login-btn">
					<span class="dashicons dashicons-facebook"></span>
					<?php esc_html_e( 'Connect with Meta (Facebook & Instagram)', 'woocommerce-social-publisher' ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
