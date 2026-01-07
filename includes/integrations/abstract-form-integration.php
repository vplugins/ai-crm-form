<?php
/**
 * Abstract Form Integration Class
 *
 * Base class for form plugin integrations with common functionality.
 *
 * @package AI_CRM_Form
 * @subpackage Integrations
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract class for Form Integration Adapters
 */
abstract class AICRMFORM_Form_Integration_Abstract implements AICRMFORM_Form_Integration_Interface {

	/**
	 * Integration key.
	 *
	 * @var string
	 */
	protected $key = '';

	/**
	 * Integration name.
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	protected $plugin_slug = '';

	/**
	 * Class to check for plugin availability.
	 *
	 * @var string
	 */
	protected $check_class = '';

	/**
	 * Field type mappings from source to our format.
	 *
	 * @var array
	 */
	protected $type_map = [];

	/**
	 * Get the integration key.
	 *
	 * @return string
	 */
	public function get_key() {
		return $this->key;
	}

	/**
	 * Get the display name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Get the plugin slug.
	 *
	 * @return string
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Get the check class name.
	 *
	 * @return string
	 */
	public function get_check_class() {
		return $this->check_class;
	}

	/**
	 * Check if the plugin is available.
	 *
	 * @return bool
	 */
	public function is_available() {
		return class_exists( $this->check_class );
	}

	/**
	 * Get integration info for display.
	 *
	 * @return array
	 */
	public function get_info() {
		return [
			'key'    => $this->get_key(),
			'name'   => $this->get_name(),
			'slug'   => $this->get_plugin_slug(),
			'active' => $this->is_available(),
			'forms'  => $this->is_available() ? $this->get_forms() : [],
		];
	}

	/**
	 * Map source field type to our field type.
	 *
	 * @param string $source_type Source field type.
	 * @return string|null Mapped field type or null.
	 */
	protected function map_field_type( $source_type ) {
		// Remove asterisk for required fields.
		$base_type = str_replace( '*', '', $source_type );

		return $this->type_map[ $base_type ] ?? null;
	}

	/**
	 * Check if a field type indicates a required field.
	 *
	 * @param string $type Field type string.
	 * @return bool
	 */
	protected function is_required_type( $type ) {
		return strpos( $type, '*' ) !== false;
	}

	/**
	 * Guess CRM mapping based on field name.
	 *
	 * @param string $field_name Field name.
	 * @return string CRM mapping or empty string.
	 */
	protected function guess_crm_mapping( $field_name ) {
		$name_lower = strtolower( $field_name );

		// Remove common prefixes.
		$clean_name = preg_replace( '/^(your|user|contact|my|the|form|field)[_-]?/i', '', $name_lower );

		// Mappings with keywords.
		$mappings = [
			'first_name'              => [ 'firstname', 'first_name', 'first-name', 'fname', 'givenname', 'given_name' ],
			'last_name'               => [ 'lastname', 'last_name', 'last-name', 'lname', 'surname', 'familyname', 'family_name' ],
			'email'                   => [ 'email', 'mail', 'e-mail', 'email_address', 'emailaddress' ],
			'phone_number'            => [ 'phone', 'tel', 'telephone', 'mobile', 'cell', 'phonenumber', 'phone_number' ],
			'company_name'            => [ 'company', 'organization', 'org', 'business', 'companyname', 'company_name' ],
			'company_website'         => [ 'website', 'url', 'site', 'web', 'homepage' ],
			'primary_address_line1'   => [ 'address', 'street', 'addr', 'address1', 'address_1', 'streetaddress' ],
			'primary_address_city'    => [ 'city', 'town', 'locality' ],
			'primary_address_state'   => [ 'state', 'province', 'region', 'county' ],
			'primary_address_postal'  => [ 'zip', 'zipcode', 'postal', 'postcode', 'postalcode', 'postal_code' ],
			'primary_address_country' => [ 'country', 'nation' ],
			'message'                 => [ 'message', 'comment', 'comments', 'inquiry', 'question', 'body', 'content', 'text', 'note', 'notes', 'description' ],
			'source_name'             => [ 'source', 'referral', 'howdidyouhear', 'hearabout' ],
		];

		// First, try exact match with cleaned name.
		foreach ( $mappings as $crm_field => $keywords ) {
			foreach ( $keywords as $keyword ) {
				if ( $clean_name === $keyword ) {
					return $crm_field;
				}
			}
		}

		// Second, try substring match.
		foreach ( $mappings as $crm_field => $keywords ) {
			foreach ( $keywords as $keyword ) {
				if ( strpos( $clean_name, $keyword ) !== false || strpos( $keyword, $clean_name ) !== false ) {
					return $crm_field;
				}
			}
		}

		// Try substring match with original name.
		foreach ( $mappings as $crm_field => $keywords ) {
			foreach ( $keywords as $keyword ) {
				if ( strpos( $name_lower, $keyword ) !== false ) {
					return $crm_field;
				}
			}
		}

		// Special case: generic "name" field.
		if ( $clean_name === 'name' || $name_lower === 'name' || $name_lower === 'your-name' || $name_lower === 'fullname' || $name_lower === 'full_name' ) {
			return 'first_name';
		}

		// Special case: "subject".
		if ( strpos( $name_lower, 'subject' ) !== false ) {
			return 'message';
		}

		return '';
	}

	/**
	 * Generate a label from field name.
	 *
	 * @param string $name Field name.
	 * @return string Generated label.
	 */
	protected function generate_label( $name ) {
		return ucfirst( str_replace( [ '-', '_' ], ' ', $name ) );
	}
}

