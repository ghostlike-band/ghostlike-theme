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
				'video'       => array( 'label' => 'Video (MP4, optional)', 'type' => 'media', 'help' => 'Kurzer, stummer Loop (10–20 s, max. ca. 8 MB). Ohne Video wird das Beitragsbild gezeigt. Das Beitragsbild ist auch das Vorschaubild.' ),
				'button_text' => array( 'label' => 'Button-Text', 'type' => 'text', 'help' => 'z. B. „Watch the video“' ),
				'button_url'  => array( 'label' => 'Button-Link', 'type' => 'url', 'help' => 'Externer Link oder Anker wie #music, #news, #tour' ),
			),
		),
		'gl_release' => array(
			'title'  => 'Release-Details',
			'fields' => array(
				'featured'    => array( 'label' => 'Groß auf der Startseite zeigen', 'type' => 'checkbox' ),
				'kicker'      => array( 'label' => 'Kleine Zeile oben', 'type' => 'text', 'help' => 'z. B. „EP · Era I · 5 songs · Out now“' ),
				'headline'    => array( 'label' => 'Headline (optional)', 'type' => 'textarea', 'rows' => 2, 'help' => 'Leer = Titel. Zeilenumbruch = neue Zeile.' ),
				'tracks'      => array( 'label' => 'Tracklist', 'type' => 'textarea', 'rows' => 7, 'help' => 'Eine Zeile pro Song: <code>Titel | single | Video-Link | Cover-URL</code>. Nur der Titel ist Pflicht, z. B. <code>Spiral</code> oder <code>Deep Blue Sea | single | https://youtu.be/…</code>' ),
				'spotify'     => array( 'label' => 'Spotify', 'type' => 'url' ),
				'apple'       => array( 'label' => 'Apple Music', 'type' => 'url' ),
				'bandcamp'    => array( 'label' => 'Bandcamp', 'type' => 'url' ),
				'youtube'     => array( 'label' => 'YouTube', 'type' => 'url' ),
				'smartlink'   => array( 'label' => 'Alle Plattformen (Smartlink)', 'type' => 'url' ),
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
				printf( '<input type="url" class="regular-text" id="%s" name="%s" value="%s"> <button type="button" class="button gl-media" data-target="%s">Aus Mediathek wählen</button>', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ), esc_attr( $id ) );
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
			var frame = wp.media({ title: 'Video wählen', library: { type: 'video' }, button: { text: 'Übernehmen' }, multiple: false });
			frame.on('select', function () {
				document.getElementById(b.dataset.target).value = frame.state().get('selection').first().toJSON().url;
			});
			frame.open();
		});
	" );
} );
