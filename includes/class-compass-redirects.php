<?php
/**
 * Redirects (301, 302, 307, 410) and a 404 monitor.
 *
 * Redirects live in their own table, plus a small cached map (one option) so matching a
 * request costs no database query. Sources are paths ("/old-page"); a trailing * matches
 * everything that starts with the path. The 404 monitor records missing pages (with the
 * page that linked to them) so they can be fixed with one click.
 *
 * @package Compass
 */

defined( 'ABSPATH' ) || exit;

class Compass_Redirects {

	const MAP_OPTION = 'compass_redirect_map';
	const CODES      = array( 301, 302, 307, 410 );
	const LOG_MAX    = 1000;

	/** @var bool This request matched a "410 Gone" rule. */
	private static $gone = false;

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'compass_redirects';
	}

	public static function log_table() {
		global $wpdb;
		return $wpdb->prefix . 'compass_404';
	}

	public static function init() {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect' ), -10 );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_log' ), 99 );
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		dbDelta(
			'CREATE TABLE ' . self::table() . " (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				source varchar(255) NOT NULL,
				target text NOT NULL,
				code smallint(5) unsigned NOT NULL DEFAULT 301,
				hits bigint(20) unsigned NOT NULL DEFAULT 0,
				last_hit datetime DEFAULT NULL,
				created datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY source (source(191))
			) $charset;"
		);
		dbDelta(
			'CREATE TABLE ' . self::log_table() . " (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				path varchar(255) NOT NULL,
				referrer text,
				hits bigint(20) unsigned NOT NULL DEFAULT 1,
				first_seen datetime NOT NULL,
				last_seen datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY path (path(191)),
				KEY last_seen (last_seen)
			) $charset;"
		);
		self::rebuild_map();
	}

	/** "/Old-Page/?x=1" → "/old-page" (query dropped, lower case, no trailing slash). */
	public static function normalize( $url ) {
		$path = (string) wp_parse_url( (string) $url, PHP_URL_PATH );
		$path = '/' . trim( rawurldecode( $path ), '/' );
		$base = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
		if ( '' !== $base && 0 === strpos( $path, $base . '/' ) ) {
			$path = substr( $path, strlen( $base ) );
		}
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $path ) : strtolower( $path );
	}

	public static function rebuild_map() {
		global $wpdb;
		$rows = $wpdb->get_results( 'SELECT id, source, target, code FROM ' . self::table() . ' ORDER BY id ASC', ARRAY_A ); // phpcs:ignore
		$map  = array( 'exact' => array(), 'prefix' => array() );
		foreach ( (array) $rows as $r ) {
			$entry = array( (int) $r['id'], $r['target'], (int) $r['code'] );
			if ( '*' === substr( $r['source'], -1 ) ) {
				$map['prefix'][ rtrim( substr( $r['source'], 0, -1 ), '/' ) ] = $entry;
			} else {
				$map['exact'][ $r['source'] ] = $entry;
			}
		}
		update_option( self::MAP_OPTION, $map, true );
		do_action( 'compass_redirects_changed', $rows );
	}

	public static function find( $path ) {
		$map = get_option( self::MAP_OPTION );
		if ( ! is_array( $map ) ) {
			return null;
		}
		if ( isset( $map['exact'][ $path ] ) ) {
			return $map['exact'][ $path ];
		}
		$best = null;
		foreach ( (array) $map['prefix'] as $prefix => $entry ) {
			if ( ( $path === $prefix || 0 === strpos( $path, $prefix . '/' ) || ( '' === $prefix ) ) && ( null === $best || strlen( $prefix ) > strlen( $best[0] ) ) ) {
				$best = array( $prefix, $entry );
			}
		}
		return $best ? $best[1] : null;
	}

	public static function maybe_redirect() {
		if ( is_admin() || ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$path = self::normalize( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
		$hit  = self::find( $path );
		if ( ! $hit ) {
			return;
		}
		list( $id, $target, $code ) = $hit;
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . self::table() . ' SET hits = hits + 1, last_hit = %s WHERE id = %d', current_time( 'mysql', true ), $id ) ); // phpcs:ignore
		if ( 410 === $code ) {
			self::$gone = true; // Deliberately removed: not a broken link for the 404 monitor.
			global $wp_query;
			$wp_query->set_404();
			status_header( 410 );
			nocache_headers();
			return; // The theme's 404 template is shown with status 410 Gone.
		}
		$target = 0 === strpos( $target, '/' ) ? home_url( $target ) : $target;
		// Never loop back to the same address.
		if ( self::normalize( $target ) === $path && wp_parse_url( $target, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST ) ) {
			return;
		}
		wp_redirect( esc_url_raw( $target ), $code, 'Compass' ); // phpcs:ignore -- targets may be other sites by design.
		exit;
	}

	/* ================================================================ admin */

	/** @return true|WP_Error */
	public static function add( $source, $target, $code = 301 ) {
		global $wpdb;
		$wild   = '*' === substr( trim( (string) $source ), -1 );
		$source = self::normalize( rtrim( trim( (string) $source ), '*' ) ) . ( $wild ? '*' : '' );
		$code   = in_array( (int) $code, self::CODES, true ) ? (int) $code : 301;
		$target = trim( (string) $target );
		if ( '/' === $source || '/*' === $source ) {
			return new WP_Error( 'compass_home', __( 'The home page (or the whole site) can\'t be redirected here.', 'compass' ) );
		}
		if ( 410 !== $code ) {
			if ( '' === $target ) {
				return new WP_Error( 'compass_target', __( 'Enter where visitors should go.', 'compass' ) );
			}
			$target = 0 === strpos( $target, '/' ) ? '/' . ltrim( $target, '/' ) : esc_url_raw( $target );
			if ( '' === $target ) {
				return new WP_Error( 'compass_target', __( 'The destination is not a valid address.', 'compass' ) );
			}
			if ( ! $wild && self::normalize( $target ) === $source && ( 0 === strpos( $target, '/' ) || wp_parse_url( $target, PHP_URL_HOST ) === wp_parse_url( home_url(), PHP_URL_HOST ) ) ) {
				return new WP_Error( 'compass_loop', __( 'A page can\'t redirect to itself.', 'compass' ) );
			}
		} else {
			$target = '';
		}
		$wpdb->delete( self::table(), array( 'source' => $source ) ); // Replace an existing rule for the same address.
		$wpdb->insert( self::table(), array( 'source' => $source, 'target' => $target, 'code' => $code, 'created' => current_time( 'mysql', true ) ) );
		$wpdb->delete( self::log_table(), array( 'path' => rtrim( $source, '*' ) ) ); // Fixed: drop it from the 404 list.
		self::rebuild_map();
		return true;
	}

	public static function delete( $id ) {
		global $wpdb;
		$wpdb->delete( self::table(), array( 'id' => (int) $id ) );
		self::rebuild_map();
	}

	public static function all() {
		global $wpdb;
		return (array) $wpdb->get_results( 'SELECT * FROM ' . self::table() . ' ORDER BY id DESC LIMIT 2000', ARRAY_A ); // phpcs:ignore
	}

	/* ========================================================== 404 monitor */

	public static function maybe_log() {
		if ( self::$gone || ! is_404() || ! Compass_Settings::get( 'log_404' ) || ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$path = self::normalize( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
		// Attack probes aren't broken links; keep the list useful.
		if ( strlen( $path ) > 250 || preg_match( '#\.(php\d?|env|git|ini|sql|bak|old|log|asp|aspx|cgi)(/|$)|/(wp-admin|wp-includes|\.well-known|cgi-bin|vendor|xmlrpc)(/|$)#i', $path ) ) {
			return;
		}
		global $wpdb;
		$now = current_time( 'mysql', true );
		$ref = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$id  = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::log_table() . ' WHERE path = %s', $path ) ); // phpcs:ignore
		if ( $id ) {
			$wpdb->query( $wpdb->prepare( 'UPDATE ' . self::log_table() . ' SET hits = hits + 1, last_seen = %s' . ( $ref ? ', referrer = %s' : '' ) . ' WHERE id = %d', ...( $ref ? array( $now, $ref, $id ) : array( $now, $id ) ) ) ); // phpcs:ignore
			return;
		}
		$wpdb->insert( self::log_table(), array( 'path' => $path, 'referrer' => $ref, 'hits' => 1, 'first_seen' => $now, 'last_seen' => $now ) );
		if ( 0 === wp_rand( 0, 19 ) ) { // Occasionally trim the oldest rows.
			$count = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::log_table() ); // phpcs:ignore
			if ( $count > self::LOG_MAX ) {
				$cut = $wpdb->get_var( $wpdb->prepare( 'SELECT last_seen FROM ' . self::log_table() . ' ORDER BY last_seen DESC LIMIT 1 OFFSET %d', self::LOG_MAX ) ); // phpcs:ignore
				if ( $cut ) {
					$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::log_table() . ' WHERE last_seen <= %s', $cut ) ); // phpcs:ignore
				}
			}
		}
	}

	public static function log( $limit = 200 ) {
		global $wpdb;
		return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::log_table() . ' ORDER BY last_seen DESC LIMIT %d', $limit ), ARRAY_A ); // phpcs:ignore
	}

	public static function log_count_since( $ts ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(hits),0) FROM ' . self::log_table() . ' WHERE last_seen >= %s', gmdate( 'Y-m-d H:i:s', $ts ) ) ); // phpcs:ignore
	}

	public static function clear_log() {
		global $wpdb;
		$wpdb->query( 'DELETE FROM ' . self::log_table() ); // phpcs:ignore
	}
}
