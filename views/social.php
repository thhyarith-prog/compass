<?php
/**
 * Social & schema.
 *
 * @package Compass
 */

defined( 'ABSPATH' ) || exit;

$s     = Compass_Settings::all();
$types = array(
	'LocalBusiness'           => __( 'Local business (general)', 'compass' ),
	'Restaurant'              => __( 'Restaurant', 'compass' ),
	'CafeOrCoffeeShop'        => __( 'Café / coffee shop', 'compass' ),
	'Store'                   => __( 'Shop / store', 'compass' ),
	'Hotel'                   => __( 'Hotel / guesthouse', 'compass' ),
	'ProfessionalService'     => __( 'Professional service', 'compass' ),
	'BeautySalon'             => __( 'Beauty salon / spa', 'compass' ),
	'TravelAgency'            => __( 'Travel agency / tours', 'compass' ),
	'RealEstateAgent'         => __( 'Real estate', 'compass' ),
	'EducationalOrganization' => __( 'School / training', 'compass' ),
	'MedicalClinic'           => __( 'Clinic', 'compass' ),
	'AutoRepair'              => __( 'Car / motorbike repair', 'compass' ),
);
$field = function ( $key, $label, $placeholder = '', $type = 'text' ) use ( $s ) {
	printf( '<p><label class="pd-label" for="cp-%1$s">%2$s</label><input type="%5$s" id="cp-%1$s" name="cp[%1$s]" class="large-text" value="%3$s" placeholder="%4$s"></p>', esc_attr( $key ), esc_html( $label ), esc_attr( $s[ $key ] ), esc_attr( $placeholder ), esc_attr( $type ) );
};
Compass_Admin::form_start( 'social' );
?>
<section class="pd-card">
	<h2 id="cp-owner-h"><?php esc_html_e( 'Site owner', 'compass' ); ?></h2>
	<p class="pd-lead"><?php esc_html_e( 'Google uses this for the knowledge panel, your logo in results and (for local businesses) maps and opening hours.', 'compass' ); ?></p>
	<div class="pd-picks cp-org" role="radiogroup" aria-labelledby="cp-owner-h">
		<label class="pd-pick"><input type="radio" name="cp[org_type]" value="Organization" <?php checked( 'Organization', $s['org_type'] ); ?>><span><?php esc_html_e( 'Company or organization', 'compass' ); ?></span></label>
		<label class="pd-pick"><input type="radio" name="cp[org_type]" value="LocalBusiness" <?php checked( 'LocalBusiness', $s['org_type'] ); ?>><span><?php esc_html_e( 'Local business with an address', 'compass' ); ?></span></label>
		<label class="pd-pick"><input type="radio" name="cp[org_type]" value="Person" <?php checked( 'Person', $s['org_type'] ); ?>><span><?php esc_html_e( 'A person', 'compass' ); ?></span></label>
	</div>
	<div class="pd-grid" style="margin:16px 0 0">
		<div>
			<?php $field( 'org_name', __( 'Name', 'compass' ), get_bloginfo( 'name' ) ); ?>
			<p><label class="pd-label" for="cp-org_logo"><?php esc_html_e( 'Logo (or photo, for a person)', 'compass' ); ?></label><span class="cp-pick-row"><input type="url" id="cp-org_logo" name="cp[org_logo]" class="large-text code" value="<?php echo esc_attr( $s['org_logo'] ); ?>" placeholder="https://"><button type="button" class="button" data-pick="cp-org_logo"><?php esc_html_e( 'Choose', 'compass' ); ?></button></span></p>
		</div>
		<div>
			<p><label class="pd-label" for="cp-same_as"><?php esc_html_e( 'Social profiles (one per line)', 'compass' ); ?></label><textarea id="cp-same_as" name="cp[same_as]" rows="4" class="large-text code" placeholder="https://www.facebook.com/yourpage&#10;https://t.me/yourchannel&#10;https://www.tiktok.com/@you"><?php echo esc_textarea( implode( "\n", (array) $s['same_as'] ) ); ?></textarea></p>
		</div>
	</div>

	<div class="cp-local" data-show-when="LocalBusiness">
		<h3><?php esc_html_e( 'Business details', 'compass' ); ?></h3>
		<p><label class="pd-label" for="cp-biz_type"><?php esc_html_e( 'Type of business', 'compass' ); ?></label>
			<select id="cp-biz_type" name="cp[biz_type]">
				<?php foreach ( $types as $k => $label ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $k, $s['biz_type'] ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<div class="pd-grid">
			<div>
				<?php
				$field( 'biz_street', __( 'Street address', 'compass' ), __( 'No. 12, Street 240', 'compass' ) );
				$field( 'biz_city', __( 'City', 'compass' ), 'Phnom Penh' );
				$field( 'biz_region', __( 'Province / region', 'compass' ), 'Phnom Penh' );
				$field( 'biz_postcode', __( 'Postal code', 'compass' ), '12207' );
				$field( 'biz_country', __( 'Country code', 'compass' ), 'KH' );
				?>
			</div>
			<div>
				<?php
				$field( 'biz_phone', __( 'Phone', 'compass' ), '+855 16 777 366', 'tel' );
				$field( 'biz_price', __( 'Price range', 'compass' ), '$$' );
				?>
				<p><label class="pd-label" for="cp-biz_hours"><?php esc_html_e( 'Opening hours (one per line)', 'compass' ); ?></label><textarea id="cp-biz_hours" name="cp[biz_hours]" rows="3" class="large-text code" placeholder="Mo-Fr 07:00-21:00&#10;Sa-Su 08:00-22:00"><?php echo esc_textarea( implode( "\n", (array) $s['biz_hours'] ) ); ?></textarea></p>
				<div class="cp-two"><?php $field( 'biz_lat', __( 'Latitude', 'compass' ), '11.5564' ); ?><?php $field( 'biz_lng', __( 'Longitude', 'compass' ), '104.9282' ); ?></div>
				<p class="description"><?php esc_html_e( 'Tip: right-click your shop in Google Maps to copy its coordinates.', 'compass' ); ?></p>
			</div>
		</div>
	</div>
</section>

<section class="pd-card">
	<h2><?php esc_html_e( 'Shared links', 'compass' ); ?></h2>
	<div class="pd-rows">
		<?php
		Compass_Admin::switch_row( 'cp[og]', (bool) $s['og'], __( 'Social cards (Open Graph and X)', 'compass' ), __( 'Title, description and image used when a page is shared on Facebook, Telegram, Messenger, LinkedIn or X.', 'compass' ), Compass_Admin::badge( __( 'Recommended', 'compass' ), 'ok' ) );
		Compass_Admin::switch_row( 'cp[schema]', (bool) $s['schema'], __( 'Structured data (schema.org)', 'compass' ), __( 'Describes the site, your business, each page, article and breadcrumb trail to Google in the format it reads for rich results.', 'compass' ), Compass_Admin::badge( __( 'Recommended', 'compass' ), 'ok' ) );
		?>
	</div>
	<div class="pd-grid" style="margin:16px 0 0">
		<p><label class="pd-label" for="cp-og_image"><?php esc_html_e( 'Default image', 'compass' ); ?></label><span class="cp-pick-row"><input type="url" id="cp-og_image" name="cp[og_image]" class="large-text code" value="<?php echo esc_attr( $s['og_image'] ); ?>" placeholder="https://"><button type="button" class="button" data-pick="cp-og_image"><?php esc_html_e( 'Choose', 'compass' ); ?></button></span><span class="description"><?php esc_html_e( 'Used when a page has no featured image. 1200 × 630 pixels works everywhere.', 'compass' ); ?></span></p>
		<p><label class="pd-label" for="cp-twitter_site"><?php esc_html_e( 'X (Twitter) username', 'compass' ); ?></label><input type="text" id="cp-twitter_site" name="cp[twitter_site]" class="regular-text" value="<?php echo esc_attr( $s['twitter_site'] ); ?>" placeholder="@yourbrand"></p>
	</div>
</section>
<?php
Compass_Admin::form_end();
