<?php
/**
 * Blog-Übersicht (News), Archive und Fallback.
 * Kacheln in wechselnden Größen (Bento-Raster); Muster wiederholt sich alle 7 Beiträge.
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
$pattern = array( 'xl', 'tall', 's', 's', 'wide', 's', 'wide' );
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
		<div class="bento" data-stagger>
			<?php
			$i = 0;
			while ( have_posts() ) :
				the_post();
				$size  = $pattern[ $i % count( $pattern ) ];
				$thumb = get_post_thumbnail_id();
				$i++;
				?>
				<a class="tile tile--<?php echo esc_attr( $size ); ?><?php echo $thumb ? '' : ' tile--text'; ?>" href="<?php the_permalink(); ?>">
					<?php
					if ( $thumb ) {
						echo wp_get_attachment_image( $thumb, $size === 'xl' ? 'gl-hero' : 'gl-card', false, array(
							'class'   => 'tile__img',
							'alt'     => '',
							'loading' => $i <= 2 ? 'eager' : 'lazy',
							'sizes'   => $size === 'xl' || $size === 'wide' ? '(max-width: 960px) 100vw, 50vw' : '(max-width: 600px) 100vw, (max-width: 960px) 50vw, 25vw',
						) );
					}
					?>
					<span class="tile__pfeil" aria-hidden="true">↗</span>
					<span class="tile__body">
						<span class="post__meta"><span class="tag-s"><?php echo esc_html( $cat_of() ); ?></span><span class="meta"><?php echo esc_html( get_the_date( 'd.m.Y' ) ); ?></span></span>
						<span class="tile__title"><?php the_title(); ?></span>
						<?php if ( in_array( $size, array( 'xl', 'wide' ), true ) || ! $thumb ) : ?>
							<span class="tile__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></span>
						<?php endif; ?>
					</span>
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
