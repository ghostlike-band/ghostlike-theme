<?php
defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="section seite seite--404">
	<span class="meta">Error 404</span>
	<h1 class="display-xl">Gone<br>ghost</h1>
	<p>This page doesn’t exist (anymore).</p>
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--hell">Back to the start <span class="pfeil">↗</span></a>
</section>
<?php
get_footer();
