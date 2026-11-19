<?php
/**
 * Breadcrumbs: the trail used in structured data, and printable with [compass_breadcrumbs]
 * or compass_breadcrumbs() in a theme.
 *
 * @package Compass
 */

defined( 'ABSPATH' ) || exit;

class Compass_Breadcrumbs {

	public static function init() {
		add_shortcode( 'compass_breadcrumbs', array( __CLASS__, 'html' ) );
	}

	private static function term_chain( $term ) {
		$out = array();
		foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) ) as $id ) {
			$t = get_term( $id, $term->taxonomy );
			if ( $t && ! is_wp_error( $t ) ) {
				$out[] = array( 'name' => $t->name, 'url' => get_term_link( $t ) );
			}
		}
		$out[] = array( 'name' => $term->name, 'url' => get_term_link( $term ) );
		return $out;
	}

	/** [ ['name' =>, 'url' =>], … ] from home to the current page. */
	public static function trail() {
		$home  = array( array( 'name' => Compass_Settings::get( 'bc_home' ) ? Compass_Settings::get( 'bc_home' ) : __( 'Home', 'compass' ), 'url' => home_url( '/' ) ) );
		$trail = array();
		if ( is_front_page() ) {
			return $home;
		}
		if ( is_singular() ) {
			$post = get_queried_object();
			if ( 'post' === $post->post_type ) {
				$blog = get_option( 'page_for_posts' );
				if ( $blog && 'page' === get_option( 'show_on_front' ) ) {
					$trail[] = array( 'name' => get_the_title( $blog ), 'url' => get_permalink( $blog ) );
				}
				$cats = get_the_category( $post->ID );
				if ( $cats ) {
					$trail = array_merge( $trail, self::term_chain( $cats[0] ) );
				}
			} elseif ( is_post_type_hierarchical( $post->post_type ) ) {
				foreach ( array_reverse( get_post_ancestors( $post ) ) as $id ) {
					$trail[] = array( 'name' => get_the_title( $id ), 'url' => get_permalink( $id ) );
				}
			} else {
				$pto = get_post_type_object( $post->post_type );
				if ( $pto && $pto->has_archive ) {
					$trail[] = array( 'name' => $pto->labels->name, 'url' => get_post_type_archive_link( $post->post_type ) );
				}
			}
			$trail[] = array( 'name' => wp_strip_all_tags( get_the_title( $post ) ), 'url' => get_permalink( $post ) );
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$trail = self::term_chain( get_queried_object() );
		} elseif ( is_home() ) {
			$blog    = get_option( 'page_for_posts' );
			$trail[] = array( 'name' => $blog ? get_the_title( $blog ) : __( 'Blog', 'compass' ), 'url' => $blog ? get_permalink( $blog ) : '' );
		} elseif ( is_post_type_archive() ) {
			$pto     = get_queried_object();
			$trail[] = array( 'name' => $pto ? $pto->labels->name : '', 'url' => get_post_type_archive_link( get_query_var( 'post_type' ) ) );
		} elseif ( is_author() ) {
			$a       = get_queried_object();
			$trail[] = array( 'name' => $a ? $a->display_name : '', 'url' => $a ? get_author_posts_url( $a->ID ) : '' );
		} elseif ( is_search() ) {
			$trail[] = array( 'name' => sprintf( /* translators: %s: search terms */ __( 'Search: %s', 'compass' ), get_search_query( false ) ), 'url' => '' );
		} elseif ( is_404() ) {
			$trail[] = array( 'name' => __( 'Page not found', 'compass' ), 'url' => '' );
		} elseif ( is_archive() ) {
			$trail[] = array( 'name' => wp_strip_all_tags( get_the_archive_title() ), 'url' => '' );
		}
		foreach ( $trail as &$t ) {
			if ( is_wp_error( $t['url'] ) ) {
				$t['url'] = '';
			}
		}
		unset( $t );
		return array_merge( $home, $trail );
	}

	public static function html() {
		$trail = self::trail();
		if ( count( $trail ) < 2 ) {
			return '';
		}
		$sep   = '<span class="compass-bc__sep" aria-hidden="true"> ' . esc_html( Compass_Settings::get( 'bc_sep' ) ) . ' </span>';
		$parts = array();
		$last  = count( $trail ) - 1;
		foreach ( $trail as $i => $t ) {
			$parts[] = $i < $last && $t['url'] ? '<a href="' . esc_url( $t['url'] ) . '">' . esc_html( $t['name'] ) . '</a>' : '<span' . ( $i === $last ? ' aria-current="page"' : '' ) . '>' . esc_html( $t['name'] ) . '</span>';
		}
		return '<nav class="compass-bc" aria-label="' . esc_attr__( 'Breadcrumb', 'compass' ) . '">' . implode( $sep, $parts ) . '</nav>';
	}
}

if ( ! function_exists( 'compass_breadcrumbs' ) ) {
	/** Theme template tag. */
	function compass_breadcrumbs() {
		echo Compass_Breadcrumbs::html(); // phpcs:ignore -- escaped inside.
	}
}
