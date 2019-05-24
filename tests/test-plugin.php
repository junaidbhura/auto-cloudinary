<?php

/**
 * Plugin-related tests.
 */
class JB_Test_Cloudinary_Plugin extends WP_UnitTestCase {

	private static $_upload_dir    = '';
	private static $_image_id      = 0;
	private static $_attached_file = null;

	/**
	 * Setup.
	 */
	static function setUpBeforeClass() {
		/**
		 * Set aspect ratio the same as the original image (1920x1080),
		 * so that wp_image_matches_ratio() doesn't strip them
		 * from wp_calculate_image_srcset()
		 */
		update_option( 'large_size_w', 1024 );
		update_option( 'large_size_h', 576 );
		update_option( 'medium_size_w', 300 );
		update_option( 'medium_size_h', 169 );

		add_image_size( 'different_aspect_ratio', 400, 200, true );

		self::$_upload_dir    = wp_upload_dir();
		self::$_image_id      = self::upload_image();
		self::$_attached_file = get_attached_file( self::$_image_id );

		update_option( 'cloudinary_default_hard_crop', 'fill' );
		update_option( 'cloudinary_default_soft_crop', 'fit' );
		update_option( 'cloudinary_content_images', '1' );
		JB\Cloudinary\bootstrap();
	}

	/**
	 * Tear down.
	 */
	static function tearDownAfterClass() {
		wp_delete_attachment( self::$_image_id, true );
	}

	/**
	 * Upload an image.
	 *
	 * @return int|WP_Error
	 */
	static function upload_image() {
		$wp_upload_dir = self::$_upload_dir;

		$file_name = $wp_upload_dir['path'] . DIRECTORY_SEPARATOR . 'image-' . rand_str( 6 ) . '.jpg';
		$file_type = wp_check_filetype( basename( $file_name ), null );
		copy( JB_CLOUDINARY_PATH . '/tests/data/image.jpg', $file_name );

		$attachment = array(
			'guid'           => $wp_upload_dir['url'] . '/' . basename( $file_name ),
			'post_mime_type' => $file_type['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $file_name ) ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$image_id = wp_insert_attachment( $attachment, $file_name );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attach_data = wp_generate_attachment_metadata( $image_id, $file_name );
		wp_update_attachment_metadata( $image_id, $attach_data );

		return $image_id;
	}

	/**
	 * Get the path to the uploaded image.
	 *
	 * @param  string $file_name
	 * @return string
	 */
	function get_image_path( $file_name = '' ) {
		$file          = self::$_attached_file;
		$file_info     = pathinfo( $file );
		$wp_upload_dir = self::$_upload_dir;

		if ( empty( $file_name ) ) {
			return $wp_upload_dir['subdir'] . '/' . $file_info['basename'];
		} else {
			return $wp_upload_dir['subdir'] . '/' . $file_info['filename'] . '/' . $file_name . '.' . $file_info['extension'];
		}
	}

	/**
	 * @covers \JB\Cloudinary\Core::setup()
	 */
	function test_setup() {
		$cloudinary = JB\Cloudinary\Core::get_instance();
		$this->assertFalse( $cloudinary->_setup, 'Cloudinary already setup.' );

		update_option( 'cloudinary_cloud_name', 'test-cloud' );
		update_option( 'cloudinary_auto_mapping_folder', 'test-auto-folder' );
		$cloudinary->setup();

		$this->assertTrue( $cloudinary->_setup, 'Cloudinary not setup.' );
	}

	/**
	 * @covers \JB\Cloudinary\Core::get_domain()
	 */
	function test_domain() {
		$cloudinary = JB\Cloudinary\Core::get_instance();

		$this->assertEquals( $cloudinary->get_domain(), 'https://res.cloudinary.com', 'Cloudinary not setup.' );

		$cloudinary->_urls = array();
		add_filter( 'cloudinary_urls', function( $urls ) {
			return array( 'https://res-1.cloudinary.com', 'https://res-2.cloudinary.com', 'https://res-3.cloudinary.com' );
		} );

		$this->assertEquals( $cloudinary->get_domain(), 'https://res-1.cloudinary.com', 'Incorrect URL.' );
		$this->assertEquals( $cloudinary->get_domain(), 'https://res-2.cloudinary.com', 'Incorrect URL.' );
		$this->assertEquals( $cloudinary->get_domain(), 'https://res-3.cloudinary.com', 'Incorrect URL.' );
		$this->assertEquals( $cloudinary->get_domain(), 'https://res-1.cloudinary.com', 'Incorrect URL.' );
	}

	/**
	 * @covers cloudinary_url()
	 * @covers \JB\Cloudinary\Core::get_url()
	 */
	function test_get_url() {
		$test_file_name     = 'test-file-name';
		$image_path         = $this->get_image_path();
		$image_path_2       = $this->get_image_path( $test_file_name );
		$original_image_url = cloudinary_get_original_url( self::$_image_id );
		$options            = array(
			'transform' => array(
				'width'   => 300,
				'height'  => 200,
				'crop'    => 'fill',
				'quality' => '80',
				'gravity' => 'face',
			),
		);
		$options_2          = array(
			'file_name' => $test_file_name,
		);
		$options_3          = array_merge( $options, $options_2 );

		$this->assertEquals( cloudinary_url( self::$_image_id ), 'https://res-2.cloudinary.com/test-cloud/test-auto-folder' . $image_path, 'Incorrect URL.' );
		$this->assertEquals( cloudinary_url( $original_image_url ), 'https://res-3.cloudinary.com/test-cloud/test-auto-folder' . $image_path, 'Incorrect URL.' );

		$this->assertEquals( cloudinary_url( self::$_image_id, $options ), 'https://res-1.cloudinary.com/test-cloud/w_300,h_200,c_fill,q_80,g_face/test-auto-folder' . $image_path, 'Incorrect URL.' );
		$this->assertEquals( cloudinary_url( $original_image_url, $options ), 'https://res-2.cloudinary.com/test-cloud/w_300,h_200,c_fill,q_80,g_face/test-auto-folder' . $image_path, 'Incorrect URL.' );

		$this->assertEquals( cloudinary_url( self::$_image_id, $options_2 ), 'https://res-3.cloudinary.com/test-cloud/images/test-auto-folder' . $image_path_2, 'Incorrect URL.' );
		$this->assertEquals( cloudinary_url( $original_image_url, $options_2 ), 'https://res-1.cloudinary.com/test-cloud/images/test-auto-folder' . $image_path_2, 'Incorrect URL.' );

		$this->assertEquals( cloudinary_url( self::$_image_id, $options_3 ), 'https://res-2.cloudinary.com/test-cloud/images/w_300,h_200,c_fill,q_80,g_face/test-auto-folder' . $image_path_2, 'Incorrect URL.' );
		$this->assertEquals( cloudinary_url( $original_image_url, $options_3 ), 'https://res-3.cloudinary.com/test-cloud/images/w_300,h_200,c_fill,q_80,g_face/test-auto-folder' . $image_path_2, 'Incorrect URL.' );
	}

	/**
	 * @covers cloudinary_update_content_images()
	 */
	function test_update_content_images() {
		cloudinary_ignore_start();
		$content         = 'Test content. ' . get_image_tag( self::$_image_id, '', '', 'none', 'full' );
		$updated_content = str_replace( self::$_upload_dir['baseurl'], 'https://res-1.cloudinary.com/test-cloud/w_1920,h_1080,c_fit/test-auto-folder', $content );
		cloudinary_ignore_end();

		$this->assertEquals( cloudinary_update_content_images( $content ), $updated_content, 'Content image incorrect.' );
	}

	/**
	 * @covers \JB\Cloudinary\Frontend::filter_wp_calculate_image_srcset()
	 * @covers \JB\Cloudinary\Frontend::filter_image_downsize()
	 */
	function test_image_srcset() {
		$image_path    = $this->get_image_path();
		$srcset_full   = array(
			'https://res-3.cloudinary.com/test-cloud/w_1920,c_fit/test-auto-folder' . $image_path . ' 1920w',
			'https://res-1.cloudinary.com/test-cloud/w_300,h_169,c_fit/test-auto-folder' . $image_path . ' 300w',
			'https://res-2.cloudinary.com/test-cloud/w_768,h_432,c_fit/test-auto-folder' . $image_path . ' 768w',
			'https://res-3.cloudinary.com/test-cloud/w_1024,h_576,c_fit/test-auto-folder' . $image_path . ' 1024w',
		);
		$srcset_large  = array(
			'https://res-2.cloudinary.com/test-cloud/w_1920,c_fit/test-auto-folder' . $image_path . ' 1920w',
			'https://res-3.cloudinary.com/test-cloud/w_300,h_169,c_fit/test-auto-folder' . $image_path . ' 300w',
			'https://res-1.cloudinary.com/test-cloud/w_768,h_432,c_fit/test-auto-folder' . $image_path . ' 768w',
			'https://res-2.cloudinary.com/test-cloud/w_1024,h_576,c_fit/test-auto-folder' . $image_path . ' 1024w',
		);
		$srcset_medium = array(
			'https://res-1.cloudinary.com/test-cloud/w_1920,c_fit/test-auto-folder' . $image_path . ' 1920w',
			'https://res-2.cloudinary.com/test-cloud/w_300,h_169,c_fit/test-auto-folder' . $image_path . ' 300w',
			'https://res-3.cloudinary.com/test-cloud/w_768,h_432,c_fit/test-auto-folder' . $image_path . ' 768w',
			'https://res-1.cloudinary.com/test-cloud/w_1024,h_576,c_fit/test-auto-folder' . $image_path . ' 1024w',
		);

		$this->assertEquals( wp_get_attachment_image_srcset( self::$_image_id, 'full' ), implode( ', ', $srcset_full ) );
		$this->assertEquals( wp_get_attachment_image_srcset( self::$_image_id, 'large' ), implode( ', ', $srcset_large ) );
		$this->assertEquals( wp_get_attachment_image_srcset( self::$_image_id, 'medium' ), implode( ', ', $srcset_medium ) );
	}

	/**
	 * @covers \JB\Cloudinary\Frontend::filter_image_downsize()
	 */
	function test_wp_get_attachment_image_src() {
		$src = wp_get_attachment_image_src( self::$_image_id, 'full' );
		$this->assertEquals( $src[0], 'https://res-2.cloudinary.com/test-cloud/test-auto-folder' . $this->get_image_path(), 'Incorrect SRC.' );

		$src = wp_get_attachment_image_src( self::$_image_id, 'large' );
		$this->assertEquals( $src[0], 'https://res-3.cloudinary.com/test-cloud/w_1024,h_576,c_fit/test-auto-folder' . $this->get_image_path(), 'Incorrect SRC.' );

		$src = wp_get_attachment_image_src( self::$_image_id, 'medium' );
		$this->assertEquals( $src[0], 'https://res-1.cloudinary.com/test-cloud/w_300,h_169,c_fit/test-auto-folder' . $this->get_image_path(), 'Incorrect SRC.' );

		$src = wp_get_attachment_image_src( self::$_image_id, array( 100, 100 ) );
		$this->assertEquals( $src[0], 'https://res-2.cloudinary.com/test-cloud/w_100,h_100,c_fit/test-auto-folder' . $this->get_image_path(), 'Incorrect SRC.' );
	}

	/**
	 * @covers \JB\Cloudinary\Frontend::filter_image_downsize()
	 */
	function test_wp_get_attachment_image() {
		$image_path = $this->get_image_path();

		$test_string = '<img width="1920" height="1080" src="https://res-3.cloudinary.com/test-cloud/test-auto-folder' . $image_path . '" class="attachment-full size-full" alt="Test Alt" title="Test Title" srcset="https://res-1.cloudinary.com/test-cloud/w_1920,c_fit/test-auto-folder' . $image_path . ' 1920w, https://res-2.cloudinary.com/test-cloud/w_300,h_169,c_fit/test-auto-folder' . $image_path . ' 300w, https://res-3.cloudinary.com/test-cloud/w_768,h_432,c_fit/test-auto-folder' . $image_path . ' 768w, https://res-1.cloudinary.com/test-cloud/w_1024,h_576,c_fit/test-auto-folder' . $image_path . ' 1024w" sizes="(max-width: 1920px) 100vw, 1920px" />';
		$this->assertEquals(
			wp_get_attachment_image( self::$_image_id, 'full', false, array(
				'alt'   => 'Test Alt',
				'title' => 'Test Title',
			) ),
			$test_string,
			'Incorrect attachment image.'
		);

		$test_string = '<img width="300" height="169" src="https://res-2.cloudinary.com/test-cloud/w_300,h_169,c_fit/test-auto-folder' . $image_path . '" class="attachment-medium size-medium" alt="" srcset="https://res-3.cloudinary.com/test-cloud/w_1920,c_fit/test-auto-folder' . $image_path . ' 1920w, https://res-1.cloudinary.com/test-cloud/w_300,h_169,c_fit/test-auto-folder' . $image_path . ' 300w, https://res-2.cloudinary.com/test-cloud/w_768,h_432,c_fit/test-auto-folder' . $image_path . ' 768w, https://res-3.cloudinary.com/test-cloud/w_1024,h_576,c_fit/test-auto-folder' . $image_path . ' 1024w" sizes="(max-width: 300px) 100vw, 300px" />';
		$this->assertEquals(
			wp_get_attachment_image( self::$_image_id, 'medium' ),
			$test_string,
			'Incorrect attachment image.'
		);

		$test_string = '<img width="1024" height="576" src="https://res-1.cloudinary.com/test-cloud/w_1024,h_576,c_fit/test-auto-folder' . $image_path . '" class="attachment-large size-large" alt="" srcset="https://res-2.cloudinary.com/test-cloud/w_1920,c_fit/test-auto-folder' . $image_path . ' 1920w, https://res-3.cloudinary.com/test-cloud/w_300,h_169,c_fit/test-auto-folder' . $image_path . ' 300w, https://res-1.cloudinary.com/test-cloud/w_768,h_432,c_fit/test-auto-folder' . $image_path . ' 768w, https://res-2.cloudinary.com/test-cloud/w_1024,h_576,c_fit/test-auto-folder' . $image_path . ' 1024w" sizes="(max-width: 1024px) 100vw, 1024px" />';
		$this->assertEquals(
			wp_get_attachment_image( self::$_image_id, 'large' ),
			$test_string,
			'Incorrect attachment image.'
		);
	}

	/**
	 * Test default crop.
	 *
	 * @covers cloudinary_default_crop()
	 */
	function test_default_crop() {
		$this->assertEquals( cloudinary_default_crop(), 'fit', 'Incorrect default crop.' );
		$this->assertEquals( cloudinary_default_crop( false ), 'fit', 'Incorrect default crop.' );
		$this->assertEquals( cloudinary_default_crop( true ), 'fill', 'Incorrect default crop.' );
	}

	/**
	 * Test filters.
	 */
	function test_filters() {
		$src = wp_get_attachment_image_src( self::$_image_id, 'large' );
		$this->assertEquals( $src[0], 'https://res-3.cloudinary.com/test-cloud/w_1024,h_576,c_fit/test-auto-folder' . $this->get_image_path(), 'Incorrect SRC.' );

		add_filter( 'cloudinary_default_args', function( $args ) {
			return array(
				'transform' => array(
					'crop' => 'crop',
				),
			);
		}, 10, 1 );

		add_filter( 'cloudinary_args', function( $args, $identifier ) {
			if ( $identifier === self::$_image_id && ! empty( $args ) ) {
				$args['transform']['gravity'] = 'face';
			}
			return $args;
		}, 10, 2 );

		$this->assertEquals(
			cloudinary_url( self::$_image_id ),
			'https://res-1.cloudinary.com/test-cloud/c_crop,g_face/test-auto-folder' . $this->get_image_path(),
			'Incorrect filtered URL.'
		);

		$this->assertEquals(
			cloudinary_url( self::$_image_id, array(
				'transform' => array(
					'width'  => 500,
					'height' => 300,
				),
				'file_name' => 'test-file-name',
			) ),
			'https://res-2.cloudinary.com/test-cloud/images/c_crop,w_500,h_300,g_face/test-auto-folder' . $this->get_image_path( 'test-file-name' ),
			'Incorrect filtered URL.'
		);

		$src = wp_get_attachment_image_src( self::$_image_id, 'different_aspect_ratio' );
		$this->assertEquals( $src[0], 'https://res-3.cloudinary.com/test-cloud/c_fill,w_400,h_200,g_face/test-auto-folder' . $this->get_image_path(), 'Incorrect SRC.' );

		add_filter( 'cloudinary_default_crop', function() {
			return 'scale';
		} );

		$src = wp_get_attachment_image_src( self::$_image_id, 'large' );
		$this->assertEquals( $src[0], 'https://res-1.cloudinary.com/test-cloud/c_scale,w_1024,h_576,g_face/test-auto-folder' . $this->get_image_path(), 'Incorrect SRC.' );

		$src = wp_get_attachment_image_src( self::$_image_id, 'different_aspect_ratio' );
		$this->assertEquals( $src[0], 'https://res-2.cloudinary.com/test-cloud/c_scale,w_400,h_200,g_face/test-auto-folder' . $this->get_image_path(), 'Incorrect SRC.' );

		add_filter( 'cloudinary_default_hard_crop', function() {
			return 'fit';
		} );

		$src = wp_get_attachment_image_src( self::$_image_id, 'large' );
		$this->assertEquals( $src[0], 'https://res-3.cloudinary.com/test-cloud/c_scale,w_1024,h_576,g_face/test-auto-folder' . $this->get_image_path(), 'Incorrect SRC.' );

		$src = wp_get_attachment_image_src( self::$_image_id, 'different_aspect_ratio' );
		$this->assertEquals( $src[0], 'https://res-1.cloudinary.com/test-cloud/c_fit,w_400,h_200,g_face/test-auto-folder' . $this->get_image_path(), 'Incorrect SRC.' );

		add_filter( 'cloudinary_default_soft_crop', function() {
			return 'limit';
		} );

		$src = wp_get_attachment_image_src( self::$_image_id, 'large' );
		$this->assertEquals( $src[0], 'https://res-2.cloudinary.com/test-cloud/c_limit,w_1024,h_576,g_face/test-auto-folder' . $this->get_image_path(), 'Incorrect SRC.' );

		$src = wp_get_attachment_image_src( self::$_image_id, 'different_aspect_ratio' );
		$this->assertEquals( $src[0], 'https://res-3.cloudinary.com/test-cloud/c_fit,w_400,h_200,g_face/test-auto-folder' . $this->get_image_path(), 'Incorrect SRC.' );

		$this->assertEquals(
			cloudinary_url( self::$_image_id, array(
				'transform' => array(
					'progressive' => true,
				),
			) ),
			'https://res-1.cloudinary.com/test-cloud/c_crop,fl_progressive,g_face/test-auto-folder' . $this->get_image_path(),
			'Incorrect filtered URL.'
		);

		$this->assertEquals(
			cloudinary_url( self::$_image_id, array(
				'transform' => array(
					'progressive' => 'semi',
				),
			) ),
			'https://res-2.cloudinary.com/test-cloud/c_crop,fl_progressive:semi,g_face/test-auto-folder' . $this->get_image_path(),
			'Incorrect filtered URL.'
		);
	}

	/**
	 * @covers \JB\Cloudinary\Core::valid_value()
	 */
	function test_invalid_values() {
		$this->assertEquals(
			cloudinary_url( self::$_image_id, array(
				'transform' => array(
					'width'   => 0,
					'height'  => 0,
					'crop'    => 'fill',
					'gravity' => 'face',
				),
			) ),
			'https://res-3.cloudinary.com/test-cloud/c_fill,g_face/test-auto-folder' . $this->get_image_path(),
			'Incorrect value.'
		);

		$this->assertEquals(
			cloudinary_url( self::$_image_id, array(
				'transform' => array(
					'width'   => 100,
					'height'  => 0,
					'crop'    => 'fill',
					'gravity' => 'face',
				),
			) ),
			'https://res-1.cloudinary.com/test-cloud/w_100,c_fill,g_face/test-auto-folder' . $this->get_image_path(),
			'Incorrect value.'
		);

		$this->assertEquals(
			cloudinary_url( self::$_image_id, array(
				'transform' => array(
					'width'   => 0,
					'height'  => 100,
					'crop'    => 'fill',
					'gravity' => 'face',
				),
			) ),
			'https://res-2.cloudinary.com/test-cloud/h_100,c_fill,g_face/test-auto-folder' . $this->get_image_path(),
			'Incorrect value.'
		);

		$this->assertEquals(
			cloudinary_url( self::$_image_id, array(
				'transform' => array(
					'width'   => '',
					'height'  => '',
					'crop'    => 'fill',
					'gravity' => 'face',
				),
			) ),
			'https://res-3.cloudinary.com/test-cloud/c_fill,g_face/test-auto-folder' . $this->get_image_path(),
			'Incorrect value.'
		);
	}

	/**
	 * Test REST API call.
	 */
	function test_rest_api_call() {
		$request = new WP_REST_Request( 'GET', '/wp/v2/media' );
		$request->set_param( 'include', self::$_image_id );

		$response = rest_do_request( $request );
		$this->assertEquals( $response->data[0]['media_details']['sizes']['full']['source_url'], 'http://example.org/wp-content/uploads' . $this->get_image_path() );

		add_filter( 'cloudinary_allow_rest_api_call', '__return_true' );
		$response = rest_do_request( $request );
		$this->assertEquals( $response->data[0]['media_details']['sizes']['full']['source_url'], 'https://res-1.cloudinary.com/test-cloud/test-auto-folder' . $this->get_image_path() );
	}

}
