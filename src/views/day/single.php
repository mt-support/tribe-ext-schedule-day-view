<?php
/**
 * Day View Single Event
 * This file contains one event in the day view
 *
 * TODO: Add special class to Featured Events
 * Override this template in your own theme by creating a file at [your-theme]/tribe-events/day/single.php
 *
 * @version 4.5.11
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

$venue_details = tribe_get_venue_details();

// Venue microformats
$has_venue = $venue_details ? ' vcard' : '';

// The address string via tribe_get_venue_details will often be populated even when there's
// no address, so let's get the address string on its own for a couple of checks below.
$venue_address = tribe_get_address();

?>

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
            <?php esc_html_e( 'Event Details', 'the-events-calendar' ) ?> &raquo;
        </a>
    <?php do_action( 'tribe_ext_sch_day_single_after_the_content' ); ?>

</div>
