<?php
/**
 * Turns customizer values into CSS custom properties.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Curated palette presets.
 *
 * @return array
 */
function od_palettes() {
	return array(
		'aubergine' => array(
			'bg'         => '#140a12',
			'bg_alt'     => '#1a0d17',
			'surface'    => '#21121d',
			'surface_2'  => '#2b1826',
			'surface_3'  => '#3a2233',
			'gold'       => '#d9a441',
			'gold_light' => '#f0d08a',
			'gold_deep'  => '#a97a26',
			'maroon'     => '#7b1e3b',
			'marigold'   => '#e8952f',
			'text'       => '#f4eaee',
		),
		'midnight'  => array(
			'bg'         => '#080f18',
			'bg_alt'     => '#0c1622',
			'surface'    => '#10202f',
			'surface_2'  => '#16293b',
			'surface_3'  => '#1e3a4d',
			'gold'       => '#c9a227',
			'gold_light' => '#e9d38b',
			'gold_deep'  => '#8f7118',
			'maroon'     => '#123f4d',
			'marigold'   => '#2fa8a0',
			'text'       => '#e8f1f6',
		),
		'espresso'  => array(
			'bg'         => '#140f0b',
			'bg_alt'     => '#1b1410',
			'surface'    => '#231a14',
			'surface_2'  => '#2f231a',
			'surface_3'  => '#3f2f22',
			'gold'       => '#d08b4a',
			'gold_light' => '#f0c391',
			'gold_deep'  => '#9c6229',
			'maroon'     => '#6d3320',
			'marigold'   => '#e0913c',
			'text'       => '#f3e8dd',
		),
		'ink'       => array(
			'bg'         => '#0b1110',
			'bg_alt'     => '#0f1917',
			'surface'    => '#13211e',
			'surface_2'  => '#1a2d29',
			'surface_3'  => '#254039',
			'gold'       => '#c9a961',
			'gold_light' => '#ecd7a2',
			'gold_deep'  => '#94793c',
			'maroon'     => '#1f7a63',
			'marigold'   => '#d6a13c',
			'text'       => '#eaf3ef',
		),
	);
}

/**
 * Lighten or darken a hex colour.
 *
 * @param string $hex   Hex colour.
 * @param int    $steps -255..255.
 * @return string
 */
function od_shade( $hex, $steps ) {
	$hex = ltrim( (string) $hex, '#' );

	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
		return '#' . $hex;
	}

	$out = '#';

	for ( $i = 0; $i < 3; $i++ ) {
		$c    = hexdec( substr( $hex, $i * 2, 2 ) );
		$c    = max( 0, min( 255, $c + $steps ) );
		$out .= str_pad( dechex( $c ), 2, '0', STR_PAD_LEFT );
	}

	return $out;
}

/**
 * Convert a hex colour to "r, g, b" for use in rgba().
 *
 * @param string $hex Hex colour.
 * @return string
 */
function od_rgb( $hex ) {
	$hex = ltrim( (string) $hex, '#' );

	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
		return '0, 0, 0';
	}

	return hexdec( substr( $hex, 0, 2 ) ) . ', ' . hexdec( substr( $hex, 2, 2 ) ) . ', ' . hexdec( substr( $hex, 4, 2 ) );
}

/**
 * Build the inline stylesheet.
 *
 * @return string
 */
function od_dynamic_css() {
	$preset   = od_option( 'palette_preset', 'aubergine' );
	$palettes = od_palettes();
	$p        = isset( $palettes[ $preset ] ) ? $palettes[ $preset ] : $palettes['aubergine'];

	// Explicit colour settings win over the preset.
	$bg       = od_option( 'color_bg', $p['bg'] );
	$surface  = od_option( 'color_surface', $p['surface'] );
	$gold     = od_option( 'color_gold', $p['gold'] );
	$gold_lt  = od_option( 'color_gold_light', $p['gold_light'] );
	$maroon   = od_option( 'color_maroon', $p['maroon'] );
	$marigold = od_option( 'color_marigold', $p['marigold'] );
	$text     = od_option( 'color_text', $p['text'] );

	// When a non-default preset is picked, let it drive unless the user changed a colour.
	if ( 'aubergine' !== $preset && isset( $palettes[ $preset ] ) ) {
		$defaults = $palettes['aubergine'];
		$bg       = ( $bg === $defaults['bg'] ) ? $p['bg'] : $bg;
		$surface  = ( $surface === $defaults['surface'] ) ? $p['surface'] : $surface;
		$gold     = ( $gold === $defaults['gold'] ) ? $p['gold'] : $gold;
		$gold_lt  = ( $gold_lt === $defaults['gold_light'] ) ? $p['gold_light'] : $gold_lt;
		$maroon   = ( $maroon === $defaults['maroon'] ) ? $p['maroon'] : $maroon;
		$marigold = ( $marigold === $defaults['marigold'] ) ? $p['marigold'] : $marigold;
		$text     = ( $text === $defaults['text'] ) ? $p['text'] : $text;
	}

	$gold_deep = od_shade( $gold, -50 );
	$bg_alt    = od_shade( $bg, 8 );
	$surface_2 = od_shade( $surface, 12 );
	$surface_3 = od_shade( $surface, 26 );

	$font_head = od_option( 'font_head', '"Playfair Display", Georgia, serif' );
	$scale     = absint( od_option( 'font_scale', 16 ) );
	$radius    = absint( od_option( 'radius', 10 ) );
	$container = absint( od_option( 'container', 1320 ) );

	$css = ':root{';
	$css .= '--od-bg:' . $bg . ';';
	$css .= '--od-bg-alt:' . $bg_alt . ';';
	$css .= '--od-surface:' . $surface . ';';
	$css .= '--od-surface-2:' . $surface_2 . ';';
	$css .= '--od-surface-3:' . $surface_3 . ';';
	$css .= '--od-overlay:rgba(' . od_rgb( $bg ) . ',0.82);';
	$css .= '--od-gold:' . $gold . ';';
	$css .= '--od-gold-light:' . $gold_lt . ';';
	$css .= '--od-gold-deep:' . $gold_deep . ';';
	$css .= '--od-gold-grad:linear-gradient(135deg,' . $gold_deep . ' 0%,' . $gold_lt . ' 45%,' . $gold . ' 100%);';
	$css .= '--od-maroon:' . $maroon . ';';
	$css .= '--od-marigold:' . $marigold . ';';
	$css .= '--od-text:' . $text . ';';
	$css .= '--od-text-soft:rgba(' . od_rgb( $text ) . ',0.86);';
	$css .= '--od-muted:rgba(' . od_rgb( $text ) . ',0.62);';
	$css .= '--od-faint:rgba(' . od_rgb( $text ) . ',0.44);';
	$css .= '--od-line:rgba(' . od_rgb( $gold ) . ',0.18);';
	$css .= '--od-line-soft:rgba(' . od_rgb( $text ) . ',0.09);';
	$css .= '--od-shadow-gold:0 14px 40px -14px rgba(' . od_rgb( $gold ) . ',0.35);';
	$css .= '--od-font-head:' . $font_head . ';';

	if ( $scale && 16 !== $scale ) {
		$css .= '--od-base:' . $scale . 'px;';
	}

	$css .= '--od-radius-lg:' . $radius . 'px;';
	$css .= '--od-radius:' . max( 2, round( $radius * 0.4 ) ) . 'px;';
	$css .= '--od-container:' . $container . 'px;';
	$css .= '}';

	// Page background wash follows the accent colours.
	$css .= 'body{background-image:'
		. 'radial-gradient(1100px 620px at 82% -8%,rgba(' . od_rgb( $maroon ) . ',0.34),transparent 62%),'
		. 'radial-gradient(900px 520px at 6% 4%,rgba(' . od_rgb( $surface_3 ) . ',0.5),transparent 58%),'
		. 'radial-gradient(700px 700px at 50% 118%,rgba(' . od_rgb( $gold_deep ) . ',0.16),transparent 60%);}';

	if ( $scale && 16 !== $scale ) {
		$css .= 'body{font-size:' . $scale . 'px;}';
	}

	if ( ! od_option( 'sticky_header', true ) ) {
		$css .= '.od-header{position:relative;}';
	}

	$cols = absint( od_option( 'shop_columns', 4 ) );
	if ( $cols ) {
		$css .= 'ul.products{--od-cols:' . $cols . ';}';
	}

	return $css;
}
