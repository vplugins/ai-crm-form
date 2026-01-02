<?php
/**
 * AI Form Generator
 *
 * @package AI_CRM_Form
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIEngine\AIEngine;

/**
 * Form Generator Class
 *
 * Uses AI to generate form configurations based on user requirements.
 */
class AICRMFORM_Form_Generator {

	/**
	 * AI Engine instance.
	 *
	 * @var AIEngine|null
	 */
	private $ai_engine = null;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_ai_engine();
	}

	/**
	 * Initialize AI Engine.
	 */
	private function init_ai_engine() {
		$settings = get_option( 'aicrmform_settings', [] );

		$api_key  = $settings['api_key'] ?? '';
		$provider = $settings['ai_provider'] ?? 'groq';
		$model    = $settings['ai_model'] ?? 'llama-3.3-70b-versatile';

		if ( empty( $api_key ) ) {
			return;
		}

		try {
			$this->ai_engine = new AIEngine(
				$api_key,
				[
					'provider' => $provider,
					'model'    => $model,
					'timeout'  => 60,
				]
			);

			// Set system instruction for form generation.
			$system_instruction = $this->get_system_instruction();
			$this->ai_engine->setSystemInstruction( $system_instruction );
		} catch ( \Exception $e ) {
			error_log( 'AI CRM Form: Failed to initialize AI Engine - ' . $e->getMessage() );
		}
	}

	/**
	 * Get system instruction for AI form generation.
	 *
	 * @return string The system instruction.
	 */
	private function get_system_instruction() {
		$field_definitions = AICRMFORM_Field_Mapping::get_field_definitions_for_ai();
		$fields_json       = wp_json_encode( $field_definitions, JSON_PRETTY_PRINT );

		return <<<INSTRUCTION
You are an AI form generator assistant. Your task is to create form configurations based on user requirements.

Available CRM fields that you can use:
{$fields_json}

When generating a form, you MUST respond with a valid JSON object in the following structure:
{
  "form_name": "Form Name",
  "form_description": "Description of the form",
  "fields": [
    {
      "name": "field_name",
      "field_id": "FieldID-xxx",
      "label": "Field Label",
      "type": "text|email|tel|textarea|select|checkbox|radio|number|date|url",
      "required": true|false,
      "placeholder": "Optional placeholder text",
      "options": ["option1", "option2"] // Only for select, checkbox, radio types
    }
  ],
  "submit_button_text": "Submit",
  "success_message": "Thank you for your submission!"
}

Rules:
1. Always use the exact field_id values from the available fields list when mapping to CRM fields.
2. For fields not in the CRM list, use a custom field name without field_id.
3. Include appropriate validation based on field type (email, tel, etc.).
4. Make the form user-friendly with clear labels and placeholders.
5. ONLY respond with valid JSON. No additional text or explanation.
INSTRUCTION;
	}

	/**
	 * Check if AI is configured.
	 *
	 * @return bool True if AI is configured.
	 */
	public function is_configured() {
		return $this->ai_engine !== null && $this->ai_engine->isConfigured();
	}

	/**
	 * Generate form configuration from user prompt.
	 *
	 * @param string $prompt User's description of the form they want.
	 * @return array Response with form configuration or error.
	 */
	public function generate_form( $prompt ) {
		if ( ! $this->is_configured() ) {
			return [
				'success' => false,
				'error'   => __( 'AI is not configured. Please add your API key in settings.', 'ai-crm-form' ),
			];
		}

		try {
			// Generate form using AI.
			$response = $this->ai_engine->chat( $prompt );

			if ( is_array( $response ) && isset( $response['error'] ) ) {
				return [
					'success' => false,
					'error'   => $response['error'],
				];
			}

			// Parse the JSON response.
			$form_config = $this->parse_form_response( $response );

			if ( ! $form_config ) {
				return [
					'success'      => false,
					'error'        => __( 'Failed to parse AI response. Please try again.', 'ai-crm-form' ),
					'raw_response' => $response,
				];
			}

			return [
				'success'     => true,
				'form_config' => $form_config,
			];
		} catch ( \Exception $e ) {
			return [
				'success' => false,
				'error'   => $e->getMessage(),
			];
		}
	}

	/**
	 * Parse form response from AI.
	 *
	 * @param string $response The AI response.
	 * @return array|null Parsed form configuration or null on failure.
	 */
	private function parse_form_response( $response ) {
		// Try to extract JSON from the response.
		$json_start = strpos( $response, '{' );
		$json_end   = strrpos( $response, '}' );

		if ( $json_start === false || $json_end === false ) {
			return null;
		}

		$json_string = substr( $response, $json_start, $json_end - $json_start + 1 );
		$parsed      = json_decode( $json_string, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return null;
		}

		// Validate required fields.
		if ( ! isset( $parsed['fields'] ) || ! is_array( $parsed['fields'] ) ) {
			return null;
		}

		return $parsed;
	}

	/**
	 * Generate form HTML from configuration.
	 *
	 * @param array  $form_config The form configuration.
	 * @param string $form_id     The form ID for submission.
	 * @return string The generated HTML.
	 */
	public function generate_form_html( $form_config, $form_id ) {
		$fields = $form_config['fields'] ?? [];
		$html   = '<form class="aicrmform-form" data-form-id="' . esc_attr( $form_id ) . '">';

		foreach ( $fields as $field ) {
			$html .= $this->generate_field_html( $field );
		}

		$submit_text = $form_config['submit_button_text'] ?? __( 'Submit', 'ai-crm-form' );
		$html       .= '<div class="aicrmform-field aicrmform-submit">';
		$html       .= '<button type="submit" class="aicrmform-button">' . esc_html( $submit_text ) . '</button>';
		$html       .= '</div>';

		$html .= '</form>';

		return $html;
	}

	/**
	 * Generate HTML for a single field.
	 *
	 * @param array $field The field configuration.
	 * @return string The field HTML.
	 */
	private function generate_field_html( $field ) {
		$name        = esc_attr( $field['name'] ?? '' );
		$field_id    = esc_attr( $field['field_id'] ?? $name );
		$label       = esc_html( $field['label'] ?? ucfirst( $name ) );
		$type        = $field['type'] ?? 'text';
		$required    = ! empty( $field['required'] );
		$placeholder = esc_attr( $field['placeholder'] ?? '' );
		$req_attr    = $required ? 'required' : '';
		$req_mark    = $required ? '<span class="aicrmform-required">*</span>' : '';

		$html  = '<div class="aicrmform-field aicrmform-field-' . esc_attr( $type ) . '">';
		$html .= '<label for="' . $name . '">' . $label . $req_mark . '</label>';

		switch ( $type ) {
			case 'textarea':
				$html .= '<textarea id="' . $name . '" name="' . $name . '" data-field-id="' . $field_id . '" placeholder="' . $placeholder . '" ' . $req_attr . '></textarea>';
				break;

			case 'select':
				$html   .= '<select id="' . $name . '" name="' . $name . '" data-field-id="' . $field_id . '" ' . $req_attr . '>';
				$html   .= '<option value="">' . esc_html__( 'Select...', 'ai-crm-form' ) . '</option>';
				$options = $field['options'] ?? [];
				foreach ( $options as $option ) {
					$html .= '<option value="' . esc_attr( $option ) . '">' . esc_html( $option ) . '</option>';
				}
				$html .= '</select>';
				break;

			case 'checkbox':
				$options = $field['options'] ?? [ $label ];
				foreach ( $options as $option ) {
					$html .= '<label class="aicrmform-checkbox-label">';
					$html .= '<input type="checkbox" name="' . $name . '[]" value="' . esc_attr( $option ) . '" data-field-id="' . $field_id . '">';
					$html .= '<span>' . esc_html( $option ) . '</span>';
					$html .= '</label>';
				}
				break;

			case 'radio':
				$options = $field['options'] ?? [];
				foreach ( $options as $option ) {
					$html .= '<label class="aicrmform-radio-label">';
					$html .= '<input type="radio" name="' . $name . '" value="' . esc_attr( $option ) . '" data-field-id="' . $field_id . '" ' . $req_attr . '>';
					$html .= '<span>' . esc_html( $option ) . '</span>';
					$html .= '</label>';
				}
				break;

			default:
				$html .= '<input type="' . esc_attr( $type ) . '" id="' . $name . '" name="' . $name . '" data-field-id="' . $field_id . '" placeholder="' . $placeholder . '" ' . $req_attr . '>';
				break;
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Save form to database.
	 *
	 * @param array  $form_config  The form configuration.
	 * @param string $crm_form_id  The CRM form ID.
	 * @param string $name         Optional form name override.
	 * @param string $description  Optional form description override.
	 * @return array Result with success status, form_id or error.
	 */
	public function save_form( $form_config, $crm_form_id, $name = null, $description = null ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'aicrmform_forms';

		// Check if table exists.
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! $table_exists ) {
			// Try to create the table.
			if ( function_exists( 'aicrmform_init' ) ) {
				$plugin = aicrmform_init();
				$plugin->maybe_create_tables();
			}

			// Check again.
			$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ( ! $table_exists ) {
				return [
					'success' => false,
					'error'   => __( 'Database table does not exist. Please deactivate and reactivate the plugin.', 'ai-crm-form' ),
				];
			}
		}

		// Validate form_config.
		if ( ! is_array( $form_config ) || empty( $form_config['fields'] ) ) {
			return [
				'success' => false,
				'error'   => __( 'Invalid form configuration.', 'ai-crm-form' ),
			];
		}

		// Extract field mapping from config.
		$field_mapping = [];
		foreach ( $form_config['fields'] as $field ) {
			if ( ! empty( $field['field_id'] ) && ! empty( $field['name'] ) ) {
				$field_mapping[ $field['name'] ] = $field['field_id'];
			}
		}

		$form_name        = $name ?? ( $form_config['form_name'] ?? __( 'Untitled Form', 'ai-crm-form' ) );
		$form_description = $description ?? ( $form_config['form_description'] ?? '' );

		$result = $wpdb->insert(
			$table_name,
			[
				'name'          => $form_name,
				'description'   => $form_description,
				'form_config'   => wp_json_encode( $form_config ),
				'field_mapping' => wp_json_encode( $field_mapping ),
				'crm_form_id'   => $crm_form_id,
				'status'        => 'active',
			],
			[
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			]
		);

		if ( $result ) {
			return [
				'success' => true,
				'form_id' => $wpdb->insert_id,
			];
		}

		// Log the error for debugging.
		error_log( 'AI CRM Form: Failed to save form. DB Error: ' . $wpdb->last_error );

		return [
			'success' => false,
			'error'   => __( 'Failed to save form to database.', 'ai-crm-form' ) . ( $wpdb->last_error ? ' ' . $wpdb->last_error : '' ),
		];
	}

	/**
	 * Update form in database.
	 *
	 * @param int   $form_id     The form ID.
	 * @param array $form_config The form configuration.
	 * @param array $updates     Additional fields to update (name, description, crm_form_id, status).
	 * @return bool True on success.
	 */
	public function update_form( $form_id, $form_config, $updates = [] ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'aicrmform_forms';

		// Extract field mapping from config.
		$field_mapping = [];
		if ( isset( $form_config['fields'] ) && is_array( $form_config['fields'] ) ) {
			foreach ( $form_config['fields'] as $field ) {
				if ( ! empty( $field['field_id'] ) && ! empty( $field['name'] ) ) {
					$field_mapping[ $field['name'] ] = $field['field_id'];
				}
			}
		}

		// Base data with form config and field mapping.
		$data = [
			'form_config'   => wp_json_encode( $form_config ),
			'field_mapping' => wp_json_encode( $field_mapping ),
		];

		// Add allowed update fields.
		$allowed_fields = [ 'name', 'description', 'crm_form_id', 'status' ];
		foreach ( $allowed_fields as $field ) {
			if ( isset( $updates[ $field ] ) ) {
				$data[ $field ] = $updates[ $field ];
			}
		}

		$result = $wpdb->update(
			$table_name,
			$data,
			[ 'id' => $form_id ],
			array_fill( 0, count( $data ), '%s' ),
			[ '%d' ]
		);

		return $result !== false;
	}

	/**
	 * Get form by ID.
	 *
	 * @param int $form_id The form ID.
	 * @return object|null The form object or null.
	 */
	public function get_form( $form_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'aicrmform_forms';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$form = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$form_id
			)
		);

		if ( $form ) {
			$form->form_config   = json_decode( $form->form_config, true );
			$form->field_mapping = json_decode( $form->field_mapping, true );
		}

		return $form;
	}

	/**
	 * Get all forms.
	 *
	 * @param string $status Optional status filter.
	 * @return array Array of form objects.
	 */
	public function get_all_forms( $status = null ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'aicrmform_forms';

		if ( $status ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$forms = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table_name} WHERE status = %s ORDER BY created_at DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$status
				)
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$forms = $wpdb->get_results(
				"SELECT * FROM {$table_name} ORDER BY created_at DESC" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			);
		}

		foreach ( $forms as $form ) {
			$form->form_config   = json_decode( $form->form_config, true );
			$form->field_mapping = json_decode( $form->field_mapping, true );
		}

		return $forms;
	}

	/**
	 * Delete form.
	 *
	 * @param int $form_id The form ID.
	 * @return bool True on success.
	 */
	public function delete_form( $form_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'aicrmform_forms';

		$result = $wpdb->delete(
			$table_name,
			[ 'id' => $form_id ],
			[ '%d' ]
		);

		return $result !== false;
	}

	/**
	 * Refine existing form with AI.
	 *
	 * @param array  $current_config Current form configuration.
	 * @param string $refinement     User's refinement request.
	 * @return array Response with updated form configuration or error.
	 */
	public function refine_form( $current_config, $refinement ) {
		if ( ! $this->is_configured() ) {
			return [
				'success' => false,
				'error'   => __( 'AI is not configured. Please add your API key in settings.', 'ai-crm-form' ),
			];
		}

		$current_json = wp_json_encode( $current_config, JSON_PRETTY_PRINT );

		$prompt = <<<PROMPT
Current form configuration:
{$current_json}

Please modify the form based on this request: {$refinement}

Respond with the complete updated form configuration in JSON format.
PROMPT;

		return $this->generate_form( $prompt );
	}
}

