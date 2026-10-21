<?php
/**
 * Startseite: der Onepager.
 */

defined( 'ABSPATH' ) || exit;

get_header();

// Haupt-Überschrift für Suchmaschinen und Screenreader (die Slides zeigen wechselnde Headlines)
echo '<h1 class="visually-hidden">' . esc_html( gl_seo( 'seo_title' ) ) . '</h1>';

get_template_part( 'template-parts/hero' );
// Reihenfolge und Sichtbarkeit: Customizer → GHOSTLIKE → Sektionen & Menü
foreach ( gl_sections() as $section ) {
	if ( $section['on'] ) {
		get_template_part( 'template-parts/' . $section['id'] );
	}
}

get_footer();
