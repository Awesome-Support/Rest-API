<?php
/**
 * Plugin Name: Awesome Support API
 * Plugin URI: https://getawesomesupport.com/addons/api/
 * Description: API add-on for Awesome Support
 * Author: Awesome Support
 * Author URI: https://getawesomesupport.com/
 * Version: 1.0.0
 * Text Domain: awesome-support-api
 * Domain Path: /languages/
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

defined( 'ABSPATH' ) or exit;

wpas_api();

/**
 * WooCommerce AvaTax main plugin class.
 *
 * @since 1.0.0
 */
class WPAS_API {


	/** plugin version number */
	const VERSION = '1.0.0';

	/** @var WPAS_API single instance of this plugin */
	protected static $instance;

	/** @var bool $logging_enabled Whether debug logging is enabled */
	private $logging_enabled;

	public $auth;

	/**
	 * Initializes the plugin
	 *
	 * @since 1.0.0
	 */
	protected function __construct() {
		require_once( $this->plugin_path() . 'vendor/autoload.php' );

		// Lifecycle
		add_action( 'admin_init', array ( $this, 'maybe_activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Turn off API request logging unless specified in the settings
		if ( ! $this->logging_enabled() ) {
			remove_action( 'wpas_api_request_performed', array( $this, 'log_api_request' ) );
		}

		$this->includes();
		$this->actions();
		$this->filters();
	}


	/**
	 * Include required files
	 *
	 * @since 1.0.0
	 */
	protected function includes() {
		$this->auth = WPAS_API\Auth\Init::get_instance();
	}

	/**
	 * Handle Actions
	 */
	protected function actions() {
		add_action( 'init', array( $this, 'load_text_domain' ) );
		add_action( 'rest_api_init', array( $this, 'load_api_routes' ) );
		add_action( 'rest_api_init', array( $this, 'user_fields' ) );
	}

	/**
	 * Handle Filters
	 */
	protected function filters() {
		add_filter( 'register_post_type_args', array( $this, 'enable_rest_api_cpt' ), 10, 2 );
		add_filter( 'register_taxonomy_args',  array( $this, 'enable_rest_api_tax' ), 10, 3 );
		add_filter( 'rest_prepare_taxonomy',   array( $this, 'taxonomy_rest_response' ), 10, 3 );
	}

	/** Actions ******************************************************/

	/**
	 * Load this plugins text domain
	 */
	public function load_text_domain() {

		// Set filter for plugin's languages directory
		$rcp_lang_dir = dirname( plugin_basename( $this->plugin_file() ) ) . '/languages/';
		$rcp_lang_dir = apply_filters( 'wpas_api_languages_directory', $rcp_lang_dir );


		// Traditional WordPress plugin locale filter

		$get_locale = get_locale();

		if ( function_exists( 'rcp_compare_wp_version' ) && rcp_compare_wp_version( 4.7 ) ) {
			$get_locale = get_user_locale();
		}

		/**
		 * Defines the plugin language locale used in RCP.
		 *
		 * @var string $get_locale The locale to use. Uses get_user_locale()` in WordPress 4.7 or greater,
		 *                  otherwise uses `get_locale()`.
		 */
		$locale = apply_filters( 'plugin_locale', $get_locale, 'awesome-support-api' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'awesome-support-api', $locale );

		// Setup paths to current locale file
		$mofile_local  = $rcp_lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/awesome-support-api/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/rcp folder
			load_textdomain( 'awesome-support-api', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/easy-digital-downloads/languages/ folder
			load_textdomain( 'awesome-support-api', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'awesome-support-api', false, $rcp_lang_dir );
		}

	}

	/**
	 * Load APIs that are not loaded automatically
	 */
	public function load_api_routes() {
		$controller = new WPAS_API\API\Settings();
		$controller->register_routes();

		$controller = new WPAS_API\API\Users();
		$controller->register_routes();

		$controller = new WPAS_API\API\Passwords();
		$controller->register_routes();
	}

	public function user_fields() {
		register_rest_field( 'users', 'wpas_can_be_assigned', array(
			'get_callback'    => function ( $comment_arr ) {
				$comment_obj = get_comment( $comment_arr['id'] );

				return (int) $comment_obj->comment_karma;
			},
			'update_callback' => function ( $karma, $comment_obj ) {
				$ret = wp_update_comment( array(
					'comment_ID'    => $comment_obj->comment_ID,
					'comment_karma' => $karma
				) );
				if ( false === $ret ) {
					return new WP_Error( 'rest_comment_karma_failed', __( 'Failed to update comment karma.' ),
						array( 'status' => 500 ) );
				}

				return true;
			},
			'schema'          => array(
				'description' => __( 'Comment karma.' ),
				'type'        => 'integer'
			),
		) );
	}

	/** Filters ******************************************************/

	/**
	 * @param array  $args      Array of arguments for registering a post type.
	 * @param string $post_type Post type key.
	 *
	 * @return array $args
	 */
	public function enable_rest_api_cpt( $args, $post_type ) {

		switch( $post_type ) {
			case 'ticket' :
				$args['show_in_rest'] = true;
				$args['rest_base'] = 'tickets';
				$args['rest_controller_class'] = 'WPAS_API\API\Tickets';
				break;

			case 'ticket_reply' :
				$args['show_in_rest'] = true;
				$args['rest_base'] = 'replies';
				$args['rest_controller_class'] = 'WPAS_API\API\TicketReplies';
				break;

			case 'ticket_history' :
				$args['show_in_rest'] = true;
				$args['rest_base'] = 'history';
				$args['rest_controller_class'] = 'WPAS_API\API\TicketHistory';
				break;

		}
		return $args;
	}

	/**
	 * @param array  $args      Array of arguments for registering a post type.
	 * @param string $post_type Post type key.
	 *
	 * @return array $args
	 */
	public function enable_rest_api_tax( $args, $taxonomy, $post_type ) {

		if ( in_array( 'ticket', (array) $post_type ) ) {
			$args['show_in_rest'] = true;
			$args['rest_base'] = $taxonomy;
			$args['rest_controller_class'] = 'WPAS_API\API\TicketTaxonomy';
		}

		return $args;
	}

	/**
	 * Filter the response and update the term page to user the correct namespace.
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param object           $taxonomy     The original taxonomy object.
	 * @param WP_REST_Request  $request  Request used to generate the response.
	 *
	 * @return WP_REST_Response $response
	 */
	public function taxonomy_rest_response( $response, $taxonomy, $request ) {
		$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

		if ( in_array( 'ticket', $taxonomy->object_type ) ) {
			$response->remove_link( 'https://api.w.org/items' );
			$response->add_link( 'https://api.w.org/items', rest_url( wpas_api()->get_api_namespace() . '/' . $base ) );
		}

		return $response;
	}

	/** Helper methods ******************************************************/

	/**
	 * Main WPAS_API Instance, ensures only one instance is/can be loaded.
	 *
	 * @since 1.0.0
	 * @see wpas_api()
	 * @return WPAS_API
	 */
	public static function instance() {
		if ( ! self::$instance instanceof WPAS_API ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * @return string
	 */
	public function get_api_namespace() {
		return apply_filters( 'wpas_api_namespace', 'wpas-api/v1' );
	}

	/**
	 * Gets the plugin documentation URL
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_documentation_url() {
		return 'http://docs.awesomesupport.com/';
	}


	/**
	 * Gets the plugin support URL
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_support_url() {
		return 'https://awesomesupport.com/';
	}


	/**
	 * Returns the plugin name, localized
	 *
	 * @since 1.0.0
	 * @return string the plugin name
	 */
	public function get_plugin_name() {
		return __( 'Awesome Support API', 'awesome-support-api' );
	}


	/**
	 * Returns __FILE__
	 *
	 * @since 1.0.0
	 * @return string the full path and filename of the plugin file
	 */
	protected function plugin_file() {
		return __FILE__;
	}

	/**
	 * Returns path to plugin directory
	 *
	 * @return string
	 */
	public function plugin_path() {
		return plugin_dir_path( $this->plugin_file() );
	}

	/**
	 * Returns url to plugin directory
	 *
	 * @return string
	 */
	public function plugin_url() {
		return trailingslashit( plugin_dir_url( $this->plugin_file() ) );
	}

	/**
	 * Returns true if on the plugin's settings page
	 *
	 * @since 1.0.0
	 * @return boolean true if on the settings page
	 */
	public function is_plugin_settings() {
		return isset( $_GET['page'] ) &&
			'wpas-settings' == $_GET['page'] &&
			isset( $_GET['tab'] ) &&
			'api' == $_GET['tab'];
	}


	/**
	 * Gets the plugin configuration URL
	 *
	 * @since 1.0.0
	 * @return string plugin settings URL
	 */
	public function get_settings_url() {
		return admin_url( 'admin.php?page=wpas-settings&tab=api' );
	}

	/**
	 * Determine if debug logging is enabled.
	 *
	 * @since 1.0.0
	 * @return bool $logging_enabled Whether debug logging is enabled.
	 */
	public function logging_enabled() {

		$this->logging_enabled = ( 'yes' === get_option( 'wpas_api_debug' ) );

		/**
		 * Filter whether debug logging is enabled.
		 *
		 * @since 1.0.0
		 * @param bool $logging_enabled Whether debug logging is enabled.
		 */
		return apply_filters( 'wpas_api_logging_enabled', $this->logging_enabled );
	}

	/** Lifecycle methods ******************************************************/

	/**
	 * Handle plugin activation
	 *
	 * @since 1.0.0
	 */
	public function maybe_activate() {

		$is_active = get_option( 'wpas_api_is_active', false );

		if ( ! $is_active ) {

			update_option( 'wpas_api_is_active', true );

			/**
			 * Run when AvaTax is activated.
			 *
			 * @since 1.0.0
			 */
			do_action( 'wpas_api_activated' );
		}

	}


	/**
	 * Handle plugin deactivation
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {

		delete_option( 'wpas_api_is_active' );

		/**
		 * Run when AvaTax is deactivated
		 *
		 * @since 1.0.0
		 */
		do_action( 'wpas_api_deactivated' );
	}


} // end WPAS_API class


/**
 * Returns the One True Instance of WPAS_API
 *
 * @since 1.0.0
 * @return object | WPAS_API
 */
function wpas_api() {
	return WPAS_API::instance();
}