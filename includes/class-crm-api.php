<?php
/**
 * CRM API Integration
 *
 * @package AI_CRM_Form
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CRM API Class
 *
 * Handles communication with the CRM API for form submissions.
 */
class AICRMFORM_CRM_API {

	/**
	 * Default CRM API URL.
	 */
	const DEFAULT_API_URL = 'https://forms-prod.apigateway.co/forms.v1.FormSubmissionService/CreateFormSubmission';

	/**
	 * API URL.
	 *
	 * @var string
	 */
	private $api_url;

	/**
	 * Form ID.
	 *
	 * @var string
	 */
	private $form_id;

	/**
	 * Constructor.
	 *
	 * @param string|null $api_url Optional API URL override.
	 * @param string|null $form_id Optional Form ID override.
	 */
	public function __construct( $api_url = null, $form_id = null ) {
		$settings = get_option( 'aicrmform_settings', [] );

		$this->api_url = $api_url ?? ( $settings['crm_api_url'] ?? self::DEFAULT_API_URL );
		$this->form_id = $form_id ?? ( $settings['form_id'] ?? '' );
	}

	/**
	 * Submit form data to CRM API.
	 *
	 * @param array  $form_data The form data with CRM field IDs as keys.
	 * @param string $form_id   Optional form ID to override default.
	 * @return array Response with success status and data or error.
	 */
	public function submit( $form_data, $form_id = null ) {
		$form_id = $form_id ?? $this->form_id;

		if ( empty( $form_id ) ) {
			return [
				'success' => false,
				'error'   => __( 'Form ID is required.', 'ai-crm-form' ),
			];
		}

		// Build the values JSON string.
		$values_json = wp_json_encode( $form_data );

		// Build the submission payload.
		$payload = [
			'submission' => [
				'form_id' => $form_id,
				'values'  => $values_json,
			],
		];

		// Send the request.
		$response = wp_remote_post(
			$this->api_url,
			[
				'headers' => [
					'Content-Type' => 'application/json',
					'Accept'       => '*/*',
				],
				'body'    => wp_json_encode( $payload ),
				'timeout' => 30,
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'error'   => $response->get_error_message(),
			];
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code >= 200 && $response_code < 300 ) {
			return [
				'success' => true,
				'data'    => json_decode( $response_body, true ),
			];
		}

		return [
			'success'       => false,
			'error'         => __( 'API request failed.', 'ai-crm-form' ),
			'response_code' => $response_code,
			'response_body' => $response_body,
		];
	}

	/**
	 * Submit form with field mapping.
	 *
	 * @param array  $form_data     Raw form data with field names as keys.
	 * @param array  $field_mapping Mapping of field names to CRM field IDs.
	 * @param string $form_id       The CRM form ID.
	 * @return array Response with success status and data or error.
	 */
	public function submit_with_mapping( $form_data, $field_mapping, $form_id ) {
		$mapped_data = AICRMFORM_Field_Mapping::map_form_data_to_crm( $form_data, $field_mapping );
		return $this->submit( $mapped_data, $form_id );
	}

	/**
	 * Test API connection.
	 *
	 * @return array Response with success status.
	 */
	public function test_connection() {
		// Simple HEAD request to check if API is reachable.
		$response = wp_remote_get(
			$this->api_url,
			[
				'timeout' => 10,
				'headers' => [
					'Accept' => '*/*',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'error'   => $response->get_error_message(),
			];
		}

		$response_code = wp_remote_retrieve_response_code( $response );

		// Any response (even error) means the API is reachable.
		return [
			'success'       => true,
			'response_code' => $response_code,
			'message'       => __( 'API endpoint is reachable.', 'ai-crm-form' ),
		];
	}

	/**
	 * Get the API URL.
	 *
	 * @return string The API URL.
	 */
	public function get_api_url() {
		return $this->api_url;
	}

	/**
	 * Set the API URL.
	 *
	 * @param string $url The API URL.
	 */
	public function set_api_url( $url ) {
		$this->api_url = $url;
	}

	/**
	 * Get the Form ID.
	 *
	 * @return string The Form ID.
	 */
	public function get_form_id() {
		return $this->form_id;
	}

	/**
	 * Set the Form ID.
	 *
	 * @param string $form_id The Form ID.
	 */
	public function set_form_id( $form_id ) {
		$this->form_id = $form_id;
	}

	/**
	 * Log submission to database.
	 *
	 * @param int    $form_id         The internal form ID.
	 * @param array  $submission_data The submitted data.
	 * @param array  $crm_response    The CRM API response.
	 * @param string $status          The submission status.
	 * @return int|false The submission ID or false on failure.
	 */
	public function log_submission( $form_id, $submission_data, $crm_response, $status = 'success' ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'aicrmform_submissions';

		$result = $wpdb->insert(
			$table_name,
			[
				'form_id'         => $form_id,
				'submission_data' => wp_json_encode( $submission_data ),
				'crm_response'    => wp_json_encode( $crm_response ),
				'status'          => $status,
				'ip_address'      => $this->get_client_ip(),
				'user_agent'      => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			],
			[
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			]
		);

		if ( $result ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Get client IP address.
	 *
	 * @return string The client IP address.
	 */
	private function get_client_ip() {
		$ip_keys = [
			'HTTP_CF_CONNECTING_IP',
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		];

		foreach ( $ip_keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				// Handle comma-separated IPs (X-Forwarded-For can have multiple).
				if ( strpos( $ip, ',' ) !== false ) {
					$ips = explode( ',', $ip );
					$ip  = trim( $ips[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}

	/**
	 * Get submission by ID.
	 *
	 * @param int $submission_id The submission ID.
	 * @return object|null The submission object or null.
	 */
	public function get_submission( $submission_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'aicrmform_submissions';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$submission_id
			)
		);
	}

	/**
	 * Get submissions for a form.
	 *
	 * @param int $form_id The form ID.
	 * @param int $limit   Number of results to return.
	 * @param int $offset  Offset for pagination.
	 * @return array Array of submission objects.
	 */
	public function get_form_submissions( $form_id, $limit = 20, $offset = 0 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'aicrmform_submissions';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE form_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$form_id,
				$limit,
				$offset
			)
		);
	}

	/**
	 * Get all submissions.
	 *
	 * @param int $limit  Number of results to return.
	 * @param int $offset Offset for pagination.
	 * @return array Array of submission objects.
	 */
	public function get_all_submissions( $limit = 20, $offset = 0 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'aicrmform_submissions';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} ORDER BY created_at DESC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$limit,
				$offset
			)
		);
	}

	/**
	 * Count submissions for a form.
	 *
	 * @param int|null $form_id The form ID (null for all forms).
	 * @return int The count of submissions.
	 */
	public function count_submissions( $form_id = null ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'aicrmform_submissions';

		if ( $form_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table_name} WHERE form_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$form_id
				)
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table_name}" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}
}

