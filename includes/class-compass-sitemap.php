<?php
/**
 * XML sitemap and robots.txt. Builds on WordPress's own sitemap (wp-sitemap.xml) instead of
 * replacing it: hidden (noindex) content is left out, last-modified dates are added, and the
 * addresses people and tools expect (/sitemap.xml, /sitemap_index.xml) point to it.
 *
 * @package Compass
 */

defined( 'ABSPATH' ) || exit;

class Compass_Sitemap {

	public static function init() {
		$s = Compass_Settings::all();
		if ( ! $s['sitemap'] ) {
			add_filter( 'wp_sitemaps_enabled', '__return_false' );
		} else {
			add_filter( 'wp_sitemaps_post_types', array( __CLASS__, 'post_types' ) );
			add_filter( 'wp_sitemaps_taxonomies', array( __CLASS__, 'taxonomies' ) );
			add_filter( 'wp_sitemaps_add_provider', array( __CLASS__, 'providers' ), 10, 2 );
			add_filter( 'wp_sitemaps_posts_query_args', array( __CLASS__, 'posts_query' ), 10, 2 );
			add_filter( 'wp_sitemaps_taxonomies_query_args', array( __CLASS__, 'terms_query' ), 10, 2 );
			add_filter( 'wp_sitemaps_posts_entry', array( __CLASS__, 'post_entry' ), 10, 2 );
			add_action( 'template_redirect', array( __CLASS__, 'aliases' ), 0 );
		}
		add_filter( 'robots_txt', array( __CLASS__, 'robots_txt' ), 20, 2 );
	}

	public static function post_types( $types ) {
		foreach ( (array) Compass_Settings::get( 'sitemap_skip_pt' ) as $pt ) {
			unset( $types[ $pt ] );
		}
		foreach ( array_keys( $types ) as $pt ) {
			if ( Compass_Settings::noindex_pt( $pt ) ) {
				unset( $types[ $pt ] );
			}
		}
		return $types;
	}

	public static function taxonomies( $taxes ) {
		foreach ( (array) Compass_Settings::get( 'sitemap_skip_tax' ) as $t ) {
			unset( $taxes[ $t ] );
		}
		foreach ( array_keys( $taxes ) as $t ) {
			if ( Compass_Settings::noindex_tax( $t ) ) {
				unset( $taxes[ $t ] );
			}
		}
		return $taxes;
	}

	public static function providers( $provider, $name ) {
		return 'users' === $name && ( ! Compass_Settings::get( 'sitemap_users' ) || Compass_Settings::get( 'noindex_author' ) ) ? false : $provider;
	}

	/** Leave out posts marked "hide from search engines". */
	public static function posts_query( $args ) {
		$args['meta_query'] = array( // phpcs:ignore
			'relation' => 'OR',
			array( 'key' => Compass_Context::META['noindex'], 'compare' => 'NOT EXISTS' ),
			array( 'key' => Compass_Context::META['noindex'], 'value' => '1', 'compare' => '!=' ),
		);
		return $args;
	}

	public static function terms_query( $args ) {
		$args['meta_query'] = array( // phpcs:ignore
			'relation' => 'OR',
			array( 'key' => 'compass_noindex', 'compare' => 'NOT EXISTS' ),
			array( 'key' => 'compass_noindex', 'value' => '1', 'compare' => '!=' ),
		);
		return $args;
	}

	public static function post_entry( $entry, $post ) {
		$entry['lastmod'] = get_post_modified_time( 'c', true, $post );
		$canon            = Compass_Context::post_meta( $post->ID, 'canonical' );
		if ( $canon ) {
			$entry['loc'] = $canon;
		}
		return $entry;
	}

	/** /sitemap.xml and /sitemap_index.xml → WordPress's sitemap. */
	public static function aliases() {
		if ( ! is_404() ) {
			return;
		}
		$path = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '', PHP_URL_PATH );
		$base = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
		if ( in_array( $path, array( $base . '/sitemap.xml', $base . '/sitemap_index.xml' ), true ) ) {
			wp_safe_redirect( get_sitemap_url( 'index' ), 301, 'Compass' );
			exit;
		}
	}

	public static function robots_txt( $txt, $public ) {
		$extra = trim( (string) Compass_Settings::get( 'robots_extra' ) );
		if ( $public && $extra ) {
			$txt = rtrim( $txt ) . "\n\n# Added in Compass\n" . $extra . "\n";
		}
		return $txt;
	}
}
