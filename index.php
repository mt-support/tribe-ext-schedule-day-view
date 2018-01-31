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


	private function templates() {
		return
			[
				'day/single-event.php'    => 'views/day/single.php',
				'day/single-featured.php' => 'views/day/single.php',
				'day/loop.php'            => 'views/day/loop.php',
			];
	}


	public function construct() {
		$this->add_required_plugin( 'Tribe__Events__Main' );
		$this->set_url( 'https://theeventscalendar.com/extensions/schedule-day-view/' );
	}

	public function init() {
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
		$time_of_day_ranges = array(
			'allday' => __( 'All Day', 'tribe-ext-schedule-day-view' ),
			'06-12'  => __( 'Morning', 'tribe-ext-schedule-day-view' ),
			'12-17'  => __( 'Afternoon', 'tribe-ext-schedule-day-view' ),
			'17-21'  => __( 'Evening', 'tribe-ext-schedule-day-view' ),
			'21-06'  => __( 'Night', 'tribe-ext-schedule-day-view' ),
		);

		return $time_of_day_ranges;
	}

}
