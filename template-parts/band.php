<?php
defined( 'ABSPATH' ) || exit;

$img_id  = (int) gl_opt( 'band_image', 0 );
$img     = $img_id ? wp_get_attachment_image_url( $img_id, 'gl-hero' ) : GL_URI . '/assets/img/demo/band-wall.jpg';
$alt     = $img_id ? get_post_meta( $img_id, '_wp_attachment_image_alt', true ) : get_bloginfo( 'name' );
$cta_url = gl_opt( 'bio_cta_url' );
$cta_url = str_starts_with( (string) $cta_url, '#' ) ? gl_anchor( substr( $cta_url, 1 ) ) : $cta_url;
?>
<section class="section band" id="band">
	<div class="band__body">
		<span class="meta" data-reveal><?php echo esc_html( gl_opt( 'bio_kicker' ) ); ?></span>
		<h2 class="display-l" data-split><?php echo gl_lines( gl_opt( 'bio_heading' ) ); ?></h2>
		<?php if ( gl_opt( 'bio_lead' ) ) : ?><p class="band__bio" data-reveal><?php echo esc_html( gl_opt( 'bio_lead' ) ); ?></p><?php endif; ?>
		<?php if ( gl_opt( 'bio_text' ) ) : ?><p class="band__bio band__bio--klein" data-reveal><?php echo esc_html( gl_opt( 'bio_text' ) ); ?></p><?php endif; ?>
		<?php $members = gl_pipe_rows( gl_opt( 'members' ) ); if ( $members ) : ?>
			<ul class="members" data-stagger>
				<?php foreach ( $members as $m ) : ?>
					<li><span class="label"><?php echo esc_html( $m[0] ); ?></span><span class="meta"><?php echo esc_html( $m[1] ?? '' ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( gl_opt( 'bio_cta_text' ) && $cta_url ) : ?>
			<a href="<?php echo esc_url( $cta_url ); ?>" class="btn" data-reveal<?php echo gl_target( $cta_url ); ?>><?php echo esc_html( gl_opt( 'bio_cta_text' ) ); ?> <span class="pfeil">↗</span></a>
		<?php endif; ?>
	</div>
	<div class="band__img" data-reveal="clip">
		<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="lazy" data-parallax="0.1">
		<?php if ( gl_opt( 'bio_caption' ) ) : ?><span class="meta meta--hell band__caption"><?php echo esc_html( gl_opt( 'bio_caption' ) ); ?></span><?php endif; ?>
	</div>
</section>
