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

		return new WP_REST_Response(
			[
				'success' => false,
				'error'   => $result['error'] ?? __( 'Submission failed.', 'ai-crm-form' ),
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
		$result   = $importer->import_form( $plugin_key, $form_id, $use_same_shortcode );

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
			'cf7' => 'contact-form-7/wp-contact-form-7.php',
			// Add more plugins here as needed.
		];

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

