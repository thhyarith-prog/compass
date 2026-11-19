<?php
/**
 * Sitemap & robots.
 *
 * @package Compass
 */

defined( 'ABSPATH' ) || exit;

$s    = Compass_Settings::all();
$last = get_option( 'compass_indexnow_last' );
Compass_Admin::form_start( 'sitemap' );
?>
<section class="pd-card">
	<h2><?php esc_html_e( 'XML sitemap', 'compass' ); ?></h2>
	<p class="pd-lead"><?php echo wp_kses( sprintf( __( 'A list of every page for search engines. Your sitemap: <a href="%1$s" target="_blank" rel="noopener">%1$s</a>', 'compass' ), esc_url( get_sitemap_url( 'index' ) ) ), array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) ); ?></p>
	<div class="pd-rows">
		<?php
		Compass_Admin::switch_row( 'cp[sitemap]', (bool) $s['sitemap'], __( 'Sitemap on', 'compass' ), __( 'Hidden (noindex) pages are left out automatically, and every entry has its last-modified date.', 'compass' ), Compass_Admin::badge( __( 'Recommended', 'compass' ), 'ok' ) );
		Compass_Admin::switch_row( 'cp[sitemap_users]', (bool) $s['sitemap_users'], __( 'Include author pages', 'compass' ), __( 'Only useful for sites with several writers.', 'compass' ) );
		?>
	</div>
	<h3 id="cp-skip-h"><?php esc_html_e( 'Leave out of the sitemap', 'compass' ); ?></h3>
	<div class="pd-picks" role="group" aria-labelledby="cp-skip-h">
		<?php foreach ( Compass_Settings::post_types() as $pt ) : ?>
			<label class="pd-pick"><input type="checkbox" name="cp[sitemap_skip_pt][]" value="<?php echo esc_attr( $pt ); ?>" <?php checked( in_array( $pt, (array) $s['sitemap_skip_pt'], true ) ); ?>><span><?php echo esc_html( get_post_type_object( $pt )->labels->name ); ?></span></label>
		<?php endforeach; ?>
		<?php foreach ( Compass_Settings::taxonomies() as $tax ) : ?>
			<label class="pd-pick"><input type="checkbox" name="cp[sitemap_skip_tax][]" value="<?php echo esc_attr( $tax ); ?>" <?php checked( in_array( $tax, (array) $s['sitemap_skip_tax'], true ) ); ?>><span><?php echo esc_html( get_taxonomy( $tax )->labels->name ); ?></span></label>
		<?php endforeach; ?>
	</div>
</section>

<section class="pd-card">
	<h2><?php esc_html_e( 'Instant indexing', 'compass' ); ?></h2>
	<div class="pd-rows">
		<?php
		$more = '<p class="pd-row__desc">' . wp_kses( sprintf( __( 'Key file: <a href="%1$s" target="_blank" rel="noopener">%1$s</a>', 'compass' ), esc_url( Compass_IndexNow::key_url() ) ), array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) ) . '</p>';
		if ( $last ) {
			$more .= '<p class="pd-row__desc">' . esc_html( sprintf( __( 'Last sent %1$s ago: %2$d pages (response %3$s).', 'compass' ), human_time_diff( $last['time'] ), $last['count'], $last['code'] ? $last['code'] : $last['error'] ) ) . '</p>';
		}
		Compass_Admin::switch_row( 'cp[indexnow]', (bool) $s['indexnow'], __( 'IndexNow', 'compass' ), __( 'Tells Bing, Yandex, Naver and Seznam about new or updated pages within a minute (sends the page address to api.indexnow.org). Google doesn\'t use IndexNow; it reads your sitemap.', 'compass' ), '', $more );
		?>
	</div>
</section>

<section class="pd-card">
	<h2>robots.txt</h2>
	<p class="pd-lead"><?php echo wp_kses( sprintf( __( 'WordPress writes <a href="%s" target="_blank" rel="noopener">robots.txt</a> and adds your sitemap to it. Extra rules go below.', 'compass' ), esc_url( home_url( '/robots.txt' ) ) ), array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) ); ?></p>
	<?php if ( file_exists( ABSPATH . 'robots.txt' ) ) : ?>
		<p class="pd-note pd-note--warn"><?php esc_html_e( 'A robots.txt file exists in the site folder, so WordPress\'s version (and these rules) are not used. Delete that file to manage robots.txt here.', 'compass' ); ?></p>
	<?php endif; ?>
	<label class="pd-label" for="cp-robots_extra"><?php esc_html_e( 'Extra rules', 'compass' ); ?></label>
	<textarea id="cp-robots_extra" name="cp[robots_extra]" rows="5" class="large-text code" placeholder="User-agent: GPTBot&#10;Disallow: /"><?php echo esc_textarea( $s['robots_extra'] ); ?></textarea>
</section>
<?php
Compass_Admin::form_end();
