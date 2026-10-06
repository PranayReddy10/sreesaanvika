<?php
/**
 * Template Name: Sign In / Sign Up
 *
 * A standalone auth page that works with or without WooCommerce. When Woo is
 * active it posts through the AJAX endpoints and lands the customer on their
 * account page.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

get_header();

$od_art      = od_option( 'hero1_img', '' );
$od_account  = class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
$od_can_reg  = (bool) get_option( 'users_can_register' ) || ( class_exists( 'WooCommerce' ) && 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) );
$od_redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>

<?php if ( is_user_logged_in() ) : ?>

	<div class="od-container od-section">
		<?php
		$od_user = wp_get_current_user();

		od_empty_state(
			'check-circle',
			sprintf(
				/* translators: %s: user display name */
				__( 'You are signed in as %s', 'ojasvidrapes' ),
				$od_user->display_name
			),
			__( 'Head to your account to track orders, manage addresses and see your saved pieces.', 'ojasvidrapes' ),
			$od_account,
			__( 'Go to my account', 'ojasvidrapes' )
		);
		?>

		<p class="od-text-center" style="margin-top:18px">
			<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" style="font-size:.9rem">
				<?php esc_html_e( 'Sign out', 'ojasvidrapes' ); ?>
			</a>
		</p>
	</div>

<?php else : ?>

	<div class="od-container od-section od-section--tight">
		<div class="od-auth">

			<div class="od-auth__art">
				<div class="od-auth__art-bg"<?php echo $od_art ? od_bg_style( $od_art ) : ''; ?>></div>

				<div class="od-auth__art-content">
					<div class="od-ornament" aria-hidden="true" style="justify-content:flex-start;margin-bottom:14px">
						<?php od_the_icon( 'lotus', 22 ); ?>
					</div>

					<h2><?php echo od_kses( __( 'Join the <em>Ojasvi</em> circle', 'ojasvidrapes' ) ); ?></h2>
					<p><?php esc_html_e( 'Members see every new weave first, and get a ₹500 voucher on their first order.', 'ojasvidrapes' ); ?></p>

					<ul class="od-auth__perks">
						<li><?php od_the_icon( 'check-circle', 18 ); ?><?php esc_html_e( 'Early access to festive collections', 'ojasvidrapes' ); ?></li>
						<li><?php od_the_icon( 'check-circle', 18 ); ?><?php esc_html_e( 'Wishlist synced across your devices', 'ojasvidrapes' ); ?></li>
						<li><?php od_the_icon( 'check-circle', 18 ); ?><?php esc_html_e( 'One-tap reorder and saved addresses', 'ojasvidrapes' ); ?></li>
						<li><?php od_the_icon( 'check-circle', 18 ); ?><?php esc_html_e( 'A personal stylist on WhatsApp', 'ojasvidrapes' ); ?></li>
					</ul>
				</div>
			</div>

			<div class="od-auth__panel" data-tabs>

				<?php if ( $od_can_reg ) : ?>
					<div class="od-auth__tabs" role="tablist">
						<button type="button" class="od-auth__tab is-active" data-tab="login" role="tab" aria-selected="true">
							<?php esc_html_e( 'Sign in', 'ojasvidrapes' ); ?>
						</button>
						<button type="button" class="od-auth__tab" data-tab="register" role="tab" aria-selected="false">
							<?php esc_html_e( 'Create account', 'ojasvidrapes' ); ?>
						</button>
					</div>
				<?php endif; ?>

				<!-- Sign in -->
				<div class="od-auth__pane is-active" data-pane="login">
					<h1 style="font-size:1.8rem"><?php esc_html_e( 'Welcome back', 'ojasvidrapes' ); ?></h1>
					<p style="color:var(--od-muted);margin-bottom:26px">
						<?php esc_html_e( 'Sign in to your account to continue.', 'ojasvidrapes' ); ?>
					</p>

					<form data-auth-form="login" <?php echo $od_redirect ? 'data-redirect="' . esc_url( $od_redirect ) . '"' : ''; ?>>
						<div class="od-field">
							<label for="od-login-user"><?php esc_html_e( 'Email or username', 'ojasvidrapes' ); ?></label>
							<input type="text" id="od-login-user" name="username" autocomplete="username" required
								placeholder="<?php esc_attr_e( 'you@example.com', 'ojasvidrapes' ); ?>" />
						</div>

						<div class="od-field">
							<label for="od-login-pass"><?php esc_html_e( 'Password', 'ojasvidrapes' ); ?></label>
							<div class="od-pw-wrap">
								<input type="password" id="od-login-pass" name="password" autocomplete="current-password" required
									placeholder="<?php esc_attr_e( 'Your password', 'ojasvidrapes' ); ?>" />
								<button type="button" class="od-pw-toggle" aria-label="<?php esc_attr_e( 'Show password', 'ojasvidrapes' ); ?>">
									<?php od_the_icon( 'eye', 18 ); ?>
								</button>
							</div>
						</div>

						<div class="od-between" style="margin-bottom:22px">
							<label class="od-check">
								<input type="checkbox" name="remember" value="1" checked />
								<span><?php esc_html_e( 'Keep me signed in', 'ojasvidrapes' ); ?></span>
							</label>

							<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" style="font-size:.86rem">
								<?php esc_html_e( 'Forgot password?', 'ojasvidrapes' ); ?>
							</a>
						</div>

						<button type="submit" class="od-btn od-btn--block od-btn--lg">
							<?php esc_html_e( 'Sign in', 'ojasvidrapes' ); ?>
						</button>
					</form>

					<?php if ( $od_can_reg ) : ?>
						<div class="od-divider-or"><?php esc_html_e( 'New here?', 'ojasvidrapes' ); ?></div>

						<button type="button" class="od-btn od-btn--ghost od-btn--block" data-tab="register">
							<?php esc_html_e( 'Create an account', 'ojasvidrapes' ); ?>
						</button>
					<?php endif; ?>
				</div>

				<?php if ( $od_can_reg ) : ?>
					<!-- Register -->
					<div class="od-auth__pane" data-pane="register">
						<h2 style="font-size:1.8rem"><?php esc_html_e( 'Create your account', 'ojasvidrapes' ); ?></h2>
						<p style="color:var(--od-muted);margin-bottom:26px">
							<?php esc_html_e( 'A minute now, faster checkout forever.', 'ojasvidrapes' ); ?>
						</p>

						<form data-auth-form="register">
							<div class="od-grid od-grid--2" style="gap:0 14px">
								<div class="od-field">
									<label for="od-reg-first"><?php esc_html_e( 'First name', 'ojasvidrapes' ); ?></label>
									<input type="text" id="od-reg-first" name="first_name" autocomplete="given-name" />
								</div>

								<div class="od-field">
									<label for="od-reg-last"><?php esc_html_e( 'Last name', 'ojasvidrapes' ); ?></label>
									<input type="text" id="od-reg-last" name="last_name" autocomplete="family-name" />
								</div>
							</div>

							<div class="od-field">
								<label for="od-reg-email"><?php esc_html_e( 'Email address', 'ojasvidrapes' ); ?></label>
								<input type="email" id="od-reg-email" name="email" autocomplete="email" required
									placeholder="<?php esc_attr_e( 'you@example.com', 'ojasvidrapes' ); ?>" />
							</div>

							<div class="od-field">
								<label for="od-reg-phone"><?php esc_html_e( 'Mobile number', 'ojasvidrapes' ); ?></label>
								<input type="tel" id="od-reg-phone" name="phone" autocomplete="tel" inputmode="numeric"
									placeholder="<?php esc_attr_e( '+91 98765 43210', 'ojasvidrapes' ); ?>" />
							</div>

							<div class="od-field">
								<label for="od-reg-pass"><?php esc_html_e( 'Password', 'ojasvidrapes' ); ?></label>
								<div class="od-pw-wrap">
									<input type="password" id="od-reg-pass" name="password" autocomplete="new-password" required
										minlength="8" data-strength="#od-auth-strength"
										placeholder="<?php esc_attr_e( 'At least 8 characters', 'ojasvidrapes' ); ?>" />
									<button type="button" class="od-pw-toggle" aria-label="<?php esc_attr_e( 'Show password', 'ojasvidrapes' ); ?>">
										<?php od_the_icon( 'eye', 18 ); ?>
									</button>
								</div>
								<div class="od-pw-strength" id="od-auth-strength" data-level="0"><i></i></div>
							</div>

							<label class="od-check" style="margin-bottom:22px">
								<input type="checkbox" name="terms" value="1" required />
								<span><?php esc_html_e( 'I agree to the terms of service and privacy policy.', 'ojasvidrapes' ); ?></span>
							</label>

							<button type="submit" class="od-btn od-btn--block od-btn--lg">
								<?php esc_html_e( 'Create account', 'ojasvidrapes' ); ?>
							</button>
						</form>

						<div class="od-divider-or"><?php esc_html_e( 'Already a member?', 'ojasvidrapes' ); ?></div>

						<button type="button" class="od-btn od-btn--ghost od-btn--block" data-tab="login">
							<?php esc_html_e( 'Sign in instead', 'ojasvidrapes' ); ?>
						</button>
					</div>
				<?php endif; ?>

			</div>
		</div>
	</div>

<?php endif; ?>

<?php
get_footer();
