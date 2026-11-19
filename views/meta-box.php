<?php
/**
 * The Compass panel under the post editor.
 *
 * @var WP_Post $post
 * @var array   $m           Saved values.
 * @var bool    $pt_noindex  This post type is hidden in Compass settings.
 * @var string  $auto_desc   Description used when none is written.
 *
 * @package Compass
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="compass-mb" id="compass-mb">
	<div class="compass-mb__tabs" role="tablist">
		<button type="button" role="tab" aria-selected="true" aria-controls="compass-tab-search" id="compass-t-search" tabindex="0" class="is-active"><?php esc_html_e( 'Search', 'compass' ); ?> <span class="compass-mb__dot" data-score-dot aria-hidden="true"></span></button>
		<button type="button" role="tab" aria-selected="false" aria-controls="compass-tab-social" id="compass-t-social" tabindex="-1"><?php esc_html_e( 'Social', 'compass' ); ?></button>
		<button type="button" role="tab" aria-selected="false" aria-controls="compass-tab-advanced" id="compass-t-advanced" tabindex="-1"><?php esc_html_e( 'Advanced', 'compass' ); ?></button>
	</div>

	<section class="compass-mb__panel" id="compass-tab-search" role="tabpanel" aria-labelledby="compass-t-search">
		<div class="compass-serp" role="group" aria-label="<?php esc_attr_e( 'Google preview', 'compass' ); ?>">
			<div class="compass-serp__site"><span class="compass-serp__fav" aria-hidden="true"><?php echo esc_html( mb_substr( get_bloginfo( 'name' ), 0, 1 ) ); ?></span><span><strong><?php echo esc_html( get_bloginfo( 'name' ) ); ?></strong><span class="compass-serp__url" data-preview="url"></span></span></div>
			<div class="compass-serp__title" data-preview="title"></div>
			<div class="compass-serp__desc" data-preview="desc"></div>
		</div>

		<p class="compass-mb__field">
			<label for="compass-focus"><?php esc_html_e( 'Focus keyphrase', 'compass' ); ?></label>
			<input type="text" id="compass-focus" name="compass[focus]" value="<?php echo esc_attr( $m['focus'] ); ?>" placeholder="<?php esc_attr_e( 'The search people type to find this page', 'compass' ); ?>">
		</p>
		<p class="compass-mb__field">
			<label for="compass-title"><?php esc_html_e( 'SEO title', 'compass' ); ?> <span class="compass-mb__count" data-count="title" aria-hidden="true"></span></label>
			<input type="text" id="compass-title" name="compass[title]" value="<?php echo esc_attr( $m['title'] ); ?>" placeholder="<?php echo esc_attr( Compass_Settings::title_for_pt( $post->post_type ) ); ?>">
			<span class="compass-mb__meter" aria-hidden="true"><span data-meter="title"></span></span>
			<span class="compass-mb__help"><?php esc_html_e( 'Variables: %title% %sitename% %sep% %category% %year%. Empty = the site-wide template.', 'compass' ); ?></span>
		</p>
		<p class="compass-mb__field">
			<label for="compass-desc"><?php esc_html_e( 'Meta description', 'compass' ); ?> <span class="compass-mb__count" data-count="desc" aria-hidden="true"></span></label>
			<textarea id="compass-desc" name="compass[desc]" rows="3" placeholder="<?php echo esc_attr( $auto_desc ? $auto_desc : __( 'A short summary that makes people want to click (120–158 characters).', 'compass' ) ); ?>"><?php echo esc_textarea( $m['desc'] ); ?></textarea>
			<span class="compass-mb__meter" aria-hidden="true"><span data-meter="desc"></span></span>
		</p>

		<h4 class="compass-mb__h"><?php esc_html_e( 'SEO checks', 'compass' ); ?> <span class="compass-mb__score" data-score role="status" aria-live="polite"></span></h4>
		<ul class="compass-checks" data-checks></ul>
		<input type="hidden" name="compass[score]" value="<?php echo esc_attr( $m['score'] ); ?>" data-score-input>
	</section>

	<section class="compass-mb__panel" id="compass-tab-social" role="tabpanel" aria-labelledby="compass-t-social" hidden>
		<div class="compass-card">
			<div class="compass-card__img" data-social="img"><span><?php esc_html_e( 'No image: the featured image or the site default is used.', 'compass' ); ?></span></div>
			<div class="compass-card__body"><span class="compass-card__host"><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></span><strong data-social="title"></strong><span data-social="desc"></span></div>
		</div>
		<p class="compass-mb__help"><?php esc_html_e( 'How this page looks when shared on Facebook, Telegram, LinkedIn, X and Messenger.', 'compass' ); ?></p>
		<p class="compass-mb__field">
			<label for="compass-og-title"><?php esc_html_e( 'Social title', 'compass' ); ?></label>
			<input type="text" id="compass-og-title" name="compass[og_title]" value="<?php echo esc_attr( $m['og_title'] ); ?>" placeholder="<?php esc_attr_e( 'Same as the post title', 'compass' ); ?>">
		</p>
		<p class="compass-mb__field">
			<label for="compass-og-desc"><?php esc_html_e( 'Social description', 'compass' ); ?></label>
			<textarea id="compass-og-desc" name="compass[og_desc]" rows="2" placeholder="<?php esc_attr_e( 'Same as the meta description', 'compass' ); ?>"><?php echo esc_textarea( $m['og_desc'] ); ?></textarea>
		</p>
		<p class="compass-mb__field">
			<label for="compass-og-image"><?php esc_html_e( 'Social image', 'compass' ); ?></label>
			<span class="compass-mb__row"><input type="url" id="compass-og-image" name="compass[og_image]" value="<?php echo esc_attr( $m['og_image'] ); ?>" placeholder="https://"><button type="button" class="button" data-pick-image><?php esc_html_e( 'Choose image', 'compass' ); ?></button></span>
			<span class="compass-mb__help"><?php esc_html_e( 'Best size: 1200 × 630 pixels.', 'compass' ); ?></span>
		</p>
	</section>

	<section class="compass-mb__panel" id="compass-tab-advanced" role="tabpanel" aria-labelledby="compass-t-advanced" hidden>
		<?php if ( $pt_noindex ) : ?>
			<p class="compass-mb__note"><?php esc_html_e( 'All items of this type are hidden from search engines in Compass → Titles & meta.', 'compass' ); ?></p>
		<?php endif; ?>
		<p class="compass-mb__check"><label><input type="checkbox" name="compass[noindex]" value="1" <?php checked( '1', $m['noindex'] ); ?> data-noindex> <?php esc_html_e( 'Hide this page from search results (noindex)', 'compass' ); ?></label><span class="compass-mb__help"><?php esc_html_e( 'Also removes it from the sitemap. Use for thank-you pages, private offers and duplicates.', 'compass' ); ?></span></p>
		<p class="compass-mb__check"><label><input type="checkbox" name="compass[nofollow]" value="1" <?php checked( '1', $m['nofollow'] ); ?>> <?php esc_html_e( 'Ask search engines not to follow links on this page (nofollow)', 'compass' ); ?></label></p>
		<p class="compass-mb__field">
			<label for="compass-canonical"><?php esc_html_e( 'Canonical URL', 'compass' ); ?></label>
			<input type="url" id="compass-canonical" name="compass[canonical]" value="<?php echo esc_attr( $m['canonical'] ); ?>" placeholder="<?php echo esc_attr( get_permalink( $post ) ); ?>">
			<span class="compass-mb__help"><?php esc_html_e( 'Only when this content also lives at another address that should rank instead.', 'compass' ); ?></span>
		</p>
	</section>
</div>
