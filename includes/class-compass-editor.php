<?php
/**
 * In the editor: a Compass panel under each post with a live Google preview, social card
 * settings and SEO checks; SEO fields on category/tag screens; an SEO column in post lists.
 *
 * @package Compass
 */

defined( 'ABSPATH' ) || exit;

class Compass_Editor {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_box' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		self::register_meta(); // init() itself runs late on "init", once every post type and taxonomy exists.
		foreach ( Compass_Settings::taxonomies() as $tax ) {
			add_action( $tax . '_edit_form_fields', array( __CLASS__, 'term_fields' ), 20 );
			add_action( 'edited_' . $tax, array( __CLASS__, 'save_term' ) );
		}
		foreach ( Compass_Settings::post_types() as $pt ) {
			add_filter( 'manage_' . $pt . '_posts_columns', array( __CLASS__, 'column' ) );
			add_action( 'manage_' . $pt . '_posts_custom_column', array( __CLASS__, 'column_value' ), 10, 2 );
		}
	}

	/** Visible to the REST API so the block editor keeps them when it saves. */
	public static function register_meta() {
		foreach ( Compass_Context::META as $key ) {
			register_meta(
				'post',
				$key,
				array(
					'type'          => 'string',
					'single'        => true,
					'show_in_rest'  => false,
					'auth_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}

	public static function meta_box() {
		foreach ( Compass_Settings::post_types() as $pt ) {
			add_meta_box( 'compass-seo', __( 'Compass SEO', 'compass' ), array( __CLASS__, 'render' ), $pt, 'normal', 'high' );
		}
	}

	public static function assets( $hook ) {
		$screen = get_current_screen();
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! $screen || ! in_array( $screen->post_type, Compass_Settings::post_types(), true ) ) {
			return;
		}
		$v = COMPASS_VERSION . '.' . (int) @filemtime( COMPASS_DIR . 'assets/editor.js' ) . (int) @filemtime( COMPASS_DIR . 'assets/editor.css' ); // phpcs:ignore
		wp_enqueue_media();
		wp_enqueue_style( 'compass-editor', COMPASS_URL . 'assets/editor.css', array(), $v );
		wp_enqueue_script( 'compass-editor', COMPASS_URL . 'assets/editor.js', array(), $v, true );
		global $post;
		wp_localize_script(
			'compass-editor',
			'CompassEditor',
			array(
				'sep'      => Compass_Settings::get( 'sep' ),
				'sitename' => get_bloginfo( 'name' ),
				'tagline'  => get_bloginfo( 'description' ),
				'template' => $post ? Compass_Settings::title_for_pt( $post->post_type ) : '%title% %sep% %sitename%',
				'home'     => home_url( '/' ),
				'i18n'     => array(
					'good'         => __( 'Good', 'compass' ),
					'ok'           => __( 'Needs work', 'compass' ),
					'poor'         => __( 'Poor', 'compass' ),
					'noFocus'      => __( 'Add a focus keyphrase to see keyphrase checks.', 'compass' ),
					'chars'        => __( '%1$d of %2$d characters', 'compass' ),
					'chooseImage'  => __( 'Choose a social image', 'compass' ),
					'useImage'     => __( 'Use this image', 'compass' ),
					'untitled'     => __( '(no title)', 'compass' ),
					'checks'       => array(
						'titleLen'      => __( 'SEO title length: %s', 'compass' ),
						'descLen'       => __( 'Meta description length: %s', 'compass' ),
						'descMissing'   => __( 'No meta description: search engines will pick text from the page.', 'compass' ),
						'words'         => __( 'Content length: %s words', 'compass' ),
						'kpTitle'       => __( 'Keyphrase in the SEO title', 'compass' ),
						'kpDesc'        => __( 'Keyphrase in the meta description', 'compass' ),
						'kpSlug'        => __( 'Keyphrase in the URL', 'compass' ),
						'kpIntro'       => __( 'Keyphrase in the first paragraph', 'compass' ),
						'kpHeading'     => __( 'Keyphrase in a subheading (H2/H3)', 'compass' ),
						'kpDensity'     => __( 'Keyphrase used %1$d times (%2$s%%)', 'compass' ),
						'kpAlt'         => __( 'Keyphrase in an image\'s alt text', 'compass' ),
						'imgAlt'        => __( 'Images without alt text: %d', 'compass' ),
						'internal'      => __( 'Links to other pages on this site: %d', 'compass' ),
						'external'      => __( 'Links to other sites: %d', 'compass' ),
						'noindex'       => __( 'This page is hidden from search engines', 'compass' ),
						'tooShort'      => __( 'too short', 'compass' ),
						'tooLong'       => __( 'too long', 'compass' ),
						'fine'          => __( 'good', 'compass' ),
					),
				),
			)
		);
	}

	public static function render( $post ) {
		wp_nonce_field( 'compass_save_' . $post->ID, 'compass_nonce' );
		$m = array();
		foreach ( Compass_Context::META as $k => $key ) {
			$m[ $k ] = (string) get_post_meta( $post->ID, $key, true );
		}
		$pt_noindex = Compass_Settings::noindex_pt( $post->post_type );
		$auto_desc  = Compass_Context::excerpt( $post );
		include COMPASS_DIR . 'views/meta-box.php';
	}

	public static function save( $post_id, $post ) {
		if ( ! isset( $_POST['compass_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['compass_nonce'] ) ), 'compass_save_' . $post_id ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$in = isset( $_POST['compass'] ) && is_array( $_POST['compass'] ) ? wp_unslash( $_POST['compass'] ) : array(); // phpcs:ignore -- sanitized per field below.
		$clean = array(
			'title'     => sanitize_text_field( isset( $in['title'] ) ? $in['title'] : '' ),
			'desc'      => sanitize_textarea_field( isset( $in['desc'] ) ? $in['desc'] : '' ),
			'focus'     => sanitize_text_field( isset( $in['focus'] ) ? $in['focus'] : '' ),
			'canonical' => esc_url_raw( isset( $in['canonical'] ) ? trim( $in['canonical'] ) : '' ),
			'noindex'   => empty( $in['noindex'] ) ? '' : '1',
			'nofollow'  => empty( $in['nofollow'] ) ? '' : '1',
			'og_title'  => sanitize_text_field( isset( $in['og_title'] ) ? $in['og_title'] : '' ),
			'og_desc'   => sanitize_textarea_field( isset( $in['og_desc'] ) ? $in['og_desc'] : '' ),
			'og_image'  => esc_url_raw( isset( $in['og_image'] ) ? trim( $in['og_image'] ) : '' ),
			'score'     => isset( $in['score'] ) && in_array( $in['score'], array( 'good', 'ok', 'poor' ), true ) ? $in['score'] : '',
		);
		foreach ( $clean as $k => $v ) {
			if ( '' === $v ) {
				delete_post_meta( $post_id, Compass_Context::META[ $k ] );
			} else {
				update_post_meta( $post_id, Compass_Context::META[ $k ], $v );
			}
		}
	}

	/* ================================================================ terms */

	public static function term_fields( $term ) {
		wp_nonce_field( 'compass_term_' . $term->term_id, 'compass_term_nonce' );
		$title   = (string) get_term_meta( $term->term_id, 'compass_title', true );
		$desc    = (string) get_term_meta( $term->term_id, 'compass_desc', true );
		$noindex = '1' === (string) get_term_meta( $term->term_id, 'compass_noindex', true );
		?>
		<tr class="form-field"><th colspan="2"><h2 style="margin:12px 0 0"><?php esc_html_e( 'Compass SEO', 'compass' ); ?></h2></th></tr>
		<tr class="form-field">
			<th scope="row"><label for="compass-term-title"><?php esc_html_e( 'SEO title', 'compass' ); ?></label></th>
			<td><input type="text" id="compass-term-title" name="compass_term[title]" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php echo esc_attr( Compass_Settings::title_for_tax( $term->taxonomy ) ); ?>"><p class="description"><?php esc_html_e( 'Leave empty to use the template from Compass → Titles & meta.', 'compass' ); ?></p></td>
		</tr>
		<tr class="form-field">
			<th scope="row"><label for="compass-term-desc"><?php esc_html_e( 'Meta description', 'compass' ); ?></label></th>
			<td><textarea id="compass-term-desc" name="compass_term[desc]" rows="3"><?php echo esc_textarea( $desc ); ?></textarea></td>
		</tr>
		<tr class="form-field">
			<th scope="row"><?php esc_html_e( 'Search engines', 'compass' ); ?></th>
			<td><label><input type="checkbox" name="compass_term[noindex]" value="1" <?php checked( $noindex ); ?>> <?php esc_html_e( 'Hide this archive from search results (noindex)', 'compass' ); ?></label></td>
		</tr>
		<?php
	}

	public static function save_term( $term_id ) {
		if ( ! isset( $_POST['compass_term_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['compass_term_nonce'] ) ), 'compass_term_' . $term_id ) || ! current_user_can( 'edit_term', $term_id ) ) {
			return;
		}
		$in = isset( $_POST['compass_term'] ) && is_array( $_POST['compass_term'] ) ? wp_unslash( $_POST['compass_term'] ) : array(); // phpcs:ignore
		$v  = array(
			'compass_title'   => sanitize_text_field( isset( $in['title'] ) ? $in['title'] : '' ),
			'compass_desc'    => sanitize_textarea_field( isset( $in['desc'] ) ? $in['desc'] : '' ),
			'compass_noindex' => empty( $in['noindex'] ) ? '' : '1',
		);
		foreach ( $v as $k => $val ) {
			if ( '' === $val ) {
				delete_term_meta( $term_id, $k );
			} else {
				update_term_meta( $term_id, $k, $val );
			}
		}
	}

	/* =============================================================== column */

	public static function column( $cols ) {
		$cols['compass'] = 'SEO';
		return $cols;
	}

	public static function column_value( $col, $post_id ) {
		if ( 'compass' !== $col ) {
			return;
		}
		$score   = (string) get_post_meta( $post_id, Compass_Context::META['score'], true );
		$noindex = Compass_Context::post_noindex( $post_id );
		$labels  = array( 'good' => __( 'Good', 'compass' ), 'ok' => __( 'Needs work', 'compass' ), 'poor' => __( 'Poor', 'compass' ) );
		$colors  = array( 'good' => '#0f766e', 'ok' => '#d97706', 'poor' => '#dc2626' );
		if ( $noindex ) {
			echo '<span style="display:inline-block;padding:0 6px;border-radius:4px;background:#f5f5f4;color:#78716c;font-size:11px;font-weight:600">' . esc_html__( 'Hidden', 'compass' ) . '</span>';
		} elseif ( isset( $labels[ $score ] ) ) {
			printf( '<span style="display:inline-flex;align-items:center;gap:5px"><span aria-hidden="true" style="width:9px;height:9px;border-radius:50%%;background:%s"></span>%s</span>', esc_attr( $colors[ $score ] ), esc_html( $labels[ $score ] ) );
		} else {
			echo '<span style="color:#a8a29e">—</span>';
		}
	}
}
