<?php
/**
 * Import titles, descriptions, keyphrases and settings per post from Yoast SEO and Rank Math,
 * so switching to Compass keeps every page's search appearance. Existing Compass values are
 * never overwritten, and the other plugin's data is left in place.
 *
 * @package Compass
 */

defined( 'ABSPATH' ) || exit;

class Compass_Import {

	public static function sources() {
		return array(
			'yoast'    => array(
				'name' => 'Yoast SEO',
				'map'  => array(
					'_yoast_wpseo_title'                 => 'title',
					'_yoast_wpseo_metadesc'              => 'desc',
					'_yoast_wpseo_focuskw'               => 'focus',
					'_yoast_wpseo_canonical'             => 'canonical',
					'_yoast_wpseo_meta-robots-noindex'   => 'noindex',
					'_yoast_wpseo_meta-robots-nofollow'  => 'nofollow',
					'_yoast_wpseo_opengraph-title'       => 'og_title',
					'_yoast_wpseo_opengraph-description' => 'og_desc',
					'_yoast_wpseo_opengraph-image'       => 'og_image',
				),
			),
			'rankmath' => array(
				'name' => 'Rank Math',
				'map'  => array(
					'rank_math_title'                => 'title',
					'rank_math_description'          => 'desc',
					'rank_math_focus_keyword'        => 'focus',
					'rank_math_canonical_url'        => 'canonical',
					'rank_math_robots'               => 'robots',
					'rank_math_facebook_title'       => 'og_title',
					'rank_math_facebook_description' => 'og_desc',
					'rank_math_facebook_image'       => 'og_image',
				),
			),
		);
	}

	/** Posts with data from each source. */
	public static function counts() {
		global $wpdb;
		$out = array();
		foreach ( self::sources() as $id => $src ) {
			$keys       = array_keys( $src['map'] );
			$in         = implode( ',', array_fill( 0, count( $keys ), '%s' ) );
			$out[ $id ] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key IN ($in) AND meta_value <> ''", $keys ) ); // phpcs:ignore
		}
		return $out;
	}

	/** Their template variables (%%title%%) → ours (%title%). */
	private static function vars( $v ) {
		return preg_replace( '/%%([a-z_]+)%%/', '%$1%', (string) $v );
	}

	public static function run( $source ) {
		global $wpdb;
		$sources = self::sources();
		if ( ! isset( $sources[ $source ] ) ) {
			return 0;
		}
		$map  = $sources[ $source ]['map'];
		$keys = array_keys( $map );
		$in   = implode( ',', array_fill( 0, count( $keys ), '%s' ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_key IN ($in) AND meta_value <> ''", $keys ) ); // phpcs:ignore
		$done = array();
		foreach ( (array) $rows as $r ) {
			$field = $map[ $r->meta_key ];
			$val   = $r->meta_value;
			if ( 'robots' === $field ) { // Rank Math stores an array of robots values.
				$robots = maybe_unserialize( $val );
				$robots = is_array( $robots ) ? $robots : array();
				foreach ( array( 'noindex', 'nofollow' ) as $flag ) {
					if ( in_array( $flag, $robots, true ) ) {
						self::set( $r->post_id, $flag, '1' );
					}
				}
				$done[ $r->post_id ] = 1;
				continue;
			}
			if ( 'noindex' === $field || 'nofollow' === $field ) {
				$val = '1' === (string) $val ? '1' : '';
			} elseif ( 'focus' === $field ) {
				$val = trim( explode( ',', $val )[0] );
			} elseif ( in_array( $field, array( 'title', 'desc', 'og_title', 'og_desc' ), true ) ) {
				$val = self::vars( $val );
			}
			if ( '' !== $val && self::set( $r->post_id, $field, $val ) ) {
				$done[ $r->post_id ] = 1;
			}
		}
		return count( $done );
	}

	/** Only fills empty Compass fields. */
	private static function set( $post_id, $field, $val ) {
		$key = Compass_Context::META[ $field ];
		if ( '' !== (string) get_post_meta( $post_id, $key, true ) ) {
			return false;
		}
		update_post_meta( $post_id, $key, $val );
		return true;
	}
}
