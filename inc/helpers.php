<?php
/**
 * Kleine Helfer für Templates.
 */

defined( 'ABSPATH' ) || exit;

/** Theme-Einstellung (Customizer) mit Standardwert. */
function gl_opt( $key, $default = '' ) {
	$defaults = gl_defaults();
	$fallback = $default !== '' ? $default : ( $defaults[ $key ] ?? '' );
	return get_theme_mod( 'gl_' . $key, $fallback );
}

/** Standardwerte, damit die Seite auch ohne Einstellungen vollständig aussieht. */
function gl_defaults() {
	return array(
		'tagline'         => 'Metalcore · Augsburg, DE',
		'bio_kicker'      => 'Band',
		'bio_heading'     => "Five\nghosts",
		'bio_lead'        => 'GHOSTLIKE is a five-piece metalcore band from Augsburg, Germany. Cold and weathered rather than neon: crushing grooves, screams and clean vocals that stick.',
		'bio_text'        => 'Our EP The Quiet Collapse opened Era I: five songs, three singles, a music video for each of them. We write, record, mix and film most of it ourselves. Right now we are back in the studio.',
		'members'         => "Robin | Vocals\nAlexander | Bass\nValentin | Drums\nAndreas | Guitar\nSilvan | Guitar",
		'bio_cta_text'    => '',
		'bio_cta_url'     => '',
		'bio_caption'     => 'Augsburg, DE — Era I',
		'marquee'         => 'The Quiet Collapse | Out now | Back in the studio',
		'slider_interval' => 7,
		'instagram'       => 'https://www.instagram.com/ghostlikeband/',
		'instagram_handle'=> 'ghostlikeband',
		'youtube'         => 'https://www.youtube.com/@ghostlikeband',
		'bandcamp'        => 'https://ghostlike.bandcamp.com',
		'spotify'         => '',
		'apple'           => '',
		'contact_intro'   => 'Booking, press, collaborations, or just say hi. We read everything.',
		'contact_email'   => get_option( 'admin_email' ),
		'contact_email_booking' => '',
		'contact_email_press'   => '',
		'privacy_url'     => get_privacy_policy_url(),
		'member_login_url'=> home_url( '/portal/' ),
		'footer_meta'     => 'Style GL-27 / Ära I',
	);
}

/** Mehrzeiliger Text → sichere Zeilen mit <br>. */
function gl_lines( $text ) {
	$lines = array_filter( array_map( 'trim', explode( "\n", (string) $text ) ), 'strlen' );
	return implode( '<br>', array_map( 'esc_html', $lines ) );
}

/** „A | B“-Zeilen in Arrays zerlegen. */
function gl_pipe_rows( $text ) {
	$rows = array();
	foreach ( explode( "\n", (string) $text ) as $line ) {
		$line = trim( $line );
		if ( $line === '' ) {
			continue;
		}
		$rows[] = array_map( 'trim', explode( '|', $line ) );
	}
	return $rows;
}

/** Meta-Wert eines Beitrags. */
function gl_meta( $post_id, $key, $default = '' ) {
	$v = get_post_meta( $post_id, '_gl_' . $key, true );
	return $v === '' ? $default : $v;
}

/** Bild-URL einer Anhang-ID oder einer URL. */
function gl_img_url( $id_or_url, $size = 'large' ) {
	if ( is_numeric( $id_or_url ) && $id_or_url ) {
		return wp_get_attachment_image_url( (int) $id_or_url, $size ) ?: '';
	}
	return (string) $id_or_url;
}

/** Ziel für Anker-Links: auf der Startseite „#x“, sonst „/#x“. */
function gl_anchor( $id ) {
	return is_front_page() ? '#' . $id : home_url( '/#' . $id );
}

/** Links mit http(s) öffnen in neuem Tab. */
function gl_target( $url ) {
	return preg_match( '#^https?://#', (string) $url ) && strpos( $url, home_url() ) !== 0 ? ' target="_blank" rel="noopener"' : '';
}

/** SVG-Sprite mit Logo und Bildmarke (einmal pro Seite). */
function gl_svg_sprite() {
	$g = 'M170.1,69c0-6.6-5.7-11.1-12.3-11.1s-12.9,4.8-17.7,10.2c-5.7,6.6-11.1,12.3-21,13.8-11.1-3.9-24.3-6-39-6C34.5,75.9,1.2,96,1.2,130.2c0,20.1,12.6,33.3,31.5,41.1C15.6,179.1,0,191.1,0,209.7c0,12.3,8.7,34.8,50.4,34.8h56.7c12,0,18.6,3.6,18.6,12.9,0,8.7-5.7,18.9-22.5,18.9-3.3,0-6.9,1.5-6.9,5.7,0,4.5,5.1,6.3,10.5,6.3,40.8,0,60.9-25.8,60.9-49.2,0-26.1-18.9-42.6-63.3-42.6h-59.1c-9.9,0-13.5-3.3-13.5-8.1,0-5.1,4.2-9.6,9.6-14.1,11.1,3,23.4,4.5,36.3,4.5,44.1,0,78-17.7,78-49.2,0-18.6-9.3-33-25.2-42.3,26.7-1.5,39.6-6.6,39.6-18.3ZM80.1,167.7c-15.3,0-22.5-17.7-22.5-42s6.3-37.8,19.8-37.8c15.6,0,22.2,18,22.2,41.1,0,24.9-6.3,38.7-19.5,38.7Z';
	?>
<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
  <symbol id="logo" viewBox="0 0 1464.29 288.3">
    <path d="<?php echo esc_attr( $g ); ?>"/>
    <path d="M363.31,93.31c-7.7-12.09-22.99-17.41-44.41-17.41-30,0-48.9,18.3-61.5,34.2V.3l-78.6,22.2v8.4l18,2.1v183.3l-18,5.1v11.1h95.7v-11.1l-17.1-5.1v-96.9c8.1-6.9,17.4-12.3,27.9-12.3,17.4,0,22.2,3.6,22.2,13.8v95.4l-17.1,5.1v11.1h74.33c-12.43-20.69-19.58-44.91-19.58-70.8s6.61-48.24,18.16-68.39Z"/>
    <path d="M688.79,129.6c-24.6-7.2-34.2-12-34.2-24,0-10.8,9.6-17.7,23.7-17.7,16.2,0,24,8.4,24,21.9,0,7.8,3.3,13.5,18,13.5,11.4,0,16.8-7.5,16.8-15.6,0-17.1-26.1-31.8-59.7-31.8s-58.94,12.82-66.09,36.12c5.38,13.9,8.56,28.89,9.14,44.55,9.26,9.69,24.3,16.27,45.25,21.93,21.9,6,34.8,11.1,34.8,25.5,0,12.9-12.6,19.5-27.9,19.5-16.5,0-31.8-6-31.8-27,0-10.2-10.2-14.1-19.2-14.1-.45,0-.89.01-1.33.03-.43.02-.86.05-1.28.08h0c-1.79,11.83-5.1,23.16-9.7,33.79,12.02,14.45,39.18,19.2,63.3,19.2,44.4,0,73.2-18.9,73.2-52.2,0-31.2-21-43.2-57-53.7Z"/>
    <path d="M836.99,93.9h34.2v-15h-34.2V24l-82.2,60.9v9h21v100.8c0,29.4,22.8,40.8,42.3,40.8s37.2-7.5,56.1-15.6v-7.5l-37.2-6v-112.5Z"/>
    <polygon points="956.99 0 878.99 22.5 878.99 30.9 896.99 33 896.99 216.3 878.99 221.4 878.99 232.5 974.99 232.5 974.99 221.4 956.99 216.3 956.99 0"/>
    <polygon points="1065.59 75.9 986.99 101.7 986.99 109.5 1004.99 113.1 1004.99 216.3 986.99 221.4 986.99 232.5 1083.59 232.5 1083.59 221.4 1065.59 216.3 1065.59 75.9"/>
    <path d="M1035.29,63c17.1,0,33-13.2,33-31.8s-15.9-31.2-33-31.2-33,12.9-33,31.2,15.3,31.8,33,31.8Z"/>
    <polygon points="1217.09 137.1 1263.29 94.5 1287.29 90 1287.29 78.9 1223.69 78.9 1223.69 90 1235.69 92.4 1174.19 151.5 1174.19 .3 1095.59 22.5 1095.59 30.9 1113.59 33 1113.59 216.3 1095.59 221.4 1095.59 232.5 1192.19 232.5 1192.19 221.4 1174.19 216.3 1174.19 176.4 1182.29 168.9 1235.99 232.5 1299.59 232.5 1299.59 221.4 1286.09 217.5 1217.09 137.1"/>
    <path d="M1457.99,179.7c-12.6,14.4-27.9,21-49.2,21-30,0-46.8-16.5-50.1-43.2l104.4-3v-7.2c0-41.4-24.6-71.4-72.3-71.4s-88.2,32.7-88.2,83.4c0,47.7,36.9,76.2,85.8,76.2,41.7,0,66.9-21,75.9-52.2l-6.3-3.6ZM1386.88,87.9c16.8,0,20.4,23.7,20.7,53.7l-49.5,3c.6-29.4,8.7-56.7,28.8-56.7Z"/>
  </symbol>
  <symbol id="logo-g" viewBox="0 0 172 289"><path d="<?php echo esc_attr( $g ); ?>"/></symbol>
</svg>
	<?php
}

function gl_logo( $class = '' ) {
	echo '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 1464.29 288.3" aria-hidden="true" focusable="false"><use href="#logo"/></svg>';
}

function gl_mark( $class = '' ) {
	echo '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 172 289" aria-hidden="true" focusable="false"><use href="#logo-g"/></svg>';
}

/** Social-Links aus dem Customizer. */
function gl_socials() {
	$out = array();
	foreach ( array( 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'bandcamp' => 'Bandcamp', 'spotify' => 'Spotify', 'apple' => 'Apple Music' ) as $key => $label ) {
		$url = gl_opt( $key );
		if ( $url ) {
			$out[ $key ] = array( 'label' => $label, 'url' => $url );
		}
	}
	return $out;
}

/** Logo eines Streamingdienstes (Simple Icons, CC0) als Inline-SVG. */
function gl_icon( $name ) {
	static $cache = array();
	if ( ! isset( $cache[ $name ] ) ) {
		$file           = GL_DIR . '/assets/img/icons/' . sanitize_file_name( $name ) . '.svg';
		$svg            = file_exists( $file ) ? (string) file_get_contents( $file ) : '';
		$svg            = preg_replace( '#<title>.*?</title>#s', '', $svg );
		$cache[ $name ] = $svg ? str_replace( '<svg ', '<svg aria-hidden="true" focusable="false" fill="currentColor" ', $svg ) : '';
	}
	return $cache[ $name ];
}

/** Streamingdienste in Anzeige-Reihenfolge: Meta-Schlüssel => [Name, Icon]. */
function gl_platforms() {
	return array(
		'spotify'   => array( 'Spotify', 'spotify' ),
		'apple'     => array( 'Apple Music', 'applemusic' ),
		'tidal'     => array( 'TIDAL', 'tidal' ),
		'deezer'    => array( 'Deezer', 'deezer' ),
		'ytmusic'   => array( 'YouTube Music', 'youtubemusic' ),
		'amazon'    => array( 'Amazon Music', '' ),
		'bandcamp'  => array( 'Bandcamp', 'bandcamp' ),
		'smartlink' => array( 'All platforms', '' ),
	);
}
