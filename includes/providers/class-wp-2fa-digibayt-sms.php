<?php
/**
 * SMS OTP Provider Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_2FA_DigiBayt_SMS {

	/**
	 * Send an OTP via SMS
	 */
	public function send_otp( $user_id ) {
		$phone = get_user_meta( $user_id, '_wp_2fa_digibayt_phone', true );
		if ( ! $phone ) {
			return false;
		}

		$otp = wp_generate_password( 6, false, false );
		$hashed_otp = wp_hash_password( $otp );
		
		update_user_meta( $user_id, '_wp_2fa_digibayt_sms_otp', array(
			'hash' => $hashed_otp,
			'expires' => time() + 600,
		) );

		// Implementation for SMS Gateway (e.g. Twilio) would go here
		// Example:
		// $message = sprintf( __( 'Your verification code is: %s', 'wp-2fa-digibayt' ), $otp );
		// $this->gateway_send( $phone, $message );

		return true; // Simulating success for now
	}

	/**
	 * Verify the SMS OTP
	 */
	public function verify_otp( $user_id, $code ) {
		$data = get_user_meta( $user_id, '_wp_2fa_digibayt_sms_otp', true );
		
		if ( ! is_array( $data ) || time() > $data['expires'] ) {
			return false;
		}

		if ( wp_check_password( $code, $data['hash'] ) ) {
			delete_user_meta( $user_id, '_wp_2fa_digibayt_sms_otp' );
			return true;
		}

		return false;
	}
}
