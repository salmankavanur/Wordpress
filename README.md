# WP 2FA Auth by DigiBayt

A premium, full-featured Two-Factor Authentication (2FA) plugin for WordPress. Secure your site with TOTP (Google Authenticator, Authy), Backup Codes, and Audit Logging.

## Features
- **TOTP Support**: Local QR code generation (no external API calls for privacy).
- **Backup Codes**: Emergency access codes for account recovery.
- **Audit Logs**: Track security events like login attempts and 2FA activations.
- **Admin Enforcement**: Force administrators to use 2FA.
- **Modern Dashboard**: Built with React and WordPress @wordpress/components.
- **Offline Capable**: Works perfectly in local/intranet environments.

## Installation
1. Clone this repository into your `/wp-content/plugins/` directory.
2. Run `npm install` to install dependencies.
3. Run `npm run build` to compile the frontend assets.
4. Activate the plugin through the 'Plugins' menu in WordPress.

## Development
- `npm start`: Starts the build process in watch mode.
- `npm run build`: Builds the production version of the dashboard.
- `npm run plugin-zip`: Generates a distributable .zip file.

## License
GPLv2 or later.
