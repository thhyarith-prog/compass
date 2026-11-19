<?php
/**
 * Redirects & 404s.
 *
 * @package Compass
 */

defined( 'ABSPATH' ) || exit;

$redirects = Compass_Redirects::all();
$log       = Compass_Redirects::log( 200 );
$prefill   = isset( $_GET['source'] ) ? sanitize_text_field( wp_unslash( $_GET['source'] ) ) : ''; // phpcs:ignore -- pre-fills the form only.
$prefill_t = isset( $_GET['target'] ) ? sanitize_text_field( wp_unslash( $_GET['target'] ) ) : ''; // phpcs:ignore -- kept after a failed save.
$codes     = array(
	301 => __( '301 Moved permanently', 'compass' ),
	302 => __( '302 Moved temporarily', 'compass' ),
	307 => __( '307 Temporary (keep method)', 'compass' ),
	410 => __( '410 Gone (deleted for good)', 'compass' ),
);
$fmt = get_option( 'date_format' );
?>
<section class="pd-card" id="cp-add">
	<h2><?php esc_html_e( 'Add a redirect', 'compass' ); ?></h2>
	<p class="pd-lead"><?php esc_html_e( 'Send visitors and search engines from an old address to the new one, keeping the old page\'s ranking. Changing a post\'s URL in WordPress already redirects automatically.', 'compass' ); ?></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cp-redirect-form">
		<input type="hidden" name="action" value="compass_redirect_add">
		<?php wp_nonce_field( 'compass_redirect_add' ); ?>
		<p><label class="pd-label" for="cp-src"><?php esc_html_e( 'Old address', 'compass' ); ?></label><input type="text" id="cp-src" name="source" class="large-text code" value="<?php echo esc_attr( $prefill ); ?>" placeholder="/old-page/" required></p>
		<p><label class="pd-label" for="cp-dst"><?php esc_html_e( 'New address', 'compass' ); ?></label><input type="text" id="cp-dst" name="target" class="large-text code" value="<?php echo esc_attr( $prefill_t ); ?>" placeholder="/new-page/ or https://…" data-target></p>
		<p><label class="pd-label" for="cp-code"><?php esc_html_e( 'Type', 'compass' ); ?></label><select id="cp-code" name="code" data-code><?php foreach ( $codes as $c => $label ) : ?><option value="<?php echo (int) $c; ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></p>
		<p class="cp-redirect-form__go"><button class="button button-primary"><?php esc_html_e( 'Save redirect', 'compass' ); ?></button></p>
	</form>
	<p class="description"><?php echo wp_kses( __( 'End the old address with <code>*</code> to redirect a whole section, e.g. <code>/old-blog/*</code>.', 'compass' ), array( 'code' => array() ) ); ?></p>
</section>

<section class="pd-card">
	<h2><?php esc_html_e( 'Redirects', 'compass' ); ?> <span class="pd-count"><?php echo count( $redirects ); ?></span></h2>
	<?php if ( ! $redirects ) : ?>
		<p class="pd-empty"><?php esc_html_e( 'No redirects yet.', 'compass' ); ?></p>
	<?php else : ?>
		<div class="pd-table-wrap"><table class="pd-table">
			<thead><tr><th><?php esc_html_e( 'Old address', 'compass' ); ?></th><th><?php esc_html_e( 'Goes to', 'compass' ); ?></th><th><?php esc_html_e( 'Type', 'compass' ); ?></th><th class="num"><?php esc_html_e( 'Visits', 'compass' ); ?></th><th><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'compass' ); ?></span></th></tr></thead>
			<tbody>
			<?php foreach ( $redirects as $r ) : ?>
				<tr>
					<td><code><?php echo esc_html( $r['source'] ); ?></code></td>
					<td><?php echo 410 === (int) $r['code'] ? '<span class="pd-muted">—</span>' : '<code>' . esc_html( $r['target'] ) . '</code>'; ?></td>
					<td><span class="pd-badge<?php echo 410 === (int) $r['code'] ? ' pd-badge--warn' : ' pd-badge--info'; ?>"><?php echo (int) $r['code']; ?></span></td>
					<td class="num"><?php echo esc_html( number_format_i18n( $r['hits'] ) ); ?><?php if ( $r['last_hit'] ) : ?><br><small class="pd-muted"><?php echo esc_html( sprintf( __( '%s ago', 'compass' ), human_time_diff( strtotime( $r['last_hit'] . ' UTC' ) ) ) ); ?></small><?php endif; ?></td>
					<td class="num"><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-pd-confirm="<?php echo esc_attr( sprintf( __( 'Delete the redirect from %s?', 'compass' ), $r['source'] ) ); ?>"><input type="hidden" name="action" value="compass_redirect_delete"><input type="hidden" name="id" value="<?php echo (int) $r['id']; ?>"><?php wp_nonce_field( 'compass_redirect_delete' ); ?><button class="button button-small pd-danger"><?php esc_html_e( 'Delete', 'compass' ); ?><span class="screen-reader-text"> <?php echo esc_html( $r['source'] ); ?></span></button></form></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table></div>
	<?php endif; ?>
</section>

<section class="pd-card">
	<h2><?php esc_html_e( 'Pages not found (404)', 'compass' ); ?> <span class="pd-count"><?php echo count( $log ); ?></span></h2>
	<p class="pd-lead"><?php esc_html_e( 'Addresses visitors or search engines tried that don\'t exist, most recent first. Redirect the ones that matter; attack probes (.php, wp-admin…) are not listed.', 'compass' ); ?></p>
	<?php if ( ! Compass_Settings::get( 'log_404' ) ) : ?>
		<p class="pd-note pd-note--info"><?php esc_html_e( 'The 404 monitor is off (Tools).', 'compass' ); ?></p>
	<?php elseif ( ! $log ) : ?>
		<p class="pd-empty"><?php esc_html_e( 'No broken links recorded.', 'compass' ); ?></p>
	<?php else : ?>
		<div class="pd-table-wrap"><table class="pd-table">
			<thead><tr><th><?php esc_html_e( 'Address', 'compass' ); ?></th><th><?php esc_html_e( 'Linked from', 'compass' ); ?></th><th class="num"><?php esc_html_e( 'Visits', 'compass' ); ?></th><th><?php esc_html_e( 'Last seen', 'compass' ); ?></th><th><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'compass' ); ?></span></th></tr></thead>
			<tbody>
			<?php foreach ( $log as $row ) : ?>
				<tr>
					<td><code><?php echo esc_html( $row['path'] ); ?></code></td>
					<td class="cp-ref"><?php echo $row['referrer'] ? '<a href="' . esc_url( $row['referrer'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( preg_replace( '#^https?://#', '', $row['referrer'] ) ) . '</a>' : '<span class="pd-muted">' . esc_html__( 'Direct / unknown', 'compass' ) . '</span>'; ?></td>
					<td class="num"><?php echo esc_html( number_format_i18n( $row['hits'] ) ); ?></td>
					<td><?php echo esc_html( sprintf( __( '%s ago', 'compass' ), human_time_diff( strtotime( $row['last_seen'] . ' UTC' ) ) ) ); ?></td>
					<td class="num"><a class="button button-small" href="<?php echo esc_url( Compass_Admin::url( 'redirects', array( 'source' => $row['path'] ) ) . '#cp-add' ); ?>"><?php esc_html_e( 'Redirect', 'compass' ); ?><span class="screen-reader-text"> <?php echo esc_html( $row['path'] ); ?></span></a></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table></div>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-pd-confirm="<?php esc_attr_e( 'Clear the whole 404 list?', 'compass' ); ?>" style="margin-top:12px"><input type="hidden" name="action" value="compass_log_clear"><?php wp_nonce_field( 'compass_log_clear' ); ?><button class="button"><?php esc_html_e( 'Clear list', 'compass' ); ?></button></form>
	<?php endif; ?>
</section>
