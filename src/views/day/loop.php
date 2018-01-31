<?php
/**
 * Schedule Day View Loop
 * This file sets up the structure for the Schedule Day View loop.
 *
 * TODO:
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/day/loop.php
 *
 * @version 1.0.0
 * @package TribeEventsCalendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

global $more, $post, $wp_query;
$more = false;
$current_timeslot = null;

?>

<div id="tribe-events-day" class="tribe-events-loop">
	<div class="tribe-events-day-time-slot">

	<?php while ( have_posts() ) : the_post(); ?>
		<?php do_action( 'tribe_ext_sch_day_inside_before_loop' ); ?>

		<?php if ( $current_timeslot != $post->timeslot ) :
		$current_timeslot = $post->timeslot; ?>
	</div>
	<!-- .tribe-events-day-time-slot -->

	<div class="tribe-events-day-time-slot">
		<h5><?php echo $current_timeslot; ?></h5>
		<?php endif;

		<!-- Event  -->
		$event_type = tribe( 'tec.featured_events' )->is_featured( $post->ID ) ? 'featured' : 'event';

		/**
		* Filters the event type used when selecting a template to render
		*
		* @param $event_type
		*/
		$event_type = apply_filters( 'tribe_ext_sch_day_view_event_type', $event_type );
		?>
		<div id="post-<?php the_ID(); ?>" class="<?php tribe_events_event_classes(); ?> event-type-<?php $event_type; ?>">
			<?php tribe_get_template_part( 'day/single' ); ?>
		</div>

		<?php do_action( 'tribe_ext_sch_day_inside_after_loop' ); ?>
	<?php endwhile; ?>

	</div>
	<!-- .tribe-events-day-time-slot -->
</div><!-- .tribe-events-loop -->
