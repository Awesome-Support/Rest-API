<?php

namespace WPAS_API;

class Init {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of \RCP_Avatax\Init
	 *
	 * @return Init
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof Init ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'plugins_loaded', array( $this, 'maybe_setup' ), - 9999 );
	}


	public function maybe_setup() {
		if ( ! $this->check_required_plugins() ) {
			return;
		}

		$this->includes();

		add_action( 'wp_enqueue_scripts',    array( $this, 'scripts' ) );
		add_action( 'rcp_view_member_after', array( $this, 'member_details' ) );
	}

	protected function includes() {}

	/**
	 * Make sure RCP is active
	 * @return bool
	 */
	protected function check_required_plugins() {

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if ( is_plugin_active( 'restrict-content-pro/restrict-content-pro.php' ) ) {
			return true;
		}

		add_action( 'admin_notices', array( $this, 'required_plugins' ) );

		return false;
	}

	/**
	 * Required Plugins notice
	 */
	public function required_plugins() {
		printf( '<div class="error"><p>%s</p></div>', __( 'Restrict Content Pro is required for the Restrict Content Pro - AvaTax add-on to function.', 'rcp-avatax' ) );
	}

	public function scripts() {

		global $rcp_options;

//		wp_register_script( 'taxamo', 'https://api.taxamo.com/js/v1/taxamo.all.js', array(), '1' );
//		wp_register_script( 'rcp-avatax', RCPTX_PATH . 'assets/scripts/rcp-taxamo.min.js', array( 'jquery', 'taxamo' ), '1', true );
//
//		if ( is_page( $rcp_options['registration_page'] ) && ! empty( $rcp_options['avatax_account_number'] ) ) {
//			wp_enqueue_script( 'rcp-avatax' );
//			wp_localize_script( 'rcp-avatax', 'rcp_taxamo_vars', array(
//					'avatax_account_number' => $rcp_options['avatax_account_number'],
//					'currency'            => $rcp_options['currency'],
//					'priceTemplate'       => ! empty( $rcp_options['taxamo_price_template'] ) ? $rcp_options['taxamo_price_template'] : __( '${totalAmount} (${taxRate}% tax)', 'rcp-avatax' ),
//					'noTaxTitle'          => ! empty( $rcp_options['taxamo_no_tax_title'] ) ? $rcp_options['taxamo_no_tax_title'] : __( 'No tax applied in this location', 'rcp-avatax' ),
//					'taxTitle'            => ! empty( $rcp_options['taxamo_tax_title'] ) ? $rcp_options['taxamo_tax_title'] : __( 'Original amount: ${amount}, tax rate: ${taxRate}%', 'rcp-avatax' ),
//					'priceClass'          => ! empty( $rcp_options['taxamo_price_class'] ) ? '.' . $rcp_options['taxamo_price_class'] : '.rcp_price',
//				)
//			);
//		}

	}

	/**
	 * Render the country field for member details.
	 */
	public function member_details( $user_id ) {
		$country   = get_user_meta( $user_id, 'rcp_country', true );
		$countries = self::get_countries();
		if ( empty( $country ) ) {
			return;
		} ?>
		<tr class="form-field">
		<th scope="row" valign="top">
			<?php _e( 'Country', 'rcp-avatax' ); ?>
		</th>
		<td>
			<?php echo $countries[ $country ]; ?>
		</td>
		</tr><?php
	}

	/** Helper Methods **************************************/

	/**
	 * @param $key
	 *
	 * @return string
	 */
	public static function get_settings( $key = false, $default = '' ) {
		$settings = get_option( 'rcp_avatax', '' );

		if ( ! $key ) {
			return $settings;
		}

		if ( empty( $settings[ $key ] ) ) {
			$settings[ $key ] = $default;
		}

		return apply_filters( 'rcp_avatax_get_setting', $settings[ $key ], $key );
	}

	/**
	 * Get the endpoint to use for AvaTax requests
	 *
	 * @return string
	 */
	public static function get_service_url() {
		return 'https://development.avalara.net';
	}

	/**
	 * Gets the plugin documentation URL
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_documentation_url() {
		return '';
	}

	/**
	 * Gets the plugin support URL
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_support_url() {

		return '';
	}

	/**
	 * Returns the plugin name, localized
	 *
	 * @since 1.0.0
	 * @return string the plugin name
	 */
	public function get_plugin_name() {
		return __( 'RCP AvaTax', 'rcp-avatax' );
	}

	public function get_id() {
		return 'rcp-avatax';
	}

	/**
	 * Returns __FILE__
	 *
	 * @since 1.0.0
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {
		return RCP_AVATAX_PLUGIN_FILE;
	}

	/**
	 * Returns the current version of the plugin
	 *
	 * @since 1.0.0
	 * @return string plugin version
	 */
	public function get_version() {
		return RCP_AVATAX_PLUGIN_VERSION;
	}

	/** Subscription Meta Helpers ********************/

	/**
	 * Save subscription meta
	 *
	 * @param $subscription_id
	 * @param $values
	 */
	public static function meta_save( $subscription_id, $values ) {
		$meta = get_option( Levels::get_option_key(), array() );

		$meta[ $subscription_id ] = apply_filters( 'rcp_avatax_tax_code_save_sanitize', $values );

		update_option( Levels::get_option_key(), $meta );
	}

	/**
	 * Get subscription meta
	 *
	 * @param      $subscription_id
	 * @param null $key
	 *
	 * @return mixed|void
	 */
	public static function meta_get( $subscription_id, $key = null ) {
		$meta = get_option( Levels::get_option_key(), array() );

		$meta = Helpers::get_param( $meta, $subscription_id, array() );

		if ( $key ) {
			$meta = Helpers::get_param( $meta, $key );
		}

		return apply_filters( 'rcp_avatax_tax_code_get', $meta, $key, $subscription_id );
	}

	/** API Helpers ********************/

	/**
	 * Return new API request
	 *
	 * @return API
	 */
	public static function new_request() {
		$account_number = self::get_settings( 'avatax_account_number' );
		$license_key    = self::get_settings( 'avatax_license_key' );
		$environment    = 'sandbox';

		return new API( $account_number, $license_key, $environment );
	}

	/**
	 * Calculate registration tax
	 *
	 * @return object
	 */
	public static function calculate_registration_tax() {
		$request = self::new_request();
		return $request->calculate_registration_tax();
	}

}