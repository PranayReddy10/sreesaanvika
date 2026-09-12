<?php
/**
 * Catalogue widgets: product grid, category rail, category mosaic, lookbook.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;

/**
 * Product grid — the same card as the shop, with a choice of source.
 */
class SS_Widget_Products extends SS_Widget {

	public function get_name() { return 'ss-products'; }
	public function get_title() { return esc_html__( 'Product Grid', 'sreesaanvika' ); }
	public function get_icon() { return 'eicon-products'; }

	protected function register_controls() {
		$this->start_controls_section( 'heading_section', array( 'label' => esc_html__( 'Heading', 'sreesaanvika' ) ) );

		$this->add_heading_controls(
			esc_html__( 'Fresh off the loom', 'sreesaanvika' ),
			esc_html__( 'New <em>Arrivals</em>', 'sreesaanvika' ),
			esc_html__( 'The newest weaves, added this week.', 'sreesaanvika' )
		);

		$this->end_controls_section();

		$this->start_controls_section( 'query_section', array( 'label' => esc_html__( 'Products', 'sreesaanvika' ) ) );

		$this->add_control(
			'source',
			array(
				'label'   => esc_html__( 'Show', 'sreesaanvika' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'latest',
				'options' => array(
					'latest'   => esc_html__( 'Newest first', 'sreesaanvika' ),
					'best'     => esc_html__( 'Best sellers', 'sreesaanvika' ),
					'sale'     => esc_html__( 'On sale', 'sreesaanvika' ),
					'featured' => esc_html__( 'Featured', 'sreesaanvika' ),
					'rated'    => esc_html__( 'Top rated', 'sreesaanvika' ),
					'random'   => esc_html__( 'Random', 'sreesaanvika' ),
				),
			)
		);

		$this->add_control(
			'category',
			array(
				'label'   => esc_html__( 'Category', 'sreesaanvika' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => $this->category_choices(),
			)
		);

		$this->add_control(
			'count',
			array(
				'label'   => esc_html__( 'How many', 'sreesaanvika' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 24,
				'default' => 8,
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'   => esc_html__( 'Columns', 'sreesaanvika' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '4',
				'options' => array( '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'       => esc_html__( 'Button below the grid', 'sreesaanvika' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'description' => esc_html__( 'Leave empty to hide it.', 'sreesaanvika' ),
			)
		);

		$this->add_control(
			'button_link',
			array(
				'label'     => esc_html__( 'Button link', 'sreesaanvika' ),
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
			$cat_query = ss_cat_query( $s['category'] );

			$args['tax_query'] = isset( $args['tax_query'] ) // phpcs:ignore WordPress.DB.SlowDBQuery
				? array_merge( $args['tax_query'], $cat_query['tax_query'] )
				: $cat_query['tax_query'];
		}

		return $args;
	}

	protected function render() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			$this->editor_notice( esc_html__( 'This widget needs WooCommerce.', 'sreesaanvika' ) );
			return;
		}

		$s = $this->get_settings_for_display();

		ob_start();
		$found = ss_product_loop( $this->query_args( $s ), absint( $s['columns'] ) );
		$loop  = ob_get_clean();

		if ( ! $found ) {
			$this->editor_notice( esc_html__( 'No products match this selection yet.', 'sreesaanvika' ) );
			return;
		}

		$this->render_heading( $s );

		echo $loop; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( ! empty( $s['button_text'] ) ) {
			echo '<div class="ss-text-center" style="margin-top:36px">';
			printf(
				'<a class="ss-btn ss-btn--ghost ss-btn--lg"%s>%s</a>',
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
class SS_Widget_Category_Rail extends SS_Widget {

	public function get_name() { return 'ss-category-rail'; }
	public function get_title() { return esc_html__( 'Category Rail', 'sreesaanvika' ); }
	public function get_icon() { return 'eicon-post-slider'; }

	protected function register_controls() {
		$this->start_controls_section( 'heading_section', array( 'label' => esc_html__( 'Heading', 'sreesaanvika' ) ) );

		$this->add_heading_controls(
			esc_html__( 'Shop by category', 'sreesaanvika' ),
			esc_html__( 'Find Your <em>Drape</em>', 'sreesaanvika' ),
			esc_html__( 'From nine-yard Kanjivarams to everyday cottons and festive jewellery.', 'sreesaanvika' )
		);

		$this->end_controls_section();

		$this->start_controls_section( 'query_section', array( 'label' => esc_html__( 'Categories', 'sreesaanvika' ) ) );

		$this->add_control(
			'count',
			array(
				'label'   => esc_html__( 'How many', 'sreesaanvika' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 2,
				'max'     => 20,
				'default' => 10,
			)
		);

		$this->add_control(
			'top_level',
			array(
				'label'        => esc_html__( 'Top-level categories only', 'sreesaanvika' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			$this->editor_notice( esc_html__( 'This widget needs WooCommerce.', 'sreesaanvika' ) );
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
			$this->editor_notice( esc_html__( 'Add some product categories to fill this rail.', 'sreesaanvika' ) );
			return;
		}

		$this->render_heading( $s );
		?>
		<div class="ss-catrail">
			<?php
			foreach ( $terms as $term ) :
				$thumb_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
				$img      = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'ss-category' ) : '';
				?>
				<a class="ss-catrail__item" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
					<div class="ss-catrail__ring">
						<?php if ( $img ) : ?>
							<img src="<?php echo esc_url( $img ); ?>" alt="" loading="lazy" width="160" height="160" />
						<?php else : ?>
							<span aria-hidden="true"><?php echo esc_html( mb_substr( $term->name, 0, 1 ) ); ?></span>
						<?php endif; ?>
					</div>
					<span class="ss-catrail__name"><?php echo esc_html( $term->name ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
	}
}

/**
 * Category mosaic.
 */
class SS_Widget_Category_Mosaic extends SS_Widget {

	public function get_name() { return 'ss-category-mosaic'; }
	public function get_title() { return esc_html__( 'Category Mosaic', 'sreesaanvika' ); }
	public function get_icon() { return 'eicon-gallery-grid'; }

	protected function register_controls() {
		$this->start_controls_section( 'heading_section', array( 'label' => esc_html__( 'Heading', 'sreesaanvika' ) ) );

		$this->add_heading_controls(
			esc_html__( 'The collections', 'sreesaanvika' ),
			esc_html__( 'Curated for <em>Every Celebration</em>', 'sreesaanvika' ),
			esc_html__( 'Weddings, festivals, workdays and the quiet evenings in between.', 'sreesaanvika' )
		);

		$this->end_controls_section();

		$this->start_controls_section( 'query_section', array( 'label' => esc_html__( 'Categories', 'sreesaanvika' ) ) );

		$this->add_control(
			'count',
			array(
				'label'       => esc_html__( 'How many tiles', 'sreesaanvika' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 1,
				'max'         => 6,
				'default'     => 5,
				'description' => esc_html__( 'The tile pattern adapts so every row fills exactly.', 'sreesaanvika' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		if ( ! taxonomy_exists( 'product_cat' ) ) {
			$this->editor_notice( esc_html__( 'This widget needs WooCommerce.', 'sreesaanvika' ) );
			return;
		}

		$s = $this->get_settings_for_display();

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'number'     => absint( $s['count'] ),
				'parent'     => 0,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'exclude'    => array( get_option( 'default_product_cat' ) ),
			)
		);

		if ( ! $terms || is_wp_error( $terms ) ) {
			$this->editor_notice( esc_html__( 'Add some product categories to fill this mosaic.', 'sreesaanvika' ) );
			return;
		}

		// Same patterns as template-parts/home/cats.php.
		$patterns = array(
			1 => array( 'w6 ss-cat--h2' ),
			2 => array( 'w3', 'w3' ),
			3 => array( 'w4 ss-cat--h2', 'w2', 'w2' ),
			4 => array( 'w3', 'w3', 'w3', 'w3' ),
			5 => array( 'w4 ss-cat--h2', 'w2', 'w2', 'w3', 'w3' ),
			6 => array( 'w4 ss-cat--h2', 'w2', 'w2', 'w2', 'w2', 'w2' ),
		);

		$total = count( $terms );
		$sizes = isset( $patterns[ $total ] ) ? $patterns[ $total ] : $patterns[6];

		$this->render_heading( $s );
		?>
		<div class="ss-cats">
			<?php
			foreach ( $terms as $n => $term ) :
				$thumb_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
				$img      = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'ss-hero' ) : '';
				$size     = isset( $sizes[ $n ] ) ? $sizes[ $n ] : 'w2';
				?>
				<article class="ss-cat ss-cat--<?php echo esc_attr( $size ); ?>">
					<div class="ss-cat__img"<?php echo $img ? ss_bg_style( $img ) : ''; ?>></div>

					<div class="ss-cat__body">
						<span class="ss-cat__count">
							<?php
							printf(
								/* translators: %s: number of products */
								esc_html( _n( '%s piece', '%s pieces', $term->count, 'sreesaanvika' ) ),
								esc_html( number_format_i18n( $term->count ) )
							);
							?>
						</span>
						<h3 class="ss-cat__title"><?php echo esc_html( $term->name ); ?></h3>
						<span class="ss-cat__link"><?php esc_html_e( 'Explore', 'sreesaanvika' ); ?><?php ss_the_icon( 'arrow-right', 15 ); ?></span>
					</div>

					<a class="ss-cat__stretch" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
						<span class="screen-reader-text"><?php echo esc_html( $term->name ); ?></span>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
		<?php
	}
}

/**
 * Lookbook strip.
 */
class SS_Widget_Lookbook extends SS_Widget {

	public function get_name() { return 'ss-lookbook'; }
	public function get_title() { return esc_html__( 'Lookbook Strip', 'sreesaanvika' ); }
	public function get_icon() { return 'eicon-photo-library'; }

	protected function register_controls() {
		$this->start_controls_section( 'heading_section', array( 'label' => esc_html__( 'Heading', 'sreesaanvika' ) ) );

		$this->add_heading_controls(
			esc_html__( 'Styled by us', 'sreesaanvika' ),
			esc_html__( 'The <em>Lookbook</em>', 'sreesaanvika' ),
			esc_html__( 'How our team drapes the season — shot on real women, no retouching.', 'sreesaanvika' )
		);

		$this->end_controls_section();

		$this->start_controls_section( 'content', array( 'label' => esc_html__( 'Images', 'sreesaanvika' ) ) );

		$this->add_control(
			'gallery',
			array(
				'label'       => esc_html__( 'Choose images', 'sreesaanvika' ),
				'type'        => Controls_Manager::GALLERY,
				'description' => esc_html__( 'Leave empty to pull recent products automatically.', 'sreesaanvika' ),
			)
		);

		$this->add_control(
			'count',
			array(
				'label'     => esc_html__( 'How many (automatic mode)', 'sreesaanvika' ),
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
				$url = wp_get_attachment_image_url( $image['id'], 'ss-product-lg' );

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
				$url = get_the_post_thumbnail_url( $post, 'ss-product-lg' );

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
			$this->editor_notice( esc_html__( 'Pick some images, or add products with featured images.', 'sreesaanvika' ) );
			return;
		}

		$this->render_heading( $s );
		?>
		<div class="ss-look">
			<?php foreach ( $shots as $shot ) : ?>
				<?php $tag = $shot['url'] ? 'a' : 'div'; ?>
				<<?php echo esc_attr( $tag ); ?> class="ss-look__cell"<?php echo $shot['url'] ? ' href="' . esc_url( $shot['url'] ) . '"' : ''; ?>>
					<img src="<?php echo esc_url( $shot['img'] ); ?>" alt="<?php echo esc_attr( $shot['label'] ); ?>" loading="lazy" />
					<?php if ( $shot['label'] ) : ?>
						<span class="ss-look__tag"><?php echo esc_html( wp_trim_words( $shot['label'], 4, '' ) ); ?></span>
					<?php endif; ?>
				</<?php echo esc_attr( $tag ); ?>>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
