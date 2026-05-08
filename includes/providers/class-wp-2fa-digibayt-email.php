<?php
/**
 * Email OTP Provider Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_2FA_DigiBayt_Email {

	/**
	 * Send an OTP via email
	 */
	public function send_otp( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}

		$otp = wp_generate_password( 6, false, false );
		$hashed_otp = wp_hash_password( $otp );
		
		// Store OTP in user meta with expiration (10 minutes)
		update_user_meta( $user_id, '_wp_2fa_digibayt_email_otp', array(
			'hash' => $hashed_otp,
			'expires' => time() + 600,
		) );

		$subject = sprintf( __( 'Your %s Verification Code', 'wp-2fa-digibayt' ), get_bloginfo( 'name' ) );
		$message = sprintf( __( 'Your verification code is: %s', 'wp-2fa-digibayt' ), $otp );
		
		return wp_mail( $user->user_email, $subject, $message );
	}

	/**
	 * Verify the email OTP
	 */
	public function verify_otp( $user_id, $code ) {
		$data = get_user_meta( $user_id, '_wp_2fa_digibayt_email_otp', true );
		
		if ( ! is_array( $data ) || time() > $data['expires'] ) {
			return false;
		}

		if ( wp_check_password( $code, $data['hash'] ) ) {
			delete_user_meta( $user_id, '_wp_2fa_digibayt_email_otp' );
			return true;
		}

		return false;
	}
}
