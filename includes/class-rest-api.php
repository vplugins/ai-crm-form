<?php
/**
 * REST API Endpoints
 *
 * @package AI_CRM_Form
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Class
 */
class AICRMFORM_REST_API {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	private $namespace = 'ai-crm-form/v1';

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		// Generate form with AI.
		register_rest_route(
			$this->namespace,
			'/generate',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'generate_form' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Refine form with AI.
		register_rest_route(
			$this->namespace,
			'/refine',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'refine_form' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Save form.
		register_rest_route(
			$this->namespace,
			'/forms',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'save_form' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Get all forms.
		register_rest_route(
			$this->namespace,
			'/forms',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_forms' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Get single form.
		register_rest_route(
			$this->namespace,
			'/forms/(?P<id>\d+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_form' ],
				'permission_callback' => '__return_true',
			]
		);

		// Get form debug info (for troubleshooting).
		register_rest_route(
			$this->namespace,
			'/forms/(?P<id>\d+)/debug',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_form_debug' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Repair form field mappings.
		register_rest_route(
			$this->namespace,
			'/forms/(?P<id>\d+)/repair-mappings',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'repair_form_mappings' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Update form.
		register_rest_route(
			$this->namespace,
			'/forms/(?P<id>\d+)',
			[
				'methods'             => 'PUT',
				'callback'            => [ $this, 'update_form' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Delete form.
		register_rest_route(
			$this->namespace,
			'/forms/(?P<id>\d+)',
			[
				'methods'             => 'DELETE',
				'callback'            => [ $this, 'delete_form' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Submit form (public endpoint).
		register_rest_route(
			$this->namespace,
			'/submit/(?P<id>\d+)',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'submit_form' ],
				'permission_callback' => '__return_true',
			]
		);

		// Test CRM API connection.
		register_rest_route(
			$this->namespace,
			'/test-connection',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'test_connection' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Get field mapping.
		register_rest_route(
			$this->namespace,
			'/fields',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_fields' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Get submissions.
		register_rest_route(
			$this->namespace,
			'/submissions',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_submissions' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Get single submission.
		register_rest_route(
			$this->namespace,
			'/submissions/(?P<id>\d+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_submission' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Export submissions as CSV.
		register_rest_route(
			$this->namespace,
			'/submissions/export',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'export_submissions' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Get available import sources.
		register_rest_route(
			$this->namespace,
			'/import/sources',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_import_sources' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Import a form.
		register_rest_route(
			$this->namespace,
			'/import',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'import_form' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);

		// Deactivate a plugin.
		register_rest_route(
			$this->namespace,
			'/deactivate-plugin',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'deactivate_plugin' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);
	}

	/**
	 * Admin permission check.
	 *
	 * @return bool Whether the user has permission.
	 */
	public function admin_permission_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Generate form with AI.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function generate_form( $request ) {
		$prompt = $request->get_param( 'prompt' );

		if ( empty( $prompt ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'error'   => __( 'Prompt is required.', 'ai-crm-form' ),
				],
				400
			);
		}

		$generator = new AICRMFORM_Form_Generator();
		$result    = $generator->generate_form( $prompt );

		if ( $result['success'] ) {
			return new WP_REST_Response( $result, 200 );
		}

		return new WP_REST_Response( $result, 400 );
	}

	/**
	 * Refine form with AI.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function refine_form( $request ) {
		$current_config = $request->get_param( 'current_config' );
		$refinement     = $request->get_param( 'refinement' );

		if ( empty( $current_config ) || empty( $refinement ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'error'   => __( 'Current config and refinement are required.', 'ai-crm-form' ),
				],
				400
			);
		}

		$generator = new AICRMFORM_Form_Generator();
		$result    = $generator->refine_form( $current_config, $refinement );

		if ( $result['success'] ) {
			return new WP_REST_Response( $result, 200 );
		}

		return new WP_REST_Response( $result, 400 );
	}

	/**
	 * Save form.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function save_form( $request ) {
		$form_config = $request->get_param( 'form_config' );
		$crm_form_id = $request->get_param( 'crm_form_id' );
		$name        = $request->get_param( 'name' );
		$description = $request->get_param( 'description' );

		if ( empty( $form_config ) || empty( $crm_form_id ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'error'   => __( 'Form config and CRM form ID are required.', 'ai-crm-form' ),
				],
				400
			);
		}

		// Ensure form_config is an array.
		if ( is_string( $form_config ) ) {
			$form_config = json_decode( $form_config, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				return new WP_REST_Response(
					[
						'success' => false,
						'error'   => __( 'Invalid form config JSON.', 'ai-crm-form' ),
					],
					400
				);
			}
		}

		$generator = new AICRMFORM_Form_Generator();
		$result    = $generator->save_form( $form_config, $crm_form_id, $name, $description );

		if ( $result['success'] ) {
			return new WP_REST_Response(
				[
					'success'   => true,
					'form_id'   => $result['form_id'],
					'shortcode' => '[ai_crm_form id="' . $result['form_id'] . '"]',
				],
				201
			);
		}

		return new WP_REST_Response(
			[
				'success' => false,
				'error'   => $result['error'] ?? __( 'Failed to save form.', 'ai-crm-form' ),
			],
			500
		);
	}

	/**
	 * Get all forms.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function get_forms( $request ) {
		$status    = $request->get_param( 'status' );
		$generator = new AICRMFORM_Form_Generator();
		$forms     = $generator->get_all_forms( $status );

		return new WP_REST_Response(
			[
				'success' => true,
				'forms'   => $forms,
			],
			200
		);
	}

	/**
	 * Get single form.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function get_form( $request ) {
		$form_id   = (int) $request->get_param( 'id' );
		$generator = new AICRMFORM_Form_Generator();
		$form      = $generator->get_form( $form_id );

		if ( ! $form ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'error'   => __( 'Form not found.', 'ai-crm-form' ),
				],
				404
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'form'    => $form,
			],
			200
		);
	}

	/**
	 * Get form debug information.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function get_form_debug( $request ) {
		$form_id   = (int) $request->get_param( 'id' );
		$generator = new AICRMFORM_Form_Generator();
		$form      = $generator->get_form( $form_id );

		if ( ! $form ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'error'   => __( 'Form not found.', 'ai-crm-form' ),
				],
				404
			);
		}

		// Build detailed debug info.
		$debug_info = [
			'form_id'         => $form->id,
			'form_name'       => $form->name,
			'crm_form_id'     => $form->crm_form_id,
			'status'          => $form->status,
			'field_mapping'   => $form->field_mapping,
			'fields'          => [],
		];

		// Extract field details.
		if ( ! empty( $form->form_config['fields'] ) ) {
			foreach ( $form->form_config['fields'] as $field ) {
				$field_name  = $field['name'] ?? 'unknown';
				$crm_mapping = $field['field_id'] ?? $field['crm_mapping'] ?? '';

				// Get the actual FieldID that would be used.
				$resolved_field_id = null;
				if ( ! empty( $crm_mapping ) ) {
					if ( strpos( $crm_mapping, 'FieldID-' ) === 0 ) {
						$resolved_field_id = $crm_mapping;
					} else {
						$resolved_field_id = AICRMFORM_Field_Mapping::get_field_id( $crm_mapping );
					}
				}

				$debug_info['fields'][] = [
					'name'              => $field_name,
					'label'             => $field['label'] ?? '',
					'type'              => $field['type'] ?? 'text',
					'crm_mapping_raw'   => $crm_mapping,
					'crm_field_id'      => $resolved_field_id,
					'in_field_mapping'  => isset( $form->field_mapping[ $field_name ] ) ? $form->field_mapping[ $field_name ] : null,
					'mapping_mismatch'  => ( $resolved_field_id && isset( $form->field_mapping[ $field_name ] ) )
						? ( $resolved_field_id !== $form->field_mapping[ $field_name ] )
						: null,
				];
			}
		}

		// Check for potential issues.
		$issues = [];

		if ( empty( $form->crm_form_id ) ) {
			$issues[] = 'CRM Form ID is empty';
		}

		if ( empty( $form->field_mapping ) || count( $form->field_mapping ) === 0 ) {
			$issues[] = 'Field mapping is empty - no fields will be sent to CRM';
		}

		foreach ( $debug_info['fields'] as $field ) {
			if ( empty( $field['crm_field_id'] ) && ! empty( $field['crm_mapping_raw'] ) ) {
				$issues[] = sprintf(
					'Field "%s" has mapping "%s" which could not be resolved to a CRM Field ID',
					$field['name'],
					$field['crm_mapping_raw']
				);
			}

			if ( empty( $field['in_field_mapping'] ) && ! empty( $field['crm_field_id'] ) ) {
				$issues[] = sprintf(
					'Field "%s" has CRM mapping but is not in stored field_mapping (form may need re-save)',
					$field['name']
				);
			}
		}

		$debug_info['issues'] = $issues;
		$debug_info['has_issues'] = count( $issues ) > 0;

		return new WP_REST_Response(
			[
				'success' => true,
				'debug'   => $debug_info,
			],
			200
		);
	}

	/**
	 * Repair form field mappings.
	 *
	 * This regenerates the field_mapping based on the form config fields,
	 * optionally using AI to help with ambiguous mappings.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function repair_form_mappings( $request ) {
		$form_id = (int) $request->get_param( 'id' );
		$use_ai  = (bool) $request->get_param( 'use_ai' );

		$generator = new AICRMFORM_Form_Generator();
		$form      = $generator->get_form( $form_id );

		if ( ! $form ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'error'   => __( 'Form not found.', 'ai-crm-form' ),
				],
				404
			);
		}

		// Get fields from form config.
		$fields = $form->form_config['fields'] ?? [];

		if ( empty( $fields ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'error'   => __( 'Form has no fields to map.', 'ai-crm-form' ),
				],
				400
			);
		}

		// Build new field mapping.
		$new_field_mapping = [];
		$unmapped_fields   = [];
		$mapping_changes   = [];

		foreach ( $fields as &$field ) {
			$field_name = $field['name'] ?? '';
			if ( empty( $field_name ) ) {
				continue;
			}

			// Try to get existing mapping first.
			$existing_mapping = $field['field_id'] ?? $field['crm_mapping'] ?? '';

			// Resolve to full FieldID.
			$field_id = null;

			if ( ! empty( $existing_mapping ) ) {
				if ( strpos( $existing_mapping, 'FieldID-' ) === 0 ) {
					$field_id = $existing_mapping;
				} else {
					$field_id = AICRMFORM_Field_Mapping::get_field_id( $existing_mapping );
				}
			}

			// If no mapping found, try to guess.
			if ( empty( $field_id ) ) {
				$guessed_mapping = $this->guess_crm_mapping_for_repair( $field_name, $field['label'] ?? '' );
				if ( ! empty( $guessed_mapping ) ) {
					$field_id = AICRMFORM_Field_Mapping::get_field_id( $guessed_mapping );
					if ( $field_id ) {
						$field['crm_mapping'] = $guessed_mapping;
						$mapping_changes[] = sprintf(
							'%s → %s (%s)',
							$field_name,
							$guessed_mapping,
							$field_id
						);
					}
				}
			}

			if ( $field_id ) {
				$new_field_mapping[ $field_name ] = $field_id;
				$field['field_id'] = $field_id;
			} else {
				$unmapped_fields[] = $field_name;
			}
		}
		unset( $field );

		// If use_ai is requested and there are unmapped fields, try AI mapping.
		if ( $use_ai && ! empty( $unmapped_fields ) ) {
			$ai_mappings = $this->get_ai_field_mappings( $unmapped_fields, $fields );
			foreach ( $ai_mappings as $field_name => $crm_mapping ) {
				$field_id = AICRMFORM_Field_Mapping::get_field_id( $crm_mapping );
				if ( $field_id ) {
					$new_field_mapping[ $field_name ] = $field_id;
					$mapping_changes[] = sprintf(
						'%s → %s (%s) [AI suggested]',
						$field_name,
						$crm_mapping,
						$field_id
					);

					// Update the field in form_config.
					foreach ( $fields as &$field ) {
						if ( ( $field['name'] ?? '' ) === $field_name ) {
							$field['crm_mapping'] = $crm_mapping;
							$field['field_id']    = $field_id;
							break;
						}
					}
					unset( $field );

					// Remove from unmapped list.
					$unmapped_fields = array_diff( $unmapped_fields, [ $field_name ] );
				}
			}
		}

		// Update the form config with new mappings.
		$form->form_config['fields'] = $fields;

		// Save to database.
		global $wpdb;
		$table_name = $wpdb->prefix . 'aicrmform_forms';

		$result = $wpdb->update(
			$table_name,
			[
				'form_config'   => wp_json_encode( $form->form_config ),
				'field_mapping' => wp_json_encode( $new_field_mapping ),
			],
			[ 'id' => $form_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( false === $result ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'error'   => __( 'Failed to update form mappings.', 'ai-crm-form' ),
				],
				500
			);
		}

		return new WP_REST_Response(
			[
				'success'         => true,
				'message'         => __( 'Field mappings repaired successfully.', 'ai-crm-form' ),
				'field_mapping'   => $new_field_mapping,
				'mapping_changes' => $mapping_changes,
				'unmapped_fields' => $unmapped_fields,
			],
			200
		);
	}

	/**
	 * Guess CRM mapping based on field name and label (for repair).
	 *
	 * @param string $field_name  The field name.
	 * @param string $field_label The field label.
	 * @return string The guessed CRM mapping or empty string.
	 */
	private function guess_crm_mapping_for_repair( $field_name, $field_label = '' ) {
		// Combine name and label for better guessing.
		$text_to_match = strtolower( $field_name . ' ' . $field_label );

		// Remove common prefixes.
		$clean_text = preg_replace( '/^(your|user|contact|my|the|form|field)[_-]?\s*/i', '', $text_to_match );

		// Mappings with keywords.
		$mappings = [
			'first_name'              => [ 'firstname', 'first_name', 'first name', 'fname', 'given' ],
			'last_name'               => [ 'lastname', 'last_name', 'last name', 'lname', 'surname', 'family' ],
			'email'                   => [ 'email', 'mail', 'e-mail' ],
			'phone_number'            => [ 'phone', 'tel', 'telephone', 'mobile', 'cell' ],
			'company_name'            => [ 'company', 'organization', 'org', 'business' ],
			'company_website'         => [ 'website', 'url', 'site', 'web' ],
			'primary_address_line1'   => [ 'address', 'street', 'addr' ],
			'primary_address_city'    => [ 'city', 'town' ],
			'primary_address_state'   => [ 'state', 'province', 'region' ],
			'primary_address_postal'  => [ 'zip', 'postal', 'postcode' ],
			'primary_address_country' => [ 'country' ],
			'message'                 => [ 'message', 'comment', 'inquiry', 'question', 'subject', 'body', 'content' ],
			'source_name'             => [ 'source', 'referral', 'how did you hear' ],
		];

		// Try matching.
		foreach ( $mappings as $crm_field => $keywords ) {
			foreach ( $keywords as $keyword ) {
				if ( strpos( $clean_text, $keyword ) !== false ) {
					return $crm_field;
				}
			}
		}

		// Special case: generic "name" maps to first_name.
		if ( preg_match( '/\bname\b/', $clean_text ) && ! preg_match( '/\b(first|last|full|company)\b/', $clean_text ) ) {
			return 'first_name';
		}

		return '';
	}

	/**
	 * Get AI-suggested field mappings for unmapped fields.
	 *
	 * @param array $unmapped_fields List of unmapped field names.
	 * @param array $all_fields      All form fields for context.
	 * @return array Mapping of field_name => crm_mapping.
	 */
	private function get_ai_field_mappings( $unmapped_fields, $all_fields ) {
		$mappings = [];

		// Check if AI is configured.
		$settings = get_option( 'aicrmform_settings', [] );
		$api_key  = $settings['ai_api_key'] ?? '';

		if ( empty( $api_key ) ) {
			return $mappings;
		}

		// Get available CRM fields.
		$available_fields = array_keys( array_merge(
			AICRMFORM_Field_Mapping::CONTACT_FIELDS,
			AICRMFORM_Field_Mapping::STANDARD_CONTACT_FIELDS,
			AICRMFORM_Field_Mapping::UTM_FIELDS,
			AICRMFORM_Field_Mapping::CONSENT_FIELDS,
			AICRMFORM_Field_Mapping::COMPANY_FIELDS
		) );

		// Build context about all fields.
		$fields_context = [];
		foreach ( $all_fields as $field ) {
			$fields_context[] = sprintf(
				'- %s (label: "%s", type: %s)',
				$field['name'] ?? 'unknown',
				$field['label'] ?? '',
				$field['type'] ?? 'text'
			);
		}

		// Build prompt.
		$prompt = sprintf(
			"You are a CRM field mapping expert. Given the following form fields that need to be mapped to CRM fields, suggest the best mapping.\n\n" .
			"Form fields to map:\n%s\n\n" .
			"Available CRM field names:\n%s\n\n" .
			"For each form field, respond with ONLY a JSON object mapping form field names to CRM field names. " .
			"Only include mappings you are confident about. Use null for fields that have no clear mapping.\n\n" .
			"Example response: {\"your-name\": \"first_name\", \"your-email\": \"email\", \"random-field\": null}",
			implode( "\n", array_map( function ( $name ) use ( $all_fields ) {
				$field = array_filter( $all_fields, function ( $f ) use ( $name ) {
					return ( $f['name'] ?? '' ) === $name;
				} );
				$field = reset( $field );
				return sprintf( '- %s (label: "%s")', $name, $field['label'] ?? '' );
			}, $unmapped_fields ) ),
			implode( ', ', $available_fields )
		);

		try {
			$ai_client = new AICRMFORM_AI_Client();
			$response  = $ai_client->chat( $prompt );

			if ( is_string( $response ) ) {
				// Extract JSON from response.
				$json_start = strpos( $response, '{' );
				$json_end   = strrpos( $response, '}' );

				if ( false !== $json_start && false !== $json_end ) {
					$json_string = substr( $response, $json_start, $json_end - $json_start + 1 );
					$parsed      = json_decode( $json_string, true );

					if ( is_array( $parsed ) ) {
						foreach ( $parsed as $field_name => $crm_field ) {
							if ( ! empty( $crm_field ) && in_array( $crm_field, $available_fields, true ) ) {
								$mappings[ $field_name ] = $crm_field;
							}
						}
					}
				}
			}
		} catch ( \Exception $e ) {
			error_log( 'AI CRM Form: AI mapping error - ' . $e->getMessage() );
		}

		return $mappings;
	}

	/**
	 * Update form.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function update_form( $request ) {
		$form_id     = (int) $request->get_param( 'id' );
		$form_config = $request->get_param( 'form_config' );
		$updates     = $request->get_param( 'updates' ) ?? [];

		if ( empty( $form_config ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'error'   => __( 'Form config is required.', 'ai-crm-form' ),
				],
				400
			);
		}

		// Ensure form_config is an array.
		if ( is_string( $form_config ) ) {
			$form_config = json_decode( $form_config, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				return new WP_REST_Response(
					[
						'success' => false,
						'error'   => __( 'Invalid form config JSON.', 'ai-crm-form' ),
					],
					400
				);
			}
		}

		$generator = new AICRMFORM_Form_Generator();
		$result    = $generator->update_form( $form_id, $form_config, $updates );

		if ( $result ) {
			return new WP_REST_Response(
				[
					'success' => true,
					'message' => __( 'Form updated successfully.', 'ai-crm-form' ),
				],
				200
			);
		}

		return new WP_REST_Response(
			[
				'success' => false,
				'error'   => __( 'Failed to update form.', 'ai-crm-form' ),
			],
			500
		);
	}

	/**
	 * Delete form.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function delete_form( $request ) {
		$form_id   = (int) $request->get_param( 'id' );
		$generator = new AICRMFORM_Form_Generator();
		$result    = $generator->delete_form( $form_id );

		if ( $result ) {
			return new WP_REST_Response(
				[
					'success' => true,
					'message' => __( 'Form deleted successfully.', 'ai-crm-form' ),
				],
				200
			);
		}

		return new WP_REST_Response(
			[
				'success' => false,
				'error'   => __( 'Failed to delete form.', 'ai-crm-form' ),
			],
			500
		);
	}

	/**
	 * Submit form.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function submit_form( $request ) {
		$form_id   = (int) $request->get_param( 'id' );
		$form_data = $request->get_param( 'data' );

		if ( empty( $form_data ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'error'   => __( 'Form data is required.', 'ai-crm-form' ),
				],
				400
			);
		}

		// Get the form.
		$generator = new AICRMFORM_Form_Generator();
		$form      = $generator->get_form( $form_id );

		if ( ! $form ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'error'   => __( 'Form not found.', 'ai-crm-form' ),
				],
				404
			);
		}

		// Check if CRM Form ID is configured.
		if ( empty( $form->crm_form_id ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'error'   => __( 'This form is not configured with a CRM Form ID. Please edit the form and add a valid CRM Form ID.', 'ai-crm-form' ),
				],
				400
			);
		}

		// Log the submission attempt for debugging.
		error_log( sprintf(
			'AI CRM Form: Submitting form %d with CRM Form ID: %s, Field mapping: %s, Form data: %s',
			$form_id,
			$form->crm_form_id,
			wp_json_encode( $form->field_mapping ),
			wp_json_encode( $form_data )
		) );

		// Submit to CRM.
		$crm_api = new AICRMFORM_CRM_API();
		$result  = $crm_api->submit_with_mapping( $form_data, $form->field_mapping, $form->crm_form_id );

		// Log the submission.
		$status = $result['success'] ? 'success' : 'failed';
		$crm_api->log_submission( $form_id, $form_data, $result, $status );

		if ( $result['success'] ) {
			$success_message = $form->form_config['success_message'] ?? __( 'Thank you for your submission!', 'ai-crm-form' );
			return new WP_REST_Response(
				[
					'success' => true,
					'message' => $success_message,
				],
				200
			);
		}

		// Include debug info for troubleshooting.
		$debug_info = [
			'form_data'       => $form_data,
			'field_mapping'   => $form->field_mapping,
			'mapped_data'     => $crm_api->get_last_mapped_data(),
			'crm_payload'     => $crm_api->get_last_payload(),
			'crm_form_id'     => $form->crm_form_id,
			'response_code'   => $result['response_code'] ?? null,
			'response_body'   => $result['response_body'] ?? null,
		];

		return new WP_REST_Response(
			[
				'success' => false,
				'error'   => $result['error'] ?? __( 'Submission failed.', 'ai-crm-form' ),
				'debug'   => $debug_info,
			],
			400
		);
	}

	/**
	 * Test CRM API connection.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function test_connection( $request ) {
		$crm_api = new AICRMFORM_CRM_API();
		$result  = $crm_api->test_connection();

		$status_code = $result['success'] ? 200 : 400;
		return new WP_REST_Response( $result, $status_code );
	}

	/**
	 * Get available fields.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function get_fields( $request ) {
		return new WP_REST_Response(
			[
				'success' => true,
				'fields'  => AICRMFORM_Field_Mapping::get_all_fields(),
				'common'  => AICRMFORM_Field_Mapping::get_common_form_fields(),
			],
			200
		);
	}

	/**
	 * Get submissions.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function get_submissions( $request ) {
		$form_id = $request->get_param( 'form_id' );
		$limit   = (int) ( $request->get_param( 'limit' ) ?? 20 );
		$offset  = (int) ( $request->get_param( 'offset' ) ?? 0 );

		$crm_api = new AICRMFORM_CRM_API();

		if ( $form_id ) {
			$submissions = $crm_api->get_form_submissions( (int) $form_id, $limit, $offset );
			$total       = $crm_api->count_submissions( (int) $form_id );
		} else {
			$submissions = $crm_api->get_all_submissions( $limit, $offset );
			$total       = $crm_api->count_submissions();
		}

		return new WP_REST_Response(
			[
				'success'     => true,
				'submissions' => $submissions,
				'total'       => $total,
			],
			200
		);
	}

	/**
	 * Get single submission.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function get_submission( $request ) {
		$submission_id = (int) $request->get_param( 'id' );
		$crm_api       = new AICRMFORM_CRM_API();
		$submission    = $crm_api->get_submission( $submission_id );

		if ( ! $submission ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'error'   => __( 'Submission not found.', 'ai-crm-form' ),
				],
				404
			);
		}

		// Decode JSON fields.
		$submission->submission_data = json_decode( $submission->submission_data, true );
		$submission->crm_response    = json_decode( $submission->crm_response, true );

		return new WP_REST_Response(
			[
				'success'    => true,
				'submission' => $submission,
			],
			200
		);
	}

	/**
	 * Export submissions as CSV.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response with CSV data.
	 */
	public function export_submissions( $request ) {
		$form_id     = $request->get_param( 'form_id' );
		$status      = $request->get_param( 'status' );
		$date_from   = $request->get_param( 'date_from' );
		$date_to     = $request->get_param( 'date_to' );
		$ids         = $request->get_param( 'ids' );

		$crm_api   = new AICRMFORM_CRM_API();
		$generator = new AICRMFORM_Form_Generator();
		$forms     = $generator->get_all_forms();

		// Create form ID to name mapping.
		$form_names = [];
		foreach ( $forms as $form ) {
			$form_names[ (string) $form->id ] = $form->name;
		}

		// Get all submissions (no limit for export).
		$submissions = $crm_api->get_all_submissions( 10000, 0 );

		// Apply filters.
		$filtered = array_filter(
			$submissions,
			function ( $sub ) use ( $form_id, $status, $date_from, $date_to, $ids ) {
				// Filter by specific IDs if provided.
				if ( $ids ) {
					$id_array = array_map( 'intval', explode( ',', $ids ) );
					if ( ! in_array( (int) $sub->id, $id_array, true ) ) {
						return false;
					}
				}

				// Filter by form.
				if ( $form_id && (string) $sub->form_id !== (string) $form_id ) {
					return false;
				}

				// Filter by status.
				if ( $status && $sub->status !== $status ) {
					return false;
				}

				// Filter by date range.
				$sub_date = gmdate( 'Y-m-d', strtotime( $sub->created_at ) );
				if ( $date_from && $sub_date < $date_from ) {
					return false;
				}
				if ( $date_to && $sub_date > $date_to ) {
					return false;
				}

				return true;
			}
		);

		// Collect all unique field names from submission data.
		$all_fields = [];
		foreach ( $filtered as $submission ) {
			$data = json_decode( $submission->submission_data, true );
			if ( is_array( $data ) ) {
				foreach ( array_keys( $data ) as $field ) {
					if ( ! in_array( $field, $all_fields, true ) ) {
						$all_fields[] = $field;
					}
				}
			}
		}

		// Build CSV.
		$headers = array_merge(
			[ 'ID', 'Form', 'Status', 'IP Address', 'Submitted At' ],
			$all_fields
		);

		$rows = [];
		foreach ( $filtered as $submission ) {
			$data      = json_decode( $submission->submission_data, true ) ?: [];
			$form_name = $form_names[ (string) $submission->form_id ] ?? $submission->form_id;

			$row = [
				$submission->id,
				$form_name,
				$submission->status,
				$submission->ip_address,
				$submission->created_at,
			];

			// Add each field value.
			foreach ( $all_fields as $field ) {
				$value = $data[ $field ] ?? '';
				// Handle arrays (like checkboxes).
				if ( is_array( $value ) ) {
					$value = implode( ', ', $value );
				}
				$row[] = $value;
			}

			$rows[] = $row;
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'headers' => $headers,
				'rows'    => $rows,
				'count'   => count( $rows ),
			],
			200
		);
	}

	/**
	 * Get available import sources.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function get_import_sources( $request ) {
		$importer = new AICRMFORM_Form_Importer();
		$sources  = $importer->get_available_plugins();

		return new WP_REST_Response(
			[
				'success' => true,
				'sources' => $sources,
			],
			200
		);
	}

	/**
	 * Import a form from another plugin.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function import_form( $request ) {
		$plugin_key = sanitize_text_field( $request->get_param( 'plugin' ) );
		$form_id    = (int) $request->get_param( 'form_id' );
		$use_same_shortcode = (bool) $request->get_param( 'use_same_shortcode' );
		$crm_form_id = sanitize_text_field( $request->get_param( 'crm_form_id' ) ?? '' );

		if ( empty( $plugin_key ) || empty( $form_id ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'error'   => __( 'Plugin and form ID are required.', 'ai-crm-form' ),
				],
				400
			);
		}

		$importer = new AICRMFORM_Form_Importer();
		$result   = $importer->import_form( $plugin_key, $form_id, $use_same_shortcode, $crm_form_id );

		$status_code = $result['success'] ? 200 : 400;

		return new WP_REST_Response( $result, $status_code );
	}

	/**
	 * Deactivate a plugin.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response The response.
	 */
	public function deactivate_plugin( $request ) {
		$plugin_key = sanitize_text_field( $request->get_param( 'plugin' ) );

		$plugin_files = [
			'cf7'     => 'contact-form-7/wp-contact-form-7.php',
			'gravity' => 'gravityforms/gravityforms.php',
			'wpforms' => 'wpforms-lite/wpforms.php',
		];

		// Check for WPForms Pro if Lite is not the target.
		if ( 'wpforms' === $plugin_key && ! file_exists( WP_PLUGIN_DIR . '/wpforms-lite/wpforms.php' ) ) {
			$plugin_files['wpforms'] = 'wpforms/wpforms.php';
		}

		if ( empty( $plugin_key ) || ! isset( $plugin_files[ $plugin_key ] ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'error'   => __( 'Invalid plugin specified.', 'ai-crm-form' ),
				],
				400
			);
		}

		$plugin_file = $plugin_files[ $plugin_key ];

		// Check if plugin is active.
		if ( ! is_plugin_active( $plugin_file ) ) {
			return new WP_REST_Response(
				[
					'success' => true,
					'message' => __( 'Plugin is already deactivated.', 'ai-crm-form' ),
				],
				200
			);
		}

		// Deactivate the plugin.
		deactivate_plugins( $plugin_file );

		// Check if it worked.
		if ( is_plugin_active( $plugin_file ) ) {
			return new WP_REST_Response(
				[
					'success' => false,
					'error'   => __( 'Failed to deactivate plugin.', 'ai-crm-form' ),
				],
				500
			);
		}

		return new WP_REST_Response(
			[
				'success' => true,
				'message' => __( 'Plugin deactivated successfully.', 'ai-crm-form' ),
			],
			200
		);
	}
}
