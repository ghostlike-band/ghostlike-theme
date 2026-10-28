<?php
/**
 * Einmalige Anpassungen nach Theme-Updates. Ändert nur Demo-Inhalte (_gl_demo),
 * nie Inhalte, die ihr selbst angelegt habt.
 */

defined( 'ABSPATH' ) || exit;

const GL_CONTENT_VERSION = 4;

add_action( 'init', function () {
	$v = (int) get_option( 'gl_content_version', 1 );
	if ( $v >= GL_CONTENT_VERSION ) {
		return;
	}

	if ( $v < 2 && get_option( 'gl_demo_imported' ) ) {
		$demo = fn( $type, $title ) => get_posts( array(
			'post_type' => $type, 'post_status' => 'any', 'title' => $title,
			'meta_key' => '_gl_demo', 'posts_per_page' => 1, 'fields' => 'ids',
		) )[0] ?? 0;

		// EP: echte Reihenfolge, Musikvideos, Release-Datum; Bandcamp/YouTube raus (Streaming-Buttons kommen über die neuen Felder)
		if ( $ep = $demo( 'gl_release', 'The Quiet Collapse' ) ) {
			$covers = array();
			foreach ( gl_pipe_rows( gl_meta( $ep, 'tracks' ) ) as $t ) {
				$covers[ sanitize_title( $t[0] ) ] = $t[3] ?? '';
			}
			update_post_meta( $ep, '_gl_tracks', gl_demo_tracks( $covers['deep-blue-sea'] ?? '', $covers['nothings-okay'] ?? '', $covers['the-price-we-pay'] ?? '' ) );
			update_post_meta( $ep, '_gl_kicker', 'EP · Era I · 5 songs · 16.01.2026' );
			delete_post_meta( $ep, '_gl_bandcamp' );
			delete_post_meta( $ep, '_gl_youtube' );
		}
		if ( $slide = $demo( 'gl_slide', 'Video – Nothing’s Okay' ) ) {
			update_post_meta( $slide, '_gl_button_url', GL_VIDEOS['nothings-okay'] );
			update_post_meta( $slide, '_gl_kicker', 'Official video' );
		}
		// Handy-Varianten: Hochformat-Bandfoto für die erste Slide und die Bio
		$portrait = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_gl_demo', 'title' => 'GHOSTLIKE – Hochformat', 'posts_per_page' => 1, 'fields' => 'ids' ) )[0] ?? 0;
		if ( $portrait ) {
			if ( ( $band = $demo( 'gl_slide', 'Band' ) ) && ! get_post_meta( $band, '_gl_image_mobile', true ) ) {
				update_post_meta( $band, '_gl_image_mobile', wp_get_attachment_url( $portrait ) );
			}
			if ( ! get_theme_mod( 'gl_band_image_mobile' ) ) {
				set_theme_mod( 'gl_band_image_mobile', $portrait );
			}
		}
		if ( $post = $demo( 'post', 'The Quiet Collapse is out now' ) ) {
			wp_update_post( array(
				'ID'            => $post,
				'post_date'     => '2026-01-16 10:00:00',
				'post_date_gmt' => get_gmt_from_date( '2026-01-16 10:00:00' ),
				'post_content'  => gl_p(
					'Our EP is out now on every streaming platform.',
					'Five songs that started Era I: Deep Blue Sea, Nothing’s Okay, The Price We Pay, Who Am I and Spiral. Three of them came out as singles first, each with its own music video.',
					'Thank you to everyone who listened, shared and came to the shows. This one is yours now.'
				),
			) );
		}
	}

	// v3: Release-Datum für die EP (strukturierte Daten), falls noch leer
	if ( $v < 3 ) {
		$ep = get_posts( array( 'post_type' => 'gl_release', 'post_status' => 'any', 'title' => 'The Quiet Collapse', 'posts_per_page' => 1, 'fields' => 'ids' ) )[0] ?? 0;
		if ( $ep && ! get_post_meta( $ep, '_gl_release_date', true ) ) {
			update_post_meta( $ep, '_gl_release_date', '2026-01-16' );
		}
	}

	// v4: Spotify/Apple Music als Profile (Footer, strukturierte Daten) und deutsche Seite /de/ für lokale Suchen
	if ( $v < 4 ) {
		$mods = (array) get_theme_mods();
		foreach ( array( 'spotify', 'apple' ) as $k ) {
			if ( empty( $mods[ 'gl_' . $k ] ) ) {
				set_theme_mod( 'gl_' . $k, gl_defaults()[ $k ] );
			}
		}
		if ( ! get_page_by_path( 'de' ) ) {
			gl_create_de_page();
		}
	}

	update_option( 'gl_content_version', GL_CONTENT_VERSION );
}, 20 );

/** Deutsche Landingpage „GHOSTLIKE – Metalcore-Band aus Augsburg“ (einmalig, danach im Backend frei bearbeitbar) */
function gl_create_de_page() {
	$h  = fn( $t ) => "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">" . esc_html( $t ) . "</h2>\n<!-- /wp:heading -->\n\n";
	$pp = fn( $t ) => "<!-- wp:paragraph -->\n<p>" . $t . "</p>\n<!-- /wp:paragraph -->\n\n";
	$ul = fn( $items ) => "<!-- wp:list -->\n<ul class=\"wp-block-list\">" . implode( '', array_map( fn( $i ) => "<!-- wp:list-item -->\n<li>" . $i . "</li>\n<!-- /wp:list-item -->", $items ) ) . "</ul>\n<!-- /wp:list -->\n\n";
	$a  = fn( $url, $t ) => '<a href="' . esc_url( $url ) . '">' . esc_html( $t ) . '</a>';

	$roles   = array( 'vocals' => 'Gesang', 'bass' => 'Bass', 'drums' => 'Schlagzeug', 'guitar' => 'Gitarre', 'guitars' => 'Gitarre' );
	$members = array_map( fn( $m ) => '<strong>' . esc_html( $m[0] ) . '</strong> – ' . esc_html( $roles[ strtolower( trim( $m[1] ?? '' ) ) ] ?? ( $m[1] ?? '' ) ), gl_pipe_rows( gl_opt( 'members' ) ) );
	$social  = array_map( fn( $s ) => $a( $s['url'], $s['label'] ), gl_socials() );
	$press   = array_map( fn( $r ) => isset( $r[1] ) ? $a( trim( $r[1] ), trim( $r[0] ) ) : esc_html( $r[0] ), gl_pipe_rows( gl_seo( 'press' ) ) );
	$mail    = 'band@ghostlike.de';

	$c  = $pp( '<strong>GHOSTLIKE ist eine Metalcore-Band aus Augsburg in Bayern.</strong> Seit 2025 verbinden fünf Musiker harte Riffs und Breakdowns mit atmosphärischen Flächen, cleanen Refrains und einer fast filmischen Dramaturgie: Metalcore mit Post-Hardcore-Einflüssen, laut, melancholisch und eingängig zugleich.' );
	$c .= $pp( 'Unsere Songs entstehen im Proberaum in Augsburg. Live spielen wir in Augsburg, in Bayern und überall, wo man uns lässt.' );
	$c .= $h( 'Die Band' );
	$c .= $ul( $members ?: array( 'Fünf Musiker aus Augsburg' ) );
	$c .= $h( 'Musik: Debüt-EP „The Quiet Collapse“' );
	$c .= $pp( 'Am 16. Januar 2026 ist unsere Debüt-EP <strong>„The Quiet Collapse“</strong> erschienen, mit fünf Songs: <em>Deep Blue Sea</em>, <em>Nothing’s Okay</em>, <em>The Price We Pay</em>, <em>Who Am I</em> und <em>Spiral</em>. Drei davon kamen vorab als Singles mit eigenem Musikvideo. Die EP gibt es auf Spotify, Apple Music, Deezer, Amazon Music und allen anderen Streamingdiensten.' );
	$c .= $pp( 'Gerade sind wir wieder im Studio und arbeiten an neuen Songs. ' . $a( home_url( '/news/' ), 'Neuigkeiten aus dem Studio' ) . ' (auf Englisch).' );
	$c .= $h( 'Live & Booking in Augsburg, München und Bayern' );
	$c .= $pp( 'Ihr veranstaltet ein Konzert, ein Festival oder eine Clubshow in Augsburg, München oder anderswo in Bayern und sucht eine Metalcore-Band? Schreibt uns an ' . $a( 'mailto:' . $mail, $mail ) . '. Wir freuen uns über Anfragen von Veranstaltern, Clubs, Jugendzentren und anderen Bands, gern auch für gemeinsame Shows.' );
	$c .= $pp( 'Aktuelle Konzerttermine findet ihr ' . $a( home_url( '/#tour' ), 'auf der Startseite' ) . '.' );
	if ( $press ) {
		$c .= $h( 'Presse' );
		$c .= $pp( 'Interviews und Berichte über GHOSTLIKE:' );
		$c .= $ul( $press );
		$c .= $pp( 'Pressefotos, Bandinfo und Musik für Redaktionen gibt es auf Anfrage an ' . $a( 'mailto:' . $mail, $mail ) . '.' );
	}
	if ( $social ) {
		$c .= $h( 'GHOSTLIKE hören und folgen' );
		$c .= $ul( $social );
	}
	$c .= $pp( '<em>This page in English:</em> ' . $a( home_url( '/' ), 'ghostlike.de' ) );

	$id = wp_insert_post( array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_name'    => 'de',
		'post_title'   => 'GHOSTLIKE – Metalcore-Band aus Augsburg, Bayern',
		'post_excerpt' => 'GHOSTLIKE ist eine Metalcore-Band aus Augsburg (Bayern). Debüt-EP „The Quiet Collapse“ jetzt überall im Stream – Bandinfo, Musik, Konzerte und Booking.',
		'post_content' => $c,
	) );
	if ( $id && ! is_wp_error( $id ) && ( $img = (int) gl_opt( 'band_image', 0 ) ) ) {
		set_post_thumbnail( $id, $img );
	}
	return $id;
}
