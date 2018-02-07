<?php
/**
 * Day View Single Event
 * This file contains one event in the day view
 *
 * While this plugin is active, you cannot override this template in your own theme because it's loaded via a filter from tribe_get_template_part() in this plugin's index.php.
 *
 * @version 4.5.11
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

global $post;

$venue_details = tribe_get_venue_details();

// Venue microformats
$has_venue = $venue_details ? ' vcard' : '';

// The address string via tribe_get_venue_details will often be populated even when there's
// no address, so let's get the address string on its own for a couple of checks below.
$venue_address = tribe_get_address();

$current_timeslot_args = Tribe__Extension__Schedule_Day_View::instance()->current_timeslot_args;
?>
<div
	id="post-<?php echo esc_attr( get_the_ID() ); ?>"
	class="<?php tribe_events_event_classes( $post->ID ); ?> post-tribe-events-day-group-event <?php echo esc_attr( $current_timeslot_args['class_group_active_events_on_load'] ); ?>"
	aria-hidden="<?php echo esc_attr( $current_timeslot_args['aria_hidden_on_load'] ); ?>"
	aria-labelledby="<?php echo esc_attr( $current_timeslot_args['button_id'] ); ?>"
	data-tribe-group-event-start="<?php
	// We do it this way until \Tribe__Events__Timezones::event_start_timestamp() and end methods actually work by being TZ dependent instead of always interpreted as being in UTC
	$start = sprintf(
		'%s %s',
		get_post_meta( $post->ID, "_EventStartDate", true ),
		Tribe__Events__Timezones::get_event_timezone_string( $post->ID )
	);
	echo esc_attr( strtotime( $start ) );
	?>"
	data-tribe-group-event-end="<?php
	// We do it this way until \Tribe__Events__Timezones::event_start_timestamp() and end methods actually work by being TZ dependent instead of always interpreted as being in UTC
	$end = sprintf(
		'%s %s',
		get_post_meta( $post->ID, "_EventEndDate", true ),
		Tribe__Events__Timezones::get_event_timezone_string( $post->ID )
	);
	echo esc_attr( strtotime( $end ) );
	?>"
>

	<!-- Schedule & Recurrence Details -->
	<div class="tribe-updated published time-details">
		<?php echo tribe_events_event_schedule_details(); ?>
	</div>

	<div class="tribe-events-day-event-content">

		<!-- Event Title -->
		<?php do_action( 'tribe_ext_sch_day_single_before_the_event_title' ) ?>
		<h2 class="tribe-events-list-event-title summary ">
			<a class="url" href="<?php echo esc_url( tribe_get_event_link() ); ?>" title="<?php the_title_attribute() ?>" rel="bookmark">
				<?php the_title() ?>
			</a>
		</h2>
		<?php do_action( 'tribe_ext_sch_day_single_after_the_event_title' ) ?>

		<!-- Event Meta -->
		<?php do_action( 'tribe_ext_sch_day_single_before_the_meta' ) ?>
		<div class="tribe-events-event-meta <?php echo esc_attr( $has_venue ); ?>">

			<?php if ( $venue_details ) : ?>
				<!-- Venue Display Info -->
				<div class="tribe-events-venue-details">
					<?php
					$address_delimiter = empty( $venue_address ) ? ' ' : ', ';

					// These details are already escaped in various ways earlier in the code.
					echo implode( $address_delimiter, $venue_details );
					?>
				</div> <!-- .tribe-events-venue-details -->
			<?php endif; ?>

		</div><!-- .tribe-events-event-meta -->

		<?php if ( tribe_get_cost() ) : ?>
			<div class="tribe-events-event-cost">
				<span class="ticket-cost"><?php echo tribe_get_cost( null, true ); ?></span>
				<?php
				/** This action is documented in the-events-calendar/src/views/list/single-event.php */
				do_action( 'tribe_ext_sch_day_single_inside_cost' )
				?>
			</div>
		<?php endif; ?>

		<?php do_action( 'tribe_ext_sch_day_single_after_the_meta' ) ?>

		<!-- Event Content -->
		<?php do_action( 'tribe_ext_sch_day_single_before_the_content' ) ?>
		<a href="<?php echo esc_url( tribe_get_event_link() ); ?>" class="tribe-events-read-more" rel="bookmark">
			<?php esc_html_e( 'Event Details', 'tribe-ext-schedule-day-view' ) ?> &raquo;
		</a>
		<?php do_action( 'tribe_ext_sch_day_single_after_the_content' ); ?>

	</div><!-- .tribe-events-day-event-content -->
</div><!-- .tribe-events-day-group-event -->

