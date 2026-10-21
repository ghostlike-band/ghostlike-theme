<?php
/**
 * Blog-Übersicht (News), Archive und Fallback.
 * Neuester Beitrag groß, darunter eine ruhige Liste mit Datum, Titel und Bild (alle Bilder gleich, 3:2).
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( is_home() ) {
	$title = get_option( 'page_for_posts' ) ? get_the_title( (int) get_option( 'page_for_posts' ) ) : 'News';
} elseif ( is_category() ) {
	$title = single_cat_title( '', false );
} elseif ( is_archive() ) {
	$title = wp_strip_all_tags( get_the_archive_title() );
} elseif ( is_search() ) {
	$title = 'Search';
} else {
	$title = 'News';
}
$all_url = get_option( 'page_for_posts' ) ? get_permalink( (int) get_option( 'page_for_posts' ) ) : home_url( '/' );
$cats    = get_categories( array( 'hide_empty' => true, 'exclude' => array( (int) get_option( 'default_category' ) ) ) );
$current = is_category() ? get_queried_object_id() : 0;
$cat_of  = function () {
	$c = get_the_category();
	return $c && ! in_array( $c[0]->slug, array( 'uncategorized', 'allgemein' ), true ) ? $c[0]->name : 'News';
};
?>
<section class="section seite news-page">
	<div class="section__head section__head--row">
		<div>
			<span class="meta" data-reveal>Studio log</span>
			<h1 class="display-l" data-split><?php echo esc_html( $title ); ?></h1>
		</div>
		<?php if ( count( $cats ) > 1 ) : ?>
			<nav class="news-filter" aria-label="Categories" data-reveal>
				<a href="<?php echo esc_url( $all_url ); ?>" class="news-filter__chip<?php echo $current ? '' : ' is-on'; ?>">All</a>
				<?php foreach ( $cats as $c ) : ?>
					<a href="<?php echo esc_url( get_category_link( $c ) ); ?>" class="news-filter__chip<?php echo $current === $c->term_id ? ' is-on' : ''; ?>"><?php echo esc_html( $c->name ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	</div>

	<?php if ( have_posts() ) : ?>
		<?php
		$i = 0;
		while ( have_posts() ) :
			the_post();
			$i++;
			$thumb = get_post_thumbnail_id();
			$lead  = $i === 1 && ! is_paged();
			if ( $lead ) :
				?>
				<a class="lead-post" href="<?php the_permalink(); ?>" data-reveal>
					<span class="lead-post__img">
						<?php echo $thumb ? wp_get_attachment_image( $thumb, 'gl-card', false, array( 'alt' => '', 'loading' => 'eager', 'sizes' => '(max-width: 900px) 100vw, 60vw' ) ) : ''; ?>
					</span>
					<span class="lead-post__body">
						<span class="meta"><?php echo esc_html( get_the_date( 'd.m.Y' ) ); ?> · <?php echo esc_html( $cat_of() ); ?></span>
						<span class="lead-post__title"><?php the_title(); ?></span>
						<span class="lead-post__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></span>
						<span class="lead-post__more meta">Read <span class="pfeil">↗</span></span>
					</span>
				</a>
				<ol class="news-list" data-stagger>
			<?php else : ?>
				<?php if ( $i === 1 ) : ?><ol class="news-list" data-stagger><?php endif; ?>
				<li>
					<a class="news-row" href="<?php the_permalink(); ?>">
						<span class="news-row__date meta"><?php echo esc_html( get_the_date( 'd.m.Y' ) ); ?></span>
						<span class="news-row__main">
							<span class="news-row__cat meta"><?php echo esc_html( $cat_of() ); ?></span>
							<span class="news-row__title"><?php the_title(); ?></span>
						</span>
						<span class="news-row__img<?php echo $thumb ? '' : ' is-leer'; ?>"><?php
							if ( $thumb ) {
								echo wp_get_attachment_image( $thumb, 'gl-card', false, array( 'alt' => '', 'loading' => 'lazy', 'sizes' => '(max-width: 700px) 120px, 280px' ) );
							} else {
								gl_mark(); // ohne Beitragsbild: dezentes Logo statt leerer Fläche
							}
						?></span>
						<span class="news-row__pfeil" aria-hidden="true">↗</span>
					</a>
				</li>
			<?php endif; ?>
		<?php endwhile; ?>
		</ol>
		<nav class="pager meta meta--hell" aria-label="Pages">
			<?php echo wp_kses_post( paginate_links( array( 'prev_text' => '← Newer', 'next_text' => 'Older →' ) ) ); ?>
		</nav>
	<?php else : ?>
		<p>Nothing here yet.</p>
	<?php endif; ?>
</section>
<?php
get_footer();
