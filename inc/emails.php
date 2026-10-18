<?php
/**
 * E-Mails im Band-Look: Vorlage, bearbeitbare Texte, Einbindung aller WordPress-Mails.
 *
 * Textformat für Vorlagen (bewusst einfach):
 *   # Überschrift          → große Headline (erste Zeile eines Absatzes)
 *   **fett**               → fett
 *   [[Button-Text|{link}]] → Button (eigene Zeile)
 *   ---                    → Trennlinie (eigene Zeile)
 *   Leerzeile              → neuer Absatz
 *   {platzhalter}          → wird ersetzt
 */

defined( 'ABSPATH' ) || exit;

/* ================= VORLAGEN-REGISTER ================= */

function gl_email_defs() {
	$common = array(
		'band'    => 'Name der Seite (GHOSTLIKE)',
		'website' => 'Adresse der Website',
	);
	$defs = array(
		'portal_invite'    => array(
			'label'   => 'Einladung ins Member-Portal',
			'desc'    => 'Geht an neue Bandmitglieder (GHOSTLIKE → Bandmitglieder).',
			'active'  => function_exists( 'glp_portal_url' ),
			'vars'    => array( 'vorname' => 'Vorname', 'von' => 'Wer einlädt', 'link' => 'Persönlicher Link', 'benutzername' => 'Benutzername', 'gueltig_bis' => 'Link gültig bis' ) + $common,
			'sample'  => array( 'vorname' => 'Andreas', 'von' => 'Robin', 'link' => home_url( '/portal/?einladung=…' ), 'benutzername' => 'andreas', 'gueltig_bis' => wp_date( 'd.m.Y', strtotime( '+14 days' ) ) ),
			'subject' => '{von} lädt dich ins {band} Member-Portal ein',
			'body'    => "# Servus, {vorname}\n\n{von} hat dich ins {band} Member-Portal eingeladen. Dort findest du Proben und Termine, das Studio-Tool mit unserem Aufnahmestand und alles, was wir als Band planen.\n\n[[Passwort festlegen & loslegen|{link}]]\n\nDein Benutzername: **{benutzername}** (oder deine E-Mail-Adresse). Der Link ist bis {gueltig_bis} gültig und funktioniert einmal.",
		),
		'contact'          => array(
			'label'   => 'Kontaktformular (an euch)',
			'desc'    => 'Geht an die Empfänger aus Customizer → GHOSTLIKE → Kontakt. „Antworten“ geht direkt an die Person.',
			'active'  => true,
			'vars'    => array( 'thema' => 'Booking / Presse / Allgemein', 'name' => 'Name', 'email' => 'E-Mail', 'nachricht' => 'Nachricht' ) + $common,
			'sample'  => array( 'thema' => 'Booking', 'name' => 'Max Muster', 'email' => 'max@example.com', 'nachricht' => "Hey GHOSTLIKE,\nwir suchen noch eine Band für unser Festival im Juli. Habt ihr Zeit?\n\nViele Grüße\nMax" ),
			'subject' => '[{band} · {thema}] Nachricht von {name}',
			'body'    => "# Neue Nachricht\n\n**Thema:** {thema}\n**Von:** {name} · {email}\n\n---\n\n{nachricht}\n\n---\n\nEinfach auf diese Mail antworten, die Antwort geht direkt an {name}.",
		),
		'password_reset'   => array(
			'label'   => 'Passwort vergessen',
			'desc'    => 'Wenn jemand beim Login „Passwort vergessen?“ nutzt.',
			'active'  => true,
			'vars'    => array( 'vorname' => 'Vorname', 'benutzername' => 'Benutzername', 'link' => 'Link zum neuen Passwort' ) + $common,
			'sample'  => array( 'vorname' => 'Valentin', 'benutzername' => 'valentin', 'link' => wp_login_url() . '?action=rp&key=…' ),
			'subject' => 'Neues Passwort für {band}',
			'body'    => "# Passwort vergessen?\n\nHallo {vorname}, für deinen Zugang (**{benutzername}**) wurde ein neues Passwort angefordert.\n\n[[Neues Passwort festlegen|{link}]]\n\nWarst du das nicht? Dann ignorier diese Mail einfach, an deinem Passwort ändert sich nichts.",
		),
		'password_changed' => array(
			'label'   => 'Passwort wurde geändert',
			'desc'    => 'Bestätigung, nachdem jemand sein Passwort geändert hat.',
			'active'  => true,
			'vars'    => array( 'vorname' => 'Vorname', 'admin_email' => 'E-Mail des Admins' ) + $common,
			'sample'  => array( 'vorname' => 'Silvan', 'admin_email' => get_option( 'admin_email' ) ),
			'subject' => 'Dein Passwort wurde geändert',
			'body'    => "# Passwort geändert\n\nHallo {vorname}, das Passwort für deinen {band} Zugang wurde gerade geändert.\n\nWarst du das nicht? Dann melde dich bitte sofort bei {admin_email}.",
		),
		'email_changed'    => array(
			'label'   => 'E-Mail-Adresse wurde geändert',
			'desc'    => 'Geht an die alte Adresse, wenn jemand seine E-Mail ändert.',
			'active'  => true,
			'vars'    => array( 'vorname' => 'Vorname', 'neue_email' => 'Neue E-Mail-Adresse', 'admin_email' => 'E-Mail des Admins' ) + $common,
			'sample'  => array( 'vorname' => 'Alexander', 'neue_email' => 'alex@example.com', 'admin_email' => get_option( 'admin_email' ) ),
			'subject' => 'Deine E-Mail-Adresse wurde geändert',
			'body'    => "# E-Mail geändert\n\nHallo {vorname}, die E-Mail-Adresse deines {band} Zugangs ist jetzt **{neue_email}**. Ab sofort kommen alle Mails dorthin.\n\nWarst du das nicht? Dann melde dich bitte sofort bei {admin_email}.",
		),
		'new_user'         => array(
			'label'   => 'Neues Benutzerkonto (WordPress)',
			'desc'    => 'Wenn jemand direkt unter Benutzer → Neu angelegt wird. Für Bandmitglieder lieber die Einladung nutzen.',
			'active'  => true,
			'vars'    => array( 'vorname' => 'Vorname', 'benutzername' => 'Benutzername', 'link' => 'Link zum Passwort' ) + $common,
			'sample'  => array( 'vorname' => 'Silvan', 'benutzername' => 'silvan', 'link' => wp_login_url() . '?action=rp&key=…' ),
			'subject' => 'Dein Zugang zu {band}',
			'body'    => "# Willkommen, {vorname}\n\nFür dich wurde ein Zugang bei {band} angelegt. Dein Benutzername: **{benutzername}**\n\n[[Passwort festlegen|{link}]]",
		),
	);
	return array_filter( $defs, fn( $d ) => $d['active'] );
}

function gl_email_tpl( $id ) {
	$defs  = gl_email_defs();
	$saved = (array) get_option( 'gl_emails', array() );
	if ( ! isset( $defs[ $id ] ) ) {
		return null;
	}
	return array(
		'subject' => trim( (string) ( $saved[ $id ]['subject'] ?? '' ) ) ?: $defs[ $id ]['subject'],
		'body'    => trim( (string) ( $saved[ $id ]['body'] ?? '' ) ) ?: $defs[ $id ]['body'],
	);
}

function gl_email_common_vars() {
	return array(
		'band'    => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		'website' => home_url( '/' ),
	);
}

function gl_email_fill( $str, $vars ) {
	$vars = $vars + gl_email_common_vars();
	return preg_replace_callback( '/\{([a-z_]+)\}/', fn( $m ) => array_key_exists( $m[1], $vars ) ? (string) $vars[ $m[1] ] : $m[0], (string) $str );
}

/* ================= DARSTELLUNG ================= */

const GL_MAIL_C = array( 'bg' => '#0A0B0A', 'card' => '#1A1D1B', 'line' => '#2B302D', 'text' => '#C9CEC6', 'head' => '#ECEEE8', 'mute' => '#8A918B', 'patina' => '#7C9A85' );

/** Vorlagen-Text → HTML-Inhalt (alles escaped, nur unser Mini-Format wird umgesetzt). */
function gl_email_body_html( $body, $vars ) {
	$c    = GL_MAIL_C;
	$font = "Arial, Helvetica, sans-serif";
	$out  = '';
	foreach ( preg_split( "/\n\s*\n/", str_replace( "\r", '', (string) $body ) ) as $block ) {
		$block = trim( $block );
		if ( $block === '' ) {
			continue;
		}
		if ( $block === '---' ) {
			$out .= '<tr><td style="padding:8px 36px"><div style="height:1px;background:' . $c['line'] . ';line-height:1px;font-size:1px">&nbsp;</div></td></tr>';
			continue;
		}
		if ( preg_match( '/^\[\[(.+?)\|(.+?)\]\]$/', $block, $m ) ) {
			$url   = esc_url( gl_email_fill( trim( $m[2] ), $vars ) );
			$label = esc_html( gl_email_fill( trim( $m[1] ), $vars ) );
			$out  .= '<tr><td style="padding:14px 36px 18px"><table role="presentation" cellpadding="0" cellspacing="0"><tr><td style="background:' . $c['patina'] . '"><a href="' . $url . '" style="display:inline-block;padding:15px 24px;font-family:' . $font . ';font-size:14px;font-weight:bold;letter-spacing:.5px;text-transform:uppercase;color:' . $c['bg'] . ';text-decoration:none">' . $label . '</a></td></tr></table></td></tr>';
			continue;
		}
		$head = '';
		if ( str_starts_with( $block, '# ' ) ) {
			[ $first, $rest ] = array_pad( explode( "\n", $block, 2 ), 2, '' );
			$head  = esc_html( gl_email_fill( substr( $first, 2 ), $vars ) );
			$block = trim( $rest );
			$out  .= '<tr><td style="padding:6px 36px 4px;font-family:\'Arial Black\',Arial,Helvetica,sans-serif;font-size:32px;line-height:1.05;font-weight:900;text-transform:uppercase;letter-spacing:-.5px;color:' . $c['head'] . '">' . $head . '</td></tr>';
			if ( $block === '' ) {
				continue;
			}
		}
		$html = esc_html( gl_email_fill( $block, $vars ) );
		$html = preg_replace( '/\*\*(.+?)\*\*/s', '<strong style="color:' . $c['head'] . '">$1</strong>', $html );
		$html = make_clickable( $html );
		$html = preg_replace( '/<a href=/', '<a style="color:' . $c['head'] . ';text-decoration:underline" href=', $html );
		$out .= '<tr><td style="padding:12px 36px;font-family:' . $font . ';font-size:16px;line-height:1.6;color:' . $c['text'] . '">' . nl2br( $html ) . '</td></tr>';
	}
	return $out;
}

/** Komplette Mail im CI: Logo, Karte mit Patina-Kante, Fuß. */
function gl_email_wrap( $rows, $preheader = '', $foot = '' ) {
	$c     = GL_MAIL_C;
	$logo  = GL_URI . '/assets/img/email-logo.png';
	$name  = esc_html( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$host  = esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) );
	$foot  = $foot ?: 'Diese Mail wurde automatisch von ' . $host . ' verschickt.';
	return '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="color-scheme" content="dark"><meta name="supported-color-schemes" content="dark"><title>' . $name . '</title></head>'
		. '<body style="margin:0;padding:0;background:' . $c['bg'] . '"><!--gl-email-->'
		. '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:' . $c['bg'] . '">' . esc_html( $preheader ) . '</div>'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:' . $c['bg'] . '"><tr><td align="center" style="padding:32px 12px">'
		. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px">'
		. '<tr><td style="padding:0 4px 22px"><a href="' . esc_url( home_url( '/' ) ) . '" style="text-decoration:none"><img src="' . esc_url( $logo ) . '" width="200" alt="' . $name . '" style="display:block;border:0;width:200px;height:auto;color:' . $c['head'] . ';font-family:Arial,sans-serif;font-size:28px;font-weight:bold"></a></td></tr>'
		. '<tr><td style="background:' . $c['card'] . ';border-left:4px solid ' . $c['patina'] . '"><table role="presentation" width="100%" cellpadding="0" cellspacing="0">'
		. '<tr><td style="padding:30px 36px 0;font-family:\'Courier New\',monospace;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:' . $c['mute'] . '">' . $name . ' · ' . $host . '</td></tr>'
		. $rows
		. '<tr><td style="height:24px;line-height:24px;font-size:1px">&nbsp;</td></tr></table></td></tr>'
		. '<tr><td style="padding:18px 4px;font-family:\'Courier New\',monospace;font-size:11px;line-height:1.6;letter-spacing:.5px;color:' . $c['mute'] . '">' . esc_html( $foot ) . '<br><a href="' . esc_url( home_url( '/' ) ) . '" style="color:' . $c['mute'] . '">' . $host . '</a></td></tr>'
		. '</table></td></tr></table></body></html>';
}

/** Text-Version (für Mail-Programme ohne HTML). */
function gl_email_text( $body, $vars ) {
	$t = gl_email_fill( $body, $vars );
	$t = preg_replace( '/^# /m', '', $t );
	$t = preg_replace( '/\[\[(.+?)\|(.+?)\]\]/', '$1: $2', $t );
	$t = str_replace( array( '**', "\n---\n" ), array( '', "\n――――――――\n" ), $t );
	return $t . "\n\n— " . wp_parse_url( home_url(), PHP_URL_HOST );
}

/** Fertige Mail: [subject, html, text]. */
function gl_email( $id, $vars = array() ) {
	$tpl = gl_email_tpl( $id );
	if ( ! $tpl ) {
		return null;
	}
	$subject = wp_strip_all_tags( gl_email_fill( $tpl['subject'], $vars ) );
	$plain   = gl_email_text( $tpl['body'], $vars );
	$pre     = mb_substr( trim( preg_replace( '/\s+/', ' ', preg_replace( '/^# .*$/m', '', $plain ) ) ), 0, 110 );
	return array(
		'subject' => $subject,
		'html'    => gl_email_wrap( gl_email_body_html( $tpl['body'], $vars ), $pre ),
		'text'    => $plain,
	);
}

/** Senden mit HTML + Text-Version. */
function gl_send_email( $to, $id, $vars = array(), $headers = array() ) {
	$m = gl_email( $id, $vars );
	if ( ! $m ) {
		return false;
	}
	gl_mail_alt( $m['html'], $m['text'] );
	return wp_mail( $to, $m['subject'], $m['html'], array_merge( array( 'Content-Type: text/html; charset=UTF-8' ), (array) $headers ) );
}

/* ================= ALLE MAILS IM CI ================= */

/** Text-Version zu einer HTML-Mail merken (wird in phpmailer_init gesetzt). */
function gl_mail_alt( $html = null, $text = null ) {
	static $map = array();
	if ( $html !== null && $text !== null ) {
		$map[ md5( $html ) ] = $text;
		return null;
	}
	return $html !== null ? ( $map[ md5( $html ) ] ?? null ) : null;
}

// Absender
add_filter( 'wp_mail_from_name', function ( $name ) {
	$n = trim( (string) get_option( 'gl_mail_from_name', '' ) );
	return $n !== '' ? $n : ( $name === 'WordPress' ? wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) : $name );
} );
add_filter( 'wp_mail_from', function ( $from ) {
	$f = sanitize_email( (string) get_option( 'gl_mail_from', '' ) );
	return is_email( $f ) ? $f : $from;
} );

// Jede andere Text-Mail (Updates, Benachrichtigungen, Plugins) in die CI-Vorlage packen
add_filter( 'wp_mail', function ( $args ) {
	$headers = is_array( $args['headers'] ) ? $args['headers'] : array_filter( explode( "\n", str_replace( "\r", '', (string) $args['headers'] ) ) );
	$is_html = (bool) preg_grep( '/content-type:\s*text\/html/i', $headers );
	$msg     = (string) $args['message'];

	if ( strpos( $msg, '<!--gl-email-->' ) !== false ) {
		if ( ! $is_html ) {
			$headers[] = 'Content-Type: text/html; charset=UTF-8';
		}
		$args['headers'] = $headers;
		return $args;
	}
	if ( $is_html || stripos( $msg, '<html' ) !== false || get_option( 'gl_mail_wrap_all', '1' ) !== '1' ) {
		return $args; // fremde HTML-Mails nicht anfassen
	}
	$text = $msg;
	$html = esc_html( $text );
	$html = make_clickable( $html );
	$html = preg_replace( '/<a href=/', '<a style="color:' . GL_MAIL_C['head'] . ';text-decoration:underline" href=', $html );
	$rows = '<tr><td style="padding:6px 36px 4px;font-family:\'Arial Black\',Arial,Helvetica,sans-serif;font-size:22px;line-height:1.15;font-weight:900;text-transform:uppercase;color:' . GL_MAIL_C['head'] . '">' . esc_html( wp_strip_all_tags( (string) $args['subject'] ) ) . '</td></tr>'
		. '<tr><td style="padding:12px 36px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:' . GL_MAIL_C['text'] . '">' . nl2br( $html ) . '</td></tr>';
	$args['message'] = gl_email_wrap( $rows, mb_substr( preg_replace( '/\s+/', ' ', $text ), 0, 110 ) );
	gl_mail_alt( $args['message'], $text );
	$headers[]       = 'Content-Type: text/html; charset=UTF-8';
	$args['headers'] = $headers;
	return $args;
}, 99 );

add_action( 'phpmailer_init', function ( $phpmailer ) {
	$alt = gl_mail_alt( $phpmailer->Body );
	if ( $alt ) {
		$phpmailer->AltBody = $alt;
	}
} );

/* ================= WORDPRESS-STANDARDMAILS ERSETZEN ================= */

function gl_user_first( $user ) {
	return trim( (string) $user->first_name ) ?: $user->display_name;
}

add_filter( 'retrieve_password_notification_email', function ( $email, $key, $user_login, $user ) {
	$link = network_site_url( 'wp-login.php?action=rp&key=' . $key . '&login=' . rawurlencode( $user_login ), 'login' );
	$m    = gl_email( 'password_reset', array( 'vorname' => gl_user_first( $user ), 'benutzername' => $user_login, 'link' => $link ) );
	gl_mail_alt( $m['html'], $m['text'] );
	$email['subject'] = $m['subject'];
	$email['message'] = $m['html'];
	$email['headers'] = array( 'Content-Type: text/html; charset=UTF-8' );
	return $email;
}, 10, 4 );

add_filter( 'wp_new_user_notification_email', function ( $email, $user ) {
	preg_match( '#https?://\S*wp-login\.php\?\S+#', (string) $email['message'], $mm );
	$link = $mm ? rtrim( $mm[0], '>' ) : wp_login_url();
	$m    = gl_email( 'new_user', array( 'vorname' => gl_user_first( $user ), 'benutzername' => $user->user_login, 'link' => $link ) );
	gl_mail_alt( $m['html'], $m['text'] );
	$email['subject'] = $m['subject'];
	$email['message'] = $m['html'];
	$email['headers'] = array( 'Content-Type: text/html; charset=UTF-8' );
	return $email;
}, 10, 2 );

add_filter( 'password_change_email', function ( $email, $user ) {
	$u = get_userdata( $user['ID'] );
	$m = gl_email( 'password_changed', array( 'vorname' => $u ? gl_user_first( $u ) : '', 'admin_email' => get_option( 'admin_email' ) ) );
	gl_mail_alt( $m['html'], $m['text'] );
	$email['subject'] = $m['subject'];
	$email['message'] = $m['html'];
	$email['headers'] = array( 'Content-Type: text/html; charset=UTF-8' );
	return $email;
}, 10, 2 );

add_filter( 'email_change_email', function ( $email, $user, $userdata ) {
	$u = get_userdata( $user['ID'] );
	$m = gl_email( 'email_changed', array( 'vorname' => $u ? gl_user_first( $u ) : '', 'neue_email' => $userdata['user_email'] ?? '', 'admin_email' => get_option( 'admin_email' ) ) );
	gl_mail_alt( $m['html'], $m['text'] );
	$email['subject'] = $m['subject'];
	$email['message'] = $m['html'];
	$email['headers'] = array( 'Content-Type: text/html; charset=UTF-8' );
	return $email;
}, 10, 3 );
