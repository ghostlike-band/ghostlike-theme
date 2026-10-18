<?php
/**
 * Demo-Inhalte per Klick (Design → GHOSTLIKE). Alles wird mit _gl_demo markiert
 * und lässt sich mit einem Klick wieder entfernen.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_post_gl_demo_import', function () {
	check_admin_referer( 'gl_demo' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Keine Berechtigung.' );
	}
	if ( ! get_option( 'gl_demo_imported' ) ) {
		@set_time_limit( 300 );
		gl_demo_import();
	}
	wp_safe_redirect( admin_url( 'themes.php?page=ghostlike&demo=1' ) );
	exit;
} );

add_action( 'admin_post_gl_demo_remove', function () {
	check_admin_referer( 'gl_demo' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Keine Berechtigung.' );
	}
	gl_demo_remove();
	wp_safe_redirect( admin_url( 'themes.php?page=ghostlike&demo=removed' ) );
	exit;
} );

function gl_demo_attach( $file, $title ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	$src = GL_DIR . '/assets/img/demo/' . $file;
	if ( ! file_exists( $src ) ) {
		return 0;
	}
	$tmp = wp_tempnam( $file );
	copy( $src, $tmp );
	$id = media_handle_sideload( array( 'name' => $file, 'tmp_name' => $tmp ), 0, $title );
	if ( is_wp_error( $id ) ) {
		@unlink( $tmp );
		return 0;
	}
	update_post_meta( $id, '_gl_demo', 1 );
	return $id;
}

function gl_demo_post( $args, $meta = array(), $thumb = 0 ) {
	$id = wp_insert_post( wp_slash( $args + array( 'post_status' => 'publish' ) ) );
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}
	update_post_meta( $id, '_gl_demo', 1 );
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, '_gl_' . $k, $v );
	}
	if ( $thumb ) {
		set_post_thumbnail( $id, $thumb );
	}
	return $id;
}

function gl_p( ...$paras ) {
	return implode( "\n\n", array_map( fn( $p ) => "<!-- wp:paragraph -->\n<p>{$p}</p>\n<!-- /wp:paragraph -->", $paras ) );
}

function gl_demo_import() {
	// WordPress-Standardinhalte („Hallo Welt!“, „Beispiel-Seite“) in den Papierkorb.
	foreach ( get_posts( array( 'post_type' => array( 'post', 'page' ), 'post_status' => 'publish', 'posts_per_page' => 10, 'post__in' => array( 1, 2 ) ) ) as $p ) {
		if ( in_array( $p->post_name, array( 'hello-world', 'hallo-welt', 'sample-page', 'beispiel-seite' ), true ) ) {
			wp_trash_post( $p->ID );
		}
	}

	$img = array();
	foreach ( array(
		'band'     => array( 'band-industrial.jpg', 'GHOSTLIKE – Industriehalle' ),
		'portrait' => array( 'band-portrait.jpg', 'GHOSTLIKE – Hochformat' ),
		'wall'     => array( 'band-wall.jpg', 'GHOSTLIKE – Betonwand' ),
		'wall2'    => array( 'band-wall-2.jpg', 'GHOSTLIKE – Betonwand 2' ),
		'wide'     => array( 'band-wall-wide.jpg', 'GHOSTLIKE – Betonwand breit' ),
		'dome'     => array( 'dome.jpg', 'Kuppel' ),
		'ep'       => array( 'ep-the-quiet-collapse.jpg', 'Cover – The Quiet Collapse' ),
		'hand'     => array( 'single-hand.jpg', 'Single-Cover – Hand' ),
		'thorns'   => array( 'single-thorns.jpg', 'Single-Cover – Dornen' ),
		'ice'      => array( 'single-ice.jpg', 'Single-Cover – Eis' ),
		'video'    => array( 'demo-loop.mp4', 'Demo-Loop' ),
	) as $key => $f ) {
		$img[ $key ] = gl_demo_attach( $f[0], $f[1] );
	}
	$yt  = 'https://www.youtube.com/@ghostlikeband';
	$url = fn( $k ) => $img[ $k ] ? wp_get_attachment_url( $img[ $k ] ) : '';

	// Seiten: Startseite + News + Impressum
	$home = gl_demo_post( array( 'post_type' => 'page', 'post_title' => 'Home', 'post_content' => '' ) );
	$news = gl_demo_post( array( 'post_type' => 'page', 'post_title' => 'News', 'post_content' => '' ) );
	$imp  = gl_demo_post( array( 'post_type' => 'page', 'post_title' => 'Imprint', 'post_content' => gl_p( '[Impressum von der alten Website hier einfügen]' ) ) );
	update_option( 'gl_demo_prev_reading', array( get_option( 'show_on_front' ), get_option( 'page_on_front' ), get_option( 'page_for_posts' ) ) );
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home );
	update_option( 'page_for_posts', $news );

	// Footer-Menü
	$menu_id = wp_create_nav_menu( 'Rechtliches (Demo)' );
	if ( ! is_wp_error( $menu_id ) ) {
		wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-object-id' => $imp, 'menu-item-object' => 'page', 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
		$privacy = (int) get_option( 'wp_page_for_privacy_policy' );
		if ( $privacy ) {
			wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-object-id' => $privacy, 'menu-item-object' => 'page', 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
		}
		$locations           = get_theme_mod( 'nav_menu_locations', array() );
		$locations['footer'] = $menu_id;
		set_theme_mod( 'nav_menu_locations', $locations );
		update_option( 'gl_demo_menu', $menu_id );
	}

	// Hero-Slides
	$slides = array(
		array( 'Band', $img['band'], array( 'kicker' => 'Metalcore · Augsburg, DE', 'show_logo' => '1', 'subline' => 'Five-piece metalcore from Augsburg, Germany.', 'button_text' => 'Meet the band', 'button_url' => '#band' ) ),
		array( 'Video – Nothing’s Okay', $img['hand'], array( 'kicker' => 'Official video · Single 01', 'headline' => "Nothing’s\nokay", 'video' => $url( 'video' ), 'button_text' => 'Watch the video', 'button_url' => $yt ) ),
		array( 'EP – The Quiet Collapse', $img['ice'], array( 'kicker' => 'EP · Out now', 'headline' => "The quiet\ncollapse", 'button_text' => 'Listen now', 'button_url' => '#music' ) ),
		array( 'News – Studio', $img['dome'], array( 'kicker' => 'News · Studio log', 'headline' => "Back in\nthe studio", 'button_text' => 'Read more', 'button_url' => '#news' ) ),
		array( 'Live 2027', $img['wide'], array( 'kicker' => 'Live · 2027', 'headline' => "Catch us\nlive", 'button_text' => 'Tour dates', 'button_url' => '#tour' ) ),
	);
	foreach ( $slides as $i => $s ) {
		gl_demo_post( array( 'post_type' => 'gl_slide', 'post_title' => $s[0], 'menu_order' => $i + 1 ), $s[2], $s[1] );
	}

	// Release
	$tracks = implode( "\n", array(
		'Nothing’s Okay | single | ' . $yt . ' | ' . $url( 'hand' ),
		'The Price We Pay | single | ' . $yt . ' | ' . $url( 'thorns' ),
		'Deep Blue Sea | single | ' . $yt . ' | ' . $url( 'ice' ),
		'Who Am I',
		'Spiral',
	) );
	gl_demo_post( array( 'post_type' => 'gl_release', 'post_title' => 'The Quiet Collapse', 'menu_order' => 1 ), array(
		'featured' => '1',
		'kicker'   => 'EP · Era I · 5 songs · Out now',
		'tracks'   => $tracks,
		'bandcamp' => 'https://ghostlike.bandcamp.com/track/nothings-okay',
		'youtube'  => $yt,
	), $img['ep'] );

	// Tourdaten (Platzhalter)
	foreach ( array( array( 60, 'tickets' ), array( 74, 'last' ), array( 90, 'soldout' ) ) as $n => $s ) {
		gl_demo_post( array( 'post_type' => 'gl_show', 'post_title' => 'Beispiel-Show ' . ( $n + 1 ) ), array(
			'date'   => gmdate( 'Y-m-d', time() + $s[0] * DAY_IN_SECONDS ),
			'city'   => '[City]',
			'venue'  => '[Venue]',
			'status' => $s[1],
		) );
	}

	// Blogposts
	$cat = wp_create_category( 'Studio' );
	gl_demo_post( array(
		'post_title'   => 'Back in the studio',
		'post_date'    => '2026-10-14 18:00:00',
		'post_excerpt' => 'It’s been quiet around here, on purpose. We are writing and recording new songs, and something bigger is taking shape.',
		'post_category'=> array( $cat ),
		'post_content' => gl_p(
			'It’s been quiet around here. On purpose.',
			'Since <em>The Quiet Collapse</em> came out, we have been writing. Every Tuesday in the rehearsal room, and a lot of late nights in our home studios on top of that. Valentin tracks drums at his place, guitars and vocals happen at Robin’s, and Alexander is already pulling together the first rough mixes.',
			'The board on our studio wall is filling up fast. Some songs are almost fully tracked, others only have a riff, a structure and a working title we’d rather not repeat in public.',
			'Count the songs and you get a lot more than a single. A lot more than an EP, too. We won’t say the word yet. 😉',
			'We’ll keep you posted here: studio diaries, snippets, and the first new single as soon as it’s ready.'
		),
	), array(), $img['dome'] );
	gl_demo_post( array(
		'post_title'   => 'The Quiet Collapse is out now',
		'post_date'    => '2026-03-20 18:00:00',
		'post_excerpt' => 'Five songs, one era. Our EP is out on every platform.',
		'post_category'=> array( wp_create_category( 'Release' ) ),
		'post_content' => gl_p(
			'Our EP is out now on every streaming platform.',
			'Five songs that started Era I: Nothing’s Okay, The Price We Pay, Deep Blue Sea, Who Am I and Spiral. Three of them came out as singles first, each with its own music video.',
			'Thank you to everyone who listened, shared and came to the shows. This one is yours now.'
		),
	), array(), $img['ep'] );
	gl_demo_post( array(
		'post_title'   => 'Behind the video: Deep Blue Sea',
		'post_date'    => '2026-02-06 18:00:00',
		'post_excerpt' => 'One cold night, a water tank and far too much fog.',
		'post_category'=> array( wp_create_category( 'Video' ) ),
		'post_content' => gl_p( 'Example post: a look behind the scenes of a music video shoot.', 'Here goes the story of the shoot: location, idea, the things that went wrong, and a few stills from the set.' ),
	), array(), $img['ice'] );
	gl_demo_post( array(
		'post_title'   => 'Thank you, [City]',
		'post_date'    => '2025-12-12 18:00:00',
		'post_excerpt' => 'A packed room, a broken string and a crowd that sang every chorus.',
		'post_category'=> array( wp_create_category( 'Live' ) ),
		'post_content' => gl_p( 'Example post: a short recap after a show.', 'A few lines about the night, a photo gallery from the pit and a thank-you to the venue, the crew and the other bands.' ),
	), array(), $img['portrait'] );

	// Customizer-Bilder
	set_theme_mod( 'gl_band_image', $img['wall'] );
	foreach ( array( 'portrait', 'thorns', 'wall2', 'dome', 'ice', 'wide' ) as $i => $k ) {
		set_theme_mod( 'gl_feed_' . ( $i + 1 ), $img[ $k ] );
	}

	update_option( 'gl_demo_imported', time() );
}

function gl_demo_remove() {
	$ids = get_posts( array(
		'post_type'      => 'any',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_key'       => '_gl_demo',
	) );
	$atts = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_gl_demo' ) );
	foreach ( array_unique( array_merge( $ids, $atts ) ) as $id ) {
		get_post_type( $id ) === 'attachment' ? wp_delete_attachment( $id, true ) : wp_delete_post( $id, true );
	}
	$prev = get_option( 'gl_demo_prev_reading' );
	if ( is_array( $prev ) ) {
		update_option( 'show_on_front', $prev[0] ?: 'posts' );
		update_option( 'page_on_front', (int) $prev[1] );
		update_option( 'page_for_posts', (int) $prev[2] );
	}
	if ( $menu = (int) get_option( 'gl_demo_menu' ) ) {
		wp_delete_nav_menu( $menu );
	}
	remove_theme_mod( 'gl_band_image' );
	for ( $i = 1; $i <= 6; $i++ ) {
		remove_theme_mod( 'gl_feed_' . $i );
	}
	delete_option( 'gl_demo_imported' );
	delete_option( 'gl_demo_prev_reading' );
	delete_option( 'gl_demo_menu' );
}
