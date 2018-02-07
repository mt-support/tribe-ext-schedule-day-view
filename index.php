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

	const PREFIX = 'tribe_ext_sch_day_view';

	public $current_timeslot_args = array();

	public function timeslot_name() {
		return self::PREFIX . '_timeslot';
	}

	private function templates() {
		return
			[
				'day/single.php'          => 'src/views/day/single.php',
				'day/single-event.php'    => 'src/views/day/single.php',
				'day/single-featured.php' => 'src/views/day/single.php',
				'day/loop.php'            => 'src/views/day/loop.php',
			];
	}


	public function construct() {
		// Tribe__Assets::maybe_get_min_file() requires v4.3
		$this->add_required_plugin( 'Tribe__Events__Main', '4.3' );
		$this->set_url( 'https://theeventscalendar.com/extensions/schedule-day-view/' );
	}

	public function init() {
		$this->setup_templates();
		$this->setup_loop();
		$this->display_cleanup();
		add_action( 'init', array( $this, 'register_assets' ) );

		// Load assets for main archive Day View
		add_action( 'wp_enqueue_scripts', array( $this, 'load_assets_in_day_view_archive' ) );
		// Load assets for PRO shortcode
		add_action(
			'tribe_events_pro_tribe_events_shortcode_prepare_day', array(
				$this,
				'load_assets_in_day_view_shortcode',
			)
		);
		$this->setup_plain_language_redirect();
	}

	/**
	 * Filters templates to use our overrides.
	 */
	private function setup_templates() {
		foreach ( $this->templates() as $template => $new_template ) {
			add_filter(
				'tribe_get_template_part_path_' . $template, function ( $file, $slug, $name ) use ( $new_template ) {
				// Return the path for our file.
				return plugin_dir_path( __FILE__ ) . $new_template;
			}, 10, 3
			);
		}
	}

	/**
	 * Load this view's assets.
	 */
	public function register_assets() {
		$resources_url = trailingslashit( plugin_dir_url( __FILE__ ) ) . 'src/resources/';

		$css = $resources_url . 'css/style.css';
		$css = Tribe__Assets::maybe_get_min_file( $css );

		$js = $resources_url . 'js/script.js';
		$js = Tribe__Assets::maybe_get_min_file( $js );

		wp_register_style( self::PREFIX, $css, array( 'tribe-events-calendar-style' ), $this->get_version() );
		wp_register_script( self::PREFIX . '_js', $js, array( 'tribe-moment' ), $this->get_version(), true );
	}

	/**
	 * Load this view's assets in Day archive view.
	 */
	public function load_assets_in_day_view_archive() {
		if ( tribe_is_day() ) {
			wp_enqueue_style( self::PREFIX );
			wp_enqueue_script( self::PREFIX . '_js' );
		}
	}

	/**
	 * Load this view's assets in Day shortcode view.
	 */
	public function load_assets_in_day_view_shortcode() {
		wp_enqueue_style( self::PREFIX );
		wp_enqueue_script( self::PREFIX . '_js' );
	}

	/**
	 * Get the times of day as an array.
	 *
	 * @see \Tribe__Events__Filterbar__Filters__Time_Of_Day::get_values()
	 *
	 * @return array
	 */
	protected function get_time_of_day_ranges() {
		// TODO: sanitize_key()? for array keys to not break
		return [
			__( 'Morning', 'tribe-ext-schedule-day-view' )   => [
				6,
				7,
				8,
				9,
				10,
				11,
			],
			__( 'Afternoon', 'tribe-ext-schedule-day-view' ) => [
				12,
				13,
				14,
				15,
				16,
			],
			__( 'Evening', 'tribe-ext-schedule-day-view' )   => [
				17,
				18,
				19,
				20,
			],
			__( 'Night', 'tribe-ext-schedule-day-view' )     => [
				21,
				22,
				23,
				0,
				1,
				2,
				3,
				4,
				5,
			],
		];
	}

	public function get_all_day_text() {
		return esc_html__( 'All Day', 'tribe-ext-schedule-day-view' );
	}

	private function get_timeslot_event_count( $timeslot ) {
		global $wp_query;

		if ( empty( $wp_query->timeslot_counts[$timeslot] ) ) {
			$event_count = 0;
		} else {
			$event_count = $wp_query->timeslot_counts[$timeslot];
		}

		return absint( $event_count );
	}

	private function get_timeslot_title( $timeslot ) {
		$event_count = $this->get_timeslot_event_count( $timeslot );

		if ( 0 === $event_count ) {
			$event_count_text = sprintf( __( '(No %s)', 'tribe-ext-schedule-day-view' ), tribe_get_event_label_plural() );
		} elseif ( 1 === $event_count ) {
			$event_count_text = sprintf( __( '(%d %s)', 'tribe-ext-schedule-day-view' ), $event_count, tribe_get_event_label_singular() );
		} else {
			$event_count_text = sprintf( __( '(%d %s)', 'tribe-ext-schedule-day-view' ), $event_count, tribe_get_event_label_plural() );
		}

		return sprintf( esc_html__( '%s %s', 'tribe-ext-schedule-day-view' ), $timeslot, $event_count_text );
	}

	private function setup_loop() {
		add_action(
			'tribe_ext_sch_day_inside_before_loop', function () {
			global $wp_query;

			$wp_query->set( 'timeslots', $this->get_js_timeslots() );

			$timeslot_name = $this->timeslot_name();

			$all_timeslots    = array();
			$active_timeslots = array();

			foreach ( $wp_query->posts as &$post ) {
				if ( tribe_event_is_all_day( $post->ID ) ) {
					$post->$timeslot_name = $this->get_all_day_text();
				} else {
					$post->$timeslot_name = $this->get_non_all_day_timeslot_name( $post->ID );
				}

				$all_timeslots[] = $post->$timeslot_name;

				$post->is_active_on_load = $this->active( $post );

				if ( $post->is_active_on_load ) {
					$active_timeslots[] = $post->$timeslot_name;
				}
			}

			$wp_query->timeslot_counts = array_count_values( $all_timeslots );

			$wp_query->active_timeslots = array_unique( $active_timeslots );

			$wp_query->rewind_posts();
		}
		);
	}

	public function build_current_timeslot_args( $timeslot ) {
		global $wp_query;

		$args = array(
			'is_all_day_timeslot' => $timeslot === Tribe__Extension__Schedule_Day_View::instance()->get_all_day_text(),
			'is_active_on_load'   => in_array( $timeslot, $wp_query->active_timeslots ) ? true : false,
		);

		$args['class_group_active_events_on_load'] = $args['is_active_on_load'] ? ' tribe-events-day-grouping-event-is-active' : '';
		$args['aria_expanded_on_load']             = $args['is_active_on_load'] ? 'true' : 'false';
		$args['aria_hidden_on_load']               = $args['is_active_on_load'] ? 'false' : 'true';
		$args['timeslot_id']                       = $this->get_timeslot_id_from_timeslot( $timeslot );
		$args['timeslot_event_count']              = $this->get_timeslot_event_count( $timeslot );
		$args['timeslot_title']                    = $this->get_timeslot_title( $timeslot );
		$args['button_id']                         = $this->get_button_id_from_timeslot( $timeslot );
		$args['start_timestamp']                   = $this->get_timeslot_timestamp( $timeslot );
		$args['end_timestamp']                     = $this->get_timeslot_timestamp( $timeslot, false );

		$this->current_timeslot_args = $args;
	}

	public function get_timestamp( $post_id, $start_end = 'Start' ) {
		// We do it this way until \Tribe__Events__Timezones::event_start_timestamp() and end methods actually work by being TZ dependent instead of always interpreted as being in UTC
		$time = sprintf(
			'%s %s',
			get_post_meta( $post_id, "_Event{$start_end}Date", true ),
			Tribe__Events__Timezones::get_event_timezone_string( $post_id )
		);

		return strtotime( $time );
	}

	private function get_non_all_day_timeslot_name( $post_id ) {
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
	 *
	 * @see \Tribe__Events__Template__Day::header_attributes()
	 */
	private function get_today_midnight_timestamp() {
		global $wp_query;

		// 'start_date' is actually set to 12:00:01 (bug) so we don't use that query_var
		$current_day_midnight = sprintf(
			'%s 00:00:00 %s',
			$wp_query->get( 'eventDate' ),
			Tribe__Events__Timezones::wp_timezone_string()
		);

		return strtotime( $current_day_midnight );
	}

	/**
	 *
	 * @see \Tribe__Events__Template__Day::header_attributes()
	 * @see \Tribe__Extension__Schedule_Day_View::today() TODO: try to be more consistent between this and that.
	 */
	private function get_today_ymd() {
		return date( 'Y-m-d', $this->get_today_midnight_timestamp() );
	}

	private function get_js_timeslots() {
		$today_ymd = $this->get_today_ymd();

		$timeslot_timestamps = array();

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

			$timeslot_timestamps[$time_of_day] = [
				'start' => strtotime( $start_hour_string ),
				'end'   => strtotime( $end_hour_string ) - 1, // one second less than the start hour of the next range
			];
		}

		return $timeslot_timestamps;
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

	public function today() {
		global $wp_query;

		if ( $this->get_today_ymd() === $wp_query->get( 'eventDate' ) ) {
			return true;
		} else {
			return false;
		}
	}

	// to only be used in the loop
	public function active( $post ) {
		$timeslot_name = $this->timeslot_name();

		if ( ! $this->today() ) {
			return true;
		}

		if ( $this->get_all_day_text() === $post->$timeslot_name ) {
			return true;
		}

		$now = time();

		if (
			$now >= $this->get_timeslot_timestamp( $post->$timeslot_name )
			&& $now <= $this->get_timeslot_timestamp( $post->$timeslot_name, false )
		) {
			return true;
		}

		return false;
	}

	public function get_timeslot_timestamp( $timeslot = '', $start = true ) {
		global $wp_query;

		if (
			empty( $timeslot )
			|| $this->get_all_day_text() === $timeslot
		) {
			return '';
		}

		if ( $start ) {
			return $wp_query->get( 'timeslots' )[$timeslot]['start'];
		} else {
			return $wp_query->get( 'timeslots' )[$timeslot]['end'];
		}
	}

	private function get_timeslot_id_from_timeslot( $timeslot = '' ) {
		if ( ! empty( $timeslot ) ) {
			$timeslot = str_replace( ' ', '-', $timeslot ); // e.g. All Day becomes All-Day

			return 'tribe-events-day-time-slot-' . esc_attr( $timeslot );
		}
	}

	private function get_button_id_from_timeslot( $timeslot = '' ) {
		if ( ! empty( $timeslot ) ) {
			$timeslot = str_replace( ' ', '-', $timeslot ); // e.g. All Day becomes All-Day

			return 'timeslot-trigger-' . esc_attr( $timeslot );
		}
	}

	private function setup_plain_language_redirect() {
		add_filter(
			'query_vars', function ( $vars ) {
			return array_merge( $vars, [ 'eventDateModified' ], [ 'eventWeekModified' ], [ 'eventMonthModified' ] );
		}, 10, 1
		);

		$plain_language = [
			__( 'events/tomorrow', 'tribe-ext-schedule-day-view' )                => 'index.php?post_type=tribe_events&eventDateModified=1',
			__( 'events/yesterday', 'tribe-ext-schedule-day-view' )               => 'index.php?post_type=tribe_events&eventDateModified=-1',
			__( 'events/nextweek', 'tribe-ext-schedule-day-view' )                => 'index.php?post_type=tribe_events&eventWeekModified=1&eventDisplay=week',
			__( 'events/lastweek', 'tribe-ext-schedule-day-view' )                => 'index.php?post_type=tribe_events&eventWeekModified=-1&eventDisplay=week',
			__( 'events/nextmonth', 'tribe-ext-schedule-day-view' )               => 'index.php?post_type=tribe_events&eventMonthModified=1&eventDisplay=month',
			__( 'events/lastmonth', 'tribe-ext-schedule-day-view' )               => 'index.php?post_type=tribe_events&eventMonthModified=-1&eventDisplay=month',
			__( 'events/today', 'tribe-ext-schedule-day-view' ) . '/(\-?[0-9])/?' => 'index.php?post_type=tribe_events&eventDateModified=$matches[1]',
			__( 'events/week', 'tribe-ext-schedule-day-view' ) . '/(\-?[0-9])/?'  => 'index.php?post_type=tribe_events&eventWeekModified=$matches[1]&eventDisplay=week',
			__( 'events/month', 'tribe-ext-schedule-day-view' ) . '/(\-?[0-9])/?' => 'index.php?post_type=tribe_events&eventMonthModified=$matches[1]&eventDisplay=month',
		];

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
