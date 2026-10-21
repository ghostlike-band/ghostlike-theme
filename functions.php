<?php
/**
 * GHOSTLIKE Theme – Einstieg.
 */

defined( 'ABSPATH' ) || exit;

define( 'GL_VERSION', wp_get_theme( get_template() )->get( 'Version' ) );
define( 'GL_DIR', get_template_directory() );
define( 'GL_URI', get_template_directory_uri() );

// GitHub-Repo für Updates („organisation/repo“). Kann in wp-config.php überschrieben werden.
if ( ! defined( 'GL_GITHUB_REPO' ) ) {
	define( 'GL_GITHUB_REPO', 'ghostlike-band/ghostlike-theme' );
}

require GL_DIR . '/inc/helpers.php';
require GL_DIR . '/inc/post-types.php';
require GL_DIR . '/inc/meta-boxes.php';
require GL_DIR . '/inc/customizer.php';
require GL_DIR . '/inc/contact.php';
require GL_DIR . '/inc/updater.php';
require GL_DIR . '/inc/admin-page.php';
require GL_DIR . '/inc/demo-content.php';
require GL_DIR . '/inc/sections.php';
require GL_DIR . '/inc/upgrade.php';
require GL_DIR . '/inc/tools-updates.php';
require GL_DIR . '/inc/tools-bunny.php';
require GL_DIR . '/inc/emails.php';
require GL_DIR . '/inc/emails-admin.php';
require GL_DIR . '/inc/shortcodes.php';
require GL_DIR . '/inc/bildpool.php';
require GL_DIR . '/inc/seo.php';
require GL_DIR . '/inc/migrate.php';

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );

	register_nav_menus( array(
		'main'   => 'Hauptmenü',
		'footer' => 'Footer (Rechtliches)',
	) );

	add_image_size( 'gl-hero', 2200, 1400 );
	add_image_size( 'gl-card', 1200, 800, true );
	add_image_size( 'gl-square', 900, 900, true );
} );

add_action( 'wp_enqueue_scripts', function () {
	// Version + Änderungszeit, damit Browser nach Updates nie alte Dateien aus dem Cache nehmen.
	$v = fn( $f ) => GL_VERSION . '.' . (int) @filemtime( GL_DIR . $f );
	wp_enqueue_style( 'gl-fonts', GL_URI . '/assets/css/fonts.css', array(), $v( '/assets/css/fonts.css' ) );
	wp_enqueue_style( 'gl-base', GL_URI . '/assets/css/base.css', array( 'gl-fonts' ), $v( '/assets/css/base.css' ) );
	wp_enqueue_style( 'gl-site', GL_URI . '/assets/css/site.css', array( 'gl-base' ), $v( '/assets/css/site.css' ) );

	wp_enqueue_script( 'gl-site', GL_URI . '/assets/js/site.js', array(), $v( '/assets/js/site.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_localize_script( 'gl-site', 'GL', array(
		'contactUrl' => esc_url_raw( rest_url( 'ghostlike/v1/contact' ) ),
		'interval'   => (int) gl_opt( 'slider_interval', 7 ),
	) );
} );

// Favicon aus dem Theme, solange kein Website-Icon gesetzt ist.
add_action( 'wp_head', function () {
	if ( ! has_site_icon() ) {
		echo '<link rel="icon" href="' . esc_url( GL_URI . '/assets/img/favicon.svg' ) . '" type="image/svg+xml">' . "\n";
	}
	echo '<meta name="theme-color" content="#0A0B0A">' . "\n";
} );

// Ballast im Frontend entfernen.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );

add_filter( 'excerpt_length', fn() => 28 );
add_filter( 'excerpt_more', fn() => '…' );
