<?php
/**
 * Plugin Name: AI CRM Form
 * Plugin URI: https://github.com/rajanvijayan/ai-crm-form
 * Description: AI-powered form generator that submits to CRM API. Generate dynamic forms using AI and capture leads seamlessly.
 * Version: 1.0.0
 * Author: Rajan Vijayan
 * Author URI: https://rajanvijayan.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-crm-form
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 8.0
 *
 * @package AI_CRM_Form
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'AICRMFORM_VERSION', '1.0.0' );
define( 'AICRMFORM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AICRMFORM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AICRMFORM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Plugin Class
 */
class AI_CRM_Form {

	/**
	 * Single instance of the class.
	 *
	 * @var AI_CRM_Form|null
	 */
	private static $instance = null;

	/**
	 * Get single instance.
	 *
	 * @return AI_CRM_Form
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
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required files.
	 */
	private function load_dependencies() {
		// Load Composer autoloader.
		if ( file_exists( AICRMFORM_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
			require_once AICRMFORM_PLUGIN_DIR . 'vendor/autoload.php';
		}

		// Load plugin classes.
		require_once AICRMFORM_PLUGIN_DIR . 'includes/class-field-mapping.php';
		require_once AICRMFORM_PLUGIN_DIR . 'includes/class-crm-api.php';
		require_once AICRMFORM_PLUGIN_DIR . 'includes/class-form-generator.php';
		require_once AICRMFORM_PLUGIN_DIR . 'includes/class-admin-settings.php';
		require_once AICRMFORM_PLUGIN_DIR . 'includes/class-rest-api.php';
		require_once AICRMFORM_PLUGIN_DIR . 'includes/class-form-shortcode.php';
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Activation/Deactivation hooks.
		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );

		// Initialize components.
		add_action( 'init', [ $this, 'init' ] );
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'frontend_scripts' ] );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

		// Check and create tables if needed.
		add_action( 'admin_init', [ $this, 'maybe_create_tables' ] );

		// Cron for auto-deleting old submissions.
		add_action( 'aicrmform_cleanup_submissions', [ $this, 'cleanup_old_submissions' ] );
	}

	/**
	 * Check if tables exist and create if needed.
	 */
	public function maybe_create_tables() {
		$db_version = get_option( 'aicrmform_db_version', '0' );

		if ( version_compare( $db_version, AICRMFORM_VERSION, '<' ) ) {
			$this->create_tables();
		}
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		// Set default options.
		$defaults = [
			'api_key'                 => '',
			'ai_provider'             => 'groq',
			'ai_model'                => 'llama-3.3-70b-versatile',
			'crm_api_url'             => 'https://forms-prod.apigateway.co/forms.v1.FormSubmissionService/CreateFormSubmission',
			'form_id'                 => '',
			'system_instruction'      => 'You are a helpful form generation assistant. Generate HTML forms based on user requirements.',
			'default_success_message' => 'Thank you for your submission! We will get back to you soon.',
			'default_error_message'   => 'Something went wrong. Please try again later.',
			'enabled'                 => false,
		];

		if ( ! get_option( 'aicrmform_settings' ) ) {
			add_option( 'aicrmform_settings', $defaults );
		} else {
			// Merge new defaults with existing settings.
			$existing = get_option( 'aicrmform_settings' );
			$merged   = array_merge( $defaults, $existing );
			update_option( 'aicrmform_settings', $merged );
		}

		// Create forms table.
		$this->create_tables();

		// Schedule cron for cleaning up old submissions.
		if ( ! wp_next_scheduled( 'aicrmform_cleanup_submissions' ) ) {
			wp_schedule_event( time(), 'daily', 'aicrmform_cleanup_submissions' );
		}

		flush_rewrite_rules();
	}

	/**
	 * Create database tables.
	 */
	private function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Forms table.
		$forms_table = $wpdb->prefix . 'aicrmform_forms';
		$forms_sql   = "CREATE TABLE $forms_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			description text DEFAULT NULL,
			form_config longtext NOT NULL,
			field_mapping longtext NOT NULL,
			crm_form_id varchar(100) NOT NULL,
			status varchar(20) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY status (status)
		) $charset_collate;";

		// Submissions table.
		$submissions_table = $wpdb->prefix . 'aicrmform_submissions';
		$submissions_sql   = "CREATE TABLE $submissions_table (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			form_id bigint(20) unsigned NOT NULL,
			submission_data longtext NOT NULL,
			crm_response longtext DEFAULT NULL,
			status varchar(20) DEFAULT 'pending',
			ip_address varchar(45) DEFAULT NULL,
			user_agent varchar(500) DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY form_id (form_id),
			KEY status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $forms_sql );
		dbDelta( $submissions_sql );

		update_option( 'aicrmform_db_version', '1.0.0' );
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		// Clear scheduled cron.
		$timestamp = wp_next_scheduled( 'aicrmform_cleanup_submissions' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'aicrmform_cleanup_submissions' );
		}

		flush_rewrite_rules();
	}

	/**
	 * Cleanup old submissions based on settings.
	 */
	public function cleanup_old_submissions() {
		$settings = get_option( 'aicrmform_settings', [] );
		$days     = absint( $settings['auto_delete_submissions'] ?? 0 );

		if ( $days <= 0 ) {
			return; // Auto-delete is disabled.
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'aicrmform_submissions';
		$cutoff     = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table_name} WHERE created_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$cutoff
			)
		);
	}

	/**
	 * Initialize plugin.
	 */
	public function init() {
		// Load text domain.
		load_plugin_textdomain( 'ai-crm-form', false, dirname( AICRMFORM_PLUGIN_BASENAME ) . '/languages' );

		// Register shortcodes.
		$shortcode = new AICRMFORM_Form_Shortcode();
		$shortcode->register();
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'AI CRM Forms', 'ai-crm-form' ),
			__( 'AI CRM Forms', 'ai-crm-form' ),
			'manage_options',
			'ai-crm-form-settings',
			[ $this, 'render_admin_page' ],
			'dashicons-feedback',
			30
		);

		add_submenu_page(
			'ai-crm-form-settings',
			__( 'Settings', 'ai-crm-form' ),
			__( 'Settings', 'ai-crm-form' ),
			'manage_options',
			'ai-crm-form-settings',
			[ $this, 'render_admin_page' ]
		);

		add_submenu_page(
			'ai-crm-form-settings',
			__( 'Forms', 'ai-crm-form' ),
			__( 'Forms', 'ai-crm-form' ),
			'manage_options',
			'ai-crm-form-forms',
			[ $this, 'render_forms_page' ]
		);

		add_submenu_page(
			'ai-crm-form-settings',
			__( 'Form Builder', 'ai-crm-form' ),
			__( 'Form Builder', 'ai-crm-form' ),
			'manage_options',
			'ai-crm-form-generator',
			[ $this, 'render_generator_page' ]
		);

		add_submenu_page(
			'ai-crm-form-settings',
			__( 'Submissions', 'ai-crm-form' ),
			__( 'Submissions', 'ai-crm-form' ),
			'manage_options',
			'ai-crm-form-submissions',
			[ $this, 'render_submissions_page' ]
		);
	}

	/**
	 * Render admin settings page.
	 */
	public function render_admin_page() {
		$admin = new AICRMFORM_Admin_Settings();
		$admin->render();
	}

	/**
	 * Render forms page.
	 */
	public function render_forms_page() {
		$admin = new AICRMFORM_Admin_Settings();
		$admin->render_forms_page();
	}

	/**
	 * Render form generator page.
	 */
	public function render_generator_page() {
		$admin = new AICRMFORM_Admin_Settings();
		$admin->render_generator_page();
	}

	/**
	 * Render submissions page.
	 */
	public function render_submissions_page() {
		$admin = new AICRMFORM_Admin_Settings();
		$admin->render_submissions_page();
	}

	/**
	 * Register REST API routes.
	 */
	public function register_rest_routes() {
		$api = new AICRMFORM_REST_API();
		$api->register_routes();
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function admin_scripts( $hook ) {
		if ( strpos( $hook, 'ai-crm-form' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'aicrmform-admin',
			AICRMFORM_PLUGIN_URL . 'assets/css/admin.css',
			[],
			AICRMFORM_VERSION
		);

		wp_enqueue_script(
			'aicrmform-admin',
			AICRMFORM_PLUGIN_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			AICRMFORM_VERSION,
			true
		);

		wp_localize_script(
			'aicrmform-admin',
			'aicrmformAdmin',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'restUrl' => rest_url( 'ai-crm-form/v1/' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			]
		);
	}

	/**
	 * Enqueue frontend scripts.
	 */
	public function frontend_scripts() {
		$settings = get_option( 'aicrmform_settings', [] );

		// Load Google Font if configured.
		$font_family = $settings['default_font_family'] ?? '';
		if ( ! empty( $font_family ) ) {
			$font_slug = str_replace( ' ', '+', $font_family );
			wp_enqueue_style(
				'aicrmform-google-fonts',
				'https://fonts.googleapis.com/css2?family=' . esc_attr( $font_slug ) . ':wght@400;500;600;700&display=swap',
				[],
				AICRMFORM_VERSION
			);
		}

		wp_enqueue_style(
			'aicrmform-frontend',
			AICRMFORM_PLUGIN_URL . 'assets/css/form.css',
			[],
			AICRMFORM_VERSION
		);

		wp_enqueue_script(
			'aicrmform-frontend',
			AICRMFORM_PLUGIN_URL . 'assets/js/form.js',
			[],
			AICRMFORM_VERSION,
			true
		);

		wp_localize_script(
			'aicrmform-frontend',
			'aicrmformConfig',
			[
				'restUrl' => rest_url( 'ai-crm-form/v1/' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			]
		);
	}

	/**
	 * Get plugin settings.
	 *
	 * @return array Plugin settings.
	 */
	public static function get_settings() {
		return get_option( 'aicrmform_settings', [] );
	}

	/**
	 * Update plugin settings.
	 *
	 * @param array $settings New settings to save.
	 * @return bool True if updated successfully.
	 */
	public static function update_settings( $settings ) {
		return update_option( 'aicrmform_settings', $settings );
	}
}

/**
 * Initialize plugin.
 *
 * @return AI_CRM_Form
 */
function aicrmform_init() {
	return AI_CRM_Form::get_instance();
}

// Start the plugin.
add_action( 'plugins_loaded', 'aicrmform_init' );

// Add settings link on plugins page.
add_filter(
	'plugin_action_links_' . AICRMFORM_PLUGIN_BASENAME,
	function ( $links ) {
		$settings_link = '<a href="' . admin_url( 'admin.php?page=ai-crm-form-settings' ) . '">' . __( 'Settings', 'ai-crm-form' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}
);

