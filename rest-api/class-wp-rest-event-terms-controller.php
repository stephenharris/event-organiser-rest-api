<?php

/**
 * Access terms associated with a taxonomy
 */
class WP_REST_Event_Terms_Controller extends WP_REST_Terms_Controller {

	public function __construct( $taxonomy ) {
		parent::__construct( $taxonomy );
		add_filter( "rest_{$this->taxonomy}_query", array( $this, '_prepare_args' ), 10, 2 );
	}

	/**
	 * Handles support for the 'event' parameter by mapping it to the Rest API's
	 * 'post' parameter. 'post' becomes an alias for the more semantically correct#
	 * 'event' attribute
	 * @access private
	 */
	public function _prepare_args( $prepared_args, $request ) {
		if ( isset( $request['event'] ) ) {
			$prepared_args['post'] = $request['event'];
		}
		return $prepared_args;
	}

	/**
	 * Adds our 'event' parameter, and moves the 'post' paramter to the bottom
	 * and changes the description.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		$query_params = parent::get_collection_params();
		$post = isset( $query_params['post'] ) ? $query_params['post'] : null;
		unset( $query_params['post'] );
		$query_params['event'] = array(
			'description'           => __( 'Limit result set to resources assigned to a specific event.' ),
			'type'                  => 'integer',
			'default'               => null,
			'validate_callback'     => 'rest_validate_request_arg',
		);
		if ( ! is_null( $post ) ) {
			$query_params['post'] = array_merge( $post, array(
				'description' => __( 'Limit result set to resources assigned to a specific event. Alias of event.' ),
			) );
		}
		return $query_params;
	}
}
