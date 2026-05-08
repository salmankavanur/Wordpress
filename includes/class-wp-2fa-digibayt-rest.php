<?php
/**
 * REST API Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_2FA_DigiBayt_REST {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes
	 */
	public function register_routes() {
		register_rest_route( 'wp-2fa-digibayt/v1', '/settings', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_settings' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		register_rest_route( 'wp-2fa-digibayt/v1', '/settings', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'update_settings' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		register_rest_route( 'wp-2fa-digibayt/v1', '/user/config', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_user_config' ),
			'permission_callback' => array( $this, 'check_user_permission' ),
		) );

		register_rest_route( 'wp-2fa-digibayt/v1', '/user/totp/setup', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'setup_totp' ),
			'permission_callback' => array( $this, 'check_user_permission' ),
		) );

		register_rest_route( 'wp-2fa-digibayt/v1', '/user/totp/verify', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'verify_totp' ),
			'permission_callback' => array( $this, 'check_user_permission' ),
		) );

		register_rest_route( 'wp-2fa-digibayt/v1', '/user/backup-codes/generate', array(
			'methods'             => 'POST',
			'callback'            => array( $this, 'generate_backup_codes' ),
			'permission_callback' => array( $this, 'check_user_permission' ),
		) );

		register_rest_route( 'wp-2fa-digibayt/v1', '/logs', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_logs' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );

		register_rest_route( 'wp-2fa-digibayt/v1', '/stats', array(
			'methods'             => 'GET',
			'callback'            => array( $this, 'get_stats' ),
			'permission_callback' => array( $this, 'check_admin_permission' ),
		) );
	}

	/**
	 * Get Audit Logs
	 */
	public function get_logs() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'digibayt_2fa_logs';
		$logs = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 50" );
		return rest_ensure_response( $logs );
	}

	/**
	 * Get Dashboard Statistics
	 */
	public function get_stats() {
		global $wpdb;
		$table_logs = $wpdb->prefix . 'digibayt_2fa_logs';
		
		$total_users = count_users()['total_users'];
		
		$enabled_users = $wpdb->get_var( "SELECT COUNT(user_id) FROM $wpdb->usermeta WHERE meta_key = '_wp_2fa_digibayt_enabled' AND meta_value = 'yes'" );
		
		$failed_attempts = $wpdb->get_var( $wpdb->prepare( 
			"SELECT COUNT(*) FROM $table_logs WHERE event_type = 'login_failed' AND created_at > %s",
			date( 'Y-m-d H:i:s', strtotime( '-24 hours' ) )
		) );

		return rest_ensure_response( array(
			'total_users'     => (int) $total_users,
			'enabled_users'   => (int) $enabled_users,
			'failed_attempts' => (int) $failed_attempts,
		) );
	}

	/**
	 * Setup TOTP: Generate secret and QR code URL
	 */
	public function setup_totp() {
		if ( ! class_exists( 'WP_2FA_DigiBayt_TOTP' ) ) {
			return new WP_Error( 'missing_provider', 'TOTP provider not found.', array( 'status' => 500 ) );
		}
		$totp = new WP_2FA_DigiBayt_TOTP();
		$user = wp_get_current_user();
		$secret = $totp->generate_secret();
		
		// Use Site Name and Host as Issuer for better identification in apps
		$issuer = sprintf( '%s (%s)', get_bloginfo( 'name' ), parse_url( home_url(), PHP_URL_HOST ) );
		
		// Temporarily store secret in user meta until verified
		update_user_meta( $user->ID, '_wp_2fa_digibayt_totp_pending_secret', $secret );

		return rest_ensure_response( array(
			'secret' => $secret,
			'qr_url' => $totp->get_qr_code_url( $user->user_login, $secret, $issuer ),
		) );
	}

	/**
	 * Verify TOTP setup
	 */
	public function verify_totp( $request ) {
		if ( ! class_exists( 'WP_2FA_DigiBayt_TOTP' ) ) {
			return new WP_Error( 'missing_provider', 'TOTP provider not found.', array( 'status' => 500 ) );
		}
		$params = $request->get_json_params();
		$code = $params['code'] ?? '';
		$user_id = get_current_user_id();
		$secret = get_user_meta( $user_id, '_wp_2fa_digibayt_totp_pending_secret', true );

		if ( ! $secret ) {
			return new WP_Error( 'no_pending_secret', 'No pending secret found.', array( 'status' => 400 ) );
		}

		$totp = new WP_2FA_DigiBayt_TOTP();
		if ( $totp->verify_code( $secret, $code ) ) {
			update_user_meta( $user_id, '_wp_2fa_digibayt_totp_secret', $secret );
			update_user_meta( $user_id, '_wp_2fa_digibayt_enabled', 'yes' );
			delete_user_meta( $user_id, '_wp_2fa_digibayt_totp_pending_secret' );
			return rest_ensure_response( array( 'success' => true ) );
		}

		return new WP_Error( 'invalid_code', 'Invalid verification code.', array( 'status' => 400 ) );
	}

	/**
	 * Generate Backup Codes
	 */
	public function generate_backup_codes() {
		if ( ! class_exists( 'WP_2FA_DigiBayt_Backup_Codes' ) ) {
			return new WP_Error( 'missing_provider', 'Backup codes provider not found.', array( 'status' => 500 ) );
		}
		$bc = new WP_2FA_DigiBayt_Backup_Codes();
		$user_id = get_current_user_id();
		$codes = $bc->generate_codes();
		$bc->save_codes( $user_id, $codes );

		return rest_ensure_response( array( 'codes' => $codes ) );
	}

	/**
	 * Check if user is administrator
	 */
	public function check_admin_permission() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check if user is logged in
	 */
	public function check_user_permission() {
		return is_user_logged_in();
	}

	/**
	 * Get global settings
	 */
	public function get_settings() {
		$defaults = array(
			'enforce_admins'  => false,
			'grace_period'    => 3,
			'remember_device' => 30,
		);
		$settings = get_option( 'wp_2fa_digibayt_settings', array() );
		$settings = wp_parse_args( $settings, $defaults );
		
		return rest_ensure_response( $settings );
	}

	/**
	 * Update global settings
	 */
	public function update_settings( $request ) {
		$params = $request->get_json_params();
		update_option( 'wp_2fa_digibayt_settings', $params );
		return rest_ensure_response( array( 'success' => true ) );
	}

	/**
	 * Get current user 2FA configuration
	 */
	public function get_user_config() {
		$user_id = get_current_user_id();
		$config = array(
			'enabled' => get_user_meta( $user_id, '_wp_2fa_digibayt_enabled', true ) === 'yes',
			'methods' => get_user_meta( $user_id, '_wp_2fa_digibayt_methods', true ),
		);
		return rest_ensure_response( $config );
	}
}
