<?php
/**
 * Plugin Name: Awesome Support: REST API
 * Plugin URI: https://getawesomesupport.com/addons/awesome-support-rest-api/
 * Description: REST API add-on for Awesome Support
 * Author: Awesome Support
 * Author URI: https://getawesomesupport.com/
 * Version: 1.0.4
 * Text Domain: awesome-support-api
 * Domain Path: /languages/
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Instantiate the plugin
 *----------------------------------------------------------------------------*/

/**
 * Register the activation hook
 */
register_activation_hook( __FILE__, array( 'WPAS_API', 'maybe_activate' ) );

add_action( 'plugins_loaded', 'wpas_api' );

/**
 * Awesome Support API main plugin class.
 *
 * @since 1.0.0
 */
class WPAS_API {

	/**
	 * Required version of the core.
	 *
	 * The minimum version of the core that's required
	 * to properly run this addon. If the minimum version
	 * requirement isn't met an error message is displayed
	 * and the addon isn't registered.
	 *
	 * @since  0.1.0
	 * @var    string
	 */
	protected $version_required = '3.2.5';

	/**
	 * Required version of PHP.
	 *
	 * Follow WordPress latest requirements and require
	 * PHP version 5.4 at least.
	 *
	 * @var string
	 */
	protected $php_version_required = '5.4';

	/**
	 * Plugin slug.
	 *
	 * @since  0.1.0
	 * @var    string
	 */
	protected $slug = 'api';

	/**
	 * Possible error message.
	 *
	 * @var null|WP_Error
	 */
	protected $error = null;

	/**
	 * @var object WPAS_API\Auth\Init
	 */
	public $auth;

	/**
	 * Instance of this loader class.
	 *
	 * @since    0.1.0
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * WPAS_API constructor.
	 */
	public function __construct() {
		$this->declare_constants();
		$this->init();
	}

	/**
	 * Declare plugin constants
	 */
	protected function declare_constants() {
		define( 'AS_API_VERSION', '1.0.4' );
		define( 'AS_API_URL',     $this->plugin_url() );
		define( 'AS_API_PATH',    trailingslashit( $this->plugin_path() ) );
	}

	/**
	 * Initialize the addon.
	 *
	 * This method is the one running the checks and
	 * registering the addon to the core.
	 *
	 * @since  0.1.0
	 * @return boolean Whether or not the addon was registered
	 */
	public function init() {

		$plugin_name = $this->plugin_data( 'Name' );

		if ( ! $this->is_core_active() ) {
			$this->add_error( sprintf( __( '%s requires Awesome Support to be active. Please activate the core plugin first.', 'awesome-support-api' ), $plugin_name ) );
		}

		if ( ! $this->is_php_version_enough() ) {
			$this->add_error( sprintf( __( 'Unfortunately, %s can not run on PHP versions older than %s. Read more information about <a href="%s" target="_blank">how you can update</a>.', 'awesome-support-api' ), $plugin_name, $this->php_version_required, esc_url( 'http://www.wpupdatephp.com/update/' ) ) );
		}

		if ( ! $this->is_version_compatible() ) {
			$this->add_error( sprintf( __( '%s requires Awesome Support version %s or greater. Please update the core plugin first.', 'awesome-support-api' ), $plugin_name, $this->version_required ) );
		}

		if ( is_a( $this->error, 'WP_Error' ) ) {
			add_action( 'admin_notices', array( $this, 'display_error' ), 10, 0 );
			add_action( 'admin_init',    array( $this, 'deactivate' ),    10, 0 );
			return false;
		}

		/**
		 * Register the addon
		 */
		if ( function_exists( 'wpas_register_addon' ) ) {
			wpas_register_addon( $this->slug, array( $this, 'load' ) );
		}

		return true;

	}

	/**
	 * Load the addon.
	 *
	 * Include all necessary files and instanciate the addon.
	 *
	 * @since  0.1.0
	 * @return void
	 */
	public function load() {
		require_once( $this->plugin_path() . 'vendor/autoload.php' );

		register_deactivation_hook( $this->plugin_file(), array( 'WPAS_API', 'deactivate' ) );

		$this->includes();
		$this->actions();
		$this->filters();
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
		add_filter( 'rest_pre_dispatch',       array( $this, 'reroute_ticket_dispatch' ), 10, 3 );
	}

	/**
	 * Include required files
	 *
	 * @since 1.0.0
	 */
	protected function includes() {
		$this->auth = WPAS_API\Auth\Init::get_instance();
	}


	/** Actions ******************************************************/

	/**
	 * Load this plugins text domain
	 */
	public function load_text_domain() {

		// Set filter for plugin's languages directory
		$wpas_api_lang_dir = dirname( plugin_basename( $this->plugin_file() ) ) . '/languages/';
		$wpas_api_lang_dir = apply_filters( 'wpas_api_languages_directory', $wpas_api_lang_dir );


		// Traditional WordPress plugin locale filter

		$get_locale = get_locale();

		if ( function_exists( 'get_user_locale' ) ) {
			$get_locale = get_user_locale();
		}

		/**
		 * Defines the plugin language locale used.
		 *
		 * @var string $get_locale The locale to use. Uses get_user_locale()` in WordPress 4.7 or greater,
		 *                  otherwise uses `get_locale()`.
		 */
		$locale = apply_filters( 'plugin_locale', $get_locale, 'awesome-support-api' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'awesome-support-api', $locale );

		// Setup paths to current locale file
		$mofile_local  = $wpas_api_lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/awesome-support-api/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/awesome-support-api folder
			load_textdomain( 'awesome-support-api', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/awesome-support-api/languages/ folder
			load_textdomain( 'awesome-support-api', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'awesome-support-api', false, $wpas_api_lang_dir );
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

		$controller = new WPAS_API\API\UserData();
		$controller->register_routes();

		$controller = new WPAS_API\API\TicketStatus();
		$controller->register_routes();

		$controller = new WPAS_API\API\CustomFields();
		$controller->register_routes();

		$controller = new WPAS_API\API\Passwords();
		$controller->register_routes();

		$controller = new WPAS_API\API\Attachments();
		$controller->register_routes();
	}

	/**
	 * Register user field
	 */
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

	/**
	 * Display error.
	 *
	 * Get all the error messages and display them
	 * in the admin notices.
	 *
	 * @since  0.1.0
	 * @return void
	 */
	public function display_error() {

		if ( ! is_a( $this->error, 'WP_Error' ) ) {
			return;
		}

		$message = $this->error->get_error_messages(); ?>
		<div class="error">
			<p>
				<?php
				if ( count( $message ) > 1 ) {

					echo '<ul>';

					foreach ( $message as $msg ) {
						echo "<li>$msg</li>";
					}

					echo '</li>';

				} else {
					echo $message[0];
				}
				?>
			</p>
		</div>
	<?php

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

	/**
	 * Gutenberg uses the default /wp/v2/ namespace for all posts. This rewrites the route to support our namespace.
	 *
	 * @param $result
	 * @param WP_REST_Server $server
	 * @param WP_REST_Request $request
	 *
	 * @since  1.0.4
	 *
	 * @return WP_REST_Response
	 * @author Tanner Moushey
	 */
	public function reroute_ticket_dispatch( $result, $server, $request ) {

		if ( false === strpos( $request->get_route(), '/wp/v2/tickets' ) ) {
			return $result;
		}

		$request->set_route( str_replace( '/wp/v2/tickets', '/wpas-api/v1/tickets', $request->get_route() ) );
		return rest_do_request( $request );

	}

	/** Helper methods ******************************************************/

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * @return string
	 */
	public function get_api_namespace() {
		return apply_filters( 'wpas_api_get_api_namespace', 'wpas-api/v1' );
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
	 * Get the plugin data.
	 *
	 * @since  0.1.0
	 * @param  string $data Plugin data to retrieve
	 * @return string       Data value
	 */
	protected function plugin_data( $data ) {

		if ( ! function_exists( 'get_plugin_data' ) ) {

			$site_url = get_site_url() . '/';

			if ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN && 'http://' === substr( $site_url, 0, 7 ) ) {
				$site_url = str_replace( 'http://', 'https://', $site_url );
			}

			$admin_path = str_replace( $site_url, ABSPATH, get_admin_url() );

			require_once( $admin_path . 'includes/plugin.php' );

		}

		$plugin = get_plugin_data( $this->plugin_file(), false, false );

		if ( array_key_exists( $data, $plugin ) ) {
			return $plugin[$data];
		} else {
			return '';
		}

	}

	/**
	 * Check if core is active.
	 *
	 * Checks if the core plugin is listed in the acitve
	 * plugins in the WordPress database.
	 *
	 * @since  0.1.0
	 * @return boolean Whether or not the core is active
	 */
	protected function is_core_active() {
		if ( class_exists( 'Awesome_Support' ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check if the core version is compatible with this addon.
	 *
	 * @since  0.1.0
	 * @return boolean
	 */
	protected function is_version_compatible() {

		/**
		 * Return true if the core is not active so that this message won't show.
		 * We already have the error saying the plugin is disabled, no need to add this one.
		 */
		if ( ! $this->is_core_active() ) {
			return true;
		}

		if ( empty( $this->version_required ) ) {
			return true;
		}

		if ( ! defined( 'WPAS_VERSION' ) ) {
			return false;
		}

		if ( version_compare( WPAS_VERSION, $this->version_required, '<' ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Check if the version of PHP is compatible with this addon.
	 *
	 * @since  0.1.0
	 * @return boolean
	 */
	protected function is_php_version_enough() {

		/**
		 * No version set, we assume everything is fine.
		 */
		if ( empty( $this->php_version_required ) ) {
			return true;
		}

		if ( version_compare( phpversion(), $this->php_version_required, '<' ) ) {
			return false;
		}

		return true;

	}

	/**
	 * Add error.
	 *
	 * Add a new error to the WP_Error object
	 * and create the object if it doesn't exist yet.
	 *
	 * @since  0.1.0
	 * @param string $message Error message to add
	 * @return void
	 */
	public function add_error( $message ) {

		if ( ! is_object( $this->error ) || ! is_a( $this->error, 'WP_Error' ) ) {
			$this->error = new WP_Error();
		}

		$this->error->add( 'addon_error', $message );

	}

	/** Lifecycle methods ******************************************************/

	/**
	 * Handle plugin activation
	 *
	 * @since 1.0.0
	 */
	public static function maybe_activate() {

		if ( ! class_exists( 'Awesome_Support' ) ) {
			deactivate_plugins( basename( __FILE__ ) );
			wp_die( sprintf( __( 'You need Awesome Support to activate this addon. Please <a href="%s" target="_blank">install Awesome Support</a> before continuing.', 'wpascr' ), esc_url( 'http://getawesomesupport.com/?utm_source=internal&utm_medium=addon_loader&utm_campaign=Addons' ) ) );
		}

		$is_active = get_option( 'wpas_api_is_active', false );

		if ( ! $is_active ) {

			update_option( 'wpas_api_is_active', true );

			/**
			 * Run when activated.
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
	public static function deactivate() {
		if ( function_exists( 'deactivate_plugins' ) ) {
			deactivate_plugins( basename( __FILE__ ) );
		}

		delete_option( 'wpas_api_is_active' );

		/**
		 * Run when deactivated
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
	return WPAS_API::get_instance();
}