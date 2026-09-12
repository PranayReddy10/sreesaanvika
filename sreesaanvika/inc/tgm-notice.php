<?php
/**
 * Theme welcome screen and the WooCommerce reminder notice.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the welcome page under Appearance.
 */
function ss_admin_menu() {
	add_theme_page(
		__( 'Sree Saanvika', 'sreesaanvika' ),
		__( 'Sree Saanvika', 'sreesaanvika' ),
		'edit_theme_options',
		'sreesaanvika',
		'ss_welcome_screen'
	);
}
add_action( 'admin_menu', 'ss_admin_menu' );

/**
 * Nudge the shop owner to install WooCommerce and run setup.
 */
function ss_admin_notices() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$screen = get_current_screen();

	if ( $screen && 'appearance_page_sreesaanvika' === $screen->id ) {
		return;
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		$install = wp_nonce_url(
			self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ),
			'install-plugin_woocommerce'
		);
		?>
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'Sree Saanvika needs WooCommerce.', 'sreesaanvika' ); ?></strong>
				<?php esc_html_e( 'The shop, cart, product pages, compare and wishlist all depend on it.', 'sreesaanvika' ); ?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( $install ); ?>">
					<?php esc_html_e( 'Install WooCommerce', 'sreesaanvika' ); ?>
				</a>
			</p>
		</div>
		<?php
		return;
	}

	if ( ! get_option( 'ss_setup_complete' ) ) {
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Almost there.', 'sreesaanvika' ); ?></strong>
				<?php esc_html_e( 'Run the one-click setup to create the Compare, Wishlist, Sign In, Lookbook, About, Contact, FAQ and Track Order pages and build your main menu.', 'sreesaanvika' ); ?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'themes.php?page=sreesaanvika' ) ); ?>">
					<?php esc_html_e( 'Open theme setup', 'sreesaanvika' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'ss_admin_notices' );

/**
 * The welcome screen.
 */
function ss_welcome_screen() {
	$done = get_transient( 'ss_setup_done' );

	if ( $done ) {
		delete_transient( 'ss_setup_done' );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Sree Saanvika', 'sreesaanvika' ); ?>
			<span style="font-size:13px;font-weight:400;color:#666">v<?php echo esc_html( SS_VERSION ); ?></span>
		</h1>

		<p style="font-size:14px;max-width:760px">
			<?php esc_html_e( 'A dark-luxe WooCommerce theme for Indian women\'s fashion — handloom sarees, temple jewellery and festive dresses. Follow the three steps below and your storefront is live.', 'sreesaanvika' ); ?>
		</p>

		<?php
		$el = get_transient( 'ss_el_result' );

		if ( $el ) {
			delete_transient( 'ss_el_result' );
		}
		?>

		<?php if ( $el && ! empty( $el['error'] ) ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $el['error'] ); ?></p></div>
		<?php elseif ( $el && ! empty( $el['page_id'] ) ) : ?>
			<div class="notice notice-success">
				<p>
					<?php
					printf(
						/* translators: %d: number of sections */
						esc_html__( 'Built an Elementor copy of the homepage with %d sections.', 'sreesaanvika' ),
						absint( $el['sections'] )
					);

					if ( ! empty( $el['made_home'] ) ) {
						echo ' ' . esc_html__( 'It is now your site homepage.', 'sreesaanvika' );
					}
					?>
				</p>
				<p>
					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $el['page_id'] ) . '&action=elementor' ) ); ?>">
						<?php esc_html_e( 'Edit it with Elementor', 'sreesaanvika' ); ?>
					</a>
					<a class="button" href="<?php echo esc_url( get_permalink( $el['page_id'] ) ); ?>" target="_blank" rel="noopener">
						<?php esc_html_e( 'View the page', 'sreesaanvika' ); ?>
					</a>
					<?php if ( empty( $el['made_home'] ) ) : ?>
						<a class="button" href="<?php echo esc_url( admin_url( 'options-reading.php' ) ); ?>">
							<?php esc_html_e( 'Set it as the homepage', 'sreesaanvika' ); ?>
						</a>
					<?php endif; ?>
				</p>
			</div>
		<?php endif; ?>

		<?php if ( $done ) : ?>
			<div class="notice notice-success">
				<p>
					<?php
					if ( ! empty( $done['pages'] ) ) {
						printf(
							/* translators: %s: comma separated page names */
							esc_html__( 'Created these pages: %s.', 'sreesaanvika' ),
							esc_html( implode( ', ', $done['pages'] ) )
						);
					} else {
						esc_html_e( 'All theme pages already existed — nothing was overwritten.', 'sreesaanvika' );
					}

					if ( ! empty( $done['menu'] ) ) {
						echo ' ' . esc_html__( 'A primary menu was created and assigned.', 'sreesaanvika' );
					}
					?>
				</p>
			</div>
		<?php endif; ?>

		<div class="card" style="max-width:820px;padding:8px 22px 22px">
			<h2><?php esc_html_e( 'Step 1 — WooCommerce', 'sreesaanvika' ); ?></h2>

			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<p style="color:#1a7f5a">✓ <?php esc_html_e( 'WooCommerce is active.', 'sreesaanvika' ); ?></p>
			<?php else : ?>
				<p><?php esc_html_e( 'The shop needs WooCommerce. Install and activate it, then come back here.', 'sreesaanvika' ); ?></p>
				<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' ) ); ?>">
					<?php esc_html_e( 'Install WooCommerce', 'sreesaanvika' ); ?>
				</a>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Step 2 — Create the theme pages and menu', 'sreesaanvika' ); ?></h2>
			<p>
				<?php esc_html_e( 'This creates Compare, Wishlist, Sign In, Lookbook, Our Story, Contact, FAQs and Track Your Order, then builds a primary menu from your product categories. It never overwrites a page or menu you already have.', 'sreesaanvika' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ss_run_setup" />
				<?php wp_nonce_field( 'ss_setup' ); ?>
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Run one-click setup', 'sreesaanvika' ); ?>
				</button>
			</form>

			<h2><?php esc_html_e( 'Step 3 — Make it yours', 'sreesaanvika' ); ?></h2>
			<p><?php esc_html_e( 'Every colour, homepage section, hero slide and shop behaviour lives in the Customizer under “Sree Saanvika Options”.', 'sreesaanvika' ); ?></p>
			<a class="button" href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>">
				<?php esc_html_e( 'Open the Customizer', 'sreesaanvika' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">
				<?php esc_html_e( 'Edit menus', 'sreesaanvika' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'widgets.php' ) ); ?>">
				<?php esc_html_e( 'Edit widgets', 'sreesaanvika' ); ?>
			</a>
		</div>

		<div class="card" style="max-width:820px;padding:8px 22px 22px;margin-top:20px">
			<h2><?php esc_html_e( 'Editing the homepage', 'sreesaanvika' ); ?></h2>

			<p>
				<strong><?php esc_html_e( 'Option A — keep the theme homepage.', 'sreesaanvika' ); ?></strong>
				<?php esc_html_e( 'Everything on it is edited in the Customizer: hero slides, offer banners, the story band, which sections show and in what order. Nothing to install, and it stays fast.', 'sreesaanvika' ); ?>
			</p>

			<p>
				<a class="button" href="<?php echo esc_url( admin_url( 'customize.php?autofocus[panel]=ss_panel' ) ); ?>">
					<?php esc_html_e( 'Edit the homepage in the Customizer', 'sreesaanvika' ); ?>
				</a>
			</p>

			<p>
				<strong><?php esc_html_e( 'Option B — rebuild it in Elementor.', 'sreesaanvika' ); ?></strong>
				<?php esc_html_e( 'This creates a real Elementor page holding the same sections, seeded with your current settings, so you can drag, drop and restyle them visually. Every section is available as a widget under the "Sree Saanvika" category.', 'sreesaanvika' ); ?>
			</p>

			<?php if ( ! ss_has_elementor() ) : ?>
				<p><em><?php esc_html_e( 'Install and activate Elementor to use this.', 'sreesaanvika' ); ?></em></p>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ss_build_elementor_home" />
					<?php wp_nonce_field( 'ss_elementor_home' ); ?>

					<p>
						<label>
							<input type="checkbox" name="ss_set_home" value="1" checked />
							<?php esc_html_e( 'Also set the new page as the site homepage', 'sreesaanvika' ); ?>
						</label>
					</p>

					<p style="color:#666;font-size:12px;margin-top:-6px">
						<?php esc_html_e( 'A new page is always created — your current homepage is never overwritten. Untick the box to review it first, or revert any time under Settings → Reading.', 'sreesaanvika' ); ?>
					</p>

					<p>
						<button type="submit" class="button button-primary">
							<?php esc_html_e( 'Build an Elementor copy of the homepage', 'sreesaanvika' ); ?>
						</button>
					</p>
				</form>
			<?php endif; ?>
		</div>

		<div class="card" style="max-width:820px;padding:8px 22px 22px;margin-top:20px">
			<h2><?php esc_html_e( 'Good to know', 'sreesaanvika' ); ?></h2>
			<ul style="list-style:disc;padding-left:20px">
				<li><?php esc_html_e( 'Add "mega" as a CSS class on a top-level menu item to turn its dropdown into a four-column mega menu. "hot" and "new" add a small flag.', 'sreesaanvika' ); ?></li>
				<li><?php esc_html_e( 'Colour swatches read a product\'s Color / Colour attribute. Add a term meta named ss_color with a hex value to set an exact shade.', 'sreesaanvika' ); ?></li>
				<li><?php esc_html_e( 'Product images look best as portrait 3:4 — 1200 × 1600 pixels or larger, so the zoom stays sharp.', 'sreesaanvika' ); ?></li>
				<li><?php esc_html_e( 'Set a product category image under Products → Categories to fill the homepage mosaic and the round category rail.', 'sreesaanvika' ); ?></li>
				<li><?php esc_html_e( 'Homepage sections can each be switched off in the Customizer under Homepage — Sections.', 'sreesaanvika' ); ?></li>
			</ul>
		</div>
	</div>
	<?php
}
