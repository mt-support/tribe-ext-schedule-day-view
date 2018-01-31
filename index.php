<?php
/**
 * Plugin Name:     The Events Calendar Extension: Schedule Day View
 * Description:     Overrides The Events Calendar's Day View with a Schedule Day View, displaying events within All Day, Morning, Afternoon, and Evening contexts, as well as indicating events happening right now.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Example
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
class Tribe__Extension__Example extends Tribe__Extension {

	//!!! fatal !!! -- Arrays are not allowed in class constants
	/**
	 const SINGLE_TYPES = [
		'event',
		'featured',
	];
	 */

	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main' );
		$this->set_url( 'https://theeventscalendar.com/extensions/schedule-day-view/' );
	}

	public function init() {
		add_filter( 'tribe_events_template_day/loop.php', array( $this, 'override_day_loop_template' ) );
		add_filter( 'tribe_events_template_day/single.php', array( $this, 'override_day_single_template' ) );

/*		foreach ( self::SINGLE_TYPES as $type ) {
			//tribe_events_template_paths
			add_filter( 'tribe_get_template_part_day_single_' . $type, array( $this, 'filter_day_view' ), 10, 5 );
		}*/

	}

	public function get_our_template_dir() {
		$dir = trailingslashit( plugin_dir_path( __FILE__ ) ) . 'src/views/day/';

		return $dir;
	}

	public function override_day_loop_template( $file ) {
		$file = $this->get_our_template_dir() . 'loop.php';

		return $file;
	}

	public function override_day_single_event_template( $file ) {
		$file = $this->get_our_template_dir() . 'single.php';

		return $file;
	}

	/**
	 * Get the times of day as an array.
	 *
	 * @see \Tribe__Events__Filterbar__Filters__Time_Of_Day::get_values()
	 *
	 * @return array
	 */
	protected function get_times_of_day() {
		$time_of_day_array = array(
			'allday' => __( 'All Day', 'tribe-ext-schedule-day-view' ),
			'06-12'  => __( 'Morning', 'tribe-ext-schedule-day-view' ),
			'12-17'  => __( 'Afternoon', 'tribe-ext-schedule-day-view' ),
			'17-21'  => __( 'Evening', 'tribe-ext-schedule-day-view' ),
			'21-06'  => __( 'Night', 'tribe-ext-schedule-day-view' ),
		);

		$time_of_day_values = array();

		foreach ( $time_of_day_array as $value => $name ) {
			$time_of_day_values[] = array(
				'name'  => $name,
				'value' => $value,
			);
		}

		return $time_of_day_values;
	}

}
