<?php
/**
 * Text measurements shared by the dashboard and structured data. (The live checks in the
 * editor run in editor.js with the same rules.)
 *
 * Khmer is written without spaces between words, so Khmer text is counted at roughly one
 * word per 5 Khmer characters instead of by spaces.
 *
 * @package Compass
 */

defined( 'ABSPATH' ) || exit;

class Compass_Analysis {

	public static function plain( $html ) {
		$html = preg_replace( '#<(script|style)\b.*?</\1>#is', ' ', (string) $html );
		return trim( preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( strip_shortcodes( excerpt_remove_blocks( $html ) ) ), ENT_QUOTES, 'UTF-8' ) ) );
	}

	public static function word_count( $html ) {
		$text  = self::plain( $html );
		$khmer = preg_match_all( '/[\x{1780}-\x{17FF}]/u', $text );
		$rest  = preg_replace( '/[\x{1780}-\x{17FF}\x{19E0}-\x{19FF}]+/u', ' ', $text );
		$latin = preg_match_all( '/[\p{L}\p{N}][\p{L}\p{N}\'’\-]*/u', $rest );
		return (int) $latin + (int) round( $khmer / 5 );
	}
}
