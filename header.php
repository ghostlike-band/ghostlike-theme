<?php defined( 'ABSPATH' ) || exit; ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link visually-hidden" href="#content">Skip to content</a>
<?php gl_svg_sprite(); ?>

<?php
$items = array();
if ( has_nav_menu( 'main' ) ) {
	$locations = get_nav_menu_locations();
	foreach ( (array) wp_get_nav_menu_items( $locations['main'] ) as $item ) {
		if ( ! $item->menu_item_parent ) {
			$items[] = array( 'url' => $item->url, 'label' => $item->title );
		}
	}
} else {
	// Menü aus den Sektionen (Customizer → GHOSTLIKE → Sektionen & Menü)
	foreach ( gl_sections() as $s ) {
		if ( $s['on'] && $s['menu'] && $s['label'] !== '' ) {
			$items[] = array( 'url' => gl_anchor( $s['id'] ), 'label' => $s['label'] );
		}
	}
}
$member_on    = (bool) get_theme_mod( 'gl_member_login_header', 1 ) && gl_opt( 'member_login_url' );
$member_label = is_user_logged_in() && current_user_can( 'gl_portal' ) ? 'Portal' : 'Members';
$music_on = (bool) array_filter( gl_sections(), fn( $s ) => $s['id'] === 'music' && $s['on'] );
?>
<header class="nav<?php echo is_front_page() ? '' : ' is-solid'; ?>" id="nav">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="nav__mark logo" aria-label="<?php bloginfo( 'name' ); ?> — Home"><?php gl_mark(); ?></a>
	<nav class="nav__links" aria-label="Main">
		<?php foreach ( $items as $it ) : ?>
			<a href="<?php echo esc_url( $it['url'] ); ?>"><?php echo esc_html( $it['label'] ); ?></a>
		<?php endforeach; ?>
	</nav>
	<div class="nav__cta">
		<?php if ( $music_on ) : ?><a href="<?php echo esc_url( gl_anchor( 'music' ) ); ?>" class="btn btn--patina btn--klein">Listen <span class="pfeil">↗</span></a><?php endif; ?>
		<?php if ( $member_on ) : ?>
			<a href="<?php echo esc_url( gl_opt( 'member_login_url' ) ); ?>" class="nav__member" aria-label="Member login" rel="nofollow">
				<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M7 10V7a5 5 0 0 1 10 0v3" fill="none" stroke="currentColor" stroke-width="2"/><rect x="4" y="10" width="16" height="11" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 14v3" stroke="currentColor" stroke-width="2"/></svg>
				<span><?php echo esc_html( $member_label ); ?></span>
			</a>
		<?php endif; ?>
		<button class="nav__burger" aria-label="Open menu" aria-expanded="false" aria-controls="menu"><span></span><span></span></button>
	</div>
</header>

<div class="menu" id="menu" hidden>
	<nav aria-label="Mobile">
		<?php foreach ( $items as $it ) : ?>
			<a href="<?php echo esc_url( $it['url'] ); ?>"><?php echo esc_html( $it['label'] ); ?></a>
		<?php endforeach; ?>
	</nav>
	<a href="<?php echo esc_url( gl_opt( 'member_login_url' ) ); ?>" class="meta meta--hell">Member Login ↗</a>
</div>

<main id="content">
