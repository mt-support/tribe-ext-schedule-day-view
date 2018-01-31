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
$more             = false;
$current_timeslot = null;

?>

<div id="tribe-events-day" class="tribe-events-loop" data-site-timezone="<?php echo esc_attr( Tribe__Events__Timezones::wp_timezone_string() ); ?>" data-now="<?php echo esc_attr( time() ); ?>">
	<div class="tribe-events-day-time-slot">

		<?php do_action( 'tribe_ext_sch_day_inside_before_loop' ); ?>
		<?php while ( have_posts() ) :
		the_post(); ?>

		<?php if ( $current_timeslot != $post->timeslot ) :
		$current_timeslot = $post->timeslot; ?>
	</div>
	<!-- .tribe-events-day-time-slot -->

	<div class="tribe-events-day-time-slot">
		<h5><?php echo $current_timeslot; ?></h5>
		<?php endif; ?>
		<div id="post-<?php the_ID(); ?>" class="<?php tribe_events_event_classes(); ?>">
			<?php tribe_get_template_part( 'day/single' ); ?>
		</div>

		<?php do_action( 'tribe_ext_sch_day_inside_after_loop' ); ?>
		<?php endwhile; ?>

	</div>
	<!-- .tribe-events-day-time-slot -->
</div><!-- .tribe-events-loop -->
