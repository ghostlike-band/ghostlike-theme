<?php
/**
 * Inhaltstypen: Hero-Slides, Releases, Tourdaten.
 * Blog = normale WordPress-Beiträge.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	$common = array(
		'public'             => false,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_rest'       => false,
		'publicly_queryable' => false,
		'exclude_from_search'=> true,
		'has_archive'        => false,
		'rewrite'            => false,
	);

	register_post_type( 'gl_slide', $common + array(
		'labels'        => array(
			'name'          => 'Hero-Slides',
			'singular_name' => 'Slide',
			'add_new'       => 'Neue Slide',
			'add_new_item'  => 'Neue Slide anlegen',
			'edit_item'     => 'Slide bearbeiten',
			'all_items'     => 'Alle Slides',
		),
		'menu_icon'     => 'dashicons-images-alt2',
		'menu_position' => 20,
		'supports'      => array( 'title', 'thumbnail', 'page-attributes' ),
	) );

	register_post_type( 'gl_release', $common + array(
		'labels'        => array(
			'name'          => 'Releases',
			'singular_name' => 'Release',
			'add_new'       => 'Neues Release',
			'add_new_item'  => 'Neues Release anlegen',
			'edit_item'     => 'Release bearbeiten',
			'all_items'     => 'Alle Releases',
		),
		'menu_icon'     => 'dashicons-album',
		'menu_position' => 21,
		'supports'      => array( 'title', 'thumbnail', 'page-attributes' ),
	) );

	register_post_type( 'gl_show', $common + array(
		'labels'        => array(
			'name'          => 'Tourdaten',
			'singular_name' => 'Show',
			'add_new'       => 'Neue Show',
			'add_new_item'  => 'Neue Show anlegen',
			'edit_item'     => 'Show bearbeiten',
			'all_items'     => 'Alle Shows',
		),
		'menu_icon'     => 'dashicons-tickets-alt',
		'menu_position' => 22,
		'supports'      => array( 'title' ),
	) );
} );

// Hilfreiche Spalten in den Listen.
add_filter( 'manage_gl_show_posts_columns', function ( $cols ) {
	return array( 'cb' => $cols['cb'], 'title' => 'Titel', 'gl_date' => 'Datum', 'gl_city' => 'Stadt', 'gl_status' => 'Status' );
} );
add_action( 'manage_gl_show_posts_custom_column', function ( $col, $id ) {
	if ( $col === 'gl_date' ) {
		$d = gl_meta( $id, 'date' );
		echo esc_html( $d ? date_i18n( 'd.m.Y', strtotime( $d ) ) : '–' );
	} elseif ( $col === 'gl_city' ) {
		echo esc_html( gl_meta( $id, 'city', '–' ) );
	} elseif ( $col === 'gl_status' ) {
		$labels = gl_show_statuses();
		echo esc_html( $labels[ gl_meta( $id, 'status', 'tickets' ) ] ?? '–' );
	}
}, 10, 2 );

add_filter( 'manage_gl_slide_posts_columns', function ( $cols ) {
	return array( 'cb' => $cols['cb'], 'title' => 'Titel', 'gl_type' => 'Typ', 'menu_order' => 'Reihenfolge' );
} );
add_action( 'manage_gl_slide_posts_custom_column', function ( $col, $id ) {
	if ( $col === 'gl_type' ) {
		echo gl_meta( $id, 'video' ) ? 'Video' : 'Bild';
	} elseif ( $col === 'menu_order' ) {
		echo (int) get_post_field( 'menu_order', $id );
	}
}, 10, 2 );

// Slides und Releases in der Admin-Liste nach Reihenfolge sortieren.
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() && $q->is_main_query() && in_array( $q->get( 'post_type' ), array( 'gl_slide', 'gl_release' ), true ) && ! $q->get( 'orderby' ) ) {
		$q->set( 'orderby', 'menu_order' );
		$q->set( 'order', 'ASC' );
	}
} );

function gl_show_statuses() {
	return array(
		'tickets'   => 'Tickets verfügbar',
		'last'      => 'Letzte Tickets',
		'soldout'   => 'Ausverkauft',
		'free'      => 'Eintritt frei',
		'cancelled' => 'Abgesagt',
	);
}
