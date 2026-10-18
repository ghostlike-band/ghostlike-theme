<?php
/**
 * GHOSTLIKE → E-Mails: Absender, Texte bearbeiten, Live-Vorschau, Testmail.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	add_submenu_page( 'ghostlike', 'GHOSTLIKE – E-Mails', 'E-Mails', 'manage_options', 'ghostlike-emails', 'gl_emails_page' );
}, 15 );

add_action( 'admin_post_gl_emails', function () {
	check_admin_referer( 'gl_emails' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Keine Berechtigung.' );
	}
	$in   = wp_unslash( $_POST );
	$id   = sanitize_key( $in['mail'] ?? '' );
	$do   = sanitize_key( $in['do'] ?? 'save' );
	$defs = gl_email_defs();
	$back = admin_url( 'admin.php?page=ghostlike-emails&mail=' . rawurlencode( $id ) );
	$msg  = '';

	if ( $do === 'sender' ) {
		update_option( 'gl_mail_from_name', sanitize_text_field( $in['from_name'] ?? '' ) );
		$from = sanitize_email( $in['from'] ?? '' );
		update_option( 'gl_mail_from', is_email( $from ) ? $from : '' );
		update_option( 'gl_mail_wrap_all', empty( $in['wrap_all'] ) ? '0' : '1' );
		$msg = 'ok:Absender gespeichert.';
	} elseif ( isset( $defs[ $id ] ) ) {
		$all = (array) get_option( 'gl_emails', array() );
		if ( $do === 'reset' ) {
			unset( $all[ $id ] );
			update_option( 'gl_emails', $all, false );
			$msg = 'ok:Standardtext wiederhergestellt.';
		} else {
			$all[ $id ] = array(
				'subject' => sanitize_text_field( $in['subject'] ?? '' ),
				'body'    => sanitize_textarea_field( $in['body'] ?? '' ),
			);
			update_option( 'gl_emails', $all, false );
			$msg = 'ok:Gespeichert.';
			if ( $do === 'test' ) {
				$me  = wp_get_current_user();
				$ok  = gl_send_email( $me->user_email, $id, $defs[ $id ]['sample'] );
				$msg = $ok ? 'ok:Gespeichert und Testmail an ' . $me->user_email . ' verschickt.' : 'err:Gespeichert, aber die Testmail konnte nicht verschickt werden. Mailversand prüfen (SMTP).';
			}
		}
	}
	wp_safe_redirect( add_query_arg( 'm', rawurlencode( $msg ), $back ) );
	exit;
} );

// Live-Vorschau
add_action( 'wp_ajax_gl_email_preview', function () {
	check_ajax_referer( 'gl_emails', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die();
	}
	$id   = sanitize_key( $_POST['mail'] ?? '' );
	$defs = gl_email_defs();
	if ( ! isset( $defs[ $id ] ) ) {
		wp_send_json_error();
	}
	$vars = $defs[ $id ]['sample'];
	$body = sanitize_textarea_field( wp_unslash( $_POST['body'] ?? '' ) );
	$subj = sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) );
	wp_send_json_success( array(
		'subject' => wp_strip_all_tags( gl_email_fill( $subj, $vars ) ),
		'html'    => gl_email_wrap( gl_email_body_html( $body, $vars ), '' ),
	) );
} );

function gl_emails_page() {
	$defs = gl_email_defs();
	$id   = sanitize_key( $_GET['mail'] ?? '' );
	$id   = isset( $defs[ $id ] ) ? $id : array_key_first( $defs );
	$tpl  = gl_email_tpl( $id );
	$def  = $defs[ $id ];
	$m    = isset( $_GET['m'] ) ? sanitize_text_field( wp_unslash( $_GET['m'] ) ) : '';
	[ $type, $text ] = array_pad( explode( ':', $m, 2 ), 2, '' );
	$saved   = (array) get_option( 'gl_emails', array() );
	$preview = gl_email( $id, $def['sample'] );
	?>
	<div class="wrap gl-emails">
		<h1>E-Mails</h1>
		<?php if ( $text ) : ?><div class="notice <?php echo $type === 'ok' ? 'notice-success' : 'notice-error'; ?> is-dismissible"><p><?php echo esc_html( $text ); ?></p></div><?php endif; ?>
		<style>
			.gl-emails .gl-cols { display: grid; grid-template-columns: 240px minmax(0, 1fr) minmax(0, 1fr); gap: 20px; align-items: start; max-width: 1500px; }
			.gl-emails .gl-list { background: #fff; border: 1px solid #dcdcde; }
			.gl-emails .gl-list a { display: block; padding: 10px 12px; border-bottom: 1px solid #f0f0f1; text-decoration: none; color: #1d2327; }
			.gl-emails .gl-list a.is-on { background: #1d2327; color: #fff; }
			.gl-emails .gl-list small { display: block; color: #8c8f94; }
			.gl-emails .gl-list a.is-on small { color: #c3c4c7; }
			.gl-emails textarea { width: 100%; min-height: 340px; font-family: Menlo, Consolas, monospace; font-size: 13px; line-height: 1.55; }
			.gl-emails .gl-vars { display: flex; flex-wrap: wrap; gap: 6px; margin: 8px 0 14px; }
			.gl-emails .gl-vars button { font-family: Menlo, Consolas, monospace; font-size: 12px; }
			.gl-emails iframe { width: 100%; height: 720px; border: 1px solid #dcdcde; background: #0A0B0A; }
			.gl-emails .gl-help { background: #f6f7f7; border: 1px solid #dcdcde; padding: 10px 14px; font-size: 12px; line-height: 1.8; }
			.gl-emails .gl-help code { font-size: 12px; }
			@media (max-width: 1200px) { .gl-emails .gl-cols { grid-template-columns: 220px minmax(0, 1fr); } .gl-emails .gl-prev { grid-column: 1 / -1; } }
		</style>

		<div class="gl-cols">
			<nav class="gl-list" aria-label="E-Mails">
				<?php foreach ( $defs as $k => $d ) : ?>
					<a class="<?php echo $k === $id ? 'is-on' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=ghostlike-emails&mail=' . $k ) ); ?>"><?php echo esc_html( $d['label'] ); ?><small><?php echo isset( $saved[ $k ] ) ? 'angepasst' : 'Standardtext'; ?></small></a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="gl-mail-form">
				<?php wp_nonce_field( 'gl_emails' ); ?>
				<input type="hidden" name="action" value="gl_emails">
				<input type="hidden" name="mail" value="<?php echo esc_attr( $id ); ?>">
				<h2 style="margin-top:0"><?php echo esc_html( $def['label'] ); ?></h2>
				<p class="description"><?php echo esc_html( $def['desc'] ); ?></p>
				<p><label><strong>Betreff</strong><br><input type="text" name="subject" id="gl-subject" class="large-text" value="<?php echo esc_attr( $tpl['subject'] ); ?>"></label></p>
				<p style="margin-bottom:0"><strong>Text</strong></p>
				<div class="gl-vars" aria-label="Platzhalter einfügen">
					<?php foreach ( $def['vars'] as $v => $label ) : ?>
						<button type="button" class="button button-small" data-var="<?php echo esc_attr( '{' . $v . '}' ); ?>" title="<?php echo esc_attr( $label ); ?>">{<?php echo esc_html( $v ); ?>}</button>
					<?php endforeach; ?>
				</div>
				<textarea name="body" id="gl-body" spellcheck="true"><?php echo esc_textarea( $tpl['body'] ); ?></textarea>
				<div class="gl-help">
					<code># Überschrift</code> große Headline &nbsp;·&nbsp; <code>**fett**</code> &nbsp;·&nbsp; <code>[[Button-Text|{link}]]</code> Button (eigene Zeile) &nbsp;·&nbsp; <code>---</code> Trennlinie &nbsp;·&nbsp; Leerzeile = neuer Absatz &nbsp;·&nbsp; Platzhalter oben anklicken zum Einfügen
				</div>
				<p style="display:flex;gap:8px;flex-wrap:wrap">
					<button class="button button-primary" name="do" value="save">Speichern</button>
					<button class="button" name="do" value="test">Speichern &amp; Testmail an mich</button>
					<?php if ( isset( $saved[ $id ] ) ) : ?><button class="button button-link-delete" name="do" value="reset" onclick="return confirm('Eigenen Text verwerfen und Standard wiederherstellen?')">Standard wiederherstellen</button><?php endif; ?>
				</p>
			</form>

			<div class="gl-prev">
				<h2 style="margin-top:0">Vorschau</h2>
				<p class="description">Mit Beispielwerten. Betreff: <strong id="gl-prev-subject"><?php echo esc_html( $preview['subject'] ); ?></strong></p>
				<iframe id="gl-prev" title="Vorschau der E-Mail" srcdoc="<?php echo esc_attr( $preview['html'] ); ?>"></iframe>
			</div>
		</div>

		<h2 style="margin-top:36px">Absender</h2>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:760px">
			<?php wp_nonce_field( 'gl_emails' ); ?>
			<input type="hidden" name="action" value="gl_emails"><input type="hidden" name="do" value="sender"><input type="hidden" name="mail" value="<?php echo esc_attr( $id ); ?>">
			<table class="form-table"><tbody>
				<tr><th><label for="gl-from-name">Absendername</label></th><td><input id="gl-from-name" name="from_name" class="regular-text" value="<?php echo esc_attr( get_option( 'gl_mail_from_name', '' ) ); ?>" placeholder="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>"></td></tr>
				<tr><th><label for="gl-from">Absenderadresse</label></th><td><input id="gl-from" name="from" type="email" class="regular-text" value="<?php echo esc_attr( get_option( 'gl_mail_from', '' ) ); ?>" placeholder="z. B. portal@ghostlike.de"><p class="description">Am besten ein echtes Postfach auf eurer Domain (bei one.com anlegen), sonst landen Mails leichter im Spam. Leer = WordPress-Standard.</p></td></tr>
				<tr><th>Alle anderen Mails</th><td><label><input type="checkbox" name="wrap_all" value="1" <?php checked( get_option( 'gl_mail_wrap_all', '1' ), '1' ); ?>> Auch WordPress-Hinweise (Updates, Benachrichtigungen) und Plugin-Mails im Band-Look verschicken</label></td></tr>
			</tbody></table>
			<?php submit_button( 'Absender speichern' ); ?>
		</form>
	</div>
	<script>
	(function () {
		const body = document.getElementById('gl-body'), subj = document.getElementById('gl-subject');
		document.querySelectorAll('.gl-vars [data-var]').forEach((b) => b.addEventListener('click', () => {
			const t = b.dataset.var, s = body.selectionStart, e = body.selectionEnd;
			body.setRangeText(t, s, e, 'end'); body.focus(); queue();
		}));
		let timer;
		function queue() { clearTimeout(timer); timer = setTimeout(render, 400); }
		async function render() {
			const fd = new FormData();
			fd.append('action', 'gl_email_preview'); fd.append('nonce', <?php echo wp_json_encode( wp_create_nonce( 'gl_emails' ) ); ?>);
			fd.append('mail', <?php echo wp_json_encode( $id ); ?>); fd.append('body', body.value); fd.append('subject', subj.value);
			const r = await fetch(ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' }).then((x) => x.json()).catch(() => null);
			if (r && r.success) { document.getElementById('gl-prev').srcdoc = r.data.html; document.getElementById('gl-prev-subject').textContent = r.data.subject; }
		}
		body.addEventListener('input', queue); subj.addEventListener('input', queue);
	})();
	</script>
	<?php
}
