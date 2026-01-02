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
	 * Test that the shortcode class exists.
	 */
	public function test_class_exists() {
		$this->assertTrue( class_exists( 'AICRMFORM_Form_Shortcode' ) );
	}

	/**
	 * Test shortcode can be instantiated.
	 */
	public function test_shortcode_instantiation() {
		$shortcode = new AICRMFORM_Form_Shortcode();
		$this->assertInstanceOf( 'AICRMFORM_Form_Shortcode', $shortcode );
	}

	/**
	 * Test shortcode is registered.
	 */
	public function test_shortcode_registered() {
		$this->assertTrue( shortcode_exists( 'ai_crm_form' ) );
	}
}
