<?php
/**
 * Normale Seiten (Impressum, Datenschutz, …).
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article class="artikel artikel--seite">
		<header class="artikel__head">
			<h1 class="display-l" data-split><?php the_title(); ?></h1>
		</header>
		<div class="prose">
			<?php the_content(); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
