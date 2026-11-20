<?php
/**
 * Everything Compass prints in <head>: title, meta description, robots, canonical,
 * Open Graph / X (Twitter) cards, verification codes and structured data.
 *
 * @package Compass
 */

defined( 'ABSPATH' ) || exit;

class Compass_Head {

	public static function init() {
		add_filter( 'pre_get_document_title', array( __CLASS__, 'title' ), 20 );
		add_filter( 'wp_robots', array( __CLASS__, 'robots' ), 20 );
		remove_action( 'wp_head', 'rel_canonical' );
		add_action( 'wp_head', array( __CLASS__, 'output' ), 1 );
	}

	public static function title( $title ) {
		if ( is_feed() || is_admin() || empty( $GLOBALS['wp_query'] ) || ! did_action( 'wp' ) ) {
			return $title;
		}
		$c = Compass_Context::current();
		return '' !== $c['title'] ? $c['title'] : $title;
	}

	public static function robots( $robots ) {
		if ( empty( $GLOBALS['wp_query'] ) || ! did_action( 'wp' ) ) {
			return $robots; // e.g. an error page printed before WordPress parsed the request
		}
		$c = Compass_Context::current();
		if ( $c['noindex'] ) {
			$robots['noindex'] = true;
			unset( $robots['max-image-preview'] );
			$robots['follow'] = empty( $robots['nofollow'] ) && ! $c['nofollow'];
		}
		if ( $c['nofollow'] ) {
			$robots['nofollow'] = true;
			unset( $robots['follow'] );
		}
		if ( empty( $robots['noindex'] ) && get_option( 'blog_public' ) ) {
			$robots['max-image-preview'] = 'large';
			$robots['max-snippet']       = '-1';
			$robots['max-video-preview'] = '-1';
		}
		return $robots;
	}

	private static function meta( $attr, $name, $content ) {
		if ( '' === (string) $content ) {
			return '';
		}
		return '<meta ' . $attr . '="' . esc_attr( $name ) . '" content="' . esc_attr( $content ) . '">' . "\n";
	}

	public static function output() {
		$s   = Compass_Settings::all();
		$c   = Compass_Context::current();
		$out = "\n<!-- Compass SEO by Prasat Digital -->\n";
		$out .= self::meta( 'name', 'description', $c['desc'] );
		if ( $c['canonical'] && ! $c['noindex'] ) {
			$out .= '<link rel="canonical" href="' . esc_url( $c['canonical'] ) . '">' . "\n";
		}
		foreach ( array( 'google' => 'google-site-verification', 'bing' => 'msvalidate.01', 'yandex' => 'yandex-verification', 'baidu' => 'baidu-site-verification', 'pinterest' => 'p:domain_verify' ) as $k => $name ) {
			if ( is_front_page() ) {
				$out .= self::meta( 'name', $name, $s[ 'verify_' . $k ] );
			}
		}
		if ( $s['og'] && ! is_404() ) {
			$out .= self::social( $c, $s );
		}
		if ( $s['schema'] && ! is_404() ) {
			$out .= Compass_Schema::script( $c );
		}
		echo $out . "<!-- / Compass SEO -->\n\n"; // phpcs:ignore -- every value is escaped above.
	}

	private static function social( array $c, array $s ) {
		$post  = $c['post'];
		$title = $c['title'];
		$desc  = $c['desc'];
		if ( $post ) {
			$t = Compass_Context::post_meta( $post->ID, 'og_title' );
			$d = Compass_Context::post_meta( $post->ID, 'og_desc' );
			// A static front page is usually named "Home" or "Homepage"; share the site's title instead.
			$title = $t ? $t : ( is_front_page() ? $title : get_the_title( $post ) );
			$desc  = $d ? $d : $desc;
		}
		$url = $c['canonical'] ? $c['canonical'] : home_url( add_query_arg( array() ) );
		$o   = self::meta( 'property', 'og:locale', str_replace( '-', '_', get_bloginfo( 'language' ) ) );
		$o  .= self::meta( 'property', 'og:type', $c['type'] );
		$o  .= self::meta( 'property', 'og:title', $title );
		$o  .= self::meta( 'property', 'og:description', $desc );
		$o  .= self::meta( 'property', 'og:url', $url );
		$o  .= self::meta( 'property', 'og:site_name', get_bloginfo( 'name' ) );
		if ( $post && 'article' === $c['type'] ) {
			$o .= self::meta( 'property', 'article:published_time', get_post_time( 'c', true, $post ) );
			$o .= self::meta( 'property', 'article:modified_time', get_post_modified_time( 'c', true, $post ) );
		}
		$img = $c['image'];
		if ( ! empty( $img['url'] ) ) {
			$o .= self::meta( 'property', 'og:image', $img['url'] );
			if ( ! empty( $img['width'] ) ) {
				$o .= self::meta( 'property', 'og:image:width', $img['width'] );
				$o .= self::meta( 'property', 'og:image:height', $img['height'] );
			}
			if ( ! empty( $img['alt'] ) ) {
				$o .= self::meta( 'property', 'og:image:alt', $img['alt'] );
			}
		}
		$o .= self::meta( 'name', 'twitter:card', ! empty( $img['url'] ) ? 'summary_large_image' : 'summary' );
		if ( $s['twitter_site'] ) {
			$o .= self::meta( 'name', 'twitter:site', '@' . ltrim( $s['twitter_site'], '@' ) );
		}
		return $o;
	}
}
