<?php
/**
 * Login / register on the My Account page, styled as the theme auth panel.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_customer_login_form' );

$od_can_register = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
$od_art          = od_option( 'hero1_img', '' );
?>
<div class="od-auth">

	<div class="od-auth__art">
		<div class="od-auth__art-bg"<?php echo $od_art ? od_bg_style( $od_art ) : ''; ?>></div>

		<div class="od-auth__art-content">
			<h2><?php echo od_kses( __( 'Your wardrobe, <em>remembered</em>', 'ojasvidrapes' ) ); ?></h2>
			<p><?php esc_html_e( 'One account for your orders, wishlist, saved addresses and early access to every new drop.', 'ojasvidrapes' ); ?></p>

			<ul class="od-auth__perks">
				<li><?php od_the_icon( 'check-circle', 18 ); ?><?php esc_html_e( 'Track every order in real time', 'ojasvidrapes' ); ?></li>
				<li><?php od_the_icon( 'check-circle', 18 ); ?><?php esc_html_e( 'Wishlist that follows you across devices', 'ojasvidrapes' ); ?></li>
				<li><?php od_the_icon( 'check-circle', 18 ); ?><?php esc_html_e( 'Members-only festive previews', 'ojasvidrapes' ); ?></li>
				<li><?php od_the_icon( 'check-circle', 18 ); ?><?php esc_html_e( 'Faster checkout with saved addresses', 'ojasvidrapes' ); ?></li>
			</ul>
		</div>
	</div>

	<div class="od-auth__panel" data-tabs>

		<?php if ( $od_can_register ) : ?>
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
			<h2 style="font-size:1.7rem"><?php esc_html_e( 'Welcome back', 'ojasvidrapes' ); ?></h2>
			<p style="color:var(--od-muted);margin-bottom:24px">
				<?php esc_html_e( 'Sign in to pick up where you left off.', 'ojasvidrapes' ); ?>
			</p>

			<form class="woocommerce-form woocommerce-form-login login" method="post">
				<?php do_action( 'woocommerce_login_form_start' ); ?>

				<div class="od-field">
					<label for="username"><?php esc_html_e( 'Email or username', 'ojasvidrapes' ); ?>&nbsp;<span class="required">*</span></label>
					<input type="text" class="woocommerce-Input input-text" name="username" id="username" autocomplete="username"
						value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required /><?php // phpcs:ignore WordPress.Security.NonceVerification.Missing ?>
				</div>

				<div class="od-field">
					<label for="password"><?php esc_html_e( 'Password', 'ojasvidrapes' ); ?>&nbsp;<span class="required">*</span></label>
					<div class="od-pw-wrap">
						<input class="woocommerce-Input input-text" type="password" name="password" id="password" autocomplete="current-password" required />
						<button type="button" class="od-pw-toggle" aria-label="<?php esc_attr_e( 'Show password', 'ojasvidrapes' ); ?>">
							<?php od_the_icon( 'eye', 18 ); ?>
						</button>
					</div>
				</div>

				<?php do_action( 'woocommerce_login_form' ); ?>

				<div class="od-between" style="margin-bottom:20px">
					<label class="od-check woocommerce-form-login__rememberme">
						<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" value="forever" />
						<span><?php esc_html_e( 'Keep me signed in', 'ojasvidrapes' ); ?></span>
					</label>

					<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" style="font-size:.86rem">
						<?php esc_html_e( 'Forgot password?', 'ojasvidrapes' ); ?>
					</a>
				</div>

				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>

				<button type="submit" class="od-btn od-btn--block woocommerce-button button woocommerce-form-login__submit"
					name="login" value="<?php esc_attr_e( 'Sign in', 'ojasvidrapes' ); ?>">
					<?php esc_html_e( 'Sign in', 'ojasvidrapes' ); ?>
				</button>

				<?php do_action( 'woocommerce_login_form_end' ); ?>
			</form>
		</div>

		<?php if ( $od_can_register ) : ?>
			<!-- Register -->
			<div class="od-auth__pane" data-pane="register">
				<h2 style="font-size:1.7rem"><?php esc_html_e( 'Create your account', 'ojasvidrapes' ); ?></h2>
				<p style="color:var(--od-muted);margin-bottom:24px">
					<?php esc_html_e( 'It takes under a minute — and your first order gets ₹500 off above ₹4,999.', 'ojasvidrapes' ); ?>
				</p>

				<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?>>
					<?php do_action( 'woocommerce_register_form_start' ); ?>

					<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
						<div class="od-field">
							<label for="reg_username"><?php esc_html_e( 'Username', 'ojasvidrapes' ); ?>&nbsp;<span class="required">*</span></label>
							<input type="text" class="woocommerce-Input input-text" name="username" id="reg_username" autocomplete="username"
								value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required /><?php // phpcs:ignore WordPress.Security.NonceVerification.Missing ?>
						</div>
					<?php endif; ?>

					<div class="od-field">
						<label for="reg_email"><?php esc_html_e( 'Email address', 'ojasvidrapes' ); ?>&nbsp;<span class="required">*</span></label>
						<input type="email" class="woocommerce-Input input-text" name="email" id="reg_email" autocomplete="email"
							value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" required /><?php // phpcs:ignore WordPress.Security.NonceVerification.Missing ?>
					</div>

					<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
						<div class="od-field">
							<label for="reg_password"><?php esc_html_e( 'Password', 'ojasvidrapes' ); ?>&nbsp;<span class="required">*</span></label>
							<div class="od-pw-wrap">
								<input type="password" class="woocommerce-Input input-text" name="password" id="reg_password"
									autocomplete="new-password" data-strength="#od-reg-strength" required />
								<button type="button" class="od-pw-toggle" aria-label="<?php esc_attr_e( 'Show password', 'ojasvidrapes' ); ?>">
									<?php od_the_icon( 'eye', 18 ); ?>
								</button>
							</div>
							<div class="od-pw-strength" id="od-reg-strength" data-level="0"><i></i></div>
						</div>
					<?php else : ?>
						<p style="color:var(--od-muted);font-size:.88rem">
							<?php esc_html_e( 'A password will be emailed to you.', 'ojasvidrapes' ); ?>
						</p>
					<?php endif; ?>

					<?php do_action( 'woocommerce_register_form' ); ?>

					<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>

					<button type="submit" class="od-btn od-btn--block woocommerce-Button woocommerce-button button"
						name="register" value="<?php esc_attr_e( 'Create account', 'ojasvidrapes' ); ?>">
						<?php esc_html_e( 'Create account', 'ojasvidrapes' ); ?>
					</button>

					<p style="color:var(--od-faint);font-size:.8rem;margin-top:16px;text-align:center">
						<?php esc_html_e( 'By creating an account you agree to our terms and privacy policy.', 'ojasvidrapes' ); ?>
					</p>

					<?php do_action( 'woocommerce_register_form_end' ); ?>
				</form>
			</div>
		<?php endif; ?>

	</div>
</div>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
