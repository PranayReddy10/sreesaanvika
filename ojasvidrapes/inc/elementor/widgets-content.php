<?php
/**
 * Content widgets: heading, hero, trust strip, promo banner, countdown,
 * story band and newsletter.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Controls_Manager;
use Elementor\Repeater;

/**
 * Section heading — eyebrow, gilded title, lotus ornament, description.
 */
class OD_Widget_Heading extends OD_Widget {

	public function get_name() { return 'od-heading'; }
	public function get_title() { return esc_html__( 'Section Heading', 'ojasvidrapes' ); }
	public function get_icon() { return 'eicon-heading'; }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => esc_html__( 'Heading', 'ojasvidrapes' ) ) );

		$this->add_heading_controls(
			esc_html__( 'The collection', 'ojasvidrapes' ),
			__( 'Every <em>Drape</em> We Have', 'ojasvidrapes' ),
			''
		);

		$this->end_controls_section();
	}

	protected function render() {
		$this->render_heading( $this->get_settings_for_display() );
	}
}

/**
 * Hero slider.
 */
class OD_Widget_Hero extends OD_Widget {

	public function get_name() { return 'od-hero'; }
	public function get_title() { return esc_html__( 'Hero Slider', 'ojasvidrapes' ); }
	public function get_icon() { return 'eicon-slides'; }

	protected function register_controls() {
		$this->start_controls_section( 'slides_section', array( 'label' => esc_html__( 'Slides', 'ojasvidrapes' ) ) );

		$slide = new Repeater();

		$slide->add_control(
			'eyebrow',
			array(
				'label'   => esc_html__( 'Eyebrow', 'ojasvidrapes' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'The Bridal Edit', 'ojasvidrapes' ),
			)
		);

		$slide->add_control(
			'title',
			array(
				'label'       => esc_html__( 'Title', 'ojasvidrapes' ),
				'description' => esc_html__( 'Wrap a word in <em> tags to gild it.', 'ojasvidrapes' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 2,
				'default'     => __( 'Kanchipuram Silk, <em>Woven in Gold</em>', 'ojasvidrapes' ),
			)
		);

		$slide->add_control(
			'text',
			array(
				'label'   => esc_html__( 'Description', 'ojasvidrapes' ),
				'type'    => Controls_Manager::TEXTAREA,
				'rows'    => 3,
				'default' => esc_html__( 'Pure zari, temple borders and the kind of lustre that only a six-month loom can give.', 'ojasvidrapes' ),
			)
		);

		$slide->add_control(
			'image',
			array(
				'label' => esc_html__( 'Background image', 'ojasvidrapes' ),
				'type'  => Controls_Manager::MEDIA,
			)
		);

		$slide->add_control(
			'align',
			array(
				'label'   => esc_html__( 'Text position', 'ojasvidrapes' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'left',
				'options' => array(
					'left'   => esc_html__( 'Left', 'ojasvidrapes' ),
					'center' => esc_html__( 'Centre', 'ojasvidrapes' ),
					'right'  => esc_html__( 'Right', 'ojasvidrapes' ),
				),
			)
		);

		$slide->add_control(
			'btn_text',
			array(
				'label'   => esc_html__( 'Button label', 'ojasvidrapes' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Shop Sarees', 'ojasvidrapes' ),
			)
		);

		$slide->add_control(
			'btn_link',
			array(
				'label' => esc_html__( 'Button link', 'ojasvidrapes' ),
				'type'  => Controls_Manager::URL,
			)
		);

		$slide->add_control(
			'btn2_text',
			array(
				'label'   => esc_html__( 'Second button label', 'ojasvidrapes' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'View lookbook', 'ojasvidrapes' ),
			)
		);

		$slide->add_control(
			'btn2_link',
			array(
				'label' => esc_html__( 'Second button link', 'ojasvidrapes' ),
				'type'  => Controls_Manager::URL,
			)
		);

		$this->add_control(
			'slides',
			array(
				'label'       => esc_html__( 'Slides', 'ojasvidrapes' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $slide->get_controls(),
				'title_field' => '{{{ eyebrow || title }}}',
				'default'     => array(
					array( 'eyebrow' => esc_html__( 'The Bridal Edit', 'ojasvidrapes' ) ),
				),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'hero_settings', array( 'label' => esc_html__( 'Settings', 'ojasvidrapes' ) ) );

		$this->add_control(
			'autoplay',
			array(
				'label'        => esc_html__( 'Auto-advance', 'ojasvidrapes' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'speed',
			array(
				'label'   => esc_html__( 'Seconds per slide', 'ojasvidrapes' ),
				'type'    => Controls_Manager::NUMBER,
				'min'     => 3,
				'max'     => 20,
				'default' => 6,
			)
		);

		$this->add_responsive_control(
			'min_height',
			array(
				'label'      => esc_html__( 'Minimum height', 'ojasvidrapes' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array(
					'px' => array( 'min' => 320, 'max' => 1000 ),
					'vh' => array( 'min' => 40, 'max' => 100 ),
				),
				'selectors'  => array(
					'{{WRAPPER}} .od-hero__slide' => 'min-height: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s      = $this->get_settings_for_display();
		$slides = ! empty( $s['slides'] ) ? $s['slides'] : array();

		if ( ! $slides ) {
			$this->editor_notice( esc_html__( 'Add a slide to see the hero.', 'ojasvidrapes' ) );
			return;
		}

		$total = count( $slides );
		?>
		<section class="od-hero" data-hero
			data-autoplay="<?php echo ( 'yes' === $s['autoplay'] ) ? '1' : '0'; ?>"
			data-speed="<?php echo esc_attr( absint( $s['speed'] ) ); ?>"
			aria-roledescription="carousel">

			<div class="od-hero__viewport">
				<?php foreach ( $slides as $n => $slide ) : ?>
					<?php $img = $this->image_url( isset( $slide['image'] ) ? $slide['image'] : array() ); ?>
					<div class="od-hero__slide od-hero__slide--<?php echo esc_attr( $slide['align'] ); ?><?php echo 0 === $n ? ' is-active' : ''; ?>"
						role="group" aria-roledescription="slide">

						<div class="od-hero__bg"<?php echo $img ? od_bg_style( $img ) : ''; ?>></div>

						<div class="od-container">
							<div class="od-hero__content">
								<?php if ( ! empty( $slide['eyebrow'] ) ) : ?>
									<span class="od-hero__eyebrow"><?php echo esc_html( $slide['eyebrow'] ); ?></span>
								<?php endif; ?>

								<?php if ( ! empty( $slide['title'] ) ) : ?>
									<h2 class="od-hero__title"><?php echo od_kses( $slide['title'] ); ?></h2>
								<?php endif; ?>

								<?php if ( ! empty( $slide['text'] ) ) : ?>
									<p class="od-hero__text"><?php echo esc_html( $slide['text'] ); ?></p>
								<?php endif; ?>

								<?php if ( ! empty( $slide['btn_text'] ) || ! empty( $slide['btn2_text'] ) ) : ?>
									<div class="od-hero__cta">
										<?php if ( ! empty( $slide['btn_text'] ) ) : ?>
											<a class="od-btn od-btn--lg"<?php echo $this->link_attrs( $slide['btn_link'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
												<?php echo esc_html( $slide['btn_text'] ); ?>
												<?php od_the_icon( 'arrow-right', 16 ); ?>
											</a>
										<?php endif; ?>

										<?php if ( ! empty( $slide['btn2_text'] ) ) : ?>
											<a class="od-btn od-btn--outline-light od-btn--lg"<?php echo $this->link_attrs( $slide['btn2_link'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
												<?php echo esc_html( $slide['btn2_text'] ); ?>
											</a>
										<?php endif; ?>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( $total > 1 ) : ?>
				<button type="button" class="od-icon-btn od-hero__nav od-hero__nav--prev"
					aria-label="<?php esc_attr_e( 'Previous slide', 'ojasvidrapes' ); ?>"><?php od_the_icon( 'chevron-left', 20 ); ?></button>
				<button type="button" class="od-icon-btn od-hero__nav od-hero__nav--next"
					aria-label="<?php esc_attr_e( 'Next slide', 'ojasvidrapes' ); ?>"><?php od_the_icon( 'chevron-right', 20 ); ?></button>

				<div class="od-hero__dots" role="tablist">
					<?php for ( $i = 0; $i < $total; $i++ ) : ?>
						<button type="button" class="od-hero__dot<?php echo 0 === $i ? ' is-active' : ''; ?>" role="tab"
							aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slide number */ __( 'Go to slide %d', 'ojasvidrapes' ), $i + 1 ) ); ?>"></button>
					<?php endfor; ?>
				</div>
			<?php endif; ?>
		</section>
		<?php
	}
}

/**
 * Trust / USP strip.
 */
class OD_Widget_USP extends OD_Widget {

	public function get_name() { return 'od-usp'; }
	public function get_title() { return esc_html__( 'Trust Strip', 'ojasvidrapes' ); }
	public function get_icon() { return 'eicon-info-box'; }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => esc_html__( 'Items', 'ojasvidrapes' ) ) );

		$item = new Repeater();

		$item->add_control(
			'icon',
			array(
				'label'   => esc_html__( 'Icon', 'ojasvidrapes' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'truck',
				'options' => $this->icon_choices(),
			)
		);

		$item->add_control(
			'title',
			array(
				'label'   => esc_html__( 'Title', 'ojasvidrapes' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Free shipping in India', 'ojasvidrapes' ),
			)
		);

		$item->add_control(
			'text',
			array(
				'label'   => esc_html__( 'Text', 'ojasvidrapes' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'On every order above ₹2,999', 'ojasvidrapes' ),
			)
		);

		$this->add_control(
			'items',
			array(
				'label'       => esc_html__( 'Items', 'ojasvidrapes' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $item->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array(
					array( 'icon' => 'truck', 'title' => esc_html__( 'Free shipping in India', 'ojasvidrapes' ), 'text' => esc_html__( 'On every order above ₹2,999', 'ojasvidrapes' ) ),
					array( 'icon' => 'shield', 'title' => esc_html__( 'Certified handloom', 'ojasvidrapes' ), 'text' => esc_html__( 'Silk Mark & Handloom Mark', 'ojasvidrapes' ) ),
					array( 'icon' => 'refresh', 'title' => esc_html__( '7-day easy returns', 'ojasvidrapes' ), 'text' => esc_html__( 'No questions, free pickup', 'ojasvidrapes' ) ),
					array( 'icon' => 'headset', 'title' => esc_html__( 'Talk to a stylist', 'ojasvidrapes' ), 'text' => esc_html__( 'WhatsApp us, 10am – 7pm', 'ojasvidrapes' ) ),
				),
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => esc_html__( 'Columns', 'ojasvidrapes' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '4',
				// Without per-device defaults Elementor applies the desktop
				// value at every width and the row overflows the screen.
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => array( '1' => '1', '2' => '2', '3' => '3', '4' => '4' ),
				'selectors'      => array(
					'{{WRAPPER}} .od-usp__grid' => 'grid-template-columns: repeat({{VALUE}}, minmax(0, 1fr));',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		if ( empty( $s['items'] ) ) {
			return;
		}
		?>
		<div class="od-usp" style="border:0;background:none">
			<div class="od-usp__grid">
				<?php foreach ( $s['items'] as $item ) : ?>
					<?php od_usp_item( $item['icon'], $item['title'], $item['text'] ); ?>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}
}

/**
 * Offer banner.
 */
class OD_Widget_Promo extends OD_Widget {

	public function get_name() { return 'od-promo'; }
	public function get_title() { return esc_html__( 'Offer Banner', 'ojasvidrapes' ); }
	public function get_icon() { return 'eicon-image-rollover'; }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => esc_html__( 'Banner', 'ojasvidrapes' ) ) );

		$this->add_control( 'big', array( 'label' => esc_html__( 'Big text', 'ojasvidrapes' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( '40% OFF', 'ojasvidrapes' ) ) );
		$this->add_control( 'title', array( 'label' => esc_html__( 'Heading', 'ojasvidrapes' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'Banarasi Silk Festival', 'ojasvidrapes' ) ) );
		$this->add_control( 'text', array( 'label' => esc_html__( 'Text', 'ojasvidrapes' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 3, 'default' => esc_html__( 'Hand-woven katan silk with real zari butis. Limited looms, limited pieces.', 'ojasvidrapes' ) ) );
		$this->add_control( 'btn_text', array( 'label' => esc_html__( 'Button label', 'ojasvidrapes' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'Shop the offer', 'ojasvidrapes' ) ) );
		$this->add_control( 'btn_link', array( 'label' => esc_html__( 'Button link', 'ojasvidrapes' ), 'type' => Controls_Manager::URL ) );
		$this->add_control( 'image', array( 'label' => esc_html__( 'Background image', 'ojasvidrapes' ), 'type' => Controls_Manager::MEDIA ) );

		$this->add_control(
			'show_countdown',
			array(
				'label'        => esc_html__( 'Show a countdown', 'ojasvidrapes' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'end',
			array(
				'label'       => esc_html__( 'Countdown ends', 'ojasvidrapes' ),
				'type'        => Controls_Manager::DATE_TIME,
				'condition'   => array( 'show_countdown' => 'yes' ),
				'description' => esc_html__( 'Leave empty for three days from now.', 'ojasvidrapes' ),
			)
		);

		$this->add_responsive_control(
			'min_height',
			array(
				'label'      => esc_html__( 'Minimum height', 'ojasvidrapes' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 200, 'max' => 700 ) ),
				'selectors'  => array( '{{WRAPPER}} .od-promo' => 'min-height: {{SIZE}}{{UNIT}};' ),
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s   = $this->get_settings_for_display();
		$img = $this->image_url( isset( $s['image'] ) ? $s['image'] : array() );
		?>
		<article class="od-promo">
			<div class="od-promo__bg"<?php echo $img ? od_bg_style( $img ) : ''; ?>></div>

			<div class="od-promo__content">
				<?php if ( ! empty( $s['big'] ) ) : ?>
					<span class="od-promo__off"><?php echo esc_html( $s['big'] ); ?></span>
				<?php endif; ?>

				<?php if ( ! empty( $s['title'] ) ) : ?>
					<h3><?php echo esc_html( $s['title'] ); ?></h3>
				<?php endif; ?>

				<?php if ( ! empty( $s['text'] ) ) : ?>
					<p><?php echo esc_html( $s['text'] ); ?></p>
				<?php endif; ?>

				<?php
				if ( ! empty( $s['show_countdown'] ) && 'yes' === $s['show_countdown'] ) {
					od_countdown( isset( $s['end'] ) ? $s['end'] : '' );
				}
				?>

				<?php if ( ! empty( $s['btn_text'] ) ) : ?>
					<a class="od-btn"<?php echo $this->link_attrs( $s['btn_link'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<?php echo esc_html( $s['btn_text'] ); ?>
						<?php od_the_icon( 'arrow-right', 16 ); ?>
					</a>
				<?php endif; ?>
			</div>
		</article>
		<?php
	}
}

/**
 * Story band — full-bleed image band with a centred message.
 */
class OD_Widget_Band extends OD_Widget {

	public function get_name() { return 'od-band'; }
	public function get_title() { return esc_html__( 'Story Band', 'ojasvidrapes' ); }
	public function get_icon() { return 'eicon-parallax'; }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => esc_html__( 'Band', 'ojasvidrapes' ) ) );

		$this->add_control( 'title', array( 'label' => esc_html__( 'Heading', 'ojasvidrapes' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 2, 'default' => esc_html__( 'Woven by hands that have known the loom for six generations', 'ojasvidrapes' ) ) );
		$this->add_control( 'text', array( 'label' => esc_html__( 'Text', 'ojasvidrapes' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 4, 'default' => esc_html__( 'Every OJASVI saree is sourced straight from weaver families in Kanchipuram, Banaras, Pochampally and Bhagalpur — no middlemen, fair wages, and a name tag on every drape.', 'ojasvidrapes' ) ) );
		$this->add_control( 'btn_text', array( 'label' => esc_html__( 'Button label', 'ojasvidrapes' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'Read our story', 'ojasvidrapes' ) ) );
		$this->add_control( 'btn_link', array( 'label' => esc_html__( 'Button link', 'ojasvidrapes' ), 'type' => Controls_Manager::URL ) );
		$this->add_control( 'image', array( 'label' => esc_html__( 'Background image', 'ojasvidrapes' ), 'type' => Controls_Manager::MEDIA ) );

		$this->end_controls_section();
	}

	protected function render() {
		$s   = $this->get_settings_for_display();
		$img = $this->image_url( isset( $s['image'] ) ? $s['image'] : array() );
		?>
		<section class="od-band"<?php echo $img ? od_bg_style( $img ) : ''; ?>>
			<div class="od-container od-container--narrow">
				<div class="od-ornament" aria-hidden="true" style="margin-bottom:20px"><?php od_the_icon( 'paisley', 24 ); ?></div>

				<?php if ( ! empty( $s['title'] ) ) : ?>
					<h2 style="font-size:clamp(1.6rem,3.6vw,2.8rem)"><?php echo esc_html( $s['title'] ); ?></h2>
				<?php endif; ?>

				<?php if ( ! empty( $s['text'] ) ) : ?>
					<p style="color:var(--od-text-soft);font-size:1.06rem;max-width:660px;margin:0 auto 28px"><?php echo esc_html( $s['text'] ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $s['btn_text'] ) ) : ?>
					<a class="od-btn od-btn--ghost od-btn--lg"<?php echo $this->link_attrs( $s['btn_link'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
						<?php echo esc_html( $s['btn_text'] ); ?>
					</a>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}
}

/**
 * Newsletter sign-up.
 */
class OD_Widget_Newsletter extends OD_Widget {

	public function get_name() { return 'od-newsletter'; }
	public function get_title() { return esc_html__( 'Newsletter', 'ojasvidrapes' ); }
	public function get_icon() { return 'eicon-email-field'; }

	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => esc_html__( 'Newsletter', 'ojasvidrapes' ) ) );

		$this->add_control( 'title', array( 'label' => __( 'Heading', 'ojasvidrapes' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Be first to the <em>New Drop</em>', 'ojasvidrapes' ) ) );
		$this->add_control( 'text', array( 'label' => esc_html__( 'Text', 'ojasvidrapes' ), 'type' => Controls_Manager::TEXTAREA, 'rows' => 3, 'default' => esc_html__( 'Weave stories, early access to festive collections and a ₹500 voucher on your first order.', 'ojasvidrapes' ) ) );
		$this->add_control( 'button', array( 'label' => esc_html__( 'Button label', 'ojasvidrapes' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'Subscribe', 'ojasvidrapes' ) ) );
		$this->add_control( 'note', array( 'label' => esc_html__( 'Small print', 'ojasvidrapes' ), 'type' => Controls_Manager::TEXT, 'default' => esc_html__( 'No spam. Unsubscribe any time.', 'ojasvidrapes' ) ) );

		$this->end_controls_section();
	}

	protected function render() {
		$s  = $this->get_settings_for_display();
		$id = 'od-news-' . $this->get_id();
		?>
		<div class="od-newsletter" style="border:0;padding:0;background:none">
			<div class="od-container od-container--narrow">
				<div class="od-ornament" aria-hidden="true" style="margin-bottom:16px"><?php od_the_icon( 'lotus', 22 ); ?></div>

				<?php if ( ! empty( $s['title'] ) ) : ?>
					<h2><?php echo od_kses( $s['title'] ); ?></h2>
				<?php endif; ?>

				<?php if ( ! empty( $s['text'] ) ) : ?>
					<p style="color:var(--od-muted);max-width:520px;margin:0 auto"><?php echo esc_html( $s['text'] ); ?></p>
				<?php endif; ?>

				<form class="od-newsletter__form" data-newsletter>
					<label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Email address', 'ojasvidrapes' ); ?></label>
					<input type="email" id="<?php echo esc_attr( $id ); ?>" name="email" required
						placeholder="<?php esc_attr_e( 'you@example.com', 'ojasvidrapes' ); ?>" autocomplete="email" />
					<button type="submit" class="od-btn"><?php echo esc_html( $s['button'] ); ?></button>
				</form>

				<?php if ( ! empty( $s['note'] ) ) : ?>
					<p class="od-newsletter__note"><?php echo esc_html( $s['note'] ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
