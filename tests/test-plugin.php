<?php

/**
 * Plugin-related tests.
 */
class JB_Test_Cloudinary_Plugin extends WP_UnitTestCase {

	/**
	 * Setup.
	 */
	static function setUpBeforeClass() {
		add_filter( 'cloudinary_content_url', function( $url ) {
			return 'https://test.dev/wp-content';
		} );
	}

	/**
	 * @covers \JB\Cloudinary\Core::setup()
	 */
	function test_setup() {
		$cloudinary = JB\Cloudinary\Core::get_instance();
		$this->assertFalse( $cloudinary->_setup , 'Cloudinary already setup.' );

		update_option( 'cloudinary_cloud_name', 'test-cloud' );
		update_option( 'cloudinary_auto_mapping_folder', 'test-auto-folder' );
		$cloudinary->setup();

		$this->assertTrue( $cloudinary->_setup , 'Cloudinary not setup.' );
	}

}
