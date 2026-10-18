<?php
/**
 * Social-Bereich. Bilder vorerst aus dem Customizer (Social-Bilder),
 * später automatisch aus Instagram.
 */

defined( 'ABSPATH' ) || exit;

$url    = gl_opt( 'instagram' );
$handle = gl_opt( 'instagram_handle' );
$images = array();
for ( $i = 1; $i <= 6; $i++ ) {
	$id = (int) gl_opt( 'feed_' . $i, 0 );
	if ( $id && ( $src = wp_get_attachment_image_url( $id, 'medium_large' ) ) ) {
		$images[] = $src;
	}
}
if ( ! $url && ! $images ) {
	return;
}
// Handle für zwei Zeilen: „@ghostlike / band“
$h = $handle ? '@' . $handle : 'Social';
if ( $handle && preg_match( '/^(.+?)(band)$/i', $handle, $m ) ) {
	$h = '@' . $m[1] . '<br>' . $m[2];
}
?>
<section class="section section--schiefer" id="feed">
	<div class="section__head section__head--row">
		<div>
			<span class="meta" data-reveal>Social</span>
			<h2 class="display-l" data-split><?php echo wp_kses( $h, array( 'br' => array() ) ); ?></h2>
		</div>
		<?php if ( $url ) : ?><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" class="btn" data-reveal>Follow on Instagram <span class="pfeil">↗</span></a><?php endif; ?>
	</div>
	<?php if ( $images ) : ?>
		<div class="feed" data-stagger>
			<?php foreach ( $images as $src ) : ?>
				<a href="<?php echo esc_url( $url ?: '#' ); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url( $src ); ?>" alt="" loading="lazy"><span class="meta meta--hell">Instagram ↗</span></a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
