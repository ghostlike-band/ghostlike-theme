<?php
defined( 'ABSPATH' ) || exit;

$parts = array_filter( array_map( 'trim', explode( '|', (string) gl_opt( 'marquee' ) ) ), 'strlen' );
if ( ! $parts ) {
	return;
}
$seps = array( '—', '↗' );
$run  = '';
foreach ( array_values( $parts ) as $i => $p ) {
	$run .= '<span>' . esc_html( $p ) . '</span><b>' . $seps[ $i % 2 ] . '</b>';
}
?>
<div class="marquee" aria-hidden="true">
	<div class="marquee__track"><?php echo $run . $run . $run; // phpcs:ignore -- bereits escaped ?></div>
</div>
