<?php
/**
 * Startseite: der Onepager.
 */

defined( 'ABSPATH' ) || exit;

get_header();

get_template_part( 'template-parts/hero' );
// Reihenfolge und Sichtbarkeit: Customizer → GHOSTLIKE → Sektionen & Menü
foreach ( gl_sections() as $section ) {
	if ( $section['on'] ) {
		get_template_part( 'template-parts/' . $section['id'] );
	}
}

get_footer();
