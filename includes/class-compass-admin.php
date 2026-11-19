<?php
/**
 * Compass admin screens (Prasat Digital UI kit).
 *
 * @package Compass
 */

defined( 'ABSPATH' ) || exit;

class Compass_Admin {

	const CAP = 'manage_options';

	/** Settings each tab's form saves; booleans are listed separately (unchecked = not posted). */
	const FIELDS = array(
		'titles'  => array( 'sep', 'title_home', 'desc_home', 'title_pt', 'desc_pt', 'title_tax', 'desc_tax', 'title_author', 'title_search', 'title_404' ),
		'social'  => array( 'og_image', 'twitter_site', 'org_type', 'org_name', 'org_logo', 'same_as', 'biz_type', 'biz_street', 'biz_city', 'biz_region', 'biz_postcode', 'biz_country', 'biz_phone', 'biz_price', 'biz_hours', 'biz_lat', 'biz_lng' ),
		'sitemap' => array( 'sitemap_skip_pt', 'sitemap_skip_tax', 'robots_extra' ),
		'tools'   => array( 'verify_google', 'verify_bing', 'verify_yandex', 'verify_baidu', 'verify_pinterest', 'bc_home', 'bc_sep' ),
	);
	const BOOLS = array(
		'titles'  => array( 'noindex_author', 'noindex_date' ),
		'social'  => array( 'og', 'schema' ),
		'sitemap' => array( 'sitemap', 'sitemap_users', 'indexnow' ),
		'tools'   => array( 'log_404' ),
	);
	const BOOL_MAPS = array( 'titles' => array( 'noindex_pt', 'noindex_tax' ) );

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'admin_body_class', array( __CLASS__, 'body_class' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		foreach ( array( 'save', 'redirect_add', 'redirect_delete', 'log_clear', 'import', 'fix_visibility' ) as $a ) {
			add_action( 'admin_post_compass_' . $a, array( __CLASS__, 'post_' . $a ) );
		}
		add_filter( 'plugin_action_links_' . plugin_basename( COMPASS_FILE ), array( __CLASS__, 'links' ) );
	}

	public static function menu() {
		add_submenu_page( Prasat_Hub::SLUG, 'Compass', 'Compass', self::CAP, 'compass', array( __CLASS__, 'render' ) );
	}

	public static function links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( self::url() ) . '">' . esc_html__( 'Settings', 'compass' ) . '</a>' );
		return $links;
	}

	public static function tabs() {
		return array(
			'dashboard' => __( 'Dashboard', 'compass' ),
			'titles'    => __( 'Titles & meta', 'compass' ),
			'social'    => __( 'Social & schema', 'compass' ),
			'sitemap'   => __( 'Sitemap & robots', 'compass' ),
			'redirects' => __( 'Redirects & 404s', 'compass' ),
			'tools'     => __( 'Tools', 'compass' ),
		);
	}

	public static function url( $tab = 'dashboard', array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => 'compass', 'tab' => $tab ), $args ), admin_url( 'admin.php' ) );
	}

	private static function on_page() {
		return isset( $_GET['page'] ) && 'compass' === $_GET['page']; // phpcs:ignore
	}

	public static function body_class( $c ) {
		return self::on_page() ? $c . ' pd-body' : $c;
	}

	public static function assets() {
		if ( ! self::on_page() ) {
			return;
		}
		$v = COMPASS_VERSION . '.' . (int) @filemtime( COMPASS_DIR . 'assets/admin.css' ) . (int) @filemtime( COMPASS_DIR . 'assets/admin.js' ); // phpcs:ignore
		wp_enqueue_media();
		wp_enqueue_style( 'compass-ui', COMPASS_URL . 'assets/prasat-ui.css', array(), $v );
		wp_enqueue_style( 'compass-admin', COMPASS_URL . 'assets/admin.css', array( 'compass-ui' ), $v );
		wp_enqueue_script( 'compass-ui', COMPASS_URL . 'assets/prasat-ui.js', array(), $v, true );
		wp_enqueue_script( 'compass-admin', COMPASS_URL . 'assets/admin.js', array( 'compass-ui' ), $v, true );
		wp_localize_script(
			'compass-admin',
			'CompassAdmin',
			array(
				'chooseImage'   => __( 'Choose an image', 'compass' ),
				'useImage'      => __( 'Use this image', 'compass' ),
				'confirmDelete' => __( 'Delete this redirect?', 'compass' ),
				'confirmClear'  => __( 'Clear the whole 404 list?', 'compass' ),
			)
		);
	}

	/* ================================================================ render */

	public static function render() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'compass' ) );
		}
		delete_transient( 'compass_welcome' );
		$tabs = self::tabs();
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard'; // phpcs:ignore
		$tab  = isset( $tabs[ $tab ] ) ? $tab : 'dashboard';
		echo '<div class="wrap pd-app compass">';
		echo '<header class="pd-brand"><div class="pd-brand__id"><span class="pd-brand__mark pd-brand__mark--icon" aria-hidden="true"><img src="' . esc_url( COMPASS_URL . 'assets/img/icon-compass.png' ) . '" alt="" width="48" height="48"></span><div><h1>Compass</h1><p class="pd-brand__tag">' . esc_html__( 'Search · Social · Schema', 'compass' ) . '</p></div></div><div class="pd-brand__side">';
		self::header_status();
		printf(
			'<a class="pd-credit" href="%1$s" target="_blank" rel="noopener noreferrer" title="prasatdigital.com"><img src="%2$s" alt="" width="20" height="20"><span>%3$s</span></a>',
			esc_url( 'https://www.prasatdigital.com' ),
			esc_url( COMPASS_URL . 'assets/img/prasat-mark-white.svg' ),
			sprintf( /* translators: %s: Prasat Digital */ esc_html__( 'by %s', 'compass' ), '<b>Prasat Digital</b>' )
		);
		echo '</div></header><nav class="pd-tabs" aria-label="' . esc_attr__( 'Compass sections', 'compass' ) . '">';
		foreach ( $tabs as $k => $label ) {
			printf( '<a href="%s" class="%s"%s>%s</a>', esc_url( self::url( $k ) ), $k === $tab ? 'is-active' : '', $k === $tab ? ' aria-current="page"' : '', esc_html( $label ) );
		}
		echo '</nav><hr class="wp-header-end">';
		include COMPASS_DIR . 'views/' . $tab . '.php';
		echo '</div>';
	}

	private static function header_status() {
		if ( ! get_option( 'blog_public' ) ) {
			$dot  = 'bad';
			$text = __( 'Hidden from search engines', 'compass' );
		} elseif ( Compass::conflict() ) {
			$dot  = 'warn';
			$text = __( 'Paused: another SEO plugin is active', 'compass' );
		} else {
			$dot  = '';
			$text = __( 'Visible in search', 'compass' );
		}
		printf( '<a class="pd-brand__status" href="%1$s"><span class="pd-dot%2$s" aria-hidden="true"></span>%3$s</a>', esc_url( self::url() ), $dot ? ' pd-dot--' . esc_attr( $dot ) : '', esc_html( $text ) );
	}

	/* ============================================================ helpers */

	public static function switch_row( $name, $on, $title, $desc, $badge = '', $more = '' ) {
		$id = 'cp-' . sanitize_html_class( str_replace( array( '[', ']' ), '-', $name ) );
		printf(
			'<div class="pd-row%1$s"><div class="pd-row__text"><label class="pd-row__title" for="%2$s">%3$s%4$s</label><p class="pd-row__desc">%5$s</p>%6$s</div><div class="pd-row__control"><span class="pd-switch"><input type="checkbox" role="switch" id="%2$s" name="%7$s" value="1"%8$s><span></span></span></div></div>',
			$on ? '' : ' is-off',
			esc_attr( $id ),
			esc_html( $title ),
			$badge, // phpcs:ignore -- built by badge().
			esc_html( $desc ),
			$more ? '<div class="pd-row__more">' . $more . '</div>' : '', // phpcs:ignore -- built from escaped parts in the views.
			esc_attr( $name ),
			checked( $on, true, false )
		);
	}

	public static function badge( $text, $tone = '' ) {
		return ' <span class="pd-badge' . ( $tone ? ' pd-badge--' . esc_attr( $tone ) : '' ) . '">' . esc_html( $text ) . '</span>';
	}

	public static function form_start( $tab ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="pd-form"><input type="hidden" name="action" value="compass_save"><input type="hidden" name="tab" value="' . esc_attr( $tab ) . '">';
		wp_nonce_field( 'compass_save' );
	}

	public static function form_end() {
		echo '<p class="pd-actions pd-save"><button class="button button-primary button-large">' . esc_html__( 'Save changes', 'compass' ) . '</button></p></form>';
	}

	/** Site-wide health checks shown on the dashboard. */
	public static function checks() {
		$home    = home_url( '/' );
		$tagline = get_bloginfo( 'description' );
		$s       = Compass_Settings::all();
		return array(
			array( (bool) get_option( 'blog_public' ), __( 'Search engines are allowed to index the site', 'compass' ), __( 'Settings → Reading → "Discourage search engines" is ticked. Nothing will appear in Google.', 'compass' ), 'critical' ),
			array( '' !== (string) get_option( 'permalink_structure' ), __( 'Readable page addresses (permalinks)', 'compass' ), __( 'Pages use ?p=123 addresses. Choose "Post name" in Settings → Permalinks.', 'compass' ), 'high' ),
			array( 0 === strpos( $home, 'https://' ), __( 'Site uses HTTPS', 'compass' ), __( 'Google prefers secure (https://) sites. Ask your host for a free SSL certificate.', 'compass' ), 'high' ),
			array( '' !== trim( get_bloginfo( 'name' ) ) && '' !== trim( $tagline ) && __( 'Just another WordPress site' ) !== $tagline, __( 'Site title and tagline filled in', 'compass' ), __( 'Settings → General: a clear title and a one-line tagline describe the site in search.', 'compass' ), 'medium' ),
			array( (bool) $s['sitemap'], __( 'XML sitemap is on', 'compass' ), __( 'Turn the sitemap on so search engines find every page.', 'compass' ), 'medium' ),
			array( '' !== $s['verify_google'], __( 'Google Search Console verified', 'compass' ), __( 'Add your Google verification code under Tools, then submit the sitemap in Search Console.', 'compass' ), 'medium' ),
			array( '' !== $s['og_image'] || has_site_icon(), __( 'Default image for shared links', 'compass' ), __( 'Pick an image under Social & schema so links without a featured image still look good when shared.', 'compass' ), 'low' ),
			array( '' !== $s['org_logo'] || has_site_icon(), __( 'Logo for Google\'s knowledge panel', 'compass' ), __( 'Add your logo under Social & schema.', 'compass' ), 'low' ),
		);
	}

	/** Published, indexable posts and how many have no written description. */
	public static function content_stats() {
		global $wpdb;
		$types = array_values( Compass_Settings::post_types() );
		if ( ! $types ) {
			return array( 0, 0, 0 );
		}
		$in      = implode( ',', array_fill( 0, count( $types ), '%s' ) );
		$total   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ($in)", $types ) ); // phpcs:ignore
		$hidden  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = %s AND m.meta_value = '1' WHERE p.post_status = 'publish' AND p.post_type IN ($in)", array_merge( array( Compass_Context::META['noindex'] ), $types ) ) ); // phpcs:ignore
		$no_desc = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = %s WHERE p.post_status = 'publish' AND p.post_type IN ($in) AND ( m.meta_value IS NULL OR m.meta_value = '' )", array_merge( array( Compass_Context::META['desc'] ), $types ) ) ); // phpcs:ignore
		return array( $total, $hidden, $no_desc );
	}

	/* =============================================================== notices */

	public static function notices() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$conflict = Compass::conflict();
		if ( $conflict && ! self::on_page() && get_transient( 'compass_welcome' ) ) { // On Compass's own screens the dashboard explains it.
			printf( '<div class="notice notice-warning"><p><strong>%s</strong> %s</p></div>', esc_html__( 'Compass is paused on your pages.', 'compass' ), esc_html( sprintf( __( '%s is active, and two SEO plugins would print every tag twice. Import its data under Compass → Tools, then deactivate it.', 'compass' ), $conflict ) ) );
		}
		if ( ! get_option( 'blog_public' ) && ! self::on_page() ) {
			printf( '<div class="notice notice-error"><p><strong>%s</strong> %s <a href="%s">%s</a></p></div>', esc_html__( 'Your site is hidden from search engines.', 'compass' ), esc_html__( '"Discourage search engines" is ticked in Settings → Reading.', 'compass' ), esc_url( self::url() ), esc_html__( 'Review in Compass', 'compass' ) );
		}
		$note = self::on_page() && isset( $_GET['cp_msg'] ) ? get_transient( 'compass_msg_' . get_current_user_id() ) : false; // phpcs:ignore
		if ( is_array( $note ) ) {
			delete_transient( 'compass_msg_' . get_current_user_id() );
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', $note['error'] ? 'error' : 'success', esc_html( $note['msg'] ) );
		}
	}

	/* ================================================================= forms */

	private static function guard( $nonce ) {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Not allowed.', 'compass' ), 403 );
		}
		check_admin_referer( $nonce );
	}

	private static function back( $tab, $msg, $error = false, array $keep = array() ) {
		// The message travels in a short-lived per-user transient, so a crafted link can't put text on the screen.
		set_transient( 'compass_msg_' . get_current_user_id(), array( 'msg' => $msg, 'error' => (bool) $error ), 60 );
		wp_safe_redirect( self::url( $tab, array_merge( array( 'cp_msg' => 1 ), $keep ) ) );
		exit;
	}

	public static function post_save() {
		self::guard( 'compass_save' );
		$tab = isset( $_POST['tab'] ) ? sanitize_key( $_POST['tab'] ) : '';
		if ( ! isset( self::FIELDS[ $tab ] ) ) {
			wp_die( 'Unknown section.' );
		}
		$in   = isset( $_POST['cp'] ) && is_array( $_POST['cp'] ) ? wp_unslash( $_POST['cp'] ) : array(); // phpcs:ignore -- sanitized per key by Compass_Settings.
		$vals = array();
		foreach ( self::FIELDS[ $tab ] as $k ) {
			if ( isset( $in[ $k ] ) ) {
				$vals[ $k ] = $in[ $k ];
			} elseif ( in_array( $k, array( 'sitemap_skip_pt', 'sitemap_skip_tax' ), true ) ) {
				$vals[ $k ] = array(); // No chip ticked.
			}
		}
		foreach ( isset( self::BOOLS[ $tab ] ) ? self::BOOLS[ $tab ] : array() as $k ) {
			$vals[ $k ] = ! empty( $in[ $k ] );
		}
		foreach ( isset( self::BOOL_MAPS[ $tab ] ) ? self::BOOL_MAPS[ $tab ] : array() as $k ) {
			$keys       = 'noindex_pt' === $k ? Compass_Settings::post_types() : Compass_Settings::taxonomies();
			$vals[ $k ] = array();
			foreach ( $keys as $key ) {
				$vals[ $k ][ $key ] = ! empty( $in[ $k ][ $key ] );
			}
		}
		Compass_Settings::update( $vals );
		self::back( $tab, __( 'Saved.', 'compass' ) );
	}

	public static function post_redirect_add() {
		self::guard( 'compass_redirect_add' );
		$r = Compass_Redirects::add(
			isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '',
			isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : '',
			isset( $_POST['code'] ) ? (int) $_POST['code'] : 301
		);
		$keep = is_wp_error( $r ) ? array(
			'source' => isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '',
			'target' => isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : '',
		) : array();
		self::back( 'redirects', is_wp_error( $r ) ? $r->get_error_message() : __( 'Redirect saved.', 'compass' ), is_wp_error( $r ), $keep );
	}

	public static function post_redirect_delete() {
		self::guard( 'compass_redirect_delete' );
		Compass_Redirects::delete( isset( $_POST['id'] ) ? (int) $_POST['id'] : 0 );
		self::back( 'redirects', __( 'Redirect deleted.', 'compass' ) );
	}

	public static function post_log_clear() {
		self::guard( 'compass_log_clear' );
		Compass_Redirects::clear_log();
		self::back( 'redirects', __( '404 list cleared.', 'compass' ) );
	}

	public static function post_import() {
		self::guard( 'compass_import' );
		$src = isset( $_POST['source'] ) ? sanitize_key( $_POST['source'] ) : '';
		$n   = Compass_Import::run( $src );
		self::back( 'tools', sprintf( /* translators: %d: number of posts */ _n( 'Imported SEO data for %d post. Existing Compass values were kept.', 'Imported SEO data for %d posts. Existing Compass values were kept.', $n, 'compass' ), $n ) );
	}

	/** One click to untick "Discourage search engines". */
	public static function post_fix_visibility() {
		self::guard( 'compass_fix_visibility' );
		update_option( 'blog_public', '1' );
		self::back( 'dashboard', __( 'Search engines can now index the site.', 'compass' ) );
	}

	/**
	 * Findings for the Prasat Digital one-click site check (Prasat_Hub::run_check).
	 */
	public static function health() {
		$ids   = array( 'blog_public', 'permalinks', 'https', 'tagline', 'sitemap', 'search_console', 'share_image', 'logo' );
		$fixes = array( 'options-reading.php', 'options-permalink.php', 'options-general.php', 'options-general.php', 'admin.php?page=compass&tab=sitemap', 'admin.php?page=compass&tab=tools', 'admin.php?page=compass&tab=social', 'admin.php?page=compass&tab=social' );
		$out   = array();
		foreach ( self::checks() as $i => $c ) {
			$out[] = array(
				'id'       => isset( $ids[ $i ] ) ? $ids[ $i ] : 'seo_' . $i,
				'status'   => $c[0] ? 'pass' : ( in_array( $c[3], array( 'critical', 'high' ), true ) ? 'fail' : 'warn' ),
				'severity' => $c[3],
				'title'    => $c[1],
				'detail'   => $c[0] ? '' : $c[2],
				'fix_url'  => admin_url( isset( $fixes[ $i ] ) ? $fixes[ $i ] : 'admin.php?page=compass' ),
			);
		}
		$other = Compass::conflict();
		if ( $other ) {
			$out[] = array( 'id' => 'seo_conflict', 'status' => 'warn', 'severity' => 'medium', 'title' => __( 'Only one SEO plugin is active', 'compass' ), 'detail' => sprintf( __( '%s is also active, so Compass stays quiet to avoid duplicate tags. Keep one of them.', 'compass' ), $other ), 'fix_url' => admin_url( 'plugins.php' ) );
		}
		list( $total, , $no_desc ) = self::content_stats();
		if ( $total ) {
			$share = $no_desc / $total;
			$out[] = array( 'id' => 'seo_descriptions', 'status' => $share > 0.5 ? 'warn' : 'pass', 'severity' => 'low', 'title' => __( 'Pages have their own search description', 'compass' ), 'detail' => $share > 0.5 ? sprintf( __( '%1$d of %2$d published pages use an automatic description. Writing your own for the main pages gets more clicks from Google.', 'compass' ), $no_desc, $total ) : '', 'fix_url' => admin_url( 'edit.php?post_type=page' ), 'fix_label' => __( 'Edit pages', 'compass' ) );
		}
		return $out;
	}
}
