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
$wpdb->query( "DELETE FROM $wpdb->usermeta WHERE meta_key LIKE '_wp_2fa_auth_digibayt_%'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

// Drop custom table
$wp_2fa_table_name = $wpdb->prefix . 'digibayt_2fa_logs';
$wpdb->query( "DROP TABLE IF EXISTS $wp_2fa_table_name" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange

