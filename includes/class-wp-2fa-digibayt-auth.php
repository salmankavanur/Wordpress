<?php
/**
 * Core Authentication Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_2FA_Auth_DigiBayt_Auth {

	public function __construct() {
		// Hook into WP authentication
		add_filter( 'wp_authenticate_user', array( $this, 'intercept_login' ), 100, 2 );
		add_action( 'wp_login', array( $this, 'handle_wp_login' ), 10, 2 );
		
		// Handle 2FA verification screen
		add_action( 'login_form_wp-2fa-auth', array( $this, 'render_2fa_form' ) );
		add_action( 'login_init', array( $this, 'process_2fa_verification' ) );
	}

	/**
	 * Render the 2FA Form on the login page
	 */
	public function render_2fa_form() {
		if ( ! isset( $_COOKIE['wp_2fa_auth_digibayt_pending'] ) ) {
			wp_safe_redirect( wp_login_url() );
			exit;
		}

		login_header( __( 'Two-Factor Authentication', '2fa-auth-by-digibayt' ) );
		?>
		<form name="2faform" id="2faform" action="<?php echo esc_url( wp_login_url() ); ?>" method="post">
			<p>
				<label for="2fa_code"><?php esc_html_e( 'Verification Code', '2fa-auth-by-digibayt' ); ?><br />
				<input type="text" name="2fa_code" id="2fa_code" class="input" value="" size="20" autofocus autocomplete="one-time-code" /></label>
			</p>
			<?php wp_nonce_field( 'wp_2fa_auth_digibayt_verify' ); ?>
			<input type="hidden" name="wp-2fa-verify" value="1" />
			<p class="submit">
				<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php echo esc_attr__( 'Verify', '2fa-auth-by-digibayt' ); ?>" />
			</p>
		</form>
		<p id="backtoblog">
			<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_html_e( '&larr; Back to Login', '2fa-auth-by-digibayt' ); ?></a>
		</p>
		<?php
		login_footer();
		exit;
	}

	/**
	 * Process 2FA Verification
	 */
	public function process_2fa_verification() {
		if ( ! isset( $_POST['wp-2fa-verify'] ) || ! isset( $_POST['2fa_code'] ) ) {
			return;
		}

		check_admin_referer( 'wp_2fa_auth_digibayt_verify' );

		$token = isset( $_COOKIE['wp_2fa_auth_digibayt_pending'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['wp_2fa_auth_digibayt_pending'] ) ) : '';
		$user_id = get_transient( 'wp_2fa_auth_digibayt_login_' . $token );

		if ( ! $user_id ) {
			wp_die( esc_html__( 'Session expired. Please try logging in again.', '2fa-auth-by-digibayt' ) );
		}

		$code = isset( $_POST['2fa_code'] ) ? sanitize_text_field( wp_unslash( $_POST['2fa_code'] ) ) : '';
		
		if ( $this->verify_code( $user_id, $code ) ) {
			// Success! Clear 2FA session and log the user in
			delete_transient( 'wp_2fa_auth_digibayt_login_' . $token );
			setcookie( 'wp_2fa_auth_digibayt_pending', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN );

			// Trust this device
			$this->trust_current_device( $user_id );

			wp_set_auth_cookie( $user_id, true );
			
			// Log the success
			$this->log_event( $user_id, 'login_success', 'Successful 2FA login' );

			$redirect_to = admin_url();
			wp_safe_redirect( $redirect_to );
			exit;
		} else {
			// Fail!
			$this->log_event( $user_id, 'login_failed', 'Failed 2FA login attempt' );
			wp_die( esc_html__( 'Invalid verification code.', '2fa-auth-by-digibayt' ) );
		}
	}

	/**
	 * Verify code against enabled providers
	 */
	private function verify_code( $user_id, $code ) {
		// Try TOTP
		if ( class_exists( 'WP_2FA_Auth_DigiBayt_TOTP' ) ) {
			$totp = new WP_2FA_Auth_DigiBayt_TOTP();
			$secret = get_user_meta( $user_id, '_wp_2fa_auth_digibayt_totp_secret', true );
			if ( $secret && $totp->verify_code( $secret, $code ) ) {
				return true;
			}
		}

		// Try Backup Codes
		if ( class_exists( 'WP_2FA_Auth_DigiBayt_Backup_Codes' ) ) {
			$bc = new WP_2FA_Auth_DigiBayt_Backup_Codes();
			if ( $bc->verify_code( $user_id, $code ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Log security event
	 */
	private function log_event( $user_id, $type, $description ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->prefix . 'digibayt_2fa_logs',
			array(
				'user_id'     => $user_id,
				'event_type'  => $type,
				'description' => $description,
				'ip_address'  => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
				'created_at'  => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Intercept the login process to check for 2FA
	 */
	public function intercept_login( $user, $password ) {
		if ( is_wp_error( $user ) || ! $user instanceof WP_User ) {
			return $user;
		}

		// Check for Trusted Device
		if ( $this->is_trusted_device( $user->ID ) ) {
			return $user;
		}

		// Check if 2FA is enabled for this user
		if ( ! $this->is_2fa_enabled( $user->ID ) ) {
			return $user;
		}

		return $user;
	}

	/**
	 * Check if the current device is trusted
	 */
	private function is_trusted_device( $user_id ) {
		$cookie_name = 'wp_2fa_auth_digibayt_trusted_' . COOKIEHASH;
		if ( ! isset( $_COOKIE[ $cookie_name ] ) ) {
			return false;
		}

		$token = sanitize_text_field( wp_unslash( $_COOKIE[ $cookie_name ] ) );
		$trusted_devices = get_user_meta( $user_id, '_wp_2fa_auth_digibayt_trusted_devices', true );

		if ( ! is_array( $trusted_devices ) ) {
			return false;
		}

		foreach ( $trusted_devices as $index => $device ) {
			if ( hash_equals( $device['token'], hash( 'sha256', $token ) ) ) {
				if ( time() < $device['expires'] ) {
					return true;
				} else {
					// Expired, remove it
					unset( $trusted_devices[ $index ] );
					update_user_meta( $user_id, '_wp_2fa_auth_digibayt_trusted_devices', array_values( $trusted_devices ) );
				}
			}
		}

		return false;
	}

	/**
	 * Add the current device to trusted devices
	 */
	private function trust_current_device( $user_id ) {
		$settings = get_option( 'wp_2fa_auth_digibayt_settings', array() );
		$days = $settings['remember_device'] ?? 0;
		if ( $days <= 0 ) {
			return;
		}

		$token = wp_generate_password( 64, false );
		$expires = time() + ( $days * DAY_IN_SECONDS );
		$cookie_name = 'wp_2fa_auth_digibayt_trusted_' . COOKIEHASH;

		setcookie( $cookie_name, $token, $expires, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );

		$trusted_devices = get_user_meta( $user_id, '_wp_2fa_auth_digibayt_trusted_devices', true );
		if ( ! is_array( $trusted_devices ) ) {
			$trusted_devices = array();
		}

		$trusted_devices[] = array(
			'token'   => hash( 'sha256', $token ),
			'expires' => $expires,
			'ip'      => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '',
			'ua'      => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
		);

		// Keep only last 10 devices
		if ( count( $trusted_devices ) > 10 ) {
			array_shift( $trusted_devices );
		}

		update_user_meta( $user_id, '_wp_2fa_auth_digibayt_trusted_devices', $trusted_devices );
	}

	/**
	 * Handle post-login redirection for 2FA
	 */
	public function handle_wp_login( $user_login, $user ) {
		if ( ! $this->is_2fa_enabled( $user->ID ) ) {
			return;
		}

		// If 2FA is enabled, clear the auth cookie and show 2FA screen
		wp_clear_auth_cookie();
		
		// Set a temporary session/token for 2FA
		$this->start_2fa_session( $user->ID );

		// Redirect to 2FA page
		wp_safe_redirect( add_query_arg( 'action', 'wp-2fa-auth', wp_login_url() ) );
		exit;
	}

	/**
	 * Check if 2FA is enabled for a user
	 */
	public function is_2fa_enabled( $user_id ) {
		// Individual setting
		if ( get_user_meta( $user_id, '_wp_2fa_auth_digibayt_enabled', true ) === 'yes' ) {
			return true;
		}

		// Role Enforcement
		$settings = get_option( 'wp_2fa_auth_digibayt_settings', array() );
		if ( ! empty( $settings['enforce_admins'] ) ) {
			$user = get_userdata( $user_id );
			if ( $user && in_array( 'administrator', (array) $user->roles ) ) {
				
				// Check for Grace Period
				$grace_period = $settings['grace_period'] ?? 0;
				if ( $grace_period > 0 ) {
					$registered = strtotime( $user->user_registered );
					$grace_end = $registered + ( $grace_period * DAY_IN_SECONDS );
					
					if ( time() < $grace_end ) {
						return false; // Still in grace period
					}
				}
				
				return true; // Enforced
			}
		}

		return false;
	}

	/**
	 * Start a 2FA session
	 */
	private function start_2fa_session( $user_id ) {
		$token = wp_generate_password( 40, false );
		set_transient( 'wp_2fa_auth_digibayt_login_' . $token, $user_id, 15 * MINUTE_IN_SECONDS );
		setcookie( 'wp_2fa_auth_digibayt_pending', $token, time() + 15 * MINUTE_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
	}
}
