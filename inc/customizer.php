<?php
/**
 * Einstellungen unter Design → Customizer → „GHOSTLIKE“.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'customize_register', function ( WP_Customize_Manager $c ) {
	$d = gl_defaults();

	$c->add_panel( 'gl_panel', array( 'title' => 'GHOSTLIKE', 'priority' => 20 ) );

	$sections = array(
		'gl_band'    => 'Band & Bio',
		'gl_hero'    => 'Header-Slider',
		'gl_social'  => 'Social & Streaming',
		'gl_feed'    => 'Social-Bilder',
		'gl_contact' => 'Kontakt',
		'gl_footer'  => 'Footer & Login',
	);
	foreach ( $sections as $id => $title ) {
		$c->add_section( $id, array( 'title' => $title, 'panel' => 'gl_panel' ) );
	}

	$add = function ( $key, $section, $label, $type = 'text', $sanitize = 'sanitize_text_field', $desc = '' ) use ( $c, $d ) {
		$c->add_setting( 'gl_' . $key, array(
			'default'           => $d[ $key ] ?? '',
			'sanitize_callback' => $sanitize,
			'transport'         => 'refresh',
		) );
		$c->add_control( 'gl_' . $key, array(
			'label'       => $label,
			'section'     => $section,
			'type'        => $type,
			'description' => $desc,
		) );
	};

	// Band & Bio
	$add( 'tagline', 'gl_band', 'Kurzbeschreibung (Hero, Footer)' );
	$add( 'bio_kicker', 'gl_band', 'Kleine Zeile über der Bio' );
	$add( 'bio_heading', 'gl_band', 'Bio-Headline', 'textarea', 'sanitize_textarea_field', 'Zeilenumbruch = neue Zeile.' );
	$add( 'bio_lead', 'gl_band', 'Bio – erster Absatz', 'textarea', 'sanitize_textarea_field' );
	$add( 'bio_text', 'gl_band', 'Bio – zweiter Absatz', 'textarea', 'sanitize_textarea_field' );
	$add( 'bio_cta_text', 'gl_band', 'Button-Text (leer = kein Button)' );
	$add( 'bio_cta_url', 'gl_band', 'Button-Link', 'text', 'gl_sanitize_link' );
	$add( 'bio_caption', 'gl_band', 'Bildunterschrift' );
	$c->add_setting( 'gl_band_image', array( 'sanitize_callback' => 'absint' ) );
	$c->add_control( new WP_Customize_Media_Control( $c, 'gl_band_image', array( 'label' => 'Bandfoto (Desktop)', 'section' => 'gl_band', 'mime_type' => 'image', 'description' => 'Wird nie zugeschnitten. Ideal: Querformat 2400 × 1350 px, alle Mitglieder gut erkennbar.' ) ) );
	$c->add_setting( 'gl_band_image_mobile', array( 'sanitize_callback' => 'absint' ) );
	$c->add_control( new WP_Customize_Media_Control( $c, 'gl_band_image_mobile', array( 'label' => 'Bandfoto (Handy, optional)', 'section' => 'gl_band', 'mime_type' => 'image', 'description' => 'Z. B. ein Hochformat-Foto, auf dem alle fünf groß zu sehen sind (1080 × 1350 px).' ) ) );
	$add( 'marquee', 'gl_band', 'Laufband-Text', 'text', 'sanitize_text_field', 'Teile mit | trennen.' );

	// Header-Slider
	$c->add_setting( 'gl_slider_interval', array( 'default' => 7, 'sanitize_callback' => 'absint' ) );
	$c->add_control( 'gl_slider_interval', array( 'label' => 'Sekunden pro Slide', 'section' => 'gl_hero', 'type' => 'number', 'input_attrs' => array( 'min' => 3, 'max' => 30 ), 'description' => 'Die Slides selbst pflegt ihr links im Menü unter „Hero-Slides“.' ) );

	// Social & Streaming
	foreach ( array( 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'bandcamp' => 'Bandcamp', 'spotify' => 'Spotify', 'apple' => 'Apple Music' ) as $k => $l ) {
		$add( $k, 'gl_social', $l . ' (Link)', 'url', 'esc_url_raw' );
	}
	$add( 'instagram_handle', 'gl_social', 'Instagram-Name (ohne @)' );

	// Social-Bilder (bis die Instagram-Anbindung kommt)
	for ( $i = 1; $i <= 6; $i++ ) {
		$c->add_setting( 'gl_feed_' . $i, array( 'sanitize_callback' => 'absint' ) );
		$c->add_control( new WP_Customize_Media_Control( $c, 'gl_feed_' . $i, array( 'label' => 'Bild ' . $i, 'section' => 'gl_feed', 'mime_type' => 'image' ) ) );
	}

	// Kontakt
	$add( 'contact_intro', 'gl_contact', 'Einleitungstext', 'textarea', 'sanitize_textarea_field' );
	$add( 'contact_email', 'gl_contact', 'Empfänger (allgemein)', 'email', 'sanitize_email' );
	$add( 'contact_email_booking', 'gl_contact', 'Empfänger Booking (optional)', 'email', 'sanitize_email', 'Leer = allgemeiner Empfänger.' );
	$add( 'contact_email_press', 'gl_contact', 'Empfänger Presse (optional)', 'email', 'sanitize_email', 'Leer = allgemeiner Empfänger.' );
	$add( 'privacy_url', 'gl_contact', 'Link zur Datenschutzerklärung', 'url', 'esc_url_raw' );

	// Footer & Login
	$add( 'footer_meta', 'gl_footer', 'Kleine Zeile unten links' );
	$add( 'member_login_url', 'gl_footer', 'Link „Member login“', 'url', 'esc_url_raw' );
} );

function gl_sanitize_link( $v ) {
	$v = trim( (string) $v );
	return str_starts_with( $v, '#' ) ? '#' . sanitize_title( substr( $v, 1 ) ) : esc_url_raw( $v );
}
