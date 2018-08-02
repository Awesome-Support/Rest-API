<?php

namespace WPAS_API\API;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;


/**
 * Class used to get custom fields
 * 
 */
class CustomFields {

	public function __construct() {
		$this->namespace = wpas_api()->get_api_namespace();
		$this->rest_base = 'tickets';
    }

	
	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<ticket_id>[\d]+)/custom-fields', array(
            'args' => array(
				'ticket_id' => array(
					'description' => __( 'Unique ticket identifier.' ),
					'type'        => 'integer',
					'required'    => true,
				),
			),
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_custom_fields' ),
                'permission_callback' => array( $this, 'get_custom_fields_permissions_check' ),
            ) 
            
        ) );

    }
    
	/**
	 * Checks if a given request has access to create a ticket
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has read access for the item, otherwise false.
	 */
	public function get_custom_fields_permissions_check( $request ) {
        return current_user_can( 'create_ticket' );
    }

	/*
	* Retrieves the custom fields
	*
	* @param WP_REST_Request $request Full details about the request.
	* @return array on success, or WP_Error object on failure.
	*/
	public function get_custom_fields( $request ) {

        $fields = array();
        
        // custom fields to skip
        // these fields are used in the admin part only
        $skip = array(
            'id',
            'status',
            'assignee',
            'wpas-client',
            'time_adjustments_pos_or_neg',
            'wpas-activity',
            'ttl_replies_by_agent',
            'ttl_calculated_time_spent_on_ticket',
            'ttl_adjustments_to_time_spent_on_ticket',
            'final_time_spent_on_ticket',
            'first_addl_interested_party_name',
            'first_addl_interested_party_email',
            'second_addl_interested_party_name',
            'second_addl_interested_party_email'
        );

        $skip_fields   = apply_filters( 'wpas_api_custom_fields_filter', $skip );
        $custom_fields = WPAS()->custom_fields->get_custom_fields(); 

        foreach ( $custom_fields as $field => $data ) {
            // check field
            if ( in_array( $field, $skip_fields ) ) {
                continue;
            }

            $field_name  = ! empty( $data[ 'args' ][ 'label' ] ) ? $data[ 'args' ][ 'label' ] : $data[ 'args' ][ 'title' ];
            $field_value = function_exists( $data[ 'args' ][ 'column_callback' ] ) ? $this->column_callback( $data[ 'args' ][ 'column_callback' ], $data[ 'name' ], $request['ticket_id'] ) : wpas_get_cf_value( $data[ 'name' ], $request['ticket_id'], false );

            $fields[ $field ] = array(
                'name'  => $field_name,
                'value' => $field_value
            );

        }

        return $fields;

    }
    

    /**
     * Run column callback
     *
     * @param callable $callback
     * @param string $name
     * @param int $ticket_id
     * @return void
     */
    private function column_callback( $callback, $name, $ticket_id ) {

        if ( is_callable( $callback) ) {

            ob_start();
            call_user_func( $callback, $name, $ticket_id ); 
            $contents = ob_get_clean();

            return $contents;

        }

        return false;

    }

    
}