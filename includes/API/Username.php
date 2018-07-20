<?php

namespace WPAS_API\API;

use WPAS_API\Auth\User;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class used to get user id by user name
 *
 * @since 4.7.0
 *
 * @see WP_REST_Controller
 */
class Username {

	public function __construct() {
		$this->namespace = wpas_api()->get_api_namespace();
    }

	
	/**
	 * Registers the routes for the objects of the controller.
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, '/users/user/(?P<username>[0-9a-zA-Z_-]+)', array(
			'args' => array(
				'username' => array(
					'description' => __( 'The username of requested user.', 'awesome-support-api' ),
					'type'        => 'string',
					'required'    => true,
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_user' ),
				'permission_callback' => array( $this, 'get_user_permissions_check' ),
            ),
			'schema' => null,
		) );


    }
    
	/**
	 * Checks if a given request has access to list users or create ticket
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has read access for the item, otherwise false.
	 */
	public function get_user_permissions_check( $request ) {

        if ( current_user_can( 'list_users' ) or current_user_can( 'create_ticket' ) ) {
			return true;
		}

		return false;
    }

	/*
	* Retrieves the user ID.
	*
	* @param WP_REST_Request $request Full details about the request.
	* @return array on success, or WP_Error object on failure.
	*/
	public function get_user( $request ) {

		$user = get_user_by( 'login',  base64_decode( $request[ 'username' ] ) );
		
		// Check result
        if ( ! $user ) {
            return new WP_Error( 'invalid_username', __( 'Invalid username.', 'awesome-support-api' ), array( 'status' => 400 ) );
		}

		// Check user ID
		if ( $user->ID != get_current_user_id() ) {
            return new WP_Error( 'invalid_username_access', __( 'You are not allowed to get user data', 'awesome-support-api' ), array( 'status' => 400 ) );
		}
		
		return array(
			'id' => $user->ID
		);

	}

    
}