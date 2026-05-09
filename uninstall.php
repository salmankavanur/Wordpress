<?php
/**
 * Uninstall Plugin - Cleanup
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete settings
delete_option( 'wp_2fa_auth_digibayt_settings' );

// Delete user meta
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE %s", '_wp_2fa_auth_digibayt_%' ) );

// Drop custom table
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "digibayt_2fa_logs" );




