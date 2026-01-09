<?php
/**
 * WPForms Integration
 *
 * Handles importing and parsing forms from WPForms.
 *
 * @package AI_CRM_Form
 * @subpackage Integrations\WPForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPForms Integration Adapter Class
 */
class AICRMFORM_WPForms_Integration extends AICRMFORM_Form_Integration_Abstract {

	/**
	 * Integration key.
	 *
	 * @var string
	 */
	protected $key = 'wpforms';

	/**
	 * Integration name.
	 *
	 * @var string
	 */
	protected $name = 'WPForms';

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	protected $plugin_slug = 'wpforms-lite/wpforms.php';

	/**
	 * Class to check for availability.
	 *
	 * @var string
	 */
	protected $check_class = 'WPForms';

	/**
	 * Field type mappings from WPForms to our format.
	 *
	 * @var array
	 */
	protected $type_map = [
		'text'              => 'text',
		'textarea'          => 'textarea',
		'select'            => 'select',
		'radio'             => 'radio',
		'checkbox'          => 'checkbox',
		'email'             => 'email',
		'url'               => 'url',
		'name'              => 'name',
		'phone'             => 'tel',
		'address'           => 'address',
		'number'            => 'number',
		'number-slider'     => 'number',
		'date-time'         => 'date',
		'file-upload'       => 'file',
		'hidden'            => 'hidden',
		'html'              => 'html',
		'content'           => 'html',
		'pagebreak'         => 'page',
		'divider'           => 'section',
		'password'          => 'password',
		'payment-single'    => 'text',
		'payment-multiple'  => 'radio',
		'payment-checkbox'  => 'checkbox',
		'payment-select'    => 'select',
		'payment-total'     => 'text',
		'gdpr-checkbox'     => 'checkbox',
		'rating'            => 'number',
		'likert_scale'      => 'radio',
		'net_promoter_score' => 'radio',
		'richtext'          => 'textarea',
	];

	/**
	 * Check if WPForms is available.
	 *
	 * Also checks for WPForms Pro.
	 *
	 * @return bool
	 */
	public function is_available() {
		return class_exists( 'WPForms' ) || function_exists( 'wpforms' );
	}

	/**
	 * Get all WPForms forms.
	 *
	 * @return array List of forms.
	 */
	public function get_forms() {
		if ( ! $this->is_available() ) {
			return [];
		}

		$forms       = [];
		$wpforms_obj = wpforms()->get( 'form' );

		if ( ! $wpforms_obj ) {
			return [];
		}

		$wpforms_forms = $wpforms_obj->get();

		if ( empty( $wpforms_forms ) || ! is_array( $wpforms_forms ) ) {
			return [];
		}

		foreach ( $wpforms_forms as $wpform ) {
			$form_data = $this->decode_form_data( $wpform );

			if ( ! $form_data ) {
				continue;
			}

			$forms[] = [
				'id'          => $wpform->ID,
				'title'       => $wpform->post_title,
				'description' => $form_data['settings']['form_desc'] ?? '',
				'fields'      => $this->parse_fields( $form_data ),
			];
		}

		return $forms;
	}

	/**
	 * Get a specific form by ID.
	 *
	 * @param int|string $form_id The form ID.
	 * @return array|null Form data or null if not found.
	 */
	public function get_form( $form_id ) {
		if ( ! $this->is_available() ) {
			return null;
		}

		$wpforms_obj = wpforms()->get( 'form' );

		if ( ! $wpforms_obj ) {
			return null;
		}

		$wpform = $wpforms_obj->get( absint( $form_id ) );

		if ( ! $wpform ) {
			return null;
		}

		$form_data = $this->decode_form_data( $wpform );

		if ( ! $form_data ) {
			return null;
		}

		return [
			'id'          => $wpform->ID,
			'title'       => $wpform->post_title,
			'description' => $form_data['settings']['form_desc'] ?? '',
			'fields'      => $this->parse_fields( $form_data ),
		];
	}

	/**
	 * Decode form data from WPForms post content.
	 *
	 * @param WP_Post $wpform The WPForms post object.
	 * @return array|null Decoded form data or null.
	 */
	private function decode_form_data( $wpform ) {
		if ( empty( $wpform->post_content ) ) {
			return null;
		}

		$form_data = json_decode( $wpform->post_content, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return null;
		}

		return $form_data;
	}

	/**
	 * Parse fields from a WPForms form.
	 *
	 * @param array $form WPForms form data array.
	 * @return array Parsed fields.
	 */
	public function parse_fields( $form ) {
		$fields = [];

		if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
			return $fields;
		}

		foreach ( $form['fields'] as $wpf_field ) {
			$parsed = $this->parse_field( $wpf_field );

			if ( $parsed ) {
				// Handle name field (splits into first/last).
				if ( 'name' === ( $wpf_field['type'] ?? '' ) ) {
					$name_fields = $this->parse_name_field( $wpf_field );
					$fields      = array_merge( $fields, $name_fields );
				} elseif ( 'address' === ( $wpf_field['type'] ?? '' ) ) {
					// Handle address field (splits into multiple).
					$address_fields = $this->parse_address_field( $wpf_field );
					$fields         = array_merge( $fields, $address_fields );
				} else {
					$fields[] = $parsed;
				}
			}
		}

		return $fields;
	}

	/**
	 * Parse a single WPForms field.
	 *
	 * @param array  $field The WPForms field array.
	 * @param string $type  Optional type override.
	 * @param string $attrs Optional attributes (unused for WPForms).
	 * @return array|null Parsed field data or null.
	 */
	public function parse_field( $field, $type = '', $attrs = '' ) {
		$field_type = $field['type'] ?? '';
		$field_id   = $field['id'] ?? '';

		// Skip certain field types.
		$skip_types = [ 'html', 'content', 'pagebreak', 'divider', 'captcha', 'turnstile' ];
		if ( in_array( $field_type, $skip_types, true ) ) {
			return null;
		}

		$mapped_type = $this->map_field_type( $field_type );

		if ( ! $mapped_type ) {
			return null;
		}

		$label = $field['label'] ?? '';
		$name  = $this->generate_field_name( $field );

		$field_data = [
			'type'           => $mapped_type,
			'name'           => $name,
			'label'          => $label ?: $this->generate_label( $name ),
			'placeholder'    => $field['placeholder'] ?? '',
			'required'       => ! empty( $field['required'] ),
			'crm_mapping'    => $this->guess_crm_mapping( $name ),
			'wpforms_field_id' => $field_id,
		];

		// Add description if present.
		if ( ! empty( $field['description'] ) ) {
			$field_data['description'] = $field['description'];
		}

		// Add choices for select/checkbox/radio.
		if ( in_array( $field_type, [ 'select', 'checkbox', 'radio', 'payment-multiple', 'payment-checkbox', 'payment-select' ], true ) ) {
			if ( ! empty( $field['choices'] ) ) {
				$field_data['options'] = $this->parse_choices( $field['choices'] );
			}
		}

		// Add CSS class if present.
		if ( ! empty( $field['css'] ) ) {
			$field_data['class'] = $field['css'];
		}

		// Add size if present.
		if ( ! empty( $field['size'] ) ) {
			$field_data['size'] = $field['size'];
		}

		// Handle limit length.
		if ( ! empty( $field['limit_count'] ) ) {
			$field_data['maxlength'] = absint( $field['limit_count'] );
		}

		// Handle default value.
		if ( ! empty( $field['default_value'] ) ) {
			$field_data['default'] = $field['default_value'];
		}

		return $field_data;
	}

	/**
	 * Parse a name field into separate first/last name fields.
	 *
	 * @param array $field The name field.
	 * @return array Array of parsed fields.
	 */
	private function parse_name_field( $field ) {
		$fields   = [];
		$field_id = $field['id'] ?? '';
		$format   = $field['format'] ?? 'first-last';

		// Define sub-fields based on format.
		$sub_fields = [];

		switch ( $format ) {
			case 'simple':
				// Simple format is just one field.
				return [
					[
						'type'             => 'text',
						'name'             => 'wpforms_' . $field_id . '_name',
						'label'            => $field['label'] ?? 'Name',
						'required'         => ! empty( $field['required'] ),
						'crm_mapping'      => 'first_name',
						'wpforms_field_id' => $field_id,
					],
				];

			case 'first-last':
				$sub_fields = [
					'first' => [ 'label' => 'First', 'mapping' => 'first_name' ],
					'last'  => [ 'label' => 'Last', 'mapping' => 'last_name' ],
				];
				break;

			case 'first-middle-last':
				$sub_fields = [
					'first'  => [ 'label' => 'First', 'mapping' => 'first_name' ],
					'middle' => [ 'label' => 'Middle', 'mapping' => '' ],
					'last'   => [ 'label' => 'Last', 'mapping' => 'last_name' ],
				];
				break;

			default:
				$sub_fields = [
					'first' => [ 'label' => 'First', 'mapping' => 'first_name' ],
					'last'  => [ 'label' => 'Last', 'mapping' => 'last_name' ],
				];
				break;
		}

		foreach ( $sub_fields as $sub_key => $sub_config ) {
			// Check if sub-field is hidden.
			$hide_key = $sub_key . '_hide';
			if ( ! empty( $field[ $hide_key ] ) ) {
				continue;
			}

			$sub_label = $field[ $sub_key . '_label' ] ?? $sub_config['label'];

			$fields[] = [
				'type'             => 'text',
				'name'             => 'wpforms_' . $field_id . '_' . $sub_key,
				'label'            => $sub_label,
				'placeholder'      => $field[ $sub_key . '_placeholder' ] ?? '',
				'required'         => ! empty( $field['required'] ),
				'crm_mapping'      => $sub_config['mapping'],
				'wpforms_field_id' => $field_id . '_' . $sub_key,
			];
		}

		return $fields;
	}

	/**
	 * Parse an address field into separate address component fields.
	 *
	 * @param array $field The address field.
	 * @return array Array of parsed fields.
	 */
	private function parse_address_field( $field ) {
		$fields   = [];
		$field_id = $field['id'] ?? '';
		$scheme   = $field['scheme'] ?? 'us';

		// Define address sub-fields.
		$address_sub_fields = [
			'address1' => [
				'label'   => 'Address Line 1',
				'mapping' => 'primary_address_line1',
			],
			'address2' => [
				'label'   => 'Address Line 2',
				'mapping' => 'primary_address_line2',
			],
			'city'     => [
				'label'   => 'City',
				'mapping' => 'primary_address_city',
			],
			'state'    => [
				'label'   => 'State / Province / Region',
				'mapping' => 'primary_address_state',
			],
			'postal'   => [
				'label'   => 'ZIP / Postal Code',
				'mapping' => 'primary_address_postal',
			],
			'country'  => [
				'label'   => 'Country',
				'mapping' => 'primary_address_country',
			],
		];

		foreach ( $address_sub_fields as $sub_key => $sub_config ) {
			// Check if sub-field is hidden.
			$hide_key = $sub_key . '_hide';
			if ( ! empty( $field[ $hide_key ] ) ) {
				continue;
			}

			$sub_label = $field[ $sub_key . '_label' ] ?? $sub_config['label'];

			$fields[] = [
				'type'             => 'text',
				'name'             => 'wpforms_' . $field_id . '_' . $sub_key,
				'label'            => $sub_label,
				'placeholder'      => $field[ $sub_key . '_placeholder' ] ?? '',
				'required'         => ! empty( $field['required'] ),
				'crm_mapping'      => $sub_config['mapping'],
				'wpforms_field_id' => $field_id . '_' . $sub_key,
			];
		}

		return $fields;
	}

	/**
	 * Parse WPForms choices into our options format.
	 *
	 * @param array $choices WPForms choices array.
	 * @return array Array of option values.
	 */
	private function parse_choices( $choices ) {
		$options = [];

		foreach ( $choices as $choice ) {
			$label = $choice['label'] ?? '';
			$value = $choice['value'] ?? $label;

			if ( empty( $label ) && empty( $value ) ) {
				continue;
			}

			$options[] = [
				'label' => $label,
				'value' => $value,
			];
		}

		return $options;
	}

	/**
	 * Generate a field name from WPForms field.
	 *
	 * @param array $field The field.
	 * @return string Generated field name.
	 */
	private function generate_field_name( $field ) {
		$field_id    = $field['id'] ?? '';
		$field_label = $field['label'] ?? '';

		// Use sanitized field label if available.
		if ( ! empty( $field_label ) ) {
			return sanitize_title( $field_label );
		}

		// Fall back to field ID format.
		return 'wpforms_field_' . $field_id;
	}

	/**
	 * Get form settings.
	 *
	 * @param int $form_id Form ID.
	 * @return array|null Form settings or null.
	 */
	public function get_form_settings( $form_id ) {
		if ( ! $this->is_available() ) {
			return null;
		}

		$wpforms_obj = wpforms()->get( 'form' );

		if ( ! $wpforms_obj ) {
			return null;
		}

		$wpform = $wpforms_obj->get( absint( $form_id ) );

		if ( ! $wpform ) {
			return null;
		}

		$form_data = $this->decode_form_data( $wpform );

		if ( ! $form_data || empty( $form_data['settings'] ) ) {
			return null;
		}

		$settings = $form_data['settings'];

		return [
			'submit_text'            => $settings['submit_text'] ?? 'Submit',
			'submit_text_processing' => $settings['submit_text_processing'] ?? 'Processing...',
			'ajax_submit'            => ! empty( $settings['ajax_submit'] ),
			'honeypot'               => ! empty( $settings['honeypot'] ),
			'antispam'               => ! empty( $settings['antispam'] ),
			'notification_enable'    => ! empty( $settings['notification_enable'] ),
			'confirmations'          => $settings['confirmations'] ?? [],
			'notifications'          => $form_data['notifications'] ?? [],
		];
	}

	/**
	 * Get form entries count.
	 *
	 * @param int $form_id Form ID.
	 * @return int Entry count.
	 */
	public function get_entries_count( $form_id ) {
		if ( ! $this->is_available() ) {
			return 0;
		}

		// Check if entries functionality is available (Pro feature).
		if ( ! function_exists( 'wpforms' ) || ! method_exists( wpforms()->get( 'entry' ), 'get_entries' ) ) {
			return 0;
		}

		$entries_obj = wpforms()->get( 'entry' );

		if ( ! $entries_obj || ! method_exists( $entries_obj, 'get_entries' ) ) {
			return 0;
		}

		$entries = $entries_obj->get_entries(
			[
				'form_id' => absint( $form_id ),
				'number'  => 0, // Get count only.
			]
		);

		if ( is_array( $entries ) ) {
			return count( $entries );
		}

		return 0;
	}

	/**
	 * Get form notifications.
	 *
	 * @param int $form_id Form ID.
	 * @return array|null Notifications or null.
	 */
	public function get_form_notifications( $form_id ) {
		if ( ! $this->is_available() ) {
			return null;
		}

		$wpforms_obj = wpforms()->get( 'form' );

		if ( ! $wpforms_obj ) {
			return null;
		}

		$wpform = $wpforms_obj->get( absint( $form_id ) );

		if ( ! $wpform ) {
			return null;
		}

		$form_data = $this->decode_form_data( $wpform );

		if ( ! $form_data ) {
			return null;
		}

		return $form_data['notifications'] ?? [];
	}

	/**
	 * Get form confirmations.
	 *
	 * @param int $form_id Form ID.
	 * @return array|null Confirmations or null.
	 */
	public function get_form_confirmations( $form_id ) {
		if ( ! $this->is_available() ) {
			return null;
		}

		$wpforms_obj = wpforms()->get( 'form' );

		if ( ! $wpforms_obj ) {
			return null;
		}

		$wpform = $wpforms_obj->get( absint( $form_id ) );

		if ( ! $wpform ) {
			return null;
		}

		$form_data = $this->decode_form_data( $wpform );

		if ( ! $form_data || empty( $form_data['settings']['confirmations'] ) ) {
			return null;
		}

		return $form_data['settings']['confirmations'];
	}
}

