<?php
/**
 * Plugin Name: WooCommerce Social Publisher
 * Plugin URI: https://github.com/officialzubairansari/woocommerce-social-publisher
 * Description: Publish WooCommerce products directly to Facebook Pages and Instagram Business/Creator accounts via official Meta Graph API.
 * Version: 1.0.1
 * Author: Zubair Ansari
 * Author URI: https://github.com/officialzubairansari/woocommerce-social-publisher
 * Text Domain: woocommerce-social-publisher
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 9.0
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin Constants
define( 'WSP_VERSION', '1.0.1' );
define( 'WSP_FILE', __FILE__ );
define( 'WSP_PATH', plugin_dir_path( __FILE__ ) );
define( 'WSP_URL', plugin_dir_url( __FILE__ ) );
define( 'WSP_BASENAME', plugin_basename( __FILE__ ) );
define( 'WSP_DEFAULT_META_API_VERSION', 'v21.0' );

/**
 * Declare WooCommerce HPOS (High-Performance Order Storage) and feature compatibility
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
} );

/**
 * Autoload plugin classes
 */
spl_autoload_register( function ( $class ) {
	$prefix = 'WSP_';
	if ( strpos( $class, $prefix ) !== 0 ) {
		return;
	}

	$class_name = strtolower( str_replace( '_', '-', substr( $class, strlen( $prefix ) ) ) );

	$locations = [
		WSP_PATH . 'includes/class-wsp-' . $class_name . '.php',
		WSP_PATH . 'admin/class-wsp-' . $class_name . '.php',
	];

	foreach ( $locations as $file ) {
		if ( file_exists( $file ) ) {
			require_once $file;
			return;
		}
	}
} );

/**
 * Verify WooCommerce is installed and active
 */
function wsp_check_dependencies() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'wsp_woocommerce_missing_notice' );
		return false;
	}
	return true;
}

/**
 * Admin notice if WooCommerce is not active
 */
function wsp_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error is-dismissible">
		<p><strong><?php esc_html_e( 'WooCommerce Social Publisher', 'woocommerce-social-publisher' ); ?>:</strong>
		<?php esc_html_e( 'This plugin requires WooCommerce to be installed and active. Please activate WooCommerce to enable social publishing.', 'woocommerce-social-publisher' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Initialize Plugin
 */
function wsp_init_plugin() {
	if ( wsp_check_dependencies() ) {
		WSP_Plugin::get_instance();
	}
}
add_action( 'plugins_loaded', 'wsp_init_plugin', 20 );

/**
 * Activation & Deactivation hooks
 */
register_activation_hook( __FILE__, function () {
	require_once WSP_PATH . 'includes/class-wsp-database.php';
	require_once WSP_PATH . 'includes/class-wsp-settings.php';
	WSP_Database::create_tables();
	WSP_Settings::set_defaults();
} );

register_deactivation_hook( __FILE__, function () {
	require_once WSP_PATH . 'includes/class-wsp-queue.php';
	WSP_Queue::clear_scheduled_actions();
} );
