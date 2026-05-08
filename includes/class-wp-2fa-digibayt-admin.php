<?php
/**
 * Admin Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_2FA_DigiBayt_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add Admin Menu Page
	 */
	public function add_menu_page() {
		add_menu_page(
			__( 'WP 2FA Auth', 'wp-2fa-digibayt' ),
			__( 'WP 2FA Auth', 'wp-2fa-digibayt' ),
			WP_2FA_Auth_DigiBayt::get_admin_capability(),
			'digibayt-2fa-settings', // Unique Slug
			array( $this, 'render_admin_page' ),
			'dashicons-shield-lock',
			80
		);
	}

	/**
	 * Render Admin Page (React mount point)
	 */
	public function render_admin_page() {
		if ( ! current_user_can( WP_2FA_Auth_DigiBayt::get_admin_capability() ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'wp-2fa-digibayt' ) );
		}

		echo '<div id="wp-2fa-digibayt-admin"></div>';
		echo '<noscript><div class="notice notice-warning inline"><p>' . esc_html__( 'The WP 2FA Auth interface requires JavaScript. Please enable JavaScript or refresh the page after build files are present.', 'wp-2fa-digibayt' ) . '</p></div></noscript>';
	}

	/**
	 * Enqueue Scripts and Styles
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'toplevel_page_digibayt-2fa-settings' !== $hook ) {
			return;
		}

		$asset_file = WP_2FA_DIGIBAYT_PATH . 'build/index.asset.php';
		if ( file_exists( $asset_file ) ) {
			$assets = require $asset_file;
			wp_enqueue_script(
				'wp-2fa-digibayt-admin',
				WP_2FA_DIGIBAYT_URL . 'build/index.js',
				$assets['dependencies'],
				$assets['version'],
				true
			);
			wp_enqueue_style(
				'wp-2fa-digibayt-admin',
				WP_2FA_DIGIBAYT_URL . 'build/index.css',
				array(),
				$assets['version']
			);
		}
	}
}
