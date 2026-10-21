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

/**
 * YouTube-Videos in Beiträgen erst nach Klick laden (Datenschutz):
 * Vorschaubild + Play-Button, beim Klick wird das Video von youtube-nocookie.com geladen.
 */
add_filter( 'embed_oembed_html', function ( $html, $url ) {
	if ( is_admin() || ! preg_match( '~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/))([\w-]{11})~', (string) $url, $m ) ) {
		return $html;
	}
	$id     = $m[1];
	$title  = preg_match( '/title="([^"]*)"/', (string) $html, $t ) ? html_entity_decode( $t[1] ) : 'Video';
	$local  = array( 'DqXVOw3MyLE' => 'video-nothings-okay.jpg', 'EakE0ouacgo' => 'video-deep-blue-sea.jpg', 'LRb5R9Nma24' => 'video-the-price-we-pay.jpg' );
	$poster = isset( $local[ $id ] ) ? GL_URI . '/assets/img/press/' . $local[ $id ] : ( has_post_thumbnail() ? get_the_post_thumbnail_url( null, 'gl-card' ) : '' );
	$src    = 'https://www.youtube-nocookie.com/embed/' . $id . '?autoplay=1&rel=0';
	$priv   = get_privacy_policy_url();
	return '<div class="yt-consent" data-yt="' . esc_url( $src ) . '" data-title="' . esc_attr( $title ) . '">'
		. ( $poster ? '<img src="' . esc_url( $poster ) . '" alt="" loading="lazy">' : '' )
		. '<button type="button" class="yt-consent__play" aria-label="' . esc_attr( 'Play video: ' . $title ) . '"><span aria-hidden="true">▶</span></button>'
		. '<span class="yt-consent__note">Plays via YouTube (Google). By clicking you agree to load it.' . ( $priv ? ' <a href="' . esc_url( $priv ) . '">Privacy policy</a>' : '' ) . '</span>'
		. '</div>';
}, 10, 2 );
