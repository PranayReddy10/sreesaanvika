<?php
/**
 * Navigation walkers.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

/**
 * Desktop walker — adds a caret to parents so the dropdown is discoverable.
 */
class SS_Nav_Walker extends Walker_Nav_Menu {

	/**
	 * Start an element.
	 *
	 * @param string   $output Output buffer.
	 * @param WP_Post  $item   Menu item.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   Args.
	 * @param int      $id     Item id.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes   = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;

		$class_names = implode( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

		$output .= '<li id="menu-item-' . absint( $item->ID ) . '"' . $class_names . '>';

		$atts = array(
			'title'  => ! empty( $item->attr_title ) ? $item->attr_title : '',
			'target' => ! empty( $item->target ) ? $item->target : '',
			'rel'    => ! empty( $item->xfn ) ? $item->xfn : '',
			'href'   => ! empty( $item->url ) ? $item->url : '',
		);

		$atts    = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );
		$att_str = '';

		foreach ( $atts as $key => $value ) {
			if ( '' === $value || false === $value ) {
				continue;
			}
			$value    = ( 'href' === $key ) ? esc_url( $value ) : esc_attr( $value );
			$att_str .= ' ' . $key . '="' . $value . '"';
		}

		$title = apply_filters( 'the_title', $item->title, $item->ID );
		$title = apply_filters( 'nav_menu_item_title', $title, $item, $args, $depth );

		$caret = ( in_array( 'menu-item-has-children', $classes, true ) && 0 === $depth )
			? '<span class="ss-caret" aria-hidden="true"></span>'
			: '';

		$output .= '<a' . $att_str . '>' . esc_html( $title ) . $caret . '</a>';
	}
}

/**
 * Mobile drawer walker — adds an accordion toggle button next to parents.
 */
class SS_Drawer_Walker extends Walker_Nav_Menu {

	/**
	 * Start an element.
	 *
	 * @param string   $output Output buffer.
	 * @param WP_Post  $item   Menu item.
	 * @param int      $depth  Depth.
	 * @param stdClass $args   Args.
	 * @param int      $id     Item id.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$classes     = empty( $item->classes ) ? array() : (array) $item->classes;
		$has_kids    = in_array( 'menu-item-has-children', $classes, true );
		$class_names = implode( ' ', array_filter( $classes ) );

		$output .= '<li class="' . esc_attr( $class_names ) . '">';
		$output .= '<a href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';

		if ( $has_kids ) {
			$output .= '<button type="button" class="ss-drawer__toggle" aria-expanded="false" aria-label="'
				. esc_attr( sprintf( /* translators: %s: menu item name */ __( 'Toggle %s submenu', 'sreesaanvika' ), $item->title ) )
				. '">' . ss_icon( 'chevron-down', 16 ) . '</button>';
		}
	}
}

/**
 * Fallback menu when no menu is assigned yet — keeps a fresh install usable.
 *
 * @param array $args Menu args.
 */
function ss_menu_fallback( $args = array() ) {
	$items = array(
		home_url( '/' ) => __( 'Home', 'sreesaanvika' ),
	);

	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$items[ wc_get_page_permalink( 'shop' ) ] = __( 'Shop', 'sreesaanvika' );
	}

	foreach ( array( 'sarees' => __( 'Sarees', 'sreesaanvika' ), 'jewellery' => __( 'Jewellery', 'sreesaanvika' ), 'dresses' => __( 'Dresses', 'sreesaanvika' ) ) as $slug => $label ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$items[ get_term_link( $term ) ] = $label;
		}
	}

	$items[ ss_page_url( 'lookbook' ) ] = __( 'Lookbook', 'sreesaanvika' );
	$items[ ss_page_url( 'contact' ) ]  = __( 'Contact', 'sreesaanvika' );

	$is_drawer = ! empty( $args['walker'] ) && $args['walker'] instanceof SS_Drawer_Walker;
	$class     = $is_drawer ? 'ss-drawer__menu' : 'ss-nav__list';

	echo '<ul class="' . esc_attr( $class ) . '">';

	foreach ( $items as $url => $label ) {
		echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
	}

	echo '</ul>';
}
