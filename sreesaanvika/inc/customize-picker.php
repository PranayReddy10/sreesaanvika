<?php
/**
 * A Customizer control for choosing things, in an order.
 *
 * Typing category slugs into a textarea works, but nobody should have to know
 * their own slugs. This searches by name, shows what is chosen, and lets it be
 * dragged into the order it will appear in. The stored value stays a plain
 * comma separated list, so nothing that reads these settings has to change.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WP_Customize_Control' ) ) :

	/**
	 * Search-and-order picker.
	 */
	class SS_Customize_Picker extends WP_Customize_Control {

		/**
		 * Control type.
		 *
		 * @var string
		 */
		public $type = 'ss-picker';

		/**
		 * What is being picked: product_cat or product.
		 *
		 * @var string
		 */
		public $entity = 'product_cat';

		/**
		 * Draw the control.
		 */
		public function render_content() {
			$value = trim( (string) $this->value() );
			?>
			<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>

			<?php if ( $this->description ) : ?>
				<span class="description customize-control-description"><?php echo esc_html( $this->description ); ?></span>
			<?php endif; ?>

			<div class="ss-picker" data-entity="<?php echo esc_attr( $this->entity ); ?>">
				<ul class="ss-picker__chosen" data-chosen></ul>

				<p class="ss-picker__empty" data-empty>
					<?php
					'product' === $this->entity
						? esc_html_e( 'Nothing chosen — the newest products are used.', 'sreesaanvika' )
						: esc_html_e( 'Nothing chosen — the busiest categories are used.', 'sreesaanvika' );
					?>
				</p>

				<input type="search" class="ss-picker__search" data-search
					placeholder="<?php echo 'product' === $this->entity
						? esc_attr__( 'Search products…', 'sreesaanvika' )
						: esc_attr__( 'Search categories…', 'sreesaanvika' ); ?>"
					autocomplete="off" />

				<ul class="ss-picker__results" data-results hidden></ul>

				<input type="hidden" value="<?php echo esc_attr( $value ); ?>" data-value <?php $this->link(); ?> />
			</div>
			<?php
		}
	}

endif;

/**
 * Search categories or products for the picker.
 */
function ss_picker_search() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_send_json_error( array(), 403 );
	}

	check_ajax_referer( 'ss_picker', 'nonce' );

	$entity = isset( $_GET['entity'] ) ? sanitize_key( wp_unslash( $_GET['entity'] ) ) : 'product_cat';
	$term   = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$want   = isset( $_GET['have'] ) ? sanitize_text_field( wp_unslash( $_GET['have'] ) ) : '';
	$out    = array();

	if ( 'product' === $entity ) {
		$out = ss_picker_products( $term, $want );
	} else {
		$out = ss_picker_terms( $term, $want );
	}

	wp_send_json_success( $out );
}
add_action( 'wp_ajax_ss_picker_search', 'ss_picker_search' );

/**
 * Category results, or the labels for an existing selection.
 *
 * @param string $search Search text.
 * @param string $have   Comma separated slugs already chosen, when relabelling.
 * @return array
 */
function ss_picker_terms( $search, $have ) {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}

	$args = array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'number'     => 30,
		'orderby'    => 'count',
		'order'      => 'DESC',
	);

	if ( $have ) {
		$args['slug']   = array_map( 'sanitize_title', array_filter( array_map( 'trim', explode( ',', $have ) ) ) );
		$args['number'] = 60;
	} elseif ( $search ) {
		$args['search'] = $search;
	}

	$terms = get_terms( $args );

	if ( ! $terms || is_wp_error( $terms ) ) {
		return array();
	}

	$out = array();

	foreach ( $terms as $term ) {
		$out[] = array(
			'value' => $term->slug,
			'label' => $term->name,
			/* translators: %s: number of products */
			'sub'   => sprintf( _n( '%s product', '%s products', $term->count, 'sreesaanvika' ), number_format_i18n( $term->count ) ),
		);
	}

	return $out;
}

/**
 * Product results, or the labels for an existing selection.
 *
 * @param string $search Search text.
 * @param string $have   Comma separated ids already chosen, when relabelling.
 * @return array
 */
function ss_picker_products( $search, $have ) {
	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 30,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	);

	if ( $have ) {
		$args['post__in']       = array_filter( array_map( 'absint', explode( ',', $have ) ) );
		$args['orderby']        = 'post__in';
		$args['posts_per_page'] = 60;
	} elseif ( $search ) {
		$args['s'] = $search;
	}

	if ( ! empty( $args['post__in'] ) || empty( $have ) ) {
		$query = new WP_Query( $args );
	} else {
		return array();
	}

	$out = array();

	foreach ( $query->posts as $post ) {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $post ) : null;

		$out[] = array(
			'value' => (string) $post->ID,
			'label' => get_the_title( $post ),
			'sub'   => $product ? wp_strip_all_tags( $product->get_price_html() ) : '',
		);
	}

	wp_reset_postdata();

	return $out;
}

/**
 * The picker's own script and styling, in the Customizer only.
 */
function ss_picker_assets() {
	wp_enqueue_script(
		'ss-picker',
		SS_URI . '/assets/js/customize-picker.js',
		array( 'jquery', 'customize-controls', 'jquery-ui-sortable' ),
		SS_VERSION,
		true
	);

	wp_localize_script(
		'ss-picker',
		'ssPicker',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'ss_picker' ),
			'i18n'    => array(
				'remove'   => __( 'Remove', 'sreesaanvika' ),
				'up'       => __( 'Move up', 'sreesaanvika' ),
				'down'     => __( 'Move down', 'sreesaanvika' ),
				'none'     => __( 'Nothing found.', 'sreesaanvika' ),
				'searching' => __( 'Searching…', 'sreesaanvika' ),
			),
		)
	);

	wp_add_inline_style( 'customize-controls', ss_picker_css() );
}
add_action( 'customize_controls_enqueue_scripts', 'ss_picker_assets' );

/**
 * Styling for the picker.
 *
 * @return string
 */
function ss_picker_css() {
	return '
	.ss-picker { margin-top: 6px; }
	.ss-picker__chosen { margin: 0 0 8px; padding: 0; list-style: none; }
	.ss-picker__chosen:empty { display: none; }
	.ss-picker__chosen li {
		display: flex; align-items: center; gap: 6px;
		padding: 6px 8px; margin-bottom: 4px;
		background: #fff; border: 1px solid #dcdcde; border-radius: 3px;
		font-size: 12px; cursor: grab;
	}
	.ss-picker__chosen li.is-dragging { opacity: .6; }
	.ss-picker__grip { color: #a7aaad; cursor: grab; line-height: 1; }
	.ss-picker__label { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
	.ss-picker__n { color: #787c82; font-size: 11px; }
	.ss-picker__btn {
		border: 0; background: none; cursor: pointer; padding: 2px 4px;
		color: #787c82; line-height: 1; font-size: 13px;
	}
	.ss-picker__btn:hover { color: #135e96; }
	.ss-picker__btn--x:hover { color: #b32d2e; }
	.ss-picker__empty { color: #787c82; font-size: 12px; font-style: italic; margin: 0 0 8px; }
	.ss-picker__search { width: 100%; }
	.ss-picker__results {
		margin: 4px 0 0; padding: 0; list-style: none;
		max-height: 190px; overflow-y: auto;
		border: 1px solid #dcdcde; border-radius: 3px; background: #fff;
	}
	.ss-picker__results li {
		margin: 0; padding: 7px 9px; cursor: pointer; font-size: 12px;
		border-bottom: 1px solid #f0f0f1;
	}
	.ss-picker__results li:last-child { border-bottom: 0; }
	.ss-picker__results li:hover { background: #f0f6fc; }
	.ss-picker__results li.is-in { opacity: .45; cursor: default; }
	.ss-picker__results li span { display: block; color: #787c82; font-size: 11px; }
	';
}
