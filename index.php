<?php
/**
 * Plugin Name:     The Events Calendar Extension: Schedule Day View
 * Description:     Overrides The Events Calendar's Day View with a Schedule Day View, displaying events within All
 * Day, Morning, Afternoon, and Evening contexts, as well as indicating events happening right now.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Schedule_Day_View
 * Author:          Modern Tribe, Inc.
 * Author URI:      http://m.tri.be/1971
 * License:         GPL version 3 or any later version
 * License URI:     https://www.gnu.org/licenses/gpl-3.0.html
 *
 *     This plugin is free software: you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation, either version 3 of the License, or
 *     any later version.
 *
 *     This plugin is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *     GNU General Public License for more details.
 */

// Do not load unless Tribe Common is fully loaded.
if ( ! class_exists( 'Tribe__Extension' ) ) {
	return;
}

/**
 * Extension main class, class begins loading on init() function.
 */
class Tribe__Extension__Schedule_Day_View extends Tribe__Extension {

	/**
	 * The prefix used for settings or whatever else needs to be namespaced.
	 */
	const PREFIX = 'tribe_ext_sch_day_view';

	/**
	 * Time slot prefix that has itself already been namespaced. Used in WP_Post.
	 *
	 * @see Tribe__Extension__Schedule_Day_View::PREFIX
	 *
	 * @return string
	 */
	public function time_slot_name() {
		return self::PREFIX . '_time_slot';
	}

	/**
	 * The current time slot's array of arguments. Used in the loop template file per time slot.
	 *
	 * @var array
	 */
	public $current_time_slot_args = array();

	/**
	 * The list of The Events Calendar's template files to override with which of this plugin's template files.
	 *
	 * @return array
	 */
	private function templates() {
		return array(
			'day/single.php'          => 'src/views/day/single.php',
			'day/single-event.php'    => 'src/views/day/single.php',
			'day/single-featured.php' => 'src/views/day/single.php',
			'day/loop.php'            => 'src/views/day/loop.php',
		);
	}

	/**
	 * Set the minimum version of The Events Calendar and set this extension's URL.
	 */
	public function construct() {
		// Tribe__Assets::maybe_get_min_file() requires v4.3
		$this->add_required_plugin( 'Tribe__Events__Main', '4.3' );
		$this->set_url( 'https://theeventscalendar.com/extensions/schedule-day-view/' );
	}

	/**
	 * Start doing stuff.
	 */
	public function init() {
		$this->setup_templates();
		$this->setup_loop();
		$this->display_cleanup();
		add_action( 'init', array( $this, 'register_assets' ) );

		// Load assets for main day view archive
		add_action( 'wp_enqueue_scripts', array( $this, 'load_assets_in_day_view_archive' ) );
		// Load assets for PRO shortcode's day view
		add_action( 'tribe_events_pro_tribe_events_shortcode_prepare_day', array( $this, 'load_assets_in_day_view_shortcode' ) );

		add_filter( 'tribe_get_events_title', array( $this, 'set_todays_day_view_title' ) );

		$this->setup_plain_language_redirect(); // TODO
	}

	/**
	 * Filters templates to use our overrides.
	 */
	private function setup_templates() {
		foreach ( $this->templates() as $template => $new_template ) {
			add_filter( 'tribe_get_template_part_path_' . $template, function ( $file, $slug, $name ) use ( $new_template ) {
				// Return the path for our file.
				return plugin_dir_path( __FILE__ ) . $new_template;
			}, 10, 3 );
		}
	}

	/**
	 * Register this view's assets.
	 */
	public function register_assets() {
		$resources_url = trailingslashit( plugin_dir_url( __FILE__ ) ) . 'src/resources/';

		$css = $resources_url . 'css/style.css';
		$css = Tribe__Assets::maybe_get_min_file( $css );

		$js = $resources_url . 'js/script.js';
		$js = Tribe__Assets::maybe_get_min_file( $js );

		// Just making sure our CSS only loads if The Events Calendar's global CSS successfully loaded.
		wp_register_style( self::PREFIX, $css, array( 'tribe-events-calendar-style' ), $this->get_version() );

		// We don't actually use any MomentJS in this extension, but it's a way to ensure we're loading after the default The Events Calendar stuff does. Plus, we might want to do some fancier JS stuff in the future, in which case MomentJS would probably come in handy.
		wp_register_script( self::PREFIX . '_js', $js, array( 'tribe-moment' ), $this->get_version(), true );
	}

	/**
	 * Load this view's assets in day view archive.
	 */
	public function load_assets_in_day_view_archive() {
		if ( tribe_is_day() ) {
			wp_enqueue_style( self::PREFIX );
			wp_enqueue_script( self::PREFIX . '_js' );
		}
	}

	/**
	 * Load this view's assets in day view shortcode.
	 */
	public function load_assets_in_day_view_shortcode() {
		wp_enqueue_style( self::PREFIX );
		wp_enqueue_script( self::PREFIX . '_js' );
	}

	/**
	 * Get the time slots (except for All Day) and the integer hours they include as a multidimensional aray.
	 *
	 * @see \Tribe__Events__Filterbar__Filters__Time_Of_Day::get_values()
	 *
	 * @return array
	 */
	protected function get_time_of_day_ranges() {
		return array(
			__( 'Morning', 'tribe-ext-schedule-day-view' )   => array(
				6,
				7,
				8,
				9,
				10,
				11,
			),
			__( 'Afternoon', 'tribe-ext-schedule-day-view' ) => array(
				12,
				13,
				14,
				15,
				16,
			),
			__( 'Evening', 'tribe-ext-schedule-day-view' )   => array(
				17,
				18,
				19,
				20,
			),
			__( 'Night', 'tribe-ext-schedule-day-view' )     => array(
				21,
				22,
				23,
				0, // midnight the next day
				1, // 1am the next day
				2, // 2am the next day
				3, // 3am the next day
				4, // 4am the next day
				5, // 5am the next day
			),
		);
	}

	/**
	 * Set day view archive's title to be "Today's Event" if just 1 event, else "Today's Events".
	 *
	 * @param $title
	 *
	 * @return string
	 */
	public function set_todays_day_view_title( $title ) {
		global $wp_query;

		if (
			tribe_is_day()
			&& $this->today()
		) {
			if ( 1 === $wp_query->found_posts ) {
				$title = sprintf( __( "Today's %s", 'tribe-ext-schedule-day-view' ), tribe_get_event_label_singular() );
			} else {
				// Either zero events (when a notice would display) or 2+ events
				$title = sprintf( __( "Today's %s", 'tribe-ext-schedule-day-view' ), tribe_get_event_label_plural() );
			}

			$title = esc_html( $title );
		}

		return $title;
	}

	/**
	 * Get the "All Day" text after translation.
	 *
	 * Used to compare/determine if we are in the All Day time slot, which is why
	 * we need to have this as a method instead, to keep things DRY.
	 *
	 * @return string
	 */
	public function get_all_day_text() {
		return esc_html__( 'All Day', 'tribe-ext-schedule-day-view' );
	}

	/**
	 * Get the number of events within a given time slot.
	 *
	 * @param $time_slot
	 *
	 * @return int
	 */
	private function get_time_slot_event_count( $time_slot ) {
		global $wp_query;

		if ( empty( $wp_query->time_slot_counts[$time_slot] ) ) {
			$event_count = 0;
		} else {
			$event_count = $wp_query->time_slot_counts[$time_slot];
		}

		return absint( $event_count );
	}

	/**
	 * Get the title of a given time slot.
	 *
	 * @param $time_slot
	 *
	 * @return string
	 */
	private function get_time_slot_title( $time_slot ) {
		$event_count = $this->get_time_slot_event_count( $time_slot );

		if ( 0 === $event_count ) {
			$event_count_text = sprintf( __( '(No %s)', 'tribe-ext-schedule-day-view' ), tribe_get_event_label_plural() );
		} elseif ( 1 === $event_count ) {
			$event_count_text = sprintf( __( '(%d %s)', 'tribe-ext-schedule-day-view' ), $event_count, tribe_get_event_label_singular() );
		} else {
			$event_count_text = sprintf( __( '(%d %s)', 'tribe-ext-schedule-day-view' ), $event_count, tribe_get_event_label_plural() );
		}

		return sprintf( esc_html__( '%s %s', 'tribe-ext-schedule-day-view' ), $time_slot, $event_count_text );
	}

	/**
	 * Sets up each time slot and its data and adds it to $wp_query.
	 *
	 * Runs on the initial action hook in the src/views/day/loop.php template.
	 */
	private function setup_loop() {
		add_action( 'tribe_ext_sch_day_inside_before_loop', function () {
			global $wp_query;

			$wp_query->set( 'time_slots', $this->get_time_slots_array_of_timestamp_ranges() );

			$time_slot_name = $this->time_slot_name();

			$all_time_slots    = array();
			$active_time_slots = array();

			foreach ( $wp_query->posts as &$post ) {
				if ( tribe_event_is_all_day( $post->ID ) ) {
					$post->$time_slot_name = $this->get_all_day_text();
				} else {
					$post->$time_slot_name = $this->get_non_all_day_time_slot_name( $post->ID );
				}

				$all_time_slots[] = $post->$time_slot_name;

				$post->is_active_on_load = $this->active( $post );

				if ( $post->is_active_on_load ) {
					$active_time_slots[] = $post->$time_slot_name;
				}
			}

			$wp_query->time_slot_counts = array_count_values( $all_time_slots );

			$wp_query->active_time_slots = array_unique( $active_time_slots );

			$wp_query->rewind_posts();
		} );
	}

	/**
	 * Build the data for a given time slot.
	 *
	 * @param $time_slot
	 */
	public function build_current_time_slot_args( $time_slot ) {
		global $wp_query;

		$args = array(
			'is_all_day_time_slot' => $time_slot === $this->get_all_day_text(),
			'is_active_on_load'   => in_array( $time_slot, $wp_query->active_time_slots ) ? true : false,
		);

		$args['class_group_active_events_on_load'] = $args['is_active_on_load'] ? ' tribe-events-day-grouping-event-is-active' : '';
		$args['aria_expanded_on_load']             = $args['is_active_on_load'] ? 'true' : 'false';
		$args['aria_hidden_on_load']               = $args['is_active_on_load'] ? 'false' : 'true';
		$args['time_slot_id']                       = $this->get_time_slot_id_from_time_slot( $time_slot );
		$args['time_slot_event_count']              = $this->get_time_slot_event_count( $time_slot );
		$args['time_slot_title']                    = $this->get_time_slot_title( $time_slot );
		$args['button_id']                         = $this->get_button_id_from_time_slot( $time_slot );
		$args['start_timestamp']                   = $this->get_time_slot_timestamp( $time_slot );
		$args['end_timestamp']                     = $this->get_time_slot_timestamp( $time_slot, false );

		$this->current_time_slot_args = $args;
	}

	/**
	 * Get an event's start or end timestamp.
	 *
	 * @param        $post_id
	 * @param string $start_end
	 *
	 * @return false|int
	 */
	public function get_timestamp( $post_id, $start_end = 'Start' ) {
		// We do it this way until \Tribe__Events__Timezones::event_start_timestamp() and end methods actually work by being TZ dependent instead of always interpreted as being in UTC
		$time = sprintf(
			'%s %s',
			get_post_meta( $post_id, "_Event{$start_end}Date", true ),
			Tribe__Events__Timezones::get_event_timezone_string( $post_id )
		);

		return strtotime( $time );
	}

	/**
	 * Get the front-end time slot name of a given time slot (does not work for
	 * All Day time slot).
	 *
	 * @param $post_id
	 *
	 * @return int|string
	 */
	private function get_non_all_day_time_slot_name( $post_id ) {
		$timezone = Tribe__Events__Timezones::wp_timezone_string();

		$existing_timezone = date_default_timezone_get(); // will fallback to UTC but may also return a TZ environment variable (e.g. EST)

		if ( ! in_array( $timezone, timezone_identifiers_list() ) ) {
			$timezone = get_option( 'timezone_string' ); // could return NULL
		}

		if ( empty( $timezone ) ) {
			$timezone = $existing_timezone;
		}

		date_default_timezone_set( $timezone );

		$hour = date_i18n( 'G', $this->get_timestamp( $post_id ) );

		// set back to what date_default_timezone_get() was
		date_default_timezone_set( $existing_timezone );

		foreach ( $this->get_time_of_day_ranges() as $time_of_day => $hours ) {
			if ( in_array( $hour, $hours ) ) {
				return $time_of_day;
			}
		}
	}

	/**
	 * Get the timestamp of "right now" in the timezone from WordPress settings.
	 *
	 * @return int
	 */
	public static function now_timestamp() {
		$timezone = Tribe__Events__Timezones::wp_timezone_string();

		$existing_timezone = date_default_timezone_get(); // will fallback to UTC but may also return a TZ environment variable (e.g. EST)

		if ( ! in_array( $timezone, timezone_identifiers_list() ) ) {
			$timezone = get_option( 'timezone_string' ); // could return NULL
		}

		if ( empty( $timezone ) ) {
			$timezone = $existing_timezone;
		}

		date_default_timezone_set( $timezone );

		$now_timestamp = time();

		// set back to what date_default_timezone_get() was
		date_default_timezone_set( $existing_timezone );

		return $now_timestamp;
	}

	/**
	 * Get today's date in the format of 'Y-m-d' (e.g. 2018-03-01).
	 *
	 * @see \Tribe__Events__Template__Day::header_attributes()
	 */
	private function get_today_ymd() {
		$timezone = Tribe__Events__Timezones::wp_timezone_string();

		$existing_timezone = date_default_timezone_get(); // will fallback to UTC but may also return a TZ environment variable (e.g. EST)

		if ( ! in_array( $timezone, timezone_identifiers_list() ) ) {
			$timezone = get_option( 'timezone_string' ); // could return NULL
		}

		if ( empty( $timezone ) ) {
			$timezone = $existing_timezone;
		}

		date_default_timezone_set( $timezone );

		$today_ymd = date( 'Y-m-d', self::now_timestamp() );

		// set back to what date_default_timezone_get() was
		date_default_timezone_set( $existing_timezone );

		return $today_ymd;
	}

	/**
	 * Determine if an event is a featured event.
	 *
	 * Usable within the loop and single templates.
	 *
	 * @return bool
	 */
	public function is_featured_event() {
		global $post;

		return (bool) tribe( 'tec.featured_events' )->is_featured( $post->ID );
	}

	/**
	 * Remove additional information we do not want to display in this
	 * minimalized view.
	 */
	private function display_cleanup() {
		add_filter(
			'tribe_events_recurrence_tooltip', function ( $tooltip ) {
			return '';
		}, 10, 1
		);

		add_filter(
			'tribe_get_venue_details', function ( $venue_details ) {
			unset( $venue_details['address'] );

			return $venue_details;
		}
		);
	}

	/**
	 * Determine if the day view is currently rendering today's events.
	 *
	 * @return bool
	 */
	public function today() {
		global $wp_query;

		if ( $this->get_today_ymd() === $wp_query->get( 'eventDate' ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Determine if an event is within an "active" (All Day or Right Now) time
	 * slot.
	 *
	 * This method is to only be used within the loop.
	 *
	 * @param WP_Post $post
	 *
	 * @return bool
	 */
	public function active( WP_Post $post ) {
		$time_slot_name = $this->time_slot_name();

		if ( ! $this->today() ) {
			return true;
		}

		if ( $this->get_all_day_text() === $post->$time_slot_name ) {
			return true;
		}

		$now = time();

		if (
			$now >= $this->get_time_slot_timestamp( $post->$time_slot_name )
			&& $now <= $this->get_time_slot_timestamp( $post->$time_slot_name, false )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Build the array of time slots and their start and end timestamps.
	 *
	 * The end of one time slot is 1 second less than the start of the next
	 * time slot.
	 *
	 * @return array
	 */
	public function get_time_slots_array_of_timestamp_ranges() {
		$today_ymd = $this->get_today_ymd();

		$time_slot_timestamps = array();

		foreach ( $this->get_time_of_day_ranges() as $time_of_day => $hours ) {
			$start_hour = $hours[0];
			if ( 6 > $start_hour ) {
				$start_hour = 24 + 6;
			}
			$start_hour_string = sprintf( '%s %s +%d hours', $today_ymd, Tribe__Events__Timezones::wp_timezone_string(), $start_hour );

			$end_hour = end( $hours );
			reset( $hours );
			if ( 6 > $end_hour ) {
				$end_hour = 24 + 6;
			}
			$end_hour        += 1; // We actually need the start hour of the next range
			$end_hour_string = sprintf( '%s %s +%d hours', $today_ymd, Tribe__Events__Timezones::wp_timezone_string(), $end_hour );

			$time_slot_timestamps[$time_of_day] = array(
				'start' => strtotime( $start_hour_string ),
				'end'   => strtotime( $end_hour_string ) - 1, // one second less than the start hour of the next range
			);
		}

		return $time_slot_timestamps;
	}

	/**
	 * Get the start or end timestamp of a given time slot.
	 *
	 * @param string $time_slot
	 * @param bool   $start
	 *
	 * @return string
	 */
	public function get_time_slot_timestamp( $time_slot = '', $start = true ) {
		global $wp_query;

		if (
			empty( $time_slot )
			|| $this->get_all_day_text() === $time_slot
		) {
			return '';
		}

		if ( $start ) {
			return $wp_query->get( 'time_slots' )[$time_slot]['start'];
		} else {
			return $wp_query->get( 'time_slots' )[$time_slot]['end'];
		}
	}

	/**
	 * Get the div ID of a given time slot.
	 *
	 * This is for best practices but is not used for JS or CSS targeting.
	 *
	 * @param string $time_slot
	 *
	 * @return string
	 */
	private function get_time_slot_id_from_time_slot( $time_slot = '' ) {
		if ( ! empty( $time_slot ) ) {
			$time_slot = str_replace( ' ', '-', $time_slot ); // e.g. All Day becomes All-Day

			return 'tribe-events-day-time-slot-' . esc_attr( $time_slot );
		}
	}

	/**
	 * Get the button ID of a given time slot.
	 *
	 * This is for best practices but is not used for JS or CSS targeting.
	 *
	 * @param string $time_slot
	 *
	 * @return string
	 */
	private function get_button_id_from_time_slot( $time_slot = '' ) {
		if ( ! empty( $time_slot ) ) {
			$time_slot = str_replace( ' ', '-', $time_slot ); // e.g. All Day becomes All-Day

			return 'time-slot-trigger-' . esc_attr( $time_slot );
		}
	}

	/**
	 * TODO
	 */
	private function setup_plain_language_redirect() {
		add_filter(
			'query_vars', function ( $vars ) {
			return array_merge( $vars, array( 'eventDateModified' ), array( 'eventWeekModified' ), array( 'eventMonthModified' ) );
		}, 10, 1
		);

		$plain_language = array(
			__( 'events/tomorrow', 'tribe-ext-schedule-day-view' )                => 'index.php?post_type=tribe_events&eventDateModified=1',
			__( 'events/yesterday', 'tribe-ext-schedule-day-view' )               => 'index.php?post_type=tribe_events&eventDateModified=-1',
			__( 'events/nextweek', 'tribe-ext-schedule-day-view' )                => 'index.php?post_type=tribe_events&eventWeekModified=1&eventDisplay=week',
			__( 'events/lastweek', 'tribe-ext-schedule-day-view' )                => 'index.php?post_type=tribe_events&eventWeekModified=-1&eventDisplay=week',
			__( 'events/nextmonth', 'tribe-ext-schedule-day-view' )               => 'index.php?post_type=tribe_events&eventMonthModified=1&eventDisplay=month',
			__( 'events/lastmonth', 'tribe-ext-schedule-day-view' )               => 'index.php?post_type=tribe_events&eventMonthModified=-1&eventDisplay=month',
			__( 'events/today', 'tribe-ext-schedule-day-view' ) . '/(\-?[0-9])/?' => 'index.php?post_type=tribe_events&eventDateModified=$matches[1]',
			__( 'events/week', 'tribe-ext-schedule-day-view' ) . '/(\-?[0-9])/?'  => 'index.php?post_type=tribe_events&eventWeekModified=$matches[1]&eventDisplay=week',
			__( 'events/month', 'tribe-ext-schedule-day-view' ) . '/(\-?[0-9])/?' => 'index.php?post_type=tribe_events&eventMonthModified=$matches[1]&eventDisplay=month',
		);

		add_filter(
			'rewrite_rules_array', function ( $rules ) use ( $plain_language ) {
			$new_rules = array_merge(
				$plain_language,
				$rules
			);

			return $new_rules;
		}, 10, 1
		);

		add_filter(
			'pre_get_posts', function ( $query ) {
			if ( get_query_var( 'eventDateModified' ) ) {
				$offset = date( 'Y-m-d', time() + ( DAY_IN_SECONDS * get_query_var( 'eventDateModified' ) ) );
				$query->set( 'eventDate', $offset );
			}

			if ( get_query_var( 'eventWeekModified' ) ) {
				$offset = date( 'Y-m-d', time() + ( WEEK_IN_SECONDS * get_query_var( 'eventWeekModified' ) ) );
				$query->set( 'eventDate', $offset );
			}

			if ( get_query_var( 'eventMonthModified' ) ) {
				$offset = date( 'Y-m-d', time() + ( MONTH_IN_SECONDS * get_query_var( 'eventMonthModified' ) ) );
				$query->set( 'eventDate', $offset );
			}

			return $query;
		}
		);
	}

}
