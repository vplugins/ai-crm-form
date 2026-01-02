<?php
/**
 * Class Test_Shortcode
 *
 * @package AI_CRM_Form
 */

/**
 * Shortcode test case.
 */
class Test_Shortcode extends WP_UnitTestCase {

	/**
	 * Set up test fixtures.
	 */
	public function set_up() {
		parent::set_up();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tear_down() {
		parent::tear_down();
	}

	/**
	 * Test that the shortcode class exists.
	 */
	public function test_class_exists() {
		$this->assertTrue( class_exists( 'AICRMFORM_Form_Shortcode' ) );
	}

	/**
	 * Test shortcode is registered.
	 */
	public function test_shortcode_registered() {
		global $shortcode_tags;
		$this->assertArrayHasKey( 'ai_crm_form', $shortcode_tags );
	}

	/**
	 * Test shortcode with invalid form ID.
	 */
	public function test_shortcode_invalid_id() {
		$output = do_shortcode( '[ai_crm_form id="999999"]' );
		$this->assertStringContainsString( 'Form not found', $output );
	}

	/**
	 * Test shortcode without ID.
	 */
	public function test_shortcode_missing_id() {
		$output = do_shortcode( '[ai_crm_form]' );
		$this->assertStringContainsString( 'Form ID is required', $output );
	}

	/**
	 * Test shortcode with valid form.
	 */
	public function test_shortcode_valid_form() {
		// Create a test form.
		$generator   = new AICRMFORM_Form_Generator();
		$form_config = [
			'form_name'          => 'Shortcode Test Form',
			'fields'             => [
				[
					'name'     => 'first_name',
					'label'    => 'First Name',
					'type'     => 'text',
					'required' => true,
				],
				[
					'name'     => 'email',
					'label'    => 'Email',
					'type'     => 'email',
					'required' => true,
				],
			],
			'submit_button_text' => 'Submit',
			'success_message'    => 'Thank you!',
		];

		$form_id = $generator->save_form( $form_config, 'FormConfigID-12345678-1234-1234-1234-123456789012' );

		$output = do_shortcode( '[ai_crm_form id="' . $form_id . '"]' );

		// Check that the form wrapper is present.
		$this->assertStringContainsString( 'aicrmform-wrapper', $output );
		$this->assertStringContainsString( 'aicrmform-form', $output );

		// Check that form fields are present.
		$this->assertStringContainsString( 'First Name', $output );
		$this->assertStringContainsString( 'Email', $output );

		// Check that submit button is present.
		$this->assertStringContainsString( 'Submit', $output );
	}

	/**
	 * Test shortcode renders different field types.
	 */
	public function test_shortcode_field_types() {
		$generator   = new AICRMFORM_Form_Generator();
		$form_config = [
			'form_name' => 'Field Types Test',
			'fields'    => [
				[
					'name'  => 'text_field',
					'label' => 'Text Field',
					'type'  => 'text',
				],
				[
					'name'  => 'email_field',
					'label' => 'Email Field',
					'type'  => 'email',
				],
				[
					'name'  => 'tel_field',
					'label' => 'Phone Field',
					'type'  => 'tel',
				],
				[
					'name'  => 'textarea_field',
					'label' => 'Textarea Field',
					'type'  => 'textarea',
				],
				[
					'name'    => 'select_field',
					'label'   => 'Select Field',
					'type'    => 'select',
					'options' => [ 'Option 1', 'Option 2' ],
				],
			],
		];

		$form_id = $generator->save_form( $form_config, 'FormConfigID-12345678-1234-1234-1234-123456789012' );

		$output = do_shortcode( '[ai_crm_form id="' . $form_id . '"]' );

		// Check for different input types.
		$this->assertStringContainsString( 'type="text"', $output );
		$this->assertStringContainsString( 'type="email"', $output );
		$this->assertStringContainsString( 'type="tel"', $output );
		$this->assertStringContainsString( '<textarea', $output );
		$this->assertStringContainsString( '<select', $output );
	}

	/**
	 * Test inactive form is not displayed.
	 */
	public function test_shortcode_inactive_form() {
		$generator   = new AICRMFORM_Form_Generator();
		$form_config = [
			'form_name' => 'Inactive Form',
			'fields'    => [],
		];

		$form_id = $generator->save_form( $form_config, 'FormConfigID-12345678-1234-1234-1234-123456789012' );

		// Set form to inactive.
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'aicrmform_forms',
			[ 'status' => 'inactive' ],
			[ 'id' => $form_id ]
		);

		$output = do_shortcode( '[ai_crm_form id="' . $form_id . '"]' );

		// Inactive forms should not display.
		$this->assertStringContainsString( 'not available', $output );
	}
}

