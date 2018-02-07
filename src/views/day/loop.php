<?php
/**
 * Schedule Day View Loop
 * This file sets up the structure for the Schedule Day View loop.
 *
 * While this plugin is active, you cannot override this template in your own theme because it's loaded via a filter from tribe_get_template_part() in this plugin's index.php.
 *
 * @version 1.0.0
 * @package TribeEventsCalendar
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

global $post, $wp_query;

$current_timeslot = null;
$today            = Tribe__Extension__Schedule_Day_View::instance()->today();
$class_is_today   = $today ? ' tribe-events-loop-day-today' : ' tribe-events-day-grouping-is-active tribe-events-loop-day-not-today';

$all_day_text = Tribe__Extension__Schedule_Day_View::instance()->get_all_day_text();

$now = Tribe__Extension__Schedule_Day_View::instance()->now_timestamp();
?>

<div
	id="tribe-events-day"
	class="tribe-events-loop<?php echo esc_attr( $class_is_today ); ?>"
	data-tribe-timezone="<?php echo esc_attr( Tribe__Events__Timezones::wp_timezone_string() ); ?>"
	data-tribe-now="<?php echo esc_attr( $now ); ?>"
>

	<?php
	// Used by Tribe__Extension__Schedule_Day_View::setup_loop()
	do_action( 'tribe_ext_sch_day_inside_before_loop' );
	$all_timeslots = array_merge( array( $all_day_text ), array_keys( $wp_query->get( 'timeslots' ) ) );

	foreach ( $all_timeslots as $current_timeslot ) :
		// Used by Tribe__Extension__Schedule_Day_View::process_a_timeslot()
		Tribe__Extension__Schedule_Day_View::instance()->build_current_timeslot_args( $current_timeslot );
		$current_timeslot_args = Tribe__Extension__Schedule_Day_View::instance()->current_timeslot_args;

		$active_class = '';
		if ( $today ) {
			if (
				$current_timeslot_args['is_all_day_timeslot']
				|| (
					$now >= $current_timeslot_args['start_timestamp']
					&& $now <= $current_timeslot_args['end_timestamp']
				)
			) {
				$active_class = ' tribe-events-day-grouping-is-active tribe-events-day-grouping-is-now';
			}
		} elseif ( ! empty( $current_timeslot_args['timeslot_event_count'] ) ) {
			$active_class = ' tribe-events-day-grouping-is-active';
		}
		?>

		<div
			id="<?php echo esc_attr( $current_timeslot_args['timeslot_id'] ); ?>"
			class="tribe-events-day-time-slot<?php echo esc_attr( $active_class ); ?>"
			data-tribe-groupstart="<?php echo esc_attr( $current_timeslot_args['start_timestamp'] ); ?>"
			data-tribe-groupend="<?php echo esc_attr( $current_timeslot_args['end_timestamp'] ); ?>"
			data-tribe-groupeventscount="<?php echo esc_attr( $current_timeslot_args['timeslot_event_count'] ); ?>"
			<?php
			if ( ! empty( $current_timeslot_args['timeslot_event_count'] ) ) {
				echo 'data-tribe-grouphasevents';
			} else {
				echo 'data-tribe-grouphasnoevents';
			}
			?>
		>
			<h5>
				<button
					id="<?php echo esc_attr( $current_timeslot_args['button_id'] ); ?>"
					class="tribe-events-day-group-trigger"
					aria-expanded="<?php echo esc_attr( $current_timeslot_args['aria_expanded_on_load'] ); ?>"
					<?php
					if ( empty( $current_timeslot_args['timeslot_event_count'] ) ) {
						echo 'disabled';
					}
					?>
				>
					<?php echo esc_html( $current_timeslot_args['timeslot_title'] ); ?>
					<?php
					if ( ! empty( $current_timeslot_args['timeslot_event_count'] ) ) {
						printf( '<span>%s</span>', esc_html__( "Toggle Group's Events", 'tribe-ext-schedule-day-view' ) );
					}
					?>
				</button>
			</h5>
			<?php
			while ( have_posts() ) : the_post();

				$timeslot_name = Tribe__Extension__Schedule_Day_View::instance()->timeslot_name();
				if ( $current_timeslot === $post->$timeslot_name ) {
					tribe_get_template_part( 'day/single' );
				}

			endwhile;
			?>
		</div><!-- .tribe-events-day-time-slot -->

	<?php
	endforeach;

	do_action( 'tribe_ext_sch_day_inside_after_loop' );
	?>

</div><!-- .tribe-events-loop -->
