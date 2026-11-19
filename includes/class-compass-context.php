<?php
/**
 * Works out what search engines should see for the current page (or any post): title,
 * description, canonical URL, robots rules and social card. Everything else reads from here.
 *
 * @package Compass
 */

defined( 'ABSPATH' ) || exit;

class Compass_Context {

	const META = array(
		'title'     => '_compass_title',
		'desc'      => '_compass_desc',
		'focus'     => '_compass_focus',
		'canonical' => '_compass_canonical',
		'noindex'   => '_compass_noindex',
		'nofollow'  => '_compass_nofollow',
		'og_title'  => '_compass_og_title',
		'og_desc'   => '_compass_og_desc',
		'og_image'  => '_compass_og_image',
		'score'     => '_compass_score',
	);

	/** @var array|null Resolved values for this request. */
	private static $cache;

	public static function post_meta( $post_id, $key ) {
		return (string) get_post_meta( $post_id, self::META[ $key ], true );
	}

	/* =========================================================== variables */

	/** Plain text, whitespace collapsed, cut at a word (or character, for Khmer) boundary. */
	public static function trim_text( $text, $max = 155 ) {
		$text = wp_strip_all_tags( strip_shortcodes( (string) $text ), true );
		$text = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( $text, ENT_QUOTES, 'UTF-8' ) ) );
		if ( mb_strlen( $text ) <= $max ) {
			return $text;
		}
		$cut   = mb_substr( $text, 0, $max );
		$space = mb_strrpos( $cut, ' ' );
		return rtrim( ( false !== $space && $space > $max * 0.6 ? mb_substr( $cut, 0, $space ) : $cut ), ' ,.;:–-' ) . '…';
	}

	public static function excerpt( $post ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return '';
		}
		$text = '' !== trim( $post->post_excerpt ) ? $post->post_excerpt : $post->post_content;
		$text = preg_replace( '#<(script|style)\b.*?</\1>#is', '', (string) $text );
		return self::trim_text( excerpt_remove_blocks( $text ) );
	}

	/** Replace %variables% in a template. */
	public static function replace( $template, array $ctx = array() ) {
		$post  = isset( $ctx['post'] ) ? get_post( $ctx['post'] ) : null;
		$term  = isset( $ctx['term'] ) ? $ctx['term'] : null;
		$paged = max( (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
		$vars  = array(
			'%sitename%'         => get_bloginfo( 'name' ),
			'%tagline%'          => get_bloginfo( 'description' ),
			'%sep%'              => Compass_Settings::get( 'sep' ),
			'%year%'             => wp_date( 'Y' ),
			'%title%'            => $post ? get_the_title( $post ) : ( isset( $ctx['title'] ) ? $ctx['title'] : '' ),
			'%excerpt%'          => $post ? self::excerpt( $post ) : '',
			'%posttype%'         => $post ? (string) get_post_type_object( $post->post_type )->labels->singular_name : '',
			'%category%'         => '',
			'%term%'             => $term ? $term->name : '',
			'%term_description%' => $term ? self::trim_text( term_description( $term ) ) : '',
			'%author%'           => isset( $ctx['author'] ) ? $ctx['author'] : ( $post ? get_the_author_meta( 'display_name', $post->post_author ) : '' ),
			'%search%'           => isset( $ctx['search'] ) ? $ctx['search'] : '',
			'%page%'             => $paged > 1 ? sprintf( /* translators: %d: page number */ __( 'Page %d', 'compass' ), $paged ) : '',
		);
		if ( $post && false !== strpos( $template, '%category%' ) ) {
			$cats = get_the_category( $post->ID );
			$vars['%category%'] = $cats ? $cats[0]->name : '';
		}
		$out = strtr( (string) $template, $vars );
		// Tidy: drop separators left dangling by empty variables ("Title – " or " –  – ").
		$sep = preg_quote( Compass_Settings::get( 'sep' ), '/' );
		$out = preg_replace( '/(\s*' . $sep . '\s*){2,}/u', ' ' . Compass_Settings::get( 'sep' ) . ' ', $out );
		$out = preg_replace( '/^\s*' . $sep . '\s*|\s*' . $sep . '\s*$/u', '', $out );
		$out = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $out ) ) );
		// A page named like the site ("Acme – Acme"), or a tagline equal to the name: say it once.
		if ( '' !== $sep ) {
			$parts = preg_split( '/\s+' . $sep . '\s+/u', $out );
			if ( count( $parts ) > 1 ) {
				$seen = array();
				foreach ( $parts as $i => $part ) {
					$key = mb_strtolower( trim( $part ) );
					if ( isset( $seen[ $key ] ) ) {
						unset( $parts[ $i ] );
					}
					$seen[ $key ] = true;
				}
				$out = implode( ' ' . Compass_Settings::get( 'sep' ) . ' ', $parts );
			}
		}
		return $out;
	}

	/* ========================================================== for a post */

	public static function post_title( $post ) {
		$post   = get_post( $post );
		$custom = self::post_meta( $post->ID, 'title' );
		$tpl    = '' !== $custom ? $custom : Compass_Settings::title_for_pt( $post->post_type );
		if ( (int) get_option( 'page_on_front' ) === $post->ID && 'page' === get_option( 'show_on_front' ) && '' === $custom ) {
			$tpl = Compass_Settings::get( 'title_home' );
		}
		return self::replace( $tpl, array( 'post' => $post ) );
	}

	public static function post_desc( $post ) {
		$post   = get_post( $post );
		$custom = self::post_meta( $post->ID, 'desc' );
		if ( '' === $custom && (int) get_option( 'page_on_front' ) === $post->ID && Compass_Settings::get( 'desc_home' ) ) {
			$custom = Compass_Settings::get( 'desc_home' );
		}
		return self::trim_text( self::replace( '' !== $custom ? $custom : Compass_Settings::desc_for_pt( $post->post_type ), array( 'post' => $post ) ), 300 );
	}

	public static function post_noindex( $post ) {
		$post = get_post( $post );
		return '1' === self::post_meta( $post->ID, 'noindex' ) || Compass_Settings::noindex_pt( $post->post_type );
	}

	public static function post_image( $post ) {
		$post = get_post( $post );
		$own  = self::post_meta( $post->ID, 'og_image' );
		if ( $own ) {
			return array( 'url' => $own );
		}
		$thumb = get_post_thumbnail_id( $post );
		if ( $thumb ) {
			$src = wp_get_attachment_image_src( $thumb, 'large' );
			if ( $src ) {
				return array( 'url' => $src[0], 'width' => $src[1], 'height' => $src[2], 'alt' => get_post_meta( $thumb, '_wp_attachment_image_alt', true ) );
			}
		}
		if ( preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $m ) ) {
			return array( 'url' => $m[1] );
		}
		return self::default_image();
	}

	public static function default_image() {
		$u = Compass_Settings::get( 'og_image' );
		if ( ! $u && has_site_icon() ) {
			$u = get_site_icon_url( 512 );
		}
		return $u ? array( 'url' => $u ) : array();
	}

	/* ======================================================= this request */

	/** title, desc, canonical, robots[], og{} for the page being viewed. */
	public static function current() {
		if ( null !== self::$cache ) {
			return self::$cache;
		}
		$s     = Compass_Settings::all();
		$paged = max( 1, (int) get_query_var( 'paged' ) );
		$c     = array( 'title' => '', 'desc' => '', 'canonical' => '', 'noindex' => false, 'nofollow' => false, 'type' => 'website', 'image' => array(), 'post' => null, 'term' => null );

		if ( is_front_page() && is_home() || ( is_front_page() && ! is_singular() ) ) {
			$c['title']     = self::replace( $s['title_home'] );
			$c['desc']      = self::trim_text( $s['desc_home'] ? $s['desc_home'] : get_bloginfo( 'description' ) );
			$c['canonical'] = $paged > 1 ? get_pagenum_link( $paged ) : home_url( '/' );
			$c['image']     = self::default_image();
		} elseif ( is_singular() ) {
			$post             = get_queried_object();
			$c['post']        = $post;
			$c['title']       = self::post_title( $post );
			$c['desc']        = self::post_desc( $post );
			$custom           = self::post_meta( $post->ID, 'canonical' );
			$c['canonical']   = $custom ? $custom : wp_get_canonical_url( $post );
			$c['noindex']     = self::post_noindex( $post );
			$c['nofollow']    = '1' === self::post_meta( $post->ID, 'nofollow' );
			$c['type']        = is_front_page() ? 'website' : ( 'page' === $post->post_type ? 'website' : 'article' );
			$c['image']       = self::post_image( $post );
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term         = get_queried_object();
			$c['term']    = $term;
			$own_title    = (string) get_term_meta( $term->term_id, 'compass_title', true );
			$own_desc     = (string) get_term_meta( $term->term_id, 'compass_desc', true );
			$c['title']   = self::replace( $own_title ? $own_title : Compass_Settings::title_for_tax( $term->taxonomy ), array( 'term' => $term ) );
			$c['desc']    = self::trim_text( self::replace( $own_desc ? $own_desc : Compass_Settings::desc_for_tax( $term->taxonomy ), array( 'term' => $term ) ) );
			$link         = get_term_link( $term );
			$c['canonical'] = is_wp_error( $link ) ? '' : ( $paged > 1 ? get_pagenum_link( $paged ) : $link );
			$c['noindex'] = '1' === (string) get_term_meta( $term->term_id, 'compass_noindex', true ) || Compass_Settings::noindex_tax( $term->taxonomy );
			$c['image']   = self::default_image();
		} elseif ( is_home() ) { // Blog page when the front page is static.
			$page           = get_option( 'page_for_posts' );
			$c['title']     = $page ? self::post_title( $page ) : self::replace( $s['title_home'] );
			$c['desc']      = $page ? self::post_desc( $page ) : '';
			$c['canonical'] = $paged > 1 ? get_pagenum_link( $paged ) : ( $page ? get_permalink( $page ) : home_url( '/' ) );
			$c['image']     = self::default_image();
		} elseif ( is_post_type_archive() ) {
			$pto            = get_queried_object();
			$c['title']     = self::replace( '%title% %sep% %sitename%', array( 'title' => $pto ? $pto->labels->name : '' ) );
			$c['canonical'] = $paged > 1 ? get_pagenum_link( $paged ) : get_post_type_archive_link( get_query_var( 'post_type' ) );
			$c['image']     = self::default_image();
		} elseif ( is_author() ) {
			$author         = get_queried_object();
			$c['title']     = self::replace( $s['title_author'], array( 'author' => $author ? $author->display_name : '' ) );
			$c['desc']      = $author ? self::trim_text( get_the_author_meta( 'description', $author->ID ) ) : '';
			$c['canonical'] = $author ? ( $paged > 1 ? get_pagenum_link( $paged ) : get_author_posts_url( $author->ID ) ) : '';
			$c['noindex']   = (bool) $s['noindex_author'];
		} elseif ( is_date() ) {
			$c['title']   = self::replace( '%title% %sep% %sitename%', array( 'title' => wp_strip_all_tags( get_the_archive_title() ) ) );
			$c['noindex'] = (bool) $s['noindex_date'];
		} elseif ( is_search() ) {
			$c['title']   = self::replace( $s['title_search'], array( 'search' => sprintf( /* translators: %s: search terms */ __( 'Search results for "%s"', 'compass' ), get_search_query( false ) ) ) );
			$c['noindex'] = true;
		} elseif ( is_404() ) {
			$c['title']   = self::replace( $s['title_404'], array( 'title' => __( 'Page not found', 'compass' ) ) );
			$c['noindex'] = true;
		}
		if ( $paged > 1 && $c['title'] && false === strpos( $c['title'], (string) $paged ) ) {
			$c['title'] .= ' ' . Compass_Settings::get( 'sep' ) . ' ' . sprintf( __( 'Page %d', 'compass' ), $paged );
		}
		self::$cache = apply_filters( 'compass_context', $c );
		return self::$cache;
	}
}
