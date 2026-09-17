<?php
/**
 * Admin Controller for WooCommerce Social Publisher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_Admin {

	/**
	 * Initialize admin hooks
	 */
	public static function init() {
		add_action( 'admin_menu', [ __CLASS__, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	/**
	 * Register admin menu items under WooCommerce
	 */
	public static function register_menus() {
		// Main settings page
		add_submenu_page(
			'woocommerce',
			__( 'Social Publisher', 'woocommerce-social-publisher' ),
			__( 'Social Publisher', 'woocommerce-social-publisher' ),
			WSP_Security::CAPABILITY,
			'woocommerce-social-publisher',
			[ 'WSP_Settings_Page', 'render' ]
		);

		// History submenu page
		add_submenu_page(
			'woocommerce',
			__( 'Publishing History', 'woocommerce-social-publisher' ),
			__( 'Social History', 'woocommerce-social-publisher' ),
			WSP_Security::CAPABILITY,
			'wsp-history',
			[ 'WSP_History_Page', 'render' ]
		);

		// Hidden preview subpage
		add_submenu_page(
			null, // No parent menu = hidden from navigation
			__( 'Social Publisher Preview', 'woocommerce-social-publisher' ),
			__( 'Social Publisher Preview', 'woocommerce-social-publisher' ),
			WSP_Security::CAPABILITY,
			'wsp-preview',
			[ 'WSP_Preview_Page', 'render' ]
		);
	}

	/**
	 * Enqueue admin stylesheets and scripts
	 *
	 * @param string $hook
	 */
	public static function enqueue_assets( $hook ) {
		// Only enqueue on our plugin admin pages
		$plugin_pages = [
			'woocommerce_page_woocommerce-social-publisher',
			'woocommerce_page_wsp-history',
			'admin_page_wsp-preview',
		];

		if ( ! in_array( $hook, $plugin_pages, true ) ) {
			return;
		}

		// Main admin stylesheet
		$admin_css_ver = file_exists( WSP_PATH . 'assets/css/admin-style.css' ) ? filemtime( WSP_PATH . 'assets/css/admin-style.css' ) : WSP_VERSION;
		wp_enqueue_style(
			'wsp-admin-style',
			WSP_URL . 'assets/css/admin-style.css',
			[],
			$admin_css_ver
		);

		// Preview stylesheet
		if ( 'admin_page_wsp-preview' === $hook ) {
			$preview_css_ver = file_exists( WSP_PATH . 'assets/css/preview.css' ) ? filemtime( WSP_PATH . 'assets/css/preview.css' ) : WSP_VERSION;
			$preview_js_ver  = file_exists( WSP_PATH . 'assets/js/preview.js' ) ? filemtime( WSP_PATH . 'assets/js/preview.js' ) : WSP_VERSION;

			wp_enqueue_style(
				'wsp-preview-style',
				WSP_URL . 'assets/css/preview.css',
				[ 'wsp-admin-style' ],
				$preview_css_ver
			);

			wp_enqueue_script(
				'wsp-preview-js',
				WSP_URL . 'assets/js/preview.js',
				[ 'jquery' ],
				$preview_js_ver,
				true
			);

			wp_localize_script( 'wsp-preview-js', 'wspPreviewData', [
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'wsp_preview_ajax_nonce' ),
				'historyUrl'=> admin_url( 'admin.php?page=wsp-history' ),
				'i18n'      => [
					'publishing'      => __( 'Publishing...', 'woocommerce-social-publisher' ),
					'published'       => __( 'Published', 'woocommerce-social-publisher' ),
					'failed'          => __( 'Failed', 'woocommerce-social-publisher' ),
					'retry'           => __( 'Retry', 'woocommerce-social-publisher' ),
					'confirmCancel'   => __( 'Are you sure you want to cancel? Unsaved custom edits will be lost.', 'woocommerce-social-publisher' ),
					'scheduledNotice' => __( 'Scheduled successfully! View in Publishing History.', 'woocommerce-social-publisher' ),
				],
			] );
		}

		// Settings script
		if ( 'woocommerce_page_woocommerce-social-publisher' === $hook ) {
			wp_enqueue_script(
				'wsp-settings-js',
				WSP_URL . 'assets/js/settings.js',
				[ 'jquery' ],
				WSP_VERSION,
				true
			);

			wp_localize_script( 'wsp-settings-js', 'wspSettingsData', [
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wsp_settings_ajax_nonce' ),
				'i18n'    => [
					'testing'        => __( 'Testing connection...', 'woocommerce-social-publisher' ),
					'confirmReset'   => __( 'Reset template to default? Your current customizations will be overwritten.', 'woocommerce-social-publisher' ),
					'copied'         => __( 'Copied to clipboard!', 'woocommerce-social-publisher' ),
				],
			] );
		}

		// History script
		if ( 'woocommerce_page_wsp-history' === $hook ) {
			wp_enqueue_script(
				'wsp-history-js',
				WSP_URL . 'assets/js/history.js',
				[ 'jquery' ],
				WSP_VERSION,
				true
			);

			wp_localize_script( 'wsp-history-js', 'wspHistoryData', [
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'wsp_history_ajax_nonce' ),
				'i18n'    => [
					'retrying'       => __( 'Retrying...', 'woocommerce-social-publisher' ),
					'confirmDelete'  => __( 'Are you sure you want to delete this log entry?', 'woocommerce-social-publisher' ),
				],
			] );
		}
	}
}
