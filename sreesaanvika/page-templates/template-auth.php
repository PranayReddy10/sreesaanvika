<?php
/**
 * Template Name: Sign In / Sign Up
 *
 * A standalone auth page that works with or without WooCommerce. When Woo is
 * active it posts through the AJAX endpoints and lands the customer on their
 * account page.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ss_art      = ss_option( 'hero1_img', '' );
$ss_account  = class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
$ss_can_reg  = (bool) get_option( 'users_can_register' ) || ( class_exists( 'WooCommerce' ) && 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) );
$ss_redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>

<?php if ( is_user_logged_in() ) : ?>

	<div class="ss-container ss-section">
		<?php
		$ss_user = wp_get_current_user();

		ss_empty_state(
			'check-circle',
			sprintf(
				/* translators: %s: user display name */
				__( 'You are signed in as %s', 'sreesaanvika' ),
				$ss_user->display_name
			),
			__( 'Head to your account to track orders, manage addresses and see your saved pieces.', 'sreesaanvika' ),
			$ss_account,
			__( 'Go to my account', 'sreesaanvika' )
		);
		?>

		<p class="ss-text-center" style="margin-top:18px">
			<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" style="font-size:.9rem">
				<?php esc_html_e( 'Sign out', 'sreesaanvika' ); ?>
			</a>
		</p>
	</div>

<?php else : ?>

	<div class="ss-container ss-section ss-section--tight">
		<div class="ss-auth">

			<div class="ss-auth__art">
				<div class="ss-auth__art-bg"<?php echo $ss_art ? ss_bg_style( $ss_art ) : ''; ?>></div>

				<div class="ss-auth__art-content">
					<div class="ss-ornament" aria-hidden="true" style="justify-content:flex-start;margin-bottom:14px">
						<?php ss_the_icon( 'lotus', 22 ); ?>
					</div>

					<h2><?php echo ss_kses( __( 'Join the <em>Saanvika</em> circle', 'sreesaanvika' ) ); ?></h2>
					<p><?php esc_html_e( 'Members see every new weave first, and get a ₹500 voucher on their first order.', 'sreesaanvika' ); ?></p>

					<ul class="ss-auth__perks">
						<li><?php ss_the_icon( 'check-circle', 18 ); ?><?php esc_html_e( 'Early access to festive collections', 'sreesaanvika' ); ?></li>
						<li><?php ss_the_icon( 'check-circle', 18 ); ?><?php esc_html_e( 'Wishlist synced across your devices', 'sreesaanvika' ); ?></li>
						<li><?php ss_the_icon( 'check-circle', 18 ); ?><?php esc_html_e( 'One-tap reorder and saved addresses', 'sreesaanvika' ); ?></li>
						<li><?php ss_the_icon( 'check-circle', 18 ); ?><?php esc_html_e( 'A personal stylist on WhatsApp', 'sreesaanvika' ); ?></li>
					</ul>
				</div>
			</div>

			<div class="ss-auth__panel" data-tabs>

				<?php if ( $ss_can_reg ) : ?>
					<div class="ss-auth__tabs" role="tablist">
						<button type="button" class="ss-auth__tab is-active" data-tab="login" role="tab" aria-selected="true">
							<?php esc_html_e( 'Sign in', 'sreesaanvika' ); ?>
						</button>
						<button type="button" class="ss-auth__tab" data-tab="register" role="tab" aria-selected="false">
							<?php esc_html_e( 'Create account', 'sreesaanvika' ); ?>
						</button>
					</div>
				<?php endif; ?>

				<!-- Sign in -->
				<div class="ss-auth__pane is-active" data-pane="login">
					<h1 style="font-size:1.8rem"><?php esc_html_e( 'Welcome back', 'sreesaanvika' ); ?></h1>
					<p style="color:var(--ss-muted);margin-bottom:26px">
						<?php esc_html_e( 'Sign in to your account to continue.', 'sreesaanvika' ); ?>
					</p>

					<form data-auth-form="login" <?php echo $ss_redirect ? 'data-redirect="' . esc_url( $ss_redirect ) . '"' : ''; ?>>
						<div class="ss-field">
							<label for="ss-login-user"><?php esc_html_e( 'Email or username', 'sreesaanvika' ); ?></label>
							<input type="text" id="ss-login-user" name="username" autocomplete="username" required
								placeholder="<?php esc_attr_e( 'you@example.com', 'sreesaanvika' ); ?>" />
						</div>

						<div class="ss-field">
							<label for="ss-login-pass"><?php esc_html_e( 'Password', 'sreesaanvika' ); ?></label>
							<div class="ss-pw-wrap">
								<input type="password" id="ss-login-pass" name="password" autocomplete="current-password" required
									placeholder="<?php esc_attr_e( 'Your password', 'sreesaanvika' ); ?>" />
								<button type="button" class="ss-pw-toggle" aria-label="<?php esc_attr_e( 'Show password', 'sreesaanvika' ); ?>">
									<?php ss_the_icon( 'eye', 18 ); ?>
								</button>
							</div>
						</div>

						<div class="ss-between" style="margin-bottom:22px">
							<label class="ss-check">
								<input type="checkbox" name="remember" value="1" checked />
								<span><?php esc_html_e( 'Keep me signed in', 'sreesaanvika' ); ?></span>
							</label>

							<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" style="font-size:.86rem">
								<?php esc_html_e( 'Forgot password?', 'sreesaanvika' ); ?>
							</a>
						</div>

						<button type="submit" class="ss-btn ss-btn--block ss-btn--lg">
							<?php esc_html_e( 'Sign in', 'sreesaanvika' ); ?>
						</button>
					</form>

					<?php if ( $ss_can_reg ) : ?>
						<div class="ss-divider-or"><?php esc_html_e( 'New here?', 'sreesaanvika' ); ?></div>

						<button type="button" class="ss-btn ss-btn--ghost ss-btn--block" data-tab="register">
							<?php esc_html_e( 'Create an account', 'sreesaanvika' ); ?>
						</button>
					<?php endif; ?>
				</div>

				<?php if ( $ss_can_reg ) : ?>
					<!-- Register -->
					<div class="ss-auth__pane" data-pane="register">
						<h2 style="font-size:1.8rem"><?php esc_html_e( 'Create your account', 'sreesaanvika' ); ?></h2>
						<p style="color:var(--ss-muted);margin-bottom:26px">
							<?php esc_html_e( 'A minute now, faster checkout forever.', 'sreesaanvika' ); ?>
						</p>

						<form data-auth-form="register">
							<div class="ss-grid ss-grid--2" style="gap:0 14px">
								<div class="ss-field">
									<label for="ss-reg-first"><?php esc_html_e( 'First name', 'sreesaanvika' ); ?></label>
									<input type="text" id="ss-reg-first" name="first_name" autocomplete="given-name" />
								</div>

								<div class="ss-field">
									<label for="ss-reg-last"><?php esc_html_e( 'Last name', 'sreesaanvika' ); ?></label>
									<input type="text" id="ss-reg-last" name="last_name" autocomplete="family-name" />
								</div>
							</div>

							<div class="ss-field">
								<label for="ss-reg-email"><?php esc_html_e( 'Email address', 'sreesaanvika' ); ?></label>
								<input type="email" id="ss-reg-email" name="email" autocomplete="email" required
									placeholder="<?php esc_attr_e( 'you@example.com', 'sreesaanvika' ); ?>" />
							</div>

							<div class="ss-field">
								<label for="ss-reg-phone"><?php esc_html_e( 'Mobile number', 'sreesaanvika' ); ?></label>
								<input type="tel" id="ss-reg-phone" name="phone" autocomplete="tel" inputmode="numeric"
									placeholder="<?php esc_attr_e( '+91 98765 43210', 'sreesaanvika' ); ?>" />
							</div>

							<div class="ss-field">
								<label for="ss-reg-pass"><?php esc_html_e( 'Password', 'sreesaanvika' ); ?></label>
								<div class="ss-pw-wrap">
									<input type="password" id="ss-reg-pass" name="password" autocomplete="new-password" required
										minlength="8" data-strength="#ss-auth-strength"
										placeholder="<?php esc_attr_e( 'At least 8 characters', 'sreesaanvika' ); ?>" />
									<button type="button" class="ss-pw-toggle" aria-label="<?php esc_attr_e( 'Show password', 'sreesaanvika' ); ?>">
										<?php ss_the_icon( 'eye', 18 ); ?>
									</button>
								</div>
								<div class="ss-pw-strength" id="ss-auth-strength" data-level="0"><i></i></div>
							</div>

							<label class="ss-check" style="margin-bottom:22px">
								<input type="checkbox" name="terms" value="1" required />
								<span><?php esc_html_e( 'I agree to the terms of service and privacy policy.', 'sreesaanvika' ); ?></span>
							</label>

							<button type="submit" class="ss-btn ss-btn--block ss-btn--lg">
								<?php esc_html_e( 'Create account', 'sreesaanvika' ); ?>
							</button>
						</form>

						<div class="ss-divider-or"><?php esc_html_e( 'Already a member?', 'sreesaanvika' ); ?></div>

						<button type="button" class="ss-btn ss-btn--ghost ss-btn--block" data-tab="login">
							<?php esc_html_e( 'Sign in instead', 'sreesaanvika' ); ?>
						</button>
					</div>
				<?php endif; ?>

			</div>
		</div>
	</div>

<?php endif; ?>

<?php
get_footer();
