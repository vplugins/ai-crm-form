<?php
/**
 * Integration Manager Class
 *
 * Manages all form plugin integrations.
 *
 * @package AI_CRM_Form
 * @subpackage Integrations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integration Manager Class
 */
class AICRMFORM_Integration_Manager {

	/**
	 * Single instance of the class.
	 *
	 * @var AICRMFORM_Integration_Manager|null
	 */
	private static $instance = null;

	/**
	 * Registered integrations.
	 *
	 * @var array
	 */
	private $integrations = [];

	/**
	 * Get single instance.
	 *
	 * @return AICRMFORM_Integration_Manager
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_integrations();
	}

	/**
	 * Load all available integrations.
	 */
	private function load_integrations() {
		// Load Contact Form 7 integration.
		$cf7 = new AICRMFORM_CF7_Integration();
		$this->register_integration( $cf7 );

		// Load Gravity Forms integration.
		$gravity = new AICRMFORM_Gravity_Forms_Integration();
		$this->register_integration( $gravity );

		// Load WPForms integration.
		$wpforms = new AICRMFORM_WPForms_Integration();
		$this->register_integration( $wpforms );

		/**
		 * Allow third-party plugins to register their own integrations.
		 *
		 * @param AICRMFORM_Integration_Manager $manager The integration manager instance.
		 */
		do_action( 'aicrmform_register_integrations', $this );
	}

	/**
	 * Register an integration.
	 *
	 * @param AICRMFORM_Form_Integration_Interface $integration Integration instance.
	 */
	public function register_integration( AICRMFORM_Form_Integration_Interface $integration ) {
		$key = $integration->get_key();
		$this->integrations[ $key ] = $integration;
	}

	/**
	 * Get all registered integrations.
	 *
	 * @return array Array of integration instances.
	 */
	public function get_integrations() {
		return $this->integrations;
	}

	/**
	 * Get a specific integration by key.
	 *
	 * @param string $key Integration key.
	 * @return AICRMFORM_Form_Integration_Interface|null Integration or null.
	 */
	public function get_integration( $key ) {
		return $this->integrations[ $key ] ?? null;
	}

	/**
	 * Get all available (active) integrations.
	 *
	 * @return array Array of available integration instances.
	 */
	public function get_available_integrations() {
		return array_filter(
			$this->integrations,
			function ( $integration ) {
				return $integration->is_available();
			}
		);
	}

	/**
	 * Get integration info for all registered integrations.
	 *
	 * @return array Array of integration info.
	 */
	public function get_all_info() {
		$info = [];

		foreach ( $this->integrations as $key => $integration ) {
			$info[ $key ] = $integration->get_info();
		}

		return $info;
	}

	/**
	 * Get forms from a specific integration.
	 *
	 * @param string $key Integration key.
	 * @return array Array of forms or empty array.
	 */
	public function get_forms( $key ) {
		$integration = $this->get_integration( $key );

		if ( ! $integration || ! $integration->is_available() ) {
			return [];
		}

		return $integration->get_forms();
	}

	/**
	 * Get a specific form from an integration.
	 *
	 * @param string     $key     Integration key.
	 * @param int|string $form_id Form ID.
	 * @return array|null Form data or null.
	 */
	public function get_form( $key, $form_id ) {
		$integration = $this->get_integration( $key );

		if ( ! $integration || ! $integration->is_available() ) {
			return null;
		}

		return $integration->get_form( $form_id );
	}

	/**
	 * Check if an integration is available.
	 *
	 * @param string $key Integration key.
	 * @return bool True if available, false otherwise.
	 */
	public function is_available( $key ) {
		$integration = $this->get_integration( $key );

		if ( ! $integration ) {
			return false;
		}

		return $integration->is_available();
	}

	/**
	 * Get list of supported plugins for display.
	 *
	 * @return array Array with plugin info.
	 */
	public function get_supported_plugins() {
		$plugins = [];

		foreach ( $this->integrations as $key => $integration ) {
			$plugins[ $key ] = [
				'name'   => $integration->get_name(),
				'slug'   => $integration->get_plugin_slug(),
				'active' => $integration->is_available(),
			];
		}

		return $plugins;
	}
}

