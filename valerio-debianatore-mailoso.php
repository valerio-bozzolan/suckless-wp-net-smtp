<?php
/*
Plugin Name: Debianatore Mailoso di Valerio
Plugin URI: https://wordpress.org/support/topic/the-e-mail-could-not-be-sent/#post-179327
Description: Send SMTP mails with an include "Net/SMTP.php";... simple! #nocrap
Author: Valerio Bozzolan
Author URI: https://boz.reyboz.it
Version: 0000000.0.0.-1.0
License: GPLv3 or later
*/

if( ! function_exists('wp_mail') ):

function wp_mail($to, $subject, $message, $additional_headers = "", $more = "") {
	require_once "Net/SMTP.php";

	$socket_options = [
		'ssl' => [
			'verify_peer_name' => false,
			'verify_peer'      => false
		]
	];

	if( ! ($smtp = new Net_SMTP(DEBIANATORE_MAILOSO_HOST, DEBIANATORE_MAILOSO_PORT, null, false, 0, $socket_options)) ) {
		die("Unable to instantiate Net_SMTP object {$smtp->getUserInfo()} \n");
	}

	// $smtp->setDebug(true);

	if( PEAR::isError($e = $smtp->connect()) ) {
		die("Error connect {$e->getMessage()} ....... \n");
	}

	if( PEAR::isError($e = $smtp->auth(DEBIANATORE_MAILOSO_FROM, DEBIANATORE_MAILOSO_PASS, DEBIANATORE_MAILOSO_AUTH, true, '', true) ) ) {
		die("Error auth {$e->getMessage()} \n");
	}

	if( PEAR::isError( $smtp->mailFrom(DEBIANATORE_MAILOSO_FROM) ) ) {
		die("Unable to set sender \n");
	}

	if( PEAR::isError($res = $smtp->rcptTo($to)) ) {
		die("Unable to add recipient <$to_address>: " . $res->getMessage() . "\n");
	}

	$headers = [
		"MIME-Version" => "1.0",
		"Subject"      => "$subject",
		"To"           => $to,
		"From"         => sprintf(
		                    "%s <%s>",
		                    get_bloginfo('name'),
		                    DEBIANATORE_MAILOSO_FROM
		               ),
		"Content-Type" => sprintf(
		                    "text/plain;charset=%s",
                                    get_settings('blog_charset')
		               ),
		"X-Mailer"     => "Net/SMTP.php tramite plugin WP debianatore mailoso"
	];

	$merge = [];
	foreach($headers as $header=>$value) {
		$value = trim($value);
		$merge[] = sprintf("%s: %s", $header, $value);
	}
	$headers = $additional_headers . implode("\r\n", $merge);

	$error = PEAR::isError($smtp->data("$headers\r\n$message"));

	$smtp->disconnect();

	return ! $error;
}

endif;

add_filter( 'https_local_ssl_verify', '__return_false' );
