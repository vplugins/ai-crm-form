<?php
/**
 * Form Integration Interface
 *
 * Defines the contract for all form plugin integrations.
 *
 * @package AI_CRM_Form
 * @subpackage Integrations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface for Form Integration Adapters
 */
interface AICRMFORM_Form_Integration_Interface {

	/**
	 * Get the integration key/identifier.
	 *
	 * @return string Unique key for this integration (e.g., 'cf7', 'gravity').
	 */
	public function get_key();

	/**
	 * Get the display name of the form plugin.
	 *
	 * @return string Human-readable name (e.g., 'Contact Form 7').
	 */
	public function get_name();

	/**
	 * Get the plugin slug for detection.
	 *
	 * @return string Plugin file path (e.g., 'contact-form-7/wp-contact-form-7.php').
	 */
	public function get_plugin_slug();

	/**
	 * Get the class name to check for plugin availability.
	 *
	 * @return string Class name to check if exists.
	 */
	public function get_check_class();

	/**
	 * Check if the form plugin is active and available.
	 *
	 * @return bool True if available, false otherwise.
	 */
	public function is_available();

	/**
	 * Get all forms from the plugin.
	 *
	 * @return array Array of form data with id, title, and fields.
	 */
	public function get_forms();

	/**
	 * Get a specific form by ID.
	 *
	 * @param int|string $form_id The form ID.
	 * @return array|null Form data or null if not found.
	 */
	public function get_form( $form_id );

	/**
	 * Parse form fields from the source form.
	 *
	 * @param mixed $form The source form object/data.
	 * @return array Array of parsed field data.
	 */
	public function parse_fields( $form );

	/**
	 * Parse a single field from the source form.
	 *
	 * @param mixed  $field     The source field object/data.
	 * @param string $type      The field type.
	 * @param string $attrs     Additional attributes.
	 * @return array|null Parsed field data or null.
	 */
	public function parse_field( $field, $type = '', $attrs = '' );
}

