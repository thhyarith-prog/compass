<?php
/**
 * Tools: verification codes, import, breadcrumbs, 404 monitor switch.
 *
 * @package Compass
 */

defined( 'ABSPATH' ) || exit;

$s       = Compass_Settings::all();
$counts  = Compass_Import::counts();
$sources = Compass_Import::sources();
$verify  = array(
	'verify_google'    => array( 'Google Search Console', 'https://search.google.com/search-console' ),
	'verify_bing'      => array( 'Bing Webmaster Tools', 'https://www.bing.com/webmasters' ),
	'verify_yandex'    => array( 'Yandex Webmaster', 'https://webmaster.yandex.com' ),
	'verify_baidu'     => array( 'Baidu Webmaster', 'https://ziyuan.baidu.com' ),
	'verify_pinterest' => array( 'Pinterest', 'https://www.pinterest.com/settings/claim' ),
);
?>
<section class="pd-card">
	<h2><?php esc_html_e( 'Import from another SEO plugin', 'compass' ); ?></h2>
	<p class="pd-lead"><?php esc_html_e( 'Copies each post\'s SEO title, description, focus keyphrase, canonical, noindex and social settings. Values already set in Compass are kept, and nothing is removed from the other plugin.', 'compass' ); ?></p>
	<div class="cp-imports">
		<?php foreach ( $sources as $id => $src ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cp-import">
				<input type="hidden" name="action" value="compass_import"><input type="hidden" name="source" value="<?php echo esc_attr( $id ); ?>">
				<?php wp_nonce_field( 'compass_import' ); ?>
				<strong><?php echo esc_html( $src['name'] ); ?></strong>
				<span class="pd-muted"><?php echo esc_html( sprintf( _n( '%s post with data', '%s posts with data', $counts[ $id ], 'compass' ), number_format_i18n( $counts[ $id ] ) ) ); ?></span>
				<button class="button" <?php disabled( 0 === $counts[ $id ] ); ?>><?php esc_html_e( 'Import', 'compass' ); ?></button>
			</form>
		<?php endforeach; ?>
	</div>
</section>

<?php Compass_Admin::form_start( 'tools' ); ?>
<section class="pd-card">
	<h2><?php esc_html_e( 'Verify site ownership', 'compass' ); ?></h2>
	<p class="pd-lead"><?php esc_html_e( 'Choose the "HTML tag" method in each service and paste the code (or the whole tag) here. It is added to your home page only.', 'compass' ); ?></p>
	<div class="pd-grid">
		<?php foreach ( $verify as $key => $v ) : ?>
			<p><label class="pd-label" for="cp-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $v[0] ); ?></label><input type="text" id="cp-<?php echo esc_attr( $key ); ?>" name="cp[<?php echo esc_attr( $key ); ?>]" class="large-text code" value="<?php echo esc_attr( $s[ $key ] ); ?>"><a href="<?php echo esc_url( $v[1] ); ?>" target="_blank" rel="noopener" class="cp-ext"><?php echo esc_html( sprintf( __( 'Open %s', 'compass' ), $v[0] ) ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'compass' ); ?></span></a></p>
		<?php endforeach; ?>
	</div>
</section>

<section class="pd-card">
	<h2><?php esc_html_e( 'Breadcrumbs', 'compass' ); ?></h2>
	<p class="pd-lead"><?php echo wp_kses( __( 'Breadcrumbs are always in your structured data. To show them on the page, add the <code>[compass_breadcrumbs]</code> shortcode, or <code>&lt;?php compass_breadcrumbs(); ?&gt;</code> in your theme.', 'compass' ), array( 'code' => array() ) ); ?></p>
	<div class="cp-two">
		<p><label class="pd-label" for="cp-bc_home"><?php esc_html_e( 'Home label', 'compass' ); ?></label><input type="text" id="cp-bc_home" name="cp[bc_home]" class="regular-text" value="<?php echo esc_attr( $s['bc_home'] ); ?>"></p>
		<p><label class="pd-label" for="cp-bc_sep"><?php esc_html_e( 'Separator', 'compass' ); ?></label><input type="text" id="cp-bc_sep" name="cp[bc_sep]" class="small-text" value="<?php echo esc_attr( $s['bc_sep'] ); ?>"></p>
	</div>
</section>

<section class="pd-card">
	<h2><?php esc_html_e( '404 monitor', 'compass' ); ?></h2>
	<div class="pd-rows"><?php Compass_Admin::switch_row( 'cp[log_404]', (bool) $s['log_404'], __( '404 monitor', 'compass' ), __( 'Records addresses that don\'t exist (with the page that linked to them) under Redirects & 404s. Keeps the latest 1,000.', 'compass' ) ); ?></div>
</section>
<?php
Compass_Admin::form_end();
