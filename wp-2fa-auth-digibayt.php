<?php
/**
 * Plugin Name:       2FA Auth by DigiBayt
 * Plugin URI:        https://digibayt.com/2fa-auth
 * Description:       Secure your site with TOTP, Backup Codes, and Security Audit Logs. Features local QR generation for maximum privacy.
 * Version:           1.0.0
 * Author:            DigiBayt
 * Author URI:        https://digibayt.com
 * Text Domain:       wp-2fa-auth-digibayt
 * Domain Path:       /languages
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 1. Constants & Path Detection (Clean & Standard)
define( 'WP_2FA_AUTH_DIGIBAYT_VERSION', '1.0.0' );
define( 'WP_2FA_AUTH_DIGIBAYT_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_2FA_AUTH_DIGIBAYT_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_2FA_AUTH_DIGIBAYT_CAPABILITY', 'manage_wp_2fa_auth_digibayt' );
define( 'WP_2FA_AUTH_DIGIBAYT_LOGS_CAPABILITY', 'view_wp_2fa_auth_digibayt_logs' );

// 2. Load Core Logic
$wp_2fa_core_files = array(
	'includes/class-wp-2fa-digibayt-auth.php',
	'includes/class-wp-2fa-digibayt-rest.php',
	'includes/providers/class-wp-2fa-digibayt-totp.php',
	'includes/providers/class-wp-2fa-digibayt-backup-codes.php',
);

foreach ( $wp_2fa_core_files as $wp_2fa_file ) {
    $wp_2fa_path = WP_2FA_AUTH_DIGIBAYT_PATH . $wp_2fa_file;
	if ( file_exists( $wp_2fa_path ) ) {
		require_once $wp_2fa_path;
	}
}

// 3. Initialize Backend
add_action( 'init', function() {
	// Ensure capabilities are granted to administrators (for already active installs)
	if ( is_admin() && current_user_can( 'manage_options' ) ) {
		$role = get_role( 'administrator' );
		if ( $role ) {
			if ( ! $role->has_cap( WP_2FA_AUTH_DIGIBAYT_CAPABILITY ) ) {
				$role->add_cap( WP_2FA_AUTH_DIGIBAYT_CAPABILITY );
			}
			if ( ! $role->has_cap( WP_2FA_AUTH_DIGIBAYT_LOGS_CAPABILITY ) ) {
				$role->add_cap( WP_2FA_AUTH_DIGIBAYT_LOGS_CAPABILITY );
			}
		}
	}

	if ( class_exists( 'WP_2FA_Auth_DigiBayt_Auth' ) ) {
		new WP_2FA_Auth_DigiBayt_Auth();
	}
	if ( class_exists( 'WP_2FA_Auth_DigiBayt_REST' ) ) {
		new WP_2FA_Auth_DigiBayt_REST();
	}
} );

// 4. Admin Menu Registration
add_action( 'admin_menu', 'wp_2fa_auth_digibayt_register_menu', 10 );
function wp_2fa_auth_digibayt_register_menu() {
	$icon = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"><path fill="currentColor" d="M10 1L3 4v6c0 5.55 3.84 10.74 7 12c3.16-1.26 7-6.45 7-12V4l-7-3zm0 10.94l-2.12 2.12l-1.41-1.41L8.59 10.5L6.47 8.38l1.41-1.41L10 9.09l2.12-2.12l1.41 1.41L11.41 10.5l2.12 2.12l-1.41 1.41L10 11.94z"/></svg>');

	add_menu_page(
		esc_html__( '2FA Auth', 'wp-2fa-auth-digibayt' ),
		esc_html__( '2FA Auth', 'wp-2fa-auth-digibayt' ),
		WP_2FA_AUTH_DIGIBAYT_CAPABILITY,
		'wp-2fa-auth',
		'wp_2fa_auth_digibayt_render_admin',
		$icon,
		80
	);
}

// 5. Admin Render
function wp_2fa_auth_digibayt_render_admin() {
	echo '<div class="wrap">';
	echo '<div id="wp-2fa-auth-digibayt-admin"><p>' . esc_html__( 'Loading Security Dashboard...', 'wp-2fa-auth-digibayt' ) . '</p></div>';
	echo '</div>';
}

// 6. Enqueue Scripts
add_action( 'admin_enqueue_scripts', 'wp_2fa_auth_digibayt_enqueue_assets' );
function wp_2fa_auth_digibayt_enqueue_assets( $hook ) {
	if ( strpos( $hook, 'wp-2fa-auth' ) === false ) {
		return;
	}

	$asset_file = WP_2FA_AUTH_DIGIBAYT_PATH . 'build/index.asset.php';
	if ( file_exists( $asset_file ) ) {
		$assets = require $asset_file;
		wp_enqueue_script(
			'wp-2fa-auth-digibayt-admin',
			WP_2FA_AUTH_DIGIBAYT_URL . 'build/index.js',
			$assets['dependencies'],
			$assets['version'],
			true
		);
		wp_enqueue_style(
			'wp-2fa-auth-digibayt-admin',
			WP_2FA_AUTH_DIGIBAYT_URL . 'build/style-index.css',
			array(),
			$assets['version']
		);
	}
}

// 7. Plugin Action Links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wp_2fa_auth_digibayt_action_links' );
function wp_2fa_auth_digibayt_action_links( $links ) {
	$settings_link = '<a href="' . admin_url( 'admin.php?page=wp-2fa-auth' ) . '">' . __( 'Settings', 'wp-2fa-auth-digibayt' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

// 8. Activation
register_activation_hook( __FILE__, 'wp_2fa_auth_digibayt_activate' );
function wp_2fa_auth_digibayt_activate() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_name      = $wpdb->prefix . 'digibayt_2fa_logs';

	$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		user_id bigint(20) NOT NULL,
		event_type varchar(50) NOT NULL,
		description text NOT NULL,
		ip_address varchar(45) NOT NULL,
		created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		PRIMARY KEY  (id),
		KEY user_id (user_id),
		KEY event_type (event_type)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	// Grant custom capabilities to administrator role
	$role = get_role( 'administrator' );
	if ( $role ) {
		$role->add_cap( WP_2FA_AUTH_DIGIBAYT_CAPABILITY );
		$role->add_cap( WP_2FA_AUTH_DIGIBAYT_LOGS_CAPABILITY );
	}
}
