/**
 * @file This file contains all schedule day view specific javascript.
 * This file should load after all vendors and core events javascript.
 * @version 1.0
 */

(function( window, document, $, td, te, tf, ts, tt, config, dbug ) {

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

	$( document ).ready( function() {

		var $container = $( '#tribe-events-day' ),
			tribe_timezone = $container.data( 'timezone' ),
			tribe_time_on_load = $container.data( 'current_time_on_load' );

		// for ajax pagination?
		// finish on load
		// styles / refinement
		// stretch goals
		// bring JS closer in line

		/**
		 * @function
		 * @desc Handles toggles for event groups
		 */

		$( '#tribe-events' ).on( 'click', '.tribe-events-day-group-trigger',function( e ) {
			e.preventDefault();
			$(e.target).closest('.tribe-events-day-time-slot').toggleClass('tribe-events-day-grouping-is-active');
		});


		// @ifdef DEBUG
		dbug && debug.info( 'TEC Debug: tribe events schedule day view successfully loaded' );
		ts.view && dbug && debug.timeEnd( 'Tribe JS Init Timer' );
		// @endif

	} );

})( window, document, jQuery, tribe_ev.data, tribe_ev.events, tribe_ev.fn, tribe_ev.state, tribe_ev.tests, tribe_js_config, tribe_debug );
