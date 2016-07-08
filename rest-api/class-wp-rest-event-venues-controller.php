<?php

/**
 * Access terms associated with a taxonomy
 */
class WP_REST_Event_Venues_Controller extends WP_REST_Event_Terms_Controller {

	protected $taxonomy;

	protected function add_additional_fields_to_object( $data, $request ) {
		$data = parent::add_additional_fields_to_object( $data, $request );
		$venue_id = (int) $data['id'];
		$data = array_merge(
			$data,
			eo_get_venue_address( $venue_id ),
			array(
				'latitude'   => eo_get_venue_lat( $venue_id ),
				'longtitude' => eo_get_venue_lng( $venue_id ),
			)
		);
		return $data;
	}

	public function prepare_item_for_database( $request ) {
		$prepared_term = parent::prepare_item_for_database( $request );
		$schema = $this->get_item_schema();

		if ( isset( $request['address'] ) && ! empty( $schema['properties']['address'] ) ) {
			$prepared_term->address = $request['address'];
		}
		if ( isset( $request['city'] ) && ! empty( $schema['properties']['city'] ) ) {
			$prepared_term->city = $request['city'];
		}
		if ( isset( $request['state'] ) && ! empty( $schema['properties']['state'] ) ) {
			$prepared_term->state = $request['state'];
		}
		if ( isset( $request['postcode'] ) && ! empty( $schema['properties']['postcode'] ) ) {
			$prepared_term->postcode = $request['postcode'];
		}
		if ( isset( $request['country'] ) && ! empty( $schema['properties']['country'] ) ) {
			$prepared_term->country = $request['country'];
		}
		if ( isset( $request['latitude'] ) && ! empty( $schema['properties']['latitude'] ) ) {
			$prepared_term->latitude = $request['latitude'];
		}
		if ( isset( $request['longtitude'] ) && ! empty( $schema['properties']['longtitude'] ) ) {
			$prepared_term->longtitude = $request['longtitude'];
		}
		return $prepared_term;
	}

	/**
	 *
	 * TODO This means eo_insert_venue is not used when creating a venue. Does this matter?
	 */
	public function update_additional_fields_for_object( $term, $request ) {
		parent::update_additional_fields_for_object( $term, $request );
		$prepared_term = $this->prepare_item_for_database( $request );
		eo_update_venue( $term->term_id, (array) $prepared_term );
	}

	/**
	 * Delete a single term from a taxonomy
	 *
	 * @param WP_REST_Request $request Full details about the request
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$force = isset( $request['force'] ) ? (bool) $request['force'] : false;
		// We don't support trashing for this type, error out
		if ( ! $force ) {
			return new WP_Error( 'rest_trash_not_supported', __( 'Resource does not support trashing.' ), array( 'status' => 501 ) );
		}
		$term = get_term( (int) $request['id'], $this->taxonomy );
		$request->set_param( 'context', 'view' );
		$response = $this->prepare_item_for_response( $term, $request );
		$retval = eo_delete_venue( $term->term_id );
		if ( ! $retval ) {
			return new WP_Error( 'rest_cannot_delete', __( 'The resource cannot be deleted.' ), array( 'status' => 500 ) );
		}
		/**
		 * Fires after a single term is deleted via the REST API.
		 *
		 * @param WP_Term          $term     The deleted term.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( "rest_delete_{$this->taxonomy}", $term, $response, $request );
		return $response;
	}

	protected function add_additional_fields_schema( $schema ) {
		$schema = parent::add_additional_fields_schema( $schema );
		$schema['properties'] = array_merge( $schema['properties'], array(
			'address'       => array(
				'description'  => __( 'Building number and street address of venue.' ),
				'type'         => 'string',
				'context'      => array( 'view', 'edit' ),
				'arg_options'  => array(
					'sanitize_callback' => 'wp_filter_post_kses',
				),
			),
			'city'      => array(
				'description'  => __( 'City of the venue.' ),
				'type'         => 'string',
				'context'      => array( 'view', 'edit' ),
				'arg_options'  => array(
					'sanitize_callback' => 'wp_filter_post_kses',
				),
			),
			'state'      => array(
				'description'  => __( 'State or provnce of the venue.' ),
				'type'         => 'string',
				'context'      => array( 'view', 'edit' ),
				'arg_options'  => array(
					'sanitize_callback' => 'wp_filter_post_kses',
				),
			),
			'postcode'      => array(
				'description'  => __( 'Postal / ZIP code of the venue.' ),
				'type'         => 'string',
				'context'      => array( 'view', 'edit' ),
				'arg_options'  => array(
					'sanitize_callback' => 'wp_filter_post_kses',
				),
			),
			'country'      => array(
				'description'  => __( 'Country of the venue.' ),
				'type'         => 'string',
				'context'      => array( 'view', 'edit' ),
				'arg_options'  => array(
					'sanitize_callback' => 'wp_filter_post_kses',
				),
			),
			'latitude'      => array(
				'description'  => __( 'Latitude co-ordinate of the venue.' ),
				'type'         => 'string',
				'context'      => array( 'view', 'edit' ),
				'arg_options'  => array(
					'sanitize_callback' => 'wp_filter_post_kses',
				),
			),
			'longtitude'      => array(
				'description'  => __( 'Longtitude co-ordinate of the venue.' ),
				'type'         => 'string',
				'context'      => array( 'view', 'edit' ),
				'arg_options'  => array(
					'sanitize_callback' => 'wp_filter_post_kses',
				),
			),
		) );
		return $schema;
	}
}
