<?php
/**
 * Settings, described by a schema so every value is sanitized the same way.
 *
 * Title and description templates use variables: %title% %sitename% %tagline% %sep% %excerpt%
 * %term% %term_description% %author% %search% %page% %year% %category% %posttype%.
 *
 * @package Compass
 */

defined( 'ABSPATH' ) || exit;

class Compass_Settings {

	const OPTION = 'compass_settings';

	const SEPARATORS = array( '–', '-', '—', '·', '•', '|', '»', '›' );

	/** @var array|null */
	private static $cache;

	/** key => [type, default] */
	public static function schema() {
		return array(
			// Titles & meta.
			'sep'              => array( 'sep', '–' ),
			'title_home'       => array( 'text', '%sitename% %sep% %tagline%' ),
			'desc_home'        => array( 'textarea', '' ),
			'title_pt'         => array( 'map_text', array() ),
			'desc_pt'          => array( 'map_text', array() ),
			'noindex_pt'       => array( 'map_bool', array() ),
			'title_tax'        => array( 'map_text', array() ),
			'desc_tax'         => array( 'map_text', array() ),
			'noindex_tax'      => array( 'map_bool', array( 'post_format' => true ) ),
			'title_author'     => array( 'text', '%author% %sep% %sitename%' ),
			'noindex_author'   => array( 'bool', false ),
			'noindex_date'     => array( 'bool', true ),
			'title_search'     => array( 'text', '%search% %sep% %sitename%' ),
			'title_404'        => array( 'text', '%title% %sep% %sitename%' ),
			// Social.
			'og'               => array( 'bool', true ),
			'og_image'         => array( 'url', '' ),
			'twitter_site'     => array( 'text', '' ),
			// Schema.
			'schema'           => array( 'bool', true ),
			'org_type'         => array( 'choice:Organization|LocalBusiness|Person', 'Organization' ),
			'org_name'         => array( 'text', '' ),
			'org_logo'         => array( 'url', '' ),
			'same_as'          => array( 'lines_url', array() ),
			'biz_type'         => array( 'choice:LocalBusiness|Restaurant|CafeOrCoffeeShop|Store|Hotel|ProfessionalService|BeautySalon|TravelAgency|RealEstateAgent|EducationalOrganization|MedicalClinic|AutoRepair', 'LocalBusiness' ),
			'biz_street'       => array( 'text', '' ),
			'biz_city'         => array( 'text', '' ),
			'biz_region'       => array( 'text', '' ),
			'biz_postcode'     => array( 'text', '' ),
			'biz_country'      => array( 'text', 'KH' ),
			'biz_phone'        => array( 'text', '' ),
			'biz_price'        => array( 'text', '' ),
			'biz_hours'        => array( 'lines', array() ),
			'biz_lat'          => array( 'text', '' ),
			'biz_lng'          => array( 'text', '' ),
			// Sitemap & robots.
			'sitemap'          => array( 'bool', true ),
			'sitemap_users'    => array( 'bool', false ),
			'sitemap_skip_pt'  => array( 'list', array() ),
			'sitemap_skip_tax' => array( 'list', array( 'post_format' ) ),
			'robots_extra'     => array( 'textarea', '' ),
			'indexnow'         => array( 'bool', false ),
			// Redirects & 404s.
			'log_404'          => array( 'bool', true ),
			// Tools.
			'verify_google'    => array( 'token', '' ),
			'verify_bing'      => array( 'token', '' ),
			'verify_yandex'    => array( 'token', '' ),
			'verify_baidu'     => array( 'token', '' ),
			'verify_pinterest' => array( 'token', '' ),
			'bc_home'          => array( 'text', 'Home' ),
			'bc_sep'           => array( 'text', '›' ),
		);
	}

	public static function defaults() {
		return wp_list_pluck( self::schema(), 1 );
	}

	public static function all() {
		if ( null === self::$cache ) {
			$saved       = get_option( self::OPTION, array() );
			self::$cache = array_merge( self::defaults(), is_array( $saved ) ? $saved : array() );
		}
		return self::$cache;
	}

	public static function get( $key ) {
		$a = self::all();
		return isset( $a[ $key ] ) ? $a[ $key ] : null;
	}

	public static function update( array $values ) {
		$all    = self::all();
		$schema = self::schema();
		foreach ( $values as $k => $v ) {
			if ( isset( $schema[ $k ] ) ) {
				$all[ $k ] = self::sanitize( $schema[ $k ][0], $v, $schema[ $k ][1] );
			}
		}
		update_option( self::OPTION, $all, true );
		self::$cache = $all;
		do_action( 'compass_settings_updated', $all );
		return $all;
	}

	private static function lines( $v ) {
		$v = is_array( $v ) ? $v : preg_split( '/[\r\n]+/', (string) $v );
		return array_values( array_filter( array_map( 'trim', array_map( 'sanitize_text_field', $v ) ), 'strlen' ) );
	}

	private static function sanitize( $type, $v, $default ) {
		if ( 'sep' === $type ) {
			return in_array( (string) $v, self::SEPARATORS, true ) ? (string) $v : $default;
		}
		if ( 0 === strpos( $type, 'choice:' ) ) {
			return in_array( (string) $v, explode( '|', substr( $type, 7 ) ), true ) ? (string) $v : $default;
		}
		switch ( $type ) {
			case 'bool':
				return (bool) $v;
			case 'text':
				return sanitize_text_field( (string) $v );
			case 'textarea':
				return sanitize_textarea_field( (string) $v );
			case 'url':
				return esc_url_raw( trim( (string) $v ) );
			case 'token':
				// Accept either the code or the whole <meta …content="code"> tag Google shows.
				$v = (string) $v;
				if ( preg_match( '/content=["\']([^"\']+)["\']/i', $v, $m ) ) {
					$v = $m[1];
				}
				return preg_replace( '/[^A-Za-z0-9_\-=.]/', '', $v );
			case 'lines':
				return self::lines( $v );
			case 'lines_url':
				return array_values( array_filter( array_map( 'esc_url_raw', self::lines( $v ) ) ) );
			case 'list':
				return array_values( array_map( 'sanitize_key', (array) $v ) );
			case 'map_text':
				$out = array();
				foreach ( (array) $v as $k => $val ) {
					$out[ sanitize_key( $k ) ] = sanitize_text_field( (string) $val );
				}
				return $out;
			case 'map_bool':
				$out = array();
				foreach ( (array) $v as $k => $val ) {
					$out[ sanitize_key( $k ) ] = (bool) $val;
				}
				return $out;
		}
		return $default;
	}

	/* ------------------------------------------------------- template lookups */

	public static function title_for_pt( $pt ) {
		$m = (array) self::get( 'title_pt' );
		return ! empty( $m[ $pt ] ) ? $m[ $pt ] : '%title% %sep% %sitename%';
	}

	public static function desc_for_pt( $pt ) {
		$m = (array) self::get( 'desc_pt' );
		return ! empty( $m[ $pt ] ) ? $m[ $pt ] : '%excerpt%';
	}

	/**
	 * Page-builder template types. Themes register them as public so previews work, but they
	 * are fragments (headers, footers, sections), never pages people should land on from Google.
	 */
	const TEMPLATE_TYPES = array( 'blocks', 'elementor_library', 'et_pb_layout', 'fl-builder-template', 'ct_template', 'oxy_user_library', 'brizy_template', 'us_page_block', 'kadence_element', 'jet-theme-core', 'porto_builder', 'tbuilder_layout', 'astra-advanced-hook', 'gp_elements' );

	public static function noindex_pt( $pt ) {
		$m = (array) self::get( 'noindex_pt' );
		if ( array_key_exists( $pt, $m ) ) {
			return ! empty( $m[ $pt ] ); // The site owner decided on the Titles screen.
		}
		return in_array( $pt, self::TEMPLATE_TYPES, true );
	}

	public static function title_for_tax( $tax ) {
		$m = (array) self::get( 'title_tax' );
		return ! empty( $m[ $tax ] ) ? $m[ $tax ] : '%term% %sep% %sitename%';
	}

	public static function desc_for_tax( $tax ) {
		$m = (array) self::get( 'desc_tax' );
		return ! empty( $m[ $tax ] ) ? $m[ $tax ] : '%term_description%';
	}

	public static function noindex_tax( $tax ) {
		$m = (array) self::get( 'noindex_tax' );
		return ! empty( $m[ $tax ] );
	}

	/** Public post types and taxonomies Compass manages. */
	public static function post_types() {
		return array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) );
	}

	public static function taxonomies() {
		return get_taxonomies( array( 'public' => true ) );
	}
}
