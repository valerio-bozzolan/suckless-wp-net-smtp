<?php
/*
Plugin Name: Suckless WordPress Net_SMTP
Description: Send e-mails using the Net_SMTP library included in your Debian GNU/Linux distribution. Simple! #nocrap
Author: Valerio Bozzolan
Author URI: https://boz.reyboz.it
Version: 0000000.0.0.-1.0
License: GPLv3 or later
*/

/**
 * Show an useful error message in the case
 * the sysadmin has not read the fantastic manual.
 */
if( is_admin() && !defined( 'WP_NET_SMTP_HOST' ) ) {
	echo "Awesome! You installed wp-net-smtp! Now please define these in your wp-config.php: \n<br />".
		"define( 'WP_NET_SMTP_HOST', 'mail.example.com' );\n<br />".
		"define( 'WP_NET_SMTP_PORT', '465' );\n<br />".
		"define( 'WP_NET_SMTP_AUTH', 'PLAIN' );\n<br />".
		"define( 'WP_NET_SMTP_FROM', 'noreply@example.com' );\n<br />".
		"define( 'WP_NET_SMTP_PASS', 'super-secret' );";
}

/**
 * Your WordPress will explode if you installed
 * another extension providing the wp_mail() function.
 *
 * This wraps everything inside a big 'if' like a kebab.
 *
 * The end of this file should end with an 'endif'.
 *
 * It also does not provide any wp_mail() function if the configuration is
 * not partially completed.
 */
if( ! function_exists( 'wp_mail' ) && defined( 'WP_NET_SMTP_HOST' ) ):

/**
 * This supid function is useful to throw
 * a custom error message with a custom title and a message.
 *
 * @param string $title
 * @param string $message
 */
function error_wp_net_smtp( $title, $message ) {

	// no debug no party
	if( ! WP_DEBUG ) {
		$message = "Enable WP_DEBUG to show the specific error message";
	}

	// TODO: handle
	printf(
		"<p>Suckless WP Net SMTP error [%s]: %s.</p>\n",
		$title,
		esc_html( $message )
	);
}

/**
 * Define the WordPress function able to send an email to someone
 *
 * @param string $to
 * @param string $subject
 * @param string $message
 */
function wp_mail( $to, $subject, $message, $additional_headers = '', $more = '' ) {

	// the sysadmin has to install the php-net-smtp package
	//    sudo apt install php-net-smtp
	require_once 'Net/SMTP.php';

	// Force array
	if( ! is_array( $to ) ) {
		$to = [ $to ];
	}

	// tro to fix some stupid providers
	$socket_options = [
		'ssl' => [
			'verify_peer_name' => false,
			'verify_peer'      => false,
		],
	];

	// try the connection
	if( ! ($smtp = new Net_SMTP( WP_NET_SMTP_HOST, WP_NET_SMTP_PORT, null, false, 0, $socket_options ) ) ) {
		error_wp_net_smtp(
			'Unable to instantiate Net_SMTP object',
			$smtp->getUserInfo()
		);
		return false;
	}

	// eventually enable debug
	if( WP_DEBUG ) {
		$smtp->setDebug( true );
	}

	// try to connect
	if( PEAR::isError( $e = $smtp->connect() ) ) {
		error_wp_net_smtp(
			'Error connect',
			$e->getMessage()
		);
		return false;
	}

	// try to authenticate
	if( PEAR::isError( $e = $smtp->auth( WP_NET_SMTP_FROM, WP_NET_SMTP_PASS, WP_NET_SMTP_AUTH, true, '', true ) ) ) {
		error_wp_net_smtp(
			'Error auth',
			$e->getMessage()
		);
		return false;
	}

	// try to set the sender
	if( PEAR::isError( $smtp->mailFrom( WP_NET_SMTP_FROM ) ) ) {
		error_wp_net_smtp(
			'Error set from',
			$res->getMessage()
		);
		return false;
	}

	// process each single receiver
	foreach( $to as $i => $single_to ) {

		// skip eventually invalid addresses
		if( filter_var( $single_to, FILTER_VALIDATE_EMAIL ) === false ) {
			unset( $to[$i] );

			error_wp_net_smtp(
				'Wrong e-mail address stripped out',
				$single_to
			);
			continue;
		}

		// something goes wrong
		if( PEAR::isError( $res = $smtp->rcptTo( $single_to ) ) ) {
			error_wp_net_smtp(
				'Error set To',
				$res->getMessage()
			);
			return false;
		}
	}

	// no receiver no party
	if( count( $to ) === 0 ) {
		error_wp_net_smtp( 'No email sent', 'no addresses' );
		return false;
	}

	$headers = [
		'MIME-Version' => '1.0',
		'Subject'      => $subject,
		'To'           => implode( ',', $to ),
		'From'         => sprintf(
		                    '%s <%s>',
		                    get_bloginfo( 'name' ),
		                    WP_NET_SMTP_FROM
		               ),
		'Content-Type' => sprintf(
		                    'text/plain;charset=%s',
                                    get_settings( 'blog_charset' )
		               ),
		'X-Mailer'     => 'Net/SMTP.php via WordPress in Debian GNU/Linux',
	];

	// merge the headers
	$merge = [];
	foreach( $headers as $header => $value ) {
		$value = trim( $value );
		$merge[] = sprintf('%s: %s', $header, $value);
	}
	$headers = $additional_headers . implode( "\r\n" , $merge );

	$error = PEAR::isError( $smtp->data( "$headers\r\n$message" ) );

	$smtp->disconnect();

	return ! $error;
}

if( defined( 'WP_NET_SMTP_FIX_SSL' ) && WP_NET_SMTP_FIX_SSL ) {
	add_filter( 'https_local_ssl_verify', '__return_false' );
}

/**
 * This closes the initial big 'if' condition
 * wrapping the whole file as a kebab.
 */
endif;
