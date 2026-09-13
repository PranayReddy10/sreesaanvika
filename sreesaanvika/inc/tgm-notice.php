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
		$ss_mode_result = get_transient( 'ss_woo_mode_result' );

		if ( $ss_mode_result ) {
			delete_transient( 'ss_woo_mode_result' );
		}
		?>

		<?php if ( $ss_mode_result ) : ?>
			<div class="notice notice-success">
				<p>
					<?php
					if ( empty( $ss_mode_result['changed'] ) ) {
						esc_html_e( 'Both pages were already set that way — nothing changed.', 'sreesaanvika' );
					} elseif ( 'shortcode' === $ss_mode_result['mode'] ) {
						printf(
							/* translators: %s: comma separated page names */
							esc_html__( 'Switched %s to the theme\'s own cart and checkout.', 'sreesaanvika' ),
							esc_html( implode( ', ', $ss_mode_result['changed'] ) )
						);
					} else {
						printf(
							/* translators: %s: comma separated page names */
							esc_html__( 'Restored the WooCommerce blocks on %s.', 'sreesaanvika' ),
							esc_html( implode( ', ', $ss_mode_result['changed'] ) )
						);
					}
					?>
				</p>
			</div>
		<?php endif; ?>

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

		<?php
		$ss_repair = get_transient( 'ss_repair_done' );

		if ( $ss_repair ) {
			delete_transient( 'ss_repair_done' );
		}
		?>

		<?php if ( $ss_repair ) : ?>
			<div class="notice notice-success">
				<p>
					<?php
					if ( ! empty( $ss_repair['fixed'] ) ) {
						printf(
							/* translators: %s: comma separated page names */
							esc_html__( 'Put these pages back on their theme template: %s. Reload them to see the theme design.', 'sreesaanvika' ),
							esc_html( implode( ', ', $ss_repair['fixed'] ) )
						);
					} else {
						esc_html_e( 'Every theme page was already on the right template.', 'sreesaanvika' );
					}

					if ( ! empty( $ss_repair['missing'] ) ) {
						echo ' ';
						printf(
							/* translators: %s: comma separated page names */
							esc_html__( 'Not found at all, so nothing to repair: %s — run the one-click setup to create them.', 'sreesaanvika' ),
							esc_html( implode( ', ', $ss_repair['missing'] ) )
						);
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
				<?php esc_html_e( 'This creates Compare, Wishlist, Sign In, Lookbook, Our Story, Contact, FAQs, Track Your Order, and the four policy pages — Privacy, Terms, Shipping and Returns — with a full draft of each written in. It never overwrites a page or menu you already have.', 'sreesaanvika' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ss_run_setup" />
				<?php wp_nonce_field( 'ss_setup' ); ?>
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Run one-click setup', 'sreesaanvika' ); ?>
				</button>
			</form>

			<h2><?php esc_html_e( 'Page looks plain? Repair its template', 'sreesaanvika' ); ?></h2>
			<p>
				<?php esc_html_e( 'If Our Story, Contact, Track Your Order or a policy page renders as a plain page with none of the theme design, it is on WordPress\'s default template rather than the theme\'s — usually because the page already existed, or an importer or page builder reset it. This finds each one by its address or title and puts it back on the theme template. It never changes what you have written.', 'sreesaanvika' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ss_repair_templates" />
				<?php wp_nonce_field( 'ss_repair' ); ?>
				<button type="submit" class="button">
					<?php esc_html_e( 'Repair page templates', 'sreesaanvika' ); ?>
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

		<?php if ( class_exists( 'WooCommerce' ) && function_exists( 'ss_woo_page_modes' ) ) : ?>
			<?php
			$ss_modes  = ss_woo_page_modes();
			$ss_blocks = in_array( 'block', $ss_modes, true );
			?>
			<div class="card" style="max-width:820px;padding:8px 22px 22px;margin-top:20px">
				<h2><?php esc_html_e( 'Cart & Checkout style', 'sreesaanvika' ); ?></h2>

				<p>
					<?php esc_html_e( 'WooCommerce builds these two pages out of blocks by default. Blocks do not use the theme\'s cart and checkout designs, so you miss the free-shipping meter, the savings line and the three-step indicator.', 'sreesaanvika' ); ?>
				</p>

				<p>
					<?php
					printf(
						/* translators: 1: cart mode, 2: checkout mode */
						esc_html__( 'Right now — Cart: %1$s · Checkout: %2$s', 'sreesaanvika' ),
						'<strong>' . esc_html( isset( $ss_modes['cart'] ) ? $ss_modes['cart'] : '—' ) . '</strong>',
						'<strong>' . esc_html( isset( $ss_modes['checkout'] ) ? $ss_modes['checkout'] : '—' ) . '</strong>'
					);
					?>
				</p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ss_switch_woo_pages" />
					<input type="hidden" name="ss_mode" value="<?php echo $ss_blocks ? 'shortcode' : 'block'; ?>" />
					<?php wp_nonce_field( 'ss_woo_page_mode' ); ?>

					<?php if ( $ss_blocks ) : ?>
						<p>
							<button type="submit" class="button button-primary">
								<?php esc_html_e( 'Use the theme\'s cart & checkout', 'sreesaanvika' ); ?>
							</button>
						</p>
						<p style="color:#666;font-size:12px;margin-top:-6px">
							<?php esc_html_e( 'Replaces the block with the WooCommerce shortcode on both pages. The block markup is saved first, so you can switch back from this same screen.', 'sreesaanvika' ); ?>
						</p>
					<?php else : ?>
						<p style="color:#1a7f5a">
							✓ <?php esc_html_e( 'Both pages use the theme\'s own cart and checkout.', 'sreesaanvika' ); ?>
						</p>
						<p>
							<button type="submit" class="button">
								<?php esc_html_e( 'Switch back to WooCommerce blocks', 'sreesaanvika' ); ?>
							</button>
						</p>
					<?php endif; ?>
				</form>
			</div>
		<?php endif; ?>

		<div class="card" style="max-width:820px;padding:8px 22px 22px;margin-top:20px">
			<h2><?php esc_html_e( 'Delivery tracking', 'sreesaanvika' ); ?></h2>

			<?php if ( class_exists( 'SSD_Shipment' ) ) : ?>
				<p style="color:#1a7f5a">✓ <?php esc_html_e( 'Sree Saanvika Delivery is active.', 'sreesaanvika' ); ?></p>
				<p>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ssd-settings' ) ); ?>">
						<?php esc_html_e( 'Delivery settings', 'sreesaanvika' ); ?>
					</a>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=ssd-import' ) ); ?>">
						<?php esc_html_e( 'Import tracking numbers', 'sreesaanvika' ); ?>
					</a>
				</p>
			<?php else : ?>
				<p>
					<?php esc_html_e( 'The theme ships with a companion plugin, Sree Saanvika Delivery. It records the courier\'s tracking number and delivery status on each order, shows the shopper a progress line on the order page and under Track Your Order, and takes status pushes straight from your delivery app.', 'sreesaanvika' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Install sreesaanvika-delivery.zip under Plugins → Add New → Upload Plugin.', 'sreesaanvika' ); ?>
				</p>
				<a class="button" href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=upload' ) ); ?>">
					<?php esc_html_e( 'Upload the plugin', 'sreesaanvika' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<div class="card" style="max-width:820px;padding:8px 22px 22px;margin-top:20px">
			<h2><?php esc_html_e( 'Offers without a promo code', 'sreesaanvika' ); ?></h2>

			<?php if ( class_exists( 'SSO_Offer' ) ) : ?>
				<p style="color:#1a7f5a">✓ <?php esc_html_e( 'Sree Saanvika Offers is active.', 'sreesaanvika' ); ?></p>
				<p>
					<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=ss_offer' ) ); ?>">
						<?php esc_html_e( 'Your offers', 'sreesaanvika' ); ?>
					</a>
					<a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=ss_offer' ) ); ?>">
						<?php esc_html_e( 'Add an offer', 'sreesaanvika' ); ?>
					</a>
				</p>
			<?php else : ?>
				<p>
					<?php esc_html_e( 'Sree Saanvika Offers runs Buy 2 Get 1 Free and offers like it with no code to type. You pick the products; when a shopper has enough of them in the cart the cheapest one goes free by itself. Run one for sarees and another for jewellery — they are counted separately.', 'sreesaanvika' ); ?>
				</p>
				<p><?php esc_html_e( 'Install sreesaanvika-offers.zip under Plugins → Add New → Upload Plugin.', 'sreesaanvika' ); ?></p>
				<a class="button" href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=upload' ) ); ?>">
					<?php esc_html_e( 'Upload the plugin', 'sreesaanvika' ); ?>
				</a>
			<?php endif; ?>
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
			<h2><?php esc_html_e( 'Policy pages — read before you launch', 'sreesaanvika' ); ?></h2>

			<p>
				<?php esc_html_e( 'Privacy, Terms, Shipping and Returns each ship with a full draft written for an Indian direct-to-consumer store. The return window, shipping threshold, COD limit, business name, GSTIN, jurisdiction and grievance officer are pulled from Customizer → Policies & Legal, so changing a figure there is enough — you do not have to hunt through the text.', 'sreesaanvika' ); ?>
			</p>

			<p style="padding:12px 16px;background:#fff8e5;border-left:4px solid #dba617">
				<strong><?php esc_html_e( 'These are drafts, not legal advice.', 'sreesaanvika' ); ?></strong>
				<?php esc_html_e( 'They are a solid starting point, but they have not been reviewed by a lawyer and they cannot know the specifics of your business. Have someone qualified read them before you take real orders — particularly the liability, jurisdiction and grievance sections.', 'sreesaanvika' ); ?>
			</p>

			<p>
				<a class="button" href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=ss_policy' ) ); ?>">
					<?php esc_html_e( 'Set the policy figures', 'sreesaanvika' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=ss_seo' ) ); ?>">
					<?php esc_html_e( 'SEO & social settings', 'sreesaanvika' ); ?>
				</a>
			</p>
		</div>

		<div class="card" style="max-width:820px;padding:8px 22px 22px;margin-top:20px">
			<h2><?php esc_html_e( 'Good to know', 'sreesaanvika' ); ?></h2>
			<ul style="list-style:disc;padding-left:20px">
				<li><?php esc_html_e( 'Add "mega" as a CSS class on a top-level menu item to turn its dropdown into a four-column mega menu. "hot" and "new" add a small flag.', 'sreesaanvika' ); ?></li>
				<li><?php esc_html_e( 'Colour swatches read a product\'s Color / Colour attribute. Add a term meta named ss_color with a hex value to set an exact shade.', 'sreesaanvika' ); ?></li>
				<li><?php esc_html_e( 'Product images look best as portrait 3:4 — 1200 × 1600 pixels or larger, so the zoom stays sharp.', 'sreesaanvika' ); ?></li>
				<li><?php esc_html_e( 'Set a product category image under Products → Categories to fill the homepage mosaic and the round category rail.', 'sreesaanvika' ); ?></li>
				<li><?php esc_html_e( 'Homepage sections can each be switched off in the Customizer under Homepage — Sections.', 'sreesaanvika' ); ?></li>
				<li><?php esc_html_e( 'The theme writes its own meta tags, Open Graph and schema.org data — and steps aside automatically if you install Yoast or Rank Math, so nothing is ever duplicated.', 'sreesaanvika' ); ?></li>
				<li><?php esc_html_e( 'Cart, checkout, account, compare, wishlist and sign-in are set to noindex and kept out of the sitemap. That happens with or without an SEO plugin.', 'sreesaanvika' ); ?></li>
			</ul>
		</div>
	</div>
	<?php
}
