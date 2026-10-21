<?php
/**
 * Eingabefelder für Slides, Releases und Shows (ohne Zusatz-Plugin).
 */

defined( 'ABSPATH' ) || exit;

function gl_fields() {
	return array(
		'gl_slide'   => array(
			'title'  => 'Slide-Inhalt',
			'fields' => array(
				'kicker'      => array( 'label' => 'Kleine Zeile oben', 'type' => 'text', 'help' => 'z. B. „Official video · Single 01“' ),
				'headline'    => array( 'label' => 'Headline', 'type' => 'textarea', 'rows' => 2, 'help' => 'Zeilenumbruch = neue Zeile. Maximal zwei Wörter pro Zeile (CI).' ),
				'show_logo'   => array( 'label' => 'Statt Headline das Logo zeigen', 'type' => 'checkbox' ),
				'subline'     => array( 'label' => 'Unterzeile', 'type' => 'text' ),
				'image_mobile'=> array( 'label' => 'Bild fürs Handy (Hochformat, optional)', 'type' => 'media', 'library' => 'image', 'help' => 'Ideal 1080 × 1920 px (9:16), JPG/WebP, unter 300 KB. Leer = das Beitragsbild wird zugeschnitten.' ),
				'focus'       => array( 'label' => 'Bildausschnitt', 'type' => 'select', 'options' => array( 'center' => 'Mitte', 'top' => 'Oben', 'bottom' => 'Unten', 'left' => 'Links', 'right' => 'Rechts', '30% center' => 'Etwas links', '70% center' => 'Etwas rechts' ), 'help' => 'Welcher Teil bleibt sichtbar, wenn das Bild zugeschnitten wird (vor allem auf dem Handy).' ),
				'focus_mobile'=> array( 'label' => 'Bildausschnitt Handy', 'type' => 'select', 'options' => array( 'center 20%' => 'Oben (Gesichter)', 'top' => 'Ganz oben', 'center' => 'Mitte', 'bottom' => 'Unten', 'left' => 'Links', 'right' => 'Rechts' ), 'help' => 'Auf dem Handy zeigt der Header das Bild oben und den Text darunter. Hier wählst du, welcher Teil des Bildes sichtbar bleibt.' ),
				'video'       => array( 'label' => 'Video (MP4, optional)', 'type' => 'media', 'library' => 'video', 'help' => 'Stummer Loop, 10–20 s, 1920 × 1080 oder 1280 × 720, H.264-MP4, unter 8 MB. Mediathek oder externe URL (z. B. bunny.net: …/play_720p.mp4). Das Beitragsbild ist das Vorschaubild.' ),
				'video_mobile'=> array( 'label' => 'Video fürs Handy (Hochformat, optional)', 'type' => 'media', 'library' => 'video', 'help' => '720 × 1280 (9:16), unter 5 MB. Leer = das Desktop-Video wird zugeschnitten.' ),
				'button_text' => array( 'label' => 'Button-Text', 'type' => 'text', 'help' => 'z. B. „Watch the video“' ),
				'button_url'  => array( 'label' => 'Button-Link', 'type' => 'url', 'help' => 'Externer Link oder Anker wie #music, #news, #tour' ),
			),
		),
		'gl_release' => array(
			'title'  => 'Release-Details',
			'fields' => array(
				'featured'    => array( 'label' => 'Groß auf der Startseite zeigen', 'type' => 'checkbox' ),
				'kicker'      => array( 'label' => 'Kleine Zeile oben', 'type' => 'text', 'help' => 'z. B. „EP · Era I · 5 songs · Out now“' ),
				'release_date'=> array( 'label' => 'Release-Datum', 'type' => 'date', 'help' => 'Für Google (strukturierte Daten) und KI-Suchen.' ),
				'headline'    => array( 'label' => 'Headline (optional)', 'type' => 'textarea', 'rows' => 2, 'help' => 'Leer = Titel. Zeilenumbruch = neue Zeile.' ),
				'tracks'      => array( 'label' => 'Tracklist', 'type' => 'textarea', 'rows' => 7, 'help' => 'Eine Zeile pro Song: <code>Titel | single | Video-Link | Cover-URL</code>. Nur der Titel ist Pflicht, z. B. <code>Spiral</code> oder <code>Deep Blue Sea | single | https://youtu.be/…</code>' ),
				'spotify'     => array( 'label' => 'Spotify', 'type' => 'url' ),
				'apple'       => array( 'label' => 'Apple Music', 'type' => 'url' ),
				'tidal'       => array( 'label' => 'TIDAL', 'type' => 'url' ),
				'deezer'      => array( 'label' => 'Deezer', 'type' => 'url' ),
				'ytmusic'     => array( 'label' => 'YouTube Music', 'type' => 'url' ),
				'amazon'      => array( 'label' => 'Amazon Music', 'type' => 'url' ),
				'bandcamp'    => array( 'label' => 'Bandcamp', 'type' => 'url' ),
				'youtube'     => array( 'label' => 'YouTube (Videos)', 'type' => 'url', 'help' => 'Wird nicht als Streaming-Button gezeigt, nur als Fallback-Link in der Diskografie.' ),
				'smartlink'   => array( 'label' => 'Alle Plattformen (Smartlink)', 'type' => 'url', 'help' => 'Buttons erscheinen nur für ausgefüllte Dienste.' ),
			),
		),
		'gl_show'    => array(
			'title'  => 'Show-Details',
			'fields' => array(
				'date'        => array( 'label' => 'Datum', 'type' => 'date' ),
				'city'        => array( 'label' => 'Stadt', 'type' => 'text' ),
				'venue'       => array( 'label' => 'Venue', 'type' => 'text' ),
				'support'     => array( 'label' => 'Mit / Support (optional)', 'type' => 'text', 'help' => 'z. B. „w/ Band XY“' ),
				'status'      => array( 'label' => 'Status', 'type' => 'select', 'options' => gl_show_statuses() ),
				'ticket_url'  => array( 'label' => 'Ticket-Link', 'type' => 'url' ),
			),
		),
	);
}

add_action( 'add_meta_boxes', function () {
	foreach ( gl_fields() as $type => $box ) {
		add_meta_box( 'gl_box_' . $type, $box['title'], 'gl_render_box', $type, 'normal', 'high', array( 'type' => $type ) );
	}
} );

function gl_render_box( $post, $args ) {
	$type = $args['args']['type'];
	wp_nonce_field( 'gl_save_' . $type, 'gl_nonce' );
	echo '<table class="form-table gl-fields"><tbody>';
	foreach ( gl_fields()[ $type ]['fields'] as $key => $f ) {
		$name  = 'gl[' . $key . ']';
		$id    = 'gl-' . $key;
		$value = get_post_meta( $post->ID, '_gl_' . $key, true );
		echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $f['label'] ) . '</label></th><td>';
		switch ( $f['type'] ) {
			case 'textarea':
				printf( '<textarea class="large-text" rows="%d" id="%s" name="%s">%s</textarea>', (int) ( $f['rows'] ?? 3 ), esc_attr( $id ), esc_attr( $name ), esc_textarea( $value ) );
				break;
			case 'checkbox':
				printf( '<label><input type="checkbox" id="%s" name="%s" value="1" %s> Ja</label>', esc_attr( $id ), esc_attr( $name ), checked( $value, '1', false ) );
				break;
			case 'select':
				printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $name ) );
				foreach ( $f['options'] as $k => $label ) {
					printf( '<option value="%s" %s>%s</option>', esc_attr( $k ), selected( $value, $k, false ), esc_html( $label ) );
				}
				echo '</select>';
				break;
			case 'media':
				printf( '<input type="url" class="regular-text" id="%s" name="%s" value="%s"> <button type="button" class="button gl-media" data-target="%s" data-library="%s">Aus Mediathek wählen</button>', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ), esc_attr( $id ), esc_attr( $f['library'] ?? 'video' ) );
				break;
			case 'date':
				printf( '<input type="date" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ) );
				break;
			case 'url':
				printf( '<input type="text" class="regular-text" id="%s" name="%s" value="%s" placeholder="https://">', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ) );
				break;
			default:
				printf( '<input type="text" class="regular-text" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ) );
		}
		if ( ! empty( $f['help'] ) ) {
			echo '<p class="description">' . wp_kses( $f['help'], array( 'code' => array() ) ) . '</p>';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
}

add_action( 'save_post', function ( $post_id, $post ) {
	$fields = gl_fields();
	if ( ! isset( $fields[ $post->post_type ] ) || ! isset( $_POST['gl_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['gl_nonce'] ) ), 'gl_save_' . $post->post_type ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$in = isset( $_POST['gl'] ) && is_array( $_POST['gl'] ) ? wp_unslash( $_POST['gl'] ) : array();
	foreach ( $fields[ $post->post_type ]['fields'] as $key => $f ) {
		$raw = $in[ $key ] ?? '';
		switch ( $f['type'] ) {
			case 'textarea':
				$val = sanitize_textarea_field( $raw );
				break;
			case 'checkbox':
				$val = $raw ? '1' : '';
				break;
			case 'select':
				$val = array_key_exists( $raw, $f['options'] ) ? $raw : '';
				break;
			case 'url':
			case 'media':
				$raw = trim( (string) $raw );
				$val = str_starts_with( $raw, '#' ) ? '#' . sanitize_title( substr( $raw, 1 ) ) : esc_url_raw( $raw );
				break;
			case 'date':
				$val = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ? $raw : '';
				break;
			default:
				$val = sanitize_text_field( $raw );
		}
		if ( $val === '' ) {
			delete_post_meta( $post_id, '_gl_' . $key );
		} else {
			update_post_meta( $post_id, '_gl_' . $key, $val );
		}
	}
}, 10, 2 );

// Mediathek-Button für das Video-Feld.
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || get_post_type() !== 'gl_slide' ) {
		return;
	}
	wp_enqueue_media();
	wp_add_inline_script( 'media-editor', "
		document.addEventListener('click', function (e) {
			var b = e.target.closest('.gl-media'); if (!b) return;
			e.preventDefault();
			var type = b.dataset.library || 'video';
			var frame = wp.media({ title: type === 'image' ? 'Bild wählen' : 'Video wählen', library: { type: type }, button: { text: 'Übernehmen' }, multiple: false });
			frame.on('select', function () {
				document.getElementById(b.dataset.target).value = frame.state().get('selection').first().toJSON().url;
			});
			frame.open();
		});
	" );
} );
