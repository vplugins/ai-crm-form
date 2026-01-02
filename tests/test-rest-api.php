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
	 * Test that the REST API class exists.
	 */
	public function test_class_exists() {
		$this->assertTrue( class_exists( 'AICRMFORM_REST_API' ) );
	}

	/**
	 * Test REST API can be instantiated.
	 */
	public function test_rest_api_instantiation() {
		$rest_api = new AICRMFORM_REST_API();
		$this->assertInstanceOf( 'AICRMFORM_REST_API', $rest_api );
	}
}
