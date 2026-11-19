<?php
/**
 * Plugin Name:       Compass
 * Plugin URI:        https://www.prasatdigital.com
 * Description:       Search visibility made clear: titles and descriptions with a live Google preview, SEO checks (Khmer-aware), social cards, schema for businesses and articles, XML sitemap, redirects, a 404 monitor, IndexNow and breadcrumbs.
 * Version:           1.0.5
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Update URI:        https://www.prasatdigital.com/plugins/compass/
 * Author:            Prasat Digital
 * Author URI:        https://www.prasatdigital.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       compass
 *
 * @package Compass
 */

defined( 'ABSPATH' ) || exit;

define( 'COMPASS_VERSION', '1.0.5' );
define( 'COMPASS_FILE', __FILE__ );
define( 'COMPASS_DIR', plugin_dir_path( __FILE__ ) );
define( 'COMPASS_URL', plugin_dir_url( __FILE__ ) );

foreach ( array( 'settings', 'context', 'analysis', 'breadcrumbs', 'schema', 'head', 'sitemap', 'redirects', 'indexnow', 'editor', 'import' ) as $compass_file ) {
	require_once COMPASS_DIR . 'includes/class-compass-' . $compass_file . '.php';
}
unset( $compass_file );
if ( is_admin() ) {
	require_once COMPASS_DIR . 'includes/class-compass-admin.php';
	require_once COMPASS_DIR . 'includes/prasat-hub.php';
	Prasat_Hub::register( 'compass', array( 'assets' => COMPASS_URL . 'assets/', 'health' => array( 'Compass_Admin', 'health' ) ) );
}

final class Compass {

	/** Other SEO plugins: Compass stays quiet on the front end while one is active. */
	const CONFLICTS = array(
		'wordpress-seo/wp-seo.php'                       => 'Yoast SEO',
		'wordpress-seo-premium/wp-seo-premium.php'       => 'Yoast SEO Premium',
		'seo-by-rank-math/rank-math.php'                 => 'Rank Math',
		'all-in-one-seo-pack/all_in_one_seo_pack.php'    => 'All in One SEO',
		'all-in-one-seo-pack-pro/all_in_one_seo_pack.php' => 'All in One SEO Pro',
		'wp-seopress/seopress.php'                       => 'SEOPress',
		'autodescription/autodescription.php'            => 'The SEO Framework',
		'slim-seo/slim-seo.php'                          => 'Slim SEO',
	);

	public static function conflict() {
		$active = (array) get_option( 'active_plugins', array() );
		foreach ( self::CONFLICTS as $file => $name ) {
			if ( in_array( $file, $active, true ) ) {
				return $name;
			}
		}
		return '';
	}

	public static function init() {
		Compass_Breadcrumbs::init();
		Compass_Redirects::init();
		Compass_IndexNow::init();
		add_action( 'init', array( 'Compass_Editor', 'init' ), 99 );
		if ( ! self::conflict() ) {
			Compass_Head::init();
			Compass_Sitemap::init();
		}
		add_action( 'init', array( __CLASS__, 'maintain' ) );
		// Pages cached by Slipstream carry the old <head>: clear them when SEO settings change.
		add_action( 'compass_settings_updated', array( __CLASS__, 'clear_page_cache' ) );
		add_action( 'compass_redirects_changed', array( __CLASS__, 'clear_page_cache' ) );
		if ( class_exists( 'Compass_Admin' ) ) {
			Compass_Admin::init();
		}
	}

	public static function clear_page_cache() {
		if ( class_exists( 'Slipstream_Cache' ) ) {
			Slipstream_Cache::purge_all_soon();
		}
	}

	public static function maintain() {
		if ( get_option( 'compass_version' ) !== COMPASS_VERSION ) {
			Compass_Redirects::install();
			update_option( 'compass_version', COMPASS_VERSION, false );
		}
	}

	public static function activate() {
		if ( false === get_option( Compass_Settings::OPTION ) ) {
			add_option( Compass_Settings::OPTION, Compass_Settings::defaults(), '', true );
		}
		Compass_Redirects::install();
		update_option( 'compass_version', COMPASS_VERSION, false );
		set_transient( 'compass_welcome', 1, HOUR_IN_SECONDS );
		flush_rewrite_rules( false );
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( Compass_IndexNow::HOOK );
	}
}

register_activation_hook( __FILE__, array( 'Compass', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Compass', 'deactivate' ) );
Compass::init();
