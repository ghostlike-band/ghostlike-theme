<?php
/**
 * Shortcodes für Blogposts.
 *
 * [listen spotify="…" apple="…" tidal="…" deezer="…" ytmusic="…" amazon="…" bandcamp="…" title="Listen on"]
 * → Streaming-Buttons mit Logos, wie im Release-Bereich der Startseite.
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'listen', function ( $atts ) {
	$atts  = (array) $atts;
	$links = array();
	foreach ( gl_platforms() as $key => $p ) {
		if ( ! empty( $atts[ $key ] ) ) {
			$links[] = array( 'url' => $atts[ $key ], 'label' => $p[0], 'icon' => $p[1] ? gl_icon( $p[1] ) : '' );
		}
	}
	if ( ! $links ) {
		return '';
	}
	$out = '<div class="stream stream--post"><span class="meta">' . esc_html( $atts['title'] ?? 'Listen on' ) . '</span><div class="stream__list">';
	foreach ( $links as $l ) {
		$out .= '<a href="' . esc_url( $l['url'] ) . '" target="_blank" rel="noopener" class="stream__btn">'
			. ( $l['icon'] ?: '<span class="stream__pfeil" aria-hidden="true">↗</span>' )
			. '<span>' . esc_html( $l['label'] ) . '</span></a>';
	}
	return $out . '</div></div>';
} );

/**
 * [press_image file="cover-nothings-okay.jpg" alt="…" caption="…"]
 * → Bild aus assets/img/press des Themes (Cover, Pressefotos). Unabhängig von der Domain.
 */
add_shortcode( 'press_image', function ( $atts ) {
	$a    = shortcode_atts( array( 'file' => '', 'alt' => '', 'caption' => '', 'class' => 'cover-s' ), $atts );
	$file = sanitize_file_name( $a['file'] );
	if ( ! $file || ! file_exists( GL_DIR . '/assets/img/press/' . $file ) ) {
		return '';
	}
	$src = get_template_directory_uri() . '/assets/img/press/' . $file;
	return '<figure class="wp-block-image ' . esc_attr( $a['class'] ) . '"><img src="' . esc_url( $src ) . '" alt="' . esc_attr( $a['alt'] ) . '" loading="lazy">'
		. ( $a['caption'] !== '' ? '<figcaption class="wp-element-caption">' . esc_html( $a['caption'] ) . '</figcaption>' : '' ) . '</figure>';
} );
