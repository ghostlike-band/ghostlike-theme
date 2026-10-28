<?php
/**
 * Releases: das hervorgehobene Release groß, weitere als Liste darunter.
 */

defined( 'ABSPATH' ) || exit;

$releases = get_posts( array(
	'post_type'      => 'gl_release',
	'posts_per_page' => 12,
	'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
) );
if ( ! $releases ) {
	return;
}
usort( $releases, fn( $a, $b ) => (int) ( gl_meta( $b->ID, 'featured' ) === '1' ) - (int) ( gl_meta( $a->ID, 'featured' ) === '1' ) );
$main  = array_shift( $releases );
$id    = $main->ID;
$cover = get_the_post_thumbnail_url( $main, 'gl-square' );
$links = array();
foreach ( gl_platforms() as $key => $pl ) {
	if ( $url = gl_meta( $id, $key ) ) {
		$links[] = array( 'url' => $url, 'label' => $pl[0], 'icon' => $pl[1] ? gl_icon( $pl[1] ) : '' );
	}
}
?>
<section class="section" id="music">
	<div class="section__head">
		<span class="meta" data-reveal>Releases</span>
		<h2 class="display-l" data-split>Latest <br>release</h2>
	</div>

	<article class="release">
		<div class="release__cover" data-reveal="clip">
			<?php if ( $cover ) : ?><img src="<?php echo esc_url( $cover ); ?>" alt="<?php echo esc_attr( 'Cover: ' . get_the_title( $main ) ); ?>"><?php endif; ?>
		</div>
		<div class="release__info">
			<?php if ( gl_meta( $id, 'kicker' ) ) : ?><span class="meta" data-reveal><?php echo esc_html( gl_meta( $id, 'kicker' ) ); ?></span><?php endif; ?>
			<h3 class="display-l release__title" data-split><?php echo gl_lines( gl_meta( $id, 'headline', get_the_title( $main ) ) ); ?></h3>

			<?php $tracks = gl_pipe_rows( gl_meta( $id, 'tracks' ) ); if ( $tracks ) : ?>
				<ol class="tracks" data-stagger>
					<?php foreach ( $tracks as $n => $t ) : $video = $t[2] ?? ''; ?>
						<li class="track">
							<span class="meta"><?php echo esc_html( str_pad( (string) ( $n + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
							<span class="label"><?php echo esc_html( $t[0] ); ?></span>
							<?php if ( $video ) : ?>
								<a class="track__video" href="<?php echo esc_url( $video ); ?>" target="_blank" rel="noopener">Video <span class="pfeil">↗</span></a>
							<?php else : ?>
								<span></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>

			<?php if ( $links ) : ?>
				<div class="stream" data-reveal>
					<span class="meta">Listen on</span>
					<div class="stream__list">
						<?php foreach ( $links as $l ) : ?>
							<a href="<?php echo esc_url( $l['url'] ); ?>" target="_blank" rel="noopener" class="stream__btn">
								<?php echo $l['icon'] ? $l['icon'] : '<span class="stream__pfeil" aria-hidden="true">↗</span>'; // phpcs:ignore -- lokale SVG-Datei ?>
								<span><?php echo esc_html( $l['label'] ); ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</article>

	<?php if ( $releases ) : ?>
		<div class="disco">
			<span class="meta" data-reveal>Discography</span>
			<ul class="disco__list" data-stagger>
				<?php foreach ( $releases as $r ) :
					$url = gl_meta( $r->ID, 'smartlink' ) ?: gl_meta( $r->ID, 'spotify' ) ?: gl_meta( $r->ID, 'bandcamp' ) ?: gl_meta( $r->ID, 'youtube' );
					?>
					<li>
						<?php if ( has_post_thumbnail( $r ) ) : ?><img src="<?php echo esc_url( get_the_post_thumbnail_url( $r, 'thumbnail' ) ); ?>" alt="<?php echo esc_attr( 'Cover: ' . get_the_title( $r ) . ' – GHOSTLIKE' ); ?>" loading="lazy"><?php endif; ?>
						<span class="label"><?php echo esc_html( get_the_title( $r ) ); ?></span>
						<span class="meta"><?php echo esc_html( gl_meta( $r->ID, 'kicker' ) ); ?></span>
						<?php if ( $url ) : ?><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" class="track__video">Listen <span class="pfeil">↗</span></a><?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
</section>
