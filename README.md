# WP 2FA Auth by DigiBayt

A premium-quality, full-featured **Two-Factor Authentication (2FA)** plugin for WordPress. Secure your site with TOTP (Google Authenticator, Authy), Backup Codes, and robust Security Audit Logging.

[![License](https://img.shields.io/badge/License-GPL--2.0--or--later-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress](https://img.shields.io/badge/WordPress-%3E%3D5.8-blue.svg)](https://wordpress.org)

## 🛡️ Key Features

- **📱 Universal TOTP Support**: Compatible with Google Authenticator, Authy, Microsoft Authenticator, and all standard TOTP apps.
- **🔒 Privacy-First QR Generation**: QR codes are generated locally in your browser. No external API calls, keeping your secrets strictly on your server.
- **🔑 Secure Backup Codes**: Generate and store emergency recovery codes to prevent account lockout.
- **📝 Security Audit Logs**: Track 2FA activations, login attempts, IP addresses, and failed verification events.
- **⚙️ Global Enforcement**: Force administrators to enable 2FA with customizable grace periods.
- **⚛️ Modern UI**: A sleek, responsive admin dashboard built with React and official WordPress components.

## 📦 Installation

### From Zip (Recommended)
1. Download the latest `wp-2fa-auth-digibayt.zip`.
2. In your WordPress Admin, go to **Plugins > Add New > Upload Plugin**.
3. Choose the zip file and click **Install Now**, then **Activate**.

### For Developers
1. Clone this repository into your `/wp-content/plugins/` directory.
2. Run `npm install` to install dependencies.
3. Run `npm run build` to compile the frontend assets.
4. Activate the plugin via the WordPress dashboard.

## 💻 Development Commands

- `npm start`: Runs the build process in watch mode.
- `npm run build`: Compiles production-optimized assets.
- `npm run plugin-zip`: Packages the plugin into an official-standard `.zip` file for distribution.

## 📄 License

This project is licensed under the GPLv2 or later.

---
Built with ❤️ by [DigiBayt](https://digibayt.com)
