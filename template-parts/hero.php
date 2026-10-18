<?php
/**
 * Hero-Slider. Slides kommen aus „Hero-Slides“. Ohne Slides: eine Band-Slide mit Logo.
 * Pro Slide optional eigenes Bild/Video fürs Handy (Hochformat) und ein Bildausschnitt.
 * Videos laden erst, wenn ihre Slide dran ist (schneller Seitenstart).
 */

defined( 'ABSPATH' ) || exit;

$q = new WP_Query( array(
	'post_type'      => 'gl_slide',
	'posts_per_page' => 6,
	'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
	'no_found_rows'  => true,
) );

$slides = array();
foreach ( $q->posts as $p ) {
	$thumb    = get_post_thumbnail_id( $p );
	$slides[] = array(
		'thumb'    => $thumb,
		'image'    => $thumb ? wp_get_attachment_image_url( $thumb, 'gl-hero' ) : '',
		'srcset'   => $thumb ? wp_get_attachment_image_srcset( $thumb, 'gl-hero' ) : '',
		'alt'      => $thumb ? get_post_meta( $thumb, '_wp_attachment_image_alt', true ) : '',
		'mobile'   => gl_meta( $p->ID, 'image_mobile' ),
		'focus'    => gl_meta( $p->ID, 'focus', 'center' ),
		'video'    => gl_meta( $p->ID, 'video' ),
		'video_m'  => gl_meta( $p->ID, 'video_mobile' ),
		'kicker'   => gl_meta( $p->ID, 'kicker' ),
		'headline' => gl_meta( $p->ID, 'headline', $p->post_title ),
		'logo'     => gl_meta( $p->ID, 'show_logo' ) === '1',
		'subline'  => gl_meta( $p->ID, 'subline' ),
		'btn'      => gl_meta( $p->ID, 'button_text' ),
		'url'      => gl_meta( $p->ID, 'button_url' ),
		'label'    => trim( preg_split( '/\s[–-]\s/u', $p->post_title )[0] ),
	);
}
if ( ! $slides ) {
	$slides[] = array(
		'thumb' => 0, 'image' => GL_URI . '/assets/img/demo/band-industrial.jpg', 'srcset' => '', 'alt' => get_bloginfo( 'name' ),
		'mobile' => GL_URI . '/assets/img/demo/band-portrait.jpg', 'focus' => 'center', 'video' => '', 'video_m' => '',
		'kicker' => gl_opt( 'tagline' ), 'headline' => get_bloginfo( 'name' ), 'logo' => true, 'subline' => '',
		'btn' => 'Meet the band', 'url' => '#band', 'label' => 'Band',
	);
}
$link = function ( $url ) {
	return str_starts_with( (string) $url, '#' ) ? gl_anchor( substr( $url, 1 ) ) : $url;
};
$total = count( $slides );
?>
<section class="hero" id="top" aria-roledescription="carousel" aria-label="Highlights">
	<div class="slides">
		<?php foreach ( $slides as $i => $s ) :
			$first = $i === 0;
			$style = '--fokus:' . $s['focus'];
			?>
			<article class="slide<?php echo $first ? ' is-active' : ''; ?>"<?php echo $s['video'] ? ' data-video' : ''; ?> aria-roledescription="slide" aria-label="<?php echo esc_attr( ( $i + 1 ) . ' of ' . $total ); ?>">
				<div class="slide__media" style="<?php echo esc_attr( $style ); ?>">
					<?php if ( $s['video'] ) : ?>
						<video muted loop playsinline preload="none"
							data-src="<?php echo esc_url( $s['video'] ); ?>"
							<?php if ( $s['video_m'] ) : ?>data-src-mobile="<?php echo esc_url( $s['video_m'] ); ?>"<?php endif; ?>
							<?php if ( $s['image'] ) : ?>poster="<?php echo esc_url( $s['image'] ); ?>"<?php endif; ?>
							<?php if ( $s['mobile'] ) : ?>data-poster-mobile="<?php echo esc_url( $s['mobile'] ); ?>"<?php endif; ?>></video>
					<?php elseif ( $s['image'] ) : ?>
						<picture>
							<?php if ( $s['mobile'] ) : ?><source media="(max-width: 767px)" srcset="<?php echo esc_url( $s['mobile'] ); ?>"><?php endif; ?>
							<img src="<?php echo esc_url( $s['image'] ); ?>"<?php echo $s['srcset'] ? ' srcset="' . esc_attr( $s['srcset'] ) . '" sizes="100vw"' : ''; ?> alt="<?php echo esc_attr( $s['alt'] ); ?>"<?php echo $first ? ' fetchpriority="high"' : ' loading="lazy"'; ?>>
						</picture>
					<?php endif; ?>
				</div>
				<div class="slide__content">
					<?php if ( $s['kicker'] ) : ?><span class="meta meta--hell"><?php echo esc_html( $s['kicker'] ); ?></span><?php endif; ?>
					<?php
					$tag = $first ? 'h1' : 'h2';
					if ( $s['logo'] ) {
						echo '<' . $tag . ' class="slide__logo logo"><span class="visually-hidden">' . esc_html( get_bloginfo( 'name' ) ) . '</span>';
						gl_logo();
						echo '</' . $tag . '>';
					} else {
						echo '<' . $tag . ' class="display-xl">' . gl_lines( $s['headline'] ) . '</' . $tag . '>';
					}
					?>
					<?php if ( $s['subline'] ) : ?><p class="slide__sub"><?php echo esc_html( $s['subline'] ); ?></p><?php endif; ?>
					<?php if ( $s['btn'] && $s['url'] ) : ?>
						<a href="<?php echo esc_url( $link( $s['url'] ) ); ?>" class="btn btn--hell"<?php echo gl_target( $s['url'] ); ?>><?php echo esc_html( $s['btn'] ); ?> <span class="pfeil">↗</span></a>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>

	<?php if ( $total > 1 ) : ?>
		<div class="hero__ui">
			<ol class="dots" role="tablist" aria-label="Choose slide" style="--n:<?php echo (int) $total; ?>">
				<?php foreach ( $slides as $i => $s ) : ?>
					<li><button role="tab" aria-selected="<?php echo $i === 0 ? 'true' : 'false'; ?>"><span class="meta"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><span class="dots__label"><?php echo esc_html( wp_trim_words( $s['label'], 2, '' ) ); ?></span><i></i></button></li>
				<?php endforeach; ?>
			</ol>
			<div class="arrows">
				<button class="arrow" data-dir="-1" aria-label="Previous slide">←</button>
				<button class="arrow" data-pause aria-label="Pause slideshow">❚❚</button>
				<button class="arrow" data-dir="1" aria-label="Next slide">→</button>
			</div>
		</div>
	<?php endif; ?>
</section>
