<?php

/**
 * Plugin Name: Event Organiser Rest API
 * Plugin URI: http://www.wp-event-organiser.com
 * Version: 0.1.0
 * Author: Stephen Harris
 */

add_action( 'plugins_loaded', function() {

	if ( ! class_exists( 'WP_REST_Posts_Controller' ) ) {
		add_action( 'admin_notices', 'eventorganiser_restapi_not_active_notice' );
		return;
	}

	/****** Rest API ******/
	require_once( dirname( __FILE__ ) . '/rest-api/class-wp-rest-events-controller.php' );
	require_once( dirname( __FILE__ ) . '/rest-api/class-wp-rest-event-occurrences-controller.php' );
	require_once( dirname( __FILE__ ) . '/rest-api/class-wp-rest-event-terms-controller.php' );
	require_once( dirname( __FILE__ ) . '/rest-api/class-wp-rest-event-venues-controller.php' );
	require_once( dirname( __FILE__ ) . '/rest-api/class-wp-rest-event-categories-controller.php' );
	require_once( dirname( __FILE__ ) . '/rest-api/class-wp-rest-event-tags-controller.php' );

	add_action( 'rest_api_init', 'eventorganiser_init_restapi', 50 );

} );


function eventorganiser_restapi_not_active_notice() {
	?>
	<div class="notice-error notice is-dismissible">
		<p>Event Organiser Rest API requires the <a href="https://wordpress.org/plugins/rest-api/">Rest API plugin</a> to be active</p>
	</div>
	<?php
}

function eventorganiser_init_restapi(){
	$controller = new WP_REST_Event_Occurrences_Controller();
	$controller->register_routes();
}

add_filter( 'eventorganiser_event_properties', function( $args ) {
	$args = array_merge( array(
		'rest_base'             => 'events',
		'show_in_rest'          => true,
		'rest_controller_class' => 'WP_REST_Events_Controller',
	), $args ); 
	return $args;
} );

add_filter( 'eventorganiser_register_taxonomy_event-category', function( $args ) {
	$args = array_merge( array(
		'show_in_rest'          => true,
		'rest_base'             => 'event-categories',
		'rest_controller_class' => 'WP_REST_Event_Categories_Controller',
	), $args ); 
	return $args;
} );

add_filter( 'eventorganiser_register_taxonomy_event-tag', function( $args ) {
	$args = array_merge( array(
		'show_in_rest'          => true,
		'rest_base'             => 'event-tags',
		'rest_controller_class' => 'WP_REST_Event_Tags_Controller',
	), $args ); 
	return $args;
} );

add_filter( 'eventorganiser_register_taxonomy_event-venue', function( $args ) {
	$args = array_merge( array(
		'show_in_rest'          => true,
		'rest_base'             => 'event-venues',
		'rest_controller_class' => 'WP_REST_Event_Venues_Controller',
	), $args ); 
	return $args;
} );





