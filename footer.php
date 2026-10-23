<?php defined( 'ABSPATH' ) || exit; ?>
</main>

<footer class="footer">
	<div class="footer__top">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="footer__logo logo" aria-label="<?php bloginfo( 'name' ); ?>"><?php gl_logo(); ?></a>
		<span class="meta"><?php echo esc_html( gl_opt( 'tagline' ) ); ?></span>
	</div>
	<div class="footer__grid">
		<div>
			<span class="meta">Follow</span>
			<?php foreach ( gl_socials() as $s ) : ?>
				<a href="<?php echo esc_url( $s['url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $s['label'] ); ?> ↗</a>
			<?php endforeach; ?>
		</div>
		<div>
			<span class="meta">Info</span>
			<?php
			if ( has_nav_menu( 'footer' ) ) {
				$locations = get_nav_menu_locations();
				foreach ( (array) wp_get_nav_menu_items( $locations['footer'] ) as $item ) {
					echo '<a href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
				}
			} elseif ( get_privacy_policy_url() ) {
				echo '<a href="' . esc_url( get_privacy_policy_url() ) . '">Privacy</a>';
			}
			?>
		</div>
	</div>
	<div class="footer__base meta">
		<span><?php echo esc_html( gl_opt( 'footer_meta' ) ); ?></span><span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></span>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
