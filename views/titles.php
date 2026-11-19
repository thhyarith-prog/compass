<?php
/**
 * Titles & meta.
 *
 * @package Compass
 */

defined( 'ABSPATH' ) || exit;

$s = Compass_Settings::all();
Compass_Admin::form_start( 'titles' );
?>
<section class="pd-card">
	<h2><?php esc_html_e( 'Title separator', 'compass' ); ?></h2>
	<p class="pd-lead"><?php esc_html_e( 'Shown between the page name and the site name in search results and browser tabs.', 'compass' ); ?></p>
	<div class="pd-picks" role="radiogroup" aria-label="<?php esc_attr_e( 'Title separator', 'compass' ); ?>">
		<?php foreach ( Compass_Settings::SEPARATORS as $sep ) : ?>
			<label class="pd-pick cp-sep"><input type="radio" name="cp[sep]" value="<?php echo esc_attr( $sep ); ?>" <?php checked( $sep, $s['sep'] ); ?>><span><?php echo esc_html( $sep ); ?></span></label>
		<?php endforeach; ?>
	</div>
	<p class="description"><?php echo wp_kses( __( 'Variables you can use in any title or description: <code>%title%</code> <code>%sitename%</code> <code>%tagline%</code> <code>%sep%</code> <code>%excerpt%</code> <code>%category%</code> <code>%term%</code> <code>%term_description%</code> <code>%author%</code> <code>%year%</code>', 'compass' ), array( 'code' => array() ) ); ?></p>
</section>

<section class="pd-card">
	<h2><?php esc_html_e( 'Home page', 'compass' ); ?></h2>
	<div class="cp-fields">
		<p><label class="pd-label" for="cp-title_home"><?php esc_html_e( 'Title', 'compass' ); ?></label><input type="text" id="cp-title_home" name="cp[title_home]" class="large-text" value="<?php echo esc_attr( $s['title_home'] ); ?>"></p>
		<p><label class="pd-label" for="cp-desc_home"><?php esc_html_e( 'Description', 'compass' ); ?></label><textarea id="cp-desc_home" name="cp[desc_home]" rows="2" class="large-text" placeholder="<?php echo esc_attr( get_bloginfo( 'description' ) ); ?>"><?php echo esc_textarea( $s['desc_home'] ); ?></textarea></p>
	</div>
	<?php if ( 'page' === get_option( 'show_on_front' ) && get_option( 'page_on_front' ) ) : ?>
		<p class="description"><?php esc_html_e( 'Your home page is a static page: a title or description written in its Compass panel takes priority over these.', 'compass' ); ?></p>
	<?php endif; ?>
</section>

<section class="pd-card">
	<h2><?php esc_html_e( 'Content types', 'compass' ); ?></h2>
	<p class="pd-lead"><?php esc_html_e( 'The default title and description for each type. Any post can override them in its Compass panel.', 'compass' ); ?></p>
	<div class="cp-types">
		<?php foreach ( Compass_Settings::post_types() as $pt ) : ?>
			<?php $obj = get_post_type_object( $pt ); ?>
			<div class="cp-type">
				<h3><?php echo esc_html( $obj->labels->name ); ?> <code><?php echo esc_html( $pt ); ?></code></h3>
				<p><label class="pd-label" for="cp-tpt-<?php echo esc_attr( $pt ); ?>"><?php esc_html_e( 'Title', 'compass' ); ?></label><input type="text" id="cp-tpt-<?php echo esc_attr( $pt ); ?>" name="cp[title_pt][<?php echo esc_attr( $pt ); ?>]" class="large-text" value="<?php echo esc_attr( Compass_Settings::title_for_pt( $pt ) ); ?>"></p>
				<p><label class="pd-label" for="cp-dpt-<?php echo esc_attr( $pt ); ?>"><?php esc_html_e( 'Description', 'compass' ); ?></label><input type="text" id="cp-dpt-<?php echo esc_attr( $pt ); ?>" name="cp[desc_pt][<?php echo esc_attr( $pt ); ?>]" class="large-text" value="<?php echo esc_attr( Compass_Settings::desc_for_pt( $pt ) ); ?>"></p>
				<label class="cp-hide"><input type="checkbox" name="cp[noindex_pt][<?php echo esc_attr( $pt ); ?>]" value="1" <?php checked( Compass_Settings::noindex_pt( $pt ) ); ?>> <?php esc_html_e( 'Hide all from search results', 'compass' ); ?></label>
			</div>
		<?php endforeach; ?>
	</div>
</section>

<section class="pd-card">
	<h2><?php esc_html_e( 'Categories, tags and other archives', 'compass' ); ?></h2>
	<div class="cp-types">
		<?php foreach ( Compass_Settings::taxonomies() as $tax ) : ?>
			<?php $obj = get_taxonomy( $tax ); ?>
			<div class="cp-type">
				<h3><?php echo esc_html( $obj->labels->name ); ?> <code><?php echo esc_html( $tax ); ?></code></h3>
				<p><label class="pd-label" for="cp-ttax-<?php echo esc_attr( $tax ); ?>"><?php esc_html_e( 'Title', 'compass' ); ?></label><input type="text" id="cp-ttax-<?php echo esc_attr( $tax ); ?>" name="cp[title_tax][<?php echo esc_attr( $tax ); ?>]" class="large-text" value="<?php echo esc_attr( Compass_Settings::title_for_tax( $tax ) ); ?>"></p>
				<p><label class="pd-label" for="cp-dtax-<?php echo esc_attr( $tax ); ?>"><?php esc_html_e( 'Description', 'compass' ); ?></label><input type="text" id="cp-dtax-<?php echo esc_attr( $tax ); ?>" name="cp[desc_tax][<?php echo esc_attr( $tax ); ?>]" class="large-text" value="<?php echo esc_attr( Compass_Settings::desc_for_tax( $tax ) ); ?>"></p>
				<label class="cp-hide"><input type="checkbox" name="cp[noindex_tax][<?php echo esc_attr( $tax ); ?>]" value="1" <?php checked( Compass_Settings::noindex_tax( $tax ) ); ?>> <?php esc_html_e( 'Hide all from search results', 'compass' ); ?></label>
			</div>
		<?php endforeach; ?>
	</div>
	<div class="pd-rows" style="margin-top:16px">
		<?php
		Compass_Admin::switch_row( 'cp[noindex_author]', (bool) $s['noindex_author'], __( 'Hide author archives', 'compass' ), __( 'Recommended for single-author sites: the author page repeats the blog.', 'compass' ), '', '<label class="pd-label" for="cp-title_author">' . esc_html__( 'Author archive title', 'compass' ) . '</label><input type="text" id="cp-title_author" name="cp[title_author]" class="large-text" value="' . esc_attr( $s['title_author'] ) . '">' );
		Compass_Admin::switch_row( 'cp[noindex_date]', (bool) $s['noindex_date'], __( 'Hide date archives', 'compass' ), __( 'Pages like /2026/11/ only repeat your posts; hiding them avoids duplicate content.', 'compass' ), Compass_Admin::badge( __( 'Recommended', 'compass' ), 'ok' ) );
		?>
	</div>
	<div class="cp-fields" style="margin-top:16px">
		<p><label class="pd-label" for="cp-title_search"><?php esc_html_e( 'Search results title', 'compass' ); ?></label><input type="text" id="cp-title_search" name="cp[title_search]" class="large-text" value="<?php echo esc_attr( $s['title_search'] ); ?>"></p>
		<p><label class="pd-label" for="cp-title_404"><?php esc_html_e( '"Page not found" title', 'compass' ); ?></label><input type="text" id="cp-title_404" name="cp[title_404]" class="large-text" value="<?php echo esc_attr( $s['title_404'] ); ?>"></p>
	</div>
	<p class="description"><?php esc_html_e( 'Search results and "page not found" pages are always hidden from search engines.', 'compass' ); ?></p>
</section>
<?php
Compass_Admin::form_end();
