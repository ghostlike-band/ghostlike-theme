<?php
/**
 * Design → GHOSTLIKE: Version, Update-Check, Demo-Inhalte, Kurzanleitung.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', function () {
	$icon  = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="-40 -10 252 310"><path fill="black" d="M170.1,69c0-6.6-5.7-11.1-12.3-11.1s-12.9,4.8-17.7,10.2c-5.7,6.6-11.1,12.3-21,13.8-11.1-3.9-24.3-6-39-6C34.5,75.9,1.2,96,1.2,130.2c0,20.1,12.6,33.3,31.5,41.1C15.6,179.1,0,191.1,0,209.7c0,12.3,8.7,34.8,50.4,34.8h56.7c12,0,18.6,3.6,18.6,12.9,0,8.7-5.7,18.9-22.5,18.9-3.3,0-6.9,1.5-6.9,5.7,0,4.5,5.1,6.3,10.5,6.3,40.8,0,60.9-25.8,60.9-49.2,0-26.1-18.9-42.6-63.3-42.6h-59.1c-9.9,0-13.5-3.3-13.5-8.1,0-5.1,4.2-9.6,9.6-14.1,11.1,3,23.4,4.5,36.3,4.5,44.1,0,78-17.7,78-49.2,0-18.6-9.3-33-25.2-42.3,26.7-1.5,39.6-6.6,39.6-18.3ZM80.1,167.7c-15.3,0-22.5-17.7-22.5-42s6.3-37.8,19.8-37.8c15.6,0,22.2,18,22.2,41.1,0,24.9-6.3,38.7-19.5,38.7Z"/></svg>' );
	$count = function_exists( 'gl_pending_updates' ) ? count( gl_pending_updates() ) : 0;
	$badge = $count ? ' <span class="update-plugins count-' . $count . '"><span class="update-count">' . $count . '</span></span>' : '';
	add_menu_page( 'GHOSTLIKE', 'GHOSTLIKE' . $badge, 'manage_options', 'ghostlike', 'gl_admin_page', $icon, 3 );
	add_submenu_page( 'ghostlike', 'GHOSTLIKE – Übersicht', 'Übersicht', 'manage_options', 'ghostlike', 'gl_admin_page' );
	add_submenu_page( 'ghostlike', 'GHOSTLIKE – Updates', 'Updates' . $badge, 'update_plugins', 'ghostlike-updates', 'gl_updates_page' );
	add_submenu_page( 'ghostlike', 'GHOSTLIKE – Videos', 'Videos (bunny.net)', 'upload_files', 'ghostlike-videos', 'gl_videos_page' );
} );

// Alte Adresse (Design → GHOSTLIKE) weiterleiten
add_action( 'admin_init', function () {
	global $pagenow;
	if ( $pagenow === 'themes.php' && ( $_GET['page'] ?? '' ) === 'ghostlike' ) {
		wp_safe_redirect( admin_url( 'admin.php?page=ghostlike' ) );
		exit;
	}
} );

add_action( 'admin_post_gl_check_updates', function () {
	check_admin_referer( 'gl_check_updates' );
	if ( ! current_user_can( 'update_themes' ) ) {
		wp_die( 'Keine Berechtigung.' );
	}
	gl_latest_release( true );
	delete_site_transient( 'update_themes' );
	wp_update_themes();
	wp_safe_redirect( admin_url( 'admin.php?page=ghostlike&checked=1' ) );
	exit;
} );

function gl_admin_page() {
	$release = gl_latest_release();
	$has_new = $release && version_compare( $release['version'], GL_VERSION, '>' );
	$demo    = (int) get_option( 'gl_demo_imported' );
	?>
	<div class="wrap">
		<h1>GHOSTLIKE</h1>

		<?php if ( isset( $_GET['checked'] ) ) : ?>
			<div class="notice notice-info is-dismissible"><p>Update-Prüfung abgeschlossen.</p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['demo'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo $_GET['demo'] === 'removed' ? 'Demo-Inhalte wurden entfernt.' : 'Demo-Inhalte wurden angelegt. Schau dir die Startseite an!'; ?></p></div>
		<?php endif; ?>

		<h2>Version &amp; Updates</h2>
		<table class="widefat striped" style="max-width:720px">
			<tbody>
				<tr><td>Installiert</td><td><strong><?php echo esc_html( GL_VERSION ); ?></strong></td></tr>
				<tr><td>Neueste auf GitHub</td><td><?php echo $release ? '<strong>' . esc_html( $release['version'] ) . '</strong>' : '– (nicht erreichbar' . ( get_option( 'gl_update_error' ) ? ': ' . esc_html( get_option( 'gl_update_error' ) ) : '' ) . ')'; ?></td></tr>
				<tr><td>Repository</td><td><a href="<?php echo esc_url( 'https://github.com/' . GL_GITHUB_REPO . '/releases' ); ?>" target="_blank" rel="noopener"><code><?php echo esc_html( GL_GITHUB_REPO ); ?></code> – alle Versionen ↗</a></td></tr>
			</tbody>
		</table>
		<p>
			<?php if ( $has_new ) : ?>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'update-core.php' ) ); ?>">Update auf <?php echo esc_html( $release['version'] ); ?> installieren</a>
			<?php endif; ?>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=gl_check_updates' ), 'gl_check_updates' ) ); ?>">Jetzt nach Updates suchen</a>
		</p>
		<?php if ( $has_new && $release['notes'] ) : ?>
			<h3>Was ist neu?</h3>
			<div style="max-width:720px;background:#fff;padding:12px 16px;border:1px solid #dcdcde"><?php echo wp_kses_post( wpautop( esc_html( $release['notes'] ) ) ); ?></div>
		<?php endif; ?>

		<h2 style="margin-top:2em">Demo-Inhalte</h2>
		<p style="max-width:720px">Legt Slides, das EP-Release, Beispiel-Tourdaten, vier Blogposts, die Seiten „Home“ und „News“ sowie die Bilder an, genau wie im Prototyp. Danach könnt ihr alles bearbeiten oder löschen.</p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
			<?php wp_nonce_field( 'gl_demo' ); ?>
			<input type="hidden" name="action" value="gl_demo_import">
			<button class="button button-primary" <?php disabled( $demo ); ?>>Demo-Inhalte anlegen</button>
		</form>
		<?php if ( $demo ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline" onsubmit="return confirm('Alle Demo-Inhalte (auch bearbeitete) löschen?');">
				<?php wp_nonce_field( 'gl_demo' ); ?>
				<input type="hidden" name="action" value="gl_demo_remove">
				<button class="button">Demo-Inhalte entfernen</button>
			</form>
		<?php endif; ?>

		<h2 style="margin-top:2em">Wo pflege ich was?</h2>
		<ul style="list-style:disc;padding-left:20px;max-width:720px">
			<li><strong>Texte und Absender aller E-Mails:</strong> <a href="<?php echo esc_url( admin_url( 'admin.php?page=ghostlike-emails' ) ); ?>">GHOSTLIKE → E-Mails</a>.</li>
			<li><strong>Alle Updates mit einem Klick:</strong> <a href="<?php echo esc_url( admin_url( 'admin.php?page=ghostlike-updates' ) ); ?>">GHOSTLIKE → Updates</a>.</li>
			<li><strong>Videos zu bunny.net hochladen und als Header-Video eintragen:</strong> <a href="<?php echo esc_url( admin_url( 'admin.php?page=ghostlike-videos' ) ); ?>">GHOSTLIKE → Videos</a>.</li>
			<li><strong>Sektionen ein/aus, Reihenfolge, Menü:</strong> <a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=gl_sections' ) ); ?>">Customizer → GHOSTLIKE → Sektionen &amp; Menü</a>.</li>
			<li><strong>Header-Slider:</strong> links im Menü „Hero-Slides“. Reihenfolge über „Reihenfolge“ (Seiten-Attribute). Pro Slide optional eigenes Bild/Video fürs Handy.</li>
			<li><strong>Ideale Größen Header:</strong> Bild 2400 × 1350 px (Handy 1080 × 1920), unter 400 KB · Video 1920 × 1080 oder 1280 × 720, H.264-MP4, 10–20 s, ohne Ton, unter 8 MB (Handy 720 × 1280, unter 5 MB).</li>
			<li><strong>Releases:</strong> „Releases“. Das Release mit Haken „Groß auf der Startseite“ steht oben.</li>
			<li><strong>Tourdaten:</strong> „Tourdaten“. Vergangene Shows verschwinden automatisch.</li>
			<li><strong>News/Blog:</strong> ganz normal unter „Beiträge“. Beitragsbild nicht vergessen.</li>
			<li><strong>Bio, Social-Links, Kontakt-E-Mail, Social-Bilder:</strong> <a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[panel]=gl_panel' ) ); ?>">Customizer → GHOSTLIKE</a>.</li>
			<li><strong>Impressum &amp; Datenschutz:</strong> als Seiten anlegen und dem Menü „Footer (Rechtliches)“ zuweisen.</li>
		</ul>
	</div>
	<?php
}
