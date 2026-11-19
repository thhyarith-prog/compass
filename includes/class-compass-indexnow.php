<?php
/**
 * IndexNow: tells Bing, Yandex, Seznam, Naver and others the moment a page is published or
 * updated, so it is re-crawled within minutes. (Google doesn't take part; it reads the sitemap.)
 * Off by default because it sends the changed URL to api.indexnow.org.
 *
 * @package Compass
 */

defined( 'ABSPATH' ) || exit;

class Compass_IndexNow {

	const HOOK = 'compass_indexnow_ping';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'serve_key' ), 1 );
		if ( Compass_Settings::get( 'indexnow' ) ) {
			add_action( 'transition_post_status', array( __CLASS__, 'on_status' ), 20, 3 );
		}
		add_action( self::HOOK, array( __CLASS__, 'ping' ) );
	}

	public static function key() {
		$k = get_option( 'compass_indexnow_key' );
		if ( ! $k ) {
			$k = strtolower( wp_generate_password( 32, false ) );
			update_option( 'compass_indexnow_key', $k, true );
		}
		return $k;
	}

	public static function key_url() {
		return home_url( '/' . self::key() . '.txt' );
	}

	/** Search engines fetch /{key}.txt to confirm the site owns the key. */
	public static function serve_key() {
		if ( ! Compass_Settings::get( 'indexnow' ) || empty( $_SERVER['REQUEST_URI'] ) ) {
			return;
		}
		$path = (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
		$key  = get_option( 'compass_indexnow_key' );
		if ( $key && untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ) . '/' . $key . '.txt' === $path ) {
			header( 'Content-Type: text/plain; charset=utf-8' );
			echo esc_html( $key );
			exit;
		}
	}

	public static function on_status( $new, $old, $post ) {
		if ( 'publish' !== $new || wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
			return;
		}
		$pto = get_post_type_object( $post->post_type );
		if ( ! $pto || ! $pto->public || Compass_Context::post_noindex( $post ) ) {
			return;
		}
		// Gather edits for a minute, then send them together (and never slow down saving).
		$queue                         = (array) get_option( 'compass_indexnow_queue', array() );
		$queue[ get_permalink( $post ) ] = 1;
		update_option( 'compass_indexnow_queue', array_slice( $queue, -500, null, true ), false );
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_single_event( time() + 60, self::HOOK );
		}
	}

	public static function ping() {
		$urls = array_keys( (array) get_option( 'compass_indexnow_queue', array() ) );
		delete_option( 'compass_indexnow_queue' );
		if ( ! $urls ) {
			return;
		}
		$r = wp_remote_post(
			'https://api.indexnow.org/indexnow',
			array(
				'timeout' => 10,
				'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
				'body'    => wp_json_encode(
					array(
						'host'        => wp_parse_url( home_url(), PHP_URL_HOST ),
						'key'         => self::key(),
						'keyLocation' => self::key_url(),
						'urlList'     => array_values( $urls ),
					)
				),
			)
		);
		update_option(
			'compass_indexnow_last',
			array(
				'time'  => time(),
				'count' => count( $urls ),
				'code'  => is_wp_error( $r ) ? 0 : (int) wp_remote_retrieve_response_code( $r ),
				'error' => is_wp_error( $r ) ? $r->get_error_message() : '',
			),
			false
		);
	}
}
