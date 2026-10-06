<?php
/**
 * Catalogue widgets: product grid, category rail, category mosaic, lookbook.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Product grid — the same card as the shop, with a choice of source.
 */
class OD_Widget_Products extends OD_Widget {

	public function get_name() { return 'od-products'; }
	public function get_title() { return esc_html__( 'Product Grid', 'ojasvidrapes' ); }
	public function get_icon() { return 'eicon-products'; }

	protected function register_controls() {
		$this->start_controls_section( 'heading_section', array( 'label' => esc_html__( 'Heading', 'ojasvidrapes' ) ) );

		$this->add_heading_controls(
			esc_html__( 'Fresh off the loom', 'ojasvidrapes' ),
			__( 'New <em>Arrivals</em>', 'ojasvidrapes' ),
			esc_html__( 'The newest weaves, added this week.', 'ojasvidrapes' )
		);

		$this->end_controls_section();

		$this->start_controls_section( 'query_section', array( 'label' => esc_html__( 'Products', 'ojasvidrapes' ) ) );

		$this->add_control(
			'source',
			array(
				'label'   => esc_html__( 'Show', 'ojasvidrapes' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'latest',
				'options' => array(
					'latest'   => esc_html__( 'Newest first', 'ojasvidrapes' ),
					'best'     => esc_html__( 'Best sellers', 'ojasvidrapes' ),
					'sale'     => esc_html__( 'On sale', 'ojasvidrapes' ),
					'featured' => esc_html__( 'Featured', 'ojasvidrapes' ),
					'rated'    => esc_html__( 'Top rated', 'ojasvidrapes' ),
					'random'   => esc_html__( 'Random', 'ojasvidrapes' ),
				),
			)
		);

		$this->add_control(
			'category',
			array(
				'label'   => esc_html__( 'Category', 'ojasvidrapes' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => $this->category_choices(),
			)
		);

		$this->add_control(
			'count',
			array(
				'label'   => esc_html__( 'How many', 'ojasvidrapes' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 24,
				'default' => 8,
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'   => esc_html__( 'Columns', 'ojasvidrapes' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '4',
				'options' => array( '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
			)
		);

		$this->add_control(
			'show_loadmore',
			array(
				'label'        => esc_html__( 'Show a "Load more" button', 'ojasvidrapes' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'description'  => esc_html__( 'Appends the next products in place instead of sending shoppers to another page.', 'ojasvidrapes' ),
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'       => esc_html__( 'Button below the grid', 'ojasvidrapes' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'description' => esc_html__( 'Leave empty to hide it.', 'ojasvidrapes' ),
			)
		);

		$this->add_control(
			'button_link',
			array(
				'label'     => esc_html__( 'Button link', 'ojasvidrapes' ),
				'type'      => Controls_Manager::URL,
				'condition' => array( 'button_text!' => '' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Build the WP_Query args for the chosen source.
	 *
	 * @param array $s Settings.
	 * @return array
	 */
	protected function query_args( $s ) {
		$args = array( 'posts_per_page' => absint( $s['count'] ) );

		switch ( $s['source'] ) {
			case 'best':
				$args['meta_key'] = 'total_sales'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'DESC';
				break;

			case 'sale':
				$ids              = function_exists( 'wc_get_product_ids_on_sale' ) ? wc_get_product_ids_on_sale() : array();
				$args['post__in'] = $ids ? $ids : array( 0 );
				$args['orderby']  = 'date';
				break;

			case 'featured':
				$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array(
						'taxonomy' => 'product_visibility',
						'field'    => 'name',
						'terms'    => 'featured',
					),
				);
				break;

			case 'rated':
				$args['meta_key'] = '_wc_average_rating'; // phpcs:ignore WordPress.DB.SlowDBQuery
				$args['orderby']  = 'meta_value_num';
				$args['order']    = 'DESC';
				break;

			case 'random':
				$args['orderby'] = 'rand';
				break;

			default:
				$args['orderby'] = 'date';
				$args['order']   = 'DESC';
		}

		if ( ! empty( $s['category'] ) ) {
			$cat_query = od_cat_query( $s['category'] );

			$args['tax_query'] = isset( $args['tax_query'] ) // phpcs:ignore WordPress.DB.SlowDBQuery
				? array_merge( $args['tax_query'], $cat_query['tax_query'] )
				: $cat_query['tax_query'];
		}

		return $args;
	}

	protected function render() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->editor_notice( esc_html__( 'This widget needs WooCommerce.', 'ojasvidrapes' ) );
			return;
		}

		$s = $this->get_settings_for_display();

		$more = array();

		if ( ! empty( $s['show_loadmore'] ) && 'yes' === $s['show_loadmore'] ) {
			$more = array(
				'source'   => $s['source'],
				'category' => $s['category'],
			);
		}

		ob_start();
		$found = od_product_loop( $this->query_args( $s ), absint( $s['columns'] ), $more );
		$loop  = ob_get_clean();

		if ( ! $found ) {
			$this->editor_notice( esc_html__( 'No products match this selection yet.', 'ojasvidrapes' ) );
			return;
		}

		$this->render_heading( $s );

		echo $loop; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( ! empty( $s['button_text'] ) ) {
			echo '<div class="od-text-center" style="margin-top:36px">';
			printf(
				'<a class="od-btn od-btn--ghost od-btn--lg"%s>%s</a>',
				$this->link_attrs( $s['button_link'] ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $s['button_text'] )
			);
			echo '</div>';
		}
	}
}

/**
 * Round category rail.
 */
class OD_Widget_Category_Rail extends OD_Widget {

	public function get_name() { return 'od-category-rail'; }
	public function get_title() { return esc_html__( 'Category Rail', 'ojasvidrapes' ); }
	public function get_icon() { return 'eicon-post-slider'; }

	protected function register_controls() {
		$this->start_controls_section( 'heading_section', array( 'label' => esc_html__( 'Heading', 'ojasvidrapes' ) ) );

		$this->add_heading_controls(
			esc_html__( 'Shop by category', 'ojasvidrapes' ),
			__( 'Find Your <em>Drape</em>', 'ojasvidrapes' ),
			esc_html__( 'From nine-yard Kanjivarams to everyday cottons and festive jewellery.', 'ojasvidrapes' )
		);

		$this->end_controls_section();

		$this->start_controls_section( 'query_section', array( 'label' => esc_html__( 'Categories', 'ojasvidrapes' ) ) );

		$this->add_control(
			'count',
			array(
				'label'   => esc_html__( 'How many', 'ojasvidrapes' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 2,
				'max'     => 20,
				'default' => 10,
			)
		);

		$this->add_control(
			'top_level',
			array(
				'label'        => esc_html__( 'Top-level categories only', 'ojasvidrapes' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			$this->editor_notice( esc_html__( 'This widget needs WooCommerce.', 'ojasvidrapes' ) );
			return;
		}

		$s = $this->get_settings_for_display();

		$args = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'number'     => absint( $s['count'] ),
			'orderby'    => 'count',
			'order'      => 'DESC',
			'exclude'    => array( get_option( 'default_product_cat' ) ),
		);

		if ( 'yes' === $s['top_level'] ) {
			$args['parent'] = 0;
		}

		$terms = get_terms( $args );

		if ( ! $terms || is_wp_error( $terms ) ) {
			$this->editor_notice( esc_html__( 'Add some product categories to fill this rail.', 'ojasvidrapes' ) );
			return;
		}

		$this->render_heading( $s );
		?>
		<div class="od-catrail">
			<?php
			foreach ( $terms as $term ) :
				$thumb_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
				$img      = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'od-category' ) : '';
				?>
				<a class="od-catrail__item" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
					<div class="od-catrail__ring">
						<?php if ( $img ) : ?>
							<img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy" width="160" height="160" />
						<?php else : ?>
							<span aria-hidden="true"><?php echo esc_html( mb_substr( $term->name, 0, 1 ) ); ?></span>
						<?php endif; ?>
					</div>
					<span class="od-catrail__name"><?php echo esc_html( $term->name ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
	}
}

/**
 * Category mosaic.
 */
class OD_Widget_Category_Mosaic extends OD_Widget {

	public function get_name() { return 'od-category-mosaic'; }
	public function get_title() { return esc_html__( 'Category Mosaic', 'ojasvidrapes' ); }
	public function get_icon() { return 'eicon-gallery-grid'; }

	protected function register_controls() {
		$this->start_controls_section( 'heading_section', array( 'label' => esc_html__( 'Heading', 'ojasvidrapes' ) ) );

		$this->add_heading_controls(
			esc_html__( 'The collection', 'ojasvidrapes' ),
			__( 'Every <em>Drape</em> We Have', 'ojasvidrapes' ),
			''
		);

		$this->end_controls_section();

		$this->start_controls_section( 'query_section', array( 'label' => esc_html__( 'Categories', 'ojasvidrapes' ) ) );

		$this->add_control(
			'count',
			array(
				'label'       => esc_html__( 'How many tiles', 'ojasvidrapes' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 6,
				'default'     => 5,
				'description' => esc_html__( 'The tile pattern adapts so every row fills exactly.', 'ojasvidrapes' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		if ( ! function_exists( 'wc_get_product' ) ) {
			$this->editor_notice( esc_html__( 'This widget needs WooCommerce.', 'ojasvidrapes' ) );
			return;
		}

		$s = $this->get_settings_for_display();

		/*
		 * Tiles of whatever the shop browses by — and pieces when it browses
		 * by nothing. A page built with this widget before the shop dropped
		 * its categories would otherwise go on showing them for ever, since
		 * Elementor saves a widget's settings into the page and nothing in
		 * the theme can reach in and change them.
		 */
		$tiles = $this->mosaic_tiles( absint( $s['count'] ) );

		if ( ! $tiles ) {
			$this->editor_notice( esc_html__( 'Add some products to fill this mosaic.', 'ojasvidrapes' ) );
			return;
		}

		// Same patterns as template-parts/home/cats.php.
		$patterns = array(
			1 => array( 'w6 od-cat--h2' ),
			2 => array( 'w3', 'w3' ),
			3 => array( 'w4 od-cat--h2', 'w2', 'w2' ),
			4 => array( 'w3', 'w3', 'w3', 'w3' ),
			5 => array( 'w4 od-cat--h2', 'w2', 'w2', 'w3', 'w3' ),
			6 => array( 'w4 od-cat--h2', 'w2', 'w2', 'w2', 'w2', 'w2' ),
		);

		$total = count( $tiles );
		$sizes = isset( $patterns[ $total ] ) ? $patterns[ $total ] : $patterns[6];

		$this->render_heading( $s );
		?>
		<div class="od-cats">
			<?php
			foreach ( $tiles as $n => $tile ) :
				$size = isset( $sizes[ $n ] ) ? $sizes[ $n ] : 'w2';
				?>
				<article class="od-cat od-cat--<?php echo esc_attr( $size ); ?>">
					<div class="od-cat__img"<?php echo $tile['img'] ? od_bg_style( $tile['img'] ) : ''; ?>></div>

					<div class="od-cat__body">
						<?php if ( $tile['meta'] ) : ?>
							<span class="od-cat__count"><?php echo esc_html( $tile['meta'] ); ?></span>
						<?php endif; ?>
						<h3 class="od-cat__title"><?php echo esc_html( $tile['title'] ); ?></h3>
						<span class="od-cat__link"><?php echo esc_html( $tile['cta'] ); ?><?php od_the_icon( 'arrow-right', 15 ); ?></span>
					</div>

					<a class="od-cat__stretch" href="<?php echo esc_url( $tile['url'] ); ?>">
						<span class="screen-reader-text"><?php echo esc_html( $tile['title'] ); ?></span>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * The tiles: browse terms where the shop has them, pieces where it does not.
	 *
	 * @param int $count How many tiles.
	 * @return array
	 */
	protected function mosaic_tiles( $count ) {
		$count = max( 1, $count );
		$out   = array();

		if ( od_has_browse() ) {
			foreach ( od_browse_terms( '', $count, true ) as $term ) {
				$link = get_term_link( $term );

				if ( is_wp_error( $link ) ) {
					continue;
				}

				$thumb = get_term_meta( $term->term_id, 'thumbnail_id', true );

				$out[] = array(
					'title' => $term->name,
					/* translators: %s: number of products */
					'meta'  => sprintf( _n( '%s piece', '%s pieces', $term->count, 'ojasvidrapes' ), number_format_i18n( $term->count ) ),
					'img'   => $thumb ? wp_get_attachment_image_url( $thumb, 'od-hero' ) : '',
					'url'   => $link,
					'cta'   => __( 'Explore', 'ojasvidrapes' ),
				);
			}

			return $out;
		}

		foreach ( od_picked_products( '', $count ) as $product ) {
			$out[] = array(
				'title' => $product->get_name(),
				'meta'  => wp_strip_all_tags( $product->get_price_html() ),
				'img'   => od_product_image_url( $product, 'od-hero' ),
				'url'   => $product->get_permalink(),
				'cta'   => __( 'View', 'ojasvidrapes' ),
			);
		}

		return $out;
	}
}

/**
 * Lookbook strip.
 */
class OD_Widget_Lookbook extends OD_Widget {

	public function get_name() { return 'od-lookbook'; }
	public function get_title() { return esc_html__( 'Lookbook Strip', 'ojasvidrapes' ); }
	public function get_icon() { return 'eicon-photo-library'; }

	protected function register_controls() {
		$this->start_controls_section( 'heading_section', array( 'label' => esc_html__( 'Heading', 'ojasvidrapes' ) ) );

		$this->add_heading_controls(
			esc_html__( 'Styled by us', 'ojasvidrapes' ),
			__( 'The <em>Lookbook</em>', 'ojasvidrapes' ),
			esc_html__( 'How our team drapes the season — shot on real women, no retouching.', 'ojasvidrapes' )
		);

		$this->end_controls_section();

		$this->start_controls_section( 'content', array( 'label' => esc_html__( 'Images', 'ojasvidrapes' ) ) );

		$this->add_control(
			'gallery',
			array(
				'label'       => esc_html__( 'Choose images', 'ojasvidrapes' ),
				'type'        => Controls_Manager::GALLERY,
				'description' => esc_html__( 'Leave empty to pull recent products automatically.', 'ojasvidrapes' ),
			)
		);

		$this->add_control(
			'count',
			array(
				'label'     => esc_html__( 'How many (automatic mode)', 'ojasvidrapes' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 3,
				'max'       => 12,
				'default'   => 5,
				'condition' => array( 'gallery' => '' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$shots = array();

		if ( ! empty( $s['gallery'] ) ) {
			foreach ( $s['gallery'] as $image ) {
				$url = wp_get_attachment_image_url( $image['id'], 'od-product-lg' );

				if ( $url ) {
					$shots[] = array(
						'img'   => $url,
						'url'   => '',
						'label' => get_post_meta( $image['id'], '_wp_attachment_image_alt', true ),
					);
				}
			}
		} elseif ( class_exists( 'WooCommerce' ) ) {
			$query = new WP_Query(
				array(
					'post_type'      => 'product',
					'post_status'    => 'publish',
					'posts_per_page' => absint( $s['count'] ),
					'orderby'        => 'rand',
					'no_found_rows'  => true,
				)
			);

			foreach ( $query->posts as $post ) {
				$url = get_the_post_thumbnail_url( $post, 'od-product-lg' );

				if ( $url ) {
					$shots[] = array(
						'img'   => $url,
						'url'   => get_permalink( $post ),
						'label' => get_the_title( $post ),
					);
				}
			}

			wp_reset_postdata();
		}

		if ( count( $shots ) < 2 ) {
			$this->editor_notice( esc_html__( 'Pick some images, or add products with featured images.', 'ojasvidrapes' ) );
			return;
		}

		$this->render_heading( $s );
		?>
		<div class="od-look">
			<?php foreach ( $shots as $shot ) : ?>
				<?php $tag = $shot['url'] ? 'a' : 'div'; ?>
				<<?php echo esc_attr( $tag ); ?> class="od-look__cell"<?php echo $shot['url'] ? ' href="' . esc_url( $shot['url'] ) . '"' : ''; ?>>
					<img src="<?php echo esc_url( $shot['img'] ); ?>" alt="<?php echo esc_attr( $shot['label'] ); ?>" loading="lazy" />
					<?php if ( $shot['label'] ) : ?>
						<span class="od-look__tag"><?php echo esc_html( wp_trim_words( $shot['label'], 4, '' ) ); ?></span>
					<?php endif; ?>
				</<?php echo esc_attr( $tag ); ?>>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
