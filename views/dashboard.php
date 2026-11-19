<?php
/**
 * Dashboard: search visibility health.
 *
 * @package Compass
 */

defined( 'ABSPATH' ) || exit;

$checks = Compass_Admin::checks();
usort(
	$checks,
	function ( $a, $b ) {
		$rank = array( 'critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3 );
		return ( (int) $a[0] - (int) $b[0] ) ?: ( $rank[ $a[3] ] - $rank[ $b[3] ] ); // Failing first, worst first.
	}
);
$passed = count( array_filter( wp_list_pluck( $checks, 0 ) ) );
$pct    = (int) round( 100 * $passed / max( 1, count( $checks ) ) );
list( $total, $hidden, $no_desc ) = Compass_Admin::content_stats();
$f404     = Compass_Redirects::log_count_since( time() - WEEK_IN_SECONDS );
$redirs   = count( Compass_Redirects::all() );
$public   = (bool) get_option( 'blog_public' );
$conflict = Compass::conflict();
$tone     = ! $public ? 'bad' : ( $pct >= 75 ? 'ok' : 'warn' );
?>
<?php if ( ! $public ) : ?>
	<section class="pd-note pd-note--bad cp-alert">
		<strong><?php esc_html_e( 'Your whole site is hidden from Google and every other search engine.', 'compass' ); ?></strong>
		<span><?php esc_html_e( '"Discourage search engines from indexing this site" is ticked in Settings → Reading. That is normal while a site is being built, but once it is live nothing will appear in search results.', 'compass' ); ?></span>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="compass_fix_visibility"><?php wp_nonce_field( 'compass_fix_visibility' ); ?><button class="button button-primary"><?php esc_html_e( 'Allow search engines', 'compass' ); ?></button></form>
	</section>
<?php endif; ?>
<?php if ( $conflict ) : ?>
	<p class="pd-note pd-note--warn"><?php echo esc_html( sprintf( __( '%s is active, so Compass is not printing tags on your pages (two SEO plugins would duplicate them). Import its data under Tools, then deactivate it.', 'compass' ), $conflict ) ); ?></p>
<?php endif; ?>

<div class="pd-grid pd-grid--wide">
	<section class="pd-card cp-score cp-score--<?php echo esc_attr( $tone ); ?>">
		<div class="cp-ring" style="--p:<?php echo (int) $pct; ?>" role="img" aria-label="<?php echo esc_attr( sprintf( __( 'Search visibility: %d%%', 'compass' ), $pct ) ); ?>"><span><?php echo (int) $pct; ?><small>%</small></span></div>
		<div>
			<h2><?php esc_html_e( 'Search visibility', 'compass' ); ?></h2>
			<p class="pd-lead"><?php echo esc_html( sprintf( /* translators: 1: passed, 2: total */ __( '%1$d of %2$d site-wide essentials are in place.', 'compass' ), $passed, count( $checks ) ) ); ?></p>
			<div class="pd-actions">
				<a class="button" href="<?php echo esc_url( get_sitemap_url( 'index' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View sitemap', 'compass' ); ?></a>
				<a class="button" href="<?php echo esc_url( home_url( '/robots.txt' ) ); ?>" target="_blank" rel="noopener">robots.txt</a>
				<a class="button" href="<?php echo esc_url( 'https://search.google.com/test/rich-results?url=' . rawurlencode( home_url( '/' ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Test rich results', 'compass' ); ?></a>
			</div>
		</div>
	</section>
	<section class="pd-card">
		<h2><?php esc_html_e( 'Essentials', 'compass' ); ?></h2>
		<ul class="pd-checklist cp-checks">
			<?php foreach ( $checks as $c ) : ?>
				<li class="<?php echo $c[0] ? '' : 'is-off'; ?>">
					<span class="pd-tick<?php echo $c[0] ? '' : ' pd-tick--off cp-tick--' . esc_attr( $c[3] ); ?>" aria-hidden="true"><?php echo $c[0] ? '&#10003;' : '!'; ?></span>
					<span><?php if ( ! $c[0] ) : ?><span class="screen-reader-text"><?php esc_html_e( 'To do:', 'compass' ); ?> </span><?php endif; ?><?php echo esc_html( $c[1] ); ?><?php if ( ! $c[0] ) : ?><small><?php echo esc_html( $c[2] ); ?></small><?php endif; ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</section>
</div>

<div class="pd-grid pd-grid--4">
	<div class="pd-stat"><span class="pd-stat__num"><?php echo esc_html( number_format_i18n( max( 0, $total - $hidden ) ) ); ?></span><span class="pd-stat__label"><?php esc_html_e( 'Pages in search', 'compass' ); ?></span><span class="pd-stat__sub"><?php echo esc_html( sprintf( _n( '%s hidden on purpose', '%s hidden on purpose', $hidden, 'compass' ), number_format_i18n( $hidden ) ) ); ?></span></div>
	<div class="pd-stat"><span class="pd-stat__num"><?php echo esc_html( number_format_i18n( $no_desc ) ); ?></span><span class="pd-stat__label"><?php esc_html_e( 'Without a written description', 'compass' ); ?></span><span class="pd-stat__sub"><?php esc_html_e( 'Compass uses the first lines meanwhile.', 'compass' ); ?></span></div>
	<div class="pd-stat"><span class="pd-stat__num"><?php echo esc_html( number_format_i18n( $f404 ) ); ?></span><span class="pd-stat__label"><?php esc_html_e( 'Broken-link visits (7 days)', 'compass' ); ?></span><span class="pd-stat__sub"><a href="<?php echo esc_url( Compass_Admin::url( 'redirects' ) ); ?>"><?php esc_html_e( 'Fix with a redirect', 'compass' ); ?></a></span></div>
	<div class="pd-stat"><span class="pd-stat__num"><?php echo esc_html( number_format_i18n( $redirs ) ); ?></span><span class="pd-stat__label"><?php esc_html_e( 'Redirects', 'compass' ); ?></span><span class="pd-stat__sub"><?php esc_html_e( 'Old addresses sent to new ones.', 'compass' ); ?></span></div>
</div>

<section class="pd-card">
	<h2><?php esc_html_e( 'Next steps', 'compass' ); ?></h2>
	<ol class="cp-steps">
		<li><?php echo wp_kses( sprintf( __( 'Fill in your business details in <a href="%s">Social &amp; schema</a> so Google can show your logo, address and opening hours.', 'compass' ), esc_url( Compass_Admin::url( 'social' ) ) ), array( 'a' => array( 'href' => array() ) ) ); ?></li>
		<li><?php echo wp_kses( sprintf( __( 'Verify the site in <a href="%1$s" target="_blank" rel="noopener">Google Search Console</a> (paste the code under <a href="%2$s">Tools</a>), then submit your sitemap: %3$s', 'compass' ), 'https://search.google.com/search-console', esc_url( Compass_Admin::url( 'tools' ) ), '<code>' . esc_html( get_sitemap_url( 'index' ) ) . '</code>' ), array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ), 'code' => array() ) ); ?></li>
		<li><?php esc_html_e( 'Open your most important pages and set a focus keyphrase in the Compass panel under the editor; follow the checks until it says Good.', 'compass' ); ?></li>
	</ol>
</section>
