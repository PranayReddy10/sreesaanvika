<?php
/**
 * Social proof widgets: testimonials and the Instagram grid.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

/**
 * Customer testimonials.
 */
class SS_Widget_Testimonials extends SS_Widget {

	public function get_name() { return 'ss-testimonials'; }
	public function get_title() { return esc_html__( 'Testimonials', 'sreesaanvika' ); }
	public function get_icon() { return 'eicon-testimonial'; }

	protected function register_controls() {
		$this->start_controls_section( 'heading_section', array( 'label' => esc_html__( 'Heading', 'sreesaanvika' ) ) );

		$this->add_heading_controls(
			esc_html__( 'From our customers', 'sreesaanvika' ),
			__( 'Worn & <em>Loved</em>', 'sreesaanvika' ),
			esc_html__( 'Over 12,000 women across India have shopped with us.', 'sreesaanvika' )
		);

		$this->end_controls_section();

		$this->start_controls_section( 'content', array( 'label' => esc_html__( 'Quotes', 'sreesaanvika' ) ) );

		$this->add_control(
			'source',
			array(
				'label'   => esc_html__( 'Source', 'sreesaanvika' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'manual',
				'options' => array(
					'manual' => esc_html__( 'Written here', 'sreesaanvika' ),
					'reviews' => esc_html__( 'Latest WooCommerce reviews', 'sreesaanvika' ),
				),
			)
		);

		$this->add_control(
			'review_count',
			array(
				'label'     => esc_html__( 'How many reviews', 'sreesaanvika' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 9,
				'default'   => 3,
				'condition' => array( 'source' => 'reviews' ),
			)
		);

		$quote = new Repeater();

		$quote->add_control(
			'stars',
			array(
				'label'   => esc_html__( 'Stars', 'sreesaanvika' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '5',
				'options' => array( '5' => '5', '4' => '4', '3' => '3', '2' => '2', '1' => '1' ),
			)
		);

		$quote->add_control( 'text', array( 'label' => esc_html__( 'Quote', 'sreesaanvika' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 4 ) );
		$quote->add_control( 'name', array( 'label' => esc_html__( 'Name', 'sreesaanvika' ), 'type' => Controls_Manager::TEXT ) );
		$quote->add_control( 'city', array( 'label' => esc_html__( 'City', 'sreesaanvika' ), 'type' => Controls_Manager::TEXT ) );

		$this->add_control(
			'quotes',
			array(
				'label'       => esc_html__( 'Quotes', 'sreesaanvika' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $quote->get_controls(),
				'title_field' => '{{{ name }}}',
				'condition'   => array( 'source' => 'manual' ),
				'default'     => array(
					array(
						'stars' => '5',
						'text'  => esc_html__( 'The Kanchipuram I ordered for my sister\'s wedding arrived in four days, beautifully packed with the weaver\'s name on the tag. The zari is real — my mother checked!', 'sreesaanvika' ),
						'name'  => esc_html__( 'Lakshmi Narayanan', 'sreesaanvika' ),
						'city'  => esc_html__( 'Chennai', 'sreesaanvika' ),
					),
					array(
						'stars' => '5',
						'text'  => esc_html__( 'I have bought three sarees and a temple haaram now. The colours are exactly as photographed, which almost never happens online.', 'sreesaanvika' ),
						'name'  => esc_html__( 'Ananya Deshmukh', 'sreesaanvika' ),
						'city'  => esc_html__( 'Pune', 'sreesaanvika' ),
					),
					array(
						'stars' => '5',
						'text'  => esc_html__( 'Ordered an Anarkali two sizes up by mistake — the return pickup came the next morning and the exchange shipped the same week.', 'sreesaanvika' ),
						'name'  => esc_html__( 'Fatima Sheikh', 'sreesaanvika' ),
						'city'  => esc_html__( 'Hyderabad', 'sreesaanvika' ),
					),
				),
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => esc_html__( 'Columns', 'sreesaanvika' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '3',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => array( '1' => '1', '2' => '2', '3' => '3' ),
				'selectors'      => array(
					'{{WRAPPER}} .ss-quotes' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s      = $this->get_settings_for_display();
		$quotes = array();

		if ( 'reviews' === $s['source'] && class_exists( 'WooCommerce' ) ) {
			$comments = get_comments(
				array(
					'post_type'    => 'product',
					'status'       => 'approve',
					'number'       => absint( $s['review_count'] ),
					'meta_key'     => 'rating', // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_value'   => array( '4', '5' ), // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_compare' => 'IN',
				)
			);

			foreach ( $comments as $comment ) {
				$quotes[] = array(
					'stars' => (int) get_comment_meta( $comment->comment_ID, 'rating', true ),
					'text'  => wp_trim_words( $comment->comment_content, 34 ),
					'name'  => $comment->comment_author,
					'city'  => get_the_title( $comment->comment_post_ID ),
				);
			}
		} else {
			foreach ( (array) $s['quotes'] as $quote ) {
				if ( empty( $quote['text'] ) ) {
					continue;
				}

				$quotes[] = array(
					'stars' => (int) $quote['stars'],
					'text'  => $quote['text'],
					'name'  => $quote['name'],
					'city'  => $quote['city'],
				);
			}
		}

		if ( ! $quotes ) {
			$this->editor_notice( esc_html__( 'Add a quote, or switch the source to WooCommerce reviews.', 'sreesaanvika' ) );
			return;
		}

		$this->render_heading( $s );
		?>
		<div class="ss-quotes">
			<?php foreach ( $quotes as $quote ) : ?>
				<figure class="ss-quote">
					<div class="ss-quote__stars" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: star rating */ __( '%d out of 5 stars', 'sreesaanvika' ), $quote['stars'] ) ); ?>">
						<?php echo esc_html( str_repeat( '★', max( 1, min( 5, $quote['stars'] ) ) ) ); ?>
					</div>

					<blockquote class="ss-quote__text" style="margin:0;padding:0;border:0;background:none">
						<?php echo esc_html( $quote['text'] ); ?>
					</blockquote>

					<?php if ( $quote['name'] ) : ?>
						<figcaption class="ss-quote__who">
							<span class="ss-quote__avatar" aria-hidden="true"><?php echo esc_html( ss_initials( $quote['name'] ) ); ?></span>
							<span>
								<strong class="ss-quote__name"><?php echo esc_html( $quote['name'] ); ?></strong>
								<?php if ( $quote['city'] ) : ?>
									<span class="ss-quote__city"><?php echo esc_html( $quote['city'] ); ?></span>
								<?php endif; ?>
							</span>
						</figcaption>
					<?php endif; ?>
				</figure>
			<?php endforeach; ?>
		</div>
		<?php
	}
}

/**
 * Instagram-style grid.
 */
class SS_Widget_Instagram extends SS_Widget {

	public function get_name() { return 'ss-instagram'; }
	public function get_title() { return esc_html__( 'Instagram Grid', 'sreesaanvika' ); }
	public function get_icon() { return 'eicon-instagram-gallery'; }

	protected function register_controls() {
		$this->start_controls_section( 'heading_section', array( 'label' => esc_html__( 'Heading', 'sreesaanvika' ) ) );

		$this->add_heading_controls(
			esc_html__( 'Follow along', 'sreesaanvika' ),
			'@sreesaanvika',
			esc_html__( 'Tag us in your drape — we reshare our favourites every week.', 'sreesaanvika' )
		);

		$this->end_controls_section();

		$this->start_controls_section( 'content', array( 'label' => esc_html__( 'Images', 'sreesaanvika' ) ) );

		$this->add_control(
			'profile',
			array(
				'label'       => esc_html__( 'Instagram link', 'sreesaanvika' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => 'https://instagram.com/sreesaanvika',
			)
		);

		$this->add_control(
			'gallery',
			array(
				'label'       => esc_html__( 'Choose images', 'sreesaanvika' ),
				'type'        => Controls_Manager::GALLERY,
				'description' => esc_html__( 'Leave empty to use the newest images in your media library.', 'sreesaanvika' ),
			)
		);

		$this->add_control(
			'count',
			array(
				'label'     => esc_html__( 'How many (automatic mode)', 'sreesaanvika' ),
				'type'      => Controls_Manager::NUMBER,
				'min'       => 3,
				'max'       => 12,
				'default'   => 6,
				'condition' => array( 'gallery' => '' ),
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => esc_html__( 'Columns', 'sreesaanvika' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '6',
				'tablet_default' => '4',
				'mobile_default' => '3',
				'options'        => array( '3' => '3', '4' => '4', '5' => '5', '6' => '6' ),
				'selectors'      => array(
					'{{WRAPPER}} .ss-gram' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s   = $this->get_settings_for_display();
		$ids = array();

		if ( ! empty( $s['gallery'] ) ) {
			foreach ( $s['gallery'] as $image ) {
				$ids[] = (int) $image['id'];
			}
		} else {
			$images = get_posts(
				array(
					'post_type'      => 'attachment',
					'post_mime_type' => 'image',
					'post_status'    => 'inherit',
					'posts_per_page' => absint( $s['count'] ),
					'orderby'        => 'date',
					'order'          => 'DESC',
					'fields'         => 'ids',
				)
			);

			$ids = $images ? $images : array();
		}

		if ( count( $ids ) < 3 ) {
			$this->editor_notice( esc_html__( 'Pick some images, or upload a few to the media library.', 'sreesaanvika' ) );
			return;
		}

		$link = ! empty( $s['profile']['url'] ) ? $s['profile']['url'] : '';

		$this->render_heading( $s );
		?>
		<div class="ss-gram">
			<?php foreach ( $ids as $id ) : ?>
				<?php if ( $link ) : ?>
					<a class="ss-gram__cell" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo wp_get_attachment_image( $id, 'ss-category', false, array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
						<?php ss_the_icon( 'instagram', 26 ); ?>
						<span class="screen-reader-text"><?php esc_html_e( 'Open our Instagram', 'sreesaanvika' ); ?></span>
					</a>
				<?php else : ?>
					<div class="ss-gram__cell">
						<?php echo wp_get_attachment_image( $id, 'ss-category', false, array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
