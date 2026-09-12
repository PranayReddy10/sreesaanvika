<?php
/**
 * Inline SVG icon set.
 *
 * Keeping icons inline avoids an icon-font request and lets them inherit
 * currentColor, which the dark palette relies on.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return an inline SVG icon.
 *
 * @param string $name  Icon key.
 * @param int    $size  Pixel size.
 * @param string $class Extra CSS class.
 * @return string
 */
function ss_icon( $name, $size = 20, $class = '' ) {
	$paths = ss_icon_paths();

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	$attrs = sprintf(
		'xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="ss-i ss-i--%2$s %3$s"',
		absint( $size ),
		esc_attr( $name ),
		esc_attr( $class )
	);

	// Some marks are solid rather than stroked.
	$solid = array( 'star-fill', 'instagram', 'facebook', 'youtube', 'whatsapp', 'pinterest', 'twitter', 'paisley', 'lotus' );

	if ( in_array( $name, $solid, true ) ) {
		$attrs = str_replace( 'fill="none" stroke="currentColor" stroke-width="1.6"', 'fill="currentColor"', $attrs );
	}

	return '<svg ' . $attrs . '>' . $paths[ $name ] . '</svg>';
}

/**
 * Echo an icon.
 *
 * @param string $name  Icon key.
 * @param int    $size  Pixel size.
 * @param string $class Extra CSS class.
 */
function ss_the_icon( $name, $size = 20, $class = '' ) {
	echo ss_icon( $name, $size, $class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Icon path data.
 *
 * @return array
 */
function ss_icon_paths() {
	static $paths = null;

	if ( null !== $paths ) {
		return $paths;
	}

	$paths = array(
		'search'      => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/>',
		'user'        => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
		'heart'       => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21.2l7.8-7.7 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>',
		'heart-fill'  => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21.2l7.8-7.7 1-1.1a5.5 5.5 0 0 0 0-7.8z" fill="currentColor"/>',
		'bag'         => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
		'cart'        => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/>',
		'compare'     => '<path d="M4 7h7M4 17h7"/><path d="M13 7h7M13 17h7"/><path d="M7 4 4 7l3 3"/><path d="M17 14l3 3-3 3"/>',
		'menu'        => '<path d="M3 6h18M3 12h18M3 18h18"/>',
		'close'       => '<path d="M18 6 6 18M6 6l12 12"/>',
		'chevron-down'=> '<path d="m6 9 6 6 6-6"/>',
		'chevron-up'  => '<path d="m18 15-6-6-6 6"/>',
		'chevron-left'=> '<path d="m15 18-6-6 6-6"/>',
		'chevron-right'=> '<path d="m9 18 6-6-6-6"/>',
		'arrow-right' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
		'arrow-left'  => '<path d="M19 12H5"/><path d="m12 19-7-7 7-7"/>',
		'arrow-up'    => '<path d="M12 19V5"/><path d="m5 12 7-7 7 7"/>',
		'plus'        => '<path d="M12 5v14M5 12h14"/>',
		'minus'       => '<path d="M5 12h14"/>',
		'check'       => '<path d="m20 6-11 11-5-5"/>',
		'check-circle'=> '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/>',
		'info'        => '<circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/>',
		'alert'       => '<path d="M12 3 2 20h20L12 3z"/><path d="M12 10v4M12 17h.01"/>',
		'eye'         => '<path d="M1.5 12S5 5.5 12 5.5 22.5 12 22.5 12 19 18.5 12 18.5 1.5 12 1.5 12z"/><circle cx="12" cy="12" r="3"/>',
		'eye-off'     => '<path d="M9.9 5.7A9.6 9.6 0 0 1 12 5.5c7 0 10.5 6.5 10.5 6.5a17 17 0 0 1-3.2 4"/><path d="M6.3 7.8A16.6 16.6 0 0 0 1.5 12S5 18.5 12 18.5a9.8 9.8 0 0 0 4.2-.9"/><path d="M3 3l18 18"/><path d="M9.9 10.1a3 3 0 0 0 4.1 4.2"/>',
		'zoom'        => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5M11 8v6M8 11h6"/>',
		'expand'      => '<path d="M8 3H3v5M16 3h5v5M21 16v5h-5M3 16v5h5"/>',
		'star'        => '<path d="m12 2.8 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3-5.8 3 1.1-6.5L2.6 9.6l6.5-.9z"/>',
		'star-fill'   => '<path d="m12 2.8 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3-5.8 3 1.1-6.5L2.6 9.6l6.5-.9z"/>',
		'truck'       => '<path d="M1 3h13v13H1z"/><path d="M14 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2"/><circle cx="17.5" cy="18.5" r="2"/>',
		'refresh'     => '<path d="M21 12a9 9 0 1 1-2.6-6.4"/><path d="M21 3v6h-6"/>',
		'shield'      => '<path d="M12 2.5 4 6v6c0 5 3.4 8.4 8 9.5 4.6-1.1 8-4.5 8-9.5V6z"/><path d="m9 12 2 2 4-4"/>',
		'headset'     => '<path d="M4 13a8 8 0 0 1 16 0"/><path d="M4 13v3a2 2 0 0 0 2 2h1v-6H6a2 2 0 0 0-2 2z"/><path d="M20 13v3a2 2 0 0 1-2 2h-1v-6h1a2 2 0 0 1 2 2z"/><path d="M17 19a3 3 0 0 1-3 2h-2"/>',
		'gift'        => '<path d="M20 12v9H4v-9"/><path d="M2 7h20v5H2z"/><path d="M12 21V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>',
		'tag'         => '<path d="M20.6 13.4 12 22l-9-9V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><path d="M7.5 7.5h.01"/>',
		'sparkle'     => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4"/><path d="m5.6 5.6 2.8 2.8M15.6 15.6l2.8 2.8M18.4 5.6l-2.8 2.8M8.4 15.6l-2.8 2.8"/>',
		'ruler'       => '<path d="m3 15 6-6 9 9-6 6z" /><path d="m14 4 6 6-3 3-6-6z"/><path d="m7 13 1.5 1.5M10 10l1.5 1.5M13 7l1.5 1.5"/>',
		'clock'       => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'pin'         => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 1 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
		'mail'        => '<path d="M3 5h18v14H3z"/><path d="m3 6 9 7 9-7"/>',
		'phone'       => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
		'share'       => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/>',
		'link'        => '<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7L12.2 19"/>',
		'grid'        => '<path d="M3 3h7v7H3zM14 3h7v7h-7zM14 14h7v7h-7zM3 14h7v7H3z"/>',
		'list'        => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
		'filter'      => '<path d="M3 4h18l-7 8v7l-4 2v-9z"/>',
		'lock'        => '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
		'trash'       => '<path d="M3 6h18M8 6V4h8v2M6 6l1 15h10l1-15"/>',
		'edit'        => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
		'flame'       => '<path d="M12 22c4 0 7-2.8 7-7 0-4-3-6-3-10 0 0-3 2-3 5 0-2-1-4-3-5 0 3-5 4-5 10 0 4.2 3 7 7 7z"/>',
		'leaf'        => '<path d="M11 20A7 7 0 0 1 4 13c0-6 8-10 16-10 0 8-4 16-10 16z"/><path d="M4 21c2-4 6-8 11-10"/>',
		'globe'       => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18z"/>',
		'play'        => '<path d="M6 4l14 8-14 8z"/>',
		'quote'       => '<path d="M9 7H5a2 2 0 0 0-2 2v3h4v5H3"/><path d="M19 7h-4a2 2 0 0 0-2 2v3h4v5h-4"/>',
		'palette'     => '<path d="M12 3a9 9 0 0 0 0 18h2a2 2 0 0 0 0-4 2 2 0 0 1 2-2h2a3 3 0 0 0 3-3 9 9 0 0 0-9-9z"/><circle cx="7.5" cy="11.5" r="1"/><circle cx="10.5" cy="7.5" r="1"/><circle cx="15" cy="8.5" r="1"/>',
		'scissors'    => '<circle cx="6" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M20 4 8.5 15.5M14.5 14.5 20 20M8.5 8.5 10 10"/>',
		'instagram'   => '<path d="M12 2.2c3.2 0 3.6 0 4.9.1 1.2.1 1.8.2 2.2.4.6.2 1 .5 1.4 1 .5.4.8.8 1 1.4.2.4.3 1 .4 2.2.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c-.1 1.2-.2 1.8-.4 2.2-.2.6-.5 1-1 1.4-.4.5-.8.8-1.4 1-.4.2-1 .3-2.2.4-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2-.1-1.8-.2-2.2-.4-.6-.2-1-.5-1.4-1-.5-.4-.8-.8-1-1.4-.2-.4-.3-1-.4-2.2C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.9c.1-1.2.2-1.8.4-2.2.2-.6.5-1 1-1.4.4-.5.8-.8 1.4-1 .4-.2 1-.3 2.2-.4 1.3-.1 1.7-.1 4.7-.1zm0 3.1A6.7 6.7 0 1 0 18.7 12 6.7 6.7 0 0 0 12 5.3zm0 11a4.3 4.3 0 1 1 4.3-4.3 4.3 4.3 0 0 1-4.3 4.3zm6.9-11.3a1.6 1.6 0 1 1-1.6-1.6 1.6 1.6 0 0 1 1.6 1.6z"/>',
		'facebook'    => '<path d="M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.5 2.9h-2.3v7A10 10 0 0 0 22 12z"/>',
		'youtube'     => '<path d="M23 12s0-3.6-.5-5.3a2.7 2.7 0 0 0-1.9-1.9C18.9 4.3 12 4.3 12 4.3s-6.9 0-8.6.5a2.7 2.7 0 0 0-1.9 1.9C1 8.4 1 12 1 12s0 3.6.5 5.3a2.7 2.7 0 0 0 1.9 1.9c1.7.5 8.6.5 8.6.5s6.9 0 8.6-.5a2.7 2.7 0 0 0 1.9-1.9C23 15.6 23 12 23 12zM9.8 15.3V8.7l5.7 3.3z"/>',
		'whatsapp'    => '<path d="M17.5 14.4c-.3-.2-1.8-.9-2-1-.3-.1-.5-.2-.7.1s-.8 1-.9 1.2c-.2.2-.3.2-.6.1a8.2 8.2 0 0 1-2.4-1.5 9 9 0 0 1-1.7-2.1c-.2-.3 0-.5.1-.6l.5-.6a2 2 0 0 0 .3-.5.6.6 0 0 0 0-.5c0-.2-.7-1.7-1-2.3s-.5-.5-.7-.5h-.6a1.2 1.2 0 0 0-.8.4A3.4 3.4 0 0 0 6 8.7a5.9 5.9 0 0 0 1.3 3.2 13.5 13.5 0 0 0 5.2 4.6 17 17 0 0 0 1.7.6 4.2 4.2 0 0 0 1.9.1 3.1 3.1 0 0 0 2-1.4 2.5 2.5 0 0 0 .2-1.4c-.1-.1-.3-.2-.6-.3zM12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2zm0 18.3a8.3 8.3 0 0 1-4.2-1.2l-.3-.2-3.1.8.8-3-.2-.3A8.3 8.3 0 1 1 12 20.3z"/>',
		'pinterest'   => '<path d="M12 2a10 10 0 0 0-3.6 19.3 9.6 9.6 0 0 1 .2-2.9l1.2-4.9a3.6 3.6 0 0 1-.3-1.5c0-1.4.8-2.4 1.8-2.4a1.3 1.3 0 0 1 1.3 1.4 20.8 20.8 0 0 1-.8 3.4 1.5 1.5 0 0 0 1.5 1.9c1.8 0 3.2-1.9 3.2-4.7a4 4 0 0 0-4.3-4.1 4.5 4.5 0 0 0-4.6 4.5 4 4 0 0 0 .8 2.4.3.3 0 0 1 .1.3l-.3 1.1c0 .2-.1.2-.3.2-1.3-.6-2-2.4-2-3.9 0-3.2 2.3-6.1 6.7-6.1a5.9 5.9 0 0 1 6.2 5.8c0 3.5-2.2 6.3-5.3 6.3a2.7 2.7 0 0 1-2.3-1.2l-.6 2.4a11 11 0 0 1-1.3 2.7A10 10 0 1 0 12 2z"/>',
		'twitter'     => '<path d="M18.2 2.2h3.3l-7.2 8.3 8.5 11.3h-6.7l-5.2-6.9-6 6.9H1.6l7.7-8.8L1.2 2.2H8l4.7 6.3zm-1.2 17.7h1.9L7.1 4.1H5.1z"/>',
		'paisley'     => '<path d="M12 2c4 3 6 6 6 9.5 0 3.6-2.6 6.5-6 6.5a4.6 4.6 0 0 1-4.7-4.6c0-2.4 1.8-4.2 4-4.2a2.8 2.8 0 0 1 2.9 2.8 1.9 1.9 0 0 1-1.9 1.9 1.3 1.3 0 0 1-1.3-1.3.9.9 0 0 1 .9-.9.6.6 0 0 1 .6.6.4.4 0 0 1-.4.4.3.3 0 0 1-.3-.3" opacity=".95"/><path d="M12 22c-4-1.4-6.8-4.4-6.8-8.4C5.2 8.4 8.4 4.4 12 2" fill="none" stroke="currentColor" stroke-width="1.2" opacity=".7"/>',
		'lotus'       => '<path d="M12 4c1.6 1.6 2.4 3.4 2.4 5.4 0 1-.2 2-.6 2.9 1.4-1.1 2.4-2.6 2.9-4.3 1.4 1.9 1.8 3.9 1.2 5.8 1.3-.6 2.4-1.6 3.1-3-.2 4.6-3.9 8.2-9 8.2s-8.8-3.6-9-8.2c.7 1.4 1.8 2.4 3.1 3-.6-1.9-.2-3.9 1.2-5.8.5 1.7 1.5 3.2 2.9 4.3-.4-.9-.6-1.9-.6-2.9C9.6 7.4 10.4 5.6 12 4z"/>',
	);

	return $paths;
}

/**
 * Render a row of stars for a rating value.
 *
 * @param float $rating Rating 0–5.
 * @param int   $count  Optional review count.
 * @return string
 */
function ss_stars( $rating, $count = 0 ) {
	$rating = max( 0, min( 5, (float) $rating ) );
	$full   = (int) floor( $rating );

	$out = '<span class="ss-rating" role="img" aria-label="' . esc_attr( sprintf( /* translators: %s: rating out of five */ __( 'Rated %s out of 5', 'sreesaanvika' ), number_format_i18n( $rating, 1 ) ) ) . '">';

	for ( $i = 1; $i <= 5; $i++ ) {
		$out .= ss_icon( $i <= $full ? 'star-fill' : 'star', 15 );
	}

	if ( $count ) {
		$out .= '<span class="ss-rating__count">(' . esc_html( number_format_i18n( $count ) ) . ')</span>';
	}

	$out .= '</span>';

	return $out;
}
