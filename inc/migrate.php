<?php
/**
 * Einmaliger Umzug neu.ghostlike.de → www.ghostlike.de (one.com, getrennte Webroots).
 *
 * GHOSTLIKE → Umzug (nur für Admins, nur solange die Seite noch unter neu.ghostlike.de läuft):
 *  1. Analyse: findet den Webroot der Hauptdomain (dort liegt die alte WordPress-Seite).
 *  2. Umzug:   a) Datenbank: „neu.ghostlike.de“ → „www.ghostlike.de“ (gleich lang, daher auch in
 *                 serialisierten Daten sicher), Suchmaschinen erlauben.
 *              b) Alte Seite im Ziel nach _alte-website/ verschieben (nichts wird gelöscht).
 *              c) Neue Seite ins Ziel verschieben.
 *              d) Im alten Ordner eine .htaccess mit 301-Weiterleitung auf www anlegen.
 *  Alles wird protokolliert; Rückweg steht im Protokoll.
 */

defined( 'ABSPATH' ) || exit;

const GL_MIG_FROM = 'neu.ghostlike.de';
const GL_MIG_TO   = 'www.ghostlike.de';

function gl_mig_active() {
	return wp_parse_url( home_url(), PHP_URL_HOST ) === GL_MIG_FROM;
}

/** Kandidaten für den Webroot der Hauptdomain: Ordner mit WordPress, nicht unserer. */
function gl_mig_candidates() {
	$self  = untrailingslashit( ABSPATH );
	$dirs  = array();
	$roots = array( dirname( $self ), dirname( dirname( $self ) ) );
	foreach ( $roots as $root ) {
		foreach ( (array) @scandir( $root ) as $e ) {
			if ( $e === '.' || $e === '..' ) {
				continue;
			}
			$p = $root . '/' . $e;
			if ( is_dir( $p ) && $p !== $self && $p !== dirname( $self ) ) {
				$dirs[] = $p;
				// eine Ebene tiefer (z. B. httpd.www, webroots/xyz)
				foreach ( (array) @scandir( $p ) as $e2 ) {
					if ( $e2 !== '.' && $e2 !== '..' && is_dir( $p . '/' . $e2 ) && $p . '/' . $e2 !== $self ) {
						$dirs[] = $p . '/' . $e2;
					}
				}
			}
		}
	}
	$out = array();
	foreach ( array_unique( $dirs ) as $d ) {
		if ( ! is_file( $d . '/wp-config.php' ) ) {
			continue;
		}
		$cfg = (string) @file_get_contents( $d . '/wp-config.php' );
		preg_match( "/define\(\s*['\"]DB_NAME['\"]\s*,\s*['\"]([^'\"]+)/", $cfg, $m );
		$out[] = array(
			'path'      => $d,
			'db'        => $m[1] ?? '?',
			'elementor' => is_dir( $d . '/wp-content/plugins/elementor' ),
			'writable'  => is_writable( $d ),
			'entries'   => count( array_diff( (array) @scandir( $d ), array( '.', '..' ) ) ),
		);
	}
	return $out;
}

add_action( 'admin_menu', function () {
	if ( gl_mig_active() || get_option( 'gl_mig_log' ) ) {
		add_submenu_page( 'ghostlike', 'GHOSTLIKE – Umzug', 'Umzug', 'manage_options', 'ghostlike-umzug', 'gl_mig_page', 99 );
	}
}, 30 );

function gl_mig_page() {
	$cands = gl_mig_active() ? gl_mig_candidates() : array();
	$log   = (array) get_option( 'gl_mig_log', array() );
	?>
	<div class="wrap">
		<h1>Umzug auf <?php echo esc_html( GL_MIG_TO ); ?></h1>
		<?php if ( gl_mig_active() ) : ?>
			<div class="card" style="max-width:760px;margin:16px 0 24px">
				<h2>Nach dem Umzug: neu.ghostlike.de weiterleiten</h2>
				<p>Leitet alle Aufrufe dieser Kopie per 301 auf <strong>https://<?php echo esc_html( GL_MIG_TO ); ?></strong> um, inklusive Kalender-Abos und Zusage-Links. Danach ist dieses Backend nicht mehr erreichbar (Rückgängig: die Zeilen „GHOSTLIKE-UMZUG“ aus der .htaccess entfernen).</p>
				<?php if ( isset( $_GET['redirected'] ) ) : ?><p><strong>Weiterleitung ist aktiv.</strong></p><?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="gl_mig_redirect"><?php wp_nonce_field( 'gl_mig_redirect' ); ?>
					<label><input type="checkbox" name="confirm" value="1" required> www.ghostlike.de läuft, jetzt weiterleiten</label>
					<?php submit_button( 'Weiterleitung aktivieren', 'primary', 'submit', false ); ?>
				</form>
			</div>
		<?php endif; ?>
		<?php if ( ! gl_mig_active() ) : ?>
			<p>Diese Seite läuft bereits unter <strong><?php echo esc_html( home_url() ); ?></strong>.</p>
		<?php else : ?>
			<p>Diese Installation: <code><?php echo esc_html( untrailingslashit( ABSPATH ) ); ?></code> · Datenbank <code><?php echo esc_html( DB_NAME ); ?></code></p>
			<h2>Gefundene WordPress-Ordner (Ziel = alte Seite der Hauptdomain)</h2>
			<table class="widefat striped" style="max-width:980px">
				<thead><tr><th>Ordner</th><th>Datenbank</th><th>Elementor (alte Seite)</th><th>Beschreibbar</th><th>Einträge</th></tr></thead>
				<tbody>
				<?php foreach ( $cands as $c ) : ?>
					<tr><td><code><?php echo esc_html( $c['path'] ); ?></code></td><td><?php echo esc_html( $c['db'] ); ?></td><td><?php echo $c['elementor'] ? 'ja' : 'nein'; ?></td><td><?php echo $c['writable'] ? 'ja' : 'NEIN'; ?></td><td><?php echo (int) $c['entries']; ?></td></tr>
				<?php endforeach; ?>
				<?php if ( ! $cands ) : ?><tr><td colspan="5">Keinen anderen WordPress-Ordner gefunden.</td></tr><?php endif; ?>
				</tbody>
			</table>
			<?php $ok = array_values( array_filter( $cands, fn( $c ) => $c['elementor'] && $c['writable'] && $c['db'] !== DB_NAME ) ); ?>
			<?php if ( count( $ok ) === 1 ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:24px;max-width:760px">
					<input type="hidden" name="action" value="gl_mig_run">
					<input type="hidden" name="target" value="<?php echo esc_attr( $ok[0]['path'] ); ?>">
					<?php wp_nonce_field( 'gl_mig_run' ); ?>
					<p><strong>Ziel:</strong> <code><?php echo esc_html( $ok[0]['path'] ); ?></code></p>
					<p><label><input type="checkbox" name="confirm" value="1" required> Ja, alte Seite nach <code>_alte-website</code> verschieben und diese Seite nach <?php echo esc_html( GL_MIG_TO ); ?> umziehen.</label></p>
					<?php submit_button( 'Umzug jetzt ausführen', 'primary', 'submit', false ); ?>
				</form>
			<?php else : ?>
				<p><strong>Kein eindeutiges Ziel gefunden – Umzug nicht möglich.</strong></p>
			<?php endif; ?>
		<?php endif; ?>
		<?php if ( $log ) : ?>
			<h2>Protokoll</h2>
			<pre style="background:#fff;border:1px solid #dcdcde;padding:12px;max-width:980px;white-space:pre-wrap"><?php echo esc_html( implode( "\n", $log ) ); ?></pre>
		<?php endif; ?>
	</div>
	<?php
}

add_action( 'admin_post_gl_mig_run', function () {
	check_admin_referer( 'gl_mig_run' );
	if ( ! current_user_can( 'manage_options' ) || ! gl_mig_active() || empty( $_POST['confirm'] ) ) {
		wp_die( 'Nicht erlaubt.' );
	}
	$target = (string) wp_unslash( $_POST['target'] ?? '' );
	$valid  = array_column( array_filter( gl_mig_candidates(), fn( $c ) => $c['elementor'] && $c['writable'] && $c['db'] !== DB_NAME ), 'path' );
	if ( count( $valid ) !== 1 || $valid[0] !== $target ) {
		wp_die( 'Ziel ungültig.' );
	}
	@set_time_limit( 300 );
	ignore_user_abort( true );
	global $wpdb;
	$self = untrailingslashit( ABSPATH );
	$log  = array( 'Start ' . gmdate( 'c' ), 'Quelle: ' . $self, 'Ziel: ' . $target );

	// a) Datenbank (nur Tabellen dieser Installation)
	$tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix ) . '%' ) );
	$total  = 0;
	foreach ( $tables as $t ) {
		foreach ( $wpdb->get_results( "SHOW COLUMNS FROM `$t`" ) as $col ) {
			if ( preg_match( '/char|text|blob/i', $col->Type ) ) {
				$n      = $wpdb->query( $wpdb->prepare( "UPDATE `$t` SET `{$col->Field}` = REPLACE(`{$col->Field}`, %s, %s) WHERE `{$col->Field}` LIKE %s", GL_MIG_FROM, GL_MIG_TO, '%' . $wpdb->esc_like( GL_MIG_FROM ) . '%' ) );
				$total += (int) $n;
			}
		}
	}
	$wpdb->update( $wpdb->options, array( 'option_value' => '1' ), array( 'option_name' => 'blog_public' ) );
	$log[] = 'DB: ' . $total . ' Felder ersetzt (' . GL_MIG_FROM . ' → ' . GL_MIG_TO . '), Suchmaschinen erlaubt';
	update_option( 'gl_mig_log', $log, false );

	// b) Alte Seite beiseite
	$old = $target . '/_alte-website';
	if ( ! is_dir( $old ) && ! @mkdir( $old, 0755 ) ) {
		$log[] = 'FEHLER: _alte-website nicht anlegbar';
		update_option( 'gl_mig_log', $log, false );
		wp_die( implode( '<br>', array_map( 'esc_html', $log ) ) );
	}
	foreach ( array_diff( (array) scandir( $target ), array( '.', '..', '_alte-website' ) ) as $e ) {
		$ok    = @rename( $target . '/' . $e, $old . '/' . $e );
		$log[] = ( $ok ? 'alt → _alte-website: ' : 'FEHLER alt: ' ) . $e;
	}
	update_option( 'gl_mig_log', $log, false );

	// c) Neue Seite ins Ziel
	foreach ( array_diff( (array) scandir( $self ), array( '.', '..' ) ) as $e ) {
		$ok    = @rename( $self . '/' . $e, $target . '/' . $e );
		$log[] = ( $ok ? 'neu → Ziel: ' : 'FEHLER neu: ' ) . $e;
	}

	// d) Weiterleitung im alten Subdomain-Ordner
	@file_put_contents( $self . '/.htaccess', "# Umzug " . gmdate( 'Y-m-d' ) . ": alles auf https://" . GL_MIG_TO . " weiterleiten\n<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteRule ^(.*)$ https://" . GL_MIG_TO . "/$1 [R=301,L]\n</IfModule>\n" );
	$log[] = 'Weiterleitung in ' . $self . '/.htaccess angelegt';
	$log[] = 'Rückweg: Einträge aus Ziel zurück nach ' . $self . ', Inhalt von _alte-website zurück ins Ziel, in der DB ' . GL_MIG_TO . ' → ' . GL_MIG_FROM . ' ersetzen.';
	$log[] = 'Ende ' . gmdate( 'c' );
	update_option( 'gl_mig_log', $log, false );

	// Ab hier keine weiteren Theme-Dateien laden (sie liegen jetzt woanders)
	nocache_headers();
	header( 'Content-Type: text/html; charset=utf-8' );
	echo '<!doctype html><meta charset="utf-8"><title>Umzug</title><body style="font:15px/1.6 system-ui;padding:40px;max-width:900px">';
	echo '<h1>Umzug ausgeführt</h1><p><a href="https://' . GL_MIG_TO . '/wp-admin/admin.php?page=ghostlike-umzug">Weiter zu https://' . GL_MIG_TO . '/wp-admin</a> (neu einloggen)</p><pre style="white-space:pre-wrap;background:#f6f7f7;padding:12px">';
	echo esc_html( implode( "\n", $log ) );
	echo '</pre></body>';
	exit;
} );

add_action( 'admin_post_gl_mig_redirect', function () {
	check_admin_referer( 'gl_mig_redirect' );
	if ( ! current_user_can( 'manage_options' ) || ! gl_mig_active() || empty( $_POST['confirm'] ) ) {
		wp_die( 'Nicht erlaubt.' );
	}
	$file  = ABSPATH . '.htaccess';
	$old   = is_file( $file ) ? (string) file_get_contents( $file ) : '';
	$block = "# BEGIN GHOSTLIKE-UMZUG\n<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteRule ^(.*)$ https://" . GL_MIG_TO . "/$1 [R=301,L]\n</IfModule>\n# END GHOSTLIKE-UMZUG\n\n";
	if ( strpos( $old, 'GHOSTLIKE-UMZUG' ) === false ) {
		file_put_contents( $file, $block . $old );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=ghostlike-umzug&redirected=1' ) );
	exit;
} );
