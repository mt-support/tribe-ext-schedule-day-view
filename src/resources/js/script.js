/**
 * @file This file contains all JavaScript specific to Schedule Day View.
 * This file should load after all vendor and core events JavaScript.
 * @version 1.0.0
 */

(function ( window, document, $, td, te, tf, ts, tt, config, dbug ) {

	/*
	 * $    = jQuery
	 * td   = tribe_ev.data
	 * te   = tribe_ev.events
	 * tf   = tribe_ev.fn
	 * ts   = tribe_ev.state
	 * tt   = tribe_ev.tests
	 * dbug = tribe_debug
	 */

	// @ifdef DEBUG
	if ( dbug ) {

	}
	// @endif

	$( document ).ready( function () {
		var $container = $( '#tribe-events-day' ),
			tribe_timezone = $container.data( 'tribe-timezone' ),
			tribe_time_on_load = $container.data( 'tribe-now' );


		/**
		 * @function
		 * @desc Handles toggles for event groups
		 */

		$( '#tribe-events' ).on( 'click', '.tribe-events-day-group-trigger:enabled', function ( e ) {
			var $target = $( e.target ),
				$parent = $target.closest( '.tribe-events-day-time-slot' ),
				$content = $parent.find( '.post-tribe-events-day-group-event' );

			e.preventDefault();

			if ( $parent.is( '.tribe-events-day-grouping-is-active' ) ) {
				$parent.removeClass( 'tribe-events-day-grouping-is-active' );
				$target.attr( 'aria-expanded', false );
				$content.attr( 'aria-hidden', true );
			} else {
				$parent.addClass( 'tribe-events-day-grouping-is-active' );
				$target.attr( 'aria-expanded', true );
				$content.attr( 'aria-hidden', false );
			}
		} );


		// @ifdef DEBUG
		dbug && debug.info( 'TEC Debug: tribe events schedule day view successfully loaded' );
		ts.view && dbug && debug.timeEnd( 'Tribe JS Init Timer' );
		// @endif

	} );

})( window, document, jQuery, tribe_ev.data, tribe_ev.events, tribe_ev.fn, tribe_ev.state, tribe_ev.tests, tribe_js_config, tribe_debug );