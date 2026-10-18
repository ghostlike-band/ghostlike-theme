<?php
/**
 * GHOSTLIKE → Videos: Upload direkt zu bunny.net Stream, Videoliste, „als Header-Video verwenden“.
 *
 * Der Browser lädt per TUS direkt zu bunny.net hoch (keine Upload-Grenze von one.com).
 * Der API-Schlüssel bleibt auf dem Server; der Browser bekommt nur eine zeitlich
 * begrenzte Signatur für genau ein Video.
 */

defined( 'ABSPATH' ) || exit;

function gl_bunny() {
	$o = (array) get_option( 'gl_bunny', array() );
	return array(
		'library' => preg_replace( '/\D/', '', (string) ( $o['library'] ?? '' ) ),
		'cdn'     => preg_replace( '#^https?://|/+$#', '', trim( (string) ( $o['cdn'] ?? '' ) ) ),
		'key'     => (string) get_option( 'gl_bunny_key', '' ),
	);
}

function gl_bunny_ready() {
	$b = gl_bunny();
	return $b['library'] && $b['cdn'] && $b['key'];
}

function gl_bunny_api( $method, $path, $body = null ) {
	$b   = gl_bunny();
	$res = wp_remote_request( 'https://video.bunnycdn.com/library/' . $b['library'] . $path, array(
		'method'  => $method,
		'timeout' => 20,
		'headers' => array( 'AccessKey' => $b['key'], 'Accept' => 'application/json', 'Content-Type' => 'application/json' ),
		'body'    => $body === null ? null : wp_json_encode( $body ),
	) );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$code = wp_remote_retrieve_response_code( $res );
	$data = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( $code >= 300 ) {
		$msg = $code === 401 ? 'bunny.net lehnt den API-Schlüssel ab (401). Bitte Library-ID und Stream-API-Key prüfen.' : ( $data['Message'] ?? $data['message'] ?? 'Fehler ' . $code );
		return new WP_Error( 'bunny', $msg );
	}
	return $data ?: array();
}

/** Video-Objekt von bunny.net → was die Seite braucht. */
function gl_bunny_video( $v ) {
	$cdn    = gl_bunny()['cdn'];
	$guid   = $v['guid'];
	$res    = array_filter( explode( ',', (string) ( $v['availableResolutions'] ?? '' ) ) );
	$mp4res = array_values( array_filter( $res, fn( $r ) => (int) $r <= 720 ) ); // MP4-Fallback gibt es bis 720p
	usort( $mp4res, fn( $a, $b ) => (int) $b - (int) $a );
	$status = array( 0 => 'Angelegt', 1 => 'Hochgeladen', 2 => 'Wird verarbeitet', 3 => 'Wird umgewandelt', 4 => 'Fertig', 5 => 'Fehler', 6 => 'Upload fehlgeschlagen', 7 => 'Fertig', 8 => 'Fertig' );
	$st     = (int) ( $v['status'] ?? 0 );
	return array(
		'guid'     => $guid,
		'title'    => $v['title'] ?? '',
		'status'   => $status[ $st ] ?? '–',
		'done'     => in_array( $st, array( 4, 7, 8 ), true ),
		'failed'   => in_array( $st, array( 5, 6 ), true ),
		'progress' => (int) ( $v['encodeProgress'] ?? 0 ),
		'length'   => (int) ( $v['length'] ?? 0 ),
		'size'     => (int) ( $v['storageSize'] ?? 0 ),
		'width'    => (int) ( $v['width'] ?? 0 ),
		'height'   => (int) ( $v['height'] ?? 0 ),
		'date'     => $v['dateUploaded'] ?? '',
		'thumb'    => ! empty( $v['thumbnailFileName'] ) ? 'https://' . $cdn . '/' . $guid . '/' . $v['thumbnailFileName'] : '',
		'mp4'      => array_map( fn( $r ) => array( 'res' => $r, 'url' => 'https://' . $cdn . '/' . $guid . '/play_' . $r . '.mp4' ), $mp4res ),
		'mp4_720'  => 'https://' . $cdn . '/' . $guid . '/play_720p.mp4',
		'hls'      => 'https://' . $cdn . '/' . $guid . '/playlist.m3u8',
	);
}

/* ================= EINSTELLUNGEN ================= */

add_action( 'admin_post_gl_bunny_save', function () {
	check_admin_referer( 'gl_bunny_save' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Keine Berechtigung.' );
	}
	$in = wp_unslash( $_POST );
	update_option( 'gl_bunny', array(
		'library' => sanitize_text_field( $in['library'] ?? '' ),
		'cdn'     => sanitize_text_field( $in['cdn'] ?? '' ),
	) );
	$key = trim( (string) ( $in['key'] ?? '' ) );
	if ( $key !== '' && strpos( $key, '•' ) === false ) {
		update_option( 'gl_bunny_key', sanitize_text_field( $key ), false );
	}
	$ok = '';
	if ( gl_bunny_ready() ) {
		$t  = gl_bunny_api( 'GET', '/videos?page=1&itemsPerPage=1' );
		$ok = is_wp_error( $t ) ? 'fail&msg=' . rawurlencode( $t->get_error_message() ) : 'ok';
	}
	wp_safe_redirect( admin_url( 'admin.php?page=ghostlike-videos&saved=' . $ok ) );
	exit;
} );

/* ================= AJAX ================= */

function gl_bunny_ajax_guard() {
	check_ajax_referer( 'gl_bunny', 'nonce' );
	if ( ! current_user_can( 'upload_files' ) ) {
		wp_send_json_error( array( 'message' => 'Keine Berechtigung.' ), 403 );
	}
	if ( ! gl_bunny_ready() ) {
		wp_send_json_error( array( 'message' => 'bunny.net ist noch nicht eingerichtet.' ), 400 );
	}
}

add_action( 'wp_ajax_gl_bunny_create', function () {
	gl_bunny_ajax_guard();
	$title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ) ?: 'Video ' . wp_date( 'd.m.Y H:i' );
	$v     = gl_bunny_api( 'POST', '/videos', array( 'title' => $title ) );
	if ( is_wp_error( $v ) || empty( $v['guid'] ) ) {
		wp_send_json_error( array( 'message' => is_wp_error( $v ) ? $v->get_error_message() : 'Video konnte nicht angelegt werden.' ) );
	}
	$b      = gl_bunny();
	$expire = time() + DAY_IN_SECONDS;
	wp_send_json_success( array(
		'guid'      => $v['guid'],
		'library'   => $b['library'],
		'expire'    => $expire,
		'signature' => hash( 'sha256', $b['library'] . $b['key'] . $expire . $v['guid'] ),
		'mp4_720'   => 'https://' . $b['cdn'] . '/' . $v['guid'] . '/play_720p.mp4',
	) );
} );

add_action( 'wp_ajax_gl_bunny_list', function () {
	gl_bunny_ajax_guard();
	$r = gl_bunny_api( 'GET', '/videos?page=1&itemsPerPage=60&orderBy=date' );
	if ( is_wp_error( $r ) ) {
		wp_send_json_error( array( 'message' => $r->get_error_message() ) );
	}
	wp_send_json_success( array_map( 'gl_bunny_video', (array) ( $r['items'] ?? array() ) ) );
} );

add_action( 'wp_ajax_gl_bunny_delete', function () {
	gl_bunny_ajax_guard();
	$guid = preg_replace( '/[^a-f0-9-]/i', '', (string) ( $_POST['guid'] ?? '' ) );
	$r    = $guid ? gl_bunny_api( 'DELETE', '/videos/' . $guid ) : new WP_Error( 'bunny', 'Kein Video.' );
	is_wp_error( $r ) ? wp_send_json_error( array( 'message' => $r->get_error_message() ) ) : wp_send_json_success();
} );

add_action( 'wp_ajax_gl_bunny_assign', function () {
	gl_bunny_ajax_guard();
	$slide  = (int) ( $_POST['slide'] ?? 0 );
	$target = ( $_POST['target'] ?? '' ) === 'mobile' ? 'video_mobile' : 'video';
	$url    = esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) );
	if ( get_post_type( $slide ) !== 'gl_slide' || ! current_user_can( 'edit_post', $slide ) || ! $url ) {
		wp_send_json_error( array( 'message' => 'Slide nicht gefunden.' ) );
	}
	update_post_meta( $slide, '_gl_' . $target, $url );
	wp_send_json_success( array( 'message' => 'Video bei „' . get_the_title( $slide ) . '“ (' . ( $target === 'video' ? 'Desktop' : 'Handy' ) . ') eingetragen.' ) );
} );

/* ================= SEITE ================= */

add_action( 'admin_enqueue_scripts', function ( $hook ) {
	// Nicht über $hook prüfen: der Menüname enthält ggf. die Update-Zahl und ändert den Hook-Namen
	if ( ( $_GET['page'] ?? '' ) !== 'ghostlike-videos' || ! gl_bunny_ready() ) {
		return;
	}
	$v = fn( $f ) => GL_VERSION . '.' . (int) @filemtime( GL_DIR . $f );
	wp_enqueue_script( 'gl-tus', GL_URI . '/assets/js/vendor/tus.min.js', array(), '4.3.1', true );
	wp_enqueue_script( 'gl-bunny', GL_URI . '/assets/js/admin-bunny.js', array( 'gl-tus' ), $v( '/assets/js/admin-bunny.js' ), true );
	$slides = array_map( fn( $p ) => array( 'id' => $p->ID, 'title' => $p->post_title ), get_posts( array( 'post_type' => 'gl_slide', 'posts_per_page' => 20, 'orderby' => 'menu_order', 'order' => 'ASC' ) ) );
	wp_localize_script( 'gl-bunny', 'GLB', array(
		'ajax'   => admin_url( 'admin-ajax.php' ),
		'nonce'  => wp_create_nonce( 'gl_bunny' ),
		'slides' => $slides,
	) );
} );

function gl_videos_page() {
	$b     = gl_bunny();
	$ready = gl_bunny_ready();
	$admin = current_user_can( 'manage_options' );
	?>
	<div class="wrap gl-videos">
		<h1>Videos (bunny.net)</h1>
		<?php if ( ( $_GET['saved'] ?? '' ) === 'ok' ) : ?><div class="notice notice-success is-dismissible"><p>Gespeichert. Verbindung zu bunny.net steht ✓</p></div><?php endif; ?>
		<?php if ( ( $_GET['saved'] ?? '' ) === 'fail' ) : ?><div class="notice notice-error"><p>Gespeichert, aber die Verbindung klappt nicht: <?php echo esc_html( wp_unslash( $_GET['msg'] ?? '' ) ); ?></p></div><?php endif; ?>

		<style>
			.gl-videos .card { max-width: 980px; padding: 16px 20px; }
			.gl-videos .gl-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px; margin-top: 12px; }
			.gl-videos .gl-vid { background: #fff; border: 1px solid #dcdcde; display: grid; }
			.gl-videos .gl-vid__img { aspect-ratio: 16/9; background: #1d2327 center/cover no-repeat; position: relative; }
			.gl-videos .gl-vid__badge { position: absolute; left: 8px; top: 8px; background: #1d2327; color: #fff; font-size: 11px; padding: 2px 6px; }
			.gl-videos .gl-vid__badge.is-done { background: #00a32a; }
			.gl-videos .gl-vid__badge.is-fail { background: #d63638; }
			.gl-videos .gl-vid__body { padding: 10px 12px; display: grid; gap: 8px; }
			.gl-videos .gl-vid__body code { font-size: 11px; word-break: break-all; }
			.gl-videos .gl-row { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
			.gl-videos progress { width: 100%; height: 14px; }
			.gl-videos .gl-drop { border: 2px dashed #c3c4c7; padding: 24px; text-align: center; background: #f6f7f7; cursor: pointer; }
			.gl-videos .gl-drop.is-over { border-color: #2271b1; background: #f0f6fc; }
		</style>

		<?php if ( $ready ) : ?>
			<div class="card">
				<h2>Video hochladen</h2>
				<div class="gl-row">
					<label>Titel <input type="text" id="glb-title" class="regular-text" placeholder="z. B. Header – Nothing’s Okay"></label>
				</div>
				<p class="gl-row">
					<label>Danach direkt eintragen bei:
						<select id="glb-slide"><option value="">– nicht eintragen –</option></select>
					</label>
					<label><select id="glb-target"><option value="desktop">als Desktop-Video</option><option value="mobile">als Handy-Video (Hochformat)</option></select></label>
				</p>
				<div class="gl-drop" id="glb-drop">
					<p><strong>MP4 hierher ziehen</strong> oder <button type="button" class="button" id="glb-pick">Datei auswählen</button></p>
					<p class="description">Header-Loop: 10–20 s, ohne Ton, 1920 × 1080 (Handy 1080 × 1920). Keine Größengrenze, der Upload geht direkt zu bunny.net und lässt sich nach Abbruch fortsetzen.</p>
					<input type="file" id="glb-file" accept="video/*" hidden>
				</div>
				<div id="glb-progress" hidden style="margin-top:12px">
					<progress max="100" value="0"></progress>
					<p class="description" id="glb-status"></p>
				</div>
			</div>

			<div class="card">
				<div class="gl-row" style="justify-content:space-between">
					<h2 style="margin:0">Eure Videos</h2>
					<button type="button" class="button" id="glb-reload">Aktualisieren</button>
				</div>
				<p class="description">Nach dem Upload wandelt bunny.net das Video um (meist 1–5 Minuten). Für Header-Slides den <strong>MP4-Link (720p)</strong> verwenden, damit er funktioniert, muss in der Library „MP4 Fallback“ aktiv sein.</p>
				<div class="gl-grid" id="glb-list"><p>Lade …</p></div>
			</div>
		<?php endif; ?>

		<?php if ( $admin ) : ?>
			<details class="card" <?php echo $ready ? '' : 'open'; ?>>
				<summary><strong>Verbindung zu bunny.net</strong> <?php echo $ready ? '· Zugangsdaten hinterlegt' : '– noch nicht eingerichtet'; ?></summary>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'gl_bunny_save' ); ?>
					<input type="hidden" name="action" value="gl_bunny_save">
					<table class="form-table"><tbody>
						<tr><th><label for="glb-lib">Library ID</label></th><td><input id="glb-lib" name="library" class="regular-text" value="<?php echo esc_attr( $b['library'] ); ?>" inputmode="numeric"><p class="description">bunny.net → Stream → eure Library → <em>API</em> → „Video Library ID“</p></td></tr>
						<tr><th><label for="glb-key">API Key</label></th><td><input id="glb-key" name="key" class="regular-text" type="password" autocomplete="off" value="<?php echo $b['key'] ? '••••••••' . esc_attr( substr( $b['key'], -4 ) ) : ''; ?>"><p class="description">Gleiche Seite: „API Key“ der Library (nicht der Account-Schlüssel). Wird nur auf dem Server gespeichert.</p></td></tr>
						<tr><th><label for="glb-cdn">CDN Hostname</label></th><td><input id="glb-cdn" name="cdn" class="regular-text" value="<?php echo esc_attr( $b['cdn'] ); ?>" placeholder="vz-xxxxxxxx-xxx.b-cdn.net"><p class="description">Gleiche Seite: „CDN Hostname“</p></td></tr>
					</tbody></table>
					<?php submit_button( 'Speichern & Verbindung testen' ); ?>
				</form>
			</details>
		<?php elseif ( ! $ready ) : ?>
			<p>bunny.net ist noch nicht eingerichtet. Bitte einen Admin, das zu erledigen.</p>
		<?php endif; ?>
	</div>
	<?php
}
