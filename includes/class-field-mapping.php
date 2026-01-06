<?php
/**
 * CRM Field Mapping Configuration
 *
 * @package AI_CRM_Form
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field Mapping Class
 *
 * Contains all CRM field IDs and provides mapping utilities.
 */
class AICRMFORM_Field_Mapping {

	/**
	 * Production Environment Contact Fields
	 */
	const CONTACT_FIELDS = [
		'first_name'   => 'FieldID-211d398a-d905-43f8-a8e2-c1c952d4f5cc',
		'last_name'    => 'FieldID-a1c64750-0a8e-457b-8575-bcada69db4a1',
		'phone_number' => 'FieldID-1bcd68e1-2234-4a0c-9c70-21d9a6ff0ce8',
		'email'        => 'FieldID-d2fea894-1e23-4bf6-b488-2cd7a8359dde',
		'tags'         => 'FieldID-a7b68b66-6820-4a44-9717-f8f4e1d399ce',
	];

	/**
	 * Standard Contact Fields (Same Across Environments)
	 */
	const STANDARD_CONTACT_FIELDS = [
		'mobile_phone'             => 'FieldID-c11b2495-6f02-4386-9fe0-141067c63c14',
		'additional_emails'        => 'FieldID-6e3bb5c2-8baf-4f94-8b61-9b8a3d3b7a5c',
		'primary_company_id'       => 'FieldID-e4d82132-ed77-4a2f-8625-1163a04efba1',
		'primary_address_line1'    => 'FieldID-59dc9adf-6401-4af4-91cb-666ce81acc37',
		'primary_address_line2'    => 'FieldID-623fb8a0-c224-4952-af4c-a4e1e3bd5b70',
		'primary_address_city'     => 'FieldID-4fe28279-6e83-4e7c-b310-9474dcd5034d',
		'primary_address_state'    => 'FieldID-a215813c-8225-4c21-b646-01b370eefd47',
		'primary_address_postal'   => 'FieldID-3ba73c58-3fa9-4fbf-b28b-8709837685bc',
		'primary_address_country'  => 'FieldID-aa50a455-c00b-4d6b-98f7-f833332b1b5c',
		'primary_salesperson_id'   => 'FieldID-46d1bf91-111a-44d6-a029-9f6722e51fc9',
		'additional_salesperson'   => 'FieldID-3febf407-1d0d-4e06-a962-8774560a2dbc',
		'message'                  => 'FieldID-0a2e9d97-3d09-4b7d-a5fd-7308fa8066e1',
		'lead_score'               => 'FieldID-bb231018-4fe9-4744-b729-95e32f3692da',
		'lead_quality'             => 'FieldID-232e19aa-d5b4-4746-9f70-de01bb6101e4',
		'source_name'              => 'FieldID-d80132b7-4fbe-45dd-92d3-a313286567ac',
		'original_source'          => 'FieldID-78e702fa-84c1-46c3-84d9-c59b9f83db88',
	];

	/**
	 * Contact UTM Fields
	 */
	const UTM_FIELDS = [
		'utm_medium'   => 'FieldID-2b0c575e-4a6e-4646-bd3d-0e43fa341c9a',
		'utm_source'   => 'FieldID-7edd3d42-41ab-4b53-9141-6f0113046ad5',
		'utm_campaign' => 'FieldID-dd36880f-73a9-46fc-a254-a9259341df79',
		'utm_term'     => 'FieldID-31553fd6-ea45-4420-ba1f-9f5eb51956a1',
		'utm_content'  => 'FieldID-7df2ac1d-8405-477c-9ae7-98a176c64c10',
		'gclid'        => 'FieldID-a90f2d07-a40c-4c9f-a9f4-20122437beaa',
		'fbclid'       => 'FieldID-59d59574-50da-4f67-a2fa-f99cf077a6b0',
		'msclkid'      => 'FieldID-52ba3729-fe15-41f7-ac87-662d23656c85',
	];

	/**
	 * Contact Consent Fields
	 */
	const CONSENT_FIELDS = [
		'marketing_email_consent_status'    => 'FieldID-7e76c322-bf7c-47ed-817a-dc4e2d60402f',
		'marketing_email_consent_source'    => 'FieldID-b23bf8e2-b543-4788-8082-4cef2722d435',
		'marketing_email_consent_timestamp' => 'FieldID-95ce7369-6556-45b7-b2ea-2efe17afa360',
		'sms_consent_status'                => 'FieldID-7c8eda3b-b1b9-44b1-8f88-adff9dbc61fb',
		'sms_consent_source'                => 'FieldID-6b8b9930-6b8e-40d2-ace6-11372797870d',
		'sms_consent_timestamp'             => 'FieldID-265dfa1e-4cd8-4c79-9e06-d87a298e404e',
	];

	/**
	 * Company Fields
	 */
	const COMPANY_FIELDS = [
		'company_name'                 => 'FieldID-b2a5d86e-f9ae-11ed-be56-0242ac120002',
		'company_address_line1'        => 'FieldID-36785fbc-9c40-4938-92e3-b262d40ef6bb',
		'company_address_line2'        => 'FieldID-ac4c2ea1-f8d1-4bdd-8a42-a1ad4127eef6',
		'company_address_city'         => 'FieldID-33b7b0a0-82ed-471b-baa1-87ab958f90d2',
		'company_address_state'        => 'FieldID-184fe9c2-b3a8-47c5-85dc-7c3a285c17ac',
		'company_address_postal'       => 'FieldID-b0e75bc5-59f7-4c54-98a8-510b9ec982ec',
		'company_address_country'      => 'FieldID-daa0c91f-8a0e-4c90-be9c-e53a73789edc',
		'company_website'              => 'FieldID-f25628fb-eeb3-4d99-8737-44b403758837',
		'company_phone'                => 'FieldID-fc7ff4ae-b753-4650-9961-42b07bcc6ac6',
		'company_tags'                 => 'FieldID-b74e203d-bab3-4c78-83d0-ac96f44d1061',
		'company_google_places_id'     => 'FieldID-ecba7574-ed05-4e89-98f2-d49a2c307a69',
		'company_primary_salesperson'  => 'FieldID-1d95f7ec-48f5-407e-ae13-22bf13420a7f',
		'company_linkedin_url'         => 'FieldID-ac372fb8-3066-447f-9c0b-b7fc693390b0',
		'company_facebook_url'         => 'FieldID-b71abcf6-9091-4be9-a63d-14a6151124e5',
		'company_instagram_url'        => 'FieldID-60e53809-3833-4977-a563-77538c19c2c4',
		'company_twitter_url'          => 'FieldID-d46270a6-dab3-4ff9-b617-920659297469',
		'company_tiktok_url'           => 'FieldID-6d056a77-c30f-43b7-acb6-ffb60c20a6f9',
		'company_pinterest_url'        => 'FieldID-9011434d-7e16-429b-8d80-db72e6ffa0df',
	];

	/**
	 * Platform Extension Fields
	 */
	const PLATFORM_FIELDS = [
		'account_group_id'     => 'FieldID-12c064c5-3260-478e-952d-04a3a7e83b15',
		'latest_snapshot_id'   => 'FieldID-60e8f540-6ad5-4e04-a08d-ac6fd1a331e7',
		'user_id'              => 'FieldID-2ccc0de0-89be-499a-968d-b6d1d59b77a1',
	];

	/**
	 * Get all available fields grouped by category.
	 *
	 * @return array All fields organized by category.
	 */
	public static function get_all_fields() {
		return [
			'contact'          => self::CONTACT_FIELDS,
			'standard_contact' => self::STANDARD_CONTACT_FIELDS,
			'utm'              => self::UTM_FIELDS,
			'consent'          => self::CONSENT_FIELDS,
			'company'          => self::COMPANY_FIELDS,
			'platform'         => self::PLATFORM_FIELDS,
		];
	}

	/**
	 * Get field ID by field name.
	 *
	 * @param string $field_name The field name to look up.
	 * @return string|null The field ID or null if not found.
	 */
	public static function get_field_id( $field_name ) {
		$all_fields = array_merge(
			self::CONTACT_FIELDS,
			self::STANDARD_CONTACT_FIELDS,
			self::UTM_FIELDS,
			self::CONSENT_FIELDS,
			self::COMPANY_FIELDS,
			self::PLATFORM_FIELDS
		);

		return $all_fields[ $field_name ] ?? null;
	}

	/**
	 * Get field name by field ID.
	 *
	 * @param string $field_id The field ID to look up.
	 * @return string|null The field name or null if not found.
	 */
	public static function get_field_name( $field_id ) {
		$all_fields = array_merge(
			self::CONTACT_FIELDS,
			self::STANDARD_CONTACT_FIELDS,
			self::UTM_FIELDS,
			self::CONSENT_FIELDS,
			self::COMPANY_FIELDS,
			self::PLATFORM_FIELDS
		);

		$flipped = array_flip( $all_fields );
		return $flipped[ $field_id ] ?? null;
	}

	/**
	 * Get commonly used form fields for quick form creation.
	 *
	 * @return array Common form fields with their IDs.
	 */
	public static function get_common_form_fields() {
		return [
			'first_name' => [
				'id'       => self::CONTACT_FIELDS['first_name'],
				'label'    => __( 'First Name', 'ai-crm-form' ),
				'type'     => 'text',
				'required' => true,
			],
			'last_name'  => [
				'id'       => self::CONTACT_FIELDS['last_name'],
				'label'    => __( 'Last Name', 'ai-crm-form' ),
				'type'     => 'text',
				'required' => true,
			],
			'email'      => [
				'id'       => self::CONTACT_FIELDS['email'],
				'label'    => __( 'Email', 'ai-crm-form' ),
				'type'     => 'email',
				'required' => true,
			],
			'phone'      => [
				'id'       => self::CONTACT_FIELDS['phone_number'],
				'label'    => __( 'Phone Number', 'ai-crm-form' ),
				'type'     => 'tel',
				'required' => false,
			],
			'message'    => [
				'id'       => self::STANDARD_CONTACT_FIELDS['message'],
				'label'    => __( 'Message', 'ai-crm-form' ),
				'type'     => 'textarea',
				'required' => false,
			],
		];
	}

	/**
	 * Get field configuration for AI form generation.
	 *
	 * @return array Field definitions for AI prompt.
	 */
	public static function get_field_definitions_for_ai() {
		return [
			[
				'name'        => 'first_name',
				'field_id'    => self::CONTACT_FIELDS['first_name'],
				'description' => 'First name of the contact',
				'type'        => 'text',
			],
			[
				'name'        => 'last_name',
				'field_id'    => self::CONTACT_FIELDS['last_name'],
				'description' => 'Last name of the contact',
				'type'        => 'text',
			],
			[
				'name'        => 'email',
				'field_id'    => self::CONTACT_FIELDS['email'],
				'description' => 'Email address of the contact',
				'type'        => 'email',
			],
			[
				'name'        => 'phone_number',
				'field_id'    => self::CONTACT_FIELDS['phone_number'],
				'description' => 'Phone number of the contact',
				'type'        => 'tel',
			],
			[
				'name'        => 'mobile_phone',
				'field_id'    => self::STANDARD_CONTACT_FIELDS['mobile_phone'],
				'description' => 'Mobile phone number',
				'type'        => 'tel',
			],
			[
				'name'        => 'message',
				'field_id'    => self::STANDARD_CONTACT_FIELDS['message'],
				'description' => 'Message or inquiry from the contact',
				'type'        => 'textarea',
			],
			[
				'name'        => 'company_name',
				'field_id'    => self::COMPANY_FIELDS['company_name'],
				'description' => 'Company or organization name',
				'type'        => 'text',
			],
			[
				'name'        => 'company_website',
				'field_id'    => self::COMPANY_FIELDS['company_website'],
				'description' => 'Company website URL',
				'type'        => 'url',
			],
			[
				'name'        => 'primary_address_line1',
				'field_id'    => self::STANDARD_CONTACT_FIELDS['primary_address_line1'],
				'description' => 'Street address line 1',
				'type'        => 'text',
			],
			[
				'name'        => 'primary_address_line2',
				'field_id'    => self::STANDARD_CONTACT_FIELDS['primary_address_line2'],
				'description' => 'Street address line 2 (apartment, suite, etc.)',
				'type'        => 'text',
			],
			[
				'name'        => 'primary_address_city',
				'field_id'    => self::STANDARD_CONTACT_FIELDS['primary_address_city'],
				'description' => 'City',
				'type'        => 'text',
			],
			[
				'name'        => 'primary_address_state',
				'field_id'    => self::STANDARD_CONTACT_FIELDS['primary_address_state'],
				'description' => 'State or province',
				'type'        => 'text',
			],
			[
				'name'        => 'primary_address_postal',
				'field_id'    => self::STANDARD_CONTACT_FIELDS['primary_address_postal'],
				'description' => 'Postal or ZIP code',
				'type'        => 'text',
			],
			[
				'name'        => 'primary_address_country',
				'field_id'    => self::STANDARD_CONTACT_FIELDS['primary_address_country'],
				'description' => 'Country',
				'type'        => 'text',
			],
			[
				'name'        => 'source_name',
				'field_id'    => self::STANDARD_CONTACT_FIELDS['source_name'],
				'description' => 'Lead source name',
				'type'        => 'text',
			],
		];
	}

	/**
	 * Map form data to CRM field format.
	 *
	 * Handles:
	 * - Direct field mappings
	 * - Split mappings (e.g., "your-name" -> first_name + last_name)
	 * - Auto-detection of name fields that need splitting
	 *
	 * @param array $form_data The form submission data.
	 * @param array $field_mapping The mapping of form fields to CRM field IDs.
	 * @return array The mapped data ready for CRM API.
	 */
	public static function map_form_data_to_crm( $form_data, $field_mapping ) {
		$mapped_data    = [];
		$first_name_id  = self::get_field_id( 'first_name' );
		$last_name_id   = self::get_field_id( 'last_name' );
		$has_first_name = false;
		$has_last_name  = false;

		// First, find explicit split mappings.
		$split_mappings = [];
		foreach ( $field_mapping as $key => $field_id ) {
			if ( strpos( $key, '__split__' ) !== false ) {
				$parts      = explode( '__split__', $key );
				$form_field = $parts[0];
				$crm_field  = $parts[1];

				if ( ! isset( $split_mappings[ $form_field ] ) ) {
					$split_mappings[ $form_field ] = [];
				}
				$split_mappings[ $form_field ][ $crm_field ] = $field_id;
			}
		}

		// Also auto-detect name fields that map to first_name but need splitting.
		foreach ( $field_mapping as $form_field => $field_id ) {
			if ( strpos( $form_field, '__split__' ) !== false ) {
				continue;
			}

			// If this field maps to first_name and looks like a combined name field.
			if ( $field_id === $first_name_id ) {
				$field_lower = strtolower( $form_field );
				// Check if it's likely a full name field (contains 'name' but not 'first' or 'last').
				if ( ( strpos( $field_lower, 'name' ) !== false || strpos( $field_lower, 'full' ) !== false ) &&
					 strpos( $field_lower, 'first' ) === false &&
					 strpos( $field_lower, 'last' ) === false ) {
					// This is likely a full name field - add to split mappings.
					if ( ! isset( $split_mappings[ $form_field ] ) ) {
						$split_mappings[ $form_field ] = [
							'first_name' => $first_name_id,
							'last_name'  => $last_name_id,
						];
						error_log( sprintf(
							'AI CRM Form: Auto-detected "%s" as full name field, will split into first_name + last_name',
							$form_field
						) );
					}
				}
			}
		}

		foreach ( $form_data as $form_field => $value ) {
			// Check for split mappings first.
			if ( isset( $split_mappings[ $form_field ] ) ) {
				$value_str = is_string( $value ) ? trim( $value ) : '';

				// If we have both first_name and last_name split mappings, split the value.
				if ( isset( $split_mappings[ $form_field ]['first_name'] ) &&
					 isset( $split_mappings[ $form_field ]['last_name'] ) ) {

					if ( strpos( $value_str, ' ' ) !== false ) {
						$name_parts  = explode( ' ', $value_str, 2 );
						$first_name  = trim( $name_parts[0] );
						$last_name   = trim( $name_parts[1] ?? '' );
					} else {
						$first_name = $value_str;
						$last_name  = '';
					}

					$mapped_data[ $split_mappings[ $form_field ]['first_name'] ] = $first_name;
					$mapped_data[ $split_mappings[ $form_field ]['last_name'] ]  = $last_name;
					$has_first_name = true;
					$has_last_name  = true;

					error_log( sprintf(
						'AI CRM Form: Split "%s" = "%s" -> first_name="%s", last_name="%s"',
						$form_field,
						$value_str,
						$first_name,
						$last_name
					) );
					continue;
				}

				// Handle other split mappings.
				foreach ( $split_mappings[ $form_field ] as $crm_field => $field_id ) {
					$mapped_data[ $field_id ] = $value;
				}
				continue;
			}

			// Regular direct mapping.
			if ( isset( $field_mapping[ $form_field ] ) ) {
				$field_id                 = $field_mapping[ $form_field ];
				$mapped_data[ $field_id ] = $value;

				if ( $field_id === $first_name_id ) {
					$has_first_name = true;
				}
				if ( $field_id === $last_name_id ) {
					$has_last_name = true;
				}
			}
		}

		// Ensure last_name exists if first_name exists (many CRM schemas require both).
		if ( $has_first_name && ! $has_last_name ) {
			$mapped_data[ $last_name_id ] = '';
			error_log( 'AI CRM Form: Added empty last_name since only first_name was provided' );
		}

		// Ensure phone_number exists if we have contact info (some CRM schemas require it).
		$phone_number_id = self::get_field_id( 'phone_number' );
		$email_id        = self::get_field_id( 'email' );
		if ( isset( $mapped_data[ $email_id ] ) && ! isset( $mapped_data[ $phone_number_id ] ) ) {
			$mapped_data[ $phone_number_id ] = '';
			error_log( 'AI CRM Form: Added empty phone_number field' );
		}

		return $mapped_data;
	}

	/**
	 * Generate field mapping from form fields to CRM fields.
	 *
	 * @param array $form_fields Array of form field names.
	 * @return array Mapping of form fields to CRM field IDs.
	 */
	public static function auto_map_fields( $form_fields ) {
		$mapping = [];

		foreach ( $form_fields as $field ) {
			$field_id = self::get_field_id( $field );
			if ( $field_id ) {
				$mapping[ $field ] = $field_id;
			}
		}

		return $mapping;
	}
}
