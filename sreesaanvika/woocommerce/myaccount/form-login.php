<?php
/**
 * Login / register on the My Account page, styled as the theme auth panel.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_customer_login_form' );

$ss_can_register = 'yes' === get_option( 'woocommerce_enable_myaccount_registration' );
$ss_art          = ss_option( 'hero1_img', '' );
?>
<div class="ss-auth">

	<div class="ss-auth__art">
		<div class="ss-auth__art-bg"<?php echo $ss_art ? ss_bg_style( $ss_art ) : ''; ?>></div>

		<div class="ss-auth__art-content">
			<h2><?php echo ss_kses( __( 'Your wardrobe, <em>remembered</em>', 'sreesaanvika' ) ); ?></h2>
			<p><?php esc_html_e( 'One account for your orders, wishlist, saved addresses and early access to every new drop.', 'sreesaanvika' ); ?></p>

			<ul class="ss-auth__perks">
				<li><?php ss_the_icon( 'check-circle', 18 ); ?><?php esc_html_e( 'Track every order in real time', 'sreesaanvika' ); ?></li>
				<li><?php ss_the_icon( 'check-circle', 18 ); ?><?php esc_html_e( 'Wishlist that follows you across devices', 'sreesaanvika' ); ?></li>
				<li><?php ss_the_icon( 'check-circle', 18 ); ?><?php esc_html_e( 'Members-only festive previews', 'sreesaanvika' ); ?></li>
				<li><?php ss_the_icon( 'check-circle', 18 ); ?><?php esc_html_e( 'Faster checkout with saved addresses', 'sreesaanvika' ); ?></li>
			</ul>
		</div>
	</div>

	<div class="ss-auth__panel" data-tabs>

		<?php if ( $ss_can_register ) : ?>
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
			<h2 style="font-size:1.7rem"><?php esc_html_e( 'Welcome back', 'sreesaanvika' ); ?></h2>
			<p style="color:var(--ss-muted);margin-bottom:24px">
				<?php esc_html_e( 'Sign in to pick up where you left off.', 'sreesaanvika' ); ?>
			</p>

			<form class="woocommerce-form woocommerce-form-login login" method="post">
				<?php do_action( 'woocommerce_login_form_start' ); ?>

				<div class="ss-field">
					<label for="username"><?php esc_html_e( 'Email or username', 'sreesaanvika' ); ?>&nbsp;<span class="required">*</span></label>
					<input type="text" class="woocommerce-Input input-text" name="username" id="username" autocomplete="username"
						value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required /><?php // phpcs:ignore WordPress.Security.NonceVerification.Missing ?>
				</div>

				<div class="ss-field">
					<label for="password"><?php esc_html_e( 'Password', 'sreesaanvika' ); ?>&nbsp;<span class="required">*</span></label>
					<div class="ss-pw-wrap">
						<input class="woocommerce-Input input-text" type="password" name="password" id="password" autocomplete="current-password" required />
						<button type="button" class="ss-pw-toggle" aria-label="<?php esc_attr_e( 'Show password', 'sreesaanvika' ); ?>">
							<?php ss_the_icon( 'eye', 18 ); ?>
						</button>
					</div>
				</div>

				<?php do_action( 'woocommerce_login_form' ); ?>

				<div class="ss-between" style="margin-bottom:20px">
					<label class="ss-check woocommerce-form-login__rememberme">
						<input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" value="forever" />
						<span><?php esc_html_e( 'Keep me signed in', 'sreesaanvika' ); ?></span>
					</label>

					<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" style="font-size:.86rem">
						<?php esc_html_e( 'Forgot password?', 'sreesaanvika' ); ?>
					</a>
				</div>

				<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>

				<button type="submit" class="ss-btn ss-btn--block woocommerce-button button woocommerce-form-login__submit"
					name="login" value="<?php esc_attr_e( 'Sign in', 'sreesaanvika' ); ?>">
					<?php esc_html_e( 'Sign in', 'sreesaanvika' ); ?>
				</button>

				<?php do_action( 'woocommerce_login_form_end' ); ?>
			</form>
		</div>

		<?php if ( $ss_can_register ) : ?>
			<!-- Register -->
			<div class="ss-auth__pane" data-pane="register">
				<h2 style="font-size:1.7rem"><?php esc_html_e( 'Create your account', 'sreesaanvika' ); ?></h2>
				<p style="color:var(--ss-muted);margin-bottom:24px">
					<?php esc_html_e( 'It takes under a minute — and your first order gets ₹500 off above ₹4,999.', 'sreesaanvika' ); ?>
				</p>

				<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?>>
					<?php do_action( 'woocommerce_register_form_start' ); ?>

					<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>
						<div class="ss-field">
							<label for="reg_username"><?php esc_html_e( 'Username', 'sreesaanvika' ); ?>&nbsp;<span class="required">*</span></label>
							<input type="text" class="woocommerce-Input input-text" name="username" id="reg_username" autocomplete="username"
								value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required /><?php // phpcs:ignore WordPress.Security.NonceVerification.Missing ?>
						</div>
					<?php endif; ?>

					<div class="ss-field">
						<label for="reg_email"><?php esc_html_e( 'Email address', 'sreesaanvika' ); ?>&nbsp;<span class="required">*</span></label>
						<input type="email" class="woocommerce-Input input-text" name="email" id="reg_email" autocomplete="email"
							value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" required /><?php // phpcs:ignore WordPress.Security.NonceVerification.Missing ?>
					</div>

					<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>
						<div class="ss-field">
							<label for="reg_password"><?php esc_html_e( 'Password', 'sreesaanvika' ); ?>&nbsp;<span class="required">*</span></label>
							<div class="ss-pw-wrap">
								<input type="password" class="woocommerce-Input input-text" name="password" id="reg_password"
									autocomplete="new-password" data-strength="#ss-reg-strength" required />
								<button type="button" class="ss-pw-toggle" aria-label="<?php esc_attr_e( 'Show password', 'sreesaanvika' ); ?>">
									<?php ss_the_icon( 'eye', 18 ); ?>
								</button>
							</div>
							<div class="ss-pw-strength" id="ss-reg-strength" data-level="0"><i></i></div>
						</div>
					<?php else : ?>
						<p style="color:var(--ss-muted);font-size:.88rem">
							<?php esc_html_e( 'A password will be emailed to you.', 'sreesaanvika' ); ?>
						</p>
					<?php endif; ?>

					<?php do_action( 'woocommerce_register_form' ); ?>

					<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>

					<button type="submit" class="ss-btn ss-btn--block woocommerce-Button woocommerce-button button"
						name="register" value="<?php esc_attr_e( 'Create account', 'sreesaanvika' ); ?>">
						<?php esc_html_e( 'Create account', 'sreesaanvika' ); ?>
					</button>

					<p style="color:var(--ss-faint);font-size:.8rem;margin-top:16px;text-align:center">
						<?php esc_html_e( 'By creating an account you agree to our terms and privacy policy.', 'sreesaanvika' ); ?>
					</p>

					<?php do_action( 'woocommerce_register_form_end' ); ?>
				</form>
			</div>
		<?php endif; ?>

	</div>
</div>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
