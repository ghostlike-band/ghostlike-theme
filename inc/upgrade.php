<?php
/**
 * Einmalige Anpassungen nach Theme-Updates. Ändert nur Demo-Inhalte (_gl_demo),
 * nie Inhalte, die ihr selbst angelegt habt.
 */

defined( 'ABSPATH' ) || exit;

const GL_CONTENT_VERSION = 2;

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

	update_option( 'gl_content_version', GL_CONTENT_VERSION );
}, 20 );
