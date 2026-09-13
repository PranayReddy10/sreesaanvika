<?php
/**
 * One offer: which products it covers, and what the customer gets.
 *
 * @package SreeSaanvikaOffers
 */

defined( 'ABSPATH' ) || exit;

/**
 * An offer, stored as a post so the shop can add as many as it likes.
 */
class SSO_Offer {

	const TYPE = 'ss_offer';

	/**
	 * The post.
	 *
	 * @var WP_Post
	 */
	protected $post;

	/**
	 * Hook up.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_type' ) );
	}

	/**
	 * Register the post type.
	 */
	public static function register_type() {
		register_post_type(
			self::TYPE,
			array(
				'labels'          => array(
					'name'               => __( 'Offers', 'sreesaanvika-offers' ),
					'singular_name'      => __( 'Offer', 'sreesaanvika-offers' ),
					'add_new'            => __( 'Add offer', 'sreesaanvika-offers' ),
					'add_new_item'       => __( 'Add offer', 'sreesaanvika-offers' ),
					'edit_item'          => __( 'Edit offer', 'sreesaanvika-offers' ),
					'new_item'           => __( 'New offer', 'sreesaanvika-offers' ),
					'search_items'       => __( 'Search offers', 'sreesaanvika-offers' ),
					'not_found'          => __( 'No offers yet.', 'sreesaanvika-offers' ),
					'all_items'          => __( 'Offers', 'sreesaanvika-offers' ),
					'menu_name'          => __( 'Offers', 'sreesaanvika-offers' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'woocommerce',
				'supports'        => array( 'title' ),
				'capability_type' => 'post',
				'capabilities'    => array(
					'edit_posts'         => 'manage_woocommerce',
					'edit_others_posts'  => 'manage_woocommerce',
					'publish_posts'      => 'manage_woocommerce',
					'read_private_posts' => 'manage_woocommerce',
					'delete_posts'       => 'manage_woocommerce',
				),
				'map_meta_cap'    => true,
			)
		);
	}

	/**
	 * Constructor.
	 *
	 * @param WP_Post|int $post Offer post.
	 */
	public function __construct( $post ) {
		$this->post = get_post( $post );
	}

	/**
	 * Every published offer, cheapest lookup first.
	 *
	 * @return SSO_Offer[]
	 */
	public static function all() {
		static $cache = null;

		if ( null !== $cache ) {
			return $cache;
		}

		$posts = get_posts(
			array(
				'post_type'      => self::TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 50,
				'orderby'        => 'menu_order date',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);

		$cache = array_map(
			function ( $post ) {
				return new self( $post );
			},
			$posts
		);

		return $cache;
	}

	/**
	 * Every offer that is live right now.
	 *
	 * @return SSO_Offer[]
	 */
	public static function live() {
		return array_values(
			array_filter(
				self::all(),
				function ( $offer ) {
					return $offer->is_running();
				}
			)
		);
	}

	/**
	 * The live offers a given product takes part in.
	 *
	 * @param int $product_id Product id.
	 * @return SSO_Offer[]
	 */
	public static function for_product( $product_id ) {
		return array_values(
			array_filter(
				self::live(),
				function ( $offer ) use ( $product_id ) {
					return $offer->covers( $product_id );
				}
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Fields
	 * ------------------------------------------------------------------ */

	/**
	 * Offer id.
	 *
	 * @return int
	 */
	public function id() {
		return $this->post ? (int) $this->post->ID : 0;
	}

	/**
	 * Offer name, used as the fallback headline.
	 *
	 * @return string
	 */
	public function name() {
		return $this->post ? $this->post->post_title : '';
	}

	/**
	 * A meta value with a default.
	 *
	 * @param string $key     Meta key without the prefix.
	 * @param mixed  $default Fallback.
	 * @return mixed
	 */
	public function meta( $key, $default = '' ) {
		$value = get_post_meta( $this->id(), '_sso_' . $key, true );

		return ( '' === $value || null === $value ) ? $default : $value;
	}

	/**
	 * How many the customer has to buy.
	 *
	 * @return int
	 */
	public function buy() {
		return max( 1, (int) $this->meta( 'buy', 2 ) );
	}

	/**
	 * How many they then get at a discount.
	 *
	 * @return int
	 */
	public function get() {
		return max( 1, (int) $this->meta( 'get', 1 ) );
	}

	/**
	 * How much off the free ones, as a percentage.
	 *
	 * @return float
	 */
	public function percent() {
		$pct = (float) $this->meta( 'percent', 100 );

		return max( 0, min( 100, $pct ) );
	}

	/**
	 * Items needed for one round of the offer.
	 *
	 * @return int
	 */
	public function group_size() {
		return $this->buy() + $this->get();
	}

	/**
	 * Apply the offer more than once in a single cart?
	 *
	 * @return bool
	 */
	public function repeats() {
		return 'no' !== $this->meta( 'repeat', 'yes' );
	}

	/**
	 * Product ids picked by hand.
	 *
	 * @return int[]
	 */
	public function product_ids() {
		return array_filter( array_map( 'absint', (array) $this->meta( 'products', array() ) ) );
	}

	/**
	 * Category term ids.
	 *
	 * @return int[]
	 */
	public function category_ids() {
		return array_filter( array_map( 'absint', (array) $this->meta( 'categories', array() ) ) );
	}

	/**
	 * Products kept out, even if a category would let them in.
	 *
	 * @return int[]
	 */
	public function excluded_ids() {
		return array_filter( array_map( 'absint', (array) $this->meta( 'exclude', array() ) ) );
	}

	/**
	 * Headline shown to the customer.
	 *
	 * @return string
	 */
	public function headline() {
		$headline = (string) $this->meta( 'headline', '' );

		if ( $headline ) {
			return $headline;
		}

		return sprintf(
			/* translators: 1: number to buy, 2: number free */
			__( 'Buy %1$d get %2$d free', 'sreesaanvika-offers' ),
			$this->buy(),
			$this->get()
		);
	}

	/**
	 * The line under the headline.
	 *
	 * @return string
	 */
	public function subline() {
		return (string) $this->meta( 'subline', '' );
	}

	/**
	 * When it stops, as a timestamp, or 0 for no end.
	 *
	 * @return int
	 */
	public function ends_at() {
		$ends = (string) $this->meta( 'ends', '' );

		return $ends ? (int) strtotime( $ends . ' ' . wp_timezone_string() ) : 0;
	}

	/**
	 * When it starts, as a timestamp, or 0 for already started.
	 *
	 * @return int
	 */
	public function starts_at() {
		$starts = (string) $this->meta( 'starts', '' );

		return $starts ? (int) strtotime( $starts . ' ' . wp_timezone_string() ) : 0;
	}

	/**
	 * Show a countdown to the end?
	 *
	 * @return bool
	 */
	public function shows_countdown() {
		return 'yes' === $this->meta( 'countdown', 'no' ) && $this->ends_at() > time();
	}

	/**
	 * Is the offer on right now?
	 *
	 * @return bool
	 */
	public function is_running() {
		if ( ! $this->post || 'publish' !== $this->post->post_status ) {
			return false;
		}

		$now    = time();
		$starts = $this->starts_at();
		$ends   = $this->ends_at();

		if ( $starts && $now < $starts ) {
			return false;
		}

		if ( $ends && $now > $ends ) {
			return false;
		}

		if ( ! $this->product_ids() && ! $this->category_ids() ) {
			return false;
		}

		return true;
	}

	/**
	 * Does this offer cover a given product?
	 *
	 * A variation is judged by its parent, since the offer is picked against
	 * products rather than variations.
	 *
	 * @param int $product_id Product or variation id.
	 * @return bool
	 */
	public function covers( $product_id ) {
		$product_id = absint( $product_id );

		if ( ! $product_id ) {
			return false;
		}

		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return false;
		}

		$id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();

		if ( in_array( $id, $this->excluded_ids(), true ) ) {
			return false;
		}

		if ( in_array( $id, $this->product_ids(), true ) ) {
			return true;
		}

		$categories = $this->category_ids();

		if ( $categories && has_term( $categories, 'product_cat', $id ) ) {
			return true;
		}

		return false;
	}
}
