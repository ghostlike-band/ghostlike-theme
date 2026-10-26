<?php
/**
 * GHOSTLIKE → Updates: alle anstehenden Theme-, Plugin- und Übersetzungs-Updates mit einem Klick.
 */

defined( 'ABSPATH' ) || exit;

/** Anstehende Updates aus dem WordPress-Cache (ohne neue Abfrage). */
function gl_pending_updates() {
	$out = array();
	$pl  = get_site_transient( 'update_plugins' );
	if ( ! empty( $pl->response ) ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all = get_plugins();
		foreach ( $pl->response as $file => $u ) {
			if ( isset( $all[ $file ] ) ) {
				$out[] = array( 'type' => 'plugin', 'id' => $file, 'name' => $all[ $file ]['Name'], 'from' => $all[ $file ]['Version'], 'to' => is_object( $u ) ? $u->new_version : ( $u['new_version'] ?? '' ) );
			}
		}
	}
	$th = get_site_transient( 'update_themes' );
	if ( ! empty( $th->response ) ) {
		foreach ( $th->response as $slug => $u ) {
			$t = wp_get_theme( $slug );
			if ( $t->exists() ) {
				$out[] = array( 'type' => 'theme', 'id' => $slug, 'name' => $t->get( 'Name' ), 'from' => $t->get( 'Version' ), 'to' => is_array( $u ) ? ( $u['new_version'] ?? '' ) : ( $u->new_version ?? '' ) );
			}
		}
	}
	return $out;
}

/** Frisch bei WordPress.org und GitHub nachfragen. */
function gl_refresh_update_checks() {
	delete_site_transient( 'gl_github_release' );
	delete_site_transient( 'glp_github_release' );
	delete_site_transient( 'update_plugins' );
	delete_site_transient( 'update_themes' );
	wp_update_plugins();
	wp_update_themes();
}

add_action( 'admin_post_gl_check_all', function () {
	check_admin_referer( 'gl_check_all' );
	if ( ! current_user_can( 'update_plugins' ) ) {
		wp_die( 'Keine Berechtigung.' );
	}
	gl_refresh_update_checks();
	wp_safe_redirect( admin_url( 'admin.php?page=ghostlike-updates&checked=1' ) );
	exit;
} );

add_action( 'admin_post_gl_update_all', function () {
	check_admin_referer( 'gl_update_all' );
	if ( ! current_user_can( 'update_plugins' ) || ! current_user_can( 'update_themes' ) ) {
		wp_die( 'Keine Berechtigung.' );
	}
	@set_time_limit( 600 );
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-admin/includes/theme.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';

	gl_refresh_update_checks();
	$pending = gl_pending_updates();
	$result  = array();

	$run = function ( $upgrader_class, $items ) use ( &$result ) {
		if ( ! $items ) {
			return;
		}
		$skin     = new Automatic_Upgrader_Skin();
		$upgrader = new $upgrader_class( $skin );
		$res      = $upgrader->bulk_upgrade( array_column( $items, 'id' ) );
		foreach ( $items as $it ) {
			$r        = is_array( $res ) ? ( $res[ $it['id'] ] ?? null ) : $res;
			$ok       = $r && ! is_wp_error( $r );
			$result[] = array(
				'name' => $it['name'],
				'to'   => $it['to'],
				'ok'   => $ok,
				'msg'  => $ok ? '' : ( is_wp_error( $r ) ? $r->get_error_message() : wp_strip_all_tags( implode( ' ', array_slice( (array) $skin->get_upgrade_messages(), -2 ) ) ) ),
			);
		}
	};
	$run( 'Plugin_Upgrader', array_values( array_filter( $pending, fn( $p ) => $p['type'] === 'plugin' ) ) );
	$run( 'Theme_Upgrader', array_values( array_filter( $pending, fn( $p ) => $p['type'] === 'theme' ) ) );

	// Übersetzungen gleich mit
	$langs = wp_get_translation_updates();
	if ( $langs ) {
		$lp = new Language_Pack_Upgrader( new Automatic_Upgrader_Skin() );
		$lp->bulk_upgrade( $langs );
		$result[] = array( 'name' => 'Übersetzungen', 'to' => count( $langs ) . ' Pakete', 'ok' => true, 'msg' => '' );
	}

	set_transient( 'gl_update_result_' . get_current_user_id(), $result, 10 * MINUTE_IN_SECONDS );
	wp_clean_plugins_cache( true );
	delete_site_transient( 'update_themes' );
	wp_safe_redirect( admin_url( 'admin.php?page=ghostlike-updates&done=1' ) );
	exit;
} );

function gl_updates_page() {
	$pending = gl_pending_updates();
	$result  = isset( $_GET['done'] ) ? get_transient( 'gl_update_result_' . get_current_user_id() ) : false;
	$last    = get_site_transient( 'update_plugins' );
	?>
	<div class="wrap">
		<h1>Updates</h1>
		<?php if ( isset( $_GET['checked'] ) ) : ?><div class="notice notice-info is-dismissible"><p>Neu geprüft.</p></div><?php endif; ?>

		<?php if ( is_array( $result ) ) : ?>
			<div class="notice <?php echo in_array( false, array_column( $result, 'ok' ), true ) ? 'notice-warning' : 'notice-success'; ?>">
				<p><strong><?php echo $result ? 'Fertig:' : 'Es gab nichts zu aktualisieren.'; ?></strong></p>
				<?php if ( $result ) : ?>
					<ul style="list-style:disc;padding-left:20px">
						<?php foreach ( $result as $r ) : ?>
							<li><?php echo esc_html( $r['name'] . ' → ' . $r['to'] ); ?> <?php echo $r['ok'] ? '✓' : '<strong>✕ ' . esc_html( $r['msg'] ?: 'fehlgeschlagen' ) . '</strong>'; ?></li>
						<?php endforeach; ?>
					</ul>
					<?php if ( in_array( false, array_column( $result, 'ok' ), true ) ) : ?>
						<p>Falls etwas nicht geklappt hat: unter <a href="<?php echo esc_url( admin_url( 'update-core.php' ) ); ?>">Dashboard → Aktualisierungen</a> noch einmal einzeln versuchen.</p>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<p style="max-width:720px">Installiert alle anstehenden Updates für Themes, Plugins (auch GHOSTLIKE-Theme und Member-Portal) und Übersetzungen auf einmal. WordPress selbst wird hier nicht aktualisiert, das macht WordPress automatisch bzw. unter <a href="<?php echo esc_url( admin_url( 'update-core.php' ) ); ?>">Dashboard → Aktualisierungen</a>.</p>

		<?php if ( $pending ) : ?>
			<table class="widefat striped" style="max-width:720px">
				<thead><tr><th>Was</th><th>Art</th><th>Installiert</th><th>Neu</th></tr></thead>
				<tbody>
					<?php foreach ( $pending as $p ) : ?>
						<tr><td><strong><?php echo esc_html( $p['name'] ); ?></strong></td><td><?php echo $p['type'] === 'theme' ? 'Theme' : 'Plugin'; ?></td><td><?php echo esc_html( $p['from'] ); ?></td><td><?php echo esc_html( $p['to'] ); ?></td></tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><strong>Alles aktuell.</strong> ✓</p>
		<?php endif; ?>

		<div style="display:flex;gap:8px;margin-top:16px;flex-wrap:wrap">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="this.querySelector('button').disabled=true;this.querySelector('button').textContent='Aktualisiere … (bitte warten)';">
				<?php wp_nonce_field( 'gl_update_all' ); ?>
				<input type="hidden" name="action" value="gl_update_all">
				<button class="button button-primary button-hero"><?php echo $pending ? 'Alles aktualisieren (' . count( $pending ) . ')' : 'Trotzdem prüfen und aktualisieren'; ?></button>
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'gl_check_all' ); ?>
				<input type="hidden" name="action" value="gl_check_all">
				<button class="button button-hero">Jetzt nach Updates suchen</button>
			</form>
		</div>
		<?php if ( ! empty( $last->last_checked ) ) : ?>
			<p class="description">Zuletzt geprüft: <?php echo esc_html( wp_date( 'd.m.Y, H:i', $last->last_checked ) ); ?> Uhr</p>
		<?php endif; ?>
	</div>
	<?php
}

/* ================= 1-KLICK-KNOPF OBEN IN DER ADMIN-LEISTE ================= */

add_action( 'admin_bar_menu', function ( $bar ) {
	if ( ! is_admin() || ! current_user_can( 'update_plugins' ) || ! current_user_can( 'update_themes' ) ) {
		return;
	}
	$n = count( gl_pending_updates() );
	$bar->add_node( array(
		'id'    => 'gl-update-all',
		'title' => '<span class="ab-icon dashicons dashicons-update" aria-hidden="true"></span><span class="ab-label">' . ( $n ? 'Alles aktualisieren (' . $n . ')' : 'Alles aktualisieren' ) . '</span>',
		'href'  => wp_nonce_url( admin_url( 'admin-post.php?action=gl_update_all' ), 'gl_update_all' ),
		'meta'  => array(
			'class'   => $n ? 'gl-has-updates' : '',
			'title'   => $n ? $n . ' Update(s) bereit: Theme, Plugins und Übersetzungen mit einem Klick installieren' : 'Nach Updates suchen und alles Anstehende installieren',
			'onclick' => "var l=this.querySelector('.ab-label');if(l){l.textContent='Aktualisiere … bitte warten';}this.style.pointerEvents='none';",
		),
	) );
}, 90 );

add_action( 'admin_head', function () {
	if ( ! current_user_can( 'update_plugins' ) ) {
		return;
	}
	?>
	<style>
		#wpadminbar #wp-admin-bar-gl-update-all .ab-icon:before { content: "\f463"; top: 2px; }
		#wpadminbar #wp-admin-bar-gl-update-all.gl-has-updates > .ab-item { background: #7C9A85; color: #0A0B0A; font-weight: 600; }
		#wpadminbar #wp-admin-bar-gl-update-all.gl-has-updates > .ab-item .ab-icon:before { color: #0A0B0A; }
		#wpadminbar #wp-admin-bar-gl-update-all.gl-has-updates:hover > .ab-item { background: #93b39c; color: #0A0B0A; }
		@media screen and (max-width: 782px) { #wpadminbar #wp-admin-bar-gl-update-all { display: block; } #wpadminbar #wp-admin-bar-gl-update-all .ab-label { display: none; } #wpadminbar #wp-admin-bar-gl-update-all .ab-icon:before { font-size: 28px; top: 0; } }
	</style>
	<?php
} );
