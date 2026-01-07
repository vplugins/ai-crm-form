<?php
/**
 * Gravity Forms Integration
 *
 * Handles importing and parsing forms from Gravity Forms.
 *
 * @package AI_CRM_Form
 * @subpackage Integrations\GravityForms
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gravity Forms Integration Adapter Class
 */
class AICRMFORM_Gravity_Forms_Integration extends AICRMFORM_Form_Integration_Abstract {

	/**
	 * Integration key.
	 *
	 * @var string
	 */
	protected $key = 'gravity';

	/**
	 * Integration name.
	 *
	 * @var string
	 */
	protected $name = 'Gravity Forms';

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	protected $plugin_slug = 'gravityforms/gravityforms.php';

	/**
	 * Class to check for availability.
	 *
	 * @var string
	 */
	protected $check_class = 'GFForms';

	/**
	 * Field type mappings from Gravity Forms to our format.
	 *
	 * @var array
	 */
	protected $type_map = [
		'text'          => 'text',
		'textarea'      => 'textarea',
		'select'        => 'select',
		'multiselect'   => 'select',
		'number'        => 'number',
		'checkbox'      => 'checkbox',
		'radio'         => 'radio',
		'hidden'        => 'hidden',
		'html'          => 'html',
		'section'       => 'section',
		'page'          => 'page',
		'name'          => 'name',
		'date'          => 'date',
		'time'          => 'time',
		'phone'         => 'tel',
		'address'       => 'address',
		'website'       => 'url',
		'email'         => 'email',
		'fileupload'    => 'file',
		'consent'       => 'checkbox',
		'list'          => 'list',
		'captcha'       => 'captcha',
		'post_title'    => 'text',
		'post_content'  => 'textarea',
		'post_excerpt'  => 'textarea',
		'post_tags'     => 'text',
		'post_category' => 'select',
		'post_image'    => 'file',
		'post_custom'   => 'text',
	];

	/**
	 * Get all Gravity Forms.
	 *
	 * @return array List of forms.
	 */
	public function get_forms() {
		if ( ! $this->is_available() ) {
			return [];
		}

		$forms    = [];
		$gf_forms = GFAPI::get_forms();

		if ( ! is_array( $gf_forms ) ) {
			return [];
		}

		foreach ( $gf_forms as $gf_form ) {
			$forms[] = [
				'id'          => $gf_form['id'],
				'title'       => $gf_form['title'],
				'description' => $gf_form['description'] ?? '',
				'fields'      => $this->parse_fields( $gf_form ),
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

		$gf_form = GFAPI::get_form( $form_id );

		if ( ! $gf_form ) {
			return null;
		}

		return [
			'id'          => $gf_form['id'],
			'title'       => $gf_form['title'],
			'description' => $gf_form['description'] ?? '',
			'fields'      => $this->parse_fields( $gf_form ),
		];
	}

	/**
	 * Parse fields from a Gravity Form.
	 *
	 * @param array $form Gravity Form array.
	 * @return array Parsed fields.
	 */
	public function parse_fields( $form ) {
		$fields = [];

		if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
			return $fields;
		}

		foreach ( $form['fields'] as $gf_field ) {
			$parsed = $this->parse_field( $gf_field );

			if ( $parsed ) {
				// Handle name field (splits into first/last).
				if ( 'name' === $gf_field->type ) {
					$name_fields = $this->parse_name_field( $gf_field );
					$fields      = array_merge( $fields, $name_fields );
				} elseif ( 'address' === $gf_field->type ) {
					// Handle address field (splits into multiple).
					$address_fields = $this->parse_address_field( $gf_field );
					$fields         = array_merge( $fields, $address_fields );
				} else {
					$fields[] = $parsed;
				}
			}
		}

		return $fields;
	}

	/**
	 * Parse a single Gravity Form field.
	 *
	 * @param GF_Field|array $field The Gravity Form field.
	 * @param string         $type  Optional type override.
	 * @param string         $attrs Optional attributes (unused for GF).
	 * @return array|null Parsed field data or null.
	 */
	public function parse_field( $field, $type = '', $attrs = '' ) {
		// Handle both object and array formats.
		$field_type  = is_object( $field ) ? $field->type : ( $field['type'] ?? '' );
		$field_id    = is_object( $field ) ? $field->id : ( $field['id'] ?? '' );
		$field_label = is_object( $field ) ? $field->label : ( $field['label'] ?? '' );

		// Skip certain field types.
		$skip_types = [ 'html', 'section', 'page', 'captcha' ];
		if ( in_array( $field_type, $skip_types, true ) ) {
			return null;
		}

		$mapped_type = $this->map_field_type( $field_type );

		if ( ! $mapped_type ) {
			return null;
		}

		$name = $this->generate_field_name( $field );

		$field_data = [
			'type'        => $mapped_type,
			'name'        => $name,
			'label'       => $field_label ?: $this->generate_label( $name ),
			'placeholder' => $this->get_field_property( $field, 'placeholder', '' ),
			'required'    => (bool) $this->get_field_property( $field, 'isRequired', false ),
			'crm_mapping' => $this->guess_crm_mapping( $name ),
			'gf_field_id' => $field_id,
		];

		// Add description if present.
		$description = $this->get_field_property( $field, 'description', '' );
		if ( ! empty( $description ) ) {
			$field_data['description'] = $description;
		}

		// Add choices for select/checkbox/radio.
		if ( in_array( $field_type, [ 'select', 'multiselect', 'checkbox', 'radio' ], true ) ) {
			$choices = $this->get_field_property( $field, 'choices', [] );
			if ( ! empty( $choices ) ) {
				$field_data['options'] = $this->parse_choices( $choices );
			}
		}

		// Add CSS class if present.
		$css_class = $this->get_field_property( $field, 'cssClass', '' );
		if ( ! empty( $css_class ) ) {
			$field_data['class'] = $css_class;
		}

		// Add size if present.
		$size = $this->get_field_property( $field, 'size', '' );
		if ( ! empty( $size ) ) {
			$field_data['size'] = $size;
		}

		// Handle visibility.
		$visibility = $this->get_field_property( $field, 'visibility', 'visible' );
		if ( 'administrative' === $visibility || 'hidden' === $visibility ) {
			$field_data['type'] = 'hidden';
		}

		return $field_data;
	}

	/**
	 * Parse a name field into separate first/last name fields.
	 *
	 * @param GF_Field|array $field The name field.
	 * @return array Array of parsed fields.
	 */
	private function parse_name_field( $field ) {
		$fields   = [];
		$inputs   = $this->get_field_property( $field, 'inputs', [] );
		$field_id = $this->get_field_property( $field, 'id' );

		foreach ( $inputs as $input ) {
			$input_id    = is_array( $input ) ? $input['id'] : $input->id;
			$input_label = is_array( $input ) ? ( $input['label'] ?? '' ) : ( $input->label ?? '' );
			$is_hidden   = is_array( $input ) ? ( $input['isHidden'] ?? false ) : ( $input->isHidden ?? false );

			if ( $is_hidden ) {
				continue;
			}

			$name        = 'input_' . str_replace( '.', '_', $input_id );
			$crm_mapping = '';

			// Determine CRM mapping based on input position/label.
			$label_lower = strtolower( $input_label );
			if ( strpos( $label_lower, 'first' ) !== false ) {
				$crm_mapping = 'first_name';
			} elseif ( strpos( $label_lower, 'last' ) !== false ) {
				$crm_mapping = 'last_name';
			} elseif ( strpos( $label_lower, 'prefix' ) !== false || strpos( $label_lower, 'title' ) !== false ) {
				$crm_mapping = '';
			} elseif ( strpos( $label_lower, 'middle' ) !== false ) {
				$crm_mapping = '';
			} elseif ( strpos( $label_lower, 'suffix' ) !== false ) {
				$crm_mapping = '';
			}

			$fields[] = [
				'type'        => 'text',
				'name'        => $name,
				'label'       => $input_label,
				'required'    => (bool) $this->get_field_property( $field, 'isRequired', false ),
				'crm_mapping' => $crm_mapping,
				'gf_field_id' => $input_id,
			];
		}

		return $fields;
	}

	/**
	 * Parse an address field into separate address component fields.
	 *
	 * @param GF_Field|array $field The address field.
	 * @return array Array of parsed fields.
	 */
	private function parse_address_field( $field ) {
		$fields = [];
		$inputs = $this->get_field_property( $field, 'inputs', [] );

		$address_mappings = [
			'street'  => 'primary_address_line1',
			'line 2'  => 'primary_address_line2',
			'city'    => 'primary_address_city',
			'state'   => 'primary_address_state',
			'zip'     => 'primary_address_postal',
			'country' => 'primary_address_country',
		];

		foreach ( $inputs as $input ) {
			$input_id    = is_array( $input ) ? $input['id'] : $input->id;
			$input_label = is_array( $input ) ? ( $input['label'] ?? '' ) : ( $input->label ?? '' );
			$is_hidden   = is_array( $input ) ? ( $input['isHidden'] ?? false ) : ( $input->isHidden ?? false );

			if ( $is_hidden ) {
				continue;
			}

			$name        = 'input_' . str_replace( '.', '_', $input_id );
			$crm_mapping = '';

			// Determine CRM mapping based on input label.
			$label_lower = strtolower( $input_label );
			foreach ( $address_mappings as $keyword => $mapping ) {
				if ( strpos( $label_lower, $keyword ) !== false ) {
					$crm_mapping = $mapping;
					break;
				}
			}

			$fields[] = [
				'type'        => 'text',
				'name'        => $name,
				'label'       => $input_label,
				'required'    => (bool) $this->get_field_property( $field, 'isRequired', false ),
				'crm_mapping' => $crm_mapping,
				'gf_field_id' => $input_id,
			];
		}

		return $fields;
	}

	/**
	 * Parse Gravity Forms choices into our options format.
	 *
	 * @param array $choices Gravity Forms choices array.
	 * @return array Array of option values.
	 */
	private function parse_choices( $choices ) {
		$options = [];

		foreach ( $choices as $choice ) {
			$text  = is_array( $choice ) ? ( $choice['text'] ?? '' ) : ( $choice->text ?? '' );
			$value = is_array( $choice ) ? ( $choice['value'] ?? $text ) : ( $choice->value ?? $text );

			$options[] = [
				'label' => $text,
				'value' => $value,
			];
		}

		return $options;
	}

	/**
	 * Generate a field name from Gravity Form field.
	 *
	 * @param GF_Field|array $field The field.
	 * @return string Generated field name.
	 */
	private function generate_field_name( $field ) {
		$field_id    = $this->get_field_property( $field, 'id' );
		$field_label = $this->get_field_property( $field, 'label', '' );
		$admin_label = $this->get_field_property( $field, 'adminLabel', '' );

		// Prefer admin label if set.
		if ( ! empty( $admin_label ) ) {
			return sanitize_title( $admin_label );
		}

		// Use sanitized field label.
		if ( ! empty( $field_label ) ) {
			return sanitize_title( $field_label );
		}

		// Fall back to input ID format.
		return 'input_' . $field_id;
	}

	/**
	 * Get a property from a field (handles both object and array).
	 *
	 * @param GF_Field|array $field    The field.
	 * @param string         $property Property name.
	 * @param mixed          $default  Default value.
	 * @return mixed Property value or default.
	 */
	private function get_field_property( $field, $property, $default = null ) {
		if ( is_object( $field ) ) {
			return isset( $field->$property ) ? $field->$property : $default;
		}

		if ( is_array( $field ) ) {
			return isset( $field[ $property ] ) ? $field[ $property ] : $default;
		}

		return $default;
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

		$form = GFAPI::get_form( $form_id );

		if ( ! $form ) {
			return null;
		}

		return [
			'confirmations'      => $form['confirmations'] ?? [],
			'notifications'      => $form['notifications'] ?? [],
			'requireLogin'       => $form['requireLogin'] ?? false,
			'requireLoginMessage' => $form['requireLoginMessage'] ?? '',
			'limitEntries'       => $form['limitEntries'] ?? false,
			'limitEntriesCount'  => $form['limitEntriesCount'] ?? '',
			'scheduleForm'       => $form['scheduleForm'] ?? false,
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

		return GFAPI::count_entries( $form_id );
	}
}

