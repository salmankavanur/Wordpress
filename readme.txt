=== WP 2FA Auth by DigiBayt ===
Contributors: digibayt
Tags: 2fa, two-factor authentication, security, totp, backup codes, login security
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A premium, full-featured Two-Factor Authentication (2FA) plugin for WordPress. Secure your site with TOTP (Google Authenticator, Authy), Backup Codes, and Audit Logging.

== Description ==

Secure your WordPress website with industry-standard Two-Factor Authentication. **WP 2FA Auth by DigiBayt** provides a robust security layer without relying on external APIs for QR code generation, ensuring maximum privacy and reliability.

= Key Features =
* **TOTP Support**: Compatible with Google Authenticator, Authy, Microsoft Authenticator, and more.
* **Privacy Focused**: QR codes are generated locally in your browser using `qrcode.react`. No sensitive data is ever sent to external services.
* **Backup Codes**: Generate emergency recovery codes in case you lose access to your mobile device.
* **Audit Logs**: Comprehensive tracking of security events, including 2FA activations, login attempts, and failed verification events.
* **Admin Enforcement**: Require all administrators to use 2FA to protect the most sensitive parts of your site.
* **Modern Interface**: A clean, React-powered dashboard integrated directly into the WordPress admin.
* **Fast & Responsive**: Optimized with cache-busting and immediate state updates for a smooth user experience.

== Installation ==

1. Upload the `wp-2fa-auth-digibayt.zip` file through the WordPress 'Plugins' menu or manually to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the **WP 2FA Auth** menu in your sidebar to configure settings.
4. Go to the **My Security** tab to set up 2FA for your account.

== Frequently Asked Questions ==

= Does this plugin send data to external servers? =
No. For maximum privacy, all QR code generation and verification logic are handled locally on your server and browser.

= What happens if I lose my phone? =
You can use one of your pre-generated **Backup Codes** to log in and reset your 2FA settings.

== Screenshots ==

1. The Security Dashboard showing real-time statistics.
2. The User Setup tab with local QR code generation.
3. Global Settings for site-wide 2FA enforcement.
4. Detailed Audit Logs for security monitoring.

== Changelog ==

= 1.0.0 =
* Initial public release.
* TOTP and Backup Codes support.
* Audit logging system.
* React-powered management interface.
