<?php
/**
 * Bildpool „Bandbilder“ (nur Backend).
 *
 * - GHOSTLIKE → Bandbilder: Fotos hinzufügen, verschlagworten, entfernen.
 * - Im Medien-Fenster (Beitragsbild, Bild-Block …) gibt es oben den Filter „★ Bandbilder“.
 * - Technisch: Schlagwörter-Taxonomie gl_pool an Anhängen. Jedes Poolbild hat den Begriff „bandbild“,
 *   dazu optional weitere (Gruppe, Live, Portrait …). Per REST lesbar (/wp-json/wp/v2/media?gl_pool=ID).
 */

defined( 'ABSPATH' ) || exit;

const GL_POOL_TAX  = 'gl_pool';
const GL_POOL_BASE = 'bandbild';

add_action( 'init', function () {
	register_taxonomy( GL_POOL_TAX, 'attachment', array(
		'labels'                => array( 'name' => 'Bildpool', 'singular_name' => 'Bildpool-Schlagwort' ),
		'public'                => false,
		'show_ui'               => false,
		'show_in_rest'          => true,
		'rest_base'             => 'gl_pool',
		'hierarchical'          => false,
		'update_count_callback' => '_update_generic_term_count',
	) );
	if ( ! term_exists( GL_POOL_BASE, GL_POOL_TAX ) ) {
		wp_insert_term( 'Bandbild', GL_POOL_TAX, array( 'slug' => GL_POOL_BASE ) );
		foreach ( array( 'Gruppe', 'Einzeln', 'Live', 'Studio', 'Outdoor', 'Video-Still' ) as $t ) {
			wp_insert_term( $t, GL_POOL_TAX );
		}
	}
} );

function gl_pool_base_id() {
	$t = get_term_by( 'slug', GL_POOL_BASE, GL_POOL_TAX );
	return $t ? (int) $t->term_id : 0;
}

/** Zusätzliche Schlagwörter (ohne „Bandbild“). */
function gl_pool_tags() {
	$terms = get_terms( array( 'taxonomy' => GL_POOL_TAX, 'hide_empty' => false, 'orderby' => 'name' ) );
	return array_values( array_filter( is_array( $terms ) ? $terms : array(), fn( $t ) => $t->slug !== GL_POOL_BASE ) );
}

/** Wie oft ein Bild als Beitragsbild benutzt wird. */
function gl_pool_usage( $id ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE pm.meta_key = '_thumbnail_id' AND pm.meta_value = %d AND p.post_status IN ('publish','future','draft')",
		$id
	) );
}

/* ================= SEITE ================= */

add_action( 'admin_menu', function () {
	add_submenu_page( 'ghostlike', 'GHOSTLIKE – Bandbilder', 'Bandbilder', 'upload_files', 'ghostlike-bandbilder', 'gl_pool_page' );
}, 20 );

function gl_pool_page() {
	wp_enqueue_media();
	$base = gl_pool_base_id();
	$ids  = get_posts( array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 300,
		'fields'         => 'ids',
		'tax_query'      => array( array( 'taxonomy' => GL_POOL_TAX, 'terms' => $base ) ),
	) );
	$tags = gl_pool_tags();
	$nonce = wp_create_nonce( 'gl_pool' );
	?>
	<div class="wrap gl-pool">
		<h1 class="wp-heading-inline">Bandbilder</h1>
		<button type="button" class="page-title-action" id="gl-pool-add">Bilder hinzufügen</button>
		<hr class="wp-header-end">
		<p class="description" style="max-width:760px">Euer Pool an Bandfotos. Daraus werden Beitragsbilder und Bilder für neue Inhalte ausgesucht, möglichst abwechslungsreich. Beim Beitragsbild oder im Bild-Block findet ihr den Pool im Medien-Fenster über den Filter <strong>★ Bandbilder</strong>. Tipp: Alternativtext ausfüllen (wer/was ist zu sehen), das hilft auch bei Google.</p>

		<div class="gl-pool__bar">
			<button type="button" class="button gl-chip is-on" data-filter="">Alle (<?php echo count( $ids ); ?>)</button>
			<?php foreach ( $tags as $t ) : ?>
				<button type="button" class="button gl-chip" data-filter="<?php echo (int) $t->term_id; ?>"><?php echo esc_html( $t->name ); ?></button>
			<?php endforeach; ?>
			<span class="gl-pool__new"><input type="text" id="gl-pool-newtag" placeholder="Neues Schlagwort" maxlength="40"><button type="button" class="button" id="gl-pool-addtag">+</button></span>
		</div>

		<?php if ( ! $ids ) : ?>
			<div class="gl-pool__leer"><p>Noch keine Bandbilder. Klick auf <strong>Bilder hinzufügen</strong> und wähle Fotos aus der Mediathek oder lade neue hoch.</p></div>
		<?php endif; ?>
		<ul class="gl-pool__grid" id="gl-pool-grid">
			<?php foreach ( $ids as $id ) :
				$meta  = wp_get_attachment_metadata( $id );
				$w     = (int) ( $meta['width'] ?? 0 );
				$h     = (int) ( $meta['height'] ?? 0 );
				$have  = wp_get_object_terms( $id, GL_POOL_TAX, array( 'fields' => 'ids' ) );
				$used  = gl_pool_usage( $id );
				$alt   = get_post_meta( $id, '_wp_attachment_image_alt', true );
				?>
				<li class="gl-pool__item" data-id="<?php echo (int) $id; ?>" data-tags="<?php echo esc_attr( implode( ',', (array) $have ) ); ?>">
					<div class="gl-pool__img"><?php echo wp_get_attachment_image( $id, 'medium', false, array( 'loading' => 'lazy' ) ); ?>
						<span class="gl-pool__fmt"><?php echo $w && $h ? ( $w > $h * 1.15 ? 'Quer' : ( $h > $w * 1.15 ? 'Hoch' : 'Quadrat' ) ) . ' · ' . $w . '×' . $h : ''; ?></span>
					</div>
					<div class="gl-pool__info">
						<span class="gl-pool__used<?php echo $used ? '' : ' is-neu'; ?>"><?php echo $used ? 'als Beitragsbild: ' . $used . '×' : 'noch nicht benutzt'; ?></span>
						<?php if ( ! $alt ) : ?><span class="gl-pool__warn" title="Alternativtext fehlt">Alt-Text fehlt</span><?php endif; ?>
					</div>
					<div class="gl-pool__tags">
						<?php foreach ( $tags as $t ) : ?>
							<button type="button" class="gl-tag<?php echo in_array( $t->term_id, (array) $have, true ) ? ' is-on' : ''; ?>" data-tag="<?php echo (int) $t->term_id; ?>"><?php echo esc_html( $t->name ); ?></button>
						<?php endforeach; ?>
					</div>
					<div class="gl-pool__tools">
						<a href="<?php echo esc_url( get_edit_post_link( $id ) ); ?>">Bearbeiten / Alt-Text</a>
						<button type="button" class="button-link gl-pool__rm">Aus Pool entfernen</button>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<style>
		.gl-pool__bar { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; margin: 18px 0; }
		.gl-chip.is-on { background: #1A1D1B; color: #ECEEE8; border-color: #1A1D1B; }
		.gl-pool__new { display: inline-flex; gap: 4px; margin-left: 8px; }
		.gl-pool__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 14px; margin: 0; }
		.gl-pool__item { background: #fff; border: 1px solid #dcdcde; margin: 0; display: grid; }
		.gl-pool__item.is-hidden { display: none; }
		.gl-pool__img { position: relative; aspect-ratio: 4 / 3; background: #0A0B0A; }
		.gl-pool__img img { width: 100%; height: 100%; object-fit: contain; display: block; }
		.gl-pool__fmt { position: absolute; left: 6px; bottom: 6px; background: rgba(10,11,10,.8); color: #C9CEC6; font: 11px/1.4 monospace; padding: 2px 6px; }
		.gl-pool__info { display: flex; gap: 8px; flex-wrap: wrap; padding: 8px 10px 0; font-size: 12px; color: #646970; }
		.gl-pool__used.is-neu { color: #2e7d32; font-weight: 600; }
		.gl-pool__warn { color: #b32d2e; }
		.gl-pool__tags { display: flex; flex-wrap: wrap; gap: 4px; padding: 8px 10px; }
		.gl-tag { border: 1px solid #c3c4c7; background: #f6f7f7; font-size: 11px; padding: 2px 8px; cursor: pointer; }
		.gl-tag.is-on { background: #7C9A85; border-color: #7C9A85; color: #0A0B0A; }
		.gl-pool__tools { display: flex; justify-content: space-between; padding: 0 10px 10px; font-size: 12px; }
		.gl-pool__rm { color: #b32d2e !important; }
		.gl-pool__leer { background: #fff; border: 1px dashed #c3c4c7; padding: 30px; text-align: center; }
	</style>
	<script>
	(function () {
		const nonce = <?php echo wp_json_encode( $nonce ); ?>;
		const post = (action, data) => { const fd = new FormData(); fd.append('action', action); fd.append('nonce', nonce); Object.entries(data).forEach(([k, v]) => fd.append(k, v)); return fetch(ajaxurl, { method: 'POST', body: fd, credentials: 'same-origin' }).then((r) => r.json()); };

		document.getElementById('gl-pool-add').addEventListener('click', () => {
			const frame = wp.media({ title: 'Bandbilder hinzufügen', button: { text: 'Zum Pool hinzufügen' }, library: { type: 'image' }, multiple: 'add' });
			frame.on('select', () => {
				const ids = frame.state().get('selection').map((a) => a.id);
				post('gl_pool_add', { ids: ids.join(',') }).then(() => location.reload());
			});
			frame.open();
		});

		document.getElementById('gl-pool-grid').addEventListener('click', (e) => {
			const item = e.target.closest('.gl-pool__item'); if (!item) return;
			const tag = e.target.closest('[data-tag]');
			if (tag) {
				const on = !tag.classList.contains('is-on');
				tag.classList.toggle('is-on', on);
				post('gl_pool_tag', { id: item.dataset.id, tag: tag.dataset.tag, on: on ? 1 : 0 }).then((r) => { if (r.success) item.dataset.tags = r.data.join(','); });
				return;
			}
			if (e.target.closest('.gl-pool__rm')) {
				post('gl_pool_remove', { id: item.dataset.id }).then(() => item.remove());
			}
		});

		document.querySelector('.gl-pool__bar').addEventListener('click', (e) => {
			const c = e.target.closest('[data-filter]'); if (!c) return;
			document.querySelectorAll('[data-filter]').forEach((x) => x.classList.toggle('is-on', x === c));
			document.querySelectorAll('.gl-pool__item').forEach((it) => {
				it.classList.toggle('is-hidden', !!c.dataset.filter && !it.dataset.tags.split(',').includes(c.dataset.filter));
			});
		});

		document.getElementById('gl-pool-addtag').addEventListener('click', () => {
			const v = document.getElementById('gl-pool-newtag').value.trim(); if (!v) return;
			post('gl_pool_newtag', { name: v }).then(() => location.reload());
		});
	})();
	</script>
	<?php
}

/* ================= AJAX ================= */

function gl_pool_check() {
	check_ajax_referer( 'gl_pool', 'nonce' );
	if ( ! current_user_can( 'upload_files' ) ) {
		wp_send_json_error( 'Keine Berechtigung.', 403 );
	}
}

add_action( 'wp_ajax_gl_pool_add', function () {
	gl_pool_check();
	foreach ( array_filter( array_map( 'intval', explode( ',', (string) ( $_POST['ids'] ?? '' ) ) ) ) as $id ) {
		if ( wp_attachment_is_image( $id ) ) {
			wp_add_object_terms( $id, gl_pool_base_id(), GL_POOL_TAX );
		}
	}
	wp_send_json_success();
} );

add_action( 'wp_ajax_gl_pool_remove', function () {
	gl_pool_check();
	wp_delete_object_term_relationships( (int) ( $_POST['id'] ?? 0 ), GL_POOL_TAX ); // Bild bleibt in der Mediathek
	wp_send_json_success();
} );

add_action( 'wp_ajax_gl_pool_tag', function () {
	gl_pool_check();
	$id  = (int) ( $_POST['id'] ?? 0 );
	$tag = (int) ( $_POST['tag'] ?? 0 );
	if ( ! empty( $_POST['on'] ) ) {
		wp_add_object_terms( $id, $tag, GL_POOL_TAX );
	} else {
		wp_remove_object_terms( $id, $tag, GL_POOL_TAX );
	}
	wp_send_json_success( wp_get_object_terms( $id, GL_POOL_TAX, array( 'fields' => 'ids' ) ) );
} );

add_action( 'wp_ajax_gl_pool_newtag', function () {
	gl_pool_check();
	$name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	if ( $name !== '' && ! term_exists( $name, GL_POOL_TAX ) ) {
		wp_insert_term( $name, GL_POOL_TAX );
	}
	wp_send_json_success();
} );

/* ================= MEDIEN-FENSTER: FILTER „★ BANDBILDER“ ================= */

// Abfrage im Medien-Fenster nach Pool/Schlagwort einschränken
add_filter( 'ajax_query_attachments_args', function ( $q ) {
	$f = isset( $_REQUEST['query']['gl_pool'] ) ? (int) $_REQUEST['query']['gl_pool'] : 0;
	unset( $q['gl_pool'] ); // sonst liest WordPress die Zahl zusätzlich als Schlagwort-Namen
	if ( $f ) {
		$q['tax_query'] = array( array( 'taxonomy' => GL_POOL_TAX, 'terms' => $f ) );
	}
	return $q;
} );

add_action( 'wp_enqueue_media', function () {
	$opts = array( array( 'id' => 0, 'name' => 'Alle Medien' ), array( 'id' => gl_pool_base_id(), 'name' => '★ Bandbilder' ) );
	foreach ( gl_pool_tags() as $t ) {
		$opts[] = array( 'id' => (int) $t->term_id, 'name' => '★ Bandbilder · ' . $t->name );
	}
	add_action( 'admin_print_footer_scripts', function () use ( $opts ) {
		?>
		<script>
		(function () {
			if (!window.wp || !wp.media || !wp.media.view) return;
			const OPTS = <?php echo wp_json_encode( $opts ); ?>;
			const PoolFilter = wp.media.view.AttachmentFilters.extend({
				id: 'gl-pool-filter',
				createFilters: function () {
					const f = {};
					OPTS.forEach((o) => { f[o.id || 'all'] = { text: o.name, props: { gl_pool: o.id || null }, priority: 10 }; });
					this.filters = f;
				},
			});
			const Browser = wp.media.view.AttachmentsBrowser;
			wp.media.view.AttachmentsBrowser = Browser.extend({
				createToolbar: function () {
					Browser.prototype.createToolbar.call(this);
					this.toolbar.set('glPoolLabel', new wp.media.view.Label({ value: 'Bildpool', attributes: { for: 'gl-pool-filter' }, priority: -75 }).render());
					this.toolbar.set('glPool', new PoolFilter({ controller: this.controller, model: this.collection.props, priority: -75 }).render());
				},
			});
		})();
		</script>
		<?php
	}, 99 );
} );
