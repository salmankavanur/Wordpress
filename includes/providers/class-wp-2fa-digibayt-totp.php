<?php
/**
 * TOTP Provider Class
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_2FA_DigiBayt_TOTP {

	/**
	 * Generate a new TOTP secret
	 */
	public function generate_secret() {
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32 alphabet
		$secret = '';
		for ( $i = 0; $i < 16; $i++ ) {
			$secret .= $chars[ wp_rand( 0, 31 ) ];
		}
		return $secret;
	}

	/**
	 * Calculate TOTP code for a secret
	 */
	public function calculate_code( $secret, $time_slice = null ) {
		if ( null === $time_slice ) {
			$time_slice = floor( time() / 30 );
		}

		$secret_key = $this->base32_decode( $secret );

		// Pack time into binary string
		$time = pack( 'N', $time_slice );
		$time = str_pad( $time, 8, chr( 0 ), STR_PAD_LEFT );

		// Hash with HMAC-SHA1
		$hash = hash_hmac( 'sha1', $time, $secret_key, true );

		// Dynamic truncation
		$offset = ord( $hash[19] ) & 0xf;
		$hash_part = substr( $hash, $offset, 4 );

		// Unpack binary value
		$value = unpack( 'N', $hash_part );
		$value = $value[1];
		$value = $value & 0x7fffffff;

		return str_pad( $value % 1000000, 6, '0', STR_PAD_LEFT );
	}

	/**
	 * Verify a TOTP code
	 */
	public function verify_code( $secret, $code, $discrepancy = 1 ) {
		$current_slice = floor( time() / 30 );

		for ( $i = -$discrepancy; $i <= $discrepancy; $i++ ) {
			if ( $this->calculate_code( $secret, $current_slice + $i ) === $code ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get QR Code URL (Placeholder for now to avoid DNS issues)
	 */
	public function get_qr_code_url( $name, $secret, $issuer = 'WP 2FA DigiBayt' ) {
		$url = 'otpauth://totp/' . rawurlencode( $issuer . ':' . $name ) . '?secret=' . $secret . '&issuer=' . rawurlencode( $issuer );
		// Returning the raw URL - the frontend can use a local QR library in the next phase
		return $url; 
	}

	/**
	 * Base32 Decode
	 */
	private function base32_decode( $base32 ) {
		$base32 = strtoupper( $base32 );
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$map = array_flip( str_split( $chars ) );
		
		$binary = '';
		foreach ( str_split( $base32 ) as $char ) {
			if ( ! isset( $map[ $char ] ) ) continue;
			$binary .= str_pad( decbin( $map[ $char ] ), 5, '0', STR_PAD_LEFT );
		}

		$data = '';
		foreach ( str_split( $binary, 8 ) as $byte ) {
			if ( strlen( $byte ) < 8 ) continue;
			$data .= chr( bindec( $byte ) );
		}

		return $data;
	}
}
