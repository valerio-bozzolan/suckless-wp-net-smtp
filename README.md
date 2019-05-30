# Suckless WordPress Net_SMTP bridge

It just allows your WordPress installation to send e-mails through a MTA, using the Net_SMTP library that it's already packaged into your distribution.

This plugin is just ~133 lines of code. The missing of a feature is a feature.

## Installation

	sudo apt install php-net-smtp

Then go into your `wp-config.php` and fill these:

	define( 'WP_NET_SMTP_HOST', 'mail.example.com' );
	define( 'WP_NET_SMTP_PORT', '465' );
	define( 'WP_NET_SMTP_AUTH', 'PLAIN' );
	define( 'WP_NET_SMTP_FROM', 'noreply@example.com' );
	define( 'WP_NET_SMTP_PASS', 'super-secret' );

Then proceed to install this plugin as every WordPress plugin. You know.
