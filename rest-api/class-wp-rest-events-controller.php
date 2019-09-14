<?php
class WP_REST_Events_Controller extends WP_REST_Posts_Controller {

	private $schedule_fields = array(
		'start', 'end', 'schedule', 'schedule_meta', 'frequency', 'all_day',
		'until', 'schedule_last', 'include', 'exclude', 'occurs_by', 'number_occurrences'
	);

	function __construct() {
		parent::__construct( 'event' );
		//Populate event schedule fields when creating an event
		add_filter( 'rest_pre_insert_event', array( $this, '_add_event_fields_for_database' ), 10, 2 );
		//Populate occurrence / schedule data for response
		add_filter( 'rest_prepare_event', array( $this, '_add_event_fields_for_response' ), 10, 3 );

		add_filter( 'rest_event_query', array( $this, '_set_custom_request_fields_to_event_query' ), 10, 2 );
	}

	public function _set_custom_request_fields_to_event_query( $args, $request ) {
		if( isset( $request['event_start_before'] ) ) {
			$args['event_start_before'] = $request['event_start_before'];
		}
		if( isset( $request['event_start_after'] ) ) {
			$args['event_start_after'] = $request['event_start_after'];
		}
		if( isset( $request['event_end_before'] ) ) {
			$args['event_end_before'] = $request['event_end_before'];
		}
		if( isset( $request['event_end_after'] ) ) {
			$args['event_end_after'] = $request['event_end_after'];
		}
		return $args;
	}

	public function get_collection_params() {
		$orignal_params = parent::get_collection_params();
		$params         = array();

		//Remove unnecessary/ambigeous parameters
		unset( $orignal_params['after'] );
		unset( $orignal_params['before'] );

		$orignal_params['orderby']['enum'][]  = 'eventstart';
		$orignal_params['orderby']['enum'][]  = 'eventend';
		$orignal_params['orderby']['default'] = 'eventstart';
		$orignal_params['order']['default'] = 'asc';

		$params['event_start_after'] = array(
			'description'        => __( 'Limit response to events starting after a given ISO8601 compliant date or relative datetime string. (inclusive)' ),
			'type'               => 'string',
			'format'             => 'date-time',
			//'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['event_start_before'] = array(
			'description'        => __( 'Limit response to events starting before a given ISO8601 compliant date or relative datetime string. (inclusive)' ),
			'type'               => 'string',
			'format'             => 'date-time',
			//'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['event_end_after'] = array(
			'description'        => __( 'Limit response to events finishing after a given ISO8601 compliant date or relative datetime string. (inclusive)' ),
			'type'               => 'string',
			'format'             => 'date-time',
			//'validate_callback'  => 'rest_validate_request_arg',
		);
		$params['event_end_before'] = array(
			'description'        => __( 'Limit response to events finishing before a given ISO8601 compliant date or relative datetime string. (inclusive)' ),
			'type'               => 'string',
			'format'             => 'date-time',
			//'validate_callback'  => 'rest_validate_request_arg',
		);
		return array_merge( $params, $orignal_params );
	}

	public function _add_event_fields_for_response( $response, $post, $request ) {
		$data     = $response->get_data();
		$schema   = $this->get_item_schema();

		//Remove ambigeous data
		unset( $data['date'] );
		unset( $data['date_gmt'] );

		//Add occurrence data
		if ( ! empty( $post->occurrence_id ) ) {
			$data['occurrence_id'] = $post->occurrence_id;
			$data['start'] = eo_get_the_start( 'c', $post->ID, $post->occurrence_id );
			$data['end']   = eo_get_the_end( 'c', $post->ID, $post->occurrence_id );
		} else {
			//Add schedule data
			$schedule = eo_get_event_schedule( $post->ID );

			foreach ( $this->schedule_fields as $field ) {

				if ( ! isset( $schema['properties'][$field] ) ) {
					continue;
				}
				//TODO Move this to validate/sanitize?
				switch ( $field ) {
					case 'start':
					case 'end':
					case 'until':
							$data[ $field ] = $schedule[ $field ]->format( 'Y-m-d H:i:s' );
						break;
					case 'include':
					case 'exclude':
						$data[ $field ] = array();
						foreach ( $schedule[ $field ] as $date ) {
							$data[ $field ][] = $date->format( 'Y-m-d H:i:s' );
						}
						break;
					default:
						$data[ $field ] = $schedule[ $field ];
						break;
				}
			}
		}
		$response->set_data( $data );
		return $response;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WP_Post $post Post object.
	 * @return array Links for the given post.
	 */
	protected function prepare_links( $post ) {
		$links = parent::prepare_links( $post );
		$base = sprintf( '/%s/%s', $this->namespace, $this->rest_base );
		$links['occurrences'] = array(
			'href'       => trailingslashit( rest_url( trailingslashit( $base ) . $post->ID ) ) . 'occurrences',
			'embeddable' => true,
		);
		return $links;
	}

	public function _add_event_fields_for_database( $prepared_post, $request ) {

		$timezone = eo_get_blog_timezone();
		$schema = $this->get_item_schema();

		foreach ( $this->schedule_fields as $field ) {

			if ( ! isset( $request[$field] ) || ! isset( $schema['properties'][$field] ) ) {
				continue;
			}
			//TODO Move this to validate/sanitize?
			switch ( $field ) {
				case 'start':
				case 'end':
				case 'until':
					try {
						$prepared_post->$field = new DateTime( $request[$field], $timezone );
					} catch ( Exception $e ) {
						return WP_Error( sprintf( 'Error with %s field: %s', $field, $e->getMessage() ) );
					}
					break;
				case 'include':
				case 'exclude':
					$dates = array();
					foreach ( $request[$field] as $date ) {
						try {
							$dates[] = new DateTime( $date, $timezone );
						} catch ( Exception $e ) {
							return WP_Error( sprintf( 'Error with %s field: %s', $field, $e->getMessage() ) );
						}
					}
					$prepared_post->$field = $dates;
					break;
				default:
					$prepared_post->$field = $request[$field];
					break;
			}
		}

		return $prepared_post;
	}

	/**
	 * Create a single post.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			return new WP_Error( 'rest_post_exists', __( 'Cannot create existing post.' ), array( 'status' => 400 ) );
		}

		$post = $this->prepare_item_for_database( $request );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$post->post_type = $this->post_type;

		$post_id = eo_insert_event( (array) $post );

		if ( is_wp_error( $post_id ) ) {

			if ( in_array( $post_id->get_error_code(), array( 'db_insert_error' ) ) ) {
				$post_id->add_data( array( 'status' => 500 ) );
			} else {
				$post_id->add_data( array( 'status' => 400 ) );
			}
			return $post_id;
		}
		$post->ID = $post_id;

		$schema = $this->get_item_schema();

		if ( ! empty( $schema['properties']['sticky'] ) ) {
			if ( ! empty( $request['sticky'] ) ) {
				stick_post( $post_id );
			} else {
				unstick_post( $post_id );
			}
		}

		if ( ! empty( $schema['properties']['featured_media'] ) && isset( $request['featured_media'] ) ) {
			$this->handle_featured_media( $request['featured_media'], $post->ID );
		}

		$terms_update = $this->handle_terms( $post->ID, $request );
		if ( is_wp_error( $terms_update ) ) {
			return $terms_update;
		}

		$post = get_post( $post_id );
		$this->update_additional_fields_for_object( $post, $request );

		/**
		 * Fires after a single post is created or updated via the REST API.
		 *
		 * @param object          $post      Inserted Post object (not a WP_Post object).
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating post, false when updating.
		 */
		do_action( "rest_insert_{$this->post_type}", $post, $request, true );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $post_id ) ) );

		return $response;
	}

	/**
	 * Update a single post.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$id = (int) $request['id'];
		$post = get_post( $id );

		if ( empty( $id ) || empty( $post->ID ) || $this->post_type !== $post->post_type ) {
			return new WP_Error( 'rest_post_invalid_id', __( 'Post id is invalid.' ), array( 'status' => 400 ) );
		}

		$post = $this->prepare_item_for_database( $request );
		if ( is_wp_error( $post ) ) {
			return $post;
		}
		// convert the post object to an array, otherwise wp_update_post will expect non-escaped input
		$post_id = eo_update_event( $id, (array) $post );
		if ( is_wp_error( $post_id ) ) {
			if ( in_array( $post_id->get_error_code(), array( 'db_update_error' ) ) ) {
				$post_id->add_data( array( 'status' => 500 ) );
			} else {
				$post_id->add_data( array( 'status' => 400 ) );
			}
			return $post_id;
		}

		$schema = $this->get_item_schema();

		if ( ! empty( $schema['properties']['featured_media'] ) && isset( $request['featured_media'] ) ) {
			$this->handle_featured_media( $request['featured_media'], $post_id );
		}

		if ( ! empty( $schema['properties']['sticky'] ) && isset( $request['sticky'] ) ) {
			if ( ! empty( $request['sticky'] ) ) {
				stick_post( $post_id );
			} else {
				unstick_post( $post_id );
			}
		}
		$terms_update = $this->handle_terms( $post->ID, $request );
		if ( is_wp_error( $terms_update ) ) {
			return $terms_update;
		}

		$post = get_post( $post_id );
		$this->update_additional_fields_for_object( $post, $request );

		/* This action is documented in lib/endpoints/class-wp-rest-controller.php */
		do_action( "rest_insert_{$this->post_type}", $post, $request, false );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $post, $request );
		return rest_ensure_response( $response );
	}

	protected function add_additional_fields_schema( $schema ) {

		//remove ambigeous properties
		unset( $schema['properties']['date'] );
		unset( $schema['properties']['date_gmt'] );

		$schema = parent::add_additional_fields_schema( $schema );
		$schema['properties'] = array_merge( array(
			'start'       => array(
				'description'  => __( 'The start date-time of the first occurrence of the event.' ),
				'type'         => 'string',
				'context'      => array( 'view', 'edit' ),
				'arg_options'  => array(
					//'validate_callback' => 'wp_filter_post_kses',
					//'sanitize_callback' => 'wp_filter_post_kses',
				),
			),
			'end'       => array(
				'description'  => __( 'The end date-time of the first occurrence of the event.' ),
				'type'         => 'string',
				'context'      => array( 'view', 'edit' ),
				'arg_options'  => array(
					'sanitize_callback' => 'wp_filter_post_kses',
				),
			),
			'all_day'       => array(
				'description'  => __( 'Whether the event is all day or not' ),
				'type'         => 'boolean',
				'context'      => array( 'view', 'edit' ),
				'arg_options'  => array(
					'sanitize_callback' => 'wp_filter_post_kses',
				),
			),
			'schedule'       => array(
				'description'  => __( 'The recurrence pattern for the event: none,daily,weekly,monthly,yearly,custom.' ),
				'type'         => 'string',
				'context'      => array( 'view', 'edit' ),
				'arg_options'  => array(
					'sanitize_callback' => 'wp_filter_post_kses',
				),
			),
			'schedule_meta'       => array(
				'description'  => __( 'how to a handle array?.' ),
				'type'         => 'string',
				'context'      => array( 'view', 'edit' ),
				'arg_options'  => array(
					'sanitize_callback' => 'wp_filter_post_kses',
				),
			),
			'frequency'       => array(
				'description'  => __( 'How frequent the recurrence is' ),
				'type'         => 'integer',
				'context'      => array( 'view', 'edit' ),
				'arg_options'  => array(
					'sanitize_callback' => 'wp_filter_post_kses',
				),
			),
			'until'       => array(
				'description'  => __( 'Repeat the event until this date-time (according to schedule and frequency).' ),
				'type'         => 'string',
				'context'      => array( 'view', 'edit' ),
				'arg_options'  => array(
					'sanitize_callback' => 'wp_filter_post_kses',
				),
			),
			/*'schedule_last' => array(
				'description'  => __( 'The start date-time of the last occurrenceof the event.' ),
				'type'         => 'string',
				'context'      => array( 'view' ),
				'arg_options'  => array(
					'sanitize_callback' => 'wp_filter_post_kses',
				),
			),*/
			'include' => array(
				'description'  => __( 'Array of date-times to add to the event\' schedule.' ),
				'type'         => 'string',
				'context'      => array( 'view', 'edit' ),
				'arg_options'  => array(
					'sanitize_callback' => 'wp_filter_post_kses',
				),
			),
			'exclude' => array(
				'description'  => __( 'Array of date-times to remove from the event\' schedule.' ),
				'type'         => 'string',
				'context'      => array( 'view', 'edit' ),
				'arg_options'  => array(
					'sanitize_callback' => 'wp_filter_post_kses',
				),
			),
			/*'number_occurrences' => array(
				'description'  => __( 'Repeat the event for this number of occurrences (according to schedule and frequency). Ignored if until is set.' ),
				'type'         => 'integer',
				'context'      => array( 'edit' ),
				'arg_options'  => array(
					'sanitize_callback' => 'wp_filter_post_kses',
				),
			),*/
		), $schema['properties'] );
		return $schema;
	}
}
