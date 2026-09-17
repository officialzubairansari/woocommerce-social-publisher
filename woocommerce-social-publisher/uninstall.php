<?php
/**
 * WooCommerce Social Publisher Uninstall
 *
 * Fired when the plugin is uninstalled.
 * Preserves all WooCommerce product and customer data.
 * By default, preserves plugin history and settings unless explicitly
 * enabled in Settings -> General -> "Delete all plugin data when uninstalling".
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$settings = get_option( 'wsp_settings', [] );
$delete_data = ! empty( $settings['delete_data_on_uninstall'] );

if ( $delete_data ) {
	global $wpdb;

	// Drop custom social posts table
	$table_name = $wpdb->prefix . 'wsp_social_posts';
	$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

	// Delete plugin options
	delete_option( 'wsp_settings' );
	delete_option( 'wsp_db_version' );
	delete_option( 'wsp_oauth_state' );

	// Clear any remaining transients
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wsp_%' OR option_name LIKE '_transient_timeout_wsp_%'" );
}
