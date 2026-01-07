<?php
/**
 * Contact Form 7 Integration
 *
 * Handles importing and parsing forms from Contact Form 7.
 *
 * @package AI_CRM_Form
 * @subpackage Integrations\ContactForm7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contact Form 7 Integration Adapter Class
 */
class AICRMFORM_CF7_Integration extends AICRMFORM_Form_Integration_Abstract {

	/**
	 * Integration key.
	 *
	 * @var string
	 */
	protected $key = 'cf7';

	/**
	 * Integration name.
	 *
	 * @var string
	 */
	protected $name = 'Contact Form 7';

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	protected $plugin_slug = 'contact-form-7/wp-contact-form-7.php';

	/**
	 * Class to check for availability.
	 *
	 * @var string
	 */
	protected $check_class = 'WPCF7_ContactForm';

	/**
	 * Field type mappings from CF7 to our format.
	 *
	 * @var array
	 */
	protected $type_map = [
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

	/**
	 * Get all Contact Form 7 forms.
	 *
	 * @return array List of forms.
	 */
	public function get_forms() {
		if ( ! $this->is_available() ) {
			return [];
		}

		$forms     = [];
		$cf7_forms = WPCF7_ContactForm::find();

		foreach ( $cf7_forms as $cf7_form ) {
			$hash = get_post_meta( $cf7_form->id(), '_hash', true );

			$forms[] = [
				'id'     => $cf7_form->id(),
				'hash'   => $hash ?: '',
				'title'  => $cf7_form->title(),
				'fields' => $this->parse_fields( $cf7_form ),
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

		$cf7_form = WPCF7_ContactForm::get_instance( $form_id );

		if ( ! $cf7_form ) {
			return null;
		}

		$hash = get_post_meta( $cf7_form->id(), '_hash', true );

		return [
			'id'     => $cf7_form->id(),
			'hash'   => $hash ?: '',
			'title'  => $cf7_form->title(),
			'fields' => $this->parse_fields( $cf7_form ),
		];
	}

	/**
	 * Parse fields from a CF7 form.
	 *
	 * @param WPCF7_ContactForm $form CF7 form object.
	 * @return array Parsed fields.
	 */
	public function parse_fields( $form ) {
		$fields       = [];
		$form_content = $form->prop( 'form' );

		// Parse CF7 shortcode tags.
		$pattern = '/\[(\w+)(?:\*)?(?:\s+([^\]]+))?\]/';
		preg_match_all( $pattern, $form_content, $matches, PREG_SET_ORDER );

		foreach ( $matches as $match ) {
			$tag_type     = $match[1];
			$attrs_string = isset( $match[2] ) ? $match[2] : '';

			// Skip submit buttons.
			if ( 'submit' === $tag_type ) {
				continue;
			}

			$field = $this->parse_field( null, $tag_type, $attrs_string );

			if ( $field ) {
				$fields[] = $field;
			}
		}

		return $fields;
	}

	/**
	 * Parse a single CF7 field tag.
	 *
	 * @param mixed  $field The source field (unused for CF7).
	 * @param string $type  The tag type.
	 * @param string $attrs Attributes string.
	 * @return array|null Parsed field data or null.
	 */
	public function parse_field( $field, $type = '', $attrs = '' ) {
		$parsed_attrs = $this->parse_cf7_attributes( $attrs );
		$is_required  = $this->is_required_type( $type );
		$base_type    = str_replace( '*', '', $type );

		$mapped_type = $this->map_field_type( $base_type );

		if ( ! $mapped_type ) {
			return null;
		}

		$name = $parsed_attrs['name'] ?: 'field_' . wp_generate_password( 6, false );

		$field_data = [
			'type'        => $mapped_type,
			'name'        => $name,
			'label'       => $this->generate_label( $name ),
			'placeholder' => $parsed_attrs['placeholder'] ?? '',
			'required'    => $is_required,
			'crm_mapping' => $this->guess_crm_mapping( $name ),
		];

		// Add options for select/checkbox/radio.
		if ( in_array( $base_type, [ 'select', 'checkbox', 'radio' ], true ) && ! empty( $parsed_attrs['options'] ) ) {
			$field_data['options'] = $parsed_attrs['options'];
		}

		// Add class if present.
		if ( ! empty( $parsed_attrs['class'] ) ) {
			$field_data['class'] = $parsed_attrs['class'];
		}

		// Add ID if present.
		if ( ! empty( $parsed_attrs['id'] ) ) {
			$field_data['id'] = $parsed_attrs['id'];
		}

		return $field_data;
	}

	/**
	 * Parse CF7 tag attributes string.
	 *
	 * @param string $attrs_string Attributes string from CF7 tag.
	 * @return array Parsed attributes.
	 */
	private function parse_cf7_attributes( $attrs_string ) {
		$attrs   = [];
		$name    = '';
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

		$attrs['name']    = $name;
		$attrs['options'] = $options;

		return $attrs;
	}

	/**
	 * Get the form content/template.
	 *
	 * @param int $form_id Form ID.
	 * @return string|null Form content or null.
	 */
	public function get_form_content( $form_id ) {
		if ( ! $this->is_available() ) {
			return null;
		}

		$cf7_form = WPCF7_ContactForm::get_instance( $form_id );

		if ( ! $cf7_form ) {
			return null;
		}

		return $cf7_form->prop( 'form' );
	}

	/**
	 * Get form mail settings.
	 *
	 * @param int $form_id Form ID.
	 * @return array|null Mail settings or null.
	 */
	public function get_form_mail_settings( $form_id ) {
		if ( ! $this->is_available() ) {
			return null;
		}

		$cf7_form = WPCF7_ContactForm::get_instance( $form_id );

		if ( ! $cf7_form ) {
			return null;
		}

		return $cf7_form->prop( 'mail' );
	}
}

