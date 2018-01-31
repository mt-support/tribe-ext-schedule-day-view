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
		add_action( 'tribe_events_pro_tribe_events_shortcode_prepare_day', array( $this, 'load_assets_in_day_view_shortcode' ) );
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
		add_action(
			'tribe_ext_sch_day_inside_before_loop', function () {
			global $wp_query;

			foreach ( $wp_query->posts as &$post ) {
				if ( tribe_event_is_all_day( $post->ID ) ) {
					$post->timeslot = __( 'All Day', 'tribe-ext-schedule-day-view' );
				} else {
					$post->timeslot = $this->get_timeslot( $post->timeslot );
				}
				$post->timeslots = $this->get_js_timeslots( $post->timeslot, $post->ID );
				$post->is_active_on_load = $this->active( [ 'all_day' => ( 'All Day' == $post->timeslot ), 'timeslots' => $post->timeslots, 'group_name' => $post->timeslot ] );
				if ( $post->is_active_on_load ) {
					$active_timeslots[] = $post->timeslot;
				}
			}

			$wp_query->active_timeslots = array_unique( $active_timeslots );

			$wp_query->rewind_posts();

		}
		);
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

	private function get_js_timeslots( $timeslot, $id ) {

		if ( array_key_exists( $timeslot, $this->get_time_of_day_ranges() ) ) {
			$start = sprintf(
				'%s %s',
				get_post_meta( $id, "_EventStartDate", true ),
				get_post_meta( $id, "_EventTimezone", true )
			);
			$end = sprintf(
				'%s %s',
				get_post_meta( $id, "_EventEndDate", true ),
				get_post_meta( $id, "_EventTimezone", true )
			);

			sleep(0);
			return [
				'start' => strtotime( $start ),
				'end'   => strtotime( $end ),
			];
		}
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

	public static function today() {
		return get_query_var( 'eventDate' ) == date( 'Y-m-d', time() );
	}

	public function active( $args ) {
		if ( ! self::today() ) {
			return true;
		}

		if ( $args['all_day'] ) {
			return true;
		}

		if ( time() >= $args['timeslots']['start'] && time()< $args['timeslots']['end']) {
			return true;
		}

		return false;
	}

}
