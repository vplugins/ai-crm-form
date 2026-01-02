<?php
/**
 * Class Test_Form_Generator
 *
 * @package AI_CRM_Form
 */

/**
 * Form Generator test case.
 */
class Test_Form_Generator extends WP_UnitTestCase {

	/**
	 * Form generator instance.
	 *
	 * @var AICRMFORM_Form_Generator
	 */
	private $generator;

	/**
	 * Set up test fixtures.
	 */
	public function set_up() {
		parent::set_up();
		$this->generator = new AICRMFORM_Form_Generator();
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tear_down() {
		parent::tear_down();
	}

	/**
	 * Test that the generator class exists.
	 */
	public function test_class_exists() {
		$this->assertTrue( class_exists( 'AICRMFORM_Form_Generator' ) );
	}

	/**
	 * Test form saving.
	 */
	public function test_save_form() {
		$form_config = [
			'form_name'   => 'Test Form',
			'fields'      => [
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
			'submit_text' => 'Submit',
		];

		$crm_form_id = 'FormConfigID-12345678-1234-1234-1234-123456789012';

		$form_id = $this->generator->save_form( $form_config, $crm_form_id );

		$this->assertIsInt( $form_id );
		$this->assertGreaterThan( 0, $form_id );
	}

	/**
	 * Test form retrieval.
	 */
	public function test_get_form() {
		$form_config = [
			'form_name' => 'Retrieval Test',
			'fields'    => [
				[
					'name'  => 'test_field',
					'label' => 'Test',
					'type'  => 'text',
				],
			],
		];

		$crm_form_id = 'FormConfigID-12345678-1234-1234-1234-123456789012';
		$form_id     = $this->generator->save_form( $form_config, $crm_form_id );

		$form = $this->generator->get_form( $form_id );

		$this->assertIsObject( $form );
		$this->assertEquals( 'Retrieval Test', $form->name );
		$this->assertEquals( $crm_form_id, $form->crm_form_id );
	}

	/**
	 * Test get all forms.
	 */
	public function test_get_all_forms() {
		// Save multiple forms.
		$form_config = [
			'form_name' => 'Form 1',
			'fields'    => [],
		];

		$crm_form_id = 'FormConfigID-12345678-1234-1234-1234-123456789012';

		$this->generator->save_form( $form_config, $crm_form_id );

		$form_config['form_name'] = 'Form 2';
		$this->generator->save_form( $form_config, $crm_form_id );

		$forms = $this->generator->get_all_forms();

		$this->assertIsArray( $forms );
		$this->assertGreaterThanOrEqual( 2, count( $forms ) );
	}

	/**
	 * Test form deletion.
	 */
	public function test_delete_form() {
		$form_config = [
			'form_name' => 'Delete Test',
			'fields'    => [],
		];

		$crm_form_id = 'FormConfigID-12345678-1234-1234-1234-123456789012';
		$form_id     = $this->generator->save_form( $form_config, $crm_form_id );

		$result = $this->generator->delete_form( $form_id );
		$this->assertTrue( $result );

		$form = $this->generator->get_form( $form_id );
		$this->assertNull( $form );
	}

	/**
	 * Test form update.
	 */
	public function test_update_form() {
		$form_config = [
			'form_name' => 'Original Name',
			'fields'    => [
				[
					'name'  => 'field1',
					'label' => 'Field 1',
					'type'  => 'text',
				],
			],
		];

		$crm_form_id = 'FormConfigID-12345678-1234-1234-1234-123456789012';
		$form_id     = $this->generator->save_form( $form_config, $crm_form_id );

		$updated_config = [
			'form_name' => 'Updated Name',
			'fields'    => [
				[
					'name'  => 'field1',
					'label' => 'Updated Field',
					'type'  => 'email',
				],
			],
		];

		$result = $this->generator->update_form( $form_id, $updated_config, [ 'name' => 'Updated Name' ] );
		$this->assertTrue( $result );

		$form = $this->generator->get_form( $form_id );
		$this->assertEquals( 'Updated Name', $form->name );
	}

	/**
	 * Test is_configured method.
	 */
	public function test_is_configured() {
		// Without API key.
		delete_option( 'aicrmform_settings' );
		$this->assertFalse( $this->generator->is_configured() );

		// With API key.
		update_option( 'aicrmform_settings', [ 'api_key' => 'test-key' ] );
		$this->assertTrue( $this->generator->is_configured() );

		// Cleanup.
		delete_option( 'aicrmform_settings' );
	}
}

