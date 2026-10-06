<?php
/**
 * Theme welcome screen and the WooCommerce reminder notice.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the welcome page under Appearance.
 */
function od_admin_menu() {
	add_theme_page(
		__( 'OJASVI', 'ojasvidrapes' ),
		__( 'OJASVI', 'ojasvidrapes' ),
		'edit_theme_options',
		'ojasvidrapes',
		'od_welcome_screen'
	);
}
add_action( 'admin_menu', 'od_admin_menu' );

/**
 * Nudge the shop owner to install WooCommerce and run setup.
 */
function od_admin_notices() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$screen = get_current_screen();

	if ( $screen && 'appearance_page_ojasvidrapes' === $screen->id ) {
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
				<strong><?php esc_html_e( 'OJASVI needs WooCommerce.', 'ojasvidrapes' ); ?></strong>
				<?php esc_html_e( 'The shop, cart, product pages, compare and wishlist all depend on it.', 'ojasvidrapes' ); ?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( $install ); ?>">
					<?php esc_html_e( 'Install WooCommerce', 'ojasvidrapes' ); ?>
				</a>
			</p>
		</div>
		<?php
		return;
	}

	if ( ! get_option( 'od_setup_complete' ) ) {
		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<strong><?php esc_html_e( 'Almost there.', 'ojasvidrapes' ); ?></strong>
				<?php esc_html_e( 'Run the one-click setup to create the Compare, Wishlist, Sign In, Lookbook, About, Contact, FAQ and Track Order pages and build your main menu.', 'ojasvidrapes' ); ?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'themes.php?page=ojasvidrapes' ) ); ?>">
					<?php esc_html_e( 'Open theme setup', 'ojasvidrapes' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}
add_action( 'admin_notices', 'od_admin_notices' );

/**
 * The welcome screen.
 */
function od_welcome_screen() {
	$done = get_transient( 'od_setup_done' );

	if ( $done ) {
		delete_transient( 'od_setup_done' );
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'OJASVI', 'ojasvidrapes' ); ?>
			<span style="font-size:13px;font-weight:400;color:#666">v<?php echo esc_html( OD_VERSION ); ?></span>
		</h1>

		<p style="font-size:14px;max-width:760px">
			<?php esc_html_e( 'A dark-luxe WooCommerce theme for a handloom saree house. Follow the three steps below and your storefront is live.', 'ojasvidrapes' ); ?>
		</p>

		<?php
		$od_mode_result = get_transient( 'od_woo_mode_result' );

		if ( $od_mode_result ) {
			delete_transient( 'od_woo_mode_result' );
		}
		?>

		<?php if ( $od_mode_result ) : ?>
			<div class="notice notice-success">
				<p>
					<?php
					if ( empty( $od_mode_result['changed'] ) ) {
						esc_html_e( 'Both pages were already set that way — nothing changed.', 'ojasvidrapes' );
					} elseif ( 'shortcode' === $od_mode_result['mode'] ) {
						printf(
							/* translators: %s: comma separated page names */
							esc_html__( 'Switched %s to the theme\'s own cart and checkout.', 'ojasvidrapes' ),
							esc_html( implode( ', ', $od_mode_result['changed'] ) )
						);
					} else {
						printf(
							/* translators: %s: comma separated page names */
							esc_html__( 'Restored the WooCommerce blocks on %s.', 'ojasvidrapes' ),
							esc_html( implode( ', ', $od_mode_result['changed'] ) )
						);
					}
					?>
				</p>
			</div>
		<?php endif; ?>

		<?php
		$el = get_transient( 'od_el_result' );

		if ( $el ) {
			delete_transient( 'od_el_result' );
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
						esc_html__( 'Built an Elementor copy of the homepage with %d sections.', 'ojasvidrapes' ),
						absint( $el['sections'] )
					);

					if ( ! empty( $el['made_home'] ) ) {
						echo ' ' . esc_html__( 'It is now your site homepage.', 'ojasvidrapes' );
					}
					?>
				</p>
				<p>
					<a class="button button-primary" href="<?php echo esc_url( admin_url( 'post.php?post=' . absint( $el['page_id'] ) . '&action=elementor' ) ); ?>">
						<?php esc_html_e( 'Edit it with Elementor', 'ojasvidrapes' ); ?>
					</a>
					<a class="button" href="<?php echo esc_url( get_permalink( $el['page_id'] ) ); ?>" target="_blank" rel="noopener">
						<?php esc_html_e( 'View the page', 'ojasvidrapes' ); ?>
					</a>
					<?php if ( empty( $el['made_home'] ) ) : ?>
						<a class="button" href="<?php echo esc_url( admin_url( 'options-reading.php' ) ); ?>">
							<?php esc_html_e( 'Set it as the homepage', 'ojasvidrapes' ); ?>
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
							esc_html__( 'Created these pages: %s.', 'ojasvidrapes' ),
							esc_html( implode( ', ', $done['pages'] ) )
						);
					} else {
						esc_html_e( 'All theme pages already existed — nothing was overwritten.', 'ojasvidrapes' );
					}

					if ( ! empty( $done['menu'] ) ) {
						echo ' ' . esc_html__( 'A primary menu was created and assigned.', 'ojasvidrapes' );
					}
					?>
				</p>
			</div>
		<?php endif; ?>

		<?php
		$od_repair = get_transient( 'od_repair_done' );

		if ( $od_repair ) {
			delete_transient( 'od_repair_done' );
		}
		?>

		<?php if ( $od_repair ) : ?>
			<div class="notice notice-success">
				<p>
					<?php
					if ( ! empty( $od_repair['fixed'] ) ) {
						printf(
							/* translators: %s: comma separated page names */
							esc_html__( 'Put these pages back on their theme template: %s. Reload them to see the theme design.', 'ojasvidrapes' ),
							esc_html( implode( ', ', $od_repair['fixed'] ) )
						);
					} else {
						esc_html_e( 'Every theme page was already on the right template.', 'ojasvidrapes' );
					}

					if ( ! empty( $od_repair['missing'] ) ) {
						echo ' ';
						printf(
							/* translators: %s: comma separated page names */
							esc_html__( 'Not found at all, so nothing to repair: %s — run the one-click setup to create them.', 'ojasvidrapes' ),
							esc_html( implode( ', ', $od_repair['missing'] ) )
						);
					}
					?>
				</p>
			</div>
		<?php endif; ?>

		<div class="card" style="max-width:820px;padding:8px 22px 22px">
			<h2><?php esc_html_e( 'Step 1 — WooCommerce', 'ojasvidrapes' ); ?></h2>

			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<p style="color:#1a7f5a">✓ <?php esc_html_e( 'WooCommerce is active.', 'ojasvidrapes' ); ?></p>
			<?php else : ?>
				<p><?php esc_html_e( 'The shop needs WooCommerce. Install and activate it, then come back here.', 'ojasvidrapes' ); ?></p>
				<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' ) ); ?>">
					<?php esc_html_e( 'Install WooCommerce', 'ojasvidrapes' ); ?>
				</a>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Step 2 — Create the theme pages and menu', 'ojasvidrapes' ); ?></h2>
			<p>
				<?php esc_html_e( 'This creates Compare, Wishlist, Sign In, Lookbook, Our Story, Contact, FAQs, Track Your Order, and the four policy pages — Privacy, Terms, Shipping and Returns — with a full draft of each written in. It never overwrites a page or menu you already have.', 'ojasvidrapes' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="od_run_setup" />
				<?php wp_nonce_field( 'od_setup' ); ?>
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Run one-click setup', 'ojasvidrapes' ); ?>
				</button>
			</form>

			<h2><?php esc_html_e( 'Page looks plain? Repair its template', 'ojasvidrapes' ); ?></h2>
			<p>
				<?php esc_html_e( 'If Our Story, Contact, Track Your Order or a policy page renders as a plain page with none of the theme design, it is on WordPress\'s default template rather than the theme\'s — usually because the page already existed, or an importer or page builder reset it. This finds each one by its address or title and puts it back on the theme template. It never changes what you have written.', 'ojasvidrapes' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="od_repair_templates" />
				<?php wp_nonce_field( 'od_repair' ); ?>
				<button type="submit" class="button">
					<?php esc_html_e( 'Repair page templates', 'ojasvidrapes' ); ?>
				</button>
			</form>

			<h2><?php esc_html_e( 'Step 3 — Make it yours', 'ojasvidrapes' ); ?></h2>
			<p><?php esc_html_e( 'Every colour, homepage section, hero slide and shop behaviour lives in the Customizer under “OJASVI Options”.', 'ojasvidrapes' ); ?></p>
			<a class="button" href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>">
				<?php esc_html_e( 'Open the Customizer', 'ojasvidrapes' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">
				<?php esc_html_e( 'Edit menus', 'ojasvidrapes' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'widgets.php' ) ); ?>">
				<?php esc_html_e( 'Edit widgets', 'ojasvidrapes' ); ?>
			</a>
		</div>

		<?php if ( class_exists( 'WooCommerce' ) && function_exists( 'od_woo_page_modes' ) ) : ?>
			<?php
			$od_modes  = od_woo_page_modes();
			$od_blocks = in_array( 'block', $od_modes, true );
			?>
			<div class="card" style="max-width:820px;padding:8px 22px 22px;margin-top:20px">
				<h2><?php esc_html_e( 'Cart & Checkout style', 'ojasvidrapes' ); ?></h2>

				<p>
					<?php esc_html_e( 'WooCommerce builds these two pages out of blocks by default. Blocks do not use the theme\'s cart and checkout designs, so you miss the free-shipping meter, the savings line and the three-step indicator.', 'ojasvidrapes' ); ?>
				</p>

				<p>
					<?php
					printf(
						/* translators: 1: cart mode, 2: checkout mode */
						esc_html__( 'Right now — Cart: %1$s · Checkout: %2$s', 'ojasvidrapes' ),
						'<strong>' . esc_html( isset( $od_modes['cart'] ) ? $od_modes['cart'] : '—' ) . '</strong>',
						'<strong>' . esc_html( isset( $od_modes['checkout'] ) ? $od_modes['checkout'] : '—' ) . '</strong>'
					);
					?>
				</p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="od_switch_woo_pages" />
					<input type="hidden" name="od_mode" value="<?php echo $od_blocks ? 'shortcode' : 'block'; ?>" />
					<?php wp_nonce_field( 'od_woo_page_mode' ); ?>

					<?php if ( $od_blocks ) : ?>
						<p>
							<button type="submit" class="button button-primary">
								<?php esc_html_e( 'Use the theme\'s cart & checkout', 'ojasvidrapes' ); ?>
							</button>
						</p>
						<p style="color:#666;font-size:12px;margin-top:-6px">
							<?php esc_html_e( 'Replaces the block with the WooCommerce shortcode on both pages. The block markup is saved first, so you can switch back from this same screen.', 'ojasvidrapes' ); ?>
						</p>
					<?php else : ?>
						<p style="color:#1a7f5a">
							✓ <?php esc_html_e( 'Both pages use the theme\'s own cart and checkout.', 'ojasvidrapes' ); ?>
						</p>
						<p>
							<button type="submit" class="button">
								<?php esc_html_e( 'Switch back to WooCommerce blocks', 'ojasvidrapes' ); ?>
							</button>
						</p>
					<?php endif; ?>
				</form>
			</div>
		<?php endif; ?>

		<div class="card" style="max-width:820px;padding:8px 22px 22px;margin-top:20px">
			<h2><?php esc_html_e( 'Delivery tracking', 'ojasvidrapes' ); ?></h2>

			<?php if ( class_exists( 'ODD_Shipment' ) ) : ?>
				<p style="color:#1a7f5a">✓ <?php esc_html_e( 'OJASVI Delivery is active.', 'ojasvidrapes' ); ?></p>
				<p>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=odd-settings' ) ); ?>">
						<?php esc_html_e( 'Delivery settings', 'ojasvidrapes' ); ?>
					</a>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=odd-import' ) ); ?>">
						<?php esc_html_e( 'Import tracking numbers', 'ojasvidrapes' ); ?>
					</a>
				</p>
			<?php else : ?>
				<p>
					<?php esc_html_e( 'The theme ships with a companion plugin, OJASVI Delivery. It records the courier\'s tracking number and delivery status on each order, shows the shopper a progress line on the order page and under Track Your Order, and takes status pushes straight from your delivery app.', 'ojasvidrapes' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Install ojasvidrapes-delivery.zip under Plugins → Add New → Upload Plugin.', 'ojasvidrapes' ); ?>
				</p>
				<a class="button" href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=upload' ) ); ?>">
					<?php esc_html_e( 'Upload the plugin', 'ojasvidrapes' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<div class="card" style="max-width:820px;padding:8px 22px 22px;margin-top:20px">
			<h2><?php esc_html_e( 'Offers without a promo code', 'ojasvidrapes' ); ?></h2>

			<?php if ( class_exists( 'ODO_Offer' ) ) : ?>
				<p style="color:#1a7f5a">✓ <?php esc_html_e( 'OJASVI Offers is active.', 'ojasvidrapes' ); ?></p>
				<p>
					<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=od_offer' ) ); ?>">
						<?php esc_html_e( 'Your offers', 'ojasvidrapes' ); ?>
					</a>
					<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=od_offer' ) ); ?>">
						<?php esc_html_e( 'Add an offer', 'ojasvidrapes' ); ?>
					</a>
				</p>
			<?php else : ?>
				<p>
					<?php esc_html_e( 'OJASVI Offers runs Buy 2 Get 1 Free and offers like it with no code to type. You pick the pieces; when a shopper has enough of them in the cart the cheapest one goes free by itself. Several offers can run at once and are counted separately.', 'ojasvidrapes' ); ?>
				</p>
				<p><?php esc_html_e( 'Install ojasvidrapes-offers.zip under Plugins → Add New → Upload Plugin.', 'ojasvidrapes' ); ?></p>
				<a class="button" href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=upload' ) ); ?>">
					<?php esc_html_e( 'Upload the plugin', 'ojasvidrapes' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<div class="card" style="max-width:820px;padding:8px 22px 22px;margin-top:20px">
			<h2><?php esc_html_e( 'Editing the homepage', 'ojasvidrapes' ); ?></h2>

			<p>
				<strong><?php esc_html_e( 'Option A — keep the theme homepage.', 'ojasvidrapes' ); ?></strong>
				<?php esc_html_e( 'Everything on it is edited in the Customizer: hero slides, offer banners, the story band, which sections show and in what order. Nothing to install, and it stays fast.', 'ojasvidrapes' ); ?>
			</p>

			<p>
				<a class="button" href="<?php echo esc_url( admin_url( 'customize.php?autofocus[panel]=od_panel' ) ); ?>">
					<?php esc_html_e( 'Edit the homepage in the Customizer', 'ojasvidrapes' ); ?>
				</a>
			</p>

			<p>
				<strong><?php esc_html_e( 'Option B — rebuild it in Elementor.', 'ojasvidrapes' ); ?></strong>
				<?php esc_html_e( 'This creates a real Elementor page holding the same sections, seeded with your current settings, so you can drag, drop and restyle them visually. Every section is available as a widget under the "OJASVI" category.', 'ojasvidrapes' ); ?>
			</p>

			<?php if ( ! od_has_elementor() ) : ?>
				<p><em><?php esc_html_e( 'Install and activate Elementor to use this.', 'ojasvidrapes' ); ?></em></p>
			<?php else : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="od_build_elementor_home" />
					<?php wp_nonce_field( 'od_elementor_home' ); ?>

					<p>
						<label>
							<input type="checkbox" name="od_set_home" value="1" checked />
							<?php esc_html_e( 'Also set the new page as the site homepage', 'ojasvidrapes' ); ?>
						</label>
					</p>

					<p style="color:#666;font-size:12px;margin-top:-6px">
						<?php esc_html_e( 'A new page is always created — your current homepage is never overwritten. Untick the box to review it first, or revert any time under Settings → Reading.', 'ojasvidrapes' ); ?>
					</p>

					<p>
						<button type="submit" class="button button-primary">
							<?php esc_html_e( 'Build an Elementor copy of the homepage', 'ojasvidrapes' ); ?>
						</button>
					</p>
				</form>
			<?php endif; ?>
		</div>

		<div class="card" style="max-width:820px;padding:8px 22px 22px;margin-top:20px">
			<h2><?php esc_html_e( 'Policy pages — read before you launch', 'ojasvidrapes' ); ?></h2>

			<p>
				<?php esc_html_e( 'Privacy, Terms, Shipping and Returns each ship with a full draft written for an Indian direct-to-consumer store. The return window, shipping threshold, COD limit, business name, GSTIN, jurisdiction and grievance officer are pulled from Customizer → Policies & Legal, so changing a figure there is enough — you do not have to hunt through the text.', 'ojasvidrapes' ); ?>
			</p>

			<p style="padding:12px 16px;background:#fff8e5;border-left:4px solid #dba617">
				<strong><?php esc_html_e( 'These are drafts, not legal advice.', 'ojasvidrapes' ); ?></strong>
				<?php esc_html_e( 'They are a solid starting point, but they have not been reviewed by a lawyer and they cannot know the specifics of your business. Have someone qualified read them before you take real orders — particularly the liability, jurisdiction and grievance sections.', 'ojasvidrapes' ); ?>
			</p>

			<p>
				<a class="button" href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=od_policy' ) ); ?>">
					<?php esc_html_e( 'Set the policy figures', 'ojasvidrapes' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=od_seo' ) ); ?>">
					<?php esc_html_e( 'SEO & social settings', 'ojasvidrapes' ); ?>
				</a>
			</p>
		</div>

		<div class="card" style="max-width:820px;padding:8px 22px 22px;margin-top:20px">
			<h2><?php esc_html_e( 'Good to know', 'ojasvidrapes' ); ?></h2>
			<ul style="list-style:disc;padding-left:20px">
				<li><?php esc_html_e( 'Add "mega" as a CSS class on a top-level menu item to turn its dropdown into a four-column mega menu. "hot" and "new" add a small flag.', 'ojasvidrapes' ); ?></li>
				<li><?php esc_html_e( 'Colour swatches read a product\'s Color / Colour attribute. Add a term meta named od_color with a hex value to set an exact shade.', 'ojasvidrapes' ); ?></li>
				<li><?php esc_html_e( 'Product images look best as portrait 3:4 — 1200 × 1600 pixels or larger, so the zoom stays sharp.', 'ojasvidrapes' ); ?></li>
				<li><?php esc_html_e( 'Set a product category image under Products → Categories to fill the homepage mosaic and the round category rail.', 'ojasvidrapes' ); ?></li>
				<li><?php esc_html_e( 'Homepage sections can each be switched off in the Customizer under Homepage — Sections.', 'ojasvidrapes' ); ?></li>
				<li><?php esc_html_e( 'The theme writes its own meta tags, Open Graph and schema.org data — and steps aside automatically if you install Yoast or Rank Math, so nothing is ever duplicated.', 'ojasvidrapes' ); ?></li>
				<li><?php esc_html_e( 'Cart, checkout, account, compare, wishlist and sign-in are set to noindex and kept out of the sitemap. That happens with or without an SEO plugin.', 'ojasvidrapes' ); ?></li>
			</ul>
		</div>
	</div>
	<?php
}
