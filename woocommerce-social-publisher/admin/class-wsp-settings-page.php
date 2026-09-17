<?php
/**
 * Settings Page Controller for WooCommerce Social Publisher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_Settings_Page {

	/**
	 * Initialize settings hooks and AJAX actions
	 */
	public static function init() {
		add_action( 'admin_init', [ __CLASS__, 'handle_actions' ] );
		add_action( 'wp_ajax_wsp_test_connection', [ __CLASS__, 'ajax_test_connection' ] );
		add_action( 'wp_ajax_wsp_reset_template', [ __CLASS__, 'ajax_reset_template' ] );
	}

	/**
	 * Handle OAuth callback and form submissions
	 */
	public static function handle_actions() {
		if ( ! is_admin() || ! WSP_Security::current_user_can_manage() ) {
			return;
		}

		// Handle OAuth callback
		if ( isset( $_GET['wsp_action'] ) && 'oauth_callback' === $_GET['wsp_action'] ) {
			$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
			$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';

			if ( ! empty( $code ) && ! empty( $state ) ) {
				$result = WSP_Meta_Auth::handle_callback( $code, $state );
				$type = $result['success'] ? 'updated' : 'error';
				add_settings_error( 'wsp_settings_messages', 'wsp_oauth', $result['message'], $type );
			}
		}

		// Handle Form Submissions
		if ( isset( $_POST['wsp_save_settings'] ) ) {
			check_admin_referer( 'wsp_settings_save_nonce', 'wsp_nonce' );

			$tab = isset( $_POST['wsp_current_tab'] ) ? sanitize_key( $_POST['wsp_current_tab'] ) : 'general';
			$current = WSP_Settings::get_all();

			switch ( $tab ) {
				case 'general':
					$current['default_platform']         = sanitize_text_field( $_POST['default_platform'] );
					$current['default_image_source']     = sanitize_text_field( $_POST['default_image_source'] );
					$current['currency_display']         = sanitize_text_field( $_POST['currency_display'] );
					$current['duplicate_behavior']       = sanitize_text_field( $_POST['duplicate_behavior'] );
					$current['delete_data_on_uninstall'] = ! empty( $_POST['delete_data_on_uninstall'] ) ? 1 : 0;
					break;

				case 'post_content':
					$fields = [
						'field_title', 'field_description', 'field_short_description',
						'field_regular_price', 'field_sale_price', 'field_sku',
						'field_product_url', 'field_product_images', 'field_category',
						'field_tags', 'field_custom_text', 'field_hashtags'
					];
					foreach ( $fields as $f ) {
						$current[ $f ] = ! empty( $_POST[ $f ] ) ? 1 : 0;
					}
					$current['custom_text'] = sanitize_textarea_field( wp_unslash( $_POST['custom_text'] ) );
					$current['hashtags']    = sanitize_textarea_field( wp_unslash( $_POST['hashtags'] ) );
					break;

				case 'template':
					if ( isset( $_POST['post_template'] ) ) {
						$current['post_template'] = sanitize_textarea_field( wp_unslash( $_POST['post_template'] ) );
					}
					break;

				case 'facebook':
					if ( isset( $_POST['meta_app_id'] ) ) {
						$current['meta_app_id'] = sanitize_text_field( wp_unslash( $_POST['meta_app_id'] ) );
					}
					if ( ! empty( $_POST['meta_app_secret'] ) && strpos( $_POST['meta_app_secret'], '•••' ) === false ) {
						$current['meta_app_secret'] = sanitize_text_field( wp_unslash( $_POST['meta_app_secret'] ) );
					}
					if ( isset( $_POST['meta_api_version'] ) ) {
						$current['meta_api_version'] = sanitize_text_field( wp_unslash( $_POST['meta_api_version'] ) );
					}
					if ( isset( $_POST['fb_page_select'] ) && ! empty( $_POST['fb_page_select'] ) ) {
						WSP_Meta_Auth::select_active_page( sanitize_text_field( $_POST['fb_page_select'] ) );
						$current = WSP_Settings::get_all(); // Refresh after page selection
					}
					break;

				case 'scheduling':
					if ( isset( $_POST['schedule_default_delay_mins'] ) ) {
						$current['schedule_default_delay_mins'] = absint( $_POST['schedule_default_delay_mins'] );
					}
					break;
			}

			WSP_Settings::update_all( $current );
			add_settings_error( 'wsp_settings_messages', 'wsp_saved', __( 'Settings saved successfully.', 'woocommerce-social-publisher' ), 'updated' );
		}

		// Handle Disconnect action
		if ( isset( $_GET['wsp_action'] ) && 'disconnect' === $_GET['wsp_action'] ) {
			check_admin_referer( 'wsp_disconnect_nonce' );
			WSP_Meta_Auth::disconnect();
			wp_safe_redirect( admin_url( 'admin.php?page=woocommerce-social-publisher&tab=facebook&disconnected=1' ) );
			exit;
		}
	}

	/**
	 * Render settings page view with navigation tabs
	 */
	public static function render() {
		WSP_Security::check_permissions_or_abort();

		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
		$tabs = [
			'general'      => __( 'General', 'woocommerce-social-publisher' ),
			'post_content' => __( 'Post Content', 'woocommerce-social-publisher' ),
			'template'     => __( 'Post Template', 'woocommerce-social-publisher' ),
			'facebook'     => __( 'Facebook', 'woocommerce-social-publisher' ),
			'instagram'    => __( 'Instagram', 'woocommerce-social-publisher' ),
			'scheduling'   => __( 'Scheduling', 'woocommerce-social-publisher' ),
		];

		$settings = WSP_Settings::get_all();
		?>
		<div class="wrap wsp-wrap">
			<div class="wsp-header-bar">
				<div class="wsp-header-title">
					<h1><?php esc_html_e( 'WooCommerce Social Publisher', 'woocommerce-social-publisher' ); ?></h1>
					<span class="wsp-version-badge">v<?php echo esc_html( WSP_VERSION ); ?></span>
				</div>
				<div class="wsp-header-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wsp-history' ) ); ?>" class="button">
						<?php esc_html_e( 'View Publishing History', 'woocommerce-social-publisher' ); ?> &rarr;
					</a>
				</div>
			</div>

			<?php settings_errors( 'wsp_settings_messages' ); ?>

			<nav class="nav-tab-wrapper wsp-nav-tabs">
				<?php foreach ( $tabs as $tab_key => $tab_title ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=woocommerce-social-publisher&tab=' . $tab_key ) ); ?>" 
					   class="nav-tab <?php echo ( $current_tab === $tab_key ) ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $tab_title ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="wsp-tab-content">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=woocommerce-social-publisher&tab=' . $current_tab ) ); ?>">
					<?php wp_nonce_field( 'wsp_settings_save_nonce', 'wsp_nonce' ); ?>
					<input type="hidden" name="wsp_current_tab" value="<?php echo esc_attr( $current_tab ); ?>">

					<?php
					$view_file = WSP_PATH . 'admin/views/settings-' . str_replace( '_', '-', $current_tab ) . '.php';
					if ( file_exists( $view_file ) ) {
						include $view_file;
					} else {
						include WSP_PATH . 'admin/views/settings-general.php';
					}
					?>

					<?php if ( 'instagram' !== $current_tab ) : ?>
						<p class="submit">
							<button type="submit" name="wsp_save_settings" class="button button-primary button-hero">
								<?php esc_html_e( 'Save Changes', 'woocommerce-social-publisher' ); ?>
							</button>
						</p>
					<?php endif; ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler: Test Meta connection
	 */
	public static function ajax_test_connection() {
		WSP_Security::verify_ajax( 'wsp_settings_ajax_nonce', 'nonce' );

		$results = WSP_Meta_Auth::test_connection();
		wp_send_json_success( $results );
	}

	/**
	 * AJAX handler: Reset template to default
	 */
	public static function ajax_reset_template() {
		WSP_Security::verify_ajax( 'wsp_settings_ajax_nonce', 'nonce' );

		$defaults = WSP_Settings::get_defaults();
		wp_send_json_success( [ 'default_template' => $defaults['post_template'] ] );
	}
}
