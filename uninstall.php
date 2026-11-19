<?php
/**
 * Uninstall: removes Compass's settings and tables. Per-post SEO fields are kept, so
 * reinstalling Compass (or importing them elsewhere) loses nothing.
 *
 * @package Compass
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;
foreach ( array( 'compass_settings', 'compass_version', 'compass_redirect_map', 'compass_indexnow_key', 'compass_indexnow_queue', 'compass_indexnow_last' ) as $compass_option ) {
	delete_option( $compass_option );
}
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}compass_redirects" ); // phpcs:ignore
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}compass_404" ); // phpcs:ignore
wp_clear_scheduled_hook( 'compass_indexnow_ping' );
