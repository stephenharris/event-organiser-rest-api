<?php

/**
 * Access terms associated with a taxonomy
 */
class WP_REST_Event_Categories_Controller extends WP_REST_Event_Terms_Controller {

	protected $taxonomy;

	protected function add_additional_fields_to_object( $data, $request ) {
		$data = parent::add_additional_fields_to_object( $data, $request );
		$term_id = (int) $data['id'];
		$data = array_merge(
			$data,
			array( 'color' => eo_get_category_color( $term_id ) )
		);
		return $data;
	}

	/**
	 *
	 */
	public function update_additional_fields_for_object( $term, $request ) {
		parent::update_additional_fields_for_object( $term, $request );
		$schema = $this->get_item_schema();
		if ( isset( $request['color'] ) && ! empty( $schema['properties']['color'] ) ) {
			update_option( "eo-event-category_{$term->term_id}", array(
				'colour' => $request['color'],
			) );
		}
	}

	protected function add_additional_fields_schema( $schema ) {
		$schema = parent::add_additional_fields_schema( $schema );
		$schema['properties'] = array_merge( $schema['properties'], array(
			'color'       => array(
				'description'  => __( 'The colour (HEX format) associated with this event.' ),
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
