<?php
/**
 * Blog-Übersicht (News), Archive und Fallback.
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( is_home() ) {
	$title = get_option( 'page_for_posts' ) ? get_the_title( (int) get_option( 'page_for_posts' ) ) : 'News';
} elseif ( is_archive() ) {
	$title = wp_strip_all_tags( get_the_archive_title() );
} elseif ( is_search() ) {
	$title = 'Search';
} else {
	$title = 'News';
}
?>
<section class="section seite">
	<div class="section__head">
		<span class="meta" data-reveal>Studio log</span>
		<h1 class="display-l" data-split><?php echo esc_html( $title ); ?></h1>
	</div>

	<?php if ( have_posts() ) : ?>
		<div class="post-grid" data-stagger>
			<?php while ( have_posts() ) : the_post(); $c = get_the_category(); ?>
				<a class="post post--karte" href="<?php the_permalink(); ?>">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="post__img"><img src="<?php echo esc_url( get_the_post_thumbnail_url( null, 'gl-card' ) ); ?>" alt="" loading="lazy"></div>
					<?php endif; ?>
					<div class="post__body">
						<span class="post__meta"><span class="tag-s"><?php echo esc_html( $c && ! in_array( $c[0]->slug, array( 'uncategorized', 'allgemein' ), true ) ? $c[0]->name : 'News' ); ?></span><span class="meta"><?php echo esc_html( get_the_date( 'd.m.Y' ) ); ?></span></span>
						<span class="label"><?php the_title(); ?></span>
						<span class="post__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></span>
						<span class="post__more meta meta--hell">Read ↗</span>
					</div>
				</a>
			<?php endwhile; ?>
		</div>
		<nav class="pager meta meta--hell" aria-label="Pages">
			<?php echo wp_kses_post( paginate_links( array( 'prev_text' => '← Newer', 'next_text' => 'Older →' ) ) ); ?>
		</nav>
	<?php else : ?>
		<p>Nothing here yet.</p>
	<?php endif; ?>
</section>
<?php
get_footer();
