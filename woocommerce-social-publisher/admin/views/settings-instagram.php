<?php
/**
 * Settings Tab: Instagram Status & Requirements
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_ig_connected = WSP_Settings::is_instagram_connected();
$ig_id           = WSP_Settings::get( 'ig_account_id' );
$ig_username     = WSP_Settings::get( 'ig_username' );
$ig_name         = WSP_Settings::get( 'ig_name' );
$ig_pic          = WSP_Settings::get( 'ig_profile_picture_url' );
$fb_page_name    = WSP_Settings::get( 'fb_page_name' );
$fb_connected    = WSP_Settings::is_facebook_connected();
?>

<div class="wsp-section-box">
	<h2><?php esc_html_e( 'Instagram Professional Connection Status', 'woocommerce-social-publisher' ); ?></h2>

	<?php if ( $is_ig_connected ) : ?>
		<div class="wsp-account-card">
			<div class="wsp-account-avatar">
				<?php if ( ! empty( $ig_pic ) ) : ?>
					<img src="<?php echo esc_url( $ig_pic ); ?>" alt="<?php echo esc_attr( $ig_username ); ?>" width="80" height="80">
				<?php else : ?>
					<span class="dashicons dashicons-camera"></span>
				<?php endif; ?>
			</div>
			<div class="wsp-account-info">
				<h3>@<?php echo esc_html( $ig_username ); ?></h3>
				<?php if ( ! empty( $ig_name ) ) : ?>
					<p class="wsp-account-realname"><?php echo esc_html( $ig_name ); ?></p>
				<?php endif; ?>
				<div class="wsp-account-meta">
					<span class="wsp-tag success"><?php esc_html_e( 'Eligible for Content Publishing API', 'woocommerce-social-publisher' ); ?></span>
					<span class="wsp-tag">ID: <?php echo esc_html( $ig_id ); ?></span>
					<span class="wsp-tag"><?php printf( esc_html__( 'Linked to FB Page: %s', 'woocommerce-social-publisher' ), esc_html( $fb_page_name ) ); ?></span>
				</div>
			</div>
		</div>

	<?php elseif ( $fb_connected ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<strong><?php esc_html_e( 'No Instagram Business or Creator Account Linked:', 'woocommerce-social-publisher' ); ?></strong><br>
				<?php printf( esc_html__( 'Your connected Facebook Page ("%s") does not have an active Instagram Professional account connected to it in Meta Business Suite.', 'woocommerce-social-publisher' ), esc_html( $fb_page_name ) ); ?>
			</p>
		</div>
	<?php else : ?>
		<div class="notice notice-info inline">
			<p>
				<?php esc_html_e( 'Please connect your Meta account on the Facebook tab first. The linked Instagram Business or Creator account will be automatically recognized.', 'woocommerce-social-publisher' ); ?>
			</p>
		</div>
	<?php endif; ?>
</div>

<div class="wsp-section-box">
	<h2><?php esc_html_e( 'Meta Graph API Instagram Requirements Checklist', 'woocommerce-social-publisher' ); ?></h2>
	<p class="description">
		<?php esc_html_e( 'Meta strictly enforces these criteria for publishing via the official Instagram Content Publishing API:', 'woocommerce-social-publisher' ); ?>
	</p>

	<div class="wsp-checklist">
		<div class="wsp-check-item">
			<span class="dashicons dashicons-yes"></span>
			<div>
				<strong><?php esc_html_e( '1. Instagram Professional Account', 'woocommerce-social-publisher' ); ?></strong>
				<p><?php esc_html_e( 'The Instagram account must be a Business or Creator account (Personal accounts do not support API publishing).', 'woocommerce-social-publisher' ); ?></p>
			</div>
		</div>

		<div class="wsp-check-item">
			<span class="dashicons dashicons-yes"></span>
			<div>
				<strong><?php esc_html_e( '2. Connected to a Facebook Page', 'woocommerce-social-publisher' ); ?></strong>
				<p><?php esc_html_e( 'The Instagram account must be linked to your Facebook Page in Meta Business Suite under Settings > Linked Accounts.', 'woocommerce-social-publisher' ); ?></p>
			</div>
		</div>

		<div class="wsp-check-item">
			<span class="dashicons dashicons-yes"></span>
			<div>
				<strong><?php esc_html_e( '3. Required Permissions', 'woocommerce-social-publisher' ); ?></strong>
				<p><?php esc_html_e( 'Your Meta App must request "instagram_content_publish", "instagram_basic", and "pages_read_engagement".', 'woocommerce-social-publisher' ); ?></p>
			</div>
		</div>

		<div class="wsp-check-item">
			<span class="dashicons dashicons-yes"></span>
			<div>
				<strong><?php esc_html_e( '4. Public Image URLs', 'woocommerce-social-publisher' ); ?></strong>
				<p><?php esc_html_e( 'Meta servers download images from your WooCommerce store. The image URL must be publicly accessible (not localhost/intranet) with an aspect ratio between 4:5 and 1.91:1 in JPEG or PNG format.', 'woocommerce-social-publisher' ); ?></p>
			</div>
		</div>
	</div>
</div>
