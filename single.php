<?php
/**
 * Einzelner Blogpost.
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$c       = get_the_category();
	$back    = get_option( 'page_for_posts' ) ? get_permalink( (int) get_option( 'page_for_posts' ) ) : home_url( '/#news' );
	?>
	<article class="artikel">
		<header class="artikel__head">
			<a href="<?php echo esc_url( $back ); ?>" class="meta meta--hell">← All news</a>
			<span class="post__meta">
				<span class="tag-s"><?php echo esc_html( $c && ! in_array( $c[0]->slug, array( 'uncategorized', 'allgemein' ), true ) ? $c[0]->name : 'News' ); ?></span>
				<span class="meta"><?php echo esc_html( get_the_date( 'd.m.Y' ) ); ?> · <?php the_author(); ?></span>
			</span>
			<h1 class="display-l" data-split><?php the_title(); ?></h1>
		</header>
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="artikel__bild" data-reveal="clip"><?php the_post_thumbnail( 'gl-hero' ); ?></figure>
		<?php endif; ?>
		<div class="prose">
			<?php the_content(); ?>
		</div>
		<nav class="artikel__nav">
			<span><?php previous_post_link( '%link', '← %title' ); ?></span>
			<span><?php next_post_link( '%link', '%title →' ); ?></span>
		</nav>
	</article>
	<?php
endwhile;

get_footer();
