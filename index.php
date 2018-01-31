<?php
/**
 * Plugin Name:     The Events Calendar Extension: Schedule Day View
 * Description:     Overrides The Events Calendar's Day View with a Schedule Day View, displaying events within All
 * Day, Morning, Afternoon, and Evening contexts, as well as indicating events happening right now. Version: 1.0.0
 * Extension Class: Tribe__Extension__Schedule_Day_View
 * Author:          Modern Tribe, Inc. Author URI:
 * http://m.tri.be/1971 License:         GPL version 3 or any later version License URI:
 * https://www.gnu.org/licenses/gpl-3.0.html
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
		$this->add_required_plugin( 'Tribe__Events__Main' );
		$this->set_url( 'https://theeventscalendar.com/extensions/schedule-day-view/' );
	}

	public function init() {
		$this->setup_templates();
		$this->setup_loop();
		$this->setup_plain_language_redirect();
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
	 * Get the times of day as an array.
	 *
	 * @see \Tribe__Events__Filterbar__Filters__Time_Of_Day::get_values()
	 *
	 * @return array
	 */
	protected function get_time_of_day_ranges() {
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

	private function setup_loop() {
		add_action( 'tribe_ext_sch_day_inside_before_loop', function () {
			global $wp_query;

			foreach ( $wp_query->posts as &$post ) {
				$post->timeslot  = $this->get_timeslot( $post->timeslot );
				$post->timeslots = $this->get_js_timeslots();
			}
		} );
	}

	private function get_timeslot( $timeslot ) {
		$hour = date( 'G', strtotime( $timeslot ) );

		foreach ( $this->get_time_of_day_ranges() as $time_of_day => $hours ) {
			if ( in_array( $hour, $hours ) ) {
				return $time_of_day;
			}
		}

		return $timeslot;
	}

	private function get_js_timeslots() {
		return [
			'start' => tribe_get_start_date( get_the_ID(), true, 'U' ),
			'end'   => tribe_get_end_date( get_the_ID(), true, 'U' ),
		];
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

	private function setup_plain_language_redirect() {

		add_filter( 'query_vars', function ( $vars ) {
			return array_merge( $vars, [ 'eventDateModified' ], [ 'eventWeekModified' ], [ 'eventMonthModified' ] );
		}, 10, 1 );

		$plane_language = [
			__( 'events/tomorrow', 'tribe-ext-schedule-day-view' )                => 'index.php?post_type=tribe_events&eventDateModified=1',
			__( 'events/yesterday', 'tribe-ext-schedule-day-view' )               => 'index.php?post_type=tribe_events&eventDateModified=-1',
			__( 'events/nextweek', 'tribe-ext-schedule-day-view' )                => 'index.php?post_type=tribe_events&eventWeekModified=1&eventDisplay=week',
			__( 'events/lastweek', 'tribe-ext-schedule-day-view' )                => 'index.php?post_type=tribe_events&eventWeekModified=-1&eventDisplay=week',
			__( 'events/nextmonth', 'tribe-ext-schedule-day-view' )                => 'index.php?post_type=tribe_events&eventMonthModified=1&eventDisplay=month',
			__( 'events/lastmonth', 'tribe-ext-schedule-day-view' )                => 'index.php?post_type=tribe_events&eventMonthModified=-1&eventDisplay=month',
			__( 'events/today', 'tribe-ext-schedule-day-view' ) . '/(\-?[0-9])/?' => 'index.php?post_type=tribe_events&eventDateModified=$matches[1]',
			__( 'events/week', 'tribe-ext-schedule-day-view' ) . '/(\-?[0-9])/?'  => 'index.php?post_type=tribe_events&eventWeekModified=$matches[1]&eventDisplay=week',
			__( 'events/month', 'tribe-ext-schedule-day-view' ) . '/(\-?[0-9])/?'  => 'index.php?post_type=tribe_events&eventMonthModified=$matches[1]&eventDisplay=month',
		];

		add_filter( 'rewrite_rules_array', function ( $rules ) use ( $plane_language ) {
			$new_rules = array_merge(
				$plane_language,
				$rules
			);

			return $new_rules;
		}, 10, 1 );

		add_filter( 'pre_get_posts', function ( $query ) {
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
		} );
	}

}
