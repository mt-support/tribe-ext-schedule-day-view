<?php
/**
 * Plugin Name:     The Events Calendar Extension: Schedule Day View
 * Description:     Overrides The Events Calendar's Day View with a Schedule Day View, displaying events within All Day, Morning, Afternoon, Evening, and Night time slots, as well as indicating events happening right now.
 * Version:         1.0.0
 * Extension Class: Tribe__Extension__Schedule_Day_View
 * Author:          Modern Tribe, Inc.
 * Author URI:      http://m.tri.be/1971
 * License:         GPL version 3 or any later version
 * License URI:     https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:     tribe-ext-schedule-day-view
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

// Do not load unless Tribe Common is fully loaded and our class does not yet exist.
if (
	class_exists( 'Tribe__Extension' )
	&& ! class_exists( 'Tribe__Extension__Schedule_Day_View' )
) {
	/**
	 * Extension main class, class begins loading on init() function.
	 */
	class Tribe__Extension__Schedule_Day_View extends Tribe__Extension {

		/**
		 * The prefix used for settings or whatever else needs to be namespaced.
		 */
		const PREFIX = 'tribe_ext_sch_day_view';

		/**
		 * Time slot name used within each WP_Post.
		 *
		 * @see Tribe__Extension__Schedule_Day_View::PREFIX
		 *
		 * @return string
		 */
		public function time_slot_name() {
			return self::PREFIX . '_time_slot';
		}

		/**
		 * The current time slot's array of arguments. Used in the loop template
		 * file per time slot.
		 *
		 * @var array
		 */
		public $current_time_slot_args = array();

		/**
		 * The list of The Events Calendar's template files to override with
		 * which of this plugin's template files.
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
		 * Set the minimum required version of The Events Calendar
		 * and this extension's URL.
		 */
		public function construct() {
			// Tribe__Assets::maybe_get_min_file() requires v4.3
			$this->add_required_plugin( 'Tribe__Events__Main', '4.3' );
			$this->set_url( 'https://theeventscalendar.com/extensions/schedule-day-view/' );
		}

		/**
		 * Extension initialization and hooks.
		 */
		public function init() {
			/**
			 * Protect against fatals by specifying the required minimum PHP
			 * version. Make sure to match the readme.txt header.
			 *
			 * @link https://secure.php.net/manual/en/migration54.new-features.php
			 * 5.4: Traits, Short Array Syntax, and $this within Closures
			 */
			$php_required_version = '5.4';

			if ( version_compare( PHP_VERSION, $php_required_version, '<' ) ) {
				if (
					is_admin()
					&& current_user_can( 'activate_plugins' )
				) {
					$message = '<p>' . $this->get_name() . ' ';

					$message .= sprintf( __( 'requires PHP version %s or newer to work. Please contact your website host and inquire about updating PHP.', 'tribe-ext-schedule-day-view' ), $php_required_version );

					$message .= sprintf( ' <a href="%1$s">%1$s</a>', 'https://wordpress.org/about/requirements/' );

					$message .= '</p>';

					tribe_notice( $this->get_name(), $message, 'type=error' );
				}

				return;
			}

			add_action( 'init', array( $this, 'register_assets' ) );

			add_action( 'wp_enqueue_scripts', array( $this, 'load_assets_everywhere_except_event_archive_that_isnt_day_view' ) );

			add_action( 'tribe_events_pro_tribe_events_shortcode_prepare_day', array( $this, 'load_assets_in_day_view_shortcode' ) );

			/**
			 * Load as much as possible after $wp_query is set so we can support
			 * displaying Schedule Day View for today only (via filter), which
			 * we can only do after `$wp_query->get( 'eventDate' )` is set.
			 *
			 * We need to use the `tribe_pre_get_view` hook so that
			 * tribe_is_day() is true as well as being able to detect the date
			 * it is displaying even within/after Ajax.
			 */
			add_action( 'tribe_pre_get_view', array( $this, 'setup_for_day_view_archive' ) );

			add_action( 'tribe_events_pro_tribe_events_shortcode_prepare_day', array( $this, 'setup_for_day_view_shortcode' ) );
		}

		/**
		 * Load this plugin's assets on all page loads if PRO is active except
		 * event archives that are not day view.
		 */
		public function load_assets_everywhere_except_event_archive_that_isnt_day_view() {
			$should_enqueue = false;

			if (
				class_exists( 'Tribe__Events__Pro__Shortcodes__Tribe_Events__Day' )
				|| tribe_is_day() // not true for shortcode views
			) {
				$should_enqueue = true;
			}

			if ( true === $should_enqueue ) {
				wp_enqueue_style( self::PREFIX );
				wp_enqueue_script( self::PREFIX . '_js' );
			}

			// Only add body class to day view archive, not all pages.
			if ( tribe_is_day() ) {
				add_filter( 'body_class', array( $this, 'add_archive_body_class' ) );
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
		 * Should Schedule Day View load every day or just today.
		 *
		 * @return bool
		 */
		private function load_only_if_today() {
			/**
			 * If false (default), Schedule Day View will display for every
			 * day's Day View. If true, the normal Day View will display for
			 * every day and Schedule Day View will only take over for Today's
			 * Day View.
			 *
			 * @param bool $schedule_day_view_only_if_today
			 */
			return (bool) apply_filters( self::PREFIX . '_only_if_today', false );
		}

		/**
		 * Loading logic for Schedule Day View event archive.
		 */
		public function setup_for_day_view_archive() {
			if ( ! tribe_is_day() ) {
				return;
			}

			$schedule_day_view_only_if_today = $this->load_only_if_today();

			if (
				true !== $schedule_day_view_only_if_today
				|| (
					true === $schedule_day_view_only_if_today
					&& $this->today()
				)
			) {
				$this->common_setup();
			}
		}

		/**
		 * Loading logic for PRO's day view shortcode.
		 */
		public function setup_for_day_view_shortcode() {
			$schedule_day_view_only_if_today = $this->load_only_if_today();

			if (
				true !== $schedule_day_view_only_if_today
				|| (
					true === $schedule_day_view_only_if_today
					&& $this->today()
				)
			) {
				$this->common_setup();

				add_filter( 'tribe_events_pro_tribe_events_shortcode_wrapper_classes', array( $this, 'add_shortcode_container_classes' ) );
			}
		}

		/**
		 * Do the things common to rendering both event archive and shortcode.
		 */
		private function common_setup() {
			$this->setup_templates();
			$this->setup_loop();
			$this->display_cleanup();

			add_filter( 'tribe_get_events_title', array( $this, 'set_todays_day_view_title' ) );
		}

		/**
		 * Add our HTML class to <body>.
		 *
		 * @see \Tribe__Extension__Schedule_Day_View::PREFIX
		 *
		 * @param $classes
		 *
		 * @return array
		 */
		public function add_archive_body_class( $classes ) {
			$classes[] = self::PREFIX;

			$classes = array_merge( $classes, $this->array_of_container_classes_based_on_today() );

			return $classes;
		}

		/**
		 * Add our HTML classes to the shortcode's container.
		 *
		 * @see \Tribe__Extension__Schedule_Day_View::PREFIX
		 *
		 * @param $classes
		 *
		 * @return array
		 */
		public function add_shortcode_container_classes( $classes ) {
			$classes[] = self::PREFIX;

			$classes = array_merge( $classes, $this->array_of_container_classes_based_on_today() );

			return $classes;
		}

		/**
		 * Create an array of HTML classes based on whether or not we are
		 * viewing Today in day view.
		 *
		 * @return array
		 */
		public function array_of_container_classes_based_on_today() {
			$classes_if_today = array( 'tribe-events-day-is-today' );

			/**
			 * Array of classes if Today.
			 *
			 * @param array $classes_if_today
			 */
			$classes_if_today = (array) apply_filters( self::PREFIX . '_classes_if_today', $classes_if_today );

			$classes_if_not_today = array(
				'tribe-events-day-grouping-is-active',
				'tribe-events-loop-day-not-today',
			);

			/**
			 * Array of classes if not Today.
			 *
			 * @param array $classes_if_not_today
			 */
			$classes_if_not_today = (array) apply_filters( self::PREFIX . '_classes_if_not_today', $classes_if_not_today );

			if ( $this->today() ) {
				$classes = $classes_if_today;
			} else {
				$classes = $classes_if_not_today;
			}

			return $classes;
		}

		/**
		 * Build a space-separated string of escaped classes from an array of
		 * HTML classes.
		 *
		 * Used in the src/views/day/loop.php template.
		 *
		 * @param array $array_of_container_classes
		 *
		 * @return string
		 */
		public function array_of_container_classes_based_on_today_to_string() {
			$output = '';

			foreach ( $this->array_of_container_classes_based_on_today() as $class ) {
				$output .= sprintf( ' %s', esc_attr( $class ) );
			}

			return $output;
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

			// We need to load after this not just because it sounds like the correct one but because if we're too early PRO's Day View Shortcode will throw a JS error. Basically, this is correct and we need to be correct here.
			wp_register_script( self::PREFIX . '_js', $js, array( 'tribe-events-ajax-day' ), $this->get_version(), true );
		}

		/**
		 * Get the time slots (except for All Day) and the integer hours they
		 * include as a multidimensional array.
		 *
		 * Note that the hours do not exactly match the *setup* of the Time of
		 * Day filter's get_values(), but this is setup in a way to match its
		 * actual *results* when using this filter.
		 * -1 is only ever manually set for an event having a start time prior
		 * to this day.
		 *
		 * @see \Tribe__Events__Filterbar__Filters__Time_Of_Day::get_values()
		 *
		 * @return array
		 */
		protected function get_time_of_day_ranges() {
			return array(
				__( 'Morning', 'tribe-ext-schedule-day-view' )   => array(
					-1,
					0,
					1,
					2,
					3,
					4,
					5,
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
				),
			);
		}

		/**
		 * Set day view archive's title to be "Today's Event" if just 1 event,
		 * else "Today's Events".
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
		 * Used to compare/determine if we are in the All Day time slot, which
		 * is why we need to have this as a method instead, to keep things DRY.
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

			if ( empty( $wp_query->time_slot_counts[ $time_slot ] ) ) {
				$event_count = 0;
			} else {
				$event_count = $wp_query->time_slot_counts[ $time_slot ];
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
			add_action( self::PREFIX . '_inside_before_loop', function () {
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

				$all_time_slots = array_filter( $all_time_slots );

				$wp_query->time_slot_counts = array_count_values( $all_time_slots );

				$active_time_slots = array_filter( $active_time_slots );

				$wp_query->active_time_slots = array_unique( $active_time_slots );

				$wp_query->rewind_posts();
			} );
		}

		/**
		 * Build the data for a given time slot.
		 *
		 * Used in src/views/day/loop.php
		 *
		 * @param $time_slot
		 */
		public function build_current_time_slot_args( $time_slot ) {
			global $wp_query;

			$args = array(
				'is_all_day_time_slot' => $time_slot === $this->get_all_day_text(),
				'is_active_on_load'    => in_array( $time_slot, $wp_query->active_time_slots ) ? true : false,
			);

			$args['class_group_active_events_on_load'] = $args['is_active_on_load'] ? ' tribe-events-day-grouping-event-is-active' : '';
			$args['aria_expanded_on_load']             = $args['is_active_on_load'] ? 'true' : 'false';
			$args['aria_hidden_on_load']               = $args['is_active_on_load'] ? 'false' : 'true';
			$args['time_slot_id']                      = $this->get_time_slot_id_from_time_slot( $time_slot );
			$args['time_slot_event_count']             = $this->get_time_slot_event_count( $time_slot );
			$args['time_slot_title']                   = $this->get_time_slot_title( $time_slot );
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
			// We do it this way until \Tribe__Events__Timezones::event_start_timestamp() and end methods actually work by being timezone-dependent instead of always interpreted as being in UTC.
			$time = sprintf(
				'%s %s',
				get_post_meta( $post_id, "_Event{$start_end}Date", true ),
				Tribe__Events__Timezones::get_event_timezone_string( $post_id )
			);

			return strtotime( $time );
		}

		/**
		 * Get the front-end time slot name of a given time slot for events
		 * without the "All Day" box checked (i.e. start and end times are set).
		 *
		 * If an event started a previous day (yesterday or prior) but is still
		 * happening ("has not yet ended", nothing to do with recurrence):
		 * A) Make it appear as an All Day event if its end time is beyond today
		 * B) Or make its hour = -1 (not a real hour number), which will show up
		 * in the Morning time slot
		 * C) Else get the hour number (e.g. `23` for 11pm)
		 * Most events will fall into the "C" scenario.
		 * If an event falls into scenario "A" or "B", its WP_Post
		 * `$post->timeslot` (from TEC's Day View) will actually be the
		 * translated "Ongoing" text, set from
		 * Tribe__Events__Template__Day::setup_view().
		 *
		 * @param $post_id
		 *
		 * @return string
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

			$event_start_timestamp = $this->get_timestamp( $post_id );
			$event_end_timestamp   = $this->get_timestamp( $post_id, 'End' );

			$day_view_ymd = get_query_var( 'eventDate' );

			$day_view_midnight_timestamp   = strtotime( sprintf( '%s 00:00:00', $day_view_ymd ) );
			$day_view_end_of_day_timestamp = strtotime( sprintf( '%s 23:59:59', $day_view_ymd ) );

			if ( $day_view_midnight_timestamp > $event_start_timestamp ) {
				if ( $day_view_end_of_day_timestamp < $event_end_timestamp ) {
					return $this->get_all_day_text();
				} else {
					$hour = - 1;
				}
			} else {
				$hour = date( 'G', $event_start_timestamp );
			}

			// set back to what date_default_timezone_get() was
			date_default_timezone_set( $existing_timezone );

			foreach ( $this->get_time_of_day_ranges() as $time_of_day => $hours ) {
				if ( in_array( $hour, $hours ) ) {
					return (string) $time_of_day;
				}
			}
		}

		/**
		 * Get the timestamp of "right now" in the timezone from WordPress settings.
		 *
		 * Note: `current_time( 'timestamp' )` does not work for our purposes.
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
		 * Get today's date in the format of 'Y-m-d' (e.g. 2018-03-01 for March 1).
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
		 * minimalized view, such as recurrence tooltip and excess venue info.
		 */
		private function display_cleanup() {
			add_filter( 'tribe_events_recurrence_tooltip', function ( $tooltip ) {
				return '';
			} );

			add_filter( 'tribe_get_venue_details', function ( $venue_details ) {
				unset( $venue_details['address'] );

				return $venue_details;
			} );
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
					$end_hour = 24 + 5;
				}
				$end_hour        += 1; // We actually need the start hour of the next range
				$end_hour_string = sprintf( '%s %s +%d hours', $today_ymd, Tribe__Events__Timezones::wp_timezone_string(), $end_hour );

				$time_slot_timestamps[ $time_of_day ] = array(
					'start' => strtotime( $start_hour_string ),
					'end'   => strtotime( $end_hour_string ) - 1,
					// one second less than the start hour of the next range
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

			$wp_query_time_slots = $wp_query->get( 'time_slots' );

			if (
				empty( $time_slot )
				|| $this->get_all_day_text() === $time_slot
			) {
				return '';
			}

			if ( $start ) {

				return $wp_query_time_slots[ $time_slot ]['start'];
			} else {
				return $wp_query_time_slots[ $time_slot ]['end'];
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
			} else {
				return '';
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
			} else {
				return '';
			}
		}
	} // end class
} // end if class_exists check