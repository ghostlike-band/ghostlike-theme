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
