# wordpress-smtp-mail-free-premium-plugin
A lightweight WordPress SMTP plugin that lets you configure SMTP settings and send test emails directly from the WordPress admin panel.

# Samanlı SMTP Mail

A lightweight and easy-to-use WordPress SMTP plugin that allows you to configure SMTP settings and send test emails directly from the WordPress admin panel.

## Features

- SMTP Host configuration
- SMTP Port configuration
- SMTP Authentication
- SSL / TLS encryption support
- Custom sender email address
- Custom sender name
- Send test emails directly from the dashboard
- Compatible with WordPress `wp_mail()`
- Lightweight and fast
- User-friendly interface

## Requirements

- WordPress 6.0 or later
- PHP 7.4 or later

## Installation

1. Download or clone this repository.
2. Upload the plugin folder to:

```
/wp-content/plugins/
```

3. Activate **Samanlı SMTP Mail** from the WordPress admin panel.
4. Open **Settings → SMTP Mail**.
5. Configure your SMTP settings.
6. Send a test email to verify your configuration.

## Frequently Asked Questions

### Which SMTP providers are supported?

Any SMTP provider that supports standard SMTP authentication, including:

- Gmail
- Outlook
- Microsoft 365
- Zoho Mail
- Yandex Mail
- cPanel Email
- Plesk Email
- Any custom SMTP server

### Does this plugin replace wp_mail()?

Yes. After activation, all WordPress emails are sent using your configured SMTP server.

## Changelog

### 1.0.0

- Initial Release

## License

GPL-2.0-or-later
