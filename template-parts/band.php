<?php
/**
 * Band-Bio. Das Bandfoto wird nie zugeschnitten (alle Mitglieder bleiben sichtbar).
 * Fürs Handy kann ein eigenes Foto hinterlegt werden (z. B. Hochformat).
 */

defined( 'ABSPATH' ) || exit;

$img_id  = (int) gl_opt( 'band_image', 0 );
$mob_id  = (int) gl_opt( 'band_image_mobile', 0 );
$img     = $img_id ? wp_get_attachment_image_url( $img_id, 'gl-hero' ) : GL_URI . '/assets/img/demo/band-wall.jpg';
$srcset  = $img_id ? wp_get_attachment_image_srcset( $img_id, 'gl-hero' ) : '';
$mobile  = $mob_id ? wp_get_attachment_image_url( $mob_id, 'large' ) : '';
$alt     = $img_id ? get_post_meta( $img_id, '_wp_attachment_image_alt', true ) : '';
$alt     = $alt ?: get_bloginfo( 'name' );
$meta    = $img_id ? wp_get_attachment_metadata( $img_id ) : array( 'width' => 1800, 'height' => 1200 );
$cta_url = gl_opt( 'bio_cta_url' );
$cta_url = str_starts_with( (string) $cta_url, '#' ) ? gl_anchor( substr( $cta_url, 1 ) ) : $cta_url;
?>
<section class="section band" id="band">
	<div class="band__grid">
		<div class="band__body">
			<span class="meta" data-reveal><?php echo esc_html( gl_opt( 'bio_kicker' ) ); ?></span>
			<h2 class="display-l" data-split><?php echo gl_lines( gl_opt( 'bio_heading' ) ); ?></h2>
			<?php if ( gl_opt( 'bio_lead' ) ) : ?><p class="band__bio" data-reveal><?php echo esc_html( gl_opt( 'bio_lead' ) ); ?></p><?php endif; ?>
			<?php if ( gl_opt( 'bio_text' ) ) : ?><p class="band__bio band__bio--klein" data-reveal><?php echo esc_html( gl_opt( 'bio_text' ) ); ?></p><?php endif; ?>
			<?php echo gl_fans_of_line(); // phpcs:ignore -- escaped ?>
			<?php if ( gl_opt( 'bio_cta_text' ) && $cta_url ) : ?>
				<a href="<?php echo esc_url( $cta_url ); ?>" class="btn" data-reveal<?php echo gl_target( $cta_url ); ?>><?php echo esc_html( gl_opt( 'bio_cta_text' ) ); ?> <span class="pfeil">↗</span></a>
			<?php endif; ?>
		</div>
	</div>
	<figure class="band__img" data-reveal="clip">
		<picture>
			<?php if ( $mobile ) : ?><source media="(max-width: 767px)" srcset="<?php echo esc_url( $mobile ); ?>"><?php endif; ?>
			<img src="<?php echo esc_url( $img ); ?>"<?php echo $srcset ? ' srcset="' . esc_attr( $srcset ) . '" sizes="(max-width: 1440px) 100vw, 1440px"' : ''; ?> width="<?php echo (int) ( $meta['width'] ?? 1800 ); ?>" height="<?php echo (int) ( $meta['height'] ?? 1200 ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="lazy">
		</picture>
		<?php if ( gl_opt( 'bio_caption' ) ) : ?><figcaption class="meta meta--hell band__caption"><?php echo esc_html( gl_opt( 'bio_caption' ) ); ?></figcaption><?php endif; ?>
	</figure>
</section>
