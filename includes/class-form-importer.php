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
	 * AI Client instance.
	 *
	 * @var AICRMFORM_AI_Client|null
	 */
	private $ai_client;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->generator = new AICRMFORM_Form_Generator();
		$this->init_ai_client();
	}

	/**
	 * Initialize AI client if configured.
	 */
	private function init_ai_client() {
		$settings = get_option( 'aicrmform_settings', [] );
		$api_key  = $settings['ai_api_key'] ?? '';

		if ( ! empty( $api_key ) ) {
			$provider = $settings['ai_provider'] ?? 'groq';
			$model    = $settings['ai_model'] ?? 'llama-3.3-70b-versatile';
			$this->ai_client = new AICRMFORM_AI_Client( $api_key, $provider, $model );
		}
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
			// Get the hash if it exists.
			$hash = get_post_meta( $cf7_form->id(), '_hash', true );

			$forms[] = [
				'id'     => $cf7_form->id(),
				'hash'   => $hash ?: '',
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

		// Remove common prefixes like "your-", "user-", "contact-", etc.
		$clean_name = preg_replace( '/^(your|user|contact|my|the|form|field)[_-]?/i', '', $name_lower );

		// Mappings with keywords - ordered by priority.
		$mappings = [
			'first_name'            => [ 'firstname', 'first_name', 'first-name', 'fname', 'givenname', 'given_name' ],
			'last_name'             => [ 'lastname', 'last_name', 'last-name', 'lname', 'surname', 'familyname', 'family_name' ],
			'email'                 => [ 'email', 'mail', 'e-mail', 'email_address', 'emailaddress' ],
			'phone_number'          => [ 'phone', 'tel', 'telephone', 'mobile', 'cell', 'phonenumber', 'phone_number' ],
			'company_name'          => [ 'company', 'organization', 'org', 'business', 'companyname', 'company_name' ],
			'company_website'       => [ 'website', 'url', 'site', 'web', 'homepage' ],
			'primary_address_line1' => [ 'address', 'street', 'addr', 'address1', 'address_1', 'streetaddress' ],
			'primary_address_city'  => [ 'city', 'town', 'locality' ],
			'primary_address_state' => [ 'state', 'province', 'region', 'county' ],
			'primary_address_postal' => [ 'zip', 'zipcode', 'postal', 'postcode', 'postalcode', 'postal_code' ],
			'primary_address_country' => [ 'country', 'nation' ],
			'message'               => [ 'message', 'comment', 'comments', 'inquiry', 'question', 'body', 'content', 'text', 'note', 'notes', 'description' ],
			'source_name'           => [ 'source', 'referral', 'howdidyouhear', 'hearabout' ],
		];

		// First, try exact match with cleaned name.
		foreach ( $mappings as $crm_field => $keywords ) {
			foreach ( $keywords as $keyword ) {
				if ( $clean_name === $keyword ) {
					return $crm_field;
				}
			}
		}

		// Second, try substring match with cleaned name.
		foreach ( $mappings as $crm_field => $keywords ) {
			foreach ( $keywords as $keyword ) {
				if ( strpos( $clean_name, $keyword ) !== false || strpos( $keyword, $clean_name ) !== false ) {
					return $crm_field;
				}
			}
		}

		// Third, try substring match with original name.
		foreach ( $mappings as $crm_field => $keywords ) {
			foreach ( $keywords as $keyword ) {
				if ( strpos( $name_lower, $keyword ) !== false ) {
					return $crm_field;
				}
			}
		}

		// Special case: generic "name" field - map to first_name.
		if ( $clean_name === 'name' || $name_lower === 'name' || $name_lower === 'your-name' || $name_lower === 'fullname' || $name_lower === 'full_name' ) {
			return 'first_name';
		}

		// Special case: "subject" - map to message (as part of the inquiry).
		if ( strpos( $name_lower, 'subject' ) !== false ) {
			return 'message';
		}

		return '';
	}

	/**
	 * Use AI to intelligently map form fields to CRM fields.
	 *
	 * @param array $fields Array of form fields.
	 * @return array Updated fields with AI-suggested mappings.
	 */
	public function ai_map_fields( $fields ) {
		if ( ! $this->ai_client || ! $this->ai_client->isConfigured() ) {
			error_log( 'AI CRM Form: AI client not configured, using fallback mapping' );
			return $fields;
		}

		// Get available CRM fields.
		$available_crm_fields = $this->get_available_crm_fields_for_ai();

		// Build field info for AI prompt.
		$field_info = [];
		foreach ( $fields as $field ) {
			$field_info[] = [
				'name'  => $field['name'] ?? '',
				'label' => $field['label'] ?? '',
				'type'  => $field['type'] ?? 'text',
			];
		}

		// Build AI prompt.
		$prompt = $this->build_ai_mapping_prompt( $field_info, $available_crm_fields );

		// Set system instruction for mapping.
		$this->ai_client->setSystemInstruction(
			'You are a CRM field mapping expert. Your task is to map form fields to CRM fields. ' .
			'Respond ONLY with valid JSON, no explanation or markdown. ' .
			'For name fields that contain full names (like "your-name" or "full_name"), split them into first_name AND last_name. ' .
			'Be intelligent about mapping - "your-email" should map to "email", "your-name" should map to both "first_name" and "last_name".'
		);

		try {
			$response = $this->ai_client->chat( $prompt );

			if ( is_array( $response ) && isset( $response['error'] ) ) {
				error_log( 'AI CRM Form: AI mapping error - ' . $response['error'] );
				return $fields;
			}

			// Parse AI response.
			$mappings = $this->parse_ai_mapping_response( $response );

			if ( empty( $mappings ) ) {
				error_log( 'AI CRM Form: Could not parse AI mapping response' );
				return $fields;
			}

			// Apply mappings to fields.
			$fields = $this->apply_ai_mappings( $fields, $mappings );

			error_log( 'AI CRM Form: AI field mapping successful - ' . wp_json_encode( $mappings ) );

		} catch ( \Exception $e ) {
			error_log( 'AI CRM Form: AI mapping exception - ' . $e->getMessage() );
		}

		return $fields;
	}

	/**
	 * Get available CRM fields formatted for AI prompt.
	 *
	 * @return array CRM fields info.
	 */
	private function get_available_crm_fields_for_ai() {
		return [
			'first_name'              => 'First name of the contact',
			'last_name'               => 'Last name / surname of the contact',
			'email'                   => 'Email address',
			'phone_number'            => 'Phone number',
			'mobile_phone'            => 'Mobile phone number',
			'message'                 => 'Message, inquiry, comments, or any text content',
			'company_name'            => 'Company or organization name',
			'company_website'         => 'Company website URL',
			'primary_address_line1'   => 'Street address line 1',
			'primary_address_line2'   => 'Street address line 2',
			'primary_address_city'    => 'City',
			'primary_address_state'   => 'State or province',
			'primary_address_postal'  => 'Postal / ZIP code',
			'primary_address_country' => 'Country',
			'source_name'             => 'Lead source or how they heard about us',
			'utm_source'              => 'UTM source parameter',
			'utm_medium'              => 'UTM medium parameter',
			'utm_campaign'            => 'UTM campaign parameter',
		];
	}

	/**
	 * Build AI prompt for field mapping.
	 *
	 * @param array $field_info  Form field information.
	 * @param array $crm_fields  Available CRM fields.
	 * @return string The prompt.
	 */
	private function build_ai_mapping_prompt( $field_info, $crm_fields ) {
		$fields_json     = wp_json_encode( $field_info, JSON_PRETTY_PRINT );
		$crm_fields_json = wp_json_encode( $crm_fields, JSON_PRETTY_PRINT );

		return <<<PROMPT
Map these form fields to CRM fields. For each form field, determine which CRM field(s) it should map to.

IMPORTANT RULES:
1. If a field is a "name" or "full name" field (like "your-name"), it should be SPLIT into BOTH "first_name" and "last_name"
2. Subject fields should map to "message" 
3. Email fields should map to "email"
4. Phone/tel fields should map to "phone_number"
5. Message/comment/textarea fields should map to "message"
6. If a field doesn't clearly match any CRM field, use null

Form Fields:
{$fields_json}

Available CRM Fields:
{$crm_fields_json}

Respond with a JSON object where:
- Keys are the form field names
- Values are either:
  - A string (single CRM field name)
  - An array (for split mappings like ["first_name", "last_name"])
  - null (if no mapping)

Example response:
{
  "your-name": ["first_name", "last_name"],
  "your-email": "email",
  "your-subject": "message",
  "your-message": "message",
  "random-field": null
}

JSON response:
PROMPT;
	}

	/**
	 * Parse AI mapping response.
	 *
	 * @param string $response AI response.
	 * @return array Parsed mappings.
	 */
	private function parse_ai_mapping_response( $response ) {
		// Try to extract JSON from response.
		$json_start = strpos( $response, '{' );
		$json_end   = strrpos( $response, '}' );

		if ( false === $json_start || false === $json_end ) {
			return [];
		}

		$json_string = substr( $response, $json_start, $json_end - $json_start + 1 );
		$parsed      = json_decode( $json_string, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			error_log( 'AI CRM Form: JSON parse error - ' . json_last_error_msg() );
			return [];
		}

		return $parsed;
	}

	/**
	 * Apply AI mappings to form fields.
	 *
	 * @param array $fields   Original fields.
	 * @param array $mappings AI-suggested mappings.
	 * @return array Updated fields.
	 */
	private function apply_ai_mappings( $fields, $mappings ) {
		$updated_fields = [];

		foreach ( $fields as $field ) {
			$field_name = $field['name'] ?? '';

			if ( empty( $field_name ) || ! isset( $mappings[ $field_name ] ) ) {
				$updated_fields[] = $field;
				continue;
			}

			$mapping = $mappings[ $field_name ];

			// Handle split mappings (e.g., name -> first_name + last_name).
			if ( is_array( $mapping ) && count( $mapping ) > 1 ) {
				// This field needs to be split - mark it specially.
				$field['crm_mapping']       = $mapping[0]; // Primary mapping.
				$field['crm_mapping_split'] = $mapping;    // All mappings for splitting.
				$field['field_id']          = AICRMFORM_Field_Mapping::get_field_id( $mapping[0] );
				$updated_fields[] = $field;
			} elseif ( is_array( $mapping ) ) {
				// Single item array.
				$crm_field              = $mapping[0];
				$field['crm_mapping']   = $crm_field;
				$field['field_id']      = AICRMFORM_Field_Mapping::get_field_id( $crm_field );
				$updated_fields[] = $field;
			} elseif ( ! empty( $mapping ) ) {
				// String mapping.
				$field['crm_mapping'] = $mapping;
				$field['field_id']    = AICRMFORM_Field_Mapping::get_field_id( $mapping );
				$updated_fields[] = $field;
			} else {
				// No mapping.
				$updated_fields[] = $field;
			}
		}

		return $updated_fields;
	}

	/**
	 * Import a form from another plugin.
	 *
	 * @param string $plugin_key Plugin key (e.g., 'cf7').
	 * @param int    $form_id Source form ID.
	 * @param bool   $use_same_shortcode Whether to use same shortcode after import.
	 * @param string $crm_form_id Optional CRM Form ID override.
	 * @return array Result with success status and data.
	 */
	public function import_form( $plugin_key, $form_id, $use_same_shortcode = false, $crm_form_id = null ) {
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

		// Use AI to intelligently map fields if available.
		$fields_with_mapping = $this->ai_map_fields( $source_form['fields'] );

		// Build form config.
		$form_config = [
			'title'            => $source_form['title'],
			'fields'           => $fields_with_mapping,
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

		// Build field mapping (including split mappings for name fields).
		$field_mapping = [];
		foreach ( $fields_with_mapping as $field ) {
			$field_name = $field['name'] ?? '';
			if ( empty( $field_name ) ) {
				continue;
			}

			// Handle split mappings (e.g., name -> first_name + last_name).
			if ( ! empty( $field['crm_mapping_split'] ) && is_array( $field['crm_mapping_split'] ) ) {
				foreach ( $field['crm_mapping_split'] as $crm_field ) {
					$field_id = AICRMFORM_Field_Mapping::get_field_id( $crm_field );
					if ( $field_id ) {
						// Store the split mapping info.
						$field_mapping[ $field_name . '__split__' . $crm_field ] = $field_id;
					}
				}
				// Also store primary mapping.
				if ( ! empty( $field['field_id'] ) ) {
					$field_mapping[ $field_name ] = $field['field_id'];
				}
			} elseif ( ! empty( $field['field_id'] ) ) {
				$field_mapping[ $field_name ] = $field['field_id'];
			} elseif ( ! empty( $field['crm_mapping'] ) ) {
				$field_id = AICRMFORM_Field_Mapping::get_field_id( $field['crm_mapping'] );
				if ( $field_id ) {
					$field_mapping[ $field_name ] = $field_id;
				}
			}
		}

		// Get CRM form ID - use provided value or fall back to settings.
		if ( empty( $crm_form_id ) ) {
			$settings    = get_option( 'aicrmform_settings', [] );
			$crm_form_id = $settings['form_id'] ?? '';
		}

		// Validate CRM Form ID.
		if ( empty( $crm_form_id ) ) {
			return [
				'success' => false,
				'error'   => __( 'CRM Form ID is required. Please configure a default CRM Form ID in Settings or provide one during import.', 'ai-crm-form' ),
			];
		}

		// Validate CRM Form ID format.
		$pattern = '/^FormConfigID-[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i';
		if ( ! preg_match( $pattern, $crm_form_id ) ) {
			return [
				'success' => false,
				'error'   => __( 'Invalid CRM Form ID format. Expected: FormConfigID-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx', 'ai-crm-form' ),
			];
		}

		// Save the form.
		$result = $this->generator->save_form(
			$form_config,
			$crm_form_id,
			$source_form['title'] . ' (Imported)',
			'Imported from ' . $this->supported_plugins[ $plugin_key ]['name']
		);

		// Check if save was successful.
		if ( ! $result['success'] ) {
			return [
				'success' => false,
				'error'   => $result['error'] ?? __( 'Failed to import form.', 'ai-crm-form' ),
			];
		}

		$response = [
			'success'        => true,
			'form_id'        => $result['form_id'],
			'message'        => __( 'Form imported successfully!', 'ai-crm-form' ),
			'source_plugin'  => $plugin_key,
			'source_form_id' => $form_id,
		];

		// If using same shortcode, store the mapping.
		if ( $use_same_shortcode ) {
			$shortcode_map = get_option( 'aicrmform_shortcode_map', [] );

			// Store mapping by post ID.
			$shortcode_map[ $plugin_key . '_' . $form_id ] = $result['form_id'];

			// Also store mapping by hash if available.
			if ( ! empty( $source_form['hash'] ) ) {
				$shortcode_map[ $plugin_key . '_hash_' . $source_form['hash'] ] = $result['form_id'];
			}

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
