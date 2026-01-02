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
	 * Test that the plugin is loaded.
	 */
	public function test_plugin_loaded() {
		$this->assertTrue( class_exists( 'AI_CRM_Form' ) );
	}

	/**
	 * Test that the generator class exists.
	 */
	public function test_class_exists() {
		$this->assertTrue( class_exists( 'AICRMFORM_Form_Generator' ) );
	}

	/**
	 * Test generator can be instantiated.
	 */
	public function test_generator_instantiation() {
		$generator = new AICRMFORM_Form_Generator();
		$this->assertInstanceOf( 'AICRMFORM_Form_Generator', $generator );
	}

	/**
	 * Test is_configured method without API key.
	 */
	public function test_is_configured_without_api_key() {
		delete_option( 'aicrmform_settings' );
		$generator = new AICRMFORM_Form_Generator();
		$this->assertFalse( $generator->is_configured() );
	}

	/**
	 * Test plugin constants are defined.
	 */
	public function test_plugin_constants() {
		$this->assertTrue( defined( 'AICRMFORM_VERSION' ) );
		$this->assertTrue( defined( 'AICRMFORM_PLUGIN_DIR' ) );
		$this->assertTrue( defined( 'AICRMFORM_PLUGIN_URL' ) );
	}
}
