<?php
/**
 * Kontaktformular: REST-Endpunkt, der eine E-Mail verschickt.
 * Es wird nichts in der Datenbank gespeichert (DSGVO-freundlich).
 * Spamschutz: Honeypot, Mindest-Ausfüllzeit, Limit pro IP.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', function () {
	register_rest_route( 'ghostlike/v1', '/contact', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => 'gl_contact_submit',
	) );
} );

function gl_contact_submit( WP_REST_Request $req ) {
	$topic   = sanitize_key( $req->get_param( 'topic' ) );
	$name    = sanitize_text_field( (string) $req->get_param( 'name' ) );
	$email   = sanitize_email( (string) $req->get_param( 'email' ) );
	$message = sanitize_textarea_field( (string) $req->get_param( 'message' ) );
	$honey   = (string) $req->get_param( 'website' );
	$started = (int) $req->get_param( 't' );

	// Bots: Honeypot ausgefüllt oder zu schnell → still „ok“ antworten.
	if ( $honey !== '' || ( $started && ( time() - $started ) < 3 ) ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	if ( $name === '' || ! is_email( $email ) || mb_strlen( $message ) < 5 ) {
		return new WP_REST_Response( array( 'ok' => false, 'message' => 'Please fill in your name, a valid email and a message.' ), 400 );
	}
	if ( mb_strlen( $message ) > 5000 || mb_strlen( $name ) > 120 ) {
		return new WP_REST_Response( array( 'ok' => false, 'message' => 'Your message is too long.' ), 400 );
	}

	$ip_key = 'gl_contact_' . md5( ( $_SERVER['REMOTE_ADDR'] ?? '' ) . wp_salt() );
	$count  = (int) get_transient( $ip_key );
	if ( $count >= 5 ) {
		return new WP_REST_Response( array( 'ok' => false, 'message' => 'Too many messages. Please try again in an hour.' ), 429 );
	}
	set_transient( $ip_key, $count + 1, HOUR_IN_SECONDS );

	$topics = array( 'booking' => 'Booking', 'press' => 'Presse', 'general' => 'Allgemein' );
	$topic  = isset( $topics[ $topic ] ) ? $topic : 'general';
	$to     = gl_opt( 'contact_email_' . $topic ) ?: gl_opt( 'contact_email' ) ?: get_option( 'admin_email' );

	$subject = sprintf( '[ghostlike.de · %s] Nachricht von %s', $topics[ $topic ], $name );
	$body    = "Thema: {$topics[$topic]}\nName: {$name}\nE-Mail: {$email}\n\n{$message}\n\n— gesendet über das Kontaktformular auf " . home_url();
	$headers = array( 'Reply-To: ' . $name . ' <' . $email . '>' );

	if ( ! wp_mail( $to, $subject, $body, $headers ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'message' => 'Sending failed. Please write us directly via Instagram.' ), 500 );
	}
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}
