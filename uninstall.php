<?php
/**
 * Uninstall Plugin - Cleanup
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete settings
delete_option( 'wp_2fa_digibayt_settings' );

// Delete user meta
global $wpdb;
$wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE '_wp_2fa_digibayt_%'" );

// Drop custom table
$table_name = $wpdb->prefix . 'digibayt_2fa_logs';
$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
