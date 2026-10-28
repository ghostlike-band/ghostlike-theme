<?php
/**
 * Social-Bereich. Mit Smash Balloon (Instagram Feed) wird der echte Feed gezeigt,
 * im Website-Raster und CI-Look. Sonst die Bilder aus Customizer → Social-Bilder.
 */

defined( 'ABSPATH' ) || exit;

$url    = gl_opt( 'instagram' );
$handle = gl_opt( 'instagram_handle' );
$source = gl_opt( 'feed_source', 'auto' );
$smash  = shortcode_exists( 'instagram-feed' ) && $source !== 'images';

$feed_html = '';
if ( $smash ) {
	$id        = (int) gl_opt( 'feed_id', 1 );
	$feed_html = do_shortcode( '[instagram-feed' . ( $id ? ' feed="' . $id . '"' : '' ) . ' num="6" cols="6" showheader="false" showbutton="false" showfollow="false"]' );
}

$images = array();
if ( ! $feed_html ) {
	for ( $i = 1; $i <= 6; $i++ ) {
		$img = (int) gl_opt( 'feed_' . $i, 0 );
		if ( $img && ( $src = wp_get_attachment_image_url( $img, 'medium_large' ) ) ) {
			$images[] = $src;
		}
	}
}
if ( ! $url && ! $feed_html && ! $images ) {
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
	<?php if ( $feed_html ) : ?>
		<div class="feed feed--insta" data-reveal><?php echo $feed_html; // phpcs:ignore -- Ausgabe von Smash Balloon ?></div>
	<?php elseif ( $images ) : ?>
		<div class="feed" data-stagger>
			<?php foreach ( $images as $src ) : ?>
				<a href="<?php echo esc_url( $url ?: '#' ); ?>" target="_blank" rel="noopener"><img src="<?php echo esc_url( $src ); ?>" alt="GHOSTLIKE on Instagram" loading="lazy"><span class="meta meta--hell">Instagram ↗</span></a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
