<?php
/**
 * Backup Codes Provider Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_2FA_Auth_DigiBayt_Backup_Codes {

	/**
	 * Generate a set of backup codes
	 */
	public function generate_codes( $count = 10 ) {
		$codes = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$codes[] = wp_generate_password( 12, false );
		}
		return $codes;
	}

	/**
	 * Save codes for a user
	 */
	public function save_codes( $user_id, $codes ) {
		$hashed_codes = array_map( 'wp_hash_password', $codes );
		update_user_meta( $user_id, '_wp_2fa_auth_digibayt_backup_codes', $hashed_codes );
	}

	/**
	 * Verify and consume a backup code
	 */
	public function verify_code( $user_id, $code ) {
		$hashed_codes = get_user_meta( $user_id, '_wp_2fa_auth_digibayt_backup_codes', true );
		if ( ! is_array( $hashed_codes ) ) {
			return false;
		}

		foreach ( $hashed_codes as $index => $hashed_code ) {
			if ( wp_check_password( $code, $hashed_code ) ) {
				// Remove the used code
				unset( $hashed_codes[ $index ] );
				update_user_meta( $user_id, '_wp_2fa_auth_digibayt_backup_codes', array_values( $hashed_codes ) );
				return true;
			}
		}

		return false;
	}
}
