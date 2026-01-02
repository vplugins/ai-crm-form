<?php
/**
 * Class Test_REST_API
 *
 * @package AI_CRM_Form
 */

/**
 * REST API test case.
 */
class Test_REST_API extends WP_UnitTestCase {

	/**
	 * REST server instance.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	/**
	 * Administrator user ID.
	 *
	 * @var int
	 */
	private $admin_id;

	/**
	 * Set up test fixtures.
	 */
	public function set_up() {
		parent::set_up();

		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );

		// Create an admin user.
		$this->admin_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;

		parent::tear_down();
	}

	/**
	 * Test that REST routes are registered.
	 */
	public function test_routes_registered() {
		$routes = $this->server->get_routes();

		$this->assertArrayHasKey( '/aicrmform/v1', $routes );
		$this->assertArrayHasKey( '/aicrmform/v1/forms', $routes );
		$this->assertArrayHasKey( '/aicrmform/v1/forms/(?P<id>\\d+)', $routes );
		$this->assertArrayHasKey( '/aicrmform/v1/generate', $routes );
		$this->assertArrayHasKey( '/aicrmform/v1/submit', $routes );
	}

	/**
	 * Test get forms endpoint.
	 */
	public function test_get_forms() {
		wp_set_current_user( $this->admin_id );

		$request  = new WP_REST_Request( 'GET', '/aicrmform/v1/forms' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'forms', $data );
	}

	/**
	 * Test get forms requires authentication.
	 */
	public function test_get_forms_requires_auth() {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'GET', '/aicrmform/v1/forms' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test create form endpoint.
	 */
	public function test_create_form() {
		wp_set_current_user( $this->admin_id );

		$request = new WP_REST_Request( 'POST', '/aicrmform/v1/forms' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'form_config' => [
						'form_name' => 'API Test Form',
						'fields'    => [
							[
								'name'  => 'email',
								'label' => 'Email',
								'type'  => 'email',
							],
						],
					],
					'crm_form_id' => 'FormConfigID-12345678-1234-1234-1234-123456789012',
					'name'        => 'API Test Form',
				]
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'form_id', $data );
	}

	/**
	 * Test get single form endpoint.
	 */
	public function test_get_single_form() {
		wp_set_current_user( $this->admin_id );

		// First create a form.
		$generator   = new AICRMFORM_Form_Generator();
		$form_config = [
			'form_name' => 'Single Form Test',
			'fields'    => [],
		];
		$form_id     = $generator->save_form( $form_config, 'FormConfigID-12345678-1234-1234-1234-123456789012' );

		$request  = new WP_REST_Request( 'GET', '/aicrmform/v1/forms/' . $form_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'form', $data );
		$this->assertEquals( 'Single Form Test', $data['form']->name );
	}

	/**
	 * Test delete form endpoint.
	 */
	public function test_delete_form() {
		wp_set_current_user( $this->admin_id );

		// First create a form.
		$generator   = new AICRMFORM_Form_Generator();
		$form_config = [
			'form_name' => 'Delete Test',
			'fields'    => [],
		];
		$form_id     = $generator->save_form( $form_config, 'FormConfigID-12345678-1234-1234-1234-123456789012' );

		$request  = new WP_REST_Request( 'DELETE', '/aicrmform/v1/forms/' . $form_id );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
	}

	/**
	 * Test form submission endpoint validation.
	 */
	public function test_submit_form_validation() {
		$request = new WP_REST_Request( 'POST', '/aicrmform/v1/submit' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					// Missing form_id.
					'data' => [],
				]
			)
		);

		$response = $this->server->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
	}

	/**
	 * Test generate endpoint requires API key.
	 */
	public function test_generate_requires_api_key() {
		wp_set_current_user( $this->admin_id );
		delete_option( 'aicrmform_settings' );

		$request = new WP_REST_Request( 'POST', '/aicrmform/v1/generate' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'prompt' => 'Create a contact form',
				]
			)
		);

		$response = $this->server->dispatch( $request );

		// Should fail because no API key is configured.
		$this->assertNotEquals( 200, $response->get_status() );
	}
}

