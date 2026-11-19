<?php
/**
 * Structured data (schema.org JSON-LD) as one connected @graph: the website, who runs it
 * (organization, local business or person), the page, the article and its breadcrumb trail.
 * This is what Google uses for rich results and the knowledge panel.
 *
 * @package Compass
 */

defined( 'ABSPATH' ) || exit;

class Compass_Schema {

	public static function publisher() {
		$s    = Compass_Settings::all();
		$home = home_url( '/' );
		$name = $s['org_name'] ? $s['org_name'] : get_bloginfo( 'name' );
		if ( 'Person' === $s['org_type'] ) {
			$p = array( '@type' => 'Person', '@id' => $home . '#person', 'name' => $name );
		} else {
			$type = 'LocalBusiness' === $s['org_type'] ? $s['biz_type'] : 'Organization';
			$p    = array( '@type' => $type, '@id' => $home . '#organization', 'name' => $name, 'url' => $home );
		}
		$logo = $s['org_logo'] ? $s['org_logo'] : ( has_site_icon() ? get_site_icon_url( 512 ) : '' );
		if ( $logo ) {
			$p[ 'Person' === $s['org_type'] ? 'image' : 'logo' ] = array( '@type' => 'ImageObject', 'url' => $logo );
		}
		if ( $s['same_as'] ) {
			$p['sameAs'] = array_values( $s['same_as'] );
		}
		if ( 'LocalBusiness' === $s['org_type'] ) {
			$addr = array_filter(
				array(
					'@type'           => 'PostalAddress',
					'streetAddress'   => $s['biz_street'],
					'addressLocality' => $s['biz_city'],
					'addressRegion'   => $s['biz_region'],
					'postalCode'      => $s['biz_postcode'],
					'addressCountry'  => $s['biz_country'],
				)
			);
			if ( count( $addr ) > 1 ) {
				$p['address'] = $addr;
			}
			if ( $s['biz_phone'] ) {
				$p['telephone'] = $s['biz_phone'];
			}
			if ( $s['biz_price'] ) {
				$p['priceRange'] = $s['biz_price'];
			}
			if ( $s['biz_hours'] ) {
				$p['openingHours'] = array_values( $s['biz_hours'] );
			}
			if ( is_numeric( $s['biz_lat'] ) && is_numeric( $s['biz_lng'] ) ) {
				$p['geo'] = array( '@type' => 'GeoCoordinates', 'latitude' => (float) $s['biz_lat'], 'longitude' => (float) $s['biz_lng'] );
			}
			if ( $logo ) {
				$p['image'] = $logo;
			}
		}
		return $p;
	}

	public static function graph( array $c ) {
		$home  = home_url( '/' );
		$pub   = self::publisher();
		$graph = array(
			array(
				'@type'           => 'WebSite',
				'@id'             => $home . '#website',
				'url'             => $home,
				'name'            => get_bloginfo( 'name' ),
				'description'     => get_bloginfo( 'description' ),
				'inLanguage'      => get_bloginfo( 'language' ),
				'publisher'       => array( '@id' => $pub['@id'] ),
				'potentialAction' => array(
					'@type'       => 'SearchAction',
					'target'      => array( '@type' => 'EntryPoint', 'urlTemplate' => home_url( '/?s={search_term_string}' ) ),
					'query-input' => 'required name=search_term_string',
				),
			),
			$pub,
		);
		$url = $c['canonical'] ? $c['canonical'] : '';
		if ( ! $url ) {
			return $graph;
		}
		$page = array(
			'@type'      => is_search() ? 'SearchResultsPage' : ( is_singular() || is_front_page() ? 'WebPage' : 'CollectionPage' ),
			'@id'        => $url . '#webpage',
			'url'        => $url,
			'name'       => $c['title'],
			'isPartOf'   => array( '@id' => $home . '#website' ),
			'inLanguage' => get_bloginfo( 'language' ),
		);
		if ( $c['desc'] ) {
			$page['description'] = $c['desc'];
		}
		if ( ! empty( $c['image']['url'] ) ) {
			$page['primaryImageOfPage'] = array( '@type' => 'ImageObject', 'url' => $c['image']['url'] );
		}
		$crumbs = Compass_Breadcrumbs::trail();
		if ( count( $crumbs ) > 1 ) {
			$items = array();
			foreach ( $crumbs as $i => $cr ) {
				$item = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $cr['name'] );
				if ( ! empty( $cr['url'] ) ) {
					$item['item'] = $cr['url'];
				}
				$items[] = $item;
			}
			$graph[]            = array( '@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => $items );
			$page['breadcrumb'] = array( '@id' => $url . '#breadcrumb' );
		}
		$post = $c['post'];
		if ( $post ) {
			$page['datePublished'] = get_post_time( 'c', true, $post );
			$page['dateModified']  = get_post_modified_time( 'c', true, $post );
		}
		$graph[] = $page;
		if ( $post && 'article' === $c['type'] ) {
			$author  = get_userdata( $post->post_author );
			$article = array(
				'@type'            => 'post' === $post->post_type ? 'BlogPosting' : 'Article',
				'@id'              => $url . '#article',
				'headline'         => wp_strip_all_tags( get_the_title( $post ) ),
				'datePublished'    => get_post_time( 'c', true, $post ),
				'dateModified'     => get_post_modified_time( 'c', true, $post ),
				'mainEntityOfPage' => array( '@id' => $url . '#webpage' ),
				'isPartOf'         => array( '@id' => $url . '#webpage' ),
				'publisher'        => array( '@id' => $pub['@id'] ),
				'inLanguage'       => get_bloginfo( 'language' ),
			);
			if ( $author ) {
				$article['author'] = array( '@type' => 'Person', 'name' => $author->display_name, 'url' => get_author_posts_url( $author->ID ) );
			}
			if ( ! empty( $c['image']['url'] ) ) {
				$article['image'] = array( '@type' => 'ImageObject', 'url' => $c['image']['url'] );
			}
			if ( $c['desc'] ) {
				$article['description'] = $c['desc'];
			}
			$cats = get_the_category( $post->ID );
			if ( $cats ) {
				$article['articleSection'] = wp_list_pluck( $cats, 'name' );
			}
			$article['wordCount'] = Compass_Analysis::word_count( $post->post_content );
			$graph[]              = $article;
		}
		return apply_filters( 'compass_schema_graph', $graph, $c );
	}

	public static function script( array $c ) {
		$json = wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => self::graph( $c ) ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		return $json ? '<script type="application/ld+json" class="compass-schema">' . str_replace( '</', '<\/', $json ) . "</script>\n" : '';
	}
}
