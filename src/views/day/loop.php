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
$today            = Tribe__Extension__Schedule_Day_View::today();

?>

<div
	id="tribe-events-day"
	class="tribe-events-loop"
	data-site-timezone="<?php echo esc_attr( Tribe__Events__Timezones::wp_timezone_string() ); ?>"
	data-now="<?php echo esc_attr( time() ); ?>"
>

	<div class="tribe-events-day-time-slot">

		<?php do_action( 'tribe_ext_sch_day_inside_before_loop' ); ?>
		<?php while ( have_posts() ) :
		the_post(); ?>

		<?php
		if ( $current_timeslot !== $post->timeslot ) :
		$current_timeslot           = $post->timeslot;
		$is_all_day_timeslot        = $current_timeslot === 'All Day';
		$class_group_active_on_load = ( $today ) || $is_all_day_timeslot ? ' tribe-events-day-grouping-is-active' : '';
		?>
	</div><!-- .tribe-events-day-time-slot -->

	<div class="tribe-events-day-time-slot<?php echo $class_group_active_on_load; ?>">
		<h5>
			<button class="tribe-events-day-group-trigger">
				<?php echo $current_timeslot; ?>
				<span><?php _e( 'Toggle Time Group\'s Events', 'the-events-calendar' ); ?></span>
			</button>
		</h5>
		<?php endif; ?>
		<div id="post-<?php the_ID(); ?>" class="<?php tribe_events_event_classes( 'tribe-events-day-group-event' ); ?>"
			 data-start="<?php
			 // We do it this way until \Tribe__Events__Timezones::event_start_timestamp() and end methods actually work by being TZ dependent instead of always interpreted as being in UTC
			 $start = sprintf(
				 '%s %s',
				 get_post_meta( $post->ID, "_EventStartDate", true ),
				 Tribe__Events__Timezones::get_event_timezone_string( $post->ID )
			 );
			 echo esc_attr( strtotime( $start ) );
			 ?>"
			 data-end="<?php
			 // We do it this way until \Tribe__Events__Timezones::event_start_timestamp() and end methods actually work by being TZ dependent instead of always interpreted as being in UTC
			 $end = sprintf(
				 '%s %s',
				 get_post_meta( $post->ID, "_EventEndDate", true ),
				 Tribe__Events__Timezones::get_event_timezone_string( $post->ID )
			 );
			 echo esc_attr( strtotime( $end ) );
			 ?>"
		>
			<?php tribe_get_template_part( 'day/single' ); ?>
		</div>

		<?php do_action( 'tribe_ext_sch_day_inside_after_loop' ); ?>
		<?php endwhile; ?>

	</div><!-- .tribe-events-day-time-slot -->

</div><!-- .tribe-events-loop -->
