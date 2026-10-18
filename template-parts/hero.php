<?php
/**
 * Hero-Slider. Slides kommen aus „Hero-Slides“. Ohne Slides: eine Band-Slide mit Logo.
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
	$slides[] = array(
		'image'    => get_the_post_thumbnail_url( $p, 'gl-hero' ) ?: '',
		'alt'      => get_post_meta( get_post_thumbnail_id( $p ), '_wp_attachment_image_alt', true ),
		'video'    => gl_meta( $p->ID, 'video' ),
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
		'image' => GL_URI . '/assets/img/demo/band-industrial.jpg', 'alt' => get_bloginfo( 'name' ), 'video' => '',
		'kicker' => gl_opt( 'tagline' ), 'headline' => get_bloginfo( 'name' ), 'logo' => true, 'subline' => '',
		'btn' => 'Meet the band', 'url' => '#band', 'label' => 'Band',
	);
}
$link = function ( $url ) {
	return str_starts_with( (string) $url, '#' ) ? gl_anchor( substr( $url, 1 ) ) : $url;
};
$total = count( $slides );
?>
<section class="hero" id="top" aria-roledescription="carousel" aria-label="Highlights" data-count="<?php echo (int) $total; ?>">
	<div class="slides">
		<?php foreach ( $slides as $i => $s ) : ?>
			<article class="slide<?php echo $i === 0 ? ' is-active' : ''; ?>"<?php echo $s['video'] ? ' data-video' : ''; ?> aria-roledescription="slide" aria-label="<?php echo esc_attr( ( $i + 1 ) . ' of ' . $total ); ?>">
				<div class="slide__media">
					<?php if ( $s['video'] ) : ?>
						<video muted loop playsinline preload="<?php echo $i === 0 ? 'auto' : 'metadata'; ?>"<?php echo $s['image'] ? ' poster="' . esc_url( $s['image'] ) . '"' : ''; ?>><source src="<?php echo esc_url( $s['video'] ); ?>" type="video/mp4"></video>
					<?php elseif ( $s['image'] ) : ?>
						<img src="<?php echo esc_url( $s['image'] ); ?>" alt="<?php echo esc_attr( $s['alt'] ); ?>"<?php echo $i === 0 ? ' fetchpriority="high"' : ' loading="lazy"'; ?>>
					<?php endif; ?>
				</div>
				<div class="slide__content">
					<?php if ( $s['kicker'] ) : ?><span class="meta meta--hell"><?php echo esc_html( $s['kicker'] ); ?></span><?php endif; ?>
					<?php
					$tag = $i === 0 ? 'h1' : 'h2';
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
