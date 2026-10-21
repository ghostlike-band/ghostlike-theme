<?php
/**
 * Sektionen der Startseite: ein-/ausblenden, Reihenfolge (Drag & Drop), im Menü ja/nein, Menü-Name.
 * Customizer → GHOSTLIKE → Sektionen & Menü.
 */

defined( 'ABSPATH' ) || exit;

/** Alle Sektionen: ID => [Name im Backend, Standard-Menüname (leer = nie im Menü)]. */
function gl_section_defs() {
	return array(
		'band'    => array( 'Band-Bio', 'Band' ),
		'marquee' => array( 'Laufband', '' ),
		'music'   => array( 'Releases', 'Music' ),
		'news'    => array( 'News / Blog', 'News' ),
		'tour'    => array( 'Tourdaten', 'Tour' ),
		'feed'    => array( 'Social / Instagram', 'Social' ),
		'contact' => array( 'Kontakt', 'Contact' ),
	);
}

/** Gespeicherte Einstellung + neue Sektionen (aus späteren Updates) hinten angehängt. */
function gl_sections() {
	$defs  = gl_section_defs();
	$saved = json_decode( (string) get_theme_mod( 'gl_sections', '' ), true );
	$out   = array();
	foreach ( is_array( $saved ) ? $saved : array() as $row ) {
		$id = $row['id'] ?? '';
		if ( isset( $defs[ $id ] ) && ! isset( $out[ $id ] ) ) {
			$out[ $id ] = array(
				'id'    => $id,
				'on'    => ! empty( $row['on'] ),
				'menu'  => $defs[ $id ][1] !== '' && ! empty( $row['menu'] ),
				'label' => trim( (string) ( $row['label'] ?? '' ) ) ?: $defs[ $id ][1],
			);
		}
	}
	foreach ( $defs as $id => $d ) {
		if ( ! isset( $out[ $id ] ) ) {
			$out[ $id ] = array( 'id' => $id, 'on' => true, 'menu' => $d[1] !== '' && $id !== 'feed', 'label' => $d[1] );
		}
	}
	return array_values( $out );
}

function gl_sanitize_sections( $value ) {
	$rows  = json_decode( (string) $value, true );
	$defs  = gl_section_defs();
	$clean = array();
	foreach ( is_array( $rows ) ? $rows : array() as $r ) {
		if ( isset( $defs[ $r['id'] ?? '' ] ) ) {
			$clean[] = array(
				'id'    => $r['id'],
				'on'    => ! empty( $r['on'] ),
				'menu'  => ! empty( $r['menu'] ),
				'label' => sanitize_text_field( $r['label'] ?? '' ),
			);
		}
	}
	return wp_json_encode( $clean );
}

add_action( 'customize_register', function ( WP_Customize_Manager $c ) {
	class GL_Sections_Control extends WP_Customize_Control {
		public $type = 'gl_sections';
		public function render_content() {
			$defs = gl_section_defs();
			?>
			<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<span class="description customize-control-description">Ziehen zum Sortieren (oder ↑ ↓). Haken links = sichtbar, „Menü“ = erscheint in der Navigation. Der Header-Slider steht immer oben.</span>
			<ul class="gl-sections">
				<?php foreach ( gl_sections() as $s ) : $menu_ok = $defs[ $s['id'] ][1] !== ''; ?>
					<li data-id="<?php echo esc_attr( $s['id'] ); ?>">
						<span class="gl-sections__grip" title="Ziehen">☰</span>
						<label class="gl-sections__on"><input type="checkbox" class="gl-on" <?php checked( $s['on'] ); ?>> <strong><?php echo esc_html( $defs[ $s['id'] ][0] ); ?></strong></label>
						<span class="gl-sections__move"><button type="button" class="button-link gl-up" aria-label="Nach oben">↑</button><button type="button" class="button-link gl-down" aria-label="Nach unten">↓</button></span>
						<?php if ( $menu_ok ) : ?>
							<label class="gl-sections__menu"><input type="checkbox" class="gl-menu" <?php checked( $s['menu'] ); ?>> Menü</label>
							<input type="text" class="gl-label" value="<?php echo esc_attr( $s['label'] ); ?>" placeholder="Name im Menü">
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<input type="hidden" class="gl-sections__value" <?php $this->link(); ?> value="<?php echo esc_attr( $this->value() ); ?>">
			<?php
		}
	}

	$c->add_section( 'gl_sections', array( 'title' => 'Sektionen & Menü', 'panel' => 'gl_panel', 'priority' => 1 ) );
	$c->add_setting( 'gl_sections', array( 'default' => '', 'sanitize_callback' => 'gl_sanitize_sections', 'transport' => 'refresh' ) );
	$c->add_control( new GL_Sections_Control( $c, 'gl_sections', array(
		'label'       => 'Sektionen der Startseite',
		'section'     => 'gl_sections',
		'description' => 'Ist unter Design → Menüs ein eigenes „Hauptmenü“ zugewiesen, wird stattdessen dieses angezeigt.',
	) ) );
}, 20 );

add_action( 'customize_controls_enqueue_scripts', function () {
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_add_inline_style( 'customize-controls', '
		.gl-sections { margin: 12px 0 0; }
		.gl-sections li { display: grid; grid-template-columns: 18px 1fr auto; gap: 6px 8px; align-items: center; background: #fff; border: 1px solid #dcdcde; padding: 8px 10px; margin: 0 0 6px; }
		.gl-sections li.ui-sortable-helper { box-shadow: 0 4px 12px rgba(0,0,0,.15); }
		.gl-sections__grip { cursor: grab; color: #8c8f94; }
		.gl-sections__move button { font-size: 15px; padding: 0 4px; text-decoration: none; }
		.gl-sections__menu { grid-column: 2; font-size: 12px; }
		.gl-sections .gl-label { grid-column: 2 / 4; width: 100%; }
		.gl-sections li.is-off strong { opacity: .45; text-decoration: line-through; }
	' );
	wp_add_inline_script( 'customize-controls', '
		jQuery(function ($) {
			function sync($ul) {
				var rows = $ul.children("li").map(function () {
					var $li = $(this);
					$li.toggleClass("is-off", !$li.find(".gl-on").prop("checked"));
					return { id: $li.data("id"), on: $li.find(".gl-on").prop("checked"), menu: $li.find(".gl-menu").prop("checked") || false, label: $li.find(".gl-label").val() || "" };
				}).get();
				$ul.siblings(".gl-sections__value").val(JSON.stringify(rows)).trigger("change");
			}
			$(".gl-sections").each(function () {
				var $ul = $(this);
				$ul.sortable({ handle: ".gl-sections__grip", axis: "y", update: function () { sync($ul); } });
				$ul.on("change", "input[type=checkbox]", function () { sync($ul); });
				$ul.on("input", ".gl-label", function () { clearTimeout($ul.data("t")); $ul.data("t", setTimeout(function () { sync($ul); }, 500)); });
				$ul.on("click", ".gl-up, .gl-down", function () {
					var $li = $(this).closest("li");
					if ($(this).hasClass("gl-up")) $li.prev().before($li); else $li.next().after($li);
					sync($ul);
				});
				$ul.children("li").each(function () { $(this).toggleClass("is-off", !$(this).find(".gl-on").prop("checked")); });
			});
		});
	' );
} );

/* ================= BACKEND-SEITE: GHOSTLIKE → Startseite ================= */

add_action( 'admin_menu', function () {
	add_submenu_page( 'ghostlike', 'GHOSTLIKE – Startseite', 'Startseite', 'edit_theme_options', 'ghostlike-startseite', 'gl_sections_page', 1 );
}, 15 );

add_action( 'admin_post_gl_sections_save', function () {
	check_admin_referer( 'gl_sections_save' );
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( 'Keine Berechtigung.' );
	}
	$in   = wp_unslash( $_POST );
	$rows = array();
	foreach ( (array) ( $in['order'] ?? array() ) as $id ) {
		$id     = sanitize_key( $id );
		$rows[] = array(
			'id'    => $id,
			'on'    => ! empty( $in['on'][ $id ] ),
			'menu'  => ! empty( $in['menu'][ $id ] ),
			'label' => $in['label'][ $id ] ?? '',
		);
	}
	set_theme_mod( 'gl_sections', gl_sanitize_sections( wp_json_encode( $rows ) ) );
	wp_safe_redirect( admin_url( 'admin.php?page=ghostlike-startseite&saved=1' ) );
	exit;
} );

function gl_sections_page() {
	wp_enqueue_script( 'jquery-ui-sortable' );
	$defs     = gl_section_defs();
	$own_menu = has_nav_menu( 'main' );
	$hints    = array(
		'band'    => 'Bio und Bandfoto',
		'marquee' => 'Laufband mit kurzen Sätzen',
		'music'   => 'EP / Releases mit Streaming-Buttons',
		'news'    => 'Die neuesten Blogposts',
		'tour'    => 'Kommende Shows',
		'feed'    => 'Instagram-Feed',
		'contact' => 'Kontaktformular',
	);
	?>
	<div class="wrap gl-start">
		<h1>Startseite</h1>
		<?php if ( isset( $_GET['saved'] ) ) : ?><div class="notice notice-success is-dismissible"><p>Gespeichert. <a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank">Startseite ansehen ↗</a></p></div><?php endif; ?>
		<p class="description">Reihenfolge per Ziehen (☰) oder mit ↑ ↓. <strong>Sichtbar</strong> = erscheint auf der Startseite. <strong>Im Menü</strong> = Link oben in der Navigation, mit eigenem Namen. Der Header-Slider steht immer ganz oben.</p>
		<?php if ( $own_menu ) : ?>
			<div class="notice notice-warning inline"><p>Unter Design → Menüs ist ein eigenes „Hauptmenü“ zugewiesen. Die Menü-Haken hier wirken erst, wenn ihr diese Zuweisung entfernt.</p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="gl_sections_save">
			<?php wp_nonce_field( 'gl_sections_save' ); ?>
			<div class="gl-start__head"><span></span><span>Sektion</span><span>Sichtbar</span><span>Im Menü</span><span>Name im Menü</span><span></span></div>
			<ul class="gl-start__list" id="gl-start-list">
				<li class="gl-start__fix"><span class="gl-start__grip">—</span><span><strong>Header-Slider</strong><br><small>Immer oben · Inhalte unter „Hero-Slides“</small></span><span>✓</span><span></span><span></span><span></span></li>
				<?php foreach ( gl_sections() as $s ) : $menu_ok = $defs[ $s['id'] ][1] !== ''; ?>
					<li class="<?php echo $s['on'] ? '' : 'is-off'; ?>">
						<span class="gl-start__grip" title="Ziehen">☰</span>
						<span><strong><?php echo esc_html( $defs[ $s['id'] ][0] ); ?></strong><br><small><?php echo esc_html( $hints[ $s['id'] ] ?? '' ); ?></small><input type="hidden" name="order[]" value="<?php echo esc_attr( $s['id'] ); ?>"></span>
						<label class="gl-switch"><input type="checkbox" class="gl-start__on" name="on[<?php echo esc_attr( $s['id'] ); ?>]" value="1" <?php checked( $s['on'] ); ?>><span></span></label>
						<?php if ( $menu_ok ) : ?>
							<label class="gl-switch"><input type="checkbox" name="menu[<?php echo esc_attr( $s['id'] ); ?>]" value="1" <?php checked( $s['menu'] ); ?>><span></span></label>
							<input type="text" name="label[<?php echo esc_attr( $s['id'] ); ?>]" value="<?php echo esc_attr( $s['label'] ); ?>" placeholder="<?php echo esc_attr( $defs[ $s['id'] ][1] ); ?>">
						<?php else : ?>
							<span class="gl-start__na">–</span><span></span>
						<?php endif; ?>
						<span class="gl-start__move"><button type="button" class="button-link gl-up" aria-label="Nach oben">↑</button><button type="button" class="button-link gl-down" aria-label="Nach unten">↓</button></span>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php submit_button( 'Speichern' ); ?>
		</form>
	</div>
	<style>
		.gl-start { max-width: 980px; }
		.gl-start__head, .gl-start__list li { display: grid; grid-template-columns: 28px minmax(180px, 1fr) 80px 80px minmax(150px, 220px) 56px; gap: 12px; align-items: center; }
		.gl-start__head { padding: 18px 14px 8px; font-size: 12px; text-transform: uppercase; letter-spacing: .06em; color: #646970; }
		.gl-start__list { margin: 0; }
		.gl-start__list li { background: #fff; border: 1px solid #dcdcde; padding: 12px 14px; margin: 0 0 6px; }
		.gl-start__list li.is-off strong { opacity: .45; text-decoration: line-through; }
		.gl-start__list li.ui-sortable-helper { box-shadow: 0 6px 16px rgba(0,0,0,.15); }
		.gl-start__list li.ui-sortable-placeholder { visibility: visible !important; background: #f0f6f2; border: 1px dashed #7C9A85; }
		.gl-start__fix { background: #f6f7f7 !important; color: #646970; }
		.gl-start__grip { cursor: grab; color: #8c8f94; font-size: 16px; text-align: center; }
		.gl-start__fix .gl-start__grip { cursor: default; }
		.gl-start__list small { color: #646970; }
		.gl-start__move button { font-size: 16px; padding: 0 5px; text-decoration: none; }
		.gl-start__na { color: #a7aaad; padding-left: 14px; }
		.gl-start__list input[type=text] { width: 100%; min-width: 0; }
		.gl-switch { position: relative; display: inline-block; width: 40px; height: 22px; }
		.gl-switch input { position: absolute; opacity: 0; width: 100%; height: 100%; margin: 0; cursor: pointer; z-index: 1; }
		.gl-switch span { position: absolute; inset: 0; background: #c3c4c7; border-radius: 22px; transition: .2s; }
		.gl-switch span::after { content: ""; position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; background: #fff; border-radius: 50%; transition: .2s; }
		.gl-switch input:checked + span { background: #7C9A85; }
		.gl-switch input:checked + span::after { transform: translateX(18px); }
		.gl-switch input:focus-visible + span { outline: 2px solid #2271b1; outline-offset: 2px; }
		@media (max-width: 782px) {
			.gl-start__head { display: none; }
			.gl-start__list li { grid-template-columns: 28px 1fr auto; }
			.gl-start__list li > :nth-child(n+3) { grid-column: 2 / 4; }
		}
	</style>
	<script>
	jQuery(function ($) {
		var $ul = $('#gl-start-list');
		$ul.sortable({ handle: '.gl-start__grip', items: 'li:not(.gl-start__fix)', axis: 'y', placeholder: 'ui-sortable-placeholder', forcePlaceholderSize: true });
		$ul.on('click', '.gl-up, .gl-down', function () {
			var $li = $(this).closest('li');
			if ($(this).hasClass('gl-up')) { if (!$li.prev().hasClass('gl-start__fix')) $li.prev().before($li); }
			else $li.next().after($li);
		});
		$ul.on('change', '.gl-start__on', function () { $(this).closest('li').toggleClass('is-off', !this.checked); });
	});
	</script>
	<?php
}
