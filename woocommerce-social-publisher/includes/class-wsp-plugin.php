<?php
/**
 * Main Plugin Orchestrator Singleton
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WSP_Plugin {

	/**
	 * @var WSP_Plugin|null
	 */
	protected static $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return WSP_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	protected function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize plugin hooks
	 */
	protected function init_hooks() {
		// Load text domain for translations
		add_action( 'init', [ $this, 'load_textdomain' ] );

		// Initialize Background Queue
		WSP_Queue::init();

		// Initialize Admin components if in admin
		if ( is_admin() ) {
			WSP_Admin::init();
			WSP_Bulk_Actions::init();
			WSP_Preview_Page::init();
			WSP_Settings_Page::init();
			WSP_History_Page::init();
		}
	}

	/**
	 * Load translation files
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'woocommerce-social-publisher',
			false,
			dirname( WSP_BASENAME ) . '/languages'
		);
	}
}
