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
$nav = array(
	'band'    => 'Band',
	'music'   => 'Music',
	'news'    => 'News',
	'tour'    => 'Tour',
	'contact' => 'Contact',
);
$items = array();
if ( has_nav_menu( 'main' ) ) {
	$locations = get_nav_menu_locations();
	foreach ( (array) wp_get_nav_menu_items( $locations['main'] ) as $item ) {
		if ( ! $item->menu_item_parent ) {
			$items[] = array( 'url' => $item->url, 'label' => $item->title );
		}
	}
} else {
	foreach ( $nav as $id => $label ) {
		$items[] = array( 'url' => gl_anchor( $id ), 'label' => $label );
	}
}
?>
<header class="nav<?php echo is_front_page() ? '' : ' is-solid'; ?>" id="nav">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="nav__mark logo" aria-label="<?php bloginfo( 'name' ); ?> — Home"><?php gl_mark(); ?></a>
	<nav class="nav__links" aria-label="Main">
		<?php foreach ( $items as $it ) : ?>
			<a href="<?php echo esc_url( $it['url'] ); ?>"><?php echo esc_html( $it['label'] ); ?></a>
		<?php endforeach; ?>
	</nav>
	<div class="nav__cta">
		<a href="<?php echo esc_url( gl_anchor( 'music' ) ); ?>" class="btn btn--patina btn--klein">Listen <span class="pfeil">↗</span></a>
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
