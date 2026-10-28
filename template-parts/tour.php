<?php
/**
 * Kommende Shows. Vergangene verschwinden automatisch.
 */

defined( 'ABSPATH' ) || exit;

$shows = get_posts( array(
	'post_type'      => 'gl_show',
	'posts_per_page' => 20,
	'meta_key'       => '_gl_date',
	'orderby'        => 'meta_value',
	'order'          => 'ASC',
	'meta_query'     => array(
		array( 'key' => '_gl_date', 'value' => wp_date( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ),
	),
) );
$year = $shows ? wp_date( 'Y', strtotime( gl_meta( $shows[0]->ID, 'date' ) ) ) : wp_date( 'Y' );
?>
<section class="section" id="tour">
	<div class="section__head section__head--row">
		<div>
			<span class="meta" data-reveal>Live</span>
			<h2 class="display-l" data-split>Tour <br><?php echo esc_html( $year ); ?></h2>
		</div>
		<?php if ( gl_opt( 'instagram' ) ) : ?>
			<a href="<?php echo esc_url( gl_opt( 'instagram' ) ); ?>" target="_blank" rel="noopener" class="meta meta--hell unterstrichen" data-reveal>Get notified ↗</a>
		<?php endif; ?>
	</div>

	<?php if ( $shows ) : ?>
		<ol class="tour" data-stagger>
			<?php foreach ( $shows as $s ) :
				$status = gl_meta( $s->ID, 'status', 'tickets' );
				$ticket = gl_meta( $s->ID, 'ticket_url' );
				$venue  = trim( gl_meta( $s->ID, 'venue' ) . ( gl_meta( $s->ID, 'support' ) ? ' · ' . gl_meta( $s->ID, 'support' ) : '' ) );
				$off    = in_array( $status, array( 'soldout', 'cancelled' ), true );
				?>
				<li class="tour__row<?php echo $off ? ' tour__row--aus' : ''; ?>">
					<span class="meta"><?php echo esc_html( wp_date( 'd.m.y', strtotime( gl_meta( $s->ID, 'date' ) ) ) ); ?></span>
					<span class="display-m"><?php echo esc_html( gl_meta( $s->ID, 'city', get_the_title( $s ) ) ); ?></span>
					<span class="tour__venue"><?php echo esc_html( $venue ); ?></span>
					<?php if ( $status === 'soldout' ) : ?>
						<span class="meta">Sold out</span>
					<?php elseif ( $status === 'cancelled' ) : ?>
						<span class="meta">Cancelled</span>
					<?php elseif ( $status === 'free' ) : ?>
						<span class="meta meta--hell">Free entry</span>
					<?php elseif ( $ticket ) : ?>
						<a href="<?php echo esc_url( $ticket ); ?>" target="_blank" rel="noopener" class="btn btn--klein<?php echo $status === 'last' ? ' btn--patina' : ''; ?>"><?php echo $status === 'last' ? 'Last tickets' : 'Tickets'; ?> <span class="pfeil">↗</span></a>
					<?php else : ?>
						<span class="meta meta--hell"><?php echo $status === 'last' ? 'Last tickets' : 'Tickets soon'; ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php else : ?>
		<p class="tour__leer" data-reveal>No shows announced right now. Follow us on Instagram and you’ll be the first to know.</p>
	<?php endif; ?>
</section>
