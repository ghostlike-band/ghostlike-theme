<?php
/**
 * Startseite: der Onepager.
 */

defined( 'ABSPATH' ) || exit;

get_header();

get_template_part( 'template-parts/hero' );
get_template_part( 'template-parts/band' );
get_template_part( 'template-parts/marquee' );
get_template_part( 'template-parts/music' );
get_template_part( 'template-parts/news' );
get_template_part( 'template-parts/tour' );
get_template_part( 'template-parts/feed' );
get_template_part( 'template-parts/contact' );

get_footer();
