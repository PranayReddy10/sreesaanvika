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
		/*
		 * The one light palette. Everything else in the theme is built dark,
		 * so this is not simply the others inverted: the surfaces have to get
		 * *darker* than the page rather than lighter, and the gold is taken
		 * down a few steps because the bright gold that carries a dark page
		 * has almost no contrast on white.
		 */
		'ivory'     => array(
			'bg'         => '#fbf8f4',
			'bg_alt'     => '#f4ece1',
			'surface'    => '#ffffff',
			'surface_2'  => '#f7f1e8',
			'surface_3'  => '#ede2d4',
			'gold'       => '#996910',
			'gold_light' => '#c9a14a',
			'gold_deep'  => '#7d5513',
			'maroon'     => '#7b1e3b',
			'marigold'   => '#bf6f1b',
			'text'       => '#2a1b22',
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
 * Is this colour light enough that dark text belongs on it?
 *
 * Used to decide which way the surface shades run: on a dark page a card is
 * lighter than the page, on a light one it has to be darker, and the same
 * od_shade() call cannot do both.
 *
 * @param string $hex Hex colour.
 * @return bool
 */
function od_is_light( $hex ) {
	$rgb = array_map( 'intval', explode( ',', od_rgb( $hex ) ) );

	if ( 3 !== count( $rgb ) ) {
		return false;
	}

	// Rec. 601 luma, which tracks perceived brightness closely enough here.
	$luma = ( $rgb[0] * 299 + $rgb[1] * 587 + $rgb[2] * 114 ) / 1000;

	return $luma > 150;
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
 * The colours actually in force: the chosen preset, with any colour the shop
 * owner set by hand taking precedence over it.
 *
 * Both the stylesheet and the body class read this, so the page can never
 * claim to be light while rendering dark.
 *
 * @return array
 */
function od_resolved_palette() {
	$preset   = od_option( 'palette_preset', 'aubergine' );
	$palettes = od_palettes();
	$p        = isset( $palettes[ $preset ] ) ? $palettes[ $preset ] : $palettes['aubergine'];

	// Explicit colour settings win over the preset.
	$out = array(
		'bg'         => od_option( 'color_bg', $p['bg'] ),
		'surface'    => od_option( 'color_surface', $p['surface'] ),
		'gold'       => od_option( 'color_gold', $p['gold'] ),
		'gold_light' => od_option( 'color_gold_light', $p['gold_light'] ),
		'maroon'     => od_option( 'color_maroon', $p['maroon'] ),
		'marigold'   => od_option( 'color_marigold', $p['marigold'] ),
		'text'       => od_option( 'color_text', $p['text'] ),
	);

	// When a non-default preset is picked, let it drive unless the user changed a colour.
	if ( 'aubergine' !== $preset && isset( $palettes[ $preset ] ) ) {
		$defaults = $palettes['aubergine'];

		foreach ( $out as $key => $value ) {
			if ( $value === $defaults[ $key ] ) {
				$out[ $key ] = $p[ $key ];
			}
		}
	}

	/*
	 * The in-between tones. A palette states its own, because deriving them
	 * by shifting every channel the same amount drains the warmth out —
	 * ivory's white card turned flat grey that way. They are only derived
	 * when the shop owner has picked a background or surface of their own,
	 * which the palette knows nothing about.
	 */
	$step = od_is_light( $out['bg'] ) ? -1 : 1;

	$out['bg_alt']    = ( $out['bg'] === $p['bg'] )
		? $p['bg_alt']
		: od_shade( $out['bg'], $step * 8 );
	$out['surface_2'] = ( $out['surface'] === $p['surface'] )
		? $p['surface_2']
		: od_shade( $out['surface'], $step * 12 );
	$out['surface_3'] = ( $out['surface'] === $p['surface'] )
		? $p['surface_3']
		: od_shade( $out['surface'], $step * 26 );

	return $out;
}

/**
 * Is the shop currently running on a light background?
 *
 * @return bool
 */
function od_palette_is_light() {
	$p = od_resolved_palette();

	return od_is_light( $p['bg'] );
}

/**
 * Build the inline stylesheet.
 *
 * @return string
 */
function od_dynamic_css() {
	$p = od_resolved_palette();

	$bg       = $p['bg'];
	$surface  = $p['surface'];
	$gold     = $p['gold'];
	$gold_lt  = $p['gold_light'];
	$maroon   = $p['maroon'];
	$marigold = $p['marigold'];
	$text     = $p['text'];

	$light     = od_is_light( $bg );
	$gold_deep = od_shade( $gold, -50 );
	$bg_alt    = $p['bg_alt'];
	$surface_2 = $p['surface_2'];
	$surface_3 = $p['surface_3'];

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

	/*
	 * Page background wash follows the accent colours. At the strength that
	 * gives a dark page its depth it would stain a light one, so it drops to
	 * roughly a fifth there.
	 */
	$w = $light ? 0.2 : 1.0;

	$css .= 'body{background-image:'
		. 'radial-gradient(1100px 620px at 82% -8%,rgba(' . od_rgb( $maroon ) . ',' . round( 0.34 * $w, 3 ) . '),transparent 62%),'
		. 'radial-gradient(900px 520px at 6% 4%,rgba(' . od_rgb( $surface_3 ) . ',' . round( 0.5 * $w, 3 ) . '),transparent 58%),'
		. 'radial-gradient(700px 700px at 50% 118%,rgba(' . od_rgb( $gold_deep ) . ',' . round( 0.16 * $w, 3 ) . '),transparent 60%);}';

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
