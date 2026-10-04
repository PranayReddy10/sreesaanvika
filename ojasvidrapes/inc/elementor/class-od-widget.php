<?php
/**
 * Base class for the theme's Elementor widgets.
 *
 * Each widget renders the same markup as its counterpart in
 * template-parts/home/, so a page rebuilt in Elementor is visually identical
 * to the Customizer-driven homepage.
 *
 * Widgets output the bare component — no .od-container, no .od-section — and
 * let Elementor's own section or container handle width and vertical spacing.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shared behaviour for every OJASVI widget.
 */
abstract class OD_Widget extends \Elementor\Widget_Base {

	/**
	 * Widget category.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'ojasvidrapes' );
	}

	/**
	 * Keywords for the widget search box.
	 *
	 * @return array
	 */
	public function get_keywords() {
		return array( 'ojasvi', 'drapes', 'saree', 'shop', 'india' );
	}

	/**
	 * The theme's stylesheets, so Elementor loads them with the widget.
	 *
	 * @return array
	 */
	public function get_style_depends() {
		$handles = array( 'od-main' );

		// od-shop is only registered when WooCommerce is active.
		if ( class_exists( 'WooCommerce' ) ) {
			$handles[] = 'od-shop';
		}

		return $handles;
	}

	/**
	 * The theme's scripts.
	 *
	 * @return array
	 */
	public function get_script_depends() {
		return array( 'od-main' );
	}

	/**
	 * Icon choices built from the theme's own SVG set.
	 *
	 * @return array
	 */
	protected function icon_choices() {
		$choices = array();

		foreach ( array_keys( od_icon_paths() ) as $key ) {
			$choices[ $key ] = ucwords( str_replace( '-', ' ', $key ) );
		}

		return $choices;
	}

	/**
	 * Product category choices, slug => name.
	 *
	 * @return array
	 */
	protected function category_choices() {
		$choices = array( '' => esc_html__( '— All categories —', 'ojasvidrapes' ) );

		if ( ! taxonomy_exists( 'product_cat' ) ) {
			return $choices;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'number'     => 100,
			)
		);

		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$choices[ $term->slug ] = $term->name;
			}
		}

		return $choices;
	}

	/**
	 * Turn an Elementor URL control value into safe anchor attributes.
	 *
	 * @param array $link URL control value.
	 * @return string
	 */
	protected function link_attrs( $link ) {
		if ( empty( $link['url'] ) ) {
			return '';
		}

		$out = ' href="' . esc_url( $link['url'] ) . '"';

		if ( ! empty( $link['is_external'] ) ) {
			$out .= ' target="_blank"';
		}

		$rel = array();

		if ( ! empty( $link['is_external'] ) ) {
			$rel[] = 'noopener';
			$rel[] = 'noreferrer';
		}

		if ( ! empty( $link['nofollow'] ) ) {
			$rel[] = 'nofollow';
		}

		if ( $rel ) {
			$out .= ' rel="' . esc_attr( implode( ' ', $rel ) ) . '"';
		}

		return $out;
	}

	/**
	 * A media control value reduced to a usable URL.
	 *
	 * @param array  $media Media control value.
	 * @param string $size  Image size for an attachment.
	 * @return string
	 */
	protected function image_url( $media, $size = 'od-hero' ) {
		if ( ! empty( $media['id'] ) ) {
			$url = wp_get_attachment_image_url( $media['id'], $size );

			if ( $url ) {
				return $url;
			}
		}

		return ! empty( $media['url'] ) ? $media['url'] : '';
	}

	/**
	 * Shared "heading" controls, so every section can carry the same
	 * eyebrow / title / description block.
	 *
	 * @param string $eyebrow Default eyebrow.
	 * @param string $title   Default title.
	 * @param string $text    Default description.
	 */
	protected function add_heading_controls( $eyebrow = '', $title = '', $text = '' ) {
		$this->add_control(
			'show_heading',
			array(
				'label'        => esc_html__( 'Show section heading', 'ojasvidrapes' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'eyebrow',
			array(
				'label'     => esc_html__( 'Eyebrow', 'ojasvidrapes' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => $eyebrow,
				'condition' => array( 'show_heading' => 'yes' ),
			)
		);

		$this->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'ojasvidrapes' ),
				'description' => esc_html__( 'Wrap a word in <em> tags to gild it.', 'ojasvidrapes' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => $title,
				'condition'   => array( 'show_heading' => 'yes' ),
			)
		);

		$this->add_control(
			'subtitle',
			array(
				'label'     => esc_html__( 'Description', 'ojasvidrapes' ),
				'type'      => \Elementor\Controls_Manager::TEXTAREA,
				'rows'      => 3,
				'default'   => $text,
				'condition' => array( 'show_heading' => 'yes' ),
			)
		);

		$this->add_control(
			'heading_align',
			array(
				'label'     => esc_html__( 'Heading alignment', 'ojasvidrapes' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'center',
				'options'   => array(
					'center' => esc_html__( 'Centre', 'ojasvidrapes' ),
					'left'   => esc_html__( 'Left', 'ojasvidrapes' ),
				),
				'condition' => array( 'show_heading' => 'yes' ),
			)
		);
	}

	/**
	 * Render the shared heading block from the widget settings.
	 *
	 * @param array $s Settings.
	 */
	protected function render_heading( $s ) {
		if ( empty( $s['show_heading'] ) || 'yes' !== $s['show_heading'] ) {
			return;
		}

		if ( empty( $s['title'] ) && empty( $s['eyebrow'] ) ) {
			return;
		}

		od_section_head(
			isset( $s['eyebrow'] ) ? $s['eyebrow'] : '',
			isset( $s['title'] ) ? $s['title'] : '',
			isset( $s['subtitle'] ) ? $s['subtitle'] : '',
			isset( $s['heading_align'] ) ? $s['heading_align'] : 'center'
		);
	}

	/**
	 * A short notice shown in the editor when a widget has nothing to show.
	 *
	 * @param string $message Explanation.
	 */
	protected function editor_notice( $message ) {
		if ( ! od_elementor_preview() ) {
			return;
		}

		printf(
			'<div style="padding:28px;border:1px dashed rgba(217,164,65,.5);border-radius:10px;color:#a8909c;text-align:center;font-family:system-ui,sans-serif">%s</div>',
			esc_html( $message )
		);
	}
}
