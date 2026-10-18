<?php
/**
 * Die vier neuesten Blogposts: einer groß, drei klein.
 */

defined( 'ABSPATH' ) || exit;

$posts = get_posts( array( 'post_type' => 'post', 'posts_per_page' => 4, 'ignore_sticky_posts' => false ) );
if ( ! $posts ) {
	return;
}
$all_url = get_option( 'page_for_posts' ) ? get_permalink( (int) get_option( 'page_for_posts' ) ) : '';
$first   = array_shift( $posts );
$cat     = function ( $p ) {
	$c = get_the_category( $p->ID );
	return $c && $c[0]->slug !== 'uncategorized' && $c[0]->slug !== 'allgemein' ? $c[0]->name : 'News';
};
?>
<section class="section section--schiefer" id="news">
	<div class="section__head section__head--row">
		<div>
			<span class="meta" data-reveal>News</span>
			<h2 class="display-l" data-split>Studio<br>log</h2>
		</div>
		<?php if ( $all_url ) : ?><a href="<?php echo esc_url( $all_url ); ?>" class="meta meta--hell unterstrichen" data-reveal>All posts ↗</a><?php endif; ?>
	</div>

	<div class="blog<?php echo $posts ? '' : ' blog--einzeln'; ?>">
		<a class="post post--gross" href="<?php echo esc_url( get_permalink( $first ) ); ?>" data-reveal>
			<?php if ( has_post_thumbnail( $first ) ) : ?>
				<div class="post__img"><img src="<?php echo esc_url( get_the_post_thumbnail_url( $first, 'gl-card' ) ); ?>" alt="" loading="lazy"></div>
			<?php endif; ?>
			<div class="post__body">
				<span class="post__meta"><span class="tag-s"><?php echo esc_html( $cat( $first ) ); ?></span><span class="meta"><?php echo esc_html( get_the_date( 'd.m.Y', $first ) ); ?></span></span>
				<span class="display-m"><?php echo esc_html( get_the_title( $first ) ); ?></span>
				<span class="post__excerpt"><?php echo esc_html( get_the_excerpt( $first ) ); ?></span>
				<span class="post__more label">Read more <span class="pfeil">↗</span></span>
			</div>
		</a>

		<?php if ( $posts ) : ?>
			<div class="blog__list" data-stagger>
				<?php foreach ( $posts as $p ) : ?>
					<a class="post" href="<?php echo esc_url( get_permalink( $p ) ); ?>">
						<span class="post__meta"><span class="tag-s"><?php echo esc_html( $cat( $p ) ); ?></span><span class="meta"><?php echo esc_html( get_the_date( 'd.m.Y', $p ) ); ?></span></span>
						<span class="label"><?php echo esc_html( get_the_title( $p ) ); ?></span>
						<span class="post__excerpt"><?php echo esc_html( get_the_excerpt( $p ) ); ?></span>
						<span class="post__more meta meta--hell">Read ↗</span>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
