<?php
/**
 * AI Client Class
 *
 * Simple AI client for making API calls to various providers.
 *
 * @package AI_CRM_Form
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI Client Class
 */
class AICRMFORM_AI_Client {

	/**
	 * API key.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Provider (groq, gemini, openai).
	 *
	 * @var string
	 */
	private $provider;

	/**
	 * Model name.
	 *
	 * @var string
	 */
	private $model;

	/**
	 * System instruction.
	 *
	 * @var string
	 */
	private $system_instruction = '';

	/**
	 * Provider endpoints.
	 *
	 * @var array
	 */
	private $endpoints = [
		'groq'   => 'https://api.groq.com/openai/v1/chat/completions',
		'openai' => 'https://api.openai.com/v1/chat/completions',
		'gemini' => 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent',
	];

	/**
	 * Constructor.
	 *
	 * @param string $api_key  API key.
	 * @param string $provider Provider name.
	 * @param string $model    Model name.
	 */
	public function __construct( $api_key, $provider = 'groq', $model = 'llama-3.3-70b-versatile' ) {
		$this->api_key  = $api_key;
		$this->provider = $provider;
		$this->model    = $model;
	}

	/**
	 * Set system instruction.
	 *
	 * @param string $instruction System instruction.
	 */
	public function setSystemInstruction( $instruction ) {
		$this->system_instruction = $instruction;
	}

	/**
	 * Check if client is configured.
	 *
	 * @return bool True if configured.
	 */
	public function isConfigured() {
		return ! empty( $this->api_key );
	}

	/**
	 * Send chat message and get response.
	 *
	 * @param string $message User message.
	 * @return string|array Response text or error array.
	 */
	public function chat( $message ) {
		if ( empty( $this->api_key ) ) {
			return [ 'error' => __( 'API key is not configured.', 'ai-crm-form' ) ];
		}

		if ( 'gemini' === $this->provider ) {
			return $this->chat_gemini( $message );
		}

		// OpenAI-compatible API (Groq, OpenAI).
		return $this->chat_openai_compatible( $message );
	}

	/**
	 * Chat using OpenAI-compatible API (Groq, OpenAI).
	 *
	 * @param string $message User message.
	 * @return string|array Response text or error array.
	 */
	private function chat_openai_compatible( $message ) {
		$endpoint = $this->endpoints[ $this->provider ] ?? $this->endpoints['groq'];

		$messages = [];

		if ( ! empty( $this->system_instruction ) ) {
			$messages[] = [
				'role'    => 'system',
				'content' => $this->system_instruction,
			];
		}

		$messages[] = [
			'role'    => 'user',
			'content' => $message,
		];

		$body = [
			'model'       => $this->model,
			'messages'    => $messages,
			'temperature' => 0.7,
			'max_tokens'  => 4096,
		];

		$response = wp_remote_post(
			$endpoint,
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode( $body ),
				'timeout' => 60,
			]
		);

		if ( is_wp_error( $response ) ) {
			return [ 'error' => $response->get_error_message() ];
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( $status_code >= 400 ) {
			$error_message = $data['error']['message'] ?? __( 'API request failed.', 'ai-crm-form' );
			return [ 'error' => $error_message ];
		}

		if ( isset( $data['choices'][0]['message']['content'] ) ) {
			return $data['choices'][0]['message']['content'];
		}

		return [ 'error' => __( 'Unexpected API response format.', 'ai-crm-form' ) ];
	}

	/**
	 * Chat using Google Gemini API.
	 *
	 * @param string $message User message.
	 * @return string|array Response text or error array.
	 */
	private function chat_gemini( $message ) {
		$endpoint = str_replace( '{model}', $this->model, $this->endpoints['gemini'] );
		$endpoint = add_query_arg( 'key', $this->api_key, $endpoint );

		$contents = [];

		if ( ! empty( $this->system_instruction ) ) {
			$contents[] = [
				'role'  => 'user',
				'parts' => [
					[ 'text' => 'System instruction: ' . $this->system_instruction ],
				],
			];
			$contents[] = [
				'role'  => 'model',
				'parts' => [
					[ 'text' => 'I understand. I will follow these instructions.' ],
				],
			];
		}

		$contents[] = [
			'role'  => 'user',
			'parts' => [
				[ 'text' => $message ],
			],
		];

		$body = [
			'contents'         => $contents,
			'generationConfig' => [
				'temperature'   => 0.7,
				'maxOutputTokens' => 4096,
			],
		];

		$response = wp_remote_post(
			$endpoint,
			[
				'headers' => [
					'Content-Type' => 'application/json',
				],
				'body'    => wp_json_encode( $body ),
				'timeout' => 60,
			]
		);

		if ( is_wp_error( $response ) ) {
			return [ 'error' => $response->get_error_message() ];
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( $status_code >= 400 ) {
			$error_message = $data['error']['message'] ?? __( 'API request failed.', 'ai-crm-form' );
			return [ 'error' => $error_message ];
		}

		if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
			return $data['candidates'][0]['content']['parts'][0]['text'];
		}

		return [ 'error' => __( 'Unexpected API response format.', 'ai-crm-form' ) ];
	}
}

