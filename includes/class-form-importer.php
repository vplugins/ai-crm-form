<?php
/**
 * Form Importer Class
 *
 * Handles importing forms from other plugins like Contact Form 7.
 *
 * @package AI_CRM_Form
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Form Importer Class
 */
class AICRMFORM_Form_Importer {

	/**
	 * Supported plugins for import.
	 *
	 * @var array
	 */
	private $supported_plugins = [
		'cf7' => [
			'name'        => 'Contact Form 7',
			'slug'        => 'contact-form-7/wp-contact-form-7.php',
			'check_class' => 'WPCF7_ContactForm',
		],
		// Future plugins can be added here.
		// 'gravity' => [
		//     'name'        => 'Gravity Forms',
		//     'slug'        => 'gravityforms/gravityforms.php',
		//     'check_class' => 'GFForms',
		// ],
	];

	/**
	 * Form Generator instance.
	 *
	 * @var AICRMFORM_Form_Generator
	 */
	private $generator;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->generator = new AICRMFORM_Form_Generator();
	}

	/**
	 * Get available plugins for import.
	 *
	 * @return array List of available plugins.
	 */
	public function get_available_plugins() {
		$available = [];

		foreach ( $this->supported_plugins as $key => $plugin ) {
			$is_active = class_exists( $plugin['check_class'] );
			$available[ $key ] = [
				'name'      => $plugin['name'],
				'slug'      => $plugin['slug'],
				'active'    => $is_active,
				'forms'     => $is_active ? $this->get_plugin_forms( $key ) : [],
			];
		}

		return $available;
	}

	/**
	 * Get forms from a specific plugin.
	 *
	 * @param string $plugin_key Plugin key.
	 * @return array List of forms.
	 */
	public function get_plugin_forms( $plugin_key ) {
		switch ( $plugin_key ) {
			case 'cf7':
				return $this->get_cf7_forms();
			default:
				return [];
		}
	}

	/**
	 * Get Contact Form 7 forms.
	 *
	 * @return array List of CF7 forms.
	 */
	private function get_cf7_forms() {
		if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
			return [];
		}

		$forms = [];
		$cf7_forms = WPCF7_ContactForm::find();

		foreach ( $cf7_forms as $cf7_form ) {
			$forms[] = [
				'id'     => $cf7_form->id(),
				'title'  => $cf7_form->title(),
				'fields' => $this->parse_cf7_form( $cf7_form ),
			];
		}

		return $forms;
	}

	/**
	 * Parse CF7 form to extract fields.
	 *
	 * @param WPCF7_ContactForm $cf7_form CF7 form object.
	 * @return array Parsed fields.
	 */
	private function parse_cf7_form( $cf7_form ) {
		$fields = [];
		$form_content = $cf7_form->prop( 'form' );

		// Parse CF7 shortcode tags.
		$pattern = '/\[(\w+)(?:\*)?(?:\s+([^\]]+))?\]/';
		preg_match_all( $pattern, $form_content, $matches, PREG_SET_ORDER );

		foreach ( $matches as $match ) {
			$tag_type = $match[1];
			$attrs_string = isset( $match[2] ) ? $match[2] : '';

			// Skip submit buttons for now.
			if ( 'submit' === $tag_type ) {
				continue;
			}

			$field = $this->parse_cf7_tag( $tag_type, $attrs_string );
			if ( $field ) {
				$fields[] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Parse a CF7 tag into our field format.
	 *
	 * @param string $tag_type Tag type.
	 * @param string $attrs_string Attributes string.
	 * @return array|null Parsed field or null.
	 */
	private function parse_cf7_tag( $tag_type, $attrs_string ) {
		// Parse attributes.
		$attrs = [];
		$name = '';
		$options = [];

		// Split by whitespace.
		$parts = preg_split( '/\s+/', trim( $attrs_string ) );

		foreach ( $parts as $part ) {
			if ( empty( $part ) ) {
				continue;
			}

			// Check for name (first part without special chars).
			if ( empty( $name ) && preg_match( '/^[a-zA-Z_][a-zA-Z0-9_-]*$/', $part ) ) {
				$name = $part;
				continue;
			}

			// Check for placeholder.
			if ( preg_match( '/^placeholder:(.+)$/', $part, $m ) ) {
				$attrs['placeholder'] = str_replace( '"', '', $m[1] );
				continue;
			}

			// Check for class.
			if ( preg_match( '/^class:(.+)$/', $part, $m ) ) {
				$attrs['class'] = $m[1];
				continue;
			}

			// Check for id.
			if ( preg_match( '/^id:(.+)$/', $part, $m ) ) {
				$attrs['id'] = $m[1];
				continue;
			}

			// Check for options (quoted strings).
			if ( preg_match( '/^"([^"]+)"$/', $part, $m ) ) {
				$options[] = $m[1];
			}
		}

		// Map CF7 types to our types.
		$type_map = [
			'text'     => 'text',
			'email'    => 'email',
			'tel'      => 'tel',
			'url'      => 'url',
			'textarea' => 'textarea',
			'select'   => 'select',
			'checkbox' => 'checkbox',
			'radio'    => 'radio',
			'number'   => 'number',
			'date'     => 'date',
			'file'     => 'file',
		];

		// Handle required fields (marked with *).
		$is_required = strpos( $tag_type, '*' ) !== false;
		$base_type = str_replace( '*', '', $tag_type );

		if ( ! isset( $type_map[ $base_type ] ) ) {
			return null;
		}

		$field = [
			'type'        => $type_map[ $base_type ],
			'name'        => $name ?: 'field_' . wp_generate_password( 6, false ),
			'label'       => ucfirst( str_replace( [ '-', '_' ], ' ', $name ) ),
			'placeholder' => $attrs['placeholder'] ?? '',
			'required'    => $is_required,
			'crm_mapping' => $this->guess_crm_mapping( $name ),
		];

		// Add options for select/checkbox/radio.
		if ( in_array( $base_type, [ 'select', 'checkbox', 'radio' ], true ) && ! empty( $options ) ) {
			$field['options'] = $options;
		}

		return $field;
	}

	/**
	 * Guess CRM mapping based on field name.
	 *
	 * @param string $field_name Field name.
	 * @return string CRM mapping or empty.
	 */
	private function guess_crm_mapping( $field_name ) {
		$name_lower = strtolower( $field_name );

		$mappings = [
			'first_name'  => [ 'first', 'firstname', 'first_name', 'fname' ],
			'last_name'   => [ 'last', 'lastname', 'last_name', 'lname', 'surname' ],
			'email'       => [ 'email', 'mail', 'e-mail', 'email_address' ],
			'phone'       => [ 'phone', 'tel', 'telephone', 'mobile', 'cell' ],
			'company'     => [ 'company', 'organization', 'org', 'business' ],
			'website'     => [ 'website', 'url', 'site', 'web' ],
			'address'     => [ 'address', 'street', 'addr' ],
			'city'        => [ 'city', 'town' ],
			'state'       => [ 'state', 'province', 'region' ],
			'zip'         => [ 'zip', 'zipcode', 'postal', 'postcode' ],
			'country'     => [ 'country', 'nation' ],
			'message'     => [ 'message', 'comment', 'comments', 'inquiry', 'question' ],
		];

		foreach ( $mappings as $crm_field => $keywords ) {
			foreach ( $keywords as $keyword ) {
				if ( strpos( $name_lower, $keyword ) !== false ) {
					return $crm_field;
				}
			}
		}

		return '';
	}

	/**
	 * Import a form from another plugin.
	 *
	 * @param string $plugin_key Plugin key (e.g., 'cf7').
	 * @param int    $form_id Source form ID.
	 * @param bool   $use_same_shortcode Whether to use same shortcode after import.
	 * @return array Result with success status and data.
	 */
	public function import_form( $plugin_key, $form_id, $use_same_shortcode = false ) {
		// Get the source form.
		$forms = $this->get_plugin_forms( $plugin_key );
		$source_form = null;

		foreach ( $forms as $form ) {
			if ( (int) $form['id'] === (int) $form_id ) {
				$source_form = $form;
				break;
			}
		}

		if ( ! $source_form ) {
			return [
				'success' => false,
				'error'   => __( 'Source form not found.', 'ai-crm-form' ),
			];
		}

		// Build form config.
		$form_config = [
			'title'            => $source_form['title'],
			'fields'           => $source_form['fields'],
			'submit_text'      => __( 'Submit', 'ai-crm-form' ),
			'success_message'  => __( 'Thank you for your submission!', 'ai-crm-form' ),
			'error_message'    => __( 'Something went wrong. Please try again.', 'ai-crm-form' ),
			'styles'           => [
				'form_width'     => '100%',
				'primary_color'  => '#0073aa',
				'border_radius'  => '4px',
				'label_position' => 'top',
			],
		];

		// Build field mapping.
		$field_mapping = [];
		foreach ( $source_form['fields'] as $field ) {
			if ( ! empty( $field['crm_mapping'] ) ) {
				$field_mapping[ $field['name'] ] = $field['crm_mapping'];
			}
		}

		// Get settings for CRM form ID.
		$settings = get_option( 'aicrmform_settings', [] );
		$crm_form_id = $settings['crm_form_id'] ?? '';

		// Save the form.
		$result = $this->generator->save_form(
			$source_form['title'] . ' (Imported)',
			'Imported from ' . $this->supported_plugins[ $plugin_key ]['name'],
			$form_config,
			$field_mapping,
			$crm_form_id
		);

		if ( is_wp_error( $result ) ) {
			return [
				'success' => false,
				'error'   => $result->get_error_message(),
			];
		}

		$response = [
			'success'        => true,
			'form_id'        => $result,
			'message'        => __( 'Form imported successfully!', 'ai-crm-form' ),
			'source_plugin'  => $plugin_key,
			'source_form_id' => $form_id,
		];

		// If using same shortcode, store the mapping.
		if ( $use_same_shortcode ) {
			$shortcode_map = get_option( 'aicrmform_shortcode_map', [] );
			$shortcode_map[ $plugin_key . '_' . $form_id ] = $result;
			update_option( 'aicrmform_shortcode_map', $shortcode_map );
			$response['shortcode_mapped'] = true;
		}

		return $response;
	}

	/**
	 * Import multiple forms.
	 *
	 * @param string $plugin_key Plugin key.
	 * @param array  $form_ids Array of form IDs to import.
	 * @return array Results for each form.
	 */
	public function bulk_import_forms( $plugin_key, $form_ids ) {
		$results = [];

		foreach ( $form_ids as $form_id ) {
			$results[ $form_id ] = $this->import_form( $plugin_key, $form_id );
		}

		return $results;
	}
}

