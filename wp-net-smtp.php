<?php
/*
Plugin Name: WordPress Net_SMTP
Description: Send SMTP mails with the Net_SMTP class included in your Debian GNU/Linux distribution. Simple! #nocrap
Author: Valerio Bozzolan
Author URI: https://boz.reyboz.it
Version: 0000000.0.0.-1.0
License: GPLv3 or later
*/

if( ! function_exists( 'wp_mail' ) ):

function error_wp_net_smtp( $title, $message ) {
	if( ! WP_DEBUG ) {
		$message = "Enable WP_DEBUG to show";
	}

	printf(
		"<p>Debianatore Mailoso. %s: %s.</p>\n",
		$title,
		esc_html( $message )
	);
}

function wp_mail( $to, $subject, $message, $additional_headers = '', $more = '' ) {
	require_once 'Net/SMTP.php';

	// Force array
	if( ! is_array( $to ) ) {
		$to = [ $to ];
	}

	$socket_options = [
		'ssl' => [
			'verify_peer_name' => false,
			'verify_peer'      => false
		]
	];

	if( ! ($smtp = new Net_SMTP( WP_NET_SMTP_HOST, WP_NET_SMTP_PORT, null, false, 0, $socket_options ) ) ) {
		error_wp_net_smtp(
			'Unable to instantiate Net_SMTP object',
			$smtp->getUserInfo()
		);
		return false;
	}

	WP_WP_DEBUG && $smtp->setDebug(true);

	if( PEAR::isError($e = $smtp->connect()) ) {
		error_wp_net_smtp(
			'Error connect',
			$e->getMessage()
		);
		return false;
	}

	if( PEAR::isError($e = $smtp->auth(WP_NET_SMTP_FROM, WP_NET_SMTP_PASS, WP_NET_SMTP_AUTH, true, '', true) ) ) {
		error_wp_net_smtp(
			'Error auth',
			$e->getMessage()
		);
		return false;
	}

	if( PEAR::isError( $smtp->mailFrom(WP_NET_SMTP_FROM) ) ) {
		error_wp_net_smtp(
			'Error set from',
			$res->getMessage()
		);
		return false;
	}

	foreach( $to as $i => $single_to ) {
		if( filter_var( $single_to, FILTER_VALIDATE_EMAIL ) === false ) {
			unset( $to[$i] );

			error_wp_net_smtp(
				'Wrong e-mail address stripped out',
				$single_to
			);
			continue;
		}

		if( PEAR::isError( $res = $smtp->rcptTo( $single_to ) ) ) {
			error_wp_net_smtp(
				'Error set To',
				$res->getMessage()
			);
			return false;
		}
	}

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

endif;

if( defined( 'WP_NET_SMTP_FIX_SSL' ) && WP_NET_SMTP_FIX_SSL ) {
	add_filter( 'https_local_ssl_verify', '__return_false' );
}
