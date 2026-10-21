<?php
/**
 * SEO & GEO (Auffindbarkeit bei Google und KI-Suchen).
 *
 * - Seitentitel, Meta-Description, Canonical, Open Graph / Twitter-Karten
 * - Strukturierte Daten (JSON-LD): MusicGroup, MusicAlbum, MusicEvent, BlogPosting, WebSite
 * - „For fans of“ (Sounds like) als sichtbarer Text und in den Daten
 * - /llms.txt: kompakte Band-Infos für KI-Assistenten (ChatGPT, Perplexity, Gemini …)
 * - Sitemap ohne Benutzer, noindex für Suche/404
 *
 * Schaltet sich ab, sobald ein SEO-Plugin (Yoast, Rank Math, SEOPress, AIOSEO) aktiv ist –
 * nur llms.txt und „For fans of“ bleiben.
 */

defined( 'ABSPATH' ) || exit;

function gl_seo_plugin_active() {
	return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'AIOSEO_VERSION' );
}

/* ================= EINSTELLUNGEN (Customizer → GHOSTLIKE → SEO & Teilen) ================= */

function gl_seo_defaults() {
	return array(
		'seo_title'       => 'GHOSTLIKE – Metalcore Band from Augsburg, Germany',
		'seo_description' => 'GHOSTLIKE is a metalcore band from Augsburg, Germany. Heavy, atmospheric and cinematic – for fans of Architects, Bad Omens, Spiritbox and Bring Me The Horizon. Debut EP The Quiet Collapse out now.',
		'fans_of'         => 'Architects, Bad Omens, Spiritbox, Bring Me The Horizon',
		'genres'          => 'Metalcore, Post-Hardcore, Alternative Metal',
		'city'            => 'Augsburg',
		'country'         => 'Germany',
		'founded'         => '2025',
		'press'           => "Paranoyd Magazin – Interview (2025) | https://paranoyd-magazin.de/interview/ghostlike-frisch-und-doch-bekannt/\nLelahel Metal – Interview (2025) | https://www.lelahelmetal.net/2025/10/interview-ghostlike.html\nFrontstage Magazine – Debut single news (2025) | https://frontstage-magazine.de/2025/10/09/ghostlike-veroeffentlichen-ihre-erste-single-nothings-okay/",
	);
}

function gl_seo( $key ) {
	return get_theme_mod( 'gl_' . $key, gl_seo_defaults()[ $key ] ?? '' );
}

/** Liste aus „A, B, C“ */
function gl_seo_list( $key ) {
	return array_values( array_filter( array_map( 'trim', explode( ',', (string) gl_seo( $key ) ) ), 'strlen' ) );
}

add_action( 'customize_register', function ( $c ) {
	$c->add_section( 'gl_seo', array( 'title' => 'SEO & Teilen', 'panel' => 'gl_panel', 'description' => 'Wie GHOSTLIKE bei Google, Instagram-/WhatsApp-Vorschauen und KI-Suchen erscheint. Alles auf Englisch (international).' ) );
	$fields = array(
		'seo_title'       => array( 'Seitentitel Startseite', 'text', 'Erscheint als blaue Zeile bei Google. Ideal 50–60 Zeichen.' ),
		'seo_description' => array( 'Beschreibung (Meta-Description)', 'textarea', 'Zwei Sätze unter dem Titel bei Google. Ideal 140–160 Zeichen.' ),
		'fans_of'         => array( 'For fans of / Sounds like', 'text', 'Bekannte Bands, mit Komma getrennt. Steckt in Meta-Beschreibung, strukturierten Daten und llms.txt. Auf der Seite nur sichtbar, wenn unten angehakt.' ),
		'genres'          => array( 'Genres', 'text', 'Mit Komma getrennt.' ),
		'city'            => array( 'Stadt', 'text', '' ),
		'country'         => array( 'Land', 'text', '' ),
		'founded'         => array( 'Gründungsjahr', 'text', '' ),
		'press'           => array( 'Presse (für llms.txt)', 'textarea', 'Eine Zeile pro Artikel: Titel | Link' ),
	);
	foreach ( $fields as $k => $f ) {
		$c->add_setting( 'gl_' . $k, array( 'default' => gl_seo_defaults()[ $k ], 'sanitize_callback' => $f[1] === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field' ) );
		$c->add_control( 'gl_' . $k, array( 'label' => $f[0], 'section' => 'gl_seo', 'type' => $f[1], 'description' => $f[2] ) );
	}
	$c->add_setting( 'gl_fans_of_visible', array( 'default' => 0, 'sanitize_callback' => 'absint' ) );
	$c->add_control( 'gl_fans_of_visible', array( 'label' => '„For fans of“ unter der Bio sichtbar anzeigen', 'section' => 'gl_seo', 'type' => 'checkbox' ) );
	$c->add_setting( 'gl_share_image', array( 'sanitize_callback' => 'absint' ) );
	$c->add_control( new WP_Customize_Media_Control( $c, 'gl_share_image', array( 'label' => 'Vorschaubild beim Teilen', 'section' => 'gl_seo', 'mime_type' => 'image', 'description' => '1200 × 630 px. Für Startseite und Seiten ohne Beitragsbild. Leer = Bandfoto.' ) ) );
}, 20 );

/* ================= HILFEN ================= */

function gl_seo_description() {
	if ( is_singular() && ! is_front_page() ) {
		$p = get_queried_object();
		$d = has_excerpt( $p ) ? get_the_excerpt( $p ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $p->post_content ) ), 30, '…' );
		return trim( preg_replace( '/\s+/', ' ', (string) $d ) );
	}
	if ( is_category() ) {
		return single_cat_title( '', false ) . ' from GHOSTLIKE, metalcore band from ' . gl_seo( 'city' ) . ', ' . gl_seo( 'country' ) . '.';
	}
	if ( is_home() ) {
		return 'News, releases, interviews and studio updates from GHOSTLIKE – metalcore from ' . gl_seo( 'city' ) . ', ' . gl_seo( 'country' ) . '.';
	}
	return (string) gl_seo( 'seo_description' );
}

/** [url, breite, höhe] des Vorschaubilds */
function gl_seo_image() {
	$id = 0;
	if ( is_singular() && ! is_front_page() && has_post_thumbnail() ) {
		$id = get_post_thumbnail_id();
	}
	$id = $id ?: (int) get_theme_mod( 'gl_share_image' ) ?: (int) gl_opt( 'band_image', 0 );
	if ( $id && ( $src = wp_get_attachment_image_src( $id, 'gl-card' ) ) ) {
		return array( $src[0], $src[1], $src[2] );
	}
	return array( GL_URI . '/assets/img/demo/band-wall-wide.jpg', 1800, 941 );
}

function gl_seo_url() {
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	if ( is_singular() ) {
		return get_permalink();
	}
	if ( is_home() && get_option( 'page_for_posts' ) ) {
		return get_permalink( (int) get_option( 'page_for_posts' ) );
	}
	if ( is_category() ) {
		return get_category_link( get_queried_object_id() );
	}
	return home_url( add_query_arg( array() ) );
}

/* ================= TITEL & META ================= */

add_filter( 'document_title_separator', fn() => '–' );
add_filter( 'document_title_parts', function ( $parts ) {
	if ( gl_seo_plugin_active() ) {
		return $parts;
	}
	if ( is_front_page() ) {
		return array( 'title' => gl_seo( 'seo_title' ) );
	}
	unset( $parts['tagline'] );
	$parts['site'] = 'GHOSTLIKE';
	return $parts;
} );

add_action( 'wp_head', function () {
	if ( gl_seo_plugin_active() ) {
		return;
	}
	$title = is_front_page() ? gl_seo( 'seo_title' ) : wp_get_document_title();
	$desc  = gl_seo_description();
	$url   = gl_seo_url();
	[ $img, $w, $h ] = gl_seo_image();
	$type  = is_singular( 'post' ) ? 'article' : 'website';

	$tags = array(
		array( 'name', 'description', $desc ),
		array( 'property', 'og:site_name', 'GHOSTLIKE' ),
		array( 'property', 'og:locale', 'en_US' ),
		array( 'property', 'og:type', $type ),
		array( 'property', 'og:title', wp_strip_all_tags( $title ) ),
		array( 'property', 'og:description', $desc ),
		array( 'property', 'og:url', $url ),
		array( 'property', 'og:image', $img ),
		array( 'property', 'og:image:width', $w ),
		array( 'property', 'og:image:height', $h ),
		array( 'name', 'twitter:card', 'summary_large_image' ),
		array( 'name', 'twitter:title', wp_strip_all_tags( $title ) ),
		array( 'name', 'twitter:description', $desc ),
		array( 'name', 'twitter:image', $img ),
	);
	if ( $type === 'article' ) {
		$tags[] = array( 'property', 'article:published_time', get_the_date( 'c' ) );
		$tags[] = array( 'property', 'article:modified_time', get_the_modified_date( 'c' ) );
	}
	echo "\n<!-- GHOSTLIKE SEO -->\n";
	foreach ( $tags as $t ) {
		if ( $t[2] !== '' && $t[2] !== null ) {
			printf( '<meta %s="%s" content="%s">' . "\n", $t[0], esc_attr( $t[1] ), esc_attr( (string) $t[2] ) );
		}
	}
	// Canonical für Startseite und Übersichten (Einzelseiten setzt WordPress selbst)
	if ( ! is_singular() || is_front_page() ) {
		echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
	}
	echo '<link rel="alternate" type="text/plain" title="LLM summary" href="' . esc_url( home_url( '/llms.txt' ) ) . '">' . "\n";
	echo '<script type="application/ld+json">' . wp_json_encode( gl_seo_graph(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "</script>\n";
}, 2 );

// Suche, 404 und Anhangseiten nicht indexieren
add_filter( 'wp_robots', function ( $robots ) {
	if ( is_search() || is_404() || is_attachment() || is_author() || is_date() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	} elseif ( get_option( 'blog_public' ) ) {
		$robots['max-image-preview'] = 'large';
	}
	return $robots;
} );

// Sitemap: keine Benutzerseiten (verrät sonst Login-Namen)
add_filter( 'wp_sitemaps_add_provider', fn( $provider, $name ) => $name === 'users' ? false : $provider, 10, 2 );
add_filter( 'wp_sitemaps_taxonomies', function ( $tax ) {
	unset( $tax['post_tag'], $tax['post_format'] );
	return $tax;
} );

/* ================= STRUKTURIERTE DATEN ================= */

function gl_seo_band_id() {
	return home_url( '/#band' );
}

function gl_seo_band() {
	$same = array();
	foreach ( gl_socials() as $s ) {
		$same[] = $s['url'];
	}
	$members = array();
	foreach ( gl_pipe_rows( gl_opt( 'members' ) ) as $m ) {
		$members[] = array(
			'@type'    => 'OrganizationRole',
			'member'   => array( '@type' => 'Person', 'name' => $m[0] ),
			'roleName' => $m[1] ?? 'Member',
		);
	}
	[ $img ] = gl_seo_image();
	$fans = gl_seo_list( 'fans_of' );
	$band = array(
		'@type'            => 'MusicGroup',
		'@id'              => gl_seo_band_id(),
		'name'             => 'GHOSTLIKE',
		'alternateName'    => 'Ghostlike',
		'url'              => home_url( '/' ),
		'description'      => gl_seo( 'seo_description' ),
		'genre'            => gl_seo_list( 'genres' ),
		'image'            => $img,
		'logo'             => GL_URI . '/assets/img/email-logo.png',
		'foundingDate'     => gl_seo( 'founded' ),
		'foundingLocation' => array( '@type' => 'Place', 'name' => gl_seo( 'city' ) . ', ' . gl_seo( 'country' ) ),
		'location'         => array( '@type' => 'Place', 'address' => array( '@type' => 'PostalAddress', 'addressLocality' => gl_seo( 'city' ), 'addressCountry' => gl_seo( 'country' ) === 'Germany' ? 'DE' : gl_seo( 'country' ) ) ),
		'sameAs'           => $same,
		'member'           => $members,
		'keywords'         => implode( ', ', array_merge( gl_seo_list( 'genres' ), array_map( fn( $f ) => 'sounds like ' . $f, $fans ), array( 'German metalcore', 'metalcore band ' . gl_seo( 'city' ) ) ) ),
	);
	return array_filter( $band );
}

/** Releases als MusicAlbum mit Tracks */
function gl_seo_albums() {
	$out = array();
	foreach ( get_posts( array( 'post_type' => 'gl_release', 'posts_per_page' => 20, 'orderby' => 'menu_order', 'order' => 'ASC' ) ) as $r ) {
		$tracks = array();
		foreach ( gl_pipe_rows( gl_meta( $r->ID, 'tracks' ) ) as $i => $t ) {
			$track = array( '@type' => 'MusicRecording', 'name' => $t[0], 'position' => $i + 1, 'byArtist' => array( '@id' => gl_seo_band_id() ) );
			if ( ! empty( $t[2] ) ) {
				$track['video'] = array( '@type' => 'VideoObject', 'name' => $t[0] . ' (Official Music Video)', 'embedUrl' => $t[2], 'thumbnailUrl' => gl_seo_yt_thumb( $t[2] ), 'uploadDate' => gl_meta( $r->ID, 'release_date' ) ?: get_the_date( 'Y-m-d', $r ), 'description' => 'Official music video for ' . $t[0] . ' by GHOSTLIKE.' );
			}
			$tracks[] = array_filter( $track );
		}
		$links = array();
		foreach ( array_keys( gl_platforms() ) as $k ) {
			if ( $u = gl_meta( $r->ID, $k ) ) {
				$links[] = $u;
			}
		}
		$out[] = array_filter( array(
			'@type'         => 'MusicAlbum',
			'name'          => get_the_title( $r ),
			'byArtist'      => array( '@id' => gl_seo_band_id() ),
			'albumReleaseType' => count( $tracks ) > 1 && count( $tracks ) < 7 ? 'https://schema.org/EPRelease' : null,
			'numTracks'     => count( $tracks ) ?: null,
			'datePublished' => gl_meta( $r->ID, 'release_date' ) ?: null,
			'image'         => has_post_thumbnail( $r ) ? get_the_post_thumbnail_url( $r, 'gl-square' ) : null,
			'track'         => $tracks ?: null,
			'sameAs'        => $links ?: null,
			'genre'         => gl_seo_list( 'genres' ),
		) );
	}
	return $out;
}

function gl_seo_yt_thumb( $url ) {
	return preg_match( '~(?:youtu\.be/|v=|embed/)([\w-]{11})~', (string) $url, $m ) ? 'https://i.ytimg.com/vi/' . $m[1] . '/maxresdefault.jpg' : null;
}

/** Kommende Shows als MusicEvent */
function gl_seo_events() {
	$out = array();
	foreach ( get_posts( array( 'post_type' => 'gl_show', 'posts_per_page' => 20, 'meta_key' => '_gl_date', 'orderby' => 'meta_value', 'order' => 'ASC' ) ) as $s ) {
		$date = gl_meta( $s->ID, 'date' );
		if ( ! $date || $date < wp_date( 'Y-m-d' ) || get_post_meta( $s->ID, '_gl_demo', true ) ) {
			continue;
		}
		$status = gl_meta( $s->ID, 'status' );
		$out[]  = array_filter( array(
			'@type'               => 'MusicEvent',
			'name'                => 'GHOSTLIKE live in ' . gl_meta( $s->ID, 'city' ),
			'startDate'           => $date,
			'eventStatus'         => $status === 'cancelled' ? 'https://schema.org/EventCancelled' : 'https://schema.org/EventScheduled',
			'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
			'location'            => array( '@type' => 'Place', 'name' => gl_meta( $s->ID, 'venue' ) ?: gl_meta( $s->ID, 'city' ), 'address' => gl_meta( $s->ID, 'city' ) ),
			'performer'           => array( '@id' => gl_seo_band_id() ),
			'offers'              => gl_meta( $s->ID, 'ticket_url' ) ? array( '@type' => 'Offer', 'url' => gl_meta( $s->ID, 'ticket_url' ), 'availability' => $status === 'soldout' ? 'https://schema.org/SoldOut' : 'https://schema.org/InStock' ) : null,
		) );
	}
	return $out;
}

function gl_seo_graph() {
	$graph = array(
		array( '@type' => 'WebSite', '@id' => home_url( '/#website' ), 'url' => home_url( '/' ), 'name' => 'GHOSTLIKE', 'inLanguage' => 'en', 'publisher' => array( '@id' => gl_seo_band_id() ) ),
	);
	if ( is_front_page() ) {
		$graph[] = gl_seo_band();
		$graph   = array_merge( $graph, gl_seo_albums(), gl_seo_events() );
	} else {
		$graph[] = array( '@type' => 'MusicGroup', '@id' => gl_seo_band_id(), 'name' => 'GHOSTLIKE', 'url' => home_url( '/' ) );
	}
	if ( is_singular( 'post' ) ) {
		[ $img ] = gl_seo_image();
		$graph[] = array(
			'@type'            => 'BlogPosting',
			'headline'         => get_the_title(),
			'description'      => gl_seo_description(),
			'image'            => $img,
			'datePublished'    => get_the_date( 'c' ),
			'dateModified'     => get_the_modified_date( 'c' ),
			'url'              => get_permalink(),
			'mainEntityOfPage' => get_permalink(),
			'inLanguage'       => 'en',
			'author'           => array( '@id' => gl_seo_band_id() ),
			'publisher'        => array( '@id' => gl_seo_band_id() ),
			'articleSection'   => ( get_the_category()[0]->name ?? 'News' ),
		);
	}
	return array( '@context' => 'https://schema.org', '@graph' => $graph );
}

/* ================= /llms.txt ================= */

add_action( 'init', function () {
	add_rewrite_rule( '^llms\.txt$', 'index.php?gl_llms=1', 'top' );
	if ( get_option( 'gl_rewrite_v' ) !== 'llms2' ) {
		flush_rewrite_rules( false );
		update_option( 'gl_rewrite_v', 'llms2' );
	}
}, 20 );
add_filter( 'query_vars', function ( $v ) {
	$v[] = 'gl_llms';
	return $v;
} );

// Kein Schrägstrich anhängen (/llms.txt muss exakt so erreichbar sein)
add_filter( 'redirect_canonical', fn( $url ) => get_query_var( 'gl_llms' ) ? false : $url );

add_action( 'template_redirect', function () {
	if ( ! get_query_var( 'gl_llms' ) ) {
		return;
	}
	$l   = array();
	$l[] = '# GHOSTLIKE';
	$l[] = '';
	$l[] = '> ' . gl_seo( 'seo_description' );
	$l[] = '';
	$l[] = '- Genre: ' . implode( ', ', gl_seo_list( 'genres' ) );
	$l[] = '- From: ' . gl_seo( 'city' ) . ', ' . gl_seo( 'country' ) . ( gl_seo( 'founded' ) ? ' (formed ' . gl_seo( 'founded' ) . ')' : '' );
	if ( $fans = gl_seo_list( 'fans_of' ) ) {
		$l[] = '- For fans of / sounds like: ' . implode( ', ', $fans );
	}
	$members = array_map( fn( $m ) => $m[0] . ( isset( $m[1] ) ? ' (' . $m[1] . ')' : '' ), gl_pipe_rows( gl_opt( 'members' ) ) );
	if ( $members ) {
		$l[] = '- Members: ' . implode( ', ', $members );
	}
	$l[] = '- Website: ' . home_url( '/' );
	$l[] = '';
	$l[] = '## About';
	$l[] = '';
	$l[] = trim( gl_opt( 'bio_lead' ) . ' ' . gl_opt( 'bio_text' ) );
	$l[] = '';
	$l[] = '## Releases';
	$l[] = '';
	foreach ( get_posts( array( 'post_type' => 'gl_release', 'posts_per_page' => 20, 'orderby' => 'menu_order', 'order' => 'ASC' ) ) as $r ) {
		$date   = gl_meta( $r->ID, 'release_date' );
		$tracks = array_map( fn( $t ) => $t[0] . ( ! empty( $t[2] ) ? ' (video: ' . $t[2] . ')' : '' ), gl_pipe_rows( gl_meta( $r->ID, 'tracks' ) ) );
		$link   = gl_meta( $r->ID, 'spotify' ) ?: gl_meta( $r->ID, 'smartlink' ) ?: gl_meta( $r->ID, 'apple' );
		$l[]    = '- ' . get_the_title( $r ) . ( $date ? ' (' . $date . ')' : '' ) . ( $link ? ': ' . $link : '' ) . ( $tracks ? ' – Tracks: ' . implode( '; ', $tracks ) : '' );
	}
	$l[] = '';
	$l[] = '## Latest news';
	$l[] = '';
	foreach ( get_posts( array( 'posts_per_page' => 10 ) ) as $p ) {
		$l[] = '- [' . get_the_title( $p ) . '](' . get_permalink( $p ) . ') – ' . get_the_date( 'Y-m-d', $p );
	}
	if ( $press = gl_pipe_rows( gl_seo( 'press' ) ) ) {
		$l[] = '';
		$l[] = '## Press';
		$l[] = '';
		foreach ( $press as $p ) {
			$l[] = '- [' . $p[0] . '](' . ( $p[1] ?? '' ) . ')';
		}
	}
	$l[] = '';
	$l[] = '## Links';
	$l[] = '';
	foreach ( gl_socials() as $s ) {
		$l[] = '- ' . $s['label'] . ': ' . $s['url'];
	}
	$l[] = '- Contact (booking & press): ' . home_url( '/#contact' );

	nocache_headers();
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );
	echo wp_strip_all_tags( implode( "\n", $l ) ) . "\n"; // phpcs:ignore
	exit;
} );

/* ================= SICHTBAR: „FOR FANS OF“ ================= */

function gl_fans_of_line() {
	$fans = gl_seo_list( 'fans_of' );
	if ( ! $fans || ! get_theme_mod( 'gl_fans_of_visible', 0 ) ) {
		return '';
	}
	return '<p class="fans-of" data-reveal><span class="meta">For fans of</span> <span class="fans-of__list">' . esc_html( implode( ' · ', $fans ) ) . '</span></p>';
}
